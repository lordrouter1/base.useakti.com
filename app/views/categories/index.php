<?php
    $editCatSectorIds = isset($editCategorySectors) ? array_column($editCategorySectors, 'sector_id') : [];
    $editSubSectorIds = isset($editSubcategorySectors) ? array_column($editSubcategorySectors, 'sector_id') : [];
?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-primary mb-0"><i class="fas fa-folder-open me-2"></i>Categorias e Subcategorias</h2>
    </div>

    <!-- Nav Tabs -->
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item">
            <button class="nav-link <?= (!isset($_GET['tab']) || $_GET['tab'] === 'categories') ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-categories" type="button">
                <i class="fas fa-folder me-1"></i>Categorias
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link <?= (isset($_GET['tab']) && $_GET['tab'] === 'subcategories') ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-subcategories" type="button">
                <i class="fas fa-sitemap me-1"></i>Subcategorias
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <!-- ═══════ ABA CATEGORIAS ═══════ -->
        <div class="tab-pane fade <?= (!isset($_GET['tab']) || $_GET['tab'] === 'categories') ? 'show active' : '' ?>" id="tab-categories">
            <div class="row">
                <!-- Form -->
                <div class="col-md-5 mb-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-primary p-3">
                            <h6 class="mb-0 text-white ">
                                <?php if(isset($editCategory)): ?>
                                    <i class="fas fa-edit me-2"></i>Editar Categoria
                                <?php else: ?>
                                    <i class="fas fa-plus me-2"></i>Nova Categoria
                                <?php endif; ?>
                            </h6>
                        </div>
                        <div class="card-body p-3">
                            <form method="POST" action="?page=categories&action=<?= isset($editCategory) ? 'update' : 'store' ?>">
                                <?= csrf_field() ?>
                                <?php if(isset($editCategory)): ?>
                                    <input type="hidden" name="id" value="<?= $editCategory['id'] ?>">
                                <?php endif; ?>
                                <div class="mb-3">
                                    <label class="form-label fw-bold small">Nome da Categoria</label>
                                    <input type="text" class="form-control" name="name" required placeholder="Ex: Impressão Digital" value="<?= isset($editCategory) ? eAttr($editCategory['name']) : '' ?>">
                                </div>

                                <!-- Setores de Produção -->
                                <?php if (!empty($allSectors)): ?>
                                <div class="mb-3">
                                    <label class="form-label fw-bold small"><i class="fas fa-industry me-1 text-success"></i>Setores de Produção</label>
                                    <p class="text-muted" style="font-size: 0.75rem; margin-bottom: 0.5rem;">Selecione e arraste para ordenar os setores padrão desta categoria.</p>
                                    
                                    <!-- Setores selecionados (ordenáveis) -->
                                    <div id="cat-sectors-selected" class="sectors-sortable-list mb-2" style="min-height: 36px; border: 1px dashed #dee2e6; border-radius: 0.375rem; padding: 4px;">
                                        <?php foreach ($editCatSectorIds as $sid): 
                                            $sector = null;
                                            foreach ($allSectors as $s) { if ($s['id'] == $sid) { $sector = $s; break; } }
                                            if (!$sector) continue;
                                        ?>
                                        <div class="sector-item badge d-inline-flex align-items-center me-1 mb-1 px-2 py-1" data-id="<?= $sector['id'] ?>" style="background-color: <?= $sector['color'] ?>; cursor: grab; font-size: 0.8rem;">
                                            <i class="<?= $sector['icon'] ?> me-1"></i>
                                            <?= e($sector['name']) ?>
                                            <button type="button" class="btn-close btn-close-white ms-1 sector-remove" style="font-size: 0.5rem;" data-id="<?= $sector['id'] ?>"></button>
                                            <input type="hidden" name="sector_ids[]" value="<?= $sector['id'] ?>">
                                        </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <!-- Setores disponíveis para adicionar -->
                                    <div class="d-flex flex-wrap gap-1" id="cat-sectors-available">
                                        <?php foreach ($allSectors as $sector): 
                                            $isSelected = in_array($sector['id'], $editCatSectorIds);
                                        ?>
                                        <button type="button" class="btn btn-sm sector-add-btn <?= $isSelected ? 'd-none' : '' ?>" 
                                                data-id="<?= $sector['id'] ?>" data-name="<?= eAttr($sector['name']) ?>"
                                                data-icon="<?= $sector['icon'] ?>" data-color="<?= $sector['color'] ?>"
                                                style="border: 1px solid <?= $sector['color'] ?>; color: <?= $sector['color'] ?>; font-size: 0.75rem; padding: 2px 8px;">
                                            <i class="fas fa-plus me-1" style="font-size: 0.6rem;"></i>
                                            <i class="<?= $sector['icon'] ?> me-1"></i><?= e($sector['name']) ?>
                                        </button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Grades / Variações Padrão da Categoria -->
                                <?php
                                    $entityType = 'category';
                                    $entityGrades = $editCategoryGrades ?? [];
                                    $entityCombinations = $editCategoryCombinations ?? [];
                                    // $gradeTypes is already available from controller
                                    require 'app/views/categories/_grades_partial.php';
                                ?>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i><?= isset($editCategory) ? 'Salvar' : 'Criar Categoria' ?>
                                    </button>
                                    <?php if(isset($editCategory)): ?>
                                        <?php if(!empty($editCategoryGrades) || !empty($editCategorySectors)): ?>
                                        <button type="button" class="btn btn-outline-info btn-sm btn-export-to-products" 
                                                data-type="category" data-id="<?= $editCategory['id'] ?>" data-name="<?= eAttr($editCategory['name']) ?>">
                                            <i class="fas fa-share-alt me-1"></i>Exportar Grades/Setores para Produtos
                                        </button>
                                        <?php endif; ?>
                                        <a href="?page=categories" class="btn btn-outline-secondary btn-sm">Cancelar</a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Lista -->
                <div class="col-md-7">
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="py-3 ps-4">Categoria</th>
                                            <th class="py-3">Setores</th>
                                            <th class="py-3 text-center" style="width:80px;">Subs</th>
                                            <th class="py-3 text-center" style="width:80px;">Prod.</th>
                                            <th class="py-3 text-end pe-4" style="width:130px;">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(empty($categories)): ?>
                                            <tr><td colspan="5" class="text-center py-5 text-muted">Nenhuma categoria cadastrada.</td></tr>
                                        <?php else: ?>
                                            <?php foreach($categories as $cat): ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <i class="fas fa-folder text-warning me-2"></i>
                                                    <strong><?= e($cat['name']) ?></strong>
                                                    <?php if(!empty($categoryGradesMap[$cat['id']])): ?>
                                                        <span class="badge bg-info ms-1" style="font-size:0.6rem;" title="Possui grades padrão">
                                                            <i class="fas fa-th-large me-1"></i>Grades
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                        $catSectorsData = isset($categorySectorsMap[$cat['id']]) ? $categorySectorsMap[$cat['id']] : [];
                                                    ?>
                                                    <?php if(!empty($catSectorsData)): ?>
                                                        <?php foreach($catSectorsData as $cs): ?>
                                                            <span class="badge me-1" style="background-color: <?= $cs['color'] ?>; font-size: 0.65rem;">
                                                                <i class="<?= $cs['icon'] ?> me-1"></i><?= e($cs['sector_name']) ?>
                                                            </span>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <span class="text-muted" style="font-size: 0.75rem;">—</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-info"><?= $cat['sub_count'] ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-secondary"><?= $cat['product_count'] ?></span>
                                                </td>
                                                <td class="text-end pe-4">
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <?php if(!empty($categoryGradesMap[$cat['id']]) || !empty($catSectorsData)): ?>
                                                        <button class="btn btn-outline-info btn-export-to-products" 
                                                                data-type="category" data-id="<?= $cat['id'] ?>" data-name="<?= eAttr($cat['name']) ?>"
                                                                title="Exportar grades/setores para produtos">
                                                            <i class="fas fa-share-alt"></i>
                                                        </button>
                                                        <?php endif; ?>
                                                        <a href="?page=categories&action=edit&id=<?= $cat['id'] ?>" class="btn btn-outline-primary" title="Editar">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button class="btn btn-outline-danger btn-delete-cat" data-id="<?= $cat['id'] ?>" data-name="<?= eAttr($cat['name']) ?>" data-products="<?= $cat['product_count'] ?>" title="Excluir">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
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

        <!-- ═══════ ABA SUBCATEGORIAS ═══════ -->
        <div class="tab-pane fade <?= (isset($_GET['tab']) && $_GET['tab'] === 'subcategories') ? 'show active' : '' ?>" id="tab-subcategories">
            <div class="row">
                <!-- Form -->
                <div class="col-md-5 mb-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-success p-3">
                            <h6 class="mb-0 text-white ">
                                <?php if(isset($editSubcategory)): ?>
                                    <i class="fas fa-edit me-2"></i>Editar Subcategoria
                                <?php else: ?>
                                    <i class="fas fa-plus me-2"></i>Nova Subcategoria
                                <?php endif; ?>
                            </h6>
                        </div>
                        <div class="card-body p-3">
                            <form method="POST" action="?page=categories&action=<?= isset($editSubcategory) ? 'updateSub' : 'storeSub' ?>">
                                <?= csrf_field() ?>
                                <?php if(isset($editSubcategory)): ?>
                                    <input type="hidden" name="id" value="<?= $editSubcategory['id'] ?>">
                                <?php endif; ?>
                                <div class="mb-3">
                                    <label class="form-label fw-bold small">Categoria</label>
                                    <select class="form-select" name="category_id" required>
                                        <option value="">Selecione...</option>
                                        <?php foreach($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" <?= (isset($editSubcategory) && $editSubcategory['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                            <?= e($cat['name']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold small">Nome da Subcategoria</label>
                                    <input type="text" class="form-control" name="name" required placeholder="Ex: Banner Lona" value="<?= isset($editSubcategory) ? eAttr($editSubcategory['name']) : '' ?>">
                                </div>

                                <!-- Setores de Produção -->
                                <?php if (!empty($allSectors)): ?>
                                <div class="mb-3">
                                    <label class="form-label fw-bold small"><i class="fas fa-industry me-1 text-success"></i>Setores de Produção</label>
                                    <p class="text-muted" style="font-size: 0.75rem; margin-bottom: 0.5rem;">Selecione e arraste para ordenar os setores padrão desta subcategoria.</p>
                                    
                                    <!-- Setores selecionados (ordenáveis) -->
                                    <div id="sub-sectors-selected" class="sectors-sortable-list mb-2" style="min-height: 36px; border: 1px dashed #dee2e6; border-radius: 0.375rem; padding: 4px;">
                                        <?php foreach ($editSubSectorIds as $sid): 
                                            $sector = null;
                                            foreach ($allSectors as $s) { if ($s['id'] == $sid) { $sector = $s; break; } }
                                            if (!$sector) continue;
                                        ?>
                                        <div class="sector-item badge d-inline-flex align-items-center me-1 mb-1 px-2 py-1" data-id="<?= $sector['id'] ?>" style="background-color: <?= $sector['color'] ?>; cursor: grab; font-size: 0.8rem;">
                                            <i class="<?= $sector['icon'] ?> me-1"></i>
                                            <?= e($sector['name']) ?>
                                            <button type="button" class="btn-close btn-close-white ms-1 sector-remove" style="font-size: 0.5rem;" data-id="<?= $sector['id'] ?>"></button>
                                            <input type="hidden" name="sector_ids[]" value="<?= $sector['id'] ?>">
                                        </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <!-- Setores disponíveis para adicionar -->
                                    <div class="d-flex flex-wrap gap-1" id="sub-sectors-available">
                                        <?php foreach ($allSectors as $sector): 
                                            $isSelected = in_array($sector['id'], $editSubSectorIds);
                                        ?>
                                        <button type="button" class="btn btn-sm sector-add-btn <?= $isSelected ? 'd-none' : '' ?>"
                                                data-id="<?= $sector['id'] ?>" data-name="<?= eAttr($sector['name']) ?>"
                                                data-icon="<?= $sector['icon'] ?>" data-color="<?= $sector['color'] ?>"
                                                style="border: 1px solid <?= $sector['color'] ?>; color: <?= $sector['color'] ?>; font-size: 0.75rem; padding: 2px 8px;">
                                            <i class="fas fa-plus me-1" style="font-size: 0.6rem;"></i>
                                            <i class="<?= $sector['icon'] ?> me-1"></i><?= e($sector['name']) ?>
                                        </button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Grades / Variações Padrão da Subcategoria -->
                                <?php
                                    $entityType = 'subcategory';
                                    $entityGrades = $editSubcategoryGrades ?? [];
                                    $entityCombinations = $editSubcategoryCombinations ?? [];
                                    // $gradeTypes is already available from controller
                                    require 'app/views/categories/_grades_partial.php';
                                ?>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-save me-1"></i><?= isset($editSubcategory) ? 'Salvar' : 'Criar Subcategoria' ?>
                                    </button>
                                    <?php if(isset($editSubcategory)): ?>
                                        <?php if(!empty($editSubcategoryGrades) || !empty($editSubcategorySectors)): ?>
                                        <button type="button" class="btn btn-outline-info btn-sm btn-export-to-products" 
                                                data-type="subcategory" data-id="<?= $editSubcategory['id'] ?>" data-name="<?= eAttr($editSubcategory['name']) ?>">
                                            <i class="fas fa-share-alt me-1"></i>Exportar Grades/Setores para Produtos
                                        </button>
                                        <?php endif; ?>
                                        <a href="?page=categories&tab=subcategories" class="btn btn-outline-secondary btn-sm">Cancelar</a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Lista -->
                <div class="col-md-7">
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="py-3 ps-4">Subcategoria</th>
                                            <th class="py-3">Categoria</th>
                                            <th class="py-3">Setores</th>
                                            <th class="py-3 text-end pe-4" style="width:130px;">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(empty($subcategories)): ?>
                                            <tr><td colspan="4" class="text-center py-5 text-muted">Nenhuma subcategoria cadastrada.</td></tr>
                                        <?php else: ?>
                                            <?php foreach($subcategories as $sub): ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <i class="fas fa-sitemap text-success me-2"></i>
                                                    <strong><?= e($sub['name']) ?></strong>
                                                    <?php if(!empty($subcategoryGradesMap[$sub['id']])): ?>
                                                        <span class="badge bg-info ms-1" style="font-size:0.6rem;" title="Possui grades padrão">
                                                            <i class="fas fa-th-large me-1"></i>Grades
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-warning text-dark"><i class="fas fa-folder me-1"></i><?= e($sub['category_name']) ?></span>
                                                </td>
                                                <td>
                                                    <?php 
                                                        $subSectorsData = isset($subcategorySectorsMap[$sub['id']]) ? $subcategorySectorsMap[$sub['id']] : [];
                                                    ?>
                                                    <?php if(!empty($subSectorsData)): ?>
                                                        <?php foreach($subSectorsData as $ss): ?>
                                                            <span class="badge me-1" style="background-color: <?= $ss['color'] ?>; font-size: 0.65rem;">
                                                                <i class="<?= $ss['icon'] ?> me-1"></i><?= e($ss['sector_name']) ?>
                                                            </span>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <span class="text-muted" style="font-size: 0.75rem;">—</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end pe-4">
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <?php if(!empty($subcategoryGradesMap[$sub['id']]) || !empty($subSectorsData)): ?>
                                                        <button class="btn btn-outline-info btn-export-to-products" 
                                                                data-type="subcategory" data-id="<?= $sub['id'] ?>" data-name="<?= eAttr($sub['name']) ?>"
                                                                title="Exportar grades/setores para produtos">
                                                            <i class="fas fa-share-alt"></i>
                                                        </button>
                                                        <?php endif; ?>
                                                        <a href="?page=categories&action=editSub&id=<?= $sub['id'] ?>&tab=subcategories" class="btn btn-outline-primary" title="Editar">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button class="btn btn-outline-danger btn-delete-sub" data-id="<?= $sub['id'] ?>" data-name="<?= eAttr($sub['name']) ?>" title="Excluir">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
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
    </div>
</div>

<!-- SortableJS CDN -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<!-- ═══════ MODAL: Exportar Grades/Setores para Produtos ═══════ -->
<div class="modal fade" id="modalExportToProducts" tabindex="-1" aria-labelledby="modalExportLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="modalExportLabel">
                    <i class="fas fa-share-alt me-2"></i>Exportar para Produtos
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="export-loading" class="text-center py-4">
                    <div class="spinner-border text-info" role="status"></div>
                    <p class="mt-2 text-muted">Carregando produtos...</p>
                </div>
                <div id="export-content" style="display:none;">
                    <!-- Info da origem -->
                    <div class="alert alert-info py-2 px-3 mb-3" style="font-size:0.85rem;">
                        <i class="fas fa-info-circle me-2"></i>
                        <span id="export-source-info"></span>
                    </div>

                    <!-- O que exportar -->
                    <div class="card mb-3">
                        <div class="card-body py-2 px-3">
                            <label class="form-label fw-bold small mb-2">O que exportar?</label>
                            <div class="d-flex gap-3">
                                <div class="form-check" id="export-grades-check-wrapper">
                                    <input class="form-check-input" type="checkbox" id="chkExportGrades" checked>
                                    <label class="form-check-label small" for="chkExportGrades">
                                        <i class="fas fa-th-large text-info me-1"></i>Grades / Variações
                                    </label>
                                </div>
                                <div class="form-check" id="export-sectors-check-wrapper">
                                    <input class="form-check-input" type="checkbox" id="chkExportSectors" checked>
                                    <label class="form-check-label small" for="chkExportSectors">
                                        <i class="fas fa-industry text-success me-1"></i>Setores de Produção
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Warning -->
                    <div class="alert alert-warning py-2 px-3 mb-3" style="font-size:0.8rem;">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        <strong>Atenção:</strong> Esta ação irá <strong>substituir</strong> as grades e/ou setores existentes nos produtos selecionados. A ação não pode ser desfeita.
                    </div>

                    <!-- Seleção de produtos -->
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label fw-bold small mb-0">Selecione os produtos:</label>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnSelectAll">
                                <i class="fas fa-check-double me-1"></i>Todos
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnSelectNone">
                                <i class="fas fa-times me-1"></i>Nenhum
                            </button>
                        </div>
                    </div>

                    <!-- Lista de produtos (sem grades/setores = highlight) -->
                    <div id="export-products-list" class="border rounded" style="max-height: 350px; overflow-y: auto;">
                        <!-- Populated via JS -->
                    </div>
                    <div id="export-no-products" class="text-center py-4 text-muted" style="display:none;">
                        <i class="fas fa-box-open fa-2x mb-2 d-block"></i>
                        Nenhum produto encontrado nesta categoria/subcategoria.
                    </div>

                    <div class="text-muted small mt-2">
                        <i class="fas fa-info-circle me-1"></i>
                        <span id="export-selected-count">0</span> produto(s) selecionado(s)
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-info text-white" id="btnConfirmExport" disabled>
                    <i class="fas fa-share-alt me-1"></i>Exportar para Selecionados
                </button>
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

    // ── Inicializar drag-and-drop para setores ──
    function initSectorSortable(containerId, availableId) {
        const selectedContainer = document.getElementById(containerId);
        const availableContainer = document.getElementById(availableId);
        
        if (!selectedContainer || !availableContainer) return;

        // Inicializar SortableJS
        new Sortable(selectedContainer, {
            animation: 150,
            ghostClass: 'bg-opacity-50',
            handle: '.sector-item',
            draggable: '.sector-item',
            onEnd: function() {
                // Reordenar inputs hidden
                updateHiddenInputs(selectedContainer);
            }
        });

        // Botões de adicionar setor
        availableContainer.querySelectorAll('.sector-add-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                const name = this.dataset.name;
                const icon = this.dataset.icon;
                const color = this.dataset.color;

                // Criar item no container selecionado
                const item = document.createElement('div');
                item.className = 'sector-item badge d-inline-flex align-items-center me-1 mb-1 px-2 py-1';
                item.dataset.id = id;
                item.style.backgroundColor = color;
                item.style.cursor = 'grab';
                item.style.fontSize = '0.8rem';
                item.innerHTML = `
                    <i class="${icon} me-1"></i>
                    ${name}
                    <button type="button" class="btn-close btn-close-white ms-1 sector-remove" style="font-size: 0.5rem;" data-id="${id}"></button>
                    <input type="hidden" name="sector_ids[]" value="${id}">
                `;
                selectedContainer.appendChild(item);

                // Esconder o botão de adicionar
                this.classList.add('d-none');

                // Registrar evento de remover no novo item
                item.querySelector('.sector-remove').addEventListener('click', function() {
                    removeSector(this.dataset.id, selectedContainer, availableContainer);
                });
            });
        });

        // Registrar eventos de remover nos itens existentes
        selectedContainer.querySelectorAll('.sector-remove').forEach(btn => {
            btn.addEventListener('click', function() {
                removeSector(this.dataset.id, selectedContainer, availableContainer);
            });
        });
    }

    function removeSector(sectorId, selectedContainer, availableContainer) {
        // Remover do container selecionado
        const item = selectedContainer.querySelector(`.sector-item[data-id="${sectorId}"]`);
        if (item) item.remove();

        // Mostrar no container disponível
        const addBtn = availableContainer.querySelector(`.sector-add-btn[data-id="${sectorId}"]`);
        if (addBtn) addBtn.classList.remove('d-none');
    }

    function updateHiddenInputs(container) {
        // Os hidden inputs já estão dentro de cada sector-item, a ordem do DOM já reflete a ordem
    }

    // Inicializar para categorias e subcategorias
    initSectorSortable('cat-sectors-selected', 'cat-sectors-available');
    initSectorSortable('sub-sectors-selected', 'sub-sectors-available');

    // ── Confirmações de exclusão ──
    document.querySelectorAll('.btn-delete-cat').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id, name = this.dataset.name, prods = this.dataset.products;
            let msg = `Deseja excluir a categoria <strong>${name}</strong>?`;
            if (parseInt(prods) > 0) msg += `<br><span class="text-danger">Atenção: ${prods} produto(s) vinculado(s) perderão a categoria.</span>`;
            Swal.fire({
                title: 'Excluir categoria?', html: msg, icon: 'warning', showCancelButton: true,
                confirmButtonColor: '#c0392b', confirmButtonText: '<i class="fas fa-trash me-1"></i> Excluir', cancelButtonText: 'Cancelar'
            }).then(r => { if (r.isConfirmed) window.location = '?page=categories&action=delete&id=' + id; });
        });
    });

    document.querySelectorAll('.btn-delete-sub').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id, name = this.dataset.name;
            Swal.fire({
                title: 'Excluir subcategoria?', html: `Deseja excluir <strong>${name}</strong>?`, icon: 'warning', showCancelButton: true,
                confirmButtonColor: '#c0392b', confirmButtonText: '<i class="fas fa-trash me-1"></i> Excluir', cancelButtonText: 'Cancelar'
            }).then(r => { if (r.isConfirmed) window.location = '?page=categories&action=deleteSub&id=' + id; });
        });
    });

    // ═══════ EXPORT TO PRODUCTS LOGIC ═══════
    let exportModal = null;
    let exportState = { type: '', sourceId: '', sourceName: '' };

    document.querySelectorAll('.btn-export-to-products').forEach(btn => {
        btn.addEventListener('click', function() {
            exportState.type = this.dataset.type;
            exportState.sourceId = this.dataset.id;
            exportState.sourceName = this.dataset.name;

            const typeName = exportState.type === 'category' ? 'Categoria' : 'Subcategoria';
            document.getElementById('modalExportLabel').innerHTML = 
                `<i class="fas fa-share-alt me-2"></i>Exportar — ${typeName}: ${exportState.sourceName}`;

            document.getElementById('export-loading').style.display = 'block';
            document.getElementById('export-content').style.display = 'none';
            document.getElementById('btnConfirmExport').disabled = true;

            if (!exportModal) {
                exportModal = new bootstrap.Modal(document.getElementById('modalExportToProducts'));
            }
            exportModal.show();

            // Fetch products
            fetch(`?page=categories&action=getProductsForExport&type=${exportState.type}&id=${exportState.sourceId}`)
                .then(r => r.json())
                .then(data => {
                    document.getElementById('export-loading').style.display = 'none';
                    document.getElementById('export-content').style.display = 'block';

                    if (!data.success) {
                        document.getElementById('export-no-products').style.display = 'block';
                        document.getElementById('export-products-list').style.display = 'none';
                        return;
                    }

                    // Setup checkboxes based on what's available
                    const gradesCW = document.getElementById('export-grades-check-wrapper');
                    const sectorsCW = document.getElementById('export-sectors-check-wrapper');
                    const chkGrades = document.getElementById('chkExportGrades');
                    const chkSectors = document.getElementById('chkExportSectors');

                    if (data.has_grades) {
                        gradesCW.style.display = 'block';
                        chkGrades.checked = true;
                        chkGrades.disabled = false;
                    } else {
                        gradesCW.style.display = 'none';
                        chkGrades.checked = false;
                    }
                    if (data.has_sectors) {
                        sectorsCW.style.display = 'block';
                        chkSectors.checked = true;
                        chkSectors.disabled = false;
                    } else {
                        sectorsCW.style.display = 'none';
                        chkSectors.checked = false;
                    }

                    // Source info
                    const infoItems = [];
                    if (data.has_grades) infoItems.push('<i class="fas fa-th-large text-info me-1"></i>Grades');
                    if (data.has_sectors) infoItems.push('<i class="fas fa-industry text-success me-1"></i>Setores');
                    document.getElementById('export-source-info').innerHTML = 
                        `Exportando ${infoItems.join(' e ')} da <strong>${typeName.toLowerCase()} "${exportState.sourceName}"</strong> para os produtos selecionados.`;

                    // Render product list
                    const listContainer = document.getElementById('export-products-list');
                    const noProducts = document.getElementById('export-no-products');

                    if (data.products.length === 0) {
                        listContainer.style.display = 'none';
                        noProducts.style.display = 'block';
                        return;
                    }

                    listContainer.style.display = 'block';
                    noProducts.style.display = 'none';

                    let html = '';
                    data.products.forEach(p => {
                        const hasG = parseInt(p.grade_count) > 0;
                        const hasS = parseInt(p.sector_count) > 0;
                        const highlightClass = (!hasG && !hasS) ? 'bg-warning ' : '';
                        const imgHtml = p.main_image_path 
                            ? `<img src="${p.main_image_path}" class="rounded" style="width:32px; height:32px; object-fit:cover;">` 
                            : `<div class="rounded bg-light d-flex align-items-center justify-content-center" style="width:32px; height:32px;"><i class="fas fa-box text-muted" style="font-size:0.8rem;"></i></div>`;

                        html += `
                            <div class="d-flex align-items-center px-3 py-2 border-bottom export-product-row ${highlightClass}" data-id="${p.id}">
                                <div class="form-check me-3">
                                    <input class="form-check-input export-product-check" type="checkbox" value="${p.id}" checked>
                                </div>
                                <div class="me-2">${imgHtml}</div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold small">${p.name}</div>
                                    <div class="text-muted" style="font-size:0.7rem;">
                                        ${p.sku ? '<i class="fas fa-barcode me-1"></i>' + p.sku + ' · ' : ''}
                                        ${hasG ? '<span class="text-info"><i class="fas fa-th-large me-1"></i>' + p.grade_count + ' grade(s)</span>' : '<span class="text-warning"><i class="fas fa-th-large me-1"></i>Sem grades</span>'}
                                         · 
                                        ${hasS ? '<span class="text-success"><i class="fas fa-industry me-1"></i>' + p.sector_count + ' setor(es)</span>' : '<span class="text-warning"><i class="fas fa-industry me-1"></i>Sem setores</span>'}
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    listContainer.innerHTML = html;

                    // Update count and bind events
                    updateExportCount();
                    listContainer.querySelectorAll('.export-product-check').forEach(chk => {
                        chk.addEventListener('change', updateExportCount);
                    });
                })
                .catch(err => {
                    console.error(err);
                    document.getElementById('export-loading').style.display = 'none';
                    document.getElementById('export-content').style.display = 'block';
                    document.getElementById('export-no-products').style.display = 'block';
                    document.getElementById('export-products-list').style.display = 'none';
                });
        });
    });

    // Select All / None
    document.getElementById('btnSelectAll')?.addEventListener('click', function() {
        document.querySelectorAll('.export-product-check').forEach(chk => { chk.checked = true; });
        updateExportCount();
    });
    document.getElementById('btnSelectNone')?.addEventListener('click', function() {
        document.querySelectorAll('.export-product-check').forEach(chk => { chk.checked = false; });
        updateExportCount();
    });

    function updateExportCount() {
        const checked = document.querySelectorAll('.export-product-check:checked');
        document.getElementById('export-selected-count').textContent = checked.length;
        const hasGrades = document.getElementById('chkExportGrades').checked;
        const hasSectors = document.getElementById('chkExportSectors').checked;
        document.getElementById('btnConfirmExport').disabled = checked.length === 0 || (!hasGrades && !hasSectors);
    }

    // Also update when checkboxes change
    document.getElementById('chkExportGrades')?.addEventListener('change', updateExportCount);
    document.getElementById('chkExportSectors')?.addEventListener('change', updateExportCount);

    // Confirm export
    document.getElementById('btnConfirmExport')?.addEventListener('click', function() {
        const checked = document.querySelectorAll('.export-product-check:checked');
        const productIds = Array.from(checked).map(chk => chk.value);
        const exportGrades = document.getElementById('chkExportGrades').checked;
        const exportSectors = document.getElementById('chkExportSectors').checked;

        if (productIds.length === 0) return;

        const itemsText = [];
        if (exportGrades) itemsText.push('grades');
        if (exportSectors) itemsText.push('setores');

        Swal.fire({
            title: 'Confirmar exportação?',
            html: `Você está prestes a exportar <strong>${itemsText.join(' e ')}</strong> para <strong>${productIds.length}</strong> produto(s).<br><br><span class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i>As configurações existentes nesses produtos serão substituídas.</span>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#17a2b8',
            confirmButtonText: '<i class="fas fa-share-alt me-1"></i> Confirmar Exportação',
            cancelButtonText: 'Cancelar',
            showLoaderOnConfirm: true,
            allowOutsideClick: () => !Swal.isLoading(),
            preConfirm: () => {
                const formData = new FormData();
                formData.append('type', exportState.type);
                formData.append('source_id', exportState.sourceId);
                if (exportGrades) formData.append('export_grades', '1');
                if (exportSectors) formData.append('export_sectors', '1');
                productIds.forEach(id => formData.append('product_ids[]', id));

                return fetch('?page=categories&action=exportToProducts', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    if (!data.success) throw new Error(data.message || 'Erro desconhecido');
                    return data;
                })
                .catch(err => {
                    Swal.showValidationMessage('Erro: ' + err.message);
                });
            }
        }).then(result => {
            if (result.isConfirmed && result.value) {
                if (exportModal) exportModal.hide();
                Swal.fire({
                    icon: 'success',
                    title: 'Exportação concluída!',
                    html: result.value.message,
                    timer: 3000,
                    showConfirmButton: true,
                    confirmButtonText: 'OK'
                });
            }
        });
    });
});
</script>
