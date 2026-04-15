# Sistema de Tickets no Master — Design Completo — Akti v3

> **Data:** 15/04/2026  
> **Objetivo:** Permitir que o admin Master visualize, responda e gerencie tickets abertos pelos clientes (tenants)  
> **Prioridade:** 🟠 ALTA  
> **Complexidade:** Média  
> **Dependências:** Sistema de tickets existente no app principal (`Akti\Models\Ticket`)

---

## 1. Contexto e Justificativa

### 1.1 Sistema Existente no App Principal

O app principal já possui um módulo completo de tickets em cada tenant:

**Tabelas (por tenant DB):**
- `tickets` — Tickets com subject, description, priority, status, SLA, etc.
- `ticket_messages` — Mensagens/respostas no ticket (user_id ou customer_id)
- `ticket_categories` — Categorias de tickets

**Model:** `Akti\Models\Ticket` com métodos completos:
- `create()`, `readAll()`, `readPaginated()`, `readOne()`, `update()`, `delete()`
- `getMessages()`, `addMessage()`, `getCategories()`, `getDashboardStats()`
- `updateStatus()` — com tracking de `resolved_at` e `closed_at`
- `generateTicketNumber()` — formato `TKT-000001`

**Status possíveis:** `open`, `in_progress`, `resolved`, `closed`  
**Prioridades:** `urgent`, `high`, `medium`, `low`  
**Source:** `internal`, `portal`, `email`, `whatsapp`

### 1.2 Problema Atual

O admin Master **não tem visibilidade** dos tickets abertos pelos clientes. Para verificar um ticket, precisaria:
1. Identificar qual tenant abriu o ticket
2. Conectar manualmente ao banco do tenant
3. Consultar a tabela `tickets` via SQL

Isso é inviável operacionalmente.

### 1.3 Solução Proposta

Criar um módulo no Master que:
1. **Lista tickets de TODOS os tenants** em uma view centralizada
2. **Permite filtrar** por tenant, status, prioridade, data
3. **Permite visualizar detalhes** do ticket e mensagens
4. **Permite responder** ao ticket (mensagem fica salva no banco do tenant)
5. **Permite alterar status** do ticket (open → in_progress → resolved → closed)
6. **Dashboard** com métricas consolidadas de tickets de todos os tenants

---

## 2. Arquitetura

### 2.1 Abordagem: Cross-Database Query

Como cada tenant tem seu próprio banco de dados, o módulo de tickets no Master precisa **conectar nos bancos dos tenants** para ler/escrever tickets. A abordagem é similar ao `MigrationController` que já faz cross-tenant queries.

```
┌──────────────────┐     ┌──────────────────┐
│   Master Admin    │     │  akti_master DB   │
│   (Browser)       │     │  - tenant_clients │
└───────┬──────────┘     │  - master_ticket_ │
        │                 │    replies (log)   │
        ▼                 └──────────┬────────┘
┌──────────────────┐               │
│ TicketMaster     │───────────────┘
│ Controller       │
└───────┬──────────┘
        │ connects to each tenant DB
        ▼
┌──────────────┐  ┌──────────────┐  ┌──────────────┐
│ akti_client1 │  │ akti_client2 │  │ akti_clientN │
│ - tickets    │  │ - tickets    │  │ - tickets    │
│ - ticket_    │  │ - ticket_    │  │ - ticket_    │
│   messages   │  │   messages   │  │   messages   │
└──────────────┘  └──────────────┘  └──────────────┘
```

### 2.2 Tabela Auxiliar no Master DB

Para registro e rastreabilidade, criar uma tabela `master_ticket_replies` no `akti_master`:

```sql
CREATE TABLE IF NOT EXISTS `master_ticket_replies` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `admin_id` INT NOT NULL,
    `tenant_client_id` INT NOT NULL,
    `tenant_db_name` VARCHAR(100) NOT NULL,
    `ticket_id` INT NOT NULL,
    `ticket_number` VARCHAR(20) NOT NULL,
    `message` TEXT NOT NULL,
    `action` ENUM('reply', 'status_change', 'assign', 'note') DEFAULT 'reply',
    `new_status` VARCHAR(30) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_admin` (`admin_id`),
    INDEX `idx_tenant` (`tenant_client_id`),
    INDEX `idx_ticket` (`ticket_id`),
    CONSTRAINT `fk_mtr_admin` FOREIGN KEY (`admin_id`) REFERENCES `admin_users`(`id`),
    CONSTRAINT `fk_mtr_tenant` FOREIGN KEY (`tenant_client_id`) REFERENCES `tenant_clients`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 3. Componentes a Criar

### 3.1 Model: `MasterTicket`

**Arquivo:** `master/app/models/MasterTicket.php`

```php
class MasterTicket
{
    private $db; // Master DB connection

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Lista tickets de todos os tenants ativos
     * Conecta em cada banco de tenant e busca tickets
     */
    public function readAllFromAllTenants(array $filters = []): array
    {
        // 1. Buscar todos os tenants ativos
        $tenants = $this->getActiveTenants();
        $allTickets = [];

        foreach ($tenants as $tenant) {
            try {
                $pdo = Database::connectTo(
                    $tenant['db_host'],
                    $tenant['db_port'],
                    DB_USER, DB_PASS,
                    $tenant['db_name']
                );

                $where = 'deleted_at IS NULL';
                $params = [];

                if (!empty($filters['status'])) {
                    $where .= ' AND status = :status';
                    $params[':status'] = $filters['status'];
                }
                if (!empty($filters['priority'])) {
                    $where .= ' AND priority = :priority';
                    $params[':priority'] = $filters['priority'];
                }

                $stmt = $pdo->prepare("
                    SELECT t.*, 
                        (SELECT COUNT(*) FROM ticket_messages WHERE ticket_id = t.id) AS message_count
                    FROM tickets t 
                    WHERE {$where}
                    ORDER BY t.created_at DESC
                    LIMIT 100
                ");
                $stmt->execute($params);
                $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Adicionar info do tenant a cada ticket
                foreach ($tickets as &$ticket) {
                    $ticket['tenant_id_master'] = $tenant['id'];
                    $ticket['tenant_name'] = $tenant['client_name'];
                    $ticket['tenant_subdomain'] = $tenant['subdomain'];
                    $ticket['tenant_db_name'] = $tenant['db_name'];
                }
                unset($ticket);

                $allTickets = array_merge($allTickets, $tickets);
            } catch (PDOException $e) {
                // Log error, skip tenant
                continue;
            }
        }

        // Ordenar por created_at DESC (mais recentes primeiro)
        usort($allTickets, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));
        
        return $allTickets;
    }

    /**
     * Lê um ticket específico de um tenant
     */
    public function readTicketFromTenant(int $tenantClientId, int $ticketId): ?array
    {
        $tenant = $this->getTenant($tenantClientId);
        if (!$tenant) return null;

        $pdo = Database::connectTo(
            $tenant['db_host'], $tenant['db_port'],
            DB_USER, DB_PASS, $tenant['db_name']
        );

        $stmt = $pdo->prepare("
            SELECT t.*, tc.name AS category_name
            FROM tickets t
            LEFT JOIN ticket_categories tc ON t.category_id = tc.id
            WHERE t.id = :id AND t.deleted_at IS NULL
        ");
        $stmt->execute([':id' => $ticketId]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ticket) return null;

        $ticket['tenant_name'] = $tenant['client_name'];
        $ticket['tenant_subdomain'] = $tenant['subdomain'];
        $ticket['tenant_db_name'] = $tenant['db_name'];
        $ticket['tenant_client_id'] = $tenant['id'];

        return $ticket;
    }

    /**
     * Retorna mensagens de um ticket
     */
    public function getTicketMessages(int $tenantClientId, int $ticketId): array
    {
        $tenant = $this->getTenant($tenantClientId);
        if (!$tenant) return [];

        $pdo = Database::connectTo(
            $tenant['db_host'], $tenant['db_port'],
            DB_USER, DB_PASS, $tenant['db_name']
        );

        $stmt = $pdo->prepare("
            SELECT tm.*, u.name AS user_name, c.name AS customer_name
            FROM ticket_messages tm
            LEFT JOIN users u ON tm.user_id = u.id
            LEFT JOIN customers c ON tm.customer_id = c.id
            WHERE tm.ticket_id = :tid
            ORDER BY tm.created_at ASC
        ");
        $stmt->execute([':tid' => $ticketId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Adiciona resposta do admin Master ao ticket (salva no tenant DB + log no master)
     */
    public function replyToTicket(int $adminId, int $tenantClientId, int $ticketId, string $message): array
    {
        $tenant = $this->getTenant($tenantClientId);
        if (!$tenant) return ['success' => false, 'message' => 'Tenant não encontrado'];

        try {
            $pdo = Database::connectTo(
                $tenant['db_host'], $tenant['db_port'],
                DB_USER, DB_PASS, $tenant['db_name']
            );

            // Inserir mensagem no banco do tenant
            // user_id = NULL (é o admin master), customer_id = NULL
            // Usar is_internal_note = 0 para que o cliente veja a resposta
            $stmt = $pdo->prepare("
                INSERT INTO ticket_messages 
                    (tenant_id, ticket_id, user_id, customer_id, message, is_internal_note)
                VALUES 
                    (:tenant_id, :ticket_id, NULL, NULL, :message, 0)
            ");

            // tenant_id no banco do tenant (geralmente 1 ou o id do tenant local)
            // Buscar o tenant_id local
            $tenantLocal = $pdo->query("SELECT id FROM tenants LIMIT 1")->fetch();
            $localTenantId = $tenantLocal ? $tenantLocal['id'] : 1;

            $stmt->execute([
                ':tenant_id' => $localTenantId,
                ':ticket_id' => $ticketId,
                ':message'   => '[Suporte Akti] ' . $message,
            ]);

            // Atualizar first_response_at se ainda não foi respondido
            $pdo->prepare("
                UPDATE tickets SET first_response_at = COALESCE(first_response_at, NOW())
                WHERE id = :id
            ")->execute([':id' => $ticketId]);

            // Buscar ticket_number para log
            $ticketStmt = $pdo->prepare("SELECT ticket_number FROM tickets WHERE id = :id");
            $ticketStmt->execute([':id' => $ticketId]);
            $ticketNumber = $ticketStmt->fetchColumn() ?: 'N/A';

            // Log no master DB
            $this->logReply($adminId, $tenantClientId, $tenant['db_name'], $ticketId, $ticketNumber, $message, 'reply');

            return ['success' => true, 'message' => 'Resposta enviada com sucesso'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erro ao responder: ' . $e->getMessage()];
        }
    }

    /**
     * Altera status de um ticket no tenant
     */
    public function changeTicketStatus(int $adminId, int $tenantClientId, int $ticketId, string $newStatus): array
    {
        $tenant = $this->getTenant($tenantClientId);
        if (!$tenant) return ['success' => false, 'message' => 'Tenant não encontrado'];

        $validStatuses = ['open', 'in_progress', 'resolved', 'closed'];
        if (!in_array($newStatus, $validStatuses)) {
            return ['success' => false, 'message' => 'Status inválido'];
        }

        try {
            $pdo = Database::connectTo(
                $tenant['db_host'], $tenant['db_port'],
                DB_USER, DB_PASS, $tenant['db_name']
            );

            $extra = '';
            if ($newStatus === 'resolved') $extra = ', resolved_at = NOW()';
            elseif ($newStatus === 'closed') $extra = ', closed_at = NOW()';

            $stmt = $pdo->prepare("UPDATE tickets SET status = :status{$extra} WHERE id = :id");
            $stmt->execute([':status' => $newStatus, ':id' => $ticketId]);

            // Buscar ticket_number
            $ticketStmt = $pdo->prepare("SELECT ticket_number FROM tickets WHERE id = :id");
            $ticketStmt->execute([':id' => $ticketId]);
            $ticketNumber = $ticketStmt->fetchColumn() ?: 'N/A';

            // Log no master
            $this->logReply($adminId, $tenantClientId, $tenant['db_name'], $ticketId, $ticketNumber, "Status alterado para: {$newStatus}", 'status_change', $newStatus);

            return ['success' => true, 'message' => "Status alterado para {$newStatus}"];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erro: ' . $e->getMessage()];
        }
    }

    /**
     * Dashboard consolidado de tickets de todos os tenants
     */
    public function getGlobalStats(): array
    {
        $tenants = $this->getActiveTenants();
        $stats = [
            'total' => 0, 'open' => 0, 'in_progress' => 0,
            'resolved' => 0, 'closed' => 0, 'urgent' => 0,
            'sla_breached' => 0, 'tenants_with_tickets' => 0,
            'by_tenant' => [],
        ];

        foreach ($tenants as $tenant) {
            try {
                $pdo = Database::connectTo(
                    $tenant['db_host'], $tenant['db_port'],
                    DB_USER, DB_PASS, $tenant['db_name']
                );

                $stmt = $pdo->query("
                    SELECT
                        COUNT(*) AS total,
                        SUM(status = 'open') AS open_count,
                        SUM(status = 'in_progress') AS in_progress_count,
                        SUM(status = 'resolved') AS resolved_count,
                        SUM(status = 'closed') AS closed_count,
                        SUM(priority = 'urgent' AND status NOT IN ('resolved','closed')) AS urgent_count,
                        SUM(sla_resolution_due < NOW() AND status NOT IN ('resolved','closed')) AS sla_breached
                    FROM tickets WHERE deleted_at IS NULL
                ");
                $row = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($row && $row['total'] > 0) {
                    $stats['total'] += (int)$row['total'];
                    $stats['open'] += (int)$row['open_count'];
                    $stats['in_progress'] += (int)$row['in_progress_count'];
                    $stats['resolved'] += (int)$row['resolved_count'];
                    $stats['closed'] += (int)$row['closed_count'];
                    $stats['urgent'] += (int)$row['urgent_count'];
                    $stats['sla_breached'] += (int)$row['sla_breached'];
                    $stats['tenants_with_tickets']++;
                    $stats['by_tenant'][] = [
                        'tenant_name' => $tenant['client_name'],
                        'tenant_id' => $tenant['id'],
                        'total' => (int)$row['total'],
                        'open' => (int)$row['open_count'],
                    ];
                }
            } catch (PDOException $e) {
                continue;
            }
        }

        return $stats;
    }

    // ─── Helpers ───

    private function getActiveTenants(): array
    {
        $stmt = $this->db->query("SELECT * FROM tenant_clients WHERE is_active = 1 ORDER BY client_name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getTenant(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM tenant_clients WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function logReply(int $adminId, int $tenantClientId, string $dbName, int $ticketId, string $ticketNumber, string $message, string $action, ?string $newStatus = null): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO master_ticket_replies 
                (admin_id, tenant_client_id, tenant_db_name, ticket_id, ticket_number, message, action, new_status)
            VALUES 
                (:admin_id, :tenant_client_id, :db_name, :ticket_id, :ticket_number, :message, :action, :new_status)
        ");
        $stmt->execute([
            ':admin_id'         => $adminId,
            ':tenant_client_id' => $tenantClientId,
            ':db_name'          => $dbName,
            ':ticket_id'        => $ticketId,
            ':ticket_number'    => $ticketNumber,
            ':message'          => $message,
            ':action'           => $action,
            ':new_status'       => $newStatus,
        ]);
    }
}
```

### 3.2 Controller: `TicketMasterController`

**Arquivo:** `master/app/controllers/TicketMasterController.php`

```php
class TicketMasterController
{
    private $db;
    private $ticketModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->ticketModel = new MasterTicket($db);
    }

    /**
     * Lista todos os tickets de todos os tenants
     */
    public function index()
    {
        $filters = [
            'status'   => $_GET['status'] ?? '',
            'priority' => $_GET['priority'] ?? '',
            'tenant'   => $_GET['tenant'] ?? '',
        ];

        $allTickets = $this->ticketModel->readAllFromAllTenants($filters);
        $stats = $this->ticketModel->getGlobalStats();

        // Filtrar por tenant se especificado
        if (!empty($filters['tenant'])) {
            $allTickets = array_filter($allTickets, fn($t) => $t['tenant_id_master'] == $filters['tenant']);
        }

        // Buscar lista de tenants para filtro
        $stmt = $this->db->query("SELECT id, client_name FROM tenant_clients WHERE is_active = 1 ORDER BY client_name");
        $tenants = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $pageTitle = 'Tickets de Suporte';
        $pageSubtitle = 'Gerenciar tickets de todos os clientes';
        require_once __DIR__ . '/../views/tickets/index.php';
    }

    /**
     * Visualiza um ticket específico com mensagens
     */
    public function view()
    {
        $tenantClientId = (int)($_GET['tenant_id'] ?? 0);
        $ticketId = (int)($_GET['ticket_id'] ?? 0);

        $ticket = $this->ticketModel->readTicketFromTenant($tenantClientId, $ticketId);
        if (!$ticket) {
            $_SESSION['error'] = 'Ticket não encontrado.';
            header('Location: ?page=tickets');
            exit;
        }

        $messages = $this->ticketModel->getTicketMessages($tenantClientId, $ticketId);

        // Buscar respostas do master (log)
        $stmt = $this->db->prepare("
            SELECT mtr.*, au.name AS admin_name 
            FROM master_ticket_replies mtr
            JOIN admin_users au ON mtr.admin_id = au.id
            WHERE mtr.tenant_client_id = :tid AND mtr.ticket_id = :ticket_id
            ORDER BY mtr.created_at ASC
        ");
        $stmt->execute([':tid' => $tenantClientId, ':ticket_id' => $ticketId]);
        $masterReplies = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $pageTitle = 'Ticket ' . htmlspecialchars($ticket['ticket_number']);
        $pageSubtitle = 'Cliente: ' . htmlspecialchars($ticket['tenant_name']);
        require_once __DIR__ . '/../views/tickets/view.php';
    }

    /**
     * Responde a um ticket (POST)
     */
    public function reply()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=tickets');
            exit;
        }

        $tenantClientId = (int)($_POST['tenant_client_id'] ?? 0);
        $ticketId = (int)($_POST['ticket_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');

        if (empty($message)) {
            $_SESSION['error'] = 'A mensagem não pode ser vazia.';
            header("Location: ?page=tickets&action=view&tenant_id={$tenantClientId}&ticket_id={$ticketId}");
            exit;
        }

        $result = $this->ticketModel->replyToTicket(
            $_SESSION['admin_id'],
            $tenantClientId,
            $ticketId,
            $message
        );

        // Log na auditoria
        $log = new AdminLog($this->db);
        $log->log($_SESSION['admin_id'], 'reply_ticket', 'ticket', $ticketId, 
            "Respondeu ticket #{$ticketId} do tenant #{$tenantClientId}");

        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }

        header("Location: ?page=tickets&action=view&tenant_id={$tenantClientId}&ticket_id={$ticketId}");
        exit;
    }

    /**
     * Altera status de un ticket (POST)
     */
    public function changeStatus()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=tickets');
            exit;
        }

        $tenantClientId = (int)($_POST['tenant_client_id'] ?? 0);
        $ticketId = (int)($_POST['ticket_id'] ?? 0);
        $newStatus = $_POST['new_status'] ?? '';

        $result = $this->ticketModel->changeTicketStatus(
            $_SESSION['admin_id'],
            $tenantClientId,
            $ticketId,
            $newStatus
        );

        $log = new AdminLog($this->db);
        $log->log($_SESSION['admin_id'], 'change_ticket_status', 'ticket', $ticketId,
            "Alterou status para '{$newStatus}' no ticket #{$ticketId} do tenant #{$tenantClientId}");

        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }

        header("Location: ?page=tickets&action=view&tenant_id={$tenantClientId}&ticket_id={$ticketId}");
        exit;
    }
}
```

### 3.3 Views

#### `master/app/views/tickets/index.php` — Listagem Centralizada

**Layout proposto:**

```
┌─────────────────────────────────────────────────────────┐
│ Tickets de Suporte                                       │
├─────────────────────────────────────────────────────────┤
│ [Card: Total] [Card: Abertos] [Card: Urgentes] [Card: SLA] │
├─────────────────────────────────────────────────────────┤
│ Filtros: [Tenant ▼] [Status ▼] [Prioridade ▼] [Buscar]  │
├─────────────────────────────────────────────────────────┤
│ # | Ticket    | Assunto        | Cliente    | Prioridade | Status   | Data       | Msgs | Ações │
│ 1 | TKT-0001  | Erro no login  | ClienteA   | 🔴 Urgente | Aberto   | 15/04/2026 | 3    | 👁️    │
│ 2 | TKT-0015  | Dúvida sobre.. | ClienteB   | 🟡 Média   | Andamento| 14/04/2026 | 5    | 👁️    │
└─────────────────────────────────────────────────────────┘
```

#### `master/app/views/tickets/view.php` — Detalhe do Ticket

```
┌─────────────────────────────────────────────────────────┐
│ Ticket TKT-000001  |  Cliente: ClienteA                  │
├─────────────────────────────────────────────────────────┤
│ Assunto: Erro ao gerar NF-e                              │
│ Categoria: Fiscal  |  Prioridade: 🔴 Urgente             │
│ Status: [Aberto ▼] [Alterar Status]                      │
│ SLA Resposta: 15/04 14:00  |  SLA Resolução: 16/04 18:00 │
├─────────────────────────────────────────────────────────┤
│ MENSAGENS                                                │
│ ┌─────────────────────────────────────┐                  │
│ │ [Cliente] João Silva - 15/04 10:30  │                  │
│ │ Estou com erro ao emitir NF-e...    │                  │
│ └─────────────────────────────────────┘                  │
│ ┌─────────────────────────────────────┐                  │
│ │ [Suporte Akti] Admin - 15/04 11:00 │                  │
│ │ Verificamos e o certificado...      │                  │
│ └─────────────────────────────────────┘                  │
├─────────────────────────────────────────────────────────┤
│ RESPONDER                                                │
│ [Textarea: Digite sua resposta...]                       │
│                              [Enviar Resposta]           │
└─────────────────────────────────────────────────────────┘
```

---

## 4. Roteamento

Adicionar ao `master/index.php`:

```php
case 'tickets':
    $controller = new TicketMasterController($db);
    switch ($action) {
        case 'view':          $controller->view(); break;
        case 'reply':         $controller->reply(); break;
        case 'changeStatus':  $controller->changeStatus(); break;
        default:              $controller->index(); break;
    }
    break;
```

### Menu (header.php)

Adicionar na seção "Gestão" do sidebar:

```html
<div class="nav-item">
    <a href="?page=tickets" class="nav-link <?= $currentPage === 'tickets' ? 'active' : '' ?>">
        <i class="fas fa-headset"></i>
        Tickets
        <?php if (isset($openTicketsCount) && $openTicketsCount > 0): ?>
            <span class="badge bg-danger ms-auto"><?= $openTicketsCount ?></span>
        <?php endif; ?>
    </a>
</div>
```

---

## 5. Fluxo de Interação

### 5.1 Fluxo Principal

```
Admin abre ?page=tickets
    → TicketMasterController::index()
    → MasterTicket::readAllFromAllTenants() 
        → Loop em cada tenant ativo
        → Database::connectTo() para cada DB
        → SELECT tickets com filtros
    → Renderiza view com todos os tickets consolidados

Admin clica em um ticket
    → ?page=tickets&action=view&tenant_id=X&ticket_id=Y
    → MasterTicket::readTicketFromTenant()
    → MasterTicket::getTicketMessages()
    → Renderiza detalhes + mensagens + form de resposta

Admin responde
    → POST ?page=tickets&action=reply
    → MasterTicket::replyToTicket()
        → INSERT em ticket_messages do tenant (prefixo [Suporte Akti])
        → INSERT em master_ticket_replies (log local)
        → UPDATE first_response_at se primeira resposta
    → AdminLog::log()
    → Redirect com sucesso

Admin muda status
    → POST ?page=tickets&action=changeStatus
    → MasterTicket::changeTicketStatus()
        → UPDATE status em tickets do tenant
        → INSERT em master_ticket_replies (log)
    → AdminLog::log()
    → Redirect com sucesso
```

### 5.2 Identificação de Resposta no Tenant

As respostas do Master são inseridas na tabela `ticket_messages` do tenant com:
- `user_id = NULL` (não é um usuário do tenant)
- `customer_id = NULL` (não é um cliente)
- `message` prefixada com `[Suporte Akti]` para identificação visual

**Alternativa futura:** Adicionar coluna `source ENUM('internal','customer','master_admin')` à tabela `ticket_messages` em cada tenant, usando migration cross-tenant.

---

## 6. Considerações de Performance

### 6.1 Problema: N+1 Connections

A listagem de tickets faz 1 conexão por tenant ativo. Com 50 tenants, são 50 conexões de banco sequenciais.

### 6.2 Mitigações

1. **Limite por tenant:** `LIMIT 100` por tenant na query
2. **Cache em sessão:** Cachear resultado por 60 segundos em `$_SESSION['tickets_cache']`
3. **Paginação:** Implementar paginação lazy (carregar primeiro 10 tenants, depois os próximos)
4. **Filtro obrigatório:** Em produção com muitos tenants, exigir filtro por tenant ou status

### 6.3 Evolução Futura

- **Tabela espelho:** Criar `master_tickets_mirror` no `akti_master` que sincroniza periodicamente via cron
- **Webhook:** Tenants notificam o Master via API quando novo ticket é criado
- **Push notifications:** WebSocket ou polling para notificar admin de novos tickets

---

## 7. Segurança

| Aspecto | Proteção |
|---------|----------|
| SQL Injection | Prepared statements em toda query |
| XSS | `htmlspecialchars()` em toda saída na view |
| CSRF | Token em forms de reply e changeStatus |
| Auth | Somente admin logado acessa módulo |
| Conexão tenant | Usa credenciais master (DB_USER/DB_PASS), não as do tenant |
| Log/Audit | Toda ação logada em `admin_logs` + `master_ticket_replies` |
| Input validation | Message trim + empty check, status whitelist |

---

## 8. Checklist de Implementação

- [ ] Criar tabela `master_ticket_replies` no `akti_master` (usar skill sql-migration)
- [ ] Criar model `master/app/models/MasterTicket.php`
- [ ] Criar controller `master/app/controllers/TicketMasterController.php`
- [ ] Criar view `master/app/views/tickets/index.php`
- [ ] Criar view `master/app/views/tickets/view.php`
- [ ] Adicionar rota `tickets` no `master/index.php`
- [ ] Adicionar item "Tickets" no sidebar (`master/app/views/layout/header.php`)
- [ ] Adicionar badge com contagem de tickets abertos no sidebar
- [ ] Testar com múltiplos tenants
- [ ] Verificar CSRF em todos os forms
