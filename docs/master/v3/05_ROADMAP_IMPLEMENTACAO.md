# Roadmap de Implementação — Akti Master v3

> **Data:** 15/04/2026  
> **Escopo:** Roadmap priorizado para implementação dos módulos de Tickets e Permissões por Tenant  
> **Referências:** [02_SISTEMA_TICKETS_MASTER.md](02_SISTEMA_TICKETS_MASTER.md), [03_PERMISSOES_PAGINAS_TENANT.md](03_PERMISSOES_PAGINAS_TENANT.md)

---

## Por que este Roadmap existe?

A auditoria v3 identificou duas lacunas críticas no painel Master:
1. **Sem controle de páginas por tenant** — todos os tenants têm acesso irrestrito a todas as funcionalidades
2. **Sem gestão de tickets** — admin não consegue interagir com suporte aberto pelos clientes

Ambas as funcionalidades são pré-requisito para operação comercial (venda de planos com funcionalidades diferenciadas) e suporte ao cliente.

---

## Fase 1 — Preparação (Banco de Dados)

### TASK-001: Criar migrations SQL

- **Prioridade:** 🔴 CRÍTICA (bloqueia todas as demais tasks)
- **Complexidade:** Baixa
- **Arquivo:** Usar skill `sql-migration`
- **Escopo:**
  - [ ] Criar tabela `plan_page_permissions` no `akti_master`
  - [ ] Criar tabela `tenant_page_permissions` no `akti_master`
  - [ ] Criar tabela `master_ticket_replies` no `akti_master`
- **Validação:** Executar migration em ambiente de teste
- **Status:** ⬜ Pendente

---

## Fase 2 — Permissões por Tenant (Prioridade Máxima)

> As permissões são mais urgentes que os tickets porque habilitam a diferenciação comercial de planos.

### TASK-002: Criar model TenantPagePermission

- **Prioridade:** 🔴 CRÍTICA
- **Complexidade:** Média
- **Arquivo:** `master/app/models/TenantPagePermission.php`
- **Escopo:** Ver [03_PERMISSOES_PAGINAS_TENANT.md §5.1](03_PERMISSOES_PAGINAS_TENANT.md#51-model-no-master-tenantpagepermission)
  - [ ] `getPermissions($tenantClientId)`
  - [ ] `hasRestrictions($tenantClientId)`
  - [ ] `setPermissions($tenantClientId, $pages, $adminId)` — com transaction
  - [ ] `removeRestrictions($tenantClientId)`
  - [ ] `applyPlanPermissions($tenantClientId, $planId, $adminId)`
  - [ ] `getPlanPermissions($planId)`
  - [ ] `setPlanPermissions($planId, $pages)` — com transaction
  - [ ] `syncPlanToAllTenants($planId, $adminId)`
- **Status:** ⬜ Pendente

### TASK-003: Criar controller TenantPermissionController

- **Prioridade:** 🔴 CRÍTICA
- **Complexidade:** Média
- **Arquivo:** `master/app/controllers/TenantPermissionController.php`
- **Escopo:** Ver [03_PERMISSOES_PAGINAS_TENANT.md §5.2](03_PERMISSOES_PAGINAS_TENANT.md#52-controller-no-master-tenantpermissioncontroller)
  - [ ] `edit()` — tela de permissões por tenant
  - [ ] `update()` — salvar permissões (POST)
  - [ ] `applyPlan()` — copiar do plano
  - [ ] `editPlan()` — tela de permissões por plano
  - [ ] `updatePlan()` — salvar permissões do plano (POST)
  - [ ] `getAllPages()` — lista de páginas de menu.php
- **Status:** ⬜ Pendente

### TASK-004: Criar views de permissões

- **Prioridade:** 🔴 CRÍTICA
- **Complexidade:** Média
- **Arquivos:**
  - [ ] `master/app/views/permissions/edit.php` — permissões por tenant
  - [ ] `master/app/views/permissions/edit_plan.php` — permissões por plano
- **UI:** Checkboxes agrupados por categoria (Comercial, Catálogo, Produção, Fiscal, Ferramentas, Sistema)
- **Features:**
  - [ ] Radio: "Acesso Total" vs "Acesso Restrito"
  - [ ] Select: "Aplicar do Plano"
  - [ ] Checkbox "Marcar Todos" por grupo
  - [ ] Contador "X/36 páginas selecionadas"
  - [ ] SweetAlert2 para confirmação
- **Status:** ⬜ Pendente

### TASK-005: Adicionar rota e menu no Master

- **Prioridade:** 🟠 ALTA
- **Complexidade:** Baixa
- **Escopo:**
  - [ ] Adicionar case `permissions` no `master/index.php`
  - [ ] Adicionar require do model e controller no index.php
  - [ ] Adicionar botão "Permissões" na view `clients/edit.php`
  - [ ] Adicionar botão "Permissões" na view `plans/edit.php`
- **Status:** ⬜ Pendente

### TASK-006: Enforcement no app principal

- **Prioridade:** 🔴 CRÍTICA
- **Complexidade:** Alta
- **Escopo:** Ver [03_PERMISSOES_PAGINAS_TENANT.md §6](03_PERMISSOES_PAGINAS_TENANT.md#6-enforcement-no-app-principal)
  - [ ] Adicionar método `checkTenantPagePermission()` em `app/core/Application.php`
  - [ ] Adicionar método `getTenantPermissionsFromMaster()` com cache em sessão
  - [ ] Definir constantes `MASTER_DB_*` no config do app
  - [ ] Integrar verificação no fluxo `handle()` (após ModuleBootloader)
  - [ ] Definir lista `$alwaysAllowed` (home, dashboard, profile, login, logout)
  - [ ] Implementar TTL de cache (5 minutos)
- **Validação:**
  - [ ] Testar tenant sem restrições → acesso total ✅
  - [ ] Testar tenant com whitelist → apenas páginas permitidas ✅
  - [ ] Testar acesso direto por URL a página bloqueada → redirect ✅
  - [ ] Testar fail-open (master DB down) → acesso total ✅
- **Status:** ⬜ Pendente

### TASK-007: Filtrar menu por permissões de tenant

- **Prioridade:** 🟠 ALTA
- **Complexidade:** Média
- **Arquivo:** `app/views/layout/header.php`
- **Escopo:**
  - [ ] Ler `$_SESSION['tenant_page_permissions']`
  - [ ] Se não vazio, filtrar itens do menu
  - [ ] Ocultar grupos vazios (ex: se nenhum item de "Ferramentas" está permitido, ocultar dropdown)
  - [ ] Manter `dashboard`, `profile` sempre visíveis
- **Status:** ⬜ Pendente

---

## Fase 3 — Sistema de Tickets no Master

### TASK-008: Criar model MasterTicket

- **Prioridade:** 🟠 ALTA
- **Complexidade:** Alta
- **Arquivo:** `master/app/models/MasterTicket.php`
- **Escopo:** Ver [02_SISTEMA_TICKETS_MASTER.md §3.1](02_SISTEMA_TICKETS_MASTER.md#31-model-masterticket)
  - [ ] `readAllFromAllTenants($filters)` — cross-DB query
  - [ ] `readTicketFromTenant($tenantClientId, $ticketId)`
  - [ ] `getTicketMessages($tenantClientId, $ticketId)`
  - [ ] `replyToTicket($adminId, $tenantId, $ticketId, $message)`
  - [ ] `changeTicketStatus($adminId, $tenantId, $ticketId, $newStatus)`
  - [ ] `getGlobalStats()` — dashboard consolidado
  - [ ] `logReply()` — registrar no master_ticket_replies
- **Status:** ⬜ Pendente

### TASK-009: Criar controller TicketMasterController

- **Prioridade:** 🟠 ALTA
- **Complexidade:** Média
- **Arquivo:** `master/app/controllers/TicketMasterController.php`
- **Escopo:** Ver [02_SISTEMA_TICKETS_MASTER.md §3.2](02_SISTEMA_TICKETS_MASTER.md#32-controller-ticketmastercontroller)
  - [ ] `index()` — listagem com filtros e stats
  - [ ] `view()` — detalhe do ticket com mensagens
  - [ ] `reply()` — responder ticket (POST)
  - [ ] `changeStatus()` — alterar status (POST)
- **Status:** ⬜ Pendente

### TASK-010: Criar views de tickets

- **Prioridade:** 🟠 ALTA
- **Complexidade:** Média
- **Arquivos:**
  - [ ] `master/app/views/tickets/index.php` — listagem centralizada
  - [ ] `master/app/views/tickets/view.php` — detalhe + chat
- **UI:**
  - [ ] Cards de estatísticas (total, abertos, urgentes, SLA violado)
  - [ ] Filtros: tenant, status, prioridade
  - [ ] Tabela com badge de prioridade colorido
  - [ ] Timeline de mensagens estilo chat
  - [ ] Textarea para resposta
  - [ ] Dropdown de mudança de status
- **Status:** ⬜ Pendente

### TASK-011: Adicionar rota e menu para tickets

- **Prioridade:** 🟠 ALTA
- **Complexidade:** Baixa
- **Escopo:**
  - [ ] Adicionar case `tickets` no `master/index.php`
  - [ ] Adicionar item "Tickets" no sidebar (header.php) com badge de contagem
  - [ ] Adicionar require do model e controller
- **Status:** ⬜ Pendente

---

## Fase 4 — Segurança e Polimento

### TASK-012: Adicionar CSRF ao Master

- **Prioridade:** 🟠 ALTA
- **Complexidade:** Média
- **Escopo:**
  - [ ] Gerar token CSRF na sessão
  - [ ] Adicionar `<input type="hidden" name="_csrf" value="...">` em todos os forms
  - [ ] Validar token em todos os POST handlers
  - [ ] Adicionar header `X-CSRF-TOKEN` em AJAX requests
- **Status:** ⬜ Pendente

### TASK-013: Adicionar rate limiting no login

- **Prioridade:** 🟡 MÉDIA
- **Complexidade:** Baixa
- **Escopo:**
  - [ ] Criar tabela `master_login_attempts` (ou usar `admin_logs`)
  - [ ] Bloquear após 5 tentativas em 15 minutos
  - [ ] Exibir reCAPTCHA após 3 tentativas
- **Status:** ⬜ Pendente

### TASK-014: Alterar toggleActive para POST

- **Prioridade:** 🟡 MÉDIA
- **Complexidade:** Baixa
- **Escopo:**
  - [ ] Alterar link para form com POST
  - [ ] Adicionar CSRF no form
  - [ ] Manter SweetAlert2 de confirmação
- **Status:** ⬜ Pendente

---

## Cronograma Sugerido

| Fase | Tasks | Estimativa | Dependências |
|------|-------|------------|-------------|
| **Fase 1** — DB | TASK-001 | Sprint 1 | Nenhuma |
| **Fase 2** — Permissões | TASK-002 a TASK-007 | Sprint 1-2 | TASK-001 |
| **Fase 3** — Tickets | TASK-008 a TASK-011 | Sprint 2-3 | TASK-001 |
| **Fase 4** — Segurança | TASK-012 a TASK-014 | Sprint 3 | Nenhuma |

**Ordem de implementação recomendada:**

```
TASK-001 (SQL) 
    → TASK-002 (Model Perm) → TASK-003 (Ctrl Perm) → TASK-004 (Views Perm)
    → TASK-005 (Rotas Perm) → TASK-006 (Enforcement) → TASK-007 (Menu Filter)
    → TASK-008 (Model Ticket) → TASK-009 (Ctrl Ticket) → TASK-010 (Views Ticket)
    → TASK-011 (Rotas Ticket)
TASK-012 (CSRF) — paralelo
TASK-013 (Rate Limit) — paralelo
TASK-014 (toggleActive POST) — paralelo
```

---

## Métricas de Sucesso

| Métrica | Meta |
|---------|------|
| Permissões funcionando retrocompatível | 100% tenants sem restrições continuam com acesso total |
| Tempo de carregamento com verificação | < 50ms adicionais (cache em sessão) |
| Tickets de todos os tenants visíveis | 100% dos tenants ativos consultados |
| CSRF em 100% dos forms | 0 forms sem proteção |
| Zero SQL injection | Prepared statements em toda query |
| Auditoria completa | Toda ação logada em admin_logs + tabelas específicas |

---

## Riscos e Mitigações

| Risco | Impacto | Mitigação |
|-------|---------|-----------|
| Performance da listagem de tickets (N connections) | Lentidão com 50+ tenants | Filtro obrigatório por tenant, cache, paginação, limit 100/tenant |
| Sincronização de permissões com menu.php | Páginas novas não aparecem na lista do Master | Manter `getAllPages()` sincronizado; criar validação automática |
| Falha na conexão com master DB (enforcement) | Tenant poderia ficar sem acesso | Fail-open: se master indisponível, permitir acesso total |
| Cache de permissões defasado | Tenant não vê mudança imediatamente | TTL de 5 min + opção de "forçar limpar cache" no master |
| Mensagem do master sem user_id no tenant | Mensagem aparece sem autor | Prefixo `[Suporte Akti]` + evolução futura com campo `source` |
