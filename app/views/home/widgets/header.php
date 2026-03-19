<?php
/**
 * Widget: Saudação + Atalhos Rápidos
 * Variáveis esperadas do escopo pai: nenhuma obrigatória (usa $_SESSION)
 */
?>
<div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom" id="home-header">
    <div>
        <h1 class="h2 mb-0"><i class="fas fa-hand-sparkles me-2 text-warning"></i>Olá, <?= e($_SESSION['user_name'] ?? 'Usuário') ?>!</h1>
        <small class="text-muted"><?php
            if (class_exists('IntlDateFormatter')) {
                $fmt = new IntlDateFormatter('pt_BR', IntlDateFormatter::FULL, IntlDateFormatter::NONE);
                echo ucfirst($fmt->format(new DateTime()));
            } else {
                setlocale(LC_TIME, 'pt_BR.UTF-8', 'pt_BR', 'portuguese');
                echo ucfirst(strftime('%A, %d de %B de %Y'));
            }
        ?></small>
    </div>
    <div class="btn-toolbar gap-2 mt-2 mt-md-0" id="home-shortcuts">
        <a href="?page=orders&action=create" class="btn btn-sm btn-primary">
            <i class="fas fa-plus me-1"></i> Novo Pedido
        </a>
        <a href="?page=customers&action=create" class="btn btn-sm btn-outline-success">
            <i class="fas fa-user-plus me-1"></i> Novo Cliente
        </a>
        <a href="?page=pipeline" class="btn btn-sm btn-outline-warning text-dark">
            <i class="fas fa-stream me-1"></i> Pipeline
        </a>
        <a href="?page=financial_payments" class="btn btn-sm btn-outline-info">
            <i class="fas fa-file-invoice-dollar me-1"></i> Pagamentos
        </a>
    </div>
</div>
