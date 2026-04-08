<?php
/**
 * Checkout — Confirmation: Estado SUCESSO.
 * Variáveis esperadas: $token (array), $company (array), $externalId (string)
 */
$orderNumber = $token['order_number'] ?? $token['order_id'] ?? '';
$amount      = (float) ($token['amount'] ?? 0);
$usedMethod  = $token['used_method'] ?? '';
$usedAt      = $token['used_at'] ?? '';

$methodLabels = [
    'pix'         => 'PIX',
    'credit_card' => 'Cartão de Crédito',
    'boleto'      => 'Boleto Bancário',
];
$methodLabel = $methodLabels[$usedMethod] ?? $usedMethod;
?>
<div class="checkout-confirmation-card text-center">
    <div class="confirmation-checkmark mb-3">
        <svg class="checkmark-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52" width="56" height="56">
            <circle class="checkmark-circle" cx="26" cy="26" r="25" fill="none" stroke="var(--co-success)" stroke-width="2"/>
            <path class="checkmark-check" fill="none" stroke="var(--co-success)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
        </svg>
    </div>

    <h4 class="mb-1" style="color: var(--co-success);">Pagamento confirmado</h4>
    <p style="font-size:0.82rem;color:var(--co-text-secondary);margin-bottom:1.5rem;">Seu pagamento foi processado com sucesso.</p>

    <div class="card checkout-confirmation-details mx-auto">
        <div class="card-body text-start">
            <?php if ($orderNumber): ?>
                <div class="checkout-detail-row">
                    <span class="checkout-detail-label">Pedido</span>
                    <span class="checkout-detail-value">#<?= e($orderNumber) ?></span>
                </div>
            <?php endif; ?>
            <div class="checkout-detail-row">
                <span class="checkout-detail-label">Valor pago</span>
                <span class="checkout-detail-value">R$ <?= eNum($amount) ?></span>
            </div>
            <?php if ($methodLabel): ?>
                <div class="checkout-detail-row">
                    <span class="checkout-detail-label">Método</span>
                    <span class="checkout-detail-value"><?= e($methodLabel) ?></span>
                </div>
            <?php endif; ?>
            <?php if ($usedAt): ?>
                <div class="checkout-detail-row">
                    <span class="checkout-detail-label">Data/hora</span>
                    <span class="checkout-detail-value"><?= e(date('d/m/Y H:i', strtotime($usedAt))) ?></span>
                </div>
            <?php endif; ?>
            <?php if ($externalId): ?>
                <div class="checkout-detail-row">
                    <span class="checkout-detail-label">Transação</span>
                    <span class="checkout-detail-value text-truncate" style="max-width:160px;" title="<?= eAttr($externalId) ?>"><?= e($externalId) ?></span>
                </div>
            <?php endif; ?>
            <div class="checkout-detail-row">
                <span class="checkout-detail-label">Status</span>
                <span class="checkout-status-badge success"><i class="fas fa-check"></i> Confirmado</span>
            </div>
        </div>
    </div>
</div>
