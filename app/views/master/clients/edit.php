<?php
/**
 * View: Clients - Editar
 */
$pageTitle = 'Editar Cliente';
$pageSubtitle = 'Atualize os dados de: ' . htmlspecialchars($client['client_name']);
$topbarActions = '<a href="?page=master_clients" class="btn btn-akti-outline"><i class="fas fa-arrow-left me-2"></i>Voltar</a>';

// Scripts específicos desta página
$pageScripts = <<<'SCRIPTS'
<script>
$(document).ready(function() {
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
            $('.limit-field').prop('readonly', false).css('background', '');
        }
    });
});

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

function generateTenantPassword() {
    var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%';
    var password = '';
    for (var i = 0; i < 12; i++) {
        password += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    document.getElementById('tenantPasswordInput').value = password;
    document.getElementById('tenantPasswordInput').type = 'text';
    document.getElementById('tenantPasswordIcon').className = 'fas fa-eye-slash';
}

function toggleTenantPassword() {
    var input = document.getElementById('tenantPasswordInput');
    var icon = document.getElementById('tenantPasswordIcon');
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
        <form action="?page=master_clients&action=update" method="POST" class="form-card" id="clientForm">
                    <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $client['id'] ?>">

            <!-- Informações do Cliente -->
            <div class="form-section">
                <div class="form-section-title">
                    <i class="fas fa-building"></i> Informações do Cliente
                </div>
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Nome do Cliente <span class="text-danger">*</span></label>
                        <input type="text" name="client_name" class="form-control" 
                               value="<?= htmlspecialchars($client['client_name']) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Subdomínio <i class="fas fa-lock ms-1 text-muted" style="font-size:11px;" title="Não pode ser alterado após o cadastro"></i></label>
                        <div class="input-group">
                            <input type="text" class="form-control" 
                                   value="<?= htmlspecialchars($client['subdomain']) ?>" 
                                   readonly disabled style="background:#f0f0f0; cursor:not-allowed;">
                            <input type="hidden" name="subdomain" value="<?= htmlspecialchars($client['subdomain']) ?>">
                            <span class="input-group-text" style="font-size:13px;">.useakti.com</span>
                        </div>
                        <div class="form-text text-muted"><i class="fas fa-info-circle me-1"></i>O subdomínio não pode ser alterado após o cadastro.</div>
                    </div>
                    <div class="col-12">
                        <div class="subdomain-preview">
                            URL do cliente: <strong>https://<?= htmlspecialchars($client['subdomain']) ?>.useakti.com</strong>
                            <br>Banco de dados: <strong><i class="fas fa-lock me-1" style="font-size:10px;"></i><?= htmlspecialchars($client['db_name']) ?></strong>
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
                                        <?= $client['plan_id'] == $plan['id'] ? 'selected' : '' ?>
                                        data-max-users="<?= $plan['max_users'] ?? '' ?>"
                                        data-max-products="<?= $plan['max_products'] ?? '' ?>"
                                        data-max-warehouses="<?= $plan['max_warehouses'] ?? '' ?>"
                                        data-max-price-tables="<?= $plan['max_price_tables'] ?? '' ?>"
                                        data-max-sectors="<?= $plan['max_sectors'] ?? '' ?>">
                                    <?= htmlspecialchars($plan['plan_name']) ?> — R$ <?= number_format($plan['price'], 2, ',', '.') ?>/mês
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Ao alterar o plano, os limites serão atualizados conforme o novo plano.</div>
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
                        <input type="number" name="max_users" class="form-control limit-field" id="maxUsers" min="0" 
                               placeholder="Ilimitado" value="<?= $client['max_users'] ?? '' ?>"
                               <?= $client['plan_id'] ? 'readonly style="background:#f8f9fa;"' : '' ?>>
                    </div>
                    <div class="col-md-4 col-6">
                        <label class="form-label"><i class="fas fa-boxes-stacked me-1 text-akti"></i> Máx. Produtos</label>
                        <input type="number" name="max_products" class="form-control limit-field" id="maxProducts" min="0" 
                               placeholder="Ilimitado" value="<?= $client['max_products'] ?? '' ?>"
                               <?= $client['plan_id'] ? 'readonly style="background:#f8f9fa;"' : '' ?>>
                    </div>
                    <div class="col-md-4 col-6">
                        <label class="form-label"><i class="fas fa-warehouse me-1 text-akti"></i> Máx. Armazéns</label>
                        <input type="number" name="max_warehouses" class="form-control limit-field" id="maxWarehouses" min="0" 
                               placeholder="Ilimitado" value="<?= $client['max_warehouses'] ?? '' ?>"
                               <?= $client['plan_id'] ? 'readonly style="background:#f8f9fa;"' : '' ?>>
                    </div>
                    <div class="col-md-4 col-6">
                        <label class="form-label"><i class="fas fa-tags me-1 text-akti"></i> Máx. Tab. Preço</label>
                        <input type="number" name="max_price_tables" class="form-control limit-field" id="maxPriceTables" min="0" 
                               placeholder="Ilimitado" value="<?= $client['max_price_tables'] ?? '' ?>"
                               <?= $client['plan_id'] ? 'readonly style="background:#f8f9fa;"' : '' ?>>
                    </div>
                    <div class="col-md-4 col-6">
                        <label class="form-label"><i class="fas fa-industry me-1 text-akti"></i> Máx. Setores</label>
                        <input type="number" name="max_sectors" class="form-control limit-field" id="maxSectors" min="0" 
                               placeholder="Ilimitado" value="<?= $client['max_sectors'] ?? '' ?>"
                               <?= $client['plan_id'] ? 'readonly style="background:#f8f9fa;"' : '' ?>>
                    </div>
                </div>
            </div>

            <!-- Conexão do Banco de Dados -->
            <div class="form-section">
                <div class="form-section-title">
                    <i class="fas fa-database"></i> Conexão do Banco de Dados
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Nome do Banco <i class="fas fa-lock ms-1 text-muted" style="font-size:11px;" title="Não pode ser alterado"></i></label>
                        <input type="text" class="form-control" 
                               value="<?= htmlspecialchars($client['db_name']) ?>" 
                               readonly disabled style="background:#f0f0f0; cursor:not-allowed; font-family: monospace; font-size:13px;">
                        <div class="form-text text-muted"><i class="fas fa-info-circle me-1"></i>O nome do banco não pode ser alterado.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Host</label>
                        <input type="text" name="db_host" class="form-control" value="<?= htmlspecialchars($client['db_host']) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Porta</label>
                        <input type="number" name="db_port" class="form-control" value="<?= $client['db_port'] ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Charset</label>
                        <input type="text" name="db_charset" class="form-control" value="<?= htmlspecialchars($client['db_charset']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Usuário do Banco</label>
                        <input type="text" name="db_user" class="form-control" value="<?= htmlspecialchars($client['db_user']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Senha do Banco</label>
                        <div class="input-group">
                            <input type="password" name="db_password" class="form-control" id="dbPasswordInput" 
                                   value="<?= htmlspecialchars($client['db_password']) ?>">
                            <button type="button" class="btn btn-outline-secondary" onclick="toggleDbPassword()" title="Mostrar/ocultar">
                                <i class="fas fa-eye" id="dbPasswordIcon"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informações adicionais -->
            <div class="form-section">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="p-3 rounded-3" style="background:#f8f9fa;">
                            <small class="text-muted d-block mb-1">Criado em</small>
                            <strong><?= date('d/m/Y H:i', strtotime($client['created_at'])) ?></strong>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 rounded-3" style="background:#f8f9fa;">
                            <small class="text-muted d-block mb-1">Última atualização</small>
                            <strong><?= date('d/m/Y H:i', strtotime($client['updated_at'])) ?></strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status e Ações -->
            <div class="form-section mb-0" style="position:sticky; bottom:0; z-index:10; background:white; margin:-32px; margin-top:0; padding:20px 32px; border-top:2px solid #e5e7eb; box-shadow:0 -4px 12px rgba(0,0,0,0.05); border-radius:0 0 12px 12px;">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="isActive" name="is_active" 
                               <?= $client['is_active'] ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold" for="isActive">Cliente Ativo</label>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="?page=master_clients" class="btn btn-outline-secondary px-4">Cancelar</a>
                        <button type="submit" class="btn btn-akti px-4">
                            <i class="fas fa-save me-2"></i>Salvar Alterações
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <!-- Seção: Criar Primeiro Usuário no Banco do Cliente -->
        <div class="form-card mt-4" id="createUserSection">
            <div class="form-section mb-0">
                <div class="form-section-title">
                    <i class="fas fa-user-plus"></i> Criar Usuário no Banco do Cliente
                    <small class="text-muted fw-normal ms-2" style="font-size:12px;">Cria um usuário diretamente no banco <code><?= htmlspecialchars($client['db_name']) ?></code></small>
                </div>
                
                <div class="alert border-0 mb-3" style="background: linear-gradient(135deg, #fff3cd, #fef9e7); border-radius:12px;">
                    <i class="fas fa-exclamation-triangle me-2 text-warning"></i>
                    <small>Este formulário cria um usuário diretamente na tabela <code>users</code> do banco do cliente. Use para criar o primeiro acesso administrativo ou para suporte.</small>
                </div>

                <form action="?page=master_clients&action=createTenantUser" method="POST" id="createTenantUserForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="client_id" value="<?= $client['id'] ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nome completo <span class="text-danger">*</span></label>
                            <input type="text" name="user_name" class="form-control" placeholder="Nome do usuário" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">E-mail <span class="text-danger">*</span></label>
                            <input type="email" name="user_email" class="form-control" placeholder="email@exemplo.com" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Senha <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" name="user_password" class="form-control" id="tenantPasswordInput" 
                                       placeholder="Senha do usuário" required minlength="6">
                                <button type="button" class="btn btn-outline-secondary" onclick="generateTenantPassword()" title="Gerar senha aleatória">
                                    <i class="fas fa-key"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="toggleTenantPassword()" title="Mostrar/ocultar">
                                    <i class="fas fa-eye" id="tenantPasswordIcon"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Telefone</label>
                            <input type="text" name="user_phone" class="form-control" placeholder="(00) 00000-0000">
                        </div>
                        <div class="col-md-3">
                            <div class="mt-4 pt-2">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="userIsAdmin" name="user_is_admin" checked>
                                    <label class="form-check-label fw-semibold" for="userIsAdmin">Administrador</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-akti px-4" onclick="return confirm('Tem certeza que deseja criar este usuário no banco do cliente?')">
                                <i class="fas fa-user-plus me-2"></i>Criar Usuário no Banco do Cliente
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
