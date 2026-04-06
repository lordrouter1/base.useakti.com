<?php
/**
 * Model: AdminLog
 * Registra ações administrativas
 */

class AdminLog
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function log($adminId, $action, $targetType = null, $targetId = null, $details = null)
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

    public function readRecent($limit = 20)
    {
        $stmt = $this->db->prepare("
            SELECT al.*, au.name as admin_name 
            FROM admin_logs al 
            JOIN admin_users au ON al.admin_id = au.id 
            ORDER BY al.created_at DESC 
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
