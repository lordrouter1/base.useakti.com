<?php
namespace Akti\Controllers;

use Akti\Core\Log;

/**
 * HealthController
 *
 * Endpoint de health check para monitoramento e verificação de status.
 * Verifica: banco de dados, sistema de arquivos, backup recente, versão PHP.
 *
 * Endpoints:
 *   ?page=health&action=check   → JSON com status detalhado
 *   ?page=health&action=ping    → Resposta simples "pong" (para uptime monitors)
 */
class HealthController
{
    /** @var \PDO|null */
    private $db;

    /**
     * Construtor da classe HealthController.
     *
     * @param \PDO|null $db Conexão PDO com o banco de dados
     */
    public function __construct(?\PDO $db = null)
    {
        $this->db = $db;
    }

    /**
     * Ping simples — para uptime monitors (UptimeRobot, Pingdom, etc.)
     */
    public function ping(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'ok',
            'timestamp' => date('c'),
        ]);
        exit;
    }

    /**
     * Health check completo — verifica todos os componentes.
     */
    public function check(): void
    {
        $checks = [];
        $allHealthy = true;

        // 1. PHP Version
        $checks['php'] = [
            'status'  => 'ok',
            'version' => PHP_VERSION,
            'sapi'    => PHP_SAPI,
        ];

        // 2. Database connectivity
        $checks['database'] = $this->checkDatabase();
        if ($checks['database']['status'] !== 'ok') {
            $allHealthy = false;
        }

        // 3. Filesystem (write permissions)
        $checks['filesystem'] = $this->checkFilesystem();
        if ($checks['filesystem']['status'] !== 'ok') {
            $allHealthy = false;
        }

        // 4. Last backup check
        $checks['backup'] = $this->checkLastBackup();
        if ($checks['backup']['status'] === 'error') {
            $allHealthy = false;
        }

        // 5. Disk space
        $checks['disk'] = $this->checkDiskSpace();
        if ($checks['disk']['status'] !== 'ok') {
            $allHealthy = false;
        }

        // 6. Required PHP extensions
        $checks['extensions'] = $this->checkExtensions();
        if ($checks['extensions']['status'] !== 'ok') {
            $allHealthy = false;
        }

        $response = [
            'status'    => $allHealthy ? 'healthy' : 'degraded',
            'timestamp' => date('c'),
            'uptime'    => $this->getUptime(),
            'checks'    => $checks,
        ];

        http_response_code($allHealthy ? 200 : 503);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Verifica conectividade com o banco de dados.
     */
    private function checkDatabase(): array
    {
        if (!$this->db) {
            return ['status' => 'error', 'message' => 'No database connection'];
        }

        try {
            $start = microtime(true);
            $stmt = $this->db->query('SELECT 1');
            $latency = round((microtime(true) - $start) * 1000, 2);

            return [
                'status'     => 'ok',
                'latency_ms' => $latency,
            ];
        } catch (\Throwable $e) {
            Log::channel('general')->error('Health check: database failed', [
                'error' => $e->getMessage(),
            ]);
            return ['status' => 'error', 'message' => 'Database query failed'];
        }
    }

    /**
     * Verifica permissões de escrita no filesystem.
     */
    private function checkFilesystem(): array
    {
        $dirs = [
            'storage/logs' => dirname(__DIR__, 2) . '/storage/logs',
            'assets/uploads' => dirname(__DIR__, 2) . '/assets/uploads',
        ];

        $writable = [];
        $allOk = true;

        foreach ($dirs as $label => $path) {
            $isWritable = is_dir($path) && is_writable($path);
            $writable[$label] = $isWritable;
            if (!$isWritable) {
                $allOk = false;
            }
        }

        return [
            'status'      => $allOk ? 'ok' : 'warning',
            'directories' => $writable,
        ];
    }

    /**
     * Verifica se há um backup recente (últimas 48h).
     */
    private function checkLastBackup(): array
    {
        $backupDir = dirname(__DIR__, 2) . '/storage/backups';

        if (!is_dir($backupDir)) {
            return ['status' => 'warning', 'message' => 'Backup directory not found'];
        }

        $files = glob($backupDir . '/*.sql.gz');
        if (empty($files)) {
            $files = glob($backupDir . '/*.sql');
        }

        if (empty($files)) {
            return ['status' => 'warning', 'message' => 'No backup files found'];
        }

        // Find most recent
        $latestTime = 0;
        $latestFile = '';
        foreach ($files as $file) {
            $mtime = filemtime($file);
            if ($mtime > $latestTime) {
                $latestTime = $mtime;
                $latestFile = basename($file);
            }
        }

        $hoursAgo = round((time() - $latestTime) / 3600, 1);
        $isRecent = $hoursAgo <= 48;

        return [
            'status'      => $isRecent ? 'ok' : 'warning',
            'last_backup' => $latestFile,
            'hours_ago'   => $hoursAgo,
            'last_date'   => date('Y-m-d H:i:s', $latestTime),
        ];
    }

    /**
     * Verifica espaço em disco disponível.
     */
    private function checkDiskSpace(): array
    {
        $path = dirname(__DIR__, 2);
        $free = disk_free_space($path);
        $total = disk_total_space($path);

        if ($free === false || $total === false) {
            return ['status' => 'warning', 'message' => 'Cannot determine disk space'];
        }

        $usedPct = round(($total - $free) / $total * 100, 1);
        $freeGb = round($free / (1024 * 1024 * 1024), 2);

        return [
            'status'       => $usedPct < 90 ? 'ok' : ($usedPct < 95 ? 'warning' : 'error'),
            'free_gb'      => $freeGb,
            'used_percent' => $usedPct,
        ];
    }

    /**
     * Verifica extensões PHP necessárias.
     */
    private function checkExtensions(): array
    {
        $required = ['pdo_mysql', 'mbstring', 'json', 'openssl', 'curl', 'gd'];
        $missing = [];

        foreach ($required as $ext) {
            if (!extension_loaded($ext)) {
                $missing[] = $ext;
            }
        }

        return [
            'status'  => empty($missing) ? 'ok' : 'error',
            'missing' => $missing,
        ];
    }

    /**
     * Retorna informação de uptime do processo.
     */
    private function getUptime(): string
    {
        if (function_exists('getrusage')) {
            return 'N/A (PHP-FPM)';
        }
        return 'N/A';
    }
}
