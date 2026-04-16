<?php

namespace Akti\Controllers;

use Akti\Models\QualityChecklist;
use Akti\Utils\Input;

/**
 * Class QualityController.
 */
class QualityController extends BaseController {
    private QualityChecklist $model;

    /**
     * Construtor da classe QualityController.
     *
     * @param \PDO $db Conexão PDO com o banco de dados
     * @param QualityChecklist $model Model
     */
    public function __construct(\PDO $db, QualityChecklist $model)
    {
        $this->db = $db;
        $this->model = $model;
    }

    /**
     * Exibe a página de listagem.
     */
    public function index()
    {
        $checklists = $this->model->readAll();
        $nonConformities = $this->model->getNonConformities(['status' => 'open']);

        require 'app/views/layout/header.php';
        require 'app/views/quality/index.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * Cria um novo registro no banco de dados.
     */
    public function create()
    {
        $checklist = null;
        require 'app/views/layout/header.php';
        require 'app/views/quality/form.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * Processa e armazena um novo registro.
     */
    public function store()
    {
        $data = [
            'tenant_id'         => $_SESSION['tenant']['id'] ?? 0,
            'name'              => Input::post('name', 'string', ''),
            'description'       => Input::post('description', 'string', ''),
            'pipeline_stage_id' => Input::post('pipeline_stage_id', 'int', 0) ?: null,
        ];

        $id = $this->model->create($data);
        $_SESSION['flash_success'] = 'Checklist criado.';
        header('Location: ?page=quality&action=edit&id=' . $id);
    }

    /**
     * Exibe o formulário de edição.
     */
    public function edit()
    {
        $id = Input::get('id', 'int', 0);
        $checklist = $this->model->readOne($id);
        if (!$checklist) {
            $_SESSION['flash_error'] = 'Checklist não encontrado.';
            header('Location: ?page=quality');
            return;
        }
        $items = $this->model->getItems($id);

        require 'app/views/layout/header.php';
        require 'app/views/quality/form.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * Atualiza um registro existente.
     */
    public function update()
    {
        $id = Input::post('id', 'int', 0);
        $data = [
            'name'              => Input::post('name', 'string', ''),
            'description'       => Input::post('description', 'string', ''),
            'pipeline_stage_id' => Input::post('pipeline_stage_id', 'int', 0) ?: null,
            'is_active'         => Input::post('is_active', 'int', 1),
        ];

        $this->model->update($id, $data);
        $_SESSION['flash_success'] = 'Checklist atualizado.';
        header('Location: ?page=quality&action=edit&id=' . $id);
    }

    /**
     * Remove um registro pelo ID.
     */
    public function delete()
    {
        $id = Input::get('id', 'int', 0);
        $this->model->delete($id);
        $_SESSION['flash_success'] = 'Checklist removido.';
        header('Location: ?page=quality');
    }

    /**
     * Add item.
     */
    public function addItem()
    {
        $data = [
            'tenant_id'    => $_SESSION['tenant']['id'] ?? 0,
            'checklist_id' => Input::post('checklist_id', 'int', 0),
            'description'  => Input::post('description', 'string', ''),
            'required'     => Input::post('required', 'int', 1),
            'sort_order'   => Input::post('sort_order', 'int', 0),
        ];

        $this->model->addItem($data);
        header('Content-Type: application/json');
        $this->json(['success' => true]);
    }

    /**
     * Remove item.
     */
    public function removeItem()
    {
        $itemId = Input::get('item_id', 'int', 0);
        $this->model->removeItem($itemId);
        header('Content-Type: application/json');
        $this->json(['success' => true]);
    }

    /**
     * Inspect.
     */
    public function inspect()
    {
        $orderId = Input::get('order_id', 'int', 0);
        $inspections = $this->model->getInspections($orderId);
        $checklists = $this->model->readAll();

        require 'app/views/layout/header.php';
        require 'app/views/quality/inspect.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * Store inspection.
     */
    public function storeInspection()
    {
        $data = [
            'tenant_id'    => $_SESSION['tenant']['id'] ?? 0,
            'checklist_id' => Input::post('checklist_id', 'int', 0),
            'order_id'     => Input::post('order_id', 'int', 0) ?: null,
            'inspector_id' => $_SESSION['user_id'] ?? null,
            'status'       => Input::post('status', 'string', 'pending'),
            'results'      => json_decode(Input::post('results', 'string', '[]'), true) ?: [],
            'notes'        => Input::post('notes', 'string', ''),
        ];

        $this->model->createInspection($data);
        $_SESSION['flash_success'] = 'Inspeção registrada.';
        header('Location: ?page=quality');
    }

    /**
     * Non conformities.
     */
    public function nonConformities()
    {
        $filters = [
            'status'   => Input::get('status', 'string', ''),
            'severity' => Input::get('severity', 'string', ''),
        ];
        $filters = array_filter($filters);
        $nonConformities = $this->model->getNonConformities($filters);

        require 'app/views/layout/header.php';
        require 'app/views/quality/nonconformities.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * Store non conformity.
     */
    public function storeNonConformity()
    {
        $data = [
            'tenant_id'     => $_SESSION['tenant']['id'] ?? 0,
            'inspection_id' => Input::post('inspection_id', 'int', 0) ?: null,
            'order_id'      => Input::post('order_id', 'int', 0) ?: null,
            'title'         => Input::post('title', 'string', ''),
            'description'   => Input::post('description', 'string', ''),
            'severity'      => Input::post('severity', 'string', 'medium'),
            'responsible_id' => Input::post('responsible_id', 'int', 0) ?: null,
        ];

        $this->model->createNonConformity($data);
        $_SESSION['flash_success'] = 'Não-conformidade registrada.';
        header('Location: ?page=quality&action=nonConformities');
    }

    /**
     * Resolve dependência ou valor.
     */
    public function resolveNonConformity()
    {
        $id = Input::post('id', 'int', 0);
        $correctiveAction = Input::post('corrective_action', 'string', '');
        $this->model->resolveNonConformity($id, $correctiveAction);
        header('Content-Type: application/json');
        $this->json(['success' => true]);
    }
}
