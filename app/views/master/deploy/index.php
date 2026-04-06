<?php
/**
 * View: Deploy — Painel de Deploy Automatizado
 */
$pageTitle = 'Deploy';
$pageSubtitle = 'Deploy automatizado: Git Pull + Migrações + Cache';

$pendingCount = count($pendingSql);
$tenantDbs = array_column($tenants, 'db_name');

$pageScripts = <<<'SCRIPTS'
<script>
$(document).ready(function() {
    // Select/deselect all databases
    $('#selectAllDbs').on('change', function() {
        $('input[name="selected_dbs[]"]').prop('checked', $(this).is(':checked'));
    });

    // Deploy form submit
    $('#deployForm').on('submit', function(e) {
        e.preventDefault();
        var form = this;

        var steps = [];
        if ($('#doGitPull').is(':checked')) steps.push('Git Pull');
        if ($('#doMigrations').is(':checked')) steps.push('Migrações (' + $('input[name="selected_dbs[]"]:checked').length + ' DBs)');
        if ($('#doCacheClear').is(':checked')) steps.push('Limpar Cache');

        if (steps.length === 0) {
            Swal.fire({icon:'warning', title:'Nenhuma etapa', text:'Selecione pelo menos uma etapa do deploy.'});
            return;
        }

        Swal.fire({
            icon: 'warning',
            title: 'Confirmar Deploy?',
            html: '<strong>Etapas:</strong><br>' + steps.map(function(s) { return '• ' + s; }).join('<br>') + '<br><br><small class="text-muted">O deploy será executado sequencialmente.</small>',
            showCancelButton: true,
            confirmButtonColor: '#4f46e5',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-rocket me-1"></i>Executar Deploy',
            cancelButtonText: 'Cancelar',
            reverseButtons: true
        }).then(function(result) {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Executando deploy...',
                    html: '<i class="fas fa-spinner fa-spin fa-2x"></i><br><br>Processando etapas...',
                    showConfirmButton: false,
                    allowOutsideClick: false
                });
                form.submit();
            }
        });
    });

    // Toggle migration db selection visibility
    $('#doMigrations').on('change', function() {
        $('#migrationDbSection').toggle($(this).is(':checked'));
    });
});
</script>
SCRIPTS;
?>

<!-- Status Overview -->
<div class="row g-4 mb-4">
    <div class="col-xl-4 col-md-6">
        <div class="stat-card" style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
            <div class="stat-icon"><i class="fas fa-code-branch"></i></div>
            <div class="stat-value"><?= $mainRepo ? htmlspecialchars($mainRepo['branch'] ?? 'N/A') : 'N/A' ?></div>
            <div class="stat-label">Branch Atual</div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6">
        <div class="stat-card" style="background: linear-gradient(135deg, <?= $pendingCount > 0 ? '#f59e0b, #d97706' : '#10b981, #059669' ?>);">
            <div class="stat-icon"><i class="fas fa-file-code"></i></div>
            <div class="stat-value"><?= $pendingCount ?></div>
            <div class="stat-label">SQL Pendentes</div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6">
        <div class="stat-card bg-primary-gradient">
            <div class="stat-icon"><i class="fas fa-database"></i></div>
            <div class="stat-value"><?= count($tenants) ?></div>
            <div class="stat-label">Bancos Tenant</div>
        </div>
    </div>
</div>

<!-- Deploy Form -->
<form id="deployForm" action="?page=master_deploy&action=run" method="POST">
    <?= csrf_field() ?>

    <div class="row g-4">
        <!-- Pipeline Steps -->
        <div class="col-lg-7">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="fas fa-rocket" style="color: var(--akti-primary);"></i>
                    <strong>Pipeline de Deploy</strong>
                </div>
                <div class="card-body">
                    <!-- Step 1: Git Pull -->
                    <div class="d-flex align-items-start gap-3 p-3 rounded-3 mb-3" style="background:#f0f4ff;">
                        <div class="form-check form-switch mt-1">
                            <input type="checkbox" name="do_git_pull" id="doGitPull" class="form-check-input" checked>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="fw-bold mb-1"><i class="fas fa-code-branch me-2 text-primary"></i>1. Git Pull</h6>
                            <small class="text-muted">Atualiza o código do repositório. Executa <code>git pull</code> no diretório base.</small>
                            <?php if ($mainRepo): ?>
                                <div class="mt-1" style="font-size:12px;">
                                    <span class="text-muted">Branch:</span> <code><?= htmlspecialchars($mainRepo['branch'] ?? 'main') ?></code>
                                    <?php if (!empty($mainRepo['status']) && $mainRepo['status'] === 'behind'): ?>
                                        <span class="badge bg-warning text-dark ms-2">Behind</span>
                                    <?php elseif (!empty($mainRepo['has_changes'])): ?>
                                        <span class="badge bg-info ms-2">Changes</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Step 2: Migrations -->
                    <div class="d-flex align-items-start gap-3 p-3 rounded-3 mb-3" style="background:#fff8f0;">
                        <div class="form-check form-switch mt-1">
                            <input type="checkbox" name="do_migrations" id="doMigrations" class="form-check-input" <?= $pendingCount > 0 ? 'checked' : '' ?>>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="fw-bold mb-1"><i class="fas fa-database me-2 text-warning"></i>2. Aplicar Migrações</h6>
                            <small class="text-muted">Executa arquivos SQL pendentes em <code>/sql/</code> e move para <code>/sql/prontos/</code>.</small>
                            <?php if ($pendingCount > 0): ?>
                                <div class="mt-2" style="font-size:12px;">
                                    <?php foreach ($pendingSql as $sf): ?>
                                        <div class="d-flex align-items-center gap-1 mb-1">
                                            <i class="fas fa-file-alt text-warning" style="font-size:10px;"></i>
                                            <code><?= htmlspecialchars($sf['name']) ?></code>
                                            <small class="text-muted">(<?= number_format($sf['size'] / 1024, 1) ?> KB)</small>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="mt-1"><span class="badge bg-success" style="font-size:11px;">Nenhuma migração pendente</span></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Step 3: Cache Clear -->
                    <div class="d-flex align-items-start gap-3 p-3 rounded-3" style="background:#f0fff4;">
                        <div class="form-check form-switch mt-1">
                            <input type="checkbox" name="do_cache_clear" id="doCacheClear" class="form-check-input" checked>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="fw-bold mb-1"><i class="fas fa-broom me-2 text-success"></i>3. Limpar Cache</h6>
                            <small class="text-muted">Limpa o OPcache do PHP para garantir que o código atualizado seja utilizado.</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-akti btn-lg">
                    <i class="fas fa-rocket me-2"></i>Executar Deploy
                </button>
            </div>
        </div>

        <!-- Target DBs Selection -->
        <div class="col-lg-5">
            <div class="card" id="migrationDbSection" <?= $pendingCount === 0 ? 'style="display:none;"' : '' ?>>
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-server" style="color: var(--akti-primary);"></i>
                        <strong>Bancos Alvo (Migrações)</strong>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="px-3 py-2 border-bottom" style="background:#f8f9fa;">
                        <div class="form-check">
                            <input type="checkbox" id="selectAllDbs" class="form-check-input" checked>
                            <label class="form-check-label fw-semibold" for="selectAllDbs" style="font-size:13px;">Selecionar todos</label>
                        </div>
                    </div>
                    <!-- akti_master - Sistema Master -->
                    <div class="px-3 py-2 border-bottom d-flex align-items-center justify-content-between" style="font-size:13px; background: #fff3cd;">
                        <div class="form-check mb-0">
                            <input type="checkbox" name="selected_dbs[]" value="akti_master" 
                                   class="form-check-input" id="deploy_akti_master">
                            <label class="form-check-label" for="deploy_akti_master">
                                <code style="background:#ffc107; color:#000; padding:2px 6px; border-radius:4px;">akti_master</code>
                                <span class="text-muted ms-1">(Sistema Master)</span>
                            </label>
                        </div>
                        <span class="badge bg-warning text-dark" style="font-size:9px;"><i class="fas fa-crown me-1"></i>Master</span>
                    </div>
                    <div style="max-height:400px; overflow-y:auto;">
                        <?php foreach ($tenants as $t): ?>
                            <div class="px-3 py-2 border-bottom d-flex align-items-center justify-content-between" style="font-size:13px;">
                                <div class="form-check mb-0">
                                    <input type="checkbox" name="selected_dbs[]" value="<?= htmlspecialchars($t['db_name']) ?>" 
                                           class="form-check-input" id="deploy_<?= htmlspecialchars($t['db_name']) ?>" checked>
                                    <label class="form-check-label" for="deploy_<?= htmlspecialchars($t['db_name']) ?>">
                                        <code><?= htmlspecialchars($t['db_name']) ?></code>
                                        <span class="text-muted ms-1">(<?= htmlspecialchars($t['client_name']) ?>)</span>
                                    </label>
                                </div>
                                <?php if (!$t['is_active']): ?>
                                    <span class="badge bg-secondary" style="font-size:9px;">Inativo</span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
