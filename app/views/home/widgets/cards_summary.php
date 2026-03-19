<?php
/**
 * Widget: Cards de Resumo Principal
 * Variáveis esperadas: $totalPedidosAtivos, $pedidosHoje, $atrasados, $concluidosMes
 */
?>
<div class="row g-3 mb-4" id="home-cards-summary">
    <div class="col-xl-3 col-md-6">
        <a href="?page=pipeline" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:50px;height:50px;background:rgba(52,152,219,0.15);">
                        <i class="fas fa-tasks fa-lg text-primary"></i>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase fw-bold">Pedidos Ativos</div>
                        <div class="fw-bold fs-4 text-primary"><?= $totalPedidosAtivos ?></div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-xl-3 col-md-6">
        <a href="?page=orders" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 border-start border-info border-4">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:50px;height:50px;background:rgba(23,162,184,0.15);">
                        <i class="fas fa-calendar-day fa-lg text-info"></i>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase fw-bold">Criados Hoje</div>
                        <div class="fw-bold fs-4 text-info"><?= $pedidosHoje ?></div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-xl-3 col-md-6">
        <a href="?page=pipeline" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 border-start border-4 <?= $atrasados > 0 ? 'border-danger' : 'border-success' ?>">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:50px;height:50px;background:<?= $atrasados > 0 ? 'rgba(192,57,43,0.15)' : 'rgba(39,174,96,0.15)' ?>;">
                        <i class="fas fa-exclamation-triangle fa-lg <?= $atrasados > 0 ? 'text-danger' : 'text-success' ?>"></i>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase fw-bold">Atrasados</div>
                        <div class="fw-bold fs-4 <?= $atrasados > 0 ? 'text-danger' : 'text-success' ?>"><?= $atrasados ?></div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-xl-3 col-md-6">
        <a href="?page=orders" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:50px;height:50px;background:rgba(39,174,96,0.15);">
                        <i class="fas fa-check-double fa-lg text-success"></i>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase fw-bold">Concluídos no Mês</div>
                        <div class="fw-bold fs-4 text-success"><?= $concluidosMes ?></div>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>
