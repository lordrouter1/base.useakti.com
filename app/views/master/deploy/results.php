<?php
/**
 * View: Deploy — Resultados do Deploy
 */
$pageTitle = 'Resultado do Deploy';
$pageSubtitle = $deployResults['timestamp'];
$topbarActions = '<a href="?page=master_deploy" class="btn btn-akti-outline"><i class="fas fa-arrow-left me-2"></i>Voltar</a>';

$steps = $deployResults['steps'];
$allSuccess = $deployResults['all_success'];
$successSteps = count(array_filter($steps, fn($s) => $s['success']));
$failedSteps = count($steps) - $successSteps;
?>

<!-- Overall Status -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card <?= $allSuccess ? 'border-success' : 'border-danger' ?>">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <?php if ($allSuccess): ?>
                    <i class="fas fa-check-circle fa-3x text-success"></i>
                    <div>
                        <h4 class="mb-0 text-success">Deploy concluído com sucesso!</h4>
                        <small class="text-muted">Todas as <?= count($steps) ?> etapa(s) finalizaram sem erros.</small>
                    </div>
                <?php else: ?>
                    <i class="fas fa-exclamation-circle fa-3x text-danger"></i>
                    <div>
                        <h4 class="mb-0 text-danger">Deploy com problemas</h4>
                        <small class="text-muted"><?= $failedSteps ?> etapa(s) falharam de <?= count($steps) ?> total.</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Stats -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="stat-card" style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
            <div class="stat-icon"><i class="fas fa-list-check"></i></div>
            <div class="stat-value"><?= count($steps) ?></div>
            <div class="stat-label">Etapas Executadas</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card" style="background: linear-gradient(135deg, #10b981, #059669);">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-value"><?= $successSteps ?></div>
            <div class="stat-label">Sucesso</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
            <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
            <div class="stat-value"><?= $failedSteps ?></div>
            <div class="stat-label">Falhas</div>
        </div>
    </div>
</div>

<!-- Step Details -->
<div class="card">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="fas fa-list" style="color: var(--akti-primary);"></i>
        <strong>Detalhes por Etapa</strong>
    </div>
    <div class="card-body p-0">
        <?php foreach ($steps as $i => $step): ?>
            <div class="p-3 border-bottom <?= $step['success'] ? '' : 'bg-danger bg-opacity-10' ?>">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge rounded-pill <?= $step['success'] ? 'bg-success' : 'bg-danger' ?>" style="width:28px; height:28px; display:flex; align-items:center; justify-content:center;">
                            <?= $i + 1 ?>
                        </span>
                        <i class="fas <?= $step['icon'] ?> <?= $step['success'] ? 'text-success' : 'text-danger' ?>"></i>
                        <strong><?= htmlspecialchars($step['step']) ?></strong>
                    </div>
                    <?php if ($step['success']): ?>
                        <span class="badge bg-success"><i class="fas fa-check me-1"></i>Sucesso</span>
                    <?php else: ?>
                        <span class="badge bg-danger"><i class="fas fa-times me-1"></i>Falha</span>
                    <?php endif; ?>
                </div>
                <p class="mb-0 ms-5" style="font-size:13px;"><?= htmlspecialchars($step['message']) ?></p>
                <?php if (!empty($step['output'])): ?>
                    <pre class="ms-5 mt-2 mb-0" style="background:#1e1e2e; color:#cdd6f4; padding:10px; border-radius:6px; font-size:11px; max-height:150px; overflow:auto;"><?= htmlspecialchars($step['output']) ?></pre>
                <?php endif; ?>
                <?php if (!empty($step['details'])): ?>
                    <div class="ms-5 mt-2" style="font-size:12px;">
                        <?php foreach ($step['details'] as $d): ?>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <i class="fas <?= $d['moved'] ? 'fa-check text-success' : 'fa-exclamation text-warning' ?>" style="font-size:10px;"></i>
                                <code><?= htmlspecialchars($d['file']) ?></code>
                                <small class="text-muted">(<?= $d['success'] ?>/<?= $d['total'] ?> DBs)</small>
                                <?php if ($d['moved']): ?>
                                    <span class="badge bg-success" style="font-size:9px;">prontos/</span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="mt-4 d-flex justify-content-center gap-3">
    <a href="?page=master_deploy" class="btn btn-akti-outline">
        <i class="fas fa-redo me-2"></i>Novo Deploy
    </a>
    <a href="?page=master_dashboard" class="btn btn-akti">
        <i class="fas fa-home me-2"></i>Dashboard
    </a>
</div>
