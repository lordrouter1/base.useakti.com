<!-- FEAT-016: Dashboard em Tempo Real -->
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1"><i class="fas fa-tachometer-alt me-2"></i>Dashboard em Tempo Real</h1>
            <small class="text-muted">Atualizado: <span id="lastUpdate">-</span></small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span id="connectionStatus" class="badge bg-secondary">
                <i class="fas fa-circle me-1"></i>Conectando...
            </span>
            <a href="?page=home" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i>Dashboard Padrão
            </a>
        </div>
    </div>

    <!-- KPIs em tempo real -->
    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="card border-start border-primary border-4 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">Total de Pedidos</p>
                            <h3 class="mb-0" id="kpiTotalOrders">-</h3>
                        </div>
                        <div class="p-2 bg-primary bg-opacity-10 rounded">
                            <i class="fas fa-shopping-cart fa-lg text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card border-start border-success border-4 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">Valor Ativo</p>
                            <h3 class="mb-0" id="kpiActiveValue">-</h3>
                        </div>
                        <div class="p-2 bg-success bg-opacity-10 rounded">
                            <i class="fas fa-dollar-sign fa-lg text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card border-start border-warning border-4 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">Pipeline Ativo</p>
                            <h3 class="mb-0" id="kpiPipelineActive">-</h3>
                        </div>
                        <div class="p-2 bg-warning bg-opacity-10 rounded">
                            <i class="fas fa-stream fa-lg text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card border-start border-info border-4 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">Clientes</p>
                            <h3 class="mb-0" id="kpiCustomers">-</h3>
                        </div>
                        <div class="p-2 bg-info bg-opacity-10 rounded">
                            <i class="fas fa-users fa-lg text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <!-- Pipeline por etapa -->
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Pipeline por Etapa</h5>
                    <span class="badge bg-secondary" id="pipelineTotal">0</span>
                </div>
                <div class="card-body">
                    <canvas id="pipelineChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Pedidos por status -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Pedidos por Status</h5>
                </div>
                <div class="card-body">
                    <canvas id="statusChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Pedidos atrasados -->
    <div class="row g-3 mt-1">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle text-danger me-2"></i>Pedidos Atrasados</h5>
                    <span class="badge bg-danger" id="delayedCount">0</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Pedido</th>
                                    <th>Etapa</th>
                                    <th>Dias de Atraso</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody id="delayedTableBody">
                                <tr><td colspan="4" class="text-center text-muted py-3">Carregando...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function() {
    'use strict';

    const POLL_INTERVAL = 5000; // 5 segundos
    let pipelineChart = null;
    let statusChart = null;
    let pollTimer = null;

    // ── Inicializar gráficos ──
    function initCharts() {
        const pipelineCtx = document.getElementById('pipelineChart').getContext('2d');
        pipelineChart = new Chart(pipelineCtx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'Pedidos',
                    data: [],
                    backgroundColor: [
                        '#3498db', '#2ecc71', '#e67e22', '#9b59b6',
                        '#1abc9c', '#e74c3c', '#f39c12', '#34495e'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });

        const statusCtx = document.getElementById('statusChart').getContext('2d');
        statusChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: [],
                datasets: [{
                    data: [],
                    backgroundColor: ['#3498db', '#2ecc71', '#e67e22', '#e74c3c', '#95a5a6']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }

    // ── Buscar dados ──
    function fetchData() {
        $.ajax({
            url: '?page=dashboard_realtime&action=data',
            type: 'GET',
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': typeof csrfToken !== 'undefined' ? csrfToken : '' },
            success: function(response) {
                if (response.success) {
                    updateDashboard(response.data);
                    setConnectionStatus('online');
                } else {
                    setConnectionStatus('error');
                }
            },
            error: function() {
                setConnectionStatus('offline');
            }
        });
    }

    // ── Atualizar dashboard ──
    function updateDashboard(data) {
        // KPIs
        $('#kpiTotalOrders').text(data.total_orders || 0);
        $('#kpiActiveValue').text('R$ ' + parseFloat(data.active_value || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2}));
        $('#kpiPipelineActive').text(data.pipeline.total_active || 0);
        $('#kpiCustomers').text(data.total_customers || 0);
        $('#lastUpdate').text(data.timestamp || '-');

        // Pipeline chart
        if (data.pipeline.by_stage && pipelineChart) {
            const labels = Object.keys(data.pipeline.by_stage);
            const values = Object.values(data.pipeline.by_stage);
            pipelineChart.data.labels = labels;
            pipelineChart.data.datasets[0].data = values;
            pipelineChart.update('none');
            $('#pipelineTotal').text(data.pipeline.total_active || 0);
        }

        // Status chart
        if (data.orders_status && statusChart) {
            const statusLabels = Object.keys(data.orders_status);
            const statusValues = Object.values(data.orders_status);
            statusChart.data.labels = statusLabels;
            statusChart.data.datasets[0].data = statusValues;
            statusChart.update('none');
        }

        // Pedidos atrasados
        const delayed = data.pipeline.delayed_orders || [];
        $('#delayedCount').text(delayed.length);
        const tbody = $('#delayedTableBody');
        tbody.empty();
        if (delayed.length === 0) {
            tbody.append('<tr><td colspan="4" class="text-center text-muted py-3"><i class="fas fa-check-circle text-success me-1"></i>Nenhum pedido atrasado</td></tr>');
        } else {
            delayed.forEach(function(order) {
                tbody.append(
                    '<tr>' +
                    '<td><strong>#' + (order.order_number || order.id) + '</strong></td>' +
                    '<td><span class="badge bg-warning">' + (order.stage_name || order.current_stage || '-') + '</span></td>' +
                    '<td><span class="text-danger fw-bold">' + (order.days_delayed || '-') + ' dias</span></td>' +
                    '<td><a href="?page=pipeline&action=edit&id=' + order.id + '" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a></td>' +
                    '</tr>'
                );
            });
        }
    }

    // ── Status da conexão ──
    function setConnectionStatus(status) {
        const el = $('#connectionStatus');
        switch (status) {
            case 'online':
                el.removeClass('bg-secondary bg-danger').addClass('bg-success')
                    .html('<i class="fas fa-circle me-1"></i>Online');
                break;
            case 'offline':
                el.removeClass('bg-secondary bg-success').addClass('bg-danger')
                    .html('<i class="fas fa-circle me-1"></i>Offline');
                break;
            case 'error':
                el.removeClass('bg-secondary bg-success').addClass('bg-danger')
                    .html('<i class="fas fa-exclamation-triangle me-1"></i>Erro');
                break;
        }
    }

    // ── Init ──
    $(document).ready(function() {
        initCharts();
        fetchData();
        pollTimer = setInterval(fetchData, POLL_INTERVAL);
    });

    // Limpar ao sair da página
    $(window).on('beforeunload', function() {
        if (pollTimer) clearInterval(pollTimer);
    });
})();
</script>
