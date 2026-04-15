# Migrations SQL — Novos Módulos Master — Akti v3

> **Data:** 15/04/2026  
> **Escopo:** Schemas SQL necessários para os módulos de Tickets no Master e Permissões por Tenant  
> **Nota:** Estes são os schemas propostos. A criação efetiva dos arquivos `.sql` deve usar a skill `sql-migration`.

---

## 1. Tabela: `master_ticket_replies` (akti_master)

**Objetivo:** Registrar respostas do admin Master aos tickets dos tenants (log de auditoria local).

```sql
-- Migration: Criar tabela master_ticket_replies para log de respostas do admin a tickets de tenants
-- Banco: akti_master

CREATE TABLE IF NOT EXISTS `master_ticket_replies` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `admin_id` INT NOT NULL COMMENT 'Admin do Master que respondeu',
    `tenant_client_id` INT NOT NULL COMMENT 'ID do tenant no master',
    `tenant_db_name` VARCHAR(100) NOT NULL COMMENT 'Nome do banco do tenant',
    `ticket_id` INT NOT NULL COMMENT 'ID do ticket no banco do tenant',
    `ticket_number` VARCHAR(20) NOT NULL COMMENT 'Número do ticket (TKT-XXXXXX)',
    `message` TEXT NOT NULL COMMENT 'Conteúdo da mensagem/ação',
    `action` ENUM('reply', 'status_change', 'assign', 'note') NOT NULL DEFAULT 'reply',
    `new_status` VARCHAR(30) NULL COMMENT 'Novo status se action=status_change',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_mtr_admin` (`admin_id`),
    INDEX `idx_mtr_tenant` (`tenant_client_id`),
    INDEX `idx_mtr_ticket` (`ticket_id`),
    INDEX `idx_mtr_created` (`created_at`),
    CONSTRAINT `fk_mtr_admin` FOREIGN KEY (`admin_id`) REFERENCES `admin_users`(`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_mtr_tenant` FOREIGN KEY (`tenant_client_id`) REFERENCES `tenant_clients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Notas:**
- `ticket_id` não tem FK porque referencia um ID em outro banco (o do tenant)
- `tenant_db_name` registrado para referência mesmo se o tenant for excluído
- `action` permite distinguir entre resposta, mudança de status, atribuição e nota interna

---

## 2. Tabela: `tenant_page_permissions` (akti_master)

**Objetivo:** Controlar quais páginas cada tenant pode acessar (whitelist).

```sql
-- Migration: Criar tabela tenant_page_permissions para controle de acesso por tenant
-- Banco: akti_master

CREATE TABLE IF NOT EXISTS `tenant_page_permissions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tenant_client_id` INT NOT NULL COMMENT 'ID do tenant',
    `page_name` VARCHAR(80) NOT NULL COMMENT 'Chave da página (ex: products, orders)',
    `granted_by` INT NULL COMMENT 'Admin que concedeu a permissão',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_tenant_page` (`tenant_client_id`, `page_name`),
    INDEX `idx_tpp_tenant` (`tenant_client_id`),
    CONSTRAINT `fk_tpp_tenant` FOREIGN KEY (`tenant_client_id`) REFERENCES `tenant_clients`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_tpp_admin` FOREIGN KEY (`granted_by`) REFERENCES `admin_users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Lógica de uso:**
- **Nenhum registro** para um tenant → acesso total (retrocompatível)
- **Pelo menos 1 registro** → somente páginas listadas são acessíveis (whitelist)
- `ON DELETE CASCADE` → se o tenant for excluído, permissões são removidas automaticamente

---

## 3. Tabela: `plan_page_permissions` (akti_master)

**Objetivo:** Definir permissões padrão por plano (template reutilizável).

```sql
-- Migration: Criar tabela plan_page_permissions para templates de permissões por plano
-- Banco: akti_master

CREATE TABLE IF NOT EXISTS `plan_page_permissions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `plan_id` INT NOT NULL COMMENT 'ID do plano',
    `page_name` VARCHAR(80) NOT NULL COMMENT 'Chave da página',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_plan_page` (`plan_id`, `page_name`),
    INDEX `idx_ppp_plan` (`plan_id`),
    CONSTRAINT `fk_ppp_plan` FOREIGN KEY (`plan_id`) REFERENCES `plans`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Uso:**
- Admin configura quais páginas cada plano inclui
- Ao criar/atualizar um tenant, pode copiar permissões do plano
- Opção "Sincronizar" aplica permissões do plano a todos os tenants vinculados

---

## 4. Resumo de Alterações

| Tabela | Banco | Tipo | Colunas | Índices | FKs |
|--------|-------|------|---------|---------|-----|
| `master_ticket_replies` | akti_master | CREATE | 10 | 4 | 2 |
| `tenant_page_permissions` | akti_master | CREATE | 5 | 2 (+ 1 unique) | 2 |
| `plan_page_permissions` | akti_master | CREATE | 4 | 1 (+ 1 unique) | 1 |

**Total: 3 novas tabelas, 19 colunas, 7 índices, 5 foreign keys**

---

## 5. Dados Iniciais Sugeridos (Seeds)

### 5.1 Permissões do Plano "Enterprise" (todas as páginas)

```sql
-- Seed: Plano Enterprise com acesso total (todas as 36 páginas)
-- Executar apenas se existir plano com nome 'Enterprise'

INSERT IGNORE INTO plan_page_permissions (plan_id, page_name)
SELECT p.id, pages.page_name
FROM plans p
CROSS JOIN (
    SELECT 'customers' AS page_name UNION ALL
    SELECT 'orders' UNION ALL SELECT 'quotes' UNION ALL
    SELECT 'agenda' UNION ALL SELECT 'calendar' UNION ALL
    SELECT 'price_tables' UNION ALL SELECT 'suppliers' UNION ALL
    SELECT 'tickets' UNION ALL SELECT 'whatsapp' UNION ALL
    SELECT 'products' UNION ALL SELECT 'categories' UNION ALL
    SELECT 'stock' UNION ALL SELECT 'supplies' UNION ALL
    SELECT 'supply_stock' UNION ALL SELECT 'pipeline' UNION ALL
    SELECT 'production_board' UNION ALL SELECT 'sectors' UNION ALL
    SELECT 'quality' UNION ALL SELECT 'equipment' UNION ALL
    SELECT 'production_costs' UNION ALL SELECT 'shipments' UNION ALL
    SELECT 'financial' UNION ALL SELECT 'commissions' UNION ALL
    SELECT 'payment_gateways' UNION ALL SELECT 'nfe_documents' UNION ALL
    SELECT 'nfe_credentials' UNION ALL SELECT 'reports' UNION ALL
    SELECT 'custom_reports' UNION ALL SELECT 'bi' UNION ALL
    SELECT 'site_builder' UNION ALL SELECT 'workflows' UNION ALL
    SELECT 'email_marketing' UNION ALL SELECT 'attachments' UNION ALL
    SELECT 'audit' UNION ALL SELECT 'branches' UNION ALL
    SELECT 'achievements' UNION ALL SELECT 'esg' UNION ALL
    SELECT 'ai_assistant' UNION ALL SELECT 'settings' UNION ALL
    SELECT 'users' UNION ALL SELECT 'portal_admin'
) pages
WHERE p.plan_name = 'Enterprise';
```

### 5.2 Permissões do Plano "Básico" (essenciais)

```sql
-- Seed: Plano Básico com 15 páginas essenciais
INSERT IGNORE INTO plan_page_permissions (plan_id, page_name)
SELECT p.id, pages.page_name
FROM plans p
CROSS JOIN (
    SELECT 'customers' AS page_name UNION ALL
    SELECT 'orders' UNION ALL SELECT 'quotes' UNION ALL
    SELECT 'products' UNION ALL SELECT 'categories' UNION ALL
    SELECT 'stock' UNION ALL SELECT 'pipeline' UNION ALL
    SELECT 'production_board' UNION ALL SELECT 'sectors' UNION ALL
    SELECT 'shipments' UNION ALL SELECT 'financial' UNION ALL
    SELECT 'reports' UNION ALL SELECT 'settings' UNION ALL
    SELECT 'users' UNION ALL SELECT 'tickets'
) pages
WHERE p.plan_name = 'Básico';
```

> **⚠️ IMPORTANTE:** Estes seeds são sugestões. NÃO devem ser incluídos no arquivo de migration. Devem ser aplicados manualmente pelo admin ou via interface do Master.

---

## 6. Ordem de Execução

1. **Primeiro:** Criar tabela `plan_page_permissions` (sem dependências além de `plans`)
2. **Segundo:** Criar tabela `tenant_page_permissions` (sem dependências além de `tenant_clients` e `admin_users`)
3. **Terceiro:** Criar tabela `master_ticket_replies` (sem dependências além de `admin_users` e `tenant_clients`)

Todas as 3 tabelas podem ser criadas em um único migration file, pois não há dependências entre elas.
