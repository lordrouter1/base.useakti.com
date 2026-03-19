import { Router } from 'express';
import express from 'express';
import { WebhookController } from '../controllers/WebhookController.js';
import { tenantMiddleware } from '../middlewares/tenantMiddleware.js';

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
 * O tenant é resolvido via query param: ?tenant=db_name
 * Isso é necessário porque os gateways não enviam JWT, mas precisamos
 * saber de qual tenant é a notificação.
 *
 * NOTA: Estas rotas são montadas em app.js ANTES do express.json()
 * global, para que o rawBody seja capturado pelo verify callback.
 *
 * Fluxo:
 *   POST /api/webhooks/stripe?tenant=akti_empresa_x
 *   POST /api/webhooks/mercadopago?tenant=akti_empresa_x
 *   POST /api/webhooks/pagseguro?tenant=akti_empresa_x
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
 * Usa query param ?tenant=db_name em vez do JWT.
 * Injeta req.user com tenant_db para que o tenantMiddleware funcione.
 */
function webhookTenantResolver(req, _res, next) {
  const tenantDb = req.query.tenant;
  if (!tenantDb) {
    console.error(`[Webhook] Missing 'tenant' query param — URL: ${req.originalUrl}`);
    return next(Object.assign(new Error('Query param "tenant" is required.'), { status: 400 }));
  }
  // Simular req.user para que tenantMiddleware resolva o banco
  req.user = { tenant_db: tenantDb, webhook: true };
  return next();
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
