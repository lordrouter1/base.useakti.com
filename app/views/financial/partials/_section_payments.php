<?php
/**
 * Partial: Seção Pagamentos (Parcelas).
 *
 * Variáveis esperadas:
 *   $activeSection — seção ativa
 */
?>
<div class="fin-section <?= $activeSection === 'payments' ? 'active' : '' ?>" id="fin-payments">

    <div class="d-flex align-items-center mb-3">
        <div class="icon-circle icon-circle-blue me-2">
            <i class="fas fa-file-invoice-dollar text-blue" style="font-size:.85rem;"></i>
        </div>
        <div>
            <h5 class="mb-0" style="font-size:1rem;">Pagamentos</h5>
            <p class="text-muted mb-0" style="font-size:.72rem;">Parcelas de pedidos nas etapas Financeiro e Concluído.</p>
        </div>
    </div>

    <!-- Cards de Resumo (preenchidos via AJAX) -->
    <div class="row g-3 mb-4" id="paymentsSummaryCards">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="icon-circle icon-circle-lg icon-circle-primary me-3">
                        <i class="fas fa-list-ol text-primary"></i>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase fw-bold" style="font-size:.65rem;">Total</div>
                        <div class="fw-bold fs-5" id="cardPayTotal">—</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 border-start border-warning border-4">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="icon-circle icon-circle-lg icon-circle-warning me-3">
                        <i class="fas fa-clock text-warning"></i>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase fw-bold" style="font-size:.65rem;">Pendentes</div>
                        <div class="fw-bold fs-5" id="cardPayPending">—</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="icon-circle icon-circle-lg icon-circle-success me-3">
                        <i class="fas fa-check-circle text-success"></i>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase fw-bold" style="font-size:.65rem;">Pagas</div>
                        <div class="fw-bold fs-5" id="cardPayPaid">—</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 border-start border-info border-4">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="icon-circle icon-circle-lg icon-circle-info me-3">
                        <i class="fas fa-user-clock text-info"></i>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase fw-bold" style="font-size:.65rem;">Aguardando</div>
                        <div class="fw-bold fs-5" id="cardPayAwaiting">—</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros Dinâmicos -->
    <div class="row g-2 mb-3 align-items-end">
        <div class="col-auto">
            <label class="form-label small fw-bold mb-1">Status</label>
            <select id="fPayStatus" class="form-select form-select-sm" style="width:170px">
                <option value="">Todos</option>
                <option value="pendente">Pendentes/Atrasadas</option>
                <option value="pago">Pagas</option>
                <option value="atrasado">Atrasadas</option>
                <option value="aguardando">Aguardando Confirm.</option>
            </select>
        </div>
        <div class="col-auto">
            <label class="form-label small fw-bold mb-1">Mês</label>
            <select id="fPayMonth" class="form-select form-select-sm" style="width:120px">
                <option value="">Todos</option>
                <?php $mn=['','Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez']; for($m=1;$m<=12;$m++): ?>
                <option value="<?= $m ?>"><?= $mn[$m] ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-auto">
            <label class="form-label small fw-bold mb-1">Ano</label>
            <select id="fPayYear" class="form-select form-select-sm" style="width:100px">
                <option value="">Todos</option>
                <?php for($y=date('Y')-2;$y<=date('Y')+1;$y++): ?>
                <option value="<?= $y ?>"><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                <input type="text" class="form-control" id="fPaySearch" placeholder="Buscar pedido, cliente..." autocomplete="off">
            </div>
        </div>
    </div>

    <!-- Tabela de Parcelas -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-list me-2"></i>Parcelas</h6>
            <span class="badge bg-secondary" id="payTotalBadge">—</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="py-3 ps-3">Pedido</th>
                            <th class="py-3">Cliente</th>
                            <th class="py-3">Parcela</th>
                            <th class="py-3">Vencimento</th>
                            <th class="py-3">Valor</th>
                            <th class="py-3">Pago em</th>
                            <th class="py-3">Valor Pago</th>
                            <th class="py-3">Status</th>
                            <th class="py-3">NF-e</th>
                            <th class="py-3 text-end pe-3">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="paymentsTableBody">
                        <tr><td colspan="10" class="text-center text-muted py-5">
                            <i class="fas fa-spinner fa-spin fa-2x mb-2 d-block opacity-50"></i>Carregando...
                        </td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="fin-pagination" id="paymentsPagination"></div>
</div>
