<?php
/**
 * Financeiro — Entradas e Saídas
 * Registro manual de transações financeiras (despesas fixas, compras, etc.)
 * Variáveis: $transactions, $categories, $totalEntradas, $totalSaidas
 */
$filterType     = $_GET['type'] ?? '';
$filterMonth    = $_GET['filter_month'] ?? '';
$filterYear     = $_GET['filter_year'] ?? '';
$filterCategory = $_GET['category'] ?? '';
$saldo = ($totalEntradas ?? 0) - ($totalSaidas ?? 0);

// Merge categorias internas para exibição
$allCats = array_merge($categories['entrada'] ?? [], $categories['saida'] ?? [], \Akti\Models\Financial::getInternalCategories());
$methodLabels = [
    'dinheiro'=>'💵 Dinheiro','pix'=>'📱 PIX','cartao_credito'=>'💳 Crédito',
    'cartao_debito'=>'💳 Débito','boleto'=>'📄 Boleto','transferencia'=>'🏦 Transf.',
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
    <h1 class="h2 mb-0"><i class="fas fa-exchange-alt me-2 text-success"></i>Entradas e Saídas</h1>
    <div class="btn-toolbar gap-2">
        <a href="?page=financial" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Dashboard
        </a>
        <a href="?page=financial&action=payments" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-file-invoice-dollar me-1"></i> Pagamentos
        </a>
        <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#modalImportOfx">
            <i class="fas fa-file-import me-1"></i> Importar OFX
        </button>
        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalAddTransaction">
            <i class="fas fa-plus me-1"></i> Nova Transação
        </button>
    </div>
</div>

<!-- ══════ Cards Resumo ══════ -->
<div class="row g-3 mb-4">
    <div class="col-xl-4 col-md-4">
        <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
            <div class="card-body d-flex align-items-center p-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:50px;height:50px;background:rgba(39,174,96,0.15);">
                    <i class="fas fa-arrow-down fa-lg text-success"></i>
                </div>
                <div>
                    <div class="text-muted small text-uppercase">Entradas</div>
                    <div class="fw-bold fs-4 text-success">R$ <?= number_format($totalEntradas ?? 0, 2, ',', '.') ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-4">
        <div class="card border-0 shadow-sm h-100 border-start border-danger border-4">
            <div class="card-body d-flex align-items-center p-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:50px;height:50px;background:rgba(192,57,43,0.15);">
                    <i class="fas fa-arrow-up fa-lg text-danger"></i>
                </div>
                <div>
                    <div class="text-muted small text-uppercase">Saídas</div>
                    <div class="fw-bold fs-4 text-danger">R$ <?= number_format($totalSaidas ?? 0, 2, ',', '.') ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-4">
        <div class="card border-0 shadow-sm h-100 border-start <?= $saldo >= 0 ? 'border-primary' : 'border-warning' ?> border-4">
            <div class="card-body d-flex align-items-center p-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:50px;height:50px;background:<?= $saldo >= 0 ? 'rgba(52,152,219,0.15)' : 'rgba(243,156,18,0.15)' ?>;">
                    <i class="fas fa-balance-scale fa-lg <?= $saldo >= 0 ? 'text-primary' : 'text-warning' ?>"></i>
                </div>
                <div>
                    <div class="text-muted small text-uppercase">Saldo</div>
                    <div class="fw-bold fs-4 <?= $saldo >= 0 ? 'text-primary' : 'text-danger' ?>">R$ <?= number_format($saldo, 2, ',', '.') ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══════ Filtros ══════ -->
<form method="get" class="row g-2 mb-3 align-items-end">
    <input type="hidden" name="page" value="financial">
    <input type="hidden" name="action" value="transactions">
    <div class="col-auto">
        <label class="form-label small fw-bold mb-1">Tipo</label>
        <select name="type" class="form-select form-select-sm" style="width:140px">
            <option value="">Todos</option>
            <option value="entrada" <?= $filterType==='entrada'?'selected':'' ?>>Entradas</option>
            <option value="saida" <?= $filterType==='saida'?'selected':'' ?>>Saídas</option>
            <option value="registro" <?= $filterType==='registro'?'selected':'' ?>>Registros</option>
        </select>
    </div>
    <div class="col-auto">
        <label class="form-label small fw-bold mb-1">Categoria</label>
        <select name="category" class="form-select form-select-sm" style="width:180px">
            <option value="">Todas</option>
            <optgroup label="Entradas">
                <?php foreach ($categories['entrada'] ?? [] as $k => $v): ?>
                <option value="<?= $k ?>" <?= $filterCategory===$k?'selected':'' ?>><?= $v ?></option>
                <?php endforeach; ?>
            </optgroup>
            <optgroup label="Saídas">
                <?php foreach ($categories['saida'] ?? [] as $k => $v): ?>
                <option value="<?= $k ?>" <?= $filterCategory===$k?'selected':'' ?>><?= $v ?></option>
                <?php endforeach; ?>
            </optgroup>
        </select>
    </div>
    <div class="col-auto">
        <label class="form-label small fw-bold mb-1">Mês</label>
        <select name="filter_month" class="form-select form-select-sm" style="width:120px">
            <option value="">Todos</option>
            <?php $mn=['','Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez']; for($m=1;$m<=12;$m++): ?>
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
        <a href="?page=financial&action=transactions" class="btn btn-sm btn-outline-secondary"><i class="fas fa-times"></i></a>
    </div>
</form>

<!-- ══════ Busca ══════ -->
<div class="mb-3">
    <div class="input-group">
        <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
        <input type="text" class="form-control" id="searchTransactions" placeholder="Buscar por descrição, categoria..." autocomplete="off">
    </div>
</div>

<!-- ══════ Tabela ══════ -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="transactionsTable">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-3 py-3">Data</th>
                        <th class="py-3">Tipo</th>
                        <th class="py-3">Categoria</th>
                        <th class="py-3">Descrição</th>
                        <th class="py-3">Valor</th>
                        <th class="py-3">Método</th>
                        <th class="py-3">Registrado por</th>
                        <th class="py-3 text-end pe-3">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-2 d-block opacity-50"></i>Nenhuma transação encontrada.
                    </td></tr>
                    <?php else: ?>
                    <?php foreach ($transactions as $t):
                        $isRegistro = ($t['type'] === 'registro' || ($t['category'] ?? '') === 'estorno_pagamento' || ($t['category'] ?? '') === 'registro_ofx');
                    ?>
                    <tr<?= $isRegistro ? ' class="table-light"' : '' ?>>
                        <td class="ps-3 small"><?= date('d/m/Y', strtotime($t['transaction_date'])) ?></td>
                        <td>
                            <?php if ($isRegistro): ?>
                                <?php if (($t['category'] ?? '') === 'estorno_pagamento'): ?>
                                    <span class="badge bg-secondary"><i class="fas fa-minus me-1"></i>Estorno</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><i class="fas fa-minus me-1"></i>Registro</span>
                                <?php endif; ?>
                            <?php elseif ($t['type'] === 'entrada'): ?>
                                <span class="badge bg-success"><i class="fas fa-arrow-down me-1"></i>Entrada</span>
                            <?php else: ?>
                                <span class="badge bg-danger"><i class="fas fa-arrow-up me-1"></i>Saída</span>
                            <?php endif; ?>
                        </td>
                        <td class="small"><?= htmlspecialchars($allCats[$t['category']] ?? ucfirst($t['category'])) ?></td>
                        <td class="small"><?= htmlspecialchars($t['description']) ?></td>
                        <td class="fw-bold <?= $isRegistro ? 'text-secondary' : ($t['type']==='entrada' ? 'text-success' : 'text-danger') ?>">
                            <?php if ($isRegistro): ?>
                                — R$ <?= number_format($t['amount'], 2, ',', '.') ?>
                            <?php elseif ($t['type'] === 'entrada'): ?>
                                + R$ <?= number_format($t['amount'], 2, ',', '.') ?>
                            <?php else: ?>
                                - R$ <?= number_format($t['amount'], 2, ',', '.') ?>
                            <?php endif; ?>
                        </td>
                        <td class="small"><?= $methodLabels[$t['payment_method'] ?? ''] ?? ($t['payment_method'] ? ucfirst($t['payment_method']) : '—') ?></td>
                        <td class="small"><?= htmlspecialchars($t['user_name'] ?? '—') ?></td>
                        <td class="text-end pe-3">
                            <?php if (empty($t['reference_type']) || $t['reference_type'] === 'manual'): ?>
                            <form method="post" action="?page=financial&action=deleteTransaction" class="d-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="transaction_id" value="<?= $t['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger btn-delete-tx" title="Excluir">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            <?php else: ?>
                                <span class="badge bg-light text-muted border" style="font-size:0.65rem;">
                                    <?= $isRegistro ? 'Registro' : 'Automática' ?>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ══════ Modal Nova Transação ══════ -->
<div class="modal fade" id="modalAddTransaction" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" action="?page=financial&action=addTransaction" id="formAddTx">
                <?= csrf_field() ?>
                <div class="modal-header bg-success bg-opacity-10 border-0">
                    <h5 class="modal-title text-success"><i class="fas fa-plus-circle me-2"></i>Nova Transação</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Tipo</label>
                            <select name="type" id="txType" class="form-select" required>
                                <option value="entrada">✅ Entrada</option>
                                <option value="saida">🔴 Saída</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Categoria</label>
                            <select name="category" id="txCategory" class="form-select" required>
                                <?php foreach ($categories['entrada'] ?? [] as $k => $v): ?>
                                <option value="<?= $k ?>" data-type="entrada"><?= $v ?></option>
                                <?php endforeach; ?>
                                <?php foreach ($categories['saida'] ?? [] as $k => $v): ?>
                                <option value="<?= $k ?>" data-type="saida" style="display:none;"><?= $v ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Descrição</label>
                            <input type="text" name="description" class="form-control" placeholder="Ex: Compra de papel A4" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Valor (R$)</label>
                            <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Data</label>
                            <input type="date" name="transaction_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Forma de Pagamento</label>
                            <select name="payment_method" class="form-select">
                                <option value="">— Não informado —</option>
                                <option value="dinheiro">💵 Dinheiro</option>
                                <option value="pix">📱 PIX</option>
                                <option value="cartao_credito">💳 Cartão Crédito</option>
                                <option value="cartao_debito">💳 Cartão Débito</option>
                                <option value="boleto">📄 Boleto</option>
                                <option value="transferencia">🏦 Transferência</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Observação <span class="text-muted fw-normal">(opcional)</span></label>
                            <input type="text" name="notes" class="form-control" placeholder="Nota adicional">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check me-1"></i> Registrar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ══════ Modal Importar OFX ══════ -->
<div class="modal fade" id="modalImportOfx" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="formImportOfx" enctype="multipart/form-data">
                <div class="modal-header bg-info bg-opacity-10 border-0">
                    <h5 class="modal-title text-info"><i class="fas fa-file-import me-2"></i>Importar Extrato OFX</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Arquivo OFX</label>
                        <input type="file" name="ofx_file" class="form-control" accept=".ofx,.ofc" required>
                        <div class="form-text">Selecione o arquivo .OFX exportado do seu banco.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Modo de importação</label>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="import_mode" id="modeRegistro" value="registro" checked>
                            <label class="form-check-label" for="modeRegistro">
                                <span class="badge bg-secondary me-1"><i class="fas fa-minus me-1"></i>Registro</span>
                                <span class="text-muted small">Apenas para consulta — <strong>não contabiliza</strong> no caixa</span>
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="import_mode" id="modeContabilizar" value="contabilizar">
                            <label class="form-check-label" for="modeContabilizar">
                                <span class="badge bg-success me-1"><i class="fas fa-check me-1"></i>Contabilizar</span>
                                <span class="text-muted small">Créditos como <strong>entrada</strong> e débitos como <strong>saída</strong></span>
                            </label>
                        </div>
                    </div>
                    <div class="alert alert-light border small mb-0">
                        <i class="fas fa-info-circle text-info me-1"></i>
                        No modo <strong>Registro</strong>, as transações aparecem na lista com badge cinza e não somam nos totais.
                        No modo <strong>Contabilizar</strong>, cada transação entra como entrada ou saída real no caixa.
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-info text-white" id="btnImportOfx">
                        <i class="fas fa-upload me-1"></i> Importar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ══════ Scripts ══════ -->
<script>
document.addEventListener('DOMContentLoaded', function() {

    // ── Busca na tabela ──
    const s = document.getElementById('searchTransactions');
    if (s) s.addEventListener('input', function() {
        const t = this.value.toLowerCase();
        document.querySelectorAll('#transactionsTable tbody tr').forEach(tr => {
            tr.style.display = tr.textContent.toLowerCase().includes(t) ? '' : 'none';
        });
    });

    // ── Filtrar categorias pelo tipo ──
    const txType = document.getElementById('txType');
    const txCat  = document.getElementById('txCategory');
    if (txType && txCat) {
        function filterCats() {
            const type = txType.value;
            const defaultCat = type === 'entrada' ? 'outra_entrada' : 'outra_saida';
            let first = null;
            txCat.querySelectorAll('option').forEach(opt => {
                const show = opt.dataset.type === type;
                opt.style.display = show ? '' : 'none';
                if (show && !first) first = opt;
            });
            // Selecionar a categoria default
            const defaultOpt = txCat.querySelector('option[value="' + defaultCat + '"]');
            if (defaultOpt) {
                txCat.value = defaultCat;
            } else if (first) {
                txCat.value = first.value;
            }
        }
        txType.addEventListener('change', filterCats);
        filterCats();
    }

    // ── Registrar transação com SweetAlert2 ──
    document.getElementById('formAddTx')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const form = this;
        Swal.fire({
            title: 'Registrar transação?',
            text: 'Deseja salvar esta transação financeira?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#27ae60',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-check me-1"></i> Registrar',
            cancelButtonText: 'Cancelar'
        }).then(result => {
            if (result.isConfirmed) form.submit();
        });
    });

    // ── Excluir transação com SweetAlert2 ──
    document.querySelectorAll('.btn-delete-tx').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const form = this.closest('form');
            Swal.fire({
                title: 'Excluir transação?',
                text: 'Essa ação não pode ser desfeita.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74c3c',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-trash me-1"></i> Excluir',
                cancelButtonText: 'Manter'
            }).then(result => {
                if (result.isConfirmed) form.submit();
            });
        });
    });

    // ── Importar OFX via AJAX ──
    document.getElementById('formImportOfx')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const form = this;
        const btn = document.getElementById('btnImportOfx');
        const fileInput = form.querySelector('input[name="ofx_file"]');

        if (!fileInput.files.length) {
            Swal.fire({ icon: 'warning', title: 'Selecione um arquivo OFX.' });
            return;
        }

        const formData = new FormData(form);
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Importando...';

        fetch('?page=financial&action=importOfx', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-upload me-1"></i> Importar';
            if (data.success) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalImportOfx'));
                if (modal) modal.hide();
                Swal.fire({
                    icon: 'success',
                    title: 'Importação concluída!',
                    html: data.message,
                    confirmButtonColor: '#17a2b8'
                }).then(() => { window.location.reload(); });
            } else {
                Swal.fire({ icon: 'error', title: 'Erro na importação', text: data.message });
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-upload me-1"></i> Importar';
            Swal.fire({ icon: 'error', title: 'Erro', text: 'Falha ao importar o arquivo.' });
        });
    });

});
</script>
