<?php
/**
 * Qualidade — Dashboard
 * FEAT-017
 * Variáveis: $checklists, $nonConformities
 */
?>

<?php if (!empty($_SESSION['flash_success'])): ?>
<script nonce="<?= cspNonce() ?>">document.addEventListener('DOMContentLoaded',()=>{if(typeof AktiToast!=='undefined')AktiToast.success('<?= eJs($_SESSION['flash_success']) ?>');});</script>
<?php unset($_SESSION['flash_success']); endif; ?>

<div class="container-fluid py-3">

    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div>
            <h1 class="h2 mb-1"><i class="fas fa-clipboard-check me-2 text-primary"></i>Controle de Qualidade</h1>
            <p class="text-muted mb-0" style="font-size:.82rem;">Checklists, inspeções e não-conformidades.</p>
        </div>
        <div class="btn-toolbar gap-2">
            <a href="?page=quality&action=nonConformities" class="btn btn-sm btn-outline-danger"><i class="fas fa-exclamation-triangle me-1"></i>Não-Conformidades</a>
            <a href="?page=quality&action=create" class="btn btn-sm btn-primary"><i class="fas fa-plus me-1"></i>Novo Checklist</a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Checklists -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white"><h6 class="mb-0"><i class="fas fa-list-check me-2"></i>Checklists de Qualidade</h6></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nome</th>
                                    <th>Descrição</th>
                                    <th>Status</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($checklists)): ?>
                                <tr><td colspan="4" class="text-center text-muted py-4">Nenhum checklist.</td></tr>
                            <?php else: ?>
                                <?php foreach ($checklists as $cl): ?>
                                <tr>
                                    <td class="fw-bold"><?= e($cl['name']) ?></td>
                                    <td class="text-muted" style="font-size:.85rem;"><?= e(mb_substr($cl['description'] ?? '', 0, 60)) ?></td>
                                    <td>
                                        <?php if ($cl['is_active']): ?>
                                        <span class="badge bg-success">Ativo</span>
                                        <?php else: ?>
                                        <span class="badge bg-secondary">Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="?page=quality&action=edit&id=<?= (int) $cl['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                                        <button class="btn btn-sm btn-outline-danger btnDelete" data-id="<?= (int) $cl['id'] ?>"><i class="fas fa-trash"></i></button>
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

        <!-- Não-conformidades abertas -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm border-start border-danger border-4">
                <div class="card-header bg-white"><h6 class="mb-0 text-danger"><i class="fas fa-exclamation-triangle me-2"></i>NC Abertas</h6></div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                    <?php if (empty($nonConformities)): ?>
                        <div class="list-group-item text-muted text-center py-3">Nenhuma NC aberta.</div>
                    <?php else: ?>
                        <?php foreach (array_slice($nonConformities, 0, 10) as $nc): ?>
                        <div class="list-group-item">
                            <div class="fw-bold" style="font-size:.85rem;"><?= e($nc['title']) ?></div>
                            <small class="text-muted"><?= e($nc['severity'] ?? 'medium') ?> | <?= date('d/m/Y', strtotime($nc['created_at'])) ?></small>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btnDelete').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            Swal.fire({ title: 'Excluir checklist?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Sim', cancelButtonText: 'Não' })
            .then(r => { if (r.isConfirmed) window.location.href = '?page=quality&action=delete&id=' + id; });
        });
    });
});
</script>
