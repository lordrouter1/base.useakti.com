<?php
/**
 * View: Gerenciamento de Webhooks — NF-e
 * Cadastro, edição, exclusão e teste de webhooks para eventos fiscais.
 *
 * @var array $webhooksList Lista de webhooks cadastrados
 */
$pageTitle = 'Webhooks — NF-e';

$availableEvents = [
    'nfe.emitted'      => 'NF-e Emitida',
    'nfe.authorized'    => 'NF-e Autorizada',
    'nfe.rejected'      => 'NF-e Rejeitada',
    'nfe.cancelled'     => 'NF-e Cancelada',
    'nfe.corrected'     => 'Carta de Correção',
    'nfe.batch'         => 'Emissão em Lote',
    'nfe.queue'         => 'Fila Processada',
    'nfe.manifestation' => 'Manifestação',
    'nfe.distdfe'       => 'DistDFe Consultado',
    'nfe.test'          => 'Teste',
    '*'                 => 'Todos os Eventos',
];
$isAjax = $isAjax ?? false;
?>

<?php if (!$isAjax): ?>
<div class="container py-4">

    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center pt-2 pb-2 mb-3 border-bottom">
        <div>
            <h1 class="h2 mb-0"><i class="fas fa-plug me-2 text-primary"></i> Webhooks NF-e</h1>
            <small class="text-muted">Notifique sistemas externos sobre eventos fiscais</small>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-success btn-sm" id="btnNewWebhook">
                <i class="fas fa-plus me-1"></i> Novo Webhook
            </button>
            <a href="?page=nfe_documents" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-file-invoice me-1"></i> NF-e
            </a>
            <a href="?page=nfe_documents&sec=dashboard" class="btn btn-outline-info btn-sm">
                <i class="fas fa-chart-bar me-1"></i> Dashboard
            </a>
        </div>
    </div>
<?php else: ?>
    <!-- Em modo AJAX, exibe botão de criar webhook inline -->
    <div class="d-flex justify-content-end mb-3">
        <button type="button" class="btn btn-success btn-sm" id="btnNewWebhook">
            <i class="fas fa-plus me-1"></i> Novo Webhook
        </button>
    </div>
<?php endif; ?>

    <!-- Info -->
    <div class="alert alert-info small py-2 mb-4">
        <i class="fas fa-info-circle me-2"></i>
        Webhooks enviam notificações HTTP POST para URLs externas quando eventos fiscais ocorrem no sistema.
        O payload é enviado em JSON com assinatura HMAC-SHA256 no header <code>X-Webhook-Signature</code>.
    </div>

    <!-- Lista de Webhooks -->
    <div class="row g-3">
        <?php if (empty($webhooksList)): ?>
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="fas fa-plug fa-3x text-muted mb-3 opacity-25"></i>
                    <p class="text-muted mb-3">Nenhum webhook configurado ainda.</p>
                    <button type="button" class="btn btn-primary" id="btnNewWebhookEmpty">
                        <i class="fas fa-plus me-1"></i> Criar Primeiro Webhook
                    </button>
                </div>
            </div>
        </div>
        <?php else: ?>
        <?php foreach ($webhooksList as $wh): 
            $events = is_string($wh['events']) ? json_decode($wh['events'], true) : ($wh['events'] ?? ['*']);
        ?>
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100 <?= !$wh['is_active'] ? 'opacity-50' : '' ?>">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                    <div>
                        <span class="badge bg-<?= $wh['is_active'] ? 'success' : 'secondary' ?> me-1">
                            <i class="fas fa-circle fa-xs"></i>
                        </span>
                        <strong class="small"><?= e($wh['name']) ?></strong>
                    </div>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-primary btn-edit-webhook"
                                data-webhook='<?= eAttr(json_encode($wh)) ?>' title="Editar">
                            <i class="fas fa-pen"></i>
                        </button>
                        <button type="button" class="btn btn-outline-success btn-test-webhook"
                                data-id="<?= (int)$wh['id'] ?>" title="Testar">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-delete-webhook"
                                data-id="<?= (int)$wh['id'] ?>" data-name="<?= eAttr($wh['name']) ?>" title="Excluir">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body py-2">
                    <div class="mb-2">
                        <small class="text-muted d-block">URL:</small>
                        <code class="small text-break"><?= e($wh['url']) ?></code>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted d-block">Eventos:</small>
                        <?php foreach ((array)$events as $ev): ?>
                        <span class="badge bg-light text-dark border me-1 mb-1" style="font-size:0.7rem;">
                            <?= e($availableEvents[$ev] ?? $ev) ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                    <div class="row small text-muted">
                        <div class="col-6">
                            <i class="fas fa-redo me-1"></i> Retries: <?= (int)$wh['retry_count'] ?>
                        </div>
                        <div class="col-6">
                            <i class="fas fa-clock me-1"></i> Timeout: <?= (int)$wh['timeout_seconds'] ?>s
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white py-2 d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        Criado: <?= date('d/m/Y', strtotime($wh['created_at'])) ?>
                    </small>
                    <button type="button" class="btn btn-link btn-sm p-0 btn-view-logs"
                            data-id="<?= (int)$wh['id'] ?>" data-name="<?= eAttr($wh['name']) ?>">
                        <i class="fas fa-history me-1"></i> Logs
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
<?php if (!$isAjax): ?>
</div>
<?php endif; ?>

<!-- Modal: Criar/Editar Webhook -->
<div class="modal fade" id="modalWebhook" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalWebhookTitle">
                    <i class="fas fa-plug me-2"></i> Novo Webhook
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="webhookId" value="0">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Nome <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="webhookName" placeholder="Ex: Notificação ERP">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">URL <span class="text-danger">*</span></label>
                        <input type="url" class="form-control" id="webhookUrl" placeholder="https://api.exemplo.com/webhook">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Secret (HMAC-SHA256)</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="webhookSecret" placeholder="Chave secreta (opcional)">
                            <button type="button" class="btn btn-outline-secondary" id="btnGenerateSecret" title="Gerar chave aleatória">
                                <i class="fas fa-key"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Retries</label>
                        <input type="number" class="form-control" id="webhookRetryCount" value="3" min="1" max="10">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Timeout (s)</label>
                        <input type="number" class="form-control" id="webhookTimeout" value="10" min="5" max="30">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">Eventos</label>
                        <div class="row g-2">
                            <?php foreach ($availableEvents as $evKey => $evLabel): ?>
                            <div class="col-md-4 col-6">
                                <div class="form-check">
                                    <input class="form-check-input webhook-event-check" type="checkbox" 
                                           value="<?= e($evKey) ?>" id="ev_<?= e(str_replace(['.',  '*'], ['_', 'all'], $evKey)) ?>">
                                    <label class="form-check-label small" for="ev_<?= e(str_replace(['.', '*'], ['_', 'all'], $evKey)) ?>">
                                        <?= e($evLabel) ?>
                                    </label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="webhookIsActive" checked>
                            <label class="form-check-label small fw-bold" for="webhookIsActive">Ativo</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnSaveWebhook">
                    <i class="fas fa-save me-1"></i> Salvar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Logs do Webhook -->
<div class="modal fade" id="modalWebhookLogs" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title"><i class="fas fa-history me-2"></i> Logs — <span id="logsWebhookName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="webhookLogsContainer">
                    <div class="text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                        <p class="text-muted mt-2">Carregando logs...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<script>
(function(__run){if(typeof jQuery!=='undefined'){jQuery(__run);}else{document.addEventListener('DOMContentLoaded',__run);}})(function(){
    var csrfToken = $('meta[name="csrf-token"]').attr('content');
    var webhookModal = null;

    function openWebhookModal() {
        if (!webhookModal) webhookModal = new bootstrap.Modal('#modalWebhook');
        webhookModal.show();
    }

    // Gerar secret aleatório
    $('#btnGenerateSecret').on('click', function(){
        var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        var secret = '';
        for (var i = 0; i < 32; i++) secret += chars.charAt(Math.floor(Math.random() * chars.length));
        $('#webhookSecret').val(secret);
    });

    // Novo Webhook
    $('#btnNewWebhook, #btnNewWebhookEmpty').on('click', function(){
        $('#modalWebhookTitle').html('<i class="fas fa-plug me-2"></i> Novo Webhook');
        $('#webhookId').val(0);
        $('#webhookName').val('');
        $('#webhookUrl').val('');
        $('#webhookSecret').val('');
        $('#webhookRetryCount').val(3);
        $('#webhookTimeout').val(10);
        $('#webhookIsActive').prop('checked', true);
        $('.webhook-event-check').prop('checked', false);
        $('#ev_all').prop('checked', true);
        openWebhookModal();
    });

    // Editar Webhook
    $('.btn-edit-webhook').on('click', function(){
        var wh = $(this).data('webhook');
        if (typeof wh === 'string') wh = JSON.parse(wh);

        $('#modalWebhookTitle').html('<i class="fas fa-pen me-2"></i> Editar Webhook');
        $('#webhookId').val(wh.id);
        $('#webhookName').val(wh.name);
        $('#webhookUrl').val(wh.url);
        $('#webhookSecret').val(wh.secret || '');
        $('#webhookRetryCount').val(wh.retry_count || 3);
        $('#webhookTimeout').val(wh.timeout_seconds || 10);
        $('#webhookIsActive').prop('checked', !!wh.is_active);

        // Eventos
        var events = typeof wh.events === 'string' ? JSON.parse(wh.events) : (wh.events || ['*']);
        $('.webhook-event-check').prop('checked', false);
        events.forEach(function(ev){
            var id = 'ev_' + ev.replace(/\./g, '_').replace('*', 'all');
            $('#' + id).prop('checked', true);
        });

        openWebhookModal();
    });

    // Salvar Webhook
    $('#btnSaveWebhook').on('click', function(){
        var name = $('#webhookName').val().trim();
        var url = $('#webhookUrl').val().trim();

        if (!name || !url) {
            Swal.fire('Atenção', 'Nome e URL são obrigatórios.', 'warning');
            return;
        }

        var events = [];
        $('.webhook-event-check:checked').each(function(){ events.push($(this).val()); });
        if (events.length === 0) events = ['*'];

        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Salvando...');

        $.ajax({
            url: '?page=nfe_documents&action=saveWebhook',
            method: 'POST',
            dataType: 'json',
            data: {
                id: $('#webhookId').val(),
                name: name,
                url: url,
                secret: $('#webhookSecret').val(),
                events: events.join(','),
                is_active: $('#webhookIsActive').is(':checked') ? 1 : 0,
                retry_count: $('#webhookRetryCount').val(),
                timeout_seconds: $('#webhookTimeout').val()
            },
            headers: { 'X-CSRF-TOKEN': csrfToken }
        }).done(function(resp){
            if (resp.success) {
                Swal.fire('Salvo!', resp.message, 'success').then(function(){ location.reload(); });
            } else {
                Swal.fire('Erro', resp.message, 'error');
            }
        }).fail(function(){
            Swal.fire('Erro', 'Falha na comunicação.', 'error');
        }).always(function(){
            btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Salvar');
        });
    });

    // Excluir Webhook
    $('.btn-delete-webhook').on('click', function(){
        var id = $(this).data('id');
        var name = $(this).data('name');
        Swal.fire({
            icon: 'warning',
            title: 'Excluir Webhook?',
            html: 'Excluir o webhook <strong>' + name + '</strong>? Esta ação é irreversível.',
            showCancelButton: true,
            confirmButtonText: 'Sim, excluir',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc3545',
        }).then(function(result){
            if (!result.isConfirmed) return;
            $.ajax({
                url: '?page=nfe_documents&action=deleteWebhook',
                method: 'POST',
                dataType: 'json',
                data: { id: id },
                headers: { 'X-CSRF-TOKEN': csrfToken }
            }).done(function(resp){
                if (resp.success) {
                    Swal.fire('Excluído!', resp.message, 'success').then(function(){ location.reload(); });
                } else {
                    Swal.fire('Erro', resp.message, 'error');
                }
            }).fail(function(){
                Swal.fire('Erro', 'Falha na comunicação.', 'error');
            });
        });
    });

    // Testar Webhook
    $('.btn-test-webhook').on('click', function(){
        var btn = $(this);
        var id = btn.data('id');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        $.ajax({
            url: '?page=nfe_documents&action=testWebhook',
            method: 'POST',
            dataType: 'json',
            data: { id: id },
            headers: { 'X-CSRF-TOKEN': csrfToken }
        }).done(function(resp){
            if (resp.success) {
                Swal.fire('Sucesso!', resp.message, 'success');
            } else {
                Swal.fire('Falha', resp.message, 'error');
            }
        }).fail(function(){
            Swal.fire('Erro', 'Falha na comunicação.', 'error');
        }).always(function(){
            btn.prop('disabled', false).html('<i class="fas fa-paper-plane"></i>');
        });
    });

    // Ver logs do webhook
    $('.btn-view-logs').on('click', function(){
        var id = $(this).data('id');
        var name = $(this).data('name');
        $('#logsWebhookName').text(name);
        $('#webhookLogsContainer').html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-muted"></i></div>');
        new bootstrap.Modal('#modalWebhookLogs').show();

        $.ajax({
            url: '?page=nfe_documents&action=webhookLogs&id=' + id,
            method: 'GET',
            dataType: 'json',
        }).done(function(resp){
            if (resp.success && resp.data && resp.data.length > 0) {
                var html = '<div class="table-responsive"><table class="table table-sm table-hover mb-0">';
                html += '<thead class="table-light"><tr><th>Data</th><th>Evento</th><th>Status</th><th>Resposta</th></tr></thead><tbody>';
                resp.data.forEach(function(log){
                    var statusBadge = log.response_code >= 200 && log.response_code < 300
                        ? '<span class="badge bg-success">' + log.response_code + '</span>'
                        : '<span class="badge bg-danger">' + (log.response_code || 'ERR') + '</span>';
                    html += '<tr>';
                    html += '<td><small>' + log.created_at + '</small></td>';
                    html += '<td><small>' + (log.event || '—') + '</small></td>';
                    html += '<td>' + statusBadge + '</td>';
                    html += '<td><small class="text-muted">' + (log.response_body || '—').substring(0, 100) + '</small></td>';
                    html += '</tr>';
                });
                html += '</tbody></table></div>';
                html += '<div class="text-muted small mt-2">Total: ' + resp.total + ' registros</div>';
                $('#webhookLogsContainer').html(html);
            } else {
                $('#webhookLogsContainer').html('<div class="text-center py-4 text-muted"><i class="fas fa-inbox fa-2x mb-2 opacity-25"></i><p>Nenhum log encontrado.</p></div>');
            }
        }).fail(function(){
            $('#webhookLogsContainer').html('<div class="alert alert-danger">Erro ao carregar logs.</div>');
        });
    });
});
</script>
