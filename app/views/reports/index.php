<?php
/**
 * View: Relatórios do Sistema
 * Menu lateral por categoria + cards de exportação à direita.
 *
 * Variáveis disponíveis:
 *   $company — Configurações da empresa (array)
 */

$activeCategory = $_GET['cat'] ?? 'vendas';
$validCategories = ['vendas', 'financeiro', 'cobranca', 'agendamentos', 'produtos'];
if (!in_array($activeCategory, $validCategories)) $activeCategory = 'vendas';
?>

<!-- ── SweetAlert2 flash messages ── -->
<?php if (!empty($_SESSION['flash_error'])): ?>
<script>document.addEventListener('DOMContentLoaded',()=>Swal.fire({icon:'error',title:'Erro',html:'<?= addslashes($_SESSION['flash_error']) ?>',confirmButtonColor:'#3498db'}));</script>
<?php unset($_SESSION['flash_error']); endif; ?>
<?php if (!empty($_SESSION['flash_success'])): ?>
<script>document.addEventListener('DOMContentLoaded',()=>Swal.mixin({toast:true,position:'top-end',showConfirmButton:false,timer:2500,timerProgressBar:true}).fire({icon:'success',title:'<?= addslashes($_SESSION['flash_success']) ?>'}));</script>
<?php unset($_SESSION['flash_success']); endif; ?>

<style>
    /* ── Sidebar nav ── */
    .rpt-sidebar .rpt-nav-item{display:flex;align-items:center;gap:.75rem;padding:.7rem 1rem;border-radius:10px;text-decoration:none;color:#555;font-size:.82rem;font-weight:500;transition:all .15s ease;margin-bottom:2px;border:1px solid transparent}
    .rpt-sidebar .rpt-nav-item:hover{background:#f1f5f9;color:#333}
    .rpt-sidebar .rpt-nav-item.active{background:var(--bs-primary,#3498db);color:#fff;box-shadow:0 2px 8px rgba(52,152,219,.3)}
    .rpt-sidebar .rpt-nav-item.active .rpt-nav-icon{background:rgba(255,255,255,.2);color:#fff}
    .rpt-sidebar .rpt-nav-item.active .rpt-nav-count{background:rgba(255,255,255,.25);color:#fff}
    .rpt-nav-icon{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:.8rem;flex-shrink:0;transition:all .15s ease}
    .rpt-nav-count{font-size:.65rem;padding:2px 7px;border-radius:10px;font-weight:600;margin-left:auto}
    .rpt-sidebar-label{font-size:.65rem;text-transform:uppercase;letter-spacing:.8px;color:#aaa;font-weight:700;padding:0 1rem;margin-bottom:.3rem;margin-top:.6rem}

    /* ── Report cards ── */
    .rpt-card{transition:transform .15s ease,box-shadow .15s ease;border-radius:12px;overflow:hidden}
    .rpt-card:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,0,0,.08)!important}
    .rpt-export-btn{border-radius:10px;font-size:.78rem;font-weight:600;padding:.45rem 1rem;transition:all .15s ease}
    .rpt-export-btn:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(0,0,0,.15)}
    .rpt-period-row input[type=date]{border-radius:8px;font-size:.8rem}
    .rpt-period-row label{font-size:.68rem;letter-spacing:.3px;text-transform:uppercase;color:#999}
    .rpt-desc{font-size:.78rem;line-height:1.5;color:#6c757d}
    .rpt-icon-circle{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0}

    /* ── Category section transition ── */
    .rpt-section{display:none;animation:rptFadeIn .25s ease}
    .rpt-section.active{display:block}
    @keyframes rptFadeIn{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:translateY(0)}}

    /* ── Mobile sidebar ── */
    @media(max-width:991.98px){
        .rpt-sidebar-col{margin-bottom:1rem}
        .rpt-sidebar{display:flex;gap:.4rem;overflow-x:auto;padding-bottom:.5rem;scrollbar-width:thin}
        .rpt-sidebar .rpt-nav-item{white-space:nowrap;flex-shrink:0;padding:.5rem .85rem;font-size:.75rem}
        .rpt-sidebar-label{display:none}
        .rpt-nav-count{display:none}
    }
</style>

<div class="container-fluid py-3">

    <!-- ══════ Header ══════ -->
    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div>
            <h1 class="h2 mb-1"><i class="fas fa-chart-bar me-2 text-primary"></i>Relatórios</h1>
            <p class="text-muted mb-0" style="font-size:.82rem;">Central de relatórios do sistema — vendas, financeiro, cobrança, agendamentos e produtos.</p>
        </div>
        <div class="btn-toolbar gap-2">
            <a href="?page=dashboard" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-tachometer-alt me-1"></i> Dashboard
            </a>
        </div>
    </div>

    <div class="row g-4">

        <!-- ═══════════════════════════════════════════════ -->
        <!-- SIDEBAR — Menu Lateral de Categorias (3/12)     -->
        <!-- ═══════════════════════════════════════════════ -->
        <div class="col-lg-3 rpt-sidebar-col">
            <div class="card border-0 shadow-sm" style="border-radius:12px;">
                <div class="card-body p-3">
                    <nav class="rpt-sidebar">

                        <div class="rpt-sidebar-label">Categorias</div>

                        <a href="#" class="rpt-nav-item <?= $activeCategory === 'vendas' ? 'active' : '' ?>" data-cat="vendas">
                            <span class="rpt-nav-icon" style="background:rgba(52,152,219,.1);color:#3498db;">
                                <i class="fas fa-shopping-cart"></i>
                            </span>
                            <span>Vendas</span>
                            <span class="rpt-nav-count" style="background:rgba(52,152,219,.1);color:#3498db;">2</span>
                        </a>

                        <a href="#" class="rpt-nav-item <?= $activeCategory === 'financeiro' ? 'active' : '' ?>" data-cat="financeiro">
                            <span class="rpt-nav-icon" style="background:rgba(243,156,18,.1);color:#f39c12;">
                                <i class="fas fa-coins"></i>
                            </span>
                            <span>Financeiro</span>
                            <span class="rpt-nav-count" style="background:rgba(243,156,18,.1);color:#f39c12;">1</span>
                        </a>

                        <a href="#" class="rpt-nav-item <?= $activeCategory === 'cobranca' ? 'active' : '' ?>" data-cat="cobranca">
                            <span class="rpt-nav-icon" style="background:rgba(231,76,60,.1);color:#e74c3c;">
                                <i class="fas fa-clock"></i>
                            </span>
                            <span>Cobrança</span>
                            <span class="rpt-nav-count" style="background:rgba(231,76,60,.1);color:#e74c3c;">1</span>
                        </a>

                        <a href="#" class="rpt-nav-item <?= $activeCategory === 'agendamentos' ? 'active' : '' ?>" data-cat="agendamentos">
                            <span class="rpt-nav-icon" style="background:rgba(155,89,182,.1);color:#9b59b6;">
                                <i class="fas fa-calendar-check"></i>
                            </span>
                            <span>Agendamentos</span>
                            <span class="rpt-nav-count" style="background:rgba(155,89,182,.1);color:#9b59b6;">1</span>
                        </a>

                        <a href="#" class="rpt-nav-item <?= $activeCategory === 'produtos' ? 'active' : '' ?>" data-cat="produtos">
                            <span class="rpt-nav-icon" style="background:rgba(22,160,133,.1);color:#16a085;">
                                <i class="fas fa-boxes-stacked"></i>
                            </span>
                            <span>Produtos & Estoque</span>
                            <span class="rpt-nav-count" style="background:rgba(22,160,133,.1);color:#16a085;">3</span>
                        </a>

                    </nav>
                </div>
            </div>

            <!-- Mini-dica abaixo do sidebar (apenas desktop) -->
            <div class="card border-0 shadow-sm mt-3 d-none d-lg-block" style="border-radius:12px;">
                <div class="card-body p-3">
                    <h6 class="mb-2 fw-bold" style="font-size:.78rem;color:#17a2b8;">
                        <i class="fas fa-lightbulb me-1"></i>Dica
                    </h6>
                    <p class="mb-0 text-muted" style="font-size:.72rem;line-height:1.55;">
                        Selecione a categoria no menu, defina o período e clique em
                        <span class="text-danger fw-bold">PDF</span> ou
                        <span class="text-success fw-bold">Excel</span>.
                        O download inicia automaticamente.
                    </p>
                </div>
            </div>

            <!-- Legenda formatos -->
            <div class="card border-0 shadow-sm mt-3 d-none d-lg-block" style="border-radius:12px;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-2">
                        <span class="badge me-2 px-2 py-1" style="font-size:.65rem;background:rgba(220,53,69,.1);color:#dc3545;">
                            <i class="fas fa-file-pdf me-1"></i>PDF
                        </span>
                        <span class="text-muted" style="font-size:.7rem;">Impressão e e-mail</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="badge me-2 px-2 py-1" style="font-size:.65rem;background:rgba(25,135,84,.1);color:#198754;">
                            <i class="fas fa-file-excel me-1"></i>XLSX
                        </span>
                        <span class="text-muted" style="font-size:.7rem;">Análise e gráficos</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════ -->
        <!-- CONTEÚDO PRINCIPAL — Cards por Categoria (9/12) -->
        <!-- ═══════════════════════════════════════════════ -->
        <div class="col-lg-9">

            <!-- ══════════════════════════════════════ -->
            <!-- CATEGORIA: Vendas                      -->
            <!-- ══════════════════════════════════════ -->
            <div class="rpt-section <?= $activeCategory === 'vendas' ? 'active' : '' ?>" id="cat-vendas">

                <div class="d-flex align-items-center mb-3">
                    <div class="rpt-icon-circle me-2" style="background:rgba(52,152,219,.1);width:34px;height:34px;">
                        <i class="fas fa-shopping-cart" style="color:#3498db;font-size:.85rem;"></i>
                    </div>
                    <div>
                        <h5 class="mb-0" style="font-size:1rem;">Vendas</h5>
                        <p class="text-muted mb-0" style="font-size:.72rem;">Relatórios de pedidos e faturamento comercial.</p>
                    </div>
                </div>

                <div class="row g-3">

                    <!-- Pedidos por Período -->
                    <div class="col-xl-6">
                        <div class="card border-0 shadow-sm h-100 rpt-card">
                            <div class="card-header py-2" style="background:linear-gradient(135deg,#3498db 0%,#2980b9 100%);">
                                <h6 class="mb-0 text-white" style="font-size:.85rem;">
                                    <i class="fas fa-file-invoice-dollar me-2"></i>Pedidos por Período
                                </h6>
                            </div>
                            <div class="card-body p-3 d-flex flex-column">
                                <p class="rpt-desc mb-3">Lista completa de pedidos com cliente, valor total, status de pagamento e etapa no pipeline.</p>
                                <form class="rpt-form mt-auto" data-type="orders_period">
                                    <?= csrf_field() ?>
                                    <div class="row g-2 mb-3 rpt-period-row">
                                        <div class="col-6">
                                            <label class="form-label mb-1">De</label>
                                            <input type="date" class="form-control form-control-sm" name="start" required value="<?= eAttr(date('Y-m-01')) ?>">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label mb-1">Até</label>
                                            <input type="date" class="form-control form-control-sm" name="end" required value="<?= eAttr(date('Y-m-d')) ?>">
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-outline-danger rpt-export-btn flex-fill" data-action="exportPdf">
                                            <i class="fas fa-file-pdf me-1"></i> PDF
                                        </button>
                                        <button type="button" class="btn btn-outline-success rpt-export-btn flex-fill" data-action="exportExcel">
                                            <i class="fas fa-file-excel me-1"></i> Excel
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Faturamento por Cliente -->
                    <div class="col-xl-6">
                        <div class="card border-0 shadow-sm h-100 rpt-card">
                            <div class="card-header py-2" style="background:linear-gradient(135deg,#27ae60 0%,#219a52 100%);">
                                <h6 class="mb-0 text-white" style="font-size:.85rem;">
                                    <i class="fas fa-ranking-star me-2"></i>Faturamento por Cliente
                                </h6>
                            </div>
                            <div class="card-body p-3 d-flex flex-column">
                                <p class="rpt-desc mb-3">Ranking de clientes por valor faturado, com quantidade de pedidos e soma total.</p>
                                <form class="rpt-form mt-auto" data-type="revenue_customer">
                                    <?= csrf_field() ?>
                                    <div class="row g-2 mb-3 rpt-period-row">
                                        <div class="col-6">
                                            <label class="form-label mb-1">De</label>
                                            <input type="date" class="form-control form-control-sm" name="start" required value="<?= eAttr(date('Y-m-01')) ?>">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label mb-1">Até</label>
                                            <input type="date" class="form-control form-control-sm" name="end" required value="<?= eAttr(date('Y-m-d')) ?>">
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-outline-danger rpt-export-btn flex-fill" data-action="exportPdf">
                                            <i class="fas fa-file-pdf me-1"></i> PDF
                                        </button>
                                        <button type="button" class="btn btn-outline-success rpt-export-btn flex-fill" data-action="exportExcel">
                                            <i class="fas fa-file-excel me-1"></i> Excel
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- ══════════════════════════════════════ -->
            <!-- CATEGORIA: Financeiro                   -->
            <!-- ══════════════════════════════════════ -->
            <div class="rpt-section <?= $activeCategory === 'financeiro' ? 'active' : '' ?>" id="cat-financeiro">

                <div class="d-flex align-items-center mb-3">
                    <div class="rpt-icon-circle me-2" style="background:rgba(243,156,18,.1);width:34px;height:34px;">
                        <i class="fas fa-coins" style="color:#f39c12;font-size:.85rem;"></i>
                    </div>
                    <div>
                        <h5 class="mb-0" style="font-size:1rem;">Financeiro</h5>
                        <p class="text-muted mb-0" style="font-size:.72rem;">Demonstrativos e balanços do caixa.</p>
                    </div>
                </div>

                <div class="row g-3">

                    <!-- DRE -->
                    <div class="col-xl-6">
                        <div class="card border-0 shadow-sm h-100 rpt-card">
                            <div class="card-header py-2" style="background:linear-gradient(135deg,#f39c12 0%,#e67e22 100%);">
                                <h6 class="mb-0 text-white" style="font-size:.85rem;">
                                    <i class="fas fa-scale-balanced me-2"></i>DRE — Resultado
                                </h6>
                            </div>
                            <div class="card-body p-3 d-flex flex-column">
                                <p class="rpt-desc mb-3">Entradas e saídas agrupadas por categoria, com saldo líquido. Inclui pagamentos de pedidos e transações manuais.</p>
                                <form class="rpt-form mt-auto" data-type="income_statement">
                                    <?= csrf_field() ?>
                                    <div class="row g-2 mb-3 rpt-period-row">
                                        <div class="col-6">
                                            <label class="form-label mb-1">De</label>
                                            <input type="date" class="form-control form-control-sm" name="start" required value="<?= eAttr(date('Y-m-01')) ?>">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label mb-1">Até</label>
                                            <input type="date" class="form-control form-control-sm" name="end" required value="<?= eAttr(date('Y-m-d')) ?>">
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-outline-danger rpt-export-btn flex-fill" data-action="exportPdf">
                                            <i class="fas fa-file-pdf me-1"></i> PDF
                                        </button>
                                        <button type="button" class="btn btn-outline-success rpt-export-btn flex-fill" data-action="exportExcel">
                                            <i class="fas fa-file-excel me-1"></i> Excel
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- ══════════════════════════════════════ -->
            <!-- CATEGORIA: Cobrança                     -->
            <!-- ══════════════════════════════════════ -->
            <div class="rpt-section <?= $activeCategory === 'cobranca' ? 'active' : '' ?>" id="cat-cobranca">

                <div class="d-flex align-items-center mb-3">
                    <div class="rpt-icon-circle me-2" style="background:rgba(231,76,60,.1);width:34px;height:34px;">
                        <i class="fas fa-clock" style="color:#e74c3c;font-size:.85rem;"></i>
                    </div>
                    <div>
                        <h5 class="mb-0" style="font-size:1rem;">Cobrança</h5>
                        <p class="text-muted mb-0" style="font-size:.72rem;">Parcelas pendentes, atrasos e inadimplência.</p>
                    </div>
                </div>

                <div class="row g-3">

                    <!-- Parcelas Pendentes -->
                    <div class="col-xl-6">
                        <div class="card border-0 shadow-sm h-100 rpt-card">
                            <div class="card-header py-2" style="background:linear-gradient(135deg,#e74c3c 0%,#c0392b 100%);">
                                <h6 class="mb-0 text-white" style="font-size:.85rem;">
                                    <i class="fas fa-exclamation-circle me-2"></i>Parcelas Pendentes
                                </h6>
                            </div>
                            <div class="card-body p-3 d-flex flex-column">
                                <p class="rpt-desc mb-3">Todas as parcelas com status pendente ou atrasado, com dias de atraso calculados, ordenadas por vencimento.</p>
                                <!-- CTA pattern: sem período -->
                                <div class="text-center py-3 mb-3 mt-auto" style="background:linear-gradient(135deg,#fdecea 0%,#fdf0ee 100%);border-radius:10px;border:2px dashed rgba(231,76,60,.2);">
                                    <i class="fas fa-bolt d-block mb-1" style="font-size:1.5rem;color:#e74c3c;opacity:.4;"></i>
                                    <p class="mb-0" style="font-size:.7rem;color:#aaa;">Exportação instantânea — sem filtro de data.</p>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="?page=reports&action=exportPdf&type=open_installments"
                                       class="btn btn-outline-danger rpt-export-btn flex-fill rpt-link-btn">
                                        <i class="fas fa-file-pdf me-1"></i> PDF
                                    </a>
                                    <a href="?page=reports&action=exportExcel&type=open_installments"
                                       class="btn btn-outline-success rpt-export-btn flex-fill rpt-link-btn">
                                        <i class="fas fa-file-excel me-1"></i> Excel
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- ══════════════════════════════════════ -->
            <!-- CATEGORIA: Agendamentos                 -->
            <!-- ══════════════════════════════════════ -->
            <div class="rpt-section <?= $activeCategory === 'agendamentos' ? 'active' : '' ?>" id="cat-agendamentos">

                <div class="d-flex align-items-center mb-3">
                    <div class="rpt-icon-circle me-2" style="background:rgba(155,89,182,.1);width:34px;height:34px;">
                        <i class="fas fa-calendar-check" style="color:#9b59b6;font-size:.85rem;"></i>
                    </div>
                    <div>
                        <h5 class="mb-0" style="font-size:1rem;">Agendamentos</h5>
                        <p class="text-muted mb-0" style="font-size:.72rem;">Contatos agendados para orçamento e follow-up comercial.</p>
                    </div>
                </div>

                <div class="row g-3">

                    <!-- Agendamentos de Contato -->
                    <div class="col-xl-6">
                        <div class="card border-0 shadow-sm h-100 rpt-card">
                            <div class="card-header py-2" style="background:linear-gradient(135deg,#9b59b6 0%,#8e44ad 100%);">
                                <h6 class="mb-0 text-white" style="font-size:.85rem;">
                                    <i class="fas fa-phone-volume me-2"></i>Contatos Agendados
                                </h6>
                            </div>
                            <div class="card-body p-3 d-flex flex-column">
                                <p class="rpt-desc mb-3">Lista de contatos agendados na etapa de orçamento, com prioridade, telefone e valor estimado do pedido.</p>
                                <form class="rpt-form mt-auto" data-type="scheduled_contacts">
                                    <?= csrf_field() ?>
                                    <div class="row g-2 mb-3 rpt-period-row">
                                        <div class="col-6">
                                            <label class="form-label mb-1">De</label>
                                            <input type="date" class="form-control form-control-sm" name="start" required value="<?= eAttr(date('Y-m-01')) ?>">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label mb-1">Até</label>
                                            <input type="date" class="form-control form-control-sm" name="end" required value="<?= eAttr(date('Y-m-t')) ?>">
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-outline-danger rpt-export-btn flex-fill" data-action="exportPdf">
                                            <i class="fas fa-file-pdf me-1"></i> PDF
                                        </button>
                                        <button type="button" class="btn btn-outline-success rpt-export-btn flex-fill" data-action="exportExcel">
                                            <i class="fas fa-file-excel me-1"></i> Excel
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- ══════════════════════════════════════ -->
            <!-- CATEGORIA: Produtos & Estoque           -->
            <!-- ══════════════════════════════════════ -->
            <div class="rpt-section <?= $activeCategory === 'produtos' ? 'active' : '' ?>" id="cat-produtos">

                <div class="d-flex align-items-center mb-3">
                    <div class="rpt-icon-circle me-2" style="background:rgba(22,160,133,.1);width:34px;height:34px;">
                        <i class="fas fa-boxes-stacked" style="color:#16a085;font-size:.85rem;"></i>
                    </div>
                    <div>
                        <h5 class="mb-0" style="font-size:1rem;">Produtos & Estoque</h5>
                        <p class="text-muted mb-0" style="font-size:.72rem;">Catálogo de produtos, estoque por armazém e movimentações.</p>
                    </div>
                </div>

                <div class="row g-3">

                    <!-- Catálogo de Produtos -->
                    <div class="col-xl-6">
                        <div class="card border-0 shadow-sm h-100 rpt-card">
                            <div class="card-header py-2" style="background:linear-gradient(135deg,#16a085 0%,#1abc9c 100%);">
                                <h6 class="mb-0 text-white" style="font-size:.85rem;">
                                    <i class="fas fa-box-open me-2"></i>Catálogo de Produtos
                                </h6>
                            </div>
                            <div class="card-body p-3 d-flex flex-column">
                                <p class="rpt-desc mb-3">Lista todos os produtos com nome, SKU, categoria, subcategoria, preços por tabela, setores de produção e variações de grade.</p>
                                <form class="rpt-form-custom mt-auto" data-type="product_catalog">
                                    <?= csrf_field() ?>
                                    <div class="mb-2">
                                        <label class="form-label mb-1" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.3px;color:#999;">Produto (opcional)</label>
                                        <select name="product_id" class="form-select form-select-sm" style="border-radius:8px;font-size:.8rem;">
                                            <option value="">— Todos os produtos —</option>
                                            <?php foreach ($productsList as $pItem): ?>
                                                <option value="<?= eAttr($pItem['id']) ?>"><?= e($pItem['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" name="show_variations" value="1" id="chkVarCatalog">
                                        <label class="form-check-label" for="chkVarCatalog" style="font-size:.78rem;">Incluir variações de grade</label>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-outline-danger rpt-export-btn flex-fill rpt-custom-btn" data-action="exportPdf">
                                            <i class="fas fa-file-pdf me-1"></i> PDF
                                        </button>
                                        <button type="button" class="btn btn-outline-success rpt-export-btn flex-fill rpt-custom-btn" data-action="exportExcel">
                                            <i class="fas fa-file-excel me-1"></i> Excel
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Estoque por Armazém -->
                    <div class="col-xl-6">
                        <div class="card border-0 shadow-sm h-100 rpt-card">
                            <div class="card-header py-2" style="background:linear-gradient(135deg,#2980b9 0%,#3498db 100%);">
                                <h6 class="mb-0 text-white" style="font-size:.85rem;">
                                    <i class="fas fa-warehouse me-2"></i>Estoque por Armazém
                                </h6>
                            </div>
                            <div class="card-body p-3 d-flex flex-column">
                                <p class="rpt-desc mb-3">Produtos em estoque separados por armazém, com quantidade, localização e variação. Filtre por produto ou armazém específico.</p>
                                <form class="rpt-form-custom mt-auto" data-type="stock_warehouse">
                                    <?= csrf_field() ?>
                                    <div class="mb-2">
                                        <label class="form-label mb-1" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.3px;color:#999;">Produto (opcional)</label>
                                        <select name="product_id" class="form-select form-select-sm" style="border-radius:8px;font-size:.8rem;">
                                            <option value="">— Todos os produtos —</option>
                                            <?php foreach ($productsList as $pItem): ?>
                                                <option value="<?= eAttr($pItem['id']) ?>"><?= e($pItem['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label mb-1" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.3px;color:#999;">Armazém (opcional)</label>
                                        <select name="warehouse_id" class="form-select form-select-sm" style="border-radius:8px;font-size:.8rem;">
                                            <option value="">— Todos os armazéns —</option>
                                            <?php foreach ($warehousesList as $wItem): ?>
                                                <option value="<?= eAttr($wItem['id']) ?>"><?= e($wItem['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-outline-danger rpt-export-btn flex-fill rpt-custom-btn" data-action="exportPdf">
                                            <i class="fas fa-file-pdf me-1"></i> PDF
                                        </button>
                                        <button type="button" class="btn btn-outline-success rpt-export-btn flex-fill rpt-custom-btn" data-action="exportExcel">
                                            <i class="fas fa-file-excel me-1"></i> Excel
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Movimentações de Estoque -->
                    <div class="col-xl-6">
                        <div class="card border-0 shadow-sm h-100 rpt-card">
                            <div class="card-header py-2" style="background:linear-gradient(135deg,#e67e22 0%,#f39c12 100%);">
                                <h6 class="mb-0 text-white" style="font-size:.85rem;">
                                    <i class="fas fa-exchange-alt me-2"></i>Movimentações de Estoque
                                </h6>
                            </div>
                            <div class="card-body p-3 d-flex flex-column">
                                <p class="rpt-desc mb-3">Registro completo de entradas, saídas, ajustes e transferências com produto, armazém, quantidade e usuário responsável.</p>
                                <form class="rpt-form mt-auto" data-type="stock_movements">
                                    <?= csrf_field() ?>
                                    <div class="row g-2 mb-3 rpt-period-row">
                                        <div class="col-6">
                                            <label class="form-label mb-1">De</label>
                                            <input type="date" class="form-control form-control-sm" name="start" required value="<?= eAttr(date('Y-m-01')) ?>">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label mb-1">Até</label>
                                            <input type="date" class="form-control form-control-sm" name="end" required value="<?= eAttr(date('Y-m-d')) ?>">
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-outline-danger rpt-export-btn flex-fill" data-action="exportPdf">
                                            <i class="fas fa-file-pdf me-1"></i> PDF
                                        </button>
                                        <button type="button" class="btn btn-outline-success rpt-export-btn flex-fill" data-action="exportExcel">
                                            <i class="fas fa-file-excel me-1"></i> Excel
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div><!-- /.col-lg-9 -->

    </div><!-- /.row -->

</div><!-- /.container-fluid -->

<!-- ══════ JS ══════ -->
<script>
document.addEventListener('DOMContentLoaded', function() {

    // ── Sidebar navigation (SPA-like, sem reload) ──
    document.querySelectorAll('.rpt-nav-item').forEach(function(item) {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            var cat = this.dataset.cat;
            if (!cat) return;

            // Atualizar sidebar
            document.querySelectorAll('.rpt-nav-item').forEach(function(n) { n.classList.remove('active'); });
            this.classList.add('active');

            // Atualizar seções
            document.querySelectorAll('.rpt-section').forEach(function(s) { s.classList.remove('active'); });
            var target = document.getElementById('cat-' + cat);
            if (target) target.classList.add('active');

            // Atualizar URL sem reload (para bookmarks)
            var url = new URL(window.location);
            url.searchParams.set('cat', cat);
            history.replaceState(null, '', url);
        });
    });

    // ── Export buttons (com loading state + iframe download) ──
    document.querySelectorAll('.rpt-form .rpt-export-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var form   = this.closest('.rpt-form');
            var type   = form.dataset.type;
            var action = this.dataset.action;

            // Validar período
            var start = form.querySelector('[name=start]');
            var end   = form.querySelector('[name=end]');
            if (start && end) {
                if (!start.value || !end.value) {
                    Swal.fire({icon:'warning', title:'Período obrigatório', text:'Preencha as datas de início e fim.', confirmButtonColor:'#3498db'});
                    return;
                }
                if (start.value > end.value) {
                    Swal.fire({icon:'warning', title:'Período inválido', text:'A data inicial não pode ser maior que a final.', confirmButtonColor:'#3498db'});
                    return;
                }
            }

            // Loading
            var origHtml = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Gerando...';

            // URL
            var url = '?page=reports&action=' + encodeURIComponent(action)
                    + '&type=' + encodeURIComponent(type);
            if (start && end) {
                url += '&start=' + encodeURIComponent(start.value)
                     + '&end='   + encodeURIComponent(end.value);
            }

            // Download via iframe
            var iframe = document.createElement('iframe');
            iframe.style.display = 'none';
            iframe.src = url;
            document.body.appendChild(iframe);

            var self = this;
            setTimeout(function() {
                self.disabled = false;
                self.innerHTML = origHtml;
                Swal.mixin({toast:true, position:'top-end', showConfirmButton:false, timer:2000, timerProgressBar:true})
                    .fire({icon:'success', title:'Download iniciado!'});
            }, 2500);
            setTimeout(function() { iframe.remove(); }, 15000);
        });
    });

    // ── Link buttons (parcelas — sem form) ──
    document.querySelectorAll('.rpt-link-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var href = this.getAttribute('href');
            var origHtml = this.innerHTML;
            this.classList.add('disabled');
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Gerando...';

            var iframe = document.createElement('iframe');
            iframe.style.display = 'none';
            iframe.src = href;
            document.body.appendChild(iframe);

            var self = this;
            setTimeout(function() {
                self.classList.remove('disabled');
                self.innerHTML = origHtml;
                Swal.mixin({toast:true, position:'top-end', showConfirmButton:false, timer:2000, timerProgressBar:true})
                    .fire({icon:'success', title:'Download iniciado!'});
            }, 2500);
            setTimeout(function() { iframe.remove(); }, 15000);
        });
    });

    // ── Custom form buttons (catálogo de produtos, estoque — sem período obrigatório) ──
    document.querySelectorAll('.rpt-form-custom .rpt-custom-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var form   = this.closest('.rpt-form-custom');
            var type   = form.dataset.type;
            var action = this.dataset.action;

            // Loading
            var origHtml = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Gerando...';

            // Build URL with all form fields
            var url = '?page=reports&action=' + encodeURIComponent(action)
                    + '&type=' + encodeURIComponent(type);

            // Selects
            form.querySelectorAll('select').forEach(function(sel) {
                if (sel.name && sel.value) {
                    url += '&' + encodeURIComponent(sel.name) + '=' + encodeURIComponent(sel.value);
                }
            });

            // Checkboxes
            form.querySelectorAll('input[type=checkbox]').forEach(function(chk) {
                url += '&' + encodeURIComponent(chk.name) + '=' + (chk.checked ? '1' : '0');
            });

            // Download via iframe
            var iframe = document.createElement('iframe');
            iframe.style.display = 'none';
            iframe.src = url;
            document.body.appendChild(iframe);

            var self = this;
            setTimeout(function() {
                self.disabled = false;
                self.innerHTML = origHtml;
                Swal.mixin({toast:true, position:'top-end', showConfirmButton:false, timer:2000, timerProgressBar:true})
                    .fire({icon:'success', title:'Download iniciado!'});
            }, 2500);
            setTimeout(function() { iframe.remove(); }, 15000);
        });
    });
});
</script>
