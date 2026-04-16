<?php

namespace Akti\Models;

use PDO;
use Akti\Core\EventDispatcher;
use Akti\Core\Event;

/**
 * Model de permissões de acesso por grupo de usuários.
 */
class Permission
{
    private PDO $conn;

    /**
     * Construtor da classe Permission.
     *
     * @param PDO $db Conexão PDO com o banco de dados
     */
    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * Retorna todos os registros.
     * @return array
     */
    public function readAll(): array
    {
        $stmt = $this->conn->prepare("SELECT * FROM permissions ORDER BY page, action");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Read by page.
     *
     * @param string $page Número da página
     * @return array
     */
    public function readByPage(string $page): array
    {
        $stmt = $this->conn->prepare("SELECT * FROM permissions WHERE page = :page ORDER BY action");
        $stmt->execute([':page' => $page]);
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
        $stmt = $this->conn->prepare("SELECT * FROM permissions WHERE id = :id");
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
            "INSERT INTO permissions (tenant_id, page, action, label)
             VALUES (:tenant_id, :page, :action, :label)"
        );
        $stmt->execute([
            ':tenant_id' => $data['tenant_id'],
            ':page'      => $data['page'],
            ':action'    => $data['action'] ?? '*',
            ':label'     => $data['label'] ?? null,
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
        $stmt = $this->conn->prepare("DELETE FROM permissions WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Obtém dados específicos.
     *
     * @param int $groupId Group id
     * @return array
     */
    public function getGroupPermissions(int $groupId): array
    {
        $stmt = $this->conn->prepare(
            "SELECT gp.*, p.page, p.action, p.label
             FROM group_permissions gp
             JOIN permissions p ON p.id = gp.permission_id
             WHERE gp.group_id = :group_id
             ORDER BY p.page, p.action"
        );
        $stmt->execute([':group_id' => $groupId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Define valor específico.
     *
     * @param int $groupId Group id
     * @param int $permissionId Permission id
     * @param array $abilities Abilities
     * @return bool
     */
    public function setGroupPermission(int $groupId, int $permissionId, array $abilities): bool
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO group_permissions (tenant_id, group_id, permission_id, can_view, can_create, can_edit, can_delete)
             VALUES (:tenant_id, :group_id, :permission_id, :can_view, :can_create, :can_edit, :can_delete)
             ON DUPLICATE KEY UPDATE
                can_view = VALUES(can_view), can_create = VALUES(can_create),
                can_edit = VALUES(can_edit), can_delete = VALUES(can_delete)"
        );
        return $stmt->execute([
            ':tenant_id'     => $abilities['tenant_id'],
            ':group_id'      => $groupId,
            ':permission_id' => $permissionId,
            ':can_view'      => $abilities['can_view'] ?? 1,
            ':can_create'    => $abilities['can_create'] ?? 0,
            ':can_edit'      => $abilities['can_edit'] ?? 0,
            ':can_delete'    => $abilities['can_delete'] ?? 0,
        ]);
    }

    /**
     * Remove group permission.
     *
     * @param int $groupId Group id
     * @param int $permissionId Permission id
     * @return bool
     */
    public function removeGroupPermission(int $groupId, int $permissionId): bool
    {
        $stmt = $this->conn->prepare(
            "DELETE FROM group_permissions WHERE group_id = :group_id AND permission_id = :permission_id"
        );
        return $stmt->execute([':group_id' => $groupId, ':permission_id' => $permissionId]);
    }

    /**
     * Verifica se o usuário tem permissão de acesso.
     *
     * @param int $groupId Group id
     * @param string $page Número da página
     * @param string $action Action
     * @return bool
     */
    public function checkPermission(int $groupId, string $page, string $action = 'view'): bool
    {
        $column = match ($action) {
            'create' => 'can_create',
            'edit'   => 'can_edit',
            'delete' => 'can_delete',
            default  => 'can_view',
        };

        $stmt = $this->conn->prepare(
            "SELECT gp.{$column}
             FROM group_permissions gp
             JOIN permissions p ON p.id = gp.permission_id
             WHERE gp.group_id = :group_id AND p.page = :page AND (p.action = :action OR p.action = '*')
             LIMIT 1"
        );
        $stmt->execute([':group_id' => $groupId, ':page' => $page, ':action' => $action]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (bool) $row[$column] : true;
    }

 /**
  * Seed permissions from menu.
  *
  * @param int $tenantId ID do tenant
  * @param array $menuConfig Menu config
  * @return int
  */
    public function seedPermissionsFromMenu(int $tenantId, array $menuConfig): int
    {
        $count = 0;
        foreach ($menuConfig as $key => $item) {
            if (!empty($item['children'])) {
                foreach ($item['children'] as $childKey => $child) {
                    if (!empty($child['permission'])) {
                        $this->createIfNotExists($tenantId, $childKey);
                        $count++;
                    }
                }
            } elseif (!empty($item['permission'])) {
                $this->createIfNotExists($tenantId, $key);
                $count++;
            }
        }
        return $count;
    }

 /**
  * Create if not exists.
  *
  * @param int $tenantId ID do tenant
  * @param string $page Número da página
  * @return void
  */
    private function createIfNotExists(int $tenantId, string $page): void
    {
        $stmt = $this->conn->prepare(
            "SELECT id FROM permissions WHERE tenant_id = :tenant_id AND page = :page AND action = '*'"
        );
        $stmt->execute([':tenant_id' => $tenantId, ':page' => $page]);
        if (!$stmt->fetch()) {
            $this->create([
                'tenant_id' => $tenantId,
                'page'      => $page,
                'action'    => '*',
                'label'     => ucfirst(str_replace('_', ' ', $page)),
            ]);
        }
    }
}
