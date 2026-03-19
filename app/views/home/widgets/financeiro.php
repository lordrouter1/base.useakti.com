<?php
/**
 * Widget: Resumo Financeiro
 * Variáveis esperadas: $recebidoMes, $aReceberTotal, $atrasadosFin, $pendentesConfirmacao
 */
?>
<div class="col-xl-6" id="home-financeiro">
    <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-success"><i class="fas fa-coins me-2"></i>Financeiro — <?php
                if (class_exists('IntlDateFormatter')) {
                    $fmt = new IntlDateFormatter('pt_BR', IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, 'MMMM/yyyy');
                    echo ucfirst($fmt->format(new DateTime()));
                } else {
                    setlocale(LC_TIME, 'pt_BR.UTF-8', 'pt_BR', 'portuguese');
                    echo strftime('%B/%Y');
                }
            ?></h6>
            <a href="?page=financial_payments" class="btn btn-sm btn-outline-success">Pagamentos <i class="fas fa-arrow-right ms-1"></i></a>
        </div>
        <div class="card-body p-3">
            <div class="row g-3">
                <div class="col-6">
                    <div class="bg-light rounded p-3 text-center h-100">
                        <div class="text-muted small text-uppercase fw-bold">Recebido</div>
                        <div class="fw-bold fs-5 text-success">R$ <?= number_format($recebidoMes, 2, ',', '.') ?></div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="bg-light rounded p-3 text-center h-100">
                        <div class="text-muted small text-uppercase fw-bold">A Receber</div>
                        <div class="fw-bold fs-5 text-warning">R$ <?= number_format($aReceberTotal, 2, ',', '.') ?></div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="bg-light rounded p-3 text-center h-100">
                        <div class="text-muted small text-uppercase fw-bold">Em Atraso</div>
                        <div class="fw-bold fs-5 <?= $atrasadosFin > 0 ? 'text-danger' : 'text-muted' ?>">R$ <?= number_format($atrasadosFin, 2, ',', '.') ?></div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="bg-light rounded p-3 text-center h-100">
                        <div class="text-muted small text-uppercase fw-bold">Aguardando Confirm.</div>
                        <div class="fw-bold fs-5 <?= $pendentesConfirmacao > 0 ? 'text-info' : 'text-muted' ?>"><?= $pendentesConfirmacao ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
