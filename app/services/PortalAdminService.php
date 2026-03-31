<?php
namespace Akti\Services;

use PDO;

/**
 * PortalAdminService — Lógica de negócio da administração do Portal do Cliente.
 *
 * Responsabilidades:
 *   - Consultas de acessos filtrados
 *   - Consulta de clientes sem acesso ao portal
 *   - Métricas do portal
 *   - Contagem de mensagens pendentes
 *   - Remoção de sessões ativas
 *   - Geração de senhas temporárias
 *
 * @package Akti\Services
 */
class PortalAdminService
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Busca acessos filtrados por pesquisa e status.
     */
    public function getFilteredAccesses(string $search, string $filter): array
    {
        $where = '1=1';
        $params = [];

        if (!empty($search)) {
            $where .= " AND (pa.email LIKE :search OR c.name LIKE :search OR c.phone LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        switch ($filter) {
            case 'active':
                $where .= " AND pa.is_active = 1";
                break;
            case 'inactive':
                $where .= " AND pa.is_active = 0";
                break;
            case 'locked':
                $where .= " AND pa.locked_until > NOW()";
                break;
            case 'recent':
                $where .= " AND pa.last_login_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
        }

        $sql = "SELECT pa.*, c.name AS customer_name, c.phone AS customer_phone,
                       c.email AS customer_email_main
                FROM customer_portal_access pa
                JOIN customers c ON c.id = pa.customer_id
                WHERE {$where}
                ORDER BY pa.created_at DESC";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna clientes sem acesso ao portal.
     */
    public function getCustomersWithoutAccess(): array
    {
        $sql = "SELECT c.id, c.name, c.email, c.phone
                FROM customers c
                LEFT JOIN customer_portal_access pa ON pa.customer_id = c.id
                WHERE pa.id IS NULL
                ORDER BY c.name ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna métricas do portal.
     */
    public function getPortalMetrics(): array
    {
        $metrics = [
            'total_accesses'    => 0,
            'active_accesses'   => 0,
            'inactive_accesses' => 0,
            'logins_last_7d'    => 0,
            'logins_last_30d'   => 0,
            'pending_messages'  => 0,
            'locked_accounts'   => 0,
        ];

        $metrics['total_accesses'] = (int) $this->db
            ->query("SELECT COUNT(*) FROM customer_portal_access")
            ->fetchColumn();

        $metrics['active_accesses'] = (int) $this->db
            ->query("SELECT COUNT(*) FROM customer_portal_access WHERE is_active = 1")
            ->fetchColumn();

        $metrics['inactive_accesses'] = $metrics['total_accesses'] - $metrics['active_accesses'];

        $metrics['logins_last_7d'] = (int) $this->db
            ->query("SELECT COUNT(*) FROM customer_portal_access WHERE last_login_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")
            ->fetchColumn();

        $metrics['logins_last_30d'] = (int) $this->db
            ->query("SELECT COUNT(*) FROM customer_portal_access WHERE last_login_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")
            ->fetchColumn();

        try {
            $metrics['pending_messages'] = (int) $this->db
                ->query("SELECT COUNT(*) FROM customer_portal_messages WHERE sender_type = 'customer' AND is_read = 0")
                ->fetchColumn();
        } catch (\PDOException $e) {
            // Tabela pode não existir
        }

        $metrics['locked_accounts'] = (int) $this->db
            ->query("SELECT COUNT(*) FROM customer_portal_access WHERE locked_until > NOW()")
            ->fetchColumn();

        return $metrics;
    }

    /**
     * Conta mensagens pendentes de clientes.
     */
    public function countPendingMessages(): int
    {
        try {
            $stmt = $this->db->query(
                "SELECT COUNT(*) FROM customer_portal_messages WHERE sender_type = 'customer' AND is_read = 0"
            );
            return (int) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            return 0;
        }
    }

    /**
     * Remove sessões ativas de um acesso.
     */
    public function removeActiveSessions(int $accessId): int
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM customer_portal_sessions WHERE access_id = :aid");
            $stmt->execute([':aid' => $accessId]);
            return $stmt->rowCount();
        } catch (\PDOException $e) {
            return 0;
        }
    }

    /**
     * Gera senha temporária aleatória.
     */
    public function generateTempPassword(): string
    {
        $chars = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $password = '';
        for ($i = 0; $i < 10; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $password;
    }
}
