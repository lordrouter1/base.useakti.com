<?php
/**
 * Custos de Produção — Resultado do cálculo
 * Variáveis: $cost, $config
 */
?>
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div><h1 class="h2 mb-1"><i class="fas fa-calculator me-2 text-primary"></i>Custo do Pedido #<?= (int) ($cost['order_id'] ?? 0) ?></h1></div>
        <a href="?page=production_costs" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body py-3">
                    <i class="fas fa-boxes fa-2x text-info mb-2"></i>
                    <h4 class="mb-0">R$ <?= number_format((float) ($cost['material_cost'] ?? 0), 2, ',', '.') ?></h4>
                    <small class="text-muted">Material</small>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body py-3">
                    <i class="fas fa-hard-hat fa-2x text-warning mb-2"></i>
                    <h4 class="mb-0">R$ <?= number_format((float) ($cost['labor_cost'] ?? 0), 2, ',', '.') ?></h4>
                    <small class="text-muted">Mão-de-Obra</small>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body py-3">
                    <i class="fas fa-building fa-2x text-secondary mb-2"></i>
                    <h4 class="mb-0">R$ <?= number_format((float) ($cost['overhead_cost'] ?? 0), 2, ',', '.') ?></h4>
                    <small class="text-muted">Overhead</small>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm text-center bg-primary text-white">
                <div class="card-body py-3">
                    <i class="fas fa-sigma fa-2x mb-2"></i>
                    <h4 class="mb-0">R$ <?= number_format((float) ($cost['total_cost'] ?? 0), 2, ',', '.') ?></h4>
                    <small>Custo Total</small>
                </div>
            </div>
        </div>
    </div>
</div>
