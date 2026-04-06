<?php
/**
 * Akti Master Admin - Conexão com o banco de dados
 */

class Database
{
    private static $instance = null;
    private $pdo;

    public function __construct($host = DB_HOST, $port = DB_PORT, $dbname = DB_NAME, $user = DB_USER, $pass = DB_PASS, $charset = DB_CHARSET)
    {
        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
            $this->pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            die('Erro de conexão: ' . $e->getMessage());
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->pdo;
    }

    /**
     * Cria uma conexão direta para um banco específico (para provisionamento)
     */
    public static function connectTo($host, $port, $user, $pass, $dbname = null, $charset = 'utf8mb4')
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
