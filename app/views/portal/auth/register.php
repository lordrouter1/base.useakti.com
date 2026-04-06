<?php
/**
 * Portal do Cliente — Tela de Auto-Registro
 *
 * Variáveis: $error, $formData, $company
 */
$companyName = $company['company_name'] ?? 'Akti';
$companyLogo = $company['company_logo'] ?? '';
$formData = $formData ?? [];
?>

<div class="portal-auth-container">
    <div class="portal-auth-card">
        <!-- Logo / Marca -->
        <div class="portal-auth-header">
            <?php if ($companyLogo): ?>
                <img src="<?= eAttr(thumb_url($companyLogo, 150)) ?>" alt="<?= eAttr($companyName) ?>" class="portal-auth-logo">
            <?php else: ?>
                <div class="portal-auth-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
            <?php endif; ?>
            <h1 class="portal-auth-title"><?= __p('register_title') ?></h1>
            <p class="portal-auth-subtitle"><?= e($companyName) ?></p>
        </div>

        <!-- Mensagens -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-sm">
                <i class="fas fa-exclamation-circle me-1"></i>
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <!-- Formulário de Registro -->
        <form method="POST" action="?page=portal&action=register" class="portal-auth-form">
            <?= csrf_field() ?>

            <div class="mb-3">
                <div class="portal-input-group">
                    <span class="portal-input-icon"><i class="fas fa-user"></i></span>
                    <input type="text"
                           name="name"
                           class="form-control portal-input"
                           placeholder="<?= __p('register_name') ?>"
                           value="<?= eAttr($formData['name'] ?? '') ?>"
                           required
                           autofocus>
                </div>
            </div>

            <div class="mb-3">
                <div class="portal-input-group">
                    <span class="portal-input-icon"><i class="fas fa-envelope"></i></span>
                    <input type="email"
                           name="email"
                           class="form-control portal-input"
                           placeholder="<?= __p('register_email') ?>"
                           value="<?= eAttr($formData['email'] ?? '') ?>"
                           required
                           autocomplete="email">
                </div>
            </div>

            <div class="mb-3">
                <div class="portal-input-group">
                    <span class="portal-input-icon"><i class="fas fa-phone"></i></span>
                    <input type="tel"
                           name="phone"
                           class="form-control portal-input"
                           placeholder="<?= __p('register_phone') ?>"
                           value="<?= eAttr($formData['phone'] ?? '') ?>">
                </div>
            </div>

            <div class="mb-3">
                <div class="portal-input-group">
                    <span class="portal-input-icon"><i class="fas fa-id-card"></i></span>
                    <input type="text"
                           name="document"
                           class="form-control portal-input"
                           placeholder="<?= __p('register_document') ?>"
                           value="<?= eAttr($formData['document'] ?? '') ?>">
                </div>
            </div>

            <div class="mb-3">
                <div class="portal-input-group">
                    <span class="portal-input-icon"><i class="fas fa-lock"></i></span>
                    <input type="password"
                           name="password"
                           class="form-control portal-input"
                           placeholder="<?= __p('register_password') ?>"
                           required
                           minlength="6"
                           autocomplete="new-password">
                    <button type="button" class="portal-input-toggle" onclick="togglePasswordVisibility(this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="mb-3">
                <div class="portal-input-group">
                    <span class="portal-input-icon"><i class="fas fa-lock"></i></span>
                    <input type="password"
                           name="password_confirm"
                           class="form-control portal-input"
                           placeholder="<?= __p('register_password_confirm') ?>"
                           required
                           minlength="6"
                           autocomplete="new-password">
                </div>
            </div>

            <button type="submit" class="btn portal-btn-primary w-100 mb-3">
                <i class="fas fa-user-plus me-2"></i>
                <?= __p('register_btn') ?>
            </button>
        </form>

        <!-- Links -->
        <div class="portal-auth-links">
            <span class="text-muted"><?= __p('register_has_account') ?></span>
            <a href="?page=portal&action=login" class="portal-auth-link">
                <?= __p('register_login') ?>
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
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
</script>
