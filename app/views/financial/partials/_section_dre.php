<?php
/**
 * Partial: Seção DRE Simplificado (Demonstrativo de Resultado do Exercício).
 *
 * Variáveis esperadas:
 *   $activeSection — seção ativa
 */
?>
<div class="fin-section <?= $activeSection === 'reports' ? 'active' : '' ?>" id="fin-reports">

    <div class="d-flex align-items-center justify-content-between mb-3">
        <div class="d-flex align-items-center">
            <div class="rounded-circle d-flex align-items-center justify-content-center me-2" style="width:34px;height:34px;background:rgba(230,126,34,.1);">
                <i class="fas fa-chart-pie" style="color:#e67e22;font-size:.85rem;"></i>
            </div>
            <div>
                <h5 class="mb-0" style="font-size:1rem;">DRE — Demonstrativo de Resultado</h5>
                <p class="text-muted mb-0" style="font-size:.72rem;">Visão simplificada de receitas, despesas e resultado líquido por período.</p>
            </div>
        </div>
        <button class="btn btn-sm btn-outline-warning" id="btnExportDre" title="Exportar DRE em CSV">
            <i class="fas fa-file-csv me-1"></i>Exportar
        </button>
    </div>

    <!-- Filtro de período -->
    <div class="row g-2 mb-4 align-items-end">
        <div class="col-auto">
            <label class="form-label small fw-bold mb-1">De</label>
            <input type="month" id="dreFrom" class="form-control form-control-sm" value="<?= date('Y') ?>-01" style="width:160px">
        </div>
        <div class="col-auto">
            <label class="form-label small fw-bold mb-1">Até</label>
            <input type="month" id="dreTo" class="form-control form-control-sm" value="<?= date('Y-m') ?>" style="width:160px">
        </div>
        <div class="col-auto">
            <button class="btn btn-sm btn-primary" id="btnLoadDre">
                <i class="fas fa-sync-alt me-1"></i>Gerar DRE
            </button>
        </div>
    </div>

    <!-- Container do DRE -->
    <div id="dreContainer">
        <div class="text-center text-muted py-5">
            <i class="fas fa-chart-pie fa-3x mb-3 opacity-25"></i>
            <p>Selecione o período e clique em <strong>Gerar DRE</strong> para visualizar o demonstrativo.</p>
        </div>
    </div>

</div>
