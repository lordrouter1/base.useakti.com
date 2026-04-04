# 05 — Roadmap de Implementação PSR-11 — Akti

> **Data da Auditoria:** 04/04/2026
> **Objetivo:** Plano detalhado para adoção completa de PSR-11
> **Estimativa Total:** 4 fases progressivas

---

## Por que Implementar PSR-11?

1. **Testabilidade:** Permite mockar PDO e dependências em testes unitários de controllers
2. **Manutenibilidade:** Elimina ~250-300 instanciações manuais de dependências
3. **Padronização:** Conformidade com PHP-FIG (interop com libs de terceiros)
4. **Escalabilidade:** Novos controllers/services ficam triviais de adicionar
5. **Performance:** Lazy-loading evita instanciar dependências não usadas

---

## Fase 1 — Foundation (Infraestrutura do Container)

### PSR11-001: Instalar `psr/container`

- **Prioridade:** CRÍTICO
- **Arquivo:** `composer.json`
- **Ação:**
```bash
composer require psr/container
```
- **Resultado:** Disponibiliza `Psr\Container\ContainerInterface`, `NotFoundExceptionInterface`, `ContainerExceptionInterface`
- **Status:** ⬜ Pendente

---

### PSR11-002: Criar Exceções do Container

- **Prioridade:** ALTO
- **Arquivos novos:**
  - `app/core/ContainerException.php`
  - `app/core/NotFoundException.php`

```php
<?php
namespace Akti\Core;

use Psr\Container\ContainerExceptionInterface;

class ContainerException extends \RuntimeException implements ContainerExceptionInterface {}
```

```php
<?php
namespace Akti\Core;

use Psr\Container\NotFoundExceptionInterface;

class NotFoundException extends \RuntimeException implements NotFoundExceptionInterface {}
```

- **Status:** ⬜ Pendente

---

### PSR11-003: Criar Container PSR-11

- **Prioridade:** CRÍTICO
- **Arquivo novo:** `app/core/Container.php`
- **Requisitos:**
  - Implementar `Psr\Container\ContainerInterface`
  - Suporte a bindings (string ID → callable factory)
  - Suporte a singleton/shared (mesma instância por request)
  - Auto-wiring via Reflection (resolver type-hints recursivamente)
  - Cache de ReflectionClass para performance

```php
<?php
namespace Akti\Core;

use Psr\Container\ContainerInterface;

class Container implements ContainerInterface
{
    /** @var array<string, callable> Factories registradas */
    private array $bindings = [];

    /** @var array<string, mixed> Cache de instâncias (shared/singleton) */
    private array $instances = [];

    /** @var array<string, bool> IDs marcados como shared */
    private array $shared = [];

    /**
     * Registra um binding no container.
     */
    public function bind(string $id, callable $factory, bool $shared = false): void
    {
        $this->bindings[$id] = $factory;
        if ($shared) {
            $this->shared[$id] = true;
        }
    }

    /**
     * Registra um binding como singleton.
     */
    public function singleton(string $id, callable $factory): void
    {
        $this->bind($id, $factory, true);
    }

    /**
     * Registra uma instância já pronta.
     */
    public function instance(string $id, mixed $value): void
    {
        $this->instances[$id] = $value;
    }

    /**
     * @inheritDoc
     */
    public function get(string $id): mixed
    {
        // 1. Instância cacheada?
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        // 2. Binding registrado?
        if (isset($this->bindings[$id])) {
            $resolved = ($this->bindings[$id])($this);
            if (!empty($this->shared[$id])) {
                $this->instances[$id] = $resolved;
            }
            return $resolved;
        }

        // 3. Auto-wiring via Reflection
        if (class_exists($id)) {
            $resolved = $this->autoWire($id);
            // Classes auto-wired são shared por padrão
            $this->instances[$id] = $resolved;
            return $resolved;
        }

        throw new NotFoundException("Entry not found: {$id}");
    }

    /**
     * @inheritDoc
     */
    public function has(string $id): bool
    {
        return isset($this->instances[$id])
            || isset($this->bindings[$id])
            || class_exists($id);
    }

    /**
     * Auto-wiring recursivo via Reflection.
     */
    private function autoWire(string $class): object
    {
        try {
            $ref = new \ReflectionClass($class);
        } catch (\ReflectionException $e) {
            throw new ContainerException("Cannot reflect: {$class}", 0, $e);
        }

        if (!$ref->isInstantiable()) {
            throw new ContainerException("Not instantiable: {$class}");
        }

        $ctor = $ref->getConstructor();
        if ($ctor === null || $ctor->getNumberOfParameters() === 0) {
            return new $class();
        }

        $args = [];
        foreach ($ctor->getParameters() as $param) {
            $type = $param->getType();

            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $args[] = $this->get($type->getName());
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } elseif ($type instanceof \ReflectionNamedType && $type->allowsNull()) {
                $args[] = null;
            } else {
                throw new ContainerException(
                    "Cannot resolve param \${$param->getName()} in {$class}"
                );
            }
        }

        return $ref->newInstanceArgs($args);
    }
}
```

- **Funcionalidades:**
  - ✅ `ContainerInterface` compliant
  - ✅ Binding manual via `bind()` / `singleton()`
  - ✅ Instâncias pré-registradas via `instance()`
  - ✅ Auto-wiring recursivo
  - ✅ Shared/singleton support
  - ✅ Exceções PSR-11

- **Status:** ⬜ Pendente

---

### PSR11-004: Registrar PDO no Container

- **Prioridade:** CRÍTICO
- **Onde:** Bootstrap (index.php ou novo `app/bootstrap/container.php`)

```php
$container = new \Akti\Core\Container();

// PDO como singleton (via Database existente)
$container->singleton(\PDO::class, function () {
    return \Database::getInstance();
});
```

- **Status:** ⬜ Pendente

---

### PSR11-005: Integrar Container no Application

- **Prioridade:** ALTO
- **Arquivo:** `app/core/Application.php`
- **Ação:** Receber Container no construtor e passá-lo ao Router

```php
public function __construct(string $basePath, ContainerInterface $container)
{
    $this->basePath = $basePath;
    $this->container = $container;
}
```

- **Status:** ⬜ Pendente

---

### PSR11-006: Modificar Router para Usar Container

- **Prioridade:** ALTO
- **Arquivo:** `app/core/Router.php`
- **Ação:** Substituir `createController()` por `$container->get($class)`

```php
// ANTES
private function createController(string $class): object
{
    // Reflection manual ...
}

// DEPOIS
private function createController(string $class): object
{
    return $this->container->get($class);
}
```

- **Status:** ⬜ Pendente

---

## Fase 2 — Models e BaseController

### PSR11-007: Adicionar PDO Type-Hint em 42 Models

- **Prioridade:** MÉDIO
- **Escopo:** 42 arquivos em `app/models/`
- **Ação:** `public function __construct($db)` → `public function __construct(\PDO $db)`
- **Impacto:** Zero — todos já recebem PDO
- **Benefício:** Auto-wiring funciona sem config manual

**Lista de Models a alterar:**
CatalogLink, Category, CategoryGrade, CompanySettings, Customer, CustomerContact, DashboardWidget, Financial, FinancialReport, FinancialSchema, IbptaxModel, ImportBatch, ImportMappingProfile, LoginAttempt, Logger, NfeAuditLog, NfeCredential, NfeDocument, NfeLog, NfeQueue, NfeReceivedDocument, NfeReportModel, NfeWebhook, Order, OrderItemLog, OrderPreparation, PaymentGateway, Pipeline, PortalAccess, PortalMessage, PreparationStep, PriceTable, Product, ProductGrade, ProductionSector, ReportModel, SiteBuilder, Stock, Subcategory, Transaction, User, UserGroup

- **Status:** ⬜ Pendente

---

### PSR11-008: Refatorar BaseController

- **Prioridade:** ALTO
- **Arquivo:** `app/controllers/BaseController.php`
- **Ação:**

```php
// ANTES
abstract class BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Database::getInstance();
    }
}

// DEPOIS
abstract class BaseController
{
    protected \PDO $db;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }
}
```

- **Impacto:** Controllers Cat.A (3) passam a receber PDO injetado
- **Status:** ⬜ Pendente

---

## Fase 3 — Controllers (Refatoração Progressiva)

### PSR11-009: Refatorar Controllers Simples (1-2 deps)

- **Prioridade:** MÉDIO
- **Escopo:** ~12 controllers

```php
// ANTES
public function __construct() {
    $database = new Database();
    $this->db = $database->getConnection();
    $this->model = new SomeModel($this->db);
}

// DEPOIS
public function __construct(\PDO $db, SomeModel $model) {
    $this->db = $db;
    $this->model = $model;
}
```

**Controllers alvo:**
AttachmentController, AuditController, CalendarController, CustomReportController, EmailMarketingController, EmailTrackingController, QualityController, QuoteController, RecurringTransactionController, SiteBuilderController, WalkthroughController, WorkflowController

- **Status:** ⬜ Pendente

---

### PSR11-010: Refatorar Controllers Médios (3-6 deps)

- **Prioridade:** MÉDIO
- **Escopo:** ~10 controllers

**Controllers alvo:**
CategoryController, CommissionController, FinancialController, FinancialImportController, InstallmentController, NfeCredentialController, PaymentGatewayController, SectorController, StockController, SupplierController, TransactionController

- **Status:** ⬜ Pendente

---

### PSR11-011: Refatorar Controllers Complexos (7+ deps)

- **Prioridade:** MÉDIO
- **Escopo:** ~10 controllers

**Controllers alvo:**
CatalogController, CustomerController, NfeDocumentController, OrderController, PipelineController, PortalAdminController, PortalController, ProductController, ReportController, SettingsController, UserController

**Nota:** Estes controllers podem precisar de decomposição (muitas dependências podem indicar violação de SRP).

- **Status:** ⬜ Pendente

---

### PSR11-012: Registrar Factories para Services Especiais

- **Prioridade:** BAIXO
- **Escopo:** ReportPdfService, ReportExcelService

```php
// Estes services precisam de dados runtime (array $company, string $responsibleUser)
// Não são auto-wirable puros — precisam de factory
$container->bind(ReportPdfService::class, function ($c) {
    // Resolver via contexto da sessão/request
    // ou usar factory method no controller
});
```

- **Status:** ⬜ Pendente

---

## Fase 4 — Otimização e Testing

### PSR11-013: Cache de Reflection

- **Prioridade:** BAIXO
- **Ação:** Adicionar cache de ReflectionClass no Container para evitar re-inspeção

```php
private array $reflectionCache = [];

private function getReflection(string $class): \ReflectionClass
{
    return $this->reflectionCache[$class] ??= new \ReflectionClass($class);
}
```

- **Status:** ⬜ Pendente

---

### PSR11-014: Testes Unitários do Container

- **Prioridade:** ALTO
- **Arquivo novo:** `tests/Unit/ContainerTest.php`
- **Cenários:**
  - `test_get_returns_registered_binding`
  - `test_get_throws_not_found_exception`
  - `test_has_returns_true_for_registered`
  - `test_has_returns_false_for_unknown`
  - `test_singleton_returns_same_instance`
  - `test_auto_wiring_resolves_pdo`
  - `test_auto_wiring_resolves_model_recursively`
  - `test_auto_wiring_resolves_service_with_model`

- **Status:** ⬜ Pendente

---

### PSR11-015: Testes Unitários de Controllers com Mock

- **Prioridade:** MÉDIO
- **Benefício:** Validar que controllers funcionam com PDO mockado
- **Escopo:** Priorizar controllers com regras de negócio complexas

- **Status:** ⬜ Pendente

---

## Resumo do Roadmap

| Fase | Itens | Descrição | Deps |
|------|-------|-----------|------|
| **1 — Foundation** | PSR11-001 a 006 | Container, exceções, integração | Nenhuma |
| **2 — Models & Base** | PSR11-007, 008 | Type-hints, BaseController | Fase 1 |
| **3 — Controllers** | PSR11-009 a 012 | Refatoração progressiva | Fase 2 |
| **4 — Testing** | PSR11-013 a 015 | Cache, testes, coverage | Fase 3 |

---

## Estratégia de Migração

### Compatibilidade Retroativa

A migração pode ser **gradual e não-breaking**:

1. **Container coexiste** com `new Database()` — controllers antigos continuam funcionando
2. **Auto-wiring** resolve controllers com PDO type-hint automaticamente
3. **Fallback no Router** mantém `new $class()` para controllers sem type-hint
4. **BaseController** pode suportar ambos os padrões temporariamente:

```php
abstract class BaseController
{
    protected \PDO $db;

    public function __construct(?\PDO $db = null)
    {
        $this->db = $db ?? \Database::getInstance();
    }
}
```

### Ordem de Migração Sugerida

```
1. Instalar psr/container + criar Container       ◄── Sem impacto
2. Registrar PDO no Container                     ◄── Sem impacto
3. Integrar Container no Application/Router        ◄── Sem impacto (fallback)
4. Adicionar PDO type-hint nos models             ◄── Sem impacto
5. Refatorar BaseController (com fallback null)   ◄── Sem impacto
6. Converter controllers 1 a 1, testando cada     ◄── Impacto isolado
7. Remover fallbacks quando tudo migrado          ◄── Cleanup
```

---

## Métricas de Sucesso

| Métrica | Antes | Após Fase 1 | Após Fase 4 |
|---------|-------|-------------|-------------|
| PSR-11 compliance | 0% | 100% (interface) | 100% |
| Controllers container-ready | 2% | 2% | 100% |
| `new Database()` em controllers | 38 | 38 | 0 |
| `new Model($db)` manual | ~200 | ~200 | 0 |
| Controllers testáveis com mock | 1 | 1 | 42 |
| Models com PDO type-hint | 29% | 29% | 100% |
| Linhas de boilerplate eliminadas | 0 | 0 | ~400-500 |
