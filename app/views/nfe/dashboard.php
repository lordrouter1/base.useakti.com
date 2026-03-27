<?php
/**
 * View: Dashboard Fiscal Avançado
 * KPIs, gráficos, alertas e tabelas para visão gerencial do módulo NF-e.
 *
 * @var array  $kpis          KPIs últimos 12 meses
 * @var array  $kpisMonth     KPIs mês atual
 * @var array  $nfesByMonth   NF-e por mês (gráfico de barras)
 * @var array  $statusDist    Distribuição por status (gráfico de pizza)
 * @var array  $topCfops      Top 5 CFOPs
 * @var array  $topCustomers  Top 5 clientes
 * @var array  $totalTaxes    Totais de impostos (12 meses)
 * @var array  $alerts        Alertas fiscais
 * @var float  $taxaRejeicao  Taxa de rejeição (%)
 * @var array  $statusColors  Cores por status
 * @var array  $statusLabels  Labels por status
 */
$pageTitle = 'Dashboard Fiscal';
$isAjax = $isAjax ?? false;
?>

<?php if (!$isAjax): ?>
<div class="container py-4">

    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center pt-2 pb-2 mb-3 border-bottom">
        <div>
            <h1 class="h2 mb-0"><i class="fas fa-chart-bar me-2 text-primary"></i> Dashboard Fiscal</h1>
            <small class="text-muted">Visão gerencial do módulo NF-e/NFC-e</small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="?page=nfe_documents" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-file-invoice me-1"></i> Listar NF-e
            </a>
            <a href="?page=nfe_documents&action=correctionReport" class="btn btn-outline-info btn-sm">
                <i class="fas fa-edit me-1"></i> Relatório CC-e
            </a>
            <a href="?page=nfe_documents&sec=credenciais" class="btn btn-outline-success btn-sm">
                <i class="fas fa-certificate me-1"></i> Credenciais
            </a>
            <!-- Exportações (FASE4-03) -->
            <div class="dropdown">
                <button class="btn btn-success btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-file-excel me-1"></i> Exportar
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="?page=nfe_documents&action=exportReport&type=nfes&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>">
                        <i class="fas fa-file-invoice me-2 text-primary"></i>NF-e Emitidas
                    </a></li>
                    <li><a class="dropdown-item" href="?page=nfe_documents&action=exportReport&type=taxes&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>">
                        <i class="fas fa-calculator me-2 text-warning"></i>Resumo de Impostos
                    </a></li>
                    <li><a class="dropdown-item" href="?page=nfe_documents&action=exportReport&type=cfop&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>">
                        <i class="fas fa-tags me-2 text-info"></i>Resumo por CFOP
                    </a></li>
                    <li><a class="dropdown-item" href="?page=nfe_documents&action=exportReport&type=cancelled&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>">
                        <i class="fas fa-ban me-2 text-danger"></i>NF-e Canceladas
                    </a></li>
                    <li><a class="dropdown-item" href="?page=nfe_documents&action=exportReport&type=corrections&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>">
                        <i class="fas fa-edit me-2 text-secondary"></i>Cartas de Correção
                    </a></li>
                </ul>
            </div>
        </div>
    </div>
<?php endif; ?>

    <!-- ═══ Alertas Fiscais ═══ -->
    <?php if (!empty($alerts)): ?>
    <div class="mb-4">
        <?php foreach ($alerts as $alert): ?>
        <div class="alert alert-<?= $alert['severity'] ?> alert-dismissible fade show py-2 mb-2" style="font-size: 0.85rem;">
            <i class="fas fa-<?= $alert['severity'] === 'danger' ? 'exclamation-circle' : ($alert['severity'] === 'warning' ? 'exclamation-triangle' : 'info-circle') ?> me-2"></i>
            <strong><?= e($alert['title']) ?>:</strong> <?= e($alert['message']) ?>
            <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" style="font-size: 0.65rem;"></button>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- ═══ KPIs Principais — Mês Atual ═══ -->
    <div class="row g-3 mb-4">
        <div class="col-md-2 col-6">
            <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);">
                <div class="card-body text-center py-3">
                    <div class="text-primary mb-1"><i class="fas fa-file-invoice fa-2x"></i></div>
                    <h3 class="mb-0"><?= (int)($kpisMonth['total_emitidas'] ?? 0) ?></h3>
                    <small class="text-muted" style="font-size: 0.7rem;">Emitidas (Mês)</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);">
                <div class="card-body text-center py-3">
                    <div class="text-success mb-1"><i class="fas fa-check-circle fa-2x"></i></div>
                    <h3 class="mb-0"><?= (int)($kpisMonth['autorizadas'] ?? 0) ?></h3>
                    <small class="text-muted" style="font-size: 0.7rem;">Autorizadas (Mês)</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);">
                <div class="card-body text-center py-3">
                    <div class="text-warning mb-1"><i class="fas fa-coins fa-2x"></i></div>
                    <h3 class="mb-0 fs-5">R$ <?= number_format((float)($kpisMonth['valor_autorizado'] ?? 0), 0, ',', '.') ?></h3>
                    <small class="text-muted" style="font-size: 0.7rem;">Faturamento (Mês)</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #fce4ec 0%, #f8bbd0 100%);">
                <div class="card-body text-center py-3">
                    <div class="text-danger mb-1"><i class="fas fa-times-circle fa-2x"></i></div>
                    <h3 class="mb-0"><?= (int)($kpisMonth['rejeitadas'] ?? 0) + (int)($kpisMonth['canceladas'] ?? 0) ?></h3>
                    <small class="text-muted" style="font-size: 0.7rem;">Rejeit./Canc. (Mês)</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #f3e5f5 0%, #e1bee7 100%);">
                <div class="card-body text-center py-3">
                    <div class="text-purple mb-1" style="color: #7b1fa2;"><i class="fas fa-calculator fa-2x"></i></div>
                    <h3 class="mb-0 fs-5">R$ <?= number_format((float)($kpisMonth['ticket_medio'] ?? 0), 0, ',', '.') ?></h3>
                    <small class="text-muted" style="font-size: 0.7rem;">Ticket Médio</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, <?= $taxaRejeicao >= 10 ? '#fce4ec' : '#e8f5e9' ?> 0%, <?= $taxaRejeicao >= 10 ? '#f8bbd0' : '#c8e6c9' ?> 100%);">
                <div class="card-body text-center py-3">
                    <div class="<?= $taxaRejeicao >= 10 ? 'text-danger' : 'text-success' ?> mb-1"><i class="fas fa-chart-pie fa-2x"></i></div>
                    <h3 class="mb-0"><?= $taxaRejeicao ?>%</h3>
                    <small class="text-muted" style="font-size: 0.7rem;">Taxa Rejeição</small>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ Gráficos ═══ -->
    <div class="row g-3 mb-4">
        <!-- Gráfico de Barras: NF-e por Mês -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-2">
                    <h6 class="mb-0"><i class="fas fa-chart-bar me-2 text-primary"></i> NF-e por Mês (Últimos 12 Meses)</h6>
                </div>
                <div class="card-body">
                    <canvas id="chartNfeByMonth" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Gráfico de Pizza: Distribuição por Status -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-2">
                    <h6 class="mb-0"><i class="fas fa-chart-pie me-2 text-info"></i> Distribuição por Status</h6>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <canvas id="chartStatusDist" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ Totais de Impostos (12 meses) ═══ -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body py-3">
                    <small class="text-muted d-block" style="font-size:0.7rem;">ICMS (12m)</small>
                    <span class="fw-bold text-primary">R$ <?= number_format((float)($totalTaxes['total_icms'] ?? 0), 2, ',', '.') ?></span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body py-3">
                    <small class="text-muted d-block" style="font-size:0.7rem;">PIS (12m)</small>
                    <span class="fw-bold text-success">R$ <?= number_format((float)($totalTaxes['total_pis'] ?? 0), 2, ',', '.') ?></span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body py-3">
                    <small class="text-muted d-block" style="font-size:0.7rem;">COFINS (12m)</small>
                    <span class="fw-bold text-warning">R$ <?= number_format((float)($totalTaxes['total_cofins'] ?? 0), 2, ',', '.') ?></span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body py-3">
                    <small class="text-muted d-block" style="font-size:0.7rem;">IPI (12m)</small>
                    <span class="fw-bold text-danger">R$ <?= number_format((float)($totalTaxes['total_ipi'] ?? 0), 2, ',', '.') ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ Tabelas: Top CFOPs e Top Clientes ═══ -->
    <div class="row g-3 mb-4">
        <!-- Top 5 CFOPs -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-2">
                    <h6 class="mb-0"><i class="fas fa-list-ol me-2 text-success"></i> Top 5 CFOPs</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>CFOP</th>
                                <th>Descrição</th>
                                <th class="text-end">Itens</th>
                                <th class="text-end">Valor Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($topCfops)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-3">Nenhum dado disponível.</td></tr>
                            <?php else: ?>
                            <?php foreach ($topCfops as $cfop): ?>
                            <tr>
                                <td><span class="badge bg-light text-dark"><?= e($cfop['cfop']) ?></span></td>
                                <td class="small"><?= e(\Akti\Models\NfeReportModel::getCfopDescription($cfop['cfop'])) ?></td>
                                <td class="text-end"><?= (int)$cfop['qtd_itens'] ?></td>
                                <td class="text-end">R$ <?= number_format((float)$cfop['valor_total'], 2, ',', '.') ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Top 5 Clientes -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-2">
                    <h6 class="mb-0"><i class="fas fa-users me-2 text-warning"></i> Top 5 Clientes (Faturamento)</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Cliente</th>
                                <th class="text-end">NF-e</th>
                                <th class="text-end">Valor Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($topCustomers)): ?>
                            <tr><td colspan="3" class="text-center text-muted py-3">Nenhum dado disponível.</td></tr>
                            <?php else: ?>
                            <?php foreach ($topCustomers as $cust): ?>
                            <tr>
                                <td><?= e($cust['customer_name']) ?></td>
                                <td class="text-end"><?= (int)$cust['total_nfes'] ?></td>
                                <td class="text-end fw-bold">R$ <?= number_format((float)$cust['valor_total'], 2, ',', '.') ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ KPIs Acumulados (12 Meses) ═══ -->
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-2">
                    <h6 class="mb-0"><i class="fas fa-tachometer-alt me-2 text-dark"></i> Resumo Acumulado (Últimos 12 Meses)</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3 text-center">
                        <div class="col-md-2 col-4">
                            <span class="d-block fw-bold fs-4"><?= (int)($kpis['total_emitidas'] ?? 0) ?></span>
                            <small class="text-muted" style="font-size:0.7rem;">Total Emitidas</small>
                        </div>
                        <div class="col-md-2 col-4">
                            <span class="d-block fw-bold fs-4 text-success"><?= (int)($kpis['autorizadas'] ?? 0) ?></span>
                            <small class="text-muted" style="font-size:0.7rem;">Autorizadas</small>
                        </div>
                        <div class="col-md-2 col-4">
                            <span class="d-block fw-bold fs-4 text-danger"><?= (int)($kpis['canceladas'] ?? 0) ?></span>
                            <small class="text-muted" style="font-size:0.7rem;">Canceladas</small>
                        </div>
                        <div class="col-md-2 col-4">
                            <span class="d-block fw-bold fs-5 text-success">R$ <?= number_format((float)($kpis['valor_autorizado'] ?? 0), 2, ',', '.') ?></span>
                            <small class="text-muted" style="font-size:0.7rem;">Valor Autorizado</small>
                        </div>
                        <div class="col-md-2 col-4">
                            <span class="d-block fw-bold fs-5 text-dark">R$ <?= number_format((float)($kpis['valor_cancelado'] ?? 0), 2, ',', '.') ?></span>
                            <small class="text-muted" style="font-size:0.7rem;">Valor Cancelado</small>
                        </div>
                        <div class="col-md-2 col-4">
                            <span class="d-block fw-bold fs-5 text-info">
                                <?= (int)($kpis['nfe_count'] ?? 0) ?> / <?= (int)($kpis['nfce_count'] ?? 0) ?>
                            </span>
                            <small class="text-muted" style="font-size:0.7rem;">NF-e / NFC-e</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php if (!$isAjax): ?>
</div>
<?php endif; ?>

<!-- Chart.js: carregamento dinâmico para compatibilidade com AJAX -->
<script>
(function(){
    function initDashboardCharts(){
    // ══ Gráfico de Barras: NF-e por Mês ══
    var monthData = <?= json_encode($nfesByMonth) ?>;
    var labels = monthData.map(function(d){ return d.month_label; });
    var dataAuth = monthData.map(function(d){ return parseInt(d.autorizadas || 0); });
    var dataCanc = monthData.map(function(d){ return parseInt(d.canceladas || 0); });
    var dataRej  = monthData.map(function(d){ return parseInt(d.rejeitadas || 0); });

    if (document.getElementById('chartNfeByMonth')) {
        new Chart(document.getElementById('chartNfeByMonth'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Autorizadas',
                        data: dataAuth,
                        backgroundColor: 'rgba(40, 167, 69, 0.8)',
                        borderRadius: 4,
                    },
                    {
                        label: 'Canceladas',
                        data: dataCanc,
                        backgroundColor: 'rgba(52, 58, 64, 0.7)',
                        borderRadius: 4,
                    },
                    {
                        label: 'Rejeitadas',
                        data: dataRej,
                        backgroundColor: 'rgba(220, 53, 69, 0.7)',
                        borderRadius: 4,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top', labels: { font: { size: 11 } } },
                    tooltip: {
                        callbacks: {
                            afterBody: function(context) {
                                var idx = context[0].dataIndex;
                                var val = monthData[idx] ? parseFloat(monthData[idx].valor || 0) : 0;
                                return 'Valor: R$ ' + val.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
                            }
                        }
                    }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 10 } } },
                    x: { ticks: { font: { size: 10 } } }
                }
            }
        });
    }

    // ══ Gráfico de Pizza: Distribuição por Status ══
    var statusData = <?= json_encode($statusDist) ?>;
    var statusColors = <?= json_encode($statusColors) ?>;
    var statusLabelsMap = <?= json_encode($statusLabels) ?>;
    var pieLabels = statusData.map(function(d){ return statusLabelsMap[d.status] || d.status; });
    var pieData = statusData.map(function(d){ return parseInt(d.count || 0); });
    var pieColors = statusData.map(function(d){ return statusColors[d.status] || '#adb5bd'; });

    if (document.getElementById('chartStatusDist')) {
        new Chart(document.getElementById('chartStatusDist'), {
            type: 'doughnut',
            data: {
                labels: pieLabels,
                datasets: [{
                    data: pieData,
                    backgroundColor: pieColors,
                    borderWidth: 2,
                    borderColor: '#fff',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { font: { size: 10 }, padding: 8 } },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                var total = ctx.dataset.data.reduce(function(a, b){ return a + b; }, 0);
                                var pct = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) : 0;
                                var val = statusData[ctx.dataIndex] ? parseFloat(statusData[ctx.dataIndex].valor || 0) : 0;
                                return ctx.label + ': ' + ctx.parsed + ' (' + pct + '%) — R$ ' + val.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
                            }
                        }
                    }
                }
            }
        });
    }
    } // end initDashboardCharts

    // Carregar Chart.js dinamicamente se ainda não estiver disponível
    function loadChartJs(callback) {
        if (typeof Chart !== 'undefined') {
            callback();
            return;
        }
        var script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js';
        script.onload = callback;
        script.onerror = function(){ console.error('Falha ao carregar Chart.js'); };
        document.head.appendChild(script);
    }

    // Executar quando DOM e Chart.js estiverem prontos
    function run() {
        loadChartJs(initDashboardCharts);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run);
    } else {
        run();
    }
})();
</script>
