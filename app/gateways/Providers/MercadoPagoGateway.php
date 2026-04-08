<?php
namespace Akti\Gateways\Providers;

use Akti\Gateways\AbstractGateway;

/**
 * MercadoPagoGateway — Integração com a API do Mercado Pago.
 *
 * Suporta: PIX, Cartão de Crédito, Cartão de Débito, Boleto.
 * API Docs: https://www.mercadopago.com.br/developers/pt/reference
 *
 * Credenciais necessárias:
 *   - access_token (Token de acesso do Mercado Pago)
 *   - public_key   (Chave pública para checkout frontend)
 *
 * @package Akti\Gateways\Providers
 */
class MercadoPagoGateway extends AbstractGateway
{
    private const SANDBOX_URL    = 'https://api.mercadopago.com';
    private const PRODUCTION_URL = 'https://api.mercadopago.com';

    // ══════════════════════════════════════════════════════════════
    // Identificação
    // ══════════════════════════════════════════════════════════════

    public function getSlug(): string
    {
        return 'mercadopago';
    }

    public function getDisplayName(): string
    {
        return 'Mercado Pago';
    }

    // ══════════════════════════════════════════════════════════════
    // Capabilities
    // ══════════════════════════════════════════════════════════════

    public function supports(string $method): bool
    {
        return in_array($method, $this->getSupportedMethods());
    }

    public function getSupportedMethods(): array
    {
        return ['auto', 'pix', 'credit_card', 'debit_card', 'boleto'];
    }

    // ══════════════════════════════════════════════════════════════
    // Campos de Configuração
    // ══════════════════════════════════════════════════════════════

    public function getCredentialFields(): array
    {
        return [
            ['key' => 'access_token', 'label' => 'Access Token', 'type' => 'password', 'required' => true],
            ['key' => 'public_key',   'label' => 'Public Key',   'type' => 'text',     'required' => true],
        ];
    }

    public function getSettingsFields(): array
    {
        return [
            ['key' => 'pix_expiration_minutes', 'label' => 'Expiração PIX (minutos)', 'type' => 'number', 'default' => 30],
            ['key' => 'boleto_days_due',        'label' => 'Dias para vencimento do boleto', 'type' => 'number', 'default' => 3],
            ['key' => 'statement_descriptor',   'label' => 'Descrição na fatura',      'type' => 'text',   'default' => 'AKTI'],
            ['key' => 'notification_url',       'label' => 'URL de Webhook (automática)', 'type' => 'readonly', 'default' => ''],
        ];
    }

    // ══════════════════════════════════════════════════════════════
    // Operações
    // ══════════════════════════════════════════════════════════════

    public function createCharge(array $data): array
    {
        $method  = $data['method'] ?? 'pix';

        // Checkout transparente: PIX, boleto e cartão com token usam /v1/payments (direto)
        // Apenas 'auto' ou fluxo redirect usam /checkout/preferences
        if (in_array($method, ['pix', 'boleto', 'credit_card', 'debit_card'], true)) {
            return $this->createDirectPayment($data, $method);
        }

        return $this->createPreferenceLink($data, $method);
    }

    /**
     * Cria um pagamento direto via /v1/payments (quando card_token está disponível).
     */
    private function createDirectPayment(array $data, string $method): array
    {
        $payload = $this->buildChargePayload($data, $method);

        $this->log('info', 'Creating direct payment', ['method' => $method, 'amount' => $data['amount']]);

        $response = $this->httpRequest('POST', $this->getBaseUrl() . '/v1/payments', [
            'Authorization: Bearer ' . $this->getCredential('access_token'),
            'X-Idempotency-Key: ' . ($data['idempotency_key'] ?? uniqid('mp_', true)),
        ], $payload);

        if ($response['status'] === 201 || $response['status'] === 200) {
            $body = $response['decoded'];
            return $this->successResponse([
                'external_id'    => (string) ($body['id'] ?? ''),
                'status'         => $this->mapStatus($body['status'] ?? 'pending'),
                'payment_url'    => $body['point_of_interaction']['transaction_data']['ticket_url'] ?? null,
                'qr_code'        => $body['point_of_interaction']['transaction_data']['qr_code'] ?? null,
                'qr_code_base64' => $body['point_of_interaction']['transaction_data']['qr_code_base64'] ?? null,
                'boleto_url'     => $body['transaction_details']['external_resource_url'] ?? null,
                'boleto_barcode' => $body['barcode']['content'] ?? null,
                'expires_at'     => $body['date_of_expiration'] ?? null,
                'raw'            => $body,
            ]);
        }

        $this->log('error', 'Direct payment creation failed', ['status' => $response['status'], 'body' => $response['body']]);
        return $this->errorResponse(
            'Erro ao criar cobrança no Mercado Pago: ' . ($response['decoded']['message'] ?? $response['body']),
            ['raw' => $response['decoded'] ?? $response['body']]
        );
    }

    /**
     * Cria uma Preferência de Checkout via /checkout/preferences.
     * Retorna init_point (URL de pagamento que o cliente pode acessar).
     * Suporta todos os métodos: pix, boleto, credit_card, debit_card.
     */
    private function createPreferenceLink(array $data, string $method): array
    {
        $this->log('info', 'Creating checkout preference', ['method' => $method, 'amount' => $data['amount']]);

        $orderNum = str_pad((string)($data['order_id'] ?? '0'), 4, '0', STR_PAD_LEFT);

        $payload = [
            'items' => [
                [
                    'title'       => $data['description'] ?? ('Pedido #' . $orderNum),
                    'quantity'    => 1,
                    'unit_price'  => (float) $data['amount'],
                    'currency_id' => 'BRL',
                ],
            ],
            'external_reference' => (string) ($data['order_id'] ?? ''),
            'statement_descriptor' => $this->getSetting('statement_descriptor', 'AKTI'),
            'metadata' => array_merge($data['metadata'] ?? [], [
                'installment_id' => $data['installment_id'] ?? null,
                'order_id'       => $data['order_id'] ?? null,
                'source'         => 'akti',
            ]),
        ];

        // Dados do pagador
        if (!empty($data['customer'])) {
            $payload['payer'] = [
                'email'      => $data['customer']['email'] ?? 'cliente@akti.com',
                'name'       => $data['customer']['name'] ?? 'Cliente',
            ];
            $doc = preg_replace('/\D/', '', $data['customer']['document'] ?? '');
            if ($doc !== '') {
                $payload['payer']['identification'] = [
                    'type'   => 'CPF',
                    'number' => $doc,
                ];
            }
        }

        // Restringir métodos de pagamento conforme selecionado pelo usuário
        $excludedTypes = [];
        switch ($method) {
            case 'pix':
                // Excluir tudo exceto PIX (bank_transfer cobre pix no MP)
                $excludedTypes = ['credit_card', 'debit_card', 'ticket', 'atm', 'prepaid_card'];
                break;
            case 'boleto':
                // Excluir tudo exceto boleto (ticket cobre boleto no MP)
                $excludedTypes = ['credit_card', 'debit_card', 'bank_transfer', 'atm', 'prepaid_card'];
                break;
            case 'credit_card':
                $excludedTypes = ['debit_card', 'bank_transfer', 'ticket', 'atm', 'prepaid_card'];
                break;
            case 'debit_card':
                $excludedTypes = ['credit_card', 'bank_transfer', 'ticket', 'atm', 'prepaid_card'];
                break;
        }
        if (!empty($excludedTypes)) {
            $payload['payment_methods'] = [
                'excluded_payment_types' => array_map(function($t) { return ['id' => $t]; }, $excludedTypes),
            ];
        }

        // PIX expiração
        if ($method === 'pix') {
            $expMinutes = (int) $this->getSetting('pix_expiration_minutes', 30);
            $payload['expiration_date_from'] = $this->formatDateForMp('now');
            $payload['expiration_date_to']   = $this->formatDateForMp("+{$expMinutes} minutes");
        }

        // Boleto expiração
        if ($method === 'boleto') {
            $daysDue = (int) $this->getSetting('boleto_days_due', 3);
            $payload['expiration_date_from'] = $this->formatDateForMp('now');
            $payload['expiration_date_to']   = $this->formatDateForMp("+{$daysDue} days");
        }

        // Webhook URL — só incluir se for uma URL HTTPS válida (requisito do Mercado Pago)
        $notificationUrl = trim((string) $this->getSetting('notification_url', ''));
        if (
            $notificationUrl !== ''
            && filter_var($notificationUrl, FILTER_VALIDATE_URL)
            && preg_match('#^https://#i', $notificationUrl)
        ) {
            $payload['notification_url'] = $notificationUrl;
        } else {
            // Logar aviso — sem notification_url, o MP não enviará webhooks
            if (!empty($notificationUrl)) {
                $this->log('warning', 'notification_url ignorada — Mercado Pago exige HTTPS', [
                    'url' => $notificationUrl,
                ]);
            }
        }

        $response = $this->httpRequest('POST', $this->getBaseUrl() . '/checkout/preferences', [
            'Authorization: Bearer ' . $this->getCredential('access_token'),
        ], $payload);

        if ($response['status'] === 201 || $response['status'] === 200) {
            $body = $response['decoded'];

            // init_point = produção, sandbox_init_point = sandbox
            $paymentUrl = $this->isSandbox()
                ? ($body['sandbox_init_point'] ?? $body['init_point'] ?? '')
                : ($body['init_point'] ?? '');

            return $this->successResponse([
                'external_id'    => (string) ($body['id'] ?? ''),
                'status'         => 'pending',
                'payment_url'    => $paymentUrl,
                'qr_code'        => null,
                'qr_code_base64' => null,
                'boleto_url'     => null,
                'expires_at'     => $body['expiration_date_to'] ?? null,
                'raw'            => $body,
            ]);
        }

        $this->log('error', 'Preference creation failed', ['status' => $response['status'], 'body' => $response['body']]);
        return $this->errorResponse(
            'Erro ao criar link de pagamento no Mercado Pago: ' . ($response['decoded']['message'] ?? $response['body']),
            ['raw' => $response['decoded'] ?? $response['body']]
        );
    }

    public function getChargeStatus(string $externalId): array
    {
        $response = $this->httpRequest('GET', $this->getBaseUrl() . '/v1/payments/' . $externalId, [
            'Authorization: Bearer ' . $this->getCredential('access_token'),
        ]);

        if ($response['status'] === 200) {
            $body = $response['decoded'];
            return $this->successResponse([
                'external_id' => (string) $body['id'],
                'status'      => $this->mapStatus($body['status'] ?? 'unknown'),
                'paid_amount' => (float) ($body['transaction_amount_refunded'] ?? $body['transaction_amount'] ?? 0),
                'paid_at'     => $body['date_approved'] ?? null,
                'raw'         => $body,
            ]);
        }

        return $this->errorResponse('Erro ao consultar cobrança', ['raw' => $response['decoded']]);
    }

    public function refund(string $externalId, ?float $amount = null): array
    {
        $body = $amount !== null ? ['amount' => $amount] : [];

        $response = $this->httpRequest('POST', $this->getBaseUrl() . "/v1/payments/{$externalId}/refunds", [
            'Authorization: Bearer ' . $this->getCredential('access_token'),
        ], $body ?: null);

        if ($response['status'] === 201 || $response['status'] === 200) {
            $data = $response['decoded'];
            return $this->successResponse([
                'refund_id' => (string) ($data['id'] ?? ''),
                'status'    => 'refunded',
                'raw'       => $data,
            ]);
        }

        return $this->errorResponse('Erro ao estornar cobrança', ['raw' => $response['decoded']]);
    }

    // ══════════════════════════════════════════════════════════════
    // Webhooks
    // ══════════════════════════════════════════════════════════════

    public function validateWebhookSignature(string $payload, array $headers, string $secret): bool
    {
        // Mercado Pago usa x-signature com ts e v1
        $xSignature = $headers['x-signature'] ?? $headers['X-Signature'] ?? '';
        $xRequestId = $headers['x-request-id'] ?? $headers['X-Request-Id'] ?? '';

        if (empty($xSignature) || empty($secret)) {
            return false;
        }

        // Extrair ts e v1 do x-signature
        $parts = [];
        foreach (explode(',', $xSignature) as $part) {
            $kv = explode('=', trim($part), 2);
            if (count($kv) === 2) {
                $parts[$kv[0]] = $kv[1];
            }
        }

        $ts = $parts['ts'] ?? '';
        $v1 = $parts['v1'] ?? '';

        if (empty($ts) || empty($v1)) {
            return false;
        }

        // Extrair data.id do payload
        $decoded = json_decode($payload, true);
        $dataId = $decoded['data']['id'] ?? '';

        // Recalcular assinatura
        $manifest = "id:{$dataId};request-id:{$xRequestId};ts:{$ts};";
        $hash = hash_hmac('sha256', $manifest, $secret);

        return hash_equals($hash, $v1);
    }

    public function parseWebhookPayload(string $payload, array $headers): array
    {
        $data = json_decode($payload, true) ?? [];

        $eventType = $data['type'] ?? $data['action'] ?? 'unknown';
        $paymentId = (string) ($data['data']['id'] ?? '');

        // Para obter os dados completos, precisamos consultar a API
        // O webhook do MP envia apenas o ID, não os dados completos
        $paymentData = [];
        if ($paymentId && $this->getCredential('access_token')) {
            $result = $this->getChargeStatus($paymentId);
            if ($result['success']) {
                $paymentData = $result['raw'] ?? [];
            }
        }

        return [
            'event_type'  => $eventType,
            'external_id' => $paymentId,
            'status'      => $this->mapStatus($paymentData['status'] ?? 'unknown'),
            'amount'      => (float) ($paymentData['transaction_amount'] ?? 0),
            'paid_amount' => (float) ($paymentData['transaction_amount_refunded'] ?? $paymentData['transaction_amount'] ?? 0),
            'metadata'    => $paymentData['metadata'] ?? [],
            'raw'         => $data,
        ];
    }

    // ══════════════════════════════════════════════════════════════
    // Testes
    // ══════════════════════════════════════════════════════════════

    public function testConnection(): array
    {
        $accessToken = $this->getCredential('access_token');

        if (empty($accessToken)) {
            return ['success' => false, 'message' => 'Access Token não informado.'];
        }

        // Usar /users/me para validar o access_token (mais confiável que /payment_methods)
        $response = $this->httpRequest('GET', $this->getBaseUrl() . '/users/me', [
            'Authorization: Bearer ' . $accessToken,
        ]);

        if ($response['status'] === 200 && !empty($response['decoded']['id'])) {
            $userId = $response['decoded']['id'] ?? '';
            $nickname = $response['decoded']['nickname'] ?? '';
            $siteId = $response['decoded']['site_id'] ?? '';
            return [
                'success' => true,
                'message' => "Conexão com Mercado Pago estabelecida com sucesso! Conta: {$nickname} (ID: {$userId}, Site: {$siteId})",
            ];
        }

        // Se /users/me falhou, tentar /v1/payment_methods como fallback
        $response2 = $this->httpRequest('GET', $this->getBaseUrl() . '/v1/payment_methods', [
            'Authorization: Bearer ' . $accessToken,
        ]);

        if ($response2['status'] === 200) {
            return ['success' => true, 'message' => 'Conexão com Mercado Pago estabelecida com sucesso.'];
        }

        $errorMsg = $response['decoded']['message'] ?? $response2['decoded']['message'] ?? $response['body'] ?? 'Erro desconhecido';
        return ['success' => false, 'message' => 'Falha na conexão: ' . $errorMsg];
    }

    // ══════════════════════════════════════════════════════════════
    // Internals
    // ══════════════════════════════════════════════════════════════

    private function getBaseUrl(): string
    {
        // MP não diferencia sandbox/production pela URL, e sim pelo access_token
        return self::PRODUCTION_URL;
    }

    /**
     * Formata uma data no formato exigido pelo Mercado Pago.
     * Formato: yyyy-MM-dd'T'HH:mm:ss.sss+HH:mm (ISO 8601 com milissegundos).
     *
     * @param string $relativeTime Ex: "+30 minutes", "+3 days"
     * @return string Ex: "2026-03-18T15:30:00.000-03:00"
     */
    private function formatDateForMp(string $relativeTime): string
    {
        $dt = new \DateTime($relativeTime);
        // Formato ISO 8601 com milissegundos: yyyy-MM-dd'T'HH:mm:ss.sssP
        return $dt->format('Y-m-d\TH:i:s.vP');
    }

    protected function mapStatus(string $gatewayStatus): string
    {
        $map = [
            'approved'     => 'approved',
            'pending'      => 'pending',
            'authorized'   => 'pending',
            'in_process'   => 'pending',
            'in_mediation' => 'pending',
            'rejected'     => 'rejected',
            'cancelled'    => 'cancelled',
            'refunded'     => 'refunded',
            'charged_back' => 'refunded',
        ];

        return $map[$gatewayStatus] ?? 'pending';
    }

    private function buildChargePayload(array $data, string $method): array
    {
        $payload = [
            'transaction_amount' => (float) $data['amount'],
            'description'        => $data['description'] ?? 'Pagamento Akti',
            'statement_descriptor' => $this->getSetting('statement_descriptor', 'AKTI'),
            'metadata' => array_merge($data['metadata'] ?? [], [
                'installment_id' => $data['installment_id'] ?? null,
                'order_id'       => $data['order_id'] ?? null,
                'source'         => 'akti',
            ]),
        ];

        // Dados do pagador — MP exige first_name + last_name para boleto
        if (!empty($data['customer'])) {
            $fullName = trim($data['customer']['name'] ?? '');
            if ($fullName === '') {
                $fullName = 'Cliente';
            }
            $nameParts = preg_split('/\s+/', $fullName, 2);
            $firstName = $nameParts[0] ?: 'Cliente';
            $lastName  = $nameParts[1] ?? $firstName; // MP exige last_name; duplicar se só houver um nome

            $doc = preg_replace('/\D/', '', $data['customer']['document'] ?? '');
            $docType = strlen($doc) > 11 ? 'CNPJ' : 'CPF';

            $payload['payer'] = [
                'email'      => $data['customer']['email'] ?? 'cliente@akti.com',
                'first_name' => $firstName,
                'last_name'  => $lastName,
            ];
            if ($doc !== '') {
                $payload['payer']['identification'] = [
                    'type'   => $docType,
                    'number' => $doc,
                ];
            }
        }

        // Webhook URL — só incluir se for uma URL válida com protocolo https (MP rejeita qualquer valor inválido)
        $notificationUrl = trim((string) $this->getSetting('notification_url', ''));
        // Fallback: usar notification_url enviada pelo caller (CheckoutService)
        if (empty($notificationUrl) && !empty($data['notification_url'])) {
            $notificationUrl = trim((string) $data['notification_url']);
        }
        if (
            $notificationUrl !== ''
            && filter_var($notificationUrl, FILTER_VALIDATE_URL)
            && preg_match('#^https://#i', $notificationUrl)
        ) {
            $payload['notification_url'] = $notificationUrl;
        }

        switch ($method) {
            case 'pix':
                $payload['payment_method_id'] = 'pix';
                $expMinutes = (int) $this->getSetting('pix_expiration_minutes', 30);
                $payload['date_of_expiration'] = $this->formatDateForMp("+{$expMinutes} minutes");
                break;

            case 'boleto':
                $payload['payment_method_id'] = 'bolbradesco';
                $daysDue = (int) $this->getSetting('boleto_days_due', 3);
                $payload['date_of_expiration'] = $this->formatDateForMp("+{$daysDue} days");
                break;

            case 'credit_card':
                if (!empty($data['card_payment_method'])) {
                    $payload['payment_method_id'] = $data['card_payment_method'];
                }
                $payload['token'] = $data['card_token'] ?? null;
                $payload['installments'] = (int) ($data['card_installments'] ?? 1);
                break;

            case 'debit_card':
                if (!empty($data['card_payment_method'])) {
                    $payload['payment_method_id'] = $data['card_payment_method'];
                }
                $payload['token'] = $data['card_token'] ?? null;
                $payload['installments'] = 1; // Débito é sempre à vista
                break;
        }

        return $payload;
    }
}
