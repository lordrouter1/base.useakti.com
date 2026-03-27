<?php
/**
 * View: Documentos Recebidos (DistDFe)
 * Listagem de NF-e recebidas de fornecedores via consulta DistDFe na SEFAZ.
 *
 * @var array  $receivedDocs     Documentos recebidos paginados
 * @var int    $totalItems       Total de registros
 * @var int    $totalPages       Total de páginas
 * @var int    $ctPage           Página atual
 * @var array  $statusCounts     Contagem por status de manifestação
 * @var string $baseUrl          URL para paginação
 * @var array  $filters          Filtros aplicados
 * @var bool   $distdfeAvailable Se o serviço DistDFe está disponível
 */
$pageTitle = 'Documentos Recebidos — NF-e';
$isAjax = $isAjax ?? false;

$manifestStatusLabels = [
    'pendente'       => ['label' => 'Pendente',           'color' => 'warning',   'icon' => 'fas fa-clock'],
    'ciencia'        => ['label' => 'Ciência',            'color' => 'info',      'icon' => 'fas fa-eye'],
    'confirmada'     => ['label' => 'Confirmada',         'color' => 'success',   'icon' => 'fas fa-check-circle'],
    'desconhecida'   => ['label' => 'Desconhecida',       'color' => 'dark',      'icon' => 'fas fa-question-circle'],
    'nao_realizada'  => ['label' => 'Não Realizada',      'color' => 'danger',    'icon' => 'fas fa-times-circle'],
];
?>

<?php if (!$isAjax): ?>
<div class="container py-4">

    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center pt-2 pb-2 mb-3 border-bottom">
        <div>
            <h1 class="h2 mb-0"><i class="fas fa-inbox me-2 text-primary"></i> Documentos Recebidos</h1>
            <small class="text-muted">NF-e recebidas de fornecedores via DistDFe</small>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-success btn-sm" id="btnQueryDistDFe" <?= !$distdfeAvailable ? 'disabled' : '' ?>>
                <i class="fas fa-sync me-1"></i> Consultar SEFAZ
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#modalQueryByChave">
                <i class="fas fa-search me-1"></i> Buscar por Chave
            </button>
            <a href="?page=nfe_documents" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-file-invoice me-1"></i> NF-e Emitidas
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

    <?php if (!$distdfeAvailable): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>DistDFe indisponível.</strong> Configure as credenciais SEFAZ e o certificado digital antes de consultar documentos recebidos.
    </div>
    <?php endif; ?>

    <!-- Cards de Status -->
    <div class="row g-3 mb-4">
        <?php foreach ($manifestStatusLabels as $key => $info): ?>
        <div class="col">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div class="text-<?= $info['color'] ?> mb-1"><i class="<?= $info['icon'] ?> fa-2x"></i></div>
                    <h3 class="mb-0"><?= (int)($statusCounts[$key] ?? 0) ?></h3>
                    <small class="text-muted"><?= $info['label'] ?></small>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Filtros -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end" <?php if ($isAjax ?? false): ?>data-ajax-filter="recebidos" data-ajax-url="?page=nfe_documents&action=received&_ajax=1"<?php endif; ?>>
                <input type="hidden" name="page" value="nfe_documents">
                <input type="hidden" name="action" value="received">
                <?php if ($isAjax ?? false): ?><input type="hidden" name="_ajax" value="1"><?php endif; ?>
                <div class="col-auto">
                    <label class="form-label small mb-0">Status Manifestação</label>
                    <select class="form-select form-select-sm" name="status">
                        <option value="">Todos</option>
                        <?php foreach ($manifestStatusLabels as $key => $info): ?>
                        <option value="<?= $key ?>" <?= ($filters['status'] ?? '') === $key ? 'selected' : '' ?>>
                            <?= $info['label'] ?>
                        </option>
                        <?php endforeach; ?>
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
                           placeholder="Chave, CNPJ, emitente..."
                           value="<?= eAttr($filters['search'] ?? '') ?>">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search me-1"></i> Filtrar</button>
                    <a href="<?= ($isAjax ?? false) ? 'javascript:void(0)' : '?page=nfe_documents&action=received' ?>" 
                       class="btn btn-sm btn-outline-secondary"
                       <?php if ($isAjax ?? false): ?>onclick="$.get('?page=nfe_documents&action=received&_ajax=1',function(h){$('#recebidosContent').html(h)})"<?php endif; ?>>
                        <i class="fas fa-times me-1"></i> Limpar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabela -->
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width:60px;">#</th>
                        <th>Emitente</th>
                        <th>CNPJ Emitente</th>
                        <th>Chave de Acesso</th>
                        <th style="width:100px;">Valor</th>
                        <th style="width:130px;">Data Emissão</th>
                        <th style="width:140px;">Manifestação</th>
                        <th style="width:140px;" class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($receivedDocs)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-3x mb-2 opacity-25"></i><br>
                            Nenhum documento recebido encontrado.
                            <?php if ($distdfeAvailable): ?>
                            <br><small>Clique em "Consultar SEFAZ" para buscar novos documentos.</small>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($receivedDocs as $doc):
                        $msi = $manifestStatusLabels[$doc['manifestation_status'] ?? 'pendente'] ?? $manifestStatusLabels['pending'];
                    ?>
                    <tr>
                        <td><?= (int)$doc['id'] ?></td>
                        <td>
                            <span class="d-block fw-semibold"><?= e($doc['nome_emitente'] ?? '—') ?></span>
                        </td>
                        <td><small class="text-muted"><?= e($doc['cnpj_emitente'] ?? '—') ?></small></td>
                        <td>
                            <small class="font-monospace text-truncate d-inline-block" style="max-width: 180px;"
                                   title="<?= eAttr($doc['chave'] ?? '') ?>">
                                <?= e($doc['chave'] ?? '—') ?>
                            </small>
                        </td>
                        <td>
                            <?php if (isset($doc['valor_total'])): ?>
                            R$ <?= number_format((float)$doc['valor_total'], 2, ',', '.') ?>
                            <?php else: ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($doc['data_emissao'])): ?>
                            <small><?= date('d/m/Y', strtotime($doc['data_emissao'])) ?></small>
                            <?php else: ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-<?= $msi['color'] ?>">
                                <i class="<?= $msi['icon'] ?> me-1"></i> <?= $msi['label'] ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <?php if (($doc['manifestation_status'] ?? 'pendente') === 'pendente'): ?>
                                <!-- Ações de manifestação -->
                                <button type="button" class="btn btn-outline-info btn-manifest"
                                        data-id="<?= (int)$doc['id'] ?>" data-type="ciencia"
                                        data-emitente="<?= eAttr($doc['nome_emitente'] ?? '') ?>"
                                        title="Ciência da Operação">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button type="button" class="btn btn-outline-success btn-manifest"
                                        data-id="<?= (int)$doc['id'] ?>" data-type="confirmada"
                                        data-emitente="<?= eAttr($doc['nome_emitente'] ?? '') ?>"
                                        title="Confirmar Operação">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-manifest"
                                        data-id="<?= (int)$doc['id'] ?>" data-type="desconhecida"
                                        data-emitente="<?= eAttr($doc['nome_emitente'] ?? '') ?>"
                                        title="Desconhecimento da Operação">
                                    <i class="fas fa-question"></i>
                                </button>
                                <button type="button" class="btn btn-outline-dark btn-manifest-nao-realizada"
                                        data-id="<?= (int)$doc['id'] ?>"
                                        data-emitente="<?= eAttr($doc['nome_emitente'] ?? '') ?>"
                                        title="Operação Não Realizada">
                                    <i class="fas fa-times"></i>
                                </button>
                                <?php elseif (($doc['manifestation_status'] ?? '') === 'ciencia'): ?>
                                <!-- Após ciência, pode confirmar ou negar -->
                                <button type="button" class="btn btn-outline-success btn-manifest"
                                        data-id="<?= (int)$doc['id'] ?>" data-type="confirmada"
                                        data-emitente="<?= eAttr($doc['nome_emitente'] ?? '') ?>"
                                        title="Confirmar Operação">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button type="button" class="btn btn-outline-dark btn-manifest-nao-realizada"
                                        data-id="<?= (int)$doc['id'] ?>"
                                        data-emitente="<?= eAttr($doc['nome_emitente'] ?? '') ?>"
                                        title="Operação Não Realizada">
                                    <i class="fas fa-times"></i>
                                </button>
                                <?php endif; ?>

                                <?php if (!empty($doc['xml_content'])): ?>
                                <a href="#" class="btn btn-outline-secondary btn-view-xml"
                                   data-id="<?= (int)$doc['id'] ?>" title="Ver XML">
                                    <i class="fas fa-file-code"></i>
                                </a>
                                <?php endif; ?>
                            </div>
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

<!-- Modal: Buscar por Chave de Acesso -->
<div class="modal fade" id="modalQueryByChave" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title"><i class="fas fa-search me-2"></i> Buscar por Chave de Acesso</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold small">Chave de Acesso (44 dígitos)</label>
                    <input type="text" class="form-control" id="inputChaveAcesso" maxlength="44"
                           placeholder="Informe a chave de acesso da NF-e...">
                    <div class="form-text">Apenas números, 44 dígitos.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary" id="btnQueryByChave">
                    <i class="fas fa-search me-1"></i> Consultar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Manifestação "Não Realizada" (requer justificativa) -->
<div class="modal fade" id="modalNaoRealizada" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-times-circle me-2"></i> Operação Não Realizada</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="naoRealizadaDocId">
                <p>Informar que a operação do documento de <strong id="naoRealizadaEmitente"></strong> <strong>não foi realizada</strong>.</p>
                <div class="mb-3">
                    <label class="form-label fw-bold small">Justificativa (mínimo 15 caracteres) <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="naoRealizadaJustificativa" rows="3" minlength="15"
                              placeholder="Descreva o motivo..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btnConfirmNaoRealizada">
                    <i class="fas fa-times me-1"></i> Confirmar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function(__run){if(typeof jQuery!=='undefined'){jQuery(__run);}else{document.addEventListener('DOMContentLoaded',__run);}})(function(){
    var csrfToken = $('meta[name="csrf-token"]').attr('content');

    // Consultar DistDFe na SEFAZ
    $('#btnQueryDistDFe').on('click', function(){
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Consultando SEFAZ...');
        $.ajax({
            url: '?page=nfe_documents&action=queryDistDFe',
            method: 'POST',
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': csrfToken }
        }).done(function(resp){
            if (resp.success) {
                var msg = 'Documentos encontrados: ' + (resp.total || 0);
                if ((resp.new_docs || 0) > 0) msg += ' (' + resp.new_docs + ' novos)';
                Swal.fire('Consulta Concluída', msg, 'success').then(function(){ location.reload(); });
            } else {
                Swal.fire('Erro', resp.message || 'Erro ao consultar SEFAZ.', 'error');
            }
        }).fail(function(){
            Swal.fire('Erro', 'Falha na comunicação com o servidor.', 'error');
        }).always(function(){
            btn.prop('disabled', false).html('<i class="fas fa-sync me-1"></i> Consultar SEFAZ');
        });
    });

    // Consultar por chave de acesso
    $('#btnQueryByChave').on('click', function(){
        var chave = $('#inputChaveAcesso').val().replace(/\D/g, '');
        if (chave.length !== 44) {
            Swal.fire('Atenção', 'A chave de acesso deve ter 44 dígitos.', 'warning');
            return;
        }
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Consultando...');
        $.ajax({
            url: '?page=nfe_documents&action=queryDistDFeByChave',
            method: 'POST',
            dataType: 'json',
            data: { chave: chave },
            headers: { 'X-CSRF-TOKEN': csrfToken }
        }).done(function(resp){
            if (resp.success) {
                Swal.fire('Sucesso', resp.message || 'Documento encontrado!', 'success').then(function(){ location.reload(); });
            } else {
                Swal.fire('Erro', resp.message || 'Documento não encontrado.', 'error');
            }
        }).fail(function(){
            Swal.fire('Erro', 'Falha na comunicação.', 'error');
        }).always(function(){
            btn.prop('disabled', false).html('<i class="fas fa-search me-1"></i> Consultar');
        });
    });

    // Manifestação simples (ciência, confirmação, desconhecimento)
    $('.btn-manifest').on('click', function(){
        var btn = $(this);
        var docId = btn.data('id');
        var type = btn.data('type');
        var typeLabels = {
            ciencia: 'Ciência da Operação',
            confirmada: 'Confirmação da Operação',
            desconhecida: 'Desconhecimento da Operação'
        };

        Swal.fire({
            icon: 'question',
            title: typeLabels[type] || type,
            text: 'Confirma o envio da manifestação "' + (typeLabels[type] || type) + '" para este documento?',
            showCancelButton: true,
            confirmButtonText: 'Sim, enviar',
            cancelButtonText: 'Cancelar',
        }).then(function(result){
            if (!result.isConfirmed) return;

            btn.prop('disabled', true);
            $.ajax({
                url: '?page=nfe_documents&action=manifest',
                method: 'POST',
                dataType: 'json',
                data: { doc_id: docId, type: type },
                headers: { 'X-CSRF-TOKEN': csrfToken }
            }).done(function(resp){
                if (resp.success) {
                    Swal.fire('Enviada!', resp.message || 'Manifestação registrada.', 'success').then(function(){ location.reload(); });
                } else {
                    Swal.fire('Erro', resp.message, 'error');
                }
            }).fail(function(){
                Swal.fire('Erro', 'Falha na comunicação.', 'error');
            }).always(function(){
                btn.prop('disabled', false);
            });
        });
    });

    // Manifestação "Não Realizada" (com justificativa)
    $('.btn-manifest-nao-realizada').on('click', function(){
        $('#naoRealizadaDocId').val($(this).data('id'));
        $('#naoRealizadaEmitente').text($(this).data('emitente'));
        $('#naoRealizadaJustificativa').val('');
        new bootstrap.Modal('#modalNaoRealizada').show();
    });

    $('#btnConfirmNaoRealizada').on('click', function(){
        var justificativa = $('#naoRealizadaJustificativa').val().trim();
        if (justificativa.length < 15) {
            Swal.fire('Atenção', 'Justificativa deve ter pelo menos 15 caracteres.', 'warning');
            return;
        }
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Enviando...');
        $.ajax({
            url: '?page=nfe_documents&action=manifest',
            method: 'POST',
            dataType: 'json',
            data: {
                doc_id: $('#naoRealizadaDocId').val(),
                type: 'nao_realizada',
                justificativa: justificativa
            },
            headers: { 'X-CSRF-TOKEN': csrfToken }
        }).done(function(resp){
            if (resp.success) {
                Swal.fire('Enviada!', resp.message || 'Manifestação registrada.', 'success').then(function(){ location.reload(); });
            } else {
                Swal.fire('Erro', resp.message, 'error');
            }
        }).fail(function(){
            Swal.fire('Erro', 'Falha na comunicação.', 'error');
        }).always(function(){
            btn.prop('disabled', false).html('<i class="fas fa-times me-1"></i> Confirmar');
        });
    });
});
</script>
