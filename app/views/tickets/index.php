<?php
/**
 * Tickets — Listagem
 * Variáveis: $tickets, $pagination, $categories
 */
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$priorityFilter = $_GET['priority'] ?? '';
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
            <h1 class="h2 mb-1"><i class="fas fa-headset me-2 text-primary"></i>Tickets de Suporte</h1>
            <p class="text-muted mb-0" style="font-size:.82rem;">Central de chamados e atendimento.</p>
        </div>
        <div class="btn-toolbar gap-2">
            <a href="?page=tickets&action=dashboard" class="btn btn-sm btn-outline-info"><i class="fas fa-chart-pie me-1"></i>Dashboard</a>
            <a href="?page=tickets&action=create" class="btn btn-sm btn-primary"><i class="fas fa-plus me-1"></i>Novo Ticket</a>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="get" class="row g-2 align-items-end">
                <input type="hidden" name="page" value="tickets">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Buscar por assunto ou nº do ticket..." value="<?= eAttr($search) ?>">
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Status</option>
                        <?php foreach (['open' => 'Aberto', 'in_progress' => 'Em Andamento', 'resolved' => 'Resolvido', 'closed' => 'Fechado'] as $k => $v): ?>
                        <option value="<?= $k ?>" <?= $statusFilter === $k ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="priority" class="form-select form-select-sm">
                        <option value="">Prioridade</option>
                        <?php foreach (['low' => 'Baixa', 'medium' => 'Média', 'high' => 'Alta', 'urgent' => 'Urgente'] as $k => $v): ?>
                        <option value="<?= $k ?>" <?= $priorityFilter === $k ? 'selected' : '' ?>><?= $v ?></option>
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
                        <tr>
                            <th>Nº</th>
                            <th>Assunto</th>
                            <th>Prioridade</th>
                            <th>Status</th>
                            <th>Solicitante</th>
                            <th>Criado em</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($tickets)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">Nenhum ticket encontrado.</td></tr>
                    <?php else: ?>
                        <?php foreach ($tickets as $t): ?>
                        <tr>
                            <td><span class="badge bg-light text-dark"><?= e($t['ticket_number']) ?></span></td>
                            <td><a href="?page=tickets&action=view&id=<?= (int) $t['id'] ?>"><?= e($t['subject']) ?></a></td>
                            <td>
                                <?php
                                $priorityColors = ['low' => 'success', 'medium' => 'warning', 'high' => 'danger', 'urgent' => 'dark'];
                                $priorityLabels = ['low' => 'Baixa', 'medium' => 'Média', 'high' => 'Alta', 'urgent' => 'Urgente'];
                                $pColor = $priorityColors[$t['priority']] ?? 'secondary';
                                $pLabel = $priorityLabels[$t['priority']] ?? $t['priority'];
                                ?>
                                <span class="badge bg-<?= $pColor ?>"><?= $pLabel ?></span>
                            </td>
                            <td>
                                <?php
                                $statusColors = ['open' => 'primary', 'in_progress' => 'info', 'resolved' => 'success', 'closed' => 'secondary'];
                                $statusLabels = ['open' => 'Aberto', 'in_progress' => 'Em Andamento', 'resolved' => 'Resolvido', 'closed' => 'Fechado'];
                                $sColor = $statusColors[$t['status']] ?? 'secondary';
                                $sLabel = $statusLabels[$t['status']] ?? $t['status'];
                                ?>
                                <span class="badge bg-<?= $sColor ?>"><?= $sLabel ?></span>
                            </td>
                            <td><?= e($t['requester_name'] ?? '-') ?></td>
                            <td><?= e(date('d/m/Y H:i', strtotime($t['created_at']))) ?></td>
                            <td class="text-end">
                                <a href="?page=tickets&action=view&id=<?= (int) $t['id'] ?>" class="btn btn-sm btn-outline-primary" title="Ver"><i class="fas fa-eye"></i></a>
                                <button class="btn btn-sm btn-outline-danger btnDelete" data-id="<?= (int) $t['id'] ?>" title="Excluir"><i class="fas fa-trash"></i></button>
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
                <a class="page-link" href="?page=tickets&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&priority=<?= urlencode($priorityFilter) ?>&p=<?= $p ?>"><?= $p ?></a>
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
            Swal.fire({ title:'Excluir ticket?', text:'Esta ação não pode ser desfeita.', icon:'warning', showCancelButton:true, confirmButtonColor:'#d33', confirmButtonText:'Sim, excluir', cancelButtonText:'Cancelar' }).then(r => { if(r.isConfirmed) window.location.href='?page=tickets&action=delete&id='+id; });
        });
    });
});
</script>
