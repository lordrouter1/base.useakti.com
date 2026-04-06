<?php

namespace Akti\Models\Master;

class GitVersion
{
    private static $diagCache = null;

    public static function getBasePath(): string
    {
        if (defined('WWW_BASE_PATH')) {
            return rtrim(WWW_BASE_PATH, '/\\');
        }

        $envPath = getenv('AKTI_WWW_BASE_PATH');
        if ($envPath) {
            return rtrim($envPath, '/\\');
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $candidates = ['d:/xampp/htdocs', 'c:/xampp/htdocs'];
            foreach ($candidates as $path) {
                if (is_dir($path)) return str_replace('/', DIRECTORY_SEPARATOR, $path);
            }
            return str_replace('/', DIRECTORY_SEPARATOR, 'd:/xampp/htdocs');
        }

        return '/var/www';
    }

    private static function getGitBin(): string
    {
        static $cached = null;
        if ($cached !== null) return $cached;

        if (defined('GIT_PATH')) {
            $cached = GIT_PATH;
            return $cached;
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $paths = [
                'C:\\Program Files\\Git\\cmd\\git.exe',
                'C:\\Program Files\\Git\\bin\\git.exe',
                'C:\\Program Files (x86)\\Git\\cmd\\git.exe',
                'C:\\Program Files (x86)\\Git\\bin\\git.exe',
                'D:\\Program Files\\Git\\cmd\\git.exe',
                'D:\\Git\\cmd\\git.exe',
            ];
            foreach ($paths as $p) {
                if (@file_exists($p)) {
                    $cached = '"' . $p . '"';
                    return $cached;
                }
            }
            $found = trim(@shell_exec('cmd /c where git 2>NUL') ?? '');
            if ($found) {
                $cached = '"' . trim(explode("\n", $found)[0]) . '"';
                return $cached;
            }
            $cached = 'git';
            return $cached;
        }

        $linuxPaths = ['/usr/bin/git', '/usr/local/bin/git', '/bin/git'];
        foreach ($linuxPaths as $p) {
            if (@file_exists($p) && @is_executable($p)) {
                $cached = $p;
                return $cached;
            }
        }
        $found = trim(@shell_exec('which git 2>/dev/null') ?? '');
        $cached = $found ?: 'git';
        return $cached;
    }

    private static function canExec(): bool
    {
        if (!function_exists('exec')) return false;
        $disabled = ini_get('disable_functions');
        if ($disabled) {
            $list = array_map('trim', explode(',', strtolower($disabled)));
            if (in_array('exec', $list)) return false;
        }
        return true;
    }

    private static $debugLog = [];

    private static function debugLog(string $msg): void
    {
        self::$debugLog[] = $msg;
    }

    public static function getDebugLog(): array
    {
        return self::$debugLog;
    }

    private static function execGit(string $repoPath, string $command): array
    {
        if (!self::canExec()) {
            return [
                'output'      => 'exec() está desabilitado no php.ini (disable_functions)',
                'lines'       => [],
                'return_code' => -1,
                'success'     => false,
                'cmd'         => '(exec disabled)',
            ];
        }

        $git = self::getGitBin();
        $repoPath = rtrim(str_replace('\\', '/', $repoPath), '/');

        if (PHP_OS_FAMILY === 'Windows') {
            $cmd = "{$git} -C \"{$repoPath}\" {$command} 2>&1";
        } else {
            $home = getenv('HOME');
            if (empty($home) || !is_dir($home)) {
                $home = '/tmp';
                if (function_exists('posix_geteuid') && function_exists('posix_getpwuid')) {
                    $pw = @posix_getpwuid(posix_geteuid());
                    if (!empty($pw['dir']) && is_dir($pw['dir'])) {
                        $home = $pw['dir'];
                    }
                }
            }

            $prevHome = getenv('HOME');
            $prevPrompt = getenv('GIT_TERMINAL_PROMPT');
            putenv("HOME={$home}");
            putenv("GIT_TERMINAL_PROMPT=0");

            $cmd = $git
                 . ' -c safe.directory=' . escapeshellarg($repoPath)
                 . ' -c safe.directory=*'
                 . ' -C ' . escapeshellarg($repoPath)
                 . ' ' . $command . ' 2>&1';
        }

        $output = [];
        $returnCode = 0;
        @exec($cmd, $output, $returnCode);

        if (PHP_OS_FAMILY !== 'Windows') {
            if ($prevHome !== false) {
                putenv("HOME={$prevHome}");
            } else {
                putenv("HOME");
            }
            if ($prevPrompt !== false) {
                putenv("GIT_TERMINAL_PROMPT={$prevPrompt}");
            } else {
                putenv("GIT_TERMINAL_PROMPT");
            }
        }

        $result = [
            'output'      => implode("\n", $output),
            'lines'       => $output,
            'return_code' => $returnCode,
            'success'     => $returnCode === 0,
            'cmd'         => $cmd,
        ];

        if ($returnCode !== 0) {
            self::debugLog("FAIL [{$returnCode}] {$cmd} => " . implode(' | ', $output));
        }

        return $result;
    }

    public static function listRepositories(): array
    {
        $basePath = self::getBasePath();
        $repos = [];

        if (!is_dir($basePath) || !is_readable($basePath)) {
            return $repos;
        }

        $dirs = @scandir($basePath);
        if ($dirs === false) return $repos;

        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') continue;

            $fullPath = $basePath . DIRECTORY_SEPARATOR . $dir;
            if (!is_dir($fullPath)) continue;

            $gitDir = $fullPath . DIRECTORY_SEPARATOR . '.git';
            if (is_dir($gitDir)) {
                $repos[] = [
                    'name'      => $dir,
                    'path'      => $fullPath,
                    'git_dir'   => $gitDir,
                ];
            }
        }

        usort($repos, function ($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        return $repos;
    }

    public static function diagnose(): array
    {
        if (self::$diagCache !== null) return self::$diagCache;

        $diag = [
            'os'                => PHP_OS_FAMILY,
            'php_user'          => '',
            'php_uid'           => '',
            'php_home'          => getenv('HOME') ?: (getenv('USERPROFILE') ?: '(não definido)'),
            'php_sapi'          => php_sapi_name(),
            'base_path'         => self::getBasePath(),
            'base_path_exists'  => is_dir(self::getBasePath()),
            'base_path_readable'=> is_readable(self::getBasePath()),
            'git_bin'           => self::getGitBin(),
            'git_exists'        => false,
            'git_version'       => null,
            'exec_available'    => self::canExec(),
            'shell_exec_available' => function_exists('shell_exec'),
            'safe_directory_ok' => false,
            'issues'            => [],
            'fixes'             => [],
            'raw_tests'         => [],
        ];

        if (function_exists('posix_geteuid') && function_exists('posix_getpwuid')) {
            $uid = posix_geteuid();
            $pw = posix_getpwuid($uid);
            $diag['php_user'] = $pw['name'] ?? '(desconhecido)';
            $diag['php_uid'] = $uid;
            $diag['php_home'] = $pw['dir'] ?? '(não definido)';
        } elseif (PHP_OS_FAMILY === 'Windows') {
            $diag['php_user'] = getenv('USERNAME') ?: '(Windows)';
        } else {
            $diag['php_user'] = trim(@shell_exec('whoami 2>/dev/null') ?? '(desconhecido)');
        }

        if (!$diag['exec_available']) {
            $diag['issues'][] = 'exec() está desabilitado no php.ini (disable_functions)';
            $diag['fixes'][] = 'Edite o php.ini do PHP-FPM e remova "exec" de disable_functions, depois reinicie: sudo systemctl restart php*-fpm';
            self::$diagCache = $diag;
            return $diag;
        }

        $rawTest = [];
        @exec('echo "exec_works" 2>&1', $rawTest, $rawRc);
        $diag['raw_tests']['echo'] = ['output' => implode("\n", $rawTest), 'rc' => $rawRc];

        $gitBin = self::getGitBin();
        $gitBinClean = trim($gitBin, '"');
        $diag['git_exists'] = @file_exists($gitBinClean) || self::execGit('/tmp', '--version')['success'];

        if (!$diag['git_exists']) {
            $diag['issues'][] = "Git não encontrado em: {$gitBin}";
            $diag['fixes'][] = 'Instale o Git: sudo apt install git';
            self::$diagCache = $diag;
            return $diag;
        }

        $ver = self::execGit('/tmp', '--version');
        $diag['git_version'] = $ver['success'] ? trim($ver['output']) : 'erro: ' . $ver['output'];
        $diag['raw_tests']['git_version'] = ['output' => $ver['output'], 'rc' => $ver['return_code'], 'cmd' => $ver['cmd'] ?? ''];

        if (!$diag['base_path_exists']) {
            $diag['issues'][] = "Diretório base não existe: {$diag['base_path']}";
            $diag['fixes'][] = "Crie o diretório ou configure WWW_BASE_PATH no config.php";
        } elseif (!$diag['base_path_readable']) {
            $diag['issues'][] = "Sem permissão de leitura em: {$diag['base_path']}";
            $diag['fixes'][] = "sudo chmod o+rX {$diag['base_path']}";
        }

        if ($diag['base_path_readable']) {
            $basePath = self::getBasePath();
            $dirs = @scandir($basePath);
            if ($dirs !== false) {
                $diag['raw_tests']['base_dirs'] = [];
                foreach ($dirs as $dir) {
                    if ($dir === '.' || $dir === '..') continue;
                    $fullPath = $basePath . '/' . $dir;
                    if (!is_dir($fullPath)) continue;
                    $gitDir = $fullPath . '/.git';
                    $diag['raw_tests']['base_dirs'][$dir] = [
                        'is_dir' => is_dir($fullPath),
                        'readable' => is_readable($fullPath),
                        'git_dir_exists' => is_dir($gitDir),
                        'git_dir_readable' => is_dir($gitDir) ? is_readable($gitDir) : null,
                    ];
                }
            }
        }

        $repos = self::listRepositories();
        $diag['repos_found'] = count($repos);

        if (!empty($repos)) {
            $testRepo = $repos[0];
            $testResult = self::execGit($testRepo['path'], 'rev-parse --abbrev-ref HEAD');
            $diag['safe_directory_ok'] = $testResult['success'];
            $diag['raw_tests']['safe_dir_test'] = [
                'repo'   => $testRepo['name'],
                'cmd'    => $testResult['cmd'] ?? '',
                'output' => $testResult['output'],
                'rc'     => $testResult['return_code'],
            ];

            if (!$testResult['success']) {
                $output = $testResult['output'];
                if (stripos($output, 'dubious ownership') !== false || stripos($output, 'safe.directory') !== false) {
                    $diag['issues'][] = 'Git recusa operar: "dubious ownership" — o dono dos repos não é o usuário do PHP (' . $diag['php_user'] . ')';
                    $diag['fixes'][] = "sudo -u {$diag['php_user']} git config --global --add safe.directory '*'";
                } elseif (stripos($output, 'permission denied') !== false || stripos($output, 'not permitted') !== false) {
                    $diag['issues'][] = 'Permissão negada ao ler os repositórios Git';
                    $diag['fixes'][] = "sudo chmod -R o+rX {$diag['base_path']}/*/.git";
                } else {
                    $diag['issues'][] = "Erro ao executar git no repo '{$testRepo['name']}': {$output}";
                    $diag['fixes'][] = "Verifique as permissões do diretório e do .git. Teste manualmente: sudo -u {$diag['php_user']} git -C {$testRepo['path']} status";
                }
            }

            $statusResult = self::execGit($testRepo['path'], 'status --porcelain');
            $diag['raw_tests']['git_status_test'] = [
                'repo'   => $testRepo['name'],
                'cmd'    => $statusResult['cmd'] ?? '',
                'output' => $statusResult['output'],
                'rc'     => $statusResult['return_code'],
            ];

            $remoteResult = self::execGit($testRepo['path'], 'remote -v');
            $diag['raw_tests']['remote_test'] = [
                'repo'   => $testRepo['name'],
                'output' => $remoteResult['output'],
                'rc'     => $remoteResult['return_code'],
            ];
        } else {
            if ($diag['base_path_readable']) {
                $diag['issues'][] = "Nenhum repositório Git encontrado em: {$diag['base_path']}";
                $diag['fixes'][] = "Certifique-se de que as pastas em {$diag['base_path']} contêm a pasta .git";
            }
        }

        self::$diagCache = $diag;
        return $diag;
    }

    public static function getRepoInfo(string $repoPath): array
    {
        if (!is_dir($repoPath . DIRECTORY_SEPARATOR . '.git')) {
            return ['error' => 'Não é um repositório Git', 'name' => basename($repoPath)];
        }

        $info = [
            'path'            => $repoPath,
            'name'            => basename($repoPath),
            'branch'          => null,
            'commit_hash'     => null,
            'commit_hash_short' => null,
            'commit_message'  => null,
            'commit_author'   => null,
            'commit_date'     => null,
            'remote_url'      => null,
            'remote_name'     => null,
            'status'          => null,
            'has_changes'     => false,
            'ahead'           => 0,
            'behind'          => 0,
            'tags'            => [],
            'last_tag'        => null,
            'files_changed'   => 0,
            'untracked'       => 0,
            'errors'          => [],
        ];

        $result = self::execGit($repoPath, 'rev-parse --abbrev-ref HEAD');
        if ($result['success']) {
            $info['branch'] = trim($result['output']);
        } else {
            $info['errors'][] = 'branch: ' . $result['output'];
        }

        $result = self::execGit($repoPath, 'rev-parse HEAD');
        if ($result['success']) {
            $info['commit_hash'] = trim($result['output']);
            $info['commit_hash_short'] = substr($info['commit_hash'], 0, 7);
        } else {
            $info['errors'][] = 'hash: ' . $result['output'];
        }

        $result = self::execGit($repoPath, 'log -1 --pretty=format:"%s"');
        if ($result['success']) {
            $info['commit_message'] = trim($result['output'], '"');
        }

        $result = self::execGit($repoPath, 'log -1 --pretty=format:"%an"');
        if ($result['success']) {
            $info['commit_author'] = trim($result['output'], '"');
        }

        $result = self::execGit($repoPath, 'log -1 --pretty=format:"%ci"');
        if ($result['success']) {
            $info['commit_date'] = trim($result['output'], '"');
        }

        $result = self::execGit($repoPath, 'remote get-url origin');
        if ($result['success']) {
            $info['remote_url'] = trim($result['output']);
            $info['remote_name'] = 'origin';
        } else {
            $info['errors'][] = 'remote: ' . $result['output'];
        }

        $result = self::execGit($repoPath, 'status --porcelain');
        if ($result['success']) {
            $lines = array_filter($result['lines'], fn($l) => trim($l) !== '');
            $info['has_changes'] = count($lines) > 0;
            $info['files_changed'] = count(array_filter($lines, fn($l) => !str_starts_with(trim($l), '??')));
            $info['untracked'] = count(array_filter($lines, fn($l) => str_starts_with(trim($l), '??')));
        } else {
            $info['errors'][] = 'status: ' . $result['output'];
        }

        $result = self::execGit($repoPath, 'rev-list --count --left-right @{upstream}...HEAD');
        if ($result['success']) {
            $parts = preg_split('/\s+/', trim($result['output']));
            if (count($parts) === 2) {
                $info['behind'] = (int)$parts[0];
                $info['ahead'] = (int)$parts[1];
            }
        }

        $result = self::execGit($repoPath, 'tag --sort=-creatordate');
        if ($result['success']) {
            $tags = array_filter($result['lines'], fn($t) => trim($t) !== '');
            $info['tags'] = array_slice($tags, 0, 10);
            $info['last_tag'] = !empty($tags) ? $tags[0] : null;
        }

        $result = self::execGit($repoPath, 'describe --tags --always');
        if ($result['success']) {
            $info['describe'] = trim($result['output']);
        }

        if ($info['branch'] === null && $info['commit_hash'] === null) {
            $info['status'] = 'error';
        } elseif ($info['behind'] > 0) {
            $info['status'] = 'behind';
        } elseif ($info['has_changes']) {
            $info['status'] = 'dirty';
        } elseif ($info['ahead'] > 0) {
            $info['status'] = 'ahead';
        } else {
            $info['status'] = 'up-to-date';
        }

        return $info;
    }

    public static function getAllReposInfo(): array
    {
        $repos = self::listRepositories();
        $results = [];
        foreach ($repos as $repo) {
            $results[] = self::getRepoInfo($repo['path']);
        }
        return $results;
    }

    public static function fetch(string $repoPath): array
    {
        if (!self::validateRepoPath($repoPath)) {
            return ['success' => false, 'output' => 'Caminho inválido'];
        }
        return self::execGit($repoPath, 'fetch --all --prune');
    }

    public static function pull(string $repoPath): array
    {
        if (!self::validateRepoPath($repoPath)) {
            return ['success' => false, 'output' => 'Caminho inválido'];
        }
        return self::execGit($repoPath, 'pull');
    }

    public static function pullRebase(string $repoPath): array
    {
        if (!self::validateRepoPath($repoPath)) {
            return ['success' => false, 'output' => 'Caminho inválido'];
        }
        return self::execGit($repoPath, 'pull --rebase');
    }

    public static function getLog(string $repoPath, int $limit = 20): array
    {
        if (!self::validateRepoPath($repoPath)) {
            return [];
        }
        $result = self::execGit($repoPath, "log --oneline --graph --decorate -n {$limit}");
        return $result['success'] ? $result['lines'] : [];
    }

    public static function getDetailedLog(string $repoPath, int $limit = 20): array
    {
        if (!self::validateRepoPath($repoPath)) {
            return [];
        }

        $sep = '|||';
        $format = "%H{$sep}%h{$sep}%an{$sep}%ae{$sep}%ci{$sep}%s{$sep}%D";
        $result = self::execGit($repoPath, "log --pretty=format:\"{$format}\" -n {$limit}");

        if (!$result['success']) return [];

        $commits = [];
        foreach ($result['lines'] as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $parts = explode($sep, $line);
            if (count($parts) < 7) continue;

            $commits[] = [
                'hash'       => $parts[0],
                'hash_short' => $parts[1],
                'author'     => $parts[2],
                'email'      => $parts[3],
                'date'       => $parts[4],
                'message'    => $parts[5],
                'refs'       => $parts[6],
            ];
        }

        return $commits;
    }

    public static function getBranches(string $repoPath): array
    {
        if (!self::validateRepoPath($repoPath)) {
            return [];
        }

        $result = self::execGit($repoPath, 'branch -a --format="%(refname:short) %(objectname:short) %(upstream:short)"');
        if (!$result['success']) return [];

        $branches = [];
        foreach ($result['lines'] as $line) {
            $line = trim($line, ' "');
            if (empty($line)) continue;
            $parts = preg_split('/\s+/', $line, 3);
            $branches[] = [
                'name'     => $parts[0],
                'hash'     => $parts[1] ?? '',
                'upstream' => $parts[2] ?? '',
            ];
        }

        return $branches;
    }

    public static function checkout(string $repoPath, string $branch): array
    {
        if (!self::validateRepoPath($repoPath)) {
            return ['success' => false, 'output' => 'Caminho inválido'];
        }

        if (!preg_match('/^[a-zA-Z0-9\/\-_.]+$/', $branch)) {
            return ['success' => false, 'output' => 'Nome de branch inválido'];
        }

        return self::execGit($repoPath, 'checkout ' . escapeshellarg($branch));
    }

    public static function stashAndPull(string $repoPath): array
    {
        if (!self::validateRepoPath($repoPath)) {
            return ['success' => false, 'output' => 'Caminho inválido'];
        }

        $stash = self::execGit($repoPath, 'stash');
        $hadChanges = strpos($stash['output'], 'No local changes') === false;

        $pull = self::pull($repoPath);

        $stashPop = null;
        if ($hadChanges) {
            $stashPop = self::execGit($repoPath, 'stash pop');
        }

        return [
            'success'    => $pull['success'],
            'output'     => $pull['output'],
            'stash'      => $stash['output'],
            'stash_pop'  => $stashPop ? $stashPop['output'] : null,
            'had_changes'=> $hadChanges,
        ];
    }

    public static function forceReset(string $repoPath): array
    {
        if (!self::validateRepoPath($repoPath)) {
            return ['success' => false, 'output' => 'Caminho inválido'];
        }

        $branchResult = self::execGit($repoPath, 'rev-parse --abbrev-ref HEAD');
        if (!$branchResult['success']) {
            return ['success' => false, 'output' => 'Não foi possível detectar a branch'];
        }
        $branch = trim($branchResult['output']);

        self::fetch($repoPath);

        return self::execGit($repoPath, "reset --hard origin/{$branch}");
    }

    private static function validateRepoPath(string $repoPath): bool
    {
        $basePath = self::getBasePath();

        $realBase = realpath($basePath);
        $realRepo = realpath($repoPath);

        if ($realBase === false || $realRepo === false) {
            return false;
        }

        if (strpos($realRepo, $realBase) !== 0) {
            return false;
        }

        if (!is_dir($realRepo . DIRECTORY_SEPARATOR . '.git')) {
            return false;
        }

        return true;
    }

    public static function getDiff(string $repoPath): string
    {
        if (!self::validateRepoPath($repoPath)) {
            return '';
        }
        $result = self::execGit($repoPath, 'diff --stat');
        return $result['success'] ? $result['output'] : '';
    }

    public static function getRepoSize(string $repoPath): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $gitDir = $repoPath . DIRECTORY_SEPARATOR . '.git';
            if (is_dir($gitDir)) {
                $size = 0;
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($gitDir, \RecursiveDirectoryIterator::SKIP_DOTS)
                );
                foreach ($iterator as $file) {
                    $size += $file->getSize();
                }
                return self::formatBytes($size);
            }
            return '?';
        }

        $result = shell_exec("du -sh " . escapeshellarg($repoPath . '/.git') . " 2>/dev/null");
        if ($result) {
            return trim(explode("\t", $result)[0]);
        }
        return '?';
    }

    private static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 1) . ' ' . $units[$i];
    }
}
