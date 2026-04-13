<?php

namespace Akti\Models;

use PDO;
use Akti\Core\EventDispatcher;
use Akti\Core\Event;

class Supply
{
    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    // ──── CRUD Insumos ────

    public function readAll(): array
    {
        $stmt = $this->conn->prepare(
            "SELECT s.*, sc.name AS category_name
             FROM supplies s
             LEFT JOIN supply_categories sc ON sc.id = s.category_id
             WHERE s.deleted_at IS NULL
             ORDER BY s.name ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function readPaginated(int $page = 1, int $perPage = 15, array $filters = []): array
    {
        $where = ' WHERE s.deleted_at IS NULL';
        $params = [];

        if (!empty($filters['search'])) {
            $where .= ' AND (s.name LIKE :search OR s.code LIKE :s2)';
            $params[':search'] = '%' . $filters['search'] . '%';
            $params[':s2'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['category_id'])) {
            $where .= ' AND s.category_id = :category_id';
            $params[':category_id'] = (int) $filters['category_id'];
        }
        if (isset($filters['is_active'])) {
            $where .= ' AND s.is_active = :is_active';
            $params[':is_active'] = (int) $filters['is_active'];
        }

        $countStmt = $this->conn->prepare("SELECT COUNT(*) FROM supplies s" . $where);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $sql = "SELECT s.*, sc.name AS category_name
                FROM supplies s
                LEFT JOIN supply_categories sc ON sc.id = s.category_id
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

    public function readOne(int $id): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT s.*, sc.name AS category_name
             FROM supplies s
             LEFT JOIN supply_categories sc ON sc.id = s.category_id
             WHERE s.id = :id AND s.deleted_at IS NULL"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO supplies (category_id, code, name, description, unit_measure, cost_price,
                min_stock, reorder_point, waste_percent, is_active, notes,
                fiscal_ncm, fiscal_cest, fiscal_origem, fiscal_unidade)
             VALUES (:category_id, :code, :name, :description, :unit_measure, :cost_price,
                :min_stock, :reorder_point, :waste_percent, :is_active, :notes,
                :fiscal_ncm, :fiscal_cest, :fiscal_origem, :fiscal_unidade)"
        );
        $stmt->execute([
            ':category_id'    => $data['category_id'] ?: null,
            ':code'           => $data['code'],
            ':name'           => $data['name'],
            ':description'    => $data['description'] ?? null,
            ':unit_measure'   => $data['unit_measure'] ?? 'un',
            ':cost_price'     => $data['cost_price'] ?? 0,
            ':min_stock'      => $data['min_stock'] ?? 0,
            ':reorder_point'  => $data['reorder_point'] ?? 0,
            ':waste_percent'  => $data['waste_percent'] ?? 0,
            ':is_active'      => $data['is_active'] ?? 1,
            ':notes'          => $data['notes'] ?? null,
            ':fiscal_ncm'     => $data['fiscal_ncm'] ?? null,
            ':fiscal_cest'    => $data['fiscal_cest'] ?? null,
            ':fiscal_origem'  => $data['fiscal_origem'] ?? null,
            ':fiscal_unidade' => $data['fiscal_unidade'] ?? null,
        ]);
        $id = (int) $this->conn->lastInsertId();
        EventDispatcher::dispatch('model.supply.created', new Event('model.supply.created', ['id' => $id, 'name' => $data['name']]));
        return $id;
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE supplies SET
                category_id = :category_id, code = :code, name = :name, description = :description,
                unit_measure = :unit_measure, cost_price = :cost_price, min_stock = :min_stock,
                reorder_point = :reorder_point, waste_percent = :waste_percent, is_active = :is_active,
                notes = :notes, fiscal_ncm = :fiscal_ncm, fiscal_cest = :fiscal_cest,
                fiscal_origem = :fiscal_origem, fiscal_unidade = :fiscal_unidade
             WHERE id = :id AND deleted_at IS NULL"
        );
        $result = $stmt->execute([
            ':id'             => $id,
            ':category_id'    => $data['category_id'] ?: null,
            ':code'           => $data['code'],
            ':name'           => $data['name'],
            ':description'    => $data['description'] ?? null,
            ':unit_measure'   => $data['unit_measure'] ?? 'un',
            ':cost_price'     => $data['cost_price'] ?? 0,
            ':min_stock'      => $data['min_stock'] ?? 0,
            ':reorder_point'  => $data['reorder_point'] ?? 0,
            ':waste_percent'  => $data['waste_percent'] ?? 0,
            ':is_active'      => $data['is_active'] ?? 1,
            ':notes'          => $data['notes'] ?? null,
            ':fiscal_ncm'     => $data['fiscal_ncm'] ?? null,
            ':fiscal_cest'    => $data['fiscal_cest'] ?? null,
            ':fiscal_origem'  => $data['fiscal_origem'] ?? null,
            ':fiscal_unidade' => $data['fiscal_unidade'] ?? null,
        ]);
        EventDispatcher::dispatch('model.supply.updated', new Event('model.supply.updated', ['id' => $id]));
        return $result;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->conn->prepare("UPDATE supplies SET deleted_at = NOW() WHERE id = :id");
        $result = $stmt->execute([':id' => $id]);
        EventDispatcher::dispatch('model.supply.deleted', new Event('model.supply.deleted', ['id' => $id]));
        return $result;
    }

    public function countAll(array $filters = []): int
    {
        $where = ' WHERE deleted_at IS NULL';
        $params = [];
        if (!empty($filters['search'])) {
            $where .= ' AND (name LIKE :search OR code LIKE :s2)';
            $params[':search'] = '%' . $filters['search'] . '%';
            $params[':s2'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['category_id'])) {
            $where .= ' AND category_id = :category_id';
            $params[':category_id'] = (int) $filters['category_id'];
        }
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM supplies" . $where);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function generateNextCode(): string
    {
        $stmt = $this->conn->prepare(
            "SELECT MAX(CAST(SUBSTRING(code, 5) AS UNSIGNED)) AS max_num FROM supplies WHERE code LIKE 'INS-%'"
        );
        $stmt->execute();
        $max = (int) ($stmt->fetchColumn() ?: 0);
        return 'INS-' . str_pad($max + 1, 4, '0', STR_PAD_LEFT);
    }

    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM supplies WHERE code = :code AND deleted_at IS NULL";
        $params = [':code' => $code];
        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    // ──── Categorias ────

    public function getCategories(): array
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM supply_categories WHERE is_active = 1 ORDER BY sort_order, name"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createCategory(array $data): int
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO supply_categories (name, description, sort_order) VALUES (:name, :description, :sort_order)"
        );
        $stmt->execute([
            ':name'        => $data['name'],
            ':description' => $data['description'] ?? null,
            ':sort_order'  => $data['sort_order'] ?? 0,
        ]);
        return (int) $this->conn->lastInsertId();
    }

    public function updateCategory(int $id, array $data): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE supply_categories SET name = :name, description = :description, sort_order = :sort_order WHERE id = :id"
        );
        return $stmt->execute([
            ':id'          => $id,
            ':name'        => $data['name'],
            ':description' => $data['description'] ?? null,
            ':sort_order'  => $data['sort_order'] ?? 0,
        ]);
    }

    public function deleteCategory(int $id): bool
    {
        $check = $this->conn->prepare("SELECT COUNT(*) FROM supplies WHERE category_id = :id AND deleted_at IS NULL");
        $check->execute([':id' => $id]);
        if ((int) $check->fetchColumn() > 0) {
            return false;
        }
        $stmt = $this->conn->prepare("DELETE FROM supply_categories WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    // ──── Vínculo Fornecedor (Fase 2) ────

    public function getSuppliers(int $supplyId): array
    {
        $stmt = $this->conn->prepare(
            "SELECT ss.*, s.company_name, s.trade_name
             FROM supply_suppliers ss
             JOIN suppliers s ON s.id = ss.supplier_id
             WHERE ss.supply_id = :supply_id AND ss.is_active = 1
             ORDER BY ss.is_preferred DESC, s.company_name ASC"
        );
        $stmt->execute([':supply_id' => $supplyId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function linkSupplier(array $data): int
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO supply_suppliers (supply_id, supplier_id, supplier_sku, supplier_name,
                unit_price, min_order_qty, lead_time_days, conversion_factor, is_preferred, notes)
             VALUES (:supply_id, :supplier_id, :supplier_sku, :supplier_name,
                :unit_price, :min_order_qty, :lead_time_days, :conversion_factor, :is_preferred, :notes)"
        );
        $stmt->execute([
            ':supply_id'         => $data['supply_id'],
            ':supplier_id'       => $data['supplier_id'],
            ':supplier_sku'      => $data['supplier_sku'] ?? null,
            ':supplier_name'     => $data['supplier_name'] ?? null,
            ':unit_price'        => $data['unit_price'] ?? 0,
            ':min_order_qty'     => $data['min_order_qty'] ?? 1,
            ':lead_time_days'    => $data['lead_time_days'] ?? null,
            ':conversion_factor' => $data['conversion_factor'] ?? 1,
            ':is_preferred'      => $data['is_preferred'] ?? 0,
            ':notes'             => $data['notes'] ?? null,
        ]);
        $id = (int) $this->conn->lastInsertId();
        EventDispatcher::dispatch('model.supply.supplier_linked', new Event('model.supply.supplier_linked', [
            'id' => $id, 'supply_id' => $data['supply_id'], 'supplier_id' => $data['supplier_id'],
        ]));
        return $id;
    }

    public function updateSupplierLink(int $id, array $data): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE supply_suppliers SET
                supplier_sku = :supplier_sku, supplier_name = :supplier_name, unit_price = :unit_price,
                min_order_qty = :min_order_qty, lead_time_days = :lead_time_days,
                conversion_factor = :conversion_factor, is_preferred = :is_preferred, notes = :notes
             WHERE id = :id"
        );
        return $stmt->execute([
            ':id'                => $id,
            ':supplier_sku'      => $data['supplier_sku'] ?? null,
            ':supplier_name'     => $data['supplier_name'] ?? null,
            ':unit_price'        => $data['unit_price'] ?? 0,
            ':min_order_qty'     => $data['min_order_qty'] ?? 1,
            ':lead_time_days'    => $data['lead_time_days'] ?? null,
            ':conversion_factor' => $data['conversion_factor'] ?? 1,
            ':is_preferred'      => $data['is_preferred'] ?? 0,
            ':notes'             => $data['notes'] ?? null,
        ]);
    }

    public function unlinkSupplier(int $id): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM supply_suppliers WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function setPreferredSupplier(int $supplyId, int $supplierId): bool
    {
        $this->conn->beginTransaction();
        try {
            $stmt = $this->conn->prepare("UPDATE supply_suppliers SET is_preferred = 0 WHERE supply_id = :supply_id");
            $stmt->execute([':supply_id' => $supplyId]);

            $stmt2 = $this->conn->prepare("UPDATE supply_suppliers SET is_preferred = 1 WHERE supply_id = :supply_id AND supplier_id = :supplier_id");
            $stmt2->execute([':supply_id' => $supplyId, ':supplier_id' => $supplierId]);

            $this->conn->commit();
            return true;
        } catch (\Throwable $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function getPreferredSupplier(int $supplyId): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT ss.*, s.company_name FROM supply_suppliers ss
             JOIN suppliers s ON s.id = ss.supplier_id
             WHERE ss.supply_id = :supply_id AND ss.is_preferred = 1
             LIMIT 1"
        );
        $stmt->execute([':supply_id' => $supplyId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getSupplierInsumos(int $supplierId): array
    {
        $stmt = $this->conn->prepare(
            "SELECT ss.*, s.code, s.name, s.unit_measure
             FROM supply_suppliers ss
             JOIN supplies s ON s.id = ss.supply_id
             WHERE ss.supplier_id = :supplier_id AND s.deleted_at IS NULL
             ORDER BY s.name"
        );
        $stmt->execute([':supplier_id' => $supplierId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ──── Histórico de Preços (Fase 5) ────

    public function getPriceHistory(int $supplyId, ?int $supplierId = null, int $limit = 50): array
    {
        $sql = "SELECT sph.*, s.company_name AS supplier_name
                FROM supply_price_history sph
                LEFT JOIN suppliers s ON s.id = sph.supplier_id
                WHERE sph.supply_id = :supply_id";
        $params = [':supply_id' => $supplyId];
        if ($supplierId) {
            $sql .= " AND sph.supplier_id = :supplier_id";
            $params[':supplier_id'] = $supplierId;
        }
        $sql .= " ORDER BY sph.created_at DESC LIMIT :limit";
        $stmt = $this->conn->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function recordPriceHistory(array $data): int
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO supply_price_history (supply_id, supplier_id, unit_price, quantity, source, notes, created_by)
             VALUES (:supply_id, :supplier_id, :unit_price, :quantity, :source, :notes, :created_by)"
        );
        $stmt->execute([
            ':supply_id'   => $data['supply_id'],
            ':supplier_id' => $data['supplier_id'] ?? null,
            ':unit_price'  => $data['unit_price'],
            ':quantity'    => $data['quantity'] ?? null,
            ':source'      => $data['source'] ?? 'compra',
            ':notes'       => $data['notes'] ?? null,
            ':created_by'  => $data['created_by'] ?? null,
        ]);
        return (int) $this->conn->lastInsertId();
    }

    public function updateCostPrice(int $supplyId, float $newCost): bool
    {
        $stmt = $this->conn->prepare("UPDATE supplies SET cost_price = :cost WHERE id = :id");
        return $stmt->execute([':cost' => $newCost, ':id' => $supplyId]);
    }

    // ──── BOM — Vínculo Insumo ↔ Produto (Fase 6) ────

    public function getProductSupplies(int $productId): array
    {
        $stmt = $this->conn->prepare(
            "SELECT ps.*, s.name AS supply_name, s.code AS supply_code, s.cost_price, s.unit_measure AS supply_unit
             FROM product_supplies ps
             JOIN supplies s ON s.id = ps.supply_id
             WHERE ps.product_id = :product_id
             ORDER BY ps.sort_order, s.name"
        );
        $stmt->execute([':product_id' => $productId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSupplyProducts(int $supplyId): array
    {
        $stmt = $this->conn->prepare(
            "SELECT ps.*, p.name AS product_name, p.sku AS product_code, p.price AS product_price
             FROM product_supplies ps
             JOIN products p ON p.id = ps.product_id
             WHERE ps.supply_id = :supply_id
             ORDER BY p.name"
        );
        $stmt->execute([':supply_id' => $supplyId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addProductSupply(array $data): int
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO product_supplies (product_id, supply_id, quantity, yield_qty, unit_measure, waste_percent, is_optional, notes, sort_order)
             VALUES (:product_id, :supply_id, :quantity, :yield_qty, :unit_measure, :waste_percent, :is_optional, :notes, :sort_order)"
        );
        $stmt->execute([
            ':product_id'    => $data['product_id'],
            ':supply_id'     => $data['supply_id'],
            ':quantity'      => $data['quantity'] ?? 0,
            ':yield_qty'     => $data['yield_qty'] ?? 1,
            ':unit_measure'  => $data['unit_measure'] ?? 'un',
            ':waste_percent' => $data['waste_percent'] ?? 0,
            ':is_optional'   => $data['is_optional'] ?? 0,
            ':notes'         => $data['notes'] ?? null,
            ':sort_order'    => $data['sort_order'] ?? 0,
        ]);
        $id = (int) $this->conn->lastInsertId();
        EventDispatcher::dispatch('model.supply.product_linked', new Event('model.supply.product_linked', [
            'id' => $id, 'product_id' => $data['product_id'], 'supply_id' => $data['supply_id'],
        ]));
        return $id;
    }

    public function updateProductSupply(int $id, array $data): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE product_supplies SET
                quantity = :quantity, yield_qty = :yield_qty, unit_measure = :unit_measure,
                waste_percent = :waste_percent, is_optional = :is_optional, notes = :notes, sort_order = :sort_order
             WHERE id = :id"
        );
        return $stmt->execute([
            ':id'            => $id,
            ':quantity'      => $data['quantity'] ?? 0,
            ':yield_qty'     => $data['yield_qty'] ?? 1,
            ':unit_measure'  => $data['unit_measure'] ?? 'un',
            ':waste_percent' => $data['waste_percent'] ?? 0,
            ':is_optional'   => $data['is_optional'] ?? 0,
            ':notes'         => $data['notes'] ?? null,
            ':sort_order'    => $data['sort_order'] ?? 0,
        ]);
    }

    public function removeProductSupply(int $id): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM product_supplies WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function calculateProductCost(int $productId): float
    {
        $stmt = $this->conn->prepare(
            "SELECT SUM((ps.quantity / ps.yield_qty) * (1 + ps.waste_percent / 100) * s.cost_price) AS total_cost
             FROM product_supplies ps
             JOIN supplies s ON s.id = ps.supply_id
             WHERE ps.product_id = :product_id AND ps.is_optional = 0"
        );
        $stmt->execute([':product_id' => $productId]);
        return (float) ($stmt->fetchColumn() ?: 0);
    }

    public function estimateConsumption(int $productId, float $qty): array
    {
        $supplies = $this->getProductSupplies($productId);
        $result = [];
        foreach ($supplies as $s) {
            $yieldQty = max((float)($s['yield_qty'] ?? 1), 0.0001);
            $perUnit = (float)$s['quantity'] / $yieldQty;
            $effective = $perUnit * (1 + $s['waste_percent'] / 100);
            $totalNeeded = $effective * $qty;

            $stockStmt = $this->conn->prepare(
                "SELECT COALESCE(SUM(quantity), 0) FROM supply_stock_items WHERE supply_id = :sid"
            );
            $stockStmt->execute([':sid' => $s['supply_id']]);
            $stockAvailable = (float) $stockStmt->fetchColumn();

            $result[] = [
                'supply_id'       => $s['supply_id'],
                'supply_name'     => $s['supply_name'],
                'supply_code'     => $s['supply_code'],
                'qty_per_unit'    => $effective,
                'total_needed'    => $totalNeeded,
                'stock_available' => $stockAvailable,
                'sufficient'      => $stockAvailable >= $totalNeeded,
            ];
        }
        return $result;
    }

    // ──── Where Used Impact (Fase 7) ────

    public function getAffectedProducts(int $supplyId): array
    {
        $stmt = $this->conn->prepare(
            "SELECT DISTINCT ps.product_id, p.name, p.code, p.price
             FROM product_supplies ps
             JOIN products p ON p.id = ps.product_id
             WHERE ps.supply_id = :supply_id"
        );
        $stmt->execute([':supply_id' => $supplyId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getWhereUsedImpact(int $supplyId, float $newCMP): array
    {
        $products = $this->getAffectedProducts($supplyId);
        $result = [];
        foreach ($products as $p) {
            $oldCost = $this->calculateProductCost($p['product_id']);

            $stmt = $this->conn->prepare(
                "SELECT ps.quantity, ps.yield_qty, ps.waste_percent, s.cost_price, s.id AS supply_id
                 FROM product_supplies ps
                 JOIN supplies s ON s.id = ps.supply_id
                 WHERE ps.product_id = :pid AND ps.is_optional = 0"
            );
            $stmt->execute([':pid' => $p['product_id']]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $newCost = 0;
            foreach ($items as $item) {
                $yieldQty = max((float)($item['yield_qty'] ?? 1), 0.0001);
                $price = ((int) $item['supply_id'] === $supplyId) ? $newCMP : $item['cost_price'];
                $newCost += ($item['quantity'] / $yieldQty) * (1 + $item['waste_percent'] / 100) * $price;
            }

            $price = (float) $p['price'];
            $result[] = [
                'product_id'  => $p['product_id'],
                'name'        => $p['name'],
                'code'        => $p['code'],
                'old_cost'    => $oldCost,
                'new_cost'    => $newCost,
                'variation'   => $newCost - $oldCost,
                'old_margin'  => $price > 0 ? (($price - $oldCost) / $price) * 100 : 0,
                'new_margin'  => $price > 0 ? (($price - $newCost) / $price) * 100 : 0,
            ];
        }
        return $result;
    }

    // ──── Search para Select2 ────

    public function searchSelect2(string $term, int $limit = 20): array
    {
        $stmt = $this->conn->prepare(
            "SELECT id, code, name, unit_measure, cost_price
             FROM supplies
             WHERE deleted_at IS NULL AND is_active = 1
               AND (name LIKE :term OR code LIKE :t2)
             ORDER BY name ASC
             LIMIT :limit"
        );
        $stmt->bindValue(':term', '%' . $term . '%');
        $stmt->bindValue(':t2', '%' . $term . '%');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $results = [];
        foreach ($rows as $r) {
            $results[] = [
                'id'   => (int) $r['id'],
                'text'  => $r['code'] . ' — ' . $r['name'] . ' (' . $r['unit_measure'] . ')',
                'unit'  => $r['unit_measure'],
                'cost'  => (float) $r['cost_price'],
            ];
        }
        return $results;
    }
}
