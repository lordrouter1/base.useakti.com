<?php

namespace Akti\Models;

use PDO;

class Branch
{
    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function create(array $data): int
    {
        $stmt = $this->conn->prepare("
            INSERT INTO branches (tenant_id, name, code, cnpj, address, city, state, phone, is_headquarters, is_active)
            VALUES (:tenant_id, :name, :code, :cnpj, :address, :city, :state, :phone, :is_headquarters, :is_active)
        ");
        $stmt->execute([
            ':tenant_id'       => $data['tenant_id'],
            ':name'            => $data['name'],
            ':code'            => $data['code'] ?? null,
            ':cnpj'            => $data['cnpj'] ?? null,
            ':address'         => $data['address'] ?? null,
            ':city'            => $data['city'] ?? null,
            ':state'           => $data['state'] ?? null,
            ':phone'           => $data['phone'] ?? null,
            ':is_headquarters' => $data['is_headquarters'] ?? 0,
            ':is_active'       => $data['is_active'] ?? 1,
        ]);
        return (int) $this->conn->lastInsertId();
    }

    public function readAll(int $tenantId): array
    {
        $stmt = $this->conn->prepare("SELECT * FROM branches WHERE tenant_id = :tid AND deleted_at IS NULL ORDER BY is_headquarters DESC, name ASC");
        $stmt->execute([':tid' => $tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function readOne(int $id, int $tenantId): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM branches WHERE id = :id AND tenant_id = :tid AND deleted_at IS NULL");
        $stmt->execute([':id' => $id, ':tid' => $tenantId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function update(int $id, int $tenantId, array $data): bool
    {
        $stmt = $this->conn->prepare("
            UPDATE branches SET name = :name, code = :code, cnpj = :cnpj, address = :address, city = :city, state = :state, phone = :phone, is_headquarters = :is_headquarters, is_active = :is_active
            WHERE id = :id AND tenant_id = :tid
        ");
        return $stmt->execute([
            ':name'            => $data['name'],
            ':code'            => $data['code'] ?? null,
            ':cnpj'            => $data['cnpj'] ?? null,
            ':address'         => $data['address'] ?? null,
            ':city'            => $data['city'] ?? null,
            ':state'           => $data['state'] ?? null,
            ':phone'           => $data['phone'] ?? null,
            ':is_headquarters' => $data['is_headquarters'] ?? 0,
            ':is_active'       => $data['is_active'] ?? 1,
            ':id'              => $id,
            ':tid'             => $tenantId,
        ]);
    }

    public function delete(int $id, int $tenantId): bool
    {
        $stmt = $this->conn->prepare("UPDATE branches SET deleted_at = NOW() WHERE id = :id AND tenant_id = :tid");
        return $stmt->execute([':id' => $id, ':tid' => $tenantId]);
    }
}
