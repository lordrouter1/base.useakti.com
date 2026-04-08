<?php
/**
 * Checkout — Página de confirmação (3 estados).
 *
 * Variáveis esperadas:
 *   $token              (array)  Dados do checkout_token
 *   $company            (array)  Dados de company_settings
 *   $confirmationState  (string) 'succeeded' | 'pending' | 'error'
 *   $externalId         (string) ID da transação externa
 *   $errorMessage       (string) Mensagem de erro (se houver)
 */
$companyName  = $company['company_name'] ?? $company['name'] ?? 'Pagamento';
$primaryColor = $company['primary_color'] ?? $company['checkout_primary_color'] ?? '#3b82f6';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Confirmação &mdash; <?= e($companyName) ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="/assets/css/checkout.css" rel="stylesheet">

    <style>
        :root {
            --co-primary: <?= e($primaryColor) ?>;
        }
    </style>
</head>
<body class="checkout-body">

    <?php include __DIR__ . '/partials/_header.php'; ?>

    <main class="checkout-main" style="max-width:600px;">
        <div id="confirmationContainer">
            <?php if ($confirmationState === 'succeeded'): ?>
                <?php include __DIR__ . '/partials/_confirmation_success.php'; ?>
            <?php elseif ($confirmationState === 'pending'): ?>
                <?php include __DIR__ . '/partials/_confirmation_pending.php'; ?>
            <?php else: ?>
                <?php include __DIR__ . '/partials/_confirmation_error.php'; ?>
            <?php endif; ?>
        </div>
    </main>

    <?php include __DIR__ . '/partials/_footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <?php if ($confirmationState === 'pending'): ?>
    <script src="/assets/js/checkout.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof ConfirmationPolling !== 'undefined') {
                ConfirmationPolling.init();
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>
