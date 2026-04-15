# Auditoria Master v3 — Sistema de Tickets + Permissões por Tenant

> **Data:** 15/04/2026  
> **Escopo:** Auditoria da gestão de clientes no painel Master + design de dois novos módulos  
> **Versão anterior:** `docs/master/v2` (auditoria pós-integração)  
> **Auditor:** Auditoria Automatizada via Análise Estática de Código

---

## Documentos desta versão

| # | Arquivo | Conteúdo |
|---|---------|----------|
| 01 | [01_AUDITORIA_GESTAO_CLIENTES.md](01_AUDITORIA_GESTAO_CLIENTES.md) | Auditoria completa da gestão de clientes no Master |
| 02 | [02_SISTEMA_TICKETS_MASTER.md](02_SISTEMA_TICKETS_MASTER.md) | Design completo do sistema de tickets no Master |
| 03 | [03_PERMISSOES_PAGINAS_TENANT.md](03_PERMISSOES_PAGINAS_TENANT.md) | Design do controle de páginas por tenant |
| 04 | [04_MIGRATIONS_SQL.md](04_MIGRATIONS_SQL.md) | Schemas SQL necessários para os novos módulos |
| 05 | [05_ROADMAP_IMPLEMENTACAO.md](05_ROADMAP_IMPLEMENTACAO.md) | Roadmap priorizado de implementação |

---

## Resumo Executivo

### Estado Atual do Master

O painel Master possui **8 controllers**, **8 models**, **16 views** e gerencia clientes (tenants) com CRUD completo, provisionamento de banco, planos e limites. A auditoria v2 identificou bugs críticos que foram **todos corrigidos** (3/3 P0, 5/5 P1, 7/7 P2).

### Lacunas Identificadas

| Lacuna | Severidade | Impacto |
|--------|-----------|---------|
| Sem sistema de tickets no Master | 🟠 ALTO | Admin não consegue interagir com tickets abertos pelos clientes |
| Sem controle de páginas por tenant | 🔴 CRÍTICO | Todos os tenants têm acesso a todas as páginas, sem possibilidade de restrição |
| Sem CSRF no Master | 🟠 ALTO | Forms vulneráveis a ataques CSRF (pendente de v2) |
| Sem rate limiting no login | 🟡 MÉDIO | Login pode sofrer brute force |

### Novos Módulos Propostos

1. **Sistema de Tickets no Master** — Permite ao admin visualizar, responder e gerenciar tickets abertos pelos clientes (tenants). Reutiliza a estrutura já existente em `Akti\Models\Ticket` no app principal.

2. **Permissões de Páginas por Tenant** — Controle granular de quais páginas cada tenant pode acessar. Similar ao sistema de `group_permissions` mas aplicado no nível do tenant. Páginas sem permissão ficam bloqueadas para todos os usuários do tenant.

### Métricas do Sistema Master

| Categoria | Total |
|-----------|-------|
| Controllers | 8 → 10 (após implementação) |
| Models | 8 → 10 (após implementação) |
| Views | 16 → 22 (após implementação) |
| Rotas | 7 → 9 (após implementação) |
| Tabelas Master DB | 5 → 7 (após implementação) |

### Evolução vs. v2

| Aspecto | v2 | v3 |
|---------|-----|-----|
| Bugs Críticos | 2 (corrigidos) | 0 pendentes |
| Issues P1 Segurança | 5 (corrigidas) | 2 novas (CSRF, rate limit) |
| Módulos Master | 8 | 8 + 2 propostos |
| Cobertura funcional | CRUD clientes, planos, migrations, git, backup, logs | + tickets + permissões tenant |

---

## Sprint Imediato (Top 5 Prioridades)

1. **Criar tabelas `tenant_page_permissions` e `master_ticket_replies`** no `akti_master`
2. **Implementar módulo de Permissões por Tenant** (bloqueia/libera páginas)
3. **Implementar módulo de Tickets no Master** (visualizar e responder tickets dos clientes)
4. **Adicionar CSRF tokens** a todos os forms do Master
5. **Adicionar rate limiting** no login do Master

---

## Como usar esta documentação

| Persona | Documentos Relevantes |
|---------|----------------------|
| **Dev Backend** | 01 (audit), 02 (tickets), 03 (permissões), 04 (SQL) |
| **Tech Lead** | README (resumo), 05 (roadmap), 03 (arquitetura permissões) |
| **DBA** | 04 (migrations SQL) |
| **QA** | 02 (fluxos tickets), 03 (cenários permissão) |
