<?php
/**
 * Widget: Pedidos Atrasados
 * Variáveis esperadas: $delayedOrders, $stagesMap
 */
?>
<div class="col-xl-6" id="home-atrasados">
    <div class="card border-0 shadow-sm h-100 <?= count($delayedOrders) > 0 ? 'border-start border-danger border-4' : '' ?>">
        <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold <?= count($delayedOrders) > 0 ? 'text-danger' : 'text-muted' ?>">
                <i class="fas fa-exclamation-triangle me-2"></i>Pedidos Atrasados
                <?php if (count($delayedOrders) > 0): ?>
                    <span class="badge bg-danger rounded-pill ms-1"><?= count($delayedOrders) ?></span>
                <?php endif; ?>
            </h6>
            <a href="?page=pipeline" class="btn btn-sm btn-outline-danger">Ver todos <i class="fas fa-arrow-right ms-1"></i></a>
        </div>
        <div class="card-body p-0">
            <?php if (empty($delayedOrders)): ?>
            <div class="text-center text-muted py-4">
                <i class="fas fa-check-circle fa-2x d-block mb-2 text-success opacity-50"></i>
                <small>Nenhum pedido atrasado!</small>
            </div>
            <?php else: ?>
            <div class="list-group list-group-flush" style="max-height:220px;overflow-y:auto;">
                <?php foreach (array_slice($delayedOrders, 0, 6) as $dOrder):
                    $dStage = $stagesMap[$dOrder['pipeline_stage']] ?? ['label'=>$dOrder['pipeline_stage'],'color'=>'#999','icon'=>'fas fa-circle'];
                ?>
                <a href="?page=pipeline&action=detail&id=<?= $dOrder['id'] ?>" class="list-group-item list-group-item-action py-2 px-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <span class="rounded-circle d-inline-flex align-items-center justify-content-center"
                                  style="width:28px;height:28px;background:<?= $dStage['color'] ?>;color:#fff;font-size:0.65rem;">
                                <i class="<?= $dStage['icon'] ?>"></i>
                            </span>
                            <div>
                                <span class="fw-bold small">#<?= str_pad($dOrder['id'], 4, '0', STR_PAD_LEFT) ?></span>
                                <span class="ms-1 small text-muted"><?= e($dOrder['customer_name'] ?? '') ?></span>
                            </div>
                        </div>
                        <span class="badge bg-danger rounded-pill" style="font-size:0.65rem;">+<?= $dOrder['delay_hours'] ?>h</span>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
