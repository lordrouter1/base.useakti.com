<?php
/**
 * Relatórios Customizados — Formulário  
 * FEAT-008
 * Variáveis: $template (null = novo), $entities
 */
$isEdit = !empty($template);
$t = $template ?? [];
$cols = !empty($t['columns']) ? (is_string($t['columns']) ? json_decode($t['columns'], true) : $t['columns']) : [];
$filters = !empty($t['filters']) ? (is_string($t['filters']) ? json_decode($t['filters'], true) : $t['filters']) : [];
?>

<div class="container-fluid py-3">

    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div>
            <h1 class="h2 mb-1"><i class="fas fa-chart-bar me-2 text-primary"></i><?= $isEdit ? 'Editar Relatório' : 'Novo Relatório' ?></h1>
        </div>
        <a href="?page=custom_reports" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="post" action="?page=custom_reports&action=<?= $isEdit ? 'update' : 'store' ?>" id="reportForm">
                <?= csrf_field() ?>
                <?php if ($isEdit): ?>
                    <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
                <?php endif; ?>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Nome do Relatório <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="<?= eAttr($t['name'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Entidade Base <span class="text-danger">*</span></label>
                        <select name="entity" id="entitySelect" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($entities ?? [] as $key => $info): ?>
                            <option value="<?= eAttr($key) ?>" <?= ($t['entity'] ?? '') === $key ? 'selected' : '' ?>><?= e($info['label'] ?? $key) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_shared" value="1" id="isShared" <?= !empty($t['is_shared']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="isShared">Compartilhar</label>
                        </div>
                    </div>
                </div>

                <input type="hidden" name="columns" id="columnsInput" value="<?= eAttr(json_encode($cols)) ?>">
                <input type="hidden" name="filters" id="filtersInput" value="<?= eAttr(json_encode($filters)) ?>">
                <input type="hidden" name="grouping" value="<?= eAttr(is_string($t['grouping'] ?? '[]') ? ($t['grouping'] ?? '[]') : json_encode($t['grouping'] ?? [])) ?>">
                <input type="hidden" name="sorting" value="<?= eAttr(is_string($t['sorting'] ?? '[]') ? ($t['sorting'] ?? '[]') : json_encode($t['sorting'] ?? [])) ?>">

                <div class="mt-3" id="columnsPanel">
                    <h6><i class="fas fa-columns me-2"></i>Colunas</h6>
                    <p class="text-muted small">Selecione as colunas que deseja incluir no relatório.</p>
                    <div id="columnsList" class="row g-2"></div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Salvar</button>
                    <a href="?page=custom_reports" class="btn btn-outline-secondary ms-2">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const entities = <?= json_encode($entities ?? []) ?>;
    const entitySelect = document.getElementById('entitySelect');
    const columnsList = document.getElementById('columnsList');
    const selectedCols = <?= json_encode($cols) ?>;

    function loadColumns(entity) {
        columnsList.innerHTML = '';
        if (!entity || !entities[entity]) return;
        const cols = entities[entity].columns || [];
        cols.forEach(col => {
            const checked = selectedCols.includes(col) ? 'checked' : '';
            columnsList.innerHTML += `
                <div class="col-md-3">
                    <div class="form-check">
                        <input class="form-check-input colCheck" type="checkbox" value="${col}" ${checked}>
                        <label class="form-check-label">${col}</label>
                    </div>
                </div>`;
        });
    }

    entitySelect.addEventListener('change', () => loadColumns(entitySelect.value));
    if (entitySelect.value) loadColumns(entitySelect.value);

    document.getElementById('reportForm').addEventListener('submit', function() {
        const selected = [];
        document.querySelectorAll('.colCheck:checked').forEach(c => selected.push(c.value));
        document.getElementById('columnsInput').value = JSON.stringify(selected);
    });
});
</script>
