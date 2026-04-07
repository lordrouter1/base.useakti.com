<?php
/**
 * View: Migrations - Resultados da Migração
 */
$pageTitle = 'Resultados da Migração';
$pageSubtitle = htmlspecialchars($migrationResults['name'] ?? 'Migração');
$topbarActions = '<a href="?page=master_migrations" class="btn btn-akti-outline"><i class="fas fa-arrow-left me-2"></i>Voltar</a>';

$isBatch = !empty($migrationResults['batch']);
$databases = $migrationResults['databases'] ?? [];
$initBase = $migrationResults['init_base'] ?? null;
$sqlPreview = $migrationResults['sql_preview'] ?? '';
$movedToProntos = $migrationResults['moved_to_prontos'] ?? false;
$sourceFile = $migrationResults['source_file'] ?? '';
$batchFiles = $migrationResults['files'] ?? [];

$totalDbs = count($databases);
$successDbs = count(array_filter($databases, fn($r) => $r['status'] === 'success'));
$partialDbs = count(array_filter($databases, fn($r) => $r['status'] === 'partial'));
$failedDbs  = count(array_filter($databases, fn($r) => $r['status'] === 'failed' || $r['status'] === 'error'));
$skippedDbs = count(array_filter($databases, fn($r) => $r['status'] === 'skipped'));
?>

<?php if ($isBatch): ?>
<!-- Batch Results -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="stat-card" style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
            <div class="stat-icon"><i class="fas fa-files"></i></div>
            <div class="stat-value"><?= count($batchFiles) ?></div>
            <div class="stat-label">Arquivos Processados</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card" style="background: linear-gradient(135deg, #10b981, #059669);">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-value"><?= count(array_filter($batchFiles, fn($f) => $f['moved'])) ?></div>
            <div class="stat-label">Movidos p/ Prontos</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
            <div class="stat-icon"><i class="fas fa-exclamation-circle"></i></div>
            <div class="stat-value"><?= count(array_filter($batchFiles, fn($f) => !$f['moved'])) ?></div>
            <div class="stat-label">Com Problemas</div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="fas fa-list-check" style="color: var(--akti-primary);"></i>
        <strong>Resultado por Arquivo</strong>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0" style="font-size:13px;">
                <thead>
                    <tr style="background:#f8f9fa;">
                        <th class="ps-3">Arquivo</th>
                        <th>Sucesso</th>
                        <th>Total DBs</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($batchFiles as $bf): ?>
                        <tr class="<?= $bf['moved'] ? '' : 'table-warning' ?>">
                            <td class="ps-3"><code style="font-size:12px;"><?= htmlspecialchars($bf['file']) ?></code></td>
                            <td><strong class="text-success"><?= $bf['success'] ?></strong></td>
                            <td><?= $bf['total'] ?></td>
                            <td>
                                <?php if ($bf['moved']): ?>
                                    <span class="badge bg-success"><i class="fas fa-check me-1"></i>Movido p/ prontos/</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark"><i class="fas fa-exclamation me-1"></i>Pendente</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php else: ?>

<!-- Resumo -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #10b981, #059669);">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-value"><?= $successDbs ?></div>
            <div class="stat-label">Sucesso</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
            <div class="stat-icon"><i class="fas fa-exclamation-circle"></i></div>
            <div class="stat-value"><?= $partialDbs ?></div>
            <div class="stat-label">Parcial</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
            <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
            <div class="stat-value"><?= $failedDbs ?></div>
            <div class="stat-label">Falhas</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #6b7280, #4b5563);">
            <div class="stat-icon"><i class="fas fa-forward"></i></div>
            <div class="stat-value"><?= $skippedDbs ?></div>
            <div class="stat-label">Já Aplicado</div>
        </div>
    </div>
</div>

<!-- SQL aplicado -->
<?php if ($sqlPreview): ?>
<div class="card mb-4">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="fas fa-code" style="color: var(--akti-primary);"></i>
        <strong>SQL Executado</strong>
    </div>
    <div class="card-body">
        <pre style="background:#1e1e1e; color:#d4d4d4; padding:16px; border-radius:8px; font-size:12px; max-height:200px; overflow-y:auto; margin:0;"><?= htmlspecialchars($sqlPreview) ?><?php if (strlen($migrationResults['sql_preview'] ?? '') >= 500): ?>...<?php endif; ?></pre>
    </div>
</div>
<?php endif; ?>

<!-- Init Base -->
<?php if ($initBase): ?>
<?php $initBaseName = getenv('AKTI_MASTER_INIT_BASE') ?: 'akti_init_base'; ?>
<div class="card mb-4">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="fas fa-star" style="color: #f59e0b;"></i>
        <strong>Banco de Referência (<?= htmlspecialchars($initBaseName) ?>)</strong>
        <?php if ($initBase['failed'] === 0): ?>
            <span class="badge bg-success ms-auto">OK</span>
        <?php else: ?>
            <span class="badge bg-warning text-dark ms-auto"><?= $initBase['failed'] ?> erro(s)</span>
        <?php endif; ?>
    </div>
    <?php if (!empty($initBase['errors'])): ?>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0" style="font-size:12px;">
                <tbody>
                    <?php foreach ($initBase['errors'] as $err): ?>
                        <tr class="table-danger">
                            <td class="ps-3" style="width:60px;">#<?= $err['index'] ?></td>
                            <td><code style="font-size:11px;"><?= htmlspecialchars(mb_substr($err['sql'], 0, 120)) ?></code></td>
                            <td class="text-danger"><?= htmlspecialchars($err['error']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Resultado por banco -->
<div class="card">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="fas fa-list-check" style="color: var(--akti-primary);"></i>
        <strong>Resultado por Banco</strong>
        <span class="badge bg-secondary ms-auto"><?= $totalDbs ?> banco(s)</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0" style="font-size:13px;">
                <thead>
                    <tr style="background:#f8f9fa;">
                        <th class="ps-3">Banco</th>
                        <th>Status</th>
                        <th>Detalhes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($databases as $dbName => $res): ?>
                        <tr class="<?php
                            if ($res['status'] === 'success') echo '';
                            elseif ($res['status'] === 'skipped') echo 'table-light';
                            elseif ($res['status'] === 'partial') echo 'table-warning';
                            else echo 'table-danger';
                        ?>">
                            <td class="ps-3">
                                <code style="font-size:12px;"><?= htmlspecialchars($dbName) ?></code>
                            </td>
                            <td>
                                <?php if ($res['status'] === 'success'): ?>
                                    <span class="badge bg-success"><i class="fas fa-check me-1"></i>Sucesso</span>
                                <?php elseif ($res['status'] === 'skipped'): ?>
                                    <span class="badge bg-secondary"><i class="fas fa-forward me-1"></i>Já aplicado</span>
                                <?php elseif ($res['status'] === 'partial'): ?>
                                    <span class="badge bg-warning text-dark"><i class="fas fa-exclamation me-1"></i>Parcial</span>
                                <?php else: ?>
                                    <span class="badge bg-danger"><i class="fas fa-times me-1"></i>Falha</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($res['message']) ?>
                                <?php if (!empty($res['result']['errors'])): ?>
                                    <br>
                                    <?php foreach ($res['result']['errors'] as $err): ?>
                                        <small class="text-danger d-block mt-1">
                                            <i class="fas fa-arrow-right me-1"></i>
                                            #<?= $err['index'] ?>: <?= htmlspecialchars(mb_substr($err['error'], 0, 150)) ?>
                                        </small>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($sourceFile && $movedToProntos): ?>
<div class="alert alert-success d-flex align-items-center mt-4" role="alert">
    <i class="fas fa-folder-open me-3 fa-lg"></i>
    <div>
        <strong>Arquivo movido!</strong> <code><?= htmlspecialchars($sourceFile) ?></code> foi movido para <code>sql/prontos/</code>.
    </div>
</div>
<?php endif; ?>

<?php endif; /* end batch vs single */ ?>

<div class="mt-4 text-center">
    <a href="?page=master_migrations" class="btn btn-akti">
        <i class="fas fa-arrow-left me-2"></i>Voltar para Migrações
    </a>
</div>
