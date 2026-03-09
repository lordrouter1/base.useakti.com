<?php
/**
 * Financeiro — Dashboard
 * Cards de resumo, alertas de vencimento e atalhos rápidos.
 * Variáveis: $summary, $chartData, $pendingConfirmations, $overdueInstallments, $upcomingInstallments
 */
$month = $_GET['month'] ?? date('m');
$year  = $_GET['year']  ?? date('Y');
$monthNames = ['','Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
?>

<?php if (!empty($_SESSION['flash_success'])): ?>
<script>document.addEventListener('DOMContentLoaded',()=>Swal.fire({icon:'success',title:'Sucesso!',text:'<?= addslashes($_SESSION['flash_success']) ?>',timer:2500,showConfirmButton:false}));</script>
<?php unset($_SESSION['flash_success']); endif; ?>
<?php if (!empty($_SESSION['flash_error'])): ?>
<script>document.addEventListener('DOMContentLoaded',()=>Swal.fire({icon:'error',title:'Erro',text:'<?= addslashes($_SESSION['flash_error']) ?>'}));</script>
<?php unset($_SESSION['flash_error']); endif; ?>

<!-- ══════ Header ══════ -->
<div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <h1 class="h2 mb-0"><i class="fas fa-chart-line me-2 text-primary"></i>Financeiro</h1>
    <div class="btn-toolbar gap-2">
        <a href="?page=financial&action=payments" class="btn btn-sm btn-primary">
            <i class="fas fa-file-invoice-dollar me-1"></i> Pagamentos
        </a>
        <a href="?page=financial&action=transactions" class="btn btn-sm btn-outline-success">
            <i class="fas fa-exchange-alt me-1"></i> Entradas / Saídas
        </a>
    </div>
</div>

<!-- ══════ Filtro Mês/Ano ══════ -->
<form method="get" class="row g-2 mb-4 align-items-end">
    <input type="hidden" name="page" value="financial">
    <div class="col-auto">
        <label class="form-label small fw-bold mb-1">Mês</label>
        <select name="month" class="form-select form-select-sm" style="width:120px">
            <?php for($m=1;$m<=12;$m++): ?>
            <option value="<?= $m ?>" <?= $month==$m?'selected':'' ?>><?= $monthNames[$m] ?></option>
            <?php endfor; ?>
        </select>
    </div>
    <div class="col-auto">
        <label class="form-label small fw-bold mb-1">Ano</label>
        <select name="year" class="form-select form-select-sm" style="width:100px">
            <?php for($y=date('Y')-2;$y<=date('Y')+1;$y++): ?>
            <option value="<?= $y ?>" <?= $year==$y?'selected':'' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i> Filtrar</button>
    </div>
</form>

<!-- ══════ Cards Resumo ══════ -->
<div class="row g-3 mb-4">
    <!-- Receita do Mês -->
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
            <div class="card-body d-flex align-items-center p-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:50px;height:50px;background:rgba(52,152,219,0.15);">
                    <i class="fas fa-dollar-sign fa-lg text-primary"></i>
                </div>
                <div>
                    <div class="text-muted small text-uppercase">Receita do Mês</div>
                    <div class="fw-bold fs-4">R$ <?= number_format($summary['receita_mes'] ?? 0, 2, ',', '.') ?></div>
                </div>
            </div>
        </div>
    </div>
    <!-- Recebido -->
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
            <div class="card-body d-flex align-items-center p-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:50px;height:50px;background:rgba(39,174,96,0.15);">
                    <i class="fas fa-check-circle fa-lg text-success"></i>
                </div>
                <div>
                    <div class="text-muted small text-uppercase">Recebido</div>
                    <div class="fw-bold fs-4">R$ <?= number_format($summary['recebido_mes'] ?? 0, 2, ',', '.') ?></div>
                </div>
            </div>
        </div>
    </div>
    <!-- A Receber -->
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100 border-start border-warning border-4">
            <div class="card-body d-flex align-items-center p-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:50px;height:50px;background:rgba(243,156,18,0.15);">
                    <i class="fas fa-clock fa-lg text-warning"></i>
                </div>
                <div>
                    <div class="text-muted small text-uppercase">A Receber</div>
                    <div class="fw-bold fs-4">R$ <?= number_format($summary['a_receber_total'] ?? 0, 2, ',', '.') ?></div>
                </div>
            </div>
        </div>
    </div>
    <!-- Atrasados -->
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100 border-start border-danger border-4">
            <div class="card-body d-flex align-items-center p-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:50px;height:50px;background:rgba(192,57,43,0.15);">
                    <i class="fas fa-exclamation-triangle fa-lg text-danger"></i>
                </div>
                <div>
                    <div class="text-muted small text-uppercase">Em Atraso</div>
                    <div class="fw-bold fs-4 <?= ($summary['atrasados_total'] ?? 0) > 0 ? 'text-danger' : '' ?>">
                        R$ <?= number_format($summary['atrasados_total'] ?? 0, 2, ',', '.') ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══════ Linha 2: Entradas/Saídas + Confirmações ══════ -->
<div class="row g-3 mb-4">
    <!-- Entradas x Saídas -->
    <div class="col-xl-4 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3">
                <?php
                    $totalEntradas = ($summary['entradas_mes'] ?? 0) + ($summary['recebido_mes'] ?? 0);
                    $totalSaidas   = $summary['saidas_mes'] ?? 0;
                    $saldo         = $totalEntradas - $totalSaidas;
                ?>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small text-uppercase fw-bold">Entradas</span>
                    <span class="fw-bold text-success">R$ <?= number_format($totalEntradas, 2, ',', '.') ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small text-uppercase fw-bold">Saídas</span>
                    <span class="fw-bold text-danger">R$ <?= number_format($totalSaidas, 2, ',', '.') ?></span>
                </div>
                <hr class="my-2">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-bold small text-uppercase">Saldo</span>
                    <span class="fw-bold fs-5 <?= $saldo >= 0 ? 'text-success' : 'text-danger' ?>">
                        R$ <?= number_format($saldo, 2, ',', '.') ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
    <!-- Pendentes de Confirmação -->
    <div class="col-xl-4 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom p-3">
                <h6 class="mb-0 fw-bold text-warning"><i class="fas fa-user-check me-2"></i>Aguardando Confirmação</h6>
            </div>
            <div class="card-body p-0">
                <?php if (empty($pendingConfirmations)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-check-circle fa-2x d-block mb-2 text-success opacity-50"></i>
                        <small>Nenhum pagamento pendente</small>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush" style="max-height:200px;overflow-y:auto;">
                        <?php foreach (array_slice($pendingConfirmations, 0, 5) as $pc): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                            <div>
                                <a href="?page=financial&action=installments&order_id=<?= $pc['order_id'] ?>" class="text-decoration-none fw-bold small">
                                    #<?= str_pad($pc['order_id'], 4, '0', STR_PAD_LEFT) ?>
                                </a>
                                <span class="text-muted small ms-1"><?= htmlspecialchars($pc['customer_name'] ?? '') ?></span>
                                <div class="text-muted" style="font-size:0.7rem;">Parcela <?= $pc['installment_number'] ?> — R$ <?= number_format($pc['paid_amount'] ?? $pc['amount'], 2, ',', '.') ?></div>
                            </div>
                            <form method="post" action="?page=financial&action=confirmPayment" class="d-inline confirm-form">
                                <?= csrf_field() ?>
                                <input type="hidden" name="installment_id" value="<?= $pc['id'] ?>">
                                <input type="hidden" name="redirect" value="?page=financial">
                                <button type="submit" class="btn btn-sm btn-outline-success btn-confirm-payment" title="Confirmar">
                                    <i class="fas fa-check"></i>
                                </button>
                            </form>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($pendingConfirmations) > 5): ?>
                    <div class="p-2 text-center border-top">
                        <a href="?page=financial&action=payments&status=pendente" class="text-warning small fw-bold">
                            Ver todos (<?= count($pendingConfirmations) ?>) <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- Pedidos com Pgto Pendente -->
    <div class="col-xl-4 col-md-12">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom p-3">
                <h6 class="mb-0 fw-bold text-info"><i class="fas fa-file-invoice me-2"></i>Pedidos Pendentes</h6>
            </div>
            <div class="card-body p-3 text-center">
                <div class="fw-bold fs-2 text-info"><?= $summary['pedidos_pendentes_pgto'] ?? 0 ?></div>
                <div class="text-muted small">pedidos no financeiro sem pagamento total</div>
                <a href="?page=financial&action=payments&status=pendente" class="btn btn-sm btn-outline-info mt-2">
                    <i class="fas fa-eye me-1"></i> Ver Pagamentos
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ══════ Parcelas Atrasadas ══════ -->
<?php if (!empty($overdueInstallments)): ?>
<div class="card border-0 shadow-sm mb-4 border-start border-danger border-4">
    <div class="card-header bg-danger p-3">
        <h6 class="mb-0 text-white"><i class="fas fa-exclamation-triangle me-2"></i>Parcelas em Atraso (<?= count($overdueInstallments) ?>)</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-3 small fw-bold">Pedido</th>
                        <th class="small fw-bold">Cliente</th>
                        <th class="small fw-bold">Parcela</th>
                        <th class="small fw-bold">Vencimento</th>
                        <th class="small fw-bold">Valor</th>
                        <th class="small fw-bold">Atraso</th>
                        <th class="text-end pe-3 small fw-bold">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($overdueInstallments, 0, 10) as $ov): ?>
                    <tr>
                        <td class="ps-3 fw-bold">
                            <a href="?page=financial&action=installments&order_id=<?= $ov['order_id'] ?>" class="text-decoration-none">#<?= str_pad($ov['order_id'], 4, '0', STR_PAD_LEFT) ?></a>
                        </td>
                        <td class="small"><?= htmlspecialchars($ov['customer_name'] ?? '—') ?></td>
                        <td><span class="badge bg-secondary"><?= $ov['installment_number'] ?>ª</span></td>
                        <td class="small text-danger fw-bold"><?= date('d/m/Y', strtotime($ov['due_date'])) ?></td>
                        <td class="fw-bold">R$ <?= number_format($ov['amount'], 2, ',', '.') ?></td>
                        <td><span class="badge bg-danger rounded-pill">+<?= $ov['days_overdue'] ?> dias</span></td>
                        <td class="text-end pe-3">
                            <a href="?page=financial&action=installments&order_id=<?= $ov['order_id'] ?>" class="btn btn-sm btn-outline-danger">
                                <i class="fas fa-hand-holding-usd me-1"></i> Cobrar
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ══════ Próximos Vencimentos ══════ -->
<?php if (!empty($upcomingInstallments)): ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom p-3">
        <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-calendar-alt me-2"></i>Próximos Vencimentos (7 dias)</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-3 small fw-bold">Pedido</th>
                        <th class="small fw-bold">Cliente</th>
                        <th class="small fw-bold">Parcela</th>
                        <th class="small fw-bold">Vencimento</th>
                        <th class="small fw-bold">Valor</th>
                        <th class="text-end pe-3 small fw-bold">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($upcomingInstallments as $up): ?>
                    <tr>
                        <td class="ps-3 fw-bold">
                            <a href="?page=financial&action=installments&order_id=<?= $up['order_id'] ?>" class="text-decoration-none">#<?= str_pad($up['order_id'], 4, '0', STR_PAD_LEFT) ?></a>
                        </td>
                        <td class="small"><?= htmlspecialchars($up['customer_name'] ?? '—') ?></td>
                        <td><span class="badge bg-secondary"><?= $up['installment_number'] ?>ª</span></td>
                        <td class="small"><?= date('d/m/Y', strtotime($up['due_date'])) ?></td>
                        <td class="fw-bold">R$ <?= number_format($up['amount'], 2, ',', '.') ?></td>
                        <td class="text-end pe-3">
                            <a href="?page=financial&action=installments&order_id=<?= $up['order_id'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye me-1"></i> Ver
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ══════ Gráfico Entradas x Saídas ══════ -->
<?php if (!empty($chartData)): ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom p-3">
        <h5 class="mb-0 text-primary fw-bold"><i class="fas fa-chart-bar me-2"></i>Receita x Despesa (últimos 6 meses)</h5>
    </div>
    <div class="card-body p-3">
        <canvas id="finChart" height="100"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('finChart');
    if (!ctx) return;
    const labels = <?= json_encode(array_column($chartData, 'label')) ?>;
    const entradas = <?= json_encode(array_column($chartData, 'entradas')) ?>;
    const saidas   = <?= json_encode(array_column($chartData, 'saidas')) ?>;
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels.map(l => { const [y,m] = l.split('-'); return ['','Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'][+m]+'/'+y; }),
            datasets: [
                { label: 'Entradas', data: entradas, backgroundColor: 'rgba(39,174,96,0.7)', borderRadius: 6 },
                { label: 'Saídas',   data: saidas,   backgroundColor: 'rgba(192,57,43,0.7)', borderRadius: 6 },
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } },
            scales: { y: { beginAtZero: true, ticks: { callback: v => 'R$ '+v.toLocaleString('pt-BR') } } }
        }
    });
});
</script>
<?php endif; ?>

<!-- ══════ Confirmação via SweetAlert2 ══════ -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-confirm-payment').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const form = this.closest('form');
            Swal.fire({
                title: 'Confirmar pagamento?',
                text: 'Essa parcela será marcada como confirmada.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#27ae60',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-check me-1"></i> Confirmar',
                cancelButtonText: 'Cancelar'
            }).then(result => {
                if (result.isConfirmed) form.submit();
            });
        });
    });
});
</script>
