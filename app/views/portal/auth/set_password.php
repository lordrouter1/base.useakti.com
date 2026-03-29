<?php
/**
 * Portal do Cliente — Cadastrar Senha (via Magic Link)
 *
 * Variáveis: $error, $successMsg, $company, $token, $validToken
 */
$companyName = $company['company_name'] ?? 'Akti';
$companyLogo = $company['company_logo'] ?? '';
$validToken  = $validToken ?? false;
?>

<div class="portal-auth-container">
    <div class="portal-auth-card">
        <!-- Logo / Marca -->
        <div class="portal-auth-header">
            <?php if ($companyLogo): ?>
                <img src="<?= eAttr($companyLogo) ?>" alt="<?= eAttr($companyName) ?>" class="portal-auth-logo">
            <?php else: ?>
                <div class="portal-auth-icon">
                    <i class="fas fa-key"></i>
                </div>
            <?php endif; ?>
            <h1 class="portal-auth-title"><?= __p('setup_password_title') ?></h1>
            <p class="text-muted mt-2"><?= __p('setup_password_subtitle') ?></p>
        </div>

        <!-- Mensagens -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-sm">
                <i class="fas fa-exclamation-circle me-1"></i>
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($validToken): ?>
        <!-- Formulário de cadastro de senha -->
        <form method="POST" action="?page=portal&action=setupPassword&token=<?= eAttr($token) ?>" class="portal-auth-form">
            <?= csrf_field() ?>
            <input type="hidden" name="token" value="<?= eAttr($token) ?>">

            <div class="mb-3">
                <div class="portal-input-group">
                    <span class="portal-input-icon"><i class="fas fa-lock"></i></span>
                    <input type="password"
                           name="password"
                           class="form-control portal-input"
                           placeholder="<?= __p('setup_password_field') ?>"
                           required
                           autocomplete="new-password"
                           minlength="8"
                           autofocus>
                    <button type="button" class="portal-input-toggle" onclick="togglePasswordVisibility(this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <small class="text-muted"><?= __p('reset_password_hint') ?></small>
            </div>

            <div class="mb-3">
                <div class="portal-input-group">
                    <span class="portal-input-icon"><i class="fas fa-lock"></i></span>
                    <input type="password"
                           name="password_confirm"
                           class="form-control portal-input"
                           placeholder="<?= __p('setup_password_confirm') ?>"
                           required
                           autocomplete="new-password"
                           minlength="8">
                </div>
            </div>

            <button type="submit" class="btn portal-btn-primary w-100 mb-3">
                <i class="fas fa-save me-2"></i>
                <?= __p('setup_password_btn') ?>
            </button>
        </form>
        <?php endif; ?>

        <!-- Voltar -->
        <div class="portal-auth-links">
            <a href="?page=portal&action=login" class="portal-auth-link">
                <i class="fas fa-arrow-left me-1"></i>
                <?= __p('forgot_back') ?>
            </a>
        </div>
    </div>
</div>

<script>
function togglePasswordVisibility(btn) {
    var input = btn.parentElement.querySelector('input[type="password"], input[type="text"]');
    var icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>
