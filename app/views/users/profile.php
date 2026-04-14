<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2"><i class="fas fa-user-circle me-2"></i>Meu Perfil</h1>
            <span class="badge bg-primary fs-6"><?= isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin' ? 'Administrador' : 'Usuário' ?></span>
        </div>
    </div>
</div>

<div class="row justify-content-center">
    <!-- Coluna da Foto / Avatar -->
    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body text-center p-4">
                <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3 shadow" style="width: 120px; height: 120px; font-size: 3rem;">
                    <?= e(strtoupper(substr($user['name'], 0, 1))) ?>
                </div>
                <h5 class="mb-1"><?= e($user['name']) ?></h5>
                <p class="text-muted small mb-2"><?= e($user['email']) ?></p>
                <span class="badge bg-body-secondary text-body border">ID: #<?= (int)$user['id'] ?></span>
                <?php if(!empty($user['group_name'])): ?>
                    <div class="mt-2"><span class="badge bg-info text-white"><?= e($user['group_name']) ?></span></div>
                <?php endif; ?>
                <hr>
                <p class="text-muted small mb-0"><i class="fas fa-calendar me-1"></i>Membro desde</p>
                <p class="small fw-bold"><?= isset($user['created_at']) ? date('d/m/Y', strtotime($user['created_at'])) : '---' ?></p>
            </div>
        </div>
    </div>

    <!-- Coluna do Formulário -->
    <div class="col-md-5">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary p-3">
                <h5 class="mb-0 text-white"><i class="fas fa-edit me-2"></i>Editar Perfil</h5>
            </div>
            <div class="card-body p-4">
                <form id="profileForm" action="?page=profile&action=update" method="POST">
                    <?= csrf_field() ?>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted" for="profileName">Nome Completo</label>
                        <input type="text" class="form-control" name="name" id="profileName" required value="<?= eAttr($user['name']) ?>" placeholder="Seu nome completo">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted" for="profileEmail">Email</label>
                        <input type="email" class="form-control" name="email" id="profileEmail" required value="<?= eAttr($user['email']) ?>" placeholder="seu@email.com">
                    </div>
                    
                    <hr>
                    <p class="small text-muted mb-2"><i class="fas fa-lock me-1"></i>Alterar Senha</p>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted" for="profilePassword">Nova Senha</label>
                        <input type="password" class="form-control" name="password" id="profilePassword" placeholder="Deixe em branco para manter a atual" minlength="6" aria-describedby="profilePasswordHelp">
                        <div class="form-text" id="profilePasswordHelp">Preencha apenas se desejar alterar sua senha (mínimo 6 caracteres).</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted" for="password_confirm">Confirmar Nova Senha</label>
                        <input type="password" class="form-control" name="password_confirm" id="password_confirm" placeholder="Repita a nova senha" aria-describedby="confirmPasswordHelp">
                        <div class="form-text" id="confirmPasswordHelp">Repita a nova senha para confirmação.</div>
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary fw-bold"><i class="fas fa-save me-2"></i>Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if(isset($_GET['success'])): ?>
    Swal.fire({
        icon: 'success',
        title: 'Perfil Atualizado!',
        text: 'Suas informações foram salvas com sucesso.',
        timer: 2500,
        showConfirmButton: false
    });
    <?php endif; ?>

    // Validação de confirmação de senha
    document.getElementById('profileForm').addEventListener('submit', function(e) {
        const pass = this.querySelector('[name="password"]').value;
        const confirm = document.getElementById('password_confirm').value;
        
        if (pass && pass !== confirm) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Senhas não conferem',
                text: 'A nova senha e a confirmação devem ser iguais.',
            });
            return false;
        }

        if (pass && pass.length < 6) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Senha muito curta',
                text: 'A senha deve ter pelo menos 6 caracteres.',
            });
            return false;
        }
    });
});
</script>
