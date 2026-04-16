<?php
namespace Akti\Models;

use PDO;

/**
 * Model: ImportMappingProfile
 * Gerencia perfis de mapeamento salvos para importação.
 */
class ImportMappingProfile
{
    private $conn;
    private $table = 'import_mapping_profiles';

    /**
     * Construtor da classe ImportMappingProfile.
     *
     * @param \PDO $db Conexão PDO com o banco de dados
     */
    public function __construct(\PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * Lista perfis do tenant.
     */
    public function listByTenant(int $tenantId, string $entityType = 'customers'): array
    {
        $stmt = $this->conn->prepare("
            SELECT id, name, mapping_json, import_mode, is_default, created_at
            FROM {$this->table}
            WHERE tenant_id = :tid AND entity_type = :et
            ORDER BY is_default DESC, name ASC
        ");
        $stmt->execute([':tid' => $tenantId, ':et' => $entityType]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca um perfil por ID.
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Cria um novo perfil.
     */
    public function create(array $data): int
    {
        // Se marcado como default, desmarcar outros
        if (!empty($data['is_default'])) {
            $this->clearDefault($data['tenant_id'], $data['entity_type'] ?? 'customers');
        }

        $stmt = $this->conn->prepare("
            INSERT INTO {$this->table}
                (tenant_id, entity_type, name, mapping_json, import_mode, is_default, created_by)
            VALUES
                (:tid, :et, :name, :mapping, :mode, :def, :uid)
        ");
        $stmt->execute([
            ':tid'     => $data['tenant_id'],
            ':et'      => $data['entity_type'] ?? 'customers',
            ':name'    => $data['name'],
            ':mapping' => $data['mapping_json'],
            ':mode'    => $data['import_mode'] ?? 'create',
            ':def'     => !empty($data['is_default']) ? 1 : 0,
            ':uid'     => $data['created_by'] ?? null,
        ]);
        return (int) $this->conn->lastInsertId();
    }

    /**
     * Atualiza um perfil existente.
     */
    public function update(int $id, array $data): bool
    {
        if (!empty($data['is_default']) && isset($data['tenant_id'])) {
            $this->clearDefault($data['tenant_id'], $data['entity_type'] ?? 'customers');
        }

        $sets = [];
        $params = [':id' => $id];

        if (isset($data['name'])) {
            $sets[] = 'name = :name';
            $params[':name'] = $data['name'];
        }
        if (isset($data['mapping_json'])) {
            $sets[] = 'mapping_json = :mapping';
            $params[':mapping'] = $data['mapping_json'];
        }
        if (isset($data['import_mode'])) {
            $sets[] = 'import_mode = :mode';
            $params[':mode'] = $data['import_mode'];
        }
        if (isset($data['is_default'])) {
            $sets[] = 'is_default = :def';
            $params[':def'] = $data['is_default'] ? 1 : 0;
        }

        if (empty($sets)) return false;

        $stmt = $this->conn->prepare("UPDATE {$this->table} SET " . implode(', ', $sets) . " WHERE id = :id");
        return $stmt->execute($params);
    }

    /**
     * Exclui um perfil.
     */
    public function delete(int $id): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Desmarca todos os perfis default de um tenant/entity_type.
     */
    private function clearDefault(int $tenantId, string $entityType): void
    {
        $stmt = $this->conn->prepare("
            UPDATE {$this->table} SET is_default = 0
            WHERE tenant_id = :tid AND entity_type = :et
        ");
        $stmt->execute([':tid' => $tenantId, ':et' => $entityType]);
    }
}
