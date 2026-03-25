<?php
/**
 * Portal do Cliente — Financeiro (Listagem de Parcelas)
 *
 * Variáveis: $installments, $filter, $page, $totalPages, $summary,
 *            $countAll, $countOpen, $countPaid, $company
 */
?>

<div class="portal-page">
    <!-- ═══ Header ═══ -->
    <div class="portal-page-header">
        <h1 class="portal-page-title">
            <i class="fas fa-wallet me-2"></i>
            <?= __p('financial_title') ?>
        </h1>
    </div>

    <!-- ═══ Resumo Financeiro ═══ -->
    <div class="portal-stats-grid portal-stats-grid-2">
        <div class="portal-stat-card portal-stat-danger">
            <div class="portal-stat-number"><?= portal_money($summary['total_open']) ?></div>
            <div class="portal-stat-label"><?= __p('financial_open') ?></div>
            <i class="portal-stat-icon fas fa-exclamation-circle"></i>
        </div>
        <div class="portal-stat-card portal-stat-success">
            <div class="portal-stat-number"><?= portal_money($summary['total_paid']) ?></div>
            <div class="portal-stat-label"><?= __p('financial_paid') ?></div>
            <i class="portal-stat-icon fas fa-check-circle"></i>
        </div>
    </div>

    <?php if ($summary['count_overdue'] > 0): ?>
        <div class="alert alert-danger alert-sm">
            <i class="fas fa-exclamation-triangle me-1"></i>
            <?= __p('financial_overdue_alert', ['count' => $summary['count_overdue']]) ?>
        </div>
    <?php endif; ?>

    <!-- ═══ Tabs de Filtro ═══ -->
    <div class="portal-tab-filter">
        <a href="?page=portal&action=installments&filter=all"
           class="portal-tab-item <?= $filter === 'all' ? 'active' : '' ?>">
            <?= __p('financial_tab_all') ?>
            <span class="portal-tab-badge"><?= (int) $countAll ?></span>
        </a>
        <a href="?page=portal&action=installments&filter=open"
           class="portal-tab-item <?= $filter === 'open' ? 'active' : '' ?>">
            <?= __p('financial_tab_open') ?>
            <?php if ($countOpen > 0): ?>
                <span class="portal-tab-badge portal-tab-badge-danger"><?= (int) $countOpen ?></span>
            <?php else: ?>
                <span class="portal-tab-badge"><?= (int) $countOpen ?></span>
            <?php endif; ?>
        </a>
        <a href="?page=portal&action=installments&filter=paid"
           class="portal-tab-item <?= $filter === 'paid' ? 'active' : '' ?>">
            <?= __p('financial_tab_paid') ?>
            <span class="portal-tab-badge"><?= (int) $countPaid ?></span>
        </a>
    </div>

    <!-- ═══ Lista de Parcelas ═══ -->
    <?php if (empty($installments)): ?>
        <div class="portal-empty-state">
            <i class="fas fa-wallet"></i>
            <p><?= __p('financial_empty') ?></p>
        </div>
    <?php else: ?>
        <div class="portal-installment-list">
            <?php foreach ($installments as $inst): ?>
                <?php
                    $isOverdue  = ($inst['status'] === 'atrasado');
                    $isPaid     = ($inst['status'] === 'pago');
                    $isPending  = ($inst['status'] === 'pendente');
                    $statusClass = $isPaid ? 'success' : ($isOverdue ? 'danger' : 'warning');
                ?>
                <a href="?page=portal&action=installmentDetail&id=<?= (int) $inst['id'] ?>"
                   class="portal-installment-card <?= $isOverdue ? 'portal-installment-overdue' : '' ?>">
                    <div class="portal-installment-header">
                        <div>
                            <span class="portal-installment-label">
                                <?= __p('order_installment_number', ['n' => (int) $inst['installment_number']]) ?>
                            </span>
                            <span class="text-muted"> — <?= __p('order_detail_title', ['id' => (int) $inst['order_id']]) ?></span>
                        </div>
                        <span class="badge bg-<?= $statusClass ?>">
                            <?= __p('financial_' . $inst['status']) ?>
                        </span>
                    </div>
                    <div class="portal-installment-body">
                        <div class="portal-installment-amount">
                            <?= portal_money($inst['amount']) ?>
                        </div>
                        <div class="portal-installment-date">
                            <?php if ($isPaid): ?>
                                <i class="fas fa-check me-1"></i>
                                <?= __p('financial_paid_at', ['date' => portal_date($inst['paid_date'])]) ?>
                            <?php else: ?>
                                <i class="fas fa-calendar me-1"></i>
                                <?= __p('financial_due_date', ['date' => portal_date($inst['due_date'])]) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- ═══ Paginação ═══ -->
        <?php if ($totalPages > 1): ?>
            <nav class="portal-pagination mt-3">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=portal&action=installments&filter=<?= $filter ?>&p=<?= $i ?>"
                       class="portal-pagination-item <?= $i === $page ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>
