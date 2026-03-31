<?php
/**
 * View: Detalhe de NF-e — Fase 6 UX Modernizada
 * Layout com tabs, timeline de eventos, downloads, ações rápidas.
 * @var array      $doc            Documento NF-e
 * @var array      $logs           Logs SEFAZ
 * @var array|null $order          Pedido vinculado
 * @var array|null $installments   Parcelas financeiras
 * @var array|null $installmentSummary Resumo financeiro
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
$valorTributosAprox = $valorTributosAprox ?? 0;
$ibptaxFonte = $ibptaxFonte ?? '';
$installments = $installments ?? [];
$installmentSummary = $installmentSummary ?? ['total' => 0, 'pagas' => 0, 'pendentes' => 0, 'faturadas' => 0, 'valor_total' => 0, 'valor_pago' => 0];
?>

<!-- Styles moved to assets/css/modules/nfe.css -->

<div class="container-fluid py-4 px-lg-4">

    <!-- ═══ Cabeçalho com Status ═══ -->
    <div class="nfe-detail-header p-4 mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div class="flex-grow-1">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <h1 class="h3 mb-0 fw-bold">
                        <i class="<?= $si['icon'] ?> me-2" style="color:var(--bs-<?= $si['color'] ?>)"></i>
                        NF-e #<?= e($doc['numero']) ?>
                    </h1>
                    <span class="badge bg-<?= $si['color'] ?> status-badge"><?= $si['label'] ?></span>
                    <?php if ($doc['modelo'] ?? ''): ?>
                    <span class="badge bg-light text-dark border status-badge">Modelo <?= e($doc['modelo']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="d-flex flex-wrap gap-3 text-muted small">
                    <span><i class="fas fa-hashtag me-1"></i> Série <?= e($doc['serie']) ?></span>
                    <span><i class="fas fa-calendar me-1"></i> Criada em <?= date('d/m/Y H:i', strtotime($doc['created_at'])) ?></span>
                    <?php if ($doc['emitted_at']): ?>
                    <span><i class="fas fa-paper-plane me-1"></i> Emitida em <?= date('d/m/Y H:i', strtotime($doc['emitted_at'])) ?></span>
                    <?php endif; ?>
                    <?php if ($doc['natureza_op'] ?? ''): ?>
                    <span><i class="fas fa-tag me-1"></i> <?= e($doc['natureza_op']) ?></span>
                    <?php endif; ?>
                </div>
                <!-- Chave de Acesso -->
                <?php if ($doc['chave'] ?? ''): ?>
                <div class="mt-3">
                    <div class="info-label"><i class="fas fa-key me-1"></i> Chave de Acesso</div>
                    <div class="nfe-key-box d-flex align-items-center gap-2">
                        <span id="nfeChave"><?= e($doc['chave']) ?></span>
                        <button type="button" class="btn btn-sm btn-outline-secondary border-0 p-1" onclick="copyKey()" title="Copiar">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <div class="d-flex flex-wrap gap-2 align-self-start">
                <a href="?page=nfe_documents" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Voltar
                </a>
                <?php if ($doc['xml_autorizado']): ?>
                <a href="?page=nfe_documents&action=download&id=<?= $doc['id'] ?>&type=danfe" 
                   class="btn btn-danger btn-sm" target="_blank">
                    <i class="fas fa-print me-1"></i> DANFE
                </a>
                <?php endif; ?>
                <?php if ($doc['status'] === 'autorizada'): ?>
                <button type="button" class="btn btn-outline-info btn-sm" id="btnCheckStatusDetail" data-id="<?= $doc['id'] ?>">
                    <i class="fas fa-sync me-1"></i> Consultar SEFAZ
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ═══ Alertas Contextuais ═══ -->
    <?php if ($doc['status'] === 'rejeitada'): ?>
    <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center gap-2 mb-4">
        <i class="fas fa-exclamation-triangle fs-5"></i>
        <div>
            <strong>NF-e Rejeitada pela SEFAZ</strong>
            <?php if ($doc['motivo_sefaz']): ?>
            <span class="d-block small">cStat <?= e($doc['status_sefaz'] ?? '') ?>: <?= e($doc['motivo_sefaz']) ?></span>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($doc['status'] === 'cancelada'): ?>
    <div class="alert alert-dark border-0 shadow-sm d-flex align-items-center gap-2 mb-4">
        <i class="fas fa-ban fs-5"></i>
        <div>
            <strong>NF-e Cancelada</strong>
            <?php if ($doc['cancel_motivo'] ?? ''): ?>
            <span class="d-block small"><?= e($doc['cancel_motivo']) ?></span>
            <?php endif; ?>
            <?php if ($doc['cancel_date'] ?? ''): ?>
            <span class="d-block small text-muted">Em <?= date('d/m/Y H:i', strtotime($doc['cancel_date'])) ?></span>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- ═══ Coluna Principal com Tabs ═══ -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <!-- Nav Tabs -->
                <div class="card-header bg-white border-bottom-0 p-0">
                    <ul class="nav nfe-tab-nav" id="nfeDetailTabs" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active" id="tab-dados" data-bs-toggle="tab" data-bs-target="#pane-dados" type="button" role="tab">
                                <i class="fas fa-file-invoice me-1"></i> Dados
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="tab-dest" data-bs-toggle="tab" data-bs-target="#pane-dest" type="button" role="tab">
                                <i class="fas fa-user me-1"></i> Destinatário
                            </button>
                        </li>
                        <?php if (!empty($installments)): ?>
                        <li class="nav-item">
                            <button class="nav-link" id="tab-fin" data-bs-toggle="tab" data-bs-target="#pane-fin" type="button" role="tab">
                                <i class="fas fa-file-invoice-dollar me-1"></i> Financeiro
                                <?php if ($installmentSummary['pendentes'] > 0): ?>
                                <span class="badge bg-warning text-dark ms-1" style="font-size:0.65rem;"><?= $installmentSummary['pendentes'] ?></span>
                                <?php endif; ?>
                            </button>
                        </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <button class="nav-link" id="tab-eventos" data-bs-toggle="tab" data-bs-target="#pane-eventos" type="button" role="tab">
                                <i class="fas fa-history me-1"></i> Eventos
                                <?php if (!empty($logs)): ?>
                                <span class="badge bg-secondary ms-1" style="font-size:0.65rem;"><?= count($logs) ?></span>
                                <?php endif; ?>
                            </button>
                        </li>
                        <?php if ($doc['status'] === 'cancelada' || $doc['correcao_texto']): ?>
                        <li class="nav-item">
                            <button class="nav-link" id="tab-ocorr" data-bs-toggle="tab" data-bs-target="#pane-ocorr" type="button" role="tab">
                                <i class="fas fa-exclamation-circle me-1"></i> Ocorrências
                            </button>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Tab Content -->
                <div class="card-body tab-content" id="nfeDetailTabContent">

                    <!-- ═══ TAB: Dados da NF-e ═══ -->
                    <div class="tab-pane fade show active" id="pane-dados" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="info-label">Protocolo</div>
                                <div class="info-value"><?= e($doc['protocolo'] ?? '—') ?></div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-label">Natureza da Operação</div>
                                <div class="info-value"><?= e($doc['natureza_op'] ?? '—') ?></div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-label">Modelo / Série</div>
                                <div class="info-value"><?= e($doc['modelo'] ?? '55') ?> / <?= e($doc['serie']) ?></div>
                            </div>
                        </div>

                        <hr class="my-3">
                        <h6 class="fw-bold text-muted small text-uppercase mb-3"><i class="fas fa-dollar-sign me-1"></i> Valores</h6>
                        <div class="row g-3">
                            <div class="col-6 col-md-3">
                                <div class="info-label">Valor Total</div>
                                <div class="info-value text-success fs-5">R$ <?= number_format($doc['valor_total'], 2, ',', '.') ?></div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="info-label">Valor Produtos</div>
                                <div class="info-value">R$ <?= number_format($doc['valor_produtos'], 2, ',', '.') ?></div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="info-label">Desconto</div>
                                <div class="info-value">R$ <?= number_format($doc['valor_desconto'], 2, ',', '.') ?></div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="info-label">Frete</div>
                                <div class="info-value">R$ <?= number_format($doc['valor_frete'], 2, ',', '.') ?></div>
                            </div>
                            <?php if ($valorTributosAprox > 0): ?>
                            <div class="col-md-6">
                                <div class="info-label"><i class="fas fa-calculator me-1"></i> Tributos Aproximados (Lei 12.741)</div>
                                <div class="info-value text-info">R$ <?= number_format($valorTributosAprox, 2, ',', '.') ?></div>
                                <?php if (!empty($ibptaxFonte)): ?>
                                <span class="text-muted" style="font-size:0.7rem;">Fonte: <?= e($ibptaxFonte) ?></span>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($doc['status_sefaz'] || $doc['motivo_sefaz']): ?>
                        <hr class="my-3">
                        <h6 class="fw-bold text-muted small text-uppercase mb-3"><i class="fas fa-server me-1"></i> Resposta SEFAZ</h6>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-secondary">cStat: <?= e($doc['status_sefaz'] ?? '—') ?></span>
                            <span class="text-muted"><?= e($doc['motivo_sefaz'] ?? '') ?></span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- ═══ TAB: Destinatário ═══ -->
                    <div class="tab-pane fade" id="pane-dest" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="info-label">Nome / Razão Social</div>
                                <div class="info-value"><?= e($doc['dest_nome'] ?? '—') ?></div>
                            </div>
                            <div class="col-md-3">
                                <div class="info-label">CPF / CNPJ</div>
                                <div class="info-value"><?= e($doc['dest_cnpj_cpf'] ?? '—') ?></div>
                            </div>
                            <div class="col-md-3">
                                <div class="info-label">Inscrição Estadual</div>
                                <div class="info-value"><?= e($doc['dest_ie'] ?? '—') ?></div>
                            </div>
                            <div class="col-md-3">
                                <div class="info-label">UF</div>
                                <div class="info-value"><?= e($doc['dest_uf'] ?? '—') ?></div>
                            </div>
                            <div class="col-md-5">
                                <div class="info-label">Endereço</div>
                                <div class="info-value"><?= e($doc['dest_endereco'] ?? '—') ?></div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-label">Município</div>
                                <div class="info-value"><?= e($doc['dest_municipio'] ?? '—') ?></div>
                            </div>
                        </div>

                        <?php if ($order): ?>
                        <hr class="my-3">
                        <h6 class="fw-bold text-muted small text-uppercase mb-3"><i class="fas fa-shopping-cart me-1"></i> Pedido Vinculado</h6>
                        <div class="d-flex align-items-center gap-3">
                            <a href="?page=pipeline&action=detail&id=<?= $order['id'] ?>" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-eye me-1"></i> Pedido #<?= str_pad($order['id'], 4, '0', STR_PAD_LEFT) ?>
                            </a>
                            <span class="text-muted small">
                                <?= e($order['customer_name'] ?? '—') ?> 
                                — R$ <?= number_format($order['total_amount'] ?? 0, 2, ',', '.') ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- ═══ TAB: Financeiro ═══ -->
                    <?php if (!empty($installments)): ?>
                    <div class="tab-pane fade" id="pane-fin" role="tabpanel">
                        <!-- Resumo -->
                        <div class="row g-3 mb-4">
                            <div class="col-6 col-md-3">
                                <div class="border rounded-3 p-3 text-center h-100">
                                    <div class="info-label">Parcelas</div>
                                    <div class="fw-bold fs-5"><?= $installmentSummary['pagas'] ?>/<?= $installmentSummary['total'] ?></div>
                                    <small class="text-muted">pagas</small>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="border rounded-3 p-3 text-center h-100">
                                    <div class="info-label">Valor Total</div>
                                    <div class="fw-bold fs-5">R$ <?= number_format($installmentSummary['valor_total'], 2, ',', '.') ?></div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="border rounded-3 p-3 text-center h-100">
                                    <div class="info-label">Valor Pago</div>
                                    <div class="fw-bold fs-5 text-success">R$ <?= number_format($installmentSummary['valor_pago'], 2, ',', '.') ?></div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="border rounded-3 p-3 text-center h-100">
                                    <div class="info-label">Pendentes</div>
                                    <div class="fw-bold fs-5 <?= $installmentSummary['pendentes'] > 0 ? 'text-warning' : 'text-success' ?>"><?= $installmentSummary['pendentes'] ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Progress bar -->
                        <?php 
                        $pctPaid = $installmentSummary['valor_total'] > 0 
                            ? min(100, round(($installmentSummary['valor_pago'] / $installmentSummary['valor_total']) * 100)) 
                            : 0;
                        ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="text-muted">Progresso de pagamento</span>
                                <span class="fw-bold"><?= $pctPaid ?>%</span>
                            </div>
                            <div class="progress" style="height: 10px; border-radius: 1rem;">
                                <div class="progress-bar <?= $pctPaid >= 100 ? 'bg-success' : ($pctPaid > 0 ? 'bg-info' : 'bg-secondary') ?>" 
                                     style="width: <?= $pctPaid ?>%; border-radius: 1rem;" role="progressbar"></div>
                            </div>
                        </div>

                        <!-- Lista de parcelas -->
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="font-size:0.75rem;">#</th>
                                        <th style="font-size:0.75rem;">Valor</th>
                                        <th style="font-size:0.75rem;">Vencimento</th>
                                        <th style="font-size:0.75rem;">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $statusBadges = [
                                        'pendente'  => 'bg-warning text-dark',
                                        'pago'      => 'bg-success',
                                        'atrasado'  => 'bg-danger',
                                        'cancelado' => 'bg-secondary',
                                    ];
                                    foreach ($installments as $inst):
                                        $badge = $statusBadges[$inst['status']] ?? 'bg-secondary';
                                        $isEntrada = ($inst['installment_number'] ?? 1) == 0;
                                    ?>
                                    <tr>
                                        <td class="small"><?= $isEntrada ? '<span class="badge bg-info">Ent.</span>' : $inst['installment_number'] . 'ª' ?></td>
                                        <td class="small fw-bold">R$ <?= number_format($inst['amount'], 2, ',', '.') ?></td>
                                        <td class="small"><?= date('d/m/Y', strtotime($inst['due_date'])) ?></td>
                                        <td><span class="badge <?= $badge ?>" style="font-size:0.68rem;"><?= ucfirst($inst['status']) ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if (!empty($doc['order_id'])): ?>
                        <div class="mt-3">
                            <a href="?page=installments&action=installments&order_id=<?= $doc['order_id'] ?>" 
                               class="btn btn-sm btn-outline-info">
                                <i class="fas fa-external-link-alt me-1"></i> Ver Parcelas Completas
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- ═══ TAB: Eventos / Timeline ═══ -->
                    <div class="tab-pane fade" id="pane-eventos" role="tabpanel">
                        <?php if (empty($logs)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-inbox text-muted fs-1 mb-3 d-block"></i>
                            <p class="text-muted mb-0">Nenhum evento registrado para esta NF-e.</p>
                        </div>
                        <?php else: ?>
                        <div class="py-2">
                            <?php foreach ($logs as $i => $log): 
                                $tlColor = match($log['status'] ?? '') {
                                    'success' => 'success',
                                    'error'   => 'danger',
                                    'warning' => 'warning',
                                    'info'    => 'info',
                                    default   => 'secondary',
                                };
                            ?>
                            <div class="timeline-item tl-<?= $tlColor ?>">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <span class="badge bg-<?= $tlColor ?> me-1" style="font-size:0.7rem;">
                                            <?= e($log['action']) ?>
                                        </span>
                                        <?php if ($log['code_sefaz'] ?? ''): ?>
                                        <span class="badge bg-light text-dark border" style="font-size:0.65rem;">
                                            cStat: <?= e($log['code_sefaz']) ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        <?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?>
                                    </small>
                                </div>
                                <p class="small text-muted mb-0 mt-1"><?= e($log['message'] ?? '') ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- ═══ TAB: Ocorrências (Cancelamento / Correção) ═══ -->
                    <?php if ($doc['status'] === 'cancelada' || $doc['correcao_texto']): ?>
                    <div class="tab-pane fade" id="pane-ocorr" role="tabpanel">
                        <?php if ($doc['status'] === 'cancelada'): ?>
                        <div class="border-start border-danger border-3 ps-3 mb-4">
                            <h6 class="fw-bold text-danger mb-2"><i class="fas fa-ban me-2"></i> Cancelamento</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="info-label">Protocolo Cancelamento</div>
                                    <div class="info-value"><?= e($doc['cancel_protocolo'] ?? '—') ?></div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-label">Data</div>
                                    <div class="info-value"><?= $doc['cancel_date'] ? date('d/m/Y H:i', strtotime($doc['cancel_date'])) : '—' ?></div>
                                </div>
                                <div class="col-md-12">
                                    <div class="info-label">Justificativa</div>
                                    <div class="info-value"><?= e($doc['cancel_motivo'] ?? '—') ?></div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($doc['correcao_texto']): ?>
                        <div class="border-start border-info border-3 ps-3">
                            <h6 class="fw-bold text-info mb-2"><i class="fas fa-pen me-2"></i> Carta de Correção (Seq: <?= e($doc['correcao_seq']) ?>)</h6>
                            <p class="mb-1"><?= e($doc['correcao_texto']) ?></p>
                            <small class="text-muted">
                                Enviada em <?= $doc['correcao_date'] ? date('d/m/Y H:i', strtotime($doc['correcao_date'])) : '—' ?>
                            </small>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                </div><!-- /tab-content -->
            </div><!-- /card -->
        </div>

        <!-- ═══ Coluna Lateral ═══ -->
        <div class="col-lg-4">

            <!-- Downloads -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-download me-2 text-primary"></i> Downloads</h6>
                </div>
                <div class="card-body">
                    <?php if ($doc['xml_autorizado']): ?>
                    <a href="?page=nfe_documents&action=download&id=<?= $doc['id'] ?>&type=danfe" target="_blank" 
                       class="download-card d-flex align-items-center gap-3 p-3 mb-2 border rounded-3 text-decoration-none text-dark">
                        <div class="bg-danger bg-opacity-10 rounded-3 p-2">
                            <i class="fas fa-file-pdf text-danger fs-5"></i>
                        </div>
                        <div>
                            <div class="fw-bold small">Imprimir DANFE</div>
                            <div class="text-muted" style="font-size:0.72rem;">Abrir PDF em nova aba</div>
                        </div>
                    </a>
                    <a href="?page=nfe_documents&action=download&id=<?= $doc['id'] ?>&type=xml" 
                       class="download-card d-flex align-items-center gap-3 p-3 mb-2 border rounded-3 text-decoration-none text-dark">
                        <div class="bg-primary bg-opacity-10 rounded-3 p-2">
                            <i class="fas fa-file-code text-primary fs-5"></i>
                        </div>
                        <div>
                            <div class="fw-bold small">XML Autorizado</div>
                            <div class="text-muted" style="font-size:0.72rem;">Download do XML assinado</div>
                        </div>
                    </a>
                    <?php endif; ?>

                    <?php if ($doc['xml_cancelamento'] ?? ''): ?>
                    <a href="?page=nfe_documents&action=download&id=<?= $doc['id'] ?>&type=xml_cancel" 
                       class="download-card d-flex align-items-center gap-3 p-3 mb-2 border rounded-3 text-decoration-none text-dark">
                        <div class="bg-dark bg-opacity-10 rounded-3 p-2">
                            <i class="fas fa-file-code text-dark fs-5"></i>
                        </div>
                        <div>
                            <div class="fw-bold small">XML Cancelamento</div>
                            <div class="text-muted" style="font-size:0.72rem;">XML do evento de cancelamento</div>
                        </div>
                    </a>
                    <?php endif; ?>

                    <?php if ($doc['xml_correcao'] ?? ''): ?>
                    <a href="?page=nfe_documents&action=download&id=<?= $doc['id'] ?>&type=xml_correcao" 
                       class="download-card d-flex align-items-center gap-3 p-3 mb-2 border rounded-3 text-decoration-none text-dark">
                        <div class="bg-info bg-opacity-10 rounded-3 p-2">
                            <i class="fas fa-file-code text-info fs-5"></i>
                        </div>
                        <div>
                            <div class="fw-bold small">XML Carta de Correção</div>
                            <div class="text-muted" style="font-size:0.72rem;">XML do evento CC-e</div>
                        </div>
                    </a>
                    <?php endif; ?>

                    <?php if (!$doc['xml_autorizado'] && !($doc['xml_cancelamento'] ?? '') && !($doc['xml_correcao'] ?? '')): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-inbox text-muted fs-3 mb-2 d-block"></i>
                        <span class="text-muted small">Nenhum XML disponível</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Ações -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-bolt me-2 text-warning"></i> Ações</h6>
                </div>
                <div class="card-body action-btn-group d-grid gap-2">
                    <?php if ($doc['status'] === 'rascunho'): ?>
                    <a href="?page=nfe_documents&action=emit&id=<?= $doc['id'] ?>" 
                       class="btn btn-success"
                       onclick="return confirm('Confirma a emissão desta NF-e?')">
                        <i class="fas fa-paper-plane me-1"></i> Emitir NF-e
                    </a>
                    <?php endif; ?>

                    <?php if ($doc['status'] === 'autorizada'): ?>
                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalCancelar">
                        <i class="fas fa-ban me-1"></i> Cancelar NF-e
                    </button>
                    <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#modalCorrecao">
                        <i class="fas fa-pen me-1"></i> Carta de Correção
                    </button>
                    <button type="button" class="btn btn-outline-secondary" id="btnCheckStatusSidebar" data-id="<?= $doc['id'] ?>">
                        <i class="fas fa-sync me-1"></i> Consultar SEFAZ
                    </button>
                    <?php endif; ?>

                    <?php if ($doc['status'] === 'rejeitada'): ?>
                    <a href="?page=nfe_documents&action=emit&id=<?= $doc['id'] ?>" 
                       class="btn btn-warning"
                       onclick="return confirm('Reenviar esta NF-e à SEFAZ?')">
                        <i class="fas fa-redo me-1"></i> Reenviar NF-e
                    </a>
                    <?php endif; ?>

                    <a href="?page=nfe_documents" class="btn btn-outline-secondary">
                        <i class="fas fa-list me-1"></i> Voltar ao Painel
                    </a>
                </div>
            </div>

            <!-- Pedido Vinculado (mini card) -->
            <?php if ($order): ?>
            <div class="card border-0 shadow-sm mb-4 border-start border-primary border-3">
                <div class="card-body py-3">
                    <h6 class="fw-bold small text-uppercase text-muted mb-2">
                        <i class="fas fa-shopping-cart me-1"></i> Pedido Vinculado
                    </h6>
                    <a href="?page=pipeline&action=detail&id=<?= $order['id'] ?>" class="text-decoration-none">
                        <div class="fw-bold text-primary">Pedido #<?= str_pad($order['id'], 4, '0', STR_PAD_LEFT) ?></div>
                    </a>
                    <small class="text-muted"><?= e($order['customer_name'] ?? '—') ?></small>
                    <div class="small fw-bold mt-1">R$ <?= number_format($order['total_amount'] ?? 0, 2, ',', '.') ?></div>
                </div>
            </div>
            <?php endif; ?>

        </div><!-- /col-lg-4 -->
    </div><!-- /row -->
</div>

<!-- ═══ Modal Cancelar ═══ -->
<?php if ($doc['status'] === 'autorizada'): ?>
<div class="modal fade" id="modalCancelar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-ban me-2"></i> Cancelar NF-e #<?= e($doc['numero']) ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="?page=nfe_documents&action=cancel&id=<?= $doc['id'] ?>">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="alert alert-warning py-2">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        O cancelamento é irreversível. A NF-e deve ter sido autorizada há no máximo 24 horas.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Justificativa (mínimo 15 caracteres) <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="motivo" rows="3" minlength="15" required
                                  placeholder="Informe o motivo do cancelamento..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-ban me-1"></i> Confirmar Cancelamento
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ═══ Modal Carta de Correção ═══ -->
<div class="modal fade" id="modalCorrecao" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-pen me-2"></i> Carta de Correção — NF-e #<?= e($doc['numero']) ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="?page=nfe_documents&action=correcao&id=<?= $doc['id'] ?>">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="alert alert-info py-2">
                        <i class="fas fa-info-circle me-1"></i>
                        A CC-e corrige informações da NF-e sem alterar valores ou impostos. Máximo 20 correções por NF-e.
                        <?php if ($doc['correcao_seq'] ?? 0 > 0): ?>
                        <br><strong>Sequência atual: <?= e($doc['correcao_seq']) ?></strong>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Texto da Correção (mínimo 15 caracteres) <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="correcao" rows="4" minlength="15" required
                                  placeholder="Descreva a correção a ser feita..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="submit" class="btn btn-info text-white">
                        <i class="fas fa-paper-plane me-1"></i> Enviar CC-e
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function(){
    // Consultar SEFAZ
    function checkSefaz(btn) {
        var nfeId = btn.data('id');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Consultando...');

        $.ajax({
            url: '?page=nfe_documents&action=checkStatus&id=' + nfeId,
            method: 'POST',
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        }).done(function(resp){
            var icon = resp.success ? 'success' : 'error';
            Swal.fire('Consulta SEFAZ', resp.message || 'Sem resposta', icon)
                .then(function(){ if (resp.success) location.reload(); });
        }).fail(function(){
            Swal.fire('Erro', 'Falha na comunicação com o servidor.', 'error');
        }).always(function(){
            btn.prop('disabled', false).html('<i class="fas fa-sync me-1"></i> Consultar SEFAZ');
        });
    }

    $('#btnCheckStatusDetail, #btnCheckStatusSidebar').on('click', function(){
        checkSefaz($(this));
    });
});

// Copiar chave de acesso
function copyKey() {
    var keyText = document.getElementById('nfeChave').textContent;
    navigator.clipboard.writeText(keyText).then(function(){
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Chave copiada!', showConfirmButton: false, timer: 1500 });
    });
}
</script>

<?php include __DIR__ . '/partials/toast_notifications.php'; ?>
