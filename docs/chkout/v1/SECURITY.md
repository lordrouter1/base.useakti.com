# Checkout Transparente — Especificação de Segurança

> Análise de ameaças, controles implementados e checklist de segurança para o módulo de checkout transparente.

---

## 1. Modelo de Ameaças

### 1.1 Superfície de Ataque

O checkout transparente expõe **endpoints públicos** (sem autenticação), o que amplia a superfície de ataque comparado ao fluxo admin-only atual.

| Componente | Exposição | Risco |
|------------|-----------|-------|
| URL do checkout (`/?page=checkout&token=xxx`) | Pública | Token leaking, brute-force |
| Endpoint de processamento (`processPayment`) | Pública (com token) | Replay, manipulação de valor |
| Endpoint de status (`checkStatus`) | Pública (com token) | Information disclosure |
| Dados de cartão (frontend) | Browser do cliente | XSS, MITM, keylogging |
| Webhook (Node.js) | Internet | Spoofing, replay, signature bypass |

### 1.2 Threat Matrix (STRIDE)

| Ameaça | Tipo STRIDE | Cenário | Mitigação |
|--------|-------------|---------|-----------|
| Token forjado | Spoofing | Atacante gera token falso para pagar valor diferente | Token de 256 bits, valor no DB, não no frontend |
| Token vazado | Information Disclosure | Link compartilhado em ambiente público | Token expira em 48h, uso único, cancelável |
| Brute force de token | Tampering | Atacante tenta muitos tokens | Token de 64 chars hex = 16^64 combinações = inviável |
| Manipulação de valor | Tampering | Atacante altera amount no request | Valor lido do DB (checkout_tokens.amount), ignorado do request |
| Double-charge | Repudiation | Processar o mesmo pagamento 2x | Idempotency key (token+método+tentativa) |
| XSS na view | Info Disclosure | Script malicioso na descrição do pedido | Escape com `e()` em todos os dados |
| Clickjacking | Spoofing | Checkout embutido em iframe malicioso | `X-Frame-Options: DENY` |
| MITM (dados de cartão) | Info Disclosure | Interceptação de dados de cartão | HTTPS obrigatório, dados nunca passam pelo servidor |
| Webhook forjado | Spoofing | Atacante envia webhook falso para confirmar pagamento | Validação HMAC obrigatória |
| Rate limiting bypass | DoS | Múltiplas tentativas de pagamento em sequência | Rate limit por token + por IP |

---

## 2. Controles de Segurança

### 2.1 Geração do Token

```
Algoritmo:  bin2hex(random_bytes(32))
Entropia:   256 bits (2^256 combinações possíveis)
Formato:    64 caracteres hexadecimais [a-f0-9]
Exemplo:    a3f8c72e91b4d60857e2f1a3c9d4b76e8f2a1c3d5e7b9f0a2c4d6e8f0b2a4c6
Tempo para brute force: Inviável (> idade do universo com hardware atual)
```

### 2.2 Validação do Token

Em **toda requisição** que recebe token:

```php
// 1. Formato
if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
    // Rejeitar: formato inválido
}

// 2. Existência + status + expiração (query única)
$stmt = $db->prepare(
    "SELECT * FROM checkout_tokens
     WHERE token = :token AND status = 'active' AND expires_at > NOW()
     LIMIT 1"
);

// 3. Se não encontrado → rejeitar
// 4. Se encontrado → usar dados do DB (amount, order_id, etc.)
```

### 2.3 Rate Limiting

| Endpoint | Limite | Escopo | Período |
|----------|--------|--------|---------|
| `show` (página) | 30 req | por IP | 1 minuto |
| `processPayment` | 5 req | por token | 10 minutos |
| `processPayment` | 20 req | por IP | 10 minutos |
| `checkStatus` | 60 req | por token | 1 minuto |

**Implementação sugerida:**

```php
// Usando payment_attempts no checkout_tokens
$token = $model->findByToken($tokenStr);

if ($token['payment_attempts'] >= 5) {
    $lastAttempt = new \DateTime($token['last_attempt_at']);
    $now = new \DateTime();
    $diff = $now->getTimestamp() - $lastAttempt->getTimestamp();
    
    if ($diff < 600) { // 10 minutos
        return ['success' => false, 'error' => 'rate_limited'];
    }
    
    // Se mais de 10 min, resetar contador
    $model->resetAttempts($token['id']);
}

// Incrementar tentativa
$model->incrementAttempt($token['id']);
```

### 2.4 Proteção contra Manipulação de Valor

**Regra absoluta:** O valor cobrado vem **exclusivamente** do banco de dados.

```php
// NÃO fazer isso:
$amount = $_POST['amount']; // ❌ NUNCA

// Fazer isso:
$token = $model->findByToken($tokenStr);
$amount = $token['amount']; // ✅ Do banco
```

O campo `amount` é definido na **geração do token** e nunca mais é alterado.

### 2.5 PCI DSS Compliance

O checkout transparente usa **tokenização client-side** para dados de cartão:

```
┌──────────────┐     ┌──────────────────┐     ┌──────────────┐
│  Browser     │     │  Gateway SDK     │     │  Gateway API │
│  do Cliente  │────>│  (JS iframe)     │────>│              │
│              │     │  Tokeniza dados  │     │  Retorna     │
│              │<────│  de cartão       │<────│  card_token  │
└──────┬───────┘     └──────────────────┘     └──────────────┘
       │
       │ card_token (não dados do cartão)
       ▼
┌──────────────┐     ┌──────────────┐
│  Akti PHP    │────>│  Gateway API │
│  (servidor)  │     │  Processa    │
│  Recebe      │<────│  Pagamento   │
│  card_token  │     │              │
└──────────────┘     └──────────────┘
```

**O servidor Akti NUNCA vê:**
- Número do cartão (PAN)
- CVV / CVC
- Data de validade

**Nível de conformidade:** SAQ A-EP (Eligible Provider)

### 2.6 HTTPS Obrigatório

```php
// No CheckoutController, antes de tudo:
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
    // Em produção: redirecionar para HTTPS
    if ($_SERVER['HTTP_HOST'] !== 'localhost' && !str_starts_with($_SERVER['HTTP_HOST'], '127.0.0.1')) {
        header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], true, 301);
        exit;
    }
}
```

### 2.7 Headers de Segurança

```php
// Aplicados no CheckoutController para todas as actions
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

// CSP dinâmico baseado no gateway
$cspSources = [
    'stripe' => "script-src 'self' https://js.stripe.com; frame-src https://js.stripe.com https://hooks.stripe.com;",
    'mercadopago' => "script-src 'self' https://sdk.mercadopago.com; frame-src https://www.mercadopago.com.br;",
    'pagseguro' => "script-src 'self' https://assets.pagseguro.com.br; frame-src https://pagseguro.uol.com.br;",
];
header('Content-Security-Policy: default-src \'self\'; ' . ($cspSources[$gatewaySlug] ?? ''));
```

### 2.8 Idempotency

Para evitar cobranças duplicadas:

```php
// Gerar idempotency key única por token + método + tentativa
$idempotencyKey = 'akti_checkout_' . $token['id'] . '_' . $method . '_' . $token['payment_attempts'];

// Enviar ao gateway
$chargeData['idempotency_key'] = $idempotencyKey;
```

### 2.9 Proteção XSS nas Views

```php
// OBRIGATÓRIO em todos os dados dinâmicos:
<?= e($order['description']) ?>     // ✅
<?= e($token['customer_name']) ?>   // ✅
<?= e($company['name']) ?>          // ✅

// NUNCA:
<?= $order['description'] ?>        // ❌ XSS!
```

### 2.10 Auditoria e Logging

Todas as ações no checkout são logadas:

```php
// Em storage/logs/checkout.log
[2026-04-08 14:32:15] CHECKOUT.ACCESS token=abc123 ip=192.168.1.100 ua=Mozilla/5.0...
[2026-04-08 14:32:18] CHECKOUT.PROCESS token=abc123 method=pix ip=192.168.1.100 attempt=1
[2026-04-08 14:32:19] CHECKOUT.CHARGE_CREATED token=abc123 external_id=pi_1Abc gateway=stripe
[2026-04-08 14:35:42] CHECKOUT.CONFIRMED token=abc123 external_id=pi_1Abc via=webhook
```

---

## 3. Webhook Security (Revisão)

### 3.1 Validação de Assinatura (Já Implementada)

O `WebhookService.js` já valida HMAC para cada gateway:

| Gateway | Algoritmo | Header |
|---------|-----------|--------|
| Stripe | HMAC-SHA256 | `stripe-signature` (formato: `t=xxx,v1=xxx`) |
| Mercado Pago | HMAC-SHA256 | `x-signature` (formato: `ts=xxx,v1=xxx`) |
| PagSeguro | HMAC-SHA256 | `x-pagseguro-signature` |

### 3.2 Resolução de Tenant por Subdomínio

A URL de webhook é montada automaticamente pelo `CheckoutService` usando o **subdomínio do tenant** em vez de expor o nome do banco:

```
# URL gerada pelo backend (resolve por subdomínio):
POST https://empresa-x.useakti.com/api/webhooks/stripe

# URL antiga DEPRECADA (expunha o DB):
POST /api/webhooks/stripe?tenant=akti_empresa_x
```

**Validações de segurança na geração:**
- URL obrigatoriamente HTTPS (MercadoPago rejeita HTTP)
- Validação contra path traversal (`/../` no subdomínio)
- `filter_var($url, FILTER_VALIDATE_URL)` antes de usar
- Subdomínio consultado na `akti_master.tenant_clients` com `is_active = 1`

**Validações na recepção (Node.js):**
- Subdomínio extraído do header `Host` (não manipulável pelo payload)
- Consulta ao master DB com prepared statement
- Fallback para `?tenant=` legado com log de deprecação (para remoção futura)
- Se subdomínio não encontrado → HTTP 404 (não revela se tenant existe)

### 3.3 Auto-Registro de Webhook (Stripe)

O Stripe requer registro prévio do webhook endpoint. O sistema registra automaticamente:

- `POST /v1/webhook_endpoints` com URL e eventos necessários
- O `webhook_secret` (signing secret) retornado é armazenado em `payment_gateways.webhook_secret`
- Verificação de idempotência: antes de registrar, verifica se `webhook_endpoint_id` já existe
- Se URL mudou (ex: migração de domínio): atualiza endpoint existente

### 3.4 Proteção Adicional para Checkout

Ao processar webhook de pagamento que veio de checkout transparente:

1. Verificar que `checkout_tokens.amount` == `webhook.amount` (integridade)
2. Verificar que `checkout_tokens.status` == `active` (não reprocessar token já usado)
3. Marcar token como `used` atomicamente (evitar race condition)

```sql
-- Query atômica: só atualiza se ainda active
UPDATE checkout_tokens
SET status = 'used', used_at = NOW(), used_method = ?, external_id = ?
WHERE id = ? AND status = 'active'
-- Se affected_rows = 0, token já foi usado (race condition prevenida)
```

---

## 4. Checklist de Segurança

### Pré-Deploy

- [ ] Token gerado com `random_bytes(32)` (CSPRNG)
- [ ] Valor cobrado vem exclusivamente do DB
- [ ] Rate limiting implementado (por token e por IP)
- [ ] HTTPS verificado no controller (redirect em produção)
- [ ] Headers de segurança aplicados (X-Frame-Options, CSP, etc.)
- [ ] Dados de cartão nunca trafegam pelo servidor PHP
- [ ] Todos os dados escapados com `e()` nas views
- [ ] Prepared statements em todas as queries
- [ ] Webhook valida assinatura HMAC
- [ ] Webhook URL não expõe nome do banco (resolve por subdomínio)
- [ ] Webhook URL validada (HTTPS, sem path traversal) antes de enviar ao gateway
- [ ] Stripe webhook_secret armazenado com segurança após auto-registro
- [ ] Fallback `?tenant=` registra log de deprecação
- [ ] Token marcado como `used` atomicamente
- [ ] Idempotency key enviada ao gateway
- [ ] IP do visitante registrado
- [ ] Logging de todas as ações do checkout
- [ ] CSP configurado por gateway (permitir SDKs)

### Testes de Segurança

- [ ] Token inválido (formato errado) → rejeitar
- [ ] Token inexistente → rejeitar
- [ ] Token expirado → página de expirado
- [ ] Token usado → página de sucesso (não reprocessar)
- [ ] Token cancelado → página de expirado
- [ ] Valor manipulado no request → ignorado (usar DB)
- [ ] Mais de 5 tentativas → rate limited
- [ ] Sem HTTPS em produção → redirect 301
- [ ] XSS em descrição do pedido → escape funciona
- [ ] Webhook sem assinatura → rejeitar
- [ ] Webhook com assinatura inválida → rejeitar
- [ ] Webhook via subdomínio inexistente → HTTP 404
- [ ] Webhook URL gerada com subdomínio inválido → exceção
- [ ] Double-click no botão pagar → idempotency previne duplicata

---

*Especificação de Segurança — Checkout Transparente v1 — 2026-04-08*
