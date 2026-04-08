<?php
/**
 * Checkout — Resumo do pedido (coluna esquerda).
 * Variáveis: $token, $company, $orderItems, $extraCosts
 */
$orderDescription = $token['order_description'] ?? $token['description'] ?? '';
$orderNumber      = $token['order_number'] ?? $token['order_id'] ?? '';
$amount           = (float) ($token['amount'] ?? 0);
$currency         = $token['currency'] ?? 'BRL';

$installmentNumber = $token['installment_number'] ?? null;
$totalInstallments = $token['total_installments'] ?? null;
$dueDate           = $token['due_date'] ?? null;

// Calculate subtotals
$itemsSubtotal = 0;
$totalDiscount = 0;
$totalExtras   = 0;

if (!empty($orderItems)) {
    foreach ($orderItems as $item) {
        $itemsSubtotal += (float) ($item['subtotal'] ?? 0);
        $totalDiscount += (float) ($item['discount'] ?? 0);
    }
}
if (!empty($extraCosts)) {
    foreach ($extraCosts as $extra) {
        $totalExtras += (float) ($extra['amount'] ?? 0);
    }
}
?>

<!-- Order Info -->
<div class="co-card">
    <div class="co-card-header">
        <i class="fas fa-receipt"></i>
        <h2>Resumo do pedido</h2>
    </div>
    <div class="co-card-body">
        <?php if ($orderNumber): ?>
            <div class="co-order-title">Pedido</div>
            <div class="co-order-number">#<?= e($orderNumber) ?></div>
        <?php endif; ?>
        <?php if ($orderDescription): ?>
            <div class="co-order-desc"><?= e($orderDescription) ?></div>
        <?php endif; ?>

        <?php if ($installmentNumber && $totalInstallments): ?>
            <div class="co-installment-info">
                <i class="fas fa-calendar-check"></i>
                Parcela <?= (int) $installmentNumber ?>/<?= (int) $totalInstallments ?>
                <?php if ($dueDate): ?>
                    &middot; Venc. <?= e(date('d/m/Y', strtotime($dueDate))) ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Items Table -->
<?php if (!empty($orderItems)): ?>
<div class="co-card">
    <div class="co-card-header">
        <i class="fas fa-box"></i>
        <h2>Itens</h2>
    </div>
    <div class="co-card-body" style="padding:0;">
        <table class="co-items-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th style="text-align:center;">Qtd</th>
                    <th style="text-align:right;">Valor</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orderItems as $item):
                    $itemName     = $item['product_name'] ?? 'Item';
                    $variant      = $item['combination_label'] ?? ($item['grade_description'] ?? '');
                    $qty          = (int) ($item['quantity'] ?? 1);
                    $unitPrice    = (float) ($item['unit_price'] ?? 0);
                    $subtotal     = (float) ($item['subtotal'] ?? 0);
                    $discount     = (float) ($item['discount'] ?? 0);
                ?>
                <tr>
                    <td>
                        <div class="co-item-name"><?= e($itemName) ?></div>
                        <?php if ($variant): ?>
                            <div class="co-item-variant"><?= e($variant) ?></div>
                        <?php endif; ?>
                        <?php if ($discount > 0): ?>
                            <div class="co-item-discount">-R$ <?= eNum($discount) ?> desconto</div>
                        <?php endif; ?>
                    </td>
                    <td class="co-item-qty"><?= $qty ?></td>
                    <td class="co-item-price">
                        R$ <?= eNum($subtotal - $discount) ?>
                        <?php if ($discount > 0): ?>
                            <br><small style="color:var(--co-text-muted);text-decoration:line-through;font-size:0.72rem;">R$ <?= eNum($subtotal) ?></small>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Totals breakdown -->
        <div class="co-totals">
            <div class="co-totals-row">
                <span class="label">Subtotal (<?= count($orderItems) ?> <?= count($orderItems) === 1 ? 'item' : 'itens' ?>)</span>
                <span class="value">R$ <?= eNum($itemsSubtotal) ?></span>
            </div>
            <?php if ($totalDiscount > 0): ?>
                <div class="co-totals-row discount">
                    <span class="label">Descontos</span>
                    <span class="value">-R$ <?= eNum($totalDiscount) ?></span>
                </div>
            <?php endif; ?>
            <?php if (!empty($extraCosts)): ?>
                <?php foreach ($extraCosts as $extra): ?>
                    <div class="co-totals-row extra">
                        <span class="label"><?= e($extra['description'] ?? 'Custo adicional') ?></span>
                        <span class="value">R$ <?= eNum((float) ($extra['amount'] ?? 0)) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            <div class="co-totals-row total">
                <span class="label">Total</span>
                <span class="value">R$ <?= eNum($amount) ?></span>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<!-- Fallback: No items, just show total -->
<div class="co-card">
    <div class="co-card-header">
        <i class="fas fa-money-bill-wave"></i>
        <h2>Valor</h2>
    </div>
    <div class="co-card-body">
        <div class="co-totals">
            <div class="co-totals-row total" style="border-top:none;margin-top:0;padding-top:0;">
                <span class="label">Total a pagar</span>
                <span class="value">R$ <?= eNum($amount) ?></span>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
