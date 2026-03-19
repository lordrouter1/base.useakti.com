import { Sequelize } from 'sequelize';
import { env } from './env.js';

/**
 * ════════════════════════════════════════════════════════════════
 * TenantPool — Gerenciador de conexões multi-tenant.
 *
 * Cada tenant tem seu próprio banco de dados MySQL. Este módulo:
 *
 *   1. Mantém um pool de conexões Sequelize por tenant (chave = db_name).
 *   2. Consulta o banco master (akti_master.tenant_clients) para
 *      resolver as credenciais do tenant quando necessário.
 *   3. Remove automaticamente pools ociosos após IDLE_TTL_MS.
 *
 * Uso simplificado (dentro de qualquer handler com req.db):
 *
 *   const { Product } = getModels(req.db);
 *   const rows = await Product.findAll();
 *
 * @see .github/instructions/nodejs-api.md
 * ════════════════════════════════════════════════════════════════
 */

// ── Pool do banco master (singleton) ──────────────────────────

let _masterInstance = null;

/**
 * Retorna a instância Sequelize conectada ao banco master.
 * O master é usado APENAS para resolver credenciais de tenants.
 */
export function getMasterSequelize() {
  if (!_masterInstance) {
    _masterInstance = new Sequelize(
      env.DB_MASTER_NAME,
      env.DB_MASTER_USER,
      env.DB_MASTER_PASS,
      {
        host: env.DB_MASTER_HOST,
        port: env.DB_MASTER_PORT,
        dialect: 'mysql',
        logging: env.NODE_ENV === 'development' ? console.log : false,
        pool: { max: 3, min: 0, acquire: 30_000, idle: 60_000 },
      },
    );
  }
  return _masterInstance;
}

/**
 * Testa a conexão com o banco master. Chamar uma vez no startup.
 */
export async function testMasterConnection() {
  const master = getMasterSequelize();
  await master.authenticate();
  console.log('[DB] Master connection (akti_master) OK.');
}

// ── Cache de credenciais do master ────────────────────────────

/** @type {Map<string, { host: string, port: number, name: string, user: string, pass: string, resolvedAt: number }>} */
const _credentialsCache = new Map();
const CREDENTIALS_TTL_MS = 5 * 60 * 1000; // 5 min

/**
 * Busca credenciais do tenant no banco master (com cache de 5 min).
 * @param {string} dbName  Nome do banco do tenant (ex: 'akti_teste')
 * @returns {Promise<{host:string, port:number, name:string, user:string, pass:string}>}
 */
async function resolveTenantCredentials(dbName) {
  const cached = _credentialsCache.get(dbName);
  if (cached && Date.now() - cached.resolvedAt < CREDENTIALS_TTL_MS) {
    return cached;
  }

  const master = getMasterSequelize();
  const rows = await master.query(
    `SELECT db_host, db_port, db_name, db_user, db_password
       FROM tenant_clients
      WHERE db_name = :dbName AND is_active = 1
      LIMIT 1`,
    { replacements: { dbName }, type: Sequelize.QueryTypes.SELECT },
  );

  if (!rows || rows.length === 0) {
    throw Object.assign(
      new Error(`Tenant DB "${dbName}" não encontrado ou inativo.`),
      { status: 404 },
    );
  }

  const row = rows[0];
  const creds = {
    host: row.db_host || env.DB_MASTER_HOST,
    port: parseInt(row.db_port, 10) || 3306,
    name: row.db_name,
    user: row.db_user,
    pass: row.db_password,
    resolvedAt: Date.now(),
  };

  _credentialsCache.set(dbName, creds);
  return creds;
}

// ── Pool de conexões por tenant ───────────────────────────────

/**
 * @typedef {Object} TenantEntry
 * @property {Sequelize} sequelize
 * @property {number}    lastUsedAt  Timestamp da última utilização
 */

/** @type {Map<string, TenantEntry>} */
const _pool = new Map();

/** Tempo máximo de inatividade antes de fechar a conexão (padrão: 10 min) */
const IDLE_TTL_MS = parseInt(process.env.TENANT_POOL_IDLE_MS, 10) || 10 * 60 * 1000;

/** Intervalo do garbage collector (1 min) */
const GC_INTERVAL_MS = 60 * 1000;

/**
 * TenantPool — API pública para obter conexões de tenant.
 */
export const tenantPool = {
  /**
   * Retorna (ou cria) a instância Sequelize para o banco do tenant.
   *
   * @param {string} dbName  Nome do banco (ex: 'akti_teste')
   * @returns {Promise<Sequelize>}
   */
  async acquire(dbName) {
    if (!dbName || typeof dbName !== 'string') {
      throw Object.assign(new Error('dbName é obrigatório.'), { status: 400 });
    }

    const existing = _pool.get(dbName);
    if (existing) {
      existing.lastUsedAt = Date.now();
      return existing.sequelize;
    }

    // Resolver credenciais no master
    const creds = await resolveTenantCredentials(dbName);

    const sequelize = new Sequelize(creds.name, creds.user, creds.pass, {
      host: creds.host,
      port: creds.port,
      dialect: 'mysql',
      logging: env.NODE_ENV === 'development' ? console.log : false,
      pool: { max: 5, min: 0, acquire: 30_000, idle: 10_000 },
    });

    // Testar conexão antes de cachear
    await sequelize.authenticate();
    console.log(`[DB] Tenant pool created: ${creds.name} @ ${creds.host}`);

    _pool.set(dbName, { sequelize, lastUsedAt: Date.now() });
    return sequelize;
  },

  /**
   * Fecha e remove a conexão de um tenant específico.
   */
  async release(dbName) {
    const entry = _pool.get(dbName);
    if (entry) {
      await entry.sequelize.close();
      _pool.delete(dbName);
      _credentialsCache.delete(dbName);
      console.log(`[DB] Tenant pool released: ${dbName}`);
    }
  },

  /**
   * Fecha todas as conexões (graceful shutdown).
   */
  async closeAll() {
    const tasks = [];
    for (const [name, entry] of _pool) {
      tasks.push(entry.sequelize.close().then(() => console.log(`[DB] Closed: ${name}`)));
    }
    _pool.clear();
    _credentialsCache.clear();
    if (_masterInstance) {
      tasks.push(_masterInstance.close().then(() => console.log('[DB] Closed: master')));
      _masterInstance = null;
    }
    await Promise.allSettled(tasks);
  },

  /** Número de pools ativos (monitoramento). */
  get size() {
    return _pool.size;
  },
};

// ── Garbage Collector — remove pools ociosos ──────────────────

function gcSweep() {
  const now = Date.now();
  for (const [name, entry] of _pool) {
    if (now - entry.lastUsedAt > IDLE_TTL_MS) {
      entry.sequelize.close().catch(() => {});
      _pool.delete(name);
      _credentialsCache.delete(name);
      console.log(`[DB/GC] Evicted idle tenant pool: ${name}`);
    }
  }
}

const _gcTimer = setInterval(gcSweep, GC_INTERVAL_MS);
_gcTimer.unref(); // Não impede o Node de encerrar
