/**
 * Akti — Command Palette (Ctrl+K)
 * Global search with keyboard navigation, inspired by VS Code / Linear.
 *
 * Usage:
 *   AktiCommandPalette.open();
 *   AktiCommandPalette.close();
 *
 * Searches: Pages, Customers, Orders, Products via AJAX.
 * Prefix ">" for quick actions: >novo pedido, >novo cliente
 */
(function() {
    'use strict';

    var DEBOUNCE_MS = 200;
    var backdrop = null;
    var searchInput = null;
    var resultsList = null;
    var debounceTimer = null;
    var selectedIndex = -1;
    var currentResults = [];
    var _isOpen = false;

    // Static pages (no AJAX needed)
    var PAGES = [
        { type: 'page', label: 'Dashboard',       icon: 'fas fa-tachometer-alt', url: '?page=dashboard' },
        { type: 'page', label: 'Clientes',         icon: 'fas fa-users',          url: '?page=customers' },
        { type: 'page', label: 'Pedidos',           icon: 'fas fa-shopping-cart',   url: '?page=orders' },
        { type: 'page', label: 'Produtos',          icon: 'fas fa-boxes-stacked',   url: '?page=products' },
        { type: 'page', label: 'Pipeline',          icon: 'fas fa-columns',         url: '?page=pipeline' },
        { type: 'page', label: 'Financeiro',        icon: 'fas fa-wallet',          url: '?page=financial' },
        { type: 'page', label: 'Estoque',           icon: 'fas fa-warehouse',       url: '?page=stock' },
        { type: 'page', label: 'Relatórios',        icon: 'fas fa-chart-bar',       url: '?page=reports' },
        { type: 'page', label: 'Configurações',     icon: 'fas fa-cog',             url: '?page=settings' },
        { type: 'page', label: 'Usuários',          icon: 'fas fa-users-cog',       url: '?page=users' },
        { type: 'page', label: 'Categorias',        icon: 'fas fa-tags',            url: '?page=categories' },
        { type: 'page', label: 'Comissões',         icon: 'fas fa-hand-holding-usd',url: '?page=commissions' },
        { type: 'page', label: 'Meu Perfil',        icon: 'fas fa-user-circle',     url: '?page=profile' },
    ];

    var ACTIONS = [
        { type: 'action', label: 'Novo Pedido',     icon: 'fas fa-plus',  url: '?page=orders&action=create' },
        { type: 'action', label: 'Novo Cliente',    icon: 'fas fa-plus',  url: '?page=customers&action=create' },
        { type: 'action', label: 'Novo Produto',    icon: 'fas fa-plus',  url: '?page=products&action=create' },
        { type: 'action', label: 'Tema Escuro/Claro', icon: 'fas fa-moon', url: '#theme-toggle', handler: function() { if(window.AktiTheme) AktiTheme.toggle(); } },
        { type: 'action', label: 'Atalhos de Teclado', icon: 'fas fa-keyboard', url: '#shortcuts', handler: function() { if(window.AktiShortcuts) AktiShortcuts.showHelp(); } },
    ];

    function createDOM() {
        if (backdrop) return;

        backdrop = document.createElement('div');
        backdrop.className = 'akti-modal-backdrop';
        backdrop.id = 'akti-command-palette';
        backdrop.addEventListener('click', function(e) {
            if (e.target === backdrop) close();
        });

        var modal = document.createElement('div');
        modal.className = 'akti-modal';
        modal.style.cssText = 'max-width:580px;top:20%;transform:translate(-50%,0);';

        // Header with search input
        var header = document.createElement('div');
        header.style.cssText = 'padding:0;border:none;';

        var inputWrap = document.createElement('div');
        inputWrap.style.cssText = 'display:flex;align-items:center;gap:10px;padding:14px 20px;border-bottom:1px solid var(--border-light);';

        var searchIcon = document.createElement('span');
        searchIcon.style.cssText = 'color:var(--text-muted);font-size:1rem;flex-shrink:0;';
        searchIcon.innerHTML = '<i class="fas fa-search"></i>';

        searchInput = document.createElement('input');
        searchInput.type = 'text';
        searchInput.placeholder = 'Buscar páginas, clientes, pedidos... (> para ações)';
        searchInput.style.cssText = 'flex:1;border:none;outline:none;font-size:0.9375rem;background:transparent;color:var(--text-primary);font-family:var(--font-sans);';
        searchInput.addEventListener('input', onInput);
        searchInput.addEventListener('keydown', onKeydown);

        var kbdHint = document.createElement('kbd');
        kbdHint.style.cssText = 'font-size:0.7rem;background:var(--bg-tertiary);border:1px solid var(--border);padding:2px 6px;border-radius:4px;color:var(--text-muted);font-family:var(--font-mono);flex-shrink:0;';
        kbdHint.textContent = 'esc';

        inputWrap.appendChild(searchIcon);
        inputWrap.appendChild(searchInput);
        inputWrap.appendChild(kbdHint);
        header.appendChild(inputWrap);

        // Results
        resultsList = document.createElement('div');
        resultsList.style.cssText = 'max-height:360px;overflow-y:auto;padding:8px;';
        resultsList.className = 'akti-command-results';

        modal.appendChild(header);
        modal.appendChild(resultsList);
        backdrop.appendChild(modal);
        document.body.appendChild(backdrop);
    }

    function open() {
        createDOM();
        _isOpen = true;
        searchInput.value = '';
        selectedIndex = -1;
        showDefaultResults();
        backdrop.classList.add('active');
        setTimeout(function() { searchInput.focus(); }, 50);
    }

    function close() {
        if (backdrop) {
            backdrop.classList.remove('active');
        }
        _isOpen = false;
    }

    function showDefaultResults() {
        currentResults = PAGES.slice(0, 8);
        renderResults(currentResults, 'Páginas');
    }

    function onInput() {
        var q = searchInput.value.trim();
        selectedIndex = -1;

        if (!q) {
            showDefaultResults();
            return;
        }

        // ">" prefix → show actions
        if (q.charAt(0) === '>') {
            var actionQuery = q.substring(1).trim().toLowerCase();
            var filtered = ACTIONS.filter(function(a) {
                return a.label.toLowerCase().indexOf(actionQuery) !== -1;
            });
            currentResults = filtered;
            renderResults(filtered, 'Ações Rápidas');
            return;
        }

        // Search static pages first
        var qLower = q.toLowerCase();
        var pageResults = PAGES.filter(function(p) {
            return p.label.toLowerCase().indexOf(qLower) !== -1;
        });

        currentResults = pageResults;
        renderResults(pageResults, 'Páginas');

        // Also search via AJAX (debounced)
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function() { searchAjax(q); }, DEBOUNCE_MS);
    }

    function searchAjax(q) {
        fetch('?page=search&action=query&q=' + encodeURIComponent(q), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (searchInput.value.trim().toLowerCase() !== q.toLowerCase()) return; // stale

            var ajaxResults = [];
            if (data.results && data.results.length) {
                data.results.forEach(function(r) {
                    ajaxResults.push({
                        type: r.type || 'result',
                        label: r.title,
                        sublabel: r.subtitle || '',
                        icon: r.icon || 'fas fa-circle',
                        url: r.url
                    });
                });
            }

            // Merge with page results
            var qLower = q.toLowerCase();
            var pageResults = PAGES.filter(function(p) {
                return p.label.toLowerCase().indexOf(qLower) !== -1;
            });

            currentResults = pageResults.concat(ajaxResults).slice(0, 20);
            renderMixedResults(currentResults);
        })
        .catch(function() {});
    }

    function renderResults(items, groupLabel) {
        if (!items.length) {
            resultsList.innerHTML = '<div style="padding:24px;text-align:center;color:var(--text-muted);font-size:0.875rem;"><i class="fas fa-search me-2"></i>Nenhum resultado encontrado</div>';
            return;
        }

        var html = '';
        if (groupLabel) {
            html += '<div style="padding:4px 8px;font-size:0.7rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.04em;">' + groupLabel + '</div>';
        }
        items.forEach(function(item, i) {
            html += renderItem(item, i);
        });
        resultsList.innerHTML = html;
        bindItemClicks();
    }

    function renderMixedResults(items) {
        if (!items.length) {
            resultsList.innerHTML = '<div style="padding:24px;text-align:center;color:var(--text-muted);font-size:0.875rem;"><i class="fas fa-search me-2"></i>Nenhum resultado encontrado</div>';
            return;
        }

        var grouped = {};
        var typeLabels = { page: 'Páginas', customer: 'Clientes', order: 'Pedidos', product: 'Produtos', action: 'Ações' };

        items.forEach(function(item) {
            var g = typeLabels[item.type] || 'Outros';
            if (!grouped[g]) grouped[g] = [];
            grouped[g].push(item);
        });

        var html = '';
        var idx = 0;
        Object.keys(grouped).forEach(function(label) {
            html += '<div style="padding:4px 8px;font-size:0.7rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.04em;margin-top:' + (idx > 0 ? '8px' : '0') + ';">' + label + '</div>';
            grouped[label].forEach(function(item) {
                html += renderItem(item, idx++);
            });
        });

        resultsList.innerHTML = html;
        bindItemClicks();
    }

    function renderItem(item, index) {
        return '<a href="' + (item.url || '#') + '" class="akti-cmd-item" data-index="' + index + '" ' +
            (item.handler ? 'data-has-handler="true"' : '') +
            ' style="display:flex;align-items:center;gap:10px;padding:8px 12px;border-radius:var(--ds-radius-md,8px);color:var(--text-primary);text-decoration:none;cursor:pointer;transition:background 0.1s ease;"' +
            ' onmouseenter="this.style.background=\'var(--bg-tertiary)\'" onmouseleave="this.style.background=\'transparent\'">' +
            '<i class="' + item.icon + '" style="width:20px;text-align:center;color:var(--text-secondary);font-size:0.85rem;flex-shrink:0;"></i>' +
            '<div style="flex:1;min-width:0;">' +
            '<div style="font-size:0.875rem;font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + escHtml(item.label) + '</div>' +
            (item.sublabel ? '<div style="font-size:0.75rem;color:var(--text-muted);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + escHtml(item.sublabel) + '</div>' : '') +
            '</div>' +
            '<i class="fas fa-arrow-right" style="font-size:0.6rem;color:var(--text-muted);flex-shrink:0;opacity:0.5;"></i>' +
            '</a>';
    }

    function bindItemClicks() {
        var items = resultsList.querySelectorAll('.akti-cmd-item');
        items.forEach(function(el, i) {
            el.addEventListener('click', function(e) {
                var item = currentResults[i];
                if (item && item.handler) {
                    e.preventDefault();
                    close();
                    item.handler();
                }
                // If it's a normal link, the browser navigates naturally
                else {
                    close();
                }
            });
        });
    }

    function onKeydown(e) {
        var items = resultsList.querySelectorAll('.akti-cmd-item');
        var count = items.length;

        // Arrow down
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            selectedIndex = Math.min(selectedIndex + 1, count - 1);
            updateSelection(items);
            return;
        }

        // Arrow up
        if (e.key === 'ArrowUp') {
            e.preventDefault();
            selectedIndex = Math.max(selectedIndex - 1, -1);
            updateSelection(items);
            return;
        }

        // Enter → navigate to selected
        if (e.key === 'Enter' && selectedIndex >= 0 && selectedIndex < count) {
            e.preventDefault();
            items[selectedIndex].click();
            return;
        }
    }

    function updateSelection(items) {
        items.forEach(function(el, i) {
            el.style.background = (i === selectedIndex) ? 'var(--bg-tertiary)' : 'transparent';
        });
        if (selectedIndex >= 0 && items[selectedIndex]) {
            items[selectedIndex].scrollIntoView({ block: 'nearest' });
        }
    }

    function escHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    window.AktiCommandPalette = {
        open: open,
        close: close,
        isOpen: function() { return _isOpen; },
    };

})();
