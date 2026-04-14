<?php
/**
 * Filiais — Listagem
 * Variáveis: $branches
 */
?>

<?php if (!empty($_SESSION['flash_success'])): ?>
<script nonce="<?= cspNonce() ?>">document.addEventListener('DOMContentLoaded',()=>{if(typeof AktiToast!=='undefined')AktiToast.success('<?= eJs($_SESSION['flash_success']) ?>');});</script>
<?php unset($_SESSION['flash_success']); endif; ?>
<?php if (!empty($_SESSION['flash_error'])): ?>
<script nonce="<?= cspNonce() ?>">document.addEventListener('DOMContentLoaded',()=>{if(typeof AktiToast!=='undefined')AktiToast.error('<?= eJs($_SESSION['flash_error']) ?>');});</script>
<?php unset($_SESSION['flash_error']); endif; ?>

<div class="container-fluid py-3">
    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div>
            <h1 class="h2 mb-1"><i class="fas fa-building me-2 text-primary"></i>Filiais</h1>
            <p class="text-muted mb-0" style="font-size:.82rem;">Gestão de filiais e unidades.</p>
        </div>
        <a href="?page=branches&action=create" class="btn btn-sm btn-primary"><i class="fas fa-plus me-1"></i>Nova Filial</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>#</th><th>Nome</th><th>Código</th><th>Cidade/UF</th><th>Telefone</th><th>Tipo</th><th>Status</th><th class="text-end">Ações</th></tr>
                    </thead>
                    <tbody>
                    <?php if (empty($branches)): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">Nenhuma filial cadastrada.</td></tr>
                    <?php else: ?>
                        <?php foreach ($branches as $b): ?>
                        <tr>
                            <td><?= (int) $b['id'] ?></td>
                            <td><?= e($b['name']) ?></td>
                            <td><?= e($b['code'] ?? '-') ?></td>
                            <td><?= e(($b['city'] ?? '') . ($b['state'] ? '/' . $b['state'] : '')) ?></td>
                            <td><?= e($b['phone'] ?? '-') ?></td>
                            <td><?= !empty($b['is_headquarters']) ? '<span class="badge bg-primary">Matriz</span>' : '<span class="badge bg-light text-dark">Filial</span>' ?></td>
                            <td><?= !empty($b['is_active']) ? '<span class="badge bg-success">Ativa</span>' : '<span class="badge bg-secondary">Inativa</span>' ?></td>
                            <td class="text-end">
                                <a href="?page=branches&action=edit&id=<?= (int) $b['id'] ?>" class="btn btn-sm btn-outline-primary" title="Editar"><i class="fas fa-edit"></i></a>
                                <button class="btn btn-sm btn-outline-danger btnDelete" data-id="<?= (int) $b['id'] ?>" title="Excluir"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script nonce="<?= cspNonce() ?>">
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btnDelete').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            Swal.fire({ title:'Excluir filial?', text:'Esta ação não pode ser desfeita.', icon:'warning', showCancelButton:true, confirmButtonColor:'#d33', confirmButtonText:'Sim, excluir', cancelButtonText:'Cancelar' }).then(r => { if(r.isConfirmed) window.location.href='?page=branches&action=delete&id='+id; });
        });
    });
});
</script>
