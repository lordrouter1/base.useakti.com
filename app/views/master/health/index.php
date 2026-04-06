<?php
/**
 * View: Health Check — Status do Sistema
 */
$pageTitle = 'Health Check';
$pageSubtitle = 'Monitoramento em tempo real dos serviços';

$mysql = $health['mysql_master'];
$tenantDbs = $health['tenant_dbs'];
$disk = $health['disk'];
$php = $health['php'];
$nodeApi = $health['node_api'];
$pendingSql = $health['pending_sql'];

$totalTenants = count($tenantDbs);
$tenantsOk = count(array_filter($tenantDbs, fn($t) => $t['status'] === 'ok'));
$tenantsError = $totalTenants - $tenantsOk;

// Overall status
$overallStatus = 'ok';
if ($mysql['status'] !== 'ok' || $tenantsError > 0 || ($disk['status'] ?? '') === 'critical') {
    $overallStatus = 'critical';
} elseif (($disk['status'] ?? '') === 'warning' || ($nodeApi['status'] ?? '') === 'offline' || ($pendingSql['status'] ?? '') === 'warning') {
    $overallStatus = 'warning';
}

$pageScripts = <<<'SCRIPTS'
<script>
$(document).ready(function() {
    // Auto-refresh every 60 seconds
    var refreshInterval = setInterval(function() {
        $.getJSON('?page=master_health&action=statusJson', function(data) {
            if (data.success) {
                $('#lastCheck').text(data.timestamp);
                // Indicators update
                var h = data.health;
                updateStatusBadge('#mysql-status', h.mysql_master.status);
                updateStatusBadge('#disk-status', h.disk.status);
                updateStatusBadge('#node-status', h.node_api.status === 'offline' ? 'error' : h.node_api.status);
                updateStatusBadge('#sql-status', h.pending_sql.status);

                if (h.disk.used_percent !== undefined) {
                    $('#disk-progress').css('width', h.disk.used_percent + '%').text(h.disk.used_percent + '%');
                }
                if (h.mysql_master.latency_ms !== undefined) {
                    $('#mysql-latency').text(h.mysql_master.latency_ms + ' ms');
                }
                if (h.mysql_master.threads !== undefined) {
                    $('#mysql-threads').text(h.mysql_master.threads);
                }
                if (h.pending_sql.count !== undefined) {
                    $('#pending-count').text(h.pending_sql.count);
                }
            }
        });
    }, 60000);

    function updateStatusBadge(selector, status) {
        var $el = $(selector);
        $el.removeClass('bg-success bg-warning bg-danger bg-secondary');
        if (status === 'ok') $el.addClass('bg-success').text('Online');
        else if (status === 'warning') $el.addClass('bg-warning').text('Atenção');
        else if (status === 'critical') $el.addClass('bg-danger').text('Crítico');
        else $el.addClass('bg-danger').text('Offline');
    }
});
</script>
SCRIPTS;
?>

<!-- Overall Status -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card <?= $overallStatus === 'ok' ? '' : ($overallStatus === 'warning' ? 'border-warning' : 'border-danger') ?>">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <?php if ($overallStatus === 'ok'): ?>
                    <i class="fas fa-shield-check fa-3x text-success"></i>
                    <div>
                        <h4 class="mb-0 text-success">Todos os serviços operacionais</h4>
                        <small class="text-muted">Última verificação: <span id="lastCheck"><?= date('Y-m-d H:i:s') ?></span> — Atualiza automaticamente a cada 60s</small>
                    </div>
                <?php elseif ($overallStatus === 'warning'): ?>
                    <i class="fas fa-exclamation-triangle fa-3x text-warning"></i>
                    <div>
                        <h4 class="mb-0 text-warning">Atenção — Alguns serviços precisam de verificação</h4>
                        <small class="text-muted">Última verificação: <span id="lastCheck"><?= date('Y-m-d H:i:s') ?></span></small>
                    </div>
                <?php else: ?>
                    <i class="fas fa-times-circle fa-3x text-danger"></i>
                    <div>
                        <h4 class="mb-0 text-danger">Problemas detectados em serviços críticos</h4>
                        <small class="text-muted">Última verificação: <span id="lastCheck"><?= date('Y-m-d H:i:s') ?></span></small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Service Cards Row 1 -->
<div class="row g-4 mb-4">
    <!-- MySQL Master -->
    <div class="col-xl-3 col-md-6">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="fas fa-database fa-2x mb-2 <?= $mysql['status'] === 'ok' ? 'text-success' : 'text-danger' ?>"></i>
                <h6 class="fw-bold">MySQL Master</h6>
                <span id="mysql-status" class="badge <?= $mysql['status'] === 'ok' ? 'bg-success' : 'bg-danger' ?> mb-2">
                    <?= $mysql['status'] === 'ok' ? 'Online' : 'Offline' ?>
                </span>
                <?php if ($mysql['status'] === 'ok'): ?>
                    <div style="font-size:12px;">
                        <div class="d-flex justify-content-between"><span class="text-muted">Latência:</span><strong id="mysql-latency"><?= $mysql['latency_ms'] ?> ms</strong></div>
                        <div class="d-flex justify-content-between"><span class="text-muted">Versão:</span><strong><?= htmlspecialchars($mysql['version']) ?></strong></div>
                        <div class="d-flex justify-content-between"><span class="text-muted">Threads:</span><strong id="mysql-threads"><?= $mysql['threads'] ?></strong></div>
                        <div class="d-flex justify-content-between"><span class="text-muted">Uptime:</span><strong><?= round($mysql['uptime_s'] / 3600, 1) ?>h</strong></div>
                    </div>
                <?php else: ?>
                    <small class="text-danger"><?= htmlspecialchars($mysql['message'] ?? 'Erro desconhecido') ?></small>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Disk -->
    <div class="col-xl-3 col-md-6">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="fas fa-hard-drive fa-2x mb-2 <?= ($disk['status'] ?? '') === 'ok' ? 'text-success' : (($disk['status'] ?? '') === 'warning' ? 'text-warning' : 'text-danger') ?>"></i>
                <h6 class="fw-bold">Disco</h6>
                <span id="disk-status" class="badge <?= ($disk['status'] ?? '') === 'ok' ? 'bg-success' : (($disk['status'] ?? '') === 'warning' ? 'bg-warning' : 'bg-danger') ?> mb-2">
                    <?= ($disk['status'] ?? '') === 'ok' ? 'OK' : (($disk['status'] ?? '') === 'warning' ? 'Atenção' : 'Crítico') ?>
                </span>
                <?php if (isset($disk['used_percent'])): ?>
                    <div class="progress mb-2" style="height:8px;">
                        <div id="disk-progress" class="progress-bar <?= $disk['used_percent'] > 90 ? 'bg-danger' : ($disk['used_percent'] > 75 ? 'bg-warning' : 'bg-success') ?>" 
                             style="width:<?= $disk['used_percent'] ?>%"><?= $disk['used_percent'] ?>%</div>
                    </div>
                    <div style="font-size:12px;">
                        <div class="d-flex justify-content-between"><span class="text-muted">Usado:</span><strong><?= $disk['used_gb'] ?> GB</strong></div>
                        <div class="d-flex justify-content-between"><span class="text-muted">Livre:</span><strong><?= $disk['free_gb'] ?> GB</strong></div>
                        <div class="d-flex justify-content-between"><span class="text-muted">Total:</span><strong><?= $disk['total_gb'] ?> GB</strong></div>
                    </div>
                <?php else: ?>
                    <small class="text-muted"><?= htmlspecialchars($disk['message'] ?? '') ?></small>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Node.js API -->
    <div class="col-xl-3 col-md-6">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="fab fa-node-js fa-2x mb-2 <?= ($nodeApi['status'] ?? '') === 'ok' ? 'text-success' : 'text-danger' ?>"></i>
                <h6 class="fw-bold">API Node.js</h6>
                <span id="node-status" class="badge <?= ($nodeApi['status'] ?? '') === 'ok' ? 'bg-success' : 'bg-danger' ?> mb-2">
                    <?= ($nodeApi['status'] ?? '') === 'ok' ? 'Online' : 'Offline' ?>
                </span>
                <?php if (($nodeApi['status'] ?? '') === 'ok'): ?>
                    <div style="font-size:12px;">
                        <div class="d-flex justify-content-between"><span class="text-muted">Latência:</span><strong><?= $nodeApi['latency_ms'] ?> ms</strong></div>
                    </div>
                <?php else: ?>
                    <small class="text-muted"><?= htmlspecialchars($nodeApi['message'] ?? 'Sem resposta') ?></small>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Pending SQL -->
    <div class="col-xl-3 col-md-6">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="fas fa-file-code fa-2x mb-2 <?= ($pendingSql['status'] ?? '') === 'ok' ? 'text-success' : 'text-warning' ?>"></i>
                <h6 class="fw-bold">Migrations Pendentes</h6>
                <span id="sql-status" class="badge <?= ($pendingSql['status'] ?? '') === 'ok' ? 'bg-success' : 'bg-warning' ?> mb-2">
                    <?= ($pendingSql['count'] ?? 0) === 0 ? 'Nenhuma' : ($pendingSql['count'] . ' pendente(s)') ?>
                </span>
                <div style="font-size:12px;">
                    <div class="d-flex justify-content-between"><span class="text-muted">Arquivos:</span><strong id="pending-count"><?= $pendingSql['count'] ?? 0 ?></strong></div>
                </div>
                <?php if (!empty($pendingSql['files'])): ?>
                    <div class="mt-2 text-start" style="font-size:11px;">
                        <?php foreach (array_slice($pendingSql['files'], 0, 3) as $f): ?>
                            <div class="text-truncate"><code><?= htmlspecialchars($f) ?></code></div>
                        <?php endforeach; ?>
                        <?php if (count($pendingSql['files']) > 3): ?>
                            <small class="text-muted">+<?= count($pendingSql['files']) - 3 ?> mais...</small>
                        <?php endif; ?>
                    </div>
                    <a href="?page=master_migrations" class="btn btn-sm btn-outline-warning mt-2" style="font-size:11px;">
                        <i class="fas fa-arrow-right me-1"></i>Ir para Migrações
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- PHP Info -->
<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="fab fa-php" style="color: #777BB4; font-size:20px;"></i>
                <strong>PHP</strong>
                <span class="badge bg-success ms-auto">v<?= htmlspecialchars($php['version']) ?></span>
            </div>
            <div class="card-body" style="font-size:13px;">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted">Memória Limite</span>
                            <strong><?= htmlspecialchars($php['memory_limit']) ?></strong>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted">Memória em Uso</span>
                            <strong><?= $php['memory_usage'] ?> MB</strong>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted">Upload Máx</span>
                            <strong><?= htmlspecialchars($php['max_upload']) ?></strong>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted">POST Máx</span>
                            <strong><?= htmlspecialchars($php['max_post']) ?></strong>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted">Exec Máx</span>
                            <strong><?= $php['max_exec_time'] ?>s</strong>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted">OPcache</span>
                            <strong><?= $php['opcache'] ? '<span class="text-success">Ativo</span>' : '<span class="text-muted">Inativo</span>' ?></strong>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <strong class="d-block mb-2" style="font-size:12px;">Extensões</strong>
                    <div class="d-flex flex-wrap gap-1">
                        <?php foreach ($php['extensions'] as $ext => $loaded): ?>
                            <span class="badge <?= $loaded ? 'bg-success' : 'bg-danger' ?>" style="font-size:11px;">
                                <i class="fas <?= $loaded ? 'fa-check' : 'fa-times' ?> me-1"></i><?= $ext ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tenant DBs Status -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-server" style="color: var(--akti-primary);"></i>
                    <strong>Bancos Tenant</strong>
                </div>
                <div class="d-flex gap-2">
                    <span class="badge bg-success"><?= $tenantsOk ?> OK</span>
                    <?php if ($tenantsError > 0): ?>
                        <span class="badge bg-danger"><?= $tenantsError ?> Erro</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush" style="max-height:350px; overflow-y:auto;">
                    <?php foreach ($tenantDbs as $dbName => $info): ?>
                        <div class="list-group-item px-3 py-2 d-flex align-items-center justify-content-between" style="font-size:12px;">
                            <div>
                                <code><?= htmlspecialchars($dbName) ?></code>
                                <?php if (!empty($info['client_name'])): ?>
                                    <br><small class="text-muted"><?= htmlspecialchars($info['client_name']) ?></small>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <?php if ($info['status'] === 'ok'): ?>
                                    <span class="badge bg-success" style="font-size:10px;"><?= $info['latency_ms'] ?>ms</span>
                                    <span class="text-muted" style="font-size:10px;"><?= $info['tables'] ?> tabelas</span>
                                <?php else: ?>
                                    <span class="badge bg-danger" style="font-size:10px;">Erro</span>
                                <?php endif; ?>
                                <?php if (!$info['is_active']): ?>
                                    <span class="badge bg-secondary" style="font-size:9px;">Inativo</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
