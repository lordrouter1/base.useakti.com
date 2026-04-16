<?php

namespace Akti\Controllers\Master;

use Akti\Models\Master\TenantClient;

/**
 * Class HealthCheckController.
 */
class HealthCheckController extends MasterBaseController
{
    /**
     * Exibe a página de listagem.
     * @return void
     */
    public function index(): void
    {
        $this->requireMasterAuth();

        $clientModel = new TenantClient($this->db);
        $tenants = $clientModel->readAll();

        $health = [
            'mysql_master'  => $this->checkMasterDb(),
            'tenant_dbs'    => $this->checkTenantDbs($tenants),
            'disk'          => $this->checkDiskSpace(),
            'php'           => $this->checkPhpInfo(),
            'node_api'      => $this->checkNodeApi(),
            'pending_sql'   => $this->checkPendingSql(),
        ];

        $this->renderMaster('health/index', compact('health', 'tenants'));
    }

    /**
     * JSON endpoint for auto-refresh.
     */
    public function statusJson(): void
    {
        $this->requireMasterAuth();

        $clientModel = new TenantClient($this->db);
        $tenants = $clientModel->readAll();

        $health = [
            'mysql_master'  => $this->checkMasterDb(),
            'tenant_dbs'    => $this->checkTenantDbs($tenants),
            'disk'          => $this->checkDiskSpace(),
            'php'           => $this->checkPhpInfo(),
            'node_api'      => $this->checkNodeApi(),
            'pending_sql'   => $this->checkPendingSql(),
        ];

        $this->json(['success' => true, 'health' => $health, 'timestamp' => date('Y-m-d H:i:s')]);
    }

    /**
     * Verifica condição ou estado.
     * @return array
     */
    private function checkMasterDb(): array
    {
        try {
            $start = microtime(true);
            $stmt = $this->db->query("SELECT 1");
            $latency = round((microtime(true) - $start) * 1000, 1);

            $version = $this->db->query("SELECT VERSION()")->fetchColumn();
            $uptime = $this->db->query("SHOW STATUS LIKE 'Uptime'")->fetch();
            $threads = $this->db->query("SHOW STATUS LIKE 'Threads_connected'")->fetch();

            return [
                'status'     => 'ok',
                'latency_ms' => $latency,
                'version'    => $version,
                'uptime_s'   => (int)($uptime['Value'] ?? 0),
                'threads'    => (int)($threads['Value'] ?? 0),
            ];
        } catch (\Exception $e) {
            return [
                'status'  => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verifica condição ou estado.
     *
     * @param array $tenants Tenants
     * @return array
     */
    private function checkTenantDbs(array $tenants): array
    {
        $results = [];
        $creds = \TenantManager::getMasterConfig();

        foreach ($tenants as $t) {
            try {
                $start = microtime(true);
                $pdo = \Database::connectTo(
                    $t['db_host'] ?: $creds['host'],
                    $t['db_port'] ?: $creds['port'],
                    $creds['username'],
                    $creds['password'],
                    $t['db_name']
                );
                $pdo->query("SELECT 1");
                $latency = round((microtime(true) - $start) * 1000, 1);

                $tableCount = $pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()")->fetchColumn();

                $results[$t['db_name']] = [
                    'status'      => 'ok',
                    'latency_ms'  => $latency,
                    'tables'      => (int)$tableCount,
                    'client_name' => $t['client_name'],
                    'is_active'   => $t['is_active'],
                ];
            } catch (\Exception $e) {
                $results[$t['db_name']] = [
                    'status'      => 'error',
                    'message'     => $e->getMessage(),
                    'client_name' => $t['client_name'],
                    'is_active'   => $t['is_active'],
                ];
            }
        }

        return $results;
    }

    /**
     * Verifica condição ou estado.
     * @return array
     */
    private function checkDiskSpace(): array
    {
        $path = $_SERVER['DOCUMENT_ROOT'] ?: '.';
        $total = @disk_total_space($path);
        $free = @disk_free_space($path);

        if ($total === false || $free === false) {
            return ['status' => 'unknown', 'message' => 'Não foi possível verificar espaço em disco'];
        }

        $used = $total - $free;
        $usedPercent = round(($used / $total) * 100, 1);

        return [
            'status'       => $usedPercent > 90 ? 'critical' : ($usedPercent > 75 ? 'warning' : 'ok'),
            'total_gb'     => round($total / (1024 ** 3), 2),
            'free_gb'      => round($free / (1024 ** 3), 2),
            'used_gb'      => round($used / (1024 ** 3), 2),
            'used_percent' => $usedPercent,
        ];
    }

    /**
     * Verifica condição ou estado.
     * @return array
     */
    private function checkPhpInfo(): array
    {
        return [
            'status'         => 'ok',
            'version'        => PHP_VERSION,
            'memory_limit'   => ini_get('memory_limit'),
            'memory_usage'   => round(memory_get_usage(true) / (1024 * 1024), 1),
            'max_upload'     => ini_get('upload_max_filesize'),
            'max_post'       => ini_get('post_max_size'),
            'max_exec_time'  => ini_get('max_execution_time'),
            'opcache'        => function_exists('opcache_get_status') && @opcache_get_status() !== false,
            'extensions'     => [
                'pdo_mysql' => extension_loaded('pdo_mysql'),
                'mbstring'  => extension_loaded('mbstring'),
                'openssl'   => extension_loaded('openssl'),
                'curl'      => extension_loaded('curl'),
                'gd'        => extension_loaded('gd'),
                'zip'       => extension_loaded('zip'),
            ],
        ];
    }

    /**
     * Verifica condição ou estado.
     * @return array
     */
    private function checkNodeApi(): array
    {
        $apiUrl = getenv('AKTI_NODE_API_URL') ?: 'http://localhost:3000';

        try {
            $ctx = stream_context_create([
                'http' => [
                    'timeout' => 3,
                    'method'  => 'GET',
                ],
            ]);

            $start = microtime(true);
            $response = @file_get_contents($apiUrl . '/health', false, $ctx);
            $latency = round((microtime(true) - $start) * 1000, 1);

            if ($response === false) {
                return ['status' => 'offline', 'message' => 'API Node.js não está respondendo'];
            }

            $data = json_decode($response, true);
            return [
                'status'     => 'ok',
                'latency_ms' => $latency,
                'response'   => $data,
            ];
        } catch (\Exception $e) {
            return ['status' => 'offline', 'message' => $e->getMessage()];
        }
    }

    /**
     * Verifica condição ou estado.
     * @return array
     */
    private function checkPendingSql(): array
    {
        $sqlDir = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . '/sql';
        $count = 0;
        $files = [];

        if (is_dir($sqlDir)) {
            foreach (scandir($sqlDir) as $entry) {
                if ($entry !== '.' && $entry !== '..' && $entry !== 'prontos' && pathinfo($entry, PATHINFO_EXTENSION) === 'sql') {
                    $count++;
                    $files[] = $entry;
                }
            }
        }

        return [
            'status' => $count > 0 ? 'warning' : 'ok',
            'count'  => $count,
            'files'  => $files,
        ];
    }
}
