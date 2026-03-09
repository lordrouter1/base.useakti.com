<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary p-3">
                <h5 class="mb-0 text-white"><i class="fas fa-user-edit me-2"></i>Editar Usuário</h5>
            </div>
            <div class="card-body p-4">
                <form action="?page=users&action=update" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $user['id'] ?>">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Nome Completo</label>
                            <input type="text" class="form-control" name="name" required value="<?= $user['name'] ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Email</label>
                            <input type="email" class="form-control" name="email" required value="<?= $user['email'] ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Senha</label>
                            <input type="password" class="form-control" name="password" placeholder="Deixe em branco para manter a atual">
                            <div class="form-text">Preencha apenas se desejar alterar.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Nível de Acesso</label>
                            <select class="form-select" name="role" required onchange="toggleGroups(this.value)">
                                <option value="funcionario" <?= $user['role'] == 'funcionario' ? 'selected' : '' ?>>Funcionário (Restrito)</option>
                                <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Administrador (Total)</option>
                            </select>
                        </div>
                        
                        <div class="col-12" id="group-select-container" style="<?= $user['role'] == 'admin' ? 'opacity:0.5; pointer-events:none;' : '' ?>">
                            <label class="form-label fw-bold small text-muted">Grupo de Permissões</label>
                            <select class="form-select" name="group_id">
                                <option value="">Selecione um grupo...</option>
                                <?php foreach($groups as $grp): ?>
                                    <option value="<?= $grp['id'] ?>" <?= $user['group_id'] == $grp['id'] ? 'selected' : '' ?>><?= $grp['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Define quais páginas este usuário pode acessar.</div>
                        </div>

                        <div class="col-12 text-end mt-4">
                            <a href="?page=users" class="btn btn-secondary px-4 me-2">Cancelar</a>
                            <button type="submit" class="btn btn-primary px-4 fw-bold"><i class="fas fa-save me-2"></i>Salvar Alterações</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function toggleGroups(role) {
    const container = document.getElementById('group-select-container');
    if (role === 'admin') {
        container.style.opacity = '0.5';
        container.style.pointerEvents = 'none';
        container.querySelector('select').value = ''; 
    } else {
        container.style.opacity = '1';
        container.style.pointerEvents = 'auto';
    }
}
</script>
