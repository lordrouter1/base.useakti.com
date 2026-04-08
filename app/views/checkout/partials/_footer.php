<?php
/**
 * Checkout — Footer standalone.
 * Variáveis esperadas: $company (array de company_settings)
 */
$companyName = $company['company_name'] ?? $company['name'] ?? 'Empresa';
?>
<footer class="checkout-footer">
    <div class="container text-center">
        <div class="checkout-footer-badges">
            <span class="checkout-footer-badge"><i class="fas fa-shield-halved"></i> Conexão segura</span>
            <span class="checkout-footer-badge"><i class="fas fa-lock"></i> Dados criptografados</span>
        </div>
        <p class="checkout-footer-text">
            &copy; <?= date('Y') ?> <?= e($companyName) ?> &middot; Powered by Akti
        </p>
    </div>
</footer>
