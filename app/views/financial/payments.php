<?php
/**
 * Financeiro — Página Unificada com Sidebar
 * Layout inspirado na página de estoque: sidebar com seções à esquerda,
 * conteúdo da seção ativa à direita.
 *
 * Seções:
 *   - payments     → Pagamentos (parcelas) — PRINCIPAL
 *   - transactions → Visão Geral (entradas/saídas)
 *   - import       → Importação (OFX/CSV/Excel)
 *   - new          → Nova Transação
 *
 * Variáveis disponíveis (carregadas pelo FinancialController::payments):
 *   $summary, $categories, $company, $companyAddress
 *   $overdueCount, $pendingConfirmCount
 *
 * Tabelas são carregadas via AJAX com filtros dinâmicos e paginação.
 */

$activeSection = $_GET['section'] ?? 'payments';
$validSections = ['payments', 'transactions', 'import', 'new'];
if (!in_array($activeSection, $validSections)) $activeSection = 'payments';

$canUseBoletoModule = \Akti\Core\ModuleBootloader::isModuleEnabled('boleto');

$statusMap = [
    'pendente'  => ['badge' => 'bg-warning text-dark', 'icon' => 'fas fa-clock',                'label' => 'Pendente'],
    'pago'      => ['badge' => 'bg-success',            'icon' => 'fas fa-check-circle',         'label' => 'Pago'],
    'atrasado'  => ['badge' => 'bg-danger',             'icon' => 'fas fa-exclamation-triangle',  'label' => 'Atrasado'],
    'cancelado' => ['badge' => 'bg-secondary',          'icon' => 'fas fa-ban',                  'label' => 'Cancelado'],
];

$methodLabels = [
    'dinheiro'       => '💵 Dinheiro',
    'pix'            => '📱 PIX',
    'cartao_credito' => '💳 Crédito',
    'cartao_debito'  => '💳 Débito',
    'boleto'         => '📄 Boleto',
    'transferencia'  => '🏦 Transf.',
    'gateway'        => '🌐 Gateway Online',
];

$allCats = array_merge($categories['entrada'] ?? [], $categories['saida'] ?? [], \Akti\Models\Financial::getInternalCategories());
?>

<!-- ══════ Flash messages ══════ -->
<?php if (!empty($_SESSION['flash_error'])): ?>
<script>document.addEventListener('DOMContentLoaded',()=>Swal.fire({icon:'error',title:'Erro',html:'<?= addslashes($_SESSION['flash_error']) ?>',confirmButtonColor:'#3498db'}));</script>
<?php unset($_SESSION['flash_error']); endif; ?>
<?php if (!empty($_SESSION['flash_success'])): ?>
<script>document.addEventListener('DOMContentLoaded',()=>Swal.mixin({toast:true,position:'top-end',showConfirmButton:false,timer:2500,timerProgressBar:true}).fire({icon:'success',title:'<?= addslashes($_SESSION['flash_success']) ?>'}));</script>
<?php unset($_SESSION['flash_success']); endif; ?>

<style>
    /* ── Sidebar nav ── */
    .fin-sidebar .fin-nav-item{display:flex;align-items:center;gap:.75rem;padding:.7rem 1rem;border-radius:10px;text-decoration:none;color:#555;font-size:.82rem;font-weight:500;transition:all .15s ease;margin-bottom:2px;border:1px solid transparent;cursor:pointer}
    .fin-sidebar .fin-nav-item:hover{background:#f1f5f9;color:#333}
    .fin-sidebar .fin-nav-item.active{background:var(--bs-primary,#3498db);color:#fff;box-shadow:0 2px 8px rgba(52,152,219,.3)}
    .fin-sidebar .fin-nav-item.active .fin-nav-icon{background:rgba(255,255,255,.2) !important;color:#fff !important}
    .fin-sidebar .fin-nav-item.active .fin-nav-count{background:rgba(255,255,255,.25) !important;color:#fff !important}
    .fin-nav-icon{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:.8rem;flex-shrink:0;transition:all .15s ease}
    .fin-nav-count{font-size:.65rem;padding:2px 7px;border-radius:10px;font-weight:600;margin-left:auto}
    .fin-sidebar-label{font-size:.65rem;text-transform:uppercase;letter-spacing:.8px;color:#aaa;font-weight:700;padding:0 1rem;margin-bottom:.3rem;margin-top:.6rem}
    .fin-sidebar-divider{height:1px;background:#e9ecef;margin:.5rem 1rem}

    /* ── Section transition ── */
    .fin-section{display:none;animation:finFadeIn .25s ease}
    .fin-section.active{display:block}
    @keyframes finFadeIn{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:translateY(0)}}

    /* ── Mobile sidebar ── */
    @media(max-width:991.98px){
        .fin-sidebar-col{margin-bottom:1rem}
        .fin-sidebar{display:flex;gap:.4rem;overflow-x:auto;padding-bottom:.5rem;scrollbar-width:thin}
        .fin-sidebar .fin-nav-item{white-space:nowrap;flex-shrink:0;padding:.5rem .85rem;font-size:.75rem}
        .fin-sidebar-label{display:none}
        .fin-sidebar-divider{display:none}
    }

    /* ── Pagination style ── */
    .fin-pagination{display:flex;align-items:center;justify-content:center;gap:.5rem;margin-top:1rem}
    .fin-pagination .btn{min-width:36px;font-size:.78rem}
    .fin-pagination .page-info{font-size:.75rem;color:#888}

    /* ── Import styles (dropzone + stepper) ── */
    .import-dropzone{border:2px dashed #ccc;border-radius:12px;padding:2.5rem 1.5rem;text-align:center;transition:all .2s ease;cursor:pointer;background:#fafbfc}
    .import-dropzone:hover,.import-dropzone.dragover{border-color:#17a2b8;background:rgba(23,162,184,.05)}
    .import-dropzone.has-file{border-color:#27ae60;background:rgba(39,174,96,.05)}
    .import-step{display:none}
    .import-step.active{display:block}
    .mapping-select{font-size:.78rem;padding:.25rem .5rem}
    .preview-table{font-size:.72rem;max-height:350px;overflow:auto}
    .preview-table th{position:sticky;top:0;z-index:2;background:#e9ecef}
    .preview-table td{white-space:nowrap;max-width:200px;overflow:hidden;text-overflow:ellipsis}
</style>

<div class="container-fluid py-3">

    <!-- ══════ Header ══════ -->
    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div>
            <h1 class="h2 mb-1"><i class="fas fa-file-invoice-dollar me-2 text-primary"></i>Financeiro</h1>
            <p class="text-muted mb-0" style="font-size:.82rem;">Pagamentos, entradas/saídas, importação e transações.</p>
        </div>
    </div>

    <div class="row g-4">

        <!-- ═══════════════════════════════════════════════ -->
        <!-- SIDEBAR — Menu Lateral de Seções (3/12)         -->
        <!-- ═══════════════════════════════════════════════ -->
        <div class="col-lg-3 fin-sidebar-col">
            <div class="card border-0 shadow-sm" style="border-radius:12px;">
                <div class="card-body p-3">
                    <nav class="fin-sidebar">

                        <div class="fin-sidebar-label">Financeiro</div>

                        <a href="#" class="fin-nav-item <?= $activeSection === 'payments' ? 'active' : '' ?>" data-section="payments">
                            <span class="fin-nav-icon" style="background:rgba(52,152,219,.1);color:#3498db;">
                                <i class="fas fa-file-invoice-dollar"></i>
                            </span>
                            <span>Pagamentos</span>
                            <?php if ($overdueCount > 0): ?>
                            <span class="fin-nav-count" style="background:rgba(231,76,60,.15);color:#e74c3c;"><?= $overdueCount ?></span>
                            <?php endif; ?>
                        </a>

                        <a href="#" class="fin-nav-item <?= $activeSection === 'transactions' ? 'active' : '' ?>" data-section="transactions">
                            <span class="fin-nav-icon" style="background:rgba(39,174,96,.1);color:#27ae60;">
                                <i class="fas fa-exchange-alt"></i>
                            </span>
                            <span>Visão Geral</span>
                        </a>

                        <div class="fin-sidebar-divider"></div>

                        <a href="#" class="fin-nav-item <?= $activeSection === 'import' ? 'active' : '' ?>" data-section="import">
                            <span class="fin-nav-icon" style="background:rgba(23,162,184,.1);color:#17a2b8;">
                                <i class="fas fa-file-import"></i>
                            </span>
                            <span>Importação</span>
                        </a>

                        <a href="#" class="fin-nav-item <?= $activeSection === 'new' ? 'active' : '' ?>" data-section="new">
                            <span class="fin-nav-icon" style="background:rgba(155,89,182,.1);color:#9b59b6;">
                                <i class="fas fa-plus-circle"></i>
                            </span>
                            <span>Nova Transação</span>
                        </a>

                    </nav>
                </div>
            </div>

            <!-- Mini-dica -->
            <div class="card border-0 shadow-sm mt-3 d-none d-lg-block" style="border-radius:12px;">
                <div class="card-body p-3">
                    <h6 class="mb-2 fw-bold" style="font-size:.78rem;color:#17a2b8;">
                        <i class="fas fa-lightbulb me-1"></i>Dica
                    </h6>
                    <p class="mb-0 text-muted" style="font-size:.72rem;line-height:1.55;">
                        Use <span class="fw-bold text-primary">Pagamentos</span> para gerenciar parcelas de pedidos,
                        <span class="fw-bold text-success">Visão Geral</span> para entradas e saídas
                        e <span class="fw-bold" style="color:#17a2b8;">Importação</span> para arquivos OFX/CSV.
                    </p>
                </div>
            </div>

            <!-- Alertas -->
            <?php if ($pendingConfirmCount > 0): ?>
            <div class="card border-0 shadow-sm mt-3 border-start border-warning border-4" style="border-radius:12px;">
                <div class="card-body p-3">
                    <h6 class="mb-1 fw-bold text-warning" style="font-size:.78rem;">
                        <i class="fas fa-user-clock me-1"></i>Aguardando Confirmação
                    </h6>
                    <p class="mb-0 text-muted" style="font-size:.72rem;">
                        <strong><?= $pendingConfirmCount ?></strong> pagamento(s) pendente(s) de confirmação.
                    </p>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- ═══════════════════════════════════════════════ -->
        <!-- CONTEÚDO PRINCIPAL — Seção Ativa (9/12)         -->
        <!-- ═══════════════════════════════════════════════ -->
        <div class="col-lg-9">

            <!-- ══════════════════════════════════════ -->
            <!-- SEÇÃO: Pagamentos (Parcelas) — PRINCIPAL -->
            <!-- ══════════════════════════════════════ -->
            <div class="fin-section <?= $activeSection === 'payments' ? 'active' : '' ?>" id="fin-payments">

                <div class="d-flex align-items-center mb-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-2" style="width:34px;height:34px;background:rgba(52,152,219,.1);">
                        <i class="fas fa-file-invoice-dollar" style="color:#3498db;font-size:.85rem;"></i>
                    </div>
                    <div>
                        <h5 class="mb-0" style="font-size:1rem;">Pagamentos</h5>
                        <p class="text-muted mb-0" style="font-size:.72rem;">Parcelas de pedidos nas etapas Financeiro e Concluído.</p>
                    </div>
                </div>

                <!-- Cards de Resumo (preenchidos via AJAX) -->
                <div class="row g-3 mb-4" id="paymentsSummaryCards">
                    <div class="col-xl-3 col-md-6">
                        <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
                            <div class="card-body d-flex align-items-center p-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:42px;height:42px;background:rgba(52,152,219,0.15);">
                                    <i class="fas fa-list-ol text-primary"></i>
                                </div>
                                <div>
                                    <div class="text-muted small text-uppercase fw-bold" style="font-size:.65rem;">Total</div>
                                    <div class="fw-bold fs-5" id="cardPayTotal">—</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card border-0 shadow-sm h-100 border-start border-warning border-4">
                            <div class="card-body d-flex align-items-center p-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:42px;height:42px;background:rgba(243,156,18,0.15);">
                                    <i class="fas fa-clock text-warning"></i>
                                </div>
                                <div>
                                    <div class="text-muted small text-uppercase fw-bold" style="font-size:.65rem;">Pendentes</div>
                                    <div class="fw-bold fs-5" id="cardPayPending">—</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
                            <div class="card-body d-flex align-items-center p-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:42px;height:42px;background:rgba(39,174,96,0.15);">
                                    <i class="fas fa-check-circle text-success"></i>
                                </div>
                                <div>
                                    <div class="text-muted small text-uppercase fw-bold" style="font-size:.65rem;">Pagas</div>
                                    <div class="fw-bold fs-5" id="cardPayPaid">—</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card border-0 shadow-sm h-100 border-start border-info border-4">
                            <div class="card-body d-flex align-items-center p-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:42px;height:42px;background:rgba(23,162,184,0.15);">
                                    <i class="fas fa-user-clock text-info"></i>
                                </div>
                                <div>
                                    <div class="text-muted small text-uppercase fw-bold" style="font-size:.65rem;">Aguardando</div>
                                    <div class="fw-bold fs-5" id="cardPayAwaiting">—</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtros Dinâmicos -->
                <div class="row g-2 mb-3 align-items-end">
                    <div class="col-auto">
                        <label class="form-label small fw-bold mb-1">Status</label>
                        <select id="fPayStatus" class="form-select form-select-sm" style="width:170px">
                            <option value="">Todos</option>
                            <option value="pendente">Pendentes/Atrasadas</option>
                            <option value="pago">Pagas</option>
                            <option value="atrasado">Atrasadas</option>
                            <option value="aguardando">Aguardando Confirm.</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <label class="form-label small fw-bold mb-1">Mês</label>
                        <select id="fPayMonth" class="form-select form-select-sm" style="width:120px">
                            <option value="">Todos</option>
                            <?php $mn=['','Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez']; for($m=1;$m<=12;$m++): ?>
                            <option value="<?= $m ?>"><?= $mn[$m] ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-auto">
                        <label class="form-label small fw-bold mb-1">Ano</label>
                        <select id="fPayYear" class="form-select form-select-sm" style="width:100px">
                            <option value="">Todos</option>
                            <?php for($y=date('Y')-2;$y<=date('Y')+1;$y++): ?>
                            <option value="<?= $y ?>"><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" class="form-control" id="fPaySearch" placeholder="Buscar pedido, cliente..." autocomplete="off">
                        </div>
                    </div>
                </div>

                <!-- Tabela de Parcelas -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-list me-2"></i>Parcelas</h6>
                        <span class="badge bg-secondary" id="payTotalBadge">—</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="py-3 ps-3">Pedido</th>
                                        <th class="py-3">Cliente</th>
                                        <th class="py-3">Parcela</th>
                                        <th class="py-3">Vencimento</th>
                                        <th class="py-3">Valor</th>
                                        <th class="py-3">Pago em</th>
                                        <th class="py-3">Valor Pago</th>
                                        <th class="py-3">Status</th>
                                        <th class="py-3">NF-e</th>
                                        <th class="py-3 text-end pe-3">Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="paymentsTableBody">
                                    <tr><td colspan="10" class="text-center text-muted py-5">
                                        <i class="fas fa-spinner fa-spin fa-2x mb-2 d-block opacity-50"></i>Carregando...
                                    </td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="fin-pagination" id="paymentsPagination"></div>
            </div>

            <!-- ══════════════════════════════════════ -->
            <!-- SEÇÃO: Visão Geral (Entradas/Saídas)    -->
            <!-- ══════════════════════════════════════ -->
            <div class="fin-section <?= $activeSection === 'transactions' ? 'active' : '' ?>" id="fin-transactions">

                <div class="d-flex align-items-center mb-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-2" style="width:34px;height:34px;background:rgba(39,174,96,.1);">
                        <i class="fas fa-exchange-alt" style="color:#27ae60;font-size:.85rem;"></i>
                    </div>
                    <div>
                        <h5 class="mb-0" style="font-size:1rem;">Visão Geral — Entradas e Saídas</h5>
                        <p class="text-muted mb-0" style="font-size:.72rem;">Todas as movimentações financeiras do sistema.</p>
                    </div>
                </div>

                <!-- Cards de Resumo (via AJAX) -->
                <div class="row g-3 mb-4" id="txSummaryCards">
                    <div class="col-xl-4 col-md-4">
                        <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
                            <div class="card-body d-flex align-items-center p-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:44px;height:44px;background:rgba(39,174,96,0.15);">
                                    <i class="fas fa-arrow-down fa-lg text-success"></i>
                                </div>
                                <div>
                                    <div class="text-muted small text-uppercase" style="font-size:.65rem;">Entradas</div>
                                    <div class="fw-bold fs-5 text-success" id="cardTxEntradas">R$ —</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-4 col-md-4">
                        <div class="card border-0 shadow-sm h-100 border-start border-danger border-4">
                            <div class="card-body d-flex align-items-center p-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:44px;height:44px;background:rgba(192,57,43,0.15);">
                                    <i class="fas fa-arrow-up fa-lg text-danger"></i>
                                </div>
                                <div>
                                    <div class="text-muted small text-uppercase" style="font-size:.65rem;">Saídas</div>
                                    <div class="fw-bold fs-5 text-danger" id="cardTxSaidas">R$ —</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-4 col-md-4">
                        <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
                            <div class="card-body d-flex align-items-center p-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:44px;height:44px;background:rgba(52,152,219,0.15);">
                                    <i class="fas fa-balance-scale fa-lg text-primary"></i>
                                </div>
                                <div>
                                    <div class="text-muted small text-uppercase" style="font-size:.65rem;">Saldo</div>
                                    <div class="fw-bold fs-5" id="cardTxSaldo">R$ —</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtros Dinâmicos -->
                <div class="row g-2 mb-3 align-items-end">
                    <div class="col-auto">
                        <label class="form-label small fw-bold mb-1">Tipo</label>
                        <select id="fTxType" class="form-select form-select-sm" style="width:140px">
                            <option value="">Todos</option>
                            <option value="entrada">Entradas</option>
                            <option value="saida">Saídas</option>
                            <option value="registro">Registros</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <label class="form-label small fw-bold mb-1">Categoria</label>
                        <select id="fTxCategory" class="form-select form-select-sm" style="width:180px">
                            <option value="">Todas</option>
                            <optgroup label="Entradas">
                                <?php foreach ($categories['entrada'] ?? [] as $k => $v): ?>
                                <option value="<?= $k ?>"><?= $v ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="Saídas">
                                <?php foreach ($categories['saida'] ?? [] as $k => $v): ?>
                                <option value="<?= $k ?>"><?= $v ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                        </select>
                    </div>
                    <div class="col-auto">
                        <label class="form-label small fw-bold mb-1">Mês</label>
                        <select id="fTxMonth" class="form-select form-select-sm" style="width:120px">
                            <option value="">Todos</option>
                            <?php for($m=1;$m<=12;$m++): ?>
                            <option value="<?= $m ?>"><?= $mn[$m] ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-auto">
                        <label class="form-label small fw-bold mb-1">Ano</label>
                        <select id="fTxYear" class="form-select form-select-sm" style="width:100px">
                            <option value="">Todos</option>
                            <?php for($y=date('Y')-2;$y<=date('Y')+1;$y++): ?>
                            <option value="<?= $y ?>"><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" class="form-control" id="fTxSearch" placeholder="Buscar descrição..." autocomplete="off">
                        </div>
                    </div>
                </div>

                <!-- Tabela de Transações -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-success"><i class="fas fa-exchange-alt me-2"></i>Transações</h6>
                        <span class="badge bg-secondary" id="txTotalBadge">—</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-3 py-3">Data</th>
                                        <th class="py-3">Tipo</th>
                                        <th class="py-3">Categoria</th>
                                        <th class="py-3">Descrição</th>
                                        <th class="py-3">Valor</th>
                                        <th class="py-3">Método</th>
                                        <th class="py-3 text-end pe-3">Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="txTableBody">
                                    <tr><td colspan="7" class="text-center text-muted py-5">
                                        <i class="fas fa-spinner fa-spin fa-2x mb-2 d-block opacity-50"></i>Carregando...
                                    </td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="fin-pagination" id="txPagination"></div>
            </div>

            <!-- ══════════════════════════════════════ -->
            <!-- SEÇÃO: Importação OFX/CSV/Excel         -->
            <!-- ══════════════════════════════════════ -->
            <div class="fin-section <?= $activeSection === 'import' ? 'active' : '' ?>" id="fin-import">

                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle d-flex align-items-center justify-content-center me-2" style="width:34px;height:34px;background:rgba(23,162,184,.1);">
                            <i class="fas fa-file-import" style="color:#17a2b8;font-size:.85rem;"></i>
                        </div>
                        <div>
                            <h5 class="mb-0" style="font-size:1rem;">Importação de Arquivos</h5>
                            <p class="text-muted mb-0" style="font-size:.72rem;">Importe extratos bancários (OFX) ou planilhas (CSV/Excel) com mapeamento dinâmico de colunas.</p>
                        </div>
                    </div>
                </div>

                <!-- ── Stepper visual ── -->
                <div class="d-flex align-items-center mb-4 gap-2" id="importStepper">
                    <div class="import-step-indicator active" data-step="1">
                        <span class="badge bg-primary rounded-pill px-3 py-2"><i class="fas fa-upload me-1"></i>1. Upload</span>
                    </div>
                    <i class="fas fa-chevron-right text-muted small"></i>
                    <div class="import-step-indicator" data-step="2">
                        <span class="badge bg-secondary rounded-pill px-3 py-2"><i class="fas fa-columns me-1"></i>2. Mapeamento</span>
                    </div>
                    <i class="fas fa-chevron-right text-muted small"></i>
                    <div class="import-step-indicator" data-step="3">
                        <span class="badge bg-secondary rounded-pill px-3 py-2"><i class="fas fa-check-circle me-1"></i>3. Resultado</span>
                    </div>
                </div>

                <!-- ══ Step 1: Upload do Arquivo ══ -->
                <div class="import-step active" id="importStep1">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">

                            <div class="import-dropzone" id="importDropzone">
                                <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                <h6 class="mb-1">Arraste o arquivo aqui</h6>
                                <p class="text-muted small mb-2">ou clique para selecionar</p>
                                <input type="file" id="importFileInput" accept=".ofx,.ofc,.csv,.txt,.xls,.xlsx" style="display:none;">
                                <p class="text-muted mb-0" style="font-size:.7rem;">Formatos aceitos: <strong>OFX</strong>, <strong>CSV</strong>, <strong>TXT</strong>, <strong>XLS</strong>, <strong>XLSX</strong></p>
                            </div>

                            <div id="importFileInfo" style="display:none;" class="mt-3">
                                <div class="alert alert-success d-flex align-items-center py-2 mb-0">
                                    <i class="fas fa-file-circle-check fa-lg me-3 text-success"></i>
                                    <div class="flex-grow-1">
                                        <strong id="importFileName">arquivo.csv</strong>
                                        <span class="text-muted small ms-2" id="importFileSize"></span>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-danger ms-2" id="btnRemoveFile">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="row g-3 mt-3 align-items-end">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Modo de importação</label>
                                    <select id="importMode" class="form-select form-select-sm">
                                        <option value="registro">📋 Apenas Registro (não contabiliza)</option>
                                        <option value="contabilizar">✅ Contabilizar (entradas/saídas no caixa)</option>
                                    </select>
                                </div>
                                <div class="col-md-6 text-end">
                                    <button type="button" class="btn btn-info text-white" id="btnParseFile" disabled>
                                        <i class="fas fa-cog me-1"></i>Analisar Arquivo
                                    </button>
                                </div>
                            </div>

                            <div class="alert alert-light border small mt-3 mb-0">
                                <i class="fas fa-info-circle text-info me-1"></i>
                                <strong>Registro:</strong> apenas para consulta (não altera saldo).
                                <strong>Contabilizar:</strong> créditos como entrada e débitos como saída no caixa.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ══ Step 2: Preview / Mapeamento ══ -->
                <div class="import-step" id="importStep2">

                    <!-- CSV Column Mapping Table (only for CSV/TXT/XLS/XLSX) -->
                    <div class="card border-0 shadow-sm mb-3 d-none" id="csvMappingSection">
                        <div class="card-header bg-white py-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 fw-bold"><i class="fas fa-columns me-2 text-primary"></i>Mapeamento de Colunas</h6>
                                <div>
                                    <span class="badge bg-info text-white me-1" id="importFileType">—</span>
                                    <span class="badge bg-secondary" id="totalRowsBadge">0 linhas</span>
                                </div>
                            </div>
                            <p class="text-muted mb-0 mt-1" style="font-size:.72rem;">Selecione a qual campo financeiro cada coluna do arquivo corresponde. Colunas sem mapeamento serão ignoradas.</p>
                        </div>
                        <div class="card-body p-3">
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm align-middle mb-0" id="finMappingTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width:40px;" class="text-center">
                                                <input type="checkbox" class="form-check-input" id="finCheckAllCols" checked title="Marcar/desmarcar todas">
                                            </th>
                                            <th>Coluna do Arquivo</th>
                                            <th>Amostra de Dados</th>
                                            <th style="width:220px;">Corresponde a</th>
                                        </tr>
                                    </thead>
                                    <tbody id="finMappingTableBody">
                                    </tbody>
                                </table>
                            </div>

                            <!-- Mapping validation messages -->
                            <div id="mappingValidation" class="mt-3" style="display:none;"></div>
                        </div>
                    </div>

                    <!-- Preview table + row selection -->
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-white py-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 fw-bold"><i class="fas fa-table me-2 text-info"></i>Pré-visualização</h6>
                                <div>
                                    <span class="badge bg-info text-white me-2" id="importFileTypeBadge">—</span>
                                    <span class="badge bg-secondary" id="importRowCount">0 linhas</span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <!-- Row selection controls -->
                            <div class="p-3 border-bottom">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div>
                                        <button class="btn btn-sm btn-outline-primary me-1" id="btnSelectAll">
                                            <i class="fas fa-check-square me-1"></i>Selecionar Todas
                                        </button>
                                        <button class="btn btn-sm btn-outline-secondary me-1" id="btnDeselectAll">
                                            <i class="far fa-square me-1"></i>Desmarcar Todas
                                        </button>
                                        <span class="text-muted small ms-2"><strong id="selectedCount">0</strong> selecionada(s)</span>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="skipFirstRow" checked>
                                        <label class="form-check-label small" for="skipFirstRow">Pular 1ª linha (cabeçalho)</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Preview table -->
                            <div class="preview-table">
                                <table class="table table-sm table-striped table-bordered table-hover align-middle mb-0" id="importPreviewTable">
                                    <thead class="table-light"><tr id="importPreviewHead"></tr></thead>
                                    <tbody id="importPreviewBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-outline-secondary" id="btnImportBack">
                            <i class="fas fa-arrow-left me-1"></i>Voltar
                        </button>
                        <button type="button" class="btn btn-success btn-lg" id="btnImportConfirm">
                            <i class="fas fa-file-import me-1"></i>Importar <span id="importCountLabel">0</span> Transação(ões)
                        </button>
                    </div>
                </div>

                <!-- ══ Step 3: Resultado ══ -->
                <div class="import-step" id="importStep3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4" id="importResultContent">
                            <!-- Preenchido via JS -->
                        </div>
                    </div>
                    <div class="text-center mt-3">
                        <button type="button" class="btn btn-outline-info" id="btnNewImport">
                            <i class="fas fa-redo me-1"></i>Nova Importação
                        </button>
                        <a href="#" class="btn btn-primary ms-2 fin-go-transactions">
                            <i class="fas fa-exchange-alt me-1"></i>Ver Transações
                        </a>
                    </div>
                </div>

            </div>

            <!-- ══════════════════════════════════════ -->
            <!-- SEÇÃO: Nova Transação                    -->
            <!-- ══════════════════════════════════════ -->
            <div class="fin-section <?= $activeSection === 'new' ? 'active' : '' ?>" id="fin-new">

                <div class="d-flex align-items-center mb-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-2" style="width:34px;height:34px;background:rgba(155,89,182,.1);">
                        <i class="fas fa-plus-circle" style="color:#9b59b6;font-size:.85rem;"></i>
                    </div>
                    <div>
                        <h5 class="mb-0" style="font-size:1rem;">Nova Transação</h5>
                        <p class="text-muted mb-0" style="font-size:.72rem;">Registre uma entrada ou saída manual.</p>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <form id="formNewTransaction" method="post" action="?page=financial&action=addTransaction">
                            <?= csrf_field() ?>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Tipo</label>
                                    <select name="type" id="newTxType" class="form-select" required>
                                        <option value="entrada">✅ Entrada</option>
                                        <option value="saida">🔴 Saída</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Categoria</label>
                                    <select name="category" id="newTxCategory" class="form-select" required>
                                        <?php foreach ($categories['entrada'] ?? [] as $k => $v): ?>
                                        <option value="<?= $k ?>" data-type="entrada"><?= $v ?></option>
                                        <?php endforeach; ?>
                                        <?php foreach ($categories['saida'] ?? [] as $k => $v): ?>
                                        <option value="<?= $k ?>" data-type="saida" style="display:none;"><?= $v ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold">Descrição</label>
                                    <input type="text" name="description" class="form-control" placeholder="Ex: Compra de papel A4" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Valor (R$)</label>
                                    <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Data</label>
                                    <input type="date" name="transaction_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Forma de Pagamento</label>
                                    <select name="payment_method" class="form-select">
                                        <option value="">— Não informado —</option>
                                        <option value="dinheiro">💵 Dinheiro</option>
                                        <option value="pix">📱 PIX</option>
                                        <option value="cartao_credito">💳 Cartão Crédito</option>
                                        <option value="cartao_debito">💳 Cartão Débito</option>
                                        <option value="boleto">📄 Boleto</option>
                                        <option value="transferencia">🏦 Transferência</option>
                                        <option value="gateway">🌐 Gateway Online</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold">Observação <span class="text-muted fw-normal">(opcional)</span></label>
                                    <input type="text" name="notes" class="form-control" placeholder="Nota adicional">
                                </div>
                                <div class="col-12 text-end">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-check me-1"></i> Registrar Transação
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div><!-- /col-lg-9 -->
    </div><!-- /row -->
</div><!-- /container-fluid -->


<!-- ══════ Modal Registrar Pagamento ══════ -->
<div class="modal fade" id="modalPay" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="post" action="?page=financial&action=payInstallment" id="formPay" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="installment_id" id="payInstId">
                <input type="hidden" name="order_id" id="payOrderId">
                <div class="modal-header bg-success border-0">
                    <h5 class="modal-title text-success"><i class="fas fa-hand-holding-usd me-2"></i>Registrar Pagamento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 mb-3">
                        <i class="fas fa-info-circle me-1"></i>
                        Pedido <strong id="payOrderDisplay">—</strong> ·
                        Parcela <strong id="payNumber">—</strong> ·
                        Valor: <strong id="payAmountDisplay">—</strong>
                        <br><small class="text-muted" id="payCustomerDisplay"></small>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Data do Pagamento</label>
                            <input type="date" name="paid_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Valor Pago (R$)</label>
                            <input type="number" step="0.01" min="0.01" name="paid_amount" id="payAmountInput" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Forma de Pagamento</label>
                            <select name="payment_method" id="payMethodSelect" class="form-select" required>
                                <option value="dinheiro">💵 Dinheiro</option>
                                <option value="pix">📱 PIX</option>
                                <option value="cartao_credito">💳 Cartão Crédito</option>
                                <option value="cartao_debito">💳 Cartão Débito</option>
                                <option value="boleto">📄 Boleto</option>
                                <option value="transferencia">🏦 Transferência</option>
                                <option value="gateway">🌐 Gateway Online</option>
                            </select>
                            <small class="text-muted" id="payMethodHint"></small>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold"><i class="fas fa-paperclip me-1"></i>Comprovante <span class="text-muted fw-normal">(opcional)</span></label>
                            <input type="file" name="attachment" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Observação <span class="text-muted fw-normal">(opcional)</span></label>
                            <input type="text" name="notes" class="form-control" placeholder="Ex: Comprovante recebido via WhatsApp">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success" id="btnSubmitPay">
                        <i class="fas fa-check me-1"></i> Registrar Pagamento
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ══════ Modal Editar Transação ══════ -->
<div class="modal fade" id="modalEditTx" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Editar Transação</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editTxId">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Tipo</label>
                        <select id="editTxType" class="form-select" required>
                            <option value="entrada">✅ Entrada</option>
                            <option value="saida">🔴 Saída</option>
                            <option value="registro">📋 Registro</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Categoria</label>
                        <select id="editTxCategory" class="form-select" required>
                            <optgroup label="Entradas" id="editCatEntrada">
                                <?php foreach ($categories['entrada'] ?? [] as $k => $v): ?>
                                <option value="<?= $k ?>" data-type="entrada"><?= $v ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="Saídas" id="editCatSaida">
                                <?php foreach ($categories['saida'] ?? [] as $k => $v): ?>
                                <option value="<?= $k ?>" data-type="saida"><?= $v ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="Sistema">
                                <option value="registro_ofx" data-type="registro">Registro OFX/Importação</option>
                                <option value="estorno_pagamento" data-type="registro">Estorno de Pagamento</option>
                            </optgroup>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">Descrição</label>
                        <input type="text" id="editTxDescription" class="form-control" placeholder="Descrição da transação" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Valor (R$)</label>
                        <input type="number" step="0.01" min="0.01" id="editTxAmount" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Data</label>
                        <input type="date" id="editTxDate" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Forma de Pagamento</label>
                        <select id="editTxMethod" class="form-select">
                            <option value="">— Não informado —</option>
                            <option value="dinheiro">💵 Dinheiro</option>
                            <option value="pix">📱 PIX</option>
                            <option value="cartao_credito">💳 Cartão Crédito</option>
                            <option value="cartao_debito">💳 Cartão Débito</option>
                            <option value="boleto">📄 Boleto</option>
                            <option value="transferencia">🏦 Transferência</option>
                            <option value="gateway">🌐 Gateway Online</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">Observação <span class="text-muted fw-normal">(opcional)</span></label>
                        <input type="text" id="editTxNotes" class="form-control" placeholder="Nota adicional">
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 d-flex justify-content-between">
                <button type="button" class="btn btn-outline-danger" id="btnEditTxDelete">
                    <i class="fas fa-trash me-1"></i>Excluir
                </button>
                <div>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btnEditTxSave">
                        <i class="fas fa-save me-1"></i>Salvar Alterações
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══════ Scripts ══════ -->
<script>
// Inject PHP data for the external JS module
window.AktiFinancial = {
    statusMap:      <?= json_encode($statusMap) ?>,
    methodLabels:   <?= json_encode($methodLabels) ?>,
    allCats:        <?= json_encode($allCats) ?>,
    bankConfig: {
        banco:   <?= json_encode($company['boleto_banco'] ?? '') ?>,
        agencia: <?= json_encode($company['boleto_agencia'] ?? '') ?>,
        conta:   <?= json_encode($company['boleto_conta'] ?? '') ?>
    },
    initialSection: '<?= $activeSection ?>'
};
</script>
<script src="assets/js/financial-payments.js?v=<?= filemtime('assets/js/financial-payments.js') ?>"></script>
