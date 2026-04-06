# Auditoria Sistema Master — Akti v1

> **Data da Auditoria:** 06/04/2026  
> **Escopo:** Sistema Master (painel administrativo interno)  
> **Auditor:** Auditoria Automatizada via Análise Estática de Código  
> **Classificação de Severidade:** CRÍTICO > ALTO > MÉDIO > BAIXO > INFORMATIVO

---

## Resumo Executivo

O **Akti Master** é o painel administrativo interno para gestão de tenants, planos, migrações de banco, versionamento Git, backups e logs. Este sistema **não deve ser acessado por clientes** — apenas pelo proprietário e funcionários autorizados.

### Inventário do Sistema

| Métrica                | Valor |
|------------------------|-------|
| Controllers            | 8     |
| Models                 | 8     |
| Views                  | 14    |
| Arquivos CSS           | 1     |
| Arquivos JS            | 1     |
| Páginas/Módulos        | 7     |
| Linhas de código (est.)| ~6.500|

### Módulos Existentes

| Módulo       | Controller           | Funcionalidades                                    |
|--------------|---------------------|----------------------------------------------------|
| Auth         | AuthController       | Login, logout, sessão                              |
| Dashboard    | DashboardController  | Estatísticas, atividade recente                    |
| Plans        | PlanController       | CRUD de planos de assinatura                       |
| Clients      | ClientController     | CRUD de tenants, provisionamento de BD, usuários   |
| Migrations   | MigrationController  | Comparação de schemas, execução SQL cross-tenant   |
| Git          | GitController        | Fetch, pull, checkout, force-reset, diagnóstico    |
| Backup       | BackupController     | Executar, listar, download, excluir backups        |
| Logs         | LogController        | Visualização de logs Nginx/PHP, busca, análise     |

### Notas por Categoria

| Categoria        | Nota  | Status |
|------------------|-------|--------|
| Segurança        | 6/10  | ⚠️     |
| Arquitetura      | 7/10  | ⚠️     |
| Banco de Dados   | 8/10  | ✅     |
| Frontend         | 7/10  | ⚠️     |
| Automação        | 4/10  | ❌     |
| Funcionalidades  | 7/10  | ⚠️     |

### Top 5 Issues Críticos

1. **SEC-001** — Credenciais de BD hardcoded em `config.php` (senha em texto plano no código)
2. **SEC-002** — Ausência de CSRF token em formulários e requisições AJAX
3. **SEC-003** — SQL Injection potencial em queries com interpolação direta (`{$dbName}`, `{$table}`)
4. **SEC-004** — Sem rate limiting no login (brute force possível)
5. **AUTO-001** — Migrations não leem automaticamente da pasta `sql/` (processo manual)

---

## Índice de Documentos

| # | Arquivo | Conteúdo |
|---|---------|----------|
| 00 | [README.md](README.md) | Este documento — resumo e índice |
| 01 | [01_AUDITORIA_SEGURANCA.md](01_AUDITORIA_SEGURANCA.md) | Análise completa de segurança |
| 02 | [02_AUDITORIA_ARQUITETURA.md](02_AUDITORIA_ARQUITETURA.md) | Arquitetura, MVC, padrões |
| 03 | [03_AUDITORIA_BANCO_DADOS.md](03_AUDITORIA_BANCO_DADOS.md) | Banco de dados, queries, models |
| 04 | [04_AUDITORIA_FRONTEND.md](04_AUDITORIA_FRONTEND.md) | Frontend, UI/UX, responsividade |
| 05 | [05_AUDITORIA_FUNCIONALIDADES.md](05_AUDITORIA_FUNCIONALIDADES.md) | Módulos, completude, code smells |
| 06 | [06_ROADMAP_CORRECOES.md](06_ROADMAP_CORRECOES.md) | Correções priorizadas por severidade |
| 07 | [07_ROADMAP_AUTOMACOES_MELHORIAS.md](07_ROADMAP_AUTOMACOES_MELHORIAS.md) | Automações e novas funcionalidades |
| 08 | [08_ROADMAP_INTEGRACAO.md](08_ROADMAP_INTEGRACAO.md) | Roadmap de integração Master → Akti unificado |
| 09 | [09_ROADMAP_SEGURANCA.md](09_ROADMAP_SEGURANCA.md) | Roadmap de segurança pós-integração |

---

## Como Usar Esta Documentação

1. **Integração:** Leia `08_ROADMAP_INTEGRACAO.md` — plano completo de unificação do master ao Akti
2. **Segurança pós-integração:** Leia `09_ROADMAP_SEGURANCA.md` — vulnerabilidades que restam após unificação
3. **Prioridade imediata:** Leia `06_ROADMAP_CORRECOES.md` — itens CRÍTICOS caso não integre
4. **Automações:** Leia `07_ROADMAP_AUTOMACOES_MELHORIAS.md` — funcionalidades e automações sugeridas
5. **Detalhes técnicos:** Consulte os documentos 01-05 para contexto e evidências de cada issue
