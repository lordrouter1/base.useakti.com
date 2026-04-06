<?php
/**
 * View: Plans - Listagem
 */
$pageTitle = 'Planos';
$pageSubtitle = 'Gerencie os planos de assinatura dos clientes';
$topbarActions = '<a href="?page=master_plans&action=create" class="btn btn-akti"><i class="fas fa-plus me-2"></i>Novo Plano</a>';
?>

<?php if (empty($plans)): ?>
    <div class="card">
        <div class="card-body">
            <div class="empty-state">
                <i class="fas fa-layer-group"></i>
                <h5>Nenhum plano cadastrado</h5>
                <p>Crie o primeiro plano para começar a vincular aos seus clientes.</p>
                <a href="?page=master_plans&action=create" class="btn btn-akti mt-2">
                    <i class="fas fa-plus me-2"></i>Criar Primeiro Plano
                </a>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- Cards de Planos -->
    <div class="row g-4 mb-4">
        <?php foreach ($plans as $index => $plan): ?>
            <div class="col-xl-4 col-md-6">
                <div class="plan-card <?= $index === 1 ? 'featured' : '' ?> <?= !$plan['is_active'] ? 'opacity-50' : '' ?>">
                    <div class="plan-name">
                        <?= htmlspecialchars($plan['plan_name']) ?>
                        <?php if (!$plan['is_active']): ?>
                            <span class="badge-inactive ms-1" style="font-size:10px;">Inativo</span>
                        <?php endif; ?>
                    </div>
                    <div class="plan-price">
                        R$ <?= number_format($plan['price'], 2, ',', '.') ?>
                        <small>/mês</small>
                    </div>
                    <ul class="plan-features">
                        <li class="<?= !$plan['max_users'] ? 'unlimited' : '' ?>">
                            <i class="fas fa-<?= !$plan['max_users'] ? 'infinity' : 'check' ?>"></i>
                            <?= $plan['max_users'] ? $plan['max_users'] . ' Usuários' : 'Usuários ilimitados' ?>
                        </li>
                        <li class="<?= !$plan['max_products'] ? 'unlimited' : '' ?>">
                            <i class="fas fa-<?= !$plan['max_products'] ? 'infinity' : 'check' ?>"></i>
                            <?= $plan['max_products'] ? $plan['max_products'] . ' Produtos' : 'Produtos ilimitados' ?>
                        </li>
                        <li class="<?= !$plan['max_warehouses'] ? 'unlimited' : '' ?>">
                            <i class="fas fa-<?= !$plan['max_warehouses'] ? 'infinity' : 'check' ?>"></i>
                            <?= $plan['max_warehouses'] ? $plan['max_warehouses'] . ' Armazéns' : 'Armazéns ilimitados' ?>
                        </li>
                        <li class="<?= !$plan['max_price_tables'] ? 'unlimited' : '' ?>">
                            <i class="fas fa-<?= !$plan['max_price_tables'] ? 'infinity' : 'check' ?>"></i>
                            <?= $plan['max_price_tables'] ? $plan['max_price_tables'] . ' Tabelas de Preço' : 'Tabelas ilimitadas' ?>
                        </li>
                        <li class="<?= !$plan['max_sectors'] ? 'unlimited' : '' ?>">
                            <i class="fas fa-<?= !$plan['max_sectors'] ? 'infinity' : 'check' ?>"></i>
                            <?= $plan['max_sectors'] ? $plan['max_sectors'] . ' Setores' : 'Setores ilimitados' ?>
                        </li>
                    </ul>
                    <div class="plan-clients mb-3">
                        <i class="fas fa-users"></i> <?= $plan['total_clients'] ?> <?= $plan['total_clients'] == 1 ? 'cliente' : 'clientes' ?> vinculados
                    </div>
                    <div class="d-flex gap-2">
                        <a href="?page=master_plans&action=edit&id=<?= $plan['id'] ?>" class="btn btn-akti-outline flex-fill" style="font-size:13px;">
                            <i class="fas fa-edit me-1"></i> Editar
                        </a>
                        <button class="btn btn-outline-danger flex-fill" style="font-size:13px; border-radius:8px;" 
                                onclick="deletePlan(<?= $plan['id'] ?>, '<?= htmlspecialchars($plan['plan_name'], ENT_QUOTES) ?>', <?= $plan['total_clients'] ?>)">
                            <i class="fas fa-trash me-1"></i> Excluir
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Tabela detalhada -->
    <div class="table-container">
        <div class="table-header">
            <h5><i class="fas fa-table me-2"></i>Visão Detalhada</h5>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Plano</th>
                        <th>Preço</th>
                        <th>Usuários</th>
                        <th>Produtos</th>
                        <th>Armazéns</th>
                        <th>Tab. Preço</th>
                        <th>Setores</th>
                        <th>Clientes</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($plans as $plan): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($plan['plan_name']) ?></strong></td>
                            <td>R$ <?= number_format($plan['price'], 2, ',', '.') ?></td>
                            <td>
                                <span class="limit-badge <?= $plan['max_users'] ? 'has-limit' : 'unlimited' ?>">
                                    <?= $plan['max_users'] ?: '∞' ?>
                                </span>
                            </td>
                            <td>
                                <span class="limit-badge <?= $plan['max_products'] ? 'has-limit' : 'unlimited' ?>">
                                    <?= $plan['max_products'] ?: '∞' ?>
                                </span>
                            </td>
                            <td>
                                <span class="limit-badge <?= $plan['max_warehouses'] ? 'has-limit' : 'unlimited' ?>">
                                    <?= $plan['max_warehouses'] ?: '∞' ?>
                                </span>
                            </td>
                            <td>
                                <span class="limit-badge <?= $plan['max_price_tables'] ? 'has-limit' : 'unlimited' ?>">
                                    <?= $plan['max_price_tables'] ?: '∞' ?>
                                </span>
                            </td>
                            <td>
                                <span class="limit-badge <?= $plan['max_sectors'] ? 'has-limit' : 'unlimited' ?>">
                                    <?= $plan['max_sectors'] ?: '∞' ?>
                                </span>
                            </td>
                            <td><span class="badge bg-secondary"><?= $plan['total_clients'] ?></span></td>
                            <td>
                                <span class="<?= $plan['is_active'] ? 'badge-active' : 'badge-inactive' ?>">
                                    <?= $plan['is_active'] ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </td>
                            <td>
                                <a href="?page=master_plans&action=edit&id=<?= $plan['id'] ?>" class="btn-action btn-edit" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button class="btn-action btn-delete" title="Excluir" 
                                        onclick="deletePlan(<?= $plan['id'] ?>, '<?= htmlspecialchars($plan['plan_name'], ENT_QUOTES) ?>', <?= $plan['total_clients'] ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<script>
function deletePlan(id, name, clientCount) {
    if (clientCount > 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Não é possível excluir',
            html: `O plano <strong>${name}</strong> possui <strong>${clientCount}</strong> cliente(s) vinculado(s).<br>Remova ou migre os clientes antes de excluir o plano.`,
            confirmButtonColor: '#4f46e5'
        });
        return;
    }

    Swal.fire({
        title: 'Excluir plano?',
        html: `Deseja realmente excluir o plano <strong>${name}</strong>?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sim, excluir',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `?page=master_plans&action=delete&id=${id}`;
        }
    });
}
</script>
