<?php
/**
 * Comissões — Formas de Comissão (CRUD)
 * Cadastro de modelos genéricos de comissão.
 * Padrão visual: Financeiro (sidebar em card, header padronizado).
 * Variáveis: $formas, $aux
 */
$tipoLabels = ['percentual' => 'Percentual', 'valor_fixo' => 'Valor Fixo', 'faixa' => 'Faixa Progressiva'];
$baseLabels = ['valor_venda' => 'Valor da Venda', 'margem_lucro' => 'Margem de Lucro', 'valor_produto' => 'Valor do Produto'];
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
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-2" style="width:34px;height:34px;background:rgba(39,174,96,.1);">
                        <i class="fas fa-file-alt" style="color:#27ae60;font-size:.85rem;"></i>
                    </div>
                    <div>
                        <h5 class="mb-0" style="font-size:1rem;">Formas de Comissão</h5>
                        <p class="text-muted mb-0" style="font-size:.72rem;">Modelos reutilizáveis de comissão (percentual, fixo ou faixa).</p>
                    </div>
                </div>
                <button class="btn btn-sm btn-success" onclick="openFormaModal()"><i class="fas fa-plus me-1"></i>Nova Forma</button>
            </div>

            <!-- Cards resumo rápido -->
            <div class="row g-3 mb-4">
                <div class="col-xl-4 col-md-6">
                    <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
                        <div class="card-body d-flex align-items-center p-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:42px;height:42px;background:rgba(39,174,96,0.15);">
                                <i class="fas fa-list text-success"></i>
                            </div>
                            <div>
                                <div class="text-muted small text-uppercase fw-bold" style="font-size:.65rem;">Total Cadastradas</div>
                                <div class="fw-bold fs-5"><?= count($formas) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-6">
                    <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
                        <div class="card-body d-flex align-items-center p-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:42px;height:42px;background:rgba(52,152,219,0.15);">
                                <i class="fas fa-check-circle text-primary"></i>
                            </div>
                            <div>
                                <div class="text-muted small text-uppercase fw-bold" style="font-size:.65rem;">Ativas</div>
                                <div class="fw-bold fs-5"><?= count(array_filter($formas, fn($f) => $f['ativo'])) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-6">
                    <div class="card border-0 shadow-sm h-100 border-start border-info border-4">
                        <div class="card-body d-flex align-items-center p-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:42px;height:42px;background:rgba(23,162,184,0.15);">
                                <i class="fas fa-layer-group text-info"></i>
                            </div>
                            <div>
                                <div class="text-muted small text-uppercase fw-bold" style="font-size:.65rem;">Com Faixas</div>
                                <div class="fw-bold fs-5"><?= count(array_filter($formas, fn($f) => $f['tipo_calculo'] === 'faixa')) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtro dinâmico -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body py-2">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label small mb-1">Buscar</label>
                            <input type="text" id="filtro_formas_search" class="form-control form-control-sm" placeholder="Nome ou descrição...">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-1">Tipo</label>
                            <select id="filtro_formas_tipo" class="form-select form-select-sm">
                                <option value="">Todos</option>
                                <option value="percentual">Percentual</option>
                                <option value="valor_fixo">Valor Fixo</option>
                                <option value="faixa">Faixa Progressiva</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-1">Status</label>
                            <select id="filtro_formas_status" class="form-select form-select-sm">
                                <option value="">Todos</option>
                                <option value="1">Ativo</option>
                                <option value="0">Inativo</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button class="btn btn-sm btn-outline-secondary w-100" onclick="clearFormasFilter()"><i class="fas fa-times me-1"></i>Limpar</button>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (empty($formas)): ?>
            <div class="text-center text-muted py-5">
                <i class="fas fa-file-alt fa-3x d-block mb-3 text-success opacity-50"></i>
                <p class="mb-2">Nenhuma forma de comissão cadastrada.</p>
                <button class="btn btn-success" onclick="openFormaModal()"><i class="fas fa-plus me-1"></i>Criar Primeira Forma</button>
            </div>
            <?php else: ?>
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="formasTable">
                        <thead class="table-light">
                            <tr>
                                <th>Nome</th>
                                <th>Tipo de Cálculo</th>
                                <th>Base</th>
                                <th class="text-end">Valor</th>
                                <th class="text-center">Faixas</th>
                                <th class="text-center">Vínculos</th>
                                <th class="text-center">Status</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="formasBody">
                        <?php foreach ($formas as $f): ?>
                        <tr class="forma-row" 
                            data-nome="<?= eAttr(mb_strtolower($f['nome'] . ' ' . ($f['descricao'] ?? ''))) ?>" 
                            data-tipo="<?= eAttr($f['tipo_calculo']) ?>" 
                            data-ativo="<?= $f['ativo'] ?>">
                            <td>
                                <div class="fw-semibold"><?= e($f['nome']) ?></div>
                                <?php if ($f['descricao']): ?>
                                <small class="text-muted"><?= e(mb_strimwidth($f['descricao'], 0, 60, '...')) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge bg-light text-dark"><?= $tipoLabels[$f['tipo_calculo']] ?? $f['tipo_calculo'] ?></span></td>
                            <td><small><?= $baseLabels[$f['base_calculo']] ?? $f['base_calculo'] ?></small></td>
                            <td class="text-end fw-semibold">
                                <?php if ($f['tipo_calculo'] === 'percentual'): ?>
                                    <?= number_format($f['valor'], 2, ',', '.') ?>%
                                <?php elseif ($f['tipo_calculo'] === 'valor_fixo'): ?>
                                    R$ <?= number_format($f['valor'], 2, ',', '.') ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($f['tipo_calculo'] === 'faixa'): ?>
                                    <span class="badge bg-info"><?= $f['total_faixas'] ?></span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark" title="Grupos"><?= $f['total_grupos'] ?> <i class="fas fa-users ms-1"></i></span>
                                <span class="badge bg-light text-dark" title="Usuários"><?= $f['total_usuarios'] ?> <i class="fas fa-user ms-1"></i></span>
                            </td>
                            <td class="text-center">
                                <?php if ($f['ativo']): ?>
                                    <span class="badge bg-success"><i class="fas fa-check"></i> Ativo</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><i class="fas fa-ban"></i> Inativo</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary" onclick="editForma(<?= eAttr(json_encode($f)) ?>)" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteForma(<?= $f['id'] ?>, '<?= eAttr($f['nome']) ?>')" title="Excluir">
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

<!-- Modal: Nova/Editar Forma -->
<div class="modal fade" id="formaModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formaForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="formaModalTitle"><i class="fas fa-file-alt me-2"></i>Nova Forma de Comissão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" id="forma_id">

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-bold">Nome <span class="text-danger">*</span></label>
                            <input type="text" name="nome" id="forma_nome" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Status</label>
                            <select name="ativo" id="forma_ativo" class="form-select">
                                <option value="1">Ativo</option>
                                <option value="0">Inativo</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Descrição</label>
                            <textarea name="descricao" id="forma_descricao" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Tipo de Cálculo</label>
                            <select name="tipo_calculo" id="forma_tipo" class="form-select" onchange="toggleFaixas()">
                                <option value="percentual">Percentual</option>
                                <option value="valor_fixo">Valor Fixo</option>
                                <option value="faixa">Faixa Progressiva</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Base de Cálculo</label>
                            <select name="base_calculo" id="forma_base" class="form-select">
                                <option value="valor_venda">Valor da Venda</option>
                                <option value="margem_lucro">Margem de Lucro</option>
                                <option value="valor_produto">Valor do Produto</option>
                            </select>
                        </div>
                        <div class="col-md-4" id="valorField">
                            <label class="form-label fw-bold" id="valorLabel">Percentual (%)</label>
                            <input type="number" name="valor" id="forma_valor" class="form-control" step="0.01" value="0">
                        </div>
                    </div>

                    <!-- Faixas (visível quando tipo = faixa) -->
                    <div id="faixasContainer" class="mt-3" style="display:none">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0"><i class="fas fa-layer-group me-1"></i>Faixas Progressivas</h6>
                            <button type="button" class="btn btn-sm btn-outline-success" onclick="addFaixa()"><i class="fas fa-plus me-1"></i>Adicionar</button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered" id="faixasTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Mínimo</th>
                                        <th>Máximo</th>
                                        <th>Percentual (%)</th>
                                        <th style="width:60px"></th>
                                    </tr>
                                </thead>
                                <tbody id="faixasBody"></tbody>
                            </table>
                        </div>
                        <small class="text-muted"><i class="fas fa-info-circle me-1"></i>Deixe "Máximo" vazio para sem limite superior.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="formaSubmitBtn"><i class="fas fa-save me-1"></i>Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

function openFormaModal(data = null) {
    document.getElementById('forma_id').value = data?.id || '';
    document.getElementById('forma_nome').value = data?.nome || '';
    document.getElementById('forma_descricao').value = data?.descricao || '';
    document.getElementById('forma_tipo').value = data?.tipo_calculo || 'percentual';
    document.getElementById('forma_base').value = data?.base_calculo || 'valor_venda';
    document.getElementById('forma_valor').value = data?.valor || 0;
    document.getElementById('forma_ativo').value = data?.ativo ?? 1;
    document.getElementById('formaModalTitle').innerHTML = data?.id
        ? '<i class="fas fa-edit me-2"></i>Editar Forma'
        : '<i class="fas fa-file-alt me-2"></i>Nova Forma';
    document.getElementById('faixasBody').innerHTML = '';
    toggleFaixas();

    if (data?.id && data.tipo_calculo === 'faixa') {
        fetch(`?page=commissions&action=getFaixas&id=${data.id}`)
            .then(r => r.json())
            .then(res => {
                if (res.success && res.data) {
                    res.data.forEach(f => addFaixa(f.faixa_min, f.faixa_max, f.percentual));
                }
            });
    }

    new bootstrap.Modal(document.getElementById('formaModal')).show();
}

function editForma(data) {
    openFormaModal(data);
}

function toggleFaixas() {
    const tipo = document.getElementById('forma_tipo').value;
    document.getElementById('faixasContainer').style.display = tipo === 'faixa' ? 'block' : 'none';
    document.getElementById('valorField').style.display = tipo === 'faixa' ? 'none' : '';
    document.getElementById('valorLabel').textContent = tipo === 'percentual' ? 'Percentual (%)' : 'Valor (R$)';
}

function addFaixa(min = '', max = '', pct = '') {
    const row = document.createElement('tr');
    row.innerHTML = `
        <td><input type="number" name="faixa_min[]" class="form-control form-control-sm" step="0.01" value="${min}" required></td>
        <td><input type="number" name="faixa_max[]" class="form-control form-control-sm" step="0.01" value="${max || ''}"></td>
        <td><input type="number" name="faixa_pct[]" class="form-control form-control-sm" step="0.01" value="${pct}" required></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()"><i class="fas fa-times"></i></button></td>
    `;
    document.getElementById('faixasBody').appendChild(row);
}

document.getElementById('formaForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const id = document.getElementById('forma_id').value;
    const action = id ? 'updateForma' : 'storeForma';
    const formData = new FormData(this);
    formData.set('csrf_token', csrfToken);

    fetch(`?page=commissions&action=${action}`, { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                Swal.fire({icon:'success', title:'Sucesso', text: res.message, timer: 1500, showConfirmButton: false})
                    .then(() => location.reload());
            } else {
                Swal.fire({icon:'error', title:'Erro', text: res.message});
            }
        })
        .catch(() => Swal.fire({icon:'error', title:'Erro', text:'Falha na comunicação.'}));
});

function deleteForma(id, nome) {
    Swal.fire({
        title: 'Excluir forma?',
        html: `Tem certeza que deseja excluir <strong>${nome}</strong>?<br>Todos os vínculos serão removidos.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e74c3c',
        confirmButtonText: 'Sim, excluir',
        cancelButtonText: 'Cancelar'
    }).then(result => {
        if (result.isConfirmed) {
            fetch(`?page=commissions&action=deleteForma&id=${id}&csrf_token=${csrfToken}`)
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        Swal.fire({icon:'success', title:'Excluído', timer: 1200, showConfirmButton: false})
                            .then(() => location.reload());
                    } else {
                        Swal.fire({icon:'error', title:'Erro', text: res.message});
                    }
                });
        }
    });
}

// ── Filtros dinâmicos (auto-apply) ──
function filterFormas() {
    const search = document.getElementById('filtro_formas_search').value.toLowerCase();
    const tipo   = document.getElementById('filtro_formas_tipo').value;
    const status = document.getElementById('filtro_formas_status').value;
    let count = 0;

    document.querySelectorAll('.forma-row').forEach(row => {
        const matchSearch = !search || row.dataset.nome.includes(search);
        const matchTipo   = !tipo   || row.dataset.tipo === tipo;
        const matchStatus = status === '' || row.dataset.ativo === status;
        const show = matchSearch && matchTipo && matchStatus;
        row.style.display = show ? '' : 'none';
        if (show) count++;
    });

    // Mostrar mensagem se nenhum resultado
    let noResult = document.getElementById('formasNoResult');
    if (!noResult) {
        noResult = document.createElement('tr');
        noResult.id = 'formasNoResult';
        noResult.innerHTML = '<td colspan="8" class="text-center text-muted py-3"><i class="fas fa-search me-1"></i>Nenhum resultado para o filtro.</td>';
        document.getElementById('formasBody')?.appendChild(noResult);
    }
    noResult.style.display = count === 0 ? '' : 'none';
}

function clearFormasFilter() {
    document.getElementById('filtro_formas_search').value = '';
    document.getElementById('filtro_formas_tipo').value = '';
    document.getElementById('filtro_formas_status').value = '';
    filterFormas();
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('filtro_formas_search')?.addEventListener('input', filterFormas);
    document.getElementById('filtro_formas_tipo')?.addEventListener('change', filterFormas);
    document.getElementById('filtro_formas_status')?.addEventListener('change', filterFormas);
});
</script>
