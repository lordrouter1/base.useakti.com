<?php
/**
 * Tickets — Dashboard
 * Variáveis: $stats
 */
?>
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div>
            <h1 class="h2 mb-1"><i class="fas fa-chart-pie me-2 text-primary"></i>Dashboard de Tickets</h1>
        </div>
        <a href="?page=tickets" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
    </div>

    <div class="row g-3 mb-4">
        <?php
        $cards = [
            ['label' => 'Abertos', 'value' => $stats['open'] ?? 0, 'icon' => 'fas fa-envelope-open', 'color' => 'primary'],
            ['label' => 'Em Andamento', 'value' => $stats['in_progress'] ?? 0, 'icon' => 'fas fa-spinner', 'color' => 'info'],
            ['label' => 'Resolvidos', 'value' => $stats['resolved'] ?? 0, 'icon' => 'fas fa-check-circle', 'color' => 'success'],
            ['label' => 'Fechados', 'value' => $stats['closed'] ?? 0, 'icon' => 'fas fa-lock', 'color' => 'secondary'],
            ['label' => 'Urgentes', 'value' => $stats['urgent'] ?? 0, 'icon' => 'fas fa-exclamation-triangle', 'color' => 'danger'],
            ['label' => 'SLA Estourado', 'value' => $stats['sla_breached'] ?? 0, 'icon' => 'fas fa-clock', 'color' => 'warning'],
        ];
        foreach ($cards as $c): ?>
        <div class="col-sm-6 col-lg-2">
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
