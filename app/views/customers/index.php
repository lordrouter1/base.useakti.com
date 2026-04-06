<?php
/**
 * Clientes — Página Unificada com Sidebar (Fase 3)
 * Layout com sidebar, drawer de filtros, toggle cards/tabela,
 * ações em lote, badges de filtros ativos, responsividade.
 *
 * Variáveis disponíveis (carregadas pelo CustomerController::index):
 *   $totalItems     — total de clientes
 *   $limitReached   — se atingiu limite do tenant
 *   $limitInfo      — info do limite
 *   $importFields   — campos disponíveis para mapeamento de importação
 */

$activeSection = $_GET['section'] ?? 'overview';
$validSections = ['overview', 'create', 'import'];
if (!in_array($activeSection, $validSections)) $activeSection = 'overview';
?>

<!-- Styles loaded from assets/css/modules/customers.css via header.php -->
<link rel="stylesheet" href="assets/css/customers.css">
<?php require 'app/views/components/flash-messages.php'; ?>

<div class="container-fluid py-3">

    <!-- ══════ Header ══════ -->
    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom">
        <div>
            <h1 class="h2 mb-1"><i class="fas fa-users me-2 text-primary"></i>Clientes</h1>
            <p class="text-muted mb-0" style="font-size:.82rem;">Gerencie seus clientes, cadastre novos e importe em massa.</p>
        </div>
    </div>

    <?php if (!empty($limitReached)): ?>
    <div class="alert alert-warning border-warning d-flex align-items-center mb-3" role="alert">
        <i class="fas fa-exclamation-triangle fs-5 me-3 text-warning"></i>
        <div>
            <strong>Limite do plano atingido!</strong> Você possui <strong><?= $limitInfo['current'] ?></strong> de <strong><?= $limitInfo['max'] ?></strong> clientes permitidos.
            <span class="text-muted">Para cadastrar mais clientes, entre em contato com o suporte para fazer um upgrade do seu plano.</span>
        </div>
    </div>
    <?php endif; ?>

    <div class="row g-4">

        <!-- ═══════════════════════════════════════════════ -->
        <!-- SIDEBAR — Menu Lateral de Seções (3/12)         -->
        <!-- ═══════════════════════════════════════════════ -->
        <div class="col-lg-3 cst-sidebar-col">
            <div class="card border-0 shadow-sm" style="border-radius:12px;">
                <div class="card-body p-3">
                    <nav class="cst-sidebar">
                        <div class="cst-sidebar-label">Clientes</div>

                        <a href="#" class="cst-nav-item <?= $activeSection === 'overview' ? 'active' : '' ?>" data-section="overview">
                            <span class="cst-nav-icon nav-icon-blue">
                                <i class="fas fa-users"></i>
                            </span>
                            <span>Visão Geral</span>
                            <span class="cst-nav-count nav-icon-blue"><?= $totalItems ?></span>
                        </a>

                        <div class="cst-sidebar-divider"></div>

                        <a href="#" class="cst-nav-item <?= $activeSection === 'create' ? 'active' : '' ?>" data-section="create">
                            <span class="cst-nav-icon nav-icon-green">
                                <i class="fas fa-user-plus"></i>
                            </span>
                            <span>Cadastro de Clientes</span>
                        </a>

                        <a href="#" class="cst-nav-item <?= $activeSection === 'import' ? 'active' : '' ?>" data-section="import">
                            <span class="cst-nav-icon nav-icon-orange">
                                <i class="fas fa-file-import"></i>
                            </span>
                            <span>Importação</span>
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
                        Use a <span class="fw-bold text-primary">Visão Geral</span> para buscar e gerenciar seus clientes,
                        <span class="fw-bold text-success">Cadastro de Clientes</span> para registrar rapidamente
                        e <span class="fw-bold text-orange">Importação</span> para adicionar clientes em massa via planilha.
                    </p>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════ -->
        <!-- CONTEÚDO PRINCIPAL — Seção Ativa (9/12)         -->
        <!-- ═══════════════════════════════════════════════ -->
        <div class="col-lg-9">

            <!-- ══════════════════════════════════════ -->
            <!-- SEÇÃO: Visão Geral dos Clientes         -->
            <!-- ══════════════════════════════════════ -->
            <div class="cst-section <?= $activeSection === 'overview' ? 'active' : '' ?>" id="cst-overview">

                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                    <div class="d-flex align-items-center">
                        <div class="icon-circle icon-circle-blue me-2">
                            <i class="fas fa-users text-blue" style="font-size:.85rem;"></i>
                        </div>
                        <div>
                            <h5 class="mb-0" style="font-size:1rem;">Clientes Cadastrados</h5>
                            <p class="text-muted mb-0" style="font-size:.72rem;">Lista completa com filtros avançados.</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <!-- View toggle -->
                        <div class="cst-view-toggle">
                            <button type="button" class="btn active" id="btnViewTable" title="Visualizar como tabela"><i class="fas fa-list"></i></button>
                            <button type="button" class="btn" id="btnViewCards" title="Visualizar como cards"><i class="fas fa-th-large"></i></button>
                        </div>
                        <!-- Export -->
                        <button type="button" class="btn btn-sm btn-outline-success" id="btnExportCsv" title="Exportar CSV">
                            <i class="fas fa-file-csv me-1"></i><span class="d-none d-md-inline">Exportar</span>
                        </button>
                        <?php if (empty($limitReached)): ?>
                        <a href="?page=customers&action=create" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus me-1"></i><span class="d-none d-md-inline">Novo Cliente</span>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Filtros + Busca -->
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body p-3">
                        <div class="row g-2 align-items-end">
                            <div class="col">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" id="cst_search" class="form-control cst-filter" placeholder="Buscar por nome, e-mail, telefone ou documento..." autocomplete="off">
                                </div>
                            </div>
                            <div class="col-auto">
                                <button type="button" class="btn btn-sm btn-outline-primary" id="btnOpenFilters" data-bs-toggle="offcanvas" data-bs-target="#filterDrawer">
                                    <i class="fas fa-sliders-h me-1"></i>Filtros
                                </button>
                            </div>
                            <div class="col-auto">
                                <a href="#" class="text-muted small" id="btnClearCustomers" style="font-size:.72rem;"><i class="fas fa-times me-1"></i>Limpar</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Badges de filtros ativos -->
                <div class="cst-filter-badges" id="filterBadges" style="display:none;"></div>

                <!-- Toolbar de ações em lote -->
                <div class="cst-bulk-bar" id="bulkToolbar">
                    <i class="fas fa-check-square me-1"></i>
                    <span id="bulkCount">0</span> selecionado(s)
                    <div class="ms-auto d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-light" id="btnBulkExport" title="Exportar selecionados"><i class="fas fa-file-export me-1"></i>Exportar</button>
                        <div class="dropdown">
                            <button type="button" class="btn btn-sm btn-outline-light dropdown-toggle" data-bs-toggle="dropdown" title="Alterar status"><i class="fas fa-exchange-alt me-1"></i>Status</button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item small bulk-status-action" href="#" data-status="active"><i class="fas fa-check-circle text-success me-1"></i>Ativar</a></li>
                                <li><a class="dropdown-item small bulk-status-action" href="#" data-status="inactive"><i class="fas fa-pause-circle text-warning me-1"></i>Inativar</a></li>
                                <li><a class="dropdown-item small bulk-status-action" href="#" data-status="blocked"><i class="fas fa-ban text-danger me-1"></i>Bloquear</a></li>
                            </ul>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-light" id="btnBulkDelete" title="Excluir selecionados"><i class="fas fa-trash me-1"></i>Excluir</button>
                    </div>
                </div>

                <!-- TABELA de Clientes -->
                <div class="cst-table-wrap active" id="customersTableWrap">
                    <div class="table-responsive bg-white rounded shadow-sm">
                        <table class="table table-hover align-middle mb-0" id="customersTable">
                            <caption class="visually-hidden">Lista de clientes</caption>
                            <thead class="bg-light">
                                <tr>
                                    <th class="py-3 ps-3" style="width:40px;">
                                        <input type="checkbox" class="form-check-input" id="checkAll" title="Selecionar todos">
                                    </th>
                                    <th class="py-3">Nome</th>
                                    <th class="py-3" style="width:60px;">Tipo</th>
                                    <th class="py-3" style="width:150px;">Documento</th>
                                    <th class="py-3 d-none d-lg-table-cell" style="width:150px;">Cidade/UF</th>
                                    <th class="py-3" style="width:90px;">Status</th>
                                    <th class="py-3 text-end pe-4" style="width:130px;">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="customersTableBody">
                                <tr><td colspan="7" class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin me-1"></i>Carregando...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- CARDS de Clientes -->
                <div class="cst-cards-grid" id="customersCardsGrid">
                    <!-- Preenchido via JS -->
                </div>

                <!-- Paginação -->
                <div class="d-flex justify-content-between align-items-center mt-2">
                    <span class="text-muted small" id="cstPaginationInfo"></span>
                    <nav><ul class="pagination pagination-sm mb-0" id="cstPagination"></ul></nav>
                </div>

            </div><!-- /.cst-section overview -->


            <!-- ══════════════════════════════════════ -->
            <!-- SEÇÃO: Cadastro de Clientes             -->
            <!-- ══════════════════════════════════════ -->
            <div class="cst-section <?= $activeSection === 'create' ? 'active' : '' ?>" id="cst-create">
                <div class="d-flex align-items-center mb-3">
                    <div class="icon-circle icon-circle-green me-2">
                        <i class="fas fa-user-plus text-green" style="font-size:.85rem;"></i>
                    </div>
                    <div>
                        <h5 class="mb-0" style="font-size:1rem;">Cadastrar Novo Cliente</h5>
                        <p class="text-muted mb-0" style="font-size:.72rem;">Preencha as informações para adicionar um novo cliente.</p>
                    </div>
                </div>

                <?php if (!empty($limitReached)): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-lock fa-3x text-warning mb-3"></i>
                        <h5 class="text-warning">Limite de Clientes Atingido</h5>
                        <p class="text-muted">Você possui <strong><?= $limitInfo['current'] ?></strong> de <strong><?= $limitInfo['max'] ?></strong> clientes permitidos.<br>Entre em contato com o suporte para fazer um upgrade.</p>
                    </div>
                </div>
                <?php else: ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <div class="mb-4">
                            <div class="icon-circle icon-circle-80 icon-circle-green d-inline-flex mx-auto">
                                <i class="fas fa-user-plus fa-2x text-success"></i>
                            </div>
                        </div>
                        <h5 class="mb-2">Novo Cliente</h5>
                        <p class="text-muted mb-4" style="font-size:.85rem;">Clique no botão abaixo para acessar o formulário completo de cadastro de cliente, com dados pessoais, endereço e muito mais.</p>
                        <a href="?page=customers&action=create" class="btn btn-success btn-lg px-5">
                            <i class="fas fa-plus me-2"></i>Cadastrar Novo Cliente
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>


            <!-- ══════════════════════════════════════ -->
            <!-- SEÇÃO: Importar Clientes               -->
            <!-- ══════════════════════════════════════ -->
            <div class="cst-section <?= $activeSection === 'import' ? 'active' : '' ?>" id="cst-import">

                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center">
                        <div class="icon-circle icon-circle-orange me-2">
                            <i class="fas fa-file-import text-orange" style="font-size:.85rem;"></i>
                        </div>
                        <div>
                            <h5 class="mb-0" style="font-size:1rem;">Importar Clientes em Massa</h5>
                            <p class="text-muted mb-0" style="font-size:.72rem;">Importe clientes de arquivos CSV, XLS ou XLSX com mapeamento dinâmico de colunas. Detecção automática de CPF/CNPJ, PF/PJ e validação de dados.</p>
                        </div>
                    </div>
                    <a href="?page=customers&action=downloadImportTemplate" class="btn btn-sm btn-outline-success">
                        <i class="fas fa-file-excel me-1"></i> Baixar Modelo
                    </a>
                </div>

                <?php if (!empty($limitReached)): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-lock fa-3x text-warning mb-3"></i>
                        <h5 class="text-warning">Limite de Clientes Atingido</h5>
                        <p class="text-muted">Não é possível importar novos clientes.</p>
                    </div>
                </div>
                <?php else: ?>

                <!-- Stepper visual -->
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

                <!-- Step 1: Upload -->
                <div class="import-step active" id="importStep1">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <!-- Modo de importação -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small"><i class="fas fa-cog me-1 text-primary"></i>Modo de Importação</label>
                                    <select class="form-select form-select-sm" id="importMode">
                                        <option value="create" selected>Criar novos registros</option>
                                        <option value="update">Apenas atualizar existentes</option>
                                        <option value="create_or_update">Criar ou atualizar (merge)</option>
                                    </select>
                                    <div class="form-text" style="font-size:.68rem;" id="importModeHelp">Todos os registros serão criados como novos clientes.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small"><i class="fas fa-bookmark me-1 text-warning"></i>Perfil de Mapeamento</label>
                                    <div class="input-group input-group-sm">
                                        <select class="form-select form-select-sm" id="mappingProfileSelect">
                                            <option value="">— Mapeamento automático —</option>
                                        </select>
                                        <button class="btn btn-outline-danger btn-sm" type="button" id="btnDeleteProfile" title="Excluir perfil" style="display:none;"><i class="fas fa-trash"></i></button>
                                    </div>
                                </div>
                            </div>
                            <div class="import-dropzone" id="importDropzone">
                                <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                <h6 class="mb-1">Arraste o arquivo aqui</h6>
                                <p class="text-muted small mb-2">ou clique para selecionar</p>
                                <input type="file" id="importFileInput" accept=".csv,.xls,.xlsx" style="display:none;">
                                <p class="text-muted mb-0" style="font-size:.7rem;">Formatos aceitos: <strong>CSV</strong>, <strong>XLS</strong>, <strong>XLSX</strong></p>
                            </div>
                            <div id="importFileInfo" style="display:none;" class="mt-3">
                                <div class="alert alert-success d-flex align-items-center py-2 mb-0">
                                    <i class="fas fa-file-circle-check fa-lg me-3 text-success"></i>
                                    <div class="flex-grow-1">
                                        <strong id="importFileName">arquivo.csv</strong>
                                        <span class="text-muted small ms-2" id="importFileSize"></span>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-danger ms-2" id="btnRemoveFile"><i class="fas fa-times"></i></button>
                                </div>
                            </div>
                            <div class="text-end mt-3">
                                <button type="button" class="btn btn-primary" id="btnParseFile" disabled><i class="fas fa-cog me-1"></i>Analisar Arquivo</button>
                            </div>
                        </div>
                    </div>
                    <!-- Histórico de Importações -->
                    <div class="card border-0 shadow-sm mt-3">
                        <div class="card-header bg-white py-2 border-bottom d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold small"><i class="fas fa-history me-2 text-info"></i>Histórico de Importações</h6>
                            <button type="button" class="btn btn-sm btn-outline-info" id="btnRefreshHistory"><i class="fas fa-sync-alt"></i></button>
                        </div>
                        <div class="card-body p-0" id="importHistoryContainer">
                            <div class="text-center py-3 text-muted small">Carregando...</div>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Mapeamento -->
                <div class="import-step" id="importStep2">
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-white py-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 fw-bold"><i class="fas fa-columns me-2 text-primary"></i>Mapeamento de Colunas</h6>
                                <span class="badge bg-info" id="totalRowsBadge">0 linhas</span>
                            </div>
                            <p class="text-muted mb-0 mt-1" style="font-size:.72rem;">Selecione a qual campo do sistema cada coluna do arquivo corresponde.</p>
                        </div>
                        <div class="card-body p-3">
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm align-middle mb-0" id="mappingTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width:40px;" class="text-center"><input type="checkbox" class="form-check-input" id="checkAllCols" checked title="Marcar/desmarcar todas"></th>
                                            <th>Coluna do Arquivo</th>
                                            <th>Amostra de Dados</th>
                                            <th style="width:220px;">Corresponde a</th>
                                        </tr>
                                    </thead>
                                    <tbody id="mappingTableBody"></tbody>
                                </table>
                            </div>
                            <div id="mappingValidation" class="mt-3" style="display:none;"></div>
                        </div>
                    </div>
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h6 class="mb-0 fw-bold"><i class="fas fa-table me-2 text-info"></i>Preview dos Dados <small class="text-muted fw-normal">(primeiras 10 linhas)</small></h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="preview-table" id="previewTableWrap">
                                <table class="table table-striped table-bordered table-sm mb-0" id="previewTable">
                                    <thead id="previewTableHead"></thead>
                                    <tbody id="previewTableBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-outline-secondary" id="btnBackToStep1"><i class="fas fa-arrow-left me-1"></i>Voltar</button>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-warning btn-sm" id="btnSaveProfile" title="Salvar este mapeamento como perfil"><i class="fas fa-bookmark me-1"></i>Salvar Perfil</button>
                            <button type="button" class="btn btn-success btn-lg" id="btnDoImport" disabled><i class="fas fa-upload me-1"></i>Importar <span id="importCountLabel">0</span> Cliente(s)</button>
                        </div>
                    </div>

                    <!-- Barra de progresso (Rec 1) -->
                    <div class="card border-0 shadow-sm mt-3" id="importProgressCard" style="display:none;">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold small"><i class="fas fa-spinner fa-spin me-1 text-primary"></i>Importando...</span>
                                <span class="badge bg-primary" id="progressPercent">0%</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" id="importProgressBar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="d-flex justify-content-between mt-2 text-muted" style="font-size:.7rem;">
                                <span id="progressDetail">0 de 0 processados</span>
                                <span id="progressStats">Criados: 0 | Atualizados: 0 | Erros: 0</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Resultado -->
                <div class="import-step" id="importStep3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4" id="importResultContent"></div>
                    </div>
                    <div class="text-center mt-3">
                        <button type="button" class="btn btn-outline-danger me-2" id="btnUndoImport" style="display:none;" data-batch-id=""><i class="fas fa-undo me-1"></i>Desfazer Importação</button>
                        <button type="button" class="btn btn-outline-primary" id="btnNewImport"><i class="fas fa-redo me-1"></i>Nova Importação</button>
                        <a href="#" class="btn btn-primary ms-2 cst-go-overview"><i class="fas fa-users me-1"></i>Ver Clientes</a>
                    </div>
                </div>

                <?php endif; ?>
            </div>

        </div><!-- /.col-lg-9 -->
    </div><!-- /.row -->
</div><!-- /.container-fluid -->


<!-- ═══════════════════════════════════════════════ -->
<!-- DRAWER DE FILTROS — Off-canvas Bootstrap 5      -->
<!-- ═══════════════════════════════════════════════ -->
<div class="offcanvas offcanvas-end cst-filter-drawer" tabindex="-1" id="filterDrawer" aria-labelledby="filterDrawerLabel" style="width:320px;">
    <div class="offcanvas-header">
        <h6 class="offcanvas-title fw-bold" id="filterDrawerLabel"><i class="fas fa-sliders-h me-2 text-primary"></i>Filtros Avançados</h6>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Fechar"></button>
    </div>
    <div class="offcanvas-body">
        <!-- Status -->
        <div class="cst-filter-group">
            <label>Status</label>
            <div class="d-flex flex-wrap gap-2">
                <div class="form-check"><input class="form-check-input filter-status" type="checkbox" value="active" id="fStatusActive" checked><label class="form-check-label small" for="fStatusActive">Ativo</label></div>
                <div class="form-check"><input class="form-check-input filter-status" type="checkbox" value="inactive" id="fStatusInactive"><label class="form-check-label small" for="fStatusInactive">Inativo</label></div>
                <div class="form-check"><input class="form-check-input filter-status" type="checkbox" value="blocked" id="fStatusBlocked"><label class="form-check-label small" for="fStatusBlocked">Bloqueado</label></div>
            </div>
        </div>

        <!-- Tipo de Pessoa -->
        <div class="cst-filter-group">
            <label>Tipo de Pessoa</label>
            <div class="d-flex flex-wrap gap-2">
                <div class="form-check"><input class="form-check-input filter-person-type" type="checkbox" value="PF" id="fTypePF"><label class="form-check-label small" for="fTypePF">Pessoa Física</label></div>
                <div class="form-check"><input class="form-check-input filter-person-type" type="checkbox" value="PJ" id="fTypePJ"><label class="form-check-label small" for="fTypePJ">Pessoa Jurídica</label></div>
            </div>
        </div>

        <!-- UF -->
        <div class="cst-filter-group">
            <label for="fState">Estado (UF)</label>
            <select id="fState" class="form-select form-select-sm">
                <option value="">Todos</option>
                <?php
                $ufs = ['AC','AL','AM','AP','BA','CE','DF','ES','GO','MA','MG','MS','MT','PA','PB','PE','PI','PR','RJ','RN','RO','RR','RS','SC','SE','SP','TO'];
                foreach ($ufs as $uf): ?>
                <option value="<?= $uf ?>"><?= $uf ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Cidade -->
        <div class="cst-filter-group">
            <label for="fCity">Cidade</label>
            <input type="text" id="fCity" class="form-control form-control-sm" placeholder="Nome da cidade...">
        </div>

        <!-- Tags -->
        <div class="cst-filter-group">
            <label for="fTags">Tags</label>
            <input type="text" id="fTags" class="form-control form-control-sm" placeholder="Ex: VIP, Atacado...">
        </div>

        <!-- Período -->
        <div class="cst-filter-group">
            <label>Período de Cadastro</label>
            <div class="row g-2">
                <div class="col-6">
                    <input type="date" id="fDateFrom" class="form-control form-control-sm" title="De">
                </div>
                <div class="col-6">
                    <input type="date" id="fDateTo" class="form-control form-control-sm" title="Até">
                </div>
            </div>
        </div>

        <hr>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary btn-sm flex-fill" id="btnApplyFilters"><i class="fas fa-check me-1"></i>Aplicar</button>
            <button type="button" class="btn btn-outline-secondary btn-sm flex-fill" id="btnResetFilters"><i class="fas fa-undo me-1"></i>Limpar</button>
        </div>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {

    // ═══════════════════════════════════════════
    // ═══ SIDEBAR NAVIGATION (SPA-like)       ═══
    // ═══════════════════════════════════════════
    function navigateToSection(sectionId) {
        document.querySelectorAll('.cst-nav-item').forEach(function(n) { n.classList.remove('active'); });
        var navItem = document.querySelector('.cst-nav-item[data-section="' + sectionId + '"]');
        if (navItem) navItem.classList.add('active');

        document.querySelectorAll('.cst-section').forEach(function(s) { s.classList.remove('active'); });
        var target = document.getElementById('cst-' + sectionId);
        if (target) target.classList.add('active');

        var url = new URL(window.location);
        url.searchParams.set('section', sectionId);
        history.replaceState(null, '', url);

        if (sectionId === 'overview') loadCustomers(1);
    }

    document.querySelectorAll('.cst-nav-item').forEach(function(item) {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            var section = this.dataset.section;
            if (!section) return;
            navigateToSection(section);
        });
    });

    document.querySelectorAll('.cst-go-overview').forEach(function(a) {
        a.addEventListener('click', function(e) { e.preventDefault(); navigateToSection('overview'); });
    });


    // ═══════════════════════════════════════════
    // ═══ STATUS ALERTS                        ═══
    // ═══════════════════════════════════════════
    <?php if (isset($_GET['status'])): ?>
    var urlClean = new URL(window.location);
    urlClean.searchParams.delete('status');
    window.history.replaceState({}, '', urlClean);
    <?php if ($_GET['status'] == 'success'): ?>
    Swal.fire({ icon:'success', title:'Sucesso!', text:'Cliente salvo com sucesso!', timer:2000, showConfirmButton:false });
    <?php endif; ?>
    <?php if ($_GET['status'] == 'limit_customers'): ?>
    Swal.fire({ icon:'warning', title:'Limite atingido!', text:'Você atingiu o limite de clientes do seu plano.', confirmButtonColor:'#3498db' });
    <?php endif; ?>
    <?php endif; ?>


    // ═══════════════════════════════════════════
    // ═══ CSRF TOKEN                           ═══
    // ═══════════════════════════════════════════
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

    function escHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // ═══════════════════════════════════════════
    // ═══ VIEW TOGGLE — Tabela / Cards        ═══
    // ═══════════════════════════════════════════
    var currentView = 'table';

    document.getElementById('btnViewTable').addEventListener('click', function() {
        currentView = 'table';
        this.classList.add('active');
        document.getElementById('btnViewCards').classList.remove('active');
        document.getElementById('customersTableWrap').classList.add('active');
        document.getElementById('customersCardsGrid').classList.remove('active');
    });

    document.getElementById('btnViewCards').addEventListener('click', function() {
        currentView = 'cards';
        this.classList.add('active');
        document.getElementById('btnViewTable').classList.remove('active');
        document.getElementById('customersCardsGrid').classList.add('active');
        document.getElementById('customersTableWrap').classList.remove('active');
    });


    // ═══════════════════════════════════════════
    // ═══ FILTROS AVANÇADOS                    ═══
    // ═══════════════════════════════════════════
    var activeFilters = {};

    function collectFilters() {
        activeFilters = {};

        // Status
        var statuses = [];
        document.querySelectorAll('.filter-status:checked').forEach(function(cb) { statuses.push(cb.value); });
        if (statuses.length > 0 && statuses.length < 3) activeFilters.status = statuses.join(',');

        // Person type
        var types = [];
        document.querySelectorAll('.filter-person-type:checked').forEach(function(cb) { types.push(cb.value); });
        if (types.length === 1) activeFilters.person_type = types[0];

        // State
        var state = document.getElementById('fState').value;
        if (state) activeFilters.state = state;

        // City
        var city = document.getElementById('fCity').value.trim();
        if (city) activeFilters.city = city;

        // Tags
        var tags = document.getElementById('fTags').value.trim();
        if (tags) activeFilters.tags = tags;

        // Date range
        var from = document.getElementById('fDateFrom').value;
        var to = document.getElementById('fDateTo').value;
        if (from) activeFilters.from = from;
        if (to) activeFilters.to = to;
    }

    function renderFilterBadges() {
        var container = document.getElementById('filterBadges');
        var badges = [];
        var labelMap = {
            status: 'Status',
            person_type: 'Tipo',
            state: 'UF',
            city: 'Cidade',
            tags: 'Tags',
            from: 'De',
            to: 'Até'
        };

        for (var key in activeFilters) {
            var label = labelMap[key] || key;
            var value = activeFilters[key];
            badges.push(
                '<span class="cst-filter-badge">' + label + ': <strong>' + escHtml(value) + '</strong>' +
                '<button type="button" class="btn-close-filter" data-filter="' + key + '" title="Remover">&times;</button>' +
                '</span>'
            );
        }

        if (badges.length > 0) {
            container.innerHTML = badges.join('');
            container.style.display = '';
            // Bind remove buttons
            container.querySelectorAll('.btn-close-filter').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    removeFilter(this.dataset.filter);
                });
            });
        } else {
            container.innerHTML = '';
            container.style.display = 'none';
        }
    }

    function removeFilter(key) {
        delete activeFilters[key];
        // Reset corresponding UI element
        if (key === 'status') document.querySelectorAll('.filter-status').forEach(function(cb) { cb.checked = true; });
        if (key === 'person_type') document.querySelectorAll('.filter-person-type').forEach(function(cb) { cb.checked = false; });
        if (key === 'state') document.getElementById('fState').value = '';
        if (key === 'city') document.getElementById('fCity').value = '';
        if (key === 'tags') document.getElementById('fTags').value = '';
        if (key === 'from') document.getElementById('fDateFrom').value = '';
        if (key === 'to') document.getElementById('fDateTo').value = '';
        renderFilterBadges();
        loadCustomers(1);
    }

    document.getElementById('btnApplyFilters').addEventListener('click', function() {
        collectFilters();
        renderFilterBadges();
        loadCustomers(1);
        // Close drawer
        var drawer = bootstrap.Offcanvas.getInstance(document.getElementById('filterDrawer'));
        if (drawer) drawer.hide();
    });

    document.getElementById('btnResetFilters').addEventListener('click', function() {
        document.querySelectorAll('.filter-status').forEach(function(cb) { cb.checked = (cb.value === 'active'); });
        document.querySelectorAll('.filter-person-type').forEach(function(cb) { cb.checked = false; });
        document.getElementById('fState').value = '';
        document.getElementById('fCity').value = '';
        document.getElementById('fTags').value = '';
        document.getElementById('fDateFrom').value = '';
        document.getElementById('fDateTo').value = '';
        activeFilters = {};
        renderFilterBadges();
    });


    // ═══════════════════════════════════════════
    // ═══ PAGINAÇÃO — Renderizador genérico   ═══
    // ═══════════════════════════════════════════
    function renderPagination(containerId, page, totalPages, total, perPage, callback) {
        var container = document.getElementById(containerId);
        if (!container) return;
        container.innerHTML = '';
        if (totalPages <= 1) return;

        var liPrev = document.createElement('li');
        liPrev.className = 'page-item' + (page <= 1 ? ' disabled' : '');
        liPrev.innerHTML = '<a class="page-link" href="#">&laquo;</a>';
        if (page > 1) liPrev.querySelector('a').addEventListener('click', function(e) { e.preventDefault(); callback(page - 1); });
        container.appendChild(liPrev);

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

        var liNext = document.createElement('li');
        liNext.className = 'page-item' + (page >= totalPages ? ' disabled' : '');
        liNext.innerHTML = '<a class="page-link" href="#">&raquo;</a>';
        if (page < totalPages) liNext.querySelector('a').addEventListener('click', function(e) { e.preventDefault(); callback(page + 1); });
        container.appendChild(liNext);
    }


    // ═══════════════════════════════════════════
    // ═══ VISÃO GERAL — AJAX + Paginação      ═══
    // ═══════════════════════════════════════════
    var cstCurrentPage = 1;
    var selectedIds = [];

    function loadCustomers(page) {
        cstCurrentPage = page || 1;
        selectedIds = [];
        updateBulkToolbar();

        var tbody = document.getElementById('customersTableBody');
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin me-1"></i>Carregando...</td></tr>';

        var cardsGrid = document.getElementById('customersCardsGrid');
        cardsGrid.innerHTML = '';

        var params = new URLSearchParams({
            page: 'customers',
            action: 'getCustomersList',
            search: document.getElementById('cst_search').value,
            pg: cstCurrentPage,
            per_page: 20
        });

        // Append active filters
        for (var key in activeFilters) {
            params.set(key, activeFilters[key]);
        }

        fetch('?' + params.toString())
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success) {
                    tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger py-4">Erro ao carregar dados.</td></tr>';
                    return;
                }

                if (data.items.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-5">' +
                        '<i class="fas fa-users fa-3x mb-3 d-block text-secondary"></i>' +
                        'Nenhum cliente encontrado com os filtros selecionados.' +
                        '</td></tr>';
                    cardsGrid.innerHTML = '<div class="col-12 text-center text-muted py-5"><i class="fas fa-users fa-3x mb-3 d-block text-secondary"></i>Nenhum cliente encontrado.</div>';
                    document.getElementById('cstPagination').innerHTML = '';
                    document.getElementById('cstPaginationInfo').textContent = '0 registros';
                    return;
                }

                renderTable(data.items);
                renderCards(data.items);
                bindRowActions();

                // Paginação
                renderPagination('cstPagination', data.page, data.total_pages, data.total, data.per_page, loadCustomers);
                var infoEl = document.getElementById('cstPaginationInfo');
                if (infoEl) {
                    var from = data.total > 0 ? ((data.page - 1) * data.per_page + 1) : 0;
                    var to = Math.min(data.page * data.per_page, data.total);
                    infoEl.textContent = 'Exibindo ' + from + '–' + to + ' de ' + data.total + ' cliente(s)';
                }

                // Check all reset
                var checkAll = document.getElementById('checkAll');
                if (checkAll) checkAll.checked = false;
            })
            .catch(function() {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger py-4">Erro de comunicação ao carregar clientes.</td></tr>';
            });
    }


    // ═══════════════════════════════════════════
    // ═══ RENDER — Tabela                      ═══
    // ═══════════════════════════════════════════
    function renderTable(items) {
        var tbody = document.getElementById('customersTableBody');
        var html = '';
        var statusMap = { active: ['Ativo', 'badge-status-active'], inactive: ['Inativo', 'badge-status-inactive'], blocked: ['Bloqueado', 'badge-status-blocked'] };

        items.forEach(function(c) {
            var initial = c.name ? c.name.charAt(0).toUpperCase() : '?';
            var photoCell = c.photo
                ? '<img src="' + escHtml(thumbUrl(c.photo, 32, 32)) + '" class="rounded-circle" style="width:32px;height:32px;object-fit:cover;">'
                : '<div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;font-size:.8rem;">' + escHtml(initial) + '</div>';

            var docCell = c.document ? '<span class="badge bg-light text-dark border" style="font-size:.72rem;">' + formatDoc(c.document) + '</span>' : '<span class="text-muted">—</span>';
            var typeCell = (c.person_type === 'PJ')
                ? '<span class="badge bg-info bg-opacity-10 text-info" style="font-size:.68rem;">PJ</span>'
                : '<span class="badge bg-secondary bg-opacity-10 text-secondary" style="font-size:.68rem;">PF</span>';

            var cityUf = '';
            if (c.address_city || c.address_state) {
                cityUf = escHtml((c.address_city || '') + (c.address_state ? ' / ' + c.address_state : ''));
            } else {
                cityUf = '<span class="text-muted">—</span>';
            }

            var st = c.status || 'active';
            var statusInfo = statusMap[st] || ['—', ''];
            var statusBadge = '<span class="badge ' + statusInfo[1] + '" style="font-size:.68rem;padding:.35em .6em;border-radius:6px;">' + statusInfo[0] + '</span>';

            var codeStr = c.code ? '<small class="text-muted d-block" style="font-size:.68rem;">' + escHtml(c.code) + '</small>' : '';

            html += '<tr data-id="' + c.id + '">' +
                '<td class="ps-3"><input type="checkbox" class="form-check-input row-check" value="' + c.id + '"></td>' +
                '<td><div class="d-flex align-items-center">' + photoCell +
                    '<div class="ms-2"><span class="fw-bold" style="font-size:.85rem;">' + escHtml(c.name) + '</span>' + codeStr + '</div></div></td>' +
                '<td>' + typeCell + '</td>' +
                '<td>' + docCell + '</td>' +
                '<td class="d-none d-lg-table-cell" style="font-size:.78rem;">' + cityUf + '</td>' +
                '<td>' + statusBadge + '</td>' +
                '<td class="text-end pe-3"><div class="btn-group btn-group-sm">' +
                    '<a href="?page=customers&action=view&id=' + c.id + '" class="btn btn-outline-info" title="Ver ficha"><i class="fas fa-eye"></i></a>' +
                    '<a href="?page=customers&action=edit&id=' + c.id + '" class="btn btn-outline-primary" title="Editar"><i class="fas fa-edit"></i></a>' +
                    '<button type="button" class="btn btn-outline-danger btn-delete-customer" data-id="' + c.id + '" data-name="' + escHtml(c.name) + '" title="Excluir"><i class="fas fa-trash"></i></button>' +
                '</div></td>' +
            '</tr>';
        });
        tbody.innerHTML = html;
    }


    // ═══════════════════════════════════════════
    // ═══ RENDER — Cards                       ═══
    // ═══════════════════════════════════════════
    function renderCards(items) {
        var grid = document.getElementById('customersCardsGrid');
        var html = '';
        var statusMap = { active: ['Ativo', 'badge-status-active'], inactive: ['Inativo', 'badge-status-inactive'], blocked: ['Bloqueado', 'badge-status-blocked'] };

        items.forEach(function(c) {
            var initial = c.name ? c.name.charAt(0).toUpperCase() : '?';
            var avatar = c.photo
                ? '<img src="' + escHtml(thumbUrl(c.photo, 80, 80)) + '" class="cst-card-avatar">'
                : '<div class="cst-card-avatar-placeholder">' + escHtml(initial) + '</div>';

            var typeLabel = (c.person_type === 'PJ') ? 'PJ' : 'PF';
            var st = c.status || 'active';
            var statusInfo = statusMap[st] || ['—', ''];

            var cityUf = (c.address_city || c.address_state)
                ? '<i class="fas fa-map-marker-alt"></i> ' + escHtml((c.address_city || '') + (c.address_state ? ' / ' + c.address_state : ''))
                : '';

            var emailLine = c.email
                ? '<span><i class="fas fa-envelope"></i> ' + escHtml(c.email) + '</span>' : '';
            var phoneLine = (c.cellphone || c.phone)
                ? '<span><i class="fas fa-phone"></i> ' + escHtml(c.cellphone || c.phone) + '</span>' : '';

            html += '<div class="cst-card-col">' +
                '<div class="cst-customer-card">' +
                    '<div class="d-flex align-items-center gap-3 mb-2">' +
                        avatar +
                        '<div class="cst-card-body" style="min-width:0;">' +
                            '<div class="cst-card-name">' + escHtml(c.name) + '</div>' +
                            '<div class="cst-card-meta">' +
                                typeLabel + ' · <span class="badge ' + statusInfo[1] + '" style="font-size:.62rem;padding:.2em .5em;">' + statusInfo[0] + '</span>' +
                            '</div>' +
                            (cityUf ? '<div class="cst-card-meta mt-1" style="font-size:.7rem;">' + cityUf + '</div>' : '') +
                        '</div>' +
                    '</div>' +
                    '<div class="cst-card-info">' +
                        emailLine + phoneLine +
                    '</div>' +
                    '<div class="cst-card-actions mt-1">' +
                        '<a href="?page=customers&action=view&id=' + c.id + '" class="btn btn-sm btn-outline-info flex-fill"><i class="fas fa-eye me-1"></i>Ver</a>' +
                        '<a href="?page=customers&action=edit&id=' + c.id + '" class="btn btn-sm btn-outline-primary flex-fill"><i class="fas fa-edit me-1"></i>Editar</a>' +
                    '</div>' +
                '</div>' +
            '</div>';
        });
        grid.innerHTML = html;
    }


    // ═══════════════════════════════════════════
    // ═══ HELPERS                              ═══
    // ═══════════════════════════════════════════
    function formatDoc(doc) {
        if (!doc) return '';
        var d = doc.replace(/\D/g, '');
        if (d.length === 11) return d.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
        if (d.length === 14) return d.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
        return doc;
    }


    // ═══════════════════════════════════════════
    // ═══ ROW ACTIONS — Delete, Checkbox       ═══
    // ═══════════════════════════════════════════
    function bindRowActions() {
        // Delete buttons
        document.querySelectorAll('.btn-delete-customer').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var id = this.dataset.id;
                var name = this.dataset.name;
                Swal.fire({
                    title: 'Excluir cliente?',
                    html: 'Deseja realmente excluir <strong>' + name + '</strong>?<br><small class="text-muted">O registro será inativado (soft delete).</small>',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#c0392b',
                    cancelButtonColor: '#95a5a6',
                    confirmButtonText: '<i class="fas fa-trash me-1"></i> Sim, excluir',
                    cancelButtonText: 'Cancelar'
                }).then(function(result) {
                    if (result.isConfirmed) {
                        var fd = new FormData();
                        fd.append('id', id);
                        fetch('?page=customers&action=delete', { method: 'POST', body: fd, headers: { 'X-CSRF-TOKEN': csrfToken } })
                            .then(function(r) { return r.json(); })
                            .then(function(data) {
                                if (data.success) {
                                    Swal.mixin({toast:true,position:'top-end',showConfirmButton:false,timer:2000,timerProgressBar:true})
                                        .fire({icon:'success',title:'Cliente excluído com sucesso'});
                                    loadCustomers(cstCurrentPage);
                                } else {
                                    Swal.fire({icon:'error',title:'Erro',text:data.message || 'Não foi possível excluir.'});
                                }
                            })
                            .catch(function() {
                                Swal.fire({icon:'error',title:'Erro de comunicação'});
                            });
                    }
                });
            });
        });

        // Row checkboxes
        document.querySelectorAll('.row-check').forEach(function(cb) {
            cb.addEventListener('change', function() {
                var id = parseInt(this.value);
                if (this.checked) {
                    if (selectedIds.indexOf(id) === -1) selectedIds.push(id);
                } else {
                    selectedIds = selectedIds.filter(function(x) { return x !== id; });
                }
                updateBulkToolbar();
            });
        });
    }

    // Check all
    document.getElementById('checkAll').addEventListener('change', function() {
        var checked = this.checked;
        if (!checked) selectedIds = [];
        document.querySelectorAll('.row-check').forEach(function(cb) {
            cb.checked = checked;
            var id = parseInt(cb.value);
            if (checked && selectedIds.indexOf(id) === -1) selectedIds.push(id);
        });
        updateBulkToolbar();
    });


    // ═══════════════════════════════════════════
    // ═══ BULK ACTIONS — Toolbar               ═══
    // ═══════════════════════════════════════════
    function updateBulkToolbar() {
        var toolbar = document.getElementById('bulkToolbar');
        var countEl = document.getElementById('bulkCount');
        if (selectedIds.length > 0) {
            toolbar.classList.add('show');
            countEl.textContent = selectedIds.length;
        } else {
            toolbar.classList.remove('show');
        }
    }

    // Bulk status
    document.querySelectorAll('.bulk-status-action').forEach(function(a) {
        a.addEventListener('click', function(e) {
            e.preventDefault();
            var status = this.dataset.status;
            var statusLabels = { active: 'Ativar', inactive: 'Inativar', blocked: 'Bloquear' };
            // Map view status to controller bulk_action
            var bulkActionMap = { active: 'activate', inactive: 'inactivate', blocked: 'block' };
            Swal.fire({
                title: statusLabels[status] + ' ' + selectedIds.length + ' cliente(s)?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sim, ' + statusLabels[status].toLowerCase(),
                cancelButtonText: 'Cancelar'
            }).then(function(result) {
                if (result.isConfirmed) {
                    var fd = new FormData();
                    fd.append('bulk_action', bulkActionMap[status]);
                    selectedIds.forEach(function(id) { fd.append('ids[]', id); });
                    fetch('?page=customers&action=bulkAction', { method: 'POST', body: fd, headers: { 'X-CSRF-TOKEN': csrfToken } })
                        .then(function(r) { return r.json(); })
                        .then(function(data) {
                            if (data.success) {
                                Swal.mixin({toast:true,position:'top-end',showConfirmButton:false,timer:2000,timerProgressBar:true})
                                    .fire({icon:'success',title:data.message || 'Ação realizada com sucesso'});
                                loadCustomers(cstCurrentPage);
                            } else {
                                Swal.fire({icon:'error',title:'Erro',text:data.message});
                            }
                        });
                }
            });
        });
    });

    // Bulk delete
    document.getElementById('btnBulkDelete').addEventListener('click', function() {
        Swal.fire({
            title: 'Excluir ' + selectedIds.length + ' cliente(s)?',
            html: '<small class="text-muted">Os registros serão inativados (soft delete).</small>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#c0392b',
            confirmButtonText: '<i class="fas fa-trash me-1"></i>Excluir',
            cancelButtonText: 'Cancelar'
        }).then(function(result) {
            if (result.isConfirmed) {
                var fd = new FormData();
                fd.append('bulk_action', 'delete');
                selectedIds.forEach(function(id) { fd.append('ids[]', id); });
                fetch('?page=customers&action=bulkAction', { method: 'POST', body: fd, headers: { 'X-CSRF-TOKEN': csrfToken } })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.success) {
                            Swal.mixin({toast:true,position:'top-end',showConfirmButton:false,timer:2000,timerProgressBar:true})
                                .fire({icon:'success',title:data.message || 'Registros excluídos'});
                            loadCustomers(cstCurrentPage);
                        } else {
                            Swal.fire({icon:'error',title:'Erro',text:data.message});
                        }
                    });
            }
        });
    });

    // Bulk export
    document.getElementById('btnBulkExport').addEventListener('click', function() {
        if (selectedIds.length === 0) return;
        var params = new URLSearchParams({ page: 'customers', action: 'export', format: 'csv' });
        params.set('ids', selectedIds.join(','));
        window.location.href = '?' + params.toString();
    });


    // ═══════════════════════════════════════════
    // ═══ EXPORT BUTTON                        ═══
    // ═══════════════════════════════════════════
    document.getElementById('btnExportCsv').addEventListener('click', function() {
        var params = new URLSearchParams({ page: 'customers', action: 'export', format: 'csv' });
        for (var key in activeFilters) { params.set(key, activeFilters[key]); }
        params.set('search', document.getElementById('cst_search').value);
        window.location.href = '?' + params.toString();
    });


    // ═══════════════════════════════════════════
    // ═══ SEARCH DEBOUNCE                      ═══
    // ═══════════════════════════════════════════
    var _cstDebounce = null;
    document.getElementById('cst_search').addEventListener('input', function() {
        clearTimeout(_cstDebounce);
        _cstDebounce = setTimeout(function() { loadCustomers(1); }, 350);
    });

    document.getElementById('btnClearCustomers').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('cst_search').value = '';
        activeFilters = {};
        document.querySelectorAll('.filter-status').forEach(function(cb) { cb.checked = (cb.value === 'active'); });
        document.querySelectorAll('.filter-person-type').forEach(function(cb) { cb.checked = false; });
        document.getElementById('fState').value = '';
        document.getElementById('fCity').value = '';
        document.getElementById('fTags').value = '';
        document.getElementById('fDateFrom').value = '';
        document.getElementById('fDateTo').value = '';
        renderFilterBadges();
        loadCustomers(1);
    });

    // Keyboard shortcut: "/" to focus search
    document.addEventListener('keydown', function(e) {
        if (e.key === '/' && !e.ctrlKey && !e.metaKey) {
            var active = document.activeElement;
            if (active && (active.tagName === 'INPUT' || active.tagName === 'TEXTAREA' || active.tagName === 'SELECT')) return;
            e.preventDefault();
            var searchEl = document.getElementById('cst_search');
            if (searchEl) searchEl.focus();
        }
        // Ctrl+N → new customer
        if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
            e.preventDefault();
            window.location.href = '?page=customers&action=create';
        }
        // Ctrl+E → export
        if ((e.ctrlKey || e.metaKey) && e.key === 'e') {
            var active2 = document.activeElement;
            if (active2 && (active2.tagName === 'INPUT' || active2.tagName === 'TEXTAREA')) return;
            e.preventDefault();
            document.getElementById('btnExportCsv').click();
        }
    });

    // Load on section active
    <?php if ($activeSection === 'overview'): ?>
    loadCustomers(1);
    <?php endif; ?>


    // ═══════════════════════════════════════════
    // ═══ IMPORTAÇÃO — Lógica completa         ═══
    // ═══════════════════════════════════════════

    var importFieldOptions = <?= json_encode($importFields ?? []) ?>;
    var importDropzone = document.getElementById('importDropzone');
    var importFileInput = document.getElementById('importFileInput');
    var importFileInfo = document.getElementById('importFileInfo');
    var btnParseFile = document.getElementById('btnParseFile');
    var btnRemoveFile = document.getElementById('btnRemoveFile');
    var btnDoImport = document.getElementById('btnDoImport');
    var btnBackToStep1 = document.getElementById('btnBackToStep1');
    var btnNewImport = document.getElementById('btnNewImport');
    var btnUndoImport = document.getElementById('btnUndoImport');
    var btnSaveProfile = document.getElementById('btnSaveProfile');
    var btnDeleteProfile = document.getElementById('btnDeleteProfile');
    var btnRefreshHistory = document.getElementById('btnRefreshHistory');
    var importModeSelect = document.getElementById('importMode');
    var mappingProfileSelect = document.getElementById('mappingProfileSelect');
    var importData = null;
    var progressInterval = null;

    if (!importDropzone) return;

    // ── Modo de importação: textos de ajuda ──
    var modeHelps = {
        'create': 'Todos os registros serão criados como novos clientes.',
        'update': 'Apenas clientes existentes (por CPF/CNPJ) serão atualizados. Novos serão ignorados.',
        'create_or_update': 'Clientes existentes serão atualizados, novos serão criados automaticamente.'
    };
    if (importModeSelect) {
        importModeSelect.addEventListener('change', function() {
            var help = document.getElementById('importModeHelp');
            if (help) help.textContent = modeHelps[this.value] || '';
        });
    }

    // ── Perfis de mapeamento ──
    function loadMappingProfiles() {
        fetch('?page=customers&action=getMappingProfiles', { headers: { 'X-CSRF-TOKEN': csrfToken } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success || !mappingProfileSelect) return;
                var current = mappingProfileSelect.value;
                mappingProfileSelect.innerHTML = '<option value="">— Mapeamento automático —</option>';
                (data.profiles || []).forEach(function(p) {
                    var opt = document.createElement('option');
                    opt.value = p.id;
                    opt.textContent = p.name + (p.is_default == 1 ? ' (padrão)' : '');
                    opt.dataset.mapping = p.mapping_json || '{}';
                    mappingProfileSelect.appendChild(opt);
                });
                if (current) mappingProfileSelect.value = current;
            });
    }
    loadMappingProfiles();

    if (mappingProfileSelect) {
        mappingProfileSelect.addEventListener('change', function() {
            if (btnDeleteProfile) btnDeleteProfile.style.display = this.value ? '' : 'none';
            if (this.value && importData) {
                var opt = this.options[this.selectedIndex];
                var savedMapping = JSON.parse(opt.dataset.mapping || '{}');
                applyProfileMapping(savedMapping);
            }
        });
    }

    function applyProfileMapping(savedMapping) {
        document.querySelectorAll('#mappingTableBody .mapping-select').forEach(function(sel) {
            var col = sel.dataset.col;
            if (savedMapping[col]) {
                sel.value = savedMapping[col];
            } else {
                sel.value = '_skip';
            }
        });
        validateMapping();
    }

    if (btnSaveProfile) {
        btnSaveProfile.addEventListener('click', function() {
            var mapping = getMapping();
            if (Object.keys(mapping).length === 0) {
                Swal.fire({ icon: 'warning', title: 'Mapeamento vazio', text: 'Mapeie ao menos uma coluna antes de salvar.' });
                return;
            }
            Swal.fire({
                title: 'Salvar Perfil de Mapeamento',
                html: '<input id="swal-profile-name" class="swal2-input" placeholder="Nome do perfil" style="font-size:.9rem;">' +
                      '<label class="mt-2 small"><input type="checkbox" id="swal-profile-default"> Perfil padrão</label>',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-save me-1"></i>Salvar',
                cancelButtonText: 'Cancelar',
                preConfirm: function() {
                    var name = document.getElementById('swal-profile-name').value.trim();
                    if (!name) { Swal.showValidationMessage('Informe um nome'); return false; }
                    return { name: name, isDefault: document.getElementById('swal-profile-default').checked ? 1 : 0 };
                }
            }).then(function(result) {
                if (!result.isConfirmed) return;
                var formData = new FormData();
                formData.append('profile_name', result.value.name);
                formData.append('mapping', JSON.stringify(mapping));
                formData.append('is_default', result.value.isDefault);
                fetch('?page=customers&action=saveMappingProfile', { method: 'POST', body: formData, headers: { 'X-CSRF-TOKEN': csrfToken } })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.success) {
                            Swal.fire({ icon: 'success', title: data.message, timer: 1500, showConfirmButton: false });
                            loadMappingProfiles();
                        } else {
                            Swal.fire({ icon: 'error', title: 'Erro', text: data.message });
                        }
                    });
            });
        });
    }

    if (btnDeleteProfile) {
        btnDeleteProfile.addEventListener('click', function() {
            var profileId = mappingProfileSelect.value;
            if (!profileId) return;
            Swal.fire({
                title: 'Excluir perfil?', icon: 'warning', showCancelButton: true,
                confirmButtonText: 'Sim, excluir', cancelButtonText: 'Cancelar', confirmButtonColor: '#d33'
            }).then(function(result) {
                if (!result.isConfirmed) return;
                var formData = new FormData();
                formData.append('profile_id', profileId);
                fetch('?page=customers&action=deleteMappingProfile', { method: 'POST', body: formData, headers: { 'X-CSRF-TOKEN': csrfToken } })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.success) {
                            loadMappingProfiles();
                            btnDeleteProfile.style.display = 'none';
                        }
                        Swal.fire({ icon: data.success ? 'success' : 'error', title: data.message, timer: 1500, showConfirmButton: false });
                    });
            });
        });
    }

    // ── Histórico de importações ──
    function loadImportHistory() {
        var container = document.getElementById('importHistoryContainer');
        if (!container) return;
        fetch('?page=customers&action=getImportHistory', { headers: { 'X-CSRF-TOKEN': csrfToken } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success || !data.batches || data.batches.length === 0) {
                    container.innerHTML = '<div class="text-center py-3 text-muted small"><i class="fas fa-inbox me-1"></i>Nenhuma importação realizada.</div>';
                    return;
                }
                var html = '<table class="table table-sm table-hover mb-0" style="font-size:.75rem;">';
                html += '<thead class="table-light"><tr><th>#</th><th>Data</th><th>Modo</th><th>Criados</th><th>Atualizados</th><th>Erros</th><th>Status</th><th></th></tr></thead><tbody>';
                data.batches.forEach(function(b) {
                    var statusBadge = '<span class="badge bg-success">OK</span>';
                    if (b.status === 'undone') statusBadge = '<span class="badge bg-secondary">Desfeita</span>';
                    else if (b.status === 'completed_with_errors') statusBadge = '<span class="badge bg-warning text-dark">Parcial</span>';
                    else if (b.status === 'processing') statusBadge = '<span class="badge bg-info">Em andamento</span>';
                    var undoBtn = (b.status !== 'undone' && b.status !== 'processing')
                        ? '<button class="btn btn-outline-danger btn-sm py-0 px-1 hist-undo-btn" data-batch-id="' + b.id + '" title="Desfazer"><i class="fas fa-undo"></i></button>'
                        : '';
                    var detailBtn = '<button class="btn btn-outline-primary btn-sm py-0 px-1 hist-detail-btn me-1" data-batch-id="' + b.id + '" title="Ver detalhes"><i class="fas fa-eye"></i></button>';
                    html += '<tr><td>' + b.id + '</td><td>' + escHtml(b.created_at || '') + '</td><td>' + escHtml(b.import_mode || 'create') + '</td>';
                    html += '<td class="text-success fw-bold">' + (b.created_count || 0) + '</td>';
                    html += '<td class="text-info fw-bold">' + (b.updated_count || 0) + '</td>';
                    html += '<td class="text-danger fw-bold">' + (b.error_count || 0) + '</td>';
                    html += '<td>' + statusBadge + '</td><td class="text-nowrap">' + detailBtn + undoBtn + '</td></tr>';
                });
                html += '</tbody></table>';
                container.innerHTML = html;
                // Bind undo buttons
                container.querySelectorAll('.hist-undo-btn').forEach(function(btn) {
                    btn.addEventListener('click', function() { doUndoImport(this.dataset.batchId); });
                });
                // Bind detail buttons
                container.querySelectorAll('.hist-detail-btn').forEach(function(btn) {
                    btn.addEventListener('click', function() { showImportDetails(this.dataset.batchId); });
                });
            })
            .catch(function() {
                container.innerHTML = '<div class="text-center py-3 text-muted small">Erro ao carregar histórico.</div>';
            });
    }
    loadImportHistory();

    if (btnRefreshHistory) {
        btnRefreshHistory.addEventListener('click', function() { loadImportHistory(); });
    }

    // ── Upload e Drag & Drop ──
    importDropzone.addEventListener('click', function() { importFileInput.click(); });
    importDropzone.addEventListener('dragover', function(e) { e.preventDefault(); this.classList.add('dragover'); });
    importDropzone.addEventListener('dragleave', function() { this.classList.remove('dragover'); });
    importDropzone.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
        if (e.dataTransfer.files.length > 0) { importFileInput.files = e.dataTransfer.files; handleFileSelected(); }
    });

    importFileInput.addEventListener('change', handleFileSelected);

    function handleFileSelected() {
        var file = importFileInput.files[0];
        if (!file) return;
        var validExts = ['.csv', '.xls', '.xlsx'];
        var ext = file.name.substring(file.name.lastIndexOf('.')).toLowerCase();
        if (!validExts.includes(ext)) {
            Swal.fire({ icon:'error', title:'Formato inválido', text:'Use arquivos CSV, XLS ou XLSX.' });
            return;
        }
        document.getElementById('importFileName').textContent = file.name;
        document.getElementById('importFileSize').textContent = formatFileSize(file.size);
        importFileInfo.style.display = '';
        importDropzone.classList.add('has-file');
        btnParseFile.disabled = false;
    }

    if (btnRemoveFile) {
        btnRemoveFile.addEventListener('click', function() {
            importFileInput.value = '';
            importFileInfo.style.display = 'none';
            importDropzone.classList.remove('has-file');
            btnParseFile.disabled = true;
        });
    }

    function formatFileSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    function goToStep(step) {
        document.querySelectorAll('.import-step').forEach(function(s) { s.classList.remove('active'); });
        var stepEl = document.getElementById('importStep' + step);
        if (stepEl) stepEl.classList.add('active');
        document.querySelectorAll('.import-step-indicator').forEach(function(ind) {
            var badge = ind.querySelector('.badge');
            if (parseInt(ind.dataset.step) <= step) { badge.classList.remove('bg-secondary'); badge.classList.add('bg-primary'); }
            else { badge.classList.remove('bg-primary'); badge.classList.add('bg-secondary'); }
        });
    }

    if (btnParseFile) {
        btnParseFile.addEventListener('click', function() {
            var file = importFileInput.files[0];
            if (!file) return;
            btnParseFile.disabled = true;
            btnParseFile.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Analisando...';
            var formData = new FormData();
            formData.append('import_file', file);
            fetch('?page=customers&action=parseImportFile', { method: 'POST', body: formData, headers: { 'X-CSRF-TOKEN': csrfToken } })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    btnParseFile.disabled = false;
                    btnParseFile.innerHTML = '<i class="fas fa-cog me-1"></i>Analisar Arquivo';
                    if (!data.success) { Swal.fire({ icon:'error', title:'Erro', text: data.message }); return; }
                    importData = data;
                    buildMappingTable(data.columns, data.preview, data.auto_mapping);
                    buildPreviewTable(data.columns, data.preview);
                    document.getElementById('totalRowsBadge').textContent = data.total_rows + ' linha(s)';
                    document.getElementById('importCountLabel').textContent = data.total_rows;
                    goToStep(2);
                    validateMapping();
                    // Aplicar perfil padrão se selecionado
                    if (mappingProfileSelect && mappingProfileSelect.value) {
                        var opt = mappingProfileSelect.options[mappingProfileSelect.selectedIndex];
                        var saved = JSON.parse(opt.dataset.mapping || '{}');
                        applyProfileMapping(saved);
                    }
                })
                .catch(function() {
                    btnParseFile.disabled = false;
                    btnParseFile.innerHTML = '<i class="fas fa-cog me-1"></i>Analisar Arquivo';
                    Swal.fire({ icon:'error', title:'Erro de comunicação' });
                });
        });
    }

    function buildMappingTable(columns, preview, autoMapping) {
        var tbody = document.getElementById('mappingTableBody');
        var html = '';
        columns.forEach(function(col) {
            var samples = [];
            for (var i = 0; i < Math.min(preview.length, 3); i++) {
                var val = preview[i][col];
                if (val && String(val).trim() !== '') samples.push(String(val).trim());
            }
            var sampleHtml = samples.length > 0
                ? samples.map(function(s) { return '<span class="badge bg-light text-dark border me-1" style="font-size:.7rem;">' + escHtml(s.substring(0, 40)) + '</span>'; }).join('')
                : '<span class="text-muted" style="font-size:.7rem;">—</span>';
            var autoVal = autoMapping[col] || '';
            var optionsHtml = '<option value="_skip"' + (autoVal === '' ? ' selected' : '') + '>— Ignorar coluna —</option>';
            for (var field in importFieldOptions) {
                var info = importFieldOptions[field];
                var isReq = info.required ? ' *' : '';
                optionsHtml += '<option value="' + field + '"' + (autoVal === field ? ' selected' : '') + '>' + info.label + isReq + '</option>';
            }
            html += '<tr>' +
                '<td class="text-center"><input type="checkbox" class="form-check-input col-check" data-col="' + escHtml(col) + '" checked></td>' +
                '<td><strong style="font-size:.82rem;"><i class="fas fa-columns me-1 text-muted"></i>' + escHtml(col) + '</strong></td>' +
                '<td>' + sampleHtml + '</td>' +
                '<td><select class="form-select mapping-select" data-col="' + escHtml(col) + '">' + optionsHtml + '</select></td>' +
            '</tr>';
        });
        tbody.innerHTML = html;
        tbody.querySelectorAll('.mapping-select').forEach(function(sel) { sel.addEventListener('change', validateMapping); });
        tbody.querySelectorAll('.col-check').forEach(function(chk) {
            chk.addEventListener('change', function() {
                var row = this.closest('tr');
                var sel = row.querySelector('.mapping-select');
                if (!this.checked) { sel.value = '_skip'; sel.disabled = true; }
                else { sel.disabled = false; }
                validateMapping();
            });
        });
        var checkAll = document.getElementById('checkAllCols');
        if (checkAll) {
            checkAll.addEventListener('change', function() {
                var checked = this.checked;
                tbody.querySelectorAll('.col-check').forEach(function(chk) { chk.checked = checked; chk.dispatchEvent(new Event('change')); });
            });
        }
    }

    function validateMapping() {
        var validationEl = document.getElementById('mappingValidation');
        if (!validationEl) return;
        var mapping = getMapping();
        var mappedFields = Object.values(mapping).filter(function(v) { return v !== '_skip'; });
        var warnings = [];
        var errors = [];
        var mode = importModeSelect ? importModeSelect.value : 'create';
        if (!mappedFields.includes('name')) errors.push('<i class="fas fa-times-circle text-danger me-1"></i><strong>Nome / Razão Social</strong> é obrigatório.');
        if ((mode === 'update' || mode === 'create_or_update') && !mappedFields.includes('document')) {
            errors.push('<i class="fas fa-times-circle text-danger me-1"></i><strong>CPF/CNPJ</strong> é obrigatório para modo de atualização.');
        }
        var fieldCount = {};
        mappedFields.forEach(function(f) { fieldCount[f] = (fieldCount[f] || 0) + 1; });
        for (var f in fieldCount) {
            if (fieldCount[f] > 1) {
                var label = importFieldOptions[f] ? importFieldOptions[f].label : f;
                warnings.push('<i class="fas fa-exclamation-triangle text-warning me-1"></i><strong>' + label + '</strong> mapeado mais de uma vez.');
            }
        }
        if (errors.length > 0 || warnings.length > 0) {
            var html = '';
            errors.forEach(function(e) { html += '<div class="alert alert-danger py-1 mb-1 small">' + e + '</div>'; });
            warnings.forEach(function(w) { html += '<div class="alert alert-warning py-1 mb-1 small">' + w + '</div>'; });
            validationEl.innerHTML = html;
            validationEl.style.display = '';
        } else {
            validationEl.innerHTML = '<div class="alert alert-success py-1 mb-1 small"><i class="fas fa-check-circle text-success me-1"></i>Mapeamento válido!</div>';
            validationEl.style.display = '';
        }
        if (btnDoImport) btnDoImport.disabled = errors.length > 0;
    }

    // Re-validate on mode change
    if (importModeSelect) {
        importModeSelect.addEventListener('change', function() {
            if (importData) validateMapping();
        });
    }

    function getMapping() {
        var mapping = {};
        document.querySelectorAll('#mappingTableBody .mapping-select').forEach(function(sel) {
            var col = sel.dataset.col;
            var val = sel.value;
            if (val && val !== '_skip') mapping[col] = val;
        });
        return mapping;
    }

    function buildPreviewTable(columns, preview) {
        var thead = document.getElementById('previewTableHead');
        var tbody = document.getElementById('previewTableBody');
        var headHtml = '<tr><th class="text-muted" style="width:30px;">#</th>';
        columns.forEach(function(col) { headHtml += '<th>' + escHtml(col) + '</th>'; });
        headHtml += '</tr>';
        thead.innerHTML = headHtml;
        var bodyHtml = '';
        preview.forEach(function(row, idx) {
            bodyHtml += '<tr><td class="text-muted">' + (idx + 1) + '</td>';
            columns.forEach(function(col) { var val = row[col] || ''; bodyHtml += '<td title="' + escHtml(String(val)) + '">' + escHtml(String(val).substring(0, 50)) + '</td>'; });
            bodyHtml += '</tr>';
        });
        tbody.innerHTML = bodyHtml;
    }

    if (btnBackToStep1) btnBackToStep1.addEventListener('click', function() { goToStep(1); });

    // ── Progresso em tempo real (Rec 1) ──
    function startProgressPolling() {
        var progressCard = document.getElementById('importProgressCard');
        if (progressCard) progressCard.style.display = '';
        progressInterval = setInterval(function() {
            fetch('?page=customers&action=getImportProgress', { headers: { 'X-CSRF-TOKEN': csrfToken } })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (!data.success || !data.progress) return;
                    var p = data.progress;
                    var pct = p.total > 0 ? Math.round((p.processed / p.total) * 100) : 0;
                    var bar = document.getElementById('importProgressBar');
                    var pctEl = document.getElementById('progressPercent');
                    var detailEl = document.getElementById('progressDetail');
                    var statsEl = document.getElementById('progressStats');
                    if (bar) { bar.style.width = pct + '%'; bar.setAttribute('aria-valuenow', pct); }
                    if (pctEl) pctEl.textContent = pct + '%';
                    if (detailEl) detailEl.textContent = p.processed + ' de ' + p.total + ' processados';
                    if (statsEl) statsEl.textContent = 'Criados: ' + (p.imported || 0) + ' | Atualizados: ' + (p.updated || 0) + ' | Erros: ' + (p.errors || 0);
                    if (p.status === 'completed' || pct >= 100) {
                        stopProgressPolling();
                    }
                });
        }, 800);
    }

    function stopProgressPolling() {
        if (progressInterval) { clearInterval(progressInterval); progressInterval = null; }
    }

    // ── Executar importação ──
    if (btnDoImport) {
        btnDoImport.addEventListener('click', function() {
            var mapping = getMapping();
            var mode = importModeSelect ? importModeSelect.value : 'create';
            if (!Object.values(mapping).includes('name')) {
                Swal.fire({ icon:'error', title:'Mapeamento incompleto', text:'O campo Nome é obrigatório.' });
                return;
            }
            var modeLabels = { 'create': 'criados', 'update': 'atualizados', 'create_or_update': 'criados/atualizados' };
            Swal.fire({
                title: 'Confirmar importação?',
                html: '<strong>' + (importData ? importData.total_rows : '?') + '</strong> cliente(s) serão ' + (modeLabels[mode] || 'processados') + '.<br><small class="text-muted">Modo: <strong>' + mode + '</strong></small>',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-upload me-1"></i>Importar',
                cancelButtonText: 'Cancelar'
            }).then(function(result) {
                if (!result.isConfirmed) return;
                btnDoImport.disabled = true;
                btnDoImport.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Importando...';
                // Iniciar polling de progresso
                startProgressPolling();
                var formData = new FormData();
                formData.append('mapping', JSON.stringify(mapping));
                formData.append('import_mode', mode);
                fetch('?page=customers&action=importCustomersMapped', { method: 'POST', body: formData, headers: { 'X-CSRF-TOKEN': csrfToken } })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        stopProgressPolling();
                        btnDoImport.disabled = false;
                        btnDoImport.innerHTML = '<i class="fas fa-upload me-1"></i>Importar';
                        var progressCard = document.getElementById('importProgressCard');
                        if (progressCard) progressCard.style.display = 'none';
                        showImportResult(data);
                        goToStep(3);
                        loadImportHistory();
                    })
                    .catch(function() {
                        stopProgressPolling();
                        btnDoImport.disabled = false;
                        btnDoImport.innerHTML = '<i class="fas fa-upload me-1"></i>Importar';
                        var progressCard = document.getElementById('importProgressCard');
                        if (progressCard) progressCard.style.display = 'none';
                        Swal.fire({ icon:'error', title:'Erro de comunicação' });
                    });
            });
        });
    }

    function showImportResult(data) {
        var container = document.getElementById('importResultContent');
        if (!container) return;
        var html = '';
        if (data.success) {
            var totalProcessed = (data.imported || 0) + (data.updated || 0);
            if (totalProcessed > 0) {
                html += '<div class="text-center mb-4"><div class="icon-circle icon-circle-80 icon-circle-green d-inline-flex mx-auto mb-3"><i class="fas fa-check-circle fa-2x text-success"></i></div>';
                html += '<h4 class="text-success">Importação Concluída!</h4><p class="text-muted">';
                if (data.imported > 0) html += '<strong>' + data.imported + '</strong> cliente(s) criado(s)';
                if (data.imported > 0 && data.updated > 0) html += ', ';
                if (data.updated > 0) html += '<strong>' + data.updated + '</strong> atualizado(s)';
                if (data.skipped > 0) html += ', <strong>' + data.skipped + '</strong> ignorado(s)';
                html += '.</p></div>';
            } else {
                html += '<div class="text-center mb-4"><div class="icon-circle icon-circle-80 icon-circle-warning d-inline-flex mx-auto mb-3"><i class="fas fa-exclamation-triangle fa-2x text-warning"></i></div>';
                html += '<h4 class="text-warning">Nenhum cliente processado</h4><p class="text-muted">Verifique os erros abaixo.</p></div>';
            }
            // Warnings
            if (data.warnings && data.warnings.length > 0) {
                html += '<div class="alert alert-info py-2 d-flex align-items-center mb-2"><i class="fas fa-info-circle me-2"></i><strong>' + data.warnings.length + '</strong>&nbsp;aviso(s) de validação:</div>';
                html += '<div class="list-group mb-3" style="max-height:200px;overflow-y:auto;">';
                data.warnings.forEach(function(w) {
                    html += '<div class="list-group-item list-group-item-warning py-2 small"><i class="fas fa-exclamation-circle me-1"></i><strong>Linha ' + w.line + ':</strong> ' + escHtml(w.message) + '</div>';
                });
                html += '</div>';
            }
            // Errors
            if (data.errors && data.errors.length > 0) {
                html += '<div class="alert alert-danger py-2 d-flex align-items-center mb-2"><i class="fas fa-exclamation-triangle me-2"></i><strong>' + data.errors.length + '</strong>&nbsp;linha(s) com erro:</div>';
                html += '<div class="list-group" style="max-height:250px;overflow-y:auto;">';
                data.errors.forEach(function(err) {
                    html += '<div class="list-group-item list-group-item-danger py-2 small"><i class="fas fa-times-circle me-1"></i><strong>Linha ' + err.line + ':</strong> ' + escHtml(err.message) + '</div>';
                });
                html += '</div>';
            }
            var countEl = document.querySelector('.cst-nav-item[data-section="overview"] .cst-nav-count');
            if (countEl && data.imported > 0) { var current = parseInt(countEl.textContent) || 0; countEl.textContent = current + data.imported; }
            // Mostrar botão de desfazer
            if (btnUndoImport && data.batch_id && data.imported > 0) {
                btnUndoImport.style.display = '';
                btnUndoImport.dataset.batchId = data.batch_id;
            }
        } else {
            html += '<div class="text-center"><div class="icon-circle icon-circle-80 icon-circle-danger d-inline-flex mx-auto mb-3"><i class="fas fa-times-circle fa-2x text-danger"></i></div>';
            html += '<h4 class="text-danger">Erro na Importação</h4><p class="text-muted">' + escHtml(data.message || 'Erro desconhecido.') + '</p></div>';
        }
        container.innerHTML = html;
    }

    // ── Desfazer importação (Rec 3) ──
    function doUndoImport(batchId) {
        Swal.fire({
            title: 'Desfazer importação?',
            html: 'Todos os clientes <strong>criados</strong> neste lote serão removidos.<br><small class="text-danger">Esta ação não pode ser revertida facilmente.</small>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-undo me-1"></i>Desfazer',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#d33'
        }).then(function(result) {
            if (!result.isConfirmed) return;
            var formData = new FormData();
            formData.append('batch_id', batchId);
            fetch('?page=customers&action=undoImport', { method: 'POST', body: formData, headers: { 'X-CSRF-TOKEN': csrfToken } })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success) {
                        Swal.fire({ icon: 'success', title: 'Importação desfeita', text: data.message, timer: 2500 });
                        loadImportHistory();
                        if (btnUndoImport) btnUndoImport.style.display = 'none';
                        // Atualizar contador
                        var countEl = document.querySelector('.cst-nav-item[data-section="overview"] .cst-nav-count');
                        if (countEl && data.deleted) { var current = parseInt(countEl.textContent) || 0; countEl.textContent = Math.max(0, current - data.deleted); }
                    } else {
                        Swal.fire({ icon: 'error', title: 'Erro', text: data.message });
                    }
                });
        });
    }

    if (btnUndoImport) {
        btnUndoImport.addEventListener('click', function() {
            doUndoImport(this.dataset.batchId);
        });
    }

    if (btnNewImport) {
        btnNewImport.addEventListener('click', function() {
            importFileInput.value = '';
            importFileInfo.style.display = 'none';
            importDropzone.classList.remove('has-file');
            btnParseFile.disabled = true;
            importData = null;
            if (btnUndoImport) btnUndoImport.style.display = 'none';
            goToStep(1);
        });
    }

    // ── Detalhes da importação ──
    function showImportDetails(batchId) {
        Swal.fire({ title: 'Carregando...', allowOutsideClick: false, didOpen: function() { Swal.showLoading(); } });

        fetch('?page=customers&action=getImportDetails&batch_id=' + encodeURIComponent(batchId), { headers: { 'X-CSRF-TOKEN': csrfToken } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                Swal.close();
                if (!data.success) {
                    Swal.fire({ icon: 'error', title: 'Erro', text: data.message || 'Não foi possível carregar os detalhes.' });
                    return;
                }

                var b = data.batch;
                var modeLabel = { create: 'Criar', update: 'Atualizar', create_or_update: 'Criar ou Atualizar' };

                var html = '<div style="font-size:.82rem; text-align:left;">';

                // Header info
                html += '<div class="row mb-3">';
                html += '<div class="col-6"><strong>Arquivo:</strong> ' + escHtml(b.file_name || '—') + '</div>';
                html += '<div class="col-6"><strong>Data:</strong> ' + escHtml(b.created_at || '') + '</div>';
                html += '<div class="col-6"><strong>Modo:</strong> ' + escHtml(modeLabel[b.import_mode] || b.import_mode) + '</div>';
                html += '<div class="col-6"><strong>Total de linhas:</strong> ' + (b.total_rows || 0) + '</div>';
                html += '</div>';

                // Summary badges
                html += '<div class="d-flex gap-2 flex-wrap mb-3">';
                html += '<span class="badge bg-success"><i class="fas fa-plus me-1"></i>Criados: ' + (b.imported_count || 0) + '</span>';
                html += '<span class="badge bg-info"><i class="fas fa-sync-alt me-1"></i>Atualizados: ' + (b.updated_count || 0) + '</span>';
                html += '<span class="badge bg-secondary"><i class="fas fa-forward me-1"></i>Ignorados: ' + (b.skipped_count || 0) + '</span>';
                html += '<span class="badge bg-danger"><i class="fas fa-times me-1"></i>Erros: ' + (b.error_count || 0) + '</span>';
                html += '</div>';

                // Tabs
                html += '<ul class="nav nav-tabs nav-fill" role="tablist" style="font-size:.78rem;">';
                html += '<li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tabCreated"><i class="fas fa-user-plus me-1"></i>Criados (' + data.created.length + ')</a></li>';
                html += '<li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabUpdated"><i class="fas fa-user-edit me-1"></i>Atualizados (' + data.updated.length + ')</a></li>';
                html += '<li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabErrors"><i class="fas fa-exclamation-triangle me-1"></i>Erros (' + (data.errors ? data.errors.length : 0) + ')</a></li>';
                html += '<li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabWarnings"><i class="fas fa-info-circle me-1"></i>Avisos (' + (data.warnings ? data.warnings.length : 0) + ')</a></li>';
                html += '</ul>';

                html += '<div class="tab-content border border-top-0 rounded-bottom p-2" style="max-height:350px; overflow-y:auto;">';

                // Created tab
                html += '<div class="tab-pane fade show active" id="tabCreated">';
                if (data.created.length === 0) {
                    html += '<p class="text-muted text-center my-3">Nenhum cliente criado neste lote.</p>';
                } else {
                    html += '<table class="table table-sm table-striped mb-0" style="font-size:.75rem;"><thead><tr><th>Linha</th><th>ID</th><th>Nome</th><th>Documento</th><th>E-mail</th></tr></thead><tbody>';
                    data.created.forEach(function(c) {
                        html += '<tr><td>' + (c.line || '—') + '</td><td>' + c.id + '</td><td>' + escHtml(c.name) + '</td><td>' + escHtml(c.document || '') + '</td><td>' + escHtml(c.email || '') + '</td></tr>';
                    });
                    html += '</tbody></table>';
                }
                html += '</div>';

                // Updated tab
                html += '<div class="tab-pane fade" id="tabUpdated">';
                if (data.updated.length === 0) {
                    html += '<p class="text-muted text-center my-3">Nenhum cliente atualizado neste lote.</p>';
                } else {
                    html += '<table class="table table-sm table-striped mb-0" style="font-size:.75rem;"><thead><tr><th>Linha</th><th>ID</th><th>Nome</th><th>Documento</th><th>E-mail</th></tr></thead><tbody>';
                    data.updated.forEach(function(c) {
                        html += '<tr><td>' + (c.line || '—') + '</td><td>' + c.id + '</td><td>' + escHtml(c.name) + '</td><td>' + escHtml(c.document || '') + '</td><td>' + escHtml(c.email || '') + '</td></tr>';
                    });
                    html += '</tbody></table>';
                }
                html += '</div>';

                // Errors tab
                html += '<div class="tab-pane fade" id="tabErrors">';
                if (!data.errors || data.errors.length === 0) {
                    html += '<p class="text-muted text-center my-3">Nenhum erro registrado.</p>';
                } else {
                    html += '<table class="table table-sm table-striped mb-0" style="font-size:.75rem;"><thead><tr><th>Linha</th><th>Erro</th></tr></thead><tbody>';
                    data.errors.forEach(function(e) {
                        var line = (typeof e === 'object') ? (e.line || e.row || '—') : '—';
                        var msg  = (typeof e === 'object') ? (e.message || e.error || JSON.stringify(e)) : escHtml(e);
                        html += '<tr><td>' + escHtml(String(line)) + '</td><td class="text-danger">' + escHtml(msg) + '</td></tr>';
                    });
                    html += '</tbody></table>';
                }
                html += '</div>';

                // Warnings tab
                html += '<div class="tab-pane fade" id="tabWarnings">';
                if (!data.warnings || data.warnings.length === 0) {
                    html += '<p class="text-muted text-center my-3">Nenhum aviso registrado.</p>';
                } else {
                    html += '<table class="table table-sm table-striped mb-0" style="font-size:.75rem;"><thead><tr><th>Linha</th><th>Aviso</th></tr></thead><tbody>';
                    data.warnings.forEach(function(w) {
                        var line = (typeof w === 'object') ? (w.line || w.row || '—') : '—';
                        var msg  = (typeof w === 'object') ? (w.message || w.warning || JSON.stringify(w)) : escHtml(w);
                        html += '<tr><td>' + escHtml(String(line)) + '</td><td class="text-warning">' + escHtml(msg) + '</td></tr>';
                    });
                    html += '</tbody></table>';
                }
                html += '</div>';

                html += '</div></div>';

                Swal.fire({
                    title: '<i class="fas fa-file-import me-2"></i>Importação #' + b.id,
                    html: html,
                    width: '750px',
                    showCloseButton: true,
                    showConfirmButton: false,
                    customClass: { popup: 'text-start' }
                });
            })
            .catch(function() {
                Swal.close();
                Swal.fire({ icon: 'error', title: 'Erro', text: 'Falha ao carregar detalhes da importação.' });
            });
    }

});
</script>
