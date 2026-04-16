# Referência Rápida — Akti ERP/CRM

> Lista compacta de todas as classes e seus métodos públicos.

**Gerado em:** 16/04/2026 12:41:01

---

## Core (Núcleo)

### `Akti\Core\Application`

_Application — encapsula o ciclo de vida da requisição HTTP._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe Application. |
| `boot()` | `void` | Boot — inicializa security headers, sessão, tenant, router. |
| `handle()` | `bool` | Handle — resolve autenticação, permissões e CSRF. |
| `dispatch()` | `void` | Dispatch — despacha a rota autenticada. |

### `Akti\Core\Cache`

_Sistema de cache em arquivo com suporte a TTL e invalidação._

| Método | Retorno | Descrição |
|---|---|---|
| `remember()` | `mixed` | Retrieve a cached value, or compute and store it if missing/expired. |
| `get()` | `mixed` | Get. |
| `set()` | `void` | Set. |
| `forget()` | `void` | Forget. |
| `forgetByPrefix()` | `void` | Invalidate all cache entries matching a prefix. |
| `flush()` | `void` | Descarrega dados pendentes. |

### `Akti\Core\Container`

_Container de injeção de dependências compatível com PSR-11._

| Método | Retorno | Descrição |
|---|---|---|
| `bind()` | `void` | Registra um binding no container. |
| `singleton()` | `void` | Registra um binding como singleton. |
| `instance()` | `void` | Registra uma instância já pronta. |
| `get()` | `mixed` |  |

### `Akti\Core\Event`

_Event — Value Object para dados de eventos do sistema._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` |  |
| `getData()` | `array` | Retorna os dados do evento. |

### `Akti\Core\EventDispatcher`

_EventDispatcher — Sistema de eventos nativo do Akti._

| Método | Retorno | Descrição |
|---|---|---|
| `listen()` | `void` | Registra um listener para um evento nomeado. |
| `dispatch()` | `void` | Dispara um evento para todos os listeners registrados. |
| `forget()` | `void` | Remove todos os listeners de um evento específico. |
| `getRegistered()` | `array` | Retorna todos os eventos registrados com seus listeners. |

### `Akti\Core\Log`

_Log — Structured Logging (PSR-3 inspired)_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` |  |
| `channel()` | `self` | Cria uma instância de Log com um canal específico. |
| `emergency()` | `void` | Emergency. |
| `alert()` | `void` | Alert. |
| `critical()` | `void` | Critical. |
| `error()` | `void` | Error. |
| `warning()` | `void` | Warning. |
| `notice()` | `void` | Notice. |
| `info()` | `void` | Info. |
| `debug()` | `void` | Debug. |
| `log()` | `void` | Grava um log estruturado em JSON. |

### `Akti\Core\ModuleBootloader`

_Bootloader central de módulos por tenant._

| Método | Retorno | Descrição |
|---|---|---|
| `isModuleEnabled()` | `bool` | Verifica uma condição booleana. |
| `canAccessPage()` | `bool` | Verifica permissão ou capacidade. |
| `canAccessSettingsTab()` | `bool` | Verifica permissão ou capacidade. |
| `sanitizeSettingsTab()` | `string` | Sanitiza dados de entrada. |
| `getModuleLabel()` | `string` | Retorna o label amigável do módulo. |
| `getDisabledModuleJS()` | `string` | Retorna JavaScript inline para exibir um SweetAlert2 de módulo desabilitado. |

### `Akti\Core\Router`

_Router baseado em mapa de rotas — Akti_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` |  |

### `Akti\Core\Security`

_Security — Proteção CSRF centralizada para o sistema Akti._

| Método | Retorno | Descrição |
|---|---|---|
| `generateCsrfToken()` | `string` | Gera (ou reutiliza) um token CSRF criptograficamente seguro. |
| `getToken()` | `?string` | Retorna o token CSRF atual da sessão (sem gerar novo). |
| `validateCsrfToken()` | `bool` | Valida o token CSRF recebido contra o token da sessão. |
| `logCsrfFailure()` | `void` | Registra uma falha de validação CSRF no log de segurança. |

### `Akti\Core\TransactionManager`

_Gerenciador de transações de banco de dados com suporte a savepoints._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe TransactionManager. |
| `begin()` | `void` | Begin. |

## Bootstrap (Inicialização)

## Config (Configurações)

### `Database`

_Database — Singleton com cache por tenant (DSN)._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe Database. |
| `getInstance()` | `PDO` | Retorna uma conexão PDO via singleton (cached por DSN). |
| `getConnection()` | `PDO` | Wrapper de compatibilidade — retorna conexão PDO via singleton. |
| `resetInstances()` | `void` | Remove todas as instâncias em cache. Útil para testes unitários. |
| `resetInstance()` | `void` | Remove a instância de um DSN específico. |
| `getMasterInstance()` | `PDO` | Retorna uma conexão PDO para o banco master (akti_master). |
| `getMasterCredentials()` | `array` | Retorna as credenciais do banco master para uso em operações cross-DB. |
| `connectTo()` | `PDO` | Cria uma conexão PDO avulsa (não cached) para um banco de dados específico. |

### `SessionGuard`

_Classe auxiliar para controle de sessão (timeout por inatividade)._

| Método | Retorno | Descrição |
|---|---|---|
| `checkInactivity()` | `void` | Verifica se a sessão expirou por inatividade. |
| `touch()` | `void` | Atualiza o timestamp de última atividade. |
| `getTimeoutMinutes()` | `int` | Obtém o timeout configurado pelo admin em company_settings. |
| `getJsSessionData()` | `array` | Retorna dados necessários para o JavaScript do modal de aviso de expiração. |

### `Akti\Config\TenantManager`

_Class TenantManager._

| Método | Retorno | Descrição |
|---|---|---|
| `bootstrap()` | `void` | Inicializa o componente. |
| `getTenantConfig()` | `array` | Obtém dados específicos. |
| `getMasterConfig()` | `array` | Obtém dados específicos. |
| `getTenantUploadBase()` | `string` | Returns the base upload directory for the current tenant. |
| `getTenantLimit()` | `?int` | Obtém dados específicos. |
| `enforceTenantSession()` | `void` | Enforce tenant session. |

## Utils (Utilitários)

### `Akti\Utils\CurrencyFormatter`

_CurrencyFormatter — Format monetary values for different locales/currencies._

| Método | Retorno | Descrição |
|---|---|---|
| `format()` | `string` | Format a value in the given currency. |
| `parse()` | `float` | Parse a locale-formatted string back to a float. |
| `getAvailable()` | `array` | Get available currencies list. |

### `Akti\Utils\CursorPaginator`

_CursorPaginator — Paginação baseada em cursor para large datasets._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe CursorPaginator. |
| `paginate()` | `array` | Executa paginação cursor-based. |

### `Akti\Utils\Escape`

_Utilitários de escape para prevenção de XSS._

| Método | Retorno | Descrição |
|---|---|---|
| `html()` | `string` | Escape para contexto HTML (conteúdo de tags). |
| `attr()` | `string` | Escape para contexto de atributo HTML. |
| `js()` | `string` | Escape para contexto JavaScript (inline scripts). |
| `url()` | `string` | Escape para contexto de URL (query string). |
| `css()` | `string` | Escape para contexto CSS (valores inline). |
| `number()` | `string` | Formata número para exibição (locale BR). |

### `Akti\Utils\Input`

_Classe para captura e sanitização de inputs do usuário._

| Método | Retorno | Descrição |
|---|---|---|
| `post()` | `mixed` | Obtém valor de $_POST com sanitização. |
| `get()` | `mixed` | Obtém valor de $_GET com sanitização. |
| `request()` | `mixed` | Obtém valor de $_REQUEST com sanitização. |
| `hasPost()` | `bool` | Verifica se um campo existe em $_POST (e não está vazio). |
| `hasGet()` | `bool` | Verifica se um campo existe em $_GET (e não está vazio). |
| `allPost()` | `array` | Obtém múltiplos campos de $_POST com sanitização. |
| `allGet()` | `array` | Obtém múltiplos campos de $_GET com sanitização. |
| `postRaw()` | `mixed` | Obtém um valor raw de $_POST sem sanitização (usar com cautela). |
| `getRaw()` | `mixed` | Obtém um valor raw de $_GET sem sanitização. |
| `postArray()` | `array` | Obtém um array de $_POST (ex: grades[], items[]). |
| `getArray()` | `array` | Obtém um array de $_GET. |

### `Akti\Utils\JwtHelper`

_JWT Helper — Gera tokens JWT (HMAC-SHA256) compatíveis com jsonwebtoken do Node.js._

| Método | Retorno | Descrição |
|---|---|---|
| `encode()` | `string` | Gera um JWT com payload personalizado. |

### `Akti\Utils\SafeHtml`

_Classe para geração segura de HTML._

| Método | Retorno | Descrição |
|---|---|---|
| `sanitizeFragment()` | `string` | Sanitiza dados de entrada. |

### `Akti\Utils\Sanitizer`

_Sanitizador de dados de entrada._

| Método | Retorno | Descrição |
|---|---|---|
| `string()` | `string` | Sanitiza string genérica: trim + strip_tags. |
| `richText()` | `string` | Sanitiza string preservando algumas tags HTML permitidas. |
| `int()` | `?int` | Sanitiza e converte para inteiro. |
| `float()` | `?float` | Sanitiza e converte para float, aceitando formato PT-BR ("1.234,56"). |
| `bool()` | `bool` | Sanitiza valor booleano. |
| `email()` | `string` | Sanitiza e-mail: trim + lowercase + filter. |
| `phone()` | `string` | Sanitiza telefone: remove tudo exceto dígitos, +, ( e ). |
| `document()` | `string` | Sanitiza CPF/CNPJ: remove tudo exceto dígitos. |
| `cep()` | `string` | Sanitiza CEP: remove tudo exceto dígitos. |
| `url()` | `string` | Sanitiza URL. |
| `date()` | `?string` | Sanitiza data no formato Y-m-d. |
| `datetime()` | `?string` | Sanitiza datetime no formato Y-m-d H:i:s. |
| `slug()` | `string` | Sanitiza slug: lowercase, sem acentos, apenas a-z 0-9 e hífens. |
| `intArray()` | `array` | Sanitiza um array de inteiros. |
| `stringArray()` | `array` | Sanitiza um array de strings. |
| `enum()` | `mixed` | Valida se o valor está em uma lista de opções permitidas. |
| `filename()` | `string` | Sanitiza um nome de arquivo: remove caracteres perigosos, preserva extensão. |
| `json()` | `?string` | Sanitiza JSON string: decode + re-encode para garantir formato válido. |

### `Akti\Utils\SimpleCache`

_SimpleCache — Cache em sessão para dados frequentes_

| Método | Retorno | Descrição |
|---|---|---|
| `remember()` | `mixed` | Busca um valor no cache; se não existir ou expirado, executa o loader |
| `get()` | `mixed` | Retorna dados do cache sem executar loader. |
| `set()` | `void` | Armazena um valor diretamente no cache. |
| `forget()` | `void` | Invalida uma chave específica do cache. |
| `forgetByPrefix()` | `int` | Invalida todas as chaves que começam com um prefixo. |
| `flush()` | `void` | Limpa todo o cache da sessão. |
| `has()` | `bool` | Verifica se uma chave existe e não está expirada. |
| `stats()` | `array` | Retorna estatísticas do cache (para debug). |

### `Akti\Utils\Validator`

_Validador de dados com regras configuráveis._

| Método | Retorno | Descrição |
|---|---|---|
| `fails()` | `bool` | Verifica se existem erros acumulados. |
| `passes()` | `bool` | Retorna verdadeiro se não houver erros. |
| `errors()` | `array` | Retorna todos os erros: ['campo' => 'mensagem', ...] |
| `error()` | `?string` | Retorna o primeiro erro de um campo específico (ou null). |
| `addError()` | `self` | Adiciona um erro manualmente. |
| `reset()` | `self` | Limpa todos os erros acumulados. |
| `required()` | `self` | Campo obrigatório (não vazio). |

### `Akti\Utils\ViteAssets`

_Vite asset helper — reads the manifest produced by `npm run build`_

| Método | Retorno | Descrição |
|---|---|---|
| `isBuilt()` | `bool` | Verifica uma condição booleana. |
| `css()` | `?string` | Css. |
| `js()` | `?string` | Js. |
| `tag()` | `string` | Tag. |

### `AktiEnvRegistry`

_Lightweight .env file loader for Akti._

| Método | Retorno | Descrição |
|---|---|---|
| `set()` | `void` | Set. |
| `get()` | `mixed` |  |
| `isLoaded()` | `bool` | Verifica uma condição booleana. |

## Middleware

### `Akti\Middleware\CsrfMiddleware`

_CsrfMiddleware — Intercepta requisições que alteram dados e valida token CSRF._

| Método | Retorno | Descrição |
|---|---|---|
| `handle()` | `void` | Verifica se a requisição atual precisa de validação CSRF e, se sim, valida. |

### `Akti\Middleware\PortalAuthMiddleware`

_PortalAuthMiddleware — Verificação de autenticação do Portal do Cliente._

| Método | Retorno | Descrição |
|---|---|---|
| `check()` | `void` | Verifica se o cliente está autenticado no portal. |
| `isAuthenticated()` | `bool` | Verifica se o cliente está autenticado (sem redirecionar). |
| `getCustomerId()` | `?int` | Retorna o customer_id da sessão do portal. |
| `getAccessId()` | `?int` | Retorna o access_id da sessão do portal. |
| `getLang()` | `string` | Retorna o idioma do cliente na sessão. |
| `login()` | `void` | Inicia a sessão do portal para o cliente. |
| `logout()` | `void` | Encerra a sessão do portal (sem destruir a sessão admin se existir). |
| `touch()` | `void` | Atualiza o timestamp de última atividade. |
| `checkInactivity()` | `void` | Verifica inatividade do portal (timeout configurável, padrão 60min). |
| `getClientIp()` | `string` | Retorna o IP real do cliente. |
| `is2faPending()` | `bool` | Verifica se o 2FA está pendente de verificação. |
| `set2faPending()` | `void` | Marca 2FA como pendente. |
| `set2faVerified()` | `void` | Marca 2FA como verificado. |

### `Akti\Middleware\RateLimitMiddleware`

_RateLimitMiddleware — Proteção contra burst de ações._

| Método | Retorno | Descrição |
|---|---|---|
| `check()` | `array` | Verifica rate limit usando sessão (rápido, sem DB). |

### `Akti\Middleware\SecurityHeadersMiddleware`

_SecurityHeadersMiddleware_

| Método | Retorno | Descrição |
|---|---|---|
| `getNonce()` | `string` | Retorna o nonce CSP do request atual. Gera um novo se não existir. |
| `handle()` | `void` | Aplica todos os headers de segurança. |

### `Akti\Middleware\SentryMiddleware`

_SentryMiddleware — Error Tracking Integration_

| Método | Retorno | Descrição |
|---|---|---|
| `init()` | `void` | Inicializa o middleware de captura de erros. |
| `handleException()` | `void` | Handler global de exceções não capturadas. |
| `handleError()` | `bool` | Handler global de erros PHP. |

## Gateways (Pagamento)

### `Akti\Gateways\AbstractGateway`

| Método | Retorno | Descrição |
|---|---|---|
| `setCredentials()` | `void` | {@inheritDoc} |
| `setSettings()` | `void` | {@inheritDoc} |
| `setEnvironment()` | `void` | {@inheritDoc} |

### `Akti\Gateways\Contracts\PaymentGatewayInterface`

_PaymentGatewayInterface — Contrato para todos os gateways de pagamento._

| Método | Retorno | Descrição |
|---|---|---|
| `getSlug()` | `string` | Retorna o slug único do gateway (ex: 'mercadopago', 'stripe'). |
| `getDisplayName()` | `string` | Retorna o nome amigável para exibição na UI. |
| `supports()` | `bool` | Verifica se o gateway suporta um determinado método de pagamento. |
| `getSupportedMethods()` | `array` | Retorna lista de métodos suportados pelo gateway. |
| `createCharge()` | `array` | Cria uma cobrança no gateway externo. |
| `getChargeStatus()` | `array` | Consulta o status de uma cobrança pelo ID externo. |
| `refund()` | `array` | Solicita estorno total ou parcial de uma cobrança. |
| `validateWebhookSignature()` | `bool` | Valida a assinatura do webhook recebido. |
| `parseWebhookPayload()` | `array` | Interpreta o payload do webhook e retorna dados padronizados. |
| `setCredentials()` | `void` | Define as credenciais do gateway. |
| `setSettings()` | `void` | Define configurações extras (ex: pix_enabled, boleto_days, etc.). |
| `setEnvironment()` | `void` | Define o ambiente (sandbox ou production). |
| `getCredentialFields()` | `array` | Retorna a lista de campos de credencial exigidos por este gateway. |
| `getSettingsFields()` | `array` | Retorna a lista de campos de configuração extras. |
| `testConnection()` | `array` | Testa se as credenciais configuradas são válidas (ping na API). |

### `Akti\Gateways\GatewayManager`

_GatewayManager — Strategy Pattern resolver para gateways de pagamento._

| Método | Retorno | Descrição |
|---|---|---|
| `make()` | `PaymentGatewayInterface` | Resolve e retorna um gateway pelo slug. |

### `Akti\Gateways\Providers\MercadoPagoGateway`

_MercadoPagoGateway — Integração com a API do Mercado Pago._

| Método | Retorno | Descrição |
|---|---|---|
| `getSlug()` | `string` | {@inheritDoc} |
| `getDisplayName()` | `string` | {@inheritDoc} |
| `supports()` | `bool` | {@inheritDoc} |
| `getSupportedMethods()` | `array` | {@inheritDoc} |
| `getCredentialFields()` | `array` | {@inheritDoc} |
| `getSettingsFields()` | `array` | {@inheritDoc} |
| `createCharge()` | `array` | {@inheritDoc} |

### `Akti\Gateways\Providers\PagSeguroGateway`

_PagSeguroGateway — Integração com a API do PagSeguro (PagBank)._

| Método | Retorno | Descrição |
|---|---|---|
| `getSlug()` | `string` | {@inheritDoc} |
| `getDisplayName()` | `string` | {@inheritDoc} |
| `supports()` | `bool` | {@inheritDoc} |
| `getSupportedMethods()` | `array` | {@inheritDoc} |
| `getCredentialFields()` | `array` | {@inheritDoc} |
| `getSettingsFields()` | `array` | {@inheritDoc} |
| `createCharge()` | `array` | {@inheritDoc} |

### `Akti\Gateways\Providers\StripeGateway`

_StripeGateway — Integração com a API do Stripe._

| Método | Retorno | Descrição |
|---|---|---|
| `getSlug()` | `string` | {@inheritDoc} |
| `getDisplayName()` | `string` | {@inheritDoc} |
| `supports()` | `bool` | {@inheritDoc} |
| `getSupportedMethods()` | `array` | {@inheritDoc} |
| `getCredentialFields()` | `array` | {@inheritDoc} |
| `getSettingsFields()` | `array` | {@inheritDoc} |
| `createCharge()` | `array` | {@inheritDoc} |
| `getChargeStatus()` | `array` | {@inheritDoc} |

## Models (Modelos)

### `Akti\Models\Achievement`

_Model de conquistas/gamificação do sistema._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe Achievement. |
| `create()` | `int` | Cria um novo registro no banco de dados. |
| `readAll()` | `array` | Retorna todos os registros. |
| `readOne()` | `?array` | Retorna um registro pelo ID. |
| `update()` | `bool` | Atualiza um registro existente. |
| `delete()` | `bool` | Remove um registro pelo ID. |
| `awardAchievement()` | `bool` | Award achievement. |
| `addPoints()` | `void` | Add points. |
| `getLeaderboard()` | `array` | Obtém dados específicos. |
| `getUserAchievements()` | `array` | Obtém dados específicos. |
| `getUserScore()` | `array` | Obtém dados específicos. |

### `Akti\Models\Attachment`

_Model de anexos/arquivos vinculados a registros._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe Attachment. |
| `readByEntity()` | `array` | Read by entity. |
| `readOne()` | `?array` | Retorna um registro pelo ID. |
| `create()` | `int` | Cria um novo registro no banco de dados. |
| `delete()` | `bool` | Remove um registro pelo ID. |
| `countByEntity()` | `int` | Conta registros com critérios opcionais. |
| `readPaginated()` | `array` | Read paginated. |

### `Akti\Models\AuditLog`

_Model de log de auditoria para rastreamento de alterações._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe AuditLog. |
| `log()` | `int` | Registra informação no log. |
| `readPaginated()` | `array` | Read paginated. |

### `Akti\Models\Branch`

_Model de filiais/unidades da empresa._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe Branch. |
| `create()` | `int` | Cria um novo registro no banco de dados. |
| `readAll()` | `array` | Retorna todos os registros. |
| `readOne()` | `?array` | Retorna um registro pelo ID. |
| `update()` | `bool` | Atualiza um registro existente. |
| `delete()` | `bool` | Remove um registro pelo ID. |

### `Akti\Models\CalendarEvent`

_Model de eventos do calendário._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe CalendarEvent. |
| `readByRange()` | `array` | Read by range. |

### `Akti\Models\CatalogLink`

_Model: CatalogLink_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor do model CatalogLink |
| `create()` | `mixed` | Cria um novo link de catálogo para um pedido |

### `Akti\Models\Category`

_Model: Category_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor do model Category |
| `readAll()` | `mixed` | Retorna todas as categorias ordenadas por nome |
| `countAll()` | `int` | Retorna a quantidade total de categorias. |
| `readPaginated()` | `array` | Retorna categorias paginadas. |

### `Akti\Models\CategoryGrade`

_Model: CategoryGrade_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe CategoryGrade. |
| `getCategoryGrades()` | `mixed` | Retorna todas as grades de uma categoria (com info de tipo) |
| `getCategoryGradesWithValues()` | `mixed` | Retorna todas as grades de uma categoria com seus valores |
| `getCategoryGradeValues()` | `mixed` | Retorna todos os valores de uma grade de categoria |
| `addGradeToCategory()` | `mixed` | Adiciona uma grade a uma categoria |
| `addCategoryGradeValue()` | `mixed` | Adiciona um valor a uma grade de categoria |
| `saveCategoryGrades()` | `mixed` | Salva todas as grades e valores de uma categoria a partir de dados de formulário |
| `generateCategoryCombinations()` | `mixed` | Gera conteúdo ou dados. |
| `getCategoryCombinations()` | `mixed` | Retorna todas as combinações de grades de uma categoria |
| `toggleCategoryCombination()` | `mixed` | Ativa ou desativa uma combinação de grades de categoria |
| `categoryHasGrades()` | `mixed` | Verifica se uma categoria possui grades |
| `getSubcategoryGrades()` | `mixed` | Retorna todas as grades de uma subcategoria (com info de tipo) |
| `getSubcategoryGradesWithValues()` | `mixed` | Retorna todas as grades de uma subcategoria com seus valores |
| `getSubcategoryGradeValues()` | `mixed` | Retorna todos os valores de uma grade de subcategoria |
| `addGradeToSubcategory()` | `mixed` | Adiciona uma grade a uma subcategoria |
| `addSubcategoryGradeValue()` | `mixed` | Adiciona um valor a uma grade de subcategoria |
| `saveSubcategoryGrades()` | `mixed` | Salva todas as grades e valores de uma subcategoria a partir de dados de formulário |
| `generateSubcategoryCombinations()` | `mixed` | Gera todas as combinações de grades de uma subcategoria e salva no banco |
| `getSubcategoryCombinations()` | `mixed` | Retorna todas as combinações de grades de uma subcategoria |
| `toggleSubcategoryCombination()` | `mixed` | Ativa ou desativa uma combinação de grades de subcategoria |
| `subcategoryHasGrades()` | `mixed` | Verifica se uma subcategoria possui grades |
| `getInheritedGrades()` | `mixed` | Retorna grades herdadas para um produto com base em subcategoria ou categoria. |
| `convertInheritedToProductFormat()` | `mixed` | Converte grades herdadas para o formato esperado por saveProductGrades(). |

### `Akti\Models\CheckoutToken`

_Model de tokens de checkout para pagamento seguro._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe CheckoutToken. |
| `create()` | `int` | Cria um novo token de checkout. |
| `findByToken()` | `?array` | Busca token pelo hash (com JOIN em orders). |
| `findById()` | `?array` | Busca token pelo ID. |
| `findByOrder()` | `array` | Lista tokens de um pedido. |
| `getActiveByOrder()` | `?array` | Busca token ativo de um pedido. |
| `markUsed()` | `bool` | Marca token como usado (atômico — só atualiza se status='active'). |
| `markUsedByOrder()` | `bool` | Marca token como usado pelo order_id (para webhooks). |
| `markExpired()` | `bool` | Marca token como expirado. |
| `cancel()` | `bool` | Cancela token. |
| `expireAll()` | `int` | Expira todos os tokens vencidos. |
| `updateIp()` | `bool` | Grava IP do visitante. |

### `Akti\Models\Commission`

_Model: Commission_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe Commission. |
| `getAllFormas()` | `array` | Retorna todas as formas de comissão. |
| `getForma()` | `?array` | Retorna uma forma de comissão pelo ID. |
| `createForma()` | `int` | Cria uma nova forma de comissão. |
| `updateForma()` | `bool` | Atualiza uma forma de comissão. |
| `deleteForma()` | `bool` | Remove uma forma de comissão. |
| `getGrupoFormas()` | `array` | Retorna todas as vinculações de grupo. |
| `linkGrupoForma()` | `bool` | Vincula uma forma de comissão a um grupo. |
| `unlinkGrupoForma()` | `bool` | Remove vínculo grupo-forma. |
| `getUsuarioFormas()` | `array` | Retorna todas as vinculações de usuário. |
| `linkUsuarioForma()` | `bool` | Vincula uma forma de comissão a um usuário. |
| `unlinkUsuarioForma()` | `bool` | Remove vínculo usuário-forma. |
| `getComissaoProdutos()` | `array` | Retorna todas as regras de comissão por produto. |
| `getComissaoProduto()` | `?array` | Retorna regra de comissão para um produto específico. |
| `getComissaoCategoria()` | `?array` | Retorna regra de comissão para uma categoria. |
| `saveComissaoProduto()` | `int` | Cria/atualiza regra de comissão por produto. |
| `deleteComissaoProduto()` | `bool` | Remove regra de comissão por produto. |
| `getFaixas()` | `array` | Retorna faixas de uma forma de comissão. |
| `saveFaixas()` | `bool` | Salva faixas para uma forma de comissão (replace all). |
| `registrarComissao()` | `int` | Registra uma comissão calculada. |
| `getComissoesRegistradas()` | `array` | Retorna comissões registradas com filtros e paginação. |
| `getComissaoRegistrada()` | `?array` | Retorna uma comissão registrada por ID. |
| `updateComissaoStatus()` | `bool` | Atualiza status de uma comissão registrada. |
| `getVendedoresComPendentes()` | `array` | Retorna lista de vendedores com comissões pendentes (para o modal de lote). |
| `getComissoesPendentesPorVendedor()` | `array` | Retorna comissões pendentes de um vendedor específico. |

### `Akti\Models\CompanySettings`

_Model: CompanySettings_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor do model |
| `getAll()` | `mixed` | Retorna todas as configurações como array associativo |

### `Akti\Models\Customer`

_Model: Customer_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor do model |
| `readAll()` | `array` | Retorna todos os clientes (exclui soft-deleted) |

### `Akti\Models\CustomerContact`

_Model: CustomerContact_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor do model |
| `create()` | `int` | Cria um novo contato para um cliente. |

### `Akti\Models\DashboardWidget`

_Model: DashboardWidget_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor |
| `getAvailableWidgets()` | `array` | Retorna a lista de widgets disponíveis (estática). |
| `getByGroup()` | `array` | Retorna a configuração de widgets para um grupo. |

### `Akti\Models\EmailCampaign`

_Model de campanhas de email marketing._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe EmailCampaign. |
| `readPaginated()` | `array` | Read paginated. |

### `Akti\Models\Equipment`

_Model de equipamentos/máquinas de produção._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe Equipment. |
| `create()` | `int` | Cria um novo registro no banco de dados. |
| `readAll()` | `array` | Retorna todos os registros. |
| `readPaginated()` | `array` | Read paginated. |

### `Akti\Models\EsgMetric`

_Model de métricas ESG (Environmental, Social, Governance)._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe EsgMetric. |
| `create()` | `int` | Cria um novo registro no banco de dados. |
| `readAll()` | `array` | Retorna todos os registros. |
| `readOne()` | `?array` | Retorna um registro pelo ID. |
| `update()` | `bool` | Atualiza um registro existente. |
| `delete()` | `bool` | Remove um registro pelo ID. |
| `addRecord()` | `int` | Add record. |
| `getRecords()` | `array` | Obtém dados específicos. |

### `Akti\Models\Financial`

_Model: Financial_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor do model |
| `getSummary()` | `mixed` | Retorna resumo geral do financeiro |
| `getChartData()` | `mixed` | Retorna dados para gráfico de receita x despesa dos últimos N meses |

### `Akti\Models\FinancialReport`

_FinancialReport — relatórios e dados de dashboard._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe FinancialReport. |
| `getSummary()` | `array` | Proxy para Financial::getSummary(). |
| `getChartData()` | `array` | Proxy para Financial::getChartData(). |
| `getOrderFinancialTotals()` | `array` | Proxy para Financial::getOrderFinancialTotals(). |
| `getPendingConfirmations()` | `array` | Proxy para Financial::getPendingConfirmations(). |
| `getUpcomingInstallments()` | `array` | Proxy para Financial::getUpcomingInstallments(). |
| `getOverdueInstallments()` | `array` | Proxy para Financial::getOverdueInstallments(). |

### `Akti\Models\FinancialSchema`

_FinancialSchema — verificação de schema/tabelas financeiras._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe FinancialSchema. |
| `hasSoftDeleteColumn()` | `bool` | Proxy para Financial::hasSoftDeleteColumn(). |

### `Akti\Models\IbptaxModel`

_Model: IbptaxModel_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe IbptaxModel. |
| `getByNcm()` | `?array` | Busca alíquotas IBPTax por NCM. |

### `Akti\Models\ImportBatch`

_Model: ImportBatch_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe ImportBatch. |
| `create()` | `int` | Cria um novo lote de importação. |

### `Akti\Models\ImportMappingProfile`

_Model: ImportMappingProfile_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe ImportMappingProfile. |
| `listByTenant()` | `array` | Lista perfis do tenant. |

### `Akti\Models\Installment`

_Model: Installment_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe Installment. |
| `getById()` | `?array` | Busca uma parcela pelo ID (dados completos com join). |
| `getBasic()` | `?array` | Retorna dados básicos de uma parcela (id, order_id, amount, installment_number). |
| `getByOrderId()` | `array` | Retorna parcelas de um pedido. |
| `countByOrderId()` | `int` | Conta parcelas de um pedido. |
| `hasAnyPaid()` | `bool` | Verifica se existe alguma parcela paga para um pedido. |
| `getPendingConfirmations()` | `array` | Retorna parcelas pendentes de confirmação. |
| `getUpcoming()` | `array` | Retorna próximas parcelas a vencer. |
| `getOverdue()` | `array` | Retorna parcelas vencidas não pagas. |
| `getPaginated()` | `array` | Lista parcelas com filtros e paginação. |
| `deleteByOrderId()` | `int` | Remove todas as parcelas de um pedido (somente se não houver pagas). |
| `generate()` | `bool` | Gera parcelas para um pedido. |

### `Akti\Models\IpGuard`

_IpGuard — Detecção de flood 404 e blacklist automática de IPs._

| Método | Retorno | Descrição |
|---|---|---|
| `getClientIp()` | `string` | Obtém o IP real do visitante, considerando proxies confiáveis. |
| `isBlacklisted()` | `bool` | Verifica se um IP está na blacklist ativa (não expirada). |
| `register404Hit()` | `void` | Registra um hit 404 para o IP atual. |
| `blacklistIp()` | `void` | Adiciona um IP à blacklist. |

### `Akti\Models\Logger`

_Model de logging legado._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe Logger. |
| `log()` | `mixed` | Registra informação no log. |
| `getPaginated()` | `array` | Lista logs com paginação e filtros. |

### `Akti\Models\LoginAttempt`

_LoginAttempt — Proteção contra força bruta_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe LoginAttempt. |
| `getSiteKey()` | `string` | Obtém dados específicos. |
| `getSecretKey()` | `string` | Obtém dados específicos. |
| `record()` | `bool` | Registra uma tentativa de login (falha ou sucesso). |
| `countRecentFailures()` | `int` | Conta tentativas falhas de um IP+email na janela de tempo. |
| `checkLockout()` | `array` | Verifica se o IP+email está bloqueado. |
| `requiresCaptcha()` | `bool` | Verifica se o captcha deve ser exibido (>= 3 falhas recentes). |
| `validateCaptcha()` | `bool` | Valida a resposta do reCAPTCHA v2 com a API do Google. |
| `purgeOld()` | `int` | Remove tentativas com mais de CLEANUP_MINUTES (padrão: 60 min). |
| `clearFailures()` | `bool` | Limpa falhas de um IP+email específico (após login bem-sucedido). |
| `getClientIp()` | `string` | Retorna o IP real do cliente, considerando proxies. |

### `Akti\Models\Master\AdminLog`

_Model de log de ações administrativas do painel master._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe AdminLog. |
| `log()` | `void` | Registra informação no log. |
| `readRecent()` | `array` | Read recent. |

### `Akti\Models\Master\AdminUser`

_Model de usuários administradores do painel master._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe AdminUser. |
| `findByEmail()` | `array` | Busca registro(s) com critérios específicos. |
| `findById()` | `array` | Busca registro(s) com critérios específicos. |
| `updateLastLogin()` | `void` | Update last login. |
| `updatePassword()` | `void` | Update password. |
| `readAll()` | `array` | Retorna todos os registros. |
| `create()` | `string` | Cria um novo registro no banco de dados. |
| `update()` | `void` | Atualiza um registro existente. |
| `delete()` | `void` | Remove um registro pelo ID. |
| `emailExists()` | `bool` | Email exists. |
| `countByRole()` | `array` | Conta registros com critérios opcionais. |

### `Akti\Models\Master\Backup`

_Model de backups de banco de dados._

| Método | Retorno | Descrição |
|---|---|---|
| `runBackup()` | `array` | Executa um processo. |
| `listBackups()` | `array` | List backups. |

### `Akti\Models\Master\GitVersion`

_Model de controle de versões Git._

| Método | Retorno | Descrição |
|---|---|---|
| `getBasePath()` | `string` | Obtém dados específicos. |
| `getDebugLog()` | `array` | Obtém dados específicos. |

### `Akti\Models\Master\Migration`

_Model de migrações SQL do sistema._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe Migration. |
| `listTenantDatabases()` | `array` | List tenant databases. |
| `getRegisteredTenants()` | `array` | Obtém dados específicos. |
| `getSchemaStructure()` | `array` | Obtém dados específicos. |

### `Akti\Models\Master\NginxLog`

_Model de logs do Nginx._

| Método | Retorno | Descrição |
|---|---|---|
| `listLogFiles()` | `array` | List log files. |

### `Akti\Models\Master\Plan`

_Model de planos de assinatura._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe Plan. |
| `readAll()` | `array` | Retorna todos os registros. |
| `readActive()` | `array` | Read active. |
| `readOne()` | `array` | Retorna um registro pelo ID. |
| `create()` | `string` | Cria um novo registro no banco de dados. |
| `update()` | `void` | Atualiza um registro existente. |
| `delete()` | `bool` | Remove um registro pelo ID. |

### `Akti\Models\Master\TenantClient`

_Model de clientes/tenants do sistema multi-tenant._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe TenantClient. |
| `readAll()` | `array` | Retorna todos os registros. |
| `readOne()` | `array` | Retorna um registro pelo ID. |
| `findBySubdomain()` | `array` | Busca registro(s) com critérios específicos. |
| `findByDbName()` | `array` | Busca registro(s) com critérios específicos. |
| `create()` | `string` | Cria um novo registro no banco de dados. |
| `update()` | `void` | Atualiza um registro existente. |
| `updateLimitsFromPlan()` | `void` | Update limits from plan. |
| `toggleActive()` | `void` | Alterna estado de propriedade. |
| `delete()` | `void` | Remove um registro pelo ID. |
| `getStats()` | `array` | Obtém dados específicos. |
| `provisionDatabase()` | `array` | Provision database. |

### `Akti\Models\NfeAuditLog`

_Model: NfeAuditLog_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe NfeAuditLog. |
| `log()` | `int` | Registra uma ação de auditoria. |

### `Akti\Models\NfeCredential`

_Model: NfeCredential_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` |  |
| `get()` | `array` | Busca credenciais SEFAZ ativas. |

### `Akti\Models\NfeDocument`

_Model: NfeDocument_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe NfeDocument. |
| `create()` | `int` | Cria um novo registro de NF-e. |

### `Akti\Models\NfeLog`

_Model: NfeLog_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe NfeLog. |
| `create()` | `int` | Registra um log de comunicação SEFAZ. |

### `Akti\Models\NfeQueue`

_Model: NfeQueue_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe NfeQueue. |
| `enqueue()` | `int` | Adiciona item à fila. |

### `Akti\Models\NfeReceivedDocument`

_Model: NfeReceivedDocument_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe NfeReceivedDocument. |
| `upsert()` | `int` | Insere ou atualiza um documento recebido pelo NSU. |

### `Akti\Models\NfeReportModel`

_Model: NfeReportModel_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe NfeReportModel. |
| `getNfesByPeriod()` | `array` | Retorna NF-e emitidas dentro de um período com filtros opcionais. |

### `Akti\Models\NfeWebhook`

_Model: NfeWebhook_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe NfeWebhook. |
| `create()` | `int` | Cria um webhook. |

### `Akti\Models\Notification`

_Notification Model_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe Notification. |
| `create()` | `int` | Cria uma nova notificação. |
| `getByUser()` | `array` | Lista notificações de um usuário (mais recentes primeiro). |
| `countUnread()` | `int` | Conta notificações não-lidas de um usuário. |
| `markAsRead()` | `bool` | Marca uma notificação como lida. |
| `markAllAsRead()` | `bool` | Marca todas as notificações de um usuário como lidas. |
| `deleteOld()` | `int` | Exclui notificações antigas (mais de X dias). |
| `readOne()` | `?array` | Retorna uma notificação pelo ID. |
| `broadcast()` | `int` | Envia notificação para múltiplos usuários (broadcast). |

### `Akti\Models\Order`

_Model de pedidos/ordens de serviço._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe Order. |
| `__get()` | `mixed` | __get. |
| `__set()` | `void` | __set. |
| `create()` | `mixed` | Cria um novo registro no banco de dados. |
| `getScheduledContacts()` | `mixed` | Busca contatos agendados para um determinado mês/ano |
| `getScheduledContactsByDate()` | `mixed` | Busca contatos agendados para um dia específico (para relatório) |
| `readAll()` | `array` | Retorna todos os registros. |
| `readPaginated()` | `array` | Retorna pedidos com paginação |

### `Akti\Models\OrderItemLog`

_Model para logs/histórico de itens de pedido (por produto)_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe OrderItemLog. |
| `createTableIfNotExists()` | `mixed` | Verifica se a tabela existe (DDL movida para /sql). |
| `addLog()` | `mixed` | Adicionar log a um item do pedido |
| `getLogsByItem()` | `mixed` | Buscar logs de um item específico (para modal do painel de produção) |
| `getLogsByOrder()` | `mixed` | Buscar todos os logs de todos os itens de um pedido (para detalhe do pedido) |
| `countLogsByItem()` | `mixed` | Contar logs por item (para badge no painel de produção) |
| `countLogsByOrderGrouped()` | `mixed` | Contar logs agrupados por item para um pedido (batch) |
| `deleteLog()` | `mixed` | Excluir um log (e seu arquivo se existir) |
| `handleFileUpload()` | `mixed` | Upload de arquivo e retorna dados do arquivo |

### `Akti\Models\OrderPreparation`

_Model para checklist de preparação de pedidos._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe OrderPreparation. |
| `createTableIfNotExists()` | `mixed` | Verifica se a tabela existe (DDL movida para /sql). |
| `getChecklist()` | `mixed` | Obter checklist de um pedido como array associativo |
| `toggle()` | `mixed` | Alternar o status de uma etapa do checklist (toggle) |

### `Akti\Models\PaymentGateway`

_Model: PaymentGateway_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe PaymentGateway. |
| `readAll()` | `array` | Retorna todos os gateways cadastrados. |
| `readOne()` | `array` | Retorna um gateway pelo ID. |
| `readBySlug()` | `array` | Retorna um gateway pelo slug. |
| `getDefault()` | `array` | Retorna o gateway padrão (is_default=1 e is_active=1). |
| `getActive()` | `array` | Retorna todos os gateways ativos. |
| `update()` | `bool` | Atualiza configurações de um gateway (credenciais, settings, ambiente, status). |

### `Akti\Models\Permission`

_Model de permissões de acesso por grupo de usuários._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe Permission. |
| `readAll()` | `array` | Retorna todos os registros. |
| `readByPage()` | `array` | Read by page. |
| `readOne()` | `?array` | Retorna um registro pelo ID. |
| `create()` | `int` | Cria um novo registro no banco de dados. |
| `delete()` | `bool` | Remove um registro pelo ID. |
| `getGroupPermissions()` | `array` | Obtém dados específicos. |
| `setGroupPermission()` | `bool` | Define valor específico. |
| `removeGroupPermission()` | `bool` | Remove group permission. |
| `checkPermission()` | `bool` | Verifica se o usuário tem permissão de acesso. |

### `Akti\Models\Pipeline`

_Model do pipeline Kanban de produção._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe Pipeline. |
| `getOrdersByStage()` | `mixed` | Busca todos os pedidos ativos no pipeline (não concluídos/cancelados) |
| `getStageGoals()` | `mixed` | Busca metas de tempo por etapa |
| `updateStageGoal()` | `mixed` | Atualiza a meta de horas de uma etapa |
| `moveToStage()` | `mixed` | Move um pedido para a próxima etapa (ou uma etapa específica) |
| `addHistory()` | `mixed` | Registra histórico de movimentação |
| `getHistory()` | `mixed` | Busca histórico de um pedido com duração em cada etapa. |
| `getOrderDetail()` | `mixed` | Busca detalhes completos de um pedido para o pipeline |
| `updateOrderDetails()` | `mixed` | Atualiza dados extras do pedido (prioridade, responsável, notas, financeiro, envio) |
| `getDelayedOrders()` | `mixed` | Conta pedidos atrasados (acima da meta de horas por etapa) |
| `getCompletedOrders()` | `mixed` | Busca pedidos concluídos (para histórico/relatório) |
| `initOrderProductionSectors()` | `mixed` | Inicializa os setores de produção POR ITEM do pedido quando entra na etapa "producao". |
| `getOrderProductionSectors()` | `mixed` | Retorna os setores de produção de um pedido agrupados por item, com dados do setor e produto |
| `advanceItemSector()` | `mixed` | Concluir o setor atual de um item e avançar para o próximo. |
| `revertItemSector()` | `mixed` | Retroceder: reverte o último setor concluído de um item para pendente. |
| `getProductionBoardData()` | `mixed` | Retorna todos os itens de produção agrupados por setor, para o painel de produção. |
| `moveOrderSector()` | `mixed` | Mover um setor de produção de um item para um status específico (fallback genérico) |
| `getStats()` | `mixed` | Estatísticas do pipeline para o dashboard |

### `Akti\Models\PortalAccess`

_Model: PortalAccess_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor do model |
| `create()` | `int` | Cria um acesso ao portal para um cliente |

### `Akti\Models\PortalMessage`

_Model: PortalMessage_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor do model |
| `create()` | `int` | Cria uma nova mensagem |

### `Akti\Models\PreparationStep`

_Model para gerenciar etapas de preparo globais (configuráveis via Settings)._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe PreparationStep. |
| `createTableIfNotExists()` | `mixed` | Verifica se a tabela existe (DDL movida para /sql). |
| `getAll()` | `mixed` | Retorna todas as etapas (ativas e inativas), ordenadas |
| `getActive()` | `mixed` | Retorna apenas as etapas ativas, ordenadas |
| `getActiveAsMap()` | `mixed` | Retorna as etapas ativas como array associativo [step_key => ['icon'=>..., 'label'=>..., 'desc'=>...]] |
| `add()` | `mixed` | Adicionar uma nova etapa |
| `update()` | `mixed` | Atualizar uma etapa existente |
| `delete()` | `mixed` | Excluir uma etapa |
| `toggleActive()` | `mixed` | Ativar/desativar uma etapa |
| `getById()` | `mixed` | Buscar uma etapa por ID |

### `Akti\Models\PriceTable`

_Model: PriceTable_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe PriceTable. |
| `countAll()` | `mixed` | Conta o total de tabelas de preço cadastradas |
| `readAll()` | `mixed` | Lista todas as tabelas de preço |
| `readOne()` | `mixed` | Lê uma tabela de preço |
| `create()` | `mixed` | Cria tabela de preço |
| `update()` | `mixed` | Atualiza tabela de preço |
| `delete()` | `mixed` | Exclui tabela de preço (se não for padrão) |
| `getDefault()` | `mixed` | Retorna tabela padrão |
| `getItems()` | `mixed` | Retorna itens de uma tabela de preço com dados do produto |
| `setItemPrice()` | `mixed` | Define preço de um produto na tabela |
| `removeItem()` | `mixed` | Remove item de uma tabela de preço |
| `getPricesForProduct()` | `mixed` | Retorna todos os preços de todas as tabelas para um dado produto |
| `saveProductPrices()` | `mixed` | Salva os preços de um produto em múltiplas tabelas de uma vez. |
| `getProductPriceForCustomer()` | `mixed` | Retorna o preço de um produto para um determinado cliente |
| `getAllPricesForCustomer()` | `mixed` | Retorna todos os preços para um cliente (para preencher JS no frontend) |

### `Akti\Models\Product`

_Model de produtos com suporte a grades e variações._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe Product. |
| `readAll()` | `mixed` | Retorna todos os registros. |
| `readPaginated()` | `array` | Retorna produtos com paginação |

### `Akti\Models\ProductGrade`

_ProductGrade Model_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe ProductGrade. |
| `getAllGradeTypes()` | `mixed` | List all grade types |
| `createGradeType()` | `mixed` | Create a new grade type |
| `getProductGrades()` | `mixed` | Get all grades for a product (with type info) |
| `getProductGradesWithValues()` | `mixed` | Get all grades for a product WITH their values |
| `addGradeToProduct()` | `mixed` | Add a grade to a product |
| `removeGradeFromProduct()` | `mixed` | Remove a grade from a product (soft delete) |
| `deleteGradeFromProduct()` | `mixed` | Hard delete a grade and its values |
| `getGradeValues()` | `mixed` | Get all values for a product grade |
| `addGradeValue()` | `mixed` | Add a value to a product grade |
| `removeGradeValue()` | `mixed` | Remove a grade value (soft delete) |
| `deleteGradeValue()` | `mixed` | Hard delete a grade value |
| `getProductCombinations()` | `mixed` | Get all combinations for a product |
| `getActiveProductCombinations()` | `mixed` | Get only active combinations for a product |
| `toggleProductCombination()` | `mixed` | Toggle combination active/inactive for a product |
| `saveCombination()` | `mixed` | Save a combination |
| `generateCombinations()` | `mixed` | Generate all combinations from current grades/values and save them. |
| `saveProductGrades()` | `mixed` | Save all grades and values for a product from form data. |
| `saveCombinationsData()` | `mixed` | Save combinations data (prices, stock, SKU, active state) from form |
| `productHasGrades()` | `mixed` | Check if a product has any grades configured |
| `getCombination()` | `mixed` | Get a specific combination by ID |

### `Akti\Models\ProductionSector`

_Model de setores de produção._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe ProductionSector. |
| `countAll()` | `mixed` | Conta o total de setores de produção cadastrados |

### `Akti\Models\PurchaseOrder`

_Model de ordens de compra de insumos._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe PurchaseOrder. |
| `readPaginated()` | `array` | Read paginated. |

### `Akti\Models\QualityChecklist`

_Model de checklists de controle de qualidade._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe QualityChecklist. |
| `readAll()` | `array` | Retorna todos os registros. |
| `readOne()` | `?array` | Retorna um registro pelo ID. |
| `create()` | `int` | Cria um novo registro no banco de dados. |
| `update()` | `bool` | Atualiza um registro existente. |
| `delete()` | `bool` | Remove um registro pelo ID. |
| `getItems()` | `array` | Obtém dados específicos. |
| `addItem()` | `int` | Add item. |
| `removeItem()` | `bool` | Remove item. |
| `createInspection()` | `int` | Create inspection. |
| `updateInspection()` | `bool` | Update inspection. |
| `getInspections()` | `array` | Obtém dados específicos. |
| `createNonConformity()` | `int` | Create non conformity. |
| `getNonConformities()` | `array` | Obtém dados específicos. |

### `Akti\Models\Quote`

_Model de orçamentos/cotações._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe Quote. |
| `readPaginated()` | `array` | Read paginated. |

### `Akti\Models\RecurringTransaction`

_RecurringTransaction — Model para transações recorrentes._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe RecurringTransaction. |
| `create()` | `?int` | Cria uma nova recorrência. |
| `update()` | `bool` | Atualiza uma recorrência existente. |
| `getById()` | `?array` | Busca uma recorrência pelo ID. |
| `readAll()` | `array` | Lista todas as recorrências (ativas primeiro). |
| `getActive()` | `array` | Lista apenas recorrências ativas. |
| `toggleActive()` | `bool` | Ativa/desativa uma recorrência. |
| `delete()` | `bool` | Exclui uma recorrência. |
| `processMonth()` | `array` | Processa recorrências pendentes para o mês atual. |

### `Akti\Models\ReportModel`

_Model: ReportModel_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor do model |
| `getOrdersByPeriod()` | `array` | Retorna pedidos dentro de um período com cliente, total, status e data. |
| `getRevenueByCustomer()` | `array` | Retorna faturamento agrupado por cliente com quantidade de pedidos e soma. |
| `getIncomeStatement()` | `array` | Retorna entradas e saídas agrupadas por categoria com saldo líquido. |
| `getOpenInstallments()` | `array` | Retorna parcelas pendentes ou atrasadas com dias de atraso, ordenadas por vencimento. |
| `getScheduledContacts()` | `array` | Retorna contatos agendados dentro de um período, com cliente e prioridade. |
| `getCategoryLabels()` | `array` | Mapa de categorias de transação para labels legíveis (pt-BR). |
| `getCategoryLabel()` | `string` | Retorna label legível de uma categoria. |
| `getStatusLabels()` | `array` | Mapa de status de pedido para labels legíveis (pt-BR). |
| `getStatusLabel()` | `string` | Retorna label legível de um status. |
| `getStageLabels()` | `array` | Mapa de etapas do pipeline para labels legíveis (pt-BR). |
| `getPriorityLabels()` | `array` | Mapa de prioridades para labels legíveis (pt-BR). |
| `getPriorityLabel()` | `string` | Retorna label legível de uma prioridade. |
| `getProductsCatalog()` | `array` | Retorna produtos com informações completas para relatório: |

### `Akti\Models\ReportTemplate`

_Model de templates de relatórios personalizados._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe ReportTemplate. |
| `readAll()` | `array` | Retorna todos os registros. |

### `Akti\Models\Shipment`

_Model de remessas/entregas._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe Shipment. |
| `create()` | `int` | Cria um novo registro no banco de dados. |
| `readAll()` | `array` | Retorna todos os registros. |

### `Akti\Models\SiteBuilder`

_Model para o Site Builder._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe SiteBuilder. |
| `getSettings()` | `array` | Obtém todas as configurações do tenant (tema + conteúdo de páginas). |
| `getSettingsByGroup()` | `array` | Obtém configurações filtradas por grupo. |
| `getSetting()` | `?string` | Obtém o valor de uma configuração específica. |
| `saveSetting()` | `bool` | Salva uma configuração (insert ou update via UPSERT). |
| `saveSettingsBatch()` | `bool` | Salva múltiplas configurações de um grupo em transação. |
| `getThemeSettings()` | `array` | Obtém dados específicos. |
| `saveThemeSettings()` | `bool` | Salva dados. |

### `Akti\Models\Stock`

_Stock Model_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe Stock. |
| `countWarehouses()` | `mixed` | Conta o total de armazéns cadastrados |
| `getAllWarehouses()` | `mixed` | Obtém dados específicos. |
| `getWarehouse()` | `mixed` | Obtém dados específicos. |
| `createWarehouse()` | `mixed` | Create warehouse. |
| `updateWarehouse()` | `mixed` | Update warehouse. |
| `deleteWarehouse()` | `mixed` | Delete warehouse. |
| `getStockItems()` | `mixed` | Listar itens do estoque com filtros |
| `getOrCreateStockItem()` | `mixed` | Obter ou criar item de estoque |
| `getStockItem()` | `mixed` | Obtém dados específicos. |
| `updateStockItemMeta()` | `mixed` | Update stock item meta. |
| `addMovement()` | `mixed` | Registrar movimentação de estoque |
| `getMovement()` | `mixed` | Buscar uma movimentação pelo ID |
| `updateMovement()` | `mixed` | Atualizar uma movimentação e recalcular saldo do stock_item |
| `deleteMovement()` | `mixed` | Excluir uma movimentação e reverter o saldo do stock_item |
| `getMovements()` | `mixed` | Listar movimentações com filtros |

### `Akti\Models\Subcategory`

_Model de subcategorias de produtos._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe Subcategory. |
| `__get()` | `mixed` | __get. |
| `__set()` | `void` | __set. |
| `readByCategoryId()` | `mixed` | Read by category id. |
| `create()` | `mixed` | Cria um novo registro no banco de dados. |
| `readAll()` | `mixed` | Retorna todos os registros. |
| `countAll()` | `int` | Retorna a quantidade total de subcategorias. |
| `readPaginated()` | `array` | Retorna subcategorias paginadas com nome da categoria. |

### `Akti\Models\Supplier`

_Model de fornecedores._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe Supplier. |
| `readAll()` | `array` | Retorna todos os registros. |
| `readPaginated()` | `array` | Read paginated. |

### `Akti\Models\Supply`

_Model de insumos/matérias-primas._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe Supply. |
| `readAll()` | `array` | Retorna todos os registros. |
| `readPaginated()` | `array` | Read paginated. |

### `Akti\Models\SupplyStock`

_Model de estoque de insumos._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe SupplyStock. |
| `getItems()` | `array` | Obtém dados específicos. |

### `Akti\Models\Ticket`

_Model de tickets de suporte._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe Ticket. |
| `create()` | `int` | Cria um novo registro no banco de dados. |
| `readAll()` | `array` | Retorna todos os registros. |
| `readPaginated()` | `array` | Read paginated. |

### `Akti\Models\Transaction`

_Transaction — CRUD e consultas de transações financeiras._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe Transaction. |
| `create()` | `mixed` | Proxy para Financial::addTransaction(). |
| `readOne()` | `mixed` | Proxy para Financial::getTransactionById(). |
| `update()` | `bool` | Proxy para Financial::updateTransaction(). |
| `delete()` | `bool` | Proxy para Financial::deleteTransaction(). |
| `restore()` | `bool` | Proxy para Financial::restoreTransaction(). |
| `getAll()` | `array` | Proxy para Financial::getTransactions(). |
| `getPaginated()` | `array` | Proxy para Financial::getTransactionsPaginated(). |

### `Akti\Models\User`

_Modelo User_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor |
| `__get()` | `mixed` | __get. |
| `__set()` | `void` | __set. |
| `setPassword()` | `void` | Define valor específico. |
| `getPassword()` | `?string` | Obtém dados específicos. |
| `login()` | `mixed` | Tenta autenticar um usuário pelo e-mail e senha. |
| `readAll()` | `mixed` | Retorna um PDOStatement com todos os usuários (junto com o nome do grupo quando houver). |
| `countAll()` | `mixed` | Retorna a quantidade total de usuários. |
| `emailExists()` | `bool` | Verifica se um e-mail já existe na tabela de usuários, opcionalmente excluindo um ID. |
| `readPaginated()` | `array` | Retorna usuários paginados com JOIN no grupo. |

### `Akti\Models\UserGroup`

_Classe UserGroup_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor |
| `readAll()` | `mixed` | Retorna todos os grupos cadastrados. |
| `countAll()` | `int` | Retorna a quantidade total de grupos. |
| `readPaginated()` | `array` | Retorna grupos paginados. |

### `Akti\Models\Walkthrough`

_Classe Walkthrough_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor |
| `needsWalkthrough()` | `bool` | Verifica se o usuário precisa ver o walkthrough. |

### `Akti\Models\WhatsAppMessage`

_Model de mensagens do WhatsApp._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe WhatsAppMessage. |
| `getConfig()` | `?array` | Obtém dados específicos. |
| `saveConfig()` | `bool` | Salva dados. |
| `getTemplates()` | `array` | Obtém dados específicos. |
| `saveTemplate()` | `int` | Salva dados. |
| `logMessage()` | `int` | Registra informação no log. |
| `updateMessageStatus()` | `bool` | Update message status. |

### `Akti\Models\WorkflowRule`

_Model de regras de workflow automatizado._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe WorkflowRule. |
| `readAll()` | `array` | Retorna todos os registros. |
| `readActive()` | `array` | Read active. |
| `readByEvent()` | `array` | Read by event. |
| `readOne()` | `?array` | Retorna um registro pelo ID. |
| `create()` | `int` | Cria um novo registro no banco de dados. |
| `update()` | `bool` | Atualiza um registro existente. |
| `delete()` | `bool` | Remove um registro pelo ID. |
| `toggle()` | `bool` | Alterna estado de propriedade. |
| `logExecution()` | `int` | Registra informação no log. |
| `getLogs()` | `array` | Obtém dados específicos. |
| `updatePriority()` | `bool` | Update priority. |

## Services (Serviços)

### `Akti\Services\AiAssistantService`

_AI Assistant Service — integrates with OpenAI-compatible APIs (GPT, Ollama, etc.)_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe AiAssistantService. |
| `isConfigured()` | `bool` | Verifica uma condição booleana. |
| `chat()` | `array` | Send a chat message and get a response. |
| `getHistory()` | `array` | Get conversation history for a user. |
| `saveMessage()` | `void` | Save a message to conversation history. |
| `clearHistory()` | `void` | Clear conversation history for a user. |

### `Akti\Services\AuditLogService`

_AuditLogService — Convenience wrapper for logging auditable actions._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe AuditLogService. |
| `log()` | `void` | Log an audit entry. |

### `Akti\Services\AuthService`

_AuthService — Lógica de autenticação (login, brute-force, portal unificado)._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe AuthService. |
| `attemptLogin()` | `array` | Processar tentativa de login. |

### `Akti\Services\BiService`

_Class BiService._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe BiService. |
| `getSalesDashboard()` | `array` | Dashboard de vendas: faturamento, ticket médio, conversão, por período. |
| `getProductionDashboard()` | `array` | Dashboard de produção: throughput, gargalos, tempo médio por etapa. |
| `getFinancialDashboard()` | `array` | Dashboard financeiro: fluxo de caixa, inadimplência, DRE simplificado. |
| `drillDown()` | `array` | Drill-down: detalhamento de pedidos por filtro. |

### `Akti\Services\CatalogCartService`

_CatalogCartService — Lógica de carrinho do catálogo público._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe CatalogCartService. |
| `addToCart()` | `array` | Adicionar produto ao carrinho (item do pedido). |
| `removeFromCart()` | `array` | Remover item do carrinho. |
| `updateCartItem()` | `array` | Atualizar quantidade de um item no carrinho. |
| `getCart()` | `array` | Buscar carrinho atual por token. |

### `Akti\Services\CatalogQuoteService`

_CatalogQuoteService — Lógica de confirmação/revogação de orçamento via catálogo._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe CatalogQuoteService. |
| `confirmQuote()` | `array` | Confirmar orçamento pelo cliente. |

### `Akti\Services\CategoryService`

_CategoryService — Lógica de negócio para categorias e subcategorias._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe CategoryService. |
| `saveCategoryCombinationsState()` | `void` | Salva o estado (ativo/inativo) das combinações de grades de uma categoria. |
| `saveSubcategoryCombinationsState()` | `void` | Salva o estado (ativo/inativo) das combinações de grades de uma subcategoria. |
| `exportToProducts()` | `array` | Exporta grades e/ou setores de uma categoria/subcategoria para um conjunto de produtos. |

### `Akti\Services\CheckoutService`

_Class CheckoutService._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe CheckoutService. |
| `generateToken()` | `array` | Gera um token de checkout transparente. |
| `processCheckout()` | `array` | Processa pagamento vindo do checkout transparente. |
| `cancelToken()` | `bool` | Cancela um token de checkout. |
| `markInstallmentPaidFromCheckout()` | `void` | Garante que existe uma parcela para o pedido e marca como paga. |

### `Akti\Services\CommissionAutoService`

_CommissionAutoService — Serviço de Comissão Automática_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe CommissionAutoService. |
| `getStageGatilho()` | `string` | Retorna a etapa do pipeline configurada para gerar comissão. |
| `getCriterioLiberacao()` | `string` | Retorna o critério de liberação configurado. |
| `isAprovacaoAutomatica()` | `bool` | Retorna se a aprovação é automática. |
| `tryAutoCommission()` | `array` | Verifica se o pedido atende às condições para comissão automática |

### `Akti\Services\CommissionEngine`

_CommissionEngine — Motor de Regras de Comissão (Rule Engine)_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe CommissionEngine. |
| `registerStrategy()` | `void` | Registra uma nova estratégia de cálculo. |
| `registerResolver()` | `void` | Registra um novo resolver de regra. |
| `resolveRegra()` | `?array` | Resolve qual regra aplicar para o contexto dado. |
| `calcular()` | `array` | Calcula a comissão para um contexto (sem registrar). |

### `Akti\Services\CommissionService`

_CommissionService — Camada de Serviço para Comissões_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe CommissionService. |
| `getAllFormas()` | `array` | Obtém dados específicos. |
| `getForma()` | `?array` | Obtém dados específicos. |
| `createForma()` | `array` | Create forma. |
| `updateForma()` | `array` | Update forma. |
| `deleteForma()` | `array` | Delete forma. |
| `getFaixas()` | `array` | Obtém dados específicos. |
| `getGrupoFormas()` | `array` | Obtém dados específicos. |
| `linkGrupoForma()` | `array` | Link grupo forma. |
| `unlinkGrupoForma()` | `array` | Unlink grupo forma. |
| `getUsuarioFormas()` | `array` | Obtém dados específicos. |
| `linkUsuarioForma()` | `array` | Link usuario forma. |
| `unlinkUsuarioForma()` | `array` | Unlink usuario forma. |
| `getComissaoProdutos()` | `array` | Obtém dados específicos. |
| `saveComissaoProduto()` | `array` | Salva dados. |
| `deleteComissaoProduto()` | `array` | Delete comissao produto. |
| `simular()` | `array` | Simula comissão sem registrar. |
| `calcularComissao()` | `array` | Calcula e registra comissão para um pedido. |
| `getComissoesRegistradas()` | `array` | Obtém dados específicos. |
| `getComissaoRegistrada()` | `?array` | Obtém dados específicos. |
| `aprovarComissao()` | `array` | Aprovar comissao. |
| `pagarComissao()` | `array` | Pagar comissao. |
| `cancelarComissao()` | `array` | Cancela operação. |
| `aprovarEmLote()` | `array` | Aprovar múltiplas comissões (muda para aguardando_pagamento). |

### `Akti\Services\Contracts\AuthServiceInterface`

_Interface AuthServiceInterface._

| Método | Retorno | Descrição |
|---|---|---|
| `attemptLogin()` | `array` | Attempt login. |

### `Akti\Services\Contracts\CheckoutServiceInterface`

_Interface CheckoutServiceInterface._

| Método | Retorno | Descrição |
|---|---|---|
| `generateToken()` | `array` | Gera conteúdo ou dados. |
| `processCheckout()` | `array` | Processa uma operação específica. |
| `cancelToken()` | `bool` | Cancela operação. |
| `markInstallmentPaidFromCheckout()` | `void` | Mark installment paid from checkout. |
| `expireOldTokens()` | `int` | Expire old tokens. |
| `getTokenByToken()` | `?array` | Obtém dados específicos. |

### `Akti\Services\Contracts\EmailServiceInterface`

_Interface EmailServiceInterface._

| Método | Retorno | Descrição |
|---|---|---|
| `send()` | `array` | Envia dados ou notificação. |
| `sendCampaign()` | `array` | Envia dados ou notificação. |
| `sendTest()` | `array` | Envia dados ou notificação. |

### `Akti\Services\Contracts\NfeServiceInterface`

_Interface NfeServiceInterface._

| Método | Retorno | Descrição |
|---|---|---|
| `isLibraryAvailable()` | `bool` | Verifica uma condição booleana. |
| `testConnection()` | `array` | Test connection. |
| `emit()` | `array` | Emite evento ou sinal. |
| `cancel()` | `array` | Cancela operação. |
| `correction()` | `array` | Correction. |
| `checkStatus()` | `array` | Verifica condição ou estado. |
| `getCredentials()` | `array` | Obtém dados específicos. |
| `inutilizar()` | `array` | Inutilizar. |

### `Akti\Services\Contracts\PipelinePaymentServiceInterface`

_Interface PipelinePaymentServiceInterface._

| Método | Retorno | Descrição |
|---|---|---|
| `generatePaymentLink()` | `array` | Gera conteúdo ou dados. |
| `generateCheckoutLink()` | `array` | Gera conteúdo ou dados. |

### `Akti\Services\CustomerContactService`

_Service: CustomerContactService_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe CustomerContactService. |
| `listByCustomer()` | `array` | Lista contatos de um cliente. |
| `save()` | `array` | Cria ou atualiza um contato. |
| `delete()` | `array` | Remove um contato. |

### `Akti\Services\CustomerExportService`

_CustomerExportService — Lógica de exportação de clientes._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe CustomerExportService. |
| `exportCsv()` | `void` | Exporta clientes em formato CSV. |

### `Akti\Services\CustomerFormService`

_Service: CustomerFormService_

| Método | Retorno | Descrição |
|---|---|---|
| `captureFormData()` | `array` | Captura e sanitiza todos os campos do formulário de cliente. |
| `validateCustomerData()` | `Validator` | Validação server-side completa dos dados do cliente. |
| `buildAddressJson()` | `string` | Monta o JSON de endereço para retrocompatibilidade. |

### `Akti\Services\CustomerImportService`

_CustomerImportService — Lógica de importação de clientes extraída do CustomerController._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe CustomerImportService. |
| `parseFile()` | `array` | Faz parse do arquivo enviado e retorna colunas + preview + auto-mapeamento. |
| `executeImport()` | `array` | Executa a importação com o mapeamento fornecido. |

### `Akti\Services\CustomerOrderHistoryService`

_Service: CustomerOrderHistoryService_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe CustomerOrderHistoryService. |
| `getRecentOrders()` | `array` | Busca pedidos recentes de um cliente. |
| `getOrderHistoryPaginated()` | `array` | Busca pedidos paginados de um cliente com formatação. |

### `Akti\Services\DemandPredictionService`

_DemandPredictionService — Previsão de demanda com base em dados históricos._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe DemandPredictionService. |
| `predictDemand()` | `array` | Prever demanda de um produto para os próximos N dias. |

### `Akti\Services\EmailService`

_Class EmailService._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe EmailService. |
| `send()` | `array` | Send a single email |

### `Akti\Services\ExternalApiService`

_Service: ExternalApiService_

| Método | Retorno | Descrição |
|---|---|---|
| `searchCep()` | `array` | Consulta endereço por CEP via ViaCEP. |

### `Akti\Services\FileManager`

_FileManager — Serviço centralizado de gestão de arquivos._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe FileManager. |
| `upload()` | `array` | Upload de um único arquivo. |

### `Akti\Services\FinancialAuditService`

_FinancialAuditService — Serviço de auditoria para o módulo financeiro._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe FinancialAuditService. |
| `log()` | `bool` | Registra uma entrada de auditoria. |
| `logInstallment()` | `bool` | Log de ação em parcela (installment). |
| `logTransaction()` | `bool` | Log de ação em transação financeira. |
| `logOrder()` | `bool` | Log de ação em pedido (financial context). |
| `getHistory()` | `array` | Retorna histórico de auditoria de uma entidade. |
| `getRecent()` | `array` | Retorna últimas ações de auditoria do módulo financeiro. |
| `getPaginated()` | `array` | Retorna registros de auditoria com paginação e filtros para o relatório. |

### `Akti\Services\FinancialImportService`

_FinancialImportService — Camada de Serviço para Importação Financeira (OFX/CSV/Excel)._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe FinancialImportService. |
| `parseOfx()` | `array` | Parse de arquivo OFX/OFC — retorna transações para preview. |
| `parseCsv()` | `array` | Parse de arquivo CSV/TXT — retorna dados estruturados. |
| `parseExcel()` | `array` | Parse de arquivo Excel (XLS/XLSX) via PhpSpreadsheet. |
| `saveImportTmpFile()` | `void` | Salva o arquivo de importação em diretório temporário para reutilizar. |
| `importOfxSelected()` | `array` | Importa transações OFX de linhas selecionadas. |
| `importCsvMapped()` | `array` | Importa transações CSV/Excel mapeado. |
| `importOfxDirect()` | `array` | Importa OFX diretamente (sem seleção de linhas — modo legacy). |
| `getFinancialImportFields()` | `array` | Campos disponíveis para mapeamento de importação financeira. |

### `Akti\Services\FinancialReportService`

_FinancialReportService — Camada de Serviço para Relatórios Financeiros._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe FinancialReportService. |
| `getSummary()` | `array` | Retorna resumo geral do financeiro (dashboard). |
| `getChartData()` | `array` | Retorna dados para gráfico de receita x despesa. |
| `getPendingConfirmations()` | `array` | Retorna parcelas pendentes de confirmação. |
| `getOverdueInstallments()` | `array` | Retorna parcelas vencidas. |
| `getUpcomingInstallments()` | `array` | Retorna próximas parcelas a vencer. |
| `getOrdersPendingPayment()` | `array` | Retorna pedidos com pagamento pendente. |
| `getDre()` | `array` | Gera DRE simplificado para um período. |
| `getCashflowProjection()` | `array` | Gera fluxo de caixa projetado para os próximos N meses. |

### `Akti\Services\HeaderDataService`

_HeaderDataService — Centraliza todas as queries que alimentam o header/layout._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe HeaderDataService. |
| `getAllHeaderData()` | `array` | Retorna todos os dados necessários para o header em uma única chamada. |
| `getUserMenuPermissions()` | `array` | Retorna as permissões de menu para o grupo do usuário. |
| `getDelayedOrders()` | `array` | Retorna a contagem e lista de pedidos atrasados no pipeline. |
| `getDelayedProducts()` | `array` | Retorna os produtos atrasados nos setores de produção. |
| `invalidateCache()` | `void` | Invalida o cache do header (chamar após operações que alteram dados do pipeline). |

### `Akti\Services\InstallmentService`

_InstallmentService — Camada de Serviço para Parcelas._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe InstallmentService. |
| `getModel()` | `Installment` | Retorna a instância do model Installment. |
| `updateOverdue()` | `void` | Atualiza parcelas vencidas. |
| `generateForOrder()` | `bool` | Gera parcelas para um pedido e atualiza os campos financeiros. |
| `payInstallment()` | `array` | Registra pagamento de parcela (fluxo completo: pagar + transação + parcela restante). |
| `confirmPayment()` | `bool` | Confirma pagamento de parcela. |
| `cancelInstallment()` | `bool` | Cancela/estorna parcela (e registra estorno na tabela de transações). |
| `mergeInstallments()` | `mixed` | Merge de parcelas pendentes. |
| `splitInstallment()` | `array` | Split de parcela. |

### `Akti\Services\MarketplaceConnector`

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe MarketplaceConnector. |
| `authenticate()` | `bool` | Autenticar com a API do marketplace (OAuth2 ou API key). |
| `syncProducts()` | `array` | Sincronizar produtos: enviar catálogo local → marketplace. |
| `importOrders()` | `array` | Importar pedidos do marketplace → sistema local. |
| `updateOrderStatus()` | `bool` | Atualizar status de um pedido no marketplace. |
| `syncStock()` | `array` | Sincronizar estoque local → marketplace. |
| `getName()` | `string` | Retorna o nome do conector. |

### `Akti\Services\MercadoLivreConnector`

_MercadoLivreConnector — Conector para Mercado Livre._

| Método | Retorno | Descrição |
|---|---|---|
| `getName()` | `string` | Obtém dados específicos. |
| `authenticate()` | `bool` | Autentica o usuário com credenciais. |
| `syncProducts()` | `array` | Sincroniza dados. |
| `importOrders()` | `array` | Importa dados. |
| `updateOrderStatus()` | `bool` | Update order status. |

### `Akti\Services\NfceDanfeGenerator`

_NfceDanfeGenerator — Gera DANFE para NFC-e (modelo 65) em formato de cupom térmico._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe NfceDanfeGenerator. |
| `generate()` | `?string` | Gera DANFE NFC-e a partir do XML autorizado. |

### `Akti\Services\NfceXmlBuilder`

_NfceXmlBuilder — Monta XML da NFC-e (modelo 65) no formato 4.00._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe NfceXmlBuilder. |
| `getCalculatedItems()` | `array` | Retorna os dados fiscais calculados de cada item (após build()). |
| `getCalculatedTotals()` | `array` | Retorna os totais fiscais calculados (após build()). |
| `getQrCodeUrl()` | `string` | Retorna URL do QR Code gerado (após build()). |
| `build()` | `string` | Monta e retorna o XML da NFC-e (não assinado). |
| `generateQrCode()` | `string` | Gera URL do QR Code da NFC-e. |

### `Akti\Services\NfeAuditService`

_NfeAuditService — Registra trilha de auditoria para o módulo NF-e._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe NfeAuditService. |
| `record()` | `?int` | Registra uma ação de auditoria. |
| `logView()` | `void` | Atalhos de registro. |

### `Akti\Services\NfeBackupManagementService`

_Service: NfeBackupManagementService_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe NfeBackupManagementService. |
| `executeBackup()` | `array` | Executa backup de XMLs no período/tipo informado. |
| `getHistory()` | `array` | Retorna histórico de backups. |
| `loadConfig()` | `array` | Carrega configurações de backup do banco. |
| `saveConfig()` | `void` | Salva configurações de backup. |

### `Akti\Services\NfeBackupService`

_NfeBackupService — Realiza backup de XMLs de NF-e para storage externo._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe NfeBackupService. |
| `execute()` | `array` | Executa backup de XMLs para o período especificado. |

### `Akti\Services\NfeBatchDownloadService`

_Service: NfeBatchDownloadService_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe NfeBatchDownloadService. |
| `fetchByIds()` | `array` | Busca documentos NF-e por IDs específicos. |

### `Akti\Services\NfeCancellationService`

_NfeCancellationService — Cancelamento de NF-e._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe NfeCancellationService. |
| `cancel()` | `array` | Cancela uma NF-e autorizada. |

### `Akti\Services\NfeContingencyService`

_NfeContingencyService — Gerencia ativação/desativação de contingência NF-e._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe NfeContingencyService. |
| `getStatus()` | `array` | Verifica se o sistema está em contingência. |
| `activate()` | `array` | Ativa contingência manualmente. |
| `deactivate()` | `array` | Desativa contingência e inicia sincronização. |

### `Akti\Services\NfeCorrectionService`

_NfeCorrectionService — Carta de Correção (CC-e) de NF-e._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe NfeCorrectionService. |
| `correction()` | `array` | Envia Carta de Correção (CC-e). |

### `Akti\Services\NfeDanfeCustomizer`

_NfeDanfeCustomizer — Personalização do DANFE._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe NfeDanfeCustomizer. |
| `generate()` | `?string` | Gera DANFE personalizado a partir do XML autorizado. |
| `saveSettings()` | `bool` | Salva configurações de personalização do DANFE. |
| `uploadLogo()` | `array` | Faz upload e salva o logo do DANFE. |
| `getSettings()` | `array` | Retorna configurações atuais. |

### `Akti\Services\NfeDashboardService`

_Service: NfeDashboardService_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe NfeDashboardService. |
| `loadDashboardData()` | `array` | Carrega todos os dados para o dashboard fiscal. |

### `Akti\Services\NfeDetailService`

_Service: NfeDetailService_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe NfeDetailService. |
| `loadInstallmentData()` | `array` | Carrega parcelas vinculadas a um pedido e calcula resumo financeiro. |
| `calculateIbptax()` | `array` | Calcula valor de tributos aproximados via IBPTax para uma NF-e. |

### `Akti\Services\NfeDistDFeService`

_NfeDistDFeService — Consulta DistDFe (Distribuição de Documentos Fiscais Eletrônicos)._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe NfeDistDFeService. |
| `isAvailable()` | `bool` | Verifica se o serviço está disponível. |
| `queryByNSU()` | `array` | Consulta DistDFe por NSU (incremental). |

### `Akti\Services\NfeDownloadService`

_Service: NfeDownloadService_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe NfeDownloadService. |
| `getAuthorizedXml()` | `?string` | Obtém o conteúdo XML autorizado de uma NF-e. |
| `generateDanfe()` | `?string` | Gera o DANFE (PDF) de uma NF-e autorizada. |
| `getCancelXml()` | `?string` | Obtém XML de cancelamento de uma NF-e. |
| `getCceXml()` | `?string` | Obtém XML de carta de correção de uma NF-e. |
| `sendXmlDownload()` | `void` | Envia cabeçalhos e conteúdo XML para download. |
| `sendDanfeDownload()` | `void` | Envia cabeçalhos e conteúdo PDF (DANFE) para visualização inline. |

### `Akti\Services\NfeEmissionService`

_NfeEmissionService — Emissão e inutilização de NF-e._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe NfeEmissionService. |
| `emit()` | `array` | Emite uma NF-e para o pedido. |

### `Akti\Services\NfeExportService`

_NfeExportService — Exportação de relatórios NF-e para Excel (.xlsx)._

| Método | Retorno | Descrição |
|---|---|---|
| `exportToExcel()` | `void` | Exporta dados de relatório NF-e para Excel e envia para download. |

### `Akti\Services\NfeFiscalReportService`

_Service: NfeFiscalReportService_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe NfeFiscalReportService. |
| `getCorrectionReportData()` | `array` | Carrega dados do relatório de Cartas de Correção (CC-e). |
| `getExportData()` | `array` | Retorna dados e título para exportação de relatório. |

### `Akti\Services\NfeManifestationService`

_NfeManifestationService — Manifestação do Destinatário._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe NfeManifestationService. |
| `manifest()` | `array` | Envia manifestação do destinatário. |

### `Akti\Services\NfeOrderDataService`

_Service: NfeOrderDataService_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe NfeOrderDataService. |
| `loadOrderWithItems()` | `array` | Carrega e valida um pedido para emissão. |

### `Akti\Services\NfePdfGenerator`

_NfePdfGenerator — Gera DANFE (PDF) a partir do XML autorizado._

| Método | Retorno | Descrição |
|---|---|---|
| `generate()` | `bool` | Gera o DANFE a partir do XML autorizado. |
| `renderToString()` | `?string` | Retorna o PDF como string (para download direto). |

### `Akti\Services\NfeQueryService`

_NfeQueryService — Consultas SEFAZ (status do serviço, consulta por chave)._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe NfeQueryService. |
| `testConnection()` | `array` | Testa conexão com a SEFAZ (statusServico). |

### `Akti\Services\NfeQueueService`

_NfeQueueService — Gerencia a fila de emissão assíncrona de NF-e._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe NfeQueueService. |
| `enqueue()` | `array` | Enfileira um pedido para emissão. |

### `Akti\Services\NfeSefazClient`

_NfeSefazClient — Gerencia inicialização e acesso ao sped-nfe Tools._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe NfeSefazClient. |
| `isLibraryAvailable()` | `bool` | Verifica se a biblioteca sped-nfe está disponível. |
| `initTools()` | `bool` | Inicializa o Tools do sped-nfe com as credenciais do tenant. |
| `getTools()` | `mixed` | Retorna a instância de Tools (null se não inicializado). |
| `getCredentials()` | `array` | Retorna as credenciais carregadas. |
| `getCredModel()` | `NfeCredential` | Retorna o model de credenciais. |
| `getLogModel()` | `NfeLog` | Retorna o model de log. |

### `Akti\Services\NfeService`

_NfeService — Facade para operações NF-e._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe NfeService. |
| `isLibraryAvailable()` | `bool` | Verifica uma condição booleana. |
| `testConnection()` | `array` | Test connection. |
| `emit()` | `array` | Emite evento ou sinal. |
| `cancel()` | `array` | Cancela operação. |
| `correction()` | `array` | Correction. |
| `checkStatus()` | `array` | Verifica condição ou estado. |
| `getCredentials()` | `array` | Obtém dados específicos. |
| `inutilizar()` | `array` | Inutilizar. |

### `Akti\Services\NfeSintegraService`

_NfeSintegraService — Gera arquivo no formato SINTEGRA._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe NfeSintegraService. |
| `generate()` | `string` | Gera o arquivo SINTEGRA completo para o período. |

### `Akti\Services\NfeSpedFiscalService`

_NfeSpedFiscalService — Gera arquivo SPED Fiscal (EFD ICMS/IPI)._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe NfeSpedFiscalService. |
| `generate()` | `string` | Gera o arquivo SPED Fiscal completo para o período. |

### `Akti\Services\NfeStorageService`

_NfeStorageService — Salva XMLs e DANFEs em disco, organizados por tenant/ano/mês._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe NfeStorageService. |
| `saveXml()` | `?string` | Salva o XML autorizado em disco. |

### `Akti\Services\NfeWebhookManagementService`

_Service: NfeWebhookManagementService_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe NfeWebhookManagementService. |
| `listAll()` | `array` | Lista todos os webhooks configurados. |
| `save()` | `array` | Cria ou atualiza um webhook. |
| `delete()` | `array` | Exclui um webhook. |
| `test()` | `array` | Testa envio de um webhook. |

### `Akti\Services\NfeWebhookService`

_NfeWebhookService — Dispara webhooks para eventos NF-e._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe NfeWebhookService. |
| `dispatch()` | `array` | Dispara webhooks para um evento. |

### `Akti\Services\NfeXmlBuilder`

_NfeXmlBuilder — Monta o XML da NF-e no formato 4.00._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe NfeXmlBuilder. |
| `getCalculatedItems()` | `array` | Retorna os dados fiscais calculados de cada item (após build()). |
| `getCalculatedTotals()` | `array` | Retorna os totais fiscais calculados (após build()). |
| `build()` | `string` | Monta e retorna o XML da NF-e (ainda não assinado). |

### `Akti\Services\NfeXmlValidator`

_NfeXmlValidator — Valida XML da NF-e contra schema XSD antes do envio à SEFAZ._

| Método | Retorno | Descrição |
|---|---|---|
| `validate()` | `array` | Valida o XML assinado contra o schema XSD da NF-e 4.00. |

### `Akti\Services\OrderItemService`

_OrderItemService — Lógica de negócio para itens de pedido._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe OrderItemService. |
| `orderHasPaidInstallments()` | `bool` | Verifica se o pedido possui parcelas pagas (bloqueia alteração de produtos). |
| `clearQuoteConfirmation()` | `void` | Remove a confirmação de orçamento quando produtos são modificados. |

### `Akti\Services\PipelineAlertService`

_Service responsável pela lógica de alertas e atrasos do pipeline._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe PipelineAlertService. |
| `getDelayedOrders()` | `array` | Retorna pedidos atrasados para notificações. |
| `getStats()` | `array` | Retorna estatísticas do pipeline (stats gerais). |
| `getStageGoals()` | `array` | Retorna metas de tempo por etapa. |
| `checkOrderStock()` | `array` | Verifica disponibilidade de estoque dos itens de um pedido num armazém. |
| `countInstallments()` | `int` | Conta parcelas existentes de um pedido. |
| `deleteInstallments()` | `array` | Remove todas as parcelas de um pedido (se nenhuma paga). |

### `Akti\Services\PipelineDetailService`

_Service responsável por agregar todos os dados necessários para a view de detalhes do pipeline._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe PipelineDetailService. |
| `loadDetailData()` | `?array` | Carrega todos os dados necessários para exibir o detalhe de um pedido no pipeline. |
| `loadPrintProductionData()` | `?array` | Carrega dados para a view de impressão da ordem de produção. |
| `loadThermalReceiptData()` | `?array` | Carrega dados para a view de impressão do cupom térmico. |
| `loadProductionBoardData()` | `array` | Carrega dados para o painel de produção (production board). |

### `Akti\Services\PipelinePaymentService`

_Service responsável pela lógica de geração de links de pagamento do pipeline._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe PipelinePaymentService. |
| `generatePaymentLink()` | `array` | Gera link de pagamento via gateway configurado ou fallback legado. |

### `Akti\Services\PipelineService`

_Service responsável pela lógica de movimentação e regras de etapas do pipeline._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe PipelineService. |
| `isPreProduction()` | `bool` | Verifica se a etapa é pré-produção. |
| `isProduction()` | `bool` | Verifica se a etapa é de produção. |
| `transitionNeedsWarehouse()` | `bool` | Verifica se a transição de etapa precisa de seleção de armazém. |
| `checkPaidInstallmentsBlock()` | `?string` | Verifica se a movimentação está bloqueada por parcelas pagas. |
| `getCurrentStage()` | `?string` | Busca a etapa atual de um pedido. |
| `handleStockTransition()` | `array` | Processa a lógica de estoque ao mudar de etapa. |
| `autoGenerateInstallments()` | `bool` | Gera automaticamente as parcelas de pagamento quando o pedido |
| `clearQuoteConfirmation()` | `void` | Remove a confirmação de orçamento quando o pedido é modificado. |

### `Akti\Services\Portal2faService`

_Service: Portal2faService_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe Portal2faService. |
| `validateCode()` | `bool` | Valida código 2FA informado pelo cliente. |
| `resendCode()` | `string` | Gera e retorna novo código 2FA. |
| `toggle()` | `void` | Ativa ou desativa 2FA para um acesso. |

### `Akti\Services\PortalAdminService`

_PortalAdminService — Lógica de negócio da administração do Portal do Cliente._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe PortalAdminService. |
| `getFilteredAccesses()` | `array` | Busca acessos filtrados por pesquisa e status. |

### `Akti\Services\PortalAuthService`

_Service: PortalAuthService_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe PortalAuthService. |
| `loginWithPassword()` | `array` | Processa login por e-mail e senha. |

### `Akti\Services\PortalAvatarService`

_Service: PortalAvatarService_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe PortalAvatarService. |
| `upload()` | `array` | Processa upload de avatar. |

### `Akti\Services\PortalCartService`

_Service: PortalCartService_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe PortalCartService. |
| `getCartSummary()` | `array` | Retorna os dados atuais do carrinho (itens, contagem, total). |
| `addItem()` | `array` | Adiciona um produto ao carrinho. |
| `removeItem()` | `array` | Remove um produto do carrinho. |
| `updateItemQuantity()` | `array` | Atualiza a quantidade de um item no carrinho. |
| `clear()` | `void` | Limpa o carrinho completamente. |

### `Akti\Services\PortalLang`

_PortalLang — Sistema de tradução (i18n) do Portal do Cliente._

| Método | Retorno | Descrição |
|---|---|---|
| `init()` | `void` | Inicializa o sistema de tradução com o idioma especificado. |
| `get()` | `string` | Retorna a tradução de uma chave, com suporte a placeholders. |
| `getLang()` | `string` | Retorna o idioma atual. |
| `getAvailableLanguages()` | `array` | Lista idiomas disponíveis. |

### `Akti\Services\PortalOrderService`

_Service: PortalOrderService_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe PortalOrderService. |
| `listOrders()` | `array` | Carrega lista de pedidos paginada para um cliente. |
| `getOrderDetail()` | `?array` | Carrega detalhes completos de um pedido para um cliente. |
| `approveOrder()` | `array` | Aprova um orçamento. |
| `rejectOrder()` | `array` | Rejeita um orçamento. |
| `cancelApproval()` | `array` | Cancela aprovação/rejeição (volta para pendente). |
| `submitOrder()` | `?int` | Submete pedido a partir do carrinho. |

### `Akti\Services\ProductGradeService`

_Service responsável pela lógica de grades e combinações de produtos._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe ProductGradeService. |
| `getAllGradeTypes()` | `array` | Retorna todos os tipos de grade. |
| `getProductGradesWithValues()` | `array` | Retorna grades com valores de um produto. |
| `getProductCombinations()` | `array` | Retorna combinações de um produto. |
| `createGradeType()` | `array` | Cria um novo tipo de grade via AJAX. |
| `saveProductGrades()` | `void` | Salva grades de um produto. |
| `saveCombinationsData()` | `void` | Salva dados de combinações de um produto. |
| `generateCombinations()` | `array` | Gera combinações (produto cartesiano) com base nos dados de grades. |

### `Akti\Services\ProductImportService`

_Service responsável por toda lógica de importação de produtos._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe ProductImportService. |
| `parseImportFile()` | `array` | Analisa arquivo de importação (CSV/XLS/XLSX) e retorna preview sem efetuar importação. |
| `importProductsMapped()` | `array` | Importa produtos usando mapeamento de colunas definido pelo usuário. |
| `importProductsDirect()` | `array` | Importa produtos diretamente (mapeamento automático por header). |
| `generateImportTemplate()` | `void` | Gera CSV de template para importação. |

### `Akti\Services\ProductionCostService`

_Class ProductionCostService._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe ProductionCostService. |
| `calculateOrderCost()` | `array` | Calcula valor. |
| `getOrderCost()` | `?array` | Obtém dados específicos. |
| `getConfig()` | `array` | Obtém dados específicos. |

### `Akti\Services\ReportExcelService`

_Service: ReportExcelService_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe ReportExcelService. |
| `exportOrdersByPeriod()` | `void` | Exporta dados. |

### `Akti\Services\ReportPdfService`

_Service: ReportPdfService_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe ReportPdfService. |
| `exportOrdersByPeriod()` | `void` | Exporta dados. |
| `exportRevenueByCustomer()` | `void` | Exporta dados. |
| `exportIncomeStatement()` | `void` | Exporta dados. |
| `exportOpenInstallments()` | `void` | Exporta dados. |
| `exportScheduledContacts()` | `void` | Exporta dados. |
| `exportProductCatalog()` | `void` | Exporta dados. |
| `exportStockByWarehouse()` | `void` | Exporta dados. |
| `exportStockMovements()` | `void` | Exporta dados. |
| `exportCommissionsReport()` | `void` | Exporta dados. |
| `exportNfesByPeriod()` | `void` | Exporta dados. |
| `exportTaxSummary()` | `void` | Exporta dados. |
| `exportNfesByCustomer()` | `void` | Exporta dados. |
| `exportCfopSummary()` | `void` | Exporta dados. |
| `exportCancelledNfes()` | `void` | Exporta dados. |
| `exportInutilizacoes()` | `void` | Exporta dados. |
| `exportSefazLogs()` | `void` | Exporta dados. |

### `Akti\Services\SettingsService`

_SettingsService — Lógica de negócio para configurações do sistema._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe SettingsService. |
| `saveCompanySettings()` | `void` | Salva configurações da empresa a partir de um array de chave => valor. |
| `handleLogoUpload()` | `bool` | Processa upload do logo da empresa. |
| `removeLogo()` | `void` | Remove o logo da empresa. |
| `saveBankSettings()` | `array` | Salva configurações bancárias/boleto. |
| `saveFiscalSettings()` | `array` | Salva configurações fiscais da empresa. |
| `saveSecuritySettings()` | `int` | Salva configurações de segurança (timeout de sessão). |

### `Akti\Services\SpedExportService`

_SpedExportService — Exportação para SPED Fiscal e Contábil._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe SpedExportService. |
| `exportFinancialCsv()` | `array` | Exporta dados financeiros no formato CSV contábil padrão. |
| `exportSpedTxt()` | `array` | Exporta lançamentos contábeis no formato SPED simplificado (TXT). |
| `exportChartOfAccounts()` | `array` | Exporta plano de contas simplificado. |

### `Akti\Services\StockMovementService`

_StockMovementService — Lógica de negócio para movimentações de estoque._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe StockMovementService. |
| `processMovement()` | `array` | Processar movimentação de estoque (múltiplos itens). |

### `Akti\Services\SupplyStockMovementService`

_Class SupplyStockMovementService._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe SupplyStockMovementService. |
| `processEntry()` | `array` | Processa entrada de insumos no estoque com suporte a lote/validade e CMP. |

### `Akti\Services\TaxCalculator`

_TaxCalculator — Cálculo dinâmico de impostos para NF-e._

| Método | Retorno | Descrição |
|---|---|---|
| `calculateItem()` | `array` | Calcula todos os impostos de um item para NF-e. |
| `calculateICMS()` | `array` | Calcula ICMS conforme CRT e dados do produto. |
| `calculatePIS()` | `array` | Calcula PIS. |
| `calculateCOFINS()` | `array` | Calcula COFINS. |
| `calculateIPI()` | `array` | Calcula IPI (Imposto sobre Produtos Industrializados). |
| `calculateDIFAL()` | `array` | Calcula DIFAL — Diferencial de Alíquota Interestadual. |
| `calculateTotal()` | `array` | Totaliza impostos de todos os itens. |
| `getAliquotaInterestadual()` | `float` | Retorna alíquota interestadual de ICMS. |
| `calculateIdDest()` | `int` | Calcula o idDest (indicador de destino) dinamicamente. |
| `determineCFOP()` | `string` | Determina o CFOP correto com base na operação e UFs. |
| `validateNCM()` | `bool` | Valida NCM — 8 dígitos numéricos. |
| `validateCFOP()` | `bool` | Valida CFOP — 4 dígitos numéricos, iniciando com 1-7. |
| `mapModFrete()` | `int` | Mapeia modFrete do pedido para código NF-e. |
| `mapIndPres()` | `int` | Mapeia indPres (indicador de presença) da venda. |

### `Akti\Services\ThumbnailService`

_ThumbnailService — Geração e gestão de thumbnails para imagens._

| Método | Retorno | Descrição |
|---|---|---|
| `generate()` | `?string` | Gerar thumbnail de uma imagem. |
| `getOrCreate()` | `?string` | Obter thumbnail existente ou criar um novo. |
| `deleteThumbnails()` | `void` | Deletar todos os thumbnails de uma imagem. |
| `isGdAvailable()` | `bool` | Verificar se GD está disponível. |

### `Akti\Services\TransactionService`

_TransactionService — Camada de Serviço para Transações Financeiras._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe TransactionService. |
| `addTransaction()` | `bool` | Adiciona uma transação financeira. |
| `getById()` | `mixed` | Busca uma transação pelo ID. |
| `update()` | `bool` | Atualiza uma transação existente. |
| `delete()` | `array` | Deleta transação (soft-delete). Captura dados anteriores para auditoria. |
| `getPaginated()` | `array` | Lista transações com paginação e filtros. |
| `registerReversal()` | `bool` | Registra estorno na tabela de transações (chamado pelo InstallmentService). |

### `Akti\Services\TwigRenderer`

_Serviço de renderização Twig para a Loja._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe TwigRenderer. |
| `render()` | `string` | Renderiza um template Twig e retorna o HTML. |
| `getEnvironment()` | `Environment` | Retorna a instância Twig para extensões customizadas. |

### `Akti\Services\WhatsAppService`

_Class WhatsAppService._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe WhatsAppService. |
| `isConfigured()` | `bool` | Verifica uma condição booleana. |
| `send()` | `array` | Envia dados ou notificação. |
| `sendFromTemplate()` | `array` | Envia dados ou notificação. |

### `Akti\Services\WorkflowEngine`

_WorkflowEngine — Evaluates and executes workflow rules on events._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe WorkflowEngine. |
| `process()` | `void` | Process an event: find matching rules, evaluate conditions, execute actions. |

## Controllers (Controladores)

### `Akti\Controllers\AchievementController`

_Class AchievementController._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe AchievementController. |
| `index()` | `mixed` | Exibe a página de listagem. |
| `create()` | `mixed` | Cria um novo registro no banco de dados. |
| `store()` | `mixed` | Processa e armazena um novo registro. |
| `edit()` | `mixed` | Exibe o formulário de edição. |
| `update()` | `mixed` | Atualiza um registro existente. |
| `delete()` | `mixed` | Remove um registro pelo ID. |
| `leaderboard()` | `mixed` | Leaderboard. |
| `award()` | `mixed` | Award. |

### `Akti\Controllers\AiAssistantController`

_Class AiAssistantController._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe AiAssistantController. |
| `index()` | `void` | Chat widget page (standalone or embedded). |
| `send()` | `void` | AJAX: Send a message to the AI. |
| `clearHistory()` | `void` | AJAX: Clear conversation history. |

### `Akti\Controllers\ApiController`

_ApiController — Gera tokens JWT para o frontend consumir a API Node.js._

| Método | Retorno | Descrição |
|---|---|---|
| `token()` | `void` | GET ?page=api&action=token |

### `Akti\Controllers\AttachmentController`

_Class AttachmentController._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe AttachmentController. |
| `index()` | `mixed` | Exibe a página de listagem. |
| `upload()` | `mixed` | Processa upload de arquivo. |
| `download()` | `mixed` | Gera download de arquivo. |
| `delete()` | `mixed` | Remove um registro pelo ID. |
| `listByEntity()` | `mixed` | Lista registros filtrados por critério. |
| `searchEntities()` | `mixed` | Search entities. |

### `Akti\Controllers\AuditController`

_Class AuditController._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe AuditController. |
| `index()` | `mixed` | Exibe a página de listagem. |
| `detail()` | `mixed` | Detail. |
| `exportCsv()` | `mixed` | Exporta dados. |

### `Akti\Controllers\BaseController`

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe BaseController. |

### `Akti\Controllers\BiController`

_Class BiController._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe BiController. |
| `index()` | `void` | Exibe a página de listagem. |
| `drillDown()` | `void` | Drill down. |
| `exportPdf()` | `void` | Exporta dados. |

### `Akti\Controllers\BranchController`

_Class BranchController._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe BranchController. |
| `index()` | `mixed` | Exibe a página de listagem. |
| `create()` | `mixed` | Cria um novo registro no banco de dados. |
| `store()` | `mixed` | Processa e armazena um novo registro. |
| `edit()` | `mixed` | Exibe o formulário de edição. |
| `update()` | `mixed` | Atualiza um registro existente. |
| `delete()` | `mixed` | Remove um registro pelo ID. |

### `Akti\Controllers\CalendarController`

_Class CalendarController._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe CalendarController. |
| `index()` | `mixed` | Exibe a página de listagem. |
| `events()` | `mixed` | Events. |
| `store()` | `mixed` | Processa e armazena um novo registro. |
| `update()` | `mixed` | Atualiza um registro existente. |
| `delete()` | `mixed` | Remove um registro pelo ID. |
| `sync()` | `mixed` | Sincroniza dados. |

### `Akti\Controllers\CatalogController`

_Controller: CatalogController_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe CatalogController. |
| `index()` | `mixed` | Página pública do catálogo (não precisa de login) |
| `generate()` | `mixed` | API: Gerar link de catálogo (chamado via AJAX do pipeline) |

### `Akti\Controllers\CategoryController`

_Class CategoryController._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe CategoryController. |
| `index()` | `mixed` | Exibe a página de listagem. |
| `store()` | `mixed` | Processa e armazena um novo registro. |
| `update()` | `mixed` | Atualiza um registro existente. |
| `delete()` | `mixed` | Remove um registro pelo ID. |
| `storeSub()` | `mixed` | Store sub. |
| `updateSub()` | `mixed` | Update sub. |
| `deleteSub()` | `mixed` | Delete sub. |
| `getInheritedGradesAjax()` | `mixed` | Obtém dados específicos. |
| `toggleCategoryCombinationAjax()` | `mixed` | Alterna estado de propriedade. |
| `toggleSubcategoryCombinationAjax()` | `mixed` | Alterna estado de propriedade. |
| `getProductsForExport()` | `mixed` | Obtém dados específicos. |
| `exportToProducts()` | `mixed` | Exporta dados. |
| `getInheritedSectorsAjax()` | `mixed` | Obtém dados específicos. |

### `Akti\Controllers\CheckoutController`

_Class CheckoutController._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe CheckoutController. |
| `show()` | `void` | GET: Exibe página de checkout (pública). |
| `processPayment()` | `void` | POST (AJAX): Processa pagamento. |
| `tokenizeCard()` | `void` | POST (AJAX): Proxy de tokenização de cartão (evita CORS em ambientes HTTP). |
| `checkStatus()` | `void` | GET (AJAX): Verifica status de pagamento (polling). |
| `confirmation()` | `void` | GET: Página de confirmação de pagamento (3 estados). |

### `Akti\Controllers\CommissionController`

_CommissionController — Controller do Módulo de Comissões_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe CommissionController. |
| `index()` | `mixed` | Exibe a página de listagem. |
| `formas()` | `mixed` | Formas. |
| `storeForma()` | `mixed` | Store forma. |
| `updateForma()` | `mixed` | Update forma. |
| `deleteForma()` | `mixed` | Delete forma. |
| `getFaixas()` | `mixed` | Obtém dados específicos. |
| `grupos()` | `mixed` | Grupos. |
| `linkGrupo()` | `mixed` | Link grupo. |
| `unlinkGrupo()` | `mixed` | Unlink grupo. |
| `usuarios()` | `mixed` | Usuarios. |
| `linkUsuario()` | `mixed` | Link usuario. |
| `unlinkUsuario()` | `mixed` | Unlink usuario. |
| `produtos()` | `mixed` | Produtos. |
| `saveProdutoRegra()` | `mixed` | Salva dados. |
| `deleteProdutoRegra()` | `mixed` | Delete produto regra. |
| `simulador()` | `mixed` | Simulador. |
| `simularCalculo()` | `mixed` | Simular calculo. |
| `calcular()` | `mixed` | Calcula valor. |
| `historico()` | `mixed` | Historico. |
| `getHistoricoPaginated()` | `mixed` | Obtém dados específicos. |
| `aprovar()` | `mixed` | Aprovar. |
| `pagar()` | `mixed` | Pagar. |
| `cancelar()` | `mixed` | Cancela operação. |
| `aprovarLote()` | `mixed` | Aprovar lote. |
| `pagarLote()` | `mixed` | Pagar lote. |
| `configuracoes()` | `mixed` | Configuracoes. |
| `saveConfig()` | `mixed` | Salva dados. |
| `getVendedoresPendentes()` | `mixed` | Retorna lista de vendedores com comissões pendentes (JSON). |
| `getComissoesVendedor()` | `mixed` | Retorna comissões pendentes de um vendedor (JSON). |

### `Akti\Controllers\CustomReportController`

_Class CustomReportController._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe CustomReportController. |
| `index()` | `mixed` | Exibe a página de listagem. |
| `create()` | `mixed` | Cria um novo registro no banco de dados. |
| `store()` | `mixed` | Processa e armazena um novo registro. |
| `edit()` | `mixed` | Exibe o formulário de edição. |
| `update()` | `mixed` | Atualiza um registro existente. |
| `delete()` | `mixed` | Remove um registro pelo ID. |
| `run()` | `mixed` | Executa um processo. |
| `getEntities()` | `mixed` | Obtém dados específicos. |

### `Akti\Controllers\CustomerController`

_Controller: CustomerController_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe CustomerController. |
| `index()` | `mixed` | Exibe a página de listagem. |
| `create()` | `mixed` | Cria um novo registro no banco de dados. |
| `store()` | `mixed` | Processa e armazena um novo registro. |

### `Akti\Controllers\CustomerExportController`

_CustomerExportController — Exportação de clientes (CSV)._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe CustomerExportController. |
| `export()` | `mixed` | Exporta dados. |

### `Akti\Controllers\CustomerImportController`

_Class CustomerImportController._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe CustomerImportController. |
| `parseImportFile()` | `mixed` | Interpreta dados. |
| `importCustomersMapped()` | `mixed` | Importa dados. |
| `getImportProgress()` | `mixed` | Obtém dados específicos. |
| `undoImport()` | `mixed` | Undo import. |

### `Akti\Controllers\DashboardController`

_Class DashboardController._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe DashboardController. |
| `index()` | `mixed` | Exibe a página de listagem. |
| `realtime()` | `mixed` | FEAT-016: Dashboard em tempo real com SSE. |
| `realtimeData()` | `mixed` | FEAT-016: Endpoint JSON para dados do dashboard (polling/SSE). |

### `Akti\Controllers\DashboardWidgetController`

_DashboardWidgetController_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` |  |
| `load()` | `void` | Carrega um widget individual via AJAX. |
| `config()` | `void` | Retorna a configuração de widgets do grupo do usuário (JSON). |

### `Akti\Controllers\EmailMarketingController`

_Class EmailMarketingController._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe EmailMarketingController. |
| `index()` | `mixed` | Exibe a página de listagem. |
| `create()` | `mixed` | Cria um novo registro no banco de dados. |
| `store()` | `mixed` | Processa e armazena um novo registro. |
| `edit()` | `mixed` | Exibe o formulário de edição. |
| `update()` | `mixed` | Atualiza um registro existente. |
| `delete()` | `mixed` | Remove um registro pelo ID. |
| `templates()` | `mixed` | Templates. |
| `createTemplate()` | `mixed` | Create template. |
| `storeTemplate()` | `mixed` | Store template. |
| `editTemplate()` | `mixed` | Edit template. |
| `updateTemplate()` | `mixed` | Update template. |
| `deleteTemplate()` | `mixed` | Delete template. |
| `getTemplateJson()` | `mixed` | Obtém dados específicos. |
| `searchCustomers()` | `mixed` | Search customers. |

### `Akti\Controllers\EmailTrackingController`

_Class EmailTrackingController._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe EmailTrackingController. |
| `open()` | `mixed` | Tracking pixel — records email open |
| `click()` | `mixed` | Click tracking — records link click and redirects |
| `generateHash()` | `string` | Generate a tracking hash for a log entry (HMAC) |

### `Akti\Controllers\EquipmentController`

_Class EquipmentController._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe EquipmentController. |
| `index()` | `mixed` | Exibe a página de listagem. |
| `create()` | `mixed` | Cria um novo registro no banco de dados. |
| `store()` | `mixed` | Processa e armazena um novo registro. |
| `edit()` | `mixed` | Exibe o formulário de edição. |
| `update()` | `mixed` | Atualiza um registro existente. |
| `delete()` | `mixed` | Remove um registro pelo ID. |
| `schedules()` | `mixed` | Agenda tarefa ou evento. |
| `storeSchedule()` | `mixed` | Store schedule. |
| `storeLog()` | `mixed` | Store log. |
| `dashboard()` | `mixed` | Dashboard. |

### `Akti\Controllers\EsgController`

_Class EsgController._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe EsgController. |
| `index()` | `mixed` | Exibe a página de listagem. |
| `create()` | `mixed` | Cria um novo registro no banco de dados. |
| `store()` | `mixed` | Processa e armazena um novo registro. |
| `edit()` | `mixed` | Exibe o formulário de edição. |
| `update()` | `mixed` | Atualiza um registro existente. |
| `delete()` | `mixed` | Remove um registro pelo ID. |
| `addRecord()` | `mixed` | Add record. |
| `setTarget()` | `mixed` | Define valor específico. |
| `dashboard()` | `mixed` | Dashboard. |

### `Akti\Controllers\FileController`

_FileController — Endpoints HTTP para gestão de arquivos._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe FileController. |
| `serve()` | `void` | Servir arquivo com cache headers. |
| `thumb()` | `void` | Gerar e servir thumbnail on-the-fly. |
| `download()` | `void` | Download de arquivo. |
| `upload()` | `void` | Upload genérico via AJAX. |

### `Akti\Controllers\FinancialController`

_FinancialController — Controller principal do módulo financeiro (SLIM)._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe FinancialController. |
| `index()` | `mixed` | Dashboard com cards de resumo, gráficos e alertas. |
| `payments()` | `mixed` | Página unificada com sidebar: parcelas, transações, importação, nova transação. |
| `getSummaryJson()` | `mixed` | Retorna resumo financeiro do mês/ano em JSON. |
| `getDre()` | `mixed` | Retorna DRE em JSON para o período informado. |
| `getCashflow()` | `mixed` | Retorna projeção de fluxo de caixa em JSON. |
| `exportTransactionsCsv()` | `mixed` | Exporta transações em CSV (download direto). |
| `exportDreCsv()` | `mixed` | Exporta DRE em CSV (download direto). |
| `exportCashflowCsv()` | `mixed` | Exporta fluxo de caixa projetado em CSV (download direto). |
| `getAuditLog()` | `mixed` | Retorna log de auditoria financeira em JSON (paginado com filtros). |
| `exportAuditCsv()` | `mixed` | Exporta auditoria financeira em CSV. |
| `getFinancialImportFields()` | `array` | Campos disponíveis para mapeamento de importação financeira. |

### `Akti\Controllers\FinancialImportController`

_FinancialImportController — Controller dedicado a importação financeira (OFX/CSV/Excel)._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe FinancialImportController. |
| `getFinancialImportFields()` | `array` | Campos disponíveis para mapeamento de importação financeira. |
| `parseFile()` | `mixed` | Interpreta dados. |
| `importCsv()` | `mixed` | Importa dados. |
| `importOfxSelected()` | `mixed` | Importa dados. |
| `importOfx()` | `mixed` | Importa dados. |

### `Akti\Controllers\HealthController`

_HealthController_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe HealthController. |
| `ping()` | `void` | Ping simples — para uptime monitors (UptimeRobot, Pingdom, etc.) |
| `check()` | `void` | Health check completo — verifica todos os componentes. |

### `Akti\Controllers\HomeController`

_Class HomeController._

| Método | Retorno | Descrição |
|---|---|---|
| `index()` | `mixed` | Exibe a página de listagem. |

### `Akti\Controllers\InstallmentController`

_InstallmentController — Controller dedicado a parcelas (order_installments)._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe InstallmentController. |
| `installments()` | `mixed` | Installments. |
| `generate()` | `mixed` | Gera conteúdo ou dados. |
| `pay()` | `mixed` | Pay. |
| `confirm()` | `mixed` | Confirm. |
| `cancel()` | `mixed` | Cancela operação. |
| `uploadAttachment()` | `mixed` | Processa upload de arquivo. |
| `removeAttachment()` | `mixed` | Remove attachment. |
| `merge()` | `mixed` | Mescla dados. |
| `split()` | `mixed` | Split. |
| `getPaginated()` | `mixed` | Obtém dados específicos. |
| `getJson()` | `mixed` | Obtém dados específicos. |

### `Akti\Controllers\LojaController`

_Controller público da Loja._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe LojaController. |
| `home()` | `void` | Home page da loja. |
| `collection()` | `void` | Catálogo de produtos. |
| `product()` | `void` | Página de produto individual. |
| `cart()` | `void` | Página do carrinho de compras. |
| `contact()` | `void` | Página de contato. |
| `profile()` | `void` | Página de perfil do cliente. |
| `addToCart()` | `void` | API: Adicionar produto ao carrinho (AJAX POST). |
| `removeFromCart()` | `void` | API: Remover produto do carrinho (AJAX POST). |
| `searchSuggestions()` | `void` | API: Sugestões de busca (AJAX GET). |

### `Akti\Controllers\Master\AdminController`

_Class AdminController._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe AdminController. |
| `index()` | `void` | Exibe a página de listagem. |
| `create()` | `void` | Cria um novo registro no banco de dados. |
| `store()` | `void` | Processa e armazena um novo registro. |

### `Akti\Controllers\Master\BackupController`

_Class BackupController._

| Método | Retorno | Descrição |
|---|---|---|
| `index()` | `void` | Exibe a página de listagem. |
| `run()` | `void` | Executa um processo. |

### `Akti\Controllers\Master\ClientController`

_Class ClientController._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe ClientController. |
| `index()` | `void` | Exibe a página de listagem. |
| `create()` | `void` | Cria um novo registro no banco de dados. |
| `store()` | `void` | Processa e armazena um novo registro. |

### `Akti\Controllers\Master\DashboardController`

_Class DashboardController._

| Método | Retorno | Descrição |
|---|---|---|
| `index()` | `void` | Exibe a página de listagem. |

### `Akti\Controllers\Master\DeployController`

_Class DeployController._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe DeployController. |
| `index()` | `void` | Exibe a página de listagem. |
| `run()` | `void` | Execute the deploy pipeline: git pull → apply pending SQL → clear cache. |

### `Akti\Controllers\Master\GitController`

_Class GitController._

| Método | Retorno | Descrição |
|---|---|---|
| `index()` | `void` | Página principal — renderiza shell com spinners, dados carregam via AJAX |
| `loadRepos()` | `void` | Carrega informações de todos os repositórios e diagnóstico (AJAX) |
| `fetchAll()` | `void` | Busca dados. |
| `fetch()` | `void` | Busca dados. |
| `pull()` | `void` | Pull. |

### `Akti\Controllers\Master\HealthCheckController`

_Class HealthCheckController._

| Método | Retorno | Descrição |
|---|---|---|
| `index()` | `void` | Exibe a página de listagem. |
| `statusJson()` | `void` | JSON endpoint for auto-refresh. |

### `Akti\Controllers\Master\LogController`

_Class LogController._

| Método | Retorno | Descrição |
|---|---|---|
| `index()` | `void` | Exibe a página de listagem. |
| `read()` | `void` | Read. |
| `search()` | `void` | Realiza busca com filtros. |
| `download()` | `void` | Gera download de arquivo. |

### `Akti\Controllers\Master\MasterBaseController`

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe MasterBaseController. |

### `Akti\Controllers\Master\MigrationController`

_Class MigrationController._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe MigrationController. |
| `index()` | `void` | Exibe a página de listagem. |
| `previewSqlFile()` | `void` | Preview content of a pending SQL file. |
| `applySingleFile()` | `void` | Apply a single pending SQL file via AJAX (from the file list). |

### `Akti\Controllers\Master\PlanController`

_Class PlanController._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe PlanController. |
| `index()` | `void` | Exibe a página de listagem. |
| `create()` | `void` | Cria um novo registro no banco de dados. |
| `store()` | `void` | Processa e armazena um novo registro. |

### `Akti\Controllers\NfeCredentialController`

_Controller: NfeCredentialController_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe NfeCredentialController. |
| `index()` | `mixed` | Exibe formulário de credenciais SEFAZ. |
| `store()` | `mixed` | Salva/atualiza credenciais SEFAZ. |
| `update()` | `mixed` | Atualiza credenciais (alias para store). |
| `testConnection()` | `mixed` | Testa a conexão com a SEFAZ. |
| `importIbptax()` | `mixed` | Importa tabela IBPTax a partir de arquivo CSV enviado pelo usuário. |
| `ibptaxStats()` | `mixed` | Retorna estatísticas da tabela IBPTax (AJAX/JSON). |

### `Akti\Controllers\NfeDocumentController`

_Controller: NfeDocumentController_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe NfeDocumentController. |
| `index()` | `mixed` | Painel de Notas Fiscais — listagem com filtros e cards de resumo. |
| `detail()` | `mixed` | Exibe detalhe completo de uma NF-e com dados financeiros e IBPTax. |
| `emit()` | `mixed` | Emite NF-e para um pedido (AJAX/JSON). |

### `Akti\Controllers\NotificationController`

_NotificationController_

| Método | Retorno | Descrição |
|---|---|---|
| `index()` | `void` | Lista as notificações do usuário (JSON para AJAX). |
| `count()` | `void` | Conta notificações não-lidas (JSON endpoint para badge). |
| `markRead()` | `void` | Marca uma notificação como lida. |
| `markAllRead()` | `void` | Marca todas as notificações como lidas. |
| `stream()` | `void` | SSE stream — pushes new notifications to the browser in real time. |

### `Akti\Controllers\OrderController`

_Class OrderController._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe OrderController. |
| `index()` | `mixed` | Exibe a página de listagem. |
| `create()` | `mixed` | Cria um novo registro no banco de dados. |
| `store()` | `mixed` | Processa e armazena um novo registro. |

### `Akti\Controllers\PaymentGatewayController`

_Controller: PaymentGatewayController_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe PaymentGatewayController. |
| `index()` | `mixed` | Lista gateways configurados (aba em settings ou page separada). |
| `edit()` | `mixed` | Editar configuração de um gateway específico. |
| `update()` | `mixed` | Salvar configuração de um gateway (POST). |

### `Akti\Controllers\PipelineController`

_Class PipelineController._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe PipelineController. |
| `index()` | `mixed` | View principal: Kanban Board |
| `move()` | `mixed` | Move registro de posição. |
| `moveAjax()` | `mixed` | Mover pedido via AJAX (drag-and-drop). |
| `detail()` | `mixed` | Detalhes de um pedido no pipeline. |
| `updateDetails()` | `mixed` | Atualizar detalhes do pedido (POST). |
| `settings()` | `mixed` | ConfiguraÃ§Ãµes de metas por etapa |
| `saveSettings()` | `mixed` | Salvar configuraÃ§Ãµes de metas (POST) |
| `alerts()` | `mixed` | API JSON: pedidos atrasados (para notificaÃ§Ãµes). |
| `getPricesByTable()` | `mixed` | API JSON: Retorna preÃ§os de uma tabela de preÃ§o especÃ­fica (AJAX) |
| `checkOrderStock()` | `mixed` | API JSON: Verifica disponibilidade de estoque dos itens de um pedido num armazÃ©m (AJAX). |
| `addExtraCost()` | `mixed` | Adicionar custo extra ao pedido (POST) |
| `deleteExtraCost()` | `mixed` | Remover custo extra do pedido |
| `printProductionOrder()` | `mixed` | Imprimir Ordem de ProduÃ§Ã£o. |
| `togglePreparation()` | `mixed` | Alternar item do checklist de preparaÃ§Ã£o (AJAX POST) |
| `printThermalReceipt()` | `mixed` | Imprimir cupom nÃ£o fiscal (impressora tÃ©rmica). |

### `Akti\Controllers\PipelinePaymentController`

_Class PipelinePaymentController._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe PipelinePaymentController. |
| `countInstallments()` | `mixed` | Conta registros com critérios opcionais. |
| `deleteInstallments()` | `mixed` | Delete installments. |
| `generatePaymentLink()` | `mixed` | Gera conteúdo ou dados. |
| `generateMercadoPagoLink()` | `mixed` | Gera conteúdo ou dados. |
| `confirmDownPayment()` | `mixed` | Confirm down payment. |
| `syncInstallments()` | `mixed` | Sincroniza dados. |
| `updateInstallmentDueDate()` | `mixed` | Update installment due date. |

### `Akti\Controllers\PipelineProductionController`

_PipelineProductionController — Painel de produção e setores._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe PipelineProductionController. |
| `moveSector()` | `mixed` | Move registro de posição. |
| `productionBoard()` | `mixed` | Production board. |
| `getItemLogs()` | `mixed` | Obtém dados específicos. |
| `addItemLog()` | `mixed` | Add item log. |
| `deleteItemLog()` | `mixed` | Delete item log. |

### `Akti\Controllers\PortalAdminController`

_Controller: PortalAdminController_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe PortalAdminController. |
| `index()` | `void` | Listagem de acessos ao portal com dados do cliente. |
| `create()` | `void` | Exibe formulário de criação de acesso ao portal. |
| `store()` | `void` | Processa criação de acesso ao portal (POST). |
| `edit()` | `void` | Exibe formulário de edição de acesso ao portal. |
| `update()` | `void` | Processa atualização de acesso ao portal (POST). |
| `toggleAccess()` | `void` | Ativar/desativar acesso (toggle). POST AJAX. |
| `resetPassword()` | `void` | Resetar senha de um acesso. POST AJAX. |
| `sendMagicLink()` | `void` | Enviar magic link para o cliente. POST AJAX. |

### `Akti\Controllers\PortalController`

_Controller: PortalController_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe PortalController. |
| `index()` | `void` | Página inicial do portal — redireciona conforme estado de autenticação. |
| `login()` | `void` | Exibe a tela de login / Processa o login (POST). |
| `loginMagic()` | `void` | Login via link mágico. |
| `setupPassword()` | `void` | Página temporária para cadastrar senha (via magic link ou senha temporária). |
| `logout()` | `void` | Logout do portal. |

### `Akti\Controllers\ProductController`

_Class ProductController._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe ProductController. |
| `index()` | `mixed` | Exibe a página de listagem. |
| `create()` | `mixed` | Cria um novo registro no banco de dados. |
| `store()` | `mixed` | Processa e armazena um novo registro. |
| `getSubcategories()` | `mixed` | Obtém dados específicos. |
| `createCategoryAjax()` | `mixed` | Create category ajax. |
| `edit()` | `mixed` | Exibe o formulário de edição. |
| `update()` | `mixed` | Atualiza um registro existente. |
| `delete()` | `mixed` | Remove um registro pelo ID. |
| `deleteImage()` | `mixed` | Delete image. |
| `searchSelect2()` | `mixed` | AJAX: Busca produtos para Select2 (substitui a API Node.js). |
| `searchAjax()` | `void` | AJAX: Busca paginada de produtos para Select2 com scroll infinito. |
| `getProductsList()` | `mixed` | AJAX: Lista produtos com filtros e paginação (para a seção de visão geral) |
| `parseImportFile()` | `mixed` | AJAX: Analisa arquivo de importação (CSV/XLS/XLSX) e retorna preview. |
| `importProductsMapped()` | `mixed` | AJAX: Importa produtos usando mapeamento de colunas definido pelo usuário. |
| `downloadImportTemplate()` | `mixed` | Download CSV import template. |
| `importProducts()` | `mixed` | AJAX: Import products from CSV/XLS file (mapeamento automático por header). |
| `createGradeTypeAjax()` | `mixed` | AJAX: Create a new grade type on the fly. |
| `getGradeTypes()` | `mixed` | AJAX: Get grade types list. |
| `generateCombinationsAjax()` | `mixed` | AJAX: Generate and return combinations based on provided grades data. |

### `Akti\Controllers\ProductGradeController`

_ProductGradeController — Gerenciamento de grades de produtos._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe ProductGradeController. |
| `createGradeTypeAjax()` | `mixed` | Create grade type ajax. |
| `getGradeTypes()` | `mixed` | Obtém dados específicos. |
| `generateCombinationsAjax()` | `mixed` | Gera conteúdo ou dados. |

### `Akti\Controllers\ProductImportController`

_ProductImportController — Importação de produtos (CSV/Excel)._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe ProductImportController. |
| `parseImportFile()` | `mixed` | Interpreta dados. |
| `importProductsMapped()` | `mixed` | Importa dados. |
| `downloadImportTemplate()` | `mixed` | Gera download de arquivo. |
| `importProducts()` | `mixed` | Importa dados. |

### `Akti\Controllers\ProductionCostController`

_Class ProductionCostController._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe ProductionCostController. |
| `index()` | `mixed` | Exibe a página de listagem. |
| `saveConfig()` | `mixed` | Salva dados. |
| `calculate()` | `mixed` | Calcula valor. |
| `marginReport()` | `mixed` | Margin report. |

### `Akti\Controllers\QualityController`

_Class QualityController._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe QualityController. |
| `index()` | `mixed` | Exibe a página de listagem. |
| `create()` | `mixed` | Cria um novo registro no banco de dados. |
| `store()` | `mixed` | Processa e armazena um novo registro. |
| `edit()` | `mixed` | Exibe o formulário de edição. |
| `update()` | `mixed` | Atualiza um registro existente. |
| `delete()` | `mixed` | Remove um registro pelo ID. |
| `addItem()` | `mixed` | Add item. |
| `removeItem()` | `mixed` | Remove item. |
| `inspect()` | `mixed` | Inspect. |
| `storeInspection()` | `mixed` | Store inspection. |
| `nonConformities()` | `mixed` | Non conformities. |
| `storeNonConformity()` | `mixed` | Store non conformity. |
| `resolveNonConformity()` | `mixed` | Resolve dependência ou valor. |

### `Akti\Controllers\QuoteController`

_Class QuoteController._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe QuoteController. |
| `index()` | `mixed` | Exibe a página de listagem. |
| `create()` | `mixed` | Cria um novo registro no banco de dados. |
| `store()` | `mixed` | Processa e armazena um novo registro. |
| `edit()` | `mixed` | Exibe o formulário de edição. |
| `update()` | `mixed` | Atualiza um registro existente. |
| `delete()` | `mixed` | Remove um registro pelo ID. |
| `approve()` | `mixed` | Aprova registro ou operação. |
| `convertToOrder()` | `mixed` | Converte dados de um formato para outro. |
| `addItem()` | `mixed` | Add item. |
| `removeItem()` | `mixed` | Remove item. |

### `Akti\Controllers\RecurringTransactionController`

_RecurringTransactionController — CRUD + processamento de recorrências._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe RecurringTransactionController. |
| `()` | `mixed` | Lista todas as recorrências (JSON). |
| `store()` | `mixed` | Cria nova recorrência (POST JSON). |
| `update()` | `mixed` | Atualiza recorrência existente (POST JSON). |
| `delete()` | `mixed` | Exclui uma recorrência (POST). |
| `toggle()` | `mixed` | Ativa/desativa recorrência (POST). |
| `process()` | `mixed` | Processa recorrências pendentes do mês (POST). |
| `get()` | `mixed` | Busca uma recorrência por ID (GET). |

### `Akti\Controllers\ReportController`

_Controller: ReportController_

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe ReportController. |
| `index()` | `void` | Exibe a tela de filtros e seleção de relatórios. |
| `exportPdf()` | `void` | Gera e envia um PDF para download conforme o tipo de relatório. |
| `exportExcel()` | `void` | Gera e envia um XLSX para download conforme o tipo de relatório. |

### `Akti\Controllers\SearchController`

_SearchController_

| Método | Retorno | Descrição |
|---|---|---|
| `query()` | `void` | Busca global AJAX — retorna JSON com resultados agrupados. |

### `Akti\Controllers\SectorController`

_Class SectorController._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe SectorController. |
| `index()` | `mixed` | Exibe a página de listagem. |
| `store()` | `mixed` | Processa e armazena um novo registro. |
| `update()` | `mixed` | Atualiza um registro existente. |
| `delete()` | `mixed` | Remove um registro pelo ID. |

### `Akti\Controllers\SettingsController`

_Class SettingsController._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe SettingsController. |
| `index()` | `mixed` | Página de configurações da empresa |
| `saveCompany()` | `mixed` | Salvar configurações da empresa (POST) |
| `saveBankSettings()` | `mixed` | Salvar configurações bancárias para boletos (POST) |
| `priceTablesIndex()` | `mixed` | Página dedicada de Tabelas de Preço (menu principal) |
| `createPriceTable()` | `mixed` | Criar tabela de preço (POST) |
| `updatePriceTable()` | `mixed` | Atualizar tabela de preço (POST) |
| `deletePriceTable()` | `mixed` | Excluir tabela de preço |
| `editPriceTable()` | `mixed` | Editar itens de uma tabela de preço |
| `savePriceItem()` | `mixed` | Adicionar/atualizar item na tabela de preço (POST) |
| `deletePriceItem()` | `mixed` | Remover item da tabela de preço |
| `getPricesForCustomer()` | `mixed` | API: Retorna preços para um cliente (AJAX/JSON) |
| `addPreparationStep()` | `mixed` | Adicionar nova etapa de preparo (POST) |
| `updatePreparationStep()` | `mixed` | Atualizar etapa de preparo (POST) |
| `deletePreparationStep()` | `mixed` | Excluir etapa de preparo |
| `togglePreparationStep()` | `mixed` | Ativar/desativar etapa de preparo (AJAX) |
| `saveFiscalSettings()` | `mixed` | Salvar configurações fiscais da empresa (POST) |
| `saveSecuritySettings()` | `mixed` | Salvar configurações de segurança (POST) |
| `saveDashboardWidgets()` | `mixed` | Salvar configuração de widgets do dashboard para um grupo (AJAX/JSON) |
| `resetDashboardWidgets()` | `mixed` | Resetar configuração de widgets de um grupo para o padrão global (AJAX/JSON) |

### `Akti\Controllers\ShipmentController`

_Class ShipmentController._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe ShipmentController. |
| `index()` | `mixed` | Exibe a página de listagem. |
| `create()` | `mixed` | Cria um novo registro no banco de dados. |
| `store()` | `mixed` | Processa e armazena um novo registro. |
| `view()` | `mixed` | View. |
| `addEvent()` | `mixed` | Add event. |
| `carriers()` | `mixed` | Carriers. |
| `saveCarrier()` | `mixed` | Salva dados. |
| `dashboard()` | `mixed` | Dashboard. |
| `delete()` | `mixed` | Remove um registro pelo ID. |

### `Akti\Controllers\SiteBuilderController`

_Controller do Site Builder._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe SiteBuilderController. |
| `index()` | `void` | Página principal do Site Builder (editor de configurações + preview). |
| `getSettings()` | `void` | Retorna todas as configurações (AJAX GET). |
| `saveSettings()` | `void` | Salva configurações de um grupo (POST AJAX). |
| `preview()` | `void` | Preview da loja (renderiza no iframe). |
| `uploadImage()` | `void` | Upload de imagem para o site builder (POST AJAX). |

### `Akti\Controllers\StockController`

_Class StockController._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe StockController. |
| `index()` | `mixed` | Exibe a página de listagem. |
| `warehouses()` | `mixed` | Warehouses. |
| `storeWarehouse()` | `mixed` | Store warehouse. |

### `Akti\Controllers\SupplierController`

_Class SupplierController._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe SupplierController. |
| `index()` | `mixed` | Exibe a página de listagem. |
| `create()` | `mixed` | Cria um novo registro no banco de dados. |
| `store()` | `mixed` | Processa e armazena um novo registro. |
| `edit()` | `mixed` | Exibe o formulário de edição. |
| `update()` | `mixed` | Atualiza um registro existente. |
| `delete()` | `mixed` | Remove um registro pelo ID. |
| `purchases()` | `mixed` | Purchases. |
| `createPurchase()` | `mixed` | Create purchase. |
| `storePurchase()` | `mixed` | Store purchase. |
| `editPurchase()` | `mixed` | Edit purchase. |
| `receivePurchase()` | `mixed` | Receive purchase. |

### `Akti\Controllers\SupplyController`

_Class SupplyController._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe SupplyController. |
| `index()` | `mixed` | Exibe a página de listagem. |
| `create()` | `mixed` | Cria um novo registro no banco de dados. |
| `store()` | `mixed` | Processa e armazena um novo registro. |
| `edit()` | `mixed` | Exibe o formulário de edição. |
| `update()` | `mixed` | Atualiza um registro existente. |
| `delete()` | `mixed` | Remove um registro pelo ID. |
| `createCategoryAjax()` | `mixed` | Create category ajax. |
| `getCategoriesAjax()` | `mixed` | Obtém dados específicos. |
| `searchSelect2()` | `mixed` | Search select2. |
| `getSuppliers()` | `mixed` | Obtém dados específicos. |
| `linkSupplier()` | `mixed` | Link supplier. |
| `updateSupplierLink()` | `mixed` | Update supplier link. |
| `unlinkSupplier()` | `mixed` | Unlink supplier. |
| `searchSuppliers()` | `mixed` | Search suppliers. |
| `getPriceHistory()` | `mixed` | Obtém dados específicos. |
| `getProductSupplies()` | `mixed` | Obtém dados específicos. |
| `addProductSupply()` | `mixed` | Add product supply. |
| `updateProductSupply()` | `mixed` | Update product supply. |
| `removeProductSupply()` | `mixed` | Remove product supply. |
| `estimateConsumption()` | `mixed` | Estimate consumption. |
| `getSupplyProducts()` | `mixed` | Obtém dados específicos. |
| `getWhereUsedImpact()` | `mixed` | Obtém dados específicos. |
| `applyBOMCostUpdate()` | `mixed` | Apply b o m cost update. |

### `Akti\Controllers\SupplyStockController`

_Class SupplyStockController._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe SupplyStockController. |
| `index()` | `void` | Exibe a página de listagem. |
| `entry()` | `void` | Entry. |
| `storeEntry()` | `void` | Store entry. |
| `()` | `void` | . |
| `storeExit()` | `void` | Store exit. |
| `transfer()` | `void` | Transfer. |
| `storeTransfer()` | `void` | Store transfer. |
| `adjust()` | `void` | Adjust. |
| `storeAdjust()` | `void` | Store adjust. |
| `movements()` | `void` | Move registro de posição. |
| `searchSupplies()` | `void` | Search supplies. |
| `getStockInfo()` | `void` | Obtém dados específicos. |
| `getBatches()` | `void` | Obtém dados específicos. |
| `getStockItems()` | `void` | Obtém dados específicos. |
| `reorderSuggestions()` | `void` | Reordena registros. |
| `getDashboard()` | `void` | Obtém dados específicos. |

### `Akti\Controllers\TicketController`

_Class TicketController._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe TicketController. |
| `index()` | `mixed` | Exibe a página de listagem. |
| `create()` | `mixed` | Cria um novo registro no banco de dados. |
| `store()` | `mixed` | Processa e armazena um novo registro. |
| `view()` | `mixed` | View. |
| `addMessage()` | `mixed` | Add message. |
| `updateStatus()` | `mixed` | Update status. |
| `dashboard()` | `mixed` | Dashboard. |
| `delete()` | `mixed` | Remove um registro pelo ID. |

### `Akti\Controllers\TransactionController`

_TransactionController — Controller dedicado a transações financeiras (entradas/saídas)._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe TransactionController. |
| `index()` | `mixed` | Exibe a página de listagem. |
| `add()` | `mixed` | Add. |
| `delete()` | `mixed` | Remove um registro pelo ID. |
| `get()` | `mixed` | Get. |
| `update()` | `mixed` | Atualiza um registro existente. |
| `getPaginated()` | `mixed` | Obtém dados específicos. |

### `Akti\Controllers\UserController`

_Class UserController._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe UserController. |
| `index()` | `mixed` | Exibe a página de listagem. |
| `create()` | `mixed` | Cria um novo registro no banco de dados. |
| `store()` | `mixed` | Processa e armazena um novo registro. |
| `edit()` | `mixed` | Exibe o formulário de edição. |
| `update()` | `mixed` | Atualiza um registro existente. |
| `delete()` | `mixed` | Remove um registro pelo ID. |
| `groups()` | `mixed` | Groups. |
| `createGroup()` | `mixed` | Create group. |
| `updateGroup()` | `mixed` | Update group. |
| `deleteGroup()` | `mixed` | Delete group. |
| `profile()` | `mixed` | Profile. |
| `updateProfile()` | `mixed` | Update profile. |
| `login()` | `mixed` | Processa a autenticação do usuário. |
| `logout()` | `mixed` | Encerra a sessão do usuário. |

### `Akti\Controllers\WalkthroughController`

_Class WalkthroughController._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe WalkthroughController. |
| `checkStatus()` | `mixed` | API: Verifica se o usuário precisa ver o walkthrough. |
| `start()` | `mixed` | API: Marca o walkthrough como iniciado. |
| `complete()` | `mixed` | API: Marca o walkthrough como completo. |
| `skip()` | `mixed` | API: Marca o walkthrough como pulado. |
| `saveStep()` | `mixed` | API: Salva o passo atual do walkthrough. |
| `reset()` | `mixed` | API: Reseta o walkthrough (para o admin permitir que o usuário veja de novo). |
| `manual()` | `mixed` | Página de manual/documentação embutida. |
| `getSteps()` | `mixed` | Retorna os passos do walkthrough baseados no role e permissões do usuário. |

### `Akti\Controllers\WebhookController`

_WebhookController — Recebe notificações (webhooks) de gateways de pagamento via PHP._

| Método | Retorno | Descrição |
|---|---|---|
| `handle()` | `void` | POST ?page=webhook&action=handle&gateway=<slug> |

### `Akti\Controllers\WhatsAppController`

_Class WhatsAppController._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe WhatsAppController. |
| `index()` | `mixed` | Exibe a página de listagem. |
| `saveConfig()` | `mixed` | Salva dados. |
| `saveTemplate()` | `mixed` | Salva dados. |
| `send()` | `mixed` | Envia dados ou notificação. |
| `testConnection()` | `mixed` | Test connection. |

### `Akti\Controllers\WorkflowController`

_Class WorkflowController._

| Método | Retorno | Descrição |
|---|---|---|
| `__construct()` | `mixed` | Construtor da classe WorkflowController. |
| `index()` | `mixed` | Exibe a página de listagem. |
| `create()` | `mixed` | Cria um novo registro no banco de dados. |
| `store()` | `mixed` | Processa e armazena um novo registro. |
| `edit()` | `mixed` | Exibe o formulário de edição. |
| `update()` | `mixed` | Atualiza um registro existente. |
| `delete()` | `mixed` | Remove um registro pelo ID. |
| `toggle()` | `mixed` | Alterna estado de propriedade. |
| `logs()` | `mixed` | Registra informação no log. |
| `reorder()` | `mixed` | Reordena registros. |

