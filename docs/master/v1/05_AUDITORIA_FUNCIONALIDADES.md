# Auditoria de Funcionalidades — Akti Master v1

> **Data da Auditoria:** 06/04/2026  
> **Escopo:** Completude dos módulos, funcionalidades existentes e ausentes

---

## 1. Resumo Executivo

O Master possui 7 módulos funcionais que cobrem as operações administrativas essenciais. No entanto, faltam automações significativas e alguns módulos têm lacunas importantes.

| Módulo | Completude | Gaps Identificados |
|--------|-----------|-------------------|
| Auth | 60% | Sem 2FA, sem rate limit, sem gerenciamento de admins |
| Dashboard | 70% | Falta monitoring em tempo real, alertas |
| Plans | 90% | ✅ CRUD completo, sync com clientes |
| Clients | 85% | ✅ CRUD + provisioning, falta monitoring por tenant |
| Migrations | 65% | Falta auto-detectar SQL, falta rollback |
| Git | 80% | ✅ Operações completas, falta deploy automatizado |
| Backup | 75% | ✅ CRUD, falta agendamento, falta restore |
| Logs | 70% | ✅ Visualização, falta alertas e parsing |

---

## 2. Módulo Auth — Análise Detalhada

### Funcionalidades Existentes:
- ✅ Login com email/senha
- ✅ Sessão com admin_id, admin_name, admin_email
- ✅ Logout com log de atividade
- ✅ Senhas com bcrypt (`password_hash/password_verify`)
- ✅ Log de login/logout

### Funcionalidades Ausentes:
- ❌ **FUNC-001:** Gerenciamento de administradores (CRUD de admin_users) — Só existe `reset_password.php` avulso
- ❌ **FUNC-002:** Autenticação 2FA (TOTP) — Essencial para painel de infraestrutura
- ❌ **FUNC-003:** Rate limiting de login
- ❌ **FUNC-004:** Política de senha (complexidade mínima, expiração)
- ❌ **FUNC-005:** "Lembrar-me" com token seguro
- ❌ **FUNC-006:** Log de tentativas de login falhadas
- ❌ **FUNC-007:** Múltiplos níveis de admin (superadmin vs operador)

---

## 3. Módulo Dashboard — Análise Detalhada

### Funcionalidades Existentes:
- ✅ Total de clientes (ativos/inativos)
- ✅ Total de planos
- ✅ Clientes por plano
- ✅ Clientes recentes
- ✅ Log de atividade recente (últimas 10 ações)

### Funcionalidades Ausentes:
- ❌ **FUNC-008:** Monitoring de saúde dos bancos tenant (up/down)
- ❌ **FUNC-009:** Alertas de espaço em disco, CPU, RAM
- ❌ **FUNC-010:** Gráfico de crescimento de clientes ao longo do tempo
- ❌ **FUNC-011:** Status dos serviços (Nginx, MySQL, Node API)
- ❌ **FUNC-012:** Faturamento total por período (soma de planos)
- ❌ **FUNC-013:** Widget de migrations pendentes
- ❌ **FUNC-014:** Widget de repositórios com updates disponíveis

---

## 4. Módulo Plans — Análise Detalhada

### Funcionalidades Existentes:
- ✅ CRUD completo (create, read, update, delete)
- ✅ Campos: nome, descrição, preço, limites (users, products, warehouses, price_tables, sectors)
- ✅ Ativo/inativo
- ✅ Sync de limites para clientes vinculados ao atualizar
- ✅ Verificação de clientes vinculados antes de excluir
- ✅ Cards visuais com destaque "popular"
- ✅ Log de todas as operações

### Funcionalidades Ausentes:
- ❌ **FUNC-015:** Módulos habilitados por plano (feature flags)
- ❌ **FUNC-016:** Período de trial
- ❌ **FUNC-017:** Cobranças recorrentes integradas

---

## 5. Módulo Clients — Análise Detalhada

### Funcionalidades Existentes:
- ✅ CRUD completo
- ✅ Provisionamento automático de banco (clone de `akti_init_base`)
- ✅ Criação de usuário MySQL dedicado
- ✅ Criação de primeiro usuário no tenant
- ✅ Toggle ativo/inativo
- ✅ Exclusão com dupla confirmação (nome do banco + senha admin)
- ✅ Drop automático do banco ao excluir
- ✅ Filtros por status e plano
- ✅ Busca por nome

### Funcionalidades Ausentes:
- ❌ **FUNC-018:** Monitoring individual por tenant (disk usage, record count)
- ❌ **FUNC-019:** Suspend/unsuspend com página de aviso para o tenant
- ❌ **FUNC-020:** Customização de módulos por tenant (feature flags)
- ❌ **FUNC-021:** Impersonação — logar como admin do tenant para suporte
- ❌ **FUNC-022:** Exportação de lista de clientes (CSV/Excel)
- ❌ **FUNC-023:** Notas/observações por cliente
- ❌ **FUNC-024:** Histórico de alterações por cliente (log detalhado)

---

## 6. Módulo Migrations — Análise Detalhada

### Funcionalidades Existentes:
- ✅ Comparação de schema (init_base vs cada tenant)
- ✅ Detecção de tabelas/colunas faltando, extras, tipos diferentes
- ✅ Execução de SQL em bancos selecionados
- ✅ Aplicação no banco de referência (init_base)
- ✅ Log de migrações com hash SHA-256 (idempotência)
- ✅ Histórico de migrações
- ✅ Gestão de usuários cross-tenant (listagem, criação, toggle)

### Funcionalidades Ausentes — CRÍTICAS:
- ❌ **FUNC-025:** **Auto-detecção de arquivos SQL da pasta `sql/`** — Atualmente o operador precisa copiar/colar SQL manualmente
- ❌ **FUNC-026:** **Preview/dry-run** antes de executar SQL
- ❌ **FUNC-027:** **Rollback** de migrações falhas
- ❌ **FUNC-028:** **Validação de SQL** antes de executar (detectar DROP sem WHERE, TRUNCATE, etc.)
- ❌ **FUNC-029:** **Geração automática de SQL** a partir das diferenças de schema detectadas
- ❌ **FUNC-030:** **Mover arquivos SQL para `sql/prontos/`** após aplicar com sucesso
- ❌ **FUNC-031:** **Agendamento** de migrações (ex: aplicar à noite)

---

## 7. Módulo Git — Análise Detalhada

### Funcionalidades Existentes:
- ✅ Listagem de repositórios com status (up-to-date, behind, dirty, error)
- ✅ Fetch individual e em massa
- ✅ Pull individual e em massa
- ✅ Stash & Pull (save local changes + pull)
- ✅ Force Reset (git reset --hard origin/branch) com confirmação
- ✅ Checkout de branch
- ✅ Visualização de commits, branches, diff
- ✅ Diagnóstico completo do ambiente
- ✅ Log administrativo de todas as operações

### Funcionalidades Ausentes:
- ❌ **FUNC-032:** **Deploy automatizado** — Pull + executar migrations + limpar cache
- ❌ **FUNC-033:** **Webhook** do GitHub/GitLab para auto-deploy
- ❌ **FUNC-034:** **Notificação** quando há updates disponíveis
- ❌ **FUNC-035:** **Comparação visual de diff** (syntax highlighting)
- ❌ **FUNC-036:** **Tag management** — criar/listar tags de release

---

## 8. Módulo Backup — Análise Detalhada

### Funcionalidades Existentes:
- ✅ Execução de backup via comando (`sudo /bin/bkp`)
- ✅ Listagem de backups com tamanho e data
- ✅ Download de arquivos de backup
- ✅ Exclusão com dupla confirmação (nome + senha admin)
- ✅ Diagnóstico do ambiente
- ✅ Log de operações

### Funcionalidades Ausentes:
- ❌ **FUNC-037:** **Agendamento** de backups (cron-like via painel)
- ❌ **FUNC-038:** **Restore** de backup via painel
- ❌ **FUNC-039:** **Backup individual por tenant** (não apenas full)
- ❌ **FUNC-040:** **Verificação de integridade** do backup
- ❌ **FUNC-041:** **Upload de backup** para cloud (S3, Google Drive)
- ❌ **FUNC-042:** **Retenção automática** — excluir backups > N dias

---

## 9. Módulo Logs — Análise Detalhada

### Funcionalidades Existentes:
- ✅ Listagem de arquivos de log (Nginx error/access)
- ✅ Visualização com tail (últimas N linhas)
- ✅ Busca por texto
- ✅ Análise de erros (agrupamento por tipo)
- ✅ Download de logs
- ✅ Auto-refresh
- ✅ Suporte a arquivos .gz

### Funcionalidades Ausentes:
- ❌ **FUNC-043:** **Alertas automáticos** — notificar em caso de erros 500 em massa
- ❌ **FUNC-044:** **Logs de aplicação** (PHP, não apenas Nginx)
- ❌ **FUNC-045:** **Parsing estruturado** de logs (JSON format)
- ❌ **FUNC-046:** **Gráficos de trends** — erros por hora/dia
- ❌ **FUNC-047:** **Integração com logs de admin** — visualizar log administrativo no mesmo painel

---

## 10. Funcionalidades Totalmente Ausentes

| ID | Funcionalidade | Prioridade | Descrição |
|----|---------------|-----------|-----------|
| FUNC-048 | **Módulo de Settings** | Alta | Configurações do sistema (email, SMTP, variáveis globais) |
| FUNC-049 | **Módulo de Cron Jobs** | Alta | Gestão de tarefas agendadas do servidor |
| FUNC-050 | **Módulo de Monitoring** | Alta | Health checks de serviços (MySQL, Nginx, Node, PHP-FPM) |
| FUNC-051 | **Módulo de Email** | Média | Envio de notificações/alertas para o admin |
| FUNC-052 | **API REST** | Média | API para automações externas e CI/CD |
| FUNC-053 | **Módulo de DNS** | Baixa | Gestão de subdomínios/DNS dos tenants |
| FUNC-054 | **Help/Docs inline** | Baixa | Documentação acessível dentro do painel |

---

## 11. Code Smells

| ID | Arquivo | Problema |
|----|---------|----------|
| CS-001 | `git/index.php` | 752 linhas — view muito grande, mistura layout + JS + lógica |
| CS-002 | `logs/index.php` | 575 linhas — idem |
| CS-003 | `clients/index.php` | 534 linhas — idem |
| CS-004 | `migrations/index.php` | 452 linhas — idem |
| CS-005 | `GitVersion.php` | 700+ linhas — model muito grande, considerar split |
| CS-006 | `index.php` (router) | Switch monolítico de 170+ linhas |
| CS-007 | `config.php` | Credenciais hardcoded |
