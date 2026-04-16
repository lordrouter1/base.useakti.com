<?php
/**
 * Previsão de Ruptura de Estoque
 * Variáveis: $kpis, $forecasts
 */
$csrfToken = csrf_token();
?>

<div class="container-fluid py-3">
    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div>
            <h1 class="h2 mb-1">
                <i class="fas fa-chart-area me-2 text-info"></i>Previsão de Ruptura
            </h1>
        </div>
        <div>
            <button class="btn btn-sm btn-primary" id="btnRecalculate"><i class="fas fa-sync me-1"></i>Recalcular</button>
            <a href="?page=supply_stock" class="btn btn-sm btn-outline-secondary ms-1"><i class="fas fa-arrow-left me-1"></i>Estoque</a>
        </div>
    </div>

    <!-- KPIs -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 border-start border-danger border-4">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="icon-circle icon-circle-lg bg-danger bg-opacity-10 text-danger me-3">
                        <i class="fas fa-times-circle fa-lg"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">Em Ruptura</small>
                        <span class="fw-bold fs-5"><?= (int)($kpis['ruptured'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 border-start border-warning border-4">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="icon-circle icon-circle-lg bg-warning bg-opacity-10 text-warning me-3">
                        <i class="fas fa-exclamation-triangle fa-lg"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">Crítico (≤ 7 dias)</small>
                        <span class="fw-bold fs-5"><?= (int)($kpis['critical'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 border-start border-info border-4">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="icon-circle icon-circle-lg bg-info bg-opacity-10 text-info me-3">
                        <i class="fas fa-exclamation-circle fa-lg"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">Atenção (≤ 30 dias)</small>
                        <span class="fw-bold fs-5"><?= (int)($kpis['warning'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="icon-circle icon-circle-lg bg-success bg-opacity-10 text-success me-3">
                        <i class="fas fa-check-circle fa-lg"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">Normal</small>
                        <span class="fw-bold fs-5"><?= (int)($kpis['ok'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label form-label-sm">Status</label>
                    <select id="filterStatus" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="ruptured">Em Ruptura</option>
                        <option value="critical">Crítico</option>
                        <option value="warning">Atenção</option>
                        <option value="ok">Normal</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label form-label-sm">Buscar</label>
                    <input type="text" id="filterSearch" class="form-control form-control-sm" placeholder="Nome ou código...">
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="forecastTable">
                    <thead class="table-light">
                        <tr>
                            <th>Código</th>
                            <th>Insumo</th>
                            <th class="text-end">Estoque Atual</th>
                            <th class="text-end">Comprometido</th>
                            <th class="text-end">Disponível</th>
                            <th class="text-center">Dias p/ Ruptura</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($forecasts)): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">Nenhuma previsão calculada. Clique em "Recalcular".</td></tr>
                        <?php else: ?>
                        <?php foreach ($forecasts as $f): ?>
                        <?php
                            $statusClass = ['ruptured' => 'danger', 'critical' => 'warning', 'warning' => 'info', 'ok' => 'success'];
                            $statusLabel = ['ruptured' => 'Ruptura', 'critical' => 'Crítico', 'warning' => 'Atenção', 'ok' => 'Normal'];
                            $st = $f['status'];
                            $available = (float)$f['current_stock'] - (float)$f['committed_quantity'];
                        ?>
                        <tr class="forecast-row" data-status="<?= e($st) ?>" data-name="<?= eAttr($f['supply_name'] ?? '') ?>" data-code="<?= eAttr($f['supply_code'] ?? '') ?>">
                            <td><span class="badge bg-light text-dark"><?= e($f['supply_code'] ?? '') ?></span></td>
                            <td><?= e($f['supply_name'] ?? '') ?></td>
                            <td class="text-end"><?= number_format((float)$f['current_stock'], 4, ',', '.') ?> <?= e($f['unit_measure'] ?? '') ?></td>
                            <td class="text-end"><?= number_format((float)$f['committed_quantity'], 4, ',', '.') ?></td>
                            <td class="text-end <?= $available <= 0 ? 'text-danger fw-bold' : '' ?>"><?= number_format($available, 4, ',', '.') ?></td>
                            <td class="text-center">
                                <?php if ($f['days_to_rupture'] !== null): ?>
                                <span class="badge bg-<?= $statusClass[$st] ?? 'secondary' ?>"><?= (int)$f['days_to_rupture'] ?> dias</span>
                                <?php else: ?>
                                <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center"><span class="badge bg-<?= $statusClass[$st] ?? 'secondary' ?>"><?= $statusLabel[$st] ?? $st ?></span></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-info btnDetail" data-id="<?= (int)$f['supply_id'] ?>"><i class="fas fa-eye"></i></button>
                            </td>
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
    const csrfToken = '<?= $csrfToken ?>';

    // Recalcular
    document.getElementById('btnRecalculate')?.addEventListener('click', function() {
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Calculando...';
        fetch('?page=supply_stock&action=recalculateForecasts', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': csrfToken},
            body: 'csrf_token=' + csrfToken
        }).then(r => r.json()).then(r => {
            if (r.success) {
                if (typeof AktiToast !== 'undefined') AktiToast.success(r.message || 'Previsões recalculadas.');
                setTimeout(() => location.reload(), 500);
            } else {
                if (typeof AktiToast !== 'undefined') AktiToast.error(r.message || 'Erro.');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-sync me-1"></i>Recalcular';
            }
        });
    });

    // Filtros
    function applyFilters() {
        const status = document.getElementById('filterStatus').value.toLowerCase();
        const search = document.getElementById('filterSearch').value.toLowerCase();
        document.querySelectorAll('.forecast-row').forEach(row => {
            const rowStatus = row.dataset.status;
            const rowName = (row.dataset.name || '').toLowerCase();
            const rowCode = (row.dataset.code || '').toLowerCase();
            const matchStatus = !status || rowStatus === status;
            const matchSearch = !search || rowName.includes(search) || rowCode.includes(search);
            row.style.display = (matchStatus && matchSearch) ? '' : 'none';
        });
    }
    document.getElementById('filterStatus')?.addEventListener('change', applyFilters);
    document.getElementById('filterSearch')?.addEventListener('input', applyFilters);

    // Detalhe
    document.querySelectorAll('.btnDetail').forEach(btn => {
        btn.addEventListener('click', function() {
            const supplyId = this.dataset.id;
            fetch('?page=supply_stock&action=getForecastDetail&supply_id=' + supplyId)
                .then(r => r.json())
                .then(data => {
                    let html = '<div class="text-start">';
                    if (data.forecast) {
                        html += '<p><strong>Estoque:</strong> ' + parseFloat(data.forecast.current_stock).toFixed(4) + '</p>';
                        html += '<p><strong>Comprometido:</strong> ' + parseFloat(data.forecast.committed_quantity).toFixed(4) + '</p>';
                        html += '<p><strong>Dias p/ Ruptura:</strong> ' + (data.forecast.days_to_rupture ?? '—') + '</p>';
                    }
                    if (data.demand_products && data.demand_products.length) {
                        html += '<hr><h6>Produtos que demandam este insumo:</h6><ul>';
                        data.demand_products.forEach(p => {
                            html += '<li>' + p.product_name + ' (ratio: ' + parseFloat(p.ratio).toFixed(4) + ')</li>';
                        });
                        html += '</ul>';
                    }
                    html += '</div>';
                    Swal.fire({title: 'Detalhe da Previsão', html: html, width: '600px', showCloseButton: true, showConfirmButton: false});
                });
        });
    });
});
</script>
