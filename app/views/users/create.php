<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary p-3">
                <h5 class="mb-0 text-white"><i class="fas fa-user-plus me-2"></i>Novo Usuário</h5>
            </div>
            <div class="card-body p-4">
                <form action="?page=users&action=store" method="POST">
                    <?= csrf_field() ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted" for="createUserName">Nome Completo</label>
                            <input type="text" class="form-control" name="name" id="createUserName" required placeholder="Ex: João da Silva">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted" for="createUserEmail">Email</label>
                            <input type="email" class="form-control" name="email" id="createUserEmail" required placeholder="email@empresa.com">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted" for="createUserPassword">Senha</label>
                            <input type="password" class="form-control" name="password" id="createUserPassword" required placeholder="Mínimo 6 caracteres" aria-describedby="createPasswordHelp">
                            <div class="form-text" id="createPasswordHelp">A senha deve ter no mínimo 6 caracteres.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Nível de Acesso</label>
                            <select class="form-select" name="role" required onchange="toggleGroups(this.value)">
                                <option value="funcionario">Funcionário (Restrito)</option>
                                <option value="admin">Administrador (Total)</option>
                            </select>
                        </div>
                        
                        <div class="col-12" id="group-select-container">
                            <label class="form-label fw-bold small text-muted">Grupo de Permissões</label>
                            <select class="form-select" name="group_id" id="group_id" aria-describedby="group_id_help">
                                <option value="">Selecione um grupo...</option>
                                <?php foreach($groups as $grp): ?>
                                    <option value="<?= (int)$grp['id'] ?>"><?= e($grp['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text" id="group_id_help">Define quais páginas este usuário pode acessar.</div>
                        </div>

                        <div class="col-12 text-end mt-4">
                            <a href="?page=users" class="btn btn-secondary px-4 me-2">Cancelar</a>
                            <button type="submit" class="btn btn-primary px-4 fw-bold"><i class="fas fa-check me-2"></i>Salvar Usuário</button>
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
