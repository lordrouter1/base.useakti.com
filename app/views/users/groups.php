<?php
// Carrega dados dinâmicos para as permissões
$menuPages = require 'app/config/menu.php';

// Achata o menu (extrai itens de dentro de submenus para lista plana)
$flatPages = [];
foreach ($menuPages as $key => $info) {
    if (isset($info['children'])) {
        foreach ($info['children'] as $childKey => $childInfo) {
            $flatPages[$childKey] = $childInfo;
        }
    } else {
        $flatPages[$key] = $info;
    }
}

$pages = [];
foreach ($flatPages as $key => $info) {
    if (!empty($info['permission']) && empty($info['permission_alias'])) {
        $pages[$key] = $info;
    }
}

$pipelineStages = \Akti\Models\Pipeline::$stages;

$dbSec = (new \Database())->getConnection();
$sectorModel = new \Akti\Models\ProductionSector($dbSec);
$sectors = $sectorModel->readAll(true);

$currentPermissions = isset($editGroup) ? $editGroup['permissions'] : [];
?>

<div class="row">
    <!-- ═══════ FORMULÁRIO ═══════ -->
    <div class="col-lg-5 col-md-6 mb-4">
        <div class="card shadow-sm border-0 sticky-top" style="top: 90px; z-index: 1;">
            <div class="card-header bg-primary p-3">
                <h5 class="mb-0 text-white">
                    <?php if(isset($editGroup)): ?>
                        <i class="fas fa-edit me-2"></i>Editar Grupo: <?= e($editGroup['name']) ?>
                    <?php else: ?>
                        <i class="fas fa-layer-group me-2"></i>Novo Grupo de Permissões
                    <?php endif; ?>
                </h5>
            </div>
            <div class="card-body p-4" style="max-height: calc(100vh - 160px); overflow-y: auto;">
                <form action="?page=users&action=<?= isset($editGroup) ? 'updateGroup' : 'createGroup' ?>" method="POST">
                    <?= csrf_field() ?>
                    <?php if(isset($editGroup)): ?>
                        <input type="hidden" name="id" value="<?= $editGroup['id'] ?>">
                    <?php endif; ?>
                    
                    <!-- Nome e Descrição -->
                    <div class="mb-3">
                        <label class="form-label fw-bold"><i class="fas fa-tag me-1 text-primary"></i>Nome do Grupo</label>
                        <input type="text" class="form-control" name="name" required placeholder="Ex: Financeiro, Produção, Vendas" value="<?= isset($editGroup) ? e($editGroup['name']) : '' ?>">
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold"><i class="fas fa-align-left me-1 text-primary"></i>Descrição</label>
                        <textarea class="form-control" name="description" rows="2" placeholder="Breve descrição do grupo..."><?= isset($editGroup) ? e($editGroup['description']) : '' ?></textarea>
                    </div>

                    <!-- ── SEÇÃO 1: Módulos do Sistema ── -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-bold mb-0"><i class="fas fa-th-large me-1 text-primary"></i>Módulos do Sistema</label>
                            <div>
                                <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2" onclick="toggleAll('perm_page_', true)" style="font-size:0.7rem;">Todos</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" onclick="toggleAll('perm_page_', false)" style="font-size:0.7rem;">Nenhum</button>
                            </div>
                        </div>
                        <div class="border rounded p-3 bg-light">
                            <div class="row g-2">
                            <?php foreach($pages as $key => $info): 
                                $checked = in_array($key, $currentPermissions) ? 'checked' : '';
                            ?>
                                <div class="col-6">
                                    <div class="form-check d-flex align-items-center">
                                        <input class="form-check-input me-2" type="checkbox" name="permissions[]" value="<?= $key ?>" id="perm_page_<?= $key ?>" <?= $checked ?>>
                                        <label class="form-check-label d-flex align-items-center small" for="perm_page_<?= $key ?>">
                                            <i class="<?= $info['icon'] ?> me-1 text-primary" style="width:16px;text-align:center;font-size:0.8rem;"></i>
                                            <?= $info['permission_label'] ?? $info['label'] ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="form-text"><i class="fas fa-info-circle me-1"></i>Define quais páginas o grupo pode acessar no menu.</div>
                    </div>

                    <!-- ── SEÇÃO 2: Etapas do Pipeline (Kanban) ── -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-bold mb-0"><i class="fas fa-columns me-1 text-warning"></i>Etapas do Pipeline</label>
                            <div>
                                <button type="button" class="btn btn-outline-warning btn-sm py-0 px-2" onclick="toggleAll('perm_stage_', true)" style="font-size:0.7rem;">Todos</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" onclick="toggleAll('perm_stage_', false)" style="font-size:0.7rem;">Nenhum</button>
                            </div>
                        </div>
                        <div class="border rounded p-3 bg-light">
                            <div class="row g-2">
                            <?php foreach($pipelineStages as $stageKey => $stageInfo): 
                                $stagePermKey = 'stage_' . $stageKey;
                                $checked = in_array($stagePermKey, $currentPermissions) ? 'checked' : '';
                            ?>
                                <div class="col-6">
                                    <div class="form-check d-flex align-items-center">
                                        <input class="form-check-input me-2" type="checkbox" name="permissions[]" value="<?= $stagePermKey ?>" id="perm_stage_<?= $stageKey ?>" <?= $checked ?>>
                                        <label class="form-check-label d-flex align-items-center small" for="perm_stage_<?= $stageKey ?>">
                                            <i class="<?= $stageInfo['icon'] ?> me-1" style="color:<?= $stageInfo['color'] ?>;width:16px;text-align:center;font-size:0.8rem;"></i>
                                            <?= $stageInfo['label'] ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="form-text"><i class="fas fa-info-circle me-1"></i>O grupo só verá no Kanban as etapas selecionadas aqui.</div>
                    </div>

                    <!-- ── SEÇÃO 3: Setores de Produção ── -->
                    <?php if(!empty($sectors)): ?>
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-bold mb-0"><i class="fas fa-industry me-1 text-success"></i>Setores de Produção</label>
                            <div>
                                <button type="button" class="btn btn-outline-success btn-sm py-0 px-2" onclick="toggleAll('perm_sector_', true)" style="font-size:0.7rem;">Todos</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" onclick="toggleAll('perm_sector_', false)" style="font-size:0.7rem;">Nenhum</button>
                            </div>
                        </div>
                        <div class="border rounded p-3 bg-light">
                            <div class="row g-2">
                            <?php foreach($sectors as $sector): 
                                $sectorPermKey = 'sector_' . $sector['id'];
                                $checked = in_array($sectorPermKey, $currentPermissions) ? 'checked' : '';
                            ?>
                                <div class="col-6">
                                    <div class="form-check d-flex align-items-center">
                                        <input class="form-check-input me-2" type="checkbox" name="permissions[]" value="<?= $sectorPermKey ?>" id="perm_sector_<?= $sector['id'] ?>" <?= $checked ?>>
                                        <label class="form-check-label d-flex align-items-center small" for="perm_sector_<?= $sector['id'] ?>">
                                            <i class="<?= $sector['icon'] ?> me-1" style="color:<?= $sector['color'] ?>;width:16px;text-align:center;font-size:0.8rem;"></i>
                                            <?= e($sector['name']) ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="form-text"><i class="fas fa-info-circle me-1"></i>O grupo só terá acesso aos setores de produção marcados.</div>
                    </div>
                    <?php endif; ?>

                    <!-- Botões -->
                    <div class="d-grid gap-2 mt-3">
                        <button type="submit" class="btn btn-primary fw-bold">
                            <?php if(isset($editGroup)): ?>
                                <i class="fas fa-save me-2"></i>Salvar Alterações
                            <?php else: ?>
                                <i class="fas fa-plus me-2"></i>Criar Grupo
                            <?php endif; ?>
                        </button>
                        <?php if(isset($editGroup)): ?>
                            <a href="?page=users&action=groups" class="btn btn-outline-secondary">Cancelar</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- ═══════ LISTA DE GRUPOS ═══════ -->
    <div class="col-lg-7 col-md-6">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="text-primary mb-0"><i class="fas fa-list-ul me-2"></i>Grupos Existentes</h4>
            <a href="?page=users" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-2"></i>Voltar</a>
        </div>

        <?php if(empty($groups)): ?>
            <div class="card shadow-sm border-0">
                <div class="card-body text-center py-5 text-muted">Nenhum grupo cadastrado ainda.</div>
            </div>
        <?php else: ?>
            <?php foreach($groups as $group2): ?>
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="fw-bold mb-1"><i class="fas fa-layer-group text-primary me-2"></i><?= e($group2['name']) ?></h6>
                            <small class="text-muted"><?= e($group2['description']) ?></small>
                        </div>
                        <div class="d-flex gap-1">
                            <a href="?page=users&action=groups&manage_id=<?= $group2['id'] ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-danger btn-delete-group" data-id="<?= $group2['id'] ?>" data-name="<?= e($group2['name']) ?>" title="Excluir">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>

                    <?php
                    // Separar permissões por tipo
                    $permPages = []; $permStages = []; $permSectors = [];
                    if (!empty($group2['permissions'])) {
                        foreach ($group2['permissions'] as $p) {
                            if (str_starts_with($p, 'stage_')) {
                                $permStages[] = $p;
                            } elseif (str_starts_with($p, 'sector_')) {
                                $permSectors[] = $p;
                            } else {
                                $permPages[] = $p;
                            }
                        }
                    }
                    ?>

                    <!-- Badges: Módulos -->
                    <?php if(!empty($permPages)): ?>
                    <div class="mb-2">
                        <small class="text-muted fw-bold d-block mb-1"><i class="fas fa-th-large me-1"></i>Módulos:</small>
                        <div class="d-flex flex-wrap gap-1">
                            <?php foreach($permPages as $perm):
                                $icon = $flatPages[$perm]['icon'] ?? 'fas fa-circle';
                                $label = $flatPages[$perm]['permission_label'] ?? $flatPages[$perm]['label'] ?? $perm;
                            ?>
                            <span class="badge bg-primary  text-primary border border-primary border-opacity-25 bg-opacity-10">
                                <i class="<?= $icon ?> me-1" style="font-size:0.65rem;"></i><?= $label ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Badges: Pipeline -->
                    <?php if(!empty($permStages)): ?>
                    <div class="mb-2">
                        <small class="text-muted fw-bold d-block mb-1"><i class="fas fa-columns me-1"></i>Pipeline:</small>
                        <div class="d-flex flex-wrap gap-1">
                            <?php foreach($permStages as $perm):
                                $stKey = str_replace('stage_', '', $perm);
                                $stInfo = $pipelineStages[$stKey] ?? null;
                                if (!$stInfo) continue;
                            ?>
                            <span class="badge text-white" style="background:<?= $stInfo['color'] ?>;">
                                <i class="<?= $stInfo['icon'] ?> me-1" style="font-size:0.65rem;"></i><?= $stInfo['label'] ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Badges: Setores -->
                    <?php if(!empty($permSectors)): ?>
                    <div class="mb-1">
                        <small class="text-muted fw-bold d-block mb-1"><i class="fas fa-industry me-1"></i>Setores:</small>
                        <div class="d-flex flex-wrap gap-1">
                            <?php foreach($permSectors as $perm):
                                $secId = str_replace('sector_', '', $perm);
                                $secInfo = null;
                                foreach ($sectors as $s) { if ($s['id'] == $secId) { $secInfo = $s; break; } }
                                if (!$secInfo) continue;
                            ?>
                            <span class="badge bg-success  text-success border border-success border-opacity-25">
                                <i class="<?= $secInfo['icon'] ?> me-1" style="font-size:0.65rem;"></i><?= e($secInfo['name']) ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if(empty($group2['permissions'])): ?>
                        <small class="text-muted fst-italic">Sem permissões configuradas</small>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleAll(prefix, checked) {
    document.querySelectorAll('[id^="' + prefix + '"]').forEach(cb => cb.checked = checked);
}

document.addEventListener('DOMContentLoaded', function() {
    <?php if(isset($_GET['status']) && $_GET['status'] == 'success'): ?>
    // Limpar o parâmetro status da URL para não disparar novamente
    if (window.history.replaceState) {
        const url = new URL(window.location);
        url.searchParams.delete('status');
        window.history.replaceState({}, '', url);
    }
    Swal.fire({ icon: 'success', title: 'Sucesso!', text: 'Grupo salvo com sucesso!', timer: 2000, showConfirmButton: false });
    <?php endif; ?>

    document.querySelectorAll('.btn-delete-group').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id, name = this.dataset.name;
            Swal.fire({
                title: 'Excluir grupo?',
                html: `Deseja realmente excluir o grupo <strong>${name}</strong>?<br>Isso pode afetar usuários vinculados.`,
                icon: 'warning', showCancelButton: true,
                confirmButtonColor: '#c0392b', cancelButtonColor: '#95a5a6',
                confirmButtonText: '<i class="fas fa-trash me-1"></i> Sim, excluir', cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '?page=users&action=deleteGroup';
                    const input = document.createElement('input');
                    input.type = 'hidden'; input.name = 'id'; input.value = id;
                    form.appendChild(input);
                    const csrf = document.createElement('input');
                    csrf.type = 'hidden'; csrf.name = 'csrf_token'; csrf.value = document.querySelector('meta[name="csrf-token"]').content;
                    form.appendChild(csrf);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });
    });
});
</script>
