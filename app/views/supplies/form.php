<?php
/**
 * Insumos — Formulário (criar/editar)
 * Variáveis: $supply (null = novo), $categories, $nextCode
 */
$isEdit = !empty($supply);
$s = $supply ?? [];
$units = ['un','kg','g','mg','L','mL','m','cm','mm','m2','m3','pc','cx','rl','fl','par'];
$csrfToken = csrf_token();
?>

<div class="container-fluid py-3">

    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div>
            <h1 class="h2 mb-1">
                <i class="fas fa-cubes me-2 text-primary"></i>
                <?= $isEdit ? 'Editar Insumo' : 'Novo Insumo' ?>
            </h1>
        </div>
        <a href="?page=supplies" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
    </div>

    <?php if ($isEdit): ?>
    <!-- Tabs (somente edição) -->
    <ul class="nav nav-tabs mb-3" id="supplyTabs">
        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tabDados">Dados</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabFornecedores">Fornecedores</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabProdutos">Produtos (BOM)</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabSubstitutos">Substitutos</a></li>
    </ul>
    <?php endif; ?>

    <div class="tab-content">
        <!-- Tab: Dados -->
        <div class="tab-pane fade show active" id="tabDados">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="post" action="?page=supplies&action=<?= $isEdit ? 'update' : 'store' ?>">
                        <?= csrf_field() ?>
                        <?php if ($isEdit): ?>
                            <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                        <?php endif; ?>

                        <div class="row g-3">
                            <!-- Código -->
                            <div class="col-md-2">
                                <label class="form-label fw-bold">Código <span class="text-danger">*</span></label>
                                <input type="text" name="code" class="form-control" value="<?= eAttr($s['code'] ?? $nextCode ?? '') ?>" required>
                            </div>
                            <!-- Nome -->
                            <div class="col-md-5">
                                <label class="form-label fw-bold">Nome <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="<?= eAttr($s['name'] ?? '') ?>" required>
                            </div>
                            <!-- Categoria -->
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Categoria</label>
                                <div class="input-group">
                                    <select name="category_id" id="categorySelect" class="form-select">
                                        <option value="">Sem categoria</option>
                                        <?php foreach ($categories as $cat): ?>
                                        <option value="<?= (int) $cat['id'] ?>" <?= ((int)($s['category_id'] ?? 0)) === (int)$cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnNewCategory" title="Nova categoria"><i class="fas fa-plus"></i></button>
                                </div>
                            </div>
                            <!-- Unidade -->
                            <div class="col-md-2">
                                <label class="form-label fw-bold">Unidade</label>
                                <select name="unit_measure" class="form-select">
                                    <?php foreach ($units as $u): ?>
                                    <option value="<?= $u ?>" <?= ($s['unit_measure'] ?? 'un') === $u ? 'selected' : '' ?>><?= $u ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <!-- Status -->
                            <div class="col-md-2">
                                <label class="form-label fw-bold">Status</label>
                                <select name="is_active" class="form-select">
                                    <option value="1" <?= ((int)($s['is_active'] ?? 1)) === 1 ? 'selected' : '' ?>>Ativo</option>
                                    <option value="0" <?= ((int)($s['is_active'] ?? 1)) === 0 ? 'selected' : '' ?>>Inativo</option>
                                </select>
                            </div>
                            <!-- Custo -->
                            <div class="col-md-2">
                                <label class="form-label fw-bold">Custo Unitário</label>
                                <input type="number" name="cost_price" class="form-control" step="0.0001" min="0" value="<?= eAttr($s['cost_price'] ?? '0') ?>">
                            </div>
                            <!-- Estoque Mínimo -->
                            <div class="col-md-2">
                                <label class="form-label fw-bold">Estoque Mínimo</label>
                                <input type="number" name="min_stock" class="form-control" step="0.01" min="0" value="<?= eAttr($s['min_stock'] ?? '0') ?>">
                            </div>
                            <!-- Ponto de Pedido -->
                            <div class="col-md-2">
                                <label class="form-label fw-bold">Ponto de Pedido</label>
                                <input type="number" name="reorder_point" class="form-control" step="0.01" min="0" value="<?= eAttr($s['reorder_point'] ?? '0') ?>">
                            </div>
                            <!-- % Perda -->
                            <div class="col-md-2">
                                <label class="form-label fw-bold">% Perda Padrão</label>
                                <input type="number" name="waste_percent" class="form-control" step="0.01" min="0" max="100" value="<?= eAttr($s['waste_percent'] ?? '0') ?>">
                            </div>
                            <!-- Fracionamento -->
                            <div class="col-md-2">
                                <label class="form-label fw-bold">Fracionamento</label>
                                <div class="form-check form-switch mt-2">
                                    <input type="hidden" name="permite_fracionamento" value="0">
                                    <input class="form-check-input" type="checkbox" name="permite_fracionamento" value="1" id="chkFracionamento"
                                        <?= ((int)($s['permite_fracionamento'] ?? 1)) === 1 ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="chkFracionamento">Permite frações</label>
                                </div>
                                <small class="text-muted">Se desativado, consumo é arredondado para cima (ex: parafusos)</small>
                            </div>
                            <!-- Precisão Decimal -->
                            <div class="col-md-2">
                                <label class="form-label fw-bold">Precisão Decimal</label>
                                <select name="decimal_precision" class="form-select">
                                    <?php for ($dp = 2; $dp <= 6; $dp++): ?>
                                    <option value="<?= $dp ?>" <?= ((int)($s['decimal_precision'] ?? 4)) === $dp ? 'selected' : '' ?>><?= $dp ?> casas</option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <!-- Descrição -->
                            <div class="col-12">
                                <label class="form-label fw-bold">Descrição</label>
                                <textarea name="description" class="form-control" rows="2"><?= e($s['description'] ?? '') ?></textarea>
                            </div>
                            <!-- Observações -->
                            <div class="col-12">
                                <label class="form-label fw-bold">Observações</label>
                                <textarea name="notes" class="form-control" rows="2"><?= e($s['notes'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <!-- Dados Fiscais (colapsável) -->
                        <div class="mt-3">
                            <a class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" href="#fiscalCollapse">
                                <i class="fas fa-file-invoice me-1"></i>Dados Fiscais
                            </a>
                            <div class="collapse <?= !empty($s['fiscal_ncm']) ? 'show' : '' ?>" id="fiscalCollapse">
                                <div class="row g-3 mt-1">
                                    <div class="col-md-3">
                                        <label class="form-label">NCM</label>
                                        <input type="text" name="fiscal_ncm" class="form-control" value="<?= eAttr($s['fiscal_ncm'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">CEST</label>
                                        <input type="text" name="fiscal_cest" class="form-control" value="<?= eAttr($s['fiscal_cest'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Origem</label>
                                        <input type="text" name="fiscal_origem" class="form-control" value="<?= eAttr($s['fiscal_origem'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Unidade Fiscal</label>
                                        <input type="text" name="fiscal_unidade" class="form-control" value="<?= eAttr($s['fiscal_unidade'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Salvar</button>
                            <a href="?page=supplies" class="btn btn-outline-secondary ms-2">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <?php if ($isEdit): ?>
        <!-- Tab: Fornecedores (Fase 2) -->
        <div class="tab-pane fade" id="tabFornecedores">
            <div class="card border-0 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-truck me-1"></i>Fornecedores Vinculados</span>
                    <button class="btn btn-sm btn-primary" id="btnLinkSupplier"><i class="fas fa-plus me-1"></i>Vincular</button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="suppliersTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Fornecedor</th>
                                    <th>SKU</th>
                                    <th class="text-end">Preço</th>
                                    <th class="text-end">Ped. Mín.</th>
                                    <th>Prazo</th>
                                    <th>Fator UOM</th>
                                    <th>Pref.</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="suppliersBody">
                                <tr><td colspan="8" class="text-center text-muted py-3">Carregando...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Gráfico de Preços (Fase 5) -->
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header"><i class="fas fa-chart-line me-1"></i>Histórico de Preços</div>
                <div class="card-body">
                    <canvas id="priceChart" height="200"></canvas>
                </div>
            </div>
        </div>

        <!-- Tab: Produtos BOM (Fase 6) -->
        <div class="tab-pane fade" id="tabProdutos">
            <div class="card border-0 shadow-sm">
                <div class="card-header"><i class="fas fa-sitemap me-1"></i>Produtos que usam este insumo</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Produto</th>
                                    <th>Código</th>
                                    <th class="text-end">Qtd</th>
                                    <th class="text-end">% Perda</th>
                                    <th class="text-end">Custo MP</th>
                                    <th class="text-end">Preço Venda</th>
                                </tr>
                            </thead>
                            <tbody id="productsBody">
                                <tr><td colspan="6" class="text-center text-muted py-3">Carregando...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab: Substitutos (v2) -->
        <div class="tab-pane fade" id="tabSubstitutos">
            <div class="card border-0 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-exchange-alt me-1"></i>Insumos Substitutos</span>
                    <button class="btn btn-sm btn-primary" id="btnAddSubstitute"><i class="fas fa-plus me-1"></i>Adicionar</button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="substitutesTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Insumo Substituto</th>
                                    <th>Código</th>
                                    <th class="text-end">Taxa Conversão</th>
                                    <th class="text-center">Prioridade</th>
                                    <th class="text-center">Ativo</th>
                                    <th>Notas</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="substitutesBody">
                                <tr><td colspan="7" class="text-center text-muted py-3">Carregando...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = '<?= $csrfToken ?>';
    <?php if ($isEdit): ?>
    const supplyId = <?= (int) $s['id'] ?>;

    // ── Carregar Fornecedores ──
    function loadSuppliers() {
        fetch('?page=supplies&action=getSuppliers&id=' + supplyId)
            .then(r => r.json())
            .then(data => {
                const body = document.getElementById('suppliersBody');
                if (!data.length) {
                    body.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-3">Nenhum fornecedor vinculado.</td></tr>';
                    return;
                }
                body.innerHTML = data.map(s => `
                    <tr>
                        <td>${s.company_name}${s.trade_name ? ' <small class="text-muted">(' + s.trade_name + ')</small>' : ''}</td>
                        <td>${s.supplier_sku || '-'}</td>
                        <td class="text-end">${parseFloat(s.unit_price).toFixed(4)}</td>
                        <td class="text-end">${parseFloat(s.min_order_qty).toFixed(2)}</td>
                        <td>${s.lead_time_days ? s.lead_time_days + ' dias' : '-'}</td>
                        <td>&times;${parseFloat(s.conversion_factor).toFixed(4)}</td>
                        <td>${parseInt(s.is_preferred) ? '<i class="fas fa-star text-warning"></i>' : ''}</td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-danger btnUnlink" data-id="${s.id}"><i class="fas fa-unlink"></i></button>
                        </td>
                    </tr>
                `).join('');

                body.querySelectorAll('.btnUnlink').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const linkId = this.dataset.id;
                        Swal.fire({title:'Desvincular?',icon:'warning',showCancelButton:true,confirmButtonColor:'#d33',confirmButtonText:'Sim',cancelButtonText:'Cancelar'}).then(r => {
                            if (r.isConfirmed) {
                                fetch('?page=supplies&action=unlinkSupplier', {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded','X-CSRF-TOKEN':csrfToken},body:'id='+linkId+'&csrf_token='+csrfToken}).then(()=>loadSuppliers());
                            }
                        });
                    });
                });
            });
    }
    loadSuppliers();

    // ── Vincular Fornecedor ──
    document.getElementById('btnLinkSupplier').addEventListener('click', function() {
        Swal.fire({
            title: 'Vincular Fornecedor',
            html: `
                <div class="text-start">
                    <div class="mb-2"><label class="form-label">Fornecedor</label><select id="swalSupplier" class="form-select"></select></div>
                    <div class="row g-2 mb-2">
                        <div class="col-6"><label class="form-label">SKU</label><input id="swalSku" class="form-control" type="text"></div>
                        <div class="col-6"><label class="form-label">Preço</label><input id="swalPrice" class="form-control" type="number" step="0.0001" value="0"></div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-4"><label class="form-label">Ped. Mín.</label><input id="swalMinQty" class="form-control" type="number" step="0.01" value="1"></div>
                        <div class="col-4"><label class="form-label">Prazo (dias)</label><input id="swalLead" class="form-control" type="number" value="0"></div>
                        <div class="col-4"><label class="form-label">Fator UOM</label><input id="swalFactor" class="form-control" type="number" step="0.000001" value="1"></div>
                    </div>
                    <div class="form-check mb-2"><input id="swalPreferred" class="form-check-input" type="checkbox"><label class="form-check-label" for="swalPreferred">Preferencial</label></div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Vincular',
            cancelButtonText: 'Cancelar',
            didOpen: () => {
                fetch('?page=supplies&action=searchSuppliers&term=').then(r=>r.json()).then(d=>{
                    const sel = document.getElementById('swalSupplier');
                    d.results.forEach(s=>{ const o=document.createElement('option'); o.value=s.id; o.text=s.text; sel.add(o); });
                });
            },
            preConfirm: () => {
                return {
                    supply_id: supplyId,
                    supplier_id: document.getElementById('swalSupplier').value,
                    supplier_sku: document.getElementById('swalSku').value,
                    unit_price: document.getElementById('swalPrice').value,
                    min_order_qty: document.getElementById('swalMinQty').value,
                    lead_time_days: document.getElementById('swalLead').value,
                    conversion_factor: document.getElementById('swalFactor').value,
                    is_preferred: document.getElementById('swalPreferred').checked ? 1 : 0,
                };
            }
        }).then(result => {
            if (result.isConfirmed) {
                const d = result.value;
                const body = Object.keys(d).map(k => k+'='+encodeURIComponent(d[k])).join('&') + '&csrf_token=' + csrfToken;
                fetch('?page=supplies&action=linkSupplier', {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded','X-CSRF-TOKEN':csrfToken},body:body})
                    .then(r=>r.json()).then(r=>{
                        if (r.success) { loadSuppliers(); AktiToast.success('Fornecedor vinculado.'); }
                        else AktiToast.error(r.message || 'Erro ao vincular.');
                    });
            }
        });
    });

    // ── Carregar Produtos (BOM) ──
    function loadProducts() {
        fetch('?page=supplies&action=getSupplyProducts&id=' + supplyId)
            .then(r => r.json())
            .then(data => {
                const body = document.getElementById('productsBody');
                if (!data.length) {
                    body.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">Nenhum produto usa este insumo.</td></tr>';
                    return;
                }
                body.innerHTML = data.map(p => `
                    <tr>
                        <td>${p.product_name}</td>
                        <td><span class="badge bg-light text-dark">${p.product_code || '-'}</span></td>
                        <td class="text-end">${parseFloat(p.quantity).toFixed(4)}</td>
                        <td class="text-end">${parseFloat(p.waste_percent).toFixed(2)}%</td>
                        <td class="text-end">—</td>
                        <td class="text-end">${parseFloat(p.product_price).toFixed(2)}</td>
                    </tr>
                `).join('');
            });
    }
    loadProducts();

    // ── Gráfico de Preços (Chart.js) ──
    fetch('?page=supplies&action=getPriceHistory&id=' + supplyId)
        .then(r => r.json())
        .then(data => {
            if (!data.length) return;
            const labels = data.map(d => d.created_at ? d.created_at.substring(0,10) : '').reverse();
            const prices = data.map(d => parseFloat(d.unit_price)).reverse();
            if (typeof Chart !== 'undefined') {
                new Chart(document.getElementById('priceChart'), {
                    type: 'line',
                    data: { labels, datasets: [{ label: 'Preço', data: prices, borderColor: '#0d6efd', tension: 0.3, fill: false }] },
                    options: { responsive: true, plugins: { legend: { display: false } } }
                });
            }
        });

    // ── Carregar Substitutos (v2) ──
    function loadSubstitutes() {
        fetch('?page=supplies&action=getSubstitutes&id=' + supplyId)
            .then(r => r.json())
            .then(data => {
                const body = document.getElementById('substitutesBody');
                if (!data.length) {
                    body.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-3">Nenhum substituto cadastrado.</td></tr>';
                    return;
                }
                body.innerHTML = data.map(s => `
                    <tr>
                        <td>${s.substitute_name}</td>
                        <td><span class="badge bg-light text-dark">${s.substitute_code || '-'}</span></td>
                        <td class="text-end">&times;${parseFloat(s.conversion_rate).toFixed(6)}</td>
                        <td class="text-center"><span class="badge bg-secondary">${s.priority}</span></td>
                        <td class="text-center">${parseInt(s.is_active) ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>'}</td>
                        <td>${s.notes || '-'}</td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary btnEditSub" data-item='${JSON.stringify(s)}'><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm btn-outline-danger btnDelSub" data-id="${s.id}"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                `).join('');

                body.querySelectorAll('.btnDelSub').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const sid = this.dataset.id;
                        Swal.fire({title:'Remover substituto?',icon:'warning',showCancelButton:true,confirmButtonColor:'#d33',confirmButtonText:'Sim',cancelButtonText:'Cancelar'}).then(r => {
                            if (r.isConfirmed) {
                                fetch('?page=supplies&action=removeSubstitute', {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded','X-CSRF-TOKEN':csrfToken},body:'id='+sid+'&csrf_token='+csrfToken})
                                    .then(r=>r.json()).then(r=>{ if(r.success){loadSubstitutes(); AktiToast.success('Substituto removido.');} else AktiToast.error(r.message||'Erro.'); });
                            }
                        });
                    });
                });

                body.querySelectorAll('.btnEditSub').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const item = JSON.parse(this.dataset.item);
                        showSubstituteModal(item);
                    });
                });
            });
    }
    loadSubstitutes();

    function showSubstituteModal(existing) {
        const isEdit = !!existing;
        Swal.fire({
            title: isEdit ? 'Editar Substituto' : 'Adicionar Substituto',
            html: `
                <div class="text-start">
                    <div class="mb-2"><label class="form-label">Insumo Substituto</label><select id="swalSubSupply" class="form-select" ${isEdit ? 'disabled' : ''}></select></div>
                    <div class="row g-2 mb-2">
                        <div class="col-4"><label class="form-label">Taxa Conversão</label><input id="swalConvRate" class="form-control" type="number" step="0.000001" value="${existing ? existing.conversion_rate : '1'}"></div>
                        <div class="col-4"><label class="form-label">Prioridade</label><input id="swalPriority" class="form-control" type="number" min="1" value="${existing ? existing.priority : '1'}"></div>
                        <div class="col-4"><label class="form-label">Ativo</label><select id="swalSubActive" class="form-select"><option value="1" ${(!existing || parseInt(existing.is_active)) ? 'selected' : ''}>Sim</option><option value="0" ${(existing && !parseInt(existing.is_active)) ? 'selected' : ''}>Não</option></select></div>
                    </div>
                    <div class="mb-2"><label class="form-label">Notas</label><textarea id="swalSubNotes" class="form-control" rows="2">${existing ? (existing.notes||'') : ''}</textarea></div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: isEdit ? 'Salvar' : 'Adicionar',
            cancelButtonText: 'Cancelar',
            didOpen: () => {
                fetch('?page=supplies&action=searchSelect2&term=').then(r=>r.json()).then(d=>{
                    const sel = document.getElementById('swalSubSupply');
                    d.results.forEach(s=>{
                        if (s.id != supplyId) {
                            const o=document.createElement('option'); o.value=s.id; o.text=s.text;
                            if (existing && s.id == existing.substitute_id) o.selected = true;
                            sel.add(o);
                        }
                    });
                });
            },
            preConfirm: () => {
                return {
                    substitute_id: document.getElementById('swalSubSupply').value,
                    conversion_rate: document.getElementById('swalConvRate').value,
                    priority: document.getElementById('swalPriority').value,
                    is_active: document.getElementById('swalSubActive').value,
                    notes: document.getElementById('swalSubNotes').value,
                };
            }
        }).then(result => {
            if (result.isConfirmed) {
                const d = result.value;
                let bodyStr;
                let action;
                if (isEdit) {
                    bodyStr = 'id=' + existing.id + '&conversion_rate=' + encodeURIComponent(d.conversion_rate) + '&priority=' + d.priority + '&is_active=' + d.is_active + '&notes=' + encodeURIComponent(d.notes) + '&csrf_token=' + csrfToken;
                    action = 'updateSubstitute';
                } else {
                    bodyStr = 'supply_id=' + supplyId + '&substitute_id=' + d.substitute_id + '&conversion_rate=' + encodeURIComponent(d.conversion_rate) + '&priority=' + d.priority + '&is_active=' + d.is_active + '&notes=' + encodeURIComponent(d.notes) + '&csrf_token=' + csrfToken;
                    action = 'addSubstitute';
                }
                fetch('?page=supplies&action=' + action, {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded','X-CSRF-TOKEN':csrfToken},body:bodyStr})
                    .then(r=>r.json()).then(r=>{
                        if (r.success) { loadSubstitutes(); AktiToast.success(isEdit ? 'Substituto atualizado.' : 'Substituto adicionado.'); }
                        else AktiToast.error(r.message || 'Erro.');
                    });
            }
        });
    }

    document.getElementById('btnAddSubstitute')?.addEventListener('click', function() {
        showSubstituteModal(null);
    });
    <?php endif; ?>

    // ── Nova Categoria (inline) ──
    document.getElementById('btnNewCategory')?.addEventListener('click', function() {
        Swal.fire({
            title: 'Nova Categoria',
            input: 'text',
            inputLabel: 'Nome da categoria',
            showCancelButton: true,
            confirmButtonText: 'Criar',
            cancelButtonText: 'Cancelar'
        }).then(result => {
            if (result.isConfirmed && result.value) {
                fetch('?page=supplies&action=createCategoryAjax', {
                    method: 'POST',
                    headers: {'Content-Type':'application/x-www-form-urlencoded','X-CSRF-TOKEN':csrfToken},
                    body: 'name=' + encodeURIComponent(result.value) + '&csrf_token=' + csrfToken
                }).then(r => r.json()).then(r => {
                    if (r.success) {
                        const sel = document.getElementById('categorySelect');
                        const opt = document.createElement('option');
                        opt.value = r.id;
                        opt.text = r.name;
                        opt.selected = true;
                        sel.add(opt);
                        if (typeof AktiToast !== 'undefined') AktiToast.success('Categoria criada.');
                    }
                });
            }
        });
    });
});
</script>
