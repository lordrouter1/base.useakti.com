# Fluxo Completo — Mercado Pago no Akti

> Versão: 1.0 — 10/04/2026
> De ponta a ponta: criação do pagamento → confirmação da parcela.

---

## Resumo Ultra-Básico (5 passos)

```
1. Operador gera link de checkout para o cliente
2. Cliente abre o link, escolhe método (PIX/cartão/boleto) e paga
3. Akti envia a cobrança para o Mercado Pago
4. Mercado Pago processa e avisa o Akti via webhook
5. Akti marca a parcela como "pago" no banco de dados
```

---

## Índice

1. [Visão Geral (Diagrama)](#1-visão-geral-diagrama)
2. [Fase 1 — Geração do Token de Checkout](#2-fase-1--geração-do-token-de-checkout)
3. [Fase 2 — Cliente Abre o Checkout](#3-fase-2--cliente-abre-o-checkout)
4. [Fase 3 — Processamento do Pagamento](#4-fase-3--processamento-do-pagamento)
5. [Fase 4 — Webhook (Mercado Pago avisa o Akti)](#5-fase-4--webhook-mercado-pago-avisa-o-akti)
6. [Fase 5 — Confirmação da Parcela](#6-fase-5--confirmação-da-parcela)
7. [Dados Enviados ao Mercado Pago](#7-dados-enviados-ao-mercado-pago)
8. [Dados Retornados pelo Mercado Pago](#8-dados-retornados-pelo-mercado-pago)
9. [Mapeamento de Status](#9-mapeamento-de-status)
10. [Problemas Encontrados na Auditoria](#10-problemas-encontrados-na-auditoria)
11. [Referência de Arquivos](#11-referência-de-arquivos)

---

## 1. Visão Geral (Diagrama)

```
┌──────────┐     ┌──────────┐     ┌─────────────┐     ┌──────────────┐     ┌──────────────┐
│ Operador │     │ Cliente  │     │    Akti      │     │ Mercado Pago │     │   Banco DB   │
│ (admin)  │     │ (naveg.) │     │   (PHP)      │     │    (API)     │     │   (MySQL)    │
└────┬─────┘     └────┬─────┘     └──────┬───────┘     └──────┬───────┘     └──────┬───────┘
     │                │                   │                    │                    │
     │ 1. Gera link   │                   │                    │                    │
     │───────────────────────────────────>│                    │                    │
     │                │                   │ Cria token         │                    │
     │                │                   │───────────────────────────────────────>│
     │                │                   │<──────────────────────────────────────│
     │<──────────────────────────────────│                    │                    │
     │  URL: /checkout?token=abc123      │                    │                    │
     │                │                   │                    │                    │
     │ 2. Envia link  │                   │                    │                    │
     │───────────────>│                   │                    │                    │
     │                │                   │                    │                    │
     │                │ 3. Abre checkout  │                    │                    │
     │                │──────────────────>│                    │                    │
     │                │<─────────────────│ Página de checkout │                    │
     │                │                   │                    │                    │
     │                │ 4. Escolhe PIX    │                    │                    │
     │                │   e clica PAGAR   │                    │                    │
     │                │──────────────────>│                    │                    │
     │                │                   │                    │                    │
     │                │                   │ 5. POST /v1/payments                   │
     │                │                   │───────────────────>│                    │
     │                │                   │<──────────────────│ {id, status, qr}   │
     │                │                   │                    │                    │
     │                │                   │ 6. Loga transação  │                    │
     │                │                   │───────────────────────────────────────>│
     │                │<─────────────────│ QR Code PIX        │                    │
     │                │                   │                    │                    │
     │                │ 7. Paga o PIX     │                    │                    │
     │                │──────────────────────────────────────>│                    │
     │                │                   │                    │                    │
     │                │                   │ 8. WEBHOOK POST    │                    │
     │                │                   │<──────────────────│ {data.id: "123"}   │
     │                │                   │                    │                    │
     │                │                   │ 9. GET /v1/payments/123                │
     │                │                   │───────────────────>│                    │
     │                │                   │<──────────────────│ {status: approved} │
     │                │                   │                    │                    │
     │                │                   │ 10. UPDATE parcela │                    │
     │                │                   │───────────────────────────────────────>│
     │                │                   │  SET status='pago' │                    │
     │                │                   │                    │                    │
     │                │                   │ 11. Retorna 200 OK │                    │
     │                │                   │───────────────────>│                    │
     └──────────┘     └──────────┘     └─────────────┘     └──────────────┘     └──────────────┘
```

---

## 2. Fase 1 — Geração do Token de Checkout

**Quem dispara:** Operador (admin) via interface do sistema.

**O que acontece:**

1. Operador clica em "Gerar link de pagamento" na tela do pedido.
2. O sistema chama `CheckoutService::generateToken()`.
3. O service:
   - Busca o pedido no banco (`orders`).
   - Busca a parcela pendente (`order_installments` onde `status = 'pendente'`).
   - Gera um token criptográfico de 64 caracteres hex: `bin2hex(random_bytes(32))`.
   - Salva o token na tabela `checkout_tokens` com: `order_id`, `installment_id`, `amount`, `expires_at`.
4. Retorna a URL: `https://tenant.useakti.com/?page=checkout&token=abc123...`

**Arquivo:** `app/services/CheckoutService.php` → `generateToken()`

**Dados salvos no token:**

| Campo | Exemplo | De onde vem |
|---|---|---|
| `token` | `a1b2c3d4...` (64 chars) | `random_bytes(32)` |
| `order_id` | `3` | Parâmetro do operador |
| `installment_id` | `7` | Primeira parcela pendente do pedido |
| `amount` | `150.00` | Valor da parcela |
| `gateway_slug` | `mercadopago` | Gateway padrão ou selecionado |
| `expires_at` | `2026-04-12 10:00:00` | Agora + 48h |

---

## 3. Fase 2 — Cliente Abre o Checkout

**Quem dispara:** Cliente ao clicar no link recebido.

**O que acontece:**

1. Navegador acessa `?page=checkout&token=abc123...`
2. `CheckoutController::show()` é chamado.
3. O controller:
   - Valida o token (64 hex chars).
   - Busca o token no banco (`checkout_tokens`).
   - Verifica se está ativo e não expirado.
   - Resolve o gateway (Mercado Pago) e busca a `public_key`.
   - Busca dados da empresa e itens do pedido.
4. Renderiza `app/views/checkout/pay.php` com:
   - Dados do pedido (itens, valor).
   - Métodos disponíveis (PIX, Cartão, Boleto).
   - Public key do Mercado Pago (para SDK frontend).

**Arquivo:** `app/controllers/CheckoutController.php` → `show()`

---

## 4. Fase 3 — Processamento do Pagamento

**Quem dispara:** Cliente clica em "Pagar" no checkout.

**O que acontece (AJAX):**

1. Frontend envia POST para `?page=checkout&action=processPayment` com:
   ```json
   {
     "token": "abc123...",
     "method": "pix",
     "customer_document": "12345678901"
   }
   ```
2. `CheckoutController::processPayment()` chama `CheckoutService::processCheckout()`.
3. O service:
   - Valida token (ativo, não expirado).
   - Resolve o gateway via `GatewayManager::resolveFromRow()`.
   - Monta o `chargeData` (dados da cobrança).
   - Monta a `notification_url` (webhook): `https://tenant.useakti.com/?page=webhook&action=handle&gateway=mercadopago`
   - Chama `$gateway->createCharge($chargeData)`.
4. `MercadoPagoGateway::createCharge()` decide a rota:
   - **PIX, Cartão, Boleto** → `createDirectPayment()` → `POST /v1/payments`
   - **Auto (redirect)** → `createPreferenceLink()` → `POST /checkout/preferences`
5. Para **pagamento direto** (`/v1/payments`):
   - Monta o payload via `buildChargePayload()`.
   - Envia para `https://api.mercadopago.com/v1/payments`.
   - Recebe resposta com `id`, `status`, dados do PIX/boleto.
6. O service loga a transação em `payment_gateway_transactions`.
7. **Se status = `approved`** (cartão aprovado na hora):
   - Marca a parcela como paga imediatamente.
   - Atualiza `payment_status` do pedido.
8. **Se status = `pending`** (PIX/boleto — aguardando pagamento):
   - Retorna QR code PIX ou link do boleto para o frontend.
   - A confirmação virá depois via **webhook**.

**Arquivos:**
- `app/services/CheckoutService.php` → `processCheckout()`
- `app/gateways/Providers/MercadoPagoGateway.php` → `createCharge()` / `createDirectPayment()` / `buildChargePayload()`

---

## 5. Fase 4 — Webhook (Mercado Pago avisa o Akti)

**Quem dispara:** Mercado Pago automaticamente, quando o pagamento muda de status.

**O que acontece:**

1. O cliente paga o PIX (ou boleto é compensado).
2. Mercado Pago envia um `POST` para a `notification_url`:
   ```
   POST https://tenant.useakti.com/?page=webhook&action=handle&gateway=mercadopago
   ```
3. O body contém apenas o ID do pagamento:
   ```json
   {
     "action": "payment.updated",
     "type": "payment",
     "data": { "id": "82271874093" }
   }
   ```
4. `WebhookController::handle()` processa:
   - Lê o body raw (necessário para validação HMAC).
   - Busca o gateway no banco (`payment_gateways` WHERE `gateway_slug = 'mercadopago'`).
   - Resolve a instância do gateway via `GatewayManager::resolveFromRow()`.
   - Valida assinatura HMAC (se `webhook_secret` configurado).
   - Chama `$gateway->parseWebhookPayload()`.
5. `MercadoPagoGateway::parseWebhookPayload()`:
   - Extrai `data.id` = `"82271874093"`.
   - Faz `GET https://api.mercadopago.com/v1/payments/82271874093`.
   - Recebe a resposta completa com: `status`, `transaction_amount`, `metadata`.
   - Extrai do `metadata`: `installment_id` e `order_id`.
   - Retorna dados padronizados.
6. O controller extrai `installment_id` e `order_id` do resultado.
7. Loga a transação em `payment_gateway_transactions`.
8. Se `status = 'approved'` → chama `processApprovedPayment()`.

**Arquivos:**
- `app/controllers/WebhookController.php` → `handle()`
- `app/gateways/Providers/MercadoPagoGateway.php` → `parseWebhookPayload()`

### Dois tipos de webhook do Mercado Pago

| Tipo | Quando | `data.id` | Dados inline? | Precisa lookup? |
|---|---|---|---|---|
| `payment` | Pagamento direto (`/v1/payments`) | ID do pagamento (ex: `82271874093`) | Não | Sim (`GET /v1/payments/{id}`) |
| `order` | Checkout Pro / Point | ID da merchant order | Sim (status, valor, pagamentos) | Não |

O sistema trata **ambos os tipos**.

---

## 6. Fase 5 — Confirmação da Parcela

**Quem dispara:** O próprio `WebhookController` após verificar que o status é `approved`.

**O que acontece:**

1. `WebhookController::processApprovedPayment()` recebe `installment_id` e/ou `order_id`.
2. **Se tem `installment_id`** (caminho principal):
   - Busca parcela no banco: `SELECT * FROM order_installments WHERE id = {installment_id}`.
   - Verifica se já está paga (evita duplicidade).
   - Chama `Installment::pay()`:
     ```sql
     UPDATE order_installments SET
       status = 'pago',
       paid_date = '2026-04-10',
       paid_amount = 150.00,
       payment_method = 'mercadopago',
       is_confirmed = 1,
       confirmed_at = NOW(),
       notes = 'Pago via webhook mercadopago (ID: 82271874093)'
     WHERE id = 7
     ```
   - Chama `Installment::updateOrderPaymentStatus()`:
     - Conta total de parcelas e quantas estão pagas.
     - Atualiza `orders.payment_status` para `'pago'`, `'parcial'` ou `'pendente'`.
3. **Se só tem `order_id`** (fallback):
   - Delega para `CheckoutService::markInstallmentPaidFromCheckout()`.
   - Busca a primeira parcela pendente do pedido e marca como paga.
4. Retorna HTTP 200 para o Mercado Pago (confirma recebimento).

**Arquivos:**
- `app/controllers/WebhookController.php` → `processApprovedPayment()`
- `app/models/Installment.php` → `pay()`, `updateOrderPaymentStatus()`

---

## 7. Dados Enviados ao Mercado Pago

### Pagamento Direto (`POST /v1/payments`)

Montado por `MercadoPagoGateway::buildChargePayload()`:

```json
{
  "transaction_amount": 150.00,
  "description": "Pedido #0003",
  "statement_descriptor": "AKTI",
  "payment_method_id": "pix",
  "date_of_expiration": "2026-04-10T11:00:00.000-03:00",
  "notification_url": "https://tenant.useakti.com/?page=webhook&action=handle&gateway=mercadopago",
  "metadata": {
    "checkout_token_id": 15,
    "source": "akti",
    "installment_id": 7,
    "order_id": 3
  },
  "payer": {
    "email": "cliente@email.com",
    "first_name": "João",
    "last_name": "Silva",
    "identification": {
      "type": "CPF",
      "number": "12345678901"
    },
    "address": {
      "zip_code": "01001000",
      "street_name": "Rua Exemplo",
      "street_number": "123",
      "neighborhood": "Centro",
      "city": "São Paulo",
      "federal_unit": "SP"
    }
  }
}
```

**Origem de cada campo:**

| Campo | De onde vem |
|---|---|
| `transaction_amount` | `checkout_tokens.amount` (valor da parcela) |
| `description` | `"Pedido #" + order_id` |
| `statement_descriptor` | `payment_gateways.settings_json.statement_descriptor` |
| `payment_method_id` | Método escolhido pelo cliente (pix/bolbradesco/visa etc) |
| `notification_url` | `CheckoutService::buildWebhookUrl()` — monta com subdomínio do tenant |
| `metadata.installment_id` | `checkout_tokens.installment_id` — **este é o campo que liga o pagamento à parcela** |
| `metadata.order_id` | `checkout_tokens.order_id` |
| `payer.*` | Tabela `customers` via `CheckoutService::getCustomerAddressFromOrder()` |

### Checkout Pro (`POST /checkout/preferences`)

Montado por `MercadoPagoGateway::createPreferenceLink()`:

```json
{
  "items": [{ "title": "Pedido #0003", "quantity": 1, "unit_price": 150.00, "currency_id": "BRL" }],
  "external_reference": "3",
  "metadata": { "installment_id": 7, "order_id": 3, "source": "akti" },
  "payer": { "email": "cliente@email.com", "name": "João Silva" },
  "notification_url": "https://tenant.useakti.com/?page=webhook&action=handle&gateway=mercadopago"
}
```

**Diferença principal:** No Checkout Pro, o `external_reference` é o `order_id` (como string). No pagamento direto, o vínculo com o pedido é feito via `metadata`.

---

## 8. Dados Retornados pelo Mercado Pago

### Resposta do `POST /v1/payments`

```json
{
  "id": 82271874093,
  "status": "pending",
  "status_detail": "pending_waiting_transfer",
  "transaction_amount": 150.00,
  "date_of_expiration": "2026-04-10T11:00:00.000-03:00",
  "point_of_interaction": {
    "transaction_data": {
      "qr_code": "00020126...",
      "qr_code_base64": "iVBORw0KGgo...",
      "ticket_url": "https://www.mercadopago.com.br/..."
    }
  },
  "metadata": {
    "installment_id": 7,
    "order_id": 3,
    "source": "akti"
  }
}
```

| Campo | Significado |
|---|---|
| `id` | ID do pagamento **no Mercado Pago** (NÃO é o ID do pedido) |
| `status` | `pending` (aguardando) / `approved` (pago) / `rejected` (negado) |
| `metadata` | Exatamente o que o Akti enviou — contém `installment_id` e `order_id` |

### Webhook recebido pelo Akti

```json
{
  "action": "payment.updated",
  "type": "payment",
  "data": { "id": "82271874093" },
  "date_created": "2026-04-10T10:30:00-03:00",
  "live_mode": true,
  "user_id": 134725931
}
```

**Nota:** O webhook NÃO traz os dados completos. Só traz o `data.id`. O Akti precisa fazer `GET /v1/payments/82271874093` para obter o status e o metadata.

---

## 9. Mapeamento de Status

### Mercado Pago → Akti (padronizado)

| Status MP | Status Akti | Significado |
|---|---|---|
| `approved` | `approved` | Pago e confirmado |
| `pending` | `pending` | Aguardando pagamento (PIX, boleto) |
| `authorized` | `pending` | Pré-autorizado (cartão) |
| `in_process` | `pending` | Em análise antifraude |
| `in_mediation` | `pending` | Em disputa |
| `rejected` | `rejected` | Pagamento negado |
| `cancelled` | `cancelled` | Cancelado |
| `refunded` | `refunded` | Estornado |
| `charged_back` | `refunded` | Chargeback |

### Status Akti → Ação no banco

| Status Akti | Ação |
|---|---|
| `approved` | `UPDATE order_installments SET status = 'pago'` |
| `pending` | Nenhuma (aguarda) |
| `rejected` | Nenhuma (loga transação) |
| `refunded` | Nenhuma (só loga — requer estorno manual) |

---

## 10. Problemas Encontrados na Auditoria

### 10.1 — Estorno (refunded) não é tratado automaticamente

**Situação:** Se o Mercado Pago envia webhook com status `refunded`, o `WebhookController` apenas loga a transação. Não reverte a parcela para `pendente`.

**Impacto:** Parcela continua como "pago" no sistema mesmo após estorno.

**Sugestão:** Implementar `processRefundedPayment()` similar ao Node.js `WebhookService._markInstallmentRefunded()`.

### 10.2 — Node.js WebhookService (api/) está duplicado e desalinhado

**Situação:** Existem dois sistemas de webhook:
- **PHP:** `WebhookController.php` + `MercadoPagoGateway::parseWebhookPayload()` — **ativo e funcional**.
- **Node.js:** `api/src/services/WebhookService.js` — código funcional mas **não está sendo usado** (webhook URL aponta para PHP, não para Node.js).

**Impacto:** Manutenção duplicada. Se alguém alterar apenas o Node.js pensando que é o ativo, a mudança não terá efeito.

**Sugestão:** Manter apenas o endpoint PHP como ativo. Documentar que o Node.js é alternativa futura.

### 10.3 — Webhook Secret não configurado (sandbox)

**Situação:** Em sandbox, o `webhook_secret` geralmente está vazio. O sistema aceita qualquer POST sem validação de assinatura.

**Impacto:** Em sandbox é aceitável. Em produção, **obrigatório** configurar o secret.

**Ação necessária para produção:** Configurar `webhook_secret` no `payment_gateways` via admin.

### 10.4 — `notification_url` requer HTTPS

**Situação:** O Mercado Pago exige HTTPS na `notification_url`. Em ambiente local (HTTP), a URL é omitida e o MP não envia webhooks.

**Impacto:** Em desenvolvimento local, webhooks não funcionam. Precisa testar via simulador ou em servidor com HTTPS.

### 10.5 — `updateOrderPaymentStatus` exige `is_confirmed = 1`

**Situação:** O `Installment::updateOrderPaymentStatus()` só conta como "paga" as parcelas com `is_confirmed = 1`:
```sql
SUM(CASE WHEN status = 'pago' AND is_confirmed = 1 THEN 1 ELSE 0 END) as pagas
```

O `processApprovedPayment()` do webhook marca `autoConfirm = true`, então `is_confirmed = 1`. Está correto.

**Sem impacto** — apenas documentado para consciência.

### 10.6 — Idempotência de webhook

**Situação:** O Mercado Pago pode reenviar o mesmo webhook múltiplas vezes. O `processApprovedPayment()` verifica se a parcela já está paga antes de atualizar:
```php
if ($installment['status'] === 'pago') {
    // Já pago — skipping
    return;
}
```

**Sem impacto** — proteção contra duplicidade já implementada.

---

## 11. Referência de Arquivos

### Fase de Criação (Checkout)

| Arquivo | Responsabilidade |
|---|---|
| `app/services/CheckoutService.php` | Lógica principal: gera token, processa pagamento, monta chargeData |
| `app/controllers/CheckoutController.php` | HTTP: renderiza checkout, recebe AJAX, proxy tokenização |
| `app/gateways/GatewayManager.php` | Factory: resolve qual gateway usar pelo slug |
| `app/gateways/Providers/MercadoPagoGateway.php` | API MP: monta payload, envia cobrança, parseia webhook |
| `app/gateways/AbstractGateway.php` | Base: httpRequest (cURL), formatação de respostas |
| `app/models/CheckoutToken.php` | CRUD da tabela `checkout_tokens` |
| `app/models/PaymentGateway.php` | CRUD da tabela `payment_gateways` + log de transações |

### Fase de Confirmação (Webhook)

| Arquivo | Responsabilidade |
|---|---|
| `app/controllers/WebhookController.php` | Recebe POST do MP, valida, processa, marca parcela paga |
| `app/gateways/Providers/MercadoPagoGateway.php` | `parseWebhookPayload()`: faz GET /v1/payments/{id}, extrai metadata |
| `app/gateways/Providers/MercadoPagoGateway.php` | `validateWebhookSignature()`: valida HMAC x-signature |
| `app/models/Installment.php` | `pay()`: UPDATE parcela para 'pago'; `updateOrderPaymentStatus()`: recalcula status do pedido |
| `app/services/CheckoutService.php` | `markInstallmentPaidFromCheckout()`: fallback quando só tem order_id |

### Rotas

| URL | Método | Controlador | Função |
|---|---|---|---|
| `?page=checkout&token=X` | GET | CheckoutController | `show()` — Exibe checkout |
| `?page=checkout&action=processPayment` | POST | CheckoutController | `processPayment()` — AJAX pagamento |
| `?page=checkout&action=tokenizeCard` | POST | CheckoutController | `tokenizeCard()` — Proxy tokenização |
| `?page=checkout&action=checkStatus` | GET | CheckoutController | `checkStatus()` — Polling status |
| `?page=checkout&action=confirmation` | GET | CheckoutController | `confirmation()` — Tela final |
| `?page=webhook&action=handle&gateway=mercadopago` | POST | WebhookController | `handle()` — Recebe webhook |

### Tabelas do Banco

| Tabela | Papel no fluxo |
|---|---|
| `orders` | Pedido principal; `payment_status` atualizado pelo webhook |
| `order_installments` | Parcelas; `status` atualizado para 'pago' via webhook |
| `checkout_tokens` | Token de checkout com `order_id`, `installment_id`, `amount` |
| `payment_gateways` | Config do gateway: credenciais, environment, webhook_secret |
| `payment_gateway_transactions` | Log de cada transação (criação + webhook) |
| `customers` | Dados do cliente (nome, CPF, endereço) enviados ao MP |
