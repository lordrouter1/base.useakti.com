<?php
/**
 * Admin do Portal — Editar Acesso
 *
 * Variáveis: $access, $customer, $error, $success
 */
$isActive = (bool) ($access['is_active'] ?? 0);
$isLocked = !empty($access['locked_until']) && strtotime($access['locked_until']) > time();
?>

<div class="container-fluid px-4 py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-6">

            <!-- ═══ Header ═══ -->
            <div class="d-flex align-items-center mb-4">
                <a href="?page=portal_admin" class="btn btn-outline-secondary me-3">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-user-edit me-2 text-primary"></i>
                        Editar Acesso — <?= e($customer['name'] ?? 'Cliente') ?>
                    </h1>
                    <small class="text-muted">ID do acesso: <?= (int) $access['id'] ?></small>
                </div>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-1"></i> <?= e($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-1"></i> <?= e($error) ?>
                </div>
            <?php endif; ?>

            <!-- ═══ Info do Cliente ═══ -->
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-user me-1"></i> Dados do Cliente</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <small class="text-muted d-block">Nome</small>
                            <strong><?= e($customer['name'] ?? '—') ?></strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">E-mail (cadastro)</small>
                            <code><?= e($customer['email'] ?? '—') ?></code>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Telefone</small>
                            <?= e($customer['phone'] ?? '—') ?>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">CPF/CNPJ</small>
                            <?= e($customer['document'] ?? '—') ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══ Status Atual ═══ -->
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-1"></i> Status do Acesso</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <small class="text-muted d-block">Status</small>
                            <?php if ($isLocked): ?>
                                <span class="badge bg-danger"><i class="fas fa-lock me-1"></i> Bloqueado</span>
                                <br><small class="text-muted">Até: <?= date('d/m/Y H:i', strtotime($access['locked_until'])) ?></small>
                            <?php elseif ($isActive): ?>
                                <span class="badge bg-success"><i class="fas fa-check me-1"></i> Ativo</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><i class="fas fa-ban me-1"></i> Inativo</span>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Último Login</small>
                            <?php if (!empty($access['last_login_at'])): ?>
                                <?= date('d/m/Y H:i', strtotime($access['last_login_at'])) ?>
                                <br><small class="text-muted">IP: <?= e($access['last_login_ip'] ?? '—') ?></small>
                            <?php else: ?>
                                <span class="text-muted">Nunca acessou</span>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Tentativas Falhas</small>
                            <span class="<?= (int) $access['failed_attempts'] > 0 ? 'text-danger fw-bold' : 'text-muted' ?>">
                                <?= (int) $access['failed_attempts'] ?>
                            </span>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Senha Definida</small>
                            <?php if (!empty($access['password_hash'])): ?>
                                <span class="text-success"><i class="fas fa-check"></i> Sim</span>
                            <?php else: ?>
                                <span class="text-warning"><i class="fas fa-times"></i> Não</span>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Criado em</small>
                            <?= date('d/m/Y H:i', strtotime($access['created_at'])) ?>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">E-mail do Portal</small>
                            <code><?= e($access['email']) ?></code>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══ Ações Rápidas ═══ -->
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-bolt me-1"></i> Ações Rápidas</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-sm btn-outline-info"
                                onclick="sendMagicLink(<?= (int) $access['id'] ?>)">
                            <i class="fas fa-link me-1"></i> Enviar Link Mágico
                        </button>
                        <button class="btn btn-sm btn-outline-warning"
                                onclick="resetPortalPassword(<?= (int) $access['id'] ?>)">
                            <i class="fas fa-key me-1"></i> Resetar Senha
                        </button>
                        <button class="btn btn-sm btn-outline-secondary"
                                onclick="forceLogoutAccess(<?= (int) $access['id'] ?>)">
                            <i class="fas fa-sign-out-alt me-1"></i> Forçar Logout
                        </button>
                    </div>
                </div>
            </div>

            <!-- ═══ Formulário de Edição ═══ -->
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-edit me-1"></i> Editar Configurações</h6>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="?page=portal_admin&action=update">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) $access['id'] ?>">

                        <!-- Status Ativo -->
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1"
                                       class="form-check-input" id="is_active"
                                       <?= $isActive ? 'checked' : '' ?>>
                                <label class="form-check-label fw-semibold" for="is_active">
                                    Acesso Ativo
                                </label>
                            </div>
                            <div class="form-text">
                                Desativar impede o cliente de fazer login no portal.
                            </div>
                        </div>

                        <!-- Idioma -->
                        <div class="mb-3">
                            <label for="lang" class="form-label fw-semibold">
                                <i class="fas fa-language me-1"></i> Idioma
                            </label>
                            <select name="lang" id="lang" class="form-select" style="max-width:300px;">
                                <option value="pt-br" <?= ($access['lang'] ?? 'pt-br') === 'pt-br' ? 'selected' : '' ?>>
                                    Português (Brasil)
                                </option>
                                <option value="en" <?= ($access['lang'] ?? '') === 'en' ? 'selected' : '' ?>>
                                    English
                                </option>
                                <option value="es" <?= ($access['lang'] ?? '') === 'es' ? 'selected' : '' ?>>
                                    Español
                                </option>
                            </select>
                        </div>

                        <!-- Nova Senha -->
                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold">
                                <i class="fas fa-key me-1"></i> Definir Nova Senha
                            </label>
                            <input type="text" name="password" id="password" class="form-control"
                                   placeholder="Deixe em branco para manter a senha atual"
                                   autocomplete="new-password" style="max-width:400px;">
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between">
                            <a href="?page=portal_admin" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Voltar
                            </a>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-danger"
                                        onclick="deletePortalAccess(<?= (int) $access['id'] ?>)">
                                    <i class="fas fa-trash me-1"></i> Excluir
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-check me-1"></i> Salvar Alterações
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- ═══ Modal: Magic Link ═══ -->
<div class="modal fade" id="magicLinkModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-link me-2"></i> Link Mágico</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Link gerado:</p>
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control" id="magicLinkUrl" readonly>
                    <button class="btn btn-outline-primary" type="button"
                            onclick="navigator.clipboard.writeText(document.getElementById('magicLinkUrl').value); this.innerHTML='<i class=\'fas fa-check\'></i>';">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══ Modal: Senha Temporária ═══ -->
<div class="modal fade" id="tempPasswordModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-key me-2"></i> Nova Senha</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control fw-bold" id="tempPasswordValue" readonly>
                    <button class="btn btn-outline-primary" type="button"
                            onclick="navigator.clipboard.writeText(document.getElementById('tempPasswordValue').value); this.innerHTML='<i class=\'fas fa-check\'></i>';">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Form oculto para exclusão -->
<form id="deleteAccessForm" method="POST" action="?page=portal_admin&action=delete" style="display:none;">
    <?= csrf_field() ?>
    <input type="hidden" name="id" id="deleteAccessId">
</form>

<script>
function sendMagicLink(accessId) {
    if (!confirm('Gerar link mágico para este acesso?')) return;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    fetch('?page=portal_admin&action=sendMagicLink', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: `id=${accessId}&csrf_token=${csrfToken}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success && data.magic_link) {
            document.getElementById('magicLinkUrl').value = data.magic_link;
            new bootstrap.Modal(document.getElementById('magicLinkModal')).show();
        } else {
            alert(data.message || 'Erro.');
        }
    });
}

function resetPortalPassword(accessId) {
    if (!confirm('Resetar a senha? Uma nova senha temporária será gerada.')) return;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    fetch('?page=portal_admin&action=resetPassword', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: `id=${accessId}&csrf_token=${csrfToken}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success && data.temp_password) {
            document.getElementById('tempPasswordValue').value = data.temp_password;
            new bootstrap.Modal(document.getElementById('tempPasswordModal')).show();
        } else {
            alert(data.message || 'Erro.');
        }
    });
}

function forceLogoutAccess(accessId) {
    if (!confirm('Forçar logout de todas as sessões ativas deste acesso?')) return;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    fetch('?page=portal_admin&action=forceLogout', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: `id=${accessId}&csrf_token=${csrfToken}`
    })
    .then(r => r.json())
    .then(data => {
        alert(data.message || 'Concluído.');
    });
}

function deletePortalAccess(accessId) {
    if (!confirm('ATENÇÃO: Excluir este acesso ao portal? Esta ação não pode ser desfeita.')) return;
    document.getElementById('deleteAccessId').value = accessId;
    document.getElementById('deleteAccessForm').submit();
}
</script>
