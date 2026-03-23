<?php
/**
 * Comissões — Dashboard
 * Visão geral de indicadores do módulo de comissões.
 * Padrão visual: Financeiro (cards border-start, sidebar em card, filtros dinâmicos).
 * Variáveis: $summary, $config
 */
$month = $_GET['month'] ?? date('m');
$year  = $_GET['year']  ?? date('Y');
$monthNames = ['','Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
?>

<?php if (!empty($_SESSION['flash_success'])): ?>
<script>document.addEventListener('DOMContentLoaded',()=>Swal.mixin({toast:true,position:'top-end',showConfirmButton:false,timer:2500,timerProgressBar:true}).fire({icon:'success',title:'<?= addslashes($_SESSION['flash_success']) ?>'}));</script>
<?php unset($_SESSION['flash_success']); endif; ?>

<div class="container-fluid py-3">

    <!-- ══════ Header ══════ -->
    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div>
            <h1 class="h2 mb-1"><i class="fas fa-hand-holding-usd me-2 text-primary"></i>Comissões</h1>
            <p class="text-muted mb-0" style="font-size:.82rem;">Dashboard, regras, simulação e histórico de comissões.</p>
        </div>
        <div class="btn-toolbar gap-2">
            <a href="?page=commissions&action=simulador" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-calculator me-1"></i> Simulador
            </a>
            <a href="?page=commissions&action=historico" class="btn btn-sm btn-primary">
                <i class="fas fa-history me-1"></i> Histórico
            </a>
        </div>
    </div>

    <div class="row g-4">

        <!-- Sidebar -->
        <?php require 'app/views/commissions/_sidebar.php'; ?>

        <!-- Conteúdo -->
        <div class="col-lg-9">

            <div class="d-flex align-items-center mb-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center me-2" style="width:34px;height:34px;background:rgba(52,152,219,.1);">
                    <i class="fas fa-tachometer-alt" style="color:#3498db;font-size:.85rem;"></i>
                </div>
                <div>
                    <h5 class="mb-0" style="font-size:1rem;">Dashboard</h5>
                    <p class="text-muted mb-0" style="font-size:.72rem;">Resumo e indicadores do mês selecionado.</p>
                </div>
            </div>

            <!-- Filtro Mês/Ano (dinâmico) -->
            <div class="row g-2 mb-4 align-items-end">
                <div class="col-auto">
                    <label class="form-label small fw-bold mb-1">Mês</label>
                    <select id="dashMonth" class="form-select form-select-sm" style="width:120px">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= $month == $m ? 'selected' : '' ?>><?= $monthNames[$m] ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label small fw-bold mb-1">Ano</label>
                    <select id="dashYear" class="form-select form-select-sm" style="width:100px">
                        <?php for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++): ?>
                        <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>

            <!-- Cards resumo (padrão Financeiro: border-start border-4) -->
            <div class="row g-3 mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
                        <div class="card-body d-flex align-items-center p-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:50px;height:50px;background:rgba(52,152,219,0.15);">
                                <i class="fas fa-coins fa-lg text-primary"></i>
                            </div>
                            <div>
                                <div class="text-muted small text-uppercase fw-bold" style="font-size:.65rem;">Total do Mês</div>
                                <div class="fw-bold fs-4" id="cardTotalMes">R$ <?= number_format($summary['total_mes'] ?? 0, 2, ',', '.') ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
                        <div class="card-body d-flex align-items-center p-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:50px;height:50px;background:rgba(39,174,96,0.15);">
                                <i class="fas fa-check-circle fa-lg text-success"></i>
                            </div>
                            <div>
                                <div class="text-muted small text-uppercase fw-bold" style="font-size:.65rem;">Pagas no Mês</div>
                                <div class="fw-bold fs-4" id="cardPagoMes">R$ <?= number_format($summary['total_pago_mes'] ?? 0, 2, ',', '.') ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 shadow-sm h-100 border-start border-warning border-4">
                        <div class="card-body d-flex align-items-center p-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:50px;height:50px;background:rgba(243,156,18,0.15);">
                                <i class="fas fa-clock fa-lg text-warning"></i>
                            </div>
                            <div>
                                <div class="text-muted small text-uppercase fw-bold" style="font-size:.65rem;">Pendentes</div>
                                <div class="fw-bold fs-5" id="cardPendentes">
                                    <?= $summary['pendentes_count'] ?? 0 ?>
                                    <small class="text-muted fw-normal">(R$ <?= number_format($summary['pendentes_valor'] ?? 0, 2, ',', '.') ?>)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 shadow-sm h-100 border-start border-info border-4">
                        <div class="card-body d-flex align-items-center p-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:50px;height:50px;background:rgba(23,162,184,0.15);">
                                <i class="fas fa-thumbs-up fa-lg text-info"></i>
                            </div>
                            <div>
                                <div class="text-muted small text-uppercase fw-bold" style="font-size:.65rem;">Aprovadas (a pagar)</div>
                                <div class="fw-bold fs-5" id="cardAprovadas">
                                    <?= $summary['aprovadas_count'] ?? 0 ?>
                                    <small class="text-muted fw-normal">(R$ <?= number_format($summary['aprovadas_valor'] ?? 0, 2, ',', '.') ?>)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <!-- Gráfico -->
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-bottom p-3">
                            <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-chart-bar me-2"></i>Comissões — Últimos 6 meses</h6>
                        </div>
                        <div class="card-body p-3">
                            <canvas id="chartComissoes" height="250"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Top Comissionados -->
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-bottom p-3">
                            <h6 class="mb-0 fw-bold text-warning"><i class="fas fa-trophy me-2"></i>Top Comissionados</h6>
                        </div>
                        <div class="card-body p-0" id="topComissionadosBody">
                            <?php if (!empty($summary['top_comissionados'])): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($summary['top_comissionados'] as $i => $tc): ?>
                                <div class="list-group-item d-flex align-items-center gap-3 py-2 px-3">
                                    <span class="badge bg-<?= $i === 0 ? 'warning text-dark' : ($i === 1 ? 'secondary' : 'light text-dark border') ?> rounded-pill fs-6" style="width:32px;text-align:center">
                                        <?= $i + 1 ?>º
                                    </span>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold small"><?= e($tc['name']) ?></div>
                                        <small class="text-muted" style="font-size:.7rem;"><?= $tc['qty'] ?> comissões</small>
                                    </div>
                                    <span class="fw-bold text-success small">R$ <?= number_format($tc['total'], 2, ',', '.') ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-check-circle fa-2x d-block mb-2 text-success opacity-50"></i>
                                <small>Nenhuma comissão neste mês.</small>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Atalhos rápidos -->
            <div class="row g-3 mt-3">
                <div class="col-md-4">
                    <a href="?page=commissions&action=formas" class="card border-0 shadow-sm text-decoration-none h-100" style="border-radius:12px;">
                        <div class="card-body text-center py-4">
                            <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width:48px;height:48px;background:rgba(39,174,96,.1);">
                                <i class="fas fa-file-alt fs-5 text-success"></i>
                            </div>
                            <h6 class="mb-1">Formas de Comissão</h6>
                            <small class="text-muted">Cadastrar e gerenciar modelos</small>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="?page=commissions&action=simulador" class="card border-0 shadow-sm text-decoration-none h-100" style="border-radius:12px;">
                        <div class="card-body text-center py-4">
                            <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width:48px;height:48px;background:rgba(52,152,219,.1);">
                                <i class="fas fa-calculator fs-5 text-primary"></i>
                            </div>
                            <h6 class="mb-1">Simulador</h6>
                            <small class="text-muted">Simular cálculos antes de registrar</small>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="?page=commissions&action=configuracoes" class="card border-0 shadow-sm text-decoration-none h-100" style="border-radius:12px;">
                        <div class="card-body text-center py-4">
                            <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width:48px;height:48px;background:rgba(127,140,141,.1);">
                                <i class="fas fa-cog fs-5 text-secondary"></i>
                            </div>
                            <h6 class="mb-1">Configurações</h6>
                            <small class="text-muted">Parâmetros do módulo</small>
                        </div>
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const chartData = <?= json_encode($summary['chart'] ?? []) ?>;
    const labels = chartData.map(d => d.label);
    const values = chartData.map(d => d.value);

    const chartInstance = new Chart(document.getElementById('chartComissoes'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Comissões (R$)',
                data: values,
                backgroundColor: 'rgba(52, 152, 219, 0.7)',
                borderColor: 'rgba(52, 152, 219, 1)',
                borderWidth: 1,
                borderRadius: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: v => 'R$ ' + v.toLocaleString('pt-BR', {minimumFractionDigits: 2})
                    }
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => 'R$ ' + ctx.parsed.y.toLocaleString('pt-BR', {minimumFractionDigits: 2})
                    }
                }
            }
        }
    });

    // ── Filtros dinâmicos (auto-apply ao trocar mês/ano) ──
    const elMonth = document.getElementById('dashMonth');
    const elYear  = document.getElementById('dashYear');

    function applyDashFilter() {
        const m = elMonth.value;
        const y = elYear.value;
        const url = new URL(window.location);
        url.searchParams.set('page', 'commissions');
        url.searchParams.set('month', m);
        url.searchParams.set('year', y);
        window.location.href = url.toString();
    }

    elMonth.addEventListener('change', applyDashFilter);
    elYear.addEventListener('change', applyDashFilter);
});
</script>
