<?php

namespace Akti\Controllers;

use Akti\Models\Ticket;
use Akti\Utils\Input;

/**
 * Class TicketController.
 */
class TicketController extends BaseController
{
    private Ticket $ticketModel;

    /**
     * Construtor da classe TicketController.
     *
     * @param \PDO $db Conexão PDO com o banco de dados
     */
    public function __construct(\PDO $db)
    {
        parent::__construct($db);
        $this->ticketModel = new Ticket($db);
    }

    /**
     * Exibe a página de listagem.
     */
    public function index()
    {
        $this->requireAuth();
        $page = Input::get('p', 'int', 1);
        $filters = [
            'search'      => Input::get('search', 'string', ''),
            'status'      => Input::get('status', 'string', ''),
            'priority'    => Input::get('priority', 'string', ''),
            'category_id' => Input::get('category_id', 'int', 0) ?: null,
        ];
        $filters = array_filter($filters);
        $tenantId = $this->getTenantId();

        $result = $this->ticketModel->readPaginated($tenantId, $page, 15, $filters);
        $tickets = $result['data'];
        $pagination = $result;
        $categories = $this->ticketModel->getCategories($tenantId);

        require 'app/views/layout/header.php';
        require 'app/views/tickets/index.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * Cria um novo registro no banco de dados.
     */
    public function create()
    {
        $this->requireAuth();
        $ticket = null;
        $categories = $this->ticketModel->getCategories($this->getTenantId());

        require 'app/views/layout/header.php';
        require 'app/views/tickets/form.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * Processa e armazena um novo registro.
     */
    public function store()
    {
        $this->requireAuth();
        $tenantId = $this->getTenantId();
        $data = [
            'tenant_id'   => $tenantId,
            'ticket_number' => $this->ticketModel->generateTicketNumber($tenantId),
            'category_id' => Input::post('category_id', 'int', 0) ?: null,
            'requester_id' => $_SESSION['user_id'] ?? 0,
            'subject'     => Input::post('subject', 'string', ''),
            'description' => Input::post('description', 'string', ''),
            'priority'    => Input::post('priority', 'string', 'medium'),
            'sla_hours'   => Input::post('sla_hours', 'int', 48),
        ];

        if (empty($data['subject'])) {
            $_SESSION['flash_error'] = 'O assunto é obrigatório.';
            header('Location: ?page=tickets&action=create');
            return;
        }

        $this->ticketModel->create($data);
        $_SESSION['flash_success'] = 'Ticket criado com sucesso.';
        header('Location: ?page=tickets');
    }

    /**
     * View.
     */
    public function view()
    {
        $this->requireAuth();
        $id = Input::get('id', 'int', 0);
        $ticket = $this->ticketModel->readOne($id);
        if (!$ticket) {
            $_SESSION['flash_error'] = 'Ticket não encontrado.';
            header('Location: ?page=tickets');
            return;
        }
        $messages = $this->ticketModel->getMessages($id);

        require 'app/views/layout/header.php';
        require 'app/views/tickets/view.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * Add message.
     */
    public function addMessage()
    {
        $this->requireAuth();
        $ticketId = Input::post('ticket_id', 'int', 0);
        $message = Input::post('message', 'string', '');

        if (empty($message)) {
            $_SESSION['flash_error'] = 'A mensagem não pode estar vazia.';
            header('Location: ?page=tickets&action=view&id=' . $ticketId);
            return;
        }

        $this->ticketModel->addMessage([
            'ticket_id' => $ticketId,
            'user_id'   => $_SESSION['user_id'] ?? 0,
            'message'   => $message,
            'is_internal' => Input::post('is_internal', 'int', 0),
        ]);

        $_SESSION['flash_success'] = 'Mensagem adicionada.';
        header('Location: ?page=tickets&action=view&id=' . $ticketId);
    }

    /**
     * Update status.
     */
    public function updateStatus()
    {
        $this->requireAuth();
        $id = Input::post('id', 'int', 0);
        $status = Input::post('status', 'string', '');

        $this->ticketModel->updateStatus($id, $status);
        $_SESSION['flash_success'] = 'Status atualizado.';
        header('Location: ?page=tickets&action=view&id=' . $id);
    }

    /**
     * Dashboard.
     */
    public function dashboard()
    {
        $this->requireAuth();
        $stats = $this->ticketModel->getDashboardStats($this->getTenantId());

        if ($this->isAjax()) {
            $this->json(['success' => true, 'data' => $stats]);
            return;
        }

        require 'app/views/layout/header.php';
        require 'app/views/tickets/dashboard.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * Remove um registro pelo ID.
     */
    public function delete()
    {
        $this->requireAuth();
        $id = Input::get('id', 'int', 0);
        $this->ticketModel->delete($id);
        $_SESSION['flash_success'] = 'Ticket removido.';
        header('Location: ?page=tickets');
    }
}
