<?php
/**
 * Checkout Transparente — Página principal de pagamento.
 *
 * Variáveis esperadas:
 *   $token            (array)  Dados do checkout_token (com JOINs)
 *   $company          (array)  Dados de company_settings
 *   $gatewaySlug      (string) Slug do gateway ativo
 *   $publicKey        (string) Chave pública do gateway (para SDK JS)
 *   $supportedMethods (array)  Métodos permitidos: ['pix','credit_card','boleto']
 */
$companyName  = $company['company_name'] ?? $company['name'] ?? 'Pagamento';
$primaryColor = $company['primary_color'] ?? $company['checkout_primary_color'] ?? '#3b82f6';
$amount       = (float) ($token['amount'] ?? 0);

$methodIcons = [
    'pix'         => 'fas fa-qrcode',
    'credit_card' => 'fas fa-credit-card',
    'boleto'      => 'fas fa-barcode',
    'debit_card'  => 'fas fa-money-check',
];
$methodLabels = [
    'pix'         => 'PIX',
    'credit_card' => 'Cartão de Crédito',
    'boleto'      => 'Boleto Bancário',
    'debit_card'  => 'Cartão de Débito',
];
$methodDescs = [
    'pix'         => 'Aprovação instantânea',
    'credit_card' => 'Parcelamento disponível',
    'boleto'      => 'Vence em até 3 dias úteis',
    'debit_card'  => 'Débito à vista',
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Pagamento &mdash; <?= e($companyName) ?></title>

    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 CDN -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- Checkout CSS -->
    <link href="/assets/css/checkout.css" rel="stylesheet">

    <style>
        :root {
            --co-primary: <?= e($primaryColor) ?>;
        }
    </style>
</head>
<body class="checkout-body">

    <?php include __DIR__ . '/partials/_header.php'; ?>

    <main class="checkout-main">
        <div class="checkout-grid">
            <!-- LEFT COLUMN: Order Summary -->
            <div class="checkout-summary-section">
                <?php include __DIR__ . '/partials/_order_summary.php'; ?>
            </div>

            <!-- RIGHT COLUMN: Payment Methods -->
            <div class="checkout-payment-section">
                <div class="co-card">
                    <div class="co-card-header">
                        <i class="fas fa-wallet"></i>
                        <h2>Pagamento</h2>
                    </div>
                    <div class="co-card-body">
                        <!-- Total -->
                        <div class="co-pay-total">
                            <div class="co-pay-total-label">Total a pagar</div>
                            <div class="co-pay-total-value">R$ <?= eNum($amount) ?></div>
                        </div>

                        <!-- Payment method selector (radio cards) -->
                        <?php if (count($supportedMethods) > 1): ?>
                        <div class="co-method-list" id="paymentMethodSelector">
                            <?php foreach ($supportedMethods as $i => $method): ?>
                                <div class="co-method-option <?= $i === 0 ? 'active' : '' ?>"
                                     data-method="<?= e($method) ?>">
                                    <div class="co-method-radio">
                                        <div class="co-method-radio-dot"></div>
                                    </div>
                                    <div class="co-method-icon">
                                        <i class="<?= e($methodIcons[$method] ?? 'fas fa-money-bill') ?>"></i>
                                    </div>
                                    <div>
                                        <div class="co-method-label"><?= e($methodLabels[$method] ?? $method) ?></div>
                                        <div class="co-method-desc"><?= e($methodDescs[$method] ?? '') ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Payment forms (only active one visible) -->
                        <div class="co-payment-form">
                            <div id="paymentMethodContent">
                                <?php
                                $activeMethod = $supportedMethods[0] ?? '';
                                foreach ($supportedMethods as $method):
                                    $isActive = ($method === $activeMethod);
                                ?>
                                    <?php if ($method === 'pix'): ?>
                                        <?php $__tabActive = $isActive; include __DIR__ . '/partials/_pix.php'; ?>
                                    <?php elseif ($method === 'credit_card'): ?>
                                        <?php $__tabActive = $isActive; include __DIR__ . '/partials/_credit_card.php'; ?>
                                    <?php elseif ($method === 'boleto'): ?>
                                        <?php $__tabActive = $isActive; include __DIR__ . '/partials/_boleto.php'; ?>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
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
            token: <?= eJs($token['token']) ?>,
            gatewaySlug: <?= eJs($gatewaySlug) ?>,
            publicKey: <?= eJs($publicKey) ?>,
            amount: <?= json_encode($amount) ?>,
            currency: <?= eJs($token['currency'] ?? 'BRL') ?>,
            methods: <?= json_encode($supportedMethods) ?>,
            processUrl: '/?page=checkout&action=processPayment',
            tokenizeUrl: '/?page=checkout&action=tokenizeCard',
            statusUrl: '/?page=checkout&action=checkStatus',
            confirmationUrl: '/?page=checkout&action=confirmation&token=' + <?= eJs($token['token']) ?>
        };
    </script>

    <!-- Bootstrap JS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Payment method selector + init -->
    <script>
    (function() {
        var PANE_MAP = { pix: 'pix-tab-pane', credit_card: 'card-tab-pane', boleto: 'boleto-tab-pane' };

        function switchMethod(method, optionEl) {
            // Toggle radio card active state
            var allOptions = document.querySelectorAll('.co-method-option');
            for (var i = 0; i < allOptions.length; i++) {
                allOptions[i].classList.remove('active');
            }
            optionEl.classList.add('active');

            // Hide all panes
            var allPanes = document.querySelectorAll('#paymentMethodContent .co-method-pane');
            for (var j = 0; j < allPanes.length; j++) {
                allPanes[j].style.display = 'none';
            }

            // Show target pane
            var targetId = PANE_MAP[method];
            if (targetId) {
                var target = document.getElementById(targetId);
                if (target) target.style.display = 'block';
            }
        }

        // Event delegation on the selector container
        var selector = document.getElementById('paymentMethodSelector');
        if (selector) {
            selector.addEventListener('click', function(e) {
                var option = e.target.closest('.co-method-option');
                if (!option) return;
                var method = option.getAttribute('data-method');
                if (method) switchMethod(method, option);
            });
        }
    })();
    </script>
    <!-- Checkout JS -->
    <script src="/assets/js/checkout.js"></script>
    <!-- Card masks + Init gateway -->
    <script>
    (function() {
        // Card number mask: 0000 0000 0000 0000
        var cardNum = document.getElementById('cardNumber');
        if (cardNum) {
            cardNum.addEventListener('input', function() {
                var v = this.value.replace(/\D/g, '').substring(0, 16);
                var parts = [];
                for (var i = 0; i < v.length; i += 4) {
                    parts.push(v.substring(i, i + 4));
                }
                this.value = parts.join(' ');
            });
        }

        // Expiry mask: MM/AA
        var cardExp = document.getElementById('cardExpiry');
        if (cardExp) {
            cardExp.addEventListener('input', function() {
                var v = this.value.replace(/\D/g, '').substring(0, 4);
                if (v.length >= 3) {
                    this.value = v.substring(0, 2) + '/' + v.substring(2);
                } else {
                    this.value = v;
                }
            });
        }

        // CVV mask: only digits
        var cardCvv = document.getElementById('cardCvv');
        if (cardCvv) {
            cardCvv.addEventListener('input', function() {
                this.value = this.value.replace(/\D/g, '').substring(0, 4);
            });
        }

        // Init gateway
        if (typeof AktiCheckout !== 'undefined' && typeof CHECKOUT_CONFIG !== 'undefined') {
            AktiCheckout.init(CHECKOUT_CONFIG);
        }
    })();
    </script>
</body>
</html>
