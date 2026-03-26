<?php
/**
 * Portal do Cliente — Dashboard (Home)
 *
 * Variáveis: $customerName, $stats, $recentOrders, $notifications,
 *            $unreadMessages, $trackingCount, $documentsCount, $company
 */
$unreadMessages = $unreadMessages ?? 0;
$trackingCount  = $trackingCount ?? 0;
$documentsCount = $documentsCount ?? 0;
?>

<div class="portal-page" id="portalDashboard">
    <!-- ═══ Saudação ═══ -->
    <div class="portal-greeting">
        <h2 class="portal-greeting-text">
            <i class="fas fa-hand-sparkles me-2"></i>
            <?= __p('dashboard_greeting', ['name' => e($customerName)]) ?>
        </h2>
        <p class="portal-greeting-sub"><?= e($company['company_name'] ?? '') ?></p>
    </div>

    <!-- ═══ Skeleton Loading (shown initially, hidden by JS) ═══ -->
    <div id="dashboardSkeleton" data-skeleton-for="dashboardContent" style="display:none">
        <div class="portal-stats-grid">
            <?php for ($i = 0; $i < 4; $i++): ?>
                <div class="portal-skeleton-stat-card">
                    <div class="portal-skeleton portal-skeleton-number"></div>
                    <div class="portal-skeleton portal-skeleton-label"></div>
                </div>
            <?php endfor; ?>
        </div>
        <div class="mt-3">
            <?php for ($i = 0; $i < 3; $i++): ?>
                <div class="portal-skeleton-order-card">
                    <div class="portal-skeleton-row">
                        <div class="portal-skeleton portal-skeleton-text" style="width:40%"></div>
                        <div class="portal-skeleton portal-skeleton-badge"></div>
                    </div>
                    <div class="portal-skeleton-row">
                        <div class="portal-skeleton portal-skeleton-text-sm" style="width:50%"></div>
                        <div class="portal-skeleton portal-skeleton-text-sm" style="width:25%"></div>
                    </div>
                </div>
            <?php endfor; ?>
        </div>
    </div>

    <!-- ═══ Real Content ═══ -->
    <div id="dashboardContent">

    <!-- ═══ Cards de Estatísticas ═══ -->
    <div class="portal-stats-grid">
        <div class="portal-stat-card portal-stat-primary">
            <div class="portal-stat-number"><?= (int) $stats['active_orders'] ?></div>
            <div class="portal-stat-label"><?= __p('dashboard_active_orders') ?></div>
            <i class="portal-stat-icon fas fa-box"></i>
        </div>
        <div class="portal-stat-card portal-stat-warning">
            <div class="portal-stat-number"><?= (int) $stats['pending_approval'] ?></div>
            <div class="portal-stat-label"><?= __p('dashboard_pending_approval') ?></div>
            <i class="portal-stat-icon fas fa-clipboard-check"></i>
        </div>
        <div class="portal-stat-card portal-stat-danger">
            <div class="portal-stat-number"><?= (int) $stats['open_installments'] ?></div>
            <div class="portal-stat-label"><?= __p('dashboard_open_installments') ?></div>
            <i class="portal-stat-icon fas fa-wallet"></i>
        </div>
        <div class="portal-stat-card portal-stat-success">
            <div class="portal-stat-number"><?= portal_money($stats['total_open_amount']) ?></div>
            <div class="portal-stat-label"><?= __p('dashboard_open_amount') ?></div>
            <i class="portal-stat-icon fas fa-dollar-sign"></i>
        </div>
    </div>

    <!-- ═══ Atalhos Rápidos ═══ -->
    <div class="portal-quick-links">
        <a href="?page=portal&action=newOrder" class="portal-quick-link portal-quick-primary">
            <div class="portal-quick-icon"><i class="fas fa-circle-plus"></i></div>
            <span><?= __p('new_order_title') ?></span>
        </a>
        <a href="?page=portal&action=messages" class="portal-quick-link portal-quick-info">
            <div class="portal-quick-icon">
                <i class="fas fa-comments"></i>
                <?php if ($unreadMessages > 0): ?>
                    <span class="portal-quick-badge"><?= (int) $unreadMessages ?></span>
                <?php endif; ?>
            </div>
            <span><?= __p('messages_title') ?></span>
        </a>
        <a href="?page=portal&action=tracking" class="portal-quick-link portal-quick-success">
            <div class="portal-quick-icon"><i class="fas fa-truck"></i></div>
            <span><?= __p('tracking_title') ?></span>
            <?php if ($trackingCount > 0): ?>
                <small class="text-muted"><?= $trackingCount ?></small>
            <?php endif; ?>
        </a>
        <a href="?page=portal&action=documents" class="portal-quick-link portal-quick-secondary">
            <div class="portal-quick-icon"><i class="fas fa-file-alt"></i></div>
            <span><?= __p('documents_title') ?></span>
        </a>
    </div>

    <!-- ═══ Notificações Recentes ═══ -->
    <div class="portal-section">
        <div class="portal-section-header">
            <h3 class="portal-section-title">
                <i class="fas fa-bell me-2"></i>
                <?= __p('dashboard_recent_notifications') ?>
            </h3>
        </div>

        <?php if (empty($notifications)): ?>
            <div class="portal-empty-state portal-empty-sm">
                <i class="fas fa-check-circle"></i>
                <p><?= __p('dashboard_no_notifications') ?></p>
            </div>
        <?php else: ?>
            <div class="portal-notification-list">
                <?php foreach ($notifications as $notif): ?>
                    <a href="<?= eAttr($notif['link']) ?>" class="portal-notification-item">
                        <div class="portal-notification-icon bg-<?= e($notif['color']) ?>">
                            <i class="<?= e($notif['icon']) ?>"></i>
                        </div>
                        <div class="portal-notification-content">
                            <p class="portal-notification-text"><?= e($notif['message']) ?></p>
                            <small class="portal-notification-date"><?= portal_date($notif['date']) ?></small>
                        </div>
                        <i class="fas fa-chevron-right portal-notification-arrow"></i>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- ═══ Pedidos Recentes ═══ -->
    <div class="portal-section">
        <div class="portal-section-header">
            <h3 class="portal-section-title">
                <i class="fas fa-box me-2"></i>
                <?= __p('dashboard_recent_orders') ?>
            </h3>
            <a href="?page=portal&action=orders" class="portal-section-link">
                <?= __p('dashboard_view_all') ?> <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>

        <?php if (empty($recentOrders)): ?>
            <div class="portal-empty-state portal-empty-sm">
                <i class="fas fa-box-open"></i>
                <p><?= __p('dashboard_no_orders') ?></p>
            </div>
        <?php else: ?>
            <div class="portal-order-list">
                <?php foreach ($recentOrders as $order): ?>
                    <a href="?page=portal&action=orderDetail&id=<?= (int) $order['id'] ?>" class="portal-order-card">
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
                                <?= portal_money($order['total'] ?? 0) ?>
                            </span>
                        </div>
                        <?php if (!empty($order['customer_approval_status']) && $order['customer_approval_status'] === 'pendente'): ?>
                            <div class="portal-order-card-footer">
                                <span class="badge bg-warning text-dark">
                                    <i class="fas fa-clock me-1"></i>
                                    <?= __p('approval_status_pendente') ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    </div><!-- /dashboardContent -->
</div>
