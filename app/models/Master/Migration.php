<?php

namespace Akti\Models\Master;

use PDO;
use PDOException;

class Migration
{
    private $db;
    private ?array $migrationLogColumns = null;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Detecta as colunas existentes na tabela migration_logs do master.
     */
    private function getMigrationLogColumns(): array
    {
        if ($this->migrationLogColumns === null) {
            try {
                $stmt = $this->db->query("SHOW COLUMNS FROM migration_logs");
                $this->migrationLogColumns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
            } catch (\Exception $e) {
                $this->migrationLogColumns = [];
            }
        }
        return $this->migrationLogColumns;
    }

    /**
     * Insere log de migração adaptando-se às colunas disponíveis.
     */
    private function insertMigrationLog(string $dbName, string $migrationName, string $sqlHash, array $result, string $sql, int $adminId): void
    {
        $columns = $this->getMigrationLogColumns();
        if (empty($columns)) {
            return; // tabela migration_logs não existe no master
        }

        $hasExtendedCols = in_array('sql_content', $columns)
            && in_array('warnings', $columns)
            && in_array('execution_time_ms', $columns);

        $status = 'success';
        if ($result['failed'] > 0 && $result['ok'] === 0) {
            $status = 'failed';
        } elseif ($result['failed'] > 0) {
            $status = 'partial';
        }

        $errorLog = !empty($result['errors']) ? json_encode($result['errors'], JSON_UNESCAPED_UNICODE) : null;

        if ($hasExtendedCols) {
            $warningsLog = !empty($result['warnings']) ? json_encode($result['warnings'], JSON_UNESCAPED_UNICODE) : null;
            $logStmt = $this->db->prepare("
                INSERT INTO migration_logs (db_name, migration_name, sql_hash, statements_total, statements_ok, statements_failed, status, error_log, sql_content, warnings, execution_time_ms, applied_by)
                VALUES (:db, :name, :hash, :total, :ok, :failed, :status, :errors, :sql_content, :warnings, :exec_time, :admin)
            ");
            $logStmt->execute([
                'db'          => $dbName,
                'name'        => $migrationName,
                'hash'        => $sqlHash,
                'total'       => $result['total'],
                'ok'          => $result['ok'],
                'failed'      => $result['failed'],
                'status'      => $status,
                'errors'      => $errorLog,
                'sql_content' => $sql,
                'warnings'    => $warningsLog,
                'exec_time'   => $result['execution_time_ms'] ?? null,
                'admin'       => $adminId,
            ]);
        } else {
            $logStmt = $this->db->prepare("
                INSERT INTO migration_logs (db_name, migration_name, sql_hash, statements_total, statements_ok, statements_failed, status, error_log, applied_by)
                VALUES (:db, :name, :hash, :total, :ok, :failed, :status, :errors, :admin)
            ");
            $logStmt->execute([
                'db'      => $dbName,
                'name'    => $migrationName,
                'hash'    => $sqlHash,
                'total'   => $result['total'],
                'ok'      => $result['ok'],
                'failed'  => $result['failed'],
                'status'  => $status,
                'errors'  => $errorLog,
                'admin'   => $adminId,
            ]);
        }

        // Invalida cache para próxima execução detectar novas colunas
        $this->migrationLogColumns = null;
    }

    // =========================================================================
    // LISTAGEM DE BANCOS TENANT
    // =========================================================================

    public function listTenantDatabases(): array
    {
        $initBase = getenv('AKTI_MASTER_INIT_BASE') ?: 'akti_init_base';
        $excluded = ['akti_master', $initBase];

        $creds = \TenantManager::getMasterConfig();
        $pdo = \Database::connectTo($creds['host'], $creds['port'], $creds['username'], $creds['password']);
        $stmt = $pdo->query("SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME LIKE 'akti\\_%' ORDER BY SCHEMA_NAME");
        $all = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return array_values(array_filter($all, function ($db) use ($excluded) {
            return !in_array($db, $excluded);
        }));
    }

    public function getRegisteredTenants(): array
    {
        $stmt = $this->db->query("
            SELECT id, client_name, subdomain, db_host, db_port, db_name, db_user, db_password, db_charset, is_active
            FROM tenant_clients ORDER BY client_name ASC
        ");
        return $stmt->fetchAll();
    }

    // =========================================================================
    // COMPARAÇÃO DE SCHEMA
    // =========================================================================

    public function getSchemaStructure(string $dbName): array
    {
        $creds = \TenantManager::getMasterConfig();
        $pdo = \Database::connectTo($creds['host'], $creds['port'], $creds['username'], $creds['password'], $dbName);

        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

        $schema = [];
        foreach ($tables as $table) {
            $cols = $pdo->query("SHOW FULL COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
            $schema[$table] = [];
            foreach ($cols as $col) {
                $schema[$table][$col['Field']] = [
                    'type'      => $col['Type'],
                    'null'      => $col['Null'],
                    'key'       => $col['Key'],
                    'default'   => $col['Default'],
                    'extra'     => $col['Extra'],
                    'collation' => $col['Collation'] ?? null,
                ];
            }
        }

        return $schema;
    }

    public function compareSchema(string $targetDb): array
    {
        $initBase = getenv('AKTI_MASTER_INIT_BASE') ?: 'akti_init_base';

        $refSchema    = $this->getSchemaStructure($initBase);
        $targetSchema = $this->getSchemaStructure($targetDb);

        $diff = [
            'missing_tables'  => [],
            'extra_tables'    => [],
            'missing_columns' => [],
            'extra_columns'   => [],
            'type_mismatches' => [],
        ];

        foreach ($refSchema as $table => $columns) {
            if (!isset($targetSchema[$table])) {
                $diff['missing_tables'][] = $table;
                continue;
            }

            foreach ($columns as $col => $info) {
                if (!isset($targetSchema[$table][$col])) {
                    $diff['missing_columns'][] = [
                        'table'  => $table,
                        'column' => $col,
                        'info'   => $info,
                    ];
                } else {
                    $targetInfo = $targetSchema[$table][$col];
                    if (strtolower($info['type']) !== strtolower($targetInfo['type'])) {
                        $diff['type_mismatches'][] = [
                            'table'       => $table,
                            'column'      => $col,
                            'expected'    => $info['type'],
                            'actual'      => $targetInfo['type'],
                        ];
                    }
                }
            }

            foreach ($targetSchema[$table] as $col => $info) {
                if (!isset($refSchema[$table][$col])) {
                    $diff['extra_columns'][] = [
                        'table'  => $table,
                        'column' => $col,
                        'info'   => $info,
                    ];
                }
            }
        }

        foreach ($targetSchema as $table => $columns) {
            if (!isset($refSchema[$table])) {
                $diff['extra_tables'][] = $table;
            }
        }

        return $diff;
    }

    public function compareAllTenants(): array
    {
        $databases = $this->listTenantDatabases();
        $results = [];

        foreach ($databases as $dbName) {
            try {
                $diff = $this->compareSchema($dbName);
                $totalIssues = count($diff['missing_tables'])
                             + count($diff['missing_columns'])
                             + count($diff['type_mismatches']);

                $results[$dbName] = [
                    'status'  => $totalIssues === 0 ? 'ok' : 'divergent',
                    'issues'  => $totalIssues,
                    'diff'    => $diff,
                    'error'   => null,
                ];
            } catch (\Exception $e) {
                $results[$dbName] = [
                    'status' => 'error',
                    'issues' => -1,
                    'diff'   => null,
                    'error'  => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    // =========================================================================
    // EXECUÇÃO DE SQL EM BANCOS TENANT
    // =========================================================================

    public function executeSqlOnDatabase(string $dbName, string $sql): array
    {
        $creds = \TenantManager::getMasterConfig();
        $pdo = \Database::connectTo($creds['host'], $creds['port'], $creds['username'], $creds['password'], $dbName);
        // Migration connections need emulated prepares to support multi-statement
        // execution via exec(). This avoids "unbuffered queries" errors and correctly
        // handles complex SQL (PREPARE/EXECUTE, SET @var, string literals with ;).
        $pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, true);
        $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);

        // Strip SQL comments for display/counting purposes
        $cleanSql = preg_replace('/--[^\r\n]*/', '', $sql);
        $cleanSql = preg_replace('/\/\*[\s\S]*?\*\//', '', $cleanSql);
        $cleanSql = trim($cleanSql);

        if (empty($cleanSql)) {
            return ['total' => 0, 'ok' => 0, 'failed' => 0, 'errors' => [], 'executed' => []];
        }

        $results = [
            'total'    => 1,
            'ok'       => 0,
            'failed'   => 0,
            'errors'   => [],
            'executed' => [],
        ];

        $startTime = hrtime(true);

        try {
            $pdo->exec($cleanSql);
            $results['ok'] = 1;
            $results['executed'][] = [
                'index'  => 1,
                'sql'    => mb_substr($cleanSql, 0, 300),
                'status' => 'ok',
            ];
        } catch (PDOException $e) {
            $results['failed'] = 1;
            $results['errors'][] = [
                'index'   => 1,
                'sql'     => mb_substr($cleanSql, 0, 300),
                'error'   => $e->getMessage(),
            ];
            $results['executed'][] = [
                'index'  => 1,
                'sql'    => mb_substr($cleanSql, 0, 300),
                'status' => 'error',
                'error'  => $e->getMessage(),
            ];
            $this->logMigrationError($dbName, $sql, $results);
        }

        $results['execution_time_ms'] = (int) ((hrtime(true) - $startTime) / 1_000_000);

        // Capture MySQL warnings
        try {
            $warnStmt = $pdo->query('SHOW WARNINGS');
            $warnings = $warnStmt ? $warnStmt->fetchAll(PDO::FETCH_ASSOC) : [];
            $results['warnings'] = $warnings;
        } catch (\Exception $e) {
            $results['warnings'] = [];
        }

        return $results;
    }

    private function logMigrationError(string $dbName, string $sql, array $results): void
    {
        $logDir = $_SERVER['DOCUMENT_ROOT'] . '/storage/logs';
        if (!is_dir($logDir)) {
            return;
        }

        $logFile = $logDir . '/migration_errors_' . date('Y-m-d') . '.log';
        $entry = sprintf(
            "[%s] DB: %s | Statements: OK=%d, Failed=%d, Total=%d\nSQL:\n%s\nErrors:\n%s\n%s\n",
            date('Y-m-d H:i:s'),
            $dbName,
            $results['ok'],
            $results['failed'],
            $results['total'],
            mb_substr($sql, 0, 2000),
            json_encode($results['errors'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            str_repeat('─', 80)
        );

        file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
    }

    public function executeSqlOnAllTenants(string $sql, string $migrationName, int $adminId, ?array $selectedDbs = null): array
    {
        $databases = $selectedDbs ?: $this->listTenantDatabases();
        $sqlHash = hash('sha256', $sql);
        $overall = [];

        foreach ($databases as $dbName) {
            $check = $this->db->prepare("SELECT id FROM migration_logs WHERE db_name = :db AND sql_hash = :hash AND status = 'success'");
            $check->execute(['db' => $dbName, 'hash' => $sqlHash]);
            if ($check->fetch()) {
                $overall[$dbName] = [
                    'status'  => 'skipped',
                    'message' => 'Migração já aplicada anteriormente',
                ];
                continue;
            }

            try {
                $result = $this->executeSqlOnDatabase($dbName, $sql);

                $status = 'success';
                if ($result['failed'] > 0 && $result['ok'] === 0) {
                    $status = 'failed';
                } elseif ($result['failed'] > 0) {
                    $status = 'partial';
                }

                $this->insertMigrationLog($dbName, $migrationName, $sqlHash, $result, $sql, $adminId);

                $overall[$dbName] = [
                    'status'  => $status,
                    'message' => "OK: {$result['ok']}, Falhas: {$result['failed']} de {$result['total']}",
                    'result'  => $result,
                ];
            } catch (\Exception $e) {
                $overall[$dbName] = [
                    'status'  => 'error',
                    'message' => $e->getMessage(),
                ];
            }
        }

        return $overall;
    }

    public function executeSqlOnInitBase(string $sql): array
    {
        $initBase = getenv('AKTI_MASTER_INIT_BASE') ?: 'akti_init_base';
        return $this->executeSqlOnDatabase($initBase, $sql);
    }

    private function parseSqlStatements(string $sql): array
    {
        $sql = preg_replace('/--[^\r\n]*/', '', $sql);
        $sql = preg_replace('/\/\*[\s\S]*?\*\//', '', $sql);

        $raw = explode(';', $sql);
        $statements = [];
        foreach ($raw as $s) {
            $s = trim($s);
            if (!empty($s) && $s !== "\n" && $s !== "\r\n") {
                $statements[] = $s;
            }
        }

        return $statements;
    }

    // =========================================================================
    // HISTÓRICO DE MIGRATIONS
    // =========================================================================

    public function getMigrationHistory(int $limit = 50): array
    {
        $stmt = $this->db->prepare("
            SELECT ml.*, au.name as admin_name
            FROM migration_logs ml
            LEFT JOIN admin_users au ON ml.applied_by = au.id
            ORDER BY ml.applied_at DESC
            LIMIT :lim
        ");
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get paginated migration history with optional filters.
     */
    public function getMigrationHistoryPaginated(int $page = 1, int $perPage = 25, array $filters = []): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 'ml.status = :status';
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['db_name'])) {
            $where[] = 'ml.db_name = :db_name';
            $params['db_name'] = $filters['db_name'];
        }
        if (!empty($filters['migration_name'])) {
            $where[] = 'ml.migration_name LIKE :migration_name';
            $params['migration_name'] = '%' . $filters['migration_name'] . '%';
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'ml.applied_at >= :date_from';
            $params['date_from'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'ml.applied_at <= :date_to';
            $params['date_to'] = $filters['date_to'] . ' 23:59:59';
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Count total
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM migration_logs ml {$whereClause}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $dataStmt = $this->db->prepare("
            SELECT ml.*, au.name as admin_name
            FROM migration_logs ml
            LEFT JOIN admin_users au ON ml.applied_by = au.id
            {$whereClause}
            ORDER BY ml.applied_at DESC
            LIMIT :lim OFFSET :off
        ");
        foreach ($params as $k => $v) {
            $dataStmt->bindValue(':' . $k, $v);
        }
        $dataStmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
        $dataStmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $dataStmt->execute();

        return [
            'data'       => $dataStmt->fetchAll(),
            'total'      => $total,
            'page'       => $page,
            'per_page'   => $perPage,
            'total_pages' => (int) ceil($total / $perPage),
        ];
    }

    /**
     * Get migration history stats summary.
     */
    public function getMigrationStats(): array
    {
        $stmt = $this->db->query("
            SELECT 
                COUNT(*) as total,
                SUM(status = 'success') as success_count,
                SUM(status = 'failed') as failed_count,
                SUM(status = 'partial') as partial_count,
                SUM(status = 'skipped') as skipped_count,
                COUNT(DISTINCT migration_name) as unique_migrations,
                COUNT(DISTINCT db_name) as databases_affected,
                MAX(applied_at) as last_migration_at
            FROM migration_logs
        ");
        return $stmt->fetch() ?: [];
    }

    /**
     * Log a migration execution to migration_logs (for master/manual executions).
     */
    public function logMigrationExecution(string $dbName, string $migrationName, string $sql, array $result, int $adminId): void
    {
        $sqlHash = hash('sha256', $sql);
        $this->insertMigrationLog($dbName, $migrationName, $sqlHash, $result, $sql, $adminId);
    }

    public function getMigrationDetail(int $id)
    {
        $stmt = $this->db->prepare("
            SELECT ml.*, au.name as admin_name
            FROM migration_logs ml
            LEFT JOIN admin_users au ON ml.applied_by = au.id
            WHERE ml.id = :id LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    // =========================================================================
    // GESTÃO DE USUÁRIOS CROSS-TENANT
    // =========================================================================

    private function buildUserSelectQuery(PDO $pdo): string
    {
        $cols = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
        $colSet = array_flip($cols);

        $fields = ['u.id', 'u.name', 'u.email'];
        $fields[] = isset($colSet['phone']) ? 'u.phone' : "NULL as phone";

        if (isset($colSet['is_admin'])) {
            $fields[] = 'u.is_admin';
        } elseif (isset($colSet['role'])) {
            $fields[] = "CASE WHEN u.role = 'admin' THEN 1 ELSE 0 END as is_admin";
        } else {
            $fields[] = '0 as is_admin';
        }

        $fields[] = isset($colSet['is_active']) ? 'u.is_active' : '1 as is_active';
        $fields[] = 'u.created_at';

        $hasGroupId = isset($colSet['group_id']);
        $ugCheck = $pdo->query("SHOW TABLES LIKE 'user_groups'")->fetch();
        $ugNameCol = null;
        if ($ugCheck) {
            $ugCols = $pdo->query("SHOW COLUMNS FROM user_groups")->fetchAll(PDO::FETCH_COLUMN);
            if (in_array('group_name', $ugCols)) $ugNameCol = 'group_name';
            elseif (in_array('name', $ugCols)) $ugNameCol = 'name';
        }

        if ($hasGroupId && $ugNameCol) {
            $fields[] = "ug.{$ugNameCol} as group_name";
            $join = "LEFT JOIN user_groups ug ON u.group_id = ug.id";
        } else {
            $fields[] = "NULL as group_name";
            $join = "";
        }

        return "SELECT " . implode(', ', $fields) . " FROM users u {$join} ORDER BY u.name ASC";
    }

    public function listAllTenantUsers(): array
    {
        $tenants = $this->getRegisteredTenants();
        $creds = \TenantManager::getMasterConfig();
        $allUsers = [];

        foreach ($tenants as $tenant) {
            try {
                $pdo = \Database::connectTo(
                    $tenant['db_host'],
                    $tenant['db_port'],
                    $creds['username'], $creds['password'],
                    $tenant['db_name'],
                    $tenant['db_charset'] ?: 'utf8mb4'
                );

                $check = $pdo->query("SHOW TABLES LIKE 'users'")->fetch();
                if (!$check) continue;

                $query = $this->buildUserSelectQuery($pdo);
                $stmt = $pdo->query($query);
                $users = $stmt->fetchAll();

                foreach ($users as $user) {
                    $user['db_name']     = $tenant['db_name'];
                    $user['client_name'] = $tenant['client_name'];
                    $user['client_id']   = $tenant['id'];
                    $user['subdomain']   = $tenant['subdomain'];
                    $user['tenant_active'] = $tenant['is_active'];
                    $allUsers[] = $user;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return $allUsers;
    }

    public function listUsersFromDatabase(string $dbName): array
    {
        $creds = \TenantManager::getMasterConfig();
        $pdo = \Database::connectTo($creds['host'], $creds['port'], $creds['username'], $creds['password'], $dbName);

        $check = $pdo->query("SHOW TABLES LIKE 'users'")->fetch();
        if (!$check) return [];

        $query = $this->buildUserSelectQuery($pdo);
        return $pdo->query($query)->fetchAll();
    }

    public function toggleTenantUser(string $dbName, int $userId): bool
    {
        $creds = \TenantManager::getMasterConfig();
        $pdo = \Database::connectTo($creds['host'], $creds['port'], $creds['username'], $creds['password'], $dbName);

        $cols = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_active'")->fetch();
        if ($cols) {
            $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id = :id")->execute(['id' => $userId]);
        } else {
            throw new \Exception("A tabela users deste banco não possui a coluna 'is_active'.");
        }
        return true;
    }

    public function getTableCount(string $dbName): int
    {
        $creds = \TenantManager::getMasterConfig();
        $pdo = \Database::connectTo($creds['host'], $creds['port'], $creds['username'], $creds['password']);
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = :db");
        $stmt->execute(['db' => $dbName]);
        return (int) $stmt->fetchColumn();
    }
}
