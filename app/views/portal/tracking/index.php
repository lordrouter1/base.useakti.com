<?php
/**
 * Portal do Cliente — Rastreamento
 *
 * Variáveis: $trackingOrders, $trackingDetail, $company
 */
?>

<div class="portal-page">
    <!-- ═══ Header ═══ -->
    <div class="portal-page-header">
        <h1 class="portal-page-title">
            <i class="fas fa-truck me-2"></i>
            <?= __p('tracking_title') ?>
        </h1>
    </div>

    <!-- ═══ Detalhe de Tracking (se informado via ?id=X) ═══ -->
    <?php if ($trackingDetail): ?>
        <div class="portal-card mb-3 portal-tracking-highlight">
            <div class="portal-card-header">
                <h5>
                    <i class="fas fa-truck me-2"></i>
                    <?= __p('order_detail_title', ['id' => (int) $trackingDetail['id']]) ?>
                </h5>
                <span class="badge bg-<?= portal_stage_class($trackingDetail['pipeline_stage'] ?? '') ?>">
                    <?= __p('status_' . ($trackingDetail['pipeline_stage'] ?? 'envio')) ?>
                </span>
            </div>
            <div class="portal-card-body">
                <?php if (!empty($trackingDetail['tracking_code'])): ?>
                    <div class="portal-detail-row">
                        <span class="portal-detail-label">
                            <i class="fas fa-barcode me-1"></i> <?= __p('tracking_code') ?>
                        </span>
                        <span class="fw-bold portal-tracking-code"><?= e($trackingDetail['tracking_code']) ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($trackingDetail['tracking_carrier'])): ?>
                    <div class="portal-detail-row">
                        <span class="portal-detail-label">
                            <i class="fas fa-building me-1"></i> <?= __p('tracking_carrier') ?>
                        </span>
                        <span><?= e($trackingDetail['tracking_carrier']) ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($trackingDetail['shipping_address'])): ?>
                    <div class="portal-detail-row">
                        <span class="portal-detail-label">
                            <i class="fas fa-map-marker-alt me-1"></i> <?= __p('tracking_destination') ?>
                        </span>
                        <span><?= e($trackingDetail['shipping_address']) ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($trackingDetail['scheduled_date'])): ?>
                    <div class="portal-detail-row">
                        <span class="portal-detail-label">
                            <i class="fas fa-calendar-check me-1"></i> <?= __p('tracking_forecast') ?>
                        </span>
                        <span><?= portal_date($trackingDetail['scheduled_date']) ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($trackingDetail['tracking_url'])): ?>
                    <div class="mt-3 text-center">
                        <a href="<?= eAttr($trackingDetail['tracking_url']) ?>" target="_blank"
                           class="btn btn-primary">
                            <i class="fas fa-external-link-alt me-1"></i>
                            <?= __p('tracking_track_btn') ?>
                        </a>
                    </div>
                <?php elseif (!empty($trackingDetail['tracking_code'])): ?>
                    <div class="mt-3 text-center">
                        <button class="btn btn-outline-primary btn-sm"
                                onclick="navigator.clipboard.writeText('<?= eAttr($trackingDetail['tracking_code']) ?>')">
                            <i class="fas fa-copy me-1"></i>
                            <?= __p('tracking_copy_code') ?>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- ═══ Lista de Pedidos com Rastreamento ═══ -->
    <?php if (empty($trackingOrders)): ?>
        <div class="portal-empty-state">
            <i class="fas fa-truck"></i>
            <p><?= __p('tracking_empty') ?></p>
        </div>
    <?php else: ?>
        <div class="portal-section">
            <h3 class="portal-section-title mb-3">
                <i class="fas fa-list me-2"></i>
                <?= __p('tracking_orders_title') ?>
            </h3>
            <div class="portal-order-list">
                <?php foreach ($trackingOrders as $order): ?>
                    <a href="?page=portal&action=tracking&id=<?= (int) $order['id'] ?>"
                       class="portal-order-card <?= $trackingDetail && (int) $trackingDetail['id'] === (int) $order['id'] ? 'portal-card-selected' : '' ?>">
                        <div class="portal-order-card-header">
                            <span class="portal-order-id">#<?= (int) $order['id'] ?></span>
                            <span class="badge bg-<?= portal_stage_class($order['pipeline_stage'] ?? '') ?>">
                                <?= __p('status_' . ($order['pipeline_stage'] ?? 'envio')) ?>
                            </span>
                        </div>
                        <div class="portal-order-card-body">
                            <span class="portal-order-date">
                                <i class="fas fa-calendar-alt me-1"></i>
                                <?= portal_date($order['created_at']) ?>
                            </span>
                            <?php if (!empty($order['tracking_code'])): ?>
                                <span class="text-muted">
                                    <i class="fas fa-barcode me-1"></i>
                                    <?= e($order['tracking_code']) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">
                                    <i class="fas fa-clock me-1"></i>
                                    <?= __p('tracking_no_code') ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
