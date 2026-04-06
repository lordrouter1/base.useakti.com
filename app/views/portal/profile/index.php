<?php
/**
 * Portal do Cliente — Perfil
 *
 * Variáveis: $customer, $access, $company, $languages, $message, $avatarPath, $is2faEnabled
 */
$customer     = $customer ?? [];
$access       = $access ?? [];
$avatarPath   = $avatarPath ?? ($access['avatar'] ?? '');
$is2faEnabled = $is2faEnabled ?? (($access['two_factor_enabled'] ?? 0) == 1);
$customerInitial = strtoupper(substr($customer['name'] ?? 'C', 0, 1));
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

    <?php if (!empty($_GET['avatar_updated'])): ?>
        <div class="alert alert-success alert-sm">
            <i class="fas fa-check-circle me-1"></i>
            <?= __p('avatar_updated') ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($_GET['avatar_error'])): ?>
        <div class="alert alert-danger alert-sm">
            <i class="fas fa-exclamation-circle me-1"></i>
            <?= __p('avatar_upload_error') ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($_GET['password_error'])): ?>
        <div class="alert alert-danger alert-sm">
            <i class="fas fa-exclamation-circle me-1"></i>
            <?= e($_GET['password_error']) ?>
        </div>
    <?php endif; ?>

    <!-- ═══ Avatar Section ═══ -->
    <div class="portal-card">
        <div class="portal-card-body">
            <div class="portal-avatar-section">
                <div class="portal-avatar-wrapper">
                    <?php if (!empty($avatarPath) && file_exists($avatarPath)): ?>
                        <img src="<?= eAttr(thumb_url($avatarPath, 150, 150)) ?>" alt="Avatar" class="portal-avatar" id="portalAvatarPreview">
                        <span id="portalAvatarPlaceholder" style="display:none" class="portal-avatar-placeholder"><?= e($customerInitial) ?></span>
                    <?php else: ?>
                        <img src="" alt="Avatar" class="portal-avatar" id="portalAvatarPreview" style="display:none">
                        <span id="portalAvatarPlaceholder" class="portal-avatar-placeholder"><?= e($customerInitial) ?></span>
                    <?php endif; ?>
                    <label for="portalAvatarInput" class="portal-avatar-upload-btn" title="<?= __p('avatar_change') ?>">
                        <i class="fas fa-camera"></i>
                    </label>
                    <input type="file" id="portalAvatarInput" accept="image/jpeg,image/png,image/webp" style="display:none">
                </div>
                <div class="portal-avatar-name"><?= e($customer['name'] ?? '') ?></div>
                <div class="portal-avatar-email"><?= e($access['email'] ?? $customer['email'] ?? '') ?></div>
            </div>
        </div>
    </div>

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
                           value="<?= eAttr($customer['phone'] ?? '') ?>"
                           data-mask="phone">
                </div>

                <div class="mb-3">
                    <label class="form-label portal-label"><?= __p('profile_document') ?></label>
                    <input type="text" class="form-control portal-input" disabled
                           value="<?= eAttr($customer['document'] ?? '') ?>"
                           data-mask="cpf_cnpj">
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

    <!-- ═══ Segurança — 2FA ═══ -->
    <div class="portal-2fa-card">
        <div class="portal-2fa-header">
            <h5><i class="fas fa-shield-halved me-2"></i> <?= __p('2fa_title') ?></h5>
            <span id="portal2faStatus" class="portal-2fa-status <?= $is2faEnabled ? 'portal-2fa-status-on' : 'portal-2fa-status-off' ?>">
                <?= $is2faEnabled ? __p('2fa_status_on') : __p('2fa_status_off') ?>
            </span>
        </div>
        <p class="portal-2fa-description"><?= __p('2fa_description') ?></p>
        <label class="portal-2fa-toggle">
            <input type="checkbox" id="portal2faToggle" <?= $is2faEnabled ? 'checked' : '' ?>>
            <span class="portal-2fa-slider"></span>
        </label>
    </div>

    <!-- Logout -->
    <div class="mt-4 text-center">
        <a href="?page=portal&action=logout" class="btn btn-outline-danger w-100"
           onclick="return confirm('<?= __p('profile_logout_confirm') ?>')">
            <i class="fas fa-sign-out-alt me-2"></i>
            <?= __p('profile_logout') ?>
        </a>
    </div>
</div>
