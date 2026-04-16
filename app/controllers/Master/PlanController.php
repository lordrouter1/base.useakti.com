<?php

namespace Akti\Controllers\Master;

use Akti\Models\Master\Plan;
use Akti\Models\Master\TenantClient;
use Akti\Models\Master\AdminLog;

/**
 * Class PlanController.
 */
class PlanController extends MasterBaseController
{
    private Plan $planModel;

    /**
     * Construtor da classe PlanController.
     *
     * @param \PDO|null $db Conexão PDO com o banco de dados
     */
    public function __construct(?\PDO $db = null)
    {
        parent::__construct($db);
        $this->planModel = new Plan($this->db);
    }

    /**
     * Exibe a página de listagem.
     * @return void
     */
    public function index(): void
    {
        $this->requireMasterAuth();
        $plans = $this->planModel->readAll();
        $this->renderMaster('plans/index', compact('plans'));
    }

    /**
     * Cria um novo registro no banco de dados.
     * @return void
     */
    public function create(): void
    {
        $this->requireMasterAuth();
        $this->renderMaster('plans/create');
    }

    /**
     * Processa e armazena um novo registro.
     * @return void
     */
    public function store(): void
    {
        $this->requireMasterAuth();

        $data = [
            'plan_name'       => trim($_POST['plan_name'] ?? ''),
            'description'     => trim($_POST['description'] ?? ''),
            'max_users'       => $_POST['max_users'] !== '' ? (int)$_POST['max_users'] : null,
            'max_products'    => $_POST['max_products'] !== '' ? (int)$_POST['max_products'] : null,
            'max_warehouses'  => $_POST['max_warehouses'] !== '' ? (int)$_POST['max_warehouses'] : null,
            'max_price_tables'=> $_POST['max_price_tables'] !== '' ? (int)$_POST['max_price_tables'] : null,
            'max_sectors'     => $_POST['max_sectors'] !== '' ? (int)$_POST['max_sectors'] : null,
            'price'           => (float)str_replace(',', '.', $_POST['price'] ?? '0'),
            'is_active'       => isset($_POST['is_active']),
        ];

        if (empty($data['plan_name'])) {
            $_SESSION['error'] = 'Nome do plano é obrigatório.';
            $this->redirect('?page=master_plans&action=create');
        }

        $id = $this->planModel->create($data);

        $this->logAction('create_plan', 'plan', (int)$id, "Plano '{$data['plan_name']}' criado");

        $_SESSION['success'] = 'Plano criado com sucesso!';
        $this->redirect('?page=master_plans');
    }

 /**
  * Edit.
  * @return void
  */
    public function edit(): void
    {
        $this->requireMasterAuth();

        $id = (int)($_GET['id'] ?? 0);
        $plan = $this->planModel->readOne($id);

        if (!$plan) {
            $_SESSION['error'] = 'Plano não encontrado.';
            $this->redirect('?page=master_plans');
        }

        $this->renderMaster('plans/edit', compact('plan'));
    }

 /**
  * Update.
  * @return void
  */
    public function update(): void
    {
        $this->requireMasterAuth();

        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'plan_name'       => trim($_POST['plan_name'] ?? ''),
            'description'     => trim($_POST['description'] ?? ''),
            'max_users'       => $_POST['max_users'] !== '' ? (int)$_POST['max_users'] : null,
            'max_products'    => $_POST['max_products'] !== '' ? (int)$_POST['max_products'] : null,
            'max_warehouses'  => $_POST['max_warehouses'] !== '' ? (int)$_POST['max_warehouses'] : null,
            'max_price_tables'=> $_POST['max_price_tables'] !== '' ? (int)$_POST['max_price_tables'] : null,
            'max_sectors'     => $_POST['max_sectors'] !== '' ? (int)$_POST['max_sectors'] : null,
            'price'           => (float)str_replace(',', '.', $_POST['price'] ?? '0'),
            'is_active'       => isset($_POST['is_active']),
        ];

        if (empty($data['plan_name'])) {
            $_SESSION['error'] = 'Nome do plano é obrigatório.';
            $this->redirect("?page=master_plans&action=edit&id={$id}");
        }

        $this->planModel->update($id, $data);

        if (isset($_POST['sync_clients'])) {
            $plan = $this->planModel->readOne($id);
            $clientModel = new TenantClient($this->db);
            $clients = $clientModel->readAll();
            foreach ($clients as $client) {
                if ($client['plan_id'] == $id) {
                    $clientModel->updateLimitsFromPlan($client['id'], $plan);
                }
            }
        }

        $this->logAction('update_plan', 'plan', $id, "Plano '{$data['plan_name']}' atualizado");

        $_SESSION['success'] = 'Plano atualizado com sucesso!';
        $this->redirect('?page=master_plans');
    }

 /**
  * Delete.
  * @return void
  */
    public function delete(): void
    {
        $this->requireMasterAuth();

        $id = (int)($_GET['id'] ?? 0);
        $plan = $this->planModel->readOne($id);

        if (!$plan) {
            $_SESSION['error'] = 'Plano não encontrado.';
            $this->redirect('?page=master_plans');
        }

        $result = $this->planModel->delete($id);

        if (!$result) {
            $_SESSION['error'] = 'Não é possível excluir o plano pois existem clientes vinculados.';
            $this->redirect('?page=master_plans');
        }

        $this->logAction('delete_plan', 'plan', $id, "Plano '{$plan['plan_name']}' excluído");

        $_SESSION['success'] = 'Plano excluído com sucesso!';
        $this->redirect('?page=master_plans');
    }
}
