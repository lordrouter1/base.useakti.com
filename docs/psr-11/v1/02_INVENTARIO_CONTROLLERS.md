# 02 — Inventário de Controllers — Auditoria PSR-11

> **Data da Auditoria:** 04/04/2026
> **Total de Controllers:** 42
> **Controllers Container-Ready:** 1 (2%)

---

## 1. Categorização por Padrão de DI

### Categoria A — BaseController (herdam `$this->db` do pai) — 3 controllers

| # | Controller | Arquivo | Construtor | Deps Herdadas |
|---|-----------|---------|------------|---------------|
| 1 | HomeController | `app/controllers/HomeController.php` | Nenhum | `$this->db` via BaseController |
| 2 | NotificationController | `app/controllers/NotificationController.php` | Nenhum | `$this->db` via BaseController |
| 3 | SearchController | `app/controllers/SearchController.php` | Nenhum | `$this->db` via BaseController |

**Status:** ⚠️ Funcional, mas acoplado a `Database::getInstance()` via parent

---

### Categoria B — `new Database()` no construtor — 35 controllers

#### B1: Padrão `$database = new Database(); $this->db = $database->getConnection();`

| # | Controller | Arquivo | Models instanciados | Services instanciados |
|---|-----------|---------|--------------------|-----------------------|
| 1 | AttachmentController | `app/controllers/AttachmentController.php:10` | Attachment | — |
| 2 | AuditController | `app/controllers/AuditController.php:10` | AuditLog | — |
| 3 | CalendarController | `app/controllers/CalendarController.php:10` | CalendarEvent | — |
| 4 | CatalogController | `app/controllers/CatalogController.php:28` | CatalogLink, Order, Product, PriceTable, CompanySettings, Logger | CatalogCartService, CatalogQuoteService |
| 5 | CommissionController | `app/controllers/CommissionController.php:27` | Commission | CommissionEngine, CommissionService |
| 6 | CustomerController | `app/controllers/CustomerController.php:35` | Customer, CustomerContact, ImportBatch, ImportMappingProfile, Logger, User | CustomerImportService, CustomerExportService + 3 outros |
| 7 | CustomReportController | `app/controllers/CustomReportController.php:13` | ReportTemplate | — |
| 8 | DashboardWidgetController | `app/controllers/DashboardWidgetController.php:17` | — | — |
| 9 | EmailMarketingController | `app/controllers/EmailMarketingController.php:12` | EmailCampaign | — |
| 10 | EmailTrackingController | `app/controllers/EmailTrackingController.php:12` | — | — |
| 11 | FinancialController | `app/controllers/FinancialController.php:41` | Financial, Installment | InstallmentService, FinancialReportService |
| 12 | FinancialImportController | `app/controllers/FinancialImportController.php:20` | Financial | FinancialImportService |
| 13 | InstallmentController | `app/controllers/InstallmentController.php:42` | Installment, Financial, Order | InstallmentService, TransactionService |
| 14 | NfeCredentialController | `app/controllers/NfeCredentialController.php:23` | NfeCredential | — |
| 15 | NfeDocumentController | `app/controllers/NfeDocumentController.php:55` | NfeDocument, NfeLog, User, IbptaxModel | 15+ NfeServices |
| 16 | OrderController | `app/controllers/OrderController.php:21` | Order, Product, Customer, Pipeline, Logger, PriceTable, CompanySettings, Financial | OrderItemService |
| 17 | PaymentGatewayController | `app/controllers/PaymentGatewayController.php:22` | PaymentGateway | GatewayManager |
| 18 | PipelineController | `app/controllers/PipelineController.php:28` | Pipeline, Stock, Logger, OrderItemLog, OrderPreparation, Financial, User, PriceTable, CompanySettings | PipelineService + 3 outros |
| 19 | ProductController | `app/controllers/ProductController.php:27` | Product, Category, Subcategory, ProductionSector, ProductGrade, Logger, PriceTable | ProductImportService, ProductGradeService |
| 20 | QualityController | `app/controllers/QualityController.php:13` | QualityChecklist | — |
| 21 | QuoteController | `app/controllers/QuoteController.php:13` | Quote | — |
| 22 | RecurringTransactionController | `app/controllers/RecurringTransactionController.php:18` | RecurringTransaction | — |
| 23 | ReportController | `app/controllers/ReportController.php:28` | ReportModel, NfeReportModel, CompanySettings | ReportPdfService, ReportExcelService |
| 24 | SettingsController | `app/controllers/SettingsController.php:20` | CompanySettings, PriceTable, PreparationStep, UserGroup, DashboardWidget | SettingsService |
| 25 | SiteBuilderController | `app/controllers/SiteBuilderController.php:18` | SiteBuilder, Product | — |
| 26 | StockController | `app/controllers/StockController.php:20` | Stock, Product, Logger, Order | StockMovementService |
| 27 | SupplierController | `app/controllers/SupplierController.php:11` | Supplier, PurchaseOrder | — |
| 28 | TransactionController | `app/controllers/TransactionController.php:26` | Financial | TransactionService |
| 29 | UserController | `app/controllers/UserController.php:24` | User, UserGroup, LoginAttempt, Logger, PortalAccess, Customer | AuthService |
| 30 | WalkthroughController | `app/controllers/WalkthroughController.php:11` | Walkthrough | — |
| 31 | WorkflowController | `app/controllers/WorkflowController.php:13` | WorkflowRule | — |

#### B2: Variante `(new Database())->getConnection()` (inline)

| # | Controller | Arquivo | Models instanciados | Services instanciados |
|---|-----------|---------|--------------------|-----------------------|
| 32 | CategoryController | `app/controllers/CategoryController.php:23` | Category, Subcategory, ProductionSector, ProductGrade, CategoryGrade, Logger | CategoryService |
| 33 | PortalAdminController | `app/controllers/PortalAdminController.php:24` | PortalAccess | PortalAdminService |
| 34 | PortalController | `app/controllers/PortalController.php:31` | PortalAccess, CompanySettings, Logger | 7+ portal services |
| 35 | SectorController | `app/controllers/SectorController.php:11` | ProductionSector, Logger, User | — |

**Status:** 🔴 CRÍTICO — Acoplamento forte, não testável, boilerplate massivo

---

### Categoria C — Sem construtor ou DB ad-hoc em métodos — 2 controllers

| # | Controller | Arquivo | Padrão |
|---|-----------|---------|--------|
| 1 | ApiController | `app/controllers/ApiController.php` | Sem construtor — usa JwtHelper/session |
| 2 | DashboardController | `app/controllers/DashboardController.php:7` | `new Database()` dentro do `index()` |

**Status:** 🔴 Inconsistente e imprevisível

---

### Categoria D — PDO injetável via construtor — 1 controller

| # | Controller | Arquivo | Construtor |
|---|-----------|---------|------------|
| 1 | HealthController | `app/controllers/HealthController.php:20` | `public function __construct(?\PDO $db = null)` |

**Status:** 🟢 **Container-ready** — modelo a seguir

---

### Categoria E — Classe base abstrata — 1 controller

| # | Controller | Arquivo | Construtor |
|---|-----------|---------|------------|
| 1 | BaseController | `app/controllers/BaseController.php:19` | `$this->db = \Database::getInstance()` |

**Status:** ⚠️ Funcional mas acoplado

---

## 2. Estatísticas Consolidadas

| Categoria | Qtd | % | Container-Ready | Testável |
|-----------|-----|---|-----------------|----------|
| A (BaseController inherit) | 3 | 7% | ❌ | ❌ |
| B (new Database()) | 35 | 83% | ❌ | ❌ |
| C (ad-hoc) | 2 | 5% | ❌ | ❌ |
| D (PDO injetável) | 1 | 2% | ✅ | ✅ |
| E (classe base) | 1 | 2% | ❌ | ❌ |
| **Total** | **42** | **100%** | **1 (2%)** | **1 (2%)** |

---

## 3. Análise de Complexidade de Construtores

### Top 10 Controllers com Mais Dependências Manuais

| # | Controller | Models | Services | Total deps | Complexidade |
|---|-----------|--------|----------|------------|--------------|
| 1 | NfeDocumentController | 4 | 15+ | ~19 | 🔴 CRÍTICA |
| 2 | CustomerController | 6 | 5+ | ~11 | 🔴 ALTA |
| 3 | PipelineController | 9 | 4 | ~13 | 🔴 ALTA |
| 4 | OrderController | 8 | 1 | ~9 | 🔴 ALTA |
| 5 | ProductController | 7 | 2 | ~9 | 🟡 MÉDIA |
| 6 | CatalogController | 6 | 2 | ~8 | 🟡 MÉDIA |
| 7 | PortalController | 3 | 7+ | ~10 | 🔴 ALTA |
| 8 | CategoryController | 6 | 1 | ~7 | 🟡 MÉDIA |
| 9 | SettingsController | 5 | 1 | ~6 | 🟡 MÉDIA |
| 10 | UserController | 6 | 1 | ~7 | 🟡 MÉDIA |

**Observação:** Controllers com >10 dependências são candidatos prioritários para Service Layer ou decomposição.

---

## 4. Contagem de Instanciações Manuais

| Padrão | Ocorrências | Local |
|--------|-------------|-------|
| `new Database()` | 38 | Construtores de controllers |
| `Database::getInstance()` | ~5 | BaseController, DashboardWidgetController |
| `new Model($db)` | ~150-200 | Construtores de controllers |
| `new Service($db, ...)` | ~50-70 | Construtores de controllers |
| **Total de wiring manual** | **~250-300** | **Apenas em controllers** |

---

## 5. Controllers que Verificam Módulos

Alguns controllers verificam se o módulo está habilitado antes de continuar. Essa lógica também é manual e poderia ser container-aware:

| Controller | Verificação |
|-----------|------------|
| FinancialController | `ModuleBootloader::isModuleEnabled('financeiro')` |
| FinancialImportController | `ModuleBootloader::isModuleEnabled('financeiro')` |
| InstallmentController | `ModuleBootloader::isModuleEnabled('financeiro')` |
| TransactionController | `ModuleBootloader::isModuleEnabled('financeiro')` |
| NfeCredentialController | `ModuleBootloader::isModuleEnabled('nfe')` |
| NfeDocumentController | `ModuleBootloader::isModuleEnabled('nfe')` |
| RecurringTransactionController | `ModuleBootloader::isModuleEnabled('financeiro')` |
