<?php

require_once __DIR__ . '/tenant.php';

class Database {
    private $host;
    private $port;
    private $db_name;
    private $username;
    private $password;
    private $charset;
    public $conn;

    public function __construct() {
        $tenantConfig = TenantManager::getTenantConfig();
        $this->host = $tenantConfig['host'];
        $this->port = $tenantConfig['port'];
        $this->db_name = $tenantConfig['db_name'];
        $this->username = $tenantConfig['username'];
        $this->password = $tenantConfig['password'];
        $this->charset = $tenantConfig['charset'];
    }

    public function getConnection() {
        $this->conn = null;

        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $this->host,
                $this->port,
                $this->db_name,
                $this->charset
            );

            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo 'Erro na conexão: ' . $exception->getMessage();
        }

        return $this->conn;
    }
}
