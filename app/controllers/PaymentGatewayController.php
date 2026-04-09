<?php
namespace Akti\Controllers;

use Akti\Models\PaymentGateway;
use Akti\Models\Financial;
use Akti\Gateways\GatewayManager;
use Akti\Core\ModuleBootloader;
use Akti\Core\Log;
use Akti\Utils\Input;
use Akti\Utils\Sanitizer;
use PDO;
use TenantManager;

/**
 * Controller: PaymentGatewayController
 * Gerencia configuração de gateways, criação de cobranças e consultas.
 * As webhooks são processadas pela API Node.js (ver api/src/routes/webhookRoutes.js).
 *
 * @package Akti\Controllers
 */
class PaymentGatewayController
{
    private \PDO $db;
    private PaymentGateway $gatewayModel;

    public function __construct(\PDO $db, PaymentGateway $gatewayModel)
    {
        $this->db = $db;
        $this->gatewayModel = $gatewayModel;
    }

    // ══════════════════════════════════════════════════════════════
    // CONFIGURAÇÃO (Settings)
    // ══════════════════════════════════════════════════════════════

    /**
     * Lista gateways configurados (aba em settings ou page separada).
     */
    public function index()
    {
        $gateways = $this->gatewayModel->readAll();
        $availableGateways = GatewayManager::getAvailableGateways();
        $recentTransactions = $this->gatewayModel->getRecentTransactions(20);

        require 'app/views/layout/header.php';
        require 'app/views/gateways/index.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * Editar configuração de um gateway específico.
     */
    public function edit()
    {
        $id = Input::get('id', 'int', 0);
        $gateway = $this->gatewayModel->readOne($id);

        if (!$gateway) {
            $_SESSION['flash_error'] = 'Gateway não encontrado.';
            header('Location: ?page=payment_gateways');
            exit;
        }

        // Obter campos do gateway via GatewayManager
        $gatewayInstance = GatewayManager::make($gateway['gateway_slug']);
        $credentialFields = $gatewayInstance->getCredentialFields();
        $settingsFields = $gatewayInstance->getSettingsFields();

        // Decodificar credenciais e settings atuais
        $currentCredentials = json_decode($gateway['credentials'] ?? '{}', true) ?: [];
        $currentSettings = json_decode($gateway['settings_json'] ?? '{}', true) ?: [];

        // Gerar URL de webhook do Node.js
        $tenantDb = $_SESSION['tenant']['db_name'] ?? '';
        $apiBaseUrl = $this->getApiBaseUrl();
        $webhookUrl = $apiBaseUrl . '/api/webhooks/' . $gateway['gateway_slug'] . '?tenant=' . urlencode($tenantDb);

        require 'app/views/layout/header.php';
        require 'app/views/gateways/edit.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * Salvar configuração de um gateway (POST).
     */
    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=payment_gateways');
            exit;
        }

        $id = Input::post('gateway_id', 'int', 0);
        $gateway = $this->gatewayModel->readOne($id);

        if (!$gateway) {
            $_SESSION['flash_error'] = 'Gateway não encontrado.';
            header('Location: ?page=payment_gateways');
            exit;
        }

        // Atualizar dados gerais
        $data = [
            'is_active'    => Input::post('is_active', 'int', 0),
            'is_default'   => Input::post('is_default', 'int', 0),
            'environment'  => Input::post('environment', 'enum', 'sandbox', ['sandbox', 'production']),
            'webhook_secret' => Input::post('webhook_secret') ?: null,
        ];

        $this->gatewayModel->update($id, $data);

        // Atualizar credenciais (usar postRaw para preservar tokens/keys sem sanitização)
        $gatewayInstance = GatewayManager::make($gateway['gateway_slug']);
        $credentialFields = $gatewayInstance->getCredentialFields();
        $credentials = [];
        foreach ($credentialFields as $field) {
            $value = trim(Input::postRaw('credential_' . $field['key'], ''));
            // Se o campo está vazio e é do tipo password, manter o valor anterior
            if (empty($value) && $field['type'] === 'password') {
                $old = json_decode($gateway['credentials'] ?? '{}', true) ?: [];
                $value = $old[$field['key']] ?? '';
            }
            $credentials[$field['key']] = $value;
        }
        $this->gatewayModel->updateCredentials($id, $credentials);

        // Atualizar settings
        $settingsFields = $gatewayInstance->getSettingsFields();
        $settings = [];
        foreach ($settingsFields as $field) {
            if ($field['type'] === 'readonly') {
                continue;
            }
            $settings[$field['key']] = Input::post('setting_' . $field['key']) ?? ($field['default'] ?? '');
        }

        // Gerar webhook URL automaticamente
        $tenantDb = $_SESSION['tenant']['db_name'] ?? '';
        $apiBaseUrl = $this->getApiBaseUrl();
        $settings['notification_url'] = $apiBaseUrl . '/api/webhooks/' . $gateway['gateway_slug'] . '?tenant=' . urlencode($tenantDb);

        $this->gatewayModel->updateSettings($id, $settings);

        // Se clicou "Salvar e Testar", redirecionar com flag para auto-teste
        $saveAndTest = Input::post('save_and_test', 'int', 0);

        $_SESSION['flash_success'] = "Gateway {$gateway['display_name']} atualizado com sucesso!";
        $redirectUrl = "?page=payment_gateways&action=edit&id={$id}";
        if ($saveAndTest) {
            $redirectUrl .= '&autotest=1';
        }
        header("Location: {$redirectUrl}");
        exit;
    }

    /**
     * Testar conexão com um gateway (AJAX POST).
     *
     * Aceita credenciais via POST para permitir teste ANTES de salvar.
     * Se as credenciais não vierem via POST, usa as salvas no banco.
     */
    public function testConnection()
    {
        header('Content-Type: application/json');

        // Aceitar tanto GET (com credenciais do banco) quanto POST (com credenciais do formulário)
        $id = Input::get('id', 'int', 0) ?: Input::post('gateway_id', 'int', 0);
        $gateway = $this->gatewayModel->readOne($id);

        if (!$gateway) {
            echo json_encode(['success' => false, 'message' => 'Gateway não encontrado.']);
            exit;
        }

        try {
            $gatewayInstance = GatewayManager::make($gateway['gateway_slug']);
            $credentialFields = $gatewayInstance->getCredentialFields();

            // Credenciais salvas no banco (base)
            $savedCredentials = json_decode($gateway['credentials'] ?? '{}', true) ?: [];

            $credentials = $savedCredentials;
            $hasPostCredentials = false;

            // Se é POST, tentar ler credenciais do formulário
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                foreach ($credentialFields as $field) {
                    // Usar postRaw para evitar sanitização que poderia alterar tokens
                    $value = Input::postRaw('credential_' . $field['key']);
                    if ($value !== null && $value !== '') {
                        $hasPostCredentials = true;
                        $credentials[$field['key']] = $value;
                    }
                }
            }

            // Verificar se há credenciais mínimas para testar
            $hasAnyCredential = false;
            foreach ($credentialFields as $field) {
                if (!empty($credentials[$field['key'] ?? ''])) {
                    $hasAnyCredential = true;
                    break;
                }
            }

            if (!$hasAnyCredential) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Nenhuma credencial configurada. Preencha e salve as credenciais primeiro, depois teste a conexão.',
                ]);
                exit;
            }

            $settings = json_decode($gateway['settings_json'] ?? '{}', true) ?: [];
            $environment = ($_SERVER['REQUEST_METHOD'] === 'POST')
                ? (Input::postRaw('environment') ?: ($gateway['environment'] ?? 'sandbox'))
                : ($gateway['environment'] ?? 'sandbox');

            $instance = GatewayManager::resolve(
                $gateway['gateway_slug'],
                $credentials,
                $settings,
                $environment
            );

            $result = $instance->testConnection();
            echo json_encode($result);
        } catch (\Exception $e) {
            Log::error('PaymentGatewayController: testConnection', ['exception' => $e->getMessage()]);
            echo json_encode(['success' => false, 'message' => 'Erro interno ao testar conexão. Tente novamente.']);
        }
        exit;
    }

    // ══════════════════════════════════════════════════════════════
    // COBRANÇAS
    // ══════════════════════════════════════════════════════════════

    /**
     * Criar cobrança via gateway (AJAX POST).
     * Recebe: installment_id, gateway_slug, method (pix, credit_card, boleto)
     */
    public function createCharge()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método inválido.']);
            exit;
        }

        $installmentId = Input::post('installment_id', 'int', 0);
        $gatewaySlug = Input::post('gateway_slug', 'string', '');
        $method = Input::post('method', 'string', 'pix');

        if (!$installmentId || !$gatewaySlug) {
            echo json_encode(['success' => false, 'message' => 'Dados insuficientes.']);
            exit;
        }

        // Buscar gateway
        $gatewayRow = $this->gatewayModel->readBySlug($gatewaySlug);
        if (!$gatewayRow || !$gatewayRow['is_active']) {
            echo json_encode(['success' => false, 'message' => 'Gateway inativo ou não encontrado.']);
            exit;
        }

        // Buscar parcela e pedido
        $q = "SELECT oi.*, o.id as order_id, c.name as customer_name, c.email as customer_email, c.document as customer_document
              FROM order_installments oi
              JOIN orders o ON oi.order_id = o.id
              LEFT JOIN customers c ON o.customer_id = c.id
              WHERE oi.id = :id";
        $s = $this->db->prepare($q);
        $s->execute([':id' => $installmentId]);
        $installment = $s->fetch(PDO::FETCH_ASSOC);

        if (!$installment) {
            echo json_encode(['success' => false, 'message' => 'Parcela não encontrada.']);
            exit;
        }

        try {
            $gateway = GatewayManager::resolveFromRow($gatewayRow);

            $chargeData = [
                'amount'         => (float) $installment['amount'],
                'description'    => "Pedido #{$installment['order_id']} - Parcela {$installment['installment_number']}",
                'method'         => $method,
                'installment_id' => $installmentId,
                'order_id'       => $installment['order_id'],
                'customer'       => [
                    'name'     => $installment['customer_name'] ?? '',
                    'email'    => $installment['customer_email'] ?? '',
                    'document' => $installment['customer_document'] ?? '',
                ],
                'metadata'       => [
                    'installment_id' => $installmentId,
                    'order_id'       => $installment['order_id'],
                ],
            ];

            $result = $gateway->createCharge($chargeData);

            // Logar a transação
            $this->gatewayModel->logTransaction([
                'gateway_slug'        => $gatewaySlug,
                'installment_id'      => $installmentId,
                'order_id'            => $installment['order_id'],
                'external_id'         => $result['external_id'] ?? null,
                'external_status'     => $result['status'] ?? null,
                'amount'              => (float) $installment['amount'],
                'payment_method_type' => $method,
                'raw_payload'         => $result['raw'] ?? null,
                'event_type'          => 'charge.created',
            ]);

            echo json_encode($result);
        } catch (\Exception $e) {
            Log::error('PaymentGatewayController: createCharge', ['exception' => $e->getMessage()]);
            echo json_encode(['success' => false, 'message' => 'Erro interno ao processar cobrança. Tente novamente.']);
        }
        exit;
    }

    /**
     * Consultar status de uma cobrança (AJAX GET).
     */
    public function chargeStatus()
    {
        header('Content-Type: application/json');

        $gatewaySlug = Input::get('gateway_slug', 'string', '');
        $externalId = Input::get('external_id', 'string', '');

        if (!$gatewaySlug || !$externalId) {
            echo json_encode(['success' => false, 'message' => 'Dados insuficientes.']);
            exit;
        }

        $gatewayRow = $this->gatewayModel->readBySlug($gatewaySlug);
        if (!$gatewayRow) {
            echo json_encode(['success' => false, 'message' => 'Gateway não encontrado.']);
            exit;
        }

        try {
            $gateway = GatewayManager::resolveFromRow($gatewayRow);
            $result = $gateway->getChargeStatus($externalId);
            echo json_encode($result);
        } catch (\Exception $e) {
            Log::error('PaymentGatewayController: getChargeStatus', ['exception' => $e->getMessage()]);
            echo json_encode(['success' => false, 'message' => 'Erro interno ao consultar cobrança. Tente novamente.']);
        }
        exit;
    }

    /**
     * Log de transações do gateway (listagem).
     */
    public function transactions()
    {
        $transactions = $this->gatewayModel->getRecentTransactions(100);

        require 'app/views/layout/header.php';
        require 'app/views/gateways/transactions.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * Gerar link de checkout transparente (AJAX POST).
     * Recebe: order_id, installment_id?, gateway_slug?, allowed_methods[]?
     */
    public function createCheckoutLink()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método inválido.']);
            exit;
        }

        $orderId = Input::post('order_id', 'int', 0);
        $installmentId = Input::post('installment_id', 'int', 0) ?: null;
        $gatewaySlug = Input::post('gateway_slug', 'string', '');
        $allowedMethods = $_POST['allowed_methods'] ?? [];

        if (!$orderId) {
            echo json_encode(['success' => false, 'message' => 'Pedido não informado.']);
            exit;
        }

        if (!is_array($allowedMethods)) {
            $allowedMethods = [];
        }
        // Sanitize allowed methods
        $validMethods = ['pix', 'credit_card', 'boleto'];
        $allowedMethods = array_values(array_intersect($allowedMethods, $validMethods));

        try {
            $service = new \Akti\Services\PipelinePaymentService($this->db);
            $result = $service->generateCheckoutLink($orderId, $installmentId, $gatewaySlug, $allowedMethods);
            echo json_encode($result);
        } catch (\Exception $e) {
            \Akti\Utils\Log::error('PaymentGatewayController: createCheckoutLink', ['exception' => $e->getMessage()]);
            echo json_encode(['success' => false, 'message' => 'Erro interno ao gerar link de checkout.']);
        }
        exit;
    }

    // ══════════════════════════════════════════════════════════════
    // Internals
    // ══════════════════════════════════════════════════════════════

    /**
     * Retorna a URL base da API Node.js para montar webhook URLs.
     */
    private function getApiBaseUrl(): string
    {
        // Detectar a URL base da API Node.js
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        // Em produção, a API Node.js roda no mesmo domínio (porta 3000 ou proxy)
        // Verificar se existe configuração customizada
        $configApiUrl = (new \Akti\Models\CompanySettings($this->db))->get('api_base_url', '');
        if (!empty($configApiUrl)) {
            return rtrim($configApiUrl, '/');
        }

        // Fallback: API no mesmo host, porta 3000
        $hostWithoutPort = explode(':', $host)[0];
        return "{$protocol}://{$hostWithoutPort}:3000";
    }
}
