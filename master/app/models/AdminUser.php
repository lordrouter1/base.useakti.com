<?php
/**
 * Model: AdminUser
 * Gerencia os administradores do painel master
 */

class AdminUser
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function findByEmail($email)
    {
        $stmt = $this->db->prepare("SELECT * FROM admin_users WHERE email = :email AND is_active = 1 LIMIT 1");
        $stmt->execute(['email' => $email]);
        return $stmt->fetch();
    }

    public function findById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM admin_users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function updateLastLogin($id)
    {
        $stmt = $this->db->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }

    public function updatePassword($id, $hashedPassword)
    {
        $stmt = $this->db->prepare("UPDATE admin_users SET password = :password WHERE id = :id");
        $stmt->execute(['password' => $hashedPassword, 'id' => $id]);
    }

    public function readAll()
    {
        $stmt = $this->db->query("SELECT id, name, email, is_active, last_login, created_at FROM admin_users ORDER BY name");
        return $stmt->fetchAll();
    }
}
