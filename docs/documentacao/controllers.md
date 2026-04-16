# Controllers (Controladores)

> Controladores HTTP: recebem requisições, delegam para models/services, retornam views.

**Total de arquivos:** 75

---

## Índice

- [AchievementController](#achievementcontroller) — `app/controllers/AchievementController.php`
- [AiAssistantController](#aiassistantcontroller) — `app/controllers/AiAssistantController.php`
- [ApiController](#apicontroller) — `app/controllers/ApiController.php`
- [AttachmentController](#attachmentcontroller) — `app/controllers/AttachmentController.php`
- [AuditController](#auditcontroller) — `app/controllers/AuditController.php`
- [BaseController](#basecontroller) — `app/controllers/BaseController.php`
- [BiController](#bicontroller) — `app/controllers/BiController.php`
- [BranchController](#branchcontroller) — `app/controllers/BranchController.php`
- [CalendarController](#calendarcontroller) — `app/controllers/CalendarController.php`
- [CatalogController](#catalogcontroller) — `app/controllers/CatalogController.php`
- [CategoryController](#categorycontroller) — `app/controllers/CategoryController.php`
- [CheckoutController](#checkoutcontroller) — `app/controllers/CheckoutController.php`
- [CommissionController](#commissioncontroller) — `app/controllers/CommissionController.php`
- [CustomReportController](#customreportcontroller) — `app/controllers/CustomReportController.php`
- [CustomerController](#customercontroller) — `app/controllers/CustomerController.php`
- [CustomerExportController](#customerexportcontroller) — `app/controllers/CustomerExportController.php`
- [CustomerImportController](#customerimportcontroller) — `app/controllers/CustomerImportController.php`
- [DashboardController](#dashboardcontroller) — `app/controllers/DashboardController.php`
- [DashboardWidgetController](#dashboardwidgetcontroller) — `app/controllers/DashboardWidgetController.php`
- [EmailMarketingController](#emailmarketingcontroller) — `app/controllers/EmailMarketingController.php`
- [EmailTrackingController](#emailtrackingcontroller) — `app/controllers/EmailTrackingController.php`
- [EquipmentController](#equipmentcontroller) — `app/controllers/EquipmentController.php`
- [EsgController](#esgcontroller) — `app/controllers/EsgController.php`
- [FileController](#filecontroller) — `app/controllers/FileController.php`
- [FinancialController](#financialcontroller) — `app/controllers/FinancialController.php`
- [FinancialImportController](#financialimportcontroller) — `app/controllers/FinancialImportController.php`
- [HealthController](#healthcontroller) — `app/controllers/HealthController.php`
- [HomeController](#homecontroller) — `app/controllers/HomeController.php`
- [InstallmentController](#installmentcontroller) — `app/controllers/InstallmentController.php`
- [LojaController](#lojacontroller) — `app/controllers/LojaController.php`
- [AdminController](#admincontroller) — `app/controllers/Master/AdminController.php`
- [BackupController](#backupcontroller) — `app/controllers/Master/BackupController.php`
- [ClientController](#clientcontroller) — `app/controllers/Master/ClientController.php`
- [DashboardController](#dashboardcontroller) — `app/controllers/Master/DashboardController.php`
- [DeployController](#deploycontroller) — `app/controllers/Master/DeployController.php`
- [GitController](#gitcontroller) — `app/controllers/Master/GitController.php`
- [](#) — `app/controllers/Master/GitController.php`
- [HealthCheckController](#healthcheckcontroller) — `app/controllers/Master/HealthCheckController.php`
- [LogController](#logcontroller) — `app/controllers/Master/LogController.php`
- [MasterBaseController](#masterbasecontroller) — `app/controllers/Master/MasterBaseController.php`
- [MigrationController](#migrationcontroller) — `app/controllers/Master/MigrationController.php`
- [PlanController](#plancontroller) — `app/controllers/Master/PlanController.php`
- [NfeCredentialController](#nfecredentialcontroller) — `app/controllers/NfeCredentialController.php`
- [NfeDocumentController](#nfedocumentcontroller) — `app/controllers/NfeDocumentController.php`
- [NotificationController](#notificationcontroller) — `app/controllers/NotificationController.php`
- [OrderController](#ordercontroller) — `app/controllers/OrderController.php`
- [PaymentGatewayController](#paymentgatewaycontroller) — `app/controllers/PaymentGatewayController.php`
- [PipelineController](#pipelinecontroller) — `app/controllers/PipelineController.php`
- [PipelinePaymentController](#pipelinepaymentcontroller) — `app/controllers/PipelinePaymentController.php`
- [PipelineProductionController](#pipelineproductioncontroller) — `app/controllers/PipelineProductionController.php`
- [PortalAdminController](#portaladmincontroller) — `app/controllers/PortalAdminController.php`
- [PortalController](#portalcontroller) — `app/controllers/PortalController.php`
- [ProductController](#productcontroller) — `app/controllers/ProductController.php`
- [ProductGradeController](#productgradecontroller) — `app/controllers/ProductGradeController.php`
- [ProductImportController](#productimportcontroller) — `app/controllers/ProductImportController.php`
- [ProductionCostController](#productioncostcontroller) — `app/controllers/ProductionCostController.php`
- [QualityController](#qualitycontroller) — `app/controllers/QualityController.php`
- [QuoteController](#quotecontroller) — `app/controllers/QuoteController.php`
- [RecurringTransactionController](#recurringtransactioncontroller) — `app/controllers/RecurringTransactionController.php`
- [ReportController](#reportcontroller) — `app/controllers/ReportController.php`
- [SearchController](#searchcontroller) — `app/controllers/SearchController.php`
- [SectorController](#sectorcontroller) — `app/controllers/SectorController.php`
- [SettingsController](#settingscontroller) — `app/controllers/SettingsController.php`
- [ShipmentController](#shipmentcontroller) — `app/controllers/ShipmentController.php`
- [SiteBuilderController](#sitebuildercontroller) — `app/controllers/SiteBuilderController.php`
- [StockController](#stockcontroller) — `app/controllers/StockController.php`
- [SupplierController](#suppliercontroller) — `app/controllers/SupplierController.php`
- [SupplyController](#supplycontroller) — `app/controllers/SupplyController.php`
- [SupplyStockController](#supplystockcontroller) — `app/controllers/SupplyStockController.php`
- [TicketController](#ticketcontroller) — `app/controllers/TicketController.php`
- [TransactionController](#transactioncontroller) — `app/controllers/TransactionController.php`
- [UserController](#usercontroller) — `app/controllers/UserController.php`
- [WalkthroughController](#walkthroughcontroller) — `app/controllers/WalkthroughController.php`
- [WebhookController](#webhookcontroller) — `app/controllers/WebhookController.php`
- [WhatsAppController](#whatsappcontroller) — `app/controllers/WhatsAppController.php`
- [WorkflowController](#workflowcontroller) — `app/controllers/WorkflowController.php`

---

## AchievementController

**Tipo:** Class  
**Arquivo:** `app/controllers/AchievementController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

Class AchievementController.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$achievementModel` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor da classe AchievementController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |

---

##### `index()`

Exibe a página de listagem.

---

##### `create()`

Cria um novo registro no banco de dados.

---

##### `store()`

Processa e armazena um novo registro.

---

##### `edit()`

Exibe o formulário de edição.

---

##### `update()`

Atualiza um registro existente.

---

##### `delete()`

Remove um registro pelo ID.

---

##### `leaderboard()`

Leaderboard.

---

##### `award()`

Award.

---

## AiAssistantController

**Tipo:** Class  
**Arquivo:** `app/controllers/AiAssistantController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

Class AiAssistantController.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$ai` | Não |

### Métodos

#### Métodos Public

##### `__construct(?\PDO $db = null)`

Construtor da classe AiAssistantController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO|null` | Conexão PDO com o banco de dados |

---

##### `index(): void`

Chat widget page (standalone or embedded).

---

##### `send(): void`

AJAX: Send a message to the AI.

---

##### `clearHistory(): void`

AJAX: Clear conversation history.

---

## ApiController

**Tipo:** Class  
**Arquivo:** `app/controllers/ApiController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

ApiController — Gera tokens JWT para o frontend consumir a API Node.js.

### Métodos

#### Métodos Public

##### `token(): void`

GET ?page=api&action=token

---

## AttachmentController

**Tipo:** Class  
**Arquivo:** `app/controllers/AttachmentController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

Class AttachmentController.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$model` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db, Attachment $model)`

Construtor da classe AttachmentController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |
| `$model` | `Attachment` | Model |

---

##### `index()`

Exibe a página de listagem.

---

##### `upload()`

Processa upload de arquivo.

---

##### `download()`

Gera download de arquivo.

---

##### `delete()`

Remove um registro pelo ID.

---

##### `listByEntity()`

Lista registros filtrados por critério.

---

##### `searchEntities()`

Search entities.

---

## AuditController

**Tipo:** Class  
**Arquivo:** `app/controllers/AuditController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

Class AuditController.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$model` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db, AuditLog $model)`

Construtor da classe AuditController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |
| `$model` | `AuditLog` | Model |

---

##### `index()`

Exibe a página de listagem.

---

##### `detail()`

Detail.

---

##### `exportCsv()`

Exporta dados.

---

## BaseController

**Tipo:** Class  
**Arquivo:** `app/controllers/BaseController.php`  
**Namespace:** `Akti\Controllers`  

### Métodos

#### Métodos Public

##### `__construct(?\PDO $db = null)`

Construtor da classe BaseController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO|null` | Conexão PDO com o banco de dados |

---

#### Métodos Protected

##### `json(mixed $data, int $status = 200): void`

Retorna resposta JSON e encerra a execução.

---

##### `redirect(string $url): void`

Redireciona para uma URL e encerra a execução.

---

##### `render(string $view, array $data = []): void`

Renderiza views com header e footer.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$view` | `string` | Caminho relativo dentro de app/views/ (sem extensão) |
| `$data` | `array` | Variáveis extraídas para a view |

---

##### `requireAuth(): void`

Verifica se o usuário está autenticado.

---

##### `requireAdmin(): void`

Verifica se o usuário logado é admin.

---

##### `getTenantId(): int`

Retorna o tenant_id da sessão.

---

##### `isAjax(): bool`

Detecta se a requisição é AJAX/fetch.

---

## BiController

**Tipo:** Class  
**Arquivo:** `app/controllers/BiController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

Class BiController.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$biService` | Não |

### Métodos

#### Métodos Public

##### `__construct(?\PDO $db = null)`

Construtor da classe BiController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO|null` | Conexão PDO com o banco de dados |

---

##### `index(): void`

Exibe a página de listagem.

**Retorno:** `void — */`

---

##### `drillDown(): void`

Drill down.

**Retorno:** `void — */`

---

##### `exportPdf(): void`

Exporta dados.

**Retorno:** `void — */`

---

## BranchController

**Tipo:** Class  
**Arquivo:** `app/controllers/BranchController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

Class BranchController.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$branchModel` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor da classe BranchController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |

---

##### `index()`

Exibe a página de listagem.

---

##### `create()`

Cria um novo registro no banco de dados.

---

##### `store()`

Processa e armazena um novo registro.

---

##### `edit()`

Exibe o formulário de edição.

---

##### `update()`

Atualiza um registro existente.

---

##### `delete()`

Remove um registro pelo ID.

---

## CalendarController

**Tipo:** Class  
**Arquivo:** `app/controllers/CalendarController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

Class CalendarController.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$model` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db, CalendarEvent $model)`

Construtor da classe CalendarController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |
| `$model` | `CalendarEvent` | Model |

---

##### `index()`

Exibe a página de listagem.

---

##### `events()`

Events.

---

##### `store()`

Processa e armazena um novo registro.

---

##### `update()`

Atualiza um registro existente.

---

##### `delete()`

Remove um registro pelo ID.

---

##### `sync()`

Sincroniza dados.

---

## CatalogController

**Tipo:** Class  
**Arquivo:** `app/controllers/CatalogController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

Controller: CatalogController

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$cartService` | Não |
| private | `$quoteService` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db, CatalogCartService $cartService, CatalogQuoteService $quoteService)`

Construtor da classe CatalogController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |
| `$cartService` | `CatalogCartService` | Cart service |
| `$quoteService` | `CatalogQuoteService` | Quote service |

---

##### `index()`

Página pública do catálogo (não precisa de login)

---

##### `generate()`

API: Gerar link de catálogo (chamado via AJAX do pipeline)

---

### Funções auxiliares do arquivo

#### `deactivate()`

---

#### `getLink()`

---

#### `addToCart()`

---

#### `removeFromCart()`

---

#### `updateCartItem()`

---

#### `getCart()`

---

#### `confirmQuote()`

---

#### `revokeQuote()`

---

#### `getProducts()`

---

## CategoryController

**Tipo:** Class  
**Arquivo:** `app/controllers/CategoryController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

Class CategoryController.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$categoryModel` | Não |
| private | `$subcategoryModel` | Não |
| private | `$sectorModel` | Não |
| private | `$gradeModel` | Não |
| private | `$categoryGradeModel` | Não |
| private | `$logger` | Não |
| private | `$categoryService` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db,
        Category $categoryModel,
        Subcategory $subcategoryModel,
        ProductionSector $sectorModel,
        ProductGrade $gradeModel,
        CategoryGrade $categoryGradeModel,
        Logger $logger,
        CategoryService $categoryService)`

Construtor da classe CategoryController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |
| `$categoryModel` | `Category` | Category model |
| `$subcategoryModel` | `Subcategory` | Subcategory model |
| `$sectorModel` | `ProductionSector` | Sector model |
| `$gradeModel` | `ProductGrade` | Grade model |
| `$categoryGradeModel` | `CategoryGrade` | Category grade model |
| `$logger` | `Logger` | Logger |
| `$categoryService` | `CategoryService` | Category service |

---

##### `index()`

Exibe a página de listagem.

---

##### `store()`

Processa e armazena um novo registro.

---

##### `update()`

Atualiza um registro existente.

---

##### `delete()`

Remove um registro pelo ID.

---

##### `storeSub()`

Store sub.

---

##### `updateSub()`

Update sub.

---

##### `deleteSub()`

Delete sub.

---

##### `getInheritedGradesAjax()`

Obtém dados específicos.

---

##### `toggleCategoryCombinationAjax()`

Alterna estado de propriedade.

---

##### `toggleSubcategoryCombinationAjax()`

Alterna estado de propriedade.

---

##### `getProductsForExport()`

Obtém dados específicos.

---

##### `exportToProducts()`

Exporta dados.

---

##### `getInheritedSectorsAjax()`

Obtém dados específicos.

---

## CheckoutController

**Tipo:** Class  
**Arquivo:** `app/controllers/CheckoutController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

Class CheckoutController.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$tokenModel` | Não |
| private | `$checkoutService` | Não |

### Métodos

#### Métodos Public

##### `__construct(?\PDO $db = null)`

Construtor da classe CheckoutController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO|null` | Conexão PDO com o banco de dados |

---

##### `show(): void`

GET: Exibe página de checkout (pública).

---

##### `processPayment(): void`

POST (AJAX): Processa pagamento.

---

##### `tokenizeCard(): void`

POST (AJAX): Proxy de tokenização de cartão (evita CORS em ambientes HTTP).

---

##### `checkStatus(): void`

GET (AJAX): Verifica status de pagamento (polling).

---

##### `confirmation(): void`

GET: Página de confirmação de pagamento (3 estados).

---

#### Métodos Private

##### `validateTokenFormat(string $token): ?string`

Valida formato do token (64 chars hex).

---

##### `renderExpired(): void`

Renderiza página de token expirado/inválido.

---

##### `redirectToConfirmation(string $token, string $status, string $externalId = ''): void`

Redireciona para página de confirmação.

---

##### `setSecurityHeaders(string $gatewaySlug): void`

Define headers de segurança para a página de checkout.

---

### Funções auxiliares do arquivo

#### `getCustomerDataForCheckout(array $tokenRow)`

---

#### `detectMissingFields(array $customerData, array $methods, string $gatewaySlug)`

---

#### `updateCustomerData()`

---

## CommissionController

**Tipo:** Class  
**Arquivo:** `app/controllers/CommissionController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

CommissionController — Controller do Módulo de Comissões

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$service` | Não |
| private | `$model` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db, Commission $model, CommissionEngine $engine, CommissionService $service)`

Construtor da classe CommissionController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |
| `$model` | `Commission` | Model |
| `$engine` | `CommissionEngine` | Engine |
| `$service` | `CommissionService` | Service |

---

##### `index()`

Exibe a página de listagem.

---

##### `formas()`

Formas.

---

##### `storeForma()`

Store forma.

---

##### `updateForma()`

Update forma.

---

##### `deleteForma()`

Delete forma.

---

##### `getFaixas()`

Obtém dados específicos.

---

##### `grupos()`

Grupos.

---

##### `linkGrupo()`

Link grupo.

---

##### `unlinkGrupo()`

Unlink grupo.

---

##### `usuarios()`

Usuarios.

---

##### `linkUsuario()`

Link usuario.

---

##### `unlinkUsuario()`

Unlink usuario.

---

##### `produtos()`

Produtos.

---

##### `saveProdutoRegra()`

Salva dados.

---

##### `deleteProdutoRegra()`

Delete produto regra.

---

##### `simulador()`

Simulador.

---

##### `simularCalculo()`

Simular calculo.

---

##### `calcular()`

Calcula valor.

---

##### `historico()`

Historico.

---

##### `getHistoricoPaginated()`

Obtém dados específicos.

---

##### `aprovar()`

Aprovar.

---

##### `pagar()`

Pagar.

---

##### `cancelar()`

Cancela operação.

---

##### `aprovarLote()`

Aprovar lote.

---

##### `pagarLote()`

Pagar lote.

---

##### `configuracoes()`

Configuracoes.

---

##### `saveConfig()`

Salva dados.

---

##### `getVendedoresPendentes()`

Retorna lista de vendedores com comissões pendentes (JSON).

---

##### `getComissoesVendedor()`

Retorna comissões pendentes de um vendedor (JSON).

---

#### Métodos Private

##### `parseFaixasFromPost(): array`

Interpreta dados.

**Retorno:** `array — */`

---

## CustomReportController

**Tipo:** Class  
**Arquivo:** `app/controllers/CustomReportController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

Class CustomReportController.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$model` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db, ReportTemplate $model)`

Construtor da classe CustomReportController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |
| `$model` | `ReportTemplate` | Model |

---

##### `index()`

Exibe a página de listagem.

---

##### `create()`

Cria um novo registro no banco de dados.

---

##### `store()`

Processa e armazena um novo registro.

---

##### `edit()`

Exibe o formulário de edição.

---

##### `update()`

Atualiza um registro existente.

---

##### `delete()`

Remove um registro pelo ID.

---

##### `run()`

Executa um processo.

---

##### `getEntities()`

Obtém dados específicos.

---

## CustomerController

**Tipo:** Class  
**Arquivo:** `app/controllers/CustomerController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

Controller: CustomerController

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$customerModel` | Não |
| private | `$contactModel` | Não |
| private | `$importBatchModel` | Não |
| private | `$mappingProfileModel` | Não |
| private | `$logger` | Não |
| private | `$importService` | Não |
| private | `$exportService` | Não |
| private | `$formService` | Não |
| private | `$externalApiService` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db,
        Customer $customerModel,
        CustomerContact $contactModel,
        ImportBatch $importBatchModel,
        ImportMappingProfile $mappingProfileModel,
        Logger $logger,
        CustomerImportService $importService,
        CustomerExportService $exportService,
        CustomerFormService $formService,
        ExternalApiService $externalApiService)`

Construtor da classe CustomerController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |
| `$customerModel` | `Customer` | Customer model |
| `$contactModel` | `CustomerContact` | Contact model |
| `$importBatchModel` | `ImportBatch` | Import batch model |
| `$mappingProfileModel` | `ImportMappingProfile` | Mapping profile model |
| `$logger` | `Logger` | Logger |
| `$importService` | `CustomerImportService` | Import service |
| `$exportService` | `CustomerExportService` | Export service |
| `$formService` | `CustomerFormService` | Form service |
| `$externalApiService` | `ExternalApiService` | External api service |

---

##### `index()`

Exibe a página de listagem.

---

##### `create()`

Cria um novo registro no banco de dados.

---

##### `store()`

Processa e armazena um novo registro.

---

### Funções auxiliares do arquivo

#### `edit()`

---

#### `update()`

---

#### `delete()`

---

#### `restore()`

---

#### `updateStatus()`

---

#### `view()`

---

#### `checkDuplicate()`

---

#### `searchCep()`

---

#### `searchCnpj()`

---

#### `export()`

---

#### `getTags()`

---

#### `getOrderHistory()`

---

#### `bulkAction()`

---

#### `getContacts()`

---

#### `saveContact()`

---

#### `deleteContact()`

---

#### `getCustomersList()`

---

#### `searchSelect2()`

---

#### `searchAjax()`

---

#### `parseImportFile()`

---

#### `importCustomersMapped()`

---

#### `getImportProgress()`

---

#### `undoImport()`

---

#### `getImportHistory()`

---

#### `getImportDetails()`

---

#### `getMappingProfiles()`

---

#### `saveMappingProfile()`

---

#### `deleteMappingProfile()`

---

#### `downloadImportTemplate()`

---

#### `captureFormData()`

---

#### `validateCustomerData(array $data, ?int $excludeId = null)`

---

#### `captureFilters()`

---

#### `getRecentOrders(int $customerId, int $limit = 5)`

---

#### `handlePhotoUpload()`

---

#### `jsonResponse(array $data)`

---

#### `parseCsvFile($filePath)`

---

#### `parseExcelFile($filePath)`

---

#### `normalizeDateForImport(string $dateStr)`

---

#### `normalizeUfForImport(string $state)`

---

## CustomerExportController

**Tipo:** Class  
**Arquivo:** `app/controllers/CustomerExportController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

CustomerExportController — Exportação de clientes (CSV).

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$exportService` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db, CustomerExportService $exportService)`

Construtor da classe CustomerExportController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |
| `$exportService` | `CustomerExportService` | Export service |

---

##### `export()`

Exporta dados.

---

#### Métodos Private

##### `captureFilters(): array`

Capture filters.

**Retorno:** `array — */`

---

## CustomerImportController

**Tipo:** Class  
**Arquivo:** `app/controllers/CustomerImportController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

Class CustomerImportController.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$importService` | Não |
| private | `$importBatchModel` | Não |
| private | `$mappingProfileModel` | Não |
| private | `$customerModel` | Não |
| private | `$logger` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor da classe CustomerImportController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |

---

##### `parseImportFile()`

Interpreta dados.

---

##### `importCustomersMapped()`

Importa dados.

---

##### `getImportProgress()`

Obtém dados específicos.

---

##### `undoImport()`

Undo import.

---

### Funções auxiliares do arquivo

#### `getImportHistory()`

---

#### `getImportDetails()`

---

#### `getMappingProfiles()`

---

#### `saveMappingProfile()`

---

#### `deleteMappingProfile()`

---

#### `downloadImportTemplate()`

---

## DashboardController

**Tipo:** Class  
**Arquivo:** `app/controllers/DashboardController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

Class DashboardController.

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor da classe DashboardController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |

---

##### `index()`

Exibe a página de listagem.

---

##### `realtime()`

FEAT-016: Dashboard em tempo real com SSE.

---

##### `realtimeData()`

FEAT-016: Endpoint JSON para dados do dashboard (polling/SSE).

---

## DashboardWidgetController

**Tipo:** Class  
**Arquivo:** `app/controllers/DashboardWidgetController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

DashboardWidgetController

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

---

##### `load(): void`

Carrega um widget individual via AJAX.

---

##### `config(): void`

Retorna a configuração de widgets do grupo do usuário (JSON).

---

#### Métodos Private

##### `requireAuth(): void`

Require auth.

**Retorno:** `void — */`

---

## EmailMarketingController

**Tipo:** Class  
**Arquivo:** `app/controllers/EmailMarketingController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

Class EmailMarketingController.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$model` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db, EmailCampaign $model)`

Construtor da classe EmailMarketingController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |
| `$model` | `EmailCampaign` | Model |

---

##### `index()`

Exibe a página de listagem.

---

##### `create()`

Cria um novo registro no banco de dados.

---

##### `store()`

Processa e armazena um novo registro.

---

##### `edit()`

Exibe o formulário de edição.

---

##### `update()`

Atualiza um registro existente.

---

##### `delete()`

Remove um registro pelo ID.

---

##### `templates()`

Templates.

---

##### `createTemplate()`

Create template.

---

##### `storeTemplate()`

Store template.

---

##### `editTemplate()`

Edit template.

---

##### `updateTemplate()`

Update template.

---

##### `deleteTemplate()`

Delete template.

---

##### `getTemplateJson()`

Obtém dados específicos.

---

##### `searchCustomers()`

Search customers.

---

### Funções auxiliares do arquivo

#### `previewTemplate()`

---

#### `previewCampaign()`

---

#### `renderPreview(string $bodyHtml, string $subject)`

---

#### `sendCampaign()`

---

#### `sendTest()`

---

## EmailTrackingController

**Tipo:** Class  
**Arquivo:** `app/controllers/EmailTrackingController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

Class EmailTrackingController.

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor da classe EmailTrackingController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |

---

##### `open()`

Tracking pixel — records email open

---

##### `click()`

Click tracking — records link click and redirects

---

##### `static generateHash(int $logId): string`

Generate a tracking hash for a log entry (HMAC)

---

#### Métodos Private

##### `verifyHash(int $logId, string $hash): bool`

Verify the tracking hash

---

##### `updateCampaignOpenCount(int $logId): void`

Update campaign total_opened from email_logs

---

##### `updateCampaignClickCount(int $logId): void`

Update campaign total_clicked from email_logs

---

## EquipmentController

**Tipo:** Class  
**Arquivo:** `app/controllers/EquipmentController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

Class EquipmentController.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$equipmentModel` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor da classe EquipmentController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |

---

##### `index()`

Exibe a página de listagem.

---

##### `create()`

Cria um novo registro no banco de dados.

---

##### `store()`

Processa e armazena um novo registro.

---

##### `edit()`

Exibe o formulário de edição.

---

##### `update()`

Atualiza um registro existente.

---

##### `delete()`

Remove um registro pelo ID.

---

##### `schedules()`

Agenda tarefa ou evento.

---

##### `storeSchedule()`

Store schedule.

---

##### `storeLog()`

Store log.

---

##### `dashboard()`

Dashboard.

---

## EsgController

**Tipo:** Class  
**Arquivo:** `app/controllers/EsgController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

Class EsgController.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$esgModel` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor da classe EsgController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |

---

##### `index()`

Exibe a página de listagem.

---

##### `create()`

Cria um novo registro no banco de dados.

---

##### `store()`

Processa e armazena um novo registro.

---

##### `edit()`

Exibe o formulário de edição.

---

##### `update()`

Atualiza um registro existente.

---

##### `delete()`

Remove um registro pelo ID.

---

##### `addRecord()`

Add record.

---

##### `setTarget()`

Define valor específico.

---

##### `dashboard()`

Dashboard.

---

## FileController

**Tipo:** Class  
**Arquivo:** `app/controllers/FileController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

FileController — Endpoints HTTP para gestão de arquivos.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$fileManager` | Não |

### Métodos

#### Métodos Public

##### `__construct(?\PDO $db = null)`

Construtor da classe FileController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO|null` | Conexão PDO com o banco de dados |

---

##### `serve(): void`

Servir arquivo com cache headers.

---

##### `thumb(): void`

Gerar e servir thumbnail on-the-fly.

---

##### `download(): void`

Download de arquivo.

---

##### `upload(): void`

Upload genérico via AJAX.

---

#### Métodos Private

##### `isPathSafe(string $path): bool`

Validar se o path é seguro (sem path traversal).

---

## FinancialController

**Tipo:** Class  
**Arquivo:** `app/controllers/FinancialController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

FinancialController — Controller principal do módulo financeiro (SLIM).

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$financial` | Não |
| private | `$installmentModel` | Não |
| private | `$installmentService` | Não |
| private | `$reportService` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db,
        Financial $financial,
        Installment $installmentModel,
        InstallmentService $installmentService,
        FinancialReportService $reportService)`

Construtor da classe FinancialController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |
| `$financial` | `Financial` | Financial |
| `$installmentModel` | `Installment` | Installment model |
| `$installmentService` | `InstallmentService` | Installment service |
| `$reportService` | `FinancialReportService` | Report service |

---

##### `index()`

Dashboard com cards de resumo, gráficos e alertas.

---

##### `payments()`

Página unificada com sidebar: parcelas, transações, importação, nova transação.

---

##### `getSummaryJson()`

Retorna resumo financeiro do mês/ano em JSON.

---

##### `getDre()`

Retorna DRE em JSON para o período informado.

---

##### `getCashflow()`

Retorna projeção de fluxo de caixa em JSON.

---

##### `exportTransactionsCsv()`

Exporta transações em CSV (download direto).

---

##### `exportDreCsv()`

Exporta DRE em CSV (download direto).

---

##### `exportCashflowCsv()`

Exporta fluxo de caixa projetado em CSV (download direto).

---

##### `getAuditLog()`

Retorna log de auditoria financeira em JSON (paginado com filtros).

---

##### `exportAuditCsv()`

Exporta auditoria financeira em CSV.

---

##### `static getFinancialImportFields(): array`

Campos disponíveis para mapeamento de importação financeira.

---

## FinancialImportController

**Tipo:** Class  
**Arquivo:** `app/controllers/FinancialImportController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

FinancialImportController — Controller dedicado a importação financeira (OFX/CSV/Excel).

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$importService` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db, FinancialImportService $importService)`

Construtor da classe FinancialImportController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |
| `$importService` | `FinancialImportService` | Import service |

---

##### `static getFinancialImportFields(): array`

Campos disponíveis para mapeamento de importação financeira.

---

##### `parseFile()`

Interpreta dados.

---

##### `importCsv()`

Importa dados.

---

##### `importOfxSelected()`

Importa dados.

---

##### `importOfx()`

Importa dados.

---

## HealthController

**Tipo:** Class  
**Arquivo:** `app/controllers/HealthController.php`  
**Namespace:** `Akti\Controllers`  

HealthController

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |

### Métodos

#### Métodos Public

##### `__construct(?\PDO $db = null)`

Construtor da classe HealthController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO|null` | Conexão PDO com o banco de dados |

---

##### `ping(): void`

Ping simples — para uptime monitors (UptimeRobot, Pingdom, etc.)

---

##### `check(): void`

Health check completo — verifica todos os componentes.

---

#### Métodos Private

##### `checkDatabase(): array`

Verifica conectividade com o banco de dados.

---

##### `checkFilesystem(): array`

Verifica permissões de escrita no filesystem.

---

##### `checkLastBackup(): array`

Verifica se há um backup recente (últimas 48h).

---

##### `checkDiskSpace(): array`

Verifica espaço em disco disponível.

---

##### `checkExtensions(): array`

Verifica extensões PHP necessárias.

---

##### `getUptime(): string`

Retorna informação de uptime do processo.

---

## HomeController

**Tipo:** Class  
**Arquivo:** `app/controllers/HomeController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

Class HomeController.

### Métodos

#### Métodos Public

##### `index()`

Exibe a página de listagem.

---

## InstallmentController

**Tipo:** Class  
**Arquivo:** `app/controllers/InstallmentController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

InstallmentController — Controller dedicado a parcelas (order_installments).

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$installmentModel` | Não |
| private | `$installmentService` | Não |
| private | `$transactionService` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db,
        Installment $installmentModel,
        InstallmentService $installmentService,
        TransactionService $transactionService)`

Construtor da classe InstallmentController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |
| `$installmentModel` | `Installment` | Installment model |
| `$installmentService` | `InstallmentService` | Installment service |
| `$transactionService` | `TransactionService` | Transaction service |

---

##### `installments()`

Installments.

---

##### `generate()`

Gera conteúdo ou dados.

---

##### `pay()`

Pay.

---

##### `confirm()`

Confirm.

---

##### `cancel()`

Cancela operação.

---

##### `uploadAttachment()`

Processa upload de arquivo.

---

##### `removeAttachment()`

Remove attachment.

---

##### `merge()`

Mescla dados.

---

##### `split()`

Split.

---

##### `getPaginated()`

Obtém dados específicos.

---

##### `getJson()`

Obtém dados específicos.

---

#### Métodos Private

##### `sanitizeRedirect(?string $redirect, string $default = '?page=financial&action=payments'): string`

Valida e sanitiza URL de redirecionamento.

---

##### `jsonResponse(array $data): void`

Envia resposta JSON e encerra.

---

##### `handleAttachmentUpload(int $installmentId): ?string`

Faz upload de arquivo de comprovante e retorna o caminho.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$installmentId` | `int` | * @return string|null Caminho do arquivo ou null em caso de erro |

**Retorno:** `string|null — Caminho do arquivo ou null em caso de erro`

---

## LojaController

**Tipo:** Class  
**Arquivo:** `app/controllers/LojaController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

Controller público da Loja.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$siteBuilder` | Não |
| private | `$twig` | Não |
| private | `$tenantId` | Não |
| private | `$settings` | Não |
| private | `$shopName` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db, SiteBuilder $siteBuilder, TwigRenderer $twig, int $tenantId)`

Construtor da classe LojaController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |
| `$siteBuilder` | `SiteBuilder` | Site builder |
| `$twig` | `TwigRenderer` | Twig |
| `$tenantId` | `int` | ID do tenant |

---

##### `home(): void`

Home page da loja.

---

##### `collection(): void`

Catálogo de produtos.

---

##### `product(string $slug): void`

Página de produto individual.

---

##### `cart(): void`

Página do carrinho de compras.

---

##### `contact(): void`

Página de contato.

---

##### `profile(): void`

Página de perfil do cliente.

---

##### `addToCart(): void`

API: Adicionar produto ao carrinho (AJAX POST).

---

##### `removeFromCart(): void`

API: Remover produto do carrinho (AJAX POST).

---

##### `searchSuggestions(): void`

API: Sugestões de busca (AJAX GET).

---

#### Métodos Private

##### `buildContext(array $extra = []): array`

Monta o contexto base para todos os templates Twig.

---

##### `getFeaturedProducts(int $limit): array`

Busca produtos em destaque.

---

##### `calculateCartTotal(array $items): float`

Calcula o total do carrinho.

---

##### `notFound(): void`

Página 404.

---

##### `getBaseUrl(): string`

Retorna a URL base da loja.

---

##### `getCategories(): array`

Retorna categorias com suas subcategorias.

---

## AdminController

**Tipo:** Class  
**Arquivo:** `app/controllers/Master/AdminController.php`  
**Namespace:** `Akti\Controllers\Master`  
**Herda de:** `MasterBaseController`  

Class AdminController.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$adminModel` | Não |

### Métodos

#### Métodos Public

##### `__construct(?\PDO $db = null)`

Construtor da classe AdminController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO|null` | Conexão PDO com o banco de dados |

---

##### `index(): void`

Exibe a página de listagem.

**Retorno:** `void — */`

---

##### `create(): void`

Cria um novo registro no banco de dados.

**Retorno:** `void — */`

---

##### `store(): void`

Processa e armazena um novo registro.

**Retorno:** `void — */`

---

#### Métodos Private

##### `requireSuperadmin(): void`

Check if current user is superadmin (required for admin management).

---

### Funções auxiliares do arquivo

#### `edit()`

---

#### `update()`

---

#### `delete()`

---

## BackupController

**Tipo:** Class  
**Arquivo:** `app/controllers/Master/BackupController.php`  
**Namespace:** `Akti\Controllers\Master`  
**Herda de:** `MasterBaseController`  

Class BackupController.

### Métodos

#### Métodos Public

##### `index(): void`

Exibe a página de listagem.

**Retorno:** `void — */`

---

##### `run(): void`

Executa um processo.

**Retorno:** `void — */`

---

#### Métodos Private

##### `formatBytesHelper(int $bytes): string`

Formata dados para exibição.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$bytes` | `int` | Bytes |

**Retorno:** `string — */`

---

### Funções auxiliares do arquivo

#### `download()`

---

#### `diagnoseJson()`

---

#### `delete()`

---

## ClientController

**Tipo:** Class  
**Arquivo:** `app/controllers/Master/ClientController.php`  
**Namespace:** `Akti\Controllers\Master`  
**Herda de:** `MasterBaseController`  

Class ClientController.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$clientModel` | Não |
| private | `$planModel` | Não |

### Métodos

#### Métodos Public

##### `__construct(?\PDO $db = null)`

Construtor da classe ClientController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO|null` | Conexão PDO com o banco de dados |

---

##### `index(): void`

Exibe a página de listagem.

**Retorno:** `void — */`

---

##### `create(): void`

Cria um novo registro no banco de dados.

**Retorno:** `void — */`

---

##### `store(): void`

Processa e armazena um novo registro.

**Retorno:** `void — */`

---

### Funções auxiliares do arquivo

#### `edit()`

---

#### `update()`

---

#### `toggleActive()`

---

#### `delete()`

---

#### `createTenantUser()`

---

#### `getPlanLimits()`

---

## DashboardController

**Tipo:** Class  
**Arquivo:** `app/controllers/Master/DashboardController.php`  
**Namespace:** `Akti\Controllers\Master`  
**Herda de:** `MasterBaseController`  

Class DashboardController.

### Métodos

#### Métodos Public

##### `index(): void`

Exibe a página de listagem.

**Retorno:** `void — */`

---

## DeployController

**Tipo:** Class  
**Arquivo:** `app/controllers/Master/DeployController.php`  
**Namespace:** `Akti\Controllers\Master`  
**Herda de:** `MasterBaseController`  

Class DeployController.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$migrationModel` | Não |

### Métodos

#### Métodos Public

##### `__construct(?\PDO $db = null)`

Construtor da classe DeployController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO|null` | Conexão PDO com o banco de dados |

---

##### `index(): void`

Exibe a página de listagem.

**Retorno:** `void — */`

---

##### `run(): void`

Execute the deploy pipeline: git pull → apply pending SQL → clear cache.

---

#### Métodos Private

##### `requireSuperadmin(): void`

Require superadmin for deploy operations.

---

### Funções auxiliares do arquivo

#### `scanPendingSqlFiles()`

---

## GitController

**Tipo:** Class  
**Arquivo:** `app/controllers/Master/GitController.php`  
**Namespace:** `Akti\Controllers\Master`  
**Herda de:** `MasterBaseController`  

Class GitController.

### Métodos

#### Métodos Public

##### `index(): void`

Página principal — renderiza shell com spinners, dados carregam via AJAX

---

##### `loadRepos(): void`

Carrega informações de todos os repositórios e diagnóstico (AJAX)

---

##### `fetchAll(): void`

Busca dados.

**Retorno:** `void — */`

---

##### `fetch(): void`

Busca dados.

**Retorno:** `void — */`

---

##### `pull(): void`

Pull.

**Retorno:** `void — */`

---

## 

**Tipo:** Class  
**Arquivo:** `app/controllers/Master/GitController.php`  
**Namespace:** `Akti\Controllers\Master`  

### Funções auxiliares do arquivo

#### `forceReset()`

---

#### `detail()`

---

#### `checkout()`

---

#### `pullAll()`

---

#### `diagnoseJson()`

---

## HealthCheckController

**Tipo:** Class  
**Arquivo:** `app/controllers/Master/HealthCheckController.php`  
**Namespace:** `Akti\Controllers\Master`  
**Herda de:** `MasterBaseController`  

Class HealthCheckController.

### Métodos

#### Métodos Public

##### `index(): void`

Exibe a página de listagem.

**Retorno:** `void — */`

---

##### `statusJson(): void`

JSON endpoint for auto-refresh.

---

#### Métodos Private

##### `checkMasterDb(): array`

Verifica condição ou estado.

**Retorno:** `array — */`

---

##### `checkTenantDbs(array $tenants): array`

Verifica condição ou estado.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$tenants` | `array` | Tenants |

**Retorno:** `array — */`

---

##### `checkDiskSpace(): array`

Verifica condição ou estado.

**Retorno:** `array — */`

---

##### `checkPhpInfo(): array`

Verifica condição ou estado.

**Retorno:** `array — */`

---

##### `checkNodeApi(): array`

Verifica condição ou estado.

**Retorno:** `array — */`

---

##### `checkPendingSql(): array`

Verifica condição ou estado.

**Retorno:** `array — */`

---

## LogController

**Tipo:** Class  
**Arquivo:** `app/controllers/Master/LogController.php`  
**Namespace:** `Akti\Controllers\Master`  
**Herda de:** `MasterBaseController`  

Class LogController.

### Métodos

#### Métodos Public

##### `index(): void`

Exibe a página de listagem.

**Retorno:** `void — */`

---

##### `read(): void`

Read.

**Retorno:** `void — */`

---

##### `search(): void`

Realiza busca com filtros.

**Retorno:** `void — */`

---

##### `download(): void`

Gera download de arquivo.

**Retorno:** `void — */`

---

## MasterBaseController

**Tipo:** Class  
**Arquivo:** `app/controllers/Master/MasterBaseController.php`  
**Namespace:** `Akti\Controllers\Master`  
**Herda de:** `BaseController`  

### Métodos

#### Métodos Public

##### `__construct(?\PDO $db = null)`

Construtor da classe MasterBaseController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO|null` | Conexão PDO com o banco de dados |

---

#### Métodos Protected

##### `requireMasterAuth(): void`

Require master auth.

**Retorno:** `void — */`

---

##### `getMasterAdminId(): ?int`

Obtém dados específicos.

**Retorno:** `int|null — */`

---

##### `logAction(string $action, string $targetType, ?int $targetId, string $details): void`

Registra informação no log.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$action` | `string` | Action |
| `$targetType` | `string` | Target type |
| `$targetId` | `int|null` | Target id |
| `$details` | `string` | Details |

**Retorno:** `void — */`

---

##### `renderMaster(string $view, array $data = []): void`

Renderiza view/template.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$view` | `string` | View |
| `$data` | `array` | Dados para processamento |

**Retorno:** `void — */`

---

## MigrationController

**Tipo:** Class  
**Arquivo:** `app/controllers/Master/MigrationController.php`  
**Namespace:** `Akti\Controllers\Master`  
**Herda de:** `MasterBaseController`  

Class MigrationController.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$migrationModel` | Não |
| private | `$clientModel` | Não |

### Métodos

#### Métodos Public

##### `__construct(?\PDO $db = null)`

Construtor da classe MigrationController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO|null` | Conexão PDO com o banco de dados |

---

##### `index(): void`

Exibe a página de listagem.

**Retorno:** `void — */`

---

##### `previewSqlFile(): void`

Preview content of a pending SQL file.

---

##### `applySingleFile(): void`

Apply a single pending SQL file via AJAX (from the file list).

---

#### Métodos Private

##### `isRegisteredDb(string $dbName): bool`

Valida se o nome de banco pertence a um tenant registrado.

---

##### `separateAndValidateDbs(array $selectedDbs): array`

Separa akti_master dos bancos tenant selecionados e valida os tenants.

**Retorno:** `array{tenantDbs: — array, applyToMaster: bool, invalidDbs: array}`

---

##### `executeSqlOnMaster(string $sql): array`

Executa SQL no akti_master e retorna resultado.

---

##### `scanPendingSqlFiles(): array`

Scan the /sql/ folder for pending migration files (not in /sql/prontos/).

---

### Funções auxiliares do arquivo

#### `applyFile()`

---

#### `applyAllFiles()`

---

#### `compareDetail()`

---

#### `apply()`

---

#### `results()`

---

#### `history()`

---

#### `historyDetail()`

---

#### `users()`

---

#### `createUser()`

---

#### `toggleUser()`

---

#### `dbUsers()`

---

## PlanController

**Tipo:** Class  
**Arquivo:** `app/controllers/Master/PlanController.php`  
**Namespace:** `Akti\Controllers\Master`  
**Herda de:** `MasterBaseController`  

Class PlanController.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$planModel` | Não |

### Métodos

#### Métodos Public

##### `__construct(?\PDO $db = null)`

Construtor da classe PlanController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO|null` | Conexão PDO com o banco de dados |

---

##### `index(): void`

Exibe a página de listagem.

**Retorno:** `void — */`

---

##### `create(): void`

Cria um novo registro no banco de dados.

**Retorno:** `void — */`

---

##### `store(): void`

Processa e armazena um novo registro.

**Retorno:** `void — */`

---

### Funções auxiliares do arquivo

#### `edit()`

---

#### `update()`

---

#### `delete()`

---

## NfeCredentialController

**Tipo:** Class  
**Arquivo:** `app/controllers/NfeCredentialController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

Controller: NfeCredentialController

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$credModel` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db, NfeCredential $credModel)`

Construtor da classe NfeCredentialController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |
| `$credModel` | `NfeCredential` | Cred model |

---

##### `index()`

Exibe formulário de credenciais SEFAZ.

---

##### `store()`

Salva/atualiza credenciais SEFAZ.

---

##### `update()`

Atualiza credenciais (alias para store).

---

##### `testConnection()`

Testa a conexão com a SEFAZ.

---

##### `importIbptax()`

Importa tabela IBPTax a partir de arquivo CSV enviado pelo usuário.

---

##### `ibptaxStats()`

Retorna estatísticas da tabela IBPTax (AJAX/JSON).

---

#### Métodos Private

##### `checkPermission(string $page): void`

Verifica se o usuário tem permissão para acessar credenciais NF-e.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$page` | `string` | Nome da página/módulo |

---

##### `handleCertificateUpload(array &$data): void`

Processa upload do certificado .pfx.

---

##### `getAuditService(): NfeAuditService`

Retorna instância do serviço de auditoria (lazy).

**Retorno:** `NfeAuditService — */`

---

## NfeDocumentController

**Tipo:** Class  
**Arquivo:** `app/controllers/NfeDocumentController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

Controller: NfeDocumentController

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$docModel` | Não |
| private | `$logModel` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db, NfeDocument $docModel, NfeLog $logModel)`

Construtor da classe NfeDocumentController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |
| `$docModel` | `NfeDocument` | Doc model |
| `$logModel` | `NfeLog` | Log model |

---

##### `index()`

Painel de Notas Fiscais — listagem com filtros e cards de resumo.

---

##### `detail()`

Exibe detalhe completo de uma NF-e com dados financeiros e IBPTax.

---

##### `emit()`

Emite NF-e para um pedido (AJAX/JSON).

---

#### Métodos Private

##### `checkPermission(string $page): void`

Verifica se o usuário tem permissão para acessar a página.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$page` | `string` | Nome da página/módulo |

---

##### `checkWritePermission(): void`

Verifica permissão de escrita (edit) para ações de emissão/cancelamento/correção.

---

##### `isAjaxFragment(): bool`

Verifica se a requisição é AJAX (fragmento para sidebar).

---

### Funções auxiliares do arquivo

#### `cancel()`

---

#### `correction()`

---

#### `download()`

---

#### `checkStatus()`

---

#### `dashboard()`

---

#### `correctionReport()`

---

#### `exportReport()`

---

#### `batchEmit()`

---

#### `queue()`

---

#### `processQueue()`

---

#### `cancelQueue()`

---

#### `received()`

---

#### `queryDistDFe()`

---

#### `queryDistDFeByChave()`

---

#### `manifest()`

---

#### `audit()`

---

#### `webhooks()`

---

#### `saveWebhook()`

---

#### `deleteWebhook()`

---

#### `testWebhook()`

---

#### `webhookLogs()`

---

#### `danfeSettings()`

---

#### `saveDanfeSettings()`

---

#### `retry()`

---

#### `inutilizar()`

---

#### `emitNfce()`

---

#### `downloadDanfeNfce()`

---

#### `contingencyStatus()`

---

#### `contingencyActivate()`

---

#### `contingencyDeactivate()`

---

#### `contingencySync()`

---

#### `contingencyHistory()`

---

#### `downloadBatch()`

---

#### `exportSped()`

---

#### `exportSintegra()`

---

#### `livroSaidas()`

---

#### `livroEntradas()`

---

#### `backupXml()`

---

#### `backupHistory()`

---

#### `backupSettings()`

---

#### `saveBackupSettings()`

---

#### `getAuditService()`

---

#### `dispatchWebhook(string $event, array $payload)`

---

## NotificationController

**Tipo:** Class  
**Arquivo:** `app/controllers/NotificationController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

NotificationController

### Métodos

#### Métodos Public

##### `index(): void`

Lista as notificações do usuário (JSON para AJAX).

---

##### `count(): void`

Conta notificações não-lidas (JSON endpoint para badge).

---

##### `markRead(): void`

Marca uma notificação como lida.

---

##### `markAllRead(): void`

Marca todas as notificações como lidas.

---

##### `stream(): void`

SSE stream — pushes new notifications to the browser in real time.

---

## OrderController

**Tipo:** Class  
**Arquivo:** `app/controllers/OrderController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

Class OrderController.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$orderModel` | Não |
| private | `$itemService` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db, Order $orderModel, OrderItemService $itemService)`

Construtor da classe OrderController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |
| `$orderModel` | `Order` | Order model |
| `$itemService` | `OrderItemService` | Item service |

---

##### `index()`

Exibe a página de listagem.

---

##### `create()`

Cria um novo registro no banco de dados.

---

##### `store()`

Processa e armazena um novo registro.

---

### Funções auxiliares do arquivo

#### `edit()`

---

#### `update()`

---

#### `addItem()`

---

#### `updateItem()`

---

#### `deleteItem()`

---

#### `updateItemQty()`

---

#### `updateItemDiscount()`

---

#### `printQuote()`

---

#### `printOrder()`

---

#### `delete()`

---

#### `agenda()`

---

#### `report()`

---

## PaymentGatewayController

**Tipo:** Class  
**Arquivo:** `app/controllers/PaymentGatewayController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

Controller: PaymentGatewayController

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$gatewayModel` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db, PaymentGateway $gatewayModel)`

Construtor da classe PaymentGatewayController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |
| `$gatewayModel` | `PaymentGateway` | Gateway model |

---

##### `index()`

Lista gateways configurados (aba em settings ou page separada).

---

##### `edit()`

Editar configuração de um gateway específico.

---

##### `update()`

Salvar configuração de um gateway (POST).

---

### Funções auxiliares do arquivo

#### `testConnection()`

---

#### `createCharge()`

---

#### `chargeStatus()`

---

#### `transactions()`

---

#### `createCheckoutLink()`

---

#### `getApiBaseUrl()`

---

## PipelineController

**Tipo:** Class  
**Arquivo:** `app/controllers/PipelineController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

Class PipelineController.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$pipelineModel` | Não |
| private | `$stockModel` | Não |
| private | `$pipelineService` | Não |
| private | `$alertService` | Não |
| private | `$paymentService` | Não |
| private | `$detailService` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db,
        Pipeline $pipelineModel,
        Stock $stockModel,
        PipelineService $pipelineService,
        PipelineAlertService $alertService,
        PipelinePaymentService $paymentService,
        PipelineDetailService $detailService)`

Construtor da classe PipelineController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |
| `$pipelineModel` | `Pipeline` | Pipeline model |
| `$stockModel` | `Stock` | Stock model |
| `$pipelineService` | `PipelineService` | Pipeline service |
| `$alertService` | `PipelineAlertService` | Alert service |
| `$paymentService` | `PipelinePaymentService` | Payment service |
| `$detailService` | `PipelineDetailService` | Detail service |

---

##### `index()`

View principal: Kanban Board

---

##### `move()`

Move registro de posição.

---

##### `moveAjax()`

Mover pedido via AJAX (drag-and-drop).

---

##### `detail()`

Detalhes de um pedido no pipeline.

---

##### `updateDetails()`

Atualizar detalhes do pedido (POST).

---

##### `settings()`

ConfiguraÃ§Ãµes de metas por etapa

---

##### `saveSettings()`

Salvar configuraÃ§Ãµes de metas (POST)

---

##### `alerts()`

API JSON: pedidos atrasados (para notificaÃ§Ãµes).

---

##### `getPricesByTable()`

API JSON: Retorna preÃ§os de uma tabela de preÃ§o especÃ­fica (AJAX)

---

##### `checkOrderStock()`

API JSON: Verifica disponibilidade de estoque dos itens de um pedido num armazÃ©m (AJAX).

---

##### `addExtraCost()`

Adicionar custo extra ao pedido (POST)

---

##### `deleteExtraCost()`

Remover custo extra do pedido

---

##### `printProductionOrder()`

Imprimir Ordem de ProduÃ§Ã£o.

---

##### `togglePreparation()`

Alternar item do checklist de preparaÃ§Ã£o (AJAX POST)

---

##### `printThermalReceipt()`

Imprimir cupom nÃ£o fiscal (impressora tÃ©rmica).

---

#### Métodos Private

##### `clearQuoteConfirmation($orderId)`

Remove a confirmaÃ§Ã£o de orÃ§amento quando o pedido Ã© modificado.

---

## PipelinePaymentController

**Tipo:** Class  
**Arquivo:** `app/controllers/PipelinePaymentController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

Class PipelinePaymentController.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$alertService` | Não |
| private | `$paymentService` | Não |
| private | `$pipelineService` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor da classe PipelinePaymentController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |

---

##### `countInstallments()`

Conta registros com critérios opcionais.

---

##### `deleteInstallments()`

Delete installments.

---

##### `generatePaymentLink()`

Gera conteúdo ou dados.

---

##### `generateMercadoPagoLink()`

Gera conteúdo ou dados.

---

##### `confirmDownPayment()`

Confirm down payment.

---

##### `syncInstallments()`

Sincroniza dados.

---

##### `updateInstallmentDueDate()`

Update installment due date.

---

## PipelineProductionController

**Tipo:** Class  
**Arquivo:** `app/controllers/PipelineProductionController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

PipelineProductionController — Painel de produção e setores.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$pipelineModel` | Não |
| private | `$detailService` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db, Pipeline $pipelineModel, PipelineDetailService $detailService)`

Construtor da classe PipelineProductionController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |
| `$pipelineModel` | `Pipeline` | Pipeline model |
| `$detailService` | `PipelineDetailService` | Detail service |

---

##### `moveSector()`

Move registro de posição.

---

##### `productionBoard()`

Production board.

---

##### `getItemLogs()`

Obtém dados específicos.

---

##### `addItemLog()`

Add item log.

---

##### `deleteItemLog()`

Delete item log.

---

## PortalAdminController

**Tipo:** Class  
**Arquivo:** `app/controllers/PortalAdminController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

Controller: PortalAdminController

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$portalAccess` | Não |
| private | `$service` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db, PortalAccess $portalAccess, PortalAdminService $service)`

Construtor da classe PortalAdminController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |
| `$portalAccess` | `PortalAccess` | Portal access |
| `$service` | `PortalAdminService` | Service |

---

##### `index(): void`

Listagem de acessos ao portal com dados do cliente.

---

##### `create(): void`

Exibe formulário de criação de acesso ao portal.

---

##### `store(): void`

Processa criação de acesso ao portal (POST).

---

##### `edit(): void`

Exibe formulário de edição de acesso ao portal.

---

##### `update(): void`

Processa atualização de acesso ao portal (POST).

---

##### `toggleAccess(): void`

Ativar/desativar acesso (toggle). POST AJAX.

---

##### `resetPassword(): void`

Resetar senha de um acesso. POST AJAX.

---

##### `sendMagicLink(): void`

Enviar magic link para o cliente. POST AJAX.

---

### Funções auxiliares do arquivo

#### `delete()`

---

#### `forceLogout()`

---

#### `config()`

---

#### `saveConfig()`

---

#### `metrics()`

---

#### `jsonResponse(bool $success, string $message, array $data = [])`

---

## PortalController

**Tipo:** Class  
**Arquivo:** `app/controllers/PortalController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

Controller: PortalController

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$portalAccess` | Não |
| private | `$logger` | Não |
| private | `$company` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db, PortalAccess $portalAccess, Logger $logger, CompanySettings $companySettings)`

Construtor da classe PortalController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |
| `$portalAccess` | `PortalAccess` | Portal access |
| `$logger` | `Logger` | Logger |
| `$companySettings` | `CompanySettings` | Company settings |

---

##### `index(): void`

Página inicial do portal — redireciona conforme estado de autenticação.

---

##### `login(): void`

Exibe a tela de login / Processa o login (POST).

---

##### `loginMagic(): void`

Login via link mágico.

---

##### `setupPassword(): void`

Página temporária para cadastrar senha (via magic link ou senha temporária).

---

##### `logout(): void`

Logout do portal.

---

### Funções auxiliares do arquivo

#### `register()`

---

#### `dashboard()`

---

#### `profile()`

---

#### `updateProfile()`

---

#### `requestMagicLink()`

---

#### `forgotPassword()`

---

#### `resetPassword()`

---

#### `orders()`

---

#### `orderDetail()`

---

#### `approveOrder()`

---

#### `rejectOrder()`

---

#### `cancelApproval()`

---

#### `newOrder()`

---

#### `getProducts()`

---

#### `addToCart()`

---

#### `removeFromCart()`

---

#### `updateCartItem()`

---

#### `getCart()`

---

#### `submitOrder()`

---

#### `installments()`

---

#### `installmentDetail()`

---

#### `tracking()`

---

#### `messages()`

---

#### `sendMessage()`

---

#### `documents()`

---

#### `downloadDocument()`

---

#### `verify2fa()`

---

#### `resend2fa()`

---

#### `toggle2fa()`

---

#### `uploadAvatar()`

---

#### `checkRateLimit(string $action, int $maxAttempts = 30, int $windowSeconds = 60)`

---

#### `isPortalEnabled()`

---

#### `renderDisabled()`

---

#### `findCustomerByEmail(string $email)`

---

#### `isPasswordStrong(string $password)`

---

#### `jsonResponse(bool $success, string $message, array $data = [])`

---

## ProductController

**Tipo:** Class  
**Arquivo:** `app/controllers/ProductController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

Class ProductController.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$productModel` | Não |
| private | `$categoryModel` | Não |
| private | `$subcategoryModel` | Não |
| private | `$sectorModel` | Não |
| private | `$gradeModel` | Não |
| private | `$logger` | Não |
| private | `$importService` | Não |
| private | `$gradeService` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db,
        Product $productModel,
        Category $categoryModel,
        Subcategory $subcategoryModel,
        ProductionSector $sectorModel,
        ProductGrade $gradeModel,
        Logger $logger,
        ProductImportService $importService,
        ProductGradeService $gradeService)`

Construtor da classe ProductController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |
| `$productModel` | `Product` | Product model |
| `$categoryModel` | `Category` | Category model |
| `$subcategoryModel` | `Subcategory` | Subcategory model |
| `$sectorModel` | `ProductionSector` | Sector model |
| `$gradeModel` | `ProductGrade` | Grade model |
| `$logger` | `Logger` | Logger |
| `$importService` | `ProductImportService` | Import service |
| `$gradeService` | `ProductGradeService` | Grade service |

---

##### `index()`

Exibe a página de listagem.

---

##### `create()`

Cria um novo registro no banco de dados.

---

##### `store()`

Processa e armazena um novo registro.

---

##### `getSubcategories()`

Obtém dados específicos.

---

##### `createCategoryAjax()`

Create category ajax.

---

##### `edit()`

Exibe o formulário de edição.

---

##### `update()`

Atualiza um registro existente.

---

##### `delete()`

Remove um registro pelo ID.

---

##### `deleteImage()`

Delete image.

---

##### `searchSelect2()`

AJAX: Busca produtos para Select2 (substitui a API Node.js).

---

##### `searchAjax(): void`

AJAX: Busca paginada de produtos para Select2 com scroll infinito.

---

##### `getProductsList()`

AJAX: Lista produtos com filtros e paginação (para a seção de visão geral)

---

##### `parseImportFile()`

AJAX: Analisa arquivo de importação (CSV/XLS/XLSX) e retorna preview.

---

##### `importProductsMapped()`

AJAX: Importa produtos usando mapeamento de colunas definido pelo usuário.

---

##### `downloadImportTemplate()`

Download CSV import template.

---

##### `importProducts()`

AJAX: Import products from CSV/XLS file (mapeamento automático por header).

---

##### `createGradeTypeAjax()`

AJAX: Create a new grade type on the fly.

---

##### `getGradeTypes()`

AJAX: Get grade types list.

---

##### `generateCombinationsAjax()`

AJAX: Generate and return combinations based on provided grades data.

---

#### Métodos Private

##### `handlePhotoUpload(int $productId, ?array $files, $mainImageIndex = 0): void`

Método privado para upload de fotos do produto.

---

## ProductGradeController

**Tipo:** Class  
**Arquivo:** `app/controllers/ProductGradeController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

ProductGradeController — Gerenciamento de grades de produtos.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$gradeService` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db, ProductGradeService $gradeService)`

Construtor da classe ProductGradeController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |
| `$gradeService` | `ProductGradeService` | Grade service |

---

##### `createGradeTypeAjax()`

Create grade type ajax.

---

##### `getGradeTypes()`

Obtém dados específicos.

---

##### `generateCombinationsAjax()`

Gera conteúdo ou dados.

---

## ProductImportController

**Tipo:** Class  
**Arquivo:** `app/controllers/ProductImportController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

ProductImportController — Importação de produtos (CSV/Excel).

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$importService` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db, ProductImportService $importService)`

Construtor da classe ProductImportController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |
| `$importService` | `ProductImportService` | Import service |

---

##### `parseImportFile()`

Interpreta dados.

---

##### `importProductsMapped()`

Importa dados.

---

##### `downloadImportTemplate()`

Gera download de arquivo.

---

##### `importProducts()`

Importa dados.

---

## ProductionCostController

**Tipo:** Class  
**Arquivo:** `app/controllers/ProductionCostController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

Class ProductionCostController.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$costService` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor da classe ProductionCostController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |

---

##### `index()`

Exibe a página de listagem.

---

##### `saveConfig()`

Salva dados.

---

##### `calculate()`

Calcula valor.

---

##### `marginReport()`

Margin report.

---

## QualityController

**Tipo:** Class  
**Arquivo:** `app/controllers/QualityController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

Class QualityController.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$model` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db, QualityChecklist $model)`

Construtor da classe QualityController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |
| `$model` | `QualityChecklist` | Model |

---

##### `index()`

Exibe a página de listagem.

---

##### `create()`

Cria um novo registro no banco de dados.

---

##### `store()`

Processa e armazena um novo registro.

---

##### `edit()`

Exibe o formulário de edição.

---

##### `update()`

Atualiza um registro existente.

---

##### `delete()`

Remove um registro pelo ID.

---

##### `addItem()`

Add item.

---

##### `removeItem()`

Remove item.

---

##### `inspect()`

Inspect.

---

##### `storeInspection()`

Store inspection.

---

##### `nonConformities()`

Non conformities.

---

##### `storeNonConformity()`

Store non conformity.

---

##### `resolveNonConformity()`

Resolve dependência ou valor.

---

## QuoteController

**Tipo:** Class  
**Arquivo:** `app/controllers/QuoteController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

Class QuoteController.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$model` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db, Quote $model)`

Construtor da classe QuoteController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |
| `$model` | `Quote` | Model |

---

##### `index()`

Exibe a página de listagem.

---

##### `create()`

Cria um novo registro no banco de dados.

---

##### `store()`

Processa e armazena um novo registro.

---

##### `edit()`

Exibe o formulário de edição.

---

##### `update()`

Atualiza um registro existente.

---

##### `delete()`

Remove um registro pelo ID.

---

##### `approve()`

Aprova registro ou operação.

---

##### `convertToOrder()`

Converte dados de um formato para outro.

---

##### `addItem()`

Add item.

---

##### `removeItem()`

Remove item.

---

## RecurringTransactionController

**Tipo:** Class  
**Arquivo:** `app/controllers/RecurringTransactionController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

RecurringTransactionController — CRUD + processamento de recorrências.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$model` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db, RecurringTransaction $model)`

Construtor da classe RecurringTransactionController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |
| `$model` | `RecurringTransaction` | Model |

---

##### `()`

Lista todas as recorrências (JSON).

---

##### `store()`

Cria nova recorrência (POST JSON).

---

##### `update()`

Atualiza recorrência existente (POST JSON).

---

##### `delete()`

Exclui uma recorrência (POST).

---

##### `toggle()`

Ativa/desativa recorrência (POST).

---

##### `process()`

Processa recorrências pendentes do mês (POST).

---

##### `get()`

Busca uma recorrência por ID (GET).

---

#### Métodos Private

##### `getNextGenerationDate(array $rec): ?string`

Calcula próxima data de geração.

---

## ReportController

**Tipo:** Class  
**Arquivo:** `app/controllers/ReportController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

Controller: ReportController

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$report` | Não |
| private | `$nfeReport` | Não |
| private | `$company` | Não |
| private | `$pdfService` | Não |
| private | `$excelService` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db, ReportModel $report, NfeReportModel $nfeReport, CompanySettings $companySettings)`

Construtor da classe ReportController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |
| `$report` | `ReportModel` | Report |
| `$nfeReport` | `NfeReportModel` | Nfe report |
| `$companySettings` | `CompanySettings` | Company settings |

---

##### `index(): void`

Exibe a tela de filtros e seleção de relatórios.

---

##### `exportPdf(): void`

Gera e envia um PDF para download conforme o tipo de relatório.

---

##### `exportExcel(): void`

Gera e envia um XLSX para download conforme o tipo de relatório.

---

#### Métodos Private

##### `requirePeriod(): array`

Valida e retorna período obrigatório (start, end). Redireciona se inválido.

**Retorno:** `array{0: — ?string, 1: ?string}`

---

##### `dispatchPdf(string $type, ?string $start = null, ?string $end = null): void`

Despacha a geração de PDF para o ReportPdfService conforme o tipo.

---

##### `dispatchExcel(string $type, ?string $start = null, ?string $end = null): void`

Despacha a geração de Excel para o ReportExcelService conforme o tipo.

---

##### `getTypeMethodMap(): array`

Mapa de tipo de relatório → método do service e parâmetros extras.

---

##### `buildArgs(array $config, ?string $start, ?string $end): array`

Constrói a lista de argumentos para o método do service.

---

## SearchController

**Tipo:** Class  
**Arquivo:** `app/controllers/SearchController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

SearchController

### Métodos

#### Métodos Public

##### `query(): void`

Busca global AJAX — retorna JSON com resultados agrupados.

---

#### Métodos Private

##### `searchCustomers(string $q, int $limit): array`

Busca clientes pelo nome, email, telefone ou documento.

---

##### `searchProducts(string $q, int $limit): array`

Busca produtos pelo nome ou descrição.

---

##### `searchOrders(string $q, int $limit): array`

Busca pedidos pelo ID, nome do cliente ou observações.

---

## SectorController

**Tipo:** Class  
**Arquivo:** `app/controllers/SectorController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

Class SectorController.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$sectorModel` | Não |
| private | `$logger` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db, ProductionSector $sectorModel, Logger $logger)`

Construtor da classe SectorController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |
| `$sectorModel` | `ProductionSector` | Sector model |
| `$logger` | `Logger` | Logger |

---

##### `index()`

Exibe a página de listagem.

---

##### `store()`

Processa e armazena um novo registro.

---

##### `update()`

Atualiza um registro existente.

---

##### `delete()`

Remove um registro pelo ID.

---

## SettingsController

**Tipo:** Class  
**Arquivo:** `app/controllers/SettingsController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

Class SettingsController.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$companySettings` | Não |
| private | `$priceTable` | Não |
| private | `$preparationStep` | Não |
| private | `$settingsService` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db,
        CompanySettings $companySettings,
        PriceTable $priceTable,
        PreparationStep $preparationStep,
        SettingsService $settingsService)`

Construtor da classe SettingsController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |
| `$companySettings` | `CompanySettings` | Company settings |
| `$priceTable` | `PriceTable` | Price table |
| `$preparationStep` | `PreparationStep` | Preparation step |
| `$settingsService` | `SettingsService` | Settings service |

---

##### `index()`

Página de configurações da empresa

---

##### `saveCompany()`

Salvar configurações da empresa (POST)

---

##### `saveBankSettings()`

Salvar configurações bancárias para boletos (POST)

---

##### `priceTablesIndex()`

Página dedicada de Tabelas de Preço (menu principal)

---

##### `createPriceTable()`

Criar tabela de preço (POST)

---

##### `updatePriceTable()`

Atualizar tabela de preço (POST)

---

##### `deletePriceTable()`

Excluir tabela de preço

---

##### `editPriceTable()`

Editar itens de uma tabela de preço

---

##### `savePriceItem()`

Adicionar/atualizar item na tabela de preço (POST)

---

##### `deletePriceItem()`

Remover item da tabela de preço

---

##### `getPricesForCustomer()`

API: Retorna preços para um cliente (AJAX/JSON)

---

##### `addPreparationStep()`

Adicionar nova etapa de preparo (POST)

---

##### `updatePreparationStep()`

Atualizar etapa de preparo (POST)

---

##### `deletePreparationStep()`

Excluir etapa de preparo

---

##### `togglePreparationStep()`

Ativar/desativar etapa de preparo (AJAX)

---

##### `saveFiscalSettings()`

Salvar configurações fiscais da empresa (POST)

---

##### `saveSecuritySettings()`

Salvar configurações de segurança (POST)

---

##### `saveDashboardWidgets()`

Salvar configuração de widgets do dashboard para um grupo (AJAX/JSON)

---

##### `resetDashboardWidgets()`

Resetar configuração de widgets de um grupo para o padrão global (AJAX/JSON)

---

## ShipmentController

**Tipo:** Class  
**Arquivo:** `app/controllers/ShipmentController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

Class ShipmentController.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$shipmentModel` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor da classe ShipmentController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |

---

##### `index()`

Exibe a página de listagem.

---

##### `create()`

Cria um novo registro no banco de dados.

---

##### `store()`

Processa e armazena um novo registro.

---

##### `view()`

View.

---

##### `addEvent()`

Add event.

---

##### `carriers()`

Carriers.

---

##### `saveCarrier()`

Salva dados.

---

##### `dashboard()`

Dashboard.

---

##### `delete()`

Remove um registro pelo ID.

---

## SiteBuilderController

**Tipo:** Class  
**Arquivo:** `app/controllers/SiteBuilderController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

Controller do Site Builder.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$siteBuilder` | Não |
| private | `$tenantId` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db, SiteBuilder $siteBuilder)`

Construtor da classe SiteBuilderController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |
| `$siteBuilder` | `SiteBuilder` | Site builder |

---

##### `index(): void`

Página principal do Site Builder (editor de configurações + preview).

---

##### `getSettings(): void`

Retorna todas as configurações (AJAX GET).

---

##### `saveSettings(): void`

Salva configurações de um grupo (POST AJAX).

---

##### `preview(): void`

Preview da loja (renderiza no iframe).

---

##### `uploadImage(): void`

Upload de imagem para o site builder (POST AJAX).

---

#### Métodos Private

##### `requireTenant(): bool`

Require tenant.

**Retorno:** `bool — */`

---

## StockController

**Tipo:** Class  
**Arquivo:** `app/controllers/StockController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

Class StockController.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$stockModel` | Não |
| private | `$productModel` | Não |
| private | `$logger` | Não |
| private | `$movementService` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db,
        Stock $stockModel,
        Product $productModel,
        Logger $logger,
        StockMovementService $movementService)`

Construtor da classe StockController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |
| `$stockModel` | `Stock` | Stock model |
| `$productModel` | `Product` | Product model |
| `$logger` | `Logger` | Logger |
| `$movementService` | `StockMovementService` | Movement service |

---

##### `index()`

Exibe a página de listagem.

---

##### `warehouses()`

Warehouses.

---

##### `storeWarehouse()`

Store warehouse.

---

### Funções auxiliares do arquivo

#### `updateWarehouse()`

---

#### `deleteWarehouse()`

---

#### `movements()`

---

#### `entry()`

---

#### `getStockItems()`

---

#### `getMovements()`

---

#### `storeMovement()`

---

#### `getMovement()`

---

#### `updateMovement()`

---

#### `deleteMovement()`

---

#### `getProductCombinations()`

---

#### `updateItemMeta()`

---

#### `getProductStock()`

---

#### `setDefault()`

---

#### `getDefaultWarehouse()`

---

#### `checkOrderStock()`

---

## SupplierController

**Tipo:** Class  
**Arquivo:** `app/controllers/SupplierController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

Class SupplierController.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$supplierModel` | Não |
| private | `$purchaseModel` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db, Supplier $supplierModel, PurchaseOrder $purchaseModel)`

Construtor da classe SupplierController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |
| `$supplierModel` | `Supplier` | Supplier model |
| `$purchaseModel` | `PurchaseOrder` | Purchase model |

---

##### `index()`

Exibe a página de listagem.

---

##### `create()`

Cria um novo registro no banco de dados.

---

##### `store()`

Processa e armazena um novo registro.

---

##### `edit()`

Exibe o formulário de edição.

---

##### `update()`

Atualiza um registro existente.

---

##### `delete()`

Remove um registro pelo ID.

---

##### `purchases()`

Purchases.

---

##### `createPurchase()`

Create purchase.

---

##### `storePurchase()`

Store purchase.

---

##### `editPurchase()`

Edit purchase.

---

##### `receivePurchase()`

Receive purchase.

---

## SupplyController

**Tipo:** Class  
**Arquivo:** `app/controllers/SupplyController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

Class SupplyController.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$supplyModel` | Não |
| private | `$supplierModel` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db, Supply $supplyModel, Supplier $supplierModel)`

Construtor da classe SupplyController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |
| `$supplyModel` | `Supply` | Supply model |
| `$supplierModel` | `Supplier` | Supplier model |

---

##### `index()`

Exibe a página de listagem.

---

##### `create()`

Cria um novo registro no banco de dados.

---

##### `store()`

Processa e armazena um novo registro.

---

##### `edit()`

Exibe o formulário de edição.

---

##### `update()`

Atualiza um registro existente.

---

##### `delete()`

Remove um registro pelo ID.

---

##### `createCategoryAjax()`

Create category ajax.

---

##### `getCategoriesAjax()`

Obtém dados específicos.

---

##### `searchSelect2()`

Search select2.

---

##### `getSuppliers()`

Obtém dados específicos.

---

##### `linkSupplier()`

Link supplier.

---

##### `updateSupplierLink()`

Update supplier link.

---

##### `unlinkSupplier()`

Unlink supplier.

---

##### `searchSuppliers()`

Search suppliers.

---

##### `getPriceHistory()`

Obtém dados específicos.

---

##### `getProductSupplies()`

Obtém dados específicos.

---

##### `addProductSupply()`

Add product supply.

---

##### `updateProductSupply()`

Update product supply.

---

##### `removeProductSupply()`

Remove product supply.

---

##### `estimateConsumption()`

Estimate consumption.

---

##### `getSupplyProducts()`

Obtém dados específicos.

---

##### `getWhereUsedImpact()`

Obtém dados específicos.

---

##### `applyBOMCostUpdate()`

Apply b o m cost update.

---

## SupplyStockController

**Tipo:** Class  
**Arquivo:** `app/controllers/SupplyStockController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

Class SupplyStockController.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$stockModel` | Não |
| private | `$supplyModel` | Não |
| private | `$logger` | Não |
| private | `$movementService` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db,
        SupplyStock $stockModel,
        Supply $supplyModel,
        Logger $logger,
        SupplyStockMovementService $movementService)`

Construtor da classe SupplyStockController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |
| `$stockModel` | `SupplyStock` | Stock model |
| `$supplyModel` | `Supply` | Supply model |
| `$logger` | `Logger` | Logger |
| `$movementService` | `SupplyStockMovementService` | Movement service |

---

##### `index(): void`

Exibe a página de listagem.

**Retorno:** `void — */`

---

##### `entry(): void`

Entry.

**Retorno:** `void — */`

---

##### `storeEntry(): void`

Store entry.

**Retorno:** `void — */`

---

##### `(): void`

.

**Retorno:** `void — */`

---

##### `storeExit(): void`

Store exit.

**Retorno:** `void — */`

---

##### `transfer(): void`

Transfer.

**Retorno:** `void — */`

---

##### `storeTransfer(): void`

Store transfer.

**Retorno:** `void — */`

---

##### `adjust(): void`

Adjust.

**Retorno:** `void — */`

---

##### `storeAdjust(): void`

Store adjust.

**Retorno:** `void — */`

---

##### `movements(): void`

Move registro de posição.

**Retorno:** `void — */`

---

##### `searchSupplies(): void`

Search supplies.

**Retorno:** `void — */`

---

##### `getStockInfo(): void`

Obtém dados específicos.

**Retorno:** `void — */`

---

##### `getBatches(): void`

Obtém dados específicos.

**Retorno:** `void — */`

---

##### `getStockItems(): void`

Obtém dados específicos.

**Retorno:** `void — */`

---

##### `reorderSuggestions(): void`

Reordena registros.

**Retorno:** `void — */`

---

##### `getDashboard(): void`

Obtém dados específicos.

**Retorno:** `void — */`

---

#### Métodos Private

##### `getMovementsJson(): void`

Obtém dados específicos.

**Retorno:** `void — */`

---

## TicketController

**Tipo:** Class  
**Arquivo:** `app/controllers/TicketController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

Class TicketController.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$ticketModel` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor da classe TicketController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |

---

##### `index()`

Exibe a página de listagem.

---

##### `create()`

Cria um novo registro no banco de dados.

---

##### `store()`

Processa e armazena um novo registro.

---

##### `view()`

View.

---

##### `addMessage()`

Add message.

---

##### `updateStatus()`

Update status.

---

##### `dashboard()`

Dashboard.

---

##### `delete()`

Remove um registro pelo ID.

---

## TransactionController

**Tipo:** Class  
**Arquivo:** `app/controllers/TransactionController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

TransactionController — Controller dedicado a transações financeiras (entradas/saídas).

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$transactionService` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db, TransactionService $transactionService)`

Construtor da classe TransactionController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |
| `$transactionService` | `TransactionService` | Transaction service |

---

##### `index()`

Exibe a página de listagem.

---

##### `add()`

Add.

---

##### `delete()`

Remove um registro pelo ID.

---

##### `get()`

Get.

---

##### `update()`

Atualiza um registro existente.

---

##### `getPaginated()`

Obtém dados específicos.

---

#### Métodos Private

##### `jsonResponse(array $data): void`

Json response.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$data` | `array` | Dados para processamento |

**Retorno:** `void — */`

---

## UserController

**Tipo:** Class  
**Arquivo:** `app/controllers/UserController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

Class UserController.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$userModel` | Não |
| private | `$groupModel` | Não |
| private | `$logger` | Não |
| private | `$loginAttempt` | Não |
| private | `$authService` | Não |

### Métodos

#### Métodos Public

##### `__construct(User $userModel,
        UserGroup $groupModel,
        LoginAttempt $loginAttempt,
        Logger $logger,
        AuthService $authService)`

Construtor da classe UserController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$userModel` | `User` | User model |
| `$groupModel` | `UserGroup` | Group model |
| `$loginAttempt` | `LoginAttempt` | Login attempt |
| `$logger` | `Logger` | Logger |
| `$authService` | `AuthService` | Auth service |

---

##### `index()`

Exibe a página de listagem.

---

##### `create()`

Cria um novo registro no banco de dados.

---

##### `store()`

Processa e armazena um novo registro.

---

##### `edit()`

Exibe o formulário de edição.

---

##### `update()`

Atualiza um registro existente.

---

##### `delete()`

Remove um registro pelo ID.

---

##### `groups()`

Groups.

---

##### `createGroup()`

Create group.

---

##### `updateGroup()`

Update group.

---

##### `deleteGroup()`

Delete group.

---

##### `profile()`

Profile.

---

##### `updateProfile()`

Update profile.

---

##### `login()`

Processa a autenticação do usuário.

---

##### `logout()`

Encerra a sessão do usuário.

---

#### Métodos Private

##### `checkAdmin()`

Verifica se o usuário é administrador.

---

## WalkthroughController

**Tipo:** Class  
**Arquivo:** `app/controllers/WalkthroughController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

Class WalkthroughController.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$walkthroughModel` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db, Walkthrough $walkthroughModel)`

Construtor da classe WalkthroughController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |
| `$walkthroughModel` | `Walkthrough` | Walkthrough model |

---

##### `checkStatus()`

API: Verifica se o usuário precisa ver o walkthrough.

---

##### `start()`

API: Marca o walkthrough como iniciado.

---

##### `complete()`

API: Marca o walkthrough como completo.

---

##### `skip()`

API: Marca o walkthrough como pulado.

---

##### `saveStep()`

API: Salva o passo atual do walkthrough.

---

##### `reset()`

API: Reseta o walkthrough (para o admin permitir que o usuário veja de novo).

---

##### `manual()`

Página de manual/documentação embutida.

---

##### `getSteps()`

Retorna os passos do walkthrough baseados no role e permissões do usuário.

---

#### Métodos Private

##### `buildSteps(string $role, array $permissions): array`

Monta os passos do walkthrough com base no perfil do usuário.

---

## WebhookController

**Tipo:** Class  
**Arquivo:** `app/controllers/WebhookController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

WebhookController — Recebe notificações (webhooks) de gateways de pagamento via PHP.

### Métodos

#### Métodos Public

##### `handle(): void`

POST ?page=webhook&action=handle&gateway=<slug>

---

### Funções auxiliares do arquivo

#### `processApprovedPayment(?int $installmentId,
        ?int $orderId,
        float $amount,
        string $gatewaySlug,
        ?string $externalId)`

---

#### `getWebhookHeaders()`

---

#### `logWebhook(string $level, string $gateway, string $message)`

---

## WhatsAppController

**Tipo:** Class  
**Arquivo:** `app/controllers/WhatsAppController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

Class WhatsAppController.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$model` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor da classe WhatsAppController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |

---

##### `index()`

Exibe a página de listagem.

---

##### `saveConfig()`

Salva dados.

---

##### `saveTemplate()`

Salva dados.

---

##### `send()`

Envia dados ou notificação.

---

##### `testConnection()`

Test connection.

---

## WorkflowController

**Tipo:** Class  
**Arquivo:** `app/controllers/WorkflowController.php`  
**Namespace:** `Akti\Controllers`  
**Herda de:** `BaseController`  

Class WorkflowController.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$model` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db, WorkflowRule $model)`

Construtor da classe WorkflowController.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |
| `$model` | `WorkflowRule` | Model |

---

##### `index()`

Exibe a página de listagem.

---

##### `create()`

Cria um novo registro no banco de dados.

---

##### `store()`

Processa e armazena um novo registro.

---

##### `edit()`

Exibe o formulário de edição.

---

##### `update()`

Atualiza um registro existente.

---

##### `delete()`

Remove um registro pelo ID.

---

##### `toggle()`

Alterna estado de propriedade.

---

##### `logs()`

Registra informação no log.

---

##### `reorder()`

Reordena registros.

---

#### Métodos Private

##### `getAvailableEvents(): array`

Obtém dados específicos.

**Retorno:** `array — */`

---

##### `getEventFields(): array`

Obtém dados específicos.

**Retorno:** `array — */`

---

