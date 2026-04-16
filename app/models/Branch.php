<?php

namespace Akti\Models;

use PDO;

/**
 * Model de filiais/unidades da empresa.
 */
class Branch
{
    private PDO $conn;

    /**
     * Construtor da classe Branch.
     *
     * @param PDO $db Conexão PDO com o banco de dados
     */
    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * Cria um novo registro no banco de dados.
     *
     * @param array $data Dados para processamento
     * @return int
     */
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

    /**
     * Retorna todos os registros.
     *
     * @param int $tenantId ID do tenant
     * @return array
     */
    public function readAll(int $tenantId): array
    {
        $stmt = $this->conn->prepare("SELECT * FROM branches WHERE tenant_id = :tid AND deleted_at IS NULL ORDER BY is_headquarters DESC, name ASC");
        $stmt->execute([':tid' => $tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna um registro pelo ID.
     *
     * @param int $id ID do registro
     * @param int $tenantId ID do tenant
     * @return array|null
     */
    public function readOne(int $id, int $tenantId): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM branches WHERE id = :id AND tenant_id = :tid AND deleted_at IS NULL");
        $stmt->execute([':id' => $id, ':tid' => $tenantId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Atualiza um registro existente.
     *
     * @param int $id ID do registro
     * @param int $tenantId ID do tenant
     * @param array $data Dados para processamento
     * @return bool
     */
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

    /**
     * Remove um registro pelo ID.
     *
     * @param int $id ID do registro
     * @param int $tenantId ID do tenant
     * @return bool
     */
    public function delete(int $id, int $tenantId): bool
    {
        $stmt = $this->conn->prepare("UPDATE branches SET deleted_at = NOW() WHERE id = :id AND tenant_id = :tid");
        return $stmt->execute([':id' => $id, ':tid' => $tenantId]);
    }
}
