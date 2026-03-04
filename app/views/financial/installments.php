<?php
/**
 * Fiscal — Parcelas do Pedido
 * Variáveis: $order, $installments
 */
$orderId = $order['id'] ?? 0;
?>

<?php if (!empty($_SESSION['flash_success'])): ?>
<script>document.addEventListener('DOMContentLoaded', () => Swal.fire({ icon:'success', title:'Sucesso!', text:'<?= addslashes($_SESSION['flash_success']) ?>', timer:2500, showConfirmButton:false }));</script>
<?php unset($_SESSION['flash_success']); endif; ?>
<?php if (!empty($_SESSION['flash_error'])): ?>
<script>document.addEventListener('DOMContentLoaded', () => Swal.fire({ icon:'error', title:'Erro', text:'<?= addslashes($_SESSION['flash_error']) ?>' }));</script>
<?php unset($_SESSION['flash_error']); endif; ?>

<?php
$net = ($order['total_amount'] ?? 0) - ($order['discount'] ?? 0);
$totalPago = 0;
$totalConfirmado = 0;
foreach ($installments as $inst) {
    if ($inst['status'] === 'pago') {
        $totalPago += (float)$inst['paid_amount'];
        if ($inst['is_confirmed']) $totalConfirmado += (float)$inst['paid_amount'];
    }
}
$saldo = $net - $totalPago;

$statusColors = [
    'pendente' => ['bg' => 'bg-warning text-dark', 'icon' => 'fas fa-clock',           'label' => 'Pendente'],
    'pago'     => ['bg' => 'bg-success',            'icon' => 'fas fa-check-circle',    'label' => 'Pago'],
    'atrasado' => ['bg' => 'bg-danger',             'icon' => 'fas fa-exclamation-triangle', 'label' => 'Atrasado'],
];

$methodLabels = [
    'dinheiro' => ['icon' => 'fas fa-money-bill-wave', 'label' => 'Dinheiro'],
    'pix' => ['icon' => 'fas fa-qrcode', 'label' => 'PIX'],
    'boleto' => ['icon' => 'fas fa-barcode', 'label' => 'Boleto'],
    'cartao_credito' => ['icon' => 'fas fa-credit-card', 'label' => 'Cartão Crédito'],
    'cartao_debito' => ['icon' => 'fas fa-credit-card', 'label' => 'Cartão Débito'],
    'transferencia' => ['icon' => 'fas fa-university', 'label' => 'Transferência'],
    'cheque' => ['icon' => 'fas fa-money-check', 'label' => 'Cheque'],
    'gateway' => ['icon' => 'fas fa-globe', 'label' => 'Gateway Online'],
];
?>

<!-- ══════ Header ══════ -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-file-invoice me-2 text-primary"></i>
        Parcelas — Pedido #<?= str_pad($orderId, 4, '0', STR_PAD_LEFT) ?>
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0 gap-2">
        <a href="?page=financial&action=payments" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Voltar
        </a>
        <a href="?page=pipeline&action=detail&id=<?= $orderId ?>" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-eye me-1"></i> Ver Pedido
        </a>
    </div>
</div>

<!-- ══════ Resumo do Pedido ══════ -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="card border-start border-primary border-4 h-100">
            <div class="card-body py-3 px-3">
                <div class="text-muted small fw-bold text-uppercase">Valor Total</div>
                <div class="h5 mb-0 text-primary">R$ <?= number_format($net, 2, ',', '.') ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-start border-success border-4 h-100">
            <div class="card-body py-3 px-3">
                <div class="text-muted small fw-bold text-uppercase">Total Pago</div>
                <div class="h5 mb-0 text-success">R$ <?= number_format($totalPago, 2, ',', '.') ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-start border-warning border-4 h-100">
            <div class="card-body py-3 px-3">
                <div class="text-muted small fw-bold text-uppercase">Saldo Restante</div>
                <div class="h5 mb-0 text-warning">R$ <?= number_format(max(0, $saldo), 2, ',', '.') ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-start border-info border-4 h-100">
            <div class="card-body py-3 px-3">
                <div class="text-muted small fw-bold text-uppercase">Cliente</div>
                <div class="h6 mb-0 text-info"><?= htmlspecialchars($order['customer_name'] ?? 'N/A') ?></div>
            </div>
        </div>
    </div>
</div>

<!-- ══════ Barra de Progresso ══════ -->
<?php $pctPaid = $net > 0 ? min(100, round(($totalPago / $net) * 100)) : 0; ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="fw-bold small">Progresso de Pagamento</span>
            <span class="fw-bold"><?= $pctPaid ?>% pago</span>
        </div>
        <div class="progress" style="height: 12px;">
            <div class="progress-bar <?= $pctPaid >= 100 ? 'bg-success' : ($pctPaid > 0 ? 'bg-primary' : 'bg-secondary') ?> progress-bar-striped" 
                 style="width:<?= $pctPaid ?>%" role="progressbar"></div>
        </div>
    </div>
</div>

<!-- ══════ Botão Gerar/Regerar Parcelas ══════ -->
<?php if (($order['payment_status'] ?? 'pendente') !== 'pago'): ?>
<div class="mb-4">
    <button class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#collapseGenerate">
        <i class="fas fa-calculator me-1"></i> 
        <?= count($installments) > 0 ? 'Regerar Parcelas' : 'Gerar Parcelas' ?>
    </button>
    <?php if(count($installments) > 0): ?>
        <small class="text-muted ms-2"><i class="fas fa-info-circle me-1"></i>Regerar irá substituir as parcelas atuais (exceto as já pagas e confirmadas)</small>
    <?php endif; ?>

    <div class="collapse mt-3 <?= count($installments) == 0 ? 'show' : '' ?>" id="collapseGenerate">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form method="post" action="?page=financial&action=generateInstallments">
                    <input type="hidden" name="order_id" value="<?= $orderId ?>">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Nº de Parcelas</label>
                            <input type="number" name="num_installments" class="form-control" min="1" max="48" value="<?= $order['installments'] ?? 1 ?>" required id="instNum">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Entrada (R$)</label>
                            <input type="number" name="down_payment" class="form-control" min="0" step="0.01" value="0" id="instDP">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Data Início</label>
                            <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Valor Estimado / Parcela</label>
                            <input type="text" class="form-control bg-light" id="instCalcValue" readonly>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary" onclick="return confirm('Gerar parcelas? Isso substituirá as parcelas não pagas.')">
                            <i class="fas fa-check me-1"></i> Gerar Parcelas
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ══════ Timeline de Parcelas ══════ -->
<?php if (!empty($installments)): ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 py-3">
        <h6 class="mb-0 fw-bold"><i class="fas fa-list-ol me-2 text-primary"></i>Parcelas</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="py-3 ps-3">#</th>
                        <th class="py-3">Vencimento</th>
                        <th class="py-3">Valor</th>
                        <th class="py-3">Status</th>
                        <th class="py-3">Data Pgto</th>
                        <th class="py-3">Valor Pago</th>
                        <th class="py-3">Método</th>
                        <th class="py-3">Confirmação</th>
                        <th class="py-3 text-end pe-3">Ações</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($installments as $inst):
                    $sc = $statusColors[$inst['status']] ?? $statusColors['pendente'];
                    $isOverdue = ($inst['status'] === 'pendente' || $inst['status'] === 'atrasado') && strtotime($inst['due_date']) < time();
                    $isPaid = $inst['status'] === 'pago';
                    $isConfirmed = $inst['is_confirmed'] == 1;
                    $mm = $methodLabels[$inst['payment_method'] ?? ''] ?? null;
                ?>
                <tr class="<?= $isOverdue ? 'table-danger bg-opacity-10' : '' ?> <?= ($isPaid && $isConfirmed) ? 'table-success bg-opacity-10' : '' ?>">
                    <td class="ps-3">
                        <?php if ($inst['installment_number'] == 0): ?>
                            <span class="badge bg-dark">Entrada</span>
                        <?php else: ?>
                            <span class="badge bg-secondary"><?= $inst['installment_number'] ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="<?= $isOverdue ? 'text-danger fw-bold' : '' ?>">
                            <?= date('d/m/Y', strtotime($inst['due_date'])) ?>
                        </span>
                        <?php if ($isOverdue): ?>
                            <br><small class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i>Vencida</small>
                        <?php endif; ?>
                    </td>
                    <td class="fw-bold">R$ <?= number_format($inst['amount'], 2, ',', '.') ?></td>
                    <td>
                        <span class="badge <?= $sc['bg'] ?>">
                            <i class="<?= $sc['icon'] ?> me-1"></i><?= $sc['label'] ?>
                        </span>
                    </td>
                    <td class="small"><?= $inst['paid_date'] ? date('d/m/Y', strtotime($inst['paid_date'])) : '—' ?></td>
                    <td class="fw-bold <?= $isPaid ? 'text-success' : '' ?>">
                        <?= $inst['paid_amount'] ? 'R$ ' . number_format($inst['paid_amount'], 2, ',', '.') : '—' ?>
                    </td>
                    <td>
                        <?php if ($mm): ?>
                            <span class="small"><i class="<?= $mm['icon'] ?> me-1"></i><?= $mm['label'] ?></span>
                        <?php else: ?>
                            <span class="text-muted small">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($isPaid && $isConfirmed): ?>
                            <span class="badge bg-success"><i class="fas fa-check-double me-1"></i>Confirmado</span>
                            <?php if ($inst['confirmed_by_name']): ?>
                                <br><small class="text-muted">por <?= htmlspecialchars($inst['confirmed_by_name']) ?></small>
                            <?php endif; ?>
                        <?php elseif ($isPaid && !$isConfirmed): ?>
                            <span class="badge bg-warning text-dark"><i class="fas fa-hourglass-half me-1"></i>Aguardando</span>
                        <?php else: ?>
                            <span class="text-muted small">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end pe-3">
                        <?php if (!$isPaid): ?>
                            <!-- Registrar Pagamento -->
                            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#payModal"
                                    data-inst-id="<?= $inst['id'] ?>"
                                    data-inst-num="<?= $inst['installment_number'] ?>"
                                    data-inst-amount="<?= $inst['amount'] ?>"
                                    title="Registrar Pagamento">
                                <i class="fas fa-dollar-sign me-1"></i> Pagar
                            </button>
                        <?php elseif ($isPaid && !$isConfirmed): ?>
                            <!-- Confirmar Pagamento -->
                            <form method="post" action="?page=financial&action=confirmPayment" class="d-inline"
                                  onsubmit="return confirm('Confirmar que este pagamento foi recebido?')">
                                <input type="hidden" name="installment_id" value="<?= $inst['id'] ?>">
                                <input type="hidden" name="redirect" value="?page=financial&action=installments&order_id=<?= $orderId ?>">
                                <button type="submit" class="btn btn-sm btn-success" title="Confirmar">
                                    <i class="fas fa-check me-1"></i> Confirmar
                                </button>
                            </form>
                            <form method="post" action="?page=financial&action=cancelInstallment" class="d-inline ms-1"
                                  onsubmit="return confirm('Estornar este pagamento?')">
                                <input type="hidden" name="installment_id" value="<?= $inst['id'] ?>">
                                <input type="hidden" name="order_id" value="<?= $orderId ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Estornar">
                                    <i class="fas fa-undo"></i>
                                </button>
                            </form>
                        <?php elseif ($isPaid && $isConfirmed && $inst['installment_number'] > 0): ?>
                            <!-- Estornar -->
                            <form method="post" action="?page=financial&action=cancelInstallment" class="d-inline"
                                  onsubmit="return confirm('Estornar este pagamento? A parcela voltará como pendente.')">
                                <input type="hidden" name="installment_id" value="<?= $inst['id'] ?>">
                                <input type="hidden" name="order_id" value="<?= $orderId ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Estornar">
                                    <i class="fas fa-undo me-1"></i> Estornar
                                </button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php else: ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body text-center py-5">
        <i class="fas fa-file-invoice fa-3x text-muted mb-3"></i>
        <h5 class="text-muted">Nenhuma parcela gerada</h5>
        <p class="text-muted">Use o formulário acima para gerar as parcelas deste pedido.</p>
    </div>
</div>
<?php endif; ?>

<!-- ══════ Modal: Registrar Pagamento ══════ -->
<div class="modal fade" id="payModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="?page=financial&action=payInstallment">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-dollar-sign me-2 text-success"></i>Registrar Pagamento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="installment_id" id="payInstId">
                    <input type="hidden" name="order_id" value="<?= $orderId ?>">

                    <div class="alert alert-info py-2 mb-3">
                        <strong>Parcela:</strong> <span id="payInstLabel"></span> — 
                        <strong>Valor:</strong> R$ <span id="payInstAmount"></span>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Data Pagamento</label>
                            <input type="date" name="paid_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Valor Pago (R$)</label>
                            <input type="number" name="paid_amount" id="payAmount" class="form-control" step="0.01" min="0.01" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Método de Pagamento</label>
                            <select name="payment_method" class="form-select" required>
                                <option value="dinheiro">Dinheiro</option>
                                <option value="pix">PIX</option>
                                <option value="boleto">Boleto</option>
                                <option value="cartao_credito">Cartão de Crédito</option>
                                <option value="cartao_debito">Cartão de Débito</option>
                                <option value="transferencia">Transferência Bancária</option>
                                <option value="cheque">Cheque</option>
                                <option value="gateway">Gateway Online</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Ref. Gateway <small class="text-muted">(opcional)</small></label>
                            <input type="text" name="gateway_reference" class="form-control" placeholder="ID da transação no gateway">
                            <small class="text-muted">Se preenchido, o pagamento será confirmado automaticamente.</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Observações</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Observações sobre o pagamento..."></textarea>
                        </div>
                    </div>

                    <div class="alert alert-warning py-2 mt-3 mb-0">
                        <i class="fas fa-info-circle me-1"></i>
                        Pagamentos sem referência de gateway precisarão de <strong>confirmação manual</strong> posterior.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-check me-1"></i> Registrar Pagamento</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Calcular valor parcela no form de gerar
    const netTotal = <?= $net ?>;
    function calcInstVal() {
        const num = parseInt(document.getElementById('instNum')?.value || 1);
        const dp = parseFloat(document.getElementById('instDP')?.value || 0);
        const val = num > 0 ? ((netTotal - dp) / num) : netTotal;
        const el = document.getElementById('instCalcValue');
        if (el) el.value = 'R$ ' + Math.max(0, val).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }
    document.getElementById('instNum')?.addEventListener('input', calcInstVal);
    document.getElementById('instDP')?.addEventListener('input', calcInstVal);
    calcInstVal();

    // Modal pagamento
    const payModal = document.getElementById('payModal');
    if (payModal) {
        payModal.addEventListener('show.bs.modal', function(e) {
            const btn = e.relatedTarget;
            const instId = btn.getAttribute('data-inst-id');
            const instNum = btn.getAttribute('data-inst-num');
            const instAmt = parseFloat(btn.getAttribute('data-inst-amount'));
            document.getElementById('payInstId').value = instId;
            document.getElementById('payInstLabel').textContent = instNum == 0 ? 'Entrada' : ('Parcela ' + instNum);
            document.getElementById('payInstAmount').textContent = instAmt.toLocaleString('pt-BR', {minimumFractionDigits: 2});
            document.getElementById('payAmount').value = instAmt.toFixed(2);
        });
    }
});
</script>
