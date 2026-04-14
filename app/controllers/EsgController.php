<?php

namespace Akti\Controllers;

use Akti\Models\EsgMetric;
use Akti\Utils\Input;

class EsgController extends BaseController
{
    private EsgMetric $esgModel;

    public function __construct(\PDO $db)
    {
        parent::__construct($db);
        $this->esgModel = new EsgMetric($db);
    }

    public function index()
    {
        $this->requireAuth();
        $tenantId = $this->getTenantId();
        $metrics = $this->esgModel->readAll($tenantId);
        $summary = $this->esgModel->getDashboardSummary($tenantId);

        require 'app/views/layout/header.php';
        require 'app/views/esg/index.php';
        require 'app/views/layout/footer.php';
    }

    public function create()
    {
        $this->requireAuth();
        $metric = null;

        require 'app/views/layout/header.php';
        require 'app/views/esg/form.php';
        require 'app/views/layout/footer.php';
    }

    public function store()
    {
        $this->requireAuth();
        $data = [
            'tenant_id'   => $this->getTenantId(),
            'name'        => Input::post('name', 'string', ''),
            'category'    => Input::post('category', 'string', 'environmental'),
            'unit'        => Input::post('unit', 'string', ''),
            'description' => Input::post('description', 'string', ''),
            'is_active'   => Input::post('is_active', 'int', 1),
        ];

        if (empty($data['name'])) {
            $_SESSION['flash_error'] = 'O nome da métrica é obrigatório.';
            header('Location: ?page=esg&action=create');
            return;
        }

        $this->esgModel->create($data);
        $_SESSION['flash_success'] = 'Métrica ESG criada com sucesso.';
        header('Location: ?page=esg');
    }

    public function edit()
    {
        $this->requireAuth();
        $id = Input::get('id', 'int', 0);
        $metric = $this->esgModel->readOne($id);
        if (!$metric) {
            $_SESSION['flash_error'] = 'Métrica não encontrada.';
            header('Location: ?page=esg');
            return;
        }
        $records = $this->esgModel->getRecords($this->getTenantId(), ['metric_id' => $id]);
        $targets = $this->esgModel->getTargets($this->getTenantId(), $id);

        require 'app/views/layout/header.php';
        require 'app/views/esg/form.php';
        require 'app/views/layout/footer.php';
    }

    public function update()
    {
        $this->requireAuth();
        $id = Input::post('id', 'int', 0);
        $data = [
            'name'        => Input::post('name', 'string', ''),
            'category'    => Input::post('category', 'string', 'environmental'),
            'unit'        => Input::post('unit', 'string', ''),
            'description' => Input::post('description', 'string', ''),
            'is_active'   => Input::post('is_active', 'int', 1),
        ];

        $this->esgModel->update($id, $data);
        $_SESSION['flash_success'] = 'Métrica atualizada.';
        header('Location: ?page=esg');
    }

    public function delete()
    {
        $this->requireAuth();
        $id = Input::get('id', 'int', 0);
        $this->esgModel->delete($id);
        $_SESSION['flash_success'] = 'Métrica removida.';
        header('Location: ?page=esg');
    }

    public function addRecord()
    {
        $this->requireAuth();
        $metricId = Input::post('metric_id', 'int', 0);
        $data = [
            'metric_id'    => $metricId,
            'tenant_id'    => $this->getTenantId(),
            'recorded_at'  => Input::post('recorded_at', 'string', date('Y-m-d')),
            'value'        => Input::post('value', 'string', '0'),
            'notes'        => Input::post('notes', 'string', ''),
            'recorded_by'  => $_SESSION['user_id'] ?? 0,
        ];

        $this->esgModel->addRecord($data);
        $_SESSION['flash_success'] = 'Registro adicionado.';
        header('Location: ?page=esg&action=edit&id=' . $metricId);
    }

    public function setTarget()
    {
        $this->requireAuth();
        $metricId = Input::post('metric_id', 'int', 0);
        $data = [
            'metric_id'    => $metricId,
            'tenant_id'    => $this->getTenantId(),
            'year'         => Input::post('year', 'int', (int) date('Y')),
            'target_value' => Input::post('target_value', 'string', '0'),
            'description'  => Input::post('description', 'string', ''),
        ];

        $this->esgModel->saveTarget($data);
        $_SESSION['flash_success'] = 'Meta ESG definida.';
        header('Location: ?page=esg&action=edit&id=' . $metricId);
    }

    public function dashboard()
    {
        $this->requireAuth();
        $tenantId = $this->getTenantId();
        $summary = $this->esgModel->getDashboardSummary($tenantId);
        $metrics = $this->esgModel->readAll($tenantId);
        $targets = $this->esgModel->getTargets($tenantId);

        if ($this->isAjax()) {
            $this->json(['success' => true, 'data' => compact('summary', 'targets')]);
            return;
        }

        require 'app/views/layout/header.php';
        require 'app/views/esg/dashboard.php';
        require 'app/views/layout/footer.php';
    }
}
