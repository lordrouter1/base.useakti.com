<?php
/**
 * View: Relatório de Cartas de Correção (CC-e)
 * Exibe histórico de CC-e com filtro por período.
 * FASE4-02
 *
 * @var array  $corrections        Lista de CC-e
 * @var array  $correctionsByMonth CC-e por mês (gráfico)
 * @var int    $totalCorrections   Total de CC-e no período
 * @var int    $totalNfes          Total de NF-e com CC-e
 * @var string $startDate          Data inicial do filtro
 * @var string $endDate            Data final do filtro
 */
$pageTitle = 'Relatório de Cartas de Correção (CC-e)';
$isAjax = $isAjax ?? false;
?>

<?php if (!$isAjax): ?>
<div class="container py-4">

    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center pt-2 pb-2 mb-3 border-bottom">
        <div>
            <h1 class="h2 mb-0"><i class="fas fa-edit me-2 text-primary"></i> Relatório de CC-e</h1>
            <small class="text-muted">Cartas de Correção emitidas</small>
        </div>
        <div class="d-flex gap-2">
            <a href="?page=nfe_documents&action=dashboard" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-chart-bar me-1"></i> Dashboard
            </a>
            <a href="?page=nfe_documents" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-file-invoice me-1"></i> Listar NF-e
            </a>
            <a href="?page=nfe_documents&action=exportReport&type=corrections&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>" 
               class="btn btn-success btn-sm">
                <i class="fas fa-file-excel me-1"></i> Exportar Excel
            </a>
        </div>
    </div>
<?php endif; ?>

    <!-- Filtro de período -->
    <div class="card shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
                <input type="hidden" name="page" value="nfe_documents">
                <input type="hidden" name="action" value="correctionReport">
                <div class="col-md-3">
                    <label for="start_date" class="form-label mb-1 small fw-semibold">Data Inicial</label>
                    <input type="date" class="form-control form-control-sm" id="start_date" name="start_date" 
                           value="<?= htmlspecialchars($startDate) ?>">
                </div>
                <div class="col-md-3">
                    <label for="end_date" class="form-label mb-1 small fw-semibold">Data Final</label>
                    <input type="date" class="form-control form-control-sm" id="end_date" name="end_date" 
                           value="<?= htmlspecialchars($endDate) ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="fas fa-search me-1"></i> Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- KPIs -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);">
                <div class="card-body text-center py-3">
                    <div class="text-primary mb-1"><i class="fas fa-edit fa-2x"></i></div>
                    <h3 class="mb-0"><?= $totalCorrections ?></h3>
                    <small class="text-muted" style="font-size: 0.7rem;">Total CC-e</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);">
                <div class="card-body text-center py-3">
                    <div class="text-success mb-1"><i class="fas fa-file-invoice fa-2x"></i></div>
                    <h3 class="mb-0"><?= $totalNfes ?></h3>
                    <small class="text-muted" style="font-size: 0.7rem;">NF-e Corrigidas</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);">
                <div class="card-body text-center py-3">
                    <div class="text-warning mb-1"><i class="fas fa-calculator fa-2x"></i></div>
                    <h3 class="mb-0"><?= $totalCorrections > 0 ? number_format($totalCorrections / max($totalNfes, 1), 1) : '0' ?></h3>
                    <small class="text-muted" style="font-size: 0.7rem;">Média CC-e/NF-e</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #f3e5f5 0%, #e1bee7 100%);">
                <div class="card-body text-center py-3">
                    <div class="text-purple mb-1"><i class="fas fa-calendar-alt fa-2x"></i></div>
                    <h3 class="mb-0 fs-6"><?= htmlspecialchars(date('d/m/Y', strtotime($startDate))) ?> — <?= htmlspecialchars(date('d/m/Y', strtotime($endDate))) ?></h3>
                    <small class="text-muted" style="font-size: 0.7rem;">Período</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela de CC-e -->
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0"><i class="fas fa-list me-2"></i>Detalhamento</h5>
            <span class="badge bg-primary"><?= $totalCorrections ?> registro(s)</span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($corrections)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                    Nenhuma Carta de Correção encontrada no período selecionado.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0" id="tblCorrections">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>NF-e</th>
                                <th>Destinatário</th>
                                <th style="width: 60px;">Seq.</th>
                                <th>Texto da Correção</th>
                                <th>Protocolo</th>
                                <th>Status</th>
                                <th>Usuário</th>
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($corrections as $cc): ?>
                            <tr>
                                <td class="text-muted"><?= (int) $cc['id'] ?></td>
                                <td>
                                    <a href="?page=nfe_documents&action=detail&id=<?= (int) $cc['nfe_document_id'] ?>" 
                                       class="text-decoration-none fw-semibold">
                                        Nº <?= htmlspecialchars($cc['numero'] ?? '-') ?>/<?= htmlspecialchars($cc['serie'] ?? '1') ?>
                                    </a>
                                    <?php if (!empty($cc['chave'])): ?>
                                        <br><small class="text-muted" style="font-size: 0.65rem;"><?= htmlspecialchars(substr($cc['chave'], 0, 22)) ?>…</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($cc['dest_nome'] ?? '-') ?>
                                    <?php if (!empty($cc['dest_cnpj_cpf'])): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($cc['dest_cnpj_cpf']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary"><?= (int) ($cc['seq_evento'] ?? 1) ?></span>
                                </td>
                                <td>
                                    <span title="<?= htmlspecialchars($cc['texto_correcao'] ?? '') ?>" style="max-width: 250px; display: inline-block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        <?= htmlspecialchars(mb_substr($cc['texto_correcao'] ?? '', 0, 80)) ?>
                                        <?= mb_strlen($cc['texto_correcao'] ?? '') > 80 ? '…' : '' ?>
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted"><?= htmlspecialchars($cc['protocolo'] ?? '-') ?></small>
                                </td>
                                <td>
                                    <?php
                                    $cStat = $cc['c_stat'] ?? '';
                                    $badgeClass = ($cStat == '135' || $cStat == '573') ? 'bg-success' : ($cStat ? 'bg-danger' : 'bg-secondary');
                                    ?>
                                    <span class="badge <?= $badgeClass ?>">
                                        <?= htmlspecialchars($cStat ?: '-') ?>
                                    </span>
                                    <?php if (!empty($cc['x_motivo'])): ?>
                                        <br><small class="text-muted" style="font-size: 0.65rem;"><?= htmlspecialchars(mb_substr($cc['x_motivo'], 0, 40)) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small><?= htmlspecialchars($cc['user_name'] ?? 'Sistema') ?></small>
                                </td>
                                <td>
                                    <small><?= htmlspecialchars($cc['created_at_fmt'] ?? '-') ?></small>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php if (!$isAjax): ?>
</div>
<?php endif; ?>
