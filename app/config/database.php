<?php

require_once __DIR__ . '/tenant.php';

/**
 * Database — Singleton com cache por tenant (DSN).
 *
 * Cada tenant possui sua própria conexão PDO, mas reutilizada
 * durante todo o request (evita múltiplas conexões ao mesmo banco).
 *
 * Uso:
 *   $pdo = Database::getInstance();          // tenant atual
 *   $pdo = Database::getInstance('outro_db'); // banco específico
 *   $pdo = (new Database())->getConnection(); // compatibilidade legada
 *
 * @see ROADMAP Fase 2 — Item 3.1
 */
class Database {
    /** @var PDO[] Cache de instâncias PDO indexadas pelo DSN */
    private static $instances = [];

    private $host;
    private $port;
    private $db_name;
    private $username;
    private $password;
    private $charset;
    public $conn;

    public function __construct() {
        $tenantConfig = TenantManager::getTenantConfig();
        $this->host     = $tenantConfig['host'];
        $this->port     = $tenantConfig['port'];
        $this->db_name  = $tenantConfig['db_name'];
        $this->username = $tenantConfig['username'];
        $this->password = $tenantConfig['password'];
        $this->charset  = $tenantConfig['charset'];
    }

    /**
     * Retorna uma conexão PDO via singleton (cached por DSN).
     *
     * @param string|null $tenantDb  Nome do banco de dados (null = tenant atual)
     * @return PDO
     * @throws \RuntimeException Se a conexão falhar
     */
    public static function getInstance(?string $tenantDb = null): PDO
    {
        $tenantConfig = TenantManager::getTenantConfig();

        $host    = $tenantConfig['host'];
        $port    = $tenantConfig['port'];
        $dbName  = $tenantDb ?? $tenantConfig['db_name'];
        $user    = $tenantConfig['username'];
        $pass    = $tenantConfig['password'];
        $charset = $tenantConfig['charset'];

        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $port, $dbName, $charset);

        if (isset(self::$instances[$dsn]) && self::$instances[$dsn] instanceof PDO) {
            return self::$instances[$dsn];
        }

        try {
            $pdo = new PDO($dsn, $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$instances[$dsn] = $pdo;
            return $pdo;
        } catch (PDOException $exception) {
            error_log('[Database] Connection failed: ' . $exception->getMessage());
            throw new \RuntimeException('Falha na conexão com o banco de dados.');
        }
    }

    /**
     * Wrapper de compatibilidade — retorna conexão PDO via singleton.
     *
     * Código legado que usa `(new Database())->getConnection()` continuará
     * funcionando, mas agora reaproveita a mesma conexão por DSN.
     *
     * @return PDO
     */
    public function getConnection(): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $this->host,
            $this->port,
            $this->db_name,
            $this->charset
        );

        if (isset(self::$instances[$dsn]) && self::$instances[$dsn] instanceof PDO) {
            $this->conn = self::$instances[$dsn];
            return $this->conn;
        }

        try {
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$instances[$dsn] = $this->conn;
        } catch (PDOException $exception) {
            error_log('[Database] Connection failed: ' . $exception->getMessage());
            throw new \RuntimeException('Falha na conexão com o banco de dados.');
        }

        return $this->conn;
    }

    /**
     * Remove todas as instâncias em cache. Útil para testes unitários.
     */
    public static function resetInstances(): void
    {
        self::$instances = [];
    }

    /**
     * Remove a instância de um DSN específico.
     *
     * @param string|null $tenantDb  Nome do banco (null = todos)
     */
    public static function resetInstance(?string $tenantDb = null): void
    {
        if ($tenantDb === null) {
            self::$instances = [];
            return;
        }

        $tenantConfig = TenantManager::getTenantConfig();
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $tenantConfig['host'],
            $tenantConfig['port'],
            $tenantDb,
            $tenantConfig['charset']
        );
        unset(self::$instances[$dsn]);
    }

    /**
     * Retorna uma conexão PDO para o banco master (akti_master).
     *
     * @return PDO
     * @throws \RuntimeException Se a conexão falhar
     */
    public static function getMasterInstance(): PDO
    {
        $masterConfig = TenantManager::getMasterConfig();

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $masterConfig['host'],
            $masterConfig['port'],
            $masterConfig['db_name'],
            $masterConfig['charset']
        );

        if (isset(self::$instances[$dsn]) && self::$instances[$dsn] instanceof PDO) {
            return self::$instances[$dsn];
        }

        try {
            $pdo = new PDO($dsn, $masterConfig['username'], $masterConfig['password']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            self::$instances[$dsn] = $pdo;
            return $pdo;
        } catch (PDOException $exception) {
            error_log('[Database] Master connection failed: ' . $exception->getMessage());
            throw new \RuntimeException('Falha na conexão com o banco master.');
        }
    }

    /**
     * Retorna as credenciais do banco master para uso em operações cross-DB.
     *
     * @return array{host: string, port: int, username: string, password: string, charset: string, db_name: string}
     */
    public static function getMasterCredentials(): array
    {
        return TenantManager::getMasterConfig();
    }

    /**
     * Cria uma conexão PDO avulsa (não cached) para um banco de dados específico.
     * Usada por operações cross-tenant (migrations, provisioning).
     *
     * @param string      $host
     * @param int         $port
     * @param string      $user
     * @param string      $pass
     * @param string|null $dbname
     * @param string      $charset
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
}
