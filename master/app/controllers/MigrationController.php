<?php
/**
 * Controller: MigrationController
 * Gerencia migrações de banco de dados e usuários cross-tenant
 */

class MigrationController
{
    private $db;
    private $migrationModel;
    private $clientModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->migrationModel = new Migration($db);
        $this->clientModel = new TenantClient($db);
    }

    // =========================================================================
    // MIGRATIONS
    // =========================================================================

    /**
     * Página principal de migrações — comparação de schemas
     */
    public function index()
    {
        $initBase = defined('CLIENT_DB_INIT_BASE') ? CLIENT_DB_INIT_BASE : 'akti_init_base';
        $tenantDbs = $this->migrationModel->listTenantDatabases();
        $tenants = $this->migrationModel->getRegisteredTenants();

        // Mapear db_name -> client_name
        $dbClientMap = [];
        foreach ($tenants as $t) {
            $dbClientMap[$t['db_name']] = $t;
        }

        // Comparar schemas
        $comparisons = $this->migrationModel->compareAllTenants();

        // Contagem de tabelas do banco base
        try {
            $baseTables = $this->migrationModel->getTableCount($initBase);
        } catch (Exception $e) {
            $baseTables = '?';
        }

        // Histórico recente
        $history = $this->migrationModel->getMigrationHistory(20);

        require_once __DIR__ . '/../views/migrations/index.php';
    }

    /**
     * Exibe detalhes de comparação de um banco específico (AJAX)
     */
    public function compareDetail()
    {
        header('Content-Type: application/json; charset=utf-8');
        $dbName = $_GET['db'] ?? '';

        if (empty($dbName)) {
            echo json_encode(['success' => false, 'message' => 'Banco não informado']);
            exit;
        }

        try {
            $diff = $this->migrationModel->compareSchema($dbName);
            echo json_encode(['success' => true, 'db' => $dbName, 'diff' => $diff]);
        } catch (Exception $e) {
            error_log('[MigrationController::compareSchema] ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro interno ao comparar schema.']);
        }
        exit;
    }

    /**
     * Aplica SQL nos bancos selecionados
     */
    public function apply()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=migrations');
            exit;
        }

        $sql = trim($_POST['sql_content'] ?? '');
        $migrationName = trim($_POST['migration_name'] ?? 'Migração manual ' . date('Y-m-d H:i'));
        $selectedDbs = $_POST['selected_dbs'] ?? [];
        $applyToInitBase = isset($_POST['apply_to_init_base']);
        $adminId = $_SESSION['admin_id'] ?? null;

        if (empty($sql)) {
            $_SESSION['error'] = 'O conteúdo SQL não pode estar vazio.';
            header('Location: ?page=migrations');
            exit;
        }

        if (empty($selectedDbs)) {
            $_SESSION['error'] = 'Selecione pelo menos um banco de dados.';
            header('Location: ?page=migrations');
            exit;
        }

        // Aplicar no banco de referência primeiro (se solicitado)
        $initBaseResult = null;
        if ($applyToInitBase) {
            $initBaseResult = $this->migrationModel->executeSqlOnInitBase($sql);
        }

        // Aplicar nos bancos selecionados
        $results = $this->migrationModel->executeSqlOnAllTenants($sql, $migrationName, $adminId, $selectedDbs);

        // Log de admin
        $log = new AdminLog($this->db);
        $totalDbs = count($selectedDbs);
        $successCount = count(array_filter($results, fn($r) => $r['status'] === 'success'));
        $log->log($adminId, 'apply_migration', 'migration', null, 
            "Migração '{$migrationName}' aplicada em {$successCount}/{$totalDbs} bancos");

        // Armazenar resultados na sessão para exibir
        $_SESSION['migration_results'] = [
            'name'       => $migrationName,
            'init_base'  => $initBaseResult,
            'databases'  => $results,
            'sql_preview'=> mb_substr($sql, 0, 500),
        ];

        $_SESSION['success'] = "Migração '{$migrationName}' processada em {$totalDbs} banco(s).";
        header('Location: ?page=migrations&action=results');
        exit;
    }

    /**
     * Exibe resultados da última migração
     */
    public function results()
    {
        $migrationResults = $_SESSION['migration_results'] ?? null;
        if (!$migrationResults) {
            header('Location: ?page=migrations');
            exit;
        }
        // Manter nos resultados para exibição, mas limpar depois de renderizar
        require_once __DIR__ . '/../views/migrations/results.php';
        unset($_SESSION['migration_results']);
    }

    // =========================================================================
    // TENANT USERS
    // =========================================================================

    /**
     * Lista todos os usuários de todos os bancos tenant
     */
    public function users()
    {
        $allUsers = $this->migrationModel->listAllTenantUsers();
        $tenants = $this->migrationModel->getRegisteredTenants();

        require_once __DIR__ . '/../views/migrations/users.php';
    }

    /**
     * Cria um usuário em um banco tenant (via AJAX ou POST)
     */
    public function createUser()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=migrations&action=users');
            exit;
        }

        $dbName    = trim($_POST['db_name'] ?? '');
        $userName  = trim($_POST['user_name'] ?? '');
        $userEmail = trim($_POST['user_email'] ?? '');
        $userPass  = $_POST['user_password'] ?? '';
        $userPhone = trim($_POST['user_phone'] ?? '');
        $isAdmin   = isset($_POST['user_is_admin']) ? 1 : 0;

        // Validações
        if (empty($dbName) || empty($userName) || empty($userEmail) || empty($userPass)) {
            $_SESSION['error'] = 'Banco, nome, e-mail e senha são obrigatórios.';
            header('Location: ?page=migrations&action=users');
            exit;
        }

        if (strlen($userPass) < 6) {
            $_SESSION['error'] = 'A senha deve ter pelo menos 6 caracteres.';
            header('Location: ?page=migrations&action=users');
            exit;
        }

        // Buscar tenant para obter dados de conexão
        $tenant = $this->clientModel->findByDbName($dbName);
        if (!$tenant) {
            $_SESSION['error'] = "Banco '{$dbName}' não encontrado na tabela de clientes.";
            header('Location: ?page=migrations&action=users');
            exit;
        }

        $result = $this->clientModel->createTenantUser(
            $tenant['db_host'],
            $tenant['db_port'],
            $tenant['db_name'],
            DB_USER, DB_PASS,
            $tenant['db_charset'] ?: 'utf8mb4',
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
            $log->log($_SESSION['admin_id'], 'create_tenant_user', 'user', null,
                "Usuário '{$userEmail}' criado no banco '{$dbName}' via painel de migrations");
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }

        header('Location: ?page=migrations&action=users');
        exit;
    }

    /**
     * Toggle ativo/inativo de um usuário tenant (AJAX)
     */
    public function toggleUser()
    {
        header('Content-Type: application/json; charset=utf-8');
        $dbName = $_POST['db_name'] ?? '';
        $userId = (int)($_POST['user_id'] ?? 0);

        if (empty($dbName) || !$userId) {
            echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos']);
            exit;
        }

        try {
            $this->migrationModel->toggleTenantUser($dbName, $userId);
            echo json_encode(['success' => true, 'message' => 'Status do usuário alterado']);
        } catch (Exception $e) {
            error_log('[MigrationController::toggleTenantUser] ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro interno ao alterar usuário.']);
        }
        exit;
    }

    /**
     * Lista usuários de um banco específico (AJAX)
     */
    public function dbUsers()
    {
        header('Content-Type: application/json; charset=utf-8');
        $dbName = $_GET['db'] ?? '';

        if (empty($dbName)) {
            echo json_encode(['success' => false, 'message' => 'Banco não informado']);
            exit;
        }

        try {
            $users = $this->migrationModel->listUsersFromDatabase($dbName);
            echo json_encode(['success' => true, 'users' => $users]);
        } catch (Exception $e) {
            error_log('[MigrationController::dbUsers] ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro interno ao listar usuários.']);
        }
        exit;
    }
}
