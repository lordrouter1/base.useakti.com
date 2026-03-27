<?php
/**
 * Financeiro — Parcelas de um Pedido
 * Fluxo simplificado: as parcelas já vêm do pedido (pipeline).
 * Aqui o operador pode confirmar/estornar pagamentos, unificar ou dividir parcelas.
 * Variáveis: $order, $installments
 */
$orderId   = $order['id'] ?? 0;
$orderNet  = ($order['total_amount'] ?? 0) - ($order['discount'] ?? 0);
$totalPago = 0;
$totalParcelas = count($installments ?? []);
$parcelasPagas = 0;
$openInstallments = [];
foreach ($installments as $inst) {
    if ($inst['status'] === 'pago') {
        $totalPago += (float)($inst['paid_amount'] ?? $inst['amount']);
        $parcelasPagas++;
    }
    if (in_array($inst['status'], ['pendente', 'atrasado'])) {
        $openInstallments[] = $inst;
    }
}
$pctPaid = $orderNet > 0 ? min(100, round(($totalPago / $orderNet) * 100)) : 0;
$restante = $orderNet - $totalPago;
$hasOpenInstallments = count($openInstallments) > 0;

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
    'gateway'        => '🌐 Gateway Online',
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
        <?php
        // Verificar se existe NF-e vinculada ao pedido
        $nfeLinked = null;
        try {
            $nfeDocModel = new \Akti\Models\NfeDocument((new Database())->getConnection());
            $nfeLinked = $nfeDocModel->readByOrder($orderId);
        } catch (\Throwable $e) {}
        ?>
        <?php if ($nfeLinked): ?>
        <a href="?page=nfe_documents&action=detail&id=<?= $nfeLinked['id'] ?>" class="btn btn-sm btn-outline-success" title="NF-e vinculada">
            <i class="fas fa-file-invoice me-1"></i> NF-e #<?= e($nfeLinked['numero'] ?? $nfeLinked['id']) ?>
            <?php 
            $nfeBadgeColor = match($nfeLinked['status'] ?? '') {
                'autorizada' => 'bg-success',
                'cancelada'  => 'bg-dark',
                'rejeitada'  => 'bg-danger',
                'processando'=> 'bg-info',
                default      => 'bg-secondary',
            };
            ?>
            <span class="badge <?= $nfeBadgeColor ?> ms-1" style="font-size:0.6rem;"><?= ucfirst($nfeLinked['status'] ?? 'N/A') ?></span>
        </a>
        <?php endif; ?>
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
                <div class="fw-bold"><?= e($order['customer_name'] ?? 'N/A') ?></div>
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

<!-- ══════ Toolbar de ações: Unificar / Dividir ══════ -->
<?php if ($hasOpenInstallments): ?>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body p-3">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h6 class="mb-0 fw-bold text-secondary"><i class="fas fa-tools me-2"></i>Gerenciar Parcelas em Aberto</h6>
                <small class="text-muted"><?= count($openInstallments) ?> parcela(s) em aberto disponíveis para alteração</small>
            </div>
            <div class="d-flex gap-2">
                <?php if (count($openInstallments) >= 2): ?>
                <button type="button" class="btn btn-sm btn-outline-primary" id="btnStartMerge">
                    <i class="fas fa-compress-arrows-alt me-1"></i> Unificar Parcelas
                </button>
                <?php endif; ?>
                <button type="button" class="btn btn-sm btn-outline-warning text-dark" id="btnStartSplit" disabled>
                    <i class="fas fa-expand-arrows-alt me-1"></i> Dividir Parcela
                </button>
            </div>
        </div>

        <!-- ── Painel de Unificação (hidden por padrão) ── -->
        <div id="mergePanel" class="mt-3" style="display:none;">
            <div class="alert alert-primary py-2 px-3 mb-2">
                <i class="fas fa-info-circle me-1"></i>
                <strong>Unificar:</strong> Selecione 2 ou mais parcelas em aberto para unificá-las em uma única parcela.
            </div>
            <div class="row g-2 align-items-end">
                <div class="col-auto">
                    <label class="form-label small fw-bold mb-1">Parcelas selecionadas:</label>
                    <div id="mergeSelectedBadges" class="d-flex flex-wrap gap-1">
                        <span class="text-muted small">Clique nas parcelas abaixo...</span>
                    </div>
                </div>
                <div class="col-auto">
                    <label class="form-label small fw-bold mb-1">Soma:</label>
                    <span class="fw-bold text-primary" id="mergeTotalDisplay">R$ 0,00</span>
                </div>
                <div class="col-auto">
                    <label class="form-label small fw-bold mb-1">Vencimento da nova parcela</label>
                    <input type="date" class="form-control form-control-sm" id="mergeDueDate" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-sm btn-primary" id="btnConfirmMerge" disabled>
                        <i class="fas fa-check me-1"></i> Confirmar Unificação
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btnCancelMerge">
                        <i class="fas fa-times me-1"></i> Cancelar
                    </button>
                </div>
            </div>
        </div>

        <!-- ── Painel de Divisão (hidden por padrão) ── -->
        <div id="splitPanel" class="mt-3" style="display:none;">
            <div class="alert alert-warning py-2 px-3 mb-2">
                <i class="fas fa-info-circle me-1"></i>
                <strong>Dividir:</strong> Selecione uma parcela em aberto na tabela para dividi-la em partes iguais.
            </div>
            <div class="row g-2 align-items-end">
                <div class="col-auto">
                    <label class="form-label small fw-bold mb-1">Parcela selecionada:</label>
                    <span class="fw-bold" id="splitSelectedLabel">—</span>
                    <input type="hidden" id="splitInstallmentId" value="">
                </div>
                <div class="col-auto">
                    <label class="form-label small fw-bold mb-1">Valor original:</label>
                    <span class="fw-bold text-primary" id="splitOriginalAmount">R$ 0,00</span>
                </div>
                <div class="col-auto">
                    <label class="form-label small fw-bold mb-1">Dividir em</label>
                    <select class="form-select form-select-sm" id="splitParts" style="width:80px;">
                        <?php for ($p = 2; $p <= 12; $p++): ?>
                        <option value="<?= $p ?>"><?= $p ?>x</option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label small fw-bold mb-1">Valor por parte:</label>
                    <span class="fw-bold text-success" id="splitPerPartDisplay">R$ 0,00</span>
                </div>
                <div class="col-auto">
                    <label class="form-label small fw-bold mb-1">1º Vencimento</label>
                    <input type="date" class="form-control form-control-sm" id="splitFirstDueDate" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-sm btn-warning text-dark" id="btnConfirmSplit" disabled>
                        <i class="fas fa-check me-1"></i> Confirmar Divisão
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btnCancelSplit">
                        <i class="fas fa-times me-1"></i> Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-list-ol me-2"></i>Parcelas</h6>
        <span class="badge bg-secondary" id="badgeParcelasCount"><?= $totalParcelas ?> parcela(s)</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="installmentsTable">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-3 py-3" style="width:40px;">
                            <span class="text-muted" title="Seleção para unificar/dividir"><i class="fas fa-check-square"></i></span>
                        </th>
                        <th class="py-3" style="width:60px;">#</th>
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
                        $isOpen = in_array($inst['status'], ['pendente', 'atrasado']);
                    ?>
                    <tr class="<?= $inst['status'] === 'atrasado' ? 'table-danger' : '' ?> <?= $isOpen ? 'installment-open-row' : '' ?>"
                        data-id="<?= $inst['id'] ?>"
                        data-amount="<?= $inst['amount'] ?>"
                        data-number="<?= $inst['installment_number'] ?>"
                        data-status="<?= $inst['status'] ?>"
                        data-due="<?= $inst['due_date'] ?>"
                        data-is-entrada="<?= $isEntrada ? '1' : '0' ?>"
                        style="<?= $isOpen ? 'cursor:pointer;' : '' ?>">
                        <td class="ps-3">
                            <?php if ($isOpen): ?>
                            <input type="checkbox" class="form-check-input installment-check" 
                                   data-id="<?= $inst['id'] ?>" 
                                   data-amount="<?= $inst['amount'] ?>"
                                   data-number="<?= $isEntrada ? 'Entrada' : $inst['installment_number'] . 'ª' ?>"
                                   style="display:none;">
                            <!-- Radio for split (single select) -->
                            <input type="radio" class="form-check-input installment-radio" name="splitSelect"
                                   data-id="<?= $inst['id'] ?>"
                                   data-amount="<?= $inst['amount'] ?>"
                                   data-number="<?= $isEntrada ? 'Entrada' : $inst['installment_number'] . 'ª' ?>"
                                   data-due="<?= $inst['due_date'] ?>"
                                   style="display:none;">
                            <?php else: ?>
                            <span class="text-muted"><i class="fas fa-lock" style="font-size:0.65rem;"></i></span>
                            <?php endif; ?>
                        </td>
                        <td class="fw-bold">
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
                                <span class="text-success small" title="Confirmado por <?= e($inst['confirmed_by_name'] ?? '—') ?> em <?= $inst['confirmed_at'] ? date('d/m/Y H:i', strtotime($inst['confirmed_at'])) : '' ?>">
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

<!-- ══════ JavaScript: Unificar / Dividir ══════ -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const orderId = <?= (int)$orderId ?>;

    // ── Elementos ──
    const btnStartMerge = document.getElementById('btnStartMerge');
    const btnStartSplit = document.getElementById('btnStartSplit');
    const mergePanel = document.getElementById('mergePanel');
    const splitPanel = document.getElementById('splitPanel');
    const btnConfirmMerge = document.getElementById('btnConfirmMerge');
    const btnCancelMerge = document.getElementById('btnCancelMerge');
    const btnConfirmSplit = document.getElementById('btnConfirmSplit');
    const btnCancelSplit = document.getElementById('btnCancelSplit');

    let currentMode = null; // 'merge' | 'split' | null

    function formatBRL(val) {
        return 'R$ ' + parseFloat(val).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    function resetMode() {
        currentMode = null;
        if (mergePanel) mergePanel.style.display = 'none';
        if (splitPanel) splitPanel.style.display = 'none';
        // Hide all checkboxes / radios
        document.querySelectorAll('.installment-check').forEach(el => { el.style.display = 'none'; el.checked = false; });
        document.querySelectorAll('.installment-radio').forEach(el => { el.style.display = 'none'; el.checked = false; });
        // Remove highlight from rows
        document.querySelectorAll('.installment-open-row').forEach(el => el.classList.remove('table-primary', 'table-warning'));
        // Enable both buttons
        if (btnStartMerge) btnStartMerge.disabled = false;
        if (btnStartSplit) btnStartSplit.disabled = false;
        updateMergeUI();
        updateSplitUI();
    }

    // ── MERGE (Unificar) ──
    if (btnStartMerge) {
        btnStartMerge.addEventListener('click', function() {
            resetMode();
            currentMode = 'merge';
            mergePanel.style.display = '';
            btnStartSplit.disabled = true;
            // Show checkboxes on open rows
            document.querySelectorAll('.installment-check').forEach(el => el.style.display = '');
        });
    }

    if (btnCancelMerge) btnCancelMerge.addEventListener('click', resetMode);

    // Checkbox change events
    document.querySelectorAll('.installment-check').forEach(function(chk) {
        chk.addEventListener('change', function() {
            const row = this.closest('tr');
            if (this.checked) {
                row.classList.add('table-primary');
            } else {
                row.classList.remove('table-primary');
            }
            updateMergeUI();
        });
    });

    function updateMergeUI() {
        const checked = document.querySelectorAll('.installment-check:checked');
        const badges = document.getElementById('mergeSelectedBadges');
        const totalDisp = document.getElementById('mergeTotalDisplay');
        
        if (!badges || !totalDisp) return;
        
        if (checked.length === 0) {
            badges.innerHTML = '<span class="text-muted small">Clique nas parcelas abaixo...</span>';
            totalDisp.textContent = 'R$ 0,00';
            if (btnConfirmMerge) btnConfirmMerge.disabled = true;
            return;
        }

        let html = '';
        let total = 0;
        checked.forEach(function(chk) {
            const num = chk.getAttribute('data-number');
            const amt = parseFloat(chk.getAttribute('data-amount'));
            total += amt;
            html += '<span class="badge bg-primary me-1">' + num + ' — ' + formatBRL(amt) + '</span>';
        });

        badges.innerHTML = html;
        totalDisp.textContent = formatBRL(total);
        if (btnConfirmMerge) btnConfirmMerge.disabled = (checked.length < 2);
    }

    if (btnConfirmMerge) {
        btnConfirmMerge.addEventListener('click', function() {
            const checked = document.querySelectorAll('.installment-check:checked');
            if (checked.length < 2) return;

            const ids = [];
            let total = 0;
            checked.forEach(c => { ids.push(c.getAttribute('data-id')); total += parseFloat(c.getAttribute('data-amount')); });

            const dueDate = document.getElementById('mergeDueDate')?.value || '';

            Swal.fire({
                title: 'Confirmar Unificação',
                html: '<p>Unificar <strong>' + ids.length + '</strong> parcelas em uma única de <strong>' + formatBRL(total) + '</strong>?</p>' +
                      '<p class="small text-muted">As parcelas originais serão removidas e substituídas por uma nova parcela com o valor somado.</p>',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-compress-arrows-alt me-1"></i> Unificar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#3085d6',
            }).then(function(result) {
                if (!result.isConfirmed) return;

                const formData = new FormData();
                ids.forEach(id => formData.append('installment_ids[]', id));
                formData.append('due_date', dueDate);
                formData.append('csrf_token', csrfToken);

                fetch('?page=financial&action=mergeInstallments', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(function(data) {
                    if (data.success) {
                        Swal.fire({icon:'success', title:'Parcelas Unificadas!', text: data.message, timer:2000, showConfirmButton:false})
                        .then(() => location.reload());
                    } else {
                        Swal.fire({icon:'error', title:'Erro', text: data.message});
                    }
                })
                .catch(function() {
                    Swal.fire({icon:'error', title:'Erro', text:'Falha na comunicação com o servidor.'});
                });
            });
        });
    }

    // ── SPLIT (Dividir) ──
    if (btnStartSplit) {
        btnStartSplit.addEventListener('click', function() {
            resetMode();
            currentMode = 'split';
            splitPanel.style.display = '';
            if (btnStartMerge) btnStartMerge.disabled = true;
            // Show radio buttons on open rows
            document.querySelectorAll('.installment-radio').forEach(el => el.style.display = '');
        });
    }

    if (btnCancelSplit) btnCancelSplit.addEventListener('click', resetMode);

    // Radio change events
    document.querySelectorAll('.installment-radio').forEach(function(radio) {
        radio.addEventListener('change', function() {
            // Remove highlight from all
            document.querySelectorAll('.installment-open-row').forEach(el => el.classList.remove('table-warning'));
            // Highlight selected
            this.closest('tr').classList.add('table-warning');
            updateSplitUI();
        });
    });

    const splitPartsSelect = document.getElementById('splitParts');
    if (splitPartsSelect) {
        splitPartsSelect.addEventListener('change', updateSplitUI);
    }

    function updateSplitUI() {
        const selected = document.querySelector('.installment-radio:checked');
        const label = document.getElementById('splitSelectedLabel');
        const origAmt = document.getElementById('splitOriginalAmount');
        const perPart = document.getElementById('splitPerPartDisplay');
        const idField = document.getElementById('splitInstallmentId');
        const dueDateField = document.getElementById('splitFirstDueDate');

        if (!selected || !label || !origAmt) return;

        if (!selected) {
            if (label) label.textContent = '—';
            if (origAmt) origAmt.textContent = 'R$ 0,00';
            if (perPart) perPart.textContent = 'R$ 0,00';
            if (btnConfirmSplit) btnConfirmSplit.disabled = true;
            return;
        }

        const num = selected.getAttribute('data-number');
        const amt = parseFloat(selected.getAttribute('data-amount'));
        const parts = parseInt(splitPartsSelect?.value || 2);
        const due = selected.getAttribute('data-due');

        label.textContent = num + ' parcela';
        origAmt.textContent = formatBRL(amt);
        idField.value = selected.getAttribute('data-id');
        if (due) dueDateField.value = due;

        const valuePerPart = amt / parts;
        perPart.textContent = formatBRL(valuePerPart.toFixed(2));

        if (btnConfirmSplit) btnConfirmSplit.disabled = false;
    }

    if (btnConfirmSplit) {
        btnConfirmSplit.addEventListener('click', function() {
            const instId = document.getElementById('splitInstallmentId')?.value;
            const parts = parseInt(document.getElementById('splitParts')?.value || 2);
            const firstDue = document.getElementById('splitFirstDueDate')?.value || '';
            const origAmt = document.getElementById('splitOriginalAmount')?.textContent || '';

            if (!instId) return;

            Swal.fire({
                title: 'Confirmar Divisão',
                html: '<p>Dividir a parcela de <strong>' + origAmt + '</strong> em <strong>' + parts + '</strong> partes iguais?</p>' +
                      '<p class="small text-muted">A parcela original será removida e substituída por ' + parts + ' novas parcelas com vencimentos mensais.</p>',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-expand-arrows-alt me-1"></i> Dividir',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#f39c12',
            }).then(function(result) {
                if (!result.isConfirmed) return;

                const formData = new FormData();
                formData.append('installment_id', instId);
                formData.append('parts', parts);
                formData.append('first_due_date', firstDue);
                formData.append('csrf_token', csrfToken);

                fetch('?page=financial&action=splitInstallment', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(function(data) {
                    if (data.success) {
                        Swal.fire({icon:'success', title:'Parcela Dividida!', text: data.message, timer:2000, showConfirmButton:false})
                        .then(() => location.reload());
                    } else {
                        Swal.fire({icon:'error', title:'Erro', text: data.message});
                    }
                })
                .catch(function() {
                    Swal.fire({icon:'error', title:'Erro', text:'Falha na comunicação com o servidor.'});
                });
            });
        });
    }

    // ── Enable split button only when there are open installments ──
    <?php if ($hasOpenInstallments): ?>
    if (btnStartSplit) btnStartSplit.disabled = false;
    <?php endif; ?>

    // ── Click on row to toggle checkbox/radio ──
    document.querySelectorAll('.installment-open-row').forEach(function(row) {
        row.addEventListener('click', function(e) {
            // Ignore if clicking directly on input
            if (e.target.tagName === 'INPUT') return;
            if (!currentMode) return;

            if (currentMode === 'merge') {
                const chk = row.querySelector('.installment-check');
                if (chk && chk.style.display !== 'none') {
                    chk.checked = !chk.checked;
                    chk.dispatchEvent(new Event('change'));
                }
            } else if (currentMode === 'split') {
                const radio = row.querySelector('.installment-radio');
                if (radio && radio.style.display !== 'none') {
                    radio.checked = true;
                    radio.dispatchEvent(new Event('change'));
                }
            }
        });
    });
});
</script>

<style>
.installment-open-row:hover {
    background-color: rgba(0,123,255,0.05) !important;
}
.installment-open-row.table-primary {
    background-color: rgba(0,123,255,0.12) !important;
}
.installment-open-row.table-warning {
    background-color: rgba(243,156,18,0.12) !important;
}
</style>
