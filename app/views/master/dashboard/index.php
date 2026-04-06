<?php
/**
 * View: Dashboard
 */
$pageTitle = 'Dashboard';
$pageSubtitle = 'Visão geral do sistema multi-tenant';
?>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="stat-card bg-primary-gradient">
            <div class="stat-icon"><i class="fas fa-building"></i></div>
            <div class="stat-value"><?= $stats['total_clients'] ?></div>
            <div class="stat-label">Total de Clientes</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card bg-success-gradient">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-value"><?= $stats['active_clients'] ?></div>
            <div class="stat-label">Clientes Ativos</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card bg-danger-gradient">
            <div class="stat-icon"><i class="fas fa-ban"></i></div>
            <div class="stat-value"><?= $stats['inactive_clients'] ?></div>
            <div class="stat-label">Clientes Inativos</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card bg-info-gradient">
            <div class="stat-icon"><i class="fas fa-layer-group"></i></div>
            <div class="stat-value"><?= $stats['total_plans'] ?></div>
            <div class="stat-label">Planos Ativos</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Clientes por Plano -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="fas fa-chart-bar text-akti"></i>
                <strong>Clientes por Plano</strong>
            </div>
            <div class="card-body">
                <?php if (empty($stats['clients_by_plan'])): ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-2 opacity-25"></i>
                        <p class="mb-0" style="font-size:13px;">Nenhum plano cadastrado</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($stats['clients_by_plan'] as $item): ?>
                        <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge-plan"><?= htmlspecialchars($item['plan_name']) ?></span>
                            </div>
                            <strong class="text-akti"><?= $item['total'] ?> <?= $item['total'] == 1 ? 'cliente' : 'clientes' ?></strong>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Últimos Clientes -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-clock text-akti"></i>
                    <strong>Últimos Clientes</strong>
                </div>
                <a href="?page=master_clients" class="btn btn-sm btn-akti-outline" style="font-size:12px;">Ver todos</a>
            </div>
            <div class="card-body">
                <?php if (empty($stats['recent_clients'])): ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-user-plus fa-2x mb-2 opacity-25"></i>
                        <p class="mb-0" style="font-size:13px;">Nenhum cliente cadastrado</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($stats['recent_clients'] as $client): ?>
                        <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                            <div>
                                <strong style="font-size:14px;"><?= htmlspecialchars($client['client_name']) ?></strong>
                                <br><small class="text-muted"><?= htmlspecialchars($client['subdomain']) ?>.useakti.com</small>
                            </div>
                            <span class="<?= $client['is_active'] ? 'badge-active' : 'badge-inactive' ?>">
                                <?= $client['is_active'] ? 'Ativo' : 'Inativo' ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Atividade Recente -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="fas fa-history text-akti"></i>
                <strong>Atividade Recente</strong>
            </div>
            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                <?php if (empty($recentLogs)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-list fa-2x mb-2 opacity-25"></i>
                        <p class="mb-0" style="font-size:13px;">Nenhuma atividade registrada</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recentLogs as $log): ?>
                        <div class="activity-item">
                            <div class="activity-dot"></div>
                            <div class="activity-content">
                                <div class="activity-action">
                                    <?= htmlspecialchars($log['admin_name']) ?> — <?= htmlspecialchars($log['action']) ?>
                                </div>
                                <?php if ($log['details']): ?>
                                    <div style="font-size:12px; color:#888;"><?= htmlspecialchars(mb_strimwidth($log['details'], 0, 60, '...')) ?></div>
                                <?php endif; ?>
                                <div class="activity-time">
                                    <i class="far fa-clock"></i> <?= date('d/m/Y H:i', strtotime($log['created_at'])) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row g-4 mt-2">
    <div class="col-12">
        <div class="card">
            <div class="card-body py-4">
                <div class="row text-center">
                    <div class="col-md-4 mb-3 mb-md-0">
                        <a href="?page=master_plans&action=create" class="text-decoration-none">
                            <div class="p-3 rounded-3" style="background:#f0f4ff; transition: all 0.3s;">
                                <i class="fas fa-plus-circle fa-2x text-akti mb-2"></i>
                                <h6 class="mb-0 text-akti fw-bold">Novo Plano</h6>
                                <small class="text-muted">Criar um plano de assinatura</small>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-4 mb-3 mb-md-0">
                        <a href="?page=master_clients&action=create" class="text-decoration-none">
                            <div class="p-3 rounded-3" style="background:#f0fff4; transition: all 0.3s;">
                                <i class="fas fa-user-plus fa-2x mb-2" style="color:#28a745;"></i>
                                <h6 class="mb-0 fw-bold" style="color:#28a745;">Novo Cliente</h6>
                                <small class="text-muted">Cadastrar novo tenant</small>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="?page=master_clients" class="text-decoration-none">
                            <div class="p-3 rounded-3" style="background:#fff8f0; transition: all 0.3s;">
                                <i class="fas fa-list fa-2x mb-2" style="color:#f39c12;"></i>
                                <h6 class="mb-0 fw-bold" style="color:#f39c12;">Gerenciar Clientes</h6>
                                <small class="text-muted">Ver e editar clientes</small>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
