# 01 — Status da Integração Master → Akti

> **Data:** 06/04/2026

---

## Roadmap Original (v1/08_ROADMAP_INTEGRACAO.md) vs Executado

| Fase | Descrição | Status | Observações |
|------|-----------|--------|-------------|
| **Fase 1** | Estrutura e Namespace (Models) | ✅ Concluída | 8 models PSR-4 criados |
| **Fase 2** | Infraestrutura de Banco | ✅ Concluída | `getMasterInstance()`, `getMasterCredentials()`, `connectTo()` |
| **Fase 3** | Controllers | ✅ Concluída | 8 controllers incluindo `MasterBaseController` abstrato |
| **Fase 4** | Autenticação Unificada | ✅ Concluída | `attemptMasterLogin()` no AuthService (com bug — ver 06_BUGS) |
| **Fase 5** | Rotas e Middleware | ✅ Concluída | 7 rotas `master_*` + bypass em Application.php (com bug — ver 06_BUGS) |
| **Fase 6** | Views e Assets | ✅ Concluída | 16 views migradas, CSS/JS copiados |

---

## Inventário de Arquivos Criados/Modificados

### Arquivos CRIADOS (34 novos)

#### Models (8)
| Arquivo | Namespace | Linhas |
|---------|-----------|--------|
| `app/models/Master/AdminUser.php` | `Akti\Models\Master` | ~45 |
| `app/models/Master/AdminLog.php` | `Akti\Models\Master` | ~35 |
| `app/models/Master/Plan.php` | `Akti\Models\Master` | ~90 |
| `app/models/Master/TenantClient.php` | `Akti\Models\Master` | ~360 |
| `app/models/Master/Migration.php` | `Akti\Models\Master` | ~400 |
| `app/models/Master/GitVersion.php` | `Akti\Models\Master` | ~600 |
| `app/models/Master/Backup.php` | `Akti\Models\Master` | ~250 |
| `app/models/Master/NginxLog.php` | `Akti\Models\Master` | ~250 |

#### Controllers (8)
| Arquivo | Namespace | Linhas |
|---------|-----------|--------|
| `app/controllers/Master/MasterBaseController.php` | `Akti\Controllers\Master` | ~50 |
| `app/controllers/Master/DashboardController.php` | `Akti\Controllers\Master` | ~30 |
| `app/controllers/Master/PlanController.php` | `Akti\Controllers\Master` | ~120 |
| `app/controllers/Master/ClientController.php` | `Akti\Controllers\Master` | ~275 |
| `app/controllers/Master/MigrationController.php` | `Akti\Controllers\Master` | ~180 |
| `app/controllers/Master/GitController.php` | `Akti\Controllers\Master` | ~220 |
| `app/controllers/Master/BackupController.php` | `Akti\Controllers\Master` | ~115 |
| `app/controllers/Master/LogController.php` | `Akti\Controllers\Master` | ~110 |

#### Views (16)
| Arquivo | Origem |
|---------|--------|
| `app/views/master/layout/header.php` | `master/app/views/layout/header.php` |
| `app/views/master/layout/footer.php` | `master/app/views/layout/footer.php` |
| `app/views/master/dashboard/index.php` | `master/app/views/dashboard/index.php` |
| `app/views/master/plans/index.php` | `master/app/views/plans/index.php` |
| `app/views/master/plans/create.php` | `master/app/views/plans/create.php` |
| `app/views/master/plans/edit.php` | `master/app/views/plans/edit.php` |
| `app/views/master/clients/index.php` | `master/app/views/clients/index.php` |
| `app/views/master/clients/create.php` | `master/app/views/clients/create.php` |
| `app/views/master/clients/edit.php` | `master/app/views/clients/edit.php` |
| `app/views/master/migrations/index.php` | `master/app/views/migrations/index.php` |
| `app/views/master/migrations/results.php` | `master/app/views/migrations/results.php` |
| `app/views/master/migrations/users.php` | `master/app/views/migrations/users.php` |
| `app/views/master/git/index.php` | `master/app/views/git/index.php` |
| `app/views/master/backup/index.php` | `master/app/views/backup/index.php` |
| `app/views/master/logs/index.php` | `master/app/views/logs/index.php` |
| `app/views/master/auth/login.php` | `master/app/views/auth/login.php` (não utilizado) |

#### Assets (2)
| Arquivo | Origem |
|---------|--------|
| `assets/css/master.css` | `master/assets/css/style.css` |
| `assets/js/master.js` | `master/assets/js/app.js` |

### Arquivos MODIFICADOS (4)

| Arquivo | Alteração |
|---------|-----------|
| `app/config/database.php` | +3 métodos: `getMasterInstance()`, `getMasterCredentials()`, `connectTo()` |
| `app/config/tenant.php` | `getMasterConfig()`: `private` → `public` |
| `app/services/AuthService.php` | +`attemptMasterLogin()` entre tenant e portal |
| `app/core/Application.php` | Bypass `master_only` + redirect master no login |
| `app/core/Router.php` | +`getRouteConfig()` |
| `app/config/routes.php` | +7 rotas `master_*` |

---

## Decisões Arquiteturais Tomadas

### 1. MasterBaseController como classe abstrata
- Sobrescreve construtor para usar `Database::getMasterInstance()` (ignora injeção do Router)
- Adiciona `requireMasterAuth()`, `logAction()`, `renderMaster()`
- Todas as rotas master herdam autenticação e conexão master automaticamente

### 2. Sessão unificada
- Login master via mesmo formulário do tenant (`?page=login`)
- Fallback: tenant → **master** → portal
- Flags adicionadas: `$_SESSION['is_master_admin']`, `$_SESSION['master_admin_id']`

### 3. Rotas com flag `master_only`
- Bypass de `ModuleBootloader::canAccessPage()` e `checkPermissions()` no Application.php
- Controllers fazem sua própria verificação via `requireMasterAuth()`

### 4. Views com layout próprio
- `renderMaster()` usa `app/views/master/layout/header.php` e `footer.php`
- CSS/JS separados: `master.css`, `master.js`
- CSRF global via `$.ajaxSetup()` no footer

---

## O que NÃO foi migrado (intencional)

| Item | Motivo |
|------|--------|
| `master/app/controllers/AuthController.php` | Substituído por `AuthService::attemptMasterLogin()` |
| `master/app/views/auth/login.php` | Login usa formulário do Akti (`?page=login`) |
| `master/app/config/database.php` | Substituído por `Database::getMasterInstance()` |
| `master/index.php` | Entry point unificado via `index.php` + Router |
| `master/app/models/Database.php` | Substituído pelo global `Database` + `TenantManager` |
