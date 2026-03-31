/**
 * Akti — Notification Bell Dropdown
 * Real-time notification badge + dropdown with AJAX polling.
 *
 * - Polls ?page=notifications&action=count every 60s
 * - Loads notifications on dropdown open
 * - Mark as read inline
 */
(function() {
    'use strict';

    var POLL_INTERVAL = 60000; // 60 seconds
    var _badge = null;
    var _body  = null;
    var _container = null;
    var _loaded = false;
    var _delayedCount = 0; // server-side delayed orders count

    var typeIcons = {
        order_delayed:    { icon: 'fas fa-clock',                color: '#e74c3c' },
        payment_received: { icon: 'fas fa-dollar-sign',          color: '#27ae60' },
        stock_low:        { icon: 'fas fa-exclamation-triangle',  color: '#f39c12' },
        new_order:        { icon: 'fas fa-shopping-cart',         color: '#3498db' },
        system:           { icon: 'fas fa-cog',                   color: '#8e44ad' },
        custom:           { icon: 'fas fa-bell',                  color: '#1abc9c' },
    };

    function init() {
        _badge     = document.getElementById('notifBadge');
        _body      = document.getElementById('notifDropdownBody');
        _container = document.getElementById('notifBellContainer');

        if (!_badge || !_body) return;

        // Read server-side delayed orders count from badge data attribute
        _delayedCount = parseInt(_badge.getAttribute('data-delayed-count') || '0', 10);

        // Poll count immediately + interval
        pollCount();
        setInterval(pollCount, POLL_INTERVAL);

        // Load notifications when dropdown opens
        var toggle = document.getElementById('notifBellToggle');
        if (toggle) {
            toggle.addEventListener('click', function() {
                if (!_loaded) loadNotifications();
            });
        }

        // Mark all as read
        var markAll = document.getElementById('notifMarkAllRead');
        if (markAll) {
            markAll.addEventListener('click', function(e) {
                e.preventDefault();
                fetch('?page=notifications&action=markAllRead', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success) {
                        updateBadge(_delayedCount);
                        _loaded = false;
                        loadNotifications();
                    }
                });
            });
        }
    }

    function pollCount() {
        fetch('?page=notifications&action=count', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                // Combine system notification count + server-side delayed orders count
                var totalCount = (data.count || 0) + _delayedCount;
                updateBadge(totalCount);
            }
        })
        .catch(function() {});
    }

    function updateBadge(count) {
        if (!_badge) return;
        if (count > 0) {
            _badge.textContent = count > 99 ? '99+' : count;
            _badge.style.display = '';
        } else {
            _badge.style.display = 'none';
        }
    }

    function loadNotifications() {
        _loaded = true;
        _body.innerHTML = '<div class="text-center text-muted py-3" style="font-size:.82rem;"><i class="fas fa-spinner fa-spin me-1"></i>Carregando...</div>';

        fetch('?page=notifications&limit=10', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success || !data.notifications || !data.notifications.length) {
                _body.innerHTML = '<div class="text-center text-muted py-4" style="font-size:.82rem;">' +
                    '<i class="far fa-bell-slash fa-2x mb-2 d-block" style="opacity:.3;"></i>' +
                    'Nenhuma notificação</div>';
                return;
            }

            var html = '';
            data.notifications.forEach(function(n) {
                var ti = typeIcons[n.type] || typeIcons.custom;
                var isUnread = !n.read_at;
                var timeAgo = formatTimeAgo(n.created_at);
                var url = '#';
                if (n.data && n.data.url) url = n.data.url;
                else if (n.type === 'new_order' && n.data && n.data.order_id) url = '?page=pipeline&action=detail&id=' + n.data.order_id;

                html += '<a href="' + url + '" class="dropdown-item px-3 py-2 border-bottom" ' +
                    'style="white-space:normal;' + (isUnread ? 'background:rgba(52,152,219,.04);border-left:3px solid ' + ti.color + ';' : '') + '">' +
                    '<div class="d-flex align-items-start gap-2">' +
                    '<span class="d-inline-flex align-items-center justify-content-center rounded-circle mt-1" ' +
                    'style="width:28px;height:28px;min-width:28px;background:' + ti.color + '15;">' +
                    '<i class="' + ti.icon + '" style="color:' + ti.color + ';font-size:.7rem;"></i></span>' +
                    '<div>' +
                    '<div style="font-size:.82rem;' + (isUnread ? 'font-weight:700;' : '') + 'color:#333;">' + escHtml(n.title) + '</div>' +
                    (n.message ? '<div style="font-size:.72rem;color:#6c757d;">' + escHtml(n.message).substring(0, 80) + '</div>' : '') +
                    '<div style="font-size:.65rem;color:#aaa;"><i class="fas fa-clock me-1"></i>' + timeAgo + '</div>' +
                    '</div></div></a>';
            });

            _body.innerHTML = html;
            // Combine system unread count + server-side delayed count
            updateBadge((data.unread_count || 0) + _delayedCount);
        })
        .catch(function() {
            _body.innerHTML = '<div class="text-center text-danger py-3" style="font-size:.82rem;">Erro ao carregar.</div>';
        });
    }

    function formatTimeAgo(dateStr) {
        if (!dateStr) return '';
        var diff = Math.floor((Date.now() - new Date(dateStr).getTime()) / 1000);
        if (diff < 60)    return 'agora';
        if (diff < 3600)  return Math.floor(diff / 60) + 'min atrás';
        if (diff < 86400) return Math.floor(diff / 3600) + 'h atrás';
        return Math.floor(diff / 86400) + 'd atrás';
    }

    function escHtml(s) {
        var d = document.createElement('div');
        d.textContent = s || '';
        return d.innerHTML;
    }

    // Init on DOMContentLoaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    window.AktiNotifications = {
        refresh: function() { _loaded = false; pollCount(); },
    };
})();
