<?php
/**
 * Estoque — Histórico de Movimentações
 * Variáveis: $movements, $warehouses, $products
 */

$fWarehouse = $_GET['warehouse_id'] ?? '';
$fProduct = $_GET['product_id'] ?? '';
$fType = $_GET['type'] ?? '';
$fDateFrom = $_GET['date_from'] ?? '';
$fDateTo = $_GET['date_to'] ?? '';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-history me-2"></i>Movimentações de Estoque</h1>
    <div class="btn-toolbar mb-2 mb-md-0 gap-2">
        <a href="?page=stock&action=entry" class="btn btn-sm btn-success"><i class="fas fa-plus me-1"></i>Nova Movimentação</a>
        <a href="?page=stock" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
    </div>
</div>

<!-- ══════ Filtros ══════ -->
<form method="get" class="row g-2 mb-3 align-items-end">
    <input type="hidden" name="page" value="stock">
    <input type="hidden" name="action" value="movements">
    <div class="col-md-2">
        <label class="form-label small fw-bold">Armazém</label>
        <select name="warehouse_id" class="form-select form-select-sm">
            <option value="">Todos</option>
            <?php foreach ($warehouses as $wh): ?>
                <option value="<?= $wh['id'] ?>" <?= $fWarehouse == $wh['id'] ? 'selected' : '' ?>><?= e($wh['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label small fw-bold">Produto</label>
        <select name="product_id" class="form-select form-select-sm">
            <option value="">Todos</option>
            <?php foreach ($products as $p): ?>
                <option value="<?= $p['id'] ?>" <?= $fProduct == $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label small fw-bold">Tipo</label>
        <select name="type" class="form-select form-select-sm">
            <option value="">Todos</option>
            <option value="entrada" <?= $fType === 'entrada' ? 'selected' : '' ?>>Entrada</option>
            <option value="saida" <?= $fType === 'saida' ? 'selected' : '' ?>>Saída</option>
            <option value="ajuste" <?= $fType === 'ajuste' ? 'selected' : '' ?>>Ajuste</option>
            <option value="transferencia" <?= $fType === 'transferencia' ? 'selected' : '' ?>>Transferência</option>
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label small fw-bold">De</label>
        <input type="date" name="date_from" class="form-control form-control-sm" value="<?= eAttr($fDateFrom) ?>">
    </div>
    <div class="col-md-2">
        <label class="form-label small fw-bold">Até</label>
        <input type="date" name="date_to" class="form-control form-control-sm" value="<?= eAttr($fDateTo) ?>">
    </div>
    <div class="col-md-1 d-flex gap-1">
        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter"></i></button>
        <a href="?page=stock&action=movements" class="btn btn-sm btn-outline-secondary"><i class="fas fa-times"></i></a>
    </div>
</form>

<!-- ══════ Tabela de Movimentações ══════ -->
<div class="table-responsive bg-white rounded shadow-sm">
    <table class="table table-hover table-sm align-middle mb-0">
        <thead class="bg-light">
            <tr>
                <th class="py-2 ps-3" style="width:50px;">#</th>
                <th class="py-2">Data</th>
                <th class="py-2">Tipo</th>
                <th class="py-2">Produto</th>
                <th class="py-2">Variação</th>
                <th class="py-2">Armazém</th>
                <th class="py-2 text-center">Qtd</th>
                <th class="py-2 text-center">Antes</th>
                <th class="py-2 text-center">Depois</th>
                <th class="py-2">Motivo</th>
                <th class="py-2">Usuário</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($movements) > 0): ?>
            <?php foreach ($movements as $m): 
                $typeBadges = [
                    'entrada' => 'bg-success',
                    'saida' => 'bg-danger',
                    'ajuste' => 'bg-warning text-dark',
                    'transferencia' => 'bg-info',
                ];
                $typeIcons = [
                    'entrada' => 'fas fa-arrow-down',
                    'saida' => 'fas fa-arrow-up',
                    'ajuste' => 'fas fa-sliders-h',
                    'transferencia' => 'fas fa-truck',
                ];
                $typeLabels = [
                    'entrada' => 'Entrada',
                    'saida' => 'Saída',
                    'ajuste' => 'Ajuste',
                    'transferencia' => 'Transferência',
                ];
            ?>
            <tr>
                <td class="ps-3 text-muted small"><?= $m['id'] ?></td>
                <td class="small"><?= date('d/m/Y H:i', strtotime($m['created_at'])) ?></td>
                <td>
                    <span class="badge <?= $typeBadges[$m['type']] ?? 'bg-secondary' ?>">
                        <i class="<?= $typeIcons[$m['type']] ?? '' ?> me-1"></i><?= $typeLabels[$m['type']] ?? $m['type'] ?>
                    </span>
                </td>
                <td class="fw-bold small"><?= e($m['product_name']) ?></td>
                <td class="small">
                    <?= $m['combination_label'] ? '<span class="badge bg-light text-dark border">' . e($m['combination_label']) . '</span>' : '<span class="text-muted">—</span>' ?>
                </td>
                <td class="small">
                    <?= e($m['warehouse_name']) ?>
                    <?php if ($m['type'] === 'transferencia' && $m['dest_warehouse_name']): ?>
                        <i class="fas fa-arrow-right mx-1 text-muted" style="font-size:0.6rem;"></i>
                        <span class="text-info"><?= e($m['dest_warehouse_name']) ?></span>
                    <?php endif; ?>
                </td>
                <td class="text-center fw-bold">
                    <?php if ($m['type'] === 'entrada'): ?>
                        <span class="text-success">+<?= number_format($m['quantity'], 0) ?></span>
                    <?php elseif ($m['type'] === 'saida'): ?>
                        <span class="text-danger">-<?= number_format($m['quantity'], 0) ?></span>
                    <?php else: ?>
                        <?= number_format($m['quantity'], 0) ?>
                    <?php endif; ?>
                </td>
                <td class="text-center small text-muted"><?= number_format($m['quantity_before'], 0) ?></td>
                <td class="text-center small fw-bold"><?= number_format($m['quantity_after'], 0) ?></td>
                <td class="small text-muted" style="max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?= e($m['reason'] ?? '') ?>">
                    <?= $m['reason'] ? e($m['reason']) : '—' ?>
                </td>
                <td class="small text-muted"><?= $m['user_name'] ? e($m['user_name']) : '—' ?></td>
            </tr>
            <?php endforeach; ?>
            <?php else: ?>
            <tr>
                <td colspan="11" class="text-center text-muted py-5">
                    <i class="fas fa-exchange-alt fa-3x mb-3 d-block text-secondary"></i>
                    Nenhuma movimentação encontrada.
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="text-muted small mt-2 text-end">
    Exibindo <?= count($movements) ?> movimentação(ões)
</div>
