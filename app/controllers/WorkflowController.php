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

        require 'app/views/layout/header.php';
        require 'app/views/workflows/form.php';
        require 'app/views/layout/footer.php';
    }

    public function store()
    {
        $data = [
            'tenant_id'   => $_SESSION['tenant_id'] ?? 0,
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
}
