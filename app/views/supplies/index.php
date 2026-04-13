<?php
/**
 * Insumos — Listagem
 * Variáveis: $supplies, $pagination, $categories
 */
$search = $_GET['search'] ?? '';
$categoryFilter = (int) ($_GET['category_id'] ?? 0);
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
            <h1 class="h2 mb-1"><i class="fas fa-cubes me-2 text-primary"></i>Insumos</h1>
            <p class="text-muted mb-0" style="font-size:.82rem;">Cadastro e gestão de matérias-primas e materiais de consumo.</p>
        </div>
        <div class="btn-toolbar gap-2">
            <a href="?page=supplies&action=categories" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-folder-open me-1"></i> Categorias
            </a>
            <a href="?page=supplies&action=create" class="btn btn-sm btn-primary">
                <i class="fas fa-plus me-1"></i> Novo Insumo
            </a>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="get" class="row g-2 align-items-end">
                <input type="hidden" name="page" value="supplies">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Buscar por nome ou código..." value="<?= eAttr($search) ?>">
                </div>
                <div class="col-md-3">
                    <select name="category_id" class="form-select form-select-sm">
                        <option value="">Todas as categorias</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int) $cat['id'] ?>" <?= $categoryFilter === (int) $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
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
                            <th>Código</th>
                            <th>Nome</th>
                            <th>Categoria</th>
                            <th>Unidade</th>
                            <th class="text-end">Custo</th>
                            <th>Status</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($supplies)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">Nenhum insumo encontrado.</td></tr>
                    <?php else: ?>
                        <?php foreach ($supplies as $s): ?>
                        <tr>
                            <td><span class="badge bg-light text-dark"><?= e($s['code']) ?></span></td>
                            <td><?= e($s['name']) ?></td>
                            <td><?= e($s['category_name'] ?? '-') ?></td>
                            <td><?= e($s['unit_measure']) ?></td>
                            <td class="text-end"><?= eNum($s['cost_price'], 4) ?></td>
                            <td>
                                <?php if ((int) $s['is_active']): ?>
                                    <span class="badge bg-success">Ativo</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inativo</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="?page=supplies&action=edit&id=<?= (int) $s['id'] ?>" class="btn btn-sm btn-outline-primary" title="Editar"><i class="fas fa-edit"></i></a>
                                <button class="btn btn-sm btn-outline-danger btnDelete" data-id="<?= (int) $s['id'] ?>" data-name="<?= eAttr($s['name']) ?>" title="Excluir"><i class="fas fa-trash"></i></button>
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
    <?php if (!empty($pagination) && $pagination['pages'] > 1): ?>
    <nav class="mt-3">
        <ul class="pagination pagination-sm justify-content-center">
            <?php for ($p = 1; $p <= $pagination['pages']; $p++): ?>
            <li class="page-item <?= $p == $pagination['current_page'] ? 'active' : '' ?>">
                <a class="page-link" href="?page=supplies&search=<?= urlencode($search) ?>&category_id=<?= $categoryFilter ?>&p=<?= $p ?>"><?= $p ?></a>
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
            const name = this.dataset.name;
            Swal.fire({
                title: 'Excluir insumo?',
                html: 'O insumo <strong>' + name + '</strong> será desativado.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Sim, excluir',
                cancelButtonText: 'Cancelar'
            }).then(result => {
                if (result.isConfirmed) {
                    window.location.href = '?page=supplies&action=delete&id=' + id;
                }
            });
        });
    });
});
</script>
