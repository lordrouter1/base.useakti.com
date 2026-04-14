<?php
/**
 * Remessas — Formulário
 * Variáveis: $shipment (null para novo), $carriers
 */
$isEdit = !empty($shipment);
?>
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div><h1 class="h2 mb-1"><i class="fas fa-shipping-fast me-2 text-primary"></i><?= $isEdit ? 'Editar Remessa' : 'Nova Remessa' ?></h1></div>
        <a href="?page=shipments" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="post" action="?page=shipments&action=store">
                <?= csrf_field() ?>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Pedido (ID) <span class="text-danger">*</span></label>
                        <input type="number" name="order_id" class="form-control" value="<?= (int) ($shipment['order_id'] ?? ($_GET['order_id'] ?? 0)) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Transportadora</label>
                        <select name="carrier_id" class="form-select">
                            <option value="">Nenhuma</option>
                            <?php foreach ($carriers ?? [] as $c): ?>
                            <option value="<?= (int) $c['id'] ?>" <?= ($shipment['carrier_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Código de Rastreio</label>
                        <input type="text" name="tracking_code" class="form-control" value="<?= eAttr($shipment['tracking_code'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Método de Envio</label>
                        <input type="text" name="shipping_method" class="form-control" value="<?= eAttr($shipment['shipping_method'] ?? '') ?>" placeholder="Ex: PAC, SEDEX, Transportadora">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Custo do Frete (R$)</label>
                        <input type="number" name="shipping_cost" class="form-control" step="0.01" value="<?= eAttr($shipment['shipping_cost'] ?? '0.00') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Previsão de Entrega</label>
                        <input type="date" name="estimated_date" class="form-control" value="<?= eAttr($shipment['estimated_date'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Observações</label>
                        <textarea name="notes" class="form-control" rows="2"><?= e($shipment['notes'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Criar Remessa</button>
                </div>
            </form>
        </div>
    </div>
</div>
