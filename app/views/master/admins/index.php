<?php
/**
 * View: Admin Users — Listagem
 */
$pageTitle = 'Administradores';
$pageSubtitle = 'Gerenciamento de usuários do painel Master';
$topbarActions = '<a href="?page=master_admins&action=create" class="btn btn-akti"><i class="fas fa-plus me-2"></i>Novo Admin</a>';

$pageScripts = <<<'SCRIPTS'
<script>
$(document).ready(function() {
    $(document).on('click', '.btn-delete-admin', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');

        Swal.fire({
            icon: 'warning',
            title: 'Excluir administrador?',
            html: 'Tem certeza que deseja excluir <strong>' + name + '</strong>?<br><small class="text-muted">Esta ação não pode ser desfeita.</small>',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-trash me-1"></i>Excluir',
            cancelButtonText: 'Cancelar',
            reverseButtons: true
        }).then(function(result) {
            if (result.isConfirmed) {
                $.post('?page=master_admins&action=delete', {id: id}, function(data) {
                    if (data.success) {
                        Swal.fire({icon:'success', title:'Removido!', text: data.message, timer:2000, showConfirmButton:false, toast:true, position:'top-end'});
                        setTimeout(function() { location.reload(); }, 1500);
                    } else {
                        Swal.fire({icon:'error', title:'Erro', text: data.message});
                    }
                }, 'json').fail(function() {
                    Swal.fire({icon:'error', title:'Erro', text:'Falha na comunicação com o servidor.'});
                });
            }
        });
    });
});
</script>
SCRIPTS;
?>

<!-- Stats -->
<div class="row g-4 mb-4">
    <div class="col-xl-4 col-md-6">
        <div class="stat-card" style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-value"><?= count($admins) ?></div>
            <div class="stat-label">Total de Admins</div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6">
        <div class="stat-card" style="background: linear-gradient(135deg, #10b981, #059669);">
            <div class="stat-icon"><i class="fas fa-user-check"></i></div>
            <div class="stat-value"><?= count(array_filter($admins, fn($a) => $a['is_active'])) ?></div>
            <div class="stat-label">Ativos</div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6">
        <div class="stat-card" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
            <div class="stat-icon"><i class="fas fa-shield-halved"></i></div>
            <div class="stat-value"><?= count(array_filter($admins, fn($a) => ($a['role'] ?? 'superadmin') === 'superadmin')) ?></div>
            <div class="stat-label">Super Admins</div>
        </div>
    </div>
</div>

<!-- Tabela -->
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <i class="fas fa-user-shield" style="color: var(--akti-primary);"></i>
            <strong>Administradores</strong>
        </div>
        <span class="badge bg-secondary"><?= count($admins) ?> registro(s)</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($admins)): ?>
            <div class="text-center text-muted py-5">
                <i class="fas fa-users fa-3x mb-3 opacity-25"></i>
                <p>Nenhum administrador cadastrado.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr style="background:#f8f9fa; font-size:13px;">
                            <th class="ps-3">Nome</th>
                            <th>E-mail</th>
                            <th>Papel</th>
                            <th>Status</th>
                            <th>Último Login</th>
                            <th>Criado em</th>
                            <th class="text-end pe-3">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admins as $admin): ?>
                            <?php $role = $admin['role'] ?? 'superadmin'; ?>
                            <tr style="font-size:13px;">
                                <td class="ps-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="admin-avatar" style="width:32px; height:32px; font-size:12px; background:<?= $role === 'superadmin' ? '#dc3545' : ($role === 'operator' ? '#0d6efd' : '#6c757d') ?>; border-radius:50%; color:white; display:flex; align-items:center; justify-content:center;">
                                            <?= strtoupper(substr($admin['name'], 0, 2)) ?>
                                        </div>
                                        <strong><?= htmlspecialchars($admin['name']) ?></strong>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($admin['email']) ?></td>
                                <td>
                                    <span class="badge <?= $roleLabels[$role]['badge'] ?? 'bg-secondary' ?>">
                                        <?= $roleLabels[$role]['label'] ?? ucfirst($role) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($admin['is_active']): ?>
                                        <span class="badge bg-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= $admin['last_login'] ? date('d/m/Y H:i', strtotime($admin['last_login'])) : '<span class="text-muted">Nunca</span>' ?>
                                </td>
                                <td><?= date('d/m/Y', strtotime($admin['created_at'])) ?></td>
                                <td class="text-end pe-3">
                                    <a href="?page=master_admins&action=edit&id=<?= $admin['id'] ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($admin['id'] !== ($_SESSION['master_admin_id'] ?? 0)): ?>
                                        <button class="btn btn-sm btn-outline-danger btn-delete-admin" data-id="<?= $admin['id'] ?>" data-name="<?= htmlspecialchars($admin['name']) ?>" title="Excluir">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Legenda de Papéis -->
<div class="card mt-4">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="fas fa-info-circle" style="color: var(--akti-primary);"></i>
        <strong>Níveis de Permissão</strong>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="p-3 rounded-3" style="background:#fff0f0;">
                    <h6 class="fw-bold text-danger"><i class="fas fa-crown me-2"></i>Super Admin</h6>
                    <small class="text-muted">Acesso total: gerenciar admins, deploy, deletar backups, força reset Git.</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 rounded-3" style="background:#f0f4ff;">
                    <h6 class="fw-bold text-primary"><i class="fas fa-tools me-2"></i>Operador</h6>
                    <small class="text-muted">Operações do dia a dia: migrations, backups, logs, clientes. Sem deletar ou deploy.</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 rounded-3" style="background:#f0f0f0;">
                    <h6 class="fw-bold text-secondary"><i class="fas fa-eye me-2"></i>Visualizador</h6>
                    <small class="text-muted">Apenas leitura: ver dashboard, clientes, logs. Sem ações de escrita.</small>
                </div>
            </div>
        </div>
    </div>
</div>
