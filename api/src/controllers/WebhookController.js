import { HTTP_STATUS } from '../config/constants.js';
import { WebhookService } from '../services/WebhookService.js';

/**
 * WebhookController — Recebe e processa webhooks de gateways de pagamento.
 *
 * Endpoints:
 *   POST /api/webhooks/:gateway  — Recebe webhook de qualquer gateway
 *
 * Fluxo:
 *   1. Captura raw body (necessário para validação de assinatura)
 *   2. Resolve tenant via query param ?tenant=db_name
 *   3. Delega para WebhookService que valida, parseia, loga e atualiza
 *
 * IMPORTANTE: Webhooks são chamados pelos gateways externos, então NÃO
 * passam pelo authMiddleware (JWT). A autenticação é feita pela
 * validação da assinatura do webhook (HMAC).
 *
 * @see api/src/services/WebhookService.js
 * @see api/src/routes/webhookRoutes.js
 */
export class WebhookController {

  /**
   * POST /api/webhooks/:gateway?tenant=db_name
   *
   * Processa o webhook recebido do gateway de pagamento.
   */
  handle = async (req, res) => {
    const { gateway } = req.params;
    const tenant = req.query.tenant || '?';

    if (!gateway) {
      return res.status(HTTP_STATUS.BAD_REQUEST).json({
        error: 'Gateway slug is required.',
      });
    }

    // O raw body é injetado pelo middleware express.json({ verify })
    const rawBody = req.rawBody || '';
    const parsedBody = req.body || {};

    console.log(`[Webhook] Received ${gateway} webhook for tenant '${tenant}' — rawBody length: ${rawBody.length}, body keys: ${Object.keys(parsedBody).join(',') || '(empty)'}`);

    // Diagnóstico: se rawBody está vazio mas parsedBody não, o express.json global consumiu o body
    if (!rawBody && Object.keys(parsedBody).length > 0) {
      console.warn(`[Webhook] WARNING: rawBody is empty but parsedBody has data. This means express.json() consumed the body before the verify callback could capture it. Check middleware order in app.js.`);
    }

    try {
      const service = new WebhookService(req.models, req.db);

      const result = await service.processWebhook(
        gateway,
        rawBody,
        parsedBody,
        req.headers,
      );

      if (!result.success) {
        console.warn(`[Webhook] ${gateway}@${tenant}: REJECTED — ${result.message}`);
        return res.status(HTTP_STATUS.BAD_REQUEST).json({
          error: result.message,
        });
      }

      console.log(`[Webhook] ${gateway}@${tenant}: OK — tx #${result.transactionId} — ${result.message}`);

      // Gateways esperam 200 OK para confirmar recebimento
      return res.status(HTTP_STATUS.OK).json({
        success: true,
        message: result.message,
      });
    } catch (err) {
      console.error(`[Webhook] ${gateway}@${tenant}: EXCEPTION`, err);
      // Retornar 200 mesmo em erro para evitar retry loop dos gateways
      // mas logar o erro para investigação
      return res.status(HTTP_STATUS.OK).json({
        success: false,
        message: 'Webhook received but processing failed.',
      });
    }
  };
}
