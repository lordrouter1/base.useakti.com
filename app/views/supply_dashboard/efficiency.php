<?php include __DIR__ . '/../layout/header.php'; ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="fas fa-chart-line me-2"></i><?= e($pageTitle) ?></h1>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body py-2">
            <form method="get" class="row g-2 align-items-end">
                <input type="hidden" name="page" value="supply_dashboard">
                <input type="hidden" name="action" value="efficiency">
                <div class="col-md-3">
                    <label class="form-label form-label-sm mb-0">Produto</label>
                    <select name="product_id" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?= eAttr($p['id']) ?>" <?= ($filters['product_id'] ?? '') == $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-0">Data Início</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="<?= eAttr($filters['date_from'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-0">Data Fim</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="<?= eAttr($filters['date_to'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter me-1"></i>Filtrar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- KPIs -->
    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="card border-start border-primary border-4">
                <div class="card-body py-3">
                    <div class="text-muted small">Eficiência Global</div>
                    <div class="h4 mb-0"><?= number_format($kpis['efficiency_percent'] ?? 100, 1) ?>%</div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card border-start border-warning border-4">
                <div class="card-body py-3">
                    <div class="text-muted small">Variação Média</div>
                    <div class="h4 mb-0"><?= number_format($kpis['avg_variance_percent'] ?? 0, 1) ?>%</div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card border-start border-danger border-4">
                <div class="card-body py-3">
                    <div class="text-muted small">Custo de Perda</div>
                    <div class="h4 mb-0">R$ <?= number_format($kpis['waste_cost'] ?? 0, 2, ',', '.') ?></div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card border-start border-success border-4">
                <div class="card-body py-3">
                    <div class="text-muted small">Ordens no Período</div>
                    <div class="h4 mb-0"><?= eNum($kpis['total_orders'] ?? 0) ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Gráfico Previsto vs Real -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><i class="fas fa-chart-bar me-2"></i>Previsto vs Real (por dia)</div>
                <div class="card-body">
                    <canvas id="chartEfficiency" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Top 10 Desperdício -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><i class="fas fa-exclamation-triangle me-2"></i>Top 10 Desperdício</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Insumo</th>
                                    <th class="text-end">Perda (R$)</th>
                                    <th class="text-end">Var %</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($top_waste)): ?>
                                    <tr><td colspan="3" class="text-center text-muted py-3">Sem dados</td></tr>
                                <?php else: ?>
                                    <?php foreach ($top_waste as $w): ?>
                                        <tr>
                                            <td><?= e($w['supply_name']) ?></td>
                                            <td class="text-end text-danger">R$ <?= number_format($w['waste_cost'], 2, ',', '.') ?></td>
                                            <td class="text-end"><?= number_format($w['avg_variance_percent'], 1) ?>%</td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const chartData = <?= json_encode($chart) ?>;
    const labels = chartData.map(r => r.day);
    const planned = chartData.map(r => parseFloat(r.planned));
    const actual = chartData.map(r => parseFloat(r.actual));

    if (labels.length > 0) {
        new Chart(document.getElementById('chartEfficiency'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    { label: 'Previsto', data: planned, backgroundColor: 'rgba(54,162,235,0.6)' },
                    { label: 'Real', data: actual, backgroundColor: 'rgba(255,99,132,0.6)' }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true } }
            }
        });
    }
});
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?>
