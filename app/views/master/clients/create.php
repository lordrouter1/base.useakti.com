<?php
/**
 * View: Clients - Criar
 */
$pageTitle = 'Novo Cliente';
$pageSubtitle = 'Cadastre um novo tenant no sistema';
$topbarActions = '<a href="?page=master_clients" class="btn btn-akti-outline"><i class="fas fa-arrow-left me-2"></i>Voltar</a>';

// Scripts específicos desta página
$pageScripts = <<<'SCRIPTS'
<script>
$(document).ready(function() {
    // Preview do subdomínio e auto-fill de campos
    $('#subdomainInput').on('input', function() {
        var val = $(this).val().toLowerCase().replace(/[^a-z0-9]/g, '');
        $(this).val(val);
        $('#subdomainDisplay').text(val || '___');
        $('#dbNameDisplay').text(val || '___');
        if (val) {
            $('#dbUserInput').val('akti_' + val + '_user');
        } else {
            $('#dbUserInput').val('');
        }
    });

    // Selecionar plano -> preencher limites
    $('#planSelect').on('change', function() {
        var selected = $(this).find(':selected');
        if (selected.val()) {
            $('#maxUsers').val(selected.data('max-users') || '');
            $('#maxProducts').val(selected.data('max-products') || '');
            $('#maxWarehouses').val(selected.data('max-warehouses') || '');
            $('#maxPriceTables').val(selected.data('max-price-tables') || '');
            $('#maxSectors').val(selected.data('max-sectors') || '');
            $('.limit-field').prop('readonly', true).css('background', '#f8f9fa');
        } else {
            $('.limit-field').val('').prop('readonly', false).css('background', '');
        }
    });

    // Toggle seção de primeiro usuário
    $('#createFirstUser').on('change', function() {
        if ($(this).is(':checked')) {
            $('#firstUserFields').slideDown(200);
            $('.first-user-field').prop('required', true);
        } else {
            $('#firstUserFields').slideUp(200);
            $('.first-user-field').prop('required', false);
        }
    });

    // Se desmarcou criar banco, esconder primeiro usuário também
    $('#createDatabase').on('change', function() {
        if (!$(this).is(':checked')) {
            $('#firstUserSection').slideUp(200);
            $('#createFirstUser').prop('checked', false).trigger('change');
        } else {
            $('#firstUserSection').slideDown(200);
        }
    });
});

function generatePassword() {
    var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%';
    var password = '';
    for (var i = 0; i < 16; i++) {
        password += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    document.getElementById('dbPasswordInput').value = password;
    document.getElementById('dbPasswordInput').type = 'text';
    document.getElementById('dbPasswordIcon').className = 'fas fa-eye-slash';
}

function toggleDbPassword() {
    var input = document.getElementById('dbPasswordInput');
    var icon = document.getElementById('dbPasswordIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
}

function generateFirstUserPassword() {
    var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%';
    var password = '';
    for (var i = 0; i < 12; i++) {
        password += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    document.getElementById('firstUserPasswordInput').value = password;
    document.getElementById('firstUserPasswordInput').type = 'text';
    document.getElementById('firstUserPasswordIcon').className = 'fas fa-eye-slash';
}

function toggleFirstUserPassword() {
    var input = document.getElementById('firstUserPasswordInput');
    var icon = document.getElementById('firstUserPasswordIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
}
</script>
SCRIPTS;

?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <form action="?page=master_clients&action=store" method="POST" class="form-card" id="clientForm">
                    <?= csrf_field() ?>
            
            <!-- Informações do Cliente -->
            <div class="form-section">
                <div class="form-section-title">
                    <i class="fas fa-building"></i> Informações do Cliente
                </div>
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Nome do Cliente <span class="text-danger">*</span></label>
                        <input type="text" name="client_name" class="form-control" placeholder="Nome da empresa" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Subdomínio <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" name="subdomain" class="form-control" id="subdomainInput" 
                                   placeholder="cliente" required pattern="[a-z0-9]+"
                                   title="Apenas letras minúsculas e números">
                            <span class="input-group-text" style="font-size:13px;">.useakti.com</span>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="subdomain-preview" id="subdomainPreview">
                            URL do cliente: <strong>https://<span id="subdomainDisplay">___</span>.useakti.com</strong>
                            <br>Banco de dados: <strong>akti_<span id="dbNameDisplay">___</span></strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Plano -->
            <div class="form-section">
                <div class="form-section-title">
                    <i class="fas fa-layer-group"></i> Plano
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Selecionar Plano</label>
                        <select name="plan_id" class="form-select" id="planSelect">
                            <option value="">Sem plano (limites personalizados)</option>
                            <?php foreach ($plans as $plan): ?>
                                <option value="<?= $plan['id'] ?>" 
                                        data-max-users="<?= $plan['max_users'] ?? '' ?>"
                                        data-max-products="<?= $plan['max_products'] ?? '' ?>"
                                        data-max-warehouses="<?= $plan['max_warehouses'] ?? '' ?>"
                                        data-max-price-tables="<?= $plan['max_price_tables'] ?? '' ?>"
                                        data-max-sectors="<?= $plan['max_sectors'] ?? '' ?>">
                                    <?= htmlspecialchars($plan['plan_name']) ?> — R$ <?= number_format($plan['price'], 2, ',', '.') ?>/mês
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Ao selecionar um plano, os limites serão preenchidos automaticamente.</div>
                    </div>
                </div>
            </div>

            <!-- Limites -->
            <div class="form-section" id="limitsSection">
                <div class="form-section-title">
                    <i class="fas fa-sliders-h"></i> Limites
                    <small class="text-muted fw-normal ms-2" style="font-size:12px;">Deixe em branco para ilimitado</small>
                </div>
                <div class="row g-3">
                    <div class="col-md-4 col-6">
                        <label class="form-label"><i class="fas fa-users me-1 text-akti"></i> Máx. Usuários</label>
                        <input type="number" name="max_users" class="form-control limit-field" id="maxUsers" min="0" placeholder="Ilimitado">
                    </div>
                    <div class="col-md-4 col-6">
                        <label class="form-label"><i class="fas fa-boxes-stacked me-1 text-akti"></i> Máx. Produtos</label>
                        <input type="number" name="max_products" class="form-control limit-field" id="maxProducts" min="0" placeholder="Ilimitado">
                    </div>
                    <div class="col-md-4 col-6">
                        <label class="form-label"><i class="fas fa-warehouse me-1 text-akti"></i> Máx. Armazéns</label>
                        <input type="number" name="max_warehouses" class="form-control limit-field" id="maxWarehouses" min="0" placeholder="Ilimitado">
                    </div>
                    <div class="col-md-4 col-6">
                        <label class="form-label"><i class="fas fa-tags me-1 text-akti"></i> Máx. Tab. Preço</label>
                        <input type="number" name="max_price_tables" class="form-control limit-field" id="maxPriceTables" min="0" placeholder="Ilimitado">
                    </div>
                    <div class="col-md-4 col-6">
                        <label class="form-label"><i class="fas fa-industry me-1 text-akti"></i> Máx. Setores</label>
                        <input type="number" name="max_sectors" class="form-control limit-field" id="maxSectors" min="0" placeholder="Ilimitado">
                    </div>
                </div>
            </div>

            <!-- Conexão do Banco de Dados -->
            <div class="form-section">
                <div class="form-section-title">
                    <i class="fas fa-database"></i> Conexão do Banco de Dados
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Host</label>
                        <input type="text" name="db_host" class="form-control" value="localhost">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Porta</label>
                        <input type="number" name="db_port" class="form-control" value="3306">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Charset</label>
                        <input type="text" name="db_charset" class="form-control" value="utf8mb4">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Usuário do Banco</label>
                        <input type="text" name="db_user" class="form-control" placeholder="Ex: akti_cliente1_user" id="dbUserInput">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Senha do Banco</label>
                        <div class="input-group">
                            <input type="password" name="db_password" class="form-control" id="dbPasswordInput" placeholder="Senha do usuário MySQL">
                            <button type="button" class="btn btn-outline-secondary" onclick="generatePassword()" title="Gerar senha aleatória">
                                <i class="fas fa-key"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="toggleDbPassword()" title="Mostrar/ocultar">
                                <i class="fas fa-eye" id="dbPasswordIcon"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Opção de criar o banco automaticamente -->
                <div class="mt-4">
                    <div class="alert border-0" style="background: linear-gradient(135deg, #e8f4fd, #f0f4ff); border-radius:12px;">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="createDatabase" name="create_database" checked>
                            <label class="form-check-label" for="createDatabase">
                                <strong><i class="fas fa-clone me-1"></i> Criar banco de dados automaticamente</strong>
                                <br><small class="text-muted">O sistema criará o banco de dados clonando a base <code>akti_init_base</code> com toda a estrutura e dados iniciais.</small>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Primeiro Usuário do Cliente -->
            <div class="form-section" id="firstUserSection">
                <div class="form-section-title">
                    <i class="fas fa-user-plus"></i> Primeiro Usuário do Cliente
                    <small class="text-muted fw-normal ms-2" style="font-size:12px;">Opcional — cria o primeiro acesso admin no banco do cliente</small>
                </div>
                
                <div class="mb-3">
                    <div class="alert border-0" style="background: linear-gradient(135deg, #e8fdf0, #f0fff4); border-radius:12px;">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="createFirstUser" name="create_first_user">
                            <label class="form-check-label" for="createFirstUser">
                                <strong><i class="fas fa-user-shield me-1"></i> Criar primeiro usuário administrador</strong>
                                <br><small class="text-muted">Além do admin padrão do template, cria um usuário personalizado com as credenciais abaixo.</small>
                            </label>
                        </div>
                    </div>
                </div>

                <div id="firstUserFields" style="display:none;">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nome completo <span class="text-danger">*</span></label>
                            <input type="text" name="first_user_name" class="form-control first-user-field" placeholder="Nome do usuário">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">E-mail <span class="text-danger">*</span></label>
                            <input type="email" name="first_user_email" class="form-control first-user-field" placeholder="email@exemplo.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Senha <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" name="first_user_password" class="form-control first-user-field" id="firstUserPasswordInput" 
                                       placeholder="Senha do usuário" minlength="6">
                                <button type="button" class="btn btn-outline-secondary" onclick="generateFirstUserPassword()" title="Gerar senha aleatória">
                                    <i class="fas fa-key"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="toggleFirstUserPassword()" title="Mostrar/ocultar">
                                    <i class="fas fa-eye" id="firstUserPasswordIcon"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Telefone</label>
                            <input type="text" name="first_user_phone" class="form-control" placeholder="(00) 00000-0000">
                        </div>
                        <div class="col-md-3">
                            <div class="mt-4 pt-2">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="firstUserIsAdmin" name="first_user_is_admin" checked>
                                    <label class="form-check-label fw-semibold" for="firstUserIsAdmin">Administrador</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status e Ações -->
            <div class="form-section mb-0" style="position:sticky; bottom:0; z-index:10; background:white; margin:-32px; margin-top:0; padding:20px 32px; border-top:2px solid #e5e7eb; box-shadow:0 -4px 12px rgba(0,0,0,0.05); border-radius:0 0 12px 12px;">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="isActive" name="is_active" checked>
                        <label class="form-check-label fw-semibold" for="isActive">Cliente Ativo</label>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="?page=master_clients" class="btn btn-outline-secondary px-4">Cancelar</a>
                        <button type="submit" class="btn btn-akti px-4">
                            <i class="fas fa-save me-2"></i>Salvar Cliente
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
