<?php
/**
 * View: Migrations - Gestão de Usuários Cross-Tenant
 */
$pageTitle = 'Usuários dos Tenants';
$pageSubtitle = 'Visualize e gerencie usuários de todos os bancos de clientes';
$topbarActions = '<a href="?page=master_migrations" class="btn btn-akti-outline"><i class="fas fa-arrow-left me-2"></i>Migrações</a>';

$pageScripts = <<<'SCRIPTS'
<script>
$(document).ready(function() {
    // Filtros
    function filterUsers() {
        var search = $('#searchUsers').val().toLowerCase();
        var dbFilter = $('#filterDb').val();
        var statusFilter = $('#filterUserStatus').val();

        $('#usersTable tbody tr').each(function() {
            var text = $(this).text().toLowerCase();
            var rowDb = $(this).data('db');
            var rowActive = $(this).data('active');

            var show = true;
            if (search && text.indexOf(search) === -1) show = false;
            if (dbFilter && rowDb !== dbFilter) show = false;
            if (statusFilter === 'active' && rowActive != 1) show = false;
            if (statusFilter === 'inactive' && rowActive != 0) show = false;

            $(this).toggle(show);
        });

        // Atualizar contagem visível
        var visible = $('#usersTable tbody tr:visible').length;
        $('#visibleCount').text(visible);
    }

    $('#searchUsers').on('keyup', filterUsers);
    $('#filterDb').on('change', filterUsers);
    $('#filterUserStatus').on('change', filterUsers);

    // Toggle user status via AJAX
    $(document).on('click', '.btn-toggle-user', function() {
        var btn = $(this);
        var dbName = btn.data('db');
        var userId = btn.data('user-id');
        var userName = btn.data('user-name');
        var isActive = btn.data('active');

        var action = isActive ? 'desativar' : 'ativar';

        Swal.fire({
            title: (isActive ? 'Desativar' : 'Ativar') + ' usuário?',
            html: '<strong>' + userName + '</strong><br><small class="text-muted">Banco: ' + dbName + '</small>',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: isActive ? '#dc3545' : '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: isActive ? 'Desativar' : 'Ativar',
            cancelButtonText: 'Cancelar'
        }).then(function(result) {
            if (result.isConfirmed) {
                $.post('?page=master_migrations&action=toggleUser', {db_name: dbName, user_id: userId}, function(data) {
                    if (data.success) {
                        Swal.fire({icon:'success', title:'Atualizado!', timer:1500, showConfirmButton:false, toast:true, position:'top-end'});
                        setTimeout(function() { location.reload(); }, 800);
                    } else {
                        Swal.fire({icon:'error', title:'Erro', text:data.message});
                    }
                }, 'json').fail(function() {
                    Swal.fire({icon:'error', title:'Erro de conexão'});
                });
            }
        });
    });

    // Gerar senha aleatória
    window.generateUserPassword = function() {
        var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%';
        var password = '';
        for (var i = 0; i < 12; i++) {
            password += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        $('#newUserPassword').val(password).attr('type', 'text');
        $('#togglePasswordIcon').removeClass('fa-eye').addClass('fa-eye-slash');
    };

    window.toggleNewUserPassword = function() {
        var input = document.getElementById('newUserPassword');
        var icon = document.getElementById('togglePasswordIcon');
        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'fas fa-eye-slash';
        } else {
            input.type = 'password';
            icon.className = 'fas fa-eye';
        }
    };
});
</script>
SCRIPTS;

// Agrupar por banco para contagem
$usersByDb = [];
foreach ($allUsers as $user) {
    $db = $user['db_name'];
    if (!isset($usersByDb[$db])) $usersByDb[$db] = 0;
    $usersByDb[$db]++;
}
$totalUsers = count($allUsers);
$totalActive = count(array_filter($allUsers, fn($u) => $u['is_active']));
$totalAdmins = count(array_filter($allUsers, fn($u) => $u['is_admin']));
?>

<!-- Stats -->
<div class="row g-4 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="stat-card" style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-value"><?= $totalUsers ?></div>
            <div class="stat-label">Total de Usuários</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card" style="background: linear-gradient(135deg, #10b981, #059669);">
            <div class="stat-icon"><i class="fas fa-user-check"></i></div>
            <div class="stat-value"><?= $totalActive ?></div>
            <div class="stat-label">Ativos</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
            <div class="stat-icon"><i class="fas fa-user-shield"></i></div>
            <div class="stat-value"><?= $totalAdmins ?></div>
            <div class="stat-label">Administradores</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card bg-primary-gradient">
            <div class="stat-icon"><i class="fas fa-database"></i></div>
            <div class="stat-value"><?= count($usersByDb) ?></div>
            <div class="stat-label">Bancos com Usuários</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Tabela de usuários -->
    <div class="col-lg-8">
        <!-- Filtros -->
        <div class="row g-3 mb-3">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text" style="background:#f8f9fa; border:2px solid #dee2e6; border-right:none; border-radius:8px 0 0 8px;">
                        <i class="fas fa-search text-muted"></i>
                    </span>
                    <input type="text" class="form-control" id="searchUsers" placeholder="Buscar por nome, email, banco..." 
                           style="border:2px solid #dee2e6; border-left:none; border-radius:0 8px 8px 0; padding:10px 14px;">
                </div>
            </div>
            <div class="col-md-4">
                <select class="form-select" id="filterDb" style="border:2px solid #dee2e6; border-radius:8px; padding:10px 14px;">
                    <option value="">Todos os bancos</option>
                    <?php foreach ($tenants as $t): ?>
                        <option value="<?= htmlspecialchars($t['db_name']) ?>">
                            <?= htmlspecialchars($t['db_name']) ?> (<?= htmlspecialchars($t['client_name']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="filterUserStatus" style="border:2px solid #dee2e6; border-radius:8px; padding:10px 14px;">
                    <option value="">Todos</option>
                    <option value="active">Ativos</option>
                    <option value="inactive">Inativos</option>
                </select>
            </div>
        </div>

        <div class="table-container">
            <div class="table-header">
                <h5><i class="fas fa-users me-2"></i>Usuários dos Tenants</h5>
                <span class="badge bg-secondary"><span id="visibleCount"><?= $totalUsers ?></span> de <?= $totalUsers ?></span>
            </div>
            <div class="table-responsive">
                <table class="table" id="usersTable">
                    <thead>
                        <tr>
                            <th>Usuário</th>
                            <th>Banco / Cliente</th>
                            <th>Grupo</th>
                            <th>Status</th>
                            <th>Criado em</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($allUsers)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="fas fa-users fa-3x mb-3 opacity-25"></i>
                                    <p class="mb-0">Nenhum usuário encontrado nos bancos tenant.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($allUsers as $user): ?>
                                <tr data-db="<?= htmlspecialchars($user['db_name']) ?>" 
                                    data-active="<?= $user['is_active'] ?>">
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" 
                                                 style="width:32px; height:32px; background:<?= $user['is_admin'] ? 'linear-gradient(135deg, #f59e0b, #d97706)' : 'linear-gradient(135deg, #6366f1, #8b5cf6)' ?>; color:#fff; font-size:12px; font-weight:600;">
                                                <?= strtoupper(mb_substr($user['name'], 0, 2)) ?>
                                            </div>
                                            <div>
                                                <strong style="font-size:13px;"><?= htmlspecialchars($user['name']) ?></strong>
                                                <?php if ($user['is_admin']): ?>
                                                    <i class="fas fa-shield-halved text-warning ms-1" title="Admin" style="font-size:10px;"></i>
                                                <?php endif; ?>
                                                <br>
                                                <small class="text-muted"><?= htmlspecialchars($user['email']) ?></small>
                                                <?php if ($user['phone']): ?>
                                                    <small class="text-muted ms-1">| <?= htmlspecialchars($user['phone']) ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <code style="font-size:11px; background:#f0f0f0; padding:2px 6px; border-radius:4px;"><?= htmlspecialchars($user['db_name']) ?></code>
                                        <br>
                                        <small class="text-muted"><?= htmlspecialchars($user['client_name']) ?></small>
                                        <?php if (!$user['tenant_active']): ?>
                                            <span class="badge bg-secondary" style="font-size:9px;">Tenant inativo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($user['group_name']): ?>
                                            <span class="badge bg-light text-dark border" style="font-size:11px;"><?= htmlspecialchars($user['group_name']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted" style="font-size:11px;">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($user['is_active']): ?>
                                            <span class="badge-active">Ativo</span>
                                        <?php else: ?>
                                            <span class="badge-inactive">Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?= $user['created_at'] ? date('d/m/Y', strtotime($user['created_at'])) : '—' ?></small>
                                    </td>
                                    <td>
                                        <button class="btn-action btn-toggle btn-toggle-user" 
                                                title="<?= $user['is_active'] ? 'Desativar' : 'Ativar' ?>"
                                                data-db="<?= htmlspecialchars($user['db_name']) ?>"
                                                data-user-id="<?= $user['id'] ?>"
                                                data-user-name="<?= htmlspecialchars($user['name']) ?>"
                                                data-active="<?= $user['is_active'] ?>">
                                            <i class="fas fa-<?= $user['is_active'] ? 'ban' : 'check' ?>"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Sidebar: Cadastrar novo usuário -->
    <div class="col-lg-4">
        <div class="card" style="position:sticky; top:80px;">
            <div class="card-header d-flex align-items-center gap-2" style="background: linear-gradient(135deg, var(--m-primary), #6366f1); color:#fff;">
                <i class="fas fa-user-plus"></i>
                <strong>Cadastrar Usuário</strong>
            </div>
            <div class="card-body">
                <form action="?page=master_migrations&action=createUser" method="POST">
                    <?= csrf_field() ?>
                    <!-- Banco de destino -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="newUserDb">
                            <i class="fas fa-database me-1 text-muted"></i>Banco de Dados
                        </label>
                        <select name="db_name" id="newUserDb" class="form-select" required
                                style="border:2px solid #dee2e6; border-radius:8px; padding:10px 14px;">
                            <option value="">Selecione o banco...</option>
                            <?php foreach ($tenants as $t): ?>
                                <option value="<?= htmlspecialchars($t['db_name']) ?>" <?= !$t['is_active'] ? 'class="text-muted"' : '' ?>>
                                    <?= htmlspecialchars($t['db_name']) ?> — <?= htmlspecialchars($t['client_name']) ?>
                                    <?= !$t['is_active'] ? '(inativo)' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Nome -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="newUserName">
                            <i class="fas fa-user me-1 text-muted"></i>Nome completo
                        </label>
                        <input type="text" name="user_name" id="newUserName" class="form-control" required
                               placeholder="Nome do usuário"
                               style="border:2px solid #dee2e6; border-radius:8px; padding:10px 14px;">
                    </div>

                    <!-- Email -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="newUserEmail">
                            <i class="fas fa-envelope me-1 text-muted"></i>E-mail
                        </label>
                        <input type="email" name="user_email" id="newUserEmail" class="form-control" required
                               placeholder="email@empresa.com"
                               style="border:2px solid #dee2e6; border-radius:8px; padding:10px 14px;">
                    </div>

                    <!-- Senha -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="fas fa-lock me-1 text-muted"></i>Senha
                        </label>
                        <div class="input-group">
                            <input type="password" name="user_password" id="newUserPassword" class="form-control" required minlength="6"
                                   placeholder="Mínimo 6 caracteres"
                                   style="border:2px solid #dee2e6; border-right:none; border-radius:8px 0 0 8px; padding:10px 14px;">
                            <button type="button" class="btn btn-outline-secondary" onclick="toggleNewUserPassword()" style="border:2px solid #dee2e6; border-left:none;">
                                <i class="fas fa-eye" id="togglePasswordIcon"></i>
                            </button>
                            <button type="button" class="btn btn-outline-primary" onclick="generateUserPassword()" title="Gerar senha" style="border:2px solid #dee2e6; border-left:none; border-radius:0 8px 8px 0;">
                                <i class="fas fa-dice"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Telefone -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="newUserPhone">
                            <i class="fas fa-phone me-1 text-muted"></i>Telefone <small class="text-muted fw-normal">(opcional)</small>
                        </label>
                        <input type="text" name="user_phone" id="newUserPhone" class="form-control"
                               placeholder="(00) 00000-0000"
                               style="border:2px solid #dee2e6; border-radius:8px; padding:10px 14px;">
                    </div>

                    <!-- Admin? -->
                    <div class="form-check mb-4">
                        <input type="checkbox" name="user_is_admin" id="newUserAdmin" class="form-check-input" value="1">
                        <label class="form-check-label" for="newUserAdmin">
                            <strong>Administrador</strong>
                            <br><small class="text-muted">Terá acesso completo ao sistema do cliente</small>
                        </label>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-akti">
                            <i class="fas fa-user-plus me-2"></i>Cadastrar Usuário
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>
