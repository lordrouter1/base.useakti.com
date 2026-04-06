<?php
/**
 * Controller: ClientController
 * CRUD de Clientes (Tenants)
 */

class ClientController
{
    private $db;
    private $clientModel;
    private $planModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->clientModel = new TenantClient($db);
        $this->planModel = new Plan($db);
    }

    public function index()
    {
        $clients = $this->clientModel->readAll();
        require_once __DIR__ . '/../views/clients/index.php';
    }

    public function create()
    {
        $plans = $this->planModel->readActive();
        require_once __DIR__ . '/../views/clients/create.php';
    }

    public function store()
    {
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

        // Se selecionou um plano, buscar limites do plano
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

        // Validações
        if (empty($data['client_name']) || empty($data['subdomain'])) {
            $_SESSION['error'] = 'Nome do cliente e subdomínio são obrigatórios.';
            header('Location: ?page=clients&action=create');
            exit;
        }

        // Verificar subdomínio duplicado
        if ($this->clientModel->findBySubdomain($data['subdomain'])) {
            $_SESSION['error'] = 'Este subdomínio já está em uso.';
            header('Location: ?page=clients&action=create');
            exit;
        }

        // Verificar db_name duplicado
        if ($this->clientModel->findByDbName($data['db_name'])) {
            $_SESSION['error'] = 'Este banco de dados já existe.';
            header('Location: ?page=clients&action=create');
            exit;
        }

        // Provisionar banco de dados se solicitado
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
                header('Location: ?page=clients&action=create');
                exit;
            }
        }

        $id = $this->clientModel->create($data);

        // Criar primeiro usuário no banco do cliente se solicitado
        if (isset($_POST['create_first_user']) && !empty($_POST['first_user_name']) && !empty($_POST['first_user_email']) && !empty($_POST['first_user_password'])) {
            $userResult = $this->clientModel->createTenantUser(
                $data['db_host'],
                $data['db_port'],
                $data['db_name'],
                DB_USER,
                DB_PASS,
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

        $log = new AdminLog($this->db);
        $details = "Cliente '{$data['client_name']}' criado (subdomínio: {$data['subdomain']})";
        if ($provisionResult) {
            $details .= ' | ' . $provisionResult['message'];
        }
        if (isset($userResult) && $userResult['success']) {
            $details .= ' | Primeiro usuário criado: ' . trim($_POST['first_user_email']);
        }
        $log->log($_SESSION['admin_id'], 'create_client', 'client', $id, $details);

        $successMsg = 'Cliente criado com sucesso!';
        if ($provisionResult) {
            $successMsg .= ' ' . $provisionResult['message'];
        }
        if (isset($userResult)) {
            $successMsg .= $userResult['success'] ? ' Primeiro usuário criado.' : ' Erro ao criar usuário: ' . $userResult['message'];
        }

        $_SESSION['success'] = $successMsg;
        header('Location: ?page=clients');
        exit;
    }

    public function edit()
    {
        $id = (int)($_GET['id'] ?? 0);
        $client = $this->clientModel->readOne($id);

        if (!$client) {
            $_SESSION['error'] = 'Cliente não encontrado.';
            header('Location: ?page=clients');
            exit;
        }

        $plans = $this->planModel->readActive();
        require_once __DIR__ . '/../views/clients/edit.php';
    }

    public function update()
    {
        $id = (int)($_POST['id'] ?? 0);
        $current = $this->clientModel->readOne($id);

        if (!$current) {
            $_SESSION['error'] = 'Cliente não encontrado.';
            header('Location: ?page=clients');
            exit;
        }

        // IMPORTANTE: subdomain e db_name NÃO podem ser alterados após o cadastro
        // Usamos sempre os valores do registro atual do banco
        $data = [
            'plan_id'         => !empty($_POST['plan_id']) ? (int)$_POST['plan_id'] : null,
            'client_name'     => trim($_POST['client_name'] ?? ''),
            'subdomain'       => $current['subdomain'],  // Preservar original
            'db_host'         => trim($_POST['db_host'] ?? 'localhost'),
            'db_port'         => (int)($_POST['db_port'] ?? 3306),
            'db_name'         => $current['db_name'],    // Preservar original
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

        // Se selecionou um plano, buscar limites do plano
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

        // Validações
        if (empty($data['client_name'])) {
            $_SESSION['error'] = 'Nome do cliente é obrigatório.';
            header("Location: ?page=clients&action=edit&id={$id}");
            exit;
        }

        $this->clientModel->update($id, $data);

        $log = new AdminLog($this->db);
        $log->log($_SESSION['admin_id'], 'update_client', 'client', $id, "Cliente '{$data['client_name']}' atualizado");

        $_SESSION['success'] = 'Cliente atualizado com sucesso!';
        header('Location: ?page=clients');
        exit;
    }

    public function toggleActive()
    {
        $id = (int)($_GET['id'] ?? 0);
        $client = $this->clientModel->readOne($id);

        if ($client) {
            $this->clientModel->toggleActive($id);
            $status = $client['is_active'] ? 'desativado' : 'ativado';

            $log = new AdminLog($this->db);
            $log->log($_SESSION['admin_id'], 'toggle_client', 'client', $id, "Cliente '{$client['client_name']}' {$status}");

            $_SESSION['success'] = "Cliente {$status} com sucesso!";
        }

        header('Location: ?page=clients');
        exit;
    }

    public function delete()
    {
        // Exigir POST para exclusão
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Método inválido para exclusão.';
            header('Location: ?page=clients');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $client = $this->clientModel->readOne($id);

        if (!$client) {
            $_SESSION['error'] = 'Cliente não encontrado.';
            header('Location: ?page=clients');
            exit;
        }

        // Validar se digitou o nome do banco corretamente
        $confirmDbName = trim($_POST['confirm_db_name'] ?? '');
        if ($confirmDbName !== $client['db_name']) {
            $_SESSION['error'] = 'O nome do banco de dados digitado não confere. Exclusão cancelada.';
            header('Location: ?page=clients');
            exit;
        }

        // Validar senha do administrador logado
        $adminPassword = $_POST['admin_password'] ?? '';
        $adminModel = new AdminUser($this->db);
        $admin = $adminModel->findById($_SESSION['admin_id']);

        if (!$admin || !password_verify($adminPassword, $admin['password'])) {
            $_SESSION['error'] = 'Senha do administrador incorreta. Exclusão cancelada.';
            header('Location: ?page=clients');
            exit;
        }

        // Tentar dropar o banco de dados do cliente
        $dropResult = $this->clientModel->dropDatabase(
            $client['db_host'],
            $client['db_port'],
            $client['db_name'],
            $client['db_user']
        );

        // Excluir o registro do cliente no master
        $this->clientModel->delete($id);

        $log = new AdminLog($this->db);
        $details = "Cliente '{$client['client_name']}' excluído (subdomínio: {$client['subdomain']}, banco: {$client['db_name']})";
        if ($dropResult['success']) {
            $details .= ' | ' . $dropResult['message'];
        } else {
            $details .= ' | FALHA ao remover banco: ' . $dropResult['message'];
        }
        $log->log($_SESSION['admin_id'], 'delete_client', 'client', $id, $details);

        if ($dropResult['success']) {
            $_SESSION['success'] = "Cliente '{$client['client_name']}' e banco de dados '{$client['db_name']}' excluídos com sucesso!";
        } else {
            $_SESSION['success'] = "Cliente '{$client['client_name']}' excluído do sistema, porém houve um erro ao remover o banco: " . $dropResult['message'];
        }

        header('Location: ?page=clients');
        exit;
    }

    /**
     * Cria um usuário diretamente no banco de dados do cliente
     */
    public function createTenantUser()
    {
        $clientId = (int)($_POST['client_id'] ?? 0);
        $client = $this->clientModel->readOne($clientId);

        if (!$client) {
            $_SESSION['error'] = 'Cliente não encontrado.';
            header('Location: ?page=clients');
            exit;
        }

        $userName  = trim($_POST['user_name'] ?? '');
        $userEmail = trim($_POST['user_email'] ?? '');
        $userPass  = $_POST['user_password'] ?? '';
        $userPhone = trim($_POST['user_phone'] ?? '');
        $isAdmin   = isset($_POST['user_is_admin']) ? 1 : 0;

        // Validações básicas
        if (empty($userName) || empty($userEmail) || empty($userPass)) {
            $_SESSION['error'] = 'Nome, e-mail e senha do usuário são obrigatórios.';
            header("Location: ?page=clients&action=edit&id={$clientId}");
            exit;
        }

        if (strlen($userPass) < 6) {
            $_SESSION['error'] = 'A senha deve ter pelo menos 6 caracteres.';
            header("Location: ?page=clients&action=edit&id={$clientId}");
            exit;
        }

        $result = $this->clientModel->createTenantUser(
            $client['db_host'],
            $client['db_port'],
            $client['db_name'],
            DB_USER,
            DB_PASS,
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
            $log = new AdminLog($this->db);
            $log->log(
                $_SESSION['admin_id'],
                'create_tenant_user',
                'client',
                $clientId,
                "Usuário '{$userEmail}' criado no banco '{$client['db_name']}'"
            );
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }

        header("Location: ?page=clients&action=edit&id={$clientId}");
        exit;
    }

    /**
     * Retorna dados do plano via AJAX
     */
    public function getPlanLimits()
    {
        header('Content-Type: application/json');
        $planId = (int)($_GET['plan_id'] ?? 0);

        if ($planId) {
            $plan = $this->planModel->readOne($planId);
            if ($plan) {
                echo json_encode([
                    'success' => true,
                    'plan' => $plan
                ]);
                exit;
            }
        }

        echo json_encode(['success' => false]);
        exit;
    }
}
