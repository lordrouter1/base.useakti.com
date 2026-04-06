<?php
/**
 * Controller: PlanController
 * CRUD de Planos
 */

class PlanController
{
    private $db;
    private $planModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->planModel = new Plan($db);
    }

    public function index()
    {
        $plans = $this->planModel->readAll();
        require_once __DIR__ . '/../views/plans/index.php';
    }

    public function create()
    {
        require_once __DIR__ . '/../views/plans/create.php';
    }

    public function store()
    {
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
            header('Location: ?page=plans&action=create');
            exit;
        }

        $id = $this->planModel->create($data);

        $log = new AdminLog($this->db);
        $log->log($_SESSION['admin_id'], 'create_plan', 'plan', $id, "Plano '{$data['plan_name']}' criado");

        $_SESSION['success'] = 'Plano criado com sucesso!';
        header('Location: ?page=plans');
        exit;
    }

    public function edit()
    {
        $id = (int)($_GET['id'] ?? 0);
        $plan = $this->planModel->readOne($id);

        if (!$plan) {
            $_SESSION['error'] = 'Plano não encontrado.';
            header('Location: ?page=plans');
            exit;
        }

        require_once __DIR__ . '/../views/plans/edit.php';
    }

    public function update()
    {
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
            header("Location: ?page=plans&action=edit&id={$id}");
            exit;
        }

        $this->planModel->update($id, $data);

        // Atualizar limites dos clientes vinculados a este plano se checkbox marcado
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

        $log = new AdminLog($this->db);
        $log->log($_SESSION['admin_id'], 'update_plan', 'plan', $id, "Plano '{$data['plan_name']}' atualizado");

        $_SESSION['success'] = 'Plano atualizado com sucesso!';
        header('Location: ?page=plans');
        exit;
    }

    public function delete()
    {
        $id = (int)($_GET['id'] ?? 0);
        $plan = $this->planModel->readOne($id);

        if (!$plan) {
            $_SESSION['error'] = 'Plano não encontrado.';
            header('Location: ?page=plans');
            exit;
        }

        $result = $this->planModel->delete($id);

        if (!$result) {
            $_SESSION['error'] = 'Não é possível excluir o plano pois existem clientes vinculados.';
            header('Location: ?page=plans');
            exit;
        }

        $log = new AdminLog($this->db);
        $log->log($_SESSION['admin_id'], 'delete_plan', 'plan', $id, "Plano '{$plan['plan_name']}' excluído");

        $_SESSION['success'] = 'Plano excluído com sucesso!';
        header('Location: ?page=plans');
        exit;
    }
}
