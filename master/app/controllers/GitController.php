<?php
/**
 * Controller: GitController
 * Gerencia versionamento Git dos projetos deployados
 */

class GitController
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Página principal — renderiza shell com spinner, dados carregam via AJAX
     */
    public function index()
    {
        $basePath = GitVersion::getBasePath();

        require_once __DIR__ . '/../views/git/index.php';
    }

    /**
     * Carrega informações de todos os repositórios e diagnóstico (AJAX)
     */
    public function loadRepos()
    {
        header('Content-Type: application/json; charset=utf-8');

        $diagnostic = GitVersion::diagnose();
        $repos = GitVersion::getAllReposInfo();

        $totalRepos = count($repos);
        $upToDate = count(array_filter($repos, fn($r) => ($r['status'] ?? '') === 'up-to-date'));
        $behind = count(array_filter($repos, fn($r) => ($r['status'] ?? '') === 'behind'));
        $dirty = count(array_filter($repos, fn($r) => ($r['status'] ?? '') === 'dirty'));
        $ahead = count(array_filter($repos, fn($r) => ($r['status'] ?? '') === 'ahead'));
        $errors = count(array_filter($repos, fn($r) => ($r['status'] ?? '') === 'error'));

        echo json_encode([
            'success'    => true,
            'repos'      => $repos,
            'diagnostic' => $diagnostic,
            'stats'      => [
                'total'     => $totalRepos,
                'upToDate'  => $upToDate,
                'behind'    => $behind,
                'dirty'     => $dirty,
                'ahead'     => $ahead,
                'errors'    => $errors,
            ],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Fetch all — atualiza referências remotas de todos os repos (AJAX)
     */
    public function fetchAll()
    {
        header('Content-Type: application/json; charset=utf-8');

        $repos = GitVersion::listRepositories();
        $results = [];

        foreach ($repos as $repo) {
            $result = GitVersion::fetch($repo['path']);
            $results[$repo['name']] = [
                'success' => $result['success'],
                'output'  => $result['output'],
            ];
        }

        echo json_encode(['success' => true, 'results' => $results]);
        exit;
    }

    /**
     * Fetch de um repositório específico (AJAX)
     */
    public function fetch()
    {
        header('Content-Type: application/json; charset=utf-8');

        $repoPath = $this->resolveRepoPath();
        if (!$repoPath) {
            echo json_encode(['success' => false, 'message' => 'Repositório não informado ou inválido']);
            exit;
        }

        $result = GitVersion::fetch($repoPath);

        // Após fetch, reobter info atualizada
        $info = GitVersion::getRepoInfo($repoPath);

        echo json_encode([
            'success' => $result['success'],
            'output'  => $result['output'],
            'info'    => $info,
        ]);
        exit;
    }

    /**
     * Pull de um repositório específico (AJAX)
     */
    public function pull()
    {
        header('Content-Type: application/json; charset=utf-8');

        $repoPath = $this->resolveRepoPath();
        if (!$repoPath) {
            echo json_encode(['success' => false, 'message' => 'Repositório não informado ou inválido']);
            exit;
        }

        $repoName = basename($repoPath);
        $forceStash = isset($_POST['force_stash']);

        // Se tem alterações locais e não foi solicitado force, avisar
        $info = GitVersion::getRepoInfo($repoPath);
        if ($info['has_changes'] && !$forceStash) {
            echo json_encode([
                'success'      => false,
                'needs_stash'  => true,
                'message'      => 'O repositório tem alterações locais não commitadas. Use "Stash & Pull" para salvar as alterações temporariamente e atualizar.',
                'files_changed'=> $info['files_changed'],
                'untracked'    => $info['untracked'],
            ]);
            exit;
        }

        // Executar pull (com stash se necessário)
        if ($forceStash && $info['has_changes']) {
            $result = GitVersion::stashAndPull($repoPath);
        } else {
            $pullResult = GitVersion::pull($repoPath);
            $result = [
                'success'     => $pullResult['success'],
                'output'      => $pullResult['output'],
                'had_changes' => false,
            ];
        }

        // Logar ação
        $this->logAction('git_pull', $repoName, $result['success'] ? 'success' : 'failed', $result['output']);

        // Reobter info
        $updatedInfo = GitVersion::getRepoInfo($repoPath);

        echo json_encode([
            'success' => $result['success'],
            'output'  => $result['output'],
            'stash'   => $result['stash'] ?? null,
            'info'    => $updatedInfo,
        ]);
        exit;
    }

    /**
     * Force reset (git reset --hard origin/branch) (AJAX)
     */
    public function forceReset()
    {
        header('Content-Type: application/json; charset=utf-8');

        $repoPath = $this->resolveRepoPath();
        if (!$repoPath) {
            echo json_encode(['success' => false, 'message' => 'Repositório não informado ou inválido']);
            exit;
        }

        $repoName = basename($repoPath);
        $confirmed = isset($_POST['confirmed']);

        if (!$confirmed) {
            echo json_encode([
                'success'        => false,
                'needs_confirm'  => true,
                'message'        => 'ATENÇÃO: Esta ação descartará TODAS as alterações locais e forçará o repositório para a versão do remote. Confirma?',
            ]);
            exit;
        }

        $result = GitVersion::forceReset($repoPath);

        $this->logAction('git_force_reset', $repoName, $result['success'] ? 'success' : 'failed', $result['output']);

        $updatedInfo = GitVersion::getRepoInfo($repoPath);

        echo json_encode([
            'success' => $result['success'],
            'output'  => $result['output'],
            'info'    => $updatedInfo,
        ]);
        exit;
    }

    /**
     * Detalhes de um repositório (AJAX) — commits, branches, diff
     */
    public function detail()
    {
        header('Content-Type: application/json; charset=utf-8');

        $repoPath = $this->resolveRepoPath();
        if (!$repoPath) {
            echo json_encode(['success' => false, 'message' => 'Repositório não informado ou inválido']);
            exit;
        }

        $info = GitVersion::getRepoInfo($repoPath);
        $commits = GitVersion::getDetailedLog($repoPath, 15);
        $branches = GitVersion::getBranches($repoPath);
        $diff = GitVersion::getDiff($repoPath);
        $size = GitVersion::getRepoSize($repoPath);

        echo json_encode([
            'success'  => true,
            'info'     => $info,
            'commits'  => $commits,
            'branches' => $branches,
            'diff'     => $diff,
            'size'     => $size,
        ]);
        exit;
    }

    /**
     * Checkout de branch (AJAX)
     */
    public function checkout()
    {
        header('Content-Type: application/json; charset=utf-8');

        $repoPath = $this->resolveRepoPath();
        $branch = trim($_POST['branch'] ?? '');

        if (!$repoPath || empty($branch)) {
            echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos']);
            exit;
        }

        $repoName = basename($repoPath);
        $result = GitVersion::checkout($repoPath, $branch);

        $this->logAction('git_checkout', $repoName, $result['success'] ? 'success' : 'failed', "Branch: {$branch} — " . $result['output']);

        $updatedInfo = GitVersion::getRepoInfo($repoPath);

        echo json_encode([
            'success' => $result['success'],
            'output'  => $result['output'],
            'info'    => $updatedInfo,
        ]);
        exit;
    }

    /**
     * Pull em todos os repositórios (AJAX)
     */
    public function pullAll()
    {
        header('Content-Type: application/json; charset=utf-8');

        $repos = GitVersion::listRepositories();
        $results = [];

        foreach ($repos as $repo) {
            $info = GitVersion::getRepoInfo($repo['path']);

            if ($info['has_changes']) {
                $results[$repo['name']] = [
                    'success' => false,
                    'status'  => 'skipped',
                    'message' => 'Alterações locais não commitadas — pulando',
                ];
                continue;
            }

            $pullResult = GitVersion::pull($repo['path']);
            $results[$repo['name']] = [
                'success' => $pullResult['success'],
                'status'  => $pullResult['success'] ? 'ok' : 'error',
                'message' => mb_substr($pullResult['output'], 0, 200),
            ];

            $this->logAction('git_pull_all', $repo['name'], $pullResult['success'] ? 'success' : 'failed');
        }

        echo json_encode(['success' => true, 'results' => $results]);
        exit;
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Diagnóstico completo do ambiente em formato JSON (para debug na VPS)
     */
    public function diagnoseJson()
    {
        header('Content-Type: application/json; charset=utf-8');

        // Limpar cache para forçar diagnóstico fresco
        $refClass = new ReflectionClass('GitVersion');
        $prop = $refClass->getProperty('diagCache');
        $prop->setAccessible(true);
        $prop->setValue(null, null);

        $diagnostic = GitVersion::diagnose();

        // Adicionar também as info de todos os repos
        $repos = GitVersion::getAllReposInfo();
        $debugLog = GitVersion::getDebugLog();

        echo json_encode([
            'diagnostic' => $diagnostic,
            'repos'      => $repos,
            'debug_log'  => $debugLog,
            'php_info'   => [
                'sapi'             => php_sapi_name(),
                'version'          => PHP_VERSION,
                'os'               => PHP_OS,
                'os_family'        => PHP_OS_FAMILY,
                'user'             => get_current_user(),
                'pid'              => getmypid(),
                'disable_functions'=> ini_get('disable_functions'),
                'open_basedir'     => ini_get('open_basedir'),
                'safe_mode'        => ini_get('safe_mode'),
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Resolve o caminho do repositório a partir do parâmetro 'repo'
     */
    private function resolveRepoPath()
    {
        $repoName = $_POST['repo'] ?? $_GET['repo'] ?? '';
        $repoName = basename(trim($repoName)); // Sanitizar — apenas nome da pasta

        if (empty($repoName)) return null;

        $basePath = GitVersion::getBasePath();
        $repoPath = $basePath . DIRECTORY_SEPARATOR . $repoName;

        if (!is_dir($repoPath) || !is_dir($repoPath . DIRECTORY_SEPARATOR . '.git')) {
            return null;
        }

        return $repoPath;
    }

    /**
     * Registra ação de git no log administrativo
     */
    private function logAction($action, $repoName, $status, $details = null)
    {
        try {
            $adminId = $_SESSION['admin_id'] ?? null;
            if ($adminId) {
                $log = new AdminLog($this->db);
                $log->log($adminId, $action, 'git_repo', null,
                    "Repo: {$repoName} — Status: {$status}" . ($details ? " — " . mb_substr($details, 0, 200) : ''));
            }
        } catch (Exception $e) {
            // Não bloquear ação por erro de log
        }
    }
}
