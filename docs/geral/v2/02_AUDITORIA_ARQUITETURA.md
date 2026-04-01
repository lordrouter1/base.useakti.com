# Auditoria de Arquitetura e Padrões — Akti v2

> **Data da Auditoria:** 01/04/2026  
> **Escopo:** Análise da arquitetura MVC, padrões de código, organização de camadas, autoloader, bootstrap, roteamento, eventos e multi-tenancy  
> **Referência:** PSR-4, PSR-7, SOLID, Clean Architecture

---

## 1. Resumo Executivo

A arquitetura do Akti é baseada em **MVC com camada de serviços**, utilizando PSR-4 autoloading, roteamento declarativo via mapa de rotas, middleware pipeline para segurança, e sistema de eventos para operações desacopladas. A estrutura é sólida e escalável, com pontos de melhoria em consistência de padrões e necessidade de abstrações base.

| Aspecto | Nota | Observação |
|---|---|---|
| Separação MVC | ⭐⭐⭐⭐ | Boa, com pequenas violações |
| Autoloader | ⭐⭐⭐⭐⭐ | PSR-4 completo + Composer |
| Roteamento | ⭐⭐⭐⭐ | Declarativo, seguro, mas verboso |
| Eventos | ⭐⭐⭐⭐ | Funcional, subexplorado |
| Multi-tenant | ⭐⭐⭐⭐⭐ | Excelente isolamento por subdomain |
| DI/IoC | ⭐⭐ | Inexistente formalmente |

---

## 2. Estrutura de Diretórios

```
akti/
├── index.php                  # Entry point + bootstrap + dispatch
├── composer.json               # PSR-4 autoload rules
├── phpunit.xml                 # Test configuration
├── phpstan.neon                # Static analysis config
│
├── app/
│   ├── bootstrap/
│   │   ├── autoload.php        # PSR-4 spl_autoload + env + configs
│   │   └── events.php          # Event listener registration
│   │
│   ├── config/
│   │   ├── database.php        # PDO singleton by DSN (Database class)
│   │   ├── routes.php          # Route map declarativo (31 controllers)
│   │   ├── menu.php            # Menu + permission flags
│   │   ├── session.php         # SessionGuard + config
│   │   └── tenant.php          # TenantManager multi-tenant
│   │
│   ├── core/
│   │   ├── EventDispatcher.php # Bus de eventos (static)
│   │   ├── Event.php           # Value object com user/tenant context
│   │   ├── Log.php             # Logging estruturado (PSR-3-like)
│   │   ├── ModuleBootloader.php # Feature flags por tenant
│   │   ├── Router.php          # Resolve page/action → controller/method
│   │   └── Security.php        # CSRF tokens + validation
│   │
│   ├── middleware/
│   │   ├── CsrfMiddleware.php
│   │   ├── SecurityHeadersMiddleware.php
│   │   ├── RateLimitMiddleware.php
│   │   ├── PortalAuthMiddleware.php
│   │   └── SentryMiddleware.php
│   │
│   ├── controllers/            # 31 controllers
│   ├── models/                 # 45 models
│   ├── services/               # 64 services
│   ├── gateways/               # Payment gateways (Strategy pattern)
│   ├── utils/                  # Validator, Sanitizer, Input, Escape, Helpers
│   ├── lang/                   # Internacionalização
│   └── views/                  # 15+ módulos de views
│
├── api/                        # Node.js REST API
├── loja/                       # Storefront (Twig templates)
├── assets/                     # CSS, JS, imagens
├── sql/                        # Migrations
├── storage/                    # Logs, backups, uploads
├── tests/                      # PHPUnit test suites
└── docker/                     # Docker config
```

### 2.1 Avaliação da Estrutura

| Aspecto | Status | Observação |
|---|---|---|
| Separação de camadas | ✅ | Controllers, Models, Services, Views separados |
| Nomenclatura consistente | ✅ | PascalCase para classes, camelCase para métodos |
| PSR-4 compliance | ✅ | 10 namespace prefixes mapeados |
| Ausência de `require_once` | ✅ | Autoloader carrega tudo |
| Config separado de código | ✅ | `app/config/` dedicado |

---

## 3. Bootstrap e Ciclo de Vida da Requisição

### 3.1 Fluxo do `index.php`

```
1. require autoload.php (PSR-4 + Composer + env + configs)
2. set_exception_handler() — global error handler
3. register_shutdown_function() — fatal error handler
4. SecurityHeadersMiddleware::apply() — HTTP headers
5. session_start() — com cookie seguras (AKTI_SID)
6. TenantManager::enforceTenantSession() — multi-tenant
7. SessionGuard::checkInactivity() — timeout
8. Router init — resolve page/action de $_GET
9. Keepalive check — /?page=session&action=keepalive
10. Public pages dispatch — catálogo, portal login
11. Auth check — $_SESSION['user_id']
12. ModuleBootloader::canAccessPage() — feature flags
13. Menu permission check — user.checkPermission()
14. CsrfMiddleware::handle() — token validation (POST only)
15. Security::generateCsrfToken() — rotate token
16. $router->dispatch() — controller instantiation + method call
```

### 3.2 Avaliação do Bootstrap

| Aspecto | Status | Observação |
|---|---|---|
| Ordem de carregamento | ✅ | .env → session → tenant → database → helpers |
| Error handling precoce | ✅ | Global handler registrado antes de qualquer código |
| CSRF antes do dispatch | ✅ | Middleware valida antes do controller |
| Separação de concerns | ⚠️ | index.php tem ~280 linhas — poderia ser mais modular |

**Recomendação:** Extrair a lógica de bootstrap para uma classe `Application` ou `Kernel`:
```php
// Ideal
$app = new Application(__DIR__);
$app->boot();       // autoload, env, session, tenant
$app->handle();     // auth, csrf, permissions
$app->dispatch();   // router → controller
```

---

## 4. Sistema de Roteamento

### 4.1 Implementação Atual

**Arquivo:** `app/config/routes.php` — 843 linhas, 31 rotas

O roteamento é baseado em um **mapa declarativo** onde cada `page` aponta para um controller e lista de `actions`:

```php
'products' => [
    'controller'     => 'ProductController',
    'default_action' => 'index',
    'actions'        => [
        'create'          => 'create',
        'store'           => 'store',
        'edit'            => 'edit',
        'update'          => 'update',
        'delete'          => 'delete',
        'importProducts'  => 'importProducts',
        // ... 19 actions
    ],
],
```

**Resolução no Router:** `app/core/Router.php:197-237`

1. Busca mapeamento explícito em `actions[]`
2. Se não mapeado e action é `index` → usa default
3. Se `allow_unmapped: true` → permite qualquer método (perigoso)
4. Se não permitido → fallback para default

### 4.2 Avaliação

| Aspecto | Status | Observação |
|---|---|---|
| Whitelist de actions | ✅ | Apenas actions explicitamente mapeadas são permitidas |
| `allow_unmapped` flag | ⚠️ | Permite acesso a QUALQUER método público. Nenhuma rota atual usa, mas o recurso existe |
| Granularidade | ✅ | Cada action é mapeada individualmente |
| Centralização | ✅ | Todas as rotas em um arquivo |
| Verbosidade | ⚠️ | 843 linhas para 31 rotas — poderia usar convenções |

### 4.3 Flags Especiais

| Flag | Propósito | Rotas que usam |
|---|---|---|
| `public: true` | Bypass de autenticação | catalog |
| `before_auth: true` | Processamento pré-login | portal, login |
| `allow_unmapped: true` | Qualquer método público | Nenhuma (seguro) |

### 4.4 Recomendação

Considerar suporte a convenções REST automáticas:
```php
'products' => [
    'controller' => 'ProductController',
    'rest' => true, // Mapeia automaticamente index, create, store, edit, update, delete
    'extra_actions' => ['importProducts', 'searchSelect2'],
],
```

---

## 5. Controladores — Padrões e Consistência

### 5.1 Padrão de Construtor (31 controllers analisados)

Todos os controllers seguem este padrão:

```php
public function __construct()
{
    $database = new Database();
    $db = $database->getConnection();
    $this->model = new Model($db);
    $this->logger = new Logger($db);
}
```

### 5.2 Problemas Identificados

#### 🟠 Ausência de BaseController

Não existe `AbstractController` ou `BaseController`. Métodos utilitários são duplicados:

| Método | Controllers que duplicam |
|---|---|
| `checkAdmin()` | UserController, SettingsController |
| `json($data, $status)` | SiteBuilderController, NfeDocumentController, ApiController |
| `requireTenant()` | SiteBuilderController |
| Permission check inline | Quase todos |

**Impacto:** Código duplicado, inconsistência de padrões

**Correção proposta — `app/controllers/BaseController.php`:**
```php
abstract class BaseController
{
    protected PDO $db;
    
    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }
    
    protected function json(array $data, int $status = 200): void { ... }
    protected function redirect(string $url): void { ... }
    protected function render(string $view, array $data = []): void { ... }
    protected function requireAuth(): void { ... }
    protected function requirePermission(string $page): void { ... }
    protected function getTenantId(): int { ... }
}
```

#### 🟡 Instanciação direta de Database em cada Controller

- **Problema:** `new Database()` chamado em TODOS os 31 construtores
- **Impacto:** Acoplamento ao Database class; dificulta testes
- **Solução:** Injeção de dependência via Router `dispatch()`:
```php
// Router.php - dispatch()
$db = Database::getInstance();
$controller = new $controllerClass($db);
```

#### 🟡 Padrão de Response Inconsistente

| Padrão | Uso | Controllers |
|---|---|---|
| `require 'view.php'` | Renderização HTML | Maioria |
| `echo json_encode(...)` | API/AJAX | ~15 controllers |
| `$this->json(...)` | Método privado | SiteBuilderController, ApiController |
| `header('Location: ...')` | Redirect POST | Todos (actions store/update/delete) |

**Recomendação:** Padronizar com método `json()` do BaseController.

### 5.3 Inventário Completo de Controllers

| # | Controller | Arquivo | Models | Services | Actions |
|---|---|---|---|---|---|
| 1 | UserController | `app/controllers/UserController.php` | 6 | 1 | 12 |
| 2 | ProductController | `app/controllers/ProductController.php` | 6 | 2 | 19 |
| 3 | OrderController | `app/controllers/OrderController.php` | 8 | 1 | 15 |
| 4 | PipelineController | `app/controllers/PipelineController.php` | 10 | 4 | 20+ |
| 5 | CustomerController | `app/controllers/CustomerController.php` | 3 | 5 | 15+ |
| 6 | PortalController | `app/controllers/PortalController.php` | 6 | 7 | 27+ |
| 7 | FinancialController | `app/controllers/FinancialController.php` | 4 | 3 | 20+ |
| 8 | NfeDocumentController | `app/controllers/NfeDocumentController.php` | 3 | 10+ | 25+ |
| 9 | NfeCredentialController | `app/controllers/NfeCredentialController.php` | 2 | 2 | 8 |
| 10 | SiteBuilderController | `app/controllers/SiteBuilderController.php` | 2 | 0 | 15 |
| 11 | CategoryController | `app/controllers/CategoryController.php` | 3 | 1 | 11 |
| 12 | SectorController | `app/controllers/SectorController.php` | 2 | 0 | 6 |
| 13 | StockController | `app/controllers/StockController.php` | 3 | 1 | 10 |
| 14 | CommissionController | `app/controllers/CommissionController.php` | 2 | 3 | 10+ |
| 15 | InstallmentController | `app/controllers/InstallmentController.php` | 3 | 1 | 15+ |
| 16 | TransactionController | `app/controllers/TransactionController.php` | 2 | 1 | 8 |
| 17 | RecurringTransactionController | `app/controllers/RecurringTransactionController.php` | 1 | 1 | 6 |
| 18 | PaymentGatewayController | `app/controllers/PaymentGatewayController.php` | 2 | 1 | 8 |
| 19 | ReportController | `app/controllers/ReportController.php` | 5 | 2 | 10 |
| 20 | SettingsController | `app/controllers/SettingsController.php` | 3 | 1 | 8 |
| 21 | DashboardController | `app/controllers/DashboardController.php` | 3 | 1 | 5 |
| 22 | DashboardWidgetController | `app/controllers/DashboardWidgetController.php` | 1 | 0 | 5 |
| 23 | NotificationController | `app/controllers/NotificationController.php` | 1 | 0 | 5 |
| 24 | SearchController | `app/controllers/SearchController.php` | 3 | 0 | 3 |
| 25 | WalkthroughController | `app/controllers/WalkthroughController.php` | 1 | 0 | 4 |
| 26 | HomeController | `app/controllers/HomeController.php` | 0 | 0 | 1 |
| 27 | HealthController | `app/controllers/HealthController.php` | 0 | 0 | 2 |
| 28 | CatalogController | `app/controllers/CatalogController.php` | 3 | 2 | 7 |
| 29 | PortalAdminController | `app/controllers/PortalAdminController.php` | 2 | 1 | 5 |
| 30 | FinancialImportController | `app/controllers/FinancialImportController.php` | 2 | 1 | 5 |
| 31 | ApiController | `app/controllers/ApiController.php` | 1 | 0 | 3 |

**Total:** 31 controllers, ~100 models/services instanciados, ~300+ actions

---

## 6. Camada de Models

### 6.1 Padrão Atual (45 models)

```php
namespace Akti\Models;

class Product
{
    private $conn;   // PDO
    public $id, $name, $price; // Propriedades públicas
    
    public function __construct($db) { $this->conn = $db; }
    
    public function create() { ... }
    public function readAll() { ... }
    public function readOne($id) { ... }
    public function update($id, $data) { ... }
    public function delete($id) { ... }
}
```

### 6.2 Problemas Identificados

| Problema | Impacto | Severidade |
|---|---|---|
| Propriedades públicas em models | Exposição de estado interno | BAIXO |
| Sem tipagem de retorno | Dificulta manutenção e testes | MÉDIO |
| Models disparam eventos inconsistentemente | Eventos `model.*` não são capturados | MÉDIO |
| Sem Repository pattern | Models misturam queries e lógica de negócio | BAIXO |
| `readAll()` sem paginação padrão | Pode retornar milhares de registros | MÉDIO |

### 6.3 Eventos no Model Layer

**Registrados e ouvidos (em `app/bootstrap/events.php`):**
- `model.nfe_document.authorized` → Gera DANFE PDF automaticamente

**Disparados mas SEM listeners configurados:**
- `model.user.created`, `model.user.updated`, `model.user.deleted`
- `model.customer.created`, `model.customer.updated`, `model.customer.deleted`
- `model.order.created`, `model.order.updated`

**Impacto:** Eventos disparados sem efeito consomem recursos desnecessariamente.

---

## 7. Camada de Services

### 7.1 Organização (64 services)

| Domínio | Quantidade | Exemplos |
|---|---|---|
| NF-e (fiscal) | 19 | NfeXmlBuilder, NfePdfGenerator, NfeService |
| Portal (cliente) | 7 | PortalAuthService, PortalCartService |
| Financeiro | 8 | CommissionEngine, InstallmentService |
| Pipeline | 4 | PipelineService, PipelineAlertService |
| Produtos | 3 | ProductImportService, ProductGradeService |
| Clientes | 5 | CustomerFormService, CustomerExportService |
| Relatórios | 2 | ReportExcelService, ReportPdfService |
| Catálogo | 2 | CatalogCartService, CatalogQuoteService |
| Outros | 14 | AuthService, SettingsService, etc. |

### 7.2 Avaliação

- ✅ **Single Responsibility:** Cada service tem propósito claro
- ✅ **Dependency Injection:** Services recebem PDO e models via construtor
- ✅ **Sem acesso a $_POST/$_GET:** Controllers fazem a mediação
- ⚠️ **Sem interface/contract:** Services não implementam interfaces formais
- ⚠️ **Sem service provider:** Instantiação manual em cada controller

---

## 8. Sistema de Eventos

### 8.1 Implementação

- **EventDispatcher:** `app/core/EventDispatcher.php` — bus estático FIFO
- **Event:** `app/core/Event.php` — value object com user/tenant context auto-capturado
- **Registro:** `app/bootstrap/events.php` — listeners cadastrados no boot

### 8.2 Convenção de Nomes

```
{camada}.{entidade}.{ação}
model.order.created
middleware.csrf.failed
core.security.access_denied
auth.login.success
```

### 8.3 Gap: Listeners Subexplorados

O sistema de eventos está funcional mas subutilizado. Apenas eventos NF-e têm listeners reais. Potencial:

| Evento | Listener Recomendado |
|---|---|
| `model.order.created` | Notificação ao vendedor, iniciar pipeline |
| `model.customer.created` | Email de boas-vindas, log de auditoria |
| `model.user.login` | Auditoria de acesso, detecção de anomalias |
| `model.order.stage_changed` | Notificação ao cliente, atualizar SLA |

---

## 9. Multi-Tenancy

### 9.1 Implementação: ✅ EXCELENTE

- **Resolução:** Subdomain → database mapping via `akti_master` DB
- **Classe:** `TenantManager` (global, sem namespace) — `app/config/tenant.php`
- **Fallback:** Localhost/vazio → tenant padrão (configurado via .env)

### 9.2 Fluxo de Resolução

```
HTTP Request → Host header
  → isLocalHost() ? default config
  → extractSubdomain(host)
    → findTenantBySubdomain() in akti_master
      → Found: store config + session with tenant DB credentials
      → Not found: use default config
```

### 9.3 Limites por Tenant

```php
[
    'max_users' => 50,
    'max_products' => 5000,
    'max_warehouses' => 10,
    'max_price_tables' => 5,
    'max_sectors' => 20,
    'enabled_modules' => '{"financial":true,"nfe":false,...}'
]
```

### 9.4 Observação

**`TenantManager` não tem namespace** — é a única classe no projeto sem namespace `Akti\`. Isto é intencional pois é carregada antes do autoloader PSR-4 estar completamente configurado.

---

## 10. Padrões de Design Identificados

| Padrão | Onde é Usado | Implementação |
|---|---|---|
| **MVC** | Toda a aplicação | Controllers + Models + Views |
| **Strategy** | Payment Gateways | `GatewayManager::resolve()` + `AbstractGateway` |
| **Observer** | Event System | `EventDispatcher::listen()` / `dispatch()` |
| **Singleton** | Database Connection | `Database::getInstance()` com cache por DSN |
| **Service Layer** | Business Logic | 64 services entre controllers e models |
| **Factory** | Gateway Resolution | `GatewayManager` cria gateways por slug |
| **Middleware Pipeline** | Security | CSRF, Headers, RateLimit, Auth |
| **Feature Flags** | Modules | `ModuleBootloader::isModuleEnabled()` |

---

## 11. Métricas de Código

| Métrica | Valor | Avaliação |
|---|---|---|
| **Controllers** | 31 | Adequado |
| **Models** | 45 | Adequado |
| **Services** | 64 | Bom — lógica extraída dos controllers |
| **Views (módulos)** | 15+ | Organizado por domínio |
| **Middlewares** | 5 | Suficiente |
| **Rotas** | 31 pages × ~10 actions = ~310 endpoints | Completo |
| **Namespaces PSR-4** | 10 prefixes | Completo |
| **Testes** | 20 arquivos, 4 suites | ⚠️ Poderia ser maior |
| **PHPStan Level** | 3 | ⚠️ Poderia subir para 5+ |

---

## 12. Conclusões e Prioridades

### Forças
1. Separação MVC clara e consistente
2. PSR-4 autoloading completo
3. Roteamento seguro por whitelist
4. Multi-tenancy bem implementado
5. Camada de services rica e especializada
6. Event system funcional

### Prioridades de Melhoria
1. **BaseController** — eliminar duplicação de código entre controllers
2. **DI Container** — substituir `new Database()` manual por injeção
3. **Resposta padronizada** — todos controllers usando `json()`, `render()`, `redirect()`
4. **Event listeners** — ativar listeners para eventos já disparados
5. **PHPStan Level 5+** — aumentar rigor da análise estática
6. **Modularizar index.php** — extrair bootstrap para classe dedicada
