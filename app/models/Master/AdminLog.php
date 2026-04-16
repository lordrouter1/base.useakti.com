<?php

namespace Akti\Models\Master;

use PDO;

/**
 * Model de log de ações administrativas do painel master.
 */
class AdminLog
{
    private $db;

    /**
     * Construtor da classe AdminLog.
     *
     * @param PDO $db Conexão PDO com o banco de dados
     */
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Registra informação no log.
     *
     * @param int $adminId Admin id
     * @param string $action Action
     * @param string|null $targetType Target type
     * @param int|null $targetId Target id
     * @param string|null $details Details
     * @return void
     */
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

    /**
     * Read recent.
     *
     * @param int $limit Limite de registros
     * @return array
     */
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
