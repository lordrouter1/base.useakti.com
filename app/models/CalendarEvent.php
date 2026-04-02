<?php

namespace Akti\Models;

use PDO;

class CalendarEvent
{
    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function readByRange(string $start, string $end, ?int $userId = null): array
    {
        $where = 'WHERE ce.deleted_at IS NULL AND ce.start_date >= :start AND ce.start_date <= :end';
        $params = [':start' => $start, ':end' => $end];
        if ($userId) {
            $where .= ' AND (ce.user_id = :user_id OR ce.user_id IS NULL)';
            $params[':user_id'] = $userId;
        }

        $stmt = $this->conn->prepare(
            "SELECT ce.*, u.name as user_name
             FROM calendar_events ce
             LEFT JOIN users u ON u.id = ce.user_id
             {$where}
             ORDER BY ce.start_date ASC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function readOne(int $id): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM calendar_events WHERE id = :id AND deleted_at IS NULL");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO calendar_events (tenant_id, user_id, title, description, type, entity_type, entity_id, start_date, end_date, all_day, color, reminder_minutes)
             VALUES (:tenant_id, :user_id, :title, :description, :type, :entity_type, :entity_id, :start_date, :end_date, :all_day, :color, :reminder_minutes)"
        );
        $stmt->execute([
            ':tenant_id'        => $data['tenant_id'],
            ':user_id'          => $data['user_id'] ?? null,
            ':title'            => $data['title'],
            ':description'      => $data['description'] ?? null,
            ':type'             => $data['type'] ?? 'manual',
            ':entity_type'      => $data['entity_type'] ?? null,
            ':entity_id'        => $data['entity_id'] ?? null,
            ':start_date'       => $data['start_date'],
            ':end_date'         => $data['end_date'] ?? null,
            ':all_day'          => $data['all_day'] ?? 0,
            ':color'            => $data['color'] ?? null,
            ':reminder_minutes' => $data['reminder_minutes'] ?? null,
        ]);
        return (int) $this->conn->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE calendar_events SET
                title = :title, description = :description, type = :type,
                start_date = :start_date, end_date = :end_date, all_day = :all_day,
                color = :color, reminder_minutes = :reminder_minutes, completed = :completed
             WHERE id = :id"
        );
        return $stmt->execute([
            ':id'               => $id,
            ':title'            => $data['title'],
            ':description'      => $data['description'] ?? null,
            ':type'             => $data['type'] ?? 'manual',
            ':start_date'       => $data['start_date'],
            ':end_date'         => $data['end_date'] ?? null,
            ':all_day'          => $data['all_day'] ?? 0,
            ':color'            => $data['color'] ?? null,
            ':reminder_minutes' => $data['reminder_minutes'] ?? null,
            ':completed'        => $data['completed'] ?? 0,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->conn->prepare("UPDATE calendar_events SET deleted_at = NOW() WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function getUpcoming(int $userId, int $limit = 10): array
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM calendar_events
             WHERE deleted_at IS NULL AND completed = 0
               AND (user_id = :user_id OR user_id IS NULL)
               AND start_date >= NOW()
             ORDER BY start_date ASC LIMIT :limit"
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function syncFromOrders(int $tenantId): int
    {
        $stmt = $this->conn->prepare(
            "INSERT IGNORE INTO calendar_events (tenant_id, title, type, entity_type, entity_id, start_date, all_day, color)
             SELECT :tenant_id, CONCAT('Entrega: Pedido #', o.id), 'delivery', 'order', o.id, o.delivery_date, 1, '#28a745'
             FROM orders o
             WHERE o.delivery_date IS NOT NULL AND o.delivery_date >= CURDATE()
               AND o.id NOT IN (SELECT entity_id FROM calendar_events WHERE entity_type = 'order' AND type = 'delivery' AND deleted_at IS NULL)"
        );
        $stmt->execute([':tenant_id' => $tenantId]);
        return $stmt->rowCount();
    }

    public function syncFromInstallments(int $tenantId): int
    {
        $stmt = $this->conn->prepare(
            "INSERT IGNORE INTO calendar_events (tenant_id, title, type, entity_type, entity_id, start_date, all_day, color)
             SELECT :tenant_id, CONCAT('Vencimento: Parcela #', i.id), 'due_date', 'installment', i.id, i.due_date, 1, '#dc3545'
             FROM order_installments i
             WHERE i.status = 'pending' AND i.due_date >= CURDATE()
               AND i.id NOT IN (SELECT entity_id FROM calendar_events WHERE entity_type = 'installment' AND type = 'due_date' AND deleted_at IS NULL)"
        );
        $stmt->execute([':tenant_id' => $tenantId]);
        return $stmt->rowCount();
    }
}
