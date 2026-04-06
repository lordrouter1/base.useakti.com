# Roadmap de Automações e Melhorias — Akti Master v1

> **Objetivo:** Transformar o Master em um painel de gestão completo com automações  
> **Princípio:** Este sistema é EXCLUSIVO para o proprietário e funcionários — NUNCA acessível por clientes

---

## Fase 1 — Foundation (Infraestrutura Base)

### AUTO-001: Migration Auto-Detect da Pasta `sql/` ⭐ PRIORITÁRIO
- **Descrição:** O painel de migrations deve listar automaticamente os arquivos `.sql` pendentes da pasta `sql/` do projeto, permitindo seleção e execução com um clique
- **Benefício:** Elimina o processo manual de copiar/colar SQL
- **Complexidade:** Média
- **Implementação sugerida:**
  1. No `MigrationController::index()`, escanear a pasta `sql/` recursivamente
  2. Filtrar apenas `.sql` que não estejam em `sql/prontos/`
  3. Exibir na view com preview do conteúdo
  4. Ao executar com sucesso em todos os DBs, mover automaticamente para `sql/prontos/`
  5. Adicionar botão "Executar" por arquivo e "Executar Todos Pendentes"
- **Escopo:**
  - [x] Scan da pasta `sql/` no controller
  - [x] Exibição na view com conteúdo expandível
  - [x] Execução individual e em batch
  - [x] Move para `sql/prontos/` após sucesso
  - [x] Log no `migration_logs`
- **Status:** ✅ Implementado (2025-06-04)
- **Arquivos:** `MigrationController.php` (scanPendingSqlFiles, previewSqlFile, applyFile, applyAllFiles), `migrations/index.php`, `migrations/results.php`

### AUTO-002: Deploy Automatizado (Git Pull + Migrations + Cache)
- **Descrição:** Botão "Deploy" que executa sequencialmente: git pull → executar migrations pendentes → limpar cache
- **Benefício:** Deploy com um clique ao invés de múltiplas operações manuais
- **Complexidade:** Alta
- **Implementação sugerida:**
  1. Novo `DeployController` com método `run()`
  2. Sequência: `git pull` → scan `sql/` → aplicar migrations → mover para `prontos/` → limpar cache
  3. Log detalhado de cada etapa
  4. Rollback se alguma etapa falhar (git stash, revert migrations)
- **Escopo:**
  - [x] Controller de Deploy
  - [x] Pipeline sequencial com log
  - [x] UI com progresso em tempo real
  - [x] Confirmação antes de executar
  - [x] Notificação de resultado
- **Status:** ✅ Implementado (2025-06-04)
- **Arquivos:** `DeployController.php`, `deploy/index.php`, `deploy/results.php`
- **Nota:** Pipeline com 3 etapas toggleáveis (Git Pull, Apply Migrations, Clear Cache), acesso restrito a superadmin

### AUTO-003: CSRF Middleware
- **Descrição:** Implementar proteção CSRF em todo o sistema Master
- **Benefício:** Proteção contra ataques de forja de requisições
- **Complexidade:** Média
- **Implementação sugerida:**
  1. Gerar token na sessão no `index.php`
  2. Helper function `csrf_field()` e `csrf_token()`
  3. Validar em toda requisição POST antes do roteamento
  4. Configurar header AJAX global no `app.js`
- **Status:** ✅ Resolvido pela integração com Akti (CsrfMiddleware global + csrf_field() + $.ajaxSetup)

### AUTO-004: Autoloader PSR-4
- **Descrição:** Substituir os 16 `require_once` por autoloader PSR-4 com namespaces
- **Benefício:** Novas classes são carregadas automaticamente, sem editar `index.php`
- **Complexidade:** Média
- **Implementação sugerida:**
  1. Adicionar `namespace AktiMaster\Controllers;` e `AktiMaster\Models;`
  2. Criar `master/app/bootstrap/autoload.php`
  3. Registrar no `composer.json` raiz
  4. Remover todos os `require_once`
- **Status:** ✅ Resolvido pela integração com Akti (namespace Akti\Controllers\Master\, PSR-4 autoloader em app/bootstrap/autoload.php)

---

## Fase 2 — Core Operations (Operações Essenciais)

### AUTO-005: Gerenciamento de Administradores
- **Descrição:** CRUD completo de `admin_users` com níveis de permissão (superadmin, operador, viewer)
- **Benefício:** Permite delegar operações para funcionários com permissões granulares
- **Complexidade:** Média
- **Escopo:**
  - [x] CRUD de admin_users no controller
  - [x] Views de listagem/criação/edição
  - [x] Níveis: superadmin (tudo), operador (sem delete/deploy), viewer (apenas leitura)
  - [x] Middleware de verificação de nível
  - [x] Política de senha (mínimo 8 chars, complexidade)
- **Status:** ✅ Implementado (2025-06-04)
- **Arquivos:** `AdminController.php`, `AdminUser.php` (extended), `admins/index.php`, `admins/create.php`, `admins/edit.php`
- **Migration:** `update_202604061422_1_adicionar_role_admin_users.sql`
- **Nota:** Self-protection (não pode deletar a si mesmo nem remover próprio role superadmin)

### AUTO-006: Rate Limiting de Login
- **Descrição:** Bloqueio automático após tentativas falhadas de login
- **Benefício:** Proteção contra brute force
- **Complexidade:** Baixa
- **Escopo:**
  - [x] Tabela `master_login_attempts`
  - [x] Registrar tentativas por IP + email
  - [x] Bloquear após 5 falhas em 30 min
  - [x] CAPTCHA após 3 falhas
  - [x] Página de "bloqueado temporariamente"
- **Status:** ✅ Resolvido pela integração com Akti (LoginAttempt model + IpGuard + reCAPTCHA)

### AUTO-007: Health Check Dashboard
- **Descrição:** Widget no dashboard com status em tempo real de todos os serviços
- **Benefício:** Detecção rápida de problemas
- **Complexidade:** Média
- **Escopo:**
  - [x] Ping MySQL (master + cada tenant)
  - [ ] Status Nginx (upstream) — não aplicável em ambiente XAMPP
  - [x] Status Node.js API
  - [x] Espaço em disco
  - [x] Uso de memória (PHP info)
  - [x] Uptime (MySQL uptime)
  - [x] Auto-refresh a cada 60s
- **Status:** ✅ Implementado (2025-06-04)
- **Arquivos:** `HealthCheckController.php`, `health/index.php`
- **Nota:** Dashboard com cards de status, info PHP com extensões, lista de tenant DBs com latência, endpoint JSON para auto-refresh

### AUTO-008: Backup Agendado via Painel
- **Descrição:** Configular agendamento de backups (diário, semanal) com retenção automática
- **Benefício:** Backups garantidos sem intervenção manual
- **Complexidade:** Alta
- **Escopo:**
  - [ ] Tabela `backup_schedules` (frequency, retention_days, last_run)
  - [ ] Cron job PHP ou endpoint chamado via crontab
  - [ ] Retenção automática (excluir backups > N dias)
  - [ ] Notificação de falha via email
  - [ ] UI para configurar no painel
- **Status:** ⬜ Planejado

### AUTO-009: Backup Individual por Tenant
- **Descrição:** Backup e restore de bancos individuais (não apenas full)
- **Benefício:** Precisão no restore, sem afetar outros tenants
- **Complexidade:** Média
- **Escopo:**
  - [ ] `mysqldump` por banco específico
  - [ ] Download do dump individual
  - [ ] Restore para o mesmo banco ou novo banco
  - [ ] Comparação com banco base antes do restore
- **Status:** ⬜ Planejado

---

## Fase 3 — Advanced (Gestão Avançada)

### AUTO-010: Impersonação de Tenant
- **Descrição:** Logar no sistema do cliente como admin para suporte técnico
- **Benefício:** Resolver problemas do cliente sem pedir credenciais
- **Complexidade:** Alta
- **Segurança:** Gerar token temporário, logar ação, timeout de 30min
- **Escopo:**
  - [ ] Botão "Acessar como Admin" na página de clientes
  - [ ] Gerar JWT temporário com admin_id + tenant_id
  - [ ] Redirect para `{subdomain}.useakti.com?impersonate_token=...`
  - [ ] Banner "Você está em modo suporte" no sistema do tenant
  - [ ] Log completo no admin_logs
  - [ ] Timeout automático de 30 minutos
- **Status:** ⬜ Planejado

### AUTO-011: Webhook de Auto-Deploy (GitHub/GitLab)
- **Descrição:** Endpoint que recebe webhook de push e executa deploy automático
- **Benefício:** Deploy contínuo — push para main = deploy automático
- **Complexidade:** Alta
- **Escopo:**
  - [ ] Endpoint `master/?page=deploy&action=webhook`
  - [ ] Validação de assinatura do webhook (HMAC-SHA256)
  - [ ] Filtrar por branch (apenas `main`)
  - [ ] Executar pipeline: pull → migrations → cache clear
  - [ ] Notificação de resultado via email/Slack
- **Status:** ⬜ Planejado

### AUTO-012: Monitoring e Alertas
- **Descrição:** Sistema de alertas para eventos críticos
- **Benefício:** Detecção proativa de problemas
- **Complexidade:** Alta
- **Escopo:**
  - [ ] Tabela `alert_rules` (metric, threshold, action)
  - [ ] Checks periódicos (cron): disk > 90%, memory > 90%, DB down, 500 errors spike
  - [ ] Notifications via email (PHPMailer)
  - [ ] Dashboard de alertas ativos
  - [ ] Histórico de alertas
- **Status:** ⬜ Planejado

### AUTO-013: Migração com Preview e Validação
- **Descrição:** Antes de executar SQL, mostrar preview do impacto e validar segurança
- **Benefício:** Evita execução acidental de queries destrutivas
- **Complexidade:** Média
- **Escopo:**
  - [ ] Parser que detecta DDL perigoso (DROP, TRUNCATE, DELETE sem WHERE)
  - [ ] Alerta especial para queries destrutivas
  - [ ] Dry-run em banco de teste antes do real
  - [ ] Diff visual do schema antes/depois
  - [ ] Confirmação por senha para queries destrutivas
- **Status:** ⬜ Planejado

### AUTO-014: Cron Jobs Manager
- **Descrição:** Visualizar e gerenciar cron jobs do servidor via painel
- **Benefício:** Controle centralizado de tarefas agendadas
- **Complexidade:** Média
- **Escopo:**
  - [ ] Listar crontab do sistema (`crontab -l`)
  - [ ] Adicionar/remover jobs via painel
  - [ ] Histórico de execuções
  - [ ] Status de cada job (last run, exit code)
- **Status:** ⬜ Planejado

---

## Fase 4 — Innovation (Longo Prazo)

### AUTO-015: API REST do Master
- **Descrição:** API RESTful para automações externas e CI/CD
- **Benefício:** Integração com ferramentas externas, scripts, GitHub Actions
- **Complexidade:** Alta
- **Escopo:**
  - [ ] Autenticação via API Key
  - [ ] Endpoints: `/api/tenants`, `/api/deploy`, `/api/migrations`, `/api/health`
  - [ ] Rate limiting por API key
  - [ ] Documentação Swagger/OpenAPI
- **Status:** ⬜ Planejado

### AUTO-016: Multi-Server Management
- **Descrição:** Gerenciar múltiplos servidores/VPS a partir de um único Master
- **Benefício:** Escala horizontal quando necessário
- **Complexidade:** Muito Alta
- **Status:** ⬜ Planejado

### AUTO-017: Dashboard de Faturamento
- **Descrição:** Controle financeiro de tenants (MRR, churn, upsell)
- **Benefício:** Visão de negócio
- **Complexidade:** Média
- **Escopo:**
  - [ ] MRR (Monthly Recurring Revenue) baseado nos planos
  - [ ] Gráfico de crescimento de receita
  - [ ] Alertas de churn (clientes que desativaram)
  - [ ] Relatório por período
- **Status:** ⬜ Planejado

### AUTO-018: 2FA (Two-Factor Authentication)
- **Descrição:** TOTP (Google Authenticator) para login no Master
- **Benefício:** Segurança extra para painel crítico
- **Complexidade:** Média
- **Escopo:**
  - [ ] Biblioteca TOTP (PHPGangsta/GoogleAuthenticator ou similar)
  - [ ] QR Code na configuração inicial
  - [ ] Backup codes
  - [ ] Obrigatório para superadmin
- **Status:** ⬜ Planejado

---

## Priorização Recomendada — Sprint Plan

### Sprint 1 (Imediato — Segurança)
1. ✅ SEC-001: Remover credenciais hardcoded → `.env` *(resolvido pela integração)*
2. ✅ AUTO-003: Implementar CSRF *(resolvido pela integração — CsrfMiddleware)*
3. ✅ AUTO-006: Rate limiting de login *(resolvido pela integração — LoginAttempt + IpGuard)*
4. ✅ SEC-005: Session management seguro *(resolvido pela integração — SessionGuard)*
5. ✅ SEC-006: Remover arquivos de teste *(2025-06-04)*

### Sprint 2 (Automações Core)
1. ✅ AUTO-001: Migration auto-detect `sql/` ⭐ *(2025-06-04)*
2. ✅ AUTO-002: Deploy automatizado (pull + migrations) *(2025-06-04)*
3. ✅ AUTO-004: Autoloader PSR-4 *(resolvido pela integração)*

### Sprint 3 (Gestão)
1. ✅ AUTO-005: CRUD de administradores *(2025-06-04)*
2. ✅ AUTO-007: Health check dashboard *(2025-06-04)*
3. ⬜ AUTO-008: Backup agendado

### Sprint 4 (Avançado)
1. ⬜ AUTO-010: Impersonação de tenant
2. ⬜ AUTO-011: Webhook auto-deploy
3. ⬜ AUTO-013: Migration com preview

### Sprint 5 (Inovação)
1. ⬜ AUTO-015: API REST
2. ⬜ AUTO-017: Dashboard de faturamento
3. ⬜ AUTO-018: 2FA
