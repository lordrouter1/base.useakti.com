# 02 — Auditoria dos Models Master

> **Data:** 06/04/2026
> **Escopo:** 8 models em `app/models/Master/` (`Akti\Models\Master`)

---

## Resumo

| Model | PSR-4 | Construtor | SQL Seguro | Type Hints | Arquitetura | Severidade |
|-------|-------|------------|------------|------------|-------------|------------|
| AdminUser | ✅ | ✅ PDO | ✅ | ⚠️ -2 return | ✅ Model | 🔵 Baixo |
| AdminLog | ✅ | ✅ PDO | ✅ | ✅ | ✅ Model | 🔵 Baixo |
| Plan | ✅ | ✅ PDO | ✅ | ⚠️ -1 return | ✅ Model | 🔵 Baixo |
| TenantClient | ✅ | ✅ PDO | ⚠️ Interpolação | ⚠️ -3 return | ✅ Model | 🟠 Alto |
| Migration | ✅ | ✅ PDO | ⚠️ exec() | ⚠️ -1 return | ✅ Model | 🟡 Médio |
| GitVersion | ✅ | ❌ Static | N/A | ✅ | ❌ Deveria ser Service | 🟡 Médio |
| Backup | ✅ | ❌ Static | N/A | ✅ | ❌ Deveria ser Service | 🟡 Médio |
| NginxLog | ✅ | ❌ Static | N/A | ✅ | ❌ Deveria ser Service | 🟡 Médio |

---

## Detalhes por Model

### 1. AdminUser.php

**Métodos:** `findByEmail(string)`, `findById(int)`, `updateLastLogin(int)`, `updatePassword(int, string)`, `readAll()`

**Positivo:**
- ✅ Todos os queries usam prepared statements
- ✅ Construtor recebe PDO
- ✅ Classe limpa e coesa

**Problemas:**
- 🔵 `findByEmail()` e `findById()` sem return type (`?array`)
- 🔵 Falta CRUD completo (sem `create()`, `update()`, `delete()`)

---

### 2. AdminLog.php

**Métodos:** `log(int, string, ?string, ?int, ?string)`, `readRecent(int)`

**Positivo:**
- ✅ Prepared statements com `PDO::PARAM_INT` explícito
- ✅ Captura IP via `$_SERVER['REMOTE_ADDR']`

**Problemas:**
- 🔵 IP via `REMOTE_ADDR` não considera X-Forwarded-For (aceitável para admin interno)

---

### 3. Plan.php

**Métodos:** `readAll()`, `readActive()`, `readOne(int)`, `create(array)`, `update(int, array)`, `delete(int)`

**Positivo:**
- ✅ CRUD completo
- ✅ `delete()` verifica clientes vinculados antes de excluir
- ✅ `readAll()` usa subquery para `total_clients`

**Problemas:**
- 🔵 `readOne()` sem return type (`?array`)

---

### 4. TenantClient.php

**Métodos:** `readAll()`, `readOne(int)`, `findBySubdomain(string)`, `findByDbName(string)`, `create(array)`, `update(int, array)`, `updateLimitsFromPlan(int, array)`, `toggleActive(int)`, `delete(int)`, `getStats()`, `provisionDatabase(...)`, `dropDatabase(...)`, `createTenantUser(...)`, static `connectTo(...)`, static `findMysqlBinary()`

**Positivo:**
- ✅ CRUD completo + métodos avançados de provisionamento
- ✅ `getStats()` com queries agregadas
- ✅ Usa `\Database::getMasterCredentials()` (não constantes hardcoded)

**Problemas:**
- 🟠 **SQL Injection em `provisionDatabase()`**: String interpolation em `CREATE DATABASE IF NOT EXISTS \`{$dbName}\``. Embora `$dbName` venha do controller (que o gera como `'akti_' + sanitized subdomain`), o model não valida o input
- 🟠 **Shell command em `provisionDatabase()`**: Usa `exec()` com mysqldump/mysql pipe. Mitigado por `escapeshellarg()`, mas pipeline complexa em Windows com `cmd /c`
- 🟡 `dropDatabase()` executa `DROP DATABASE` — operação destrutiva sem validação interna
- 🔵 3 métodos sem return type hint

---

### 5. Migration.php

**Métodos:** `listTenantDatabases()`, `getRegisteredTenants()`, `getSchemaStructure(string)`, `compareSchema(string)`, `compareAllTenants()`, `executeSqlOnDatabase(string, string)`, `executeSqlOnAllTenants(string, string, int, ?array)`, `executeSqlOnInitBase(string)`, `parseSqlStatements(string)`, `getMigrationHistory(int)`, `getMigrationDetail(int)`, `buildUserSelectQuery(PDO)`, `listAllTenantUsers()`, `listUsersFromDatabase(string)`, `toggleTenantUser(string, int)`

**Positivo:**
- ✅ Logs de migração com hash para deduplicação
- ✅ `parseSqlStatements()` separa statements corretamente
- ✅ Usa `\Database::connectTo()` para conexões cross-tenant

**Problemas:**
- 🟡 **`executeSqlOnDatabase()`** — executa SQL arbitrário via `$pdo->exec($stmt)`. É intencional (migração), mas requer controle de acesso rígido no controller
- 🟡 `listAllTenantUsers()` conecta a cada DB com credenciais master — se um DB estiver comprometido, as credenciais são expostas a ele
- 🔵 `getMigrationDetail()` sem return type

---

### 6. GitVersion.php ⚠️

**Tipo:** Classe estática (todos os métodos são `static`)

**Métodos (17 public):** `getBasePath()`, `getGitBin()`, `canExec()`, `execGit(string, string)`, `listRepositories()`, `diagnose()`, `getRepoInfo(string)`, `getAllReposInfo()`, `fetch(string)`, `pull(string)`, `pullRebase(string)`, `getLog(string, int)`, `getDetailedLog(string, int)`, `getBranches(string)`, `checkout(string, string)`, `stashAndPull(string)`, `forceReset(string)`, `getDiff(string)`, `getRepoSize(string)`, `getDebugLog()`

**Positivo:**
- ✅ `escapeshellarg()` usado em inputs
- ✅ Validação de path com `realpath()` e `is_dir()`
- ✅ Suporte cross-platform (Windows + Linux)

**Problemas:**
- 🟡 **Violação arquitetural** — Classe estática sem DB não é um Model. Deveria estar em `app/services/Master/GitVersion.php` (`Akti\Services\Master`)
- 🟡 `forceReset()` e `stashAndPull()` são operações destrutivas
- 🔵 Cache via `$diagCache` limpo por `ReflectionClass` no controller (fragil)

---

### 7. Backup.php ⚠️

**Tipo:** Classe estática

**Métodos (6 public):** `getBackupPath()`, `getBackupCommand()`, `canExec()`, `runBackup()`, `listBackups()`, `listBackupsViaExec()`, `getDownloadPath(string)`, `deleteBackup(string)`, `diagnose()`

**Positivo:**
- ✅ Path validation com `realpath()` + `strpos()` para prevenir traversal

**Problemas:**
- 🟡 **Violação arquitetural** — Deveria ser Service
- 🟡 `deleteBackup()` usa `sudo rm -f` — operação destrutiva
- 🟡 `listBackupsViaExec()` usa `sudo ls -la` como fallback

---

### 8. NginxLog.php ⚠️

**Tipo:** Classe estática

**Métodos (6 public):** `listLogFiles()`, `readTail(string, int)`, `readGzTail(string, int)`, `phpTail(string, int)`, `search(string, string, int)`, `analyzeErrors(string, int)`, `getDownloadPath(string)`, `diagnose()`

**Positivo:**
- ✅ Path traversal protegido: `strpos($realPath, $realBase) !== 0`
- ✅ `escapeshellarg()` em todas chamadas shell
- ✅ Fallback PHP puro quando `exec()` não está disponível

**Problemas:**
- 🟡 **Violação arquitetural** — Deveria ser Service
- 🟡 `search()` pode ter comportamento inesperado com regex especiais na query (não é injection, mas pode causar erros)
