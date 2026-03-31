<div class="container py-4">
    <div class="d-flex align-items-center mb-4">
        <a href="?page=products" class="btn btn-outline-secondary btn-sm me-3"><i class="fas fa-arrow-left"></i></a>
        <h2 class="mb-0 text-primary"><i class="fas fa-edit me-2"></i>Editar Produto</h2>
    </div>
    
    <form id="productForm" method="post" action="?page=products&action=update" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$product['id'] ?>">

        <!-- ════════════════════════════════════════════════════
             SEÇÃO 1 — CAMPOS OBRIGATÓRIOS (sempre visíveis)
             ════════════════════════════════════════════════════ -->
        <div class="card border-primary border-2 mb-4 shadow-sm">
            <div class="card-header bg-primary py-3">
                <h5 class="mb-0 text-white"><i class="fas fa-star me-2"></i>Informações Essenciais <small class="opacity-75">— Campos obrigatórios para salvar</small></h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <!-- Nome do Produto -->
                    <div class="col-md-8">
                        <label for="name" class="form-label fw-bold">Nome do Produto <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-lg" id="name" name="name" required placeholder="Ex: Cartão de Visita, Banner, Adesivo..." value="<?= eAttr($product['name']) ?>">
                        <div class="form-text"><i class="fas fa-info-circle me-1"></i>Nome principal que identifica o produto.</div>
                    </div>
                    <div class="col-md-4">
                        <label for="sku" class="form-label fw-bold">SKU</label>
                        <input type="text" class="form-control form-control-lg" id="sku" name="sku" placeholder="Ex: CART-VIS-001" value="<?= eAttr($product['sku'] ?? '') ?>">
                        <div class="form-text"><i class="fas fa-barcode me-1"></i>Código único do produto (opcional).</div>
                    </div>

                    <!-- Preço e Estoque -->
                    <div class="col-md-4">
                        <label for="price" class="form-label fw-bold">Preço Padrão (R$) <span class="text-danger">*</span></label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text">R$</span>
                            <input type="number" step="0.01" class="form-control" id="price" name="price" required placeholder="0.00" value="<?= $product['price'] ?>">
                        </div>
                        <div class="form-text">Usado quando não há preço específico na tabela.</div>
                    </div>
                    <div class="col-md-4">
                        <label for="cost_price" class="form-label fw-bold">Preço de Custo (R$)</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text">R$</span>
                            <input type="number" step="0.01" class="form-control" id="cost_price" name="cost_price" placeholder="0.00" value="<?= $product['cost_price'] ?? '' ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Controle de Estoque</label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" id="use_stock_control" name="use_stock_control" value="1" <?= !empty($product['use_stock_control']) ? 'checked' : '' ?>>
                            <label class="form-check-label small text-muted" for="use_stock_control">
                                <i class="fas fa-boxes-stacked me-1"></i>Usar controle de estoque
                            </label>
                        </div>
                        <div class="form-text"><i class="fas fa-info-circle me-1"></i>Se ativado e houver estoque no armazém, o pedido pode pular a produção.</div>
                    </div>

                    <!-- Categoria e Subcategoria -->
                    <div class="col-md-6">
                        <label for="category_id" class="form-label fw-bold">Categoria</label>
                        <div class="input-group">
                            <select class="form-select" id="category_id" name="category_id">
                                <option value="">Selecione...</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= (int)$category['id'] ?>" <?= $product['category_id'] == $category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option>
                                <?php endforeach; ?>
                                <option value="new">+ Nova Categoria</option>
                            </select>
                            <button type="button" class="btn btn-outline-secondary" id="btnAddCategory" style="display: none;"><i class="fas fa-check"></i></button>
                        </div>
                        <input type="text" class="form-control mt-2" id="new_category_name" name="new_category_name" placeholder="Nome da nova categoria" style="display: none;">
                    </div>
                    <div class="col-md-6">
                        <label for="subcategory_id" class="form-label fw-bold">Subcategoria</label>
                         <div class="input-group">
                            <select class="form-select" id="subcategory_id" name="subcategory_id">
                                <option value="">Selecione...</option>
                                <?php foreach($subcategories as $sub): ?>
                                    <option value="<?= (int)$sub['id'] ?>" <?= $product['subcategory_id'] == $sub['id'] ? 'selected' : '' ?>><?= e($sub['name']) ?></option>
                                <?php endforeach; ?>
                                <option value="new">+ Nova Subcategoria</option>
                            </select>
                         </div>
                         <input type="text" class="form-control mt-2" id="new_subcategory_name" name="new_subcategory_name" placeholder="Nome da nova subcategoria" style="display: none;">
                    </div>

                    <!-- Descrição -->
                    <div class="col-12">
                        <label for="description" class="form-label fw-bold">Descrição</label>
                        <textarea class="form-control" id="description" name="description" rows="2" placeholder="Detalhes do produto, acabamentos, etc."><?= e($product['description']) ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- ════════════════════════════════════════════════════
             SEÇÃO 2 — IMAGENS (colapsável, aberto por padrão)
             ════════════════════════════════════════════════════ -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center section-toggle" 
                 data-bs-toggle="collapse" data-bs-target="#collapseImages" aria-expanded="true" role="button">
                <h5 class="mb-0 text-primary">
                    <i class="fas fa-camera me-2"></i>Galeria de Imagens
                    <?php if(!empty($images)): ?>
                    <span class="badge bg-secondary ms-2" style="font-size:0.7rem;"><?= count($images) ?> imagem(ns)</span>
                    <?php endif; ?>
                </h5>
                <i class="fas fa-chevron-up collapse-icon text-muted"></i>
            </div>
            <div class="collapse show" id="collapseImages">
                <div class="card-body p-4">
                    <!-- Imagens Existentes -->
                    <?php if(!empty($images)): ?>
                    <label class="form-label small fw-bold text-muted mb-2">Imagens Atuais</label>
                    <div class="d-flex flex-wrap gap-2 mb-3" id="existing-images">
                        <?php foreach($images as $img): ?>
                        <div class="position-relative border rounded p-1" id="img-cont-<?= $img['id'] ?>" style="width: 80px; height: 80px;">
                            <img src="<?= eAttr($img['image_path']) ?>" class="w-100 h-100 object-fit-cover rounded">
                            <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 p-0 d-flex align-items-center justify-content-center" 
                                    style="width: 20px; height: 20px; transform: translate(30%, -30%); border-radius: 50%;"
                                    onclick="deleteImage(<?= $img['id'] ?>)">
                                <i class="fas fa-times" style="font-size: 10px;"></i>
                            </button>
                            <div class="form-check position-absolute bottom-0 start-0 m-1 bg-white rounded-circle shadow-sm" style="padding: 2px;">
                                <input class="form-check-input m-0" type="radio" name="main_image_id" value="<?= $img['id'] ?>" <?= $img['is_main'] ? 'checked' : '' ?> title="Definir como principal">
                            </div>
                            <?php if($img['is_main']): ?>
                            <span class="badge bg-success position-absolute top-0 start-0 m-1" style="font-size: 8px;">Principal</span>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <hr>
                    <?php endif; ?>

                    <!-- Upload de Novas Imagens -->
                    <label class="form-label small fw-bold text-muted mb-2">Adicionar Novas Fotos</label>
                    <div class="row">
                        <div class="col-md-6">
                            <div id="product-img-dropbox" class="border rounded p-4 d-flex flex-column align-items-center justify-content-center bg-light" style="height: 150px; border-style: dashed !important; cursor: pointer; position: relative; overflow: hidden;">
                                <div id="dropbox-placeholder" class="text-secondary text-center">
                                    <i class="fas fa-cloud-upload-alt fa-3x mb-2"></i>
                                    <p class="mb-0 fw-bold">Arraste imagens ou clique para selecionar</p>
                                    <small class="text-muted">JPG, PNG ou GIF (máx. 5MB cada)</small>
                                </div>
                                <input type="file" name="product_photos[]" id="product_photos" accept="image/*" multiple class="position-absolute top-0 start-0 w-100 h-100 opacity-0" style="cursor: pointer;">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div id="image-gallery-preview" class="row g-2 overflow-auto" style="max-height: 150px;">
                                <div class="text-muted small text-center py-4">
                                    <i class="fas fa-image fa-2x mb-2 d-block text-secondary"></i>
                                    Novas imagens aparecerão aqui
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ════════════════════════════════════════════════════
             SEÇÃO 3 — PREÇOS POR TABELA (colapsável)
             ════════════════════════════════════════════════════ -->
        <?php if (!empty($priceTables)): ?>
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center section-toggle" 
                 data-bs-toggle="collapse" data-bs-target="#collapsePriceTables" aria-expanded="<?= !empty($productPrices) ? 'true' : 'false' ?>" role="button">
                <h5 class="mb-0 text-primary">
                    <i class="fas fa-tags me-2"></i>Preços por Tabela
                    <span class="badge bg-secondary ms-2" style="font-size:0.7rem;"><?= count($priceTables) ?> tabela(s)</span>
                </h5>
                <i class="fas fa-chevron-<?= !empty($productPrices) ? 'up' : 'down' ?> collapse-icon text-muted"></i>
            </div>
            <div class="collapse <?= !empty($productPrices) ? 'show' : '' ?>" id="collapsePriceTables">
                <div class="card-body p-4">
                    <p class="text-muted small mb-3">Defina preços específicos para cada tabela. Deixe em branco para usar o <strong>preço padrão</strong>.</p>
                    <div class="row g-3">
                        <?php foreach ($priceTables as $pt): ?>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">
                                <?= e($pt['name']) ?>
                                <?php if ($pt['is_default']): ?>
                                    <span class="badge bg-success" style="font-size:0.65rem;">Padrão</span>
                                <?php endif; ?>
                            </label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">R$</span>
                                <input type="number" step="0.01" class="form-control table-price-input" 
                                       name="table_prices[<?= $pt['id'] ?>]" 
                                       placeholder="<?= number_format($product['price'], 2, '.', '') ?>"
                                       value="<?= isset($productPrices[$pt['id']]) ? number_format($productPrices[$pt['id']], 2, '.', '') : '' ?>"
                                       data-table-id="<?= $pt['id'] ?>">
                            </div>
                            <?php if (isset($productPrices[$pt['id']])): ?>
                                <?php 
                                    $diff = $productPrices[$pt['id']] - $product['price'];
                                    $diffPercent = $product['price'] > 0 ? round(($diff / $product['price']) * 100, 1) : 0;
                                ?>
                                <small class="<?= $diff < 0 ? 'text-success' : ($diff > 0 ? 'text-danger' : 'text-muted') ?>">
                                    <?= $diff > 0 ? '+' : '' ?><?= $diffPercent ?>% em relação ao padrão
                                </small>
                            <?php else: ?>
                                <small class="text-muted">Usando preço padrão</small>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ════════════════════════════════════════════════════
             SEÇÃO 4 — ESPECIFICAÇÕES TÉCNICAS (colapsável)
             ════════════════════════════════════════════════════ -->
        <?php $hasTechData = !empty($product['format']) || !empty($product['material']); ?>
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center section-toggle" 
                 data-bs-toggle="collapse" data-bs-target="#collapseTechnical" aria-expanded="<?= $hasTechData ? 'true' : 'false' ?>" role="button">
                <h5 class="mb-0 text-primary"><i class="fas fa-cogs me-2"></i>Especificações Técnicas</h5>
                <i class="fas fa-chevron-<?= $hasTechData ? 'up' : 'down' ?> collapse-icon text-muted"></i>
            </div>
            <div class="collapse <?= $hasTechData ? 'show' : '' ?>" id="collapseTechnical">
                <div class="card-body p-4">
                    <div class="row g-3">
                         <div class="col-md-6">
                            <label for="format" class="form-label">Formato/Dimensões</label>
                            <input type="text" class="form-control" id="format" name="format" placeholder="Ex: A4, 9x5cm" value="<?= eAttr($product['format'] ?? '') ?>">
                         </div>
                         <div class="col-md-6">
                            <label for="material" class="form-label">Material/Papel</label>
                            <input type="text" class="form-control" id="material" name="material" placeholder="Ex: Couché 300g" value="<?= eAttr($product['material'] ?? '') ?>">
                         </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ════════════════════════════════════════════════════
             SEÇÃO 5 — SETORES DE PRODUÇÃO (colapsável)
             ════════════════════════════════════════════════════ -->
        <?php if (!empty($allSectors)): 
            $linkedSectorIds = array_column($productSectors, 'sector_id');
            $hasSectors = !empty($productSectors);
        ?>
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center section-toggle" 
                 data-bs-toggle="collapse" data-bs-target="#collapseSectors" aria-expanded="<?= $hasSectors ? 'true' : 'false' ?>" role="button">
                <h5 class="mb-0 text-success">
                    <i class="fas fa-industry me-2"></i>Setores de Produção
                    <?php if ($hasSectors): ?>
                    <span class="badge bg-success ms-2" style="font-size:0.7rem;"><?= count($productSectors) ?> setor(es)</span>
                    <?php endif; ?>
                </h5>
                <i class="fas fa-chevron-<?= $hasSectors ? 'up' : 'down' ?> collapse-icon text-muted"></i>
            </div>
            <div class="collapse <?= $hasSectors ? 'show' : '' ?>" id="collapseSectors">
                <div class="card-body p-4">
                    <!-- Alert para importar setores da categoria/subcategoria -->
                    <div id="inherit-sectors-section" class="mb-3" style="display:none;">
                        <div class="alert alert-success py-2 px-3 d-flex align-items-center justify-content-between" id="inherit-sectors-alert" style="font-size:0.85rem;">
                            <div>
                                <i class="fas fa-magic me-2"></i>
                                <span id="inherit-sectors-msg">A categoria/subcategoria possui setores de produção configurados.</span>
                            </div>
                            <button type="button" class="btn btn-sm btn-success text-white" id="btn-inherit-sectors">
                                <i class="fas fa-download me-1"></i>Importar Setores
                            </button>
                        </div>
                    </div>

                    <p class="text-muted small mb-3">Selecione os setores pelos quais este produto passa na produção. Arraste para reordenar.</p>
                    
                    <div id="prod-sectors-selected" class="sectors-sortable-list mb-2" style="min-height: 40px; border: 2px dashed #dee2e6; border-radius: 0.375rem; padding: 6px;">
                        <?php 
                        foreach ($productSectors as $ps): 
                            $sector = null;
                            foreach ($allSectors as $s) { if ($s['id'] == $ps['sector_id']) { $sector = $s; break; } }
                            if (!$sector) continue;
                        ?>
                        <div class="sector-item badge d-inline-flex align-items-center me-1 mb-1 px-2 py-2" data-id="<?= $sector['id'] ?>" style="background-color: <?= $sector['color'] ?>; cursor: grab; font-size: 0.85rem;">
                            <i class="fas fa-grip-vertical me-1" style="opacity:0.6; font-size:0.7rem;"></i>
                            <i class="<?= $sector['icon'] ?> me-1"></i>
                            <?= e($sector['name']) ?>
                            <button type="button" class="btn-close btn-close-white ms-2 sector-remove" style="font-size: 0.5rem;" data-id="<?= (int)$sector['id'] ?>"></button>
                            <input type="hidden" name="sector_ids[]" value="<?= (int)$sector['id'] ?>">
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($productSectors)): ?>
                        <span class="text-muted small sectors-placeholder" style="line-height: 28px; padding: 2px 6px;"><i class="fas fa-info-circle me-1"></i>Clique nos setores abaixo para adicionar e arraste para ordenar</span>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex flex-wrap gap-1 mt-2" id="prod-sectors-available">
                        <?php foreach ($allSectors as $sector): 
                            $isLinked = in_array($sector['id'], $linkedSectorIds);
                        ?>
                        <button type="button" class="btn btn-sm sector-add-btn <?= $isLinked ? 'd-none' : '' ?>" 
                                data-id="<?= (int)$sector['id'] ?>" data-name="<?= eAttr($sector['name']) ?>"
                                data-icon="<?= eAttr($sector['icon']) ?>" data-color="<?= eAttr($sector['color']) ?>"
                                style="border: 1px solid <?= eAttr($sector['color']) ?>; color: <?= eAttr($sector['color']) ?>; font-size: 0.8rem; padding: 3px 10px;">
                            <i class="fas fa-plus me-1" style="font-size: 0.65rem;"></i>
                            <i class="<?= eAttr($sector['icon']) ?> me-1"></i><?= e($sector['name']) ?>
                        </button>
                        <?php endforeach; ?>
                    </div>

                    <div class="form-text mt-2"><i class="fas fa-info-circle me-1"></i>Sem setores = usa o da subcategoria ou categoria.</div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ════════════════════════════════════════════════════
             SEÇÃO 6 — GRADES / VARIAÇÕES (colapsável)
             ════════════════════════════════════════════════════ -->
        <?php $hasGrades = !empty($productGrades); ?>
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center section-toggle" 
                 data-bs-toggle="collapse" data-bs-target="#collapseGrades" aria-expanded="<?= $hasGrades ? 'true' : 'false' ?>" role="button">
                <h5 class="mb-0 text-info">
                    <i class="fas fa-th-large me-2"></i>Grades / Variações
                    <?php if ($hasGrades): ?>
                    <span class="badge bg-info ms-2" style="font-size:0.7rem;"><?= count($productGrades) ?> grade(s)</span>
                    <?php endif; ?>
                </h5>
                <i class="fas fa-chevron-<?= $hasGrades ? 'up' : 'down' ?> collapse-icon text-muted"></i>
            </div>
            <div class="collapse <?= $hasGrades ? 'show' : '' ?>" id="collapseGrades">
                <div class="card-body p-4">
                    <?php include 'app/views/products/_grades_partial.php'; ?>
                </div>
            </div>
        </div>

        <!-- ════════════════════════════════════════════════════
             SEÇÃO 7 — INFORMAÇÕES FISCAIS (colapsável)
             ════════════════════════════════════════════════════ -->
        <?php if (\Akti\Core\ModuleBootloader::isModuleEnabled('nfe')): ?>
        <?php $hasFiscal = !empty($product['fiscal_ncm']); ?>
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center section-toggle" 
                 data-bs-toggle="collapse" data-bs-target="#collapseFiscal" aria-expanded="<?= $hasFiscal ? 'true' : 'false' ?>" role="button">
                <h5 class="mb-0 text-grape"><i class="fas fa-file-invoice me-2"></i>Informações Fiscais (NF-e)</h5>
                <i class="fas fa-chevron-<?= $hasFiscal ? 'up' : 'down' ?> collapse-icon text-muted"></i>
            </div>
            <div class="collapse <?= $hasFiscal ? 'show' : '' ?>" id="collapseFiscal">
                <div class="card-body p-4">
                    <?php include 'app/views/products/_fiscal_partial.php'; ?>
                </div>
            </div>
        </div>
        <?php else: ?>
        <!-- Módulo NF-e desabilitado — card informativo -->
        <div class="card mb-4 shadow-sm border-0 opacity-75">
            <div class="card-header bg-light py-3 d-flex justify-content-between align-items-center" 
                 role="button" onclick="<?= \Akti\Core\ModuleBootloader::getDisabledModuleJS('nfe') ?>">
                <h5 class="mb-0 text-muted"><i class="fas fa-file-invoice me-2"></i>Informações Fiscais (NF-e) <span class="badge bg-secondary ms-2" style="font-size:0.65rem;">Módulo Inativo</span></h5>
                <i class="fas fa-lock text-muted"></i>
            </div>
        </div>
        <?php endif; ?>

        <!-- ════════════════════════════════════════════════════
             BOTÕES DE AÇÃO
             ════════════════════════════════════════════════════ -->
        <div class="d-flex justify-content-between align-items-center py-3 sticky-bottom bg-body-tertiary rounded px-3 mb-3 shadow-sm">
            <a href="?page=products" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Cancelar</a>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-primary" id="btnExpandAll"><i class="fas fa-expand-alt me-1"></i>Expandir Tudo</button>
                <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save me-2"></i>Salvar Alterações</button>
            </div>
        </div>

    </form>
</div>

<!-- SortableJS CDN -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<!-- Styles moved to assets/css/modules/products.css -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ── Collapse chevron icon toggle ──
    document.querySelectorAll('.section-toggle[data-bs-toggle="collapse"]').forEach(trigger => {
        const targetId = trigger.getAttribute('data-bs-target');
        const collapseEl = document.querySelector(targetId);
        if (!collapseEl) return;
        collapseEl.addEventListener('shown.bs.collapse', () => {
            trigger.setAttribute('aria-expanded', 'true');
            const icon = trigger.querySelector('.collapse-icon');
            if (icon) { icon.classList.remove('fa-chevron-down'); icon.classList.add('fa-chevron-up'); }
        });
        collapseEl.addEventListener('hidden.bs.collapse', () => {
            trigger.setAttribute('aria-expanded', 'false');
            const icon = trigger.querySelector('.collapse-icon');
            if (icon) { icon.classList.remove('fa-chevron-up'); icon.classList.add('fa-chevron-down'); }
        });
    });

    // ── Expand All / Collapse All toggle ──
    const btnExpandAll = document.getElementById('btnExpandAll');
    let allExpanded = false;
    if (btnExpandAll) {
        btnExpandAll.addEventListener('click', function() {
            allExpanded = !allExpanded;
            document.querySelectorAll('.card .collapse').forEach(el => {
                const bsCollapse = bootstrap.Collapse.getOrCreateInstance(el, { toggle: false });
                allExpanded ? bsCollapse.show() : bsCollapse.hide();
            });
            this.innerHTML = allExpanded
                ? '<i class="fas fa-compress-alt me-1"></i>Recolher Tudo'
                : '<i class="fas fa-expand-alt me-1"></i>Expandir Tudo';
        });
    }

    // ── Inicializar drag-and-drop para setores de produção ──
    (function initProductSectors() {
        const selectedContainer = document.getElementById('prod-sectors-selected');
        const availableContainer = document.getElementById('prod-sectors-available');
        if (!selectedContainer || !availableContainer) return;

        new Sortable(selectedContainer, {
            animation: 150,
            ghostClass: 'bg-opacity-50',
            draggable: '.sector-item',
        });

        availableContainer.querySelectorAll('.sector-add-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                const name = this.dataset.name;
                const icon = this.dataset.icon;
                const color = this.dataset.color;

                const placeholder = selectedContainer.querySelector('.sectors-placeholder');
                if (placeholder) placeholder.remove();

                const item = document.createElement('div');
                item.className = 'sector-item badge d-inline-flex align-items-center me-1 mb-1 px-2 py-2';
                item.dataset.id = id;
                item.style.backgroundColor = color;
                item.style.cursor = 'grab';
                item.style.fontSize = '0.85rem';
                item.innerHTML = `
                    <i class="fas fa-grip-vertical me-1" style="opacity:0.6; font-size:0.7rem;"></i>
                    <i class="${icon} me-1"></i>
                    ${name}
                    <button type="button" class="btn-close btn-close-white ms-2 sector-remove" style="font-size: 0.5rem;" data-id="${id}"></button>
                    <input type="hidden" name="sector_ids[]" value="${id}">
                `;
                selectedContainer.appendChild(item);
                this.classList.add('d-none');

                item.querySelector('.sector-remove').addEventListener('click', function() {
                    removeSector(this.dataset.id);
                });
            });
        });

        selectedContainer.querySelectorAll('.sector-remove').forEach(btn => {
            btn.addEventListener('click', function() {
                removeSector(this.dataset.id);
            });
        });

        function removeSector(sectorId) {
            const item = selectedContainer.querySelector(`.sector-item[data-id="${sectorId}"]`);
            if (item) item.remove();
            const addBtn = availableContainer.querySelector(`.sector-add-btn[data-id="${sectorId}"]`);
            if (addBtn) addBtn.classList.remove('d-none');
            if (!selectedContainer.querySelector('.sector-item')) {
                selectedContainer.innerHTML = '<span class="text-muted small sectors-placeholder" style="line-height: 28px; padding: 2px 6px;"><i class="fas fa-info-circle me-1"></i>Clique nos setores abaixo para adicionar e arraste para ordenar</span>';
            }
        }
    })();

    // ── Category / Subcategory ──
    const categorySelect = document.getElementById('category_id');
    const newCategoryInput = document.getElementById('new_category_name');
    const subcategorySelect = document.getElementById('subcategory_id');
    const newSubcategoryInput = document.getElementById('new_subcategory_name');

    categorySelect.addEventListener('change', function() {
        const categoryId = this.value;
        if (categoryId === 'new') {
            newCategoryInput.style.display = 'block';
            newCategoryInput.required = true;
            subcategorySelect.innerHTML = '<option value="new">+ Nova Subcategoria</option>';
            subcategorySelect.value = 'new';
            subcategorySelect.dispatchEvent(new Event('change'));
        } else {
            newCategoryInput.style.display = 'none';
            newCategoryInput.required = false;
            newCategoryInput.value = '';
            if (categoryId) {
                fetchSubcategories(categoryId);
            } else {
                subcategorySelect.innerHTML = '<option value="">Selecione uma categoria primeiro</option>';
            }
        }
        // Check inherited sectors when category changes
        setTimeout(checkInheritedSectors, 500);
    });
    
    subcategorySelect.addEventListener('change', function() {
        if (this.value === 'new') {
            newSubcategoryInput.style.display = 'block';
            newSubcategoryInput.required = true;
        } else {
            newSubcategoryInput.style.display = 'none';
            newSubcategoryInput.required = false;
            newSubcategoryInput.value = '';
        }
        // Check inherited sectors when subcategory changes
        setTimeout(checkInheritedSectors, 300);
    });

    function fetchSubcategories(categoryId) {
        fetch(`?page=products&action=getSubcategories&category_id=${categoryId}`)
            .then(response => response.json())
            .then(data => {
                let options = '<option value="">Selecione...</option>';
                data.forEach(sub => {
                    options += `<option value="${sub.id}">${sub.name}</option>`;
                });
                options += '<option value="new">+ Nova Subcategoria</option>';
                subcategorySelect.innerHTML = options;
            })
            .catch(error => console.error('Error fetching subcategories:', error));
    }

    // ── Inherited Sectors Check ──
    function checkInheritedSectors() {
        const catSelect = document.getElementById('category_id');
        const subSelect = document.getElementById('subcategory_id');
        const categoryId = catSelect ? catSelect.value : '';
        const subcategoryId = subSelect ? subSelect.value : '';
        const inheritSection = document.getElementById('inherit-sectors-section');
        const inheritMsg = document.getElementById('inherit-sectors-msg');
        const btnInherit = document.getElementById('btn-inherit-sectors');

        if (!inheritSection) return;

        if ((!categoryId || categoryId === 'new') && (!subcategoryId || subcategoryId === 'new')) {
            inheritSection.style.display = 'none';
            return;
        }

        const params = new URLSearchParams();
        if (subcategoryId && subcategoryId !== '' && subcategoryId !== 'new') params.append('subcategory_id', subcategoryId);
        if (categoryId && categoryId !== '' && categoryId !== 'new') params.append('category_id', categoryId);

        fetch('?page=categories&action=getInheritedSectors&' + params.toString())
            .then(r => r.json())
            .then(data => {
                if (data.success && data.sectors && data.sectors.length > 0) {
                    const sourceName = data.source === 'subcategory' ? 'subcategoria' : 'categoria';
                    inheritMsg.innerHTML = `<i class="fas fa-magic me-2"></i>A <strong>${sourceName}</strong> possui <strong>${data.sectors.length}</strong> setor(es) de produção configurados.`;
                    inheritSection.style.display = 'block';
                    btnInherit.dataset.sectors = JSON.stringify(data.sectors);
                } else {
                    inheritSection.style.display = 'none';
                }
            })
            .catch(() => { inheritSection.style.display = 'none'; });
    }

    // Import sectors button handler
    const btnInheritSectors = document.getElementById('btn-inherit-sectors');
    if (btnInheritSectors) {
        btnInheritSectors.addEventListener('click', function() {
            const sectorsJson = this.dataset.sectors;
            if (!sectorsJson) return;

            const sectors = JSON.parse(sectorsJson);
            const selectedContainer = document.getElementById('prod-sectors-selected');
            const availableContainer = document.getElementById('prod-sectors-available');
            if (!selectedContainer || !availableContainer) return;

            const existingCount = selectedContainer.querySelectorAll('.sector-item').length;
            if (existingCount > 0) {
                Swal.fire({
                    title: 'Substituir setores?',
                    html: 'Já existem setores configurados neste produto. Deseja substituí-los pelos setores herdados?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-download me-1"></i> Sim, substituir',
                    cancelButtonText: 'Cancelar'
                }).then(result => {
                    if (result.isConfirmed) {
                        applySectorsImport(sectors, selectedContainer, availableContainer);
                    }
                });
            } else {
                applySectorsImport(sectors, selectedContainer, availableContainer);
            }
        });
    }

    function applySectorsImport(sectors, selectedContainer, availableContainer) {
        // Clear existing sectors
        selectedContainer.querySelectorAll('.sector-item').forEach(item => {
            const id = item.dataset.id;
            item.remove();
            const addBtn = availableContainer.querySelector(`.sector-add-btn[data-id="${id}"]`);
            if (addBtn) addBtn.classList.remove('d-none');
        });
        // Remove placeholder
        const placeholder = selectedContainer.querySelector('.sectors-placeholder');
        if (placeholder) placeholder.remove();

        // Add inherited sectors
        sectors.forEach(s => {
            const item = document.createElement('div');
            item.className = 'sector-item badge d-inline-flex align-items-center me-1 mb-1 px-2 py-2';
            item.dataset.id = s.sector_id;
            item.style.backgroundColor = s.color;
            item.style.cursor = 'grab';
            item.style.fontSize = '0.85rem';
            item.innerHTML = `
                <i class="fas fa-grip-vertical me-1" style="opacity:0.6; font-size:0.7rem;"></i>
                <i class="${s.icon} me-1"></i>
                ${s.sector_name}
                <button type="button" class="btn-close btn-close-white ms-2 sector-remove" style="font-size: 0.5rem;" data-id="${s.sector_id}"></button>
                <input type="hidden" name="sector_ids[]" value="${s.sector_id}">
            `;
            selectedContainer.appendChild(item);

            // Hide from available
            const addBtn = availableContainer.querySelector(`.sector-add-btn[data-id="${s.sector_id}"]`);
            if (addBtn) addBtn.classList.add('d-none');

            // Bind remove event
            item.querySelector('.sector-remove').addEventListener('click', function() {
                const sectorId = this.dataset.id;
                const sItem = selectedContainer.querySelector(`.sector-item[data-id="${sectorId}"]`);
                if (sItem) sItem.remove();
                const aBtn = availableContainer.querySelector(`.sector-add-btn[data-id="${sectorId}"]`);
                if (aBtn) aBtn.classList.remove('d-none');
                if (!selectedContainer.querySelector('.sector-item')) {
                    selectedContainer.innerHTML = '<span class="text-muted small sectors-placeholder" style="line-height: 28px; padding: 2px 6px;"><i class="fas fa-info-circle me-1"></i>Clique nos setores abaixo para adicionar e arraste para ordenar</span>';
                }
            });
        });

        // Hide the import alert
        document.getElementById('inherit-sectors-section').style.display = 'none';

        Swal.fire({
            icon: 'success',
            title: 'Setores importados!',
            text: sectors.length + ' setor(es) importado(s) com sucesso.',
            timer: 2000,
            showConfirmButton: false
        });
    }

    // Check inherited sectors on page load
    setTimeout(checkInheritedSectors, 500);

    // ── Image Drag and Drop ──
    const dropbox = document.getElementById('product-img-dropbox');
    const input = document.getElementById('product_photos');
    const gallery = document.getElementById('image-gallery-preview');
    const dt = new DataTransfer();

    dropbox.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropbox.classList.add('bg-light');
        dropbox.style.borderColor = 'var(--accent-color)';
    });
    dropbox.addEventListener('dragleave', (e) => {
        e.preventDefault();
        dropbox.classList.remove('bg-light');
        dropbox.style.borderColor = '';
    });
    dropbox.addEventListener('drop', (e) => {
        e.preventDefault();
        dropbox.classList.remove('bg-light');
        dropbox.style.borderColor = '';
        handleFiles(e.dataTransfer.files);
    });
    input.addEventListener('change', function(e) {
        handleFiles(this.files);
    });

    function handleFiles(newFiles) {
        let hasNewValidFiles = false;
        Array.from(newFiles).forEach(file => {
            if (file.type.startsWith('image/')) {
                const exists = Array.from(dt.files).some(f => f.name === file.name && f.size === file.size);
                if (!exists) {
                    dt.items.add(file);
                    hasNewValidFiles = true;
                }
            }
        });
        if (hasNewValidFiles) {
             input.files = dt.files;
             renderGallery();
        }
    }

    function renderGallery() {
        gallery.innerHTML = '';
        const files = input.files;
        let currentChecked = document.querySelector('input[name="main_image_index"]:checked');
        let checkedValue = currentChecked ? parseInt(currentChecked.value) : 0;
        if (files.length === 0) return;
        if (checkedValue >= files.length) checkedValue = 0;

        Array.from(files).forEach((file, index) => {
            const col = document.createElement('div');
            col.className = 'col-4 position-relative fade-in';
            const imgContainer = document.createElement('div');
            imgContainer.className = 'border rounded overflow-hidden position-relative';
            imgContainer.style.height = "70px";
            const img = document.createElement('img');
            img.className = 'w-100 h-100 object-fit-cover';
            const reader = new FileReader();
            reader.onload = function(e) { img.src = e.target.result; }
            reader.readAsDataURL(file);

            const controlsDiv = document.createElement('div');
            controlsDiv.className = 'position-absolute top-0 w-100 d-flex justify-content-between p-1';
            controlsDiv.style.background = 'linear-gradient(to bottom, rgba(255,255,255,0.9), rgba(255,255,255,0))';

            const radioWrapper = document.createElement('div');
            radioWrapper.className = 'form-check form-check-inline m-0 bg-white rounded-circle p-1 d-flex align-items-center justify-content-center shadow-sm';
            radioWrapper.style.width = '22px';
            radioWrapper.style.height = '22px';
            const isChecked = index === checkedValue ? 'checked' : '';
            radioWrapper.innerHTML = `<input class="form-check-input m-0" type="radio" name="main_image_index" value="${index}" ${isChecked} style="cursor: pointer;" title="Definir como principal">`;

            const deleteBtn = document.createElement('button');
            deleteBtn.type = 'button';
            deleteBtn.className = 'btn btn-danger btn-sm p-0 d-flex align-items-center justify-content-center rounded-circle shadow-sm';
            deleteBtn.style.width = '22px';
            deleteBtn.style.height = '22px';
            deleteBtn.innerHTML = '<i class="fas fa-times" style="font-size: 10px;"></i>';
            deleteBtn.onclick = function() { removeFile(index); };

            controlsDiv.appendChild(radioWrapper);
            controlsDiv.appendChild(deleteBtn);
            imgContainer.appendChild(img);
            imgContainer.appendChild(controlsDiv);
            col.appendChild(imgContainer);
            gallery.appendChild(col);
        });
    }

    function removeFile(indexToRemove) {
        dt.items.remove(indexToRemove);
        input.files = dt.files;
        renderGallery();
    }
});

// Delete existing image with SweetAlert2
function deleteImage(imageId) {
    Swal.fire({
        title: 'Excluir imagem?',
        text: 'Deseja realmente excluir esta imagem? Esta ação não pode ser desfeita.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#c0392b',
        cancelButtonColor: '#95a5a6',
        confirmButtonText: '<i class="fas fa-trash me-1"></i> Sim, excluir',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('image_id', imageId);
            
            fetch('?page=products&action=deleteImage', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    document.getElementById('img-cont-' + imageId).remove();
                    Swal.fire({ icon: 'success', title: 'Imagem excluída!', timer: 1500, showConfirmButton: false });
                } else {
                    Swal.fire({ icon: 'error', title: 'Erro!', text: 'Não foi possível excluir a imagem.' });
                }
            })
            .catch(() => {
                Swal.fire({ icon: 'error', title: 'Erro!', text: 'Erro de comunicação com o servidor.' });
            });
        }
    });
}
</script>
