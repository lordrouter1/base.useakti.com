<?php
/**
 * Remessas — Visualização com timeline de eventos
 * Variáveis: $shipment, $events
 */
$shColors = ['preparing' => 'warning', 'shipped' => 'primary', 'in_transit' => 'info', 'delivered' => 'success', 'returned' => 'danger'];
$shLabels = ['preparing' => 'Preparando', 'shipped' => 'Enviado', 'in_transit' => 'Em Trânsito', 'delivered' => 'Entregue', 'returned' => 'Devolvido'];
?>
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div>
            <h1 class="h2 mb-1"><i class="fas fa-shipping-fast me-2 text-primary"></i>Remessa #<?= (int) $shipment['id'] ?></h1>
            <p class="text-muted mb-0" style="font-size:.82rem;">
                Pedido #<?= (int) $shipment['order_id'] ?> &middot;
                <span class="badge bg-<?= $shColors[$shipment['status']] ?? 'secondary' ?>"><?= $shLabels[$shipment['status']] ?? $shipment['status'] ?></span>
            </p>
        </div>
        <a href="?page=shipments" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white"><strong>Detalhes</strong></div>
                <div class="card-body small">
                    <p class="mb-1"><strong>Rastreio:</strong> <code><?= e($shipment['tracking_code'] ?? '-') ?></code></p>
                    <p class="mb-1"><strong>Transportadora:</strong> <?= e($shipment['carrier_name'] ?? '-') ?></p>
                    <p class="mb-1"><strong>Método:</strong> <?= e($shipment['shipping_method'] ?? '-') ?></p>
                    <p class="mb-1"><strong>Custo:</strong> R$ <?= e(number_format((float)($shipment['shipping_cost'] ?? 0), 2, ',', '.')) ?></p>
                    <p class="mb-1"><strong>Previsão:</strong> <?= !empty($shipment['estimated_date']) ? e(date('d/m/Y', strtotime($shipment['estimated_date']))) : '-' ?></p>
                    <?php if (!empty($shipment['shipped_at'])): ?>
                    <p class="mb-1"><strong>Enviado em:</strong> <?= e(date('d/m/Y H:i', strtotime($shipment['shipped_at']))) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($shipment['delivered_at'])): ?>
                    <p class="mb-1"><strong>Entregue em:</strong> <?= e(date('d/m/Y H:i', strtotime($shipment['delivered_at']))) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <!-- Timeline de eventos -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white"><strong>Timeline de Eventos</strong></div>
                <div class="card-body">
                    <?php if (empty($events)): ?>
                        <p class="text-muted">Nenhum evento registrado.</p>
                    <?php else: ?>
                        <?php foreach ($events as $ev): ?>
                        <div class="border-start border-3 border-primary ps-3 mb-3">
                            <div class="d-flex justify-content-between">
                                <strong><?= e($ev['status']) ?></strong>
                                <small class="text-muted"><?= e(date('d/m/Y H:i', strtotime($ev['created_at']))) ?></small>
                            </div>
                            <?php if (!empty($ev['location'])): ?><small class="text-muted"><i class="fas fa-map-marker-alt me-1"></i><?= e($ev['location']) ?></small><br><?php endif; ?>
                            <p class="mb-0"><?= e($ev['description'] ?? '') ?></p>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Adicionar evento -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white"><strong>Adicionar Evento</strong></div>
                <div class="card-body">
                    <form method="post" action="?page=shipments&action=addEvent">
                        <?= csrf_field() ?>
                        <input type="hidden" name="shipment_id" value="<?= (int) $shipment['id'] ?>">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Status do Evento</label>
                                <input type="text" name="status" class="form-control" placeholder="Ex: Em trânsito, Saiu para entrega" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Local</label>
                                <input type="text" name="location" class="form-control" placeholder="Cidade/UF">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Atualizar status da remessa</label>
                                <select name="update_shipment_status" class="form-select">
                                    <option value="">Não alterar</option>
                                    <?php foreach ($shLabels as $k => $v): ?>
                                    <option value="<?= $k ?>"><?= $v ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Descrição</label>
                                <textarea name="description" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-plus me-1"></i>Registrar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
