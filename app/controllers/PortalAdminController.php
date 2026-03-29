<?php
namespace Akti\Controllers;

use Akti\Models\PortalAccess;
use Akti\Models\PortalMessage;
use Akti\Models\Customer;
use Akti\Models\CompanySettings;
use Akti\Core\EventDispatcher;
use Akti\Core\Event;
use Akti\Core\Security;
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

    public function __construct()
    {
        $this->db = (new \Database())->getConnection();
        $this->portalAccess = new PortalAccess($this->db);
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

        // Buscar todos os acessos
        $accesses = $this->getFilteredAccesses($search, $filter);

        // Métricas
        $metrics = $this->getPortalMetrics();

        // Mensagens pendentes (enviadas pelo cliente, não lidas)
        $pendingMessages = $this->countPendingMessages();

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
        $customers = $this->getCustomersWithoutAccess();
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
            $customers = $this->getCustomersWithoutAccess();
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
        $tempPassword = $this->generateTempPassword();
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
            $this->removeActiveSessions($accessId);

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
        $removed = $this->removeActiveSessions($accessId);

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
        $metrics = $this->getPortalMetrics();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'metrics' => $metrics]);
        exit;
    }

    // ══════════════════════════════════════════════
    // MÉTODOS PRIVADOS
    // ══════════════════════════════════════════════

    /**
     * Busca acessos filtrados por pesquisa e status.
     */
    private function getFilteredAccesses(string $search, string $filter): array
    {
        $where = '1=1';
        $params = [];

        if (!empty($search)) {
            $where .= " AND (pa.email LIKE :search OR c.name LIKE :search OR c.phone LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        switch ($filter) {
            case 'active':
                $where .= " AND pa.is_active = 1";
                break;
            case 'inactive':
                $where .= " AND pa.is_active = 0";
                break;
            case 'locked':
                $where .= " AND pa.locked_until > NOW()";
                break;
            case 'recent':
                $where .= " AND pa.last_login_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
        }

        $sql = "SELECT pa.*, c.name AS customer_name, c.phone AS customer_phone,
                       c.email AS customer_email_main
                FROM customer_portal_access pa
                JOIN customers c ON c.id = pa.customer_id
                WHERE {$where}
                ORDER BY pa.created_at DESC";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna clientes sem acesso ao portal.
     */
    private function getCustomersWithoutAccess(): array
    {
        $sql = "SELECT c.id, c.name, c.email, c.phone
                FROM customers c
                LEFT JOIN customer_portal_access pa ON pa.customer_id = c.id
                WHERE pa.id IS NULL
                ORDER BY c.name ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna métricas do portal.
     */
    private function getPortalMetrics(): array
    {
        $metrics = [
            'total_accesses'   => 0,
            'active_accesses'  => 0,
            'inactive_accesses' => 0,
            'logins_last_7d'   => 0,
            'logins_last_30d'  => 0,
            'pending_messages' => 0,
            'locked_accounts'  => 0,
        ];

        // Total de acessos
        $stmt = $this->db->query("SELECT COUNT(*) FROM customer_portal_access");
        $metrics['total_accesses'] = (int) $stmt->fetchColumn();

        // Ativos
        $stmt = $this->db->query("SELECT COUNT(*) FROM customer_portal_access WHERE is_active = 1");
        $metrics['active_accesses'] = (int) $stmt->fetchColumn();

        // Inativos
        $metrics['inactive_accesses'] = $metrics['total_accesses'] - $metrics['active_accesses'];

        // Logins nos últimos 7 dias
        $stmt = $this->db->query(
            "SELECT COUNT(*) FROM customer_portal_access
             WHERE last_login_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
        );
        $metrics['logins_last_7d'] = (int) $stmt->fetchColumn();

        // Logins nos últimos 30 dias
        $stmt = $this->db->query(
            "SELECT COUNT(*) FROM customer_portal_access
             WHERE last_login_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );
        $metrics['logins_last_30d'] = (int) $stmt->fetchColumn();

        // Mensagens pendentes (do cliente, não lidas)
        try {
            $stmt = $this->db->query(
                "SELECT COUNT(*) FROM customer_portal_messages
                 WHERE sender_type = 'customer' AND is_read = 0"
            );
            $metrics['pending_messages'] = (int) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            // Tabela pode não existir
        }

        // Contas bloqueadas
        $stmt = $this->db->query(
            "SELECT COUNT(*) FROM customer_portal_access WHERE locked_until > NOW()"
        );
        $metrics['locked_accounts'] = (int) $stmt->fetchColumn();

        return $metrics;
    }

    /**
     * Conta mensagens pendentes de clientes.
     */
    private function countPendingMessages(): int
    {
        try {
            $stmt = $this->db->query(
                "SELECT COUNT(*) FROM customer_portal_messages
                 WHERE sender_type = 'customer' AND is_read = 0"
            );
            return (int) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            return 0;
        }
    }

    /**
     * Remove sessões ativas de um acesso.
     */
    private function removeActiveSessions(int $accessId): int
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM customer_portal_sessions WHERE access_id = :aid");
            $stmt->execute([':aid' => $accessId]);
            return $stmt->rowCount();
        } catch (\PDOException $e) {
            return 0;
        }
    }

    /**
     * Gera senha temporária aleatória.
     */
    private function generateTempPassword(): string
    {
        $chars = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $password = '';
        for ($i = 0; $i < 10; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $password;
    }

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
