<?php
/**
 * Custos de Produção — Relatório de Margem
 * Variáveis: $report
 */
?>
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div><h1 class="h2 mb-1"><i class="fas fa-chart-bar me-2 text-primary"></i>Relatório de Margem</h1></div>
        <a href="?page=production_costs" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Pedido</th><th>Material</th><th>Mão-de-Obra</th><th>Overhead</th><th>Custo Total</th><th>Valor Venda</th><th>Margem</th></tr>
                    </thead>
                    <tbody>
                    <?php if (empty($report['data'])): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">Nenhum custo calculado ainda.</td></tr>
                    <?php else: ?>
                        <?php foreach ($report['data'] as $r): ?>
                        <?php $margin = ($r['total_amount'] ?? 0) > 0 ? (($r['total_amount'] - $r['total_cost']) / $r['total_amount'] * 100) : 0; ?>
                        <tr>
                            <td>#<?= (int) $r['order_id'] ?></td>
                            <td>R$ <?= number_format((float) ($r['material_cost'] ?? 0), 2, ',', '.') ?></td>
                            <td>R$ <?= number_format((float) ($r['labor_cost'] ?? 0), 2, ',', '.') ?></td>
                            <td>R$ <?= number_format((float) ($r['overhead_cost'] ?? 0), 2, ',', '.') ?></td>
                            <td><strong>R$ <?= number_format((float) ($r['total_cost'] ?? 0), 2, ',', '.') ?></strong></td>
                            <td>R$ <?= number_format((float) ($r['total_amount'] ?? 0), 2, ',', '.') ?></td>
                            <td>
                                <span class="badge bg-<?= $margin >= 20 ? 'success' : ($margin >= 0 ? 'warning' : 'danger') ?>">
                                    <?= number_format($margin, 1) ?>%
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php if (!empty($report) && ($report['pages'] ?? 1) > 1): ?>
    <nav class="mt-3">
        <ul class="pagination pagination-sm justify-content-center">
            <?php for ($p = 1; $p <= $report['pages']; $p++): ?>
            <li class="page-item <?= $p == ($report['current_page'] ?? 1) ? 'active' : '' ?>">
                <a class="page-link" href="?page=production_costs&action=marginReport&p=<?= $p ?>"><?= $p ?></a>
            </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>
