<?php
/**
 * Entregas — Dashboard
 * Variáveis: $stats
 */
?>
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div><h1 class="h2 mb-1"><i class="fas fa-chart-pie me-2 text-primary"></i>Dashboard de Entregas</h1></div>
        <a href="?page=shipments" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
    </div>

    <div class="row g-3 mb-4">
        <?php
        $cards = [
            ['label' => 'Preparando', 'value' => $stats['preparing'] ?? 0, 'icon' => 'fas fa-box', 'color' => 'warning'],
            ['label' => 'Em Trânsito', 'value' => $stats['in_transit'] ?? 0, 'icon' => 'fas fa-truck', 'color' => 'info'],
            ['label' => 'Entregues (30d)', 'value' => $stats['delivered'] ?? 0, 'icon' => 'fas fa-check-circle', 'color' => 'success'],
            ['label' => 'Devolvidas (30d)', 'value' => $stats['returned'] ?? 0, 'icon' => 'fas fa-undo', 'color' => 'danger'],
        ];
        foreach ($cards as $c): ?>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body py-3">
                    <i class="<?= $c['icon'] ?> fa-2x text-<?= $c['color'] ?> mb-2"></i>
                    <h3 class="mb-0"><?= (int) $c['value'] ?></h3>
                    <small class="text-muted"><?= $c['label'] ?></small>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
