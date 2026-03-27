<?php
/**
 * NF-e Toast Notifications — Fase 6.5
 * Componente de notificações toast para feedback em tempo real.
 * 
 * Inclusão: include __DIR__ . '/partials/toast_notifications.php';
 * 
 * Usa SweetAlert2 para toasts.
 * Flash messages da sessão são automaticamente exibidas como toasts.
 */
?>

<!-- NF-e Toast Container -->
<div id="nfeToastContainer" class="position-fixed top-0 end-0 p-3" style="z-index: 11000;"></div>

<script>
(function(){
    'use strict';

    // ═══ Sistema de toasts NF-e ═══
    window.NfeToast = {
        success: function(msg, title) {
            this._show('success', msg, title || 'Sucesso');
        },
        error: function(msg, title) {
            this._show('error', msg, title || 'Erro');
        },
        warning: function(msg, title) {
            this._show('warning', msg, title || 'Atenção');
        },
        info: function(msg, title) {
            this._show('info', msg, title || 'Informação');
        },
        _show: function(icon, msg, title) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: icon,
                    title: title,
                    text: msg,
                    showConfirmButton: false,
                    timer: 4000,
                    timerProgressBar: true,
                    didOpen: function(toast) {
                        toast.addEventListener('mouseenter', Swal.stopTimer);
                        toast.addEventListener('mouseleave', Swal.resumeTimer);
                    }
                });
            } else {
                // Fallback Bootstrap toast
                var colorMap = { success: '#198754', error: '#dc3545', warning: '#ffc107', info: '#0dcaf0' };
                var iconMap = { success: 'check-circle', error: 'times-circle', warning: 'exclamation-triangle', info: 'info-circle' };
                var toastHtml = '<div class="toast show align-items-center text-white border-0 mb-2" role="alert" ' +
                    'style="background:' + (colorMap[icon] || '#6c757d') + ';min-width:300px;">' +
                    '<div class="d-flex">' +
                    '<div class="toast-body"><i class="fas fa-' + (iconMap[icon] || 'circle') + ' me-2"></i>' +
                    '<strong>' + title + '</strong> ' + msg + '</div>' +
                    '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>' +
                    '</div></div>';
                var container = document.getElementById('nfeToastContainer');
                if (container) {
                    container.insertAdjacentHTML('beforeend', toastHtml);
                    // Auto-remove after 4s
                    setTimeout(function(){
                        var first = container.querySelector('.toast');
                        if (first) first.remove();
                    }, 4000);
                }
            }
        }
    };

    // ═══ Auto-show flash messages from PHP session ═══
    <?php if (isset($_SESSION['flash_success'])): ?>
    document.addEventListener('DOMContentLoaded', function(){ NfeToast.success(<?= json_encode($_SESSION['flash_success']) ?>); });
    <?php unset($_SESSION['flash_success']); endif; ?>

    <?php if (isset($_SESSION['flash_error'])): ?>
    document.addEventListener('DOMContentLoaded', function(){ NfeToast.error(<?= json_encode($_SESSION['flash_error']) ?>); });
    <?php unset($_SESSION['flash_error']); endif; ?>

    <?php if (isset($_SESSION['flash_warning'])): ?>
    document.addEventListener('DOMContentLoaded', function(){ NfeToast.warning(<?= json_encode($_SESSION['flash_warning']) ?>); });
    <?php unset($_SESSION['flash_warning']); endif; ?>

    <?php if (isset($_SESSION['flash_info'])): ?>
    document.addEventListener('DOMContentLoaded', function(){ NfeToast.info(<?= json_encode($_SESSION['flash_info']) ?>); });
    <?php unset($_SESSION['flash_info']); endif; ?>

    // ═══ NF-e Status Polling (background check for async operations) ═══
    window.NfeStatusPoller = {
        interval: null,
        start: function(nfeId, callback, ms) {
            ms = ms || 3000;
            var self = this;
            this.interval = setInterval(function(){
                var xhr = new XMLHttpRequest();
                xhr.open('GET', '?page=nfe_documents&action=checkStatus&id=' + nfeId);
                xhr.setRequestHeader('Accept', 'application/json');
                xhr.onload = function(){
                    if (xhr.status === 200) {
                        try {
                            var resp = JSON.parse(xhr.responseText);
                            if (resp.status && resp.status !== 'processando') {
                                self.stop();
                                if (typeof callback === 'function') callback(resp);
                                if (resp.status === 'autorizada') {
                                    NfeToast.success('NF-e #' + (resp.numero || nfeId) + ' autorizada com sucesso!');
                                } else if (resp.status === 'rejeitada') {
                                    NfeToast.error('NF-e #' + (resp.numero || nfeId) + ' rejeitada: ' + (resp.motivo || ''));
                                }
                            }
                        } catch(e) {}
                    }
                };
                xhr.send();
            }, ms);
        },
        stop: function() {
            if (this.interval) {
                clearInterval(this.interval);
                this.interval = null;
            }
        }
    };

})();
</script>
