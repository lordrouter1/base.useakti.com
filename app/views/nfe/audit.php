<?php
/**
 * View: Auditoria do Módulo NF-e
 * Exibe logs detalhados de todas as ações realizadas no módulo fiscal.
 *
 * @var array  $auditLogs       Logs de auditoria paginados
 * @var int    $totalItems       Total de registros
 * @var int    $totalPages       Total de páginas
 * @var int    $ctPage           Página atual
 * @var array  $distinctActions  Ações distintas registradas
 * @var array  $actionCounts     Contagem por tipo de ação
 * @var string $baseUrl          URL para paginação
 * @var array  $filters          Filtros aplicados
 */
$pageTitle = 'Auditoria — NF-e';

$actionLabels = [
    'emit'              => ['label' => 'Emissão',            'color' => 'primary',   'icon' => 'fas fa-paper-plane'],
    'cancel'            => ['label' => 'Cancelamento',       'color' => 'danger',    'icon' => 'fas fa-ban'],
    'correction'        => ['label' => 'Carta de Correção',  'color' => 'info',      'icon' => 'fas fa-pen'],
    'download'          => ['label' => 'Download',           'color' => 'secondary', 'icon' => 'fas fa-download'],
    'batch_emit'        => ['label' => 'Emissão em Lote',    'color' => 'warning',   'icon' => 'fas fa-layer-group'],
    'queue_process'     => ['label' => 'Processar Fila',     'color' => 'success',   'icon' => 'fas fa-play'],
    'distdfe_query'     => ['label' => 'Consulta DistDFe',   'color' => 'info',      'icon' => 'fas fa-sync'],
    'manifestation'     => ['label' => 'Manifestação',       'color' => 'primary',   'icon' => 'fas fa-signature'],
    'webhook_config'    => ['label' => 'Config. Webhook',    'color' => 'dark',       'icon' => 'fas fa-cog'],
    'webhook_delete'    => ['label' => 'Excluir Webhook',    'color' => 'danger',    'icon' => 'fas fa-trash'],
    'danfe_settings'    => ['label' => 'Config. DANFE',      'color' => 'purple',    'icon' => 'fas fa-palette'],
    'view'              => ['label' => 'Visualização',       'color' => 'light',     'icon' => 'fas fa-eye'],
];
$isAjax = $isAjax ?? false;
?>

<?php if (!$isAjax): ?>
<div class="container py-4">

    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center pt-2 pb-2 mb-3 border-bottom">
        <div>
            <h1 class="h2 mb-0"><i class="fas fa-shield-alt me-2 text-primary"></i> Auditoria NF-e</h1>
            <small class="text-muted">Registro completo de ações no módulo fiscal</small>
        </div>
        <div class="d-flex gap-2">
            <a href="?page=nfe_documents" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-file-invoice me-1"></i> NF-e
            </a>
            <a href="?page=nfe_documents&sec=dashboard" class="btn btn-outline-info btn-sm">
                <i class="fas fa-chart-bar me-1"></i> Dashboard
            </a>
        </div>
    </div>
<?php endif; ?>

    <!-- Cards de contagem por ação -->
    <div class="row g-2 mb-4">
        <?php
        $topActions = array_slice($actionCounts, 0, 6, true);
        foreach ($topActions as $actionKey => $count):
            $ai = $actionLabels[$actionKey] ?? ['label' => ucfirst($actionKey), 'color' => 'secondary', 'icon' => 'fas fa-circle'];
        ?>
        <div class="col-md-2 col-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-2">
                    <div class="text-<?= $ai['color'] ?> mb-1"><i class="<?= $ai['icon'] ?> fa-lg"></i></div>
                    <h4 class="mb-0"><?= (int)$count ?></h4>
                    <small class="text-muted" style="font-size:0.7rem;"><?= $ai['label'] ?></small>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Filtros -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end" <?php if ($isAjax ?? false): ?>data-ajax-filter="auditoria" data-ajax-url="?page=nfe_documents&action=audit&_ajax=1"<?php endif; ?>>
                <input type="hidden" name="page" value="nfe_documents">
                <input type="hidden" name="action" value="audit">
                <?php if ($isAjax ?? false): ?><input type="hidden" name="_ajax" value="1"><?php endif; ?>
                <div class="col-auto">
                    <label class="form-label small mb-0">Ação</label>
                    <select class="form-select form-select-sm" name="action_filter">
                        <option value="">Todas</option>
                        <?php foreach ($distinctActions as $act): 
                            $label = $actionLabels[$act] ?? null;
                        ?>
                        <option value="<?= e($act) ?>" <?= ($filters['action'] ?? '') === $act ? 'selected' : '' ?>>
                            <?= $label ? $label['label'] : ucfirst($act) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label small mb-0">Tipo Entidade</label>
                    <select class="form-select form-select-sm" name="entity_type">
                        <option value="">Todos</option>
                        <option value="nfe_document" <?= ($filters['entity_type'] ?? '') === 'nfe_document' ? 'selected' : '' ?>>NF-e</option>
                        <option value="nfe_received" <?= ($filters['entity_type'] ?? '') === 'nfe_received' ? 'selected' : '' ?>>Doc. Recebido</option>
                        <option value="nfe_webhook" <?= ($filters['entity_type'] ?? '') === 'nfe_webhook' ? 'selected' : '' ?>>Webhook</option>
                        <option value="nfe_credential" <?= ($filters['entity_type'] ?? '') === 'nfe_credential' ? 'selected' : '' ?>>Credencial</option>
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label small mb-0">Data Início</label>
                    <input type="date" class="form-control form-control-sm" name="date_start"
                           value="<?= eAttr($filters['date_start'] ?? '') ?>">
                </div>
                <div class="col-auto">
                    <label class="form-label small mb-0">Data Fim</label>
                    <input type="date" class="form-control form-control-sm" name="date_end"
                           value="<?= eAttr($filters['date_end'] ?? '') ?>">
                </div>
                <div class="col-auto">
                    <label class="form-label small mb-0">Buscar</label>
                    <input type="text" class="form-control form-control-sm" name="search"
                           placeholder="Descrição, IP, usuário..."
                           value="<?= eAttr($filters['search'] ?? '') ?>">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search me-1"></i> Filtrar</button>
                    <a href="<?= ($isAjax ?? false) ? 'javascript:void(0)' : '?page=nfe_documents&action=audit' ?>" 
                       class="btn btn-sm btn-outline-secondary"
                       <?php if ($isAjax ?? false): ?>onclick="$.get('?page=nfe_documents&action=audit&_ajax=1',function(h){$('#auditoriaContent').html(h)})"<?php endif; ?>>
                        <i class="fas fa-times me-1"></i> Limpar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabela de Auditoria -->
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width:60px;">#</th>
                        <th style="width:160px;">Data/Hora</th>
                        <th style="width:140px;">Ação</th>
                        <th>Descrição</th>
                        <th style="width:130px;">Entidade</th>
                        <th style="width:130px;">Usuário</th>
                        <th style="width:120px;">IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($auditLogs)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="fas fa-shield-alt fa-3x mb-2 opacity-25"></i><br>
                            Nenhum registro de auditoria encontrado.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($auditLogs as $log):
                        $ai = $actionLabels[$log['action']] ?? ['label' => ucfirst($log['action']), 'color' => 'secondary', 'icon' => 'fas fa-circle'];
                    ?>
                    <tr>
                        <td class="text-muted"><?= (int)$log['id'] ?></td>
                        <td>
                            <small><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></small>
                        </td>
                        <td>
                            <span class="badge bg-<?= $ai['color'] ?>">
                                <i class="<?= $ai['icon'] ?> me-1"></i> <?= $ai['label'] ?>
                            </span>
                        </td>
                        <td>
                            <span class="small"><?= e($log['description'] ?? '—') ?></span>
                            <?php if (!empty($log['extra_data'])): ?>
                            <button type="button" class="btn btn-link btn-sm p-0 ms-1 btn-view-extra"
                                    data-extra="<?= eAttr($log['extra_data']) ?>" title="Ver detalhes">
                                <i class="fas fa-info-circle text-muted"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($log['entity_type'])): ?>
                            <small class="text-muted">
                                <?= e($log['entity_type']) ?>
                                <?php if (!empty($log['entity_id'])): ?>
                                <span class="badge bg-light text-dark">#<?= (int)$log['entity_id'] ?></span>
                                <?php endif; ?>
                            </small>
                            <?php else: ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <small><?= e($log['user_name'] ?? 'Sistema') ?></small>
                        </td>
                        <td>
                            <small class="text-muted font-monospace"><?= e($log['ip_address'] ?? '—') ?></small>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Paginação -->
    <?php if ($totalPages > 1): ?>
    <nav class="mt-3">
        <ul class="pagination pagination-sm justify-content-center">
            <li class="page-item <?= $ctPage <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $baseUrl ?>&pg=<?= $ctPage - 1 ?>">« Anterior</a>
            </li>
            <?php for ($p = max(1, $ctPage - 2); $p <= min($totalPages, $ctPage + 2); $p++): ?>
            <li class="page-item <?= $p === $ctPage ? 'active' : '' ?>">
                <a class="page-link" href="<?= $baseUrl ?>&pg=<?= $p ?>"><?= $p ?></a>
            </li>
            <?php endfor; ?>
            <li class="page-item <?= $ctPage >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $baseUrl ?>&pg=<?= $ctPage + 1 ?>">Próxima »</a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>

    <!-- Info do total -->
    <div class="text-center mt-2">
        <small class="text-muted"><?= number_format($totalItems, 0, ',', '.') ?> registro(s) encontrado(s)</small>
    </div>
<?php if (!$isAjax): ?>
</div>
<?php endif; ?>

<!-- Modal: Detalhes Extra -->
<div class="modal fade" id="modalExtraData" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title"><i class="fas fa-info-circle me-2"></i> Detalhes do Registro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <pre id="extraDataContent" class="bg-dark text-light p-3 rounded" style="max-height:400px; overflow:auto; font-size:0.8rem;"></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<script>
(function(__run){if(typeof jQuery!=='undefined'){jQuery(__run);}else{document.addEventListener('DOMContentLoaded',__run);}})(function(){
    // Visualizar detalhes extra (JSON)
    $('.btn-view-extra').on('click', function(){
        var extra = $(this).data('extra');
        try {
            var parsed = typeof extra === 'string' ? JSON.parse(extra) : extra;
            $('#extraDataContent').text(JSON.stringify(parsed, null, 2));
        } catch(e) {
            $('#extraDataContent').text(extra);
        }
        new bootstrap.Modal('#modalExtraData').show();
    });
});
</script>
