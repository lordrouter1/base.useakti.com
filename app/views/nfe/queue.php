<?php
/**
 * View: Fila de Emissão de NF-e
 * Gerencia a fila de emissão assíncrona de NF-e.
 *
 * @var array  $queueItems   Itens da fila paginados
 * @var int    $totalItems   Total de itens
 * @var int    $totalPages   Total de páginas
 * @var int    $ctPage       Página atual
 * @var array  $statusCounts Contagem por status da fila
 * @var string $baseUrl      URL base para paginação
 * @var array  $filters      Filtros aplicados
 */
$pageTitle = 'Fila de Emissão — NF-e';
$isAjax = $isAjax ?? false;

$queueStatusLabels = [
    'pending'    => ['label' => 'Pendente',     'color' => 'warning',   'icon' => 'fas fa-clock'],
    'processing' => ['label' => 'Processando',  'color' => 'info',      'icon' => 'fas fa-spinner fa-spin'],
    'completed'  => ['label' => 'Concluído',    'color' => 'success',   'icon' => 'fas fa-check-circle'],
    'failed'     => ['label' => 'Falhou',       'color' => 'danger',    'icon' => 'fas fa-times-circle'],
    'cancelled'  => ['label' => 'Cancelado',    'color' => 'secondary', 'icon' => 'fas fa-ban'],
];
?>

<?php if (!$isAjax): ?>
<div class="container py-4">

    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center pt-2 pb-2 mb-3 border-bottom">
        <div>
            <h1 class="h2 mb-0"><i class="fas fa-layer-group me-2 text-primary"></i> Fila de Emissão</h1>
            <small class="text-muted">Gerencie a fila de emissão assíncrona de NF-e</small>
        </div>
        <div class="d-flex gap-2">
            <a href="?page=nfe_documents" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-file-invoice me-1"></i> Listar NF-e
            </a>
            <a href="?page=nfe_documents&sec=dashboard" class="btn btn-outline-info btn-sm">
                <i class="fas fa-chart-bar me-1"></i> Dashboard
            </a>
        </div>
    </div>

    <!-- Flash messages -->
    <?php if (isset($_SESSION['flash_success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check me-2"></i> <?= e($_SESSION['flash_success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash_success']); endif; ?>
    <?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-times me-2"></i> <?= e($_SESSION['flash_error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash_error']); endif; ?>
<?php endif; ?>

    <!-- Cards de Resumo -->
    <div class="row g-3 mb-4">
        <div class="col-md-2 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div class="text-warning mb-1"><i class="fas fa-clock fa-2x"></i></div>
                    <h3 class="mb-0"><?= (int)($statusCounts['pending'] ?? 0) ?></h3>
                    <small class="text-muted">Pendentes</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div class="text-info mb-1"><i class="fas fa-spinner fa-2x"></i></div>
                    <h3 class="mb-0"><?= (int)($statusCounts['processing'] ?? 0) ?></h3>
                    <small class="text-muted">Processando</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div class="text-success mb-1"><i class="fas fa-check-circle fa-2x"></i></div>
                    <h3 class="mb-0"><?= (int)($statusCounts['completed'] ?? 0) ?></h3>
                    <small class="text-muted">Concluídos</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div class="text-danger mb-1"><i class="fas fa-times-circle fa-2x"></i></div>
                    <h3 class="mb-0"><?= (int)($statusCounts['failed'] ?? 0) ?></h3>
                    <small class="text-muted">Falhas</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div class="text-secondary mb-1"><i class="fas fa-ban fa-2x"></i></div>
                    <h3 class="mb-0"><?= (int)($statusCounts['cancelled'] ?? 0) ?></h3>
                    <small class="text-muted">Cancelados</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card border-0 shadow-sm h-100 bg-primary text-white">
                <div class="card-body text-center py-3">
                    <div class="mb-1"><i class="fas fa-list fa-2x"></i></div>
                    <h3 class="mb-0"><?= array_sum(array_map('intval', $statusCounts ?? [])) ?></h3>
                    <small class="opacity-75">Total na Fila</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Ações em lote -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-success btn-sm" id="btnProcessQueue" 
                    <?= (int)($statusCounts['pending'] ?? 0) === 0 ? 'disabled' : '' ?>>
                <i class="fas fa-play me-1"></i> Processar Pendentes (<?= (int)($statusCounts['pending'] ?? 0) ?>)
            </button>
        </div>
        <div class="d-flex gap-2">
            <!-- Filtro por Lote -->
            <select class="form-select form-select-sm d-inline-block" style="width: auto;" id="filterBatchId"
                    onchange="<?php if ($isAjax ?? false): ?>$.get('?page=nfe_documents&action=queue&_ajax=1&batch_id='+this.value+'&status='+$('#filterQueueStatus').val(),function(h){$('#filaContent').html(h)});<?php else: ?>location.href='?page=nfe_documents&action=queue&batch_id='+this.value+($('#filterQueueStatus').val()?'&status='+$('#filterQueueStatus').val():'');<?php endif; ?>">
                <option value="">Todos os Lotes</option>
                <?php foreach ($batches ?? [] as $b): ?>
                <option value="<?= e($b['batch_id']) ?>" <?= ($batchFilter ?? '') === $b['batch_id'] ? 'selected' : '' ?>>
                    <?= e($b['batch_id']) ?> (<?= (int)$b['completed'] ?>/<?= (int)$b['total'] ?>)
                </option>
                <?php endforeach; ?>
            </select>
            <!-- Filtro por Status -->
            <select class="form-select form-select-sm d-inline-block" style="width: auto;" id="filterQueueStatus"
                    onchange="<?php if ($isAjax ?? false): ?>$.get('?page=nfe_documents&action=queue&_ajax=1&status='+this.value+'&batch_id='+$('#filterBatchId').val(),function(h){$('#filaContent').html(h)});<?php else: ?>location.href='?page=nfe_documents&action=queue&status='+this.value+($('#filterBatchId').val()?'&batch_id='+$('#filterBatchId').val():'');<?php endif; ?>">
                <option value="">Todos os Status</option>
                <?php foreach ($queueStatusLabels as $key => $info): ?>
                <option value="<?= $key ?>" <?= ($filters['status'] ?? '') === $key ? 'selected' : '' ?>>
                    <?= $info['label'] ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- Tabela da Fila -->
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width:60px;" class="text-center">#</th>
                        <th>Pedido</th>
                        <th>Cliente</th>
                        <th style="width:120px;">Lote</th>
                        <th style="width:120px;">Status</th>
                        <th style="width:80px;">Tentativas</th>
                        <th style="width:160px;">Criado em</th>
                        <th style="width:160px;">Processado em</th>
                        <th>Erro</th>
                        <th style="width:100px;" class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($queueItems)): ?>
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">
                            <i class="fas fa-layer-group fa-3x mb-2 opacity-25"></i><br>
                            Nenhum item na fila de emissão.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($queueItems as $item): 
                        $si = $queueStatusLabels[$item['status']] ?? ['label' => $item['status'], 'color' => 'secondary', 'icon' => 'fas fa-circle'];
                    ?>
                    <tr>
                        <td class="text-center"><?= (int)$item['id'] ?></td>
                        <td>
                            <?php if (!empty($item['order_id'])): ?>
                            <a href="?page=pipeline&action=detail&id=<?= (int)$item['order_id'] ?>" class="text-decoration-none">
                                #<?= str_pad($item['order_id'], 4, '0', STR_PAD_LEFT) ?>
                            </a>
                            <?php else: ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e($item['customer_name'] ?? '—') ?></td>
                        <td>
                            <?php if (!empty($item['batch_id'])): ?>
                            <a href="?page=nfe_documents&action=queue&batch_id=<?= urlencode($item['batch_id']) ?>" class="text-decoration-none">
                                <span class="badge bg-info"><i class="fas fa-layer-group me-1"></i><?= e($item['batch_id']) ?></span>
                            </a>
                            <?php else: ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-<?= $si['color'] ?>">
                                <i class="<?= $si['icon'] ?> me-1"></i> <?= $si['label'] ?>
                            </span>
                        </td>
                        <td class="text-center"><?= (int)$item['attempts'] ?>/<?= (int)$item['max_attempts'] ?></td>
                        <td><small><?= date('d/m/Y H:i', strtotime($item['created_at'])) ?></small></td>
                        <td>
                            <?php if (!empty($item['completed_at'])): ?>
                            <small><?= date('d/m/Y H:i', strtotime($item['completed_at'])) ?></small>
                            <?php else: ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($item['error_message'])): ?>
                            <span class="text-danger small" title="<?= eAttr($item['error_message']) ?>">
                                <?= e(mb_strimwidth($item['error_message'], 0, 60, '...')) ?>
                            </span>
                            <?php else: ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ($item['status'] === 'pending'): ?>
                            <button type="button" class="btn btn-outline-danger btn-sm btn-cancel-queue"
                                    data-id="<?= (int)$item['id'] ?>" title="Cancelar">
                                <i class="fas fa-times"></i>
                            </button>
                            <?php elseif ($item['status'] === 'failed'): ?>
                            <button type="button" class="btn btn-outline-warning btn-sm btn-retry-queue"
                                    data-id="<?= (int)$item['id'] ?>" title="Reprocessar">
                                <i class="fas fa-redo"></i>
                            </button>
                            <?php elseif ($item['status'] === 'completed' && !empty($item['nfe_document_id'])): ?>
                            <a href="?page=nfe_documents&action=detail&id=<?= (int)$item['nfe_document_id'] ?>" 
                               class="btn btn-outline-primary btn-sm" title="Ver NF-e">
                                <i class="fas fa-eye"></i>
                            </a>
                            <?php endif; ?>
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

<?php if (!$isAjax): ?>
</div>
<?php endif; ?>

<script>
(function(__run){if(typeof jQuery!=='undefined'){jQuery(__run);}else{document.addEventListener('DOMContentLoaded',__run);}})(function(){
    var csrfToken = $('meta[name="csrf-token"]').attr('content');

    // Processar fila
    $('#btnProcessQueue').on('click', function(){
        var btn = $(this);
        Swal.fire({
            icon: 'question',
            title: 'Processar Fila?',
            text: 'Deseja processar todos os itens pendentes na fila de emissão?',
            showCancelButton: true,
            confirmButtonText: 'Sim, processar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#198754',
        }).then(function(result){
            if (!result.isConfirmed) return;

            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Processando...');
            $.ajax({
                url: '?page=nfe_documents&action=processQueue',
                method: 'POST',
                dataType: 'json',
                headers: { 'X-CSRF-TOKEN': csrfToken }
            }).done(function(resp){
                if (resp.success) {
                    Swal.fire('Concluído!', resp.message, 'success').then(function(){ location.reload(); });
                } else {
                    Swal.fire('Erro', resp.message || 'Erro ao processar fila.', 'error');
                }
            }).fail(function(){
                Swal.fire('Erro', 'Falha na comunicação com o servidor.', 'error');
            }).always(function(){
                btn.prop('disabled', false).html('<i class="fas fa-play me-1"></i> Processar Pendentes');
            });
        });
    });

    // Cancelar item da fila
    $('.btn-cancel-queue').on('click', function(){
        var btn = $(this);
        var id = btn.data('id');
        Swal.fire({
            icon: 'warning',
            title: 'Cancelar item?',
            text: 'Deseja remover este item da fila de emissão?',
            showCancelButton: true,
            confirmButtonText: 'Sim, cancelar',
            cancelButtonText: 'Não',
            confirmButtonColor: '#dc3545',
        }).then(function(result){
            if (!result.isConfirmed) return;

            $.ajax({
                url: '?page=nfe_documents&action=cancelQueue',
                method: 'POST',
                dataType: 'json',
                data: { id: id },
                headers: { 'X-CSRF-TOKEN': csrfToken }
            }).done(function(resp){
                if (resp.success) {
                    Swal.fire('Cancelado!', resp.message, 'success').then(function(){ location.reload(); });
                } else {
                    Swal.fire('Erro', resp.message, 'error');
                }
            }).fail(function(){
                Swal.fire('Erro', 'Falha na comunicação.', 'error');
            });
        });
    });

    // Reprocessar item com falha
    $('.btn-retry-queue').on('click', function(){
        var btn = $(this);
        var id = btn.data('id');
        $.ajax({
            url: '?page=nfe_documents&action=processQueue',
            method: 'POST',
            dataType: 'json',
            data: { item_id: id },
            headers: { 'X-CSRF-TOKEN': csrfToken }
        }).done(function(resp){
            if (resp.success) {
                Swal.fire('Reprocessado!', resp.message, 'success').then(function(){ location.reload(); });
            } else {
                Swal.fire('Erro', resp.message, 'error');
            }
        }).fail(function(){
            Swal.fire('Erro', 'Falha na comunicação.', 'error');
        });
    });
});
</script>
