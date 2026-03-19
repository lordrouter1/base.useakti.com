<?php
namespace Akti\Gateways\Contracts;

/**
 * PaymentGatewayInterface — Contrato para todos os gateways de pagamento.
 *
 * Cada gateway (Mercado Pago, Stripe, PagSeguro, etc.) deve implementar
 * esta interface, garantindo que o core do sistema nunca dependa de uma
 * implementação concreta (Strategy Pattern).
 *
 * O GatewayManager resolve qual implementação usar com base na config do tenant.
 *
 * Métodos obrigatórios:
 *   - getName()              → Identificação
 *   - supports()             → Capabilities
 *   - createCharge()         → Criar cobrança
 *   - getChargeStatus()      → Consultar status
 *   - refund()               → Estornar
 *   - parseWebhookPayload()  → Interpretar webhook
 *   - validateWebhookSignature() → Validar assinatura
 *
 * @package Akti\Gateways\Contracts
 * @see     \Akti\Gateways\AbstractGateway
 * @see     \Akti\Gateways\GatewayManager
 */
interface PaymentGatewayInterface
{
    // ══════════════════════════════════════════════════════════════
    // Identificação
    // ══════════════════════════════════════════════════════════════

    /**
     * Retorna o slug único do gateway (ex: 'mercadopago', 'stripe').
     * Deve corresponder ao campo payment_gateways.gateway_slug no banco.
     *
     * @return string
     */
    public function getSlug(): string;

    /**
     * Retorna o nome amigável para exibição na UI.
     *
     * @return string
     */
    public function getDisplayName(): string;

    // ══════════════════════════════════════════════════════════════
    // Capabilities
    // ══════════════════════════════════════════════════════════════

    /**
     * Verifica se o gateway suporta um determinado método de pagamento.
     * Ex: 'pix', 'credit_card', 'boleto', 'debit_card'.
     *
     * @param string $method Método de pagamento
     * @return bool
     */
    public function supports(string $method): bool;

    /**
     * Retorna lista de métodos suportados pelo gateway.
     *
     * @return string[] Ex: ['pix', 'credit_card', 'boleto']
     */
    public function getSupportedMethods(): array;

    // ══════════════════════════════════════════════════════════════
    // Operações de Cobrança
    // ══════════════════════════════════════════════════════════════

    /**
     * Cria uma cobrança no gateway externo.
     *
     * @param array $data Dados da cobrança:
     *   - amount       (float)  Valor em BRL
     *   - description  (string) Descrição da cobrança
     *   - method       (string) Método: 'pix', 'credit_card', 'boleto'
     *   - customer     (array)  Dados do cliente (name, email, document)
     *   - installment_id (int)  ID da parcela vinculada
     *   - order_id     (int)    ID do pedido vinculado
     *   - metadata     (array)  Dados extras (opcionais)
     *
     * @return array Resultado padronizado:
     *   - success       (bool)
     *   - external_id   (string) ID no gateway
     *   - status        (string) Status: 'pending', 'approved', 'rejected'
     *   - payment_url   (string|null) URL de pagamento (checkout, QR code)
     *   - qr_code       (string|null) QR code para PIX
     *   - qr_code_base64 (string|null) QR code em base64
     *   - boleto_url    (string|null) URL do boleto
     *   - boleto_barcode (string|null) Código de barras
     *   - expires_at    (string|null) Data de expiração
     *   - raw           (array) Resposta bruta da API
     */
    public function createCharge(array $data): array;

    /**
     * Consulta o status de uma cobrança pelo ID externo.
     *
     * @param string $externalId ID da transação no gateway
     * @return array Resultado padronizado:
     *   - success       (bool)
     *   - external_id   (string)
     *   - status        (string) 'pending', 'approved', 'rejected', 'refunded', 'cancelled'
     *   - paid_amount   (float|null)
     *   - paid_at       (string|null) ISO 8601
     *   - raw           (array)
     */
    public function getChargeStatus(string $externalId): array;

    /**
     * Solicita estorno total ou parcial de uma cobrança.
     *
     * @param string     $externalId ID da transação no gateway
     * @param float|null $amount     Valor a estornar (null = total)
     * @return array Resultado padronizado:
     *   - success     (bool)
     *   - refund_id   (string|null) ID do estorno no gateway
     *   - status      (string) 'refunded', 'pending'
     *   - raw         (array)
     */
    public function refund(string $externalId, ?float $amount = null): array;

    // ══════════════════════════════════════════════════════════════
    // Webhooks
    // ══════════════════════════════════════════════════════════════

    /**
     * Valida a assinatura do webhook recebido.
     *
     * @param string $payload    Body raw do webhook (JSON string)
     * @param array  $headers    Headers HTTP da requisição
     * @param string $secret     Webhook secret configurado no gateway
     * @return bool True se a assinatura for válida
     */
    public function validateWebhookSignature(string $payload, array $headers, string $secret): bool;

    /**
     * Interpreta o payload do webhook e retorna dados padronizados.
     *
     * @param string $payload Body raw do webhook (JSON string)
     * @param array  $headers Headers HTTP da requisição
     * @return array Dados padronizados:
     *   - event_type   (string) Tipo do evento (ex: 'payment.approved', 'charge.refunded')
     *   - external_id  (string) ID da transação no gateway
     *   - status       (string) 'approved', 'pending', 'rejected', 'refunded', 'cancelled'
     *   - amount       (float)
     *   - paid_amount  (float|null)
     *   - metadata     (array) Metadados enviados na criação (installment_id, order_id, etc.)
     *   - raw          (array) Payload bruto
     */
    public function parseWebhookPayload(string $payload, array $headers): array;

    // ══════════════════════════════════════════════════════════════
    // Configuração
    // ══════════════════════════════════════════════════════════════

    /**
     * Define as credenciais do gateway.
     *
     * @param array $credentials Array com credenciais (api_key, secret, token, etc.)
     * @return void
     */
    public function setCredentials(array $credentials): void;

    /**
     * Define configurações extras (ex: pix_enabled, boleto_days, etc.).
     *
     * @param array $settings
     * @return void
     */
    public function setSettings(array $settings): void;

    /**
     * Define o ambiente (sandbox ou production).
     *
     * @param string $environment 'sandbox' ou 'production'
     * @return void
     */
    public function setEnvironment(string $environment): void;

    /**
     * Retorna a lista de campos de credencial exigidos por este gateway.
     * Usado pela UI de configuração para renderizar o formulário dinamicamente.
     *
     * @return array Ex: [
     *   ['key' => 'access_token', 'label' => 'Access Token', 'type' => 'password', 'required' => true],
     *   ['key' => 'public_key',   'label' => 'Public Key',   'type' => 'text',     'required' => true],
     * ]
     */
    public function getCredentialFields(): array;

    /**
     * Retorna a lista de campos de configuração extras.
     *
     * @return array Ex: [
     *   ['key' => 'pix_expiration_minutes', 'label' => 'Expiração PIX (minutos)', 'type' => 'number', 'default' => 30],
     * ]
     */
    public function getSettingsFields(): array;

    /**
     * Testa se as credenciais configuradas são válidas (ping na API).
     *
     * @return array ['success' => bool, 'message' => string]
     */
    public function testConnection(): array;
}
