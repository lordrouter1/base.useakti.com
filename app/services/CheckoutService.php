<?php

namespace Akti\Services;

use Akti\Models\CheckoutToken;
use Akti\Models\Order;
use Akti\Models\Installment;
use Akti\Models\PaymentGateway;
use Akti\Models\CompanySettings;
use Akti\Gateways\GatewayManager;
use Akti\Config\TenantManager;
use Akti\Services\Contracts\CheckoutServiceInterface;
use PDO;

/**
 * Class CheckoutService.
 */
class CheckoutService implements CheckoutServiceInterface
{
    private PDO $db;

    /**
     * Construtor da classe CheckoutService.
     *
     * @param PDO $db Conexão PDO com o banco de dados
     */
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Gera um token de checkout transparente.
     *
     * @param array $params {order_id, installment_id?, gateway_slug?, allowed_methods?, expires_in_hours?, created_by?}
     * @return array {success, token?, checkout_url?, expires_at?, message?}
     */
    public function generateToken(array $params): array
    {
        $orderId = (int) ($params['order_id'] ?? 0);
        $installmentId = !empty($params['installment_id']) ? (int) $params['installment_id'] : null;
        $gatewaySlug = $params['gateway_slug'] ?? null;
        $allowedMethods = $params['allowed_methods'] ?? null;
        $expiresInHours = (int) ($params['expires_in_hours'] ?? 48);
        $createdBy = !empty($params['created_by']) ? (int) $params['created_by'] : null;

        $orderModel = new Order($this->db);
        $order = $orderModel->readOne($orderId);
        if (!$order) {
            return ['success' => false, 'message' => 'Pedido não encontrado.'];
        }

        // Calcular valor
        $amount = 0;
        if ($installmentId) {
            $installmentModel = new Installment($this->db);
            $installment = $installmentModel->getById($installmentId);
            if (!$installment || (int) $installment['order_id'] !== $orderId) {
                return ['success' => false, 'message' => 'Parcela não encontrada ou não pertence ao pedido.'];
            }
            $amount = (float) $installment['amount'];
        } else {
            // Buscar a parcela pendente com installment_number > 0 (exclui entrada/sinal)
            $installmentModel = new Installment($this->db);
            $existingInstallments = $installmentModel->getByOrderId($orderId);
            $targetInstallment = null;
            foreach ($existingInstallments as $inst) {
                if ((int) $inst['installment_number'] > 0 && in_array($inst['status'], ['pendente', 'atrasado'], true)) {
                    $targetInstallment = $inst;
                    break;
                }
            }

            if ($targetInstallment) {
                // Usar o valor da parcela pendente (já desconta entrada)
                $amount = (float) $targetInstallment['amount'];
                $installmentId = (int) $targetInstallment['id'];
            } else {
                // Fallback: usar total do pedido menos entrada
                $totalAmount = (float) ($order['total_amount'] ?? $order['total'] ?? $order['final_total'] ?? 0);
                $downPayment = (float) ($order['down_payment'] ?? 0);
                $discount = (float) ($order['discount'] ?? 0);
                $amount = $totalAmount - $downPayment - $discount;
                if ($amount <= 0) {
                    $amount = $totalAmount - $discount;
                }
            }
        }

        if ($amount <= 0) {
            return ['success' => false, 'message' => 'Valor inválido para checkout.'];
        }

        // Buscar dados do cliente
        $customerName = $order['customer_name'] ?? null;
        $customerEmail = $order['customer_email'] ?? null;
        $customerDocument = $order['customer_document'] ?? null;

        // Se não veio no order join, buscar diretamente
        if (empty($customerName) && !empty($order['customer_id'])) {
            $stmt = $this->db->prepare("SELECT name, email, document FROM customers WHERE id = :id");
            $stmt->execute([':id' => $order['customer_id']]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($customer) {
                $customerName = $customer['name'];
                $customerEmail = $customer['email'];
                $customerDocument = $customer['document'];
            }
        }

        // Gerar token criptográfico
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + ($expiresInHours * 3600));

        $tenantId = !empty($_SESSION['tenant']['id']) ? (int) $_SESSION['tenant']['id'] : null;

        $tokenModel = new CheckoutToken($this->db);
        $tokenId = $tokenModel->create([
            'token'             => $token,
            'order_id'          => $orderId,
            'installment_id'    => $installmentId,
            'gateway_slug'      => $gatewaySlug,
            'amount'            => $amount,
            'currency'          => 'BRL',
            'allowed_methods'   => $allowedMethods,
            'customer_name'     => $customerName,
            'customer_email'    => $customerEmail,
            'customer_document' => $customerDocument,
            'expires_at'        => $expiresAt,
            'created_by'        => $createdBy,
            'tenant_id'         => $tenantId,
        ]);

        // Montar URL base
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $baseUrl = $protocol . '://' . $host;
        $checkoutUrl = $baseUrl . '/?page=checkout&token=' . $token;

        // Vincular token ao pedido
        $stmt = $this->db->prepare("UPDATE orders SET checkout_token_id = :tid WHERE id = :oid");
        $stmt->execute([':tid' => $tokenId, ':oid' => $orderId]);

        return [
            'success'      => true,
            'token'        => $token,
            'token_id'     => $tokenId,
            'checkout_url' => $checkoutUrl,
            'expires_at'   => $expiresAt,
            'amount'       => $amount,
        ];
    }

    /**
     * Processa pagamento vindo do checkout transparente.
     *
     * @param string $token Hash do token
     * @param array  $paymentData {method, card_token?, customer_document?, customer_name?, customer_email?}
     * @return array Resultado padronizado
     */
    public function processCheckout(string $token, array $paymentData): array
    {
        $tokenModel = new CheckoutToken($this->db);
        $tokenRow = $tokenModel->findByToken($token);

        if (!$tokenRow) {
            return ['success' => false, 'error' => 'Token inválido.', 'code' => 'invalid_token'];
        }
        if ($tokenRow['status'] !== 'active') {
            return ['success' => false, 'error' => 'Token já utilizado ou expirado.', 'code' => 'token_not_active'];
        }
        if (strtotime($tokenRow['expires_at']) < time()) {
            $tokenModel->markExpired((int) $tokenRow['id']);
            return ['success' => false, 'error' => 'Token expirado.', 'code' => 'token_expired'];
        }

        $method = $paymentData['method'] ?? '';
        if (!in_array($method, ['pix', 'credit_card', 'boleto'], true)) {
            return ['success' => false, 'error' => 'Método de pagamento inválido.', 'code' => 'invalid_method'];
        }

        // Verificar allowed_methods
        if (!empty($tokenRow['allowed_methods'])) {
            $allowed = json_decode($tokenRow['allowed_methods'], true);
            if (is_array($allowed) && !in_array($method, $allowed, true)) {
                return ['success' => false, 'error' => 'Método de pagamento não permitido para este checkout.', 'code' => 'method_not_allowed'];
            }
        }

        // Resolver gateway
        $gwModel = new PaymentGateway($this->db);
        if (!empty($tokenRow['gateway_slug'])) {
            $gatewayRow = $gwModel->readBySlug($tokenRow['gateway_slug']);
        } else {
            $gatewayRow = $gwModel->getDefault();
        }

        if (!$gatewayRow || !$gatewayRow['is_active']) {
            return ['success' => false, 'error' => 'Gateway de pagamento não disponível.', 'code' => 'gateway_unavailable'];
        }

        $gateway = GatewayManager::resolveFromRow($gatewayRow);

        if (!$gateway->supports($method)) {
            return ['success' => false, 'error' => 'Gateway não suporta este método.', 'code' => 'method_unsupported'];
        }

        // Montar charge data
        $chargeData = [
            'amount'          => (float) $tokenRow['amount'],
            'currency'        => $tokenRow['currency'] ?? 'BRL',
            'method'          => $method,
            'description'     => 'Pedido #' . $tokenRow['order_id'],
            'order_id'        => (int) $tokenRow['order_id'],
            'installment_id'  => $tokenRow['installment_id'] ? (int) $tokenRow['installment_id'] : null,
            'customer'        => [
                'name'     => $paymentData['customer_name'] ?? $tokenRow['customer_name'] ?? '',
                'email'    => $paymentData['customer_email'] ?? $tokenRow['customer_email'] ?? '',
                'document' => $paymentData['customer_document'] ?? $tokenRow['customer_document'] ?? '',
            ],
            'metadata' => [
                'checkout_token_id' => $tokenRow['id'],
                'source'            => 'transparent_checkout',
            ],
        ];

        // Enriquecer customer com endereço do cadastro (necessário para boleto)
        $orderId = (int) $tokenRow['order_id'];
        $customerAddress = $this->getCustomerAddressFromOrder($orderId);
        if ($customerAddress) {
            $chargeData['customer'] = array_merge($chargeData['customer'], $customerAddress);
        }

        // Card token para cartão de crédito
        if ($method === 'credit_card' && !empty($paymentData['card_token'])) {
            $chargeData['card_token'] = $paymentData['card_token'];
        }

        // Idempotency key
        $chargeData['idempotency_key'] = 'chk_' . $tokenRow['id'] . '_' . substr(md5($token . $method . time()), 0, 8);

        // Return URL para redirect-based 3DS (Stripe)
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $chargeData['return_url'] = $protocol . '://' . $host . '/?page=checkout&action=confirmation&token=' . urlencode($token) . '&status=pending';

        // Injetar webhook URL (resolução por subdomínio)
        $webhookUrl = $this->buildWebhookUrl($gatewayRow['gateway_slug']);
        if ($webhookUrl) {
            $chargeData['notification_url'] = $webhookUrl;
            $chargeData['webhook_url'] = $webhookUrl;
        }

        // Processar cobrança
        try {
            $result = $gateway->createCharge($chargeData);
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error'   => 'Erro ao processar pagamento.',
                'code'    => 'gateway_error',
            ];
        }

        // Se o gateway retornou erro, propagar para o frontend
        if (empty($result['success'])) {
            return [
                'success' => false,
                'error'   => $result['error'] ?? 'Erro ao processar pagamento no gateway.',
                'code'    => 'gateway_rejected',
            ];
        }

        // Logar transação
        $gwModel->logTransaction([
            'gateway_slug'        => $gatewayRow['gateway_slug'],
            'order_id'            => $tokenRow['order_id'],
            'installment_id'      => $tokenRow['installment_id'],
            'external_id'         => $result['external_id'] ?? null,
            'external_status'     => $result['status'] ?? 'pending',
            'amount'              => $tokenRow['amount'],
            'payment_method_type' => $method,
            'raw_payload'         => [
                'request'  => $chargeData,
                'response' => $result['raw'] ?? [],
            ],
            'event_type'          => 'charge.created',
        ]);

        // Se pagamento com sucesso imediato (cartão aprovado)
        $resultStatus = $result['status'] ?? '';
        $isImmediateSuccess = in_array($resultStatus, ['succeeded', 'approved'], true);

        if ($isImmediateSuccess) {
            $tokenModel->markUsed(
                (int) $tokenRow['id'],
                $method,
                $result['external_id'] ?? ''
            );

            // Criar parcela única (se não existir) e marcar como paga
            $this->markInstallmentPaidFromCheckout(
                (int) $tokenRow['order_id'],
                $tokenRow['installment_id'] ? (int) $tokenRow['installment_id'] : null,
                (float) $tokenRow['amount'],
                $method,
                $result['external_id'] ?? null
            );
        }

        return [
            'success'     => true,
            'status'      => $result['status'] ?? 'pending',
            'external_id' => $result['external_id'] ?? null,
            'method'      => $method,
            'qr_code'     => $result['qr_code'] ?? null,
            'qr_code_base64' => $result['qr_code_base64'] ?? null,
            'qr_code_image_url' => $result['qr_code_image_url'] ?? null,
            'payment_url' => $result['payment_url'] ?? null,
            'boleto_url'  => $result['boleto_url'] ?? null,
            'boleto_barcode' => $result['boleto_barcode'] ?? null,
            'expires_at'  => $result['expires_at'] ?? null,
            'expires_in_seconds' => $this->computeExpiresInSeconds($result['expires_at'] ?? null),
            'client_secret' => $result['client_secret'] ?? null,
        ];
    }

    /**
     * Cancela um token de checkout.
     */
    public function cancelToken(int $tokenId): bool
    {
        $tokenModel = new CheckoutToken($this->db);
        return $tokenModel->cancel($tokenId);
    }

    /**
     * Garante que existe uma parcela para o pedido e marca como paga.
     * Chamado após confirmação de pagamento (imediato ou via polling).
     *
     * @param int      $orderId
     * @param int|null $installmentId  ID da parcela já existente (do token)
     * @param float    $amount         Valor pago
     * @param string   $paymentMethod  Método (credit_card, pix, boleto)
     * @param string|null $externalId  ID externo do gateway
     */
    public function markInstallmentPaidFromCheckout(
        int $orderId,
        ?int $installmentId,
        float $amount,
        string $paymentMethod,
        ?string $externalId = null
    ): void {
        $installmentModel = new Installment($this->db);

        // Se não tem installment_id, verificar se já existem parcelas para o pedido
        if (!$installmentId) {
            $existing = $installmentModel->getByOrderId($orderId);
            if (!empty($existing)) {
                // Usar a primeira parcela pendente com installment_number > 0 (pular entrada/sinal)
                foreach ($existing as $inst) {
                    if ((int) $inst['installment_number'] > 0 && in_array($inst['status'], ['pendente', 'atrasado'], true)) {
                        $installmentId = (int) $inst['id'];
                        break;
                    }
                }
            }

            // Se ainda não tem parcela, criar uma parcela única
            if (!$installmentId) {
                $installmentModel->generate($orderId, $amount, 1, 0, date('Y-m-d'));
                $created = $installmentModel->getByOrderId($orderId);
                if (!empty($created)) {
                    // Pegar a parcela com installment_number > 0
                    foreach ($created as $inst) {
                        if ((int) $inst['installment_number'] > 0) {
                            $installmentId = (int) $inst['id'];
                            break;
                        }
                    }
                }
            }
        }

        if (!$installmentId) {
            return;
        }

        // Marcar parcela como paga com auto-confirmação
        $installmentModel->pay($installmentId, [
            'paid_date'      => date('Y-m-d'),
            'paid_amount'    => $amount,
            'payment_method' => $paymentMethod,
            'notes'          => 'Pago via checkout transparente' . ($externalId ? " (ID: {$externalId})" : ''),
        ], true);

        // Atualizar status de pagamento do pedido
        $installmentModel->updateOrderPaymentStatus($orderId);
    }

    /**
     * Expira todos os tokens vencidos.
     * @return int Quantidade expirada
     */
    public function expireOldTokens(): int
    {
        $tokenModel = new CheckoutToken($this->db);
        return $tokenModel->expireAll();
    }

    /**
     * Busca token completo pelo hash.
     */
    public function getTokenByToken(string $token): ?array
    {
        $tokenModel = new CheckoutToken($this->db);
        return $tokenModel->findByToken($token);
    }

    /**
     * Busca endereço do cliente a partir do pedido.
     */
    private function getCustomerAddressFromOrder(int $orderId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT c.zipcode, c.address_street, c.address_number,
                    c.address_neighborhood, c.address_city, c.address_state,
                    c.phone, c.cellphone
             FROM orders o
             INNER JOIN customers c ON c.id = o.customer_id
             WHERE o.id = :oid
             LIMIT 1"
        );
        $stmt->execute([':oid' => $orderId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return [
            'zip'          => $row['zipcode'] ?? '',
            'street'       => $row['address_street'] ?? '',
            'number'       => $row['address_number'] ?? '',
            'neighborhood' => $row['address_neighborhood'] ?? '',
            'city'         => $row['address_city'] ?? '',
            'state'        => $row['address_state'] ?? '',
            'phone'        => $row['cellphone'] ?? $row['phone'] ?? '',
        ];
    }

    /**
     * Calcula segundos até expiração a partir de uma data ISO 8601 ou Unix timestamp.
     */
    private function computeExpiresInSeconds($expiresAt): ?int
    {
        if ($expiresAt === null || $expiresAt === '') {
            return null;
        }
        $ts = is_numeric($expiresAt) ? (int) $expiresAt : strtotime($expiresAt);
        if ($ts === false || $ts <= 0) {
            return null;
        }
        return max(0, $ts - time());
    }

    /**
     * Monta a webhook URL baseada no subdomínio do tenant.
     * Retorna null se não for possível determinar.
     */
    private function buildWebhookUrl(string $gatewaySlug): ?string
    {
        $subdomain = $_SESSION['tenant']['key'] ?? null;

        // Se não tiver subdomínio na sessão, tentar extrair do host
        if (empty($subdomain) || $subdomain === 'localhost') {
            $host = $_SERVER['HTTP_HOST'] ?? '';
            $parts = explode('.', $host);
            if (count($parts) >= 3) {
                $subdomain = $parts[0];
            }
        }

        if (empty($subdomain) || $subdomain === 'localhost' || $subdomain === 'www') {
            return null;
        }

        // Determinar domínio base
        $baseDomain = akti_env('AKTI_BASE_DOMAIN') ?: 'useakti.com';
        $url = "https://{$subdomain}.{$baseDomain}/?page=webhook&action=handle&gateway=" . urlencode($gatewaySlug);

        // Validações de segurança
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }
        if (preg_match('#[/\\\\]\.\.#', $url)) {
            return null;
        }

        return $url;
    }
}
