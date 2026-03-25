<?php
/**
 * Portal do Cliente — Tela de Login
 *
 * Variáveis: $error, $successMsg, $company, $allowSelfRegister
 */
$companyName = $company['company_name'] ?? 'Akti';
$companyLogo = $company['company_logo'] ?? '';
?>

<div class="portal-auth-container">
    <div class="portal-auth-card">
        <!-- Logo / Marca -->
        <div class="portal-auth-header">
            <?php if ($companyLogo): ?>
                <img src="<?= eAttr($companyLogo) ?>" alt="<?= eAttr($companyName) ?>" class="portal-auth-logo">
            <?php else: ?>
                <div class="portal-auth-icon">
                    <i class="fas fa-user-circle"></i>
                </div>
            <?php endif; ?>
            <h1 class="portal-auth-title"><?= e($companyName) ?></h1>
            <p class="portal-auth-subtitle"><?= __p('portal_title') ?></p>
        </div>

        <!-- Mensagens -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-sm">
                <i class="fas fa-exclamation-circle me-1"></i>
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($successMsg)): ?>
            <div class="alert alert-success alert-sm">
                <i class="fas fa-check-circle me-1"></i>
                <?= e($successMsg) ?>
            </div>
        <?php endif; ?>

        <!-- Formulário de Login -->
        <form method="POST" action="?page=portal&action=login" class="portal-auth-form" id="portalLoginForm">
            <?= csrf_field() ?>

            <div class="mb-3">
                <div class="portal-input-group">
                    <span class="portal-input-icon"><i class="fas fa-envelope"></i></span>
                    <input type="email"
                           name="email"
                           class="form-control portal-input"
                           placeholder="<?= __p('login_email') ?>"
                           required
                           autocomplete="email"
                           autofocus>
                </div>
            </div>

            <div class="mb-3">
                <div class="portal-input-group">
                    <span class="portal-input-icon"><i class="fas fa-lock"></i></span>
                    <input type="password"
                           name="password"
                           class="form-control portal-input"
                           placeholder="<?= __p('login_password') ?>"
                           required
                           autocomplete="current-password">
                    <button type="button" class="portal-input-toggle" onclick="togglePasswordVisibility(this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn portal-btn-primary w-100 mb-3">
                <i class="fas fa-sign-in-alt me-2"></i>
                <?= __p('login_btn') ?>
            </button>
        </form>

        <!-- Divider -->
        <div class="portal-auth-divider">
            <span><?= __p('login_or') ?></span>
        </div>

        <!-- Link Mágico -->
        <form method="POST" action="?page=portal&action=requestMagicLink" class="portal-auth-form" id="portalMagicForm" style="display:none;">
            <?= csrf_field() ?>
            <div class="mb-3">
                <div class="portal-input-group">
                    <span class="portal-input-icon"><i class="fas fa-envelope"></i></span>
                    <input type="email"
                           name="magic_email"
                           class="form-control portal-input"
                           placeholder="<?= __p('login_email') ?>"
                           required>
                </div>
            </div>
            <button type="submit" class="btn portal-btn-outline w-100 mb-3">
                <i class="fas fa-paper-plane me-2"></i>
                <?= __p('login_magic_btn') ?>
            </button>
            <button type="button" class="btn btn-link w-100 text-muted" onclick="toggleMagicForm(false)">
                <?= __p('back') ?>
            </button>
        </form>

        <button type="button" class="btn portal-btn-outline w-100 mb-3" id="showMagicFormBtn" onclick="toggleMagicForm(true)">
            <i class="fas fa-magic me-2"></i>
            <?= __p('login_magic_btn') ?>
        </button>

        <!-- Links -->
        <div class="portal-auth-links">
            <a href="?page=portal&action=forgotPassword" class="portal-auth-link">
                <i class="fas fa-key me-1"></i>
                <?= __p('login_forgot') ?>
            </a>
            <?php if ($allowSelfRegister): ?>
                <a href="?page=portal&action=register" class="portal-auth-link">
                    <i class="fas fa-user-plus me-1"></i>
                    <?= __p('login_register') ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function toggleMagicForm(show) {
    document.getElementById('portalMagicForm').style.display = show ? 'block' : 'none';
    document.getElementById('showMagicFormBtn').style.display = show ? 'none' : 'block';
    document.getElementById('portalLoginForm').style.display = show ? 'none' : 'block';
    document.querySelector('.portal-auth-divider').style.display = show ? 'none' : 'flex';
}

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
