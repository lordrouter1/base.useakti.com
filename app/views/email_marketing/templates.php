<?php
/**
 * E-mail Marketing — Templates
 * FEAT-013
 * Variáveis: $templates
 */
?>

<?php if (!empty($_SESSION['flash_success'])): ?>
<script nonce="<?= cspNonce() ?>">document.addEventListener('DOMContentLoaded',()=>{if(typeof AktiToast!=='undefined')AktiToast.success('<?= eJs($_SESSION['flash_success']) ?>');});</script>
<?php unset($_SESSION['flash_success']); endif; ?>

<div class="container-fluid py-3">

    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div>
            <h1 class="h2 mb-1"><i class="fas fa-file-alt me-2 text-primary"></i>Templates de E-mail</h1>
        </div>
        <div class="btn-toolbar gap-2">
            <a href="?page=email_marketing" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Campanhas</a>
            <a href="?page=email_marketing&action=createTemplate" class="btn btn-sm btn-primary"><i class="fas fa-plus me-1"></i>Novo Template</a>
        </div>
    </div>

    <div class="row g-3">
        <?php if (empty($templates)): ?>
        <div class="col-12"><div class="alert alert-info">Nenhum template cadastrado.</div></div>
        <?php else: ?>
            <?php foreach ($templates as $t): ?>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h6><?= e($t['name']) ?></h6>
                        <p class="text-muted small mb-1">Assunto: <?= e($t['subject'] ?? '-') ?></p>
                        <small class="text-muted">Criado em <?= date('d/m/Y', strtotime($t['created_at'])) ?></small>
                    </div>
                    <div class="card-footer bg-transparent border-0 pt-0 d-flex gap-2">
                        <button class="btn btn-sm btn-outline-info btnPreviewTpl" data-id="<?= (int) $t['id'] ?>" title="Visualizar"><i class="fas fa-eye me-1"></i>Preview</button>
                        <a href="?page=email_marketing&action=editTemplate&id=<?= (int) $t['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit me-1"></i>Editar</a>
                        <button class="btn btn-sm btn-outline-danger btnDeleteTpl" data-id="<?= (int) $t['id'] ?>"><i class="fas fa-trash me-1"></i>Excluir</button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Preview -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-eye me-1"></i>Preview do Template</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <iframe id="previewFrame" style="width:100%;height:500px;border:none;"></iframe>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Preview
    document.querySelectorAll('.btnPreviewTpl').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const frame = document.getElementById('previewFrame');
            frame.src = '?page=email_marketing&action=previewTemplate&id=' + id;
            const modal = new bootstrap.Modal(document.getElementById('previewModal'));
            modal.show();
        });
    });

    // Delete
    document.querySelectorAll('.btnDeleteTpl').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            Swal.fire({
                title: 'Excluir template?', text: 'Esta ação não pode ser desfeita.',
                icon: 'warning', showCancelButton: true,
                confirmButtonColor: '#d33', confirmButtonText: 'Sim, excluir', cancelButtonText: 'Cancelar'
            }).then(r => { if (r.isConfirmed) window.location.href = '?page=email_marketing&action=deleteTemplate&id=' + id; });
        });
    });
});
</script>
