/**
 * Portal do Cliente — JavaScript
 * Akti - Gestão em Produção
 *
 * Funcionalidades:
 * - PWA Install prompt
 * - CSRF token para AJAX
 * - Helpers de UI
 * - Touch feedback
 */

(function () {
    'use strict';

    // ══════════════════════════════════════════════
    // CSRF TOKEN — Lê da meta tag para usar em AJAX
    // ══════════════════════════════════════════════
    const Portal = {
        csrfToken: function () {
            const meta = document.querySelector('meta[name="csrf-token"]');
            return meta ? meta.getAttribute('content') : '';
        },

        /**
         * Faz requisição AJAX POST.
         * @param {string} action - Action do portal
         * @param {Object} data - Dados do POST
         * @param {Function} callback - Callback de sucesso
         * @param {Function} errorCallback - Callback de erro
         */
        post: function (action, data, callback, errorCallback) {
            data = data || {};
            data.csrf_token = this.csrfToken();

            const formData = new FormData();
            for (const key in data) {
                if (data.hasOwnProperty(key)) {
                    formData.append(key, data[key]);
                }
            }

            fetch('?page=portal&action=' + action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData,
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (result) {
                    if (callback) callback(result);
                })
                .catch(function (error) {
                    console.error('Portal AJAX error:', error);
                    if (errorCallback) errorCallback(error);
                });
        },

        /**
         * Faz requisição AJAX GET.
         * @param {string} action - Action do portal
         * @param {Object} params - Query params extras
         * @param {Function} callback - Callback de sucesso
         */
        get: function (action, params, callback) {
            let url = '?page=portal&action=' + action;
            if (params) {
                for (const key in params) {
                    if (params.hasOwnProperty(key)) {
                        url += '&' + encodeURIComponent(key) + '=' + encodeURIComponent(params[key]);
                    }
                }
            }

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (result) {
                    if (callback) callback(result);
                })
                .catch(function (error) {
                    console.error('Portal AJAX error:', error);
                });
        },

        /**
         * Exibe mensagem toast simples.
         * @param {string} message
         * @param {string} type - 'success', 'error', 'warning', 'info'
         */
        toast: function (message, type) {
            type = type || 'info';
            const colors = {
                success: '#198754',
                error: '#dc3545',
                warning: '#ffc107',
                info: '#667eea',
            };

            const toast = document.createElement('div');
            toast.style.cssText =
                'position:fixed;top:16px;left:50%;transform:translateX(-50%);z-index:9999;' +
                'padding:12px 20px;border-radius:12px;color:#fff;font-size:0.9rem;font-weight:500;' +
                'box-shadow:0 4px 20px rgba(0,0,0,0.15);opacity:0;transition:opacity 0.3s;' +
                'max-width:calc(100% - 32px);text-align:center;background:' +
                (colors[type] || colors.info);

            toast.textContent = message;
            document.body.appendChild(toast);

            requestAnimationFrame(function () {
                toast.style.opacity = '1';
            });

            setTimeout(function () {
                toast.style.opacity = '0';
                setTimeout(function () {
                    toast.remove();
                }, 300);
            }, 3000);
        },
    };

    // Expor globalmente
    window.Portal = Portal;

    // ══════════════════════════════════════════════
    // PWA INSTALL PROMPT
    // ══════════════════════════════════════════════
    let deferredPrompt = null;

    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        deferredPrompt = e;

        // Verificar se já foi descartado recentemente
        const dismissed = localStorage.getItem('portal_pwa_dismissed');
        if (dismissed) {
            const dismissedAt = parseInt(dismissed, 10);
            // Não mostrar por 7 dias
            if (Date.now() - dismissedAt < 7 * 24 * 60 * 60 * 1000) {
                return;
            }
        }

        // Mostrar banner
        const banner = document.getElementById('portalPwaBanner');
        if (banner) {
            banner.style.display = 'block';
        }
    });

    // Botão de instalar
    document.addEventListener('click', function (e) {
        if (e.target.id === 'portalPwaInstallBtn' || e.target.closest('#portalPwaInstallBtn')) {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then(function (choiceResult) {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('PWA installed');
                    }
                    deferredPrompt = null;
                    const banner = document.getElementById('portalPwaBanner');
                    if (banner) banner.style.display = 'none';
                });
            }
        }
    });

    // Botão de dispensar
    document.addEventListener('click', function (e) {
        if (e.target.id === 'portalPwaDismissBtn' || e.target.closest('#portalPwaDismissBtn')) {
            const banner = document.getElementById('portalPwaBanner');
            if (banner) banner.style.display = 'none';
            localStorage.setItem('portal_pwa_dismissed', Date.now().toString());
        }
    });

    // ══════════════════════════════════════════════
    // TOUCH FEEDBACK — Adiciona ripple em cards
    // ══════════════════════════════════════════════
    document.addEventListener('touchstart', function () {}, { passive: true });

    // ══════════════════════════════════════════════
    // FORM LOADING STATE
    // ══════════════════════════════════════════════
    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!form.classList.contains('portal-auth-form') && !form.classList.contains('portal-form')) {
            return;
        }

        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Aguarde...';
            // Restaurar após 5s (fallback)
            setTimeout(function () {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }, 5000);
        }
    });

    // ══════════════════════════════════════════════
    // AUTO-HIDE ALERTS — Remove alertas após 5s
    // ══════════════════════════════════════════════
    document.addEventListener('DOMContentLoaded', function () {
        const alerts = document.querySelectorAll('.alert-sm');
        alerts.forEach(function (alert) {
            setTimeout(function () {
                alert.style.transition = 'opacity 0.3s';
                alert.style.opacity = '0';
                setTimeout(function () {
                    alert.remove();
                }, 300);
            }, 5000);
        });
    });
})();
