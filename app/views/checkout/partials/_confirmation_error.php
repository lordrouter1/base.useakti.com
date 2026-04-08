<?php
/**
 * Checkout — Confirmation: Estado ERRO.
 * Variáveis esperadas: $token (array), $company (array), $errorMessage (string)
 */
$orderNumber = $token['order_number'] ?? $token['order_id'] ?? '';
$amount      = (float) ($token['amount'] ?? 0);
$usedMethod  = $token['used_method'] ?? '';
$tokenActive = ($token['status'] ?? '') === 'active';

$methodLabels = [
    'pix'         => 'PIX',
    'credit_card' => 'Cartão de Crédito',
    'boleto'      => 'Boleto Bancário',
];
$methodLabel = $methodLabels[$usedMethod] ?? $usedMethod;

$companyPhone = $company['phone'] ?? $company['company_phone'] ?? '';
$companyEmail = $company['email'] ?? $company['company_email'] ?? '';

$friendlyError = $errorMessage ?: 'Não foi possível processar o pagamento.';
?>
<div class="checkout-confirmation-card text-center">
    <div class="checkout-state-icon error mx-auto confirmation-error-icon">
        <i class="fas fa-times" style="font-size: 1.4rem; color: var(--co-danger);"></i>
    </div>

    <h4 class="mb-1" style="color: var(--co-danger);">Pagamento não processado</h4>
    <p style="font-size:0.82rem;color:var(--co-text-secondary);margin-bottom:1.5rem;"><?= e($friendlyError) ?></p>

    <div class="card checkout-confirmation-details mx-auto mb-4">
        <div class="card-body text-start">
            <?php if ($orderNumber): ?>
                <div class="checkout-detail-row">
                    <span class="checkout-detail-label">Pedido</span>
                    <span class="checkout-detail-value">#<?= e($orderNumber) ?></span>
                </div>
            <?php endif; ?>
            <div class="checkout-detail-row">
                <span class="checkout-detail-label">Valor</span>
                <span class="checkout-detail-value">R$ <?= eNum($amount) ?></span>
            </div>
            <?php if ($methodLabel): ?>
                <div class="checkout-detail-row">
                    <span class="checkout-detail-label">Método</span>
                    <span class="checkout-detail-value"><?= e($methodLabel) ?></span>
                </div>
            <?php endif; ?>
            <div class="checkout-detail-row">
                <span class="checkout-detail-label">Status</span>
                <span class="checkout-status-badge error"><i class="fas fa-times"></i> Falhou</span>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 justify-content-center flex-wrap">
        <?php if ($tokenActive): ?>
            <a href="/?page=checkout&token=<?= eAttr($token['token']) ?>" class="btn checkout-btn-pay" style="font-size:0.85rem;padding:0.6rem 1.5rem;">
                <i class="fas fa-redo me-1"></i> Tentar novamente
            </a>
        <?php endif; ?>
        <?php if ($companyPhone || $companyEmail): ?>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="showContactInfo()" style="font-size:0.82rem;border-radius:var(--co-radius-sm);">
                <i class="fas fa-headset me-1"></i> Suporte
            </button>
        <?php endif; ?>
    </div>
</div>

<?php if ($companyPhone || $companyEmail): ?>
<script>
function showContactInfo() {
    Swal.fire({
        title: 'Contato',
        html: '<?php if ($companyPhone): ?><p><i class="fas fa-phone me-1"></i> <?= eJs($companyPhone) ?></p><?php endif; ?><?php if ($companyEmail): ?><p><i class="fas fa-envelope me-1"></i> <?= eJs($companyEmail) ?></p><?php endif; ?>',
        icon: 'info',
        confirmButtonText: 'OK'
    });
}
</script>
<?php endif; ?>
