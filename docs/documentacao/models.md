# Models (Modelos)

> Modelos de dados (camada de persistência): queries SQL via PDO, CRUD, regras de negócio.

**Total de arquivos:** 77

---

## Índice

- [Achievement](#achievement) — `app/models/Achievement.php`
- [Attachment](#attachment) — `app/models/Attachment.php`
- [AuditLog](#auditlog) — `app/models/AuditLog.php`
- [Branch](#branch) — `app/models/Branch.php`
- [CalendarEvent](#calendarevent) — `app/models/CalendarEvent.php`
- [CatalogLink](#cataloglink) — `app/models/CatalogLink.php`
- [Category](#category) — `app/models/Category.php`
- [CategoryGrade](#categorygrade) — `app/models/CategoryGrade.php`
- [CheckoutToken](#checkouttoken) — `app/models/CheckoutToken.php`
- [Commission](#commission) — `app/models/Commission.php`
- [CompanySettings](#companysettings) — `app/models/CompanySettings.php`
- [Customer](#customer) — `app/models/Customer.php`
- [CustomerContact](#customercontact) — `app/models/CustomerContact.php`
- [DashboardWidget](#dashboardwidget) — `app/models/DashboardWidget.php`
- [EmailCampaign](#emailcampaign) — `app/models/EmailCampaign.php`
- [Equipment](#equipment) — `app/models/Equipment.php`
- [EsgMetric](#esgmetric) — `app/models/EsgMetric.php`
- [Financial](#financial) — `app/models/Financial.php`
- [FinancialReport](#financialreport) — `app/models/FinancialReport.php`
- [FinancialSchema](#financialschema) — `app/models/FinancialSchema.php`
- [IbptaxModel](#ibptaxmodel) — `app/models/IbptaxModel.php`
- [ImportBatch](#importbatch) — `app/models/ImportBatch.php`
- [ImportMappingProfile](#importmappingprofile) — `app/models/ImportMappingProfile.php`
- [Installment](#installment) — `app/models/Installment.php`
- [IpGuard](#ipguard) — `app/models/IpGuard.php`
- [Logger](#logger) — `app/models/Logger.php`
- [LoginAttempt](#loginattempt) — `app/models/LoginAttempt.php`
- [AdminLog](#adminlog) — `app/models/Master/AdminLog.php`
- [AdminUser](#adminuser) — `app/models/Master/AdminUser.php`
- [Backup](#backup) — `app/models/Master/Backup.php`
- [GitVersion](#gitversion) — `app/models/Master/GitVersion.php`
- [Migration](#migration) — `app/models/Master/Migration.php`
- [NginxLog](#nginxlog) — `app/models/Master/NginxLog.php`
- [Plan](#plan) — `app/models/Master/Plan.php`
- [TenantClient](#tenantclient) — `app/models/Master/TenantClient.php`
- [NfeAuditLog](#nfeauditlog) — `app/models/NfeAuditLog.php`
- [NfeCredential](#nfecredential) — `app/models/NfeCredential.php`
- [NfeDocument](#nfedocument) — `app/models/NfeDocument.php`
- [NfeLog](#nfelog) — `app/models/NfeLog.php`
- [NfeQueue](#nfequeue) — `app/models/NfeQueue.php`
- [NfeReceivedDocument](#nfereceiveddocument) — `app/models/NfeReceivedDocument.php`
- [NfeReportModel](#nfereportmodel) — `app/models/NfeReportModel.php`
- [NfeWebhook](#nfewebhook) — `app/models/NfeWebhook.php`
- [Notification](#notification) — `app/models/Notification.php`
- [Order](#order) — `app/models/Order.php`
- [OrderItemLog](#orderitemlog) — `app/models/OrderItemLog.php`
- [OrderPreparation](#orderpreparation) — `app/models/OrderPreparation.php`
- [PaymentGateway](#paymentgateway) — `app/models/PaymentGateway.php`
- [Permission](#permission) — `app/models/Permission.php`
- [Pipeline](#pipeline) — `app/models/Pipeline.php`
- [PortalAccess](#portalaccess) — `app/models/PortalAccess.php`
- [PortalMessage](#portalmessage) — `app/models/PortalMessage.php`
- [PreparationStep](#preparationstep) — `app/models/PreparationStep.php`
- [PriceTable](#pricetable) — `app/models/PriceTable.php`
- [Product](#product) — `app/models/Product.php`
- [ProductGrade](#productgrade) — `app/models/ProductGrade.php`
- [ProductionSector](#productionsector) — `app/models/ProductionSector.php`
- [PurchaseOrder](#purchaseorder) — `app/models/PurchaseOrder.php`
- [QualityChecklist](#qualitychecklist) — `app/models/QualityChecklist.php`
- [Quote](#quote) — `app/models/Quote.php`
- [RecurringTransaction](#recurringtransaction) — `app/models/RecurringTransaction.php`
- [ReportModel](#reportmodel) — `app/models/ReportModel.php`
- [ReportTemplate](#reporttemplate) — `app/models/ReportTemplate.php`
- [Shipment](#shipment) — `app/models/Shipment.php`
- [SiteBuilder](#sitebuilder) — `app/models/SiteBuilder.php`
- [Stock](#stock) — `app/models/Stock.php`
- [Subcategory](#subcategory) — `app/models/Subcategory.php`
- [Supplier](#supplier) — `app/models/Supplier.php`
- [Supply](#supply) — `app/models/Supply.php`
- [SupplyStock](#supplystock) — `app/models/SupplyStock.php`
- [Ticket](#ticket) — `app/models/Ticket.php`
- [Transaction](#transaction) — `app/models/Transaction.php`
- [User](#user) — `app/models/User.php`
- [UserGroup](#usergroup) — `app/models/UserGroup.php`
- [Walkthrough](#walkthrough) — `app/models/Walkthrough.php`
- [WhatsAppMessage](#whatsappmessage) — `app/models/WhatsAppMessage.php`
- [WorkflowRule](#workflowrule) — `app/models/WorkflowRule.php`

---

## Achievement

**Tipo:** Class  
**Arquivo:** `app/models/Achievement.php`  
**Namespace:** `Akti\Models`  

Model de conquistas/gamificação do sistema.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe Achievement.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `create(array $data): int`

Cria um novo registro no banco de dados.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$data` | `array` | Dados para processamento |

**Retorno:** `int — */`

---

##### `readAll(int $tenantId): array`

Retorna todos os registros.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$tenantId` | `int` | ID do tenant |

**Retorno:** `array — */`

---

##### `readOne(int $id, int $tenantId): ?array`

Retorna um registro pelo ID.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `int` | ID do registro |
| `$tenantId` | `int` | ID do tenant |

**Retorno:** `array|null — */`

---

##### `update(int $id, int $tenantId, array $data): bool`

Atualiza um registro existente.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `int` | ID do registro |
| `$tenantId` | `int` | ID do tenant |
| `$data` | `array` | Dados para processamento |

**Retorno:** `bool — */`

---

##### `delete(int $id, int $tenantId): bool`

Remove um registro pelo ID.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `int` | ID do registro |
| `$tenantId` | `int` | ID do tenant |

**Retorno:** `bool — */`

---

##### `awardAchievement(int $userId, int $achievementId, int $tenantId): bool`

Award achievement.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$userId` | `int` | ID do usuário |
| `$achievementId` | `int` | Achievement id |
| `$tenantId` | `int` | ID do tenant |

**Retorno:** `bool — */`

---

##### `addPoints(int $userId, int $tenantId, int $points): void`

Add points.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$userId` | `int` | ID do usuário |
| `$tenantId` | `int` | ID do tenant |
| `$points` | `int` | Points |

**Retorno:** `void — */`

---

##### `getLeaderboard(int $tenantId, int $limit = 10): array`

Obtém dados específicos.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$tenantId` | `int` | ID do tenant |
| `$limit` | `int` | Limite de registros |

**Retorno:** `array — */`

---

##### `getUserAchievements(int $userId, int $tenantId): array`

Obtém dados específicos.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$userId` | `int` | ID do usuário |
| `$tenantId` | `int` | ID do tenant |

**Retorno:** `array — */`

---

##### `getUserScore(int $userId, int $tenantId): array`

Obtém dados específicos.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$userId` | `int` | ID do usuário |
| `$tenantId` | `int` | ID do tenant |

**Retorno:** `array — */`

---

## Attachment

**Tipo:** Class  
**Arquivo:** `app/models/Attachment.php`  
**Namespace:** `Akti\Models`  

Model de anexos/arquivos vinculados a registros.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe Attachment.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `readByEntity(string $entityType, int $entityId): array`

Read by entity.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$entityType` | `string` | Entity type |
| `$entityId` | `int` | Entity id |

**Retorno:** `array — */`

---

##### `readOne(int $id): ?array`

Retorna um registro pelo ID.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `int` | ID do registro |

**Retorno:** `array|null — */`

---

##### `create(array $data): int`

Cria um novo registro no banco de dados.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$data` | `array` | Dados para processamento |

**Retorno:** `int — */`

---

##### `delete(int $id): bool`

Remove um registro pelo ID.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `int` | ID do registro |

**Retorno:** `bool — */`

---

##### `countByEntity(string $entityType, int $entityId): int`

Conta registros com critérios opcionais.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$entityType` | `string` | Entity type |
| `$entityId` | `int` | Entity id |

**Retorno:** `int — */`

---

##### `readPaginated(int $page = 1, int $perPage = 20, string $entityType = ''): array`

Read paginated.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$page` | `int` | Número da página |
| `$perPage` | `int` | Registros por página |
| `$entityType` | `string` | Entity type |

**Retorno:** `array — */`

---

## AuditLog

**Tipo:** Class  
**Arquivo:** `app/models/AuditLog.php`  
**Namespace:** `Akti\Models`  

Model de log de auditoria para rastreamento de alterações.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe AuditLog.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `log(array $data): int`

Registra informação no log.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$data` | `array` | Dados para processamento |

**Retorno:** `int — */`

---

##### `readPaginated(int $page = 1, int $perPage = 50, array $filters = []): array`

Read paginated.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$page` | `int` | Número da página |
| `$perPage` | `int` | Registros por página |
| `$filters` | `array` | Filtros aplicados |

**Retorno:** `array — */`

---

### Funções auxiliares do arquivo

#### `readByEntity(string $entityType, int $entityId)`

---

#### `deleteOld(int $daysOld = 365)`

---

## Branch

**Tipo:** Class  
**Arquivo:** `app/models/Branch.php`  
**Namespace:** `Akti\Models`  

Model de filiais/unidades da empresa.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe Branch.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `create(array $data): int`

Cria um novo registro no banco de dados.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$data` | `array` | Dados para processamento |

**Retorno:** `int — */`

---

##### `readAll(int $tenantId): array`

Retorna todos os registros.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$tenantId` | `int` | ID do tenant |

**Retorno:** `array — */`

---

##### `readOne(int $id, int $tenantId): ?array`

Retorna um registro pelo ID.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `int` | ID do registro |
| `$tenantId` | `int` | ID do tenant |

**Retorno:** `array|null — */`

---

##### `update(int $id, int $tenantId, array $data): bool`

Atualiza um registro existente.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `int` | ID do registro |
| `$tenantId` | `int` | ID do tenant |
| `$data` | `array` | Dados para processamento |

**Retorno:** `bool — */`

---

##### `delete(int $id, int $tenantId): bool`

Remove um registro pelo ID.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `int` | ID do registro |
| `$tenantId` | `int` | ID do tenant |

**Retorno:** `bool — */`

---

## CalendarEvent

**Tipo:** Class  
**Arquivo:** `app/models/CalendarEvent.php`  
**Namespace:** `Akti\Models`  

Model de eventos do calendário.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe CalendarEvent.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `readByRange(string $start, string $end, ?int $userId = null): array`

Read by range.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$start` | `string` | Start |
| `$end` | `string` | End |
| `$userId` | `int|null` | ID do usuário |

**Retorno:** `array — */`

---

### Funções auxiliares do arquivo

#### `readOne(int $id)`

---

#### `create(array $data)`

---

#### `update(int $id, array $data)`

---

#### `delete(int $id)`

---

#### `getUpcoming(int $userId, int $limit = 10)`

---

#### `syncFromOrders(int $tenantId)`

---

#### `syncFromInstallments(int $tenantId)`

---

## CatalogLink

**Tipo:** Class  
**Arquivo:** `app/models/CatalogLink.php`  
**Namespace:** `Akti\Models`  

Model: CatalogLink

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |
| private | `$table` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor do model CatalogLink

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `create($orderId, $showPrices = true, $expiresAt = null, $requireConfirmation = false)`

Cria um novo link de catálogo para um pedido

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$orderId` | `int` | ID do pedido |
| `$showPrices` | `bool` | Se os preços devem ser exibidos (default: true) |
| `$expiresAt` | `string|null` | Data/hora de expiração (Y-m-d H:i:s) ou null |
| `$requireConfirmation` | `bool` | Se o cliente deve confirmar o orçamento (default: false) |

**Retorno:** `array|false — Retorna array com dados do link criado ou false em caso de erro`

---

### Funções auxiliares do arquivo

#### `findByToken($token)`

---

#### `findActiveByOrder($orderId)`

---

#### `deactivateByOrder($orderId)`

---

#### `deactivate($id)`

---

#### `updateShowPrices($id, $showPrices)`

---

#### `buildUrl($token)`

---

## Category

**Tipo:** Class  
**Arquivo:** `app/models/Category.php`  
**Namespace:** `Akti\Models`  

Model: Category

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |
| private | `$table_name` | Não |
| public | `$id` | Não |
| public | `$name` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor do model Category

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `readAll()`

Retorna todas as categorias ordenadas por nome

**Retorno:** `array — Array de categorias`

---

##### `countAll(): int`

Retorna a quantidade total de categorias.

**Retorno:** `int — */`

---

##### `readPaginated(int $page = 1, int $perPage = 15): array`

Retorna categorias paginadas.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$page` | `int` | Página atual (1-based) |
| `$perPage` | `int` | Registros por página |

**Retorno:** `array — */`

---

### Funções auxiliares do arquivo

#### `create()`

---

#### `getSubcategories($categoryId)`

---

#### `getCategory($categoryId)`

---

#### `update($id, $name, $showInStore = null, $freeShipping = null)`

---

#### `delete($id)`

---

#### `countProducts($categoryId)`

---

#### `readAllWithCount()`

---

#### `readAllVisible()`

---

#### `getVisibleSubcategories(int $categoryId)`

---

## CategoryGrade

**Tipo:** Class  
**Arquivo:** `app/models/CategoryGrade.php`  
**Namespace:** `Akti\Models`  

Model: CategoryGrade

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor da classe CategoryGrade.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |

---

##### `getCategoryGrades($categoryId)`

Retorna todas as grades de uma categoria (com info de tipo)

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$categoryId` | `int` | ID da categoria |

**Retorno:** `array — Array de grades (fetchAll)`

---

##### `getCategoryGradesWithValues($categoryId)`

Retorna todas as grades de uma categoria com seus valores

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$categoryId` | `int` | ID da categoria |

**Retorno:** `array — Array de grades, cada uma com 'values'`

---

##### `getCategoryGradeValues($categoryGradeId)`

Retorna todos os valores de uma grade de categoria

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$categoryGradeId` | `int` | ID da grade de categoria |

**Retorno:** `array — Array de valores (fetchAll)`

---

##### `addGradeToCategory($categoryId, $gradeTypeId, $sortOrder = 0)`

Adiciona uma grade a uma categoria

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$categoryId` | `int` | ID da categoria |
| `$gradeTypeId` | `int` | ID do tipo de grade |
| `$sortOrder` | `int` | Ordem de exibição (default: 0) |

**Retorno:** `int|false — ID da grade inserida ou false em caso de erro`

---

##### `addCategoryGradeValue($categoryGradeId, $value, $sortOrder = 0)`

Adiciona um valor a uma grade de categoria

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$categoryGradeId` | `int` | ID da grade de categoria |
| `$value` | `string` | Valor a ser inserido |
| `$sortOrder` | `int` | Ordem de exibição (default: 0) |

**Retorno:** `int|false — ID do valor inserido ou false em caso de erro`

---

##### `saveCategoryGrades($categoryId, $gradesData)`

Salva todas as grades e valores de uma categoria a partir de dados de formulário

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$categoryId` | `int` | ID da categoria |
| `$gradesData` | `array` | Dados das grades e valores |

**Retorno:** `void — * Evento disparado: 'model.category_grade.saved' com ['category_id', 'grades_count']`

---

##### `generateCategoryCombinations($categoryId)`

Gera conteúdo ou dados.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$categoryId` | `mixed` | Category id |

---

##### `getCategoryCombinations($categoryId)`

Retorna todas as combinações de grades de uma categoria

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$categoryId` | `int` | ID da categoria |

**Retorno:** `array — Array de combinações (fetchAll)`

---

##### `toggleCategoryCombination($combinationId, $isActive)`

Ativa ou desativa uma combinação de grades de categoria

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$combinationId` | `int` | ID da combinação |
| `$isActive` | `bool` | Ativo ou não |

**Retorno:** `bool — Retorna true se atualizado com sucesso`

---

##### `categoryHasGrades($categoryId)`

Verifica se uma categoria possui grades

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$categoryId` | `int` | ID da categoria |

**Retorno:** `bool — Retorna true se possui grades`

---

##### `getSubcategoryGrades($subcategoryId)`

Retorna todas as grades de uma subcategoria (com info de tipo)

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$subcategoryId` | `int` | ID da subcategoria |

**Retorno:** `array — Array de grades (fetchAll)`

---

##### `getSubcategoryGradesWithValues($subcategoryId)`

Retorna todas as grades de uma subcategoria com seus valores

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$subcategoryId` | `int` | ID da subcategoria |

**Retorno:** `array — Array de grades, cada uma com 'values'`

---

##### `getSubcategoryGradeValues($subcategoryGradeId)`

Retorna todos os valores de uma grade de subcategoria

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$subcategoryGradeId` | `int` | ID da grade de subcategoria |

**Retorno:** `array — Array de valores (fetchAll)`

---

##### `addGradeToSubcategory($subcategoryId, $gradeTypeId, $sortOrder = 0)`

Adiciona uma grade a uma subcategoria

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$subcategoryId` | `int` | ID da subcategoria |
| `$gradeTypeId` | `int` | ID do tipo de grade |
| `$sortOrder` | `int` | Ordem de exibição (default: 0) |

**Retorno:** `int|false — ID da grade inserida ou false em caso de erro`

---

##### `addSubcategoryGradeValue($subcategoryGradeId, $value, $sortOrder = 0)`

Adiciona um valor a uma grade de subcategoria

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$subcategoryGradeId` | `int` | ID da grade de subcategoria |
| `$value` | `string` | Valor a ser inserido |
| `$sortOrder` | `int` | Ordem de exibição (default: 0) |

**Retorno:** `int|false — ID do valor inserido ou false em caso de erro`

---

##### `saveSubcategoryGrades($subcategoryId, $gradesData)`

Salva todas as grades e valores de uma subcategoria a partir de dados de formulário

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$subcategoryId` | `int` | ID da subcategoria |
| `$gradesData` | `array` | Dados das grades e valores |

**Retorno:** `void — * Evento disparado: 'model.subcategory_grade.saved' com ['subcategory_id', 'grades_count']`

---

##### `generateSubcategoryCombinations($subcategoryId)`

Gera todas as combinações de grades de uma subcategoria e salva no banco

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$subcategoryId` | `int` | ID da subcategoria |

**Retorno:** `array — Array de combinações geradas`

---

##### `getSubcategoryCombinations($subcategoryId)`

Retorna todas as combinações de grades de uma subcategoria

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$subcategoryId` | `int` | ID da subcategoria |

**Retorno:** `array — Array de combinações (fetchAll)`

---

##### `toggleSubcategoryCombination($combinationId, $isActive)`

Ativa ou desativa uma combinação de grades de subcategoria

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$combinationId` | `int` | ID da combinação |
| `$isActive` | `bool` | Ativo ou não |

**Retorno:** `bool — Retorna true se atualizado com sucesso`

---

##### `subcategoryHasGrades($subcategoryId)`

Verifica se uma subcategoria possui grades

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$subcategoryId` | `int` | ID da subcategoria |

**Retorno:** `bool — Retorna true se possui grades`

---

##### `getInheritedGrades($subcategoryId = null, $categoryId = null)`

Retorna grades herdadas para um produto com base em subcategoria ou categoria.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$subcategoryId` | `int|null` | ID da subcategoria |
| `$categoryId` | `int|null` | ID da categoria |

**Retorno:** `array — ['grades' => [...], 'source' => 'subcategory'|'category'|null, 'inactive_keys' => [...]]`

---

##### `convertInheritedToProductFormat($inheritedGrades)`

Converte grades herdadas para o formato esperado por saveProductGrades().

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$inheritedGrades` | `array` | Saída de getInheritedGrades()['grades'] |

**Retorno:** `array — [['grade_type_id' => X, 'values' => ['P','M','G']], ...]`

---

#### Métodos Private

##### `getCategoryGradeId($categoryId, $gradeTypeId)`

Obtém dados específicos.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$categoryId` | `mixed` | Category id |
| `$gradeTypeId` | `mixed` | Grade type id |

---

##### `getSubcategoryGradeId($subcategoryId, $gradeTypeId)`

Retorna o ID da grade de subcategoria

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$subcategoryId` | `int` | ID da subcategoria |
| `$gradeTypeId` | `int` | ID do tipo de grade |

**Retorno:** `int|false — ID encontrado ou false se não existir`

---

##### `cartesianProduct($arrays)`

Produto cartesiano de múltiplos arrays

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$arrays` | `array` | Arrays de entrada |

**Retorno:** `array — Produto cartesiano`

---

## CheckoutToken

**Tipo:** Class  
**Arquivo:** `app/models/CheckoutToken.php`  
**Namespace:** `Akti\Models`  

Model de tokens de checkout para pagamento seguro.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe CheckoutToken.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `create(array $data): int`

Cria um novo token de checkout.

**Retorno:** `int — ID inserido`

---

##### `findByToken(string $token): ?array`

Busca token pelo hash (com JOIN em orders).

---

##### `findById(int $id): ?array`

Busca token pelo ID.

---

##### `findByOrder(int $orderId): array`

Lista tokens de um pedido.

---

##### `getActiveByOrder(int $orderId): ?array`

Busca token ativo de um pedido.

---

##### `markUsed(int $id, string $usedMethod = '', string $externalId = ''): bool`

Marca token como usado (atômico — só atualiza se status='active').

**Retorno:** `bool — true se atualizou, false se já usado (race condition prevenida)`

---

##### `markUsedByOrder(int $orderId, string $usedMethod = '', string $externalId = '', ?int $installmentId = null): bool`

Marca token como usado pelo order_id (para webhooks).

---

##### `markExpired(int $id): bool`

Marca token como expirado.

---

##### `cancel(int $id): bool`

Cancela token.

---

##### `expireAll(): int`

Expira todos os tokens vencidos.

**Retorno:** `int — Quantidade expirada`

---

##### `updateIp(int $id, string $ip): bool`

Grava IP do visitante.

---

## Commission

**Tipo:** Class  
**Arquivo:** `app/models/Commission.php`  
**Namespace:** `Akti\Models`  

Model: Commission

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe Commission.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `getAllFormas(): array`

Retorna todas as formas de comissão.

---

##### `getForma(int $id): ?array`

Retorna uma forma de comissão pelo ID.

---

##### `createForma(array $data): int`

Cria uma nova forma de comissão.

---

##### `updateForma(int $id, array $data): bool`

Atualiza uma forma de comissão.

---

##### `deleteForma(int $id): bool`

Remove uma forma de comissão.

---

##### `getGrupoFormas(?int $groupId = null): array`

Retorna todas as vinculações de grupo.

---

##### `linkGrupoForma(int $groupId, int $formaId): bool`

Vincula uma forma de comissão a um grupo.

---

##### `unlinkGrupoForma(int $id): bool`

Remove vínculo grupo-forma.

---

##### `getUsuarioFormas(?int $userId = null): array`

Retorna todas as vinculações de usuário.

---

##### `linkUsuarioForma(int $userId, int $formaId): bool`

Vincula uma forma de comissão a um usuário.

---

##### `unlinkUsuarioForma(int $id): bool`

Remove vínculo usuário-forma.

---

##### `getComissaoProdutos(): array`

Retorna todas as regras de comissão por produto.

---

##### `getComissaoProduto(int $productId): ?array`

Retorna regra de comissão para um produto específico.

---

##### `getComissaoCategoria(int $categoryId): ?array`

Retorna regra de comissão para uma categoria.

---

##### `saveComissaoProduto(array $data): int`

Cria/atualiza regra de comissão por produto.

---

##### `deleteComissaoProduto(int $id): bool`

Remove regra de comissão por produto.

---

##### `getFaixas(int $formaId): array`

Retorna faixas de uma forma de comissão.

---

##### `saveFaixas(int $formaId, array $faixas): bool`

Salva faixas para uma forma de comissão (replace all).

---

##### `registrarComissao(array $data): int`

Registra uma comissão calculada.

---

##### `getComissoesRegistradas(array $filters = [], int $page = 1, int $perPage = 25): array`

Retorna comissões registradas com filtros e paginação.

---

##### `getComissaoRegistrada(int $id): ?array`

Retorna uma comissão registrada por ID.

---

##### `updateComissaoStatus(int $id, string $status, ?int $approvedBy = null): bool`

Atualiza status de uma comissão registrada.

---

##### `getVendedoresComPendentes(): array`

Retorna lista de vendedores com comissões pendentes (para o modal de lote).

**Retorno:** `array — [['user_id' => int, 'user_name' => string, 'pendentes_aprovacao' => int,`

---

##### `getComissoesPendentesPorVendedor(int $userId, ?string $statusFilter = null): array`

Retorna comissões pendentes de um vendedor específico.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$userId` | `int` | * @param string|null $statusFilter  'aprovacao' | 'pagamento' | null (todos pendentes) |

**Retorno:** `array — */`

---

### Funções auxiliares do arquivo

#### `existeComissao(int $orderId, int $userId)`

---

#### `getConfig()`

---

#### `getConfigValue(string $key, $default = null)`

---

#### `saveConfig(string $key, string $value)`

---

#### `getDashboardSummary(?int $month = null, ?int $year = null)`

---

#### `getChartData(int $months = 6)`

---

#### `getRegraUsuario(int $userId)`

---

#### `getRegraGrupo(int $userId)`

---

#### `getUsuariosComRegras()`

---

## CompanySettings

**Tipo:** Class  
**Arquivo:** `app/models/CompanySettings.php`  
**Namespace:** `Akti\Models`  

Model: CompanySettings

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |
| private | `$table` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor do model

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO |

---

##### `getAll()`

Retorna todas as configurações como array associativo

**Retorno:** `array — Array associativo de configurações`

---

### Funções auxiliares do arquivo

#### `get($key, $default = '')`

---

#### `set($key, $value)`

---

#### `saveAll($data)`

---

#### `getFormattedAddress()`

---

#### `formatCustomerAddress($jsonOrArray)`

---

#### `formatAddressFromArray($data, $city = '', $state = '')`

---

## Customer

**Tipo:** Class  
**Arquivo:** `app/models/Customer.php`  
**Namespace:** `Akti\Models`  

Model: Customer

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |
| private | `$table_name` | Não |
| protected | `$id` | Não |
| protected | `$name` | Não |
| protected | `$email` | Não |
| protected | `$phone` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor do model

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO |

---

##### `readAll(): array`

Retorna todos os clientes (exclui soft-deleted)

**Retorno:** `array — Lista de clientes`

---

### Funções auxiliares do arquivo

#### `readPaginated(int $page = 1, int $perPage = 15)`

---

#### `readOne($id)`

---

#### `findByEmail(string $email)`

---

#### `create($data)`

---

#### `update($data)`

---

#### `delete($id)`

---

#### `softDelete(int $id)`

---

#### `restore(int $id)`

---

#### `countAll()`

---

#### `readPaginatedFiltered(int $page = 1, int $perPage = 15, ?string $search = null, array $filters = [])`

---

#### `searchForSelect2(string $term, int $limit = 10)`

---

#### `searchPaginated(string $query = '', int $page = 1, int $perPage = 20)`

---

#### `findByDocument(string $document)`

---

#### `checkDuplicate(string $document, ?int $excludeId = null)`

---

#### `generateCode()`

---

#### `updateStatus(int $id, string $status)`

---

#### `bulkUpdateStatus(array $ids, string $status)`

---

#### `bulkDelete(array $ids)`

---

#### `getCustomerStats(int $id)`

---

#### `getDistinctCities()`

---

#### `getDistinctStates()`

---

#### `getAllTags()`

---

#### `exportAll(array $filters = [])`

---

#### `importFromMapped(array $data)`

---

#### `updateFromImport(array $data)`

---

## CustomerContact

**Tipo:** Class  
**Arquivo:** `app/models/CustomerContact.php`  
**Namespace:** `Akti\Models`  

Model: CustomerContact

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |
| private | `$table_name` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor do model

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO |

---

##### `create(array $data): int`

Cria um novo contato para um cliente.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$data` | `array` | Dados do contato: customer_id, name, role, email, phone, is_primary, notes |

**Retorno:** `int — ID do contato criado`

---

### Funções auxiliares do arquivo

#### `readByCustomer(int $customerId)`

---

#### `readOne(int $id)`

---

#### `update(array $data)`

---

#### `delete(int $id)`

---

#### `setPrimary(int $id, int $customerId)`

---

#### `countByCustomer(int $customerId)`

---

#### `getPrimary(int $customerId)`

---

#### `clearPrimary(int $customerId)`

---

## DashboardWidget

**Tipo:** Class  
**Arquivo:** `app/models/DashboardWidget.php`  
**Namespace:** `Akti\Models`  

Model: DashboardWidget

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |
| private | `$table` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO |

---

##### `static getAvailableWidgets(): array`

Retorna a lista de widgets disponíveis (estática).

**Retorno:** `array — */`

---

##### `getByGroup(int $groupId): array`

Retorna a configuração de widgets para um grupo.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$groupId` | `int` | ID do grupo |

**Retorno:** `array — Lista de registros [widget_key, sort_order, is_visible]`

---

### Funções auxiliares do arquivo

#### `hasConfig(int $groupId)`

---

#### `getVisibleWidgetsForGroup(int $groupId)`

---

#### `saveForGroup(int $groupId, array $widgets)`

---

#### `resetGroup(int $groupId)`

---

## EmailCampaign

**Tipo:** Class  
**Arquivo:** `app/models/EmailCampaign.php`  
**Namespace:** `Akti\Models`  

Model de campanhas de email marketing.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe EmailCampaign.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `readPaginated(int $page = 1, int $perPage = 15, string $search = ''): array`

Read paginated.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$page` | `int` | Número da página |
| `$perPage` | `int` | Registros por página |
| `$search` | `string` | Termo de busca |

**Retorno:** `array — */`

---

### Funções auxiliares do arquivo

#### `readOne(int $id)`

---

#### `create(array $data)`

---

#### `update(int $id, array $data)`

---

#### `delete(int $id)`

---

#### `getTemplates()`

---

#### `getTemplate(int $id)`

---

#### `createTemplate(array $data)`

---

#### `deleteTemplate(int $id)`

---

#### `updateTemplate(int $id, array $data)`

---

#### `getLogs(int $campaignId)`

---

#### `getStats(int $campaignId)`

---

## Equipment

**Tipo:** Class  
**Arquivo:** `app/models/Equipment.php`  
**Namespace:** `Akti\Models`  

Model de equipamentos/máquinas de produção.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe Equipment.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `create(array $data): int`

Cria um novo registro no banco de dados.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$data` | `array` | Dados para processamento |

**Retorno:** `int — */`

---

##### `readAll(int $tenantId): array`

Retorna todos os registros.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$tenantId` | `int` | ID do tenant |

**Retorno:** `array — */`

---

##### `readPaginated(int $tenantId, int $page = 1, int $perPage = 20, array $filters = []): array`

Read paginated.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$tenantId` | `int` | ID do tenant |
| `$page` | `int` | Número da página |
| `$perPage` | `int` | Registros por página |
| `$filters` | `array` | Filtros aplicados |

**Retorno:** `array — */`

---

### Funções auxiliares do arquivo

#### `readOne(int $id, int $tenantId)`

---

#### `update(int $id, int $tenantId, array $data)`

---

#### `delete(int $id, int $tenantId)`

---

#### `getSchedules(int $equipmentId, int $tenantId)`

---

#### `getLogs(int $equipmentId, int $tenantId, int $limit = 20)`

---

#### `createSchedule(array $data)`

---

#### `createLog(array $data)`

---

#### `getUpcomingMaintenance(int $tenantId, int $days = 30)`

---

#### `getDashboardStats(int $tenantId)`

---

## EsgMetric

**Tipo:** Class  
**Arquivo:** `app/models/EsgMetric.php`  
**Namespace:** `Akti\Models`  

Model de métricas ESG (Environmental, Social, Governance).

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe EsgMetric.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `create(array $data): int`

Cria um novo registro no banco de dados.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$data` | `array` | Dados para processamento |

**Retorno:** `int — */`

---

##### `readAll(int $tenantId): array`

Retorna todos os registros.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$tenantId` | `int` | ID do tenant |

**Retorno:** `array — */`

---

##### `readOne(int $id, int $tenantId): ?array`

Retorna um registro pelo ID.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `int` | ID do registro |
| `$tenantId` | `int` | ID do tenant |

**Retorno:** `array|null — */`

---

##### `update(int $id, int $tenantId, array $data): bool`

Atualiza um registro existente.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `int` | ID do registro |
| `$tenantId` | `int` | ID do tenant |
| `$data` | `array` | Dados para processamento |

**Retorno:** `bool — */`

---

##### `delete(int $id, int $tenantId): bool`

Remove um registro pelo ID.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `int` | ID do registro |
| `$tenantId` | `int` | ID do tenant |

**Retorno:** `bool — */`

---

##### `addRecord(array $data): int`

Add record.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$data` | `array` | Dados para processamento |

**Retorno:** `int — */`

---

##### `getRecords(int $tenantId, array $filters = []): array`

Obtém dados específicos.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$tenantId` | `int` | ID do tenant |
| `$filters` | `array` | Filtros aplicados |

**Retorno:** `array — */`

---

### Funções auxiliares do arquivo

#### `saveTarget(array $data)`

---

#### `getTargets(int $tenantId)`

---

#### `getDashboardSummary(int $tenantId)`

---

## Financial

**Tipo:** Class  
**Arquivo:** `app/models/Financial.php`  
**Namespace:** `Akti\Models`  

Model: Financial

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |
| private | `$overdueUpdatedThisRequest` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor do model

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO |

---

##### `getSummary($month = null, $year = null)`

Retorna resumo geral do financeiro

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$month` | `int|null` | Mês |
| `$year` | `int|null` | Ano |

**Retorno:** `array — Resumo financeiro`

---

##### `getChartData($months = 6)`

Retorna dados para gráfico de receita x despesa dos últimos N meses

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$months` | `int` | Quantidade de meses |

**Retorno:** `array — Dados para gráfico`

---

### Funções auxiliares do arquivo

#### `getOrdersPendingPayment()`

---

#### `getOrdersWithInstallments($filters = [])`

---

#### `getAllInstallments($filters = [])`

---

#### `getInstallments($orderId)`

---

#### `countInstallments($orderId)`

---

#### `deleteInstallmentsByOrder($orderId)`

---

#### `generateInstallments($orderId, $totalAmount, $numInstallments, $downPayment = 0, $startDate = null, $dueDates = [])`

---

#### `payInstallment($installmentId, $data, $autoConfirm = false)`

---

#### `createRemainingInstallment($originalInstallmentId, $remainingAmount, $dueDate = null)`

---

#### `confirmInstallment($installmentId, $userId)`

---

#### `cancelInstallment($installmentId, $userId = null)`

---

#### `updateOrderPaymentStatus($orderId)`

---

#### `updateOverdueInstallments()`

---

#### `getInstallmentById($id)`

---

#### `updateInstallmentAmount($installmentId, $amount)`

---

#### `getInstallmentBasic($installmentId)`

---

#### `hasAnyPaidInstallment($orderId)`

---

#### `updateInstallmentDueDate($installmentId, $dueDate)`

---

#### `updateOrderFinancialFields($orderId, $data)`

---

#### `saveAttachment($installmentId, $path)`

---

#### `removeAttachment($installmentId)`

---

#### `mergeInstallments(array $installmentIds, $dueDate)`

---

#### `splitInstallment($installmentId, $parts, $firstDueDate = null)`

---

#### `renumberInstallments($orderId)`

---

#### `getOrderPipelineStage($orderId)`

---

#### `getOrderFinancialTotals($orderId)`

---

#### `getPendingConfirmations()`

---

#### `getUpcomingInstallments($days = 7)`

---

#### `getOverdueInstallments()`

---

#### `addTransaction($data)`

---

#### `getTransactionById($id)`

---

#### `updateTransaction($id, $data)`

---

#### `getTransactions($filters = [])`

---

#### `getTransactionsPaginated($filters = [], $page = 1, $perPage = 25)`

---

#### `getAllInstallmentsPaginated($filters = [], $page = 1, $perPage = 25)`

---

#### `importCsvMapped($rows, $mapping, $selectedRows, $mode = 'registro', $userId = null)`

---

#### `parseCsvDate($dateStr)`

---

#### `deleteTransaction($id, ?string $reason = null)`

---

#### `restoreTransaction(int $id)`

---

#### `hasSoftDeleteColumn()`

---

#### `getCategories()`

---

#### `getInternalCategories()`

---

#### `getAllCategoriesDetailed()`

---

#### `clearCategoriesCache()`

---

#### `importOfx($filePath, $mode = 'registro', $userId = null)`

---

#### `parseOfxTransactions($content)`

---

#### `extractOfxTag($block, $tag)`

---

#### `parseOfxDate($dateStr)`

---

## FinancialReport

**Tipo:** Class  
**Arquivo:** `app/models/FinancialReport.php`  
**Namespace:** `Akti\Models`  

FinancialReport — relatórios e dados de dashboard.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor da classe FinancialReport.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |

---

##### `getSummary($month = null, $year = null): array`

Proxy para Financial::getSummary().

---

##### `getChartData(int $months = 6): array`

Proxy para Financial::getChartData().

---

##### `getOrderFinancialTotals(int $orderId): array`

Proxy para Financial::getOrderFinancialTotals().

---

##### `getPendingConfirmations(): array`

Proxy para Financial::getPendingConfirmations().

---

##### `getUpcomingInstallments(int $days = 7): array`

Proxy para Financial::getUpcomingInstallments().

---

##### `getOverdueInstallments(): array`

Proxy para Financial::getOverdueInstallments().

---

## FinancialSchema

**Tipo:** Class  
**Arquivo:** `app/models/FinancialSchema.php`  
**Namespace:** `Akti\Models`  

FinancialSchema — verificação de schema/tabelas financeiras.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor da classe FinancialSchema.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |

---

##### `hasSoftDeleteColumn(): bool`

Proxy para Financial::hasSoftDeleteColumn().

---

## IbptaxModel

**Tipo:** Class  
**Arquivo:** `app/models/IbptaxModel.php`  
**Namespace:** `Akti\Models`  

Model: IbptaxModel

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |
| private | `$table` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor da classe IbptaxModel.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |

---

##### `getByNcm(string $ncm, ?string $ex = null): ?array`

Busca alíquotas IBPTax por NCM.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$ncm` | `string` | NCM de 8 dígitos |
| `$ex` | `string|null` | Exceção do NCM (opcional) |

**Retorno:** `array|null — Dados de alíquotas ou null se não encontrado`

---

### Funções auxiliares do arquivo

#### `calculateTaxApprox(string $ncm, float $valor, string $origem = '0')`

---

#### `buildTributosMensagem(float $vTotTribTotal, string $fonte = '')`

---

#### `importFromCsv(string $csvPath)`

---

#### `truncate()`

---

#### `getStats()`

---

## ImportBatch

**Tipo:** Class  
**Arquivo:** `app/models/ImportBatch.php`  
**Namespace:** `Akti\Models`  

Model: ImportBatch

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |
| private | `$table` | Não |
| private | `$itemsTable` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor da classe ImportBatch.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |

---

##### `create(array $data): int`

Cria um novo lote de importação.

---

### Funções auxiliares do arquivo

#### `updateProgress(int $batchId, int $progress, int $imported = 0, int $skipped = 0, int $errorCount = 0, int $warningCount = 0)`

---

#### `finalize(int $batchId, string $status, int $importedCount = 0, int $updatedCount = 0, int $skippedCount = 0, ?string $errorsJson = null, ?string $warningsJson = null)`

---

#### `addItem(int $batchId, int $entityId, string $action = 'created', ?string $originalData = null, ?int $lineNumber = null)`

---

#### `findById(int $id)`

---

#### `listByTenant(int $tenantId, string $entityType = 'customers', int $limit = 20)`

---

#### `getItems(int $batchId)`

---

#### `getCreatedItems(int $batchId)`

---

#### `getItemsWithEntity(int $batchId, string $entityType = 'customers', int $limit = 200, int $offset = 0)`

---

#### `markUndone(int $batchId, int $userId)`

---

#### `getProgress(int $batchId)`

---

## ImportMappingProfile

**Tipo:** Class  
**Arquivo:** `app/models/ImportMappingProfile.php`  
**Namespace:** `Akti\Models`  

Model: ImportMappingProfile

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |
| private | `$table` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor da classe ImportMappingProfile.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |

---

##### `listByTenant(int $tenantId, string $entityType = 'customers'): array`

Lista perfis do tenant.

---

### Funções auxiliares do arquivo

#### `findById(int $id)`

---

#### `create(array $data)`

---

#### `update(int $id, array $data)`

---

#### `delete(int $id)`

---

#### `clearDefault(int $tenantId, string $entityType)`

---

## Installment

**Tipo:** Class  
**Arquivo:** `app/models/Installment.php`  
**Namespace:** `Akti\Models`  

Model: Installment

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |
| private | `$overdueUpdatedThisRequest` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe Installment.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `getById(int $id): ?array`

Busca uma parcela pelo ID (dados completos com join).

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `int` | * @return array|null |

**Retorno:** `array|null — */`

---

##### `getBasic(int $id): ?array`

Retorna dados básicos de uma parcela (id, order_id, amount, installment_number).

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `int` | * @return array|null |

**Retorno:** `array|null — */`

---

##### `getByOrderId(int $orderId): array`

Retorna parcelas de um pedido.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$orderId` | `int` | * @return array |

**Retorno:** `array — */`

---

##### `countByOrderId(int $orderId): int`

Conta parcelas de um pedido.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$orderId` | `int` | * @return int |

**Retorno:** `int — */`

---

##### `hasAnyPaid(int $orderId): bool`

Verifica se existe alguma parcela paga para um pedido.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$orderId` | `int` | * @return bool |

**Retorno:** `bool — */`

---

##### `getPendingConfirmations(): array`

Retorna parcelas pendentes de confirmação.

**Retorno:** `array — */`

---

##### `getUpcoming(int $days = 7): array`

Retorna próximas parcelas a vencer.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$days` | `int` | * @return array |

**Retorno:** `array — */`

---

##### `getOverdue(): array`

Retorna parcelas vencidas não pagas.

**Retorno:** `array — */`

---

##### `getPaginated(array $filters = [], int $page = 1, int $perPage = 25): array`

Lista parcelas com filtros e paginação.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$filters` | `array` | * @param int $page |
| `$perPage` | `int` | * @return array |

**Retorno:** `array — */`

---

##### `deleteByOrderId(int $orderId): int`

Remove todas as parcelas de um pedido (somente se não houver pagas).

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$orderId` | `int` | * @return int Quantidade removida |

**Retorno:** `int — Quantidade removida`

---

##### `generate(int $orderId, float $totalAmount, int $numInstallments, float $downPayment = 0, ?string $startDate = null, array $dueDates = []): bool`

Gera parcelas para um pedido.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$orderId` | `int` | * @param float $totalAmount |
| `$numInstallments` | `int` | * @param float $downPayment |
| `$startDate` | `string|null` | * @param array $dueDates |

**Retorno:** `bool — */`

---

### Funções auxiliares do arquivo

#### `pay(int $installmentId, array $data, bool $autoConfirm = false)`

---

#### `createRemaining(int $originalInstallmentId, float $remainingAmount, ?string $dueDate = null)`

---

#### `confirm(int $installmentId, ?int $userId = null)`

---

#### `cancel(int $installmentId, ?int $userId = null)`

---

#### `updateAmount(int $installmentId, float $amount)`

---

#### `updateDueDate(int $installmentId, string $dueDate)`

---

#### `saveAttachment(int $installmentId, ?string $path)`

---

#### `removeAttachment(int $installmentId)`

---

#### `merge(array $installmentIds, string $dueDate)`

---

#### `split(int $installmentId, int $parts, ?string $firstDueDate = null)`

---

#### `updateOrderPaymentStatus(int $orderId)`

---

#### `updateOverdue()`

---

#### `renumberInstallments(int $orderId)`

---

#### `getOrderPipelineStage(int $orderId)`

---

#### `getOrderFinancialTotals(int $orderId)`

---

#### `updateOrderFinancialFields(int $orderId, array $data)`

---

## IpGuard

**Tipo:** Class  
**Arquivo:** `app/models/IpGuard.php`  
**Namespace:** `Akti\Models`  

IpGuard — Detecção de flood 404 e blacklist automática de IPs.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Sim |

### Métodos

#### Métodos Public

##### `static getClientIp(): string`

Obtém o IP real do visitante, considerando proxies confiáveis.

---

##### `static isBlacklisted(?string $ip = null): bool`

Verifica se um IP está na blacklist ativa (não expirada).

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$ip` | `string|null` | IP a verificar (null = IP do visitante atual) |

**Retorno:** `bool — */`

---

##### `static register404Hit(): void`

Registra um hit 404 para o IP atual.

---

##### `static blacklistIp(string $ip, int $hits = 0, string $reason = '404_flood', ?int $hours = null): void`

Adiciona um IP à blacklist.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$ip` | `string` | Endereço IP |
| `$hits` | `int` | Número de hits que motivaram o bloqueio |
| `$reason` | `string` | Motivo (ex: '404_flood') |
| `$hours` | `int|null` | Duração em horas (null = usa BLOCK_HOURS; 0 = permanente) |

---

#### Métodos Private

##### `static getConnection(): ?PDO`

Obtém (ou cria) uma conexão PDO com o banco master (akti_master).

---

##### `static sanitizePath(string $path): string`

Sanitiza e trunca o path da requisição.

---

##### `static sanitizeUserAgent(?string $ua): ?string`

Sanitiza e trunca o user-agent.

---

### Funções auxiliares do arquivo

#### `purgeOldHits(int $days = 7)`

---

## Logger

**Tipo:** Class  
**Arquivo:** `app/models/Logger.php`  
**Namespace:** `Akti\Models`  

Model de logging legado.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |
| private | `$table_name` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor da classe Logger.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |

---

##### `log($action, $details = "", $user_id = null)`

Registra informação no log.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$action` | `mixed` | Action |
| `$details` | `mixed` | Details |
| `$user_id` | `mixed` | ID do usuário |

---

##### `getPaginated(array $filters = [], int $page = 1, int $perPage = 50): array`

Lista logs com paginação e filtros.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$filters` | `array` | Filtros opcionais (action, user_id, date_from, date_to, search) |
| `$page` | `int` | Página (1-based) |
| `$perPage` | `int` | Itens por página |

**Retorno:** `array{data: — array, total: int, page: int, perPage: int, totalPages: int}`

---

### Funções auxiliares do arquivo

#### `getDistinctActions()`

---

## LoginAttempt

**Tipo:** Class  
**Arquivo:** `app/models/LoginAttempt.php`  
**Namespace:** `Akti\Models`  

LoginAttempt — Proteção contra força bruta

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor da classe LoginAttempt.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |

---

##### `static getSiteKey(): string`

Obtém dados específicos.

**Retorno:** `string — */`

---

##### `static getSecretKey(): string`

Obtém dados específicos.

**Retorno:** `string — */`

---

##### `record(string $ip, string $email, bool $success): bool`

Registra uma tentativa de login (falha ou sucesso).

---

##### `countRecentFailures(string $ip, string $email): int`

Conta tentativas falhas de um IP+email na janela de tempo.

---

##### `checkLockout(string $ip, string $email): array`

Verifica se o IP+email está bloqueado.

---

##### `requiresCaptcha(string $ip, string $email): bool`

Verifica se o captcha deve ser exibido (>= 3 falhas recentes).

---

##### `validateCaptcha(string $captchaResponse, string $ip): bool`

Valida a resposta do reCAPTCHA v2 com a API do Google.

---

##### `purgeOld(): int`

Remove tentativas com mais de CLEANUP_MINUTES (padrão: 60 min).

---

##### `clearFailures(string $ip, string $email): bool`

Limpa falhas de um IP+email específico (após login bem-sucedido).

---

##### `static getClientIp(): string`

Retorna o IP real do cliente, considerando proxies.

---

## AdminLog

**Tipo:** Class  
**Arquivo:** `app/models/Master/AdminLog.php`  
**Namespace:** `Akti\Models\Master`  

Model de log de ações administrativas do painel master.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe AdminLog.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `log(int $adminId, string $action, ?string $targetType = null, ?int $targetId = null, ?string $details = null): void`

Registra informação no log.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$adminId` | `int` | Admin id |
| `$action` | `string` | Action |
| `$targetType` | `string|null` | Target type |
| `$targetId` | `int|null` | Target id |
| `$details` | `string|null` | Details |

**Retorno:** `void — */`

---

##### `readRecent(int $limit = 20): array`

Read recent.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$limit` | `int` | Limite de registros |

**Retorno:** `array — */`

---

## AdminUser

**Tipo:** Class  
**Arquivo:** `app/models/Master/AdminUser.php`  
**Namespace:** `Akti\Models\Master`  

Model de usuários administradores do painel master.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe AdminUser.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `findByEmail(string $email): array`

Busca registro(s) com critérios específicos.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$email` | `string` | Endereço de email |

**Retorno:** `array — */`

---

##### `findById(int $id): array`

Busca registro(s) com critérios específicos.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `int` | ID do registro |

**Retorno:** `array — */`

---

##### `updateLastLogin(int $id): void`

Update last login.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `int` | ID do registro |

**Retorno:** `void — */`

---

##### `updatePassword(int $id, string $hashedPassword): void`

Update password.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `int` | ID do registro |
| `$hashedPassword` | `string` | Hashed password |

**Retorno:** `void — */`

---

##### `readAll(): array`

Retorna todos os registros.

**Retorno:** `array — */`

---

##### `create(array $data): string`

Cria um novo registro no banco de dados.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$data` | `array` | Dados para processamento |

**Retorno:** `string — */`

---

##### `update(int $id, array $data): void`

Atualiza um registro existente.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `int` | ID do registro |
| `$data` | `array` | Dados para processamento |

**Retorno:** `void — */`

---

##### `delete(int $id): void`

Remove um registro pelo ID.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `int` | ID do registro |

**Retorno:** `void — */`

---

##### `emailExists(string $email, ?int $excludeId = null): bool`

Email exists.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$email` | `string` | Endereço de email |
| `$excludeId` | `int|null` | Exclude id |

**Retorno:** `bool — */`

---

##### `countByRole(): array`

Conta registros com critérios opcionais.

**Retorno:** `array — */`

---

## Backup

**Tipo:** Class  
**Arquivo:** `app/models/Master/Backup.php`  
**Namespace:** `Akti\Models\Master`  

Model de backups de banco de dados.

### Métodos

#### Métodos Public

##### `static runBackup(): array`

Executa um processo.

**Retorno:** `array — */`

---

##### `static listBackups(): array`

List backups.

**Retorno:** `array — */`

---

#### Métodos Private

##### `static getBackupPath(): string`

Obtém dados específicos.

**Retorno:** `string — */`

---

##### `static getBackupCommand(): string`

Obtém dados específicos.

**Retorno:** `string — */`

---

##### `static canExec(): bool`

Verifica permissão ou capacidade.

**Retorno:** `bool — */`

---

### Funções auxiliares do arquivo

#### `(string $path)`

---

#### `(string $filename)`

---

#### `deleteBackup(string $filename)`

---

#### `diagnose()`

---

#### `formatBytes(int $bytes)`

---

## GitVersion

**Tipo:** Class  
**Arquivo:** `app/models/Master/GitVersion.php`  
**Namespace:** `Akti\Models\Master`  

Model de controle de versões Git.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$diagCache` | Sim |
| private | `$debugLog` | Sim |

### Métodos

#### Métodos Public

##### `static getBasePath(): string`

Obtém dados específicos.

**Retorno:** `string — */`

---

##### `static getDebugLog(): array`

Obtém dados específicos.

**Retorno:** `array — */`

---

#### Métodos Private

##### `static getGitBin(): string`

Obtém dados específicos.

**Retorno:** `string — */`

---

##### `static canExec(): bool`

Verifica permissão ou capacidade.

**Retorno:** `bool — */`

---

##### `static debugLog(string $msg): void`

Debug log.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$msg` | `string` | Msg |

**Retorno:** `void — */`

---

##### `static execGit(string $repoPath, string $command): array`

Exec git.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$repoPath` | `string` | Repo path |
| `$command` | `string` | Command |

**Retorno:** `array — */`

---

### Funções auxiliares do arquivo

#### `listRepositories()`

---

#### `($a['name'], $b['name'])`

---

#### `diagnose()`

---

#### `getRepoInfo(string $repoPath)`

---

#### `getAllReposInfo()`

---

#### `fetch(string $repoPath)`

---

#### `pull(string $repoPath)`

---

#### `pullRebase(string $repoPath)`

---

#### `getLog(string $repoPath, int $limit = 20)`

---

#### `getDetailedLog(string $repoPath, int $limit = 20)`

---

#### `getBranches(string $repoPath)`

---

#### `checkout(string $repoPath, string $branch)`

---

#### `stashAndPull(string $repoPath)`

---

#### `forceReset(string $repoPath)`

---

#### `validateRepoPath(string $repoPath)`

---

#### `getDiff(string $repoPath)`

---

#### `getRepoSize(string $repoPath)`

---

#### `formatBytes(int $bytes)`

---

## Migration

**Tipo:** Class  
**Arquivo:** `app/models/Master/Migration.php`  
**Namespace:** `Akti\Models\Master`  

Model de migrações SQL do sistema.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe Migration.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `listTenantDatabases(): array`

List tenant databases.

**Retorno:** `array — */`

---

##### `getRegisteredTenants(): array`

Obtém dados específicos.

**Retorno:** `array — */`

---

##### `getSchemaStructure(string $dbName): array`

Obtém dados específicos.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$dbName` | `string` | Db name |

**Retorno:** `array — */`

---

#### Métodos Private

##### `getMigrationLogColumns(): array`

Detecta as colunas existentes na tabela migration_logs do master.

---

##### `insertMigrationLog(string $dbName, string $migrationName, string $sqlHash, array $result, string $sql, int $adminId): void`

Insere log de migração adaptando-se às colunas disponíveis.

---

### Funções auxiliares do arquivo

#### `compareSchema(string $targetDb)`

---

#### `compareAllTenants()`

---

#### `executeSqlOnDatabase(string $dbName, string $sql)`

---

#### `logMigrationError(string $dbName, string $sql, array $results)`

---

#### `executeSqlOnAllTenants(string $sql, string $migrationName, int $adminId, ?array $selectedDbs = null)`

---

#### `executeSqlOnInitBase(string $sql)`

---

#### `parseSqlStatements(string $sql)`

---

#### `getMigrationHistory(int $limit = 50)`

---

#### `getMigrationHistoryPaginated(int $page = 1, int $perPage = 25, array $filters = [])`

---

#### `getMigrationStats()`

---

#### `logMigrationExecution(string $dbName, string $migrationName, string $sql, array $result, int $adminId)`

---

#### `getMigrationDetail(int $id)`

---

#### `buildUserSelectQuery(PDO $pdo)`

---

#### `listAllTenantUsers()`

---

#### `listUsersFromDatabase(string $dbName)`

---

#### `toggleTenantUser(string $dbName, int $userId)`

---

#### `getTableCount(string $dbName)`

---

## NginxLog

**Tipo:** Class  
**Arquivo:** `app/models/Master/NginxLog.php`  
**Namespace:** `Akti\Models\Master`  

Model de logs do Nginx.

### Métodos

#### Métodos Public

##### `static listLogFiles(): array`

List log files.

**Retorno:** `array — */`

---

#### Métodos Private

##### `static getLogPath(): string`

Obtém dados específicos.

**Retorno:** `string — */`

---

### Funções auxiliares do arquivo

#### `($a['type'] !== $b['type'])`

---

#### `readTail(string $filename, int $lines = 200)`

---

#### `readGzTail(string $path, int $lines)`

---

#### `phpTail(string $path, int $lines)`

---

#### `search(string $filename, string $query, int $maxResults = 100)`

---

#### `analyzeErrors(string $filename, int $limit = 20)`

---

#### `getDownloadPath(string $filename)`

---

#### `diagnose()`

---

#### `formatBytes(int $bytes)`

---

## Plan

**Tipo:** Class  
**Arquivo:** `app/models/Master/Plan.php`  
**Namespace:** `Akti\Models\Master`  

Model de planos de assinatura.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe Plan.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `readAll(): array`

Retorna todos os registros.

**Retorno:** `array — */`

---

##### `readActive(): array`

Read active.

**Retorno:** `array — */`

---

##### `readOne(int $id): array`

Retorna um registro pelo ID.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `int` | ID do registro |

**Retorno:** `array — */`

---

##### `create(array $data): string`

Cria um novo registro no banco de dados.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$data` | `array` | Dados para processamento |

**Retorno:** `string — */`

---

##### `update(int $id, array $data): void`

Atualiza um registro existente.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `int` | ID do registro |
| `$data` | `array` | Dados para processamento |

**Retorno:** `void — */`

---

##### `delete(int $id): bool`

Remove um registro pelo ID.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `int` | ID do registro |

**Retorno:** `bool — */`

---

## TenantClient

**Tipo:** Class  
**Arquivo:** `app/models/Master/TenantClient.php`  
**Namespace:** `Akti\Models\Master`  

Model de clientes/tenants do sistema multi-tenant.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe TenantClient.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `readAll(): array`

Retorna todos os registros.

**Retorno:** `array — */`

---

##### `readOne(int $id): array`

Retorna um registro pelo ID.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `int` | ID do registro |

**Retorno:** `array — */`

---

##### `findBySubdomain(string $subdomain): array`

Busca registro(s) com critérios específicos.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$subdomain` | `string` | Subdomain |

**Retorno:** `array — */`

---

##### `findByDbName(string $dbName): array`

Busca registro(s) com critérios específicos.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$dbName` | `string` | Db name |

**Retorno:** `array — */`

---

##### `create(array $data): string`

Cria um novo registro no banco de dados.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$data` | `array` | Dados para processamento |

**Retorno:** `string — */`

---

##### `update(int $id, array $data): void`

Atualiza um registro existente.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `int` | ID do registro |
| `$data` | `array` | Dados para processamento |

**Retorno:** `void — */`

---

##### `updateLimitsFromPlan(int $clientId, array $plan): void`

Update limits from plan.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$clientId` | `int` | Client id |
| `$plan` | `array` | Plan |

**Retorno:** `void — */`

---

##### `toggleActive(int $id): void`

Alterna estado de propriedade.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `int` | ID do registro |

**Retorno:** `void — */`

---

##### `delete(int $id): void`

Remove um registro pelo ID.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `int` | ID do registro |

**Retorno:** `void — */`

---

##### `getStats(): array`

Obtém dados específicos.

**Retorno:** `array — */`

---

##### `provisionDatabase(string $dbHost, int $dbPort, string $dbName, string $dbUser, string $dbPassword, string $dbCharset = 'utf8mb4'): array`

Provision database.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$dbHost` | `string` | Db host |
| `$dbPort` | `int` | Db port |
| `$dbName` | `string` | Db name |
| `$dbUser` | `string` | Db user |
| `$dbPassword` | `string` | Db password |
| `$dbCharset` | `string` | Db charset |

**Retorno:** `array — */`

---

### Funções auxiliares do arquivo

#### `dropDatabase(string $dbHost, int $dbPort, string $dbName, string $dbUser)`

---

#### `createTenantUser(string $dbHost, int $dbPort, string $dbName, string $dbUser, string $dbPassword, string $dbCharset, array $userData)`

---

#### `connectTo(string $host, int $port, string $user, string $pass, ?string $dbname = null, string $charset = 'utf8mb4')`

---

#### `findMysqlBinary(string $name)`

---

## NfeAuditLog

**Tipo:** Class  
**Arquivo:** `app/models/NfeAuditLog.php`  
**Namespace:** `Akti\Models`  

Model: NfeAuditLog

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |
| private | `$table` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor da classe NfeAuditLog.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |

---

##### `log(array $data): int`

Registra uma ação de auditoria.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$data` | `array` | * @return int ID do registro |

**Retorno:** `int — ID do registro`

---

### Funções auxiliares do arquivo

#### `readPaginated(array $filters = [], int $page = 1, int $perPage = 50)`

---

#### `getDistinctActions()`

---

#### `getByEntity(string $entityType, int $entityId)`

---

#### `countByAction(?string $startDate = null, ?string $endDate = null)`

---

## NfeCredential

**Tipo:** Class  
**Arquivo:** `app/models/NfeCredential.php`  
**Namespace:** `Akti\Models`  

Model: NfeCredential

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |
| private | `$table` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO |

---

##### `get(?int $filialId = null): array`

Busca credenciais SEFAZ ativas.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$filialId` | `int|null` | ID da filial (ou null para a principal/ativa) |

**Retorno:** `array|false — */`

---

### Funções auxiliares do arquivo

#### `listAll()`

---

#### `update(array $data, ?int $id = null)`

---

#### `getNextNumberForUpdate(int $credentialId = 1)`

---

#### `incrementNextNumber(int $credentialId = 1)`

---

#### `validateForEmission()`

---

#### `encryptPassword(string $password)`

---

#### `decryptPassword(string $encryptedPassword)`

---

#### `getEncryptionKey()`

---

## NfeDocument

**Tipo:** Class  
**Arquivo:** `app/models/NfeDocument.php`  
**Namespace:** `Akti\Models`  

Model: NfeDocument

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |
| private | `$table` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor da classe NfeDocument.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |

---

##### `create(array $data): int`

Cria um novo registro de NF-e.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$data` | `array` | * @return int ID da NF-e criada |

**Retorno:** `int — ID da NF-e criada`

---

### Funções auxiliares do arquivo

#### `readOne(int $id)`

---

#### `findByNumero(int $numero, int $serie = 1, int $modelo = 55)`

---

#### `readByOrder(int $orderId)`

---

#### `readAllByOrder(int $orderId)`

---

#### `readPaginated(array $filters = [], int $page = 1, int $perPage = 20)`

---

#### `update(int $id, array $data)`

---

#### `markAuthorized(int $id, string $chave, string $protocolo, string $xmlAutorizado, ?string $xmlPath = null)`

---

#### `markCancelled(int $id, string $protocolo, string $motivo, string $xmlCancelamento)`

---

#### `countByStatus()`

---

#### `countThisMonth()`

---

#### `sumAuthorizedThisMonth()`

---

## NfeLog

**Tipo:** Class  
**Arquivo:** `app/models/NfeLog.php`  
**Namespace:** `Akti\Models`  

Model: NfeLog

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |
| private | `$table` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor da classe NfeLog.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |

---

##### `create(array $data): int`

Registra um log de comunicação SEFAZ.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$data` | `array` | * @return int ID do log criado |

**Retorno:** `int — ID do log criado`

---

### Funções auxiliares do arquivo

#### `getByDocument(int $docId)`

---

#### `getByOrder(int $orderId)`

---

#### `getRecent(int $limit = 50)`

---

## NfeQueue

**Tipo:** Class  
**Arquivo:** `app/models/NfeQueue.php`  
**Namespace:** `Akti\Models`  

Model: NfeQueue

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |
| private | `$table` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor da classe NfeQueue.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |

---

##### `enqueue(array $data): int`

Adiciona item à fila.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$data` | `array` | * @return int ID do item na fila |

**Retorno:** `int — ID do item na fila`

---

### Funções auxiliares do arquivo

#### `enqueueBatch(array $orderIds, string $batchId, int $modelo = 55)`

---

#### `fetchNext()`

---

#### `markProcessing(int $id)`

---

#### `markCompleted(int $id, ?int $nfeDocumentId = null)`

---

#### `markFailed(int $id, string $errorMessage)`

---

#### `cancel(int $id)`

---

#### `readOne(int $id)`

---

#### `readPaginated(array $filters = [], int $page = 1, int $perPage = 20)`

---

#### `countByStatus()`

---

#### `getByBatch(string $batchId)`

---

#### `listBatches(int $limit = 20)`

---

## NfeReceivedDocument

**Tipo:** Class  
**Arquivo:** `app/models/NfeReceivedDocument.php`  
**Namespace:** `Akti\Models`  

Model: NfeReceivedDocument

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |
| private | `$table` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor da classe NfeReceivedDocument.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |

---

##### `upsert(array $data): int`

Insere ou atualiza um documento recebido pelo NSU.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$data` | `array` | * @return int ID inserido/atualizado |

**Retorno:** `int — ID inserido/atualizado`

---

### Funções auxiliares do arquivo

#### `readOne(int $id)`

---

#### `readByChave(string $chave)`

---

#### `readPaginated(array $filters = [], int $page = 1, int $perPage = 20)`

---

#### `updateManifestation(int $id, string $status, ?string $protocol = null)`

---

#### `markImported(int $id)`

---

#### `countByManifestationStatus()`

---

#### `getLastNSU()`

---

## NfeReportModel

**Tipo:** Class  
**Arquivo:** `app/models/NfeReportModel.php`  
**Namespace:** `Akti\Models`  

Model: NfeReportModel

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor da classe NfeReportModel.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |

---

##### `getNfesByPeriod(string $start, string $end, array $filters = []): array`

Retorna NF-e emitidas dentro de um período com filtros opcionais.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$start` | `string` | Data inicial (Y-m-d) |
| `$end` | `string` | Data final (Y-m-d) |
| `$filters` | `array` | Filtros opcionais: status, modelo |

**Retorno:** `array — Lista de NF-e`

---

### Funções auxiliares do arquivo

#### `getTaxSummary(string $start, string $end)`

---

#### `getNfesByCustomer(string $start, string $end, ?int $customerId = null)`

---

#### `getCfopSummary(string $start, string $end)`

---

#### `getCancelledNfes(string $start, string $end)`

---

#### `getInutilizacoes(string $start, string $end)`

---

#### `getSefazLogs(string $start, string $end, ?string $action = null)`

---

#### `getFiscalKpis(string $start, string $end)`

---

#### `getNfeStatusLabels()`

---

#### `getNfeStatusLabel(string $status)`

---

#### `getModeloLabel(int $modelo)`

---

#### `getLogActionLabels()`

---

#### `getLogActionLabel(string $action)`

---

#### `getCfopDescriptions()`

---

#### `getCfopDescription(string $cfop)`

---

#### `getCustomersWithNfe()`

---

#### `getNfesByMonth(int $months = 12)`

---

#### `getStatusDistribution()`

---

#### `getTopCfops(int $limit = 5)`

---

#### `getTopCustomers(int $limit = 5)`

---

#### `getFiscalAlerts()`

---

#### `getTotalTaxes12Months()`

---

#### `getCorrectionHistory(string $start, string $end)`

---

#### `getCorrectionsByMonth(int $months = 12)`

---

#### `getLivroSaidas(string $start, string $end)`

---

#### `getLivroEntradas(string $start, string $end)`

---

## NfeWebhook

**Tipo:** Class  
**Arquivo:** `app/models/NfeWebhook.php`  
**Namespace:** `Akti\Models`  

Model: NfeWebhook

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |
| private | `$table` | Não |
| private | `$logsTable` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor da classe NfeWebhook.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |

---

##### `create(array $data): int`

Cria um webhook.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$data` | `array` | * @return int |

**Retorno:** `int — */`

---

### Funções auxiliares do arquivo

#### `readOne(int $id)`

---

#### `readAll(bool $onlyActive = false)`

---

#### `getByEvent(string $eventName)`

---

#### `($eventName)`

---

#### `update(int $id, array $data)`

---

#### `delete(int $id)`

---

#### `logDelivery(array $data)`

---

#### `getLogs(int $webhookId, int $page = 1, int $perPage = 50)`

---

## Notification

**Tipo:** Class  
**Arquivo:** `app/models/Notification.php`  
**Namespace:** `Akti\Models`  

Notification Model

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor da classe Notification.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |

---

##### `create(int $userId, string $type, string $title, string $message = '', array $data = []): int`

Cria uma nova notificação.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$userId` | `int` | Destinatário |
| `$type` | `string` | Tipo da notificação |
| `$title` | `string` | Título |
| `$message` | `string` | Mensagem (opcional) |
| `$data` | `array` | Metadata JSON (opcional) |

**Retorno:** `int|false — ID inserido ou false`

---

##### `getByUser(int $userId, int $limit = 20, bool $unreadOnly = false): array`

Lista notificações de um usuário (mais recentes primeiro).

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$userId` | `int` | * @param int  $limit |
| `$unreadOnly` | `bool` | Se true, retorna apenas não-lidas |

**Retorno:** `array — */`

---

##### `countUnread(int $userId): int`

Conta notificações não-lidas de um usuário.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$userId` | `int` | * @return int |

**Retorno:** `int — */`

---

##### `markAsRead(int $id, int $userId): bool`

Marca uma notificação como lida.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `int` | * @param int $userId (segurança: só o dono pode marcar) |

**Retorno:** `bool — */`

---

##### `markAllAsRead(int $userId): bool`

Marca todas as notificações de um usuário como lidas.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$userId` | `int` | * @return bool |

**Retorno:** `bool — */`

---

##### `deleteOld(int $daysOld = 90): int`

Exclui notificações antigas (mais de X dias).

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$daysOld` | `int` | * @return int Número de registros removidos |

**Retorno:** `int — Número de registros removidos`

---

##### `readOne(int $id): ?array`

Retorna uma notificação pelo ID.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `int` | * @return array|null |

**Retorno:** `array|null — */`

---

##### `broadcast(array $userIds, string $type, string $title, string $message = '', array $data = []): int`

Envia notificação para múltiplos usuários (broadcast).

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$userIds` | `array` | * @param string $type |
| `$title` | `string` | * @param string $message |
| `$data` | `array` | * @return int Número de notificações criadas |

**Retorno:** `int — Número de notificações criadas`

---

## Order

**Tipo:** Class  
**Arquivo:** `app/models/Order.php`  
**Namespace:** `Akti\Models`  

Model de pedidos/ordens de serviço.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |
| private | `$table_name` | Não |
| protected | `$id` | Não |
| protected | `$customer_id` | Não |
| protected | `$total_amount` | Não |
| protected | `$status` | Não |
| protected | `$pipeline_stage` | Não |
| protected | `$priority` | Não |
| protected | `$internal_notes` | Não |
| protected | `$quote_notes` | Não |
| protected | `$scheduled_date` | Não |
| protected | `$created_at` | Não |
| private | `$fillable` | Sim |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor da classe Order.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |

---

##### `__get(string $name): mixed`

__get.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$name` | `string` | Nome |

**Retorno:** `mixed — */`

---

##### `__set(string $name, mixed $value): void`

__set.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$name` | `string` | Nome |
| `$value` | `mixed` | Valor |

**Retorno:** `void — */`

---

##### `create()`

Cria um novo registro no banco de dados.

---

##### `getScheduledContacts($month = null, $year = null)`

Busca contatos agendados para um determinado mês/ano

---

##### `getScheduledContactsByDate($date)`

Busca contatos agendados para um dia específico (para relatório)

---

##### `readAll(): array`

Retorna todos os registros.

**Retorno:** `array — */`

---

##### `readPaginated(int $page = 1, int $perPage = 15): array`

Retorna pedidos com paginação

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$page` | `int` | Página atual (1-based) |
| `$perPage` | `int` | Itens por página |

**Retorno:** `array — Lista de pedidos`

---

### Funções auxiliares do arquivo

#### `readOne($id)`

---

#### `update()`

---

#### `delete($id)`

---

#### `updatePaymentLink(int $orderId, array $data)`

---

#### `setCustomerApprovalStatus(int $orderId, string $status)`

---

#### `countByStatus()`

---

#### `countAll()`

---

#### `totalActiveValue()`

---

#### `getItems($orderId)`

---

#### `addItem($orderId, $productId, $quantity, $unitPrice, $combinationId = null, $gradeDescription = null)`

---

#### `updateItem($itemId, $quantity, $unitPrice)`

---

#### `deleteItem($itemId)`

---

#### `recalculateTotal($orderId)`

---

#### `updateItemQty($itemId, $quantity)`

---

#### `updateItemDiscount($itemId, $discount)`

---

#### `getExtraCosts($orderId)`

---

#### `addExtraCost($orderId, $description, $amount)`

---

#### `deleteExtraCost($costId)`

---

## OrderItemLog

**Tipo:** Class  
**Arquivo:** `app/models/OrderItemLog.php`  
**Namespace:** `Akti\Models`  

Model para logs/histórico de itens de pedido (por produto)

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |
| public | `$allowedTypes` | Sim |
| public | `$maxFileSize` | Sim |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor da classe OrderItemLog.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |

---

##### `createTableIfNotExists()`

Verifica se a tabela existe (DDL movida para /sql).

---

##### `addLog($orderId, $orderItemId, $userId, $message = null, $filePath = null, $fileName = null, $fileType = null)`

Adicionar log a um item do pedido

---

##### `getLogsByItem($orderItemId)`

Buscar logs de um item específico (para modal do painel de produção)

---

##### `getLogsByOrder($orderId)`

Buscar todos os logs de todos os itens de um pedido (para detalhe do pedido)

---

##### `countLogsByItem($orderItemId)`

Contar logs por item (para badge no painel de produção)

---

##### `countLogsByOrderGrouped($orderId)`

Contar logs agrupados por item para um pedido (batch)

---

##### `deleteLog($logId, $userId = null)`

Excluir um log (e seu arquivo se existir)

---

##### `handleFileUpload($file, $orderId, $orderItemId)`

Upload de arquivo e retorna dados do arquivo

---

## OrderPreparation

**Tipo:** Class  
**Arquivo:** `app/models/OrderPreparation.php`  
**Namespace:** `Akti\Models`  

Model para checklist de preparação de pedidos.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor da classe OrderPreparation.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |

---

##### `createTableIfNotExists()`

Verifica se a tabela existe (DDL movida para /sql).

---

##### `getChecklist($orderId)`

Obter checklist de um pedido como array associativo

---

##### `toggle($orderId, $key, $userId)`

Alternar o status de uma etapa do checklist (toggle)

---

## PaymentGateway

**Tipo:** Class  
**Arquivo:** `app/models/PaymentGateway.php`  
**Namespace:** `Akti\Models`  

Model: PaymentGateway

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor da classe PaymentGateway.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |

---

##### `readAll(): array`

Retorna todos os gateways cadastrados.

**Retorno:** `array — */`

---

##### `readOne(int $id): array`

Retorna um gateway pelo ID.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `int` | * @return array|false |

**Retorno:** `array|false — */`

---

##### `readBySlug(string $slug): array`

Retorna um gateway pelo slug.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$slug` | `string` | * @return array|false |

**Retorno:** `array|false — */`

---

##### `getDefault(): array`

Retorna o gateway padrão (is_default=1 e is_active=1).

**Retorno:** `array|false — */`

---

##### `getActive(): array`

Retorna todos os gateways ativos.

**Retorno:** `array — */`

---

##### `update(int $id, array $data): bool`

Atualiza configurações de um gateway (credenciais, settings, ambiente, status).

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `int` | * @param array $data |

**Retorno:** `bool — */`

---

### Funções auxiliares do arquivo

#### `updateCredentials(int $id, array $credentials)`

---

#### `updateSettings(int $id, array $settings)`

---

#### `logTransaction(array $data)`

---

#### `getTransactionsByExternalId(string $externalId)`

---

#### `getTransactionsByInstallment(int $installmentId)`

---

#### `getTransactionsByOrder(int $orderId)`

---

#### `getRecentTransactions(int $limit = 50)`

---

## Permission

**Tipo:** Class  
**Arquivo:** `app/models/Permission.php`  
**Namespace:** `Akti\Models`  

Model de permissões de acesso por grupo de usuários.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe Permission.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `readAll(): array`

Retorna todos os registros.

**Retorno:** `array — */`

---

##### `readByPage(string $page): array`

Read by page.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$page` | `string` | Número da página |

**Retorno:** `array — */`

---

##### `readOne(int $id): ?array`

Retorna um registro pelo ID.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `int` | ID do registro |

**Retorno:** `array|null — */`

---

##### `create(array $data): int`

Cria um novo registro no banco de dados.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$data` | `array` | Dados para processamento |

**Retorno:** `int — */`

---

##### `delete(int $id): bool`

Remove um registro pelo ID.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `int` | ID do registro |

**Retorno:** `bool — */`

---

##### `getGroupPermissions(int $groupId): array`

Obtém dados específicos.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$groupId` | `int` | Group id |

**Retorno:** `array — */`

---

##### `setGroupPermission(int $groupId, int $permissionId, array $abilities): bool`

Define valor específico.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$groupId` | `int` | Group id |
| `$permissionId` | `int` | Permission id |
| `$abilities` | `array` | Abilities |

**Retorno:** `bool — */`

---

##### `removeGroupPermission(int $groupId, int $permissionId): bool`

Remove group permission.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$groupId` | `int` | Group id |
| `$permissionId` | `int` | Permission id |

**Retorno:** `bool — */`

---

##### `checkPermission(int $groupId, string $page, string $action = 'view'): bool`

Verifica se o usuário tem permissão de acesso.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$groupId` | `int` | Group id |
| `$page` | `string` | Número da página |
| `$action` | `string` | Action |

**Retorno:** `bool — */`

---

### Funções auxiliares do arquivo

#### `seedPermissionsFromMenu(int $tenantId, array $menuConfig)`

---

#### `createIfNotExists(int $tenantId, string $page)`

---

## Pipeline

**Tipo:** Class  
**Arquivo:** `app/models/Pipeline.php`  
**Namespace:** `Akti\Models`  

Model do pipeline Kanban de produção.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |
| public | `$stages` | Sim |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor da classe Pipeline.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |

---

##### `getOrdersByStage()`

Busca todos os pedidos ativos no pipeline (não concluídos/cancelados)

---

##### `getStageGoals()`

Busca metas de tempo por etapa

---

##### `updateStageGoal($stage, $maxHours)`

Atualiza a meta de horas de uma etapa

---

##### `moveToStage($orderId, $newStage, $userId = null, $notes = '')`

Move um pedido para a próxima etapa (ou uma etapa específica)

---

##### `addHistory($orderId, $fromStage, $toStage, $userId = null, $notes = '')`

Registra histórico de movimentação

---

##### `getHistory($orderId)`

Busca histórico de um pedido com duração em cada etapa.

---

##### `getOrderDetail($orderId)`

Busca detalhes completos de um pedido para o pipeline

---

##### `updateOrderDetails($data)`

Atualiza dados extras do pedido (prioridade, responsável, notas, financeiro, envio)

---

##### `getDelayedOrders()`

Conta pedidos atrasados (acima da meta de horas por etapa)

---

##### `getCompletedOrders($limit = 50)`

Busca pedidos concluídos (para histórico/relatório)

---

##### `initOrderProductionSectors($orderId)`

Inicializa os setores de produção POR ITEM do pedido quando entra na etapa "producao".

---

##### `getOrderProductionSectors($orderId)`

Retorna os setores de produção de um pedido agrupados por item, com dados do setor e produto

---

##### `advanceItemSector($orderId, $orderItemId, $sectorId, $userId = null)`

Concluir o setor atual de um item e avançar para o próximo.

---

##### `revertItemSector($orderId, $orderItemId, $sectorId, $userId = null)`

Retroceder: reverte o último setor concluído de um item para pendente.

---

##### `getProductionBoardData($allowedSectorIds = [])`

Retorna todos os itens de produção agrupados por setor, para o painel de produção.

---

##### `moveOrderSector($orderId, $orderItemId, $sectorId, $newStatus, $userId = null)`

Mover um setor de produção de um item para um status específico (fallback genérico)

---

##### `getStats()`

Estatísticas do pipeline para o dashboard

---

## PortalAccess

**Tipo:** Class  
**Arquivo:** `app/models/PortalAccess.php`  
**Namespace:** `Akti\Models`  

Model: PortalAccess

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |
| private | `$table` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor do model

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO |

---

##### `create(array $data): int`

Cria um acesso ao portal para um cliente

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$data` | `array` | [customer_id, email, password (plain), lang] |

**Retorno:** `int — ID do acesso criado`

---

### Funções auxiliares do arquivo

#### `findByEmail(string $email)`

---

#### `findByCustomerId(int $customerId)`

---

#### `findById(int $id)`

---

#### `readAll()`

---

#### `update(int $id, array $data)`

---

#### `delete(int $id)`

---

#### `isLocked(array $access)`

---

#### `verifyPassword(string $password, string $hash)`

---

#### `registerFailedAttempt(int $accessId)`

---

#### `registerSuccessfulLogin(int $accessId, string $ip)`

---

#### `generateMagicToken(int $accessId, ?int $expiryHours = null)`

---

#### `validateMagicToken(string $token)`

---

#### `invalidateMagicToken(int $accessId)`

---

#### `generateResetToken(int $accessId, int $expiryHours = 1)`

---

#### `validateResetToken(string $token)`

---

#### `invalidateResetToken(int $accessId)`

---

#### `resetPassword(int $accessId, string $newPassword)`

---

#### `setMustChangePassword(int $accessId, bool $mustChange = true)`

---

#### `emailExists(string $email)`

---

#### `customerHasAccess(int $customerId)`

---

#### `getConfig(string $key, string $default = '')`

---

#### `getAllConfig()`

---

#### `setConfig(string $key, string $value)`

---

#### `hasApprovalColumn()`

---

#### `getDashboardStats(int $customerId)`

---

#### `getRecentOrders(int $customerId, int $limit = 5)`

---

#### `getRecentNotifications(int $customerId, int $limit = 5)`

---

#### `($b['date'])`

---

#### `getOrdersByCustomer(int $customerId, string $filter = 'all', int $limit = 10, int $offset = 0)`

---

#### `countOrdersByCustomer(int $customerId, string $filter = 'all')`

---

#### `getOrderDetail(int $orderId, int $customerId)`

---

#### `getOrderItems(int $orderId)`

---

#### `getOrderInstallments(int $orderId)`

---

#### `getOrderExtraCosts(int $orderId)`

---

#### `getOrderTimeline(array $order)`

---

#### `updateApprovalStatus(int $orderId, int $customerId, string $status, string $ip, ?string $notes)`

---

#### `cancelApprovalStatus(int $orderId, int $customerId, string $ip)`

---

#### `markOverdueInstallments(int $customerId)`

---

#### `getInstallmentsByCustomer(int $customerId, string $filter = 'all', int $limit = 20, int $offset = 0)`

---

#### `countInstallmentsByCustomer(int $customerId, string $filter = 'all')`

---

#### `getFinancialSummary(int $customerId)`

---

#### `getInstallmentDetail(int $installmentId, int $customerId)`

---

#### `getTrackingOrders(int $customerId)`

---

#### `getTrackingDetail(int $orderId, int $customerId)`

---

#### `getDocumentsByCustomer(int $customerId)`

---

#### `getDocumentDetail(int $documentId, int $customerId)`

---

#### `getAvailableProducts(?string $search = null, ?int $categoryId = null, int $limit = 20, int $offset = 0)`

---

#### `getCategories()`

---

#### `getProductById(int $productId)`

---

#### `createPortalOrder(int $customerId, array $cartItems, string $notes = '')`

---

#### `generate2faCode(int $accessId, int $expiryMinutes = 10)`

---

#### `validate2faCode(int $accessId, string $code)`

---

#### `is2faEnabled(int $accessId)`

---

#### `toggle2fa(int $accessId, bool $enable)`

---

#### `updateAvatar(int $accessId, string $avatarPath)`

---

#### `getAvatar(int $accessId)`

---

#### `getActiveSessions(int $accessId)`

---

#### `cleanExpiredSessions()`

---

## PortalMessage

**Tipo:** Class  
**Arquivo:** `app/models/PortalMessage.php`  
**Namespace:** `Akti\Models`  

Model: PortalMessage

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |
| private | `$table` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor do model

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO |

---

##### `create(array $data): int`

Cria uma nova mensagem

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$data` | `array` | [customer_id, order_id, sender_type, sender_id, message, attachment_path] |

**Retorno:** `int — ID da mensagem criada`

---

### Funções auxiliares do arquivo

#### `getByCustomer(int $customerId, ?int $orderId = null, int $limit = 50, int $offset = 0)`

---

#### `countUnread(int $customerId)`

---

#### `markAsRead(int $customerId, ?int $orderId = null)`

---

#### `findById(int $id, int $customerId)`

---

#### `countUnreadFromCustomers()`

---

## PreparationStep

**Tipo:** Class  
**Arquivo:** `app/models/PreparationStep.php`  
**Namespace:** `Akti\Models`  

Model para gerenciar etapas de preparo globais (configuráveis via Settings).

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor da classe PreparationStep.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |

---

##### `createTableIfNotExists()`

Verifica se a tabela existe (DDL movida para /sql).

---

##### `getAll()`

Retorna todas as etapas (ativas e inativas), ordenadas

---

##### `getActive()`

Retorna apenas as etapas ativas, ordenadas

---

##### `getActiveAsMap()`

Retorna as etapas ativas como array associativo [step_key => ['icon'=>..., 'label'=>..., 'desc'=>...]]

---

##### `add($key, $label, $description, $icon, $sortOrder = 0)`

Adicionar uma nova etapa

---

##### `update($id, $label, $description, $icon, $sortOrder, $isActive)`

Atualizar uma etapa existente

---

##### `delete($id)`

Excluir uma etapa

---

##### `toggleActive($id)`

Ativar/desativar uma etapa

---

##### `getById($id)`

Buscar uma etapa por ID

---

#### Métodos Private

##### `seedDefaults()`

Insere os padrões caso a tabela esteja vazia

---

## PriceTable

**Tipo:** Class  
**Arquivo:** `app/models/PriceTable.php`  
**Namespace:** `Akti\Models`  

Model: PriceTable

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor da classe PriceTable.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |

---

##### `countAll()`

Conta o total de tabelas de preço cadastradas

---

##### `readAll()`

Lista todas as tabelas de preço

---

##### `readOne($id)`

Lê uma tabela de preço

---

##### `create($name, $description = '')`

Cria tabela de preço

---

##### `update($id, $name, $description = '')`

Atualiza tabela de preço

---

##### `delete($id)`

Exclui tabela de preço (se não for padrão)

---

##### `getDefault()`

Retorna tabela padrão

---

##### `getItems($tableId)`

Retorna itens de uma tabela de preço com dados do produto

---

##### `setItemPrice($tableId, $productId, $price)`

Define preço de um produto na tabela

---

##### `removeItem($itemId)`

Remove item de uma tabela de preço

---

##### `getPricesForProduct($productId)`

Retorna todos os preços de todas as tabelas para um dado produto

---

##### `saveProductPrices($productId, $tablePrices)`

Salva os preços de um produto em múltiplas tabelas de uma vez.

---

##### `getProductPriceForCustomer($productId, $customerId)`

Retorna o preço de um produto para um determinado cliente

---

##### `getAllPricesForCustomer($customerId)`

Retorna todos os preços para um cliente (para preencher JS no frontend)

---

## Product

**Tipo:** Class  
**Arquivo:** `app/models/Product.php`  
**Namespace:** `Akti\Models`  

Model de produtos com suporte a grades e variações.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |
| private | `$table_name` | Não |
| protected | `$id` | Não |
| protected | `$name` | Não |
| protected | `$description` | Não |
| protected | `$category_id` | Não |
| protected | `$subcategory_id` | Não |
| protected | `$price` | Não |
| protected | `$stock_quantity` | Não |
| protected | `$photo_url` | Não |
| public | `$fiscalFields` | Sim |
| public | `$ecommerceFields` | Sim |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor da classe Product.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |

---

##### `readAll()`

Retorna todos os registros.

---

##### `readPaginated(int $page = 1, int $perPage = 15): array`

Retorna produtos com paginação

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$page` | `int` | Página atual (1-based) |
| `$perPage` | `int` | Itens por página |

**Retorno:** `array — Lista de produtos`

---

### Funções auxiliares do arquivo

#### `readPaginatedFiltered(int $page = 1, int $perPage = 20, ?int $categoryId = null, ?string $search = null, ?int $subcategoryId = null, bool $storeOnly = false)`

---

#### `getImages($productId)`

Get images.

---

#### `countAll()`

Count all.

---

#### `create($data)`

Create.

---

#### `addImage($productId, $imagePath, $isMain = 0)`

Add image.

---

#### `readOne($id)`

Read one.

---

#### `update($data)`

Update.

---

#### `delete($id)`

Delete.

---

#### `deleteImage($imageId)`

Delete image.

---

#### `getImage($imageId)`

Get image.

---

#### `setMainImage($productId, $imageId)`

Set main image.

---

#### `getActiveCombinations($productId)`

Busca as combinações de grade ativas de um produto

---

#### `hasCombinations($productId)`

Verifica se o produto tem combinações de grade ativas

---

#### `searchForSelect2(string $q = '', int $limit = 10)`

---

#### `searchPaginated(string $query = '', int $page = 1, int $perPage = 20)`

---

#### `getByCategory($categoryId)`

Get products by category ID (with main image)

---

#### `getBySubcategory($subcategoryId)`

Get products by subcategory ID (with main image)

---

#### `updateBaseCostFromBOM(int $productId)`

---

#### `getMarginAnalysis(int $productId)`

---

#### `bulkUpdateBOMCosts(array $productIds)`

---

## ProductGrade

**Tipo:** Class  
**Arquivo:** `app/models/ProductGrade.php`  
**Namespace:** `Akti\Models`  

ProductGrade Model

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor da classe ProductGrade.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |

---

##### `getAllGradeTypes()`

List all grade types

---

##### `createGradeType($name, $description = null, $icon = 'fas fa-th')`

Create a new grade type

---

##### `getProductGrades($productId)`

Get all grades for a product (with type info)

---

##### `getProductGradesWithValues($productId)`

Get all grades for a product WITH their values

---

##### `addGradeToProduct($productId, $gradeTypeId, $sortOrder = 0)`

Add a grade to a product

---

##### `removeGradeFromProduct($productGradeId)`

Remove a grade from a product (soft delete)

---

##### `deleteGradeFromProduct($productGradeId)`

Hard delete a grade and its values

---

##### `getGradeValues($productGradeId)`

Get all values for a product grade

---

##### `addGradeValue($productGradeId, $value, $sortOrder = 0)`

Add a value to a product grade

---

##### `removeGradeValue($valueId)`

Remove a grade value (soft delete)

---

##### `deleteGradeValue($valueId)`

Hard delete a grade value

---

##### `getProductCombinations($productId)`

Get all combinations for a product

---

##### `getActiveProductCombinations($productId)`

Get only active combinations for a product

---

##### `toggleProductCombination($combinationId, $isActive)`

Toggle combination active/inactive for a product

---

##### `saveCombination($productId, $combinationKey, $combinationLabel, $sku = null, $priceOverride = null, $stockQuantity = 0)`

Save a combination

---

##### `generateCombinations($productId)`

Generate all combinations from current grades/values and save them.

---

##### `saveProductGrades($productId, $gradesData)`

Save all grades and values for a product from form data.

---

##### `saveCombinationsData($productId, $combosData)`

Save combinations data (prices, stock, SKU, active state) from form

---

##### `productHasGrades($productId)`

Check if a product has any grades configured

---

##### `getCombination($combinationId)`

Get a specific combination by ID

---

#### Métodos Private

##### `getProductGradeId($productId, $gradeTypeId)`

Get product_grade id

---

##### `cartesianProduct($arrays)`

Cartesian product of multiple arrays

---

## ProductionSector

**Tipo:** Class  
**Arquivo:** `app/models/ProductionSector.php`  
**Namespace:** `Akti\Models`  

Model de setores de produção.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |
| private | `$table` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor da classe ProductionSector.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |

---

##### `countAll()`

Conta o total de setores de produção cadastrados

---

### Funções auxiliares do arquivo

#### `readAll($onlyActive = false)`

---

#### `readPaginated(int $page = 1, int $perPage = 15, string $search = '')`

---

#### `readOne($id)`

---

#### `create($data)`

---

#### `update($data)`

---

#### `delete($id)`

---

#### `getProductSectors($productId)`

---

#### `saveProductSectors($productId, $sectorIds)`

---

#### `getCategorySectors($categoryId)`

---

#### `saveCategorySectors($categoryId, $sectorIds)`

---

#### `getSubcategorySectors($subcategoryId)`

---

#### `saveSubcategorySectors($subcategoryId, $sectorIds)`

---

#### `getEffectiveSectors($productId, $subcategoryId = null, $categoryId = null)`

---

## PurchaseOrder

**Tipo:** Class  
**Arquivo:** `app/models/PurchaseOrder.php`  
**Namespace:** `Akti\Models`  

Model de ordens de compra de insumos.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe PurchaseOrder.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `readPaginated(int $page = 1, int $perPage = 15, array $filters = []): array`

Read paginated.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$page` | `int` | Número da página |
| `$perPage` | `int` | Registros por página |
| `$filters` | `array` | Filtros aplicados |

**Retorno:** `array — */`

---

### Funções auxiliares do arquivo

#### `readOne(int $id)`

---

#### `create(array $data)`

---

#### `update(int $id, array $data)`

---

#### `delete(int $id)`

---

#### `updateTotals(int $id)`

---

#### `getItems(int $orderId)`

---

#### `addItem(array $data)`

---

#### `removeItem(int $itemId)`

---

#### `receive(int $id)`

---

## QualityChecklist

**Tipo:** Class  
**Arquivo:** `app/models/QualityChecklist.php`  
**Namespace:** `Akti\Models`  

Model de checklists de controle de qualidade.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe QualityChecklist.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `readAll(): array`

Retorna todos os registros.

**Retorno:** `array — */`

---

##### `readOne(int $id): ?array`

Retorna um registro pelo ID.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `int` | ID do registro |

**Retorno:** `array|null — */`

---

##### `create(array $data): int`

Cria um novo registro no banco de dados.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$data` | `array` | Dados para processamento |

**Retorno:** `int — */`

---

##### `update(int $id, array $data): bool`

Atualiza um registro existente.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `int` | ID do registro |
| `$data` | `array` | Dados para processamento |

**Retorno:** `bool — */`

---

##### `delete(int $id): bool`

Remove um registro pelo ID.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `int` | ID do registro |

**Retorno:** `bool — */`

---

##### `getItems(int $checklistId): array`

Obtém dados específicos.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$checklistId` | `int` | Checklist id |

**Retorno:** `array — */`

---

##### `addItem(array $data): int`

Add item.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$data` | `array` | Dados para processamento |

**Retorno:** `int — */`

---

##### `removeItem(int $itemId): bool`

Remove item.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$itemId` | `int` | Item id |

**Retorno:** `bool — */`

---

##### `createInspection(array $data): int`

Create inspection.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$data` | `array` | Dados para processamento |

**Retorno:** `int — */`

---

##### `updateInspection(int $id, array $data): bool`

Update inspection.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `int` | ID do registro |
| `$data` | `array` | Dados para processamento |

**Retorno:** `bool — */`

---

##### `getInspections(int $orderId): array`

Obtém dados específicos.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$orderId` | `int` | ID do pedido |

**Retorno:** `array — */`

---

##### `createNonConformity(array $data): int`

Create non conformity.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$data` | `array` | Dados para processamento |

**Retorno:** `int — */`

---

##### `getNonConformities(array $filters = []): array`

Obtém dados específicos.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$filters` | `array` | Filtros aplicados |

**Retorno:** `array — */`

---

### Funções auxiliares do arquivo

#### `resolveNonConformity(int $id, string $correctiveAction)`

---

## Quote

**Tipo:** Class  
**Arquivo:** `app/models/Quote.php`  
**Namespace:** `Akti\Models`  

Model de orçamentos/cotações.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe Quote.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `readPaginated(int $page = 1, int $perPage = 15, array $filters = []): array`

Read paginated.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$page` | `int` | Número da página |
| `$perPage` | `int` | Registros por página |
| `$filters` | `array` | Filtros aplicados |

**Retorno:** `array — */`

---

### Funções auxiliares do arquivo

#### `readOne(int $id)`

---

#### `readByToken(string $token)`

---

#### `create(array $data)`

---

#### `update(int $id, array $data)`

---

#### `delete(int $id)`

---

#### `approve(int $id)`

---

#### `convertToOrder(int $id, int $orderId)`

---

#### `getItems(int $quoteId)`

---

#### `addItem(array $data)`

---

#### `removeItem(int $itemId)`

---

#### `saveVersion(int $quoteId, int $version, array $snapshot, ?int $userId = null)`

---

#### `getVersions(int $quoteId)`

---

#### `getSummary()`

---

## RecurringTransaction

**Tipo:** Class  
**Arquivo:** `app/models/RecurringTransaction.php`  
**Namespace:** `Akti\Models`  

RecurringTransaction — Model para transações recorrentes.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe RecurringTransaction.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `create(array $data): ?int`

Cria uma nova recorrência.

---

##### `update(int $id, array $data): bool`

Atualiza uma recorrência existente.

---

##### `getById(int $id): ?array`

Busca uma recorrência pelo ID.

---

##### `readAll(): array`

Lista todas as recorrências (ativas primeiro).

---

##### `getActive(): array`

Lista apenas recorrências ativas.

---

##### `toggleActive(int $id, bool $active): bool`

Ativa/desativa uma recorrência.

---

##### `delete(int $id): bool`

Exclui uma recorrência.

---

##### `processMonth(?int $userId = null): array`

Processa recorrências pendentes para o mês atual.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$userId` | `int|null` | Usuário que disparou o processamento |

**Retorno:** `array — Resumo: [generated => int, skipped => int, errors => array]`

---

### Funções auxiliares do arquivo

#### `linkTransactionToRecurring(int $txId, int $recurringId)`

---

#### `getMonthlySummary()`

---

#### `projectMonths(int $months = 6)`

---

#### `tableExists(PDO $db)`

---

## ReportModel

**Tipo:** Class  
**Arquivo:** `app/models/ReportModel.php`  
**Namespace:** `Akti\Models`  

Model: ReportModel

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor do model

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO |

---

##### `getOrdersByPeriod(string $start, string $end): array`

Retorna pedidos dentro de um período com cliente, total, status e data.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$start` | `string` | Data inicial (Y-m-d) |
| `$end` | `string` | Data final (Y-m-d) |

**Retorno:** `array — Lista de pedidos`

---

##### `getRevenueByCustomer(string $start, string $end): array`

Retorna faturamento agrupado por cliente com quantidade de pedidos e soma.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$start` | `string` | Data inicial (Y-m-d) |
| `$end` | `string` | Data final (Y-m-d) |

**Retorno:** `array — Lista de faturamento por cliente`

---

##### `getIncomeStatement(string $start, string $end): array`

Retorna entradas e saídas agrupadas por categoria com saldo líquido.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$start` | `string` | Data inicial (Y-m-d) |
| `$end` | `string` | Data final (Y-m-d) |

**Retorno:** `array — ['entries' => [...], 'exits' => [...], 'totals' => [...]]`

---

##### `getOpenInstallments(): array`

Retorna parcelas pendentes ou atrasadas com dias de atraso, ordenadas por vencimento.

**Retorno:** `array — Lista de parcelas abertas`

---

##### `getScheduledContacts(string $start, string $end): array`

Retorna contatos agendados dentro de um período, com cliente e prioridade.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$start` | `string` | Data inicial (Y-m-d) |
| `$end` | `string` | Data final (Y-m-d) |

**Retorno:** `array — Lista de agendamentos`

---

##### `static getCategoryLabels(): array`

Mapa de categorias de transação para labels legíveis (pt-BR).

---

##### `static getCategoryLabel(string $category): string`

Retorna label legível de uma categoria.

---

##### `static getStatusLabels(): array`

Mapa de status de pedido para labels legíveis (pt-BR).

---

##### `static getStatusLabel(string $status): string`

Retorna label legível de um status.

---

##### `static getStageLabels(): array`

Mapa de etapas do pipeline para labels legíveis (pt-BR).

---

##### `static getPriorityLabels(): array`

Mapa de prioridades para labels legíveis (pt-BR).

---

##### `static getPriorityLabel(string $priority): string`

Retorna label legível de uma prioridade.

---

##### `getProductsCatalog(?int $productId = null, bool $includeVariations = true): array`

Retorna produtos com informações completas para relatório:

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$productId` | `int|null` | Filtro por produto específico (null = todos) |
| `$includeVariations` | `bool` | Incluir variações de grade |

**Retorno:** `array — ['products' => [...], 'price_tables' => [...]]`

---

### Funções auxiliares do arquivo

#### `getStockByWarehouse(?int $productId = null, ?int $warehouseId = null)`

---

#### `getStockMovements(string $start, string $end)`

---

#### `getProductsForSelect()`

---

#### `getWarehousesForSelect()`

---

#### `getUsersForSelect()`

---

#### `getCommissionsByPeriod(string $start, string $end, ?int $userId = null)`

---

#### `getCommissionStatusLabels()`

---

#### `getCommissionStatusLabel(string $status)`

---

#### `getMovementTypeLabels()`

---

#### `getMovementTypeLabel(string $type)`

---

## ReportTemplate

**Tipo:** Class  
**Arquivo:** `app/models/ReportTemplate.php`  
**Namespace:** `Akti\Models`  

Model de templates de relatórios personalizados.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe ReportTemplate.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `readAll(?int $userId = null): array`

Retorna todos os registros.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$userId` | `int|null` | ID do usuário |

**Retorno:** `array — */`

---

### Funções auxiliares do arquivo

#### `readOne(int $id)`

---

#### `create(array $data)`

---

#### `update(int $id, array $data)`

---

#### `delete(int $id)`

---

#### `getAvailableEntities()`

---

#### `executeReport(int $id)`

---

## Shipment

**Tipo:** Class  
**Arquivo:** `app/models/Shipment.php`  
**Namespace:** `Akti\Models`  

Model de remessas/entregas.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe Shipment.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `create(array $data): int`

Cria um novo registro no banco de dados.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$data` | `array` | Dados para processamento |

**Retorno:** `int — */`

---

##### `readAll(int $tenantId, array $filters = []): array`

Retorna todos os registros.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$tenantId` | `int` | ID do tenant |
| `$filters` | `array` | Filtros aplicados |

**Retorno:** `array — */`

---

### Funções auxiliares do arquivo

#### `readPaginated(int $tenantId, int $page = 1, int $perPage = 15, array $filters = [])`

---

#### `readOne(int $id, int $tenantId)`

---

#### `readByOrder(int $orderId, int $tenantId)`

---

#### `update(int $id, int $tenantId, array $data)`

---

#### `updateStatus(int $id, int $tenantId, string $status)`

---

#### `addEvent(array $data)`

---

#### `getEvents(int $shipmentId, int $tenantId)`

---

#### `getCarriers(int $tenantId)`

---

#### `saveCarrier(array $data)`

---

#### `getDashboardStats(int $tenantId)`

---

## SiteBuilder

**Tipo:** Class  
**Arquivo:** `app/models/SiteBuilder.php`  
**Namespace:** `Akti\Models`  

Model para o Site Builder.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor da classe SiteBuilder.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |

---

##### `getSettings(int $tenantId): array`

Obtém todas as configurações do tenant (tema + conteúdo de páginas).

**Retorno:** `array<string, — string> Mapa key => value`

---

##### `getSettingsByGroup(int $tenantId, string $group): array`

Obtém configurações filtradas por grupo.

**Retorno:** `array<string, — string> Mapa key => value`

---

##### `getSetting(int $tenantId, string $key): ?string`

Obtém o valor de uma configuração específica.

---

##### `saveSetting(int $tenantId, string $key, string $value, string $group = 'general'): bool`

Salva uma configuração (insert ou update via UPSERT).

---

##### `saveSettingsBatch(int $tenantId, array $settings, string $group = 'general'): bool`

Salva múltiplas configurações de um grupo em transação.

---

##### `getThemeSettings(int $tenantId): array`

Obtém dados específicos.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$tenantId` | `int` | ID do tenant |

**Retorno:** `array — */`

---

##### `saveThemeSettings(int $tenantId, array $settings, string $group = 'general'): bool`

Salva dados.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$tenantId` | `int` | ID do tenant |
| `$settings` | `array` | Configurações |
| `$group` | `string` | Group |

**Retorno:** `bool — */`

---

## Stock

**Tipo:** Class  
**Arquivo:** `app/models/Stock.php`  
**Namespace:** `Akti\Models`  

Stock Model

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor da classe Stock.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |

---

##### `countWarehouses()`

Conta o total de armazéns cadastrados

---

##### `getAllWarehouses($onlyActive = true)`

Obtém dados específicos.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$onlyActive` | `mixed` | Only active |

---

##### `getWarehouse($id)`

Obtém dados específicos.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `mixed` | ID do registro |

---

##### `createWarehouse($data)`

Create warehouse.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$data` | `mixed` | Dados para processamento |

---

##### `updateWarehouse($data)`

Update warehouse.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$data` | `mixed` | Dados para processamento |

---

##### `deleteWarehouse($id)`

Delete warehouse.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `mixed` | ID do registro |

---

##### `getStockItems($warehouseId = null, $search = '', $lowStock = false)`

Listar itens do estoque com filtros

---

##### `getOrCreateStockItem($warehouseId, $productId, $combinationId = null)`

Obter ou criar item de estoque

---

##### `getStockItem($id)`

Obtém dados específicos.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `mixed` | ID do registro |

---

##### `updateStockItemMeta($id, $minQuantity, $locationCode)`

Update stock item meta.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `mixed` | ID do registro |
| `$minQuantity` | `mixed` | Min quantity |
| `$locationCode` | `mixed` | Location code |

---

##### `addMovement($data)`

Registrar movimentação de estoque

---

##### `getMovement($id)`

Buscar uma movimentação pelo ID

---

##### `updateMovement($id, $data)`

Atualizar uma movimentação e recalcular saldo do stock_item

---

##### `deleteMovement($id)`

Excluir uma movimentação e reverter o saldo do stock_item

---

##### `getMovements($filters = [])`

Listar movimentações com filtros

---

### Funções auxiliares do arquivo

#### `getMovementsPaginated(array $filters = [], int $page = 1, int $perPage = 25)`

---

#### `getDashboardSummary()`

---

#### `getLowStockItems($limit = 10)`

---

#### `getProductsForSelection()`

---

#### `getProductCombinations($productId)`

---

#### `getDefaultWarehouse()`

---

#### `setDefaultWarehouse($id)`

---

#### `getProductStockInWarehouse($warehouseId, $productId, $combinationId = null)`

---

#### `addStockDeduction($data)`

---

#### `getActiveDeductions($orderId)`

---

#### `reverseDeductions($orderId, $userId = null)`

---

#### `ensureDeductionsTable()`

---

#### `ensureDefaultColumn()`

---

#### `ensureOrderWarehouseColumn()`

---

## Subcategory

**Tipo:** Class  
**Arquivo:** `app/models/Subcategory.php`  
**Namespace:** `Akti\Models`  

Model de subcategorias de produtos.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |
| private | `$table_name` | Não |
| protected | `$id` | Não |
| protected | `$category_id` | Não |
| protected | `$name` | Não |
| protected | `$show_in_store` | Não |
| protected | `$free_shipping` | Não |
| private | `$fillable` | Sim |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor da classe Subcategory.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |

---

##### `__get(string $name): mixed`

__get.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$name` | `string` | Nome |

**Retorno:** `mixed — */`

---

##### `__set(string $name, mixed $value): void`

__set.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$name` | `string` | Nome |
| `$value` | `mixed` | Valor |

**Retorno:** `void — */`

---

##### `readByCategoryId($categoryId)`

Read by category id.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$categoryId` | `mixed` | Category id |

---

##### `create()`

Cria um novo registro no banco de dados.

---

##### `readAll()`

Retorna todos os registros.

---

##### `countAll(): int`

Retorna a quantidade total de subcategorias.

**Retorno:** `int — */`

---

##### `readPaginated(int $page = 1, int $perPage = 15): array`

Retorna subcategorias paginadas com nome da categoria.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$page` | `int` | Página atual (1-based) |
| `$perPage` | `int` | Registros por página |

**Retorno:** `array — */`

---

### Funções auxiliares do arquivo

#### `readOne($id)`

---

#### `update($id, $name, $categoryId, $showInStore = null, $freeShipping = null)`

---

#### `delete($id)`

---

#### `countProducts($subId)`

---

## Supplier

**Tipo:** Class  
**Arquivo:** `app/models/Supplier.php`  
**Namespace:** `Akti\Models`  

Model de fornecedores.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe Supplier.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `readAll(): array`

Retorna todos os registros.

**Retorno:** `array — */`

---

##### `readPaginated(int $page = 1, int $perPage = 15, string $search = ''): array`

Read paginated.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$page` | `int` | Número da página |
| `$perPage` | `int` | Registros por página |
| `$search` | `string` | Termo de busca |

**Retorno:** `array — */`

---

### Funções auxiliares do arquivo

#### `readOne(int $id)`

---

#### `create(array $data)`

---

#### `update(int $id, array $data)`

---

#### `delete(int $id)`

---

#### `countAll()`

---

## Supply

**Tipo:** Class  
**Arquivo:** `app/models/Supply.php`  
**Namespace:** `Akti\Models`  

Model de insumos/matérias-primas.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe Supply.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `readAll(): array`

Retorna todos os registros.

**Retorno:** `array — */`

---

##### `readPaginated(int $page = 1, int $perPage = 15, array $filters = []): array`

Read paginated.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$page` | `int` | Número da página |
| `$perPage` | `int` | Registros por página |
| `$filters` | `array` | Filtros aplicados |

**Retorno:** `array — */`

---

### Funções auxiliares do arquivo

#### `readOne(int $id)`

---

#### `create(array $data)`

---

#### `update(int $id, array $data)`

---

#### `delete(int $id)`

---

#### `countAll(array $filters = [])`

---

#### `generateNextCode()`

---

#### `codeExists(string $code, ?int $excludeId = null)`

---

#### `getCategories()`

---

#### `createCategory(array $data)`

---

#### `updateCategory(int $id, array $data)`

---

#### `deleteCategory(int $id)`

---

#### `getSuppliers(int $supplyId)`

---

#### `linkSupplier(array $data)`

---

#### `updateSupplierLink(int $id, array $data)`

---

#### `unlinkSupplier(int $id)`

---

#### `setPreferredSupplier(int $supplyId, int $supplierId)`

---

#### `getPreferredSupplier(int $supplyId)`

---

#### `getSupplierInsumos(int $supplierId)`

---

#### `getPriceHistory(int $supplyId, ?int $supplierId = null, int $limit = 50)`

---

#### `recordPriceHistory(array $data)`

---

#### `updateCostPrice(int $supplyId, float $newCost)`

---

#### `getProductSupplies(int $productId)`

---

#### `getSupplyProducts(int $supplyId)`

---

#### `addProductSupply(array $data)`

---

#### `updateProductSupply(int $id, array $data)`

---

#### `removeProductSupply(int $id)`

---

#### `calculateProductCost(int $productId)`

---

#### `estimateConsumption(int $productId, float $qty)`

---

#### `getAffectedProducts(int $supplyId)`

---

#### `getWhereUsedImpact(int $supplyId, float $newCMP)`

---

#### `searchSelect2(string $term, int $limit = 20)`

---

## SupplyStock

**Tipo:** Class  
**Arquivo:** `app/models/SupplyStock.php`  
**Namespace:** `Akti\Models`  

Model de estoque de insumos.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe SupplyStock.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `getItems(array $filters = [], int $page = 1, int $perPage = 20): array`

Obtém dados específicos.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$filters` | `array` | Filtros aplicados |
| `$page` | `int` | Número da página |
| `$perPage` | `int` | Registros por página |

**Retorno:** `array — */`

---

### Funções auxiliares do arquivo

#### `getOrCreateItem(int $warehouseId, int $supplyId, ?string $batchNumber = null)`

---

#### `updateQuantity(int $itemId, float $newQuantity)`

---

#### `getTotalStock(int $supplyId)`

---

#### `getDashboardSummary(?int $warehouseId = null)`

---

#### `getLowStockItems(int $limit = 20)`

---

#### `addMovement(array $data)`

---

#### `getMovements(array $filters = [], int $page = 1, int $perPage = 20)`

---

#### `getBatchesBySupply(int $supplyId, int $warehouseId)`

---

#### `getExpiringBatches(int $days = 30, int $limit = 20)`

---

#### `getExpiredBatches(int $limit = 20)`

---

#### `getReorderItems()`

---

#### `getWarehouses()`

---

## Ticket

**Tipo:** Class  
**Arquivo:** `app/models/Ticket.php`  
**Namespace:** `Akti\Models`  

Model de tickets de suporte.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe Ticket.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `create(array $data): int`

Cria um novo registro no banco de dados.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$data` | `array` | Dados para processamento |

**Retorno:** `int — */`

---

##### `readAll(int $tenantId): array`

Retorna todos os registros.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$tenantId` | `int` | ID do tenant |

**Retorno:** `array — */`

---

##### `readPaginated(int $tenantId, int $page = 1, int $perPage = 20, array $filters = []): array`

Read paginated.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$tenantId` | `int` | ID do tenant |
| `$page` | `int` | Número da página |
| `$perPage` | `int` | Registros por página |
| `$filters` | `array` | Filtros aplicados |

**Retorno:** `array — */`

---

### Funções auxiliares do arquivo

#### `readOne(int $id, int $tenantId)`

---

#### `update(int $id, int $tenantId, array $data)`

---

#### `updateStatus(int $id, int $tenantId, string $status)`

---

#### `delete(int $id, int $tenantId)`

---

#### `getMessages(int $ticketId, int $tenantId)`

---

#### `addMessage(array $data)`

---

#### `getCategories(int $tenantId)`

---

#### `getDashboardStats(int $tenantId)`

---

#### `generateTicketNumber(int $tenantId)`

---

## Transaction

**Tipo:** Class  
**Arquivo:** `app/models/Transaction.php`  
**Namespace:** `Akti\Models`  

Transaction — CRUD e consultas de transações financeiras.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor da classe Transaction.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |

---

##### `create(array $data): mixed`

Proxy para Financial::addTransaction().

---

##### `readOne(int $id): mixed`

Proxy para Financial::getTransactionById().

---

##### `update(int $id, array $data): bool`

Proxy para Financial::updateTransaction().

---

##### `delete(int $id, ?string $reason = null): bool`

Proxy para Financial::deleteTransaction().

---

##### `restore(int $id): bool`

Proxy para Financial::restoreTransaction().

---

##### `getAll(array $filters = []): array`

Proxy para Financial::getTransactions().

---

##### `getPaginated(array $filters = [], int $page = 1, int $perPage = 25): array`

Proxy para Financial::getTransactionsPaginated().

---

## User

**Tipo:** Class  
**Arquivo:** `app/models/User.php`  
**Namespace:** `Akti\Models`  

Modelo User

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |
| private | `$table_name` | Não |
| protected | `$id` | Não |
| protected | `$name` | Não |
| protected | `$email` | Não |
| protected | `$password` | Não |
| protected | `$role` | Não |
| protected | `$group_id` | Não |
| private | `$fillable` | Sim |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO (já configurada para o tenant atual) |

---

##### `__get(string $name): mixed`

__get.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$name` | `string` | Nome |

**Retorno:** `mixed — */`

---

##### `__set(string $name, mixed $value): void`

__set.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$name` | `string` | Nome |
| `$value` | `mixed` | Valor |

**Retorno:** `void — */`

---

##### `setPassword(string $password): void`

Define valor específico.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$password` | `string` | Senha |

**Retorno:** `void — */`

---

##### `getPassword(): ?string`

Obtém dados específicos.

**Retorno:** `string|null — */`

---

##### `login($email, $password)`

Tenta autenticar um usuário pelo e-mail e senha.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$email` | `string` | * @param string $password |

**Retorno:** `bool — true se autenticado, false caso contrário`

---

##### `readAll()`

Retorna um PDOStatement com todos os usuários (junto com o nome do grupo quando houver).

**Retorno:** `array — */`

---

##### `countAll()`

Retorna a quantidade total de usuários.

**Retorno:** `int — */`

---

##### `emailExists(string $email, ?int $excludeId = null): bool`

Verifica se um e-mail já existe na tabela de usuários, opcionalmente excluindo um ID.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$email` | `string` | E-mail a verificar |
| `$excludeId` | `int|null` | ID a excluir da verificação (para edição) |

**Retorno:** `bool — true se o e-mail já está em uso`

---

##### `readPaginated(int $page = 1, int $perPage = 15): array`

Retorna usuários paginados com JOIN no grupo.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$page` | `int` | Página atual (1-based) |
| `$perPage` | `int` | Registros por página |

**Retorno:** `array — */`

---

### Funções auxiliares do arquivo

#### `create()`

---

#### `readOne($id)`

---

#### `update()`

---

#### `delete($id)`

---

#### `checkPermission($userId, $page)`

---

#### `getAllowedSectorIds($userId)`

---

## UserGroup

**Tipo:** Class  
**Arquivo:** `app/models/UserGroup.php`  
**Namespace:** `Akti\Models`  

Classe UserGroup

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |
| private | `$table_name` | Não |
| public | `$id` | Não |
| public | `$name` | Não |
| public | `$description` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO |

---

##### `readAll()`

Retorna todos os grupos cadastrados.

**Retorno:** `array — */`

---

##### `countAll(): int`

Retorna a quantidade total de grupos.

**Retorno:** `int — */`

---

##### `readPaginated(int $page = 1, int $perPage = 15): array`

Retorna grupos paginados.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$page` | `int` | Página atual (1-based) |
| `$perPage` | `int` | Registros por página |

**Retorno:** `array — */`

---

### Funções auxiliares do arquivo

#### `create()`

---

#### `readOne($id)`

---

#### `update()`

---

#### `delete($id)`

---

#### `addPermission($groupId, $pageName)`

---

#### `getPermissions($groupId)`

---

#### `deletePermissions($groupId)`

---

#### `getAllowedSectors($groupId)`

---

#### `getAllowedStages($groupId)`

---

#### `hasSectorPermission($groupId, $sectorId)`

---

#### `hasStagePermission($groupId, $stageKey)`

---

## Walkthrough

**Tipo:** Class  
**Arquivo:** `app/models/Walkthrough.php`  
**Namespace:** `Akti\Models`  

Classe Walkthrough

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |
| private | `$table` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO |

---

##### `needsWalkthrough(int $userId): bool`

Verifica se o usuário precisa ver o walkthrough.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$userId` | `int` | * @return bool |

**Retorno:** `bool — */`

---

### Funções auxiliares do arquivo

#### `getStatus(int $userId)`

---

#### `start(int $userId)`

---

#### `complete(int $userId)`

---

#### `skip(int $userId)`

---

#### `saveStep(int $userId, int $step)`

---

#### `ensureRecord(int $userId)`

---

#### `reset(int $userId)`

---

## WhatsAppMessage

**Tipo:** Class  
**Arquivo:** `app/models/WhatsAppMessage.php`  
**Namespace:** `Akti\Models`  

Model de mensagens do WhatsApp.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe WhatsAppMessage.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `getConfig(int $tenantId): ?array`

Obtém dados específicos.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$tenantId` | `int` | ID do tenant |

**Retorno:** `array|null — */`

---

##### `saveConfig(int $tenantId, array $data): bool`

Salva dados.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$tenantId` | `int` | ID do tenant |
| `$data` | `array` | Dados para processamento |

**Retorno:** `bool — */`

---

##### `getTemplates(int $tenantId): array`

Obtém dados específicos.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$tenantId` | `int` | ID do tenant |

**Retorno:** `array — */`

---

##### `saveTemplate(array $data): int`

Salva dados.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$data` | `array` | Dados para processamento |

**Retorno:** `int — */`

---

##### `logMessage(array $data): int`

Registra informação no log.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$data` | `array` | Dados para processamento |

**Retorno:** `int — */`

---

##### `updateMessageStatus(int $id, string $status, ?string $externalId = null, ?string $error = null): bool`

Update message status.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `int` | ID do registro |
| `$status` | `string` | Status do registro |
| `$externalId` | `string|null` | External id |
| `$error` | `string|null` | Error |

**Retorno:** `bool — */`

---

### Funções auxiliares do arquivo

#### `getMessages(int $tenantId, int $page = 1, int $perPage = 50)`

---

#### `getDashboardStats(int $tenantId)`

---

## WorkflowRule

**Tipo:** Class  
**Arquivo:** `app/models/WorkflowRule.php`  
**Namespace:** `Akti\Models`  

Model de regras de workflow automatizado.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe WorkflowRule.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `readAll(): array`

Retorna todos os registros.

**Retorno:** `array — */`

---

##### `readActive(): array`

Read active.

**Retorno:** `array — */`

---

##### `readByEvent(string $event): array`

Read by event.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$event` | `string` | Event |

**Retorno:** `array — */`

---

##### `readOne(int $id): ?array`

Retorna um registro pelo ID.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `int` | ID do registro |

**Retorno:** `array|null — */`

---

##### `create(array $data): int`

Cria um novo registro no banco de dados.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$data` | `array` | Dados para processamento |

**Retorno:** `int — */`

---

##### `update(int $id, array $data): bool`

Atualiza um registro existente.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `int` | ID do registro |
| `$data` | `array` | Dados para processamento |

**Retorno:** `bool — */`

---

##### `delete(int $id): bool`

Remove um registro pelo ID.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `int` | ID do registro |

**Retorno:** `bool — */`

---

##### `toggle(int $id): bool`

Alterna estado de propriedade.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `int` | ID do registro |

**Retorno:** `bool — */`

---

##### `logExecution(array $data): int`

Registra informação no log.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$data` | `array` | Dados para processamento |

**Retorno:** `int — */`

---

##### `getLogs(int $ruleId, int $limit = 50): array`

Obtém dados específicos.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$ruleId` | `int` | Rule id |
| `$limit` | `int` | Limite de registros |

**Retorno:** `array — */`

---

##### `updatePriority(int $id, int $priority): bool`

Update priority.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `int` | ID do registro |
| `$priority` | `int` | Priority |

**Retorno:** `bool — */`

---

