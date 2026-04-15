<?php /** @var array $salesData */ /** @var array $productionData */ /** @var array $financialData */ /** @var string $dateFrom */ /** @var string $dateTo */ /** @var string $tab */ ?>

<div class="container-fluid py-4">
    <!-- Header + Filtros de Período -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="fas fa-chart-line me-2"></i>Business Intelligence</h1>
        <form method="get" class="d-flex align-items-center gap-2">
            <input type="hidden" name="page" value="bi">
            <input type="hidden" name="tab" id="activeTab" value="<?= eAttr($tab) ?>">
            <label class="form-label mb-0 small">De:</label>
            <input type="date" name="date_from" class="form-control form-control-sm" value="<?= eAttr($dateFrom) ?>" style="width:150px">
            <label class="form-label mb-0 small">Até:</label>
            <input type="date" name="date_to" class="form-control form-control-sm" value="<?= eAttr($dateTo) ?>" style="width:150px">
            <button class="btn btn-primary btn-sm"><i class="fas fa-filter me-1"></i>Filtrar</button>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnExportPdf" title="Exportar PDF"><i class="fas fa-file-pdf"></i></button>
        </form>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item"><a class="nav-link <?= $tab === 'sales' ? 'active' : '' ?>" data-bs-toggle="tab" href="#tabSales" data-tab="sales">
            <i class="fas fa-shopping-cart me-1"></i>Vendas</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab === 'production' ? 'active' : '' ?>" data-bs-toggle="tab" href="#tabProduction" data-tab="production">
            <i class="fas fa-industry me-1"></i>Produção</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab === 'financial' ? 'active' : '' ?>" data-bs-toggle="tab" href="#tabFinancial" data-tab="financial">
            <i class="fas fa-dollar-sign me-1"></i>Financeiro</a></li>
    </ul>

    <div class="tab-content">
        <!-- ═══ TAB VENDAS ═══ -->
        <div class="tab-pane fade <?= $tab === 'sales' ? 'show active' : '' ?>" id="tabSales">
            <!-- KPIs -->
            <div class="row g-3 mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
                        <div class="card-body d-flex align-items-center p-3">
                            <div class="icon-circle icon-circle-xxl icon-circle-primary me-3">
                                <i class="fas fa-dollar-sign fa-lg text-primary"></i>
                            </div>
                            <div>
                                <div class="text-muted small text-uppercase">Faturamento</div>
                                <div class="fw-bold fs-4">R$ <?= number_format((float)($salesData['summary']['faturamento'] ?? 0), 2, ',', '.') ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
                        <div class="card-body d-flex align-items-center p-3">
                            <div class="icon-circle icon-circle-xxl icon-circle-green me-3">
                                <i class="fas fa-shopping-cart fa-lg text-success"></i>
                            </div>
                            <div>
                                <div class="text-muted small text-uppercase">Pedidos</div>
                                <div class="fw-bold fs-4"><?= (int)($salesData['summary']['total_orders'] ?? 0) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 shadow-sm h-100 border-start border-info border-4">
                        <div class="card-body d-flex align-items-center p-3">
                            <div class="icon-circle icon-circle-xxl icon-circle-info me-3">
                                <i class="fas fa-receipt fa-lg text-info"></i>
                            </div>
                            <div>
                                <div class="text-muted small text-uppercase">Ticket Médio</div>
                                <div class="fw-bold fs-4">R$ <?= number_format((float)($salesData['summary']['ticket_medio'] ?? 0), 2, ',', '.') ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 shadow-sm h-100 border-start border-warning border-4">
                        <div class="card-body d-flex align-items-center p-3">
                            <div class="icon-circle icon-circle-xxl icon-circle-warning me-3">
                                <i class="fas fa-users fa-lg text-warning"></i>
                            </div>
                            <div>
                                <div class="text-muted small text-uppercase">Clientes Ativos</div>
                                <div class="fw-bold fs-4"><?= (int)($salesData['summary']['clientes_ativos'] ?? 0) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Faturamento Mensal -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent"><i class="fas fa-chart-bar me-2 text-primary"></i>Faturamento Mensal</div>
                        <div class="card-body"><canvas id="chartSalesMonthly" height="280"></canvas></div>
                    </div>
                </div>
                <!-- Pedidos por Status (drill-down) -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent"><i class="fas fa-chart-pie me-2 text-primary"></i>Pedidos por Status</div>
                        <div class="card-body"><canvas id="chartOrderStatus" height="280"></canvas></div>
                    </div>
                </div>
                <!-- Top Produtos -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent"><i class="fas fa-trophy me-2 text-warning"></i>Top 10 Produtos</div>
                        <div class="card-body"><canvas id="chartTopProducts" height="300"></canvas></div>
                    </div>
                </div>
                <!-- Top Clientes -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent"><i class="fas fa-users me-2 text-success"></i>Top 10 Clientes</div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead><tr><th>Cliente</th><th class="text-center">Pedidos</th><th class="text-end">Valor</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($salesData['top_customers'] as $tc): ?>
                                    <tr>
                                        <td><?= e($tc['cliente']) ?></td>
                                        <td class="text-center"><?= (int)$tc['pedidos'] ?></td>
                                        <td class="text-end">R$ <?= number_format((float)$tc['valor'], 2, ',', '.') ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ TAB PRODUÇÃO ═══ -->
        <div class="tab-pane fade <?= $tab === 'production' ? 'show active' : '' ?>" id="tabProduction">
            <!-- KPIs -->
            <div class="row g-3 mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
                        <div class="card-body d-flex align-items-center p-3">
                            <div class="icon-circle icon-circle-xxl icon-circle-primary me-3">
                                <i class="fas fa-industry fa-lg text-primary"></i>
                            </div>
                            <div>
                                <div class="text-muted small text-uppercase">Pedidos Ativos</div>
                                <div class="fw-bold fs-4"><?= (int)($productionData['pipeline_stats']['total_active'] ?? 0) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 shadow-sm h-100 border-start border-danger border-4">
                        <div class="card-body d-flex align-items-center p-3">
                            <div class="icon-circle icon-circle-xxl icon-circle-danger me-3">
                                <i class="fas fa-exclamation-triangle fa-lg text-danger"></i>
                            </div>
                            <div>
                                <div class="text-muted small text-uppercase">Atrasados</div>
                                <div class="fw-bold fs-4"><?= (int)($productionData['pipeline_stats']['total_delayed'] ?? 0) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
                        <div class="card-body d-flex align-items-center p-3">
                            <div class="icon-circle icon-circle-xxl icon-circle-green me-3">
                                <i class="fas fa-check-circle fa-lg text-success"></i>
                            </div>
                            <div>
                                <div class="text-muted small text-uppercase">Concluídos (mês)</div>
                                <div class="fw-bold fs-4"><?= (int)($productionData['pipeline_stats']['completed_month'] ?? 0) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 shadow-sm h-100 border-start border-info border-4">
                        <div class="card-body d-flex align-items-center p-3">
                            <div class="icon-circle icon-circle-xxl icon-circle-info me-3">
                                <i class="fas fa-coins fa-lg text-info"></i>
                            </div>
                            <div>
                                <div class="text-muted small text-uppercase">Valor em Produção</div>
                                <div class="fw-bold fs-4">R$ <?= number_format((float)($productionData['pipeline_stats']['total_value'] ?? 0), 2, ',', '.') ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Pipeline por Etapa (drill-down) -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent"><i class="fas fa-layer-group me-2 text-primary"></i>Pipeline por Etapa <small class="text-muted">(clique para detalhar)</small></div>
                        <div class="card-body"><canvas id="chartPipelineStages" height="300"></canvas></div>
                    </div>
                </div>
                <!-- Throughput Diário -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent"><i class="fas fa-tachometer-alt me-2 text-success"></i>Throughput (concluídos/dia)</div>
                        <div class="card-body"><canvas id="chartThroughput" height="300"></canvas></div>
                    </div>
                </div>
                <!-- Gargalos -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent"><i class="fas fa-exclamation-triangle me-2 text-danger"></i>Gargalos (atrasados por etapa)</div>
                        <div class="card-body"><canvas id="chartBottlenecks" height="250"></canvas></div>
                    </div>
                </div>
                <!-- Tempo Médio por Etapa -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent"><i class="fas fa-clock me-2 text-warning"></i>Tempo Médio por Transição (horas)</div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead><tr><th>De</th><th>Para</th><th class="text-end">Média (h)</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($productionData['stage_time'] as $st): ?>
                                    <tr>
                                        <td><?= e($st['from_stage'] ?? '-') ?></td>
                                        <td><?= e($st['to_stage'] ?? '-') ?></td>
                                        <td class="text-end"><?= number_format((float)($st['avg_hours'] ?? 0), 1) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ TAB FINANCEIRO ═══ -->
        <div class="tab-pane fade <?= $tab === 'financial' ? 'show active' : '' ?>" id="tabFinancial">
            <!-- KPIs -->
            <div class="row g-3 mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
                        <div class="card-body d-flex align-items-center p-3">
                            <div class="icon-circle icon-circle-xxl icon-circle-green me-3">
                                <i class="fas fa-file-invoice-dollar fa-lg text-success"></i>
                            </div>
                            <div>
                                <div class="text-muted small text-uppercase">Receita (mês)</div>
                                <div class="fw-bold fs-4">R$ <?= number_format((float)($financialData['summary']['receita_mes'] ?? 0), 2, ',', '.') ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
                        <div class="card-body d-flex align-items-center p-3">
                            <div class="icon-circle icon-circle-xxl icon-circle-primary me-3">
                                <i class="fas fa-hand-holding-usd fa-lg text-primary"></i>
                            </div>
                            <div>
                                <div class="text-muted small text-uppercase">Recebido (mês)</div>
                                <div class="fw-bold fs-4">R$ <?= number_format((float)($financialData['summary']['recebido_mes'] ?? 0), 2, ',', '.') ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 shadow-sm h-100 border-start border-warning border-4">
                        <div class="card-body d-flex align-items-center p-3">
                            <div class="icon-circle icon-circle-xxl icon-circle-warning me-3">
                                <i class="fas fa-clock fa-lg text-warning"></i>
                            </div>
                            <div>
                                <div class="text-muted small text-uppercase">A Receber Total</div>
                                <div class="fw-bold fs-4">R$ <?= number_format((float)($financialData['summary']['a_receber_total'] ?? 0), 2, ',', '.') ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 shadow-sm h-100 border-start border-danger border-4">
                        <div class="card-body d-flex align-items-center p-3">
                            <div class="icon-circle icon-circle-xxl icon-circle-danger me-3">
                                <i class="fas fa-exclamation-circle fa-lg text-danger"></i>
                            </div>
                            <div>
                                <div class="text-muted small text-uppercase">Inadimplentes</div>
                                <div class="fw-bold fs-4">R$ <?= number_format((float)($financialData['summary']['atrasados_total'] ?? 0), 2, ',', '.') ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Fluxo de Caixa -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent"><i class="fas fa-chart-area me-2 text-primary"></i>Fluxo de Caixa</div>
                        <div class="card-body"><canvas id="chartCashFlow" height="280"></canvas></div>
                    </div>
                </div>
                <!-- DRE Simplificado -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent"><i class="fas fa-balance-scale me-2 text-info"></i>DRE Simplificado</div>
                        <div class="card-body">
                            <?php $dre = $financialData['dre'] ?? []; $lucro = ($dre['receita_realizada'] ?? 0) - ($dre['despesa_realizada'] ?? 0); ?>
                            <table class="table table-sm mb-0">
                                <tr><td>Receita Realizada</td><td class="text-end text-success">R$ <?= number_format((float)($dre['receita_realizada'] ?? 0), 2, ',', '.') ?></td></tr>
                                <tr><td>Despesa Realizada</td><td class="text-end text-danger">R$ <?= number_format((float)($dre['despesa_realizada'] ?? 0), 2, ',', '.') ?></td></tr>
                                <tr class="fw-bold"><td>Resultado</td><td class="text-end <?= $lucro >= 0 ? 'text-success' : 'text-danger' ?>">R$ <?= number_format($lucro, 2, ',', '.') ?></td></tr>
                                <tr><td colspan="2"><hr class="my-1"></td></tr>
                                <tr class="text-muted"><td>Receita Prevista</td><td class="text-end">R$ <?= number_format((float)($dre['receita_prevista'] ?? 0), 2, ',', '.') ?></td></tr>
                                <tr class="text-muted"><td>Despesa Prevista</td><td class="text-end">R$ <?= number_format((float)($dre['despesa_prevista'] ?? 0), 2, ',', '.') ?></td></tr>
                            </table>
                        </div>
                    </div>
                </div>
                <!-- Inadimplentes (drill-down) -->
                <div class="col-lg-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-exclamation-circle me-2 text-danger"></i>Parcelas em Atraso</span>
                            <button class="btn btn-outline-danger btn-sm" onclick="biDrillDown('overdue_installments')">
                                <i class="fas fa-search-plus me-1"></i>Ver Detalhes
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead><tr><th>Pedido</th><th>Cliente</th><th class="text-end">Valor</th><th>Vencimento</th><th class="text-end">Dias Atraso</th></tr></thead>
                                    <tbody>
                                    <?php foreach (array_slice($financialData['overdue'] ?? [], 0, 10) as $ov): ?>
                                    <tr>
                                        <td><?= e($ov['order_number'] ?? $ov['order_id'] ?? '-') ?></td>
                                        <td><?= e($ov['customer_name'] ?? $ov['customer'] ?? '-') ?></td>
                                        <td class="text-end">R$ <?= number_format((float)($ov['amount'] ?? 0), 2, ',', '.') ?></td>
                                        <td><?= e($ov['due_date'] ?? '-') ?></td>
                                        <td class="text-end text-danger"><?= (int)($ov['days_overdue'] ?? 0) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Drill-Down Modal -->
<div class="modal fade" id="drillDownModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-search-plus me-2"></i>Detalhamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="drillDownContent"><div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div></div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4" nonce="<?= cspNonce() ?>"></script>
<script nonce="<?= cspNonce() ?>">
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const dateFrom = <?= eJs($dateFrom) ?>;
    const dateTo = <?= eJs($dateTo) ?>;

    // ── Tab persistence ──
    document.querySelectorAll('.nav-tabs .nav-link').forEach(function(link) {
        link.addEventListener('shown.bs.tab', function() {
            document.getElementById('activeTab').value = this.dataset.tab;
        });
    });

    // ── Color palette ──
    const colors = ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#ec4899','#f97316','#14b8a6','#6366f1'];

    // ══════════ VENDAS ══════════
    // Faturamento Mensal
    const salesMonthly = <?= json_encode($salesData['by_month'] ?? []) ?>;
    new Chart(document.getElementById('chartSalesMonthly'), {
        type: 'bar',
        data: {
            labels: salesMonthly.map(r => r.mes),
            datasets: [
                { label: 'Faturamento (R$)', data: salesMonthly.map(r => +r.faturamento), backgroundColor: '#3b82f6', borderRadius: 4 },
                { label: 'Pedidos', data: salesMonthly.map(r => +r.pedidos), backgroundColor: '#10b981', borderRadius: 4, yAxisID: 'y1' }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            scales: { y: { beginAtZero: true }, y1: { position: 'right', beginAtZero: true, grid: { drawOnChartArea: false } } },
            onClick: function(e, elements) {
                if (elements.length) {
                    const idx = elements[0].index;
                    const month = salesMonthly[idx].mes;
                    biDrillDown('orders_by_status', { date_from: month + '-01', date_to: month + '-31' });
                }
            }
        }
    });

    // Pedidos por Status (doughnut drill-down)
    const orderStatus = <?= json_encode($salesData['by_status'] ?? []) ?>;
    new Chart(document.getElementById('chartOrderStatus'), {
        type: 'doughnut',
        data: {
            labels: orderStatus.map(r => r.status),
            datasets: [{ data: orderStatus.map(r => +r.total), backgroundColor: colors }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            onClick: function(e, elements) {
                if (elements.length) {
                    const idx = elements[0].index;
                    biDrillDown('orders_by_status', { status: orderStatus[idx].status });
                }
            }
        }
    });

    // Top Produtos (horizontal bar)
    const topProducts = <?= json_encode($salesData['top_products'] ?? []) ?>;
    new Chart(document.getElementById('chartTopProducts'), {
        type: 'bar',
        data: {
            labels: topProducts.map(r => r.produto),
            datasets: [{ label: 'Valor (R$)', data: topProducts.map(r => +r.valor), backgroundColor: colors, borderRadius: 4 }]
        },
        options: {
            indexAxis: 'y', responsive: true, maintainAspectRatio: false,
            onClick: function(e, elements) {
                if (elements.length) {
                    const idx = elements[0].index;
                    biDrillDown('top_product_orders', { product_id: topProducts[idx].produto });
                }
            }
        }
    });

    // ══════════ PRODUÇÃO ══════════
    // Pipeline por Etapa
    const byStage = <?= json_encode(array_values($productionData['pipeline_stats']['by_stage'] ?? [])) ?>;
    const stageLabels = <?= json_encode(array_keys($productionData['pipeline_stats']['by_stage'] ?? [])) ?>;
    new Chart(document.getElementById('chartPipelineStages'), {
        type: 'bar',
        data: {
            labels: stageLabels,
            datasets: [{ label: 'Pedidos', data: byStage, backgroundColor: colors, borderRadius: 4 }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            onClick: function(e, elements) {
                if (elements.length) {
                    const idx = elements[0].index;
                    biDrillDown('orders_by_stage', { stage: stageLabels[idx] });
                }
            }
        }
    });

    // Throughput
    const throughput = <?= json_encode($productionData['throughput'] ?? []) ?>;
    new Chart(document.getElementById('chartThroughput'), {
        type: 'line',
        data: {
            labels: throughput.map(r => r.dia),
            datasets: [{ label: 'Concluídos', data: throughput.map(r => +r.concluidos), borderColor: '#10b981', fill: true, backgroundColor: 'rgba(16,185,129,0.1)', tension: 0.3 }]
        },
        options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
    });

    // Gargalos
    const bottlenecks = <?= json_encode($productionData['bottlenecks'] ?? []) ?>;
    new Chart(document.getElementById('chartBottlenecks'), {
        type: 'bar',
        data: {
            labels: bottlenecks.map(r => r.current_stage),
            datasets: [{ label: 'Atrasados', data: bottlenecks.map(r => +r.atrasados), backgroundColor: '#ef4444', borderRadius: 4 }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            onClick: function(e, elements) {
                if (elements.length) {
                    const idx = elements[0].index;
                    biDrillDown('orders_by_stage', { stage: bottlenecks[idx].current_stage });
                }
            }
        }
    });

    // ══════════ FINANCEIRO ══════════
    // Fluxo de Caixa
    const cashFlow = <?= json_encode($financialData['cash_flow'] ?? []) ?>;
    new Chart(document.getElementById('chartCashFlow'), {
        type: 'line',
        data: {
            labels: cashFlow.map(r => r.mes),
            datasets: [
                { label: 'Entradas', data: cashFlow.map(r => +r.entradas), borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.1)', fill: true, tension: 0.3 },
                { label: 'Saídas', data: cashFlow.map(r => +r.saidas), borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,0.1)', fill: true, tension: 0.3 }
            ]
        },
        options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
    });

    // ══════════ DRILL-DOWN ══════════
    window.biDrillDown = function(type, extraFilters) {
        const params = new URLSearchParams({ page: 'bi', action: 'drillDown', type: type, date_from: dateFrom, date_to: dateTo });
        if (extraFilters) {
            Object.keys(extraFilters).forEach(k => params.set(k, extraFilters[k]));
        }
        const modal = new bootstrap.Modal(document.getElementById('drillDownModal'));
        document.getElementById('drillDownContent').innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
        modal.show();

        fetch('?' + params.toString(), { headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(function(res) {
            if (!res.success || !res.data.length) {
                document.getElementById('drillDownContent').innerHTML = '<p class="text-muted text-center">Nenhum registro encontrado.</p>';
                return;
            }
            const cols = Object.keys(res.data[0]);
            let html = '<div class="table-responsive"><table class="table table-sm table-hover"><thead><tr>';
            cols.forEach(function(c) { html += '<th>' + c.replace(/_/g,' ') + '</th>'; });
            html += '</tr></thead><tbody>';
            res.data.forEach(function(row) {
                html += '<tr>';
                cols.forEach(function(c) {
                    let val = row[c] ?? '';
                    if (typeof val === 'number' || (!isNaN(val) && val !== '' && c.match(/amount|valor|total|subtotal|unit_price/))) {
                        val = parseFloat(val).toLocaleString('pt-BR', { minimumFractionDigits: 2 });
                    }
                    html += '<td>' + DOMPurify.sanitize(String(val)) + '</td>';
                });
                html += '</tr>';
            });
            html += '</tbody></table></div>';
            document.getElementById('drillDownContent').innerHTML = html;
        })
        .catch(function() {
            document.getElementById('drillDownContent').innerHTML = '<p class="text-danger text-center">Erro ao carregar dados.</p>';
        });
    };

    // ── PDF Export ──
    document.getElementById('btnExportPdf').addEventListener('click', function() {
        if (typeof html2canvas === 'undefined') {
            const s = document.createElement('script');
            s.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js';
            s.onload = doExport;
            document.head.appendChild(s);
        } else {
            doExport();
        }
        function doExport() {
            const activePane = document.querySelector('.tab-pane.active');
            if (!activePane) return;
            Swal.fire({ title: 'Gerando PDF...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            html2canvas(activePane, { scale: 2 }).then(function(canvas) {
                Swal.close();
                const link = document.createElement('a');
                link.download = 'bi-dashboard-' + new Date().toISOString().slice(0,10) + '.png';
                link.href = canvas.toDataURL();
                link.click();
            });
        }
    });
});
</script>
