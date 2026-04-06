# Auditoria de Banco de Dados — Akti Master v1

> **Data da Auditoria:** 06/04/2026  
> **Escopo:** Queries, models, prepared statements, migrations, multi-tenant

---

## 1. Resumo Executivo

| Aspecto | Nota | Status |
|---------|------|--------|
| Prepared Statements | 8/10 | ✅ |
| PDO Configuration | 9/10 | ✅ |
| Model CRUD Pattern | 8/10 | ✅ |
| Schema Design | 7/10 | ⚠️ |
| Migration System | 7/10 | ⚠️ |
| Multi-tenant Isolation | 8/10 | ✅ |

---

## 2. PDO Configuration ✅

**Arquivo:** `master/app/config/database.php`

```php
PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
PDO::ATTR_EMULATE_PREPARES => false,
```

Configuração correta e segura:
- `ERRMODE_EXCEPTION` — erros lançam exceções (sem falhas silenciosas)
- `FETCH_ASSOC` — arrays associativos (sem duplicação numérica)
- `EMULATE_PREPARES = false` — prepared statements nativos (proteção real contra SQLi)

---

## 3. Uso de Prepared Statements

### ✅ Bem implementados:
- `AdminUser.php` — Todos os métodos usam prepared statements
- `Plan.php` — CRUD completo com prepared statements
- `TenantClient.php` — CRUD com prepared statements
- `AdminLog.php` — Insert com prepared statements
- `Migration.php` — Queries de log com prepared statements

### ⚠️ Exceções (interpolação direta):

**DB-001:** `Migration.php:76` — Nome de tabela interpolado:
```php
$cols = $pdo->query("SHOW FULL COLUMNS FROM `{$table}`")->fetchAll();
```
**Mitigação:** `$table` vem de `SHOW TABLES` (fonte confiável), mas deveria ser validado.

**DB-002:** `TenantClient.php:218` — DDL com interpolação:
```php
$rootPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}`...");
$rootPdo->exec("CREATE USER IF NOT EXISTS '{$dbUser}'@'{$dbHost}'...");
```
**Mitigação:** `$dbName` é gerado como `'akti_' + subdomain` com sanitização, mas sem validação regex.

**DB-003:** `TenantClient.php:307` — DROP com interpolação:
```php
$rootPdo->exec("DROP DATABASE IF EXISTS `{$dbName}`");
```
**Nota:** Estas queries são DDL (CREATE, DROP) que não aceitam prepared statements nativamente. A solução correta é validação whitelist.

---

## 4. Schema do Banco Master

**Arquivo:** `master/multi_tenant_master.sql`

### Tabelas:

| Tabela | Colunas Principais | Status |
|--------|-------------------|--------|
| `tenant_clients` | id, plan_id, client_name, subdomain, db_*, max_*, is_active, created_at, updated_at | ✅ |
| `plans` | id, plan_name, description, max_*, price, is_active, created_at, updated_at | ✅ |
| `admin_users` | id, name, email, password, last_login, created_at | ✅ |
| `admin_logs` | id, admin_id, action, target_type, target_id, details, ip_address, created_at | ✅ |
| `migration_logs` | id, db_name, migration_name, sql_hash, statements_*, status, error_log, applied_by, applied_at | ✅ |

### ⚠️ Observações:

**DB-004:** Tabela `tenant_clients` armazena `db_password` em texto plano:
```sql
db_password VARCHAR(255) NOT NULL DEFAULT '',
```
**Risco:** Se o banco master for comprometido, todas as senhas de banco de todos os tenants ficam expostas.
**Correção:** Considerar criptografia simétrica (AES) para o `db_password`.

**DB-005:** Falta índice em `admin_logs.created_at` — a query `readRecent()` faz `ORDER BY created_at DESC` sem índice dedicado.

**DB-006:** Falta tabela `login_attempts` para rate limiting do login master.

---

## 5. Model Inventory

| Model | Métodos | CRUD Completo | Prepared Stmts |
|-------|---------|---------------|----------------|
| AdminUser | 5 | Parcial (sem create/delete) | ✅ |
| AdminLog | 2 | Parcial (log + read) | ✅ |
| Plan | 6 | ✅ Completo | ✅ |
| TenantClient | 11 | ✅ Completo + extras | ⚠️ Parcial |
| Migration | 12 | N/A (utilitário) | ⚠️ Parcial |
| GitVersion | 15+ | N/A (exec-based) | N/A |
| Backup | 7 | N/A (exec-based) | N/A |
| NginxLog | 8 | N/A (file-based) | N/A |

---

## 6. Migration System

### Funcionalidades existentes:
- ✅ Comparação de schema entre `akti_init_base` e bancos tenant
- ✅ Detecção de tabelas/colunas faltando ou extras
- ✅ Execução de SQL em múltiplos bancos simultaneamente
- ✅ Log de migrações aplicadas com hash SHA-256 para idempotência
- ✅ Suporte à aplicação no banco de referência (`akti_init_base`)

### ❌ Funcionalidades ausentes:

**DB-007:** O sistema de migrations **não lê automaticamente da pasta `sql/`**. O operador precisa:
1. Abrir o arquivo SQL manualmente
2. Copiar o conteúdo
3. Colar na textarea do painel de migrations
4. Selecionar os bancos alvo
5. Executar

**Automação necessária:** O painel deveria listar automaticamente os arquivos `.sql` pendentes da pasta `sql/` para seleção e execução com um clique.

**DB-008:** Não há rollback de migrações. Se uma migração falhar parcialmente, não há mecanismo para desfazer.

**DB-009:** Não há validação do SQL antes de executar (sintaxe, queries destrutivas como DROP/DELETE sem WHERE).

---

## 7. Multi-Tenant Database Operations

### Provisionamento de Banco ✅
- Clona `akti_init_base` via `mysqldump | mysql`
- Cria usuário MySQL dedicado
- Usa arquivo `.cnf` temporário para credenciais (segurança)
- Conta tabelas para feedback
- Limpa arquivo temporário em finally

### Comparação de Schema ✅
- Compara todas as tabelas e colunas
- Detecta tabelas/colunas faltando, extras e tipos diferentes
- Executa para todos os tenants em batch

### Execução Cross-Tenant ✅
- Aplica SQL em múltiplos bancos
- Log com hash para evitar duplicação
- Status por banco (success/failed/partial/skipped)
