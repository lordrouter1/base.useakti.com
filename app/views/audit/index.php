<?php
/**
 * Auditoria — Listagem
 * FEAT-004
 * Variáveis: $logs, $pagination, $users
 */
$search  = $_GET['search'] ?? '';
$userId  = $_GET['user_id'] ?? '';
$entity  = $_GET['entity_type'] ?? '';
$action  = $_GET['action_filter'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo   = $_GET['date_to'] ?? '';
?>

<div class="container-fluid py-3">

    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div>
            <h1 class="h2 mb-1"><i class="fas fa-history me-2 text-primary"></i>Log de Auditoria</h1>
            <p class="text-muted mb-0" style="font-size:.82rem;">Rastreamento completo de todas as ações do sistema.</p>
        </div>
        <a href="?page=audit&action=exportCsv&<?= http_build_query(array_filter($_GET, fn($v) => $v !== '', ARRAY_FILTER_USE_BOTH)) ?>" class="btn btn-sm btn-outline-success">
            <i class="fas fa-download me-1"></i>Exportar CSV
        </a>
    </div>

    <!-- Filtros -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="get" class="row g-2 align-items-end">
                <input type="hidden" name="page" value="audit">
                <div class="col-md-2">
                    <label class="form-label small mb-1">Usuário</label>
                    <select name="user_id" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach ($users ?? [] as $u): ?>
                        <option value="<?= (int) $u['id'] ?>" <?= $userId == $u['id'] ? 'selected' : '' ?>><?= e($u['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Entidade</label>
                    <select name="entity_type" class="form-select form-select-sm">
                        <option value="">Todas</option>
                        <option value="order" <?= $entity === 'order' ? 'selected' : '' ?>>Pedido</option>
                        <option value="customer" <?= $entity === 'customer' ? 'selected' : '' ?>>Cliente</option>
                        <option value="product" <?= $entity === 'product' ? 'selected' : '' ?>>Produto</option>
                        <option value="supplier" <?= $entity === 'supplier' ? 'selected' : '' ?>>Fornecedor</option>
                        <option value="quote" <?= $entity === 'quote' ? 'selected' : '' ?>>Orçamento</option>
                        <option value="financial" <?= $entity === 'financial' ? 'selected' : '' ?>>Financeiro</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Ação</label>
                    <select name="action_filter" class="form-select form-select-sm">
                        <option value="">Todas</option>
                        <option value="created" <?= $action === 'created' ? 'selected' : '' ?>>Criação</option>
                        <option value="updated" <?= $action === 'updated' ? 'selected' : '' ?>>Edição</option>
                        <option value="deleted" <?= $action === 'deleted' ? 'selected' : '' ?>>Exclusão</option>
                        <option value="login" <?= $action === 'login' ? 'selected' : '' ?>>Login</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">De</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="<?= eAttr($dateFrom) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Até</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="<?= eAttr($dateTo) ?>">
                </div>
                <div class="col-auto">
                    <button class="btn btn-sm btn-outline-primary"><i class="fas fa-search me-1"></i>Filtrar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabela de logs -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Data/Hora</th>
                            <th>Usuário</th>
                            <th>Ação</th>
                            <th>Entidade</th>
                            <th>ID</th>
                            <th>IP</th>
                            <th class="text-end">Detalhes</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($logs)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">Nenhum log encontrado.</td></tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td style="font-size:.8rem;"><?= date('d/m/Y H:i', strtotime($log['created_at'])) ?></td>
                            <td><?= e($log['user_name'] ?? 'Sistema') ?></td>
                            <td>
                                <?php
                                $actionBadges = ['created' => 'bg-success', 'updated' => 'bg-info', 'deleted' => 'bg-danger', 'login' => 'bg-primary'];
                                ?>
                                <span class="badge <?= $actionBadges[$log['action']] ?? 'bg-secondary' ?>"><?= e($log['action']) ?></span>
                            </td>
                            <td><?= e($log['entity_type']) ?></td>
                            <td><?= (int) ($log['entity_id'] ?? 0) ?></td>
                            <td style="font-size:.8rem;"><?= e($log['ip_address'] ?? '-') ?></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-info btnDetail" data-old="<?= eAttr($log['old_values'] ?? '') ?>" data-new="<?= eAttr($log['new_values'] ?? '') ?>" title="Ver detalhes">
                                    <i class="fas fa-eye"></i>
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

    <?php if (!empty($pagination) && ($pagination['total_pages'] ?? 1) > 1): ?>
    <nav class="mt-3">
        <ul class="pagination pagination-sm justify-content-center">
            <?php for ($p = 1; $p <= $pagination['total_pages']; $p++): ?>
            <li class="page-item <?= $p == ($pagination['page'] ?? 1) ? 'active' : '' ?>">
                <a class="page-link" href="?page=audit&<?= http_build_query(array_merge(array_filter($_GET), ['p' => $p])) ?>"><?= $p ?></a>
            </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btnDetail').forEach(btn => {
        btn.addEventListener('click', function() {
            let oldVal = this.dataset.old ? JSON.parse(this.dataset.old) : null;
            let newVal = this.dataset.new ? JSON.parse(this.dataset.new) : null;
            let html = '<div class="text-start" style="max-height:400px;overflow:auto;font-size:.85rem;">';
            if (oldVal) html += '<h6>Valores Anteriores:</h6><pre class="bg-light p-2 rounded">' + JSON.stringify(oldVal, null, 2) + '</pre>';
            if (newVal) html += '<h6>Valores Novos:</h6><pre class="bg-light p-2 rounded">' + JSON.stringify(newVal, null, 2) + '</pre>';
            if (!oldVal && !newVal) html += '<p class="text-muted">Sem detalhes disponíveis.</p>';
            html += '</div>';
            Swal.fire({title: 'Detalhes da Alteração', html: html, width: 600, showCloseButton: true, showConfirmButton: false});
        });
    });
});
</script>
