# 04 — Auditoria da Infraestrutura

> **Data:** 06/04/2026
> **Escopo:** Database, AuthService, Application, Router — alterações para integração master

---

## 1. Database (`app/config/database.php`)

### Métodos Adicionados

#### `getMasterInstance(): PDO` ✅
```php
public static function getMasterInstance(): PDO
```
- ✅ Singleton com cache por DSN (`self::$instances[$dsn]`)
- ✅ Usa `TenantManager::getMasterConfig()` para credenciais
- ✅ Configura `ERRMODE_EXCEPTION`, `FETCH_ASSOC`, `EMULATE_PREPARES=false`
- ✅ Error handling com `error_log()` + `RuntimeException`
- ✅ Senha nunca exposta (encapsulada no PDO)

#### `getMasterCredentials(): array` ⚠️
```php
public static function getMasterCredentials(): array
{
    return TenantManager::getMasterConfig();
}
```
- ⚠️ Retorna credenciais em texto plano (host, user, **password**)
- ⚠️ Usado por `TenantClient::provisionDatabase()` e `Migration::listAllTenantUsers()`
- 🟡 **Risco:** Se resultado for logado/impresso, senha master é exposta
- 📋 **Recomendação:** Restringir uso e documentar que retorno é sensível

#### `connectTo(...): PDO` ✅
```php
public static function connectTo(string $host, int $port, string $user, string $pass, ?string $dbName = null, string $charset = 'utf8mb4'): PDO
```
- ✅ Cria PDO não-cacheada (usado para conexões temporárias cross-tenant)
- ✅ Mesmas configs de segurança (`ERRMODE_EXCEPTION`, etc.)
- ✅ `$dbName` opcional para conexões sem database específico

---

## 2. TenantManager (`app/config/tenant.php`)

### Alteração: `getMasterConfig()` de `private` para `public`

```php
public static function getMasterConfig(): array  // Era: private
```

- ⚠️ Visibilidade aumentada para permitir acesso de `Database::getMasterInstance()` e `Database::getMasterCredentials()`
- 🟡 **Implicação:** Qualquer código no sistema pode acessar credenciais do banco master
- 📋 **Recomendação futura:** Considerar padrão de acesso mais restrito (package-private ou friend class)

---

## 3. AuthService (`app/services/AuthService.php`)

### Método Adicionado: `attemptMasterLogin()`

**Posição no fluxo de login:**
```
1. Tentativa admin tenant → handleAdminLoginSuccess()
2. ★ Tentativa master admin → attemptMasterLogin()  ← ADICIONADO
3. Registro de falha + evento LOGIN_FAIL
4. Tentativa portal cliente → attemptPortalLogin()
```

**Análise:**

- ✅ Usa `\Database::getMasterInstance()` para conexão
- ✅ Instancia `AdminUser` com PDO master
- ✅ `session_regenerate_id(true)` para prevenir session fixation
- ✅ `loginAttempt->record()` e `clearFailures()` integrados
- ✅ `updateLastLogin()` chamado
- ✅ Log via `$this->logger->log('MASTER_LOGIN', ...)`
- ✅ Try/catch retorna `null` em caso de erro (não quebra o fluxo)

**Sessão estabelecida:**
| Variável | Valor | Check |
|----------|-------|-------|
| `user_id` | `$admin['id']` | ✅ Compatível com SessionGuard |
| `user_name` | `$admin['name']` | ✅ |
| `user_role` | `'master_admin'` | ✅ |
| `is_master_admin` | `true` | ✅ Flag master |
| `master_admin_id` | `$admin['id']` | ✅ |
| `last_activity` | `time()` | ✅ |

**🔴 BUG CRÍTICO:**
```php
if (!$admin || !password_verify($password, $admin['password_hash'])) {
```
O campo correto é `$admin['password']` (não `password_hash`). A coluna na tabela `admin_users` é `password`.

→ Ver [06_BUGS_CRITICOS.md](06_BUGS_CRITICOS.md)

**Ausências notáveis:**
- ❌ Não registra log no `AdminLog` do master (usa `$this->logger->log()` do tenant)
- 🔵 Não seta `$_SESSION['group_id']` (pode causar warnings em código que assume existência)

---

## 4. Application (`app/core/Application.php`)

### Alteração 1: Redirect de login para master

```php
if ($this->page === 'login' && $this->action !== 'logout') {
    $redirect = !empty($_SESSION['is_master_admin']) ? '?page=master_dashboard' : '?';
    header('Location: ' . $redirect);
    exit;
}
```
- ✅ Master admin logado → redireciona para dashboard master (não tenant)
- ✅ Admin tenant → redireciona para home normal

### Alteração 2: Bypass para rotas `master_only`

```php
$routeConfig = $this->router->getRouteConfig($this->page);
if (!empty($routeConfig['master_only'])) {
    CsrfMiddleware::handle();
    Security::generateCsrfToken();
    return true;
}
```

**🔴 BUG CRÍTICO:**
O bypass **não verifica se o usuário é realmente master admin**. Qualquer usuário logado (tenant admin, portal) pode acessar páginas `master_only`.

A proteção existe nos controllers via `requireMasterAuth()`, mas a falta de verificação no Application permite que:
1. O controller seja instanciado e executado até o `requireMasterAuth()`
2. Um atacante receba a mensagem de erro/redirect do controller em vez de um bloqueio limpo

→ Ver [06_BUGS_CRITICOS.md](06_BUGS_CRITICOS.md)

---

## 5. Router (`app/core/Router.php`)

### Método Adicionado: `getRouteConfig()`

```php
public function getRouteConfig(string $page): ?array
{
    return $this->routes[$page] ?? null;
}
```

- ✅ Simples e correto
- ✅ Retorna `null` para rotas inexistentes
- ✅ Usado por `Application::handle()` para detectar flag `master_only`

---

## 6. Rotas (`app/config/routes.php`)

### 7 Rotas Master Adicionadas

| Rota | Controller | Actions | master_only |
|------|-----------|---------|-------------|
| `master_dashboard` | `Master\DashboardController` | — | ✅ |
| `master_plans` | `Master\PlanController` | create, store, edit, update, delete | ✅ |
| `master_clients` | `Master\ClientController` | create, store, edit, update, toggleActive, delete, createTenantUser, getPlanLimits | ✅ |
| `master_migrations` | `Master\MigrationController` | compareDetail, apply, results, users, createUser, toggleUser, dbUsers | ✅ |
| `master_git` | `Master\GitController` | fetchAll, fetch, pull, forceReset, detail, checkout, pullAll, diagnoseJson | ✅ |
| `master_backup` | `Master\BackupController` | run, download, diagnoseJson, delete | ✅ |
| `master_logs` | `Master\LogController` | read, search, download | ✅ |

**Análise:**
- ✅ Prefixo `master_` evita colisão com rotas do tenant
- ✅ `master_only => true` em todas
- ✅ Controller class names usam formato PSR-4 correto (`Master\\NomeController`)
- ✅ Actions mapeadas 1:1 com métodos dos controllers
- ✅ Nenhuma rota marcada como `public` (requer autenticação)
