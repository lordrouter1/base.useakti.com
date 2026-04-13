<?php
/**
 * Insumos — Gerenciamento de Categorias
 * Variáveis: $categories (carregadas via controller)
 */
$csrfToken = csrf_token();
?>

<?php if (!empty($_SESSION['flash_success'])): ?>
<script>document.addEventListener('DOMContentLoaded',()=>{if(typeof AktiToast!=='undefined')AktiToast.success('<?= addslashes($_SESSION['flash_success']) ?>');});</script>
<?php unset($_SESSION['flash_success']); endif; ?>

<div class="container-fluid py-3">

    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div>
            <h1 class="h2 mb-1"><i class="fas fa-folder-open me-2 text-primary"></i>Categorias de Insumos</h1>
        </div>
        <div class="btn-toolbar gap-2">
            <a href="?page=supplies" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
            <button class="btn btn-sm btn-primary" id="btnNewCat"><i class="fas fa-plus me-1"></i>Nova Categoria</button>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="catTable">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Nome</th>
                            <th>Descrição</th>
                            <th>Ordem</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="catBody">
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = '<?= $csrfToken ?>';

    function loadCategories() {
        fetch('?page=supplies&action=getCategoriesAjax')
            .then(r => r.json())
            .then(data => {
                const body = document.getElementById('catBody');
                if (!data.length) {
                    body.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">Nenhuma categoria cadastrada.</td></tr>';
                    return;
                }
                body.innerHTML = data.map(c => `
                    <tr>
                        <td>${c.id}</td>
                        <td>${c.name}</td>
                        <td>${c.description || '-'}</td>
                        <td>${c.sort_order}</td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary btnEditCat" data-id="${c.id}" data-name="${c.name}" data-desc="${c.description || ''}" data-order="${c.sort_order}"><i class="fas fa-edit"></i></button>
                        </td>
                    </tr>
                `).join('');
            });
    }
    loadCategories();

    document.getElementById('btnNewCat').addEventListener('click', function() {
        Swal.fire({
            title: 'Nova Categoria',
            input: 'text',
            inputLabel: 'Nome',
            showCancelButton: true,
            confirmButtonText: 'Criar'
        }).then(r => {
            if (r.isConfirmed && r.value) {
                fetch('?page=supplies&action=createCategoryAjax', {
                    method: 'POST',
                    headers: {'Content-Type':'application/x-www-form-urlencoded','X-CSRF-TOKEN':csrfToken},
                    body: 'name=' + encodeURIComponent(r.value) + '&csrf_token=' + csrfToken
                }).then(r => r.json()).then(r => {
                    if (r.success) { loadCategories(); AktiToast.success('Categoria criada.'); }
                });
            }
        });
    });
});
</script>
