# 03 — Auditoria dos Controllers Master

> **Data:** 06/04/2026
> **Escopo:** 8 controllers em `app/controllers/Master/` (`Akti\Controllers\Master`)

---

## Resumo

| Controller | Namespace | Auth | CSRF Controller | Input Val. | SQL Direto | Issues |
|-----------|-----------|------|-----------------|------------|------------|--------|
| MasterBaseController | ✅ | ✅ base | N/A | N/A | ❌ | `extract()` risk |
| DashboardController | ✅ | ✅ | N/A (GET) | ✅ | ❌ | Nenhum |
| PlanController | ✅ | ✅ | ❌ Ausente | ✅ | ❌ | CSRF faltando |
| ClientController | ✅ | ✅ | ❌ Ausente | ✅ | ❌ | CSRF faltando |
| MigrationController | ✅ | ✅ | ❌ Ausente | ⚠️ | ❌ | CSRF + SQL raw |
| GitController | ✅ | ✅ | ❌ Ausente | ⚠️ | ❌ | CSRF + branch val. |
| BackupController | ✅ | ✅ | ❌ Ausente | ⚠️ | ❌ | CSRF + header inj. |
| LogController | ✅ | ✅ | N/A (GET) | ⚠️ | ❌ | File path val. |

---

## Nota sobre CSRF

O `CsrfMiddleware::handle()` global **já valida CSRF automaticamente** para requests POST no `Application::handle()`. As views já incluem `csrf_field()` nos formulários e o `$.ajaxSetup()` envia o header `X-CSRF-TOKEN` para AJAX. Portanto, a proteção CSRF está **efetivamente presente** via middleware — os controllers NÃO precisam validar CSRF manualmente.

> ✅ **CSRF está OK** — proteção via middleware global, não precisa de validação explícita nos controllers.

---

## Detalhes por Controller

### 1. MasterBaseController.php (abstrato)

**Herança:** `extends BaseController`

**Construtor:**
```php
public function __construct(?\PDO $db = null)
{
    $this->db = \Database::getMasterInstance();
}
```
- ✅ Ignora injeção do Router (sempre usa master DB)

**Métodos protegidos:**
- `requireMasterAuth()` — verifica `$_SESSION['is_master_admin']`, AJAX → 403, não-AJAX → redirect
- `getMasterAdminId()` — retorna `$_SESSION['master_admin_id']`
- `logAction()` — wrapper de `AdminLog::log()` em try/catch
- `renderMaster()` — inclui header + view + footer do master

**Problemas:**
- 🟡 `renderMaster()` usa `extract($data)` — pode causar colisão de variáveis. Mitigado pelo fato de que os dados são controlados pelo controller

---

### 2. DashboardController.php ✅

**Métodos:** `index()`

**Análise:**
- ✅ `requireMasterAuth()` chamado
- ✅ Sem input do usuário
- ✅ Usa models para obter dados (`TenantClient::getStats()`, `AdminLog::readRecent()`)
- ✅ Sem problemas encontrados

---

### 3. PlanController.php

**Métodos:** `index()`, `create()`, `store()`, `edit()`, `update()`, `delete()`

**Análise:**
- ✅ `requireMasterAuth()` em TODOS os métodos
- ✅ Input: strings com `trim()`, numéricos com `(int)`, float com `str_replace(','.'.')` + `(float)`
- ✅ Validação: nome obrigatório, plano existe antes de editar/excluir
- ✅ `logAction()` registra todas as ações
- ✅ Redirects com `?page=master_plans`

**Problemas:**
- Nenhum problema significativo

---

### 4. ClientController.php

**Métodos:** `index()`, `create()`, `store()`, `edit()`, `update()`, `toggleActive()`, `delete()`, `createTenantUser()`, `getPlanLimits()`

**Análise:**
- ✅ `requireMasterAuth()` em TODOS os métodos
- ✅ `subdomain` sanitizado com `preg_replace('/[^a-zA-Z0-9]/', '', ...)`
- ✅ Verificação de duplicidade (`findBySubdomain()`, `findByDbName()`)
- ✅ `delete()` exige confirmação de nome do DB + senha admin (`password_verify`)
- ✅ `getPlanLimits()` retorna dados de plano via JSON (com cast `(int)` no ID)

**Problemas:**
- 🟡 Senha DB armazenada em texto plano em `tenant_clients.db_password` (legado do master original — não é um problema introduzido pela integração)
- 🔵 `getPlanLimits()` retorna objeto `$plan` completo — poderia filtrar campos sensíveis

---

### 5. MigrationController.php

**Métodos:** `index()`, `compareDetail()`, `apply()`, `results()`, `users()`, `createUser()`, `toggleUser()`, `dbUsers()`

**Análise:**
- ✅ `requireMasterAuth()` em TODOS os métodos
- ✅ `apply()` valida SQL não vazio e nome de migração
- ✅ `results` usa sessão para armazenar resultados temporários

**Problemas:**
- 🟠 **`apply()`** — executa SQL arbitrário (`$_POST['sql_content']`). É a função primária do módulo, mas:
  - Não valida que `selected_dbs[]` são DBs reais (poderia verificar contra lista registrada)
  - Não faz nenhum parsing/whitelist do SQL (por design, permite DDL/DML completo)
- 🟡 **`compareDetail()`** — `$_GET['db']` não é validado contra lista de tenants registrados
- 🟡 **`createUser()`** — `$_POST['db_name']` não é validado contra lista de tenants
- 🟡 **`toggleUser()`** — `$_POST['db_name']` idem

---

### 6. GitController.php

**Métodos:** `index()`, `fetchAll()`, `fetch()`, `pull()`, `forceReset()`, `detail()`, `checkout()`, `pullAll()`, `diagnoseJson()`

**Análise:**
- ✅ `requireMasterAuth()` em TODOS os métodos
- ✅ `resolveRepoPath()`:
  - `basename()` remove `..` e separadores
  - Valida que `.git/` existe no diretório
  - ✅ **Protegido contra path traversal**
- ✅ `forceReset()` exige confirmação explícita (`$_POST['confirmed']`)
- ✅ `pull()` verifica alterações locais antes de puxar
- ✅ `logAction()` registra git operations

**Problemas:**
- 🟡 **`checkout()`** — `$branch` é `trim()`-ado mas sem validação de formato. `GitVersion::checkout()` usa `escapeshellarg()` internamente, então não há injection, mas branch names inválidos poderiam gerar erros confusos
- 🔵 `diagnoseJson()` usa `ReflectionClass` para limpar cache — funcional mas frágil

---

### 7. BackupController.php

**Métodos:** `index()`, `run()`, `download()`, `diagnoseJson()`, `delete()`

**Análise:**
- ✅ `requireMasterAuth()` em TODOS os métodos
- ✅ `download()` usa `basename()` para filename
- ✅ `delete()` exige confirmação de nome + senha admin
- ✅ Streaming de arquivos grandes (>50MB) com `fread()` chunked

**Problemas:**
- 🔴 **`delete()`** — usa `$admin['password_hash']` mas a coluna se chama `password`. **BUG CRÍTICO** — ver `06_BUGS_CRITICOS.md`
- 🟡 **`download()`** — header `Content-Disposition` usa `$filename` sem sanitizar aspas. Mitigado por `basename()` que remove a maioria dos caracteres perigosos, mas filenames com `"` poderiam causar problemas

---

### 8. LogController.php

**Métodos:** `index()`, `read()`, `search()`, `download()`

**Análise:**
- ✅ `requireMasterAuth()` em TODOS os métodos
- ✅ `download()` usa `basename()` para filename
- ✅ `read()` limita linhas entre 50-1000

**Problemas:**
- 🟡 **`index()` e `read()`** — `$_GET['file']` é `trim()`-ado mas não usa `basename()`. A proteção depende de `NginxLog::readTail()` que internamente valida com `realpath()` + `strpos()`, mas seria melhor usar `basename()` no controller também
- 🔵 `search()` — `$_GET['q']` é passado diretamente ao `NginxLog::search()` que usa `escapeshellarg()`. Seguro, mas caracteres regex poderiam gerar resultados inesperados
