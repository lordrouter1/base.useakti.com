<?php

namespace Akti\Models;

use PDO;

/**
 * Model de insumos substitutos (v2).
 */
class SupplySubstitute
{
    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * Retorna todos os substitutos de um insumo.
     *
     * @param int $supplyId ID do insumo principal
     * @return array
     */
    public function getBySupply(int $supplyId): array
    {
        $stmt = $this->conn->prepare(
            "SELECT ss.*, s.name AS substitute_name, s.code AS substitute_code,
                    s.unit_measure AS substitute_unit, s.cost_price AS substitute_cost
             FROM supply_substitutes ss
             JOIN supplies s ON s.id = ss.substitute_id
             WHERE ss.supply_id = :supply_id
             ORDER BY ss.priority ASC, s.name ASC"
        );
        $stmt->execute([':supply_id' => $supplyId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Adiciona um substituto.
     *
     * @param array $data
     * @return int
     */
    public function create(array $data): int
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO supply_substitutes (supply_id, substitute_id, conversion_rate, priority, notes, is_active, tenant_id)
             VALUES (:supply_id, :substitute_id, :conversion_rate, :priority, :notes, :is_active,
                 (SELECT tenant_id FROM supplies WHERE id = :sid LIMIT 1))"
        );
        $stmt->execute([
            ':supply_id'       => $data['supply_id'],
            ':substitute_id'   => $data['substitute_id'],
            ':conversion_rate' => $data['conversion_rate'] ?? 1.0,
            ':priority'        => $data['priority'] ?? 1,
            ':notes'           => $data['notes'] ?? null,
            ':is_active'       => $data['is_active'] ?? 1,
            ':sid'             => $data['supply_id'],
        ]);
        return (int) $this->conn->lastInsertId();
    }

    /**
     * Atualiza um substituto.
     *
     * @param int   $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE supply_substitutes SET
                conversion_rate = :conversion_rate, priority = :priority,
                notes = :notes, is_active = :is_active
             WHERE id = :id"
        );
        return $stmt->execute([
            ':id'              => $id,
            ':conversion_rate' => $data['conversion_rate'] ?? 1.0,
            ':priority'        => $data['priority'] ?? 1,
            ':notes'           => $data['notes'] ?? null,
            ':is_active'       => $data['is_active'] ?? 1,
        ]);
    }

    /**
     * Remove um substituto.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM supply_substitutes WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Busca substituto disponível por prioridade (com estoque).
     * Retorna o primeiro substituto ativo com estoque suficiente.
     *
     * @param int        $supplyId       ID do insumo principal
     * @param float      $neededQty      Quantidade necessária (já com conversão)
     * @param int|null   $warehouseId    Depósito (null = todos)
     * @return array|null ['substitute' => ..., 'converted_qty' => ...]
     */
    public function findAvailableSubstitute(int $supplyId, float $neededQty, ?int $warehouseId = null): ?array
    {
        $substitutes = $this->getBySupply($supplyId);

        foreach ($substitutes as $sub) {
            if (!$sub['is_active']) {
                continue;
            }

            $convertedQty = $neededQty * (float) $sub['conversion_rate'];

            // Verificar estoque do substituto
            if ($warehouseId !== null) {
                $sql = "SELECT COALESCE(SUM(quantity), 0) FROM supply_stock_items
                        WHERE supply_id = :sid AND warehouse_id = :wid AND quantity > 0";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([':sid' => $sub['substitute_id'], ':wid' => $warehouseId]);
            } else {
                $sql = "SELECT COALESCE(SUM(quantity), 0) FROM supply_stock_items
                        WHERE supply_id = :sid AND quantity > 0";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([':sid' => $sub['substitute_id']]);
            }

            $available = (float) $stmt->fetchColumn();

            if ($available >= $convertedQty) {
                return [
                    'substitute_id'   => (int) $sub['substitute_id'],
                    'substitute_name' => $sub['substitute_name'],
                    'substitute_code' => $sub['substitute_code'],
                    'conversion_rate' => (float) $sub['conversion_rate'],
                    'converted_qty'   => $convertedQty,
                    'available_stock' => $available,
                    'priority'        => (int) $sub['priority'],
                ];
            }
        }

        return null;
    }

    /**
     * Retorna todos os substitutos disponíveis (com e sem estoque).
     *
     * @param int      $supplyId
     * @param float    $neededQty
     * @param int|null $warehouseId
     * @return array
     */
    public function findAllSubstitutes(int $supplyId, float $neededQty, ?int $warehouseId = null): array
    {
        $substitutes = $this->getBySupply($supplyId);
        $results = [];

        foreach ($substitutes as $sub) {
            if (!$sub['is_active']) {
                continue;
            }

            $convertedQty = $neededQty * (float) $sub['conversion_rate'];

            if ($warehouseId !== null) {
                $sql = "SELECT COALESCE(SUM(quantity), 0) FROM supply_stock_items
                        WHERE supply_id = :sid AND warehouse_id = :wid AND quantity > 0";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([':sid' => $sub['substitute_id'], ':wid' => $warehouseId]);
            } else {
                $sql = "SELECT COALESCE(SUM(quantity), 0) FROM supply_stock_items
                        WHERE supply_id = :sid AND quantity > 0";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([':sid' => $sub['substitute_id']]);
            }

            $available = (float) $stmt->fetchColumn();

            $results[] = [
                'substitute_id'   => (int) $sub['substitute_id'],
                'substitute_name' => $sub['substitute_name'],
                'substitute_code' => $sub['substitute_code'],
                'conversion_rate' => (float) $sub['conversion_rate'],
                'converted_qty'   => $convertedQty,
                'available_stock' => $available,
                'sufficient'      => $available >= $convertedQty,
                'priority'        => (int) $sub['priority'],
            ];
        }

        return $results;
    }
}
