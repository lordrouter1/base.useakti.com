<?php

namespace Akti\Models\Master;

use PDO;
use PDOException;

/**
 * Model de clientes/tenants do sistema multi-tenant.
 */
class TenantClient
{
    private $db;

    /**
     * Construtor da classe TenantClient.
     *
     * @param PDO $db Conexão PDO com o banco de dados
     */
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Retorna todos os registros.
     * @return array
     */
    public function readAll(): array
    {
        $stmt = $this->db->query("
            SELECT tc.*, p.plan_name, p.price as plan_price
            FROM tenant_clients tc
            LEFT JOIN plans p ON tc.plan_id = p.id
            ORDER BY tc.client_name ASC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Retorna um registro pelo ID.
     *
     * @param int $id ID do registro
     * @return array
     */
    public function readOne(int $id): array|false
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

    /**
     * Busca registro(s) com critérios específicos.
     *
     * @param string $subdomain Subdomain
     * @return array
     */
    public function findBySubdomain(string $subdomain): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM tenant_clients WHERE subdomain = :subdomain LIMIT 1");
        $stmt->execute(['subdomain' => $subdomain]);
        return $stmt->fetch();
    }

    /**
     * Busca registro(s) com critérios específicos.
     *
     * @param string $dbName Db name
     * @return array
     */
    public function findByDbName(string $dbName): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM tenant_clients WHERE db_name = :db_name LIMIT 1");
        $stmt->execute(['db_name' => $dbName]);
        return $stmt->fetch();
    }

    /**
     * Cria um novo registro no banco de dados.
     *
     * @param array $data Dados para processamento
     * @return string
     */
    public function create(array $data): string
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

    /**
     * Atualiza um registro existente.
     *
     * @param int $id ID do registro
     * @param array $data Dados para processamento
     * @return void
     */
    public function update(int $id, array $data): void
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

    /**
     * Update limits from plan.
     *
     * @param int $clientId Client id
     * @param array $plan Plan
     * @return void
     */
    public function updateLimitsFromPlan(int $clientId, array $plan): void
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

    /**
     * Alterna estado de propriedade.
     *
     * @param int $id ID do registro
     * @return void
     */
    public function toggleActive(int $id): void
    {
        $stmt = $this->db->prepare("UPDATE tenant_clients SET is_active = NOT is_active WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }

    /**
     * Remove um registro pelo ID.
     *
     * @param int $id ID do registro
     * @return void
     */
    public function delete(int $id): void
    {
        $stmt = $this->db->prepare("DELETE FROM tenant_clients WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }

    /**
     * Obtém dados específicos.
     * @return array
     */
    public function getStats(): array
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
     * Provision database.
     *
     * @param string $dbHost Db host
     * @param int $dbPort Db port
     * @param string $dbName Db name
     * @param string $dbUser Db user
     * @param string $dbPassword Db password
     * @param string $dbCharset Db charset
     * @return array
     */
    public function provisionDatabase(string $dbHost, int $dbPort, string $dbName, string $dbUser, string $dbPassword, string $dbCharset = 'utf8mb4'): array
    {
        // Validar identificadores SQL para prevenir injeção
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbName)) {
            return ['success' => false, 'message' => 'Nome do banco contém caracteres inválidos.'];
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbCharset)) {
            return ['success' => false, 'message' => 'Charset contém caracteres inválidos.'];
        }

        $masterConfig = \Database::getMasterCredentials();
        $masterUser = $masterConfig['username'];
        $masterPass = $masterConfig['password'];

        try {
            $rootPdo = self::connectTo($dbHost, $dbPort, $masterUser, $masterPass);

            $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET {$dbCharset} COLLATE {$dbCharset}_unicode_ci");

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

            $initBase  = getenv('AKTI_MASTER_INIT_BASE') ?: 'akti_init_base';
            $mysqldump = self::findMysqlBinary('mysqldump');
            $mysqlBin  = self::findMysqlBinary('mysql');
            $isWindows = (PHP_OS_FAMILY === 'Windows');

            $checkBase = $rootPdo->query("SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = " . $rootPdo->quote($initBase));
            if (!$checkBase->fetch()) {
                return ['success' => false, 'message' => "Banco base '{$initBase}' não encontrado no servidor."];
            }

            $tmpCnf = tempnam(sys_get_temp_dir(), 'akti_mysql_');
            $cnfContent = "[client]\nuser=" . $masterUser . "\npassword=" . $masterPass . "\nhost=" . $dbHost . "\nport=" . $dbPort . "\n";
            file_put_contents($tmpCnf, $cnfContent);
            if (!$isWindows) {
                chmod($tmpCnf, 0600);
            }

            $cnfPath = $isWindows ? str_replace('\\', '/', $tmpCnf) : $tmpCnf;

            if ($isWindows) {
                $dumpCmd = '"' . $mysqldump . '"'
                         . ' --defaults-extra-file="' . $cnfPath . '"'
                         . ' --single-transaction --routines --triggers --events'
                         . ' ' . escapeshellarg($initBase);

                $importCmd = '"' . $mysqlBin . '"'
                           . ' --defaults-extra-file="' . $cnfPath . '"'
                           . ' ' . escapeshellarg($dbName);

                $fullCmd = 'cmd /c "' . $dumpCmd . ' | ' . $importCmd . '" 2>&1';
            } else {
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

            @unlink($tmpCnf);

            if ($exitCode !== 0) {
                $errorMsg = implode("\n", $output);
                return ['success' => false, 'message' => "Erro ao clonar banco base (exit code {$exitCode}): {$errorMsg}"];
            }

            $clientPdo = new PDO(
                "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset={$dbCharset}",
                $masterUser, $masterPass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
            );
            $totalTables = $clientPdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = " . $clientPdo->quote($dbName))->fetchColumn();

            return ['success' => true, 'message' => "Banco '{$dbName}' criado com sucesso a partir de '{$initBase}' ({$totalTables} tabelas clonadas)."];
        } catch (PDOException $e) {
            @unlink($tmpCnf ?? '');
            return ['success' => false, 'message' => 'Erro ao provisionar banco: ' . $e->getMessage()];
        } catch (\Exception $e) {
            @unlink($tmpCnf ?? '');
            return ['success' => false, 'message' => 'Erro ao provisionar banco: ' . $e->getMessage()];
        }
    }

 /**
  * Drop database.
  *
  * @param string $dbHost Db host
  * @param int $dbPort Db port
  * @param string $dbName Db name
  * @param string $dbUser Db user
  * @return array
  */
    public function dropDatabase(string $dbHost, int $dbPort, string $dbName, string $dbUser): array
    {
        $masterConfig = \Database::getMasterCredentials();
        $masterUser = $masterConfig['username'];
        $masterPass = $masterConfig['password'];

        try {
            $rootPdo = self::connectTo($dbHost, $dbPort, $masterUser, $masterPass);
            $rootPdo->exec("DROP DATABASE IF EXISTS `{$dbName}`");

            if ($dbUser && $dbUser !== 'root' && $dbUser !== $masterUser) {
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
  * Create tenant user.
  *
  * @param string $dbHost Db host
  * @param int $dbPort Db port
  * @param string $dbName Db name
  * @param string $dbUser Db user
  * @param string $dbPassword Db password
  * @param string $dbCharset Db charset
  * @param array $userData User data
  * @return array
  */
    public function createTenantUser(string $dbHost, int $dbPort, string $dbName, string $dbUser, string $dbPassword, string $dbCharset, array $userData): array
    {
        try {
            $clientPdo = self::connectTo($dbHost, $dbPort, $dbUser, $dbPassword, $dbName, $dbCharset);

            $checkStmt = $clientPdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
            $checkStmt->execute(['email' => $userData['email']]);
            if ($checkStmt->fetch()) {
                return ['success' => false, 'message' => "O e-mail '{$userData['email']}' já existe no banco do cliente."];
            }

            $groupId = null;
            if (!empty($userData['is_admin'])) {
                $groupStmt = $clientPdo->query("SELECT id FROM user_groups WHERE name = 'Administradores' LIMIT 1");
                $group = $groupStmt->fetch();
                $groupId = $group ? $group['id'] : null;
            }

            $role = !empty($userData['is_admin']) ? 'admin' : 'funcionario';
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

 /**
  * Connect to.
  *
  * @param string $host Host
  * @param int $port Port
  * @param string $user User
  * @param string $pass Pass
  * @param string|null $dbname Dbname
  * @param string $charset Charset
  * @return PDO
  */
    public static function connectTo(string $host, int $port, string $user, string $pass, ?string $dbname = null, string $charset = 'utf8mb4'): PDO
    {
        $dsn = "mysql:host={$host};port={$port};charset={$charset}";
        if ($dbname) {
            $dsn .= ";dbname={$dbname}";
        }
        return new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

 /**
  * Find mysql binary.
  *
  * @param string $name Nome
  * @return string
  */
    private static function findMysqlBinary(string $name): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $winPaths = [
                'd:/xampp/mysql/bin',
                'c:/xampp/mysql/bin',
                'c:/Program Files/MySQL/MySQL Server 8.0/bin',
                'c:/Program Files/MariaDB 10.6/bin',
            ];
            foreach ($winPaths as $dir) {
                $dir = rtrim(str_replace('/', DIRECTORY_SEPARATOR, $dir), DIRECTORY_SEPARATOR);
                if (file_exists($dir . DIRECTORY_SEPARATOR . $name . '.exe')) {
                    return $dir . DIRECTORY_SEPARATOR . $name . '.exe';
                }
            }
            $found = trim(shell_exec("where {$name} 2>NUL") ?: '');
            if ($found) return explode("\n", $found)[0];
            return $name;
        }

        $found = trim(shell_exec("which {$name} 2>/dev/null") ?: '');
        return $found ?: $name;
    }
}
