<?php
/**
 * Fiscal — Dashboard Financeiro
 * Variáveis: $summary, $chartData, $pendingConfirmations, $overdueInstallments, $upcomingInstallments, $month, $year, $categories
 */
$monthNames = ['','Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
$currentMonth = $month ?? date('m');
$currentYear = $year ?? date('Y');
?>

<?php if (!empty($_SESSION['flash_success'])): ?>
<script>document.addEventListener('DOMContentLoaded', () => Swal.fire({ icon:'success', title:'Sucesso!', text:'<?= addslashes($_SESSION['flash_success']) ?>', timer:2500, showConfirmButton:false }));</script>
<?php unset($_SESSION['flash_success']); endif; ?>
<?php if (!empty($_SESSION['flash_error'])): ?>
<script>document.addEventListener('DOMContentLoaded', () => Swal.fire({ icon:'error', title:'Erro', text:'<?= addslashes($_SESSION['flash_error']) ?>' }));</script>
<?php unset($_SESSION['flash_error']); endif; ?>

<!-- ══════ Header ══════ -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-coins me-2 text-warning"></i>Financeiro</h1>
    <div class="btn-toolbar mb-2 mb-md-0 gap-2">
        <a href="?page=financial&action=payments" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-file-invoice-dollar me-1"></i> Pagamentos
        </a>
        <a href="?page=financial&action=transactions" class="btn btn-sm btn-outline-success">
            <i class="fas fa-exchange-alt me-1"></i> Entradas / Saídas
        </a>
    </div>
</div>

<!-- ══════ Filtro de Período ══════ -->
<form method="get" class="row g-2 mb-4 align-items-end">
    <input type="hidden" name="page" value="financial">
    <div class="col-auto">
        <label class="form-label small fw-bold mb-1">Mês</label>
        <select name="month" class="form-select form-select-sm" style="width:160px">
            <?php for($m=1;$m<=12;$m++): ?>
            <option value="<?= str_pad($m,2,'0',STR_PAD_LEFT) ?>" <?= $currentMonth==$m?'selected':'' ?>><?= $monthNames[$m] ?></option>
            <?php endfor; ?>
        </select>
    </div>
    <div class="col-auto">
        <label class="form-label small fw-bold mb-1">Ano</label>
        <select name="year" class="form-select form-select-sm" style="width:100px">
            <?php for($y=date('Y')-2;$y<=date('Y')+1;$y++): ?>
            <option value="<?= $y ?>" <?= $currentYear==$y?'selected':'' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i> Filtrar</button>
    </div>
</form>

<!-- ══════ Cards de Resumo ══════ -->
<div class="row g-3 mb-4">
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
            <div class="card-body py-3 px-3">
                <div class="text-muted small fw-bold text-uppercase">Receita do Mês</div>
                <div class="h5 mb-0 text-primary">R$ <?= number_format($summary['receita_mes'], 2, ',', '.') ?></div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
            <div class="card-body py-3 px-3">
                <div class="text-muted small fw-bold text-uppercase">Recebido</div>
                <div class="h5 mb-0 text-success">R$ <?= number_format($summary['recebido_mes'], 2, ',', '.') ?></div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card border-0 shadow-sm h-100 border-start border-info border-4">
            <div class="card-body py-3 px-3">
                <div class="text-muted small fw-bold text-uppercase">Entradas</div>
                <div class="h5 mb-0 text-info">R$ <?= number_format($summary['entradas_mes'], 2, ',', '.') ?></div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card border-0 shadow-sm h-100 border-start border-danger border-4">
            <div class="card-body py-3 px-3">
                <div class="text-muted small fw-bold text-uppercase">Saídas</div>
                <div class="h5 mb-0 text-danger">R$ <?= number_format($summary['saidas_mes'], 2, ',', '.') ?></div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card border-0 shadow-sm h-100 border-start border-warning border-4">
            <div class="card-body py-3 px-3">
                <div class="text-muted small fw-bold text-uppercase">A Receber</div>
                <div class="h5 mb-0 text-warning">R$ <?= number_format($summary['a_receber_total'], 2, ',', '.') ?></div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card border-0 shadow-sm h-100 border-start border-4" style="border-color: #e74c3c !important;">
            <div class="card-body py-3 px-3">
                <div class="text-muted small fw-bold text-uppercase">Atrasados</div>
                <div class="h5 mb-0" style="color:#e74c3c">R$ <?= number_format($summary['atrasados_total'], 2, ',', '.') ?></div>
            </div>
        </div>
    </div>
</div>

<!-- ══════ Alertas ══════ -->
<?php if ($summary['pendentes_confirmacao'] > 0): ?>
<div class="alert alert-warning d-flex align-items-center py-2 mb-3">
    <i class="fas fa-exclamation-circle me-2 fs-5"></i>
    <div>
        <strong><?= $summary['pendentes_confirmacao'] ?></strong> pagamento(s) aguardando confirmação manual.
        <a href="#pendingConfirmations" class="alert-link ms-1">Ver abaixo <i class="fas fa-arrow-down ms-1"></i></a>
    </div>
</div>
<?php endif; ?>

<?php if (count($overdueInstallments) > 0): ?>
<div class="alert alert-danger d-flex align-items-center py-2 mb-3">
    <i class="fas fa-clock me-2 fs-5"></i>
    <div>
        <strong><?= count($overdueInstallments) ?></strong> parcela(s) vencida(s) e não paga(s).
        <a href="#overdueSection" class="alert-link ms-1">Ver abaixo <i class="fas fa-arrow-down ms-1"></i></a>
    </div>
</div>
<?php endif; ?>

<!-- ══════ Gráfico Entradas x Saídas ══════ -->
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-chart-bar me-2 text-primary"></i>Entradas x Saídas — Últimos 6 Meses</h6>
            </div>
            <div class="card-body">
                <canvas id="financialChart" height="260"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-calendar-day me-2 text-info"></i>Próximas Parcelas (7 dias)</h6>
            </div>
            <div class="card-body p-0" style="max-height:340px;overflow-y:auto;">
                <?php if (empty($upcomingInstallments)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-check-circle fa-2x mb-2 text-success"></i>
                        <p class="mb-0">Nenhuma parcela próxima do vencimento</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                    <?php foreach ($upcomingInstallments as $up): ?>
                        <a href="?page=financial&action=installments&order_id=<?= $up['order_id'] ?>" class="list-group-item list-group-item-action py-2 px-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="fw-bold text-dark">#<?= str_pad($up['order_id'], 4, '0', STR_PAD_LEFT) ?></span>
                                    <span class="text-muted small ms-1">— <?= htmlspecialchars($up['customer_name'] ?? '') ?></span>
                                </div>
                                <span class="badge bg-warning text-dark"><?= date('d/m', strtotime($up['due_date'])) ?></span>
                            </div>
                            <div class="small text-muted">
                                Parcela <?= $up['installment_number'] ?>
                                — <strong class="text-dark">R$ <?= number_format($up['amount'], 2, ',', '.') ?></strong>
                            </div>
                        </a>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ══════ Pagamentos Pendentes de Confirmação ══════ -->
<div id="pendingConfirmations" class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold">
            <i class="fas fa-user-check me-2 text-warning"></i>Pagamentos Aguardando Confirmação
            <?php if(count($pendingConfirmations) > 0): ?>
                <span class="badge bg-warning text-dark ms-1"><?= count($pendingConfirmations) ?></span>
            <?php endif; ?>
        </h6>
    </div>
    <div class="card-body p-0">
        <?php if (empty($pendingConfirmations)): ?>
            <div class="text-center text-muted py-4">
                <i class="fas fa-check-double fa-2x mb-2 text-success"></i>
                <p class="mb-0">Todos os pagamentos estão confirmados</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="py-2 ps-3">Pedido</th>
                            <th class="py-2">Cliente</th>
                            <th class="py-2">Parcela</th>
                            <th class="py-2">Valor Pago</th>
                            <th class="py-2">Data Pgto</th>
                            <th class="py-2">Método</th>
                            <th class="py-2">Obs.</th>
                            <th class="py-2 text-end pe-3">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($pendingConfirmations as $pc): ?>
                        <tr>
                            <td class="ps-3 fw-bold">
                                <a href="?page=financial&action=installments&order_id=<?= $pc['order_id'] ?>" class="text-decoration-none">
                                    #<?= str_pad($pc['order_id'], 4, '0', STR_PAD_LEFT) ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($pc['customer_name'] ?? 'N/A') ?></td>
                            <td><span class="badge bg-secondary">Parcela <?= $pc['installment_number'] ?></span></td>
                            <td class="fw-bold text-success">R$ <?= number_format($pc['paid_amount'], 2, ',', '.') ?></td>
                            <td class="small"><?= $pc['paid_date'] ? date('d/m/Y', strtotime($pc['paid_date'])) : '—' ?></td>
                            <td>
                                <?php
                                $methodIcons = [
                                    'pix' => 'fas fa-qrcode',
                                    'boleto' => 'fas fa-barcode',
                                    'cartao_credito' => 'fas fa-credit-card',
                                    'cartao_debito' => 'fas fa-credit-card',
                                    'transferencia' => 'fas fa-university',
                                    'dinheiro' => 'fas fa-money-bill-wave',
                                    'cheque' => 'fas fa-money-check',
                                ];
                                $mIcon = $methodIcons[$pc['payment_method'] ?? ''] ?? 'fas fa-wallet';
                                ?>
                                <span class="small"><i class="<?= $mIcon ?> me-1"></i><?= ucfirst(str_replace('_', ' ', $pc['payment_method'] ?? 'N/A')) ?></span>
                            </td>
                            <td class="small text-muted"><?= htmlspecialchars($pc['notes'] ?? '') ?></td>
                            <td class="text-end pe-3">
                                <form method="post" action="?page=financial&action=confirmPayment" class="d-inline"
                                      onsubmit="return confirm('Confirmar que este pagamento foi recebido?')">
                                    <input type="hidden" name="installment_id" value="<?= $pc['id'] ?>">
                                    <input type="hidden" name="redirect" value="?page=financial">
                                    <button type="submit" class="btn btn-sm btn-success" title="Confirmar Pagamento">
                                        <i class="fas fa-check me-1"></i> Confirmar
                                    </button>
                                </form>
                                <form method="post" action="?page=financial&action=cancelInstallment" class="d-inline ms-1"
                                      onsubmit="return confirm('Estornar este pagamento? A parcela voltará como pendente.')">
                                    <input type="hidden" name="installment_id" value="<?= $pc['id'] ?>">
                                    <input type="hidden" name="order_id" value="<?= $pc['order_id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Estornar">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ══════ Parcelas Vencidas ══════ -->
<div id="overdueSection" class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 py-3">
        <h6 class="mb-0 fw-bold">
            <i class="fas fa-exclamation-triangle me-2 text-danger"></i>Parcelas Vencidas
            <?php if(count($overdueInstallments) > 0): ?>
                <span class="badge bg-danger ms-1"><?= count($overdueInstallments) ?></span>
            <?php endif; ?>
        </h6>
    </div>
    <div class="card-body p-0">
        <?php if (empty($overdueInstallments)): ?>
            <div class="text-center text-muted py-4">
                <i class="fas fa-thumbs-up fa-2x mb-2 text-success"></i>
                <p class="mb-0">Nenhuma parcela vencida</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="py-2 ps-3">Pedido</th>
                            <th class="py-2">Cliente</th>
                            <th class="py-2">Parcela</th>
                            <th class="py-2">Valor</th>
                            <th class="py-2">Vencimento</th>
                            <th class="py-2">Dias Atraso</th>
                            <th class="py-2 text-end pe-3">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($overdueInstallments as $ov): ?>
                        <tr class="table-danger bg-opacity-10">
                            <td class="ps-3 fw-bold">
                                <a href="?page=financial&action=installments&order_id=<?= $ov['order_id'] ?>" class="text-decoration-none">
                                    #<?= str_pad($ov['order_id'], 4, '0', STR_PAD_LEFT) ?>
                                </a>
                            </td>
                            <td>
                                <?= htmlspecialchars($ov['customer_name'] ?? 'N/A') ?>
                                <?php if(!empty($ov['customer_phone'])): ?>
                                    <br><small class="text-muted"><i class="fas fa-phone me-1"></i><?= htmlspecialchars($ov['customer_phone']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge bg-secondary">Parcela <?= $ov['installment_number'] ?></span></td>
                            <td class="fw-bold">R$ <?= number_format($ov['amount'], 2, ',', '.') ?></td>
                            <td class="text-danger small fw-bold"><?= date('d/m/Y', strtotime($ov['due_date'])) ?></td>
                            <td>
                                <span class="badge bg-danger"><?= $ov['days_overdue'] ?> dia(s)</span>
                            </td>
                            <td class="text-end pe-3">
                                <a href="?page=financial&action=installments&order_id=<?= $ov['order_id'] ?>" class="btn btn-sm btn-outline-primary" title="Ver Parcelas">
                                    <i class="fas fa-eye me-1"></i> Detalhes
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ══════ Chart.js ══════ -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const chartData = <?= json_encode($chartData) ?>;
    const labels = chartData.map(d => {
        const [y, m] = d.label.split('-');
        const months = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
        return months[parseInt(m)-1] + '/' + y.substring(2);
    });

    new Chart(document.getElementById('financialChart'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Entradas',
                    data: chartData.map(d => d.entradas),
                    backgroundColor: 'rgba(39, 174, 96, 0.7)',
                    borderColor: 'rgba(39, 174, 96, 1)',
                    borderWidth: 1,
                    borderRadius: 4,
                },
                {
                    label: 'Saídas',
                    data: chartData.map(d => d.saidas),
                    backgroundColor: 'rgba(231, 76, 60, 0.7)',
                    borderColor: 'rgba(231, 76, 60, 1)',
                    borderWidth: 1,
                    borderRadius: 4,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            return ctx.dataset.label + ': R$ ' + ctx.parsed.y.toLocaleString('pt-BR', {minimumFractionDigits: 2});
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'R$ ' + value.toLocaleString('pt-BR');
                        }
                    }
                }
            }
        }
    });
});
</script>
