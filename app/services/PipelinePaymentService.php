<?php
namespace Akti\Services;

use Akti\Models\Order;
use Akti\Models\CompanySettings;
use Akti\Models\Financial;
use Akti\Models\PaymentGateway;
use Akti\Gateways\GatewayManager;
use PDO;

/**
 * Service responsável pela lógica de geração de links de pagamento do pipeline.
 * Extraído do PipelineController (Fase 2 — Refatoração de Controllers Monolíticos).
 */
class PipelinePaymentService implements Contracts\PipelinePaymentServiceInterface
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Gera link de pagamento via gateway configurado ou fallback legado.
     *
     * @param int         $orderId     ID do pedido
     * @param string      $gatewaySlug Slug do gateway (vazio = usar padrão)
     * @param string      $method      Método de pagamento (auto, pix, boleto, credit_card)
     * @return array Resultado com success, payment_url, etc.
     */
    public function generatePaymentLink(int $orderId, string $gatewaySlug = '', string $method = 'auto'): array
    {
        $orderModel = new Order($this->db);
        $order = $orderModel->readOne($orderId);
        if (!$order) {
            return ['success' => false, 'message' => 'Pedido não encontrado.'];
        }

        // Resolver o gateway: se informado, usar o slug; senão, usar o padrão
        $gwModel = new PaymentGateway($this->db);

        if (!empty($gatewaySlug)) {
            $gatewayRow = $gwModel->readBySlug($gatewaySlug);
        } else {
            $gatewayRow = $gwModel->getDefault();
        }

        if (!$gatewayRow || !$gatewayRow['is_active']) {
            // Fallback: tentar credenciais antigas do company_settings (compatibilidade)
            $result = $this->legacyMercadoPagoLink($order, $orderId);

            // Se o link foi gerado com sucesso no fallback, salvar e marcar para aprovação
            if (!empty($result['success']) && !empty($result['payment_url'])) {
                $this->persistPaymentLink($orderModel, $orderId, $order, $result['payment_url'], 'mercadopago', 'auto');
            }

            return $result;
        }

        try {
            $gateway = GatewayManager::resolveFromRow($gatewayRow);
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Erro ao inicializar gateway: ' . $e->getMessage()];
        }

        // Verificar se o gateway suporta o método selecionado
        if (!$gateway->supports($method)) {
            return ['success' => false, 'message' => "O gateway {$gatewayRow['display_name']} não suporta o método '{$method}'."];
        }

        $grossTotal = (float)($order['total_amount'] ?? 0);
        $discount = (float)($order['discount'] ?? 0);
        $totalAmount = round(max(0, $grossTotal - $discount), 2);

        if ($totalAmount <= 0) {
            return ['success' => false, 'message' => 'Pedido com valor inválido para gerar link de pagamento.'];
        }

        // Buscar a primeira parcela pendente do pedido para vincular ao gateway
        $installmentId = $this->findPendingInstallmentId($orderId);

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        $chargeData = [
            'amount'         => $totalAmount,
            'description'    => 'Pedido #' . str_pad((string)$orderId, 4, '0', STR_PAD_LEFT),
            'method'         => $method,
            'order_id'       => $orderId,
            'installment_id' => $installmentId,
            'return_url'     => $protocol . '://' . $host . '/?page=pipeline&action=index',
            'customer'       => [
                'name'     => $order['customer_name'] ?? '',
                'email'    => $order['customer_email'] ?? '',
                'document' => $order['customer_document'] ?? '',
            ],
            'metadata'       => [
                'order_id'       => $orderId,
                'installment_id' => $installmentId,
                'source'         => 'akti',
                'tenant'         => $_SESSION['tenant']['db_name'] ?? ($_SESSION['tenant']['database'] ?? ''),
            ],
        ];

        // Enriquecer customer com endereço (necessário para boleto Stripe)
        $customerId = $order['customer_id'] ?? null;
        if ($customerId) {
            $addrStmt = $this->db->prepare(
                "SELECT zipcode, address_street, address_number, address_neighborhood, address_city, address_state
                 FROM customers WHERE id = :cid LIMIT 1"
            );
            $addrStmt->execute([':cid' => $customerId]);
            $addr = $addrStmt->fetch(\PDO::FETCH_ASSOC);
            if ($addr) {
                $chargeData['customer']['zip']          = $addr['zipcode'] ?? '';
                $chargeData['customer']['street']       = $addr['address_street'] ?? '';
                $chargeData['customer']['number']       = $addr['address_number'] ?? '';
                $chargeData['customer']['neighborhood'] = $addr['address_neighborhood'] ?? '';
                $chargeData['customer']['city']         = $addr['address_city'] ?? '';
                $chargeData['customer']['state']        = $addr['address_state'] ?? '';
            }
        }

        $result = $gateway->createCharge($chargeData);

        if (!$result['success']) {
            return ['success' => false, 'message' => $result['error'] ?? $result['message'] ?? 'Erro ao gerar cobrança no gateway.'];
        }

        // Logar transação
        $gwModel->logTransaction([
            'gateway_slug'        => $gatewayRow['gateway_slug'],
            'order_id'            => $orderId,
            'external_id'         => $result['external_id'] ?? null,
            'external_status'     => $result['status'] ?? null,
            'amount'              => $totalAmount,
            'payment_method_type' => $method,
            'raw_payload'         => [
                'request'  => $chargeData,
                'response' => $result['raw'] ?? [],
            ],
            'event_type'          => 'charge.created',
        ]);

        // Persistir link de pagamento no pedido para reenvio
        $paymentUrl = $result['payment_url'] ?? $result['qr_code'] ?? $result['boleto_url'] ?? null;
        if ($paymentUrl) {
            $this->persistPaymentLink($orderModel, $orderId, $order, $paymentUrl, $gatewayRow['gateway_slug'], $method);
        }

        return [
            'success'        => true,
            'payment_url'    => $result['payment_url'] ?? null,
            'qr_code'        => $result['qr_code'] ?? null,
            'qr_code_base64' => $result['qr_code_base64'] ?? null,
            'boleto_url'     => $result['boleto_url'] ?? null,
            'external_id'    => $result['external_id'] ?? null,
        ];
    }

    /**
     * Fallback: gera link via Mercado Pago usando credenciais antigas (company_settings).
     * Mantido para retrocompatibilidade com tenants que ainda não migraram para o módulo de gateways.
     */
    public function legacyMercadoPagoLink(array $order, int $orderId): array
    {
        $settingsModel = new CompanySettings($this->db);
        $settings = $settingsModel->getAll();
        $accessToken = trim((string)($settings['mercadopago_access_token'] ?? getenv('MERCADOPAGO_ACCESS_TOKEN') ?? ''));

        if ($accessToken === '') {
            return [
                'success' => false,
                'message' => 'Nenhum gateway de pagamento ativo. Configure em Gateways de Pagamento ou defina o Access Token do Mercado Pago nas configurações.',
            ];
        }

        $grossTotal = (float)($order['total_amount'] ?? 0);
        $discount = (float)($order['discount'] ?? 0);
        $totalAmount = round(max(0, $grossTotal - $discount), 2);

        if ($totalAmount <= 0) {
            return ['success' => false, 'message' => 'Pedido com valor inválido para gerar link de pagamento.'];
        }

        $baseUrl = $this->getAppBaseUrl();
        $externalRef = 'order_' . $orderId . '_tenant_' . ($_SESSION['tenant']['database'] ?? 'default');

        $payload = [
            'items' => [[
                'id' => (string)$orderId,
                'title' => 'Pedido #' . str_pad((string)$orderId, 4, '0', STR_PAD_LEFT),
                'quantity' => 1,
                'currency_id' => 'BRL',
                'unit_price' => $totalAmount,
            ]],
            'payer' => [
                'name' => (string)($order['customer_name'] ?? ''),
                'email' => (string)($order['customer_email'] ?? ''),
            ],
            'external_reference' => $externalRef,
            'back_urls' => [
                'success' => $baseUrl . '/?page=pipeline&action=detail&id=' . $orderId . '&mp_status=success',
                'pending' => $baseUrl . '/?page=pipeline&action=detail&id=' . $orderId . '&mp_status=pending',
                'failure' => $baseUrl . '/?page=pipeline&action=detail&id=' . $orderId . '&mp_status=failure',
            ],
            'auto_return' => 'approved',
            'metadata' => ['order_id' => $orderId, 'tenant' => (string)($_SESSION['tenant']['database'] ?? '')],
        ];

        $result = $this->createMercadoPagoPreference($accessToken, $payload);
        if (!$result['success']) {
            return $result;
        }

        $link = $result['data']['init_point'] ?? $result['data']['sandbox_init_point'] ?? '';
        if (!$link) {
            return ['success' => false, 'message' => 'Mercado Pago não retornou URL de pagamento.'];
        }

        return ['success' => true, 'payment_url' => $link, 'preference_id' => $result['data']['id'] ?? null];
    }

    /**
     * Cria uma preferência de pagamento no Mercado Pago via API.
     */
    public function createMercadoPagoPreference(string $accessToken, array $payload): array
    {
        $url = 'https://api.mercadopago.com/checkout/preferences';

        if (!function_exists('curl_init')) {
            return ['success' => false, 'message' => 'Extensão cURL não disponível no servidor.'];
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json',
                'X-Idempotency-Key: akti-' . uniqid('', true),
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 20,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false || $curlError) {
            return ['success' => false, 'message' => 'Falha ao conectar com Mercado Pago: ' . $curlError];
        }

        $data = json_decode($response, true);
        if ($httpCode < 200 || $httpCode >= 300) {
            $mpMessage = $data['message'] ?? $data['error'] ?? 'Erro ao gerar link no Mercado Pago.';
            return ['success' => false, 'message' => $mpMessage];
        }

        return ['success' => true, 'data' => is_array($data) ? $data : []];
    }

    /**
     * Retorna a base URL da aplicação (scheme + host).
     */
    public function getAppBaseUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host;
    }

    /**
     * Busca a primeira parcela pendente do pedido (para vincular ao gateway).
     */
    private function findPendingInstallmentId(int $orderId): ?int
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT id FROM order_installments
                 WHERE order_id = :oid AND status IN ('pendente', 'atrasado')
                 ORDER BY installment_number ASC LIMIT 1"
            );
            $stmt->execute([':oid' => $orderId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? (int)$row['id'] : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Salva o link de pagamento no pedido e marca como pendente de aprovação.
     */
    private function persistPaymentLink(Order $orderModel, int $orderId, array $order, string $paymentUrl, string $gatewaySlug, string $method): void
    {
        $orderModel->updatePaymentLink($orderId, [
            'payment_link_url'        => $paymentUrl,
            'payment_link_gateway'    => $gatewaySlug,
            'payment_link_method'     => $method,
            'payment_link_created_at' => date('Y-m-d H:i:s'),
        ]);

        // Marcar pedido como pendente de aprovação no Portal do Cliente
        $currentApproval = $order['customer_approval_status'] ?? null;
        if (empty($currentApproval) || $currentApproval === null) {
            $orderModel->setCustomerApprovalStatus($orderId, 'pendente');
        }
    }

    /**
     * Gera um link de checkout transparente (interno) em vez de link externo do gateway.
     *
     * @param int    $orderId       ID do pedido
     * @param int|null $installmentId ID da parcela (ou null para primeira pendente)
     * @param string $gatewaySlug   Slug do gateway (vazio = padrão)
     * @param array  $allowedMethods Métodos permitidos (vazio = todos do gateway)
     * @return array ['success' => bool, 'checkout_url' => string, ...]
     */
    public function generateCheckoutLink(int $orderId, ?int $installmentId = null, string $gatewaySlug = '', array $allowedMethods = []): array
    {
        $checkoutService = new \Akti\Services\CheckoutService($this->db);

        $params = [
            'order_id'        => $orderId,
            'installment_id'  => $installmentId,
            'gateway_slug'    => $gatewaySlug ?: null,
            'allowed_methods' => !empty($allowedMethods) ? $allowedMethods : null,
            'expires_in_hours' => 48,
            'created_by'      => $_SESSION['user_id'] ?? null,
        ];

        $result = $checkoutService->generateToken($params);

        if ($result['success'] && !empty($result['checkout_url'])) {
            // Persistir na tabela orders
            $orderModel = new \Akti\Models\Order($this->db);
            $order = $orderModel->readOne($orderId);

            if ($order) {
                $this->persistPaymentLink(
                    $orderModel,
                    $orderId,
                    $order,
                    $result['checkout_url'],
                    $gatewaySlug ?: 'checkout',
                    'checkout_transparente'
                );
            }
        }

        return $result;
    }
}
