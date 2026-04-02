<?php

namespace Akti\Models;

use PDO;
use Akti\Core\EventDispatcher;
use Akti\Core\Event;

class Quote
{
    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function readPaginated(int $page = 1, int $perPage = 15, array $filters = []): array
    {
        $where = ' WHERE q.deleted_at IS NULL';
        $params = [];
        if (!empty($filters['status'])) {
            $where .= ' AND q.status = :status';
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['customer_id'])) {
            $where .= ' AND q.customer_id = :customer_id';
            $params[':customer_id'] = $filters['customer_id'];
        }
        if (!empty($filters['search'])) {
            $where .= ' AND (q.code LIKE :search OR c.name LIKE :s2)';
            $params[':search'] = '%' . $filters['search'] . '%';
            $params[':s2'] = '%' . $filters['search'] . '%';
        }

        $countStmt = $this->conn->prepare(
            "SELECT COUNT(*) FROM quotes q LEFT JOIN customers c ON c.id = q.customer_id" . $where
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $sql = "SELECT q.*, c.name as customer_name, u.name as seller_name
                FROM quotes q
                LEFT JOIN customers c ON c.id = q.customer_id
                LEFT JOIN users u ON u.id = q.user_id
                {$where}
                ORDER BY q.created_at DESC LIMIT :limit OFFSET :offset";
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
            "SELECT q.*, c.name as customer_name, c.email as customer_email
             FROM quotes q
             LEFT JOIN customers c ON c.id = q.customer_id
             WHERE q.id = :id AND q.deleted_at IS NULL"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function readByToken(string $token): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT q.*, c.name as customer_name
             FROM quotes q
             LEFT JOIN customers c ON c.id = q.customer_id
             WHERE q.approval_token = :token AND q.deleted_at IS NULL"
        );
        $stmt->execute([':token' => $token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $token = bin2hex(random_bytes(32));
        $stmt = $this->conn->prepare(
            "INSERT INTO quotes (tenant_id, customer_id, user_id, code, status, valid_until, subtotal, discount, total, notes, internal_notes, approval_token)
             VALUES (:tenant_id, :customer_id, :user_id, :code, :status, :valid_until, :subtotal, :discount, :total, :notes, :internal_notes, :token)"
        );
        $stmt->execute([
            ':tenant_id'      => $data['tenant_id'],
            ':customer_id'    => $data['customer_id'],
            ':user_id'        => $data['user_id'] ?? null,
            ':code'           => $data['code'] ?? null,
            ':status'         => $data['status'] ?? 'draft',
            ':valid_until'    => $data['valid_until'] ?? null,
            ':subtotal'       => $data['subtotal'] ?? 0,
            ':discount'       => $data['discount'] ?? 0,
            ':total'          => $data['total'] ?? 0,
            ':notes'          => $data['notes'] ?? null,
            ':internal_notes' => $data['internal_notes'] ?? null,
            ':token'          => $token,
        ]);
        $id = (int) $this->conn->lastInsertId();
        EventDispatcher::dispatch('model.quote.created', new Event('model.quote.created', ['id' => $id]));
        return $id;
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE quotes SET
                customer_id = :customer_id, code = :code, status = :status,
                valid_until = :valid_until, subtotal = :subtotal, discount = :discount,
                total = :total, notes = :notes, internal_notes = :internal_notes
             WHERE id = :id"
        );
        $result = $stmt->execute([
            ':id'             => $id,
            ':customer_id'    => $data['customer_id'],
            ':code'           => $data['code'] ?? null,
            ':status'         => $data['status'] ?? 'draft',
            ':valid_until'    => $data['valid_until'] ?? null,
            ':subtotal'       => $data['subtotal'] ?? 0,
            ':discount'       => $data['discount'] ?? 0,
            ':total'          => $data['total'] ?? 0,
            ':notes'          => $data['notes'] ?? null,
            ':internal_notes' => $data['internal_notes'] ?? null,
        ]);
        EventDispatcher::dispatch('model.quote.updated', new Event('model.quote.updated', ['id' => $id]));
        return $result;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->conn->prepare("UPDATE quotes SET deleted_at = NOW() WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function approve(int $id): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE quotes SET status = 'approved', approved_at = NOW() WHERE id = :id"
        );
        return $stmt->execute([':id' => $id]);
    }

    public function convertToOrder(int $id, int $orderId): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE quotes SET status = 'converted', converted_order_id = :order_id WHERE id = :id"
        );
        return $stmt->execute([':id' => $id, ':order_id' => $orderId]);
    }

    public function getItems(int $quoteId): array
    {
        $stmt = $this->conn->prepare(
            "SELECT qi.*, p.name as product_name
             FROM quote_items qi
             LEFT JOIN products p ON p.id = qi.product_id
             WHERE qi.quote_id = :quote_id ORDER BY qi.id ASC"
        );
        $stmt->execute([':quote_id' => $quoteId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addItem(array $data): int
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO quote_items (tenant_id, quote_id, product_id, description, quantity, unit_price, discount, total)
             VALUES (:tenant_id, :quote_id, :product_id, :description, :quantity, :unit_price, :discount, :total)"
        );
        $stmt->execute([
            ':tenant_id'  => $data['tenant_id'],
            ':quote_id'   => $data['quote_id'],
            ':product_id' => $data['product_id'] ?? null,
            ':description' => $data['description'],
            ':quantity'   => $data['quantity'] ?? 1,
            ':unit_price' => $data['unit_price'] ?? 0,
            ':discount'   => $data['discount'] ?? 0,
            ':total'      => (($data['quantity'] ?? 1) * ($data['unit_price'] ?? 0)) - ($data['discount'] ?? 0),
        ]);
        return (int) $this->conn->lastInsertId();
    }

    public function removeItem(int $itemId): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM quote_items WHERE id = :id");
        return $stmt->execute([':id' => $itemId]);
    }

    public function saveVersion(int $quoteId, int $version, array $snapshot, ?int $userId = null): int
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO quote_versions (tenant_id, quote_id, version, snapshot, created_by)
             VALUES (:tenant_id, :quote_id, :version, :snapshot, :created_by)"
        );
        $quote = $this->readOne($quoteId);
        $stmt->execute([
            ':tenant_id'  => $quote['tenant_id'] ?? 0,
            ':quote_id'   => $quoteId,
            ':version'    => $version,
            ':snapshot'   => json_encode($snapshot),
            ':created_by' => $userId,
        ]);
        return (int) $this->conn->lastInsertId();
    }

    public function getVersions(int $quoteId): array
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM quote_versions WHERE quote_id = :quote_id ORDER BY version DESC"
        );
        $stmt->execute([':quote_id' => $quoteId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSummary(): array
    {
        $stmt = $this->conn->prepare(
            "SELECT status, COUNT(*) as count, SUM(total) as total_value
             FROM quotes WHERE deleted_at IS NULL GROUP BY status"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
