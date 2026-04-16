<?php

namespace Akti\Models;

use PDO;

/**
 * Model de anexos/arquivos vinculados a registros.
 */
class Attachment
{
    private PDO $conn;

    /**
     * Construtor da classe Attachment.
     *
     * @param PDO $db Conexão PDO com o banco de dados
     */
    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * Read by entity.
     *
     * @param string $entityType Entity type
     * @param int $entityId Entity id
     * @return array
     */
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

    /**
     * Retorna um registro pelo ID.
     *
     * @param int $id ID do registro
     * @return array|null
     */
    public function readOne(int $id): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM attachments WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Cria um novo registro no banco de dados.
     *
     * @param array $data Dados para processamento
     * @return int
     */
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

    /**
     * Remove um registro pelo ID.
     *
     * @param int $id ID do registro
     * @return bool
     */
    public function delete(int $id): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM attachments WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Conta registros com critérios opcionais.
     *
     * @param string $entityType Entity type
     * @param int $entityId Entity id
     * @return int
     */
    public function countByEntity(string $entityType, int $entityId): int
    {
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) FROM attachments WHERE entity_type = :entity_type AND entity_id = :entity_id"
        );
        $stmt->execute([':entity_type' => $entityType, ':entity_id' => $entityId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Read paginated.
     *
     * @param int $page Número da página
     * @param int $perPage Registros por página
     * @param string $entityType Entity type
     * @return array
     */
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
