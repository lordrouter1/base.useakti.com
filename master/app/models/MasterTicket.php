<?php
/**
 * Model: MasterTicket
 * Gerencia tickets de suporte centralizados no banco akti_master.
 * Tabelas: support_tickets, support_ticket_messages
 */

class MasterTicket
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Lista tickets de suporte com filtros opcionais.
     *
     * @param array $filters ['tenant_id' => int, 'status' => string, 'priority' => string, 'search' => string]
     * @return array
     */
    public function readAll(array $filters = []): array
    {
        $where = '1=1';
        $params = [];

        if (!empty($filters['tenant_id'])) {
            $where .= ' AND st.tenant_client_id = :tenant_id';
            $params['tenant_id'] = (int) $filters['tenant_id'];
        }
        if (!empty($filters['status'])) {
            $where .= ' AND st.status = :status';
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['priority'])) {
            $where .= ' AND st.priority = :priority';
            $params['priority'] = $filters['priority'];
        }
        if (!empty($filters['search'])) {
            $where .= ' AND (st.subject LIKE :search OR st.ticket_number LIKE :search2)';
            $params['search'] = '%' . $filters['search'] . '%';
            $params['search2'] = '%' . $filters['search'] . '%';
        }

        $sql = "
            SELECT st.*,
                   tc.client_name AS tenant_name,
                   tc.subdomain AS tenant_subdomain,
                   au.name AS assigned_admin_name
            FROM support_tickets st
            LEFT JOIN tenant_clients tc ON st.tenant_client_id = tc.id
            LEFT JOIN admin_users au ON st.assigned_admin_id = au.id
            WHERE {$where}
            ORDER BY
                FIELD(st.status, 'open', 'in_progress', 'waiting_customer', 'resolved', 'closed'),
                FIELD(st.priority, 'urgent', 'high', 'medium', 'low'),
                st.created_at DESC
            LIMIT 200
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Lê um ticket específico por ID.
     */
    public function readOne(int $ticketId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT st.*,
                   tc.client_name AS tenant_name,
                   tc.subdomain AS tenant_subdomain,
                   au.name AS assigned_admin_name
            FROM support_tickets st
            LEFT JOIN tenant_clients tc ON st.tenant_client_id = tc.id
            LEFT JOIN admin_users au ON st.assigned_admin_id = au.id
            WHERE st.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $ticketId]);
        $ticket = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $ticket ?: null;
    }

    /**
     * Obtém as mensagens de um ticket.
     */
    public function getMessages(int $ticketId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM support_ticket_messages
            WHERE support_ticket_id = :ticket_id
            ORDER BY created_at ASC
        ");
        $stmt->execute(['ticket_id' => $ticketId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Adiciona uma resposta de admin a um ticket.
     */
    public function addAdminReply(int $adminId, string $adminName, int $ticketId, string $message, bool $isInternalNote = false): bool
    {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                INSERT INTO support_ticket_messages
                    (support_ticket_id, sender_type, sender_id, sender_name, message, is_internal_note)
                VALUES
                    (:ticket_id, 'admin', :sender_id, :sender_name, :message, :is_internal)
            ");
            $stmt->execute([
                'ticket_id'   => $ticketId,
                'sender_id'   => $adminId,
                'sender_name' => $adminName,
                'message'     => $message,
                'is_internal' => $isInternalNote ? 1 : 0,
            ]);

            // Se era open, mover para in_progress
            $this->db->prepare("
                UPDATE support_tickets SET status = 'in_progress', updated_at = NOW()
                WHERE id = :id AND status = 'open'
            ")->execute(['id' => $ticketId]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log('[MasterTicket] Error adding reply: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Altera o status de um ticket.
     */
    public function changeStatus(int $ticketId, string $newStatus): bool
    {
        $validStatuses = ['open', 'in_progress', 'waiting_customer', 'resolved', 'closed'];
        if (!in_array($newStatus, $validStatuses, true)) {
            return false;
        }

        $extra = '';
        if ($newStatus === 'resolved') {
            $extra = ', resolved_at = NOW()';
        } elseif ($newStatus === 'closed') {
            $extra = ', closed_at = NOW()';
        }

        $stmt = $this->db->prepare("
            UPDATE support_tickets
            SET status = :status{$extra}, updated_at = NOW()
            WHERE id = :id
        ");
        return $stmt->execute(['status' => $newStatus, 'id' => $ticketId]);
    }

    /**
     * Atribui um admin a um ticket.
     */
    public function assignAdmin(int $ticketId, ?int $adminId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE support_tickets
            SET assigned_admin_id = :admin_id, updated_at = NOW()
            WHERE id = :id
        ");
        return $stmt->execute(['admin_id' => $adminId, 'id' => $ticketId]);
    }

    /**
     * Estatísticas globais de tickets de suporte.
     */
    public function getGlobalStats(): array
    {
        $stmt = $this->db->query("
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) AS open_count,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) AS in_progress_count,
                SUM(CASE WHEN status = 'waiting_customer' THEN 1 ELSE 0 END) AS waiting_count,
                SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) AS resolved_count,
                SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) AS closed_count,
                SUM(CASE WHEN priority = 'urgent' AND status IN ('open','in_progress') THEN 1 ELSE 0 END) AS urgent_count
            FROM support_tickets
        ");
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return [
            'total'       => (int) ($row['total'] ?? 0),
            'open'        => (int) ($row['open_count'] ?? 0),
            'in_progress' => (int) ($row['in_progress_count'] ?? 0),
            'waiting'     => (int) ($row['waiting_count'] ?? 0),
            'resolved'    => (int) ($row['resolved_count'] ?? 0),
            'closed'      => (int) ($row['closed_count'] ?? 0),
            'urgent'      => (int) ($row['urgent_count'] ?? 0),
        ];
    }

    /**
     * Lista tickets de um tenant específico (para uso pelo tenant app).
     */
    public function readByTenant(int $tenantClientId, array $filters = []): array
    {
        $where = 'st.tenant_client_id = :tenant_id';
        $params = ['tenant_id' => $tenantClientId];

        if (!empty($filters['status'])) {
            $where .= ' AND st.status = :status';
            $params['status'] = $filters['status'];
        }

        $stmt = $this->db->prepare("
            SELECT st.*
            FROM support_tickets st
            WHERE {$where}
            ORDER BY st.created_at DESC
            LIMIT 50
        ");
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Cria um ticket de suporte (chamado pelo tenant).
     */
    public function createTicket(array $data): ?int
    {
        // Gerar ticket_number
        $stmt = $this->db->query("SELECT MAX(id) AS max_id FROM support_tickets");
        $maxId = (int) ($stmt->fetch(\PDO::FETCH_ASSOC)['max_id'] ?? 0);
        $ticketNumber = 'SUP-' . str_pad($maxId + 1, 5, '0', STR_PAD_LEFT);

        $stmt = $this->db->prepare("
            INSERT INTO support_tickets
                (tenant_client_id, ticket_number, subject, description, priority, category,
                 created_by_user_id, created_by_name, created_by_email)
            VALUES
                (:tenant_id, :ticket_number, :subject, :description, :priority, :category,
                 :user_id, :user_name, :user_email)
        ");

        $success = $stmt->execute([
            'tenant_id'     => (int) $data['tenant_client_id'],
            'ticket_number' => $ticketNumber,
            'subject'       => $data['subject'],
            'description'   => $data['description'],
            'priority'      => $data['priority'] ?? 'medium',
            'category'      => $data['category'] ?? null,
            'user_id'       => $data['user_id'] ?? null,
            'user_name'     => $data['user_name'],
            'user_email'    => $data['user_email'] ?? null,
        ]);

        if ($success) {
            $ticketId = (int) $this->db->lastInsertId();

            // Inserir a descrição como primeira mensagem
            $this->db->prepare("
                INSERT INTO support_ticket_messages
                    (support_ticket_id, sender_type, sender_id, sender_name, message)
                VALUES
                    (:ticket_id, 'tenant', :sender_id, :sender_name, :message)
            ")->execute([
                'ticket_id'   => $ticketId,
                'sender_id'   => $data['user_id'] ?? null,
                'sender_name' => $data['user_name'],
                'message'     => $data['description'],
            ]);

            return $ticketId;
        }

        return null;
    }

    /**
     * Adiciona mensagem do tenant a um ticket existente.
     */
    public function addTenantMessage(int $ticketId, int $tenantClientId, ?int $userId, string $userName, string $message): bool
    {
        // Verificar que o ticket pertence ao tenant
        $stmt = $this->db->prepare("
            SELECT id FROM support_tickets WHERE id = :id AND tenant_client_id = :tenant_id
        ");
        $stmt->execute(['id' => $ticketId, 'tenant_id' => $tenantClientId]);
        if (!$stmt->fetch()) {
            return false;
        }

        $this->db->prepare("
            INSERT INTO support_ticket_messages
                (support_ticket_id, sender_type, sender_id, sender_name, message)
            VALUES
                (:ticket_id, 'tenant', :sender_id, :sender_name, :message)
        ")->execute([
            'ticket_id'   => $ticketId,
            'sender_id'   => $userId,
            'sender_name' => $userName,
            'message'     => $message,
        ]);

        // Se status é waiting_customer, mover para open
        $this->db->prepare("
            UPDATE support_tickets SET status = 'open', updated_at = NOW()
            WHERE id = :id AND status = 'waiting_customer'
        ")->execute(['id' => $ticketId]);

        return true;
    }

    /**
     * Lista admin users disponíveis para atribuição.
     */
    public function getAdminUsers(): array
    {
        $stmt = $this->db->query("SELECT id, name, email FROM admin_users ORDER BY name");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
