# 07 — Roadmap de Correções v2

> **Data:** 06/04/2026
> **Escopo:** Correções priorizadas com base na auditoria pós-integração
> **Última atualização:** 06/04/2026 — Execução das correções P0–P3 + P4 parcial

---

## Status de Execução

| Prioridade | Total | Aplicados | Pendentes |
|------------|-------|-----------|-----------|
| P0 — Bloqueantes | 3 | 3 ✅ | 0 |
| P1 — Segurança | 5 | 5 ✅ | 0 |
| P2 — Boas Práticas | 7 | 7 ✅ | 0 |
| P3 — Arquitetura | 6 | 2 ✅ | 4 (futuro) |

---

## Prioridade 1 — Bloqueantes (antes de testar)

| # | Descrição | Arquivo | Tipo | Status |
|---|-----------|---------|------|--------|
| 1.1 | Corrigir `password_hash` → `password` no AuthService | `app/services/AuthService.php` | Bug fix | ✅ Aplicado |
| 1.2 | Corrigir `password_hash` → `password` no BackupController | `app/controllers/Master/BackupController.php` | Bug fix | ✅ Aplicado |
| 1.3 | Adicionar verificação `is_master_admin` no Application.php | `app/core/Application.php` | Segurança | ✅ Aplicado |

---

## Prioridade 2 — Segurança (antes de produção)

| # | Descrição | Arquivo(s) | Tipo | Status |
|---|-----------|------------|------|--------|
| 2.1 | Validar `selected_dbs[]` contra lista registrada em `apply()` | `MigrationController.php` | Validação | ✅ Aplicado |
| 2.2 | Validar `$_GET['db']` contra tenants em `compareDetail()` | `MigrationController.php` | Validação | ✅ Aplicado |
| 2.3 | Validar `$_POST['db_name']` em `toggleUser()`, `dbUsers()` | `MigrationController.php` | Validação | ✅ Aplicado |
| 2.4 | Usar `basename()` em `$_GET['file']` no `index()`, `read()` e `search()` | `LogController.php` | Path traversal | ✅ Aplicado |
| 2.5 | Sanitizar `Content-Disposition` filename no `download()` | `BackupController.php` + `LogController.php` | Header injection | ✅ Aplicado |

---

## Prioridade 3 — Boas Práticas (melhorias incrementais)

| # | Descrição | Arquivo(s) | Tipo | Status |
|---|-----------|------------|------|--------|
| 3.1 | Adicionar return types (`array\|false`) | `AdminUser`, `Plan`, `TenantClient` | Type hints | ✅ Aplicado |
| 3.2 | Escapar `$pageSubtitle` no header.php | `app/views/master/layout/header.php` | XSS | ✅ Aplicado |
| 3.3 | Escapar conteúdo de logs em `logs/index.php` | `app/views/master/logs/index.php` | XSS | ✅ Já estava escapado (htmlspecialchars + escapeHtml em JS) |
| 3.4 | Validar formato de branch em `checkout()` | `GitController.php` | Validação | ✅ Aplicado |
| 3.5 | Remover `app/views/master/auth/login.php` (não utilizada) | Views | Cleanup | ✅ Removido (diretório auth/ também) |
| 3.6 | Adicionar `AdminLog::log()` no `attemptMasterLogin()` | `AuthService.php` | Auditoria | ✅ Aplicado |
| 3.7 | Setar `$_SESSION['group_id']` no login master | `AuthService.php` | Compatibilidade | ✅ Aplicado (valor 0) |

---

## Prioridade 4 — Arquitetura (futuro)

| # | Descrição | Arquivo(s) | Tipo | Status |
|---|-----------|------------|------|--------|
| 4.1 | Mover GitVersion, Backup, NginxLog para `app/services/Master/` | 3 models + controllers | Refatoração | ⬜ Pendente (impacto médio, requer testes) |
| 4.2 | Remover `Database::getMasterCredentials()` | `database.php` + consumidores | Segurança | ⬜ Pendente (requer refatoração de consumidores) |
| 4.3 | Restringir `TenantManager::getMasterConfig()` para `private` | `tenant.php` + `database.php` | Segurança | ⬜ Pendente (requer refatoração de Database) |
| 4.4 | Adicionar validação de identifiers SQL em `provisionDatabase()` | `TenantClient.php` | SQL Injection | ✅ Aplicado (regex em dbName e charset) |
| 4.5 | Criptografar `db_password` em `tenant_clients` | `TenantClient.php` + schema | Segurança | ⬜ Pendente (requer migration + refatoração) |
| 4.6 | Substituir `extract()` no `renderMaster()` por passagem explícita | `MasterBaseController.php` | Segurança | ✅ Aplicado (loop com validação de nome) |

---

## Diagrama de Dependências

```
Prioridade 1 (Bloqueantes)
├── 1.1 Fix password field (AuthService)
├── 1.2 Fix password field (BackupController)
└── 1.3 Fix master_only bypass (Application)
     │
Prioridade 2 (Segurança)
├── 2.1-2.3 Validação de DB names (MigrationController)
├── 2.4 Path traversal (LogController)
└── 2.5 Header injection (BackupController)
     │
Prioridade 3 (Boas Práticas)
├── 3.1 Return types
├── 3.2-3.3 XSS escape
├── 3.4 Branch validation
├── 3.5 Cleanup view
└── 3.6-3.7 Auth logging
     │
Prioridade 4 (Arquitetura)
├── 4.1 Mover static classes para Services/
├── 4.2-4.3 Restringir acesso credenciais
├── 4.4 SQL identifier validation
├── 4.5 Encrypt DB passwords
└── 4.6 Eliminar extract()
```

---

## Notas

### CSRF — Avaliação Corrigida

Na análise inicial, a ausência de validação CSRF explícita nos controllers foi apontada como problema. Após revisão:

> O `CsrfMiddleware::handle()` é chamado globalmente pelo `Application::handle()` **antes** do dispatch dos controllers (inclusive para rotas `master_only`). Os formulários incluem `csrf_field()` e o `$.ajaxSetup()` no footer envia o header `X-CSRF-TOKEN` em todas as requisições AJAX.
>
> **Conclusão:** A proteção CSRF está **efetivamente implementada** via middleware. Os controllers não precisam validar CSRF manualmente.

### SQL Arbitrário em Migrações

O `MigrationController::apply()` aceita SQL arbitrário por design (é a ferramenta de migração). A segurança depende de:
1. ✅ Autenticação master obrigatória
2. ✅ Confirmação explícita de DBs alvo
3. ✅ Log de auditoria de cada execução
4. ⚠️ Sem whitelist de statements (DDL/DML completo permitido)

Este é um risco aceito — a ferramenta de migração precisa de liberdade total para ser funcional.
