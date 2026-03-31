<?php
/**
 * Comissões — Regras por Usuário
 * Vinculação de formas de comissão a usuários específicos.
 * Padrão visual: Financeiro (sidebar em card, filtros dinâmicos auto-apply).
 * Variáveis: $usuarioFormas, $usuariosComRegras, $aux
 */
$users  = $aux['users'] ?? [];
$formas = $aux['formas'] ?? [];
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
                    <div class="icon-circle icon-circle-carrot me-2">
                        <i class="fas fa-user-tag text-carrot" style="font-size:.85rem;"></i>
                    </div>
                    <div>
                        <h5 class="mb-0" style="font-size:1rem;">Regras por Usuário</h5>
                        <p class="text-muted mb-0" style="font-size:.72rem;">Prioridade 1 (mais alta) — sobrepõe grupo e padrão.</p>
                    </div>
                </div>
                <button class="btn btn-sm btn-success" onclick="openLinkUsuarioModal()"><i class="fas fa-plus me-1"></i>Vincular</button>
            </div>

            <!-- Filtro dinâmico -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body py-2">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label small mb-1">Buscar</label>
                            <input type="text" id="filtro_user_search" class="form-control form-control-sm" placeholder="Nome do usuário...">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-1">Regra Ativa</label>
                            <select id="filtro_user_regra" class="form-select form-select-sm">
                                <option value="">Todos</option>
                                <option value="usuario">Usuário (individual)</option>
                                <option value="grupo">Grupo</option>
                                <option value="padrao">Padrão</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-1">Grupo</label>
                            <select id="filtro_user_grupo" class="form-select form-select-sm">
                                <option value="">Todos</option>
                                <?php
                                $groupNames = [];
                                foreach ($usuariosComRegras as $u) {
                                    if (!empty($u['group_name']) && !in_array($u['group_name'], $groupNames)) {
                                        $groupNames[] = $u['group_name'];
                                    }
                                }
                                foreach ($groupNames as $gn): ?>
                                <option value="<?= eAttr(mb_strtolower($gn)) ?>"><?= e($gn) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-sm btn-outline-secondary w-100" onclick="clearUserFilter()"><i class="fas fa-times me-1"></i>Limpar</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Visão consolidada -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom p-3">
                    <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-eye me-2"></i>Visão Consolidada — Regra Ativa por Usuário</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Usuário</th>
                                <th>Grupo</th>
                                <th>Regra Individual</th>
                                <th>Regra do Grupo</th>
                                <th class="text-center">Regra Ativa</th>
                            </tr>
                        </thead>
                        <tbody id="usersConsolidadoBody">
                            <?php foreach ($usuariosComRegras as $u):
                                $regraAtiva = $u['user_forma_nome'] ? 'usuario' : ($u['group_forma_nome'] ? 'grupo' : 'padrao');
                            ?>
                            <tr class="user-consolidated-row"
                                data-name="<?= eAttr(mb_strtolower($u['name'])) ?>"
                                data-regra="<?= $regraAtiva ?>"
                                data-group="<?= eAttr(mb_strtolower($u['group_name'] ?? '')) ?>">
                                <td class="fw-semibold"><?= e($u['name']) ?></td>
                                <td>
                                    <?php if ($u['group_name']): ?>
                                        <span class="badge bg-light text-dark border"><?= e($u['group_name']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">Sem grupo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($u['user_forma_nome']): ?>
                                        <span class="badge bg-primary"><?= e($u['user_forma_nome']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($u['group_forma_nome']): ?>
                                        <span class="badge bg-secondary"><?= e($u['group_forma_nome']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($u['user_forma_nome']): ?>
                                        <span class="badge bg-success"><i class="fas fa-user me-1"></i>Usuário</span>
                                    <?php elseif ($u['group_forma_nome']): ?>
                                        <span class="badge bg-info"><i class="fas fa-users me-1"></i>Grupo</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark"><i class="fas fa-cog me-1"></i>Padrão</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Vínculos individuais -->
            <div class="d-flex align-items-center mb-2">
                <div class="icon-circle icon-circle-sm icon-circle-carrot me-2">
                    <i class="fas fa-link text-carrot" style="font-size:.7rem;"></i>
                </div>
                <h6 class="mb-0" style="font-size:.9rem;">Vínculos Individuais</h6>
            </div>

            <?php if (empty($usuarioFormas)): ?>
            <div class="text-center text-muted py-4">
                <i class="fas fa-link fa-2x d-block mb-2 opacity-50 text-carrot"></i>
                <small>Nenhum vínculo individual. Todos usam a regra de grupo ou padrão.</small>
            </div>
            <?php else: ?>
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Usuário</th>
                                <th>Forma de Comissão</th>
                                <th>Tipo</th>
                                <th class="text-end">Valor</th>
                                <th class="text-center">Status</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarioFormas as $uf): ?>
                            <tr>
                                <td><span class="badge bg-light text-dark border"><i class="fas fa-user me-1"></i><?= e($uf['user_name']) ?></span></td>
                                <td class="fw-semibold"><?= e($uf['forma_nome']) ?></td>
                                <td><span class="badge bg-light text-dark"><?= e($uf['tipo_calculo']) ?></span></td>
                                <td class="text-end fw-semibold">
                                    <?php if ($uf['tipo_calculo'] === 'percentual'): ?>
                                        <?= number_format($uf['valor'], 2, ',', '.') ?>%
                                    <?php else: ?>
                                        R$ <?= number_format($uf['valor'], 2, ',', '.') ?>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?= $uf['ativo'] ? '<span class="badge bg-success">Ativo</span>' : '<span class="badge bg-secondary">Inativo</span>' ?>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-danger" onclick="unlinkUsuario(<?= $uf['id'] ?>)" title="Remover">
                                        <i class="fas fa-unlink"></i>
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

<!-- Modal: Vincular Usuário -->
<div class="modal fade" id="linkUsuarioModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="linkUsuarioForm">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-link me-2"></i>Vincular Usuário a Forma</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Usuário <span class="text-danger">*</span></label>
                        <select name="user_id" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($users as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= e($u['name']) ?> (<?= e($u['email']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Forma de Comissão <span class="text-danger">*</span></label>
                        <select name="forma_comissao_id" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($formas as $f): ?>
                            <option value="<?= $f['id'] ?>"><?= e($f['nome']) ?> (<?= $f['tipo_calculo'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-link me-1"></i>Vincular</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

function openLinkUsuarioModal() { new bootstrap.Modal(document.getElementById('linkUsuarioModal')).show(); }

document.getElementById('linkUsuarioForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.set('csrf_token', csrfToken);
    fetch('?page=commissions&action=linkUsuario', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) { Swal.fire({icon:'success', title:'Sucesso', text: res.message, timer:1500, showConfirmButton:false}).then(() => location.reload()); }
            else { Swal.fire({icon:'error', title:'Erro', text: res.message}); }
        });
});

function unlinkUsuario(id) {
    Swal.fire({ title: 'Remover vínculo?', text: 'O usuário voltará a usar a regra de grupo ou padrão.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#e74c3c', confirmButtonText: 'Sim, remover', cancelButtonText: 'Cancelar' })
    .then(result => {
        if (result.isConfirmed) {
            const fd = new FormData(); fd.set('id', id); fd.set('csrf_token', csrfToken);
            fetch('?page=commissions&action=unlinkUsuario', { method: 'POST', body: fd })
                .then(r => r.json()).then(res => { if (res.success) location.reload(); else Swal.fire({icon:'error', title:'Erro', text: res.message}); });
        }
    });
}

// ── Filtros dinâmicos (auto-apply) ──
function filterUsers() {
    const search = document.getElementById('filtro_user_search').value.toLowerCase();
    const regra  = document.getElementById('filtro_user_regra').value;
    const grupo  = document.getElementById('filtro_user_grupo').value;
    let count = 0;
    document.querySelectorAll('.user-consolidated-row').forEach(row => {
        const matchSearch = !search || row.dataset.name.includes(search);
        const matchRegra  = !regra  || row.dataset.regra === regra;
        const matchGrupo  = !grupo  || row.dataset.group === grupo;
        const show = matchSearch && matchRegra && matchGrupo;
        row.style.display = show ? '' : 'none';
        if (show) count++;
    });
    let noResult = document.getElementById('usersNoResult');
    if (!noResult) { noResult = document.createElement('tr'); noResult.id = 'usersNoResult'; noResult.innerHTML = '<td colspan="5" class="text-center text-muted py-3"><i class="fas fa-search me-1"></i>Nenhum resultado.</td>'; document.getElementById('usersConsolidadoBody')?.appendChild(noResult); }
    noResult.style.display = count === 0 ? '' : 'none';
}
function clearUserFilter() { document.getElementById('filtro_user_search').value = ''; document.getElementById('filtro_user_regra').value = ''; document.getElementById('filtro_user_grupo').value = ''; filterUsers(); }

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('filtro_user_search')?.addEventListener('input', filterUsers);
    document.getElementById('filtro_user_regra')?.addEventListener('change', filterUsers);
    document.getElementById('filtro_user_grupo')?.addEventListener('change', filterUsers);
});
</script>
