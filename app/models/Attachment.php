<?php

namespace Akti\Models;

use PDO;

class Attachment
{
    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function readByEntity(string $entityType, int $entityId): array
    {
        $stmt = $this->conn->prepare(
            "SELECT a.*, u.name as uploader_name
             FROM attachments a
             LEFT JOIN users u ON u.id = a.uploaded_by
             WHERE a.entity_type = :entity_type AND a.entity_id = :entity_id
             ORDER BY a.created_at DESC"
        );
        $stmt->execute([':entity_type' => $entityType, ':entity_id' => $entityId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function readOne(int $id): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM attachments WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO attachments (tenant_id, entity_type, entity_id, filename, original_name, path, mime_type, size, uploaded_by, description)
             VALUES (:tenant_id, :entity_type, :entity_id, :filename, :original_name, :path, :mime_type, :size, :uploaded_by, :description)"
        );
        $stmt->execute([
            ':tenant_id'     => $data['tenant_id'],
            ':entity_type'   => $data['entity_type'],
            ':entity_id'     => $data['entity_id'],
            ':filename'      => $data['filename'],
            ':original_name' => $data['original_name'],
            ':path'          => $data['path'],
            ':mime_type'     => $data['mime_type'],
            ':size'          => $data['size'] ?? 0,
            ':uploaded_by'   => $data['uploaded_by'] ?? null,
            ':description'   => $data['description'] ?? null,
        ]);
        return (int) $this->conn->lastInsertId();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM attachments WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function countByEntity(string $entityType, int $entityId): int
    {
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) FROM attachments WHERE entity_type = :entity_type AND entity_id = :entity_id"
        );
        $stmt->execute([':entity_type' => $entityType, ':entity_id' => $entityId]);
        return (int) $stmt->fetchColumn();
    }

    public function readPaginated(int $page = 1, int $perPage = 20, string $entityType = ''): array
    {
        $where = '';
        $params = [];
        if ($entityType) {
            $where = ' WHERE entity_type = :entity_type';
            $params[':entity_type'] = $entityType;
        }

        $countStmt = $this->conn->prepare("SELECT COUNT(*) FROM attachments" . $where);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $sql = "SELECT a.*, u.name as uploader_name
                FROM attachments a
                LEFT JOIN users u ON u.id = a.uploaded_by
                {$where}
                ORDER BY a.created_at DESC
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
}
