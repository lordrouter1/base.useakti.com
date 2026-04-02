<!-- Dashboard Header -->
<div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
    <h1 class="h2 mb-0"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h1>
    <div class="btn-toolbar gap-2">
        <?php if(!empty($delayedOrders)): ?>
        <a href="?page=pipeline" class="btn btn-sm btn-danger position-relative">
            <i class="fas fa-exclamation-triangle me-1"></i> Atrasados
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-dark">
                <?= count($delayedOrders) ?>
            </span>
        </a>
        <?php endif; ?>
        <a href="?page=pipeline" class="btn btn-sm btn-primary">
            <i class="fas fa-stream me-1"></i> Linha de Produção
        </a>
    </div>
</div>

<!-- Cards de Resumo Geral -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
            <div class="card-body d-flex align-items-center p-3">
                <div class="icon-circle icon-circle-xxl icon-circle-primary me-3">
                    <i class="fas fa-shopping-cart fa-lg text-primary"></i>
                </div>
                <div>
                    <div class="text-muted small text-uppercase">Total de Pedidos</div>
                    <div class="fw-bold fs-4"><?= $totalOrders ?? 0 ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
            <div class="card-body d-flex align-items-center p-3">
                <div class="icon-circle icon-circle-xxl icon-circle-green me-3">
                    <i class="fas fa-users fa-lg text-success"></i>
                </div>
                <div>
                    <div class="text-muted small text-uppercase">Clientes</div>
                    <div class="fw-bold fs-4"><?= $totalCustomers ?? 0 ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100 border-start border-warning border-4">
            <div class="card-body d-flex align-items-center p-3">
                <div class="icon-circle icon-circle-xxl icon-circle-warning me-3">
                    <i class="fas fa-tasks fa-lg text-warning"></i>
                </div>
                <div>
                    <div class="text-muted small text-uppercase">Em Andamento</div>
                    <div class="fw-bold fs-4"><?= $pipelineStats['total_active'] ?? 0 ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100 border-start border-danger border-4">
            <div class="card-body d-flex align-items-center p-3">
                <div class="icon-circle icon-circle-xxl icon-circle-danger me-3">
                    <i class="fas fa-exclamation-circle fa-lg text-danger"></i>
                </div>
                <div>
                    <div class="text-muted small text-uppercase">Atrasados</div>
                    <div class="fw-bold fs-4 <?= ($pipelineStats['total_delayed'] ?? 0) > 0 ? 'text-danger' : '' ?>"><?= $pipelineStats['total_delayed'] ?? 0 ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Pipeline Mini Overview -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-primary fw-bold"><i class="fas fa-stream me-2"></i>Visão do Pipeline</h5>
        <a href="?page=pipeline" class="btn btn-sm btn-outline-primary">Ver Kanban Completo <i class="fas fa-arrow-right ms-1"></i></a>
    </div>
    <div class="card-body p-3">
        <div class="row g-2">
            <?php foreach ($stages as $sKey => $sInfo): ?>
            <?php 
                $count = $pipelineStats['by_stage'][$sKey] ?? 0;
                if ($sKey === 'concluido') continue; // Pular concluído da barra principal
            ?>
            <div class="col">
                <a href="?page=pipeline" class="text-decoration-none">
                    <div class="text-center p-2 rounded pipeline-mini-card" style="background:<?= $sInfo['color'] ?>15; border:1px solid <?= $sInfo['color'] ?>30;">
                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center mx-auto mb-1" 
                             style="width:36px;height:36px;background:<?= $sInfo['color'] ?>;color:#fff;font-size:0.8rem;">
                            <i class="<?= $sInfo['icon'] ?>"></i>
                        </div>
                        <div class="fw-bold fs-5" style="color:<?= $sInfo['color'] ?>;"><?= $count ?></div>
                        <div class="text-muted" style="font-size:0.7rem;"><?= $sInfo['label'] ?></div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Valor em aberto -->
        <div class="row mt-3">
            <div class="col-md-4">
                <div class="bg-light rounded p-2 text-center">
                    <div class="text-muted small">Valor em Aberto</div>
                    <div class="fw-bold text-primary fs-5">R$ <?= number_format($pipelineStats['total_value'] ?? 0, 2, ',', '.') ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="bg-light rounded p-2 text-center">
                    <div class="text-muted small">Concluídos no Mês</div>
                    <div class="fw-bold text-success fs-5"><?= $pipelineStats['completed_month'] ?? 0 ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="bg-light rounded p-2 text-center">
                    <div class="text-muted small">Valor Total Ativo</div>
                    <div class="fw-bold text-warning fs-5">R$ <?= number_format($totalActiveValue ?? 0, 2, ',', '.') ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Pedidos Atrasados (se houver) -->
<?php if(!empty($delayedOrders)): ?>
<div class="card border-0 shadow-sm mb-4 border-start border-danger border-4">
    <div class="card-header bg-danger p-3">
        <h6 class="mb-0 text-white"><i class="fas fa-exclamation-triangle me-2"></i>Pedidos Atrasados (<?= count($delayedOrders) ?>)</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <caption class="visually-hidden">Pedidos com atraso</caption>
                <thead class="bg-light">
                    <tr>
                        <th class="ps-3 small fw-bold">Pedido</th>
                        <th class="small fw-bold">Cliente</th>
                        <th class="small fw-bold">Etapa</th>
                        <th class="small fw-bold">Atraso</th>
                        <th class="text-end pe-3 small fw-bold">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($delayedOrders, 0, 5) as $dOrder): ?>
                    <tr>
                        <td class="ps-3 fw-bold">#<?= str_pad($dOrder['id'], 4, '0', STR_PAD_LEFT) ?></td>
                        <td class="small"><?= $dOrder['customer_name'] ?? '—' ?></td>
                        <td>
                            <?php $dStage = $dOrder['pipeline_stage'] ?? 'contato'; ?>
                            <span class="badge rounded-pill" style="background:<?= $stages[$dStage]['color'] ?? '#999' ?>;font-size:0.7rem;">
                                <i class="<?= $stages[$dStage]['icon'] ?? 'fas fa-circle' ?> me-1"></i>
                                <?= $stages[$dStage]['label'] ?? $dStage ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-danger rounded-pill">+<?= $dOrder['delay_hours'] ?>h atrasado</span>
                        </td>
                        <td class="text-end pe-3">
                            <a href="?page=pipeline&action=detail&id=<?= $dOrder['id'] ?>" class="btn btn-sm btn-outline-danger">
                                <i class="fas fa-eye me-1"></i> Resolver
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if(count($delayedOrders) > 5): ?>
        <div class="p-2 text-center border-top">
            <a href="?page=pipeline" class="text-danger small fw-bold"><i class="fas fa-arrow-right me-1"></i> Ver todos os <?= count($delayedOrders) ?> atrasados</a>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Atalhos Rápidos -->
<h5 class="text-muted mb-3 mt-4"><i class="fas fa-th-large me-2"></i>Acesso Rápido</h5>
<div class="row g-4 justify-content-center">
    <!-- Atalho Clientes -->
    <div class="col-md-3 col-sm-6">
        <a href="?page=customers" class="text-decoration-none">
            <div class="card shadow-sm border-0 h-100 hover-card">
                <div class="card-body text-center p-4">
                    <div class="bg-primary  text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                    <h5 class="card-title text-dark">Clientes</h5>
                    <p class="card-text text-muted small">Gerenciar base de clientes</p>
                    <span class="badge bg-primary rounded-pill"><?= $totalCustomers ?? 0 ?></span>
                </div>
            </div>
        </a>
    </div>

    <!-- Atalho Produtos -->
    <div class="col-md-3 col-sm-6">
        <a href="?page=products" class="text-decoration-none">
            <div class="card shadow-sm border-0 h-100 hover-card">
                <div class="card-body text-center p-4">
                    <div class="bg-success  text-success rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                        <i class="fas fa-box-open fa-2x"></i>
                    </div>
                    <h5 class="card-title text-dark">Produtos</h5>
                    <p class="card-text text-muted small">Catálogo de serviços e estoque</p>
                </div>
            </div>
        </a>
    </div>

    <!-- Atalho Pedidos -->
    <div class="col-md-3 col-sm-6">
        <a href="?page=orders" class="text-decoration-none">
            <div class="card shadow-sm border-0 h-100 hover-card">
                <div class="card-body text-center p-4">
                    <div class="bg-warning  text-warning rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                        <i class="fas fa-shopping-cart fa-2x"></i>
                    </div>
                    <h5 class="card-title text-dark">Pedidos</h5>
                    <p class="card-text text-muted small">Vendas e Orçamentos</p>
                    <span class="badge bg-warning text-dark rounded-pill"><?= $totalOrders ?? 0 ?></span>
                </div>
            </div>
        </a>
    </div>

    <!-- Atalho Linha de Produção -->
    <div class="col-md-3 col-sm-6">
        <a href="?page=pipeline" class="text-decoration-none">
            <div class="card shadow-sm border-0 h-100 hover-card">
                <div class="card-body text-center p-4">
                    <div class="bg-info  text-info rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                        <i class="fas fa-stream fa-2x"></i>
                    </div>
                    <h5 class="card-title text-dark">Produção</h5>
                    <p class="card-text text-muted small">Linha de produção e controle</p>
                    <span class="badge bg-info rounded-pill"><?= $pipelineStats['total_active'] ?? 0 ?> ativos</span>
                </div>
            </div>
        </a>
    </div>

    <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
    <!-- Atalho Usuários -->
    <div class="col-md-3 col-sm-6">
        <a href="?page=users" class="text-decoration-none">
            <div class="card shadow-sm border-0 h-100 hover-card">
                <div class="card-body text-center p-4">
                    <div class="bg-danger  text-danger rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                        <i class="fas fa-user-cog fa-2x"></i>
                    </div>
                    <h5 class="card-title text-dark">Usuários</h5>
                    <p class="card-text text-muted small">Acessos e Permissões</p>
                </div>
            </div>
        </a>
    </div>
    <?php endif; ?>
</div>

<?php if(!empty($delayedOrders)): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toast de alerta para pedidos atrasados
    if (window.AktiToast) {
        AktiToast.warning('<?= count($delayedOrders) ?> pedido(s) atrasados na produção! <a href="?page=pipeline" class="text-decoration-underline fw-bold">Ver Pipeline →</a>', 6000);
    }
});
</script>
<?php endif; ?>

<!-- Dashboard styles loaded from assets/css/modules/dashboard.css via header.php -->
