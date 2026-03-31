<?php
/**
 * Estoque — Página Unificada com Sidebar
 * Layout inspirado na página de relatórios: sidebar com seções à esquerda,
 * conteúdo da seção ativa à direita.
 *
 * Variáveis disponíveis (carregadas pelo StockController::index):
 *   $warehouses, $warehousesAll, $summary, $lowStockItems
 *   $movFilters (valores iniciais dos filtros)
 *   $products
 *   $limitReached, $limitInfo, $maxWarehouses, $currentWarehouses
 *
 * Tabelas de Visão Geral e Movimentações são carregadas via AJAX
 * com filtros dinâmicos e paginação.
 */

$activeSection = $_GET['section'] ?? 'overview';
$validSections = ['overview', 'movements', 'entry', 'warehouses'];
if (!in_array($activeSection, $validSections)) $activeSection = 'overview';

$currentWarehouse = $_GET['warehouse_id'] ?? '';
$currentSearch = $_GET['search'] ?? '';
$isLowStock = isset($_GET['low_stock']) && $_GET['low_stock'] == '1';

// Filtros de movimentação
$fWarehouse = $_GET['mov_warehouse_id'] ?? '';
$fProduct   = $_GET['mov_product_id'] ?? '';
$fType      = $_GET['mov_type'] ?? '';
$fDateFrom  = $_GET['mov_date_from'] ?? '';
$fDateTo    = $_GET['mov_date_to'] ?? '';
?>

<!-- ══════ Flash messages ══════ -->
<?php require 'app/views/components/flash-messages.php'; ?>

<!-- Styles loaded from assets/css/modules/stock.css via header.php -->

<div class="container-fluid py-3">

    <!-- ══════ Header ══════ -->
    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div>
            <h1 class="h2 mb-1"><i class="fas fa-warehouse me-2 text-primary"></i>Controle de Estoque</h1>
            <p class="text-muted mb-0" style="font-size:.82rem;">Gerencie o estoque, movimentações, entradas/saídas e armazéns.</p>
        </div>
    </div>

    <div class="row g-4">

        <!-- ═══════════════════════════════════════════════ -->
        <!-- SIDEBAR — Menu Lateral de Seções (3/12)         -->
        <!-- ═══════════════════════════════════════════════ -->
        <div class="col-lg-3 stk-sidebar-col">
            <div class="card border-0 shadow-sm" style="border-radius:12px;">
                <div class="card-body p-3">
                    <nav class="stk-sidebar">

                        <div class="stk-sidebar-label">Estoque</div>

                        <a href="#" class="stk-nav-item <?= $activeSection === 'overview' ? 'active' : '' ?>" data-section="overview">
                            <span class="stk-nav-icon nav-icon-blue">
                                <i class="fas fa-tachometer-alt"></i>
                            </span>
                            <span>Visão Geral</span>
                            <span class="stk-nav-count nav-icon-blue"><?= $summary['total_items'] ?></span>
                        </a>

                        <a href="#" class="stk-nav-item <?= $activeSection === 'movements' ? 'active' : '' ?>" data-section="movements">
                            <span class="stk-nav-icon nav-icon-purple">
                                <i class="fas fa-exchange-alt"></i>
                            </span>
                            <span>Movimentações</span>
                        </a>

                        <div class="stk-sidebar-divider"></div>

                        <a href="#" class="stk-nav-item <?= $activeSection === 'entry' ? 'active' : '' ?>" data-section="entry">
                            <span class="stk-nav-icon nav-icon-green">
                                <i class="fas fa-arrow-right-arrow-left"></i>
                            </span>
                            <span>Entrada / Saída</span>
                        </a>

                        <div class="stk-sidebar-divider"></div>

                        <a href="#" class="stk-nav-item <?= $activeSection === 'warehouses' ? 'active' : '' ?>" data-section="warehouses">
                            <span class="stk-nav-icon nav-icon-orange">
                                <i class="fas fa-building"></i>
                            </span>
                            <span>Armazéns</span>
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
                        Use a <span class="fw-bold text-primary">Visão Geral</span> para monitorar o estoque,
                        <span class="fw-bold text-success">Entrada/Saída</span> para registrar movimentações
                        e <span class="fw-bold text-warning">Armazéns</span> para gerenciar seus locais de armazenamento.
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

        <!-- ═══════════════════════════════════════════════ -->
        <!-- CONTEÚDO PRINCIPAL — Seção Ativa (9/12)         -->
        <!-- ═══════════════════════════════════════════════ -->
        <div class="col-lg-9">

            <!-- ══════════════════════════════════════ -->
            <!-- SEÇÃO: Visão Geral                      -->
            <!-- ══════════════════════════════════════ -->
            <div class="stk-section <?= $activeSection === 'overview' ? 'active' : '' ?>" id="stk-overview">

                <div class="d-flex align-items-center mb-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-2 nav-icon-blue" style="width:34px;height:34px;">
                        <i class="fas fa-tachometer-alt" style="font-size:.85rem;"></i>
                    </div>
                    <div>
                        <h5 class="mb-0" style="font-size:1rem;">Visão Geral do Estoque</h5>
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
                                    <div class="text-muted small fw-bold text-uppercase" style="font-size:.65rem;">Armazéns</div>
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
                                <label class="form-label small fw-bold mb-1">Armazém</label>
                                <select id="ov_warehouse" class="form-select form-select-sm ov-filter">
                                    <option value="">Todos os Armazéns</option>
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
                                    <input type="text" id="ov_search" class="form-control ov-filter" placeholder="Produto, variação ou localização..." value="<?= eAttr($currentSearch) ?>">
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
                        <thead class="bg-light">
                            <tr>
                                <th class="py-3 ps-4" style="width:50px;"></th>
                                <th class="py-3">Produto</th>
                                <th class="py-3">Variação</th>
                                <th class="py-3">Armazém</th>
                                <th class="py-3 text-center">Quantidade</th>
                                <th class="py-3 text-center">Mínimo</th>
                                <th class="py-3">Localização</th>
                                <th class="py-3 text-end pe-4">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="stockTableBody">
                            <tr><td colspan="8" class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin me-1"></i>Carregando...</td></tr>
                        </tbody>
                    </table>
                </div>
                <!-- Paginação Visão Geral -->
                <div class="d-flex justify-content-between align-items-center mt-2">
                    <span class="text-muted small" id="ovPaginationInfo"></span>
                    <nav><ul class="pagination pagination-sm mb-0" id="ovPagination"></ul></nav>
                </div>

            </div><!-- /.stk-section overview -->


            <!-- ══════════════════════════════════════ -->
            <!-- SEÇÃO: Movimentações                    -->
            <!-- ══════════════════════════════════════ -->
            <div class="stk-section <?= $activeSection === 'movements' ? 'active' : '' ?>" id="stk-movements">

                <div class="d-flex align-items-center mb-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-2 nav-icon-purple" style="width:34px;height:34px;">
                        <i class="fas fa-exchange-alt" style="font-size:.85rem;"></i>
                    </div>
                    <div>
                        <h5 class="mb-0" style="font-size:1rem;">Histórico de Movimentações</h5>
                        <p class="text-muted mb-0" style="font-size:.72rem;">Todas as entradas, saídas, ajustes e transferências registradas.</p>
                    </div>
                </div>

                <!-- Filtros de Movimentações -->
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body p-3">
                        <div class="row g-2 align-items-end" id="movFilterForm">
                            <div class="col-md-2">
                                <label class="form-label small fw-bold mb-1">Armazém</label>
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
                                    <option value="saida" <?= $fType === 'saida' ? 'selected' : '' ?>>Saída</option>
                                    <option value="ajuste" <?= $fType === 'ajuste' ? 'selected' : '' ?>>Ajuste</option>
                                    <option value="transferencia" <?= $fType === 'transferencia' ? 'selected' : '' ?>>Transferência</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold mb-1">De</label>
                                <input type="date" id="mov_date_from" class="form-control form-control-sm mov-filter" value="<?= eAttr($fDateFrom) ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold mb-1">Até</label>
                                <input type="date" id="mov_date_to" class="form-control form-control-sm mov-filter" value="<?= eAttr($fDateTo) ?>">
                            </div>
                            <div class="col-md-1 text-end">
                                <a href="#" class="text-muted small" id="btnClearMov" style="font-size:.72rem;"><i class="fas fa-times me-1"></i>Limpar</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabela de Movimentações -->
                <div class="table-responsive bg-white rounded shadow-sm">
                    <table class="table table-hover table-sm align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="py-2 ps-3" style="width:50px;">#</th>
                                <th class="py-2">Data</th>
                                <th class="py-2">Tipo</th>
                                <th class="py-2">Produto</th>
                                <th class="py-2">Variação</th>
                                <th class="py-2">Armazém</th>
                                <th class="py-2 text-center">Qtd</th>
                                <th class="py-2 text-center">Antes</th>
                                <th class="py-2 text-center">Depois</th>
                                <th class="py-2">Motivo</th>
                                <th class="py-2">Usuário</th>
                                <th class="py-2 text-center" style="width:80px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="movTableBody">
                            <tr><td colspan="12" class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin me-1"></i>Carregando...</td></tr>
                        </tbody>
                    </table>
                </div>
                <!-- Paginação Movimentações -->
                <div class="d-flex justify-content-between align-items-center mt-2">
                    <span class="text-muted small" id="movPaginationInfo"></span>
                    <nav><ul class="pagination pagination-sm mb-0" id="movPagination"></ul></nav>
                </div>

            </div><!-- /.stk-section movements -->


            <!-- ══════════════════════════════════════ -->
            <!-- SEÇÃO: Entrada / Saída                  -->
            <!-- ══════════════════════════════════════ -->
            <div class="stk-section <?= $activeSection === 'entry' ? 'active' : '' ?>" id="stk-entry">

                <div class="d-flex align-items-center mb-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-2 nav-icon-green" style="width:34px;height:34px;">
                        <i class="fas fa-arrow-right-arrow-left" style="font-size:.85rem;"></i>
                    </div>
                    <div>
                        <h5 class="mb-0" style="font-size:1rem;">Movimentação de Estoque</h5>
                        <p class="text-muted mb-0" style="font-size:.72rem;">Registre entradas, saídas, ajustes e transferências.</p>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Painel de Movimentação -->
                    <div class="col-xl-8">
                        <div class="card shadow-sm">
                            <div class="card-body p-4">

                                <!-- Tipo de Movimentação -->
                                <div class="mb-4">
                                    <label class="form-label fw-bold">Tipo de Movimentação</label>
                                    <div class="btn-group w-100" role="group" id="movTypeGroup">
                                        <input type="radio" class="btn-check" name="mov_type_entry" id="typeEntrada" value="entrada" checked>
                                        <label class="btn btn-outline-success" for="typeEntrada"><i class="fas fa-arrow-down me-1"></i>Entrada</label>

                                        <input type="radio" class="btn-check" name="mov_type_entry" id="typeSaida" value="saida">
                                        <label class="btn btn-outline-danger" for="typeSaida"><i class="fas fa-arrow-up me-1"></i>Saída</label>

                                        <input type="radio" class="btn-check" name="mov_type_entry" id="typeAjuste" value="ajuste">
                                        <label class="btn btn-outline-warning" for="typeAjuste"><i class="fas fa-sliders-h me-1"></i>Ajuste</label>

                                        <input type="radio" class="btn-check" name="mov_type_entry" id="typeTransfer" value="transferencia">
                                        <label class="btn btn-outline-info" for="typeTransfer"><i class="fas fa-truck me-1"></i>Transferência</label>
                                    </div>
                                </div>

                                <!-- Armazém Origem -->
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Armazém <span class="text-danger">*</span></label>
                                        <select class="form-select" id="selWarehouse" required>
                                            <option value="">Selecione o armazém...</option>
                                            <?php foreach ($warehouses as $wh): ?>
                                                <option value="<?= $wh['id'] ?>"><?= e($wh['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6" id="destWarehouseWrap" style="display:none;">
                                        <label class="form-label fw-bold">Armazém Destino <span class="text-danger">*</span></label>
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
                                    <label class="form-label fw-bold">Motivo / Observação</label>
                                    <input type="text" class="form-control" id="movReason" placeholder="Ex: Compra fornecedor, Venda avulsa, Correção inventário...">
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
                                        <label class="form-label small fw-bold">Variação</label>
                                        <select class="form-select form-select-sm" id="selCombination">
                                            <option value="">Sem variação</option>
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
                                                <th>Variação</th>
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

                                <!-- Botão Processar -->
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted small" id="itemsCountLabel">0 item(s)</span>
                                    <button type="button" class="btn btn-lg btn-success" id="btnProcess" disabled>
                                        <i class="fas fa-check-circle me-2"></i>Processar Movimentação
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Painel Lateral: Instruções -->
                    <div class="col-xl-4">
                        <div class="card shadow-sm mb-3">
                            <div class="card-header bg-white py-2">
                                <h6 class="mb-0"><i class="fas fa-info-circle text-primary me-2"></i>Como funciona</h6>
                            </div>
                            <div class="card-body small">
                                <div class="mb-3" id="helpEntrada">
                                    <span class="badge bg-success me-1">Entrada</span>
                                    Adiciona unidades ao estoque. Use para: compras de fornecedor, devoluções, produção.
                                </div>
                                <div class="mb-3" id="helpSaida" style="display:none;">
                                    <span class="badge bg-danger me-1">Saída</span>
                                    Remove unidades do estoque. Use para: vendas avulsas, perdas, descarte.
                                </div>
                                <div class="mb-3" id="helpAjuste" style="display:none;">
                                    <span class="badge bg-warning text-dark me-1">Ajuste</span>
                                    Define o saldo exato do item. Use para: inventário, correção de divergências.
                                </div>
                                <div class="mb-3" id="helpTransfer" style="display:none;">
                                    <span class="badge bg-info me-1">Transferência</span>
                                    Move unidades entre armazéns. A saída do origem e a entrada no destino são registradas automaticamente.
                                </div>
                                <hr>
                                <ol class="ps-3 mb-0">
                                    <li>Selecione o tipo de movimentação</li>
                                    <li>Escolha o armazém</li>
                                    <li>Adicione os produtos e quantidades</li>
                                    <li>Clique em <strong>Processar</strong></li>
                                </ol>
                            </div>
                        </div>

                        <!-- Histórico Recente (mini) -->
                        <div class="card shadow-sm">
                            <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><i class="fas fa-history text-muted me-2"></i>Últimas Movimentações</h6>
                                <a href="#" class="btn btn-sm btn-outline-secondary py-0 px-2 stk-go-movements" style="font-size:0.7rem;">Ver Todas</a>
                            </div>
                            <div class="card-body p-0" id="recentMovements" style="max-height:300px;overflow-y:auto;">
                                <div class="text-center text-muted small py-3"><i class="fas fa-spinner fa-spin me-1"></i>Carregando...</div>
                            </div>
                        </div>
                    </div>
                </div>

            </div><!-- /.stk-section entry -->


            <!-- ══════════════════════════════════════ -->
            <!-- SEÇÃO: Armazéns                         -->
            <!-- ══════════════════════════════════════ -->
            <div class="stk-section <?= $activeSection === 'warehouses' ? 'active' : '' ?>" id="stk-warehouses">

                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle d-flex align-items-center justify-content-center me-2 nav-icon-orange" style="width:34px;height:34px;">
                            <i class="fas fa-building" style="font-size:.85rem;"></i>
                        </div>
                        <div>
                            <h5 class="mb-0" style="font-size:1rem;">Armazéns / Locais de Estoque</h5>
                            <p class="text-muted mb-0" style="font-size:.72rem;">Gerencie seus armazéns e locais de armazenamento.</p>
                        </div>
                    </div>
                    <div>
                        <?php if (!empty($limitReached)): ?>
                            <button type="button" class="btn btn-sm btn-primary disabled" disabled title="Limite do plano atingido">
                                <i class="fas fa-plus me-1"></i> Novo Armazém
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#warehouseModal" onclick="openNewWarehouse()">
                                <i class="fas fa-plus me-1"></i> Novo Armazém
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($limitReached)): ?>
                <div class="alert alert-warning border-warning d-flex align-items-center mb-3" role="alert">
                    <i class="fas fa-exclamation-triangle fs-5 me-3 text-warning"></i>
                    <div>
                        <strong>Limite do plano atingido!</strong> Você possui <strong><?= $limitInfo['current'] ?></strong> de <strong><?= $limitInfo['max'] ?></strong> armazéns permitidos.
                        <span class="text-muted">Para cadastrar mais armazéns, entre em contato com o suporte para fazer um upgrade do seu plano.</span>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Lista de Armazéns -->
                <div class="row g-3">
                    <?php foreach ($warehousesAll as $wh): ?>
                    <div class="col-md-6 col-xl-4">
                        <div class="card h-100 shadow-sm warehouse-card <?= $wh['is_active'] ? '' : 'opacity-50' ?>">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                                <h5 class="mb-0" style="font-size:.95rem;">
                                    <i class="fas fa-warehouse me-2 text-primary"></i><?= e($wh['name']) ?>
                                    <?php if (!empty($wh['is_default'])): ?>
                                        <span class="badge bg-success ms-1" style="font-size:.6rem;"><i class="fas fa-star me-1"></i>Padrão</span>
                                    <?php endif; ?>
                                    <?php if (!$wh['is_active']): ?>
                                        <span class="badge bg-secondary ms-1" style="font-size:.6rem;">Inativo</span>
                                    <?php endif; ?>
                                </h5>
                                <div class="btn-group btn-group-sm">
                                    <?php if (empty($wh['is_default']) && $wh['is_active']): ?>
                                    <button type="button" class="btn btn-outline-success btn-set-default-wh"
                                            data-id="<?= $wh['id'] ?>" data-name="<?= eAttr($wh['name']) ?>"
                                            title="Definir como padrão">
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
                        Nenhum armazém cadastrado ainda.
                    </div>
                    <?php endif; ?>
                </div>

            </div><!-- /.stk-section warehouses -->

        </div><!-- /.col-lg-9 -->

    </div><!-- /.row -->

</div><!-- /.container-fluid -->


<!-- ══════════════════════════════════════════════════════════════════ -->
<!-- MODAL: Editar Mínimo / Localização                                -->
<!-- ══════════════════════════════════════════════════════════════════ -->
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
                    <label class="form-label small fw-bold">Estoque Mínimo</label>
                    <input type="number" class="form-control" id="metaMinQty" min="0" step="1" placeholder="0">
                    <div class="form-text">Alerta quando atingir este valor.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Localização Física</label>
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

<!-- ══════════════════════════════════════════════════════════════════ -->
<!-- MODAL: Criar / Editar Armazém                                     -->
<!-- ══════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="warehouseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="warehouseForm" method="post">
                <?= csrf_field() ?>
                <div class="modal-header bg-primary py-2">
                    <h5 class="modal-title text-white" id="whModalTitle"><i class="fas fa-warehouse me-2"></i>Novo Armazém</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="wh_id">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nome do Armazém <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" id="wh_name" required placeholder="Ex: Estoque Principal, Depósito 2...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Endereço</label>
                        <input type="text" class="form-control" name="address" id="wh_address" placeholder="Rua, número, complemento">
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
                        <label class="form-label small fw-bold">Observações</label>
                        <textarea class="form-control" name="notes" id="wh_notes" rows="2"></textarea>
                    </div>
                    <div class="form-check mb-2" id="wh_active_wrap" style="display:none;">
                        <input type="checkbox" class="form-check-input" name="is_active" id="wh_active" checked>
                        <label class="form-check-label" for="wh_active">Armazém ativo</label>
                    </div>
                    <div class="form-check mb-0">
                        <input type="checkbox" class="form-check-input" name="is_default" id="wh_default">
                        <label class="form-check-label" for="wh_default">
                            <i class="fas fa-star text-warning me-1"></i>Armazém padrão
                            <small class="text-muted d-block">O armazém padrão será usado automaticamente nas movimentações de estoque pelo pipeline.</small>
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


<!-- ══════════════════════════════════════════════════════════════════ -->
<!-- MODAL: Editar Movimentação                                        -->
<!-- ══════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="editMovementModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary py-2">
                <h5 class="modal-title text-white"><i class="fas fa-edit me-2"></i>Editar Movimentação</h5>
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
                            <small class="text-muted d-block">Variação</small>
                            <span id="editMov_combination" class="small"></span>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">Armazém</small>
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
                        <option value="saida">Saída</option>
                        <option value="ajuste">Ajuste</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold" id="editMov_qtyLabel">Quantidade <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="editMov_quantity" min="0.01" step="0.01" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Motivo / Observação</label>
                    <textarea class="form-control" id="editMov_reason" rows="2" placeholder="Motivo da movimentação..."></textarea>
                </div>

                <div class="alert alert-info small mb-0" id="editMov_info">
                    <i class="fas fa-info-circle me-1"></i>
                    Ao alterar tipo ou quantidade, o saldo do estoque será recalculado automaticamente.
                </div>
            </div>
            <div class="modal-footer py-2 d-flex justify-content-between">
                <button type="button" class="btn btn-sm btn-outline-danger" id="btnDeleteMovement">
                    <i class="fas fa-trash me-1"></i>Excluir Movimentação
                </button>
                <div>
                    <button type="button" class="btn btn-sm btn-secondary me-1" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-sm btn-primary" id="btnSaveMovement">
                        <i class="fas fa-save me-1"></i>Salvar Alterações
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- ══════════════════════════════════════════════════════════════════ -->
<!-- JAVASCRIPT                                                         -->
<!-- ══════════════════════════════════════════════════════════════════ -->
<script>
document.addEventListener('DOMContentLoaded', function() {

    // CSRF token para requisições AJAX POST
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    // ═══════════════════════════════════════════
    // ═══ UTILITÁRIOS                         ═══
    // ═══════════════════════════════════════════
    function escHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function formatNumber(n) {
        return parseInt(n || 0).toLocaleString('pt-BR');
    }

    function formatDate(dateStr) {
        if (!dateStr) return '—';
        var d = new Date(dateStr);
        return d.toLocaleDateString('pt-BR') + ' ' + d.toLocaleTimeString('pt-BR', {hour:'2-digit',minute:'2-digit'});
    }

    // ═══════════════════════════════════════════
    // ═══ SIDEBAR NAVIGATION (SPA-like)       ═══
    // ═══════════════════════════════════════════
    function navigateToSection(sectionId) {
        document.querySelectorAll('.stk-nav-item').forEach(function(n) { n.classList.remove('active'); });
        var navItem = document.querySelector('.stk-nav-item[data-section="' + sectionId + '"]');
        if (navItem) navItem.classList.add('active');

        document.querySelectorAll('.stk-section').forEach(function(s) { s.classList.remove('active'); });
        var target = document.getElementById('stk-' + sectionId);
        if (target) target.classList.add('active');

        var url = new URL(window.location);
        url.searchParams.set('section', sectionId);
        history.replaceState(null, '', url);

        // Carregar dados ao navegar para a seção
        if (sectionId === 'overview') loadStockItems(1);
        if (sectionId === 'movements') loadMovements(1);
    }

    document.querySelectorAll('.stk-nav-item').forEach(function(item) {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            var section = this.dataset.section;
            if (!section) return;
            navigateToSection(section);
        });
    });

    // Links de atalho entre seções
    document.querySelectorAll('.stk-go-entry').forEach(function(a) {
        a.addEventListener('click', function(e) { e.preventDefault(); navigateToSection('entry'); });
    });
    document.querySelectorAll('.stk-go-movements').forEach(function(a) {
        a.addEventListener('click', function(e) { e.preventDefault(); navigateToSection('movements'); });
    });
    document.querySelectorAll('.stk-nav-link-overview').forEach(function(a) {
        a.addEventListener('click', function(e) { e.preventDefault(); navigateToSection('overview'); });
    });


    // ═══════════════════════════════════════════
    // ═══ STATUS ALERTS                        ═══
    // ═══════════════════════════════════════════
    <?php if (isset($_GET['status'])): ?>
    var urlClean = new URL(window.location);
    urlClean.searchParams.delete('status');
    urlClean.searchParams.delete('error');
    window.history.replaceState({}, '', urlClean);
    <?php if ($_GET['status'] == 'moved'): ?>
    Swal.fire({ icon:'success', title:'Movimentação registrada!', timer:2000, showConfirmButton:false });
    <?php elseif ($_GET['status'] == 'created'): ?>
    Swal.fire({ icon:'success', title:'Armazém criado!', timer:2000, showConfirmButton:false });
    <?php elseif ($_GET['status'] == 'updated'): ?>
    Swal.fire({ icon:'success', title:'Armazém atualizado!', timer:2000, showConfirmButton:false });
    <?php elseif ($_GET['status'] == 'deleted'): ?>
    Swal.fire({ icon:'success', title:'Armazém removido!', timer:2000, showConfirmButton:false });
    <?php elseif ($_GET['status'] == 'limit_warehouses'): ?>
    Swal.fire({ icon:'warning', title:'Limite atingido!', text:'Você atingiu o limite de armazéns do seu plano.', confirmButtonColor:'#3498db' });
    <?php endif; ?>
    <?php endif; ?>


    // ═══════════════════════════════════════════
    // ═══ PAGINAÇÃO — Renderizador genérico   ═══
    // ═══════════════════════════════════════════
    function renderPagination(containerId, page, totalPages, total, perPage, callback) {
        var container = document.getElementById(containerId);
        var infoEl = document.getElementById(containerId + 'Info');

        // Info text
        if (infoEl) {
            var from = total > 0 ? ((page - 1) * perPage + 1) : 0;
            var to = Math.min(page * perPage, total);
            infoEl.textContent = 'Exibindo ' + from + '–' + to + ' de ' + total + ' registro(s)';
        }

        if (!container) return;
        container.innerHTML = '';
        if (totalPages <= 1) return;

        // Prev
        var liPrev = document.createElement('li');
        liPrev.className = 'page-item' + (page <= 1 ? ' disabled' : '');
        liPrev.innerHTML = '<a class="page-link" href="#">&laquo;</a>';
        if (page > 1) liPrev.querySelector('a').addEventListener('click', function(e) { e.preventDefault(); callback(page - 1); });
        container.appendChild(liPrev);

        // Page numbers (smart range)
        var startP = Math.max(1, page - 2);
        var endP = Math.min(totalPages, page + 2);
        if (startP > 1) {
            var li1 = document.createElement('li');
            li1.className = 'page-item';
            li1.innerHTML = '<a class="page-link" href="#">1</a>';
            li1.querySelector('a').addEventListener('click', function(e) { e.preventDefault(); callback(1); });
            container.appendChild(li1);
            if (startP > 2) {
                var liDots = document.createElement('li');
                liDots.className = 'page-item disabled';
                liDots.innerHTML = '<span class="page-link">…</span>';
                container.appendChild(liDots);
            }
        }
        for (var i = startP; i <= endP; i++) {
            (function(pg) {
                var li = document.createElement('li');
                li.className = 'page-item' + (pg === page ? ' active' : '');
                li.innerHTML = '<a class="page-link" href="#">' + pg + '</a>';
                if (pg !== page) li.querySelector('a').addEventListener('click', function(e) { e.preventDefault(); callback(pg); });
                container.appendChild(li);
            })(i);
        }
        if (endP < totalPages) {
            if (endP < totalPages - 1) {
                var liDots2 = document.createElement('li');
                liDots2.className = 'page-item disabled';
                liDots2.innerHTML = '<span class="page-link">…</span>';
                container.appendChild(liDots2);
            }
            var liLast = document.createElement('li');
            liLast.className = 'page-item';
            liLast.innerHTML = '<a class="page-link" href="#">' + totalPages + '</a>';
            liLast.querySelector('a').addEventListener('click', function(e) { e.preventDefault(); callback(totalPages); });
            container.appendChild(liLast);
        }

        // Next
        var liNext = document.createElement('li');
        liNext.className = 'page-item' + (page >= totalPages ? ' disabled' : '');
        liNext.innerHTML = '<a class="page-link" href="#">&raquo;</a>';
        if (page < totalPages) liNext.querySelector('a').addEventListener('click', function(e) { e.preventDefault(); callback(page + 1); });
        container.appendChild(liNext);
    }


    // ═══════════════════════════════════════════
    // ═══ VISÃO GERAL — AJAX + Paginação      ═══
    // ═══════════════════════════════════════════
    var ovCurrentPage = 1;

    function loadStockItems(page) {
        ovCurrentPage = page || 1;
        var tbody = document.getElementById('stockTableBody');
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin me-1"></i>Carregando...</td></tr>';

        var params = new URLSearchParams({
            page: 'stock',
            action: 'getStockItems',
            warehouse_id: document.getElementById('ov_warehouse').value,
            search: document.getElementById('ov_search').value,
            low_stock: document.getElementById('ov_low_stock').checked ? '1' : '',
            pg: ovCurrentPage,
            per_page: 25
        });

        fetch('?' + params.toString())
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success) {
                    tbody.innerHTML = '<tr><td colspan="8" class="text-center text-danger py-4">Erro ao carregar dados.</td></tr>';
                    return;
                }

                if (data.items.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-5">' +
                        '<i class="fas fa-warehouse fa-3x mb-3 d-block text-secondary"></i>' +
                        'Nenhum item no estoque com os filtros selecionados.' +
                        '<br><a href="#" class="btn btn-success btn-sm mt-2 stk-go-entry"><i class="fas fa-plus me-1"></i>Dar entrada</a>' +
                        '</td></tr>';
                    // Re-bind entry link
                    tbody.querySelectorAll('.stk-go-entry').forEach(function(a) {
                        a.addEventListener('click', function(e) { e.preventDefault(); navigateToSection('entry'); });
                    });
                    document.getElementById('ovPagination').innerHTML = '';
                    document.getElementById('ovPaginationInfo').textContent = '0 registros';
                    return;
                }

                var html = '';
                data.items.forEach(function(si) {
                    var isLow = si.min_quantity > 0 && si.quantity <= si.min_quantity;
                    var imgCell = si.product_image
                        ? '<img src="' + escHtml(si.product_image) + '" class="w-100 h-100 object-fit-cover">'
                        : '<i class="fas fa-box text-secondary"></i>';
                    var combCell = si.combination_label
                        ? '<span class="badge bg-info bg-opacity-75">' + escHtml(si.combination_label) + '</span>'
                        : '<span class="text-muted small">—</span>';
                    var qtyBadge = isLow
                        ? '<span class="badge bg-danger px-3 fs-6">' + formatNumber(si.quantity) + '</span>'
                        : (si.quantity > 0
                            ? '<span class="badge bg-success px-3 fs-6">' + formatNumber(si.quantity) + '</span>'
                            : '<span class="badge bg-secondary px-3">0</span>');
                    var minCell = si.min_quantity > 0 ? formatNumber(si.min_quantity) : '—';
                    var locCell = si.location_code ? escHtml(si.location_code) : '—';

                    html += '<tr class="' + (isLow ? 'table-warning' : '') + '">' +
                        '<td class="ps-4"><div class="bg-light rounded d-flex align-items-center justify-content-center border" style="width:40px;height:40px;overflow:hidden;">' + imgCell + '</div></td>' +
                        '<td class="fw-bold">' + escHtml(si.product_name) + '</td>' +
                        '<td>' + combCell + '</td>' +
                        '<td><span class="badge bg-light text-dark border">' + escHtml(si.warehouse_name) + '</span></td>' +
                        '<td class="text-center">' + qtyBadge + '</td>' +
                        '<td class="text-center"><span class="text-muted small">' + minCell + '</span></td>' +
                        '<td><span class="text-muted small">' + locCell + '</span></td>' +
                        '<td class="text-end pe-4"><div class="btn-group btn-group-sm">' +
                            '<button type="button" class="btn btn-outline-secondary btn-edit-meta"' +
                            ' data-id="' + si.id + '" data-min="' + (si.min_quantity||0) + '"' +
                            ' data-loc="' + escHtml(si.location_code||'') + '"' +
                            ' data-name="' + escHtml(si.product_name) + '"' +
                            ' title="Editar mínimo/localização"><i class="fas fa-cog"></i></button>' +
                        '</div></td>' +
                    '</tr>';
                });
                tbody.innerHTML = html;

                // Re-bind edit meta buttons
                bindEditMetaButtons();

                // Paginação
                renderPagination('ovPagination', data.page, data.total_pages, data.total, data.per_page, loadStockItems);
                var infoEl = document.getElementById('ovPaginationInfo');
                if (infoEl) {
                    var from = data.total > 0 ? ((data.page - 1) * data.per_page + 1) : 0;
                    var to = Math.min(data.page * data.per_page, data.total);
                    infoEl.textContent = 'Exibindo ' + from + '–' + to + ' de ' + data.total + ' registro(s)';
                }
            })
            .catch(function() {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center text-danger py-4">Erro de comunicação ao carregar estoque.</td></tr>';
            });
    }

    // Filtrar visão geral — dinâmico ao alterar qualquer campo
    var _ovDebounce = null;
    document.querySelectorAll('.ov-filter').forEach(function(el) {
        var evType = (el.tagName === 'INPUT' && el.type === 'text') ? 'input' : 'change';
        el.addEventListener(evType, function() {
            clearTimeout(_ovDebounce);
            _ovDebounce = setTimeout(function() { loadStockItems(1); }, evType === 'input' ? 350 : 0);
        });
    });
    document.getElementById('btnClearOverview').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('ov_warehouse').value = '';
        document.getElementById('ov_search').value = '';
        document.getElementById('ov_low_stock').checked = false;
        loadStockItems(1);
    });

    // Carregar ao abrir a página se a seção ativa for overview
    <?php if ($activeSection === 'overview'): ?>
    loadStockItems(1);
    <?php endif; ?>


    // ═══════════════════════════════════════════
    // ═══ MOVIMENTAÇÕES — AJAX + Paginação    ═══
    // ═══════════════════════════════════════════
    var movCurrentPage = 1;

    var typeBadges = { entrada:'bg-success', saida:'bg-danger', ajuste:'bg-warning text-dark', transferencia:'bg-info' };
    var typeIcons  = { entrada:'fas fa-arrow-down', saida:'fas fa-arrow-up', ajuste:'fas fa-sliders-h', transferencia:'fas fa-truck' };
    var typeLabels = { entrada:'Entrada', saida:'Saída', ajuste:'Ajuste', transferencia:'Transferência' };

    function loadMovements(page) {
        movCurrentPage = page || 1;
        var tbody = document.getElementById('movTableBody');
        tbody.innerHTML = '<tr><td colspan="12" class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin me-1"></i>Carregando...</td></tr>';

        var params = new URLSearchParams({
            page: 'stock',
            action: 'getMovements',
            warehouse_id: document.getElementById('mov_warehouse').value,
            product_id: document.getElementById('mov_product').value,
            type: document.getElementById('mov_type_filter').value,
            date_from: document.getElementById('mov_date_from').value,
            date_to: document.getElementById('mov_date_to').value,
            pg: movCurrentPage,
            per_page: 25
        });

        fetch('?' + params.toString())
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success) {
                    tbody.innerHTML = '<tr><td colspan="12" class="text-center text-danger py-4">Erro ao carregar movimentações.</td></tr>';
                    return;
                }

                if (data.items.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="12" class="text-center text-muted py-5">' +
                        '<i class="fas fa-exchange-alt fa-3x mb-3 d-block text-secondary"></i>' +
                        'Nenhuma movimentação encontrada.</td></tr>';
                    document.getElementById('movPagination').innerHTML = '';
                    document.getElementById('movPaginationInfo').textContent = '0 registros';
                    return;
                }

                var html = '';
                data.items.forEach(function(m) {
                    var badge = typeBadges[m.type] || 'bg-secondary';
                    var icon  = typeIcons[m.type] || '';
                    var label = typeLabels[m.type] || m.type;

                    var warehouseCell = escHtml(m.warehouse_name);
                    if (m.type === 'transferencia' && m.dest_warehouse_name) {
                        warehouseCell += ' <i class="fas fa-arrow-right mx-1 text-muted" style="font-size:0.6rem;"></i> <span class="text-info">' + escHtml(m.dest_warehouse_name) + '</span>';
                    }

                    var qtyCell;
                    if (m.type === 'entrada') {
                        qtyCell = '<span class="text-success">+' + formatNumber(m.quantity) + '</span>';
                    } else if (m.type === 'saida') {
                        qtyCell = '<span class="text-danger">-' + formatNumber(m.quantity) + '</span>';
                    } else {
                        qtyCell = formatNumber(m.quantity);
                    }

                    var combCell = m.combination_label
                        ? '<span class="badge bg-light text-dark border">' + escHtml(m.combination_label) + '</span>'
                        : '<span class="text-muted">—</span>';

                    // Botão de ação: somente manuais podem ser editados/excluídos
                    var isManual = (!m.reference_type || m.reference_type === 'manual');
                    var actionsCell = '';
                    if (isManual) {
                        actionsCell = '<button class="btn btn-sm btn-outline-primary py-0 px-1 btn-edit-mov" data-id="' + m.id + '" title="Editar">' +
                            '<i class="fas fa-pen" style="font-size:0.7rem;"></i>' +
                        '</button>';
                    } else {
                        actionsCell = '<span class="text-muted" title="Automática"><i class="fas fa-lock" style="font-size:0.65rem;"></i></span>';
                    }

                    html += '<tr>' +
                        '<td class="ps-3 text-muted small">' + m.id + '</td>' +
                        '<td class="small">' + formatDate(m.created_at) + '</td>' +
                        '<td><span class="badge ' + badge + '"><i class="' + icon + ' me-1"></i>' + label + '</span></td>' +
                        '<td class="fw-bold small">' + escHtml(m.product_name) + '</td>' +
                        '<td class="small">' + combCell + '</td>' +
                        '<td class="small">' + warehouseCell + '</td>' +
                        '<td class="text-center fw-bold">' + qtyCell + '</td>' +
                        '<td class="text-center small text-muted">' + formatNumber(m.quantity_before) + '</td>' +
                        '<td class="text-center small fw-bold">' + formatNumber(m.quantity_after) + '</td>' +
                        '<td class="small text-muted" style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="' + escHtml(m.reason||'') + '">' + (m.reason ? escHtml(m.reason) : '—') + '</td>' +
                        '<td class="small text-muted">' + (m.user_name ? escHtml(m.user_name) : '—') + '</td>' +
                        '<td class="text-center">' + actionsCell + '</td>' +
                    '</tr>';
                });
                tbody.innerHTML = html;

                // Paginação
                renderPagination('movPagination', data.page, data.total_pages, data.total, data.per_page, loadMovements);
                var infoEl = document.getElementById('movPaginationInfo');
                if (infoEl) {
                    var from = data.total > 0 ? ((data.page - 1) * data.per_page + 1) : 0;
                    var to = Math.min(data.page * data.per_page, data.total);
                    infoEl.textContent = 'Exibindo ' + from + '–' + to + ' de ' + data.total + ' movimentação(ões)';
                }

                // Bind botões de editar movimentação
                bindEditMovButtons();
            })
            .catch(function() {
                tbody.innerHTML = '<tr><td colspan="12" class="text-center text-danger py-4">Erro de comunicação ao carregar movimentações.</td></tr>';
            });
    }

    // Filtrar movimentações — dinâmico ao alterar qualquer campo
    document.querySelectorAll('.mov-filter').forEach(function(el) {
        el.addEventListener('change', function() { loadMovements(1); });
    });
    document.getElementById('btnClearMov').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('mov_warehouse').value = '';
        document.getElementById('mov_product').value = '';
        document.getElementById('mov_type_filter').value = '';
        document.getElementById('mov_date_from').value = '';
        document.getElementById('mov_date_to').value = '';
        loadMovements(1);
    });

    // Carregar ao abrir a página se a seção ativa for movements
    <?php if ($activeSection === 'movements'): ?>
    loadMovements(1);
    <?php endif; ?>


    // ═══════════════════════════════════════════
    // ═══ EDITAR / EXCLUIR MOVIMENTAÇÃO       ═══
    // ═══════════════════════════════════════════
    var editMovModal = null;

    function bindEditMovButtons() {
        document.querySelectorAll('.btn-edit-mov').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var movId = this.dataset.id;
                openEditMovement(movId);
            });
        });
    }

    function openEditMovement(movId) {
        // Buscar dados da movimentação via AJAX
        fetch('?page=stock&action=getMovement&id=' + movId)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success) {
                    Swal.fire({ icon: 'error', title: 'Erro', text: data.message || 'Movimentação não encontrada.' });
                    return;
                }
                var m = data.movement;

                // Preencher campos do modal
                document.getElementById('editMov_id').value = m.id;
                document.getElementById('editMov_idLabel').textContent = '#' + m.id;
                document.getElementById('editMov_productName').textContent = m.product_name;
                document.getElementById('editMov_combination').textContent = m.combination_label || '—';
                document.getElementById('editMov_warehouse').textContent = m.warehouse_name;
                document.getElementById('editMov_refType').textContent = m.reference_type || 'manual';
                document.getElementById('editMov_date').textContent = formatDate(m.created_at);
                document.getElementById('editMov_type').value = m.type;
                document.getElementById('editMov_quantity').value = parseFloat(m.quantity);
                document.getElementById('editMov_reason').value = m.reason || '';

                // Ajustar label da quantidade
                updateEditMovQtyLabel();

                // Desabilitar tipo se for transferência
                var typeSelect = document.getElementById('editMov_type');
                if (m.type === 'transferencia') {
                    typeSelect.disabled = true;
                    document.getElementById('editMov_info').innerHTML = '<i class="fas fa-exclamation-triangle me-1 text-warning"></i>Transferências não podem ter o tipo alterado. Para desfazer, exclua a movimentação.';
                } else {
                    typeSelect.disabled = false;
                    document.getElementById('editMov_info').innerHTML = '<i class="fas fa-info-circle me-1"></i>Ao alterar tipo ou quantidade, o saldo do estoque será recalculado automaticamente.';
                }

                // Abrir modal
                if (!editMovModal) {
                    editMovModal = new bootstrap.Modal(document.getElementById('editMovementModal'));
                }
                editMovModal.show();
            })
            .catch(function() {
                Swal.fire({ icon: 'error', title: 'Erro de comunicação', text: 'Não foi possível buscar os dados da movimentação.' });
            });
    }

    function updateEditMovQtyLabel() {
        var type = document.getElementById('editMov_type').value;
        var label = document.getElementById('editMov_qtyLabel');
        label.innerHTML = type === 'ajuste' ? 'Novo Saldo <span class="text-danger">*</span>' : 'Quantidade <span class="text-danger">*</span>';
    }

    document.getElementById('editMov_type').addEventListener('change', updateEditMovQtyLabel);

    // Salvar alterações
    document.getElementById('btnSaveMovement').addEventListener('click', function() {
        var id = document.getElementById('editMov_id').value;
        var type = document.getElementById('editMov_type').value;
        var quantity = document.getElementById('editMov_quantity').value;
        var reason = document.getElementById('editMov_reason').value;

        if (!quantity || parseFloat(quantity) <= 0) {
            Swal.fire({ icon: 'warning', title: 'Quantidade inválida', text: 'Informe uma quantidade maior que zero.' });
            return;
        }

        var btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Salvando...';

        var fd = new FormData();
        fd.append('csrf_token', csrfToken);
        fd.append('id', id);
        fd.append('type', type);
        fd.append('quantity', quantity);
        fd.append('reason', reason);

        fetch('?page=stock&action=updateMovement', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save me-1"></i>Salvar Alterações';

                if (data.success) {
                    if (editMovModal) editMovModal.hide();
                    Swal.fire({ icon: 'success', title: 'Atualizado!', text: data.message, timer: 2000, showConfirmButton: false })
                        .then(function() {
                            loadMovements(movCurrentPage);
                            loadStockItems(ovCurrentPage);
                            loadRecentMovements();
                        });
                } else {
                    Swal.fire({ icon: 'error', title: 'Erro', text: data.message || 'Erro ao atualizar.' });
                }
            })
            .catch(function() {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save me-1"></i>Salvar Alterações';
                Swal.fire({ icon: 'error', title: 'Erro de comunicação' });
            });
    });

    // Excluir movimentação
    document.getElementById('btnDeleteMovement').addEventListener('click', function() {
        var id = document.getElementById('editMov_id').value;
        var productName = document.getElementById('editMov_productName').textContent;

        Swal.fire({
            title: 'Excluir movimentação?',
            html: '<p>Deseja excluir a movimentação <strong>#' + id + '</strong> do produto <strong>' + escHtml(productName) + '</strong>?</p>' +
                  '<p class="text-danger small"><i class="fas fa-exclamation-triangle me-1"></i>O saldo do estoque será revertido automaticamente. Esta ação não pode ser desfeita.</p>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#c0392b',
            confirmButtonText: '<i class="fas fa-trash me-1"></i>Excluir',
            cancelButtonText: 'Cancelar'
        }).then(function(result) {
            if (!result.isConfirmed) return;

            var fd = new FormData();
            fd.append('csrf_token', csrfToken);
            fd.append('id', id);

            fetch('?page=stock&action=deleteMovement', { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success) {
                        if (editMovModal) editMovModal.hide();
                        Swal.fire({ icon: 'success', title: 'Excluído!', text: data.message, timer: 2000, showConfirmButton: false })
                            .then(function() {
                                loadMovements(movCurrentPage);
                                loadStockItems(ovCurrentPage);
                                loadRecentMovements();
                            });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Erro', text: data.message || 'Erro ao excluir.' });
                    }
                })
                .catch(function() {
                    Swal.fire({ icon: 'error', title: 'Erro de comunicação' });
                });
        });
    });


    // ═══════════════════════════════════════════
    // ═══ VISÃO GERAL — Edit meta modal       ═══
    // ═══════════════════════════════════════════
    function bindEditMetaButtons() {
        document.querySelectorAll('.btn-edit-meta').forEach(function(btn) {
            btn.addEventListener('click', function() {
                document.getElementById('metaItemId').value = this.dataset.id;
                document.getElementById('metaMinQty').value = this.dataset.min;
                document.getElementById('metaLocCode').value = this.dataset.loc;
                document.getElementById('metaItemName').textContent = this.dataset.name;
                new bootstrap.Modal(document.getElementById('editMetaModal')).show();
            });
        });
    }
    // Bind inicialmente (caso haja dados pré-carregados)
    bindEditMetaButtons();

    var btnSaveMeta = document.getElementById('btnSaveMeta');
    if (btnSaveMeta) {
        btnSaveMeta.addEventListener('click', function() {
            var id = document.getElementById('metaItemId').value;
            var minQty = document.getElementById('metaMinQty').value;
            var locCode = document.getElementById('metaLocCode').value;

            var fd = new FormData();
            fd.append('csrf_token', csrfToken);
            fd.append('id', id);
            fd.append('min_quantity', minQty);
            fd.append('location_code', locCode);

            fetch('?page=stock&action=updateItemMeta', { method:'POST', body:fd })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success) {
                        bootstrap.Modal.getInstance(document.getElementById('editMetaModal')).hide();
                        Swal.fire({ icon:'success', title:'Atualizado!', timer:1500, showConfirmButton:false })
                            .then(function() { loadStockItems(ovCurrentPage); });
                    }
                });
        });
    }


    // ═══════════════════════════════════════════
    // ═══ ENTRADA/SAÍDA — Lógica completa     ═══
    // ═══════════════════════════════════════════
    var items = [];
    var selProduct = document.getElementById('selProduct');
    var selCombination = document.getElementById('selCombination');
    var combWrap = document.getElementById('combWrap');
    var inputQty = document.getElementById('inputQty');
    var itemsBody = document.getElementById('itemsBody');
    var btnAdd = document.getElementById('btnAddItem');
    var btnProcess = document.getElementById('btnProcess');
    var destWrap = document.getElementById('destWarehouseWrap');
    var lblQty = document.getElementById('lblQty');

    // Type toggle
    document.querySelectorAll('input[name="mov_type_entry"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            var t = this.value;
            destWrap.style.display = t === 'transferencia' ? '' : 'none';
            lblQty.textContent = t === 'ajuste' ? 'Novo Saldo' : 'Quantidade';
            document.getElementById('helpEntrada').style.display = t === 'entrada' ? '' : 'none';
            document.getElementById('helpSaida').style.display = t === 'saida' ? '' : 'none';
            document.getElementById('helpAjuste').style.display = t === 'ajuste' ? '' : 'none';
            document.getElementById('helpTransfer').style.display = t === 'transferencia' ? '' : 'none';
        });
    });

    // Fetch combinations when product selected
    if (selProduct) {
        selProduct.addEventListener('change', function() {
            var pid = this.value;
            combWrap.style.display = 'none';
            selCombination.innerHTML = '<option value="">Sem variação</option>';
            if (!pid) return;

            fetch('?page=stock&action=getProductCombinations&product_id=' + pid)
                .then(function(r) { return r.json(); })
                .then(function(combos) {
                    if (combos.length > 0) {
                        combWrap.style.display = '';
                        selCombination.innerHTML = '<option value="">Produto base (sem variação)</option>';
                        combos.forEach(function(c) {
                            selCombination.innerHTML += '<option value="' + c.id + '">' + c.combination_label + (c.sku ? ' [' + c.sku + ']' : '') + '</option>';
                        });
                    }
                });
        });
    }

    // Add item to list
    if (btnAdd) {
        btnAdd.addEventListener('click', function() {
            var productId = selProduct.value;
            var productName = selProduct.options[selProduct.selectedIndex] ? selProduct.options[selProduct.selectedIndex].text : '';
            var combId = selCombination.value || null;
            var combName = combId ? selCombination.options[selCombination.selectedIndex].text : '—';
            var qty = parseFloat(inputQty.value);

            if (!productId) { Swal.fire({ icon:'warning', title:'Selecione um produto', timer:2000, showConfirmButton:false }); return; }
            if (!qty || qty <= 0) { Swal.fire({ icon:'warning', title:'Quantidade inválida', timer:2000, showConfirmButton:false }); return; }

            var exists = items.find(function(i) { return i.product_id == productId && i.combination_id == combId; });
            if (exists) {
                exists.quantity += qty;
                renderItems();
                return;
            }

            items.push({ product_id: productId, combination_id: combId, product_name: productName, combination_name: combName, quantity: qty });
            renderItems();

            selProduct.value = '';
            selCombination.innerHTML = '<option value="">Sem variação</option>';
            combWrap.style.display = 'none';
            inputQty.value = 1;
            selProduct.focus();
        });
    }

    function renderItems() {
        if (items.length === 0) {
            itemsBody.innerHTML = '<tr id="emptyItemsRow"><td colspan="4" class="text-center text-muted py-3"><i class="fas fa-inbox me-1"></i>Adicione produtos acima</td></tr>';
            btnProcess.disabled = true;
            document.getElementById('itemsCountLabel').textContent = '0 item(s)';
            return;
        }

        var html = '';
        items.forEach(function(item, idx) {
            html += '<tr>' +
                '<td class="fw-bold">' + escHtml(item.product_name) + '</td>' +
                '<td><span class="badge bg-light text-dark border">' + escHtml(item.combination_name) + '</span></td>' +
                '<td class="text-center">' +
                    '<input type="number" class="form-control form-control-sm text-center" value="' + item.quantity + '" min="0.01" step="1" ' +
                    'onchange="updateItemQty(' + idx + ', this.value)" style="width:80px;margin:auto;">' +
                '</td>' +
                '<td class="text-center">' +
                    '<button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItem(' + idx + ')"><i class="fas fa-times"></i></button>' +
                '</td>' +
            '</tr>';
        });
        itemsBody.innerHTML = html;
        btnProcess.disabled = false;
        document.getElementById('itemsCountLabel').textContent = items.length + ' item(s)';
    }

    window.updateItemQty = function(idx, val) {
        items[idx].quantity = parseFloat(val) || 0;
    };
    window.removeItem = function(idx) {
        items.splice(idx, 1);
        renderItems();
    };

    function escHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // Process Movement
    if (btnProcess) {
        btnProcess.addEventListener('click', function() {
            var typeRadio = document.querySelector('input[name="mov_type_entry"]:checked');
            var type = typeRadio ? typeRadio.value : 'entrada';
            var warehouseId = document.getElementById('selWarehouse').value;
            var destWarehouseId = document.getElementById('selDestWarehouse') ? document.getElementById('selDestWarehouse').value : '';
            var reason = document.getElementById('movReason').value;

            if (!warehouseId) { Swal.fire({ icon:'warning', title:'Selecione o armazém' }); return; }
            if (type === 'transferencia' && !destWarehouseId) { Swal.fire({ icon:'warning', title:'Selecione o armazém de destino' }); return; }
            if (type === 'transferencia' && warehouseId === destWarehouseId) { Swal.fire({ icon:'warning', title:'Origem e destino devem ser diferentes' }); return; }
            if (items.length === 0) { Swal.fire({ icon:'warning', title:'Adicione produtos' }); return; }

            var typeLabels = { entrada:'Entrada', saida:'Saída', ajuste:'Ajuste', transferencia:'Transferência' };

            Swal.fire({
                title: 'Confirmar ' + typeLabels[type] + '?',
                html: '<strong>' + items.length + '</strong> item(s) serão processados.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-check me-1"></i>Confirmar',
                cancelButtonText: 'Cancelar'
            }).then(function(result) {
                if (!result.isConfirmed) return;

                btnProcess.disabled = true;
                btnProcess.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processando...';

                var fd = new FormData();
                fd.append('csrf_token', csrfToken);
                fd.append('warehouse_id', warehouseId);
                fd.append('destination_warehouse_id', destWarehouseId);
                fd.append('type', type);
                fd.append('reason', reason);
                items.forEach(function(item, i) {
                    fd.append('items[' + i + '][product_id]', item.product_id);
                    fd.append('items[' + i + '][combination_id]', item.combination_id || '');
                    fd.append('items[' + i + '][quantity]', item.quantity);
                });

                fetch('?page=stock&action=storeMovement', { method:'POST', body:fd })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.success) {
                            Swal.fire({ icon:'success', title:'Movimentação Registrada!', html: data.processed + ' item(s) processado(s).', timer:2500, showConfirmButton:true })
                                .then(function() {
                                    // Limpar itens e recarregar dados
                                    items = [];
                                    renderItems();
                                    document.getElementById('movReason').value = '';
                                    document.getElementById('selWarehouse').value = '';
                                    // Recarregar overview e movimentações
                                    loadStockItems(1);
                                    loadMovements(1);
                                    loadRecentMovements();
                                    navigateToSection('overview');
                                });
                        } else {
                            Swal.fire({ icon:'error', title:'Erro', text: data.message || 'Erro ao processar.' });
                            btnProcess.disabled = false;
                            btnProcess.innerHTML = '<i class="fas fa-check-circle me-2"></i>Processar Movimentação';
                        }
                    })
                    .catch(function() {
                        Swal.fire({ icon:'error', title:'Erro de comunicação' });
                        btnProcess.disabled = false;
                        btnProcess.innerHTML = '<i class="fas fa-check-circle me-2"></i>Processar Movimentação';
                    });
            });
        });
    }

    // Load recent movements (mini list in entry section)
    function loadRecentMovements() {
        var container = document.getElementById('recentMovements');
        if (!container) return;
        container.innerHTML = '<div class="text-center text-muted small py-3"><i class="fas fa-spinner fa-spin me-1"></i>Carregando...</div>';

        fetch('?page=stock&action=movements&format=json&limit=10')
            .catch(function() { return null; })
            .then(function(r) { if (r && r.ok) return r.json(); return null; })
            .then(function(data) {
                if (!data || !Array.isArray(data) || data.length === 0) {
                    container.innerHTML = '<div class="text-center text-muted small py-3">Nenhuma movimentação recente.</div>';
                    return;
                }
                var icons = { entrada:'fas fa-arrow-down text-success', saida:'fas fa-arrow-up text-danger', ajuste:'fas fa-sliders-h text-warning', transferencia:'fas fa-truck text-info' };
                var html = '<div class="list-group list-group-flush">';
                data.forEach(function(m) {
                    html += '<div class="list-group-item px-3 py-2">' +
                        '<div class="d-flex justify-content-between">' +
                            '<span><i class="' + (icons[m.type] || 'fas fa-circle') + ' me-2"></i><strong class="small">' + escHtml(m.product_name) + '</strong></span>' +
                            '<span class="badge ' + (m.type === 'entrada' ? 'bg-success' : m.type === 'saida' ? 'bg-danger' : 'bg-secondary') + '">' + (m.type === 'entrada' ? '+' : '-') + parseFloat(m.quantity).toFixed(0) + '</span>' +
                        '</div>' +
                        '<small class="text-muted">' + m.warehouse_name + ' · ' + new Date(m.created_at).toLocaleDateString('pt-BR') + '</small>' +
                    '</div>';
                });
                html += '</div>';
                container.innerHTML = html;
            });
    }
    loadRecentMovements();


    // ═══════════════════════════════════════════
    // ═══ ARMAZÉNS — CRUD                     ═══
    // ═══════════════════════════════════════════

    // Edit warehouse
    document.querySelectorAll('.btn-edit-wh').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.getElementById('whModalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Editar Armazém';
            document.getElementById('warehouseForm').action = '?page=stock&action=updateWarehouse';
            document.getElementById('wh_id').value = this.dataset.id;
            document.getElementById('wh_name').value = this.dataset.name;
            document.getElementById('wh_address').value = this.dataset.address;
            document.getElementById('wh_city').value = this.dataset.city;
            document.getElementById('wh_state').value = this.dataset.state;
            document.getElementById('wh_zip').value = this.dataset.zip;
            document.getElementById('wh_phone').value = this.dataset.phone;
            document.getElementById('wh_notes').value = this.dataset.notes;
            document.getElementById('wh_active').checked = this.dataset.active == '1';
            document.getElementById('wh_active_wrap').style.display = 'block';
            document.getElementById('wh_default').checked = this.dataset.default == '1';
            new bootstrap.Modal(document.getElementById('warehouseModal')).show();
        });
    });

    // Delete warehouse
    document.querySelectorAll('.btn-delete-wh').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.dataset.id;
            var name = this.dataset.name;
            Swal.fire({
                title: 'Excluir armazém?',
                html: 'Deseja remover <strong>' + name + '</strong>?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#c0392b',
                confirmButtonText: '<i class="fas fa-trash me-1"></i>Excluir',
                cancelButtonText: 'Cancelar'
            }).then(function(result) {
                if (result.isConfirmed) {
                    window.location.href = '?page=stock&action=deleteWarehouse&id=' + id;
                }
            });
        });
    });

    // Set as default warehouse
    document.querySelectorAll('.btn-set-default-wh').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.dataset.id;
            var name = this.dataset.name;
            Swal.fire({
                title: 'Definir como padrão?',
                html: 'Deseja definir <strong>' + name + '</strong> como o armazém padrão?<br><small class="text-muted">O estoque será movimentado automaticamente por este armazém no pipeline.</small>',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#27ae60',
                confirmButtonText: '<i class="fas fa-star me-1"></i>Definir Padrão',
                cancelButtonText: 'Cancelar'
            }).then(function(result) {
                if (result.isConfirmed) {
                    var fd = new FormData();
                    fd.append('csrf_token', csrfToken);
                    fd.append('id', id);
                    fetch('?page=stock&action=setDefault', { method: 'POST', body: fd })
                        .then(function(r) { return r.json(); })
                        .then(function(data) {
                            if (data.success) {
                                Swal.fire({ icon:'success', title:'Armazém padrão definido!', timer:1500, showConfirmButton:false })
                                    .then(function() { window.location.href = '?page=stock&section=warehouses'; });
                            }
                        });
                }
            });
        });
    });

});

// Função global para abrir modal de novo armazém
function openNewWarehouse() {
    document.getElementById('whModalTitle').innerHTML = '<i class="fas fa-warehouse me-2"></i>Novo Armazém';
    document.getElementById('warehouseForm').action = '?page=stock&action=storeWarehouse';
    document.getElementById('wh_id').value = '';
    document.getElementById('wh_name').value = '';
    document.getElementById('wh_address').value = '';
    document.getElementById('wh_city').value = '';
    document.getElementById('wh_state').value = '';
    document.getElementById('wh_zip').value = '';
    document.getElementById('wh_phone').value = '';
    document.getElementById('wh_notes').value = '';
    document.getElementById('wh_active_wrap').style.display = 'none';
    document.getElementById('wh_default').checked = false;
}
