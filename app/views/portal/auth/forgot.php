<?php
/**
 * Portal do Cliente — Esqueci minha senha
 *
 * Variáveis: $error, $successMsg, $company
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
                    <i class="fas fa-key"></i>
                </div>
            <?php endif; ?>
            <h1 class="portal-auth-title"><?= __p('forgot_title') ?></h1>
            <p class="portal-auth-subtitle"><?= __p('forgot_subtitle') ?></p>
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

        <?php if (empty($successMsg)): ?>
        <!-- Formulário -->
        <form method="POST" action="?page=portal&action=forgotPassword" class="portal-auth-form">
            <?= csrf_field() ?>

            <div class="mb-3">
                <div class="portal-input-group">
                    <span class="portal-input-icon"><i class="fas fa-envelope"></i></span>
                    <input type="email"
                           name="email"
                           class="form-control portal-input"
                           placeholder="<?= __p('forgot_email') ?>"
                           required
                           autocomplete="email"
                           autofocus>
                </div>
            </div>

            <button type="submit" class="btn portal-btn-primary w-100 mb-3">
                <i class="fas fa-paper-plane me-2"></i>
                <?= __p('forgot_btn') ?>
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
