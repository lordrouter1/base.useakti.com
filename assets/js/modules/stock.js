/**
 * Stock Module — Akti ERP
 * Extracted from app/views/stock/index.php inline script
 * Handles: SPA navigation, AJAX pagination, CRUD operations for stock
 */
document.addEventListener('DOMContentLoaded', function() {

    // CSRF token para requisições AJAX POST
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    // Config injected via data attributes on #stockApp
    var stockApp = document.getElementById('stockApp');
    var activeSection = stockApp ? (stockApp.dataset.activeSection || 'overview') : 'overview';
    var initStatus = stockApp ? (stockApp.dataset.status || '') : '';

    // ═══════════════════════════════════════════
    // ═══ UTILITÁRIOS                         ═══
    // ═══════════════════════════════════════════
    function escHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function formatNumber(n) {
        return parseInt(n || 0).toLocaleString('pt-BR');
    }

    function formatDate(dateStr) {
        if (!dateStr) return '—';
        var d = new Date(dateStr);
        return d.toLocaleDateString('pt-BR') + ' ' + d.toLocaleTimeString('pt-BR', {hour:'2-digit',minute:'2-digit'});
    }

    // ═══════════════════════════════════════════
    // ═══ SIDEBAR NAVIGATION (SPA-like)       ═══
    // ═══════════════════════════════════════════
    function navigateToSection(sectionId) {
        document.querySelectorAll('.stk-nav-item').forEach(function(n) { n.classList.remove('active'); });
        var navItem = document.querySelector('.stk-nav-item[data-section="' + sectionId + '"]');
        if (navItem) navItem.classList.add('active');

        document.querySelectorAll('.stk-section').forEach(function(s) { s.classList.remove('active'); });
        var target = document.getElementById('stk-' + sectionId);
        if (target) target.classList.add('active');

        var url = new URL(window.location);
        url.searchParams.set('section', sectionId);
        history.replaceState(null, '', url);

        if (sectionId === 'overview') loadStockItems(1);
        if (sectionId === 'movements') loadMovements(1);
    }

    document.querySelectorAll('.stk-nav-item').forEach(function(item) {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            var section = this.dataset.section;
            if (!section) return;
            navigateToSection(section);
        });
    });

    document.querySelectorAll('.stk-go-entry').forEach(function(a) {
        a.addEventListener('click', function(e) { e.preventDefault(); navigateToSection('entry'); });
    });
    document.querySelectorAll('.stk-go-movements').forEach(function(a) {
        a.addEventListener('click', function(e) { e.preventDefault(); navigateToSection('movements'); });
    });
    document.querySelectorAll('.stk-nav-link-overview').forEach(function(a) {
        a.addEventListener('click', function(e) { e.preventDefault(); navigateToSection('overview'); });
    });

    // ═══════════════════════════════════════════
    // ═══ STATUS ALERTS                        ═══
    // ═══════════════════════════════════════════
    if (initStatus) {
        var urlClean = new URL(window.location);
        urlClean.searchParams.delete('status');
        urlClean.searchParams.delete('error');
        window.history.replaceState({}, '', urlClean);

        var statusMessages = {
            moved: { icon: 'success', title: 'Movimentação registrada!' },
            created: { icon: 'success', title: 'Armazém criado!' },
            updated: { icon: 'success', title: 'Armazém atualizado!' },
            deleted: { icon: 'success', title: 'Armazém removido!' },
            limit_warehouses: { icon: 'warning', title: 'Limite atingido!', text: 'Você atingiu o limite de armazéns do seu plano.', confirmButtonColor: '#3498db' }
        };

        var msg = statusMessages[initStatus];
        if (msg) {
            Swal.fire({
                icon: msg.icon,
                title: msg.title,
                text: msg.text || undefined,
                timer: msg.text ? undefined : 2000,
                showConfirmButton: !!msg.text,
                confirmButtonColor: msg.confirmButtonColor || undefined
            });
        }
    }

    // ═══════════════════════════════════════════
    // ═══ PAGINAÇÃO — Renderizador genérico   ═══
    // ═══════════════════════════════════════════
    function renderPagination(containerId, page, totalPages, total, perPage, callback) {
        var container = document.getElementById(containerId);
        var infoEl = document.getElementById(containerId + 'Info');

        if (infoEl) {
            var from = total > 0 ? ((page - 1) * perPage + 1) : 0;
            var to = Math.min(page * perPage, total);
            infoEl.textContent = 'Exibindo ' + from + '–' + to + ' de ' + total + ' registro(s)';
        }

        if (!container) return;
        container.innerHTML = '';
        if (totalPages <= 1) return;

        var liPrev = document.createElement('li');
        liPrev.className = 'page-item' + (page <= 1 ? ' disabled' : '');
        liPrev.innerHTML = '<a class="page-link" href="#">&laquo;</a>';
        if (page > 1) liPrev.querySelector('a').addEventListener('click', function(e) { e.preventDefault(); callback(page - 1); });
        container.appendChild(liPrev);

        var startP = Math.max(1, page - 2);
        var endP = Math.min(totalPages, page + 2);
        if (startP > 1) {
            var li1 = document.createElement('li');
            li1.className = 'page-item';
            li1.innerHTML = '<a class="page-link" href="#">1</a>';
            li1.querySelector('a').addEventListener('click', function(e) { e.preventDefault(); callback(1); });
            container.appendChild(li1);
            if (startP > 2) {
                var liDots = document.createElement('li');
                liDots.className = 'page-item disabled';
                liDots.innerHTML = '<span class="page-link">…</span>';
                container.appendChild(liDots);
            }
        }
        for (var i = startP; i <= endP; i++) {
            (function(pg) {
                var li = document.createElement('li');
                li.className = 'page-item' + (pg === page ? ' active' : '');
                li.innerHTML = '<a class="page-link" href="#">' + pg + '</a>';
                if (pg !== page) li.querySelector('a').addEventListener('click', function(e) { e.preventDefault(); callback(pg); });
                container.appendChild(li);
            })(i);
        }
        if (endP < totalPages) {
            if (endP < totalPages - 1) {
                var liDots2 = document.createElement('li');
                liDots2.className = 'page-item disabled';
                liDots2.innerHTML = '<span class="page-link">…</span>';
                container.appendChild(liDots2);
            }
            var liLast = document.createElement('li');
            liLast.className = 'page-item';
            liLast.innerHTML = '<a class="page-link" href="#">' + totalPages + '</a>';
            liLast.querySelector('a').addEventListener('click', function(e) { e.preventDefault(); callback(totalPages); });
            container.appendChild(liLast);
        }

        var liNext = document.createElement('li');
        liNext.className = 'page-item' + (page >= totalPages ? ' disabled' : '');
        liNext.innerHTML = '<a class="page-link" href="#">&raquo;</a>';
        if (page < totalPages) liNext.querySelector('a').addEventListener('click', function(e) { e.preventDefault(); callback(page + 1); });
        container.appendChild(liNext);
    }

    // ═══════════════════════════════════════════
    // ═══ VISÃO GERAL — AJAX + Paginação      ═══
    // ═══════════════════════════════════════════
    var ovCurrentPage = 1;

    var _ovAbort = null;
    function loadStockItems(page) {
        if (_ovAbort) _ovAbort.abort();
        _ovAbort = new AbortController();
        ovCurrentPage = page || 1;
        var tbody = document.getElementById('stockTableBody');
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin me-1"></i>Carregando...</td></tr>';

        var params = new URLSearchParams({
            page: 'stock',
            action: 'getStockItems',
            warehouse_id: document.getElementById('ov_warehouse').value,
            search: document.getElementById('ov_search').value,
            low_stock: document.getElementById('ov_low_stock').checked ? '1' : '',
            pg: ovCurrentPage,
            per_page: 25
        });

        fetch('?' + params.toString(), { signal: _ovAbort.signal })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success) {
                    tbody.innerHTML = '<tr><td colspan="8" class="text-center text-danger py-4">Erro ao carregar dados.</td></tr>';
                    return;
                }

                if (data.items.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-5">' +
                        '<i class="fas fa-warehouse fa-3x mb-3 d-block text-secondary"></i>' +
                        'Nenhum item no estoque com os filtros selecionados.' +
                        '<br><a href="#" class="btn btn-success btn-sm mt-2 stk-go-entry"><i class="fas fa-plus me-1"></i>Dar entrada</a>' +
                        '</td></tr>';
                    tbody.querySelectorAll('.stk-go-entry').forEach(function(a) {
                        a.addEventListener('click', function(e) { e.preventDefault(); navigateToSection('entry'); });
                    });
                    document.getElementById('ovPagination').innerHTML = '';
                    document.getElementById('ovPaginationInfo').textContent = '0 registros';
                    return;
                }

                var html = '';
                data.items.forEach(function(si) {
                    var isLow = si.min_quantity > 0 && si.quantity <= si.min_quantity;
                    var imgCell = si.product_image
                        ? '<img src="' + escHtml(si.product_image) + '" class="w-100 h-100 object-fit-cover">'
                        : '<i class="fas fa-box text-secondary"></i>';
                    var combCell = si.combination_label
                        ? '<span class="badge bg-info bg-opacity-75">' + escHtml(si.combination_label) + '</span>'
                        : '<span class="text-muted small">—</span>';
                    var qtyBadge = isLow
                        ? '<span class="badge bg-danger px-3 fs-6">' + formatNumber(si.quantity) + '</span>'
                        : (si.quantity > 0
                            ? '<span class="badge bg-success px-3 fs-6">' + formatNumber(si.quantity) + '</span>'
                            : '<span class="badge bg-secondary px-3">0</span>');
                    var minCell = si.min_quantity > 0 ? formatNumber(si.min_quantity) : '—';
                    var locCell = si.location_code ? escHtml(si.location_code) : '—';

                    html += '<tr class="' + (isLow ? 'table-warning' : '') + '">' +
                        '<td class="ps-4"><div class="bg-light rounded d-flex align-items-center justify-content-center border" style="width:40px;height:40px;overflow:hidden;">' + imgCell + '</div></td>' +
                        '<td class="fw-bold">' + escHtml(si.product_name) + '</td>' +
                        '<td>' + combCell + '</td>' +
                        '<td><span class="badge bg-light text-dark border">' + escHtml(si.warehouse_name) + '</span></td>' +
                        '<td class="text-center">' + qtyBadge + '</td>' +
                        '<td class="text-center"><span class="text-muted small">' + minCell + '</span></td>' +
                        '<td><span class="text-muted small">' + locCell + '</span></td>' +
                        '<td class="text-end pe-4"><div class="btn-group btn-group-sm">' +
                            '<button type="button" class="btn btn-outline-secondary btn-edit-meta"' +
                            ' data-id="' + si.id + '" data-min="' + (si.min_quantity||0) + '"' +
                            ' data-loc="' + escHtml(si.location_code||'') + '"' +
                            ' data-name="' + escHtml(si.product_name) + '"' +
                            ' aria-label="Configurar estoque mínimo de ' + escHtml(si.product_name) + '"' +
                            ' title="Editar mínimo/localização"><i class="fas fa-cog"></i></button>' +
                        '</div></td>' +
                    '</tr>';
                });
                tbody.innerHTML = html;

                bindEditMetaButtons();
                renderPagination('ovPagination', data.page, data.total_pages, data.total, data.per_page, loadStockItems);
                var infoEl = document.getElementById('ovPaginationInfo');
                if (infoEl) {
                    var from = data.total > 0 ? ((data.page - 1) * data.per_page + 1) : 0;
                    var to = Math.min(data.page * data.per_page, data.total);
                    infoEl.textContent = 'Exibindo ' + from + '–' + to + ' de ' + data.total + ' registro(s)';
                }
            })
            .catch(function(err) {
                if (err.name === 'AbortError') return;
                tbody.innerHTML = '<tr><td colspan="8" class="text-center text-danger py-4">Erro de comunicação ao carregar estoque.</td></tr>';
            });
    }

    var _ovDebounce = null;
    document.querySelectorAll('.ov-filter').forEach(function(el) {
        var evType = (el.tagName === 'INPUT' && el.type === 'text') ? 'input' : 'change';
        el.addEventListener(evType, function() {
            clearTimeout(_ovDebounce);
            _ovDebounce = setTimeout(function() { loadStockItems(1); }, evType === 'input' ? 350 : 0);
        });
    });
    var btnClearOverview = document.getElementById('btnClearOverview');
    if (btnClearOverview) {
        btnClearOverview.addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('ov_warehouse').value = '';
            document.getElementById('ov_search').value = '';
            document.getElementById('ov_low_stock').checked = false;
            loadStockItems(1);
        });
    }

    if (activeSection === 'overview') loadStockItems(1);

    // ═══════════════════════════════════════════
    // ═══ MOVIMENTAÇÕES — AJAX + Paginação    ═══
    // ═══════════════════════════════════════════
    var movCurrentPage = 1;

    var typeBadges = { entrada:'bg-success', saida:'bg-danger', ajuste:'bg-warning text-dark', transferencia:'bg-info' };
    var typeIcons  = { entrada:'fas fa-arrow-down', saida:'fas fa-arrow-up', ajuste:'fas fa-sliders-h', transferencia:'fas fa-truck' };
    var typeLabels = { entrada:'Entrada', saida:'Saída', ajuste:'Ajuste', transferencia:'Transferência' };

    var _movAbort = null;
    function loadMovements(page) {
        if (_movAbort) _movAbort.abort();
        _movAbort = new AbortController();
        movCurrentPage = page || 1;
        var tbody = document.getElementById('movTableBody');
        tbody.innerHTML = '<tr><td colspan="12" class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin me-1"></i>Carregando...</td></tr>';

        var params = new URLSearchParams({
            page: 'stock',
            action: 'getMovements',
            warehouse_id: document.getElementById('mov_warehouse').value,
            product_id: document.getElementById('mov_product').value,
            type: document.getElementById('mov_type_filter').value,
            date_from: document.getElementById('mov_date_from').value,
            date_to: document.getElementById('mov_date_to').value,
            pg: movCurrentPage,
            per_page: 25
        });

        fetch('?' + params.toString(), { signal: _movAbort.signal })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success) {
                    tbody.innerHTML = '<tr><td colspan="12" class="text-center text-danger py-4">Erro ao carregar movimentações.</td></tr>';
                    return;
                }

                if (data.items.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="12" class="text-center text-muted py-5">' +
                        '<i class="fas fa-exchange-alt fa-3x mb-3 d-block text-secondary"></i>' +
                        'Nenhuma movimentação encontrada.</td></tr>';
                    document.getElementById('movPagination').innerHTML = '';
                    document.getElementById('movPaginationInfo').textContent = '0 registros';
                    return;
                }

                var html = '';
                data.items.forEach(function(m) {
                    var badge = typeBadges[m.type] || 'bg-secondary';
                    var icon  = typeIcons[m.type] || '';
                    var label = typeLabels[m.type] || m.type;

                    var warehouseCell = escHtml(m.warehouse_name);
                    if (m.type === 'transferencia' && m.dest_warehouse_name) {
                        warehouseCell += ' <i class="fas fa-arrow-right mx-1 text-muted" style="font-size:0.6rem;" aria-hidden="true"></i> <span class="text-info">' + escHtml(m.dest_warehouse_name) + '</span>';
                    }

                    var qtyCell;
                    if (m.type === 'entrada') {
                        qtyCell = '<span class="text-success">+' + formatNumber(m.quantity) + '</span>';
                    } else if (m.type === 'saida') {
                        qtyCell = '<span class="text-danger">-' + formatNumber(m.quantity) + '</span>';
                    } else {
                        qtyCell = formatNumber(m.quantity);
                    }

                    var combCell = m.combination_label
                        ? '<span class="badge bg-light text-dark border">' + escHtml(m.combination_label) + '</span>'
                        : '<span class="text-muted">—</span>';

                    var isManual = (!m.reference_type || m.reference_type === 'manual');
                    var actionsCell = '';
                    if (isManual) {
                        actionsCell = '<button class="btn btn-sm btn-outline-primary py-0 px-1 btn-edit-mov" data-id="' + m.id + '" title="Editar" aria-label="Editar movimentação #' + m.id + '">' +
                            '<i class="fas fa-pen" style="font-size:0.7rem;" aria-hidden="true"></i>' +
                        '</button>';
                    } else {
                        actionsCell = '<span class="text-muted" title="Automática"><i class="fas fa-lock" style="font-size:0.65rem;" aria-hidden="true"></i></span>';
                    }

                    html += '<tr>' +
                        '<td class="ps-3 text-muted small">' + m.id + '</td>' +
                        '<td class="small">' + formatDate(m.created_at) + '</td>' +
                        '<td><span class="badge ' + badge + '"><i class="' + icon + ' me-1" aria-hidden="true"></i>' + label + '</span></td>' +
                        '<td class="fw-bold small">' + escHtml(m.product_name) + '</td>' +
                        '<td class="small">' + combCell + '</td>' +
                        '<td class="small">' + warehouseCell + '</td>' +
                        '<td class="text-center fw-bold">' + qtyCell + '</td>' +
                        '<td class="text-center small text-muted">' + formatNumber(m.quantity_before) + '</td>' +
                        '<td class="text-center small fw-bold">' + formatNumber(m.quantity_after) + '</td>' +
                        '<td class="small text-muted" style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="' + escHtml(m.reason||'') + '">' + (m.reason ? escHtml(m.reason) : '—') + '</td>' +
                        '<td class="small text-muted">' + (m.user_name ? escHtml(m.user_name) : '—') + '</td>' +
                        '<td class="text-center">' + actionsCell + '</td>' +
                    '</tr>';
                });
                tbody.innerHTML = html;

                renderPagination('movPagination', data.page, data.total_pages, data.total, data.per_page, loadMovements);
                var infoEl = document.getElementById('movPaginationInfo');
                if (infoEl) {
                    var from = data.total > 0 ? ((data.page - 1) * data.per_page + 1) : 0;
                    var to = Math.min(data.page * data.per_page, data.total);
                    infoEl.textContent = 'Exibindo ' + from + '–' + to + ' de ' + data.total + ' movimentação(ões)';
                }

                bindEditMovButtons();
            })
            .catch(function(err) {
                if (err.name === 'AbortError') return;
                tbody.innerHTML = '<tr><td colspan="12" class="text-center text-danger py-4">Erro de comunicação ao carregar movimentações.</td></tr>';
            });
    }

    document.querySelectorAll('.mov-filter').forEach(function(el) {
        el.addEventListener('change', function() { loadMovements(1); });
    });
    var btnClearMov = document.getElementById('btnClearMov');
    if (btnClearMov) {
        btnClearMov.addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('mov_warehouse').value = '';
            document.getElementById('mov_product').value = '';
            document.getElementById('mov_type_filter').value = '';
            document.getElementById('mov_date_from').value = '';
            document.getElementById('mov_date_to').value = '';
            loadMovements(1);
        });
    }

    if (activeSection === 'movements') loadMovements(1);

    // ═══════════════════════════════════════════
    // ═══ EDITAR / EXCLUIR MOVIMENTAÇÃO       ═══
    // ═══════════════════════════════════════════
    var editMovModal = null;

    function bindEditMovButtons() {
        document.querySelectorAll('.btn-edit-mov').forEach(function(btn) {
            btn.addEventListener('click', function() {
                openEditMovement(this.dataset.id);
            });
        });
    }

    function openEditMovement(movId) {
        fetch('?page=stock&action=getMovement&id=' + movId)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success) {
                    Swal.fire({ icon: 'error', title: 'Erro', text: data.message || 'Movimentação não encontrada.' });
                    return;
                }
                var m = data.movement;

                document.getElementById('editMov_id').value = m.id;
                document.getElementById('editMov_idLabel').textContent = '#' + m.id;
                document.getElementById('editMov_productName').textContent = m.product_name;
                document.getElementById('editMov_combination').textContent = m.combination_label || '—';
                document.getElementById('editMov_warehouse').textContent = m.warehouse_name;
                document.getElementById('editMov_refType').textContent = m.reference_type || 'manual';
                document.getElementById('editMov_date').textContent = formatDate(m.created_at);
                document.getElementById('editMov_type').value = m.type;
                document.getElementById('editMov_quantity').value = parseFloat(m.quantity);
                document.getElementById('editMov_reason').value = m.reason || '';

                updateEditMovQtyLabel();

                var typeSelect = document.getElementById('editMov_type');
                if (m.type === 'transferencia') {
                    typeSelect.disabled = true;
                    document.getElementById('editMov_info').innerHTML = '<i class="fas fa-exclamation-triangle me-1 text-warning"></i>Transferências não podem ter o tipo alterado. Para desfazer, exclua a movimentação.';
                } else {
                    typeSelect.disabled = false;
                    document.getElementById('editMov_info').innerHTML = '<i class="fas fa-info-circle me-1"></i>Ao alterar tipo ou quantidade, o saldo do estoque será recalculado automaticamente.';
                }

                if (!editMovModal) {
                    editMovModal = new bootstrap.Modal(document.getElementById('editMovementModal'));
                }
                editMovModal.show();
            })
            .catch(function() {
                Swal.fire({ icon: 'error', title: 'Erro de comunicação', text: 'Não foi possível buscar os dados da movimentação.' });
            });
    }

    function updateEditMovQtyLabel() {
        var type = document.getElementById('editMov_type').value;
        var label = document.getElementById('editMov_qtyLabel');
        label.textContent = type === 'ajuste' ? 'Novo Saldo *' : 'Quantidade *';
    }

    var editMovType = document.getElementById('editMov_type');
    if (editMovType) editMovType.addEventListener('change', updateEditMovQtyLabel);

    var btnSaveMovement = document.getElementById('btnSaveMovement');
    if (btnSaveMovement) {
        btnSaveMovement.addEventListener('click', function() {
            var id = document.getElementById('editMov_id').value;
            var type = document.getElementById('editMov_type').value;
            var quantity = document.getElementById('editMov_quantity').value;
            var reason = document.getElementById('editMov_reason').value;

            if (!quantity || parseFloat(quantity) <= 0) {
                Swal.fire({ icon: 'warning', title: 'Quantidade inválida', text: 'Informe uma quantidade maior que zero.' });
                return;
            }

            var btn = this;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Salvando...';

            var fd = new FormData();
            fd.append('csrf_token', csrfToken);
            fd.append('id', id);
            fd.append('type', type);
            fd.append('quantity', quantity);
            fd.append('reason', reason);

            fetch('?page=stock&action=updateMovement', { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-save me-1"></i>Salvar Alterações';

                    if (data.success) {
                        if (editMovModal) editMovModal.hide();
                        Swal.fire({ icon: 'success', title: 'Atualizado!', text: data.message, timer: 2000, showConfirmButton: false })
                            .then(function() {
                                loadMovements(movCurrentPage);
                                loadStockItems(ovCurrentPage);
                                loadRecentMovements();
                            });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Erro', text: data.message || 'Erro ao atualizar.' });
                    }
                })
                .catch(function() {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-save me-1"></i>Salvar Alterações';
                    Swal.fire({ icon: 'error', title: 'Erro de comunicação' });
                });
        });
    }

    var btnDeleteMovement = document.getElementById('btnDeleteMovement');
    if (btnDeleteMovement) {
        btnDeleteMovement.addEventListener('click', function() {
            var id = document.getElementById('editMov_id').value;
            var productName = document.getElementById('editMov_productName').textContent;

            Swal.fire({
                title: 'Excluir movimentação?',
                html: '<p>Deseja excluir a movimentação <strong>#' + id + '</strong> do produto <strong>' + escHtml(productName) + '</strong>?</p>' +
                      '<p class="text-danger small"><i class="fas fa-exclamation-triangle me-1"></i>O saldo do estoque será revertido automaticamente. Esta ação não pode ser desfeita.</p>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#c0392b',
                confirmButtonText: '<i class="fas fa-trash me-1"></i>Excluir',
                cancelButtonText: 'Cancelar'
            }).then(function(result) {
                if (!result.isConfirmed) return;

                var fd = new FormData();
                fd.append('csrf_token', csrfToken);
                fd.append('id', id);

                fetch('?page=stock&action=deleteMovement', { method: 'POST', body: fd })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.success) {
                            if (editMovModal) editMovModal.hide();
                            Swal.fire({ icon: 'success', title: 'Excluído!', text: data.message, timer: 2000, showConfirmButton: false })
                                .then(function() {
                                    loadMovements(movCurrentPage);
                                    loadStockItems(ovCurrentPage);
                                    loadRecentMovements();
                                });
                        } else {
                            Swal.fire({ icon: 'error', title: 'Erro', text: data.message || 'Erro ao excluir.' });
                        }
                    })
                    .catch(function() {
                        Swal.fire({ icon: 'error', title: 'Erro de comunicação' });
                    });
            });
        });
    }

    // ═══════════════════════════════════════════
    // ═══ VISÃO GERAL — Edit meta modal       ═══
    // ═══════════════════════════════════════════
    function bindEditMetaButtons() {
        document.querySelectorAll('.btn-edit-meta').forEach(function(btn) {
            btn.addEventListener('click', function() {
                document.getElementById('metaItemId').value = this.dataset.id;
                document.getElementById('metaMinQty').value = this.dataset.min;
                document.getElementById('metaLocCode').value = this.dataset.loc;
                document.getElementById('metaItemName').textContent = this.dataset.name;
                new bootstrap.Modal(document.getElementById('editMetaModal')).show();
            });
        });
    }
    bindEditMetaButtons();

    var btnSaveMeta = document.getElementById('btnSaveMeta');
    if (btnSaveMeta) {
        btnSaveMeta.addEventListener('click', function() {
            var id = document.getElementById('metaItemId').value;
            var minQty = document.getElementById('metaMinQty').value;
            var locCode = document.getElementById('metaLocCode').value;

            var fd = new FormData();
            fd.append('csrf_token', csrfToken);
            fd.append('id', id);
            fd.append('min_quantity', minQty);
            fd.append('location_code', locCode);

            fetch('?page=stock&action=updateItemMeta', { method:'POST', body:fd })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success) {
                        bootstrap.Modal.getInstance(document.getElementById('editMetaModal')).hide();
                        Swal.fire({ icon:'success', title:'Atualizado!', timer:1500, showConfirmButton:false })
                            .then(function() { loadStockItems(ovCurrentPage); });
                    }
                });
        });
    }

    // ═══════════════════════════════════════════
    // ═══ ENTRADA/SAÍDA — Lógica completa     ═══
    // ═══════════════════════════════════════════
    var items = [];
    var selProduct = document.getElementById('selProduct');
    var selCombination = document.getElementById('selCombination');
    var combWrap = document.getElementById('combWrap');
    var inputQty = document.getElementById('inputQty');
    var itemsBody = document.getElementById('itemsBody');
    var btnAdd = document.getElementById('btnAddItem');
    var btnProcess = document.getElementById('btnProcess');
    var destWrap = document.getElementById('destWarehouseWrap');
    var lblQty = document.getElementById('lblQty');

    document.querySelectorAll('input[name="mov_type_entry"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            var t = this.value;
            if (destWrap) destWrap.style.display = t === 'transferencia' ? '' : 'none';
            if (lblQty) lblQty.textContent = t === 'ajuste' ? 'Novo Saldo' : 'Quantidade';
            var helpIds = { entrada: 'helpEntrada', saida: 'helpSaida', ajuste: 'helpAjuste', transferencia: 'helpTransfer' };
            Object.keys(helpIds).forEach(function(k) {
                var el = document.getElementById(helpIds[k]);
                if (el) el.style.display = k === t ? '' : 'none';
            });
        });
    });

    if (selProduct) {
        selProduct.addEventListener('change', function() {
            var pid = this.value;
            if (combWrap) combWrap.style.display = 'none';
            if (selCombination) selCombination.innerHTML = '<option value="">Sem variação</option>';
            if (!pid) return;

            fetch('?page=stock&action=getProductCombinations&product_id=' + pid)
                .then(function(r) { return r.json(); })
                .then(function(combos) {
                    if (combos.length > 0) {
                        if (combWrap) combWrap.style.display = '';
                        selCombination.innerHTML = '<option value="">Produto base (sem variação)</option>';
                        combos.forEach(function(c) {
                            selCombination.innerHTML += '<option value="' + c.id + '">' + c.combination_label + (c.sku ? ' [' + c.sku + ']' : '') + '</option>';
                        });
                    }
                });
        });
    }

    if (btnAdd) {
        btnAdd.addEventListener('click', function() {
            var productId = selProduct.value;
            var productName = selProduct.options[selProduct.selectedIndex] ? selProduct.options[selProduct.selectedIndex].text : '';
            var combId = selCombination.value || null;
            var combName = combId ? selCombination.options[selCombination.selectedIndex].text : '—';
            var qty = parseFloat(inputQty.value);

            if (!productId) { Swal.fire({ icon:'warning', title:'Selecione um produto', timer:2000, showConfirmButton:false }); return; }
            if (!qty || qty <= 0) { Swal.fire({ icon:'warning', title:'Quantidade inválida', timer:2000, showConfirmButton:false }); return; }

            var exists = items.find(function(i) { return i.product_id == productId && i.combination_id == combId; });
            if (exists) {
                exists.quantity += qty;
                renderItems();
                return;
            }

            items.push({ product_id: productId, combination_id: combId, product_name: productName, combination_name: combName, quantity: qty });
            renderItems();

            selProduct.value = '';
            selCombination.innerHTML = '<option value="">Sem variação</option>';
            if (combWrap) combWrap.style.display = 'none';
            inputQty.value = 1;
            selProduct.focus();
        });
    }

    function renderItems() {
        if (items.length === 0) {
            if (itemsBody) itemsBody.innerHTML = '<tr id="emptyItemsRow"><td colspan="4" class="text-center text-muted py-3"><i class="fas fa-inbox me-1"></i>Adicione produtos acima</td></tr>';
            if (btnProcess) btnProcess.disabled = true;
            var countLabel = document.getElementById('itemsCountLabel');
            if (countLabel) countLabel.textContent = '0 item(s)';
            return;
        }

        var html = '';
        items.forEach(function(item, idx) {
            html += '<tr>' +
                '<td class="fw-bold">' + escHtml(item.product_name) + '</td>' +
                '<td><span class="badge bg-light text-dark border">' + escHtml(item.combination_name) + '</span></td>' +
                '<td class="text-center">' +
                    '<input type="number" class="form-control form-control-sm text-center" value="' + item.quantity + '" min="0.01" step="1" ' +
                    'onchange="updateItemQty(' + idx + ', this.value)" style="width:80px;margin:auto;">' +
                '</td>' +
                '<td class="text-center">' +
                    '<button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItem(' + idx + ')" aria-label="Remover item"><i class="fas fa-times" aria-hidden="true"></i></button>' +
                '</td>' +
            '</tr>';
        });
        if (itemsBody) itemsBody.innerHTML = html;
        if (btnProcess) btnProcess.disabled = false;
        var countLabel = document.getElementById('itemsCountLabel');
        if (countLabel) countLabel.textContent = items.length + ' item(s)';
    }

    window.updateItemQty = function(idx, val) {
        items[idx].quantity = parseFloat(val) || 0;
    };
    window.removeItem = function(idx) {
        items.splice(idx, 1);
        renderItems();
    };

    if (btnProcess) {
        btnProcess.addEventListener('click', function() {
            var typeRadio = document.querySelector('input[name="mov_type_entry"]:checked');
            var type = typeRadio ? typeRadio.value : 'entrada';
            var warehouseId = document.getElementById('selWarehouse').value;
            var destWarehouseId = document.getElementById('selDestWarehouse') ? document.getElementById('selDestWarehouse').value : '';
            var reason = document.getElementById('movReason').value;

            if (!warehouseId) { Swal.fire({ icon:'warning', title:'Selecione o armazém' }); return; }
            if (type === 'transferencia' && !destWarehouseId) { Swal.fire({ icon:'warning', title:'Selecione o armazém de destino' }); return; }
            if (type === 'transferencia' && warehouseId === destWarehouseId) { Swal.fire({ icon:'warning', title:'Origem e destino devem ser diferentes' }); return; }
            if (items.length === 0) { Swal.fire({ icon:'warning', title:'Adicione produtos' }); return; }

            var movTypeLabels = { entrada:'Entrada', saida:'Saída', ajuste:'Ajuste', transferencia:'Transferência' };

            Swal.fire({
                title: 'Confirmar ' + movTypeLabels[type] + '?',
                html: '<strong>' + items.length + '</strong> item(s) serão processados.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-check me-1"></i>Confirmar',
                cancelButtonText: 'Cancelar'
            }).then(function(result) {
                if (!result.isConfirmed) return;

                btnProcess.disabled = true;
                btnProcess.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processando...';

                var fd = new FormData();
                fd.append('csrf_token', csrfToken);
                fd.append('warehouse_id', warehouseId);
                fd.append('destination_warehouse_id', destWarehouseId);
                fd.append('type', type);
                fd.append('reason', reason);
                items.forEach(function(item, i) {
                    fd.append('items[' + i + '][product_id]', item.product_id);
                    fd.append('items[' + i + '][combination_id]', item.combination_id || '');
                    fd.append('items[' + i + '][quantity]', item.quantity);
                });

                fetch('?page=stock&action=storeMovement', { method:'POST', body:fd })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.success) {
                            Swal.fire({ icon:'success', title:'Movimentação Registrada!', html: data.processed + ' item(s) processado(s).', timer:2500, showConfirmButton:true })
                                .then(function() {
                                    items = [];
                                    renderItems();
                                    document.getElementById('movReason').value = '';
                                    document.getElementById('selWarehouse').value = '';
                                    loadStockItems(1);
                                    loadMovements(1);
                                    loadRecentMovements();
                                    navigateToSection('overview');
                                });
                        } else {
                            Swal.fire({ icon:'error', title:'Erro', text: data.message || 'Erro ao processar.' });
                            btnProcess.disabled = false;
                            btnProcess.innerHTML = '<i class="fas fa-check-circle me-2"></i>Processar Movimentação';
                        }
                    })
                    .catch(function() {
                        Swal.fire({ icon:'error', title:'Erro de comunicação' });
                        btnProcess.disabled = false;
                        btnProcess.innerHTML = '<i class="fas fa-check-circle me-2"></i>Processar Movimentação';
                    });
            });
        });
    }

    function loadRecentMovements() {
        var container = document.getElementById('recentMovements');
        if (!container) return;
        container.innerHTML = '<div class="text-center text-muted small py-3"><i class="fas fa-spinner fa-spin me-1"></i>Carregando...</div>';

        fetch('?page=stock&action=movements&format=json&limit=10')
            .catch(function() { return null; })
            .then(function(r) { if (r && r.ok) return r.json(); return null; })
            .then(function(data) {
                if (!data || !Array.isArray(data) || data.length === 0) {
                    container.innerHTML = '<div class="text-center text-muted small py-3">Nenhuma movimentação recente.</div>';
                    return;
                }
                var icons = { entrada:'fas fa-arrow-down text-success', saida:'fas fa-arrow-up text-danger', ajuste:'fas fa-sliders-h text-warning', transferencia:'fas fa-truck text-info' };
                var html = '<div class="list-group list-group-flush">';
                data.forEach(function(m) {
                    html += '<div class="list-group-item px-3 py-2">' +
                        '<div class="d-flex justify-content-between">' +
                            '<span><i class="' + (icons[m.type] || 'fas fa-circle') + ' me-2" aria-hidden="true"></i><strong class="small">' + escHtml(m.product_name) + '</strong></span>' +
                            '<span class="badge ' + (m.type === 'entrada' ? 'bg-success' : m.type === 'saida' ? 'bg-danger' : 'bg-secondary') + '">' + (m.type === 'entrada' ? '+' : '-') + parseFloat(m.quantity).toFixed(0) + '</span>' +
                        '</div>' +
                        '<small class="text-muted">' + m.warehouse_name + ' · ' + new Date(m.created_at).toLocaleDateString('pt-BR') + '</small>' +
                    '</div>';
                });
                html += '</div>';
                container.innerHTML = html;
            });
    }
    loadRecentMovements();

    // ═══════════════════════════════════════════
    // ═══ ARMAZÉNS — CRUD                     ═══
    // ═══════════════════════════════════════════
    document.querySelectorAll('.btn-edit-wh').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.getElementById('whModalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Editar Armazém';
            document.getElementById('warehouseForm').action = '?page=stock&action=updateWarehouse';
            document.getElementById('wh_id').value = this.dataset.id;
            document.getElementById('wh_name').value = this.dataset.name;
            document.getElementById('wh_address').value = this.dataset.address;
            document.getElementById('wh_city').value = this.dataset.city;
            document.getElementById('wh_state').value = this.dataset.state;
            document.getElementById('wh_zip').value = this.dataset.zip;
            document.getElementById('wh_phone').value = this.dataset.phone;
            document.getElementById('wh_notes').value = this.dataset.notes;
            document.getElementById('wh_active').checked = this.dataset.active == '1';
            document.getElementById('wh_active_wrap').style.display = 'block';
            document.getElementById('wh_default').checked = this.dataset.default == '1';
            new bootstrap.Modal(document.getElementById('warehouseModal')).show();
        });
    });

    document.querySelectorAll('.btn-delete-wh').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.dataset.id;
            var name = this.dataset.name;
            Swal.fire({
                title: 'Excluir armazém?',
                html: 'Deseja remover <strong>' + name + '</strong>?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#c0392b',
                confirmButtonText: '<i class="fas fa-trash me-1"></i>Excluir',
                cancelButtonText: 'Cancelar'
            }).then(function(result) {
                if (result.isConfirmed) {
                    window.location.href = '?page=stock&action=deleteWarehouse&id=' + id;
                }
            });
        });
    });

    document.querySelectorAll('.btn-set-default-wh').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.dataset.id;
            var name = this.dataset.name;
            Swal.fire({
                title: 'Definir como padrão?',
                html: 'Deseja definir <strong>' + name + '</strong> como o armazém padrão?<br><small class="text-muted">O estoque será movimentado automaticamente por este armazém no pipeline.</small>',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#27ae60',
                confirmButtonText: '<i class="fas fa-star me-1"></i>Definir Padrão',
                cancelButtonText: 'Cancelar'
            }).then(function(result) {
                if (result.isConfirmed) {
                    var fd = new FormData();
                    fd.append('csrf_token', csrfToken);
                    fd.append('id', id);
                    fetch('?page=stock&action=setDefault', { method: 'POST', body: fd })
                        .then(function(r) { return r.json(); })
                        .then(function(data) {
                            if (data.success) {
                                Swal.fire({ icon:'success', title:'Armazém padrão definido!', timer:1500, showConfirmButton:false })
                                    .then(function() { window.location.href = '?page=stock&section=warehouses'; });
                            }
                        });
                }
            });
        });
    });

});

// Função global para abrir modal de novo armazém
function openNewWarehouse() {
    document.getElementById('whModalTitle').innerHTML = '<i class="fas fa-warehouse me-2"></i>Novo Armazém';
    document.getElementById('warehouseForm').action = '?page=stock&action=storeWarehouse';
    document.getElementById('wh_id').value = '';
    document.getElementById('wh_name').value = '';
    document.getElementById('wh_address').value = '';
    document.getElementById('wh_city').value = '';
    document.getElementById('wh_state').value = '';
    document.getElementById('wh_zip').value = '';
    document.getElementById('wh_phone').value = '';
    document.getElementById('wh_notes').value = '';
    document.getElementById('wh_active_wrap').style.display = 'none';
    document.getElementById('wh_default').checked = false;
}
