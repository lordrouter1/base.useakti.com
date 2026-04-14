<?php
/**
 * Equipamentos — Listagem
 * Variáveis: $equipments, $pagination
 */
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';
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
            <h1 class="h2 mb-1"><i class="fas fa-tools me-2 text-primary"></i>Equipamentos</h1>
            <p class="text-muted mb-0" style="font-size:.82rem;">Cadastro e manutenção preventiva de equipamentos.</p>
        </div>
        <div class="btn-toolbar gap-2">
            <a href="?page=equipment&action=dashboard" class="btn btn-sm btn-outline-info"><i class="fas fa-chart-pie me-1"></i>Dashboard</a>
            <a href="?page=equipment&action=create" class="btn btn-sm btn-primary"><i class="fas fa-plus me-1"></i>Novo Equipamento</a>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="get" class="row g-2 align-items-end">
                <input type="hidden" name="page" value="equipment">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Buscar por nome, código ou nº série..." value="<?= eAttr($search) ?>">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Todos os status</option>
                        <?php foreach (['active' => 'Ativo', 'maintenance' => 'Em Manutenção', 'inactive' => 'Inativo', 'decommissioned' => 'Descomissionado'] as $k => $v): ?>
                        <option value="<?= $k ?>" <?= $statusFilter === $k ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <button class="btn btn-sm btn-outline-primary"><i class="fas fa-search me-1"></i>Buscar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Nome</th>
                            <th>Código</th>
                            <th>Fabricante</th>
                            <th>Local</th>
                            <th>Status</th>
                            <th>Última Manutenção</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($equipments)): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">Nenhum equipamento encontrado.</td></tr>
                    <?php else: ?>
                        <?php foreach ($equipments as $eq): ?>
                        <tr>
                            <td><?= (int) $eq['id'] ?></td>
                            <td><?= e($eq['name']) ?></td>
                            <td><?= e($eq['code'] ?? '-') ?></td>
                            <td><?= e($eq['manufacturer'] ?? '-') ?></td>
                            <td><?= e($eq['location'] ?? '-') ?></td>
                            <td>
                                <?php
                                $eqStatusColors = ['active' => 'success', 'maintenance' => 'warning', 'inactive' => 'secondary', 'decommissioned' => 'dark'];
                                $eqStatusLabels = ['active' => 'Ativo', 'maintenance' => 'Manutenção', 'inactive' => 'Inativo', 'decommissioned' => 'Descomissionado'];
                                ?>
                                <span class="badge bg-<?= $eqStatusColors[$eq['status']] ?? 'secondary' ?>"><?= $eqStatusLabels[$eq['status']] ?? $eq['status'] ?></span>
                            </td>
                            <td><?= !empty($eq['last_maintenance_at']) ? e(date('d/m/Y', strtotime($eq['last_maintenance_at']))) : '-' ?></td>
                            <td class="text-end">
                                <a href="?page=equipment&action=edit&id=<?= (int) $eq['id'] ?>" class="btn btn-sm btn-outline-primary" title="Editar"><i class="fas fa-edit"></i></a>
                                <a href="?page=equipment&action=schedules&id=<?= (int) $eq['id'] ?>" class="btn btn-sm btn-outline-info" title="Agendamentos"><i class="fas fa-calendar-check"></i></a>
                                <button class="btn btn-sm btn-outline-danger btnDelete" data-id="<?= (int) $eq['id'] ?>" title="Excluir"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php if (!empty($pagination) && ($pagination['pages'] ?? 1) > 1): ?>
    <nav class="mt-3">
        <ul class="pagination pagination-sm justify-content-center">
            <?php for ($p = 1; $p <= $pagination['pages']; $p++): ?>
            <li class="page-item <?= $p == ($pagination['current_page'] ?? 1) ? 'active' : '' ?>">
                <a class="page-link" href="?page=equipment&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&p=<?= $p ?>"><?= $p ?></a>
            </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<script nonce="<?= cspNonce() ?>">
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btnDelete').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            Swal.fire({ title:'Excluir equipamento?', text:'Esta ação não pode ser desfeita.', icon:'warning', showCancelButton:true, confirmButtonColor:'#d33', confirmButtonText:'Sim, excluir', cancelButtonText:'Cancelar' }).then(r => { if(r.isConfirmed) window.location.href='?page=equipment&action=delete&id='+id; });
        });
    });
});
</script>
