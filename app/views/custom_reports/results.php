<?php
/**
 * Relatórios Customizados — Resultados
 * FEAT-008
 * Variáveis: $template, $reportData, $entities
 */
$cols = !empty($template['columns']) ? (is_string($template['columns']) ? json_decode($template['columns'], true) : $template['columns']) : [];
$columnDict = ($entities[$template['entity'] ?? '']['columns'] ?? []);
?>

<div class="container-fluid py-3">

    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div>
            <h1 class="h2 mb-1"><i class="fas fa-table me-2 text-primary"></i><?= e($template['name'] ?? 'Relatório') ?></h1>
            <p class="text-muted mb-0" style="font-size:.82rem;">Entidade: <?= e($template['entity'] ?? '-') ?> | <?= count($reportData) ?> registro(s)</p>
        </div>
        <div class="btn-toolbar gap-2">
            <button class="btn btn-sm btn-outline-success" id="btnExportCsv"><i class="fas fa-download me-1"></i>CSV</button>
            <button class="btn btn-sm btn-outline-primary" onclick="window.print()"><i class="fas fa-print me-1"></i>Imprimir</button>
            <a href="?page=custom_reports&action=edit&id=<?= (int) $template['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-edit me-1"></i>Editar</a>
            <a href="?page=custom_reports" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0" id="reportTable">
                    <thead class="table-light">
                        <tr>
                            <?php foreach ($cols as $col): ?>
                            <th>
                                <?= e($columnDict[$col]['label'] ?? $col) ?>
                                <?php if (!empty($columnDict[$col]['description'])): ?>
                                <i class="fas fa-info-circle text-muted ms-1" style="cursor:help;font-size:.75rem" data-bs-toggle="tooltip" title="<?= eAttr($columnDict[$col]['description']) ?>"></i>
                                <?php endif; ?>
                            </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($reportData)): ?>
                        <tr><td colspan="<?= count($cols) ?>" class="text-center text-muted py-4">Sem dados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($reportData as $row): ?>
                        <tr>
                            <?php foreach ($cols as $col): ?>
                            <td><?= e($row[$col] ?? '') ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltips.forEach(el => new bootstrap.Tooltip(el));
});

document.getElementById('btnExportCsv')?.addEventListener('click', function() {
    const table = document.getElementById('reportTable');
    let csv = [];
    for (const row of table.rows) {
        let cols = [];
        for (const cell of row.cells) cols.push('"' + cell.textContent.trim().replace(/"/g, '""') + '"');
        csv.push(cols.join(','));
    }
    const blob = new Blob(["\uFEFF" + csv.join("\n")], {type: 'text/csv;charset=utf-8;'});
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'relatorio_<?= (int) $template['id'] ?>.csv';
    link.click();
});
</script>
