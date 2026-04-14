<?php
/**
 * Equipamentos — Dashboard de manutenção
 * Variáveis: $stats, $upcoming
 */
?>
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div>
            <h1 class="h2 mb-1"><i class="fas fa-chart-pie me-2 text-primary"></i>Dashboard de Manutenção</h1>
        </div>
        <a href="?page=equipment" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
    </div>

    <div class="row g-3 mb-4">
        <?php
        $cards = [
            ['label' => 'Total Equipamentos', 'value' => $stats['total'] ?? 0, 'icon' => 'fas fa-cogs', 'color' => 'primary'],
            ['label' => 'Ativos', 'value' => $stats['active'] ?? 0, 'icon' => 'fas fa-check-circle', 'color' => 'success'],
            ['label' => 'Em Manutenção', 'value' => $stats['maintenance'] ?? 0, 'icon' => 'fas fa-wrench', 'color' => 'warning'],
            ['label' => 'Próximas 7 dias', 'value' => $stats['upcoming_7_days'] ?? 0, 'icon' => 'fas fa-calendar-alt', 'color' => 'info'],
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

    <?php if (!empty($upcoming)): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white"><strong>Manutenções Próximas (14 dias)</strong></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Equipamento</th><th>Tipo</th><th>Descrição</th><th>Data Prevista</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($upcoming as $u): ?>
                        <tr>
                            <td><?= e($u['equipment_name'] ?? '-') ?></td>
                            <td><span class="badge bg-info"><?= e($u['maintenance_type']) ?></span></td>
                            <td><?= e($u['description']) ?></td>
                            <td><?= e(date('d/m/Y', strtotime($u['next_due_date']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
