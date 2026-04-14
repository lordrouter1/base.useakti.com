<?php
/**
 * ESG — Métricas e Dashboard
 * Variáveis: $metrics, $summary
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
            <h1 class="h2 mb-1"><i class="fas fa-leaf me-2 text-success"></i>ESG — Sustentabilidade</h1>
            <p class="text-muted mb-0" style="font-size:.82rem;">Métricas ambientais, sociais e de governança.</p>
        </div>
        <div class="btn-toolbar gap-2">
            <a href="?page=esg&action=dashboard" class="btn btn-sm btn-outline-success"><i class="fas fa-chart-pie me-1"></i>Dashboard</a>
            <a href="?page=esg&action=create" class="btn btn-sm btn-primary"><i class="fas fa-plus me-1"></i>Nova Métrica</a>
        </div>
    </div>

    <!-- Resumo por categoria -->
    <?php if (!empty($summary)): ?>
    <div class="row g-3 mb-4">
        <?php
        $catIcons = ['environmental' => 'fas fa-tree', 'social' => 'fas fa-users', 'governance' => 'fas fa-balance-scale'];
        $catColors = ['environmental' => 'success', 'social' => 'info', 'governance' => 'primary'];
        $catLabels = ['environmental' => 'Ambiental', 'social' => 'Social', 'governance' => 'Governança'];
        foreach ($summary as $s): ?>
        <div class="col-sm-6 col-lg-4">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body py-3">
                    <i class="<?= $catIcons[$s['category']] ?? 'fas fa-chart-bar' ?> fa-2x text-<?= $catColors[$s['category']] ?? 'secondary' ?> mb-2"></i>
                    <h5 class="mb-0"><?= $catLabels[$s['category']] ?? e($s['category']) ?></h5>
                    <p class="text-muted mb-0 small"><?= (int) $s['record_count'] ?> registros (12 meses)</p>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>#</th><th>Métrica</th><th>Categoria</th><th>Unidade</th><th>Status</th><th class="text-end">Ações</th></tr>
                    </thead>
                    <tbody>
                    <?php if (empty($metrics)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">Nenhuma métrica ESG cadastrada.</td></tr>
                    <?php else: ?>
                        <?php foreach ($metrics as $m): ?>
                        <tr>
                            <td><?= (int) $m['id'] ?></td>
                            <td><?= e($m['name']) ?></td>
                            <td><span class="badge bg-<?= $catColors[$m['category']] ?? 'secondary' ?>"><?= $catLabels[$m['category']] ?? e($m['category']) ?></span></td>
                            <td><?= e($m['unit'] ?? '-') ?></td>
                            <td><?= $m['is_active'] ? '<span class="badge bg-success">Ativa</span>' : '<span class="badge bg-secondary">Inativa</span>' ?></td>
                            <td class="text-end">
                                <a href="?page=esg&action=edit&id=<?= (int) $m['id'] ?>" class="btn btn-sm btn-outline-primary" title="Editar"><i class="fas fa-edit"></i></a>
                                <button class="btn btn-sm btn-outline-danger btnDelete" data-id="<?= (int) $m['id'] ?>" title="Excluir"><i class="fas fa-trash"></i></button>
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
            Swal.fire({ title:'Excluir métrica ESG?', icon:'warning', showCancelButton:true, confirmButtonColor:'#d33', confirmButtonText:'Sim, excluir', cancelButtonText:'Cancelar' }).then(r => { if(r.isConfirmed) window.location.href='?page=esg&action=delete&id='+id; });
        });
    });
});
</script>
