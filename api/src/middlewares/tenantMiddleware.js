import { tenantPool } from '../config/database.js';
import { getModels } from '../models/index.js';
import { HTTP_STATUS } from '../config/constants.js';

/**
 * ════════════════════════════════════════════════════════════════
 * Tenant Middleware — resolve o banco do tenant e injeta no request.
 *
 * O tenant é identificado pelo campo `tenant_db` presente no JWT
 * (decodificado previamente pelo authMiddleware).
 *
 * Após resolver, injeta:
 *   • req.tenantDb   → Nome do banco (string)
 *   • req.db         → Instância Sequelize conectada ao banco do tenant
 *   • req.models     → Objeto com todos os models já definidos
 *
 * Dependência: Este middleware DEVE rodar DEPOIS do authMiddleware.
 * ════════════════════════════════════════════════════════════════
 */

const TENANT_DB_PATTERN = /^[a-zA-Z0-9_]+$/;
const MAX_TENANT_DB_LENGTH = 64;

function isValidTenantDb(value) {
  return (
    typeof value === 'string' &&
    value.length > 0 &&
    value.length <= MAX_TENANT_DB_LENGTH &&
    TENANT_DB_PATTERN.test(value)
  );
}

export async function tenantMiddleware(req, res, next) {
  try {
    // O authMiddleware já decodificou o JWT e populou req.user
    const tenantDb = req.user?.tenant_db;

    if (!tenantDb) {
      return res.status(HTTP_STATUS.BAD_REQUEST).json({
        error: 'Token JWT não contém tenant_db.',
      });
    }

    if (!isValidTenantDb(tenantDb)) {
      return res.status(HTTP_STATUS.BAD_REQUEST).json({
        error: 'Valor de tenant_db inválido.',
      });
    }

    // Obter (ou criar) o pool Sequelize para este tenant
    const sequelize = await tenantPool.acquire(tenantDb);

    // Injetar no request para uso nos controllers/services
    req.tenantDb = tenantDb;
    req.db = sequelize;
    req.models = getModels(sequelize);

    return next();
  } catch (err) {
    // Se o tenant não foi encontrado no master, retornar 404
    if (err.status) {
      return res.status(err.status).json({ error: err.message });
    }
    return next(err);
  }
}
