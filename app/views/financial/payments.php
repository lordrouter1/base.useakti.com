<?php
/**
 * Fiscal — Pagamentos de Pedidos
 * Variáveis: $orders
 */
$filterStatus = $_GET['status'] ?? '';
$filterMonth  = $_GET['filter_month'] ?? '';
$filterYear   = $_GET['filter_year'] ?? '';
?>

<?php if (!empty($_SESSION['flash_success'])): ?>
<script>document.addEventListener('DOMContentLoaded', () => Swal.fire({ icon:'success', title:'Sucesso!', text:'<?= addslashes($_SESSION['flash_success']) ?>', timer:2500, showConfirmButton:false }));</script>
<?php unset($_SESSION['flash_success']); endif; ?>
<?php if (!empty($_SESSION['flash_error'])): ?>
<script>document.addEventListener('DOMContentLoaded', () => Swal.fire({ icon:'error', title:'Erro', text:'<?= addslashes($_SESSION['flash_error']) ?>' }));</script>
<?php unset($_SESSION['flash_error']); endif; ?>

<?php
$paymentStatusMap = [
    'pendente' => ['badge' => 'bg-warning text-dark', 'icon' => 'fas fa-clock',        'label' => 'Pendente'],
    'parcial'  => ['badge' => 'bg-info text-white',   'icon' => 'fas fa-adjust',       'label' => 'Parcial'],
    'pago'     => ['badge' => 'bg-success',            'icon' => 'fas fa-check-circle', 'label' => 'Pago'],
];

$pipelineStageMap = [
    'contato'    => ['label' => 'Contato',       'color' => '#9b59b6'],
    'orcamento'  => ['label' => 'Orçamento',     'color' => '#3498db'],
    'venda'      => ['label' => 'Venda',         'color' => '#2ecc71'],
    'producao'   => ['label' => 'Produção',      'color' => '#e67e22'],
    'preparacao' => ['label' => 'Preparação',    'color' => '#1abc9c'],
    'envio'      => ['label' => 'Envio/Entrega', 'color' => '#e74c3c'],
    'financeiro' => ['label' => 'Financeiro',    'color' => '#f39c12'],
    'concluido'  => ['label' => 'Concluído',     'color' => '#27ae60'],
];
?>

<!-- ══════ Header ══════ -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-file-invoice-dollar me-2 text-primary"></i>Pagamentos de Pedidos</h1>
    <div class="btn-toolbar mb-2 mb-md-0 gap-2">
        <a href="?page=financial" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Voltar ao Dashboard
        </a>
        <a href="?page=financial&action=transactions" class="btn btn-sm btn-outline-success">
            <i class="fas fa-exchange-alt me-1"></i> Entradas / Saídas
        </a>
    </div>
</div>

<!-- ══════ Filtros ══════ -->
<form method="get" class="row g-2 mb-3 align-items-end">
    <input type="hidden" name="page" value="financial">
    <input type="hidden" name="action" value="payments">
    <div class="col-auto">
        <label class="form-label small fw-bold mb-1">Status Pagamento</label>
        <select name="status" class="form-select form-select-sm" style="width:160px">
            <option value="">Todos</option>
            <option value="pendente" <?= $filterStatus==='pendente'?'selected':'' ?>>Pendentes</option>
            <option value="pago" <?= $filterStatus==='pago'?'selected':'' ?>>Pagos</option>
        </select>
    </div>
    <div class="col-auto">
        <label class="form-label small fw-bold mb-1">Mês</label>
        <select name="filter_month" class="form-select form-select-sm" style="width:130px">
            <option value="">Todos</option>
            <?php 
            $mn = ['','Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
            for($m=1;$m<=12;$m++): ?>
            <option value="<?= $m ?>" <?= $filterMonth==$m?'selected':'' ?>><?= $mn[$m] ?></option>
            <?php endfor; ?>
        </select>
    </div>
    <div class="col-auto">
        <label class="form-label small fw-bold mb-1">Ano</label>
        <select name="filter_year" class="form-select form-select-sm" style="width:100px">
            <option value="">Todos</option>
            <?php for($y=date('Y')-2;$y<=date('Y')+1;$y++): ?>
            <option value="<?= $y ?>" <?= $filterYear==$y?'selected':'' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i> Filtrar</button>
        <a href="?page=financial&action=payments" class="btn btn-sm btn-outline-secondary"><i class="fas fa-times"></i></a>
    </div>
</form>

<!-- ══════ Busca Rápida ══════ -->
<div class="mb-3">
    <div class="input-group">
        <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
        <input type="text" class="form-control" id="searchPayments" placeholder="Buscar por nº pedido, cliente, status..." autocomplete="off">
    </div>
</div>

<!-- ══════ Tabela de Pedidos ══════ -->
<div class="table-responsive bg-white rounded shadow-sm">
    <table class="table table-hover align-middle mb-0" id="paymentsTable">
        <thead class="bg-light">
            <tr>
                <th class="py-3 ps-3">Pedido</th>
                <th class="py-3">Cliente</th>
                <th class="py-3">Data</th>
                <th class="py-3">Valor Total</th>
                <th class="py-3">Desconto</th>
                <th class="py-3">Entrada</th>
                <th class="py-3">Parcelas</th>
                <th class="py-3">Pago</th>
                <th class="py-3">Etapa</th>
                <th class="py-3">Status Pgto</th>
                <th class="py-3 text-end pe-3">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($orders)): ?>
            <tr><td colspan="11" class="text-center text-muted py-4"><i class="fas fa-inbox fa-2x mb-2 d-block"></i>Nenhum pedido encontrado.</td></tr>
            <?php else: ?>
            <?php foreach($orders as $o): 
                $net = $o['total_amount'] - ($o['discount'] ?? 0);
                $pctPaid = $net > 0 ? min(100, round(($o['total_pago'] / $net) * 100)) : 0;
                $ps = $paymentStatusMap[$o['payment_status'] ?? 'pendente'] ?? $paymentStatusMap['pendente'];
                $stage = $pipelineStageMap[$o['pipeline_stage'] ?? ''] ?? ['label' => ucfirst($o['pipeline_stage'] ?? ''), 'color' => '#999'];
            ?>
            <tr>
                <td class="ps-3 fw-bold">
                    <a href="?page=pipeline&action=detail&id=<?= $o['id'] ?>" class="text-decoration-none text-dark">
                        #<?= str_pad($o['id'], 4, '0', STR_PAD_LEFT) ?>
                    </a>
                </td>
                <td><?= htmlspecialchars($o['customer_name'] ?? 'N/A') ?></td>
                <td class="small"><?= date('d/m/Y', strtotime($o['created_at'])) ?></td>
                <td class="fw-bold">R$ <?= number_format($o['total_amount'], 2, ',', '.') ?></td>
                <td class="small text-muted"><?= ($o['discount'] ?? 0) > 0 ? 'R$ ' . number_format($o['discount'], 2, ',', '.') : '—' ?></td>
                <td class="small"><?= ($o['down_payment'] ?? 0) > 0 ? 'R$ ' . number_format($o['down_payment'], 2, ',', '.') : '—' ?></td>
                <td>
                    <?php if($o['total_parcelas'] > 0): ?>
                        <span class="badge bg-secondary"><?= $o['parcelas_pagas'] ?>/<?= $o['total_parcelas'] ?></span>
                    <?php else: ?>
                        <span class="text-muted small">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div class="progress flex-grow-1" style="height:6px;min-width:60px;">
                            <div class="progress-bar <?= $pctPaid >= 100 ? 'bg-success' : ($pctPaid > 0 ? 'bg-info' : 'bg-secondary') ?>" style="width:<?= $pctPaid ?>%"></div>
                        </div>
                        <small class="fw-bold" style="min-width:35px"><?= $pctPaid ?>%</small>
                    </div>
                </td>
                <td>
                    <span class="badge rounded-pill px-2 py-1" style="background:<?= $stage['color'] ?>;font-size:0.7rem;">
                        <?= $stage['label'] ?>
                    </span>
                </td>
                <td>
                    <span class="badge <?= $ps['badge'] ?>">
                        <i class="<?= $ps['icon'] ?> me-1"></i><?= $ps['label'] ?>
                    </span>
                </td>
                <td class="text-end pe-3">
                    <div class="btn-group btn-group-sm">
                        <?php if($o['total_parcelas'] == 0 && $o['payment_status'] !== 'pago'): ?>
                            <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#generateModal"
                                    data-order-id="<?= $o['id'] ?>" data-order-total="<?= $net ?>"
                                    title="Gerar Parcelas">
                                <i class="fas fa-plus-circle me-1"></i> Parcelas
                            </button>
                        <?php endif; ?>
                        <a href="?page=financial&action=installments&order_id=<?= $o['id'] ?>" class="btn btn-outline-secondary" title="Ver Parcelas">
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

<!-- ══════ Modal: Gerar Parcelas ══════ -->
<div class="modal fade" id="generateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="?page=financial&action=generateInstallments">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-calculator me-2"></i>Gerar Parcelas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="order_id" id="genOrderId">
                    <div class="alert alert-info py-2 mb-3">
                        <strong>Pedido:</strong> <span id="genOrderLabel"></span> — 
                        <strong>Total:</strong> R$ <span id="genOrderTotal"></span>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nº de Parcelas</label>
                            <input type="number" name="num_installments" id="genNumInst" class="form-control" min="1" max="48" value="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Entrada (R$)</label>
                            <input type="number" name="down_payment" id="genDownPayment" class="form-control" min="0" step="0.01" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Data Início</label>
                            <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Valor Parcela</label>
                            <input type="text" id="genInstValue" class="form-control bg-light" readonly>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-check me-1"></i> Gerar Parcelas</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Busca na tabela
    const searchInput = document.getElementById('searchPayments');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const term = this.value.toLowerCase();
            document.querySelectorAll('#paymentsTable tbody tr').forEach(tr => {
                tr.style.display = tr.textContent.toLowerCase().includes(term) ? '' : 'none';
            });
        });
    }

    // Modal: Gerar Parcelas
    const genModal = document.getElementById('generateModal');
    if (genModal) {
        genModal.addEventListener('show.bs.modal', function(e) {
            const btn = e.relatedTarget;
            const orderId = btn.getAttribute('data-order-id');
            const total = parseFloat(btn.getAttribute('data-order-total'));
            document.getElementById('genOrderId').value = orderId;
            document.getElementById('genOrderLabel').textContent = '#' + orderId.toString().padStart(4, '0');
            document.getElementById('genOrderTotal').textContent = total.toLocaleString('pt-BR', {minimumFractionDigits: 2});
            calcInstValue();
        });
    }

    function calcInstValue() {
        const total = parseFloat(document.getElementById('genOrderTotal')?.textContent?.replace('.', '').replace(',', '.') || 0);
        const num = parseInt(document.getElementById('genNumInst')?.value || 1);
        const dp = parseFloat(document.getElementById('genDownPayment')?.value || 0);
        const val = num > 0 ? ((total - dp) / num) : total;
        const el = document.getElementById('genInstValue');
        if (el) el.value = 'R$ ' + Math.max(0, val).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    document.getElementById('genNumInst')?.addEventListener('input', calcInstValue);
    document.getElementById('genDownPayment')?.addEventListener('input', calcInstValue);
});
</script>
