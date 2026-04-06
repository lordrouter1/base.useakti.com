# Roadmap de Integração — Master → Akti Unificado

> **Data:** 06/04/2026  
> **Objetivo:** Unificar o sistema Master (painel admin interno) ao sistema principal Akti,  
> reaproveitando autoloader PSR-4, rotas declarativas, middleware (CSRF, rate limiting),  
> sessão segura, .env e toda a infraestrutura existente.  
> **Princípio:** O Master continua sendo acessível **apenas pelo proprietário e funcionários** —  
> jamais por clientes dos tenants.

---

## Visão Geral da Integração

### Antes (Atual)
```
/master/index.php          ← Entry point separado
/master/app/config/         ← Config própria (credenciais hardcoded)
/master/app/controllers/    ← 8 controllers sem namespace
/master/app/models/         ← 8 models sem namespace
/master/app/views/          ← 14 views com layout próprio
/master/assets/             ← CSS/JS próprios
```

### Depois (Integrado)
```
/index.php                          ← Entry point ÚNICO (já existente)
/app/config/routes.php              ← Rotas master adicionadas aqui
/app/config/menu.php                ← Menu master adicionado aqui
/app/controllers/Master/            ← Controllers com namespace Akti\Controllers\Master
/app/models/Master/                 ← Models com namespace Akti\Models\Master
/app/views/master/                  ← Views organizadas por módulo
/app/views/master/layout/           ← Header/footer do painel master
/assets/css/master.css              ← CSS do painel master
/assets/js/master.js                ← JS do painel master
/.env                               ← Credenciais unificadas (já usa AKTI_MASTER_DB_*)
```

---

## Pré-requisitos (Já Existentes no Akti)

| Recurso | Status | Detalhes |
|---------|--------|---------|
| Autoloader PSR-4 | ✅ Pronto | `app/bootstrap/autoload.php` — mapeia `Akti\*` |
| .env com AKTI_MASTER_DB_* | ✅ Pronto | `tenant.php::getMasterConfig()` já lê do .env |
| CSRF Middleware | ✅ Pronto | `CsrfMiddleware::handle()` global |
| Rate Limiting | ✅ Pronto | `LoginAttempt` — 3 fails → CAPTCHA, 5+ → block |
| Session Segura | ✅ Pronto | `session.php` — httponly, samesite, strict_mode |
| Router Declarativo | ✅ Pronto | `routes.php` + `Router.php` |
| Escape XSS | ✅ Pronto | `e()`, `eAttr()`, `eJs()` globais |
| Security Headers | ✅ Pronto | `SecurityHeadersMiddleware` |

---

## Fases de Implementação

### FASE 1 — Estrutura e Namespace (Foundation)

#### 1.1 Criar estrutura de pastas
```
app/controllers/Master/
app/models/Master/
app/views/master/
    auth/
    backup/
    clients/
    dashboard/
    git/
    layout/
    logs/
    migrations/
    plans/
assets/css/master.css
assets/js/master.js
```

#### 1.2 Migrar Models com namespace `Akti\Models\Master`

Converter cada model do master para PSR-4:

| Origem | Destino | Namespace |
|--------|---------|-----------|
| `master/app/models/AdminUser.php` | `app/models/Master/AdminUser.php` | `Akti\Models\Master` |
| `master/app/models/Plan.php` | `app/models/Master/Plan.php` | `Akti\Models\Master` |
| `master/app/models/TenantClient.php` | `app/models/Master/TenantClient.php` | `Akti\Models\Master` |
| `master/app/models/AdminLog.php` | `app/models/Master/AdminLog.php` | `Akti\Models\Master` |
| `master/app/models/Migration.php` | `app/models/Master/Migration.php` | `Akti\Models\Master` |
| `master/app/models/GitVersion.php` | `app/models/Master/GitVersion.php` | `Akti\Models\Master` |
| `master/app/models/Backup.php` | `app/models/Master/Backup.php` | `Akti\Models\Master` |
| `master/app/models/NginxLog.php` | `app/models/Master/NginxLog.php` | `Akti\Models\Master` |

Cada arquivo deve:
- Adicionar `namespace Akti\Models\Master;`
- Adicionar `use PDO;` onde necessário
- Receber `PDO $db` no construtor (conexão ao `akti_master`)
- Remover referências à classe `Database` do master antigo — usar `Database::getInstance('akti_master')` do Akti

#### 1.3 Migrar Controllers com namespace `Akti\Controllers\Master`

| Origem | Destino | Namespace |
|--------|---------|-----------|
| `master/app/controllers/DashboardController.php` | `app/controllers/Master/DashboardController.php` | `Akti\Controllers\Master` |
| `master/app/controllers/PlanController.php` | `app/controllers/Master/PlanController.php` | `Akti\Controllers\Master` |
| `master/app/controllers/ClientController.php` | `app/controllers/Master/ClientController.php` | `Akti\Controllers\Master` |
| `master/app/controllers/MigrationController.php` | `app/controllers/Master/MigrationController.php` | `Akti\Controllers\Master` |
| `master/app/controllers/GitController.php` | `app/controllers/Master/GitController.php` | `Akti\Controllers\Master` |
| `master/app/controllers/BackupController.php` | `app/controllers/Master/BackupController.php` | `Akti\Controllers\Master` |
| `master/app/controllers/LogController.php` | `app/controllers/Master/LogController.php` | `Akti\Controllers\Master` |

**NOTA:** O `AuthController` do master será ELIMINADO — a autenticação será feita pelo `AuthService` do Akti.

Cada controller deve:
- Adicionar `namespace Akti\Controllers\Master;`
- Adicionar `use Akti\Models\Master\*;` para os models
- Usar `csrf_field()` nos formulários
- Usar `e()` para escape XSS nas views
- Obter conexão master via `Database::getInstance('akti_master')` ou injeção do container
- Verificar `$_SESSION['is_master_admin']` (nova flag de sessão)

#### 1.4 Atualizar Autoloader PSR-4

O autoloader atual mapeia `Akti\Controllers\` → `app/controllers/`. O PSR-4 resolve subnamespaces automaticamente:
- `Akti\Controllers\Master\DashboardController` → `app/controllers/Master/DashboardController.php`
- `Akti\Models\Master\Plan` → `app/models/Master/Plan.php`

**Nenhuma alteração no autoloader é necessária** — a resolução de sub-diretórios já funciona.

---

### FASE 2 — Autenticação Unificada

#### 2.1 Fluxo de Login Integrado

O login ÚNICO do Akti (`?page=login`) deve verificar em duas fontes:

```
Usuário digita email + senha
    ↓
1. Tentar login no banco do TENANT atual (tabela users)
   → Se sucesso: sessão normal de tenant, redirect → ?page=dashboard
    ↓
2. Se falhou no tenant: Tentar login no banco MASTER (tabela admin_users)
   → Se sucesso: sessão master, redirect → ?page=master_dashboard
    ↓
3. Se falhou em ambos: "Credenciais inválidas"
```

#### 2.2 Alterar `AuthService::attemptLogin()`

Adicionar tentativa de login master **após** a falha no tenant e **antes** da tentativa de portal:

```php
// Em AuthService.php, dentro de attemptLogin():

// 1. Tentativa de login admin do tenant
if ($this->userModel->login($email, $password)) {
    return $this->handleAdminLoginSuccess($email, $ip);
}

// 2. *** NOVO *** Tentativa de login master
$masterResult = $this->attemptMasterLogin($email, $password, $ip);
if ($masterResult !== null) {
    return $masterResult;
}

// 3. Registrar falha e tentar portal
$this->loginAttempt->record($ip, $email, false);
// ... resto do código existente
```

#### 2.3 Método `attemptMasterLogin()` no AuthService

```php
private function attemptMasterLogin(string $email, string $password, string $ip): ?array
{
    try {
        $masterDb = \Database::getInstance(getenv('AKTI_MASTER_DB_NAME') ?: 'akti_master');
        $adminUser = new \Akti\Models\Master\AdminUser($masterDb);
        $admin = $adminUser->findByEmail($email);

        if (!$admin || !password_verify($password, $admin['password'])) {
            return null; // Não é admin master
        }

        // Login master bem-sucedido
        $this->loginAttempt->record($ip, $email, true);
        $this->loginAttempt->clearFailures($ip, $email);

        session_regenerate_id(true);

        $_SESSION['user_id']          = $admin['id'];
        $_SESSION['user_name']        = $admin['name'];
        $_SESSION['user_role']        = 'master_admin';
        $_SESSION['is_master_admin']  = true;
        $_SESSION['master_admin_id']  = $admin['id'];
        $_SESSION['last_activity']    = time();

        // Atualizar last_login
        $adminUser->updateLastLogin($admin['id']);

        // Log master
        $adminLog = new \Akti\Models\Master\AdminLog($masterDb);
        $adminLog->log($admin['id'], 'login', 'admin', $admin['id'], 'Login via painel unificado');

        return [
            'success'      => true,
            'error'        => null,
            'show_captcha' => false,
            'redirect'     => '?page=master_dashboard',
            'type'         => 'master',
        ];
    } catch (\Exception $e) {
        error_log('[AuthService] Master login check failed: ' . $e->getMessage());
        return null;
    }
}
```

#### 2.4 Sessão Master

| Variável de Sessão | Valor | Propósito |
|-------------------|-------|-----------|
| `$_SESSION['is_master_admin']` | `true` | Flag que identifica acesso master |
| `$_SESSION['master_admin_id']` | int | ID do admin na tabela `admin_users` |
| `$_SESSION['user_role']` | `'master_admin'` | Role especial que bypassa permissões de tenant |
| `$_SESSION['user_id']` | int | Compatibilidade com SessionGuard |
| `$_SESSION['user_name']` | string | Nome do admin master |

---

### FASE 3 — Rotas e Middleware Master

#### 3.1 Registrar Rotas Master em `routes.php`

Adicionar rotas com prefixo `master_` para evitar colisão:

```php
// ══════════════════════════════════════════════════════════════
// MASTER ADMIN (acesso restrito a master_admin)
// ══════════════════════════════════════════════════════════════

'master_dashboard' => [
    'controller'     => 'Master\\DashboardController',
    'default_action' => 'index',
    'public'         => false,
    'master_only'    => true,  // ← NOVA FLAG
    'actions'        => [],
],

'master_plans' => [
    'controller'     => 'Master\\PlanController',
    'default_action' => 'index',
    'public'         => false,
    'master_only'    => true,
    'actions'        => [
        'create' => 'create',
        'store'  => 'store',
        'edit'   => 'edit',
        'update' => 'update',
        'delete' => 'delete',
    ],
],

'master_clients' => [
    'controller'     => 'Master\\ClientController',
    'default_action' => 'index',
    'public'         => false,
    'master_only'    => true,
    'actions'        => [
        'create'          => 'create',
        'store'           => 'store',
        'edit'            => 'edit',
        'update'          => 'update',
        'delete'          => 'delete',
        'toggleActive'    => 'toggleActive',
        'createTenantUser'=> 'createTenantUser',
        'getPlanLimits'   => 'getPlanLimits',
    ],
],

'master_migrations' => [
    'controller'     => 'Master\\MigrationController',
    'default_action' => 'index',
    'public'         => false,
    'master_only'    => true,
    'actions'        => [
        'apply'         => 'apply',
        'results'       => 'results',
        'compareDetail' => 'compareDetail',
        'users'         => 'users',
        'createUser'    => 'createUser',
        'toggleUser'    => 'toggleUser',
        'dbUsers'       => 'dbUsers',
    ],
],

'master_git' => [
    'controller'     => 'Master\\GitController',
    'default_action' => 'index',
    'public'         => false,
    'master_only'    => true,
    'actions'        => [
        'fetch'        => 'fetch',
        'fetchAll'     => 'fetchAll',
        'pull'         => 'pull',
        'pullAll'      => 'pullAll',
        'forceReset'   => 'forceReset',
        'detail'       => 'detail',
        'checkout'     => 'checkout',
        'diagnoseJson' => 'diagnoseJson',
    ],
],

'master_backup' => [
    'controller'     => 'Master\\BackupController',
    'default_action' => 'index',
    'public'         => false,
    'master_only'    => true,
    'actions'        => [
        'run'          => 'run',
        'download'     => 'download',
        'delete'       => 'delete',
        'diagnoseJson' => 'diagnoseJson',
    ],
],

'master_logs' => [
    'controller'     => 'Master\\LogController',
    'default_action' => 'index',
    'public'         => false,
    'master_only'    => true,
    'actions'        => [
        'read'     => 'read',
        'search'   => 'search',
        'download' => 'download',
    ],
],
```

#### 3.2 Middleware `master_only` no Application.php

Adicionar verificação no `Application::handle()` logo após o auth check:

```php
// Após verificar $_SESSION['user_id'] existe...

// Verificar se é página master_only
$routeConfig = $this->router->getRouteConfig();
if (!empty($routeConfig['master_only']) && empty($_SESSION['is_master_admin'])) {
    http_response_code(403);
    require 'app/views/errors/403.php';
    exit;
}
```

#### 3.3 Injeção de Conexão Master

Os controllers master precisam da conexão `akti_master`, não do tenant atual.  
Alterar o Router/Container para injetar automaticamente:

```php
// Em Router::dispatch() ou no container, ao instanciar controllers Master\*:
if (str_starts_with($controllerClass, 'Akti\\Controllers\\Master\\')) {
    $masterDbName = getenv('AKTI_MASTER_DB_NAME') ?: 'akti_master';
    $db = Database::getInstance($masterDbName);
} else {
    $db = Database::getInstance(); // tenant atual
}
```

---

### FASE 4 — Views e Layout Master

#### 4.1 Migrar Views

Mover views mantendo a mesma estrutura visual, mas adaptando:

| Origem | Destino |
|--------|---------|
| `master/app/views/layout/header.php` | `app/views/master/layout/header.php` |
| `master/app/views/layout/footer.php` | `app/views/master/layout/footer.php` |
| `master/app/views/dashboard/index.php` | `app/views/master/dashboard/index.php` |
| ... etc | ... etc |

#### 4.2 Adaptar Views

Em cada view:
- Trocar `htmlspecialchars()` por `e()` (helper global do Akti)
- Adicionar `<?= csrf_field() ?>` em todos os formulários
- Adicionar CSRF header no setup AJAX: `$.ajaxSetup({ headers: {'X-CSRF-TOKEN': '<?= csrf_token() ?>'} });`
- Atualizar URLs de `?page=xxx&action=yyy` para usar prefixo `master_`
- Ajustar paths de assets para `/assets/css/master.css` e `/assets/js/master.js`

#### 4.3 Layout Master

O header master deve:
- Manter sidebar e topbar próprios (visual diferente do Akti tenant)
- Exibir badge "MASTER ADMIN" no topbar
- Links do menu apontando para `?page=master_*`
- Botão "Voltar ao Akti" (se veio de um login de tenant)
- Logout via `?page=login&action=logout` (mesmo do Akti)

#### 4.4 Migrar Assets

- `master/assets/css/style.css` → `assets/css/master.css`
- `master/assets/js/app.js` → `assets/js/master.js`
- `master/logos/*` → `assets/logos/master/` ou reutilizar `assets/logos/`

---

### FASE 5 — Conexão Master no Database.php

#### 5.1 Suporte a Bancos Nomeados

O `Database::getInstance()` já aceita um `$tenantDb` opcional. Para o master funcionar:

```php
// Exemplo de uso no controller master:
$masterDb = \Database::getInstance('akti_master');
$planModel = new \Akti\Models\Master\Plan($masterDb);
```

**IMPORTANTE:** O `Database::getInstance('akti_master')` usa as credenciais do tenant atual para conectar no banco `akti_master`. Isso funciona se o usuário do tenant tem permissão para acessar `akti_master`.

**Alternativa mais segura:** Criar método `Database::getMasterInstance()` que usa as credenciais do `.env` (AKTI_MASTER_DB_*):

```php
public static function getMasterInstance(): PDO
{
    $masterConfig = \TenantManager::getMasterConfig(); // já existe!
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $masterConfig['host'], $masterConfig['port'],
        $masterConfig['db_name'], $masterConfig['charset']
    );

    if (isset(self::$instances[$dsn])) {
        return self::$instances[$dsn];
    }

    $pdo = new PDO($dsn, $masterConfig['username'], $masterConfig['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    self::$instances[$dsn] = $pdo;
    return $pdo;
}
```

#### 5.2 TenantManager::getMasterConfig() — Já Existe!

O `TenantManager` já possui `getMasterConfig()` que lê do `.env`:
```php
AKTI_MASTER_DB_HOST=
AKTI_MASTER_DB_PORT=
AKTI_MASTER_DB_NAME=akti_master
AKTI_MASTER_DB_USER=
AKTI_MASTER_DB_PASS=
```

---

### FASE 6 — Limpeza e Deprecação

#### 6.1 Remover/Deprecar `master/` Antigo

Após migração completa e testes:
1. Renomear `master/` para `master_old/` (backup temporário)
2. Testar todas as funcionalidades via rotas `master_*`
3. Após 1 semana de estabilidade, remover `master_old/`

#### 6.2 Remover Arquivos de Teste
- `master/_test_backup.php`
- `master/_test_git.php`
- `master/_write_backup_view.php`
- `master/reset_password.php`

#### 6.3 Remover Configuração Duplicada
- `master/app/config/config.php` — credenciais hardcoded → eliminado
- `master/app/config/database.php` — Database class duplicada → eliminado

---

## Diagrama de Fluxo — Login Unificado

```
┌──────────────────────────────────────────────┐
│              ?page=login (POST)               │
│         UserController::login()               │
│         AuthService::attemptLogin()           │
└────────────────────┬─────────────────────────┘
                     │
            ┌────────▼────────┐
            │ 1. Login Tenant  │
            │ (tabela users)   │
            └────────┬────────┘
                     │
              ┌──────▼──────┐
              │  Sucesso?    │
              └──────┬──────┘
               Sim   │   Não
          ┌──────────┘   └──────────┐
          ▼                         ▼
   ┌──────────────┐       ┌────────────────┐
   │ Sessão Tenant │       │ 2. Login Master │
   │ → ?page=home  │       │ (admin_users)   │
   └──────────────┘       └────────┬───────┘
                                   │
                            ┌──────▼──────┐
                            │  Sucesso?    │
                            └──────┬──────┘
                             Sim   │   Não
                        ┌──────────┘   └──────────┐
                        ▼                         ▼
                 ┌──────────────┐       ┌────────────────┐
                 │ Sessão Master │       │ 3. Login Portal │
                 │ → master_dash │       │ (portal_access) │
                 └──────────────┘       └────────┬───────┘
                                                 │
                                          ┌──────▼──────┐
                                          │  Sucesso?    │
                                          └──────┬──────┘
                                           Sim   │   Não
                                      ┌──────────┘   └──────┐
                                      ▼                      ▼
                               ┌──────────┐          ┌──────────────┐
                               │ Portal    │          │ "Credenciais  │
                               │ → portal  │          │  inválidas"   │
                               └──────────┘          └──────────────┘
```

---

## Checklist de Implementação

### Fase 1 — Estrutura
- [ ] Criar pastas `app/controllers/Master/` e `app/models/Master/`
- [ ] Migrar 8 models com namespace `Akti\Models\Master`
- [ ] Migrar 7 controllers com namespace `Akti\Controllers\Master`
- [ ] Verificar autoloader resolve subnamespaces corretamente

### Fase 2 — Autenticação
- [ ] Adicionar `Database::getMasterInstance()` em `database.php`
- [ ] Adicionar `attemptMasterLogin()` em `AuthService.php`
- [ ] Testar login master com redirect para `?page=master_dashboard`
- [ ] Verificar sessão master (`$_SESSION['is_master_admin']`)

### Fase 3 — Rotas e Middleware
- [ ] Adicionar 7 rotas `master_*` em `routes.php`
- [ ] Adicionar verificação `master_only` em `Application.php`
- [ ] Testar acesso negado para usuários non-master
- [ ] Injeção de conexão master nos controllers

### Fase 4 — Views
- [ ] Migrar 14 views para `app/views/master/`
- [ ] Migrar layout (header/footer) master
- [ ] Adicionar `csrf_field()` em todos os forms
- [ ] Adicionar CSRF header no AJAX
- [ ] Trocar `htmlspecialchars()` por `e()`
- [ ] Atualizar URLs com prefixo `master_`

### Fase 5 — Assets e Database
- [ ] Migrar CSS/JS para `assets/css/master.css` e `assets/js/master.js`
- [ ] Configurar `.env` com credenciais master separadas
- [ ] Testar `Database::getMasterInstance()` com `.env`

### Fase 6 — Limpeza
- [ ] Remover `master/app/config/config.php` (credenciais hardcoded)
- [ ] Remover arquivos de teste (`_test_*.php`)
- [ ] Deprecar pasta `master/` antiga
- [ ] Testes de regressão

---

## Notas Importantes

1. **O AuthController do master é ELIMINADO** — toda autenticação passa pelo `AuthService` do Akti
2. **O Database do master é ELIMINADO** — usa `Database::getMasterInstance()` do Akti
3. **O config.php do master é ELIMINADO** — credenciais vêm do `.env`
4. **CSRF é automático** — middleware global do Akti protege todos os POST
5. **Rate limiting é automático** — `LoginAttempt` protege o login
6. **Session segura é automática** — `session.php` já configura flags
7. **XSS helpers são automáticos** — `e()`, `eAttr()` disponíveis globalmente
