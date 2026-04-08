<?php
/**
 * Checkout — Confirmation: Estado PENDENTE.
 * Variáveis esperadas: $token (array), $company (array), $externalId (string)
 */
$orderNumber = $token['order_number'] ?? $token['order_id'] ?? '';
$amount      = (float) ($token['amount'] ?? 0);
$usedMethod  = $token['used_method'] ?? '';
$isBoleto    = ($usedMethod === 'boleto');

$methodLabels = [
    'pix'         => 'PIX',
    'credit_card' => 'Cartão de Crédito',
    'boleto'      => 'Boleto Bancário',
];
$methodLabel = $methodLabels[$usedMethod] ?? $usedMethod;
?>
<div class="checkout-confirmation-card text-center" id="pendingState">
    <div class="confirmation-spinner mb-3 mx-auto"></div>

    <h4 class="mb-1">Processando pagamento</h4>
    <p style="font-size:0.82rem;color:var(--co-text-secondary);margin-bottom:1.5rem;">Estamos verificando seu pagamento. Isso pode levar alguns instantes.</p>

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
                <span class="checkout-status-badge pending"><i class="fas fa-clock"></i> Processando</span>
            </div>
        </div>
    </div>

    <?php if ($isBoleto): ?>
        <div class="checkout-alert warning mb-3 text-start" style="max-width:340px;margin:0 auto;">
            <i class="fas fa-info-circle"></i>
            <span><strong>Boleto:</strong> a compensação pode levar até 3 dias úteis.</span>
        </div>
    <?php endif; ?>

    <div class="mb-3" id="pollingProgress">
        <div class="progress" style="height: 3px; border-radius: 2px; background: var(--co-border-light);">
            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 10%; background: var(--co-primary);"></div>
        </div>
        <small style="font-size:0.7rem;color:var(--co-text-muted);">Verificando a cada 5 segundos...</small>
    </div>

    <button type="button" class="btn btn-outline-primary btn-sm" id="btnCheckNow" style="font-size:0.8rem;border-radius:var(--co-radius-sm);">
        <i class="fas fa-sync-alt me-1"></i> Verificar agora
    </button>
</div>

<script>
    const CONFIRMATION_CONFIG = {
        token: <?= eJs($token['token']) ?>,
        externalId: <?= eJs($externalId) ?>,
        statusUrl: '/?page=checkout&action=checkStatus',
        confirmationUrl: '/?page=checkout&action=confirmation'
    };
</script>
