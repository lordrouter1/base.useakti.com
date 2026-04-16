<?php

namespace Akti\Controllers;

use Akti\Models\WhatsAppMessage;
use Akti\Services\WhatsAppService;
use Akti\Utils\Input;

/**
 * Class WhatsAppController.
 */
class WhatsAppController extends BaseController
{
    private WhatsAppMessage $model;

    /**
     * Construtor da classe WhatsAppController.
     *
     * @param \PDO $db Conexão PDO com o banco de dados
     */
    public function __construct(\PDO $db)
    {
        parent::__construct($db);
        $this->model = new WhatsAppMessage($db);
    }

    /**
     * Exibe a página de listagem.
     */
    public function index()
    {
        $this->requireAuth();
        $tenantId = $this->getTenantId();
        $config = $this->model->getConfig($tenantId);
        $templates = $this->model->getTemplates($tenantId);
        $page = Input::get('p', 'int', 1);
        $messages = $this->model->getMessages($tenantId, $page, 20);
        $stats = $this->model->getDashboardStats($tenantId);

        require 'app/views/layout/header.php';
        require 'app/views/whatsapp/index.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * Salva dados.
     */
    public function saveConfig()
    {
        $this->requireAuth();
        $tenantId = $this->getTenantId();
        $data = [
            'tenant_id'       => $tenantId,
            'provider'        => Input::post('provider', 'string', ''),
            'api_url'         => Input::post('api_url', 'string', ''),
            'api_key'         => Input::post('api_key', 'string', ''),
            'instance_name'   => Input::post('instance_name', 'string', ''),
            'phone_number_id' => Input::post('phone_number_id', 'string', ''),
            'is_active'       => Input::post('is_active', 'int', 0),
        ];

        $this->model->saveConfig($data);
        $_SESSION['flash_success'] = 'Configuração salva com sucesso.';
        header('Location: ?page=whatsapp');
    }

    /**
     * Salva dados.
     */
    public function saveTemplate()
    {
        $this->requireAuth();
        $data = [
            'tenant_id'        => $this->getTenantId(),
            'name'             => Input::post('name', 'string', ''),
            'event_type'       => Input::post('event_type', 'string', ''),
            'message_template' => Input::post('message_template', 'string', ''),
            'is_active'        => Input::post('is_active', 'int', 1),
        ];

        $id = Input::post('id', 'int', 0);
        if ($id) {
            $data['id'] = $id;
        }

        $this->model->saveTemplate($data);
        $_SESSION['flash_success'] = 'Template salvo com sucesso.';
        header('Location: ?page=whatsapp');
    }

    /**
     * Envia dados ou notificação.
     */
    public function send()
    {
        $this->requireAuth();
        $tenantId = $this->getTenantId();
        $service = new WhatsAppService($this->model, $tenantId);

        $phone = Input::post('phone', 'string', '');
        $message = Input::post('message', 'string', '');
        $customerId = Input::post('customer_id', 'int', 0) ?: null;

        $result = $service->send($phone, $message, $customerId);

        if ($this->isAjax()) {
            $this->json($result);
            return;
        }

        if ($result['success']) {
            $_SESSION['flash_success'] = 'Mensagem enviada com sucesso.';
        } else {
            $_SESSION['flash_error'] = 'Falha no envio: ' . ($result['error'] ?? 'Erro desconhecido');
        }
        header('Location: ?page=whatsapp');
    }

    /**
     * Test connection.
     */
    public function testConnection()
    {
        $this->requireAuth();
        $tenantId = $this->getTenantId();
        $service = new WhatsAppService($this->model, $tenantId);

        if (!$service->isConfigured()) {
            $this->json(['success' => false, 'error' => 'WhatsApp não configurado.']);
            return;
        }

        $this->json(['success' => true, 'message' => 'Conexão ativa.']);
    }
}
