<?php
/**
 * Portal do Cliente — Meus Pedidos (Listagem)
 *
 * Variáveis: $orders, $filter, $page, $totalPages, $totalCount,
 *            $countAll, $countOpen, $countApproval, $countDone, $company
 */
?>

<div class="portal-page">
    <!-- ═══ Header da Página ═══ -->
    <div class="portal-page-header">
        <h1 class="portal-page-title">
            <i class="fas fa-box me-2"></i>
            <?= __p('orders_title') ?>
        </h1>
    </div>

    <!-- ═══ Tabs de Filtro ═══ -->
    <div class="portal-tab-filter">
        <a href="?page=portal&action=orders&filter=all"
           class="portal-tab-item <?= $filter === 'all' ? 'active' : '' ?>">
            <?= __p('orders_all') ?>
            <span class="portal-tab-badge"><?= (int) $countAll ?></span>
        </a>
        <a href="?page=portal&action=orders&filter=open"
           class="portal-tab-item <?= $filter === 'open' ? 'active' : '' ?>">
            <?= __p('orders_open') ?>
            <span class="portal-tab-badge"><?= (int) $countOpen ?></span>
        </a>
        <a href="?page=portal&action=orders&filter=approval"
           class="portal-tab-item <?= $filter === 'approval' ? 'active' : '' ?>">
            <?= __p('orders_approval') ?>
            <?php if ($countApproval > 0): ?>
                <span class="portal-tab-badge portal-tab-badge-warning"><?= (int) $countApproval ?></span>
            <?php else: ?>
                <span class="portal-tab-badge"><?= (int) $countApproval ?></span>
            <?php endif; ?>
        </a>
        <a href="?page=portal&action=orders&filter=done"
           class="portal-tab-item <?= $filter === 'done' ? 'active' : '' ?>">
            <?= __p('orders_completed') ?>
            <span class="portal-tab-badge"><?= (int) $countDone ?></span>
        </a>
    </div>

    <!-- ═══ Lista de Pedidos ═══ -->
    <?php if (empty($orders)): ?>
        <div class="portal-empty-state portal-empty-sm">
            <i class="fas fa-box-open"></i>
            <p><?= __p('orders_empty') ?></p>
        </div>
    <?php else: ?>
        <div class="portal-order-list">
            <?php foreach ($orders as $order): ?>
                <?php
                    $detailUrl = '?page=portal&action=orderDetail&id=' . (int) $order['id'];
                    if ($filter === 'approval') {
                        $detailUrl .= '&context=approval';
                    }
                ?>
                <a href="<?= $detailUrl ?>" class="portal-order-card">
                    <div class="portal-order-card-header">
                        <span class="portal-order-id">#<?= (int) $order['id'] ?></span>
                        <span class="badge bg-<?= portal_stage_class($order['pipeline_stage'] ?? '') ?>">
                            <?= __p('status_' . ($order['pipeline_stage'] ?? 'orcamento')) ?>
                        </span>
                    </div>
                    <div class="portal-order-card-body">
                        <span class="portal-order-date">
                            <i class="fas fa-calendar-alt me-1"></i>
                            <?= portal_date($order['created_at']) ?>
                        </span>
                        <span class="portal-order-total">
                            <?= portal_money($order['total_amount'] ?? 0) ?>
                        </span>
                    </div>
                    <div class="portal-order-card-footer">
                        <span class="text-muted" style="font-size:0.8rem;">
                            <?= __p('orders_items', ['count' => (int) $order['items_count']]) ?>
                        </span>
                        <?php if (!empty($order['tracking_code'])): ?>
                            <span class="badge bg-info text-white" style="font-size:0.7rem;">
                                <i class="fas fa-truck me-1"></i>
                                <?= e($order['tracking_code']) ?>
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($order['customer_approval_status']) && $order['customer_approval_status'] === 'pendente'): ?>
                            <span class="badge bg-warning text-dark">
                                <i class="fas fa-clock me-1"></i>
                                <?= __p('approval_status_pendente') ?>
                            </span>
                        <?php elseif (!empty($order['customer_approval_status']) && $order['customer_approval_status'] === 'aprovado'): ?>
                            <span class="badge bg-success">
                                <i class="fas fa-check me-1"></i>
                                <?= __p('approval_status_aprovado') ?>
                            </span>
                        <?php elseif (!empty($order['customer_approval_status']) && $order['customer_approval_status'] === 'recusado'): ?>
                            <span class="badge bg-danger">
                                <i class="fas fa-times me-1"></i>
                                <?= __p('approval_status_recusado') ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- ═══ Paginação ═══ -->
        <?php if ($totalPages > 1): ?>
            <div class="portal-pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=portal&action=orders&filter=<?= eAttr($filter) ?>&p=<?= $page - 1 ?>"
                       class="portal-pagination-btn">
                        <i class="fas fa-chevron-left me-1"></i>
                        <?= __p('back') ?>
                    </a>
                <?php else: ?>
                    <span class="portal-pagination-btn disabled">
                        <i class="fas fa-chevron-left me-1"></i>
                        <?= __p('back') ?>
                    </span>
                <?php endif; ?>

                <span class="portal-pagination-info"><?= $page ?> / <?= $totalPages ?></span>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=portal&action=orders&filter=<?= eAttr($filter) ?>&p=<?= $page + 1 ?>"
                       class="portal-pagination-btn">
                        <?= __p('orders_next') ?>
                        <i class="fas fa-chevron-right ms-1"></i>
                    </a>
                <?php else: ?>
                    <span class="portal-pagination-btn disabled">
                        <?= __p('orders_next') ?>
                        <i class="fas fa-chevron-right ms-1"></i>
                    </span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
