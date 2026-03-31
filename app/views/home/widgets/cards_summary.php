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
                    <div class="icon-circle icon-circle-xxl icon-circle-primary me-3">
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
                    <div class="icon-circle icon-circle-xxl icon-circle-info me-3">
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
                    <div class="icon-circle icon-circle-xxl <?= $atrasados > 0 ? 'icon-circle-danger' : 'icon-circle-green' ?> me-3">
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
                    <div class="icon-circle icon-circle-xxl icon-circle-green me-3">
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
