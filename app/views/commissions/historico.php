<?php
/**
 * Comissões — Aprovação / Pagamento
 * Lista paginada de comissões registradas com filtros dinâmicos, ações em lote
 * e modal de aprovação/pagamento por vendedor.
 * Padrão visual: Financeiro (sidebar em card, filtros auto-apply sem botão Filtrar).
 * Variáveis: $aux (users, formas)
 */
$users = $aux['users'] ?? [];
$statusMap = [
    'calculada'              => ['badge' => 'bg-warning text-dark', 'icon' => 'fas fa-clock',           'label' => 'Calculada'],
    'aprovada'               => ['badge' => 'bg-info',              'icon' => 'fas fa-thumbs-up',        'label' => 'Aprovada'],
    'aguardando_pagamento'   => ['badge' => 'bg-primary',           'icon' => 'fas fa-hourglass-half',   'label' => 'Ag. Pagamento'],
    'paga'                   => ['badge' => 'bg-success',           'icon' => 'fas fa-check-circle',     'label' => 'Paga'],
    'cancelada'              => ['badge' => 'bg-secondary',         'icon' => 'fas fa-ban',              'label' => 'Cancelada'],
];
?>

<div class="container-fluid py-3">

    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div>
            <h1 class="h2 mb-1"><i class="fas fa-hand-holding-usd me-2 text-primary"></i>Comissões</h1>
            <p class="text-muted mb-0" style="font-size:.82rem;">Dashboard, regras, simulação e histórico de comissões.</p>
        </div>
    </div>

    <div class="row g-4">
        <?php require 'app/views/commissions/_sidebar.php'; ?>

        <div class="col-lg-9">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center">
                    <div class="icon-circle icon-circle-crimson me-2">
                        <i class="fas fa-check-double text-crimson" style="font-size:.85rem;"></i>
                    </div>
                    <div>
                        <h5 class="mb-0" style="font-size:1rem;">Aprovação de Comissões</h5>
                        <p class="text-muted mb-0" style="font-size:.72rem;">Aprove, pague ou cancele comissões. Use o botão abaixo para ações em lote por vendedor.</p>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div id="summaryBadges" class="d-flex gap-1 flex-wrap"></div>
                    <button class="btn btn-sm btn-outline-primary" onclick="openVendedorModal()" title="Aprovação/Pagamento por Vendedor">
                        <i class="fas fa-user-check me-1"></i>Por Vendedor
                    </button>
                </div>
            </div>

            <!-- Filtro dinâmico (auto-apply) -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body py-2">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label small mb-1">Vendedor</label>
                            <select id="filtro_user" class="form-select form-select-sm">
                                <option value="">Todos</option>
                                <?php foreach ($users as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= e($u['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-1">Status</label>
                            <select id="filtro_status" class="form-select form-select-sm">
                                <option value="">Todos</option>
                                <option value="calculada">Calculada</option>
                                <option value="aprovada">Aprovada</option>
                                <option value="aguardando_pagamento">Ag. Pagamento</option>
                                <option value="paga">Paga</option>
                                <option value="cancelada">Cancelada</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-1">De</label>
                            <input type="date" id="filtro_from" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-1">Até</label>
                            <input type="date" id="filtro_to" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-1">Busca</label>
                            <input type="text" id="filtro_search" class="form-control form-control-sm" placeholder="Nome ou pedido...">
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-sm btn-outline-secondary w-100" onclick="clearFilters()"><i class="fas fa-times me-1"></i>Limpar</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ações em lote -->
            <div class="d-flex gap-2 mb-2">
                <button class="btn btn-sm btn-outline-info" onclick="aprovarSelecionados()" id="btnAprovarLote" style="display:none">
                    <i class="fas fa-thumbs-up me-1"></i>Aprovar Selecionados
                </button>
                <button class="btn btn-sm btn-outline-success" onclick="pagarSelecionados()" id="btnPagarLote" style="display:none">
                    <i class="fas fa-money-bill me-1"></i>Pagar Selecionados
                </button>
            </div>

            <!-- Tabela -->
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 table-sm">
                        <thead class="table-light">
                            <tr>
                                <th style="width:36px"><input type="checkbox" id="checkAll" onchange="toggleAll()"></th>
                                <th>Pedido</th>
                                <th>Vendedor</th>
                                <th>Cliente</th>
                                <th>Origem</th>
                                <th class="text-end">Valor Base</th>
                                <th class="text-end">Comissão</th>
                                <th class="text-center">Status</th>
                                <th>Data</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="historicoBody">
                            <tr><td colspan="10" class="text-center py-4 text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Carregando...</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white d-flex justify-content-between align-items-center" id="paginationArea"></div>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════ -->
<!-- MODAL: Aprovação/Pagamento por Vendedor                      -->
<!-- ══════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalVendedor" tabindex="-1" aria-labelledby="modalVendedorLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-bottom-0 pb-2">
                <h5 class="modal-title" id="modalVendedorLabel">
                    <i class="fas fa-user-check me-2 text-primary"></i>Aprovação / Pagamento por Vendedor
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body pt-0">
                <!-- Lista de Vendedores -->
                <div id="vendedorListSection">
                    <p class="text-muted small mb-2">Selecione um vendedor para visualizar e gerenciar suas comissões pendentes.</p>
                    <div class="list-group" id="vendedorList">
                        <div class="text-center py-3 text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Carregando...</div>
                    </div>
                </div>

                <!-- Comissões do Vendedor Selecionado -->
                <div id="vendedorComissoes" style="display:none">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-sm btn-outline-secondary" onclick="voltarListaVendedores()" title="Voltar">
                                <i class="fas fa-arrow-left"></i>
                            </button>
                            <h6 class="mb-0">
                                <i class="fas fa-user me-1 text-primary"></i>
                                <span id="vendedorSelectedName"></span>
                            </h6>
                        </div>
                        <div class="d-flex gap-1">
                            <select class="form-select form-select-sm" style="width:auto" onchange="loadComissoesVendedor(selectedVendedorId, this.value)">
                                <option value="">Todas pendentes</option>
                                <option value="aprovacao">Pendentes de aprovação</option>
                                <option value="pagamento">Pendentes de pagamento</option>
                            </select>
                        </div>
                    </div>

                    <!-- Ações em lote do vendedor -->
                    <div class="d-flex gap-2 mb-2">
                        <button class="btn btn-sm btn-outline-info" onclick="vendedorAprovarLote()" id="btnVendedorAprovar" style="display:none">
                            <i class="fas fa-thumbs-up me-1"></i>Aprovar Selecionados
                        </button>
                        <button class="btn btn-sm btn-outline-success" onclick="vendedorPagarLote()" id="btnVendedorPagar" style="display:none">
                            <i class="fas fa-money-bill me-1"></i>Pagar Selecionados
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:32px"><input type="checkbox" id="vendedorCheckAll" onchange="toggleAllVendedor()"></th>
                                    <th>Pedido</th>
                                    <th>Cliente</th>
                                    <th class="text-end">Valor Base</th>
                                    <th class="text-end">Comissão</th>
                                    <th class="text-center">Status</th>
                                    <th>Data</th>
                                </tr>
                            </thead>
                            <tbody id="vendedorComissoesBody">
                                <tr><td colspan="7" class="text-center py-3 text-muted">Selecione um vendedor.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
const statusMap = <?= json_encode($statusMap) ?>;
let currentPage = 1;
let debounceTimer = null;

function loadHistorico(page = 1) {
    currentPage = page;
    const params = new URLSearchParams({
        page: 'commissions',
        action: 'getHistoricoPaginated',
        pg: page,
        per_page: 25,
    });
    const user = document.getElementById('filtro_user').value;
    const status = document.getElementById('filtro_status').value;
    const from = document.getElementById('filtro_from').value;
    const to = document.getElementById('filtro_to').value;
    const search = document.getElementById('filtro_search').value;

    if (user) params.set('user_id', user);
    if (status) params.set('status', status);
    if (from) params.set('date_from', from);
    if (to) params.set('date_to', to);
    if (search) params.set('search', search);

    fetch('?' + params.toString())
        .then(r => r.json())
        .then(res => {
            if (!res.success) return;
            renderTable(res.data || []);
            renderPagination(res.total, res.page, res.per_page, res.total_pages);
            renderSummary(res.summary || {});
        });
}

function loadHistoricoDebounced() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => loadHistorico(1), 350);
}

function renderTable(items) {
    const tbody = document.getElementById('historicoBody');
    if (!items.length) {
        tbody.innerHTML = '<tr><td colspan="10" class="text-center py-4 text-muted"><i class="fas fa-inbox me-2"></i>Nenhuma comissão encontrada.</td></tr>';
        return;
    }
    const origemIcons = {
        usuario: '<span class="badge bg-primary"><i class="fas fa-user"></i></span>',
        grupo: '<span class="badge bg-info"><i class="fas fa-users"></i></span>',
        produto: '<span class="badge bg-secondary"><i class="fas fa-box"></i></span>',
        padrao: '<span class="badge bg-warning text-dark"><i class="fas fa-cog"></i></span>',
    };

    tbody.innerHTML = items.map(i => {
        const s = statusMap[i.status] || statusMap['calculada'];
        const dt = new Date(i.created_at);
        const dtStr = dt.toLocaleDateString('pt-BR') + ' ' + dt.toLocaleTimeString('pt-BR', {hour:'2-digit',minute:'2-digit'});

        let actions = '';
        if (i.status === 'calculada') {
            actions += `<button class="btn btn-sm btn-outline-info" onclick="actionStatus(${i.id}, 'aprovar')" title="Aprovar"><i class="fas fa-thumbs-up"></i></button> `;
        }
        if (i.status === 'aprovada' || i.status === 'aguardando_pagamento') {
            actions += `<button class="btn btn-sm btn-outline-success" onclick="actionStatus(${i.id}, 'pagar')" title="Pagar"><i class="fas fa-money-bill"></i></button> `;
        }
        if (i.status !== 'cancelada' && i.status !== 'paga') {
            actions += `<button class="btn btn-sm btn-outline-danger" onclick="actionStatus(${i.id}, 'cancelar')" title="Cancelar"><i class="fas fa-ban"></i></button>`;
        }

        return `<tr>
            <td><input type="checkbox" class="row-check" value="${i.id}" onchange="toggleLoteBtns()"></td>
            <td><a href="?page=pipeline&action=detail&id=${i.order_id}" class="text-decoration-none fw-semibold">#${i.order_id}</a></td>
            <td>${escHtml(i.user_name)}</td>
            <td>${escHtml(i.customer_name || '—')}</td>
            <td>${origemIcons[i.origem_regra] || i.origem_regra}</td>
            <td class="text-end">R$ ${parseFloat(i.valor_base).toLocaleString('pt-BR',{minimumFractionDigits:2})}</td>
            <td class="text-end fw-bold text-success">R$ ${parseFloat(i.valor_comissao).toLocaleString('pt-BR',{minimumFractionDigits:2})}</td>
            <td class="text-center"><span class="badge ${s.badge}"><i class="${s.icon} me-1"></i>${s.label}</span></td>
            <td class="small">${dtStr}</td>
            <td class="text-end">${actions}</td>
        </tr>`;
    }).join('');
}

function renderPagination(total, page, perPage, totalPages) {
    const area = document.getElementById('paginationArea');
    if (totalPages <= 1) { area.innerHTML = `<small class="text-muted">${total} registros</small>`; return; }
    let html = `<small class="text-muted">${total} registros — página ${page} de ${totalPages}</small><nav><ul class="pagination pagination-sm mb-0">`;
    if (page > 1) html += `<li class="page-item"><a class="page-link" href="#" onclick="loadHistorico(${page-1});return false">‹</a></li>`;
    for (let p = Math.max(1, page-2); p <= Math.min(totalPages, page+2); p++) {
        html += `<li class="page-item ${p===page?'active':''}"><a class="page-link" href="#" onclick="loadHistorico(${p});return false">${p}</a></li>`;
    }
    if (page < totalPages) html += `<li class="page-item"><a class="page-link" href="#" onclick="loadHistorico(${page+1});return false">›</a></li>`;
    html += '</ul></nav>';
    area.innerHTML = html;
}

function renderSummary(summary) {
    const area = document.getElementById('summaryBadges');
    area.innerHTML = `
        <span class="badge bg-light text-dark border" style="font-size:.65rem;">Total: R$ ${parseFloat(summary.total_comissao||0).toLocaleString('pt-BR',{minimumFractionDigits:2})}</span>
        <span class="badge bg-success" style="font-size:.65rem;">Pagas: R$ ${parseFloat(summary.total_paga||0).toLocaleString('pt-BR',{minimumFractionDigits:2})}</span>
        <span class="badge bg-primary" style="font-size:.65rem;">Ag. Pgto: R$ ${parseFloat(summary.total_aguardando||0).toLocaleString('pt-BR',{minimumFractionDigits:2})}</span>
        <span class="badge bg-info" style="font-size:.65rem;">Aprovadas: R$ ${parseFloat(summary.total_aprovada||0).toLocaleString('pt-BR',{minimumFractionDigits:2})}</span>
        <span class="badge bg-warning text-dark" style="font-size:.65rem;">Calculadas: R$ ${parseFloat(summary.total_calculada||0).toLocaleString('pt-BR',{minimumFractionDigits:2})}</span>
    `;
}

function clearFilters() {
    document.getElementById('filtro_user').value = '';
    document.getElementById('filtro_status').value = '';
    document.getElementById('filtro_from').value = '';
    document.getElementById('filtro_to').value = '';
    document.getElementById('filtro_search').value = '';
    loadHistorico(1);
}

function actionStatus(id, action) {
    const labels = {aprovar:'Aprovar comissão?', pagar:'Marcar como paga?', cancelar:'Cancelar comissão?'};
    Swal.fire({
        title: labels[action] || 'Confirmar?', icon: 'question', showCancelButton: true,
        confirmButtonText: 'Sim', cancelButtonText: 'Não'
    }).then(result => {
        if (result.isConfirmed) {
            const fd = new FormData();
            fd.set('id', id);
            fd.set('csrf_token', csrfToken);
            fetch(`?page=commissions&action=${action}`, { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        Swal.mixin({toast:true,position:'top-end',showConfirmButton:false,timer:1500,timerProgressBar:true})
                            .fire({icon:'success',title:res.message});
                        loadHistorico(currentPage);
                    } else {
                        Swal.fire({icon:'error', title:'Erro', text: res.message});
                    }
                });
        }
    });
}

function toggleAll() {
    const checked = document.getElementById('checkAll').checked;
    document.querySelectorAll('.row-check').forEach(c => c.checked = checked);
    toggleLoteBtns();
}

function toggleLoteBtns() {
    const checked = document.querySelectorAll('.row-check:checked');
    document.getElementById('btnAprovarLote').style.display = checked.length > 0 ? '' : 'none';
    document.getElementById('btnPagarLote').style.display = checked.length > 0 ? '' : 'none';
}

function getSelectedIds() {
    return Array.from(document.querySelectorAll('.row-check:checked')).map(c => c.value);
}

function aprovarSelecionados() {
    const ids = getSelectedIds();
    if (!ids.length) return;
    const fd = new FormData();
    ids.forEach(id => fd.append('ids[]', id));
    fd.set('csrf_token', csrfToken);
    fetch('?page=commissions&action=aprovarLote', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            Swal.fire({icon: res.success?'success':'error', title: res.message, timer:1500, showConfirmButton:false});
            loadHistorico(currentPage);
        });
}

function pagarSelecionados() {
    const ids = getSelectedIds();
    if (!ids.length) return;
    const fd = new FormData();
    ids.forEach(id => fd.append('ids[]', id));
    fd.set('csrf_token', csrfToken);
    fetch('?page=commissions&action=pagarLote', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            Swal.fire({icon: res.success?'success':'error', title: res.message, timer:1500, showConfirmButton:false});
            loadHistorico(currentPage);
        });
}

function escHtml(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }

// ═══════════════════════════════════════════════════
// MODAL: Aprovação/Pagamento por Vendedor
// ═══════════════════════════════════════════════════

let vendedorModalInstance = null;
let selectedVendedorId = null;

function openVendedorModal() {
    if (!vendedorModalInstance) {
        vendedorModalInstance = new bootstrap.Modal(document.getElementById('modalVendedor'));
    }
    // Resetar e carregar lista de vendedores
    document.getElementById('vendedorList').innerHTML = '<div class="text-center py-3 text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Carregando...</div>';
    document.getElementById('vendedorComissoes').style.display = 'none';
    document.getElementById('vendedorListSection').style.display = '';
    selectedVendedorId = null;

    fetch('?page=commissions&action=getVendedoresPendentes')
        .then(r => r.json())
        .then(res => {
            if (!res.success || !res.data.length) {
                document.getElementById('vendedorList').innerHTML = '<div class="text-center py-3 text-muted"><i class="fas fa-check-circle me-2"></i>Nenhum vendedor com comissões pendentes.</div>';
                return;
            }
            renderVendedorList(res.data);
        });

    vendedorModalInstance.show();
}

function renderVendedorList(vendedores) {
    const container = document.getElementById('vendedorList');
    container.innerHTML = vendedores.map(v => `
        <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" onclick="selectVendedor(${v.user_id}, '${escHtml(v.user_name)}'); return false;">
            <div>
                <i class="fas fa-user me-2 text-primary"></i>
                <strong>${escHtml(v.user_name)}</strong>
            </div>
            <div class="d-flex gap-2">
                ${v.pendentes_aprovacao > 0 ? `<span class="badge bg-warning text-dark" title="Pendentes de aprovação"><i class="fas fa-clock me-1"></i>${v.pendentes_aprovacao}</span>` : ''}
                ${v.pendentes_pagamento > 0 ? `<span class="badge bg-primary" title="Pendentes de pagamento"><i class="fas fa-hourglass-half me-1"></i>${v.pendentes_pagamento}</span>` : ''}
                <span class="badge bg-light text-dark border">R$ ${parseFloat(v.total_valor||0).toLocaleString('pt-BR',{minimumFractionDigits:2})}</span>
            </div>
        </a>
    `).join('');
}

function selectVendedor(userId, userName) {
    selectedVendedorId = userId;
    document.getElementById('vendedorListSection').style.display = 'none';
    document.getElementById('vendedorComissoes').style.display = '';
    document.getElementById('vendedorSelectedName').textContent = userName;
    loadComissoesVendedor(userId);
}

function voltarListaVendedores() {
    document.getElementById('vendedorComissoes').style.display = 'none';
    document.getElementById('vendedorListSection').style.display = '';
    selectedVendedorId = null;
}

function loadComissoesVendedor(userId, statusFilter = '') {
    const tbody = document.getElementById('vendedorComissoesBody');
    tbody.innerHTML = '<tr><td colspan="7" class="text-center py-3 text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Carregando...</td></tr>';

    let url = `?page=commissions&action=getComissoesVendedor&user_id=${userId}`;
    if (statusFilter) url += `&status_filter=${statusFilter}`;

    fetch(url)
        .then(r => r.json())
        .then(res => {
            if (!res.success || !res.data.length) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center py-3 text-muted"><i class="fas fa-inbox me-2"></i>Nenhuma comissão pendente.</td></tr>';
                toggleVendedorLoteBtns();
                return;
            }
            renderVendedorComissoes(res.data);
        });
}

function renderVendedorComissoes(items) {
    const tbody = document.getElementById('vendedorComissoesBody');
    tbody.innerHTML = items.map(i => {
        const s = statusMap[i.status] || statusMap['calculada'];
        const dt = new Date(i.created_at);
        const dtStr = dt.toLocaleDateString('pt-BR');

        return `<tr>
            <td><input type="checkbox" class="vendedor-check" value="${i.id}" data-status="${i.status}" onchange="toggleVendedorLoteBtns()"></td>
            <td><a href="?page=pipeline&action=detail&id=${i.order_id}" class="text-decoration-none fw-semibold" target="_blank">#${i.order_id}</a></td>
            <td>${escHtml(i.customer_name || '—')}</td>
            <td class="text-end">R$ ${parseFloat(i.valor_base).toLocaleString('pt-BR',{minimumFractionDigits:2})}</td>
            <td class="text-end fw-bold text-success">R$ ${parseFloat(i.valor_comissao).toLocaleString('pt-BR',{minimumFractionDigits:2})}</td>
            <td class="text-center"><span class="badge ${s.badge}"><i class="${s.icon} me-1"></i>${s.label}</span></td>
            <td class="small">${dtStr}</td>
        </tr>`;
    }).join('');
    toggleVendedorLoteBtns();
}

function toggleVendedorLoteBtns() {
    const checked = document.querySelectorAll('.vendedor-check:checked');
    let hasAprovacao = false;
    let hasPagamento = false;
    checked.forEach(c => {
        if (c.dataset.status === 'calculada') hasAprovacao = true;
        if (c.dataset.status === 'aprovada' || c.dataset.status === 'aguardando_pagamento') hasPagamento = true;
    });
    document.getElementById('btnVendedorAprovar').style.display = hasAprovacao ? '' : 'none';
    document.getElementById('btnVendedorPagar').style.display = hasPagamento ? '' : 'none';
}

function toggleAllVendedor() {
    const checked = document.getElementById('vendedorCheckAll').checked;
    document.querySelectorAll('.vendedor-check').forEach(c => c.checked = checked);
    toggleVendedorLoteBtns();
}

function vendedorAprovarLote() {
    const ids = Array.from(document.querySelectorAll('.vendedor-check:checked'))
        .filter(c => c.dataset.status === 'calculada')
        .map(c => c.value);
    if (!ids.length) { Swal.fire({icon:'info', title:'Selecione itens com status "Calculada"', timer:1500, showConfirmButton:false}); return; }

    const fd = new FormData();
    ids.forEach(id => fd.append('ids[]', id));
    fd.set('csrf_token', csrfToken);
    fetch('?page=commissions&action=aprovarLote', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            Swal.mixin({toast:true,position:'top-end',showConfirmButton:false,timer:1500,timerProgressBar:true})
                .fire({icon: res.success?'success':'error', title: res.message});
            if (selectedVendedorId) loadComissoesVendedor(selectedVendedorId);
            loadHistorico(currentPage);
        });
}

function vendedorPagarLote() {
    const ids = Array.from(document.querySelectorAll('.vendedor-check:checked'))
        .filter(c => c.dataset.status === 'aprovada' || c.dataset.status === 'aguardando_pagamento')
        .map(c => c.value);
    if (!ids.length) { Swal.fire({icon:'info', title:'Selecione itens aprovados/aguardando pagamento', timer:1500, showConfirmButton:false}); return; }

    const fd = new FormData();
    ids.forEach(id => fd.append('ids[]', id));
    fd.set('csrf_token', csrfToken);
    fetch('?page=commissions&action=pagarLote', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            Swal.mixin({toast:true,position:'top-end',showConfirmButton:false,timer:1500,timerProgressBar:true})
                .fire({icon: res.success?'success':'error', title: res.message});
            if (selectedVendedorId) loadComissoesVendedor(selectedVendedorId);
            loadHistorico(currentPage);
        });
}

// ── Auto-apply: filtros disparam reload sem botão ──
document.addEventListener('DOMContentLoaded', function() {
    loadHistorico(1);

    // Selects e datas — disparam imediatamente
    ['filtro_user', 'filtro_status', 'filtro_from', 'filtro_to'].forEach(id => {
        document.getElementById(id)?.addEventListener('change', () => loadHistorico(1));
    });

    // Campo de busca — debounce
    document.getElementById('filtro_search')?.addEventListener('input', loadHistoricoDebounced);
});
</script>
