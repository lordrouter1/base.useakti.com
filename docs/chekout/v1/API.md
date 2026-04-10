# Checkout Transparente — Especificação de API e Endpoints

> Detalhamento de todos os endpoints HTTP (rotas, parâmetros, respostas) do módulo de checkout transparente.

---

## 1. Rotas Públicas (Checkout)

### 1.1 `GET /?page=checkout&token={token}` — Exibir Checkout

**Autenticação:** Nenhuma (pública)  
**Rate Limit:** 30 req/min por IP

**Parâmetros:**

| Param | Tipo | Obrigatório | Descrição |
|-------|------|-------------|-----------|
| `token` | string (64 hex) | Sim | Token do checkout |

**Fluxo:**

1. Validar formato do token (regex: `/^[a-f0-9]{64}$/`)
2. Buscar token no banco (JOIN com orders + installments)
3. Verificar: status = 'active', expires_at > NOW()
4. Resolver tenant a partir de tenant_id
5. Carregar gateway padrão do tenant
6. Obter métodos suportados (filtrar por allowed_methods)
7. Carregar company_settings (logo, nome, cores)
8. Registrar IP do visitante
9. Renderizar `checkout/pay.php`

**Respostas:**

| Cenário | Ação |
|---------|------|
| Token válido | Renderiza página de checkout |
| Token não encontrado | Renderiza `checkout/expired.php` com mensagem "Link inválido" |
| Token expirado | Renderiza `checkout/expired.php` com mensagem "Link expirado" |
| Token usado | Renderiza `checkout/success.php` com dados do pagamento |
| Token cancelado | Renderiza `checkout/expired.php` com mensagem "Link cancelado" |

---

### 1.2 `POST /?page=checkout&action=processPayment` — Processar Pagamento

**Autenticação:** Token no body  
**Content-Type:** `application/json`  
**Rate Limit:** 5 req/10min por token

**Request Body:**

```json
{
    "token": "abc123...",
    "method": "pix",
    "card_token": null,
    "customer_document": "123.456.789-00",
    "customer_name": "João Silva",
    "customer_email": "joao@email.com"
}
```

| Campo | Tipo | Obrigatório | Descrição |
|-------|------|-------------|-----------|
| `token` | string | Sim | Token do checkout |
| `method` | string | Sim | `pix`, `credit_card`, `boleto` |
| `card_token` | string | Se cartão | Token do SDK JS do gateway |
| `customer_document` | string | Se não pré-preenchido | CPF ou CNPJ |
| `customer_name` | string | Se não pré-preenchido | Nome do pagador |
| `customer_email` | string | Se não pré-preenchido | Email do pagador |

**Respostas de Sucesso (HTTP 200):**

#### PIX

```json
{
    "success": true,
    "method": "pix",
    "status": "pending",
    "external_id": "pi_1Abc123",
    "qr_code": "00020126580014BR.GOV.BCB.PIX...",
    "qr_code_base64": "data:image/png;base64,iVBORw0KGgo...",
    "expires_at": "2026-04-08T19:30:00Z",
    "expires_in_seconds": 1800
}
```

#### Cartão (Aprovado)

```json
{
    "success": true,
    "method": "credit_card",
    "status": "succeeded",
    "external_id": "pi_1Abc123",
    "redirect_url": "/?page=checkout&action=confirmation&token=abc123...&status=succeeded"
}
```

#### Cartão (3D Secure - Requer Ação)

```json
{
    "success": true,
    "method": "credit_card",
    "status": "requires_action",
    "external_id": "pi_1Abc123",
    "client_secret": "pi_1Abc123_secret_xyz",
    "gateway": "stripe"
}
```

#### Boleto

```json
{
    "success": true,
    "method": "boleto",
    "status": "pending",
    "external_id": "pi_1Abc123",
    "boleto_url": "https://...",
    "boleto_barcode": "12345.67890 12345.678901 12345.678901 1 12340000028333",
    "boleto_due_date": "2026-04-11"
}
```

**Respostas de Erro:**

```json
{
    "success": false,
    "error": "token_expired",
    "message": "Este link de pagamento expirou."
}
```

| Código de Erro | HTTP | Descrição |
|----------------|------|-----------|
| `token_invalid` | 400 | Token malformado |
| `token_not_found` | 404 | Token não existe |
| `token_expired` | 410 | Token expirou |
| `token_used` | 410 | Token já foi usado |
| `token_cancelled` | 410 | Token cancelado |
| `method_not_allowed` | 400 | Método não permitido para este token |
| `rate_limited` | 429 | Muitas tentativas |
| `gateway_error` | 502 | Erro na API do gateway |
| `validation_error` | 422 | Dados inválidos (ex: CPF inválido) |

---

### 1.3 `GET /?page=checkout&action=checkStatus&token={token}&external_id={id}` — Verificar Status

**Autenticação:** Token no query string  
**Rate Limit:** 60 req/min por token (polling a cada 5s)

**Parâmetros:**

| Param | Tipo | Obrigatório | Descrição |
|-------|------|-------------|-----------|
| `token` | string | Sim | Token do checkout |
| `external_id` | string | Sim | ID da transação no gateway |

**Resposta:**

```json
{
    "success": true,
    "status": "pending",
    "paid": false
}
```

```json
{
    "success": true,
    "status": "approved",
    "paid": true,
    "redirect_url": "/?page=checkout&action=success&token=abc123..."
}
```

---

### 1.4 `GET /?page=checkout&action=success&token={token}` — Página de Sucesso

**Autenticação:** Token no query string  
**Rate Limit:** 30 req/min por IP

**Fluxo:**

1. Validar token (deve existir com status 'used')
2. Carregar dados do pagamento
3. Renderizar `checkout/success.php`

---

## 2. Rotas Admin (Gestão de Checkout)

### 2.1 `POST /?page=payment_gateways&action=createCheckoutLink` — Gerar Link

**Autenticação:** Admin logado + permissão `payment_gateways`  
**Content-Type:** `application/x-www-form-urlencoded` ou `application/json`  
**CSRF:** Obrigatório (`X-CSRF-TOKEN` header)

**Request:**

| Campo | Tipo | Obrigatório | Descrição |
|-------|------|-------------|-----------|
| `installment_id` | int | Sim* | ID da parcela |
| `order_id` | int | Sim* | ID do pedido (se sem parcela) |
| `allowed_methods` | array | Não | Métodos permitidos (default: todos) |
| `expires_in_hours` | int | Não | Horas até expiração (default: 48) |
| `gateway_slug` | string | Não | Gateway fixo (default: padrão do tenant) |

*Obrigatório ao menos um: `installment_id` ou `order_id`.

**Resposta de Sucesso:**

```json
{
    "success": true,
    "checkout_url": "https://cliente.akti.com/?page=checkout&token=abc123...",
    "token": "abc123...",
    "expires_at": "2026-04-10T08:12:00Z",
    "expires_in_hours": 48
}
```

**Resposta de Erro:**

```json
{
    "success": false,
    "message": "Pedido não encontrado."
}
```

---

### 2.2 `POST /?page=payment_gateways&action=cancelCheckoutLink` — Cancelar Link

**Autenticação:** Admin logado + permissão  
**CSRF:** Obrigatório

**Request:**

| Campo | Tipo | Obrigatório | Descrição |
|-------|------|-------------|-----------|
| `token_id` | int | Sim | ID do checkout_token |

**Resposta:**

```json
{
    "success": true,
    "message": "Link de checkout cancelado."
}
```

---

### 2.3 `GET /?page=payment_gateways&action=checkoutTokens` — Listar Tokens

**Autenticação:** Admin logado + permissão

**Parâmetros (query string):**

| Param | Tipo | Default | Descrição |
|-------|------|---------|-----------|
| `order_id` | int | — | Filtrar por pedido |
| `status` | string | — | Filtrar por status |
| `page` | int | 1 | Paginação |
| `limit` | int | 20 | Itens por página |

**Resposta:** Renderiza view ou retorna JSON (se AJAX).

---

## 3. Webhook (Adaptação)

### Endpoint — Resolução por Subdomínio

```
# URL nova (resolve tenant pelo subdomínio):
POST https://{subdomain}.useakti.com/api/webhooks/{gateway_slug}

# Exemplo:
POST https://empresa-x.useakti.com/api/webhooks/stripe

# Fallback legado (DEPRECADO — manter temporariamente):
POST /api/webhooks/{gateway_slug}?tenant={db_name}
```

**Resolução de tenant:**
1. O Node.js extrai o subdomínio do header `Host` (ex: `empresa-x.useakti.com` → `empresa-x`)
2. Consulta `akti_master.tenant_clients WHERE subdomain = ? AND is_active = 1`
3. Obtém `db_name` e conecta ao banco do tenant via `TenantPool`
4. Se subdomínio não encontrado → fallback para `?tenant=` (com log de deprecação)

**Vantagens sobre `?tenant=db_name`:**
- Nome do banco de dados nunca é exposto na URL
- Subdomínio é informação pública (já visível como URL de acesso do tenant)
- Sem parâmetros de query → URLs mais limpas para os gateways

### Envio Automático da Webhook URL

A URL de webhook é **injetada automaticamente** pelo `CheckoutService` no payload de cada cobrança:

```php
// Montagem no CheckoutService antes de createCharge():
$webhookUrl = "https://{$subdomain}.useakti.com/api/webhooks/{$gatewaySlug}";
$chargeData['notification_url'] = $webhookUrl;
```

| Gateway | Como a URL é enviada | Campo no payload |
|---------|---------------------|------------------|
| **Stripe** | Auto-registro via `/v1/webhook_endpoints` (uma vez) | Não vai no payload de cada cobrança |
| **MercadoPago** | Enviada a cada cobrança | `notification_url` |
| **PagSeguro** | Enviada a cada cobrança | `notification_urls[]` |

> **O usuário não precisa configurar webhooks manualmente.** O sistema cuida automaticamente do envio e registro.

### Processamento do Webhook

Ao processar pagamento confirmado, o `WebhookService` deve:

1. Verificar se o `metadata` do pagamento contém `checkout_token_id`
2. Se sim, atualizar `checkout_tokens`:
   - `status = 'used'`
   - `used_at = NOW()`
   - `used_method = <método do pagamento>`
   - `external_id = <id da transação>`

**Query adicional no WebhookService:**

```sql
UPDATE checkout_tokens
SET status = 'used',
    used_at = NOW(),
    used_method = :method,
    external_id = :external_id
WHERE order_id = :order_id
  AND status = 'active'
  AND (installment_id = :installment_id OR installment_id IS NULL)
```

---

## 4. Diagrama de Sequência

### PIX Flow

```
Cliente          Akti (PHP)         Gateway API        Webhook (Node.js)
  │                  │                   │                    │
  │  GET /checkout   │                   │                    │
  │  ?token=xxx      │                   │                    │
  │─────────────────>│                   │                    │
  │                  │ valida token      │                    │
  │  HTML (pay.php)  │                   │                    │
  │<─────────────────│                   │                    │
  │                  │                   │                    │
  │  POST process    │                   │                    │
  │  {method:'pix'}  │                   │                    │
  │─────────────────>│                   │                    │
  │                  │ monta webhook URL │                    │
  │                  │ (por subdomínio)  │                    │
  │                  │                   │                    │
  │                  │ createCharge()    │                    │
  │                  │ +notification_url │                    │
  │                  │──────────────────>│                    │
  │                  │  {qr_code, ...}  │                    │
  │                  │<──────────────────│                    │
  │  {qr_code}       │                   │                    │
  │<─────────────────│                   │                    │
  │                  │                   │                    │
  │  [Redirect para  │                   │                    │
  │   confirmation   │                   │                    │
  │   ?status=pending│                   │                    │
  │   &external_id]  │                   │                    │
  │                  │                   │                    │
  │                  │                   │  webhook POST      │
  │                  │                   │  (via subdomínio)  │
  │                  │                   │──────────────────>│
  │                  │                   │                   │ resolve tenant
  │                  │                   │                   │  (Host header)
  │                  │                   │                   │ valida assinatura
  │                  │                   │                   │ atualiza installment
  │                  │                   │                   │ atualiza token
  │                  │                   │  HTTP 200          │
  │                  │                   │<──────────────────│
  │                  │                   │                    │
  │  GET checkStatus │                   │                    │
  │  (polling 5s na  │                   │                    │
  │   confirmation)  │                   │                    │
  │─────────────────>│                   │                    │
  │                  │  getChargeStatus()│                    │
  │                  │──────────────────>│                    │
  │  {paid: true}    │                   │                    │
  │<─────────────────│                   │                    │
  │                  │                   │                    │
  │  [Transição      │                   │                    │
  │   dinâmica para  │                   │                    │
  │   CONFIRMADO]    │                   │                    │
```

### Credit Card Flow

```
Cliente           SDK JS (Gateway)    Akti (PHP)          Gateway API
  │                    │                  │                    │
  │  [Preenche form]   │                  │                    │
  │  [Dados no iframe] │                  │                    │
  │───────────────────>│                  │                    │
  │                    │  tokenize()      │                    │
  │                    │─────────────────────────────────────>│
  │  card_token        │                  │                    │
  │<───────────────────│                  │                    │
  │                    │                  │                    │
  │  POST process      │                  │                    │
  │  {method:'card',   │                  │                    │
  │   card_token:'...' │                  │                    │
  │───────────────────────────────────>  │                    │
  │                    │                  │  createCharge()    │
  │                    │                  │  (com card_token)  │
  │                    │                  │───────────────────>│
  │                    │                  │  {status:succeeded}│
  │                    │                  │<───────────────────│
  │  {succeeded}       │                  │                    │
  │<───────────────────────────────────  │                    │
  │                    │                  │                    │
  │  [Redirect para    │                  │                    │
  │   confirmation     │                  │                    │
  │   ?status=succeeded│                  │                    │
  │   &token=xxx]      │                  │                    │
```

---

*Especificação de API — Checkout Transparente v1 — 2026-04-08*
