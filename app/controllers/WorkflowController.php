<?php

namespace Akti\Controllers;

use Akti\Models\WorkflowRule;
use Akti\Utils\Input;
use Database;
use PDO;

class WorkflowController
{
    private PDO $db;
    private WorkflowRule $model;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->model = new WorkflowRule($this->db);
    }

    public function index()
    {
        $rules = $this->model->readAll();

        require 'app/views/layout/header.php';
        require 'app/views/workflows/index.php';
        require 'app/views/layout/footer.php';
    }

    public function create()
    {
        $rule = null;
        $availableEvents = $this->getAvailableEvents();
        $eventFields = $this->getEventFields();

        require 'app/views/layout/header.php';
        require 'app/views/workflows/form.php';
        require 'app/views/layout/footer.php';
    }

    public function store()
    {
        $data = [
            'tenant_id'   => $_SESSION['tenant']['id'] ?? 0,
            'name'        => Input::post('name', 'string', ''),
            'description' => Input::post('description', 'string', ''),
            'event'       => Input::post('event', 'string', ''),
            'conditions'  => json_decode(Input::post('conditions', 'string', '[]'), true) ?: [],
            'actions'     => json_decode(Input::post('actions', 'string', '[]'), true) ?: [],
            'is_active'   => Input::post('is_active', 'int', 1),
            'priority'    => Input::post('priority', 'int', 0),
            'created_by'  => $_SESSION['user_id'] ?? null,
        ];

        $this->model->create($data);
        $_SESSION['flash_success'] = 'Regra de workflow criada.';
        header('Location: ?page=workflows');
    }

    public function edit()
    {
        $id = Input::get('id', 'int', 0);
        $rule = $this->model->readOne($id);
        if (!$rule) {
            $_SESSION['flash_error'] = 'Regra não encontrada.';
            header('Location: ?page=workflows');
            return;
        }
        $availableEvents = $this->getAvailableEvents();
        $eventFields = $this->getEventFields();

        require 'app/views/layout/header.php';
        require 'app/views/workflows/form.php';
        require 'app/views/layout/footer.php';
    }

    public function update()
    {
        $id = Input::post('id', 'int', 0);
        $data = [
            'name'        => Input::post('name', 'string', ''),
            'description' => Input::post('description', 'string', ''),
            'event'       => Input::post('event', 'string', ''),
            'conditions'  => json_decode(Input::post('conditions', 'string', '[]'), true) ?: [],
            'actions'     => json_decode(Input::post('actions', 'string', '[]'), true) ?: [],
            'is_active'   => Input::post('is_active', 'int', 1),
            'priority'    => Input::post('priority', 'int', 0),
        ];

        $this->model->update($id, $data);
        $_SESSION['flash_success'] = 'Regra atualizada.';
        header('Location: ?page=workflows');
    }

    public function delete()
    {
        $id = Input::get('id', 'int', 0);
        $this->model->delete($id);
        $_SESSION['flash_success'] = 'Regra removida.';
        header('Location: ?page=workflows');
    }

    public function toggle()
    {
        $id = Input::get('id', 'int', 0);
        $this->model->toggle($id);
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    }

    public function logs()
    {
        $ruleId = Input::get('rule_id', 'int', 0);
        $logs = $this->model->getLogs($ruleId);
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $logs]);
    }

    public function reorder()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $order = $input['order'] ?? [];

        foreach ($order as $item) {
            $id = (int) ($item['id'] ?? 0);
            $priority = (int) ($item['priority'] ?? 0);
            if ($id > 0) {
                $this->model->updatePriority($id, $priority);
            }
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    private function getAvailableEvents(): array
    {
        return [
            'model.order.created'        => 'Pedido criado',
            'model.order.updated'        => 'Pedido atualizado',
            'model.order.stage_changed'  => 'Pedido mudou de etapa',
            'model.customer.created'     => 'Cliente criado',
            'model.customer.updated'     => 'Cliente atualizado',
            'model.installment.paid'     => 'Parcela paga',
            'model.installment.overdue'  => 'Parcela vencida',
            'model.supplier.created'     => 'Fornecedor criado',
            'model.quote.created'        => 'Orçamento criado',
            'model.quote.approved'       => 'Orçamento aprovado',
            'model.nfe_document.authorized' => 'NF-e autorizada',
            'auth.login.failed'          => 'Falha no login',
        ];
    }

    private function getEventFields(): array
    {
        return [
            'model.order.created' => [
                'id'             => ['label' => 'ID do Pedido',         'type' => 'int'],
                'customer_id'    => ['label' => 'ID do Cliente',        'type' => 'int'],
                'total_amount'   => ['label' => 'Valor Total (R$)',     'type' => 'decimal'],
                'pipeline_stage' => ['label' => 'Etapa do Pipeline',    'type' => 'string'],
            ],
            'model.order.updated' => [
                'id'           => ['label' => 'ID do Pedido',       'type' => 'int'],
                'customer_id'  => ['label' => 'ID do Cliente',      'type' => 'int'],
                'total_amount' => ['label' => 'Valor Total (R$)',   'type' => 'decimal'],
                'status'       => ['label' => 'Status',             'type' => 'string'],
            ],
            'model.order.stage_changed' => [
                'id'         => ['label' => 'ID do Pedido',       'type' => 'int'],
                'from_stage' => ['label' => 'Etapa Anterior',     'type' => 'string'],
                'to_stage'   => ['label' => 'Nova Etapa',         'type' => 'string'],
                'user_id'    => ['label' => 'Usuário que Moveu',  'type' => 'int'],
            ],
            'model.customer.created' => [
                'id'    => ['label' => 'ID do Cliente', 'type' => 'int'],
                'name'  => ['label' => 'Nome',          'type' => 'string'],
                'email' => ['label' => 'E-mail',        'type' => 'string'],
                'code'  => ['label' => 'Código',        'type' => 'string'],
            ],
            'model.customer.updated' => [
                'id'    => ['label' => 'ID do Cliente', 'type' => 'int'],
                'name'  => ['label' => 'Nome',          'type' => 'string'],
                'email' => ['label' => 'E-mail',        'type' => 'string'],
            ],
            'model.installment.paid' => [
                'installment_id' => ['label' => 'ID da Parcela',            'type' => 'int'],
                'order_id'       => ['label' => 'ID do Pedido',             'type' => 'int'],
                'paid_amount'    => ['label' => 'Valor Pago (R$)',          'type' => 'decimal'],
                'auto_confirmed' => ['label' => 'Confirmação Automática',   'type' => 'bool'],
                'user_id'        => ['label' => 'Usuário',                  'type' => 'int'],
            ],
            'model.installment.overdue' => [
                'installment_id' => ['label' => 'ID da Parcela',       'type' => 'int'],
                'order_id'       => ['label' => 'ID do Pedido',        'type' => 'int'],
                'due_date'       => ['label' => 'Data de Vencimento',  'type' => 'date'],
                'amount'         => ['label' => 'Valor (R$)',          'type' => 'decimal'],
            ],
            'model.supplier.created' => [
                'id'   => ['label' => 'ID do Fornecedor', 'type' => 'int'],
                'name' => ['label' => 'Nome',              'type' => 'string'],
            ],
            'model.quote.created' => [
                'id' => ['label' => 'ID do Orçamento', 'type' => 'int'],
            ],
            'model.quote.approved' => [
                'id' => ['label' => 'ID do Orçamento', 'type' => 'int'],
            ],
            'model.nfe_document.authorized' => [
                'id'       => ['label' => 'ID do Documento',  'type' => 'int'],
                'order_id' => ['label' => 'ID do Pedido',     'type' => 'int'],
                'numero'   => ['label' => 'Número da NF-e',   'type' => 'string'],
            ],
            'auth.login.failed' => [
                'ip'       => ['label' => 'IP do Acesso',      'type' => 'string'],
                'username' => ['label' => 'Usuário Tentado',   'type' => 'string'],
            ],
        ];
    }
}
