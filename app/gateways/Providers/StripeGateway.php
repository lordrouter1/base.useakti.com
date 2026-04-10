<?php
namespace Akti\Gateways\Providers;

use Akti\Gateways\AbstractGateway;

/**
 * StripeGateway — Integração com a API do Stripe.
 *
 * Suporta: Cartão de Crédito, Boleto (via Stripe Brasil), PIX (via Stripe Brasil).
 * API Docs: https://stripe.com/docs/api
 *
 * Credenciais necessárias:
 *   - secret_key      (Chave secreta do Stripe)
 *   - publishable_key (Chave pública para o frontend)
 *
 * @package Akti\Gateways\Providers
 */
class StripeGateway extends AbstractGateway
{
    private const API_URL = 'https://api.stripe.com';

    // ══════════════════════════════════════════════════════════════
    // Identificação
    // ══════════════════════════════════════════════════════════════

    /**
     * {@inheritDoc}
     */
    public function getSlug(): string
    {
        return 'stripe';
    }

    /**
     * {@inheritDoc}
     */
    public function getDisplayName(): string
    {
        return 'Stripe';
    }

    // ══════════════════════════════════════════════════════════════
    // Capabilities
    // ══════════════════════════════════════════════════════════════

    /**
     * {@inheritDoc}
     */
    public function supports(string $method): bool
    {
        return in_array($method, $this->getSupportedMethods());
    }

    /**
     * {@inheritDoc}
     */
    public function getSupportedMethods(): array
    {
        return ['auto', 'credit_card', 'debit_card', 'boleto'];
    }

    // ══════════════════════════════════════════════════════════════
    // Campos de Configuração
    // ══════════════════════════════════════════════════════════════

    /**
     * {@inheritDoc}
     */
    public function getCredentialFields(): array
    {
        return [
            ['key' => 'secret_key',      'label' => 'Secret Key (sk_...)',      'type' => 'password', 'required' => true],
            ['key' => 'publishable_key', 'label' => 'Publishable Key (pk_...)', 'type' => 'text',     'required' => true],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getSettingsFields(): array
    {
        return [
            ['key' => 'pix_expiration_minutes', 'label' => 'Expiração PIX (minutos)', 'type' => 'number', 'default' => 30],
            ['key' => 'boleto_days_due',        'label' => 'Dias para vencimento do boleto', 'type' => 'number', 'default' => 3],
            ['key' => 'currency',               'label' => 'Moeda',                    'type' => 'text',   'default' => 'brl'],
            ['key' => 'webhook_endpoint_secret', 'label' => 'Webhook Endpoint Secret (whsec_...)', 'type' => 'password', 'required' => false],
        ];
    }

    // ══════════════════════════════════════════════════════════════
    // Operações
    // ══════════════════════════════════════════════════════════════

    /**
     * {@inheritDoc}
     *
     * Roteamento interno:
     * - PIX, boleto ou cartão com token → PaymentIntent direto (/v1/payment_intents).
     * - 'auto' ou cartão sem token → Checkout Session (redirect hosted pelo Stripe).
     */
    public function createCharge(array $data): array
    {
        $method = $data['method'] ?? 'credit_card';

        $hasToken = !empty($data['card_token']) || !empty($data['payment_method_id']);

        if ($hasToken || in_array($method, ['pix', 'boleto'], true)) {
            return $this->createPaymentIntent($data, $method);
        }

        return $this->createCheckoutSession($data, $method);
    }

    /**
     * Cria um PaymentIntent diretamente.
     *
     * Cartão: anexa payment_method e confirma.
     * PIX/Boleto: usa payment_method_data para criar inline e confirma.
     *
     * @param array  $data   Dados da cobrança (mesmo formato de createCharge).
     * @param string $method Método de pagamento selecionado.
     *
     * @return array Resposta padronizada com 'success', 'external_id', 'status', etc.
     */
    private function createPaymentIntent(array $data, string $method): array
    {
        $this->log('info', 'Creating PaymentIntent', ['method' => $method, 'amount' => $data['amount']]);

        $amountCents = (int) round($data['amount'] * 100);
        $currency = $this->getSetting('currency', 'brl');
        $returnUrl = $data['return_url'] ?? '';

        // Nome e e-mail do cliente
        $customerName  = trim($data['customer']['name'] ?? '') ?: 'Cliente';
        $customerEmail = trim($data['customer']['email'] ?? '') ?: 'noreply@akti.com.br';

        // Payload base com arrays nativos PHP (http_build_query resolve a notação bracket)
        $payload = [
            'amount'      => $amountCents,
            'currency'    => $currency,
            'description' => $data['description'] ?? 'Pagamento Akti',
            'confirm'     => 'true',
            'metadata'    => [
                'installment_id' => $data['installment_id'] ?? '',
                'order_id'       => $data['order_id'] ?? '',
                'source'         => 'akti',
            ],
        ];

        $piMethod = ($method === 'auto') ? 'card' : $method;

        // return_url obrigatória para métodos assíncronos (boleto) com confirm=true
        if ($piMethod === 'boleto') {
            if (!$returnUrl) {
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $returnUrl = $protocol . '://' . $host . '/';
            }
            $payload['return_url'] = $returnUrl;
        } elseif ($returnUrl) {
            $payload['return_url'] = $returnUrl;
        }

        switch ($piMethod) {
            case 'boleto':
                $doc = preg_replace('/\D/', '', $data['customer']['document'] ?? '');
                if (!$doc) {
                    $doc = '00000000000';
                }

                // Endereço do cliente (obrigatório para boleto no Stripe)
                $street = trim($data['customer']['street'] ?? '');
                $number = trim($data['customer']['number'] ?? '');
                $neighborhood = trim($data['customer']['neighborhood'] ?? '');
                $line1 = $street;
                if ($number) {
                    $line1 .= ', ' . $number;
                }
                if (!$line1) {
                    $line1 = 'Não informado';
                }

                $payload['payment_method_types'] = ['boleto'];
                $payload['payment_method_data'] = [
                    'type' => 'boleto',
                    'billing_details' => [
                        'name'  => $customerName,
                        'email' => $customerEmail,
                        'address' => [
                            'line1'       => $line1,
                            'line2'       => $neighborhood ?: null,
                            'city'        => trim($data['customer']['city'] ?? '') ?: 'Não informado',
                            'state'       => trim($data['customer']['state'] ?? '') ?: 'SP',
                            'postal_code' => preg_replace('/\D/', '', $data['customer']['zip'] ?? '') ?: '00000000',
                            'country'     => 'BR',
                        ],
                    ],
                    'boleto' => [
                        'tax_id' => $doc,
                    ],
                ];
                $daysDue = (int) $this->getSetting('boleto_days_due', 3);
                $payload['payment_method_options'] = [
                    'boleto' => [
                        'expires_after_days' => $daysDue,
                    ],
                ];
                break;

            case 'credit_card':
            case 'debit_card':
            default:
                $payload['payment_method_types'] = ['card'];
                if (!empty($data['card_token'])) {
                    $payload['payment_method'] = $data['card_token'];
                }
                break;
        }

        if (!empty($data['customer']['email'])) {
            $payload['receipt_email'] = $data['customer']['email'];
        }

        $this->log('debug', 'PaymentIntent payload', ['payload' => $payload]);

        $response = $this->stripeRequest('POST', '/v1/payment_intents', $payload);

        if ($response['status'] === 200) {
            $body = $response['decoded'];

            // Normalizar expires_at para ISO 8601
            $expiresAt = null;
            if (isset($body['next_action']['pix_display_qr_code']['expires_at'])) {
                $expiresAt = date('c', $body['next_action']['pix_display_qr_code']['expires_at']);
            } elseif (isset($body['next_action']['boleto_display_details']['expires_at'])) {
                $expiresAt = date('c', $body['next_action']['boleto_display_details']['expires_at']);
            }

            return $this->successResponse([
                'external_id'      => $body['id'] ?? '',
                'status'           => $this->mapStatus($body['status'] ?? 'requires_payment_method'),
                'payment_url'      => $body['next_action']['redirect_to_url']['url'] ?? null,
                'qr_code'          => $body['next_action']['pix_display_qr_code']['data'] ?? null,
                'qr_code_base64'   => null,
                'qr_code_image_url' => $body['next_action']['pix_display_qr_code']['image_url_png'] ?? null,
                'boleto_url'       => $body['next_action']['boleto_display_details']['hosted_voucher_url'] ?? null,
                'boleto_barcode'   => $body['next_action']['boleto_display_details']['number'] ?? null,
                'expires_at'       => $expiresAt,
                'client_secret'    => $body['client_secret'] ?? null,
                'raw'              => $body,
            ]);
        }

        $this->log('error', 'PaymentIntent creation failed', ['status' => $response['status'], 'body' => $response['body']]);
        return $this->errorResponse(
            'Erro ao criar cobrança no Stripe: ' . ($response['decoded']['error']['message'] ?? $response['body']),
            ['raw' => $response['decoded'] ?? $response['body']]
        );
    }

    /**
     * Cria uma Checkout Session que gera uma URL de pagamento hosted pelo Stripe.
     *
     * Ideal para gerar links que o cliente pode acessar diretamente.
     * Quando method='auto', o Stripe mostra todos os métodos habilitados na conta.
     *
     * @param array  $data   Dados da cobrança (mesmo formato de createCharge).
     * @param string $method Método de pagamento selecionado.
     *
     * @return array Resposta padronizada com 'success', 'external_id', 'status', 'payment_url', etc.
     */
    private function createCheckoutSession(array $data, string $method): array
    {
        $this->log('info', 'Creating Checkout Session', ['method' => $method, 'amount' => $data['amount']]);

        $amountCents = (int) round($data['amount'] * 100);
        $currency = $this->getSetting('currency', 'brl');
        $orderNum = str_pad((string)($data['order_id'] ?? '0'), 4, '0', STR_PAD_LEFT);

        // URL base do sistema para redirect após pagamento
        $baseUrl = rtrim(($_SERVER['REQUEST_SCHEME'] ?? 'https') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'), '/');
        $successUrl = $baseUrl . '/?page=pipeline&action=detail&id=' . ($data['order_id'] ?? '') . '&payment=success';
        $cancelUrl  = $baseUrl . '/?page=pipeline&action=detail&id=' . ($data['order_id'] ?? '') . '&payment=cancelled';

        $payload = [
            'mode'                          => 'payment',
            'line_items[0][price_data][currency]'    => $currency,
            'line_items[0][price_data][unit_amount]'  => $amountCents,
            'line_items[0][price_data][product_data][name]' => $data['description'] ?? ('Pedido #' . $orderNum),
            'line_items[0][quantity]'        => 1,
            'success_url'                   => $successUrl,
            'cancel_url'                    => $cancelUrl,
            'metadata[installment_id]'      => $data['installment_id'] ?? '',
            'metadata[order_id]'            => $data['order_id'] ?? '',
            'metadata[source]'              => 'akti',
        ];

        // Definir payment_method_types conforme o método selecionado.
        // Quando method='auto', NÃO definir payment_method_types — o Stripe
        // mostra automaticamente todos os métodos habilitados na conta, e o
        // cliente escolhe a forma de pagamento dentro do próprio checkout.
        if ($method !== 'auto') {
            switch ($method) {
                case 'pix':
                    $payload['payment_method_types[0]'] = 'pix';
                    break;
                case 'boleto':
                    $payload['payment_method_types[0]'] = 'boleto';
                    $daysDue = (int) $this->getSetting('boleto_days_due', 3);
                    $payload['payment_method_options[boleto][expires_after_days]'] = $daysDue;
                    break;
                case 'debit_card':
                case 'credit_card':
                default:
                    $payload['payment_method_types[0]'] = 'card';
                    break;
            }
        }

        // Dados do cliente (preencher email se disponível)
        if (!empty($data['customer']['email'])) {
            $payload['customer_email'] = $data['customer']['email'];
        }

        $response = $this->stripeRequest('POST', '/v1/checkout/sessions', $payload);

        if ($response['status'] === 200) {
            $body = $response['decoded'];
            return $this->successResponse([
                'external_id'    => $body['id'] ?? '',
                'status'         => $this->mapStatus($body['payment_status'] ?? 'unpaid'),
                'payment_url'    => $body['url'] ?? null,
                'qr_code'        => null,
                'qr_code_base64' => null,
                'boleto_url'     => null,
                'expires_at'     => isset($body['expires_at']) ? date('c', $body['expires_at']) : null,
                'client_secret'  => null,
                'raw'            => $body,
            ]);
        }

        $this->log('error', 'Checkout Session creation failed', ['status' => $response['status'], 'body' => $response['body']]);
        return $this->errorResponse(
            'Erro ao criar link de pagamento no Stripe: ' . ($response['decoded']['error']['message'] ?? $response['body']),
            ['raw' => $response['decoded'] ?? $response['body']]
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getChargeStatus(string $externalId): array
    {
        $response = $this->stripeRequest('GET', "/v1/payment_intents/{$externalId}");

        if ($response['status'] === 200) {
            $body = $response['decoded'];
            return $this->successResponse([
                'external_id' => $body['id'],
                'status'      => $this->mapStatus($body['status'] ?? 'unknown'),
                'paid_amount' => ($body['amount_received'] ?? 0) / 100,
                'paid_at'     => isset($body['charges']['data'][0]['created'])
                    ? date('c', $body['charges']['data'][0]['created'])
                    : null,
                'raw'         => $body,
            ]);
        }

        return $this->errorResponse('Erro ao consultar cobrança', ['raw' => $response['decoded']]);
    }

    /**
     * {@inheritDoc}
     */
    public function refund(string $externalId, ?float $amount = null): array
    {
        $payload = ['payment_intent' => $externalId];
        if ($amount !== null) {
            $payload['amount'] = (int) round($amount * 100);
        }

        $response = $this->stripeRequest('POST', '/v1/refunds', $payload);

        if ($response['status'] === 200) {
            $body = $response['decoded'];
            return $this->successResponse([
                'refund_id' => $body['id'] ?? '',
                'status'    => $body['status'] === 'succeeded' ? 'refunded' : 'pending',
                'raw'       => $body,
            ]);
        }

        return $this->errorResponse('Erro ao estornar cobrança', ['raw' => $response['decoded']]);
    }

    // ══════════════════════════════════════════════════════════════
    // Webhooks
    // ══════════════════════════════════════════════════════════════

    /**
     * {@inheritDoc}
     *
     * Stripe usa header stripe-signature com timestamp (t) e assinatura (v1) HMAC-SHA256.
     */
    public function validateWebhookSignature(string $payload, array $headers, string $secret): bool
    {
        $sigHeader = $headers['stripe-signature'] ?? $headers['Stripe-Signature'] ?? '';
        if (empty($sigHeader) || empty($secret)) {
            return false;
        }

        // Extrair timestamp e signatures do header
        $parts = [];
        foreach (explode(',', $sigHeader) as $item) {
            $kv = explode('=', trim($item), 2);
            if (count($kv) === 2) {
                $parts[$kv[0]][] = $kv[1];
            }
        }

        $timestamp  = $parts['t'][0] ?? '';
        $signatures = $parts['v1'] ?? [];

        if (empty($timestamp) || empty($signatures)) {
            return false;
        }

        // Recomputar a assinatura esperada
        $signedPayload = "{$timestamp}.{$payload}";
        $expectedSig = hash_hmac('sha256', $signedPayload, $secret);

        foreach ($signatures as $sig) {
            if (hash_equals($expectedSig, $sig)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function parseWebhookPayload(string $payload, array $headers): array
    {
        $data = json_decode($payload, true) ?? [];

        $eventType = $data['type'] ?? 'unknown';
        $object = $data['data']['object'] ?? [];

        $externalId = $object['id'] ?? '';
        $metadata   = $object['metadata'] ?? [];

        return [
            'event_type'  => $eventType,
            'external_id' => $externalId,
            'status'      => $this->mapStatus($object['status'] ?? 'unknown'),
            'amount'      => ($object['amount'] ?? 0) / 100,
            'paid_amount' => ($object['amount_received'] ?? $object['amount'] ?? 0) / 100,
            'metadata'    => $metadata,
            'raw'         => $data,
        ];
    }

    // ══════════════════════════════════════════════════════════════
    // Testes
    // ══════════════════════════════════════════════════════════════

    /**
     * {@inheritDoc}
     *
     * Testa a conexão consultando o endpoint /v1/balance.
     */
    public function testConnection(): array
    {
        $response = $this->stripeRequest('GET', '/v1/balance');

        if ($response['status'] === 200) {
            return ['success' => true, 'message' => 'Conexão com Stripe estabelecida com sucesso.'];
        }

        return ['success' => false, 'message' => 'Falha na conexão: ' . ($response['decoded']['error']['message'] ?? 'Erro desconhecido')];
    }

    // ══════════════════════════════════════════════════════════════
    // Internals
    // ══════════════════════════════════════════════════════════════

    /**
     * {@inheritDoc}
     *
     * Mapeamento de status do Stripe para o padrão Akti.
     */
    protected function mapStatus(string $gatewayStatus): string
    {
        $map = [
            'succeeded'                => 'approved',
            'paid'                     => 'approved',
            'requires_payment_method'  => 'pending',
            'requires_confirmation'    => 'pending',
            'requires_action'          => 'pending',
            'processing'               => 'pending',
            'requires_capture'         => 'pending',
            'unpaid'                   => 'pending',
            'no_payment_required'      => 'approved',
            'canceled'                 => 'cancelled',
        ];

        return $map[$gatewayStatus] ?? 'pending';
    }

    /**
     * Faz requisição para a API Stripe (form-urlencoded + Basic Auth).
     *
     * @param string $method Método HTTP (GET, POST, PUT, DELETE).
     * @param string $path   Path da API (ex: '/v1/payment_intents').
     * @param array  $data   Dados do body (serão enviados como form-urlencoded).
     *
     * @return array Resposta com 'status', 'body', 'decoded', 'error'.
     */
    private function stripeRequest(string $method, string $path, array $data = []): array
    {
        $url = self::API_URL . $path;
        $ch  = curl_init();

        $headers = [
            'Authorization: Basic ' . base64_encode($this->getCredential('secret_key') . ':'),
        ];

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_SSL_VERIFYPEER => !$this->isSandbox(),
        ]);

        if (!empty($data) && in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'])) {
            $body = http_build_query($data);
            $body = str_replace(['%5B', '%5D'], ['[', ']'], $body);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['status' => 0, 'body' => $error, 'decoded' => null, 'error' => $error];
        }

        return [
            'status'  => $httpCode,
            'body'    => $response,
            'decoded' => json_decode($response, true),
            'error'   => null,
        ];
    }
}
