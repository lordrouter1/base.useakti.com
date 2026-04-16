<?php
/**
 * Alertas de Custo — Listagem
 * Variáveis: $alerts, $settings
 */
$csrfToken = csrf_token();
?>

<div class="container-fluid py-3">
    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div>
            <h1 class="h2 mb-1">
                <i class="fas fa-exclamation-triangle me-2 text-warning"></i>Alertas de Custo
            </h1>
            <small class="text-muted">Margem mínima configurada: <?= eAttr($settings['min_margin_threshold'] ?? '15') ?>%</small>
        </div>
        <div>
            <button class="btn btn-sm btn-outline-secondary" id="btnSettings"><i class="fas fa-cog me-1"></i>Configurações</button>
            <a href="?page=supplies" class="btn btn-sm btn-outline-secondary ms-1"><i class="fas fa-arrow-left me-1"></i>Insumos</a>
        </div>
    </div>

    <!-- KPIs -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="h3 text-danger" id="kpiPending"><?= count(array_filter($alerts, fn($a) => $a['status'] === 'pending')) ?></div>
                    <small class="text-muted">Pendentes</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="h3 text-warning" id="kpiAcknowledged"><?= count(array_filter($alerts, fn($a) => $a['status'] === 'acknowledged')) ?></div>
                    <small class="text-muted">Reconhecidos</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="h3 text-success" id="kpiApplied"><?= count(array_filter($alerts, fn($a) => $a['status'] === 'applied')) ?></div>
                    <small class="text-muted">Aplicados</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="h3 text-secondary" id="kpiDismissed"><?= count(array_filter($alerts, fn($a) => $a['status'] === 'dismissed')) ?></div>
                    <small class="text-muted">Dispensados</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela de Alertas -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Produto</th>
                            <th>Insumo</th>
                            <th class="text-end">Custo Ant.</th>
                            <th class="text-end">Custo Novo</th>
                            <th class="text-end">Margem Ant.</th>
                            <th class="text-end">Margem Nova</th>
                            <th class="text-end">Preço Sugerido</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($alerts)): ?>
                        <tr><td colspan="9" class="text-center text-muted py-4">Nenhum alerta encontrado.</td></tr>
                        <?php else: ?>
                        <?php foreach ($alerts as $alert): ?>
                        <tr>
                            <td><?= e($alert['product_name'] ?? '') ?></td>
                            <td><span class="badge bg-light text-dark"><?= e($alert['supply_code'] ?? '') ?></span> <?= e($alert['supply_name'] ?? '') ?></td>
                            <td class="text-end">R$ <?= number_format((float)$alert['old_cost'], 4, ',', '.') ?></td>
                            <td class="text-end">R$ <?= number_format((float)$alert['new_cost'], 4, ',', '.') ?></td>
                            <td class="text-end"><?= number_format((float)$alert['old_margin'], 2, ',', '.') ?>%</td>
                            <td class="text-end"><span class="text-danger fw-bold"><?= number_format((float)$alert['new_margin'], 2, ',', '.') ?>%</span></td>
                            <td class="text-end">R$ <?= $alert['suggested_price'] ? number_format((float)$alert['suggested_price'], 2, ',', '.') : '—' ?></td>
                            <td class="text-center">
                                <?php
                                $statusMap = ['pending' => 'warning', 'acknowledged' => 'info', 'applied' => 'success', 'dismissed' => 'secondary'];
                                $statusLabel = ['pending' => 'Pendente', 'acknowledged' => 'Reconhecido', 'applied' => 'Aplicado', 'dismissed' => 'Dispensado'];
                                $st = $alert['status'];
                                ?>
                                <span class="badge bg-<?= $statusMap[$st] ?? 'secondary' ?>"><?= $statusLabel[$st] ?? $st ?></span>
                            </td>
                            <td class="text-end">
                                <?php if ($st === 'pending'): ?>
                                <button class="btn btn-sm btn-outline-success btnApply" data-id="<?= (int)$alert['id'] ?>" title="Aplicar preço sugerido"><i class="fas fa-check"></i></button>
                                <button class="btn btn-sm btn-outline-info btnAck" data-id="<?= (int)$alert['id'] ?>" title="Reconhecer"><i class="fas fa-eye"></i></button>
                                <button class="btn btn-sm btn-outline-secondary btnDismiss" data-id="<?= (int)$alert['id'] ?>" title="Dispensar"><i class="fas fa-times"></i></button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = '<?= $csrfToken ?>';

    function handleAction(id, action) {
        const body = 'alert_id=' + id + '&action=' + action + '&csrf_token=' + csrfToken;
        fetch('?page=supply_cost_alerts&action=handleAlert', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': csrfToken},
            body: body
        }).then(r => r.json()).then(r => {
            if (r.success) {
                if (typeof AktiToast !== 'undefined') AktiToast.success(r.message || 'Ação realizada.');
                setTimeout(() => location.reload(), 500);
            } else {
                if (typeof AktiToast !== 'undefined') AktiToast.error(r.message || 'Erro.');
            }
        });
    }

    document.querySelectorAll('.btnApply').forEach(btn => {
        btn.addEventListener('click', function() {
            Swal.fire({title: 'Aplicar preço sugerido?', text: 'O preço do produto será atualizado.', icon: 'question', showCancelButton: true, confirmButtonText: 'Aplicar', cancelButtonText: 'Cancelar'}).then(r => {
                if (r.isConfirmed) handleAction(this.dataset.id, 'apply');
            });
        });
    });

    document.querySelectorAll('.btnAck').forEach(btn => {
        btn.addEventListener('click', function() { handleAction(this.dataset.id, 'acknowledge'); });
    });

    document.querySelectorAll('.btnDismiss').forEach(btn => {
        btn.addEventListener('click', function() { handleAction(this.dataset.id, 'dismiss'); });
    });

    // Settings modal
    document.getElementById('btnSettings')?.addEventListener('click', function() {
        fetch('?page=supply_cost_alerts&action=getSettings').then(r => r.json()).then(settings => {
            Swal.fire({
                title: 'Configurações de Insumos',
                html: `<div class="text-start">
                    <div class="mb-2"><label class="form-label">Margem Mínima (%)</label><input id="swalThreshold" class="form-control" type="number" step="0.01" value="${settings.min_margin_threshold || 15}"></div>
                    <div class="mb-2"><label class="form-label">Método Forecast</label><select id="swalForecast" class="form-select">
                        <option value="average" ${settings.forecast_calculation_method === 'average' ? 'selected' : ''}>Média simples</option>
                        <option value="weighted" ${settings.forecast_calculation_method === 'weighted' ? 'selected' : ''}>Média ponderada</option>
                        <option value="last_30_days" ${settings.forecast_calculation_method === 'last_30_days' ? 'selected' : ''}>Últimos 30 dias</option>
                    </select></div>
                    <div class="mb-2"><label class="form-label">Estratégia de Saída</label><select id="swalFefo" class="form-select">
                        <option value="fefo" ${settings.default_fefo_strategy === 'fefo' ? 'selected' : ''}>FEFO</option>
                        <option value="fifo" ${settings.default_fefo_strategy === 'fifo' ? 'selected' : ''}>FIFO</option>
                        <option value="manual" ${settings.default_fefo_strategy === 'manual' ? 'selected' : ''}>Manual</option>
                    </select></div>
                    <div class="mb-2"><label class="form-label">Precisão Decimal Padrão</label><input id="swalPrecision" class="form-control" type="number" min="2" max="6" value="${settings.default_decimal_precision || 4}"></div>
                    <div class="form-check mb-2"><input id="swalAutoCmp" class="form-check-input" type="checkbox" ${parseInt(settings.auto_recalculate_cmp) ? 'checked' : ''}><label class="form-check-label" for="swalAutoCmp">Recalcular CMP automaticamente</label></div>
                    <div class="form-check"><input id="swalNegStock" class="form-check-input" type="checkbox" ${parseInt(settings.allow_negative_stock) ? 'checked' : ''}><label class="form-check-label" for="swalNegStock">Permitir estoque negativo</label></div>
                </div>`,
                showCancelButton: true, confirmButtonText: 'Salvar', cancelButtonText: 'Cancelar',
                preConfirm: () => ({
                    min_margin_threshold: document.getElementById('swalThreshold').value,
                    forecast_calculation_method: document.getElementById('swalForecast').value,
                    default_fefo_strategy: document.getElementById('swalFefo').value,
                    default_decimal_precision: document.getElementById('swalPrecision').value,
                    auto_recalculate_cmp: document.getElementById('swalAutoCmp').checked ? 1 : 0,
                    allow_negative_stock: document.getElementById('swalNegStock').checked ? 1 : 0,
                })
            }).then(result => {
                if (result.isConfirmed) {
                    const d = result.value;
                    const body = Object.keys(d).map(k => k + '=' + encodeURIComponent(d[k])).join('&') + '&csrf_token=' + csrfToken;
                    fetch('?page=supply_cost_alerts&action=saveSettings', {method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': csrfToken}, body: body})
                        .then(r => r.json()).then(r => {
                            if (r.success && typeof AktiToast !== 'undefined') AktiToast.success('Configurações salvas.');
                        });
                }
            });
        });
    });
});
</script>
