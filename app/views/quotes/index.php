<?php
/**
 * Orçamentos — Listagem
 * FEAT-006
 * Variáveis: $quotes, $pagination
 */
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
?>

<?php if (!empty($_SESSION['flash_success'])): ?>
<script>document.addEventListener('DOMContentLoaded',()=>{if(typeof AktiToast!=='undefined')AktiToast.success('<?= addslashes($_SESSION['flash_success']) ?>');});</script>
<?php unset($_SESSION['flash_success']); endif; ?>
<?php if (!empty($_SESSION['flash_error'])): ?>
<script>document.addEventListener('DOMContentLoaded',()=>{if(typeof AktiToast!=='undefined')AktiToast.error('<?= addslashes($_SESSION['flash_error']) ?>');});</script>
<?php unset($_SESSION['flash_error']); endif; ?>

<div class="container-fluid py-3">

    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div>
            <h1 class="h2 mb-1"><i class="fas fa-file-alt me-2 text-primary"></i>Orçamentos</h1>
            <p class="text-muted mb-0" style="font-size:.82rem;">Gestão de orçamentos e propostas comerciais.</p>
        </div>
        <a href="?page=quotes&action=create" class="btn btn-sm btn-primary"><i class="fas fa-plus me-1"></i>Novo Orçamento</a>
    </div>

    <!-- Filtros -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="get" class="row g-2 align-items-end">
                <input type="hidden" name="page" value="quotes">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Buscar..." value="<?= eAttr($search) ?>">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Todos os Status</option>
                        <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Rascunho</option>
                        <option value="sent" <?= $status === 'sent' ? 'selected' : '' ?>>Enviado</option>
                        <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Aprovado</option>
                        <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rejeitado</option>
                        <option value="expired" <?= $status === 'expired' ? 'selected' : '' ?>>Expirado</option>
                        <option value="converted" <?= $status === 'converted' ? 'selected' : '' ?>>Convertido</option>
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
                            <th>#</th>
                            <th>Cliente</th>
                            <th>Versão</th>
                            <th>Total</th>
                            <th>Validade</th>
                            <th>Status</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($quotes)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">Nenhum orçamento encontrado.</td></tr>
                    <?php else: ?>
                        <?php foreach ($quotes as $q): ?>
                        <tr>
                            <td><?= (int) $q['id'] ?></td>
                            <td><?= e($q['customer_name'] ?? '-') ?></td>
                            <td>v<?= (int) ($q['version'] ?? 1) ?></td>
                            <td>R$ <?= number_format((float) ($q['total'] ?? 0), 2, ',', '.') ?></td>
                            <td><?= !empty($q['valid_until']) ? date('d/m/Y', strtotime($q['valid_until'])) : '-' ?></td>
                            <td>
                                <?php
                                $badges = [
                                    'draft'     => 'bg-secondary',
                                    'sent'      => 'bg-info',
                                    'approved'  => 'bg-success',
                                    'rejected'  => 'bg-danger',
                                    'expired'   => 'bg-warning text-dark',
                                    'converted' => 'bg-primary',
                                ];
                                $labels = [
                                    'draft'     => 'Rascunho',
                                    'sent'      => 'Enviado',
                                    'approved'  => 'Aprovado',
                                    'rejected'  => 'Rejeitado',
                                    'expired'   => 'Expirado',
                                    'converted' => 'Convertido',
                                ];
                                $st = $q['status'] ?? 'draft';
                                ?>
                                <span class="badge <?= $badges[$st] ?? 'bg-secondary' ?>"><?= $labels[$st] ?? $st ?></span>
                            </td>
                            <td class="text-end">
                                <a href="?page=quotes&action=edit&id=<?= (int) $q['id'] ?>" class="btn btn-sm btn-outline-primary" title="Editar"><i class="fas fa-edit"></i></a>
                                <?php if ($st === 'approved'): ?>
                                <a href="?page=quotes&action=convertToOrder&id=<?= (int) $q['id'] ?>" class="btn btn-sm btn-outline-success" title="Converter em Pedido"><i class="fas fa-exchange-alt"></i></a>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-outline-danger btnDelete" data-id="<?= (int) $q['id'] ?>" title="Excluir"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php if (!empty($pagination) && ($pagination['total_pages'] ?? 1) > 1): ?>
    <nav class="mt-3">
        <ul class="pagination pagination-sm justify-content-center">
            <?php for ($p = 1; $p <= $pagination['total_pages']; $p++): ?>
            <li class="page-item <?= $p == ($pagination['page'] ?? 1) ? 'active' : '' ?>">
                <a class="page-link" href="?page=quotes&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&p=<?= $p ?>"><?= $p ?></a>
            </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btnDelete').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            Swal.fire({
                title: 'Excluir orçamento?', text: 'Esta ação não pode ser desfeita.', icon: 'warning',
                showCancelButton: true, confirmButtonColor: '#d33',
                confirmButtonText: 'Sim, excluir', cancelButtonText: 'Cancelar'
            }).then(r => { if (r.isConfirmed) window.location.href = '?page=quotes&action=delete&id=' + id; });
        });
    });
});
</script>
