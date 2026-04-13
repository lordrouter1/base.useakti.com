<?php
/**
 * View: Estoque de Insumos — Index / Dashboard
 * Variáveis: $warehouses, $summary, $lowStockItems, $expiringItems, $warehouseId, $search, $lowStock
 */
?>

<?php if (!empty($_SESSION['flash_success'])): ?>
<script>document.addEventListener('DOMContentLoaded', () => AktiToast.success(<?= json_encode($_SESSION['flash_success']) ?>));</script>
<?php unset($_SESSION['flash_success']); endif; ?>
<?php if (!empty($_SESSION['flash_error'])): ?>
<script>document.addEventListener('DOMContentLoaded', () => AktiToast.error(<?= json_encode($_SESSION['flash_error']) ?>));</script>
<?php unset($_SESSION['flash_error']); endif; ?>

<div class="container-fluid py-4">

    <!-- Título + Ações -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="fas fa-boxes-stacked me-2 text-primary"></i>Estoque de Insumos</h1>
        <div class="d-flex gap-2">
            <a href="?page=supply_stock&action=entry" class="btn btn-success btn-sm">
                <i class="fas fa-arrow-down me-1"></i>Entrada
            </a>
            <a href="?page=supply_stock&action=exit" class="btn btn-danger btn-sm">
                <i class="fas fa-arrow-up me-1"></i>Saída
            </a>
            <a href="?page=supply_stock&action=transfer" class="btn btn-info btn-sm">
                <i class="fas fa-exchange-alt me-1"></i>Transferência
            </a>
            <a href="?page=supply_stock&action=adjust" class="btn btn-warning btn-sm">
                <i class="fas fa-sliders-h me-1"></i>Ajuste
            </a>
            <a href="?page=supply_stock&action=movements" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-history me-1"></i>Movimentações
            </a>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="icon-circle icon-circle-lg bg-primary bg-opacity-10 text-primary me-3">
                        <i class="fas fa-cubes fa-lg"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">Itens em Estoque</small>
                        <span class="fw-bold fs-5" id="kpiTotalItems"><?= eNum($summary['total_items'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="icon-circle icon-circle-lg bg-success bg-opacity-10 text-success me-3">
                        <i class="fas fa-dollar-sign fa-lg"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">Valor Total</small>
                        <span class="fw-bold fs-5" id="kpiTotalValue">R$ <?= eNum($summary['total_value'] ?? 0, 2) ?></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 border-start border-warning border-4">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="icon-circle icon-circle-lg bg-warning bg-opacity-10 text-warning me-3">
                        <i class="fas fa-exclamation-triangle fa-lg"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">Estoque Baixo</small>
                        <span class="fw-bold fs-5" id="kpiLowStock"><?= eNum($summary['low_stock_count'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 border-start border-danger border-4">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="icon-circle icon-circle-lg bg-danger bg-opacity-10 text-danger me-3">
                        <i class="fas fa-arrows-alt me-1 fa-lg"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">Movimentações (mês)</small>
                        <span class="fw-bold fs-5" id="kpiMovements"><?= eNum($summary['movements_month'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Tabela principal de itens -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0">Itens em Estoque</h5>
                    <div class="d-flex gap-2">
                        <select id="filterWarehouse" class="form-select form-select-sm" style="width: 180px;">
                            <option value="">Todos os armazéns</option>
                            <?php foreach ($warehouses as $wh): ?>
                            <option value="<?= eAttr($wh['id']) ?>" <?= ($warehouseId == $wh['id']) ? 'selected' : '' ?>><?= e($wh['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" id="filterSearch" class="form-control form-control-sm" placeholder="Buscar insumo..." value="<?= eAttr($search ?? '') ?>" style="width: 200px;">
                        <label class="btn btn-sm btn-outline-warning">
                            <input type="checkbox" id="filterLowStock" class="btn-check" <?= !empty($lowStock) ? 'checked' : '' ?>> Estoque Baixo
                        </label>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Código</th>
                                    <th>Insumo</th>
                                    <th class="text-center">Armazém</th>
                                    <th class="text-center">Lote</th>
                                    <th class="text-end">Qtd</th>
                                    <th class="text-end">Custo Unit.</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody id="stockTableBody">
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="fas fa-spinner fa-spin me-1"></i>Carregando...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-between align-items-center px-4 py-2 border-top">
                        <span id="stockPaginationInfo" class="text-muted small"></span>
                        <ul id="stockPagination" class="pagination pagination-sm mb-0"></ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar: Alertas -->
        <div class="col-lg-4">
            <!-- Estoque Baixo -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-warning bg-opacity-10 py-2">
                    <h6 class="mb-0 text-warning"><i class="fas fa-exclamation-triangle me-1"></i>Estoque Baixo</h6>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($lowStockItems)): ?>
                    <p class="text-muted text-center py-3 mb-0">Nenhum item com estoque baixo.</p>
                    <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($lowStockItems as $item): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                            <div>
                                <span class="fw-semibold"><?= e($item['supply_code'] ?? '') ?></span>
                                <span class="text-muted ms-1"><?= e($item['supply_name']) ?></span>
                            </div>
                            <span class="badge bg-danger"><?= eNum($item['quantity'], 2) ?> / <?= eNum($item['min_quantity'] ?? 0, 2) ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Próximos a Vencer -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-danger bg-opacity-10 py-2">
                    <h6 class="mb-0 text-danger"><i class="fas fa-clock me-1"></i>Próximos a Vencer (30 dias)</h6>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($expiringItems)): ?>
                    <p class="text-muted text-center py-3 mb-0">Nenhum lote próximo ao vencimento.</p>
                    <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($expiringItems as $item): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                            <div>
                                <span class="fw-semibold"><?= e($item['supply_code'] ?? '') ?></span>
                                <span class="text-muted ms-1"><?= e($item['supply_name']) ?></span>
                                <?php if (!empty($item['batch_number'])): ?>
                                <small class="text-info ms-1">[<?= e($item['batch_number']) ?>]</small>
                                <?php endif; ?>
                            </div>
                            <span class="badge bg-danger"><?= e(date('d/m/Y', strtotime($item['expiry_date']))) ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sugestões de Reposição -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-info bg-opacity-10 py-2">
                    <h6 class="mb-0 text-info"><i class="fas fa-cart-plus me-1"></i>Sugestões de Reposição</h6>
                </div>
                <div class="card-body p-0" id="reorderSuggestions">
                    <p class="text-muted text-center py-3 mb-0"><i class="fas fa-spinner fa-spin me-1"></i>Carregando...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    let currentPage = 1;

    // ──── Carregar itens de estoque ────
    function loadStockItems(page = 1) {
        currentPage = page;
        const params = new URLSearchParams({
            page: 'supply_stock',
            action: 'getStockItems',
            pg: page,
            warehouse_id: document.getElementById('filterWarehouse').value,
            search: document.getElementById('filterSearch').value,
            low_stock: document.getElementById('filterLowStock').checked ? '1' : '0'
        });

        fetch('?' + params, { headers: { 'X-CSRF-TOKEN': csrfToken } })
            .then(r => r.json())
            .then(data => {
                const tbody = document.getElementById('stockTableBody');
                if (!data.success || !data.items.length) {
                    tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">Nenhum item encontrado.</td></tr>';
                    document.getElementById('stockPaginationInfo').textContent = '';
                    document.getElementById('stockPagination').innerHTML = '';
                    return;
                }

                let html = '';
                data.items.forEach(item => {
                    const isLow = item.min_quantity > 0 && parseFloat(item.quantity) <= parseFloat(item.min_quantity);
                    const statusBadge = isLow
                        ? '<span class="badge bg-danger">Baixo</span>'
                        : '<span class="badge bg-success">Normal</span>';

                    html += `<tr>
                        <td class="ps-4"><code>${escapeHtml(item.supply_code || '')}</code></td>
                        <td>${escapeHtml(item.supply_name || '')}</td>
                        <td class="text-center">${escapeHtml(item.warehouse_name || '—')}</td>
                        <td class="text-center">${item.batch_number ? escapeHtml(item.batch_number) : '—'}</td>
                        <td class="text-end fw-semibold">${parseFloat(item.quantity).toLocaleString('pt-BR', {minimumFractionDigits: 2})} ${escapeHtml(item.unit_measure || '')}</td>
                        <td class="text-end">R$ ${parseFloat(item.cost_price || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</td>
                        <td class="text-center">${statusBadge}</td>
                    </tr>`;
                });
                tbody.innerHTML = html;

                // Paginação
                const start = (data.page - 1) * data.per_page + 1;
                const end = Math.min(data.page * data.per_page, data.total);
                document.getElementById('stockPaginationInfo').textContent = `${start}-${end} de ${data.total}`;

                let pagHtml = '';
                for (let p = 1; p <= data.total_pages; p++) {
                    pagHtml += `<li class="page-item ${p === data.page ? 'active' : ''}">
                        <a class="page-link" href="#" onclick="event.preventDefault(); loadStockItems(${p})">${p}</a>
                    </li>`;
                }
                document.getElementById('stockPagination').innerHTML = pagHtml;
            })
            .catch(() => {
                document.getElementById('stockTableBody').innerHTML = '<tr><td colspan="7" class="text-center text-danger py-4">Erro ao carregar dados.</td></tr>';
            });
    }

    // ──── Carregar sugestões de reposição ────
    function loadReorderSuggestions() {
        fetch('?page=supply_stock&action=reorderSuggestions', { headers: { 'X-CSRF-TOKEN': csrfToken } })
            .then(r => r.json())
            .then(data => {
                const container = document.getElementById('reorderSuggestions');
                if (!data.success || !data.items.length) {
                    container.innerHTML = '<p class="text-muted text-center py-3 mb-0">Nenhuma sugestão no momento.</p>';
                    return;
                }
                let html = '<ul class="list-group list-group-flush">';
                data.items.forEach(item => {
                    const deficit = parseFloat(item.reorder_point) - parseFloat(item.total_stock);
                    html += `<li class="list-group-item py-2">
                        <div class="d-flex justify-content-between">
                            <span class="fw-semibold">${escapeHtml(item.code)} — ${escapeHtml(item.name)}</span>
                            <span class="badge bg-info">Repor: ${deficit.toLocaleString('pt-BR', {minimumFractionDigits: 2})}</span>
                        </div>
                        ${item.pref_supplier_name ? `<small class="text-muted">Fornecedor: ${escapeHtml(item.pref_supplier_name)}</small>` : ''}
                    </li>`;
                });
                html += '</ul>';
                container.innerHTML = html;
            })
            .catch(() => {
                document.getElementById('reorderSuggestions').innerHTML = '<p class="text-danger text-center py-3 mb-0">Erro ao carregar.</p>';
            });
    }

    // Filtros
    let filterTimeout;
    document.getElementById('filterWarehouse').addEventListener('change', () => loadStockItems(1));
    document.getElementById('filterSearch').addEventListener('input', () => {
        clearTimeout(filterTimeout);
        filterTimeout = setTimeout(() => loadStockItems(1), 400);
    });
    document.getElementById('filterLowStock').addEventListener('change', () => loadStockItems(1));

    // Expor para paginação
    window.loadStockItems = loadStockItems;

    // Inicializar
    loadStockItems(1);
    loadReorderSuggestions();

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
});
</script>
