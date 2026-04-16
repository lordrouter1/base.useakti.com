<?php

namespace Akti\Controllers;

/**
 * SupportController
 * Permite ao tenant criar e acompanhar tickets de suporte direcionados ao master.
 * Os tickets são armazenados no banco akti_master (support_tickets / support_ticket_messages).
 */
class SupportController extends BaseController
{
    private ?\PDO $masterDb = null;

    /**
     * Retorna conexão com o banco master (lazy-loaded).
     */
    private function getMasterDb(): \PDO
    {
        if ($this->masterDb !== null) {
            return $this->masterDb;
        }

        $cfg = \TenantManager::getMasterConfig();
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $cfg['host'],
            $cfg['port'],
            $cfg['db_name'],
            $cfg['charset']
        );

        $this->masterDb = new \PDO($dsn, $cfg['username'], $cfg['password'], [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_EMULATE_PREPARES   => false,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ]);

        return $this->masterDb;
    }

    /**
     * Retorna o tenant_client_id a partir da sessão.
     */
    private function getTenantClientId(): int
    {
        return (int) ($_SESSION['tenant']['id'] ?? 0);
    }

    /**
     * Listagem dos tickets de suporte do tenant.
     * GET ?page=suporte
     */
    public function index(): void
    {
        $this->requireAuth();
        $tenantClientId = $this->getTenantClientId();

        $filters = [];
        if (!empty($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        }

        try {
            $db = $this->getMasterDb();

            $where = 'st.tenant_client_id = :tenant_id';
            $params = ['tenant_id' => $tenantClientId];

            if (!empty($filters['status'])) {
                $where .= ' AND st.status = :status';
                $params['status'] = $filters['status'];
            }

            $stmt = $db->prepare("
                SELECT st.*
                FROM support_tickets st
                WHERE {$where}
                ORDER BY st.created_at DESC
                LIMIT 50
            ");
            $stmt->execute($params);
            $tickets = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Stats
            $stmtStats = $db->prepare("
                SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN status IN ('open','in_progress','waiting_customer') THEN 1 ELSE 0 END) AS active,
                    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) AS resolved
                FROM support_tickets
                WHERE tenant_client_id = :tenant_id
            ");
            $stmtStats->execute(['tenant_id' => $tenantClientId]);
            $stats = $stmtStats->fetch(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log('[Support] Erro ao buscar tickets: ' . $e->getMessage());
            $tickets = [];
            $stats = ['total' => 0, 'active' => 0, 'resolved' => 0];
        }

        $this->render('support/index', compact('tickets', 'stats', 'filters'));
    }

    /**
     * Formulário de criação de ticket.
     * GET ?page=suporte&action=create
     */
    public function create(): void
    {
        $this->requireAuth();
        $this->render('support/form');
    }

    /**
     * Processa criação do ticket.
     * POST ?page=suporte&action=store
     */
    public function store(): void
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('?page=suporte');
        }

        $subject     = trim($_POST['subject'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $priority    = $_POST['priority'] ?? 'medium';
        $category    = trim($_POST['category'] ?? '') ?: null;

        if ($subject === '' || $description === '') {
            $_SESSION['error'] = 'Assunto e descrição são obrigatórios.';
            $this->redirect('?page=suporte&action=create');
        }

        $validPriorities = ['low', 'medium', 'high', 'urgent'];
        if (!in_array($priority, $validPriorities, true)) {
            $priority = 'medium';
        }

        $tenantClientId = $this->getTenantClientId();
        $userId   = (int) ($_SESSION['user_id'] ?? 0);
        $userName = $_SESSION['user_name'] ?? 'Usuário';
        $userEmail = $_SESSION['user_email'] ?? null;

        try {
            $db = $this->getMasterDb();

            // Gerar ticket_number
            $stmt = $db->query("SELECT MAX(id) AS max_id FROM support_tickets");
            $maxId = (int) ($stmt->fetch(\PDO::FETCH_ASSOC)['max_id'] ?? 0);
            $ticketNumber = 'SUP-' . str_pad($maxId + 1, 5, '0', STR_PAD_LEFT);

            $stmt = $db->prepare("
                INSERT INTO support_tickets
                    (tenant_client_id, ticket_number, subject, description, priority, category,
                     created_by_user_id, created_by_name, created_by_email)
                VALUES
                    (:tenant_id, :number, :subject, :description, :priority, :category,
                     :user_id, :user_name, :user_email)
            ");
            $stmt->execute([
                'tenant_id'   => $tenantClientId,
                'number'      => $ticketNumber,
                'subject'     => $subject,
                'description' => $description,
                'priority'    => $priority,
                'category'    => $category,
                'user_id'     => $userId,
                'user_name'   => $userName,
                'user_email'  => $userEmail,
            ]);

            $ticketId = (int) $db->lastInsertId();

            // Inserir descrição como primeira mensagem
            $db->prepare("
                INSERT INTO support_ticket_messages
                    (support_ticket_id, sender_type, sender_id, sender_name, message)
                VALUES
                    (:ticket_id, 'tenant', :sender_id, :sender_name, :message)
            ")->execute([
                'ticket_id'   => $ticketId,
                'sender_id'   => $userId,
                'sender_name' => $userName,
                'message'     => $description,
            ]);

            $_SESSION['success'] = "Ticket {$ticketNumber} criado com sucesso.";
            $this->redirect('?page=suporte&action=view&id=' . $ticketId);
        } catch (\Exception $e) {
            error_log('[Support] Erro ao criar ticket: ' . $e->getMessage());
            $_SESSION['error'] = 'Erro ao criar ticket. Tente novamente.';
            $this->redirect('?page=suporte&action=create');
        }
    }

    /**
     * Visualizar um ticket e suas mensagens.
     * GET ?page=suporte&action=view&id=X
     */
    public function view(): void
    {
        $this->requireAuth();
        $ticketId = (int) ($_GET['id'] ?? 0);
        $tenantClientId = $this->getTenantClientId();

        if (!$ticketId) {
            $this->redirect('?page=suporte');
        }

        try {
            $db = $this->getMasterDb();

            $stmt = $db->prepare("
                SELECT * FROM support_tickets
                WHERE id = :id AND tenant_client_id = :tenant_id
                LIMIT 1
            ");
            $stmt->execute(['id' => $ticketId, 'tenant_id' => $tenantClientId]);
            $ticket = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$ticket) {
                $_SESSION['error'] = 'Ticket não encontrado.';
                $this->redirect('?page=suporte');
            }

            // Buscar mensagens (excluir notas internas do admin)
            $stmt = $db->prepare("
                SELECT * FROM support_ticket_messages
                WHERE support_ticket_id = :ticket_id AND is_internal_note = 0
                ORDER BY created_at ASC
            ");
            $stmt->execute(['ticket_id' => $ticketId]);
            $messages = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log('[Support] Erro ao visualizar ticket: ' . $e->getMessage());
            $_SESSION['error'] = 'Erro ao carregar ticket.';
            $this->redirect('?page=suporte');
        }

        $this->render('support/view', compact('ticket', 'messages'));
    }

    /**
     * Adicionar mensagem a um ticket existente.
     * POST ?page=suporte&action=addMessage
     */
    public function addMessage(): void
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('?page=suporte');
        }

        $ticketId = (int) ($_POST['ticket_id'] ?? 0);
        $message  = trim($_POST['message'] ?? '');
        $tenantClientId = $this->getTenantClientId();

        if (!$ticketId || $message === '') {
            $_SESSION['error'] = 'Preencha a mensagem.';
            $this->redirect('?page=suporte&action=view&id=' . $ticketId);
        }

        $userId   = (int) ($_SESSION['user_id'] ?? 0);
        $userName = $_SESSION['user_name'] ?? 'Usuário';

        try {
            $db = $this->getMasterDb();

            // Verificar que o ticket pertence ao tenant
            $stmt = $db->prepare("
                SELECT id, status FROM support_tickets
                WHERE id = :id AND tenant_client_id = :tenant_id
            ");
            $stmt->execute(['id' => $ticketId, 'tenant_id' => $tenantClientId]);
            $ticket = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$ticket) {
                $_SESSION['error'] = 'Ticket não encontrado.';
                $this->redirect('?page=suporte');
            }

            // Inserir mensagem
            $db->prepare("
                INSERT INTO support_ticket_messages
                    (support_ticket_id, sender_type, sender_id, sender_name, message)
                VALUES
                    (:ticket_id, 'tenant', :sender_id, :sender_name, :message)
            ")->execute([
                'ticket_id'   => $ticketId,
                'sender_id'   => $userId,
                'sender_name' => $userName,
                'message'     => $message,
            ]);

            // Se status era waiting_customer, reabrir
            if ($ticket['status'] === 'waiting_customer') {
                $db->prepare("
                    UPDATE support_tickets SET status = 'open', updated_at = NOW()
                    WHERE id = :id
                ")->execute(['id' => $ticketId]);
            }

            $_SESSION['success'] = 'Mensagem enviada.';
        } catch (\Exception $e) {
            error_log('[Support] Erro ao adicionar mensagem: ' . $e->getMessage());
            $_SESSION['error'] = 'Erro ao enviar mensagem.';
        }

        $this->redirect('?page=suporte&action=view&id=' . $ticketId);
    }
}
