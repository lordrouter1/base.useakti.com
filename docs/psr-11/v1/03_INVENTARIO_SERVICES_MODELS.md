# 03 — Inventário de Services e Models — Auditoria PSR-11

> **Data da Auditoria:** 04/04/2026
> **Total de Services:** 79
> **Total de Models:** 59
> **Prontos para Container:** Services 100% · Models 100%

---

## 1. Services — Inventário Completo

### 1.1 Resumo

Todos os 79 services usam **constructor injection** corretamente. São a camada mais bem projetada do sistema para adoção de PSR-11.

| Subgrupo | Qtd | Padrão | Container-Ready |
|----------|-----|--------|-----------------|
| S1: PDO Only | ~45 | `__construct(PDO $db)` | ✅ |
| S2: PDO + Models | ~20 | `__construct(PDO $db, Model $m, ...)` | ✅ |
| S3: Model Only | ~4 | `__construct(Model $m)` | ✅ |
| S4: Service-to-Service | ~10 | `__construct(PDO $db, Service $s, ...)` | ✅ |

### 1.2 Subgrupo S1 — PDO Only (~45 services)

Padrão:
```php
public function __construct(PDO $db) {
    $this->db = $db;
}
```

| # | Service | Arquivo | Type-Hint |
|---|---------|---------|-----------|
| 1 | EmailService | `app/services/EmailService.php:15` | ✅ `PDO` |
| 2 | NfeAuditService | `app/services/NfeAuditService.php:35` | ✅ `PDO` |
| 3 | NfeBackupService | `app/services/NfeBackupService.php:28` | ✅ `PDO` |
| 4 | NfeSpedFiscalService | `app/services/NfeSpedFiscalService.php:31` | ✅ `PDO` |
| 5 | WorkflowEngine | `app/services/WorkflowEngine.php:13` | ✅ `PDO` |
| 6 | DemandPredictionService | `app/services/DemandPredictionService.php:16` | ✅ `PDO` |
| 7 | PortalAdminService | `app/services/PortalAdminService.php:23` | ✅ `PDO` |
| 8 | NfeSintegraService | `app/services/NfeSintegraService.php:34` | ✅ `PDO` |
| 9 | NfeFiscalReportService | `app/services/NfeFiscalReportService.php:19` | ✅ `PDO` |
| 10 | CatalogCartService | `app/services/CatalogCartService.php:23` | ✅ `PDO` |
| 11 | CatalogQuoteService | `app/services/CatalogQuoteService.php:24` | ✅ `PDO` |
| 12 | FinancialAuditService | `app/services/FinancialAuditService.php:36` | ✅ `PDO` |
| 13+ | ~33 outros NfeServices | `app/services/Nfe*.php` | ✅ `PDO` |

**Avaliação:** 🟢 Todos type-hinted. Auto-wiring trivial para um container PSR-11.

### 1.3 Subgrupo S2 — PDO + Models (~20 services)

Padrão:
```php
public function __construct(PDO $db, Model $model, ...) {
    $this->db = $db;
    $this->model = $model;
}
```

| # | Service | Construtor | Dependências |
|---|---------|-----------|--------------|
| 1 | AuthService | `(PDO $db, User $userModel, LoginAttempt $loginAttempt, Logger $logger)` | PDO + 3 models |
| 2 | CommissionService | `(PDO $db, CommissionEngine $engine, Commission $model)` | PDO + 1 service + 1 model |
| 3 | CustomerImportService | `(PDO $db, Customer $customerModel, ImportBatch $importBatchModel, Logger $logger)` | PDO + 3 models |
| 4 | FinancialImportService | `(PDO $db, Financial $financial)` | PDO + 1 model |
| 5 | FinancialReportService | `(PDO $db, Financial $financial, Installment $installment)` | PDO + 2 models |
| 6 | InstallmentService | `(PDO $db, Installment $installment, TransactionService $transactionService)` | PDO + 1 model + 1 service |
| 7 | OrderItemService | `(PDO $db, Order $orderModel)` | PDO + 1 model |
| 8 | PipelineDetailService | `(PDO $db, Pipeline $pipelineModel, Stock $stockModel)` | PDO + 2 models |
| 9 | PipelineService | `(PDO $db, Pipeline $pipelineModel, Stock $stockModel)` | PDO + 2 models |
| 10 | ProductImportService | `(PDO $db, Product $productModel, Category $categoryModel, Subcategory $subcategoryModel, Logger $logger)` | PDO + 4 models |
| 11 | StockMovementService | `(PDO $db, Stock $stockModel, Logger $logger)` | PDO + 2 models |
| 12 | TransactionService | `(PDO $db, Financial $financial)` | PDO + 1 model |
| 13 | CategoryService | `(PDO $db, CategoryGrade $categoryGradeModel, ProductionSector $sectorModel)` | PDO + 2 models |
| 14 | SettingsService | `(PDO $db, CompanySettings $companySettings)` | PDO + 1 model |
| 15 | PortalAuthService | `(PDO $db, PortalAccess $portalAccess, Logger $logger)` | PDO + 2 models |
| 16 | PipelineAlertService | `(PDO $db, Pipeline $pipelineModel)` | PDO + 1 model |

**Avaliação:** 🟢 Auto-wiring possível — container pode resolver Models recursivamente (todos aceitam PDO).

### 1.4 Subgrupo S3 — Model Only (sem PDO direto)

| # | Service | Construtor | Dependências |
|---|---------|-----------|--------------|
| 1 | CustomerExportService | `(Customer $customerModel, Logger $logger)` | 2 models |
| 2 | PortalCartService | `(PortalAccess $portalAccess)` | 1 model |
| 3 | PortalAvatarService | `(PortalAccess $portalAccess, Logger $logger)` | 2 models |
| 4 | ProductGradeService | `(ProductGrade $gradeModel)` | 1 model |

**Avaliação:** 🟢 Sem PDO — container resolve via Models que por sua vez recebem PDO.

### 1.5 Subgrupo S4 — Service-to-Service

| # | Service | Depende de |
|---|---------|-----------|
| 1 | CommissionService | CommissionEngine (service) |
| 2 | InstallmentService | TransactionService (service) |
| 3 | MarketplaceConnector | Multi-service |

**Avaliação:** 🟢 Sem circularidade. Cadeia linear de deps.

### 1.6 Services com Deps Não-Container (Configuração Runtime)

| # | Service | Construtor | Deps Especiais |
|---|---------|-----------|----------------|
| 1 | ReportPdfService | `(ReportModel, NfeReportModel, array $company, string $responsibleUser)` | `array` + `string` (runtime) |
| 2 | ReportExcelService | `(ReportModel, NfeReportModel, array $company, string $responsibleUser)` | `array` + `string` (runtime) |

**Avaliação:** ⚠️ Estes services precisam de factory no container (não auto-wirable puramente).

---

## 2. Models — Inventário Completo

### 2.1 Resumo

Todos os 59 models aceitam PDO/DB no construtor. São 100% injetáveis.

| Type-Hint | Qtd | % |
|-----------|-----|---|
| `PDO` tipado | 17 | 29% |
| `$db` sem tipo | 42 | 71% |
| **Total** | **59** | **100%** |

### 2.2 Models com Type-Hint PDO (17)

```php
public function __construct(PDO $db) { $this->db = $db; }
```

| # | Model | Arquivo |
|---|-------|---------|
| 1 | Attachment | `app/models/Attachment.php:11` |
| 2 | AuditLog | `app/models/AuditLog.php:11` |
| 3 | CalendarEvent | `app/models/CalendarEvent.php:11` |
| 4 | Commission | `app/models/Commission.php:17` |
| 5 | EmailCampaign | `app/models/EmailCampaign.php:11` |
| 6 | Installment | `app/models/Installment.php:31` |
| 7 | Notification | `app/models/Notification.php:15` |
| 8 | Permission | `app/models/Permission.php:13` |
| 9 | PurchaseOrder | `app/models/PurchaseOrder.php:11` |
| 10 | QualityChecklist | `app/models/QualityChecklist.php:11` |
| 11 | Quote | `app/models/Quote.php:13` |
| 12 | RecurringTransaction | `app/models/RecurringTransaction.php:20` |
| 13 | ReportTemplate | `app/models/ReportTemplate.php:11` |
| 14 | Supplier | `app/models/Supplier.php:13` |
| 15 | WorkflowRule | `app/models/WorkflowRule.php:11` |

**Status:** 🟢 Container pode auto-wire diretamente.

### 2.3 Models sem Type-Hint (42)

```php
public function __construct($db) { $this->db = $db; }
```

| # | Model | Arquivo |
|---|-------|---------|
| 1 | CatalogLink | `app/models/CatalogLink.php:26` |
| 2 | Category | `app/models/Category.php:26` |
| 3 | CategoryGrade | `app/models/CategoryGrade.php:19` |
| 4 | CompanySettings | `app/models/CompanySettings.php:24` |
| 5 | Customer | `app/models/Customer.php:29` |
| 6 | CustomerContact | `app/models/CustomerContact.php:27` |
| 7 | DashboardWidget | `app/models/DashboardWidget.php:85` |
| 8 | Financial | `app/models/Financial.php:30` |
| 9 | FinancialReport | `app/models/FinancialReport.php:15` |
| 10 | FinancialSchema | `app/models/FinancialSchema.php:15` |
| 11 | IbptaxModel | `app/models/IbptaxModel.php:23` |
| 12 | ImportBatch | `app/models/ImportBatch.php:17` |
| 13 | ImportMappingProfile | `app/models/ImportMappingProfile.php:15` |
| 14 | LoginAttempt | `app/models/LoginAttempt.php:33` |
| 15 | Logger | `app/models/Logger.php:10` |
| 16 | NfeAuditLog | `app/models/NfeAuditLog.php:17` |
| 17 | NfeCredential | `app/models/NfeCredential.php:27` |
| 18 | NfeDocument | `app/models/NfeDocument.php:24` |
| 19 | NfeLog | `app/models/NfeLog.php:19` |
| 20 | NfeQueue | `app/models/NfeQueue.php:17` |
| 21 | NfeReceivedDocument | `app/models/NfeReceivedDocument.php:21` |
| 22 | NfeReportModel | `app/models/NfeReportModel.php:25` |
| 23 | NfeWebhook | `app/models/NfeWebhook.php:18` |
| 24 | Order | `app/models/Order.php:23` |
| 25 | OrderItemLog | `app/models/OrderItemLog.php:26` |
| 26 | OrderPreparation | `app/models/OrderPreparation.php:17` |
| 27 | PaymentGateway | `app/models/PaymentGateway.php:23` |
| 28 | Pipeline | `app/models/Pipeline.php:25` |
| 29 | PortalAccess | `app/models/PortalAccess.php:31` |
| 30 | PortalMessage | `app/models/PortalMessage.php:25` |
| 31 | PreparationStep | `app/models/PreparationStep.php:17` |
| 32 | PriceTable | `app/models/PriceTable.php:15` |
| 33 | Product | `app/models/Product.php:29` |
| 34 | ProductGrade | `app/models/ProductGrade.php:15` |
| 35 | ProductionSector | `app/models/ProductionSector.php:12` |
| 36 | ReportModel | `app/models/ReportModel.php:21` |
| 37 | SiteBuilder | `app/models/SiteBuilder.php:14` |
| 38 | Stock | `app/models/Stock.php:17` |
| 39 | Subcategory | `app/models/Subcategory.php:16` |
| 40 | Transaction | `app/models/Transaction.php:16` |
| 41 | User | `app/models/User.php:44` |
| 42 | UserGroup | `app/models/UserGroup.php:53` |

**Status:** ⚠️ Funcionais, mas precisam de `PDO` type-hint para auto-wiring.

### 2.4 Recomendação para Models

Para habilitar auto-wiring PSR-11 completo, os 42 models sem type-hint precisam de uma alteração mínima:

```php
// ANTES (42 models)
public function __construct($db) {

// DEPOIS
public function __construct(\PDO $db) {
```

**Impacto:** Nenhuma quebra de funcionalidade (todos já recebem PDO).
**Benefício:** Container pode resolver automaticamente via Reflection.

---

## 3. Análise de Dependência Circular

### Grafo de Dependências Verificado

```
Controller → Service → Model → (nenhuma dependência inversa)
Controller → Model → (nenhuma dependência inversa)
Service → Service → (linear, sem ciclos)
```

**Cadeias verificadas:**

| Cadeia | Resultado |
|--------|----------|
| InstallmentService → TransactionService → Financial (model) | ✅ Linear |
| CommissionService → CommissionEngine → Commission (model) | ✅ Linear |
| PipelineService → Pipeline + Stock (models) | ✅ Paralela |
| PortalController → 7 PortalServices → PortalAccess (model) | ✅ Fan-out |

**Resultado: 0 dependências circulares** 🟢

---

## 4. Métricas de Prontidão para Container

| Camada | Total | Container-Ready | Auto-Wirable | Precisa Factory |
|--------|-------|----------------|--------------|-----------------|
| Controllers | 42 | 1 (2%) | 1 (2%) | 0 |
| Services | 79 | 79 (100%) | 77 (97%) | 2 (3%) |
| Models | 59 | 59 (100%) | 17 (29%) | 0 |
| **Total** | **180** | **139 (77%)** | **95 (53%)** | **2 (1%)** |

**Nota:** "Auto-Wirable" = container pode resolver via Reflection sem config manual.
**Nota:** Os 42 models sem type-hint funcionam mas precisam de casting/alias no container.
