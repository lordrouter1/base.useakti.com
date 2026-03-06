<!-- ====================================================
     FIELDSET: Grades / Variações do Produto
     Permite adicionar múltiplas grades (ex: Tamanho + Cor)
     com valores dinâmicos e preview de combinações.
     ==================================================== -->
<fieldset class="p-4 mb-4" id="grades-fieldset">
    <legend class="float-none w-auto px-2 fs-5 text-info">
        <i class="fas fa-th-large me-2"></i>Grades / Variações
        <span class="badge bg-info text-white ms-2" style="font-size:0.65rem;" id="grades-count-badge">
            <?= count($productGrades ?? []) ?> grade(s)
        </span>
    </legend>
    <p class="text-muted small mb-3">
        <i class="fas fa-info-circle me-1"></i>
        Adicione grades de variação para este produto (ex: Tamanho, Cor, Material). 
        Cada grade pode ter múltiplos valores. As combinações são geradas automaticamente.
    </p>

    <!-- Botão para herdar grades da categoria/subcategoria -->
    <div id="inherit-grades-section" class="mb-3" style="display:none;">
        <div class="alert alert-info py-2 px-3 d-flex align-items-center justify-content-between" id="inherit-grades-alert" style="font-size:0.85rem;">
            <div>
                <i class="fas fa-magic me-2"></i>
                <span id="inherit-grades-msg">Esta subcategoria/categoria possui grades padrão.</span>
            </div>
            <button type="button" class="btn btn-sm btn-info text-white" id="btn-inherit-grades">
                <i class="fas fa-download me-1"></i>Importar Grades
            </button>
        </div>
    </div>

    <!-- Container das grades adicionadas -->
    <div id="grades-container">
        <?php if (!empty($productGrades)): ?>
            <?php foreach ($productGrades as $gIdx => $grade): ?>
            <div class="grade-item card border mb-3" data-grade-index="<?= $gIdx ?>">
                <div class="card-header bg-light d-flex align-items-center justify-content-between py-2">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-grip-vertical me-2 text-muted grade-drag-handle" style="cursor:grab;"></i>
                        <i class="<?= $grade['type_icon'] ?> me-2 text-info"></i>
                        <span class="fw-bold small grade-type-label"><?= htmlspecialchars($grade['type_name']) ?></span>
                        <input type="hidden" name="grades[<?= $gIdx ?>][grade_type_id]" value="<?= $grade['grade_type_id'] ?>">
                        <input type="hidden" name="grades[<?= $gIdx ?>][type_name]" value="<?= htmlspecialchars($grade['type_name']) ?>">
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 btn-remove-grade" title="Remover grade">
                        <i class="fas fa-trash-alt" style="font-size:0.75rem;"></i>
                    </button>
                </div>
                <div class="card-body p-3">
                    <label class="form-label small fw-bold text-muted mb-2">
                        Valores da grade "<?= htmlspecialchars($grade['type_name']) ?>"
                    </label>
                    <div class="grade-values-container d-flex flex-wrap gap-2 mb-2">
                        <?php foreach ($grade['values'] as $vIdx => $val): ?>
                        <div class="input-group input-group-sm grade-value-item" style="width:auto; max-width:180px;">
                            <input type="text" class="form-control form-control-sm grade-value-input" 
                                   name="grades[<?= $gIdx ?>][values][]" 
                                   value="<?= htmlspecialchars($val['value']) ?>" 
                                   placeholder="Valor" required style="min-width:80px;">
                            <button type="button" class="btn btn-outline-danger btn-sm btn-remove-value" title="Remover">
                                <i class="fas fa-times" style="font-size:0.65rem;"></i>
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-info btn-add-value">
                        <i class="fas fa-plus me-1"></i>Adicionar Valor
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Botão para adicionar nova grade -->
    <div class="d-flex align-items-center gap-2 mt-2" id="add-grade-controls">
        <select class="form-select form-select-sm" id="grade-type-selector" style="max-width:220px;">
            <option value="">Selecione um tipo de grade...</option>
            <?php 
            $usedTypeIds = array_column($productGrades ?? [], 'grade_type_id');
            foreach ($gradeTypes as $gt): 
                $isUsed = in_array($gt['id'], $usedTypeIds);
            ?>
            <option value="<?= $gt['id'] ?>" data-icon="<?= $gt['icon'] ?>" data-name="<?= htmlspecialchars($gt['name']) ?>" <?= $isUsed ? 'disabled' : '' ?>>
                <?= htmlspecialchars($gt['name']) ?> <?= $isUsed ? '(já adicionada)' : '' ?>
            </option>
            <?php endforeach; ?>
            <option value="new">+ Criar novo tipo de grade</option>
        </select>
        <button type="button" class="btn btn-sm btn-info text-white" id="btn-add-grade" disabled>
            <i class="fas fa-plus me-1"></i>Adicionar Grade
        </button>
    </div>

    <!-- Input para novo tipo de grade (escondido) -->
    <div id="new-grade-type-form" class="mt-2" style="display:none;">
        <div class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label small">Nome do novo tipo</label>
                <input type="text" class="form-control form-control-sm" id="new-grade-type-name" placeholder="Ex: Gramatura">
            </div>
            <div class="col-auto">
                <label class="form-label small">Ícone (Font Awesome)</label>
                <input type="text" class="form-control form-control-sm" id="new-grade-type-icon" value="fas fa-th" placeholder="fas fa-th">
            </div>
            <div class="col-auto">
                <button type="button" class="btn btn-sm btn-success" id="btn-save-new-grade-type">
                    <i class="fas fa-check me-1"></i>Criar
                </button>
                <button type="button" class="btn btn-sm btn-secondary" id="btn-cancel-new-grade-type">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
</fieldset>

<!-- Preview de Combinações -->
<?php if (!empty($productCombinations)): ?>
<fieldset class="p-4 mb-4" id="combinations-fieldset">
    <legend class="float-none w-auto px-2 fs-5 text-warning">
        <i class="fas fa-layer-group me-2"></i>Combinações 
        <span class="badge bg-warning text-dark ms-2" style="font-size:0.65rem;" id="combos-count-badge">
            <?= count($productCombinations) ?> combinação(ões)
        </span>
    </legend>
    <p class="text-muted small mb-3">
        <i class="fas fa-info-circle me-1"></i>
        Combinações geradas a partir das grades. Defina preço específico por combinação, ou deixe em branco para usar o preço padrão do produto.
        <strong>Desative combinações que não devem estar disponíveis.</strong>
    </p>
    <div class="table-responsive" style="border:none; box-shadow:none;">
        <table class="table table-sm table-hover mb-0" id="combinations-table">
            <thead class="bg-light">
                <tr>
                    <th style="font-size:0.8rem; width:50px;" class="text-center">Ativo</th>
                    <th style="font-size:0.8rem;">Combinação</th>
                    <th style="font-size:0.8rem; width:130px;">SKU</th>
                    <th style="font-size:0.8rem; width:130px;">Preço (R$)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($productCombinations as $combo): ?>
                <tr class="<?= !$combo['is_active'] ? 'table-danger' : '' ?>">
                    <td class="text-center">
                        <input type="hidden" name="combinations[<?= htmlspecialchars($combo['combination_key']) ?>][is_active]" value="<?= $combo['is_active'] ?>">
                        <div class="form-check form-switch d-flex justify-content-center mb-0">
                            <input class="form-check-input combo-active-toggle" type="checkbox"
                                   data-combo-key="<?= htmlspecialchars($combo['combination_key']) ?>"
                                   <?= $combo['is_active'] ? 'checked' : '' ?>
                                   style="cursor:pointer;">
                        </div>
                    </td>
                    <td class="small align-middle">
                        <i class="fas fa-cube text-muted me-1"></i>
                        <span class="combo-label-text <?= !$combo['is_active'] ? 'text-decoration-line-through text-muted' : '' ?>"><?= htmlspecialchars($combo['combination_label']) ?></span>
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm" 
                               name="combinations[<?= htmlspecialchars($combo['combination_key']) ?>][sku]" 
                               value="<?= htmlspecialchars($combo['sku'] ?? '') ?>" 
                               placeholder="SKU">
                    </td>
                    <td>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text" style="font-size:0.75rem;">R$</span>
                            <input type="number" step="0.01" class="form-control form-control-sm" 
                                   name="combinations[<?= htmlspecialchars($combo['combination_key']) ?>][price]" 
                                   value="<?= $combo['price_override'] !== null ? number_format($combo['price_override'], 2, '.', '') : '' ?>" 
                                   placeholder="Padrão">
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</fieldset>
<?php else: ?>
<fieldset class="p-4 mb-4" id="combinations-fieldset" style="display:none;">
    <legend class="float-none w-auto px-2 fs-5 text-warning">
        <i class="fas fa-layer-group me-2"></i>Combinações 
        <span class="badge bg-warning text-dark ms-2" style="font-size:0.65rem;" id="combos-count-badge">0</span>
    </legend>
    <p class="text-muted small mb-3">
        <i class="fas fa-info-circle me-1"></i>
        As combinações serão geradas após salvar as grades com valores.
    </p>
    <div id="combinations-preview" class="text-center text-muted py-3">
        <i class="fas fa-cubes fa-2x mb-2 d-block"></i>
        <span class="small">Adicione grades com valores acima para gerar combinações.</span>
    </div>
    <div class="table-responsive" style="border:none; box-shadow:none; display:none;" id="combinations-table-wrapper">
        <table class="table table-sm table-hover mb-0" id="combinations-table">
            <thead class="bg-light">
                <tr>
                    <th style="font-size:0.8rem; width:50px;" class="text-center">Ativo</th>
                    <th style="font-size:0.8rem;">Combinação</th>
                    <th style="font-size:0.8rem; width:130px;">SKU</th>
                    <th style="font-size:0.8rem; width:130px;">Preço (R$)</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</fieldset>
<?php endif; ?>

<!-- JavaScript para gerenciamento de grades -->
<script>
(function initGradesSystem() {
    let gradeIndex = <?= count($productGrades ?? []) ?>;
    const container = document.getElementById('grades-container');
    const selector = document.getElementById('grade-type-selector');
    const btnAdd = document.getElementById('btn-add-grade');
    const newTypeForm = document.getElementById('new-grade-type-form');
    const countBadge = document.getElementById('grades-count-badge');
    const combosBadge = document.getElementById('combos-count-badge');
    const combosFieldset = document.getElementById('combinations-fieldset');

    if (!container || !selector || !btnAdd) return;

    // Enable/disable add button based on selector
    selector.addEventListener('change', function() {
        if (this.value === 'new') {
            newTypeForm.style.display = 'block';
            btnAdd.disabled = true;
        } else {
            newTypeForm.style.display = 'none';
            btnAdd.disabled = !this.value;
        }
    });

    // Add grade
    btnAdd.addEventListener('click', function() {
        const typeId = selector.value;
        if (!typeId || typeId === 'new') return;

        const opt = selector.options[selector.selectedIndex];
        const typeName = opt.dataset.name;
        const typeIcon = opt.dataset.icon || 'fas fa-th';

        addGradeCard(typeId, typeName, typeIcon);

        // Disable this option in the selector
        opt.disabled = true;
        opt.textContent = typeName + ' (já adicionada)';
        selector.value = '';
        btnAdd.disabled = true;
        updateCount();
    });

    // Create new grade type via AJAX
    document.getElementById('btn-save-new-grade-type')?.addEventListener('click', function() {
        const name = document.getElementById('new-grade-type-name').value.trim();
        const icon = document.getElementById('new-grade-type-icon').value.trim() || 'fas fa-th';
        if (!name) return;

        const formData = new FormData();
        formData.append('name', name);
        formData.append('icon', icon);

        fetch('?page=products&action=createGradeType', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Add to selector
                const newOpt = document.createElement('option');
                newOpt.value = data.id;
                newOpt.dataset.name = data.name;
                newOpt.dataset.icon = data.icon;
                newOpt.textContent = data.name;
                newOpt.disabled = true;
                newOpt.textContent = data.name + ' (já adicionada)';
                // Insert before the "new" option
                const newOptRef = selector.querySelector('option[value="new"]');
                selector.insertBefore(newOpt, newOptRef);

                // Add the grade card
                addGradeCard(data.id, data.name, data.icon);
                newTypeForm.style.display = 'none';
                document.getElementById('new-grade-type-name').value = '';
                selector.value = '';
                updateCount();
            } else {
                Swal.fire('Erro', data.message || 'Erro ao criar tipo de grade.', 'error');
            }
        });
    });

    document.getElementById('btn-cancel-new-grade-type')?.addEventListener('click', function() {
        newTypeForm.style.display = 'none';
        selector.value = '';
    });

    function addGradeCard(typeId, typeName, typeIcon) {
        const idx = gradeIndex++;
        const card = document.createElement('div');
        card.className = 'grade-item card border mb-3';
        card.dataset.gradeIndex = idx;
        card.innerHTML = `
            <div class="card-header bg-light d-flex align-items-center justify-content-between py-2">
                <div class="d-flex align-items-center">
                    <i class="fas fa-grip-vertical me-2 text-muted grade-drag-handle" style="cursor:grab;"></i>
                    <i class="${typeIcon} me-2 text-info"></i>
                    <span class="fw-bold small grade-type-label">${typeName}</span>
                    <input type="hidden" name="grades[${idx}][grade_type_id]" value="${typeId}">
                    <input type="hidden" name="grades[${idx}][type_name]" value="${typeName}">
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 btn-remove-grade" title="Remover grade">
                    <i class="fas fa-trash-alt" style="font-size:0.75rem;"></i>
                </button>
            </div>
            <div class="card-body p-3">
                <label class="form-label small fw-bold text-muted mb-2">
                    Valores da grade "${typeName}"
                </label>
                <div class="grade-values-container d-flex flex-wrap gap-2 mb-2">
                    <div class="input-group input-group-sm grade-value-item" style="width:auto; max-width:180px;">
                        <input type="text" class="form-control form-control-sm grade-value-input" 
                               name="grades[${idx}][values][]" placeholder="Ex: P" required style="min-width:80px;">
                        <button type="button" class="btn btn-outline-danger btn-sm btn-remove-value" title="Remover">
                            <i class="fas fa-times" style="font-size:0.65rem;"></i>
                        </button>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-info btn-add-value">
                    <i class="fas fa-plus me-1"></i>Adicionar Valor
                </button>
            </div>
        `;
        container.appendChild(card);
        bindGradeEvents(card, idx);
        // Focus first input
        card.querySelector('.grade-value-input')?.focus();
        updateCombinationsPreview();
    }

    function bindGradeEvents(card, idx) {
        // Remove grade
        card.querySelector('.btn-remove-grade').addEventListener('click', function() {
            const typeName = card.querySelector('.grade-type-label').textContent;
            Swal.fire({
                title: 'Remover grade?',
                html: `Deseja remover a grade <strong>${typeName}</strong> e todos os seus valores?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#c0392b',
                confirmButtonText: 'Sim, remover',
                cancelButtonText: 'Cancelar'
            }).then(result => {
                if (result.isConfirmed) {
                    // Re-enable in selector
                    const typeId = card.querySelector('input[name*="grade_type_id"]').value;
                    const opt = selector.querySelector(`option[value="${typeId}"]`);
                    if (opt) {
                        opt.disabled = false;
                        opt.textContent = opt.dataset.name;
                    }
                    card.remove();
                    updateCount();
                    updateCombinationsPreview();
                }
            });
        });

        // Add value
        card.querySelector('.btn-add-value').addEventListener('click', function() {
            addValueInput(card, idx);
        });

        // Remove value buttons
        card.querySelectorAll('.btn-remove-value').forEach(btn => {
            btn.addEventListener('click', function() {
                removeValueInput(this, card);
            });
        });

        // Value input change triggers combination preview
        card.querySelectorAll('.grade-value-input').forEach(input => {
            input.addEventListener('input', debounce(updateCombinationsPreview, 500));
        });
    }

    function addValueInput(card, idx) {
        const valuesContainer = card.querySelector('.grade-values-container');
        const item = document.createElement('div');
        item.className = 'input-group input-group-sm grade-value-item';
        item.style.width = 'auto';
        item.style.maxWidth = '180px';
        item.innerHTML = `
            <input type="text" class="form-control form-control-sm grade-value-input" 
                   name="grades[${idx}][values][]" placeholder="Valor" required style="min-width:80px;">
            <button type="button" class="btn btn-outline-danger btn-sm btn-remove-value" title="Remover">
                <i class="fas fa-times" style="font-size:0.65rem;"></i>
            </button>
        `;
        valuesContainer.appendChild(item);
        item.querySelector('.grade-value-input').focus();

        item.querySelector('.btn-remove-value').addEventListener('click', function() {
            removeValueInput(this, card);
        });

        item.querySelector('.grade-value-input').addEventListener('input', debounce(updateCombinationsPreview, 500));
    }

    function removeValueInput(btn, card) {
        const item = btn.closest('.grade-value-item');
        const valuesContainer = card.querySelector('.grade-values-container');
        // Don't remove if it's the last one
        if (valuesContainer.querySelectorAll('.grade-value-item').length <= 1) {
            Swal.fire('Atenção', 'A grade precisa ter pelo menos um valor.', 'warning');
            return;
        }
        item.remove();
        updateCombinationsPreview();
    }

    function updateCount() {
        const count = container.querySelectorAll('.grade-item').length;
        countBadge.textContent = count + ' grade(s)';
    }

    function updateCombinationsPreview() {
        const gradeItems = container.querySelectorAll('.grade-item');
        const gradeArrays = [];

        gradeItems.forEach(card => {
            const typeName = card.querySelector('.grade-type-label').textContent.trim();
            const inputs = card.querySelectorAll('.grade-value-input');
            const values = [];
            inputs.forEach(inp => {
                const v = inp.value.trim();
                if (v) values.push(v);
            });
            if (values.length > 0) {
                gradeArrays.push({ name: typeName, values: values });
            }
        });

        if (gradeArrays.length === 0) {
            combosFieldset.style.display = 'none';
            return;
        }

        // Generate cartesian product client-side for preview
        let combos = [[]];
        gradeArrays.forEach(grade => {
            const newCombos = [];
            combos.forEach(existing => {
                grade.values.forEach(val => {
                    newCombos.push([...existing, { name: grade.name, value: val }]);
                });
            });
            combos = newCombos;
        });

        // Show preview
        combosFieldset.style.display = 'block';
        combosBadge.textContent = combos.length + ' combinação(ões)';

        const preview = document.getElementById('combinations-preview');
        const tableWrapper = document.getElementById('combinations-table-wrapper');
        const tbody = document.querySelector('#combinations-table tbody');

        if (combos.length > 100) {
            if (preview) {
                preview.style.display = 'block';
                preview.innerHTML = '<i class="fas fa-exclamation-triangle fa-2x mb-2 d-block text-warning"></i><span class="small">Muitas combinações (' + combos.length + '). As combinações serão geradas ao salvar.</span>';
            }
            if (tableWrapper) tableWrapper.style.display = 'none';
            return;
        }

        if (preview) preview.style.display = 'none';
        if (tableWrapper) tableWrapper.style.display = 'block';

        if (tbody) {
            tbody.innerHTML = '';
            combos.forEach(combo => {
                const label = combo.map(c => c.name + ': ' + c.value).join(' / ');
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="text-center">
                        <div class="form-check form-switch d-flex justify-content-center mb-0">
                            <input class="form-check-input" type="checkbox" checked disabled style="cursor:pointer;">
                        </div>
                    </td>
                    <td class="small align-middle">
                        <i class="fas fa-cube text-muted me-1"></i>
                        ${label}
                    </td>
                    <td><span class="text-muted small">Após salvar</span></td>
                    <td><span class="text-muted small">Após salvar</span></td>
                `;
                tbody.appendChild(tr);
            });
        }
    }

    // Bind events for existing grade cards (edit mode)
    container.querySelectorAll('.grade-item').forEach(card => {
        const idx = parseInt(card.dataset.gradeIndex);
        bindGradeEvents(card, idx);
    });

    // Initialize SortableJS for grade cards ordering
    if (typeof Sortable !== 'undefined') {
        new Sortable(container, {
            animation: 150,
            handle: '.grade-drag-handle',
            ghostClass: 'bg-light',
        });
    }

    function debounce(fn, delay) {
        let timer;
        return function(...args) {
            clearTimeout(timer);
            timer = setTimeout(() => fn.apply(this, args), delay);
        };
    }

    // ═════════════════════════════════════════════════
    // Combination Toggle (ativar/inativar combinação)
    // ═════════════════════════════════════════════════
    document.querySelectorAll('.combo-active-toggle').forEach(toggle => {
        toggle.addEventListener('change', function() {
            const row = this.closest('tr');
            const hiddenInput = row.querySelector('input[type="hidden"][name*="is_active"]');
            const labelText = row.querySelector('.combo-label-text');
            if (this.checked) {
                hiddenInput.value = '1';
                row.classList.remove('table-danger');
                if (labelText) labelText.classList.remove('text-decoration-line-through', 'text-muted');
            } else {
                hiddenInput.value = '0';
                row.classList.add('table-danger');
                if (labelText) labelText.classList.add('text-decoration-line-through', 'text-muted');
            }
        });
    });

    // ═════════════════════════════════════════════════
    // Inherit Grades from Category/Subcategory
    // ═════════════════════════════════════════════════
    const inheritSection = document.getElementById('inherit-grades-section');
    const inheritMsg = document.getElementById('inherit-grades-msg');
    const btnInherit = document.getElementById('btn-inherit-grades');

    function checkInheritedGrades() {
        // Get current category and subcategory selections
        const catSelect = document.querySelector('select[name="category_id"]');
        const subSelect = document.querySelector('select[name="subcategory_id"]');
        const categoryId = catSelect ? catSelect.value : '';
        const subcategoryId = subSelect ? subSelect.value : '';

        if (!categoryId && !subcategoryId) {
            inheritSection.style.display = 'none';
            return;
        }

        const params = new URLSearchParams();
        if (subcategoryId && subcategoryId !== '' && subcategoryId !== 'new') params.append('subcategory_id', subcategoryId);
        if (categoryId && categoryId !== '' && categoryId !== 'new') params.append('category_id', categoryId);

        fetch('?page=categories&action=getInheritedGrades&' + params.toString())
            .then(r => r.json())
            .then(data => {
                if (data.success && data.grades && data.grades.length > 0) {
                    const sourceName = data.source === 'subcategory' ? 'subcategoria' : 'categoria';
                    inheritMsg.innerHTML = `<i class="fas fa-magic me-2"></i>A <strong>${sourceName}</strong> selecionada possui <strong>${data.grades.length}</strong> grade(s) padrão definidas.`;
                    inheritSection.style.display = 'block';

                    // Store data for import
                    btnInherit.dataset.grades = JSON.stringify(data.grades);
                    btnInherit.dataset.inactiveKeys = JSON.stringify(data.inactive_keys || []);
                } else {
                    inheritSection.style.display = 'none';
                }
            })
            .catch(() => { inheritSection.style.display = 'none'; });
    }

    // Listen for category/subcategory changes
    const catSelect = document.querySelector('select[name="category_id"]');
    const subSelect = document.querySelector('select[name="subcategory_id"]');
    if (catSelect) catSelect.addEventListener('change', function() { setTimeout(checkInheritedGrades, 500); });
    if (subSelect) subSelect.addEventListener('change', checkInheritedGrades);

    // Import button handler
    if (btnInherit) {
        btnInherit.addEventListener('click', function() {
            const gradesJson = this.dataset.grades;
            if (!gradesJson) return;

            const grades = JSON.parse(gradesJson);
            
            // Confirm if there are already grades
            const existingCount = container.querySelectorAll('.grade-item').length;
            if (existingCount > 0) {
                if (!confirm('Já existem grades configuradas. Deseja substituí-las pelas grades herdadas?')) {
                    return;
                }
                // Clear existing grades
                container.innerHTML = '';
                // Re-enable all options in selector
                selector.querySelectorAll('option').forEach(opt => {
                    if (opt.value && opt.value !== 'new') {
                        opt.disabled = false;
                        opt.textContent = opt.dataset.name || opt.textContent;
                    }
                });
                gradeIndex = 0;
            }

            // Add each inherited grade
            grades.forEach(grade => {
                const typeId = grade.grade_type_id;
                const typeName = grade.type_name;
                const typeIcon = grade.type_icon || 'fas fa-th';
                const values = grade.values || [];

                const idx = gradeIndex++;
                const card = document.createElement('div');
                card.className = 'grade-item card border mb-3';
                card.dataset.gradeIndex = idx;

                let valuesHtml = '';
                values.forEach(val => {
                    valuesHtml += `
                        <div class="input-group input-group-sm grade-value-item" style="width:auto; max-width:180px;">
                            <input type="text" class="form-control form-control-sm grade-value-input" 
                                   name="grades[${idx}][values][]" 
                                   value="${val.value}" 
                                   placeholder="Valor" required style="min-width:80px;">
                            <button type="button" class="btn btn-outline-danger btn-sm btn-remove-value" title="Remover">
                                <i class="fas fa-times" style="font-size:0.65rem;"></i>
                            </button>
                        </div>
                    `;
                });

                card.innerHTML = `
                    <div class="card-header bg-light d-flex align-items-center justify-content-between py-2">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-grip-vertical me-2 text-muted grade-drag-handle" style="cursor:grab;"></i>
                            <i class="${typeIcon} me-2 text-info"></i>
                            <span class="fw-bold small grade-type-label">${typeName}</span>
                            <input type="hidden" name="grades[${idx}][grade_type_id]" value="${typeId}">
                            <input type="hidden" name="grades[${idx}][type_name]" value="${typeName}">
                            <span class="badge bg-success ms-2" style="font-size:0.55rem;">Herdada</span>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 btn-remove-grade" title="Remover grade">
                            <i class="fas fa-trash-alt" style="font-size:0.75rem;"></i>
                        </button>
                    </div>
                    <div class="card-body p-3">
                        <label class="form-label small fw-bold text-muted mb-2">
                            Valores da grade "${typeName}"
                        </label>
                        <div class="grade-values-container d-flex flex-wrap gap-2 mb-2">
                            ${valuesHtml}
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-info btn-add-value">
                            <i class="fas fa-plus me-1"></i>Adicionar Valor
                        </button>
                    </div>
                `;
                container.appendChild(card);
                bindGradeEvents(card, idx);

                // Disable the option in selector
                const opt = selector.querySelector(`option[value="${typeId}"]`);
                if (opt) {
                    opt.disabled = true;
                    opt.textContent = typeName + ' (já adicionada)';
                }
            });

            updateCount();
            updateCombinationsPreview();
            inheritSection.style.display = 'none';

            // Show success toast
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Grades importadas!',
                    text: grades.length + ' grade(s) importada(s) com sucesso.',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        });
    }

    // Check on page load (for edit mode — always check so user can re-import if needed)
    setTimeout(checkInheritedGrades, 300);

})();
</script>
