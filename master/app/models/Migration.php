<?php
/**
 * Model: Migration
 * Gerencia migrações de banco de dados cross-tenant e consultas de usuários de tenants
 */

class Migration
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // =========================================================================
    // LISTAGEM DE BANCOS TENANT
    // =========================================================================

    /**
     * Lista todos os bancos que começam com akti_ (exceto akti_master e akti_init_base)
     */
    public function listTenantDatabases()
    {
        $initBase = defined('CLIENT_DB_INIT_BASE') ? CLIENT_DB_INIT_BASE : 'akti_init_base';
        $excluded = ['akti_master', $initBase];

        $pdo = Database::connectTo(DB_HOST, DB_PORT, DB_USER, DB_PASS);
        $stmt = $pdo->query("SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME LIKE 'akti\\_%' ORDER BY SCHEMA_NAME");
        $all = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return array_values(array_filter($all, function ($db) use ($excluded) {
            return !in_array($db, $excluded);
        }));
    }

    /**
     * Retorna informações dos bancos tenant registrados em tenant_clients
     */
    public function getRegisteredTenants()
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

    /**
     * Obtém a estrutura de tabelas de um banco de dados
     * Retorna [tabela => [coluna => info, ...], ...]
     */
    public function getSchemaStructure($dbName)
    {
        $pdo = Database::connectTo(DB_HOST, DB_PORT, DB_USER, DB_PASS, $dbName);

        // Tabelas
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

    /**
     * Compara a estrutura de um banco tenant com o banco de referência
     * Retorna as diferenças encontradas
     */
    public function compareSchema($targetDb)
    {
        $initBase = defined('CLIENT_DB_INIT_BASE') ? CLIENT_DB_INIT_BASE : 'akti_init_base';

        $refSchema    = $this->getSchemaStructure($initBase);
        $targetSchema = $this->getSchemaStructure($targetDb);

        $diff = [
            'missing_tables'  => [],   // Tabelas que existem na ref mas não no target
            'extra_tables'    => [],   // Tabelas que existem no target mas não na ref
            'missing_columns' => [],   // Colunas faltando em tabelas existentes
            'extra_columns'   => [],   // Colunas extras em tabelas existentes
            'type_mismatches' => [],   // Colunas com tipo diferente
        ];

        // Tabelas faltando no target
        foreach ($refSchema as $table => $columns) {
            if (!isset($targetSchema[$table])) {
                $diff['missing_tables'][] = $table;
                continue;
            }

            // Colunas faltando
            foreach ($columns as $col => $info) {
                if (!isset($targetSchema[$table][$col])) {
                    $diff['missing_columns'][] = [
                        'table'  => $table,
                        'column' => $col,
                        'info'   => $info,
                    ];
                } else {
                    // Verificar tipo diferente
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

            // Colunas extras no target
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

        // Tabelas extras no target
        foreach ($targetSchema as $table => $columns) {
            if (!isset($refSchema[$table])) {
                $diff['extra_tables'][] = $table;
            }
        }

        return $diff;
    }

    /**
     * Compara todos os bancos tenant com a referência e retorna resumo
     */
    public function compareAllTenants()
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
            } catch (Exception $e) {
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

    /**
     * Executa um SQL em um banco específico, statement por statement
     * Retorna resultado detalhado
     */
    public function executeSqlOnDatabase($dbName, $sql)
    {
        $pdo = Database::connectTo(DB_HOST, DB_PORT, DB_USER, DB_PASS, $dbName);
        $statements = $this->parseSqlStatements($sql);

        $results = [
            'total'    => count($statements),
            'ok'       => 0,
            'failed'   => 0,
            'errors'   => [],
            'executed' => [],
        ];

        foreach ($statements as $i => $stmt) {
            $stmt = trim($stmt);
            if (empty($stmt)) continue;

            try {
                $pdo->exec($stmt);
                $results['ok']++;
                $results['executed'][] = [
                    'index'  => $i + 1,
                    'sql'    => mb_substr($stmt, 0, 200),
                    'status' => 'ok',
                ];
            } catch (PDOException $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'index'   => $i + 1,
                    'sql'     => mb_substr($stmt, 0, 200),
                    'error'   => $e->getMessage(),
                ];
                $results['executed'][] = [
                    'index'  => $i + 1,
                    'sql'    => mb_substr($stmt, 0, 200),
                    'status' => 'error',
                    'error'  => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Executa SQL em todos os bancos tenant
     */
    public function executeSqlOnAllTenants($sql, $migrationName, $adminId, $selectedDbs = null)
    {
        $databases = $selectedDbs ?: $this->listTenantDatabases();
        $sqlHash = hash('sha256', $sql);
        $overall = [];

        foreach ($databases as $dbName) {
            // Verificar se já foi aplicada
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

                // Registrar no log
                $errorLog = !empty($result['errors']) ? json_encode($result['errors'], JSON_UNESCAPED_UNICODE) : null;
                $logStmt = $this->db->prepare("
                    INSERT INTO migration_logs (db_name, migration_name, sql_hash, statements_total, statements_ok, statements_failed, status, error_log, applied_by)
                    VALUES (:db, :name, :hash, :total, :ok, :failed, :status, :errors, :admin)
                ");
                $logStmt->execute([
                    'db'     => $dbName,
                    'name'   => $migrationName,
                    'hash'   => $sqlHash,
                    'total'  => $result['total'],
                    'ok'     => $result['ok'],
                    'failed' => $result['failed'],
                    'status' => $status,
                    'errors' => $errorLog,
                    'admin'  => $adminId,
                ]);

                $overall[$dbName] = [
                    'status'  => $status,
                    'message' => "OK: {$result['ok']}, Falhas: {$result['failed']} de {$result['total']}",
                    'result'  => $result,
                ];
            } catch (Exception $e) {
                $overall[$dbName] = [
                    'status'  => 'error',
                    'message' => $e->getMessage(),
                ];
            }
        }

        return $overall;
    }

    /**
     * Também aplica SQL no banco de referência (akti_init_base) para manter sincronizado
     */
    public function executeSqlOnInitBase($sql)
    {
        $initBase = defined('CLIENT_DB_INIT_BASE') ? CLIENT_DB_INIT_BASE : 'akti_init_base';
        return $this->executeSqlOnDatabase($initBase, $sql);
    }

    /**
     * Parseia SQL em statements individuais respeitando delimiters
     */
    private function parseSqlStatements($sql)
    {
        // Remover comentários de linha única (--)
        $sql = preg_replace('/--[^\r\n]*/', '', $sql);
        // Remover blocos de comentário /* ... */
        $sql = preg_replace('/\/\*[\s\S]*?\*\//', '', $sql);

        // Separar por ;
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

    /**
     * Lista o histórico de migrações aplicadas
     */
    public function getMigrationHistory($limit = 50)
    {
        $stmt = $this->db->prepare("
            SELECT ml.*, au.name as admin_name
            FROM migration_logs ml
            LEFT JOIN admin_users au ON ml.applied_by = au.id
            ORDER BY ml.applied_at DESC
            LIMIT :lim
        ");
        $stmt->bindValue(':lim', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Detalhes de uma migração específica
     */
    public function getMigrationDetail($id)
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

    /**
     * Monta a query de seleção de usuários dinamicamente conforme as colunas existentes
     */
    private function buildUserSelectQuery($pdo)
    {
        $cols = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
        $colSet = array_flip($cols);

        $fields = ['u.id', 'u.name', 'u.email'];

        // phone pode ou não existir
        $fields[] = isset($colSet['phone']) ? 'u.phone' : "NULL as phone";

        // is_admin pode ser coluna própria ou derivada de role
        if (isset($colSet['is_admin'])) {
            $fields[] = 'u.is_admin';
        } elseif (isset($colSet['role'])) {
            $fields[] = "CASE WHEN u.role = 'admin' THEN 1 ELSE 0 END as is_admin";
        } else {
            $fields[] = '0 as is_admin';
        }

        // is_active pode ou não existir
        $fields[] = isset($colSet['is_active']) ? 'u.is_active' : '1 as is_active';

        $fields[] = 'u.created_at';

        // group_name via join (verificar se group_id existe)
        $hasGroupId = isset($colSet['group_id']);

        // Verificar se user_groups existe e qual coluna de nome usa
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

    /**
     * Lista todos os usuários de todos os bancos tenant
     */
    public function listAllTenantUsers()
    {
        $tenants = $this->getRegisteredTenants();
        $allUsers = [];

        foreach ($tenants as $tenant) {
            try {
                $pdo = Database::connectTo(
                    $tenant['db_host'],
                    $tenant['db_port'],
                    DB_USER, DB_PASS,
                    $tenant['db_name'],
                    $tenant['db_charset'] ?: 'utf8mb4'
                );

                // Verificar se a tabela users existe
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
            } catch (Exception $e) {
                // Banco pode estar inacessível — pular
                continue;
            }
        }

        return $allUsers;
    }

    /**
     * Lista usuários de um banco específico
     */
    public function listUsersFromDatabase($dbName)
    {
        $pdo = Database::connectTo(DB_HOST, DB_PORT, DB_USER, DB_PASS, $dbName);

        $check = $pdo->query("SHOW TABLES LIKE 'users'")->fetch();
        if (!$check) return [];

        $query = $this->buildUserSelectQuery($pdo);
        return $pdo->query($query)->fetchAll();
    }

    /**
     * Alterna status ativo/inativo de um usuário em um banco tenant
     */
    public function toggleTenantUser($dbName, $userId)
    {
        $pdo = Database::connectTo(DB_HOST, DB_PORT, DB_USER, DB_PASS, $dbName);
        
        // Verificar se a coluna is_active existe
        $cols = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_active'")->fetch();
        if ($cols) {
            $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id = :id")->execute(['id' => $userId]);
        } else {
            // Sem coluna is_active — não é possível ativar/desativar
            throw new Exception("A tabela users deste banco não possui a coluna 'is_active'.");
        }
        return true;
    }

    /**
     * Remove um usuário de um banco tenant
     */
    public function deleteTenantUser($dbName, $userId)
    {
        $pdo = Database::connectTo(DB_HOST, DB_PORT, DB_USER, DB_PASS, $dbName);
        $pdo->prepare("DELETE FROM users WHERE id = :id")->execute(['id' => $userId]);
        return true;
    }

    /**
     * Retorna contagem de tabelas de um banco
     */
    public function getTableCount($dbName)
    {
        $pdo = Database::connectTo(DB_HOST, DB_PORT, DB_USER, DB_PASS, $dbName);
        return $pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = " . $pdo->quote($dbName))->fetchColumn();
    }
}
