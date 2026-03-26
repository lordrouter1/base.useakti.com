<?php
/**
 * Portal do Cliente — Verificação 2FA
 * Tela para inserir código de 6 dígitos enviado por e-mail.
 *
 * Variáveis: $error, $company
 */
$error = $error ?? '';
?>

<div class="portal-auth-container">
    <div class="portal-auth-card">
        <div class="portal-auth-header">
            <div class="portal-auth-icon">
                <i class="fas fa-shield-halved"></i>
            </div>
            <h1 class="portal-auth-title"><?= __p('2fa_verify_title') ?></h1>
            <p class="portal-auth-subtitle"><?= __p('2fa_verify_subtitle') ?></p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-sm">
                <i class="fas fa-exclamation-circle me-1"></i>
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="?page=portal&action=verify2fa" class="portal-auth-form">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label class="form-label portal-label"><?= __p('2fa_code_label') ?></label>
                <input type="text"
                       name="code"
                       class="form-control portal-input portal-2fa-input portal-2fa-code-input"
                       maxlength="6"
                       pattern="[0-9]{6}"
                       inputmode="numeric"
                       autocomplete="one-time-code"
                       placeholder="<?= __p('2fa_code_placeholder') ?>"
                       autofocus
                       required>
            </div>

            <button type="submit" class="btn portal-btn-primary w-100">
                <i class="fas fa-check me-2"></i>
                <?= __p('2fa_verify_btn') ?>
            </button>
        </form>

        <div class="portal-auth-footer mt-3">
            <button type="button" class="btn btn-link text-muted" id="resend2faBtn" onclick="resend2fa()">
                <i class="fas fa-redo me-1"></i>
                <?= __p('2fa_resend') ?>
            </button>
            <a href="?page=portal&action=logout" class="btn btn-link text-danger">
                <i class="fas fa-sign-out-alt me-1"></i>
                <?= __p('profile_logout') ?>
            </a>
        </div>
    </div>
</div>

<script>
function resend2fa() {
    var btn = document.getElementById('resend2faBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> <?= __p('loading') ?>';

    fetch('?page=portal&action=resend2fa', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: new FormData()
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            btn.innerHTML = '<i class="fas fa-check me-1"></i> <?= __p('2fa_code_resent') ?>';
        } else {
            btn.innerHTML = '<i class="fas fa-times me-1"></i> <?= __p('error_generic') ?>';
        }
        setTimeout(function() {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-redo me-1"></i> <?= __p('2fa_resend') ?>';
        }, 30000);
    })
    .catch(function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-redo me-1"></i> <?= __p('2fa_resend') ?>';
    });
}

// Auto-focus e auto-submit ao completar 6 dígitos
document.querySelector('.portal-2fa-input')?.addEventListener('input', function(e) {
    this.value = this.value.replace(/\D/g, '').slice(0, 6);
    if (this.value.length === 6) {
        this.closest('form').submit();
    }
});
</script>
