<?php
/**
 * Partial: Seção Visão Geral (Entradas/Saídas).
 *
 * Variáveis esperadas:
 *   $activeSection — seção ativa
 *   $categories    — categorias agrupadas por tipo
 */
$mn = $mn ?? ['','Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
?>
<div class="fin-section <?= $activeSection === 'transactions' ? 'active' : '' ?>" id="fin-transactions">

    <div class="d-flex align-items-center justify-content-between mb-3">
        <div class="d-flex align-items-center">
            <div class="rounded-circle d-flex align-items-center justify-content-center me-2" style="width:34px;height:34px;background:rgba(39,174,96,.1);">
                <i class="fas fa-exchange-alt" style="color:#27ae60;font-size:.85rem;"></i>
            </div>
            <div>
                <h5 class="mb-0" style="font-size:1rem;">Visão Geral — Entradas e Saídas</h5>
                <p class="text-muted mb-0" style="font-size:.72rem;">Todas as movimentações financeiras do sistema.</p>
            </div>
        </div>
        <button class="btn btn-sm btn-outline-success" id="btnExportTransactions" title="Exportar transações em CSV">
            <i class="fas fa-file-csv me-1"></i>Exportar CSV
        </button>
    </div>

    <!-- Cards de Resumo (via AJAX) -->
    <div class="row g-3 mb-4" id="txSummaryCards">
        <div class="col-xl-4 col-md-4">
            <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:44px;height:44px;background:rgba(39,174,96,0.15);">
                        <i class="fas fa-arrow-down fa-lg text-success"></i>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase" style="font-size:.65rem;">Entradas</div>
                        <div class="fw-bold fs-5 text-success" id="cardTxEntradas">R$ —</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-4">
            <div class="card border-0 shadow-sm h-100 border-start border-danger border-4">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:44px;height:44px;background:rgba(192,57,43,0.15);">
                        <i class="fas fa-arrow-up fa-lg text-danger"></i>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase" style="font-size:.65rem;">Saídas</div>
                        <div class="fw-bold fs-5 text-danger" id="cardTxSaidas">R$ —</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-4">
            <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:44px;height:44px;background:rgba(52,152,219,0.15);">
                        <i class="fas fa-balance-scale fa-lg text-primary"></i>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase" style="font-size:.65rem;">Saldo</div>
                        <div class="fw-bold fs-5" id="cardTxSaldo">R$ —</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros Dinâmicos -->
    <div class="row g-2 mb-3 align-items-end">
        <div class="col-auto">
            <label class="form-label small fw-bold mb-1">Tipo</label>
            <select id="fTxType" class="form-select form-select-sm" style="width:140px">
                <option value="">Todos</option>
                <option value="entrada">Entradas</option>
                <option value="saida">Saídas</option>
                <option value="registro">Registros</option>
            </select>
        </div>
        <div class="col-auto">
            <label class="form-label small fw-bold mb-1">Categoria</label>
            <select id="fTxCategory" class="form-select form-select-sm" style="width:180px">
                <option value="">Todas</option>
                <optgroup label="Entradas">
                    <?php foreach ($categories['entrada'] ?? [] as $k => $v): ?>
                    <option value="<?= $k ?>"><?= $v ?></option>
                    <?php endforeach; ?>
                </optgroup>
                <optgroup label="Saídas">
                    <?php foreach ($categories['saida'] ?? [] as $k => $v): ?>
                    <option value="<?= $k ?>"><?= $v ?></option>
                    <?php endforeach; ?>
                </optgroup>
            </select>
        </div>
        <div class="col-auto">
            <label class="form-label small fw-bold mb-1">Mês</label>
            <select id="fTxMonth" class="form-select form-select-sm" style="width:120px">
                <option value="">Todos</option>
                <?php for($m=1;$m<=12;$m++): ?>
                <option value="<?= $m ?>"><?= $mn[$m] ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-auto">
            <label class="form-label small fw-bold mb-1">Ano</label>
            <select id="fTxYear" class="form-select form-select-sm" style="width:100px">
                <option value="">Todos</option>
                <?php for($y=date('Y')-2;$y<=date('Y')+1;$y++): ?>
                <option value="<?= $y ?>"><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                <input type="text" class="form-control" id="fTxSearch" placeholder="Buscar descrição..." autocomplete="off">
            </div>
        </div>
    </div>

    <!-- Tabela de Transações -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-success"><i class="fas fa-exchange-alt me-2"></i>Transações</h6>
            <div>
                <button class="btn btn-sm btn-outline-success me-1" id="btnExportTransactions" title="Exportar CSV">
                    <i class="fas fa-file-csv me-1"></i>Exportar
                </button>
                <span class="badge bg-secondary" id="txTotalBadge">—</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-3 py-3">Data</th>
                            <th class="py-3">Tipo</th>
                            <th class="py-3">Categoria</th>
                            <th class="py-3">Descrição</th>
                            <th class="py-3">Valor</th>
                            <th class="py-3">Método</th>
                            <th class="py-3 text-end pe-3">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="txTableBody">
                        <tr><td colspan="7" class="text-center text-muted py-5">
                            <i class="fas fa-spinner fa-spin fa-2x mb-2 d-block opacity-50"></i>Carregando...
                        </td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="fin-pagination" id="txPagination"></div>
</div>
