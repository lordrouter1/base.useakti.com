<?php
/**
 * View: Migrations - Dashboard de Migrações
 */
$pageTitle = 'Migrações de Banco';
$pageSubtitle = 'Comparação de schemas e aplicação de SQL em bancos tenant';
$topbarActions = '<a href="?page=master_migrations&action=history" class="btn btn-akti-outline me-2"><i class="fas fa-history me-2"></i>Histórico</a><a href="?page=master_migrations&action=users" class="btn btn-akti-outline"><i class="fas fa-users me-2"></i>Usuários Tenant</a>';

$pageScripts = <<<'SCRIPTS'
<script>
$(document).ready(function() {

    // ── akti_master exclusive selection ───────────────────────
    var $masterCheckbox = $('#db_akti_master');
    var $tenantCheckboxes = $('input[name="selected_dbs[]"]').not('#db_akti_master');
    var $selectAllDbs = $('#selectAllDbs');
    var $selectDivergent = $('#selectDivergent');
    var $applyInitBase = $('#applyToInitBase');

    $masterCheckbox.on('change', function() {
        if ($(this).is(':checked')) {
            // Deselect and disable all tenant checkboxes + init_base
            $tenantCheckboxes.prop('checked', false).prop('disabled', true);
            $selectAllDbs.prop('checked', false).prop('disabled', true);
            $selectDivergent.prop('disabled', true);
            $applyInitBase.prop('checked', false).prop('disabled', true);
            $tenantCheckboxes.closest('.border-bottom').css('opacity', '0.5');
        } else {
            // Re-enable all tenant checkboxes
            $tenantCheckboxes.prop('disabled', false);
            $selectAllDbs.prop('disabled', false);
            $selectDivergent.prop('disabled', false);
            $applyInitBase.prop('disabled', false);
            $tenantCheckboxes.closest('.border-bottom').css('opacity', '1');
        }
        updateSelectedCount();
    });

    function updateSelectedCount() {
        var count = $('input[name="selected_dbs[]"]:checked').length;
        $('#selectedCount').text(count);
    }

    // ── Ver detalhes de comparação de um banco ────────────────
    $(document).on('click', '.btn-view-diff', function() {
        var db = $(this).data('db');
        var card = $('#diff-detail-' + db);
        
        if (card.is(':visible')) {
            card.slideUp(200);
            return;
        }

        card.html('<div class="text-center py-3"><i class="fas fa-spinner fa-spin me-2"></i>Comparando...</div>').slideDown(200);

        $.getJSON('?page=master_migrations&action=compareDetail&db=' + encodeURIComponent(db), function(data) {
            if (!data.success) {
                card.html('<div class="alert alert-danger mb-0"><i class="fas fa-times-circle me-2"></i>' + data.message + '</div>');
                return;
            }
            var diff = data.diff;
            var html = '';

            if (diff.missing_tables.length === 0 && diff.missing_columns.length === 0 && diff.type_mismatches.length === 0) {
                html = '<div class="alert alert-success mb-0"><i class="fas fa-check-circle me-2"></i>Schema sincronizado com a base de referência.</div>';
            } else {
                if (diff.missing_tables.length > 0) {
                    html += '<div class="mb-2"><strong class="text-danger"><i class="fas fa-table me-1"></i>Tabelas faltando (' + diff.missing_tables.length + '):</strong><br>';
                    diff.missing_tables.forEach(function(t) { html += '<code class="me-1 d-inline-block mb-1" style="background:#fee;padding:2px 8px;border-radius:4px;">' + t + '</code>'; });
                    html += '</div>';
                }
                if (diff.missing_columns.length > 0) {
                    html += '<div class="mb-2"><strong class="text-warning"><i class="fas fa-columns me-1"></i>Colunas faltando (' + diff.missing_columns.length + '):</strong><ul class="mb-0 ps-3" style="font-size:13px;">';
                    diff.missing_columns.forEach(function(c) { html += '<li><code>' + c.table + '.' + c.column + '</code> <span class="text-muted">(' + c.info.type + ')</span></li>'; });
                    html += '</ul></div>';
                }
                if (diff.type_mismatches.length > 0) {
                    html += '<div class="mb-2"><strong class="text-info"><i class="fas fa-exchange-alt me-1"></i>Tipos divergentes (' + diff.type_mismatches.length + '):</strong><ul class="mb-0 ps-3" style="font-size:13px;">';
                    diff.type_mismatches.forEach(function(c) { html += '<li><code>' + c.table + '.' + c.column + '</code>: esperado <code>' + c.expected + '</code>, atual <code>' + c.actual + '</code></li>'; });
                    html += '</ul></div>';
                }
                if (diff.extra_tables.length > 0) {
                    html += '<div class="mb-2"><strong class="text-secondary"><i class="fas fa-plus-circle me-1"></i>Tabelas extras no tenant (' + diff.extra_tables.length + '):</strong><br>';
                    diff.extra_tables.forEach(function(t) { html += '<code class="me-1 d-inline-block mb-1" style="background:#eef;padding:2px 8px;border-radius:4px;">' + t + '</code>'; });
                    html += '</div>';
                }
            }

            card.html(html);
        }).fail(function() {
            card.html('<div class="alert alert-danger mb-0"><i class="fas fa-times-circle me-2"></i>Erro de conexão</div>');
        });
    });

    // ── Select/deselect all databases ─────────────────────────
    $selectAllDbs.on('change', function() {
        if ($masterCheckbox.is(':checked')) return;
        $tenantCheckboxes.prop('checked', $(this).is(':checked'));
        updateSelectedCount();
    });

    // ── Select only divergent databases ───────────────────────
    $selectDivergent.on('click', function() {
        if ($masterCheckbox.is(':checked')) return;
        $tenantCheckboxes.prop('checked', false);
        $('input[name="selected_dbs[]"][data-status="divergent"], input[name="selected_dbs[]"][data-status="error"]').not('#db_akti_master').prop('checked', true);
        $selectAllDbs.prop('checked', false);
        updateSelectedCount();
    });

    // ── Upload SQL file ───────────────────────────────────────
    $('#sqlFileInput').on('change', function() {
        var file = this.files[0];
        if (!file) return;
        
        if (!file.name.endsWith('.sql')) {
            Swal.fire({icon:'error', title:'Arquivo inválido', text:'Selecione um arquivo .sql'});
            return;
        }

        var reader = new FileReader();
        reader.onload = function(e) {
            $('#sqlContent').val(e.target.result);
            $('#sqlFileName').text(file.name);
            $('#migrationName').val(file.name.replace('.sql', ''));
        };
        reader.readAsText(file);
    });

    // ── Preview de quantos bancos selecionados ────────────────
    $(document).on('change', 'input[name="selected_dbs[]"]', function() {
        updateSelectedCount();
    });

    // ── Preview SQL file — abre modal SweetAlert2 ─────────────
    $(document).on('click', '.btn-preview-sql', function() {
        var fileName = $(this).data('file');
        var content = $(this).data('content');

        Swal.fire({
            title: '<i class="fas fa-file-code me-2"></i>' + fileName,
            html: '<pre style="text-align:left; background:#1e1e2e; color:#cdd6f4; padding:16px; border-radius:8px; font-size:12px; max-height:500px; overflow:auto; white-space:pre-wrap; word-break:break-all; margin:0;">' + $('<div>').text(content).html() + '</pre>',
            width: '800px',
            confirmButtonColor: '#1b3d6e',
            confirmButtonText: 'Fechar',
            showCancelButton: true,
            cancelButtonText: '<i class="fas fa-download me-1"></i>Carregar no Editor',
            cancelButtonColor: '#198754',
            reverseButtons: true
        }).then(function(result) {
            if (result.dismiss === Swal.DismissReason.cancel) {
                loadSqlToEditor(fileName, content);
            }
        });
    });

    // ── Load SQL file into editor ─────────────────────────────
    function loadSqlToEditor(fileName, content) {
        $('#sqlContent').val(content);
        $('#migrationName').val(fileName.replace('.sql', ''));
        $('#sqlFileName').text(fileName);
        // Set hidden field for file reference
        if ($('#hiddenSqlFile').length === 0) {
            $('#migrationForm').append('<input type="hidden" name="sql_file" id="hiddenSqlFile" value="' + fileName + '">');
        } else {
            $('#hiddenSqlFile').val(fileName);
        }
        Swal.fire({
            icon: 'success',
            title: 'Arquivo carregado',
            text: 'O conteúdo de "' + fileName + '" foi carregado no editor SQL.',
            timer: 2000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
        $('html, body').animate({scrollTop: $('#sqlContent').offset().top - 100}, 400);
    }

    $(document).on('click', '.btn-load-sql', function() {
        loadSqlToEditor($(this).data('file'), $(this).data('content'));
    });

    // ── Apply single SQL file ─────────────────────────────────
    $(document).on('click', '.btn-apply-single', function() {
        var fileName = $(this).data('file');
        var btn = $(this);

        // Gather selected databases
        var selectedDbs = [];
        $('input[name="selected_dbs[]"]:checked').not('#db_akti_master').each(function() {
            selectedDbs.push($(this).val());
        });
        var applyToMaster = $masterCheckbox.is(':checked');
        var applyToInitBase = $applyInitBase.is(':checked') && !$applyInitBase.is(':disabled');

        if (selectedDbs.length === 0 && !applyToMaster) {
            Swal.fire({icon:'warning', title:'Nenhum banco', text:'Selecione pelo menos um banco de dados antes de aplicar.'});
            return;
        }

        var targetText = applyToMaster ? '<strong>akti_master</strong>' : '<strong>' + selectedDbs.length + ' banco(s) tenant</strong>';
        if (applyToInitBase && !applyToMaster) {
            targetText += ' + <strong>init_base</strong>';
        }

        Swal.fire({
            icon: 'question',
            title: 'Aplicar arquivo SQL?',
            html: 'Arquivo: <code>' + $('<div>').text(fileName).html() + '</code><br><br>Destino: ' + targetText + '<br><br><small class="text-muted">Em caso de erro, será feito rollback automático.</small>',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-play me-1"></i>Aplicar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true
        }).then(function(result) {
            if (!result.isConfirmed) return;

            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
            Swal.fire({title:'Aplicando...', html:'<i class="fas fa-spinner fa-spin fa-2x"></i><br><br>Executando <strong>' + $('<div>').text(fileName).html() + '</strong>...', showConfirmButton:false, allowOutsideClick:false});

            var postData = {sql_file: fileName};
            if (applyToMaster) postData.apply_to_master = '1';
            if (applyToInitBase) postData.apply_to_init_base = '1';
            selectedDbs.forEach(function(db, i) {
                postData['selected_dbs[' + i + ']'] = db;
            });

            $.post('?page=master_migrations&action=applySingleFile', postData, function(data) {
                btn.prop('disabled', false).html('<i class="fas fa-play-circle"></i>');

                if (!data.success) {
                    Swal.fire({icon:'error', title:'Erro', text: data.message});
                    return;
                }

                var html = '<div style="text-align:left; max-height:350px; overflow-y:auto; font-size:13px;">';
                
                // init_base result
                if (data.init_base) {
                    var ibIcon = data.init_base.failed > 0 ? '❌' : '✅';
                    html += '<div class="mb-1"><strong>' + ibIcon + ' init_base:</strong> OK=' + data.init_base.ok + ', Falhas=' + data.init_base.failed + '</div>';
                }

                // Database results
                $.each(data.results, function(dbName, r) {
                    var icon = r.status === 'success' ? '✅' : (r.status === 'skipped' ? '⏭️' : '❌');
                    html += '<div class="mb-1"><strong>' + icon + ' ' + dbName + ':</strong> ' + r.message + '</div>';
                    if (r.result && r.result.errors && r.result.errors.length > 0) {
                        html += '<div class="ms-3 text-danger" style="font-size:11px;">';
                        r.result.errors.forEach(function(err) {
                            html += '<div><code>#' + err.index + '</code>: ' + $('<div>').text(err.error).html() + '</div>';
                        });
                        html += '</div>';
                    }
                });

                html += '</div>';
                if (data.moved) {
                    html += '<div class="mt-2 alert alert-success mb-0 py-1" style="font-size:12px;"><i class="fas fa-check me-1"></i>Arquivo movido para <code>sql/prontos/</code></div>';
                }

                var allOk = data.ok === data.total;
                Swal.fire({
                    icon: allOk ? 'success' : 'warning',
                    title: allOk ? 'Migração concluída!' : 'Migração com erros',
                    html: html,
                    width: '650px',
                    confirmButtonColor: '#1b3d6e',
                    confirmButtonText: allOk ? '<i class="fas fa-check me-1"></i>OK' : 'Fechar'
                }).then(function() {
                    if (data.moved) location.reload();
                });

            }, 'json').fail(function(xhr) {
                btn.prop('disabled', false).html('<i class="fas fa-play-circle"></i>');
                var errMsg = 'Erro de conexão';
                try { errMsg = JSON.parse(xhr.responseText).message || errMsg; } catch(e) {}
                Swal.fire({icon:'error', title:'Erro', text: errMsg});
            });
        });
    });

    // ── Apply all pending SQL files ───────────────────────────
    $('#btnApplyAllFiles').on('click', function() {
        var fileCount = $(this).data('count');
        var checkedDbs = $('input[name="selected_dbs[]"]:checked').length;

        if (checkedDbs === 0) {
            Swal.fire({icon:'warning', title:'Nenhum banco', text:'Selecione pelo menos um banco de dados antes de executar.'});
            return;
        }

        Swal.fire({
            icon: 'warning',
            title: 'Executar todos os pendentes?',
            html: 'Serão executados <strong>' + fileCount + ' arquivo(s) SQL</strong> em <strong>' + checkedDbs + ' banco(s)</strong> sequencialmente.<br><br><small class="text-muted">Arquivos com sucesso serão movidos para sql/prontos/.<br>Em caso de erro, será feito rollback automático por banco.</small>',
            showCancelButton: true,
            confirmButtonColor: '#f59e0b',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-play-circle me-1"></i>Executar Todos',
            cancelButtonText: 'Cancelar',
            reverseButtons: true
        }).then(function(result) {
            if (result.isConfirmed) {
                var form = $('<form method="POST" action="?page=master_migrations&action=applyAllFiles"></form>');
                form.append($('#migrationForm').find('input[name="_csrf_token"]').clone());
                form.append('<input type="hidden" name="apply_to_init_base" value="1">');
                $('input[name="selected_dbs[]"]:checked').each(function() {
                    form.append('<input type="hidden" name="selected_dbs[]" value="' + $(this).val() + '">');
                });
                $('body').append(form);
                Swal.fire({title:'Aplicando migrações...', html:'<i class="fas fa-spinner fa-spin fa-2x"></i><br><br>Processando ' + fileCount + ' arquivo(s)...', showConfirmButton:false, allowOutsideClick:false});
                form.submit();
            }
        });
    });

    // ── Confirmação antes de aplicar migração manual ──────────
    $('#migrationForm').on('submit', function(e) {
        var count = $('input[name="selected_dbs[]"]:checked').length;
        var sql = $('#sqlContent').val().trim();

        if (!sql) {
            e.preventDefault();
            Swal.fire({icon:'warning', title:'SQL vazio', text:'Cole ou carregue um arquivo SQL antes de aplicar.'});
            return;
        }
        if (count === 0) {
            e.preventDefault();
            Swal.fire({icon:'warning', title:'Nenhum banco', text:'Selecione pelo menos um banco de dados.'});
            return;
        }

        e.preventDefault();
        var form = this;

        Swal.fire({
            icon: 'warning',
            title: 'Confirmar migração',
            html: 'Você está prestes a executar SQL em <strong>' + count + ' banco(s)</strong>.<br><br><small class="text-muted">Em caso de erro, será feito rollback automático.<br>Esta ação não pode ser desfeita após sucesso.</small>',
            showCancelButton: true,
            confirmButtonColor: '#1b3d6e',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-play me-1"></i>Executar migração',
            cancelButtonText: 'Cancelar',
            reverseButtons: true
        }).then(function(result) {
            if (result.isConfirmed) {
                // Mostrar loading
                Swal.fire({title:'Aplicando migração...', html:'<i class="fas fa-spinner fa-spin fa-2x"></i><br><br>Processando ' + count + ' banco(s)...', showConfirmButton:false, allowOutsideClick:false});
                form.submit();
            }
        });
    });
});
</script>
SCRIPTS;

?>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="stat-card" style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
            <div class="stat-icon"><i class="fas fa-database"></i></div>
            <div class="stat-value"><?= count($tenantDbs) ?></div>
            <div class="stat-label">Bancos Tenant</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card" style="background: linear-gradient(135deg, #10b981, #059669);">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-value"><?= count(array_filter($comparisons, fn($c) => $c['status'] === 'ok')) ?></div>
            <div class="stat-label">Sincronizados</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
            <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="stat-value"><?= count(array_filter($comparisons, fn($c) => $c['status'] === 'divergent')) ?></div>
            <div class="stat-label">Divergentes</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card bg-primary-gradient">
            <div class="stat-icon"><i class="fas fa-layer-group"></i></div>
            <div class="stat-value"><?= $baseTables ?></div>
            <div class="stat-label">Tabelas (<?= htmlspecialchars($initBase) ?>)</div>
        </div>
    </div>
</div>

<?php if (!empty($pendingSqlFiles)): ?>
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="alert alert-warning d-flex align-items-center mb-0" role="alert">
            <i class="fas fa-file-code fa-2x me-3"></i>
            <div>
                <strong><?= count($pendingSqlFiles) ?> arquivo(s) SQL pendente(s)</strong> na pasta <code>/sql/</code>.
                <br><small>Selecione os bancos alvo abaixo e execute individualmente ou em batch.</small>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row g-4">
    <!-- Coluna esquerda: Status dos Bancos -->
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-server" style="color: var(--akti-primary);"></i>
                    <strong>Status dos Bancos</strong>
                </div>
                <span class="badge bg-secondary"><?= count($comparisons) ?> bancos</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($comparisons)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-database fa-3x mb-3 opacity-25"></i>
                        <p>Nenhum banco tenant encontrado.</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush" style="max-height: 600px; overflow-y:auto;">
                        <?php foreach ($comparisons as $dbName => $comp): ?>
                            <?php
                            $clientInfo = $dbClientMap[$dbName] ?? null;
                            $clientName = $clientInfo ? $clientInfo['client_name'] : '';
                            $isActive = $clientInfo ? $clientInfo['is_active'] : null;
                            
                            if ($comp['status'] === 'ok') {
                                $statusBadge = '<span class="badge bg-success"><i class="fas fa-check me-1"></i>OK</span>';
                            } elseif ($comp['status'] === 'divergent') {
                                $statusBadge = '<span class="badge bg-warning text-dark"><i class="fas fa-exclamation-triangle me-1"></i>' . $comp['issues'] . ' diferenças</span>';
                            } else {
                                $statusBadge = '<span class="badge bg-danger"><i class="fas fa-times me-1"></i>Erro</span>';
                            }
                            ?>
                            <div class="list-group-item px-3 py-2">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="flex-grow-1 me-2" style="min-width:0;">
                                        <div class="d-flex align-items-center gap-2">
                                            <code style="font-size:12px; background:#f0f0f0; padding:2px 6px; border-radius:4px; white-space:nowrap;"><?= htmlspecialchars($dbName) ?></code>
                                            <?php if ($isActive === 0): ?>
                                                <span class="badge bg-secondary" style="font-size:9px;">Inativo</span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($clientName): ?>
                                            <small class="text-muted d-block" style="font-size:11px;"><?= htmlspecialchars($clientName) ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                        <?= $statusBadge ?>
                                        <?php if ($comp['status'] !== 'ok'): ?>
                                            <button class="btn btn-sm btn-outline-primary btn-view-diff" data-db="<?= htmlspecialchars($dbName) ?>" title="Ver diferenças" style="padding:2px 8px; font-size:11px;">
                                                <i class="fas fa-search"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div id="diff-detail-<?= htmlspecialchars($dbName) ?>" style="display:none; margin-top:8px; padding:10px; background:#f8f9fa; border-radius:8px; font-size:12px;">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Coluna direita: Pending SQL Files + Aplicar SQL -->
    <div class="col-lg-7">

        <!-- AUTO-001: Pending SQL Files from /sql/ -->
        <?php if (!empty($pendingSqlFiles)): ?>
        <div class="card mb-4">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-file-code" style="color: #f59e0b;"></i>
                    <strong>Arquivos SQL Pendentes</strong>
                </div>
                <span class="badge bg-warning text-dark"><?= count($pendingSqlFiles) ?> pendente(s)</span>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush" style="max-height: 400px; overflow-y: auto;">
                    <?php foreach ($pendingSqlFiles as $sf): ?>
                        <div class="list-group-item px-3 py-2">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="flex-grow-1 me-2" style="min-width:0;">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fas fa-file-alt text-muted" style="font-size:12px;"></i>
                                        <code style="font-size:12px; background:#fff3cd; padding:2px 6px; border-radius:4px;"><?= htmlspecialchars($sf['name']) ?></code>
                                        <small class="text-muted">(<?= number_format($sf['size'] / 1024, 1) ?> KB)</small>
                                    </div>
                                    <small class="text-muted d-block" style="font-size:11px;">
                                        <i class="far fa-clock me-1"></i><?= date('d/m/Y H:i', $sf['modified']) ?>
                                    </small>
                                </div>
                                <div class="d-flex gap-1 flex-shrink-0">
                                    <button class="btn btn-sm btn-outline-primary btn-preview-sql" 
                                            data-file="<?= htmlspecialchars($sf['name']) ?>"
                                            data-content="<?= htmlspecialchars($sf['content']) ?>"
                                            title="Visualizar conteúdo" style="padding:2px 8px; font-size:11px;">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-success btn-load-sql" 
                                            data-file="<?= htmlspecialchars($sf['name']) ?>"
                                            data-content="<?= htmlspecialchars($sf['content']) ?>"
                                            title="Carregar no editor SQL" style="padding:2px 8px; font-size:11px;">
                                        <i class="fas fa-download"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-warning btn-apply-single"
                                            data-file="<?= htmlspecialchars($sf['name']) ?>"
                                            title="Aplicar este arquivo nos bancos selecionados" style="padding:2px 8px; font-size:11px;">
                                        <i class="fas fa-play-circle"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="card-footer d-flex gap-2">
                <button type="button" class="btn btn-warning btn-sm flex-grow-1" id="btnApplyAllFiles" 
                        data-count="<?= count($pendingSqlFiles) ?>">
                    <i class="fas fa-play-circle me-1"></i>Executar Todos Pendentes (<?= count($pendingSqlFiles) ?>)
                </button>
            </div>
        </div>
        <?php endif; ?>

        <form id="migrationForm" action="?page=master_migrations&action=apply" method="POST">
                    <?= csrf_field() ?>
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="fas fa-code" style="color: var(--akti-primary);"></i>
                    <strong>Aplicar Migração SQL</strong>
                </div>
                <div class="card-body">
                    <!-- Nome da migração -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="migrationName">
                            <i class="fas fa-tag me-1 text-muted"></i>Nome da Migração
                        </label>
                        <input type="text" name="migration_name" id="migrationName" class="form-control" 
                               placeholder="Ex: Adicionar coluna status em orders" 
                               style="border:2px solid #dee2e6; border-radius:8px; padding:10px 14px;">
                    </div>

                    <!-- Upload ou cole SQL -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="fas fa-file-code me-1 text-muted"></i>SQL
                            <small class="text-muted fw-normal ms-2">Carregue um arquivo .sql ou cole diretamente</small>
                        </label>
                        <div class="d-flex gap-2 mb-2">
                            <label class="btn btn-sm btn-outline-primary mb-0" for="sqlFileInput" style="cursor:pointer;">
                                <i class="fas fa-upload me-1"></i>Carregar arquivo .sql
                            </label>
                            <input type="file" id="sqlFileInput" accept=".sql" style="display:none;">
                            <span id="sqlFileName" class="align-self-center text-muted" style="font-size:12px;"></span>
                        </div>
                        <textarea name="sql_content" id="sqlContent" class="form-control" rows="12" 
                                  placeholder="-- Cole aqui o SQL de migração...&#10;-- Ex:&#10;ALTER TABLE orders ADD COLUMN new_field VARCHAR(100) DEFAULT NULL;&#10;ALTER TABLE products ADD COLUMN fiscal_obs TEXT DEFAULT NULL;"
                                  style="font-family: 'Consolas', 'Monaco', 'Courier New', monospace; font-size:13px; border:2px solid #dee2e6; border-radius:8px; resize:vertical; line-height:1.6;"></textarea>
                    </div>

                    <!-- Checkbox: aplicar no init_base -->
                    <div class="form-check mb-3">
                        <input type="checkbox" name="apply_to_init_base" id="applyToInitBase" class="form-check-input" checked>
                        <label class="form-check-label" for="applyToInitBase">
                            <strong>Aplicar também no banco de referência</strong> (<code><?= htmlspecialchars($initBase) ?></code>)
                            <br><small class="text-muted">Recomendado para manter o banco base atualizado para novos clientes</small>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Seleção de bancos -->
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-database" style="color: var(--akti-primary);"></i>
                        <strong>Bancos Alvo</strong>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary"><span id="selectedCount">0</span> selecionado(s)</span>
                        <button type="button" id="selectDivergent" class="btn btn-sm btn-outline-warning" style="font-size:11px; padding:2px 8px;">
                            <i class="fas fa-exclamation-triangle me-1"></i>Divergentes
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($tenantDbs)): ?>
                        <div class="px-3 py-2 border-bottom" style="background:#f8f9fa;">
                            <div class="form-check">
                                <input type="checkbox" id="selectAllDbs" class="form-check-input">
                                <label class="form-check-label fw-semibold" for="selectAllDbs" style="font-size:13px;">Selecionar todos</label>
                            </div>
                        </div>
                        <!-- akti_master - Sistema Master -->
                        <div class="px-3 py-2 border-bottom d-flex align-items-center justify-content-between" style="font-size:13px; background: #fff3cd;">
                            <div class="form-check mb-0">
                                <input type="checkbox" name="selected_dbs[]" value="akti_master" 
                                       class="form-check-input" id="db_akti_master"
                                       data-status="master">
                                <label class="form-check-label" for="db_akti_master">
                                    <code style="background:#ffc107; color:#000; padding:2px 6px; border-radius:4px;">akti_master</code>
                                    <span class="text-muted ms-1">(Sistema Master)</span>
                                </label>
                            </div>
                            <span class="badge bg-warning text-dark" style="font-size:10px;"><i class="fas fa-crown me-1"></i>Master</span>
                        </div>
                        <div style="max-height:300px; overflow-y:auto;">
                            <?php foreach ($tenantDbs as $db):
                                $comp = $comparisons[$db] ?? null;
                                $status = $comp ? $comp['status'] : 'unknown';
                                $clientInfo = $dbClientMap[$db] ?? null;
                            ?>
                                <div class="px-3 py-2 border-bottom d-flex align-items-center justify-content-between" style="font-size:13px;">
                                    <div class="form-check mb-0">
                                        <input type="checkbox" name="selected_dbs[]" value="<?= htmlspecialchars($db) ?>" 
                                               class="form-check-input" id="db_<?= htmlspecialchars($db) ?>"
                                               data-status="<?= $status ?>">
                                        <label class="form-check-label" for="db_<?= htmlspecialchars($db) ?>">
                                            <code><?= htmlspecialchars($db) ?></code>
                                            <?php if ($clientInfo): ?>
                                                <span class="text-muted ms-1">(<?= htmlspecialchars($clientInfo['client_name']) ?>)</span>
                                            <?php endif; ?>
                                        </label>
                                    </div>
                                    <?php if ($status === 'ok'): ?>
                                        <span class="badge bg-success" style="font-size:10px;">OK</span>
                                    <?php elseif ($status === 'divergent'): ?>
                                        <span class="badge bg-warning text-dark" style="font-size:10px;"><?= $comp['issues'] ?> dif.</span>
                                    <?php elseif ($status === 'error'): ?>
                                        <span class="badge bg-danger" style="font-size:10px;">Erro</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">Nenhum banco tenant encontrado</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-akti btn-lg">
                    <i class="fas fa-play me-2"></i>Executar Migração nos Bancos Selecionados
                </button>
            </div>
        </form>

        <!-- Histórico recente -->
        <?php if (!empty($history)): ?>
            <div class="card mt-4">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="fas fa-history" style="color: var(--akti-primary);"></i>
                    <strong>Últimas Migrações</strong>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0" style="font-size:12px;">
                            <thead>
                                <tr style="background:#f8f9fa;">
                                    <th class="ps-3">Migração</th>
                                    <th>Banco</th>
                                    <th>Status</th>
                                    <th>Stmts</th>
                                    <th>Data</th>
                                    <th>Admin</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($history as $h): ?>
                                    <tr>
                                        <td class="ps-3">
                                            <strong><?= htmlspecialchars(mb_substr($h['migration_name'], 0, 40)) ?></strong>
                                        </td>
                                        <td><code style="font-size:11px;"><?= htmlspecialchars($h['db_name']) ?></code></td>
                                        <td>
                                            <?php if ($h['status'] === 'success'): ?>
                                                <span class="badge bg-success" style="font-size:10px;">OK</span>
                                            <?php elseif ($h['status'] === 'partial'): ?>
                                                <span class="badge bg-warning text-dark" style="font-size:10px;">Parcial</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger" style="font-size:10px;">Falha</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="text-success"><?= $h['statements_ok'] ?></span>/<span class="text-muted"><?= $h['statements_total'] ?></span>
                                            <?php if ($h['statements_failed'] > 0): ?>
                                                <span class="text-danger">(<?= $h['statements_failed'] ?> erros)</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= date('d/m/Y H:i', strtotime($h['applied_at'])) ?></td>
                                        <td><?= htmlspecialchars($h['admin_name'] ?? '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
