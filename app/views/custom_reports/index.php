<?php
/**
 * Relatórios Customizados — Listagem
 * FEAT-008
 * Variáveis: $templates, $entities
 */
?>

<?php if (!empty($_SESSION['flash_success'])): ?>
<script nonce="<?= cspNonce() ?>">document.addEventListener('DOMContentLoaded',()=>{if(typeof AktiToast!=='undefined')AktiToast.success('<?= eJs($_SESSION['flash_success']) ?>');});</script>
<?php unset($_SESSION['flash_success']); endif; ?>

<div class="container-fluid py-3">

    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div>
            <h1 class="h2 mb-1"><i class="fas fa-chart-bar me-2 text-primary"></i>Relatórios Customizados</h1>
            <p class="text-muted mb-0" style="font-size:.82rem;">Crie e execute relatórios personalizados.</p>
        </div>
        <a href="?page=custom_reports&action=create" class="btn btn-sm btn-primary"><i class="fas fa-plus me-1"></i>Novo Relatório</a>
    </div>

    <div class="row g-3">
        <?php if (empty($templates)): ?>
        <div class="col-12">
            <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Nenhum relatório salvo. Clique em "Novo Relatório" para começar.</div>
        </div>
        <?php else: ?>
            <?php foreach ($templates as $t): ?>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="card-title"><?= e($t['name']) ?></h6>
                        <p class="card-text text-muted" style="font-size:.8rem;">
                            Entidade: <span class="badge bg-light text-dark"><?= e($t['entity']) ?></span>
                            <?php if (!empty($t['is_shared'])): ?>
                            <span class="badge bg-info ms-1">Compartilhado</span>
                            <?php endif; ?>
                        </p>
                        <small class="text-muted">Criado em <?= date('d/m/Y', strtotime($t['created_at'])) ?></small>
                    </div>
                    <div class="card-footer bg-white border-top-0">
                        <a href="?page=custom_reports&action=run&id=<?= (int) $t['id'] ?>" class="btn btn-sm btn-primary"><i class="fas fa-play me-1"></i>Executar</a>
                        <a href="?page=custom_reports&action=edit&id=<?= (int) $t['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-edit"></i></a>
                        <button class="btn btn-sm btn-outline-danger btnDelete" data-id="<?= (int) $t['id'] ?>"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btnDelete').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            Swal.fire({
                title: 'Excluir relatório?', icon: 'warning', showCancelButton: true,
                confirmButtonColor: '#d33', confirmButtonText: 'Sim', cancelButtonText: 'Não'
            }).then(r => { if (r.isConfirmed) window.location.href = '?page=custom_reports&action=delete&id=' + id; });
        });
    });
});
</script>
