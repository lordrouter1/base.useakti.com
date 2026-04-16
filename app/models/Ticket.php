<?php

namespace Akti\Models;

use PDO;

/**
 * Model de tickets de suporte.
 */
class Ticket
{
    private PDO $conn;

    /**
     * Construtor da classe Ticket.
     *
     * @param PDO $db Conexão PDO com o banco de dados
     */
    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * Cria um novo registro no banco de dados.
     *
     * @param array $data Dados para processamento
     * @return int
     */
    public function create(array $data): int
    {
        $data['ticket_number'] = $this->generateTicketNumber($data['tenant_id']);
        $stmt = $this->conn->prepare("
            INSERT INTO tickets (tenant_id, ticket_number, subject, description, category_id, priority, status, source, customer_id, assigned_to, created_by, sla_response_due, sla_resolution_due)
            VALUES (:tenant_id, :ticket_number, :subject, :description, :category_id, :priority, :status, :source, :customer_id, :assigned_to, :created_by, :sla_response_due, :sla_resolution_due)
        ");
        $stmt->execute([
            ':tenant_id'          => $data['tenant_id'],
            ':ticket_number'      => $data['ticket_number'],
            ':subject'            => $data['subject'],
            ':description'        => $data['description'],
            ':category_id'        => $data['category_id'] ?: null,
            ':priority'           => $data['priority'] ?? 'medium',
            ':status'             => $data['status'] ?? 'open',
            ':source'             => $data['source'] ?? 'internal',
            ':customer_id'        => $data['customer_id'] ?: null,
            ':assigned_to'        => $data['assigned_to'] ?: null,
            ':created_by'         => $data['created_by'] ?: null,
            ':sla_response_due'   => $data['sla_response_due'] ?? null,
            ':sla_resolution_due' => $data['sla_resolution_due'] ?? null,
        ]);
        return (int) $this->conn->lastInsertId();
    }

    /**
     * Retorna todos os registros.
     *
     * @param int $tenantId ID do tenant
     * @return array
     */
    public function readAll(int $tenantId): array
    {
        $stmt = $this->conn->prepare("
            SELECT t.*, tc.name AS category_name, c.name AS customer_name, u.name AS assigned_name
            FROM tickets t
            LEFT JOIN ticket_categories tc ON t.category_id = tc.id
            LEFT JOIN customers c ON t.customer_id = c.id
            LEFT JOIN users u ON t.assigned_to = u.id
            WHERE t.tenant_id = :tid AND t.deleted_at IS NULL
            ORDER BY t.created_at DESC
        ");
        $stmt->execute([':tid' => $tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Read paginated.
     *
     * @param int $tenantId ID do tenant
     * @param int $page Número da página
     * @param int $perPage Registros por página
     * @param array $filters Filtros aplicados
     * @return array
     */
    public function readPaginated(int $tenantId, int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $where = 't.tenant_id = :tid AND t.deleted_at IS NULL';
        $params = [':tid' => $tenantId];

        if (!empty($filters['status'])) {
            $where .= ' AND t.status = :status';
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['priority'])) {
            $where .= ' AND t.priority = :priority';
            $params[':priority'] = $filters['priority'];
        }
        if (!empty($filters['category_id'])) {
            $where .= ' AND t.category_id = :cat';
            $params[':cat'] = (int) $filters['category_id'];
        }
        if (!empty($filters['search'])) {
            $where .= ' AND (t.subject LIKE :search OR t.ticket_number LIKE :search2)';
            $params[':search'] = '%' . $filters['search'] . '%';
            $params[':search2'] = '%' . $filters['search'] . '%';
        }

        $countStmt = $this->conn->prepare("SELECT COUNT(*) FROM tickets t WHERE {$where}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $stmt = $this->conn->prepare("
            SELECT t.*, tc.name AS category_name, c.name AS customer_name, u.name AS assigned_name
            FROM tickets t
            LEFT JOIN ticket_categories tc ON t.category_id = tc.id
            LEFT JOIN customers c ON t.customer_id = c.id
            LEFT JOIN users u ON t.assigned_to = u.id
            WHERE {$where}
            ORDER BY FIELD(t.priority,'urgent','high','medium','low'), t.created_at DESC
            LIMIT :lim OFFSET :off
        ");
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data'         => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total'        => $total,
            'pages'        => (int) ceil($total / $perPage),
            'current_page' => $page,
        ];
    }

 /**
  * Read one.
  *
  * @param int $id ID do registro
  * @param int $tenantId ID do tenant
  * @return array|null
  */
    public function readOne(int $id, int $tenantId): ?array
    {
        $stmt = $this->conn->prepare("
            SELECT t.*, tc.name AS category_name, c.name AS customer_name, u.name AS assigned_name, cb.name AS created_by_name
            FROM tickets t
            LEFT JOIN ticket_categories tc ON t.category_id = tc.id
            LEFT JOIN customers c ON t.customer_id = c.id
            LEFT JOIN users u ON t.assigned_to = u.id
            LEFT JOIN users cb ON t.created_by = cb.id
            WHERE t.id = :id AND t.tenant_id = :tid AND t.deleted_at IS NULL
        ");
        $stmt->execute([':id' => $id, ':tid' => $tenantId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

 /**
  * Update.
  *
  * @param int $id ID do registro
  * @param int $tenantId ID do tenant
  * @param array $data Dados para processamento
  * @return bool
  */
    public function update(int $id, int $tenantId, array $data): bool
    {
        $stmt = $this->conn->prepare("
            UPDATE tickets SET subject = :subject, description = :description, category_id = :category_id, priority = :priority, status = :status, assigned_to = :assigned_to
            WHERE id = :id AND tenant_id = :tid
        ");
        return $stmt->execute([
            ':subject'     => $data['subject'],
            ':description' => $data['description'],
            ':category_id' => $data['category_id'] ?: null,
            ':priority'    => $data['priority'],
            ':status'      => $data['status'],
            ':assigned_to' => $data['assigned_to'] ?: null,
            ':id'          => $id,
            ':tid'         => $tenantId,
        ]);
    }

 /**
  * Update status.
  *
  * @param int $id ID do registro
  * @param int $tenantId ID do tenant
  * @param string $status Status do registro
  * @return bool
  */
    public function updateStatus(int $id, int $tenantId, string $status): bool
    {
        $extra = '';
        if ($status === 'resolved') {
            $extra = ', resolved_at = NOW()';
        } elseif ($status === 'closed') {
            $extra = ', closed_at = NOW()';
        }
        $stmt = $this->conn->prepare("UPDATE tickets SET status = :status{$extra} WHERE id = :id AND tenant_id = :tid");
        return $stmt->execute([':status' => $status, ':id' => $id, ':tid' => $tenantId]);
    }

 /**
  * Delete.
  *
  * @param int $id ID do registro
  * @param int $tenantId ID do tenant
  * @return bool
  */
    public function delete(int $id, int $tenantId): bool
    {
        $stmt = $this->conn->prepare("UPDATE tickets SET deleted_at = NOW() WHERE id = :id AND tenant_id = :tid");
        return $stmt->execute([':id' => $id, ':tid' => $tenantId]);
    }

 /**
  * Get messages.
  *
  * @param int $ticketId Ticket id
  * @param int $tenantId ID do tenant
  * @return array
  */
    public function getMessages(int $ticketId, int $tenantId): array
    {
        $stmt = $this->conn->prepare("
            SELECT tm.*, u.name AS user_name, c.name AS customer_name
            FROM ticket_messages tm
            LEFT JOIN users u ON tm.user_id = u.id
            LEFT JOIN customers c ON tm.customer_id = c.id
            WHERE tm.ticket_id = :tid AND tm.tenant_id = :tenant
            ORDER BY tm.created_at ASC
        ");
        $stmt->execute([':tid' => $ticketId, ':tenant' => $tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

 /**
  * Add message.
  *
  * @param array $data Dados para processamento
  * @return int
  */
    public function addMessage(array $data): int
    {
        $stmt = $this->conn->prepare("
            INSERT INTO ticket_messages (tenant_id, ticket_id, user_id, customer_id, message, is_internal_note)
            VALUES (:tenant_id, :ticket_id, :user_id, :customer_id, :message, :internal)
        ");
        $stmt->execute([
            ':tenant_id'   => $data['tenant_id'],
            ':ticket_id'   => $data['ticket_id'],
            ':user_id'     => $data['user_id'] ?? null,
            ':customer_id' => $data['customer_id'] ?? null,
            ':message'     => $data['message'],
            ':internal'    => $data['is_internal_note'] ?? 0,
        ]);

        if (empty($data['is_internal_note'])) {
            $this->conn->prepare("UPDATE tickets SET first_response_at = COALESCE(first_response_at, NOW()) WHERE id = :id")
                ->execute([':id' => $data['ticket_id']]);
        }

        return (int) $this->conn->lastInsertId();
    }

 /**
  * Get categories.
  *
  * @param int $tenantId ID do tenant
  * @return array
  */
    public function getCategories(int $tenantId): array
    {
        $stmt = $this->conn->prepare("SELECT * FROM ticket_categories WHERE tenant_id = :tid AND is_active = 1 ORDER BY name");
        $stmt->execute([':tid' => $tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

 /**
  * Get dashboard stats.
  *
  * @param int $tenantId ID do tenant
  * @return array
  */
    public function getDashboardStats(int $tenantId): array
    {
        $stmt = $this->conn->prepare("
            SELECT
                COUNT(*) AS total,
                SUM(status = 'open') AS open_count,
                SUM(status = 'in_progress') AS in_progress_count,
                SUM(status = 'resolved') AS resolved_count,
                SUM(status = 'closed') AS closed_count,
                SUM(priority = 'urgent' AND status NOT IN ('resolved','closed')) AS urgent_count,
                SUM(sla_resolution_due < NOW() AND status NOT IN ('resolved','closed')) AS sla_breached
            FROM tickets
            WHERE tenant_id = :tid AND deleted_at IS NULL
        ");
        $stmt->execute([':tid' => $tenantId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

 /**
  * Generate ticket number.
  *
  * @param int $tenantId ID do tenant
  * @return string
  */
    private function generateTicketNumber(int $tenantId): string
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) + 1 FROM tickets WHERE tenant_id = :tid");
        $stmt->execute([':tid' => $tenantId]);
        $seq = (int) $stmt->fetchColumn();
        return 'TKT-' . str_pad($seq, 6, '0', STR_PAD_LEFT);
    }
}
