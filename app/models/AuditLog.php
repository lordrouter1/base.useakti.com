<?php

namespace Akti\Models;

use PDO;

class AuditLog
{
    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function log(array $data): int
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO audit_logs (tenant_id, user_id, user_name, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent, description)
             VALUES (:tenant_id, :user_id, :user_name, :action, :entity_type, :entity_id, :old_values, :new_values, :ip_address, :user_agent, :description)"
        );
        $stmt->execute([
            ':tenant_id'   => $data['tenant_id'] ?? ($_SESSION['tenant_id'] ?? 0),
            ':user_id'     => $data['user_id'] ?? ($_SESSION['user_id'] ?? null),
            ':user_name'   => $data['user_name'] ?? ($_SESSION['user_name'] ?? null),
            ':action'      => $data['action'],
            ':entity_type' => $data['entity_type'],
            ':entity_id'   => $data['entity_id'] ?? null,
            ':old_values'  => isset($data['old_values']) ? json_encode($data['old_values']) : null,
            ':new_values'  => isset($data['new_values']) ? json_encode($data['new_values']) : null,
            ':ip_address'  => $data['ip_address'] ?? ($_SERVER['REMOTE_ADDR'] ?? null),
            ':user_agent'  => $data['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? null),
            ':description' => $data['description'] ?? null,
        ]);
        return (int) $this->conn->lastInsertId();
    }

    public function readPaginated(int $page = 1, int $perPage = 50, array $filters = []): array
    {
        $where = ' WHERE 1=1';
        $params = [];

        if (!empty($filters['user_id'])) {
            $where .= ' AND al.user_id = :user_id';
            $params[':user_id'] = $filters['user_id'];
        }
        if (!empty($filters['entity_type'])) {
            $where .= ' AND al.entity_type = :entity_type';
            $params[':entity_type'] = $filters['entity_type'];
        }
        if (!empty($filters['action'])) {
            $where .= ' AND al.action = :action';
            $params[':action'] = $filters['action'];
        }
        if (!empty($filters['date_from'])) {
            $where .= ' AND al.created_at >= :date_from';
            $params[':date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where .= ' AND al.created_at <= :date_to';
            $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
        }
        if (!empty($filters['search'])) {
            $where .= ' AND (al.description LIKE :search OR al.user_name LIKE :search2)';
            $params[':search'] = '%' . $filters['search'] . '%';
            $params[':search2'] = '%' . $filters['search'] . '%';
        }

        $countStmt = $this->conn->prepare("SELECT COUNT(*) FROM audit_logs al" . $where);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $sql = "SELECT al.* FROM audit_logs al {$where} ORDER BY al.created_at DESC LIMIT :limit OFFSET :offset";
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

    public function readByEntity(string $entityType, int $entityId): array
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM audit_logs WHERE entity_type = :entity_type AND entity_id = :entity_id ORDER BY created_at DESC"
        );
        $stmt->execute([':entity_type' => $entityType, ':entity_id' => $entityId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteOld(int $daysOld = 365): int
    {
        $stmt = $this->conn->prepare(
            "DELETE FROM audit_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)"
        );
        $stmt->execute([':days' => $daysOld]);
        return $stmt->rowCount();
    }
}
