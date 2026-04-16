<?php
/**
 * Controller: TicketMasterController
 * Gerencia tickets de suporte de todos os tenants no painel Master.
 */

class TicketMasterController
{
    private $db;
    private $ticketModel;
    private $clientModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->ticketModel = new MasterTicket($db);
        $this->clientModel = new TenantClient($db);
    }

    /**
     * Listagem centralizada de tickets com filtros e stats.
     * GET ?page=tickets
     */
    public function index(): void
    {
        $filters = [];
        if (!empty($_GET['tenant_id'])) {
            $filters['tenant_id'] = (int)$_GET['tenant_id'];
        }
        if (!empty($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        }
        if (!empty($_GET['priority'])) {
            $filters['priority'] = $_GET['priority'];
        }

        $stats = $this->ticketModel->getGlobalStats();
        $tickets = $this->ticketModel->readAllFromAllTenants($filters);
        $clients = $this->clientModel->readAll();

        require_once __DIR__ . '/../views/tickets/index.php';
    }

    /**
     * Detalhe de um ticket com mensagens e formulário de resposta.
     * GET ?page=tickets&action=view&tenant_id=X&ticket_id=Y
     */
    public function view(): void
    {
        $tenantClientId = (int)($_GET['tenant_id'] ?? 0);
        $ticketId = (int)($_GET['ticket_id'] ?? 0);

        if (!$tenantClientId || !$ticketId) {
            $_SESSION['error'] = 'Parâmetros inválidos.';
            header('Location: ?page=tickets');
            exit;
        }

        $ticket = $this->ticketModel->readTicketFromTenant($tenantClientId, $ticketId);
        if (!$ticket) {
            $_SESSION['error'] = 'Ticket não encontrado.';
            header('Location: ?page=tickets');
            exit;
        }

        $messages = $this->ticketModel->getTicketMessages($tenantClientId, $ticketId);

        // Buscar log de respostas do master
        $stmt = $this->db->prepare("
            SELECT mtr.*, au.name as admin_name
            FROM master_ticket_replies mtr
            LEFT JOIN admin_users au ON mtr.admin_id = au.id
            WHERE mtr.tenant_client_id = :tenant_id AND mtr.ticket_id = :ticket_id
            ORDER BY mtr.created_at ASC
        ");
        $stmt->execute(['tenant_id' => $tenantClientId, 'ticket_id' => $ticketId]);
        $masterReplies = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        require_once __DIR__ . '/../views/tickets/view.php';
    }

    /**
     * Responder a um ticket.
     * POST ?page=tickets&action=reply
     */
    public function reply(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=tickets');
            exit;
        }

        $tenantClientId = (int)($_POST['tenant_client_id'] ?? 0);
        $ticketId = (int)($_POST['ticket_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');
        $adminId = (int)$_SESSION['admin_id'];

        if (!$tenantClientId || !$ticketId || $message === '') {
            $_SESSION['error'] = 'Preencha todos os campos.';
            header('Location: ?page=tickets&action=view&tenant_id=' . $tenantClientId . '&ticket_id=' . $ticketId);
            exit;
        }

        $success = $this->ticketModel->replyToTicket($adminId, $tenantClientId, $ticketId, $message);

        if ($success) {
            $this->logAction('ticket_reply', 'ticket', $ticketId, "Respondeu ticket #{$ticketId} do tenant #{$tenantClientId}");
            $_SESSION['success'] = 'Resposta enviada com sucesso.';
        } else {
            $_SESSION['error'] = 'Erro ao enviar resposta. Verifique se o tenant possui a tabela de mensagens.';
        }

        header('Location: ?page=tickets&action=view&tenant_id=' . $tenantClientId . '&ticket_id=' . $ticketId);
        exit;
    }

    /**
     * Alterar status de um ticket.
     * POST ?page=tickets&action=changeStatus
     */
    public function changeStatus(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=tickets');
            exit;
        }

        $tenantClientId = (int)($_POST['tenant_client_id'] ?? 0);
        $ticketId = (int)($_POST['ticket_id'] ?? 0);
        $newStatus = $_POST['new_status'] ?? '';
        $adminId = (int)$_SESSION['admin_id'];

        if (!$tenantClientId || !$ticketId || !$newStatus) {
            $_SESSION['error'] = 'Parâmetros inválidos.';
            header('Location: ?page=tickets');
            exit;
        }

        $success = $this->ticketModel->changeTicketStatus($adminId, $tenantClientId, $ticketId, $newStatus);

        if ($success) {
            $this->logAction('ticket_status_change', 'ticket', $ticketId, "Alterou status do ticket #{$ticketId} para {$newStatus}");
            $_SESSION['success'] = 'Status alterado com sucesso.';
        } else {
            $_SESSION['error'] = 'Erro ao alterar status.';
        }

        header('Location: ?page=tickets&action=view&tenant_id=' . $tenantClientId . '&ticket_id=' . $ticketId);
        exit;
    }

    /**
     * Registra ação no log de auditoria.
     */
    private function logAction(string $action, string $targetType, int $targetId, string $details): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO admin_logs (admin_id, action, target_type, target_id, details)
            VALUES (:admin_id, :action, :target_type, :target_id, :details)
        ");
        $stmt->execute([
            'admin_id' => $_SESSION['admin_id'],
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'details' => $details,
        ]);
    }
}
