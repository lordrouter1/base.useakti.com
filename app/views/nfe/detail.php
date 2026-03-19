<?php
/**
 * View: Detalhe de NF-e
 * Timeline de eventos, logs SEFAZ, XMLs disponíveis, ações.
 * @var array      $doc   Documento NF-e
 * @var array      $logs  Logs SEFAZ
 * @var array|null $order Pedido vinculado
 */
$pageTitle = 'NF-e #' . $doc['numero'];
$statusLabels = [
    'rascunho'    => ['label' => 'Rascunho',    'color' => 'secondary', 'icon' => 'fas fa-pencil-alt'],
    'processando' => ['label' => 'Processando', 'color' => 'info',      'icon' => 'fas fa-spinner fa-spin'],
    'autorizada'  => ['label' => 'Autorizada',  'color' => 'success',   'icon' => 'fas fa-check-circle'],
    'rejeitada'   => ['label' => 'Rejeitada',   'color' => 'danger',    'icon' => 'fas fa-times-circle'],
    'cancelada'   => ['label' => 'Cancelada',   'color' => 'dark',      'icon' => 'fas fa-ban'],
    'denegada'    => ['label' => 'Denegada',     'color' => 'warning',   'icon' => 'fas fa-exclamation'],
    'corrigida'   => ['label' => 'Corrigida',    'color' => 'primary',   'icon' => 'fas fa-pen'],
];
$si = $statusLabels[$doc['status']] ?? ['label' => $doc['status'], 'color' => 'secondary', 'icon' => 'fas fa-circle'];
?>

<div class="container py-4">

    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center pt-2 pb-2 mb-3 border-bottom">
        <div>
            <h1 class="h2 mb-0">
                <i class="<?= $si['icon'] ?> me-2" style="color:var(--bs-<?= $si['color'] ?>)"></i>
                NF-e #<?= e($doc['numero']) ?>
                <span class="badge bg-<?= $si['color'] ?> ms-2" style="font-size:0.6em;"><?= $si['label'] ?></span>
            </h1>
            <small class="text-muted">
                Série <?= e($doc['serie']) ?> — 
                Criada em <?= date('d/m/Y H:i', strtotime($doc['created_at'])) ?>
                <?php if ($doc['emitted_at']): ?>
                — Emitida em <?= date('d/m/Y H:i', strtotime($doc['emitted_at'])) ?>
                <?php endif; ?>
            </small>
        </div>
        <div class="d-flex gap-2">
            <a href="?page=nfe_documents" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Voltar</a>
            <?php if ($doc['xml_autorizado']): ?>
            <a href="?page=nfe_documents&action=download&id=<?= $doc['id'] ?>&type=xml" 
               class="btn btn-outline-secondary btn-sm"><i class="fas fa-file-code me-1"></i> XML</a>
            <a href="?page=nfe_documents&action=download&id=<?= $doc['id'] ?>&type=danfe" 
               class="btn btn-outline-danger btn-sm" target="_blank"><i class="fas fa-file-pdf me-1"></i> DANFE</a>
            <?php endif; ?>
            <?php if ($doc['status'] === 'autorizada'): ?>
            <button type="button" class="btn btn-outline-info btn-sm" id="btnCheckStatusDetail"
                    data-id="<?= $doc['id'] ?>">
                <i class="fas fa-sync me-1"></i> Consultar SEFAZ
            </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-4">
        <!-- Coluna principal -->
        <div class="col-lg-8">

            <!-- ═══ Dados do Documento ═══ -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header py-2 bg-primary bg-opacity-10">
                    <h6 class="mb-0 text-primary"><i class="fas fa-file-invoice me-2"></i> Dados da NF-e</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <span class="small text-muted d-block">Chave de Acesso</span>
                            <span class="fw-bold" style="font-size:0.75rem; word-break:break-all;"><?= e($doc['chave'] ?? '—') ?></span>
                        </div>
                        <div class="col-md-4">
                            <span class="small text-muted d-block">Protocolo</span>
                            <span class="fw-bold"><?= e($doc['protocolo'] ?? '—') ?></span>
                        </div>
                        <div class="col-md-4">
                            <span class="small text-muted d-block">Natureza da Operação</span>
                            <span class="fw-bold"><?= e($doc['natureza_op'] ?? '—') ?></span>
                        </div>
                        <div class="col-md-3">
                            <span class="small text-muted d-block">Valor Total</span>
                            <span class="fw-bold text-success">R$ <?= number_format($doc['valor_total'], 2, ',', '.') ?></span>
                        </div>
                        <div class="col-md-3">
                            <span class="small text-muted d-block">Valor Produtos</span>
                            <span class="fw-bold">R$ <?= number_format($doc['valor_produtos'], 2, ',', '.') ?></span>
                        </div>
                        <div class="col-md-3">
                            <span class="small text-muted d-block">Desconto</span>
                            <span class="fw-bold">R$ <?= number_format($doc['valor_desconto'], 2, ',', '.') ?></span>
                        </div>
                        <div class="col-md-3">
                            <span class="small text-muted d-block">Frete</span>
                            <span class="fw-bold">R$ <?= number_format($doc['valor_frete'], 2, ',', '.') ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══ Destinatário ═══ -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header py-2 bg-primary bg-opacity-10">
                    <h6 class="mb-0 text-primary"><i class="fas fa-user me-2"></i> Destinatário</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <span class="small text-muted d-block">Nome / Razão Social</span>
                            <span class="fw-bold"><?= e($doc['dest_nome'] ?? '—') ?></span>
                        </div>
                        <div class="col-md-3">
                            <span class="small text-muted d-block">CPF/CNPJ</span>
                            <span class="fw-bold"><?= e($doc['dest_cnpj_cpf'] ?? '—') ?></span>
                        </div>
                        <div class="col-md-3">
                            <span class="small text-muted d-block">UF</span>
                            <span class="fw-bold"><?= e($doc['dest_uf'] ?? '—') ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══ Cancelamento / Correção ═══ -->
            <?php if ($doc['status'] === 'cancelada'): ?>
            <div class="card border-0 shadow-sm mb-4 border-start border-danger border-3">
                <div class="card-header py-2 bg-danger bg-opacity-10">
                    <h6 class="mb-0 text-danger"><i class="fas fa-ban me-2"></i> Cancelamento</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <span class="small text-muted d-block">Protocolo Cancel.</span>
                            <span class="fw-bold"><?= e($doc['cancel_protocolo'] ?? '—') ?></span>
                        </div>
                        <div class="col-md-4">
                            <span class="small text-muted d-block">Data</span>
                            <span class="fw-bold"><?= $doc['cancel_date'] ? date('d/m/Y H:i', strtotime($doc['cancel_date'])) : '—' ?></span>
                        </div>
                        <div class="col-md-12">
                            <span class="small text-muted d-block">Justificativa</span>
                            <span class="fw-bold"><?= e($doc['cancel_motivo'] ?? '—') ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($doc['correcao_texto']): ?>
            <div class="card border-0 shadow-sm mb-4 border-start border-info border-3">
                <div class="card-header py-2 bg-info bg-opacity-10">
                    <h6 class="mb-0 text-info"><i class="fas fa-pen me-2"></i> Carta de Correção (seq: <?= e($doc['correcao_seq']) ?>)</h6>
                </div>
                <div class="card-body">
                    <p class="mb-1"><?= e($doc['correcao_texto']) ?></p>
                    <small class="text-muted">
                        Enviada em <?= $doc['correcao_date'] ? date('d/m/Y H:i', strtotime($doc['correcao_date'])) : '—' ?>
                    </small>
                </div>
            </div>
            <?php endif; ?>

            <!-- ═══ SEFAZ Response ═══ -->
            <?php if ($doc['status_sefaz'] || $doc['motivo_sefaz']): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header py-2 bg-warning bg-opacity-10">
                    <h6 class="mb-0 text-warning"><i class="fas fa-server me-2"></i> Resposta SEFAZ</h6>
                </div>
                <div class="card-body">
                    <p class="mb-0">
                        <span class="badge bg-secondary me-2">cStat: <?= e($doc['status_sefaz'] ?? '—') ?></span>
                        <?= e($doc['motivo_sefaz'] ?? '') ?>
                    </p>
                </div>
            </div>
            <?php endif; ?>

        </div>

        <!-- Coluna lateral -->
        <div class="col-lg-4">

            <!-- ═══ Pedido Vinculado ═══ -->
            <?php if ($order): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header py-2 bg-success bg-opacity-10">
                    <h6 class="mb-0 text-success"><i class="fas fa-shopping-cart me-2"></i> Pedido Vinculado</h6>
                </div>
                <div class="card-body">
                    <a href="?page=pipeline&action=detail&id=<?= $order['id'] ?>" class="btn btn-outline-primary btn-sm w-100 mb-2">
                        <i class="fas fa-eye me-1"></i> Pedido #<?= str_pad($order['id'], 4, '0', STR_PAD_LEFT) ?>
                    </a>
                    <small class="text-muted d-block">Cliente: <?= e($order['customer_name'] ?? '—') ?></small>
                    <small class="text-muted d-block">Valor: R$ <?= number_format($order['total_amount'] ?? 0, 2, ',', '.') ?></small>
                </div>
            </div>
            <?php endif; ?>

            <!-- ═══ Downloads ═══ -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header py-2 bg-primary bg-opacity-10">
                    <h6 class="mb-0 text-primary"><i class="fas fa-download me-2"></i> Downloads</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <?php if ($doc['xml_autorizado']): ?>
                        <a href="?page=nfe_documents&action=download&id=<?= $doc['id'] ?>&type=xml" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-file-code me-1"></i> XML Autorizado
                        </a>
                        <a href="?page=nfe_documents&action=download&id=<?= $doc['id'] ?>&type=danfe" target="_blank" class="btn btn-sm btn-outline-danger">
                            <i class="fas fa-file-pdf me-1"></i> DANFE (PDF)
                        </a>
                        <?php endif; ?>
                        <?php if ($doc['xml_cancelamento']): ?>
                        <a href="?page=nfe_documents&action=download&id=<?= $doc['id'] ?>&type=xml_cancel" class="btn btn-sm btn-outline-dark">
                            <i class="fas fa-file-code me-1"></i> XML Cancelamento
                        </a>
                        <?php endif; ?>
                        <?php if ($doc['xml_correcao']): ?>
                        <a href="?page=nfe_documents&action=download&id=<?= $doc['id'] ?>&type=xml_correcao" class="btn btn-sm btn-outline-info">
                            <i class="fas fa-file-code me-1"></i> XML Carta de Correção
                        </a>
                        <?php endif; ?>
                        <?php if (!$doc['xml_autorizado'] && !$doc['xml_cancelamento']): ?>
                        <span class="text-muted small text-center">Nenhum XML disponível</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ═══ Timeline de Logs ═══ -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header py-2 bg-primary bg-opacity-10">
                    <h6 class="mb-0 text-primary"><i class="fas fa-history me-2"></i> Logs SEFAZ</h6>
                </div>
                <div class="card-body p-0" style="max-height:400px; overflow-y:auto;">
                    <?php if (empty($logs)): ?>
                    <p class="text-muted text-center py-3 mb-0">Nenhum log registrado.</p>
                    <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($logs as $log): 
                            $logColor = match($log['status']) {
                                'success' => 'success',
                                'error'   => 'danger',
                                'warning' => 'warning',
                                default   => 'secondary',
                            };
                        ?>
                        <li class="list-group-item px-3 py-2">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <span class="badge bg-<?= $logColor ?> me-1" style="font-size:0.6rem;"><?= e($log['action']) ?></span>
                                    <?php if ($log['code_sefaz']): ?>
                                    <span class="badge bg-light text-dark" style="font-size:0.6rem;">cStat: <?= e($log['code_sefaz']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <small class="text-muted"><?= date('d/m H:i', strtotime($log['created_at'])) ?></small>
                            </div>
                            <p class="small mb-0 mt-1" style="font-size:0.75rem;"><?= e($log['message'] ?? '') ?></p>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
$(function(){
    $('#btnCheckStatusDetail').on('click', function(){
        var btn = $(this);
        var nfeId = btn.data('id');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Consultando...');

        $.ajax({
            url: '?page=nfe_documents&action=checkStatus&id=' + nfeId,
            method: 'POST',
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        }).done(function(resp){
            var icon = resp.success ? 'success' : 'error';
            Swal.fire('Consulta SEFAZ', resp.message || 'Sem resposta', icon);
        }).fail(function(){
            Swal.fire('Erro', 'Falha na comunicação.', 'error');
        }).always(function(){
            btn.prop('disabled', false).html('<i class="fas fa-sync me-1"></i> Consultar SEFAZ');
        });
    });
});
</script>
