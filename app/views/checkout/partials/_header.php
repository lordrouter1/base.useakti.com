<?php
/**
 * Checkout — Header standalone (barra commerce).
 * Variáveis esperadas: $company (array de company_settings)
 */
$companyName  = $company['company_name'] ?? $company['name'] ?? 'Empresa';
$logoPath     = $company['logo_path'] ?? $company['company_logo'] ?? '';
?>
<header class="checkout-header">
    <div class="checkout-header-inner">
        <div class="checkout-header-brand">
            <?php if ($logoPath && file_exists($logoPath)): ?>
                <img src="/<?= eAttr($logoPath) ?>" alt="<?= eAttr($companyName) ?>" class="checkout-header-logo">
            <?php endif; ?>
            <span class="checkout-header-name"><?= e($companyName) ?></span>
        </div>
        <div class="checkout-header-secure">
            <i class="fas fa-lock"></i> Pagamento seguro
        </div>
    </div>
</header>
