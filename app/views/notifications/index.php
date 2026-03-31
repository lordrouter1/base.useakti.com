<?php
/**
 * Notificações — Página de listagem de notificações do usuário
 *
 * Variáveis disponíveis (carregadas pelo NotificationController::index):
 *   $notifications  — array de notificações
 *   $unreadCount    — total de não-lidas
 */

$typeIcons = [
    'order_delayed'    => ['icon' => 'fas fa-clock',               'color' => '#e74c3c', 'label' => 'Pedido Atrasado'],
    'payment_received' => ['icon' => 'fas fa-dollar-sign',         'color' => '#27ae60', 'label' => 'Pagamento Recebido'],
    'stock_low'        => ['icon' => 'fas fa-exclamation-triangle', 'color' => '#f39c12', 'label' => 'Estoque Baixo'],
    'new_order'        => ['icon' => 'fas fa-shopping-cart',       'color' => '#3498db', 'label' => 'Novo Pedido'],
    'system'           => ['icon' => 'fas fa-cog',                 'color' => '#8e44ad', 'label' => 'Sistema'],
    'custom'           => ['icon' => 'fas fa-bell',                'color' => '#1abc9c', 'label' => 'Notificação'],
];
?>

<div class="container-fluid py-3">

    <!-- Header -->
    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div>
            <h1 class="h2 mb-1"><i class="fas fa-bell me-2 text-primary"></i>Notificações</h1>
            <p class="text-muted mb-0" style="font-size:.82rem;">
                Todas as suas notificações do sistema.
                <?php if ($unreadCount > 0): ?>
                <span class="badge bg-danger ms-1"><?= $unreadCount ?> não lida(s)</span>
                <?php endif; ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <?php if ($unreadCount > 0): ?>
            <button type="button" class="btn btn-sm btn-outline-primary" id="btnMarkAllRead">
                <i class="fas fa-check-double me-1"></i>Marcar todas como lidas
            </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($notifications)): ?>
    <?php
    $emptyState = [
        'icon'    => 'no-notifications',
        'title'   => 'Nenhuma notificação',
        'message' => 'Quando houver novidades, elas aparecerão aqui.',
    ];
    require 'app/views/components/empty-state.php';
    ?>
    <?php else: ?>

    <!-- Filter tabs -->
    <ul class="nav nav-pills mb-3 gap-1" id="notifFilterTabs">
        <li class="nav-item">
            <button class="nav-link active btn-sm" data-filter="all">
                Todas <span class="badge bg-secondary ms-1"><?= count($notifications) ?></span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link btn-sm" data-filter="unread">
                Não lidas <span class="badge bg-danger ms-1"><?= $unreadCount ?></span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link btn-sm" data-filter="read">
                Lidas <span class="badge bg-secondary ms-1"><?= count($notifications) - $unreadCount ?></span>
            </button>
        </li>
    </ul>

    <!-- Notification list -->
    <div class="card border-0 shadow-sm">
        <div class="list-group list-group-flush" id="notificationsList">
            <?php foreach ($notifications as $notif):
                $typeInfo = $typeIcons[$notif['type']] ?? $typeIcons['custom'];
                $isUnread = empty($notif['read_at']);
                $timeAgo = '';
                if (!empty($notif['created_at'])) {
                    $diff = time() - strtotime($notif['created_at']);
                    if ($diff < 60) $timeAgo = 'agora';
                    elseif ($diff < 3600) $timeAgo = floor($diff / 60) . 'min atrás';
                    elseif ($diff < 86400) $timeAgo = floor($diff / 3600) . 'h atrás';
                    else $timeAgo = floor($diff / 86400) . 'd atrás';
                }
                $linkUrl = '#';
                if (!empty($notif['data']['url'])) {
                    $linkUrl = $notif['data']['url'];
                } elseif ($notif['type'] === 'new_order' && !empty($notif['data']['order_id'])) {
                    $linkUrl = '?page=pipeline&action=detail&id=' . (int)$notif['data']['order_id'];
                }
            ?>
            <div class="list-group-item list-group-item-action notif-item <?= $isUnread ? 'notif-unread' : '' ?>"
                 data-id="<?= (int)$notif['id'] ?>" data-read="<?= $isUnread ? '0' : '1' ?>"
                 style="<?= $isUnread ? 'background:rgba(52,152,219,.04);border-left:3px solid ' . $typeInfo['color'] . ';' : '' ?>">
                <div class="d-flex align-items-start gap-3">
                    <div class="flex-shrink-0 mt-1">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle"
                              style="width:36px;height:36px;background:<?= $typeInfo['color'] ?>15;">
                            <i class="<?= $typeInfo['icon'] ?>" style="color:<?= $typeInfo['color'] ?>;font-size:.85rem;"></i>
                        </span>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-0" style="font-size:.88rem;<?= $isUnread ? 'font-weight:700;' : 'font-weight:500;' ?>">
                                    <?= e($notif['title']) ?>
                                </h6>
                                <?php if (!empty($notif['message'])): ?>
                                <p class="mb-0 text-muted" style="font-size:.78rem;"><?= e($notif['message']) ?></p>
                                <?php endif; ?>
                                <small class="text-muted" style="font-size:.68rem;">
                                    <span class="badge" style="background:<?= $typeInfo['color'] ?>20;color:<?= $typeInfo['color'] ?>;font-size:.6rem;">
                                        <?= $typeInfo['label'] ?>
                                    </span>
                                    <i class="fas fa-clock ms-1 me-1"></i><?= $timeAgo ?>
                                </small>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <?php if ($isUnread): ?>
                                <button type="button" class="btn btn-sm btn-link text-primary p-0 btn-mark-read"
                                        data-id="<?= (int)$notif['id'] ?>" title="Marcar como lida">
                                    <i class="fas fa-check"></i>
                                </button>
                                <?php endif; ?>
                                <?php if ($linkUrl !== '#'): ?>
                                <a href="<?= e($linkUrl) ?>" class="btn btn-sm btn-link text-muted p-0" title="Ver detalhes">
                                    <i class="fas fa-arrow-right"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php endif; ?>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    // Mark single notification as read
    document.querySelectorAll('.btn-mark-read').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var id = this.dataset.id;
            var item = this.closest('.notif-item');
            fetch('?page=notifications&action=markRead&id=' + id, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    item.classList.remove('notif-unread');
                    item.style.background = '';
                    item.style.borderLeft = '';
                    item.dataset.read = '1';
                    btn.remove();
                    if (window.AktiToast) AktiToast.success('Notificação marcada como lida.');
                }
            });
        });
    });

    // Mark all as read
    var btnAll = document.getElementById('btnMarkAllRead');
    if (btnAll) {
        btnAll.addEventListener('click', function() {
            fetch('?page=notifications&action=markAllRead', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    document.querySelectorAll('.notif-unread').forEach(function(el) {
                        el.classList.remove('notif-unread');
                        el.style.background = '';
                        el.style.borderLeft = '';
                        el.dataset.read = '1';
                    });
                    document.querySelectorAll('.btn-mark-read').forEach(function(b) { b.remove(); });
                    btnAll.remove();
                    if (window.AktiToast) AktiToast.success('Todas marcadas como lidas!');
                }
            });
        });
    }

    // Filter tabs
    document.querySelectorAll('#notifFilterTabs .nav-link').forEach(function(tab) {
        tab.addEventListener('click', function() {
            document.querySelectorAll('#notifFilterTabs .nav-link').forEach(function(t) { t.classList.remove('active'); });
            this.classList.add('active');

            var filter = this.dataset.filter;
            document.querySelectorAll('.notif-item').forEach(function(item) {
                if (filter === 'all') {
                    item.style.display = '';
                } else if (filter === 'unread') {
                    item.style.display = item.dataset.read === '0' ? '' : 'none';
                } else {
                    item.style.display = item.dataset.read === '1' ? '' : 'none';
                }
            });
        });
    });
});
</script>
