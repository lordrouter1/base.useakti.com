<?php
/**
 * View: Relatórios do Sistema
 * Menu lateral por categoria + cards de exportação à direita.
 *
 * Variáveis disponíveis:
 *   $company — Configurações da empresa (array)
 */

$activeCategory = $_GET['cat'] ?? 'vendas';
$validCategories = ['vendas', 'financeiro', 'cobranca', 'agendamentos', 'produtos', 'comissoes', 'fiscal'];
if (!in_array($activeCategory, $validCategories)) $activeCategory = 'vendas';
?>

<!-- ── Flash messages ── -->
<?php require 'app/views/components/flash-messages.php'; ?>

<!-- Styles loaded from assets/css/modules/reports.css via header.php -->

<div class="container-fluid py-3">

    <!-- ══════ Header ══════ -->
    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div>
            <h1 class="h2 mb-1"><i class="fas fa-chart-bar me-2 text-primary"></i>Relatórios</h1>
            <p class="text-muted mb-0" style="font-size:.82rem;">Central de relatórios do sistema — vendas, financeiro, cobrança, agendamentos, produtos, comissões e fiscal.</p>
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
                            <span class="rpt-nav-icon nav-icon-blue">
                                <i class="fas fa-shopping-cart"></i>
                            </span>
                            <span>Vendas</span>
                            <span class="rpt-nav-count nav-icon-blue">2</span>
                        </a>

                        <a href="#" class="rpt-nav-item <?= $activeCategory === 'financeiro' ? 'active' : '' ?>" data-cat="financeiro">
                            <span class="rpt-nav-icon nav-icon-orange">
                                <i class="fas fa-coins"></i>
                            </span>
                            <span>Financeiro</span>
                            <span class="rpt-nav-count nav-icon-orange">1</span>
                        </a>

                        <a href="#" class="rpt-nav-item <?= $activeCategory === 'cobranca' ? 'active' : '' ?>" data-cat="cobranca">
                            <span class="rpt-nav-icon nav-icon-red">
                                <i class="fas fa-clock"></i>
                            </span>
                            <span>Cobrança</span>
                            <span class="rpt-nav-count nav-icon-red">1</span>
                        </a>

                        <a href="#" class="rpt-nav-item <?= $activeCategory === 'agendamentos' ? 'active' : '' ?>" data-cat="agendamentos">
                            <span class="rpt-nav-icon nav-icon-purple">
                                <i class="fas fa-calendar-check"></i>
                            </span>
                            <span>Agendamentos</span>
                            <span class="rpt-nav-count nav-icon-purple">1</span>
                        </a>

                        <a href="#" class="rpt-nav-item <?= $activeCategory === 'produtos' ? 'active' : '' ?>" data-cat="produtos">
                            <span class="rpt-nav-icon nav-icon-teal">
                                <i class="fas fa-boxes-stacked"></i>
                            </span>
                            <span>Produtos & Estoque</span>
                            <span class="rpt-nav-count nav-icon-teal">3</span>
                        </a>

                        <a href="#" class="rpt-nav-item <?= $activeCategory === 'comissoes' ? 'active' : '' ?>" data-cat="comissoes">
                            <span class="rpt-nav-icon nav-icon-grape">
                                <i class="fas fa-hand-holding-usd"></i>
                            </span>
                            <span>Comissões</span>
                            <span class="rpt-nav-count nav-icon-grape">1</span>
                        </a>

                        <div class="rpt-sidebar-label">Fiscal</div>

                        <a href="#" class="rpt-nav-item <?= $activeCategory === 'fiscal' ? 'active' : '' ?>" data-cat="fiscal">
                            <span class="rpt-nav-icon nav-icon-navy">
                                <i class="fas fa-file-invoice"></i>
                            </span>
                            <span>NF-e / NFC-e</span>
                            <span class="rpt-nav-count nav-icon-navy">7</span>
                        </a>

                    </nav>
                </div>
            </div>

            <!-- Mini-dica abaixo do sidebar (apenas desktop) -->
            <div class="card border-0 shadow-sm mt-3 d-none d-lg-block" style="border-radius:12px;">
                <div class="card-body p-3">
                    <h6 class="mb-2 fw-bold text-info-alt" style="font-size:.78rem;">
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
                        <span class="badge me-2 px-2 py-1 badge-danger-light" style="font-size:.65rem;">
                            <i class="fas fa-file-pdf me-1"></i>PDF
                        </span>
                        <span class="text-muted" style="font-size:.7rem;">Impressão e e-mail</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="badge me-2 px-2 py-1 badge-success-light" style="font-size:.65rem;">
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
                    <div class="rpt-icon-circle me-2 nav-icon-blue" style="width:34px;height:34px;">
                        <i class="fas fa-shopping-cart" style="font-size:.85rem;"></i>
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
                            <div class="card-header py-2 card-header-blue">
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
                            <div class="card-header py-2 card-header-green">
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
                    <div class="rpt-icon-circle me-2 nav-icon-orange" style="width:34px;height:34px;">
                        <i class="fas fa-coins" style="font-size:.85rem;"></i>
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
                            <div class="card-header py-2 card-header-orange">
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
                    <div class="rpt-icon-circle me-2 nav-icon-red" style="width:34px;height:34px;">
                        <i class="fas fa-clock" style="font-size:.85rem;"></i>
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
                            <div class="card-header py-2 card-header-red">
                                <h6 class="mb-0 text-white" style="font-size:.85rem;">
                                    <i class="fas fa-exclamation-circle me-2"></i>Parcelas Pendentes
                                </h6>
                            </div>
                            <div class="card-body p-3 d-flex flex-column">
                                <p class="rpt-desc mb-3">Todas as parcelas com status pendente ou atrasado, com dias de atraso calculados, ordenadas por vencimento.</p>
                                <!-- CTA pattern: sem período -->
                                <div class="text-center py-3 mb-3 mt-auto section-bg-danger">
                                    <i class="fas fa-bolt d-block mb-1 text-red" style="font-size:1.5rem;opacity:.4;"></i>
                                    <p class="mb-0 text-muted" style="font-size:.7rem;">Exportação instantânea — sem filtro de data.</p>
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
                    <div class="rpt-icon-circle me-2 nav-icon-purple" style="width:34px;height:34px;">
                        <i class="fas fa-calendar-check" style="font-size:.85rem;"></i>
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
                            <div class="card-header py-2 card-header-grape">
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
                    <div class="rpt-icon-circle me-2 nav-icon-teal" style="width:34px;height:34px;">
                        <i class="fas fa-boxes-stacked" style="font-size:.85rem;"></i>
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
                            <div class="card-header py-2 card-header-teal">
                                <h6 class="mb-0 text-white" style="font-size:.85rem;">
                                    <i class="fas fa-box-open me-2"></i>Catálogo de Produtos
                                </h6>
                            </div>
                            <div class="card-body p-3 d-flex flex-column">
                                <p class="rpt-desc mb-3">Lista todos os produtos com nome, SKU, categoria, subcategoria, preços por tabela, setores de produção e variações de grade.</p>
                                <form class="rpt-form-custom mt-auto" data-type="product_catalog">
                                    <?= csrf_field() ?>
                                    <div class="mb-2">
                                        <label class="form-label mb-1 form-label-muted">Produto (opcional)</label>
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
                            <div class="card-header py-2 card-header-navy">
                                <h6 class="mb-0 text-white" style="font-size:.85rem;">
                                    <i class="fas fa-warehouse me-2"></i>Estoque por Armazém
                                </h6>
                            </div>
                            <div class="card-body p-3 d-flex flex-column">
                                <p class="rpt-desc mb-3">Produtos em estoque separados por armazém, com quantidade, localização e variação. Filtre por produto ou armazém específico.</p>
                                <form class="rpt-form-custom mt-auto" data-type="stock_warehouse">
                                    <?= csrf_field() ?>
                                    <div class="mb-2">
                                        <label class="form-label mb-1 form-label-muted">Produto (opcional)</label>
                                        <select name="product_id" class="form-select form-select-sm" style="border-radius:8px;font-size:.8rem;">
                                            <option value="">— Todos os produtos —</option>
                                            <?php foreach ($productsList as $pItem): ?>
                                                <option value="<?= eAttr($pItem['id']) ?>"><?= e($pItem['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label mb-1 form-label-muted">Armazém (opcional)</label>
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
                            <div class="card-header py-2 card-header-carrot">
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

            <!-- ══════════════════════════════════════ -->
            <!-- CATEGORIA: Comissões                    -->
            <!-- ══════════════════════════════════════ -->
            <div class="rpt-section <?= $activeCategory === 'comissoes' ? 'active' : '' ?>" id="cat-comissoes">

                <div class="d-flex align-items-center mb-3">
                    <div class="rpt-icon-circle me-2 nav-icon-grape" style="width:34px;height:34px;">
                        <i class="fas fa-hand-holding-usd" style="font-size:.85rem;"></i>
                    </div>
                    <div>
                        <h5 class="mb-0" style="font-size:1rem;">Comissões</h5>
                        <p class="text-muted mb-0" style="font-size:.72rem;">Relatório de comissões por período e funcionário.</p>
                    </div>
                </div>

                <div class="row g-3">

                    <!-- Comissões por Período -->
                    <div class="col-xl-6">
                        <div class="card border-0 shadow-sm h-100 rpt-card">
                            <div class="card-header py-2 card-header-grape-alt">
                                <h6 class="mb-0 text-white" style="font-size:.85rem;">
                                    <i class="fas fa-hand-holding-usd me-2"></i>Comissões por Período
                                </h6>
                            </div>
                            <div class="card-body p-3 d-flex flex-column">
                                <p class="rpt-desc mb-3">Lista comissões por funcionário com valores base, comissão calculada e status. Filtre por período e/ou funcionário específico. Quando nenhum funcionário é selecionado, exibe todos agrupados com subtotais.</p>
                                <form class="rpt-form-custom mt-auto" data-type="commissions_report">
                                    <?= csrf_field() ?>
                                    <div class="row g-2 mb-2 rpt-period-row">
                                        <div class="col-6">
                                            <label class="form-label mb-1">De</label>
                                            <input type="date" class="form-control form-control-sm" name="start" required value="<?= eAttr(date('Y-m-01')) ?>">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label mb-1">Até</label>
                                            <input type="date" class="form-control form-control-sm" name="end" required value="<?= eAttr(date('Y-m-d')) ?>">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label mb-1 form-label-muted">Funcionário (opcional)</label>
                                        <select name="user_id" class="form-select form-select-sm" style="border-radius:8px;font-size:.8rem;">
                                            <option value="">— Todos os funcionários —</option>
                                            <?php foreach ($usersList as $uItem): ?>
                                                <option value="<?= eAttr($uItem['id']) ?>"><?= e($uItem['name']) ?></option>
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

                </div>
            </div>

            <!-- ══════════════════════════════════════ -->
            <!-- CATEGORIA: Fiscal (NF-e / NFC-e)       -->
            <!-- ══════════════════════════════════════ -->
            <div class="rpt-section <?= $activeCategory === 'fiscal' ? 'active' : '' ?>" id="cat-fiscal">

                <div class="d-flex align-items-center mb-3">
                    <div class="rpt-icon-circle me-2 nav-icon-navy" style="width:34px;height:34px;">
                        <i class="fas fa-file-invoice" style="font-size:.85rem;"></i>
                    </div>
                    <div>
                        <h5 class="mb-0" style="font-size:1rem;">Fiscal — NF-e / NFC-e</h5>
                        <p class="text-muted mb-0" style="font-size:.72rem;">Relatórios de documentos fiscais, impostos, CFOP, cancelamentos e comunicação SEFAZ.</p>
                    </div>
                </div>

                <div class="row g-3">

                    <!-- ── 1. NF-e por Período ── -->
                    <div class="col-xl-6">
                        <div class="card border-0 shadow-sm h-100 rpt-card">
                            <div class="card-header py-2 card-header-navy">
                                <h6 class="mb-0 text-white" style="font-size:.85rem;">
                                    <i class="fas fa-file-invoice-dollar me-2"></i>NF-e por Período
                                </h6>
                            </div>
                            <div class="card-body p-3 d-flex flex-column">
                                <p class="rpt-desc mb-3">Lista de todas as notas fiscais emitidas no período, com número, série, destinatário, valor, status e chave de acesso.</p>
                                <form class="rpt-form-custom mt-auto" data-type="nfes_period">
                                    <?= csrf_field() ?>
                                    <div class="row g-2 mb-2 rpt-period-row">
                                        <div class="col-6">
                                            <label class="form-label mb-1">De</label>
                                            <input type="date" class="form-control form-control-sm" name="start" required value="<?= eAttr(date('Y-m-01')) ?>">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label mb-1">Até</label>
                                            <input type="date" class="form-control form-control-sm" name="end" required value="<?= eAttr(date('Y-m-d')) ?>">
                                        </div>
                                    </div>
                                    <div class="row g-2 mb-3">
                                        <div class="col-6">
                                            <label class="form-label mb-1 form-label-muted">Status</label>
                                            <select name="nfe_status" class="form-select form-select-sm" style="border-radius:8px;font-size:.8rem;">
                                                <option value="">— Todos —</option>
                                                <option value="autorizada">Autorizada</option>
                                                <option value="cancelada">Cancelada</option>
                                                <option value="rejeitada">Rejeitada</option>
                                                <option value="inutilizada">Inutilizada</option>
                                                <option value="processando">Processando</option>
                                                <option value="rascunho">Rascunho</option>
                                            </select>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label mb-1 form-label-muted">Modelo</label>
                                            <select name="nfe_modelo" class="form-select form-select-sm" style="border-radius:8px;font-size:.8rem;">
                                                <option value="">— Todos —</option>
                                                <option value="55">NF-e (55)</option>
                                                <option value="65">NFC-e (65)</option>
                                            </select>
                                        </div>
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

                    <!-- ── 2. Resumo de Impostos ── -->
                    <div class="col-xl-6">
                        <div class="card border-0 shadow-sm h-100 rpt-card">
                            <div class="card-header py-2 card-header-carrot">
                                <h6 class="mb-0 text-white" style="font-size:.85rem;">
                                    <i class="fas fa-calculator me-2"></i>Resumo de Impostos
                                </h6>
                            </div>
                            <div class="card-body p-3 d-flex flex-column">
                                <p class="rpt-desc mb-3">Totalização de ICMS, PIS, COFINS e IPI das NF-e autorizadas, com detalhamento por NCM e CFOP.</p>
                                <form class="rpt-form mt-auto" data-type="tax_summary">
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

                    <!-- ── 3. NF-e por Cliente ── -->
                    <div class="col-xl-6">
                        <div class="card border-0 shadow-sm h-100 rpt-card">
                            <div class="card-header py-2 card-header-green-alt">
                                <h6 class="mb-0 text-white" style="font-size:.85rem;">
                                    <i class="fas fa-users me-2"></i>NF-e por Cliente
                                </h6>
                            </div>
                            <div class="card-body p-3 d-flex flex-column">
                                <p class="rpt-desc mb-3">Ranking de clientes por volume e valor de NF-e autorizadas. Filtre por cliente específico ou veja todos.</p>
                                <form class="rpt-form-custom mt-auto" data-type="nfes_customer">
                                    <?= csrf_field() ?>
                                    <div class="row g-2 mb-2 rpt-period-row">
                                        <div class="col-6">
                                            <label class="form-label mb-1">De</label>
                                            <input type="date" class="form-control form-control-sm" name="start" required value="<?= eAttr(date('Y-m-01')) ?>">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label mb-1">Até</label>
                                            <input type="date" class="form-control form-control-sm" name="end" required value="<?= eAttr(date('Y-m-d')) ?>">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label mb-1 form-label-muted">Cliente (opcional)</label>
                                        <select name="customer_id" class="form-select form-select-sm" style="border-radius:8px;font-size:.8rem;">
                                            <option value="">— Todos os clientes —</option>
                                            <?php foreach ($nfeCustomersList as $ncItem): ?>
                                                <option value="<?= eAttr($ncItem['id']) ?>"><?= e($ncItem['name']) ?></option>
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

                    <!-- ── 4. Resumo por CFOP ── -->
                    <div class="col-xl-6">
                        <div class="card border-0 shadow-sm h-100 rpt-card">
                            <div class="card-header py-2 card-header-grape-alt">
                                <h6 class="mb-0 text-white" style="font-size:.85rem;">
                                    <i class="fas fa-layer-group me-2"></i>Resumo por CFOP
                                </h6>
                            </div>
                            <div class="card-body p-3 d-flex flex-column">
                                <p class="rpt-desc mb-3">Agrupamento por código CFOP com descrição, quantidade de itens, NF-e envolvidas, valor total e ICMS apurado.</p>
                                <form class="rpt-form mt-auto" data-type="cfop_summary">
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

                    <!-- ── 5. NF-e Canceladas ── -->
                    <div class="col-xl-6">
                        <div class="card border-0 shadow-sm h-100 rpt-card">
                            <div class="card-header py-2 card-header-red">
                                <h6 class="mb-0 text-white" style="font-size:.85rem;">
                                    <i class="fas fa-ban me-2"></i>NF-e Canceladas
                                </h6>
                            </div>
                            <div class="card-body p-3 d-flex flex-column">
                                <p class="rpt-desc mb-3">Notas fiscais canceladas com motivo do cancelamento, protocolo, data de emissão e data do cancelamento.</p>
                                <form class="rpt-form mt-auto" data-type="cancelled_nfes">
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

                    <!-- ── 6. Inutilizações ── -->
                    <div class="col-xl-6">
                        <div class="card border-0 shadow-sm h-100 rpt-card">
                            <div class="card-header py-2 card-header-gray">
                                <h6 class="mb-0 text-white" style="font-size:.85rem;">
                                    <i class="fas fa-hashtag me-2"></i>Inutilizações
                                </h6>
                            </div>
                            <div class="card-body p-3 d-flex flex-column">
                                <p class="rpt-desc mb-3">Registro de numerações inutilizadas junto à SEFAZ, com série, modelo, protocolo e justificativa.</p>
                                <form class="rpt-form mt-auto" data-type="inutilizacoes">
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

                    <!-- ── 7. Logs de Comunicação SEFAZ ── -->
                    <div class="col-xl-6">
                        <div class="card border-0 shadow-sm h-100 rpt-card">
                            <div class="card-header py-2 card-header-dark">
                                <h6 class="mb-0 text-white" style="font-size:.85rem;">
                                    <i class="fas fa-satellite-dish me-2"></i>Logs SEFAZ
                                </h6>
                            </div>
                            <div class="card-body p-3 d-flex flex-column">
                                <p class="rpt-desc mb-3">Histórico completo de comunicações com a SEFAZ — emissões, consultas, cancelamentos, erros e retornos.</p>
                                <form class="rpt-form-custom mt-auto" data-type="sefaz_logs">
                                    <?= csrf_field() ?>
                                    <div class="row g-2 mb-2 rpt-period-row">
                                        <div class="col-6">
                                            <label class="form-label mb-1">De</label>
                                            <input type="date" class="form-control form-control-sm" name="start" required value="<?= eAttr(date('Y-m-01')) ?>">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label mb-1">Até</label>
                                            <input type="date" class="form-control form-control-sm" name="end" required value="<?= eAttr(date('Y-m-d')) ?>">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label mb-1 form-label-muted">Tipo de Ação</label>
                                        <select name="log_action" class="form-select form-select-sm" style="border-radius:8px;font-size:.8rem;">
                                            <option value="">— Todas as ações —</option>
                                            <option value="emissao">Emissão</option>
                                            <option value="consulta">Consulta</option>
                                            <option value="cancelamento">Cancelamento</option>
                                            <option value="correcao">Carta de Correção</option>
                                            <option value="inutilizacao">Inutilização</option>
                                            <option value="contingencia">Contingência</option>
                                            <option value="status_servico">Status Serviço</option>
                                            <option value="error">Erro</option>
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

    // ── Custom form buttons (catálogo de produtos, estoque, comissões — campos customizados) ──
    document.querySelectorAll('.rpt-form-custom .rpt-custom-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var form   = this.closest('.rpt-form-custom');
            var type   = form.dataset.type;
            var action = this.dataset.action;

            // Validar período se existir
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

            // Build URL with all form fields
            var url = '?page=reports&action=' + encodeURIComponent(action)
                    + '&type=' + encodeURIComponent(type);

            // Date inputs
            if (start && end) {
                url += '&start=' + encodeURIComponent(start.value)
                     + '&end='   + encodeURIComponent(end.value);
            }

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
