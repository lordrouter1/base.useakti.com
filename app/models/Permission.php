<?php

namespace Akti\Models;

use PDO;
use Akti\Core\EventDispatcher;
use Akti\Core\Event;

class Permission
{
    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function readAll(): array
    {
        $stmt = $this->conn->prepare("SELECT * FROM permissions ORDER BY page, action");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function readByPage(string $page): array
    {
        $stmt = $this->conn->prepare("SELECT * FROM permissions WHERE page = :page ORDER BY action");
        $stmt->execute([':page' => $page]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function readOne(int $id): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM permissions WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

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

    public function delete(int $id): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM permissions WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

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

    public function removeGroupPermission(int $groupId, int $permissionId): bool
    {
        $stmt = $this->conn->prepare(
            "DELETE FROM group_permissions WHERE group_id = :group_id AND permission_id = :permission_id"
        );
        return $stmt->execute([':group_id' => $groupId, ':permission_id' => $permissionId]);
    }

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
