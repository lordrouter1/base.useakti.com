<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-users-cog me-2"></i>Gestão de Usuários</h1>
    <div class="btn-toolbar mb-2 mb-md-0 gap-2">
        <a href="?page=users&action=groups" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-layer-group"></i> Grupos e Permissões
        </a>
        <?php if (!empty($limitReached)): ?>
            <button class="btn btn-sm btn-primary disabled" disabled title="Limite do plano atingido">
                <i class="fas fa-user-plus"></i> Novo Usuário
            </button>
        <?php else: ?>
            <a href="?page=users&action=create" class="btn btn-sm btn-primary">
                <i class="fas fa-user-plus"></i> Novo Usuário
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($limitReached)): ?>
<div class="alert alert-warning border-warning d-flex align-items-center mb-3" role="alert">
    <i class="fas fa-exclamation-triangle fs-5 me-3 text-warning"></i>
    <div>
        <strong>Limite do plano atingido!</strong> Você possui <strong><?= e($limitInfo['current']) ?></strong> de <strong><?= e($limitInfo['max']) ?></strong> usuários permitidos.
        <span class="text-muted">Para cadastrar mais usuários, entre em contato com o suporte para fazer um upgrade do seu plano.</span>
    </div>
</div>
<?php endif; ?>

<div class="table-responsive bg-body rounded shadow-sm">
    <table class="table table-hover align-middle mb-0">
        <caption class="visually-hidden">Lista de usuários</caption>
        <thead class="table-light">
            <tr>
                <th class="py-3 ps-4">Nome</th>
                <th class="py-3">E-mail</th>
                <th class="py-3">Função</th>
                <th class="py-3">Grupo</th>
                <th class="py-3 text-end pe-4">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($users as $user): ?>
            <tr>
                <td class="ps-4 fw-bold">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 35px; height: 35px; font-size: 0.9rem;">
                            <?= e(strtoupper(substr($user['name'], 0, 1))) ?>
                        </div>
                        <?= e($user['name']) ?>
                    </div>
                </td>
                <td><?= e($user['email']) ?></td>
                <td>
                    <?php if($user['role'] === 'admin'): ?>
                        <span class="badge bg-danger rounded-pill px-3">Administrador</span>
                    <?php else: ?>
                        <span class="badge bg-secondary rounded-pill px-3">Usuário Padrão</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if($user['role'] === 'admin'): ?>
                        <span class="text-muted small fst-italic">Acesso Total</span>
                    <?php else: ?>
                        <?= !empty($user['group_name']) ? e($user['group_name']) : '<span class="text-muted">-</span>' ?>
                    <?php endif; ?>
                </td>
                <td class="text-end pe-4">
                    <div class="btn-group">
                        <a href="?page=users&action=edit&id=<?= (int)$user['id'] ?>" class="btn btn-sm btn-outline-primary" title="Editar" aria-label="Editar">
                            <i class="fas fa-edit" aria-hidden="true"></i>
                        </a>
                        <?php if($user['id'] != $_SESSION['user_id']): ?>
                            <button type="button" class="btn btn-sm btn-outline-danger ms-1 btn-delete-user" data-id="<?= (int)$user['id'] ?>" data-name="<?= eAttr($user['name']) ?>" title="Excluir" aria-label="Excluir">
                                <i class="fas fa-trash" aria-hidden="true"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if(isset($_GET['status'])): ?>
    if (window.history.replaceState) { const url = new URL(window.location); url.searchParams.delete('status'); window.history.replaceState({}, '', url); }
    <?php endif; ?>
    <?php if(isset($_GET['status']) && $_GET['status'] == 'success'): ?>
    if (window.AktiToast) AktiToast.success('Usuário salvo com sucesso!');
    <?php endif; ?>
    <?php if(isset($_GET['status']) && $_GET['status'] == 'limit_users'): ?>
    if (window.AktiToast) AktiToast.warning('Limite de usuários atingido. Entre em contato com o suporte para upgrade.');
    <?php endif; ?>

    document.querySelectorAll('.btn-delete-user').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            Swal.fire({
                title: 'Excluir usuário?',
                html: `Deseja realmente excluir <strong>${name}</strong>?<br>Esta ação não pode ser desfeita.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#c0392b',
                cancelButtonColor: '#95a5a6',
                confirmButtonText: '<i class="fas fa-trash me-1"></i> Sim, excluir',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `?page=users&action=delete&id=${id}`;
                }
            });
        });
    });
});
</script>
