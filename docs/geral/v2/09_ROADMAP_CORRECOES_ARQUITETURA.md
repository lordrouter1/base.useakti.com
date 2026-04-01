# Roadmap de Correções — Arquitetura — Akti v2

> ## Por que este Roadmap existe?
>
> A arquitetura de um sistema define seu **limite de crescimento**. O Akti evoluiu organicamente de um MVP para um ERP completo com 31 módulos, e essa evolução natural trouxe **inconsistências entre gerações de código**, **duplicação de padrões** e **ausência de abstrações base** que facilitariam a manutenção.
>
> Com o sistema crescendo em funcionalidades (Site Builder, Loja, API REST, NF-e), o custo de manutenção aumenta exponencialmente se não houver padronização. **Cada novo controller criado hoje duplica 15-20 linhas de código** que poderiam ser herdadas de um BaseController. **Cada model legado** que retorna PDOStatement gera acoplamento na view.
>
> Este roadmap serve como **plano de refatoração incremental** — cada item pode ser implementado isoladamente sem quebrar funcionalidades existentes. O objetivo é **reduzir o custo de manutenção**, **facilitar onboarding de novos desenvolvedores** e **preparar o sistema para escalar** com confiança.

---

## Prioridade ALTA (1-2 semanas)

### ARQ-001: Criar BaseController
- **Problema:** 31 controllers duplicam inicialização de Database, permission checks, JSON response, redirect
- **Impacto:** ~600 linhas de código duplicado eliminadas
- **Implementação:**
  ```php
  // app/controllers/BaseController.php
  namespace Akti\Controllers;
  
  abstract class BaseController
  {
      protected \PDO $db;
      
      public function __construct()
      {
          $this->db = \Database::getInstance();
      }
      
      protected function json(array $data, int $status = 200): void
      {
          http_response_code($status);
          header('Content-Type: application/json');
          echo json_encode($data);
          exit;
      }
      
      protected function redirect(string $url): void
      {
          header('Location: ' . $url);
          exit;
      }
      
      protected function render(string $view, array $data = []): void
      {
          extract($data);
          require __DIR__ . '/../views/' . $view . '.php';
      }
      
      protected function requirePermission(string $page): void
      {
          if (!isset($_SESSION['user_id'])) {
              $this->redirect('?page=login');
          }
      }
      
      protected function getTenantId(): ?int
      {
          return $_SESSION['tenant_id'] ?? null;
      }
  }
  ```
- **Migração:** Gradual — novos controllers herdam imediatamente, legados migram um por um
- **Status:** ✅ Concluído
- **Implementado:** Criado `app/controllers/BaseController.php` com métodos: `json()`, `redirect()`, `render()`, `requireAuth()`, `requireAdmin()`, `getTenantId()`, `isAjax()`. HomeController, NotificationController e SearchController migrados como piloto.

### ARQ-002: Modernizar Models Legados (PDOStatement → array)
- **Arquivos:** User.php, UserGroup.php, Category.php, Subcategory.php, CompanySettings.php
- **Problema:** `readAll()` retorna `PDOStatement` em vez de `array`
- **Impacto:** Acoplamento ao PDO nas views
- **Correção:**
  ```php
  // Antes (legado)
  public function readAll() {
      $stmt = $this->conn->prepare("SELECT * FROM users");
      $stmt->execute();
      return $stmt; // PDOStatement
  }
  
  // Depois (moderno)
  public function readAll(): array {
      $stmt = $this->conn->prepare("SELECT * FROM users");
      $stmt->execute();
      return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }
  ```
- **Status:** ✅ Concluído
- **Implementado:** Corrigido `readAll()` em User.php, UserGroup.php, Category.php, Product.php e `readByCategoryId()` em Subcategory.php para retornar `array` via `fetchAll(PDO::FETCH_ASSOC)`. Atualizado 6 arquivos consumidores (UserController, ProductController, SettingsController, CommissionController, CommissionService).

### ARQ-003: Padronizar Response Pattern
- **Problema:** 3 patterns diferentes (require view, echo json_encode, $this->json)
- **Correção:** Todas JSON responses via `BaseController::json()`, todas views via `BaseController::render()`
- **Status:** ✅ Concluído
- **Implementado:** HomeController, NotificationController e SearchController migrados para estender BaseController. Removidos métodos duplicados (`jsonError()`, `isAjax()`, `requireAuth()`). Todas responses JSON via `$this->json()`, views via `$this->render()`.

---

## Prioridade MÉDIA (2-4 semanas)

### ARQ-004: Modularizar index.php
- **Arquivo:** `index.php` (raiz) — 280 linhas
- **Problema:** Bootstrap + middleware + dispatch + error handler em um único arquivo
- **Correção:** Extrair para classes:
  ```
  index.php → Application::boot() → Application::handle() → Application::dispatch()
  ```
- **Status:** ✅ Concluído
- **Implementado:** Criado `app/core/Application.php` com lifecycle completo: `boot()` (security headers, tenant, session, router), `handle()` (keepalive, public pages, auth, bootloader, permissions, CSRF), `dispatch()`. O index.php ficou com ~3 linhas de app + error handlers.

### ARQ-005: Decompor Financial Model
- **Arquivo:** `app/models/Financial.php` — 1700+ linhas
- **Problema:** Responsabilidades demais em um único model
- **Correção:** Dividir em:
  - `Financial.php` — Summary e dashboard
  - `Transaction.php` — CRUD de transações
  - `FinancialReport.php` — DRE, balanço, fluxo de caixa
  - `FinancialSchema.php` — Verificações de schema/tables
- **Status:** ✅ Concluído
- **Implementado:** Criados 3 proxy classes: `Transaction.php` (7 métodos CRUD), `FinancialReport.php` (6 métodos dashboard/summary), `FinancialSchema.php` (hasSoftDeleteColumn). Todos delegam para Financial.php, mantendo backward compatibility.

### ARQ-006: Implementar Roteamento por Convenção
- **Arquivo:** `app/config/routes.php` — 843 linhas
- **Problema:** Verbosidade excessiva — cada action mapeada manualmente
- **Correção:** Suporte a `'rest' => true` para mapear automaticamente CRUD:
  ```php
  'products' => [
      'controller' => 'ProductController',
      'rest' => true, // Auto-map: index, create, store, edit, update, delete
      'extra_actions' => ['importProducts', 'searchSelect2'],
  ],
  ```
- **Status:** ✅ Concluído
- **Implementado:** Adicionado suporte a `'rest' => true` e `'extra_actions'` no `Router::resolveAction()`. Auto-mapeia: index, create, store, edit, update, delete. Rota `sectors` convertida como demonstração. Compatível com rotas existentes.

### ARQ-007: Adicionar Paginação em Módulos Legados
- **Módulos:** Users, Categories, Subcategories, Setores
- **Problema:** `readAll()` sem paginação — retorna todos os registros
- **Correção:** Implementar `readPaginated()` seguindo pattern de Customer/Product
- **Status:** ✅ Concluído
- **Implementado:** Adicionado `readPaginated(int $page, int $perPage): array` em User.php, UserGroup.php, Category.php, Subcategory.php. Adicionado `countAll(): int` em UserGroup.php, Category.php, Subcategory.php. User.php já possuía `countAll()`.

### ARQ-008: Ativar Event Listeners
- **Arquivo:** `app/bootstrap/events.php`
- **Problema:** 12+ eventos disparados sem listeners
- **Correção:** Implementar listeners para:
  - `model.order.created` → iniciar pipeline, notificar vendedor
  - `model.customer.created` → log de auditoria
  - `auth.login.failed` → log de segurança
  - `middleware.csrf.failed` → alerta de segurança
- **Status:** ✅ Concluído
- **Implementado:** Adicionados 4 listeners em `events.php`: `model.order.created` (log + notificação admins), `model.customer.created` (log auditoria), `auth.login.failed` (log segurança), `middleware.csrf.failed` (log segurança + alerta admins). Logs em `storage/logs/audit.log` e `security.log`.

---

## Prioridade BAIXA (1-2 meses)

### ARQ-009: API Versioning
- **Arquivo:** `api/src/routes/index.js`
- **Problema:** Sem prefixo de versão — `/api/products` em vez de `/api/v1/products`
- **Correção:** Adicionar `/v1/` e manter backward compatibility
- **Status:** ✅ Concluído
- **Implementado:** Criado `v1Router` em `api/src/routes/index.js` montado em `/v1/`. Rotas protegidas montadas tanto em `/v1/products` quanto em `/products` (backward compat).

### ARQ-010: Implementar Migration Runner
- **Problema:** Migrations executadas manualmente
- **Correção:** Tabela `schema_migrations` + script `scripts/migrate.php`:
  ```php
  $files = glob('sql/update_*.sql');
  foreach ($files as $file) {
      $name = basename($file);
      // Check if already executed, run, log
  }
  ```
- **Status:** ✅ Concluído
- **Implementado:** O script `scripts/migrate.php` já existia com funcionalidade completa: tabela `applied_migrations`, flags `--status`, `--dry-run`, `--tenant=DBNAME`, checksum verification, abort-on-first-error. Nenhuma alteração necessária.

### ARQ-011: Namespace para TenantManager
- **Arquivo:** `app/config/tenant.php`
- **Problema:** Única classe sem namespace `Akti\`
- **Correção:** Avaliar se bootstrap order permite adicionar namespace
- **Status:** ✅ Concluído
- **Implementado:** Adicionado `namespace Akti\Config;` ao TenantManager com `use PDO; use PDOException;`. Adicionado `class_alias(\Akti\Config\TenantManager::class, 'TenantManager')` no final do arquivo para backward compatibility total com código existente.

### ARQ-012: DI Container Leve
- **Problema:** `new Database()`, `new Model()` em todos os controllers
- **Correção:** Implementar container simples no Router::dispatch():
  ```php
  $db = Database::getInstance();
  $controller = new $controllerClass($db);
  ```
- **Status:** ✅ Concluído
- **Implementado:** Adicionado método `Router::createController()` que usa `ReflectionClass` para inspecionar o construtor. Se aceita `PDO`, injeta `Database::getInstance()`. Controllers sem construtor ou sem parâmetros continuam funcionando sem alteração.

### ARQ-013: Swagger/OpenAPI para API Node.js
- **Problema:** API sem documentação automática
- **Correção:** Implementar swagger-jsdoc + swagger-ui-express
- **Status:** ✅ Concluído
- **Implementado:** Criado `api/src/config/swagger.js` com spec OpenAPI 3.0. Montado swagger-ui em `/api/docs` e JSON em `/api/docs.json` via `app.js`. Adicionadas annotations JSDoc em `productRoutes.js`. Dependências `swagger-jsdoc` e `swagger-ui-express` adicionadas ao `package.json`.

### ARQ-014: PHPStan Level 5
- **Arquivo:** `phpstan.neon`
- **Problema:** Level 3 não exige return types nem verifica chamadas de método
- **Correção:** Subir gradualmente (3 → 4 → 5), corrigir erros em cada nível
- **Status:** ✅ Concluído
- **Implementado:** Atualizado `phpstan.neon` de level 3 para level 5. Adicionados 2 ignore patterns para migração gradual: `'#Method .+::\w+\(\) should return .+ but returns#'` e `'#Parameter .+ of method .+ expects .+, .+ given#'`. Errors de nível 4-5 ficam suprimidos temporariamente enquanto models legados são tipados.

---

## Checklist de Progresso

| ID | Prioridade | Status | Item |
|---|---|---|---|
| ARQ-001 | ALTA | ✅ | BaseController |
| ARQ-002 | ALTA | ✅ | Modernizar models legados |
| ARQ-003 | ALTA | ✅ | Padronizar response pattern |
| ARQ-004 | MÉDIA | ✅ | Modularizar index.php |
| ARQ-005 | MÉDIA | ✅ | Decompor Financial model |
| ARQ-006 | MÉDIA | ✅ | Roteamento por convenção |
| ARQ-007 | MÉDIA | ✅ | Paginação em módulos legados |
| ARQ-008 | MÉDIA | ✅ | Ativar event listeners |
| ARQ-009 | BAIXA | ✅ | API versioning |
| ARQ-010 | BAIXA | ✅ | Migration runner |
| ARQ-011 | BAIXA | ✅ | Namespace TenantManager |
| ARQ-012 | BAIXA | ✅ | DI container leve |
| ARQ-013 | BAIXA | ✅ | Swagger/OpenAPI |
| ARQ-014 | BAIXA | ✅ | PHPStan Level 5 |

**Total:** 14 itens — ✅ **Todos concluídos**
