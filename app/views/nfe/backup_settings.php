<?php
/**
 * View: Backup de XMLs — Configurações e Histórico — FASE5-08
 *
 * Permite configurar backups automáticos (local, S3, FTP) e visualizar
 * o histórico de backups realizados. Também permite executar backup manual.
 *
 * @var array  $backupHistory  Histórico de backups
 * @var array  $backupConfig   Configurações de backup (array key=>value)
 * @var bool   $isAjax         Se carregamento via AJAX
 */
$pageTitle = 'Backup de XMLs';
$isAjax = $isAjax ?? false;
$backupHistory = $backupHistory ?? [];
$backupConfig = $backupConfig ?? [];
?>

<?php if (!$isAjax): ?>
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div>
            <h1 class="h2 mb-1"><i class="fas fa-cloud-upload-alt me-2 text-primary"></i>Backup de XMLs</h1>
            <p class="text-muted mb-0" style="font-size:.82rem;">Configuração de backup externo e histórico de execuções.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="?page=nfe_documents" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Voltar
            </a>
        </div>
    </div>
<?php endif; ?>

    <div class="row g-4">

        <!-- ═══ Coluna: Backup Manual + Configurações ═══ -->
        <div class="col-xl-5">

            <!-- Backup Manual -->
            <div class="card border-0 shadow-sm mb-4" style="border-radius:12px;">
                <div class="card-header py-2 card-header-nfe-blue">
                    <h6 class="mb-0 text-white" style="font-size:.85rem;">
                        <i class="fas fa-download me-2"></i>Executar Backup Manual
                    </h6>
                </div>
                <div class="card-body p-3">
                    <p class="small text-muted mb-3">Selecione o período e o tipo de backup para executar agora.</p>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Data Início</label>
                            <input type="date" class="form-control form-control-sm" id="backupStartDate" value="<?= date('Y-m-01') ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Data Fim</label>
                            <input type="date" class="form-control form-control-sm" id="backupEndDate" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Tipo de Backup</label>
                            <select class="form-select form-select-sm" id="backupTipo">
                                <option value="local">Local (ZIP no servidor)</option>
                                <option value="s3">Amazon S3</option>
                                <option value="ftp">FTP Externo</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <button type="button" class="btn btn-primary w-100" id="btnExecuteBackup">
                                <i class="fas fa-cloud-upload-alt me-1"></i> Executar Backup
                            </button>
                        </div>
                    </div>
                    <div id="backupResult" class="mt-3" style="display:none;"></div>
                </div>
            </div>

            <!-- Configurações de Backup Automático -->
            <div class="card border-0 shadow-sm" style="border-radius:12px;">
                <div class="card-header py-2 card-header-nfe-dark">
                    <h6 class="mb-0 text-white" style="font-size:.85rem;">
                        <i class="fas fa-cog me-2"></i>Configurações de Backup Automático
                    </h6>
                </div>
                <div class="card-body p-3">
                    <form method="POST" action="?page=nfe_documents&action=saveBackupSettings">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

                        <!-- Habilitar backup automático -->
                        <div class="form-check form-switch mb-3">
                            <input type="hidden" name="backup_auto_enabled" value="0">
                            <input class="form-check-input" type="checkbox" id="backupAutoEnabled" name="backup_auto_enabled" value="1"
                                   <?= ($backupConfig['backup_auto_enabled'] ?? '0') === '1' ? 'checked' : '' ?>>
                            <label class="form-check-label small fw-bold" for="backupAutoEnabled">
                                Backup automático diário
                            </label>
                        </div>

                        <!-- Tipo padrão -->
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Tipo de Backup Padrão</label>
                            <select class="form-select form-select-sm" name="backup_tipo" id="cfgBackupTipo">
                                <option value="local" <?= ($backupConfig['backup_tipo'] ?? 'local') === 'local' ? 'selected' : '' ?>>Local</option>
                                <option value="s3" <?= ($backupConfig['backup_tipo'] ?? '') === 's3' ? 'selected' : '' ?>>Amazon S3</option>
                                <option value="ftp" <?= ($backupConfig['backup_tipo'] ?? '') === 'ftp' ? 'selected' : '' ?>>FTP Externo</option>
                            </select>
                        </div>

                        <!-- Retenção -->
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Retenção (dias)</label>
                            <input type="number" class="form-control form-control-sm" name="backup_retention_days"
                                   value="<?= (int)($backupConfig['backup_retention_days'] ?? 365) ?>" min="30" max="3650">
                            <small class="text-muted">Backups mais antigos serão removidos automaticamente.</small>
                        </div>

                        <hr class="my-3">

                        <!-- Configurações S3 -->
                        <div id="cfgS3" class="<?= ($backupConfig['backup_tipo'] ?? '') !== 's3' ? 'd-none' : '' ?>">
                            <h6 class="fw-bold small text-info mb-2"><i class="fab fa-aws me-1"></i> Amazon S3</h6>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label small">Bucket</label>
                                    <input type="text" class="form-control form-control-sm" name="backup_s3_bucket"
                                           value="<?= eAttr($backupConfig['backup_s3_bucket'] ?? '') ?>" placeholder="meu-bucket-nfe">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small">Região</label>
                                    <input type="text" class="form-control form-control-sm" name="backup_s3_region"
                                           value="<?= eAttr($backupConfig['backup_s3_region'] ?? '') ?>" placeholder="us-east-1">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small">Access Key</label>
                                    <input type="password" class="form-control form-control-sm" name="backup_s3_key"
                                           value="<?= eAttr($backupConfig['backup_s3_key'] ?? '') ?>" placeholder="AKIA...">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small">Secret Key</label>
                                    <input type="password" class="form-control form-control-sm" name="backup_s3_secret"
                                           value="<?= eAttr($backupConfig['backup_s3_secret'] ?? '') ?>" placeholder="wJalr...">
                                </div>
                            </div>
                        </div>

                        <!-- Configurações FTP -->
                        <div id="cfgFtp" class="<?= ($backupConfig['backup_tipo'] ?? '') !== 'ftp' ? 'd-none' : '' ?>">
                            <h6 class="fw-bold small text-warning mb-2"><i class="fas fa-server me-1"></i> FTP Externo</h6>
                            <div class="row g-2 mb-3">
                                <div class="col-8">
                                    <label class="form-label small">Host</label>
                                    <input type="text" class="form-control form-control-sm" name="backup_ftp_host"
                                           value="<?= eAttr($backupConfig['backup_ftp_host'] ?? '') ?>" placeholder="ftp.meuservidor.com">
                                </div>
                                <div class="col-4">
                                    <label class="form-label small">Diretório</label>
                                    <input type="text" class="form-control form-control-sm" name="backup_ftp_path"
                                           value="<?= eAttr($backupConfig['backup_ftp_path'] ?? '/backups/nfe/') ?>" placeholder="/backups/nfe/">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small">Usuário</label>
                                    <input type="text" class="form-control form-control-sm" name="backup_ftp_user"
                                           value="<?= eAttr($backupConfig['backup_ftp_user'] ?? '') ?>">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small">Senha</label>
                                    <input type="password" class="form-control form-control-sm" name="backup_ftp_password"
                                           value="<?= eAttr($backupConfig['backup_ftp_password'] ?? '') ?>">
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-dark btn-sm w-100">
                            <i class="fas fa-save me-1"></i> Salvar Configurações
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- ═══ Coluna: Histórico de Backups ═══ -->
        <div class="col-xl-7">
            <div class="card border-0 shadow-sm" style="border-radius:12px;overflow:hidden;">
                <div class="card-header py-2 bg-light">
                    <h6 class="mb-0" style="font-size:.85rem;">
                        <i class="fas fa-history me-2 text-primary"></i>Histórico de Backups
                        <span class="badge bg-primary ms-2"><?= count($backupHistory) ?></span>
                    </h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0" style="font-size:.8rem;">
                        <thead class="table-light">
                            <tr>
                                <th style="width:50px;">#</th>
                                <th style="width:70px;">Tipo</th>
                                <th style="width:130px;">Período</th>
                                <th class="text-center" style="width:70px;">Arquivos</th>
                                <th class="text-end" style="width:90px;">Tamanho</th>
                                <th style="width:80px;">Status</th>
                                <th style="width:130px;">Data</th>
                                <th>Destino</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($backupHistory)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    <i class="fas fa-cloud-upload-alt fa-3x mb-3 opacity-25"></i><br>
                                    <span class="fw-bold">Nenhum backup realizado ainda.</span><br>
                                    <small>Execute o primeiro backup usando o formulário ao lado.</small>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($backupHistory as $bk): ?>
                            <?php
                                $statusColors = ['sucesso' => 'success', 'erro' => 'danger', 'executando' => 'warning'];
                                $statusIcons = ['sucesso' => 'check-circle', 'erro' => 'times-circle', 'executando' => 'spinner fa-spin'];
                                $bkStatus = $bk['status'] ?? 'executando';
                                $tipoIcons = ['local' => 'hdd', 's3' => 'cloud', 'ftp' => 'server'];
                            ?>
                            <tr>
                                <td class="text-muted"><?= $bk['id'] ?></td>
                                <td>
                                    <span class="badge bg-light text-dark border" style="font-size:.65rem;">
                                        <i class="fas fa-<?= $tipoIcons[$bk['tipo'] ?? 'local'] ?? 'hdd' ?> me-1"></i>
                                        <?= strtoupper($bk['tipo'] ?? 'local') ?>
                                    </span>
                                </td>
                                <td>
                                    <small>
                                        <?= !empty($bk['periodo_inicio']) ? date('d/m/Y', strtotime($bk['periodo_inicio'])) : '' ?>
                                        —
                                        <?= !empty($bk['periodo_fim']) ? date('d/m/Y', strtotime($bk['periodo_fim'])) : '' ?>
                                    </small>
                                </td>
                                <td class="text-center fw-bold"><?= (int)($bk['total_arquivos'] ?? 0) ?></td>
                                <td class="text-end">
                                    <?php
                                        $bytes = (int)($bk['tamanho_bytes'] ?? 0);
                                        if ($bytes >= 1048576) echo number_format($bytes / 1048576, 1, ',', '.') . ' MB';
                                        elseif ($bytes >= 1024) echo number_format($bytes / 1024, 1, ',', '.') . ' KB';
                                        else echo $bytes . ' B';
                                    ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $statusColors[$bkStatus] ?? 'secondary' ?>" style="font-size:.65rem;">
                                        <i class="fas fa-<?= $statusIcons[$bkStatus] ?? 'circle' ?> me-1"></i>
                                        <?= ucfirst($bkStatus) ?>
                                    </span>
                                </td>
                                <td><small><?= !empty($bk['created_at']) ? date('d/m/Y H:i', strtotime($bk['created_at'])) : '—' ?></small></td>
                                <td>
                                    <small class="text-truncate d-block" style="max-width:200px;" title="<?= eAttr($bk['arquivo_destino'] ?? '') ?>">
                                        <?= e($bk['arquivo_destino'] ?? '—') ?>
                                    </small>
                                    <?php if (!empty($bk['mensagem_erro'])): ?>
                                    <small class="text-danger d-block" style="font-size:.65rem;">
                                        <i class="fas fa-exclamation-triangle me-1"></i><?= e($bk['mensagem_erro']) ?>
                                    </small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Info Card -->
            <div class="card border-0 shadow-sm mt-4" style="border-radius:12px;">
                <div class="card-body p-3 text-muted" style="font-size:.82rem;">
                    <h6 class="fw-bold small mb-2"><i class="fas fa-info-circle me-2 text-info"></i>Sobre o Backup de XMLs</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <p class="mb-1"><i class="fas fa-hdd text-primary me-2"></i><strong>Local:</strong></p>
                            <small>Gera arquivo ZIP no diretório <code>storage/backups/nfe/</code> do servidor.</small>
                        </div>
                        <div class="col-md-4">
                            <p class="mb-1"><i class="fab fa-aws text-warning me-2"></i><strong>Amazon S3:</strong></p>
                            <small>Envia para bucket S3 com alta disponibilidade e redundância.</small>
                        </div>
                        <div class="col-md-4">
                            <p class="mb-1"><i class="fas fa-server text-success me-2"></i><strong>FTP:</strong></p>
                            <small>Envia para servidor FTP externo para cópia de segurança off-site.</small>
                        </div>
                    </div>
                    <hr class="my-2">
                    <small class="text-muted">
                        <i class="fas fa-exclamation-triangle text-warning me-1"></i>
                        Mantenha backups regulares dos XMLs por no mínimo 5 anos, conforme legislação fiscal brasileira.
                    </small>
                </div>
            </div>
        </div>

    </div><!-- /row -->

<?php if (!$isAjax): ?>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function(){
    var csrfToken = $('meta[name="csrf-token"]').attr('content') || '<?= $_SESSION['csrf_token'] ?? '' ?>';

    // Mostrar/ocultar campos S3/FTP conforme seleção
    $('#cfgBackupTipo').on('change', function(){
        var v = $(this).val();
        $('#cfgS3').toggleClass('d-none', v !== 's3');
        $('#cfgFtp').toggleClass('d-none', v !== 'ftp');
    });

    // Executar backup manual
    $('#btnExecuteBackup').on('click', function(){
        var btn = $(this);
        var startDate = $('#backupStartDate').val();
        var endDate = $('#backupEndDate').val();
        var tipo = $('#backupTipo').val();

        if (!startDate || !endDate) {
            Swal.fire({icon:'warning', title:'Atenção', text:'Preencha o período.'});
            return;
        }

        Swal.fire({
            icon: 'question',
            title: 'Executar Backup?',
            html: 'Backup <strong>' + tipo.toUpperCase() + '</strong> de ' + startDate + ' a ' + endDate,
            showCancelButton: true,
            confirmButtonText: 'Executar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#1a73e8'
        }).then(function(result){
            if (!result.isConfirmed) return;

            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Executando...');
            $('#backupResult').hide();

            $.ajax({
                url: '?page=nfe_documents&action=backupXml',
                method: 'POST',
                dataType: 'json',
                data: { start_date: startDate, end_date: endDate, tipo: tipo },
                headers: {'X-CSRF-TOKEN': csrfToken}
            }).done(function(r){
                var cls = r.success ? 'success' : 'danger';
                var icon = r.success ? 'check-circle' : 'times-circle';
                var msg = r.message || (r.success ? 'Backup realizado!' : 'Erro ao executar backup.');
                if (r.total) msg += ' — ' + r.total + ' arquivo(s)';

                $('#backupResult').html(
                    '<div class="alert alert-' + cls + ' py-2 small mb-0">' +
                    '<i class="fas fa-' + icon + ' me-1"></i> ' + msg + '</div>'
                ).show();

                if (r.success) {
                    setTimeout(function(){ location.reload(); }, 2000);
                }
            }).fail(function(){
                $('#backupResult').html(
                    '<div class="alert alert-danger py-2 small mb-0"><i class="fas fa-times-circle me-1"></i> Falha na comunicação.</div>'
                ).show();
            }).always(function(){
                btn.prop('disabled', false).html('<i class="fas fa-cloud-upload-alt me-1"></i> Executar Backup');
            });
        });
    });
});
</script>
