<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-users me-2"></i>Clientes</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="?page=customers&action=create" class="btn btn-sm btn-primary">
            <i class="fas fa-plus me-1"></i> Novo Cliente
        </a>
    </div>
</div>

<!-- Busca rápida -->
<div class="mb-3">
    <div class="input-group">
        <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
        <input type="text" class="form-control" id="searchTable" placeholder="Buscar por nome, e-mail, telefone ou documento..." autocomplete="off">
    </div>
</div>

<div class="table-responsive bg-white rounded shadow-sm">
    <table class="table table-hover align-middle mb-0">
        <thead class="bg-light">
            <tr>
                <th class="py-3 ps-4">Nome</th>
                <th class="py-3">E-mail</th>
                <th class="py-3">Telefone</th>
                <th class="py-3">Documento</th>
                <th class="py-3 text-end pe-4">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if(count($customers) > 0): ?>
            <?php foreach($customers as $customer): ?>
            <tr>
                <td class="ps-4">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 35px; height: 35px; font-size: 0.9rem;">
                            <?= e(strtoupper(substr($customer['name'], 0, 1))) ?>
                        </div>
                        <span class="fw-bold"><?= e($customer['name']) ?></span>
                    </div>
                </td>
                <td><?= $customer['email'] ? e($customer['email']) : '<span class="text-muted">—</span>' ?></td>
                <td><?= $customer['phone'] ? e($customer['phone']) : '<span class="text-muted">—</span>' ?></td>
                <td><span class="badge bg-light text-dark border"><?= $customer['document'] ? e($customer['document']) : '—' ?></span></td>
                <td class="text-end pe-4">
                    <div class="btn-group">
                        <a href="?page=customers&action=edit&id=<?= (int)$customer['id'] ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-danger ms-1 btn-delete-customer" data-id="<?= (int)$customer['id'] ?>" data-name="<?= eAttr($customer['name']) ?>" title="Excluir">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php else: ?>
            <tr>
                <td colspan="5" class="text-center text-muted py-5">
                    <i class="fas fa-users fa-3x mb-3 d-block text-secondary"></i>
                    Nenhum cliente cadastrado ainda.
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require 'app/views/layout/pagination.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if(isset($_GET['status'])): ?>
    if (window.history.replaceState) { const url = new URL(window.location); url.searchParams.delete('status'); window.history.replaceState({}, '', url); }
    <?php endif; ?>
    <?php if(isset($_GET['status']) && $_GET['status'] == 'success'): ?>
    Swal.fire({ icon: 'success', title: 'Sucesso!', text: 'Cliente salvo com sucesso!', timer: 2000, showConfirmButton: false });
    <?php endif; ?>

    // Botão excluir cliente com SweetAlert2
    document.querySelectorAll('.btn-delete-customer').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            Swal.fire({
                title: 'Excluir cliente?',
                html: `Deseja realmente excluir <strong>${name}</strong>?<br>Esta ação não pode ser desfeita.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#c0392b',
                cancelButtonColor: '#95a5a6',
                confirmButtonText: '<i class="fas fa-trash me-1"></i> Sim, excluir',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `?page=customers&action=delete&id=${id}`;
                }
            });
        });
    });

    // Busca rápida na tabela
    const searchInput = document.getElementById('searchTable');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const q = this.value.toLowerCase().trim();
            document.querySelectorAll('table tbody tr').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = (!q || text.includes(q)) ? '' : 'none';
            });
        });
    }
});
</script>
