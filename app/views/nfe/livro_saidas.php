<?php
/**
 * View: Livro de Registro de Saídas — FASE5-06
 *
 * Exibe o livro fiscal de registro de saídas com totais por CFOP,
 * valores de ICMS, IPI, PIS e COFINS.
 *
 * @var array  $items            Itens do livro de saídas
 * @var array  $totalsByCfop     Totais agrupados por CFOP
 * @var array  $totalGeral       Totais gerais consolidados
 * @var array  $cfopDescriptions Mapa CFOP => Descrição
 * @var string $startDate        Data início do período
 * @var string $endDate          Data fim do período
 * @var bool   $isAjax           Se carregamento via AJAX
 */
$pageTitle = 'Livro de Registro de Saídas';
$isAjax = $isAjax ?? false;
?>

<?php if (!$isAjax): ?>
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div>
            <h1 class="h2 mb-1"><i class="fas fa-sign-out-alt me-2 text-primary"></i>Livro de Registro de Saídas</h1>
            <p class="text-muted mb-0" style="font-size:.82rem;">Modelo P2 — Registro de documentos fiscais de saída.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="?page=nfe_documents" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Voltar
            </a>
        </div>
    </div>
<?php endif; ?>

    <!-- Filtros de Período -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius:12px;">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end" <?php if ($isAjax): ?>data-ajax-filter="livroSaidas" data-ajax-url="?page=nfe_documents&action=livroSaidas"<?php endif; ?>>
                <?php if (!$isAjax): ?>
                <input type="hidden" name="page" value="nfe_documents">
                <input type="hidden" name="action" value="livroSaidas">
                <?php endif; ?>
                <div class="col-auto">
                    <label class="form-label small mb-0 fw-bold"><i class="fas fa-calendar me-1"></i>Data Início</label>
                    <input type="date" class="form-control form-control-sm" name="start_date" value="<?= eAttr($startDate) ?>">
                </div>
                <div class="col-auto">
                    <label class="form-label small mb-0 fw-bold">Data Fim</label>
                    <input type="date" class="form-control form-control-sm" name="end_date" value="<?= eAttr($endDate) ?>">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search me-1"></i> Filtrar</button>
                </div>
                <div class="col-auto ms-auto">
                    <a href="?page=nfe_documents&action=exportSped&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>"
                       class="btn btn-sm btn-outline-success" title="Exportar SPED Fiscal">
                        <i class="fas fa-file-export me-1"></i> SPED
                    </a>
                    <a href="?page=nfe_documents&action=exportSintegra&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>"
                       class="btn btn-sm btn-outline-info" title="Exportar SINTEGRA">
                        <i class="fas fa-file-alt me-1"></i> SINTEGRA
                    </a>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print();">
                        <i class="fas fa-print me-1"></i> Imprimir
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Resumo por CFOP -->
    <?php if (!empty($totalsByCfop)): ?>
    <div class="card border-0 shadow-sm mb-4" style="border-radius:12px;">
        <div class="card-header py-2" style="background:linear-gradient(135deg,#1a73e8 0%,#4285f4 100%);">
            <h6 class="mb-0 text-white" style="font-size:.85rem;">
                <i class="fas fa-chart-pie me-2"></i>Resumo por CFOP — Período: <?= date('d/m/Y', strtotime($startDate)) ?> a <?= date('d/m/Y', strtotime($endDate)) ?>
            </h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0" style="font-size:.82rem;">
                    <thead class="table-light">
                        <tr>
                            <th>CFOP</th>
                            <th>Descrição</th>
                            <th class="text-center">Qtd</th>
                            <th class="text-end">Valor Contábil</th>
                            <th class="text-end">Base ICMS</th>
                            <th class="text-end">ICMS</th>
                            <th class="text-end">IPI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($totalsByCfop as $cfop => $t): ?>
                        <tr>
                            <td class="fw-bold"><?= e($cfop) ?></td>
                            <td><small><?= e($cfopDescriptions[$cfop] ?? 'Outros') ?></small></td>
                            <td class="text-center"><?= (int)($t['qtd'] ?? 0) ?></td>
                            <td class="text-end">R$ <?= number_format($t['valor_contabil'] ?? 0, 2, ',', '.') ?></td>
                            <td class="text-end">R$ <?= number_format($t['base_icms'] ?? 0, 2, ',', '.') ?></td>
                            <td class="text-end">R$ <?= number_format($t['icms'] ?? 0, 2, ',', '.') ?></td>
                            <td class="text-end">R$ <?= number_format($t['ipi'] ?? 0, 2, ',', '.') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="2">TOTAL GERAL</td>
                            <td class="text-center"><?= (int)($totalGeral['qtd'] ?? 0) ?></td>
                            <td class="text-end">R$ <?= number_format($totalGeral['valor_contabil'] ?? 0, 2, ',', '.') ?></td>
                            <td class="text-end">R$ <?= number_format($totalGeral['base_icms'] ?? 0, 2, ',', '.') ?></td>
                            <td class="text-end">R$ <?= number_format($totalGeral['icms'] ?? 0, 2, ',', '.') ?></td>
                            <td class="text-end">R$ <?= number_format($totalGeral['ipi'] ?? 0, 2, ',', '.') ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Itens do Livro de Saídas -->
    <div class="card border-0 shadow-sm" style="border-radius:12px;overflow:hidden;">
        <div class="card-header py-2 bg-light">
            <h6 class="mb-0" style="font-size:.85rem;">
                <i class="fas fa-list me-2 text-primary"></i>Documentos de Saída
                <span class="badge bg-primary ms-2"><?= count($items) ?></span>
            </h6>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0" style="font-size:.8rem;">
                <thead class="table-light">
                    <tr>
                        <th style="width:50px;">Mod.</th>
                        <th style="width:70px;">Série</th>
                        <th style="width:90px;">Número</th>
                        <th style="width:100px;">Data</th>
                        <th>Destinatário</th>
                        <th style="width:70px;">UF</th>
                        <th style="width:70px;">CFOP</th>
                        <th class="text-end" style="width:120px;">Vlr. Contábil</th>
                        <th class="text-end" style="width:100px;">Base ICMS</th>
                        <th class="text-end" style="width:90px;">Alíq. ICMS</th>
                        <th class="text-end" style="width:100px;">ICMS</th>
                        <th class="text-end" style="width:90px;">IPI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="12" class="text-center text-muted py-5">
                            <i class="fas fa-file-invoice fa-3x mb-3 opacity-25"></i><br>
                            <span class="fw-bold">Nenhum documento de saída encontrado no período.</span><br>
                            <small>Altere o filtro de datas e tente novamente.</small>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <span class="badge bg-<?= ($item['modelo'] ?? 55) == 65 ? 'info' : 'primary' ?>" style="font-size:.65rem;">
                                <?= ($item['modelo'] ?? 55) == 65 ? 'NFC-e' : 'NF-e' ?>
                            </span>
                        </td>
                        <td><?= e($item['serie'] ?? '1') ?></td>
                        <td class="fw-bold"><?= e($item['numero'] ?? '') ?></td>
                        <td><?= !empty($item['data_emissao']) ? date('d/m/Y', strtotime($item['data_emissao'])) : '—' ?></td>
                        <td>
                            <span class="d-block text-truncate" style="max-width:200px;" title="<?= eAttr($item['dest_nome'] ?? '') ?>">
                                <?= e($item['dest_nome'] ?? '—') ?>
                            </span>
                        </td>
                        <td><?= e($item['dest_uf'] ?? '—') ?></td>
                        <td><span class="badge bg-light text-dark border" style="font-size:.68rem;"><?= e($item['cfop'] ?? '—') ?></span></td>
                        <td class="text-end fw-bold">R$ <?= number_format($item['valor_contabil'] ?? 0, 2, ',', '.') ?></td>
                        <td class="text-end">R$ <?= number_format($item['base_icms'] ?? 0, 2, ',', '.') ?></td>
                        <td class="text-end"><?= number_format($item['aliquota_icms'] ?? 0, 2, ',', '.') ?>%</td>
                        <td class="text-end">R$ <?= number_format($item['icms'] ?? 0, 2, ',', '.') ?></td>
                        <td class="text-end">R$ <?= number_format($item['ipi'] ?? 0, 2, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($items)): ?>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="7">TOTAL</td>
                        <td class="text-end">R$ <?= number_format($totalGeral['valor_contabil'] ?? 0, 2, ',', '.') ?></td>
                        <td class="text-end">R$ <?= number_format($totalGeral['base_icms'] ?? 0, 2, ',', '.') ?></td>
                        <td></td>
                        <td class="text-end">R$ <?= number_format($totalGeral['icms'] ?? 0, 2, ',', '.') ?></td>
                        <td class="text-end">R$ <?= number_format($totalGeral['ipi'] ?? 0, 2, ',', '.') ?></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>

<?php if (!$isAjax): ?>
</div>
<?php endif; ?>
