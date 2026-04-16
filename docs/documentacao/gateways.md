# Gateways (Pagamento)

> Integração com gateways de pagamento: Mercado Pago, PagSeguro, Stripe.

**Total de arquivos:** 6

---

## Índice

- [AbstractGateway](#abstractgateway) — `app/gateways/AbstractGateway.php`
- [PaymentGatewayInterface (Interface)](#paymentgatewayinterface) — `app/gateways/Contracts/PaymentGatewayInterface.php`
- [GatewayManager](#gatewaymanager) — `app/gateways/GatewayManager.php`
- [MercadoPagoGateway](#mercadopagogateway) — `app/gateways/Providers/MercadoPagoGateway.php`
- [PagSeguroGateway](#pagsegurogateway) — `app/gateways/Providers/PagSeguroGateway.php`
- [StripeGateway](#stripegateway) — `app/gateways/Providers/StripeGateway.php`

---

## AbstractGateway

**Tipo:** Class  
**Arquivo:** `app/gateways/AbstractGateway.php`  
**Namespace:** `Akti\Gateways`  
**Implementa:** `PaymentGatewayInterface`  

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| protected | `$credentials` | Não |
| protected | `$settings` | Não |
| protected | `$environment` | Não |

### Métodos

#### Métodos Public

##### `setCredentials(array $credentials): void`

{@inheritDoc}

---

##### `setSettings(array $settings): void`

{@inheritDoc}

---

##### `setEnvironment(string $environment): void`

{@inheritDoc}

---

#### Métodos Protected

##### `getCredential(string $key, string $default = ''): string`

Retorna uma credencial pelo nome.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$key` | `string` | Nome da credencial (ex: 'access_token', 'secret_key'). |
| `$default` | `string` | Valor padrão se a credencial não existir. |

**Retorno:** `string — */`

---

##### `getSetting(string $key, $default = null)`

Retorna uma configuração extra pelo nome.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$key` | `string` | Nome da configuração (ex: 'pix_expiration_minutes'). |
| `$default` | `mixed` | Valor padrão se a configuração não existir. |

**Retorno:** `mixed — */`

---

##### `isSandbox(): bool`

Verifica se está em modo sandbox.

**Retorno:** `bool — True se o ambiente for 'sandbox'.`

---

##### `httpRequest(string $method,
        string $url,
        array $headers = [],
        $body = null,
        int $timeout = 30): array`

Faz requisição HTTP via cURL.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$method` | `string` | HTTP method (GET, POST, PUT, DELETE) |
| `$url` | `string` | URL completa |
| `$headers` | `array` | Headers adicionais |
| `$body` | `mixed` | Body (array será convertido em JSON) |
| `$timeout` | `int` | Timeout em segundos |

**Retorno:** `array — ['status' => int, 'body' => string, 'decoded' => array|null]`

---

##### `successResponse(array $data): array`

Cria uma resposta padronizada de sucesso.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$data` | `array` | Dados adicionais a incluir na resposta. |

**Retorno:** `array — Array com 'success' => true mesclado com $data.`

---

##### `errorResponse(string $message, array $extra = []): array`

Cria uma resposta padronizada de erro.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$message` | `string` | Mensagem de erro descritiva. |
| `$extra` | `array` | Dados adicionais (ex: 'raw' => resposta bruta). |

**Retorno:** `array — Array com 'success' => false, 'error' => $message, mesclado com $extra.`

---

##### `mapStatus(string $gatewayStatus): string`

Mapeia status do gateway para status padronizado do Akti.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$gatewayStatus` | `string` | Status original retornado pela API do gateway. |

**Retorno:** `string — Status padronizado: 'approved', 'pending', 'rejected', 'cancelled' ou 'refunded'.`

---

##### `log(string $level, string $message, array $context = []): void`

Loga uma operação do gateway (append em storage/logs/gateways.log).

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$level` | `string` | Nível do log ('info', 'warning', 'error'). |
| `$message` | `string` | Mensagem descritiva do evento. |
| `$context` | `array` | Dados adicionais para contexto (serializados em JSON). |

**Retorno:** `void — */`

---

## PaymentGatewayInterface

**Tipo:** Interface  
**Arquivo:** `app/gateways/Contracts/PaymentGatewayInterface.php`  
**Namespace:** `Akti\Gateways\Contracts`  

PaymentGatewayInterface — Contrato para todos os gateways de pagamento.

### Métodos

#### Métodos Public

##### `getSlug(): string`

Retorna o slug único do gateway (ex: 'mercadopago', 'stripe').

**Retorno:** `string — */`

---

##### `getDisplayName(): string`

Retorna o nome amigável para exibição na UI.

**Retorno:** `string — */`

---

##### `supports(string $method): bool`

Verifica se o gateway suporta um determinado método de pagamento.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$method` | `string` | Método de pagamento |

**Retorno:** `bool — */`

---

##### `getSupportedMethods(): array`

Retorna lista de métodos suportados pelo gateway.

**Retorno:** `string[] — Ex: ['pix', 'credit_card', 'boleto']`

---

##### `createCharge(array $data): array`

Cria uma cobrança no gateway externo.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$data` | `array` | Dados da cobrança: |

**Retorno:** `array — Resultado padronizado:`

---

##### `getChargeStatus(string $externalId): array`

Consulta o status de uma cobrança pelo ID externo.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$externalId` | `string` | ID da transação no gateway |

**Retorno:** `array — Resultado padronizado:`

---

##### `refund(string $externalId, ?float $amount = null): array`

Solicita estorno total ou parcial de uma cobrança.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$externalId` | `string` | ID da transação no gateway |
| `$amount` | `float|null` | Valor a estornar (null = total) |

**Retorno:** `array — Resultado padronizado:`

---

##### `validateWebhookSignature(string $payload, array $headers, string $secret): bool`

Valida a assinatura do webhook recebido.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$payload` | `string` | Body raw do webhook (JSON string) |
| `$headers` | `array` | Headers HTTP da requisição |
| `$secret` | `string` | Webhook secret configurado no gateway |

**Retorno:** `bool — True se a assinatura for válida`

---

##### `parseWebhookPayload(string $payload, array $headers): array`

Interpreta o payload do webhook e retorna dados padronizados.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$payload` | `string` | Body raw do webhook (JSON string) |
| `$headers` | `array` | Headers HTTP da requisição |

**Retorno:** `array — Dados padronizados:`

---

##### `setCredentials(array $credentials): void`

Define as credenciais do gateway.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$credentials` | `array` | Array com credenciais (api_key, secret, token, etc.) |

**Retorno:** `void — */`

---

##### `setSettings(array $settings): void`

Define configurações extras (ex: pix_enabled, boleto_days, etc.).

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$settings` | `array` | * @return void |

**Retorno:** `void — */`

---

##### `setEnvironment(string $environment): void`

Define o ambiente (sandbox ou production).

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$environment` | `string` | 'sandbox' ou 'production' |

**Retorno:** `void — */`

---

##### `getCredentialFields(): array`

Retorna a lista de campos de credencial exigidos por este gateway.

**Retorno:** `array — Ex: [`

---

##### `getSettingsFields(): array`

Retorna a lista de campos de configuração extras.

**Retorno:** `array — Ex: [`

---

##### `testConnection(): array`

Testa se as credenciais configuradas são válidas (ping na API).

**Retorno:** `array — ['success' => bool, 'message' => string]`

---

## GatewayManager

**Tipo:** Class  
**Arquivo:** `app/gateways/GatewayManager.php`  
**Namespace:** `Akti\Gateways`  

GatewayManager — Strategy Pattern resolver para gateways de pagamento.

### Propriedades

| Visibilidade | Nome | Estático |
|---|---|---|
| private | `$instances` | Sim |

### Métodos

#### Métodos Public

##### `static make(string $slug): PaymentGatewayInterface`

Resolve e retorna um gateway pelo slug.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$slug` | `string` | Slug do gateway (ex: 'mercadopago') |

**Retorno:** `PaymentGatewayInterface — * @throws \InvalidArgumentException Se o slug não for suportado`

---

### Funções auxiliares do arquivo

#### `resolve(string $slug,
        array $credentials = [],
        array $settings = [],
        string $environment = 'sandbox')`

---

#### `resolveFromRow(array $gatewayRow)`

---

#### `getRegisteredSlugs()`

---

#### `isRegistered(string $slug)`

---

#### `getAvailableGateways()`

---

#### `getMethodLabels()`

---

## MercadoPagoGateway

**Tipo:** Class  
**Arquivo:** `app/gateways/Providers/MercadoPagoGateway.php`  
**Namespace:** `Akti\Gateways\Providers`  
**Herda de:** `AbstractGateway`  

MercadoPagoGateway — Integração com a API do Mercado Pago.

### Métodos

#### Métodos Public

##### `getSlug(): string`

{@inheritDoc}

---

##### `getDisplayName(): string`

{@inheritDoc}

---

##### `supports(string $method): bool`

{@inheritDoc}

---

##### `getSupportedMethods(): array`

{@inheritDoc}

---

##### `getCredentialFields(): array`

{@inheritDoc}

---

##### `getSettingsFields(): array`

{@inheritDoc}

---

##### `createCharge(array $data): array`

{@inheritDoc}

---

#### Métodos Private

##### `createDirectPayment(array $data, string $method): array`

Cria um pagamento direto via /v1/payments.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$data` | `array` | Dados da cobrança (mesmo formato de createCharge). |
| `$method` | `string` | Método de pagamento selecionado. |

**Retorno:** `array — Resposta padronizada com 'success', 'external_id', 'status', etc.`

---

##### `createPreferenceLink(array $data, string $method): array`

Cria uma Preferência de Checkout via /checkout/preferences.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$data` | `array` | Dados da cobrança (mesmo formato de createCharge). |
| `$method` | `string` | Método de pagamento selecionado. |

**Retorno:** `array — Resposta padronizada com 'success', 'external_id', 'status', 'payment_url', etc.`

---

### Funções auxiliares do arquivo

#### `getChargeStatus(string $externalId)`

---

#### `refund(string $externalId, ?float $amount = null)`

---

#### `validateWebhookSignature(string $payload, array $headers, string $secret)`

---

#### `parseWebhookPayload(string $payload, array $headers)`

---

#### `parseOrderWebhook(array $data)`

---

#### `testConnection()`

---

#### `getBaseUrl()`

---

#### `formatDateForMp(string $relativeTime)`

---

#### `mapStatus(string $gatewayStatus)`

---

#### `buildChargePayload(array $data, string $method)`

---

## PagSeguroGateway

**Tipo:** Class  
**Arquivo:** `app/gateways/Providers/PagSeguroGateway.php`  
**Namespace:** `Akti\Gateways\Providers`  
**Herda de:** `AbstractGateway`  

PagSeguroGateway — Integração com a API do PagSeguro (PagBank).

### Métodos

#### Métodos Public

##### `getSlug(): string`

{@inheritDoc}

---

##### `getDisplayName(): string`

{@inheritDoc}

---

##### `supports(string $method): bool`

{@inheritDoc}

---

##### `getSupportedMethods(): array`

{@inheritDoc}

---

##### `getCredentialFields(): array`

{@inheritDoc}

---

##### `getSettingsFields(): array`

{@inheritDoc}

---

##### `createCharge(array $data): array`

{@inheritDoc}

---

### Funções auxiliares do arquivo

#### `getChargeStatus(string $externalId)`

---

#### `refund(string $externalId, ?float $amount = null)`

---

#### `validateWebhookSignature(string $payload, array $headers, string $secret)`

---

#### `parseWebhookPayload(string $payload, array $headers)`

---

#### `testConnection()`

---

#### `getBaseUrl()`

---

#### `mapStatus(string $gatewayStatus)`

---

#### `buildCustomer(array $data)`

---

#### `extractPagSeguroError(array $response)`

---

#### `buildPaymentMethod(array $data, string $method)`

---

#### `findLink(array $links, string $rel)`

---

## StripeGateway

**Tipo:** Class  
**Arquivo:** `app/gateways/Providers/StripeGateway.php`  
**Namespace:** `Akti\Gateways\Providers`  
**Herda de:** `AbstractGateway`  

StripeGateway — Integração com a API do Stripe.

### Métodos

#### Métodos Public

##### `getSlug(): string`

{@inheritDoc}

---

##### `getDisplayName(): string`

{@inheritDoc}

---

##### `supports(string $method): bool`

{@inheritDoc}

---

##### `getSupportedMethods(): array`

{@inheritDoc}

---

##### `getCredentialFields(): array`

{@inheritDoc}

---

##### `getSettingsFields(): array`

{@inheritDoc}

---

##### `createCharge(array $data): array`

{@inheritDoc}

---

##### `getChargeStatus(string $externalId): array`

{@inheritDoc}

---

#### Métodos Private

##### `createPaymentIntent(array $data, string $method): array`

Cria um PaymentIntent diretamente.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$data` | `array` | Dados da cobrança (mesmo formato de createCharge). |
| `$method` | `string` | Método de pagamento selecionado. |

**Retorno:** `array — Resposta padronizada com 'success', 'external_id', 'status', etc.`

---

##### `createCheckoutSession(array $data, string $method): array`

Cria uma Checkout Session que gera uma URL de pagamento hosted pelo Stripe.

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `$data` | `array` | Dados da cobrança (mesmo formato de createCharge). |
| `$method` | `string` | Método de pagamento selecionado. |

**Retorno:** `array — Resposta padronizada com 'success', 'external_id', 'status', 'payment_url', etc.`

---

### Funções auxiliares do arquivo

#### `refund(string $externalId, ?float $amount = null)`

---

#### `validateWebhookSignature(string $payload, array $headers, string $secret)`

---

#### `parseWebhookPayload(string $payload, array $headers)`

---

#### `testConnection()`

---

#### `mapStatus(string $gatewayStatus)`

---

#### `stripeRequest(string $method, string $path, array $data = [])`

---

