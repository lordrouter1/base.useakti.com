<?php
/**
 * Estoque â€” PÃ¡gina Unificada com Sidebar
 * Layout inspirado na pÃ¡gina de relatÃ³rios: sidebar com seÃ§Ãµes Ã  esquerda,
 * conteÃºdo da seÃ§Ã£o ativa Ã  direita.
 *
 * VariÃ¡veis disponÃ­veis (carregadas pelo StockController::index):
 *   $warehouses, $warehousesAll, $summary, $lowStockItems
 *   $movFilters (valores iniciais dos filtros)
 *   $products
 *   $limitReached, $limitInfo, $maxWarehouses, $currentWarehouses
 *
 * Tabelas de VisÃ£o Geral e MovimentaÃ§Ãµes sÃ£o carregadas via AJAX
 * com filtros dinÃ¢micos e paginaÃ§Ã£o.
 */

$activeSection = $_GET['section'] ?? 'overview';
$validSections = ['overview', 'movements', 'entry', 'warehouses'];
if (!in_array($activeSection, $validSections)) $activeSection = 'overview';

$currentWarehouse = $_GET['warehouse_id'] ?? '';
$currentSearch = $_GET['search'] ?? '';
$isLowStock = isset($_GET['low_stock']) && $_GET['low_stock'] == '1';

// Filtros de movimentaÃ§Ã£o
$fWarehouse = $_GET['mov_warehouse_id'] ?? '';
$fProduct   = $_GET['mov_product_id'] ?? '';
$fType      = $_GET['mov_type'] ?? '';
$fDateFrom  = $_GET['mov_date_from'] ?? '';
$fDateTo    = $_GET['mov_date_to'] ?? '';
?>

<!-- â•â•â•â•â•â• Flash messages â•â•â•â•â•â• -->
<?php require 'app/views/components/flash-messages.php'; ?>

<!-- Styles loaded from assets/css/modules/stock.css via header.php -->

<div class="container-fluid py-3">

    <!-- â•â•â•â•â•â• Header â•â•â•â•â•â• -->
    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div>
            <h1 class="h2 mb-1"><i class="fas fa-warehouse me-2 text-primary"></i>Controle de Estoque</h1>
            <p class="text-muted mb-0" style="font-size:.82rem;">Gerencie o estoque, movimentaÃ§Ãµes, entradas/saÃ­das e armazÃ©ns.</p>
        </div>
    </div>

    <div id="stockApp" data-active-section="<?= e($activeSection) ?>" data-status="<?= e($_GET['status'] ?? '') ?>" class="row g-4">

        <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
        <!-- SIDEBAR â€” Menu Lateral de SeÃ§Ãµes (3/12)         -->
        <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
        <div class="col-lg-3 stk-sidebar-col">
            <div class="card border-0 shadow-sm" style="border-radius:12px;">
                <div class="card-body p-3">
                    <nav class="stk-sidebar">

                        <div class="stk-sidebar-label">Estoque</div>

                        <a href="#" class="stk-nav-item <?= $activeSection === 'overview' ? 'active' : '' ?>" data-section="overview">
                            <span class="stk-nav-icon nav-icon-blue">
                                <i class="fas fa-tachometer-alt"></i>
                            </span>
                            <span>VisÃ£o Geral</span>
                            <span class="stk-nav-count nav-icon-blue"><?= $summary['total_items'] ?></span>
                        </a>

                        <a href="#" class="stk-nav-item <?= $activeSection === 'movements' ? 'active' : '' ?>" data-section="movements">
                            <span class="stk-nav-icon nav-icon-purple">
                                <i class="fas fa-exchange-alt"></i>
                            </span>
                            <span>MovimentaÃ§Ãµes</span>
                        </a>

                        <div class="stk-sidebar-divider"></div>

                        <a href="#" class="stk-nav-item <?= $activeSection === 'entry' ? 'active' : '' ?>" data-section="entry">
                            <span class="stk-nav-icon nav-icon-green">
                                <i class="fas fa-arrow-right-arrow-left"></i>
                            </span>
                            <span>Entrada / SaÃ­da</span>
                        </a>

                        <div class="stk-sidebar-divider"></div>

                        <a href="#" class="stk-nav-item <?= $activeSection === 'warehouses' ? 'active' : '' ?>" data-section="warehouses">
                            <span class="stk-nav-icon nav-icon-orange">
                                <i class="fas fa-building"></i>
                            </span>
                            <span>ArmazÃ©ns</span>
                            <span class="stk-nav-count nav-icon-orange"><?= $summary['total_warehouses'] ?></span>
                        </a>

                    </nav>
                </div>
            </div>

            <!-- Mini-dica -->
            <div class="card border-0 shadow-sm mt-3 d-none d-lg-block" style="border-radius:12px;">
                <div class="card-body p-3">
                    <h6 class="mb-2 fw-bold text-info-alt" style="font-size:.78rem;">
                        <i class="fas fa-lightbulb me-1"></i>Dica
                    </h6>
                    <p class="mb-0 text-muted" style="font-size:.72rem;line-height:1.55;">
                        Use a <span class="fw-bold text-primary">VisÃ£o Geral</span> para monitorar o estoque,
                        <span class="fw-bold text-success">Entrada/SaÃ­da</span> para registrar movimentaÃ§Ãµes
                        e <span class="fw-bold text-warning">ArmazÃ©ns</span> para gerenciar seus locais de armazenamento.
                    </p>
                </div>
            </div>

            <!-- Alertas de Estoque Baixo (sidebar) -->
            <?php if (!empty($lowStockItems)): ?>
            <div class="card border-0 shadow-sm mt-3 border-start border-danger border-4" style="border-radius:12px;">
                <div class="card-body p-3">
                    <h6 class="mb-2 fw-bold text-danger" style="font-size:.78rem;">
                        <i class="fas fa-exclamation-triangle me-1"></i>Estoque Baixo
                    </h6>
                    <?php foreach (array_slice($lowStockItems, 0, 3) as $lsi): ?>
                    <div class="d-flex justify-content-between align-items-center mb-1" style="font-size:.7rem;">
                        <span class="text-truncate me-2"><?= e($lsi['product_name']) ?></span>
                        <span class="badge bg-danger"><?= intval($lsi['quantity']) ?>/<?= intval($lsi['min_quantity']) ?></span>
                    </div>
                    <?php endforeach; ?>
                    <?php if (count($lowStockItems) > 3): ?>
                    <a href="#" class="small text-danger stk-nav-link-overview" style="font-size:.68rem;">
                        +<?= count($lowStockItems) - 3 ?> mais...
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
        <!-- CONTEÃšDO PRINCIPAL â€” SeÃ§Ã£o Ativa (9/12)         -->
        <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
        <div class="col-lg-9">

            <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
            <!-- SEÃ‡ÃƒO: VisÃ£o Geral                      -->
            <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
            <div class="stk-section <?= $activeSection === 'overview' ? 'active' : '' ?>" id="stk-overview">

                <div class="d-flex align-items-center mb-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-2 nav-icon-blue" style="width:34px;height:34px;">
                        <i class="fas fa-tachometer-alt" style="font-size:.85rem;"></i>
                    </div>
                    <div>
                        <h5 class="mb-0" style="font-size:1rem;">VisÃ£o Geral do Estoque</h5>
                        <p class="text-muted mb-0" style="font-size:.72rem;">Resumo e listagem completa do estoque atual.</p>
                    </div>
                </div>

                <!-- Cards de Resumo -->
                <div class="row g-3 mb-4">
                    <div class="col-xl-3 col-md-4 col-6">
                        <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
                            <div class="card-body d-flex align-items-center p-3">
                                <div class="icon-circle icon-circle-lg icon-circle-primary me-3">
                                    <i class="fas fa-building text-primary"></i>
                                </div>
                                <div>
                                    <div class="text-muted small fw-bold text-uppercase" style="font-size:.65rem;">ArmazÃ©ns</div>
                                    <div class="fw-bold fs-5 text-primary"><?= $summary['total_warehouses'] ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-4 col-6">
                        <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
                            <div class="card-body d-flex align-items-center p-3">
                                <div class="icon-circle icon-circle-lg icon-circle-green me-3">
                                    <i class="fas fa-box-open text-success"></i>
                                </div>
                                <div>
                                    <div class="text-muted small fw-bold text-uppercase" style="font-size:.65rem;">Produtos</div>
                                    <div class="fw-bold fs-5 text-success"><?= $summary['products_in_stock'] ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-4 col-6">
                        <div class="card border-0 shadow-sm h-100 border-start border-warning border-4">
                            <div class="card-body d-flex align-items-center p-3">
                                <div class="icon-circle icon-circle-lg icon-circle-warning me-3">
                                    <i class="fas fa-dollar-sign text-warning"></i>
                                </div>
                                <div>
                                    <div class="text-muted small fw-bold text-uppercase" style="font-size:.65rem;">Valor Total</div>
                                    <div class="fw-bold fs-6 text-warning">R$ <?= number_format($summary['total_value'], 2, ',', '.') ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-4 col-6">
                        <div class="card border-0 shadow-sm h-100 border-start border-danger border-4 <?= $summary['low_stock_count'] > 0 ? 'bg-danger bg-opacity-10' : '' ?>">
                            <div class="card-body d-flex align-items-center p-3">
                                <div class="icon-circle icon-circle-lg icon-circle-danger me-3">
                                    <i class="fas fa-exclamation-triangle text-danger"></i>
                                </div>
                                <div>
                                    <div class="text-muted small fw-bold text-uppercase" style="font-size:.65rem;">Estoque Baixo</div>
                                    <div class="fw-bold fs-5 text-danger"><?= $summary['low_stock_count'] ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body p-3">
                        <div class="row g-2 align-items-end" id="overviewFilterForm">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold mb-1">ArmazÃ©m</label>
                                <select id="ov_warehouse" class="form-select form-select-sm ov-filter">
                                    <option value="">Todos os ArmazÃ©ns</option>
                                    <?php foreach ($warehouses as $wh): ?>
                                        <option value="<?= $wh['id'] ?>" <?= $currentWarehouse == $wh['id'] ? 'selected' : '' ?>>
                                            <?= e($wh['name']) ?> (<?= $wh['total_items'] ?> itens)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold mb-1">Buscar</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" id="ov_search" class="form-control ov-filter" placeholder="Produto, variaÃ§Ã£o ou localizaÃ§Ã£o..." value="<?= eAttr($currentSearch) ?>">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-check mt-3">
                                    <input type="checkbox" class="form-check-input ov-filter" id="ov_low_stock" value="1" <?= $isLowStock ? 'checked' : '' ?>>
                                    <label class="form-check-label small" for="ov_low_stock"><i class="fas fa-exclamation-triangle text-warning me-1"></i>Baixo</label>
                                </div>
                            </div>
                            <div class="col-md-2 text-end">
                                <a href="#" class="text-muted small" id="btnClearOverview" style="font-size:.72rem;"><i class="fas fa-times me-1"></i>Limpar filtros</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabela de Estoque -->
                <div class="table-responsive bg-white rounded shadow-sm">
                    <table class="table table-hover align-middle mb-0" id="stockTable">
                        <caption class="visually-hidden">Itens em estoque</caption>
                        <thead class="bg-light">
                            <tr>
                                <th class="py-3 ps-4" style="width:50px;"></th>
                                <th class="py-3">Produto</th>
                                <th class="py-3">VariaÃ§Ã£o</th>
                                <th class="py-3">ArmazÃ©m</th>
                                <th class="py-3 text-center">Quantidade</th>
                                <th class="py-3 text-center">MÃ­nimo</th>
                                <th class="py-3">LocalizaÃ§Ã£o</th>
                                <th class="py-3 text-end pe-4">AÃ§Ãµes</th>
                            </tr>
                        </thead>
                        <tbody id="stockTableBody">
                            <tr><td colspan="8" class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin me-1"></i>Carregando...</td></tr>
                        </tbody>
                    </table>
                </div>
                <!-- PaginaÃ§Ã£o VisÃ£o Geral -->
                <div class="d-flex justify-content-between align-items-center mt-2">
                    <span class="text-muted small" id="ovPaginationInfo"></span>
                    <nav><ul class="pagination pagination-sm mb-0" id="ovPagination"></ul></nav>
                </div>

            </div><!-- /.stk-section overview -->


            <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
            <!-- SEÃ‡ÃƒO: MovimentaÃ§Ãµes                    -->
            <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
            <div class="stk-section <?= $activeSection === 'movements' ? 'active' : '' ?>" id="stk-movements">

                <div class="d-flex align-items-center mb-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-2 nav-icon-purple" style="width:34px;height:34px;">
                        <i class="fas fa-exchange-alt" style="font-size:.85rem;"></i>
                    </div>
                    <div>
                        <h5 class="mb-0" style="font-size:1rem;">HistÃ³rico de MovimentaÃ§Ãµes</h5>
                        <p class="text-muted mb-0" style="font-size:.72rem;">Todas as entradas, saÃ­das, ajustes e transferÃªncias registradas.</p>
                    </div>
                </div>

                <!-- Filtros de MovimentaÃ§Ãµes -->
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body p-3">
                        <div class="row g-2 align-items-end" id="movFilterForm">
                            <div class="col-md-2">
                                <label class="form-label small fw-bold mb-1">ArmazÃ©m</label>
                                <select id="mov_warehouse" class="form-select form-select-sm mov-filter">
                                    <option value="">Todos</option>
                                    <?php foreach ($warehouses as $wh): ?>
                                        <option value="<?= $wh['id'] ?>" <?= $fWarehouse == $wh['id'] ? 'selected' : '' ?>><?= e($wh['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold mb-1">Produto</label>
                                <select id="mov_product" class="form-select form-select-sm mov-filter">
                                    <option value="">Todos</option>
                                    <?php foreach ($products as $p): ?>
                                        <option value="<?= $p['id'] ?>" <?= $fProduct == $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold mb-1">Tipo</label>
                                <select id="mov_type_filter" class="form-select form-select-sm mov-filter">
                                    <option value="">Todos</option>
                                    <option value="entrada" <?= $fType === 'entrada' ? 'selected' : '' ?>>Entrada</option>
                                    <option value="saida" <?= $fType === 'saida' ? 'selected' : '' ?>>SaÃ­da</option>
                                    <option value="ajuste" <?= $fType === 'ajuste' ? 'selected' : '' ?>>Ajuste</option>
                                    <option value="transferencia" <?= $fType === 'transferencia' ? 'selected' : '' ?>>TransferÃªncia</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold mb-1">De</label>
                                <input type="date" id="mov_date_from" class="form-control form-control-sm mov-filter" value="<?= eAttr($fDateFrom) ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold mb-1">AtÃ©</label>
                                <input type="date" id="mov_date_to" class="form-control form-control-sm mov-filter" value="<?= eAttr($fDateTo) ?>">
                            </div>
                            <div class="col-md-1 text-end">
                                <a href="#" class="text-muted small" id="btnClearMov" style="font-size:.72rem;"><i class="fas fa-times me-1"></i>Limpar</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabela de MovimentaÃ§Ãµes -->
                <div class="table-responsive bg-white rounded shadow-sm">
                    <table class="table table-hover table-sm align-middle mb-0">
                        <caption class="visually-hidden">Movimentações de estoque</caption>
                        <thead class="bg-light">
                            <tr>
                                <th class="py-2 ps-3" style="width:50px;">#</th>
                                <th class="py-2">Data</th>
                                <th class="py-2">Tipo</th>
                                <th class="py-2">Produto</th>
                                <th class="py-2">VariaÃ§Ã£o</th>
                                <th class="py-2">ArmazÃ©m</th>
                                <th class="py-2 text-center">Qtd</th>
                                <th class="py-2 text-center">Antes</th>
                                <th class="py-2 text-center">Depois</th>
                                <th class="py-2">Motivo</th>
                                <th class="py-2">UsuÃ¡rio</th>
                                <th class="py-2 text-center" style="width:80px;">AÃ§Ãµes</th>
                            </tr>
                        </thead>
                        <tbody id="movTableBody">
                            <tr><td colspan="12" class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin me-1"></i>Carregando...</td></tr>
                        </tbody>
                    </table>
                </div>
                <!-- PaginaÃ§Ã£o MovimentaÃ§Ãµes -->
                <div class="d-flex justify-content-between align-items-center mt-2">
                    <span class="text-muted small" id="movPaginationInfo"></span>
                    <nav><ul class="pagination pagination-sm mb-0" id="movPagination"></ul></nav>
                </div>

            </div><!-- /.stk-section movements -->


            <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
            <!-- SEÃ‡ÃƒO: Entrada / SaÃ­da                  -->
            <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
            <div class="stk-section <?= $activeSection === 'entry' ? 'active' : '' ?>" id="stk-entry">

                <div class="d-flex align-items-center mb-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-2 nav-icon-green" style="width:34px;height:34px;">
                        <i class="fas fa-arrow-right-arrow-left" style="font-size:.85rem;"></i>
                    </div>
                    <div>
                        <h5 class="mb-0" style="font-size:1rem;">MovimentaÃ§Ã£o de Estoque</h5>
                        <p class="text-muted mb-0" style="font-size:.72rem;">Registre entradas, saÃ­das, ajustes e transferÃªncias.</p>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Painel de MovimentaÃ§Ã£o -->
                    <div class="col-xl-8">
                        <div class="card shadow-sm">
                            <div class="card-body p-4">

                                <!-- Tipo de MovimentaÃ§Ã£o -->
                                <div class="mb-4">
                                    <label class="form-label fw-bold">Tipo de MovimentaÃ§Ã£o</label>
                                    <div class="btn-group w-100" role="group" id="movTypeGroup">
                                        <input type="radio" class="btn-check" name="mov_type_entry" id="typeEntrada" value="entrada" checked>
                                        <label class="btn btn-outline-success" for="typeEntrada"><i class="fas fa-arrow-down me-1"></i>Entrada</label>

                                        <input type="radio" class="btn-check" name="mov_type_entry" id="typeSaida" value="saida">
                                        <label class="btn btn-outline-danger" for="typeSaida"><i class="fas fa-arrow-up me-1"></i>SaÃ­da</label>

                                        <input type="radio" class="btn-check" name="mov_type_entry" id="typeAjuste" value="ajuste">
                                        <label class="btn btn-outline-warning" for="typeAjuste"><i class="fas fa-sliders-h me-1"></i>Ajuste</label>

                                        <input type="radio" class="btn-check" name="mov_type_entry" id="typeTransfer" value="transferencia">
                                        <label class="btn btn-outline-info" for="typeTransfer"><i class="fas fa-truck me-1"></i>TransferÃªncia</label>
                                    </div>
                                </div>

                                <!-- ArmazÃ©m Origem -->
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">ArmazÃ©m <span class="text-danger">*</span></label>
                                        <select class="form-select" id="selWarehouse" required>
                                            <option value="">Selecione o armazÃ©m...</option>
                                            <?php foreach ($warehouses as $wh): ?>
                                                <option value="<?= $wh['id'] ?>"><?= e($wh['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6" id="destWarehouseWrap" style="display:none;">
                                        <label class="form-label fw-bold">ArmazÃ©m Destino <span class="text-danger">*</span></label>
                                        <select class="form-select" id="selDestWarehouse">
                                            <option value="">Selecione o destino...</option>
                                            <?php foreach ($warehouses as $wh): ?>
                                                <option value="<?= $wh['id'] ?>"><?= e($wh['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Motivo -->
                                <div class="mb-4">
                                    <label class="form-label fw-bold">Motivo / ObservaÃ§Ã£o</label>
                                    <input type="text" class="form-control" id="movReason" placeholder="Ex: Compra fornecedor, Venda avulsa, CorreÃ§Ã£o inventÃ¡rio...">
                                </div>

                                <hr>

                                <!-- Adicionar Produtos -->
                                <h6 class="fw-bold mb-3"><i class="fas fa-box-open me-2 text-primary"></i>Produtos</h6>

                                <div class="row g-2 mb-3 align-items-end" id="addProductRow">
                                    <div class="col-md-5">
                                        <label class="form-label small fw-bold">Produto</label>
                                        <select class="form-select form-select-sm" id="selProduct">
                                            <option value="">Selecione um produto...</option>
                                            <?php foreach ($products as $p): ?>
                                                <option value="<?= $p['id'] ?>" data-name="<?= eAttr($p['name']) ?>" data-cat="<?= eAttr($p['category_name'] ?? '') ?>">
                                                    <?= e($p['name']) ?>
                                                    <?= $p['category_name'] ? ' (' . e($p['category_name']) . ')' : '' ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3" id="combWrap" style="display:none;">
                                        <label class="form-label small fw-bold">VariaÃ§Ã£o</label>
                                        <select class="form-select form-select-sm" id="selCombination">
                                            <option value="">Sem variaÃ§Ã£o</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small fw-bold" id="lblQty">Quantidade</label>
                                        <input type="number" class="form-control form-control-sm" id="inputQty" min="0.01" step="1" value="1" placeholder="Qtd">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-primary btn-sm w-100" id="btnAddItem">
                                            <i class="fas fa-plus me-1"></i>Adicionar
                                        </button>
                                    </div>
                                </div>

                                <!-- Tabela de itens adicionados -->
                                <div class="table-responsive" style="padding:0; border:none; box-shadow:none;">
                                    <table class="table table-sm table-bordered align-middle mb-0" id="itemsTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Produto</th>
                                                <th>VariaÃ§Ã£o</th>
                                                <th class="text-center" style="width:120px;">Quantidade</th>
                                                <th style="width:50px;"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="itemsBody">
                                            <tr id="emptyItemsRow">
                                                <td colspan="4" class="text-center text-muted py-3">
                                                    <i class="fas fa-inbox me-1"></i>Adicione produtos acima
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <hr>

                                <!-- BotÃ£o Processar -->
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted small" id="itemsCountLabel">0 item(s)</span>
                                    <button type="button" class="btn btn-lg btn-success" id="btnProcess" disabled>
                                        <i class="fas fa-check-circle me-2"></i>Processar MovimentaÃ§Ã£o
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Painel Lateral: InstruÃ§Ãµes -->
                    <div class="col-xl-4">
                        <div class="card shadow-sm mb-3">
                            <div class="card-header bg-white py-2">
                                <h6 class="mb-0"><i class="fas fa-info-circle text-primary me-2"></i>Como funciona</h6>
                            </div>
                            <div class="card-body small">
                                <div class="mb-3" id="helpEntrada">
                                    <span class="badge bg-success me-1">Entrada</span>
                                    Adiciona unidades ao estoque. Use para: compras de fornecedor, devoluÃ§Ãµes, produÃ§Ã£o.
                                </div>
                                <div class="mb-3" id="helpSaida" style="display:none;">
                                    <span class="badge bg-danger me-1">SaÃ­da</span>
                                    Remove unidades do estoque. Use para: vendas avulsas, perdas, descarte.
                                </div>
                                <div class="mb-3" id="helpAjuste" style="display:none;">
                                    <span class="badge bg-warning text-dark me-1">Ajuste</span>
                                    Define o saldo exato do item. Use para: inventÃ¡rio, correÃ§Ã£o de divergÃªncias.
                                </div>
                                <div class="mb-3" id="helpTransfer" style="display:none;">
                                    <span class="badge bg-info me-1">TransferÃªncia</span>
                                    Move unidades entre armazÃ©ns. A saÃ­da do origem e a entrada no destino sÃ£o registradas automaticamente.
                                </div>
                                <hr>
                                <ol class="ps-3 mb-0">
                                    <li>Selecione o tipo de movimentaÃ§Ã£o</li>
                                    <li>Escolha o armazÃ©m</li>
                                    <li>Adicione os produtos e quantidades</li>
                                    <li>Clique em <strong>Processar</strong></li>
                                </ol>
                            </div>
                        </div>

                        <!-- HistÃ³rico Recente (mini) -->
                        <div class="card shadow-sm">
                            <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><i class="fas fa-history text-muted me-2"></i>Ãšltimas MovimentaÃ§Ãµes</h6>
                                <a href="#" class="btn btn-sm btn-outline-secondary py-0 px-2 stk-go-movements" style="font-size:0.7rem;">Ver Todas</a>
                            </div>
                            <div class="card-body p-0" id="recentMovements" style="max-height:300px;overflow-y:auto;">
                                <div class="text-center text-muted small py-3"><i class="fas fa-spinner fa-spin me-1"></i>Carregando...</div>
                            </div>
                        </div>
                    </div>
                </div>

            </div><!-- /.stk-section entry -->


            <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
            <!-- SEÃ‡ÃƒO: ArmazÃ©ns                         -->
            <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
            <div class="stk-section <?= $activeSection === 'warehouses' ? 'active' : '' ?>" id="stk-warehouses">

                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle d-flex align-items-center justify-content-center me-2 nav-icon-orange" style="width:34px;height:34px;">
                            <i class="fas fa-building" style="font-size:.85rem;"></i>
                        </div>
                        <div>
                            <h5 class="mb-0" style="font-size:1rem;">ArmazÃ©ns / Locais de Estoque</h5>
                            <p class="text-muted mb-0" style="font-size:.72rem;">Gerencie seus armazÃ©ns e locais de armazenamento.</p>
                        </div>
                    </div>
                    <div>
                        <?php if (!empty($limitReached)): ?>
                            <button type="button" class="btn btn-sm btn-primary disabled" disabled title="Limite do plano atingido">
                                <i class="fas fa-plus me-1"></i> Novo ArmazÃ©m
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#warehouseModal" onclick="openNewWarehouse()">
                                <i class="fas fa-plus me-1"></i> Novo ArmazÃ©m
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($limitReached)): ?>
                <div class="alert alert-warning border-warning d-flex align-items-center mb-3" role="alert">
                    <i class="fas fa-exclamation-triangle fs-5 me-3 text-warning"></i>
                    <div>
                        <strong>Limite do plano atingido!</strong> VocÃª possui <strong><?= $limitInfo['current'] ?></strong> de <strong><?= $limitInfo['max'] ?></strong> armazÃ©ns permitidos.
                        <span class="text-muted">Para cadastrar mais armazÃ©ns, entre em contato com o suporte para fazer um upgrade do seu plano.</span>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Lista de ArmazÃ©ns -->
                <div class="row g-3">
                    <?php foreach ($warehousesAll as $wh): ?>
                    <div class="col-md-6 col-xl-4">
                        <div class="card h-100 shadow-sm warehouse-card <?= $wh['is_active'] ? '' : 'opacity-50' ?>">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                                <h5 class="mb-0" style="font-size:.95rem;">
                                    <i class="fas fa-warehouse me-2 text-primary"></i><?= e($wh['name']) ?>
                                    <?php if (!empty($wh['is_default'])): ?>
                                        <span class="badge bg-success ms-1" style="font-size:.6rem;"><i class="fas fa-star me-1"></i>PadrÃ£o</span>
                                    <?php endif; ?>
                                    <?php if (!$wh['is_active']): ?>
                                        <span class="badge bg-secondary ms-1" style="font-size:.6rem;">Inativo</span>
                                    <?php endif; ?>
                                </h5>
                                <div class="btn-group btn-group-sm">
                                    <?php if (empty($wh['is_default']) && $wh['is_active']): ?>
                                    <button type="button" class="btn btn-outline-success btn-set-default-wh"
                                            data-id="<?= $wh['id'] ?>" data-name="<?= eAttr($wh['name']) ?>"
                                            title="Definir como padrÃ£o">
                                        <i class="fas fa-star"></i>
                                    </button>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-outline-primary btn-edit-wh"
                                            data-id="<?= $wh['id'] ?>"
                                            data-name="<?= eAttr($wh['name']) ?>"
                                            data-address="<?= eAttr($wh['address'] ?? '') ?>"
                                            data-city="<?= eAttr($wh['city'] ?? '') ?>"
                                            data-state="<?= eAttr($wh['state'] ?? '') ?>"
                                            data-zip="<?= eAttr($wh['zip_code'] ?? '') ?>"
                                            data-phone="<?= eAttr($wh['phone'] ?? '') ?>"
                                            data-notes="<?= eAttr($wh['notes'] ?? '') ?>"
                                            data-active="<?= $wh['is_active'] ?>"
                                            data-default="<?= $wh['is_default'] ?? 0 ?>"
                                            title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($wh['total_items'] == 0): ?>
                                    <button type="button" class="btn btn-outline-danger btn-delete-wh"
                                            data-id="<?= $wh['id'] ?>" data-name="<?= eAttr($wh['name']) ?>" title="Excluir">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-body py-3">
                                <?php if ($wh['address']): ?>
                                <p class="mb-1 small"><i class="fas fa-map-marker-alt text-danger me-2 opacity-50"></i><?= e($wh['address']) ?></p>
                                <?php endif; ?>
                                <?php if ($wh['city'] || $wh['state']): ?>
                                <p class="mb-1 small"><i class="fas fa-city text-muted me-2 opacity-50"></i><?= e(trim($wh['city'] . ' - ' . $wh['state'], ' - ')) ?></p>
                                <?php endif; ?>
                                <?php if ($wh['zip_code']): ?>
                                <p class="mb-1 small"><i class="fas fa-envelope text-muted me-2 opacity-50"></i>CEP: <?= e($wh['zip_code']) ?></p>
                                <?php endif; ?>
                                <?php if ($wh['phone']): ?>
                                <p class="mb-1 small"><i class="fas fa-phone text-muted me-2 opacity-50"></i><?= e($wh['phone']) ?></p>
                                <?php endif; ?>
                                <?php if ($wh['notes']): ?>
                                <p class="mb-0 small text-muted fst-italic mt-2"><?= e($wh['notes']) ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer bg-light d-flex justify-content-between small text-muted">
                                <span><i class="fas fa-box me-1"></i><?= $wh['total_items'] ?> itens</span>
                                <span><i class="fas fa-cubes me-1"></i><?= number_format($wh['total_quantity'], 0) ?> unidades</span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <?php if (empty($warehousesAll)): ?>
                    <div class="col-12 text-center py-5 text-muted">
                        <i class="fas fa-building fa-3x mb-3 d-block text-secondary"></i>
                        Nenhum armazÃ©m cadastrado ainda.
                    </div>
                    <?php endif; ?>
                </div>

            </div><!-- /.stk-section warehouses -->

        </div><!-- /.col-lg-9 -->

    </div><!-- /.row -->

</div><!-- /.container-fluid -->


<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<!-- MODAL: Editar MÃ­nimo / LocalizaÃ§Ã£o                                -->
<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<div class="modal fade" id="editMetaModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title"><i class="fas fa-cog me-1"></i>Configurar Item</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="fw-bold mb-3 text-primary" id="metaItemName"></p>
                <input type="hidden" id="metaItemId">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Estoque MÃ­nimo</label>
                    <input type="number" class="form-control" id="metaMinQty" min="0" step="1" placeholder="0">
                    <div class="form-text">Alerta quando atingir este valor.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">LocalizaÃ§Ã£o FÃ­sica</label>
                    <input type="text" class="form-control" id="metaLocCode" placeholder="Ex: A1-P3, Prateleira 5">
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-sm btn-primary" id="btnSaveMeta"><i class="fas fa-save me-1"></i>Salvar</button>
            </div>
        </div>
    </div>
</div>

<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<!-- MODAL: Criar / Editar ArmazÃ©m                                     -->
<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<div class="modal fade" id="warehouseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="warehouseForm" method="post">
                <?= csrf_field() ?>
                <div class="modal-header bg-primary py-2">
                    <h5 class="modal-title text-white" id="whModalTitle"><i class="fas fa-warehouse me-2"></i>Novo ArmazÃ©m</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="wh_id">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nome do ArmazÃ©m <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" id="wh_name" required placeholder="Ex: Estoque Principal, DepÃ³sito 2...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">EndereÃ§o</label>
                        <input type="text" class="form-control" name="address" id="wh_address" placeholder="Rua, nÃºmero, complemento">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-5">
                            <label class="form-label small fw-bold">Cidade</label>
                            <input type="text" class="form-control" name="city" id="wh_city">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">UF</label>
                            <input type="text" class="form-control" name="state" id="wh_state" maxlength="2" placeholder="SP">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">CEP</label>
                            <input type="text" class="form-control" name="zip_code" id="wh_zip" placeholder="00000-000">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Telefone</label>
                        <input type="text" class="form-control" name="phone" id="wh_phone" placeholder="(11) 99999-0000">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">ObservaÃ§Ãµes</label>
                        <textarea class="form-control" name="notes" id="wh_notes" rows="2"></textarea>
                    </div>
                    <div class="form-check mb-2" id="wh_active_wrap" style="display:none;">
                        <input type="checkbox" class="form-check-input" name="is_active" id="wh_active" checked>
                        <label class="form-check-label" for="wh_active">ArmazÃ©m ativo</label>
                    </div>
                    <div class="form-check mb-0">
                        <input type="checkbox" class="form-check-input" name="is_default" id="wh_default">
                        <label class="form-check-label" for="wh_default">
                            <i class="fas fa-star text-warning me-1"></i>ArmazÃ©m padrÃ£o
                            <small class="text-muted d-block">O armazÃ©m padrÃ£o serÃ¡ usado automaticamente nas movimentaÃ§Ãµes de estoque pelo pipeline.</small>
                        </label>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save me-1"></i>Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<!-- MODAL: Editar MovimentaÃ§Ã£o                                        -->
<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<div class="modal fade" id="editMovementModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary py-2">
                <h5 class="modal-title text-white"><i class="fas fa-edit me-2"></i>Editar MovimentaÃ§Ã£o</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editMov_id">

                <!-- Info do produto (readonly) -->
                <div class="mb-3 p-3 rounded bg-section-muted">
                    <div class="row">
                        <div class="col-md-6">
                            <small class="text-muted d-block">Produto</small>
                            <strong id="editMov_productName" class="text-primary"></strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">VariaÃ§Ã£o</small>
                            <span id="editMov_combination" class="small"></span>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">ArmazÃ©m</small>
                            <span id="editMov_warehouse" class="small"></span>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-4">
                            <small class="text-muted d-block">ID</small>
                            <span id="editMov_idLabel" class="badge bg-secondary"></span>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Origem</small>
                            <span id="editMov_refType" class="badge bg-light text-dark border"></span>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Data</small>
                            <span id="editMov_date" class="small text-muted"></span>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Tipo <span class="text-danger">*</span></label>
                    <select class="form-select" id="editMov_type">
                        <option value="entrada">Entrada</option>
                        <option value="saida">SaÃ­da</option>
                        <option value="ajuste">Ajuste</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold" id="editMov_qtyLabel">Quantidade <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="editMov_quantity" min="0.01" step="0.01" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Motivo / ObservaÃ§Ã£o</label>
                    <textarea class="form-control" id="editMov_reason" rows="2" placeholder="Motivo da movimentaÃ§Ã£o..."></textarea>
                </div>

                <div class="alert alert-info small mb-0" id="editMov_info">
                    <i class="fas fa-info-circle me-1"></i>
                    Ao alterar tipo ou quantidade, o saldo do estoque serÃ¡ recalculado automaticamente.
                </div>
            </div>
            <div class="modal-footer py-2 d-flex justify-content-between">
                <button type="button" class="btn btn-sm btn-outline-danger" id="btnDeleteMovement">
                    <i class="fas fa-trash me-1"></i>Excluir MovimentaÃ§Ã£o
                </button>
                <div>
                    <button type="button" class="btn btn-sm btn-secondary me-1" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-sm btn-primary" id="btnSaveMovement">
                        <i class="fas fa-save me-1"></i>Salvar AlteraÃ§Ãµes
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<!-- JAVASCRIPT                                                         -->
<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<script src="<?= asset('assets/js/modules/stock.js') ?>"></script>
