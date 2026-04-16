<?php

namespace Akti\Models;

use PDO;

/**
 * Model de ordens de compra de insumos.
 */
class PurchaseOrder
{
    private PDO $conn;

    /**
     * Construtor da classe PurchaseOrder.
     *
     * @param PDO $db Conexão PDO com o banco de dados
     */
    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * Read paginated.
     *
     * @param int $page Número da página
     * @param int $perPage Registros por página
     * @param array $filters Filtros aplicados
     * @return array
     */
    public function readPaginated(int $page = 1, int $perPage = 15, array $filters = []): array
    {
        $where = ' WHERE po.deleted_at IS NULL';
        $params = [];
        if (!empty($filters['status'])) {
            $where .= ' AND po.status = :status';
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['supplier_id'])) {
            $where .= ' AND po.supplier_id = :supplier_id';
            $params[':supplier_id'] = $filters['supplier_id'];
        }
        if (!empty($filters['search'])) {
            $where .= ' AND (po.code LIKE :search OR s.company_name LIKE :s2)';
            $params[':search'] = '%' . $filters['search'] . '%';
            $params[':s2'] = '%' . $filters['search'] . '%';
        }

        $countStmt = $this->conn->prepare(
            "SELECT COUNT(*) FROM purchase_orders po LEFT JOIN suppliers s ON s.id = po.supplier_id" . $where
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $sql = "SELECT po.*, s.company_name as supplier_name, u.name as creator_name
                FROM purchase_orders po
                LEFT JOIN suppliers s ON s.id = po.supplier_id
                LEFT JOIN users u ON u.id = po.user_id
                {$where}
                ORDER BY po.created_at DESC LIMIT :limit OFFSET :offset";
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
  * Read one.
  *
  * @param int $id ID do registro
  * @return array|null
  */
    public function readOne(int $id): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT po.*, s.company_name as supplier_name
             FROM purchase_orders po
             LEFT JOIN suppliers s ON s.id = po.supplier_id
             WHERE po.id = :id AND po.deleted_at IS NULL"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

 /**
  * Create.
  *
  * @param array $data Dados para processamento
  * @return int
  */
    public function create(array $data): int
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO purchase_orders (tenant_id, supplier_id, user_id, code, status, expected_date, payment_terms, notes)
             VALUES (:tenant_id, :supplier_id, :user_id, :code, :status, :expected_date, :payment_terms, :notes)"
        );
        $stmt->execute([
            ':tenant_id'     => $data['tenant_id'],
            ':supplier_id'   => $data['supplier_id'],
            ':user_id'       => $data['user_id'] ?? null,
            ':code'          => $data['code'] ?? null,
            ':status'        => $data['status'] ?? 'draft',
            ':expected_date' => $data['expected_date'] ?? null,
            ':payment_terms' => $data['payment_terms'] ?? null,
            ':notes'         => $data['notes'] ?? null,
        ]);
        return (int) $this->conn->lastInsertId();
    }

 /**
  * Update.
  *
  * @param int $id ID do registro
  * @param array $data Dados para processamento
  * @return bool
  */
    public function update(int $id, array $data): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE purchase_orders SET
                supplier_id = :supplier_id, code = :code, status = :status,
                expected_date = :expected_date, payment_terms = :payment_terms, notes = :notes,
                subtotal = :subtotal, discount = :discount, shipping = :shipping, total = :total
             WHERE id = :id"
        );
        return $stmt->execute([
            ':id'            => $id,
            ':supplier_id'   => $data['supplier_id'],
            ':code'          => $data['code'] ?? null,
            ':status'        => $data['status'] ?? 'draft',
            ':expected_date' => $data['expected_date'] ?? null,
            ':payment_terms' => $data['payment_terms'] ?? null,
            ':notes'         => $data['notes'] ?? null,
            ':subtotal'      => $data['subtotal'] ?? 0,
            ':discount'      => $data['discount'] ?? 0,
            ':shipping'      => $data['shipping'] ?? 0,
            ':total'         => $data['total'] ?? 0,
        ]);
    }

 /**
  * Delete.
  *
  * @param int $id ID do registro
  * @return bool
  */
    public function delete(int $id): bool
    {
        $stmt = $this->conn->prepare("UPDATE purchase_orders SET deleted_at = NOW() WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

 /**
  * Update totals.
  *
  * @param int $id ID do registro
  * @return bool
  */
    public function updateTotals(int $id): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE purchase_orders SET
                subtotal = (SELECT COALESCE(SUM(total), 0) FROM purchase_items WHERE purchase_order_id = :id1),
                total = subtotal - discount + shipping
             WHERE id = :id2"
        );
        return $stmt->execute([':id1' => $id, ':id2' => $id]);
    }

 /**
  * Get items.
  *
  * @param int $orderId ID do pedido
  * @return array
  */
    public function getItems(int $orderId): array
    {
        $stmt = $this->conn->prepare(
            "SELECT pi.*, p.name as product_name
             FROM purchase_items pi
             LEFT JOIN products p ON p.id = pi.product_id
             WHERE pi.purchase_order_id = :order_id
             ORDER BY pi.id ASC"
        );
        $stmt->execute([':order_id' => $orderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

 /**
  * Add item.
  *
  * @param array $data Dados para processamento
  * @return int
  */
    public function addItem(array $data): int
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO purchase_items (tenant_id, purchase_order_id, product_id, description, quantity, unit_price, total)
             VALUES (:tenant_id, :purchase_order_id, :product_id, :description, :quantity, :unit_price, :total)"
        );
        $stmt->execute([
            ':tenant_id'         => $data['tenant_id'],
            ':purchase_order_id' => $data['purchase_order_id'],
            ':product_id'        => $data['product_id'] ?? null,
            ':description'       => $data['description'],
            ':quantity'          => $data['quantity'] ?? 1,
            ':unit_price'        => $data['unit_price'] ?? 0,
            ':total'             => ($data['quantity'] ?? 1) * ($data['unit_price'] ?? 0),
        ]);
        return (int) $this->conn->lastInsertId();
    }

 /**
  * Remove item.
  *
  * @param int $itemId Item id
  * @return bool
  */
    public function removeItem(int $itemId): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM purchase_items WHERE id = :id");
        return $stmt->execute([':id' => $itemId]);
    }

 /**
  * Receive.
  *
  * @param int $id ID do registro
  * @return bool
  */
    public function receive(int $id): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE purchase_orders SET status = 'received', received_date = CURDATE() WHERE id = :id"
        );
        return $stmt->execute([':id' => $id]);
    }
}
