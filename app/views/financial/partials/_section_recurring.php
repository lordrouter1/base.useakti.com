<?php
/**
 * Partial: Seção Recorrências (Despesas/Receitas fixas).
 *
 * Variáveis esperadas:
 *   $activeSection — seção ativa
 *   $categories    — categorias agrupadas por tipo
 */
?>
<div class="fin-section <?= $activeSection === 'recurring' ? 'active' : '' ?>" id="fin-recurring">

    <div class="d-flex align-items-center justify-content-between mb-3">
        <div class="d-flex align-items-center">
            <div class="rounded-circle d-flex align-items-center justify-content-center me-2 nav-icon-orange" style="width:34px;height:34px;">
                <i class="fas fa-redo-alt" style="font-size:.85rem;"></i>
            </div>
            <div>
                <h5 class="mb-0" style="font-size:1rem;">Transações Recorrentes</h5>
                <p class="text-muted mb-0" style="font-size:.72rem;">Gerencie receitas e despesas fixas que se repetem mensalmente.</p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-info" id="btnProcessRecurring" title="Gerar transações pendentes do mês atual">
                <i class="fas fa-cogs me-1"></i>Processar Mês
            </button>
            <button class="btn btn-sm btn-warning" id="btnNewRecurring">
                <i class="fas fa-plus me-1"></i>Nova Recorrência
            </button>
        </div>
    </div>

    <!-- Lista de recorrências -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold legend-orange"><i class="fas fa-redo-alt me-2"></i>Recorrências Ativas</h6>
            <span class="badge bg-secondary" id="recurringTotalBadge">—</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-3 py-3">Descrição</th>
                            <th class="py-3">Tipo</th>
                            <th class="py-3">Categoria</th>
                            <th class="py-3">Valor</th>
                            <th class="py-3">Dia Vcto</th>
                            <th class="py-3">Próx. Geração</th>
                            <th class="py-3">Status</th>
                            <th class="py-3 text-end pe-3">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="recurringTableBody">
                        <tr><td colspan="8" class="text-center text-muted py-5">
                            <i class="fas fa-spinner fa-spin fa-2x mb-2 d-block opacity-50"></i>Carregando...
                        </td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Resumo mensal de recorrências -->
    <div class="row g-3 mt-3" id="recurringSummary">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
                <div class="card-body p-3 text-center">
                    <div class="text-muted small text-uppercase fw-bold" style="font-size:.65rem;">Receitas Fixas/mês</div>
                    <div class="fw-bold fs-5 text-success" id="recurringRevenue">R$ —</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 border-start border-danger border-4">
                <div class="card-body p-3 text-center">
                    <div class="text-muted small text-uppercase fw-bold" style="font-size:.65rem;">Despesas Fixas/mês</div>
                    <div class="fw-bold fs-5 text-danger" id="recurringExpenses">R$ —</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
                <div class="card-body p-3 text-center">
                    <div class="text-muted small text-uppercase fw-bold" style="font-size:.65rem;">Saldo Recorrente/mês</div>
                    <div class="fw-bold fs-5" id="recurringBalance">R$ —</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══════ Modal Nova/Editar Recorrência ══════ -->
<div class="modal fade" id="modalRecurring" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0 card-header-amber">
                <h5 class="modal-title text-dark"><i class="fas fa-redo-alt me-2"></i><span id="recurringModalTitle">Nova Recorrência</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="recurringId">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Tipo</label>
                        <select id="recurringType" class="form-select" required>
                            <option value="entrada">✅ Receita Fixa</option>
                            <option value="saida">🔴 Despesa Fixa</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Categoria</label>
                        <select id="recurringCategory" class="form-select" required>
                            <optgroup label="Entradas" id="recCatEntrada">
                                <?php foreach ($categories['entrada'] ?? [] as $k => $v): ?>
                                <option value="<?= $k ?>" data-type="entrada"><?= $v ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="Saídas" id="recCatSaida">
                                <?php foreach ($categories['saida'] ?? [] as $k => $v): ?>
                                <option value="<?= $k ?>" data-type="saida"><?= $v ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">Descrição</label>
                        <input type="text" id="recurringDescription" class="form-control" placeholder="Ex: Aluguel do Galpão" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Valor (R$)</label>
                        <input type="number" step="0.01" min="0.01" id="recurringAmount" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Dia de Vencimento</label>
                        <input type="number" min="1" max="28" id="recurringDueDay" class="form-control" value="10" required>
                        <small class="text-muted">Dia do mês (1-28)</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Início</label>
                        <input type="month" id="recurringStartMonth" class="form-control" value="<?= date('Y-m') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Término <span class="text-muted fw-normal">(opcional)</span></label>
                        <input type="month" id="recurringEndMonth" class="form-control">
                        <small class="text-muted">Deixe vazio para recorrência sem fim</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Forma de Pagamento</label>
                        <select id="recurringPaymentMethod" class="form-select">
                            <option value="">— Não informado —</option>
                            <option value="dinheiro">💵 Dinheiro</option>
                            <option value="pix">📱 PIX</option>
                            <option value="boleto">📄 Boleto</option>
                            <option value="transferencia">🏦 Transferência</option>
                            <option value="cartao_credito">💳 Cartão Crédito</option>
                            <option value="cartao_debito">💳 Cartão Débito</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">Observação <span class="text-muted fw-normal">(opcional)</span></label>
                        <input type="text" id="recurringNotes" class="form-control" placeholder="Nota adicional">
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning text-dark" id="btnSaveRecurring">
                    <i class="fas fa-save me-1"></i>Salvar
                </button>
            </div>
        </div>
    </div>
</div>
