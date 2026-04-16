# Middleware

> Middleware HTTP: CSRF, rate limiting, security headers, autenticação do portal, Sentry.

**Total de arquivos:** 5

---

## Índice

- [CsrfMiddleware](#csrfmiddleware) — `app/middleware/CsrfMiddleware.php`
- [PortalAuthMiddleware](#portalauthmiddleware) — `app/middleware/PortalAuthMiddleware.php`
- [RateLimitMiddleware](#ratelimitmiddleware) — `app/middleware/RateLimitMiddleware.php`
- [SecurityHeadersMiddleware](#securityheadersmiddleware) — `app/middleware/SecurityHeadersMiddleware.php`
- [SentryMiddleware](#sentrymiddleware) — `app/middleware/SentryMiddleware.php`

---

## CsrfMiddleware

**Tipo:** Class  
**Arquivo:** `app/middleware/CsrfMiddleware.php`  
**Namespace:** `Akti\Middleware`  

CsrfMiddleware — Intercepta requisições que alteram dados e valida token CSRF.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$exemptRoutes` | Sim |

### Métodos

#### Métodos Public

##### `static handle(): void`

Verifica se a requisição atual precisa de validação CSRF e, se sim, valida.

**Retorno:** `void — Retorna normalmente se a validação passa. Aborta com 403 se falhar.`

---

### Funções auxiliares do arquivo

#### `extractToken()`

---

#### `isExempt()`

---

#### `addExemptRoute(string $route)`

---

#### `getExemptRoutes()`

---

## PortalAuthMiddleware

**Tipo:** Class  
**Arquivo:** `app/middleware/PortalAuthMiddleware.php`  
**Namespace:** `Akti\Middleware`  

PortalAuthMiddleware — Verificação de autenticação do Portal do Cliente.

### Métodos

#### Métodos Public

##### `static check(): void`

Verifica se o cliente está autenticado no portal.

**Retorno:** `void — */`

---

##### `static isAuthenticated(): bool`

Verifica se o cliente está autenticado (sem redirecionar).

**Retorno:** `bool — */`

---

##### `static getCustomerId(): ?int`

Retorna o customer_id da sessão do portal.

**Retorno:** `int|null — */`

---

##### `static getAccessId(): ?int`

Retorna o access_id da sessão do portal.

**Retorno:** `int|null — */`

---

##### `static getLang(): string`

Retorna o idioma do cliente na sessão.

**Retorno:** `string — */`

---

##### `static login(int $customerId, int $accessId, string $customerName, string $email, string $lang = 'pt-br'): void`

Inicia a sessão do portal para o cliente.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$customerId` | `int` | * @param int    $accessId |
| `$customerName` | `string` | * @param string $email |
| `$lang` | `string` | * @return void |

**Retorno:** `void — */`

---

##### `static logout(): void`

Encerra a sessão do portal (sem destruir a sessão admin se existir).

**Retorno:** `void — */`

---

##### `static touch(): void`

Atualiza o timestamp de última atividade.

**Retorno:** `void — */`

---

##### `static checkInactivity(int $timeoutMinutes = 60): void`

Verifica inatividade do portal (timeout configurável, padrão 60min).

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$timeoutMinutes` | `int` | Timeout em minutos |

**Retorno:** `void — */`

---

##### `static getClientIp(): string`

Retorna o IP real do cliente.

**Retorno:** `string — */`

---

##### `static is2faPending(): bool`

Verifica se o 2FA está pendente de verificação.

**Retorno:** `bool — */`

---

##### `static set2faPending(bool $pending = true): void`

Marca 2FA como pendente.

---

##### `static set2faVerified(): void`

Marca 2FA como verificado.

---

#### Métodos Private

##### `static createDbSession(int $accessId, int $customerId): void`

Registra a sessão corrente na tabela customer_portal_sessions.

---

##### `static destroyDbSession(): void`

Remove a sessão corrente da tabela.

---

##### `static validateDbSession(): void`

Valida se a sessão corrente ainda existe na tabela (não foi forçado logout).

---

##### `static getConnection(): ?PDO`

Obtém conexão PDO para operações de sessão.

---

## RateLimitMiddleware

**Tipo:** Class  
**Arquivo:** `app/middleware/RateLimitMiddleware.php`  
**Namespace:** `Akti\Middleware`  

RateLimitMiddleware — Proteção contra burst de ações.

### Métodos

#### Métodos Public

##### `static check(string $action, int $minInterval = 5): array`

Verifica rate limit usando sessão (rápido, sem DB).

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$action` | `string` | Identificador da ação (ex: 'nfe_emit') |
| `$minInterval` | `int` | Intervalo mínimo em segundos entre ações |

**Retorno:** `array — ['allowed' => bool, 'retry_after' => int] Segundos até liberação`

---

### Funções auxiliares do arquivo

#### `checkWithDb(PDO $db, string $action, int $minInterval = 5, int $maxPerMinute = 10)`

---

#### `cleanup(PDO $db)`

---

## SecurityHeadersMiddleware

**Tipo:** Class  
**Arquivo:** `app/middleware/SecurityHeadersMiddleware.php`  
**Namespace:** `Akti\Middleware`  

SecurityHeadersMiddleware

### Métodos

#### Métodos Public

##### `static getNonce(): string`

Retorna o nonce CSP do request atual. Gera um novo se não existir.

---

##### `static handle(): void`

Aplica todos os headers de segurança.

---

### Funções auxiliares do arquivo

#### `isHttps()`

---

## SentryMiddleware

**Tipo:** Class  
**Arquivo:** `app/middleware/SentryMiddleware.php`  
**Namespace:** `Akti\Middleware`  

SentryMiddleware — Error Tracking Integration

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$initialized` | Sim |
| private | `$sentryAvailable` | Sim |

### Métodos

#### Métodos Public

##### `static init(): void`

Inicializa o middleware de captura de erros.

---

##### `static handleException(\Throwable $exception): void`

Handler global de exceções não capturadas.

---

##### `static handleError(int $severity, string $message, string $file, int $line): bool`

Handler global de erros PHP.

**Retorno:** `bool — */`

---

### Funções auxiliares do arquivo

#### `handleShutdown()`

---

#### `setUserContext(int $userId, ?string $email = null, ?int $tenantId = null)`

---

#### `($userId, $email, $tenantId)`

---

#### `addBreadcrumb(string $category, string $message, array $data = [])`

---

#### `severityToLogLevel(int $severity)`

---

#### `isAjax()`

---

