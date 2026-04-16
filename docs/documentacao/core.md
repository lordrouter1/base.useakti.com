# Core (Núcleo)

> Classes fundamentais do framework: Application, Router, Container, Security, EventDispatcher, Cache, Log, etc.

**Total de arquivos:** 12

---

## Índice

- [Application](#application) — `app/core/Application.php`
- [Cache](#cache) — `app/core/Cache.php`
- [Container](#container) — `app/core/Container.php`
- [ContainerException](#containerexception) — `app/core/ContainerException.php`
- [Event](#event) — `app/core/Event.php`
- [EventDispatcher](#eventdispatcher) — `app/core/EventDispatcher.php`
- [Log](#log) — `app/core/Log.php`
- [ModuleBootloader](#modulebootloader) — `app/core/ModuleBootloader.php`
- [NotFoundException](#notfoundexception) — `app/core/NotFoundException.php`
- [Router](#router) — `app/core/Router.php`
- [](#) — `app/core/Router.php`
- [Security](#security) — `app/core/Security.php`
- [TransactionManager](#transactionmanager) — `app/core/TransactionManager.php`

---

## Application

**Tipo:** Class  
**Arquivo:** `app/core/Application.php`  
**Namespace:** `Akti\Core`  

Application — encapsula o ciclo de vida da requisição HTTP.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$basePath` | Não |
| private | `$router` | Não |
| private | `$page` | Não |
| private | `$action` | Não |
| private | `$container` | Não |
| private | `$sessionDb` | Não |

### Métodos

#### Métodos Public

##### `__construct(string $basePath, ContainerInterface $container)`

Construtor da classe Application.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$basePath` | `string` | Caminho base da aplicação |
| `$container` | `ContainerInterface` | Container de dependências |

---

##### `boot(): void`

Boot — inicializa security headers, sessão, tenant, router.

---

##### `handle(): bool`

Handle — resolve autenticação, permissões e CSRF.

---

##### `dispatch(): void`

Dispatch — despacha a rota autenticada.

---

#### Métodos Private

##### `handleKeepalive(): void`

Manipula uma ação ou evento.

**Retorno:** `void — */`

---

##### `checkPermissions(): void`

Verifica se o usuário tem permissão de acesso.

**Retorno:** `void — */`

---

## Cache

**Tipo:** Class  
**Arquivo:** `app/core/Cache.php`  
**Namespace:** `Akti\Core`  

Sistema de cache em arquivo com suporte a TTL e invalidação.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$dir` | Sim |

### Métodos

#### Métodos Public

##### `static remember(string $key, int $ttl, callable $callback): mixed`

Retrieve a cached value, or compute and store it if missing/expired.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$key` | `string` | Cache key |
| `$ttl` | `int` | Time-to-live in seconds |
| `$callback` | `callable` | Function that returns the value to cache |

**Retorno:** `mixed — */`

---

##### `static get(string $key): mixed`

Get.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$key` | `string` | Chave identificadora |

**Retorno:** `mixed — */`

---

##### `static set(string $key, mixed $value, int $ttl = 60): void`

Set.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$key` | `string` | Chave identificadora |
| `$value` | `mixed` | Valor |
| `$ttl` | `int` | Tempo de vida (time-to-live) em segundos |

**Retorno:** `void — */`

---

##### `static forget(string $key): void`

Forget.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$key` | `string` | Chave identificadora |

**Retorno:** `void — */`

---

##### `static forgetByPrefix(string $prefix): void`

Invalidate all cache entries matching a prefix.

---

##### `static flush(): void`

Descarrega dados pendentes.

**Retorno:** `void — */`

---

#### Métodos Private

##### `static dir(): string`

Dir.

**Retorno:** `string — */`

---

##### `static filePath(string $key): string`

File path.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$key` | `string` | Chave identificadora |

**Retorno:** `string — */`

---

## Container

**Tipo:** Class  
**Arquivo:** `app/core/Container.php`  
**Namespace:** `Akti\Core`  
**Implementa:** `ContainerInterface`  

Container de injeção de dependências compatível com PSR-11.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$bindings` | Não |
| private | `$instances` | Não |
| private | `$shared` | Não |
| private | `$reflectionCache` | Não |

### Métodos

#### Métodos Public

##### `bind(string $id, callable $factory, bool $shared = false): void`

Registra um binding no container.

---

##### `singleton(string $id, callable $factory): void`

Registra um binding como singleton.

---

##### `instance(string $id, mixed $value): void`

Registra uma instância já pronta.

---

##### `get(string $id): mixed`

---

### Funções auxiliares do arquivo

#### `has(string $id)`

---

#### `autoWire(string $class)`

---

#### `getReflection(string $class)`

---

## ContainerException

**Tipo:** Class  
**Arquivo:** `app/core/ContainerException.php`  
**Namespace:** `Akti\Core`  
**Herda de:** `\RuntimeException`  
**Implementa:** `ContainerExceptionInterface`  

Exceção lançada quando ocorre erro no container de dependências.

## Event

**Tipo:** Class  
**Arquivo:** `app/core/Event.php`  
**Namespace:** `Akti\Core`  

Event — Value Object para dados de eventos do sistema.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| public | `$name` | Não |
| public | `$data` | Não |
| public | `$timestamp` | Não |
| public | `$userId` | Não |
| public | `$tenantDb` | Não |

### Métodos

#### Métodos Public

##### `__construct(string $name, array $data = [])`

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$name` | `string` | Nome do evento |
| `$data` | `array` | Dados do evento |

---

##### `getData(): array`

Retorna os dados do evento.

**Retorno:** `array — */`

---

## EventDispatcher

**Tipo:** Class  
**Arquivo:** `app/core/EventDispatcher.php`  
**Namespace:** `Akti\Core`  

EventDispatcher — Sistema de eventos nativo do Akti.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$listeners` | Sim |

### Métodos

#### Métodos Public

##### `static listen(string $event, callable $listener): void`

Registra um listener para um evento nomeado.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$event` | `string` | Nome do evento (ex: 'model.order.created') |
| `$listener` | `callable` | Callable que recebe um Event como parâmetro |

---

##### `static dispatch(string $event, Event $payload): void`

Dispara um evento para todos os listeners registrados.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$event` | `string` | Nome do evento |
| `$payload` | `Event` | Objeto Event com os dados |

---

##### `static forget(string $event): void`

Remove todos os listeners de um evento específico.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$event` | `string` | Nome do evento |

---

##### `static getRegistered(): array`

Retorna todos os eventos registrados com seus listeners.

**Retorno:** `array<string, — callable[]>`

---

#### Métodos Private

##### `static logError(string $event, \Throwable $e): void`

Registra erro de listener no log de eventos.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$event` | `string` | Nome do evento |
| `$e` | `\Throwable` | Exceção capturada |

---

## Log

**Tipo:** Class  
**Arquivo:** `app/core/Log.php`  
**Namespace:** `Akti\Core`  

Log — Structured Logging (PSR-3 inspired)

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$logDir` | Sim |
| private | `$channel` | Não |

### Métodos

#### Métodos Public

##### `__construct(string $channel = 'general')`

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$channel` | `string` | */ |

---

##### `static channel(string $channel): self`

Cria uma instância de Log com um canal específico.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$channel` | `string` | Ex: 'security', 'financial', 'api', 'cron' |

**Retorno:** `self — */`

---

##### `static emergency(string $message, array $context = []): void`

Emergency.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$message` | `string` | Mensagem |
| `$context` | `array` | Contexto adicional |

**Retorno:** `void — */`

---

##### `static alert(string $message, array $context = []): void`

Alert.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$message` | `string` | Mensagem |
| `$context` | `array` | Contexto adicional |

**Retorno:** `void — */`

---

##### `static critical(string $message, array $context = []): void`

Critical.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$message` | `string` | Mensagem |
| `$context` | `array` | Contexto adicional |

**Retorno:** `void — */`

---

##### `static error(string $message, array $context = []): void`

Error.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$message` | `string` | Mensagem |
| `$context` | `array` | Contexto adicional |

**Retorno:** `void — */`

---

##### `static warning(string $message, array $context = []): void`

Warning.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$message` | `string` | Mensagem |
| `$context` | `array` | Contexto adicional |

**Retorno:** `void — */`

---

##### `static notice(string $message, array $context = []): void`

Notice.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$message` | `string` | Mensagem |
| `$context` | `array` | Contexto adicional |

**Retorno:** `void — */`

---

##### `static info(string $message, array $context = []): void`

Info.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$message` | `string` | Mensagem |
| `$context` | `array` | Contexto adicional |

**Retorno:** `void — */`

---

##### `static debug(string $message, array $context = []): void`

Debug.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$message` | `string` | Mensagem |
| `$context` | `array` | Contexto adicional |

**Retorno:** `void — */`

---

##### `log(string $level, string $message, array $context = []): void`

Grava um log estruturado em JSON.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$level` | `string` | * @param string $message |
| `$context` | `array` | */ |

---

### Funções auxiliares do arquivo

#### `cleanup(int $daysToKeep = 30)`

---

## ModuleBootloader

**Tipo:** Class  
**Arquivo:** `app/core/ModuleBootloader.php`  
**Namespace:** `Akti\Core`  

Bootloader central de módulos por tenant.

### Métodos

#### Métodos Public

##### `static isModuleEnabled(string $moduleSlug): bool`

Verifica uma condição booleana.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$moduleSlug` | `string` | Module slug |

**Retorno:** `bool — */`

---

##### `static canAccessPage(string $page): bool`

Verifica permissão ou capacidade.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$page` | `string` | Número da página |

**Retorno:** `bool — */`

---

##### `static canAccessSettingsTab(string $tab): bool`

Verifica permissão ou capacidade.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$tab` | `string` | Tab |

**Retorno:** `bool — */`

---

##### `static sanitizeSettingsTab(?string $tab, string $fallback = 'company'): string`

Sanitiza dados de entrada.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$tab` | `string|null` | Tab |
| `$fallback` | `string` | Fallback |

**Retorno:** `string — */`

---

##### `static getModuleLabel(string $moduleSlug): string`

Retorna o label amigável do módulo.

---

##### `static getDisabledModuleJS(string $moduleSlug): string`

Retorna JavaScript inline para exibir um SweetAlert2 de módulo desabilitado.

---

### Funções auxiliares do arquivo

#### `getEnabledModules()`

---

#### `injectJS()`

---

## NotFoundException

**Tipo:** Class  
**Arquivo:** `app/core/NotFoundException.php`  
**Namespace:** `Akti\Core`  
**Herda de:** `\RuntimeException`  
**Implementa:** `NotFoundExceptionInterface`  

Exceção lançada quando um recurso não é encontrado.

## Router

**Tipo:** Class  
**Arquivo:** `app/core/Router.php`  
**Namespace:** `Akti\Core`  

Router baseado em mapa de rotas — Akti

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$routes` | Não |
| private | `$page` | Não |
| private | `$action` | Não |

### Métodos

#### Métodos Public

##### `__construct(string $routesFile, ?ContainerInterface $container = null)`

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$routesFile` | `string` | Caminho absoluto para o arquivo routes.php |
| `$container` | `ContainerInterface|null` | Container PSR-11 para resolução de dependências |

---

## 

**Tipo:** Class  
**Arquivo:** `app/core/Router.php`  
**Namespace:** `Akti\Core`  

### Funções auxiliares do arquivo

#### `getPage()`

---

#### `getAction()`

---

#### `getRouteConfig(string $page)`

---

#### `isPublicPage()`

---

#### `hasBeforeAuth()`

---

#### `routeExists()`

---

#### `dispatch()`

---

#### `resolveControllerClass(string $name)`

---

#### `resolveAction(array $route)`

---

#### `createController(string $class)`

---

#### `handle404()`

---

## Security

**Tipo:** Class  
**Arquivo:** `app/core/Security.php`  
**Namespace:** `Akti\Core`  

Security — Proteção CSRF centralizada para o sistema Akti.

### Métodos

#### Métodos Public

##### `static generateCsrfToken(): string`

Gera (ou reutiliza) um token CSRF criptograficamente seguro.

**Retorno:** `string — Token CSRF (64 caracteres hexadecimais)`

---

##### `static getToken(): ?string`

Retorna o token CSRF atual da sessão (sem gerar novo).

**Retorno:** `string|null — Token atual ou null se não existir`

---

##### `static validateCsrfToken(?string $token): bool`

Valida o token CSRF recebido contra o token da sessão.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$token` | `string|null` | Token recebido do formulário ou header |

**Retorno:** `bool — true se válido, false se inválido`

---

##### `static logCsrfFailure(?string $receivedToken = null): void`

Registra uma falha de validação CSRF no log de segurança.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$receivedToken` | `string|null` | Token que foi recebido (para log parcial) |

---

### Funções auxiliares do arquivo

#### `handleCsrfFailure(?string $receivedToken = null)`

---

#### `isAjaxRequest()`

---

#### `getClientIp()`

---

## TransactionManager

**Tipo:** Class  
**Arquivo:** `app/core/TransactionManager.php`  
**Namespace:** `Akti\Core`  

Gerenciador de transações de banco de dados com suporte a savepoints.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$db` | Não |
| private | `$level` | Não |

### Métodos

#### Métodos Public

##### `__construct(PDO $db)`

Construtor da classe TransactionManager.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$db` | `PDO` | Conexão PDO com o banco de dados |

---

##### `begin(): void`

Begin.

**Retorno:** `void — */`

---

### Funções auxiliares do arquivo

#### `commit()`

---

#### `rollBack()`

---

#### `getLevel()`

---

#### `inTransaction()`

---

#### `transaction(callable $callback)`

---

