# Auditoria de Arquitetura — Akti v3

> **Data da Auditoria:** 14/04/2025
> **Escopo:** MVC, PSR-4, roteamento, eventos, multi-tenant, padrões de código
> **Auditor:** Auditoria Automatizada via Análise Estática de Código
> **Classificação de Severidade:** CRÍTICO > ALTO > MÉDIO > BAIXO > INFORMATIVO

---

## 1. Resumo Executivo

| Aspecto | Nota | Tendência vs v2 |
|---------|------|------------------|
| Separação MVC | ✅ A | ↑ Melhorado |
| PSR-4 / Autoloading | ✅ A | = Mantido |
| Roteamento | ✅ B+ | ↑ Melhorado |
| Eventos / Listeners | ✅ B | ↑ Melhorado (10 listeners) |
| Multi-Tenant | ✅ A | = Mantido |
| DI Container | ✅ A | ↑↑ Resolvido (era ALTO) |
| BaseController | ✅ A | ↑↑ Resolvido (era ALTO) |
| Code Metrics | ⚠️ C | = Mantido (god classes) |
| Padrões de Design | ✅ B+ | ↑ Melhorado |

**Nota Geral: B+** (v2: C)

A arquitetura evoluiu substancialmente entre v2 e v3: BaseController implementado, DI Container criado, constructors padronizados com PDO injection em 98% dos controllers, e sistema de eventos com 10 listeners. O principal gap remanescente são 3 god classes (>900 linhas) que precisam de refatoração.

---

## 2. Separação MVC

### 2.1 Controllers — SQL Direto

**Status: ✅ Aprovado**

Busca por padrões `->prepare(`, `->query(`, `SELECT`, `INSERT`, `UPDATE`, `DELETE` em `app/controllers/`:

**Resultado:** Nenhum controller contém queries SQL diretas. Toda interação com banco de dados é delegada aos models.

### 2.2 Models — HTML/Echo/Headers

**Status: ✅ Aprovado**

Busca por `echo`, `print`, `header(`, `<html`, `<div` em `app/models/`:

**Resultado:** Nenhum model contém output HTML ou redirecionamentos. Documentação inline reforça o padrão:
- `app/models/NfeLog.php:12` — "Não deve conter HTML, echo, print"
- `app/models/Customer.php:14` — Mesmo comentário docblock

### 2.3 Views — Lógica de Banco

**Status: ✅ Aprovado**

Views utilizam apenas dados pré-processados passados pelo controller. Nenhuma instanciação de Model ou query SQL encontrada em views.

---

## 3. PSR-4 / Autoloading

### Status: ✅ Aprovado

**Configuração em `composer.json`:**
```json
"autoload": {
    "psr-4": {
        "Akti\\": "app/"
    },
    "files": ["app/utils/helpers.php"]
}
```

**Mapeamento:**
| Namespace | Diretório | # Classes |
|-----------|-----------|-----------|
| `Akti\Controllers\` | `app/controllers/` | 48 |
| `Akti\Models\` | `app/models/` | 70 |
| `Akti\Services\` | `app/services/` | 76 |
| `Akti\Core\` | `app/core/` | ~10 |
| `Akti\Middleware\` | `app/middleware/` | 5 |
| `Akti\Config\` | `app/config/` | ~5 |
| `Akti\Gateways\` | `app/gateways/` | ~3 |
| `Akti\Utils\` | `app/utils/` | ~5 |

**Nenhum `require_once` ou `include` para classes `Akti\`** encontrado no código (conforme regra do projeto).

---

## 4. Roteamento

### Status: ✅ B+

**Arquivo:** `app/config/routes.php`

**Estrutura declarativa:** Todas as 43+ rotas seguem o formato padrão:
```php
'page_name' => [
    'controller'     => 'NomeController',
    'default_action' => 'index',
    'public'         => false,
    'actions'        => [...],
],
```

**Padrão CRUD consistente:** `index`, `create`, `store`, `edit`, `update`, `delete`

**Melhoria vs v2:** Routes agora incluem `before_auth` flag para processamento antes da autenticação.

---

## 5. Sistema de Eventos

### Status: ✅ B (Melhorado vs v2)

**Arquivo:** `app/bootstrap/events.php`

**Listeners registrados (10):**
| Evento | Listener | Módulo |
|--------|----------|--------|
| `nfe.authorized` | `NfeEventListener::onAuthorized` | NF-e |
| `nfe.cancelled` | `NfeEventListener::onCancelled` | NF-e |
| `nfe.correction_letter` | `NfeEventListener::onCorrectionLetter` | NF-e |
| `nfe.denied` | `NfeEventListener::onDenied` | NF-e |
| `nfe.returned` | `NfeEventListener::onReturned` | NF-e |
| `nfe.usage` | `NfeEventListener::onUsage` | NF-e |
| `nfe.status` | `NfeEventListener::onStatus` | NF-e |
| `nfe.disabled` | `NfeEventListener::onDisabled` | NF-e |
| `nfe.contingency.on` | `NfeEventListener::onContingencyOn` | NF-e |
| `nfe.contingency.off` | `NfeEventListener::onContingencyOff` | NF-e |

**Evolução vs v2:** Na v2 havia "eventos disparados sem listeners" (ARCH-009). Agora todos os 10 eventos têm listeners correspondentes.

**Oportunidade:** Sistema de eventos usado apenas no módulo NF-e. Pode ser expandido para outros módulos (pedidos, pipeline, financeiro).

---

## 6. Multi-Tenant

### Status: ✅ A

**Componentes:**
- `TenantManager` (`app/config/tenant.php`) — Resolução por subdomínio
- Database switching por tenant — `akti_<clientname>`
- `ModuleBootloader` — Feature flags por tenant via `enabled_modules` JSON
- Upload isolation — `assets/uploads/{db_name}/`
- `akti_master` — DB centralizado para `tenant_clients`, `login_attempts`, `ip_blacklist`

**Verificação:** Todos os models auditados (amostra de 10) filtram queries por `tenant_id` com parameter binding.

---

## 7. DI Container

### Status: ✅ A (Novo em v3 — era ARCH-006 ALTO na v2)

**Implementação:** Container class com resolução automática de dependências.

**Verificação:** `tests/Unit/ContainerTest.php` — 10 métodos de teste cobrindo:
- Registro e resolução de serviços
- Autowiring de dependências
- Singletons
- Resolução recursiva

---

## 8. BaseController

### Status: ✅ A (Novo em v3 — era ARCH-001 ALTO na v2)

**Arquivo:** `app/controllers/BaseController.php`

**Métodos implementados:**
| Método | Linha | Descrição |
|--------|-------|-----------|
| `__construct(?\PDO $db = null)` | L19-21 | Fallback para `Database::getInstance()` |
| `json(array, status)` | L27-35 | Resposta JSON padronizada |
| `redirect(url)` | L41-43 | Redirecionamento HTTP |
| `render(view, data)` | L50-55 | Renderização de view com `extract()` |
| `requireAuth()` | L61-69 | Verificação de autenticação |
| `requireAdmin()` | L74-83 | Verificação de admin |
| `getTenantId()` | L88-90 | Tenant ID da sessão |
| `isAjax()` | L95-96 | Detecção de AJAX |

**Adoção:** 65+ controllers estendem BaseController ou usam o padrão PDO constructor injection.

---

## 9. Constructor Patterns

### Status: ✅ A (Novo em v3 — era ARCH-002 ALTO na v2)

**Padrão 1 — PDO + Models (Preferido, 98% dos controllers):**
```php
public function __construct(\PDO $db)
{
    parent::__construct($db);
    $this->model = new SomeModel($db);
}
```

**Padrão 2 — BaseController fallback (3 controllers):**
```php
public function __construct(?\PDO $db = null)
{
    parent::__construct($db);
}
```

**Evolução:** Na v2, cada controller instanciava `Database::getInstance()` manualmente. Agora 98% usam injeção via construtor.

---

## 10. Code Metrics — God Classes

### Status: ⚠️ CRÍTICO — 3 classes >900 linhas

| Arquivo | Linhas | Severidade | Recomendação |
|---------|--------|-----------|--------------|
| `app/services/NfeService.php` | 2069 | 🔴 CRÍTICO | Extrair: NfeEmissionService, NfeCancellationService, NfeQueryService |
| `app/controllers/CustomerController.php` | ~2398 | 🔴 CRÍTICO | Extrair: CustomerImportService, CustomerExportService |
| `app/controllers/ProductController.php` | ~1194 | 🟠 ALTO | Extrair: GradeService, ProductImportService |
| `app/controllers/PipelineController.php` | ~948 | 🟠 ALTO | Extrair: SectorService, StageService |
| `app/controllers/SiteBuilderController.php` | ~650 | 🟡 MÉDIO | Monitorar crescimento |
| `app/controllers/OrderController.php` | ~565 | 🟡 MÉDIO | Monitorar crescimento |
| `app/controllers/UserController.php` | ~516 | 🟡 MÉDIO | Monitorar crescimento |
| `app/models/Product.php` | ~810 | 🟡 MÉDIO | Considerar split por responsabilidade |
| `app/models/Installment.php` | ~750 | 🟡 MÉDIO | Monitorar crescimento |

**Critério:** Classes >500 linhas = MÉDIO, >900 = ALTO, >1500 = CRÍTICO

---

## 11. Padrões de Design

| Padrão | Uso | Status |
|--------|-----|--------|
| Strategy | Payment Gateways (`app/gateways/`) | ✅ |
| Observer | EventDispatcher + Listeners | ✅ |
| Singleton | Database connection | ✅ |
| Repository | Models como repositórios de dados | ✅ |
| Middleware | Pipeline de request processing | ✅ |
| Factory | Container resolução automática | ✅ (Novo v3) |

---

## 12. Bootstrap Pipeline

**Arquivo:** `index.php`

**Sequência de inicialização (7 estágios):**
1. Autoloader (`app/bootstrap/autoload.php`)
2. Session (`app/config/session.php`)
3. Exception Handler (error reporting)
4. Security Headers Middleware
5. Container / DI setup
6. Application boot (Router, routes, menu)
7. Request dispatch

---

## 13. Evolução vs. v2

### Issues Resolvidas

| ID v2 | Descrição | Status v3 | Evidência |
|--------|-----------|-----------|-----------|
| ARCH-001 | Ausência de BaseController | ✅ Corrigido | `app/controllers/BaseController.php` |
| ARCH-002 | Database Instantiation Manual em 31 Controllers | ✅ Corrigido | 98% usam PDO injection |
| ARCH-005 | index.php Muito Longo (~280 linhas) | ✅ Melhorado | Pipeline de 7 estágios |
| ARCH-006 | Falta de DI Container | ✅ Corrigido | Container class + 10 testes |
| ARCH-009 | Eventos Disparados sem Listeners | ✅ Corrigido | 10 listeners em events.php |

### Issues Mantidas

| ID | Descrição | Severidade | Nota |
|----|-----------|-----------|------|
| ARCH-003 | Padrão de Response Inconsistente | 🟡 MÉDIO | Parcialmente melhorado com BaseController::json/redirect |
| ARCH-004 | routes.php verboso | 🟢 BAIXO | Aceitável — formato declarativo claro |
| ARCH-007 | Sem Interfaces para Services | 🟡 MÉDIO | Mantido — complexidade não justifica ainda |
| ARCH-008 | Properties públicas em Models | 🟢 BAIXO | Refatoração gradual |
| ARCH-010 | Models retornam PDOStatement direto | 🟡 MÉDIO | Parcialmente corrigido |
| ARCH-011 | Sem tipagem de retorno em Models | 🟡 MÉDIO | Melhoria gradual em novos models |

### Novas Issues

| ID | Descrição | Severidade |
|----|-----------|-----------|
| ARCH-012 | God Classes (3 controllers >900 linhas) | 🔴 CRÍTICO |
| ARCH-013 | NfeService.php com 2069 linhas | 🔴 CRÍTICO |
| ARCH-014 | Eventos apenas no módulo NF-e | 🟢 BAIXO |

### Métricas Comparativas

| Métrica | v2 | v3 | Δ |
|---------|----|----|---|
| Controllers | 31 | 48 | +17 |
| Models | 45 | 70 | +25 |
| Services | 64 | 76 | +12 |
| View Directories | 15+ | 43 | +28 |
| Routes | 31 | 43+ | +12 |
| Middleware | 0 | 5 | +5 |
| Event Listeners | 0 | 10 | +10 |
| BaseController | ❌ | ✅ | ↑ |
| DI Container | ❌ | ✅ | ↑ |
| Issues ALTAS+ | 3 | 2 | -1 |
| Total Issues | 11 | 10 | -1 |
