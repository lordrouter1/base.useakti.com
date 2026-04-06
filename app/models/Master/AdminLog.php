<?php

namespace Akti\Models\Master;

use PDO;

class AdminLog
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function log(int $adminId, string $action, ?string $targetType = null, ?int $targetId = null, ?string $details = null): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO admin_logs (admin_id, action, target_type, target_id, details, ip_address)
            VALUES (:admin_id, :action, :target_type, :target_id, :details, :ip_address)
        ");
        $stmt->execute([
            'admin_id'    => $adminId,
            'action'      => $action,
            'target_type' => $targetType,
            'target_id'   => $targetId,
            'details'     => $details,
            'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    }

    public function readRecent(int $limit = 20): array
    {
        $stmt = $this->db->prepare("
            SELECT al.*, au.name as admin_name
            FROM admin_logs al
            JOIN admin_users au ON al.admin_id = au.id
            ORDER BY al.created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
