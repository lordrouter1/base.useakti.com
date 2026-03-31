<?php
/**
 * Comissões — Regras por Grupo
 * Vinculação de formas de comissão a grupos de usuários.
 * Padrão visual: Financeiro.
 * Variáveis: $grupoFormas, $aux (groups, formas)
 */
$groups = $aux['groups'] ?? [];
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
                    <div class="icon-circle icon-circle-grape me-2">
                        <i class="fas fa-users text-grape" style="font-size:.85rem;"></i>
                    </div>
                    <div>
                        <h5 class="mb-0" style="font-size:1rem;">Regras por Grupo</h5>
                        <p class="text-muted mb-0" style="font-size:.72rem;">Prioridade 2 — aplicadas quando o usuário não possui regra individual.</p>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="?page=users&action=groups" class="btn btn-sm btn-outline-secondary" title="Gerenciar grupos na Gestão de Usuários">
                        <i class="fas fa-users-cog me-1"></i>Gerenciar Grupos
                    </a>
                    <button class="btn btn-sm btn-success" onclick="openLinkGrupoModal()"><i class="fas fa-plus me-1"></i>Vincular</button>
                </div>
            </div>

            <!-- Filtro dinâmico -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body py-2">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label small mb-1">Grupo</label>
                            <select id="filtro_grupo_group" class="form-select form-select-sm">
                                <option value="">Todos</option>
                                <?php foreach ($groups as $g): ?>
                                <option value="<?= eAttr(mb_strtolower($g['name'])) ?>"><?= e($g['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small mb-1">Buscar</label>
                            <input type="text" id="filtro_grupo_search" class="form-control form-control-sm" placeholder="Nome da forma...">
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-sm btn-outline-secondary w-100" onclick="document.getElementById('filtro_grupo_group').value='';document.getElementById('filtro_grupo_search').value='';filterGrupos()"><i class="fas fa-times me-1"></i>Limpar</button>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (empty($grupoFormas)): ?>
            <div class="text-center text-muted py-5">
                <i class="fas fa-link fa-3x d-block mb-3 opacity-50 text-grape"></i>
                <p class="mb-2">Nenhum vínculo grupo ↔ forma cadastrado.</p>
                <button class="btn btn-success btn-sm" onclick="openLinkGrupoModal()"><i class="fas fa-plus me-1"></i>Vincular Primeiro Grupo</button>
            </div>
            <?php else: ?>
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Grupo</th>
                                <th>Forma de Comissão</th>
                                <th>Tipo</th>
                                <th class="text-end">Valor</th>
                                <th class="text-center">Status</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="gruposBody">
                            <?php foreach ($grupoFormas as $gf): ?>
                            <tr class="grupo-row"
                                data-group="<?= eAttr(mb_strtolower($gf['group_name'])) ?>"
                                data-forma="<?= eAttr(mb_strtolower($gf['forma_nome'])) ?>">
                                <td><span class="badge bg-light text-dark border"><i class="fas fa-users me-1"></i><?= e($gf['group_name']) ?></span></td>
                                <td class="fw-semibold"><?= e($gf['forma_nome']) ?></td>
                                <td><span class="badge bg-light text-dark"><?= e($gf['tipo_calculo']) ?></span></td>
                                <td class="text-end">
                                    <?php if ($gf['tipo_calculo'] === 'percentual'): ?>
                                        <?= number_format($gf['valor'], 2, ',', '.') ?>%
                                    <?php elseif ($gf['tipo_calculo'] === 'valor_fixo'): ?>
                                        R$ <?= number_format($gf['valor'], 2, ',', '.') ?>
                                    <?php else: ?>
                                        <span class="text-muted">Faixa</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?= $gf['ativo'] ? '<span class="badge bg-success">Ativo</span>' : '<span class="badge bg-secondary">Inativo</span>' ?>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-danger" onclick="unlinkGrupo(<?= $gf['id'] ?>)" title="Remover vínculo">
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

<!-- Modal: Vincular Grupo -->
<div class="modal fade" id="linkGrupoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="linkGrupoForm">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-link me-2"></i>Vincular Grupo a Forma</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Grupo <span class="text-danger">*</span></label>
                        <select name="group_id" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($groups as $g): ?>
                            <option value="<?= $g['id'] ?>"><?= e($g['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted mt-1 d-block">
                            Não encontrou o grupo? <a href="?page=users&action=groups">Cadastre um novo grupo aqui</a>.
                        </small>
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

function openLinkGrupoModal() {
    new bootstrap.Modal(document.getElementById('linkGrupoModal')).show();
}

document.getElementById('linkGrupoForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.set('csrf_token', csrfToken);

    fetch('?page=commissions&action=linkGrupo', { method: 'POST', body: formData })
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

function unlinkGrupo(id) {
    Swal.fire({
        title: 'Remover vínculo?',
        text: 'O grupo perderá esta regra de comissão.',
        icon: 'warning', showCancelButton: true,
        confirmButtonColor: '#e74c3c', confirmButtonText: 'Sim, remover', cancelButtonText: 'Cancelar'
    }).then(result => {
        if (result.isConfirmed) {
            const fd = new FormData();
            fd.set('id', id);
            fd.set('csrf_token', csrfToken);
            fetch('?page=commissions&action=unlinkGrupo', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.success) location.reload();
                    else Swal.fire({icon:'error', title:'Erro', text: res.message});
                });
        }
    });
}

// ── Filtros dinâmicos (auto-apply) ──
function filterGrupos() {
    const group  = document.getElementById('filtro_grupo_group').value;
    const search = document.getElementById('filtro_grupo_search').value.toLowerCase();
    let count = 0;

    document.querySelectorAll('.grupo-row').forEach(row => {
        const matchGroup  = !group  || row.dataset.group === group;
        const matchSearch = !search || row.dataset.forma.includes(search) || row.dataset.group.includes(search);
        const show = matchGroup && matchSearch;
        row.style.display = show ? '' : 'none';
        if (show) count++;
    });

    let noResult = document.getElementById('gruposNoResult');
    if (!noResult) {
        noResult = document.createElement('tr');
        noResult.id = 'gruposNoResult';
        noResult.innerHTML = '<td colspan="6" class="text-center text-muted py-3"><i class="fas fa-search me-1"></i>Nenhum resultado.</td>';
        document.getElementById('gruposBody')?.appendChild(noResult);
    }
    noResult.style.display = count === 0 ? '' : 'none';
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('filtro_grupo_group')?.addEventListener('change', filterGrupos);
    document.getElementById('filtro_grupo_search')?.addEventListener('input', filterGrupos);
});
</script>
