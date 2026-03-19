<?php
/**
 * Widget: Pipeline Mini Overview
 * Variáveis esperadas: $stagesMap, $pipelineCounts
 */
?>
<div class="card border-0 shadow-sm mb-4" id="home-pipeline">
    <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 text-primary fw-bold"><i class="fas fa-stream me-2"></i>Pipeline</h6>
        <a href="?page=pipeline" class="btn btn-sm btn-outline-primary">Ver Kanban <i class="fas fa-arrow-right ms-1"></i></a>
    </div>
    <div class="card-body p-3">
        <div class="row g-2">
            <?php foreach ($stagesMap as $sKey => $sInfo):
                if ($sKey === 'concluido') continue;
                $count = $pipelineCounts[$sKey] ?? 0;
            ?>
            <div class="col">
                <a href="?page=pipeline" class="text-decoration-none">
                    <div class="text-center p-2 rounded pipeline-mini-card" style="background:<?= $sInfo['color'] ?>15; border:1px solid <?= $sInfo['color'] ?>30;">
                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center mx-auto mb-1"
                             style="width:36px;height:36px;background:<?= $sInfo['color'] ?>;color:#fff;font-size:0.8rem;">
                            <i class="<?= $sInfo['icon'] ?>"></i>
                        </div>
                        <div class="fw-bold fs-5" style="color:<?= $sInfo['color'] ?>;"><?= $count ?></div>
                        <div class="text-muted" style="font-size:0.7rem;"><?= $sInfo['label'] ?></div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
