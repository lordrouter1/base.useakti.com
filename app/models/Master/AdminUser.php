<?php

namespace Akti\Models\Master;

use PDO;

class AdminUser
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function findByEmail(string $email): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM admin_users WHERE email = :email AND is_active = 1 LIMIT 1");
        $stmt->execute(['email' => $email]);
        return $stmt->fetch();
    }

    public function findById(int $id): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM admin_users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function updateLastLogin(int $id): void
    {
        $stmt = $this->db->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }

    public function updatePassword(int $id, string $hashedPassword): void
    {
        $stmt = $this->db->prepare("UPDATE admin_users SET password = :password WHERE id = :id");
        $stmt->execute(['password' => $hashedPassword, 'id' => $id]);
    }

    public function readAll(): array
    {
        $stmt = $this->db->query("SELECT id, name, email, is_active, role, last_login, created_at FROM admin_users ORDER BY name");
        return $stmt->fetchAll();
    }

    public function create(array $data): string
    {
        $stmt = $this->db->prepare("
            INSERT INTO admin_users (name, email, password, is_active, role)
            VALUES (:name, :email, :password, :is_active, :role)
        ");
        $stmt->execute([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => password_hash($data['password'], PASSWORD_BCRYPT),
            'is_active' => $data['is_active'] ?? 1,
            'role'      => $data['role'] ?? 'operator',
        ]);
        return $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $fields = [];
        $params = ['id' => $id];

        if (isset($data['name'])) {
            $fields[] = 'name = :name';
            $params['name'] = $data['name'];
        }
        if (isset($data['email'])) {
            $fields[] = 'email = :email';
            $params['email'] = $data['email'];
        }
        if (isset($data['is_active'])) {
            $fields[] = 'is_active = :is_active';
            $params['is_active'] = $data['is_active'];
        }
        if (isset($data['role'])) {
            $fields[] = 'role = :role';
            $params['role'] = $data['role'];
        }
        if (!empty($data['password'])) {
            $fields[] = 'password = :password';
            $params['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        if (empty($fields)) {
            return;
        }

        $sql = "UPDATE admin_users SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare("DELETE FROM admin_users WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }

    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM admin_users WHERE email = :email";
        $params = ['email' => $email];

        if ($excludeId) {
            $sql .= " AND id != :id";
            $params['id'] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function countByRole(): array
    {
        $stmt = $this->db->query("
            SELECT role, COUNT(*) as total 
            FROM admin_users 
            GROUP BY role
        ");
        return $stmt->fetchAll();
    }
}
