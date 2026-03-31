/**
 * Dashboard Widgets — Lazy Loading & Layout Manager
 *
 * Loads dashboard widgets via AJAX based on user group configuration.
 * Each widget is loaded independently with skeleton placeholders.
 *
 * Usage:
 *   AktiDashboardWidgets.init('#widgets-container');
 *
 * @requires assets/js/components/skeleton.js
 * @requires assets/js/components/toast.js
 */
(function () {
    'use strict';

    var DashboardWidgets = {
        container: null,
        widgets: [],
        loaded: {},

        /**
         * Initialize dashboard widgets system.
         * @param {string} containerSelector - CSS selector for widget container
         */
        init: function (containerSelector) {
            this.container = document.querySelector(containerSelector);
            if (!this.container) return;

            this.fetchConfig();
        },

        /**
         * Fetch widget configuration for current user's group.
         */
        fetchConfig: function () {
            var self = this;

            fetch('?page=dashboard_widgets&action=config', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.success && data.widgets) {
                        self.widgets = data.widgets;
                        self.renderSkeletons();
                        self.loadAll();
                    }
                })
                .catch(function (err) {
                    console.warn('[DashboardWidgets] Config fetch failed:', err);
                    // Fallback: load all default widgets
                    self.loadFallback();
                });
        },

        /**
         * Render skeleton placeholders for each widget.
         */
        renderSkeletons: function () {
            var html = '';
            for (var i = 0; i < this.widgets.length; i++) {
                var w = this.widgets[i];
                html += '<div class="widget-slot mb-4" data-widget="' + w.key + '" id="widget-' + w.key + '">';
                html += '  <div class="card border-0 shadow-sm">';
                html += '    <div class="card-body p-3">';
                html += '      <div class="d-flex align-items-center mb-3">';
                html += '        <div class="akti-skeleton" style="width:24px;height:24px;border-radius:6px;"></div>';
                html += '        <div class="akti-skeleton ms-2" style="width:140px;height:16px;border-radius:4px;"></div>';
                html += '      </div>';
                html += '      <div class="akti-skeleton mb-2" style="width:100%;height:40px;border-radius:6px;"></div>';
                html += '      <div class="akti-skeleton" style="width:80%;height:20px;border-radius:4px;"></div>';
                html += '    </div>';
                html += '  </div>';
                html += '</div>';
            }
            this.container.innerHTML = html;
        },

        /**
         * Load all widgets in parallel.
         */
        loadAll: function () {
            var self = this;
            this.widgets.forEach(function (w) {
                self.loadWidget(w.key);
            });
        },

        /**
         * Load a single widget via AJAX.
         * @param {string} key - Widget key
         */
        loadWidget: function (key) {
            var self = this;
            var slot = document.getElementById('widget-' + key);
            if (!slot) return;

            fetch('?page=dashboard_widgets&action=load&widget=' + encodeURIComponent(key), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.success && data.html) {
                        slot.innerHTML = data.html;
                        self.loaded[key] = true;

                        // Trigger custom event for widget-specific JS
                        var event = new CustomEvent('widget:loaded', {
                            detail: { key: key, element: slot }
                        });
                        document.dispatchEvent(event);
                    } else {
                        slot.innerHTML = self.renderError(key, data.error || 'Erro ao carregar widget.');
                    }
                })
                .catch(function (err) {
                    console.warn('[DashboardWidgets] Load failed for ' + key + ':', err);
                    slot.innerHTML = self.renderError(key, 'Falha na conexão.');
                });
        },

        /**
         * Render error state for a widget.
         */
        renderError: function (key, message) {
            return '<div class="card border-0 shadow-sm">' +
                '<div class="card-body p-3 text-center text-muted">' +
                '<i class="fas fa-exclamation-circle mb-2" style="font-size:1.5rem;color:var(--danger,#ef4444);"></i>' +
                '<div class="small">' + message + '</div>' +
                '<button class="btn btn-sm btn-outline-primary mt-2" onclick="AktiDashboardWidgets.loadWidget(\'' + key + '\')">' +
                '<i class="fas fa-redo me-1"></i>Tentar novamente</button>' +
                '</div></div>';
        },

        /**
         * Refresh a single widget.
         */
        refresh: function (key) {
            var slot = document.getElementById('widget-' + key);
            if (slot) {
                slot.innerHTML = '<div class="card border-0 shadow-sm"><div class="card-body p-3">' +
                    '<div class="akti-skeleton" style="width:100%;height:60px;border-radius:6px;"></div></div></div>';
            }
            this.loadWidget(key);
        },

        /**
         * Refresh all widgets.
         */
        refreshAll: function () {
            this.loaded = {};
            this.renderSkeletons();
            this.loadAll();
        },

        /**
         * Fallback: render default widgets without AJAX config.
         */
        loadFallback: function () {
            // Default widget keys in standard order
            this.widgets = [
                { key: 'header', label: 'Saudação' },
                { key: 'cards_summary', label: 'Resumo' },
                { key: 'pipeline', label: 'Pipeline' },
                { key: 'financeiro', label: 'Financeiro' },
                { key: 'atrasados', label: 'Atrasados' },
                { key: 'agenda', label: 'Agenda' },
                { key: 'atividade', label: 'Atividade' }
            ];
            this.renderSkeletons();
            this.loadAll();
        }
    };

    // Expose globally
    window.AktiDashboardWidgets = DashboardWidgets;

})();
