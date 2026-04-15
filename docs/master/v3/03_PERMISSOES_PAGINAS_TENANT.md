# Permissões de Páginas por Tenant — Design Completo — Akti v3

> **Data:** 15/04/2026  
> **Objetivo:** Controlar quais páginas cada tenant pode acessar, similar ao sistema de permissões de grupo mas no nível do tenant  
> **Prioridade:** 🔴 CRÍTICA  
> **Complexidade:** Média-Alta  
> **Dependências:** `app/config/menu.php`, `app/core/Application.php`, `app/core/ModuleBootloader.php`

---

## 1. Contexto e Justificativa

### 1.1 Sistemas de Permissão Atuais

O Akti possui **três camadas** de controle de acesso:

| Camada | Nível | Onde funciona | Status |
|--------|-------|--------------|--------|
| **Role** | Usuário | `user.role = 'admin'/'user'` | ✅ Funcional |
| **Group Permission** | Grupo de Usuário | `group_permissions.page_name` | ✅ Funcional |
| **Module Bootloader** | Tenant | `enabled_modules` JSON | ✅ Funcional (limitado) |

### 1.2 Limitação do ModuleBootloader

O `ModuleBootloader` opera com uma mapa fixa de apenas **4 módulos**:

```php
PAGE_MODULE_MAP = [
    'financial'         => 'financial',
    'nfe_credentials'   => 'nfe',
    'nfe_documents'     => 'nfe',
    'payment_gateways'  => 'payment_gateways',
]
```

Isso significa que **todas as outras páginas** (products, customers, orders, pipeline, reports, email_marketing, site_builder, quality, equipment, bi, workflows, achievements, esg, ai_assistant, etc.) **NÃO podem ser bloqueadas por tenant**.

### 1.3 Cenário Desejado

O admin Master precisa poder:
1. **Bloquear páginas específicas** para um tenant (ex: tenant no plano básico não acessa `bi`, `ai_assistant`, `email_marketing`)
2. **Liberar páginas** que estavam bloqueadas (upgrade de plano)
3. **Vincular permissões a planos** (plano "Básico" tem 10 páginas, plano "Pro" tem 25, plano "Enterprise" tem todas)
4. **Tela no Master** para gerenciar essas permissões por tenant
5. **Enforcement no app principal** — páginas bloqueadas não carregam e não aparecem no menu

---

## 2. Arquitetura da Solução

### 2.1 Abordagem: Tabela no Master DB

Criar tabela `tenant_page_permissions` no `akti_master` que registra **quais páginas cada tenant pode acessar**.

**Lógica:** 
- Se a tabela está **vazia** para um tenant → **acesso total** (comportamento atual, retrocompatível)
- Se existe **pelo menos 1 registro** para um tenant → **somente páginas listadas são acessíveis** (whitelist)

```
┌───────────────────────┐
│     akti_master       │
│                       │
│ tenant_page_permissions│
│  - tenant_client_id   │──┐
│  - page_name          │  │
│  - granted_by         │  │  ┌─────────────────┐
│  - created_at         │  │  │ tenant_clients   │
│                       │  └─►│  - id            │
│ plan_page_permissions │     │  - plan_id       │
│  - plan_id            │──┐  │  - client_name   │
│  - page_name          │  │  └─────────────────┘
│  - created_at         │  │  ┌─────────────────┐
│                       │  └─►│ plans            │
└───────────────────────┘     │  - id            │
                              │  - plan_name     │
                              └─────────────────┘
```

### 2.2 Fluxo de Verificação no App Principal

```
Requisição HTTP
    │
    ▼
Application::handle()
    │
    ├── 1. Auth check (login obrigatório?)
    │
    ├── 2. ModuleBootloader::canAccessPage() ← Módulos (existente)
    │
    ├── 3. ★ TenantPagePermission::canAccess() ← NOVO
    │   │
    │   ├── Busca permissões do tenant no akti_master
    │   ├── Se nenhuma permissão registrada → permite tudo
    │   ├── Se tem permissões → verifica se page está na whitelist
    │   └── Bloqueia com alerta se não tem permissão
    │
    ├── 4. Application::checkPermissions() ← Grupos (existente)
    │
    └── Renderiza página
```

### 2.3 Impacto no Menu

Páginas sem permissão de tenant **não devem aparecer no menu**. Isso requer alterar o `header.php` do app principal para filtrar itens do menu baseado nas permissões do tenant.

---

## 3. Schema SQL

### 3.1 Tabela: `tenant_page_permissions` (akti_master)

```sql
CREATE TABLE IF NOT EXISTS `tenant_page_permissions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tenant_client_id` INT NOT NULL,
    `page_name` VARCHAR(80) NOT NULL,
    `granted_by` INT NULL COMMENT 'admin_id que concedeu a permissão',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_tenant_page` (`tenant_client_id`, `page_name`),
    INDEX `idx_tenant` (`tenant_client_id`),
    CONSTRAINT `fk_tpp_tenant` FOREIGN KEY (`tenant_client_id`) REFERENCES `tenant_clients`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_tpp_admin` FOREIGN KEY (`granted_by`) REFERENCES `admin_users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3.2 Tabela: `plan_page_permissions` (akti_master)

Permissões padrão por plano (template):

```sql
CREATE TABLE IF NOT EXISTS `plan_page_permissions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `plan_id` INT NOT NULL,
    `page_name` VARCHAR(80) NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_plan_page` (`plan_id`, `page_name`),
    INDEX `idx_plan` (`plan_id`),
    CONSTRAINT `fk_ppp_plan` FOREIGN KEY (`plan_id`) REFERENCES `plans`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 4. Páginas Disponíveis para Controle

Lista completa extraída de `app/config/menu.php` (páginas com `permission: true`):

### 4.1 Grupo: Comercial
| page_name | Label |
|-----------|-------|
| `customers` | Clientes |
| `orders` | Pedidos |
| `quotes` | Orçamentos |
| `agenda` | Agenda de Contatos |
| `calendar` | Calendário |
| `price_tables` | Tabelas de Preço |
| `suppliers` | Fornecedores |
| `tickets` | Tickets / Suporte |
| `whatsapp` | WhatsApp |

### 4.2 Grupo: Catálogo
| page_name | Label |
|-----------|-------|
| `products` | Produtos |
| `categories` | Categorias |
| `stock` | Controle de Estoque |
| `supplies` | Insumos |
| `supply_stock` | Estoque de Insumos |

### 4.3 Grupo: Produção
| page_name | Label |
|-----------|-------|
| `pipeline` | Linha de Produção |
| `production_board` | Painel de Produção |
| `sectors` | Setores |
| `quality` | Qualidade |
| `equipment` | Equipamentos |
| `production_costs` | Custos de Produção |
| `shipments` | Entregas |

### 4.4 Grupo: Fiscal
| page_name | Label |
|-----------|-------|
| `financial` | Financeiro |
| `commissions` | Comissões |
| `payment_gateways` | Gateways de Pagamento |
| `nfe_documents` | Notas Fiscais (NF-e) |
| `nfe_credentials` | Credenciais SEFAZ |

### 4.5 Grupo: Ferramentas
| page_name | Label |
|-----------|-------|
| `reports` | Relatórios |
| `custom_reports` | Relatórios Customizados |
| `bi` | Business Intelligence |
| `site_builder` | Site Builder |
| `workflows` | Automações |
| `email_marketing` | E-mail Marketing |
| `attachments` | Anexos |
| `audit` | Auditoria |
| `branches` | Filiais |
| `achievements` | Gamificação |
| `esg` | ESG / Sustentabilidade |
| `ai_assistant` | Assistente IA |

### 4.6 Globais (sem grupo)
| page_name | Label |
|-----------|-------|
| `settings` | Configurações |
| `users` | Gestão de Usuários |
| `portal_admin` | Admin do Portal |

**Total: 36 páginas controláveis**

---

## 5. Componentes a Criar

### 5.1 Model no Master: `TenantPagePermission`

**Arquivo:** `master/app/models/TenantPagePermission.php`

```php
class TenantPagePermission
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Retorna páginas permitidas para um tenant
     * Se vazio, retorna array vazio (significa: permitir tudo)
     */
    public function getPermissions(int $tenantClientId): array
    {
        $stmt = $this->db->prepare("
            SELECT page_name FROM tenant_page_permissions 
            WHERE tenant_client_id = :tid
            ORDER BY page_name
        ");
        $stmt->execute([':tid' => $tenantClientId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Verifica se um tenant tem permissão restrita (pelo menos 1 registro)
     */
    public function hasRestrictions(int $tenantClientId): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM tenant_page_permissions 
            WHERE tenant_client_id = :tid
        ");
        $stmt->execute([':tid' => $tenantClientId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Define permissões de um tenant (substitui todas)
     */
    public function setPermissions(int $tenantClientId, array $pages, int $adminId): void
    {
        $this->db->beginTransaction();
        try {
            // Remover permissões existentes
            $stmt = $this->db->prepare("DELETE FROM tenant_page_permissions WHERE tenant_client_id = :tid");
            $stmt->execute([':tid' => $tenantClientId]);

            // Inserir novas permissões
            if (!empty($pages)) {
                $stmt = $this->db->prepare("
                    INSERT INTO tenant_page_permissions (tenant_client_id, page_name, granted_by)
                    VALUES (:tid, :page, :admin)
                ");
                foreach ($pages as $page) {
                    $stmt->execute([
                        ':tid'   => $tenantClientId,
                        ':page'  => $page,
                        ':admin' => $adminId,
                    ]);
                }
            }

            $this->db->commit();
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Copia permissões de um plano para um tenant
     */
    public function applyPlanPermissions(int $tenantClientId, int $planId, int $adminId): void
    {
        $stmt = $this->db->prepare("SELECT page_name FROM plan_page_permissions WHERE plan_id = :pid");
        $stmt->execute([':pid' => $planId]);
        $pages = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $this->setPermissions($tenantClientId, $pages, $adminId);
    }

    /**
     * Remove todas as restrições (voltar ao acesso total)
     */
    public function removeRestrictions(int $tenantClientId): void
    {
        $stmt = $this->db->prepare("DELETE FROM tenant_page_permissions WHERE tenant_client_id = :tid");
        $stmt->execute([':tid' => $tenantClientId]);
    }

    // ─── Plan Permissions (templates) ───

    /**
     * Retorna permissões de um plano
     */
    public function getPlanPermissions(int $planId): array
    {
        $stmt = $this->db->prepare("SELECT page_name FROM plan_page_permissions WHERE plan_id = :pid ORDER BY page_name");
        $stmt->execute([':pid' => $planId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Define permissões de um plano
     */
    public function setPlanPermissions(int $planId, array $pages): void
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("DELETE FROM plan_page_permissions WHERE plan_id = :pid");
            $stmt->execute([':pid' => $planId]);

            if (!empty($pages)) {
                $stmt = $this->db->prepare("
                    INSERT INTO plan_page_permissions (plan_id, page_name)
                    VALUES (:pid, :page)
                ");
                foreach ($pages as $page) {
                    $stmt->execute([':pid' => $planId, ':page' => $page]);
                }
            }

            $this->db->commit();
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Sync: Aplica permissões do plano a todos os tenants desse plano
     */
    public function syncPlanToAllTenants(int $planId, int $adminId): int
    {
        $stmt = $this->db->prepare("SELECT id FROM tenant_clients WHERE plan_id = :pid AND is_active = 1");
        $stmt->execute([':pid' => $planId]);
        $tenants = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tenants as $tenantId) {
            $this->applyPlanPermissions($tenantId, $planId, $adminId);
        }

        return count($tenants);
    }
}
```

### 5.2 Controller no Master: `TenantPermissionController`

**Arquivo:** `master/app/controllers/TenantPermissionController.php`

```php
class TenantPermissionController
{
    private $db;
    private $permModel;
    private $clientModel;
    private $planModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->permModel = new TenantPagePermission($db);
        $this->clientModel = new TenantClient($db);
        $this->planModel = new Plan($db);
    }

    /**
     * Editar permissões de um tenant específico
     * URL: ?page=permissions&action=edit&id=X
     */
    public function edit()
    {
        $id = (int)($_GET['id'] ?? 0);
        $client = $this->clientModel->readOne($id);

        if (!$client) {
            $_SESSION['error'] = 'Cliente não encontrado.';
            header('Location: ?page=clients');
            exit;
        }

        $currentPermissions = $this->permModel->getPermissions($id);
        $hasRestrictions = $this->permModel->hasRestrictions($id);
        $plans = $this->planModel->readActive();
        $allPages = $this->getAllPages(); // Lista de menu.php

        $pageTitle = 'Permissões de Páginas';
        $pageSubtitle = 'Cliente: ' . htmlspecialchars($client['client_name']);
        require_once __DIR__ . '/../views/permissions/edit.php';
    }

    /**
     * Salvar permissões de um tenant (POST)
     */
    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=clients');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $mode = $_POST['permission_mode'] ?? 'unrestricted';
        $pages = $_POST['pages'] ?? [];

        $client = $this->clientModel->readOne($id);
        if (!$client) {
            $_SESSION['error'] = 'Cliente não encontrado.';
            header('Location: ?page=clients');
            exit;
        }

        if ($mode === 'unrestricted') {
            // Remover todas as restrições
            $this->permModel->removeRestrictions($id);
            $_SESSION['success'] = "Restrições removidas. Cliente '{$client['client_name']}' tem acesso total.";
        } else {
            // Aplicar whitelist de páginas
            // Sanitizar: manter apenas page_names válidos
            $validPages = array_column($this->getAllPages(), 'page_name');
            $pages = array_intersect($pages, $validPages);

            $this->permModel->setPermissions($id, $pages, $_SESSION['admin_id']);
            $_SESSION['success'] = "Permissões atualizadas para '{$client['client_name']}'. " . count($pages) . " páginas permitidas.";
        }

        $log = new AdminLog($this->db);
        $log->log($_SESSION['admin_id'], 'update_permissions', 'client', $id,
            "Permissões de páginas atualizadas para '{$client['client_name']}' (modo: {$mode}, páginas: " . count($pages) . ")");

        header("Location: ?page=permissions&action=edit&id={$id}");
        exit;
    }

    /**
     * Aplicar permissões do plano ao tenant
     */
    public function applyPlan()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=clients');
            exit;
        }

        $tenantId = (int)($_POST['tenant_id'] ?? 0);
        $planId = (int)($_POST['plan_id'] ?? 0);

        $client = $this->clientModel->readOne($tenantId);
        $plan = $this->planModel->readOne($planId);

        if (!$client || !$plan) {
            $_SESSION['error'] = 'Cliente ou plano não encontrado.';
            header('Location: ?page=clients');
            exit;
        }

        $this->permModel->applyPlanPermissions($tenantId, $planId, $_SESSION['admin_id']);

        $log = new AdminLog($this->db);
        $log->log($_SESSION['admin_id'], 'apply_plan_permissions', 'client', $tenantId,
            "Permissões do plano '{$plan['plan_name']}' aplicadas ao cliente '{$client['client_name']}'");

        $_SESSION['success'] = "Permissões do plano '{$plan['plan_name']}' aplicadas ao cliente '{$client['client_name']}'.";
        header("Location: ?page=permissions&action=edit&id={$tenantId}");
        exit;
    }

    /**
     * Editar permissões padrão de um plano
     * URL: ?page=permissions&action=editPlan&id=X
     */
    public function editPlan()
    {
        $planId = (int)($_GET['id'] ?? 0);
        $plan = $this->planModel->readOne($planId);

        if (!$plan) {
            $_SESSION['error'] = 'Plano não encontrado.';
            header('Location: ?page=plans');
            exit;
        }

        $currentPermissions = $this->permModel->getPlanPermissions($planId);
        $allPages = $this->getAllPages();

        $pageTitle = 'Permissões do Plano';
        $pageSubtitle = 'Plano: ' . htmlspecialchars($plan['plan_name']);
        require_once __DIR__ . '/../views/permissions/edit_plan.php';
    }

    /**
     * Salvar permissões do plano (POST)
     */
    public function updatePlan()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=plans');
            exit;
        }

        $planId = (int)($_POST['plan_id'] ?? 0);
        $pages = $_POST['pages'] ?? [];
        $syncToTenants = isset($_POST['sync_to_tenants']);

        $plan = $this->planModel->readOne($planId);
        if (!$plan) {
            $_SESSION['error'] = 'Plano não encontrado.';
            header('Location: ?page=plans');
            exit;
        }

        // Sanitizar
        $validPages = array_column($this->getAllPages(), 'page_name');
        $pages = array_intersect($pages, $validPages);

        $this->permModel->setPlanPermissions($planId, $pages);

        $msg = "Permissões do plano '{$plan['plan_name']}' atualizadas (" . count($pages) . " páginas).";

        if ($syncToTenants) {
            $synced = $this->permModel->syncPlanToAllTenants($planId, $_SESSION['admin_id']);
            $msg .= " Aplicado a {$synced} tenants.";
        }

        $log = new AdminLog($this->db);
        $log->log($_SESSION['admin_id'], 'update_plan_permissions', 'plan', $planId, $msg);

        $_SESSION['success'] = $msg;
        header("Location: ?page=permissions&action=editPlan&id={$planId}");
        exit;
    }

    /**
     * Retorna todas as páginas disponíveis para controle
     * Extraído de menu.php do app principal
     */
    private function getAllPages(): array
    {
        // Lista hardcoded das páginas de menu.php com permission=true
        // Manter sincronizado com app/config/menu.php
        return [
            // Comercial
            ['page_name' => 'customers',       'label' => 'Clientes',              'group' => 'Comercial',    'icon' => 'fas fa-users'],
            ['page_name' => 'orders',           'label' => 'Pedidos',               'group' => 'Comercial',    'icon' => 'fas fa-shopping-cart'],
            ['page_name' => 'quotes',           'label' => 'Orçamentos',            'group' => 'Comercial',    'icon' => 'fas fa-file-alt'],
            ['page_name' => 'agenda',           'label' => 'Agenda de Contatos',    'group' => 'Comercial',    'icon' => 'fas fa-calendar-alt'],
            ['page_name' => 'calendar',         'label' => 'Calendário',            'group' => 'Comercial',    'icon' => 'fas fa-calendar'],
            ['page_name' => 'price_tables',     'label' => 'Tabelas de Preço',      'group' => 'Comercial',    'icon' => 'fas fa-tags'],
            ['page_name' => 'suppliers',        'label' => 'Fornecedores',          'group' => 'Comercial',    'icon' => 'fas fa-truck'],
            ['page_name' => 'tickets',          'label' => 'Tickets / Suporte',     'group' => 'Comercial',    'icon' => 'fas fa-headset'],
            ['page_name' => 'whatsapp',         'label' => 'WhatsApp',              'group' => 'Comercial',    'icon' => 'fab fa-whatsapp'],
            // Catálogo
            ['page_name' => 'products',         'label' => 'Produtos',              'group' => 'Catálogo',     'icon' => 'fas fa-box-open'],
            ['page_name' => 'categories',       'label' => 'Categorias',            'group' => 'Catálogo',     'icon' => 'fas fa-folder-open'],
            ['page_name' => 'stock',            'label' => 'Controle de Estoque',   'group' => 'Catálogo',     'icon' => 'fas fa-warehouse'],
            ['page_name' => 'supplies',         'label' => 'Insumos',               'group' => 'Catálogo',     'icon' => 'fas fa-boxes-stacked'],
            ['page_name' => 'supply_stock',     'label' => 'Estoque de Insumos',    'group' => 'Catálogo',     'icon' => 'fas fa-cubes'],
            // Produção
            ['page_name' => 'pipeline',         'label' => 'Linha de Produção',     'group' => 'Produção',     'icon' => 'fas fa-stream'],
            ['page_name' => 'production_board', 'label' => 'Painel de Produção',    'group' => 'Produção',     'icon' => 'fas fa-tasks'],
            ['page_name' => 'sectors',          'label' => 'Setores',               'group' => 'Produção',     'icon' => 'fas fa-industry'],
            ['page_name' => 'quality',          'label' => 'Qualidade',             'group' => 'Produção',     'icon' => 'fas fa-clipboard-check'],
            ['page_name' => 'equipment',        'label' => 'Equipamentos',          'group' => 'Produção',     'icon' => 'fas fa-tools'],
            ['page_name' => 'production_costs', 'label' => 'Custos de Produção',    'group' => 'Produção',     'icon' => 'fas fa-calculator'],
            ['page_name' => 'shipments',        'label' => 'Entregas',              'group' => 'Produção',     'icon' => 'fas fa-shipping-fast'],
            // Fiscal
            ['page_name' => 'financial',        'label' => 'Financeiro',            'group' => 'Fiscal',       'icon' => 'fas fa-file-invoice-dollar'],
            ['page_name' => 'commissions',      'label' => 'Comissões',             'group' => 'Fiscal',       'icon' => 'fas fa-hand-holding-usd'],
            ['page_name' => 'payment_gateways', 'label' => 'Gateways de Pagamento', 'group' => 'Fiscal',       'icon' => 'fas fa-credit-card'],
            ['page_name' => 'nfe_documents',    'label' => 'Notas Fiscais (NF-e)',  'group' => 'Fiscal',       'icon' => 'fas fa-file-invoice'],
            ['page_name' => 'nfe_credentials',  'label' => 'Credenciais SEFAZ',     'group' => 'Fiscal',       'icon' => 'fas fa-certificate'],
            // Ferramentas
            ['page_name' => 'reports',          'label' => 'Relatórios',            'group' => 'Ferramentas',  'icon' => 'fas fa-chart-bar'],
            ['page_name' => 'custom_reports',   'label' => 'Relatórios Custom.',    'group' => 'Ferramentas',  'icon' => 'fas fa-chart-line'],
            ['page_name' => 'bi',               'label' => 'Business Intelligence', 'group' => 'Ferramentas',  'icon' => 'fas fa-chart-area'],
            ['page_name' => 'site_builder',     'label' => 'Site Builder',          'group' => 'Ferramentas',  'icon' => 'fas fa-palette'],
            ['page_name' => 'workflows',        'label' => 'Automações',            'group' => 'Ferramentas',  'icon' => 'fas fa-cogs'],
            ['page_name' => 'email_marketing',  'label' => 'E-mail Marketing',      'group' => 'Ferramentas',  'icon' => 'fas fa-envelope'],
            ['page_name' => 'attachments',      'label' => 'Anexos',                'group' => 'Ferramentas',  'icon' => 'fas fa-paperclip'],
            ['page_name' => 'audit',            'label' => 'Auditoria',             'group' => 'Ferramentas',  'icon' => 'fas fa-history'],
            ['page_name' => 'branches',         'label' => 'Filiais',               'group' => 'Ferramentas',  'icon' => 'fas fa-building'],
            ['page_name' => 'achievements',     'label' => 'Gamificação',           'group' => 'Ferramentas',  'icon' => 'fas fa-trophy'],
            ['page_name' => 'esg',              'label' => 'ESG / Sustentabilidade','group' => 'Ferramentas',  'icon' => 'fas fa-leaf'],
            ['page_name' => 'ai_assistant',     'label' => 'Assistente IA',         'group' => 'Ferramentas',  'icon' => 'fas fa-robot'],
            // Globais
            ['page_name' => 'settings',         'label' => 'Configurações',         'group' => 'Sistema',      'icon' => 'fas fa-building'],
            ['page_name' => 'users',            'label' => 'Gestão de Usuários',    'group' => 'Sistema',      'icon' => 'fas fa-users-cog'],
            ['page_name' => 'portal_admin',     'label' => 'Admin do Portal',       'group' => 'Sistema',      'icon' => 'fas fa-globe'],
        ];
    }
}
```

### 5.3 Views

#### `master/app/views/permissions/edit.php` — Permissões por Tenant

**Layout proposto:**

```
┌─────────────────────────────────────────────────────────────┐
│ Permissões de Páginas — Cliente: Empresa XYZ                 │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│ Modo de Acesso:                                              │
│ (●) Acesso Total (sem restrições)                            │
│ ( ) Acesso Restrito (apenas páginas selecionadas)            │
│                                                              │
│ [Aplicar do Plano ▼] [Aplicar]                               │
│                                                              │
├─────────────────────────────────────────────────────────────┤
│ ▼ Comercial                         [Marcar Todos]           │
│   ☑ Clientes            ☑ Pedidos          ☑ Orçamentos     │
│   ☑ Agenda               ☐ Calendário       ☑ Tab. Preço    │
│   ☐ Fornecedores         ☑ Tickets          ☐ WhatsApp      │
│                                                              │
│ ▼ Catálogo                          [Marcar Todos]           │
│   ☑ Produtos            ☑ Categorias       ☑ Estoque        │
│   ☐ Insumos             ☐ Estoque Insumos                   │
│                                                              │
│ ▼ Produção                          [Marcar Todos]           │
│   ☑ Linha de Produção   ☑ Painel           ☑ Setores        │
│   ☐ Qualidade           ☐ Equipamentos     ☐ Custos         │
│   ☑ Entregas                                                 │
│                                                              │
│ ▼ Fiscal                            [Marcar Todos]           │
│   ☑ Financeiro          ☐ Comissões        ☐ Gateways       │
│   ☐ NF-e                ☐ SEFAZ                             │
│                                                              │
│ ▼ Ferramentas                       [Marcar Todos]           │
│   ☑ Relatórios          ☐ Rel. Custom.     ☐ BI             │
│   ☐ Site Builder        ☐ Automações       ☐ E-mail Mkt     │
│   ☑ Anexos              ☐ Auditoria        ☐ Filiais        │
│   ☐ Gamificação         ☐ ESG              ☐ IA             │
│                                                              │
│ ▼ Sistema                           [Marcar Todos]           │
│   ☑ Configurações       ☑ Usuários         ☐ Portal Admin   │
│                                                              │
│ [15/36 páginas selecionadas]                                 │
│                                                              │
│                              [Cancelar]  [Salvar Permissões] │
└─────────────────────────────────────────────────────────────┘
```

#### `master/app/views/permissions/edit_plan.php` — Permissões Padrão do Plano

Mesmo layout, mas com checkbox adicional:
```
☐ Sincronizar automaticamente com todos os tenants deste plano
```

---

## 6. Enforcement no App Principal

### 6.1 Modificação em `app/core/Application.php`

Adicionar verificação após o `ModuleBootloader` check:

```php
// Em Application::handle(), após ModuleBootloader::canAccessPage():

// Verificar permissões de página do tenant (definidas pelo Master)
if (!$this->checkTenantPagePermission($this->page)) {
    // Página bloqueada para este tenant
    $_SESSION['error'] = 'Seu plano não inclui acesso a esta funcionalidade. Entre em contato com o suporte.';
    header('Location: ?page=home');
    exit;
}
```

### 6.2 Novo Método: `checkTenantPagePermission()`

```php
private function checkTenantPagePermission(string $page): bool
{
    // Páginas que nunca são bloqueadas (essenciais)
    $alwaysAllowed = ['home', 'dashboard', 'profile', 'login', 'logout'];
    if (in_array($page, $alwaysAllowed)) {
        return true;
    }

    // Buscar permissões do tenant no master DB
    $tenantPermissions = $this->getTenantPermissionsFromMaster();

    // Se não há restrições (array vazio), permitir tudo
    if (empty($tenantPermissions)) {
        return true;
    }

    // Verificar se a página está na whitelist
    return in_array($page, $tenantPermissions);
}

private function getTenantPermissionsFromMaster(): array
{
    // Cache na sessão para evitar consulta ao master a cada request
    if (isset($_SESSION['tenant_page_permissions'])) {
        return $_SESSION['tenant_page_permissions'];
    }

    try {
        // Conectar ao master DB
        $masterPdo = new PDO(
            'mysql:host=' . MASTER_DB_HOST . ';port=' . MASTER_DB_PORT . ';dbname=' . MASTER_DB_NAME,
            MASTER_DB_USER, MASTER_DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );

        // Buscar tenant pelo subdomain ou db_name
        $dbName = DB_NAME; // Banco atual do tenant
        $stmt = $masterPdo->prepare("
            SELECT tpp.page_name 
            FROM tenant_page_permissions tpp
            JOIN tenant_clients tc ON tpp.tenant_client_id = tc.id
            WHERE tc.db_name = :db_name
        ");
        $stmt->execute([':db_name' => $dbName]);
        $permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Cachear na sessão (invalidado no login/logout ou por TTL)
        $_SESSION['tenant_page_permissions'] = $permissions;
        $_SESSION['tenant_page_permissions_ts'] = time();

        return $permissions;
    } catch (PDOException $e) {
        // Se falhar conexão com master, permitir tudo (fail-open)
        return [];
    }
}
```

### 6.3 Invalidação de Cache

O cache em `$_SESSION['tenant_page_permissions']` deve ser invalidado:
1. **No login** — sempre buscar permissões frescas
2. **Por TTL** — a cada 5 minutos, forçar nova consulta
3. **Manualmente** — admin Master pode forçar invalidação via migration cross-tenant

```php
// No checkTenantPagePermission(), antes de retornar cache:
$ttl = 300; // 5 minutos
if (isset($_SESSION['tenant_page_permissions_ts']) && 
    (time() - $_SESSION['tenant_page_permissions_ts']) > $ttl) {
    unset($_SESSION['tenant_page_permissions']);
    // Buscar novamente...
}
```

### 6.4 Filtro no Menu (header.php do app principal)

Modificar a renderização do menu para ocultar páginas sem permissão de tenant:

```php
// Em app/views/layout/header.php, ao iterar menu.php:
$tenantPermissions = $_SESSION['tenant_page_permissions'] ?? [];
$hasRestrictions = !empty($tenantPermissions);

foreach ($menuItems as $key => $item) {
    // Se tem restrições e a página não está na whitelist, pular
    if ($hasRestrictions && isset($item['permission']) && $item['permission'] && !in_array($key, $tenantPermissions)) {
        continue; // Não renderizar este item
    }
    // ... renderizar normalmente
}
```

---

## 7. Roteamento no Master

Adicionar ao `master/index.php`:

```php
case 'permissions':
    $controller = new TenantPermissionController($db);
    switch ($action) {
        case 'edit':       $controller->edit(); break;
        case 'update':     $controller->update(); break;
        case 'applyPlan':  $controller->applyPlan(); break;
        case 'editPlan':   $controller->editPlan(); break;
        case 'updatePlan': $controller->updatePlan(); break;
        default:           header('Location: ?page=clients'); exit;
    }
    break;
```

### Integração com Views Existentes

Na view de edição de cliente (`master/app/views/clients/edit.php`), adicionar botão:

```html
<a href="?page=permissions&action=edit&id=<?= $client['id'] ?>" class="btn btn-outline-primary">
    <i class="fas fa-lock"></i> Gerenciar Permissões de Páginas
</a>
```

Na view de edição de plano (`master/app/views/plans/edit.php`), adicionar botão:

```html
<a href="?page=permissions&action=editPlan&id=<?= $plan['id'] ?>" class="btn btn-outline-primary">
    <i class="fas fa-lock"></i> Permissões Padrão do Plano
</a>
```

---

## 8. Fluxo Completo

### 8.1 Configurar Permissões de um Plano

```
Admin abre Planos → Edita "Plano Básico"
    → Clica "Permissões Padrão do Plano"
    → Seleciona 15 de 36 páginas disponíveis
    → Marca "Sincronizar com tenants deste plano"
    → [Salvar]
    → Sistema aplica a todos os 10 tenants do plano Básico
    → Cada tenant agora só vê 15 páginas no menu
```

### 8.2 Personalizar Permissões de um Tenant

```
Admin abre Clientes → Edita "Empresa XYZ"
    → Clica "Gerenciar Permissões de Páginas"
    → Vê permissões atuais (herdadas do plano ou customizadas)
    → Adiciona 2 páginas extras (BI, Email Marketing)
    → [Salvar]
    → Empresa XYZ agora tem 17 páginas (15 do plano + 2 extras)
```

### 8.3 Remover Restrições

```
Admin abre Permissões → Seleciona "Acesso Total"
    → [Salvar]
    → Remove todos os registros de tenant_page_permissions
    → Tenant volta a ter acesso a todas as páginas
```

---

## 9. Segurança

| Aspecto | Proteção |
|---------|----------|
| SQL Injection | Prepared statements em todas as queries |
| CSRF | Token em todos os forms de permissão |
| Auth | Somente admin Master logado pode alterar permissões |
| Input Validation | Whitelist de page_names válidos (sanitização) |
| Fail-Open | Se conexão com master falhar, tenant mantém acesso total |
| Cache Invalidation | TTL de 5 minutos + invalidação no login |
| Audit | Toda alteração logada em admin_logs |
| Transação | `setPermissions()` usa transaction (delete + inserts atômico) |

---

## 10. Cenários de Teste

| # | Cenário | Resultado Esperado |
|---|---------|-------------------|
| 1 | Tenant sem registros em `tenant_page_permissions` | Acesso total (retrocompatível) |
| 2 | Tenant com 10 páginas permitidas | Só vê 10 páginas no menu, bloqueado nas demais |
| 3 | Tenant tenta acessar página bloqueada via URL direta | Redirect para home com erro |
| 4 | Admin remove restrições | Tenant volta a ver todas as páginas |
| 5 | Admin aplica plano com 15 páginas | Tenant vê exatamente 15 páginas |
| 6 | Admin personaliza + 2 páginas sobre o plano | Tenant vê 17 páginas |
| 7 | Cache expira (>5 min) | Próximo request busca permissões frescas do master |
| 8 | Conexão com master DB falha | Fail-open: tenant mantém acesso total |
| 9 | `dashboard`, `profile`, `home` sempre acessíveis | Sim, whitelist de páginas essenciais |
| 10 | Sync de plano para N tenants | Todos N tenants recebem mesmas permissões |

---

## 11. Checklist de Implementação

- [ ] Criar tabela `tenant_page_permissions` no `akti_master` (usar skill sql-migration)
- [ ] Criar tabela `plan_page_permissions` no `akti_master` (usar skill sql-migration)
- [ ] Criar model `master/app/models/TenantPagePermission.php`
- [ ] Criar controller `master/app/controllers/TenantPermissionController.php`
- [ ] Criar view `master/app/views/permissions/edit.php`
- [ ] Criar view `master/app/views/permissions/edit_plan.php`
- [ ] Adicionar rota `permissions` no `master/index.php`
- [ ] Adicionar botão "Permissões" na view de edição de cliente
- [ ] Adicionar botão "Permissões" na view de edição de plano
- [ ] Modificar `app/core/Application.php` — adicionar `checkTenantPagePermission()`
- [ ] Adicionar constantes `MASTER_DB_*` ao config do app principal
- [ ] Modificar `app/views/layout/header.php` — filtrar menu por permissões de tenant
- [ ] Testar todos os 10 cenários listados
- [ ] Verificar CSRF em todos os forms
- [ ] Testar com tenant sem restrições (retrocompatibilidade)
