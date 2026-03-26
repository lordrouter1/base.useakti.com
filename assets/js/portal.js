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
                info: '#3b82f6',
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

        // Inicializar módulos Fase 7
        PortalMasks.init();
        PortalOffline.init();
        PortalPullToRefresh.init();
        PortalSkeleton.init();
    });

    // ══════════════════════════════════════════════
    // INPUT MASKS — Telefone, CPF, CNPJ (Fase 7)
    // ══════════════════════════════════════════════
    const PortalMasks = {
        init: function () {
            document.querySelectorAll('input[data-mask]').forEach(function (input) {
                input.classList.add('portal-input-masked');
                input.addEventListener('input', function () {
                    PortalMasks.apply(this, this.getAttribute('data-mask'));
                });
                // Aplicar máscara ao valor inicial se houver
                if (input.value) {
                    PortalMasks.apply(input, input.getAttribute('data-mask'));
                }
            });

            // Auto-detect phone fields
            document.querySelectorAll('input[type="tel"], input[name="phone"]').forEach(function (input) {
                if (!input.hasAttribute('data-mask')) {
                    input.classList.add('portal-input-masked');
                    input.addEventListener('input', function () {
                        PortalMasks.applyPhone(this);
                    });
                    if (input.value) {
                        PortalMasks.applyPhone(input);
                    }
                }
            });

            // Auto-detect document/CPF/CNPJ fields
            document.querySelectorAll('input[name="document"], input[data-mask="cpf_cnpj"]').forEach(function (input) {
                input.classList.add('portal-input-masked');
                input.addEventListener('input', function () {
                    PortalMasks.applyCpfCnpj(this);
                });
                if (input.value) {
                    PortalMasks.applyCpfCnpj(input);
                }
            });
        },

        apply: function (input, type) {
            switch (type) {
                case 'phone':
                    this.applyPhone(input);
                    break;
                case 'cpf':
                    this.applyCpf(input);
                    break;
                case 'cnpj':
                    this.applyCnpj(input);
                    break;
                case 'cpf_cnpj':
                    this.applyCpfCnpj(input);
                    break;
            }
        },

        applyPhone: function (input) {
            var v = input.value.replace(/\D/g, '');
            if (v.length > 11) v = v.substring(0, 11);
            if (v.length > 10) {
                // (XX) XXXXX-XXXX
                v = v.replace(/^(\d{2})(\d{5})(\d{4}).*/, '($1) $2-$3');
            } else if (v.length > 6) {
                // (XX) XXXX-XXXX
                v = v.replace(/^(\d{2})(\d{4})(\d{0,4}).*/, '($1) $2-$3');
            } else if (v.length > 2) {
                v = v.replace(/^(\d{2})(\d{0,5})/, '($1) $2');
            } else if (v.length > 0) {
                v = v.replace(/^(\d{0,2})/, '($1');
            }
            input.value = v;
        },

        applyCpf: function (input) {
            var v = input.value.replace(/\D/g, '');
            if (v.length > 11) v = v.substring(0, 11);
            v = v.replace(/(\d{3})(\d)/, '$1.$2');
            v = v.replace(/(\d{3})(\d)/, '$1.$2');
            v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            input.value = v;
        },

        applyCnpj: function (input) {
            var v = input.value.replace(/\D/g, '');
            if (v.length > 14) v = v.substring(0, 14);
            v = v.replace(/^(\d{2})(\d)/, '$1.$2');
            v = v.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
            v = v.replace(/\.(\d{3})(\d)/, '.$1/$2');
            v = v.replace(/(\d{4})(\d)/, '$1-$2');
            input.value = v;
        },

        applyCpfCnpj: function (input) {
            var v = input.value.replace(/\D/g, '');
            if (v.length <= 11) {
                this.applyCpf(input);
            } else {
                this.applyCnpj(input);
            }
        },
    };

    window.PortalMasks = PortalMasks;

    // ══════════════════════════════════════════════
    // OFFLINE INDICATOR (Fase 7)
    // ══════════════════════════════════════════════
    const PortalOffline = {
        bar: null,

        init: function () {
            // Criar barra de offline se não existir
            if (!document.getElementById('portalOfflineBar')) {
                var bar = document.createElement('div');
                bar.id = 'portalOfflineBar';
                bar.className = 'portal-offline-bar';
                bar.innerHTML = '<i class="fas fa-wifi-slash"></i> <span>Sem conexão — modo offline</span>';
                document.body.appendChild(bar);
            }
            this.bar = document.getElementById('portalOfflineBar');

            window.addEventListener('online', this.onOnline.bind(this));
            window.addEventListener('offline', this.onOffline.bind(this));

            // Checar estado atual
            if (!navigator.onLine) {
                this.onOffline();
            }
        },

        onOffline: function () {
            if (this.bar) {
                this.bar.classList.add('show');
                document.body.classList.add('is-offline');
            }
            Portal.toast('Você está offline. Algumas funções podem não estar disponíveis.', 'warning');
        },

        onOnline: function () {
            if (this.bar) {
                this.bar.classList.remove('show');
                document.body.classList.remove('is-offline');
            }
            Portal.toast('Conexão restaurada!', 'success');
        },
    };

    window.PortalOffline = PortalOffline;

    // ══════════════════════════════════════════════
    // PULL TO REFRESH (Fase 7) — Mobile only
    // ══════════════════════════════════════════════
    const PortalPullToRefresh = {
        startY: 0,
        pulling: false,
        threshold: 80,
        indicator: null,

        init: function () {
            // Só ativar em touch devices
            if (!('ontouchstart' in window)) return;

            // Criar indicador
            if (!document.getElementById('portalPtrIndicator')) {
                var ind = document.createElement('div');
                ind.id = 'portalPtrIndicator';
                ind.className = 'portal-ptr-indicator';
                ind.innerHTML = '<i class="fas fa-arrow-down"></i>';
                document.body.appendChild(ind);
            }
            this.indicator = document.getElementById('portalPtrIndicator');

            var self = this;
            document.addEventListener('touchstart', function (e) {
                self.onTouchStart(e);
            }, { passive: true });
            document.addEventListener('touchmove', function (e) {
                self.onTouchMove(e);
            }, { passive: false });
            document.addEventListener('touchend', function (e) {
                self.onTouchEnd(e);
            }, { passive: true });
        },

        onTouchStart: function (e) {
            if (window.scrollY === 0 && e.touches.length === 1) {
                this.startY = e.touches[0].clientY;
                this.pulling = true;
            }
        },

        onTouchMove: function (e) {
            if (!this.pulling) return;
            var currentY = e.touches[0].clientY;
            var diff = currentY - this.startY;

            if (diff > 10 && window.scrollY === 0) {
                if (diff > this.threshold) {
                    this.indicator.classList.add('pulling');
                } else {
                    this.indicator.classList.remove('pulling');
                }
                // Prevent native scroll when pulling
                if (diff > 20) {
                    e.preventDefault();
                }
            } else {
                this.pulling = false;
                this.indicator.classList.remove('pulling');
            }
        },

        onTouchEnd: function () {
            if (!this.pulling) return;
            var indicator = this.indicator;

            if (indicator.classList.contains('pulling')) {
                indicator.classList.remove('pulling');
                indicator.classList.add('refreshing');
                indicator.innerHTML = '<i class="fas fa-spinner"></i>';

                setTimeout(function () {
                    window.location.reload();
                }, 500);
            }

            this.pulling = false;
        },
    };

    window.PortalPullToRefresh = PortalPullToRefresh;

    // ══════════════════════════════════════════════
    // SKELETON LOADING HELPERS (Fase 7)
    // ══════════════════════════════════════════════
    const PortalSkeleton = {
        init: function () {
            // Auto-remove skeleton containers once real content is loaded
            var skeletons = document.querySelectorAll('[data-skeleton-for]');
            skeletons.forEach(function (skeleton) {
                var targetId = skeleton.getAttribute('data-skeleton-for');
                var target = document.getElementById(targetId);
                if (target) {
                    skeleton.style.display = 'none';
                    target.style.display = '';
                }
            });
        },

        /**
         * Gera HTML de skeleton para stat cards
         * @param {number} count Número de cards
         * @returns {string} HTML
         */
        statCards: function (count) {
            count = count || 4;
            var html = '<div class="portal-stats-grid">';
            for (var i = 0; i < count; i++) {
                html += '<div class="portal-skeleton-stat-card">';
                html += '<div class="portal-skeleton portal-skeleton-number"></div>';
                html += '<div class="portal-skeleton portal-skeleton-label"></div>';
                html += '</div>';
            }
            html += '</div>';
            return html;
        },

        /**
         * Gera HTML de skeleton para order cards
         * @param {number} count
         * @returns {string} HTML
         */
        orderCards: function (count) {
            count = count || 3;
            var html = '';
            for (var i = 0; i < count; i++) {
                html += '<div class="portal-skeleton-order-card">';
                html += '<div class="portal-skeleton-row">';
                html += '<div class="portal-skeleton portal-skeleton-text" style="width:40%"></div>';
                html += '<div class="portal-skeleton portal-skeleton-badge"></div>';
                html += '</div>';
                html += '<div class="portal-skeleton-row">';
                html += '<div class="portal-skeleton portal-skeleton-text-sm" style="width:50%"></div>';
                html += '<div class="portal-skeleton portal-skeleton-text-sm" style="width:25%"></div>';
                html += '</div>';
                html += '</div>';
            }
            return html;
        },
    };

    window.PortalSkeleton = PortalSkeleton;

    // ══════════════════════════════════════════════
    // AVATAR UPLOAD PREVIEW (Fase 7)
    // ══════════════════════════════════════════════
    document.addEventListener('change', function (e) {
        if (e.target.id === 'portalAvatarInput') {
            var file = e.target.files[0];
            if (!file) return;

            // Validar tipo
            var allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            if (allowedTypes.indexOf(file.type) === -1) {
                Portal.toast('Formato inválido. Use JPG, PNG ou WebP.', 'error');
                return;
            }

            // Validar tamanho (2MB)
            if (file.size > 2 * 1024 * 1024) {
                Portal.toast('Imagem muito grande. Máximo 2MB.', 'error');
                return;
            }

            // Preview
            var reader = new FileReader();
            reader.onload = function (ev) {
                var preview = document.getElementById('portalAvatarPreview');
                if (preview) {
                    preview.src = ev.target.result;
                    preview.style.display = 'block';
                    // Esconder placeholder
                    var placeholder = document.getElementById('portalAvatarPlaceholder');
                    if (placeholder) placeholder.style.display = 'none';
                }
            };
            reader.readAsDataURL(file);

            // Auto-submit via AJAX
            var formData = new FormData();
            formData.append('avatar', file);
            formData.append('csrf_token', Portal.csrfToken());

            fetch('?page=portal&action=uploadAvatar', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': Portal.csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData,
            })
                .then(function (r) { return r.json(); })
                .then(function (result) {
                    if (result.success) {
                        Portal.toast(result.message, 'success');
                    } else {
                        Portal.toast(result.message || 'Erro ao enviar avatar.', 'error');
                    }
                })
                .catch(function () {
                    Portal.toast('Erro ao enviar avatar.', 'error');
                });
        }
    });

    // ══════════════════════════════════════════════
    // 2FA TOGGLE (Fase 7)
    // ══════════════════════════════════════════════
    document.addEventListener('change', function (e) {
        if (e.target.id === 'portal2faToggle') {
            var enable = e.target.checked ? '1' : '0';
            Portal.post('toggle2fa', { enable: enable }, function (result) {
                if (result.success) {
                    Portal.toast(result.message, 'success');
                    // Atualizar badge
                    var badge = document.getElementById('portal2faStatus');
                    if (badge) {
                        if (enable === '1') {
                            badge.className = 'portal-2fa-status portal-2fa-status-on';
                            badge.textContent = 'Ativo';
                        } else {
                            badge.className = 'portal-2fa-status portal-2fa-status-off';
                            badge.textContent = 'Inativo';
                        }
                    }
                } else {
                    Portal.toast(result.message || 'Erro.', 'error');
                    // Reverter toggle
                    e.target.checked = !e.target.checked;
                }
            }, function () {
                Portal.toast('Erro de conexão.', 'error');
                e.target.checked = !e.target.checked;
            });
        }
    });
})();
