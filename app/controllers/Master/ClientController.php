<?php

namespace Akti\Controllers\Master;

use Akti\Models\Master\TenantClient;
use Akti\Models\Master\Plan;
use Akti\Models\Master\AdminUser;

/**
 * Class ClientController.
 */
class ClientController extends MasterBaseController
{
    private TenantClient $clientModel;
    private Plan $planModel;

    /**
     * Construtor da classe ClientController.
     *
     * @param \PDO|null $db Conexão PDO com o banco de dados
     */
    public function __construct(?\PDO $db = null)
    {
        parent::__construct($db);
        $this->clientModel = new TenantClient($this->db);
        $this->planModel = new Plan($this->db);
    }

    /**
     * Exibe a página de listagem.
     * @return void
     */
    public function index(): void
    {
        $this->requireMasterAuth();
        $clients = $this->clientModel->readAll();
        $this->renderMaster('clients/index', compact('clients'));
    }

    /**
     * Cria um novo registro no banco de dados.
     * @return void
     */
    public function create(): void
    {
        $this->requireMasterAuth();
        $plans = $this->planModel->readActive();
        $this->renderMaster('clients/create', compact('plans'));
    }

    /**
     * Processa e armazena um novo registro.
     * @return void
     */
    public function store(): void
    {
        $this->requireMasterAuth();

        $subdomain = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', trim($_POST['subdomain'] ?? '')));
        $dbName = 'akti_' . $subdomain;

        $data = [
            'plan_id'         => !empty($_POST['plan_id']) ? (int)$_POST['plan_id'] : null,
            'client_name'     => trim($_POST['client_name'] ?? ''),
            'subdomain'       => $subdomain,
            'db_host'         => trim($_POST['db_host'] ?? 'localhost'),
            'db_port'         => (int)($_POST['db_port'] ?? 3306),
            'db_name'         => $dbName,
            'db_user'         => trim($_POST['db_user'] ?? ''),
            'db_password'     => $_POST['db_password'] ?? '',
            'db_charset'      => trim($_POST['db_charset'] ?? 'utf8mb4'),
            'max_users'       => $_POST['max_users'] !== '' ? (int)$_POST['max_users'] : null,
            'max_products'    => $_POST['max_products'] !== '' ? (int)$_POST['max_products'] : null,
            'max_warehouses'  => $_POST['max_warehouses'] !== '' ? (int)$_POST['max_warehouses'] : null,
            'max_price_tables'=> $_POST['max_price_tables'] !== '' ? (int)$_POST['max_price_tables'] : null,
            'max_sectors'     => $_POST['max_sectors'] !== '' ? (int)$_POST['max_sectors'] : null,
            'is_active'       => isset($_POST['is_active']),
        ];

        if ($data['plan_id']) {
            $plan = $this->planModel->readOne($data['plan_id']);
            if ($plan) {
                $data['max_users']       = $plan['max_users'];
                $data['max_products']    = $plan['max_products'];
                $data['max_warehouses']  = $plan['max_warehouses'];
                $data['max_price_tables']= $plan['max_price_tables'];
                $data['max_sectors']     = $plan['max_sectors'];
            }
        }

        if (empty($data['client_name']) || empty($data['subdomain'])) {
            $_SESSION['error'] = 'Nome do cliente e subdomínio são obrigatórios.';
            $this->redirect('?page=master_clients&action=create');
        }

        if ($this->clientModel->findBySubdomain($data['subdomain'])) {
            $_SESSION['error'] = 'Este subdomínio já está em uso.';
            $this->redirect('?page=master_clients&action=create');
        }

        if ($this->clientModel->findByDbName($data['db_name'])) {
            $_SESSION['error'] = 'Este banco de dados já existe.';
            $this->redirect('?page=master_clients&action=create');
        }

        $provisionResult = null;
        if (isset($_POST['create_database'])) {
            $provisionResult = $this->clientModel->provisionDatabase(
                $data['db_host'],
                $data['db_port'],
                $data['db_name'],
                $data['db_user'],
                $data['db_password'],
                $data['db_charset']
            );

            if (!$provisionResult['success']) {
                $_SESSION['error'] = $provisionResult['message'];
                $this->redirect('?page=master_clients&action=create');
            }
        }

        $id = $this->clientModel->create($data);

        $userResult = null;
        if (isset($_POST['create_first_user']) && !empty($_POST['first_user_name']) && !empty($_POST['first_user_email']) && !empty($_POST['first_user_password'])) {
            $masterCreds = \TenantManager::getMasterConfig();
            $userResult = $this->clientModel->createTenantUser(
                $data['db_host'],
                $data['db_port'],
                $data['db_name'],
                $masterCreds['username'],
                $masterCreds['password'],
                $data['db_charset'],
                [
                    'name'     => trim($_POST['first_user_name']),
                    'email'    => trim($_POST['first_user_email']),
                    'password' => $_POST['first_user_password'],
                    'phone'    => trim($_POST['first_user_phone'] ?? ''),
                    'is_admin' => isset($_POST['first_user_is_admin']) ? 1 : 0,
                ]
            );
        }

        $details = "Cliente '{$data['client_name']}' criado (subdomínio: {$data['subdomain']})";
        if ($provisionResult) {
            $details .= ' | ' . $provisionResult['message'];
        }
        if (isset($userResult) && $userResult['success']) {
            $details .= ' | Primeiro usuário criado: ' . trim($_POST['first_user_email']);
        }
        $this->logAction('create_client', 'client', (int)$id, $details);

        $successMsg = 'Cliente criado com sucesso!';
        if ($provisionResult) {
            $successMsg .= ' ' . $provisionResult['message'];
        }
        if (isset($userResult)) {
            $successMsg .= $userResult['success'] ? ' Primeiro usuário criado.' : ' Erro ao criar usuário: ' . $userResult['message'];
        }

        $_SESSION['success'] = $successMsg;
        $this->redirect('?page=master_clients');
    }

 /**
  * Edit.
  * @return void
  */
    public function edit(): void
    {
        $this->requireMasterAuth();

        $id = (int)($_GET['id'] ?? 0);
        $client = $this->clientModel->readOne($id);

        if (!$client) {
            $_SESSION['error'] = 'Cliente não encontrado.';
            $this->redirect('?page=master_clients');
        }

        $plans = $this->planModel->readActive();
        $this->renderMaster('clients/edit', compact('client', 'plans'));
    }

 /**
  * Update.
  * @return void
  */
    public function update(): void
    {
        $this->requireMasterAuth();

        $id = (int)($_POST['id'] ?? 0);
        $current = $this->clientModel->readOne($id);

        if (!$current) {
            $_SESSION['error'] = 'Cliente não encontrado.';
            $this->redirect('?page=master_clients');
        }

        $data = [
            'plan_id'         => !empty($_POST['plan_id']) ? (int)$_POST['plan_id'] : null,
            'client_name'     => trim($_POST['client_name'] ?? ''),
            'subdomain'       => $current['subdomain'],
            'db_host'         => trim($_POST['db_host'] ?? 'localhost'),
            'db_port'         => (int)($_POST['db_port'] ?? 3306),
            'db_name'         => $current['db_name'],
            'db_user'         => trim($_POST['db_user'] ?? ''),
            'db_password'     => $_POST['db_password'] ?? '',
            'db_charset'      => trim($_POST['db_charset'] ?? 'utf8mb4'),
            'max_users'       => $_POST['max_users'] !== '' ? (int)$_POST['max_users'] : null,
            'max_products'    => $_POST['max_products'] !== '' ? (int)$_POST['max_products'] : null,
            'max_warehouses'  => $_POST['max_warehouses'] !== '' ? (int)$_POST['max_warehouses'] : null,
            'max_price_tables'=> $_POST['max_price_tables'] !== '' ? (int)$_POST['max_price_tables'] : null,
            'max_sectors'     => $_POST['max_sectors'] !== '' ? (int)$_POST['max_sectors'] : null,
            'is_active'       => isset($_POST['is_active']),
        ];

        if ($data['plan_id']) {
            $plan = $this->planModel->readOne($data['plan_id']);
            if ($plan) {
                $data['max_users']       = $plan['max_users'];
                $data['max_products']    = $plan['max_products'];
                $data['max_warehouses']  = $plan['max_warehouses'];
                $data['max_price_tables']= $plan['max_price_tables'];
                $data['max_sectors']     = $plan['max_sectors'];
            }
        }

        if (empty($data['client_name'])) {
            $_SESSION['error'] = 'Nome do cliente é obrigatório.';
            $this->redirect("?page=master_clients&action=edit&id={$id}");
        }

        $this->clientModel->update($id, $data);

        $this->logAction('update_client', 'client', $id, "Cliente '{$data['client_name']}' atualizado");

        $_SESSION['success'] = 'Cliente atualizado com sucesso!';
        $this->redirect('?page=master_clients');
    }

 /**
  * Toggle active.
  * @return void
  */
    public function toggleActive(): void
    {
        $this->requireMasterAuth();

        $id = (int)($_GET['id'] ?? 0);
        $client = $this->clientModel->readOne($id);

        if ($client) {
            $this->clientModel->toggleActive($id);
            $status = $client['is_active'] ? 'desativado' : 'ativado';
            $this->logAction('toggle_client', 'client', $id, "Cliente '{$client['client_name']}' {$status}");
            $_SESSION['success'] = "Cliente {$status} com sucesso!";
        }

        $this->redirect('?page=master_clients');
    }

 /**
  * Delete.
  * @return void
  */
    public function delete(): void
    {
        $this->requireMasterAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Método inválido para exclusão.';
            $this->redirect('?page=master_clients');
        }

        $id = (int)($_POST['id'] ?? 0);
        $client = $this->clientModel->readOne($id);

        if (!$client) {
            $_SESSION['error'] = 'Cliente não encontrado.';
            $this->redirect('?page=master_clients');
        }

        $confirmDbName = trim($_POST['confirm_db_name'] ?? '');
        if ($confirmDbName !== $client['db_name']) {
            $_SESSION['error'] = 'O nome do banco de dados digitado não confere. Exclusão cancelada.';
            $this->redirect('?page=master_clients');
        }

        $adminPassword = $_POST['admin_password'] ?? '';
        $adminModel = new AdminUser($this->db);
        $admin = $adminModel->findById($this->getMasterAdminId());

        if (!$admin || !password_verify($adminPassword, $admin['password'])) {
            $_SESSION['error'] = 'Senha do administrador incorreta. Exclusão cancelada.';
            $this->redirect('?page=master_clients');
        }

        $dropResult = $this->clientModel->dropDatabase(
            $client['db_host'],
            $client['db_port'],
            $client['db_name'],
            $client['db_user']
        );

        $this->clientModel->delete($id);

        $details = "Cliente '{$client['client_name']}' excluído (subdomínio: {$client['subdomain']}, banco: {$client['db_name']})";
        if ($dropResult['success']) {
            $details .= ' | ' . $dropResult['message'];
        } else {
            $details .= ' | FALHA ao remover banco: ' . $dropResult['message'];
        }
        $this->logAction('delete_client', 'client', $id, $details);

        if ($dropResult['success']) {
            $_SESSION['success'] = "Cliente '{$client['client_name']}' e banco de dados '{$client['db_name']}' excluídos com sucesso!";
        } else {
            $_SESSION['success'] = "Cliente '{$client['client_name']}' excluído do sistema, porém houve um erro ao remover o banco: " . $dropResult['message'];
        }

        $this->redirect('?page=master_clients');
    }

 /**
  * Create tenant user.
  * @return void
  */
    public function createTenantUser(): void
    {
        $this->requireMasterAuth();

        $clientId = (int)($_POST['client_id'] ?? 0);
        $client = $this->clientModel->readOne($clientId);

        if (!$client) {
            $_SESSION['error'] = 'Cliente não encontrado.';
            $this->redirect('?page=master_clients');
        }

        $userName  = trim($_POST['user_name'] ?? '');
        $userEmail = trim($_POST['user_email'] ?? '');
        $userPass  = $_POST['user_password'] ?? '';
        $userPhone = trim($_POST['user_phone'] ?? '');
        $isAdmin   = isset($_POST['user_is_admin']) ? 1 : 0;

        if (empty($userName) || empty($userEmail) || empty($userPass)) {
            $_SESSION['error'] = 'Nome, e-mail e senha do usuário são obrigatórios.';
            $this->redirect("?page=master_clients&action=edit&id={$clientId}");
        }

        if (strlen($userPass) < 6) {
            $_SESSION['error'] = 'A senha deve ter pelo menos 6 caracteres.';
            $this->redirect("?page=master_clients&action=edit&id={$clientId}");
        }

        $masterCreds = \TenantManager::getMasterConfig();
        $result = $this->clientModel->createTenantUser(
            $client['db_host'],
            $client['db_port'],
            $client['db_name'],
            $masterCreds['username'],
            $masterCreds['password'],
            $client['db_charset'],
            [
                'name'     => $userName,
                'email'    => $userEmail,
                'password' => $userPass,
                'phone'    => $userPhone,
                'is_admin' => $isAdmin,
            ]
        );

        if ($result['success']) {
            $this->logAction('create_tenant_user', 'client', $clientId, "Usuário '{$userEmail}' criado no banco '{$client['db_name']}'");
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }

        $this->redirect("?page=master_clients&action=edit&id={$clientId}");
    }

 /**
  * Get plan limits.
  * @return void
  */
    public function getPlanLimits(): void
    {
        $this->requireMasterAuth();

        $planId = (int)($_GET['plan_id'] ?? 0);

        if ($planId) {
            $plan = $this->planModel->readOne($planId);
            if ($plan) {
                $this->json(['success' => true, 'plan' => $plan]);
            }
        }

        $this->json(['success' => false]);
    }
}
