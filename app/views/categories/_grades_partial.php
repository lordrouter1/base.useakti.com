<!-- ====================================================
     PARTIAL: Grades / Variações para Categoria ou Subcategoria
     Reutilizável em forms de create/edit de categorias e subcategorias.
     
     Variáveis esperadas:
       $entityType      - 'category' ou 'subcategory'
       $entityGrades    - array de grades com values (ou [])
       $entityCombinations - array de combinações (ou [])
       $gradeTypes      - todos os tipos de grade disponíveis
     ==================================================== -->
<?php
    $prefix = $entityType === 'category' ? 'cat_grades' : 'sub_grades';
    $fieldName = $entityType === 'category' ? 'category_grades' : 'subcategory_grades';
    $combosFieldName = $entityType === 'category' ? 'category_combinations' : 'subcategory_combinations';
    $colorClass = $entityType === 'category' ? 'primary' : 'success';
?>

<div class="mt-3" id="<?= $prefix ?>-section">
    <label class="form-label fw-bold small">
        <i class="fas fa-th-large me-1 text-info"></i>Grades / Variações Padrão
        <span class="badge bg-info text-white ms-1" style="font-size:0.6rem;" id="<?= $prefix ?>-count-badge">
            <?= count($entityGrades ?? []) ?> grade(s)
        </span>
    </label>
    <p class="text-muted" style="font-size: 0.7rem; margin-bottom: 0.5rem;">
        Defina grades padrão que serão herdadas pelos produtos desta <?= $entityType === 'category' ? 'categoria' : 'subcategoria' ?>.
    </p>

    <!-- Container das grades -->
    <div id="<?= $prefix ?>-container">
        <?php if (!empty($entityGrades)): ?>
            <?php foreach ($entityGrades as $gIdx => $grade): ?>
            <div class="<?= $prefix ?>-item card border mb-2" data-grade-index="<?= $gIdx ?>">
                <div class="card-header bg-light d-flex align-items-center justify-content-between py-1 px-2" style="font-size:0.8rem;">
                    <div class="d-flex align-items-center">
                        <i class="<?= $grade['type_icon'] ?> me-1 text-info" style="font-size:0.75rem;"></i>
                        <span class="fw-bold <?= $prefix ?>-type-label"><?= e($grade['type_name']) ?></span>
                        <input type="hidden" name="<?= $fieldName ?>[<?= $gIdx ?>][grade_type_id]" value="<?= $grade['grade_type_id'] ?>">
                        <input type="hidden" name="<?= $fieldName ?>[<?= $gIdx ?>][type_name]" value="<?= eAttr($grade['type_name']) ?>">
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1 <?= $prefix ?>-remove-grade" title="Remover" style="font-size:0.65rem;">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
                <div class="card-body p-2">
                    <div class="<?= $prefix ?>-values-container d-flex flex-wrap gap-1 mb-1">
                        <?php foreach ($grade['values'] as $vIdx => $val): ?>
                        <div class="input-group input-group-sm <?= $prefix ?>-value-item" style="width:auto; max-width:140px;">
                            <input type="text" class="form-control form-control-sm <?= $prefix ?>-value-input"
                                   name="<?= $fieldName ?>[<?= $gIdx ?>][values][]"
                                   value="<?= eAttr($val['value']) ?>"
                                   placeholder="Valor" required style="min-width:60px; font-size:0.75rem;">
                            <button type="button" class="btn btn-outline-danger btn-sm <?= $prefix ?>-remove-value" title="Remover" style="font-size:0.6rem;">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-info <?= $prefix ?>-add-value" style="font-size:0.7rem; padding:1px 6px;">
                        <i class="fas fa-plus me-1"></i>Valor
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Add grade controls -->
    <div class="d-flex align-items-center gap-1 mt-1" id="<?= $prefix ?>-add-controls">
        <select class="form-select form-select-sm" id="<?= $prefix ?>-type-selector" style="max-width:180px; font-size:0.75rem;">
            <option value="">Tipo de grade...</option>
            <?php
            $usedTypeIds = array_column($entityGrades ?? [], 'grade_type_id');
            foreach ($gradeTypes as $gt):
                $isUsed = in_array($gt['id'], $usedTypeIds);
            ?>
            <option value="<?= (int)$gt['id'] ?>" data-icon="<?= eAttr($gt['icon']) ?>" data-name="<?= eAttr($gt['name']) ?>" <?= $isUsed ? 'disabled' : '' ?>>
                <?= e($gt['name']) ?> <?= $isUsed ? '(usada)' : '' ?>
            </option>
            <?php endforeach; ?>
        </select>
        <button type="button" class="btn btn-sm btn-info text-white" id="<?= $prefix ?>-btn-add" disabled style="font-size:0.75rem; padding:2px 8px;">
            <i class="fas fa-plus me-1"></i>Adicionar
        </button>
    </div>

    <!-- Combinations with toggle -->
    <?php if (!empty($entityCombinations)): ?>
    <div class="mt-2" id="<?= $prefix ?>-combos-section">
        <label class="form-label fw-bold small mb-1">
            <i class="fas fa-layer-group me-1 text-warning"></i>Combinações
            <span class="badge bg-warning text-dark ms-1" style="font-size:0.6rem;">
                <?= count($entityCombinations) ?>
            </span>
        </label>
        <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
            <table class="table table-sm table-bordered mb-0" style="font-size:0.75rem;">
                <thead class="bg-light">
                    <tr>
                        <th style="width:40px;" class="text-center">Ativo</th>
                        <th>Combinação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($entityCombinations as $combo): ?>
                    <tr class="<?= !$combo['is_active'] ? 'table-danger text-decoration-line-through' : '' ?>">
                        <td class="text-center">
                            <input type="hidden" name="<?= $combosFieldName ?>[<?= e($combo['combination_key']) ?>][is_active]" value="<?= $combo['is_active'] ?>">
                            <div class="form-check form-switch d-flex justify-content-center mb-0">
                                <input class="form-check-input <?= $prefix ?>-combo-toggle" type="checkbox"
                                       data-combo-key="<?= eAttr($combo['combination_key']) ?>"
                                       <?= $combo['is_active'] ? 'checked' : '' ?>
                                       style="cursor:pointer;">
                            </div>
                        </td>
                        <td>
                            <i class="fas fa-cube text-muted me-1"></i>
                            <?= e($combo['combination_label']) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="text-muted mt-1" style="font-size:0.65rem;">
            <i class="fas fa-info-circle me-1"></i>
            Desative combinações que não devem estar disponíveis. Produtos herdarão este estado.
        </p>
    </div>
    <?php endif; ?>
</div>

<script>
(function() {
    const prefix = '<?= $prefix ?>';
    const fieldName = '<?= $fieldName ?>';
    const container = document.getElementById(prefix + '-container');
    const selector = document.getElementById(prefix + '-type-selector');
    const btnAdd = document.getElementById(prefix + '-btn-add');
    const countBadge = document.getElementById(prefix + '-count-badge');
    
    if (!container || !selector || !btnAdd) return;

    let gradeIndex = <?= count($entityGrades ?? []) ?>;

    selector.addEventListener('change', function() {
        btnAdd.disabled = !this.value;
    });

    btnAdd.addEventListener('click', function() {
        const typeId = selector.value;
        if (!typeId) return;
        const opt = selector.options[selector.selectedIndex];
        const typeName = opt.dataset.name;
        const typeIcon = opt.dataset.icon || 'fas fa-th';

        addGradeCard(typeId, typeName, typeIcon);
        opt.disabled = true;
        opt.textContent = typeName + ' (usada)';
        selector.value = '';
        btnAdd.disabled = true;
        updateCount();
    });

    function addGradeCard(typeId, typeName, typeIcon) {
        const idx = gradeIndex++;
        const card = document.createElement('div');
        card.className = prefix + '-item card border mb-2';
        card.dataset.gradeIndex = idx;
        card.innerHTML = `
            <div class="card-header bg-light d-flex align-items-center justify-content-between py-1 px-2" style="font-size:0.8rem;">
                <div class="d-flex align-items-center">
                    <i class="${typeIcon} me-1 text-info" style="font-size:0.75rem;"></i>
                    <span class="fw-bold ${prefix}-type-label">${typeName}</span>
                    <input type="hidden" name="${fieldName}[${idx}][grade_type_id]" value="${typeId}">
                    <input type="hidden" name="${fieldName}[${idx}][type_name]" value="${typeName}">
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1 ${prefix}-remove-grade" title="Remover" style="font-size:0.65rem;">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
            <div class="card-body p-2">
                <div class="${prefix}-values-container d-flex flex-wrap gap-1 mb-1">
                    <div class="input-group input-group-sm ${prefix}-value-item" style="width:auto; max-width:140px;">
                        <input type="text" class="form-control form-control-sm ${prefix}-value-input"
                               name="${fieldName}[${idx}][values][]"
                               placeholder="Ex: P" required style="min-width:60px; font-size:0.75rem;">
                        <button type="button" class="btn btn-outline-danger btn-sm ${prefix}-remove-value" title="Remover" style="font-size:0.6rem;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-info ${prefix}-add-value" style="font-size:0.7rem; padding:1px 6px;">
                    <i class="fas fa-plus me-1"></i>Valor
                </button>
            </div>
        `;
        container.appendChild(card);
        bindCardEvents(card, idx);
        card.querySelector('.' + prefix + '-value-input')?.focus();
    }

    function bindCardEvents(card, idx) {
        // Remove grade
        card.querySelector('.' + prefix + '-remove-grade').addEventListener('click', function() {
            const typeName = card.querySelector('.' + prefix + '-type-label').textContent;
            const typeId = card.querySelector('input[name*="grade_type_id"]').value;
            if (confirm('Remover a grade "' + typeName + '"?')) {
                const opt = selector.querySelector('option[value="' + typeId + '"]');
                if (opt) {
                    opt.disabled = false;
                    opt.textContent = opt.dataset.name;
                }
                card.remove();
                updateCount();
            }
        });

        // Add value
        card.querySelector('.' + prefix + '-add-value').addEventListener('click', function() {
            const vc = card.querySelector('.' + prefix + '-values-container');
            const item = document.createElement('div');
            item.className = 'input-group input-group-sm ' + prefix + '-value-item';
            item.style.width = 'auto';
            item.style.maxWidth = '140px';
            item.innerHTML = `
                <input type="text" class="form-control form-control-sm ${prefix}-value-input"
                       name="${fieldName}[${idx}][values][]"
                       placeholder="Valor" required style="min-width:60px; font-size:0.75rem;">
                <button type="button" class="btn btn-outline-danger btn-sm ${prefix}-remove-value" title="Remover" style="font-size:0.6rem;">
                    <i class="fas fa-times"></i>
                </button>
            `;
            vc.appendChild(item);
            item.querySelector('.' + prefix + '-value-input').focus();
            item.querySelector('.' + prefix + '-remove-value').addEventListener('click', function() {
                if (vc.querySelectorAll('.' + prefix + '-value-item').length > 1) {
                    item.remove();
                }
            });
        });

        // Remove value buttons
        card.querySelectorAll('.' + prefix + '-remove-value').forEach(btn => {
            btn.addEventListener('click', function() {
                const vc = card.querySelector('.' + prefix + '-values-container');
                if (vc.querySelectorAll('.' + prefix + '-value-item').length > 1) {
                    this.closest('.' + prefix + '-value-item').remove();
                }
            });
        });
    }

    function updateCount() {
        const count = container.querySelectorAll('.' + prefix + '-item').length;
        countBadge.textContent = count + ' grade(s)';
    }

    // Bind existing cards
    container.querySelectorAll('.' + prefix + '-item').forEach(card => {
        bindCardEvents(card, parseInt(card.dataset.gradeIndex));
    });

    // Combination toggle
    document.querySelectorAll('.' + prefix + '-combo-toggle').forEach(toggle => {
        toggle.addEventListener('change', function() {
            const key = this.dataset.comboKey;
            const hiddenInput = this.closest('tr').querySelector('input[type="hidden"]');
            const row = this.closest('tr');
            if (this.checked) {
                hiddenInput.value = '1';
                row.classList.remove('table-danger', 'text-decoration-line-through');
            } else {
                hiddenInput.value = '0';
                row.classList.add('table-danger', 'text-decoration-line-through');
            }
        });
    });
})();
</script>
