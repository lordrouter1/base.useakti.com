/**
 * financial-payments.js
 * JavaScript do módulo financeiro — Página unificada com sidebar.
 *
 * Requer variáveis globais injetadas pelo PHP antes deste script:
 *   window.AktiFinancial = { statusMap, methodLabels, allCats, bankConfig }
 *
 * Dependências externas: Bootstrap 5, SweetAlert2 (Swal)
 *
 * @package Akti - Gestão em Produção
 * @since   Fase 2 — Refatoração financeira
 */
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {

        // ═══════════════════════════════════════════
        // DADOS PHP injetados via window.AktiFinancial
        // ═══════════════════════════════════════════
        var cfg = window.AktiFinancial || {};
        var statusMap    = cfg.statusMap || {};
        var methodLabels = cfg.methodLabels || {};
        var allCats      = cfg.allCats || {};
        var bankConfig   = cfg.bankConfig || {};
        var initialSection = cfg.initialSection || 'payments';
        var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        var activeGateways = cfg.activeGateways || [];

        // ═══════════════════════════════════════════
        // UTILIDADES
        // ═══════════════════════════════════════════
        function escHtml(str) {
            if (!str) return '';
            var div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }
        function formatCurrency(v) {
            return parseFloat(v || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
        function formatDateBR(dateStr) {
            if (!dateStr) return '—';
            var parts = dateStr.split('-');
            if (parts.length === 3) return parts[2] + '/' + parts[1] + '/' + parts[0];
            return dateStr;
        }

        var debounceTimers = {};
        function debounce(key, fn, delay) {
            clearTimeout(debounceTimers[key]);
            debounceTimers[key] = setTimeout(fn, delay || 400);
        }

        // ═══════════════════════════════════════════
        // SIDEBAR NAVIGATION (SPA-like)
        // ═══════════════════════════════════════════
        function navigateToSection(sectionId) {
            document.querySelectorAll('.fin-nav-item').forEach(function(n) { n.classList.remove('active'); });
            var navItem = document.querySelector('.fin-nav-item[data-section="' + sectionId + '"]');
            if (navItem) navItem.classList.add('active');

            document.querySelectorAll('.fin-section').forEach(function(s) { s.classList.remove('active'); });
            var target = document.getElementById('fin-' + sectionId);
            if (target) target.classList.add('active');

            var url = new URL(window.location);
            url.searchParams.set('section', sectionId);
            history.replaceState(null, '', url);

            if (sectionId === 'payments') loadPayments(1);
            if (sectionId === 'transactions') loadTransactions(1);
            if (sectionId === 'recurring') loadRecurring();
        }

        document.querySelectorAll('.fin-nav-item').forEach(function(item) {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                var section = this.dataset.section;
                if (!section) return;
                navigateToSection(section);
            });
        });

        // ═══════════════════════════════════════════
        // PAGAMENTOS — AJAX + Paginação
        // ═══════════════════════════════════════════
        var payPage = 1;

        function loadPayments(page) {
            payPage = page || 1;
            var params = new URLSearchParams({
                page: 'financial', action: 'getInstallmentsPaginated',
                pg: payPage,
                per_page: 25,
                status: document.getElementById('fPayStatus').value,
                month: document.getElementById('fPayMonth').value,
                year: document.getElementById('fPayYear').value,
                search: document.getElementById('fPaySearch').value,
            });

            document.getElementById('paymentsTableBody').innerHTML =
                '<tr><td colspan="9" class="text-center text-muted py-5"><i class="fas fa-spinner fa-spin fa-2x mb-2 d-block opacity-50"></i>Carregando...</td></tr>';

            fetch('?' + params.toString())
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success) return;

                // Update summary cards
                var s = data.summary || {};
                document.getElementById('cardPayTotal').textContent = (parseInt(s.total_parcelas) || 0);
                document.getElementById('cardPayPending').innerHTML = (parseInt(s.pendentes || 0) + parseInt(s.atrasadas || 0)) +
                    ' <small class="text-muted fs-6">(R$ ' + formatCurrency(s.valor_pendente) + ')</small>';
                document.getElementById('cardPayPaid').innerHTML = (parseInt(s.pagas) || 0) +
                    ' <small class="text-muted fs-6">(R$ ' + formatCurrency(s.valor_pago) + ')</small>';
                document.getElementById('cardPayAwaiting').textContent = (parseInt(s.aguardando) || 0);
                document.getElementById('payTotalBadge').textContent = data.total + ' registro(s)';

                // Build table
                var tbody = document.getElementById('paymentsTableBody');
                if (!data.items || data.items.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-5"><i class="fas fa-inbox fa-3x mb-2 d-block opacity-50"></i><div class="fw-bold">Nenhuma parcela encontrada</div></td></tr>';
                    document.getElementById('paymentsPagination').innerHTML = '';
                    return;
                }

                var html = '';
                data.items.forEach(function(inst) {
                    var st = statusMap[inst.status] || statusMap['pendente'];
                    var isEntrada = parseInt(inst.installment_number) === 0;
                    var orderId = inst.order_id;
                    var orderMethod = inst.order_payment_method || '';
                    var instMethod = inst.payment_method || '';
                    var displayMethod = instMethod || orderMethod;
                    var rowClass = inst.status === 'atrasado' ? ' class="table-danger"' : '';

                    html += '<tr' + rowClass + '>';
                    // Pedido
                    html += '<td class="ps-3 fw-bold"><a href="?page=pipeline&action=detail&id=' + orderId + '" class="text-decoration-none text-dark">#' + String(orderId).padStart(4, '0') + '</a></td>';
                    // Cliente
                    html += '<td class="small">' + escHtml(inst.customer_name || 'N/A') + '</td>';
                    // Parcela
                    if (isEntrada) {
                        html += '<td><span class="badge bg-info">Entrada</span></td>';
                    } else {
                        html += '<td><span class="fw-bold">' + inst.installment_number + 'ª</span></td>';
                    }
                    // Vencimento
                    html += '<td class="small">' + formatDateBR(inst.due_date);
                    if (inst.status === 'atrasado') {
                        var diasAtraso = Math.max(0, Math.round((Date.now() - new Date(inst.due_date + 'T12:00:00').getTime()) / 86400000));
                        html += ' <span class="badge bg-danger rounded-pill ms-1" style="font-size:0.6rem;">+' + diasAtraso + 'd</span>';
                    }
                    html += '</td>';
                    // Valor
                    html += '<td class="fw-bold">R$ ' + formatCurrency(inst.amount) + '</td>';
                    // Pago em
                    html += '<td class="small">' + (inst.paid_date ? formatDateBR(inst.paid_date) : '<span class="text-muted">—</span>') + '</td>';
                    // Valor Pago
                    if (inst.paid_amount) {
                        html += '<td><span class="fw-bold text-success">R$ ' + formatCurrency(inst.paid_amount) + '</span></td>';
                    } else {
                        html += '<td><span class="text-muted">—</span></td>';
                    }
                    // Status
                    html += '<td><span class="badge ' + st.badge + '"><i class="' + st.icon + ' me-1"></i>' + st.label + '</span></td>';
                    // Ações
                    html += '<td class="text-end pe-3"><div class="btn-group btn-group-sm">';
                    if (inst.status === 'pendente' || inst.status === 'atrasado') {
                        html += '<button type="button" class="btn btn-success btn-register-pay" data-id="' + inst.id + '" data-order-id="' + orderId + '" data-amount="' + inst.amount + '" data-number="' + inst.installment_number + '" data-customer="' + escHtml(inst.customer_name || '') + '" data-order-method="' + escHtml(orderMethod) + '" title="Registrar Pagamento"><i class="fas fa-hand-holding-usd me-1"></i>Pagar</button>';
                        if (activeGateways.length > 0) {
                            html += '<button type="button" class="btn btn-outline-primary btn-gateway-charge" data-id="' + inst.id + '" data-order-id="' + orderId + '" data-amount="' + inst.amount + '" data-number="' + inst.installment_number + '" data-customer="' + escHtml(inst.customer_name || '') + '" title="Cobrar via Gateway Online"><i class="fas fa-bolt"></i></button>';
                        }
                    } else if (inst.status === 'pago' && !inst.is_confirmed) {
                        html += '<button type="button" class="btn btn-outline-success btn-confirm-pay" data-id="' + inst.id + '" title="Confirmar"><i class="fas fa-check me-1"></i>Confirmar</button>';
                        html += '<button type="button" class="btn btn-outline-danger btn-cancel-pay" data-id="' + inst.id + '" data-order-id="' + orderId + '" title="Estornar"><i class="fas fa-undo"></i></button>';
                    } else if (inst.status === 'pago' && inst.is_confirmed) {
                        html += '<button type="button" class="btn btn-outline-danger btn-cancel-pay" data-id="' + inst.id + '" data-order-id="' + orderId + '" title="Estornar"><i class="fas fa-undo me-1"></i>Estornar</button>';
                    }
                    html += '<a href="?page=financial&action=installments&order_id=' + orderId + '" class="btn btn-outline-secondary" title="Gerenciar parcelas"><i class="fas fa-cog"></i></a>';
                    html += '</div></td>';
                    html += '</tr>';
                });
                tbody.innerHTML = html;

                // Pagination
                renderPagination('paymentsPagination', data.page, data.total_pages, data.total, function(p) { loadPayments(p); });

                // Attach event listeners to new buttons
                attachPaymentButtonListeners();
            })
            .catch(function(err) {
                console.error('Erro ao carregar pagamentos:', err);
            });
        }

        function attachPaymentButtonListeners() {
            // Register payment
            document.querySelectorAll('#paymentsTableBody .btn-register-pay').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var id = this.dataset.id, orderId = this.dataset.orderId;
                    var amount = parseFloat(this.dataset.amount), num = this.dataset.number;
                    var customer = this.dataset.customer || '', orderMethod = this.dataset.orderMethod || '';

                    document.getElementById('payInstId').value = id;
                    document.getElementById('payOrderId').value = orderId;
                    document.getElementById('payOrderDisplay').textContent = '#' + String(orderId).padStart(4, '0');
                    document.getElementById('payNumber').textContent = (num == 0) ? 'Entrada' : num + 'ª';
                    document.getElementById('payAmountDisplay').textContent = 'R$ ' + formatCurrency(amount);
                    document.getElementById('payAmountInput').value = amount.toFixed(2);
                    document.getElementById('payAmountInput').dataset.originalAmount = amount.toFixed(2);
                    document.getElementById('payCustomerDisplay').textContent = customer ? 'Cliente: ' + customer : '';

                    var methodSelect = document.getElementById('payMethodSelect');
                    if (orderMethod) {
                        methodSelect.value = orderMethod;
                        document.getElementById('payMethodHint').innerHTML = '<i class="fas fa-info-circle me-1"></i>Pré-selecionada do pedido.';
                    } else {
                        methodSelect.selectedIndex = 0;
                        document.getElementById('payMethodHint').textContent = '';
                    }

                    new bootstrap.Modal(document.getElementById('modalPay')).show();
                });
            });

            // Confirm payment
            document.querySelectorAll('#paymentsTableBody .btn-confirm-pay').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var id = this.dataset.id;
                    Swal.fire({
                        title: 'Confirmar pagamento?', text: 'A parcela será marcada como confirmada.', icon: 'question',
                        showCancelButton: true, confirmButtonColor: '#27ae60', cancelButtonColor: '#6c757d',
                        confirmButtonText: '<i class="fas fa-check-double me-1"></i>Confirmar', cancelButtonText: 'Cancelar'
                    }).then(function(result) {
                        if (result.isConfirmed) {
                            var fd = new FormData();
                            fd.append('installment_id', id);
                            fd.append('csrf_token', csrfToken);
                            fetch('?page=financial&action=confirmPayment', {
                                method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd
                            }).then(function(r) { return r.json(); }).then(function(data) {
                                if (data.success) {
                                    Swal.mixin({toast:true,position:'top-end',showConfirmButton:false,timer:2000,timerProgressBar:true}).fire({icon:'success',title:'Pagamento confirmado!'});
                                    loadPayments(payPage);
                                }
                            });
                        }
                    });
                });
            });

            // Cancel/reverse payment
            document.querySelectorAll('#paymentsTableBody .btn-cancel-pay').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var id = this.dataset.id;
                    Swal.fire({
                        title: 'Estornar pagamento?',
                        html: 'O pagamento será <strong>revertido</strong> e registrado como saída no livro caixa.',
                        icon: 'warning', showCancelButton: true, confirmButtonColor: '#e74c3c', cancelButtonColor: '#6c757d',
                        confirmButtonText: '<i class="fas fa-undo me-1"></i>Estornar', cancelButtonText: 'Manter'
                    }).then(function(result) {
                        if (result.isConfirmed) {
                            var fd = new FormData();
                            fd.append('installment_id', id);
                            fd.append('csrf_token', csrfToken);
                            fetch('?page=financial&action=cancelInstallment', {
                                method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd
                            }).then(function(r) { return r.json(); }).then(function(data) {
                                if (data.success) {
                                    Swal.mixin({toast:true,position:'top-end',showConfirmButton:false,timer:2000,timerProgressBar:true}).fire({icon:'success',title:'Pagamento estornado!'});
                                    loadPayments(payPage);
                                }
                            });
                        }
                    });
                });
            });

            // Gateway charge
            document.querySelectorAll('#paymentsTableBody .btn-gateway-charge').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var id = this.dataset.id, orderId = this.dataset.orderId;
                    var amount = parseFloat(this.dataset.amount), num = this.dataset.number;
                    var customer = this.dataset.customer || '';

                    document.getElementById('gwChargeInstId').value = id;
                    document.getElementById('gwChargeOrderId').value = orderId;
                    document.getElementById('gwChargeOrderDisplay').textContent = '#' + String(orderId).padStart(4, '0');
                    document.getElementById('gwChargeNumber').textContent = (num == 0) ? 'Entrada' : num + 'ª';
                    document.getElementById('gwChargeAmountDisplay').textContent = 'R$ ' + formatCurrency(amount);
                    document.getElementById('gwChargeCustomerDisplay').textContent = customer ? 'Cliente: ' + customer : '';
                    document.getElementById('gwChargeResult').style.display = 'none';
                    document.getElementById('gwChargeResult').innerHTML = '';

                    new bootstrap.Modal(document.getElementById('modalGatewayCharge')).show();
                });
            });
        }

        // Payment form submit
        var formPay = document.getElementById('formPay');
        if (formPay) {
            formPay.addEventListener('submit', function(e) {
                e.preventDefault();
                var form = this;
                var paidAmount = parseFloat(document.getElementById('payAmountInput').value) || 0;
                var originalAmount = parseFloat(document.getElementById('payAmountInput').dataset.originalAmount) || paidAmount;

                if (paidAmount > 0 && paidAmount < originalAmount) {
                    var restante = (originalAmount - paidAmount).toFixed(2);
                    Swal.fire({
                        title: 'Pagamento parcial detectado',
                        html: '<div class="text-start"><p>Pago: <strong>R$ ' + formatCurrency(paidAmount) + '</strong> de <strong>R$ ' + formatCurrency(originalAmount) + '</strong></p>' +
                              '<p>Restante: <strong class="text-danger">R$ ' + formatCurrency(restante) + '</strong></p><hr>' +
                              '<p><strong>Criar parcela para o valor restante?</strong></p>' +
                              '<div class="mb-3"><label class="form-label small fw-bold">Vencimento:</label>' +
                              '<input type="date" id="swalRemainingDueDate" class="form-control form-control-sm" value="' + getDefaultDueDate() + '"></div></div>',
                        icon: 'question', showCancelButton: true, showDenyButton: true,
                        confirmButtonColor: '#27ae60', denyButtonColor: '#3085d6', cancelButtonColor: '#6c757d',
                        confirmButtonText: '<i class="fas fa-plus-circle me-1"></i>Sim, criar parcela',
                        denyButtonText: '<i class="fas fa-check me-1"></i>Não, quitar assim',
                        cancelButtonText: 'Cancelar'
                    }).then(function(result) {
                        if (result.isConfirmed) submitPaymentAjax(form, 1, document.getElementById('swalRemainingDueDate')?.value || '');
                        else if (result.isDenied) submitPaymentAjax(form, 0, '');
                    });
                } else {
                    Swal.fire({
                        title: 'Confirmar pagamento?', icon: 'question', showCancelButton: true,
                        confirmButtonColor: '#27ae60', cancelButtonColor: '#6c757d',
                        confirmButtonText: '<i class="fas fa-check me-1"></i>Confirmar', cancelButtonText: 'Cancelar'
                    }).then(function(result) {
                        if (result.isConfirmed) submitPaymentAjax(form, 0, '');
                    });
                }
            });
        }

        function getDefaultDueDate() {
            var d = new Date(); d.setDate(d.getDate() + 30);
            return d.toISOString().split('T')[0];
        }

        function submitPaymentAjax(form, createRemaining, remainingDueDate) {
            var formData = new FormData(form);
            formData.append('create_remaining', createRemaining);
            if (remainingDueDate) formData.append('remaining_due_date', remainingDueDate);

            var btn = document.getElementById('btnSubmitPay');
            if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Processando...'; }

            fetch('?page=financial&action=payInstallment', {
                method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken }, body: formData
            }).then(function(r) { return r.json(); }).then(function(data) {
                if (data.success) {
                    var modal = bootstrap.Modal.getInstance(document.getElementById('modalPay'));
                    if (modal) modal.hide();
                    var msg = data.remaining_created ? 'Pagamento registrado! Parcela restante de R$ ' + formatCurrency(data.remaining_amount) + ' criada.' : 'Pagamento registrado e confirmado!';
                    Swal.mixin({toast:true,position:'top-end',showConfirmButton:false,timer:3000,timerProgressBar:true}).fire({icon:'success',title:msg});
                    loadPayments(payPage);
                } else {
                    Swal.fire({ icon: 'error', title: 'Erro', text: data.message || 'Erro ao processar pagamento.' });
                }
                if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-check me-1"></i>Registrar Pagamento'; }
            }).catch(function() {
                Swal.fire({ icon: 'error', title: 'Erro de Conexão', text: 'Tente novamente.' });
                if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-check me-1"></i>Registrar Pagamento'; }
            });
        }

        // Dynamic filters for payments
        ['fPayStatus', 'fPayMonth', 'fPayYear'].forEach(function(id) {
            document.getElementById(id)?.addEventListener('change', function() { loadPayments(1); });
        });
        document.getElementById('fPaySearch')?.addEventListener('input', function() {
            debounce('paySearch', function() { loadPayments(1); }, 400);
        });


        // ═══════════════════════════════════════════
        // TRANSAÇÕES — AJAX + Paginação
        // ═══════════════════════════════════════════
        var txPage = 1;

        function loadTransactions(page) {
            txPage = page || 1;
            var params = new URLSearchParams({
                page: 'financial', action: 'getTransactionsPaginated',
                pg: txPage, per_page: 25,
                type: document.getElementById('fTxType').value,
                category: document.getElementById('fTxCategory').value,
                month: document.getElementById('fTxMonth').value,
                year: document.getElementById('fTxYear').value,
                search: document.getElementById('fTxSearch').value,
            });

            document.getElementById('txTableBody').innerHTML =
                '<tr><td colspan="7" class="text-center text-muted py-5"><i class="fas fa-spinner fa-spin fa-2x mb-2 d-block opacity-50"></i>Carregando...</td></tr>';

            fetch('?' + params.toString())
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success) return;

                var entradas = parseFloat(data.totalEntradas) || 0;
                var saidas = parseFloat(data.totalSaidas) || 0;
                var saldo = entradas - saidas;

                document.getElementById('cardTxEntradas').textContent = 'R$ ' + formatCurrency(entradas);
                document.getElementById('cardTxSaidas').textContent = 'R$ ' + formatCurrency(saidas);
                var saldoEl = document.getElementById('cardTxSaldo');
                saldoEl.textContent = 'R$ ' + formatCurrency(saldo);
                saldoEl.className = 'fw-bold fs-5 ' + (saldo >= 0 ? 'text-primary' : 'text-danger');
                document.getElementById('txTotalBadge').textContent = data.total + ' registro(s)';

                var tbody = document.getElementById('txTableBody');
                if (!data.items || data.items.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-5"><i class="fas fa-inbox fa-3x mb-2 d-block opacity-50"></i><div class="fw-bold">Nenhuma transação encontrada</div></td></tr>';
                    document.getElementById('txPagination').innerHTML = '';
                    return;
                }

                var html = '';
                data.items.forEach(function(t) {
                    var isRegistro = (t.type === 'registro' || t.category === 'estorno_pagamento' || t.category === 'registro_ofx');
                    var rowClass = isRegistro ? ' class="table-light"' : '';

                    html += '<tr' + rowClass + '>';
                    html += '<td class="ps-3 small">' + formatDateBR(t.transaction_date) + '</td>';
                    // Type badge
                    if (isRegistro) {
                        html += '<td><span class="badge bg-secondary"><i class="fas fa-minus me-1"></i>' + (t.category === 'estorno_pagamento' ? 'Estorno' : 'Registro') + '</span></td>';
                    } else if (t.type === 'entrada') {
                        html += '<td><span class="badge bg-success"><i class="fas fa-arrow-down me-1"></i>Entrada</span></td>';
                    } else {
                        html += '<td><span class="badge bg-danger"><i class="fas fa-arrow-up me-1"></i>Saída</span></td>';
                    }
                    html += '<td class="small">' + escHtml(allCats[t.category] || t.category) + '</td>';
                    html += '<td class="small">' + escHtml(t.description) + '</td>';
                    // Value
                    var valClass = isRegistro ? 'text-secondary' : (t.type === 'entrada' ? 'text-success' : 'text-danger');
                    var valPrefix = isRegistro ? '—' : (t.type === 'entrada' ? '+' : '-');
                    html += '<td class="fw-bold ' + valClass + '">' + valPrefix + ' R$ ' + formatCurrency(t.amount) + '</td>';
                    html += '<td class="small">' + (methodLabels[t.payment_method] || (t.payment_method ? t.payment_method : '—')) + '</td>';
                    // Actions
                    html += '<td class="text-end pe-3">';
                    if (!t.reference_type || t.reference_type === 'manual') {
                        html += '<button class="btn btn-sm btn-outline-danger btn-delete-tx" data-id="' + t.id + '" title="Excluir"><i class="fas fa-trash"></i></button>';
                    } else {
                        html += '<span class="badge bg-light text-muted border" style="font-size:0.65rem;">' + (isRegistro ? 'Registro' : 'Automática') + '</span>';
                    }
                    html += '</td></tr>';
                });
                tbody.innerHTML = html;

                renderPagination('txPagination', data.page, data.total_pages, data.total, function(p) { loadTransactions(p); });
                attachTxButtonListeners();
            })
            .catch(function(err) { console.error('Erro ao carregar transações:', err); });
        }

        function attachTxButtonListeners() {
            document.querySelectorAll('#txTableBody .btn-delete-tx').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var id = this.dataset.id;
                    Swal.fire({
                        title: 'Excluir transação?', text: 'Essa ação não pode ser desfeita.', icon: 'warning',
                        showCancelButton: true, confirmButtonColor: '#e74c3c', cancelButtonColor: '#6c757d',
                        confirmButtonText: '<i class="fas fa-trash me-1"></i>Excluir', cancelButtonText: 'Manter'
                    }).then(function(result) {
                        if (result.isConfirmed) {
                            var fd = new FormData();
                            fd.append('transaction_id', id);
                            fd.append('csrf_token', csrfToken);
                            fetch('?page=financial&action=deleteTransaction', {
                                method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd
                            }).then(function(r) { return r.json(); }).then(function(data) {
                                if (data.success) {
                                    Swal.mixin({toast:true,position:'top-end',showConfirmButton:false,timer:2000,timerProgressBar:true}).fire({icon:'success',title:'Transação removida!'});
                                    loadTransactions(txPage);
                                }
                            });
                        }
                    });
                });
            });
        }

        // Dynamic filters for transactions
        ['fTxType', 'fTxCategory', 'fTxMonth', 'fTxYear'].forEach(function(id) {
            document.getElementById(id)?.addEventListener('change', function() { loadTransactions(1); });
        });
        document.getElementById('fTxSearch')?.addEventListener('input', function() {
            debounce('txSearch', function() { loadTransactions(1); }, 400);
        });


        // ═══════════════════════════════════════════
        // NOVA TRANSAÇÃO — Filtro categorias por tipo
        // ═══════════════════════════════════════════
        var newTxType = document.getElementById('newTxType');
        var newTxCat = document.getElementById('newTxCategory');
        if (newTxType && newTxCat) {
            function filterNewTxCats() {
                var type = newTxType.value;
                var defaultCat = type === 'entrada' ? 'outra_entrada' : 'outra_saida';
                newTxCat.querySelectorAll('option').forEach(function(opt) {
                    var show = opt.dataset.type === type;
                    opt.style.display = show ? '' : 'none';
                });
                var defaultOpt = newTxCat.querySelector('option[value="' + defaultCat + '"]');
                if (defaultOpt) newTxCat.value = defaultCat;
            }
            newTxType.addEventListener('change', filterNewTxCats);
            filterNewTxCats();
        }

        // Submit new transaction
        document.getElementById('formNewTransaction')?.addEventListener('submit', function(e) {
            e.preventDefault();
            var form = this;
            Swal.fire({
                title: 'Registrar transação?', icon: 'question', showCancelButton: true,
                confirmButtonColor: '#27ae60', cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-check me-1"></i>Registrar', cancelButtonText: 'Cancelar'
            }).then(function(result) {
                if (result.isConfirmed) form.submit();
            });
        });


        // ═══════════════════════════════════════════
        // IMPORTAÇÃO — OFX/CSV/Excel (Dropzone + 3 Steps)
        // ═══════════════════════════════════════════
        var importDropzone   = document.getElementById('importDropzone');
        var importFileInput  = document.getElementById('importFileInput');
        var importFileInfo   = document.getElementById('importFileInfo');
        var btnParseFile     = document.getElementById('btnParseFile');
        var btnRemoveFile    = document.getElementById('btnRemoveFile');
        var btnImportBack    = document.getElementById('btnImportBack');
        var btnImportConfirm = document.getElementById('btnImportConfirm');
        var btnNewImport     = document.getElementById('btnNewImport');

        var importData = { file_type: null, rows: [], headers: [], columns: [], preview: [], auto_mapping: {}, selectedRows: new Set() };

        // ─── Step navigation ───
        function goToImportStep(step) {
            document.querySelectorAll('.import-step').forEach(function(s) { s.classList.remove('active'); });
            var stepEl = document.getElementById('importStep' + step);
            if (stepEl) stepEl.classList.add('active');

            document.querySelectorAll('.import-step-indicator').forEach(function(ind) {
                var badge = ind.querySelector('.badge');
                if (parseInt(ind.dataset.step) <= step) {
                    badge.classList.remove('bg-secondary');
                    badge.classList.add('bg-primary');
                } else {
                    badge.classList.remove('bg-primary');
                    badge.classList.add('bg-secondary');
                }
            });
        }

        // ─── Dropzone: click to select ───
        if (importDropzone) {
            importDropzone.addEventListener('click', function() {
                importFileInput.click();
            });

            // Drag & drop
            importDropzone.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('dragover');
            });
            importDropzone.addEventListener('dragleave', function() {
                this.classList.remove('dragover');
            });
            importDropzone.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('dragover');
                if (e.dataTransfer.files.length > 0) {
                    importFileInput.files = e.dataTransfer.files;
                    handleImportFileSelected();
                }
            });
        }

        // File input change
        if (importFileInput) {
            importFileInput.addEventListener('change', handleImportFileSelected);
        }

        function handleImportFileSelected() {
            var file = importFileInput.files[0];
            if (!file) return;

            var validExts = ['.ofx', '.ofc', '.csv', '.txt', '.xls', '.xlsx'];
            var ext = file.name.substring(file.name.lastIndexOf('.')).toLowerCase();
            if (!validExts.includes(ext)) {
                Swal.fire({ icon: 'error', title: 'Formato inválido', text: 'Use arquivos OFX, CSV, TXT, XLS ou XLSX.' });
                return;
            }

            document.getElementById('importFileName').textContent = file.name;
            document.getElementById('importFileSize').textContent = formatImportFileSize(file.size);
            importFileInfo.style.display = '';
            importDropzone.classList.add('has-file');
            btnParseFile.disabled = false;
        }

        // Remove file
        if (btnRemoveFile) {
            btnRemoveFile.addEventListener('click', function() {
                importFileInput.value = '';
                importFileInfo.style.display = 'none';
                importDropzone.classList.remove('has-file');
                btnParseFile.disabled = true;
            });
        }

        function formatImportFileSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
        }

        // ─── Parse file (Step 1 → Step 2) ───
        if (btnParseFile) {
            btnParseFile.addEventListener('click', function() {
                var file = importFileInput.files[0];
                if (!file) {
                    Swal.fire({ icon: 'warning', title: 'Selecione um arquivo.' });
                    return;
                }

                var btn = this;
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Analisando...';

                var fd = new FormData();
                fd.append('import_file', file);
                fd.append('csrf_token', csrfToken);

                fetch('?page=financial&action=parseImportFile', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken },
                    body: fd
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-cog me-1"></i>Analisar Arquivo';

                    if (!data.success) {
                        Swal.fire({ icon: 'error', title: 'Erro', text: data.message });
                        return;
                    }

                    importData.file_type = data.file_type;
                    importData.rows      = data.rows;
                    importData.headers   = data.headers || [];
                    importData.columns   = data.columns || [];
                    importData.preview   = data.preview || [];
                    importData.auto_mapping = data.auto_mapping || {};
                    importData.selectedRows = new Set();

                    // Update badges
                    var fileTypeBadge = document.getElementById('importFileTypeBadge');
                    var fileTypeLabel = document.getElementById('importFileType');
                    var totalRowsBadge = document.getElementById('totalRowsBadge');
                    if (fileTypeBadge) fileTypeBadge.textContent = data.file_type.toUpperCase();
                    if (fileTypeLabel) fileTypeLabel.textContent = data.file_type.toUpperCase();
                    if (totalRowsBadge) totalRowsBadge.textContent = data.total_rows + ' linha(s)';
                    document.getElementById('importRowCount').textContent = data.total_rows + ' linhas';

                    // Show/hide CSV mapping table
                    var mappingSection = document.getElementById('csvMappingSection');
                    if (data.file_type === 'csv') {
                        mappingSection.classList.remove('d-none');
                        buildFinMappingTable(data.columns, data.preview, data.auto_mapping);
                        validateFinMapping();
                    } else {
                        mappingSection.classList.add('d-none');
                    }

                    // Build preview table
                    buildImportPreviewTable(data);

                    // Go to step 2
                    goToImportStep(2);
                })
                .catch(function(err) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-cog me-1"></i>Analisar Arquivo';
                    console.error('Parsing failed:', err);
                    Swal.fire({ icon: 'error', title: 'Erro', text: 'Falha ao analisar o arquivo. Verifique o formato.' });
                });
            });
        }

        // ─── Financial import field options (matching backend getFinancialImportFields) ───
        var finImportFieldOptions = {
            'date':           { label: 'Data',                  required: true  },
            'description':    { label: 'Descrição',             required: true  },
            'amount':         { label: 'Valor',                 required: true  },
            'type':           { label: 'Tipo (Entrada/Saída)',  required: false },
            'category':       { label: 'Categoria',             required: false },
            'payment_method': { label: 'Método de Pagamento',   required: false },
            'notes':          { label: 'Observações',           required: false }
        };

        // ─── Build mapping table (like products) ───
        function buildFinMappingTable(columns, preview, autoMapping) {
            var tbody = document.getElementById('finMappingTableBody');
            var html = '';

            columns.forEach(function(col) {
                // Sample: first 3 non-empty values
                var samples = [];
                for (var i = 0; i < Math.min(preview.length, 3); i++) {
                    var val = preview[i][col];
                    if (val && String(val).trim() !== '') {
                        samples.push(String(val).trim());
                    }
                }
                var sampleHtml = samples.length > 0
                    ? samples.map(function(s) { return '<span class="badge bg-light text-dark border me-1" style="font-size:.7rem;">' + escHtml(s.substring(0, 40)) + '</span>'; }).join('')
                    : '<span class="text-muted" style="font-size:.7rem;">—</span>';

                var autoVal = autoMapping[col] || '';

                // Build select options
                var optionsHtml = '<option value="_skip"' + (autoVal === '' ? ' selected' : '') + '>— Ignorar coluna —</option>';
                for (var field in finImportFieldOptions) {
                    var info = finImportFieldOptions[field];
                    var isReq = info.required ? ' *' : '';
                    optionsHtml += '<option value="' + field + '"' + (autoVal === field ? ' selected' : '') + '>' +
                        info.label + isReq + '</option>';
                }

                html += '<tr>' +
                    '<td class="text-center"><input type="checkbox" class="form-check-input fin-col-check" data-col="' + escHtml(col) + '" checked></td>' +
                    '<td><strong style="font-size:.82rem;"><i class="fas fa-columns me-1 text-muted"></i>' + escHtml(col) + '</strong></td>' +
                    '<td>' + sampleHtml + '</td>' +
                    '<td><select class="form-select mapping-select fin-mapping-select" data-col="' + escHtml(col) + '">' + optionsHtml + '</select></td>' +
                '</tr>';
            });

            tbody.innerHTML = html;

            // Event listeners for validation
            tbody.querySelectorAll('.fin-mapping-select').forEach(function(sel) {
                sel.addEventListener('change', validateFinMapping);
            });
            tbody.querySelectorAll('.fin-col-check').forEach(function(chk) {
                chk.addEventListener('change', function() {
                    var row = this.closest('tr');
                    var sel = row.querySelector('.fin-mapping-select');
                    if (!this.checked) {
                        sel.value = '_skip';
                        sel.disabled = true;
                    } else {
                        sel.disabled = false;
                    }
                    validateFinMapping();
                });
            });

            // Check all
            var checkAll = document.getElementById('finCheckAllCols');
            if (checkAll) {
                checkAll.addEventListener('change', function() {
                    var checked = this.checked;
                    tbody.querySelectorAll('.fin-col-check').forEach(function(chk) {
                        chk.checked = checked;
                        chk.dispatchEvent(new Event('change'));
                    });
                });
            }
        }

        // ─── Get current mapping as { "Column Name": "field_key" } ───
        function getFinMapping() {
            var mapping = {};
            document.querySelectorAll('#finMappingTableBody .fin-mapping-select').forEach(function(sel) {
                var col = sel.dataset.col;
                var val = sel.value;
                if (val && val !== '_skip') {
                    mapping[col] = val;
                }
            });
            return mapping;
        }

        // ─── Validate financial mapping ───
        function validateFinMapping() {
            var validationEl = document.getElementById('mappingValidation');
            if (!validationEl) return;
            var mapping = getFinMapping();
            var mappedFields = Object.values(mapping).filter(function(v) { return v !== '_skip'; });

            var warnings = [];
            var errors = [];

            // Check required fields
            if (!mappedFields.includes('date')) {
                errors.push('<i class="fas fa-times-circle text-danger me-1"></i><strong>Data</strong> é obrigatória. Mapeie uma coluna para este campo.');
            }
            if (!mappedFields.includes('description')) {
                errors.push('<i class="fas fa-times-circle text-danger me-1"></i><strong>Descrição</strong> é obrigatória. Mapeie uma coluna para este campo.');
            }
            if (!mappedFields.includes('amount')) {
                errors.push('<i class="fas fa-times-circle text-danger me-1"></i><strong>Valor</strong> é obrigatório. Mapeie uma coluna para este campo.');
            }

            // Check duplicate mappings
            var fieldCount = {};
            mappedFields.forEach(function(f) {
                fieldCount[f] = (fieldCount[f] || 0) + 1;
            });
            for (var f in fieldCount) {
                if (fieldCount[f] > 1) {
                    var label = finImportFieldOptions[f] ? finImportFieldOptions[f].label : f;
                    warnings.push('<i class="fas fa-exclamation-triangle text-warning me-1"></i>O campo <strong>' + label + '</strong> está mapeado para mais de uma coluna.');
                }
            }

            if (errors.length > 0 || warnings.length > 0) {
                var html = '';
                errors.forEach(function(e) { html += '<div class="alert alert-danger py-1 mb-1 small">' + e + '</div>'; });
                warnings.forEach(function(w) { html += '<div class="alert alert-warning py-1 mb-1 small">' + w + '</div>'; });
                validationEl.innerHTML = html;
                validationEl.style.display = '';
            } else {
                validationEl.innerHTML = '<div class="alert alert-success py-1 mb-1 small"><i class="fas fa-check-circle text-success me-1"></i>Mapeamento válido! Pronto para importar.</div>';
                validationEl.style.display = '';
            }

            // Enable/disable import button based on mapping validity (for CSV files)
            if (importData.file_type === 'csv' && btnImportConfirm) {
                btnImportConfirm.disabled = errors.length > 0;
            }
        }

        // ─── Build preview table ───
        function buildImportPreviewTable(data) {
            var thead = document.getElementById('importPreviewHead');
            var tbody = document.getElementById('importPreviewBody');
            var skipFirst = document.getElementById('skipFirstRow').checked;

            var headHtml = '<th style="width:40px;"><input type="checkbox" id="checkAllRows" checked></th>';

            if (data.file_type === 'ofx') {
                headHtml += '<th class="small">Data</th><th class="small">Descrição</th><th class="small">Valor</th><th class="small">Tipo</th><th class="small">FITID</th>';
                thead.innerHTML = headHtml;

                var bodyHtml = '';
                data.rows.forEach(function(row, idx) {
                    importData.selectedRows.add(idx);
                    bodyHtml += '<tr>';
                    bodyHtml += '<td><input type="checkbox" class="import-row-check" data-idx="' + idx + '" checked></td>';
                    bodyHtml += '<td class="small">' + escHtml(row.date) + '</td>';
                    bodyHtml += '<td class="small">' + escHtml(row.description) + '</td>';
                    bodyHtml += '<td class="small fw-bold ' + (row.amount >= 0 ? 'text-success' : 'text-danger') + '">R$ ' + formatCurrency(Math.abs(row.amount)) + '</td>';
                    bodyHtml += '<td class="small">' + (row.amount >= 0 ? '<span class="badge bg-success-subtle text-success">Crédito</span>' : '<span class="badge bg-danger-subtle text-danger">Débito</span>') + '</td>';
                    bodyHtml += '<td class="small text-muted">' + escHtml(row.fitid || '') + '</td>';
                    bodyHtml += '</tr>';
                });
                tbody.innerHTML = bodyHtml;
            } else {
                // CSV: show all columns
                if (data.headers && data.headers.length > 0) {
                    data.headers.forEach(function(h) {
                        headHtml += '<th class="small">' + escHtml(h) + '</th>';
                    });
                } else if (data.rows.length > 0) {
                    data.rows[0].forEach(function(_, idx) {
                        headHtml += '<th class="small">Col ' + (idx + 1) + '</th>';
                    });
                }
                thead.innerHTML = headHtml;

                var bodyHtml = '';
                data.rows.forEach(function(row, idx) {
                    var isHeader = (idx === 0 && skipFirst);
                    if (!isHeader) importData.selectedRows.add(idx);
                    bodyHtml += '<tr class="' + (isHeader ? 'table-secondary' : '') + '">';
                    bodyHtml += '<td><input type="checkbox" class="import-row-check" data-idx="' + idx + '" ' + (!isHeader ? 'checked' : '') + '></td>';
                    row.forEach(function(cell) {
                        bodyHtml += '<td class="small" title="' + escHtml(String(cell)) + '">' + escHtml(String(cell).substring(0, 60)) + '</td>';
                    });
                    bodyHtml += '</tr>';
                });
                tbody.innerHTML = bodyHtml;
            }

            updateImportSelectedCount();

            // Attach checkbox listeners
            document.querySelectorAll('.import-row-check').forEach(function(cb) {
                cb.addEventListener('change', function() {
                    var idx = parseInt(this.dataset.idx);
                    if (this.checked) importData.selectedRows.add(idx);
                    else importData.selectedRows.delete(idx);
                    updateImportSelectedCount();
                });
            });

            var checkAllRows = document.getElementById('checkAllRows');
            if (checkAllRows) {
                checkAllRows.addEventListener('change', function() {
                    var checked = this.checked;
                    document.querySelectorAll('.import-row-check').forEach(function(cb) {
                        cb.checked = checked;
                        var idx = parseInt(cb.dataset.idx);
                        if (checked) importData.selectedRows.add(idx);
                        else importData.selectedRows.delete(idx);
                    });
                    updateImportSelectedCount();
                });
            }
        }

        function updateImportSelectedCount() {
            document.getElementById('selectedCount').textContent = importData.selectedRows.size;
            var countLabel = document.getElementById('importCountLabel');
            if (countLabel) countLabel.textContent = importData.selectedRows.size;
        }

        // Select/Deselect all buttons
        document.getElementById('btnSelectAll')?.addEventListener('click', function() {
            document.querySelectorAll('.import-row-check').forEach(function(cb) {
                cb.checked = true;
                importData.selectedRows.add(parseInt(cb.dataset.idx));
            });
            var checkAll = document.getElementById('checkAllRows');
            if (checkAll) checkAll.checked = true;
            updateImportSelectedCount();
        });

        document.getElementById('btnDeselectAll')?.addEventListener('click', function() {
            document.querySelectorAll('.import-row-check').forEach(function(cb) {
                cb.checked = false;
                importData.selectedRows.delete(parseInt(cb.dataset.idx));
            });
            var checkAll = document.getElementById('checkAllRows');
            if (checkAll) checkAll.checked = false;
            updateImportSelectedCount();
        });

        // Skip first row toggle
        document.getElementById('skipFirstRow')?.addEventListener('change', function() {
            if (importData.rows.length > 0) {
                buildImportPreviewTable({
                    file_type: importData.file_type,
                    rows: importData.rows,
                    headers: importData.headers,
                    total_rows: importData.rows.length
                });
            }
        });

        // Back button (Step 2 → Step 1)
        if (btnImportBack) {
            btnImportBack.addEventListener('click', function() {
                goToImportStep(1);
            });
        }

        // Link "Ver Transações"
        document.querySelectorAll('.fin-go-transactions').forEach(function(a) {
            a.addEventListener('click', function(e) {
                e.preventDefault();
                navigateToSection('transactions');
            });
        });

        // ─── Import confirm (Step 2 → Step 3) ───
        if (btnImportConfirm) {
            btnImportConfirm.addEventListener('click', function() {
                if (importData.selectedRows.size === 0) {
                    Swal.fire({ icon: 'warning', title: 'Selecione ao menos uma linha para importar.' });
                    return;
                }

                // Validate mapping for CSV files
                if (importData.file_type === 'csv') {
                    var mapping = getFinMapping();
                    var mappedFields = Object.values(mapping);
                    if (!mappedFields.includes('date') || !mappedFields.includes('description') || !mappedFields.includes('amount')) {
                        Swal.fire({ icon: 'error', title: 'Mapeamento incompleto', text: 'Os campos Data, Descrição e Valor são obrigatórios.' });
                        return;
                    }
                }

                var btn = this;

                Swal.fire({
                    title: 'Confirmar importação?',
                    html: '<strong>' + importData.selectedRows.size + '</strong> transação(ões) serão importadas como <strong>' +
                        (document.getElementById('importMode').value === 'registro' ? 'Registro' : 'Contabilizado') + '</strong>.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-file-import me-1"></i>Importar',
                    confirmButtonColor: '#17a2b8',
                    cancelButtonText: 'Cancelar'
                }).then(function(result) {
                    if (!result.isConfirmed) return;

                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Importando...';

                    var fd = new FormData();
                    fd.append('import_file', importFileInput.files[0]);
                    fd.append('import_mode', document.getElementById('importMode').value);
                    fd.append('selected_rows', JSON.stringify(Array.from(importData.selectedRows)));
                    fd.append('csrf_token', csrfToken);

                    var actionUrl;
                    if (importData.file_type === 'ofx') {
                        actionUrl = '?page=financial&action=importOfxSelected';
                    } else {
                        actionUrl = '?page=financial&action=importCsv';
                        // Send column-name→field mapping (new table-based format)
                        fd.append('mapping', JSON.stringify(getFinMapping()));
                    }

                    fetch(actionUrl, {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken },
                        body: fd
                    })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-file-import me-1"></i>Importar <span id="importCountLabel">' + importData.selectedRows.size + '</span> Transação(ões)';

                        showImportResult(data);
                        goToImportStep(3);
                    })
                    .catch(function(err) {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-file-import me-1"></i>Importar Selecionadas';
                        console.error('Import failed:', err);
                        Swal.fire({ icon: 'error', title: 'Erro', text: 'Falha ao importar. Tente novamente.' });
                    });
                });
            });
        }

        // ─── Show import result (Step 3) ───
        function showImportResult(data) {
            var container = document.getElementById('importResultContent');
            if (!container) return;
            var html = '';

            if (data.success) {
                if (data.imported > 0) {
                    html += '<div class="text-center mb-4">';
                    html += '<div class="rounded-circle d-inline-flex align-items-center justify-content-center mx-auto mb-3" style="width:80px;height:80px;background:rgba(39,174,96,.1);">';
                    html += '<i class="fas fa-check-circle fa-2x text-success"></i></div>';
                    html += '<h4 class="text-success">Importação Concluída!</h4>';
                    html += '<p class="text-muted">' + escHtml(data.message || (data.imported + ' transação(ões) importada(s).')) + '</p>';
                    html += '</div>';
                } else {
                    html += '<div class="text-center mb-4">';
                    html += '<div class="rounded-circle d-inline-flex align-items-center justify-content-center mx-auto mb-3" style="width:80px;height:80px;background:rgba(243,156,18,.1);">';
                    html += '<i class="fas fa-exclamation-triangle fa-2x text-warning"></i></div>';
                    html += '<h4 class="text-warning">Nenhuma transação importada</h4>';
                    html += '<p class="text-muted">Verifique os dados do arquivo e tente novamente.</p>';
                    html += '</div>';
                }

                // Stats
                html += '<div class="row g-3 mb-4 justify-content-center">';
                html += '<div class="col-auto"><div class="badge bg-success-subtle text-success px-3 py-2"><i class="fas fa-check me-1"></i>Importadas: ' + (data.imported || 0) + '</div></div>';
                if (data.skipped > 0) html += '<div class="col-auto"><div class="badge bg-warning-subtle text-warning px-3 py-2"><i class="fas fa-forward me-1"></i>Ignoradas: ' + data.skipped + '</div></div>';
                if (data.duplicates > 0) html += '<div class="col-auto"><div class="badge bg-info-subtle text-info px-3 py-2"><i class="fas fa-clone me-1"></i>Duplicadas: ' + data.duplicates + '</div></div>';
                if (data.errors && data.errors.length > 0) html += '<div class="col-auto"><div class="badge bg-danger-subtle text-danger px-3 py-2"><i class="fas fa-times me-1"></i>Erros: ' + data.errors.length + '</div></div>';
                html += '</div>';

                // Errors list
                if (data.errors && data.errors.length > 0) {
                    html += '<div class="alert alert-warning py-2 d-flex align-items-center">';
                    html += '<i class="fas fa-exclamation-triangle me-2"></i>';
                    html += '<strong>' + data.errors.length + '</strong>&nbsp;linha(s) com erro:</div>';
                    html += '<div class="list-group" style="max-height:250px;overflow-y:auto;">';
                    data.errors.forEach(function(err) {
                        var errMsg = typeof err === 'object' ? (err.message || JSON.stringify(err)) : String(err);
                        html += '<div class="list-group-item list-group-item-danger py-2 small">';
                        html += '<i class="fas fa-times-circle me-1"></i>' + escHtml(errMsg) + '</div>';
                    });
                    html += '</div>';
                }
            } else {
                html += '<div class="text-center">';
                html += '<div class="rounded-circle d-inline-flex align-items-center justify-content-center mx-auto mb-3" style="width:80px;height:80px;background:rgba(192,57,43,.1);">';
                html += '<i class="fas fa-times-circle fa-2x text-danger"></i></div>';
                html += '<h4 class="text-danger">Erro na Importação</h4>';
                html += '<p class="text-muted">' + escHtml(data.message || 'Erro desconhecido.') + '</p></div>';
            }

            container.innerHTML = html;
        }

        // ─── New Import (Step 3 → Step 1) ───
        if (btnNewImport) {
            btnNewImport.addEventListener('click', function() {
                importFileInput.value = '';
                importFileInfo.style.display = 'none';
                importDropzone.classList.remove('has-file');
                btnParseFile.disabled = true;
                importData = { file_type: null, rows: [], headers: [], columns: [], preview: [], auto_mapping: {}, selectedRows: new Set() };
                goToImportStep(1);
            });
        }


        // ═══════════════════════════════════════════
        // PAGINAÇÃO — Componente reutilizável
        // ═══════════════════════════════════════════
        function renderPagination(containerId, currentPage, totalPages, totalItems, onPageClick) {
            var container = document.getElementById(containerId);
            if (!container) return;
            if (totalPages <= 1) { container.innerHTML = ''; return; }

            var html = '';
            html += '<button class="btn btn-sm btn-outline-secondary" ' + (currentPage <= 1 ? 'disabled' : '') + ' data-page="' + (currentPage - 1) + '"><i class="fas fa-chevron-left"></i></button>';

            var startPage = Math.max(1, currentPage - 2);
            var endPage = Math.min(totalPages, currentPage + 2);
            if (startPage > 1) html += '<button class="btn btn-sm btn-outline-secondary" data-page="1">1</button>';
            if (startPage > 2) html += '<span class="page-info">…</span>';

            for (var p = startPage; p <= endPage; p++) {
                if (p === currentPage) {
                    html += '<button class="btn btn-sm btn-primary" disabled>' + p + '</button>';
                } else {
                    html += '<button class="btn btn-sm btn-outline-secondary" data-page="' + p + '">' + p + '</button>';
                }
            }

            if (endPage < totalPages - 1) html += '<span class="page-info">…</span>';
            if (endPage < totalPages) html += '<button class="btn btn-sm btn-outline-secondary" data-page="' + totalPages + '">' + totalPages + '</button>';
            html += '<button class="btn btn-sm btn-outline-secondary" ' + (currentPage >= totalPages ? 'disabled' : '') + ' data-page="' + (currentPage + 1) + '"><i class="fas fa-chevron-right"></i></button>';
            html += '<span class="page-info ms-2">' + totalItems + ' registro(s)</span>';

            container.innerHTML = html;
            container.querySelectorAll('button[data-page]').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var pg = parseInt(this.dataset.page);
                    if (pg && !isNaN(pg)) onPageClick(pg);
                });
            });
        }


        // ═══════════════════════════════════════════
        // INICIALIZAÇÃO
        // ═══════════════════════════════════════════
        if (initialSection === 'payments') loadPayments(1);
        if (initialSection === 'transactions') loadTransactions(1);
        if (initialSection === 'reports') loadDreOnInit();
        if (initialSection === 'cashflow') loadCashflowOnInit();
        if (initialSection === 'recurring') loadRecurring();


        // ═══════════════════════════════════════════
        // DRE — Demonstrativo de Resultado do Exercício
        // ═══════════════════════════════════════════
        function loadDreOnInit() {
            // Auto-load DRE when navigating to this section
        }

        document.getElementById('btnLoadDre')?.addEventListener('click', function() {
            loadDre();
        });

        function loadDre() {
            var fromMonth = document.getElementById('dreFrom')?.value || '';
            var toMonth   = document.getElementById('dreTo')?.value || '';
            if (!fromMonth || !toMonth) {
                Swal.fire({ icon: 'warning', title: 'Selecione o período.' });
                return;
            }

            var container = document.getElementById('dreContainer');
            container.innerHTML = '<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x text-muted"></i><p class="mt-2 text-muted">Gerando DRE...</p></div>';

            fetch('?page=financial&action=getDre&from=' + encodeURIComponent(fromMonth) + '&to=' + encodeURIComponent(toMonth))
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (!res.success || !res.data) {
                    container.innerHTML = '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>Erro ao carregar DRE.</div>';
                    return;
                }
                renderDre(res.data);
            })
            .catch(function(err) {
                console.error('DRE error:', err);
                container.innerHTML = '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>Erro de conexão.</div>';
            });
        }

        function renderDre(dre) {
            var container = document.getElementById('dreContainer');
            var resultado = dre.resultado || 0;
            var resultClass = resultado >= 0 ? 'dre-result-positive' : 'dre-result-negative';
            var resultIcon  = resultado >= 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down';

            var html = '';

            // Result summary cards
            html += '<div class="row g-3 mb-4">';
            html += '<div class="col-md-4"><div class="card border-0 shadow-sm border-start border-success border-4"><div class="card-body p-3 text-center">';
            html += '<div class="text-muted small text-uppercase fw-bold" style="font-size:.65rem;">Total Receitas</div>';
            html += '<div class="fw-bold fs-5 text-success">R$ ' + formatCurrency(dre.total_receitas) + '</div>';
            html += '</div></div></div>';
            html += '<div class="col-md-4"><div class="card border-0 shadow-sm border-start border-danger border-4"><div class="card-body p-3 text-center">';
            html += '<div class="text-muted small text-uppercase fw-bold" style="font-size:.65rem;">Total Despesas</div>';
            html += '<div class="fw-bold fs-5 text-danger">R$ ' + formatCurrency(dre.total_despesas) + '</div>';
            html += '</div></div></div>';
            html += '<div class="col-md-4"><div class="card border-0 shadow-sm border-start border-primary border-4"><div class="card-body p-3 text-center">';
            html += '<div class="text-muted small text-uppercase fw-bold" style="font-size:.65rem;">Resultado Líquido</div>';
            html += '<div class="fw-bold fs-5 ' + resultClass + '"><i class="' + resultIcon + ' me-1"></i>R$ ' + formatCurrency(Math.abs(resultado)) + '</div>';
            html += '</div></div></div>';
            html += '</div>';

            // DRE Table
            html += '<div class="card border-0 shadow-sm">';
            html += '<div class="card-body p-0">';
            html += '<table class="table table-hover align-middle mb-0">';
            html += '<thead class="bg-light"><tr><th class="ps-3 py-3">Categoria</th><th class="py-3 text-end pe-3">Valor</th></tr></thead>';
            html += '<tbody>';

            // Receitas
            html += '<tr class="table-success"><td class="ps-3 fw-bold" colspan="2"><i class="fas fa-arrow-down me-2"></i>RECEITAS</td></tr>';
            if (dre.receitas && dre.receitas.length > 0) {
                dre.receitas.forEach(function(r) {
                    html += '<tr class="dre-row"><td class="ps-4 small">' + escHtml(r.category_name) + '</td>';
                    html += '<td class="text-end pe-3 text-success fw-bold">R$ ' + formatCurrency(r.total) + '</td></tr>';
                });
            }
            if (dre.parcelas_pagas > 0) {
                html += '<tr class="dre-row"><td class="ps-4 small"><i class="fas fa-receipt me-1 text-muted"></i>Parcelas Pagas (Pedidos)</td>';
                html += '<td class="text-end pe-3 text-success fw-bold">R$ ' + formatCurrency(dre.parcelas_pagas) + '</td></tr>';
            }
            html += '<tr class="dre-total-row bg-success-subtle"><td class="ps-3 fw-bold">TOTAL RECEITAS</td>';
            html += '<td class="text-end pe-3 fw-bold text-success">R$ ' + formatCurrency(dre.total_receitas) + '</td></tr>';

            // Despesas
            html += '<tr class="table-danger"><td class="ps-3 fw-bold" colspan="2"><i class="fas fa-arrow-up me-2"></i>DESPESAS</td></tr>';
            if (dre.despesas && dre.despesas.length > 0) {
                dre.despesas.forEach(function(d) {
                    html += '<tr class="dre-row"><td class="ps-4 small">' + escHtml(d.category_name) + '</td>';
                    html += '<td class="text-end pe-3 text-danger fw-bold">R$ ' + formatCurrency(d.total) + '</td></tr>';
                });
            }
            html += '<tr class="dre-total-row bg-danger-subtle"><td class="ps-3 fw-bold">TOTAL DESPESAS</td>';
            html += '<td class="text-end pe-3 fw-bold text-danger">R$ ' + formatCurrency(dre.total_despesas) + '</td></tr>';

            // Resultado
            html += '<tr class="' + (resultado >= 0 ? 'table-primary' : 'table-warning') + '">';
            html += '<td class="ps-3 fw-bold fs-6"><i class="' + resultIcon + ' me-2"></i>RESULTADO LÍQUIDO</td>';
            html += '<td class="text-end pe-3 fw-bold fs-6 ' + resultClass + '">R$ ' + formatCurrency(Math.abs(resultado)) + '</td></tr>';

            html += '</tbody></table></div></div>';

            // Period badge
            html += '<div class="text-center mt-3"><span class="badge bg-secondary"><i class="fas fa-calendar me-1"></i>';
            html += (dre.periodo?.de || '') + ' a ' + (dre.periodo?.ate || '') + '</span></div>';

            container.innerHTML = html;
        }

        // DRE Export
        document.getElementById('btnExportDre')?.addEventListener('click', function() {
            var fromMonth = document.getElementById('dreFrom')?.value || '';
            var toMonth   = document.getElementById('dreTo')?.value || '';
            if (!fromMonth || !toMonth) {
                Swal.fire({ icon: 'warning', title: 'Selecione o período antes de exportar.' });
                return;
            }
            window.location.href = '?page=financial&action=exportDreCsv&from=' + encodeURIComponent(fromMonth) + '&to=' + encodeURIComponent(toMonth);
        });


        // ═══════════════════════════════════════════
        // FLUXO DE CAIXA PROJETADO
        // ═══════════════════════════════════════════
        var cashflowChart = null;

        function loadCashflowOnInit() {
            // Auto-load when navigating
        }

        document.getElementById('btnLoadCashflow')?.addEventListener('click', function() {
            loadCashflow();
        });

        function loadCashflow() {
            var months    = document.getElementById('cashflowMonths')?.value || 6;
            var recurring = document.getElementById('cashflowIncludeRecurring')?.checked ? 1 : 0;

            var tableContainer = document.getElementById('cashflowTableContainer');
            tableContainer.innerHTML = '<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x text-muted"></i><p class="mt-2 text-muted">Gerando projeção...</p></div>';

            fetch('?page=financial&action=getCashflow&months=' + months + '&recurring=' + recurring)
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (!res.success || !res.data) {
                    tableContainer.innerHTML = '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>Erro ao carregar projeção.</div>';
                    return;
                }
                renderCashflow(res.data);
            })
            .catch(function(err) {
                console.error('Cashflow error:', err);
                tableContainer.innerHTML = '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>Erro de conexão.</div>';
            });
        }

        function renderCashflow(data) {
            // Chart
            var ctx = document.getElementById('cashflowChart');
            if (ctx && typeof Chart !== 'undefined') {
                if (cashflowChart) cashflowChart.destroy();

                var labels     = data.map(function(d) { return d.label; });
                var entradas   = data.map(function(d) { return d.total_entradas; });
                var saidas     = data.map(function(d) { return d.total_saidas; });
                var acumulado  = data.map(function(d) { return d.saldo_acumulado; });

                cashflowChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Entradas',
                                data: entradas,
                                backgroundColor: 'rgba(39,174,96,.6)',
                                borderColor: '#27ae60',
                                borderWidth: 1,
                                borderRadius: 4,
                                order: 2
                            },
                            {
                                label: 'Saídas',
                                data: saidas,
                                backgroundColor: 'rgba(231,76,60,.6)',
                                borderColor: '#e74c3c',
                                borderWidth: 1,
                                borderRadius: 4,
                                order: 2
                            },
                            {
                                label: 'Saldo Acumulado',
                                data: acumulado,
                                type: 'line',
                                borderColor: '#3498db',
                                backgroundColor: 'rgba(52,152,219,.1)',
                                fill: true,
                                tension: 0.3,
                                pointRadius: 4,
                                pointBackgroundColor: '#3498db',
                                borderWidth: 2,
                                order: 1
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: { position: 'top', labels: { usePointStyle: true, font: { size: 11 } } },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.dataset.label + ': R$ ' + formatCurrency(context.raw);
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                ticks: {
                                    callback: function(v) { return 'R$ ' + formatCurrency(v); },
                                    font: { size: 10 }
                                },
                                grid: { color: 'rgba(0,0,0,.05)' }
                            },
                            x: { grid: { display: false } }
                        }
                    }
                });
            }

            // Table
            var tableContainer = document.getElementById('cashflowTableContainer');
            var html = '<div class="card border-0 shadow-sm"><div class="card-body p-0">';
            html += '<div class="table-responsive"><table class="table table-hover align-middle mb-0">';
            html += '<thead class="bg-light"><tr>';
            html += '<th class="ps-3 py-3">Mês</th>';
            html += '<th class="py-3 text-end">Parcelas</th>';
            html += '<th class="py-3 text-end">Rec. Entradas</th>';
            html += '<th class="py-3 text-end">Rec. Saídas</th>';
            html += '<th class="py-3 text-end">Total Entradas</th>';
            html += '<th class="py-3 text-end">Total Saídas</th>';
            html += '<th class="py-3 text-end">Saldo Mês</th>';
            html += '<th class="py-3 text-end pe-3">Acumulado</th>';
            html += '</tr></thead><tbody>';

            data.forEach(function(row) {
                var saldoClass = row.saldo_mes >= 0 ? 'text-success' : 'text-danger';
                var acumClass  = row.saldo_acumulado >= 0 ? 'text-success' : 'text-danger';

                html += '<tr>';
                html += '<td class="ps-3 fw-bold small">' + escHtml(row.label) + '</td>';
                html += '<td class="text-end small">R$ ' + formatCurrency(row.entradas_parcelas) + '</td>';
                html += '<td class="text-end small text-success">R$ ' + formatCurrency(row.entradas_recorrencias) + '</td>';
                html += '<td class="text-end small text-danger">R$ ' + formatCurrency(row.saidas_recorrencias) + '</td>';
                html += '<td class="text-end small fw-bold text-success">R$ ' + formatCurrency(row.total_entradas) + '</td>';
                html += '<td class="text-end small fw-bold text-danger">R$ ' + formatCurrency(row.total_saidas) + '</td>';
                html += '<td class="text-end small fw-bold ' + saldoClass + '">R$ ' + formatCurrency(row.saldo_mes) + '</td>';
                html += '<td class="text-end small fw-bold pe-3 ' + acumClass + '">R$ ' + formatCurrency(row.saldo_acumulado) + '</td>';
                html += '</tr>';
            });

            html += '</tbody></table></div></div></div>';
            tableContainer.innerHTML = html;
        }

        // Cashflow Export
        document.getElementById('btnExportCashflow')?.addEventListener('click', function() {
            var months    = document.getElementById('cashflowMonths')?.value || 6;
            var recurring = document.getElementById('cashflowIncludeRecurring')?.checked ? 1 : 0;
            window.location.href = '?page=financial&action=exportCashflowCsv&months=' + months + '&recurring=' + recurring;
        });


        // ═══════════════════════════════════════════
        // RECORRÊNCIAS
        // ═══════════════════════════════════════════

        function loadRecurring() {
            var tbody = document.getElementById('recurringTableBody');
            if (!tbody) return;

            tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-5"><i class="fas fa-spinner fa-spin fa-2x mb-2 d-block opacity-50"></i>Carregando...</td></tr>';

            fetch('?page=financial&action=recurringList')
            .then(function(r) { return r.json(); })
            .then(function(res) {
                var items   = res.data || [];
                var summary = res.summary || {};

                // Update summary cards
                document.getElementById('recurringRevenue').textContent = 'R$ ' + formatCurrency(summary.entradas || 0);
                document.getElementById('recurringExpenses').textContent = 'R$ ' + formatCurrency(summary.saidas || 0);
                var bal = (summary.entradas || 0) - (summary.saidas || 0);
                var balEl = document.getElementById('recurringBalance');
                balEl.textContent = 'R$ ' + formatCurrency(Math.abs(bal));
                balEl.className = 'fw-bold fs-5 ' + (bal >= 0 ? 'text-success' : 'text-danger');

                document.getElementById('recurringTotalBadge').textContent = items.length + ' registro(s)';

                if (!items.length) {
                    tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-5"><i class="fas fa-redo-alt fa-3x mb-2 d-block opacity-25"></i><div class="fw-bold">Nenhuma recorrência cadastrada</div><p class="small mt-1">Clique em "Nova Recorrência" para adicionar.</p></td></tr>';
                    return;
                }

                var html = '';
                items.forEach(function(item) {
                    var typeClass = item.type === 'entrada' ? 'bg-success' : 'bg-danger';
                    var typeLabel = item.type === 'entrada' ? 'Receita' : 'Despesa';
                    var statusClass = item.is_active ? 'bg-success' : 'bg-secondary';
                    var statusLabel = item.is_active ? 'Ativa' : 'Inativa';

                    html += '<tr' + (!item.is_active ? ' class="table-secondary"' : '') + '>';
                    html += '<td class="ps-3 fw-bold small">' + escHtml(item.description) + '</td>';
                    html += '<td><span class="badge ' + typeClass + '">' + typeLabel + '</span></td>';
                    html += '<td class="small">' + escHtml(item.category_name || item.category) + '</td>';
                    html += '<td class="fw-bold small ' + (item.type === 'entrada' ? 'text-success' : 'text-danger') + '">R$ ' + formatCurrency(item.amount) + '</td>';
                    html += '<td class="small">Dia ' + item.due_day + '</td>';
                    html += '<td class="small">' + (item.next_generation ? '<span class="badge bg-info-subtle text-info">' + escHtml(item.next_generation) + '</span>' : '<span class="text-muted">—</span>') + '</td>';
                    html += '<td><span class="badge ' + statusClass + '">' + statusLabel + '</span></td>';
                    html += '<td class="text-end pe-3"><div class="btn-group btn-group-sm">';
                    html += '<button class="btn btn-outline-warning btn-edit-recurring" data-id="' + item.id + '" title="Editar"><i class="fas fa-edit"></i></button>';
                    html += '<button class="btn btn-outline-' + (item.is_active ? 'secondary' : 'success') + ' btn-toggle-recurring" data-id="' + item.id + '" data-active="' + (item.is_active ? '0' : '1') + '" title="' + (item.is_active ? 'Desativar' : 'Ativar') + '"><i class="fas fa-' + (item.is_active ? 'pause' : 'play') + '"></i></button>';
                    html += '<button class="btn btn-outline-danger btn-delete-recurring" data-id="' + item.id + '" title="Excluir"><i class="fas fa-trash"></i></button>';
                    html += '</div></td>';
                    html += '</tr>';
                });
                tbody.innerHTML = html;
                attachRecurringListeners();
            })
            .catch(function(err) {
                console.error('Recurring error:', err);
                tbody.innerHTML = '<tr><td colspan="8" class="text-center text-danger py-3"><i class="fas fa-exclamation-triangle me-2"></i>Erro ao carregar recorrências.</td></tr>';
            });
        }

        function attachRecurringListeners() {
            // Edit
            document.querySelectorAll('#recurringTableBody .btn-edit-recurring').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var id = this.dataset.id;
                    fetch('?page=financial&action=recurringGet&id=' + id)
                    .then(function(r) { return r.json(); })
                    .then(function(item) {
                        if (item.error) { Swal.fire({ icon: 'error', title: 'Erro', text: item.error }); return; }
                        document.getElementById('recurringId').value = item.id;
                        document.getElementById('recurringModalTitle').textContent = 'Editar Recorrência';
                        document.getElementById('recurringType').value = item.type || 'saida';
                        document.getElementById('recurringCategory').value = item.category || '';
                        document.getElementById('recurringDescription').value = item.description || '';
                        document.getElementById('recurringAmount').value = parseFloat(item.amount || 0).toFixed(2);
                        document.getElementById('recurringDueDay').value = item.due_day || 10;
                        document.getElementById('recurringStartMonth').value = item.start_month || '';
                        document.getElementById('recurringEndMonth').value = item.end_month || '';
                        document.getElementById('recurringPaymentMethod').value = item.payment_method || '';
                        document.getElementById('recurringNotes').value = item.notes || '';
                        new bootstrap.Modal(document.getElementById('modalRecurring')).show();
                    });
                });
            });

            // Toggle active
            document.querySelectorAll('#recurringTableBody .btn-toggle-recurring').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var id = this.dataset.id;
                    var newActive = parseInt(this.dataset.active);
                    fetch('?page=financial&action=recurringToggle', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken },
                        body: JSON.stringify({ id: id, active: newActive, csrf_token: csrfToken })
                    }).then(function(r) { return r.json(); }).then(function(res) {
                        if (res.success) {
                            Swal.mixin({toast:true,position:'top-end',showConfirmButton:false,timer:2000,timerProgressBar:true}).fire({icon:'success',title:newActive ? 'Recorrência ativada!' : 'Recorrência desativada!'});
                            loadRecurring();
                        }
                    });
                });
            });

            // Delete
            document.querySelectorAll('#recurringTableBody .btn-delete-recurring').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var id = this.dataset.id;
                    Swal.fire({
                        title: 'Excluir recorrência?', text: 'Essa ação não pode ser desfeita.', icon: 'warning',
                        showCancelButton: true, confirmButtonColor: '#e74c3c', cancelButtonColor: '#6c757d',
                        confirmButtonText: '<i class="fas fa-trash me-1"></i>Excluir', cancelButtonText: 'Manter'
                    }).then(function(result) {
                        if (result.isConfirmed) {
                            fetch('?page=financial&action=recurringDelete&id=' + id, {
                                method: 'POST',
                                headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken }
                            }).then(function(r) { return r.json(); }).then(function(res) {
                                if (res.success) {
                                    Swal.mixin({toast:true,position:'top-end',showConfirmButton:false,timer:2000,timerProgressBar:true}).fire({icon:'success',title:'Recorrência removida!'});
                                    loadRecurring();
                                }
                            });
                        }
                    });
                });
            });
        }

        // New recurring button
        document.getElementById('btnNewRecurring')?.addEventListener('click', function() {
            document.getElementById('recurringId').value = '';
            document.getElementById('recurringModalTitle').textContent = 'Nova Recorrência';
            document.getElementById('recurringType').value = 'saida';
            document.getElementById('recurringCategory').value = 'outra_saida';
            document.getElementById('recurringDescription').value = '';
            document.getElementById('recurringAmount').value = '';
            document.getElementById('recurringDueDay').value = '10';
            document.getElementById('recurringStartMonth').value = new Date().toISOString().substring(0, 7);
            document.getElementById('recurringEndMonth').value = '';
            document.getElementById('recurringPaymentMethod').value = '';
            document.getElementById('recurringNotes').value = '';
            new bootstrap.Modal(document.getElementById('modalRecurring')).show();
        });

        // Save recurring
        document.getElementById('btnSaveRecurring')?.addEventListener('click', function() {
            var id = document.getElementById('recurringId').value;
            var data = {
                type:           document.getElementById('recurringType').value,
                category:       document.getElementById('recurringCategory').value,
                description:    document.getElementById('recurringDescription').value,
                amount:         parseFloat(document.getElementById('recurringAmount').value) || 0,
                due_day:        parseInt(document.getElementById('recurringDueDay').value) || 10,
                start_month:    document.getElementById('recurringStartMonth').value,
                end_month:      document.getElementById('recurringEndMonth').value || null,
                payment_method: document.getElementById('recurringPaymentMethod').value || null,
                notes:          document.getElementById('recurringNotes').value,
                csrf_token:     csrfToken
            };

            if (!data.description || data.amount <= 0) {
                Swal.fire({ icon: 'warning', title: 'Preencha descrição e valor.' });
                return;
            }

            if (id) data.id = parseInt(id);

            var action = id ? 'recurringUpdate' : 'recurringStore';

            var btn = this;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Salvando...';

            fetch('?page=financial&action=' + action, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify(data)
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save me-1"></i>Salvar';
                if (res.success) {
                    var modal = bootstrap.Modal.getInstance(document.getElementById('modalRecurring'));
                    if (modal) modal.hide();
                    Swal.mixin({toast:true,position:'top-end',showConfirmButton:false,timer:2500,timerProgressBar:true}).fire({icon:'success',title:id ? 'Recorrência atualizada!' : 'Recorrência criada!'});
                    loadRecurring();
                } else {
                    Swal.fire({ icon: 'error', title: 'Erro', text: res.error || 'Erro ao salvar.' });
                }
            })
            .catch(function() {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save me-1"></i>Salvar';
                Swal.fire({ icon: 'error', title: 'Erro de conexão.' });
            });
        });

        // Recurring type → filter categories
        document.getElementById('recurringType')?.addEventListener('change', function() {
            var type = this.value;
            var catSelect = document.getElementById('recurringCategory');
            catSelect.querySelectorAll('option').forEach(function(opt) {
                if (opt.dataset.type) {
                    opt.style.display = opt.dataset.type === type ? '' : 'none';
                }
            });
            // Auto-select first visible
            var first = catSelect.querySelector('option[data-type="' + type + '"]');
            if (first) catSelect.value = first.value;
        });

        // Process monthly recurring transactions
        document.getElementById('btnProcessRecurring')?.addEventListener('click', function() {
            var btn = this;
            Swal.fire({
                title: 'Processar recorrências do mês?',
                html: 'Isso irá gerar as transações financeiras pendentes para <strong>' + new Date().toLocaleDateString('pt-BR', {month:'long', year:'numeric'}) + '</strong>.<br><small class="text-muted">Transações já geradas neste mês serão ignoradas.</small>',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#17a2b8',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-cogs me-1"></i>Processar',
                cancelButtonText: 'Cancelar'
            }).then(function(result) {
                if (!result.isConfirmed) return;

                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Processando...';

                fetch('?page=financial&action=recurringProcess', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' },
                    body: JSON.stringify({ csrf_token: csrfToken })
                })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-cogs me-1"></i>Processar Mês';

                    if (res.error) {
                        Swal.fire({ icon: 'error', title: 'Erro', text: res.error });
                        return;
                    }

                    var msg = '<b>' + (res.generated || 0) + '</b> transação(ões) gerada(s)';
                    if (res.skipped > 0) msg += '<br><small class="text-muted">' + res.skipped + ' ignorada(s) (já processadas)</small>';
                    if (res.errors && res.errors.length > 0) msg += '<br><small class="text-danger">' + res.errors.length + ' erro(s)</small>';

                    Swal.fire({
                        icon: res.generated > 0 ? 'success' : 'info',
                        title: res.generated > 0 ? 'Recorrências processadas!' : 'Nada a processar',
                        html: msg
                    });

                    loadRecurring();
                })
                .catch(function() {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-cogs me-1"></i>Processar Mês';
                    Swal.fire({ icon: 'error', title: 'Erro de conexão.' });
                });
            });
        });


        // ═══════════════════════════════════════════
        // GATEWAY CHARGE — Cobrar parcela via Gateway
        // ═══════════════════════════════════════════

        document.getElementById('btnCreateGwCharge')?.addEventListener('click', function() {
            var installmentId = document.getElementById('gwChargeInstId').value;
            var gatewaySlug   = document.getElementById('gwChargeSlug')?.value;
            var method        = document.getElementById('gwChargeMethod')?.value || 'pix';

            if (!installmentId || !gatewaySlug) {
                Swal.fire({ icon: 'warning', title: 'Selecione o gateway.' });
                return;
            }

            var btn = this;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Gerando...';

            var resultDiv = document.getElementById('gwChargeResult');
            resultDiv.style.display = 'none';

            var fd = new FormData();
            fd.append('installment_id', installmentId);
            fd.append('gateway_slug', gatewaySlug);
            fd.append('method', method);
            fd.append('csrf_token', csrfToken);

            fetch('?page=payment_gateways&action=createCharge', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken },
                body: fd
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-bolt me-1"></i>Gerar Cobrança';

                resultDiv.style.display = '';

                if (res.success) {
                    var html = '<div class="alert alert-success mb-0"><i class="fas fa-check-circle me-2"></i>Cobrança criada com sucesso!';
                    if (res.payment_url) {
                        html += '<br><a href="' + escHtml(res.payment_url) + '" target="_blank" class="btn btn-sm btn-success mt-2"><i class="fas fa-external-link-alt me-1"></i>Link de Pagamento</a>';
                    }
                    if (res.qr_code) {
                        html += '<div class="mt-2 p-2 bg-white rounded border text-center"><small class="d-block text-muted mb-1">PIX Copia e Cola:</small>';
                        html += '<textarea class="form-control form-control-sm" rows="2" onclick="this.select()" readonly>' + escHtml(res.qr_code) + '</textarea></div>';
                    }
                    if (res.qr_code_base64) {
                        html += '<div class="mt-2 text-center"><img src="data:image/png;base64,' + res.qr_code_base64 + '" alt="QR Code PIX" style="max-width:200px;"></div>';
                    }
                    if (res.boleto_url) {
                        html += '<br><a href="' + escHtml(res.boleto_url) + '" target="_blank" class="btn btn-sm btn-outline-primary mt-2"><i class="fas fa-barcode me-1"></i>Ver Boleto</a>';
                    }
                    if (res.external_id) {
                        html += '<br><small class="text-muted mt-1 d-block">ID: ' + escHtml(res.external_id) + ' · Status: ' + escHtml(res.status || 'pending') + '</small>';
                    }
                    html += '</div>';
                    resultDiv.innerHTML = html;
                } else {
                    resultDiv.innerHTML = '<div class="alert alert-danger mb-0"><i class="fas fa-times-circle me-2"></i>' + escHtml(res.message || 'Erro ao criar cobrança.') + '</div>';
                }
            })
            .catch(function(err) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-bolt me-1"></i>Gerar Cobrança';
                resultDiv.style.display = '';
                resultDiv.innerHTML = '<div class="alert alert-danger mb-0"><i class="fas fa-times-circle me-2"></i>Erro de conexão.</div>';
            });
        });


        // ═══════════════════════════════════════════
        // TRANSACTIONS EXPORT CSV
        // ═══════════════════════════════════════════
        var btnExportTx = document.getElementById('btnExportTransactions');
        if (btnExportTx) {
            btnExportTx.addEventListener('click', function() {
                var params = new URLSearchParams({
                    page: 'financial', action: 'exportTransactionsCsv',
                    type: document.getElementById('fTxType')?.value || '',
                    category: document.getElementById('fTxCategory')?.value || '',
                    month: document.getElementById('fTxMonth')?.value || '',
                    year: document.getElementById('fTxYear')?.value || '',
                    search: document.getElementById('fTxSearch')?.value || ''
                });
                window.location.href = '?' + params.toString();
            });
        }

    });
})();
