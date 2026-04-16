<?php

namespace Akti\Controllers\Master;

use Akti\Models\Master\GitVersion;

/**
 * Class GitController.
 */
class GitController extends MasterBaseController
{
    /**
     * Página principal — renderiza shell com spinners, dados carregam via AJAX
     */
    public function index(): void
    {
        $this->requireMasterAuth();

        $basePath = GitVersion::getBasePath();

        $this->renderMaster('git/index', compact('basePath'));
    }

    /**
     * Carrega informações de todos os repositórios e diagnóstico (AJAX)
     */
    public function loadRepos(): void
    {
        $this->requireMasterAuth();

        $diagnostic = GitVersion::diagnose();
        $repos = GitVersion::getAllReposInfo();

        $totalRepos = count($repos);
        $upToDate = count(array_filter($repos, fn($r) => ($r['status'] ?? '') === 'up-to-date'));
        $behind = count(array_filter($repos, fn($r) => ($r['status'] ?? '') === 'behind'));
        $dirty = count(array_filter($repos, fn($r) => ($r['status'] ?? '') === 'dirty'));
        $ahead = count(array_filter($repos, fn($r) => ($r['status'] ?? '') === 'ahead'));
        $errors = count(array_filter($repos, fn($r) => ($r['status'] ?? '') === 'error'));

        $this->json([
            'success'    => true,
            'repos'      => $repos,
            'diagnostic' => $diagnostic,
            'stats'      => [
                'total'    => $totalRepos,
                'upToDate' => $upToDate,
                'behind'   => $behind,
                'dirty'    => $dirty,
                'ahead'    => $ahead,
                'errors'   => $errors,
            ],
        ]);
    }

    /**
     * Busca dados.
     * @return void
     */
    public function fetchAll(): void
    {
        $this->requireMasterAuth();

        $repos = GitVersion::listRepositories();
        $results = [];

        foreach ($repos as $repo) {
            $result = GitVersion::fetch($repo['path']);
            $results[$repo['name']] = [
                'success' => $result['success'],
                'output'  => $result['output'],
            ];
        }

        $this->json(['success' => true, 'results' => $results]);
    }

    /**
     * Busca dados.
     * @return void
     */
    public function fetch(): void
    {
        $this->requireMasterAuth();

        $repoPath = $this->resolveRepoPath();
        if (!$repoPath) {
            $this->json(['success' => false, 'message' => 'Repositório não informado ou inválido']);
        }

        $result = GitVersion::fetch($repoPath);
        $info = GitVersion::getRepoInfo($repoPath);

        $this->json([
            'success' => $result['success'],
            'output'  => $result['output'],
            'info'    => $info,
        ]);
    }

    /**
     * Pull.
     * @return void
     */
    public function pull(): void
    {
        $this->requireMasterAuth();

        $repoPath = $this->resolveRepoPath();
        if (!$repoPath) {
            $this->json(['success' => false, 'message' => 'Repositório não informado ou inválido']);
        }

        $repoName = basename($repoPath);
        $forceStash = isset($_POST['force_stash']);

        $info = GitVersion::getRepoInfo($repoPath);
        if ($info['has_changes'] && !$forceStash) {
            $this->json([
                'success'      => false,
                'needs_stash'  => true,
                'message'      => 'O repositório tem alterações locais não commitadas. Use "Stash & Pull" para salvar as alterações temporariamente e atualizar.',
                'files_changed'=> $info['files_changed'],
                'untracked'    => $info['untracked'],
            ]);
        }

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

        $this->logAction('git_pull', 'git_repo', null, "Repo: {$repoName} — Status: " . ($result['success'] ? 'success' : 'failed') . " — " . mb_substr($result['output'], 0, 200));

        $updatedInfo = GitVersion::getRepoInfo($repoPath);

        $this->json([
            'success' => $result['success'],
            'output'  => $result['output'],
            'stash'   => $result['stash'] ?? null,
            'info'    => $updatedInfo,
        ]);
    }

 /**
  * Force reset.
  * @return void
  */
    public function forceReset(): void
    {
        $this->requireMasterAuth();

        $repoPath = $this->resolveRepoPath();
        if (!$repoPath) {
            $this->json(['success' => false, 'message' => 'Repositório não informado ou inválido']);
        }

        $repoName = basename($repoPath);
        $confirmed = isset($_POST['confirmed']);

        if (!$confirmed) {
            $this->json([
                'success'        => false,
                'needs_confirm'  => true,
                'message'        => 'ATENÇÃO: Esta ação descartará TODAS as alterações locais e forçará o repositório para a versão do remote. Confirma?',
            ]);
        }

        $result = GitVersion::forceReset($repoPath);
        $this->logAction('git_force_reset', 'git_repo', null, "Repo: {$repoName} — Status: " . ($result['success'] ? 'success' : 'failed') . " — " . mb_substr($result['output'], 0, 200));

        $updatedInfo = GitVersion::getRepoInfo($repoPath);

        $this->json([
            'success' => $result['success'],
            'output'  => $result['output'],
            'info'    => $updatedInfo,
        ]);
    }

 /**
  * Detail.
  * @return void
  */
    public function detail(): void
    {
        $this->requireMasterAuth();

        $repoPath = $this->resolveRepoPath();
        if (!$repoPath) {
            $this->json(['success' => false, 'message' => 'Repositório não informado ou inválido']);
        }

        $info = GitVersion::getRepoInfo($repoPath);
        $commits = GitVersion::getDetailedLog($repoPath, 15);
        $branches = GitVersion::getBranches($repoPath);
        $diff = GitVersion::getDiff($repoPath);
        $size = GitVersion::getRepoSize($repoPath);

        $this->json([
            'success'  => true,
            'info'     => $info,
            'commits'  => $commits,
            'branches' => $branches,
            'diff'     => $diff,
            'size'     => $size,
        ]);
    }

 /**
  * Checkout.
  * @return void
  */
    public function checkout(): void
    {
        $this->requireMasterAuth();

        $repoPath = $this->resolveRepoPath();
        $branch = trim($_POST['branch'] ?? '');

        if (!$repoPath || empty($branch)) {
            $this->json(['success' => false, 'message' => 'Parâmetros inválidos']);
        }

        if (!preg_match('/^[\w.\-\/]+$/', $branch)) {
            $this->json(['success' => false, 'message' => 'Nome de branch inválido']);
        }

        $repoName = basename($repoPath);
        $result = GitVersion::checkout($repoPath, $branch);

        $this->logAction('git_checkout', 'git_repo', null, "Repo: {$repoName} — Branch: {$branch} — " . ($result['success'] ? 'success' : 'failed'));

        $updatedInfo = GitVersion::getRepoInfo($repoPath);

        $this->json([
            'success' => $result['success'],
            'output'  => $result['output'],
            'info'    => $updatedInfo,
        ]);
    }

 /**
  * Pull all.
  * @return void
  */
    public function pullAll(): void
    {
        $this->requireMasterAuth();

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

            $this->logAction('git_pull_all', 'git_repo', null, "Repo: {$repo['name']} — " . ($pullResult['success'] ? 'success' : 'failed'));
        }

        $this->json(['success' => true, 'results' => $results]);
    }

 /**
  * Diagnose json.
  * @return void
  */
    public function diagnoseJson(): void
    {
        $this->requireMasterAuth();

/**
 * Class Unknown.
 */
        $refClass = new \ReflectionClass(GitVersion::class);
        $prop = $refClass->getProperty('diagCache');
        $prop->setAccessible(true);
        $prop->setValue(null, null);

        $diagnostic = GitVersion::diagnose();
        $repos = GitVersion::getAllReposInfo();
        $debugLog = GitVersion::getDebugLog();

        $this->json([
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
            ],
        ]);
    }

    private function resolveRepoPath(): ?string
    {
        $repoName = $_POST['repo'] ?? $_GET['repo'] ?? '';
        $repoName = basename(trim($repoName));

        if (empty($repoName)) return null;

        $basePath = GitVersion::getBasePath();
        $repoPath = $basePath . DIRECTORY_SEPARATOR . $repoName;

        if (!is_dir($repoPath) || !is_dir($repoPath . DIRECTORY_SEPARATOR . '.git')) {
            return null;
        }

        return $repoPath;
    }
}
