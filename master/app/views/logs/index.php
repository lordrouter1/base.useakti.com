<?php
/**
 * View: Logs do Nginx
 * Visualização simplificada de logs de erro e acesso do PHP/Nginx
 */
$pageTitle = 'Logs do Servidor';
$pageSubtitle = 'Logs de erro e acesso do Nginx / PHP-FPM';

$pageScripts = <<<'SCRIPTS'
<script>
$(document).ready(function() {

    var autoRefreshTimer = null;

    // ── Carregar conteúdo de um log via AJAX ─────────────────
    function loadLogContent(file, lines) {
        lines = lines || 200;
        $('#logPlaceholder').hide();
        $('#logContentArea').html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i><br><small class="text-muted mt-2">Carregando...</small></div>');
        $('#logContentCard').show();
        $('#btnDownloadLog').attr('href', '?page=logs&action=download&file=' + encodeURIComponent(file)).show();

        $.getJSON('?page=logs&action=read', {file: file, lines: lines}, function(data) {
            if (data.success) {
                renderLogContent(data.content || '(vazio)', data.filename, data.lines);
            } else {
                $('#logContentArea').html('<div class="alert alert-danger mb-0"><i class="fas fa-times-circle me-2"></i>' + escapeHtml(data.error || 'Erro ao ler o log') + '</div>');
            }
        }).fail(function() {
            $('#logContentArea').html('<div class="alert alert-danger mb-0"><i class="fas fa-times-circle me-2"></i>Erro de conexão</div>');
        });
    }

    // ── Renderizar conteúdo do log com formatação ────────────
    function renderLogContent(raw, filename, lineCount) {
        var lines = raw.split('\n');
        var html = '';
        var lineNum = 0;

        lines.forEach(function(line) {
            lineNum++;
            if (!line.trim()) return;

            var cls = 'log-line';
            var levelBadge = '';

            if (/PHP Fatal error|fatal/i.test(line)) {
                cls += ' log-fatal';
                levelBadge = '<span class="log-badge log-badge-fatal">FATAL</span>';
            } else if (/PHP Warning|warning|\[warn\]/i.test(line)) {
                cls += ' log-warn';
                levelBadge = '<span class="log-badge log-badge-warn">WARN</span>';
            } else if (/PHP Notice|notice/i.test(line)) {
                cls += ' log-notice';
                levelBadge = '<span class="log-badge log-badge-notice">NOTICE</span>';
            } else if (/\[error\]|PHP Parse error|500|502|503/i.test(line)) {
                cls += ' log-error';
                levelBadge = '<span class="log-badge log-badge-error">ERROR</span>';
            } else if (/PHP Deprecated|deprecated/i.test(line)) {
                cls += ' log-deprecated';
                levelBadge = '<span class="log-badge log-badge-deprecated">DEPR</span>';
            } else if (/404|403/i.test(line)) {
                cls += ' log-http-err';
                levelBadge = '<span class="log-badge log-badge-http">HTTP</span>';
            }

            // Extrair timestamp se presente
            var timestamp = '';
            var rest = escapeHtml(line);
            var tsMatch = line.match(/^(\d{4}[\/-]\d{2}[\/-]\d{2}[ T]\d{2}:\d{2}:\d{2})/);
            if (tsMatch) {
                timestamp = '<span class="log-ts">' + escapeHtml(tsMatch[1]) + '</span> ';
                rest = escapeHtml(line.substring(tsMatch[0].length));
            }

            html += '<div class="' + cls + '"><span class="log-num">' + lineNum + '</span>' + levelBadge + timestamp + '<span class="log-text">' + rest + '</span></div>';
        });

        if (!html) html = '<div class="text-center py-4 text-muted">(Arquivo vazio)</div>';

        $('#logContentArea').html('<div id="logViewer" class="log-viewer">' + html + '</div>');

        // Scroll to bottom
        var viewer = document.getElementById('logViewer');
        if (viewer) viewer.scrollTop = viewer.scrollHeight;

        $('#logFileName').text(filename || '—');
        $('#logLineCount').text(lineCount || lineNum);
    }

    // ── Buscar no log via AJAX ────────────────────────────────
    function searchLog(file, query) {
        if (!file || !query) return;
        $('#logPlaceholder').hide();
        $('#logContentArea').html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i><br><small class="text-muted mt-2">Buscando...</small></div>');
        $('#logContentCard').show();

        $.getJSON('?page=logs&action=search', {file: file, q: query}, function(data) {
            if (data.success) {
                if (data.results && data.results.length > 0) {
                    var html = '';
                    var lineNum = 0;
                    data.results.forEach(function(line) {
                        lineNum++;
                        var escaped = escapeHtml(line);
                        var regex = new RegExp('(' + escapeRegex(query) + ')', 'gi');
                        escaped = escaped.replace(regex, '<mark style="background:#fbbf24;color:#000;padding:0 2px;border-radius:2px;">$1</mark>');
                        html += '<div class="log-line"><span class="log-num">' + lineNum + '</span><span class="log-text">' + escaped + '</span></div>';
                    });
                    $('#logContentArea').html(
                        '<div class="d-flex align-items-center gap-2 mb-2"><span class="badge bg-primary">' + data.count + ' resultado(s)</span> para "<strong>' + escapeHtml(query) + '</strong>"</div>' +
                        '<div class="log-viewer">' + html + '</div>'
                    );
                } else {
                    $('#logContentArea').html('<div class="alert alert-info mb-0"><i class="fas fa-search me-2"></i>Nenhum resultado para "<strong>' + escapeHtml(query) + '</strong>"</div>');
                }
            } else {
                $('#logContentArea').html('<div class="alert alert-danger mb-0"><i class="fas fa-times-circle me-2"></i>' + escapeHtml(data.error || 'Erro na busca') + '</div>');
            }
        }).fail(function() {
            $('#logContentArea').html('<div class="alert alert-danger mb-0"><i class="fas fa-times-circle me-2"></i>Erro de conexão</div>');
        });
    }

    // ── Clique em arquivo de log ──────────────────────────────
    $(document).on('click', '.log-file-item', function(e) {
        e.preventDefault();
        var file = $(this).data('file');
        var lines = parseInt($('#logLines').val()) || 200;
        $('.log-file-item').removeClass('active');
        $(this).addClass('active');
        $('#currentFile').val(file);
        loadLogContent(file, lines);
    });

    // ── Alterar número de linhas ──────────────────────────────
    $('#logLines').on('change', function() {
        var file = $('#currentFile').val();
        if (file) loadLogContent(file, parseInt($(this).val()) || 200);
    });

    // ── Busca ─────────────────────────────────────────────────
    $('#btnSearchLog').on('click', function() {
        var file = $('#currentFile').val();
        var query = $('#searchQuery').val().trim();
        if (!file) {
            Swal.fire({icon:'warning', title:'Selecione um arquivo', text:'Clique em um arquivo de log primeiro.', timer:2000, showConfirmButton:false, toast:true, position:'top-end'});
            return;
        }
        if (!query) return;
        searchLog(file, query);
    });
    $('#searchQuery').on('keypress', function(e) {
        if (e.which === 13) $('#btnSearchLog').click();
    });

    // ── Limpar busca ──────────────────────────────────────────
    $('#btnClearSearch').on('click', function() {
        $('#searchQuery').val('');
        var file = $('#currentFile').val();
        if (file) loadLogContent(file, parseInt($('#logLines').val()) || 200);
    });

    // ── Auto-refresh ──────────────────────────────────────────
    $('#btnAutoRefresh').on('click', function() {
        var btn = $(this);
        if (autoRefreshTimer) {
            clearInterval(autoRefreshTimer);
            autoRefreshTimer = null;
            btn.html('<i class="fas fa-sync me-1"></i>Auto').removeClass('btn-success').addClass('btn-outline-secondary');
        } else {
            var file = $('#currentFile').val();
            if (!file) {
                Swal.fire({icon:'warning', title:'Selecione um arquivo', timer:2000, showConfirmButton:false, toast:true, position:'top-end'});
                return;
            }
            btn.html('<i class="fas fa-stop me-1"></i>Parar').removeClass('btn-outline-secondary').addClass('btn-success');
            autoRefreshTimer = setInterval(function() {
                var f = $('#currentFile').val();
                var l = parseInt($('#logLines').val()) || 200;
                if (f) loadLogContent(f, l);
            }, 5000);
        }
    });

    // ── Filtro de busca na lista de arquivos ──────────────────
    $('#filterFiles').on('keyup', function() {
        var q = $(this).val().toLowerCase();
        $('.log-file-item').each(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(q) !== -1);
        });
    });

    // ── Helpers ───────────────────────────────────────────────
    function escapeHtml(text) {
        if (!text) return '';
        return $('<div>').text(text).html();
    }
    function escapeRegex(str) {
        return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    // ── Init: se veio com arquivo selecionado ─────────────────
    if (_selectedFile) {
        $('#currentFile').val(_selectedFile);
        $('.log-file-item[data-file="' + _selectedFile + '"]').addClass('active');
        if (!_searchQuery) {
            loadLogContent(_selectedFile, _logLines);
        }
    }
});
</script>
SCRIPTS;

// Inject PHP variables before the main script
$pageScriptsInit = '<script>';
$pageScriptsInit .= 'var _selectedFile = ' . json_encode($selectedFile ?? '') . ';';
$pageScriptsInit .= 'var _searchQuery = ' . json_encode($searchQuery ?? '') . ';';
$pageScriptsInit .= 'var _logLines = ' . (int)($logLines ?? 200) . ';';
$pageScriptsInit .= '</script>';
$pageScripts = $pageScriptsInit . $pageScripts;

require_once __DIR__ . '/../layout/header.php';
?>

<!-- Estilos do viewer de logs -->
<style>
.log-viewer{background:#0f172a;border-radius:10px;padding:8px 0;max-height:600px;overflow:auto;font-family:'JetBrains Mono','Fira Code','Consolas',monospace;font-size:12px;line-height:1.7}
.log-line{display:flex;align-items:flex-start;gap:8px;padding:2px 12px;border-left:3px solid transparent;transition:background .15s}
.log-line:hover{background:rgba(255,255,255,.04)}
.log-num{min-width:36px;text-align:right;color:#475569;font-size:10px;user-select:none;padding-top:2px}
.log-ts{color:#60a5fa;font-size:11px}
.log-text{color:#cbd5e1;word-break:break-all;flex:1}
.log-badge{font-size:9px;font-weight:700;padding:1px 5px;border-radius:3px;text-transform:uppercase;letter-spacing:.5px;flex-shrink:0;margin-top:2px}
.log-badge-fatal{background:#7f1d1d;color:#fca5a5}
.log-badge-error{background:#7f1d1d;color:#fca5a5}
.log-badge-warn{background:#78350f;color:#fde68a}
.log-badge-notice{background:#1e3a5f;color:#93c5fd}
.log-badge-deprecated{background:#3b0764;color:#d8b4fe}
.log-badge-http{background:#4a1d96;color:#c4b5fd}
.log-fatal,.log-error{border-left-color:#ef4444;background:rgba(239,68,68,.06)}
.log-warn{border-left-color:#f59e0b;background:rgba(245,158,11,.04)}
.log-notice{border-left-color:#3b82f6;background:rgba(59,130,246,.04)}
.log-deprecated{border-left-color:#8b5cf6;background:rgba(139,92,246,.04)}
.log-http-err{border-left-color:#a855f7;background:rgba(168,85,247,.04)}
.log-file-item{cursor:pointer;border-radius:8px;padding:10px 12px;margin-bottom:4px;transition:all .15s;border:1px solid transparent}
.log-file-item:hover{background:#f1f5f9;border-color:#e2e8f0}
.log-file-item.active{background:#eff6ff;border-color:#bfdbfe;box-shadow:0 0 0 2px rgba(59,130,246,.1)}
</style>

<!-- Campo oculto para arquivo selecionado -->
<input type="hidden" id="currentFile" value="<?= htmlspecialchars($selectedFile ?? '') ?>">

<?php if (!empty($diagnostic['issues'])): ?>
<div class="card mb-4 border-warning">
    <div class="card-header d-flex align-items-center gap-2" style="background: linear-gradient(135deg, #fef3c7, #fde68a); color:#92400e;">
        <i class="fas fa-triangle-exclamation"></i>
        <strong>Diagnóstico</strong>
        <span class="badge bg-warning text-dark ms-auto"><?= count($diagnostic['issues']) ?> problema(s)</span>
    </div>
    <div class="card-body">
        <?php foreach ($diagnostic['issues'] as $i => $issue): ?>
        <div class="alert alert-danger py-2 mb-2" style="font-size:13px;">
            <i class="fas fa-times-circle me-2"></i><?= htmlspecialchars($issue) ?>
            <?php if (isset($diagnostic['fixes'][$i])): ?>
                <br><code style="background:#1e1e1e;color:#d4d4d4;padding:3px 8px;border-radius:4px;font-size:11px;display:inline-block;margin-top:4px;"><?= htmlspecialchars($diagnostic['fixes'][$i]) ?></code>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="stat-card" style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
            <div class="stat-icon"><i class="fas fa-file-lines"></i></div>
            <div class="stat-value"><?= count($logFiles) ?></div>
            <div class="stat-label">Arquivos de Log</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
            <div class="stat-icon"><i class="fas fa-bug"></i></div>
            <div class="stat-value"><?= count(array_filter($logFiles, fn($f) => $f['type'] === 'error')) ?></div>
            <div class="stat-label">Logs de Erro</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">
            <div class="stat-icon"><i class="fas fa-globe"></i></div>
            <div class="stat-value"><?= count(array_filter($logFiles, fn($f) => $f['type'] === 'access')) ?></div>
            <div class="stat-label">Logs de Acesso</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card" style="background: linear-gradient(135deg, #10b981, #059669);">
            <div class="stat-icon"><i class="fas fa-folder-open"></i></div>
            <div class="stat-value" style="font-size:14px;"><?= htmlspecialchars($logPath) ?></div>
            <div class="stat-label">Pasta de Logs</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Lista de arquivos (lateral esquerda) -->
    <div class="col-xl-3 col-lg-4">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between" style="background: linear-gradient(135deg, #1b3d6e, #2563eb); color:white; border-radius:12px 12px 0 0; padding:12px 16px;">
                <div><i class="fas fa-folder-open me-2"></i><strong>Arquivos</strong></div>
                <span class="badge bg-white text-dark"><?= count($logFiles) ?></span>
            </div>
            <div class="card-body p-3">
                <input type="text" class="form-control form-control-sm mb-3" id="filterFiles" placeholder="Filtrar arquivos..."
                       style="border:2px solid #e2e8f0; border-radius:8px; font-size:12px; padding:8px 12px;">

                <?php if ($listError): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-folder-open fa-2x mb-2 opacity-25"></i>
                        <p style="font-size:12px;"><?= htmlspecialchars($listError) ?></p>
                    </div>
                <?php elseif (empty($logFiles)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-file-lines fa-2x mb-2 opacity-25"></i>
                        <p style="font-size:12px;">Nenhum log encontrado</p>
                    </div>
                <?php else: ?>
                    <div style="max-height:520px; overflow-y:auto;">
                        <?php foreach ($logFiles as $file):
                            $isError = ($file['type'] === 'error');
                            $isAccess = ($file['type'] === 'access');
                            $icon = $isError ? 'fa-circle-exclamation' : ($isAccess ? 'fa-globe' : 'fa-file-lines');
                            $iconColor = $isError ? '#ef4444' : ($isAccess ? '#3b82f6' : '#6b7280');
                            $isSelected = ($selectedFile === $file['name']);
                        ?>
                        <div class="log-file-item d-flex align-items-center gap-2 <?= $isSelected ? 'active' : '' ?> <?= !$file['readable'] ? 'opacity-50' : '' ?>"
                             data-file="<?= htmlspecialchars($file['name']) ?>">
                            <i class="fas <?= $icon ?>" style="color:<?= $iconColor ?>; font-size:14px; flex-shrink:0;"></i>
                            <div style="flex:1; min-width:0;">
                                <div class="fw-semibold text-truncate" style="font-size:11px;" title="<?= htmlspecialchars($file['name']) ?>">
                                    <?= htmlspecialchars($file['name']) ?>
                                </div>
                                <div class="d-flex gap-2 text-muted" style="font-size:10px;">
                                    <span><?= htmlspecialchars($file['size_human']) ?></span>
                                    <?php if ($file['compressed']): ?><span><i class="fas fa-file-zipper"></i></span><?php endif; ?>
                                    <?php if (!$file['readable']): ?><span class="text-danger"><i class="fas fa-lock"></i></span><?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Área de conteúdo do log (direita) -->
    <div class="col-xl-9 col-lg-8">
        <!-- Toolbar -->
        <div class="card mb-3">
            <div class="card-body p-2 px-3">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <!-- Busca -->
                    <div class="input-group input-group-sm" style="max-width:280px;">
                        <input type="text" class="form-control" id="searchQuery" placeholder="Buscar no log..."
                               style="border:2px solid #e2e8f0; border-right:none; border-radius:8px 0 0 8px; font-size:12px;">
                        <button class="btn btn-outline-primary btn-sm" id="btnSearchLog" style="border:2px solid #e2e8f0; border-left:none; border-right:none;">
                            <i class="fas fa-search"></i>
                        </button>
                        <button class="btn btn-outline-secondary btn-sm" id="btnClearSearch" style="border:2px solid #e2e8f0; border-left:none; border-radius:0 8px 8px 0;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <!-- Linhas -->
                    <select class="form-select form-select-sm" id="logLines" style="max-width:120px; border:2px solid #e2e8f0; border-radius:8px; font-size:12px;">
                        <option value="50">50 linhas</option>
                        <option value="100">100 linhas</option>
                        <option value="200" selected>200 linhas</option>
                        <option value="500">500 linhas</option>
                        <option value="1000">1000 linhas</option>
                    </select>

                    <!-- Ações -->
                    <button class="btn btn-sm btn-outline-secondary" id="btnAutoRefresh" title="Auto-refresh a cada 5s">
                        <i class="fas fa-sync me-1"></i>Auto
                    </button>

                    <a href="#" class="btn btn-sm btn-outline-success" id="btnDownloadLog" style="display:none;" title="Baixar arquivo de log">
                        <i class="fas fa-download me-1"></i>Download
                    </a>

                    <!-- Info -->
                    <span class="ms-auto text-muted" style="font-size:11px;">
                        <i class="fas fa-file me-1"></i><span id="logFileName">—</span>
                        <span class="ms-2"><i class="fas fa-list-ol me-1"></i><span id="logLineCount">0</span></span>
                    </span>
                </div>
            </div>
        </div>

        <!-- Conteúdo do log -->
        <div class="card" id="logContentCard" <?= $selectedFile ? '' : 'style="display:none;"' ?>>
            <div class="card-body p-3" id="logContentArea">
                <?php if ($selectedFile && $logContent): ?>
                    <?php if (!empty($logContent['is_search'])): ?>
                        <div class="mb-2">
                            <span class="badge bg-primary"><?= $logContent['lines'] ?> resultado(s)</span>
                            para "<strong><?= htmlspecialchars($logContent['query'] ?? '') ?></strong>"
                        </div>
                        <div class="log-viewer">
                            <?php foreach (explode("\n", $logContent['content'] ?? '') as $i => $line): if (!trim($line)) continue; ?>
                            <div class="log-line"><span class="log-num"><?= $i + 1 ?></span><span class="log-text"><?= htmlspecialchars($line) ?></span></div>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif ($logContent['success']): ?>
                        <div class="log-viewer">
                            <?php foreach (explode("\n", $logContent['content'] ?? '') as $i => $line): if (!trim($line)) continue; ?>
                            <div class="log-line"><span class="log-num"><?= $i + 1 ?></span><span class="log-text"><?= htmlspecialchars($line) ?></span></div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-danger mb-0">
                            <i class="fas fa-times-circle me-2"></i><?= htmlspecialchars($logContent['error'] ?? 'Erro ao ler o arquivo') ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-file-lines fa-3x mb-3 opacity-25"></i>
                        <h6>Selecione um arquivo</h6>
                        <p style="font-size:12px;">Clique em um arquivo de log à esquerda</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!$selectedFile): ?>
        <div class="card" id="logPlaceholder">
            <div class="card-body text-center py-5 text-muted">
                <i class="fas fa-file-lines fa-3x mb-3 opacity-25"></i>
                <h6>Selecione um arquivo de log</h6>
                <p style="font-size:12px;">Clique em um arquivo na lista à esquerda para visualizar</p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Análise de erros -->
        <?php if (!empty($errorAnalysis)): ?>
        <div class="card mt-3">
            <div class="card-header d-flex align-items-center gap-2" style="background: linear-gradient(135deg, #fef2f2, #fee2e2); color:#991b1b; border-radius:12px 12px 0 0; padding:10px 16px;">
                <i class="fas fa-chart-bar"></i>
                <strong>Análise de Erros</strong>
                <small class="ms-auto text-muted">(últimas 1000 linhas)</small>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr style="font-size:11px; background:#f8f9fa;">
                            <th style="padding:8px 12px;">Tipo</th>
                            <th style="padding:8px 12px; text-align:right; width:80px;">Qtd</th>
                            <th style="padding:8px 12px; width:35%;">Frequência</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            $maxCount = max($errorAnalysis);
                            foreach ($errorAnalysis as $errorType => $count):
                                $pct = ($maxCount > 0) ? round($count / $maxCount * 100) : 0;
                                $barColor = '#ef4444';
                                if (stripos($errorType, 'Warning') !== false) $barColor = '#f59e0b';
                                if (stripos($errorType, 'Notice') !== false) $barColor = '#3b82f6';
                                if (stripos($errorType, 'Deprecated') !== false) $barColor = '#8b5cf6';
                        ?>
                        <tr style="font-size:12px;">
                            <td style="padding:6px 12px;"><code style="font-size:11px;background:#f1f5f9;padding:2px 6px;border-radius:3px;"><?= htmlspecialchars($errorType) ?></code></td>
                            <td style="padding:6px 12px; text-align:right;"><strong><?= $count ?></strong></td>
                            <td style="padding:6px 12px;">
                                <div class="progress" style="height:14px;border-radius:4px;">
                                    <div class="progress-bar" style="width:<?= $pct ?>%;background:<?= $barColor ?>;font-size:9px;line-height:14px;"><?= $count ?></div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
