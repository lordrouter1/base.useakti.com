<?php
/**
 * Remessas/Entregas — Listagem
 * Variáveis: $shipments, $pagination
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
            <h1 class="h2 mb-1"><i class="fas fa-shipping-fast me-2 text-primary"></i>Entregas & Rastreamento</h1>
            <p class="text-muted mb-0" style="font-size:.82rem;">Gestão de remessas e rastreamento de entregas.</p>
        </div>
        <div class="btn-toolbar gap-2">
            <a href="?page=shipments&action=carriers" class="btn btn-sm btn-outline-info"><i class="fas fa-truck me-1"></i>Transportadoras</a>
            <a href="?page=shipments&action=dashboard" class="btn btn-sm btn-outline-info"><i class="fas fa-chart-pie me-1"></i>Dashboard</a>
            <a href="?page=shipments&action=create" class="btn btn-sm btn-primary"><i class="fas fa-plus me-1"></i>Nova Remessa</a>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="get" class="row g-2 align-items-end">
                <input type="hidden" name="page" value="shipments">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Buscar por código de rastreio..." value="<?= eAttr($search) ?>">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach (['preparing' => 'Preparando', 'shipped' => 'Enviado', 'in_transit' => 'Em Trânsito', 'delivered' => 'Entregue', 'returned' => 'Devolvido'] as $k => $v): ?>
                        <option value="<?= $k ?>" <?= $statusFilter === $k ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <button class="btn btn-sm btn-outline-primary"><i class="fas fa-search me-1"></i>Filtrar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>#</th><th>Pedido</th><th>Rastreio</th><th>Transportadora</th><th>Status</th><th>Previsão</th><th class="text-end">Ações</th></tr>
                    </thead>
                    <tbody>
                    <?php if (empty($shipments)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">Nenhuma remessa encontrada.</td></tr>
                    <?php else: ?>
                        <?php foreach ($shipments as $s): ?>
                        <tr>
                            <td><?= (int) $s['id'] ?></td>
                            <td>#<?= (int) $s['order_id'] ?></td>
                            <td><code><?= e($s['tracking_code'] ?? '-') ?></code></td>
                            <td><?= e($s['carrier_name'] ?? '-') ?></td>
                            <td>
                                <?php
                                $shColors = ['preparing' => 'warning', 'shipped' => 'primary', 'in_transit' => 'info', 'delivered' => 'success', 'returned' => 'danger'];
                                $shLabels = ['preparing' => 'Preparando', 'shipped' => 'Enviado', 'in_transit' => 'Em Trânsito', 'delivered' => 'Entregue', 'returned' => 'Devolvido'];
                                ?>
                                <span class="badge bg-<?= $shColors[$s['status']] ?? 'secondary' ?>"><?= $shLabels[$s['status']] ?? $s['status'] ?></span>
                            </td>
                            <td><?= !empty($s['estimated_date']) ? e(date('d/m/Y', strtotime($s['estimated_date']))) : '-' ?></td>
                            <td class="text-end">
                                <a href="?page=shipments&action=view&id=<?= (int) $s['id'] ?>" class="btn btn-sm btn-outline-primary" title="Ver"><i class="fas fa-eye"></i></a>
                                <button class="btn btn-sm btn-outline-danger btnDelete" data-id="<?= (int) $s['id'] ?>" title="Excluir"><i class="fas fa-trash"></i></button>
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
                <a class="page-link" href="?page=shipments&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&p=<?= $p ?>"><?= $p ?></a>
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
            Swal.fire({ title:'Excluir remessa?', text:'Esta ação não pode ser desfeita.', icon:'warning', showCancelButton:true, confirmButtonColor:'#d33', confirmButtonText:'Sim, excluir', cancelButtonText:'Cancelar' }).then(r => { if(r.isConfirmed) window.location.href='?page=shipments&action=delete&id='+id; });
        });
    });
});
</script>
