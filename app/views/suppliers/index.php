<?php
/**
 * Fornecedores — Listagem
 * FEAT-005
 * Variáveis: $suppliers, $pagination
 */
$search = $_GET['search'] ?? '';
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
            <h1 class="h2 mb-1"><i class="fas fa-truck me-2 text-primary"></i>Fornecedores</h1>
            <p class="text-muted mb-0" style="font-size:.82rem;">Cadastro e gestão de fornecedores.</p>
        </div>
        <div class="btn-toolbar gap-2">
            <a href="?page=suppliers&action=create" class="btn btn-sm btn-primary">
                <i class="fas fa-plus me-1"></i> Novo Fornecedor
            </a>
        </div>
    </div>

    <!-- Filtro -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="get" class="row g-2 align-items-end">
                <input type="hidden" name="page" value="suppliers">
                <div class="col-md-6">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Buscar por nome, documento ou e-mail..." value="<?= eAttr($search) ?>">
                </div>
                <div class="col-auto">
                    <button class="btn btn-sm btn-outline-primary"><i class="fas fa-search me-1"></i>Buscar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabela -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Razão Social</th>
                            <th>CNPJ/CPF</th>
                            <th>E-mail</th>
                            <th>Telefone</th>
                            <th>Status</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($suppliers)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">Nenhum fornecedor encontrado.</td></tr>
                    <?php else: ?>
                        <?php foreach ($suppliers as $s): ?>
                        <tr>
                            <td><?= (int) $s['id'] ?></td>
                            <td><?= e($s['company_name']) ?></td>
                            <td><?= e($s['document'] ?? '-') ?></td>
                            <td><?= e($s['email'] ?? '-') ?></td>
                            <td><?= e($s['phone'] ?? '-') ?></td>
                            <td>
                                <?php if (($s['status'] ?? 'active') === 'active'): ?>
                                    <span class="badge bg-success">Ativo</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inativo</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="?page=suppliers&action=edit&id=<?= (int) $s['id'] ?>" class="btn btn-sm btn-outline-primary" title="Editar"><i class="fas fa-edit"></i></a>
                                <a href="?page=suppliers&action=purchases&supplier_id=<?= (int) $s['id'] ?>" class="btn btn-sm btn-outline-info" title="Compras"><i class="fas fa-shopping-cart"></i></a>
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

    <!-- Paginação -->
    <?php if (!empty($pagination) && ($pagination['total_pages'] ?? 1) > 1): ?>
    <nav class="mt-3">
        <ul class="pagination pagination-sm justify-content-center">
            <?php for ($p = 1; $p <= $pagination['total_pages']; $p++): ?>
            <li class="page-item <?= $p == ($pagination['page'] ?? 1) ? 'active' : '' ?>">
                <a class="page-link" href="?page=suppliers&search=<?= urlencode($search) ?>&p=<?= $p ?>"><?= $p ?></a>
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
                title: 'Excluir fornecedor?',
                text: 'Esta ação não pode ser desfeita.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Sim, excluir',
                cancelButtonText: 'Cancelar'
            }).then(result => {
                if (result.isConfirmed) {
                    window.location.href = '?page=suppliers&action=delete&id=' + id;
                }
            });
        });
    });
});
</script>
