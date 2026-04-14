<?php
/**
 * ESG — Dashboard
 * Variáveis: $summary, $metrics, $targets
 */
$catIcons = ['environmental' => 'fas fa-tree', 'social' => 'fas fa-users', 'governance' => 'fas fa-balance-scale'];
$catColors = ['environmental' => 'success', 'social' => 'info', 'governance' => 'primary'];
$catLabels = ['environmental' => 'Ambiental', 'social' => 'Social', 'governance' => 'Governança'];
?>
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div><h1 class="h2 mb-1"><i class="fas fa-chart-pie me-2 text-success"></i>Dashboard ESG</h1></div>
        <a href="?page=esg" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
    </div>

    <div class="row g-3 mb-4">
        <?php foreach ($summary ?? [] as $s): ?>
        <div class="col-sm-6 col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="<?= $catIcons[$s['category']] ?? 'fas fa-chart-bar' ?> fa-2x text-<?= $catColors[$s['category']] ?? 'secondary' ?> mb-2"></i>
                    <h5><?= $catLabels[$s['category']] ?? e($s['category']) ?></h5>
                    <h3 class="mb-0"><?= e(number_format((float) ($s['total_value'] ?? 0), 2, ',', '.')) ?></h3>
                    <small class="text-muted"><?= (int) ($s['record_count'] ?? 0) ?> registros (12 meses)</small>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Metas -->
    <?php if (!empty($targets)): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white"><strong>Metas <?= date('Y') ?></strong></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>Métrica</th><th>Categoria</th><th>Ano</th><th>Meta</th><th>Descrição</th></tr></thead>
                    <tbody>
                    <?php foreach ($targets as $t): ?>
                        <tr>
                            <td><?= e($t['metric_name'] ?? '-') ?></td>
                            <td><span class="badge bg-<?= $catColors[$t['category'] ?? ''] ?? 'secondary' ?>"><?= $catLabels[$t['category'] ?? ''] ?? '-' ?></span></td>
                            <td><?= (int) $t['year'] ?></td>
                            <td><strong><?= e(number_format((float) $t['target_value'], 2, ',', '.')) ?></strong></td>
                            <td><?= e($t['description'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
