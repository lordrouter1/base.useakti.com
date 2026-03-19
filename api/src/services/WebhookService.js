import crypto from 'crypto';
import https from 'https';
import http from 'http';

/**
 * WebhookService — Processa webhooks de gateways de pagamento.
 *
 * Responsabilidades:
 *   1. Validar assinatura do webhook (por gateway)
 *   2. Parsear o payload padronizado
 *   3. Fazer lookup na API do gateway quando necessário (ex: Mercado Pago)
 *   4. Logar a transação no banco do tenant
 *   5. Atualizar parcelas/pedidos conforme o status
 *
 * Multi-tenant: o banco é resolvido por req.db (via tenantMiddleware).
 *
 * @see api/src/controllers/WebhookController.js
 */
export class WebhookService {
  /**
   * @param {object} models  — { PaymentGateway, PaymentGatewayTransaction } do tenant
   * @param {import('sequelize').Sequelize} sequelize — Instância Sequelize do tenant
   */
  constructor(models, sequelize) {
    this.models = models;
    this.sequelize = sequelize;
  }

  // ══════════════════════════════════════════════════════════════
  // Validação de Assinatura
  // ══════════════════════════════════════════════════════════════

  /**
   * Valida assinatura do webhook conforme o gateway.
   *
   * @param {string} gatewaySlug
   * @param {string} rawBody       — Body cru (Buffer ou string)
   * @param {object} headers       — Headers HTTP
   * @param {string} webhookSecret — Secret configurado no gateway
   * @returns {boolean}
   */
  validateSignature(gatewaySlug, rawBody, headers, webhookSecret) {
    if (!webhookSecret) {
      // Se não tem secret configurado, aceita (em sandbox)
      console.warn(`[Webhook] ${gatewaySlug}: No webhook_secret configured — skipping signature validation.`);
      return true;
    }

    switch (gatewaySlug) {
      case 'stripe':
        return this._validateStripeSignature(rawBody, headers, webhookSecret);
      case 'mercadopago':
        return this._validateMercadoPagoSignature(rawBody, headers, webhookSecret);
      case 'pagseguro':
        return this._validatePagSeguroSignature(rawBody, headers, webhookSecret);
      default:
        console.warn(`[Webhook] Unknown gateway '${gatewaySlug}' — cannot validate signature.`);
        return false;
    }
  }

  /**
   * Stripe: valida via header stripe-signature (t=timestamp,v1=hash).
   */
  _validateStripeSignature(rawBody, headers, secret) {
    const sigHeader = headers['stripe-signature'] || '';
    if (!sigHeader) return false;

    const parts = {};
    sigHeader.split(',').forEach(item => {
      const [key, val] = item.trim().split('=');
      if (!parts[key]) parts[key] = [];
      parts[key].push(val);
    });

    const timestamp = parts.t?.[0];
    const signatures = parts.v1 || [];
    if (!timestamp || !signatures.length) return false;

    // Rejeitar timestamps muito antigos (> 5 min) para evitar replay attacks
    const now = Math.floor(Date.now() / 1000);
    if (Math.abs(now - parseInt(timestamp, 10)) > 300) {
      console.warn('[Webhook] Stripe: timestamp too old, possible replay attack.');
      return false;
    }

    const signedPayload = `${timestamp}.${rawBody}`;
    const expected = crypto.createHmac('sha256', secret).update(signedPayload).digest('hex');

    return signatures.some(sig => {
      try {
        return crypto.timingSafeEqual(
          Buffer.from(expected, 'hex'),
          Buffer.from(sig, 'hex'),
        );
      } catch {
        return false;
      }
    });
  }

  /**
   * Mercado Pago: valida via header x-signature (ts=...,v1=...).
   */
  _validateMercadoPagoSignature(rawBody, headers, secret) {
    const xSignature = headers['x-signature'] || '';
    const xRequestId = headers['x-request-id'] || '';
    if (!xSignature) return false;

    const parts = {};
    xSignature.split(',').forEach(item => {
      const [key, val] = item.trim().split('=');
      parts[key] = val;
    });

    const ts = parts.ts;
    const v1 = parts.v1;
    if (!ts || !v1) return false;

    let dataId = '';
    try {
      const parsed = JSON.parse(rawBody);
      dataId = parsed?.data?.id || '';
    } catch { /* ignore */ }

    const manifest = `id:${dataId};request-id:${xRequestId};ts:${ts};`;
    const hash = crypto.createHmac('sha256', secret).update(manifest).digest('hex');

    try {
      return crypto.timingSafeEqual(Buffer.from(hash, 'hex'), Buffer.from(v1, 'hex'));
    } catch {
      return false;
    }
  }

  /**
   * PagSeguro: valida via header x-pagseguro-signature (hmac-sha256).
   */
  _validatePagSeguroSignature(rawBody, headers, secret) {
    const signature = headers['x-pagseguro-signature'] || '';
    if (!signature) return true; // PagSeguro nem sempre envia

    const expected = crypto.createHmac('sha256', secret).update(rawBody).digest('hex');
    try {
      return crypto.timingSafeEqual(Buffer.from(expected, 'hex'), Buffer.from(signature, 'hex'));
    } catch {
      return false;
    }
  }

  // ══════════════════════════════════════════════════════════════
  // Parsing
  // ══════════════════════════════════════════════════════════════

  /**
   * Parseia o payload do webhook e retorna dados padronizados.
   *
   * @param {string} gatewaySlug
   * @param {object} body — Payload JSON parseado
   * @param {object} headers
   * @returns {{ eventType: string, externalId: string, status: string, amount: number, metadata: object, requiresLookup?: boolean }}
   */
  parsePayload(gatewaySlug, body, headers) {
    switch (gatewaySlug) {
      case 'stripe':
        return this._parseStripe(body);
      case 'mercadopago':
        return this._parseMercadoPago(body);
      case 'pagseguro':
        return this._parsePagSeguro(body);
      default:
        return {
          eventType: 'unknown',
          externalId: '',
          status: 'pending',
          amount: 0,
          metadata: {},
        };
    }
  }

  /**
   * Stripe: trata tanto payment_intent.* quanto checkout.session.completed.
   */
  _parseStripe(body) {
    const eventType = body?.type || 'unknown';
    const obj = body?.data?.object || {};

    // Eventos de Checkout Session (gerados quando o cliente paga via link)
    if (eventType.startsWith('checkout.session')) {
      return {
        eventType,
        externalId: obj.payment_intent || obj.id || '',
        status: this._mapStripeCheckoutStatus(obj.payment_status || obj.status),
        amount: (obj.amount_total || 0) / 100,
        metadata: obj.metadata || {},
      };
    }

    // Eventos de PaymentIntent (pagamentos diretos)
    if (eventType.startsWith('payment_intent')) {
      return {
        eventType,
        externalId: obj.id || '',
        status: this._mapStripeStatus(obj.status),
        amount: (obj.amount_received || obj.amount || 0) / 100,
        metadata: obj.metadata || {},
      };
    }

    // Eventos de charge (refund, dispute, etc.)
    if (eventType.startsWith('charge')) {
      const isRefund = eventType.includes('refund');
      return {
        eventType,
        externalId: obj.payment_intent || obj.id || '',
        status: isRefund ? 'refunded' : this._mapStripeStatus(obj.status),
        amount: (obj.amount_refunded || obj.amount || 0) / 100,
        metadata: obj.metadata || {},
      };
    }

    // Fallback genérico
    return {
      eventType,
      externalId: obj.id || '',
      status: this._mapStripeStatus(obj.status),
      amount: (obj.amount_received || obj.amount || 0) / 100,
      metadata: obj.metadata || {},
    };
  }

  _parseMercadoPago(body) {
    return {
      eventType: body?.type || body?.action || 'unknown',
      externalId: String(body?.data?.id || ''),
      status: 'pending', // MP envia apenas o ID; status real precisa de consulta na API
      amount: 0,
      metadata: {},
      requiresLookup: true, // Sinaliza que precisa consultar a API do MP
    };
  }

  _parsePagSeguro(body) {
    const charge = body?.charges?.[0] || body;
    return {
      eventType: body?.type || 'transaction',
      externalId: charge?.id || body?.id || '',
      status: this._mapPagSeguroStatus(charge?.status),
      amount: (charge?.amount?.value || 0) / 100,
      metadata: charge?.metadata || body?.metadata || {},
    };
  }

  // ══════════════════════════════════════════════════════════════
  // Mapeamento de Status
  // ══════════════════════════════════════════════════════════════

  _mapStripeStatus(status) {
    const map = {
      succeeded: 'approved',
      requires_payment_method: 'pending',
      requires_confirmation: 'pending',
      requires_action: 'pending',
      processing: 'pending',
      canceled: 'cancelled',
    };
    return map[status] || 'pending';
  }

  /**
   * Mapeia status de Checkout Session do Stripe.
   * payment_status: 'paid' | 'unpaid' | 'no_payment_required'
   */
  _mapStripeCheckoutStatus(status) {
    const map = {
      paid: 'approved',
      unpaid: 'pending',
      no_payment_required: 'approved',
      complete: 'approved',
      expired: 'cancelled',
      open: 'pending',
    };
    return map[status] || 'pending';
  }

  _mapPagSeguroStatus(status) {
    const map = {
      PAID: 'approved',
      AUTHORIZED: 'approved',
      WAITING: 'pending',
      IN_ANALYSIS: 'pending',
      DECLINED: 'rejected',
      CANCELED: 'cancelled',
    };
    return map[(status || '').toUpperCase()] || 'pending';
  }

  // ══════════════════════════════════════════════════════════════
  // Lookup Mercado Pago (consulta detalhes via API)
  // ══════════════════════════════════════════════════════════════

  /**
   * Consulta os detalhes de um pagamento no Mercado Pago.
   * Necessário porque o webhook do MP envia apenas o ID.
   *
   * @param {string} paymentId — ID do pagamento no MP
   * @param {string} accessToken — Access token do gateway
   * @returns {Promise<{status: string, amount: number, metadata: object, externalReference: string}>}
   */
  async _lookupMercadoPago(paymentId, accessToken) {
    return new Promise((resolve, reject) => {
      const url = `https://api.mercadopago.com/v1/payments/${paymentId}`;
      const options = {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${accessToken}`,
          'Content-Type': 'application/json',
        },
        timeout: 10000,
      };

      const req = https.request(url, options, (res) => {
        let data = '';
        res.on('data', chunk => { data += chunk; });
        res.on('end', () => {
          try {
            const body = JSON.parse(data);
            if (res.statusCode === 200) {
              const mpStatusMap = {
                approved: 'approved',
                pending: 'pending',
                in_process: 'pending',
                rejected: 'rejected',
                cancelled: 'cancelled',
                refunded: 'refunded',
                charged_back: 'refunded',
              };

              resolve({
                status: mpStatusMap[body.status] || 'pending',
                amount: parseFloat(body.transaction_amount) || 0,
                metadata: body.metadata || {},
                externalReference: body.external_reference || '',
                method: body.payment_type_id || '',
              });
            } else {
              reject(new Error(`MP API returned ${res.statusCode}: ${data}`));
            }
          } catch (e) {
            reject(new Error(`Failed to parse MP response: ${e.message}`));
          }
        });
      });

      req.on('error', reject);
      req.on('timeout', () => {
        req.destroy();
        reject(new Error('MP API request timeout'));
      });
      req.end();
    });
  }

  // ══════════════════════════════════════════════════════════════
  // Processamento
  // ══════════════════════════════════════════════════════════════

  /**
   * Processa o webhook completo: valida, parseia, loga e atualiza.
   *
   * @param {string} gatewaySlug
   * @param {string} rawBody
   * @param {object} parsedBody
   * @param {object} headers
   * @returns {Promise<{success: boolean, message: string, transactionId?: number}>}
   */
  async processWebhook(gatewaySlug, rawBody, parsedBody, headers) {
    // 1. Buscar gateway no banco do tenant
    const gateway = await this.models.PaymentGateway.findOne({
      where: { gateway_slug: gatewaySlug, is_active: 1 },
    });

    if (!gateway) {
      return { success: false, message: `Gateway '${gatewaySlug}' not found or inactive.` };
    }

    // 2. Validar assinatura
    const isValid = this.validateSignature(
      gatewaySlug,
      rawBody,
      headers,
      gateway.webhook_secret,
    );

    if (!isValid) {
      console.warn(`[Webhook] ${gatewaySlug}: Signature validation failed.`);
      return { success: false, message: 'Invalid webhook signature.' };
    }

    // 3. Parsear payload
    let parsed = this.parsePayload(gatewaySlug, parsedBody, headers);

    // 4. Se requer lookup (Mercado Pago), buscar detalhes completos via API
    if (parsed.requiresLookup && gatewaySlug === 'mercadopago') {
      try {
        const credentials = gateway.credentials; // getter auto-parseia JSON
        const accessToken = credentials?.access_token || '';

        if (!accessToken) {
          console.error(`[Webhook] mercadopago: No access_token configured — cannot lookup payment.`);
        } else if (parsed.externalId) {
          console.log(`[Webhook] mercadopago: Looking up payment ${parsed.externalId}...`);
          const mpData = await this._lookupMercadoPago(parsed.externalId, accessToken);

          parsed.status = mpData.status;
          parsed.amount = mpData.amount;
          parsed.metadata = mpData.metadata || {};

          // external_reference do MP é o order_id (definido ao criar a preferência)
          if (mpData.externalReference && !parsed.metadata.order_id) {
            parsed.metadata.order_id = mpData.externalReference;
          }

          console.log(`[Webhook] mercadopago: Lookup result — status=${mpData.status}, amount=${mpData.amount}, order_id=${parsed.metadata.order_id || 'N/A'}`);
        }
      } catch (lookupErr) {
        console.error(`[Webhook] mercadopago: Lookup failed — ${lookupErr.message}`);
        // Continuar processamento com dados parciais (pelo menos loga a transação)
      }
    }

    // 5. Logar transação
    const txRecord = await this.models.PaymentGatewayTransaction.create({
      gateway_slug: gatewaySlug,
      external_id: parsed.externalId,
      external_status: parsed.status,
      amount: parsed.amount,
      event_type: parsed.eventType,
      payment_method_type: parsed.metadata?.method || parsed.method || null,
      installment_id: parsed.metadata?.installment_id ? parseInt(parsed.metadata.installment_id) : null,
      order_id: parsed.metadata?.order_id ? parseInt(parsed.metadata.order_id) : null,
      raw_payload: parsedBody,
      processed_at: new Date(),
    });

    console.log(`[Webhook] ${gatewaySlug}: Logged transaction #${txRecord.id} — status=${parsed.status}, event=${parsed.eventType}`);

    // 6. Resolver installment_id — buscar por order_id se não veio nos metadados
    let installmentId = parsed.metadata?.installment_id ? parseInt(parsed.metadata.installment_id) : null;
    const orderId = parsed.metadata?.order_id ? parseInt(parsed.metadata.order_id) : null;

    if (!installmentId && orderId) {
      installmentId = await this._findPendingInstallmentByOrder(orderId, parsed.amount);
    }

    // 7. Se o status é "approved", atualizar a parcela como paga
    if (parsed.status === 'approved' && installmentId) {
      await this._markInstallmentPaid(
        installmentId,
        parsed.amount,
        parsed.externalId,
        gatewaySlug,
      );
    }

    // 8. Se o status é "refunded", reverter o pagamento
    if (parsed.status === 'refunded' && installmentId) {
      await this._markInstallmentRefunded(installmentId);
    }

    // 9. Se temos order_id mas não installment_id, ainda atualizar status do pedido
    if (!installmentId && orderId && (parsed.status === 'approved' || parsed.status === 'refunded')) {
      await this._updateOrderPaymentStatus(orderId);
    }

    return {
      success: true,
      message: `Webhook processed — status: ${parsed.status}`,
      transactionId: txRecord.id,
    };
  }

  // ══════════════════════════════════════════════════════════════
  // Resolução de Parcelas
  // ══════════════════════════════════════════════════════════════

  /**
   * Busca a parcela pendente de um pedido que corresponde ao valor pago.
   * Usado quando o webhook não traz installment_id nos metadados (ex: link pelo pipeline).
   *
   * Prioridade:
   *   1. Parcela pendente com valor exato igual ao pago
   *   2. Primeira parcela pendente do pedido (se valor não bate exatamente)
   *
   * @param {number} orderId
   * @param {number} amount — Valor pago (pode ser 0 se desconhecido)
   * @returns {Promise<number|null>}
   */
  async _findPendingInstallmentByOrder(orderId, amount) {
    try {
      // Tentar encontrar parcela pendente com valor exato
      if (amount > 0) {
        const [exactMatch] = await this.sequelize.query(
          `SELECT id FROM order_installments
           WHERE order_id = :orderId AND status IN ('pendente', 'atrasado')
             AND ABS(amount - :amount) < 0.01
           ORDER BY installment_number ASC
           LIMIT 1`,
          { replacements: { orderId, amount } },
        );
        if (exactMatch?.[0]?.id) {
          console.log(`[Webhook] Found exact-amount installment #${exactMatch[0].id} for order #${orderId}`);
          return exactMatch[0].id;
        }
      }

      // Fallback: primeira parcela pendente
      const [firstPending] = await this.sequelize.query(
        `SELECT id FROM order_installments
         WHERE order_id = :orderId AND status IN ('pendente', 'atrasado')
         ORDER BY installment_number ASC
         LIMIT 1`,
        { replacements: { orderId } },
      );

      if (firstPending?.[0]?.id) {
        console.log(`[Webhook] Found first pending installment #${firstPending[0].id} for order #${orderId}`);
        return firstPending[0].id;
      }

      console.warn(`[Webhook] No pending installments found for order #${orderId}`);
      return null;
    } catch (err) {
      console.error(`[Webhook] Error finding installment for order #${orderId}: ${err.message}`);
      return null;
    }
  }

  // ══════════════════════════════════════════════════════════════
  // Atualização de Parcelas (via raw SQL para manter compatibilidade)
  // ══════════════════════════════════════════════════════════════

  /**
   * Marca uma parcela como paga via gateway.
   */
  async _markInstallmentPaid(installmentId, amount, externalId, gatewaySlug) {
    try {
      const [affectedRows] = await this.sequelize.query(
        `UPDATE order_installments SET
           status = 'pago',
           paid_date = CURDATE(),
           paid_amount = :amount,
           payment_method = :method,
           is_confirmed = 1,
           confirmed_at = NOW(),
           notes = CONCAT(COALESCE(notes, ''), :note),
           updated_at = NOW()
         WHERE id = :id AND status IN ('pendente', 'atrasado')`,
        {
          replacements: {
            amount,
            method: `gateway_${gatewaySlug}`,
            note: `\n[Gateway ${gatewaySlug}] Pago automaticamente via webhook. External ID: ${externalId}`,
            id: installmentId,
          },
        },
      );

      console.log(`[Webhook] Installment #${installmentId} marked as paid (gateway: ${gatewaySlug}, external: ${externalId})`);

      // Atualizar status de pagamento do pedido
      const [rows] = await this.sequelize.query(
        'SELECT order_id FROM order_installments WHERE id = :id',
        { replacements: { id: installmentId } },
      );

      if (rows?.[0]?.order_id) {
        await this._updateOrderPaymentStatus(rows[0].order_id);
      }
    } catch (err) {
      console.error('[WebhookService] Error marking installment paid:', err.message);
    }
  }

  /**
   * Reverte uma parcela para pendente (estorno via gateway).
   */
  async _markInstallmentRefunded(installmentId) {
    try {
      await this.sequelize.query(
        `UPDATE order_installments SET
           status = 'pendente',
           paid_date = NULL,
           paid_amount = NULL,
           payment_method = NULL,
           is_confirmed = 0,
           confirmed_at = NULL,
           notes = CONCAT(COALESCE(notes, ''), '\n[Gateway] Estornado via webhook.'),
           updated_at = NOW()
         WHERE id = :id`,
        { replacements: { id: installmentId } },
      );

      console.log(`[Webhook] Installment #${installmentId} marked as refunded.`);

      const [rows] = await this.sequelize.query(
        'SELECT order_id FROM order_installments WHERE id = :id',
        { replacements: { id: installmentId } },
      );

      if (rows?.[0]?.order_id) {
        await this._updateOrderPaymentStatus(rows[0].order_id);
      }
    } catch (err) {
      console.error('[WebhookService] Error marking installment refunded:', err.message);
    }
  }

  /**
   * Recalcula o payment_status do pedido com base nas parcelas.
   */
  async _updateOrderPaymentStatus(orderId) {
    try {
      const [[stats]] = await this.sequelize.query(
        `SELECT
           COUNT(*) as total,
           SUM(CASE WHEN status = 'pago' THEN 1 ELSE 0 END) as paid
         FROM order_installments
         WHERE order_id = :orderId`,
        { replacements: { orderId } },
      );

      let paymentStatus = 'pendente';
      if (stats.total > 0 && stats.paid >= stats.total) {
        paymentStatus = 'pago';
      } else if (stats.paid > 0) {
        paymentStatus = 'parcial';
      }

      await this.sequelize.query(
        'UPDATE orders SET payment_status = :status, updated_at = NOW() WHERE id = :id',
        { replacements: { status: paymentStatus, id: orderId } },
      );

      console.log(`[Webhook] Order #${orderId} payment_status updated to '${paymentStatus}' (${stats.paid}/${stats.total} paid)`);
    } catch (err) {
      console.error('[WebhookService] Error updating order payment status:', err.message);
    }
  }
}
