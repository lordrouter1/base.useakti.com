<?php
/**
 * Fiscal — Pagamentos (Parcelas)
 * Lista TODAS as parcelas de todos os pedidos, ordenadas por vencimento (mais próximo primeiro).
 * Ações diretas por linha:
 *   - Registrar pagamento (pendente/atrasado) — com forma de pagamento pré-selecionada do pedido
 *   - Confirmar pagamento (pago + não confirmado)
 *   - Estornar (pago → pendente, registra saída no livro caixa)
 *   - Anexar comprovante (upload de foto/documento)
 *   - Reimprimir boleto (quando método é boleto)
 *
 * Variáveis: $orders, $installments
 */
$filterStatus = $_GET['status'] ?? '';
$filterMonth  = $_GET['filter_month'] ?? '';
$filterYear   = $_GET['filter_year'] ?? '';

$canUseBoletoModule = \Akti\Core\ModuleBootloader::isModuleEnabled('boleto');

$statusMap = [
    'pendente'  => ['badge' => 'bg-warning text-dark', 'icon' => 'fas fa-clock',                'label' => 'Pendente'],
    'pago'      => ['badge' => 'bg-success',            'icon' => 'fas fa-check-circle',         'label' => 'Pago'],
    'atrasado'  => ['badge' => 'bg-danger',             'icon' => 'fas fa-exclamation-triangle',  'label' => 'Atrasado'],
    'cancelado' => ['badge' => 'bg-secondary',          'icon' => 'fas fa-ban',                  'label' => 'Cancelado'],
];

$methodLabels = [
    'dinheiro'       => '💵 Dinheiro',
    'pix'            => '📱 PIX',
    'cartao_credito' => '💳 Crédito',
    'cartao_debito'  => '💳 Débito',
    'boleto'         => '📄 Boleto',
    'transferencia'  => '🏦 Transf.',
];

// ── Resumo rápido ──
$totalParcelas = count($installments ?? []);
$totalPendentes = 0; $totalAtrasadas = 0; $totalPagas = 0; $totalAguardando = 0;
$valorPendente = 0; $valorPago = 0;
foreach ($installments as $inst) {
    if ($inst['status'] === 'pendente')  { $totalPendentes++; $valorPendente += (float)$inst['amount']; }
    if ($inst['status'] === 'atrasado')  { $totalAtrasadas++; $valorPendente += (float)$inst['amount']; }
    if ($inst['status'] === 'pago')      { $totalPagas++; $valorPago += (float)($inst['paid_amount'] ?? $inst['amount']); }
    if ($inst['status'] === 'pago' && empty($inst['is_confirmed'])) $totalAguardando++;
}
?>

<?php if (!empty($_SESSION['flash_success'])): ?>
<script>document.addEventListener('DOMContentLoaded',()=>Swal.fire({icon:'success',title:'Sucesso!',text:'<?= addslashes($_SESSION['flash_success']) ?>',timer:2500,showConfirmButton:false}));</script>
<?php unset($_SESSION['flash_success']); endif; ?>
<?php if (!empty($_SESSION['flash_error'])): ?>
<script>document.addEventListener('DOMContentLoaded',()=>Swal.fire({icon:'error',title:'Erro',text:'<?= addslashes($_SESSION['flash_error']) ?>'}));</script>
<?php unset($_SESSION['flash_error']); endif; ?>

<!-- ══════ Header ══════ -->
<div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <h1 class="h2 mb-0"><i class="fas fa-file-invoice-dollar me-2 text-primary"></i>Pagamentos</h1>
    <div class="btn-toolbar gap-2">
        <a href="?page=financial&action=transactions" class="btn btn-sm btn-outline-success">
            <i class="fas fa-exchange-alt me-1"></i> Entradas / Saídas
        </a>
    </div>
</div>

<!-- ══════ Cards Resumo ══════ -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
            <div class="card-body d-flex align-items-center p-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:48px;height:48px;background:rgba(52,152,219,0.15);">
                    <i class="fas fa-list-ol fa-lg text-primary"></i>
                </div>
                <div>
                    <div class="text-muted small text-uppercase fw-bold">Total Parcelas</div>
                    <div class="fw-bold fs-4"><?= $totalParcelas ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100 border-start border-warning border-4">
            <div class="card-body d-flex align-items-center p-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:48px;height:48px;background:rgba(243,156,18,0.15);">
                    <i class="fas fa-clock fa-lg text-warning"></i>
                </div>
                <div>
                    <div class="text-muted small text-uppercase fw-bold">Pendentes / Atrasadas</div>
                    <div class="fw-bold fs-4"><?= $totalPendentes + $totalAtrasadas ?>
                        <small class="text-muted fs-6">(R$ <?= number_format($valorPendente, 2, ',', '.') ?>)</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
            <div class="card-body d-flex align-items-center p-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:48px;height:48px;background:rgba(39,174,96,0.15);">
                    <i class="fas fa-check-circle fa-lg text-success"></i>
                </div>
                <div>
                    <div class="text-muted small text-uppercase fw-bold">Pagas</div>
                    <div class="fw-bold fs-4"><?= $totalPagas ?>
                        <small class="text-muted fs-6">(R$ <?= number_format($valorPago, 2, ',', '.') ?>)</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100 border-start border-info border-4">
            <div class="card-body d-flex align-items-center p-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:48px;height:48px;background:rgba(23,162,184,0.15);">
                    <i class="fas fa-user-clock fa-lg text-info"></i>
                </div>
                <div>
                    <div class="text-muted small text-uppercase fw-bold">Aguardando Confirmação</div>
                    <div class="fw-bold fs-4"><?= $totalAguardando ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══════ Filtros ══════ -->
<form method="get" class="row g-2 mb-3 align-items-end">
    <input type="hidden" name="page" value="financial">
    <input type="hidden" name="action" value="payments">
    <div class="col-auto">
        <label class="form-label small fw-bold mb-1">Status</label>
        <select name="status" class="form-select form-select-sm" style="width:170px">
            <option value="">Todos</option>
            <option value="pendente"    <?= $filterStatus==='pendente'   ?'selected':'' ?>>Pendentes/Atrasadas</option>
            <option value="pago"        <?= $filterStatus==='pago'       ?'selected':'' ?>>Pagas</option>
            <option value="atrasado"    <?= $filterStatus==='atrasado'   ?'selected':'' ?>>Atrasadas</option>
            <option value="aguardando"  <?= $filterStatus==='aguardando' ?'selected':'' ?>>Aguardando Confirm.</option>
        </select>
    </div>
    <div class="col-auto">
        <label class="form-label small fw-bold mb-1">Mês</label>
        <select name="filter_month" class="form-select form-select-sm" style="width:120px">
            <option value="">Todos</option>
            <?php $mn = ['','Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
                  for ($m = 1; $m <= 12; $m++): ?>
            <option value="<?= $m ?>" <?= $filterMonth == $m ? 'selected' : '' ?>><?= $mn[$m] ?></option>
            <?php endfor; ?>
        </select>
    </div>
    <div class="col-auto">
        <label class="form-label small fw-bold mb-1">Ano</label>
        <select name="filter_year" class="form-select form-select-sm" style="width:100px">
            <option value="">Todos</option>
            <?php for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++): ?>
            <option value="<?= $y ?>" <?= $filterYear == $y ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i> Filtrar</button>
        <a href="?page=financial&action=payments" class="btn btn-sm btn-outline-secondary"><i class="fas fa-times"></i></a>
    </div>
</form>

<!-- ══════ Busca ══════ -->
<div class="mb-3">
    <div class="input-group">
        <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
        <input type="text" class="form-control" id="searchPayments" placeholder="Buscar por nº pedido, cliente, status, método..." autocomplete="off">
    </div>
</div>

<div class="alert alert-info py-2 small mb-3">
    <i class="fas fa-info-circle me-1"></i>
    Exibindo apenas parcelas de pedidos nas etapas <strong>Financeiro</strong> e <strong>Concluído</strong>.
    Pedidos em outras etapas do pipeline não aparecem aqui.
</div>

<!-- ══════ Tabela de Parcelas ══════ -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-list me-2"></i>Todas as Parcelas <small class="text-muted fw-normal">(ordenadas por vencimento)</small></h6>
        <span class="badge bg-secondary"><?= $totalParcelas ?> registro(s)</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="paymentsTable">
                <thead class="bg-light">
                    <tr>
                        <th class="py-3 ps-3">Pedido</th>
                        <th class="py-3">Cliente</th>
                        <th class="py-3">Parcela</th>
                        <th class="py-3">Vencimento</th>
                        <th class="py-3">Valor</th>
                        <th class="py-3">Pago em</th>
                        <th class="py-3">Valor Pago</th>
                        <th class="py-3">Método</th>
                        <th class="py-3">Status</th>
                        <th class="py-3">Confirmação</th>
                        <th class="py-3 text-center">Anexo</th>
                        <th class="py-3 text-end pe-3">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($installments)): ?>
                    <tr>
                        <td colspan="12" class="text-center text-muted py-5">
                            <i class="fas fa-inbox fa-3x mb-2 d-block opacity-50"></i>
                            <div class="fw-bold">Nenhuma parcela encontrada</div>
                            <small>Ajuste os filtros ou verifique se existem pedidos com parcelas geradas.</small>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($installments as $inst):
                        $st = $statusMap[$inst['status']] ?? $statusMap['pendente'];
                        $isEntrada = ((int)($inst['installment_number'] ?? 1) === 0);
                        $orderId = $inst['order_id'];
                        // Forma de pagamento do pedido (pré-seleção)
                        $orderMethod = $inst['order_payment_method'] ?? '';
                        // Forma de pagamento efetiva da parcela (se já paga)
                        $instMethod = $inst['payment_method'] ?? '';
                        // Determinar método exibido
                        $displayMethod = !empty($instMethod) ? $instMethod : $orderMethod;
                        // Verificar se é boleto (para reimpressão)
                        $isBoleto = ($displayMethod === 'boleto');
                        // Mês referente para boleto (baseado no vencimento)
                        $boletoMesRef = date('m/Y', strtotime($inst['due_date']));
                        // Comprovante
                        $hasAttachment = !empty($inst['attachment_path']);
                        // Endereço formatado do cliente (address é JSON)
                        $customerAddrFormatted = '';
                        if (!empty($inst['customer_address'])) {
                            $customerAddrFormatted = \Akti\Models\CompanySettings::formatCustomerAddress($inst['customer_address']);
                        }
                    ?>
                    <tr class="<?= $inst['status'] === 'atrasado' ? 'table-danger' : '' ?>">
                        <!-- Pedido -->
                        <td class="ps-3 fw-bold">
                            <a href="?page=pipeline&action=detail&id=<?= $orderId ?>" class="text-decoration-none text-dark" title="Ver pedido">
                                #<?= str_pad($orderId, 4, '0', STR_PAD_LEFT) ?>
                            </a>
                        </td>
                        <!-- Cliente -->
                        <td class="small"><?= e($inst['customer_name'] ?? 'N/A') ?></td>
                        <!-- Parcela -->
                        <td>
                            <?php if ($isEntrada): ?>
                                <span class="badge bg-info">Entrada</span>
                            <?php else: ?>
                                <span class="fw-bold"><?= $inst['installment_number'] ?>ª</span>
                            <?php endif; ?>
                        </td>
                        <!-- Vencimento -->
                        <td class="small">
                            <?= date('d/m/Y', strtotime($inst['due_date'])) ?>
                            <?php if ($inst['status'] === 'atrasado'):
                                $diasAtraso = max(0, (int)((time() - strtotime($inst['due_date'])) / 86400));
                            ?>
                                <span class="badge bg-danger rounded-pill ms-1" style="font-size:0.6rem;">+<?= $diasAtraso ?>d</span>
                            <?php endif; ?>
                        </td>
                        <!-- Valor -->
                        <td class="fw-bold">R$ <?= number_format($inst['amount'], 2, ',', '.') ?></td>
                        <!-- Pago em -->
                        <td class="small">
                            <?= !empty($inst['paid_date']) ? date('d/m/Y', strtotime($inst['paid_date'])) : '<span class="text-muted">—</span>' ?>
                        </td>
                        <!-- Valor Pago -->
                        <td>
                            <?php if (!empty($inst['paid_amount'])): ?>
                                <span class="fw-bold text-success">R$ <?= number_format($inst['paid_amount'], 2, ',', '.') ?></span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <!-- Método -->
                        <td class="small">
                            <?php if (!empty($displayMethod)): ?>
                                <?= $methodLabels[$displayMethod] ?? ucfirst($displayMethod) ?>
                                <?php if (!empty($instMethod) && $instMethod !== $orderMethod && !empty($orderMethod)): ?>
                                    <i class="fas fa-exchange-alt text-muted ms-1" title="Alterado (pedido: <?= $methodLabels[$orderMethod] ?? ucfirst($orderMethod) ?>)" style="font-size:0.65rem;cursor:help;"></i>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <!-- Status -->
                        <td>
                            <span class="badge <?= $st['badge'] ?>"><i class="<?= $st['icon'] ?> me-1"></i><?= $st['label'] ?></span>
                        </td>
                        <!-- Confirmação -->
                        <td>
                            <?php if ($inst['status'] === 'pago' && !empty($inst['is_confirmed'])): ?>
                                <span class="text-success small" title="Confirmado por <?= eAttr($inst['confirmed_by_name'] ?? '—') ?> em <?= !empty($inst['confirmed_at']) ? date('d/m/Y H:i', strtotime($inst['confirmed_at'])) : '' ?>">
                                    <i class="fas fa-check-double me-1"></i>Confirmado
                                </span>
                            <?php elseif ($inst['status'] === 'pago' && empty($inst['is_confirmed'])): ?>
                                <span class="badge bg-warning text-dark"><i class="fas fa-user-clock me-1"></i>Aguardando</span>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                        <!-- Anexo (Comprovante) -->
                        <td class="text-center">
                            <?php if ($hasAttachment): ?>
                                <a href="<?= eAttr($inst['attachment_path']) ?>" target="_blank" class="text-success" title="Ver comprovante">
                                    <i class="fas fa-paperclip fa-lg"></i>
                                </a>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                        <!-- Ações -->
                        <td class="text-end pe-3">
                            <div class="btn-group btn-group-sm">
                                <?php if ($inst['status'] === 'pendente' || $inst['status'] === 'atrasado'): ?>
                                    <!-- Registrar Pagamento -->
                                    <button type="button" class="btn btn-success btn-register-pay"
                                            data-id="<?= $inst['id'] ?>"
                                            data-order-id="<?= $orderId ?>"
                                            data-amount="<?= $inst['amount'] ?>"
                                            data-number="<?= $inst['installment_number'] ?>"
                                            data-customer="<?= eAttr($inst['customer_name'] ?? '') ?>"
                                            data-order-method="<?= eAttr($orderMethod) ?>"
                                            data-due-date="<?= $inst['due_date'] ?>"
                                            title="Registrar Pagamento">
                                        <i class="fas fa-hand-holding-usd me-1"></i> Pagar
                                    </button>
                                    <!-- Boleto: Reimprimir -->
                                    <?php if ($isBoleto): ?>
                                    <?php if ($canUseBoletoModule): ?>
                                    <button type="button" class="btn btn-outline-primary btn-print-boleto"
                                            data-id="<?= $inst['id'] ?>"
                                            data-order-id="<?= $orderId ?>"
                                            data-amount="<?= $inst['amount'] ?>"
                                            data-due="<?= $inst['due_date'] ?>"
                                            data-mes-ref="<?= $boletoMesRef ?>"
                                            data-number="<?= $inst['installment_number'] ?>"
                                            data-customer="<?= eAttr($inst['customer_name'] ?? '') ?>"
                                            data-customer-doc="<?= eAttr($inst['customer_document'] ?? '') ?>"
                                            data-customer-addr="<?= eAttr($customerAddrFormatted) ?>"
                                            title="Reimprimir Boleto (ref. <?= $boletoMesRef ?>)">
                                        <i class="fas fa-print"></i>
                                    </button>
                                    <?php else: ?>
                                    <button type="button" class="btn btn-outline-secondary" title="Módulo Boleto inativo"
                                            onclick="<?= \Akti\Core\ModuleBootloader::getDisabledModuleJS('boleto') ?>">
                                        <i class="fas fa-print"></i>
                                    </button>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                <?php elseif ($inst['status'] === 'pago' && empty($inst['is_confirmed'])): ?>
                                    <!-- Confirmar -->
                                    <form method="post" action="?page=financial&action=confirmPayment" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="installment_id" value="<?= $inst['id'] ?>">
                                        <input type="hidden" name="redirect" value="?page=financial&action=payments<?= $filterStatus ? '&status='.$filterStatus : '' ?>">
                                        <button type="submit" class="btn btn-outline-success btn-confirm" title="Confirmar Pagamento">
                                            <i class="fas fa-check me-1"></i> Confirmar
                                        </button>
                                    </form>
                                    <!-- Estornar -->
                                    <form method="post" action="?page=financial&action=cancelInstallment" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="installment_id" value="<?= $inst['id'] ?>">
                                        <input type="hidden" name="order_id" value="<?= $orderId ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-estornar" title="Estornar Pagamento">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                    </form>
                                <?php elseif ($inst['status'] === 'pago' && !empty($inst['is_confirmed'])): ?>
                                    <!-- Já confirmado — permitir estorno -->
                                    <form method="post" action="?page=financial&action=cancelInstallment" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="installment_id" value="<?= $inst['id'] ?>">
                                        <input type="hidden" name="order_id" value="<?= $orderId ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-estornar" title="Estornar Pagamento">
                                            <i class="fas fa-undo me-1"></i> Estornar
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <!-- Anexar Comprovante (sempre disponível) -->
                                <button type="button" class="btn btn-outline-secondary btn-attach"
                                        data-id="<?= $inst['id'] ?>"
                                        data-order-id="<?= $orderId ?>"
                                        data-has-attachment="<?= $hasAttachment ? '1' : '0' ?>"
                                        data-attachment-path="<?= eAttr($inst['attachment_path'] ?? '') ?>"
                                        title="<?= $hasAttachment ? 'Gerenciar comprovante' : 'Anexar comprovante' ?>">
                                    <i class="fas fa-<?= $hasAttachment ? 'file-alt' : 'upload' ?>"></i>
                                </button>

                                <!-- Boleto: Reimprimir (para parcelas pagas com boleto) -->
                                <?php if ($inst['status'] === 'pago' && $isBoleto): ?>
                                <?php if ($canUseBoletoModule): ?>
                                <button type="button" class="btn btn-outline-primary btn-print-boleto"
                                        data-id="<?= $inst['id'] ?>"
                                        data-order-id="<?= $orderId ?>"
                                        data-amount="<?= $inst['paid_amount'] ?? $inst['amount'] ?>"
                                        data-due="<?= $inst['due_date'] ?>"
                                        data-mes-ref="<?= $boletoMesRef ?>"
                                        data-number="<?= $inst['installment_number'] ?>"
                                        data-customer="<?= eAttr($inst['customer_name'] ?? '') ?>"
                                        data-customer-doc="<?= eAttr($inst['customer_document'] ?? '') ?>"
                                        data-customer-addr="<?= eAttr($customerAddrFormatted) ?>"
                                        title="Reimprimir Boleto (ref. <?= $boletoMesRef ?>)">
                                    <i class="fas fa-print"></i>
                                </button>
                                <?php else: ?>
                                <button type="button" class="btn btn-outline-secondary" title="Módulo Boleto inativo"
                                        onclick="<?= \Akti\Core\ModuleBootloader::getDisabledModuleJS('boleto') ?>">
                                    <i class="fas fa-print"></i>
                                </button>
                                <?php endif; ?>
                                <?php endif; ?>

                                <!-- Link para ver todas as parcelas do pedido -->
                                <a href="?page=financial&action=installments&order_id=<?= $orderId ?>" class="btn btn-outline-secondary" title="Ver todas as parcelas deste pedido">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ══════ Modal Registrar Pagamento ══════ -->
<div class="modal fade" id="modalPay" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="post" action="?page=financial&action=payInstallment" id="formPay" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="installment_id" id="payInstId">
                <input type="hidden" name="order_id" id="payOrderId">
                <div class="modal-header bg-success bg-opacity-10 border-0">
                    <h5 class="modal-title text-success"><i class="fas fa-hand-holding-usd me-2"></i>Registrar Pagamento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 mb-3">
                        <i class="fas fa-info-circle me-1"></i>
                        Pedido <strong id="payOrderDisplay">—</strong> ·
                        Parcela <strong id="payNumber">—</strong> ·
                        Valor: <strong id="payAmountDisplay">—</strong>
                        <br><small class="text-muted" id="payCustomerDisplay"></small>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Data do Pagamento</label>
                            <input type="date" name="paid_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Valor Pago (R$)</label>
                            <input type="number" step="0.01" min="0.01" name="paid_amount" id="payAmountInput" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Forma de Pagamento</label>
                            <select name="payment_method" id="payMethodSelect" class="form-select" required>
                                <option value="dinheiro">💵 Dinheiro</option>
                                <option value="pix">📱 PIX</option>
                                <option value="cartao_credito">💳 Cartão Crédito</option>
                                <option value="cartao_debito">💳 Cartão Débito</option>
                                <option value="boleto">📄 Boleto</option>
                                <option value="transferencia">🏦 Transferência</option>
                            </select>
                            <small class="text-muted" id="payMethodHint"></small>
                        </div>
                        <!-- Campo Boleto: Reimprimir para mês referente -->
                        <div class="col-12 d-none" id="payBoletoSection">
                            <div class="card bg-light border-0">
                                <div class="card-body py-2 px-3">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <i class="fas fa-barcode text-primary me-2"></i>
                                            <span class="small fw-bold">Boleto — Mês Referente:</span>
                                            <span class="badge bg-primary ms-1" id="payBoletoMesRef">—</span>
                                        </div>
                                        <?php if ($canUseBoletoModule): ?>
                                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnPrintBoletoModal" title="Reimprimir Boleto">
                                            <i class="fas fa-print me-1"></i> Reimprimir
                                        </button>
                                        <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" title="Módulo Boleto inativo"
                                                onclick="<?= \Akti\Core\ModuleBootloader::getDisabledModuleJS('boleto') ?>">
                                            <i class="fas fa-print me-1"></i> Reimprimir
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Comprovante (upload) -->
                        <div class="col-12">
                            <label class="form-label small fw-bold"><i class="fas fa-paperclip me-1"></i>Comprovante <span class="text-muted fw-normal">(opcional)</span></label>
                            <input type="file" name="attachment" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf">
                            <small class="text-muted">Formatos aceitos: JPG, PNG, WEBP, GIF, PDF (máx 5MB)</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Observação <span class="text-muted fw-normal">(opcional)</span></label>
                            <input type="text" name="notes" class="form-control" placeholder="Ex: Comprovante recebido via WhatsApp">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success" id="btnSubmitPay">
                        <i class="fas fa-check me-1"></i> Registrar Pagamento
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ══════ Modal Anexar Comprovante ══════ -->
<div class="modal fade" id="modalAttach" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-secondary bg-opacity-10 border-0">
                <h5 class="modal-title"><i class="fas fa-paperclip me-2 text-secondary"></i>Comprovante</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Preview do anexo existente -->
                <div id="attachPreview" class="d-none mb-3 text-center">
                    <div class="border rounded p-3 bg-light">
                        <div id="attachPreviewImg" class="d-none mb-2">
                            <img src="" class="img-fluid rounded shadow-sm" style="max-height:300px;" id="attachImgTag">
                        </div>
                        <div id="attachPreviewPdf" class="d-none mb-2">
                            <i class="fas fa-file-pdf fa-3x text-danger"></i>
                            <p class="small mt-1 mb-0">Documento PDF</p>
                        </div>
                        <a href="" target="_blank" class="btn btn-sm btn-outline-primary" id="attachViewLink">
                            <i class="fas fa-external-link-alt me-1"></i> Abrir
                        </a>
                        <form method="post" action="?page=financial&action=removeAttachment" class="d-inline" id="formRemoveAttach">
                            <?= csrf_field() ?>
                            <input type="hidden" name="installment_id" id="removeAttachInstId">
                            <input type="hidden" name="redirect" value="?page=financial&action=payments">
                            <button type="submit" class="btn btn-sm btn-outline-danger ms-2 btn-remove-attach">
                                <i class="fas fa-trash me-1"></i> Remover
                            </button>
                        </form>
                    </div>
                </div>
                <!-- Upload de novo anexo -->
                <form method="post" action="?page=financial&action=uploadAttachment" enctype="multipart/form-data" id="formUploadAttach">
                    <?= csrf_field() ?>
                    <input type="hidden" name="installment_id" id="uploadAttachInstId">
                    <input type="hidden" name="redirect" value="?page=financial&action=payments">
                    <div class="mb-3">
                        <label class="form-label small fw-bold" id="attachUploadLabel">Enviar comprovante</label>
                        <input type="file" name="attachment" class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf" required>
                        <small class="text-muted">JPG, PNG, WEBP, GIF ou PDF (máx 5MB)</small>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-upload me-1"></i> Enviar Comprovante
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ══════ Modal Reimprimir Boleto ══════ -->
<div class="modal fade" id="modalBoleto" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary bg-opacity-10 border-0">
                <h5 class="modal-title text-primary"><i class="fas fa-barcode me-2"></i>Reimprimir Boleto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info py-2 mb-3">
                    <i class="fas fa-info-circle me-1"></i>
                    Pedido <strong id="boletoOrderDisplay">—</strong> ·
                    Cliente: <strong id="boletoCustomerDisplay">—</strong>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Valor do Boleto</label>
                        <div class="form-control bg-light" id="boletoAmountDisplay">—</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Vencimento</label>
                        <div class="form-control bg-light" id="boletoDueDisplay">—</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">Mês Referente</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                            <input type="month" class="form-control" id="boletoMesInput">
                        </div>
                        <small class="text-muted">Selecione o mês de referência para o boleto (pode alterar se necessário).</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary" id="btnConfirmPrintBoleto">
                    <i class="fas fa-print me-1"></i> Imprimir Boleto
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══════ Scripts ══════ -->
<script>
document.addEventListener('DOMContentLoaded', function() {

    // ═══════════════════════════════════════════════════════════════════
    // DADOS BANCÁRIOS (injetados via PHP — configurações da empresa)
    // ═══════════════════════════════════════════════════════════════════
    var bankConfig = {
        banco:         <?= json_encode($company['boleto_banco'] ?? '') ?>,
        agencia:       <?= json_encode($company['boleto_agencia'] ?? '') ?>,
        agenciaDv:     <?= json_encode($company['boleto_agencia_dv'] ?? '') ?>,
        conta:         <?= json_encode($company['boleto_conta'] ?? '') ?>,
        contaDv:       <?= json_encode($company['boleto_conta_dv'] ?? '') ?>,
        carteira:      <?= json_encode($company['boleto_carteira'] ?? '109') ?>,
        especie:       <?= json_encode($company['boleto_especie'] ?? 'R$') ?>,
        cedente:       <?= json_encode($company['boleto_cedente'] ?? $company['company_name'] ?? 'Empresa') ?>,
        cedenteDoc:    <?= json_encode($company['boleto_cedente_documento'] ?? $company['company_document'] ?? '') ?>,
        convenio:      <?= json_encode($company['boleto_convenio'] ?? '') ?>,
        nossoNumero:   parseInt(<?= json_encode($company['boleto_nosso_numero'] ?? '1') ?>) || 1,
        nossoNumDigitos: parseInt(<?= json_encode($company['boleto_nosso_numero_digitos'] ?? '7') ?>) || 7,
        instrucoes:    <?= json_encode($company['boleto_instrucoes'] ?? "Não receber após o vencimento.\nMulta de 2% após o vencimento.\nJuros de 1% ao mês.") ?>,
        multa:         <?= json_encode($company['boleto_multa'] ?? '2.00') ?>,
        juros:         <?= json_encode($company['boleto_juros'] ?? '1.00') ?>,
        aceite:        <?= json_encode($company['boleto_aceite'] ?? 'N') ?>,
        especieDoc:    <?= json_encode($company['boleto_especie_doc'] ?? 'DM') ?>,
        demonstrativo: <?= json_encode($company['boleto_demonstrativo'] ?? '') ?>,
        localPagamento: <?= json_encode($company['boleto_local_pagamento'] ?? 'Pagável em qualquer banco até o vencimento') ?>,
        cedenteEndereco: <?= json_encode($company['boleto_cedente_endereco'] ?? ($companyAddress ?? '')) ?>
    };

    var bancosNomes = {
        '001': 'Banco do Brasil S.A.', '033': 'Banco Santander S.A.', '104': 'Caixa Econômica Federal',
        '237': 'Banco Bradesco S.A.', '341': 'Itaú Unibanco S.A.', '399': 'HSBC', '422': 'Banco Safra S.A.',
        '748': 'Sicredi', '756': 'Sicoob', '077': 'Banco Inter S.A.', '260': 'Nu Pagamentos S.A.',
        '336': 'Banco C6 S.A.', '290': 'PagSeguro Internet S.A.', '380': 'PicPay', '323': 'Mercado Pago'
    };

    // ═══════════════════════════════════════════════════════════════════
    // FUNÇÕES FEBRABAN / CNAB 400 — Geração de Boleto Bancário
    // ═══════════════════════════════════════════════════════════════════
    function mod10(value) {
        var soma = 0, peso = 2;
        for (var i = value.length - 1; i >= 0; i--) {
            var parcial = parseInt(value[i]) * peso;
            if (parcial > 9) parcial = Math.floor(parcial / 10) + (parcial % 10);
            soma += parcial;
            peso = peso === 2 ? 1 : 2;
        }
        var resto = soma % 10;
        return resto === 0 ? 0 : 10 - resto;
    }

    function mod11(value, base) {
        base = base || 9;
        var soma = 0, peso = 2;
        for (var i = value.length - 1; i >= 0; i--) {
            soma += parseInt(value[i]) * peso;
            peso++;
            if (peso > base) peso = 2;
        }
        var resto = soma % 11;
        if (resto === 0 || resto === 1 || resto === 10) return 1;
        return 11 - resto;
    }

    function padLeft(str, len, ch) {
        ch = ch || '0';
        str = String(str);
        while (str.length < len) str = ch + str;
        return str;
    }

    function fatorVencimento(dateStr) {
        var base = new Date(1997, 9, 7); // 07/10/1997
        var dt = new Date(dateStr + 'T12:00:00');
        var diff = Math.round((dt - base) / (1000 * 60 * 60 * 24));
        return padLeft(Math.max(0, diff), 4);
    }

    function formatarValorBoleto(valor) {
        return padLeft(Math.round(valor * 100), 10);
    }

    function gerarCodigoBarras(banco, vencStr, valor, nossoNumStr) {
        var fv = fatorVencimento(vencStr);
        var vl = formatarValorBoleto(valor);
        var ag = padLeft(bankConfig.agencia, 4);
        var ct = padLeft(bankConfig.conta, 8);
        var ctDv = bankConfig.contaDv || '0';
        var cart = padLeft(bankConfig.carteira, 3);
        var nn = nossoNumStr;
        var conv = padLeft(bankConfig.convenio, 7);

        var campoLivre = '';
        if (banco === '001') {
            campoLivre = padLeft(conv, 7) + padLeft(nn, 10) + ag + padLeft(ct, 8) + padLeft(cart, 2).substring(0, 2);
            campoLivre = campoLivre.substring(0, 25);
        } else if (banco === '341') {
            var nn8 = padLeft(nn, 8);
            var ct5 = padLeft(bankConfig.conta, 5);
            var dacNN = mod10(ag + ct5 + cart + nn8);
            campoLivre = (cart + nn8 + ag + ct5 + String(dacNN) + '000').substring(0, 25);
        } else if (banco === '237') {
            campoLivre = (ag + padLeft(cart, 2) + padLeft(nn, 11) + padLeft(ct, 7) + '0').substring(0, 25);
        } else if (banco === '104') {
            campoLivre = (padLeft(conv, 6) + padLeft(nn, 17) + '04').substring(0, 25);
        } else if (banco === '033') {
            campoLivre = ('9' + padLeft(conv, 7) + padLeft(nn, 13) + '0' + padLeft(cart, 3)).substring(0, 25);
        } else {
            campoLivre = (ag + padLeft(ct, 8) + ctDv + padLeft(cart, 3) + padLeft(nn, 10)).substring(0, 25);
            while (campoLivre.length < 25) campoLivre += '0';
        }

        var semDv = banco + '9' + fv + vl + campoLivre;
        var dvGeral = mod11(semDv.replace(/[^0-9]/g, ''), 9);
        var cb = banco + '9' + String(dvGeral) + fv + vl + campoLivre;
        return cb.substring(0, 44);
    }

    function gerarLinhaDigitavel(cb) {
        var campo1 = cb.substring(0, 4) + cb.substring(19, 24);
        var dv1 = mod10(campo1);
        var c1 = campo1.substring(0, 5) + '.' + campo1.substring(5) + String(dv1);

        var campo2 = cb.substring(24, 34);
        var dv2 = mod10(campo2);
        var c2 = campo2.substring(0, 5) + '.' + campo2.substring(5) + String(dv2);

        var campo3 = cb.substring(34, 44);
        var dv3 = mod10(campo3);
        var c3 = campo3.substring(0, 5) + '.' + campo3.substring(5) + String(dv3);

        var c4 = cb.substring(4, 5);
        var c5 = cb.substring(5, 19);

        return c1 + ' ' + c2 + ' ' + c3 + ' ' + c4 + ' ' + c5;
    }

    function gerarBarcode128Svg(code, width, height) {
        var patterns = {
            '0': 'nnwwn', '1': 'wnnnw', '2': 'nwnnw', '3': 'wwnnn', '4': 'nnwnw',
            '5': 'wnwnn', '6': 'nwwnn', '7': 'nnnww', '8': 'wnnwn', '9': 'nwnwn'
        };
        var data = code;
        if (data.length % 2 !== 0) data = '0' + data;
        var bars = 'nnnn';
        for (var i = 0; i < data.length; i += 2) {
            var patBar = patterns[data[i]] || 'nnwwn';
            var patSpace = patterns[data[i + 1]] || 'nnwwn';
            for (var j = 0; j < 5; j++) {
                bars += patBar[j];
                bars += patSpace[j];
            }
        }
        bars += 'wnn';
        var totalUnits = 0;
        for (var k = 0; k < bars.length; k++) totalUnits += (bars[k] === 'w') ? 3 : 1;
        var unitWidth = width / totalUnits;
        var svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' + width + '" height="' + height + '" viewBox="0 0 ' + width + ' ' + height + '">';
        var x = 0;
        for (var m = 0; m < bars.length; m++) {
            var bw = (bars[m] === 'w') ? unitWidth * 3 : unitWidth;
            if (m % 2 === 0) svg += '<rect x="' + x.toFixed(2) + '" y="0" width="' + bw.toFixed(2) + '" height="' + height + '" fill="#000"/>';
            x += bw;
        }
        svg += '</svg>';
        return svg;
    }

    function formatCurrency(v) {
        return parseFloat(v).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function formatDateBR(dateStr) {
        if (!dateStr) return '—';
        var d = new Date(dateStr + 'T12:00:00');
        return d.toLocaleDateString('pt-BR');
    }

    // ═══════════════════════════════════════════════════════════════════
    // Gerar HTML completo do boleto FEBRABAN para uma parcela
    // ═══════════════════════════════════════════════════════════════════
    function gerarBoletoHTML(params) {
        // params: { orderId, parcLabel, dueDate, valor, customer, customerDoc, customerAddr, instNumber }

        if (!bankConfig.banco || !bankConfig.agencia || !bankConfig.conta) {
            Swal.fire({
                icon: 'warning',
                title: 'Configurações Bancárias Incompletas',
                html: '<p>Para gerar boletos no padrão FEBRABAN, é necessário configurar os dados bancários.</p><p class="small text-muted">Vá em <strong>Configurações → Boleto/Bancário</strong> e preencha os dados do banco, agência, conta e cedente.</p>',
                confirmButtonText: '<i class="fas fa-cog me-1"></i> Ir para Configurações',
                showCancelButton: true,
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#f39c12'
            }).then(r => {
                if (r.isConfirmed) window.open('?page=settings&tab=boleto', '_blank');
            });
            return null;
        }

        if (!bankConfig.cedente || !bankConfig.cedenteDoc) {
            Swal.fire({
                icon: 'warning',
                title: 'Dados do Cedente Incompletos',
                html: '<p>Preencha o <strong>Nome/Razão Social</strong> e o <strong>CNPJ/CPF do Cedente</strong> nas configurações.</p>',
                confirmButtonText: '<i class="fas fa-cog me-1"></i> Ir para Configurações',
                showCancelButton: true,
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#f39c12'
            }).then(r => {
                if (r.isConfirmed) window.open('?page=settings&tab=boleto', '_blank');
            });
            return null;
        }

        var orderNum = String(params.orderId).padStart(4, '0');
        var bancoNome = bancosNomes[bankConfig.banco] || ('Banco ' + bankConfig.banco);
        var bancoCode = padLeft(bankConfig.banco, 3);
        var bancoDv   = mod11(bancoCode);
        var bancoFull = bancoCode + '-' + bancoDv;
        var agenciaStr = bankConfig.agencia + (bankConfig.agenciaDv ? '-' + bankConfig.agenciaDv : '');
        var contaStr = bankConfig.conta + (bankConfig.contaDv ? '-' + bankConfig.contaDv : '');
        var agCodCedente = agenciaStr + ' / ' + (bankConfig.convenio || contaStr);
        var instrucoes = bankConfig.instrucoes ? bankConfig.instrucoes.split('\n') : [];
        var dataProcessamento = new Date().toLocaleDateString('pt-BR');
        var multaPct = parseFloat(bankConfig.multa) || 0;
        var jurosPct = parseFloat(bankConfig.juros) || 0;
        var cedenteAddr = bankConfig.cedenteEndereco || '';

        var dueDateFmt = formatDateBR(params.dueDate);
        var valorNum = parseFloat(params.valor) || 0;
        var parcLabel = params.parcLabel;
        var instNum = parseInt(params.instNumber) || 0;

        var nossoNum = padLeft(bankConfig.nossoNumero + instNum, bankConfig.nossoNumDigitos);
        var nossoNumComDv = nossoNum + '-' + mod11(nossoNum);
        var numDocumento = orderNum + '-' + padLeft(instNum, 2);

        var codigoBarras = gerarCodigoBarras(bancoCode, params.dueDate, valorNum, nossoNum);
        var linhaDigitavel = gerarLinhaDigitavel(codigoBarras);
        var barcodeSvg = gerarBarcode128Svg(codigoBarras, 580, 55);

        var instrCompletas = instrucoes.slice();
        if (multaPct > 0 && !instrCompletas.some(l => l.toLowerCase().indexOf('multa') >= 0)) {
            instrCompletas.push('Multa de ' + multaPct.toFixed(2).replace('.', ',') + '% após o vencimento.');
        }
        if (jurosPct > 0 && !instrCompletas.some(l => l.toLowerCase().indexOf('juro') >= 0)) {
            instrCompletas.push('Juros de ' + jurosPct.toFixed(2).replace('.', ',') + '% ao mês por atraso.');
        }

        var custName = params.customer || '—';
        var custDoc = params.customerDoc || '';
        var custAddr = params.customerAddr || '';

        var boletoHtml = `
        <div class="boleto-page">
            <!-- RECIBO DO SACADO -->
            <div class="recibo-sacado">
                <table class="topo w100">
                    <tr>
                        <td class="topo-logo"><strong class="banco-nome">${bancoNome}</strong></td>
                        <td class="topo-codigo"><span class="banco-numero">${bancoFull}</span></td>
                        <td class="topo-ld"><span class="linha-digitavel">${linhaDigitavel}</span></td>
                    </tr>
                </table>
                <table class="w100 body-table">
                    <tr>
                        <td class="cell" style="width:60%;"><span class="lbl">Beneficiário</span><br><strong>${bankConfig.cedente}</strong><br><small>${bankConfig.cedenteDoc}</small></td>
                        <td class="cell" style="width:20%;"><span class="lbl">Agência/Cód. Beneficiário</span><br>${agCodCedente}</td>
                        <td class="cell" style="width:20%;"><span class="lbl">Nosso Número</span><br><strong>${nossoNumComDv}</strong></td>
                    </tr>
                    <tr>
                        <td class="cell"><span class="lbl">Pagador</span><br>${custName}${custDoc ? ' — CPF/CNPJ: ' + custDoc : ''}</td>
                        <td class="cell"><span class="lbl">Vencimento</span><br><strong class="venc">${dueDateFmt}</strong></td>
                        <td class="cell"><span class="lbl">Valor Documento</span><br><strong class="valor">R$ ${formatCurrency(valorNum)}</strong></td>
                    </tr>
                    <tr>
                        <td class="cell"><span class="lbl">Endereço Pagador</span><br><small>${custAddr || '—'}</small></td>
                        <td class="cell" colspan="2">
                            <span class="lbl">Nº Documento</span> ${numDocumento}
                            &nbsp;|&nbsp; <span class="lbl">Parcela</span> ${parcLabel}
                            &nbsp;|&nbsp; <span class="lbl">Pedido</span> #${orderNum}
                        </td>
                    </tr>
                </table>
                <div class="recibo-footer">
                    <span class="tesoura">✂</span>
                    <span class="recibo-texto">Recibo do Sacado</span>
                </div>
            </div>
            <!-- FICHA DE COMPENSAÇÃO — Padrão FEBRABAN (CNAB 240/400) -->
            <div class="ficha-compensacao">
                <table class="topo w100">
                    <tr>
                        <td class="topo-logo"><strong class="banco-nome">${bancoNome}</strong></td>
                        <td class="topo-codigo"><span class="banco-numero">${bancoFull}</span></td>
                        <td class="topo-ld"><span class="linha-digitavel">${linhaDigitavel}</span></td>
                    </tr>
                </table>
                <table class="w100 body-table fc-body">
                    <tr>
                        <td class="cell" colspan="6"><span class="lbl">Local de Pagamento</span><br>${bankConfig.localPagamento}</td>
                        <td class="cell r" style="width:25%;"><span class="lbl">Vencimento</span><br><strong class="venc venc-destaque">${dueDateFmt}</strong></td>
                    </tr>
                    <tr>
                        <td class="cell" colspan="6"><span class="lbl">Beneficiário</span><br><strong>${bankConfig.cedente}</strong> — CNPJ/CPF: ${bankConfig.cedenteDoc}<br><small>${cedenteAddr}</small></td>
                        <td class="cell r"><span class="lbl">Agência / Código Cedente</span><br><strong>${agCodCedente}</strong></td>
                    </tr>
                    <tr>
                        <td class="cell"><span class="lbl">Data do Documento</span><br>${dataProcessamento}</td>
                        <td class="cell" colspan="2"><span class="lbl">Nº do Documento</span><br>${numDocumento}</td>
                        <td class="cell"><span class="lbl">Espécie Doc.</span><br>${bankConfig.especieDoc}</td>
                        <td class="cell"><span class="lbl">Aceite</span><br>${bankConfig.aceite}</td>
                        <td class="cell"><span class="lbl">Data Processamento</span><br>${dataProcessamento}</td>
                        <td class="cell r"><span class="lbl">Nosso Número</span><br><strong>${nossoNumComDv}</strong></td>
                    </tr>
                    <tr>
                        <td class="cell"><span class="lbl">Uso do Banco</span><br>&nbsp;</td>
                        <td class="cell"><span class="lbl">Carteira</span><br>${bankConfig.carteira}</td>
                        <td class="cell"><span class="lbl">Espécie</span><br>${bankConfig.especie}</td>
                        <td class="cell" colspan="2"><span class="lbl">Quantidade</span><br>&nbsp;</td>
                        <td class="cell"><span class="lbl">(x) Valor</span><br>&nbsp;</td>
                        <td class="cell r"><span class="lbl">(=) Valor do Documento</span><br><strong class="valor">R$ ${formatCurrency(valorNum)}</strong></td>
                    </tr>
                    <tr>
                        <td class="cell instrucoes" colspan="6" rowspan="5">
                            <span class="lbl">Instruções (Texto de responsabilidade do beneficiário)</span><br>
                            ${instrCompletas.map(l => l.trim()).filter(l => l).map(l => '<span class="inst-line">• ' + l + '</span>').join('<br>')}
                            ${bankConfig.demonstrativo ? '<br><br><span class="lbl">Demonstrativo:</span><br><span class="inst-line">' + bankConfig.demonstrativo + '</span>' : ''}
                            <br><br>
                            <span class="inst-line"><strong>Ref: Pedido #${orderNum} — Parcela: ${parcLabel}</strong></span>
                        </td>
                        <td class="cell r"><span class="lbl">(-) Desconto / Abatimento</span><br>&nbsp;</td>
                    </tr>
                    <tr><td class="cell r"><span class="lbl">(-) Outras Deduções</span><br>&nbsp;</td></tr>
                    <tr><td class="cell r"><span class="lbl">(+) Mora / Multa</span><br>&nbsp;</td></tr>
                    <tr><td class="cell r"><span class="lbl">(+) Outros Acréscimos</span><br>&nbsp;</td></tr>
                    <tr><td class="cell r"><span class="lbl">(=) Valor Cobrado</span><br>&nbsp;</td></tr>
                    <tr>
                        <td class="cell sacado" colspan="7">
                            <span class="lbl">Sacado / Pagador</span><br>
                            <strong>${custName}</strong>${custDoc ? ' — CPF/CNPJ: ' + custDoc : ''}<br>
                            ${custAddr || ''}
                        </td>
                    </tr>
                    <tr>
                        <td class="cell" colspan="5" style="border-bottom:none;">
                            <span class="lbl">Sacador/Avalista</span><br>&nbsp;
                        </td>
                        <td class="cell" colspan="2" style="border-bottom:none;text-align:right;">
                            <span class="lbl">Cód. Baixa</span><br>&nbsp;
                        </td>
                    </tr>
                </table>
                <!-- Código de Barras ITF (Interleaved 2 of 5 — Padrão FEBRABAN) -->
                <div class="barcode-area">
                    <div class="barcode-svg">${barcodeSvg}</div>
                    <div class="barcode-numeros">${codigoBarras}</div>
                </div>
                <div class="fc-rodape">
                    <span>Ficha de Compensação — Autenticação Mecânica</span>
                    <span>FEBRABAN — CNAB 240/400</span>
                </div>
            </div>
        </div>`;

        return boletoHtml;
    }

    // Abrir janela de impressão com boleto FEBRABAN completo
    function abrirJanelaBoleto(boletoHtml, orderNum, bancoNome, bancoFull) {
        var printWin = window.open('', '_blank', 'width=850,height=1000');
        printWin.document.write(`<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Boleto Bancário — Pedido #${orderNum}</title>
<style>
    @page { size: A4 portrait; margin: 8mm 10mm; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, Helvetica, sans-serif; color: #000; font-size: 10px; line-height: 1.3; background: #fff; }
    .w100 { width: 100%; border-collapse: collapse; }
    table.topo { border-collapse: collapse; }
    table.topo td { border: 2px solid #000; padding: 4px 8px; vertical-align: middle; }
    .topo-logo { width: 22%; }
    .topo-codigo { width: 13%; text-align: center; }
    .topo-ld { width: 65%; }
    .banco-nome { font-size: 13px; font-weight: bold; }
    .banco-numero { font-size: 22px; font-weight: bold; letter-spacing: 1px; }
    .linha-digitavel { font-size: 13px; font-weight: bold; letter-spacing: 0.8px; text-align: right; display: block; font-family: 'Courier New', monospace; }
    .body-table { border-collapse: collapse; }
    .cell { border: 1px solid #000; padding: 2px 5px; vertical-align: top; font-size: 9px; }
    .cell.r { text-align: right; }
    .lbl { font-size: 6.5px; color: #444; text-transform: uppercase; display: block; margin-bottom: 1px; letter-spacing: 0.3px; }
    .venc { font-size: 13px; font-weight: bold; }
    .venc-destaque { font-size: 14px; }
    .valor { font-size: 12px; font-weight: bold; }
    .inst-line { font-size: 9px; line-height: 1.6; display: block; }
    .instrucoes { min-height: 90px; vertical-align: top; }
    .sacado { min-height: 36px; }
    .recibo-sacado { margin-bottom: 0; }
    .recibo-footer { display: flex; align-items: center; justify-content: center; gap: 15px; padding: 2px 0; font-size: 8px; color: #777; border-bottom: 1px dashed #999; margin-bottom: 3px; letter-spacing: 0.5px; }
    .recibo-footer .tesoura { font-size: 14px; }
    .recibo-footer .recibo-texto { text-transform: uppercase; }
    .ficha-compensacao { margin-top: 4px; }
    .barcode-area { padding: 6px 0 2px 0; text-align: left; }
    .barcode-svg svg { max-width: 100%; height: 55px; }
    .barcode-numeros { font-family: 'Courier New', monospace; font-size: 8px; color: #555; letter-spacing: 2px; margin-top: 2px; }
    .fc-rodape { display: flex; justify-content: space-between; font-size: 7px; color: #666; padding: 4px 4px 0; border-top: 2px solid #000; }
    .boleto-page { margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid #eee; }
    .no-print { text-align: center; padding: 20px; background: #f8f8f8; border-top: 2px solid #ddd; margin-top: 10px; }
    .no-print .info-texto { font-size: 11px; color: #666; margin-bottom: 10px; }
    @media print { .no-print { display: none !important; } .boleto-page { page-break-inside: avoid; border-bottom: none; margin-bottom: 0; } }
</style></head><body>
    <div class="no-print">
        <p class="info-texto">
            <strong>📄 Boleto Bancário — Pedido #${orderNum}</strong><br>
            Banco: <strong>${bancoNome} (${bancoFull})</strong> | Cedente: <strong>${bankConfig.cedente}</strong><br>
            <small>Boleto gerado conforme padrão FEBRABAN (CNAB 240/400) com código de barras Interleaved 2 of 5</small>
        </p>
        <button onclick="window.print()" style="padding:10px 30px;font-size:14px;cursor:pointer;border:2px solid #333;border-radius:4px;background:#fff;font-weight:bold;">🖨️ Imprimir Boleto</button>
        <button onclick="window.close()" style="padding:10px 20px;font-size:14px;cursor:pointer;border:1px solid #ccc;border-radius:4px;background:#f5f5f5;margin-left:8px;">Fechar</button>
    </div>
    ${boletoHtml}
    <div class="no-print" style="margin-top:20px;">
        <button onclick="window.print()" style="padding:10px 30px;font-size:14px;cursor:pointer;border:2px solid #333;border-radius:4px;background:#fff;font-weight:bold;">🖨️ Imprimir Boleto</button>
        <button onclick="window.close()" style="padding:10px 20px;font-size:14px;cursor:pointer;border:1px solid #ccc;border-radius:4px;background:#f5f5f5;margin-left:8px;">Fechar</button>
    </div>
</body></html>`);
        printWin.document.close();
        printWin.focus();
    }

    // ═══════════════════════════════════════════════════════════════════
    // EVENT HANDLERS
    // ═══════════════════════════════════════════════════════════════════

    // ── Busca instantânea ──
    const searchInput = document.getElementById('searchPayments');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const term = this.value.toLowerCase();
            document.querySelectorAll('#paymentsTable tbody tr').forEach(tr => {
                tr.style.display = tr.textContent.toLowerCase().includes(term) ? '' : 'none';
            });
        });
    }

    // ── Abrir modal de registrar pagamento ──
    document.querySelectorAll('.btn-register-pay').forEach(btn => {
        btn.addEventListener('click', function() {
            const id          = this.dataset.id;
            const orderId     = this.dataset.orderId;
            const amount      = parseFloat(this.dataset.amount);
            const num         = this.dataset.number;
            const customer    = this.dataset.customer || '';
            const orderMethod = this.dataset.orderMethod || '';
            const dueDate     = this.dataset.dueDate || '';

            document.getElementById('payInstId').value = id;
            document.getElementById('payOrderId').value = orderId;
            document.getElementById('payOrderDisplay').textContent = '#' + String(orderId).padStart(4, '0');
            document.getElementById('payNumber').textContent = (num == 0) ? 'Entrada' : num + 'ª';
            document.getElementById('payAmountDisplay').textContent = 'R$ ' + amount.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
            document.getElementById('payAmountInput').value = amount.toFixed(2);
            document.getElementById('payAmountInput').dataset.originalAmount = amount.toFixed(2);
            document.getElementById('payCustomerDisplay').textContent = customer ? 'Cliente: ' + customer : '';

            // Pré-selecionar forma de pagamento do pedido
            const methodSelect = document.getElementById('payMethodSelect');
            const hintEl = document.getElementById('payMethodHint');
            if (orderMethod) {
                methodSelect.value = orderMethod;
                hintEl.innerHTML = '<i class="fas fa-info-circle me-1"></i>Forma de pagamento pré-selecionada do pedido. Você pode alterá-la se necessário.';
            } else {
                methodSelect.selectedIndex = 0;
                hintEl.textContent = '';
            }

            // Mostrar/ocultar seção de boleto
            toggleBoletoSection(methodSelect.value, dueDate);

            new bootstrap.Modal(document.getElementById('modalPay')).show();
        });
    });

    // ── Toggle seção boleto ao mudar método ──
    const payMethodSelect = document.getElementById('payMethodSelect');
    if (payMethodSelect) {
        payMethodSelect.addEventListener('change', function() {
            toggleBoletoSection(this.value, '');
        });
    }

    function toggleBoletoSection(method, dueDate) {
        const boletoSection = document.getElementById('payBoletoSection');
        const mesRefEl = document.getElementById('payBoletoMesRef');
        if (method === 'boleto') {
            boletoSection.classList.remove('d-none');
            if (dueDate) {
                const parts = dueDate.split('-');
                const meses = ['','Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
                const mesNum = parseInt(parts[1]);
                mesRefEl.textContent = meses[mesNum] + '/' + parts[0];
            } else {
                mesRefEl.textContent = 'Mês atual';
            }
        } else {
            boletoSection.classList.add('d-none');
        }
    }

    // ── Print boleto do modal de pagamento (gera FEBRABAN real) ──
    document.getElementById('btnPrintBoletoModal')?.addEventListener('click', function() {
        // Usar dados do modal para gerar boleto
        var orderId  = document.getElementById('payOrderId').value;
        var amount   = parseFloat(document.getElementById('payAmountInput').value) || 0;
        var num      = document.getElementById('payNumber').textContent;
        var customer = (document.getElementById('payCustomerDisplay').textContent || '').replace('Cliente: ', '');

        var html = gerarBoletoHTML({
            orderId: orderId,
            parcLabel: num,
            dueDate: new Date().toISOString().split('T')[0], // data atual como fallback
            valor: amount,
            customer: customer,
            customerDoc: '',
            customerAddr: '',
            instNumber: 0
        });

        if (html) {
            var bancoNome = bancosNomes[bankConfig.banco] || ('Banco ' + bankConfig.banco);
            var bancoCode = padLeft(bankConfig.banco, 3);
            var bancoFull = bancoCode + '-' + mod11(bancoCode);
            abrirJanelaBoleto(html, String(orderId).padStart(4, '0'), bancoNome, bancoFull);
        }
    });

    // ── Modal Reimprimir Boleto (botão na tabela) — preenche modal ──
    var currentBoletoData = {};
    document.querySelectorAll('.btn-print-boleto').forEach(btn => {
        btn.addEventListener('click', function() {
            const instId       = this.dataset.id;
            const orderId      = this.dataset.orderId;
            const amount       = parseFloat(this.dataset.amount);
            const due          = this.dataset.due;
            const mesRef       = this.dataset.mesRef;
            const customer     = this.dataset.customer || '';
            const customerDoc  = this.dataset.customerDoc || '';
            const customerAddr = this.dataset.customerAddr || '';
            const instNumber   = this.dataset.number || '0';

            document.getElementById('boletoOrderDisplay').textContent = '#' + String(orderId).padStart(4, '0');
            document.getElementById('boletoCustomerDisplay').textContent = customer || 'N/A';
            document.getElementById('boletoAmountDisplay').textContent = 'R$ ' + amount.toLocaleString('pt-BR', { minimumFractionDigits: 2 });

            const dParts = due.split('-');
            document.getElementById('boletoDueDisplay').textContent = dParts[2] + '/' + dParts[1] + '/' + dParts[0];
            document.getElementById('boletoMesInput').value = dParts[0] + '-' + dParts[1];

            // Guardar dados completos para geração
            currentBoletoData = {
                orderId: orderId,
                parcLabel: (instNumber == '0') ? 'Entrada' : instNumber + 'ª',
                dueDate: due,
                valor: amount,
                customer: customer,
                customerDoc: customerDoc,
                customerAddr: customerAddr,
                instNumber: instNumber
            };

            new bootstrap.Modal(document.getElementById('modalBoleto')).show();
        });
    });

    // ── Confirmar impressão do boleto (gera FEBRABAN real) ──
    document.getElementById('btnConfirmPrintBoleto')?.addEventListener('click', function() {
        var mesRefInput = document.getElementById('boletoMesInput').value;

        // Se o mês referente mudou, ajustar a data de vencimento para o último dia do mês selecionado
        if (mesRefInput && currentBoletoData.dueDate) {
            var origParts = currentBoletoData.dueDate.split('-');
            var newParts = mesRefInput.split('-');
            // Manter o dia original, mas trocar mês/ano se alterado
            if (newParts[0] !== origParts[0] || newParts[1] !== origParts[1]) {
                var newDay = origParts[2];
                // Verificar se o dia existe no novo mês
                var lastDay = new Date(parseInt(newParts[0]), parseInt(newParts[1]), 0).getDate();
                if (parseInt(newDay) > lastDay) newDay = String(lastDay);
                currentBoletoData.dueDate = newParts[0] + '-' + newParts[1] + '-' + padLeft(newDay, 2);
            }
        }

        var html = gerarBoletoHTML(currentBoletoData);
        if (html) {
            var bancoNome = bancosNomes[bankConfig.banco] || ('Banco ' + bankConfig.banco);
            var bancoCode = padLeft(bankConfig.banco, 3);
            var bancoFull = bancoCode + '-' + mod11(bancoCode);
            abrirJanelaBoleto(html, String(currentBoletoData.orderId).padStart(4, '0'), bancoNome, bancoFull);
            bootstrap.Modal.getInstance(document.getElementById('modalBoleto'))?.hide();
        }
    });

    // ── Modal Anexar Comprovante ──
    document.querySelectorAll('.btn-attach').forEach(btn => {
        btn.addEventListener('click', function() {
            const instId        = this.dataset.id;
            const hasAttachment = this.dataset.hasAttachment === '1';
            const attachPath    = this.dataset.attachmentPath || '';

            document.getElementById('uploadAttachInstId').value = instId;
            document.getElementById('removeAttachInstId').value = instId;

            const previewDiv    = document.getElementById('attachPreview');
            const previewImg    = document.getElementById('attachPreviewImg');
            const previewPdf    = document.getElementById('attachPreviewPdf');
            const imgTag        = document.getElementById('attachImgTag');
            const viewLink      = document.getElementById('attachViewLink');
            const uploadLabel   = document.getElementById('attachUploadLabel');

            if (hasAttachment && attachPath) {
                previewDiv.classList.remove('d-none');
                viewLink.href = attachPath;
                const isImage = /\.(jpg|jpeg|png|gif|webp)$/i.test(attachPath);
                if (isImage) {
                    previewImg.classList.remove('d-none');
                    previewPdf.classList.add('d-none');
                    imgTag.src = attachPath;
                } else {
                    previewImg.classList.add('d-none');
                    previewPdf.classList.remove('d-none');
                }
                uploadLabel.textContent = 'Substituir comprovante';
            } else {
                previewDiv.classList.add('d-none');
                previewImg.classList.add('d-none');
                previewPdf.classList.add('d-none');
                uploadLabel.textContent = 'Enviar comprovante';
            }

            new bootstrap.Modal(document.getElementById('modalAttach')).show();
        });
    });

    // ── Remover comprovante com SweetAlert2 ──
    document.querySelectorAll('.btn-remove-attach').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const form = this.closest('form');
            Swal.fire({
                title: 'Remover comprovante?',
                text: 'O arquivo anexado será removido permanentemente.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74c3c',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-trash me-1"></i> Remover',
                cancelButtonText: 'Manter'
            }).then(result => {
                if (result.isConfirmed) form.submit();
            });
        });
    });

    // ── Registrar pagamento com fluxo inteligente ──
    // Se pago < total → pergunta se quer criar parcela restante
    // Se pago >= total → confirma automaticamente (sem etapa extra)
    const formPay = document.getElementById('formPay');
    if (formPay) {
        formPay.addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            const paidAmount = parseFloat(document.getElementById('payAmountInput').value) || 0;
            const originalAmount = parseFloat(document.getElementById('payAmountInput').dataset.originalAmount) || paidAmount;

            // Valor pago é menor que o valor da parcela?
            if (paidAmount > 0 && paidAmount < originalAmount) {
                var restante = (originalAmount - paidAmount).toFixed(2);
                var restanteFmt = parseFloat(restante).toLocaleString('pt-BR', { minimumFractionDigits: 2 });
                var pagoFmt = paidAmount.toLocaleString('pt-BR', { minimumFractionDigits: 2 });

                Swal.fire({
                    title: 'Pagamento parcial detectado',
                    html: '<div class="text-start">' +
                          '<p>O valor pago (<strong>R$ ' + pagoFmt + '</strong>) é menor que o valor da parcela (<strong>R$ ' + parseFloat(originalAmount).toLocaleString('pt-BR', { minimumFractionDigits: 2 }) + '</strong>).</p>' +
                          '<p>Valor restante: <strong class="text-danger">R$ ' + restanteFmt + '</strong></p>' +
                          '<hr>' +
                          '<p class="mb-2"><strong>Deseja criar uma nova parcela com o valor restante?</strong></p>' +
                          '<div class="mb-3" id="swalDueDateContainer">' +
                          '  <label class="form-label small fw-bold">Vencimento da nova parcela:</label>' +
                          '  <input type="date" id="swalRemainingDueDate" class="form-control form-control-sm" value="' + getDefaultDueDate() + '">' +
                          '</div>' +
                          '</div>',
                    icon: 'question',
                    showCancelButton: true,
                    showDenyButton: true,
                    confirmButtonColor: '#27ae60',
                    denyButtonColor: '#3085d6',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-plus-circle me-1"></i> Sim, criar parcela restante',
                    denyButtonText: '<i class="fas fa-check me-1"></i> Não, quitar assim mesmo',
                    cancelButtonText: 'Cancelar',
                    reverseButtons: false,
                    customClass: { popup: 'text-start' }
                }).then(function(result) {
                    if (result.isConfirmed) {
                        // Criar parcela restante
                        submitPaymentAjax(form, 1, document.getElementById('swalRemainingDueDate')?.value || '');
                    } else if (result.isDenied) {
                        // Quitar como está (auto-confirmar)
                        submitPaymentAjax(form, 0, '');
                    }
                });
            } else {
                // Valor igual ou maior → confirma automaticamente
                Swal.fire({
                    title: 'Confirmar pagamento?',
                    text: 'O pagamento será registrado e confirmado automaticamente.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#27ae60',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-check me-1"></i> Confirmar Pagamento',
                    cancelButtonText: 'Cancelar'
                }).then(function(result) {
                    if (result.isConfirmed) {
                        submitPaymentAjax(form, 0, '');
                    }
                });
            }
        });
    }

    function getDefaultDueDate() {
        var d = new Date();
        d.setDate(d.getDate() + 30);
        return d.toISOString().split('T')[0];
    }

    function submitPaymentAjax(form, createRemaining, remainingDueDate) {
        var formData = new FormData(form);
        formData.append('create_remaining', createRemaining);
        if (remainingDueDate) {
            formData.append('remaining_due_date', remainingDueDate);
        }

        // Adicionar CSRF token via header
        var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        var btnSubmit = document.getElementById('btnSubmitPay');
        if (btnSubmit) {
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Processando...';
        }

        fetch('?page=financial&action=payInstallment', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken
            },
            body: formData
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                // Fechar modal
                var modal = bootstrap.Modal.getInstance(document.getElementById('modalPay'));
                if (modal) modal.hide();

                var msg = 'Pagamento registrado e confirmado!';
                if (data.remaining_created) {
                    var restFmt = parseFloat(data.remaining_amount).toLocaleString('pt-BR', { minimumFractionDigits: 2 });
                    msg = 'Pagamento registrado! Uma nova parcela de R$ ' + restFmt + ' foi criada para o valor restante.';
                }

                Swal.fire({
                    icon: 'success',
                    title: 'Sucesso!',
                    text: msg,
                    timer: 3000,
                    showConfirmButton: true,
                    confirmButtonText: 'OK'
                }).then(function() {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: data.message || 'Erro ao processar pagamento.'
                });
                if (btnSubmit) {
                    btnSubmit.disabled = false;
                    btnSubmit.innerHTML = '<i class="fas fa-check me-1"></i> Registrar Pagamento';
                }
            }
        })
        .catch(function(err) {
            Swal.fire({
                icon: 'error',
                title: 'Erro de Conexão',
                text: 'Não foi possível processar o pagamento. Tente novamente.'
            });
            if (btnSubmit) {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = '<i class="fas fa-check me-1"></i> Registrar Pagamento';
            }
        });
    }

    // ── Confirmar pagamento via SweetAlert2 ──
    document.querySelectorAll('.btn-confirm').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const form = this.closest('form');
            Swal.fire({
                title: 'Confirmar pagamento?',
                text: 'A parcela será marcada como confirmada no sistema.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#27ae60',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-check-double me-1"></i> Confirmar',
                cancelButtonText: 'Cancelar'
            }).then(result => {
                if (result.isConfirmed) form.submit();
            });
        });
    });

    // ── Estornar com SweetAlert2 ──
    document.querySelectorAll('.btn-estornar').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const form = this.closest('form');
            Swal.fire({
                title: 'Estornar pagamento?',
                html: 'O pagamento será <strong>revertido</strong> e registrado como <strong>saída</strong> no livro caixa.<br><small class="text-muted">A parcela voltará como pendente.</small>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74c3c',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-undo me-1"></i> Estornar',
                cancelButtonText: 'Manter'
            }).then(result => {
                if (result.isConfirmed) form.submit();
            });
        });
    });

});
</script>
