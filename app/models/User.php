<?php
class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $name;
    public $email;
    public $password;
    public $role;
    public $group_id;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function login($email, $password) {
        $query = "SELECT id, name, password, role, group_id FROM " . $this->table_name . " WHERE email = :email LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row && password_verify($password, $row['password'])) {
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->role = $row['role'];
            $this->group_id = $row['group_id'];
            return true;
        }
        
        return false;
    }

    public function readAll() {
        $query = "SELECT u.*, g.name as group_name 
                  FROM " . $this->table_name . " u 
                  LEFT JOIN user_groups g ON u.group_id = g.id 
                  ORDER BY u.name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
    


    public function countAll() {
        $query = "SELECT COUNT(*) FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (name, email, password, role, group_id, created_at) 
                  VALUES (:name, :email, :password, :role, :group_id, NOW())";
        
        $stmt = $this->conn->prepare($query);

        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->password = password_hash($this->password, PASSWORD_BCRYPT);
        
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password', $this->password);
        $stmt->bindParam(':role', $this->role);
        $stmt->bindParam(':group_id', $this->group_id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function readOne($id) {
        $query = "SELECT u.*, g.name as group_name 
                  FROM " . $this->table_name . " u 
                  LEFT JOIN user_groups g ON u.group_id = g.id 
                  WHERE u.id = :id LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->email = $row['email'];
            $this->role = $row['role'];
            $this->group_id = $row['group_id'];
            return $row;
        }
        return false;
    }

    public function update() {
        // Build query efficiently based on whether password checks out
        $query = "UPDATE " . $this->table_name . " 
                  SET name = :name, 
                      email = :email, 
                      role = :role, 
                      group_id = :group_id";
        
        if (!empty($this->password)) {
            $query .= ", password = :password";
        }
        
        $query .= " WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);

        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':role', $this->role);
        $stmt->bindParam(':group_id', $this->group_id);
        $stmt->bindParam(':id', $this->id);

        if (!empty($this->password)) {
            $this->password = password_hash($this->password, PASSWORD_BCRYPT);
            $stmt->bindParam(':password', $this->password);
        }

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function checkPermission($userId, $page) {
        // Obter usuario e Role
        $query = "SELECT role, group_id FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $userId);
        $stmt->execute();
        
        if($stmt->rowCount() == 0) return false;
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user['role'] === 'admin') {
            return true;
        }

        // Se não, verifica permissões do grupo
        if ($user['group_id']) {
            $query = "SELECT * FROM group_permissions WHERE group_id = :group_id AND page_name = :page_name";
            $stmtPermissions = $this->conn->prepare($query);
            $stmtPermissions->bindParam(':group_id', $user['group_id']);
            $stmtPermissions->bindParam(':page_name', $page);
            $stmtPermissions->execute();
            if ($stmtPermissions->rowCount() > 0) {
                return true;
            }
        }
        
        return false; 
    }

    /**
     * Retorna os IDs de setores permitidos para o usuário.
     * Admin tem acesso a todos. Se o grupo não tem restrições, retorna vazio (= todos).
     */
    public function getAllowedSectorIds($userId) {
        $query = "SELECT role, group_id FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $userId);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || $user['role'] === 'admin') {
            return []; // vazio = acesso total
        }

        if ($user['group_id']) {
            $stmtPerms = $this->conn->prepare("SELECT page_name FROM group_permissions WHERE group_id = :gid AND page_name LIKE 'sector_%'");
            $stmtPerms->bindParam(':gid', $user['group_id']);
            $stmtPerms->execute();
            $perms = $stmtPerms->fetchAll(PDO::FETCH_COLUMN);
            $sectorIds = [];
            foreach ($perms as $p) {
                $sectorIds[] = (int) str_replace('sector_', '', $p);
            }
            return $sectorIds;
        }

        return []; // sem grupo = acesso total
    }
}
