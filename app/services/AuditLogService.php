<?php

namespace Akti\Services;

/**
 * AuditLogService — Convenience wrapper for logging auditable actions.
 * FEAT-004
 *
 * Used by the global event listener to persist audit entries into the database.
 */
class AuditLogService
{
    private \PDO $db;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Log an audit entry.
     *
     * @param string      $action     e.g. 'created', 'updated', 'deleted'
     * @param string      $entityType e.g. 'order', 'customer', 'supplier'
     * @param int|string  $entityId
     * @param array       $oldValues  Previous values (for updates)
     * @param array       $newValues  New values
     * @param int|null    $userId
     * @param string|null $ipAddress
     */
    public function log(
        string $action,
        string $entityType,
        $entityId,
        array $oldValues = [],
        array $newValues = [],
        ?int $userId = null,
        ?string $ipAddress = null
    ): void {
        $userId = $userId ?? ($_SESSION['user_id'] ?? null);
        $ipAddress = $ipAddress ?? ($_SERVER['REMOTE_ADDR'] ?? null);
        $tenantId = $_SESSION['tenant']['id'] ?? 0;

        $stmt = $this->db->prepare(
            "INSERT INTO audit_logs (tenant_id, user_id, action, entity_type, entity_id, old_values, new_values, ip_address, created_at)
             VALUES (:tid, :uid, :action, :etype, :eid, :old, :new, :ip, NOW())"
        );
        $stmt->execute([
            ':tid'    => $tenantId,
            ':uid'    => $userId,
            ':action' => $action,
            ':etype'  => $entityType,
            ':eid'    => $entityId,
            ':old'    => !empty($oldValues) ? json_encode($oldValues) : null,
            ':new'    => !empty($newValues) ? json_encode($newValues) : null,
            ':ip'     => $ipAddress,
        ]);
    }
}
