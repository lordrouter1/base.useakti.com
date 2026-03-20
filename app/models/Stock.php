<?php
namespace Akti\Models;

use Akti\Core\EventDispatcher;
use Akti\Core\Event;
use PDO;

/**
 * Stock Model
 * Gerencia armazéns, itens de estoque e movimentações.
 */
class Stock {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // ═══════════════════════════════════════════════
    //  WAREHOUSES (Armazéns)
    // ═══════════════════════════════════════════════

    /**
     * Conta o total de armazéns cadastrados
     */
    public function countWarehouses() {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM warehouses");
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function getAllWarehouses($onlyActive = true) {
        $where = $onlyActive ? "WHERE is_active = 1" : "";
        $stmt = $this->conn->prepare("
            SELECT w.*, 
                   (SELECT COUNT(*) FROM stock_items si WHERE si.warehouse_id = w.id) as total_items,
                   (SELECT COALESCE(SUM(si.quantity), 0) FROM stock_items si WHERE si.warehouse_id = w.id) as total_quantity
            FROM warehouses w 
            $where 
            ORDER BY w.name ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getWarehouse($id) {
        $stmt = $this->conn->prepare("SELECT * FROM warehouses WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createWarehouse($data) {
        // Se marcado como padrão, desmarcar os outros
        if (!empty($data['is_default'])) {
            $this->conn->exec("UPDATE warehouses SET is_default = 0");
        }
        $stmt = $this->conn->prepare("
            INSERT INTO warehouses (name, address, city, state, zip_code, phone, notes, is_default)
            VALUES (:name, :address, :city, :state, :zip_code, :phone, :notes, :is_default)
        ");
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':address', $data['address']);
        $stmt->bindParam(':city', $data['city']);
        $stmt->bindParam(':state', $data['state']);
        $stmt->bindParam(':zip_code', $data['zip_code']);
        $stmt->bindParam(':phone', $data['phone']);
        $stmt->bindParam(':notes', $data['notes']);
        $isDefault = !empty($data['is_default']) ? 1 : 0;
        $stmt->bindParam(':is_default', $isDefault, PDO::PARAM_INT);
        if ($stmt->execute()) {
            $newId = $this->conn->lastInsertId();
            EventDispatcher::dispatch('model.warehouse.created', new Event('model.warehouse.created', [
                'id' => $newId,
                'name' => $data['name'],
            ]));
            return $newId;
        }
        return false;
    }

    public function updateWarehouse($data) {
        // Se marcado como padrão, desmarcar os outros
        if (!empty($data['is_default'])) {
            $this->conn->exec("UPDATE warehouses SET is_default = 0");
        }
        $stmt = $this->conn->prepare("
            UPDATE warehouses SET 
                name = :name, address = :address, city = :city, state = :state,
                zip_code = :zip_code, phone = :phone, notes = :notes, is_active = :is_active,
                is_default = :is_default
            WHERE id = :id
        ");
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':address', $data['address']);
        $stmt->bindParam(':city', $data['city']);
        $stmt->bindParam(':state', $data['state']);
        $stmt->bindParam(':zip_code', $data['zip_code']);
        $stmt->bindParam(':phone', $data['phone']);
        $stmt->bindParam(':notes', $data['notes']);
        $stmt->bindParam(':is_active', $data['is_active']);
        $isDefault = !empty($data['is_default']) ? 1 : 0;
        $stmt->bindParam(':is_default', $isDefault, PDO::PARAM_INT);
        $stmt->bindParam(':id', $data['id']);
        $result = $stmt->execute();
        if ($result) {
            EventDispatcher::dispatch('model.warehouse.updated', new Event('model.warehouse.updated', [
                'id' => $data['id'],
                'name' => $data['name'],
            ]));
        }
        return $result;
    }

    public function deleteWarehouse($id) {
        $stmt = $this->conn->prepare("DELETE FROM warehouses WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $result = $stmt->execute();
        if ($result) {
            EventDispatcher::dispatch('model.warehouse.deleted', new Event('model.warehouse.deleted', ['id' => $id]));
        }
        return $result;
    }

    // ═══════════════════════════════════════════════
    //  STOCK ITEMS (Itens no estoque)
    // ═══════════════════════════════════════════════

    /**
     * Listar itens do estoque com filtros
     */
    public function getStockItems($warehouseId = null, $search = '', $lowStock = false) {
        $where = ["1=1"];
        $params = [];

        if ($warehouseId) {
            $where[] = "si.warehouse_id = :wid";
            $params[':wid'] = $warehouseId;
        }
        if ($search) {
            $where[] = "(p.name LIKE :search OR pgc.combination_label LIKE :search2 OR si.location_code LIKE :search3)";
            $params[':search'] = "%$search%";
            $params[':search2'] = "%$search%";
            $params[':search3'] = "%$search%";
        }
        if ($lowStock) {
            $where[] = "si.quantity <= si.min_quantity AND si.min_quantity > 0";
        }

        $whereStr = implode(' AND ', $where);

        $stmt = $this->conn->prepare("
            SELECT si.*, 
                   p.name as product_name, p.price as product_price,
                   pgc.combination_label, pgc.sku as combination_sku,
                   w.name as warehouse_name,
                   (SELECT image_path FROM product_images pi WHERE pi.product_id = p.id AND pi.is_main = 1 LIMIT 1) as product_image
            FROM stock_items si
            JOIN products p ON si.product_id = p.id
            JOIN warehouses w ON si.warehouse_id = w.id
            LEFT JOIN product_grade_combinations pgc ON si.combination_id = pgc.id
            WHERE $whereStr
            ORDER BY p.name ASC, pgc.combination_label ASC
        ");
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obter ou criar item de estoque
     */
    public function getOrCreateStockItem($warehouseId, $productId, $combinationId = null) {
        // Tenta buscar existente
        if ($combinationId) {
            $stmt = $this->conn->prepare("
                SELECT * FROM stock_items 
                WHERE warehouse_id = :wid AND product_id = :pid AND combination_id = :cid
            ");
            $stmt->bindParam(':cid', $combinationId);
        } else {
            $stmt = $this->conn->prepare("
                SELECT * FROM stock_items 
                WHERE warehouse_id = :wid AND product_id = :pid AND combination_id IS NULL
            ");
        }
        $stmt->bindParam(':wid', $warehouseId);
        $stmt->bindParam(':pid', $productId);
        $stmt->execute();
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($item) return $item;

        // Criar novo
        $stmt2 = $this->conn->prepare("
            INSERT INTO stock_items (warehouse_id, product_id, combination_id, quantity)
            VALUES (:wid, :pid, :cid, 0)
        ");
        $stmt2->bindParam(':wid', $warehouseId);
        $stmt2->bindParam(':pid', $productId);
        $stmt2->bindValue(':cid', $combinationId);
        $stmt2->execute();

        $newId = $this->conn->lastInsertId();
        $stmtGet = $this->conn->prepare("SELECT * FROM stock_items WHERE id = :id");
        $stmtGet->bindParam(':id', $newId);
        $stmtGet->execute();
        return $stmtGet->fetch(PDO::FETCH_ASSOC);
    }

    public function getStockItem($id) {
        $stmt = $this->conn->prepare("
            SELECT si.*, 
                   p.name as product_name, 
                   pgc.combination_label,
                   w.name as warehouse_name
            FROM stock_items si
            JOIN products p ON si.product_id = p.id
            JOIN warehouses w ON si.warehouse_id = w.id
            LEFT JOIN product_grade_combinations pgc ON si.combination_id = pgc.id
            WHERE si.id = :id
        ");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateStockItemMeta($id, $minQuantity, $locationCode) {
        $stmt = $this->conn->prepare("
            UPDATE stock_items SET min_quantity = :min_qty, location_code = :loc WHERE id = :id
        ");
        $stmt->bindParam(':min_qty', $minQuantity);
        $stmt->bindParam(':loc', $locationCode);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    // ═══════════════════════════════════════════════
    //  STOCK MOVEMENTS (Movimentações)
    // ═══════════════════════════════════════════════

    /**
     * Registrar movimentação de estoque
     */
    public function addMovement($data) {
        $stockItem = $this->getOrCreateStockItem(
            $data['warehouse_id'],
            $data['product_id'],
            $data['combination_id'] ?? null
        );

        $qtyBefore = (float) $stockItem['quantity'];
        $quantity = (float) $data['quantity'];
        $type = $data['type'];

        // Calcular novo saldo
        if ($type === 'entrada') {
            $qtyAfter = $qtyBefore + $quantity;
        } elseif ($type === 'saida') {
            $qtyAfter = $qtyBefore - $quantity;
            if ($qtyAfter < 0) $qtyAfter = 0;
        } elseif ($type === 'ajuste') {
            $qtyAfter = $quantity; // Ajuste define o saldo diretamente
            $quantity = abs($qtyAfter - $qtyBefore);
        } else {
            // transferencia: saída do armazém origem
            $qtyAfter = $qtyBefore - $quantity;
            if ($qtyAfter < 0) $qtyAfter = 0;
        }

        // Atualizar saldo do item
        $stmtUpd = $this->conn->prepare("UPDATE stock_items SET quantity = :qty WHERE id = :id");
        $stmtUpd->bindParam(':qty', $qtyAfter);
        $stmtUpd->bindParam(':id', $stockItem['id']);
        $stmtUpd->execute();

        // Registrar movimentação
        $userId = $_SESSION['user_id'] ?? null;
        $stmt = $this->conn->prepare("
            INSERT INTO stock_movements 
                (stock_item_id, warehouse_id, product_id, combination_id, type, quantity, 
                 quantity_before, quantity_after, reason, reference_type, reference_id, 
                 destination_warehouse_id, user_id)
            VALUES 
                (:sid, :wid, :pid, :cid, :type, :qty, :qty_before, :qty_after, :reason, 
                 :ref_type, :ref_id, :dest_wid, :uid)
        ");
        $stmt->bindParam(':sid', $stockItem['id']);
        $stmt->bindParam(':wid', $data['warehouse_id']);
        $stmt->bindParam(':pid', $data['product_id']);
        $stmt->bindValue(':cid', $data['combination_id'] ?? null);
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':qty', $quantity);
        $stmt->bindParam(':qty_before', $qtyBefore);
        $stmt->bindParam(':qty_after', $qtyAfter);
        $stmt->bindValue(':reason', $data['reason'] ?? null);
        $stmt->bindValue(':ref_type', $data['reference_type'] ?? 'manual');
        $stmt->bindValue(':ref_id', $data['reference_id'] ?? null);
        $stmt->bindValue(':dest_wid', $data['destination_warehouse_id'] ?? null);
        $stmt->bindParam(':uid', $userId);
        $stmt->execute();

        $movementId = $this->conn->lastInsertId();

        // Se for transferência, criar entrada no armazém destino
        if ($type === 'transferencia' && !empty($data['destination_warehouse_id'])) {
            $destItem = $this->getOrCreateStockItem(
                $data['destination_warehouse_id'],
                $data['product_id'],
                $data['combination_id'] ?? null
            );
            $destBefore = (float) $destItem['quantity'];
            $destAfter = $destBefore + (float) $data['quantity'];

            $stmtUpdDest = $this->conn->prepare("UPDATE stock_items SET quantity = :qty WHERE id = :id");
            $stmtUpdDest->bindParam(':qty', $destAfter);
            $stmtUpdDest->bindParam(':id', $destItem['id']);
            $stmtUpdDest->execute();

            $stmtDest = $this->conn->prepare("
                INSERT INTO stock_movements 
                    (stock_item_id, warehouse_id, product_id, combination_id, type, quantity,
                     quantity_before, quantity_after, reason, reference_type, reference_id, user_id)
                VALUES 
                    (:sid, :wid, :pid, :cid, 'entrada', :qty, :qty_before, :qty_after, :reason, 
                     'transfer', :ref_id, :uid)
            ");
            $stmtDest->bindParam(':sid', $destItem['id']);
            $stmtDest->bindParam(':wid', $data['destination_warehouse_id']);
            $stmtDest->bindParam(':pid', $data['product_id']);
            $stmtDest->bindValue(':cid', $data['combination_id'] ?? null);
            $stmtDest->bindValue(':qty', $data['quantity']);
            $stmtDest->bindParam(':qty_before', $destBefore);
            $stmtDest->bindParam(':qty_after', $destAfter);
            $stmtDest->bindValue(':reason', 'Transferência do armazém: ' . ($data['warehouse_id']));
            $stmtDest->bindParam(':ref_id', $movementId);
            $stmtDest->bindParam(':uid', $userId);
            $stmtDest->execute();
        }

        return $movementId;
    }

    /**
     * Buscar uma movimentação pelo ID
     */
    public function getMovement($id) {
        $stmt = $this->conn->prepare("
            SELECT sm.*, 
                   p.name as product_name,
                   pgc.combination_label,
                   w.name as warehouse_name,
                   dw.name as dest_warehouse_name,
                   u.name as user_name
            FROM stock_movements sm
            JOIN products p ON sm.product_id = p.id
            JOIN warehouses w ON sm.warehouse_id = w.id
            LEFT JOIN product_grade_combinations pgc ON sm.combination_id = pgc.id
            LEFT JOIN warehouses dw ON sm.destination_warehouse_id = dw.id
            LEFT JOIN users u ON sm.user_id = u.id
            WHERE sm.id = :id
        ");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Atualizar uma movimentação e recalcular saldo do stock_item
     */
    public function updateMovement($id, $data) {
        $movement = $this->getMovement($id);
        if (!$movement) return false;

        // Reverter efeito da movimentação original no saldo
        $stockItem = $this->getOrCreateStockItem(
            $movement['warehouse_id'],
            $movement['product_id'],
            $movement['combination_id']
        );
        $currentQty = (float) $stockItem['quantity'];

        // Reverter: desfazer o efeito original
        if ($movement['type'] === 'entrada') {
            $currentQty -= (float) $movement['quantity'];
        } elseif ($movement['type'] === 'saida') {
            $currentQty += (float) $movement['quantity'];
        } elseif ($movement['type'] === 'ajuste') {
            $currentQty = (float) $movement['quantity_before'];
        }

        // Aplicar novo efeito
        $newType = $data['type'] ?? $movement['type'];
        $newQuantity = (float) ($data['quantity'] ?? $movement['quantity']);

        if ($newType === 'entrada') {
            $newQtyAfter = $currentQty + $newQuantity;
        } elseif ($newType === 'saida') {
            $newQtyAfter = $currentQty - $newQuantity;
            if ($newQtyAfter < 0) $newQtyAfter = 0;
        } elseif ($newType === 'ajuste') {
            $newQtyAfter = $newQuantity;
            $newQuantity = abs($newQtyAfter - $currentQty);
        } else {
            $newQtyAfter = $currentQty - $newQuantity;
            if ($newQtyAfter < 0) $newQtyAfter = 0;
        }

        // Atualizar saldo do item
        $stmtUpd = $this->conn->prepare("UPDATE stock_items SET quantity = :qty WHERE id = :id");
        $stmtUpd->bindParam(':qty', $newQtyAfter);
        $stmtUpd->bindParam(':id', $stockItem['id']);
        $stmtUpd->execute();

        // Atualizar registro da movimentação
        $reason = $data['reason'] ?? $movement['reason'];
        $stmt = $this->conn->prepare("
            UPDATE stock_movements 
            SET type = :type, quantity = :qty, quantity_before = :qty_before, 
                quantity_after = :qty_after, reason = :reason
            WHERE id = :id
        ");
        $stmt->bindParam(':type', $newType);
        $stmt->bindParam(':qty', $newQuantity);
        $stmt->bindParam(':qty_before', $currentQty);
        $stmt->bindParam(':qty_after', $newQtyAfter);
        $stmt->bindParam(':reason', $reason);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Excluir uma movimentação e reverter o saldo do stock_item
     */
    public function deleteMovement($id) {
        $movement = $this->getMovement($id);
        if (!$movement) return false;

        // Reverter efeito no saldo
        $stockItem = $this->getOrCreateStockItem(
            $movement['warehouse_id'],
            $movement['product_id'],
            $movement['combination_id']
        );
        $currentQty = (float) $stockItem['quantity'];

        if ($movement['type'] === 'entrada') {
            $newQty = $currentQty - (float) $movement['quantity'];
            if ($newQty < 0) $newQty = 0;
        } elseif ($movement['type'] === 'saida') {
            $newQty = $currentQty + (float) $movement['quantity'];
        } elseif ($movement['type'] === 'ajuste') {
            $newQty = (float) $movement['quantity_before'];
        } else {
            // transferência: reverter saída no armazém de origem
            $newQty = $currentQty + (float) $movement['quantity'];
        }

        // Atualizar saldo
        $stmtUpd = $this->conn->prepare("UPDATE stock_items SET quantity = :qty WHERE id = :id");
        $stmtUpd->bindParam(':qty', $newQty);
        $stmtUpd->bindParam(':id', $stockItem['id']);
        $stmtUpd->execute();

        // Se transferência, reverter também no destino
        if ($movement['type'] === 'transferencia' && !empty($movement['destination_warehouse_id'])) {
            $destItem = $this->getOrCreateStockItem(
                $movement['destination_warehouse_id'],
                $movement['product_id'],
                $movement['combination_id']
            );
            $destQty = (float) $destItem['quantity'] - (float) $movement['quantity'];
            if ($destQty < 0) $destQty = 0;

            $stmtDest = $this->conn->prepare("UPDATE stock_items SET quantity = :qty WHERE id = :id");
            $stmtDest->bindParam(':qty', $destQty);
            $stmtDest->bindParam(':id', $destItem['id']);
            $stmtDest->execute();

            // Remover a movimentação de entrada no destino (reference_id = $id)
            $stmtDelDest = $this->conn->prepare("DELETE FROM stock_movements WHERE reference_id = :rid AND reference_type = 'transfer'");
            $stmtDelDest->bindParam(':rid', $id, PDO::PARAM_INT);
            $stmtDelDest->execute();
        }

        // Remover a movimentação
        $stmt = $this->conn->prepare("DELETE FROM stock_movements WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Listar movimentações com filtros
     */
    public function getMovements($filters = []) {
        $where = ["1=1"];
        $params = [];

        if (!empty($filters['warehouse_id'])) {
            $where[] = "sm.warehouse_id = :wid";
            $params[':wid'] = $filters['warehouse_id'];
        }
        if (!empty($filters['product_id'])) {
            $where[] = "sm.product_id = :pid";
            $params[':pid'] = $filters['product_id'];
        }
        if (!empty($filters['type'])) {
            $where[] = "sm.type = :type";
            $params[':type'] = $filters['type'];
        }
        if (!empty($filters['date_from'])) {
            $where[] = "sm.created_at >= :date_from";
            $params[':date_from'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[] = "sm.created_at <= :date_to";
            $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
        }

        $whereStr = implode(' AND ', $where);
        $limit = !empty($filters['limit']) ? "LIMIT " . intval($filters['limit']) : "LIMIT 200";

        $stmt = $this->conn->prepare("
            SELECT sm.*, 
                   p.name as product_name,
                   pgc.combination_label,
                   w.name as warehouse_name,
                   dw.name as dest_warehouse_name,
                   u.name as user_name
            FROM stock_movements sm
            JOIN products p ON sm.product_id = p.id
            JOIN warehouses w ON sm.warehouse_id = w.id
            LEFT JOIN product_grade_combinations pgc ON sm.combination_id = pgc.id
            LEFT JOIN warehouses dw ON sm.destination_warehouse_id = dw.id
            LEFT JOIN users u ON sm.user_id = u.id
            WHERE $whereStr
            ORDER BY sm.created_at DESC
            $limit
        ");
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ═══════════════════════════════════════════════
    //  DASHBOARD / RESUMOS
    // ═══════════════════════════════════════════════

    /**
     * Resumo geral do estoque
     */
    public function getDashboardSummary() {
        $summary = [];

        // Total de armazéns
        $stmt = $this->conn->query("SELECT COUNT(*) FROM warehouses WHERE is_active = 1");
        $summary['total_warehouses'] = $stmt->fetchColumn();

        // Total de itens cadastrados
        $stmt = $this->conn->query("SELECT COUNT(*) FROM stock_items");
        $summary['total_items'] = $stmt->fetchColumn();

        // Total de produtos distintos
        $stmt = $this->conn->query("SELECT COUNT(DISTINCT product_id) FROM stock_items WHERE quantity > 0");
        $summary['products_in_stock'] = $stmt->fetchColumn();

        // Valor total do estoque
        $stmt = $this->conn->query("
            SELECT COALESCE(SUM(si.quantity * p.price), 0) 
            FROM stock_items si 
            JOIN products p ON si.product_id = p.id
        ");
        $summary['total_value'] = $stmt->fetchColumn();

        // Itens abaixo do estoque mínimo
        $stmt = $this->conn->query("
            SELECT COUNT(*) FROM stock_items 
            WHERE min_quantity > 0 AND quantity <= min_quantity
        ");
        $summary['low_stock_count'] = $stmt->fetchColumn();

        // Movimentações hoje
        $stmt = $this->conn->query("
            SELECT COUNT(*) FROM stock_movements 
            WHERE DATE(created_at) = CURDATE()
        ");
        $summary['movements_today'] = $stmt->fetchColumn();

        return $summary;
    }

    /**
     * Itens com estoque baixo
     */
    public function getLowStockItems($limit = 10) {
        $stmt = $this->conn->prepare("
            SELECT si.*, p.name as product_name, pgc.combination_label, w.name as warehouse_name,
                   (SELECT image_path FROM product_images pi WHERE pi.product_id = p.id AND pi.is_main = 1 LIMIT 1) as product_image
            FROM stock_items si
            JOIN products p ON si.product_id = p.id
            JOIN warehouses w ON si.warehouse_id = w.id
            LEFT JOIN product_grade_combinations pgc ON si.combination_id = pgc.id
            WHERE si.min_quantity > 0 AND si.quantity <= si.min_quantity
            ORDER BY (si.quantity / si.min_quantity) ASC
            LIMIT :lim
        ");
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar produtos com suas variações para seleção
     */
    public function getProductsForSelection() {
        $stmt = $this->conn->prepare("
            SELECT p.id, p.name, p.price, p.stock_quantity,
                   c.name as category_name,
                   (SELECT image_path FROM product_images pi WHERE pi.product_id = p.id AND pi.is_main = 1 LIMIT 1) as product_image
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            ORDER BY p.name ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar combinações (variações) de um produto
     */
    public function getProductCombinations($productId) {
        $stmt = $this->conn->prepare("
            SELECT id, combination_label, sku, price_override, is_active
            FROM product_grade_combinations
            WHERE product_id = :pid AND is_active = 1
            ORDER BY combination_label ASC
        ");
        $stmt->bindParam(':pid', $productId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ═══════════════════════════════════════════════
    //  ARMAZÉM PADRÃO
    // ═══════════════════════════════════════════════

    /**
     * Retorna o armazém padrão (is_default = 1)
     */
    public function getDefaultWarehouse() {
        $stmt = $this->conn->prepare("SELECT * FROM warehouses WHERE is_default = 1 AND is_active = 1 LIMIT 1");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Define um armazém como padrão (e desmarca os demais)
     */
    public function setDefaultWarehouse($id) {
        $this->conn->exec("UPDATE warehouses SET is_default = 0");
        $stmt = $this->conn->prepare("UPDATE warehouses SET is_default = 1 WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Retorna o saldo de estoque de um produto/combinação em um armazém
     */
    public function getProductStockInWarehouse($warehouseId, $productId, $combinationId = null) {
        if ($combinationId) {
            $stmt = $this->conn->prepare("SELECT COALESCE(quantity, 0) FROM stock_items WHERE warehouse_id = :wid AND product_id = :pid AND combination_id = :cid");
            $stmt->bindParam(':cid', $combinationId, PDO::PARAM_INT);
        } else {
            $stmt = $this->conn->prepare("SELECT COALESCE(quantity, 0) FROM stock_items WHERE warehouse_id = :wid AND product_id = :pid AND combination_id IS NULL");
        }
        $stmt->bindParam(':wid', $warehouseId, PDO::PARAM_INT);
        $stmt->bindParam(':pid', $productId, PDO::PARAM_INT);
        $stmt->execute();
        return (float) $stmt->fetchColumn();
    }

    // ═══════════════════════════════════════════════
    //  DEDUÇÕES DE ESTOQUE DE PEDIDOS
    // ═══════════════════════════════════════════════

    /**
     * Registra uma dedução de estoque de um pedido (ao mover para preparação)
     */
    public function addStockDeduction($data) {
        $stmt = $this->conn->prepare("
            INSERT INTO order_stock_deductions 
                (order_id, order_item_id, warehouse_id, product_id, combination_id, quantity, movement_id)
            VALUES (:oid, :iid, :wid, :pid, :cid, :qty, :mid)
        ");
        $stmt->bindParam(':oid', $data['order_id'], PDO::PARAM_INT);
        $stmt->bindParam(':iid', $data['order_item_id'], PDO::PARAM_INT);
        $stmt->bindParam(':wid', $data['warehouse_id'], PDO::PARAM_INT);
        $stmt->bindParam(':pid', $data['product_id'], PDO::PARAM_INT);
        $stmt->bindValue(':cid', $data['combination_id'] ?? null);
        $stmt->bindParam(':qty', $data['quantity']);
        $stmt->bindValue(':mid', $data['movement_id'] ?? null);
        return $stmt->execute();
    }

    /**
     * Busca deduções ativas (não revertidas) de um pedido
     */
    public function getActiveDeductions($orderId) {
        $stmt = $this->conn->prepare("
            SELECT osd.*, p.name as product_name, pgc.combination_label, w.name as warehouse_name
            FROM order_stock_deductions osd
            JOIN products p ON osd.product_id = p.id
            JOIN warehouses w ON osd.warehouse_id = w.id
            LEFT JOIN product_grade_combinations pgc ON osd.combination_id = pgc.id
            WHERE osd.order_id = :oid AND osd.status = 'deducted'
        ");
        $stmt->bindParam(':oid', $orderId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Reverte todas as deduções ativas de um pedido (devolver estoque)
     */
    public function reverseDeductions($orderId, $userId = null) {
        $deductions = $this->getActiveDeductions($orderId);
        if (empty($deductions)) return 0;

        $reversed = 0;
        foreach ($deductions as $ded) {
            // Devolver estoque ao armazém
            $this->addMovement([
                'warehouse_id'  => $ded['warehouse_id'],
                'product_id'    => $ded['product_id'],
                'combination_id'=> $ded['combination_id'],
                'type'          => 'entrada',
                'quantity'      => $ded['quantity'],
                'reason'        => 'Devolução automática — Pedido #' . $orderId . ' retornou de preparação',
                'reference_type'=> 'order_reversal',
                'reference_id'  => $orderId,
            ]);

            // Marcar dedução como revertida
            $stmt = $this->conn->prepare("
                UPDATE order_stock_deductions 
                SET status = 'reversed', reversed_at = NOW(), reversed_by = :uid
                WHERE id = :id
            ");
            $stmt->bindParam(':uid', $userId);
            $stmt->bindParam(':id', $ded['id'], PDO::PARAM_INT);
            $stmt->execute();
            $reversed++;
        }
        return $reversed;
    }

    /**
     * Cria a tabela de deduções se não existir (auto-migrate)
     */
    public function ensureDeductionsTable() {
        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS `order_stock_deductions` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `order_id` INT(11) NOT NULL,
                `order_item_id` INT(11) NOT NULL,
                `warehouse_id` INT(11) NOT NULL,
                `product_id` INT(11) NOT NULL,
                `combination_id` INT(11) DEFAULT NULL,
                `quantity` DECIMAL(12,2) NOT NULL,
                `movement_id` INT(11) DEFAULT NULL,
                `status` ENUM('deducted','reversed') NOT NULL DEFAULT 'deducted',
                `deducted_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `reversed_at` DATETIME DEFAULT NULL,
                `reversed_by` INT(11) DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_order` (`order_id`),
                KEY `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    /**
     * Garante que a coluna is_default exista na tabela warehouses (auto-migrate)
     */
    public function ensureDefaultColumn() {
        try {
            $this->conn->query("SELECT is_default FROM warehouses LIMIT 1");
        } catch (\Exception $e) {
            $this->conn->exec("ALTER TABLE warehouses ADD COLUMN is_default TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active");
        }
    }

    /**
     * Garante que a coluna stock_warehouse_id exista na tabela orders (auto-migrate)
     */
    public function ensureOrderWarehouseColumn() {
        try {
            $this->conn->query("SELECT stock_warehouse_id FROM orders LIMIT 1");
        } catch (\Exception $e) {
            $this->conn->exec("ALTER TABLE orders ADD COLUMN stock_warehouse_id INT(11) DEFAULT NULL AFTER tracking_code");
        }
    }
}
