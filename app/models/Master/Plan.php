<?php

namespace Akti\Models\Master;

use PDO;

class Plan
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function readAll(): array
    {
        $stmt = $this->db->query("
            SELECT p.*,
                   (SELECT COUNT(*) FROM tenant_clients tc WHERE tc.plan_id = p.id) as total_clients
            FROM plans p
            ORDER BY p.price ASC
        ");
        return $stmt->fetchAll();
    }

    public function readActive(): array
    {
        $stmt = $this->db->query("SELECT * FROM plans WHERE is_active = 1 ORDER BY price ASC");
        return $stmt->fetchAll();
    }

    public function readOne(int $id): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM plans WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function create(array $data): string
    {
        $stmt = $this->db->prepare("
            INSERT INTO plans (plan_name, description, max_users, max_products, max_warehouses, max_price_tables, max_sectors, price, is_active)
            VALUES (:plan_name, :description, :max_users, :max_products, :max_warehouses, :max_price_tables, :max_sectors, :price, :is_active)
        ");
        $stmt->execute([
            'plan_name'       => $data['plan_name'],
            'description'     => $data['description'] ?: null,
            'max_users'       => $data['max_users'] ?: null,
            'max_products'    => $data['max_products'] ?: null,
            'max_warehouses'  => $data['max_warehouses'] ?: null,
            'max_price_tables'=> $data['max_price_tables'] ?: null,
            'max_sectors'     => $data['max_sectors'] ?: null,
            'price'           => $data['price'],
            'is_active'       => isset($data['is_active']) ? 1 : 0,
        ]);
        return $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare("
            UPDATE plans SET
                plan_name = :plan_name,
                description = :description,
                max_users = :max_users,
                max_products = :max_products,
                max_warehouses = :max_warehouses,
                max_price_tables = :max_price_tables,
                max_sectors = :max_sectors,
                price = :price,
                is_active = :is_active
            WHERE id = :id
        ");
        $stmt->execute([
            'id'              => $id,
            'plan_name'       => $data['plan_name'],
            'description'     => $data['description'] ?: null,
            'max_users'       => $data['max_users'] ?: null,
            'max_products'    => $data['max_products'] ?: null,
            'max_warehouses'  => $data['max_warehouses'] ?: null,
            'max_price_tables'=> $data['max_price_tables'] ?: null,
            'max_sectors'     => $data['max_sectors'] ?: null,
            'price'           => $data['price'],
            'is_active'       => isset($data['is_active']) ? 1 : 0,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM tenant_clients WHERE plan_id = :id");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        if ($result['total'] > 0) {
            return false;
        }
        $stmt = $this->db->prepare("DELETE FROM plans WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return true;
    }
}
