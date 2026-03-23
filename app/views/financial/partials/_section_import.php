<?php
/**
 * Partial: Seção Importação OFX/CSV/Excel.
 *
 * Variáveis esperadas:
 *   $activeSection — seção ativa
 */
?>
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
