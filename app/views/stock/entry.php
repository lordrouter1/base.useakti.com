<?php
/**
 * Estoque — Entrada / Saída / Ajuste / Transferência
 * Variáveis: $warehouses, $products
 */
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-exchange-alt me-2"></i>Movimentação de Estoque</h1>
    <a href="?page=stock" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
</div>

<div class="row g-4">
    <!-- ══════ Painel de Movimentação ══════ -->
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body p-4">

                <!-- Tipo de Movimentação -->
                <div class="mb-4">
                    <label class="form-label fw-bold">Tipo de Movimentação</label>
                    <div class="btn-group w-100" role="group" id="movTypeGroup">
                        <input type="radio" class="btn-check" name="mov_type" id="typeEntrada" value="entrada" checked>
                        <label class="btn btn-outline-success" for="typeEntrada"><i class="fas fa-arrow-down me-1"></i>Entrada</label>

                        <input type="radio" class="btn-check" name="mov_type" id="typeSaida" value="saida">
                        <label class="btn btn-outline-danger" for="typeSaida"><i class="fas fa-arrow-up me-1"></i>Saída</label>

                        <input type="radio" class="btn-check" name="mov_type" id="typeAjuste" value="ajuste">
                        <label class="btn btn-outline-warning" for="typeAjuste"><i class="fas fa-sliders-h me-1"></i>Ajuste</label>

                        <input type="radio" class="btn-check" name="mov_type" id="typeTransfer" value="transferencia">
                        <label class="btn btn-outline-info" for="typeTransfer"><i class="fas fa-truck me-1"></i>Transferência</label>
                    </div>
                </div>

                <!-- Armazém Origem -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Armazém <span class="text-danger">*</span></label>
                        <select class="form-select" id="selWarehouse" required>
                            <option value="">Selecione o armazém...</option>
                            <?php foreach ($warehouses as $wh): ?>
                                <option value="<?= $wh['id'] ?>"><?= e($wh['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6" id="destWarehouseWrap" style="display:none;">
                        <label class="form-label fw-bold">Armazém Destino <span class="text-danger">*</span></label>
                        <select class="form-select" id="selDestWarehouse">
                            <option value="">Selecione o destino...</option>
                            <?php foreach ($warehouses as $wh): ?>
                                <option value="<?= $wh['id'] ?>"><?= e($wh['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Motivo -->
                <div class="mb-4">
                    <label class="form-label fw-bold">Motivo / Observação</label>
                    <input type="text" class="form-control" id="movReason" placeholder="Ex: Compra fornecedor, Venda avulsa, Correção inventário...">
                </div>

                <hr>

                <!-- Adicionar Produtos -->
                <h6 class="fw-bold mb-3"><i class="fas fa-box-open me-2 text-primary"></i>Produtos</h6>

                <div class="row g-2 mb-3 align-items-end" id="addProductRow">
                    <div class="col-md-5">
                        <label class="form-label small fw-bold">Produto</label>
                        <select class="form-select form-select-sm" id="selProduct">
                            <option value="">Selecione um produto...</option>
                            <?php foreach ($products as $p): ?>
                                <option value="<?= $p['id'] ?>" data-name="<?= eAttr($p['name']) ?>" data-cat="<?= eAttr($p['category_name'] ?? '') ?>">
                                    <?= e($p['name']) ?>
                                    <?= $p['category_name'] ? ' (' . e($p['category_name']) . ')' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3" id="combWrap" style="display:none;">
                        <label class="form-label small fw-bold">Variação</label>
                        <select class="form-select form-select-sm" id="selCombination">
                            <option value="">Sem variação</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold" id="lblQty">Quantidade</label>
                        <input type="number" class="form-control form-control-sm" id="inputQty" min="0.01" step="1" value="1" placeholder="Qtd">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-primary btn-sm w-100" id="btnAddItem">
                            <i class="fas fa-plus me-1"></i>Adicionar
                        </button>
                    </div>
                </div>

                <!-- Tabela de itens adicionados -->
                <div class="table-responsive" style="padding:0; border:none; box-shadow:none;">
                    <table class="table table-sm table-bordered align-middle mb-0" id="itemsTable">
                        <thead class="table-light">
                            <tr>
                                <th>Produto</th>
                                <th>Variação</th>
                                <th class="text-center" style="width:120px;">Quantidade</th>
                                <th style="width:50px;"></th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                            <tr id="emptyItemsRow">
                                <td colspan="4" class="text-center text-muted py-3">
                                    <i class="fas fa-inbox me-1"></i>Adicione produtos acima
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <hr>

                <!-- Botão Processar -->
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted small" id="itemsCountLabel">0 item(s)</span>
                    <button type="button" class="btn btn-lg btn-success" id="btnProcess" disabled>
                        <i class="fas fa-check-circle me-2"></i>Processar Movimentação
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════ Painel Lateral: Instruções ══════ -->
    <div class="col-lg-4">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white py-2">
                <h6 class="mb-0"><i class="fas fa-info-circle text-primary me-2"></i>Como funciona</h6>
            </div>
            <div class="card-body small">
                <div class="mb-3" id="helpEntrada">
                    <span class="badge bg-success me-1">Entrada</span>
                    Adiciona unidades ao estoque. Use para: compras de fornecedor, devoluções, produção.
                </div>
                <div class="mb-3" id="helpSaida" style="display:none;">
                    <span class="badge bg-danger me-1">Saída</span>
                    Remove unidades do estoque. Use para: vendas avulsas, perdas, descarte.
                </div>
                <div class="mb-3" id="helpAjuste" style="display:none;">
                    <span class="badge bg-warning text-dark me-1">Ajuste</span>
                    Define o saldo exato do item. Use para: inventário, correção de divergências.
                </div>
                <div class="mb-3" id="helpTransfer" style="display:none;">
                    <span class="badge bg-info me-1">Transferência</span>
                    Move unidades entre armazéns. A saída do origem e a entrada no destino são registradas automaticamente.
                </div>
                <hr>
                <ol class="ps-3 mb-0">
                    <li>Selecione o tipo de movimentação</li>
                    <li>Escolha o armazém</li>
                    <li>Adicione os produtos e quantidades</li>
                    <li>Clique em <strong>Processar</strong></li>
                </ol>
            </div>
        </div>

        <!-- Histórico Recente (mini) -->
        <div class="card shadow-sm">
            <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-history text-muted me-2"></i>Últimas Movimentações</h6>
                <a href="?page=stock&action=movements" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size:0.7rem;">Ver Todas</a>
            </div>
            <div class="card-body p-0" id="recentMovements" style="max-height:300px;overflow-y:auto;">
                <div class="text-center text-muted small py-3"><i class="fas fa-spinner fa-spin me-1"></i>Carregando...</div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // CSRF token para requisições AJAX POST
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const items = [];
    const selProduct = document.getElementById('selProduct');
    const selCombination = document.getElementById('selCombination');
    const combWrap = document.getElementById('combWrap');
    const inputQty = document.getElementById('inputQty');
    const itemsBody = document.getElementById('itemsBody');
    const btnAdd = document.getElementById('btnAddItem');
    const btnProcess = document.getElementById('btnProcess');
    const destWrap = document.getElementById('destWarehouseWrap');
    const lblQty = document.getElementById('lblQty');

    // ── Type toggle ──
    document.querySelectorAll('input[name="mov_type"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const t = this.value;
            destWrap.style.display = t === 'transferencia' ? '' : 'none';
            lblQty.textContent = t === 'ajuste' ? 'Novo Saldo' : 'Quantidade';
            // Help texts
            document.getElementById('helpEntrada').style.display = t === 'entrada' ? '' : 'none';
            document.getElementById('helpSaida').style.display = t === 'saida' ? '' : 'none';
            document.getElementById('helpAjuste').style.display = t === 'ajuste' ? '' : 'none';
            document.getElementById('helpTransfer').style.display = t === 'transferencia' ? '' : 'none';
        });
    });

    // ── Fetch combinations when product selected ──
    selProduct.addEventListener('change', function() {
        const pid = this.value;
        combWrap.style.display = 'none';
        selCombination.innerHTML = '<option value="">Sem variação</option>';
        if (!pid) return;

        fetch(`?page=stock&action=getProductCombinations&product_id=${pid}`)
            .then(r => r.json())
            .then(combos => {
                if (combos.length > 0) {
                    combWrap.style.display = '';
                    selCombination.innerHTML = '<option value="">Produto base (sem variação)</option>';
                    combos.forEach(c => {
                        selCombination.innerHTML += `<option value="${c.id}">${c.combination_label}${c.sku ? ' [' + c.sku + ']' : ''}</option>`;
                    });
                }
            });
    });

    // ── Add item to list ──
    btnAdd.addEventListener('click', function() {
        const productId = selProduct.value;
        const productName = selProduct.options[selProduct.selectedIndex]?.text || '';
        const combId = selCombination.value || null;
        const combName = combId ? selCombination.options[selCombination.selectedIndex]?.text : '—';
        const qty = parseFloat(inputQty.value);

        if (!productId) { Swal.fire({ icon:'warning', title:'Selecione um produto', timer:2000, showConfirmButton:false }); return; }
        if (!qty || qty <= 0) { Swal.fire({ icon:'warning', title:'Quantidade inválida', timer:2000, showConfirmButton:false }); return; }

        // Check duplicate
        const exists = items.find(i => i.product_id == productId && i.combination_id == combId);
        if (exists) {
            exists.quantity += qty;
            renderItems();
            return;
        }

        items.push({ product_id: productId, combination_id: combId, product_name: productName, combination_name: combName, quantity: qty });
        renderItems();

        // Reset
        selProduct.value = '';
        selCombination.innerHTML = '<option value="">Sem variação</option>';
        combWrap.style.display = 'none';
        inputQty.value = 1;
        selProduct.focus();
    });

    function renderItems() {
        if (items.length === 0) {
            itemsBody.innerHTML = '<tr id="emptyItemsRow"><td colspan="4" class="text-center text-muted py-3"><i class="fas fa-inbox me-1"></i>Adicione produtos acima</td></tr>';
            btnProcess.disabled = true;
            document.getElementById('itemsCountLabel').textContent = '0 item(s)';
            return;
        }

        let html = '';
        items.forEach((item, idx) => {
            html += `
                <tr>
                    <td class="fw-bold">${escHtml(item.product_name)}</td>
                    <td><span class="badge bg-light text-dark border">${escHtml(item.combination_name)}</span></td>
                    <td class="text-center">
                        <input type="number" class="form-control form-control-sm text-center" value="${item.quantity}" min="0.01" step="1"
                               onchange="updateItemQty(${idx}, this.value)" style="width:80px;margin:auto;">
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItem(${idx})"><i class="fas fa-times"></i></button>
                    </td>
                </tr>
            `;
        });
        itemsBody.innerHTML = html;
        btnProcess.disabled = false;
        document.getElementById('itemsCountLabel').textContent = items.length + ' item(s)';
    }

    window.updateItemQty = function(idx, val) {
        items[idx].quantity = parseFloat(val) || 0;
    };
    window.removeItem = function(idx) {
        items.splice(idx, 1);
        renderItems();
    };

    function escHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // ── Process Movement ──
    btnProcess.addEventListener('click', function() {
        const type = document.querySelector('input[name="mov_type"]:checked').value;
        const warehouseId = document.getElementById('selWarehouse').value;
        const destWarehouseId = document.getElementById('selDestWarehouse')?.value || '';
        const reason = document.getElementById('movReason').value;

        if (!warehouseId) { Swal.fire({ icon:'warning', title:'Selecione o armazém' }); return; }
        if (type === 'transferencia' && !destWarehouseId) { Swal.fire({ icon:'warning', title:'Selecione o armazém de destino' }); return; }
        if (type === 'transferencia' && warehouseId === destWarehouseId) { Swal.fire({ icon:'warning', title:'Origem e destino devem ser diferentes' }); return; }
        if (items.length === 0) { Swal.fire({ icon:'warning', title:'Adicione produtos' }); return; }

        const typeLabels = { entrada:'Entrada', saida:'Saída', ajuste:'Ajuste', transferencia:'Transferência' };

        Swal.fire({
            title: `Confirmar ${typeLabels[type]}?`,
            html: `<strong>${items.length}</strong> item(s) serão processados.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-check me-1"></i>Confirmar',
            cancelButtonText: 'Cancelar'
        }).then(result => {
            if (!result.isConfirmed) return;

            btnProcess.disabled = true;
            btnProcess.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processando...';

            const fd = new FormData();
            fd.append('csrf_token', csrfToken);
            fd.append('warehouse_id', warehouseId);
            fd.append('destination_warehouse_id', destWarehouseId);
            fd.append('type', type);
            fd.append('reason', reason);
            items.forEach((item, i) => {
                fd.append(`items[${i}][product_id]`, item.product_id);
                fd.append(`items[${i}][combination_id]`, item.combination_id || '');
                fd.append(`items[${i}][quantity]`, item.quantity);
            });

            fetch('?page=stock&action=storeMovement', { method:'POST', body:fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({ icon:'success', title:'Movimentação Registrada!', html:`${data.processed} item(s) processado(s).`, timer:2500, showConfirmButton:true })
                            .then(() => { window.location.href = '?page=stock&status=moved'; });
                    } else {
                        Swal.fire({ icon:'error', title:'Erro', text: data.message || 'Erro ao processar.' });
                        btnProcess.disabled = false;
                        btnProcess.innerHTML = '<i class="fas fa-check-circle me-2"></i>Processar Movimentação';
                    }
                })
                .catch(() => {
                    Swal.fire({ icon:'error', title:'Erro de comunicação' });
                    btnProcess.disabled = false;
                    btnProcess.innerHTML = '<i class="fas fa-check-circle me-2"></i>Processar Movimentação';
                });
        });
    });

    // ── Load recent movements ──
    fetch('?page=stock&action=movements&format=json&limit=10')
        .catch(() => {})
        .then(r => { if(r && r.ok) return r.json(); return null; })
        .then(data => {
            const container = document.getElementById('recentMovements');
            if (!data || !Array.isArray(data) || data.length === 0) {
                container.innerHTML = '<div class="text-center text-muted small py-3">Nenhuma movimentação recente.</div>';
                return;
            }
            let html = '<div class="list-group list-group-flush">';
            const icons = { entrada:'fas fa-arrow-down text-success', saida:'fas fa-arrow-up text-danger', ajuste:'fas fa-sliders-h text-warning', transferencia:'fas fa-truck text-info' };
            data.forEach(m => {
                html += `
                    <div class="list-group-item px-3 py-2">
                        <div class="d-flex justify-content-between">
                            <span><i class="${icons[m.type] || 'fas fa-circle'} me-2"></i><strong class="small">${escHtml(m.product_name)}</strong></span>
                            <span class="badge ${m.type === 'entrada' ? 'bg-success' : m.type === 'saida' ? 'bg-danger' : 'bg-secondary'}">${m.type === 'entrada' ? '+' : '-'}${parseFloat(m.quantity).toFixed(0)}</span>
                        </div>
                        <small class="text-muted">${m.warehouse_name} · ${new Date(m.created_at).toLocaleDateString('pt-BR')}</small>
                    </div>
                `;
            });
            html += '</div>';
            container.innerHTML = html;
        });
});
</script>
