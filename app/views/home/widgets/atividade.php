<?php
/**
 * Widget: Atividade Recente
 * Variáveis esperadas: $recentesMov, $stagesMap
 */
?>
<div class="col-md-6" id="home-atividade">
    <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-history me-2"></i>Atividade Recente</h6>
            <a href="?page=pipeline" class="btn btn-sm btn-outline-secondary">Ver Pipeline</a>
        </div>
        <div class="card-body p-0">
            <?php if (empty($recentesMov)): ?>
            <div class="text-center text-muted py-4">
                <i class="fas fa-history d-block mb-2" style="font-size:1.5rem;opacity:0.4;"></i>
                <small>Nenhuma movimentação recente</small>
            </div>
            <?php else: ?>
            <div class="list-group list-group-flush">
                <?php foreach ($recentesMov as $mov):
                    $stInfo = $stagesMap[$mov['to_stage']] ?? ['label'=>$mov['to_stage'],'color'=>'#999','icon'=>'fas fa-circle'];
                ?>
                <a href="?page=pipeline&action=detail&id=<?= $mov['order_id'] ?>" class="list-group-item list-group-item-action py-2 px-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <span class="rounded-circle d-inline-flex align-items-center justify-content-center"
                                  style="width:24px;height:24px;background:<?= $stInfo['color'] ?>;color:#fff;font-size:0.6rem;">
                                <i class="<?= $stInfo['icon'] ?>"></i>
                            </span>
                            <div>
                                <span class="fw-bold small">#<?= str_pad($mov['order_id'], 4, '0', STR_PAD_LEFT) ?></span>
                                <span class="ms-1 small text-muted"><?= e($mov['customer_name'] ?? '') ?></span>
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="badge rounded-pill" style="background:<?= $stInfo['color'] ?>;font-size:0.6rem;"><?= $stInfo['label'] ?></span>
                            <div class="text-muted" style="font-size:0.6rem;"><?= date('d/m H:i', strtotime($mov['created_at'])) ?></div>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
