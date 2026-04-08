<?php
/**
 * Checkout — Token expirado/inválido/cancelado.
 *
 * Variáveis esperadas:
 *   $company (array) Dados de company_settings
 */
$companyName  = $company['company_name'] ?? $company['name'] ?? 'Empresa';
$primaryColor = $company['primary_color'] ?? $company['checkout_primary_color'] ?? '#3b82f6';
$companyPhone = $company['phone'] ?? $company['company_phone'] ?? '';
$companyEmail = $company['email'] ?? $company['company_email'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Link expirado &mdash; <?= e($companyName) ?></title>

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
        <div class="checkout-expired-card">
            <div class="checkout-expired-icon" style="margin:0 auto;">
                <i class="fas fa-clock" style="font-size:1.4rem;color:var(--co-text-muted);"></i>
            </div>
            <h4>Link de pagamento expirado</h4>
            <p class="checkout-expired-desc">
                Este link não está mais disponível.<br>
                Entre em contato com o vendedor para solicitar um novo link.
            </p>

            <?php if ($companyPhone): ?>
                <p class="checkout-expired-contact">
                    <i class="fas fa-phone me-1"></i>
                    <a href="tel:<?= eAttr(preg_replace('/[^0-9+]/', '', $companyPhone)) ?>"><?= e($companyPhone) ?></a>
                </p>
            <?php endif; ?>
            <?php if ($companyEmail): ?>
                <p class="checkout-expired-contact" style="margin-bottom:0;">
                    <i class="fas fa-envelope me-1"></i>
                    <a href="mailto:<?= eAttr($companyEmail) ?>"><?= e($companyEmail) ?></a>
                </p>
            <?php endif; ?>
        </div>
    </main>

    <?php include __DIR__ . '/partials/_footer.php'; ?>

</body>
</html>
