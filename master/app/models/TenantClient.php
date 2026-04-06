<?php
/**
 * Model: TenantClient
 * Gerencia os clientes (tenants) do sistema
 */

class TenantClient
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function readAll()
    {
        $stmt = $this->db->query("
            SELECT tc.*, p.plan_name, p.price as plan_price
            FROM tenant_clients tc 
            LEFT JOIN plans p ON tc.plan_id = p.id 
            ORDER BY tc.client_name ASC
        ");
        return $stmt->fetchAll();
    }

    public function readOne($id)
    {
        $stmt = $this->db->prepare("
            SELECT tc.*, p.plan_name 
            FROM tenant_clients tc 
            LEFT JOIN plans p ON tc.plan_id = p.id 
            WHERE tc.id = :id LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function findBySubdomain($subdomain)
    {
        $stmt = $this->db->prepare("SELECT * FROM tenant_clients WHERE subdomain = :subdomain LIMIT 1");
        $stmt->execute(['subdomain' => $subdomain]);
        return $stmt->fetch();
    }

    public function findByDbName($dbName)
    {
        $stmt = $this->db->prepare("SELECT * FROM tenant_clients WHERE db_name = :db_name LIMIT 1");
        $stmt->execute(['db_name' => $dbName]);
        return $stmt->fetch();
    }

    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO tenant_clients (plan_id, client_name, subdomain, db_host, db_port, db_name, db_user, db_password, db_charset, max_users, max_products, max_warehouses, max_price_tables, max_sectors, is_active) 
            VALUES (:plan_id, :client_name, :subdomain, :db_host, :db_port, :db_name, :db_user, :db_password, :db_charset, :max_users, :max_products, :max_warehouses, :max_price_tables, :max_sectors, :is_active)
        ");
        $stmt->execute([
            'plan_id'         => $data['plan_id'] ?: null,
            'client_name'     => $data['client_name'],
            'subdomain'       => $data['subdomain'],
            'db_host'         => $data['db_host'] ?: 'localhost',
            'db_port'         => $data['db_port'] ?: 3306,
            'db_name'         => $data['db_name'],
            'db_user'         => $data['db_user'],
            'db_password'     => $data['db_password'],
            'db_charset'      => $data['db_charset'] ?: 'utf8mb4',
            'max_users'       => $data['max_users'] ?: null,
            'max_products'    => $data['max_products'] ?: null,
            'max_warehouses'  => $data['max_warehouses'] ?: null,
            'max_price_tables'=> $data['max_price_tables'] ?: null,
            'max_sectors'     => $data['max_sectors'] ?: null,
            'is_active'       => isset($data['is_active']) ? 1 : 0,
        ]);
        return $this->db->lastInsertId();
    }

    public function update($id, $data)
    {
        $stmt = $this->db->prepare("
            UPDATE tenant_clients SET 
                plan_id = :plan_id,
                client_name = :client_name,
                subdomain = :subdomain,
                db_host = :db_host,
                db_port = :db_port,
                db_name = :db_name,
                db_user = :db_user,
                db_password = :db_password,
                db_charset = :db_charset,
                max_users = :max_users,
                max_products = :max_products,
                max_warehouses = :max_warehouses,
                max_price_tables = :max_price_tables,
                max_sectors = :max_sectors,
                is_active = :is_active
            WHERE id = :id
        ");
        $stmt->execute([
            'id'              => $id,
            'plan_id'         => $data['plan_id'] ?: null,
            'client_name'     => $data['client_name'],
            'subdomain'       => $data['subdomain'],
            'db_host'         => $data['db_host'] ?: 'localhost',
            'db_port'         => $data['db_port'] ?: 3306,
            'db_name'         => $data['db_name'],
            'db_user'         => $data['db_user'],
            'db_password'     => $data['db_password'],
            'db_charset'      => $data['db_charset'] ?: 'utf8mb4',
            'max_users'       => $data['max_users'] ?: null,
            'max_products'    => $data['max_products'] ?: null,
            'max_warehouses'  => $data['max_warehouses'] ?: null,
            'max_price_tables'=> $data['max_price_tables'] ?: null,
            'max_sectors'     => $data['max_sectors'] ?: null,
            'is_active'       => isset($data['is_active']) ? 1 : 0,
        ]);
    }

    public function updateLimitsFromPlan($clientId, $plan)
    {
        $stmt = $this->db->prepare("
            UPDATE tenant_clients SET 
                plan_id = :plan_id,
                max_users = :max_users,
                max_products = :max_products,
                max_warehouses = :max_warehouses,
                max_price_tables = :max_price_tables,
                max_sectors = :max_sectors
            WHERE id = :id
        ");
        $stmt->execute([
            'id'              => $clientId,
            'plan_id'         => $plan['id'],
            'max_users'       => $plan['max_users'],
            'max_products'    => $plan['max_products'],
            'max_warehouses'  => $plan['max_warehouses'],
            'max_price_tables'=> $plan['max_price_tables'],
            'max_sectors'     => $plan['max_sectors'],
        ]);
    }

    public function toggleActive($id)
    {
        $stmt = $this->db->prepare("UPDATE tenant_clients SET is_active = NOT is_active WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM tenant_clients WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }

    public function getStats()
    {
        $stats = [];
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM tenant_clients");
        $stats['total_clients'] = $stmt->fetch()['total'];

        $stmt = $this->db->query("SELECT COUNT(*) as total FROM tenant_clients WHERE is_active = 1");
        $stats['active_clients'] = $stmt->fetch()['total'];

        $stmt = $this->db->query("SELECT COUNT(*) as total FROM tenant_clients WHERE is_active = 0");
        $stats['inactive_clients'] = $stmt->fetch()['total'];

        $stmt = $this->db->query("SELECT COUNT(*) as total FROM plans WHERE is_active = 1");
        $stats['total_plans'] = $stmt->fetch()['total'];

        $stmt = $this->db->query("
            SELECT p.plan_name, COUNT(tc.id) as total 
            FROM plans p 
            LEFT JOIN tenant_clients tc ON tc.plan_id = p.id 
            WHERE p.is_active = 1 
            GROUP BY p.id, p.plan_name 
            ORDER BY total DESC
        ");
        $stats['clients_by_plan'] = $stmt->fetchAll();

        $stmt = $this->db->query("SELECT * FROM tenant_clients ORDER BY created_at DESC LIMIT 5");
        $stats['recent_clients'] = $stmt->fetchAll();

        return $stats;
    }

    /**
     * Provisiona o banco de dados de um novo cliente clonando o banco base (akti_init_base)
     * Usa mysqldump/mysql para copiar estrutura + dados de forma confiável.
     */
    public function provisionDatabase($dbHost, $dbPort, $dbName, $dbUser, $dbPassword, $dbCharset = 'utf8mb4')
    {
        try {
            // Conectar sem banco específico usando credenciais master
            $rootPdo = Database::connectTo($dbHost, $dbPort, DB_USER, DB_PASS);
            
            // 1. Criar banco de dados destino
            $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET {$dbCharset} COLLATE {$dbCharset}_unicode_ci");
            
            // 2. Criar usuário MySQL (se diferente de root e não vazio)
            if (!empty($dbUser) && $dbUser !== 'root') {
                try {
                    $rootPdo->exec("CREATE USER IF NOT EXISTS '{$dbUser}'@'{$dbHost}' IDENTIFIED BY " . $rootPdo->quote($dbPassword));
                    $rootPdo->exec("GRANT ALL PRIVILEGES ON `{$dbName}`.* TO '{$dbUser}'@'{$dbHost}'");
                    $rootPdo->exec("FLUSH PRIVILEGES");
                } catch (PDOException $e) {
                    try {
                        $rootPdo->exec("GRANT ALL PRIVILEGES ON `{$dbName}`.* TO '{$dbUser}'@'{$dbHost}'");
                        $rootPdo->exec("FLUSH PRIVILEGES");
                    } catch (PDOException $e2) {
                        // Seguir com credenciais master
                    }
                }
            }
            
            // 3. Clonar banco base via mysqldump | mysql
            $initBase  = defined('CLIENT_DB_INIT_BASE') ? CLIENT_DB_INIT_BASE : 'akti_init_base';
            $mysqldump = defined('MYSQLDUMP_PATH') ? MYSQLDUMP_PATH : 'mysqldump';
            $mysqlBin  = defined('MYSQL_PATH') ? MYSQL_PATH : 'mysql';
            $isWindows = (PHP_OS_FAMILY === 'Windows');

            // Verificar se o banco base existe
            $checkBase = $rootPdo->query("SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = " . $rootPdo->quote($initBase));
            if (!$checkBase->fetch()) {
                return ['success' => false, 'message' => "Banco base '{$initBase}' não encontrado no servidor. Crie-o antes de provisionar clientes."];
            }

            // Verificar se os binários estão acessíveis
            if ($mysqldump !== 'mysqldump' && !file_exists($mysqldump)) {
                return ['success' => false, 'message' => "Binário mysqldump não encontrado em: {$mysqldump}"];
            }
            if ($mysqlBin !== 'mysql' && !file_exists($mysqlBin)) {
                return ['success' => false, 'message' => "Binário mysql não encontrado em: {$mysqlBin}"];
            }

            // Gravar credenciais em arquivo temporário .cnf para segurança (evita expor senha no CLI)
            $tmpCnf = tempnam(sys_get_temp_dir(), 'akti_mysql_');
            // Em Linux o arquivo .cnf precisa de permissão restrita para o MySQL aceitar sem warning
            $cnfContent = "[client]\nuser=" . DB_USER . "\npassword=" . DB_PASS . "\nhost=" . $dbHost . "\nport=" . $dbPort . "\n";
            file_put_contents($tmpCnf, $cnfContent);
            if (!$isWindows) {
                chmod($tmpCnf, 0600);
            }

            // Normalizar caminho do .cnf conforme SO
            $cnfPath = $isWindows ? str_replace('\\', '/', $tmpCnf) : $tmpCnf;

            // Montar comando: mysqldump banco_base | mysql banco_destino
            if ($isWindows) {
                // Windows: usar cmd /c para executar o pipe
                $dumpCmd = '"' . $mysqldump . '"'
                         . ' --defaults-extra-file="' . $cnfPath . '"'
                         . ' --single-transaction --routines --triggers --events'
                         . ' ' . escapeshellarg($initBase);

                $importCmd = '"' . $mysqlBin . '"'
                           . ' --defaults-extra-file="' . $cnfPath . '"'
                           . ' ' . escapeshellarg($dbName);

                $fullCmd = 'cmd /c "' . $dumpCmd . ' | ' . $importCmd . '" 2>&1';
            } else {
                // Linux/macOS: pipe nativo no shell
                $dumpCmd = escapeshellarg($mysqldump)
                         . ' --defaults-extra-file=' . escapeshellarg($cnfPath)
                         . ' --single-transaction --routines --triggers --events'
                         . ' ' . escapeshellarg($initBase);

                $importCmd = escapeshellarg($mysqlBin)
                           . ' --defaults-extra-file=' . escapeshellarg($cnfPath)
                           . ' ' . escapeshellarg($dbName);

                $fullCmd = $dumpCmd . ' | ' . $importCmd . ' 2>&1';
            }

            $output = [];
            $exitCode = 0;
            exec($fullCmd, $output, $exitCode);

            // Remover arquivo temporário de credenciais
            @unlink($tmpCnf);

            if ($exitCode !== 0) {
                $errorMsg = implode("\n", $output);
                return ['success' => false, 'message' => "Erro ao clonar banco base (exit code {$exitCode}): {$errorMsg}"];
            }

            // 4. Contar tabelas para feedback
            $clientPdo = new PDO(
                "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset={$dbCharset}",
                DB_USER, DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
            );
            $totalTables = $clientPdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = " . $clientPdo->quote($dbName))->fetchColumn();

            $msg = "Banco '{$dbName}' criado com sucesso a partir de '{$initBase}' ({$totalTables} tabelas clonadas).";
            
            return ['success' => true, 'message' => $msg];
        } catch (PDOException $e) {
            @unlink($tmpCnf ?? '');
            return ['success' => false, 'message' => 'Erro ao provisionar banco: ' . $e->getMessage()];
        } catch (Exception $e) {
            @unlink($tmpCnf ?? '');
            return ['success' => false, 'message' => 'Erro ao provisionar banco: ' . $e->getMessage()];
        }
    }

    /**
     * Remove o banco de dados e o usuário MySQL de um cliente
     */
    public function dropDatabase($dbHost, $dbPort, $dbName, $dbUser)
    {
        try {
            $rootPdo = Database::connectTo($dbHost, $dbPort, DB_USER, DB_PASS);

            // 1. Dropar o banco de dados
            $rootPdo->exec("DROP DATABASE IF EXISTS `{$dbName}`");

            // 2. Remover o usuário MySQL (se não for root)
            if ($dbUser && $dbUser !== 'root' && $dbUser !== DB_USER) {
                try {
                    $rootPdo->exec("DROP USER IF EXISTS '{$dbUser}'@'{$dbHost}'");
                    $rootPdo->exec("FLUSH PRIVILEGES");
                } catch (PDOException $e) {
                    // Ignorar erro se o usuário não existir
                }
            }

            return ['success' => true, 'message' => "Banco de dados '{$dbName}' e usuário '{$dbUser}' removidos com sucesso."];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erro ao remover banco de dados: ' . $e->getMessage()];
        }
    }

    /**
     * Cria um usuário diretamente no banco de dados do cliente (tenant)
     * Tabela users: id, name, email, password, role (ENUM 'admin','funcionario'), group_id, created_at
     */
    public function createTenantUser($dbHost, $dbPort, $dbName, $dbUser, $dbPassword, $dbCharset, $userData)
    {
        try {
            $clientPdo = Database::connectTo($dbHost, $dbPort, $dbUser, $dbPassword, $dbName, $dbCharset);

            // Verificar se o e-mail já existe no banco do cliente
            $checkStmt = $clientPdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
            $checkStmt->execute(['email' => $userData['email']]);
            if ($checkStmt->fetch()) {
                return ['success' => false, 'message' => "O e-mail '{$userData['email']}' já existe no banco do cliente."];
            }

            // Buscar grupo admin se existir (coluna 'name' na tabela user_groups)
            $groupId = null;
            if (!empty($userData['is_admin'])) {
                $groupStmt = $clientPdo->query("SELECT id FROM user_groups WHERE name = 'Administradores' LIMIT 1");
                $group = $groupStmt->fetch();
                $groupId = $group ? $group['id'] : null;
            }

            // Definir role baseado no is_admin
            $role = !empty($userData['is_admin']) ? 'admin' : 'funcionario';

            // Hash da senha
            $hashedPassword = password_hash($userData['password'], PASSWORD_BCRYPT);

            $stmt = $clientPdo->prepare("
                INSERT INTO users (name, email, password, role, group_id) 
                VALUES (:name, :email, :password, :role, :group_id)
            ");
            $stmt->execute([
                'name'     => $userData['name'],
                'email'    => $userData['email'],
                'password' => $hashedPassword,
                'role'     => $role,
                'group_id' => $groupId,
            ]);

            return ['success' => true, 'message' => "Usuário '{$userData['email']}' criado com sucesso no banco '{$dbName}'."];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erro ao criar usuário no banco do cliente: ' . $e->getMessage()];
        }
    }
}
