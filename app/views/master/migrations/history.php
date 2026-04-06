<?php
/**
 * View: Migrations - Histórico de Auditoria
 */
$pageTitle = 'Histórico de Migrações';
$pageSubtitle = 'Auditoria completa de todas as migrações executadas';
$topbarActions = '<a href="?page=master_migrations" class="btn btn-akti-outline"><i class="fas fa-arrow-left me-2"></i>Voltar</a>';

$data = $history['data'] ?? [];
$total = $history['total'] ?? 0;
$currentPage = $history['page'] ?? 1;
$totalPages = $history['total_pages'] ?? 1;
$perPage = $history['per_page'] ?? 25;
?>

<!-- Stats -->
<div class="row g-4 mb-4">
    <div class="col-md-3 col-6">
        <div class="stat-card" style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
            <div class="stat-icon"><i class="fas fa-database"></i></div>
            <div class="stat-value"><?= (int)($stats['total'] ?? 0) ?></div>
            <div class="stat-label">Total Execuções</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card" style="background: linear-gradient(135deg, #10b981, #059669);">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-value"><?= (int)($stats['success_count'] ?? 0) ?></div>
            <div class="stat-label">Sucesso</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
            <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
            <div class="stat-value"><?= (int)($stats['failed_count'] ?? 0) + (int)($stats['partial_count'] ?? 0) ?></div>
            <div class="stat-label">Falhas/Parcial</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
            <div class="stat-icon"><i class="fas fa-code-branch"></i></div>
            <div class="stat-value"><?= (int)($stats['unique_migrations'] ?? 0) ?></div>
            <div class="stat-label">Migrações Únicas</div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="fas fa-filter" style="color: var(--akti-primary);"></i>
        <strong>Filtros</strong>
        <?php if (array_filter($filters)): ?>
            <a href="?page=master_migrations&action=history" class="btn btn-sm btn-outline-secondary ms-auto"><i class="fas fa-times me-1"></i>Limpar</a>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <form method="get" class="row g-3">
            <input type="hidden" name="page" value="master_migrations">
            <input type="hidden" name="action" value="history">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <option value="success" <?= ($filters['status'] ?? '') === 'success' ? 'selected' : '' ?>>Sucesso</option>
                    <option value="failed" <?= ($filters['status'] ?? '') === 'failed' ? 'selected' : '' ?>>Falha</option>
                    <option value="partial" <?= ($filters['status'] ?? '') === 'partial' ? 'selected' : '' ?>>Parcial</option>
                    <option value="skipped" <?= ($filters['status'] ?? '') === 'skipped' ? 'selected' : '' ?>>Já aplicado</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Banco</label>
                <select name="db_name" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <option value="akti_master" <?= ($filters['db_name'] ?? '') === 'akti_master' ? 'selected' : '' ?>>akti_master</option>
                    <?php foreach ($tenantDbs as $db): ?>
                        <option value="<?= htmlspecialchars($db) ?>" <?= ($filters['db_name'] ?? '') === $db ? 'selected' : '' ?>><?= htmlspecialchars($db) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Nome da migração</label>
                <input type="text" name="migration_name" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['migration_name'] ?? '') ?>" placeholder="Buscar...">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Data inicial</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Data final</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>">
            </div>
            <div class="col-12 text-end">
                <button type="submit" class="btn btn-sm btn-akti"><i class="fas fa-search me-1"></i>Filtrar</button>
            </div>
        </form>
    </div>
</div>

<!-- History Table -->
<div class="card">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="fas fa-history" style="color: var(--akti-primary);"></i>
        <strong>Registros</strong>
        <span class="badge bg-secondary ms-auto"><?= $total ?> registro(s)</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($data)): ?>
            <div class="text-center py-5 text-muted">
                <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                <p>Nenhum registro encontrado.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0" style="font-size:13px;">
                    <thead>
                        <tr style="background:#f8f9fa;">
                            <th class="ps-3" style="width:40px;">#</th>
                            <th>Migração</th>
                            <th>Banco</th>
                            <th>Status</th>
                            <th>Tempo</th>
                            <th>Aplicado por</th>
                            <th>Data</th>
                            <th style="width:60px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data as $row): ?>
                            <?php
                            $rowClass = match($row['status']) {
                                'success' => '',
                                'skipped' => 'table-light',
                                'partial' => 'table-warning',
                                default   => 'table-danger',
                            };
                            $hasWarnings = !empty($row['warnings']) && $row['warnings'] !== '[]';
                            $hasErrors = !empty($row['error_log']) && $row['error_log'] !== 'null';
                            ?>
                            <tr class="<?= $rowClass ?>">
                                <td class="ps-3 text-muted"><?= (int)$row['id'] ?></td>
                                <td>
                                    <code style="font-size:12px;"><?= htmlspecialchars($row['migration_name']) ?></code>
                                    <?php if ($hasWarnings): ?>
                                        <span class="badge bg-warning text-dark ms-1" title="Warnings"><i class="fas fa-exclamation-triangle"></i></span>
                                    <?php endif; ?>
                                    <?php if ($hasErrors): ?>
                                        <span class="badge bg-danger ms-1" title="Erros"><i class="fas fa-bug"></i></span>
                                    <?php endif; ?>
                                </td>
                                <td><code style="font-size:12px;"><?= htmlspecialchars($row['db_name']) ?></code></td>
                                <td>
                                    <?php
                                    $badgeMap = [
                                        'success' => 'bg-success',
                                        'failed'  => 'bg-danger',
                                        'partial' => 'bg-warning text-dark',
                                        'skipped' => 'bg-secondary',
                                    ];
                                    $labelMap = [
                                        'success' => 'Sucesso',
                                        'failed'  => 'Falha',
                                        'partial' => 'Parcial',
                                        'skipped' => 'Já aplicado',
                                    ];
                                    $badge = $badgeMap[$row['status']] ?? 'bg-secondary';
                                    $label = $labelMap[$row['status']] ?? $row['status'];
                                    ?>
                                    <span class="badge <?= $badge ?>"><?= $label ?></span>
                                </td>
                                <td>
                                    <?php if (!empty($row['execution_time_ms'])): ?>
                                        <span class="text-muted"><?= number_format($row['execution_time_ms']) ?>ms</span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($row['admin_name'] ?? 'Desconhecido') ?></td>
                                <td><span class="text-muted" title="<?= htmlspecialchars($row['applied_at'] ?? '') ?>"><?= !empty($row['applied_at']) ? date('d/m/Y H:i', strtotime($row['applied_at'])) : '-' ?></span></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary btn-view-detail" data-id="<?= (int)$row['id'] ?>" title="Ver detalhes">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav class="d-flex justify-content-center py-3">
                    <ul class="pagination pagination-sm mb-0">
                        <?php if ($currentPage > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=master_migrations&action=history&p=<?= $currentPage - 1 ?>&<?= http_build_query(array_filter($filters)) ?>"><i class="fas fa-chevron-left"></i></a>
                            </li>
                        <?php endif; ?>

                        <?php
                        $startPage = max(1, $currentPage - 2);
                        $endPage = min($totalPages, $currentPage + 2);
                        ?>

                        <?php if ($startPage > 1): ?>
                            <li class="page-item"><a class="page-link" href="?page=master_migrations&action=history&p=1&<?= http_build_query(array_filter($filters)) ?>">1</a></li>
                            <?php if ($startPage > 2): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                        <?php endif; ?>

                        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                                <a class="page-link" href="?page=master_migrations&action=history&p=<?= $i ?>&<?= http_build_query(array_filter($filters)) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($endPage < $totalPages): ?>
                            <?php if ($endPage < $totalPages - 1): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                            <li class="page-item"><a class="page-link" href="?page=master_migrations&action=history&p=<?= $totalPages ?>&<?= http_build_query(array_filter($filters)) ?>"><?= $totalPages ?></a></li>
                        <?php endif; ?>

                        <?php if ($currentPage < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=master_migrations&action=history&p=<?= $currentPage + 1 ?>&<?= http_build_query(array_filter($filters)) ?>"><i class="fas fa-chevron-right"></i></a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
$(document).ready(function() {
    // View migration detail
    $(document).on('click', '.btn-view-detail', function() {
        const id = $(this).data('id');
        const btn = $(this);
        btn.prop('disabled', true);

        $.ajax({
            url: '?page=master_migrations&action=historyDetail&id=' + id,
            method: 'GET',
            dataType: 'json',
            headers: {'X-CSRF-TOKEN': typeof csrfToken !== 'undefined' ? csrfToken : ''},
            success: function(resp) {
                btn.prop('disabled', false);
                if (!resp.success) {
                    Swal.fire('Erro', resp.message || 'Erro ao carregar detalhes', 'error');
                    return;
                }
                const d = resp.data;
                const statusBadge = {
                    'success': '<span class="badge bg-success">Sucesso</span>',
                    'failed': '<span class="badge bg-danger">Falha</span>',
                    'partial': '<span class="badge bg-warning text-dark">Parcial</span>',
                    'skipped': '<span class="badge bg-secondary">Já aplicado</span>'
                };

                let warningsHtml = '';
                if (d.warnings) {
                    try {
                        const w = typeof d.warnings === 'string' ? JSON.parse(d.warnings) : d.warnings;
                        if (w && w.length > 0) {
                            warningsHtml = '<div class="mt-3"><h6 class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i>Warnings (' + w.length + ')</h6><div style="max-height:150px;overflow-y:auto;"><table class="table table-sm table-bordered mb-0" style="font-size:12px;"><thead><tr><th>Level</th><th>Code</th><th>Message</th></tr></thead><tbody>';
                            w.forEach(function(warn) {
                                warningsHtml += '<tr><td>' + (warn.Level || '') + '</td><td>' + (warn.Code || '') + '</td><td>' + (warn.Message || '') + '</td></tr>';
                            });
                            warningsHtml += '</tbody></table></div></div>';
                        }
                    } catch(e) {}
                }

                let errorsHtml = '';
                if (d.error_log) {
                    try {
                        const errs = typeof d.error_log === 'string' ? JSON.parse(d.error_log) : d.error_log;
                        if (errs && errs.length > 0) {
                            errorsHtml = '<div class="mt-3"><h6 class="text-danger"><i class="fas fa-bug me-1"></i>Erros (' + errs.length + ')</h6><div style="max-height:200px;overflow-y:auto;">';
                            errs.forEach(function(err) {
                                errorsHtml += '<div class="alert alert-danger py-1 px-2 mb-1" style="font-size:12px;">' + (err.error || JSON.stringify(err)) + '</div>';
                            });
                            errorsHtml += '</div></div>';
                        }
                    } catch(e) {}
                }

                let sqlHtml = '';
                if (d.sql_content) {
                    sqlHtml = '<div class="mt-3"><h6><i class="fas fa-code me-1"></i>SQL Executado</h6><pre style="background:#1e1e1e;color:#d4d4d4;padding:12px;border-radius:8px;font-size:11px;max-height:300px;overflow:auto;white-space:pre-wrap;word-break:break-all;">' + $('<span>').text(d.sql_content).html() + '</pre></div>';
                }

                const execTime = d.execution_time_ms ? d.execution_time_ms + 'ms' : '-';

                Swal.fire({
                    title: '<i class="fas fa-file-alt me-2"></i>Detalhes da Migração #' + d.id,
                    html: '<div class="text-start">' +
                        '<table class="table table-sm mb-0" style="font-size:13px;">' +
                        '<tr><th style="width:140px;">Migração</th><td><code>' + $('<span>').text(d.migration_name).html() + '</code></td></tr>' +
                        '<tr><th>Banco</th><td><code>' + $('<span>').text(d.db_name).html() + '</code></td></tr>' +
                        '<tr><th>Status</th><td>' + (statusBadge[d.status] || d.status) + '</td></tr>' +
                        '<tr><th>Tempo</th><td>' + execTime + '</td></tr>' +
                        '<tr><th>Aplicado por</th><td>' + $('<span>').text(d.admin_name).html() + '</td></tr>' +
                        '<tr><th>Data</th><td>' + $('<span>').text(d.applied_at).html() + '</td></tr>' +
                        '<tr><th>Hash SQL</th><td><code style="font-size:10px;">' + $('<span>').text(d.sql_hash || '').html() + '</code></td></tr>' +
                        '</table>' +
                        errorsHtml +
                        warningsHtml +
                        sqlHtml +
                        '</div>',
                    width: '800px',
                    showCloseButton: true,
                    confirmButtonText: 'Fechar',
                    customClass: { popup: 'text-start' }
                });
            },
            error: function() {
                btn.prop('disabled', false);
                Swal.fire('Erro', 'Falha ao carregar detalhes da migração', 'error');
            }
        });
    });
});
</script>
