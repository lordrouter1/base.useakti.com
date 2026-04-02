<?php
/**
 * View: Painel de Notas Fiscais (NF-e) — Layout com Menu Lateral
 * Estrutura idêntica à página de Relatórios: sidebar + conteúdo principal.
 *
 * @var array  $documents      Lista de NF-e paginada
 * @var int    $totalItems     Total de registros
 * @var int    $totalPages     Total de páginas
 * @var int    $ctPage         Página atual
 * @var array  $statusCounts   Contagem por status
 * @var int    $countThisMonth NF-e emitidas no mês
 * @var float  $sumAuthorized  Valor autorizado no mês
 * @var array  $validation     Validação de credenciais
 * @var array  $filters        Filtros aplicados
 * @var string $baseUrl        URL base para paginação
 * @var array  $alerts         Alertas fiscais (certificado, contingência, etc.)
 * @var int    $queuePending   Itens pendentes na fila
 * @var int    $receivedPending Docs recebidos pendentes
 */
$pageTitle = 'Notas Fiscais — NF-e';

$activeSection = $_GET['sec'] ?? 'notas';
$validSections = ['notas', 'dashboard', 'fila', 'recebidos', 'auditoria', 'webhooks', 'danfe', 'credenciais', 'inutilizar', 'contingencia', 'livros', 'backup', 'exportacoes'];
if (!in_array($activeSection, $validSections)) $activeSection = 'notas';

$statusLabels = [
    'rascunho'    => ['label' => 'Rascunho',    'color' => 'secondary', 'icon' => 'fas fa-pencil-alt'],
    'processando' => ['label' => 'Processando', 'color' => 'info',      'icon' => 'fas fa-spinner'],
    'autorizada'  => ['label' => 'Autorizada',  'color' => 'success',   'icon' => 'fas fa-check-circle'],
    'rejeitada'   => ['label' => 'Rejeitada',   'color' => 'danger',    'icon' => 'fas fa-times-circle'],
    'cancelada'   => ['label' => 'Cancelada',   'color' => 'dark',      'icon' => 'fas fa-ban'],
    'cancelada_retry' => ['label' => 'Reenvio', 'color' => 'secondary', 'icon' => 'fas fa-redo'],
    'denegada'    => ['label' => 'Denegada',     'color' => 'warning',   'icon' => 'fas fa-exclamation'],
    'corrigida'   => ['label' => 'Corrigida',    'color' => 'primary',   'icon' => 'fas fa-pen'],
    'inutilizada' => ['label' => 'Inutilizada',  'color' => 'info',      'icon' => 'fas fa-slash'],
];

$alerts = $alerts ?? [];
$queuePending = $queuePending ?? 0;
$receivedPending = $receivedPending ?? 0;
$totalAll = array_sum(array_map('intval', $statusCounts));
$totalRejCanc = ($statusCounts['rejeitada'] ?? 0) + ($statusCounts['cancelada'] ?? 0);
?>

<!-- ── Flash messages ── -->
<?php require 'app/views/components/flash-messages.php'; ?>

<!-- Styles loaded from assets/css/modules/nfe.css via header.php -->

<div class="container-fluid py-3">

    <!-- ═══ Header ═══ -->
    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div>
            <h1 class="h2 mb-1"><i class="fas fa-file-invoice me-2 text-primary"></i>Notas Fiscais</h1>
            <p class="text-muted mb-0" style="font-size:.82rem;">Central fiscal — emissão, consulta, configurações e relatórios de NF-e / NFC-e.</p>
        </div>
    </div>

    <div class="row g-4">

        <!-- ═══════════════════════════════════════ -->
        <!-- SIDEBAR — Menu Lateral (3/12)           -->
        <!-- ═══════════════════════════════════════ -->
        <div class="col-lg-3 nfe-sidebar-col">
            <div class="card border-0 shadow-sm" style="border-radius:12px;">
                <div class="card-body p-3">
                    <nav class="nfe-sidebar">

                        <div class="nfe-sidebar-label">Documentos</div>

                        <a href="#" class="nfe-nav-item <?= $activeSection === 'notas' ? 'active' : '' ?>" data-sec="notas">
                            <span class="nfe-nav-icon nav-icon-blue">
                                <i class="fas fa-file-invoice"></i>
                            </span>
                            <span>Notas Fiscais</span>
                            <span class="nfe-nav-count nav-icon-blue"><?= $totalAll ?></span>
                        </a>

                        <a href="#" class="nfe-nav-item <?= $activeSection === 'fila' ? 'active' : '' ?>" data-sec="fila">
                            <span class="nfe-nav-icon nav-icon-info">
                                <i class="fas fa-layer-group"></i>
                            </span>
                            <span>Fila de Emissão</span>
                            <?php if ($queuePending > 0): ?>
                            <span class="nfe-nav-count nav-icon-amber"><?= $queuePending ?></span>
                            <?php endif; ?>
                        </a>

                        <a href="#" class="nfe-nav-item <?= $activeSection === 'recebidos' ? 'active' : '' ?>" data-sec="recebidos">
                            <span class="nfe-nav-icon nav-icon-success">
                                <i class="fas fa-inbox"></i>
                            </span>
                            <span>Recebidos (DistDFe)</span>
                            <?php if ($receivedPending > 0): ?>
                            <span class="nfe-nav-count nav-icon-danger"><?= $receivedPending ?></span>
                            <?php endif; ?>
                        </a>

                        <a href="#" class="nfe-nav-item <?= $activeSection === 'inutilizar' ? 'active' : '' ?>" data-sec="inutilizar">
                            <span class="nfe-nav-icon nav-icon-dark">
                                <i class="fas fa-slash"></i>
                            </span>
                            <span>Inutilizar Nº</span>
                        </a>

                        <div class="nfe-sidebar-label">Análises</div>

                        <a href="#" class="nfe-nav-item <?= $activeSection === 'dashboard' ? 'active' : '' ?>" data-sec="dashboard">
                            <span class="nfe-nav-icon nav-icon-purple">
                                <i class="fas fa-chart-bar"></i>
                            </span>
                            <span>Dashboard Fiscal</span>
                        </a>

                        <a href="#" class="nfe-nav-item <?= $activeSection === 'auditoria' ? 'active' : '' ?>" data-sec="auditoria">
                            <span class="nfe-nav-icon nav-icon-red">
                                <i class="fas fa-shield-alt"></i>
                            </span>
                            <span>Auditoria</span>
                        </a>

                        <div class="nfe-sidebar-label">Relatórios Fiscais</div>

                        <a href="#" class="nfe-nav-item <?= $activeSection === 'livros' ? 'active' : '' ?>" data-sec="livros">
                            <span class="nfe-nav-icon nav-icon-teal-alt">
                                <i class="fas fa-book"></i>
                            </span>
                            <span>Livros de Registro</span>
                        </a>

                        <a href="#" class="nfe-nav-item <?= $activeSection === 'exportacoes' ? 'active' : '' ?>" data-sec="exportacoes">
                            <span class="nfe-nav-icon nav-icon-green-alt">
                                <i class="fas fa-file-export"></i>
                            </span>
                            <span>SPED / SINTEGRA</span>
                        </a>

                        <div class="nfe-sidebar-label">Configurações</div>

                        <a href="#" class="nfe-nav-item <?= $activeSection === 'contingencia' ? 'active' : '' ?>" data-sec="contingencia">
                            <span class="nfe-nav-icon nav-icon-orange-alt">
                                <i class="fas fa-exclamation-triangle"></i>
                            </span>
                            <span>Contingência</span>
                        </a>

                        <a href="#" class="nfe-nav-item <?= $activeSection === 'backup' ? 'active' : '' ?>" data-sec="backup">
                            <span class="nfe-nav-icon nav-icon-blue-alt">
                                <i class="fas fa-cloud-upload-alt"></i>
                            </span>
                            <span>Backup XMLs</span>
                        </a>

                        <a href="#" class="nfe-nav-item <?= $activeSection === 'credenciais' ? 'active' : '' ?>" data-sec="credenciais">
                            <span class="nfe-nav-icon nav-icon-success-alt">
                                <i class="fas fa-certificate"></i>
                            </span>
                            <span>Credenciais SEFAZ</span>
                            <?php if (!$validation['valid']): ?>
                            <span class="nfe-nav-count nav-icon-danger">!</span>
                            <?php endif; ?>
                        </a>

                        <a href="#" class="nfe-nav-item <?= $activeSection === 'webhooks' ? 'active' : '' ?>" data-sec="webhooks">
                            <span class="nfe-nav-icon nav-icon-gold">
                                <i class="fas fa-plug"></i>
                            </span>
                            <span>Webhooks</span>
                        </a>

                        <a href="#" class="nfe-nav-item <?= $activeSection === 'danfe' ? 'active' : '' ?>" data-sec="danfe">
                            <span class="nfe-nav-icon nav-icon-grape-alt">
                                <i class="fas fa-palette"></i>
                            </span>
                            <span>DANFE Personalizado</span>
                        </a>

                    </nav>
                </div>
            </div>

            <!-- Status card (apenas desktop) -->
            <div class="card border-0 shadow-sm mt-3 d-none d-lg-block" style="border-radius:12px;">
                <div class="card-body p-3">
                    <h6 class="mb-2 fw-bold text-info-alt" style="font-size:.78rem;">
                        <i class="fas fa-heartbeat me-1"></i>Status do Módulo
                    </h6>
                    <div class="d-flex align-items-center mb-2">
                        <span class="badge me-2 px-2 py-1 <?= $validation['valid'] ? 'badge-success-light' : 'badge-danger-light' ?>" style="font-size:.62rem;">
                            <i class="fas fa-<?= $validation['valid'] ? 'check-circle' : 'times-circle' ?> me-1"></i><?= $validation['valid'] ? 'Ativo' : 'Pendente' ?>
                        </span>
                        <span class="text-muted" style="font-size:.7rem;">Credenciais</span>
                    </div>
                    <div class="d-flex align-items-center mb-2">
                        <span class="badge me-2 px-2 py-1 badge-blue-light" style="font-size:.62rem;">
                            <i class="fas fa-file-invoice me-1"></i><?= $countThisMonth ?>
                        </span>
                        <span class="text-muted" style="font-size:.7rem;">NF-e este mês</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="badge me-2 px-2 py-1 badge-success-light" style="font-size:.62rem;">
                            <i class="fas fa-coins me-1"></i>R$<?= number_format($sumAuthorized, 0, ',', '.') ?>
                        </span>
                        <span class="text-muted" style="font-size:.7rem;">Valor autorizado/mês</span>
                    </div>
                </div>
            </div>

            <!-- Alertas fiscais (apenas desktop) -->
            <?php if (!empty($alerts)): ?>
            <div class="card border-0 shadow-sm mt-3 d-none d-lg-block" style="border-radius:12px;">
                <div class="card-body p-3">
                    <h6 class="mb-2 fw-bold text-red" style="font-size:.78rem;">
                        <i class="fas fa-exclamation-triangle me-1"></i>Alertas
                    </h6>
                    <?php foreach ($alerts as $alert): ?>
                    <div class="d-flex align-items-start gap-2 mb-2" style="font-size:.72rem;">
                        <i class="fas fa-<?= ($alert['severity'] ?? '') === 'danger' ? 'times-circle text-danger' : 'exclamation-triangle text-warning' ?> mt-1" style="font-size:.6rem;"></i>
                        <span class="text-muted"><?= e($alert['message'] ?? '') ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- ═══════════════════════════════════════ -->
        <!-- CONTEÚDO PRINCIPAL (9/12)               -->
        <!-- ═══════════════════════════════════════ -->
        <div class="col-lg-9">

            <!-- ══════════════════════════════════════ -->
            <!-- SEÇÃO: Notas Fiscais (listagem)        -->
            <!-- ══════════════════════════════════════ -->
            <div class="nfe-section <?= $activeSection === 'notas' ? 'active' : '' ?>" id="sec-notas">

                <div class="d-flex align-items-center mb-3">
                    <div class="nfe-nav-icon me-2 nav-icon-blue" style="width:34px;height:34px;">
                        <i class="fas fa-file-invoice" style="font-size:.85rem;"></i>
                    </div>
                    <div>
                        <h5 class="mb-0" style="font-size:1rem;">Notas Fiscais</h5>
                        <p class="text-muted mb-0" style="font-size:.72rem;">Listagem e emissão de NF-e / NFC-e.</p>
                    </div>
                </div>

                <!-- Alertas inline (credenciais + fiscais) -->
                <?php if (!$validation['valid']): ?>
                <div class="alert alert-warning py-2 mb-2" style="font-size:.82rem;">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Credenciais incompletas.</strong>
                    <a href="javascript:void(0)" class="alert-link nfe-goto-sec" data-sec="credenciais">Configure as credenciais SEFAZ</a> antes de emitir.
                </div>
                <?php endif; ?>

                <!-- KPIs -->
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md">
                        <div class="card border-0 shadow-sm h-100 nfe-kpi-card kpi-blue">
                            <div class="card-body text-center py-3">
                                <i class="fas fa-file-invoice fa-lg text-primary opacity-75"></i>
                                <h3 class="mb-0 mt-1"><?= $totalAll ?></h3>
                                <small class="text-muted" style="font-size:.7rem;">Total</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md">
                        <div class="card border-0 shadow-sm h-100 nfe-kpi-card kpi-green">
                            <div class="card-body text-center py-3">
                                <i class="fas fa-check-circle fa-lg text-success opacity-75"></i>
                                <h3 class="mb-0 mt-1"><?= $statusCounts['autorizada'] ?? 0 ?></h3>
                                <small class="text-muted" style="font-size:.7rem;">Autorizadas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md">
                        <div class="card border-0 shadow-sm h-100 nfe-kpi-card kpi-red">
                            <div class="card-body text-center py-3">
                                <i class="fas fa-times-circle fa-lg text-danger opacity-75"></i>
                                <h3 class="mb-0 mt-1"><?= $totalRejCanc ?></h3>
                                <small class="text-muted" style="font-size:.7rem;">Rejeit./Canc.</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md">
                        <div class="card border-0 shadow-sm h-100 nfe-kpi-card kpi-blue-alt">
                            <div class="card-body text-center py-3">
                                <i class="fas fa-calendar-alt fa-lg text-primary opacity-75"></i>
                                <h3 class="mb-0 mt-1"><?= $countThisMonth ?></h3>
                                <small class="text-muted" style="font-size:.7rem;">Este Mês</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md">
                        <div class="card border-0 shadow-sm h-100 nfe-kpi-card kpi-orange">
                            <div class="card-body text-center py-3">
                                <i class="fas fa-coins fa-lg text-warning opacity-75"></i>
                                <h3 class="mb-0 mt-1 fs-5">R$<?= number_format($sumAuthorized, 0, ',', '.') ?></h3>
                                <small class="text-muted" style="font-size:.7rem;">Valor/Mês</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ações Rápidas -->
                <div class="card border-0 shadow-sm mb-4" style="border-radius:12px;">
                    <div class="card-body py-3">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <span class="fw-bold small text-muted me-2"><i class="fas fa-bolt me-1"></i> Ações:</span>
                            <button type="button" class="btn btn-success btn-sm" id="btnBatchEmit" <?= !$validation['valid'] ? 'disabled' : '' ?>>
                                <i class="fas fa-paper-plane me-1"></i> Emitir em Lote
                            </button>
                            <button type="button" class="btn btn-outline-dark btn-sm nfe-goto-sec" data-sec="inutilizar">
                                <i class="fas fa-slash me-1"></i> Inutilizar Nº
                            </button>
                            <a href="?page=reports&cat=fiscal" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-chart-bar me-1"></i> Relatórios Fiscais
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="card border-0 shadow-sm mb-4" style="border-radius:12px;">
                    <div class="card-body py-2 px-3">
                        <form method="GET" class="row g-2 align-items-end">
                            <input type="hidden" name="page" value="nfe_documents">
                            <div class="col-auto">
                                <label class="form-label small mb-0 fw-bold"><i class="fas fa-filter me-1"></i>Status</label>
                                <select class="form-select form-select-sm" name="status" style="min-width:130px;">
                                    <option value="">Todos</option>
                                    <?php foreach ($statusLabels as $key => $info): ?>
                                    <option value="<?= $key ?>" <?= ($filters['status'] ?? '') === $key ? 'selected' : '' ?>><?= $info['label'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-auto">
                                <label class="form-label small mb-0 fw-bold">Modelo</label>
                                <select class="form-select form-select-sm" name="modelo" style="min-width:100px;">
                                    <option value="">Todos</option>
                                    <option value="55" <?= ($filters['modelo'] ?? '') == '55' ? 'selected' : '' ?>>NF-e (55)</option>
                                    <option value="65" <?= ($filters['modelo'] ?? '') == '65' ? 'selected' : '' ?>>NFC-e (65)</option>
                                </select>
                            </div>
                            <div class="col-auto">
                                <label class="form-label small mb-0 fw-bold">Mês</label>
                                <select class="form-select form-select-sm" name="month" style="min-width:70px;">
                                    <option value="">—</option>
                                    <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?= $m ?>" <?= ($filters['month'] ?? '') == $m ? 'selected' : '' ?>><?= str_pad($m, 2, '0', STR_PAD_LEFT) ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-auto">
                                <label class="form-label small mb-0 fw-bold">Ano</label>
                                <select class="form-select form-select-sm" name="year" style="min-width:80px;">
                                    <option value="">—</option>
                                    <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
                                    <option value="<?= $y ?>" <?= ($filters['year'] ?? '') == $y ? 'selected' : '' ?>><?= $y ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col">
                                <label class="form-label small mb-0 fw-bold">Buscar</label>
                                <input type="text" class="form-control form-control-sm" name="search"
                                       placeholder="Nº, chave, destinatário..." value="<?= eAttr($filters['search'] ?? '') ?>">
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search"></i></button>
                                <a href="?page=nfe_documents" class="btn btn-sm btn-outline-secondary" aria-label="Limpar filtros"><i class="fas fa-times" aria-hidden="true"></i></a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tabela de NF-e -->
                <div class="card border-0 shadow-sm" style="border-radius:12px;overflow:hidden;">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle nfe-table">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:40px;" class="text-center">
                                        <input type="checkbox" class="form-check-input" id="chkSelectAll" title="Selecionar tudo">
                                    </th>
                                    <th style="width:55px;">Mod.</th>
                                    <th style="width:70px;" class="text-center">Número</th>
                                    <th>Destinatário</th>
                                    <th style="width:110px;" class="text-end">Valor</th>
                                    <th style="width:100px;" class="text-center">Status</th>
                                    <th style="width:70px;" class="text-center">Pedido</th>
                                    <th style="width:120px;">Data</th>
                                    <th style="width:150px;" class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($documents)): ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-5">
                                        <i class="fas fa-file-invoice fa-3x mb-3 opacity-25"></i><br>
                                        <span class="fw-bold">Nenhuma nota fiscal encontrada.</span><br>
                                        <small>Use os filtros acima ou emita uma nova NF-e.</small>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($documents as $doc):
                                    $si = $statusLabels[$doc['status']] ?? ['label' => $doc['status'], 'color' => 'secondary', 'icon' => 'fas fa-circle'];
                                    $modelo = (int)($doc['modelo'] ?? 55);
                                ?>
                                <tr>
                                    <td class="text-center">
                                        <?php if (empty($doc['xml_autorizado']) && in_array($doc['status'], ['rascunho', 'rejeitada'])): ?>
                                        <input type="checkbox" class="form-check-input chk-nfe-select" value="<?= $doc['order_id'] ?? '' ?>" data-nfeid="<?= $doc['id'] ?>">
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $modelo === 65 ? 'info' : 'primary' ?> nfe-badge"><?= $modelo === 65 ? 'NFC-e' : 'NF-e' ?></span>
                                    </td>
                                    <td class="text-center fw-bold"><?= e($doc['numero']) ?></td>
                                    <td>
                                        <span class="d-block text-truncate" style="max-width:220px;"><?= e($doc['dest_nome'] ?? '—') ?></span>
                                        <?php if (!empty($doc['dest_cnpj_cpf'])): ?>
                                        <small class="text-muted" style="font-size:.7rem;"><?= e($doc['dest_cnpj_cpf']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end fw-bold">R$ <?= number_format($doc['valor_total'], 2, ',', '.') ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-<?= $si['color'] ?> nfe-badge">
                                            <i class="<?= $si['icon'] ?> me-1"></i><?= $si['label'] ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($doc['order_id']): ?>
                                        <a href="?page=pipeline&action=detail&id=<?= $doc['order_id'] ?>" class="text-decoration-none" style="font-size:.8rem;">
                                            #<?= str_pad($doc['order_num'] ?? $doc['order_id'], 4, '0', STR_PAD_LEFT) ?>
                                        </a>
                                        <?php else: ?>—<?php endif; ?>
                                    </td>
                                    <td><small style="font-size:.75rem;"><?= date('d/m/Y H:i', strtotime($doc['created_at'])) ?></small></td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <a href="?page=nfe_documents&action=detail&id=<?= $doc['id'] ?>" class="btn btn-outline-primary" title="Detalhe" aria-label="Ver detalhe"><i class="fas fa-eye" aria-hidden="true"></i></a>
                                            <?php if ($doc['xml_autorizado']): ?>
                                            <a href="?page=nfe_documents&action=download&id=<?= $doc['id'] ?>&type=danfe" class="btn btn-danger" title="DANFE" aria-label="Baixar DANFE" target="_blank"><i class="fas fa-print" aria-hidden="true"></i></a>
                                            <a href="?page=nfe_documents&action=download&id=<?= $doc['id'] ?>&type=xml" class="btn btn-outline-secondary" title="XML" aria-label="Baixar XML"><i class="fas fa-file-code" aria-hidden="true"></i></a>
                                            <?php endif; ?>
                                            <?php if ($doc['status'] === 'autorizada'): ?>
                                            <button type="button" class="btn btn-outline-dark btn-cancel-nfe" data-id="<?= $doc['id'] ?>" data-numero="<?= e($doc['numero']) ?>" title="Cancelar" aria-label="Cancelar NF-e"><i class="fas fa-ban" aria-hidden="true"></i></button>
                                            <button type="button" class="btn btn-outline-info btn-correcao-nfe" data-id="<?= $doc['id'] ?>" data-numero="<?= e($doc['numero']) ?>" title="CC-e" aria-label="Carta de correção"><i class="fas fa-pen" aria-hidden="true"></i></button>
                                            <?php endif; ?>
                                            <?php if ($doc['status'] === 'rejeitada'): ?>
                                            <button type="button" class="btn btn-outline-warning btn-retry-nfe" data-id="<?= $doc['id'] ?>" data-numero="<?= e($doc['numero']) ?>" title="Reenviar NF-e" aria-label="Reenviar NF-e"><i class="fas fa-redo" aria-hidden="true"></i></button>
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
                    <ul class="pagination pagination-sm justify-content-center mb-1">
                        <li class="page-item <?= $ctPage <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= $baseUrl ?>&pg=<?= $ctPage - 1 ?>"><i class="fas fa-chevron-left"></i></a>
                        </li>
                        <?php
                        $start = max(1, $ctPage - 3); $end = min($totalPages, $ctPage + 3);
                        if ($start > 1): ?>
                        <li class="page-item"><a class="page-link" href="<?= $baseUrl ?>&pg=1">1</a></li>
                        <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                        <?php endif; ?>
                        <?php for ($p = $start; $p <= $end; $p++): ?>
                        <li class="page-item <?= $p === $ctPage ? 'active' : '' ?>">
                            <a class="page-link" href="<?= $baseUrl ?>&pg=<?= $p ?>"><?= $p ?></a>
                        </li>
                        <?php endfor; ?>
                        <?php if ($end < $totalPages): ?>
                        <?php if ($end < $totalPages - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                        <li class="page-item"><a class="page-link" href="<?= $baseUrl ?>&pg=<?= $totalPages ?>"><?= $totalPages ?></a></li>
                        <?php endif; ?>
                        <li class="page-item <?= $ctPage >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= $baseUrl ?>&pg=<?= $ctPage + 1 ?>"><i class="fas fa-chevron-right"></i></a>
                        </li>
                    </ul>
                    <p class="text-center text-muted small mb-0"><?= $totalItems ?> registro(s)</p>
                </nav>
                <?php endif; ?>

            </div><!-- /sec-notas -->


            <!-- ══════════════════════════════════════ -->
            <!-- SEÇÃO: Dashboard Fiscal                -->
            <!-- ══════════════════════════════════════ -->
            <div class="nfe-section <?= $activeSection === 'dashboard' ? 'active' : '' ?>" id="sec-dashboard">
                <div class="d-flex align-items-center mb-3">
                    <div class="nfe-nav-icon me-2 nav-icon-purple" style="width:34px;height:34px;">
                        <i class="fas fa-chart-bar" style="font-size:.85rem;"></i>
                    </div>
                    <div>
                        <h5 class="mb-0" style="font-size:1rem;">Dashboard Fiscal</h5>
                        <p class="text-muted mb-0" style="font-size:.72rem;">Visão gerencial com gráficos e indicadores.</p>
                    </div>
                </div>
                <div class="text-center py-5" id="dashboardPlaceholder">
                    <i class="fas fa-spinner fa-spin fa-2x text-muted mb-3"></i>
                    <p class="text-muted">Carregando Dashboard...</p>
                </div>
                <div id="dashboardContent" style="display:none;"></div>
            </div>


            <!-- ══════════════════════════════════════ -->
            <!-- SEÇÃO: Fila de Emissão                 -->
            <!-- ══════════════════════════════════════ -->
            <div class="nfe-section <?= $activeSection === 'fila' ? 'active' : '' ?>" id="sec-fila">
                <div class="d-flex align-items-center mb-3">
                    <div class="nfe-nav-icon me-2 nav-icon-teal" style="width:34px;height:34px;">
                        <i class="fas fa-layer-group" style="font-size:.85rem;"></i>
                    </div>
                    <div>
                        <h5 class="mb-0" style="font-size:1rem;">Fila de Emissão</h5>
                        <p class="text-muted mb-0" style="font-size:.72rem;">NF-e aguardando processamento assíncrono.</p>
                    </div>
                </div>
                <div class="text-center py-5" id="filaPlaceholder">
                    <i class="fas fa-spinner fa-spin fa-2x text-muted mb-3"></i>
                    <p class="text-muted">Carregando Fila...</p>
                </div>
                <div id="filaContent" style="display:none;"></div>
            </div>


            <!-- ══════════════════════════════════════ -->
            <!-- SEÇÃO: Documentos Recebidos (DistDFe)  -->
            <!-- ══════════════════════════════════════ -->
            <div class="nfe-section <?= $activeSection === 'recebidos' ? 'active' : '' ?>" id="sec-recebidos">
                <div class="d-flex align-items-center mb-3">
                    <div class="nfe-nav-icon me-2 nav-icon-success" style="width:34px;height:34px;">
                        <i class="fas fa-inbox" style="font-size:.85rem;"></i>
                    </div>
                    <div>
                        <h5 class="mb-0" style="font-size:1rem;">Documentos Recebidos</h5>
                        <p class="text-muted mb-0" style="font-size:.72rem;">NF-e recebidas via DistDFe para manifestação.</p>
                    </div>
                </div>
                <div class="text-center py-5" id="recebidosPlaceholder">
                    <i class="fas fa-spinner fa-spin fa-2x text-muted mb-3"></i>
                    <p class="text-muted">Carregando Documentos Recebidos...</p>
                </div>
                <div id="recebidosContent" style="display:none;"></div>
            </div>


            <!-- ══════════════════════════════════════ -->
            <!-- SEÇÃO: Inutilizar Numeração            -->
            <!-- ══════════════════════════════════════ -->
            <div class="nfe-section <?= $activeSection === 'inutilizar' ? 'active' : '' ?>" id="sec-inutilizar">
                <div class="d-flex align-items-center mb-3">
                    <div class="nfe-nav-icon me-2 icon-circle-gray" style="width:34px;height:34px;">
                        <i class="fas fa-slash icon-dark" style="font-size:.85rem;"></i>
                    </div>
                    <div>
                        <h5 class="mb-0" style="font-size:1rem;">Inutilizar Numeração</h5>
                        <p class="text-muted mb-0" style="font-size:.72rem;">Inutilize números reservados não utilizados na SEFAZ.</p>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-xl-6">
                        <div class="card border-0 shadow-sm h-100" style="border-radius:12px;">
                            <div class="card-header py-2 card-header-nfe-dark">
                                <h6 class="mb-0 text-white" style="font-size:.85rem;">
                                    <i class="fas fa-slash me-2"></i>Inutilizar Faixa Numérica
                                </h6>
                            </div>
                            <div class="card-body p-3">
                                <div class="alert alert-warning py-2 small mb-3">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    Inutilização é obrigatória para números reservados não utilizados. <strong>Irreversível.</strong>
                                </div>
                                <div class="row g-3">
                                    <div class="col-6">
                                        <label class="form-label small fw-bold">Nº Inicial <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control form-control-sm" id="inutNumInicial" min="1">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-bold">Nº Final <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control form-control-sm" id="inutNumFinal" min="1">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-bold">Modelo</label>
                                        <select class="form-select form-select-sm" id="inutModelo">
                                            <option value="55">NF-e (55)</option>
                                            <option value="65">NFC-e (65)</option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-bold">Série</label>
                                        <input type="number" class="form-control form-control-sm" id="inutSerie" value="1" min="1">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-bold">Justificativa <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="inutJustificativa" rows="3" placeholder="Mín. 15 caracteres..."></textarea>
                                    </div>
                                    <div class="col-12">
                                        <button type="button" class="btn btn-dark w-100" id="btnConfirmInutilizar">
                                            <i class="fas fa-slash me-1"></i> Inutilizar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="card border-0 shadow-sm h-100" style="border-radius:12px;">
                            <div class="card-header py-2 bg-light">
                                <h6 class="mb-0" style="font-size:.85rem;"><i class="fas fa-info-circle me-2 text-info"></i>Sobre a Inutilização</h6>
                            </div>
                            <div class="card-body p-3 text-secondary" style="font-size:.82rem;">
                                <p class="mb-2"><i class="fas fa-check text-success me-2"></i>Obrigatória para números pulados (gaps na sequência de numeração).</p>
                                <p class="mb-2"><i class="fas fa-check text-success me-2"></i>Deve ser feita antes do primeiro dia útil do mês seguinte.</p>
                                <p class="mb-2"><i class="fas fa-times text-danger me-2"></i>Ação irreversível — não pode ser desfeita.</p>
                                <p class="mb-0"><i class="fas fa-times text-danger me-2"></i>Não inutilize números que ainda serão utilizados.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <!-- ══════════════════════════════════════ -->
            <!-- SEÇÃO: Auditoria                       -->
            <!-- ══════════════════════════════════════ -->
            <div class="nfe-section <?= $activeSection === 'auditoria' ? 'active' : '' ?>" id="sec-auditoria">
                <div class="d-flex align-items-center mb-3">
                    <div class="nfe-nav-icon me-2 nav-icon-red" style="width:34px;height:34px;">
                        <i class="fas fa-shield-alt" style="font-size:.85rem;"></i>
                    </div>
                    <div>
                        <h5 class="mb-0" style="font-size:1rem;">Auditoria</h5>
                        <p class="text-muted mb-0" style="font-size:.72rem;">Trilha de auditoria e logs de acessos fiscais.</p>
                    </div>
                </div>
                <div class="text-center py-5" id="auditoriaPlaceholder">
                    <i class="fas fa-spinner fa-spin fa-2x text-muted mb-3"></i>
                    <p class="text-muted">Carregando Auditoria...</p>
                </div>
                <div id="auditoriaContent" style="display:none;"></div>
            </div>


            <!-- ══════════════════════════════════════ -->
            <!-- SEÇÃO: Credenciais SEFAZ               -->
            <!-- ══════════════════════════════════════ -->
            <div class="nfe-section <?= $activeSection === 'credenciais' ? 'active' : '' ?>" id="sec-credenciais">
                <div class="d-flex align-items-center mb-3">
                    <div class="nfe-nav-icon me-2 nav-icon-success" style="width:34px;height:34px;">
                        <i class="fas fa-certificate" style="font-size:.85rem;"></i>
                    </div>
                    <div>
                        <h5 class="mb-0" style="font-size:1rem;">Credenciais SEFAZ</h5>
                        <p class="text-muted mb-0" style="font-size:.72rem;">Dados do emitente, certificado digital e configuração de emissão.</p>
                    </div>
                </div>
                <div class="text-center py-5" id="credenciaisPlaceholder">
                    <i class="fas fa-spinner fa-spin fa-2x text-muted mb-3"></i>
                    <p class="text-muted">Carregando Credenciais...</p>
                </div>
                <div id="credenciaisContent" style="display:none;"></div>
            </div>


            <!-- ══════════════════════════════════════ -->
            <!-- SEÇÃO: Webhooks                        -->
            <!-- ══════════════════════════════════════ -->
            <div class="nfe-section <?= $activeSection === 'webhooks' ? 'active' : '' ?>" id="sec-webhooks">
                <div class="d-flex align-items-center mb-3">
                    <div class="nfe-nav-icon me-2 nav-icon-orange" style="width:34px;height:34px;">
                        <i class="fas fa-plug" style="font-size:.85rem;"></i>
                    </div>
                    <div>
                        <h5 class="mb-0" style="font-size:1rem;">Webhooks</h5>
                        <p class="text-muted mb-0" style="font-size:.72rem;">Configuração de integrações externas via webhooks.</p>
                    </div>
                </div>
                <div class="text-center py-5" id="webhooksPlaceholder">
                    <i class="fas fa-spinner fa-spin fa-2x text-muted mb-3"></i>
                    <p class="text-muted">Carregando Webhooks...</p>
                </div>
                <div id="webhooksContent" style="display:none;"></div>
            </div>


            <!-- ══════════════════════════════════════ -->
            <!-- SEÇÃO: DANFE Personalizado              -->
            <!-- ══════════════════════════════════════ -->
            <div class="nfe-section <?= $activeSection === 'danfe' ? 'active' : '' ?>" id="sec-danfe">
                <div class="d-flex align-items-center mb-3">
                    <div class="nfe-nav-icon me-2 nav-icon-grape" style="width:34px;height:34px;">
                        <i class="fas fa-palette" style="font-size:.85rem;"></i>
                    </div>
                    <div>
                        <h5 class="mb-0" style="font-size:1rem;">DANFE Personalizado</h5>
                        <p class="text-muted mb-0" style="font-size:.72rem;">Personalize o layout do DANFE com logo e informações adicionais.</p>
                    </div>
                </div>
                <div class="text-center py-5" id="danfePlaceholder">
                    <i class="fas fa-spinner fa-spin fa-2x text-muted mb-3"></i>
                    <p class="text-muted">Carregando DANFE Personalizado...</p>
                </div>
                <div id="danfeContent" style="display:none;"></div>
            </div>


            <!-- ══════════════════════════════════════ -->
            <!-- SEÇÃO: Contingência (FASE5-02)         -->
            <!-- ══════════════════════════════════════ -->
            <div class="nfe-section <?= $activeSection === 'contingencia' ? 'active' : '' ?>" id="sec-contingencia">
                <div class="d-flex align-items-center mb-3">
                    <div class="nfe-nav-icon me-2 icon-circle-orange" style="width:34px;height:34px;">
                        <i class="fas fa-exclamation-triangle icon-warning" style="font-size:.85rem;"></i>
                    </div>
                    <div>
                        <h5 class="mb-0" style="font-size:1rem;">Contingência NF-e</h5>
                        <p class="text-muted mb-0" style="font-size:.72rem;">Gerenciamento de emissão em contingência (SVC-AN/SVC-RS/Offline NFC-e).</p>
                    </div>
                </div>

                <!-- Status da Contingência -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm h-100" style="border-radius:12px;">
                            <div class="card-header py-2 card-header-nfe-orange">
                                <h6 class="mb-0 text-white" style="font-size:.85rem;">
                                    <i class="fas fa-heartbeat me-2"></i>Status Atual
                                </h6>
                            </div>
                            <div class="card-body p-3 text-center" id="contingencyStatusContent">
                                <i class="fas fa-spinner fa-spin fa-2x text-muted mb-3"></i>
                                <p class="text-muted small">Carregando status...</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm h-100" style="border-radius:12px;">
                            <div class="card-header py-2 bg-light">
                                <h6 class="mb-0" style="font-size:.85rem;"><i class="fas fa-bolt me-2 text-warning"></i>Ações</h6>
                            </div>
                            <div class="card-body p-3">
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Justificativa</label>
                                    <textarea class="form-control form-control-sm" id="contingencyJustificativa" rows="2" placeholder="Motivo da ativação (mín. 15 caracteres)..."></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Tipo de Emissão</label>
                                    <select class="form-select form-select-sm" id="contingencyTpEmis">
                                        <option value="6">SVC-AN (6)</option>
                                        <option value="7">SVC-RS (7)</option>
                                        <option value="9">Offline NFC-e (9)</option>
                                    </select>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-warning btn-sm flex-fill" id="btnContingencyActivate">
                                        <i class="fas fa-toggle-on me-1"></i> Ativar
                                    </button>
                                    <button type="button" class="btn btn-success btn-sm flex-fill" id="btnContingencyDeactivate">
                                        <i class="fas fa-toggle-off me-1"></i> Desativar
                                    </button>
                                    <button type="button" class="btn btn-info btn-sm flex-fill" id="btnContingencySync">
                                        <i class="fas fa-sync me-1"></i> Sincronizar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Histórico de Contingência -->
                <div class="card border-0 shadow-sm" style="border-radius:12px;overflow:hidden;">
                    <div class="card-header py-2 bg-light">
                        <h6 class="mb-0" style="font-size:.85rem;"><i class="fas fa-history me-2 text-primary"></i>Histórico de Contingência</h6>
                    </div>
                    <div id="contingencyHistoryContent">
                        <div class="text-center py-4">
                            <i class="fas fa-spinner fa-spin text-muted"></i>
                            <small class="text-muted ms-2">Carregando...</small>
                        </div>
                    </div>
                </div>
            </div>


            <!-- ══════════════════════════════════════ -->
            <!-- SEÇÃO: Livros de Registro (FASE5-06/07) -->
            <!-- ══════════════════════════════════════ -->
            <div class="nfe-section <?= $activeSection === 'livros' ? 'active' : '' ?>" id="sec-livros">
                <div class="d-flex align-items-center mb-3">
                    <div class="nfe-nav-icon me-2 nav-icon-teal" style="width:34px;height:34px;">
                        <i class="fas fa-book" style="font-size:.85rem;"></i>
                    </div>
                    <div>
                        <h5 class="mb-0" style="font-size:1rem;">Livros de Registro</h5>
                        <p class="text-muted mb-0" style="font-size:.72rem;">Livros de Registro de Saídas e Entradas conforme legislação fiscal.</p>
                    </div>
                </div>
                <div class="text-center py-5" id="livrosPlaceholder">
                    <i class="fas fa-spinner fa-spin fa-2x text-muted mb-3"></i>
                    <p class="text-muted">Carregando Livros...</p>
                </div>
                <div id="livrosContent" style="display:none;"></div>
            </div>


            <!-- ══════════════════════════════════════ -->
            <!-- SEÇÃO: SPED / SINTEGRA (FASE5-04/05)   -->
            <!-- ══════════════════════════════════════ -->
            <div class="nfe-section <?= $activeSection === 'exportacoes' ? 'active' : '' ?>" id="sec-exportacoes">
                <div class="d-flex align-items-center mb-3">
                    <div class="nfe-nav-icon me-2 nav-icon-green" style="width:34px;height:34px;">
                        <i class="fas fa-file-export" style="font-size:.85rem;"></i>
                    </div>
                    <div>
                        <h5 class="mb-0" style="font-size:1rem;">Exportações Fiscais</h5>
                        <p class="text-muted mb-0" style="font-size:.72rem;">SPED Fiscal (EFD ICMS/IPI), SINTEGRA e download em lote.</p>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- SPED Fiscal -->
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100" style="border-radius:12px;">
                            <div class="card-header py-2 card-header-nfe-green">
                                <h6 class="mb-0 text-white" style="font-size:.85rem;">
                                    <i class="fas fa-file-invoice me-2"></i>SPED Fiscal (EFD)
                                </h6>
                            </div>
                            <div class="card-body p-3">
                                <p class="small text-muted mb-3">Gera o arquivo SPED Fiscal (EFD ICMS/IPI) no layout oficial da RFB.</p>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="form-label small fw-bold">Início</label>
                                        <input type="date" class="form-control form-control-sm" id="spedStartDate" value="<?= date('Y-m-01') ?>">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-bold">Fim</label>
                                        <input type="date" class="form-control form-control-sm" id="spedEndDate" value="<?= date('Y-m-d') ?>">
                                    </div>
                                </div>
                                <a href="#" id="btnExportSped" class="btn btn-success btn-sm w-100 mt-3">
                                    <i class="fas fa-download me-1"></i> Gerar SPED Fiscal
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- SINTEGRA -->
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100" style="border-radius:12px;">
                            <div class="card-header py-2 card-header-nfe-blue">
                                <h6 class="mb-0 text-white" style="font-size:.85rem;">
                                    <i class="fas fa-file-alt me-2"></i>SINTEGRA
                                </h6>
                            </div>
                            <div class="card-body p-3">
                                <p class="small text-muted mb-3">Gera o arquivo SINTEGRA com registros 10, 11, 50, 51, 54, 75, 90, 99.</p>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="form-label small fw-bold">Início</label>
                                        <input type="date" class="form-control form-control-sm" id="sintegraStartDate" value="<?= date('Y-m-01') ?>">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-bold">Fim</label>
                                        <input type="date" class="form-control form-control-sm" id="sintegraEndDate" value="<?= date('Y-m-d') ?>">
                                    </div>
                                </div>
                                <a href="#" id="btnExportSintegra" class="btn btn-info btn-sm w-100 mt-3">
                                    <i class="fas fa-download me-1"></i> Gerar SINTEGRA
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Download em Lote (ZIP) -->
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100" style="border-radius:12px;">
                            <div class="card-header py-2 card-header-nfe-purple">
                                <h6 class="mb-0 text-white" style="font-size:.85rem;">
                                    <i class="fas fa-file-archive me-2"></i>Download XML (Lote)
                                </h6>
                            </div>
                            <div class="card-body p-3">
                                <p class="small text-muted mb-3">Baixe todos os XMLs de NF-e autorizadas de um período em um ZIP.</p>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="form-label small fw-bold">Início</label>
                                        <input type="date" class="form-control form-control-sm" id="batchStartDate" value="<?= date('Y-m-01') ?>">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-bold">Fim</label>
                                        <input type="date" class="form-control form-control-sm" id="batchEndDate" value="<?= date('Y-m-d') ?>">
                                    </div>
                                </div>
                                <a href="#" id="btnDownloadBatch" class="btn btn-secondary btn-sm w-100 mt-3">
                                    <i class="fas fa-download me-1"></i> Baixar XMLs (ZIP)
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <!-- ══════════════════════════════════════ -->
            <!-- SEÇÃO: Backup de XMLs (FASE5-08)       -->
            <!-- ══════════════════════════════════════ -->
            <div class="nfe-section <?= $activeSection === 'backup' ? 'active' : '' ?>" id="sec-backup">
                <div class="d-flex align-items-center mb-3">
                    <div class="nfe-nav-icon me-2 nav-icon-blue" style="width:34px;height:34px;">
                        <i class="fas fa-cloud-upload-alt" style="font-size:.85rem;"></i>
                    </div>
                    <div>
                        <h5 class="mb-0" style="font-size:1rem;">Backup de XMLs</h5>
                        <p class="text-muted mb-0" style="font-size:.72rem;">Configuração e histórico de backup externo de XMLs fiscais.</p>
                    </div>
                </div>
                <div class="text-center py-5" id="backupPlaceholder">
                    <i class="fas fa-spinner fa-spin fa-2x text-muted mb-3"></i>
                    <p class="text-muted">Carregando Backup...</p>
                </div>
                <div id="backupContent" style="display:none;"></div>
            </div>

        </div><!-- /col-lg-9 -->

    </div><!-- /row -->
</div><!-- /container-fluid -->


<!-- ═══════════════════════════════════════════════════ -->
<!-- MODAIS                                             -->
<!-- ═══════════════════════════════════════════════════ -->

<!-- Modal Cancelamento -->
<div class="modal fade" id="modalCancelNfe" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title text-danger"><i class="fas fa-ban me-2"></i> Cancelar NF-e</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="cancelNfeId">
                <div class="alert alert-danger py-2 mb-3" style="font-size:.85rem;">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    Cancelar NF-e nº <strong id="cancelNfeNum"></strong>? Ação <strong>irreversível</strong>.
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Justificativa <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="cancelMotivo" rows="3" placeholder="Mínimo 15 caracteres..."></textarea>
                    <div class="d-flex justify-content-between mt-1">
                        <small class="text-muted">Mín. 15 caracteres</small>
                        <small class="text-muted"><span id="cancelChars">0</span>/15</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-danger" id="btnConfirmCancel"><i class="fas fa-ban me-1"></i> Confirmar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Carta de Correção -->
<div class="modal fade" id="modalCorrecaoNfe" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title text-info"><i class="fas fa-pen me-2"></i> Carta de Correção</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="correcaoNfeId">
                <p class="small text-muted mb-3">NF-e nº <strong id="correcaoNfeNum"></strong></p>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Texto da Correção <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="correcaoTexto" rows="4" placeholder="Mín. 15 caracteres..."></textarea>
                    <div class="d-flex justify-content-between mt-1">
                        <small class="text-muted">Mín. 15 caracteres</small>
                        <small class="text-muted"><span id="correcaoChars">0</span>/15</small>
                    </div>
                </div>
                <div class="alert alert-info py-2 small mb-0">
                    <i class="fas fa-info-circle me-1"></i> A CC-e não pode alterar valores, impostos, dados do emitente/destinatário.
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-info" id="btnConfirmCorrecao"><i class="fas fa-paper-plane me-1"></i> Enviar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Emissão em Lote -->
<div class="modal fade" id="modalBatchEmit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title text-success"><i class="fas fa-paper-plane me-2"></i> Emissão em Lote</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="batchStep1">
                    <p class="small text-muted mb-3"><i class="fas fa-info-circle me-1"></i> Os pedidos selecionados serão enviados para a fila.</p>
                    <div class="table-responsive" style="max-height:280px;">
                        <table class="table table-sm table-hover mb-0" style="font-size:.8rem;">
                            <thead class="table-light"><tr><th>Pedido</th><th>Cliente</th><th class="text-end">Valor</th></tr></thead>
                            <tbody id="batchPreviewBody"><tr><td colspan="3" class="text-center text-muted">Nenhum selecionado.</td></tr></tbody>
                        </table>
                    </div>
                    <div class="mt-2"><span class="small text-muted"><strong id="batchCount">0</strong> pedido(s)</span></div>
                </div>
                <div id="batchStep2" style="display:none;">
                    <div class="text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-2x text-success mb-3"></i>
                        <p class="fw-bold mb-1">Enfileirando emissão...</p>
                        <div class="progress mx-auto" style="height:6px;max-width:300px;">
                            <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" id="batchProgressBar" style="width:0%"></div>
                        </div>
                    </div>
                </div>
                <div id="batchStep3" style="display:none;"><div class="text-center py-4" id="batchResultContent"></div></div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-success" id="btnConfirmBatch"><i class="fas fa-paper-plane me-1"></i> Confirmar</button>
            </div>
        </div>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function(){
    var csrfToken = $('meta[name="csrf-token"]').attr('content');

    // ══════════════════════════════════════
    // Navegação Sidebar (idêntica a Relatórios)
    // ══════════════════════════════════════
    $('.nfe-nav-item').on('click', function(e){
        e.preventDefault();
        var sec = $(this).data('sec');
        if (!sec) return;

        // Atualizar active no sidebar
        $('.nfe-nav-item').removeClass('active');
        $(this).addClass('active');

        // Mostrar seção correta
        $('.nfe-section').removeClass('active');
        $('#sec-' + sec).addClass('active');

        // Atualizar URL sem recarregar (para bookmarking)
        var url = new URL(window.location.href);
        url.searchParams.set('sec', sec);
        window.history.replaceState({}, '', url);

        // Lazy-load de conteúdo AJAX para seções que precisam
        loadSectionContent(sec);
    });

    // Links internos para trocar seção (ex: "Configure credenciais")
    $(document).on('click', '.nfe-goto-sec', function(e){
        e.preventDefault();
        var sec = $(this).data('sec');
        if (sec) {
            $('.nfe-nav-item[data-sec="' + sec + '"]').click();
        }
    });

    // ══════════════════════════════════════
    // Lazy-load de conteúdo por AJAX
    // ══════════════════════════════════════
    var loadedSections = { notas: true, inutilizar: true, contingencia: false, exportacoes: true }; // Já carregadas inline

    function loadSectionContent(sec) {
        if (loadedSections[sec]) return;

        var urlMap = {
            dashboard:    '?page=nfe_documents&action=dashboard&_ajax=1',
            fila:         '?page=nfe_documents&action=queue&_ajax=1',
            recebidos:    '?page=nfe_documents&action=received&_ajax=1',
            auditoria:    '?page=nfe_documents&action=audit&_ajax=1',
            credenciais:  '?page=nfe_credentials&_ajax=1',
            webhooks:     '?page=nfe_documents&action=webhooks&_ajax=1',
            danfe:        '?page=nfe_documents&action=danfeSettings&_ajax=1',
            livros:       '?page=nfe_documents&action=livroSaidas&_ajax=1',
            backup:       '?page=nfe_documents&action=backupSettings&_ajax=1',
        };

        if (!urlMap[sec]) return;

        $.ajax({
            url: urlMap[sec],
            method: 'GET',
            dataType: 'html'
        }).done(function(html) {
            $('#' + sec + 'Content').html(html).show();
            $('#' + sec + 'Placeholder').hide();
            loadedSections[sec] = true;
        }).fail(function() {
            $('#' + sec + 'Placeholder').html(
                '<div class="text-center py-5">' +
                '<i class="fas fa-exclamation-circle fa-2x text-danger mb-3"></i>' +
                '<p class="text-muted">Erro ao carregar. <a href="javascript:void(0)" class="nfe-goto-sec" data-sec="' + sec + '">Tentar novamente</a></p>' +
                '</div>'
            );
            // Permitir re-carregar
            delete loadedSections[sec];
        });
    }

    // Carregar seção inicial se não for "notas"
    var initialSec = '<?= $activeSection ?>';
    if (initialSec !== 'notas' && initialSec !== 'inutilizar') {
        loadSectionContent(initialSec);
    }

    // ══════════════════════════════════════
    // AJAX form interceptor para filtros em seções lazy-loaded
    // Formulários com data-ajax-filter submetem via AJAX e
    // atualizam o conteúdo da seção sem recarregar a página.
    // ══════════════════════════════════════
    $(document).on('submit', 'form[data-ajax-filter]', function(e) {
        e.preventDefault();
        var $form = $(this);
        var sec = $form.data('ajax-filter');
        var url = $form.data('ajax-url');
        var params = $form.serialize();
        // Garantir _ajax=1
        if (params.indexOf('_ajax=1') === -1) params += '&_ajax=1';

        var $container = $('#' + sec + 'Content');
        $container.css('opacity', '.5');

        $.get(url + '&' + params, function(html) {
            $container.html(html).css('opacity', '1');
        }).fail(function() {
            $container.css('opacity', '1');
            Swal.fire({icon:'error', title:'Erro', text:'Falha ao aplicar filtro.'});
        });
    });

    // ══════════════════════════════════════
    // AJAX pagination interceptor
    // Intercepta cliques em links de paginação dentro de
    // seções lazy-loaded para recarregar via AJAX.
    // ══════════════════════════════════════
    var ajaxSectionContainers = {
        fila: '#filaContent',
        recebidos: '#recebidosContent',
        auditoria: '#auditoriaContent',
        dashboard: '#dashboardContent',
        webhooks: '#webhooksContent',
        danfe: '#danfeContent',
        credenciais: '#credenciaisContent',
        livros: '#livrosContent',
        backup: '#backupContent'
    };

    $(document).on('click', '.nfe-section .pagination a.page-link, .nfe-section a[href*="action=queue&"], .nfe-section a[href*="action=received&"], .nfe-section a[href*="action=audit&"]', function(e) {
        var $link = $(this);
        var href = $link.attr('href');
        if (!href || href === '#' || $link.closest('.disabled').length) return;

        // Verificar se estamos dentro de um container AJAX
        var $container = $link.closest('[id$="Content"]');
        if (!$container.length) return;

        e.preventDefault();
        var separator = href.indexOf('?') >= 0 ? '&' : '?';
        var ajaxUrl = href + separator + '_ajax=1';

        $container.css('opacity', '.5');
        $.get(ajaxUrl, function(html) {
            $container.html(html).css('opacity', '1');
            // Scroll to top of section
            $container.closest('.nfe-section')[0]?.scrollIntoView({behavior:'smooth', block:'start'});
        }).fail(function() {
            $container.css('opacity', '1');
        });
    });

    // ══════════════════════════════════════
    // Selecionar tudo na tabela
    // ══════════════════════════════════════
    $('#chkSelectAll').on('change', function(){ $('.chk-nfe-select').prop('checked', $(this).is(':checked')); });

    // ══════════════════════════════════════
    // Emissão em Lote
    // ══════════════════════════════════════
    $('#btnBatchEmit').on('click', function(){
        var checked = $('.chk-nfe-select:checked');
        if (!checked.length) { Swal.fire({icon:'warning',title:'Atenção',text:'Selecione ao menos um pedido.',confirmButtonColor:'#f39c12'}); return; }
        var rows = '';
        checked.each(function(){
            var tr = $(this).closest('tr');
            rows += '<tr><td>#' + $(this).val() + '</td><td>' + tr.find('td:eq(3)').text().trim() + '</td><td class="text-end">' + tr.find('td:eq(4)').text().trim() + '</td></tr>';
        });
        $('#batchPreviewBody').html(rows);
        $('#batchCount').text(checked.length);
        $('#batchStep1').show(); $('#batchStep2,#batchStep3').hide();
        $('#btnConfirmBatch').show().prop('disabled', false);
        new bootstrap.Modal('#modalBatchEmit').show();
    });

    $('#btnConfirmBatch').on('click', function(){
        var btn = $(this), ids = [];
        $('.chk-nfe-select:checked').each(function(){ if($(this).val()) ids.push($(this).val()); });
        btn.prop('disabled', true);
        $('#batchStep1').hide(); $('#batchStep2').show();
        $('#batchProgressBar').css('width','30%');
        $.ajax({
            url:'?page=nfe_documents&action=batchEmit', method:'POST', dataType:'json',
            data:{order_ids:ids.join(',')}, headers:{'X-CSRF-TOKEN':csrfToken}
        }).done(function(r){
            $('#batchProgressBar').css('width','100%');
            setTimeout(function(){
                $('#batchStep2').hide(); $('#batchStep3').show(); btn.hide();
                if(r.success) {
                    $('#batchResultContent').html('<i class="fas fa-check-circle fa-3x text-success mb-3"></i><p class="fw-bold text-success">'+r.message+'</p><button class="btn btn-sm btn-outline-info mt-2 nfe-goto-sec" data-sec="fila"><i class="fas fa-layer-group me-1"></i> Ver Fila</button>');
                } else {
                    $('#batchResultContent').html('<i class="fas fa-times-circle fa-3x text-danger mb-3"></i><p class="fw-bold text-danger">'+(r.message||'Erro')+'</p>');
                }
            }, 500);
        }).fail(function(){
            $('#batchStep2').hide(); $('#batchStep3').show(); btn.hide();
            $('#batchResultContent').html('<i class="fas fa-times-circle fa-3x text-danger mb-3"></i><p class="fw-bold text-danger">Falha na comunicação.</p>');
        });
    });

    // ══════════════════════════════════════
    // Cancelamento
    // ══════════════════════════════════════
    $(document).on('click', '.btn-cancel-nfe', function(){
        $('#cancelNfeId').val($(this).data('id')); $('#cancelNfeNum').text($(this).data('numero'));
        $('#cancelMotivo').val(''); $('#cancelChars').text('0');
        new bootstrap.Modal('#modalCancelNfe').show();
    });
    $('#cancelMotivo').on('input', function(){ $('#cancelChars').text($(this).val().length); });
    $('#btnConfirmCancel').on('click', function(){
        var btn=$(this), motivo=$('#cancelMotivo').val().trim();
        if(motivo.length<15){Swal.fire({icon:'warning',title:'Atenção',text:'Mínimo 15 caracteres.'});return;}
        Swal.fire({icon:'warning',title:'Confirmar Cancelamento?',html:'Ação <strong>irreversível</strong>.',showCancelButton:true,
            confirmButtonText:'Cancelar NF-e',cancelButtonText:'Voltar',confirmButtonColor:'#dc3545'
        }).then(function(r){
            if(!r.isConfirmed) return;
            btn.prop('disabled',true).html('<i class="fas fa-spinner fa-spin me-1"></i> Cancelando...');
            $.ajax({url:'?page=nfe_documents&action=cancel',method:'POST',dataType:'json',
                data:{nfe_id:$('#cancelNfeId').val(),motivo:motivo},headers:{'X-CSRF-TOKEN':csrfToken}
            }).done(function(r){
                bootstrap.Modal.getInstance(document.getElementById('modalCancelNfe'))?.hide();
                if(r.success){Swal.fire({icon:'success',title:'Cancelada!',text:r.message,timer:2000,timerProgressBar:true}).then(function(){location.reload();});}
                else{Swal.fire({icon:'error',title:'Erro',text:r.message});}
            }).fail(function(){Swal.fire({icon:'error',title:'Erro',text:'Falha na comunicação.'});
            }).always(function(){btn.prop('disabled',false).html('<i class="fas fa-ban me-1"></i> Confirmar');});
        });
    });

    // ══════════════════════════════════════
    // Carta de Correção
    // ══════════════════════════════════════
    $(document).on('click', '.btn-correcao-nfe', function(){
        $('#correcaoNfeId').val($(this).data('id')); $('#correcaoNfeNum').text($(this).data('numero'));
        $('#correcaoTexto').val(''); $('#correcaoChars').text('0');
        new bootstrap.Modal('#modalCorrecaoNfe').show();
    });
    $('#correcaoTexto').on('input', function(){ $('#correcaoChars').text($(this).val().length); });
    $('#btnConfirmCorrecao').on('click', function(){
        var btn=$(this), texto=$('#correcaoTexto').val().trim();
        if(texto.length<15){Swal.fire({icon:'warning',title:'Atenção',text:'Mín. 15 caracteres.'});return;}
        btn.prop('disabled',true).html('<i class="fas fa-spinner fa-spin me-1"></i> Enviando...');
        $.ajax({url:'?page=nfe_documents&action=correction',method:'POST',dataType:'json',
            data:{nfe_id:$('#correcaoNfeId').val(),texto:texto},headers:{'X-CSRF-TOKEN':csrfToken}
        }).done(function(r){
            bootstrap.Modal.getInstance(document.getElementById('modalCorrecaoNfe'))?.hide();
            if(r.success){Swal.fire({icon:'success',title:'Enviada!',text:r.message,timer:2000,timerProgressBar:true}).then(function(){location.reload();});}
            else{Swal.fire({icon:'error',title:'Erro',text:r.message});}
        }).fail(function(){Swal.fire({icon:'error',title:'Erro',text:'Falha na comunicação.'});
        }).always(function(){btn.prop('disabled',false).html('<i class="fas fa-paper-plane me-1"></i> Enviar');});
    });

    // ══════════════════════════════════════
    // Inutilização
    // ══════════════════════════════════════
    $('#btnConfirmInutilizar').on('click', function(){
        var btn=$(this), ni=parseInt($('#inutNumInicial').val()), nf=parseInt($('#inutNumFinal').val()),
            just=$('#inutJustificativa').val().trim();
        if(!ni||!nf||ni>nf){Swal.fire({icon:'warning',title:'Atenção',text:'Números inválidos.'});return;}
        if(just.length<15){Swal.fire({icon:'warning',title:'Atenção',text:'Justificativa mín. 15 caracteres.'});return;}
        Swal.fire({icon:'warning',title:'Confirmar Inutilização?',
            html:'Inutilizar nº <strong>'+ni+'</strong> a <strong>'+nf+'</strong>. <strong>Irreversível.</strong>',
            showCancelButton:true,confirmButtonText:'Inutilizar',cancelButtonText:'Cancelar',confirmButtonColor:'#343a40'
        }).then(function(r){
            if(!r.isConfirmed) return;
            btn.prop('disabled',true).html('<i class="fas fa-spinner fa-spin me-1"></i> Inutilizando...');
            $.ajax({url:'?page=nfe_documents&action=inutilizar',method:'POST',dataType:'json',
                data:{num_inicial:ni,num_final:nf,modelo:$('#inutModelo').val(),serie:$('#inutSerie').val(),justificativa:just},
                headers:{'X-CSRF-TOKEN':csrfToken}
            }).done(function(r){
                if(r.success){Swal.fire({icon:'success',title:'Inutilizado!',text:r.message,timer:2500,timerProgressBar:true}).then(function(){location.reload();});}
                else{Swal.fire({icon:'error',title:'Erro',text:r.message});}
            }).fail(function(){Swal.fire({icon:'error',title:'Erro',text:'Falha na comunicação.'});
            }).always(function(){btn.prop('disabled',false).html('<i class="fas fa-slash me-1"></i> Inutilizar');});
        });
    });

    // ══════════════════════════════════════
    // Reenvio de NF-e rejeitada (Fase 3)
    // ══════════════════════════════════════
    $(document).on('click', '.btn-retry-nfe', function(){
        var btn=$(this), id=btn.data('id'), numero=btn.data('numero');
        Swal.fire({
            icon: 'question',
            title: 'Reenviar NF-e?',
            html: 'Deseja reenviar a NF-e <strong>#'+numero+'</strong> (rejeitada)?<br>Um novo número será gerado automaticamente.',
            showCancelButton: true,
            confirmButtonText: 'Sim, reenviar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#fd7e14'
        }).then(function(result){
            if(!result.isConfirmed) return;
            btn.prop('disabled',true).html('<i class="fas fa-spinner fa-spin"></i>');
            $.ajax({
                url: '?page=nfe_documents&action=retry',
                method: 'POST',
                dataType: 'json',
                data: { nfe_id: id },
                headers: {'X-CSRF-TOKEN': csrfToken}
            }).done(function(r){
                if(r.success){
                    Swal.fire({icon:'success',title:'Reenviada!',text:r.message||'NF-e reenviada com sucesso.',timer:2500,timerProgressBar:true}).then(function(){location.reload();});
                } else {
                    Swal.fire({icon:'error',title:'Erro',text:r.message||'Erro ao reenviar NF-e.'});
                }
            }).fail(function(){
                Swal.fire({icon:'error',title:'Erro',text:'Falha na comunicação com o servidor.'});
            }).always(function(){
                btn.prop('disabled',false).html('<i class="fas fa-redo"></i>');
            });
        });
    });

    // ══════════════════════════════════════
    // FASE 5 — Contingência
    // ══════════════════════════════════════
    function loadContingencyStatus() {
        $.getJSON('?page=nfe_documents&action=contingencyStatus', function(r){
            if (r.success && r.data) {
                var d = r.data;
                var isActive = d.tp_emis && d.tp_emis > 1;
                var statusHtml = isActive
                    ? '<i class="fas fa-exclamation-triangle fa-2x text-warning mb-2"></i><h5 class="text-warning mb-1">Contingência ATIVA</h5>' +
                      '<p class="small mb-1">Tipo: <strong>tp_emis=' + d.tp_emis + '</strong></p>' +
                      (d.contingencia_ativada_em ? '<p class="small text-muted mb-1">Desde: ' + d.contingencia_ativada_em + '</p>' : '') +
                      (d.contingencia_justificativa ? '<p class="small text-muted mb-0">"' + d.contingencia_justificativa + '"</p>' : '')
                    : '<i class="fas fa-check-circle fa-2x text-success mb-2"></i><h5 class="text-success mb-1">Emissão Normal</h5>' +
                      '<p class="small text-muted mb-0">tp_emis=1 — Nenhuma contingência ativa.</p>';
                $('#contingencyStatusContent').html(statusHtml);
            }
        });
    }

    function loadContingencyHistory() {
        $.getJSON('?page=nfe_documents&action=contingencyHistory', function(r){
            if (r.success && r.data && r.data.length) {
                var html = '<div class="table-responsive"><table class="table table-sm table-hover mb-0" style="font-size:.8rem;">' +
                    '<thead class="table-light"><tr><th>Data</th><th>Tipo</th><th>tp_emis</th><th>Justificativa</th><th class="text-center">Pendentes</th><th class="text-center">Sincronizadas</th></tr></thead><tbody>';
                r.data.forEach(function(h){
                    var tipoColor = h.tipo === 'ativacao' ? 'warning' : (h.tipo === 'desativacao' ? 'success' : 'info');
                    html += '<tr><td><small>' + (h.created_at || '') + '</small></td>' +
                        '<td><span class="badge bg-' + tipoColor + '" style="font-size:.65rem;">' + (h.tipo||'') + '</span></td>' +
                        '<td>' + (h.tp_emis_anterior||'') + ' → ' + (h.tp_emis_novo||'') + '</td>' +
                        '<td><small>' + (h.justificativa||'—') + '</small></td>' +
                        '<td class="text-center">' + (h.nfes_pendentes||0) + '</td>' +
                        '<td class="text-center">' + (h.nfes_sincronizadas||0) + '</td></tr>';
                });
                html += '</tbody></table></div>';
                $('#contingencyHistoryContent').html(html);
            } else {
                $('#contingencyHistoryContent').html('<div class="text-center py-4 text-muted small"><i class="fas fa-info-circle me-1"></i>Nenhum registro de contingência.</div>');
            }
        });
    }

    // Carregar dados de contingência quando a seção é exibida
    $(document).on('click', '.nfe-nav-item[data-sec="contingencia"]', function(){
        loadContingencyStatus();
        loadContingencyHistory();
    });

    // Ativar contingência
    $('#btnContingencyActivate').on('click', function(){
        var just = $('#contingencyJustificativa').val().trim();
        if (just.length < 15) { Swal.fire({icon:'warning',title:'Atenção',text:'Justificativa mín. 15 caracteres.'}); return; }
        var tpEmis = parseInt($('#contingencyTpEmis').val());
        var btn = $(this);
        Swal.fire({
            icon:'warning', title:'Ativar Contingência?',
            html:'Tipo <strong>tp_emis=' + tpEmis + '</strong>.<br>Todas as NF-e serão emitidas em contingência.',
            showCancelButton:true, confirmButtonText:'Ativar', cancelButtonText:'Cancelar', confirmButtonColor:'#ff9800'
        }).then(function(result){
            if (!result.isConfirmed) return;
            btn.prop('disabled',true).html('<i class="fas fa-spinner fa-spin me-1"></i>Ativando...');
            $.ajax({
                url:'?page=nfe_documents&action=contingencyActivate', method:'POST', dataType:'json',
                data:{justificativa:just, tp_emis:tpEmis}, headers:{'X-CSRF-TOKEN':csrfToken}
            }).done(function(r){
                if(r.success){ Swal.fire({icon:'success',title:'Ativada!',text:r.message,timer:2000,timerProgressBar:true}); loadContingencyStatus(); loadContingencyHistory(); }
                else { Swal.fire({icon:'error',title:'Erro',text:r.message}); }
            }).fail(function(){ Swal.fire({icon:'error',title:'Erro',text:'Falha na comunicação.'}); })
            .always(function(){ btn.prop('disabled',false).html('<i class="fas fa-toggle-on me-1"></i> Ativar'); });
        });
    });

    // Desativar contingência
    $('#btnContingencyDeactivate').on('click', function(){
        var btn = $(this);
        Swal.fire({
            icon:'question', title:'Desativar Contingência?', text:'Emissão voltará ao modo normal.',
            showCancelButton:true, confirmButtonText:'Desativar', cancelButtonText:'Cancelar', confirmButtonColor:'#28a745'
        }).then(function(result){
            if (!result.isConfirmed) return;
            btn.prop('disabled',true).html('<i class="fas fa-spinner fa-spin me-1"></i>Desativando...');
            $.ajax({
                url:'?page=nfe_documents&action=contingencyDeactivate', method:'POST', dataType:'json',
                headers:{'X-CSRF-TOKEN':csrfToken}
            }).done(function(r){
                if(r.success){ Swal.fire({icon:'success',title:'Desativada!',text:r.message+(r.pending?' Pendentes: '+r.pending:''),timer:2500,timerProgressBar:true}); loadContingencyStatus(); loadContingencyHistory(); }
                else { Swal.fire({icon:'error',title:'Erro',text:r.message}); }
            }).fail(function(){ Swal.fire({icon:'error',title:'Erro',text:'Falha na comunicação.'}); })
            .always(function(){ btn.prop('disabled',false).html('<i class="fas fa-toggle-off me-1"></i> Desativar'); });
        });
    });

    // Sincronizar contingência
    $('#btnContingencySync').on('click', function(){
        var btn = $(this);
        btn.prop('disabled',true).html('<i class="fas fa-spinner fa-spin me-1"></i>Sincronizando...');
        $.ajax({
            url:'?page=nfe_documents&action=contingencySync', method:'POST', dataType:'json',
            headers:{'X-CSRF-TOKEN':csrfToken}
        }).done(function(r){
            Swal.fire({icon:r.success?'success':'error', title:r.success?'Sincronizado!':'Erro',
                html: r.synced !== undefined ? 'OK: <strong>'+r.synced+'</strong>, Falha: <strong>'+r.failed+'</strong>, Restantes: <strong>'+r.remaining+'</strong>' : (r.message||'')
            });
            loadContingencyStatus(); loadContingencyHistory();
        }).fail(function(){ Swal.fire({icon:'error',title:'Erro',text:'Falha na comunicação.'}); })
        .always(function(){ btn.prop('disabled',false).html('<i class="fas fa-sync me-1"></i> Sincronizar'); });
    });

    // ══════════════════════════════════════
    // FASE 5 — SPED / SINTEGRA / Download Lote
    // ══════════════════════════════════════
    $('#btnExportSped').on('click', function(e){
        e.preventDefault();
        var sd = $('#spedStartDate').val(), ed = $('#spedEndDate').val();
        if (!sd || !ed) { Swal.fire({icon:'warning',title:'Atenção',text:'Preencha o período.'}); return; }
        window.location.href = '?page=nfe_documents&action=exportSped&start_date='+encodeURIComponent(sd)+'&end_date='+encodeURIComponent(ed);
    });

    $('#btnExportSintegra').on('click', function(e){
        e.preventDefault();
        var sd = $('#sintegraStartDate').val(), ed = $('#sintegraEndDate').val();
        if (!sd || !ed) { Swal.fire({icon:'warning',title:'Atenção',text:'Preencha o período.'}); return; }
        window.location.href = '?page=nfe_documents&action=exportSintegra&start_date='+encodeURIComponent(sd)+'&end_date='+encodeURIComponent(ed);
    });

    $('#btnDownloadBatch').on('click', function(e){
        e.preventDefault();
        var sd = $('#batchStartDate').val(), ed = $('#batchEndDate').val();
        if (!sd || !ed) { Swal.fire({icon:'warning',title:'Atenção',text:'Preencha o período.'}); return; }
        window.location.href = '?page=nfe_documents&action=downloadBatch&start_date='+encodeURIComponent(sd)+'&end_date='+encodeURIComponent(ed);
    });
});
</script>

<?php include __DIR__ . '/partials/toast_notifications.php'; ?>
