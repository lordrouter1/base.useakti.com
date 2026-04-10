# Checkout Transparente — Roadmap v1

> **Objetivo:** Criar uma página pública de checkout transparente hospedada no próprio Akti, que processa o pagamento via gateway padrão do tenant, eliminando o redirecionamento para checkouts externos (Stripe Checkout, Mercado Pago init_point, etc.).  
> **Data de criação:** 2026-04-08  
> **Status:** Planejamento

---

## Sumário

1. [Visão Geral](#1-visão-geral)
2. [Problema Atual vs. Solução Proposta](#2-problema-atual-vs-solução-proposta)
3. [Arquitetura Proposta](#3-arquitetura-proposta)
4. [Banco de Dados](#4-banco-de-dados)
5. [Backend — Rotas e Controllers](#5-backend--rotas-e-controllers)
6. [Backend — Service Layer](#6-backend--service-layer)
7. [Backend — Adaptação dos Gateways](#7-backend--adaptação-dos-gateways)
8. [Frontend — Página de Checkout](#8-frontend--página-de-checkout)
9. [Frontend — Página de Confirmação de Pagamento](#9-frontend--página-de-confirmação-de-pagamento)
10. [Segurança](#10-segurança)
11. [Integração com Fluxo Existente](#11-integração-com-fluxo-existente)
12. [Webhook Automático — Envio Dinâmico e Resolução por Subdomínio](#12-webhook-automático--envio-dinâmico-e-resolução-por-subdomínio)
13. [Webhooks e Confirmação](#13-webhooks-e-confirmação)
14. [Fases de Implementação](#14-fases-de-implementação)
15. [Checklist Final](#15-checklist-final)

---

## 1. Visão Geral

### O que é o Checkout Transparente?

É uma página pública (sem login) hospedada no domínio do tenant que:

- Exibe os dados do pedido/parcela (valor, descrição, vencimento)
- Oferece as formas de pagamento suportadas pelo gateway padrão (PIX, Cartão, Boleto)
- Processa o pagamento **diretamente via API do gateway** sem redirecionar o cliente
- Exibe resultado em tempo real (QR Code PIX, confirmação de cartão, PDF de boleto)
- É acessível via **link único com token seguro** (sem necessidade de login)

### Por que implementar?

| Antes (Link Externo) | Depois (Checkout Transparente) |
|---|---|
| Cliente é redirecionado para Stripe/MercadoPago | Cliente permanece no domínio do tenant |
| Perda de identidade visual | Marca do tenant preservada (logo, cores) |
| Sem controle sobre UX | UX totalmente customizável |
| Dependência de checkout hosted | Independência visual do gateway |
| Um método por link | Múltiplos métodos na mesma página |
| Sem rastreio interno | Analytics e rastreio completos |

---

## 2. Problema Atual vs. Solução Proposta

### Fluxo Atual

```
Operador gera link → Gateway retorna URL externa → Cliente abre link externo
                                                    → Paga no checkout do gateway
                                                    → Webhook confirma pagamento
```

**Limitações:**
- O `payment_link_url` salvo na tabela `orders` aponta para URL do gateway (ex: `https://checkout.stripe.com/c/pay/cs_xxxx`)
- Cada link suporta apenas o método selecionado na geração
- Não é possível oferecer PIX + Cartão + Boleto na mesma página
- Identidade visual do tenant não aparece

### Fluxo Proposto

```
Operador gera checkout → Sistema cria token único → Link interno gerado
                                                     ↓
Cliente abre link interno → Página pública no Akti → Vê dados do pedido
                                                     → Escolhe método (PIX/Cartão/Boleto)
                                                     → Preenche dados (se cartão)
                                                     → Akti processa via API do gateway
                                                     → Exibe resultado (QR/Confirmação/Boleto)
                                                     → Webhook confirma pagamento
```

---

## 3. Arquitetura Proposta

### 3.1 Diagrama de Componentes

```
┌─────────────────────────────────────────────────────────────────┐
│                     CHECKOUT TRANSPARENTE                        │
│                                                                  │
│  [URL Pública]                                                   │
│  /?page=checkout&token=abc123def456                              │
│       ↓                                                          │
│  CheckoutController (public, before_auth)                        │
│       ↓ valida token                                             │
│       ↓ resolve tenant                                           │
│       ↓ carrega pedido/parcela                                   │
│       ↓                                                          │
│  ┌──────────────────────────────────────────────────────┐       │
│  │  View: checkout/pay.php (página pública)             │       │
│  │                                                       │       │
│  │  ┌─────────────┐ ┌──────────┐ ┌──────────────┐      │       │
│  │  │  PIX        │ │  Cartão  │ │  Boleto      │      │       │
│  │  │  (QR Code)  │ │  (Form)  │ │  (Gerar PDF) │      │       │
│  │  └─────────────┘ └──────────┘ └──────────────┘      │       │
│  └──────────────────────────────────────────────────────┘       │
│       ↓ AJAX POST                                                │
│  CheckoutController::processPayment()                            │
│       ↓                                                          │
│  CheckoutService::processCheckout()                              │
│       ↓                                                          │
│  GatewayManager::resolveFromRow() → $gateway->createCharge()    │
│       ↓                                                          │
│  [Gateway API: Stripe / MercadoPago / PagSeguro]                │
│       ↓ resposta                                                 │
│  Retorna resultado ao frontend (QR Code / status / boleto)      │
│       ↓                                                          │
│  [Webhook] → Confirma pagamento → Atualiza parcela/pedido       │
└─────────────────────────────────────────────────────────────────┘
```

### 3.2 Arquivos Novos

```
app/
├── controllers/
│   └── CheckoutController.php        # Controller público do checkout
├── services/
│   └── CheckoutService.php           # Lógica de negócio do checkout
├── models/
│   └── CheckoutToken.php             # Model para tokens de checkout
└── views/
    └── checkout/
        ├── pay.php                   # Página principal de pagamento
        ├── confirmation.php          # Página de confirmação (3 estados)
        ├── expired.php               # Token expirado/inválido
        └── partials/
            ├── _header.php            # Cabeçalho standalone
            ├── _footer.php            # Rodapé com selo de segurança
            ├── _order_summary.php     # Card de resumo do pedido
            ├── _pix.php              # Tab de PIX (QR Code)
            ├── _credit_card.php      # Tab de Cartão de Crédito
            ├── _boleto.php           # Tab de Boleto
            ├── _confirmation_success.php    # Estado: pagamento confirmado
            ├── _confirmation_pending.php    # Estado: aguardando confirmação
            └── _confirmation_error.php      # Estado: erro no pagamento

assets/
├── css/
│   └── checkout.css                  # Estilos do checkout
└── js/
    └── checkout.js                   # Lógica JS do checkout
```

### 3.3 Arquivos Modificados

```
app/config/routes.php                  # Nova rota 'checkout' (pública)
app/config/menu.php                    # Sem alteração (não aparece no menu)
app/services/PipelinePaymentService.php # Gerar link de checkout ao invés de link externo
app/controllers/PaymentGatewayController.php # Ação de gerar checkout link
app/views/financial/installments.php   # Botão "Gerar Checkout" ao invés de "Gerar Link"
app/views/portal/orders/detail.php     # Exibir link de checkout transparente
```

---

## 4. Banco de Dados

### 4.1 Nova Tabela: `checkout_tokens`

Armazena tokens únicos vinculados a pedidos/parcelas para acesso público seguro.

```sql
CREATE TABLE IF NOT EXISTS checkout_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token VARCHAR(64) NOT NULL UNIQUE,
    order_id INT NOT NULL,
    installment_id INT NULL,
    gateway_slug VARCHAR(50) NULL,
    amount DECIMAL(12,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'BRL',
    allowed_methods JSON NULL COMMENT '["pix","credit_card","boleto"] ou null=todos',
    status ENUM('active','used','expired','cancelled') DEFAULT 'active',
    customer_name VARCHAR(255) NULL,
    customer_email VARCHAR(255) NULL,
    customer_document VARCHAR(20) NULL,
    metadata JSON NULL,
    ip_address VARCHAR(45) NULL COMMENT 'IP do pagador ao acessar',
    used_at DATETIME NULL,
    expires_at DATETIME NOT NULL,
    created_by INT NULL COMMENT 'user_id que gerou o link',
    tenant_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_token (token),
    INDEX idx_order (order_id),
    INDEX idx_status_expires (status, expires_at),
    INDEX idx_tenant (tenant_id),
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (installment_id) REFERENCES order_installments(id) ON DELETE SET NULL,
    FOREIGN KEY (tenant_id) REFERENCES akti_master.tenant_clients(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 4.2 Campos da Tabela

| Coluna | Tipo | Descrição |
|--------|------|-----------|
| `id` | INT PK | ID auto-incremento |
| `token` | VARCHAR(64) UNIQUE | Token criptograficamente seguro (hex) |
| `order_id` | INT FK | Pedido vinculado |
| `installment_id` | INT FK NULL | Parcela específica (null = pedido inteiro) |
| `gateway_slug` | VARCHAR(50) NULL | Gateway fixo (null = usar padrão do tenant) |
| `amount` | DECIMAL(12,2) | Valor a cobrar |
| `currency` | VARCHAR(3) | Moeda (BRL) |
| `allowed_methods` | JSON NULL | Métodos permitidos (null = todos do gateway) |
| `status` | ENUM | `active`, `used`, `expired`, `cancelled` |
| `customer_name` | VARCHAR(255) | Nome do cliente (pré-preenchido) |
| `customer_email` | VARCHAR(255) | Email do cliente |
| `customer_document` | VARCHAR(20) | CPF/CNPJ do cliente |
| `metadata` | JSON NULL | Dados extras (referência interna, notas) |
| `ip_address` | VARCHAR(45) | IP do cliente ao acessar o checkout |
| `used_at` | DATETIME NULL | Quando o pagamento foi processado |
| `expires_at` | DATETIME | Expiração do link |
| `created_by` | INT NULL | Usuário que gerou o link |
| `tenant_id` | INT FK | Tenant proprietário |
| `created_at` | DATETIME | Criação do registro |
| `updated_at` | DATETIME | Última atualização |

### 4.3 Alteração na Tabela `orders`

Adicionar campo para guardar o link do checkout transparente:

```sql
ALTER TABLE orders
    ADD COLUMN checkout_token_id INT NULL AFTER payment_link_created_at,
    ADD FOREIGN KEY (checkout_token_id) REFERENCES checkout_tokens(id) ON DELETE SET NULL;
```

---

## 5. Backend — Rotas e Controllers

### 5.1 Nova Rota em `routes.php`

```php
'checkout' => [
    'controller'     => 'CheckoutController',
    'default_action' => 'show',
    'public'         => true,
    'before_auth'    => true,
    'actions'        => [
        'show'           => 'show',           // GET: exibir página de checkout
        'processPayment' => 'processPayment', // POST (AJAX): processar pagamento
        'checkStatus'    => 'checkStatus',    // GET (AJAX): polling de status
        'confirmation'   => 'confirmation',   // GET: página de confirmação (sucesso/pendente/erro)
    ],
],
```

**Notas:**
- `public: true` + `before_auth: true` — acessível sem login
- Autenticação via token no query string, não via sessão
- CSRF não se aplica (página pública), mas proteger com rate-limiting

### 5.2 CheckoutController

**Arquivo:** `app/controllers/CheckoutController.php`

```
Namespace: Akti\Controllers
Extends: BaseController (ou standalone para páginas públicas)
```

**Métodos:**

| Método | HTTP | Descrição |
|--------|------|-----------|
| `show()` | GET | Valida token, carrega dados, renderiza view |
| `processPayment()` | POST (AJAX) | Processa pagamento via gateway |
| `checkStatus()` | GET (AJAX) | Verifica status da cobrança (polling) |
| `confirmation()` | GET | Página de confirmação de pagamento (3 estados) |

#### `show()`

```
1. Capturar token do query string: $_GET['token']
2. Validar formato do token (64 char hex)
3. Buscar registro em checkout_tokens via CheckoutToken model
4. Verificar:
   - Token existe
   - status = 'active'
   - expires_at > NOW()
   - tenant_id — resolver DB do tenant
5. Carregar dados do pedido (Order model) e parcela (Installment model)
6. Resolver gateway padrão do tenant (GatewayManager)
7. Obter métodos suportados pelo gateway
8. Filtrar por allowed_methods do token (se definido)
9. Carregar company_settings do tenant (logo, nome da empresa, cores)
10. Gravar IP do visitante: checkout_tokens.ip_address
11. Renderizar view: app/views/checkout/pay.php
```

#### `processPayment()`

```
1. Receber via AJAX POST:
   - token (string)
   - method (string: 'pix', 'credit_card', 'boleto')
   - card_token (string, apenas para cartão — gerado pelo JS do gateway)
   - customer_document (string, CPF/CNPJ — se não pré-preenchido)
   - customer_name (string, se necessário)
   - customer_email (string, se necessário)
2. Validar token (ativo, não expirado, não usado)
3. Rate limiting: max 5 tentativas por token por 10 min
4. Delegar para CheckoutService::processCheckout()
5. Retornar JSON com resultado:
   - PIX: { qr_code, qr_code_base64, expires_at }
   - Cartão: { status: 'succeeded' | 'requires_action', client_secret }
   - Boleto: { boleto_url, boleto_barcode, due_date }
6. Se pagamento bem-sucedido: marcar token como 'used'
```

#### `checkStatus()`

```
1. Receber token + external_id
2. Validar token
3. Consultar gateway: $gateway->getChargeStatus($externalId)
4. Retornar status atualizado
5. Usado para polling quando PIX/boleto ainda pendente
```

#### `confirmation()`

```
1. Receber token via query string: $_GET['token']
2. Receber status opcional: $_GET['status'] (succeeded, pending, error)
3. Receber external_id opcional: $_GET['external_id']
4. Buscar checkout_token no banco (qualquer status exceto 'cancelled')
5. Verificar se token existe
6. Determinar estado da página:
   a) Se token.status = 'used' → ESTADO: CONFIRMADO
      - Exibir dados do pagamento confirmado
      - Mostrar método usado (PIX/Cartão/Boleto)
      - Mostrar data/hora da confirmação
   b) Se $_GET['status'] = 'error' → ESTADO: ERRO
      - Exibir mensagem do erro (via $_GET['error_message'] ou genérica)
      - Botão "Tentar novamente" → redireciona para /?page=checkout&token=xxx
      - Se token ainda ativo: permitir retry
      - Se token expirado/usado: orientar contatar vendedor
   c) Se token.status = 'active' E external_id presente → ESTADO: AGUARDANDO
      - Consultar status no gateway: $gateway->getChargeStatus($externalId)
      - Se já aprovado no gateway → marcar token como used → redirect para confirmation?status=succeeded
      - Se ainda pendente → exibir tela de aguardando
      - Polling automático a cada 5s via checkStatus()
      - Countdown para expiração
   d) Se token.status = 'expired' → renderizar expired.php
7. Carregar dados do pedido e company_settings (logo, nome, cores)
8. Renderizar view: checkout/confirmation.php
```

---

## 6. Backend — Service Layer

### 6.1 CheckoutService

**Arquivo:** `app/services/CheckoutService.php`

**Responsabilidades:**
- Gerar tokens de checkout
- Processar pagamentos vindos do checkout
- Gerenciar ciclo de vida dos tokens

**Métodos:**

#### `generateToken(array $params): array`

```
Parâmetros:
  - order_id (int, obrigatório)
  - installment_id (int|null)
  - gateway_slug (string|null — null = usar padrão)
  - allowed_methods (array|null — null = todos)
  - expires_in_hours (int, default: 48)
  - created_by (int|null — user_id)

Passos:
  1. Buscar pedido e validar existência
  2. Calcular amount (do pedido ou parcela específica)
  3. Buscar dados do cliente (name, email, document)
  4. Gerar token criptográfico: bin2hex(random_bytes(32)) → 64 chars
  5. Calcular expires_at = NOW() + expires_in_hours
  6. Inserir em checkout_tokens
  7. Gerar URL: {base_url}/?page=checkout&token={token}
  8. Retornar: { success, token, checkout_url, expires_at }
```

#### `processCheckout(string $token, array $paymentData): array`

```
Parâmetros:
  - token (string)
  - paymentData:
    - method (string: pix, credit_card, boleto)
    - card_token (string|null — tokenizado pelo JS do gateway)
    - customer_document (string|null)
    - customer_name (string|null)
    - customer_email (string|null)

Passos:
  1. Buscar checkout_token e validar (active, não expirado)
  2. Resolver tenant DB a partir do tenant_id do token
  3. Resolver gateway (do token ou padrão do tenant)
  4. Verificar se método é permitido (allowed_methods)
  5. Montar $chargeData:
     - amount, description, method, order_id, installment_id
     - customer (name, email, document)
     - metadata (token_id, source: 'transparent_checkout')
     - card_token (se cartão)
     - notification_url (webhook URL montada por subdomínio — ver seção 12)
  6. Resolver subdomínio do tenant e injetar webhook URL:
     - $webhookUrl = "https://{subdomain}.useakti.com/api/webhooks/{gateway_slug}"
     - $chargeData['notification_url'] = $webhookUrl
  7. Chamar $gateway->createCharge($chargeData)
  8. Logar transação em payment_gateway_transactions
  9. Se cartão com status 'succeeded':
     - Marcar token como 'used'
     - Atualizar parcela (se aplicável)
  10. Se PIX/boleto: token continua 'active' até webhook confirmar
  11. Retornar resultado padronizado
```

#### `cancelToken(int $tokenId): bool`

```
Atualizar status para 'cancelled'
```

#### `expireOldTokens(): int`

```
UPDATE checkout_tokens SET status = 'expired'
WHERE status = 'active' AND expires_at < NOW()
Retornar quantidade de tokens expirados (para cron job)
```

#### `getTokenByToken(string $token): ?array`

```
SELECT com JOIN em orders, order_installments
Retornar dados completos para renderização
```

### 6.2 Integração com CheckoutToken Model

**Arquivo:** `app/models/CheckoutToken.php`

**Métodos CRUD:**

| Método | Descrição |
|--------|-----------|
| `create(array $data): int` | Inserir novo token |
| `findByToken(string $token): ?array` | Buscar por token (com JOIN em orders) |
| `findByOrder(int $orderId): array` | Listar tokens de um pedido |
| `markUsed(int $id): bool` | Marcar como usado (`status='used'`, `used_at=NOW()`) |
| `markExpired(int $id): bool` | Marcar como expirado |
| `cancel(int $id): bool` | Cancelar token |
| `expireAll(): int` | Expirar todos os tokens vencidos |
| `updateIp(int $id, string $ip): bool` | Gravar IP do visitante |
| `getActiveByOrder(int $orderId): ?array` | Buscar token ativo de um pedido |

---

## 7. Backend — Adaptação dos Gateways

### 7.1 Alteração na Interface (Opcional, não-breaking)

O `PaymentGatewayInterface::createCharge($data)` já aceita `card_token` no `$data`. Não é necessário alterar a interface.

O campo `notification_url` será injetado automaticamente pelo `CheckoutService` no `$data` antes de chamar `createCharge()`. Cada gateway mapeia esse campo para o formato correto da respectiva API (ver [Seção 12 — Webhook Automático](#12-webhook-automático--envio-dinâmico-e-resolução-por-subdomínio)).

### 7.2 Suporte a Cobrança Direta por Gateway

Cada gateway já suporta dois modos de cobrança:

| Gateway | Com `card_token` | Sem `card_token` |
|---------|-------------------|-------------------|
| **Stripe** | `PaymentIntent` (cobrança direta) | `Checkout Session` (URL hosted) |
| **MercadoPago** | `POST /v1/payments` (cobrança direta) | `POST /checkout/preferences` (URL hosted) |
| **PagSeguro** | `POST /orders` com card data | `POST /orders` com redirect |

**Para o checkout transparente:**
- **PIX:** Sem `card_token`, o gateway gera QR Code diretamente → já funciona
- **Boleto:** Sem `card_token`, o gateway gera boleto diretamente → já funciona
- **Cartão:** Precisa de `card_token` gerado pelo JS do gateway no frontend

### 7.3 Tokenização de Cartão no Frontend

Cada gateway tem seu próprio SDK JS para capturar e tokenizar dados do cartão:

| Gateway | SDK JS | Função de Tokenização |
|---------|--------|----------------------|
| **Stripe** | Stripe.js + Elements | `stripe.confirmCardPayment(clientSecret)` ou `stripe.createPaymentMethod()` |
| **MercadoPago** | MercadoPago.js v2 | `mp.cardForm({...}).getCardFormData()` → retorna `token` |
| **PagSeguro** | PagSeguro.js | `PagSeguro.encryptCard({...})` → retorna `encryptedCard` |

**Estratégia:**
- O frontend do checkout detecta o gateway ativo
- Carrega dinamicamente o SDK JS correspondente
- Captura dados do cartão no iframe/form seguro do SDK (PCI compliance)
- Envia o token (não dados puros do cartão) ao `processPayment()`

### 7.4 Campos Necessários por Método

| Método | Campos no Frontend | Enviado ao Backend |
|--------|--------------------|--------------------|
| **PIX** | Nenhum (gera QR automaticamente) | `{ method: 'pix' }` |
| **Cartão** | Número, Nome, Validade, CVV (captados pelo SDK) | `{ method: 'credit_card', card_token: 'tok_xxx' }` |
| **Boleto** | CPF/CNPJ (se não pré-preenchido) | `{ method: 'boleto', customer_document: '123.456.789-00' }` |

---

## 8. Frontend — Página de Checkout

### 8.1 Layout Geral (`pay.php`)

```
┌────────────────────────────────────────────────────────────┐
│  [Logo do Tenant]        [Nome da Empresa]                  │
├────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌─────────────────────────────────────────────────────┐   │
│  │  RESUMO DO PEDIDO                                    │   │
│  │                                                      │   │
│  │  Pedido: #0042                                       │   │
│  │  Descrição: Impressão de 500 folhetos A4             │   │
│  │  Valor: R$ 850,00                                    │   │
│  │  Parcela: 1/3 - Venc: 15/04/2026                    │   │
│  │  ────────────────────────────────────                │   │
│  │  Total a pagar: R$ 283,33                            │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│  ┌─────────────────────────────────────────────────────┐   │
│  │  FORMA DE PAGAMENTO                                  │   │
│  │                                                      │   │
│  │  ┌──────┐  ┌──────────┐  ┌────────┐                │   │
│  │  │ PIX  │  │ Cartão   │  │ Boleto │                │   │
│  │  └──────┘  └──────────┘  └────────┘                │   │
│  │                                                      │   │
│  │  [Conteúdo da tab selecionada]                      │   │
│  │                                                      │   │
│  │  ┌──────────────────────────────────────────────┐   │   │
│  │  │  PIX:                                         │   │   │
│  │  │  [QR Code grande]                             │   │   │
│  │  │  Código copia e cola: 00020126...             │   │   │
│  │  │  [Copiar código]                              │   │   │
│  │  │  Expira em: 29:42                             │   │   │
│  │  └──────────────────────────────────────────────┘   │   │
│  │                                                      │   │
│  │  ┌──────────────────────────────────────────────┐   │   │
│  │  │  CARTÃO:                                      │   │   │
│  │  │  [Iframe SDK do gateway - PCI Compliant]      │   │   │
│  │  │  Número:  [________________]                  │   │   │
│  │  │  Nome:    [________________]                  │   │   │
│  │  │  Validade:[____] CVV: [___]                   │   │   │
│  │  │  CPF:     [________________]                  │   │   │
│  │  │                                               │   │   │
│  │  │  [💳 Pagar R$ 283,33]                         │   │   │
│  │  └──────────────────────────────────────────────┘   │   │
│  │                                                      │   │
│  │  ┌──────────────────────────────────────────────┐   │   │
│  │  │  BOLETO:                                      │   │   │
│  │  │  [Gerar Boleto]                               │   │   │
│  │  │  → Código de barras: 12345.67890...           │   │   │
│  │  │  → [Copiar código]  [Abrir PDF]               │   │   │
│  │  │  Vencimento: 11/04/2026                       │   │   │
│  │  └──────────────────────────────────────────────┘   │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│  🔒 Pagamento seguro processado por [Nome do Gateway]      │
│  Powered by Akti                                            │
└────────────────────────────────────────────────────────────┘
```

### 8.2 Responsividade

- Layout single-column em mobile (< 768px)
- Tabs de método de pagamento em pills horizontais
- QR Code adapta tamanho ao container
- Form de cartão empilha campos verticalmente em mobile
- Botão de pagamento full-width em mobile

### 8.3 Componentes CSS (`checkout.css`)

- Estilo standalone (não depende do layout admin header/footer)
- Utiliza Bootstrap 5 (CDN) como base
- Variáveis CSS para customização por tenant:
  - `--checkout-primary`: cor primária (da company_settings)
  - `--checkout-bg`: cor de fundo
  - `--checkout-text`: cor do texto
- Dark mode automático via `prefers-color-scheme`
- Animações sutis para transições de tab e loading states

### 8.4 JavaScript (`checkout.js`)

**Responsabilidades:**

1. **Inicialização:**
   - Detectar gateway ativo (dados passados via PHP/data attributes)
   - Carregar SDK JS do gateway correspondente (lazy load)
   - Inicializar formulário de cartão com SDK

2. **Tab switching:**
   - Alternar entre PIX / Cartão / Boleto
   - Lazy-load do conteúdo (PIX gera QR sob demanda)

3. **Processamento PIX:**
   - AJAX POST para `processPayment` com `method: 'pix'`
   - Exibir QR Code retornado (base64 → `<img>`)
   - Exibir código copia-e-cola
   - Countdown timer até expiração
   - Polling a cada 5s via `checkStatus` para detectar pagamento
   - Ao detectar pagamento → redirecionar para `confirmation?status=succeeded`

4. **Processamento Cartão:**
   - Tokenizar dados via SDK do gateway
   - AJAX POST para `processPayment` com `method: 'credit_card', card_token: '...'`
   - Tratar 3D Secure / requires_action (Stripe)
   - Exibir loading com SweetAlert2
   - Sucesso → redirecionar para `confirmation?status=succeeded`
   - Erro → redirecionar para `confirmation?status=error&error_message=...`
   - requires_action → manter na página para 3DS, após resolver → redirect

5. **Processamento Boleto:**
   - AJAX POST para `processPayment` com `method: 'boleto'`
   - Exibir código de barras
   - Botão copiar para clipboard
   - Botão abrir PDF (se disponível)

6. **Utilitários:**
   - Máscara de CPF/CNPJ
   - Formatação de moeda (R$ X.XXX,XX)
   - Rate limiting client-side (debounce no botão pagar)
   - Error handling global

### 8.5 Carregamento Dinâmico dos SDKs

```javascript
// Mapa de SDKs por gateway
const GATEWAY_SDKS = {
    stripe: {
        url: 'https://js.stripe.com/v3/',
        init: (publishableKey) => Stripe(publishableKey)
    },
    mercadopago: {
        url: 'https://sdk.mercadopago.com/js/v2',
        init: (publicKey) => new MercadoPago(publicKey)
    },
    pagseguro: {
        url: 'https://assets.pagseguro.com.br/checkout-sdk-js/rc/dist/browser/pagseguro.min.js',
        init: () => PagSeguro
    }
};
```

---

## 9. Frontend — Página de Confirmação de Pagamento

A página de confirmação é o destino final após o cliente interagir com o checkout. Ela lida com **3 estados distintos** e serve como ponto central de feedback pós-pagamento.

### 9.1 URL e Parâmetros

```
/?page=checkout&action=confirmation&token={token}&status={status}&external_id={id}&error_message={msg}
```

| Parâmetro | Obrigatório | Descrição |
|-----------|-------------|-----------|
| `token` | Sim | Token do checkout |
| `status` | Não | `succeeded`, `pending`, `error` (inferido do DB se omitido) |
| `external_id` | Não | ID da transação no gateway (para polling) |
| `error_message` | Não | Mensagem de erro (URL-encoded) |

### 9.2 Resolução Automática de Estado

Se `status` não for informado na URL, o controller resolve automaticamente:

```
token.status = 'used'     → CONFIRMADO
token.status = 'active'   → AGUARDANDO (se external_id presente) ou redireciona para pay
token.status = 'expired'  → redireciona para expired.php
token.status = 'cancelled'→ redireciona para expired.php
```

### 9.3 Estado: PAGAMENTO CONFIRMADO (`_confirmation_success.php`)

```
┌────────────────────────────────────────────────────┐
│  [Logo]  Nome da Empresa                            │
├────────────────────────────────────────────────────┤
│                                                     │
│              ✅ (animação checkmark)                  │
│                                                     │
│     Pagamento confirmado!                           │
│                                                     │
│  ┌─────────────────────────────────────────────┐  │
│  │  DETALHES DO PAGAMENTO                          │  │
│  │                                                  │  │
│  │  Pedido:       #0042                             │  │
│  │  Valor pago:   R$ 283,33                         │  │
│  │  Método:       PIX                               │  │
│  │  Data/hora:    08/04/2026 às 14:32               │  │
│  │  Status:       ✅ Confirmado                      │  │
│  │  ID transação: pi_1Abc123...                     │  │
│  └─────────────────────────────────────────────┘  │
│                                                     │
│  📧 Um comprovante foi enviado para seu email.       │
│                                                     │
│  [Voltar ao portal]  (se logado no portal)          │
│                                                     │
└────────────────────────────────────────────────────┘
```

**Dados exibidos:**
- Número do pedido
- Valor efetivamente pago
- Método de pagamento utilizado (PIX, Cartão de Crédito, Boleto)
- Data e hora da confirmação (`checkout_tokens.used_at`)
- Status visual: badge verde "Confirmado"
- ID da transação (`checkout_tokens.external_id`) — útil para suporte
- Mensagem de comprovante por email (se email disponível)

**Comportamento:**
- Página estática (sem polling)
- Pode ser acessada múltiplas vezes (token `used` sempre mostra esta página)
- Botão "Voltar ao portal" só aparece se sessão de portal ativa
- Animação CSS do checkmark ao carregar (feedback visual positivo)

### 9.4 Estado: AGUARDANDO CONFIRMAÇÃO (`_confirmation_pending.php`)

```
┌────────────────────────────────────────────────────┐
│  [Logo]  Nome da Empresa                            │
├────────────────────────────────────────────────────┤
│                                                     │
│              ⏳ (spinner animado)                    │
│                                                     │
│     Aguardando confirmação do pagamento...           │
│                                                     │
│  ┌─────────────────────────────────────────────┐  │
│  │  DETALHES                                       │  │
│  │                                                  │  │
│  │  Pedido:       #0042                             │  │
│  │  Valor:        R$ 283,33                         │  │
│  │  Método:       PIX                               │  │
│  │  Status:       ⏳ Processando                     │  │
│  └─────────────────────────────────────────────┘  │
│                                                     │
│  ℹ️  Estamos verificando seu pagamento.              │
│     Isso pode levar alguns instantes.               │
│                                                     │
│  ┌─────────────────────────────────────────────┐  │
│  │  Progresso:                                     │  │
│  │  [█████████████████░░░░░░░░░░░░░] 60%              │  │
│  │  Verificando a cada 5 segundos...                │  │
│  └─────────────────────────────────────────────┘  │
│                                                     │
│  📌 Se o pagamento foi via Boleto, a compensação     │
│     pode levar até 3 dias úteis.                     │
│                                                     │
│  [🔄 Verificar agora]                                │
│                                                     │
└────────────────────────────────────────────────────┘
```

**Cenários que levam a este estado:**

| Método | Cenário |
|--------|--------|
| **PIX** | QR Code gerado, cliente redirecionado após escanear (webhook ainda não chegou) |
| **Boleto** | Boleto gerado, pagamento pode levar até 3 dias úteis |
| **Cartão** | Gateway retornou `pending` ou `processing` (raro, mas possível) |
| **3D Secure** | Stripe `requires_action` foi resolvido mas webhook ainda não confirmou |

**Comportamento:**
- Polling automático a cada 5s via `checkStatus()` (JS)
- Ao detectar pagamento confirmado → atualizar a página para estado CONFIRMADO (sem reload completo)
- Barra de progresso visual (indeterminada, pulsa)
- Botão "Verificar agora" para checagem manual
- Mensagem específica para boleto: "A compensação pode levar até 3 dias úteis"
- Após 30 minutos sem confirmação → parar polling, exibir mensagem:
  "Se você já realizou o pagamento, ele será confirmado em breve. Você pode fechar esta página."

### 9.5 Estado: ERRO NO PAGAMENTO (`_confirmation_error.php`)

```
┌────────────────────────────────────────────────────┐
│  [Logo]  Nome da Empresa                            │
├────────────────────────────────────────────────────┤
│                                                     │
│              ❌ (animação X)                          │
│                                                     │
│     Não foi possível processar o pagamento           │
│                                                     │
│  ┌─────────────────────────────────────────────┐  │
│  │  DETALHES DO ERRO                               │  │
│  │                                                  │  │
│  │  Pedido:       #0042                             │  │
│  │  Valor:        R$ 283,33                         │  │
│  │  Método:       Cartão de Crédito                  │  │
│  │  Motivo:       Cartão recusado pela operadora     │  │
│  └─────────────────────────────────────────────┘  │
│                                                     │
│  ⚠️  O que pode ter acontecido:                      │
│     • Dados do cartão incorretos                     │
│     • Limite insuficiente                            │
│     • Cartão bloqueado pelo banco                    │
│     • Falha temporária na operadora                  │
│                                                     │
│  [🔄 Tentar novamente]    [📞 Contatar vendedor]    │
│                                                     │
└────────────────────────────────────────────────────┘
```

**Mensagens de erro por tipo:**

| Código do Gateway | Mensagem Amigável |
|-------------------|-------------------|
| `card_declined` | Cartão recusado pela operadora. Verifique os dados ou tente outro cartão. |
| `insufficient_funds` | Saldo ou limite insuficiente. Tente outro método de pagamento. |
| `expired_card` | Cartão expirado. Utilize um cartão válido. |
| `incorrect_cvc` | Código de segurança (CVV) incorreto. |
| `processing_error` | Erro temporário no processamento. Tente novamente em alguns minutos. |
| `authentication_required` | Autenticação do banco não concluída. Tente novamente. |
| `gateway_timeout` | O serviço de pagamento não respondeu. Tente novamente. |
| (genérico) | Ocorreu um erro ao processar o pagamento. Tente novamente ou entre em contato. |

**Comportamento:**
- Exibir mensagem amigável (nunca expor detalhes técnicos do gateway ao cliente)
- Se token ainda ativo e não expirado:
  - Botão "Tentar novamente" → redireciona para `/?page=checkout&token=xxx` (volta ao checkout)
  - Permite escolher outro método (ex: se cartão falhou, tentar PIX)
- Se token expirado:
  - Ocultar botão "Tentar novamente"
  - Exibir apenas "Contatar vendedor" com dados de contato
- Botão "Contatar vendedor" exibe telefone/email da empresa (`company_settings`)
- Logar o erro em `storage/logs/checkout.log` para diagnóstico

### 9.6 Transições entre Estados

```
┌───────────────┐
│  Checkout      │
│  (pay.php)    │
└───────┬───────┘
        │
        │ processPayment()
        │
   ┌────┴────────────────────────┐
   │                              │
   │ Resultado do gateway:        │
   │                              │
   │ succeeded ─────────────────┬─┼──── → confirmation?status=succeeded
   │                              │       (cartão aprovado instantly)
   │ pending (pix/boleto) ──────┼─┼──── → confirmation?status=pending&external_id=xxx
   │                              │       (polling na página de confirmação)
   │ error / declined ─────────┼─┼──── → confirmation?status=error&error_message=...
   │                              │
   └──────────────────────────────┘

        Página de Confirmação (PENDING):
        │
        │ polling checkStatus() a cada 5s
        │
        ├── approved  → atualiza DOM para CONFIRMADO (sem redirect)
        ├── pending   → continua polling
        ├── failed    → atualiza DOM para ERRO
        └── timeout (30 min) → exibe mensagem "feche a página"

        Página de Confirmação (ERRO):
        │
        ├── "Tentar novamente" → redirect para pay.php (se token ativo)
        └── "Contatar vendedor" → exibe dados de contato
```

### 9.7 JavaScript da Página de Confirmação

O `checkout.js` inclui lógica específica para a página de confirmação:

```javascript
// Apenas ativo no estado PENDING
AktiCheckout.confirmationPolling = {
    interval: null,
    maxAttempts: 360,     // 30 min ÷ 5s = 360 tentativas
    currentAttempt: 0,

    start(token, externalId) {
        this.interval = setInterval(() => {
            this.currentAttempt++;
            if (this.currentAttempt >= this.maxAttempts) {
                this.stop();
                this.showTimeoutMessage();
                return;
            }
            this.check(token, externalId);
        }, 5000);
    },

    async check(token, externalId) {
        const response = await fetch(
            `/?page=checkout&action=checkStatus&token=${token}&external_id=${externalId}`
        );
        const data = await response.json();

        if (data.paid) {
            this.stop();
            this.transitionToSuccess(data); // Atualiza DOM sem reload
        } else if (data.status === 'failed' || data.status === 'cancelled') {
            this.stop();
            this.transitionToError(data);
        }
    },

    transitionToSuccess(data) {
        // Fade out pending, fade in success
        // Animação de checkmark
        // Atualizar dados do pagamento no card
    },

    transitionToError(data) {
        // Fade out pending, fade in error
        // Exibir mensagem de erro
    },

    stop() {
        clearInterval(this.interval);
    },

    showTimeoutMessage() {
        // "Se você já realizou o pagamento, ele será confirmado em breve."
    }
};
```

### 9.8 CSS Específico da Confirmação

| Classe CSS | Uso |
|------------|-----|
| `.confirmation-icon` | Container do ícone principal (check/spinner/X) |
| `.confirmation-icon--success` | Check verde com animação de desenhar |
| `.confirmation-icon--pending` | Spinner rotativo laranja/azul |
| `.confirmation-icon--error` | X vermelho com animação |
| `.confirmation-title` | Título principal ("Pagamento confirmado!") |
| `.confirmation-details` | Card com detalhes do pagamento |
| `.confirmation-progress` | Barra de progresso (só pending) |
| `.confirmation-actions` | Container dos botões de ação |
| `.confirmation-fade-enter` | Animação de entrada ao trocar estado |
| `.confirmation-fade-exit` | Animação de saída ao trocar estado |

**Animações:**

```css
/* Checkmark animado (sucesso) */
@keyframes checkmark-draw {
    0%   { stroke-dashoffset: 50; }
    100% { stroke-dashoffset: 0; }
}

/* Pulse do spinner (aguardando) */
@keyframes pulse-ring {
    0%   { transform: scale(0.8); opacity: 1; }
    100% { transform: scale(1.4); opacity: 0; }
}

/* Shake do X (erro) */
@keyframes error-shake {
    0%, 100% { transform: translateX(0); }
    20%, 60% { transform: translateX(-5px); }
    40%, 80% { transform: translateX(5px); }
}

/* Transição suave entre estados */
.confirmation-fade-enter {
    animation: fadeIn 0.4s ease-out;
}
.confirmation-fade-exit {
    animation: fadeOut 0.3s ease-in;
}
```

---

## 10. Segurança

### 9.1 Proteções do Token

| Ameaça | Proteção |
|--------|----------|
| Token previsível | `bin2hex(random_bytes(32))` → 64 chars hex (256-bit entropy) |
| Replay attack | Token marcado como `used` após pagamento. Usos subsequentes bloqueados |
| Expiração | `expires_at` verificado em toda requisição. Default: 48h |
| Enumeração | Tokens longos (64 chars) inviabilizam brute-force |
| IP tracking | IP gravado na primeira visita para auditoria |
| Rate limiting | Max 5 tentativas de pagamento por token por 10 min |

### 10.2 Proteções de Pagamento

| Ameaça | Proteção |
|--------|----------|
| Manipulação de valor | Amount vem do DB (checkout_tokens.amount), não do frontend |
| XSS | Escape com `e()` em todos os dados dinâmicos na view |
| Injeção SQL | Prepared statements em todas as queries |
| Dados de cartão | Nunca trafegam pelo servidor — tokenizados pelo SDK JS (PCI DSS) |
| Man-in-the-middle | HTTPS obrigatório para checkout (verificar no controller) |
| Double-charge | Idempotency key enviada ao gateway (ordem+token+tentativa) |
| CSRF | Não se aplica (página pública), mas rate-limiting compensa |

### 10.3 PCI DSS Compliance

**O checkout transparente NÃO processa dados de cartão diretamente:**

1. Dados digitados pelo cliente → Iframe/Fields do SDK do gateway
2. SDK tokeniza → Retorna `card_token` ao JS do checkout
3. JS envia `card_token` ao backend PHP
4. PHP envia `card_token` ao gateway para processar
5. **Nenhum dado de cartão (PAN, CVV) trafega pelo servidor Akti**

Isso mantém o nível **SAQ A-EP** (o mais leve para checkout transparente).

### 10.4 Headers de Segurança

Adicionar no controller para a página de checkout:

```php
header('X-Frame-Options: DENY');                    // Impedir iframe (clickjacking)
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Content-Security-Policy: default-src \'self\'; script-src \'self\' https://js.stripe.com https://sdk.mercadopago.com https://assets.pagseguro.com.br; frame-src https://js.stripe.com https://www.mercadopago.com.br;');
```

---

## 11. Integração com Fluxo Existente

### 11.1 Geração do Link de Checkout

**Onde o link é gerado:** No mesmo ponto onde hoje se gera o `payment_link_url`.

**Mudança no `PipelinePaymentService`:**

```
ANTES:
  generatePaymentLink() → cria cobrança no gateway → salva URL externa

DEPOIS:
  generateCheckoutLink() → cria token no checkout_tokens → salva URL interna
  (O pagamento é processado quando o cliente abre o link e paga)
```

**Coexistência:** Manter `generatePaymentLink()` funcional para retrocompatibilidade. Adicionar novo método `generateCheckoutLink()`.

### 11.2 Botão no Admin (Financial/Pipeline)

Adicionar opção na UI existente:

```
┌─────────────────────────────────────────┐
│  Cobrar parcela #1 — R$ 283,33          │
│                                          │
│  [🔗 Gerar Link Externo (gateway)]      │  ← existente
│  [🛒 Gerar Checkout Transparente]       │  ← NOVO
│                                          │
│  Métodos permitidos:                     │
│  ☑ PIX  ☑ Cartão  ☑ Boleto             │
│                                          │
│  Expiração: [48] horas                   │
│                                          │
│  [Gerar Link]                            │
└─────────────────────────────────────────┘
```

### 11.3 Compartilhamento do Link

Após gerar, exibir modal com:

- URL do checkout (copiável)
- QR Code da URL (para WhatsApp/impresso)
- Botão "Enviar por WhatsApp"
- Botão "Enviar por Email"
- Botão "Copiar Link"

### 11.4 Atualização do Pedido

Após geração do checkout link:

```php
// Salvar na tabela orders
$orderModel->updatePaymentLink($orderId, [
    'payment_link_url'        => $checkoutUrl,
    'payment_link_gateway'    => $gatewaySlug ?: 'default',
    'payment_link_method'     => 'transparent_checkout',
    'payment_link_created_at' => date('Y-m-d H:i:s'),
]);

// Vincular token ao pedido
$orderModel->update($orderId, ['checkout_token_id' => $tokenId]);
```

### 11.5 Portal do Cliente

No portal (`app/views/portal/orders/detail.php`), ao invés de exibir link externo, exibir botão que abre o checkout transparente:

```php
<?php if (!empty($checkoutToken) && $checkoutToken['status'] === 'active'): ?>
    <a href="/?page=checkout&token=<?= e($checkoutToken['token']) ?>" 
       class="btn btn-success btn-lg" target="_blank">
        💳 Pagar agora
    </a>
<?php endif; ?>
```

---

## 12. Webhook Automático — Envio Dinâmico e Resolução por Subdomínio

### 12.1 Problema Atual

Atualmente o sistema exige que o usuário **configure manualmente** a URL de webhook no painel de cada gateway de pagamento (Stripe Dashboard, MercadoPago Developers, etc.). Além disso, a URL atual expõe o **nome do banco de dados** como parâmetro:

```
# URL atual — PROBLEMÁTICA
POST /api/webhooks/stripe?tenant=akti_empresa_x
                                  ^^^^^^^^^^^^^^^^
                                  Nome do DB exposto na URL
```

**Problemas:**
- **Configuração manual:** O usuário precisa copiar a URL de webhook, acessar o painel do gateway e colar manualmente — propenso a erros.
- **Exposição do DB:** O parâmetro `tenant=akti_empresa_x` revela o nome do banco de dados na URL, que é informação interna.
- **Desatualização:** Se a URL da API mudar, todos os gateways precisam ser atualizados manualmente.
- **Cada gateway exige configuração separada** — Stripe, MercadoPago e PagSeguro têm telas diferentes.

### 12.2 Solução Proposta

#### Princípio: Zero Configuração pelo Usuário

Toda vez que o checkout transparente comunicar com o gateway para criar uma cobrança (`createCharge()`), o backend **automaticamente injeta** a URL de webhook no payload enviado ao gateway. O gateway então chama essa URL quando o status do pagamento mudar.

#### Resolução por Subdomínio (sem parâmetro `tenant`)

O webhook passa a usar o **subdomínio do tenant** para resolver o banco de dados, eliminando a exposição do nome do banco:

```
# URL antiga (expõe DB)
POST /api/webhooks/stripe?tenant=akti_empresa_x

# URL nova (resolve por subdomínio)
POST https://empresa-x.useakti.com/api/webhooks/stripe
            ^^^^^^^^^^
            Subdomínio identifica o tenant
```

**Vantagens:**
- O nome do banco **nunca aparece** na URL
- O subdomínio já é informação pública (é a URL que o cliente acessa)
- Compatível com HTTPS/SSL wildcard (`*.useakti.com`)
- Resolução via `TenantManager` — mesma lógica já usada no PHP

### 12.3 Montagem Automática da Webhook URL

#### No `CheckoutService::processCheckout()`

Antes de chamar `$gateway->createCharge($chargeData)`, o service **monta e injeta** a URL de webhook:

```php
// CheckoutService.php → processCheckout()

// 1. Resolver subdomínio do tenant
$tenantSubdomain = $this->getTenantSubdomain($token['tenant_id']);

// 2. Montar webhook URL baseada no subdomínio
$webhookUrl = "https://{$tenantSubdomain}.useakti.com/api/webhooks/{$gatewaySlug}";

// 3. Injetar no chargeData
$chargeData['notification_url'] = $webhookUrl;
$chargeData['webhook_url']      = $webhookUrl;
```

#### Método Helper: `getTenantSubdomain()`

```php
private function getTenantSubdomain(int $tenantId): string
{
    // Busca o subdomínio na tabela master
    $stmt = $this->masterDb->prepare(
        "SELECT subdomain FROM tenant_clients WHERE id = ? AND is_active = 1"
    );
    $stmt->execute([$tenantId]);
    $row = $stmt->fetch(\PDO::FETCH_ASSOC);

    if (!$row || empty($row['subdomain'])) {
        throw new \RuntimeException("Tenant #{$tenantId} não possui subdomínio configurado.");
    }

    return $row['subdomain'];
}
```

### 12.4 Adaptação por Gateway — Envio da Webhook URL no Payload

Cada gateway recebe a URL de webhook de forma diferente. O campo `notification_url` no `$data` do `createCharge()` deve ser mapeado para o campo correto de cada API:

#### Stripe

```php
// StripeGateway.php → dentro de createPaymentIntent() / createCheckoutSession()

// Para PaymentIntent (checkout transparente):
// Stripe NÃO aceita notification_url diretamente no PaymentIntent.
// A abordagem é usar Webhook Endpoints programáticos via API:
//
// POST https://api.stripe.com/v1/webhook_endpoints
// {
//   "url": "https://empresa-x.useakti.com/api/webhooks/stripe",
//   "enabled_events": ["payment_intent.succeeded", "payment_intent.payment_failed",
//                       "checkout.session.completed", "charge.refunded"],
//   "metadata": {"source": "akti_auto"}
// }
//
// Estratégia: registrar o webhook endpoint UMA VEZ automaticamente
// (na primeira cobrança ou ao configurar gateway) e armazenar o webhook_secret retornado.

// Para Checkout Session (link externo — quando não checkout transparente):
$payload['success_url']  = $data['success_url'] ?? $data['notification_url'] . '/success';
// O webhook ainda é via Webhook Endpoint registrado
```

**Auto-Registro de Webhook no Stripe:**

```php
/**
 * Registrar webhook endpoint no Stripe automaticamente.
 * Chamado na primeira cobrança ou ao salvar credenciais do gateway.
 * Retorna o webhook signing secret para armazenar em payment_gateways.webhook_secret.
 */
public function registerWebhookEndpoint(string $webhookUrl): array
{
    $response = $this->httpRequest('POST', $this->getApiUrl() . '/webhook_endpoints', [
        'Authorization' => 'Bearer ' . $this->getCredential('secret_key'),
        'Content-Type'  => 'application/x-www-form-urlencoded',
    ], http_build_query([
        'url'            => $webhookUrl,
        'enabled_events' => [
            'payment_intent.succeeded',
            'payment_intent.payment_failed',
            'checkout.session.completed',
            'charge.refunded',
        ],
        'metadata' => ['source' => 'akti_auto', 'registered_at' => date('Y-m-d H:i:s')],
    ]));

    if ($response['status'] !== 200 || !isset($response['decoded']['id'])) {
        throw new \RuntimeException('Falha ao registrar webhook no Stripe: ' . ($response['error'] ?? 'unknown'));
    }

    return [
        'webhook_endpoint_id' => $response['decoded']['id'],
        'webhook_secret'      => $response['decoded']['secret'], // whsec_xxx
    ];
}
```

#### Mercado Pago

```php
// MercadoPagoGateway.php → dentro de createPreferenceLink() e createDirectPayment()

// MP aceita notification_url diretamente no payload:
if (!empty($data['notification_url'])) {
    $payload['notification_url'] = $data['notification_url'];
    // URL DEVE ser HTTPS — MP rejeita HTTP
}

// Para pagamento direto (POST /v1/payments):
$payload['notification_url'] = $data['notification_url'];

// Para preferência (POST /checkout/preferences):
$payload['notification_url'] = $data['notification_url'];
// Também pode usar back_urls.success para redirecionamento pós-pagamento
```

#### PagSeguro

```php
// PagSeguroGateway.php → dentro de createCharge()

// PagSeguro aceita notification_urls como ARRAY:
if (!empty($data['notification_url'])) {
    $payload['notification_urls'] = [$data['notification_url']];
}
```

### 12.5 Resolução de Tenant no Webhook (Node.js) — Por Subdomínio

#### Mudança no `webhookRoutes.js`

O resolver de tenant no webhook deve passar a extrair o subdomínio do `Host` header em vez do query param `tenant`:

```javascript
// ANTES (expõe DB na URL):
function webhookTenantResolver(req, _res, next) {
    const tenantDb = req.query.tenant;
    if (!tenantDb) {
        return next(Object.assign(new Error('Query param "tenant" is required.'), { status: 400 }));
    }
    req.user = { tenant_db: tenantDb, webhook: true };
    return next();
}

// DEPOIS (resolve por subdomínio):
function webhookTenantResolver(req, _res, next) {
    const host = req.hostname || req.headers.host?.split(':')[0] || '';

    // 1. Extrair subdomínio: "empresa-x.useakti.com" → "empresa-x"
    const subdomain = extractSubdomain(host);
    if (!subdomain) {
        return next(Object.assign(new Error('Subdomínio não identificado.'), { status: 400 }));
    }

    // 2. Buscar tenant (db_name) pelo subdomínio na master DB
    resolveSubdomainToDb(subdomain)
        .then(tenantDb => {
            if (!tenantDb) {
                return next(Object.assign(new Error('Tenant não encontrado.'), { status: 404 }));
            }
            req.user = { tenant_db: tenantDb, webhook: true, subdomain };
            return next();
        })
        .catch(err => next(err));
}

/**
 * Extrai o subdomínio do host.
 * "empresa-x.useakti.com" → "empresa-x"
 * "empresa-x.teste.akti.com" → "empresa-x"
 * "useakti.com" → null (domínio raiz)
 */
function extractSubdomain(host) {
    const parts = host.replace(/:\d+$/, '').split('.');
    // Mínimo 3 partes para ter subdomínio: sub.domain.tld
    if (parts.length < 3) return null;
    return parts[0];
}

/**
 * Consulta akti_master.tenant_clients pelo subdomínio.
 * Retorna o db_name ou null.
 */
async function resolveSubdomainToDb(subdomain) {
    const masterPool = require('../config/database').getMasterPool();
    const [rows] = await masterPool.query(
        'SELECT db_name FROM tenant_clients WHERE subdomain = ? AND is_active = 1',
        [subdomain]
    );
    return rows.length > 0 ? rows[0].db_name : null;
}
```

#### Retrocompatibilidade

Para evitar quebra durante a migração, manter suporte temporário ao query param `tenant`:

```javascript
function webhookTenantResolver(req, _res, next) {
    const host = req.hostname || req.headers.host?.split(':')[0] || '';
    const subdomain = extractSubdomain(host);

    // Prioridade 1: Resolver por subdomínio
    if (subdomain) {
        return resolveSubdomainToDb(subdomain)
            .then(tenantDb => {
                if (!tenantDb) {
                    return next(Object.assign(new Error('Tenant não encontrado.'), { status: 404 }));
                }
                req.user = { tenant_db: tenantDb, webhook: true, subdomain };
                return next();
            })
            .catch(err => next(err));
    }

    // Fallback: query param ?tenant=db_name (DEPRECADO — para webhook URLs já configurados)
    const tenantDb = req.query.tenant;
    if (tenantDb) {
        console.warn(`[DEPRECADO] Webhook usando ?tenant= param. Host: ${host}`);
        req.user = { tenant_db: tenantDb, webhook: true };
        return next();
    }

    return next(Object.assign(new Error('Subdomínio ou tenant não identificado.'), { status: 400 }));
}
```

### 12.6 Fluxo Completo — Diagrama

```
┌─────────────────── CONFIGURAÇÃO (automática) ────────────────────┐
│                                                                    │
│  1. Admin salva credenciais do gateway (PaymentGatewayController)  │
│     OU                                                             │
│  2. Primeiro checkout transparente é processado                    │
│                                                                    │
│  → CheckoutService monta:                                          │
│    webhook_url = "https://{subdomain}.useakti.com/api/webhooks/    │
│                   {gateway_slug}"                                  │
│                                                                    │
│  → Para Stripe: auto-registra Webhook Endpoint via API             │
│    e salva webhook_secret retornado no DB                          │
│                                                                    │
│  → Para MP/PagSeguro: envia notification_url no payload            │
│    de cada createCharge() — sem registro prévio necessário         │
│                                                                    │
└────────────────────────────────────────────────────────────────────┘

┌─────────────────── COBRANÇA (a cada pagamento) ──────────────────┐
│                                                                    │
│  CheckoutService::processCheckout()                                │
│    │                                                               │
│    ├─ 1. Resolve subdomínio do tenant                              │
│    ├─ 2. Monta webhook URL: https://{sub}.useakti.com/api/...      │
│    ├─ 3. Injeta em $chargeData['notification_url']                 │
│    └─ 4. Chama $gateway->createCharge($chargeData)                 │
│           │                                                        │
│           ├─ MP: payload['notification_url'] = webhook URL          │
│           ├─ PagSeguro: payload['notification_urls'] = [URL]        │
│           └─ Stripe: não envia no payload (usa endpoint registrado)│
│                                                                    │
└────────────────────────────────────────────────────────────────────┘

┌─────────────────── WEBHOOK (callback do gateway) ────────────────┐
│                                                                    │
│  Gateway envia POST para:                                          │
│    https://empresa-x.useakti.com/api/webhooks/stripe               │
│                                                                    │
│  Node.js (webhookRoutes.js):                                       │
│    │                                                               │
│    ├─ 1. Extrai subdomínio: "empresa-x"                            │
│    ├─ 2. Consulta akti_master: subdomain → db_name                 │
│    ├─ 3. Conecta ao DB do tenant (TenantPool)                      │
│    ├─ 4. Valida assinatura HMAC                                    │
│    ├─ 5. Processa pagamento (WebhookService)                       │
│    └─ 6. Retorna HTTP 200 ao gateway                               │
│                                                                    │
└────────────────────────────────────────────────────────────────────┘
```

### 12.7 Benefícios

| Aspecto | Antes | Depois |
|---------|-------|--------|
| **Configuração** | Manual (copiar/colar URL no painel de cada gateway) | Automática (backend injeta URL a cada cobrança) |
| **Exposição de dados** | Nome do banco na URL (`?tenant=akti_empresa_x`) | Subdomínio público (já visível como URL de acesso) |
| **Manutenção** | Se URL da API muda, atualizar todos os gateways manualmente | Backend gera URL correta dinamicamente a cada cobrança |
| **Novos tenants** | Configurar webhook para cada novo tenant | Funciona automaticamente (subdomínio já existe) |
| **Stripe** | Configurar webhook no dashboard manualmente | Auto-registro via API (`/v1/webhook_endpoints`) |
| **MP/PagSeguro** | Configurar notification_url no painel | Enviado no payload de cada cobrança (sem config prévia) |

### 12.8 Considerações de Implementação

#### Pré-Requisitos

1. **Coluna `subdomain`** na tabela `tenant_clients` (akti_master) — Já existe e é usada pelo TenantManager
2. **SSL Wildcard** (`*.useakti.com`) — Necessário para que o webhook URL use HTTPS (MP exige HTTPS)
3. **DNS Wildcard** (`*.useakti.com → IP do servidor`) — Necessário para que subdomínios dinâmicos resolvam

#### Armazenamento do Webhook Endpoint (Stripe)

O Stripe retorna um `webhook_secret` (signing secret) ao registrar o endpoint. Este valor deve ser salvo:

```sql
-- Aproveitando a tabela payment_gateways existente
-- O campo webhook_secret já existe e guarda o signing secret
-- Adicionar campo para controle de registro automático:
ALTER TABLE payment_gateways
    ADD COLUMN webhook_endpoint_id VARCHAR(255) NULL COMMENT 'ID do webhook endpoint registrado automaticamente (Stripe)',
    ADD COLUMN webhook_auto_registered TINYINT(1) DEFAULT 0 COMMENT 'Se webhook foi registrado automaticamente';
```

#### Idempotência de Registro (Stripe)

Antes de registrar um novo webhook endpoint, verificar se já existe:

```php
// 1. Verificar se já tem webhook_endpoint_id no DB
$gateway = $this->gatewayModel->findBySlug($gatewaySlug);
if (!empty($gateway['webhook_endpoint_id'])) {
    // Já registrado — verificar se URL mudou
    $existingEndpoint = $this->getWebhookEndpoint($gateway['webhook_endpoint_id']);
    if ($existingEndpoint && $existingEndpoint['url'] === $webhookUrl) {
        return; // Tudo OK, nada a fazer
    }
    // URL mudou — atualizar endpoint existente
    $this->updateWebhookEndpoint($gateway['webhook_endpoint_id'], $webhookUrl);
    return;
}

// 2. Registrar novo endpoint
$result = $this->registerWebhookEndpoint($webhookUrl);
$this->gatewayModel->update($gateway['id'], [
    'webhook_endpoint_id'    => $result['webhook_endpoint_id'],
    'webhook_secret'         => $result['webhook_secret'],
    'webhook_auto_registered' => 1,
]);
```

#### Validação da Webhook URL

```php
private function buildWebhookUrl(string $subdomain, string $gatewaySlug): string
{
    $url = "https://{$subdomain}.useakti.com/api/webhooks/{$gatewaySlug}";

    // Validações de segurança
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        throw new \RuntimeException('Webhook URL inválida gerada.');
    }
    if (!preg_match('#^https://#i', $url)) {
        throw new \RuntimeException('Webhook URL deve ser HTTPS.');
    }
    if (preg_match('#[/\\\\]\.\.#', $url)) {
        throw new \RuntimeException('Webhook URL com path traversal detectado.');
    }

    return $url;
}
```

---

## 13. Webhooks e Confirmação

### 12.1 Fluxo de Confirmação

```
                          ┌──────────────────────┐
                          │  Cliente paga no      │
                          │  checkout transparente│
                          └──────────┬───────────┘
                                     │
                    ┌────────────────┼────────────────┐
                    │                │                 │
              [PIX/Boleto]     [Cartão OK]      [Cartão 3DS]
                    │                │                 │
            Webhook async    Resposta sync    Stripe confirma
                    │                │                 │
                    ▼                ▼                 ▼
           WebhookService    CheckoutService    Webhook async
                    │                │                 │
                    ▼                ▼                 ▼
         ┌─────────────────────────────────────────────────┐
         │  1. Atualizar order_installments.status = 'pago' │
         │  2. Atualizar checkout_tokens.status = 'used'    │
         │  3. Recalcular orders.payment_status             │
         │  4. Logar em payment_gateway_transactions        │
         │  5. Disparar evento: checkout.payment.confirmed  │
         └─────────────────────────────────────────────────┘
```

### 12.2 Adaptação do WebhookService (Node.js)

No `api/src/services/WebhookService.js`, ao processar webhook de pagamento:

1. Verificar se existe `checkout_token` associado ao `order_id` no metadata
2. Se existir, marcar token como `used` e gravar `used_at`
3. Manter lógica existente de atualização de parcela/pedido

### 12.3 Polling (Fallback)

Para PIX, o frontend faz polling via `checkStatus()`:

```
Intervalo: 5 segundos
Timeout: Se PIX → até expires_at | Geral: max 30 minutos
Ao detectar 'approved': redirecionar para confirmation page
```

---

## 14. Fases de Implementação

### Fase 1 — Fundação (Banco + Models + Service)

**Estimativa de complexidade:** Média

**Tarefas:**

1. **Migration SQL** — Criar tabela `checkout_tokens` e alterar `orders`
   - Usar skill `sql-migration`
   - Inclui: tabela, índices, FK, coluna em orders

2. **Model `CheckoutToken`** — CRUD completo
   - `app/models/CheckoutToken.php`
   - Métodos: create, findByToken, findByOrder, markUsed, markExpired, cancel, expireAll, updateIp, getActiveByOrder

3. **Service `CheckoutService`** — Lógica de negócio
   - `app/services/CheckoutService.php`
   - Métodos: generateToken, processCheckout, cancelToken, expireOldTokens, getTokenByToken

**Critério de conclusão:** Token pode ser gerado e persistido via Service, Model funcional com testes unitários.

---

### Fase 2 — Controller + Rotas

**Estimativa de complexidade:** Média

**Tarefas:**

1. **Controller `CheckoutController`**
   - `app/controllers/CheckoutController.php`
   - Actions: show, processPayment, checkStatus, confirmation
   - Validação de token, resolução de tenant, headers de segurança

2. **Rota em `routes.php`**
   - Rota pública `checkout` com actions

3. **Adaptação do `PipelinePaymentService`**
   - Novo método `generateCheckoutLink()`
   - Chamar `CheckoutService::generateToken()`
   - Persistir checkout URL no pedido

4. **Adaptação do `PaymentGatewayController`**
   - Nova action `createCheckoutLink` (AJAX)
   - Recebe: installment_id, allowed_methods, expires_in_hours
   - Retorna: checkout_url, token, expires_at

**Critério de conclusão:** URL `/?page=checkout&token=xxx` funciona, mostra dados do pedido (sem UI final).

---

### Fase 3 — Frontend da Página de Checkout

**Estimativa de complexidade:** Alta

**Tarefas:**

1. **View `checkout/pay.php`** — Página principal
   - Layout responsivo standalone (sem header/footer admin)
   - Logo e dados do tenant
   - Resumo do pedido/parcela
   - Tabs de método de pagamento
   - Include de partials

2. **Partial `_pix.php`** — Seção PIX
   - Botão "Gerar PIX"
   - Exibição de QR Code (imagem base64)
   - Código copia-e-cola
   - Countdown timer

3. **Partial `_credit_card.php`** — Seção Cartão
   - Container para SDK JS do gateway
   - Campos de CPF/CNPJ
   - Botão pagar

4. **Partial `_boleto.php`** — Seção Boleto
   - Botão "Gerar Boleto"
   - Código de barras
   - Link para PDF

5. **View `checkout/confirmation.php`** — Página de confirmação de pagamento
   - 3 estados: confirmado, aguardando, erro
   - Partial `_confirmation_success.php`: ícone de sucesso, resumo do pagamento, data/hora, método
   - Partial `_confirmation_pending.php`: spinner, polling automático, countdown, botão verificar manualmente
   - Partial `_confirmation_error.php`: ícone de erro, mensagem, botão tentar novamente
   - Header e footer reutilizados do checkout
   - Responsivo e com identidade visual do tenant

6. **View `checkout/expired.php`** — Token expirado/inválido
   - Mensagem explicativa
   - Orientação para contatar o vendedor

7. **CSS `checkout.css`**
   - Estilos standalone
   - Variáveis de customização
   - Responsividade

8. **JS `checkout.js`**
   - Carregamento dinâmico de SDK
   - Tab switching
   - AJAX para processamento
   - Polling de status (PIX)
   - Máscaras e validação
   - Clipboard API
   - Error handling

**Critério de conclusão:** Checkout transparente totalmente funcional. Cliente consegue pagar via PIX, Cartão ou Boleto sem sair do domínio.

---

### Fase 4 — Integração Admin + Portal

**Estimativa de complexidade:** Baixa-Média

**Tarefas:**

1. **UI Admin — Botão "Gerar Checkout"**
   - Em `app/views/financial/installments.php`
   - Em `assets/js/financial-payments.js`
   - Modal com opções: métodos permitidos, expiração
   - Resultado: link copiável + QR Code da URL

2. **UI Admin — Pipeline Detail**
   - Adaptar para oferecer checkout transparente como opção
   - Ao lado do botão "Gerar Link de Pagamento" existente

3. **UI Portal — Botão Pagar**
   - Em `app/views/portal/orders/detail.php`
   - Substituir link externo por link de checkout transparente
   - Verificar token ativo antes de exibir

4. **Listagem de Tokens**
   - Opcional: seção no admin para ver tokens gerados
   - Status, expiração, uso

**Critério de conclusão:** Operador pode gerar checkout transparente de qualquer parcela. Cliente acessa pelo portal ou link direto.

---

### Fase 5 — Webhooks, Polling e Finalização

**Estimativa de complexidade:** Média-Alta

**Tarefas:**

1. **Webhook Automático — Envio Dinâmico (PHP)**
   - Implementar `CheckoutService::getTenantSubdomain()` e `buildWebhookUrl()`
   - Injetar `notification_url` no `$chargeData` antes de `createCharge()`
   - Cada gateway mapeia `notification_url` para o campo correto da API:
     - MP: `$payload['notification_url']`
     - PagSeguro: `$payload['notification_urls']`
     - Stripe: auto-registro via `/v1/webhook_endpoints` (somente na primeira vez)

2. **Auto-Registro de Webhook (Stripe)**
   - Implementar `StripeGateway::registerWebhookEndpoint($webhookUrl)`
   - Armazenar `webhook_endpoint_id` e `webhook_secret` retornados
   - Verificar idempotência (não duplicar endpoints)
   - Trigger: ao salvar credenciais ou na primeira cobrança transparente

3. **Resolução por Subdomínio (Node.js)**
   - Alterar `webhookTenantResolver` para extrair subdomínio do `Host` header
   - Implementar `resolveSubdomainToDb()` via consulta em `akti_master.tenant_clients`
   - Manter fallback temporário para `?tenant=` (retrocompatibilidade)
   - Log de deprecação para uso do fallback

4. **Adaptação do WebhookService (Node.js)**
   - Ao confirmar pagamento, marcar checkout_token como 'used'
   - Adicionar campo token_id no metadata enviado ao gateway

5. **Polling no Frontend**
   - Implementar checkStatus com intervalo de 5s
   - Auto-redirect para confirmation ao detectar pagamento

6. **Cron de Expiração**
   - Script/endpoint para expirar tokens vencidos
   - `CheckoutService::expireOldTokens()`

7. **Testes**
   - Unitários: CheckoutToken model, CheckoutService
   - Unitários: `buildWebhookUrl()`, `getTenantSubdomain()`
   - Integração: Fluxo completo (gerar token → abrir checkout → processar)
   - Integração: Webhook recebido via subdomínio resolve corretamente
   - Segurança: Token inválido, expirado, usado, brute-force
   - Segurança: Webhook URL gerada não contém path traversal

8. **Documentação**
   - Atualizar `modulo-payment-gateways.md`
   - Documentar fluxo de checkout transparente
   - Documentar migração de `?tenant=` para subdomínio

**Critério de conclusão:** Sistema completo e testado. Webhooks configurados automaticamente, resolvem tenant por subdomínio, confirmam pagamento e atualizam token/parcela/pedido sem intervenção manual.

---

## 15. Checklist Final

### Funcional

- [ ] Token de checkout gerado com segurança (256 bits)
- [ ] Página pública acessível sem login
- [ ] Dados do pedido exibidos corretamente
- [ ] PIX: QR Code gerado e exibido, código copia-e-cola funcional
- [ ] PIX: Polling detecta pagamento e redireciona
- [ ] Cartão: SDK do gateway carregado dinamicamente
- [ ] Cartão: Tokenização funciona (dados não trafegam pelo servidor)
- [ ] Cartão: 3D Secure tratado (Stripe)
- [ ] Boleto: Gerado com código de barras e link para PDF
- [ ] Confirmação: Página de pagamento confirmado exibida corretamente
- [ ] Confirmação: Página de aguardando com polling automático funcional
- [ ] Confirmação: Página de erro com mensagem amigável e botão retry
- [ ] Confirmação: Transição dinâmica de pendente → confirmado (sem reload)
- [ ] Confirmação: Timeout de 30 min no polling com mensagem orientativa
- [ ] Expirado: Página de token expirado exibida
- [ ] Token marcado como 'used' após pagamento confirmado
- [ ] Webhook atualiza parcela e pedido automaticamente
- [ ] Webhook URL enviada automaticamente ao gateway a cada cobrança (zero config)
- [ ] Stripe: webhook endpoint auto-registrado via API
- [ ] MP/PagSeguro: notification_url injetada no payload de criação
- [ ] Webhook resolve tenant por subdomínio (sem `?tenant=` na URL)
- [ ] Fallback de `?tenant=` funciona para webhooks legados
- [ ] Admin pode gerar link de checkout de qualquer parcela
- [ ] Portal exibe botão de checkout ativo

### Segurança

- [ ] Token com 256 bits de entropia
- [ ] Valor do pagamento vem do banco (não do frontend)
- [ ] Rate limiting implementado (5 tentativas / 10 min)
- [ ] Headers de segurança aplicados (X-Frame-Options, CSP, etc.)
- [ ] HTTPS verificado no controller
- [ ] IP do visitante registrado
- [ ] Dados de cartão nunca trafegam pelo servidor PHP
- [ ] Prepared statements em todas as queries
- [ ] XSS protegido com `e()` em todas as views
- [ ] Idempotency key enviada aos gateways
- [ ] Token único por tentativa (sem reuso de token usado/expirado)
- [ ] Webhook URL gerada não expõe nome do banco de dados
- [ ] Webhook URL validada (HTTPS, sem path traversal)
- [ ] Stripe webhook_secret armazenado com segurança após auto-registro

### Técnico

- [ ] PSR-4 autoload (sem require_once)
- [ ] Migration SQL gerada via skill `sql-migration`
- [ ] Compatível com multi-tenant (tenant_id em tudo)
- [ ] Responsivo (mobile-first)
- [ ] Bootstrap 5 para layout
- [ ] SweetAlert2 para feedbacks (sem alert/confirm nativo)
- [ ] Testes unitários para Model e Service
- [ ] Testes de segurança para token
- [ ] Testes de `buildWebhookUrl()` e `getTenantSubdomain()`
- [ ] Teste de resolução de subdomínio no webhook (Node.js)

---

*Documento gerado em 2026-04-08 — Checkout Transparente v1*
