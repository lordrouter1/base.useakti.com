<?php
/**
 * View: Estoque de Insumos — Histórico de Movimentações
 * Variáveis: $warehouses
 */
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="fas fa-history me-2 text-secondary"></i>Movimentações de Insumos</h1>
        <a href="?page=supply_stock" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small mb-1">Armazém</label>
                    <select id="fWarehouse" class="form-select form-select-sm mov-filter">
                        <option value="">Todos</option>
                        <?php foreach ($warehouses as $wh): ?>
                        <option value="<?= eAttr($wh['id']) ?>"><?= e($wh['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Insumo</label>
                    <select id="fSupply" class="form-select form-select-sm select2-supply-filter"></select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Tipo</label>
                    <select id="fType" class="form-select form-select-sm mov-filter">
                        <option value="">Todos</option>
                        <option value="entrada">Entrada</option>
                        <option value="saida">Saída</option>
                        <option value="ajuste">Ajuste</option>
                        <option value="transferencia">Transferência</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">De</label>
                    <input type="date" id="fDateFrom" class="form-control form-control-sm mov-filter">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Até</label>
                    <input type="date" id="fDateTo" class="form-control form-control-sm mov-filter">
                </div>
                <div class="col-md-1 text-end">
                    <button type="button" id="btnClearFilters" class="btn btn-outline-secondary btn-sm w-100" title="Limpar" aria-label="Limpar filtros">
                        <i class="fas fa-times" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <caption class="visually-hidden">Histórico de movimentações de insumos</caption>
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4" style="width: 50px;">#</th>
                            <th>Data</th>
                            <th>Tipo</th>
                            <th>Insumo</th>
                            <th class="text-center">Armazém</th>
                            <th class="text-center">Lote</th>
                            <th class="text-end">Qtd</th>
                            <th class="text-end">Preço Unit.</th>
                            <th>Motivo</th>
                        </tr>
                    </thead>
                    <tbody id="movTableBody">
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                <i class="fas fa-spinner fa-spin me-1"></i>Carregando...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between align-items-center px-4 py-2 border-top">
                <span id="movPaginationInfo" class="text-muted small"></span>
                <ul id="movPagination" class="pagination pagination-sm mb-0"></ul>
            </div>
        </div>
    </div>
</div>

<script src="<?= asset('assets/js/modules/supply-movements.js') ?>"></script>
