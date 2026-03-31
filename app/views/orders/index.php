<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-shopping-cart me-2"></i>Pedidos</h1>
    <div class="btn-toolbar mb-2 mb-md-0 gap-2">
        <a href="?page=orders&action=agenda" class="btn btn-sm btn-outline-purple">
            <i class="fas fa-calendar-alt me-1"></i> Agenda
        </a>
        <a href="?page=pipeline" class="btn btn-sm btn-outline-info">
            <i class="fas fa-stream me-1"></i> Ver na Produção
        </a>
        <a href="?page=orders&action=create" class="btn btn-sm btn-primary">
            <i class="fas fa-plus me-1"></i> Novo Pedido
        </a>
    </div>
</div>

<!-- Busca rápida -->
<div class="mb-3">
    <div class="input-group">
        <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
        <input type="text" class="form-control" id="searchTable" placeholder="Buscar por nº pedido, cliente, etapa, status ou valor..." autocomplete="off">
    </div>
</div>

<div class="table-responsive bg-white rounded shadow-sm">
    <table class="table table-hover align-middle mb-0">
        <thead class="bg-light">
            <tr>
                <th class="py-3 ps-4">Nº Pedido</th>
                <th class="py-3">Cliente</th>
                <th class="py-3">Data</th>
                <th class="py-3">Valor Total</th>
                <th class="py-3">Etapa</th>
                <th class="py-3">Status</th>
                <th class="py-3">Prioridade</th>
                <th class="py-3 text-end pe-4">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            // Mapa de cores e ícones das etapas do pipeline
            $pipelineStageMap = [
                'contato'    => ['label' => 'Contato',       'color' => '#9b59b6', 'icon' => 'fas fa-phone'],
                'orcamento'  => ['label' => 'Orçamento',     'color' => '#3498db', 'icon' => 'fas fa-file-invoice-dollar'],
                'venda'      => ['label' => 'Venda',         'color' => '#2ecc71', 'icon' => 'fas fa-handshake'],
                'producao'   => ['label' => 'Produção',      'color' => '#e67e22', 'icon' => 'fas fa-industry'],
                'preparacao' => ['label' => 'Preparação',    'color' => '#1abc9c', 'icon' => 'fas fa-boxes-packing'],
                'envio'      => ['label' => 'Envio/Entrega', 'color' => '#e74c3c', 'icon' => 'fas fa-truck'],
                'financeiro' => ['label' => 'Financeiro',    'color' => '#f39c12', 'icon' => 'fas fa-coins'],
                'concluido'  => ['label' => 'Concluído',     'color' => '#27ae60', 'icon' => 'fas fa-check-double'],
            ];
            $priorityMap = [
                'baixa'   => ['badge' => 'bg-secondary', 'label' => 'Baixa'],
                'normal'  => ['badge' => 'bg-primary',   'label' => 'Normal'],
                'alta'    => ['badge' => 'bg-warning text-dark', 'label' => 'Alta'],
                'urgente' => ['badge' => 'bg-danger',    'label' => 'Urgente'],
            ];
            ?>
            <?php if(count($orders) > 0): ?>
            <?php foreach($orders as $order): ?>
            <?php
                $stage = $order['pipeline_stage'] ?? 'contato';
                $stageData = $pipelineStageMap[$stage] ?? ['label' => ucfirst($stage), 'color' => '#999', 'icon' => 'fas fa-circle'];
                $prio = $order['priority'] ?? 'normal';
                $prioData = $priorityMap[$prio] ?? $priorityMap['normal'];
            ?>
            <tr>
                <td class="ps-4 fw-bold">
                    <a href="?page=pipeline&action=detail&id=<?= (int)$order['id'] ?>" class="text-decoration-none text-dark">
                        #<?= str_pad((int)$order['id'], 4, '0', STR_PAD_LEFT) ?>
                    </a>
                </td>
                <td>
                    <a href="?page=pipeline&action=detail&id=<?= (int)$order['id'] ?>" class="text-decoration-none text-dark">
                        <div class="d-flex align-items-center">
                            <div class="bg-info text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 30px; height: 30px; font-size: 0.75rem;">
                                <?= $order['customer_name'] ? e(strtoupper(substr($order['customer_name'], 0, 1))) : '?' ?>
                            </div>
                            <?= $order['customer_name'] ? e($order['customer_name']) : '<span class="text-muted">Cliente Removido</span>' ?>
                        </div>
                    </a>
                </td>
                <td class="small"><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                <td class="fw-bold">R$ <?= eNum($order['total_amount']) ?></td>
                <td>
                    <a href="?page=pipeline&action=detail&id=<?= $order['id'] ?>" class="text-decoration-none">
                        <span class="badge rounded-pill px-2 py-1" style="background:<?= $stageData['color'] ?>;font-size:0.72rem;">
                            <i class="<?= $stageData['icon'] ?> me-1"></i><?= $stageData['label'] ?>
                        </span>
                    </a>
                </td>
                <td>
                    <?php 
                    $statusMap = [
                        'orcamento'    => ['bg-info text-white', 'fas fa-file-alt'],
                        'Pendente'     => ['bg-warning text-dark', 'fas fa-clock'],
                        'pendente'     => ['bg-warning text-dark', 'fas fa-clock'],
                        'aprovado'     => ['bg-primary', 'fas fa-thumbs-up'],
                        'em_producao'  => ['bg-info text-white', 'fas fa-cogs'],
                        'concluido'    => ['bg-success', 'fas fa-check-circle'],
                        'cancelado'    => ['bg-danger', 'fas fa-times-circle'],
                    ];
                    $statusKey = $order['status'];
                    $badgeClass = $statusMap[$statusKey][0] ?? 'bg-secondary';
                    $statusIcon = $statusMap[$statusKey][1] ?? 'fas fa-info-circle';
                    ?>
                    <span class="badge <?= $badgeClass ?> px-2 py-1" style="font-size:0.72rem;">
                        <i class="<?= $statusIcon ?> me-1"></i><?= ucfirst(str_replace('_', ' ', $order['status'])) ?>
                    </span>
                    <?php
                        $orderApproval = $order['customer_approval_status'] ?? null;
                        if ($orderApproval === 'aprovado'):
                    ?>
                    <span class="badge bg-success px-2 py-1 ms-1" style="font-size:0.65rem;" title="Cliente aprovou pelo Portal">
                        <i class="fas fa-user-check me-1"></i>Aprovado
                    </span>
                    <?php elseif ($orderApproval === 'pendente'): ?>
                    <span class="badge bg-warning text-dark px-2 py-1 ms-1" style="font-size:0.65rem;" title="Aguardando aprovação do cliente">
                        <i class="fas fa-hourglass-half me-1"></i>Aguard.
                    </span>
                    <?php elseif ($orderApproval === 'recusado'): ?>
                    <span class="badge bg-danger px-2 py-1 ms-1" style="font-size:0.65rem;" title="Cliente recusou pelo Portal">
                        <i class="fas fa-user-times me-1"></i>Recusado
                    </span>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="badge <?= $prioData['badge'] ?> rounded-pill px-2 py-1" style="font-size:0.7rem;">
                        <?= $prioData['label'] ?>
                    </span>
                </td>
                <td class="text-end pe-4">
                    <div class="btn-group">
                        <a href="?page=pipeline&action=detail&id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-info" title="Ver Pedido">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="?page=orders&action=edit&id=<?= (int)$order['id'] ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete-order" data-id="<?= (int)$order['id'] ?>" title="Excluir">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php else: ?>
            <tr>
                <td colspan="8" class="p-0 border-0">
                    <?php
                    $emptyState = [
                        'icon'       => 'no-orders',
                        'title'      => 'Nenhum pedido encontrado',
                        'message'    => 'Comece criando seu primeiro pedido de venda ou orçamento.',
                        'action_url' => '?page=orders&action=create',
                        'action_text'=> 'Criar Primeiro Pedido',
                    ];
                    require 'app/views/components/empty-state.php';
                    ?>
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require 'app/views/layout/pagination.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if(isset($_GET['status'])): ?>
    if (window.history.replaceState) { const url = new URL(window.location); url.searchParams.delete('status'); window.history.replaceState({}, '', url); }
    <?php endif; ?>
    <?php if(isset($_GET['status']) && $_GET['status'] == 'success'): ?>
    if (window.AktiToast) AktiToast.success('Pedido salvo com sucesso!');
    <?php endif; ?>

    document.querySelectorAll('.btn-delete-order').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            Swal.fire({
                title: 'Excluir pedido?',
                html: `Deseja realmente excluir o pedido <strong>#${id}</strong>?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#c0392b',
                cancelButtonColor: '#95a5a6',
                confirmButtonText: '<i class="fas fa-trash me-1"></i> Sim, excluir',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `?page=orders&action=delete&id=${id}`;
                }
            });
        });
    });

    // Busca rápida na tabela
    const searchInput = document.getElementById('searchTable');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const q = this.value.toLowerCase().trim();
            document.querySelectorAll('table tbody tr').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = (!q || text.includes(q)) ? '' : 'none';
            });
        });
    }
});
</script>
