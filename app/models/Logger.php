<?php
namespace Akti\Models;

class Logger {
    private $conn;
    private $table_name = "system_logs";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function log($action, $details = "", $user_id = null) {
        $query = "INSERT INTO " . $this->table_name . " 
                  (user_id, action, details, ip_address, created_at) 
                  VALUES (:user_id, :action, :details, :ip_address, NOW())";
        
        $stmt = $this->conn->prepare($query);

        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        if ($user_id === null && isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
        }

        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':details', $details);
        $stmt->bindParam(':ip_address', $ip_address);

        return $stmt->execute();
    }
}
