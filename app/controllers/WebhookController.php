<?php

namespace Akti\Controllers;

use Akti\Gateways\GatewayManager;
use Akti\Models\Installment;
use Akti\Models\PaymentGateway;
use Akti\Services\CheckoutService;

/**
 * WebhookController — Recebe notificações (webhooks) de gateways de pagamento via PHP.
 *
 * Endpoint público (sem autenticação de sessão). A validação é feita pela
 * assinatura HMAC do gateway (x-signature para MercadoPago, stripe-signature
 * para Stripe, etc.).
 *
 * URL padrão: ?page=webhook&action=handle&gateway=mercadopago
 *
 * Fluxo:
 *   1. Lê o raw body (necessário para validação de assinatura)
 *   2. Resolve o gateway pelo slug
 *   3. Valida assinatura HMAC (se webhook_secret configurado)
 *   4. Parseia payload (gateway faz lookup na API: GET /v1/payments/{data.id})
 *   5. Se status=approved, extrai installment_id do metadata e marca parcela como paga
 *   6. Loga transação no banco
 *   7. Retorna 200 OK para o gateway
 *
 * @package Akti\Controllers
 */
class WebhookController extends BaseController
{
    /**
     * POST ?page=webhook&action=handle&gateway=<slug>
     *
     * Recebe e processa webhook de qualquer gateway de pagamento.
     */
    public function handle(): void
    {
        // Apenas POST
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->json(['success' => false, 'error' => 'Method not allowed.'], 405);
        }

        $gatewaySlug = trim($_GET['gateway'] ?? '');
        if ($gatewaySlug === '') {
            $this->json(['success' => false, 'error' => 'Gateway slug is required.'], 400);
        }

        // Validar slug (apenas alfanumérico e underscore)
        if (!preg_match('/^[a-z0-9_]+$/', $gatewaySlug)) {
            $this->json(['success' => false, 'error' => 'Invalid gateway slug.'], 400);
        }

        // Ler raw body (necessário para validação HMAC)
        $rawBody = file_get_contents('php://input');
        $parsedBody = json_decode($rawBody, true) ?? [];

        $this->logWebhook('info', $gatewaySlug, "Received webhook — body length: " . strlen($rawBody));

        try {
            // 1. Buscar gateway no banco
            $gwModel = new PaymentGateway($this->db);
            $gatewayRow = $gwModel->readBySlug($gatewaySlug);

            if (!$gatewayRow || !$gatewayRow['is_active']) {
                $this->logWebhook('warn', $gatewaySlug, "Gateway not found or inactive.");
                $this->json(['success' => false, 'error' => 'Gateway not found or inactive.'], 400);
            }

            // 2. Resolver instância do gateway (com credenciais para fazer lookup)
            $gateway = GatewayManager::resolveFromRow($gatewayRow);

            // 3. Validar assinatura
            $webhookSecret = $gatewayRow['webhook_secret'] ?? '';
            if ($webhookSecret !== '') {
                $headers = $this->getWebhookHeaders();
                $isValid = $gateway->validateWebhookSignature($rawBody, $headers, $webhookSecret);

                if (!$isValid) {
                    $this->logWebhook('warn', $gatewaySlug, "Signature validation FAILED.");
                    $this->json(['success' => false, 'error' => 'Invalid webhook signature.'], 403);
                }
                $this->logWebhook('info', $gatewaySlug, "Signature validated OK.");
            } else {
                $this->logWebhook('info', $gatewaySlug, "No webhook_secret — skipping signature validation (sandbox).");
            }

            // 4. Parsear payload (o gateway faz lookup na API para obter dados completos)
            $headers = $headers ?? $this->getWebhookHeaders();
            $parsed = $gateway->parseWebhookPayload($rawBody, $headers);

            $this->logWebhook('info', $gatewaySlug, sprintf(
                "Parsed: event=%s, external_id=%s, status=%s, amount=%.2f",
                $parsed['event_type'] ?? 'unknown',
                $parsed['external_id'] ?? '',
                $parsed['status'] ?? 'unknown',
                $parsed['amount'] ?? 0
            ));

            // 5. Extrair IDs — installment_id e order_id vêm direto do parsed (gateway resolve)
            $installmentId = $parsed['installment_id'] ?? null;
            $orderId       = $parsed['order_id'] ?? null;

            // Fallback: buscar em metadata (compatibilidade)
            if (!$installmentId && !empty($parsed['metadata']['installment_id'])) {
                $installmentId = (int) $parsed['metadata']['installment_id'];
            }
            if (!$orderId && !empty($parsed['metadata']['order_id'])) {
                $orderId = (int) $parsed['metadata']['order_id'];
            }

            $this->logWebhook('info', $gatewaySlug, sprintf(
                "Resolved: installment_id=%s, order_id=%s",
                $installmentId ?? 'NULL',
                $orderId ?? 'NULL'
            ));

            // 6. Logar transação
            $txId = $gwModel->logTransaction([
                'gateway_slug'        => $gatewaySlug,
                'installment_id'      => $installmentId,
                'order_id'            => $orderId,
                'external_id'         => $parsed['external_id'] ?? '',
                'external_status'     => $parsed['status'] ?? 'unknown',
                'amount'              => $parsed['amount'] ?? 0,
                'payment_method_type' => $parsed['metadata']['method'] ?? null,
                'raw_payload'         => $parsedBody,
                'event_type'          => $parsed['event_type'] ?? 'unknown',
            ]);

            $this->logWebhook('info', $gatewaySlug, "Transaction logged: #{$txId}");

            // 7. Processar pagamento (se status = approved)
            $status = $parsed['status'] ?? 'unknown';
            if ($status === 'approved' && ($installmentId || $orderId)) {
                $this->processApprovedPayment(
                    $installmentId,
                    $orderId,
                    (float) ($parsed['amount'] ?? 0),
                    $gatewaySlug,
                    $parsed['external_id'] ?? null
                );
            } elseif ($status === 'approved') {
                $this->logWebhook('warn', $gatewaySlug, "Status approved but no installment_id or order_id found — skipping.");
            }

            // 7. Retornar 200 OK (gateways esperam 200 para confirmar recebimento)
            $this->json([
                'success' => true,
                'message' => "Webhook processed — status: " . ($parsed['status'] ?? 'unknown'),
            ]);

        } catch (\Throwable $e) {
            $this->logWebhook('error', $gatewaySlug, "EXCEPTION: " . $e->getMessage());

            // Retornar 200 mesmo em erro para evitar retry loop dos gateways
            $this->json([
                'success' => false,
                'message' => 'Webhook received but processing failed.',
            ]);
        }
    }

    /**
     * Marca parcela como paga quando o gateway confirma o pagamento (status=approved).
     *
     * Prioridade:
     *   1. Se tem installment_id → atualiza diretamente em order_installments WHERE id = installment_id
     *   2. Se só tem order_id → busca primeira parcela pendente do pedido e marca como paga
     */
    private function processApprovedPayment(
        ?int $installmentId,
        ?int $orderId,
        float $amount,
        string $gatewaySlug,
        ?string $externalId
    ): void {
        try {
            $installmentModel = new Installment($this->db);

            // ── Caminho 1: installment_id disponível → update direto ──
            if ($installmentId) {
                $installment = $installmentModel->getById($installmentId);

                if (!$installment) {
                    $this->logWebhook('warn', $gatewaySlug, "Installment #{$installmentId} not found in DB.");
                    return;
                }

                // Já pago? Evitar duplicidade
                if ($installment['status'] === 'pago') {
                    $this->logWebhook('info', $gatewaySlug, "Installment #{$installmentId} already paid — skipping.");
                    return;
                }

                $payAmount = $amount > 0 ? $amount : (float) $installment['amount'];

                $installmentModel->pay($installmentId, [
                    'paid_date'      => date('Y-m-d'),
                    'paid_amount'    => $payAmount,
                    'payment_method' => $gatewaySlug,
                    'notes'          => 'Pago via webhook ' . $gatewaySlug . ($externalId ? " (ID: {$externalId})" : ''),
                ], true); // autoConfirm = true

                $installmentOrderId = (int) $installment['order_id'];
                $installmentModel->updateOrderPaymentStatus($installmentOrderId);

                $this->logWebhook('info', $gatewaySlug, sprintf(
                    "Installment #%d marked as PAID (R$ %.2f) — order #%d",
                    $installmentId,
                    $payAmount,
                    $installmentOrderId
                ));
                return;
            }

            // ── Caminho 2: só order_id → delegar ao CheckoutService ──
            if ($orderId) {
                $checkoutService = new CheckoutService($this->db);
                $checkoutService->markInstallmentPaidFromCheckout(
                    $orderId,
                    null,
                    $amount,
                    $gatewaySlug,
                    $externalId
                );
                $this->logWebhook('info', $gatewaySlug, "Payment confirmed for order #{$orderId} (first pending installment).");
            }
        } catch (\Throwable $e) {
            $this->logWebhook('error', $gatewaySlug, "Failed to process payment: " . $e->getMessage());
        }
    }

    /**
     * Extrai headers HTTP relevantes para webhook de forma case-insensitive.
     */
    private function getWebhookHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headerName = str_replace('_', '-', strtolower(substr($key, 5)));
                $headers[$headerName] = $value;
            }
        }
        return $headers;
    }

    /**
     * Log estruturado para webhooks.
     */
    private function logWebhook(string $level, string $gateway, string $message): void
    {
        $logMessage = sprintf(
            '[%s][Webhook][%s][%s] %s',
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $gateway,
            $message
        );

        error_log($logMessage);

        $logDir = __DIR__ . '/../../storage/logs';
        if (is_dir($logDir) && is_writable($logDir)) {
            $logFile = $logDir . '/webhook_' . date('Y-m-d') . '.log';
            file_put_contents($logFile, $logMessage . "\n", FILE_APPEND | LOCK_EX);
        }
    }
}
