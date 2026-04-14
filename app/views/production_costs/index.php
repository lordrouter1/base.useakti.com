<?php
/**
 * Custos de Produção — Configuração
 * Variáveis: $config
 */
?>

<?php if (!empty($_SESSION['flash_success'])): ?>
<script nonce="<?= cspNonce() ?>">document.addEventListener('DOMContentLoaded',()=>{if(typeof AktiToast!=='undefined')AktiToast.success('<?= eJs($_SESSION['flash_success']) ?>');});</script>
<?php unset($_SESSION['flash_success']); endif; ?>

<div class="container-fluid py-3">
    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div>
            <h1 class="h2 mb-1"><i class="fas fa-calculator me-2 text-primary"></i>Custos de Produção</h1>
            <p class="text-muted mb-0" style="font-size:.82rem;">Configuração e cálculo de custos por pedido.</p>
        </div>
        <div class="btn-toolbar gap-2">
            <a href="?page=production_costs&action=marginReport" class="btn btn-sm btn-outline-info"><i class="fas fa-chart-bar me-1"></i>Relatório de Margem</a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Config -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white"><strong>Configuração de Custos</strong></div>
                <div class="card-body">
                    <form method="post" action="?page=production_costs&action=saveConfig">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label">Custo Mão-de-Obra (R$/hora)</label>
                            <input type="number" name="labor_cost_hour" class="form-control" step="0.01" value="<?= eAttr($config['labor_cost_hour'] ?? '0.00') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tipo de Overhead</label>
                            <select name="overhead_type" class="form-select">
                                <option value="percentage" <?= ($config['overhead_type'] ?? '') === 'percentage' ? 'selected' : '' ?>>Percentual (%)</option>
                                <option value="fixed" <?= ($config['overhead_type'] ?? '') === 'fixed' ? 'selected' : '' ?>>Valor Fixo (R$)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Valor do Overhead</label>
                            <input type="number" name="overhead_value" class="form-control" step="0.01" value="<?= eAttr($config['overhead_value'] ?? '0.00') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Margem de Lucro Desejada (%)</label>
                            <input type="number" name="profit_margin_pct" class="form-control" step="0.01" value="<?= eAttr($config['profit_margin_pct'] ?? '0.00') ?>">
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Salvar</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Calcular por pedido -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white"><strong>Calcular Custo de Pedido</strong></div>
                <div class="card-body">
                    <form method="get" action="?">
                        <input type="hidden" name="page" value="production_costs">
                        <input type="hidden" name="action" value="calculate">
                        <div class="mb-3">
                            <label class="form-label">ID do Pedido</label>
                            <input type="number" name="order_id" class="form-control" required placeholder="Ex: 1234">
                        </div>
                        <button type="submit" class="btn btn-success"><i class="fas fa-calculator me-1"></i>Calcular</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
