<?php
/**
 * Fiscal — Transações (Entradas e Saídas)
 * Variáveis: $transactions, $categories, $totalEntradas, $totalSaidas
 */
$filterType     = $_GET['type'] ?? '';
$filterMonth    = $_GET['filter_month'] ?? '';
$filterYear     = $_GET['filter_year'] ?? '';
$filterCategory = $_GET['category'] ?? '';
?>

<?php if (!empty($_SESSION['flash_success'])): ?>
<script>document.addEventListener('DOMContentLoaded', () => Swal.fire({ icon:'success', title:'Sucesso!', text:'<?= addslashes($_SESSION['flash_success']) ?>', timer:2500, showConfirmButton:false }));</script>
<?php unset($_SESSION['flash_success']); endif; ?>
<?php if (!empty($_SESSION['flash_error'])): ?>
<script>document.addEventListener('DOMContentLoaded', () => Swal.fire({ icon:'error', title:'Erro', text:'<?= addslashes($_SESSION['flash_error']) ?>' }));</script>
<?php unset($_SESSION['flash_error']); endif; ?>

<?php
$allCategories = array_merge($categories['entrada'] ?? [], $categories['saida'] ?? []);

$saldo = $totalEntradas - $totalSaidas;
?>

<!-- ══════ Header ══════ -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-exchange-alt me-2 text-success"></i>Entradas e Saídas</h1>
    <div class="btn-toolbar mb-2 mb-md-0 gap-2">
        <a href="?page=financial" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Dashboard Financeiro
        </a>
        <a href="?page=financial&action=payments" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-file-invoice-dollar me-1"></i> Pagamentos
        </a>
        <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addTransactionModal" data-type="entrada">
            <i class="fas fa-plus-circle me-1"></i> Nova Entrada
        </button>
        <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#addTransactionModal" data-type="saida">
            <i class="fas fa-minus-circle me-1"></i> Nova Saída
        </button>
    </div>
</div>

<!-- ══════ Cards Resumo ══════ -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
            <div class="card-body py-3 px-3 d-flex align-items-center">
                <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:45px;height:45px;background:rgba(39,174,96,0.15);">
                    <i class="fas fa-arrow-down fa-lg text-success"></i>
                </div>
                <div>
                    <div class="text-muted small fw-bold text-uppercase">Total Entradas</div>
                    <div class="h5 mb-0 text-success">R$ <?= number_format($totalEntradas, 2, ',', '.') ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100 border-start border-danger border-4">
            <div class="card-body py-3 px-3 d-flex align-items-center">
                <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:45px;height:45px;background:rgba(231,76,60,0.15);">
                    <i class="fas fa-arrow-up fa-lg text-danger"></i>
                </div>
                <div>
                    <div class="text-muted small fw-bold text-uppercase">Total Saídas</div>
                    <div class="h5 mb-0 text-danger">R$ <?= number_format($totalSaidas, 2, ',', '.') ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100 border-start border-4" style="border-color: <?= $saldo >= 0 ? '#27ae60' : '#e74c3c' ?> !important;">
            <div class="card-body py-3 px-3 d-flex align-items-center">
                <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:45px;height:45px;background:<?= $saldo >= 0 ? 'rgba(39,174,96,0.15)' : 'rgba(231,76,60,0.15)' ?>;">
                    <i class="fas fa-balance-scale fa-lg" style="color:<?= $saldo >= 0 ? '#27ae60' : '#e74c3c' ?>"></i>
                </div>
                <div>
                    <div class="text-muted small fw-bold text-uppercase">Saldo</div>
                    <div class="h5 mb-0" style="color:<?= $saldo >= 0 ? '#27ae60' : '#e74c3c' ?>">
                        R$ <?= number_format($saldo, 2, ',', '.') ?>
                    </div>
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
        </select>
    </div>
    <div class="col-auto">
        <label class="form-label small fw-bold mb-1">Categoria</label>
        <select name="category" class="form-select form-select-sm" style="width:180px">
            <option value="">Todas</option>
            <optgroup label="Entradas">
                <?php foreach($categories['entrada'] as $k => $v): ?>
                <option value="<?= $k ?>" <?= $filterCategory===$k?'selected':'' ?>><?= $v ?></option>
                <?php endforeach; ?>
            </optgroup>
            <optgroup label="Saídas">
                <?php foreach($categories['saida'] as $k => $v): ?>
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
        <input type="text" class="form-control" id="searchTransactions" placeholder="Buscar por descrição, categoria, valor..." autocomplete="off">
    </div>
</div>

<!-- ══════ Tabela de Transações ══════ -->
<div class="table-responsive bg-white rounded shadow-sm">
    <table class="table table-hover align-middle mb-0" id="transactionsTable">
        <thead class="bg-light">
            <tr>
                <th class="py-3 ps-3" style="width:50px">Tipo</th>
                <th class="py-3">Data</th>
                <th class="py-3">Categoria</th>
                <th class="py-3">Descrição</th>
                <th class="py-3">Valor</th>
                <th class="py-3">Método</th>
                <th class="py-3">Confirmado</th>
                <th class="py-3">Registrado por</th>
                <th class="py-3 text-end pe-3">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($transactions)): ?>
            <tr><td colspan="9" class="text-center text-muted py-4"><i class="fas fa-inbox fa-2x mb-2 d-block"></i>Nenhuma transação encontrada.</td></tr>
            <?php else: ?>
            <?php foreach($transactions as $t): 
                $isEntrada = $t['type'] === 'entrada';
                $catLabel = $allCategories[$t['category']] ?? ucfirst(str_replace('_', ' ', $t['category'] ?? ''));
            ?>
            <tr>
                <td class="ps-3">
                    <?php if($isEntrada): ?>
                        <span class="badge bg-success"><i class="fas fa-arrow-down"></i></span>
                    <?php else: ?>
                        <span class="badge bg-danger"><i class="fas fa-arrow-up"></i></span>
                    <?php endif; ?>
                </td>
                <td class="small"><?= date('d/m/Y', strtotime($t['transaction_date'])) ?></td>
                <td>
                    <span class="badge <?= $isEntrada ? 'bg-success bg-opacity-25 text-success' : 'bg-danger bg-opacity-25 text-danger' ?> small">
                        <?= htmlspecialchars($catLabel) ?>
                    </span>
                </td>
                <td><?= htmlspecialchars($t['description'] ?? '') ?></td>
                <td class="fw-bold <?= $isEntrada ? 'text-success' : 'text-danger' ?>">
                    <?= $isEntrada ? '+' : '-' ?> R$ <?= number_format($t['amount'], 2, ',', '.') ?>
                </td>
                <td class="small"><?= ucfirst(str_replace('_', ' ', $t['payment_method'] ?? '—')) ?></td>
                <td>
                    <?php if ($t['is_confirmed']): ?>
                        <span class="badge bg-success"><i class="fas fa-check"></i></span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark"><i class="fas fa-hourglass-half"></i></span>
                    <?php endif; ?>
                </td>
                <td class="small text-muted"><?= htmlspecialchars($t['user_name'] ?? 'Sistema') ?></td>
                <td class="text-end pe-3">
                    <?php if (empty($t['reference_type'])): ?>
                    <form method="post" action="?page=financial&action=deleteTransaction" class="d-inline"
                          onsubmit="return confirm('Remover esta transação?')">
                        <input type="hidden" name="transaction_id" value="<?= $t['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Remover">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                    <?php else: ?>
                        <span class="text-muted small" title="Transação vinculada a pagamento"><i class="fas fa-link"></i></span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- ══════ Modal: Adicionar Transação ══════ -->
<div class="modal fade" id="addTransactionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="?page=financial&action=addTransaction">
                <div class="modal-header">
                    <h5 class="modal-title" id="transModalTitle">
                        <i class="fas fa-plus-circle me-2"></i>Nova Transação
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Tipo</label>
                            <select name="type" id="transType" class="form-select" required>
                                <option value="entrada">Entrada</option>
                                <option value="saida">Saída</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Categoria</label>
                            <select name="category" id="transCategory" class="form-select" required>
                                <!-- Preenchido via JS -->
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Descrição</label>
                            <input type="text" name="description" class="form-control" required placeholder="Descrição da transação">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Valor (R$)</label>
                            <input type="number" name="amount" class="form-control" step="0.01" min="0.01" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Data</label>
                            <input type="date" name="transaction_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Método Pagamento</label>
                            <select name="payment_method" class="form-select">
                                <option value="">Nenhum</option>
                                <option value="dinheiro">Dinheiro</option>
                                <option value="pix">PIX</option>
                                <option value="boleto">Boleto</option>
                                <option value="cartao_credito">Cartão de Crédito</option>
                                <option value="cartao_debito">Cartão de Débito</option>
                                <option value="transferencia">Transferência</option>
                                <option value="cheque">Cheque</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Observações</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Notas adicionais..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-check me-1"></i> Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const categories = <?= json_encode($categories) ?>;

    // Atualizar categorias quando tipo muda
    function updateCategories(type) {
        const sel = document.getElementById('transCategory');
        if (!sel) return;
        sel.innerHTML = '';
        const cats = categories[type] || {};
        for (const [k, v] of Object.entries(cats)) {
            const opt = document.createElement('option');
            opt.value = k;
            opt.textContent = v;
            sel.appendChild(opt);
        }
    }

    const typeSelect = document.getElementById('transType');
    if (typeSelect) {
        typeSelect.addEventListener('change', function() {
            updateCategories(this.value);
        });
    }

    // Modal show
    const modal = document.getElementById('addTransactionModal');
    if (modal) {
        modal.addEventListener('show.bs.modal', function(e) {
            const type = e.relatedTarget?.getAttribute('data-type') || 'entrada';
            const typeSelect = document.getElementById('transType');
            if (typeSelect) typeSelect.value = type;
            updateCategories(type);

            const title = document.getElementById('transModalTitle');
            if (type === 'entrada') {
                title.innerHTML = '<i class="fas fa-arrow-down me-2 text-success"></i>Nova Entrada';
            } else {
                title.innerHTML = '<i class="fas fa-arrow-up me-2 text-danger"></i>Nova Saída';
            }
        });
    }

    // Busca na tabela
    const searchInput = document.getElementById('searchTransactions');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const term = this.value.toLowerCase();
            document.querySelectorAll('#transactionsTable tbody tr').forEach(tr => {
                tr.style.display = tr.textContent.toLowerCase().includes(term) ? '' : 'none';
            });
        });
    }
});
</script>
