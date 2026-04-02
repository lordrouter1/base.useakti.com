<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-primary mb-0"><i class="fas fa-industry me-2"></i>Setores de Produção</h2>
    </div>

    <?php if (!empty($limitReached)): ?>
    <div class="alert alert-warning border-warning d-flex align-items-center mb-3" role="alert">
        <i class="fas fa-exclamation-triangle fs-5 me-3 text-warning"></i>
        <div>
            <strong>Limite do plano atingido!</strong> Você possui <strong><?= $limitInfo['current'] ?></strong> de <strong><?= $limitInfo['max'] ?></strong> setores de produção permitidos.
            <span class="text-muted">Para cadastrar mais setores, entre em contato com o suporte para fazer um upgrade do seu plano.</span>
        </div>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- Form -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0 sticky-top" style="top: 90px;">
                <div class="card-header text-white p-3 card-header-carrot">
                    <h6 class="mb-0">
                        <?php if(isset($editSector)): ?>
                            <i class="fas fa-edit me-2"></i>Editar Setor
                        <?php else: ?>
                            <i class="fas fa-plus me-2"></i>Novo Setor
                        <?php endif; ?>
                    </h6>
                </div>
                <div class="card-body p-3">
                    <?php if (!empty($limitReached) && !isset($editSector)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-lock fa-2x mb-2 d-block text-warning"></i>
                            <p class="mb-0">Limite de setores atingido.<br><small>Não é possível criar novos setores.</small></p>
                        </div>
                    <?php else: ?>
                    <form method="POST" action="?page=sectors&action=<?= isset($editSector) ? 'update' : 'store' ?>">
                        <?= csrf_field() ?>
                        <?php if(isset($editSector)): ?>
                            <input type="hidden" name="id" value="<?= $editSector['id'] ?>">
                        <?php endif; ?>
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Nome do Setor <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" required placeholder="Ex: Impressão, Corte, Acabamento" value="<?= isset($editSector) ? eAttr($editSector['name']) : '' ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Descrição</label>
                            <textarea class="form-control" name="description" rows="2" placeholder="Breve descrição do setor..."><?= isset($editSector) ? e($editSector['description']) : '' ?></textarea>
                        </div>
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label fw-bold small">Ícone</label>
                                <select class="form-select" name="icon">
                                    <?php 
                                    $icons = [
                                        'fas fa-cogs' => '⚙ Engrenagens',
                                        'fas fa-print' => '🖨 Impressão',
                                        'fas fa-cut' => '✂ Corte',
                                        'fas fa-paint-brush' => '🎨 Acabamento',
                                        'fas fa-layer-group' => '📑 Camadas',
                                        'fas fa-drafting-compass' => '📐 Design',
                                        'fas fa-palette' => '🎨 Cores',
                                        'fas fa-ruler' => '📏 Medição',
                                        'fas fa-box' => '📦 Embalagem',
                                        'fas fa-tools' => '🔧 Ferramentas',
                                        'fas fa-magic' => '✨ Arte-final',
                                        'fas fa-fire' => '🔥 Solda/Calor',
                                        'fas fa-tshirt' => '👕 Sublimação',
                                        'fas fa-vector-square' => '🔲 Recorte',
                                        'fas fa-industry' => '🏭 Produção',
                                    ];
                                    $currentIcon = isset($editSector) ? $editSector['icon'] : 'fas fa-cogs';
                                    foreach($icons as $val => $lbl): ?>
                                    <option value="<?= $val ?>" <?= ($currentIcon == $val) ? 'selected' : '' ?>><?= $lbl ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold small">Cor</label>
                                <input type="color" class="form-control form-control-color w-100" name="color" value="<?= isset($editSector) ? $editSector['color'] : '#6c757d' ?>">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label fw-bold small">Ordem</label>
                                <input type="number" class="form-control" name="sort_order" min="0" value="<?= isset($editSector) ? $editSector['sort_order'] : 0 ?>">
                            </div>
                            <?php if(isset($editSector)): ?>
                            <div class="col-6">
                                <label class="form-label fw-bold small">Status</label>
                                <select class="form-select" name="is_active">
                                    <option value="1" <?= $editSector['is_active'] ? 'selected' : '' ?>>Ativo</option>
                                    <option value="0" <?= !$editSector['is_active'] ? 'selected' : '' ?>>Inativo</option>
                                </select>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-carrot text-white">
                                <i class="fas fa-save me-1"></i><?= isset($editSector) ? 'Salvar Alterações' : 'Criar Setor' ?>
                            </button>
                            <?php if(isset($editSector)): ?>
                                <a href="?page=sectors" class="btn btn-outline-secondary btn-sm">Cancelar</a>
                            <?php endif; ?>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Lista -->
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <caption class="visually-hidden">Lista de setores de produção</caption>
                            <thead class="bg-light">
                                <tr>
                                    <th class="py-3 ps-4" style="width:50px;">Ordem</th>
                                    <th class="py-3">Setor</th>
                                    <th class="py-3 text-center" style="width:80px;">Status</th>
                                    <th class="py-3 text-end pe-4" style="width:120px;">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($sectors)): ?>
                                    <tr><td colspan="4" class="text-center py-5 text-muted">Nenhum setor cadastrado.</td></tr>
                                <?php else: ?>
                                    <?php foreach($sectors as $sector): ?>
                                    <tr class="<?= !$sector['is_active'] ? 'opacity-50' : '' ?>">
                                        <td class="ps-4 text-muted"><?= $sector['sort_order'] ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="rounded-circle d-flex align-items-center justify-content-center me-3" 
                                                     style="width:36px;height:36px;background:<?= $sector['color'] ?>20;">
                                                    <i class="<?= $sector['icon'] ?>" style="color:<?= $sector['color'] ?>;"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-bold"><?= e($sector['name']) ?></div>
                                                    <?php if($sector['description']): ?>
                                                    <div class="small text-muted"><?= e($sector['description']) ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <?php if($sector['is_active']): ?>
                                                <span class="badge bg-success">Ativo</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inativo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <a href="?page=sectors&action=edit&id=<?= (int)$sector['id'] ?>" class="btn btn-sm btn-outline-primary me-1" title="Editar" aria-label="Editar setor">
                                                <i class="fas fa-edit" aria-hidden="true"></i>
                                            </a>
                                            <button class="btn btn-sm btn-outline-danger btn-delete-sector" data-id="<?= (int)$sector['id'] ?>" data-name="<?= eAttr($sector['name']) ?>" title="Excluir" aria-label="Excluir setor">
                                                <i class="fas fa-trash" aria-hidden="true"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if(isset($_GET['status'])): ?>
    if (window.history.replaceState) { const url = new URL(window.location); url.searchParams.delete('status'); window.history.replaceState({}, '', url); }
    <?php endif; ?>
    <?php if(isset($_GET['status']) && $_GET['status'] == 'success'): ?>
    Swal.fire({ icon: 'success', title: 'Sucesso!', text: 'Operação realizada!', timer: 2000, showConfirmButton: false });
    <?php endif; ?>
    <?php if(isset($_GET['status']) && $_GET['status'] == 'limit_sectors'): ?>
    Swal.fire({ icon: 'warning', title: 'Limite atingido!', text: 'Você atingiu o limite de setores de produção do seu plano. Entre em contato com o suporte para fazer um upgrade.', confirmButtonColor: '#3498db' });
    <?php endif; ?>

    document.querySelectorAll('.btn-delete-sector').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id, name = this.dataset.name;
            Swal.fire({
                title: 'Excluir setor?', html: `Deseja excluir o setor <strong>${name}</strong>?`, icon: 'warning', showCancelButton: true,
                confirmButtonColor: '#c0392b', confirmButtonText: '<i class="fas fa-trash me-1"></i> Excluir', cancelButtonText: 'Cancelar'
            }).then(r => { if (r.isConfirmed) window.location = '?page=sectors&action=delete&id=' + id; });
        });
    });
});
</script>
