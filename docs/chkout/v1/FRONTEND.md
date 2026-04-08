# Checkout Transparente — Especificação de Frontend

> Detalhamento da interface, componentes visuais, JavaScript e integrações com SDKs dos gateways.

---

## 1. Estrutura de Arquivos Frontend

```
app/views/checkout/
├── pay.php                    # Página principal do checkout
├── confirmation.php          # Página de confirmação (3 estados)
├── expired.php                # Token expirado/inválido/cancelado
└── partials/
    ├── _header.php            # Cabeçalho standalone (logo, nome empresa)
    ├── _footer.php            # Rodapé (powered by, segurança)
    ├── _order_summary.php     # Card de resumo do pedido
    ├── _pix.php               # Tab PIX (QR Code, copia-e-cola, timer)
    ├── _credit_card.php       # Tab Cartão (container SDK, CPF, botão pagar)
    ├── _boleto.php            # Tab Boleto (gerar, código barras, PDF)
    ├── _confirmation_success.php    # Estado: pagamento confirmado
    ├── _confirmation_pending.php    # Estado: aguardando confirmação
    └── _confirmation_error.php      # Estado: erro no pagamento

assets/css/
└── checkout.css               # Estilos standalone do checkout

assets/js/
└── checkout.js                # Lógica principal do checkout
```

---

## 2. Página Principal (`pay.php`)

### 2.1 Dados Recebidos do Controller

```php
// Variáveis disponíveis na view
$token          // (array) Registro do checkout_token
$order          // (array) Dados do pedido
$installment    // (array|null) Dados da parcela
$gateway        // (array) Dados do gateway ativo
$methods        // (array) Métodos permitidos: ['pix', 'credit_card', 'boleto']
$company        // (array) Dados da empresa (name, logo_path, primary_color)
$publicKey      // (string) Chave pública do gateway (para SDK JS)
$gatewaySlug    // (string) Slug do gateway
```

### 2.2 Estrutura HTML

```html
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Pagamento — <?= e($company['name']) ?></title>
    
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 CDN -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5/css/all.min.css" rel="stylesheet">
    <!-- Checkout CSS -->
    <link href="/assets/css/checkout.css" rel="stylesheet">
    
    <!-- Variáveis de tema -->
    <style>
        :root {
            --checkout-primary: <?= e($company['primary_color'] ?? '#0d6efd') ?>;
        }
    </style>
</head>
<body class="checkout-body">
    
    <?php include __DIR__ . '/partials/_header.php'; ?>
    
    <main class="checkout-main container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8 col-12">
                
                <?php include __DIR__ . '/partials/_order_summary.php'; ?>
                
                <!-- Tabs de Método de Pagamento -->
                <div class="card checkout-payment-card">
                    <div class="card-header">
                        <h5 class="mb-0">Forma de pagamento</h5>
                    </div>
                    <div class="card-body">
                        <ul class="nav nav-pills checkout-method-tabs" role="tablist">
                            <!-- Tabs dinâmicas baseadas nos métodos permitidos -->
                        </ul>
                        
                        <div class="tab-content mt-3">
                            <!-- Conteúdo das tabs -->
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </main>
    
    <?php include __DIR__ . '/partials/_footer.php'; ?>
    
    <!-- Config para JS -->
    <script>
        const CHECKOUT_CONFIG = {
            token: '<?= e($token['token']) ?>',
            gatewaySlug: '<?= e($gatewaySlug) ?>',
            publicKey: '<?= e($publicKey) ?>',
            amount: <?= json_encode((float)$token['amount']) ?>,
            currency: '<?= e($token['currency']) ?>',
            methods: <?= json_encode($methods) ?>,
            processUrl: '/?page=checkout&action=processPayment',
            statusUrl: '/?page=checkout&action=checkStatus',
            confirmationUrl: '/?page=checkout&action=confirmation&token=<?= e($token['token']) ?>'
        };
    </script>
    
    <!-- Bootstrap JS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Checkout JS -->
    <script src="/assets/js/checkout.js"></script>
</body>
</html>
```

---

## 3. Partials

### 3.1 `_header.php`

```
┌────────────────────────────────────────────────────┐
│  [Logo]  Nome da Empresa                            │
│  ─────────────────────────────────────────────────  │
│  🔒 Pagamento seguro                               │
└────────────────────────────────────────────────────┘
```

- Logo carregada de `assets/uploads/{tenant}/logo.png` ou `assets/logos/`
- Fallback para nome texto se não houver logo
- Ícone de cadeado para transmitir segurança

### 3.2 `_order_summary.php`

```
┌────────────────────────────────────────────────────┐
│  RESUMO DO PEDIDO                                   │
│                                                     │
│  📋 Pedido #0042                                    │
│  Impressão de 500 folhetos A4 formato fechado       │
│                                                     │
│  Parcela 1 de 3 • Vencimento: 15/04/2026           │
│  ─────────────────────────────────────────────────  │
│  Total a pagar:        R$ 283,33                    │
└────────────────────────────────────────────────────┘
```

- Exibir `order.description` ou fallback para "Pedido #XXXX"
- Se `installment` presente, exibir dados da parcela
- Formatar valor com `number_format($amount, 2, ',', '.')`

### 3.3 `_pix.php`

```
┌────────────────────────────────────────────────────┐
│                                                     │
│  [Gerar código PIX]     ← botão inicial            │
│                                                     │
│  ── após gerar ──                                  │
│                                                     │
│  ┌──────────────┐                                  │
│  │  [QR Code]   │   Escaneie com seu app           │
│  │  200x200     │   bancário                       │
│  └──────────────┘                                  │
│                                                     │
│  PIX Copia e Cola:                                  │
│  ┌──────────────────────────────────────┐          │
│  │ 00020126580014BR.GOV.BCB.PIX...     │ [Copiar] │
│  └──────────────────────────────────────┘          │
│                                                     │
│  ⏱ Expira em: 29:42                               │
│                                                     │
│  ✅ Aguardando confirmação do pagamento...          │
│  [spinner] Verificando a cada 5 segundos            │
└────────────────────────────────────────────────────┘
```

**Comportamento:**
1. Botão "Gerar código PIX" → AJAX processPayment(method: 'pix')
2. Resposta: exibir QR Code (base64 → `<img>`)
3. Exibir código copia-e-cola em textarea readonly
4. Botão "Copiar" usa `navigator.clipboard.writeText()`
5. Countdown timer (decrementa a cada segundo)
6. Polling a cada 5s via checkStatus()
7. Ao detectar pagamento → SweetAlert2 sucesso → redirect

### 3.4 `_credit_card.php`

```
┌────────────────────────────────────────────────────┐
│                                                     │
│  Número do cartão:                                  │
│  ┌──────────────────────────────────────────────┐  │
│  │  [Container SDK do gateway - iframe seguro]  │  │
│  └──────────────────────────────────────────────┘  │
│                                                     │
│  Nome no cartão:                                    │
│  [____________________________________]             │
│                                                     │
│  Validade:        CVV:                              │
│  [____ / ____]    [_______]                         │
│                                                     │
│  CPF/CNPJ do titular:                               │
│  [_________________]                                │
│                                                     │
│  [💳 Pagar R$ 283,33]                               │
│                                                     │
└────────────────────────────────────────────────────┘
```

**Comportamento por Gateway:**

#### Stripe
- Carregar Stripe.js (`https://js.stripe.com/v3/`)
- Criar `stripe.elements()` com `card` element
- Montar no container `#card-element`
- No submit: `stripe.createPaymentMethod({type: 'card', card: element})`
- Enviar `paymentMethod.id` como `card_token`
- Se `requires_action`: `stripe.confirmCardPayment(clientSecret)`

#### Mercado Pago
- Carregar SDK v2 (`https://sdk.mercadopago.com/js/v2`)
- Criar `mp.cardForm({...})` com auto-mount nos campos
- No submit: `cardForm.getCardFormData()` → retorna `token`
- Enviar `token` como `card_token`

#### PagSeguro
- Carregar SDK (`https://assets.pagseguro.com.br/checkout-sdk-js/...`)
- No submit: `PagSeguro.encryptCard({...})` → retorna `encryptedCard`
- Enviar `encryptedCard` como `card_token`

### 3.5 `_boleto.php`

```
┌────────────────────────────────────────────────────┐
│                                                     │
│  CPF/CNPJ (necessário para boleto):                │
│  [_________________]                                │
│                                                     │
│  [Gerar Boleto]                                     │
│                                                     │
│  ── após gerar ──                                  │
│                                                     │
│  ✅ Boleto gerado com sucesso!                      │
│                                                     │
│  Código de barras:                                  │
│  ┌──────────────────────────────────────┐          │
│  │ 12345.67890 12345.678901 1 1234...  │ [Copiar] │
│  └──────────────────────────────────────┘          │
│                                                     │
│  [📄 Abrir PDF do Boleto]                           │
│                                                     │
│  📅 Vencimento: 11/04/2026                          │
│  ⚠ O pagamento pode levar até 3 dias úteis         │
│    para compensação.                                │
│                                                     │
└────────────────────────────────────────────────────┘
```

**Comportamento:**
1. Solicitar CPF/CNPJ se não pré-preenchido no token
2. Botão "Gerar Boleto" → AJAX processPayment(method: 'boleto')
3. Exibir código de barras (linha digitável)
4. Botão "Copiar" para clipboard
5. Botão "Abrir PDF" → `window.open(boleto_url, '_blank')`
6. Mensagem de prazo de compensação

---

## 4. JavaScript (`checkout.js`)

### 4.1 Estrutura do Módulo

```javascript
/**
 * Checkout Transparente Akti
 * 
 * Módulo JS responsável pela interação do checkout público.
 * Carrega dinamicamente o SDK do gateway e processa pagamentos.
 */
const AktiCheckout = (function() {
    'use strict';

    // Estado interno
    let gatewayInstance = null;
    let pollingInterval = null;
    let countdownInterval = null;

    // Públicos
    return {
        init,               // Inicializar checkout
        processPixPayment,  // Gerar e exibir PIX
        processCardPayment, // Processar cartão
        processBoletoPayment, // Gerar boleto
        checkPaymentStatus, // Polling de status
        copyToClipboard,    // Copiar texto
        destroy             // Cleanup (parar polling, etc.)
    };

    // ... implementação
})();

// Auto-init quando DOM ready
document.addEventListener('DOMContentLoaded', () => {
    AktiCheckout.init(CHECKOUT_CONFIG);
});
```

### 4.2 Funções Principais

#### `init(config)`

```
1. Salvar config global
2. Detectar gateway slug
3. Carregar SDK JS correspondente (lazy)
4. Inicializar instância do gateway
5. Se cartão está nos métodos → montar form de cartão
6. Bind event listeners (tabs, botões, forms)
7. Selecionar primeira tab disponível
```

#### `loadGatewaySDK(slug)`

```
1. Verificar se já carregado (window.Stripe, window.MercadoPago, etc.)
2. Se não: criar <script> tag com URL do SDK
3. Retornar Promise que resolve quando script carrega
4. Rejeitar com erro se falhar
```

#### `processPixPayment()`

```
1. Mostrar loading (SweetAlert2)
2. POST para processUrl com { token, method: 'pix' }
3. Se sucesso:
   - Esconder loading
   - Exibir QR code (img src=base64)
   - Exibir código copia-e-cola
   - Iniciar countdown timer (expires_in_seconds)
   - Iniciar polling (checkPaymentStatus a cada 5s)
4. Se erro:
   - Exibir mensagem de erro (SweetAlert2)
```

#### `processCardPayment()`

```
1. Validar CPF (se campo preenchido)
2. [Gateway-específico] Tokenizar dados de cartão
   - Stripe: stripe.createPaymentMethod(...)
   - MercadoPago: cardForm.getCardFormData()
   - PagSeguro: PagSeguro.encryptCard(...)
3. Mostrar loading (SweetAlert2)
4. POST para processUrl com { token, method: 'credit_card', card_token }
5. Se status === 'succeeded':
   - SweetAlert2 sucesso → redirect para confirmationUrl
6. Se status === 'requires_action' (Stripe 3DS):
   - stripe.confirmCardPayment(client_secret)
   - Se confirmado → redirect
7. Se erro:
   - Exibir mensagem → permitir retry
```

#### `processBoletoPayment()`

```
1. Capturar CPF/CNPJ do input
2. Validar formato
3. Mostrar loading
4. POST para processUrl com { token, method: 'boleto', customer_document }
5. Se sucesso:
   - Exibir código de barras
   - Exibir botão PDF (se boleto_url)
   - Exibir data de vencimento
6. Se erro:
   - Exibir mensagem
```

#### `checkPaymentStatus(externalId)`

```
1. GET statusUrl com { token, external_id }
2. Se paid === true:
   - Parar polling
   - SweetAlert2 sucesso com animação
   - Redirect para confirmationUrl em 3s
3. Se pending:
   - Continuar polling
```

#### `startCountdown(expiresInSeconds)`

```
1. Atualizar display a cada 1s: MM:SS
2. Quando chegar a 0:
   - Parar countdown
   - Parar polling
   - Exibir mensagem "PIX expirado"
   - Botão "Gerar novo código"
```

### 4.3 Utilitários

| Função | Descrição |
|--------|-----------|
| `copyToClipboard(text)` | Clipboard API com fallback para execCommand |
| `formatCurrency(value)` | Formata para R$ X.XXX,XX |
| `maskCpfCnpj(input)` | Máscara automática CPF (xxx.xxx.xxx-xx) ou CNPJ |
| `validateCpf(cpf)` | Validação matemática de CPF |
| `showLoading(msg)` | SweetAlert2 loading com mensagem |
| `showError(msg)` | SweetAlert2 error toast |
| `showSuccess(msg)` | SweetAlert2 success com redirect |
| `debounce(fn, delay)` | Debounce para prevenir double-click |

---

## 5. CSS (`checkout.css`)

### 5.1 Variáveis CSS

```css
:root {
    --checkout-primary: #0d6efd;       /* Sobrescrita pelo PHP */
    --checkout-primary-hover: color-mix(in srgb, var(--checkout-primary) 85%, black);
    --checkout-bg: #f8f9fa;
    --checkout-card-bg: #ffffff;
    --checkout-text: #212529;
    --checkout-muted: #6c757d;
    --checkout-border: #dee2e6;
    --checkout-success: #198754;
    --checkout-danger: #dc3545;
    --checkout-radius: 12px;
    --checkout-shadow: 0 2px 12px rgba(0,0,0,0.08);
}

@media (prefers-color-scheme: dark) {
    :root {
        --checkout-bg: #1a1d21;
        --checkout-card-bg: #2b2f33;
        --checkout-text: #e9ecef;
        --checkout-muted: #adb5bd;
        --checkout-border: #3d4248;
        --checkout-shadow: 0 2px 12px rgba(0,0,0,0.3);
    }
}
```

### 5.2 Classes Principais

| Classe | Uso |
|--------|-----|
| `.checkout-body` | Body da página (bg, font, min-height) |
| `.checkout-header` | Cabeçalho com logo e nome |
| `.checkout-main` | Container principal (max-width, padding) |
| `.checkout-summary-card` | Card de resumo do pedido |
| `.checkout-payment-card` | Card de formas de pagamento |
| `.checkout-method-tabs` | Pills de PIX/Cartão/Boleto |
| `.checkout-method-tab` | Cada pill individual |
| `.checkout-method-tab.active` | Tab ativa (cor primária) |
| `.checkout-pix-qr` | Container do QR Code |
| `.checkout-copy-field` | Campo copia-e-cola com botão |
| `.checkout-countdown` | Timer de expiração |
| `.checkout-btn-pay` | Botão principal de pagar |
| `.checkout-footer` | Rodapé com badges de segurança |
| `.checkout-loading` | Overlay de carregamento |

### 5.3 Breakpoints Responsivos

```css
/* Mobile (< 576px) */
@media (max-width: 575.98px) {
    .checkout-main { padding: 0.5rem; }
    .checkout-method-tabs { flex-direction: column; }
    .checkout-pix-qr img { max-width: 200px; }
    .checkout-btn-pay { width: 100%; font-size: 1.1rem; }
}

/* Tablet (576-768px) */
@media (min-width: 576px) and (max-width: 767.98px) {
    .checkout-main { max-width: 540px; }
}

/* Desktop (> 768px) */
@media (min-width: 768px) {
    .checkout-main { max-width: 600px; }
    .checkout-pix-qr img { max-width: 280px; }
}
```

---

## 6. Páginas Secundárias

### 6.1 `confirmation.php` — Página de Confirmação (3 estados)

Esta view recebe a variável `$confirmationState` (`succeeded`, `pending`, `error`) e inclui o partial correspondente.

```php
// confirmation.php
<?php include __DIR__ . '/partials/_header.php'; ?>

<main class="checkout-main container">
  <div class="row justify-content-center">
    <div class="col-lg-6 col-md-8 col-12">
      <div id="confirmationContainer">
        <?php if ($confirmationState === 'succeeded'): ?>
            <?php include __DIR__ . '/partials/_confirmation_success.php'; ?>
        <?php elseif ($confirmationState === 'pending'): ?>
            <?php include __DIR__ . '/partials/_confirmation_pending.php'; ?>
        <?php else: ?>
            <?php include __DIR__ . '/partials/_confirmation_error.php'; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</main>

<?php include __DIR__ . '/partials/_footer.php'; ?>
```

#### Estado CONFIRMADO (`_confirmation_success.php`)

```
┌────────────────────────────────────────────────┐
│                                                │
│         ✅ (animação SVG checkmark)               │
│                                                │
│    Pagamento confirmado!                        │
│                                                │
│  ┌────────────────────────────────────────┐ │
│  │  Pedido:       #0042                        │ │
│  │  Valor pago:   R$ 283,33                    │ │
│  │  Método:       PIX                          │ │
│  │  Data/hora:    08/04/2026 às 14:32          │ │
│  │  Transação:    pi_1Abc123...                │ │
│  │  Status:       ✅ Confirmado                 │ │
│  └────────────────────────────────────────┘ │
│                                                │
│  📧 Comprovante enviado para seu email.         │
│  [Voltar ao portal]                             │
│                                                │
└────────────────────────────────────────────────┘
```

- Página estática (sem polling)
- Pode ser revisitada (token `used` sempre mostra esta página)
- Animação CSS do checkmark SVG ao carregar
- Botão "Voltar ao portal" só aparece se sessão de portal ativa

#### Estado AGUARDANDO (`_confirmation_pending.php`)

```
┌────────────────────────────────────────────────┐
│                                                │
│         ⏳ (spinner animado)                     │
│                                                │
│    Aguardando confirmação...                    │
│                                                │
│  ┌────────────────────────────────────────┐ │
│  │  Pedido:    #0042                            │ │
│  │  Valor:     R$ 283,33                        │ │
│  │  Método:    PIX                              │ │
│  │  Status:    ⏳ Processando                    │ │
│  └────────────────────────────────────────┘ │
│                                                │
│  ℹ️ Estamos verificando seu pagamento.           │
│    Isso pode levar alguns instantes.            │
│                                                │
│  [███████████████░░░░░░░░░░░░░░░] 50%           │
│  Verificando a cada 5 segundos...               │
│                                                │
│  📌 Boleto: a compensação pode levar até        │
│     3 dias úteis.                                │
│                                                │
│  [🔄 Verificar agora]                            │
│                                                │
└────────────────────────────────────────────────┘
```

- Polling automático a cada 5s via `checkStatus()` (JS)
- Ao detectar pagamento → transição animada para CONFIRMADO (sem reload)
- Botão "Verificar agora" dispara checagem manual
- Após 30 min sem confirmação: parar polling, exibir mensagem:
  _"Se você já realizou o pagamento, ele será confirmado em breve. Você pode fechar esta página."_
- Mensagem específica para boleto sobre prazo de compensação

#### Estado ERRO (`_confirmation_error.php`)

```
┌────────────────────────────────────────────────┐
│                                                │
│         ❌ (animação shake)                      │
│                                                │
│    Não foi possível processar o pagamento        │
│                                                │
│  ┌────────────────────────────────────────┐ │
│  │  Pedido:    #0042                            │ │
│  │  Valor:     R$ 283,33                        │ │
│  │  Método:    Cartão de Crédito                 │ │
│  │  Motivo:    Cartão recusado pela operadora    │ │
│  └────────────────────────────────────────┘ │
│                                                │
│  ⚠️ Possíveis motivos:                           │
│    • Dados do cartão incorretos                  │
│    • Limite insuficiente                         │
│    • Cartão bloqueado pelo banco                 │
│    • Falha temporária na operadora               │
│                                                │
│  [🔄 Tentar novamente]   [📞 Contatar vendedor] │
│                                                │
└────────────────────────────────────────────────┘
```

- Mensagem amigável (nunca expor detalhes técnicos)
- Se token ativo: "Tentar novamente" → redirect para `/?page=checkout&token=xxx`
- Se token expirado: ocultar retry, exibir só "Contatar vendedor"
- Dados de contato da empresa (telefone/email de `company_settings`)

#### JavaScript — Polling na Página de Confirmação

```javascript
// Incluído apenas no estado 'pending'
const ConfirmationPolling = {
    interval: null,
    attempts: 0,
    maxAttempts: 360, // 30min / 5s

    init() {
        this.startPolling();
        document.getElementById('btnCheckNow')
            ?.addEventListener('click', () => this.checkNow());
    },

    startPolling() {
        this.interval = setInterval(() => this.poll(), 5000);
    },

    async poll() {
        this.attempts++;
        if (this.attempts >= this.maxAttempts) {
            this.stopPolling();
            this.showTimeoutMessage();
            return;
        }

        try {
            const resp = await fetch(
                `${config.statusUrl}&token=${config.token}&external_id=${config.externalId}`
            );
            const data = await resp.json();

            if (data.status === 'succeeded') {
                this.stopPolling();
                this.transitionToSuccess(data);
            } else if (data.status === 'failed') {
                this.stopPolling();
                this.transitionToError(data);
            }
            // 'pending' → continua polling
        } catch (e) {
            console.warn('Polling error:', e);
        }
    },

    checkNow() {
        this.poll(); // Checagem manual
    },

    stopPolling() {
        clearInterval(this.interval);
    },

    transitionToSuccess(data) {
        const container = document.getElementById('confirmationContainer');
        container.innerHTML = ''; // Limpa
        // Injeta partial de sucesso via AJAX ou template JS
        container.classList.add('fade-in');
        // ... renderizar dados de confirmação
    },

    transitionToError(data) {
        const container = document.getElementById('confirmationContainer');
        // Transição similar ao success, com dados de erro
    },

    showTimeoutMessage() {
        Swal.fire({
            icon: 'info',
            title: 'Verificação pausada',
            html: 'Se você já realizou o pagamento, ele será confirmado em breve.<br>Você pode fechar esta página.',
            confirmButtonText: 'OK'
        });
    }
};

document.addEventListener('DOMContentLoaded', () => ConfirmationPolling.init());
```

#### CSS — Animações de Transição

```css
/* Animação checkmark (success) */
.confirmation-checkmark {
    animation: scaleIn 0.5s ease-out;
}

@keyframes scaleIn {
    0% { transform: scale(0); opacity: 0; }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); opacity: 1; }
}

/* Animação shake (error) */
.confirmation-error-icon {
    animation: shake 0.6s ease-in-out;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-10px); }
    75% { transform: translateX(10px); }
}

/* Transição entre estados */
.fade-in {
    animation: fadeIn 0.4s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Spinner (pending) */
.confirmation-spinner {
    width: 64px;
    height: 64px;
    border: 4px solid var(--checkout-primary);
    border-top-color: transparent;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}
```

### 6.2 `expired.php`

```
┌────────────────────────────────────────────────────┐
│  [Logo]  Nome da Empresa                            │
├────────────────────────────────────────────────────┤
│                                                     │
│              ⏰                                     │
│     Link de pagamento expirado                      │
│                                                     │
│  Este link não está mais disponível.                │
│  Entre em contato com o vendedor para               │
│  solicitar um novo link de pagamento.               │
│                                                     │
│  📞 Contato: (XX) XXXX-XXXX                        │
│  📧 email@empresa.com                              │
│                                                     │
└────────────────────────────────────────────────────┘
```

---

## 7. Acessibilidade

- Todos os botões com `aria-label` descritivo
- Tab navigation funcional entre métodos de pagamento
- Contraste mínimo 4.5:1 (WCAG AA)
- Labels associados a inputs (`for`/`id`)
- Indicadores visuais de loading para screen readers (`aria-live`)
- Skip links se necessário
- Focus visible nos elementos interativos

---

*Especificação de Frontend — Checkout Transparente v1 — 2026-04-08*
