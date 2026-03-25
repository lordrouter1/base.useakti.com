<?php
/**
 * Portal do Cliente — Detalhe de Parcela
 *
 * Variáveis: $installment, $company
 */
$inst = $installment;
$isPaid    = ($inst['status'] === 'pago');
$isOverdue = ($inst['status'] === 'atrasado');
$statusClass = $isPaid ? 'success' : ($isOverdue ? 'danger' : 'warning');
?>

<div class="portal-page">
    <!-- ═══ Header ═══ -->
    <div class="portal-page-header">
        <a href="?page=portal&action=installments" class="portal-back-link">
            <i class="fas fa-arrow-left me-1"></i> <?= __p('back') ?>
        </a>
        <h1 class="portal-page-title">
            <i class="fas fa-receipt me-2"></i>
            <?= __p('order_installment_number', ['n' => (int) $inst['installment_number']]) ?>
        </h1>
    </div>

    <!-- ═══ Card de Status ═══ -->
    <div class="portal-card mb-3">
        <div class="portal-card-body text-center">
            <div class="mb-2">
                <span class="badge bg-<?= $statusClass ?>" style="font-size:1.1rem; padding:0.5rem 1rem;">
                    <i class="fas fa-<?= $isPaid ? 'check-circle' : ($isOverdue ? 'exclamation-circle' : 'clock') ?> me-1"></i>
                    <?= __p('financial_' . $inst['status']) ?>
                </span>
            </div>
            <div class="portal-installment-detail-amount">
                <?= portal_money($inst['amount']) ?>
            </div>
        </div>
    </div>

    <!-- ═══ Informações ═══ -->
    <div class="portal-card mb-3">
        <div class="portal-card-header">
            <h5><i class="fas fa-info-circle me-2"></i> <?= __p('details') ?></h5>
        </div>
        <div class="portal-card-body">
            <div class="portal-detail-row">
                <span class="portal-detail-label"><?= __p('order_detail_title', ['id' => '']) ?></span>
                <a href="?page=portal&action=orderDetail&id=<?= (int) $inst['order_id'] ?>" class="text-primary">
                    #<?= (int) $inst['order_id'] ?>
                </a>
            </div>
            <div class="portal-detail-row">
                <span class="portal-detail-label"><?= __p('financial_due_date', ['date' => '']) ?></span>
                <span><?= portal_date($inst['due_date']) ?></span>
            </div>
            <?php if ($isPaid && !empty($inst['paid_date'])): ?>
                <div class="portal-detail-row">
                    <span class="portal-detail-label"><?= __p('financial_paid_at', ['date' => '']) ?></span>
                    <span><?= portal_date($inst['paid_date']) ?></span>
                </div>
            <?php endif; ?>
            <?php if (!empty($inst['payment_method'])): ?>
                <div class="portal-detail-row">
                    <span class="portal-detail-label"><?= __p('financial_method') ?></span>
                    <span><?= e($inst['payment_method']) ?></span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ═══ Link de Pagamento (se disponível) ═══ -->
    <?php if (!$isPaid && !empty($inst['payment_link'])): ?>
        <div class="portal-card mb-3">
            <div class="portal-card-body text-center">
                <p class="mb-2"><?= __p('payment_link_description') ?></p>
                <a href="<?= eAttr($inst['payment_link']) ?>" target="_blank" class="btn btn-primary btn-lg">
                    <i class="fas fa-credit-card me-2"></i>
                    <?= __p('payment_link_btn') ?>
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>
