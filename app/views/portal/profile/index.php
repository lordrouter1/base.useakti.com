<?php
/**
 * Portal do Cliente — Perfil
 *
 * Variáveis: $customer, $access, $company, $languages, $message
 */
$customer = $customer ?? [];
$access   = $access ?? [];
?>

<div class="portal-page">
    <div class="portal-page-header">
        <h2 class="portal-page-title">
            <i class="fas fa-user-circle me-2"></i>
            <?= __p('profile_title') ?>
        </h2>
    </div>

    <?php if (!empty($_GET['updated'])): ?>
        <div class="alert alert-success alert-sm">
            <i class="fas fa-check-circle me-1"></i>
            <?= __p('profile_updated') ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($_GET['password_error'])): ?>
        <div class="alert alert-danger alert-sm">
            <i class="fas fa-exclamation-circle me-1"></i>
            <?= e($_GET['password_error']) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="?page=portal&action=updateProfile" class="portal-form">
        <?= csrf_field() ?>

        <div class="portal-card">
            <div class="portal-card-header">
                <h5><i class="fas fa-address-card me-2"></i> <?= __p('details') ?></h5>
            </div>
            <div class="portal-card-body">
                <div class="mb-3">
                    <label class="form-label portal-label"><?= __p('profile_name') ?></label>
                    <input type="text" name="name" class="form-control portal-input"
                           value="<?= eAttr($customer['name'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label portal-label"><?= __p('profile_email') ?></label>
                    <input type="email" class="form-control portal-input" disabled
                           value="<?= eAttr($access['email'] ?? $customer['email'] ?? '') ?>">
                    <small class="text-muted">O e-mail não pode ser alterado.</small>
                </div>

                <div class="mb-3">
                    <label class="form-label portal-label"><?= __p('profile_phone') ?></label>
                    <input type="tel" name="phone" class="form-control portal-input"
                           value="<?= eAttr($customer['phone'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label portal-label"><?= __p('profile_document') ?></label>
                    <input type="text" class="form-control portal-input" disabled
                           value="<?= eAttr($customer['document'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label portal-label"><?= __p('profile_address') ?></label>
                    <textarea name="address" class="form-control portal-input" rows="2"><?= e($customer['address'] ?? '') ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label portal-label"><?= __p('profile_language') ?></label>
                    <select name="lang" class="form-select portal-input">
                        <?php foreach ($languages as $code => $label): ?>
                            <option value="<?= eAttr($code) ?>"
                                <?= ($access['lang'] ?? 'pt-br') === $code ? 'selected' : '' ?>>
                                <?= e($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Alterar Senha -->
        <div class="portal-card mt-3">
            <div class="portal-card-header">
                <h5><i class="fas fa-lock me-2"></i> <?= __p('profile_password') ?></h5>
            </div>
            <div class="portal-card-body">
                <div class="mb-3">
                    <label class="form-label portal-label"><?= __p('profile_password_current') ?></label>
                    <input type="password" name="current_password" class="form-control portal-input"
                           autocomplete="current-password">
                    <small class="text-muted"><?= __p('profile_password_current_hint') ?></small>
                </div>
                <div class="mb-3">
                    <label class="form-label portal-label"><?= __p('profile_password_new') ?></label>
                    <input type="password" name="new_password" class="form-control portal-input"
                           autocomplete="new-password" minlength="8">
                    <small class="text-muted"><?= __p('profile_password_hint') ?></small>
                </div>
                <div class="mb-3">
                    <label class="form-label portal-label"><?= __p('profile_password_confirm') ?></label>
                    <input type="password" name="new_password_confirm" class="form-control portal-input"
                           autocomplete="new-password" minlength="8">
                </div>
            </div>
        </div>

        <div class="portal-form-actions mt-3">
            <button type="submit" class="btn portal-btn-primary w-100">
                <i class="fas fa-save me-2"></i>
                <?= __p('profile_save') ?>
            </button>
        </div>
    </form>

    <!-- Logout -->
    <div class="mt-4 text-center">
        <a href="?page=portal&action=logout" class="btn btn-outline-danger w-100"
           onclick="return confirm('<?= __p('profile_logout_confirm') ?>')">
            <i class="fas fa-sign-out-alt me-2"></i>
            <?= __p('profile_logout') ?>
        </a>
    </div>
</div>
