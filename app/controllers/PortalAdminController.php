<?php
namespace Akti\Controllers;

use Akti\Models\PortalAccess;
use Akti\Models\PortalMessage;
use Akti\Models\Customer;
use Akti\Models\CompanySettings;
use Akti\Core\EventDispatcher;
use Akti\Core\Event;
use Akti\Core\Security;
use Akti\Services\PortalAdminService;
use Akti\Utils\Input;
use PDO;

/**
 * Controller: PortalAdminController
 * Administração do Portal do Cliente (acessível pelo painel admin).
 * Gerencia acessos de clientes, configurações e métricas.
 *
 * Rotas: ?page=portal_admin&action=<action>
 *
 * @package Akti\Controllers
 */
class PortalAdminController
{
    private PDO $db;
    private PortalAccess $portalAccess;
    private PortalAdminService $service;

    public function __construct()
    {
        $this->db = (new \Database())->getConnection();
        $this->portalAccess = new PortalAccess($this->db);
        $this->service = new PortalAdminService($this->db);
    }

    // ══════════════════════════════════════════════
    // LISTAGEM DE ACESSOS
    // ══════════════════════════════════════════════

    /**
     * Listagem de acessos ao portal com dados do cliente.
     * GET: ?page=portal_admin&action=index
     */
    public function index(): void
    {
        $search = Input::get('q') ?: '';
        $filter = Input::get('filter') ?: 'all';

        $accesses = $this->service->getFilteredAccesses($search, $filter);
        $metrics = $this->service->getPortalMetrics();
        $pendingMessages = $this->service->countPendingMessages();

        require 'app/views/layout/header.php';
        require 'app/views/portal_admin/index.php';
        require 'app/views/layout/footer.php';
    }

    // ══════════════════════════════════════════════
    // CRIAR ACESSO
    // ══════════════════════════════════════════════

    /**
     * Exibe formulário de criação de acesso ao portal.
     * GET: ?page=portal_admin&action=create
     */
    public function create(): void
    {
        $customers = $this->service->getCustomersWithoutAccess();
        $error = '';
        $success = '';

        require 'app/views/layout/header.php';
        require 'app/views/portal_admin/create.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * Processa criação de acesso ao portal (POST).
     * POST: ?page=portal_admin&action=store
     */
    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=portal_admin');
            exit;
        }

        $customerId = (int) Input::post('customer_id');
        $email      = Input::post('email', 'email');
        $password   = Input::post('password');
        $sendLink   = Input::post('send_magic_link') === '1';

        $error = '';

        if ($customerId <= 0 || empty($email)) {
            $error = 'Cliente e e-mail são obrigatórios.';
        } elseif ($this->portalAccess->emailExists($email)) {
            $error = 'Este e-mail já está cadastrado no portal.';
        } elseif ($this->portalAccess->customerHasAccess($customerId)) {
            $error = 'Este cliente já possui acesso ao portal.';
        }

        if ($error) {
            $customers = $this->service->getCustomersWithoutAccess();
            $success = '';
            require 'app/views/layout/header.php';
            require 'app/views/portal_admin/create.php';
            require 'app/views/layout/footer.php';
            return;
        }

        $accessId = $this->portalAccess->create([
            'customer_id' => $customerId,
            'email'       => $email,
            'password'    => $password ?: null,
            'lang'        => 'pt-br',
        ]);

        if ($sendLink && $accessId) {
            $token = $this->portalAccess->generateMagicToken($accessId);
            // TODO: Enviar e-mail com magic link
        }

        EventDispatcher::dispatch('portal.admin.access_created', new Event('portal.admin.access_created', [
            'access_id'   => $accessId,
            'customer_id' => $customerId,
            'email'       => $email,
            'created_by'  => $_SESSION['user_id'] ?? 0,
        ]));

        header('Location: ?page=portal_admin&success=created');
        exit;
    }

    // ══════════════════════════════════════════════
    // EDITAR ACESSO
    // ══════════════════════════════════════════════

    /**
     * Exibe formulário de edição de acesso ao portal.
     * GET: ?page=portal_admin&action=edit&id=X
     */
    public function edit(): void
    {
        $accessId = (int) Input::get('id');
        $access = $this->portalAccess->findById($accessId);

        if (!$access) {
            header('Location: ?page=portal_admin');
            exit;
        }

        $customer = (new Customer($this->db))->readOne($access['customer_id']);
        $error = '';
        $success = '';

        if (!empty($_GET['updated'])) {
            $success = 'Acesso atualizado com sucesso!';
        }

        require 'app/views/layout/header.php';
        require 'app/views/portal_admin/edit.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * Processa atualização de acesso ao portal (POST).
     * POST: ?page=portal_admin&action=update
     */
    public function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=portal_admin');
            exit;
        }

        $accessId  = (int) Input::post('id');
        $isActive  = Input::post('is_active') === '1' ? 1 : 0;
        $password  = Input::post('password');
        $lang      = Input::post('lang') ?: 'pt-br';

        $data = ['is_active' => $isActive, 'lang' => $lang];

        if (!empty($password)) {
            $data['password'] = $password;
        }

        $this->portalAccess->update($accessId, $data);

        EventDispatcher::dispatch('portal.admin.access_updated', new Event('portal.admin.access_updated', [
            'access_id'  => $accessId,
            'updated_by' => $_SESSION['user_id'] ?? 0,
            'changes'    => array_keys($data),
        ]));

        header('Location: ?page=portal_admin&action=edit&id=' . $accessId . '&updated=1');
        exit;
    }

    // ══════════════════════════════════════════════
    // AÇÕES RÁPIDAS
    // ══════════════════════════════════════════════

    /**
     * Ativar/desativar acesso (toggle). POST AJAX.
     * POST: ?page=portal_admin&action=toggleAccess
     */
    public function toggleAccess(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(false, 'Método inválido.');
        }

        $accessId = (int) Input::post('id');
        $access = $this->portalAccess->findById($accessId);

        if (!$access) {
            $this->jsonResponse(false, 'Acesso não encontrado.');
        }

        $newStatus = $access['is_active'] ? 0 : 1;
        $this->portalAccess->update($accessId, ['is_active' => $newStatus]);

        EventDispatcher::dispatch('portal.admin.access_toggled', new Event('portal.admin.access_toggled', [
            'access_id'  => $accessId,
            'is_active'  => $newStatus,
            'toggled_by' => $_SESSION['user_id'] ?? 0,
        ]));

        $this->jsonResponse(true, $newStatus ? 'Acesso ativado.' : 'Acesso desativado.', [
            'is_active' => $newStatus,
        ]);
    }

    /**
     * Resetar senha de um acesso. POST AJAX.
     * POST: ?page=portal_admin&action=resetPassword
     */
    public function resetPassword(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(false, 'Método inválido.');
        }

        $accessId = (int) Input::post('id');
        $access = $this->portalAccess->findById($accessId);

        if (!$access) {
            $this->jsonResponse(false, 'Acesso não encontrado.');
        }

        // Gerar nova senha temporária
        $tempPassword = $this->service->generateTempPassword();
        $this->portalAccess->update($accessId, ['password' => $tempPassword]);
        $this->portalAccess->setMustChangePassword($accessId, true);

        EventDispatcher::dispatch('portal.admin.password_reset', new Event('portal.admin.password_reset', [
            'access_id'  => $accessId,
            'reset_by'   => $_SESSION['user_id'] ?? 0,
        ]));

        $this->jsonResponse(true, 'Senha resetada com sucesso.', [
            'temp_password' => $tempPassword,
        ]);
    }

    /**
     * Enviar magic link para o cliente. POST AJAX.
     * POST: ?page=portal_admin&action=sendMagicLink
     */
    public function sendMagicLink(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(false, 'Método inválido.');
        }

        $accessId = (int) Input::post('id');
        $access = $this->portalAccess->findById($accessId);

        if (!$access) {
            $this->jsonResponse(false, 'Acesso não encontrado.');
        }

        $token = $this->portalAccess->generateMagicToken($accessId);

        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $magicLink = "{$protocol}://{$host}/?page=portal&action=loginMagic&token={$token}";

        // TODO: Enviar e-mail com o link mágico
        // mail($access['email'], 'Seu link de acesso ao portal', $magicLink);

        EventDispatcher::dispatch('portal.admin.magic_link_sent', new Event('portal.admin.magic_link_sent', [
            'access_id'   => $accessId,
            'email'       => $access['email'],
            'sent_by'     => $_SESSION['user_id'] ?? 0,
        ]));

        $this->jsonResponse(true, 'Link mágico gerado.', [
            'magic_link' => $magicLink,
        ]);
    }

    /**
     * Excluir acesso ao portal. POST.
     * POST: ?page=portal_admin&action=delete
     */
    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=portal_admin');
            exit;
        }

        $accessId = (int) Input::post('id');
        $access = $this->portalAccess->findById($accessId);

        if ($access) {
            // Remover sessões ativas
            $this->service->removeActiveSessions($accessId);

            $this->portalAccess->delete($accessId);

            EventDispatcher::dispatch('portal.admin.access_deleted', new Event('portal.admin.access_deleted', [
                'access_id'   => $accessId,
                'customer_id' => $access['customer_id'],
                'deleted_by'  => $_SESSION['user_id'] ?? 0,
            ]));
        }

        header('Location: ?page=portal_admin&success=deleted');
        exit;
    }

    /**
     * Forçar logout de todas as sessões de um acesso. POST AJAX.
     * POST: ?page=portal_admin&action=forceLogout
     */
    public function forceLogout(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(false, 'Método inválido.');
        }

        $accessId = (int) Input::post('id');
        $removed = $this->service->removeActiveSessions($accessId);

        $this->jsonResponse(true, "Sessões encerradas ({$removed}).", [
            'sessions_removed' => $removed,
        ]);
    }

    // ══════════════════════════════════════════════
    // CONFIGURAÇÕES DO PORTAL
    // ══════════════════════════════════════════════

    /**
     * Exibe tela de configurações do portal.
     * GET: ?page=portal_admin&action=config
     */
    public function config(): void
    {
        $config = $this->portalAccess->getAllConfig();
        $success = '';

        if (!empty($_GET['saved'])) {
            $success = 'Configurações salvas com sucesso!';
        }

        require 'app/views/layout/header.php';
        require 'app/views/portal_admin/config.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * Salva configurações do portal (POST).
     * POST: ?page=portal_admin&action=saveConfig
     */
    public function saveConfig(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=portal_admin&action=config');
            exit;
        }

        // Configs booleanas (checkbox: se não enviado = 0)
        $booleanKeys = [
            'portal_enabled', 'require_password', 'allow_self_register',
            'allow_order_approval', 'allow_new_order', 'allow_messages',
            'allow_documents', 'allow_tracking', 'allow_financial',
        ];

        foreach ($booleanKeys as $key) {
            $val = Input::post($key) === '1' ? '1' : '0';
            $this->portalAccess->setConfig($key, $val);
        }

        // Configs de texto/número
        $textKeys = [
            'magic_link_expiry_hours' => 'int',
            'session_timeout_minutes' => 'int',
            'new_order_notes'         => 'text',
        ];

        foreach ($textKeys as $key => $type) {
            $val = Input::post($key);
            if ($val !== null) {
                if ($type === 'int') {
                    $val = (string) max(1, (int) $val);
                }
                $this->portalAccess->setConfig($key, $val);
            }
        }

        EventDispatcher::dispatch('portal.admin.config_saved', new Event('portal.admin.config_saved', [
            'saved_by' => $_SESSION['user_id'] ?? 0,
        ]));

        header('Location: ?page=portal_admin&action=config&saved=1');
        exit;
    }

    // ══════════════════════════════════════════════
    // MÉTRICAS / DASHBOARD DO PORTAL
    // ══════════════════════════════════════════════

    /**
     * Retorna métricas do portal (AJAX).
     * GET: ?page=portal_admin&action=metrics
     */
    public function metrics(): void
    {
        $metrics = $this->service->getPortalMetrics();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'metrics' => $metrics]);
        exit;
    }

    // ══════════════════════════════════════════════
    // MÉTODOS PRIVADOS
    // ══════════════════════════════════════════════

    /**
     * Resposta JSON.
     */
    private function jsonResponse(bool $success, string $message, array $data = []): void
    {
        header('Content-Type: application/json');
        echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
        exit;
    }
}
