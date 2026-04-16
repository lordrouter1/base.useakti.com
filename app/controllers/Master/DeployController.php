<?php

namespace Akti\Controllers\Master;

use Akti\Models\Master\GitVersion;
use Akti\Models\Master\Migration;
use Akti\Models\Master\AdminUser;

/**
 * Class DeployController.
 */
class DeployController extends MasterBaseController
{
    private Migration $migrationModel;

    /**
     * Construtor da classe DeployController.
     *
     * @param \PDO|null $db Conexão PDO com o banco de dados
     */
    public function __construct(?\PDO $db = null)
    {
        parent::__construct($db);
        $this->migrationModel = new Migration($this->db);
    }

    /**
     * Require superadmin for deploy operations.
     */
    private function requireSuperadmin(): void
    {
        $adminId = $this->getMasterAdminId();
        if (!$adminId) {
            $this->redirect('?page=login');
        }

        $adminModel = new AdminUser($this->db);
        $admin = $adminModel->findById($adminId);
        if (!$admin || ($admin['role'] ?? 'superadmin') !== 'superadmin') {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'error' => 'Apenas superadmins podem executar deploy.'], 403);
            }
            $_SESSION['error'] = 'Apenas superadmins podem executar deploy.';
            $this->redirect('?page=master_dashboard');
        }
    }

    /**
     * Exibe a página de listagem.
     * @return void
     */
    public function index(): void
    {
        $this->requireMasterAuth();
        $this->requireSuperadmin();

        // Gather system state for deploy preview
        $repos = GitVersion::getAllReposInfo();
        $pendingSql = $this->scanPendingSqlFiles();
        $tenants = $this->migrationModel->getRegisteredTenants();

        $mainRepo = null;
        foreach ($repos as $repo) {
            if (($repo['name'] ?? '') === basename($_SERVER['DOCUMENT_ROOT']) || ($repo['is_main'] ?? false)) {
                $mainRepo = $repo;
                break;
            }
        }
        if (!$mainRepo && !empty($repos)) {
            $mainRepo = reset($repos);
        }

        $this->renderMaster('deploy/index', compact('repos', 'mainRepo', 'pendingSql', 'tenants'));
    }

    /**
     * Execute the deploy pipeline: git pull → apply pending SQL → clear cache.
     */
    public function run(): void
    {
        $this->requireMasterAuth();
        $this->requireSuperadmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('?page=master_deploy');
        }

        $steps = [];
        $adminId = $this->getMasterAdminId();
        $doGitPull = isset($_POST['do_git_pull']);
        $doMigrations = isset($_POST['do_migrations']);
        $doCacheClear = isset($_POST['do_cache_clear']);

        // Step 1: Git Pull
        if ($doGitPull) {
            $basePath = GitVersion::getBasePath();
            if ($basePath && is_dir($basePath . '/.git')) {
                $pullResult = GitVersion::pull($basePath);
                $steps[] = [
                    'step'    => 'Git Pull',
                    'icon'    => 'fa-code-branch',
                    'success' => $pullResult['success'],
                    'output'  => $pullResult['output'] ?? '',
                    'message' => $pullResult['success'] ? 'Pull realizado com sucesso' : ($pullResult['error'] ?? 'Erro no pull'),
                ];
            } else {
                $steps[] = [
                    'step'    => 'Git Pull',
                    'icon'    => 'fa-code-branch',
                    'success' => false,
                    'output'  => '',
                    'message' => 'Repositório Git não encontrado',
                ];
            }
        }

        // Step 2: Apply pending migrations
        if ($doMigrations) {
            $pendingFiles = $this->scanPendingSqlFiles();
            $selectedDbs = $_POST['selected_dbs'] ?? [];

            if (empty($pendingFiles)) {
                $steps[] = [
                    'step'    => 'Migrações',
                    'icon'    => 'fa-database',
                    'success' => true,
                    'output'  => '',
                    'message' => 'Nenhuma migração pendente',
                ];
            } elseif (empty($selectedDbs)) {
                $steps[] = [
                    'step'    => 'Migrações',
                    'icon'    => 'fa-database',
                    'success' => false,
                    'output'  => '',
                    'message' => 'Nenhum banco selecionado para migrações',
                ];
            } else {
                // Separate akti_master from tenant DBs
                $applyToMaster = in_array('akti_master', $selectedDbs, true);
                $tenantDbs = array_values(array_filter($selectedDbs, fn($db) => $db !== 'akti_master'));

                $registeredTenants = $this->migrationModel->getRegisteredTenants();
                $registeredDbNames = array_column($registeredTenants, 'db_name');
                $validDbs = array_intersect($tenantDbs, $registeredDbNames);

                $sqlDir = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . '/sql';
                $prontosDir = $sqlDir . '/prontos';
                $migrationResults = [];
                $totalMoved = 0;

                foreach ($pendingFiles as $file) {
                    $sql = $file['content'];
                    if (empty(trim($sql))) {
                        continue;
                    }

                    $migrationName = pathinfo($file['name'], PATHINFO_FILENAME);

                    // Apply to init base
                    $this->migrationModel->executeSqlOnInitBase($sql);

                    // Apply to akti_master if selected
                    $results = [];
                    if ($applyToMaster) {
                        $masterResult = $this->migrationModel->executeSqlOnDatabase('akti_master', $sql);
                        $masterStatus = $masterResult['failed'] > 0 ? ($masterResult['ok'] > 0 ? 'partial' : 'failed') : 'success';
                        $results['akti_master'] = [
                            'status'  => $masterStatus,
                            'message' => "OK: {$masterResult['ok']}, Falhas: {$masterResult['failed']} de {$masterResult['total']}",
                            'result'  => $masterResult,
                        ];
                    }

                    // Apply to all selected tenant DBs
                    if (!empty($validDbs)) {
                        $tenantResults = $this->migrationModel->executeSqlOnAllTenants($sql, $migrationName, $adminId, $validDbs);
                        $results = $results + $tenantResults;
                    }

                    $totalDbs = count($results);
                    $successCount = count(array_filter($results, fn($r) => $r['status'] === 'success'));

                    $moved = false;
                    if ($successCount === $totalDbs) {
                        if (!is_dir($prontosDir)) {
                            mkdir($prontosDir, 0755, true);
                        }
                        $filePath = $sqlDir . '/' . $file['name'];
                        $moved = rename($filePath, $prontosDir . '/' . $file['name']);
                        if ($moved) {
                            $totalMoved++;
                        }
                    }

                    $migrationResults[] = [
                        'file'    => $file['name'],
                        'success' => $successCount,
                        'total'   => $totalDbs,
                        'moved'   => $moved,
                    ];
                }

                $totalFiles = count($migrationResults);
                $steps[] = [
                    'step'    => 'Migrações',
                    'icon'    => 'fa-database',
                    'success' => $totalMoved === $totalFiles,
                    'output'  => json_encode($migrationResults, JSON_PRETTY_PRINT),
                    'message' => "{$totalFiles} arquivo(s) processado(s), {$totalMoved} movido(s) para prontos/",
                    'details' => $migrationResults,
                ];
            }
        }

        // Step 3: Clear cache (OPcache)
        if ($doCacheClear) {
            $cacheCleared = false;
            $cacheMsg = '';

            if (function_exists('opcache_reset')) {
                $cacheCleared = @opcache_reset();
                $cacheMsg = $cacheCleared ? 'OPcache limpo com sucesso' : 'Falha ao limpar OPcache';
            } else {
                $cacheMsg = 'OPcache não disponível';
                $cacheCleared = true; // Not a failure, just not available
            }

            $steps[] = [
                'step'    => 'Limpar Cache',
                'icon'    => 'fa-broom',
                'success' => $cacheCleared,
                'output'  => '',
                'message' => $cacheMsg,
            ];
        }

        $allSuccess = count(array_filter($steps, fn($s) => !$s['success'])) === 0;

        $this->logAction('deploy', 'system', null,
            'Deploy executado: ' . implode(', ', array_map(fn($s) => $s['step'] . '=' . ($s['success'] ? 'OK' : 'FAIL'), $steps)));

        $deployResults = [
            'steps'       => $steps,
            'all_success' => $allSuccess,
            'timestamp'   => date('Y-m-d H:i:s'),
        ];

        $this->renderMaster('deploy/results', compact('deployResults'));
    }

    /**
     * Scan the /sql/ folder for pending migration files.
     */
    private function scanPendingSqlFiles(): array
    {
        $sqlDir = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . '/sql';
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

        usort($files, fn($a, $b) => strcmp($a['name'], $b['name']));
        return $files;
    }
}
