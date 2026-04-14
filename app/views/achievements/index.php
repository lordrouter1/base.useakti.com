<?php
/**
 * Conquistas/Gamificação — Listagem
 * Variáveis: $achievements
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
            <h1 class="h2 mb-1"><i class="fas fa-trophy me-2 text-warning"></i>Gamificação</h1>
            <p class="text-muted mb-0" style="font-size:.82rem;">Conquistas, pontuação e ranking da equipe.</p>
        </div>
        <div class="btn-toolbar gap-2">
            <a href="?page=achievements&action=leaderboard" class="btn btn-sm btn-outline-warning"><i class="fas fa-medal me-1"></i>Ranking</a>
            <a href="?page=achievements&action=create" class="btn btn-sm btn-primary"><i class="fas fa-plus me-1"></i>Nova Conquista</a>
        </div>
    </div>

    <div class="row g-3">
        <?php if (empty($achievements)): ?>
            <div class="col-12"><div class="alert alert-info">Nenhuma conquista cadastrada.</div></div>
        <?php else: ?>
            <?php foreach ($achievements as $a): ?>
            <div class="col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100 text-center">
                    <div class="card-body">
                        <i class="<?= e($a['icon'] ?? 'fas fa-trophy') ?> fa-3x text-warning mb-3"></i>
                        <h5 class="card-title"><?= e($a['name']) ?></h5>
                        <p class="card-text small text-muted"><?= e($a['description'] ?? '') ?></p>
                        <span class="badge bg-primary"><?= (int) $a['points'] ?> pts</span>
                        <span class="badge bg-light text-dark"><?= e($a['category'] ?? '') ?></span>
                    </div>
                    <div class="card-footer bg-white border-0">
                        <a href="?page=achievements&action=edit&id=<?= (int) $a['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                        <button class="btn btn-sm btn-outline-danger btnDelete" data-id="<?= (int) $a['id'] ?>"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script nonce="<?= cspNonce() ?>">
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btnDelete').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            Swal.fire({ title:'Excluir conquista?', icon:'warning', showCancelButton:true, confirmButtonColor:'#d33', confirmButtonText:'Sim, excluir', cancelButtonText:'Cancelar' }).then(r => { if(r.isConfirmed) window.location.href='?page=achievements&action=delete&id='+id; });
        });
    });
});
</script>
