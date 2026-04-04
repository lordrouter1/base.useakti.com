# 01 — Estado Atual da Injeção de Dependência — Akti

> **Data da Auditoria:** 04/04/2026
> **Escopo:** Mapeamento completo do estado atual de DI no sistema
> **Classificação de Severidade:** CRÍTICO > ALTO > MÉDIO > BAIXO > INFORMATIVO

---

## 1. Resumo Executivo

O sistema Akti possui uma abordagem **híbrida e inconsistente** de injeção de dependência:

- **Nenhuma interface PSR-11** implementada
- **Nenhum Service Container** formal
- **1 mecanismo leve** de injection via Reflection no Router (apenas \PDO)
- **86% dos controllers** usam `new Database()` manualmente
- **100% dos services** possuem constructor injection correta (prontos para container)
- **100% dos models** aceitam PDO no construtor (prontos para container)

---

## 2. Componentes Existentes

### 2.1 Router — DI Container Leve (ARQ-012) ✅

**Arquivo:** `app/core/Router.php` (linhas 256-296)
**Status:** Implementado e funcional

```php
private function createController(string $class): object
{
    $ref = new \ReflectionClass($class);
    $ctor = $ref->getConstructor();

    if ($ctor === null || $ctor->getNumberOfParameters() === 0) {
        return new $class();
    }

    $params = $ctor->getParameters();
    $args = [];

    foreach ($params as $param) {
        $type = $param->getType();
        $typeName = $type instanceof \ReflectionNamedType ? $type->getName() : null;

        if ($typeName === 'PDO' || $typeName === \PDO::class) {
            $args[] = \Database::getInstance();
        } elseif ($param->isDefaultValueAvailable()) {
            $args[] = $param->getDefaultValue();
        } else {
            return new $class(); // fallback
        }
    }

    return $ref->newInstanceArgs($args);
}
```

**Avaliação:**
- ✅ Usa Reflection para inspeção de tipo
- ✅ Fallback gracioso para controllers sem construtor
- ❌ Só resolve `PDO` — ignora Models, Services e outros tipos
- ❌ Não implementa `ContainerInterface`
- ❌ Não tem registro de bindings (service map)
- ❌ Sem suporte a auto-wiring recursivo
- ❌ Sem cache de reflection (re-inspeciona a cada request)

### 2.2 Database — Singleton com Cache por DSN ✅

**Arquivo:** `app/config/database.php`
**Status:** Funcional e bem implementado

```php
class Database {
    private static $instances = [];  // Cache por DSN

    public static function getInstance(?string $tenantDb = null): PDO { ... }
    public function getConnection(): PDO { ... }  // Compatibilidade legada
}
```

**Avaliação:**
- ✅ Singleton por DSN (correto para multi-tenant)
- ✅ `ERRMODE_EXCEPTION` configurado
- ✅ Wrapper de compatibilidade `getConnection()`
- ⚠️ Classe global (sem namespace) — não seria resolvível por PSR-11 sem alias
- ❌ Não está registrada em nenhum container

### 2.3 BaseController — Acesso Estático ao DB ⚠️

**Arquivo:** `app/controllers/BaseController.php` (linhas 19-23)

```php
abstract class BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Database::getInstance();
    }
}
```

**Avaliação:**
- ⚠️ Acesso estático direto ao `Database::getInstance()` — não é injetável
- ❌ Não aceita `PDO` via parâmetro do construtor
- ❌ Filhos que estendem BaseController herdam o acoplamento estático
- ⚠️ Apenas 3 controllers usam esta classe base sem sobrescrever o construtor

### 2.4 HealthController — Único Controller Injetável ✅

**Arquivo:** `app/controllers/HealthController.php` (linhas 20-24)

```php
class HealthController
{
    private $db;

    public function __construct(?\PDO $db = null)
    {
        $this->db = $db;
    }
}
```

**Avaliação:**
- ✅ PDO opcional via construtor — container-ready
- ✅ Funciona com e sem container
- ✅ **Template ideal** para refatoração dos demais controllers

---

## 3. Padrões de Instanciação Identificados

### 3.1 Padrão Dominante: `new Database()` (86% dos controllers)

```php
// 36 controllers seguem este padrão
public function __construct() {
    $database = new Database();
    $this->db = $database->getConnection();

    $this->userModel = new User($this->db);
    $this->logger = new Logger($this->db);
    // ... mais 5-15 modelos manualmente instanciados
}
```

**Problemas:**
- ❌ **Acoplamento forte** — controller depende de classe concreta `Database`
- ❌ **Não testável** — impossível mockar PDO sem hackear a classe Database
- ❌ **Duplicação massiva** — cada controller repete o boilerplate de wiring
- ❌ **Desperdício potencial** — instancia modelos que nem sempre são usados

**Controllers que seguem o padrão:**

| Controller | Modelos/Services instanciados | Linhas de boilerplate |
|------------|------------------------------|----------------------|
| CustomerController | ~10 modelos + 5 services | ~25 linhas |
| OrderController | ~8 modelos + 1 service | ~20 linhas |
| PipelineController | ~8 modelos + 4 services | ~25 linhas |
| ProductController | ~7 modelos + 2 services | ~20 linhas |
| NfeDocumentController | ~4 modelos + 15 services | ~30 linhas |
| CatalogController | ~5 modelos + 2 services | ~15 linhas |
| CategoryController | ~6 modelos + 1 service | ~15 linhas |
| SettingsController | ~5 modelos + 1 service | ~15 linhas |
| UserController | ~6 modelos + 1 service | ~18 linhas |
| FinancialController | ~2 modelos + 2 services | ~12 linhas |
| + 26 outros | Variável | ~10-15 linhas cada |

**Estimativa total: ~150-200 instanciações manuais de `new Model($db)`**

### 3.2 Padrão Variante: `(new Database())->getConnection()` (3 controllers)

```php
// CategoryController, PortalAdminController, PortalController
$this->db = (new Database())->getConnection();
```

Funcionalmente idêntico ao padrão dominante, mas inline.

### 3.3 Padrão Ad-Hoc: DB criado dentro de métodos (2 controllers)

```php
// DashboardController — Database instanciado em index(), não no construtor
public function index() {
    $database = new Database();
    $db = $database->getConnection();
    // ...
}
```

**Problemas adicionais:**
- ❌ Cria nova conexão (ou busca singleton) em cada chamada de action
- ❌ Sem centralização — cada método repete o boilerplate

---

## 4. Fluxo Atual de Resolução de Dependências

```
REQUEST
  │
  ▼
index.php
  │ require autoload.php
  │ new Application($basePath)
  │
  ▼
Application::boot()
  │ SecurityHeaders, TenantManager, Session, Router
  │
  ▼
Application::handle()
  │ Auth, Permissions, CSRF, ModuleBootloader
  │
  ▼
Router::dispatch()
  │
  ├── resolveRoute() → encontra controller + action
  │
  ├── createController($class) ◄── DI LEVE AQUI
  │   │ ReflectionClass → inspeciona construtor
  │   │ Se PDO type-hint → Database::getInstance()
  │   │ Senão → new $class() direto
  │   │
  │   └── Retorna instância do controller
  │
  └── $controller->$action() → executa o método
        │
        └── Controller internamente faz:
              new Database()
              new Model($db)        ◄── MANUAL
              new Service($db, ...)  ◄── MANUAL
```

**Problema central:** O Router resolve apenas PDO, mas os controllers ignoram isso e fazem `new Database()` por conta própria.

---

## 5. Análise de Testabilidade

### Estado Atual

| Aspecto | Status | Detalhes |
|---------|--------|---------|
| Controllers testáveis sem DB | 1/42 (2%) | Apenas HealthController |
| Services testáveis com mock | 79/79 (100%) | Todos aceitam PDO injetado |
| Models testáveis com mock | 59/59 (100%) | Todos aceitam PDO no construtor |
| Integração container-aware | 0 | Inexistente |

### Impacto

- **Testes unitários de controller** são praticamente impossíveis sem refatorar
- **Testes de integração** requerem banco real para cada controller
- **Services e Models** são bem projetados e prontos para testing via mock

---

## 6. Dependências Externas Relevantes

### composer.json Atual

```json
{
    "require": {
        "php": ">=8.1",
        "phpmailer/phpmailer": "^6.9",
        "phpoffice/phpspreadsheet": "^5.5",
        "tecnickcom/tcpdf": "^6.11"
    }
}
```

**Ausente:** `psr/container` (PSR-11)

---

## 7. Referências a DI/Container na Documentação Existente

| Documento | Referência | Status |
|-----------|-----------|--------|
| `docs/ROADMAP.md` (L76) | A-01: Injeção de Dependência (DI Container) | ⬜ Planejado (parcialmente feito) |
| `docs/geral/v2/09_ROADMAP_CORRECOES_ARQUITETURA.md` (L186) | ARQ-012: DI Container Leve | ✅ Implementado (só PDO) |
| `docs/geral/v2/02_AUDITORIA_ARQUITETURA.md` (L249) | Nota sobre substituir `new Database()` | ⬜ Pendente |
| `docs/geral/V1/AUDITORIA_GERAL_2026.md` (L96) | Correção: implementar DI Container | ⬜ Pendente |

---

## 8. Conclusão

O sistema está em **estado intermediário**: a camada de services e models está **bem projetada** para DI, mas a camada de controllers está **fortemente acoplada** ao `Database` concreto. A implementação do ARQ-012 foi um bom primeiro passo, porém cobre apenas 2% do cenário real. Uma implementação PSR-11 completa elevaria significativamente a testabilidade, manutenibilidade e conformidade com padrões da indústria.
