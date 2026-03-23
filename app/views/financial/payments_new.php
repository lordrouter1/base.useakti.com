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
 *   - reports      → DRE Simplificado (Fase 4)
 *   - cashflow     → Fluxo de Caixa Projetado (Fase 4)
 *   - recurring    → Transações Recorrentes (Fase 4)
 *
 * Variáveis disponíveis (carregadas pelo FinancialController::payments):
 *   $summary, $categories, $company, $companyAddress
 *   $overdueCount, $pendingConfirmCount
 *   $activeGateways (gateways de pagamento ativos, para integração com parcelas)
 *
 * Tabelas são carregadas via AJAX com filtros dinâmicos e paginação.
 */

$activeSection = $_GET['section'] ?? 'payments';
$validSections = ['payments', 'transactions', 'import', 'new', 'reports', 'cashflow', 'recurring'];
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

    /* ── DRE styles ── */
    .dre-row{transition:background .15s}
    .dre-row:hover{background:#f8f9fa}
    .dre-total-row{font-weight:bold;border-top:2px solid #dee2e6}
    .dre-result-positive{color:#27ae60}
    .dre-result-negative{color:#e74c3c}
</style>

<div class="container-fluid py-3">

    <!-- ══════ Header ══════ -->
    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div>
            <h1 class="h2 mb-1"><i class="fas fa-file-invoice-dollar me-2 text-primary"></i>Financeiro</h1>
            <p class="text-muted mb-0" style="font-size:.82rem;">Pagamentos, entradas/saídas, importação, relatórios e recorrências.</p>
        </div>
        <div class="btn-toolbar gap-2">
            <a href="?page=financial" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-chart-line me-1"></i> Dashboard
            </a>
        </div>
    </div>

    <div class="row g-4">

        <!-- ═══════════════════════════════════════════════ -->
        <!-- SIDEBAR — Menu Lateral de Seções (3/12)         -->
        <!-- ═══════════════════════════════════════════════ -->
        <div class="col-lg-3 fin-sidebar-col">
            <?php require __DIR__ . '/partials/_sidebar.php'; ?>
        </div>

        <!-- ═══════════════════════════════════════════════ -->
        <!-- CONTEÚDO PRINCIPAL — Seção Ativa (9/12)         -->
        <!-- ═══════════════════════════════════════════════ -->
        <div class="col-lg-9">

            <?php require __DIR__ . '/partials/_section_payments.php'; ?>
            <?php require __DIR__ . '/partials/_section_transactions.php'; ?>
            <?php require __DIR__ . '/partials/_section_import.php'; ?>
            <?php require __DIR__ . '/partials/_section_new_transaction.php'; ?>
            <?php require __DIR__ . '/partials/_section_dre.php'; ?>
            <?php require __DIR__ . '/partials/_section_cashflow.php'; ?>
            <?php require __DIR__ . '/partials/_section_recurring.php'; ?>

        </div><!-- /col-lg-9 -->
    </div><!-- /row -->
</div><!-- /container-fluid -->

<?php require __DIR__ . '/partials/_modals.php'; ?>

<!-- ══════ Scripts ══════ -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
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
    activeGateways: <?= json_encode($activeGateways ?? []) ?>,
    initialSection: '<?= $activeSection ?>'
};
</script>
<script src="assets/js/financial-payments.js?v=<?= filemtime('assets/js/financial-payments.js') ?>"></script>
