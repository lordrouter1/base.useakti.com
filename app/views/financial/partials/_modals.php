<?php
/**
 * Partial: Modais compartilhados do módulo financeiro.
 *
 * Variáveis esperadas:
 *   $categories — categorias agrupadas por tipo
 */
?>
<!-- ══════ Modal Registrar Pagamento ══════ -->
<div class="modal fade" id="modalPay" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="post" action="?page=financial&action=payInstallment" id="formPay" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="installment_id" id="payInstId">
                <input type="hidden" name="order_id" id="payOrderId">
                <div class="modal-header bg-success border-0">
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
                                <option value="gateway">🌐 Gateway Online</option>
                            </select>
                            <small class="text-muted" id="payMethodHint"></small>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold"><i class="fas fa-paperclip me-1"></i>Comprovante <span class="text-muted fw-normal">(opcional)</span></label>
                            <input type="file" name="attachment" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf">
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

<!-- ══════ Modal Editar Transação ══════ -->
<div class="modal fade" id="modalEditTx" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Editar Transação</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editTxId">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Tipo</label>
                        <select id="editTxType" class="form-select" required>
                            <option value="entrada">✅ Entrada</option>
                            <option value="saida">🔴 Saída</option>
                            <option value="registro">📋 Registro</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Categoria</label>
                        <select id="editTxCategory" class="form-select" required>
                            <optgroup label="Entradas" id="editCatEntrada">
                                <?php foreach ($categories['entrada'] ?? [] as $k => $v): ?>
                                <option value="<?= $k ?>" data-type="entrada"><?= $v ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="Saídas" id="editCatSaida">
                                <?php foreach ($categories['saida'] ?? [] as $k => $v): ?>
                                <option value="<?= $k ?>" data-type="saida"><?= $v ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="Sistema">
                                <option value="registro_ofx" data-type="registro">Registro OFX/Importação</option>
                                <option value="estorno_pagamento" data-type="registro">Estorno de Pagamento</option>
                            </optgroup>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">Descrição</label>
                        <input type="text" id="editTxDescription" class="form-control" placeholder="Descrição da transação" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Valor (R$)</label>
                        <input type="number" step="0.01" min="0.01" id="editTxAmount" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Data</label>
                        <input type="date" id="editTxDate" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Forma de Pagamento</label>
                        <select id="editTxMethod" class="form-select">
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
                        <input type="text" id="editTxNotes" class="form-control" placeholder="Nota adicional">
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 d-flex justify-content-between">
                <button type="button" class="btn btn-outline-danger" id="btnEditTxDelete">
                    <i class="fas fa-trash me-1"></i>Excluir
                </button>
                <div>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btnEditTxSave">
                        <i class="fas fa-save me-1"></i>Salvar Alterações
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══════ Modal Cobrar via Gateway ══════ -->
<div class="modal fade" id="modalGatewayCharge" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0" style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);">
                <h5 class="modal-title text-white"><i class="fas fa-bolt me-2"></i>Cobrar via Gateway</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="gwChargeInstId">
                <input type="hidden" id="gwChargeOrderId">

                <div class="alert alert-info py-2 mb-3">
                    <i class="fas fa-info-circle me-1"></i>
                    Pedido <strong id="gwChargeOrderDisplay">—</strong> ·
                    Parcela <strong id="gwChargeNumber">—</strong> ·
                    Valor: <strong id="gwChargeAmountDisplay">—</strong>
                    <br><small class="text-muted" id="gwChargeCustomerDisplay"></small>
                </div>

                <?php if (!empty($activeGateways)): ?>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label small fw-bold">Gateway</label>
                        <select id="gwChargeSlug" class="form-select">
                            <?php foreach ($activeGateways as $gw): ?>
                            <option value="<?= htmlspecialchars($gw['gateway_slug']) ?>"
                                    data-methods="<?= htmlspecialchars(json_encode(json_decode($gw['settings_json'] ?? '{}', true)['supported_methods'] ?? ['pix'])) ?>">
                                <?= htmlspecialchars($gw['display_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">Método de Pagamento</label>
                        <select id="gwChargeMethod" class="form-select">
                            <option value="pix">📱 PIX</option>
                            <option value="credit_card">💳 Cartão de Crédito</option>
                            <option value="boleto">📄 Boleto</option>
                        </select>
                    </div>
                </div>
                <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-exclamation-triangle fa-2x text-warning mb-3"></i>
                    <p class="text-muted">Nenhum gateway de pagamento ativo.</p>
                    <a href="?page=payment_gateways" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-cog me-1"></i>Configurar Gateways
                    </a>
                </div>
                <?php endif; ?>

                <!-- Resultado da cobrança -->
                <div id="gwChargeResult" class="mt-3" style="display:none;"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Fechar</button>
                <?php if (!empty($activeGateways)): ?>
                <button type="button" class="btn text-white" id="btnCreateGwCharge" style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);">
                    <i class="fas fa-bolt me-1"></i>Gerar Cobrança
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
