<?php
/**
 * View: Git Version Control
 * Controle de versionamento dos projetos deployados
 */
$pageTitle = 'Versionamento Git';
$pageSubtitle = 'Controle de versão dos projetos em ' . htmlspecialchars($basePath);
$topbarActions = '
    <button class="btn btn-akti-outline me-2" id="btnDiagnose" title="Ver diagnóstico detalhado do ambiente">
        <i class="fas fa-stethoscope me-2"></i>Diagnóstico
    </button>
    <button class="btn btn-akti-outline me-2" id="btnFetchAll" title="Atualizar referências remotas de todos">
        <i class="fas fa-sync me-2"></i>Fetch All
    </button>
    <button class="btn btn-akti" id="btnPullAll" title="Pull em todos os repositórios sem alterações locais">
        <i class="fas fa-cloud-arrow-down me-2"></i>Pull All
    </button>
';

$pageScripts = <<<'SCRIPTS'
<script>
$(document).ready(function() {

    var _totalRepos = 0;

    // ── Carregar repositórios em segundo plano ────────────────
    function loadReposAsync() {
        $.getJSON('?page=master_git&action=loadRepos', function(data) {
            if (!data.success) {
                $('#reposLoading').html('<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>Erro ao carregar repositórios</div>');
                $('#envInfoBar').html('<span class="text-danger"><i class="fas fa-times-circle me-1"></i>Erro ao carregar diagnóstico</span>');
                return;
            }

            // Renderizar diagnóstico
            if (data.diagnostic) {
                renderDiagnostic(data.diagnostic);
            }

            // Atualizar stats
            var s = data.stats;
            _totalRepos = s.total;
            $('#statTotal').text(s.total);
            $('#statUpToDate').text(s.upToDate);
            $('#statBehind').text(s.behind);
            $('#statDirty').text(s.dirty);
            if (s.errors > 0) {
                $('#statErrorCard').removeClass('d-none');
                $('#statErrors').text(s.errors);
            }
            $('.stat-card-placeholder').addClass('d-none');
            $('.stat-card-real').removeClass('d-none');

            // Renderizar repos
            renderRepos(data.repos);
        }).fail(function() {
            $('#reposLoading').html('<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>Erro de conexão ao carregar repositórios</div>');
            $('#envInfoBar').html('<span class="text-danger"><i class="fas fa-times-circle me-1"></i>Erro de conexão</span>');
        });
    }

    function renderDiagnostic(diag) {
        // Barra de informações do ambiente
        var bar = '';
        bar += '<span><i class="fas fa-server me-1"></i>' + escapeHtml(diag.os) + '</span>';
        bar += '<span><i class="fas fa-user me-1"></i>PHP: <strong>' + escapeHtml(diag.php_user) + '</strong> (' + escapeHtml(diag.php_sapi || 'cli') + ')</span>';
        bar += '<span><i class="fab fa-git-alt me-1"></i>' + escapeHtml(diag.git_version || (diag.git_exists ? 'OK' : 'não encontrado')) + '</span>';
        bar += '<span><i class="fas fa-terminal me-1"></i>exec(): ' + (diag.exec_available ? '✅' : '❌') + '</span>';
        bar += '<span><i class="fas fa-folder me-1"></i>' + escapeHtml(diag.base_path) + ' ' + (diag.base_path_readable ? '✅' : '❌') + '</span>';
        bar += '<span><i class="fas fa-code-branch me-1"></i>Repos encontrados: <strong>' + (diag.repos_found || 0) + '</strong></span>';
        bar += '<span><i class="fas fa-shield-halved me-1"></i>safe.directory: ' + (diag.safe_directory_ok ? '✅' : '❌') + '</span>';
        if (!diag.issues || diag.issues.length === 0) {
            bar += '<span class="text-success"><i class="fas fa-check-circle me-1"></i>Ambiente OK</span>';
        }
        $('#envInfoBar').html(bar);

        // Painel de problemas (só aparece se há issues)
        if (diag.issues && diag.issues.length > 0) {
            var html = '<div class="card mb-4 border-warning">';
            html += '<div class="card-header d-flex align-items-center gap-2" style="background: linear-gradient(135deg, #fef3c7, #fde68a); color:#92400e;">';
            html += '<i class="fas fa-triangle-exclamation"></i><strong>Diagnóstico do Ambiente</strong>';
            html += '<span class="badge bg-warning text-dark ms-auto">' + diag.issues.length + ' problema(s)</span></div>';
            html += '<div class="card-body">';

            html += '<div class="row g-3 mb-3" style="font-size:12px;">';
            html += '<div class="col-md-3"><strong><i class="fas fa-server me-1"></i>OS:</strong> ' + escapeHtml(diag.os) + '</div>';
            html += '<div class="col-md-3"><strong><i class="fas fa-user me-1"></i>PHP roda como:</strong> <code style="background:#fee; padding:2px 6px; border-radius:4px;">' + escapeHtml(diag.php_user) + '</code></div>';
            html += '<div class="col-md-3"><strong><i class="fab fa-git-alt me-1"></i>Git:</strong> ';
            html += diag.git_exists ? '<span class="text-success">' + escapeHtml(diag.git_version || 'encontrado') + '</span>' : '<span class="text-danger">Não encontrado</span>';
            html += '</div>';
            html += '<div class="col-md-3"><strong><i class="fas fa-terminal me-1"></i>exec():</strong> ' + (diag.exec_available ? '<span class="text-success">Disponível</span>' : '<span class="text-danger">Desabilitado</span>') + '</div>';
            html += '</div>';

            diag.issues.forEach(function(issue, i) {
                html += '<div class="alert alert-danger py-2 mb-2" style="font-size:13px;">';
                html += '<i class="fas fa-times-circle me-2"></i><strong>Problema:</strong> ' + escapeHtml(issue);
                if (diag.fixes && diag.fixes[i]) {
                    html += '<br><i class="fas fa-wrench me-2 mt-1"></i><strong>Correção:</strong> ';
                    html += '<code style="background:#1e1e1e; color:#d4d4d4; padding:4px 10px; border-radius:4px; display:inline-block; margin-top:4px; font-size:12px; word-break:break-all;">' + escapeHtml(diag.fixes[i]) + '</code>';
                }
                html += '</div>';
            });

            if (diag.os !== 'Windows') {
                html += '<div class="mt-3 p-3" style="background:#f8f9fa; border-radius:8px; font-size:12px;">';
                html += '<strong><i class="fas fa-lightbulb text-warning me-1"></i>Correção rápida para VPS Linux:</strong>';
                html += '<pre style="background:#1e1e1e; color:#d4d4d4; padding:12px; border-radius:6px; margin-top:8px; margin-bottom:0; font-size:11px; overflow-x:auto;">';
                html += '# 1. Permitir git para o usuário do PHP (' + escapeHtml(diag.php_user) + ')\n';
                html += 'sudo -u ' + escapeHtml(diag.php_user) + ' git config --global --add safe.directory \'*\'\n\n';
                html += '# 2. Dar permissão de leitura nos repos\nsudo chmod -R o+rX /var/www/*/.git\n\n';
                html += '# 3. Verificar que exec() está permitido no php.ini do FPM\n';
                html += '# Editar: /etc/php/*/fpm/php.ini → remover \'exec\' de disable_functions\n';
                html += '# Depois: sudo systemctl restart php*-fpm\n\n';
                html += '# 4. Verificar permissão do HOME do usuário PHP\n';
                html += 'sudo mkdir -p ' + escapeHtml(diag.php_home || '/var/www') + '\n\n';
                html += '# 5. Testar manualmente\nsudo -u ' + escapeHtml(diag.php_user) + ' git -C /var/www/REPO_NAME status';
                html += '</pre></div>';
            }

            if (diag.raw_tests && Object.keys(diag.raw_tests).length > 0) {
                html += '<div class="mt-3"><button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#rawTestsCollapse">';
                html += '<i class="fas fa-bug me-1"></i>Mostrar testes raw (debug)</button>';
                html += '<div class="collapse mt-2" id="rawTestsCollapse">';
                html += '<pre style="background:#1e1e1e; color:#d4d4d4; padding:12px; border-radius:6px; font-size:10px; overflow-x:auto; max-height:300px;">' + escapeHtml(JSON.stringify(diag.raw_tests, null, 2)) + '</pre>';
                html += '</div></div>';
            }

            html += '</div></div>';
            $('#diagnosticPanel').html(html);
        }
    }

    function renderRepos(repos) {
        if (!repos || repos.length === 0) {
            $('#reposLoading').html(
                '<div class="card"><div class="card-body text-center py-5">' +
                '<i class="fas fa-code-branch fa-4x mb-3 opacity-25"></i>' +
                '<h5 class="text-muted">Nenhum repositório Git encontrado</h5>' +
                '</div></div>'
            );
            return;
        }

        var html = '<div class="row g-3">';
        repos.forEach(function(repo) {
            var status = repo.status || 'unknown';
            var safeName = (repo.name || '').replace(/[^a-zA-Z0-9_-]/g, '_');
            var sc = getStatusConfig(status, repo);

            html += '<div class="col-xl-6 repo-card-item" data-repo-name="' + escapeAttr(repo.name) + '" data-status="' + status + '">';
            html += '<div class="card h-100" style="border-left: 4px solid ' + sc.color + ';">';
            html += '<div class="card-body p-3">';

            // Header
            html += '<div class="d-flex align-items-start justify-content-between mb-2">';
            html += '<div class="flex-grow-1" style="min-width:0;">';
            html += '<div class="d-flex align-items-center gap-2 mb-1">';
            html += '<h6 class="mb-0 fw-bold" style="font-size:15px;"><i class="fas fa-folder-open me-1" style="color:' + sc.color + ';"></i>' + escapeHtml(repo.name) + '</h6>';
            html += '<span class="badge repo-status-badge" style="background:' + sc.bg + '; color:' + sc.color + '; font-size:10px; border:1px solid ' + sc.color + '20;"><i class="fas fa-' + sc.icon + ' me-1"></i>' + sc.label + '</span>';
            html += '</div>';

            html += '<div class="d-flex flex-wrap gap-3" style="font-size:12px;">';
            html += '<span title="Branch atual"><i class="fas fa-code-branch text-success me-1"></i><strong class="repo-branch">' + escapeHtml(repo.branch || '—') + '</strong></span>';
            html += '<span title="Commit hash"><i class="fas fa-fingerprint text-primary me-1"></i><code class="repo-hash" style="font-size:11px; background:#f0f0f0; padding:1px 6px; border-radius:4px;">' + escapeHtml(repo.commit_hash_short || '—') + '</code></span>';
            if (repo.last_tag) {
                html += '<span title="Última tag"><i class="fas fa-tag text-warning me-1"></i><strong>' + escapeHtml(repo.last_tag) + '</strong></span>';
            }
            if (repo.describe) {
                html += '<span title="Versão (describe)"><i class="fas fa-bookmark text-info me-1"></i>' + escapeHtml(repo.describe) + '</span>';
            }
            html += '</div></div>';

            // Actions
            html += '<div class="d-flex gap-1 flex-shrink-0 ms-2">';
            html += '<button class="btn btn-sm btn-outline-secondary btn-fetch" data-repo="' + escapeAttr(repo.name) + '" title="Fetch"><i class="fas fa-sync"></i></button>';
            html += '<button class="btn btn-sm btn-outline-primary btn-pull" data-repo="' + escapeAttr(repo.name) + '" data-name="' + escapeAttr(repo.name) + '" title="Pull"><i class="fas fa-cloud-arrow-down"></i></button>';
            html += '<button class="btn btn-sm btn-outline-info btn-detail" data-repo="' + escapeAttr(repo.name) + '" title="Ver detalhes"><i class="fas fa-info-circle"></i></button>';
            html += '<button class="btn btn-sm btn-outline-danger btn-force-reset" data-repo="' + escapeAttr(repo.name) + '" data-name="' + escapeAttr(repo.name) + '" title="Force Reset"><i class="fas fa-skull-crossbones"></i></button>';
            html += '</div></div>';

            // Commit details
            html += '<div class="d-flex flex-wrap gap-3 mt-2" style="font-size:11px; color:#6b7280;">';
            if (repo.commit_message) {
                var msg = repo.commit_message.length > 80 ? repo.commit_message.substring(0, 80) + '…' : repo.commit_message;
                html += '<span class="text-truncate" style="max-width:400px;" title="' + escapeAttr(repo.commit_message) + '"><i class="fas fa-comment me-1"></i>' + escapeHtml(msg) + '</span>';
            }
            if (repo.commit_author) html += '<span><i class="fas fa-user me-1"></i>' + escapeHtml(repo.commit_author) + '</span>';
            if (repo.commit_date) {
                var dt = new Date(repo.commit_date);
                html += '<span><i class="fas fa-clock me-1"></i>' + dt.toLocaleString('pt-BR', {day:'2-digit',month:'2-digit',year:'numeric',hour:'2-digit',minute:'2-digit'}) + '</span>';
            }
            html += '</div>';

            // Change indicators
            if (repo.has_changes || repo.behind > 0 || repo.ahead > 0) {
                html += '<div class="d-flex flex-wrap gap-2 mt-2">';
                if (repo.has_changes) {
                    html += '<span class="badge" style="background:#dbeafe; color:#1d4ed8; font-size:10px;"><i class="fas fa-file-pen me-1"></i>' + repo.files_changed + ' alterado(s)</span>';
                    if (repo.untracked > 0) html += '<span class="badge" style="background:#fef3c7; color:#92400e; font-size:10px;"><i class="fas fa-file-circle-plus me-1"></i>' + repo.untracked + ' não rastreado(s)</span>';
                }
                if (repo.behind > 0) html += '<span class="badge" style="background:#fef3c7; color:#92400e; font-size:10px;"><i class="fas fa-arrow-down me-1"></i>' + repo.behind + ' atrás do remote</span>';
                if (repo.ahead > 0) html += '<span class="badge" style="background:#f3e8ff; color:#6b21a8; font-size:10px;"><i class="fas fa-arrow-up me-1"></i>' + repo.ahead + ' à frente do remote</span>';
                html += '</div>';
            }

            // Errors
            if (repo.errors && repo.errors.length) {
                html += '<div class="mt-2 p-2" style="background:#fef2f2; border-radius:6px; font-size:11px; color:#991b1b;">';
                html += '<i class="fas fa-exclamation-triangle me-1"></i><strong>Erros:</strong>';
                repo.errors.forEach(function(err) {
                    html += '<div class="mt-1"><code style="font-size:10px; background:#fee2e2; padding:1px 4px; border-radius:3px;">' + escapeHtml((err+'').substring(0, 200)) + '</code></div>';
                });
                html += '</div>';
            }

            // Remote URL
            if (repo.remote_url) {
                var remoteDisplay = repo.remote_url;
                var m = remoteDisplay.match(/(?:github|gitlab)\.com[:/](.+?)(?:\.git)?$/);
                if (m) remoteDisplay = m[1];
                var remoteLink = repo.remote_url.replace('git@github.com:', 'https://github.com/').replace(/\.git$/, '');
                html += '<div class="mt-2" style="font-size:10px; color:#9ca3af;"><i class="fab fa-git-alt me-1"></i><a href="' + escapeAttr(remoteLink) + '" target="_blank" class="text-decoration-none" style="color:#9ca3af;">' + escapeHtml(remoteDisplay) + '</a></div>';
            }

            // Detail panel placeholder
            html += '<div id="detail-' + safeName + '" style="display:none; margin-top:12px; padding:12px; background:#f8f9fa; border-radius:8px;"></div>';
            html += '</div></div></div>';
        });
        html += '</div>';

        $('#reposLoading').replaceWith(html);
    }

    function getStatusConfig(status, repo) {
        switch (status) {
            case 'up-to-date': return {color:'#10b981', bg:'#ecfdf5', icon:'check-circle', label:'Atualizado'};
            case 'behind':     return {color:'#f59e0b', bg:'#fffbeb', icon:'arrow-down', label:(repo.behind||0)+' commit(s) atrás'};
            case 'dirty':      return {color:'#3b82f6', bg:'#eff6ff', icon:'pen-to-square', label:'Alterações locais'};
            case 'ahead':      return {color:'#8b5cf6', bg:'#f5f3ff', icon:'arrow-up', label:(repo.ahead||0)+' commit(s) à frente'};
            case 'error':      return {color:'#ef4444', bg:'#fef2f2', icon:'exclamation-triangle', label:'Erro Git'};
            default:           return {color:'#6b7280', bg:'#f9fafb', icon:'question-circle', label:'Desconhecido'};
        }
    }

    // Iniciar carregamento
    loadReposAsync();

    // ── Diagnóstico detalhado ─────────────────────────────────
    $('#btnDiagnose').on('click', function() {
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Analisando...');

        $.getJSON('?page=master_git&action=diagnoseJson', function(data) {
            btn.prop('disabled', false).html('<i class="fas fa-stethoscope me-2"></i>Diagnóstico');
            var html = '<pre style="text-align:left;font-size:11px;max-height:500px;overflow:auto;background:#1e1e1e;color:#d4d4d4;padding:16px;border-radius:8px;white-space:pre-wrap;word-break:break-all;">';
            html += escapeHtml(JSON.stringify(data, null, 2));
            html += '</pre>';
            Swal.fire({
                title: 'Diagnóstico do Ambiente',
                html: html,
                width: '800px',
                confirmButtonColor: '#1b3d6e',
                confirmButtonText: 'Fechar'
            });
        }).fail(function(xhr) {
            btn.prop('disabled', false).html('<i class="fas fa-stethoscope me-2"></i>Diagnóstico');
            var errText = xhr.responseText || 'Erro de conexão';
            Swal.fire({
                icon: 'error',
                title: 'Erro no diagnóstico',
                html: '<pre style="text-align:left;font-size:11px;max-height:400px;overflow:auto;">' + escapeHtml(errText.substring(0, 2000)) + '</pre>',
                width: '700px'
            });
        });
    });

    // ── Fetch All ─────────────────────────────────────────────
    $('#btnFetchAll').on('click', function() {
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Fetching...');

        $.post('?page=master_git&action=fetchAll', function(data) {
            btn.prop('disabled', false).html('<i class="fas fa-sync me-2"></i>Fetch All');
            if (data.success) {
                Swal.fire({icon:'success', title:'Fetch concluído', text:'Referências remotas atualizadas.', timer:2000, showConfirmButton:false, toast:true, position:'top-end'});
                location.reload();
            } else {
                Swal.fire({icon:'error', title:'Erro no Fetch', text: data.message || 'Erro desconhecido'});
            }
        }, 'json').fail(function() {
            btn.prop('disabled', false).html('<i class="fas fa-sync me-2"></i>Fetch All');
            Swal.fire({icon:'error', title:'Erro de conexão'});
        });
    });

    // ── Pull All ──────────────────────────────────────────────
    $('#btnPullAll').on('click', function() {
        var totalRepos = _totalRepos;
        Swal.fire({
            icon: 'question',
            title: 'Pull em todos os repositórios?',
            html: 'Serão atualizados <strong>' + totalRepos + ' repositório(s)</strong>.<br><small class="text-muted">Repos com alterações locais serão ignorados.</small>',
            showCancelButton: true,
            confirmButtonColor: '#1b3d6e',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-cloud-arrow-down me-1"></i>Executar Pull All',
            cancelButtonText: 'Cancelar'
        }).then(function(result) {
            if (!result.isConfirmed) return;

            Swal.fire({title:'Executando Pull...', html:'<i class="fas fa-spinner fa-spin fa-2x"></i>', showConfirmButton:false, allowOutsideClick:false});

            $.post('?page=master_git&action=pullAll', function(data) {
                Swal.close();
                if (data.success) {
                    var html = '<div style="text-align:left; max-height:300px; overflow-y:auto;">';
                    $.each(data.results, function(name, r) {
                        var icon = r.success ? '✅' : (r.status === 'skipped' ? '⏭️' : '❌');
                        html += '<div class="mb-1"><strong>' + icon + ' ' + name + '</strong>: ' + (r.message || r.status) + '</div>';
                    });
                    html += '</div>';
                    Swal.fire({title:'Pull All Concluído', html: html, confirmButtonColor:'#1b3d6e', width:'600px'}).then(function() { location.reload(); });
                }
            }, 'json').fail(function() { Swal.fire({icon:'error', title:'Erro de conexão'}); });
        });
    });

    // ── Fetch individual ──────────────────────────────────────
    $(document).on('click', '.btn-fetch', function() {
        var btn = $(this);
        var repo = btn.data('repo');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.post('?page=master_git&action=fetch', {repo: repo}, function(data) {
            btn.prop('disabled', false).html('<i class="fas fa-sync"></i>');
            if (data.success) {
                Swal.fire({icon:'success', title:'Fetch OK', text: repo, timer:1500, showConfirmButton:false, toast:true, position:'top-end'});
                if (data.info) updateRepoCard(repo, data.info);
            } else {
                Swal.fire({icon:'error', title:'Erro', text: data.output || data.message});
            }
        }, 'json').fail(function() {
            btn.prop('disabled', false).html('<i class="fas fa-sync"></i>');
        });
    });

    // ── Pull individual ───────────────────────────────────────
    $(document).on('click', '.btn-pull', function() {
        var btn = $(this);
        var repo = btn.data('repo');
        var repoName = btn.data('name') || repo;

        Swal.fire({
            icon: 'question',
            title: 'Executar Git Pull?',
            html: '<strong>' + repoName + '</strong><br><small class="text-muted">O repositório será atualizado com as últimas alterações do remote.</small>',
            showCancelButton: true,
            confirmButtonColor: '#1b3d6e',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-cloud-arrow-down me-1"></i>Pull',
            cancelButtonText: 'Cancelar'
        }).then(function(result) {
            if (!result.isConfirmed) return;

            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

            $.post('?page=master_git&action=pull', {repo: repo}, function(data) {
                btn.prop('disabled', false).html('<i class="fas fa-cloud-arrow-down"></i>');

                if (data.needs_stash) {
                    // Tem alterações locais — perguntar se quer stash & pull
                    Swal.fire({
                        icon: 'warning',
                        title: 'Alterações locais detectadas',
                        html: data.message + '<br><br><small><strong>' + data.files_changed + '</strong> arquivo(s) alterado(s), <strong>' + data.untracked + '</strong> não rastreado(s)</small>',
                        showCancelButton: true,
                        showDenyButton: true,
                        confirmButtonColor: '#f59e0b',
                        denyButtonColor: '#dc3545',
                        confirmButtonText: '<i class="fas fa-box-archive me-1"></i>Stash & Pull',
                        denyButtonText: '<i class="fas fa-skull me-1"></i>Force Reset',
                        cancelButtonText: 'Cancelar'
                    }).then(function(res) {
                        if (res.isConfirmed) {
                            // Stash & Pull
                            Swal.fire({title:'Stash & Pull...', html:'<i class="fas fa-spinner fa-spin fa-2x"></i>', showConfirmButton:false, allowOutsideClick:false});
                            $.post('?page=master_git&action=pull', {repo: repo, force_stash: 1}, function(d) {
                                if (d.success) {
                                    Swal.fire({icon:'success', title:'Pull concluído!', html:'<pre style="text-align:left;font-size:11px;max-height:200px;overflow:auto;">' + (d.output||'OK') + '</pre>', confirmButtonColor:'#1b3d6e'}).then(function(){ location.reload(); });
                                } else {
                                    Swal.fire({icon:'error', title:'Erro no Pull', html:'<pre style="text-align:left;font-size:11px;">' + (d.output||'') + '</pre>'});
                                }
                            }, 'json');
                        } else if (res.isDenied) {
                            // Force Reset
                            doForceReset(repo, repoName);
                        }
                    });
                    return;
                }

                if (data.success) {
                    Swal.fire({icon:'success', title:'Pull concluído!', html:'<pre style="text-align:left;font-size:11px;max-height:200px;overflow:auto;">' + (data.output||'OK') + '</pre>', confirmButtonColor:'#1b3d6e'}).then(function(){ location.reload(); });
                } else {
                    Swal.fire({icon:'error', title:'Erro no Pull', html:'<pre style="text-align:left;font-size:11px;">' + (data.output||'') + '</pre>'});
                }
            }, 'json').fail(function() {
                btn.prop('disabled', false).html('<i class="fas fa-cloud-arrow-down"></i>');
                Swal.fire({icon:'error', title:'Erro de conexão'});
            });
        });
    });

    // ── Force Reset ───────────────────────────────────────────
    function doForceReset(repo, repoName) {
        Swal.fire({
            icon: 'warning',
            title: 'Forçar Reset?',
            html: '<span class="text-danger"><strong>⚠️ ATENÇÃO: TODAS as alterações locais serão DESCARTADAS!</strong></span><br><br><strong>' + repoName + '</strong> será resetado para a versão do remote.<br><br><small class="text-muted">Isso equivale a <code>git reset --hard origin/branch</code></small>',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-skull-crossbones me-1"></i>Forçar Reset',
            cancelButtonText: 'Cancelar'
        }).then(function(result) {
            if (!result.isConfirmed) return;

            Swal.fire({title:'Resetando...', html:'<i class="fas fa-spinner fa-spin fa-2x"></i>', showConfirmButton:false, allowOutsideClick:false});
            $.post('?page=master_git&action=forceReset', {repo: repo, confirmed: 1}, function(data) {
                if (data.success) {
                    Swal.fire({icon:'success', title:'Reset concluído!', html:'<pre style="text-align:left;font-size:11px;">' + (data.output||'OK') + '</pre>', confirmButtonColor:'#1b3d6e'}).then(function(){ location.reload(); });
                } else {
                    Swal.fire({icon:'error', title:'Erro no Reset', html:'<pre style="text-align:left;font-size:11px;">' + (data.output||'') + '</pre>'});
                }
            }, 'json').fail(function() { Swal.fire({icon:'error', title:'Erro de conexão'}); });
        });
    }

    $(document).on('click', '.btn-force-reset', function() {
        doForceReset($(this).data('repo'), $(this).data('name') || $(this).data('repo'));
    });

    // ── Ver detalhes (commits, branches) ──────────────────────
    $(document).on('click', '.btn-detail', function() {
        var btn = $(this);
        var repo = btn.data('repo');
        var card = $('#detail-' + repo.replace(/[^a-zA-Z0-9_-]/g, '_'));

        if (card.is(':visible')) {
            card.slideUp(200);
            return;
        }

        card.html('<div class="text-center py-3"><i class="fas fa-spinner fa-spin me-2"></i>Carregando...</div>').slideDown(200);

        $.getJSON('?page=master_git&action=detail&repo=' + encodeURIComponent(repo), function(data) {
            if (!data.success) {
                card.html('<div class="alert alert-danger mb-0">' + (data.message||'Erro') + '</div>');
                return;
            }

            var html = '<div class="row g-3">';

            // Commits
            html += '<div class="col-md-7">';
            html += '<h6 class="fw-bold mb-2"><i class="fas fa-code-commit me-1 text-primary"></i>Últimos Commits</h6>';
            html += '<div style="max-height:280px; overflow-y:auto;">';
            if (data.commits && data.commits.length) {
                data.commits.forEach(function(c) {
                    var dateStr = c.date ? new Date(c.date).toLocaleString('pt-BR', {day:'2-digit',month:'2-digit',year:'2-digit',hour:'2-digit',minute:'2-digit'}) : '';
                    html += '<div class="d-flex gap-2 mb-2 pb-2 border-bottom" style="font-size:12px;">';
                    html += '<code class="flex-shrink-0 text-primary" style="font-size:11px;">' + c.hash_short + '</code>';
                    html += '<div class="flex-grow-1" style="min-width:0;"><div class="text-truncate">' + escapeHtml(c.message) + '</div>';
                    html += '<small class="text-muted">' + escapeHtml(c.author) + ' · ' + dateStr + '</small>';
                    if (c.refs) html += ' <small class="text-info">' + escapeHtml(c.refs) + '</small>';
                    html += '</div></div>';
                });
            } else {
                html += '<span class="text-muted">Nenhum commit encontrado</span>';
            }
            html += '</div></div>';

            // Branches + Info
            html += '<div class="col-md-5">';
            html += '<h6 class="fw-bold mb-2"><i class="fas fa-code-branch me-1 text-success"></i>Branches</h6>';
            html += '<div style="max-height:150px; overflow-y:auto;">';
            if (data.branches && data.branches.length) {
                data.branches.forEach(function(b) {
                    var isCurrent = data.info && b.name === data.info.branch;
                    html += '<div class="d-flex align-items-center gap-2 mb-1" style="font-size:12px;">';
                    if (isCurrent) {
                        html += '<span class="badge bg-success" style="font-size:10px;">atual</span>';
                    } else {
                        html += '<span style="width:36px;"></span>';
                    }
                    html += '<code style="font-size:11px;">' + escapeHtml(b.name) + '</code>';
                    html += '</div>';
                });
            }
            html += '</div>';

            // Info extra
            html += '<hr class="my-2">';
            html += '<div style="font-size:12px;">';
            if (data.info.remote_url) html += '<div class="mb-1"><i class="fas fa-link text-muted me-1"></i><a href="' + escapeHtml(data.info.remote_url.replace(/\.git$/, '')) + '" target="_blank" class="text-decoration-none">' + escapeHtml(data.info.remote_url) + '</a></div>';
            if (data.size) html += '<div class="mb-1"><i class="fas fa-hard-drive text-muted me-1"></i>.git: ' + data.size + '</div>';
            if (data.diff) html += '<div class="mb-1"><strong>Diff:</strong><pre style="font-size:10px;background:#1e1e1e;color:#d4d4d4;padding:8px;border-radius:6px;max-height:100px;overflow:auto;margin-top:4px;">' + escapeHtml(data.diff) + '</pre></div>';
            html += '</div>';
            html += '</div>';

            html += '</div>'; // row
            card.html(html);
        }).fail(function() {
            card.html('<div class="alert alert-danger mb-0">Erro de conexão</div>');
        });
    });

    // ── Helper: update repo card in-place ─────────────────────
    function updateRepoCard(repoName, info) {
        var card = $('[data-repo-name="' + repoName + '"]');
        if (!card.length) return;

        // Atualizar hash
        card.find('.repo-hash').text(info.commit_hash_short || '—');
        // Atualizar branch
        card.find('.repo-branch').text(info.branch || '—');
        // Atualizar ahead/behind
        var badge = card.find('.repo-status-badge');
        if (info.status === 'up-to-date') {
            badge.attr('class', 'badge bg-success repo-status-badge').html('<i class="fas fa-check me-1"></i>Atualizado');
        } else if (info.status === 'behind') {
            badge.attr('class', 'badge bg-warning text-dark repo-status-badge').html('<i class="fas fa-arrow-down me-1"></i>' + info.behind + ' atrás');
        } else if (info.status === 'dirty') {
            badge.attr('class', 'badge bg-info repo-status-badge').html('<i class="fas fa-pen me-1"></i>Alterações locais');
        } else if (info.status === 'ahead') {
            badge.attr('class', 'badge bg-primary repo-status-badge').html('<i class="fas fa-arrow-up me-1"></i>' + info.ahead + ' à frente');
        } else if (info.status === 'error') {
            badge.attr('class', 'badge bg-danger repo-status-badge').html('<i class="fas fa-exclamation-triangle me-1"></i>Erro Git');
        }
    }

    function escapeHtml(text) {
        if (!text) return '';
        return $('<div>').text(text).html();
    }

    function escapeAttr(text) {
        if (!text) return '';
        return escapeHtml(text).replace(/"/g, '&quot;');
    }

    // ── Filtro de busca ───────────────────────────────────────
    $('#searchRepos').on('keyup', function() {
        var q = $(this).val().toLowerCase();
        $('.repo-card-item').each(function() {
            var text = $(this).text().toLowerCase();
            $(this).toggle(text.indexOf(q) !== -1);
        });
    });

    // ── Filtro por status ─────────────────────────────────────
    $('#filterStatus').on('change', function() {
        var val = $(this).val();
        $('.repo-card-item').each(function() {
            if (!val) { $(this).show(); return; }
            $(this).toggle($(this).data('status') === val);
        });
    });
});
</script>
SCRIPTS;

?>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($_SESSION['success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<!-- Painel de Diagnóstico (carregado via AJAX) -->
<div id="diagnosticPanel"></div>

<!-- Info do ambiente (carregado via AJAX) -->
<div id="envInfoBar" class="d-flex flex-wrap gap-3 mb-3" style="font-size:11px; color:#6b7280;">
    <span><i class="fas fa-spinner fa-spin me-1"></i>Carregando diagnóstico do ambiente...</span>
</div>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <!-- Placeholders (mostrado enquanto carrega) -->
    <div class="col-xl-3 col-md-6 stat-card-placeholder">
        <div class="stat-card" style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
            <div class="stat-icon"><i class="fas fa-spinner fa-spin"></i></div>
            <div class="stat-value">—</div>
            <div class="stat-label">Repositórios</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 stat-card-placeholder">
        <div class="stat-card" style="background: linear-gradient(135deg, #10b981, #059669);">
            <div class="stat-icon"><i class="fas fa-spinner fa-spin"></i></div>
            <div class="stat-value">—</div>
            <div class="stat-label">Atualizados</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 stat-card-placeholder">
        <div class="stat-card" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
            <div class="stat-icon"><i class="fas fa-spinner fa-spin"></i></div>
            <div class="stat-value">—</div>
            <div class="stat-label">Precisam Pull</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 stat-card-placeholder">
        <div class="stat-card" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">
            <div class="stat-icon"><i class="fas fa-spinner fa-spin"></i></div>
            <div class="stat-value">—</div>
            <div class="stat-label">Alterações Locais</div>
        </div>
    </div>

    <!-- Real stats (escondido até carregar) -->
    <div class="col-xl-3 col-md-6 stat-card-real d-none">
        <div class="stat-card" style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
            <div class="stat-icon"><i class="fas fa-code-branch"></i></div>
            <div class="stat-value" id="statTotal">0</div>
            <div class="stat-label">Repositórios</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 stat-card-real d-none">
        <div class="stat-card" style="background: linear-gradient(135deg, #10b981, #059669);">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-value" id="statUpToDate">0</div>
            <div class="stat-label">Atualizados</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 stat-card-real d-none">
        <div class="stat-card" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
            <div class="stat-icon"><i class="fas fa-arrow-down"></i></div>
            <div class="stat-value" id="statBehind">0</div>
            <div class="stat-label">Precisam Pull</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 stat-card-real d-none">
        <div class="stat-card" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">
            <div class="stat-icon"><i class="fas fa-pen-to-square"></i></div>
            <div class="stat-value" id="statDirty">0</div>
            <div class="stat-label">Alterações Locais</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 stat-card-real d-none" id="statErrorCard">
        <div class="stat-card" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
            <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="stat-value" id="statErrors">0</div>
            <div class="stat-label">Erros Git</div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="input-group">
            <span class="input-group-text" style="background:#f8f9fa; border:2px solid #dee2e6; border-right:none; border-radius:8px 0 0 8px;">
                <i class="fas fa-search text-muted"></i>
            </span>
            <input type="text" class="form-control" id="searchRepos" placeholder="Buscar repositório..."
                   style="border:2px solid #dee2e6; border-left:none; border-radius:0 8px 8px 0; padding:10px 14px;">
        </div>
    </div>
    <div class="col-md-3">
        <select class="form-select" id="filterStatus" style="border:2px solid #dee2e6; border-radius:8px; padding:10px 14px;">
            <option value="">Todos os status</option>
            <option value="up-to-date">✅ Atualizados</option>
            <option value="behind">⚠️ Precisam Pull</option>
            <option value="dirty">✏️ Alterações Locais</option>
            <option value="ahead">⬆️ Commits à frente</option>
            <option value="error">❌ Erros Git</option>
        </select>
    </div>
    <div class="col-md-3 text-end">
        <small class="text-muted">
            <i class="fas fa-folder-open me-1"></i>Base: <code style="font-size:11px;"><?= htmlspecialchars($basePath) ?></code>
        </small>
    </div>
</div>

<!-- Lista de Repositórios (carregado via AJAX) -->
<div id="reposLoading">
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-spinner fa-spin fa-3x mb-3" style="color: #6366f1;"></i>
            <h5 class="text-muted">Carregando repositórios...</h5>
            <p class="text-muted mb-0" style="font-size:13px;">Buscando informações do Git em segundo plano</p>
        </div>
    </div>
</div>
