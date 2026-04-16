<?php

namespace Akti\Models\Master;

/**
 * Model de backups de banco de dados.
 */
class Backup
{
    /**
     * Obtém dados específicos.
     * @return string
     */
    private static function getBackupPath(): string
    {
        if (defined('BACKUP_PATH')) return BACKUP_PATH;
        if (PHP_OS_FAMILY === 'Windows') {
            return 'd:/bkp';
        }
        return '/bkp';
    }

    /**
     * Obtém dados específicos.
     * @return string
     */
    private static function getBackupCommand(): string
    {
        if (defined('BACKUP_COMMAND')) return BACKUP_COMMAND;
        return 'sudo /bin/bkp';
    }

    /**
     * Verifica permissão ou capacidade.
     * @return bool
     */
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

    /**
     * Executa um processo.
     * @return array
     */
    public static function runBackup(): array
    {
        if (!self::canExec()) {
            return ['success' => false, 'output' => 'exec() está desabilitado'];
        }

        $cmd = self::getBackupCommand() . ' 2>&1';
        $output = [];
        $returnCode = 0;
        @exec($cmd, $output, $returnCode);

        return [
            'success'     => $returnCode === 0,
            'output'      => implode("\n", $output),
            'return_code' => $returnCode,
            'cmd'         => $cmd,
        ];
    }

    /**
     * List backups.
     * @return array
     */
    public static function listBackups(): array
    {
        $path = self::getBackupPath();
        $files = [];

        if (!is_dir($path)) {
            if (PHP_OS_FAMILY !== 'Windows' && self::canExec()) {
                return self::listBackupsViaExec($path);
            }
            return ['success' => false, 'files' => [], 'path' => $path, 'error' => "Pasta '{$path}' não existe ou não é acessível. Execute: sudo chmod o+rX {$path}"];
        }

        if (!is_readable($path)) {
            if (PHP_OS_FAMILY !== 'Windows' && self::canExec()) {
                return self::listBackupsViaExec($path);
            }
            return ['success' => false, 'files' => [], 'path' => $path, 'error' => "Sem permissão de leitura em '{$path}'. Execute: sudo chmod o+rX {$path}"];
        }

        $entries = @scandir($path);
        if ($entries === false) {
            if (PHP_OS_FAMILY !== 'Windows' && self::canExec()) {
                return self::listBackupsViaExec($path);
            }
            return ['success' => false, 'files' => [], 'path' => $path, 'error' => "Erro ao ler pasta '{$path}'"];
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') continue;

            $fullPath = $path . '/' . $entry;
            if (is_dir($fullPath)) continue;

            $stat = @stat($fullPath);
            $files[] = [
                'name'         => $entry,
                'path'         => $fullPath,
                'size'         => $stat ? $stat['size'] : 0,
                'size_human'   => $stat ? self::formatBytes($stat['size']) : '?',
                'modified'     => $stat ? date('Y-m-d H:i:s', $stat['mtime']) : '?',
                'modified_ts'  => $stat ? $stat['mtime'] : 0,
                'created'      => $stat ? date('Y-m-d H:i:s', $stat['ctime']) : '?',
                'extension'    => pathinfo($entry, PATHINFO_EXTENSION),
                'downloadable' => is_readable($fullPath),
            ];
        }

        if (empty($files) && PHP_OS_FAMILY !== 'Windows' && self::canExec()) {
            $fallback = self::listBackupsViaExec($path);
            if (!empty($fallback['files'])) {
                return $fallback;
            }
        }

        usort($files, function ($a, $b) {
            return $b['modified_ts'] - $a['modified_ts'];
        });

        return ['success' => true, 'files' => $files, 'path' => $path];
    }

 /**
  * List backups via exec.
  *
  * @param string $path Caminho do arquivo
  * @return array
  */
    private static function listBackupsViaExec(string $path): array
    {
        $files = [];
        $output = [];

        $commands = [
            'ls -la --time-style=full-iso ' . escapeshellarg($path) . ' 2>&1',
            'sudo ls -la --time-style=full-iso ' . escapeshellarg($path) . ' 2>&1',
        ];

        foreach ($commands as $cmd) {
            $output = [];
            $rc = 0;
            @exec($cmd, $output, $rc);
            if ($rc === 0 && count($output) > 1) break;
        }

        if (empty($output) || count($output) <= 1) {
            return ['success' => false, 'files' => [], 'path' => $path, 'error' => "Erro ao listar '{$path}' via exec. Verifique permissões."];
        }

        foreach ($output as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, 'total') === 0) continue;
            if ($line[0] !== '-' && $line[0] !== 'l') continue;

            if (preg_match('/^[\-l]\S+\s+\d+\s+\S+\s+\S+\s+(\d+)\s+(\d{4}-\d{2}-\d{2})\s+(\d{2}:\d{2}:\d{2})\.\d+\s+\S+\s+(.+)$/', $line, $m)) {
                $size = (int)$m[1];
                $dateStr = $m[2] . ' ' . $m[3];
                $name = trim($m[4]);
                if (strpos($name, ' -> ') !== false) {
                    $name = explode(' -> ', $name)[0];
                }
                $name = trim($name);
                if (empty($name) || $name === '.' || $name === '..') continue;

                $ts = strtotime($dateStr);
                $fullPath = $path . '/' . $name;

                $files[] = [
                    'name'         => $name,
                    'path'         => $fullPath,
                    'size'         => $size,
                    'size_human'   => self::formatBytes($size),
                    'modified'     => $dateStr,
                    'modified_ts'  => $ts ?: 0,
                    'created'      => $dateStr,
                    'extension'    => pathinfo($name, PATHINFO_EXTENSION),
                    'downloadable' => @is_readable($fullPath),
                ];
            }
        }

        usort($files, function ($a, $b) {
            return $b['modified_ts'] - $a['modified_ts'];
        });

        return ['success' => true, 'files' => $files, 'path' => $path, 'method' => 'exec'];
    }

 /**
  * Get download path.
  *
  * @param string $filename Nome do arquivo
  * @return string|null
  */
    public static function getDownloadPath(string $filename): ?string
    {
        $filename = basename($filename);
        $basePath = self::getBackupPath();
        $path = $basePath . '/' . $filename;

        $realPath = @realpath($path);
        $realBase = @realpath($basePath);

        if ($realPath === false && $realBase !== false) {
            $candidate = $realBase . '/' . $filename;
            if (@file_exists($candidate)) {
                $realPath = $candidate;
            }
        }
        if ($realBase === false) {
            $realBase = rtrim($basePath, '/');
            $realPath = $realBase . '/' . $filename;
        }

        if ($realPath === false || $realBase === false) {
            return null;
        }

        if (strpos($realPath, $realBase) !== 0) {
            return null;
        }

        if (@is_file($realPath) && @is_readable($realPath)) {
            return $realPath;
        }

        if (PHP_OS_FAMILY !== 'Windows' && self::canExec()) {
            $tmpFile = sys_get_temp_dir() . '/akti_bkp_' . md5($filename) . '_' . $filename;
            $cmd = 'sudo cp ' . escapeshellarg($realPath) . ' ' . escapeshellarg($tmpFile) . ' 2>&1 && sudo chmod 644 ' . escapeshellarg($tmpFile) . ' 2>&1';
            @exec($cmd, $out, $rc);
            if ($rc === 0 && is_file($tmpFile) && is_readable($tmpFile)) {
                return $tmpFile;
            }
        }

        return null;
    }

 /**
  * Delete backup.
  *
  * @param string $filename Nome do arquivo
  * @return array
  */
    public static function deleteBackup(string $filename): array
    {
        $filename = basename($filename);
        $basePath = self::getBackupPath();
        $path = $basePath . '/' . $filename;

        $realPath = @realpath($path);
        $realBase = @realpath($basePath);

        if ($realBase === false) {
            $realBase = rtrim($basePath, '/');
            $realPath = $realBase . '/' . $filename;
        }

        if ($realPath === false || $realBase === false) {
            return ['success' => false, 'error' => 'Caminho do arquivo inválido.'];
        }

        if (strpos($realPath, $realBase) !== 0) {
            return ['success' => false, 'error' => 'Tentativa de exclusão fora da pasta de backups.'];
        }

        if (@is_file($realPath) && @is_writable($realPath)) {
            if (@unlink($realPath)) {
                return ['success' => true, 'message' => "Arquivo '{$filename}' excluído com sucesso."];
            }
            return ['success' => false, 'error' => "Erro ao excluir '{$filename}'."];
        }

        if (PHP_OS_FAMILY !== 'Windows' && self::canExec()) {
            $cmd = 'sudo rm -f ' . escapeshellarg($realPath) . ' 2>&1';
            $output = [];
            $rc = 0;
            @exec($cmd, $output, $rc);
            if ($rc === 0) {
                return ['success' => true, 'message' => "Arquivo '{$filename}' excluído com sucesso (via sudo)."];
            }
            return ['success' => false, 'error' => "Erro ao excluir '{$filename}' via sudo: " . implode(' ', $output)];
        }

        return ['success' => false, 'error' => "Sem permissão para excluir '{$filename}'. Verifique permissões da pasta."];
    }

 /**
  * Diagnose.
  * @return array
  */
    public static function diagnose(): array
    {
        $path = self::getBackupPath();
        $diag = [
            'backup_path'     => $path,
            'path_exists'     => is_dir($path),
            'path_readable'   => is_dir($path) && is_readable($path),
            'exec_available'  => self::canExec(),
            'backup_command'  => self::getBackupCommand(),
            'issues'          => [],
            'fixes'           => [],
        ];

        if (!$diag['path_exists']) {
            $diag['issues'][] = "Pasta de backups não existe: {$path}";
            $diag['fixes'][] = "sudo mkdir -p {$path} && sudo chmod o+rX {$path}";
        } elseif (!$diag['path_readable']) {
            $diag['issues'][] = "Sem permissão de leitura em: {$path}";
            $diag['fixes'][] = "sudo chmod o+rX {$path}";
        }

        if (!$diag['exec_available']) {
            $diag['issues'][] = 'exec() desabilitado — não será possível executar backup';
        }

        if (PHP_OS_FAMILY !== 'Windows' && $diag['exec_available']) {
            $out = [];
            @exec('test -x /bin/bkp && echo "OK" || echo "MISSING" 2>&1', $out);
            if (trim(implode('', $out)) !== 'OK') {
                $diag['issues'][] = 'Script /bin/bkp não encontrado ou sem permissão de execução';
                $diag['fixes'][] = 'Verificar se o script /bin/bkp existe e é executável';
            }

            $sudoOut = [];
            @exec('sudo -n -l /bin/bkp 2>&1', $sudoOut, $sudoRc);
            if ($sudoRc !== 0) {
                $sudoOutput = implode(' ', $sudoOut);
                if (stripos($sudoOutput, 'password') !== false || stripos($sudoOutput, 'sorry') !== false) {
                    $phpUser = 'www-data';
                    if (function_exists('posix_geteuid') && function_exists('posix_getpwuid')) {
                        $pw = @posix_getpwuid(posix_geteuid());
                        $phpUser = $pw['name'] ?? 'www-data';
                    }
                    $diag['issues'][] = 'sudo requer senha para executar /bin/bkp';
                    $diag['fixes'][] = "Adicionar ao sudoers: echo '{$phpUser} ALL=(ALL) NOPASSWD: /bin/bkp' | sudo tee /etc/sudoers.d/akti-backup";
                }
            }
        }

        return $diag;
    }

 /**
  * Format bytes.
  *
  * @param int $bytes Bytes
  * @return string
  */
    private static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
