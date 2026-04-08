import { Router } from 'express';
import express from 'express';
import { Sequelize } from 'sequelize';
import { WebhookController } from '../controllers/WebhookController.js';
import { tenantMiddleware } from '../middlewares/tenantMiddleware.js';
import { getMasterSequelize } from '../config/database.js';
import { env } from '../config/env.js';

const router = Router();
const controller = new WebhookController();

/**
 * ════════════════════════════════════════════════════════════════
 * Webhook Routes — Gateways de Pagamento
 *
 * IMPORTANTE: Estas rotas NÃO passam pelo authMiddleware (JWT).
 * São chamadas diretamente pelos gateways (Stripe, Mercado Pago, etc.)
 * A autenticação é feita pela validação da assinatura HMAC no WebhookService.
 *
 * O tenant é resolvido via:
 *   1. Query param: ?tenant=db_name  (método legado, compatível)
 *   2. Subdomínio do Host header     (método novo, checkout transparente)
 *
 * NOTA: Estas rotas são montadas em app.js ANTES do express.json()
 * global, para que o rawBody seja capturado pelo verify callback.
 *
 * Fluxo:
 *   POST /api/webhooks/stripe?tenant=akti_empresa_x     (query param)
 *   POST /api/webhooks/mercadopago                        (subdomain)
 *   POST /api/webhooks/pagseguro?tenant=akti_empresa_x   (query param)
 *
 * O rawBody é capturado pelo middleware para validação de assinatura.
 * ════════════════════════════════════════════════════════════════
 */

// Middleware para capturar raw body (necessário para HMAC)
// Este express.json() é o ÚNICO parser para as rotas de webhook.
// O express.json() global do app.js NÃO se aplica porque essas
// rotas são montadas antes dele.
router.use(express.json({
  limit: '5mb', // Payloads de webhook podem ser grandes
  verify: (req, _res, buf) => {
    req.rawBody = buf.toString('utf8');
  },
}));

/**
 * Middleware leve de tenant para webhooks.
 * Resolução em 2 etapas (prioridade):
 *   1. Query param ?tenant=db_name (compatibilidade)
 *   2. Subdomínio do Host header → lookup no master (checkout transparente)
 * Injeta req.user com tenant_db para que o tenantMiddleware funcione.
 */
async function webhookTenantResolver(req, _res, next) {
  // 1. Tentar query param (método legado)
  let tenantDb = req.query.tenant;

  // 2. Se não tem query param, tentar resolver via subdomínio
  if (!tenantDb) {
    const subdomain = extractSubdomain(req.hostname);
    if (subdomain) {
      try {
        tenantDb = await resolveSubdomainToDb(subdomain);
        console.log(`[Webhook] Subdomain '${subdomain}' resolved to tenant '${tenantDb}'`);
      } catch (err) {
        console.error(`[Webhook] Failed to resolve subdomain '${subdomain}':`, err.message);
      }
    }
  }

  if (!tenantDb) {
    console.error(`[Webhook] Could not resolve tenant — URL: ${req.originalUrl}, Host: ${req.hostname}`);
    return next(Object.assign(new Error('Could not resolve tenant. Provide ?tenant= param or use subdomain.'), { status: 400 }));
  }

  // Simular req.user para que tenantMiddleware resolva o banco
  req.user = { tenant_db: tenantDb, webhook: true };
  return next();
}

/**
 * Extrai subdomínio do hostname.
 * Ex: "empresa.useakti.com" → "empresa"
 *     "localhost" → null
 */
function extractSubdomain(hostname) {
  if (!hostname) return null;
  // Remover porta se presente
  const host = hostname.split(':')[0];
  const baseDomain = env.BASE_DOMAIN || 'useakti.com';
  if (host.endsWith('.' + baseDomain)) {
    const sub = host.slice(0, -(baseDomain.length + 1));
    if (sub && sub !== 'www' && sub !== 'api') {
      return sub;
    }
  }
  return null;
}

/** @type {Map<string, {db_name: string, resolvedAt: number}>} */
const _subdomainCache = new Map();
const SUBDOMAIN_CACHE_TTL = 5 * 60 * 1000; // 5 min

/**
 * Consulta o banco master para resolver subdomínio → db_name.
 */
async function resolveSubdomainToDb(subdomain) {
  const cached = _subdomainCache.get(subdomain);
  if (cached && Date.now() - cached.resolvedAt < SUBDOMAIN_CACHE_TTL) {
    return cached.db_name;
  }

  const master = getMasterSequelize();
  const rows = await master.query(
    `SELECT db_name FROM tenant_clients WHERE subdomain = :subdomain AND is_active = 1 LIMIT 1`,
    { replacements: { subdomain }, type: Sequelize.QueryTypes.SELECT },
  );

  if (!rows || rows.length === 0) {
    throw new Error(`Subdomain "${subdomain}" not found or inactive.`);
  }

  const dbName = rows[0].db_name;
  _subdomainCache.set(subdomain, { db_name: dbName, resolvedAt: Date.now() });
  return dbName;
}

// Aplicar resolução de tenant antes do handler
router.use(webhookTenantResolver);
router.use(tenantMiddleware);

/**
 * GET /api/webhooks/:gateway?tenant=db_name
 * Endpoint de diagnóstico — gateways podem testar se a URL está acessível.
 * Retorna 200 com informações básicas (sem processar nada).
 */
router.get('/:gateway', (req, res) => {
  const { gateway } = req.params;
  console.log(`[Webhook] GET health check for gateway '${gateway}' — tenant '${req.query.tenant}'`);
  res.status(200).json({
    status: 'ok',
    gateway,
    tenant: req.query.tenant || null,
    message: `Webhook endpoint for '${gateway}' is reachable. Use POST to send notifications.`,
    timestamp: new Date().toISOString(),
  });
});

// POST /api/webhooks/:gateway
router.post('/:gateway', controller.handle);

export default router;
