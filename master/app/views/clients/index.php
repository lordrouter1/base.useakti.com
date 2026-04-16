<?php
/**
 * View: Clients - Listagem
 */
$pageTitle = 'Clientes';
$pageSubtitle = 'Gerencie os tenants do sistema';
$topbarActions = '<a href="?page=clients&action=create" class="btn btn-akti"><i class="fas fa-plus me-2"></i>Novo Cliente</a>';

// Scripts específicos desta página (serão renderizados no footer após jQuery/Bootstrap/Swal)
$pageScripts = <<<'SCRIPTS'
<script>
// Variável global para o nome do banco alvo da exclusão
var deleteTargetDbName = '';

// =========================================
// Funções globais chamadas via onclick
// =========================================
function openDeleteModal(id, name, dbName) {
    deleteTargetDbName = dbName;

    document.getElementById('deleteClientId').value = id;
    document.getElementById('deleteClientName').textContent = name;
    document.getElementById('deleteDbNameDisplay').textContent = dbName;
    document.getElementById('deleteDbNameHint').textContent = dbName;

    document.getElementById('confirmDbNameInput').value = '';
    document.getElementById('confirmDbNameInput').className = 'form-control';
    document.getElementById('adminPasswordInput').value = '';
    document.getElementById('confirmDeleteBtn').disabled = true;

    var modal = new bootstrap.Modal(document.getElementById('deleteClientModal'));
    modal.show();
}

function toggleDeletePassword() {
    var input = document.getElementById('adminPasswordInput');
    var icon = document.getElementById('deletePasswordIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
}

function toggleClient(id, name, isActive) {
    var action = isActive ? 'desativar' : 'ativar';
    var iconType = isActive ? 'warning' : 'question';
    
    Swal.fire({
        title: (isActive ? 'Desativar' : 'Ativar') + ' cliente?',
        html: 'Deseja realmente <strong>' + action + '</strong> o cliente <strong>' + name + '</strong>?' + (isActive ? '<br><small class="text-muted">O cliente não poderá acessar o sistema enquanto estiver inativo.</small>' : ''),
        icon: iconType,
        showCancelButton: true,
        confirmButtonColor: isActive ? '#dc3545' : '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: isActive ? 'Sim, desativar' : 'Sim, ativar',
        cancelButtonText: 'Cancelar'
    }).then(function(result) {
        if (result.isConfirmed) {
            const form = document.getElementById('toggleActiveForm');
            document.getElementById('toggleActiveId').value = id;
            form.submit();
        }
    });
}

$(document).ready(function() {
    // =========================================
    // Busca e filtros
    // =========================================
    function filterTable() {
        var search = $('#searchClients').val().toLowerCase();
        var status = $('#filterStatus').val();
        var plan = $('#filterPlan').val();

        $('#clientsTable tbody tr').each(function() {
            var text = $(this).text().toLowerCase();
            var rowStatus = $(this).data('status');
            var rowPlan = $(this).data('plan');

            var show = true;
            if (search && text.indexOf(search) === -1) show = false;
            if (status && rowStatus !== status) show = false;
            if (plan && rowPlan !== plan) show = false;

            $(this).toggle(show);
        });
    }

    $('#searchClients').on('keyup', filterTable);
    $('#filterStatus').on('change', filterTable);
    $('#filterPlan').on('change', filterTable);

    // =========================================
    // Modal de exclusão segura
    // =========================================

    // Bloquear copiar/colar no campo de confirmação do banco
    $('#confirmDbNameInput').on('paste cut copy', function(e) {
        e.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: 'Copiar/Colar desativado',
            text: 'Você deve digitar o nome do banco manualmente para confirmar a exclusão.',
            confirmButtonColor: '#dc3545',
            timer: 3000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
    });

    // Validar nome do banco em tempo real
    $('#confirmDbNameInput').on('input', function() {
        var val = $(this).val();
        var valid = (val === deleteTargetDbName);
        var hasPassword = $('#adminPasswordInput').val().length > 0;

        $(this).toggleClass('is-valid', valid).toggleClass('is-invalid', val.length > 0 && !valid);
        $('#confirmDeleteBtn').prop('disabled', !(valid && hasPassword));
    });

    // Validar senha preenchida
    $('#adminPasswordInput').on('input', function() {
        var dbValid = ($('#confirmDbNameInput').val() === deleteTargetDbName);
        var hasPassword = $(this).val().length > 0;
        $('#confirmDeleteBtn').prop('disabled', !(dbValid && hasPassword));
    });

    // Confirmação final antes de enviar
    $('#deleteClientForm').on('submit', function(e) {
        var dbName = $('#confirmDbNameInput').val();
        var password = $('#adminPasswordInput').val();

        if (dbName !== deleteTargetDbName) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Nome incorreto',
                text: 'O nome do banco de dados não confere.',
                confirmButtonColor: '#dc3545'
            });
            return false;
        }

        if (!password) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Senha obrigatória',
                text: 'Digite sua senha de administrador para confirmar.',
                confirmButtonColor: '#dc3545'
            });
            return false;
        }

        // Última confirmação via SweetAlert
        e.preventDefault();
        var form = this;

        Swal.fire({
            icon: 'warning',
            title: 'Confirmação final',
            html: '<strong class="text-danger">Tem certeza absoluta?</strong><br><br>O banco <code>' + deleteTargetDbName + '</code> e todos os dados serão <strong>permanentemente destruídos</strong>.',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-trash me-1"></i> Sim, excluir tudo!',
            cancelButtonText: 'Não, cancelar',
            reverseButtons: true,
            focusCancel: true
        }).then(function(result) {
            if (result.isConfirmed) {
                $('#confirmDeleteBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Excluindo...');
                form.submit();
            }
        });
    });

    // Limpar modal ao fechar
    $('#deleteClientModal').on('hidden.bs.modal', function() {
        deleteTargetDbName = '';
        $('#confirmDbNameInput').val('').removeClass('is-valid is-invalid');
        $('#adminPasswordInput').val('').attr('type', 'password');
        $('#deletePasswordIcon').removeClass('fa-eye-slash').addClass('fa-eye');
        $('#confirmDeleteBtn').prop('disabled', true).html('<i class="fas fa-trash me-2"></i>Excluir Cliente e Banco');
    });
});
</script>
SCRIPTS;

require_once __DIR__ . '/../layout/header.php';
?>

<?php if (empty($clients)): ?>
    <div class="card">
        <div class="card-body">
            <div class="empty-state">
                <i class="fas fa-building"></i>
                <h5>Nenhum cliente cadastrado</h5>
                <p>Cadastre o primeiro cliente para começar a utilizar o sistema multi-tenant.</p>
                <a href="?page=clients&action=create" class="btn btn-akti mt-2">
                    <i class="fas fa-plus me-2"></i>Cadastrar Primeiro Cliente
                </a>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- Filtros rápidos -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="input-group">
                <span class="input-group-text" style="background:#f8f9fa; border:2px solid #dee2e6; border-right:none; border-radius:8px 0 0 8px;">
                    <i class="fas fa-search text-muted"></i>
                </span>
                <input type="text" class="form-control" id="searchClients" placeholder="Buscar por nome, subdomínio ou banco..." 
                       style="border:2px solid #dee2e6; border-left:none; border-radius:0 8px 8px 0; padding:10px 14px;">
            </div>
        </div>
        <div class="col-md-3">
            <select class="form-select" id="filterStatus" style="border:2px solid #dee2e6; border-radius:8px; padding:10px 14px;">
                <option value="">Todos os status</option>
                <option value="active">Ativos</option>
                <option value="inactive">Inativos</option>
            </select>
        </div>
        <div class="col-md-3">
            <select class="form-select" id="filterPlan" style="border:2px solid #dee2e6; border-radius:8px; padding:10px 14px;">
                <option value="">Todos os planos</option>
                <?php 
                $planNames = array_unique(array_filter(array_column($clients, 'plan_name')));
                foreach ($planNames as $planName): ?>
                    <option value="<?= htmlspecialchars($planName) ?>"><?= htmlspecialchars($planName) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="table-container">
        <div class="table-header">
            <h5><i class="fas fa-building me-2"></i>Lista de Clientes</h5>
            <span class="badge bg-secondary"><?= count($clients) ?> clientes</span>
        </div>
        <div class="table-responsive">
            <table class="table" id="clientsTable">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Subdomínio</th>
                        <th>Plano</th>
                        <th>Banco de Dados</th>
                        <th>Limites</th>
                        <th>Status</th>
                        <th>Criado em</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clients as $client): ?>
                        <tr data-status="<?= $client['is_active'] ? 'active' : 'inactive' ?>" 
                            data-plan="<?= htmlspecialchars($client['plan_name'] ?? '') ?>">
                            <td>
                                <strong><?= htmlspecialchars($client['client_name']) ?></strong>
                            </td>
                            <td>
                                <a href="https://<?= htmlspecialchars($client['subdomain']) ?>.useakti.com" target="_blank" 
                                   class="text-decoration-none" style="color: var(--akti-primary);">
                                    <i class="fas fa-external-link-alt me-1" style="font-size:10px;"></i>
                                    <?= htmlspecialchars($client['subdomain']) ?>.useakti.com
                                </a>
                            </td>
                            <td>
                                <?php if ($client['plan_name']): ?>
                                    <span class="badge-plan"><?= htmlspecialchars($client['plan_name']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted" style="font-size:12px;">Sem plano</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <code style="font-size:12px; background:#f0f0f0; padding:3px 8px; border-radius:4px;">
                                    <?= htmlspecialchars($client['db_name']) ?>
                                </code>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    <span class="limit-badge <?= $client['max_users'] ? 'has-limit' : 'unlimited' ?>" title="Usuários">
                                        <i class="fas fa-users" style="font-size:9px;"></i> <?= $client['max_users'] ?: '∞' ?>
                                    </span>
                                    <span class="limit-badge <?= $client['max_products'] ? 'has-limit' : 'unlimited' ?>" title="Produtos">
                                        <i class="fas fa-box" style="font-size:9px;"></i> <?= $client['max_products'] ?: '∞' ?>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <span class="<?= $client['is_active'] ? 'badge-active' : 'badge-inactive' ?>">
                                    <?= $client['is_active'] ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </td>
                            <td>
                                <small class="text-muted"><?= date('d/m/Y', strtotime($client['created_at'])) ?></small>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="?page=clients&action=edit&id=<?= $client['id'] ?>" class="btn-action btn-edit" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button class="btn-action btn-toggle" title="<?= $client['is_active'] ? 'Desativar' : 'Ativar' ?>" 
                                            onclick="toggleClient(<?= $client['id'] ?>, '<?= htmlspecialchars($client['client_name'], ENT_QUOTES) ?>', <?= $client['is_active'] ?>)">
                                        <i class="fas fa-<?= $client['is_active'] ? 'ban' : 'check' ?>"></i>
                                    </button>
                                    <button class="btn-action btn-delete" title="Excluir" 
                                            onclick="openDeleteModal(<?= $client['id'] ?>, '<?= htmlspecialchars($client['client_name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($client['db_name'], ENT_QUOTES) ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal de Exclusão Segura -->
    <div class="modal fade" id="deleteClientModal" tabindex="-1" aria-labelledby="deleteClientModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border:none; border-radius:16px; overflow:hidden;">
                <div class="modal-header border-0 text-white" style="background: linear-gradient(135deg, #dc3545, #a71d2a); padding:24px 28px;">
                    <h5 class="modal-title" id="deleteClientModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>Excluir Cliente Permanentemente
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <form action="?page=clients&action=delete" method="POST" id="deleteClientForm">
                    <?= master_csrf_field() ?>
                    <input type="hidden" name="id" id="deleteClientId">
                    <div class="modal-body" style="padding:28px;">
                        <!-- Aviso de perigo -->
                        <div class="alert border-0 mb-4" style="background:#fff5f5; border-radius:12px; border-left:4px solid #dc3545 !important; border:1px solid #f5c6cb;">
                            <div class="d-flex align-items-start gap-3">
                                <i class="fas fa-skull-crossbones text-danger mt-1" style="font-size:24px;"></i>
                                <div>
                                    <strong class="text-danger d-block mb-1">ATENÇÃO: Esta ação é irreversível!</strong>
                                    <span class="text-muted" style="font-size:13px;">
                                        Ao confirmar a exclusão, o <strong>cliente</strong>, o <strong>banco de dados</strong> e <strong>todos os dados</strong> serão removidos permanentemente do servidor. Não será possível recuperar.
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Info do cliente -->
                        <div class="mb-4 p-3 rounded-3" style="background:#f8f9fa;">
                            <div class="row g-2">
                                <div class="col-12">
                                    <small class="text-muted d-block">Cliente</small>
                                    <strong id="deleteClientName" style="font-size:15px;"></strong>
                                </div>
                                <div class="col-12">
                                    <small class="text-muted d-block">Banco de dados que será excluído</small>
                                    <code id="deleteDbNameDisplay" style="font-size:15px; background:#fee; padding:4px 10px; border-radius:6px; color:#dc3545; font-weight:600;"></code>
                                </div>
                            </div>
                        </div>

                        <!-- Campo: digitar nome do banco -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-database me-1 text-danger"></i>
                                Digite o nome do banco de dados para confirmar
                            </label>
                            <input type="text" name="confirm_db_name" id="confirmDbNameInput" class="form-control" 
                                   placeholder="Digite exatamente o nome do banco..."
                                   autocomplete="off" spellcheck="false"
                                   style="border:2px solid #dee2e6; font-family:monospace; font-size:14px; padding:10px 14px;">
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>
                                Copiar/colar está <strong>desativado</strong>. Digite manualmente: <code id="deleteDbNameHint"></code>
                            </div>
                        </div>

                        <!-- Campo: senha do administrador -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-lock me-1 text-danger"></i>
                                Digite sua senha de administrador
                            </label>
                            <div class="input-group">
                                <input type="password" name="admin_password" id="adminPasswordInput" class="form-control" 
                                       placeholder="Sua senha de acesso ao painel master..."
                                       autocomplete="off"
                                       style="border:2px solid #dee2e6; padding:10px 14px;">
                                <button type="button" class="btn btn-outline-secondary" onclick="toggleDeletePassword()" title="Mostrar/ocultar">
                                    <i class="fas fa-eye" id="deletePasswordIcon"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0" style="padding:0 28px 28px 28px;">
                        <div class="d-flex gap-2 w-100">
                            <button type="button" class="btn btn-outline-secondary flex-fill px-4" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </button>
                            <button type="submit" class="btn btn-danger flex-fill px-4" id="confirmDeleteBtn" disabled>
                                <i class="fas fa-trash me-2"></i>Excluir Cliente e Banco
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Hidden form for toggleActive POST -->
<form action="?page=clients&action=toggleActive" method="POST" id="toggleActiveForm" style="display:none;">
    <?= master_csrf_field() ?>
    <input type="hidden" name="id" id="toggleActiveId">
</form>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
