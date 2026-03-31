<?php
/**
 * Partial: Seção Fluxo de Caixa Projetado.
 *
 * Variáveis esperadas:
 *   $activeSection — seção ativa
 */
?>
<div class="fin-section <?= $activeSection === 'cashflow' ? 'active' : '' ?>" id="fin-cashflow">

    <div class="d-flex align-items-center justify-content-between mb-3">
        <div class="d-flex align-items-center">
            <div class="icon-circle icon-circle-mint me-2">
                <i class="fas fa-chart-area text-mint" style="font-size:.85rem;"></i>
            </div>
            <div>
                <h5 class="mb-0" style="font-size:1rem;">Fluxo de Caixa Projetado</h5>
                <p class="text-muted mb-0" style="font-size:.72rem;">Projeção de entradas e saídas para os próximos meses (parcelas pendentes + recorrências).</p>
            </div>
        </div>
        <button class="btn btn-sm btn-outline-success" id="btnExportCashflow" title="Exportar Fluxo de Caixa em CSV">
            <i class="fas fa-file-csv me-1"></i>Exportar
        </button>
    </div>

    <!-- Filtros -->
    <div class="row g-2 mb-4 align-items-end">
        <div class="col-auto">
            <label class="form-label small fw-bold mb-1">Horizonte</label>
            <select id="cashflowMonths" class="form-select form-select-sm" style="width:150px">
                <option value="3">3 meses</option>
                <option value="6" selected>6 meses</option>
                <option value="12">12 meses</option>
            </select>
        </div>
        <div class="col-auto">
            <div class="form-check mt-4">
                <input class="form-check-input" type="checkbox" id="cashflowIncludeRecurring" checked>
                <label class="form-check-label small" for="cashflowIncludeRecurring">Incluir recorrências</label>
            </div>
        </div>
        <div class="col-auto">
            <button class="btn btn-sm btn-primary" id="btnLoadCashflow">
                <i class="fas fa-chart-area me-1"></i>Gerar Projeção
            </button>
        </div>
    </div>

    <!-- Gráfico -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <canvas id="cashflowChart" height="120"></canvas>
        </div>
    </div>

    <!-- Tabela mês a mês -->
    <div id="cashflowTableContainer">
        <div class="text-center text-muted py-5">
            <i class="fas fa-chart-area fa-3x mb-3 opacity-25"></i>
            <p>Clique em <strong>Gerar Projeção</strong> para ver o fluxo de caixa projetado.</p>
        </div>
    </div>

</div>
