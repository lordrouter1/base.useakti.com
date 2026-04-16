<?php

namespace Akti\Models\Master;

/**
 * Model de logs do Nginx.
 */
class NginxLog
{
    /**
     * Obtém dados específicos.
     * @return string
     */
    private static function getLogPath(): string
    {
        if (defined('NGINX_LOG_PATH')) return NGINX_LOG_PATH;
        if (PHP_OS_FAMILY === 'Windows') {
            return 'd:/xampp/logs';
        }
        return '/var/log/nginx';
    }

    /**
     * List log files.
     * @return array
     */
    public static function listLogFiles(): array
    {
        $path = self::getLogPath();
        $files = [];

        if (!is_dir($path) || !is_readable($path)) {
            return ['success' => false, 'files' => [], 'error' => "Pasta '{$path}' não existe ou não é legível"];
        }

        $entries = @scandir($path);
        if ($entries === false) {
            return ['success' => false, 'files' => [], 'error' => "Erro ao ler '{$path}'"];
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            $fullPath = $path . '/' . $entry;
            if (!is_file($fullPath)) continue;

            $ext = pathinfo($entry, PATHINFO_EXTENSION);
            $isLog = (stripos($entry, 'log') !== false || stripos($entry, 'error') !== false || stripos($entry, 'access') !== false || $ext === 'log');
            if (!$isLog && !in_array($ext, ['log', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'gz'])) continue;

            $stat = @stat($fullPath);
            $isError = (stripos($entry, 'error') !== false);
            $isAccess = (stripos($entry, 'access') !== false);
            $isCompressed = ($ext === 'gz');

            $files[] = [
                'name'       => $entry,
                'path'       => $fullPath,
                'size'       => $stat ? $stat['size'] : 0,
                'size_human' => $stat ? self::formatBytes($stat['size']) : '?',
                'modified'   => $stat ? date('Y-m-d H:i:s', $stat['mtime']) : '?',
                'modified_ts'=> $stat ? $stat['mtime'] : 0,
                'readable'   => is_readable($fullPath),
                'type'       => $isError ? 'error' : ($isAccess ? 'access' : 'other'),
                'compressed' => $isCompressed,
            ];
        }

        usort($files, function ($a, $b) {
            if ($a['type'] !== $b['type']) {
                $order = ['error' => 0, 'access' => 1, 'other' => 2];
                return ($order[$a['type']] ?? 9) - ($order[$b['type']] ?? 9);
            }
            return $b['modified_ts'] - $a['modified_ts'];
        });

        return ['success' => true, 'files' => $files, 'path' => $path];
    }

 /**
  * Read tail.
  *
  * @param string $filename Nome do arquivo
  * @param int $lines Lines
  * @return array
  */
    public static function readTail(string $filename, int $lines = 200): array
    {
        $filename = basename($filename);
        $path = self::getLogPath() . '/' . $filename;

        $realPath = @realpath($path);
        $realBase = @realpath(self::getLogPath());

        if ($realPath === false || $realBase === false || strpos($realPath, $realBase) !== 0) {
            return ['success' => false, 'content' => '', 'error' => 'Arquivo inválido'];
        }

        if (!is_file($realPath) || !is_readable($realPath)) {
            return ['success' => false, 'content' => '', 'error' => 'Arquivo não legível'];
        }

        if (pathinfo($filename, PATHINFO_EXTENSION) === 'gz') {
            return self::readGzTail($realPath, $lines);
        }

        $lines = min(max($lines, 10), 2000);

        if (PHP_OS_FAMILY !== 'Windows' && function_exists('exec')) {
            $output = [];
            @exec('tail -n ' . (int)$lines . ' ' . escapeshellarg($realPath) . ' 2>&1', $output);
            $content = implode("\n", $output);
        } else {
            $content = self::phpTail($realPath, $lines);
        }

        return [
            'success'  => true,
            'content'  => $content,
            'filename' => $filename,
            'size'     => filesize($realPath),
            'lines'    => $lines,
        ];
    }

 /**
  * Read gz tail.
  *
  * @param string $path Caminho do arquivo
  * @param int $lines Lines
  * @return array
  */
    private static function readGzTail(string $path, int $lines): array
    {
        if (PHP_OS_FAMILY !== 'Windows' && function_exists('exec')) {
            $output = [];
            @exec('zcat ' . escapeshellarg($path) . ' 2>/dev/null | tail -n ' . (int)$lines, $output);
            return [
                'success'  => true,
                'content'  => implode("\n", $output),
                'filename' => basename($path),
                'lines'    => $lines,
            ];
        }

        $gz = @gzopen($path, 'rb');
        if (!$gz) {
            return ['success' => false, 'content' => '', 'error' => 'Não foi possível abrir arquivo .gz'];
        }

        $allLines = [];
        while (!gzeof($gz)) {
            $line = gzgets($gz, 8192);
            if ($line !== false) {
                $allLines[] = rtrim($line);
                if (count($allLines) > $lines * 2) {
                    $allLines = array_slice($allLines, -$lines);
                }
            }
        }
        gzclose($gz);

        return [
            'success'  => true,
            'content'  => implode("\n", array_slice($allLines, -$lines)),
            'filename' => basename($path),
            'lines'    => $lines,
        ];
    }

 /**
  * Php tail.
  *
  * @param string $path Caminho do arquivo
  * @param int $lines Lines
  * @return string
  */
    private static function phpTail(string $path, int $lines): string
    {
        $fp = @fopen($path, 'rb');
        if (!$fp) return '';

        $buffer = '';
        $chunk = 4096;
        fseek($fp, 0, SEEK_END);
        $pos = ftell($fp);
        $lineCount = 0;

        while ($pos > 0 && $lineCount < $lines + 1) {
            $readSize = min($chunk, $pos);
            $pos -= $readSize;
            fseek($fp, $pos);
            $data = fread($fp, $readSize);
            $buffer = $data . $buffer;
            $lineCount = substr_count($buffer, "\n");
        }
        fclose($fp);

        $allLines = explode("\n", $buffer);
        $result = array_slice($allLines, -$lines);
        return implode("\n", $result);
    }

 /**
  * Search.
  *
  * @param string $filename Nome do arquivo
  * @param string $query Consulta de busca
  * @param int $maxResults Max results
  * @return array
  */
    public static function search(string $filename, string $query, int $maxResults = 100): array
    {
        $filename = basename($filename);
        $path = self::getLogPath() . '/' . $filename;

        $realPath = @realpath($path);
        $realBase = @realpath(self::getLogPath());
        if ($realPath === false || $realBase === false || strpos($realPath, $realBase) !== 0) {
            return ['success' => false, 'results' => [], 'error' => 'Arquivo inválido'];
        }

        if (!is_file($realPath) || !is_readable($realPath)) {
            return ['success' => false, 'results' => [], 'error' => 'Arquivo não legível'];
        }

        $results = [];
        if (PHP_OS_FAMILY !== 'Windows' && function_exists('exec')) {
            $output = [];
            @exec('grep -n -i ' . escapeshellarg($query) . ' ' . escapeshellarg($realPath) . ' 2>/dev/null | tail -n ' . (int)$maxResults, $output);
            foreach ($output as $line) {
                $results[] = $line;
            }
        } else {
            $fp = @fopen($realPath, 'r');
            if ($fp) {
                $lineNum = 0;
                while (($line = fgets($fp)) !== false) {
                    $lineNum++;
                    if (stripos($line, $query) !== false) {
                        $results[] = $lineNum . ':' . rtrim($line);
                        if (count($results) >= $maxResults) break;
                    }
                }
                fclose($fp);
            }
        }

        return ['success' => true, 'results' => $results, 'count' => count($results)];
    }

 /**
  * Analyze errors.
  *
  * @param string $filename Nome do arquivo
  * @param int $limit Limite de registros
  * @return array
  */
    public static function analyzeErrors(string $filename, int $limit = 20): array
    {
        $tail = self::readTail($filename, 1000);
        if (!$tail['success']) return [];

        $errors = [];
        foreach (explode("\n", $tail['content']) as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            if (preg_match('/\b(PHP (?:Warning|Fatal error|Notice|Parse error|Deprecated)[^:]*)/i', $line, $m)) {
                $key = trim($m[1]);
            } elseif (preg_match('/\b(\d{4}\/\d{2}\/\d{2}.*?\[error\])/i', $line, $m)) {
                $key = 'nginx error';
            } else {
                $key = 'Outro';
            }

            if (!isset($errors[$key])) $errors[$key] = 0;
            $errors[$key]++;
        }

        arsort($errors);
        return array_slice($errors, 0, $limit, true);
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
        $basePath = self::getLogPath();
        $path = $basePath . '/' . $filename;

        $realPath = @realpath($path);
        $realBase = @realpath($basePath);

        if ($realPath === false || $realBase === false) {
            return null;
        }

        if (strpos($realPath, $realBase) !== 0) {
            return null;
        }

        if (@is_file($realPath) && @is_readable($realPath)) {
            return $realPath;
        }

        return null;
    }

 /**
  * Diagnose.
  * @return array
  */
    public static function diagnose(): array
    {
        $path = self::getLogPath();
        $diag = [
            'log_path'       => $path,
            'path_exists'    => is_dir($path),
            'path_readable'  => is_dir($path) && is_readable($path),
            'issues'         => [],
            'fixes'          => [],
        ];

        if (!$diag['path_exists']) {
            $diag['issues'][] = "Pasta de logs não existe: {$path}";
        } elseif (!$diag['path_readable']) {
            $phpUser = 'www-data';
            if (function_exists('posix_geteuid') && function_exists('posix_getpwuid')) {
                $pw = @posix_getpwuid(posix_geteuid());
                $phpUser = $pw['name'] ?? 'www-data';
            }
            $diag['issues'][] = "Sem permissão de leitura em: {$path}";
            $diag['fixes'][] = "sudo chmod o+rX {$path} && sudo chmod o+r {$path}/*.log";
            $diag['fixes'][] = "Ou adicionar o usuário '{$phpUser}' ao grupo adm: sudo usermod -aG adm {$phpUser} && sudo systemctl restart php*-fpm";
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
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 1) . ' ' . $units[$i];
    }
}
