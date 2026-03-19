<?php
/**
 * View: Gateways de Pagamento — Editar Configuração
 * Formulário dinâmico baseado nos campos do gateway.
 * Variáveis disponíveis via controller:
 *   $gateway            — Row do banco (payment_gateways)
 *   $credentialFields   — Campos de credencial do gateway
 *   $settingsFields     — Campos de settings do gateway
 *   $currentCredentials — Credenciais atuais (decodificadas)
 *   $currentSettings    — Settings atuais (decodificadas)
 *   $webhookUrl         — URL de webhook gerada automaticamente
 */
use Akti\Utils\Escape;
$e = new Escape();
$csrf = $_SESSION['csrf_token'] ?? '';
?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <a href="?page=payment_gateways" class="text-decoration-none text-muted me-2"><i class="fas fa-arrow-left"></i></a>
            <i class="fas fa-credit-card me-2"></i><?= $e->html($gateway['display_name']) ?>
        </h1>
        <span class="badge <?= $gateway['is_active'] ? 'bg-success' : 'bg-secondary' ?> fs-6">
            <?= $gateway['is_active'] ? 'Ativo' : 'Inativo' ?>
        </span>
    </div>

    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i><?= $e->html($_SESSION['flash_success']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle me-2"></i><?= $e->html($_SESSION['flash_error']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <form method="POST" action="?page=payment_gateways&action=update">
        <input type="hidden" name="csrf_token" value="<?= $e->attr($csrf) ?>">
        <input type="hidden" name="gateway_id" value="<?= (int)$gateway['id'] ?>">

        <div class="row g-4">
            <!-- Coluna: Configurações Gerais -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0"><i class="fas fa-sliders-h me-2"></i>Configurações Gerais</h5></div>
                    <div class="card-body">
                        <!-- Status -->
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?= $gateway['is_active'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_active"><strong>Gateway Ativo</strong></label>
                            </div>
                            <small class="text-muted">Ative para permitir cobranças por este gateway.</small>
                        </div>

                        <!-- Padrão -->
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_default" name="is_default" value="1" <?= $gateway['is_default'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_default"><strong>Gateway Padrão</strong></label>
                            </div>
                            <small class="text-muted">Usado automaticamente quando o operador não selecionar outro.</small>
                        </div>

                        <!-- Ambiente -->
                        <div class="mb-3">
                            <label for="environment" class="form-label fw-bold">Ambiente</label>
                            <select class="form-select" id="environment" name="environment">
                                <option value="sandbox" <?= ($gateway['environment'] ?? '') === 'sandbox' ? 'selected' : '' ?>>🟡 Sandbox (Teste)</option>
                                <option value="production" <?= ($gateway['environment'] ?? '') === 'production' ? 'selected' : '' ?>>🔴 Produção</option>
                            </select>
                            <small class="text-muted">Em sandbox, as transações são simuladas.</small>
                        </div>

                        <!-- Webhook Secret -->
                        <div class="mb-3">
                            <label for="webhook_secret" class="form-label fw-bold">Webhook Secret</label>
                            <input type="password" class="form-control" id="webhook_secret" name="webhook_secret"
                                   value="<?= $e->attr($gateway['webhook_secret'] ?? '') ?>"
                                   placeholder="Secret para validar assinatura dos webhooks">
                            <small class="text-muted">Usado pela API Node.js para validar webhooks recebidos.</small>
                        </div>

                        <!-- Webhook URL (read-only) -->
                        <div class="mb-0">
                            <label class="form-label fw-bold">URL de Webhook</label>
                            <div class="input-group">
                                <input type="text" class="form-control bg-light" value="<?= $e->attr($webhookUrl) ?>" readonly id="webhookUrl">
                                <button type="button" class="btn btn-outline-secondary" onclick="copyWebhookUrl()">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                            <small class="text-muted">Configure esta URL no painel do gateway para receber notificações de pagamento.</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Coluna: Credenciais -->
            <div class="col-lg-6">
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0"><i class="fas fa-key me-2"></i>Credenciais</h5></div>
                    <div class="card-body">
                        <?php foreach ($credentialFields as $field): ?>
                            <div class="mb-3">
                                <label for="credential_<?= $e->attr($field['key']) ?>" class="form-label fw-bold">
                                    <?= $e->html($field['label']) ?>
                                    <?php if ($field['required'] ?? false): ?>
                                        <span class="text-danger">*</span>
                                    <?php endif; ?>
                                </label>
                                <input type="<?= $field['type'] === 'password' ? 'password' : 'text' ?>"
                                       class="form-control"
                                       id="credential_<?= $e->attr($field['key']) ?>"
                                       name="credential_<?= $e->attr($field['key']) ?>"
                                       value="<?= $e->attr($currentCredentials[$field['key']] ?? '') ?>"
                                       placeholder="<?= $field['type'] === 'password' && !empty($currentCredentials[$field['key']]) ? '••••••••' : '' ?>"
                                       <?= ($field['required'] ?? false) ? 'required' : '' ?>>
                            </div>
                        <?php endforeach; ?>

                        <!-- Testar Conexão -->
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-outline-info" onclick="testGatewayConnection(<?= (int)$gateway['id'] ?>)">
                                <i class="fas fa-plug me-2"></i>Testar Conexão
                            </button>
                            <small class="text-muted text-center">Dica: se o teste falhar, salve as credenciais primeiro e tente novamente.</small>
                        </div>
                    </div>
                </div>

                <!-- Settings Extras -->
                <?php if (!empty($settingsFields)): ?>
                <div class="card">
                    <div class="card-header"><h5 class="mb-0"><i class="fas fa-cogs me-2"></i>Configurações Extras</h5></div>
                    <div class="card-body">
                        <?php foreach ($settingsFields as $field): ?>
                            <?php if ($field['type'] === 'readonly'): ?>
                                <!-- Campo readonly (ex: notification_url auto-gerada) — omitir no form -->
                                <?php continue; ?>
                            <?php endif; ?>
                            <div class="mb-3">
                                <label for="setting_<?= $e->attr($field['key']) ?>" class="form-label fw-bold">
                                    <?= $e->html($field['label']) ?>
                                </label>
                                <input type="<?= $field['type'] ?? 'text' ?>"
                                       class="form-control"
                                       id="setting_<?= $e->attr($field['key']) ?>"
                                       name="setting_<?= $e->attr($field['key']) ?>"
                                       value="<?= $e->attr($currentSettings[$field['key']] ?? ($field['default'] ?? '')) ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Ações -->
        <div class="d-flex justify-content-between mt-4">
            <a href="?page=payment_gateways" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Voltar
            </a>
            <div class="d-flex gap-2">
                <button type="submit" name="save_and_test" value="1" class="btn btn-outline-success">
                    <i class="fas fa-save me-1"></i><i class="fas fa-plug me-1"></i> Salvar e Testar
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Salvar Configurações
                </button>
            </div>
        </div>
    </form>
</div>

<script>
function testGatewayConnection(id) {
    // Estratégia 1: Enviar credenciais do formulário via POST (para testar antes de salvar)
    const formData = new FormData();
    formData.append('gateway_id', String(id));

    // Coletar todas as credenciais do formulário
    const credInputs = document.querySelectorAll('input[name^="credential_"]');
    let hasCredentials = false;
    credInputs.forEach(input => {
        const val = input.value.trim();
        if (val) hasCredentials = true;
        formData.append(input.name, val);
    });

    // Enviar ambiente selecionado
    const envSelect = document.getElementById('environment');
    if (envSelect) {
        formData.append('environment', envSelect.value);
    }

    // Enviar CSRF token
    const csrfInput = document.querySelector('input[name="csrf_token"]');
    if (csrfInput) {
        formData.append('csrf_token', csrfInput.value);
    }

    if (!hasCredentials) {
        Swal.fire({
            icon: 'warning',
            title: 'Credenciais Vazias',
            text: 'Preencha as credenciais antes de testar a conexão.',
            confirmButtonColor: '#3085d6'
        });
        return;
    }

    Swal.fire({title: 'Testando conexão...', allowOutsideClick: false, didOpen: () => Swal.showLoading()});

    fetch(`?page=payment_gateways&action=testConnection&id=${id}`, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfInput ? csrfInput.value : ''
        },
        body: formData
    })
        .then(r => {
            if (!r.ok && r.status === 403) {
                // CSRF falhou — pedir para recarregar a página
                Swal.fire({
                    icon: 'warning',
                    title: 'Sessão Expirada',
                    text: 'Recarregue a página e tente novamente.',
                    confirmButtonColor: '#3085d6'
                });
                return null;
            }
            return r.json();
        })
        .then(data => {
            if (!data) return;
            Swal.fire({
                icon: data.success ? 'success' : 'error',
                title: data.success ? 'Conexão OK' : 'Falha na Conexão',
                text: data.message,
                confirmButtonColor: '#3085d6'
            });
        })
        .catch(err => {
            console.error('Erro ao testar conexão:', err);
            Swal.fire({icon: 'error', title: 'Erro', text: 'Falha ao comunicar com o servidor.'});
        });
}

function copyWebhookUrl() {
    const el = document.getElementById('webhookUrl');
    navigator.clipboard.writeText(el.value).then(() => {
        Swal.fire({icon: 'success', title: 'Copiado!', text: 'URL copiada para a área de transferência.', timer: 1500, showConfirmButton: false});
    });
}

// Auto-teste após "Salvar e Testar"
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('autotest') === '1') {
        // Remover autotest da URL para não repetir ao recarregar
        urlParams.delete('autotest');
        const newUrl = window.location.pathname + '?' + urlParams.toString();
        window.history.replaceState({}, '', newUrl);

        // Executar teste com credenciais já salvas no banco (GET simples)
        setTimeout(() => {
            const gatewayId = <?= (int)$gateway['id'] ?>;
            Swal.fire({title: 'Testando conexão...', allowOutsideClick: false, didOpen: () => Swal.showLoading()});
            fetch(`?page=payment_gateways&action=testConnection&id=${gatewayId}`, {
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            })
                .then(r => r.json())
                .then(data => {
                    Swal.fire({
                        icon: data.success ? 'success' : 'error',
                        title: data.success ? 'Conexão OK' : 'Falha na Conexão',
                        text: data.message,
                        confirmButtonColor: '#3085d6'
                    });
                })
                .catch(() => Swal.fire({icon: 'error', title: 'Erro', text: 'Falha ao testar conexão.'}));
        }, 500);
    }
});
</script>
