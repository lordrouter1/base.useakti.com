/**
 * Akti — Toast Notification System
 * Replaces simple SweetAlert2 confirmations with lightweight toasts.
 * SweetAlert2 is kept ONLY for destructive confirmations.
 *
 * Usage:
 *   AktiToast.success('Registro salvo com sucesso!');
 *   AktiToast.error('Falha ao processar.');
 *   AktiToast.warning('Atenção: limite quase atingido.');
 *   AktiToast.info('Nova versão disponível.');
 */
(function() {
    'use strict';

    var CONTAINER_ID = 'akti-toast-container';
    var Z_INDEX = 1080;

    var DEFAULTS = {
        success: { icon: 'fas fa-check-circle', duration: 3000, color: 'var(--success)' },
        error:   { icon: 'fas fa-times-circle',  duration: 5000, color: 'var(--danger)' },
        warning: { icon: 'fas fa-exclamation-triangle', duration: 4000, color: 'var(--warning)' },
        info:    { icon: 'fas fa-info-circle',    duration: 3000, color: 'var(--accent)' },
    };

    function getContainer() {
        var el = document.getElementById(CONTAINER_ID);
        if (!el) {
            el = document.createElement('div');
            el.id = CONTAINER_ID;
            el.setAttribute('role', 'status');
            el.setAttribute('aria-live', 'polite');
            el.style.cssText = 'position:fixed;top:' + (parseInt(getComputedStyle(document.documentElement).getPropertyValue('--navbar-height') || '64', 10) + 12) + 'px;right:16px;z-index:' + Z_INDEX + ';display:flex;flex-direction:column;gap:8px;pointer-events:none;max-width:380px;width:100%;';
            document.body.appendChild(el);
        }
        return el;
    }

    function show(type, message, opts) {
        opts = opts || {};
        var cfg = DEFAULTS[type] || DEFAULTS.info;
        var dur = opts.duration || cfg.duration;

        var toast = document.createElement('div');
        toast.className = 'akti-toast akti-toast-' + type;
        toast.style.cssText = 'pointer-events:auto;display:flex;align-items:flex-start;gap:10px;padding:12px 16px;border-radius:var(--ds-radius-md, 8px);background:var(--bg-primary, #fff);border:1px solid var(--border, #dee2e6);box-shadow:var(--ds-shadow-lg, 0 10px 15px rgba(0,0,0,0.1));transform:translateX(110%);opacity:0;transition:transform 0.3s cubic-bezier(0.16,1,0.3,1),opacity 0.3s ease;font-size:0.875rem;color:var(--text-primary, #212529);line-height:1.5;max-width:100%;overflow:hidden;';

        var iconSpan = document.createElement('span');
        iconSpan.style.cssText = 'flex-shrink:0;font-size:1rem;margin-top:1px;color:' + cfg.color + ';';
        iconSpan.innerHTML = '<i class="' + cfg.icon + '"></i>';

        var textSpan = document.createElement('span');
        textSpan.style.cssText = 'flex:1;word-break:break-word;';
        textSpan.textContent = message;

        var closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.setAttribute('aria-label', 'Fechar');
        closeBtn.style.cssText = 'flex-shrink:0;background:none;border:none;cursor:pointer;color:var(--text-muted, #adb5bd);font-size:0.875rem;padding:0;margin-top:1px;line-height:1;';
        closeBtn.innerHTML = '<i class="fas fa-times"></i>';
        closeBtn.addEventListener('click', function() { dismiss(toast); });

        toast.appendChild(iconSpan);
        toast.appendChild(textSpan);
        toast.appendChild(closeBtn);

        // Left accent border
        toast.style.borderLeft = '3px solid ' + cfg.color;

        getContainer().appendChild(toast);

        // Trigger enter animation
        requestAnimationFrame(function() {
            requestAnimationFrame(function() {
                toast.style.transform = 'translateX(0)';
                toast.style.opacity = '1';
            });
        });

        // Auto-dismiss
        var timer = setTimeout(function() { dismiss(toast); }, dur);

        // Pause on hover
        toast.addEventListener('mouseenter', function() { clearTimeout(timer); });
        toast.addEventListener('mouseleave', function() {
            timer = setTimeout(function() { dismiss(toast); }, 1500);
        });
    }

    function dismiss(el) {
        if (!el || el._dismissing) return;
        el._dismissing = true;
        el.style.transform = 'translateX(110%)';
        el.style.opacity = '0';
        setTimeout(function() {
            if (el.parentNode) el.parentNode.removeChild(el);
        }, 350);
    }

    window.AktiToast = {
        success: function(msg, opts) { show('success', msg, opts); },
        error:   function(msg, opts) { show('error', msg, opts); },
        warning: function(msg, opts) { show('warning', msg, opts); },
        info:    function(msg, opts) { show('info', msg, opts); },
    };

    // ── Auto-show toasts from URL status params ──
    document.addEventListener('DOMContentLoaded', function() {
        var params = new URLSearchParams(window.location.search);
        var status = params.get('status');
        if (!status) return;

        var messages = {
            'saved':            { type: 'success', msg: 'Configurações salvas com sucesso!' },
            'created':          { type: 'success', msg: 'Registro criado com sucesso!' },
            'updated':          { type: 'success', msg: 'Registro atualizado com sucesso!' },
            'deleted':          { type: 'success', msg: 'Registro excluído com sucesso!' },
            'table_created':    { type: 'success', msg: 'Tabela de preço criada!' },
            'table_updated':    { type: 'success', msg: 'Tabela de preço atualizada!' },
            'table_deleted':    { type: 'success', msg: 'Tabela de preço excluída!' },
            'item_saved':       { type: 'success', msg: 'Item salvo com sucesso!' },
            'item_deleted':     { type: 'success', msg: 'Item removido!' },
            'step_added':       { type: 'success', msg: 'Etapa adicionada!' },
            'step_updated':     { type: 'success', msg: 'Etapa atualizada!' },
            'step_deleted':     { type: 'success', msg: 'Etapa removida!' },
            'error':            { type: 'error',   msg: 'Ocorreu um erro. Tente novamente.' },
            'limit_price_tables': { type: 'warning', msg: 'Limite de tabelas de preço atingido.' },
            'table_default_error': { type: 'error', msg: 'Não é possível excluir a tabela padrão.' },
        };

        var match = messages[status];
        if (match) {
            setTimeout(function() { AktiToast[match.type](match.msg); }, 300);
            // Clean URL without reload
            if (window.history.replaceState) {
                params.delete('status');
                var newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
                window.history.replaceState({}, '', newUrl);
            }
        }
    });

})();
