<?php
/**
 * Partial: Seção Nova Transação.
 *
 * Variáveis esperadas:
 *   $activeSection — seção ativa
 *   $categories    — categorias agrupadas por tipo
 */
?>
<div class="fin-section <?= $activeSection === 'new' ? 'active' : '' ?>" id="fin-new">

    <div class="d-flex align-items-center mb-3">
        <div class="rounded-circle d-flex align-items-center justify-content-center me-2" style="width:34px;height:34px;background:rgba(155,89,182,.1);">
            <i class="fas fa-plus-circle" style="color:#9b59b6;font-size:.85rem;"></i>
        </div>
        <div>
            <h5 class="mb-0" style="font-size:1rem;">Nova Transação</h5>
            <p class="text-muted mb-0" style="font-size:.72rem;">Registre uma entrada ou saída manual.</p>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form id="formNewTransaction" method="post" action="?page=financial&action=addTransaction">
                <?= csrf_field() ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Tipo</label>
                        <select name="type" id="newTxType" class="form-select" required>
                            <option value="entrada">✅ Entrada</option>
                            <option value="saida">🔴 Saída</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Categoria</label>
                        <select name="category" id="newTxCategory" class="form-select" required>
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
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Valor (R$)</label>
                        <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Data</label>
                        <input type="date" name="transaction_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Forma de Pagamento</label>
                        <select name="payment_method" class="form-select">
                            <option value="">— Não informado —</option>
                            <option value="dinheiro">💵 Dinheiro</option>
                            <option value="pix">📱 PIX</option>
                            <option value="cartao_credito">💳 Cartão Crédito</option>
                            <option value="cartao_debito">💳 Cartão Débito</option>
                            <option value="boleto">📄 Boleto</option>
                            <option value="transferencia">🏦 Transferência</option>
                            <option value="gateway">🌐 Gateway Online</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">Observação <span class="text-muted fw-normal">(opcional)</span></label>
                        <input type="text" name="notes" class="form-control" placeholder="Nota adicional">
                    </div>
                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check me-1"></i> Registrar Transação
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
