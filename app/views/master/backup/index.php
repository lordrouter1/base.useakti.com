<?php
/**
 * View: Backup
 * Gerenciamento de backups do servidor
 */
$pageTitle = 'Backups';
$pageSubtitle = 'Gerenciamento de backups do servidor';
$topbarActions = '
    <button class="btn btn-akti" id="btnRunBackup" title="Executar backup agora">
        <i class="fas fa-play me-2"></i>Executar Backup
    </button>
';

$pageScripts = <<<'ENDSCRIPTS'
<script>
$(document).ready(function() {

    // Executar Backup
    $('#btnRunBackup').on('click', function() {
        Swal.fire({
            icon: 'question',
            title: 'Executar Backup?',
            html: 'O comando <code>sudo /bin/bkp</code> sera executado no servidor.<br><small class="text-muted">Isso pode levar alguns minutos.</small>',
            showCancelButton: true,
            confirmButtonColor: '#4f46e5',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-play me-1"></i>Executar',
            cancelButtonText: 'Cancelar'
        }).then(function(result) {
            if (!result.isConfirmed) return;

            Swal.fire({
                title: 'Executando backup...',
                html: '<i class="fas fa-spinner fa-spin fa-2x"></i><br><small class="text-muted mt-2">Aguarde...</small>',
                showConfirmButton: false,
                allowOutsideClick: false
            });

            $.post('?page=master_backup&action=run', function(data) {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Backup concluido!',
                        html: '<pre style="text-align:left;font-size:11px;max-height:200px;overflow:auto;background:#f1f5f9;padding:12px;border-radius:6px;">' + escapeHtml(data.output || 'OK') + '</pre>',
                        confirmButtonColor: '#4f46e5'
                    }).then(function() { location.reload(); });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro no backup',
                        html: '<pre style="text-align:left;font-size:11px;max-height:200px;overflow:auto;">' + escapeHtml(data.output || 'Erro desconhecido') + '</pre>',
                        confirmButtonColor: '#4f46e5'
                    });
                }
            }, 'json').fail(function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro de conexao',
                    text: 'Timeout ou erro de rede.',
                    confirmButtonColor: '#4f46e5'
                });
            });
        });
    });

    // Excluir Backup
    $(document).on('click', '.btn-delete-backup', function(e) {
        e.preventDefault();
        var filename = $(this).data('file');
        var filesize = $(this).data('size');

        Swal.fire({
            icon: 'warning',
            title: 'Excluir Backup',
            html:
                '<div style="text-align:left;">' +
                '<p>Voce esta prestes a excluir permanentemente:</p>' +
                '<div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:12px;margin-bottom:16px;">' +
                '<strong style="color:#991b1b;"><i class="fas fa-file-archive me-1"></i>' + escapeHtml(filename) + '</strong>' +
                '<br><small class="text-muted">' + escapeHtml(filesize) + '</small>' +
                '</div>' +
                '<div class="mb-3">' +
                '<label class="form-label" style="font-size:13px;font-weight:600;">Digite o nome do arquivo para confirmar:</label>' +
                '<input type="text" id="swalConfirmName" class="form-control form-control-sm" placeholder="' + escapeHtml(filename) + '" autocomplete="off" style="font-size:12px;">' +
                '</div>' +
                '<div class="mb-2">' +
                '<label class="form-label" style="font-size:13px;font-weight:600;">Sua senha de administrador:</label>' +
                '<input type="password" id="swalConfirmPass" class="form-control form-control-sm" placeholder="Senha" autocomplete="off" style="font-size:12px;">' +
                '</div>' +
                '</div>',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-trash me-1"></i>Excluir Permanentemente',
            cancelButtonText: 'Cancelar',
            focusCancel: true,
            preConfirm: function() {
                var name = document.getElementById('swalConfirmName').value.trim();
                var pass = document.getElementById('swalConfirmPass').value;
                if (!name || !pass) {
                    Swal.showValidationMessage('Preencha o nome do arquivo e a senha.');
                    return false;
                }
                if (name !== filename) {
                    Swal.showValidationMessage('O nome digitado nao confere com o arquivo.');
                    return false;
                }
                return { name: name, pass: pass };
            }
        }).then(function(result) {
            if (!result.isConfirmed) return;

            Swal.fire({
                title: 'Excluindo...',
                html: '<i class="fas fa-spinner fa-spin fa-2x"></i>',
                showConfirmButton: false,
                allowOutsideClick: false
            });

            $.ajax({
                url: '?page=master_backup&action=delete',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    filename: filename,
                    confirm_name: result.value.name,
                    password: result.value.pass
                }),
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Excluido!',
                            text: data.message || 'Arquivo excluido com sucesso.',
                            confirmButtonColor: '#4f46e5'
                        }).then(function() { location.reload(); });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro',
                            text: data.error || 'Nao foi possivel excluir o arquivo.',
                            confirmButtonColor: '#4f46e5'
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro de conexao',
                        text: 'Nao foi possivel comunicar com o servidor.',
                        confirmButtonColor: '#4f46e5'
                    });
                }
            });
        });
    });

    // Filtro de busca
    $('#searchFiles').on('keyup', function() {
        var q = $(this).val().toLowerCase();
        $('.backup-row').each(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(q) !== -1);
        });
    });

    function escapeHtml(text) {
        if (!text) return '';
        return $('<div>').text(text).html();
    }
});
</script>
ENDSCRIPTS;

?>

<?php if (!empty($isTestEnv)): ?>
<div class="card mb-4 border-info">
    <div class="card-body text-center py-5">
        <i class="fas fa-flask fa-4x mb-3" style="color:#6366f1; opacity:0.5;"></i>
        <h5 class="text-muted">Ambiente de Testes</h5>
        <p class="text-muted mb-0">O sistema está rodando em uma base de testes.<br>O módulo de backups não está disponível neste ambiente.</p>
    </div>
</div>
<?php else: ?>

<?php if (!empty($diagnostic['issues'])): ?>
<div class="card mb-4 border-warning">
    <div class="card-header d-flex align-items-center gap-2" style="background: linear-gradient(135deg, #fef3c7, #fde68a); color:#92400e;">
        <i class="fas fa-triangle-exclamation"></i>
        <strong>Diagnostico</strong>
        <span class="badge bg-warning text-dark ms-auto"><?= count($diagnostic['issues']) ?> problema(s)</span>
    </div>
    <div class="card-body">
        <?php foreach ($diagnostic['issues'] as $i => $issue): ?>
        <div class="alert alert-danger py-2 mb-2" style="font-size:13px;">
            <i class="fas fa-times-circle me-2"></i><strong>Problema:</strong> <?= htmlspecialchars($issue) ?>
            <?php if (isset($diagnostic['fixes'][$i])): ?>
                <br><i class="fas fa-wrench me-2 mt-1"></i><strong>Correcao:</strong>
                <code style="background:#1e1e1e; color:#d4d4d4; padding:4px 10px; border-radius:4px; display:inline-block; margin-top:4px; font-size:12px;"><?= htmlspecialchars($diagnostic['fixes'][$i]) ?></code>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Stats -->
<div class="row g-4 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="stat-card" style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
            <div class="stat-icon"><i class="fas fa-file-archive"></i></div>
            <div class="stat-value"><?= $totalFiles ?></div>
            <div class="stat-label">Arquivos de Backup</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">
            <div class="stat-icon"><i class="fas fa-hard-drive"></i></div>
            <div class="stat-value"><?= $totalSizeHuman ?></div>
            <div class="stat-label">Espaco Total</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card" style="background: linear-gradient(135deg, #10b981, #059669);">
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
            <div class="stat-value" style="font-size:16px;"><?= htmlspecialchars($lastBackup) ?></div>
            <div class="stat-label">Ultimo Backup</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
            <div class="stat-icon"><i class="fas fa-folder-open"></i></div>
            <div class="stat-value" style="font-size:14px;"><?= htmlspecialchars($backupPath) ?></div>
            <div class="stat-label">Pasta de Backups</div>
        </div>
    </div>
</div>

<!-- Filtro -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="input-group">
            <span class="input-group-text" style="background:#f8f9fa; border:2px solid #dee2e6; border-right:none; border-radius:8px 0 0 8px;">
                <i class="fas fa-search text-muted"></i>
            </span>
            <input type="text" class="form-control" id="searchFiles" placeholder="Buscar arquivo..."
                   style="border:2px solid #dee2e6; border-left:none; border-radius:0 8px 8px 0; padding:10px 14px;">
        </div>
    </div>
</div>

<?php
function bkpTimeAgo($timestamp) {
    $diff = time() - $timestamp;
    if ($diff < 60) return 'agora mesmo';
    if ($diff < 3600) return floor($diff / 60) . ' min atras';
    if ($diff < 86400) return floor($diff / 3600) . 'h atras';
    if ($diff < 604800) return floor($diff / 86400) . ' dia(s) atras';
    return floor($diff / 604800) . ' semana(s) atras';
}
?>

<?php if ($listError): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-folder-open fa-4x mb-3 opacity-25"></i>
            <h5 class="text-muted">Nao foi possivel listar os backups</h5>
            <p class="text-muted"><?= htmlspecialchars($listError) ?></p>
        </div>
    </div>
<?php elseif (empty($files)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-file-archive fa-4x mb-3 opacity-25"></i>
            <h5 class="text-muted">Nenhum backup encontrado</h5>
            <p class="text-muted">Clique em "Executar Backup" para criar o primeiro backup.</p>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead style="background:#f8f9fa;">
                    <tr>
                        <th style="padding:12px 16px;"><i class="fas fa-file me-1"></i>Arquivo</th>
                        <th style="padding:12px 16px;"><i class="fas fa-weight-hanging me-1"></i>Tamanho</th>
                        <th style="padding:12px 16px;"><i class="fas fa-calendar me-1"></i>Data</th>
                        <th style="padding:12px 16px;"><i class="fas fa-tag me-1"></i>Tipo</th>
                        <th style="padding:12px 16px; text-align:center;"><i class="fas fa-cog me-1"></i>Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($files as $file):
                        $ext = strtolower($file['extension']);
                        $icon = 'fa-file';
                        $iconColor = '#6b7280';
                        if (in_array($ext, ['gz', 'tar', 'tgz', 'bz2', 'xz'])) {
                            $icon = 'fa-file-zipper'; $iconColor = '#f59e0b';
                        } elseif (in_array($ext, ['zip', 'rar', '7z'])) {
                            $icon = 'fa-file-zipper'; $iconColor = '#8b5cf6';
                        } elseif ($ext === 'sql') {
                            $icon = 'fa-database'; $iconColor = '#3b82f6';
                        } elseif ($ext === 'log' || $ext === 'txt') {
                            $icon = 'fa-file-lines'; $iconColor = '#6b7280';
                        }

                        $dt = new DateTime($file['modified']);
                        $dateFormatted = $dt->format('d/m/Y H:i:s');
                        $timeAgo = bkpTimeAgo($file['modified_ts']);
                    ?>
                    <tr class="backup-row">
                        <td style="padding:12px 16px;">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas <?= $icon ?>" style="color:<?= $iconColor ?>; font-size:18px;"></i>
                                <div>
                                    <strong style="font-size:13px;"><?= htmlspecialchars($file['name']) ?></strong>
                                </div>
                            </div>
                        </td>
                        <td style="padding:12px 16px;">
                            <span class="fw-semibold"><?= htmlspecialchars($file['size_human']) ?></span>
                        </td>
                        <td style="padding:12px 16px;">
                            <div><?= $dateFormatted ?></div>
                            <small class="text-muted"><?= $timeAgo ?></small>
                        </td>
                        <td style="padding:12px 16px;">
                            <span class="badge" style="background:#f0f0f0; color:#333; font-size:11px;">.<?= htmlspecialchars($ext ?: '-') ?></span>
                        </td>
                        <td style="padding:12px 16px; text-align:center;">
                            <div class="d-flex align-items-center justify-content-center gap-1">
                                <?php if ($file['downloadable']): ?>
                                    <a href="?page=master_backup&action=download&file=<?= urlencode($file['name']) ?>"
                                       class="btn btn-sm btn-outline-primary" title="Download">
                                        <i class="fas fa-download"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="btn btn-sm btn-outline-secondary disabled" title="Sem permissao"><i class="fas fa-lock"></i></span>
                                <?php endif; ?>
                                <button type="button"
                                        class="btn btn-sm btn-outline-danger btn-delete-backup"
                                        data-file="<?= htmlspecialchars($file['name']) ?>"
                                        data-size="<?= htmlspecialchars($file['size_human']) ?>"
                                        title="Excluir backup">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php endif; /* isTestEnv */ ?>
