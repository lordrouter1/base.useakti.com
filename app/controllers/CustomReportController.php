<?php

namespace Akti\Controllers;

use Akti\Models\ReportTemplate;
use Akti\Utils\Input;
use Database;
use PDO;

class CustomReportController
{
    private PDO $db;
    private ReportTemplate $model;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->model = new ReportTemplate($this->db);
    }

    public function index()
    {
        $userId = $_SESSION['user_id'] ?? null;
        $templates = $this->model->readAll($userId);
        $entities = $this->model->getAvailableEntities();

        require 'app/views/layout/header.php';
        require 'app/views/custom_reports/index.php';
        require 'app/views/layout/footer.php';
    }

    public function create()
    {
        $entities = $this->model->getAvailableEntities();
        $template = null;

        require 'app/views/layout/header.php';
        require 'app/views/custom_reports/form.php';
        require 'app/views/layout/footer.php';
    }

    public function store()
    {
        $data = [
            'tenant_id' => $_SESSION['tenant_id'] ?? 0,
            'user_id'   => $_SESSION['user_id'] ?? null,
            'name'      => Input::post('name', 'string', ''),
            'entity'    => Input::post('entity', 'string', ''),
            'columns'   => json_decode(Input::post('columns', 'string', '[]'), true) ?: [],
            'filters'   => json_decode(Input::post('filters', 'string', '[]'), true) ?: [],
            'grouping'  => json_decode(Input::post('grouping', 'string', '[]'), true) ?: [],
            'sorting'   => json_decode(Input::post('sorting', 'string', '[]'), true) ?: [],
            'is_shared' => Input::post('is_shared', 'int', 0),
        ];

        $id = $this->model->create($data);
        $_SESSION['flash_success'] = 'Relatório salvo com sucesso.';
        header('Location: ?page=custom_reports&action=run&id=' . $id);
    }

    public function edit()
    {
        $id = Input::get('id', 'int', 0);
        $template = $this->model->readOne($id);
        if (!$template) {
            $_SESSION['flash_error'] = 'Relatório não encontrado.';
            header('Location: ?page=custom_reports');
            return;
        }
        $entities = $this->model->getAvailableEntities();

        require 'app/views/layout/header.php';
        require 'app/views/custom_reports/form.php';
        require 'app/views/layout/footer.php';
    }

    public function update()
    {
        $id = Input::post('id', 'int', 0);
        $data = [
            'name'      => Input::post('name', 'string', ''),
            'entity'    => Input::post('entity', 'string', ''),
            'columns'   => json_decode(Input::post('columns', 'string', '[]'), true) ?: [],
            'filters'   => json_decode(Input::post('filters', 'string', '[]'), true) ?: [],
            'grouping'  => json_decode(Input::post('grouping', 'string', '[]'), true) ?: [],
            'sorting'   => json_decode(Input::post('sorting', 'string', '[]'), true) ?: [],
            'is_shared' => Input::post('is_shared', 'int', 0),
        ];

        $this->model->update($id, $data);
        $_SESSION['flash_success'] = 'Relatório atualizado.';
        header('Location: ?page=custom_reports&action=run&id=' . $id);
    }

    public function delete()
    {
        $id = Input::get('id', 'int', 0);
        $this->model->delete($id);
        $_SESSION['flash_success'] = 'Relatório removido.';
        header('Location: ?page=custom_reports');
    }

    public function run()
    {
        $id = Input::get('id', 'int', 0);
        $template = $this->model->readOne($id);
        if (!$template) {
            $_SESSION['flash_error'] = 'Relatório não encontrado.';
            header('Location: ?page=custom_reports');
            return;
        }

        $result = $this->model->executeReport($id);
        $reportData = $result['data'] ?? [];
        $entities = $this->model->getAvailableEntities();

        require 'app/views/layout/header.php';
        require 'app/views/custom_reports/results.php';
        require 'app/views/layout/footer.php';
    }

    public function getEntities()
    {
        $entities = $this->model->getAvailableEntities();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $entities]);
    }
}
