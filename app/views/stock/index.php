<?php
/**
 * Estoque — Visão Geral
 * Variáveis: $warehouses, $stockItems, $summary, $lowStockItems
 */
$currentWarehouse = $_GET['warehouse_id'] ?? '';
$currentSearch = $_GET['search'] ?? '';
$isLowStock = isset($_GET['low_stock']) && $_GET['low_stock'] == '1';
?>

<!-- ══════ Header ══════ -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-warehouse me-2"></i>Controle de Estoque</h1>
    <div class="btn-toolbar mb-2 mb-md-0 gap-2">
        <a href="?page=stock&action=entry" class="btn btn-sm btn-success">
            <i class="fas fa-arrow-down me-1"></i> Entrada / Saída
        </a>
        <a href="?page=stock&action=movements" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-exchange-alt me-1"></i> Movimentações
        </a>
        <a href="?page=stock&action=warehouses" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-building me-1"></i> Armazéns
        </a>
    </div>
</div>

<!-- ══════ Cards de Resumo ══════ -->
<div class="row g-3 mb-4">
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
            <div class="card-body d-flex align-items-center p-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:44px;height:44px;background:rgba(52,152,219,0.15);">
                    <i class="fas fa-building fa-lg text-primary"></i>
                </div>
                <div>
                    <div class="text-muted small fw-bold text-uppercase">Armazéns</div>
                    <div class="fw-bold fs-4 text-primary"><?= $summary['total_warehouses'] ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card border-0 shadow-sm h-100 border-start border-info border-4">
            <div class="card-body d-flex align-items-center p-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:44px;height:44px;background:rgba(23,162,184,0.15);">
                    <i class="fas fa-cubes fa-lg text-info"></i>
                </div>
                <div>
                    <div class="text-muted small fw-bold text-uppercase">Itens</div>
                    <div class="fw-bold fs-4 text-info"><?= $summary['total_items'] ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
            <div class="card-body d-flex align-items-center p-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:44px;height:44px;background:rgba(39,174,96,0.15);">
                    <i class="fas fa-box-open fa-lg text-success"></i>
                </div>
                <div>
                    <div class="text-muted small fw-bold text-uppercase">Produtos</div>
                    <div class="fw-bold fs-4 text-success"><?= $summary['products_in_stock'] ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card border-0 shadow-sm h-100 border-start border-warning border-4">
            <div class="card-body d-flex align-items-center p-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:44px;height:44px;background:rgba(243,156,18,0.15);">
                    <i class="fas fa-dollar-sign fa-lg text-warning"></i>
                </div>
                <div>
                    <div class="text-muted small fw-bold text-uppercase">Valor Total</div>
                    <div class="fw-bold fs-5 text-warning">R$ <?= number_format($summary['total_value'], 2, ',', '.') ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card border-0 shadow-sm h-100 border-start border-danger border-4 <?= $summary['low_stock_count'] > 0 ? 'bg-danger bg-opacity-10' : '' ?>">
            <div class="card-body d-flex align-items-center p-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:44px;height:44px;background:rgba(192,57,43,0.15);">
                    <i class="fas fa-exclamation-triangle fa-lg text-danger"></i>
                </div>
                <div>
                    <div class="text-muted small fw-bold text-uppercase">Baixo</div>
                    <div class="fw-bold fs-4 text-danger"><?= $summary['low_stock_count'] ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card border-0 shadow-sm h-100 border-start border-secondary border-4">
            <div class="card-body d-flex align-items-center p-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:44px;height:44px;background:rgba(108,117,125,0.15);">
                    <i class="fas fa-exchange-alt fa-lg text-secondary"></i>
                </div>
                <div>
                    <div class="text-muted small fw-bold text-uppercase">Mov. Hoje</div>
                    <div class="fw-bold fs-4 text-secondary"><?= $summary['movements_today'] ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══════ Alertas de Estoque Baixo ══════ -->
<?php if (!empty($lowStockItems)): ?>
<div class="alert alert-warning py-2 mb-4 d-flex align-items-start">
    <i class="fas fa-exclamation-triangle me-2 mt-1"></i>
    <div>
        <strong>Estoque Baixo:</strong>
        <?php foreach ($lowStockItems as $lsi): ?>
            <span class="badge bg-danger ms-1">
                <?= e($lsi['product_name']) ?>
                <?= $lsi['combination_label'] ? '(' . $lsi['combination_label'] . ')' : '' ?>
                — <?= intval($lsi['quantity']) ?>/<?= intval($lsi['min_quantity']) ?>
                <small>(<?= $lsi['warehouse_name'] ?>)</small>
            </span>
        <?php endforeach; ?>
        <a href="?page=stock&low_stock=1" class="btn btn-sm btn-outline-danger ms-2">Ver todos</a>
    </div>
</div>
<?php endif; ?>

<!-- ══════ Filtros ══════ -->
<form method="get" class="row g-2 mb-3 align-items-end">
    <input type="hidden" name="page" value="stock">
    <div class="col-md-3">
        <label class="form-label small fw-bold">Armazém</label>
        <select name="warehouse_id" class="form-select form-select-sm">
            <option value="">Todos os Armazéns</option>
            <?php foreach ($warehouses as $wh): ?>
                <option value="<?= $wh['id'] ?>" <?= $currentWarehouse == $wh['id'] ? 'selected' : '' ?>>
                    <?= e($wh['name']) ?> (<?= $wh['total_items'] ?> itens)
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label small fw-bold">Buscar</label>
        <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="fas fa-search"></i></span>
            <input type="text" name="search" class="form-control" placeholder="Produto, variação ou localização..." value="<?= eAttr($currentSearch) ?>">
        </div>
    </div>
    <div class="col-md-2">
        <div class="form-check mt-4">
            <input type="checkbox" class="form-check-input" name="low_stock" value="1" id="chkLowStock" <?= $isLowStock ? 'checked' : '' ?>>
            <label class="form-check-label small" for="chkLowStock"><i class="fas fa-exclamation-triangle text-warning me-1"></i>Só estoque baixo</label>
        </div>
    </div>
    <div class="col-md-3 d-flex gap-2">
        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i>Filtrar</button>
        <a href="?page=stock" class="btn btn-sm btn-outline-secondary"><i class="fas fa-times me-1"></i>Limpar</a>
    </div>
</form>

<!-- ══════ Tabela de Estoque ══════ -->
<div class="table-responsive bg-white rounded shadow-sm">
    <table class="table table-hover align-middle mb-0" id="stockTable">
        <thead class="bg-light">
            <tr>
                <th class="py-3 ps-4" style="width:50px;"></th>
                <th class="py-3">Produto</th>
                <th class="py-3">Variação</th>
                <th class="py-3">Armazém</th>
                <th class="py-3 text-center">Quantidade</th>
                <th class="py-3 text-center">Mínimo</th>
                <th class="py-3">Localização</th>
                <th class="py-3 text-end pe-4">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($stockItems) > 0): ?>
            <?php foreach ($stockItems as $si): 
                $isLow = $si['min_quantity'] > 0 && $si['quantity'] <= $si['min_quantity'];
            ?>
            <tr class="<?= $isLow ? 'table-warning' : '' ?>">
                <td class="ps-4">
                    <div class="bg-light rounded d-flex align-items-center justify-content-center border" style="width:40px;height:40px;overflow:hidden;">
                        <?php if (!empty($si['product_image'])): ?>
                            <img src="<?= $si['product_image'] ?>" class="w-100 h-100 object-fit-cover">
                        <?php else: ?>
                            <i class="fas fa-box text-secondary"></i>
                        <?php endif; ?>
                    </div>
                </td>
                <td class="fw-bold"><?= e($si['product_name']) ?></td>
                <td>
                    <?php if ($si['combination_label']): ?>
                        <span class="badge bg-info bg-opacity-75"><?= e($si['combination_label']) ?></span>
                    <?php else: ?>
                        <span class="text-muted small">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="badge bg-light text-dark border"><?= e($si['warehouse_name']) ?></span>
                </td>
                <td class="text-center">
                    <?php if ($isLow): ?>
                        <span class="badge bg-danger px-3 fs-6"><?= number_format($si['quantity'], 0) ?></span>
                    <?php elseif ($si['quantity'] > 0): ?>
                        <span class="badge bg-success px-3 fs-6"><?= number_format($si['quantity'], 0) ?></span>
                    <?php else: ?>
                        <span class="badge bg-secondary px-3">0</span>
                    <?php endif; ?>
                </td>
                <td class="text-center">
                    <span class="text-muted small"><?= $si['min_quantity'] > 0 ? number_format($si['min_quantity'], 0) : '—' ?></span>
                </td>
                <td>
                    <span class="text-muted small"><?= $si['location_code'] ? e($si['location_code']) : '—' ?></span>
                </td>
                <td class="text-end pe-4">
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-secondary btn-edit-meta"
                                data-id="<?= $si['id'] ?>"
                                data-min="<?= $si['min_quantity'] ?>"
                                data-loc="<?= eAttr($si['location_code'] ?? '') ?>"
                                data-name="<?= eAttr($si['product_name']) ?>"
                                title="Editar mínimo/localização">
                            <i class="fas fa-cog"></i>
                        </button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php else: ?>
            <tr>
                <td colspan="8" class="text-center text-muted py-5">
                    <i class="fas fa-warehouse fa-3x mb-3 d-block text-secondary"></i>
                    Nenhum item no estoque <?= $currentWarehouse || $currentSearch ? 'com os filtros selecionados' : 'ainda' ?>.
                    <br><a href="?page=stock&action=entry" class="btn btn-success btn-sm mt-2"><i class="fas fa-plus me-1"></i>Dar entrada</a>
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- ══════ Modal: Editar Mínimo / Localização ══════ -->
<div class="modal fade" id="editMetaModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title"><i class="fas fa-cog me-1"></i>Configurar Item</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="fw-bold mb-3 text-primary" id="metaItemName"></p>
                <input type="hidden" id="metaItemId">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Estoque Mínimo</label>
                    <input type="number" class="form-control" id="metaMinQty" min="0" step="1" placeholder="0">
                    <div class="form-text">Alerta quando atingir este valor.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Localização Física</label>
                    <input type="text" class="form-control" id="metaLocCode" placeholder="Ex: A1-P3, Prateleira 5">
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-sm btn-primary" id="btnSaveMeta"><i class="fas fa-save me-1"></i>Salvar</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ── Status alerts ──
    <?php if (isset($_GET['status'])): ?>
    const urlClean = new URL(window.location);
    urlClean.searchParams.delete('status');
    window.history.replaceState({}, '', urlClean);
    <?php if ($_GET['status'] == 'moved'): ?>
    Swal.fire({ icon:'success', title:'Movimentação registrada!', timer:2000, showConfirmButton:false });
    <?php endif; ?>
    <?php endif; ?>

    // ── Edit meta modal ──
    document.querySelectorAll('.btn-edit-meta').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('metaItemId').value = this.dataset.id;
            document.getElementById('metaMinQty').value = this.dataset.min;
            document.getElementById('metaLocCode').value = this.dataset.loc;
            document.getElementById('metaItemName').textContent = this.dataset.name;
            new bootstrap.Modal(document.getElementById('editMetaModal')).show();
        });
    });

    document.getElementById('btnSaveMeta')?.addEventListener('click', function() {
        const id = document.getElementById('metaItemId').value;
        const minQty = document.getElementById('metaMinQty').value;
        const locCode = document.getElementById('metaLocCode').value;

        const fd = new FormData();
        fd.append('id', id);
        fd.append('min_quantity', minQty);
        fd.append('location_code', locCode);

        fetch('?page=stock&action=updateItemMeta', { method:'POST', body:fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('editMetaModal')).hide();
                    Swal.fire({ icon:'success', title:'Atualizado!', timer:1500, showConfirmButton:false })
                        .then(() => location.reload());
                }
            });
    });
});
</script>
