# Services (Serviços)

> Camada de serviços: lógica de negócio complexa, integrações, processamento.

**Total de arquivos:** 90

---

## Índice

- [AiAssistantService](#aiassistantservice) — `app/services/AiAssistantService.php`
- [AuditLogService](#auditlogservice) — `app/services/AuditLogService.php`
- [AuthService](#authservice) — `app/services/AuthService.php`
- [BiService](#biservice) — `app/services/BiService.php`
- [CatalogCartService](#catalogcartservice) — `app/services/CatalogCartService.php`
- [CatalogQuoteService](#catalogquoteservice) — `app/services/CatalogQuoteService.php`
- [CategoryService](#categoryservice) — `app/services/CategoryService.php`
- [CheckoutService](#checkoutservice) — `app/services/CheckoutService.php`
- [CommissionAutoService](#commissionautoservice) — `app/services/CommissionAutoService.php`
- [CommissionEngine](#commissionengine) — `app/services/CommissionEngine.php`
- [CommissionService](#commissionservice) — `app/services/CommissionService.php`
- [AuthServiceInterface (Interface)](#authserviceinterface) — `app/services/Contracts/AuthServiceInterface.php`
- [CheckoutServiceInterface (Interface)](#checkoutserviceinterface) — `app/services/Contracts/CheckoutServiceInterface.php`
- [EmailServiceInterface (Interface)](#emailserviceinterface) — `app/services/Contracts/EmailServiceInterface.php`
- [NfeServiceInterface (Interface)](#nfeserviceinterface) — `app/services/Contracts/NfeServiceInterface.php`
- [PipelinePaymentServiceInterface (Interface)](#pipelinepaymentserviceinterface) — `app/services/Contracts/PipelinePaymentServiceInterface.php`
- [CustomerContactService](#customercontactservice) — `app/services/CustomerContactService.php`
- [CustomerExportService](#customerexportservice) — `app/services/CustomerExportService.php`
- [CustomerFormService](#customerformservice) — `app/services/CustomerFormService.php`
- [CustomerImportService](#customerimportservice) — `app/services/CustomerImportService.php`
- [CustomerOrderHistoryService](#customerorderhistoryservice) — `app/services/CustomerOrderHistoryService.php`
- [DemandPredictionService](#demandpredictionservice) — `app/services/DemandPredictionService.php`
- [EmailService](#emailservice) — `app/services/EmailService.php`
- [ExternalApiService](#externalapiservice) — `app/services/ExternalApiService.php`
- [FileManager](#filemanager) — `app/services/FileManager.php`
- [FinancialAuditService](#financialauditservice) — `app/services/FinancialAuditService.php`
- [FinancialImportService](#financialimportservice) — `app/services/FinancialImportService.php`
- [FinancialReportService](#financialreportservice) — `app/services/FinancialReportService.php`
- [HeaderDataService](#headerdataservice) — `app/services/HeaderDataService.php`
- [InstallmentService](#installmentservice) — `app/services/InstallmentService.php`
- [MarketplaceConnector](#marketplaceconnector) — `app/services/MarketplaceConnector.php`
- [MercadoLivreConnector](#mercadolivreconnector) — `app/services/MercadoLivreConnector.php`
- [NfceDanfeGenerator](#nfcedanfegenerator) — `app/services/NfceDanfeGenerator.php`
- [NfceXmlBuilder](#nfcexmlbuilder) — `app/services/NfceXmlBuilder.php`
- [NfeAuditService](#nfeauditservice) — `app/services/NfeAuditService.php`
- [NfeBackupManagementService](#nfebackupmanagementservice) — `app/services/NfeBackupManagementService.php`
- [NfeBackupService](#nfebackupservice) — `app/services/NfeBackupService.php`
- [](#) — `app/services/NfeBackupService.php`
- [NfeBatchDownloadService](#nfebatchdownloadservice) — `app/services/NfeBatchDownloadService.php`
- [NfeCancellationService](#nfecancellationservice) — `app/services/NfeCancellationService.php`
- [NfeContingencyService](#nfecontingencyservice) — `app/services/NfeContingencyService.php`
- [NfeCorrectionService](#nfecorrectionservice) — `app/services/NfeCorrectionService.php`
- [NfeDanfeCustomizer](#nfedanfecustomizer) — `app/services/NfeDanfeCustomizer.php`
- [NfeDashboardService](#nfedashboardservice) — `app/services/NfeDashboardService.php`
- [NfeDetailService](#nfedetailservice) — `app/services/NfeDetailService.php`
- [NfeDistDFeService](#nfedistdfeservice) — `app/services/NfeDistDFeService.php`
- [NfeDownloadService](#nfedownloadservice) — `app/services/NfeDownloadService.php`
- [NfeEmissionService](#nfeemissionservice) — `app/services/NfeEmissionService.php`
- [NfeExportService](#nfeexportservice) — `app/services/NfeExportService.php`
- [NfeFiscalReportService](#nfefiscalreportservice) — `app/services/NfeFiscalReportService.php`
- [NfeManifestationService](#nfemanifestationservice) — `app/services/NfeManifestationService.php`
- [NfeOrderDataService](#nfeorderdataservice) — `app/services/NfeOrderDataService.php`
- [NfePdfGenerator](#nfepdfgenerator) — `app/services/NfePdfGenerator.php`
- [NfeQueryService](#nfequeryservice) — `app/services/NfeQueryService.php`
- [NfeQueueService](#nfequeueservice) — `app/services/NfeQueueService.php`
- [NfeSefazClient](#nfesefazclient) — `app/services/NfeSefazClient.php`
- [NfeService](#nfeservice) — `app/services/NfeService.php`
- [NfeSintegraService](#nfesintegraservice) — `app/services/NfeSintegraService.php`
- [NfeSpedFiscalService](#nfespedfiscalservice) — `app/services/NfeSpedFiscalService.php`
- [NfeStorageService](#nfestorageservice) — `app/services/NfeStorageService.php`
- [NfeWebhookManagementService](#nfewebhookmanagementservice) — `app/services/NfeWebhookManagementService.php`
- [NfeWebhookService](#nfewebhookservice) — `app/services/NfeWebhookService.php`
- [NfeXmlBuilder](#nfexmlbuilder) — `app/services/NfeXmlBuilder.php`
- [NfeXmlValidator](#nfexmlvalidator) — `app/services/NfeXmlValidator.php`
- [OrderItemService](#orderitemservice) — `app/services/OrderItemService.php`
- [PipelineAlertService](#pipelinealertservice) — `app/services/PipelineAlertService.php`
- [PipelineDetailService](#pipelinedetailservice) — `app/services/PipelineDetailService.php`
- [PipelinePaymentService](#pipelinepaymentservice) — `app/services/PipelinePaymentService.php`
- [PipelineService](#pipelineservice) — `app/services/PipelineService.php`
- [Portal2faService](#portal2faservice) — `app/services/Portal2faService.php`
- [PortalAdminService](#portaladminservice) — `app/services/PortalAdminService.php`
- [PortalAuthService](#portalauthservice) — `app/services/PortalAuthService.php`
- [PortalAvatarService](#portalavatarservice) — `app/services/PortalAvatarService.php`
- [PortalCartService](#portalcartservice) — `app/services/PortalCartService.php`
- [PortalLang](#portallang) — `app/services/PortalLang.php`
- [PortalOrderService](#portalorderservice) — `app/services/PortalOrderService.php`
- [ProductGradeService](#productgradeservice) — `app/services/ProductGradeService.php`
- [ProductImportService](#productimportservice) — `app/services/ProductImportService.php`
- [ProductionCostService](#productioncostservice) — `app/services/ProductionCostService.php`
- [ReportExcelService](#reportexcelservice) — `app/services/ReportExcelService.php`
- [ReportPdfService](#reportpdfservice) — `app/services/ReportPdfService.php`
- [SettingsService](#settingsservice) — `app/services/SettingsService.php`
- [SpedExportService](#spedexportservice) — `app/services/SpedExportService.php`
- [StockMovementService](#stockmovementservice) — `app/services/StockMovementService.php`
- [SupplyStockMovementService](#supplystockmovementservice) — `app/services/SupplyStockMovementService.php`
- [TaxCalculator](#taxcalculator) — `app/services/TaxCalculator.php`
- [ThumbnailService](#thumbnailservice) — `app/services/ThumbnailService.php`
- [TransactionService](#transactionservice) — `app/services/TransactionService.php`
- [TwigRenderer](#twigrenderer) — `app/services/TwigRenderer.php`
- [WhatsAppService](#whatsappservice) — `app/services/WhatsAppService.php`
- [WorkflowEngine](#workflowengine) — `app/services/WorkflowEngine.php`

---

## AiAssistantService

**Tipo:** Class  
**Arquivo:** `app/services/AiAssistantService.php`  
**Namespace:** `Akti\Services`  

AI Assistant Service — integrates with OpenAI-compatible APIs (GPT, Ollama, etc.)

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$apiKey` | Não |
| private | `$apiUrl` | Não |
| private | `$model` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor da classe AiAssistantService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |

---

##### `isConfigured(): bool`

Verifica uma condição booleana.

**Retorno:** `bool — */`

---

##### `chat(array $messages): array`

Send a chat message and get a response.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$messages` | `array` | Array of ['role'=>'user/assistant/system', 'content'=>'...'] |

**Retorno:** `array — ['success'=>bool, 'message'=>string, 'usage'=>array]`

---

##### `getHistory(int $userId, int $limit = 50): array`

Get conversation history for a user.

---

##### `saveMessage(int $userId, string $role, string $content): void`

Save a message to conversation history.

---

##### `clearHistory(int $userId): void`

Clear conversation history for a user.

---

#### Métodos Private

##### `loadConfig(): void`

Carrega dados.

**Retorno:** `void — */`

---

## AuditLogService

**Tipo:** Class  
**Arquivo:** `app/services/AuditLogService.php`  
**Namespace:** `Akti\Services`  

AuditLogService — Convenience wrapper for logging auditable actions.

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor da classe AuditLogService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |

---

##### `log(string $action,
        string $entityType,
        $entityId,
        array $oldValues = [],
        array $newValues = [],
        ?int $userId = null,
        ?string $ipAddress = null): void`

Log an audit entry.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$action` | `string` | e.g. 'created', 'updated', 'deleted' |
| `$entityType` | `string` | e.g. 'order', 'customer', 'supplier' |
| `$entityId` | `int|string` | * @param array       $oldValues  Previous values (for updates) |
| `$newValues` | `array` | New values |
| `$userId` | `int|null` | * @param string|null $ipAddress |

---

## AuthService

**Tipo:** Class  
**Arquivo:** `app/services/AuthService.php`  
**Namespace:** `Akti\Services`  
**Implementa:** `Contracts\AuthServiceInterface`  

AuthService — Lógica de autenticação (login, brute-force, portal unificado).

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |
| private | `$userModel` | Não |
| private | `$loginAttempt` | Não |
| private | `$logger` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db, User $userModel, LoginAttempt $loginAttempt, Logger $logger)`

Construtor da classe AuthService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |
| `$userModel` | `User` | User model |
| `$loginAttempt` | `LoginAttempt` | Login attempt |
| `$logger` | `Logger` | Logger |

---

##### `attemptLogin(string $email,
        string $password,
        string $ip,
        string $postedTenant,
        string $resolvedTenant,
        ?string $captchaResponse = null): array`

Processar tentativa de login.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$email` | `string` | * @param string $password |
| `$ip` | `string` | * @param string $postedTenant |
| `$resolvedTenant` | `string` | * @param string|null $captchaResponse |

**Retorno:** `array — ['success' => bool, 'error' => string|null, 'show_captcha' => bool, 'redirect' => string|null, 'type' => 'admin'|'portal'|null]`

---

### Funções auxiliares do arquivo

#### `handleAdminLoginSuccess(string $email, string $ip)`

---

#### `attemptMasterLogin(string $email, string $password, string $ip)`

---

#### `attemptPortalLogin(string $email, string $password, string $ip)`

---

## BiService

**Tipo:** Class  
**Arquivo:** `app/services/BiService.php`  
**Namespace:** `Akti\Services`  

Class BiService.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$order` | Não |
| private | `$financial` | Não |
| private | `$pipeline` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor da classe BiService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |

---

##### `getSalesDashboard(string $dateFrom, string $dateTo): array`

Dashboard de vendas: faturamento, ticket médio, conversão, por período.

---

##### `getProductionDashboard(string $dateFrom, string $dateTo): array`

Dashboard de produção: throughput, gargalos, tempo médio por etapa.

---

##### `getFinancialDashboard(string $dateFrom, string $dateTo): array`

Dashboard financeiro: fluxo de caixa, inadimplência, DRE simplificado.

---

##### `drillDown(string $type, array $filters): array`

Drill-down: detalhamento de pedidos por filtro.

---

## CatalogCartService

**Tipo:** Class  
**Arquivo:** `app/services/CatalogCartService.php`  
**Namespace:** `Akti\Services`  

CatalogCartService — Lógica de carrinho do catálogo público.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe CatalogCartService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `addToCart(string $token, int $productId, int $quantity, ?int $combinationId, ?string $gradeDescription): array`

Adicionar produto ao carrinho (item do pedido).

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$token` | `string` | Token do catálogo |
| `$productId` | `int` | * @param int $quantity |
| `$combinationId` | `int|null` | * @param string|null $gradeDescription |

**Retorno:** `array — ['success' => bool, ...]`

---

##### `removeFromCart(string $token, int $itemId): array`

Remover item do carrinho.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$token` | `string` | * @param int $itemId |

**Retorno:** `array — */`

---

##### `updateCartItem(string $token, int $itemId, int $quantity): array`

Atualizar quantidade de um item no carrinho.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$token` | `string` | * @param int $itemId |
| `$quantity` | `int` | * @return array |

**Retorno:** `array — */`

---

##### `getCart(string $token): array`

Buscar carrinho atual por token.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$token` | `string` | * @return array |

**Retorno:** `array — */`

---

#### Métodos Private

##### `buildCartResponse(Order $orderModel, int $orderId): array`

Montar resposta padrão do carrinho atualizado.

---

## CatalogQuoteService

**Tipo:** Class  
**Arquivo:** `app/services/CatalogQuoteService.php`  
**Namespace:** `Akti\Services`  

CatalogQuoteService — Lógica de confirmação/revogação de orçamento via catálogo.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe CatalogQuoteService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `confirmQuote(string $token, string $clientIp): array`

Confirmar orçamento pelo cliente.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$token` | `string` | Token do catálogo |
| `$clientIp` | `string` | IP do cliente |

**Retorno:** `array — ['success' => bool, ...]`

---

### Funções auxiliares do arquivo

#### `revokeQuote(string $token, string $clientIp)`

---

#### `getClientIp()`

---

## CategoryService

**Tipo:** Class  
**Arquivo:** `app/services/CategoryService.php`  
**Namespace:** `Akti\Services`  

CategoryService — Lógica de negócio para categorias e subcategorias.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |
| private | `$categoryGradeModel` | Não |
| private | `$sectorModel` | Não |
| private | `$logger` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db,
        CategoryGrade $categoryGradeModel,
        ProductionSector $sectorModel)`

Construtor da classe CategoryService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |
| `$categoryGradeModel` | `CategoryGrade` | Category grade model |
| `$sectorModel` | `ProductionSector` | Sector model |

---

##### `saveCategoryCombinationsState(int $categoryId, array $combosData): void`

Salva o estado (ativo/inativo) das combinações de grades de uma categoria.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$categoryId` | `int` | ID da categoria |
| `$combosData` | `array` | Array associativo [combination_key => ['is_active' => 0|1]] |

---

##### `saveSubcategoryCombinationsState(int $subcategoryId, array $combosData): void`

Salva o estado (ativo/inativo) das combinações de grades de uma subcategoria.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$subcategoryId` | `int` | ID da subcategoria |
| `$combosData` | `array` | Array associativo [combination_key => ['is_active' => 0|1]] |

---

##### `exportToProducts(string $type,
        int $sourceId,
        array $productIds,
        bool $exportGrades,
        bool $exportSectors): array`

Exporta grades e/ou setores de uma categoria/subcategoria para um conjunto de produtos.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$type` | `string` | 'category' ou 'subcategory' |
| `$sourceId` | `int` | ID da categoria ou subcategoria fonte |
| `$productIds` | `array` | Lista de IDs de produtos destino |
| `$exportGrades` | `bool` | Se deve exportar grades |
| `$exportSectors` | `bool` | Se deve exportar setores |

**Retorno:** `array — Resultado com contagens e eventuais erros`

---

### Funções auxiliares do arquivo

#### `getSourceExportInfo(string $type, int $id)`

---

## CheckoutService

**Tipo:** Class  
**Arquivo:** `app/services/CheckoutService.php`  
**Namespace:** `Akti\Services`  
**Implementa:** `CheckoutServiceInterface`  

Class CheckoutService.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe CheckoutService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `generateToken(array $params): array`

Gera um token de checkout transparente.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$params` | `array` | {order_id, installment_id?, gateway_slug?, allowed_methods?, expires_in_hours?, created_by?} |

**Retorno:** `array — {success, token?, checkout_url?, expires_at?, message?}`

---

##### `processCheckout(string $token, array $paymentData): array`

Processa pagamento vindo do checkout transparente.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$token` | `string` | Hash do token |
| `$paymentData` | `array` | {method, card_token?, customer_document?, customer_name?, customer_email?} |

**Retorno:** `array — Resultado padronizado`

---

##### `cancelToken(int $tokenId): bool`

Cancela um token de checkout.

---

##### `markInstallmentPaidFromCheckout(int $orderId,
        ?int $installmentId,
        float $amount,
        string $paymentMethod,
        ?string $externalId = null): void`

Garante que existe uma parcela para o pedido e marca como paga.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$orderId` | `int` | * @param int|null $installmentId  ID da parcela já existente (do token) |
| `$amount` | `float` | Valor pago |
| `$paymentMethod` | `string` | Método (credit_card, pix, boleto) |
| `$externalId` | `string|null` | ID externo do gateway |

---

### Funções auxiliares do arquivo

#### `expireOldTokens()`

---

#### `getTokenByToken(string $token)`

---

#### `getCustomerAddressFromOrder(int $orderId)`

---

#### `computeExpiresInSeconds($expiresAt)`

---

#### `buildWebhookUrl(string $gatewaySlug)`

---

## CommissionAutoService

**Tipo:** Class  
**Arquivo:** `app/services/CommissionAutoService.php`  
**Namespace:** `Akti\Services`  

CommissionAutoService — Serviço de Comissão Automática

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe CommissionAutoService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `getStageGatilho(): string`

Retorna a etapa do pipeline configurada para gerar comissão.

---

##### `getCriterioLiberacao(): string`

Retorna o critério de liberação configurado.

**Retorno:** `string — 'sem_confirmacao' | 'primeira_parcela' | 'pagamento_total'`

---

##### `isAprovacaoAutomatica(): bool`

Retorna se a aprovação é automática.

---

##### `tryAutoCommission(int $orderId): array`

Verifica se o pedido atende às condições para comissão automática

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$orderId` | `int` | * @return array ['triggered' => bool, 'message' => string, 'comissao_id' => int|null] |

**Retorno:** `array — ['triggered' => bool, 'message' => string, 'comissao_id' => int|null]`

---

### Funções auxiliares do arquivo

#### `checkCriterioPagamento(int $orderId, array $order, string $criterio)`

---

#### `hasFirstInstallmentPaid(int $orderId)`

---

#### `isFullyPaidAndConfirmed(int $orderId)`

---

#### `getConfigValue(string $key, string $default = '')`

---

## CommissionEngine

**Tipo:** Class  
**Arquivo:** `app/services/CommissionEngine.php`  
**Namespace:** `Akti\Services`  

CommissionEngine — Motor de Regras de Comissão (Rule Engine)

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$model` | Não |
| private | `$db` | Não |
| private | `$strategies` | Não |
| private | `$resolvers` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db, Commission $model)`

Construtor da classe CommissionEngine.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |
| `$model` | `Commission` | Model |

---

##### `registerStrategy(string $tipo, callable $callback): void`

Registra uma nova estratégia de cálculo.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$tipo` | `string` | Identificador (ex: 'percentual', 'valor_fixo', 'faixa', 'equipe') |
| `$callback` | `callable` | fn(array $regra, array $context): array |

---

##### `registerResolver(string $nome, callable $callback): void`

Registra um novo resolver de regra.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$nome` | `string` | Identificador (ex: 'usuario', 'grupo', 'equipe') |
| `$callback` | `callable` | fn(array $context): ?array  Retorna regra ou null |

---

##### `resolveRegra(array $context): ?array`

Resolve qual regra aplicar para o contexto dado.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$context` | `array` | [ |

**Retorno:** `array|null — ['regra' => array, 'origem' => string]`

---

##### `calcular(array $context): array`

Calcula a comissão para um contexto (sem registrar).

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$context` | `array` | Dados da venda |

**Retorno:** `array — Resultado: valor_comissao, percentual_aplicado, regra, origem, etc.`

---

#### Métodos Protected

##### `resolveUsuario(array $context): ?array`

Resolve dependência ou valor.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$context` | `array` | Contexto adicional |

**Retorno:** `array|null — */`

---

##### `resolveGrupo(array $context): ?array`

Resolve dependência ou valor.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$context` | `array` | Contexto adicional |

**Retorno:** `array|null — */`

---

##### `resolveProduto(array $context): ?array`

Resolve dependência ou valor.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$context` | `array` | Contexto adicional |

**Retorno:** `array|null — */`

---

##### `resolvePadrao(array $context): ?array`

Resolve dependência ou valor.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$context` | `array` | Contexto adicional |

**Retorno:** `array|null — */`

---

### Funções auxiliares do arquivo

#### `calcularERegistrar(array $context)`

---

#### `calculatePercentual(array $regra, array $params)`

---

#### `calculateValorFixo(array $regra, array $params)`

---

#### `calculateFaixa(array $regra, array $params)`

---

#### `getValorBase(string $baseCalculo, array $context)`

---

## CommissionService

**Tipo:** Class  
**Arquivo:** `app/services/CommissionService.php`  
**Namespace:** `Akti\Services`  

CommissionService — Camada de Serviço para Comissões

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$engine` | Não |
| private | `$model` | Não |
| private | `$db` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db, CommissionEngine $engine, Commission $model)`

Construtor da classe CommissionService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |
| `$engine` | `CommissionEngine` | Engine |
| `$model` | `Commission` | Model |

---

##### `getAllFormas(): array`

Obtém dados específicos.

**Retorno:** `array — */`

---

##### `getForma(int $id): ?array`

Obtém dados específicos.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `int` | ID do registro |

**Retorno:** `array|null — */`

---

##### `createForma(array $data): array`

Create forma.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$data` | `array` | Dados para processamento |

**Retorno:** `array — */`

---

##### `updateForma(int $id, array $data): array`

Update forma.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `int` | ID do registro |
| `$data` | `array` | Dados para processamento |

**Retorno:** `array — */`

---

##### `deleteForma(int $id): array`

Delete forma.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `int` | ID do registro |

**Retorno:** `array — */`

---

##### `getFaixas(int $formaId): array`

Obtém dados específicos.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$formaId` | `int` | Forma id |

**Retorno:** `array — */`

---

##### `getGrupoFormas(?int $groupId = null): array`

Obtém dados específicos.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$groupId` | `int|null` | Group id |

**Retorno:** `array — */`

---

##### `linkGrupoForma(int $groupId, int $formaId): array`

Link grupo forma.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$groupId` | `int` | Group id |
| `$formaId` | `int` | Forma id |

**Retorno:** `array — */`

---

##### `unlinkGrupoForma(int $id): array`

Unlink grupo forma.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `int` | ID do registro |

**Retorno:** `array — */`

---

##### `getUsuarioFormas(?int $userId = null): array`

Obtém dados específicos.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$userId` | `int|null` | ID do usuário |

**Retorno:** `array — */`

---

##### `linkUsuarioForma(int $userId, int $formaId): array`

Link usuario forma.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$userId` | `int` | ID do usuário |
| `$formaId` | `int` | Forma id |

**Retorno:** `array — */`

---

##### `unlinkUsuarioForma(int $id): array`

Unlink usuario forma.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `int` | ID do registro |

**Retorno:** `array — */`

---

##### `getComissaoProdutos(): array`

Obtém dados específicos.

**Retorno:** `array — */`

---

##### `saveComissaoProduto(array $data): array`

Salva dados.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$data` | `array` | Dados para processamento |

**Retorno:** `array — */`

---

##### `deleteComissaoProduto(int $id): array`

Delete comissao produto.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `int` | ID do registro |

**Retorno:** `array — */`

---

##### `simular(array $context): array`

Simula comissão sem registrar.

---

##### `calcularComissao(int $orderId, int $userId, ?string $observacao = null): array`

Calcula e registra comissão para um pedido.

---

##### `getComissoesRegistradas(array $filters = [], int $page = 1, int $perPage = 25): array`

Obtém dados específicos.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$filters` | `array` | Filtros aplicados |
| `$page` | `int` | Número da página |
| `$perPage` | `int` | Registros por página |

**Retorno:** `array — */`

---

##### `getComissaoRegistrada(int $id): ?array`

Obtém dados específicos.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `int` | ID do registro |

**Retorno:** `array|null — */`

---

##### `aprovarComissao(int $id, int $approvedBy): array`

Aprovar comissao.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `int` | ID do registro |
| `$approvedBy` | `int` | Approved by |

**Retorno:** `array — */`

---

##### `pagarComissao(int $id): array`

Pagar comissao.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `int` | ID do registro |

**Retorno:** `array — */`

---

##### `cancelarComissao(int $id): array`

Cancela operação.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `int` | ID do registro |

**Retorno:** `array — */`

---

##### `aprovarEmLote(array $ids, int $approvedBy): array`

Aprovar múltiplas comissões (muda para aguardando_pagamento).

---

### Funções auxiliares do arquivo

#### `pagarEmLote(array $ids)`

---

#### `registrarTransacaoFinanceira(int $comissaoId)`

---

#### `getVendedoresComPendentes()`

---

#### `getComissoesPorVendedor(int $userId, ?string $statusFilter = null)`

---

#### `getDashboardSummary(?int $month = null, ?int $year = null)`

---

#### `getConfig()`

---

#### `saveConfig(array $configs)`

---

#### `getAuxData()`

---

#### `getUsuariosComRegras()`

---

## AuthServiceInterface

**Tipo:** Interface  
**Arquivo:** `app/services/Contracts/AuthServiceInterface.php`  
**Namespace:** `Akti\Services\Contracts`  

Interface AuthServiceInterface.

### Métodos

#### Métodos Public

##### `attemptLogin(string $email,
        string $password,
        string $ip,
        string $postedTenant,
        string $resolvedTenant,
        ?string $captchaResponse = null): array`

Attempt login.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$email` | `string` | Endereço de email |
| `$password` | `string` | Senha |
| `$ip` | `string` | Ip |
| `$postedTenant` | `string` | Posted tenant |
| `$resolvedTenant` | `string` | Resolved tenant |
| `$captchaResponse` | `string|null` | Captcha response |

**Retorno:** `array — */`

---

## CheckoutServiceInterface

**Tipo:** Interface  
**Arquivo:** `app/services/Contracts/CheckoutServiceInterface.php`  
**Namespace:** `Akti\Services\Contracts`  

Interface CheckoutServiceInterface.

### Métodos

#### Métodos Public

##### `generateToken(array $params): array`

Gera conteúdo ou dados.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$params` | `array` | Parâmetros adicionais |

**Retorno:** `array — */`

---

##### `processCheckout(string $token, array $paymentData): array`

Processa uma operação específica.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$token` | `string` | Token de autenticação/verificação |
| `$paymentData` | `array` | Payment data |

**Retorno:** `array — */`

---

##### `cancelToken(int $tokenId): bool`

Cancela operação.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$tokenId` | `int` | Token id |

**Retorno:** `bool — */`

---

##### `markInstallmentPaidFromCheckout(int $orderId,
        ?int $installmentId,
        float $amount,
        string $paymentMethod,
        ?string $externalId = null): void`

Mark installment paid from checkout.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$orderId` | `int` | ID do pedido |
| `$installmentId` | `int|null` | Installment id |
| `$amount` | `float` | Valor monetário |
| `$paymentMethod` | `string` | Payment method |
| `$externalId` | `string|null` | External id |

**Retorno:** `void — */`

---

##### `expireOldTokens(): int`

Expire old tokens.

**Retorno:** `int — */`

---

##### `getTokenByToken(string $token): ?array`

Obtém dados específicos.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$token` | `string` | Token de autenticação/verificação |

**Retorno:** `array|null — */`

---

## EmailServiceInterface

**Tipo:** Interface  
**Arquivo:** `app/services/Contracts/EmailServiceInterface.php`  
**Namespace:** `Akti\Services\Contracts`  

Interface EmailServiceInterface.

### Métodos

#### Métodos Public

##### `send(string $toEmail, string $toName, string $subject, string $bodyHtml): array`

Envia dados ou notificação.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$toEmail` | `string` | To email |
| `$toName` | `string` | To name |
| `$subject` | `string` | Assunto |
| `$bodyHtml` | `string` | Body html |

**Retorno:** `array — */`

---

##### `sendCampaign(int $campaignId): array`

Envia dados ou notificação.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$campaignId` | `int` | Campaign id |

**Retorno:** `array — */`

---

##### `sendTest(int $campaignId, string $testEmail): array`

Envia dados ou notificação.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$campaignId` | `int` | Campaign id |
| `$testEmail` | `string` | Test email |

**Retorno:** `array — */`

---

## NfeServiceInterface

**Tipo:** Interface  
**Arquivo:** `app/services/Contracts/NfeServiceInterface.php`  
**Namespace:** `Akti\Services\Contracts`  

Interface NfeServiceInterface.

### Métodos

#### Métodos Public

##### `isLibraryAvailable(): bool`

Verifica uma condição booleana.

**Retorno:** `bool — */`

---

##### `testConnection(): array`

Test connection.

**Retorno:** `array — */`

---

##### `emit(int $orderId, array $orderData): array`

Emite evento ou sinal.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$orderId` | `int` | ID do pedido |
| `$orderData` | `array` | Order data |

**Retorno:** `array — */`

---

##### `cancel(int $nfeId, string $motivo): array`

Cancela operação.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$nfeId` | `int` | Nfe id |
| `$motivo` | `string` | Motivo |

**Retorno:** `array — */`

---

##### `correction(int $nfeId, string $texto): array`

Correction.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$nfeId` | `int` | Nfe id |
| `$texto` | `string` | Texto |

**Retorno:** `array — */`

---

##### `checkStatus(int $nfeId): array`

Verifica condição ou estado.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$nfeId` | `int` | Nfe id |

**Retorno:** `array — */`

---

##### `getCredentials(): array`

Obtém dados específicos.

**Retorno:** `array — */`

---

##### `inutilizar(int $numInicial, int $numFinal, string $justificativa, int $modelo = 55, int $serie = 1): array`

Inutilizar.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$numInicial` | `int` | Num inicial |
| `$numFinal` | `int` | Num final |
| `$justificativa` | `string` | Justificativa |
| `$modelo` | `int` | Modelo |
| `$serie` | `int` | Serie |

**Retorno:** `array — */`

---

## PipelinePaymentServiceInterface

**Tipo:** Interface  
**Arquivo:** `app/services/Contracts/PipelinePaymentServiceInterface.php`  
**Namespace:** `Akti\Services\Contracts`  

Interface PipelinePaymentServiceInterface.

### Métodos

#### Métodos Public

##### `generatePaymentLink(int $orderId, string $gatewaySlug = '', string $method = 'auto'): array`

Gera conteúdo ou dados.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$orderId` | `int` | ID do pedido |
| `$gatewaySlug` | `string` | Gateway slug |
| `$method` | `string` | Method |

**Retorno:** `array — */`

---

##### `generateCheckoutLink(int $orderId,
        ?int $installmentId = null,
        string $gatewaySlug = '',
        array $allowedMethods = []): array`

Gera conteúdo ou dados.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$orderId` | `int` | ID do pedido |
| `$installmentId` | `int|null` | Installment id |
| `$gatewaySlug` | `string` | Gateway slug |
| `$allowedMethods` | `array` | Allowed methods |

**Retorno:** `array — */`

---

## CustomerContactService

**Tipo:** Class  
**Arquivo:** `app/services/CustomerContactService.php`  
**Namespace:** `Akti\Services`  

Service: CustomerContactService

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |
| private | `$contactModel` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db, CustomerContact $contactModel)`

Construtor da classe CustomerContactService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |
| `$contactModel` | `CustomerContact` | Contact model |

---

##### `listByCustomer(int $customerId): array`

Lista contatos de um cliente.

---

##### `save(array $data): array`

Cria ou atualiza um contato.

**Retorno:** `array — ['success' => bool, 'message' => string, 'id' => int|null]`

---

##### `delete(int $contactId): array`

Remove um contato.

**Retorno:** `array — ['success' => bool, 'message' => string]`

---

## CustomerExportService

**Tipo:** Class  
**Arquivo:** `app/services/CustomerExportService.php`  
**Namespace:** `Akti\Services`  

CustomerExportService — Lógica de exportação de clientes.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$customerModel` | Não |
| private | `$logger` | Não |

### Métodos

#### Métodos Public

##### `__construct(Customer $customerModel, Logger $logger)`

Construtor da classe CustomerExportService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$customerModel` | `Customer` | Customer model |
| `$logger` | `Logger` | Logger |

---

##### `exportCsv(array $filters, ?array $ids = null): void`

Exporta clientes em formato CSV.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$filters` | `array` | Filtros de busca |
| `$ids` | `int[]|null` | IDs específicos (se seleção) |

---

## CustomerFormService

**Tipo:** Class  
**Arquivo:** `app/services/CustomerFormService.php`  
**Namespace:** `Akti\Services`  

Service: CustomerFormService

### Métodos

#### Métodos Public

##### `captureFormData(): array`

Captura e sanitiza todos os campos do formulário de cliente.

**Retorno:** `array — Dados sanitizados`

---

##### `validateCustomerData(array $data, ?int $excludeId = null): Validator`

Validação server-side completa dos dados do cliente.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$data` | `array` | Dados sanitizados |
| `$excludeId` | `int|null` | ID a excluir na validação de unicidade (edição) |

**Retorno:** `Validator — */`

---

##### `buildAddressJson(array $data): string`

Monta o JSON de endereço para retrocompatibilidade.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$data` | `array` | Dados do formulário |

**Retorno:** `string — JSON do endereço`

---

## CustomerImportService

**Tipo:** Class  
**Arquivo:** `app/services/CustomerImportService.php`  
**Namespace:** `Akti\Services`  

CustomerImportService — Lógica de importação de clientes extraída do CustomerController.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$customerModel` | Não |
| private | `$importBatchModel` | Não |
| private | `$logger` | Não |
| private | `$db` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db, Customer $customerModel, ImportBatch $importBatchModel, Logger $logger)`

Construtor da classe CustomerImportService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |
| `$customerModel` | `Customer` | Customer model |
| `$importBatchModel` | `ImportBatch` | Import batch model |
| `$logger` | `Logger` | Logger |

---

##### `parseFile(array $file): array`

Faz parse do arquivo enviado e retorna colunas + preview + auto-mapeamento.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$file` | `array` | $_FILES['import_file'] |

**Retorno:** `array{success: — bool, columns?: array, preview?: array, total_rows?: int, auto_mapping?: array, message?: string}`

---

##### `executeImport(array $mapping, string $importMode, int $userId, int $tenantId): array`

Executa a importação com o mapeamento fornecido.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$mapping` | `array` | Mapeamento [coluna_arquivo => campo_sistema] |
| `$importMode` | `string` | create | update | create_or_update |
| `$userId` | `int` | * @param int    $tenantId |

**Retorno:** `array — Resultado da importação`

---

### Funções auxiliares do arquivo

#### `readFileRows(string $filePath, string $ext)`

---

#### `normalizeRow(array &$mapped, int $lineDisplay, array &$warnings, string $importMode)`

---

#### `persistRow(array $mapped, array $originalRow, string $mode, int $batchId, int $line, array &$errors, array &$warnings)`

---

#### `buildAutoMapping(array $columns)`

---

#### `parseCsvFile(string $filePath)`

---

#### `(mb_strtolower($h))`

---

#### `parseExcelFile(string $filePath)`

---

#### `(mb_strtolower($h ?? ''))`

---

#### `normalizeDateForImport(string $dateStr)`

---

#### `normalizeUfForImport(string $state)`

---

#### `generateTemplate()`

---

## CustomerOrderHistoryService

**Tipo:** Class  
**Arquivo:** `app/services/CustomerOrderHistoryService.php`  
**Namespace:** `Akti\Services`  

Service: CustomerOrderHistoryService

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe CustomerOrderHistoryService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `getRecentOrders(int $customerId, int $limit = 5): array`

Busca pedidos recentes de um cliente.

---

##### `getOrderHistoryPaginated(int $customerId, int $page = 1, int $perPage = 10): array`

Busca pedidos paginados de um cliente com formatação.

---

## DemandPredictionService

**Tipo:** Class  
**Arquivo:** `app/services/DemandPredictionService.php`  
**Namespace:** `Akti\Services`  

DemandPredictionService — Previsão de demanda com base em dados históricos.

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor da classe DemandPredictionService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |

---

##### `predictDemand(int $productId, int $days = 30): array`

Prever demanda de um produto para os próximos N dias.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$productId` | `int` | ID do produto |
| `$days` | `int` | Número de dias para previsão |

**Retorno:** `array — ['forecast' => [...], 'confidence' => float, 'trend' => string]`

---

### Funções auxiliares do arquivo

#### `suggestRestock(int $productId, int $currentStock, int $leadTimeDays = 7)`

---

#### `topDemandProducts(int $limit = 10)`

---

#### `getOrderHistory(int $productId, int $days)`

---

#### `movingAverage(array $data, int $window)`

---

#### `linearTrend(array $data)`

---

## EmailService

**Tipo:** Class  
**Arquivo:** `app/services/EmailService.php`  
**Namespace:** `Akti\Services`  
**Implementa:** `EmailServiceInterface`  

Class EmailService.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |
| private | `$config` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe EmailService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `send(string $toEmail, string $toName, string $subject, string $bodyHtml): array`

Send a single email

---

#### Métodos Private

##### `diagnoseSmtpError(string $rawError): string`

Diagnose SMTP errors and return user-friendly messages

---

### Funções auxiliares do arquivo

#### `sendCampaign(int $campaignId)`

---

#### `sendTest(int $campaignId, string $testEmail)`

---

#### `replaceVariables(string $content, array $recipient, int $tenantId)`

---

#### `getTenantCompanyName(int $tenantId)`

---

#### `getCampaign(int $campaignId)`

---

#### `getRecipients(array $campaign)`

---

#### `($c)`

---

#### `updateCampaignStatus(int $campaignId, string $status, int $totalRecipients = 0)`

---

#### `finalizeCampaign(int $campaignId, string $status, int $sentCount)`

---

#### `createLog(int $campaignId, int $tenantId, array $recipient, string $status, ?string $error)`

---

#### `updateLogStatus(int $logId, string $status, ?string $error)`

---

#### `injectTracking(string $html, int $logId)`

---

#### `($logId, $hash, $baseUrl)`

---

#### `getBaseUrl()`

---

#### `createMailer()`

---

## ExternalApiService

**Tipo:** Class  
**Arquivo:** `app/services/ExternalApiService.php`  
**Namespace:** `Akti\Services`  

Service: ExternalApiService

### Métodos

#### Métodos Public

##### `searchCep(string $cep): array`

Consulta endereço por CEP via ViaCEP.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$cep` | `string` | CEP (apenas dígitos) |

**Retorno:** `array — ['success' => bool, 'data' => array|null, 'message' => string|null, 'cached' => bool]`

---

### Funções auxiliares do arquivo

#### `searchCnpj(string $cnpj)`

---

#### `httpGet(string $url, int $timeout = 10)`

---

## FileManager

**Tipo:** Class  
**Arquivo:** `app/services/FileManager.php`  
**Namespace:** `Akti\Services`  

FileManager — Serviço centralizado de gestão de arquivos.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$basePath` | Não |
| private | `$disk` | Não |

### Métodos

#### Métodos Public

##### `__construct(?\PDO $db = null)`

Construtor da classe FileManager.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO|null` | Conexão PDO com o banco de dados |

---

##### `upload(array $file, string $module, array $options = []): array`

Upload de um único arquivo.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$file` | `array` | Entrada de $_FILES (single file) |
| `$module` | `string` | Nome do módulo (products, customers, etc.) |
| `$options` | `array` | Opções adicionais: |

**Retorno:** `array — ['success' => bool, 'path' => string|null, 'asset_id' => int|null, 'error' => string|null]`

---

### Funções auxiliares do arquivo

#### `uploadMultiple(array $files, string $module, array $options = [])`

---

#### `delete(string $path)`

---

#### `getUrl(?string $path, ?string $size = null)`

---

#### `thumbUrl(?string $path, int $width, ?int $height = null)`

---

#### `download(string $path, ?string $filename = null)`

---

#### `serve(string $path)`

---

#### `isImage(?string $path)`

---

#### `exists(?string $path)`

---

#### `detectMimeType(string $path)`

---

#### `getModuleProfile(string $module)`

---

#### `replace(?string $oldPath, array $file, string $module, array $options = [])`

---

#### `generateFilename(string $prefix, string $ext)`

---

#### `resolveFullPath(string $path)`

---

#### `trackAsset(array $data)`

---

#### `softDeleteAsset(string $path)`

---

#### `parseSizePreset(string $size)`

---

#### `getUploadErrorMessage(int $code)`

---

## FinancialAuditService

**Tipo:** Class  
**Arquivo:** `app/services/FinancialAuditService.php`  
**Namespace:** `Akti\Services`  

FinancialAuditService — Serviço de auditoria para o módulo financeiro.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe FinancialAuditService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `log(string $entityType,
        int $entityId,
        string $action,
        array $newValues = [],
        array $oldValues = [],
        ?int $userId = null,
        ?string $reason = null): bool`

Registra uma entrada de auditoria.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$entityType` | `string` | Tipo da entidade: 'installment', 'transaction', 'order' |
| `$entityId` | `int` | ID da entidade |
| `$action` | `string` | Ação executada (ex: 'created', 'paid', 'confirmed', 'cancelled', 'updated', 'deleted') |
| `$newValues` | `array` | Valores novos / dados do evento |
| `$oldValues` | `array` | Valores anteriores (opcional) |
| `$userId` | `int|null` | ID do usuário (usa session se null) |
| `$reason` | `string|null` | Motivo informado pelo usuário (obrigatório em exclusões) |

**Retorno:** `bool — */`

---

##### `logInstallment(int $id, string $action, array $data = [], array $oldData = [], ?int $userId = null, ?string $reason = null): bool`

Log de ação em parcela (installment).

---

##### `logTransaction(int $id, string $action, array $data = [], array $oldData = [], ?int $userId = null, ?string $reason = null): bool`

Log de ação em transação financeira.

---

##### `logOrder(int $id, string $action, array $data = [], array $oldData = [], ?int $userId = null, ?string $reason = null): bool`

Log de ação em pedido (financial context).

---

##### `getHistory(string $entityType, int $entityId, int $limit = 50): array`

Retorna histórico de auditoria de uma entidade.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$entityType` | `string` | * @param int    $entityId |
| `$limit` | `int` | * @return array |

**Retorno:** `array — */`

---

##### `getRecent(int $limit = 100, ?string $entityType = null): array`

Retorna últimas ações de auditoria do módulo financeiro.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$limit` | `int` | * @param string|null $entityType Filtrar por tipo |

**Retorno:** `array — */`

---

##### `getPaginated(array $filters = [], int $page = 1, int $perPage = 25): array`

Retorna registros de auditoria com paginação e filtros para o relatório.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$filters` | `array` | Filtros: entity_type, action, user_id, date_from, date_to, search |
| `$page` | `int` | * @param int   $perPage |

**Retorno:** `array — ['data' => [...], 'total' => int, 'page' => int, 'perPage' => int, 'totalPages' => int]`

---

### Funções auxiliares do arquivo

#### `exportCsv(array $filters = [])`

---

#### `hasReasonColumn()`

---

#### `ensureTableExists()`

---

## FinancialImportService

**Tipo:** Class  
**Arquivo:** `app/services/FinancialImportService.php`  
**Namespace:** `Akti\Services`  

FinancialImportService — Camada de Serviço para Importação Financeira (OFX/CSV/Excel).

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$financial` | Não |
| private | `$db` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db, Financial $financial)`

Construtor da classe FinancialImportService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |
| `$financial` | `Financial` | Financial |

---

##### `parseOfx(string $content): array`

Parse de arquivo OFX/OFC — retorna transações para preview.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$content` | `string` | Conteúdo do arquivo |

**Retorno:** `array — ['success' => bool, 'file_type' => string, 'rows' => array, ...]`

---

##### `parseCsv(string $filePath): array`

Parse de arquivo CSV/TXT — retorna dados estruturados.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$filePath` | `string` | Caminho do arquivo |

**Retorno:** `array — */`

---

##### `parseExcel(string $filePath): array`

Parse de arquivo Excel (XLS/XLSX) via PhpSpreadsheet.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$filePath` | `string` | * @return array |

**Retorno:** `array — */`

---

##### `saveImportTmpFile(string $tmpName, string $ext): void`

Salva o arquivo de importação em diretório temporário para reutilizar.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$tmpName` | `string` | Caminho do arquivo temporário original |
| `$ext` | `string` | Extensão do arquivo |

---

##### `importOfxSelected(array $selectedIndexes, string $mode, ?int $userId): array`

Importa transações OFX de linhas selecionadas.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$selectedIndexes` | `array` | Índices das transações selecionadas |
| `$mode` | `string` | 'registro' ou 'contabilizar' |
| `$userId` | `int|null` | * @return array Resultado da importação |

**Retorno:** `array — Resultado da importação`

---

##### `importCsvMapped(array $rows, array $mapping, array $selectedRows, string $mode, ?int $userId): array`

Importa transações CSV/Excel mapeado.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$rows` | `array` | Linhas do arquivo |
| `$mapping` | `array` | Mapeamento de colunas |
| `$selectedRows` | `array` | Índices selecionados |
| `$mode` | `string` | 'registro' ou 'contabilizar' |
| `$userId` | `int|null` | * @return array Resultado |

**Retorno:** `array — Resultado`

---

##### `importOfxDirect(string $filePath, string $mode, ?int $userId): array`

Importa OFX diretamente (sem seleção de linhas — modo legacy).

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$filePath` | `string` | * @param string $mode |
| `$userId` | `int|null` | * @return array |

**Retorno:** `array — */`

---

##### `static getFinancialImportFields(): array`

Campos disponíveis para mapeamento de importação financeira.

**Retorno:** `array — */`

---

#### Métodos Private

##### `buildCsvParseResponse(array $headers, array $rows): array`

Monta resposta estruturada do parse CSV/Excel.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$headers` | `array` | * @param array $rows |

**Retorno:** `array — */`

---

##### `hasOfxDuplicityTable(): bool`

Verifica se a tabela ofx_imported_transactions existe.

---

##### `isOfxTransactionImported(string $fitid, ?string $bankAccount): bool`

Verifica se uma transação OFX já foi importada (por FITID + conta bancária).

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$fitid` | `string` | * @param string|null $bankAccount |

**Retorno:** `bool — */`

---

##### `registerOfxTransaction(string $fitid, ?string $bankAccount, string $date, float $amount, string $description, ?int $transactionId): void`

Registra transação OFX importada na tabela de controle de duplicidade.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$fitid` | `string` | * @param string|null $bankAccount |
| `$date` | `string` | * @param float $amount |
| `$description` | `string` | * @param int|null $transactionId |

---

##### `extractBankAccount(string $content): ?string`

Extrai número da conta bancária do conteúdo OFX.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$content` | `string` | * @return string|null |

**Retorno:** `string|null — */`

---

## FinancialReportService

**Tipo:** Class  
**Arquivo:** `app/services/FinancialReportService.php`  
**Namespace:** `Akti\Services`  

FinancialReportService — Camada de Serviço para Relatórios Financeiros.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$financial` | Não |
| private | `$installment` | Não |
| private | `$db` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db, Financial $financial, Installment $installment)`

Construtor da classe FinancialReportService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |
| `$financial` | `Financial` | Financial |
| `$installment` | `Installment` | Installment |

---

##### `getSummary(int $month, int $year): array`

Retorna resumo geral do financeiro (dashboard).

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$month` | `int` | * @param int $year |

**Retorno:** `array — */`

---

##### `getChartData(int $months = 6): array`

Retorna dados para gráfico de receita x despesa.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$months` | `int` | * @return array |

**Retorno:** `array — */`

---

##### `getPendingConfirmations(): array`

Retorna parcelas pendentes de confirmação.

**Retorno:** `array — */`

---

##### `getOverdueInstallments(): array`

Retorna parcelas vencidas.

**Retorno:** `array — */`

---

##### `getUpcomingInstallments(int $days = 7): array`

Retorna próximas parcelas a vencer.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$days` | `int` | * @return array |

**Retorno:** `array — */`

---

##### `getOrdersPendingPayment(): array`

Retorna pedidos com pagamento pendente.

**Retorno:** `array — */`

---

##### `getDre(string $fromMonth, string $toMonth): array`

Gera DRE simplificado para um período.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$fromMonth` | `string` | YYYY-MM |
| `$toMonth` | `string` | YYYY-MM |

**Retorno:** `array — */`

---

##### `getCashflowProjection(int $months = 6, bool $includeRecurring = true): array`

Gera fluxo de caixa projetado para os próximos N meses.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$months` | `int` | Horizonte de projeção (3, 6 ou 12) |
| `$includeRecurring` | `bool` | Incluir projeção de recorrências |

**Retorno:** `array — */`

---

### Funções auxiliares do arquivo

#### `exportTransactionsCsv(array $filters = [])`

---

#### `exportDreCsv(string $fromMonth, string $toMonth)`

---

#### `exportCashflowCsv(int $months = 6, bool $includeRecurring = true)`

---

#### `formatMonthLabel(string $yearMonth)`

---

## HeaderDataService

**Tipo:** Class  
**Arquivo:** `app/services/HeaderDataService.php`  
**Namespace:** `Akti\Services`  

HeaderDataService — Centraliza todas as queries que alimentam o header/layout.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$conn` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $conn)`

Construtor da classe HeaderDataService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$conn` | `PDO` | Conn |

---

##### `getAllHeaderData(?int $userId, ?int $groupId, bool $isAdmin): array`

Retorna todos os dados necessários para o header em uma única chamada.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$userId` | `int|null` | * @param int|null $groupId |
| `$isAdmin` | `bool` | * @return array{ |

**Retorno:** `array{ — *     delayedCount: int,`

---

##### `getUserMenuPermissions(int $groupId): array`

Retorna as permissões de menu para o grupo do usuário.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$groupId` | `int` | * @return string[] |

**Retorno:** `string[] — */`

---

##### `getDelayedOrders(): array`

Retorna a contagem e lista de pedidos atrasados no pipeline.

**Retorno:** `array{count: — int, orders: array}`

---

##### `getDelayedProducts(): array`

Retorna os produtos atrasados nos setores de produção.

**Retorno:** `array — */`

---

##### `static invalidateCache(): void`

Invalida o cache do header (chamar após operações que alteram dados do pipeline).

---

## InstallmentService

**Tipo:** Class  
**Arquivo:** `app/services/InstallmentService.php`  
**Namespace:** `Akti\Services`  

InstallmentService — Camada de Serviço para Parcelas.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$installment` | Não |
| private | `$transactionService` | Não |
| private | `$db` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db, Installment $installment, TransactionService $transactionService)`

Construtor da classe InstallmentService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |
| `$installment` | `Installment` | Installment |
| `$transactionService` | `TransactionService` | Transaction service |

---

##### `getModel(): Installment`

Retorna a instância do model Installment.

**Retorno:** `Installment — */`

---

##### `updateOverdue(): void`

Atualiza parcelas vencidas.

---

##### `generateForOrder(int $orderId, int $numInstallments, float $downPayment, string $startDate): bool`

Gera parcelas para um pedido e atualiza os campos financeiros.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$orderId` | `int` | * @param int $numInstallments |
| `$downPayment` | `float` | * @param string $startDate |

**Retorno:** `bool — */`

---

##### `payInstallment(int $installmentId, array $paymentData, bool $createRemaining = false, ?string $remainingDueDate = null): array`

Registra pagamento de parcela (fluxo completo: pagar + transação + parcela restante).

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$installmentId` | `int` | * @param array $paymentData  Keys: paid_date, paid_amount, payment_method, notes, user_id, attachment_path |
| `$createRemaining` | `bool` | * @param string|null $remainingDueDate |

**Retorno:** `array — ['success' => bool, 'auto_confirmed' => bool, 'remaining_created' => bool, 'new_installment_id' => int|null, 'remaining_amount' => float]`

---

##### `confirmPayment(int $installmentId, ?int $userId = null): bool`

Confirma pagamento de parcela.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$installmentId` | `int` | * @param int|null $userId |

**Retorno:** `bool — */`

---

##### `cancelInstallment(int $installmentId, ?int $userId = null): bool`

Cancela/estorna parcela (e registra estorno na tabela de transações).

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$installmentId` | `int` | * @param int|null $userId |

**Retorno:** `bool — */`

---

##### `mergeInstallments(array $ids, string $dueDate)`

Merge de parcelas pendentes.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$ids` | `array` | * @param string $dueDate |

**Retorno:** `int|false — */`

---

##### `splitInstallment(int $installmentId, int $parts, ?string $firstDueDate = null): array`

Split de parcela.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$installmentId` | `int` | * @param int $parts |
| `$firstDueDate` | `string|null` | * @return array |

**Retorno:** `array — */`

---

## MarketplaceConnector

**Tipo:** Class  
**Arquivo:** `app/services/MarketplaceConnector.php`  
**Namespace:** `Akti\Services`  

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| protected | `$tenantId` | Não |
| protected | `$config` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db, int $tenantId, array $config = [])`

Construtor da classe MarketplaceConnector.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |
| `$tenantId` | `int` | ID do tenant |
| `$config` | `array` | Configurações |

---

##### `authenticate(): bool`

Autenticar com a API do marketplace (OAuth2 ou API key).

---

##### `syncProducts(array $productIds = []): array`

Sincronizar produtos: enviar catálogo local → marketplace.

---

##### `importOrders(string $since = ''): array`

Importar pedidos do marketplace → sistema local.

---

##### `updateOrderStatus(int $orderId, string $status): bool`

Atualizar status de um pedido no marketplace.

---

##### `syncStock(array $productIds = []): array`

Sincronizar estoque local → marketplace.

---

##### `getName(): string`

Retorna o nome do conector.

---

#### Métodos Protected

##### `log(string $action, string $message, string $level = 'info'): void`

Log de operação do marketplace.

---

## MercadoLivreConnector

**Tipo:** Class  
**Arquivo:** `app/services/MercadoLivreConnector.php`  
**Namespace:** `Akti\Services`  
**Herda de:** `MarketplaceConnector`  

MercadoLivreConnector — Conector para Mercado Livre.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$baseUrl` | Não |

### Métodos

#### Métodos Public

##### `getName(): string`

Obtém dados específicos.

**Retorno:** `string — */`

---

##### `authenticate(): bool`

Autentica o usuário com credenciais.

**Retorno:** `bool — */`

---

##### `syncProducts(array $productIds = []): array`

Sincroniza dados.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$productIds` | `array` | Product ids |

**Retorno:** `array — */`

---

##### `importOrders(string $since = ''): array`

Importa dados.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$since` | `string` | Since |

**Retorno:** `array — */`

---

##### `updateOrderStatus(int $orderId, string $status): bool`

Update order status.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$orderId` | `int` | ID do pedido |
| `$status` | `string` | Status do registro |

**Retorno:** `bool — */`

---

### Funções auxiliares do arquivo

#### `syncStock(array $productIds = [])`

---

#### `httpPost(string $endpoint, array $data)`

---

#### `httpGet(string $endpoint, array $params = [])`

---

## NfceDanfeGenerator

**Tipo:** Class  
**Arquivo:** `app/services/NfceDanfeGenerator.php`  
**Namespace:** `Akti\Services`  

NfceDanfeGenerator — Gera DANFE para NFC-e (modelo 65) em formato de cupom térmico.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |

### Métodos

#### Métodos Public

##### `__construct($db = null)`

Construtor da classe NfceDanfeGenerator.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `mixed` | Conexão PDO com o banco de dados |

---

##### `generate(string $xmlAutorizado, array $options = []): ?string`

Gera DANFE NFC-e a partir do XML autorizado.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$xmlAutorizado` | `string` | XML autorizado da NFC-e |
| `$options` | `array` | Opções: 'format' => 'pdf'|'html', 'width' => int (mm) |

**Retorno:** `string|null — Conteúdo do DANFE (PDF binário ou HTML) ou null se erro`

---

#### Métodos Private

##### `generateWithSpedDa(string $xml, int $width): string`

Gera DANFE NFC-e via biblioteca sped-da.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$xml` | `string` | XML autorizado |
| `$width` | `int` | Largura em mm |

**Retorno:** `string — PDF binário`

---

##### `generateHtml(string $xml, int $width): string`

Gera DANFE NFC-e em HTML para impressão direta ou conversão.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$xml` | `string` | XML autorizado |
| `$width` | `int` | Largura em mm |

**Retorno:** `string — HTML do cupom`

---

### Funções auxiliares do arquivo

#### `parseXml(string $xml)`

---

#### `getTagValue(\DOMElement $parent, string $tag, string $ns)`

---

#### `formatCnpj(string $cnpj)`

---

#### `formatDateTime(string $datetime)`

---

#### `getPaymentLabel(string $tPag)`

---

#### `getLogoPath()`

---

## NfceXmlBuilder

**Tipo:** Class  
**Arquivo:** `app/services/NfceXmlBuilder.php`  
**Namespace:** `Akti\Services`  

NfceXmlBuilder — Monta XML da NFC-e (modelo 65) no formato 4.00.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$emitente` | Não |
| private | `$orderData` | Não |
| private | `$numero` | Não |
| private | `$serie` | Não |
| private | `$taxCalc` | Não |
| private | `$calculatedItems` | Não |
| private | `$calculatedTotals` | Não |
| private | `$qrcodeUrl` | Não |

### Métodos

#### Métodos Public

##### `__construct(array $emitente, array $orderData, int $numero, int $serie)`

Construtor da classe NfceXmlBuilder.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$emitente` | `array` | Emitente |
| `$orderData` | `array` | Order data |
| `$numero` | `int` | Numero |
| `$serie` | `int` | Serie |

---

##### `getCalculatedItems(): array`

Retorna os dados fiscais calculados de cada item (após build()).

**Retorno:** `array — */`

---

##### `getCalculatedTotals(): array`

Retorna os totais fiscais calculados (após build()).

**Retorno:** `array — */`

---

##### `getQrCodeUrl(): string`

Retorna URL do QR Code gerado (após build()).

**Retorno:** `string — */`

---

##### `build(): string`

Monta e retorna o XML da NFC-e (não assinado).

**Retorno:** `string — XML`

---

##### `static generateQrCode(string $chave, int $tpAmb, string $cscId, string $cscToken): string`

Gera URL do QR Code da NFC-e.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$chave` | `string` | Chave de acesso (44 dígitos) |
| `$tpAmb` | `int` | Ambiente: 1=Produção, 2=Homologação |
| `$cscId` | `string` | ID do CSC |
| `$cscToken` | `string` | Token do CSC |

**Retorno:** `string — URL do QR Code`

---

#### Métodos Private

##### `mapPaymentMethod(string $method): string`

Mapeia forma de pagamento.

---

##### `getCodeUF(string $uf): int`

Retorna código UF para SEFAZ.

---

## NfeAuditService

**Tipo:** Class  
**Arquivo:** `app/services/NfeAuditService.php`  
**Namespace:** `Akti\Services`  

NfeAuditService — Registra trilha de auditoria para o módulo NF-e.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |
| private | `$model` | Não |
| private | `$enabled` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe NfeAuditService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `record(string $action,
        string $entityType,
        ?int $entityId = null,
        string $description = '',
        array $extraData = []): ?int`

Registra uma ação de auditoria.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$action` | `string` | Tipo da ação (view, emit, cancel, etc.) |
| `$entityType` | `string` | Tipo da entidade (nfe_document, nfe_credential, etc.) |
| `$entityId` | `int|null` | ID da entidade |
| `$description` | `string` | Descrição legível |
| `$extraData` | `array` | Dados adicionais (JSON) |

**Retorno:** `int|null — ID do registro ou null se desabilitado`

---

##### `logView(int $nfeId, string $description = ''): void`

Atalhos de registro.

---

### Funções auxiliares do arquivo

#### `logEmit(int $nfeId, int $orderId, string $chave = '')`

---

#### `logCancel(int $nfeId, string $motivo = '')`

---

#### `logCorrection(int $nfeId, int $seq, string $texto)`

---

#### `logDownloadXml(int $nfeId)`

---

#### `logDownloadDanfe(int $nfeId)`

---

#### `logCredentialsUpdate(array $fields = [])`

---

#### `logCredentialsView()`

---

#### `logCertificateUpload(string $ext = 'pfx')`

---

#### `logManifestation(int $docId, string $tipo, string $chave)`

---

#### `logDistDFe(int $totalDocs)`

---

#### `logBatchEmit(int $count, string $batchId)`

---

#### `logInutilizar(int $numInicial, int $numFinal, string $justificativa)`

---

#### `getModel()`

---

## NfeBackupManagementService

**Tipo:** Class  
**Arquivo:** `app/services/NfeBackupManagementService.php`  
**Namespace:** `Akti\Services`  

Service: NfeBackupManagementService

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe NfeBackupManagementService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `executeBackup(string $startDate, string $endDate, string $tipo): array`

Executa backup de XMLs no período/tipo informado.

---

##### `getHistory(int $limit = 0): array`

Retorna histórico de backups.

---

##### `loadConfig(): array`

Carrega configurações de backup do banco.

---

##### `saveConfig(array $configs): void`

Salva configurações de backup.

---

## NfeBackupService

**Tipo:** Class  
**Arquivo:** `app/services/NfeBackupService.php`  
**Namespace:** `Akti\Services`  

NfeBackupService — Realiza backup de XMLs de NF-e para storage externo.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |
| private | `$storage` | Não |
| private | `$basePath` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe NfeBackupService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `execute(string $startDate, string $endDate, string $tipo = 'local'): array`

Executa backup de XMLs para o período especificado.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$startDate` | `string` | Data inicial (Y-m-d) |
| `$endDate` | `string` | Data final (Y-m-d) |
| `$tipo` | `string` | Tipo: 'local', 's3', 'ftp' |

**Retorno:** `array — ['success' => bool, 'message' => string, 'backup_id' => int|null, 'file' => string|null]`

---

## 

**Tipo:** Class  
**Arquivo:** `app/services/NfeBackupService.php`  
**Namespace:** `Akti\Services`  

### Funções auxiliares do arquivo

#### `collectXmlFiles(string $start, string $end)`

---

#### `createZip(array $files, string $start, string $end)`

---

#### `uploadToS3(string $zipPath)`

---

#### `uploadToFtp(string $zipPath)`

---

#### `logStart(string $tipo, string $start, string $end)`

---

#### `logFinish(int $id, string $status, int $totalFiles, int $size, ?string $destino, ?string $erro = null)`

---

#### `getHistory(int $limit = 50)`

---

#### `loadConfig()`

---

#### `formatSize(int $bytes)`

---

## NfeBatchDownloadService

**Tipo:** Class  
**Arquivo:** `app/services/NfeBatchDownloadService.php`  
**Namespace:** `Akti\Services`  

Service: NfeBatchDownloadService

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe NfeBatchDownloadService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `fetchByIds(array $ids): array`

Busca documentos NF-e por IDs específicos.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$ids` | `int[]` | * @return array |

**Retorno:** `array — */`

---

### Funções auxiliares do arquivo

#### `fetchByPeriod(string $startDate, string $endDate)`

---

#### `buildZip(array $docs)`

---

#### `sendZip(string $tmpZip, string $zipFilename)`

---

## NfeCancellationService

**Tipo:** Class  
**Arquivo:** `app/services/NfeCancellationService.php`  
**Namespace:** `Akti\Services`  

NfeCancellationService — Cancelamento de NF-e.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$sefazClient` | Não |
| private | `$docModel` | Não |
| private | `$logModel` | Não |

### Métodos

#### Métodos Public

##### `__construct(NfeSefazClient $sefazClient, NfeDocument $docModel, NfeLog $logModel)`

Construtor da classe NfeCancellationService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$sefazClient` | `NfeSefazClient` | Sefaz client |
| `$docModel` | `NfeDocument` | Doc model |
| `$logModel` | `NfeLog` | Log model |

---

##### `cancel(int $nfeId, string $motivo): array`

Cancela uma NF-e autorizada.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$nfeId` | `int` | ID do registro nfe_documents |
| `$motivo` | `string` | Justificativa (mín 15 caracteres) |

**Retorno:** `array — ['success' => bool, 'message' => string]`

---

## NfeContingencyService

**Tipo:** Class  
**Arquivo:** `app/services/NfeContingencyService.php`  
**Namespace:** `Akti\Services`  

NfeContingencyService — Gerencia ativação/desativação de contingência NF-e.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |
| private | `$credModel` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe NfeContingencyService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `getStatus(?int $credentialId = null): array`

Verifica se o sistema está em contingência.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$credentialId` | `int|null` | ID da credencial (null = ativa) |

**Retorno:** `array — ['active' => bool, 'type' => int, 'since' => string|null, 'justification' => string|null]`

---

##### `activate(string $justificativa, ?int $tpEmis = null, ?int $credentialId = null): array`

Ativa contingência manualmente.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$justificativa` | `string` | Justificativa (min 15 caracteres) |
| `$tpEmis` | `int|null` | Tipo de emissão (6=SVC-AN, 7=SVC-RS, 9=Offline NFC-e) |
| `$credentialId` | `int|null` | ID da credencial |

**Retorno:** `array — ['success' => bool, 'message' => string]`

---

##### `deactivate(?int $credentialId = null): array`

Desativa contingência e inicia sincronização.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$credentialId` | `int|null` | ID da credencial |

**Retorno:** `array — ['success' => bool, 'message' => string, 'pending' => int]`

---

### Funções auxiliares do arquivo

#### `autoDetect()`

---

#### `syncPending(int $limit = 10)`

---

#### `countPendingSync()`

---

#### `logContingency(string $tipo,
        ?int $tpEmisAnterior,
        ?int $tpEmisNovo,
        ?string $justificativa,
        int $nfesPendentes = 0,
        int $nfesSincronizadas = 0)`

---

#### `getTpEmisLabel(int $tpEmis)`

---

#### `getHistory(int $limit = 50)`

---

## NfeCorrectionService

**Tipo:** Class  
**Arquivo:** `app/services/NfeCorrectionService.php`  
**Namespace:** `Akti\Services`  

NfeCorrectionService — Carta de Correção (CC-e) de NF-e.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |
| private | `$sefazClient` | Não |
| private | `$docModel` | Não |
| private | `$logModel` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db, NfeSefazClient $sefazClient, NfeDocument $docModel, NfeLog $logModel)`

Construtor da classe NfeCorrectionService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |
| `$sefazClient` | `NfeSefazClient` | Sefaz client |
| `$docModel` | `NfeDocument` | Doc model |
| `$logModel` | `NfeLog` | Log model |

---

##### `correction(int $nfeId, string $texto): array`

Envia Carta de Correção (CC-e).

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$nfeId` | `int` | ID do registro nfe_documents |
| `$texto` | `string` | Texto da correção (mín 15 chars) |

**Retorno:** `array — ['success' => bool, 'message' => string]`

---

## NfeDanfeCustomizer

**Tipo:** Class  
**Arquivo:** `app/services/NfeDanfeCustomizer.php`  
**Namespace:** `Akti\Services`  

NfeDanfeCustomizer — Personalização do DANFE.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |
| private | `$settings` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe NfeDanfeCustomizer.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `generate(string $xmlAutorizado): ?string`

Gera DANFE personalizado a partir do XML autorizado.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$xmlAutorizado` | `string` | * @return string|null PDF binário ou null se falha |

**Retorno:** `string|null — PDF binário ou null se falha`

---

##### `saveSettings(array $data): bool`

Salva configurações de personalização do DANFE.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$data` | `array` | * @return bool |

**Retorno:** `bool — */`

---

##### `uploadLogo(array $file): array`

Faz upload e salva o logo do DANFE.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$file` | `array` | $_FILES['danfe_logo'] |

**Retorno:** `array — ['success' => bool, 'path' => string|null, 'message' => string]`

---

##### `getSettings(): array`

Retorna configurações atuais.

**Retorno:** `array — */`

---

## NfeDashboardService

**Tipo:** Class  
**Arquivo:** `app/services/NfeDashboardService.php`  
**Namespace:** `Akti\Services`  

Service: NfeDashboardService

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe NfeDashboardService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `loadDashboardData(string $startDate, string $endDate): array`

Carrega todos os dados para o dashboard fiscal.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$startDate` | `string` | * @param string $endDate |

**Retorno:** `array — Dados completos para a view`

---

#### Métodos Private

##### `loadAlerts(NfeReportModel $reportModel): array`

Carrega alertas fiscais + alerta de certificado.

---

### Funções auxiliares do arquivo

#### `loadQueueCounts()`

---

#### `loadReceivedPendingCount()`

---

## NfeDetailService

**Tipo:** Class  
**Arquivo:** `app/services/NfeDetailService.php`  
**Namespace:** `Akti\Services`  

Service: NfeDetailService

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe NfeDetailService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `loadInstallmentData(int $orderId): array`

Carrega parcelas vinculadas a um pedido e calcula resumo financeiro.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$orderId` | `int` | * @return array ['installments' => [...], 'summary' => [...]] |

**Retorno:** `array — ['installments' => [...], 'summary' => [...]]`

---

##### `calculateIbptax(int $nfeId, float $existingValorTributos = 0.0): array`

Calcula valor de tributos aproximados via IBPTax para uma NF-e.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$nfeId` | `int` | ID da NF-e |
| `$existingValorTributos` | `float` | Valor já salvo no documento |

**Retorno:** `array — ['valor' => float, 'fonte' => string]`

---

## NfeDistDFeService

**Tipo:** Class  
**Arquivo:** `app/services/NfeDistDFeService.php`  
**Namespace:** `Akti\Services`  

NfeDistDFeService — Consulta DistDFe (Distribuição de Documentos Fiscais Eletrônicos).

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |
| private | `$credModel` | Não |
| private | `$receivedModel` | Não |
| private | `$logModel` | Não |
| private | `$tools` | Não |
| private | `$credentials` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe NfeDistDFeService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `isAvailable(): bool`

Verifica se o serviço está disponível.

**Retorno:** `bool — */`

---

##### `queryByNSU(?string $ultimoNsu = null, int $maxLoops = 10): array`

Consulta DistDFe por NSU (incremental).

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$ultimoNsu` | `string|null` | Se null, usa o último NSU salvo |
| `$maxLoops` | `int` | Máximo de loops de consulta |

**Retorno:** `array — ['success' => bool, 'total' => int, 'ultimo_nsu' => string, 'message' => string]`

---

#### Métodos Private

##### `initTools(): bool`

Inicializa o Tools.

**Retorno:** `bool — */`

---

### Funções auxiliares do arquivo

#### `queryByChave(string $chave)`

---

#### `processDistDFeDoc(object $docZip)`

---

#### `getReceivedModel()`

---

## NfeDownloadService

**Tipo:** Class  
**Arquivo:** `app/services/NfeDownloadService.php`  
**Namespace:** `Akti\Services`  

Service: NfeDownloadService

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe NfeDownloadService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `getAuthorizedXml(array $doc): ?string`

Obtém o conteúdo XML autorizado de uma NF-e.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$doc` | `array` | Dados do documento NF-e |

**Retorno:** `string|null — XML ou null se indisponível`

---

##### `generateDanfe(string $xmlAutorizado): ?string`

Gera o DANFE (PDF) de uma NF-e autorizada.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$xmlAutorizado` | `string` | XML autorizado da NF-e |

**Retorno:** `string|null — Conteúdo do PDF ou null em caso de falha`

---

##### `getCancelXml(array $doc): ?string`

Obtém XML de cancelamento de uma NF-e.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$doc` | `array` | Dados do documento NF-e |

**Retorno:** `string|null — */`

---

##### `getCceXml(array $doc): ?string`

Obtém XML de carta de correção de uma NF-e.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$doc` | `array` | Dados do documento NF-e |

**Retorno:** `string|null — */`

---

##### `sendXmlDownload(string $xml, string $prefix, string $chave): void`

Envia cabeçalhos e conteúdo XML para download.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$xml` | `string` | Conteúdo XML |
| `$prefix` | `string` | Prefixo do filename (ex: 'NFe', 'Cancel', 'CCe') |
| `$chave` | `string` | Chave da NF-e para o nome do arquivo |

---

##### `sendDanfeDownload(string $pdf, string $chave): void`

Envia cabeçalhos e conteúdo PDF (DANFE) para visualização inline.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$pdf` | `string` | Conteúdo PDF |
| `$chave` | `string` | Chave da NF-e para o nome do arquivo |

---

## NfeEmissionService

**Tipo:** Class  
**Arquivo:** `app/services/NfeEmissionService.php`  
**Namespace:** `Akti\Services`  

NfeEmissionService — Emissão e inutilização de NF-e.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |
| private | `$sefazClient` | Não |
| private | `$docModel` | Não |
| private | `$logModel` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db, NfeSefazClient $sefazClient, NfeDocument $docModel, NfeLog $logModel)`

Construtor da classe NfeEmissionService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |
| `$sefazClient` | `NfeSefazClient` | Sefaz client |
| `$docModel` | `NfeDocument` | Doc model |
| `$logModel` | `NfeLog` | Log model |

---

##### `emit(int $orderId, array $orderData): array`

Emite uma NF-e para o pedido.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$orderId` | `int` | ID do pedido |
| `$orderData` | `array` | Dados completos do pedido (com itens, cliente, etc.) |

**Retorno:** `array — ['success' => bool, 'message' => string, 'nfe_id' => int|null, 'chave' => string|null]`

---

### Funções auxiliares do arquivo

#### `inutilizar(int $numInicial, int $numFinal, string $justificativa, int $modelo = 55, int $serie = 1)`

---

#### `saveDocumentItems(int $nfeId, array $items)`

---

#### `saveFiscalTotals(int $nfeId, array $totals)`

---

## NfeExportService

**Tipo:** Class  
**Arquivo:** `app/services/NfeExportService.php`  
**Namespace:** `Akti\Services`  

NfeExportService — Exportação de relatórios NF-e para Excel (.xlsx).

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$columnLabels` | Sim |

### Métodos

#### Métodos Public

##### `exportToExcel(array $data, string $title = 'Relatorio_NFe'): void`

Exporta dados de relatório NF-e para Excel e envia para download.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$data` | `array` | Array de registros (cada registro é um array associativo) |
| `$title` | `string` | Título do relatório / nome do arquivo |

**Retorno:** `void — (saída direta via headers HTTP)`

---

### Funções auxiliares do arquivo

#### `exportToCsv(array $data, string $filename = 'relatorio.csv')`

---

#### `(str_replace('_', ' ', $h))`

---

## NfeFiscalReportService

**Tipo:** Class  
**Arquivo:** `app/services/NfeFiscalReportService.php`  
**Namespace:** `Akti\Services`  

Service: NfeFiscalReportService

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe NfeFiscalReportService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `getCorrectionReportData(string $startDate, string $endDate): array`

Carrega dados do relatório de Cartas de Correção (CC-e).

---

##### `getExportData(string $type, string $startDate, string $endDate): array`

Retorna dados e título para exportação de relatório.

**Retorno:** `array — ['data' => array, 'title' => string] ou ['error' => string]`

---

### Funções auxiliares do arquivo

#### `exportToExcel(array $data, string $title)`

---

#### `generateSped(string $startDate, string $endDate)`

---

#### `generateSintegra(string $startDate, string $endDate)`

---

#### `getLivroSaidasData(string $startDate, string $endDate)`

---

#### `getLivroEntradasData(string $startDate, string $endDate)`

---

## NfeManifestationService

**Tipo:** Class  
**Arquivo:** `app/services/NfeManifestationService.php`  
**Namespace:** `Akti\Services`  

NfeManifestationService — Manifestação do Destinatário.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |
| private | `$credModel` | Não |
| private | `$receivedModel` | Não |
| private | `$logModel` | Não |
| private | `$tools` | Não |
| private | `$credentials` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe NfeManifestationService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `manifest(int $docId, string $type, string $justificativa = ''): array`

Envia manifestação do destinatário.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$docId` | `int` | ID na tabela nfe_received_documents |
| `$type` | `string` | ciencia|confirmada|desconhecida|nao_realizada |
| `$justificativa` | `string` | Obrigatório para nao_realizada (mín 15 chars) |

**Retorno:** `array — ['success' => bool, 'message' => string]`

---

#### Métodos Private

##### `initTools(): bool`

Inicializa o Tools.

**Retorno:** `bool — */`

---

## NfeOrderDataService

**Tipo:** Class  
**Arquivo:** `app/services/NfeOrderDataService.php`  
**Namespace:** `Akti\Services`  

Service: NfeOrderDataService

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe NfeOrderDataService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `loadOrderWithItems(int $orderId): array`

Carrega e valida um pedido para emissão.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$orderId` | `int` | * @return array{order: array, items: array} |

**Retorno:** `array{order: — array, items: array}`

---

### Funções auxiliares do arquivo

#### `enrichItemsWithFiscalData(array $items)`

---

#### `loadCustomer(?int $customerId)`

---

#### `loadInstallments(int $orderId)`

---

#### `buildNfeData(int $orderId)`

---

#### `buildNfceData(int $orderId)`

---

## NfePdfGenerator

**Tipo:** Class  
**Arquivo:** `app/services/NfePdfGenerator.php`  
**Namespace:** `Akti\Services`  

NfePdfGenerator — Gera DANFE (PDF) a partir do XML autorizado.

### Métodos

#### Métodos Public

##### `static generate(string $xmlAutorizado, string $outputPath): bool`

Gera o DANFE a partir do XML autorizado.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$xmlAutorizado` | `string` | XML completo (procNFe) |
| `$outputPath` | `string` | Caminho onde salvar o PDF |

**Retorno:** `bool — true se gerou com sucesso`

---

##### `static renderToString(string $xmlAutorizado): ?string`

Retorna o PDF como string (para download direto).

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$xmlAutorizado` | `string` | * @return string|null PDF binary ou null se erro |

**Retorno:** `string|null — PDF binary ou null se erro`

---

## NfeQueryService

**Tipo:** Class  
**Arquivo:** `app/services/NfeQueryService.php`  
**Namespace:** `Akti\Services`  

NfeQueryService — Consultas SEFAZ (status do serviço, consulta por chave).

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$sefazClient` | Não |
| private | `$docModel` | Não |
| private | `$logModel` | Não |

### Métodos

#### Métodos Public

##### `__construct(NfeSefazClient $sefazClient, NfeDocument $docModel, NfeLog $logModel)`

Construtor da classe NfeQueryService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$sefazClient` | `NfeSefazClient` | Sefaz client |
| `$docModel` | `NfeDocument` | Doc model |
| `$logModel` | `NfeLog` | Log model |

---

##### `testConnection(): array`

Testa conexão com a SEFAZ (statusServico).

**Retorno:** `array — ['success' => bool, 'message' => string, 'details' => array]`

---

### Funções auxiliares do arquivo

#### `checkStatus(int $nfeId)`

---

## NfeQueueService

**Tipo:** Class  
**Arquivo:** `app/services/NfeQueueService.php`  
**Namespace:** `Akti\Services`  

NfeQueueService — Gerencia a fila de emissão assíncrona de NF-e.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |
| private | `$queueModel` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe NfeQueueService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `enqueue(int $orderId, int $modelo = 55, int $priority = 5): array`

Enfileira um pedido para emissão.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$orderId` | `int` | * @param int $modelo  55 ou 65 |
| `$priority` | `int` | 1=alta, 5=normal, 10=baixa |

**Retorno:** `array — ['success' => bool, 'queue_id' => int|null, 'message' => string]`

---

### Funções auxiliares do arquivo

#### `enqueueBatch(array $orderIds, int $modelo = 55)`

---

#### `processNext()`

---

#### `processMultiple(int $max = 10)`

---

#### `getModel()`

---

## NfeSefazClient

**Tipo:** Class  
**Arquivo:** `app/services/NfeSefazClient.php`  
**Namespace:** `Akti\Services`  

NfeSefazClient — Gerencia inicialização e acesso ao sped-nfe Tools.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |
| private | `$credModel` | Não |
| private | `$logModel` | Não |
| private | `$credentials` | Não |
| private | `$tools` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe NfeSefazClient.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `isLibraryAvailable(): bool`

Verifica se a biblioteca sped-nfe está disponível.

---

##### `initTools(): bool`

Inicializa o Tools do sped-nfe com as credenciais do tenant.

---

##### `getTools()`

Retorna a instância de Tools (null se não inicializado).

**Retorno:** `\NFePHP\NFe\Tools|null — */`

---

##### `getCredentials(): array`

Retorna as credenciais carregadas.

---

##### `getCredModel(): NfeCredential`

Retorna o model de credenciais.

---

##### `getLogModel(): NfeLog`

Retorna o model de log.

---

## NfeService

**Tipo:** Class  
**Arquivo:** `app/services/NfeService.php`  
**Namespace:** `Akti\Services`  
**Implementa:** `NfeServiceInterface`  

NfeService — Facade para operações NF-e.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$sefazClient` | Não |
| private | `$emissionService` | Não |
| private | `$cancellationService` | Não |
| private | `$correctionService` | Não |
| private | `$queryService` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe NfeService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `isLibraryAvailable(): bool`

Verifica uma condição booleana.

**Retorno:** `bool — */`

---

##### `testConnection(): array`

Test connection.

**Retorno:** `array — */`

---

##### `emit(int $orderId, array $orderData): array`

Emite evento ou sinal.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$orderId` | `int` | ID do pedido |
| `$orderData` | `array` | Order data |

**Retorno:** `array — */`

---

##### `cancel(int $nfeId, string $motivo): array`

Cancela operação.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$nfeId` | `int` | Nfe id |
| `$motivo` | `string` | Motivo |

**Retorno:** `array — */`

---

##### `correction(int $nfeId, string $texto): array`

Correction.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$nfeId` | `int` | Nfe id |
| `$texto` | `string` | Texto |

**Retorno:** `array — */`

---

##### `checkStatus(int $nfeId): array`

Verifica condição ou estado.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$nfeId` | `int` | Nfe id |

**Retorno:** `array — */`

---

##### `getCredentials(): array`

Obtém dados específicos.

**Retorno:** `array — */`

---

##### `inutilizar(int $numInicial, int $numFinal, string $justificativa, int $modelo = 55, int $serie = 1): array`

Inutilizar.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$numInicial` | `int` | Num inicial |
| `$numFinal` | `int` | Num final |
| `$justificativa` | `string` | Justificativa |
| `$modelo` | `int` | Modelo |
| `$serie` | `int` | Serie |

**Retorno:** `array — */`

---

## NfeSintegraService

**Tipo:** Class  
**Arquivo:** `app/services/NfeSintegraService.php`  
**Namespace:** `Akti\Services`  

NfeSintegraService — Gera arquivo no formato SINTEGRA.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |
| private | `$reportModel` | Não |
| private | `$credModel` | Não |
| private | `$typeCounts` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe NfeSintegraService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `generate(string $startDate, string $endDate, array $options = []): string`

Gera o arquivo SINTEGRA completo para o período.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$startDate` | `string` | Data inicial (Y-m-d) |
| `$endDate` | `string` | Data final (Y-m-d) |
| `$options` | `array` | Opções: cod_finalidade, cod_natureza |

**Retorno:** `string — Conteúdo do arquivo SINTEGRA (.txt)`

---

#### Métodos Private

##### `buildRegistro10(array $cred, string $start, string $end, string $finalidade, string $natureza): string`

Registro 10 — Mestre do Estabelecimento.

---

##### `buildRegistro11(array $cred): string`

Registro 11 — Dados Complementares do Informante.

---

##### `buildRegistro50(array $nfe): string`

Registro 50 — Total de Nota Fiscal.

---

##### `buildRegistro54(array $item): string`

Registro 54 — Produto (Item da Nota Fiscal).

---

##### `buildRegistro75(array $prod, string $start, string $end): string`

Registro 75 — Código do Produto ou Serviço.

---

##### `buildRegistro90(array $cred): string`

Registro 90 — Totalizador.

---

##### `buildRegistro99(int $totalLines): string`

Registro 99 — Encerramento.

---

##### `incrementCount(string $type): void`

Incrementa contador de tipo de registro.

---

##### `loadConfig(): array`

Carrega configurações fiscais.

---

##### `getNfeItems(string $start, string $end): array`

Busca itens de NF-e para o período.

---

##### `getDistinctProducts(string $start, string $end): array`

Busca produtos distintos das NF-e do período.

---

## NfeSpedFiscalService

**Tipo:** Class  
**Arquivo:** `app/services/NfeSpedFiscalService.php`  
**Namespace:** `Akti\Services`  

NfeSpedFiscalService — Gera arquivo SPED Fiscal (EFD ICMS/IPI).

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |
| private | `$reportModel` | Não |
| private | `$credModel` | Não |
| private | `$registroCounts` | Não |
| private | `$lines` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe NfeSpedFiscalService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `generate(string $startDate, string $endDate, array $options = []): string`

Gera o arquivo SPED Fiscal completo para o período.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$startDate` | `string` | Data inicial (Y-m-d) |
| `$endDate` | `string` | Data final (Y-m-d) |
| `$options` | `array` | Opções: finalidade, perfil, atividade |

**Retorno:** `string — Conteúdo do arquivo SPED (.txt)`

---

#### Métodos Private

##### `addBloco0(array $cred, string $start, string $end, string $finalidade, string $perfil, string $atividade): void`

Bloco 0 — Abertura, Identificação e Referências.

---

##### `addBlocoC(array $nfes, array $nfeItems): void`

Bloco C — Documentos Fiscais I — Mercadorias (ICMS/IPI).

---

##### `addBlocoE(string $start, string $end, array $taxSummary): void`

Bloco E — Apuração do ICMS.

---

##### `addBlocoH(): void`

Bloco H — Inventário Físico.

---

##### `addBloco9(): void`

Bloco 9 — Controle e Encerramento.

---

##### `addLine(string $registro, array $campos = []): void`

Adiciona uma linha SPED (pipe-delimited).

---

##### `countRegistros(string $bloco): string`

Conta registros de um bloco.

---

##### `formatDate(string $date): string`

Formata data para o formato SPED (ddmmaaaa).

---

##### `loadConfig(): array`

Carrega configurações fiscais.

---

##### `getNfeItems(string $start, string $end): array`

Busca itens de NF-e para o período.

---

## NfeStorageService

**Tipo:** Class  
**Arquivo:** `app/services/NfeStorageService.php`  
**Namespace:** `Akti\Services`  

NfeStorageService — Salva XMLs e DANFEs em disco, organizados por tenant/ano/mês.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$basePath` | Não |
| private | `$tenantDir` | Não |

### Métodos

#### Métodos Public

##### `__construct()`

Construtor da classe NfeStorageService.

---

##### `saveXml(string $chave, string $xml, string $tipo = 'nfe'): ?string`

Salva o XML autorizado em disco.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$chave` | `string` | Chave de acesso (44 dígitos) |
| `$xml` | `string` | Conteúdo XML |
| `$tipo` | `string` | Tipo: 'nfe', 'cancel', 'cce' |

**Retorno:** `string|null — Caminho relativo do arquivo ou null se erro`

---

### Funções auxiliares do arquivo

#### `saveDanfe(string $chave, string $pdf)`

---

#### `readFile(string $relativePath)`

---

#### `fileExists(string $relativePath)`

---

#### `getDirectory()`

---

#### `ensureHtaccess()`

---

## NfeWebhookManagementService

**Tipo:** Class  
**Arquivo:** `app/services/NfeWebhookManagementService.php`  
**Namespace:** `Akti\Services`  

Service: NfeWebhookManagementService

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe NfeWebhookManagementService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `listAll(): array`

Lista todos os webhooks configurados.

---

##### `save(array $data): array`

Cria ou atualiza um webhook.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$data` | `array` | [name, url, secret, events, is_active, retry_count, timeout_seconds, id?] |

**Retorno:** `array — ['success' => bool, 'message' => string]`

---

##### `delete(int $id): array`

Exclui um webhook.

---

##### `test(int $id): array`

Testa envio de um webhook.

---

### Funções auxiliares do arquivo

#### `getLogs(int $id, int $page = 1, int $perPage = 20)`

---

## NfeWebhookService

**Tipo:** Class  
**Arquivo:** `app/services/NfeWebhookService.php`  
**Namespace:** `Akti\Services`  

NfeWebhookService — Dispara webhooks para eventos NF-e.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |
| private | `$model` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe NfeWebhookService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `dispatch(string $eventName, array $payload): array`

Dispara webhooks para um evento.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$eventName` | `string` | Nome do evento (ex: nfe.authorized) |
| `$payload` | `array` | Dados do evento |

**Retorno:** `array — ['dispatched' => int, 'success' => int, 'failed' => int]`

---

#### Métodos Private

##### `sendWebhook(array $webhook, string $eventName, array $payload): bool`

Envia um webhook com retry.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$webhook` | `array` | * @param string $eventName |
| `$payload` | `array` | * @return bool |

**Retorno:** `bool — */`

---

### Funções auxiliares do arquivo

#### `getModel()`

---

## NfeXmlBuilder

**Tipo:** Class  
**Arquivo:** `app/services/NfeXmlBuilder.php`  
**Namespace:** `Akti\Services`  

NfeXmlBuilder — Monta o XML da NF-e no formato 4.00.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$emitente` | Não |
| private | `$orderData` | Não |
| private | `$numero` | Não |
| private | `$serie` | Não |
| private | `$taxCalc` | Não |
| private | `$ibptaxModel` | Não |
| private | `$ibptaxEnabled` | Não |
| private | `$calculatedItems` | Não |
| private | `$calculatedTotals` | Não |

### Métodos

#### Métodos Public

##### `__construct(array $emitente, array $orderData, int $numero, int $serie)`

Construtor da classe NfeXmlBuilder.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$emitente` | `array` | Emitente |
| `$orderData` | `array` | Order data |
| `$numero` | `int` | Numero |
| `$serie` | `int` | Serie |

---

##### `getCalculatedItems(): array`

Retorna os dados fiscais calculados de cada item (após build()).

**Retorno:** `array — */`

---

##### `getCalculatedTotals(): array`

Retorna os totais fiscais calculados (após build()).

**Retorno:** `array — */`

---

##### `build(): string`

Monta e retorna o XML da NF-e (ainda não assinado).

**Retorno:** `string — XML`

---

#### Métodos Private

##### `initIbptax(): void`

Inicializa o IBPTax model se a tabela existir e estiver habilitado.

---

##### `buildCobr($nfe, float $vNF): void`

Monta tag cobr (fatura/duplicatas) se o pedido tiver parcelas.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$nfe` | `\NFePHP\NFe\Make` | * @param float $vNF Valor total da NF-e |

---

##### `buildInfRespTec($nfe): void`

Monta tag infRespTec (responsável técnico pelo software emissor).

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$nfe` | `\NFePHP\NFe\Make` | */ |

---

##### `mapPaymentMethod(string $method): string`

Mapeia forma de pagamento do sistema para código NFe.

---

##### `getCodeUF(string $uf): int`

Retorna código UF para SEFAZ.

---

##### `calculateApproximateTaxes(array $items): string`

Calcula e retorna os tributos aproximados para os itens informados,

---

## NfeXmlValidator

**Tipo:** Class  
**Arquivo:** `app/services/NfeXmlValidator.php`  
**Namespace:** `Akti\Services`  

NfeXmlValidator — Valida XML da NF-e contra schema XSD antes do envio à SEFAZ.

### Métodos

#### Métodos Public

##### `static validate(string $xmlSigned): array`

Valida o XML assinado contra o schema XSD da NF-e 4.00.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$xmlSigned` | `string` | XML assinado (antes do envio) |

**Retorno:** `array — ['valid' => bool, 'errors' => string[]]`

---

#### Métodos Private

##### `static basicValidation(\DOMDocument $dom): array`

Validação básica de estrutura (quando XSD não está disponível).

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$dom` | `\DOMDocument` | * @return array ['valid' => bool, 'errors' => string[]] |

**Retorno:** `array — ['valid' => bool, 'errors' => string[]]`

---

### Funções auxiliares do arquivo

#### `findSchemaPath(string $schemaFile)`

---

#### `formatXmlError(\LibXMLError $error)`

---

## OrderItemService

**Tipo:** Class  
**Arquivo:** `app/services/OrderItemService.php`  
**Namespace:** `Akti\Services`  

OrderItemService — Lógica de negócio para itens de pedido.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |
| private | `$orderModel` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db, Order $orderModel)`

Construtor da classe OrderItemService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |
| `$orderModel` | `Order` | Order model |

---

##### `orderHasPaidInstallments(int $orderId): bool`

Verifica se o pedido possui parcelas pagas (bloqueia alteração de produtos).

---

##### `clearQuoteConfirmation(int $orderId): void`

Remove a confirmação de orçamento quando produtos são modificados.

---

### Funções auxiliares do arquivo

#### `getOrderIdFromItem(int $itemId)`

---

#### `updateItemQuantity(int $itemId, int $quantity)`

---

#### `updateItemDiscount(int $itemId, float $discount)`

---

## PipelineAlertService

**Tipo:** Class  
**Arquivo:** `app/services/PipelineAlertService.php`  
**Namespace:** `Akti\Services`  

Service responsável pela lógica de alertas e atrasos do pipeline.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |
| private | `$pipelineModel` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db, Pipeline $pipelineModel)`

Construtor da classe PipelineAlertService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |
| `$pipelineModel` | `Pipeline` | Pipeline model |

---

##### `getDelayedOrders(): array`

Retorna pedidos atrasados para notificações.

**Retorno:** `array — ['delayed' => array, 'count' => int]`

---

##### `getStats(): array`

Retorna estatísticas do pipeline (stats gerais).

---

##### `getStageGoals(): array`

Retorna metas de tempo por etapa.

---

##### `checkOrderStock(int $orderId, ?int $warehouseId, $stockModel): array`

Verifica disponibilidade de estoque dos itens de um pedido num armazém.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$orderId` | `int` | * @param int|null $warehouseId |
| `$stockModel` | `\Akti\Models\Stock` | * @return array Resultado com warehouses, items, all_from_stock |

**Retorno:** `array — Resultado com warehouses, items, all_from_stock`

---

##### `countInstallments(int $orderId): int`

Conta parcelas existentes de um pedido.

---

##### `deleteInstallments(int $orderId): array`

Remove todas as parcelas de um pedido (se nenhuma paga).

**Retorno:** `array — Resultado com success, deleted ou mensagem de erro`

---

## PipelineDetailService

**Tipo:** Class  
**Arquivo:** `app/services/PipelineDetailService.php`  
**Namespace:** `Akti\Services`  

Service responsável por agregar todos os dados necessários para a view de detalhes do pipeline.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |
| private | `$pipelineModel` | Não |
| private | `$stockModel` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db, Pipeline $pipelineModel, Stock $stockModel)`

Construtor da classe PipelineDetailService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |
| `$pipelineModel` | `Pipeline` | Pipeline model |
| `$stockModel` | `Stock` | Stock model |

---

##### `loadDetailData(int $orderId): ?array`

Carrega todos os dados necessários para exibir o detalhe de um pedido no pipeline.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$orderId` | `int` | ID do pedido |

**Retorno:** `array|null — Array com todos os dados ou null se pedido não encontrado`

---

##### `loadPrintProductionData(int $orderId): ?array`

Carrega dados para a view de impressão da ordem de produção.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$orderId` | `int` | ID do pedido |

**Retorno:** `array|null — Array com dados ou null se pedido não encontrado`

---

##### `loadThermalReceiptData(int $orderId): ?array`

Carrega dados para a view de impressão do cupom térmico.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$orderId` | `int` | ID do pedido |

**Retorno:** `array|null — Array com dados ou null se pedido não encontrado`

---

##### `loadProductionBoardData(array $userAllowedSectorIds): array`

Carrega dados para o painel de produção (production board).

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$userAllowedSectorIds` | `array` | IDs dos setores permitidos ao usuário |

**Retorno:** `array — Array com boardData, itemLogCounts, stages`

---

## PipelinePaymentService

**Tipo:** Class  
**Arquivo:** `app/services/PipelinePaymentService.php`  
**Namespace:** `Akti\Services`  
**Implementa:** `Contracts\PipelinePaymentServiceInterface`  

Service responsável pela lógica de geração de links de pagamento do pipeline.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe PipelinePaymentService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `generatePaymentLink(int $orderId, string $gatewaySlug = '', string $method = 'auto'): array`

Gera link de pagamento via gateway configurado ou fallback legado.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$orderId` | `int` | ID do pedido |
| `$gatewaySlug` | `string` | Slug do gateway (vazio = usar padrão) |
| `$method` | `string` | Método de pagamento (auto, pix, boleto, credit_card) |

**Retorno:** `array — Resultado com success, payment_url, etc.`

---

### Funções auxiliares do arquivo

#### `legacyMercadoPagoLink(array $order, int $orderId)`

---

#### `createMercadoPagoPreference(string $accessToken, array $payload)`

---

#### `getAppBaseUrl()`

---

#### `findPendingInstallmentId(int $orderId)`

---

#### `persistPaymentLink(Order $orderModel, int $orderId, array $order, string $paymentUrl, string $gatewaySlug, string $method)`

---

#### `generateCheckoutLink(int $orderId, ?int $installmentId = null, string $gatewaySlug = '', array $allowedMethods = [])`

---

## PipelineService

**Tipo:** Class  
**Arquivo:** `app/services/PipelineService.php`  
**Namespace:** `Akti\Services`  

Service responsável pela lógica de movimentação e regras de etapas do pipeline.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |
| private | `$pipelineModel` | Não |
| private | `$stockModel` | Não |
| private | `$preProductionStages` | Sim |
| private | `$productionStages` | Sim |
| private | `$stagesBlockedByPaidInstallments` | Sim |

### Métodos

#### Métodos Public

##### `__construct(PDO $db, Pipeline $pipelineModel, Stock $stockModel)`

Construtor da classe PipelineService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |
| `$pipelineModel` | `Pipeline` | Pipeline model |
| `$stockModel` | `Stock` | Stock model |

---

##### `isPreProduction(string $stage): bool`

Verifica se a etapa é pré-produção.

---

##### `isProduction(string $stage): bool`

Verifica se a etapa é de produção.

---

##### `transitionNeedsWarehouse(string $currentStage, string $newStage): bool`

Verifica se a transição de etapa precisa de seleção de armazém.

---

##### `checkPaidInstallmentsBlock(int $orderId, string $newStage): ?string`

Verifica se a movimentação está bloqueada por parcelas pagas.

**Retorno:** `string|null — Mensagem de erro ou null se OK`

---

##### `getCurrentStage(int $orderId): ?string`

Busca a etapa atual de um pedido.

---

##### `handleStockTransition(int $orderId, ?string $currentStage, string $newStage, ?int $warehouseId = null, ?int $userId = null): array`

Processa a lógica de estoque ao mudar de etapa.

**Retorno:** `array — ['success' => bool, 'notes' => string]`

---

##### `autoGenerateInstallments(int $orderId): bool`

Gera automaticamente as parcelas de pagamento quando o pedido

**Retorno:** `bool — true se parcelas foram geradas`

---

##### `clearQuoteConfirmation(int $orderId): void`

Remove a confirmação de orçamento quando o pedido é modificado.

---

#### Métodos Private

##### `deductStock(int $orderId, string $newStage, ?int $warehouseId): string`

Deduz estoque ao entrar em produção.

---

### Funções auxiliares do arquivo

#### `moveOrder(int $orderId, string $newStage, ?int $userId, ?int $warehouseId = null, string $notes = '')`

---

#### `regenerateInstallmentsIfNeeded(int $orderId, array $paymentData)`

---

#### `($regularInstallments)`

---

#### `($existingInstallments as $inst)`

---

#### `syncInstallments(int $orderId, string $paymentMethod, int $numInst, float $downPayment, float $discount, array $dueDates = [])`

---

## Portal2faService

**Tipo:** Class  
**Arquivo:** `app/services/Portal2faService.php`  
**Namespace:** `Akti\Services`  

Service: Portal2faService

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |
| private | `$portalAccess` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db, PortalAccess $portalAccess)`

Construtor da classe Portal2faService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |
| `$portalAccess` | `PortalAccess` | Portal access |

---

##### `validateCode(int $accessId, string $code): bool`

Valida código 2FA informado pelo cliente.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$accessId` | `int` | * @param string $code |

**Retorno:** `bool — true se código válido`

---

##### `resendCode(int $accessId): string`

Gera e retorna novo código 2FA.

---

##### `toggle(int $accessId, bool $enable): void`

Ativa ou desativa 2FA para um acesso.

---

## PortalAdminService

**Tipo:** Class  
**Arquivo:** `app/services/PortalAdminService.php`  
**Namespace:** `Akti\Services`  

PortalAdminService — Lógica de negócio da administração do Portal do Cliente.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe PortalAdminService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `getFilteredAccesses(string $search, string $filter): array`

Busca acessos filtrados por pesquisa e status.

---

### Funções auxiliares do arquivo

#### `getCustomersWithoutAccess()`

---

#### `getPortalMetrics()`

---

#### `countPendingMessages()`

---

#### `removeActiveSessions(int $accessId)`

---

#### `generateTempPassword()`

---

## PortalAuthService

**Tipo:** Class  
**Arquivo:** `app/services/PortalAuthService.php`  
**Namespace:** `Akti\Services`  

Service: PortalAuthService

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |
| private | `$portalAccess` | Não |
| private | `$logger` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db, PortalAccess $portalAccess, Logger $logger)`

Construtor da classe PortalAuthService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |
| `$portalAccess` | `PortalAccess` | Portal access |
| `$logger` | `Logger` | Logger |

---

##### `loginWithPassword(string $email, string $password, array $config): array`

Processa login por e-mail e senha.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$email` | `string` | * @param string $password |
| `$config` | `array` | Configuração do portal (require_password, enable_2fa, etc.) |

**Retorno:** `array — ['success' => bool, 'error' => string|null, 'redirect' => string|null, '2fa' => bool]`

---

### Funções auxiliares do arquivo

#### `loginWithMagicLink(string $token)`

---

#### `setupNewPassword(string $token, string $newPassword, string $confirmPassword)`

---

#### `validateToken(string $token)`

---

#### `register(array $formData)`

---

#### `executeSessionLogin(array $access, string $ip)`

---

#### `isPasswordStrong(string $password)`

---

## PortalAvatarService

**Tipo:** Class  
**Arquivo:** `app/services/PortalAvatarService.php`  
**Namespace:** `Akti\Services`  

Service: PortalAvatarService

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$portalAccess` | Não |
| private | `$logger` | Não |

### Métodos

#### Métodos Public

##### `__construct(PortalAccess $portalAccess, Logger $logger)`

Construtor da classe PortalAvatarService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$portalAccess` | `PortalAccess` | Portal access |
| `$logger` | `Logger` | Logger |

---

##### `upload(array $file, int $accessId, int $customerId): array`

Processa upload de avatar.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$file` | `array` | Entrada de $_FILES['avatar'] |
| `$accessId` | `int` | ID do acesso portal |
| `$customerId` | `int` | ID do cliente |

**Retorno:** `array — ['success' => bool, 'message' => string, 'path' => string|null]`

---

### Funções auxiliares do arquivo

#### `getExtension(string $mimeType)`

---

## PortalCartService

**Tipo:** Class  
**Arquivo:** `app/services/PortalCartService.php`  
**Namespace:** `Akti\Services`  

Service: PortalCartService

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$portalAccess` | Não |

### Métodos

#### Métodos Public

##### `__construct(PortalAccess $portalAccess)`

Construtor da classe PortalCartService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$portalAccess` | `PortalAccess` | Portal access |

---

##### `getCartSummary(): array`

Retorna os dados atuais do carrinho (itens, contagem, total).

**Retorno:** `array{cart: — array, cartCount: int, cartTotal: float}`

---

##### `addItem(int $productId, int $quantity = 1): array`

Adiciona um produto ao carrinho.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$productId` | `int` | * @param int $quantity |

**Retorno:** `array{success: — bool, message: string, cart: array, cartCount: int, cartTotal: float}`

---

##### `removeItem(int $productId): array`

Remove um produto do carrinho.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$productId` | `int` | * @return array{cart: array, cartCount: int, cartTotal: float} |

**Retorno:** `array{cart: — array, cartCount: int, cartTotal: float}`

---

##### `updateItemQuantity(int $productId, int $quantity): array`

Atualiza a quantidade de um item no carrinho.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$productId` | `int` | * @param int $quantity Se <= 0, remove o item |

**Retorno:** `array{cart: — array, cartCount: int, cartTotal: float}`

---

##### `clear(): void`

Limpa o carrinho completamente.

---

## PortalLang

**Tipo:** Class  
**Arquivo:** `app/services/PortalLang.php`  
**Namespace:** `Akti\Services`  

PortalLang — Sistema de tradução (i18n) do Portal do Cliente.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$translations` | Sim |
| private | `$lang` | Sim |
| private | `$initialized` | Sim |

### Métodos

#### Métodos Public

##### `static init(string $lang = 'pt-br'): void`

Inicializa o sistema de tradução com o idioma especificado.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$lang` | `string` | Código do idioma (ex: 'pt-br', 'en', 'es') |

**Retorno:** `void — */`

---

##### `static get(string $key, array $params = [], ?string $default = null): string`

Retorna a tradução de uma chave, com suporte a placeholders.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$key` | `string` | Chave de tradução (ex: 'login_title') |
| `$params` | `array` | Placeholders (ex: ['name' => 'João']) |
| `$default` | `string|null` | Valor padrão se a chave não existir |

**Retorno:** `string — */`

---

##### `static getLang(): string`

Retorna o idioma atual.

**Retorno:** `string — */`

---

##### `static getAvailableLanguages(): array`

Lista idiomas disponíveis.

**Retorno:** `array — [code => label]`

---

#### Métodos Private

##### `static loadTranslations(string $lang): array`

Carrega o arquivo de traduções para o idioma especificado.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$lang` | `string` | * @return array |

**Retorno:** `array — */`

---

## PortalOrderService

**Tipo:** Class  
**Arquivo:** `app/services/PortalOrderService.php`  
**Namespace:** `Akti\Services`  

Service: PortalOrderService

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |
| private | `$portalAccess` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db, PortalAccess $portalAccess)`

Construtor da classe PortalOrderService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |
| `$portalAccess` | `PortalAccess` | Portal access |

---

##### `listOrders(int $customerId, string $filter = 'all', int $page = 1, int $perPage = 10): array`

Carrega lista de pedidos paginada para um cliente.

---

##### `getOrderDetail(int $orderId, int $customerId): ?array`

Carrega detalhes completos de um pedido para um cliente.

**Retorno:** `array|null — null se não encontrado/não pertence ao cliente`

---

##### `approveOrder(int $orderId, int $customerId, string $ip, ?string $notes = null): array`

Aprova um orçamento.

**Retorno:** `array — ['success' => bool, 'message' => string]`

---

##### `rejectOrder(int $orderId, int $customerId, string $ip, ?string $notes = null): array`

Rejeita um orçamento.

**Retorno:** `array — ['success' => bool, 'message' => string]`

---

##### `cancelApproval(int $orderId, int $customerId, string $ip): array`

Cancela aprovação/rejeição (volta para pendente).

**Retorno:** `array — ['success' => bool, 'message' => string, 'previous_status' => string]`

---

##### `submitOrder(int $customerId, array $cart, ?string $notes = null): ?int`

Submete pedido a partir do carrinho.

**Retorno:** `int|null — ID do pedido criado ou null em caso de carrinho vazio`

---

## ProductGradeService

**Tipo:** Class  
**Arquivo:** `app/services/ProductGradeService.php`  
**Namespace:** `Akti\Services`  

Service responsável pela lógica de grades e combinações de produtos.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$gradeModel` | Não |

### Métodos

#### Métodos Public

##### `__construct(ProductGrade $gradeModel)`

Construtor da classe ProductGradeService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$gradeModel` | `ProductGrade` | Grade model |

---

##### `getAllGradeTypes(): array`

Retorna todos os tipos de grade.

---

##### `getProductGradesWithValues(int $productId): array`

Retorna grades com valores de um produto.

---

##### `getProductCombinations(int $productId): array`

Retorna combinações de um produto.

---

##### `createGradeType(string $name, ?string $description, string $icon = 'fas fa-th'): array`

Cria um novo tipo de grade via AJAX.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$name` | `string` | * @param string|null $description |
| `$icon` | `string` | * @return array Resultado com success, id, name, icon |

**Retorno:** `array — Resultado com success, id, name, icon`

---

##### `saveProductGrades(int $productId, array $grades): void`

Salva grades de um produto.

---

##### `saveCombinationsData(int $productId, array $combinations): void`

Salva dados de combinações de um produto.

---

##### `generateCombinations(array $gradesData): array`

Gera combinações (produto cartesiano) com base nos dados de grades.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$gradesData` | `array` | Array com dados de grades e valores |

**Retorno:** `array — Lista de combinações geradas`

---

## ProductImportService

**Tipo:** Class  
**Arquivo:** `app/services/ProductImportService.php`  
**Namespace:** `Akti\Services`  

Service responsável por toda lógica de importação de produtos.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |
| private | `$productModel` | Não |
| private | `$categoryModel` | Não |
| private | `$subcategoryModel` | Não |
| private | `$logger` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db, Product $productModel, Category $categoryModel, Subcategory $subcategoryModel, Logger $logger)`

Construtor da classe ProductImportService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |
| `$productModel` | `Product` | Product model |
| `$categoryModel` | `Category` | Category model |
| `$subcategoryModel` | `Subcategory` | Subcategory model |
| `$logger` | `Logger` | Logger |

---

##### `parseImportFile(array $file): array`

Analisa arquivo de importação (CSV/XLS/XLSX) e retorna preview sem efetuar importação.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$file` | `array` | $_FILES['import_file'] |

**Retorno:** `array — Resultado com columns, preview, total_rows, auto_mapping`

---

##### `importProductsMapped(array $mapping): array`

Importa produtos usando mapeamento de colunas definido pelo usuário.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$mapping` | `array` | Mapeamento file_column => system_field |

**Retorno:** `array — Resultado com imported, errors`

---

##### `importProductsDirect(array $file): array`

Importa produtos diretamente (mapeamento automático por header).

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$file` | `array` | $_FILES['import_file'] |

**Retorno:** `array — Resultado com imported, errors`

---

##### `generateImportTemplate(): void`

Gera CSV de template para importação.

---

#### Métodos Private

##### `readFileRows(string $filePath, string $ext): array`

Lê as linhas de um arquivo CSV ou Excel e retorna array associativo.

---

##### `parseCsvFile(string $filePath): array`

Interpreta dados.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$filePath` | `string` | File path |

**Retorno:** `array — */`

---

##### `parseExcelFile(string $filePath): array`

Interpreta dados.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$filePath` | `string` | File path |

**Retorno:** `array — */`

---

##### `getColumnMap(): array`

Retorna mapa de colunas para mapeamento automático.

---

##### `autoMapColumns(array $columns): array`

Gera mapeamento automático de colunas.

---

##### `normalizeColumnName(string $name): string`

Normaliza nome de coluna para mapeamento.

---

##### `resolveCategory(string $catName, array &$cache): ?int`

Resolve (ou cria) uma categoria pelo nome.

---

##### `resolveSubcategory(string $subName, ?int $categoryId, array &$cache): ?int`

Resolve (ou cria) uma subcategoria pelo nome e categoria.

---

## ProductionCostService

**Tipo:** Class  
**Arquivo:** `app/services/ProductionCostService.php`  
**Namespace:** `Akti\Services`  

Class ProductionCostService.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe ProductionCostService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `calculateOrderCost(int $orderId, int $tenantId): array`

Calcula valor.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$orderId` | `int` | ID do pedido |
| `$tenantId` | `int` | ID do tenant |

**Retorno:** `array — */`

---

##### `getOrderCost(int $orderId, int $tenantId): ?array`

Obtém dados específicos.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$orderId` | `int` | ID do pedido |
| `$tenantId` | `int` | ID do tenant |

**Retorno:** `array|null — */`

---

##### `getConfig(int $tenantId, ?int $sectorId = null): array`

Obtém dados específicos.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$tenantId` | `int` | ID do tenant |
| `$sectorId` | `int|null` | Sector id |

**Retorno:** `array — */`

---

### Funções auxiliares do arquivo

#### `saveConfig(int $tenantId, array $data)`

---

#### `getMarginReport(int $tenantId, ?string $dateFrom = null, ?string $dateTo = null)`

---

#### `getMaterialCost(int $orderId, int $tenantId)`

---

#### `getLaborCost(int $orderId, int $tenantId)`

---

#### `getOverheadCost(int $tenantId, float $directCost)`

---

#### `getEstimatedCost(int $orderId, int $tenantId)`

---

#### `getProductionTimeMinutes(int $orderId, int $tenantId)`

---

## ReportExcelService

**Tipo:** Class  
**Arquivo:** `app/services/ReportExcelService.php`  
**Namespace:** `Akti\Services`  

Service: ReportExcelService

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$report` | Não |
| private | `$nfeReport` | Não |
| private | `$company` | Não |
| private | `$responsibleUser` | Não |

### Métodos

#### Métodos Public

##### `__construct(ReportModel $report, NfeReportModel $nfeReport, array $company, string $responsibleUser)`

Construtor da classe ReportExcelService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$report` | `ReportModel` | Report |
| `$nfeReport` | `NfeReportModel` | Nfe report |
| `$company` | `array` | Company |
| `$responsibleUser` | `string` | Responsible user |

---

##### `exportOrdersByPeriod(string $start, string $end): void`

Exporta dados.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$start` | `string` | Start |
| `$end` | `string` | End |

**Retorno:** `void — */`

---

### Funções auxiliares do arquivo

#### `exportRevenueByCustomer(string $start, string $end)`

---

#### `exportIncomeStatement(string $start, string $end)`

---

#### `exportOpenInstallments()`

---

#### `exportScheduledContacts(string $start, string $end)`

---

#### `exportProductCatalog()`

---

#### `exportStockByWarehouse()`

---

#### `exportStockMovements(string $start, string $end)`

---

#### `exportCommissionsReport(string $start, string $end, ?int $userId = null)`

---

#### `exportNfesByPeriod(string $start, string $end)`

---

#### `exportTaxSummary(string $start, string $end)`

---

#### `exportNfesByCustomer(string $start, string $end)`

---

#### `exportCfopSummary(string $start, string $end)`

---

#### `exportCancelledNfes(string $start, string $end)`

---

#### `exportInutilizacoes(string $start, string $end)`

---

#### `exportSefazLogs(string $start, string $end)`

---

#### `excelCompanyHeader($sheet, string $title, string $lastCol)`

---

#### `excelSummaryBlock($sheet, int $startRow, string $lastCol, array $metrics)`

---

#### `styleExcelHeader($sheet, string $range)`

---

#### `styleExcelDataRow($sheet, string $range, bool $alternate)`

---

#### `styleExcelTotalRow($sheet, string $range)`

---

#### `excelFooter($sheet, int $row, string $lastCol)`

---

#### `autoSizeColumns($sheet, array $cols)`

---

#### `sendExcel(Spreadsheet $spreadsheet, string $filename)`

---

## ReportPdfService

**Tipo:** Class  
**Arquivo:** `app/services/ReportPdfService.php`  
**Namespace:** `Akti\Services`  

Service: ReportPdfService

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$report` | Não |
| private | `$nfeReport` | Não |
| private | `$company` | Não |
| private | `$responsibleUser` | Não |

### Métodos

#### Métodos Public

##### `__construct(ReportModel $report, NfeReportModel $nfeReport, array $company, string $responsibleUser)`

Construtor da classe ReportPdfService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$report` | `ReportModel` | Report |
| `$nfeReport` | `NfeReportModel` | Nfe report |
| `$company` | `array` | Company |
| `$responsibleUser` | `string` | Responsible user |

---

##### `exportOrdersByPeriod(string $start, string $end): void`

Exporta dados.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$start` | `string` | Start |
| `$end` | `string` | End |

**Retorno:** `void — */`

---

##### `exportRevenueByCustomer(string $start, string $end): void`

Exporta dados.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$start` | `string` | Start |
| `$end` | `string` | End |

**Retorno:** `void — */`

---

##### `exportIncomeStatement(string $start, string $end): void`

Exporta dados.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$start` | `string` | Start |
| `$end` | `string` | End |

**Retorno:** `void — */`

---

##### `exportOpenInstallments(): void`

Exporta dados.

**Retorno:** `void — */`

---

##### `exportScheduledContacts(string $start, string $end): void`

Exporta dados.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$start` | `string` | Start |
| `$end` | `string` | End |

**Retorno:** `void — */`

---

##### `exportProductCatalog(): void`

Exporta dados.

**Retorno:** `void — */`

---

##### `exportStockByWarehouse(): void`

Exporta dados.

**Retorno:** `void — */`

---

##### `exportStockMovements(string $start, string $end): void`

Exporta dados.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$start` | `string` | Start |
| `$end` | `string` | End |

**Retorno:** `void — */`

---

##### `exportCommissionsReport(string $start, string $end, ?int $userId = null): void`

Exporta dados.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$start` | `string` | Start |
| `$end` | `string` | End |
| `$userId` | `int|null` | ID do usuário |

**Retorno:** `void — */`

---

##### `exportNfesByPeriod(string $start, string $end): void`

Exporta dados.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$start` | `string` | Start |
| `$end` | `string` | End |

**Retorno:** `void — */`

---

##### `exportTaxSummary(string $start, string $end): void`

Exporta dados.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$start` | `string` | Start |
| `$end` | `string` | End |

**Retorno:** `void — */`

---

##### `exportNfesByCustomer(string $start, string $end): void`

Exporta dados.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$start` | `string` | Start |
| `$end` | `string` | End |

**Retorno:** `void — */`

---

##### `exportCfopSummary(string $start, string $end): void`

Exporta dados.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$start` | `string` | Start |
| `$end` | `string` | End |

**Retorno:** `void — */`

---

##### `exportCancelledNfes(string $start, string $end): void`

Exporta dados.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$start` | `string` | Start |
| `$end` | `string` | End |

**Retorno:** `void — */`

---

##### `exportInutilizacoes(string $start, string $end): void`

Exporta dados.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$start` | `string` | Start |
| `$end` | `string` | End |

**Retorno:** `void — */`

---

##### `exportSefazLogs(string $start, string $end): void`

Exporta dados.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$start` | `string` | Start |
| `$end` | `string` | End |

**Retorno:** `void — */`

---

#### Métodos Private

##### `createPdf(string $title): TCPDF`

Cria instância TCPDF com cabeçalho profissional minimalista.

---

##### `pdfSummaryBox(TCPDF $pdf, array $metrics): void`

Desenha uma caixa de resumo executivo com métricas-chave.

---

##### `pdfTableHeader(TCPDF $pdf, array $headers, array $widths): void`

Desenha cabeçalho de tabela minimalista no PDF.

---

##### `pdfTableRow(TCPDF $pdf, array $widths, array $values, array $aligns, bool $alternate): void`

Desenha uma linha de dados na tabela do PDF com zebra striping sutil.

---

##### `pdfTotalRow(TCPDF $pdf, array $cells): void`

Desenha linha de totais com destaque visual.

---

##### `pdfFooter(TCPDF $pdf): void`

Desenha rodapé profissional no PDF.

---

##### `sendPdf(TCPDF $pdf, string $filename): void`

Envia o PDF diretamente para download.

---

## SettingsService

**Tipo:** Class  
**Arquivo:** `app/services/SettingsService.php`  
**Namespace:** `Akti\Services`  

SettingsService — Lógica de negócio para configurações do sistema.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |
| private | `$companySettings` | Não |
| private | `$logger` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db, CompanySettings $companySettings)`

Construtor da classe SettingsService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |
| `$companySettings` | `CompanySettings` | Company settings |

---

##### `saveCompanySettings(array $data): void`

Salva configurações da empresa a partir de um array de chave => valor.

---

##### `handleLogoUpload(array $file): bool`

Processa upload do logo da empresa.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$file` | `array` | Dados do $_FILES['company_logo'] |

**Retorno:** `bool — Se o upload foi bem-sucedido`

---

##### `removeLogo(): void`

Remove o logo da empresa.

---

##### `saveBankSettings(array $data): array`

Salva configurações bancárias/boleto.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$data` | `array` | Dados do formulário |

**Retorno:** `array — ['success' => bool, 'message' => string]`

---

##### `saveFiscalSettings(array $data): array`

Salva configurações fiscais da empresa.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$data` | `array` | Dados do formulário |

**Retorno:** `array — ['success' => bool, 'message' => string]`

---

##### `saveSecuritySettings(int $timeoutMinutes): int`

Salva configurações de segurança (timeout de sessão).

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$timeoutMinutes` | `int` | Timeout em minutos |

**Retorno:** `int — O timeout validado e salvo`

---

### Funções auxiliares do arquivo

#### `generateStepKey(string $label)`

---

#### `saveDashboardWidgets(int $groupId, string $widgetsJson)`

---

#### `resetDashboardWidgets(int $groupId)`

---

## SpedExportService

**Tipo:** Class  
**Arquivo:** `app/services/SpedExportService.php`  
**Namespace:** `Akti\Services`  

SpedExportService — Exportação para SPED Fiscal e Contábil.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$tenantId` | Não |

### Métodos

#### Métodos Public

##### `__construct(\PDO $db, int $tenantId)`

Construtor da classe SpedExportService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |
| `$tenantId` | `int` | ID do tenant |

---

##### `exportFinancialCsv(string $startDate, string $endDate): array`

Exporta dados financeiros no formato CSV contábil padrão.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$startDate` | `string` | Data inicial (Y-m-d) |
| `$endDate` | `string` | Data final (Y-m-d) |

**Retorno:** `array — ['filename' => string, 'content' => string, 'records' => int]`

---

##### `exportSpedTxt(string $startDate, string $endDate): array`

Exporta lançamentos contábeis no formato SPED simplificado (TXT).

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$startDate` | `string` | * @param string $endDate |

**Retorno:** `array — */`

---

##### `exportChartOfAccounts(): array`

Exporta plano de contas simplificado.

---

#### Métodos Private

##### `sanitizeCsvField(string $value): string`

Sanitiza campo para CSV (remove ; e quebras de linha).

---

## StockMovementService

**Tipo:** Class  
**Arquivo:** `app/services/StockMovementService.php`  
**Namespace:** `Akti\Services`  

StockMovementService — Lógica de negócio para movimentações de estoque.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |
| private | `$stockModel` | Não |
| private | `$logger` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db, Stock $stockModel, Logger $logger)`

Construtor da classe StockMovementService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |
| `$stockModel` | `Stock` | Stock model |
| `$logger` | `Logger` | Logger |

---

##### `processMovement(int $warehouseId,
        string $type,
        ?string $reason,
        array $items,
        int $destWarehouseId = 0): array`

Processar movimentação de estoque (múltiplos itens).

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$warehouseId` | `int` | * @param string $type 'entrada'|'saida'|'ajuste'|'transferencia' |
| `$reason` | `string|null` | * @param array $items |
| `$destWarehouseId` | `int` | (apenas para transferência) |

**Retorno:** `array — ['success' => bool, 'processed' => int, 'errors' => array, 'message' => string]`

---

### Funções auxiliares do arquivo

#### `updateMovement(int $id, array $data)`

---

#### `deleteMovement(int $id)`

---

## SupplyStockMovementService

**Tipo:** Class  
**Arquivo:** `app/services/SupplyStockMovementService.php`  
**Namespace:** `Akti\Services`  

Class SupplyStockMovementService.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |
| private | `$stockModel` | Não |
| private | `$supplyModel` | Não |
| private | `$logger` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db, SupplyStock $stockModel, Supply $supplyModel, Logger $logger)`

Construtor da classe SupplyStockMovementService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |
| `$stockModel` | `SupplyStock` | Stock model |
| `$supplyModel` | `Supply` | Supply model |
| `$logger` | `Logger` | Logger |

---

##### `processEntry(int $warehouseId,
        ?string $reason,
        array $items,
        int $createdBy): array`

Processa entrada de insumos no estoque com suporte a lote/validade e CMP.

---

### Funções auxiliares do arquivo

#### `processExit(int $warehouseId,
        ?string $reason,
        array $items,
        int $createdBy,
        ?string $referenceType = null,
        ?int $referenceId = null)`

---

#### `processAdjustment(int $warehouseId,
        ?string $reason,
        array $items,
        int $createdBy)`

---

#### `processTransfer(int $originWarehouseId,
        int $destWarehouseId,
        ?string $reason,
        array $items,
        int $createdBy)`

---

#### `validateSufficientStock(int $warehouseId, int $supplyId, float $quantity)`

---

#### `calculateWeightedAverageCost(float $currentStock, float $currentCost, float $newQty, float $newPrice)`

---

#### `checkReorderAlerts()`

---

#### `suggestBatchForExit(int $supplyId, int $warehouseId)`

---

## TaxCalculator

**Tipo:** Class  
**Arquivo:** `app/services/TaxCalculator.php`  
**Namespace:** `Akti\Services`  

TaxCalculator — Cálculo dinâmico de impostos para NF-e.

### Métodos

#### Métodos Public

##### `calculateItem(array $product, array $operation, int $crt, string $ufOrig, string $ufDest): array`

Calcula todos os impostos de um item para NF-e.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$product` | `array` | Dados do produto (com fiscal_* fields) |
| `$operation` | `array` | Dados da operação (tipo, UF orig/dest) |
| `$crt` | `int` | CRT do emitente (1, 2 ou 3) |
| `$ufOrig` | `string` | UF do emitente |
| `$ufDest` | `string` | UF do destinatário |

**Retorno:** `array — Impostos calculados por item`

---

##### `calculateICMS(array $product, int $crt, string $ufOrig, string $ufDest, float $baseCalculo): array`

Calcula ICMS conforme CRT e dados do produto.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$product` | `array` | Dados do produto |
| `$crt` | `int` | CRT do emitente |
| `$ufOrig` | `string` | UF do emitente |
| `$ufDest` | `string` | UF do destinatário |
| `$baseCalculo` | `float` | Valor base para cálculo |

**Retorno:** `array — ['type'=>'ICMSSN'|'ICMS', 'orig'=>int, 'cst'|'csosn'=>string, 'vBC'=>float, 'pICMS'=>float, 'vICMS'=>float, 'pRedBC'=>float]`

---

##### `calculatePIS(array $product, int $crt, float $baseCalculo): array`

Calcula PIS.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$product` | `array` | Dados do produto |
| `$crt` | `int` | CRT do emitente |
| `$baseCalculo` | `float` | Base de cálculo |

**Retorno:** `array — ['CST'=>string, 'vBC'=>float, 'pPIS'=>float, 'valor'=>float]`

---

##### `calculateCOFINS(array $product, int $crt, float $baseCalculo): array`

Calcula COFINS.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$product` | `array` | Dados do produto |
| `$crt` | `int` | CRT do emitente |
| `$baseCalculo` | `float` | Base de cálculo |

**Retorno:** `array — ['CST'=>string, 'vBC'=>float, 'pCOFINS'=>float, 'valor'=>float]`

---

##### `calculateIPI(array $product, int $crt, float $baseCalculo): array`

Calcula IPI (Imposto sobre Produtos Industrializados).

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$product` | `array` | Dados do produto |
| `$crt` | `int` | CRT do emitente |
| `$baseCalculo` | `float` | Base de cálculo |

**Retorno:** `array — ['CST'=>string, 'vBC'=>float, 'pIPI'=>float, 'valor'=>float]`

---

##### `calculateDIFAL(array $product, string $ufOrig, string $ufDest, float $baseCalculo): array`

Calcula DIFAL — Diferencial de Alíquota Interestadual.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$product` | `array` | Dados do produto |
| `$ufOrig` | `string` | UF do emitente |
| `$ufDest` | `string` | UF do destinatário |
| `$baseCalculo` | `float` | Base de cálculo |

**Retorno:** `array — ['vBCUFDest'=>float, 'pFCPUFDest'=>float, 'pICMSUFDest'=>float, 'pICMSInter'=>float, 'vFCPUFDest'=>float, 'vICMSUFDest'=>float, 'vICMSUFRemet'=>float]`

---

##### `calculateTotal(array $items): array`

Totaliza impostos de todos os itens.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$items` | `array` | Array de resultados de calculateItem() |

**Retorno:** `array — Totais de cada tributo`

---

##### `getAliquotaInterestadual(string $ufOrig, string $ufDest): float`

Retorna alíquota interestadual de ICMS.

---

##### `static calculateIdDest(string $ufEmitente, string $ufDestinatario): int`

Calcula o idDest (indicador de destino) dinamicamente.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$ufEmitente` | `string` | UF do emitente |
| `$ufDestinatario` | `string` | UF do destinatário |

**Retorno:** `int — 1=interna, 2=interestadual, 3=exterior`

---

##### `static determineCFOP(array $product, string $ufOrig, string $ufDest): string`

Determina o CFOP correto com base na operação e UFs.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$product` | `array` | Dados do produto (com fiscal_cfop, fiscal_cfop_interestadual) |
| `$ufOrig` | `string` | UF do emitente |
| `$ufDest` | `string` | UF do destinatário |

**Retorno:** `string — CFOP`

---

##### `static validateNCM(?string $ncm): bool`

Valida NCM — 8 dígitos numéricos.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$ncm` | `string|null` | * @return bool |

**Retorno:** `bool — */`

---

##### `static validateCFOP(?string $cfop): bool`

Valida CFOP — 4 dígitos numéricos, iniciando com 1-7.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$cfop` | `string|null` | * @return bool |

**Retorno:** `bool — */`

---

##### `static mapModFrete(?string $shippingType, float $shippingCost = 0): int`

Mapeia modFrete do pedido para código NF-e.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$shippingType` | `string|null` | Tipo de frete do pedido |
| `$shippingCost` | `float` | Valor do frete |

**Retorno:** `int — Código modFrete NF-e`

---

##### `static mapIndPres(?string $saleType): int`

Mapeia indPres (indicador de presença) da venda.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$saleType` | `string|null` | Tipo de venda do pedido |

**Retorno:** `int — Código indPres NF-e`

---

#### Métodos Private

##### `calculateICMSSN(string $csosn, int $origem, float $baseCalculo, array $product): array`

Calcula ICMS para Simples Nacional (CSOSN).

---

##### `calculateICMSNormal(string $cst, int $origem, float $baseCalculo, float $aliquota, float $reducaoBC): array`

Calcula ICMS para Regime Normal (CST).

---

##### `calculatePISCOFINS(string $cst, float $aliquota, float $baseCalculo, string $tipo): array`

Cálculo genérico de PIS/COFINS (mesma lógica, CSTs idênticos).

---

## ThumbnailService

**Tipo:** Class  
**Arquivo:** `app/services/ThumbnailService.php`  
**Namespace:** `Akti\Services`  

ThumbnailService — Geração e gestão de thumbnails para imagens.

### Métodos

#### Métodos Public

##### `generate(string $sourcePath, int $width, ?int $height = null, string $mode = 'cover'): ?string`

Gerar thumbnail de uma imagem.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$sourcePath` | `string` | Caminho relativo da imagem original |
| `$width` | `int` | Largura desejada |
| `$height` | `int|null` | Altura (null = proporcional) |
| `$mode` | `string` | Modo: 'cover' (preenche/corta), 'contain' (proporcional) |

**Retorno:** `string|null — Caminho do thumbnail gerado, ou null se falhou`

---

##### `getOrCreate(string $sourcePath, int $width, ?int $height = null): ?string`

Obter thumbnail existente ou criar um novo.

---

##### `deleteThumbnails(string $sourcePath): void`

Deletar todos os thumbnails de uma imagem.

---

##### `isGdAvailable(): bool`

Verificar se GD está disponível.

---

#### Métodos Private

##### `buildThumbPath(string $sourcePath, int $width, ?int $height): string`

Construir caminho do thumbnail.

---

### Funções auxiliares do arquivo

#### `resolveFullPath(string $path)`

---

#### `loadImage(string $fullPath)`

---

#### `saveImage(\GdImage $image, string $fullPath, string $ext)`

---

#### `calculateCoverDimensions(int $srcW, int $srcH, int $dstW, int $dstH)`

---

#### `calculateContainDimensions(int $srcW, int $srcH, int $dstW, int $dstH)`

---

## TransactionService

**Tipo:** Class  
**Arquivo:** `app/services/TransactionService.php`  
**Namespace:** `Akti\Services`  

TransactionService — Camada de Serviço para Transações Financeiras.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$financial` | Não |
| private | `$db` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db, Financial $financial)`

Construtor da classe TransactionService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |
| `$financial` | `Financial` | Financial |

---

##### `addTransaction(array $data): bool`

Adiciona uma transação financeira.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$data` | `array` | * @return bool |

**Retorno:** `bool — */`

---

##### `getById(int $id)`

Busca uma transação pelo ID.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `int` | * @return array|false |

**Retorno:** `array|false — */`

---

##### `update(int $id, array $data): bool`

Atualiza uma transação existente.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `int` | * @param array $data |

**Retorno:** `bool — */`

---

##### `delete(int $id, ?string $reason = null): array`

Deleta transação (soft-delete). Captura dados anteriores para auditoria.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$id` | `int` | * @param string|null $reason Motivo da exclusão (informado pelo usuário) |

**Retorno:** `array — ['success' => bool, 'old_data' => array|null]`

---

##### `getPaginated(array $filters = [], int $page = 1, int $perPage = 25): array`

Lista transações com paginação e filtros.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$filters` | `array` | * @param int $page |
| `$perPage` | `int` | * @return array |

**Retorno:** `array — */`

---

##### `registerReversal(array $parcelaAntes, ?int $userId = null): bool`

Registra estorno na tabela de transações (chamado pelo InstallmentService).

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$parcelaAntes` | `array` | Dados da parcela antes do cancelamento |
| `$userId` | `int|null` | * @return bool |

**Retorno:** `bool — */`

---

### Funções auxiliares do arquivo

#### `registerInstallmentPayment(int $orderId, int $installmentId, array $data, bool $isConfirmed)`

---

#### `getCategories()`

---

## TwigRenderer

**Tipo:** Class  
**Arquivo:** `app/services/TwigRenderer.php`  
**Namespace:** `Akti\Services`  

Serviço de renderização Twig para a Loja.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$twig` | Não |

### Métodos

#### Métodos Public

##### `__construct(string $basePath)`

Construtor da classe TwigRenderer.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$basePath` | `string` | Caminho base da aplicação |

---

##### `render(string $template, array $context = []): string`

Renderiza um template Twig e retorna o HTML.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$template` | `string` | Caminho do template (ex: 'pages/home.html.twig') |
| `$context` | `array` | Variáveis disponíveis no template |

---

##### `getEnvironment(): Environment`

Retorna a instância Twig para extensões customizadas.

---

## WhatsAppService

**Tipo:** Class  
**Arquivo:** `app/services/WhatsAppService.php`  
**Namespace:** `Akti\Services`  

Class WhatsAppService.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$model` | Não |
| private | `$tenantId` | Não |

### Métodos

#### Métodos Public

##### `__construct(WhatsAppMessage $model, int $tenantId)`

Construtor da classe WhatsAppService.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$model` | `WhatsAppMessage` | Model |
| `$tenantId` | `int` | ID do tenant |

---

##### `isConfigured(): bool`

Verifica uma condição booleana.

**Retorno:** `bool — */`

---

##### `send(string $phone, string $message, ?int $customerId = null, ?int $templateId = null): array`

Envia dados ou notificação.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$phone` | `string` | Phone |
| `$message` | `string` | Mensagem |
| `$customerId` | `int|null` | ID do cliente |
| `$templateId` | `int|null` | Template id |

**Retorno:** `array — */`

---

##### `sendFromTemplate(string $eventType, string $phone, array $variables, ?int $customerId = null): array`

Envia dados ou notificação.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$eventType` | `string` | Event type |
| `$phone` | `string` | Phone |
| `$variables` | `array` | Variables |
| `$customerId` | `int|null` | ID do cliente |

**Retorno:** `array — */`

---

### Funções auxiliares do arquivo

#### `sendViaProvider(string $phone, string $message)`

---

#### `sendEvolutionApi(string $phone, string $message)`

---

#### `sendZApi(string $phone, string $message)`

---

#### `sendMetaCloud(string $phone, string $message)`

---

#### `httpPost(string $url, string $payload, array $headers)`

---

## WorkflowEngine

**Tipo:** Class  
**Arquivo:** `app/services/WorkflowEngine.php`  
**Namespace:** `Akti\Services`  

WorkflowEngine — Evaluates and executes workflow rules on events.

### Métodos

#### Métodos Public

##### `__construct(\PDO $db)`

Construtor da classe WorkflowEngine.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `\PDO` | Conexão PDO com o banco de dados |

---

##### `process(string $event, array $payload): void`

Process an event: find matching rules, evaluate conditions, execute actions.

---

#### Métodos Private

##### `evaluateConditions(array $conditions, array $payload): bool`

Evaluate all conditions against the event payload.

---

##### `executeActions(array $actions, array $payload): void`

Execute actions (notification, email, field update, etc.).

---

##### `actionNotify(array $action, array $payload): void`

Action notify.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$action` | `array` | Action |
| `$payload` | `array` | Payload |

**Retorno:** `void — */`

---

##### `actionEmail(array $action, array $payload): void`

Action email.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$action` | `array` | Action |
| `$payload` | `array` | Payload |

**Retorno:** `void — */`

---

##### `actionUpdateField(array $action, array $payload): void`

Action update field.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$action` | `array` | Action |
| `$payload` | `array` | Payload |

**Retorno:** `void — */`

---

### Funções auxiliares do arquivo

#### `actionLog(array $action, array $payload)`

---

#### `interpolate(string $template, array $payload)`

---

#### `($payload)`

---

