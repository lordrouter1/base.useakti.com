<?php
/**
 * Comissões — Regras por Produto/Categoria
 * Padrão visual: Financeiro (sidebar em card, filtros dinâmicos auto-apply).
 * Variáveis: $regras, $products, $categories
 */
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
                    <div class="icon-circle icon-circle-teal me-2">
                        <i class="fas fa-box text-teal" style="font-size:.85rem;"></i>
                    </div>
                    <div>
                        <h5 class="mb-0" style="font-size:1rem;">Regras por Produto / Categoria</h5>
                        <p class="text-muted mb-0" style="font-size:.72rem;">Prioridade 3 — verificada quando não há regra de usuário ou grupo.</p>
                    </div>
                </div>
                <button class="btn btn-sm btn-success" onclick="openProdutoModal()"><i class="fas fa-plus me-1"></i>Nova Regra</button>
            </div>

            <!-- Filtro dinâmico -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body py-2">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label small mb-1">Buscar</label>
                            <input type="text" id="filtro_prod_search" class="form-control form-control-sm" placeholder="Nome do produto ou categoria...">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-1">Tipo</label>
                            <select id="filtro_prod_tipo" class="form-select form-select-sm">
                                <option value="">Todos</option>
                                <option value="percentual">Percentual</option>
                                <option value="valor_fixo">Valor Fixo</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-1">Status</label>
                            <select id="filtro_prod_status" class="form-select form-select-sm">
                                <option value="">Todos</option>
                                <option value="1">Ativo</option>
                                <option value="0">Inativo</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-sm btn-outline-secondary w-100" onclick="clearProdutoFilter()"><i class="fas fa-times me-1"></i>Limpar</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabela de regras -->
            <?php if (empty($regras)): ?>
            <div class="text-center text-muted py-4">
                <i class="fas fa-box fa-2x d-block mb-2 opacity-50 text-teal"></i>
                <small>Nenhuma regra por produto cadastrada. Clique em <strong>Nova Regra</strong> para criar a primeira.</small>
            </div>
            <?php else: ?>
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Produto / Categoria</th>
                                <th>Tipo</th>
                                <th class="text-end">Valor</th>
                                <th class="text-center">Status</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="produtosBody">
                            <?php foreach ($regras as $r): ?>
                            <tr class="produto-row"
                                data-name="<?= eAttr(mb_strtolower(($r['product_name'] ?? '') . ' ' . ($r['category_name'] ?? ''))) ?>"
                                data-tipo="<?= eAttr($r['tipo_calculo']) ?>"
                                data-ativo="<?= $r['ativo'] ? '1' : '0' ?>">
                                <td>
                                    <?php if ($r['product_name']): ?>
                                        <i class="fas fa-box me-1 text-primary"></i> <?= e($r['product_name']) ?>
                                    <?php elseif ($r['category_name']): ?>
                                        <i class="fas fa-folder me-1 text-warning"></i> Categoria: <?= e($r['category_name']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-light text-dark"><?= e($r['tipo_calculo']) ?></span></td>
                                <td class="text-end fw-semibold">
                                    <?php if ($r['tipo_calculo'] === 'percentual'): ?>
                                        <?= number_format($r['valor'], 2, ',', '.') ?>%
                                    <?php else: ?>
                                        R$ <?= number_format($r['valor'], 2, ',', '.') ?>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?= $r['ativo'] ? '<span class="badge bg-success">Ativo</span>' : '<span class="badge bg-secondary">Inativo</span>' ?>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteProdutoRegra(<?= $r['id'] ?>)" title="Excluir">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal: Nova Regra Produto -->
<div class="modal fade" id="produtoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="produtoForm">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-box me-2"></i>Nova Regra por Produto/Categoria</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Produto</label>
                        <select name="product_id" class="form-select">
                            <option value="">Nenhum (regra por categoria)</option>
                            <?php foreach ($products as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= e($p['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Categoria</label>
                        <select name="category_id" class="form-select">
                            <option value="">Nenhuma (regra por produto)</option>
                            <?php foreach ($categories as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Selecione produto OU categoria (ou ambos para regra específica).</small>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Tipo de Cálculo</label>
                            <select name="tipo_calculo" class="form-select">
                                <option value="percentual">Percentual</option>
                                <option value="valor_fixo">Valor Fixo</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Valor</label>
                            <input type="number" name="valor" class="form-control" step="0.01" value="0" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

function openProdutoModal() {
    new bootstrap.Modal(document.getElementById('produtoModal')).show();
}

document.getElementById('produtoForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.set('csrf_token', csrfToken);

    fetch('?page=commissions&action=saveProdutoRegra', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                Swal.fire({icon:'success', title:'Sucesso', text: res.message, timer:1500, showConfirmButton:false})
                    .then(() => location.reload());
            } else {
                Swal.fire({icon:'error', title:'Erro', text: res.message});
            }
        });
});

function deleteProdutoRegra(id) {
    Swal.fire({
        title: 'Excluir regra?', icon: 'warning', showCancelButton: true,
        confirmButtonColor: '#e74c3c', confirmButtonText: 'Sim, excluir', cancelButtonText: 'Cancelar'
    }).then(result => {
        if (result.isConfirmed) {
            fetch(`?page=commissions&action=deleteProdutoRegra&id=${id}&csrf_token=${csrfToken}`)
                .then(r => r.json())
                .then(res => {
                    if (res.success) location.reload();
                    else Swal.fire({icon:'error', title:'Erro', text: res.message});
                });
        }
    });
}

// ── Filtros dinâmicos (auto-apply) ──
function filterProdutos() {
    const search = document.getElementById('filtro_prod_search').value.toLowerCase();
    const tipo   = document.getElementById('filtro_prod_tipo').value;
    const status = document.getElementById('filtro_prod_status').value;
    let count = 0;
    document.querySelectorAll('.produto-row').forEach(row => {
        const matchSearch = !search || row.dataset.name.includes(search);
        const matchTipo   = !tipo   || row.dataset.tipo === tipo;
        const matchStatus = !status || row.dataset.ativo === status;
        const show = matchSearch && matchTipo && matchStatus;
        row.style.display = show ? '' : 'none';
        if (show) count++;
    });
    let noResult = document.getElementById('produtosNoResult');
    if (!noResult) {
        noResult = document.createElement('tr');
        noResult.id = 'produtosNoResult';
        noResult.innerHTML = '<td colspan="5" class="text-center text-muted py-3"><i class="fas fa-search me-1"></i>Nenhum resultado.</td>';
        document.getElementById('produtosBody')?.appendChild(noResult);
    }
    noResult.style.display = count === 0 ? '' : 'none';
}

function clearProdutoFilter() {
    document.getElementById('filtro_prod_search').value = '';
    document.getElementById('filtro_prod_tipo').value = '';
    document.getElementById('filtro_prod_status').value = '';
    filterProdutos();
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('filtro_prod_search')?.addEventListener('input', filterProdutos);
    document.getElementById('filtro_prod_tipo')?.addEventListener('change', filterProdutos);
    document.getElementById('filtro_prod_status')?.addEventListener('change', filterProdutos);
});
</script>
