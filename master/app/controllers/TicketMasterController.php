<?php
/**
 * Controller: TicketMasterController
 * Gerencia tickets de suporte centralizados no painel Master.
 * Utiliza tabelas support_tickets / support_ticket_messages no banco akti_master.
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
        if (!empty($_GET['search'])) {
            $filters['search'] = $_GET['search'];
        }

        $stats = $this->ticketModel->getGlobalStats();
        $tickets = $this->ticketModel->readAll($filters);
        $clients = $this->clientModel->readAll();
        $admins = $this->ticketModel->getAdminUsers();

        require_once __DIR__ . '/../views/tickets/index.php';
    }

    /**
     * Detalhe de um ticket com mensagens e formulário de resposta.
     * GET ?page=tickets&action=view&id=X
     */
    public function view(): void
    {
        $ticketId = (int)($_GET['id'] ?? 0);

        if (!$ticketId) {
            $_SESSION['error'] = 'Parâmetros inválidos.';
            header('Location: ?page=tickets');
            exit;
        }

        $ticket = $this->ticketModel->readOne($ticketId);
        if (!$ticket) {
            $_SESSION['error'] = 'Ticket não encontrado.';
            header('Location: ?page=tickets');
            exit;
        }

        $messages = $this->ticketModel->getMessages($ticketId);
        $admins = $this->ticketModel->getAdminUsers();

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

        $ticketId = (int)($_POST['ticket_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');
        $isInternal = !empty($_POST['is_internal_note']);
        $adminId = (int)$_SESSION['admin_id'];

        if (!$ticketId || $message === '') {
            $_SESSION['error'] = 'Preencha todos os campos.';
            header('Location: ?page=tickets&action=view&id=' . $ticketId);
            exit;
        }

        // Buscar nome do admin
        $adminStmt = $this->db->prepare("SELECT name FROM admin_users WHERE id = :id");
        $adminStmt->execute(['id' => $adminId]);
        $admin = $adminStmt->fetch(\PDO::FETCH_ASSOC);
        $adminName = $admin ? $admin['name'] : 'Suporte Akti';

        $success = $this->ticketModel->addAdminReply($adminId, $adminName, $ticketId, $message, $isInternal);

        if ($success) {
            $this->logAction('ticket_reply', 'support_ticket', $ticketId, "Respondeu ticket de suporte #{$ticketId}");
            $_SESSION['success'] = $isInternal ? 'Nota interna adicionada.' : 'Resposta enviada com sucesso.';
        } else {
            $_SESSION['error'] = 'Erro ao enviar resposta.';
        }

        header('Location: ?page=tickets&action=view&id=' . $ticketId);
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

        $ticketId = (int)($_POST['ticket_id'] ?? 0);
        $newStatus = $_POST['new_status'] ?? '';

        if (!$ticketId || !$newStatus) {
            $_SESSION['error'] = 'Parâmetros inválidos.';
            header('Location: ?page=tickets');
            exit;
        }

        $success = $this->ticketModel->changeStatus($ticketId, $newStatus);

        if ($success) {
            $this->logAction('ticket_status_change', 'support_ticket', $ticketId, "Alterou status para {$newStatus}");
            $_SESSION['success'] = 'Status alterado com sucesso.';
        } else {
            $_SESSION['error'] = 'Erro ao alterar status.';
        }

        header('Location: ?page=tickets&action=view&id=' . $ticketId);
        exit;
    }

    /**
     * Atribuir admin a um ticket.
     * POST ?page=tickets&action=assign
     */
    public function assign(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=tickets');
            exit;
        }

        $ticketId = (int)($_POST['ticket_id'] ?? 0);
        $assignedAdminId = ($_POST['assigned_admin_id'] ?? '') !== '' ? (int)$_POST['assigned_admin_id'] : null;

        if (!$ticketId) {
            $_SESSION['error'] = 'Parâmetros inválidos.';
            header('Location: ?page=tickets');
            exit;
        }

        $success = $this->ticketModel->assignAdmin($ticketId, $assignedAdminId);

        if ($success) {
            $this->logAction('ticket_assign', 'support_ticket', $ticketId, "Atribuiu admin #{$assignedAdminId} ao ticket");
            $_SESSION['success'] = 'Responsável atualizado.';
        } else {
            $_SESSION['error'] = 'Erro ao atribuir responsável.';
        }

        header('Location: ?page=tickets&action=view&id=' . $ticketId);
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
