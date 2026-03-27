<?php
/**
 * View: Livro de Registro de Entradas — FASE5-07
 *
 * Exibe o livro fiscal de registro de entradas com dados de NF-e
 * recebidas via DistDFe manifestadas como "Confirmação da Operação".
 *
 * @var array  $items       Itens do livro de entradas
 * @var array  $totalGeral  Totais gerais consolidados
 * @var string $startDate   Data início do período
 * @var string $endDate     Data fim do período
 * @var bool   $isAjax      Se carregamento via AJAX
 */
$pageTitle = 'Livro de Registro de Entradas';
$isAjax = $isAjax ?? false;
?>

<?php if (!$isAjax): ?>
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div>
            <h1 class="h2 mb-1"><i class="fas fa-sign-in-alt me-2 text-success"></i>Livro de Registro de Entradas</h1>
            <p class="text-muted mb-0" style="font-size:.82rem;">Modelo P1 — Registro de documentos fiscais de entrada (NF-e recebidas).</p>
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
            <form method="GET" class="row g-2 align-items-end" <?php if ($isAjax): ?>data-ajax-filter="livroEntradas" data-ajax-url="?page=nfe_documents&action=livroEntradas"<?php endif; ?>>
                <?php if (!$isAjax): ?>
                <input type="hidden" name="page" value="nfe_documents">
                <input type="hidden" name="action" value="livroEntradas">
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
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print();">
                        <i class="fas fa-print me-1"></i> Imprimir
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Totais Gerais -->
    <?php if (!empty($items)): ?>
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100" style="background:linear-gradient(135deg,#e8f5e9 0%,#c8e6c9 100%);border-radius:12px;">
                <div class="card-body text-center py-3">
                    <i class="fas fa-file-invoice fa-lg text-success opacity-75"></i>
                    <h3 class="mb-0 mt-1"><?= (int)($totalGeral['qtd'] ?? 0) ?></h3>
                    <small class="text-muted" style="font-size:.7rem;">Documentos</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100" style="background:linear-gradient(135deg,#e3f2fd 0%,#bbdefb 100%);border-radius:12px;">
                <div class="card-body text-center py-3">
                    <i class="fas fa-coins fa-lg text-primary opacity-75"></i>
                    <h3 class="mb-0 mt-1 fs-5">R$ <?= number_format($totalGeral['valor_total'] ?? 0, 0, ',', '.') ?></h3>
                    <small class="text-muted" style="font-size:.7rem;">Valor Total</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100" style="background:linear-gradient(135deg,#fff3e0 0%,#ffe0b2 100%);border-radius:12px;">
                <div class="card-body text-center py-3">
                    <i class="fas fa-percentage fa-lg text-warning opacity-75"></i>
                    <h3 class="mb-0 mt-1 fs-5">R$ <?= number_format($totalGeral['icms'] ?? 0, 0, ',', '.') ?></h3>
                    <small class="text-muted" style="font-size:.7rem;">ICMS</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100" style="background:linear-gradient(135deg,#fce4ec 0%,#f8bbd0 100%);border-radius:12px;">
                <div class="card-body text-center py-3">
                    <i class="fas fa-industry fa-lg text-danger opacity-75"></i>
                    <h3 class="mb-0 mt-1 fs-5">R$ <?= number_format($totalGeral['ipi'] ?? 0, 0, ',', '.') ?></h3>
                    <small class="text-muted" style="font-size:.7rem;">IPI</small>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Tabela de Documentos de Entrada -->
    <div class="card border-0 shadow-sm" style="border-radius:12px;overflow:hidden;">
        <div class="card-header py-2 bg-light">
            <h6 class="mb-0" style="font-size:.85rem;">
                <i class="fas fa-list me-2 text-success"></i>Documentos de Entrada
                <span class="badge bg-success ms-2"><?= count($items) ?></span>
            </h6>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0" style="font-size:.8rem;">
                <thead class="table-light">
                    <tr>
                        <th style="width:90px;">Número</th>
                        <th style="width:70px;">Série</th>
                        <th style="width:100px;">Data</th>
                        <th>Emitente (Remetente)</th>
                        <th style="width:120px;">CNPJ Emitente</th>
                        <th style="width:60px;">UF</th>
                        <th style="width:70px;">CFOP</th>
                        <th class="text-end" style="width:120px;">Valor Total</th>
                        <th class="text-end" style="width:100px;">Base ICMS</th>
                        <th class="text-end" style="width:90px;">ICMS</th>
                        <th class="text-end" style="width:90px;">IPI</th>
                        <th style="width:100px;">Chave</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="12" class="text-center text-muted py-5">
                            <i class="fas fa-inbox fa-3x mb-3 opacity-25"></i><br>
                            <span class="fw-bold">Nenhum documento de entrada encontrado no período.</span><br>
                            <small>Verifique se há NF-e recebidas com manifestação "Confirmação da Operação".</small>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td class="fw-bold"><?= e($item['numero'] ?? '') ?></td>
                        <td><?= e($item['serie'] ?? '1') ?></td>
                        <td><?= !empty($item['data_emissao']) ? date('d/m/Y', strtotime($item['data_emissao'])) : '—' ?></td>
                        <td>
                            <span class="d-block text-truncate" style="max-width:200px;" title="<?= eAttr($item['emit_nome'] ?? '') ?>">
                                <?= e($item['emit_nome'] ?? '—') ?>
                            </span>
                        </td>
                        <td><small><?= e($item['emit_cnpj'] ?? '—') ?></small></td>
                        <td><?= e($item['emit_uf'] ?? '—') ?></td>
                        <td><span class="badge bg-light text-dark border" style="font-size:.68rem;"><?= e($item['cfop'] ?? '—') ?></span></td>
                        <td class="text-end fw-bold">R$ <?= number_format($item['valor_total'] ?? 0, 2, ',', '.') ?></td>
                        <td class="text-end">R$ <?= number_format($item['base_icms'] ?? 0, 2, ',', '.') ?></td>
                        <td class="text-end">R$ <?= number_format($item['icms'] ?? 0, 2, ',', '.') ?></td>
                        <td class="text-end">R$ <?= number_format($item['ipi'] ?? 0, 2, ',', '.') ?></td>
                        <td>
                            <?php if (!empty($item['chave'])): ?>
                            <span class="d-block text-truncate" style="max-width:100px;font-size:.65rem;" title="<?= eAttr($item['chave']) ?>">
                                <?= e(substr($item['chave'], -10)) ?>
                            </span>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($items)): ?>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="7">TOTAL</td>
                        <td class="text-end">R$ <?= number_format($totalGeral['valor_total'] ?? 0, 2, ',', '.') ?></td>
                        <td class="text-end">R$ <?= number_format($totalGeral['base_icms'] ?? 0, 2, ',', '.') ?></td>
                        <td class="text-end">R$ <?= number_format($totalGeral['icms'] ?? 0, 2, ',', '.') ?></td>
                        <td class="text-end">R$ <?= number_format($totalGeral['ipi'] ?? 0, 2, ',', '.') ?></td>
                        <td></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>

<?php if (!$isAjax): ?>
</div>
<?php endif; ?>
