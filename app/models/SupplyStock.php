<?php

namespace Akti\Models;

use PDO;

/**
 * Model de estoque de insumos.
 */
class SupplyStock
{
    private PDO $conn;

    /**
     * Construtor da classe SupplyStock.
     *
     * @param PDO $db Conexão PDO com o banco de dados
     */
    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    // ──── Itens de Estoque ────

    /**
     * Obtém dados específicos.
     *
     * @param array $filters Filtros aplicados
     * @param int $page Número da página
     * @param int $perPage Registros por página
     * @return array
     */
    public function getItems(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $where = ' WHERE s.deleted_at IS NULL';
        $params = [];

        if (!empty($filters['warehouse_id'])) {
            $where .= ' AND si.warehouse_id = :warehouse_id';
            $params[':warehouse_id'] = (int) $filters['warehouse_id'];
        }
        if (!empty($filters['search'])) {
            $where .= ' AND (s.name LIKE :search OR s.code LIKE :s2)';
            $params[':search'] = '%' . $filters['search'] . '%';
            $params[':s2'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['low_stock_only'])) {
            $where .= ' AND si.quantity <= si.min_quantity AND si.min_quantity > 0';
        }

        $countSql = "SELECT COUNT(*) FROM supply_stock_items si JOIN supplies s ON s.id = si.supply_id" . $where;
        $countStmt = $this->conn->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $sql = "SELECT si.*, s.name AS supply_name, s.code AS supply_code, s.unit_measure, s.cost_price,
                       w.name AS warehouse_name
                FROM supply_stock_items si
                JOIN supplies s ON s.id = si.supply_id
                LEFT JOIN warehouses w ON w.id = si.warehouse_id
                {$where}
                ORDER BY s.name ASC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data'         => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total'        => $total,
            'pages'        => (int) ceil($total / $perPage),
            'current_page' => $page,
        ];
    }

 /**
  * Get or create item.
  *
  * @param int $warehouseId Warehouse id
  * @param int $supplyId Supply id
  * @param string|null $batchNumber Batch number
  * @return array
  */
    public function getOrCreateItem(int $warehouseId, int $supplyId, ?string $batchNumber = null): array
    {
        $sql = "SELECT * FROM supply_stock_items
                WHERE warehouse_id = :wid AND supply_id = :sid";
        $params = [':wid' => $warehouseId, ':sid' => $supplyId];

        if ($batchNumber !== null) {
            $sql .= " AND batch_number = :batch";
            $params[':batch'] = $batchNumber;
        } else {
            $sql .= " AND batch_number IS NULL";
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            return $row;
        }

        $insertStmt = $this->conn->prepare(
            "INSERT INTO supply_stock_items (warehouse_id, supply_id, quantity, batch_number)
             VALUES (:wid, :sid, 0, :batch)"
        );
        $insertStmt->execute([
            ':wid'   => $warehouseId,
            ':sid'   => $supplyId,
            ':batch' => $batchNumber,
        ]);

        $id = (int) $this->conn->lastInsertId();
        return array_merge(['id' => $id, 'warehouse_id' => $warehouseId, 'supply_id' => $supplyId, 'quantity' => 0, 'batch_number' => $batchNumber]);
    }

 /**
  * Update quantity.
  *
  * @param int $itemId Item id
  * @param float $newQuantity New quantity
  * @return bool
  */
    public function updateQuantity(int $itemId, float $newQuantity): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE supply_stock_items SET quantity = :qty, last_updated = NOW() WHERE id = :id"
        );
        return $stmt->execute([':qty' => $newQuantity, ':id' => $itemId]);
    }

 /**
  * Get total stock.
  *
  * @param int $supplyId Supply id
  * @return float
  */
    public function getTotalStock(int $supplyId): float
    {
        $stmt = $this->conn->prepare(
            "SELECT COALESCE(SUM(quantity), 0) FROM supply_stock_items WHERE supply_id = :sid"
        );
        $stmt->execute([':sid' => $supplyId]);
        return (float) $stmt->fetchColumn();
    }

    // ──── Dashboard ────

 /**
  * Get dashboard summary.
  *
  * @param int|null $warehouseId Warehouse id
  * @return array
  */
    public function getDashboardSummary(?int $warehouseId = null): array
    {
        $where = '';
        $params = [];
        if ($warehouseId) {
            $where = ' AND si.warehouse_id = :wid';
            $params[':wid'] = $warehouseId;
        }

        $stmt = $this->conn->prepare(
            "SELECT
                COUNT(DISTINCT si.id) AS total_items,
                COALESCE(SUM(si.quantity * s.cost_price), 0) AS total_value,
                SUM(CASE WHEN si.quantity <= si.min_quantity AND si.min_quantity > 0 THEN 1 ELSE 0 END) AS low_stock_count
             FROM supply_stock_items si
             JOIN supplies s ON s.id = si.supply_id AND s.deleted_at IS NULL
             WHERE 1=1 {$where}"
        );
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $movStmt = $this->conn->prepare(
            "SELECT COUNT(*) FROM supply_stock_movements
             WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')" . ($warehouseId ? " AND warehouse_id = :wid" : "")
        );
        $movStmt->execute($warehouseId ? [':wid' => $warehouseId] : []);
        $movCount = (int) $movStmt->fetchColumn();

        return [
            'total_items'    => (int) ($row['total_items'] ?? 0),
            'total_value'    => (float) ($row['total_value'] ?? 0),
            'low_stock_count' => (int) ($row['low_stock_count'] ?? 0),
            'movements_month' => $movCount,
        ];
    }

 /**
  * Get low stock items.
  *
  * @param int $limit Limite de registros
  * @return array
  */
    public function getLowStockItems(int $limit = 20): array
    {
        $stmt = $this->conn->prepare(
            "SELECT si.*, s.name AS supply_name, s.code AS supply_code, s.unit_measure, w.name AS warehouse_name
             FROM supply_stock_items si
             JOIN supplies s ON s.id = si.supply_id AND s.deleted_at IS NULL
             LEFT JOIN warehouses w ON w.id = si.warehouse_id
             WHERE si.quantity <= si.min_quantity AND si.min_quantity > 0
             ORDER BY (si.quantity / si.min_quantity) ASC
             LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ──── Movimentações ────

 /**
  * Add movement.
  *
  * @param array $data Dados para processamento
  * @return int
  */
    public function addMovement(array $data): int
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO supply_stock_movements (warehouse_id, supply_id, type, quantity, unit_price, batch_number, reason, reference_type, reference_id, created_by)
             VALUES (:wid, :sid, :type, :qty, :price, :batch, :reason, :ref_type, :ref_id, :user)"
        );
        $stmt->execute([
            ':wid'      => $data['warehouse_id'],
            ':sid'      => $data['supply_id'],
            ':type'     => $data['type'],
            ':qty'      => $data['quantity'],
            ':price'    => $data['unit_price'] ?? null,
            ':batch'    => $data['batch_number'] ?? null,
            ':reason'   => $data['reason'] ?? null,
            ':ref_type' => $data['reference_type'] ?? null,
            ':ref_id'   => $data['reference_id'] ?? null,
            ':user'     => $data['created_by'] ?? ($_SESSION['user']['id'] ?? null),
        ]);
        return (int) $this->conn->lastInsertId();
    }

 /**
  * Get movements.
  *
  * @param array $filters Filtros aplicados
  * @param int $page Número da página
  * @param int $perPage Registros por página
  * @return array
  */
    public function getMovements(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $where = ' WHERE 1=1';
        $params = [];

        if (!empty($filters['warehouse_id'])) {
            $where .= ' AND m.warehouse_id = :wid';
            $params[':wid'] = (int) $filters['warehouse_id'];
        }
        if (!empty($filters['supply_id'])) {
            $where .= ' AND m.supply_id = :sid';
            $params[':sid'] = (int) $filters['supply_id'];
        }
        if (!empty($filters['type'])) {
            $where .= ' AND m.type = :type';
            $params[':type'] = $filters['type'];
        }
        if (!empty($filters['date_from'])) {
            $where .= ' AND m.created_at >= :date_from';
            $params[':date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where .= ' AND m.created_at <= :date_to';
            $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
        }

        $countSql = "SELECT COUNT(*) FROM supply_stock_movements m" . $where;
        $countStmt = $this->conn->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $sql = "SELECT m.*, s.name AS supply_name, s.code AS supply_code, w.name AS warehouse_name
                FROM supply_stock_movements m
                JOIN supplies s ON s.id = m.supply_id
                LEFT JOIN warehouses w ON w.id = m.warehouse_id
                {$where}
                ORDER BY m.created_at DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data'         => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total'        => $total,
            'pages'        => (int) ceil($total / $perPage),
            'current_page' => $page,
        ];
    }

    // ──── FEFO / Lotes (Fase 4) ────

 /**
  * Get batches by supply.
  *
  * @param int $supplyId Supply id
  * @param int $warehouseId Warehouse id
  * @return array
  */
    public function getBatchesBySupply(int $supplyId, int $warehouseId): array
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM supply_stock_items
             WHERE supply_id = :sid AND warehouse_id = :wid AND quantity > 0
             ORDER BY
                CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END,
                expiry_date ASC,
                created_at ASC"
        );
        $stmt->execute([':sid' => $supplyId, ':wid' => $warehouseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

 /**
  * Get expiring batches.
  *
  * @param int $days Days
  * @param int $limit Limite de registros
  * @return array
  */
    public function getExpiringBatches(int $days = 30, int $limit = 20): array
    {
        $stmt = $this->conn->prepare(
            "SELECT si.*, s.name AS supply_name, s.code AS supply_code, w.name AS warehouse_name
             FROM supply_stock_items si
             JOIN supplies s ON s.id = si.supply_id AND s.deleted_at IS NULL
             LEFT JOIN warehouses w ON w.id = si.warehouse_id
             WHERE si.expiry_date IS NOT NULL
               AND si.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)
               AND si.quantity > 0
             ORDER BY si.expiry_date ASC
             LIMIT :limit"
        );
        $stmt->bindValue(':days', $days, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

 /**
  * Get expired batches.
  *
  * @param int $limit Limite de registros
  * @return array
  */
    public function getExpiredBatches(int $limit = 20): array
    {
        $stmt = $this->conn->prepare(
            "SELECT si.*, s.name AS supply_name, s.code AS supply_code, w.name AS warehouse_name
             FROM supply_stock_items si
             JOIN supplies s ON s.id = si.supply_id AND s.deleted_at IS NULL
             LEFT JOIN warehouses w ON w.id = si.warehouse_id
             WHERE si.expiry_date IS NOT NULL AND si.expiry_date < CURDATE() AND si.quantity > 0
             ORDER BY si.expiry_date ASC
             LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ──── MRP / Reorder (Fase 8) ────

 /**
  * Get reorder items.
  * @return array
  */
    public function getReorderItems(): array
    {
        $stmt = $this->conn->prepare(
            "SELECT s.id AS supply_id, s.code, s.name, s.unit_measure, s.reorder_point, s.min_stock,
                    COALESCE(SUM(si.quantity), 0) AS total_stock,
                    ss.supplier_id AS pref_supplier_id, sup.company_name AS pref_supplier_name,
                    ss.min_order_qty, ss.unit_price AS pref_price, ss.lead_time_days
             FROM supplies s
             LEFT JOIN supply_stock_items si ON si.supply_id = s.id
             LEFT JOIN supply_suppliers ss ON ss.supply_id = s.id AND ss.is_preferred = 1
             LEFT JOIN suppliers sup ON sup.id = ss.supplier_id
             WHERE s.deleted_at IS NULL AND s.is_active = 1 AND s.reorder_point > 0
             GROUP BY s.id
             HAVING total_stock <= s.reorder_point"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ──── Warehouses helper ────

 /**
  * Get warehouses.
  * @return array
  */
    public function getWarehouses(): array
    {
        $stmt = $this->conn->prepare("SELECT * FROM warehouses WHERE is_active = 1 ORDER BY name");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
