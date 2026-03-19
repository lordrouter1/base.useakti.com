<?php
/**
 * View: Painel de Notas Fiscais (NF-e)
 * Listagem paginada com filtros, cards de resumo e ações.
 * @var array  $documents     Lista de NF-e paginada
 * @var int    $totalItems    Total de registros
 * @var int    $totalPages    Total de páginas
 * @var int    $ctPage        Página atual
 * @var array  $statusCounts  Contagem por status
 * @var int    $countThisMonth NF-e emitidas no mês
 * @var float  $sumAuthorized  Valor autorizado no mês
 * @var array  $validation     Validação de credenciais
 * @var array  $filters        Filtros aplicados
 * @var string $baseUrl        URL base para paginação
 */
$pageTitle = 'Notas Fiscais — NF-e';
$statusLabels = [
    'rascunho'    => ['label' => 'Rascunho',    'color' => 'secondary', 'icon' => 'fas fa-pencil-alt'],
    'processando' => ['label' => 'Processando', 'color' => 'info',      'icon' => 'fas fa-spinner'],
    'autorizada'  => ['label' => 'Autorizada',  'color' => 'success',   'icon' => 'fas fa-check-circle'],
    'rejeitada'   => ['label' => 'Rejeitada',   'color' => 'danger',    'icon' => 'fas fa-times-circle'],
    'cancelada'   => ['label' => 'Cancelada',   'color' => 'dark',      'icon' => 'fas fa-ban'],
    'denegada'    => ['label' => 'Denegada',     'color' => 'warning',   'icon' => 'fas fa-exclamation'],
    'corrigida'   => ['label' => 'Corrigida',    'color' => 'primary',   'icon' => 'fas fa-pen'],
];
?>

<div class="container py-4">

    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center pt-2 pb-2 mb-3 border-bottom">
        <div>
            <h1 class="h2 mb-0"><i class="fas fa-file-invoice me-2 text-primary"></i> Notas Fiscais (NF-e)</h1>
            <small class="text-muted">Gerencie suas notas fiscais eletrônicas</small>
        </div>
        <div class="d-flex gap-2">
            <a href="?page=nfe_credentials" class="btn btn-outline-success btn-sm">
                <i class="fas fa-certificate me-1"></i> Credenciais SEFAZ
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

    <!-- Alerta credenciais incompletas -->
    <?php if (!$validation['valid']): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Credenciais incompletas.</strong> 
        <a href="?page=nfe_credentials">Configure as credenciais SEFAZ</a> antes de emitir NF-e.
    </div>
    <?php endif; ?>

    <!-- ═══ Cards de Resumo ═══ -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div class="text-success mb-1"><i class="fas fa-check-circle fa-2x"></i></div>
                    <h3 class="mb-0"><?= $statusCounts['autorizada'] ?? 0 ?></h3>
                    <small class="text-muted">Autorizadas</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div class="text-primary mb-1"><i class="fas fa-calendar-alt fa-2x"></i></div>
                    <h3 class="mb-0"><?= $countThisMonth ?></h3>
                    <small class="text-muted">Emitidas no Mês</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div class="text-warning mb-1"><i class="fas fa-coins fa-2x"></i></div>
                    <h3 class="mb-0">R$ <?= number_format($sumAuthorized, 2, ',', '.') ?></h3>
                    <small class="text-muted">Valor Autorizado/Mês</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div class="text-danger mb-1"><i class="fas fa-times-circle fa-2x"></i></div>
                    <h3 class="mb-0"><?= ($statusCounts['rejeitada'] ?? 0) + ($statusCounts['cancelada'] ?? 0) ?></h3>
                    <small class="text-muted">Rejeitadas/Canceladas</small>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ Filtros ═══ -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <input type="hidden" name="page" value="nfe_documents">
                <div class="col-auto">
                    <label class="form-label small mb-0">Status</label>
                    <select class="form-select form-select-sm" name="status">
                        <option value="">Todos</option>
                        <?php foreach ($statusLabels as $key => $info): ?>
                        <option value="<?= $key ?>" <?= ($filters['status'] ?? '') === $key ? 'selected' : '' ?>>
                            <?= $info['label'] ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label small mb-0">Mês</label>
                    <select class="form-select form-select-sm" name="month">
                        <option value="">Todos</option>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= ($filters['month'] ?? '') == $m ? 'selected' : '' ?>>
                            <?= str_pad($m, 2, '0', STR_PAD_LEFT) ?>
                        </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label small mb-0">Ano</label>
                    <select class="form-select form-select-sm" name="year">
                        <option value="">Todos</option>
                        <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
                        <option value="<?= $y ?>" <?= ($filters['year'] ?? '') == $y ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label small mb-0">Buscar</label>
                    <input type="text" class="form-control form-control-sm" name="search" 
                           placeholder="Nº, chave ou destinatário..."
                           value="<?= eAttr($filters['search'] ?? '') ?>">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search me-1"></i> Filtrar</button>
                    <a href="?page=nfe_documents" class="btn btn-sm btn-outline-secondary"><i class="fas fa-times me-1"></i> Limpar</a>
                </div>
            </form>
        </div>
    </div>

    <!-- ═══ Tabela de NF-e ═══ -->
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width:70px;" class="text-center">Nº</th>
                        <th style="width:50px;">Série</th>
                        <th>Destinatário</th>
                        <th style="width:100px;">Valor</th>
                        <th style="width:100px;">Status</th>
                        <th style="width:80px;">Pedido</th>
                        <th style="width:130px;">Data</th>
                        <th style="width:150px;" class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($documents)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="fas fa-file-invoice fa-3x mb-2 opacity-25"></i><br>
                            Nenhuma nota fiscal encontrada.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($documents as $doc): 
                        $si = $statusLabels[$doc['status']] ?? ['label' => $doc['status'], 'color' => 'secondary', 'icon' => 'fas fa-circle'];
                    ?>
                    <tr>
                        <td class="text-center fw-bold"><?= e($doc['numero']) ?></td>
                        <td><?= e($doc['serie']) ?></td>
                        <td>
                            <span class="d-block"><?= e($doc['dest_nome'] ?? '—') ?></span>
                            <?php if (!empty($doc['dest_cnpj_cpf'])): ?>
                            <small class="text-muted"><?= e($doc['dest_cnpj_cpf']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>R$ <?= number_format($doc['valor_total'], 2, ',', '.') ?></td>
                        <td>
                            <span class="badge bg-<?= $si['color'] ?>">
                                <i class="<?= $si['icon'] ?> me-1"></i> <?= $si['label'] ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($doc['order_id']): ?>
                            <a href="?page=pipeline&action=detail&id=<?= $doc['order_id'] ?>" class="text-decoration-none">
                                #<?= str_pad($doc['order_num'] ?? $doc['order_id'], 4, '0', STR_PAD_LEFT) ?>
                            </a>
                            <?php else: ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <small><?= date('d/m/Y H:i', strtotime($doc['created_at'])) ?></small>
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="?page=nfe_documents&action=detail&id=<?= $doc['id'] ?>" 
                                   class="btn btn-outline-primary" title="Detalhe">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if ($doc['xml_autorizado']): ?>
                                <a href="?page=nfe_documents&action=download&id=<?= $doc['id'] ?>&type=danfe" 
                                   class="btn btn-danger" title="Imprimir DANFE" target="_blank">
                                    <i class="fas fa-print"></i>
                                </a>
                                <a href="?page=nfe_documents&action=download&id=<?= $doc['id'] ?>&type=xml" 
                                   class="btn btn-outline-secondary" title="Baixar XML">
                                    <i class="fas fa-file-code"></i>
                                </a>
                                <?php endif; ?>
                                <?php if ($doc['status'] === 'autorizada'): ?>
                                <button type="button" class="btn btn-outline-dark btn-cancel-nfe" 
                                        data-id="<?= $doc['id'] ?>" data-numero="<?= e($doc['numero']) ?>" title="Cancelar">
                                    <i class="fas fa-ban"></i>
                                </button>
                                <button type="button" class="btn btn-outline-info btn-correcao-nfe"
                                        data-id="<?= $doc['id'] ?>" data-numero="<?= e($doc['numero']) ?>" title="Carta de Correção">
                                    <i class="fas fa-pen"></i>
                                </button>
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

    <!-- ═══ Paginação ═══ -->
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
</div>

<!-- ═══ Modais: Cancelamento / Carta de Correção ═══ -->

<!-- Modal Cancelamento -->
<div class="modal fade" id="modalCancelNfe" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger ">
                <h5 class="modal-title text-danger"><i class="fas fa-ban me-2"></i> Cancelar NF-e</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="cancelNfeId">
                <p>Cancelar NF-e nº <strong id="cancelNfeNum"></strong>?</p>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Justificativa (mínimo 15 caracteres) <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="cancelMotivo" rows="3" minlength="15" 
                              placeholder="Descreva o motivo do cancelamento..."></textarea>
                    <div class="form-text"><span id="cancelChars">0</span>/15 caracteres mínimos</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-danger" id="btnConfirmCancel">
                    <i class="fas fa-ban me-1"></i> Confirmar Cancelamento
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Carta de Correção -->
<div class="modal fade" id="modalCorrecaoNfe" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info ">
                <h5 class="modal-title text-info"><i class="fas fa-pen me-2"></i> Carta de Correção</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="correcaoNfeId">
                <p>Enviar carta de correção para NF-e nº <strong id="correcaoNfeNum"></strong>.</p>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Texto da Correção (mínimo 15 caracteres) <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="correcaoTexto" rows="4" minlength="15"
                              placeholder="Descreva a correção a ser feita na NF-e..."></textarea>
                    <div class="form-text"><span id="correcaoChars">0</span>/15 caracteres mínimos</div>
                </div>
                <div class="alert alert-info small py-2">
                    <i class="fas fa-info-circle me-1"></i>
                    A carta de correção não pode alterar valores, impostos, dados do emitente, destinatário, ou data de emissão.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-info" id="btnConfirmCorrecao">
                    <i class="fas fa-paper-plane me-1"></i> Enviar Correção
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$(function(){
    var csrfToken = $('meta[name="csrf-token"]').attr('content');

    // ── Cancelamento ──
    $('.btn-cancel-nfe').on('click', function(){
        $('#cancelNfeId').val($(this).data('id'));
        $('#cancelNfeNum').text($(this).data('numero'));
        $('#cancelMotivo').val('');
        $('#cancelChars').text('0');
        var modal = new bootstrap.Modal('#modalCancelNfe');
        modal.show();
    });

    $('#cancelMotivo').on('input', function(){
        $('#cancelChars').text($(this).val().length);
    });

    $('#btnConfirmCancel').on('click', function(){
        var btn = $(this);
        var motivo = $('#cancelMotivo').val().trim();
        if (motivo.length < 15) {
            Swal.fire('Atenção', 'A justificativa deve ter no mínimo 15 caracteres.', 'warning');
            return;
        }

        Swal.fire({
            icon: 'warning',
            title: 'Confirmar Cancelamento?',
            html: 'Esta ação é <strong>irreversível</strong>. A NF-e será cancelada na SEFAZ.',
            showCancelButton: true,
            confirmButtonText: 'Sim, cancelar NF-e',
            cancelButtonText: 'Voltar',
            confirmButtonColor: '#dc3545',
        }).then(function(result){
            if (!result.isConfirmed) return;

            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Cancelando...');
            $.ajax({
                url: '?page=nfe_documents&action=cancel',
                method: 'POST',
                dataType: 'json',
                data: { nfe_id: $('#cancelNfeId').val(), motivo: motivo },
                headers: { 'X-CSRF-TOKEN': csrfToken }
            }).done(function(resp){
                if (resp.success) {
                    Swal.fire('Cancelada!', resp.message, 'success').then(function(){ location.reload(); });
                } else {
                    Swal.fire('Erro', resp.message, 'error');
                }
            }).fail(function(){
                Swal.fire('Erro', 'Falha na comunicação com o servidor.', 'error');
            }).always(function(){
                btn.prop('disabled', false).html('<i class="fas fa-ban me-1"></i> Confirmar Cancelamento');
            });
        });
    });

    // ── Carta de Correção ──
    $('.btn-correcao-nfe').on('click', function(){
        $('#correcaoNfeId').val($(this).data('id'));
        $('#correcaoNfeNum').text($(this).data('numero'));
        $('#correcaoTexto').val('');
        $('#correcaoChars').text('0');
        var modal = new bootstrap.Modal('#modalCorrecaoNfe');
        modal.show();
    });

    $('#correcaoTexto').on('input', function(){
        $('#correcaoChars').text($(this).val().length);
    });

    $('#btnConfirmCorrecao').on('click', function(){
        var btn = $(this);
        var texto = $('#correcaoTexto').val().trim();
        if (texto.length < 15) {
            Swal.fire('Atenção', 'O texto da correção deve ter no mínimo 15 caracteres.', 'warning');
            return;
        }

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Enviando...');
        $.ajax({
            url: '?page=nfe_documents&action=correction',
            method: 'POST',
            dataType: 'json',
            data: { nfe_id: $('#correcaoNfeId').val(), texto: texto },
            headers: { 'X-CSRF-TOKEN': csrfToken }
        }).done(function(resp){
            if (resp.success) {
                Swal.fire('Enviada!', resp.message, 'success').then(function(){ location.reload(); });
            } else {
                Swal.fire('Erro', resp.message, 'error');
            }
        }).fail(function(){
            Swal.fire('Erro', 'Falha na comunicação com o servidor.', 'error');
        }).always(function(){
            btn.prop('disabled', false).html('<i class="fas fa-paper-plane me-1"></i> Enviar Correção');
        });
    });
});
</script>
