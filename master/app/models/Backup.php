<?php
/**
 * Model: Backup
 * Gerencia backups do servidor via comando /bin/bkp e listagem de /bkp
 *
 * Na VPS Debian:
 *   - O comando 'sudo /bin/bkp' deve estar permitido sem senha para o usuário do PHP (www-data)
 *   - Adicionar ao sudoers: www-data ALL=(ALL) NOPASSWD: /bin/bkp
 *   - A pasta /bkp deve ser legível pelo www-data
 *
 * Em Windows/local: funcionalidade limitada (simulação)
 */

class Backup
{
    /** Caminho da pasta de backups */
    private static function getBackupPath()
    {
        if (defined('BACKUP_PATH')) return BACKUP_PATH;
        if (PHP_OS_FAMILY === 'Windows') {
            return 'd:/bkp'; // Pasta local para teste
        }
        return '/bkp';
    }

    /** Comando de backup */
    private static function getBackupCommand()
    {
        if (defined('BACKUP_COMMAND')) return BACKUP_COMMAND;
        return 'sudo /bin/bkp';
    }

    /**
     * Verifica se exec() está disponível
     */
    private static function canExec()
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
     * Executa o backup (sudo /bin/bkp)
     */
    public static function runBackup()
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
     * Lista arquivos de backup na pasta /bkp
     * Tenta scandir() primeiro, depois fallback com exec('ls') no Linux
     */
    public static function listBackups()
    {
        $path = self::getBackupPath();
        $files = [];

        if (!is_dir($path)) {
            // Tentar exec ls mesmo que is_dir falhe (pode ser permissão parcial)
            if (PHP_OS_FAMILY !== 'Windows' && self::canExec()) {
                return self::listBackupsViaExec($path);
            }
            return ['success' => false, 'files' => [], 'path' => $path, 'error' => "Pasta '{$path}' não existe ou não é acessível. Execute: sudo chmod o+rX {$path}"];
        }

        if (!is_readable($path)) {
            // Tentar exec ls
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
            
            // Incluir apenas arquivos (não diretórios)
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

        // Se scandir funcionou mas retornou vazio, tentar exec como fallback
        if (empty($files) && PHP_OS_FAMILY !== 'Windows' && self::canExec()) {
            $fallback = self::listBackupsViaExec($path);
            if (!empty($fallback['files'])) {
                return $fallback;
            }
        }

        // Ordenar por data de modificação (mais recente primeiro)
        usort($files, function ($a, $b) {
            return $b['modified_ts'] - $a['modified_ts'];
        });

        return ['success' => true, 'files' => $files, 'path' => $path];
    }

    /**
     * Fallback: Lista arquivos via exec('ls') quando scandir falha por permissão
     * Usa sudo ls se necessário
     */
    private static function listBackupsViaExec($path)
    {
        $files = [];
        $output = [];

        // Tentar ls direto, depois sudo ls
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
            // Primeiro char indica tipo: - = arquivo, d = diretório, l = link
            if ($line[0] !== '-' && $line[0] !== 'l') continue;

            // Parsear saída do ls -la --time-style=full-iso
            // -rw-r--r-- 1 root root 1234567 2026-03-06 10:30:00.000000000 -0300 filename.sql.gz
            if (preg_match('/^[\-l]\S+\s+\d+\s+\S+\s+\S+\s+(\d+)\s+(\d{4}-\d{2}-\d{2})\s+(\d{2}:\d{2}:\d{2})\.\d+\s+\S+\s+(.+)$/', $line, $m)) {
                $size = (int)$m[1];
                $dateStr = $m[2] . ' ' . $m[3];
                $name = trim($m[4]);
                // Remover symlink target
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
     * Retorna o caminho absoluto de um arquivo de backup para download
     * Valida que o arquivo está dentro da pasta de backups (segurança)
     */
    public static function getDownloadPath($filename)
    {
        $filename = basename($filename); // Sanitizar — só nome do arquivo
        $basePath = self::getBackupPath();
        $path = $basePath . '/' . $filename;

        // Verificar que o arquivo existe e está dentro da pasta de backups
        $realPath = @realpath($path);
        $realBase = @realpath($basePath);

        // Se realpath falha, tentar construir manualmente (pode falhar por permissão)
        if ($realPath === false && $realBase !== false) {
            $candidate = $realBase . '/' . $filename;
            if (@file_exists($candidate)) {
                $realPath = $candidate;
            }
        }
        if ($realBase === false) {
            // Tentar usar o path direto
            $realBase = rtrim($basePath, '/');
            $realPath = $realBase . '/' . $filename;
        }

        if ($realPath === false || $realBase === false) {
            return null;
        }

        if (strpos($realPath, $realBase) !== 0) {
            return null; // Tentativa de path traversal
        }

        if (@is_file($realPath) && @is_readable($realPath)) {
            return $realPath;
        }

        // Fallback: tentar copiar via sudo para /tmp para download
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
     * Exclui um arquivo de backup
     * Requer nome exato do arquivo para confirmação
     */
    public static function deleteBackup($filename)
    {
        $filename = basename($filename); // Sanitizar
        $basePath = self::getBackupPath();
        $path = $basePath . '/' . $filename;

        // Verificar caminho seguro
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

        // Tentar excluir diretamente
        if (@is_file($realPath) && @is_writable($realPath)) {
            if (@unlink($realPath)) {
                return ['success' => true, 'message' => "Arquivo '{$filename}' excluído com sucesso."];
            }
            return ['success' => false, 'error' => "Erro ao excluir '{$filename}'."];
        }

        // Fallback: tentar via sudo rm no Linux
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
     * Diagnóstico do módulo de backup
     */
    public static function diagnose()
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

        // Verificar se o comando de backup existe
        if (PHP_OS_FAMILY !== 'Windows' && $diag['exec_available']) {
            $out = [];
            @exec('test -x /bin/bkp && echo "OK" || echo "MISSING" 2>&1', $out);
            if (trim(implode('', $out)) !== 'OK') {
                $diag['issues'][] = 'Script /bin/bkp não encontrado ou sem permissão de execução';
                $diag['fixes'][] = 'Verificar se o script /bin/bkp existe e é executável';
            }

            // Verificar sudoers — APENAS checa permissão, NÃO executa o backup!
            // sudo -n -l /bin/bkp  → verifica se o comando é permitido sem senha
            // NUNCA usar sudo -n /bin/bkp --dry-run pois o script pode não suportar e executar o backup real
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

    private static function formatBytes($bytes)
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
