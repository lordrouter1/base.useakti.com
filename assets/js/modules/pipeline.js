/**
 * Pipeline Module — Akti ERP
 * Extracted from app/views/pipeline/index.php inline script
 * Handles: Drag-and-drop, search/filter, auto-expand, warehouse selection
 */
document.addEventListener('DOMContentLoaded', function() {

    // Config injected via data attributes on #pipelineApp
    var pipelineApp = document.getElementById('pipelineApp');
    var initStatus = pipelineApp ? (pipelineApp.dataset.status || '') : '';
    var delayedCount = pipelineApp ? parseInt(pipelineApp.dataset.delayedCount || '0', 10) : 0;

    // ═══════════════════════════════════════════
    // ═══ STATUS ALERTS                        ═══
    // ═══════════════════════════════════════════
    if (initStatus) {
        if (window.history.replaceState) {
            var url = new URL(window.location);
            url.searchParams.delete('status');
            window.history.replaceState({}, '', url);
        }
        if (initStatus === 'moved') {
            Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2000, timerProgressBar: true })
                .fire({ icon: 'success', title: 'Pedido movido com sucesso!' });
        } else if (initStatus === 'success') {
            Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2000, timerProgressBar: true })
                .fire({ icon: 'success', title: 'Operação realizada!' });
        }
    }

    // ═══════════════════════════════════════════
    // ═══ DELAYED ORDERS ALERT                 ═══
    // ═══════════════════════════════════════════
    if (delayedCount > 0) {
        Swal.fire({
            title: '<strong class="fs-3">Atenção!</strong>',
            toast: true,
            position: 'bottom-end',
            html: '<small><b>' + delayedCount + '</b> pedido(s) estão <strong class="text-light">atrasados</strong>!</small>',
            showCancelButton: false,
            confirmButtonText: '<span class="text-red"><i class="fas fa-eye me-1"></i> Ver Detalhes</span>',
            confirmButtonColor: '#ffffff',
            background: '#ef4444',
            color:'#ffffff',
            timer: 5000,
            timerProgressBar: true,
            customClass:{ popup: 'shadow' }
        }).then(function(result) {
            if (result.isConfirmed) {
                var modal = new bootstrap.Modal(document.getElementById('delayedModal'));
                modal.show();
            }
        });
    }

    // ══════════════════════════════════════════
    // ══ Search & Filter (client-side)       ══
    // ══════════════════════════════════════════
    var searchInput = document.getElementById('pipelineSearch');
    var priorityFilter = document.getElementById('pipelinePriorityFilter');

    function applyFilters() {
        var query = (searchInput ? searchInput.value : '').toLowerCase().trim();
        var prio = priorityFilter ? priorityFilter.value : '';

        document.querySelectorAll('.pipeline-card').forEach(function(card) {
            var orderId = (card.dataset.orderId || '').toLowerCase();
            var customer = (card.dataset.customer || '').toLowerCase();
            var cardPrio = card.dataset.priority || '';

            var matchSearch = !query || orderId.indexOf(query) !== -1 || customer.indexOf(query) !== -1;
            var matchPrio = !prio || cardPrio === prio;

            card.style.display = (matchSearch && matchPrio) ? '' : 'none';
        });

        document.querySelectorAll('.pipeline-column').forEach(function(col) {
            var badge = col.querySelector('.pipeline-column-header .badge.bg-white');
            var cards = col.querySelectorAll('.pipeline-card:not([style*="display: none"])');
            if (badge) badge.textContent = cards.length;

            var emptyState = col.querySelector('.pipeline-empty-state');
            var allCards = col.querySelectorAll('.pipeline-card');
            var visibleCards = col.querySelectorAll('.pipeline-card:not([style*="display: none"])');
            if (visibleCards.length === 0 && allCards.length > 0) {
                if (!emptyState) {
                    emptyState = document.createElement('div');
                    emptyState.className = 'pipeline-empty-state';
                    emptyState.textContent = 'Nenhum resultado';
                    col.querySelector('.pipeline-dropzone').appendChild(emptyState);
                } else {
                    emptyState.style.display = '';
                }
            } else if (emptyState && visibleCards.length > 0) {
                emptyState.style.display = 'none';
            }
        });
    }

    if (searchInput) {
        var searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(applyFilters, 200);
        });
    }
    if (priorityFilter) {
        priorityFilter.addEventListener('change', applyFilters);
    }

    // ══════════════════════════════════════════
    // ══ Pipeline Scroll Navigation           ══
    // ══════════════════════════════════════════
    (function initPipelineScroll() {
        var wrapper = document.getElementById('pipelineBoardWrapper');
        var board = document.getElementById('pipelineBoard');
        var navLeft = document.getElementById('pipelineNavLeft');
        var navRight = document.getElementById('pipelineNavRight');
        if (!wrapper || !board) return;

        function checkScroll() {
            var hasScroll = wrapper.scrollWidth > wrapper.clientWidth + 2;
            wrapper.classList.toggle('has-scroll', hasScroll);
            if (navLeft) navLeft.style.opacity = wrapper.scrollLeft > 10 ? '1' : '0.3';
            if (navRight) navRight.style.opacity = (wrapper.scrollLeft + wrapper.clientWidth < wrapper.scrollWidth - 10) ? '1' : '0.3';
        }

        if (navLeft) navLeft.addEventListener('click', function() { wrapper.scrollBy({ left: -250, behavior: 'smooth' }); });
        if (navRight) navRight.addEventListener('click', function() { wrapper.scrollBy({ left: 250, behavior: 'smooth' }); });

        wrapper.addEventListener('scroll', checkScroll);
        window.addEventListener('resize', checkScroll);
        checkScroll();

        document.querySelectorAll('.pipeline-minimap-item').forEach(function(item) {
            item.addEventListener('click', function() {
                var targetStage = this.dataset.target;
                var col = board.querySelector('.pipeline-column[data-stage-key="' + targetStage + '"]');
                if (col) {
                    col.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
                }
            });
        });
    })();

    // ══════════════════════════════════════════
    // ══ Drag-and-Drop com SortableJS         ══
    // ══════════════════════════════════════════
    var preProductionStages = ['contato', 'orcamento', 'venda'];
    var productionStages = ['producao', 'preparacao', 'envio', 'financeiro', 'concluido'];

    function needsWarehouseSelection(fromStage, toStage) {
        return preProductionStages.indexOf(fromStage) !== -1 && productionStages.indexOf(toStage) !== -1;
    }

    function needsStockReturn(fromStage, toStage) {
        return productionStages.indexOf(fromStage) !== -1 && preProductionStages.indexOf(toStage) !== -1;
    }

    var csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
    var csrfTokenValue = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';

    function escHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function performMoveAjax(orderId, newStage, warehouseId, evtItem, evtFrom) {
        var formData = new FormData();
        formData.append('order_id', orderId);
        formData.append('stage', newStage);
        formData.append('csrf_token', csrfTokenValue);
        if (warehouseId) formData.append('warehouse_id', warehouseId);

        return fetch('?page=pipeline&action=moveAjax', {
            method: 'POST',
            body: formData
        })
        .then(function(r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(function(data) {
            if (data.success) {
                evtItem.classList.add('pipeline-card-moved');
                setTimeout(function() { evtItem.classList.remove('pipeline-card-moved'); }, 1500);
                updateColumnCounts();
                if (data.stock_notes) {
                    Swal.fire({ icon: 'info', title: 'Estoque atualizado', text: data.stock_notes, timer: 3000, showConfirmButton: false });
                }
            } else if (data.needs_warehouse) {
                showWarehouseSelectionModal(orderId, newStage, evtItem, evtFrom);
            } else if (data.blocked_by_paid) {
                Swal.fire({
                    icon: 'error',
                    title: '<i class="fas fa-lock me-2"></i>Movimentação bloqueada',
                    html: '<p>' + (data.message || 'Existem parcelas já pagas.') + '</p>'
                        + '<p class="small text-muted mt-2">Estorne todos os pagamentos primeiro no módulo <strong>Financeiro</strong>.</p>',
                    confirmButtonText: '<i class="fas fa-external-link-alt me-1"></i> Ir para Financeiro',
                    showCancelButton: true,
                    cancelButtonText: 'Fechar',
                    confirmButtonColor: '#e74c3c'
                }).then(function(r) {
                    if (r.isConfirmed) {
                        window.open('?page=financial&action=payments', '_blank');
                    }
                });
                revertCard(evtItem, evtFrom);
            } else {
                Swal.fire({ icon: 'error', title: 'Erro', text: data.message || 'Não foi possível mover o pedido.', timer: 3000 });
                revertCard(evtItem, evtFrom);
            }
        })
        .catch(function(err) {
            console.error('moveAjax error:', err);
            Swal.fire({ icon: 'error', title: 'Erro', text: 'Erro de conexão ao mover pedido. ' + (err.message || ''), timer: 4000 });
            revertCard(evtItem, evtFrom);
        });
    }

    function revertCard(item, fromZone) {
        fromZone.appendChild(item);
        updateColumnCounts();
        refreshEmptyStates();
    }

    function refreshEmptyStates() {
        document.querySelectorAll('.pipeline-dropzone').forEach(function(dz) {
            var cards = dz.querySelectorAll('.pipeline-card');
            var emptyMsg = dz.querySelector('.pipeline-empty-state');
            if (cards.length === 0) {
                if (!emptyMsg) {
                    emptyMsg = document.createElement('div');
                    emptyMsg.className = 'pipeline-empty-state text-center text-muted py-4 small';
                    emptyMsg.innerHTML = '<i class="fas fa-inbox d-block mb-2" style="font-size: 1.5rem;"></i>Nenhum pedido';
                    dz.appendChild(emptyMsg);
                } else {
                    emptyMsg.style.display = '';
                }
            } else if (emptyMsg) {
                emptyMsg.style.display = 'none';
            }
        });
    }

    function showWarehouseSelectionModal(orderId, newStage, evtItem, evtFrom) {
        Swal.fire({
            title: '<i class="fas fa-warehouse me-2"></i>Selecionar Armazém',
            html: '<div class="text-center py-3"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i><br><small class="text-muted mt-2 d-block">Verificando estoque...</small></div>',
            showConfirmButton: false,
            showCancelButton: false,
            allowOutsideClick: false,
            didOpen: function() {
                fetch('?page=pipeline&action=checkOrderStock&order_id=' + orderId)
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (!data.success) {
                            Swal.fire({ icon: 'error', title: 'Erro', text: data.message || 'Erro ao verificar estoque.' });
                            revertCard(evtItem, evtFrom);
                            return;
                        }

                        var warehouseOptions = '';
                        if (data.warehouses && data.warehouses.length > 0) {
                            data.warehouses.forEach(function(w) {
                                var isDefault = (w.id == data.default_warehouse_id);
                                var selected = isDefault ? 'selected' : '';
                                var badge = isDefault ? ' ★ Padrão' : '';
                                warehouseOptions += '<option value="' + w.id + '" ' + selected + '>' + escHtml(w.name) + badge + '</option>';
                            });
                        }

                        var hasStockItems = false;
                        var itemsHtml = '';
                        if (data.items) {
                            data.items.forEach(function(item) {
                                if (item.use_stock_control) {
                                    hasStockItems = true;
                                    var icon = item.sufficient ? '<i class="fas fa-check-circle text-success" aria-hidden="true"></i>' : '<i class="fas fa-exclamation-triangle text-danger" aria-hidden="true"></i>';
                                    var label = item.combination_label ? escHtml(item.product_name) + ' — ' + escHtml(item.combination_label) : escHtml(item.product_name);
                                    var cls = item.sufficient ? 'text-success' : 'text-danger fw-bold';
                                    itemsHtml += '<tr><td class="small">' + icon + ' ' + label + '</td><td class="text-center small">' + item.quantity + '</td><td class="text-center small ' + cls + '">' + item.stock_available + '</td></tr>';
                                }
                            });
                        }

                        var html = '<p class="mb-2 small text-muted">O pedido está saindo da área comercial para produção. O estoque será deduzido automaticamente.</p>';
                        if (warehouseOptions) {
                            html += '<div class="mb-3 text-start"><label class="form-label small fw-bold"><i class="fas fa-warehouse me-1"></i>Armazém:</label>' +
                                '<select id="swalWarehouseSelect" class="form-select form-select-sm">' + warehouseOptions + '</select></div>';
                        }
                        if (hasStockItems) {
                            html += '<table class="table table-sm table-bordered mb-1" style="font-size:0.8rem;">' +
                                '<thead class="table-light"><tr><th>Produto</th><th class="text-center">Necessário</th><th class="text-center">Disponível</th></tr></thead>' +
                                '<tbody id="swalStockTableBody">' + itemsHtml + '</tbody></table>';
                            if (!data.all_from_stock) {
                                html += '<div class="alert alert-warning py-1 small mb-0"><i class="fas fa-exclamation-triangle me-1"></i><small>Alguns itens não possuem estoque suficiente.</small></div>';
                            }
                        } else {
                            html += '<div class="alert alert-light py-1 small mb-0"><i class="fas fa-info-circle me-1"></i><small>Nenhum item com controle de estoque ativo.</small></div>';
                        }

                        Swal.fire({
                            title: '<i class="fas fa-warehouse me-2"></i>Selecionar Armazém',
                            html: html,
                            showCancelButton: true,
                            confirmButtonText: '<i class="fas fa-check me-1"></i> Confirmar',
                            cancelButtonText: 'Cancelar',
                            confirmButtonColor: '#27ae60',
                            width: hasStockItems ? '500px' : undefined,
                            preConfirm: function() {
                                var whSelect = document.getElementById('swalWarehouseSelect');
                                return whSelect ? whSelect.value : null;
                            }
                        }).then(function(result) {
                            if (result.isConfirmed && result.value) {
                                performMoveAjax(orderId, newStage, result.value, evtItem, evtFrom);
                            } else {
                                revertCard(evtItem, evtFrom);
                            }
                        });

                        setTimeout(function() {
                            var whSelect = document.getElementById('swalWarehouseSelect');
                            if (whSelect) {
                                whSelect.addEventListener('change', function() {
                                    var whId = this.value;
                                    fetch('?page=pipeline&action=checkOrderStock&order_id=' + orderId + '&warehouse_id=' + whId)
                                        .then(function(r) { return r.json(); })
                                        .then(function(d) {
                                            if (d.success && d.items) {
                                                var tbody = document.getElementById('swalStockTableBody');
                                                if (tbody) {
                                                    var rows = '';
                                                    d.items.forEach(function(item) {
                                                        if (item.use_stock_control) {
                                                            var ic = item.sufficient ? '<i class="fas fa-check-circle text-success" aria-hidden="true"></i>' : '<i class="fas fa-exclamation-triangle text-danger" aria-hidden="true"></i>';
                                                            var lb = item.combination_label ? escHtml(item.product_name) + ' — ' + escHtml(item.combination_label) : escHtml(item.product_name);
                                                            var cl = item.sufficient ? 'text-success' : 'text-danger fw-bold';
                                                            rows += '<tr><td class="small">' + ic + ' ' + lb + '</td><td class="text-center small">' + item.quantity + '</td><td class="text-center small ' + cl + '">' + item.stock_available + '</td></tr>';
                                                        }
                                                    });
                                                    tbody.innerHTML = rows;
                                                }
                                            }
                                        });
                                });
                            }
                        }, 100);
                    })
                    .catch(function() {
                        Swal.fire({ icon: 'error', title: 'Erro', text: 'Não foi possível verificar o estoque.' });
                        revertCard(evtItem, evtFrom);
                    });
            }
        });
    }

    (function initDragAndDrop() {
        var dropzones = document.querySelectorAll('.pipeline-dropzone');

        dropzones.forEach(function(zone) {
            new Sortable(zone, {
                group: 'pipeline-orders',
                animation: 200,
                ghostClass: 'pipeline-card-ghost',
                chosenClass: 'pipeline-card-chosen',
                dragClass: 'pipeline-card-dragging',
                handle: '.card-body',
                filter: '.pipeline-empty-state, a',
                preventOnFilter: false,
                delay: 120,
                delayOnTouchOnly: true,
                fallbackOnBody: true,
                swapThreshold: 0.65,
                onStart: function() {
                    document.body.classList.add('pipeline-dragging');
                    document.querySelectorAll('.pipeline-empty-state').forEach(function(el) { el.style.display = 'none'; });
                },
                onEnd: function(evt) {
                    document.body.classList.remove('pipeline-dragging');
                    refreshEmptyStates();

                    var orderId = evt.item.dataset.orderId;
                    var newStage = evt.to.dataset.stage;
                    var oldStage = evt.from.dataset.stage;

                    if (newStage === oldStage) return;

                    updateColumnCounts();

                    if (needsWarehouseSelection(oldStage, newStage)) {
                        showWarehouseSelectionModal(orderId, newStage, evt.item, evt.from);
                    } else if (needsStockReturn(oldStage, newStage)) {
                        Swal.fire({
                            title: '<i class="fas fa-undo me-2 text-warning"></i>Devolver ao estoque?',
                            html: '<p>Ao retornar o pedido para a área comercial, os produtos deduzidos do estoque serão <strong>devolvidos automaticamente</strong> ao armazém.</p>',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: '<i class="fas fa-check me-1"></i> Confirmar',
                            cancelButtonText: 'Cancelar',
                            confirmButtonColor: '#e67e22'
                        }).then(function(result) {
                            if (result.isConfirmed) {
                                performMoveAjax(orderId, newStage, null, evt.item, evt.from);
                            } else {
                                revertCard(evt.item, evt.from);
                            }
                        });
                    } else {
                        performMoveAjax(orderId, newStage, null, evt.item, evt.from);
                    }
                }
            });
        });
    })();

    function updateColumnCounts() {
        document.querySelectorAll('.pipeline-column').forEach(function(col) {
            var badge = col.querySelector('.pipeline-column-header .badge.bg-white');
            var cards = col.querySelector('.pipeline-dropzone').querySelectorAll('.pipeline-card');
            if (badge) badge.textContent = cards.length;
        });
        autoExpandColumns();
    }

    // ══════════════════════════════════════════
    // ══ Auto-expand cards ≤ 4 orders         ══
    // ══════════════════════════════════════════
    var CARDS_THRESHOLD_COLLAPSE = 4;

    function autoExpandColumns() {
        document.querySelectorAll('.pipeline-column').forEach(function(col) {
            var allCards = col.querySelectorAll('.pipeline-card');
            var visibleCards = col.querySelectorAll('.pipeline-card:not([style*="display: none"])');
            var countForThreshold = visibleCards.length > 0 ? visibleCards.length : allCards.length;
            var shouldExpand = (countForThreshold <= CARDS_THRESHOLD_COLLAPSE);

            allCards.forEach(function(card) {
                if (card.dataset.manualToggle) return;
                if (shouldExpand) {
                    card.classList.add('pipeline-card-expanded');
                } else {
                    card.classList.remove('pipeline-card-expanded');
                }
            });
        });
    }

    autoExpandColumns();

    // ══════════════════════════════════════════
    // ══ Card collapse/expand toggle          ══
    // ══════════════════════════════════════════
    document.querySelectorAll('.pipeline-card-toggle').forEach(function(header) {
        header.addEventListener('click', function(e) {
            e.stopPropagation();
            var card = this.closest('.pipeline-card');
            card.classList.toggle('pipeline-card-expanded');
            card.dataset.manualToggle = '1';
        });
    });

    document.querySelectorAll('.pipeline-card').forEach(function(card) {
        card.addEventListener('mouseenter', function() {
            this.style.transition = 'transform 0.15s ease, box-shadow 0.15s ease';
        });
    });

    // ══════════════════════════════════════════
    // ══ Expand/Collapse All per Column       ══
    // ══════════════════════════════════════════
    document.querySelectorAll('.pipeline-expand-all-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            var col = this.closest('.pipeline-column');
            var cards = col.querySelectorAll('.pipeline-card');
            if (cards.length === 0) return;

            // Check if any card is collapsed (not expanded)
            var hasCollapsed = false;
            cards.forEach(function(card) {
                if (!card.classList.contains('pipeline-card-expanded')) {
                    hasCollapsed = true;
                }
            });

            var icon = this.querySelector('i');
            if (hasCollapsed) {
                // Expand all
                cards.forEach(function(card) {
                    card.classList.add('pipeline-card-expanded');
                    card.dataset.manualToggle = '1';
                });
                this.title = 'Recolher todos os cards';
                if (icon) { icon.className = 'fas fa-compress-alt'; }
            } else {
                // Collapse all
                cards.forEach(function(card) {
                    card.classList.remove('pipeline-card-expanded');
                    card.dataset.manualToggle = '1';
                });
                this.title = 'Expandir todos os cards';
                if (icon) { icon.className = 'fas fa-expand-alt'; }
            }
        });
    });
});
