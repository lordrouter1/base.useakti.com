<?php

namespace Akti\Controllers\Master;

use Akti\Models\Master\Migration;
use Akti\Models\Master\TenantClient;

/**
 * Class MigrationController.
 */
class MigrationController extends MasterBaseController
{
    private const MASTER_DB = 'akti_master';

    private Migration $migrationModel;
    private TenantClient $clientModel;

    /**
     * Construtor da classe MigrationController.
     *
     * @param \PDO|null $db Conexão PDO com o banco de dados
     */
    public function __construct(?\PDO $db = null)
    {
        parent::__construct($db);
        $this->migrationModel = new Migration($this->db);
        $this->clientModel = new TenantClient($this->db);
    }

    /**
     * Valida se o nome de banco pertence a um tenant registrado.
     */
    private function isRegisteredDb(string $dbName): bool
    {
        return !empty($dbName) && $this->clientModel->findByDbName($dbName) !== false;
    }

    /**
     * Separa akti_master dos bancos tenant selecionados e valida os tenants.
     * @return array{tenantDbs: array, applyToMaster: bool, invalidDbs: array}
     */
    private function separateAndValidateDbs(array $selectedDbs): array
    {
        $applyToMaster = in_array(self::MASTER_DB, $selectedDbs, true);
        $tenantDbs = array_values(array_filter($selectedDbs, fn($db) => $db !== self::MASTER_DB));

        $invalidDbs = [];
        if (!empty($tenantDbs)) {
            $registeredTenants = $this->migrationModel->getRegisteredTenants();
            $registeredDbNames = array_column($registeredTenants, 'db_name');
            $invalidDbs = array_diff($tenantDbs, $registeredDbNames);
        }

        return [
            'tenantDbs'     => $tenantDbs,
            'applyToMaster' => $applyToMaster,
            'invalidDbs'    => $invalidDbs,
        ];
    }

    /**
     * Executa SQL no akti_master e retorna resultado.
     */
    private function executeSqlOnMaster(string $sql): array
    {
        return $this->migrationModel->executeSqlOnDatabase(self::MASTER_DB, $sql);
    }

    /**
     * Exibe a página de listagem.
     * @return void
     */
    public function index(): void
    {
        $this->requireMasterAuth();

        $initBase = getenv('AKTI_MASTER_INIT_BASE') ?: 'akti_init_base';
        $tenantDbs = $this->migrationModel->listTenantDatabases();
        $tenants = $this->migrationModel->getRegisteredTenants();

        $dbClientMap = [];
        foreach ($tenants as $t) {
            $dbClientMap[$t['db_name']] = $t;
        }

        $comparisons = $this->migrationModel->compareAllTenants();
        $history = $this->migrationModel->getMigrationHistory(20);

        try {
            $baseTables = $this->migrationModel->getTableCount($initBase);
        } catch (\Exception $e) {
            $baseTables = '?';
        }

        // AUTO-001: Scan pending SQL files from /sql/ folder
        $pendingSqlFiles = $this->scanPendingSqlFiles();

        $this->renderMaster('migrations/index', compact('initBase', 'tenantDbs', 'tenants', 'dbClientMap', 'comparisons', 'history', 'pendingSqlFiles', 'baseTables'));
    }

    /**
     * Scan the /sql/ folder for pending migration files (not in /sql/prontos/).
     */
    private function scanPendingSqlFiles(): array
    {
        $sqlDir = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . '/sql';
        $prontosDir = $sqlDir . '/prontos';
        $files = [];

        if (!is_dir($sqlDir)) {
            return $files;
        }

        $entries = scandir($sqlDir);
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..' || $entry === 'prontos') {
                continue;
            }
            $fullPath = $sqlDir . '/' . $entry;
            if (is_file($fullPath) && strtolower(pathinfo($entry, PATHINFO_EXTENSION)) === 'sql') {
                $files[] = [
                    'name'     => $entry,
                    'size'     => filesize($fullPath),
                    'modified' => filemtime($fullPath),
                    'content'  => file_get_contents($fullPath),
                ];
            }
        }

        // Sort by name (chronological by naming convention)
        usort($files, fn($a, $b) => strcmp($a['name'], $b['name']));

        return $files;
    }

    /**
     * Preview content of a pending SQL file.
     */
    public function previewSqlFile(): void
    {
        $this->requireMasterAuth();

        $filename = basename($_GET['file'] ?? '');
        if (empty($filename)) {
            $this->json(['success' => false, 'message' => 'Arquivo não informado']);
        }

        $sqlDir = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . '/sql';
        $filePath = $sqlDir . '/' . $filename;

        if (!is_file($filePath) || strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'sql') {
            $this->json(['success' => false, 'message' => 'Arquivo não encontrado ou inválido']);
        }

        $this->json([
            'success' => true,
            'file'    => $filename,
            'content' => file_get_contents($filePath),
            'size'    => filesize($filePath),
        ]);
    }

    /**
     * Apply a single pending SQL file via AJAX (from the file list).
     */
    public function applySingleFile(): void
    {
        $this->requireMasterAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido']);
            return;
        }

        $filename = basename($_POST['sql_file'] ?? '');
        $selectedDbs = $_POST['selected_dbs'] ?? [];
        $applyToMaster = isset($_POST['apply_to_master']);
        $applyToInitBase = isset($_POST['apply_to_init_base']);
        $adminId = $this->getMasterAdminId();

        if (empty($filename)) {
            $this->json(['success' => false, 'message' => 'Nenhum arquivo SQL informado.']);
            return;
        }

        $sqlDir = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . '/sql';
        $filePath = $sqlDir . '/' . $filename;

        if (!is_file($filePath) || strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'sql') {
            $this->json(['success' => false, 'message' => 'Arquivo não encontrado: ' . $filename]);
            return;
        }

        $sql = file_get_contents($filePath);
        if (empty(trim($sql))) {
            $this->json(['success' => false, 'message' => 'O arquivo SQL está vazio.']);
            return;
        }

        $migrationName = pathinfo($filename, PATHINFO_FILENAME);
        $allResults = [];

        // Apply to init_base
        $initBaseResult = null;
        if ($applyToInitBase) {
            $initBaseResult = $this->migrationModel->executeSqlOnInitBase($sql);
        }

        // Apply to akti_master
        $masterResult = null;
        if ($applyToMaster) {
            $masterResult = $this->executeSqlOnMaster($sql);
            $this->migrationModel->logMigrationExecution(self::MASTER_DB, $migrationName, $sql, $masterResult, $adminId);
            $masterStatus = $masterResult['failed'] > 0 ? ($masterResult['ok'] > 0 ? 'partial' : 'failed') : 'success';
            $allResults[self::MASTER_DB] = [
                'status'  => $masterStatus,
                'message' => "OK: {$masterResult['ok']}, Falhas: {$masterResult['failed']} de {$masterResult['total']}",
                'result'  => $masterResult,
            ];
        }

        // Apply to tenant DBs
        if (!empty($selectedDbs)) {
            $tenantResults = $this->migrationModel->executeSqlOnAllTenants($sql, $migrationName, $adminId, $selectedDbs);
            $allResults = array_merge($allResults, $tenantResults);
        }

        $totalDbs = count($allResults);
        $successCount = count(array_filter($allResults, fn($r) => $r['status'] === 'success'));

        // Move to prontos/ on full success
        $movedToProntos = false;
        if ($totalDbs > 0 && $successCount === $totalDbs) {
            $prontosDir = $sqlDir . '/prontos';
            if (!is_dir($prontosDir)) {
                mkdir($prontosDir, 0755, true);
            }
            $movedToProntos = rename($filePath, $prontosDir . '/' . $filename);
        }

        $this->logAction('apply_single_file', 'migration', null,
            "Arquivo '{$filename}' aplicado em {$successCount}/{$totalDbs} bancos" . ($movedToProntos ? ' (movido para prontos/)' : ''));

        $this->json([
            'success'  => true,
            'file'     => $filename,
            'results'  => $allResults,
            'total'    => $totalDbs,
            'ok'       => $successCount,
            'moved'    => $movedToProntos,
            'init_base' => $initBaseResult,
        ]);
    }

    /**
     * Apply a pending SQL file from /sql/ to selected databases.
     */
    public function applyFile(): void
    {
        $this->requireMasterAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('?page=master_migrations');
        }

        $filename = basename($_POST['sql_file'] ?? '');
        $selectedDbs = $_POST['selected_dbs'] ?? [];
        $applyToInitBase = isset($_POST['apply_to_init_base']);
        $adminId = $this->getMasterAdminId();

        if (empty($filename)) {
            $_SESSION['error'] = 'Nenhum arquivo SQL selecionado.';
            $this->redirect('?page=master_migrations');
        }

        $sqlDir = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . '/sql';
        $filePath = $sqlDir . '/' . $filename;

        if (!is_file($filePath) || strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'sql') {
            $_SESSION['error'] = 'Arquivo SQL não encontrado: ' . htmlspecialchars($filename);
            $this->redirect('?page=master_migrations');
        }

        $sql = file_get_contents($filePath);
        if (empty(trim($sql))) {
            $_SESSION['error'] = 'O arquivo SQL está vazio.';
            $this->redirect('?page=master_migrations');
        }

        if (empty($selectedDbs)) {
            $_SESSION['error'] = 'Selecione pelo menos um banco de dados.';
            $this->redirect('?page=master_migrations');
        }

        // Separate akti_master from tenant DBs and validate
        $dbInfo = $this->separateAndValidateDbs($selectedDbs);
        if (!empty($dbInfo['invalidDbs'])) {
            $_SESSION['error'] = 'Bancos não registrados: ' . implode(', ', $dbInfo['invalidDbs']);
            $this->redirect('?page=master_migrations');
        }

        $migrationName = pathinfo($filename, PATHINFO_FILENAME);

        $initBaseResult = null;
        if ($applyToInitBase) {
            $initBaseResult = $this->migrationModel->executeSqlOnInitBase($sql);
        }

        // Execute on akti_master if selected
        $masterResult = null;
        if ($dbInfo['applyToMaster']) {
            $masterResult = $this->executeSqlOnMaster($sql);
            $this->migrationModel->logMigrationExecution(self::MASTER_DB, $migrationName, $sql, $masterResult, $adminId);
        }

        // Execute on tenant DBs
        $results = [];
        if (!empty($dbInfo['tenantDbs'])) {
            $results = $this->migrationModel->executeSqlOnAllTenants($sql, $migrationName, $adminId, $dbInfo['tenantDbs']);
        }

        // Add master result to results array
        if ($masterResult !== null) {
            $masterStatus = $masterResult['failed'] > 0 ? ($masterResult['ok'] > 0 ? 'partial' : 'failed') : 'success';
            $results = [self::MASTER_DB => [
                'status'  => $masterStatus,
                'message' => "OK: {$masterResult['ok']}, Falhas: {$masterResult['failed']} de {$masterResult['total']}",
                'result'  => $masterResult,
            ]] + $results;
        }

        $totalDbs = count($results);
        $successCount = count(array_filter($results, fn($r) => $r['status'] === 'success'));

        // Move to prontos/ on full success
        $movedToProntos = false;
        if ($successCount === $totalDbs) {
            $prontosDir = $sqlDir . '/prontos';
            if (!is_dir($prontosDir)) {
                mkdir($prontosDir, 0755, true);
            }
            $movedToProntos = rename($filePath, $prontosDir . '/' . $filename);
        }

        $this->logAction('apply_file_migration', 'migration', null,
            "Arquivo '{$filename}' aplicado em {$successCount}/{$totalDbs} bancos" . ($movedToProntos ? ' (movido para prontos/)' : ''));

        $_SESSION['migration_results'] = [
            'name'           => $migrationName,
            'source_file'    => $filename,
            'init_base'      => $initBaseResult,
            'databases'      => $results,
            'sql_preview'    => mb_substr($sql, 0, 500),
            'moved_to_prontos' => $movedToProntos,
        ];

        $_SESSION['success'] = "Arquivo '{$filename}' processado em {$totalDbs} banco(s)." . ($movedToProntos ? ' Movido para prontos/.' : '');
        $this->redirect('?page=master_migrations&action=results');
    }

    /**
     * Apply all pending SQL files from /sql/ sequentially.
     */
    public function applyAllFiles(): void
    {
        $this->requireMasterAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('?page=master_migrations');
        }

        $selectedDbs = $_POST['selected_dbs'] ?? [];
        $applyToInitBase = isset($_POST['apply_to_init_base']);
        $adminId = $this->getMasterAdminId();

        if (empty($selectedDbs)) {
            $_SESSION['error'] = 'Selecione pelo menos um banco de dados.';
            $this->redirect('?page=master_migrations');
        }

        // Separate akti_master from tenant DBs and validate
        $dbInfo = $this->separateAndValidateDbs($selectedDbs);
        if (!empty($dbInfo['invalidDbs'])) {
            $_SESSION['error'] = 'Bancos não registrados: ' . implode(', ', $dbInfo['invalidDbs']);
            $this->redirect('?page=master_migrations');
        }

        $pendingFiles = $this->scanPendingSqlFiles();
        if (empty($pendingFiles)) {
            $_SESSION['error'] = 'Nenhum arquivo SQL pendente encontrado.';
            $this->redirect('?page=master_migrations');
        }

        $sqlDir = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . '/sql';
        $prontosDir = $sqlDir . '/prontos';
        $allResults = [];

        foreach ($pendingFiles as $file) {
            $sql = $file['content'];
            if (empty(trim($sql))) {
                continue;
            }

            $migrationName = pathinfo($file['name'], PATHINFO_FILENAME);

            if ($applyToInitBase) {
                $this->migrationModel->executeSqlOnInitBase($sql);
            }

            // Execute on akti_master if selected
            $masterResult = null;
            if ($dbInfo['applyToMaster']) {
                $masterResult = $this->executeSqlOnMaster($sql);
                $this->migrationModel->logMigrationExecution(self::MASTER_DB, $migrationName, $sql, $masterResult, $adminId);
            }

            // Execute on tenant DBs
            $results = [];
            if (!empty($dbInfo['tenantDbs'])) {
                $results = $this->migrationModel->executeSqlOnAllTenants($sql, $migrationName, $adminId, $dbInfo['tenantDbs']);
            }

            // Add master result
            if ($masterResult !== null) {
                $masterStatus = $masterResult['failed'] > 0 ? ($masterResult['ok'] > 0 ? 'partial' : 'failed') : 'success';
                $results = [self::MASTER_DB => [
                    'status'  => $masterStatus,
                    'message' => "OK: {$masterResult['ok']}, Falhas: {$masterResult['failed']} de {$masterResult['total']}",
                    'result'  => $masterResult,
                ]] + $results;
            }

            $totalDbs = count($results);
            $successCount = count(array_filter($results, fn($r) => $r['status'] === 'success'));

            $movedToProntos = false;
            if ($successCount === $totalDbs) {
                if (!is_dir($prontosDir)) {
                    mkdir($prontosDir, 0755, true);
                }
                $filePath = $sqlDir . '/' . $file['name'];
                $movedToProntos = rename($filePath, $prontosDir . '/' . $file['name']);
            }

            $allResults[] = [
                'file'    => $file['name'],
                'success' => $successCount,
                'total'   => $totalDbs,
                'moved'   => $movedToProntos,
                'results' => $results,
            ];
        }

        $totalFiles = count($allResults);
        $allMoved = count(array_filter($allResults, fn($r) => $r['moved']));

        $this->logAction('apply_all_file_migrations', 'migration', null,
            "{$totalFiles} arquivos processados, {$allMoved} movidos para prontos/");

        $_SESSION['migration_results'] = [
            'name'        => "Batch: {$totalFiles} arquivos",
            'batch'       => true,
            'files'       => $allResults,
            'databases'   => [],
            'sql_preview' => '',
        ];

        $_SESSION['success'] = "{$totalFiles} arquivo(s) processado(s). {$allMoved} movido(s) para prontos/.";
        $this->redirect('?page=master_migrations&action=results');
    }

 /**
  * Compare detail.
  * @return void
  */
    public function compareDetail(): void
    {
        $this->requireMasterAuth();

        $dbName = $_GET['db'] ?? '';

        if (empty($dbName)) {
            $this->json(['success' => false, 'message' => 'Banco não informado']);
        }

        try {
            $diff = $this->migrationModel->compareSchema($dbName);
            $this->json(['success' => true, 'db' => $dbName, 'diff' => $diff]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

 /**
  * Apply.
  * @return void
  */
    public function apply(): void
    {
        $this->requireMasterAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('?page=master_migrations');
        }

        $sql = trim($_POST['sql_content'] ?? '');
        $migrationName = trim($_POST['migration_name'] ?? 'Migração manual ' . date('Y-m-d H:i'));
        $selectedDbs = $_POST['selected_dbs'] ?? [];
        $applyToInitBase = isset($_POST['apply_to_init_base']);
        $adminId = $this->getMasterAdminId();

        if (empty($sql)) {
            $_SESSION['error'] = 'O conteúdo SQL não pode estar vazio.';
            $this->redirect('?page=master_migrations');
        }

        if (empty($selectedDbs)) {
            $_SESSION['error'] = 'Selecione pelo menos um banco de dados.';
            $this->redirect('?page=master_migrations');
        }

        // Separate akti_master from tenant DBs and validate
        $dbInfo = $this->separateAndValidateDbs($selectedDbs);
        if (!empty($dbInfo['invalidDbs'])) {
            $_SESSION['error'] = 'Bancos não registrados: ' . implode(', ', $dbInfo['invalidDbs']);
            $this->redirect('?page=master_migrations');
        }

        $initBaseResult = null;
        if ($applyToInitBase) {
            $initBaseResult = $this->migrationModel->executeSqlOnInitBase($sql);
        }

        // Execute on akti_master if selected
        $masterResult = null;
        if ($dbInfo['applyToMaster']) {
            $masterResult = $this->executeSqlOnMaster($sql);
            $this->migrationModel->logMigrationExecution(self::MASTER_DB, $migrationName, $sql, $masterResult, $adminId);
        }

        // Execute on tenant DBs
        $results = [];
        if (!empty($dbInfo['tenantDbs'])) {
            $results = $this->migrationModel->executeSqlOnAllTenants($sql, $migrationName, $adminId, $dbInfo['tenantDbs']);
        }

        // Add master result to results array
        if ($masterResult !== null) {
            $masterStatus = $masterResult['failed'] > 0 ? ($masterResult['ok'] > 0 ? 'partial' : 'failed') : 'success';
            $results = [self::MASTER_DB => [
                'status'  => $masterStatus,
                'message' => "OK: {$masterResult['ok']}, Falhas: {$masterResult['failed']} de {$masterResult['total']}",
                'result'  => $masterResult,
            ]] + $results;
        }

        $totalDbs = count($results);
        $successCount = count(array_filter($results, fn($r) => $r['status'] === 'success'));
        $this->logAction('apply_migration', 'migration', null, "Migração '{$migrationName}' aplicada em {$successCount}/{$totalDbs} bancos");

        $_SESSION['migration_results'] = [
            'name'       => $migrationName,
            'init_base'  => $initBaseResult,
            'databases'  => $results,
            'sql_preview'=> mb_substr($sql, 0, 500),
        ];

        $_SESSION['success'] = "Migração '{$migrationName}' processada em {$totalDbs} banco(s).";
        $this->redirect('?page=master_migrations&action=results');
    }

 /**
  * Results.
  * @return void
  */
    public function results(): void
    {
        $this->requireMasterAuth();

        $migrationResults = $_SESSION['migration_results'] ?? null;
        if (!$migrationResults) {
            $this->redirect('?page=master_migrations');
        }

        $this->renderMaster('migrations/results', compact('migrationResults'));
        unset($_SESSION['migration_results']);
    }

    /**
     * Migration audit history with pagination and filters.
     */
    public function history(): void
    {
        $this->requireMasterAuth();

        $page = max(1, (int) ($_GET['p'] ?? 1));
        $filters = [
            'status'         => trim($_GET['status'] ?? ''),
            'db_name'        => trim($_GET['db_name'] ?? ''),
            'migration_name' => trim($_GET['migration_name'] ?? ''),
            'date_from'      => trim($_GET['date_from'] ?? ''),
            'date_to'        => trim($_GET['date_to'] ?? ''),
        ];

        $history = $this->migrationModel->getMigrationHistoryPaginated($page, 25, $filters);
        $stats = $this->migrationModel->getMigrationStats();
        $tenantDbs = $this->migrationModel->listTenantDatabases();

        $this->renderMaster('migrations/history', compact('history', 'stats', 'filters', 'tenantDbs'));
    }

    /**
     * AJAX: Get migration detail by ID for modal display.
     */
    public function historyDetail(): void
    {
        $this->requireMasterAuth();

        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->json(['success' => false, 'message' => 'ID inválido']);
            return;
        }

        $detail = $this->migrationModel->getMigrationDetail($id);
        if (!$detail) {
            $this->json(['success' => false, 'message' => 'Registro não encontrado']);
            return;
        }

        $this->json([
            'success' => true,
            'data'    => [
                'id'              => $detail['id'],
                'db_name'         => $detail['db_name'],
                'migration_name'  => $detail['migration_name'],
                'sql_hash'        => $detail['sql_hash'],
                'status'          => $detail['status'],
                'statements_total'  => $detail['statements_total'],
                'statements_ok'     => $detail['statements_ok'],
                'statements_failed' => $detail['statements_failed'],
                'error_log'       => $detail['error_log'],
                'sql_content'     => $detail['sql_content'] ?? null,
                'warnings'        => $detail['warnings'] ?? null,
                'execution_time_ms' => $detail['execution_time_ms'] ?? null,
                'admin_name'      => $detail['admin_name'] ?? 'Desconhecido',
                'applied_at'      => $detail['applied_at'],
            ],
        ]);
    }

 /**
  * Users.
  * @return void
  */
    public function users(): void
    {
        $this->requireMasterAuth();

        $allUsers = $this->migrationModel->listAllTenantUsers();
        $tenants = $this->migrationModel->getRegisteredTenants();

        $this->renderMaster('migrations/users', compact('allUsers', 'tenants'));
    }

 /**
  * Create user.
  * @return void
  */
    public function createUser(): void
    {
        $this->requireMasterAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('?page=master_migrations&action=users');
        }

        $dbName    = trim($_POST['db_name'] ?? '');
        $userName  = trim($_POST['user_name'] ?? '');
        $userEmail = trim($_POST['user_email'] ?? '');
        $userPass  = $_POST['user_password'] ?? '';
        $userPhone = trim($_POST['user_phone'] ?? '');
        $isAdmin   = isset($_POST['user_is_admin']) ? 1 : 0;

        if (empty($dbName) || empty($userName) || empty($userEmail) || empty($userPass)) {
            $_SESSION['error'] = 'Banco, nome, e-mail e senha são obrigatórios.';
            $this->redirect('?page=master_migrations&action=users');
        }

        if (strlen($userPass) < 6) {
            $_SESSION['error'] = 'A senha deve ter pelo menos 6 caracteres.';
            $this->redirect('?page=master_migrations&action=users');
        }

        $tenant = $this->clientModel->findByDbName($dbName);
        if (!$tenant) {
            $_SESSION['error'] = "Banco '{$dbName}' não encontrado na tabela de clientes.";
            $this->redirect('?page=master_migrations&action=users');
        }

        $masterCreds = \TenantManager::getMasterConfig();
        $result = $this->clientModel->createTenantUser(
            $tenant['db_host'],
            $tenant['db_port'],
            $tenant['db_name'],
            $masterCreds['username'], $masterCreds['password'],
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
            $this->logAction('create_tenant_user', 'user', null,
                "Usuário '{$userEmail}' criado no banco '{$dbName}' via painel de migrations");
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }

        $this->redirect('?page=master_migrations&action=users');
    }

 /**
  * Toggle user.
  * @return void
  */
    public function toggleUser(): void
    {
        $this->requireMasterAuth();

        $dbName = $_POST['db_name'] ?? '';
        $userId = (int)($_POST['user_id'] ?? 0);

        if (empty($dbName) || !$userId) {
            $this->json(['success' => false, 'message' => 'Parâmetros inválidos']);
        }

        if (!$this->isRegisteredDb($dbName)) {
            $this->json(['success' => false, 'message' => 'Banco não registrado como tenant']);
        }

        try {
            $this->migrationModel->toggleTenantUser($dbName, $userId);
            $this->json(['success' => true, 'message' => 'Status do usuário alterado']);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

 /**
  * Db users.
  * @return void
  */
    public function dbUsers(): void
    {
        $this->requireMasterAuth();

        $dbName = $_GET['db'] ?? '';

        if (empty($dbName)) {
            $this->json(['success' => false, 'message' => 'Banco não informado']);
        }

        if (!$this->isRegisteredDb($dbName)) {
            $this->json(['success' => false, 'message' => 'Banco não registrado como tenant']);
        }

        try {
            $users = $this->migrationModel->listUsersFromDatabase($dbName);
            $this->json(['success' => true, 'users' => $users, 'db' => $dbName]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
