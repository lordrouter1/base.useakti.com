<?php
/**
 * Financeiro — Parcelas de um Pedido
 * Fluxo simplificado: as parcelas já vêm do pedido (pipeline).
 * Aqui o operador apenas confirma/estorna pagamentos.
 * Variáveis: $order, $installments
 */
$orderId   = $order['id'] ?? 0;
$orderNet  = ($order['total_amount'] ?? 0) - ($order['discount'] ?? 0);
$totalPago = 0;
$totalParcelas = count($installments ?? []);
$parcelasPagas = 0;
foreach ($installments as $inst) {
    if ($inst['status'] === 'pago') {
        $totalPago += (float)($inst['paid_amount'] ?? $inst['amount']);
        $parcelasPagas++;
    }
}
$pctPaid = $orderNet > 0 ? min(100, round(($totalPago / $orderNet) * 100)) : 0;
$restante = $orderNet - $totalPago;

$statusMap = [
    'pendente'  => ['badge' => 'bg-warning text-dark', 'icon' => 'fas fa-clock',               'label' => 'Pendente'],
    'pago'      => ['badge' => 'bg-success',            'icon' => 'fas fa-check-circle',        'label' => 'Pago'],
    'atrasado'  => ['badge' => 'bg-danger',             'icon' => 'fas fa-exclamation-triangle', 'label' => 'Atrasado'],
    'cancelado' => ['badge' => 'bg-secondary',          'icon' => 'fas fa-ban',                 'label' => 'Cancelado'],
];

$methodLabels = [
    'dinheiro'       => '💵 Dinheiro',
    'pix'            => '📱 PIX',
    'cartao_credito' => '💳 Cartão Crédito',
    'cartao_debito'  => '💳 Cartão Débito',
    'boleto'         => '📄 Boleto',
    'transferencia'  => '🏦 Transferência',
];
?>

<?php if (!empty($_SESSION['flash_success'])): ?>
<script>document.addEventListener('DOMContentLoaded',()=>Swal.fire({icon:'success',title:'Sucesso!',text:'<?= addslashes($_SESSION['flash_success']) ?>',timer:2500,showConfirmButton:false}));</script>
<?php unset($_SESSION['flash_success']); endif; ?>
<?php if (!empty($_SESSION['flash_error'])): ?>
<script>document.addEventListener('DOMContentLoaded',()=>Swal.fire({icon:'error',title:'Erro',text:'<?= addslashes($_SESSION['flash_error']) ?>'}));</script>
<?php unset($_SESSION['flash_error']); endif; ?>

<!-- ══════ Header ══════ -->
<div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <h1 class="h2 mb-0">
        <i class="fas fa-file-invoice-dollar me-2 text-primary"></i>
        Parcelas — Pedido #<?= str_pad($orderId, 4, '0', STR_PAD_LEFT) ?>
    </h1>
    <div class="btn-toolbar gap-2">
        <a href="?page=financial&action=payments" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Voltar
        </a>
        <a href="?page=pipeline&action=detail&id=<?= $orderId ?>" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-eye me-1"></i> Ver Pedido
        </a>
    </div>
</div>

<!-- ══════ Resumo do Pedido (cards estilo dashboard) ══════ -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
            <div class="card-body d-flex align-items-center p-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:50px;height:50px;background:rgba(52,152,219,0.15);">
                    <i class="fas fa-receipt fa-lg text-primary"></i>
                </div>
                <div>
                    <div class="text-muted small text-uppercase">Valor Total</div>
                    <div class="fw-bold fs-4">R$ <?= number_format($orderNet, 2, ',', '.') ?></div>
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
                    <div class="text-muted small text-uppercase">Total Pago</div>
                    <div class="fw-bold fs-4">R$ <?= number_format($totalPago, 2, ',', '.') ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100 border-start border-warning border-4">
            <div class="card-body d-flex align-items-center p-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:50px;height:50px;background:rgba(243,156,18,0.15);">
                    <i class="fas fa-hourglass-half fa-lg text-warning"></i>
                </div>
                <div>
                    <div class="text-muted small text-uppercase">Restante</div>
                    <div class="fw-bold fs-4 <?= $restante > 0 ? 'text-warning' : 'text-success' ?>">
                        R$ <?= number_format(max(0, $restante), 2, ',', '.') ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100 border-start border-info border-4">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted small text-uppercase fw-bold">Progresso</span>
                    <span class="fw-bold"><?= $parcelasPagas ?>/<?= $totalParcelas ?></span>
                </div>
                <div class="progress mb-2" style="height:10px;">
                    <div class="progress-bar <?= $pctPaid >= 100 ? 'bg-success' : ($pctPaid > 0 ? 'bg-info' : 'bg-secondary') ?>" style="width:<?= $pctPaid ?>%"></div>
                </div>
                <div class="text-center fw-bold <?= $pctPaid >= 100 ? 'text-success' : 'text-info' ?>"><?= $pctPaid ?>%</div>
            </div>
        </div>
    </div>
</div>

<!-- ══════ Info Pedido Compacto ══════ -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <div class="row g-3 text-center">
            <div class="col-md-3">
                <div class="text-muted small text-uppercase fw-bold">Cliente</div>
                <div class="fw-bold"><?= htmlspecialchars($order['customer_name'] ?? 'N/A') ?></div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small text-uppercase fw-bold">Forma de Pagamento</div>
                <div class="fw-bold"><?= $methodLabels[$order['payment_method'] ?? ''] ?? ucfirst($order['payment_method'] ?? 'Não definida') ?></div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small text-uppercase fw-bold">Parcelas Definidas</div>
                <div class="fw-bold"><?= $order['installments'] ?? 1 ?>x</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small text-uppercase fw-bold">Data do Pedido</div>
                <div class="fw-bold"><?= !empty($order['created_at']) ? date('d/m/Y', strtotime($order['created_at'])) : '—' ?></div>
            </div>
        </div>
    </div>
</div>

<!-- ══════ Tabela de Parcelas ══════ -->
<?php if (empty($installments)): ?>
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5">
        <i class="fas fa-inbox fa-3x text-muted mb-3 d-block opacity-50"></i>
        <h5 class="text-muted">Nenhuma parcela gerada</h5>
        <p class="text-muted small mb-3">As parcelas são geradas automaticamente no detalhe do pedido (pipeline).</p>
        <a href="?page=pipeline&action=detail&id=<?= $orderId ?>" class="btn btn-primary">
            <i class="fas fa-cog me-1"></i> Ir para o Pedido
        </a>
    </div>
</div>
<?php else: ?>
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom p-3">
        <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-list-ol me-2"></i>Parcelas</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-3 py-3" style="width:60px;">#</th>
                        <th class="py-3">Vencimento</th>
                        <th class="py-3">Valor</th>
                        <th class="py-3">Pago em</th>
                        <th class="py-3">Valor Pago</th>
                        <th class="py-3">Método</th>
                        <th class="py-3">Status</th>
                        <th class="py-3">Confirmação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($installments as $inst):
                        $st = $statusMap[$inst['status']] ?? $statusMap['pendente'];
                        $isEntrada = ($inst['installment_number'] == 0);
                    ?>
                    <tr class="<?= $inst['status'] === 'atrasado' ? 'table-danger' : '' ?>">
                        <td class="ps-3 fw-bold">
                            <?php if ($isEntrada): ?>
                                <span class="badge bg-info">Entrada</span>
                            <?php else: ?>
                                <?= $inst['installment_number'] ?>ª
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= date('d/m/Y', strtotime($inst['due_date'])) ?>
                            <?php if ($inst['status'] === 'atrasado'): ?>
                                <?php $diasAtraso = (int)((time() - strtotime($inst['due_date'])) / 86400); ?>
                                <span class="badge bg-danger rounded-pill ms-1" style="font-size:0.65rem;">+<?= $diasAtraso ?>d</span>
                            <?php endif; ?>
                        </td>
                        <td class="fw-bold">R$ <?= number_format($inst['amount'], 2, ',', '.') ?></td>
                        <td class="small">
                            <?= $inst['paid_date'] ? date('d/m/Y', strtotime($inst['paid_date'])) : '<span class="text-muted">—</span>' ?>
                        </td>
                        <td>
                            <?php if ($inst['paid_amount']): ?>
                                <span class="fw-bold text-success">R$ <?= number_format($inst['paid_amount'], 2, ',', '.') ?></span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="small"><?= $methodLabels[$inst['payment_method'] ?? ''] ?? ($inst['payment_method'] ? ucfirst($inst['payment_method']) : '<span class="text-muted">—</span>') ?></td>
                        <td><span class="badge <?= $st['badge'] ?>"><i class="<?= $st['icon'] ?> me-1"></i><?= $st['label'] ?></span></td>
                        <td>
                            <?php if ($inst['status'] === 'pago' && $inst['is_confirmed']): ?>
                                <span class="text-success small" title="Confirmado por <?= htmlspecialchars($inst['confirmed_by_name'] ?? '—') ?> em <?= $inst['confirmed_at'] ? date('d/m/Y H:i', strtotime($inst['confirmed_at'])) : '' ?>">
                                    <i class="fas fa-check-double me-1"></i>Confirmado
                                </span>
                            <?php elseif ($inst['status'] === 'pago' && !$inst['is_confirmed']): ?>
                                <span class="badge bg-warning text-dark"><i class="fas fa-user-clock me-1"></i>Aguardando</span>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>
