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
$validSections = ['payments', 'transactions', 'import', 'new', 'reports', 'cashflow', 'recurring', 'audit'];
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

<!-- ══════ Flash messages (Toast) ══════ -->
<?php if (!empty($_SESSION['flash_error'])): ?>
<script nonce="<?= cspNonce() ?>">document.addEventListener('DOMContentLoaded',()=>{if(typeof AktiToast!=='undefined')AktiToast.error('<?= eJs($_SESSION['flash_error']) ?>');});</script>
<?php unset($_SESSION['flash_error']); endif; ?>
<?php if (!empty($_SESSION['flash_success'])): ?>
<script nonce="<?= cspNonce() ?>">document.addEventListener('DOMContentLoaded',()=>{if(typeof AktiToast!=='undefined')AktiToast.success('<?= eJs($_SESSION['flash_success']) ?>');});</script>
<?php unset($_SESSION['flash_success']); endif; ?>

<!-- Styles moved to assets/css/modules/financial.css -->

<div class="container-fluid py-3">

    <!-- ══════ Header ══════ -->
    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div>
            <h1 class="h2 mb-1"><i class="fas fa-file-invoice-dollar me-2 text-primary"></i>Financeiro</h1>
            <p class="text-muted mb-0" style="font-size:.82rem;">Pagamentos, entradas/saídas, importação, relatórios e recorrências.</p>
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
            <?php require __DIR__ . '/partials/_section_audit.php'; ?>

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
