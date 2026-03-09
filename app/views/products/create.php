<div class="container py-4">
    <div class="d-flex align-items-center mb-4">
        <a href="?page=products" class="btn btn-outline-secondary btn-sm me-3"><i class="fas fa-arrow-left"></i></a>
        <h2 class="mb-0 text-primary"><i class="fas fa-box-open me-2"></i>Novo Produto</h2>
    </div>
    
    <form id="productForm" method="post" action="?page=products&action=store" enctype="multipart/form-data">
        <?= csrf_field() ?>

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
                        <input type="text" class="form-control form-control-lg" id="name" name="name" required placeholder="Ex: Cartão de Visita, Banner, Adesivo..." autofocus>
                        <div class="form-text"><i class="fas fa-info-circle me-1"></i>Nome principal que identifica o produto.</div>
                    </div>
                    <div class="col-md-4">
                        <label for="sku" class="form-label fw-bold">SKU</label>
                        <input type="text" class="form-control form-control-lg" id="sku" name="sku" placeholder="Ex: CART-VIS-001">
                        <div class="form-text"><i class="fas fa-barcode me-1"></i>Código único do produto (opcional).</div>
                    </div>

                    <!-- Preço e Estoque -->
                    <div class="col-md-4">
                        <label for="price" class="form-label fw-bold">Preço Padrão (R$) <span class="text-danger">*</span></label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text">R$</span>
                            <input type="number" step="0.01" class="form-control" id="price" name="price" required placeholder="0.00">
                        </div>
                        <div class="form-text">Usado quando não há preço específico na tabela.</div>
                    </div>
                    <div class="col-md-4">
                        <label for="cost_price" class="form-label fw-bold">Preço de Custo (R$)</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text">R$</span>
                            <input type="number" step="0.01" class="form-control" id="cost_price" name="cost_price" placeholder="0.00">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Controle de Estoque</label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" id="use_stock_control" name="use_stock_control" value="1">
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
                                    <option value="<?= $category['id'] ?>"><?= $category['name'] ?></option>
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
                            <select class="form-select" id="subcategory_id" name="subcategory_id" disabled>
                                <option value="">Selecione uma categoria primeiro</option>
                            </select>
                         </div>
                         <input type="text" class="form-control mt-2" id="new_subcategory_name" name="new_subcategory_name" placeholder="Nome da nova subcategoria" style="display: none;">
                    </div>

                    <!-- Descrição -->
                    <div class="col-12">
                        <label for="description" class="form-label fw-bold">Descrição</label>
                        <textarea class="form-control" id="description" name="description" rows="2" placeholder="Detalhes do produto, acabamentos, etc."></textarea>
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
                <h5 class="mb-0 text-primary"><i class="fas fa-camera me-2"></i>Galeria de Imagens</h5>
                <i class="fas fa-chevron-up collapse-icon text-muted"></i>
            </div>
            <div class="collapse show" id="collapseImages">
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-md-6">
                            <div id="product-img-dropbox" class="border rounded p-4 d-flex flex-column align-items-center justify-content-center bg-light" style="height: 180px; border-style: dashed !important; cursor: pointer; position: relative; overflow: hidden;">
                                <div id="dropbox-placeholder" class="text-secondary text-center">
                                    <i class="fas fa-cloud-upload-alt fa-3x mb-2"></i>
                                    <p class="mb-0 fw-bold">Arraste imagens ou clique para selecionar</p>
                                    <small class="text-muted">JPG, PNG ou GIF (máx. 5MB cada)</small>
                                </div>
                                <input type="file" name="product_photos[]" id="product_photos" accept="image/*" multiple class="position-absolute top-0 start-0 w-100 h-100 opacity-0" style="cursor: pointer;">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div id="image-gallery-preview" class="row g-2 overflow-auto" style="max-height: 180px;">
                                <div class="text-muted small text-center py-4">
                                    <i class="fas fa-image fa-2x mb-2 d-block text-secondary"></i>
                                    As imagens aparecerão aqui
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
                 data-bs-toggle="collapse" data-bs-target="#collapsePriceTables" aria-expanded="false" role="button">
                <h5 class="mb-0 text-primary">
                    <i class="fas fa-tags me-2"></i>Preços por Tabela
                    <span class="badge bg-secondary ms-2" style="font-size:0.7rem;"><?= count($priceTables) ?> tabela(s)</span>
                </h5>
                <i class="fas fa-chevron-down collapse-icon text-muted"></i>
            </div>
            <div class="collapse" id="collapsePriceTables">
                <div class="card-body p-4">
                    <p class="text-muted small mb-3">Defina preços específicos para cada tabela. Deixe em branco para usar o <strong>preço padrão</strong>.</p>
                    <div class="row g-3">
                        <?php foreach ($priceTables as $pt): ?>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">
                                <?= htmlspecialchars($pt['name']) ?>
                                <?php if ($pt['is_default']): ?>
                                    <span class="badge bg-success" style="font-size:0.65rem;">Padrão</span>
                                <?php endif; ?>
                            </label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">R$</span>
                                <input type="number" step="0.01" class="form-control table-price-input" 
                                       name="table_prices[<?= $pt['id'] ?>]" 
                                       placeholder="Preço padrão"
                                       data-table-id="<?= $pt['id'] ?>">
                            </div>
                            <small class="text-muted">Usando preço padrão</small>
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
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center section-toggle" 
                 data-bs-toggle="collapse" data-bs-target="#collapseTechnical" aria-expanded="false" role="button">
                <h5 class="mb-0 text-primary"><i class="fas fa-cogs me-2"></i>Especificações Técnicas</h5>
                <i class="fas fa-chevron-down collapse-icon text-muted"></i>
            </div>
            <div class="collapse" id="collapseTechnical">
                <div class="card-body p-4">
                    <div class="row g-3">
                         <div class="col-md-6">
                            <label for="format" class="form-label">Formato/Dimensões</label>
                            <input type="text" class="form-control" id="format" name="format" placeholder="Ex: A4, 9x5cm">
                         </div>
                         <div class="col-md-6">
                            <label for="material" class="form-label">Material/Papel</label>
                            <input type="text" class="form-control" id="material" name="material" placeholder="Ex: Couché 300g">
                         </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ════════════════════════════════════════════════════
             SEÇÃO 5 — SETORES DE PRODUÇÃO (colapsável)
             ════════════════════════════════════════════════════ -->
        <?php if (!empty($allSectors)): ?>
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center section-toggle" 
                 data-bs-toggle="collapse" data-bs-target="#collapseSectors" aria-expanded="false" role="button">
                <h5 class="mb-0 text-success"><i class="fas fa-industry me-2"></i>Setores de Produção</h5>
                <i class="fas fa-chevron-down collapse-icon text-muted"></i>
            </div>
            <div class="collapse" id="collapseSectors">
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
                        <span class="text-muted small sectors-placeholder" style="line-height: 28px; padding: 2px 6px;"><i class="fas fa-info-circle me-1"></i>Clique nos setores abaixo para adicionar e arraste para ordenar</span>
                    </div>

                    <div class="d-flex flex-wrap gap-1 mt-2" id="prod-sectors-available">
                        <?php foreach ($allSectors as $sector): ?>
                        <button type="button" class="btn btn-sm sector-add-btn"
                                data-id="<?= $sector['id'] ?>" data-name="<?= htmlspecialchars($sector['name']) ?>"
                                data-icon="<?= $sector['icon'] ?>" data-color="<?= $sector['color'] ?>"
                                style="border: 1px solid <?= $sector['color'] ?>; color: <?= $sector['color'] ?>; font-size: 0.8rem; padding: 3px 10px;">
                            <i class="fas fa-plus me-1" style="font-size: 0.65rem;"></i>
                            <i class="<?= $sector['icon'] ?> me-1"></i><?= htmlspecialchars($sector['name']) ?>
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
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center section-toggle" 
                 data-bs-toggle="collapse" data-bs-target="#collapseGrades" aria-expanded="false" role="button">
                <h5 class="mb-0 text-info"><i class="fas fa-th-large me-2"></i>Grades / Variações</h5>
                <i class="fas fa-chevron-down collapse-icon text-muted"></i>
            </div>
            <div class="collapse" id="collapseGrades">
                <div class="card-body p-4">
                    <?php include 'app/views/products/_grades_partial.php'; ?>
                </div>
            </div>
        </div>

        <!-- ════════════════════════════════════════════════════
             SEÇÃO 7 — INFORMAÇÕES FISCAIS (colapsável)
             ════════════════════════════════════════════════════ -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center section-toggle" 
                 data-bs-toggle="collapse" data-bs-target="#collapseFiscal" aria-expanded="false" role="button">
                <h5 class="mb-0" style="color: #8e44ad;"><i class="fas fa-file-invoice me-2"></i>Informações Fiscais (NF-e)</h5>
                <i class="fas fa-chevron-down collapse-icon text-muted"></i>
            </div>
            <div class="collapse" id="collapseFiscal">
                <div class="card-body p-4">
                    <?php include 'app/views/products/_fiscal_partial.php'; ?>
                </div>
            </div>
        </div>

        <!-- ════════════════════════════════════════════════════
             BOTÕES DE AÇÃO
             ════════════════════════════════════════════════════ -->
        <div class="d-flex justify-content-between align-items-center py-3 sticky-bottom bg-body-tertiary rounded px-3 mb-3 shadow-sm">
            <a href="?page=products" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Cancelar</a>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-primary" id="btnExpandAll"><i class="fas fa-expand-alt me-1"></i>Expandir Tudo</button>
                <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save me-2"></i>Salvar Produto</button>
            </div>
        </div>

    </form>
</div>

<!-- SortableJS CDN -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<style>
.section-toggle { cursor: pointer; transition: background 0.2s; }
.section-toggle:hover { background-color: #f8f9fa !important; }
.section-toggle .collapse-icon { transition: transform 0.3s ease; }
[aria-expanded="true"] .collapse-icon { transform: rotate(180deg); }
[aria-expanded="false"] .collapse-icon { transform: rotate(0deg); }
.sticky-bottom { position: sticky; bottom: 0; z-index: 10; }
/* Remover borda/shadow dos fieldsets dentro dos cards colapsáveis */
.card .card-body fieldset { border: none !important; box-shadow: none !important; padding: 0 !important; margin: 0 !important; }
.card .card-body fieldset > legend { display: none; }
</style>

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
            subcategorySelect.disabled = false;
            subcategorySelect.innerHTML = '<option value="new">+ Nova Subcategoria</option>';
            subcategorySelect.value = 'new';
            subcategorySelect.dispatchEvent(new Event('change'));
        } else {
            newCategoryInput.style.display = 'none';
            newCategoryInput.required = false;
            newCategoryInput.value = '';
            if (categoryId) {
                subcategorySelect.disabled = false;
                fetchSubcategories(categoryId);
            } else {
                subcategorySelect.disabled = true;
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

            // Clear existing
            selectedContainer.querySelectorAll('.sector-item').forEach(item => {
                const id = item.dataset.id;
                item.remove();
                const addBtn = availableContainer.querySelector(`.sector-add-btn[data-id="${id}"]`);
                if (addBtn) addBtn.classList.remove('d-none');
            });
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
                const addBtn = availableContainer.querySelector(`.sector-add-btn[data-id="${s.sector_id}"]`);
                if (addBtn) addBtn.classList.add('d-none');
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

            document.getElementById('inherit-sectors-section').style.display = 'none';
            Swal.fire({ icon: 'success', title: 'Setores importados!', text: sectors.length + ' setor(es) importado(s).', timer: 2000, showConfirmButton: false });
        });
    }

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
            imgContainer.style.height = "80px";
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
</script>
