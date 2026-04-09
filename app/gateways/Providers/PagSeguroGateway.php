<?php
namespace Akti\Gateways\Providers;

use Akti\Gateways\AbstractGateway;

/**
 * PagSeguroGateway — Integração com a API do PagSeguro (PagBank).
 *
 * Suporta: PIX, Cartão de Crédito, Boleto.
 * API Docs: https://developer.pagbank.com.br/reference
 *
 * Credenciais necessárias:
 *   - token (Token de integração do PagSeguro)
 *
 * @package Akti\Gateways\Providers
 */
class PagSeguroGateway extends AbstractGateway
{
    private const SANDBOX_URL    = 'https://sandbox.api.pagseguro.com';
    private const PRODUCTION_URL = 'https://api.pagseguro.com';

    // ══════════════════════════════════════════════════════════════
    // Identificação
    // ══════════════════════════════════════════════════════════════

    public function getSlug(): string
    {
        return 'pagseguro';
    }

    public function getDisplayName(): string
    {
        return 'PagSeguro';
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
            ['key' => 'token', 'label' => 'Token de Integração', 'type' => 'password', 'required' => true],
        ];
    }

    public function getSettingsFields(): array
    {
        return [
            ['key' => 'pix_expiration_minutes', 'label' => 'Expiração PIX (minutos)', 'type' => 'number', 'default' => 30],
            ['key' => 'boleto_days_due',        'label' => 'Dias para vencimento do boleto', 'type' => 'number', 'default' => 3],
            ['key' => 'notification_url',       'label' => 'URL de Webhook (automática)', 'type' => 'readonly', 'default' => ''],
        ];
    }

    // ══════════════════════════════════════════════════════════════
    // Operações
    // ══════════════════════════════════════════════════════════════

    public function createCharge(array $data): array
    {
        $method = $data['method'] ?? 'pix';

        $this->log('info', 'Creating charge', ['method' => $method, 'amount' => $data['amount']]);

        // PagSeguro trabalha com valores em centavos
        $amountCents = (int) round($data['amount'] * 100);

        // Montar notification_urls somente com URLs válidas
        $notifUrl = trim((string) $this->getSetting('notification_url', ''));
        $notificationUrls = [];
        if ($notifUrl !== '' && filter_var($notifUrl, FILTER_VALIDATE_URL)) {
            $notificationUrls[] = $notifUrl;
        }

        $payload = [
            'reference_id'      => $data['reference_id'] ?? ('akti_' . ($data['order_id'] ?? uniqid())),
            'customer'          => $this->buildCustomer($data),
            'items'             => [
                [
                    'reference_id' => (string) ($data['order_id'] ?? uniqid()),
                    'name'         => $data['description'] ?? 'Pagamento Akti',
                    'quantity'     => 1,
                    'unit_amount'  => $amountCents,
                ],
            ],
            'qr_codes'          => [],
            'shipping'          => null,
            'notification_urls' => $notificationUrls,
        ];

        // Quando method='auto', NÃO definir charges com payment_method —
        // criar apenas o pedido e usar o link de checkout do PagSeguro,
        // onde o cliente escolhe a forma de pagamento.
        if ($method !== 'auto') {
            $payload['charges'] = [
                [
                    'reference_id'   => 'charge_' . ($data['order_id'] ?? uniqid()),
                    'description'    => $data['description'] ?? 'Pagamento Akti',
                    'amount'         => [
                        'value'    => $amountCents,
                        'currency' => 'BRL',
                    ],
                    'payment_method' => $this->buildPaymentMethod($data, $method),
                    'metadata'       => [
                        'installment_id' => (string) ($data['installment_id'] ?? ''),
                        'order_id'       => (string) ($data['order_id'] ?? ''),
                        'source'         => 'akti',
                    ],
                ],
            ];
        }

        // PIX: adicionar qr_code no nível do pedido
        if ($method === 'pix') {
            $expMinutes = (int) $this->getSetting('pix_expiration_minutes', 30);
            $payload['qr_codes'] = [
                [
                    'amount' => [
                        'value' => $amountCents,
                    ],
                    'expiration_date' => date('c', strtotime("+{$expMinutes} minutes")),
                ],
            ];
        }

        // Remover campos null/vazios
        if (empty($payload['notification_urls'])) {
            unset($payload['notification_urls']);
        }
        if (empty($payload['qr_codes'])) {
            unset($payload['qr_codes']);
        }
        $payload = array_filter($payload, fn($v) => $v !== null);

        $response = $this->httpRequest('POST', $this->getBaseUrl() . '/orders', [
            'Authorization: Bearer ' . $this->getCredential('token'),
        ], $payload);

        if ($response['status'] === 201 || $response['status'] === 200) {
            $body = $response['decoded'];

            // Extrair dados de resposta
            $charge   = $body['charges'][0] ?? [];
            $qrCodes  = $body['qr_codes'][0] ?? [];
            $links    = $charge['links'] ?? $body['links'] ?? [];
            $status   = $charge['status'] ?? $body['status'] ?? 'WAITING';

            return $this->successResponse([
                'external_id'    => $body['id'] ?? $charge['id'] ?? '',
                'status'         => $this->mapStatus($status),
                'payment_url'    => $this->findLink($links, 'PAYMENT') ?? $this->findLink($links, 'PAY') ?? null,
                'qr_code'        => $qrCodes['text'] ?? null,
                'qr_code_base64' => null,
                'boleto_url'     => $this->findLink($links, 'PAYMENT') ?? $this->findLink($links, 'PDF') ?? null,
                'boleto_barcode' => $charge['payment_method']['boleto']['barcode'] ?? null,
                'expires_at'     => $qrCodes['expiration_date'] ?? null,
                'raw'            => $body,
            ]);
        }

        // Log detalhado do erro
        $errMsg = $this->extractPagSeguroError($response);
        $this->log('error', 'Charge creation failed', [
            'status' => $response['status'],
            'body'   => $response['body'],
            'error'  => $errMsg,
        ]);

        return $this->errorResponse(
            'Erro ao criar cobrança no PagSeguro: ' . $errMsg,
            ['raw' => $response['decoded'] ?? $response['body']]
        );
    }

    public function getChargeStatus(string $externalId): array
    {
        // PagBank v4: consulta por /orders/{id}
        $response = $this->httpRequest('GET', $this->getBaseUrl() . '/orders/' . $externalId, [
            'Authorization: Bearer ' . $this->getCredential('token'),
        ]);

        if ($response['status'] === 200) {
            $body = $response['decoded'];
            $charge = $body['charges'][0] ?? [];
            $amount = $charge['amount']['value'] ?? $body['amount']['value'] ?? 0;
            return $this->successResponse([
                'external_id' => $body['id'] ?? $externalId,
                'status'      => $this->mapStatus($charge['status'] ?? $body['status'] ?? 'WAITING'),
                'paid_amount' => $amount / 100,
                'paid_at'     => $charge['paid_at'] ?? $body['paid_at'] ?? null,
                'raw'         => $body,
            ]);
        }

        return $this->errorResponse('Erro ao consultar cobrança: ' . $this->extractPagSeguroError($response), ['raw' => $response['decoded']]);
    }

    public function refund(string $externalId, ?float $amount = null): array
    {
        // PagBank v4: precisa do charge_id (dentro do order)
        // Primeiro buscar o order para pegar o charge_id
        $orderResp = $this->httpRequest('GET', $this->getBaseUrl() . '/orders/' . $externalId, [
            'Authorization: Bearer ' . $this->getCredential('token'),
        ]);

        $chargeId = $orderResp['decoded']['charges'][0]['id'] ?? null;
        if (!$chargeId) {
            return $this->errorResponse('Não foi possível encontrar a cobrança para estornar.');
        }

        $payload = [];
        if ($amount !== null) {
            $payload['amount'] = ['value' => (int) round($amount * 100)];
        }

        $response = $this->httpRequest('POST', $this->getBaseUrl() . "/charges/{$chargeId}/cancel", [
            'Authorization: Bearer ' . $this->getCredential('token'),
        ], $payload ?: null);

        if ($response['status'] === 200 || $response['status'] === 201) {
            $body = $response['decoded'];
            return $this->successResponse([
                'refund_id' => $body['id'] ?? $externalId,
                'status'    => 'refunded',
                'raw'       => $body,
            ]);
        }

        return $this->errorResponse('Erro ao estornar cobrança: ' . $this->extractPagSeguroError($response), ['raw' => $response['decoded']]);
    }

    // ══════════════════════════════════════════════════════════════
    // Webhooks
    // ══════════════════════════════════════════════════════════════

    public function validateWebhookSignature(string $payload, array $headers, string $secret): bool
    {
        // PagSeguro usa verificação por IP + token de notificação
        // Para segurança adicional, valida o header x-pagseguro-signature se presente
        $signature = $headers['x-pagseguro-signature'] ?? $headers['X-Pagseguro-Signature'] ?? '';

        if (!empty($signature) && !empty($secret)) {
            $expected = hash_hmac('sha256', $payload, $secret);
            return hash_equals($expected, $signature);
        }

        // Se sem header de assinatura, aceita (PagSeguro v4 nem sempre envia)
        // Nesse caso, valida pela consulta posterior da transação
        return true;
    }

    public function parseWebhookPayload(string $payload, array $headers): array
    {
        $data = json_decode($payload, true) ?? [];

        // PagSeguro envia charges[0] com os dados da cobrança
        $charge = $data['charges'][0] ?? $data;

        return [
            'event_type'  => $data['type'] ?? 'transaction',
            'external_id' => $charge['id'] ?? '',
            'status'      => $this->mapStatus($charge['status'] ?? 'WAITING'),
            'amount'      => ($charge['amount']['value'] ?? 0) / 100,
            'paid_amount' => ($charge['amount']['value'] ?? 0) / 100,
            'metadata'    => $charge['metadata'] ?? $data['metadata'] ?? [],
            'raw'         => $data,
        ];
    }

    // ══════════════════════════════════════════════════════════════
    // Testes
    // ══════════════════════════════════════════════════════════════

    public function testConnection(): array
    {
        $token = $this->getCredential('token');
        if (empty($token)) {
            return ['success' => false, 'message' => 'Token de integração não informado.'];
        }

        // PagBank v4: testar com /public-keys/card (endpoint leve)
        $response = $this->httpRequest('POST', $this->getBaseUrl() . '/public-keys', [
            'Authorization: Bearer ' . $token,
        ], ['type' => 'card']);

        if ($response['status'] === 200 || $response['status'] === 201) {
            return ['success' => true, 'message' => 'Conexão com PagSeguro (PagBank) estabelecida com sucesso.'];
        }

        // Fallback: tentar GET em /orders com limit=1
        $response2 = $this->httpRequest('GET', $this->getBaseUrl() . '/orders?reference_id=akti_test&limit=1', [
            'Authorization: Bearer ' . $token,
        ]);

        if ($response2['status'] === 200) {
            return ['success' => true, 'message' => 'Conexão com PagSeguro (PagBank) estabelecida com sucesso.'];
        }

        return ['success' => false, 'message' => 'Falha na conexão: ' . $this->extractPagSeguroError($response)];
    }

    // ══════════════════════════════════════════════════════════════
    // Internals
    // ══════════════════════════════════════════════════════════════

    private function getBaseUrl(): string
    {
        return $this->isSandbox() ? self::SANDBOX_URL : self::PRODUCTION_URL;
    }

    protected function mapStatus(string $gatewayStatus): string
    {
        $map = [
            'PAID'       => 'approved',
            'AUTHORIZED' => 'approved',
            'WAITING'    => 'pending',
            'IN_ANALYSIS'=> 'pending',
            'DECLINED'   => 'rejected',
            'CANCELED'   => 'cancelled',
        ];

        return $map[strtoupper($gatewayStatus)] ?? 'pending';
    }

    /**
     * Monta o objeto customer para a API PagBank v4 (Orders).
     */
    private function buildCustomer(array $data): array
    {
        $name  = $data['customer']['name'] ?? 'Cliente';
        $email = $data['customer']['email'] ?? '';
        $doc   = preg_replace('/\D/', '', $data['customer']['document'] ?? '');

        $customer = [
            'name'  => $name ?: 'Cliente',
            'email' => $email ?: 'cliente@exemplo.com',
            'tax_id' => $doc,
        ];

        // PagSeguro requer phone em alguns métodos
        if (!empty($data['customer']['phone'])) {
            $phone = preg_replace('/\D/', '', $data['customer']['phone']);
            $customer['phones'] = [
                [
                    'country' => '55',
                    'area'    => substr($phone, 0, 2),
                    'number'  => substr($phone, 2),
                    'type'    => 'MOBILE',
                ],
            ];
        }

        return $customer;
    }

    /**
     * Extrai mensagem de erro legível da resposta do PagSeguro.
     */
    private function extractPagSeguroError(array $response): string
    {
        $decoded = $response['decoded'] ?? null;
        if (!$decoded) {
            return $response['body'] ?: 'Erro desconhecido (sem resposta)';
        }

        // PagBank v4 retorna error_messages[] com description e parameter_name
        if (!empty($decoded['error_messages']) && is_array($decoded['error_messages'])) {
            $msgs = [];
            foreach ($decoded['error_messages'] as $err) {
                $desc  = $err['description'] ?? $err['message'] ?? '';
                $param = $err['parameter_name'] ?? '';
                $msgs[] = $param ? "{$param}: {$desc}" : $desc;
            }
            return implode('; ', array_filter($msgs)) ?: 'Erro desconhecido';
        }

        // Fallback: campo message direto
        if (!empty($decoded['message'])) {
            return $decoded['message'];
        }

        return $response['body'] ?: 'Erro desconhecido';
    }

    private function buildPaymentMethod(array $data, string $method): array
    {
        switch ($method) {
            case 'pix':
                return [
                    'type' => 'PIX',
                ];

            case 'boleto':
                $daysDue = (int) $this->getSetting('boleto_days_due', 3);
                $doc = preg_replace('/\D/', '', $data['customer']['document'] ?? '');
                return [
                    'type'   => 'BOLETO',
                    'boleto' => [
                        'due_date'             => date('Y-m-d', strtotime("+{$daysDue} days")),
                        'instruction_lines'    => [
                            'line_1' => $data['description'] ?? 'Pagamento Akti',
                            'line_2' => 'Pedido #' . str_pad((string)($data['order_id'] ?? '0'), 4, '0', STR_PAD_LEFT),
                        ],
                        'holder'   => [
                            'name'    => $data['customer']['name'] ?? 'Cliente',
                            'tax_id'  => $doc,
                            'email'   => $data['customer']['email'] ?? '',
                            'address' => [
                                'country' => 'BRA',
                                'region'  => $data['customer']['state'] ?? 'SP',
                                'city'    => $data['customer']['city'] ?? 'São Paulo',
                                'postal_code' => preg_replace('/\D/', '', $data['customer']['zip'] ?? '01000000'),
                                'street'  => $data['customer']['street'] ?? 'Não informado',
                                'number'  => $data['customer']['number'] ?? 'S/N',
                                'locality' => $data['customer']['neighborhood'] ?? 'Centro',
                            ],
                        ],
                    ],
                ];

            case 'credit_card':
                $cardData = !empty($data['card_token'])
                    ? ['encrypted' => $data['card_token']]
                    : [
                        'number'        => $data['card_number'] ?? '',
                        'exp_month'     => $data['card_exp_month'] ?? '',
                        'exp_year'      => $data['card_exp_year'] ?? '',
                        'security_code' => $data['card_cvv'] ?? '',
                        'holder'        => [
                            'name' => $data['card_holder'] ?? $data['customer']['name'] ?? '',
                        ],
                    ];
                return [
                    'type' => 'CREDIT_CARD',
                    'installments' => (int) ($data['card_installments'] ?? 1),
                    'capture'      => true,
                    'card'         => $cardData,
                ];

            case 'debit_card':
                $cardData = !empty($data['card_token'])
                    ? ['encrypted' => $data['card_token']]
                    : [
                        'number'        => $data['card_number'] ?? '',
                        'exp_month'     => $data['card_exp_month'] ?? '',
                        'exp_year'      => $data['card_exp_year'] ?? '',
                        'security_code' => $data['card_cvv'] ?? '',
                        'holder'        => [
                            'name' => $data['card_holder'] ?? $data['customer']['name'] ?? '',
                        ],
                    ];
                return [
                    'type' => 'DEBIT_CARD',
                    'card' => $cardData,
                ];

            default:
                return ['type' => 'PIX'];
        }
    }

    private function findLink(array $links, string $rel): ?string
    {
        foreach ($links as $link) {
            if (strtoupper($link['rel'] ?? '') === strtoupper($rel)) {
                return $link['href'] ?? null;
            }
        }
        return null;
    }
}
