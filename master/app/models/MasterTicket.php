<?php
/**
 * Model: MasterTicket
 * Gerencia tickets de suporte de todos os tenants a partir do painel Master.
 * Realiza queries cross-database conectando-se ao banco de cada tenant.
 */

class MasterTicket
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Lista tickets de todos os tenants ativos com filtros.
     *
     * @param array $filters ['tenant_id' => int, 'status' => string, 'priority' => string]
     * @return array
     */
    public function readAllFromAllTenants(array $filters = []): array
    {
        $tenants = $this->getActiveTenantsWithDb();
        $tickets = [];

        foreach ($tenants as $tenant) {
            try {
                $tenantDb = $this->connectToTenant($tenant);
                if (!$tenantDb) continue;

                // Verificar se a tabela tickets existe
                $check = $tenantDb->query("SHOW TABLES LIKE 'tickets'");
                if ($check->rowCount() === 0) continue;

                $where = '1=1';
                $params = [];

                if (!empty($filters['status'])) {
                    $where .= ' AND t.status = :status';
                    $params['status'] = $filters['status'];
                }
                if (!empty($filters['priority'])) {
                    $where .= ' AND t.priority = :priority';
                    $params['priority'] = $filters['priority'];
                }

                $sql = "
                    SELECT t.*, 
                           COALESCE(u.name, 'Sistema') as user_name,
                           COALESCE(u.email, '') as user_email,
                           COALESCE(c.name, '') as customer_name
                    FROM tickets t
                    LEFT JOIN users u ON t.created_by = u.id
                    LEFT JOIN customers c ON t.customer_id = c.id
                    WHERE {$where}
                    ORDER BY t.created_at DESC
                    LIMIT 100
                ";

                $stmt = $tenantDb->prepare($sql);
                $stmt->execute($params);
                $tenantTickets = $stmt->fetchAll(\PDO::FETCH_ASSOC);

                foreach ($tenantTickets as &$ticket) {
                    $ticket['tenant_client_id'] = $tenant['id'];
                    $ticket['tenant_name'] = $tenant['client_name'];
                    $ticket['tenant_subdomain'] = $tenant['subdomain'];
                }
                unset($ticket);

                $tickets = array_merge($tickets, $tenantTickets);
            } catch (\Exception $e) {
                error_log('[MasterTicket] Error reading tickets from tenant ' . $tenant['client_name'] . ': ' . $e->getMessage());
                continue;
            }
        }

        // Filtro por tenant_id (pós-query)
        if (!empty($filters['tenant_id'])) {
            $tenantId = (int)$filters['tenant_id'];
            $tickets = array_filter($tickets, fn($t) => (int)$t['tenant_client_id'] === $tenantId);
            $tickets = array_values($tickets);
        }

        // Ordenar por data mais recente
        usort($tickets, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));

        return $tickets;
    }

    /**
     * Lê um ticket específico de um tenant.
     */
    public function readTicketFromTenant(int $tenantClientId, int $ticketId): ?array
    {
        $tenant = $this->getTenantById($tenantClientId);
        if (!$tenant) return null;

        try {
            $tenantDb = $this->connectToTenant($tenant);
            if (!$tenantDb) return null;

            $stmt = $tenantDb->prepare("
                SELECT t.*, 
                       COALESCE(u.name, 'Sistema') as user_name,
                       COALESCE(u.email, '') as user_email,
                       COALESCE(c.name, '') as customer_name
                FROM tickets t
                LEFT JOIN users u ON t.created_by = u.id
                LEFT JOIN customers c ON t.customer_id = c.id
                WHERE t.id = :id
                LIMIT 1
            ");
            $stmt->execute(['id' => $ticketId]);
            $ticket = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($ticket) {
                $ticket['tenant_client_id'] = $tenant['id'];
                $ticket['tenant_name'] = $tenant['client_name'];
                $ticket['tenant_subdomain'] = $tenant['subdomain'];
            }

            return $ticket ?: null;
        } catch (\Exception $e) {
            error_log('[MasterTicket] Error reading ticket: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtém as mensagens de um ticket.
     */
    public function getTicketMessages(int $tenantClientId, int $ticketId): array
    {
        $tenant = $this->getTenantById($tenantClientId);
        if (!$tenant) return [];

        try {
            $tenantDb = $this->connectToTenant($tenant);
            if (!$tenantDb) return [];

            // Verificar se tabela ticket_messages existe
            $check = $tenantDb->query("SHOW TABLES LIKE 'ticket_messages'");
            if ($check->rowCount() === 0) return [];

            $stmt = $tenantDb->prepare("
                SELECT tm.*, 
                       COALESCE(u.name, 'Sistema') as user_name
                FROM ticket_messages tm
                LEFT JOIN users u ON tm.user_id = u.id
                WHERE tm.ticket_id = :ticket_id
                ORDER BY tm.created_at ASC
            ");
            $stmt->execute(['ticket_id' => $ticketId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log('[MasterTicket] Error reading messages: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Responde a um ticket (insere mensagem no banco do tenant + log no master).
     */
    public function replyToTicket(int $adminId, int $tenantClientId, int $ticketId, string $message): bool
    {
        $tenant = $this->getTenantById($tenantClientId);
        if (!$tenant) return false;

        try {
            $tenantDb = $this->connectToTenant($tenant);
            if (!$tenantDb) return false;

            // Verificar se tabela ticket_messages existe
            $check = $tenantDb->query("SHOW TABLES LIKE 'ticket_messages'");
            if ($check->rowCount() === 0) return false;

            // Buscar nome do admin
            $adminStmt = $this->db->prepare("SELECT name FROM admin_users WHERE id = :id");
            $adminStmt->execute(['id' => $adminId]);
            $admin = $adminStmt->fetch(\PDO::FETCH_ASSOC);
            $adminName = $admin ? $admin['name'] : 'Suporte Akti';

            // Inserir mensagem no tenant com prefixo [Suporte Akti]
            $stmt = $tenantDb->prepare("
                INSERT INTO ticket_messages (tenant_id, ticket_id, user_id, message, created_at)
                VALUES (:tenant_id, :ticket_id, NULL, :message, NOW())
            ");
            $stmt->execute([
                'tenant_id' => $tenant['id'],
                'ticket_id' => $ticketId,
                'message' => '[Suporte Akti - ' . $adminName . '] ' . $message,
            ]);

            // Atualizar status do ticket para in_progress se estava open
            $tenantDb->prepare("
                UPDATE tickets SET status = 'in_progress', updated_at = NOW()
                WHERE id = :id AND status = 'open'
            ")->execute(['id' => $ticketId]);

            // Log no master
            $this->logReply($adminId, $tenantClientId, $ticketId, $message, 'reply');

            return true;
        } catch (\Exception $e) {
            error_log('[MasterTicket] Error replying to ticket: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Altera o status de um ticket.
     */
    public function changeTicketStatus(int $adminId, int $tenantClientId, int $ticketId, string $newStatus): bool
    {
        $validStatuses = ['open', 'in_progress', 'resolved', 'closed'];
        if (!in_array($newStatus, $validStatuses, true)) return false;

        $tenant = $this->getTenantById($tenantClientId);
        if (!$tenant) return false;

        try {
            $tenantDb = $this->connectToTenant($tenant);
            if (!$tenantDb) return false;

            // Obter status atual
            $stmt = $tenantDb->prepare("SELECT status FROM tickets WHERE id = :id");
            $stmt->execute(['id' => $ticketId]);
            $current = $stmt->fetch(\PDO::FETCH_ASSOC);
            $oldStatus = $current ? $current['status'] : 'unknown';

            // Atualizar
            $stmt = $tenantDb->prepare("
                UPDATE tickets SET status = :status, updated_at = NOW() WHERE id = :id
            ");
            $stmt->execute(['status' => $newStatus, 'id' => $ticketId]);

            // Log no master
            $this->logReply($adminId, $tenantClientId, $ticketId,
                "Status alterado de {$oldStatus} para {$newStatus}", 'status_change',
                $oldStatus, $newStatus);

            return true;
        } catch (\Exception $e) {
            error_log('[MasterTicket] Error changing status: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Retorna estatísticas globais consolidadas de tickets.
     */
    public function getGlobalStats(): array
    {
        $stats = [
            'total' => 0,
            'open' => 0,
            'in_progress' => 0,
            'resolved' => 0,
            'closed' => 0,
            'urgent' => 0,
        ];

        $tenants = $this->getActiveTenantsWithDb();

        foreach ($tenants as $tenant) {
            try {
                $tenantDb = $this->connectToTenant($tenant);
                if (!$tenantDb) continue;

                $check = $tenantDb->query("SHOW TABLES LIKE 'tickets'");
                if ($check->rowCount() === 0) continue;

                $stmt = $tenantDb->query("
                    SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_count,
                        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_count,
                        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_count,
                        SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_count,
                        SUM(CASE WHEN priority = 'urgent' AND status IN ('open','in_progress') THEN 1 ELSE 0 END) as urgent_count
                    FROM tickets
                ");
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);

                $stats['total'] += (int)($row['total'] ?? 0);
                $stats['open'] += (int)($row['open_count'] ?? 0);
                $stats['in_progress'] += (int)($row['in_progress_count'] ?? 0);
                $stats['resolved'] += (int)($row['resolved_count'] ?? 0);
                $stats['closed'] += (int)($row['closed_count'] ?? 0);
                $stats['urgent'] += (int)($row['urgent_count'] ?? 0);
            } catch (\Exception $e) {
                error_log('[MasterTicket] Error getting stats from tenant ' . $tenant['client_name'] . ': ' . $e->getMessage());
                continue;
            }
        }

        return $stats;
    }

    /**
     * Registra uma resposta/ação no log do master.
     */
    private function logReply(int $adminId, int $tenantClientId, int $ticketId, string $message,
                              string $actionType = 'reply', ?string $oldStatus = null, ?string $newStatus = null): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO master_ticket_replies (admin_id, tenant_client_id, ticket_id, message, action_type, old_status, new_status)
            VALUES (:admin_id, :tenant_client_id, :ticket_id, :message, :action_type, :old_status, :new_status)
        ");
        $stmt->execute([
            'admin_id' => $adminId,
            'tenant_client_id' => $tenantClientId,
            'ticket_id' => $ticketId,
            'message' => $message,
            'action_type' => $actionType,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
        ]);
    }

    // ── Helpers de conexão ──

    /**
     * Retorna todos os tenants ativos com dados de conexão.
     */
    private function getActiveTenantsWithDb(): array
    {
        $stmt = $this->db->query("
            SELECT id, client_name, subdomain, db_host, db_port, db_name, db_user, db_password, db_charset
            FROM tenant_clients
            WHERE is_active = 1
            ORDER BY client_name
        ");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Retorna um tenant por ID.
     */
    private function getTenantById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT id, client_name, subdomain, db_host, db_port, db_name, db_user, db_password, db_charset
            FROM tenant_clients
            WHERE id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Cria conexão PDO para o banco de um tenant.
     */
    private function connectToTenant(array $tenant): ?\PDO
    {
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $tenant['db_host'],
                $tenant['db_port'] ?: 3306,
                $tenant['db_name'],
                $tenant['db_charset'] ?: 'utf8mb4'
            );

            return new \PDO($dsn, $tenant['db_user'], $tenant['db_password'], [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_EMULATE_PREPARES => false,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_TIMEOUT => 5,
            ]);
        } catch (\Exception $e) {
            error_log('[MasterTicket] Cannot connect to tenant ' . $tenant['db_name'] . ': ' . $e->getMessage());
            return null;
        }
    }
}
