# Módulo: Gateways de Pagamento

---

## Sumário
- [Visão Geral](#visão-geral)
- [Arquitetura](#arquitetura)
- [Strategy Pattern + Interface](#strategy-pattern--interface)
- [Gateways Suportados](#gateways-suportados)
- [Fluxo de uma Cobrança](#fluxo-de-uma-cobrança)
- [Fluxo de Webhook](#fluxo-de-webhook)
- [Como Adicionar um Novo Gateway](#como-adicionar-um-novo-gateway)
- [Tabelas do Banco](#tabelas-do-banco)
- [Arquivos do Módulo](#arquivos-do-módulo)
- [Configuração por Tenant](#configuração-por-tenant)
- [Segurança](#segurança)

---

## Visão Geral

O módulo de Gateways de Pagamento permite integrar múltiplos provedores de pagamento (Mercado Pago, Stripe, PagSeguro) com arquitetura extensível baseada em **Strategy Pattern + Interface**. Novos gateways podem ser adicionados sem alterar o core do sistema.

**Princípios:**
- Cada gateway implementa a mesma interface (`PaymentGatewayInterface`)
- O `GatewayManager` resolve qual gateway usar em runtime
- Configuração por tenant (cada empresa tem seus próprios credenciais)
- Webhooks processados pelo Node.js (assíncrono, performático)

---

## Arquitetura

```
┌─────────────────────────────────────────────────────────┐
│                   PHP (Backend Principal)                 │
│                                                          │
│  PaymentGatewayController                                │
│      ↓ usa                                               │
│  GatewayManager::resolve('stripe')                       │
│      ↓ retorna                                           │
│  StripeGateway implements PaymentGatewayInterface         │
│      ↓ chama API                                         │
│  [Stripe API] ──webhook──→ [Node.js API]                │
│                                                          │
├─────────────────────────────────────────────────────────┤
│                   Node.js (Webhooks)                     │
│                                                          │
│  POST /api/webhooks/:gateway?tenant=db_name             │
│      ↓                                                   │
│  WebhookController → WebhookService                     │
│      ↓ valida assinatura                                │
│      ↓ parseia payload                                  │
│      ↓ loga em payment_gateway_transactions             │
│      ↓ atualiza order_installments                      │
│      ↓ recalcula orders.payment_status                  │
└─────────────────────────────────────────────────────────┘
```

---

## Strategy Pattern + Interface

### Interface: `PaymentGatewayInterface`
Arquivo: `app/gateways/Contracts/PaymentGatewayInterface.php`

Métodos obrigatórios:
| Método | Responsabilidade |
|--------|-----------------|
| `getSlug()` | Identificador único (ex: 'stripe') |
| `getDisplayName()` | Nome amigável para UI |
| `supports($method)` | Se suporta 'pix', 'credit_card', 'boleto' |
| `getSupportedMethods()` | Lista de métodos suportados |
| `createCharge($data)` | Criar cobrança na API do gateway |
| `getChargeStatus($id)` | Consultar status de cobrança |
| `refund($id, $amount)` | Estornar cobrança |
| `validateWebhookSignature()` | Validar assinatura HMAC |
| `parseWebhookPayload()` | Interpretar payload do webhook |
| `setCredentials()` | Configurar credenciais |
| `setSettings()` | Configurar settings extras |
| `setEnvironment()` | Definir sandbox/production |
| `getCredentialFields()` | Campos de credencial (para form dinâmico) |
| `getSettingsFields()` | Campos de settings (para form dinâmico) |
| `testConnection()` | Testar se credenciais estão válidas |

### AbstractGateway
Arquivo: `app/gateways/AbstractGateway.php`

Implementação base com:
- Gestão de credenciais, settings e ambiente
- Helper HTTP via cURL
- Formatação de respostas padronizadas
- Logging em `storage/logs/gateways.log`

### GatewayManager (Resolver)
Arquivo: `app/gateways/GatewayManager.php`

```php
// Resolver um gateway pelo slug
$gateway = GatewayManager::make('stripe');

// Resolver COM credenciais
$gateway = GatewayManager::resolve('stripe', $credentials, $settings, 'production');

// Resolver a partir de um registro do banco
$gateway = GatewayManager::resolveFromRow($gatewayRow);

// Listar gateways disponíveis
$list = GatewayManager::getAvailableGateways();
```

---

## Gateways Suportados

| Gateway | Slug | Métodos | Status |
|---------|------|---------|--------|
| Mercado Pago | `mercadopago` | PIX, Cartão, Boleto | ✅ Implementado |
| Stripe | `stripe` | Cartão, PIX, Boleto | ✅ Implementado |
| PagSeguro | `pagseguro` | PIX, Cartão, Boleto | ✅ Implementado |

---

## Fluxo de uma Cobrança

1. **Operador** clica "Gerar Cobrança" na parcela
2. **PaymentGatewayController::createCharge()** recebe installment_id + gateway_slug + method
3. Busca gateway no banco → resolve via **GatewayManager::resolveFromRow()**
4. Chama **$gateway->createCharge($data)** → requisição HTTP para API do gateway
5. Loga em `payment_gateway_transactions`
6. Retorna URL de pagamento / QR Code PIX / dados do boleto

---

## Fluxo de Webhook

> ⚠️ **Webhooks são processados pelo Node.js** (não pelo PHP)

1. Gateway envia `POST /api/webhooks/:gateway?tenant=db_name`
2. **webhookRoutes.js** resolve o tenant via query param (sem JWT)
3. **WebhookController** delega para **WebhookService**
4. WebhookService:
   - Valida assinatura HMAC (específica por gateway)
   - Parseia payload padronizado
   - Cria registro em `payment_gateway_transactions`
   - Se status = 'approved': marca `order_installments` como paga
   - Se status = 'refunded': reverte para pendente
   - Recalcula `orders.payment_status`
5. Retorna HTTP 200 (obrigatório para evitar retries)

---

## Como Adicionar um Novo Gateway

### 1. Criar a classe PHP

```php
// app/gateways/Providers/NuPagGateway.php
namespace Akti\Gateways\Providers;

use Akti\Gateways\AbstractGateway;

class NuPagGateway extends AbstractGateway {
    public function getSlug(): string { return 'nupag'; }
    public function getDisplayName(): string { return 'NuPag'; }
    // ... implementar todos os métodos da interface
}
```

### 2. Registrar no GatewayManager

```php
// app/gateways/GatewayManager.php
private const GATEWAY_MAP = [
    // ... existentes
    'nupag' => NuPagGateway::class,
];
```

### 3. Migration SQL

```sql
-- sql/update_YYYYMMDD_nupag_gateway.sql
INSERT INTO payment_gateways (gateway_slug, display_name, is_active, is_default, environment)
VALUES ('nupag', 'NuPag', 0, 0, 'sandbox')
ON DUPLICATE KEY UPDATE display_name = VALUES(display_name);
```

### 4. Adicionar validação de webhook no Node.js

```javascript
// api/src/services/WebhookService.js
// Adicionar case 'nupag' nos métodos:
// - validateSignature()
// - parsePayload()
```

### 5. Pronto!
Nenhuma alteração no controller, rotas ou views é necessária.

---

## Tabelas do Banco

### `payment_gateways`
Configuração de gateways por tenant.

| Coluna | Tipo | Descrição |
|--------|------|-----------|
| id | INT PK | Auto-increment |
| gateway_slug | VARCHAR(50) UNIQUE | Identificador (mercadopago, stripe, etc.) |
| display_name | VARCHAR(100) | Nome amigável |
| is_active | TINYINT | 1=ativo |
| is_default | TINYINT | 1=gateway padrão |
| environment | ENUM | 'sandbox' ou 'production' |
| credentials | TEXT | JSON com credenciais (api_key, secret, etc.) |
| settings_json | TEXT | JSON com configs extras |
| webhook_secret | VARCHAR(255) | Secret para validação HMAC |

### `payment_gateway_transactions`
Log de todas as interações com gateways.

| Coluna | Tipo | Descrição |
|--------|------|-----------|
| id | INT PK | Auto-increment |
| gateway_slug | VARCHAR(50) | Qual gateway |
| installment_id | INT NULL | FK order_installments |
| order_id | INT NULL | FK orders |
| external_id | VARCHAR(255) | ID no gateway |
| external_status | VARCHAR(100) | Status retornado |
| amount | DECIMAL(12,2) | Valor |
| event_type | VARCHAR(100) | Tipo do evento webhook |
| raw_payload | JSON | Payload completo |

---

## Arquivos do Módulo

### PHP (Backend)
- `app/gateways/Contracts/PaymentGatewayInterface.php` — Interface
- `app/gateways/AbstractGateway.php` — Classe abstrata base
- `app/gateways/GatewayManager.php` — Strategy resolver
- `app/gateways/Providers/MercadoPagoGateway.php`
- `app/gateways/Providers/StripeGateway.php`
- `app/gateways/Providers/PagSeguroGateway.php`
- `app/models/PaymentGateway.php` — Model CRUD
- `app/controllers/PaymentGatewayController.php` — Controller
- `app/views/gateways/index.php` — Listagem
- `app/views/gateways/edit.php` — Configuração
- `app/views/gateways/transactions.php` — Log

### Node.js (Webhooks)
- `api/src/models/PaymentGateway.js` — Sequelize model
- `api/src/models/PaymentGatewayTransaction.js` — Sequelize model
- `api/src/services/WebhookService.js` — Lógica de webhook
- `api/src/controllers/WebhookController.js` — HTTP handler
- `api/src/routes/webhookRoutes.js` — Rotas de webhook

### SQL
- `sql/update_20260318_payment_gateways.sql`

---

## Configuração por Tenant

Cada tenant tem sua própria configuração de gateways na tabela `payment_gateways`. As credenciais são armazenadas como JSON no campo `credentials`.

A URL de webhook é gerada automaticamente:
```
POST {API_BASE_URL}/api/webhooks/{gateway_slug}?tenant={db_name}
```

---

## Segurança

- **Credenciais** armazenadas como JSON (considerar criptografia AES no futuro)
- **Webhooks** validados via HMAC (assinatura do gateway)
- **Webhooks não usam JWT** — o tenant é resolvido via query param
- **Log completo** de todas as interações (raw_payload) para auditoria
- **Ambiente sandbox** por padrão (não processa cobranças reais até mudar para production)

---
