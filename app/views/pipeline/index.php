<div class="container-fluid py-3">
    <!-- ═══ Page Header — Clean, Linear-style ═══ -->
    <div class="pipeline-page-header">
        <div>
            <h1><i class="fas fa-stream me-2"></i>Linha de Produção</h1>
            <small class="text-muted" style="font-size:0.72rem;"><i class="fas fa-calendar-alt me-1"></i><?= date('d/m/Y H:i') ?></small>
        </div>
        <div class="pipeline-header-actions">
            <div class="input-group input-group-sm" style="max-width:220px;">
                <span class="input-group-text bg-transparent border-end-0"><i class="fas fa-search text-muted" style="font-size:0.75rem;"></i></span>
                <input type="text" class="form-control border-start-0 ps-0" id="pipelineSearch" placeholder="Buscar pedido ou cliente..." style="font-size:0.78rem;">
            </div>
            <select class="form-select form-select-sm" id="pipelinePriorityFilter" style="max-width:140px;font-size:0.78rem;">
                <option value="">Prioridade</option>
                <option value="urgente">🔴 Urgente</option>
                <option value="alta">🟡 Alta</option>
                <option value="normal">🔵 Normal</option>
                <option value="baixa">⚪ Baixa</option>
            </select>
            <?php if(!empty($delayedOrders)): ?>
            <button class="btn btn-sm btn-danger btn-delayed-alert" data-bs-toggle="modal" data-bs-target="#delayedModal">
                <i class="fas fa-exclamation-triangle me-1"></i> <?= count($delayedOrders) ?> Atrasado<?= count($delayedOrders) > 1 ? 's' : '' ?>
            </button>
            <?php endif; ?>
            <a href="?page=pipeline&action=settings" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-sliders-h me-1"></i> Metas
            </a>
            <a href="?page=orders&action=create" class="btn btn-sm btn-primary">
                <i class="fas fa-plus me-1"></i> Novo Pedido
            </a>
        </div>
    </div>

    <?php if(!empty($delayedOrders)): ?>
    <!-- ═══ Delayed Orders Alert Banner ═══ -->
    <div class="pipeline-delayed-banner">
        <i class="fas fa-exclamation-triangle"></i>
        <div>
            <strong><?= count($delayedOrders) ?> pedido<?= count($delayedOrders) > 1 ? 's' : '' ?></strong> 
            ultrapassaram a meta de tempo. 
            <a href="#" data-bs-toggle="modal" data-bs-target="#delayedModal" class="text-danger fw-bold text-decoration-underline">Ver detalhes →</a>
        </div>
    </div>
    <?php endif; ?>

    <!-- ═══ KPI Metric Widgets ═══ -->
    <div class="pipeline-metrics">
        <div class="pipeline-metric-card metric-active">
            <div class="pipeline-metric-icon icon-active">
                <i class="fas fa-layer-group"></i>
            </div>
            <div class="pipeline-metric-data">
                <div class="pipeline-metric-label">Pedidos Ativos</div>
                <div class="pipeline-metric-value"><?= $stats['total_active'] ?></div>
                <div class="pipeline-metric-sub">em andamento agora</div>
            </div>
        </div>
        <div class="pipeline-metric-card metric-delayed" <?= $stats['total_delayed'] > 0 ? 'style="cursor:pointer;" data-bs-toggle="modal" data-bs-target="#delayedModal"' : '' ?>>
            <div class="pipeline-metric-icon icon-delayed">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <div class="pipeline-metric-data">
                <div class="pipeline-metric-label">Atrasados</div>
                <div class="pipeline-metric-value <?= $stats['total_delayed'] > 0 ? 'text-danger' : '' ?>"><?= $stats['total_delayed'] ?></div>
                <div class="pipeline-metric-sub"><?= $stats['total_delayed'] > 0 ? 'ação necessária' : 'tudo em dia ✓' ?></div>
            </div>
        </div>
        <div class="pipeline-metric-card metric-completed">
            <div class="pipeline-metric-icon icon-completed">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="pipeline-metric-data">
                <div class="pipeline-metric-label">Concluídos</div>
                <div class="pipeline-metric-value"><?= $stats['completed_month'] ?></div>
                <div class="pipeline-metric-sub">neste mês</div>
            </div>
        </div>
        <div class="pipeline-metric-card metric-value">
            <div class="pipeline-metric-icon icon-value">
                <i class="fas fa-coins"></i>
            </div>
            <div class="pipeline-metric-data">
                <div class="pipeline-metric-label">Valor em Aberto</div>
                <div class="pipeline-metric-value" style="font-size:1.2rem;">R$ <?= number_format($stats['total_value'], 2, ',', '.') ?></div>
                <div class="pipeline-metric-sub">total dos ativos</div>
            </div>
        </div>
    </div>

    <!-- ═══ Pipeline Kanban Board ═══ -->
    <?php
    // Filtra etapas por permissão do grupo do usuário
    $isAdminPipeline = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');
    $allowedStages = [];
    if (!$isAdminPipeline && isset($_SESSION['user_id'])) {
        $dbPerm = (new Database())->getConnection();
        if (!empty($_SESSION['group_id'])) {
            $stmtPerm = $dbPerm->prepare("SELECT page_name FROM group_permissions WHERE group_id = :gid AND page_name LIKE 'stage_%'");
            $stmtPerm->bindParam(':gid', $_SESSION['group_id']);
            $stmtPerm->execute();
            $stagePerms = $stmtPerm->fetchAll(PDO::FETCH_COLUMN);
            foreach ($stagePerms as $sp) {
                $allowedStages[] = str_replace('stage_', '', $sp);
            }
        }
        if (empty($allowedStages)) {
            $allowedStages = array_keys($stages);
        }
    } else {
        $allowedStages = array_keys($stages);
    }
    ?>
    <div class="pipeline-board-wrapper position-relative" id="pipelineBoardWrapper">
        <!-- Navigation buttons -->
        <button class="pipeline-nav-btn nav-left" id="pipelineNavLeft" title="Rolar para esquerda"><i class="fas fa-chevron-left"></i></button>
        <button class="pipeline-nav-btn nav-right" id="pipelineNavRight" title="Rolar para direita"><i class="fas fa-chevron-right"></i></button>

        <div class="pipeline-board d-flex pb-3" id="pipelineBoard" style="min-height: 500px;">
            <?php 
            $visibleStageCount = 0;
            $visibleStages = [];
            foreach ($stages as $sk => $si) {
                if ($sk === 'concluido' || $sk === 'cancelado') continue;
                if (!in_array($sk, $allowedStages)) continue;
                $visibleStageCount++;
                $visibleStages[$sk] = $si;
            }
            ?>
            <?php foreach ($stages as $stageKey => $stageInfo): ?>
            <?php 
                if ($stageKey === 'concluido' || $stageKey === 'cancelado') continue;
                if (!in_array($stageKey, $allowedStages)) continue;
                $stageOrders = $ordersByStage[$stageKey] ?? [];
                $stageGoal = isset($goals[$stageKey]) ? (int)$goals[$stageKey]['max_hours'] : 24;
                // Calcular quantos atrasados na coluna
                $delayedInCol = 0;
                foreach ($stageOrders as $_o) {
                    $h = (int)$_o['hours_in_stage'];
                    if ($stageGoal > 0 && $h > $stageGoal) $delayedInCol++;
                }
            ?>
            <div class="pipeline-column" data-stage-key="<?= $stageKey ?>">
                <!-- Cabeçalho da Coluna -->
                <div class="pipeline-column-header rounded-top p-2 px-3 d-flex align-items-center justify-content-between" 
                     style="background: <?= $stageInfo['color'] ?>; color: #fff;">
                    <div class="d-flex align-items-center" style="min-width:0;">
                        <i class="<?= $stageInfo['icon'] ?> me-2" style="font-size:0.85rem;flex-shrink:0;"></i>
                        <span class="fw-bold" style="font-size:0.78rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= $stageInfo['label'] ?></span>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <?php if ($delayedInCol > 0): ?>
                        <span class="badge bg-danger rounded-pill" style="font-size:0.6rem;" title="<?= $delayedInCol ?> atrasado(s)">
                            <i class="fas fa-exclamation-triangle"></i> <?= $delayedInCol ?>
                        </span>
                        <?php endif; ?>
                        <span class="badge bg-white rounded-pill" style="color: <?= $stageInfo['color'] ?>;"><?= count($stageOrders) ?></span>
                    </div>
                </div>
                
                <!-- Meta de tempo + micro progress -->
                <div class="pipeline-stage-meta">
                    <span><i class="fas fa-clock me-1"></i>Meta: <?= $stageGoal ?>h</span>
                    <?php 
                    // Micro progress: % dos pedidos dentro da meta
                    $inGoal = count($stageOrders) > 0 ? count($stageOrders) - $delayedInCol : 0;
                    $goalPct = count($stageOrders) > 0 ? round(($inGoal / count($stageOrders)) * 100) : 100;
                    ?>
                    <div class="d-flex align-items-center gap-1">
                        <div class="goal-bar">
                            <div class="goal-bar-fill" style="width:<?= $goalPct ?>%;background:<?= $goalPct === 100 ? 'var(--success-color)' : ($goalPct >= 50 ? 'var(--warning-color)' : 'var(--danger-color)') ?>;"></div>
                        </div>
                        <span style="font-size:0.6rem;"><?= $goalPct ?>%</span>
                    </div>
                </div>

                <!-- Cards dos Pedidos (droppable zone) -->
                <div class="pipeline-column-body pipeline-dropzone" 
                     style="min-height: 400px; max-height: 70vh; overflow-y: auto;"
                     data-stage="<?= $stageKey ?>">
                    
                    <?php if (empty($stageOrders)): ?>
                        <div class="pipeline-empty-state">
                            <i class="fas fa-inbox"></i>
                            Nenhum pedido
                        </div>
                    <?php endif; ?>
                    <?php foreach ($stageOrders as $order): ?>
                    <?php
                        $hoursInStage = (int)$order['hours_in_stage'];
                        $isDelayed = ($stageGoal > 0 && $hoursInStage > $stageGoal);
                        $delayHours = $isDelayed ? $hoursInStage - $stageGoal : 0;
                        $priority = $order['priority'] ?? 'normal';
                        // Timer percentage (how much of goal used)
                        $timerPct = $stageGoal > 0 ? min(100, round(($hoursInStage / $stageGoal) * 100)) : 0;
                        $timerClass = $timerPct <= 60 ? 'timer-ok' : ($timerPct <= 90 ? 'timer-warn' : 'timer-danger');
                    ?>
                    <div class="pipeline-card card border-0 shadow-sm mb-2 <?= $isDelayed ? 'pipeline-card-delayed' : '' ?>" 
                         data-order-id="<?= $order['id'] ?>" 
                         data-priority="<?= $priority ?>"
                         data-customer="<?= strtolower(e($order['customer_name'] ?? '')) ?>">
                        <div class="card-body">
                            <!-- Header: Order # + Priority (clickable toggle) -->
                            <div class="pipeline-card-header pipeline-card-toggle" role="button" title="Clique para expandir">
                                <span class="pipeline-card-order-id">#<?= str_pad($order['id'], 4, '0', STR_PAD_LEFT) ?></span>
                                <div class="d-flex align-items-center gap-1">
                                    <span class="badge-priority badge-priority-<?= $priority ?>"><?= ucfirst($priority) ?></span>
                                    <i class="fas fa-chevron-down pipeline-card-chevron"></i>
                                </div>
                            </div>
                            
                            <!-- Collapsible details -->
                            <div class="pipeline-card-details">
                                <!-- Customer name -->
                                <div class="pipeline-card-customer">
                                    <i class="fas fa-user"></i>
                                    <span><?= e($order['customer_name'] ?? 'Cliente removido') ?></span>
                                </div>

                                <!-- Time progress bar -->
                                <div class="pipeline-card-timer" title="<?= $timerPct ?>% da meta (<?= $hoursInStage ?>h / <?= $stageGoal ?>h)">
                                    <div class="pipeline-card-timer-fill <?= $timerClass ?>" style="width:<?= $timerPct ?>%;"></div>
                                </div>

                                <!-- Time label -->
                                <div class="pipeline-card-time <?= $isDelayed ? 'is-delayed' : '' ?>">
                                    <i class="fas fa-clock"></i>
                                    <span>
                                        <?php if ($hoursInStage < 24): ?>
                                            <?= $hoursInStage ?>h
                                        <?php else: ?>
                                            <?= floor($hoursInStage / 24) ?>d <?= $hoursInStage % 24 ?>h
                                        <?php endif; ?>
                                    </span>
                                    <?php if ($isDelayed): ?>
                                        <span class="delay-badge">+<?= $delayHours ?>h</span>
                                    <?php endif; ?>
                                </div>

                                <!-- Info chips -->
                                <div class="pipeline-card-chips">
                                    <?php if (!empty($order['total_amount']) && (float)$order['total_amount'] > 0): ?>
                                    <span class="pipeline-card-chip chip-value">
                                        <i class="fas fa-coins"></i> R$ <?= number_format((float)$order['total_amount'], 2, ',', '.') ?>
                                    </span>
                                    <?php endif; ?>
                                    <?php if (!empty($order['assigned_name'])): ?>
                                    <span class="pipeline-card-chip chip-assigned">
                                        <i class="fas fa-user-check"></i> <?= e($order['assigned_name']) ?>
                                    </span>
                                    <?php endif; ?>
                                    <?php if (!empty($order['deadline'])): ?>
                                    <span class="pipeline-card-chip chip-date">
                                        <i class="fas fa-calendar"></i> <?= date('d/m', strtotime($order['deadline'])) ?>
                                    </span>
                                    <?php endif; ?>
                                    <?php
                                        $cardApproval = $order['customer_approval_status'] ?? null;
                                        if ($cardApproval === 'aprovado'):
                                    ?>
                                    <span class="pipeline-card-chip chip-approved">
                                        <i class="fas fa-user-check"></i> Aprovado
                                    </span>
                                    <?php elseif ($cardApproval === 'pendente'): ?>
                                    <span class="pipeline-card-chip chip-pending">
                                        <i class="fas fa-hourglass-half"></i> Aguard. Aprov.
                                    </span>
                                    <?php elseif ($cardApproval === 'recusado'): ?>
                                    <span class="pipeline-card-chip chip-rejected">
                                        <i class="fas fa-user-times"></i> Recusado
                                    </span>
                                    <?php endif; ?>
                                    <?php
                                        // ═══ Badge NF-e — Integração Pipeline × Fiscal ═══
                                        $nfeStatus = $order['nfe_status'] ?? $order['nf_status'] ?? null;
                                        if ($nfeStatus):
                                            $_nfeBadge = [
                                                'autorizada' => ['bg' => '#d4edda', 'color' => '#155724', 'icon' => 'fas fa-check-circle', 'label' => 'NF-e OK'],
                                                'emitida'    => ['bg' => '#d4edda', 'color' => '#155724', 'icon' => 'fas fa-check-circle', 'label' => 'NF-e OK'],
                                                'processando'=> ['bg' => '#d1ecf1', 'color' => '#0c5460', 'icon' => 'fas fa-spinner fa-spin', 'label' => 'NF-e...'],
                                                'rejeitada'  => ['bg' => '#f8d7da', 'color' => '#721c24', 'icon' => 'fas fa-times-circle', 'label' => 'NF-e Rej.'],
                                                'cancelada'  => ['bg' => '#e2e3e5', 'color' => '#383d41', 'icon' => 'fas fa-ban', 'label' => 'NF-e Canc.'],
                                            ];
                                            $_nb = $_nfeBadge[$nfeStatus] ?? ['bg' => '#e2e3e5', 'color' => '#383d41', 'icon' => 'fas fa-file-invoice', 'label' => 'NF-e'];
                                    ?>
                                    <span class="pipeline-card-chip" style="background:<?= $_nb['bg'] ?>;color:<?= $_nb['color'] ?>;">
                                        <i class="<?= $_nb['icon'] ?>"></i> <?= $_nb['label'] ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Footer: view button -->
                        <div class="card-footer bg-transparent text-center">
                            <a href="?page=pipeline&action=detail&id=<?= $order['id'] ?>" 
                               class="btn btn-sm btn-outline-primary w-100 py-1" style="font-size:0.72rem;border-radius:var(--radius-sm);">
                                <i class="fas fa-eye me-1"></i> Ver Pedido
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Column Minimap / Quick Navigator -->
        <div class="pipeline-minimap" id="pipelineMinimap">
            <?php foreach ($visibleStages as $mKey => $mInfo): 
                $mCount = count($ordersByStage[$mKey] ?? []);
            ?>
            <span class="pipeline-minimap-item" style="background:<?= $mInfo['color'] ?>;" data-target="<?= $mKey ?>" title="<?= $mInfo['label'] ?>">
                <i class="<?= $mInfo['icon'] ?>"></i>
                <?= $mInfo['label'] ?>
                <span class="minimap-count"><?= $mCount ?></span>
            </span>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Modal de Pedidos Atrasados -->
<div class="modal fade" id="delayedModal" tabindex="-1" aria-labelledby="delayedModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header card-header-nfe-danger">
                <h5 class="modal-title" id="delayedModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>Pedidos Atrasados (<?= count($delayedOrders) ?>)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-3" style="font-size:0.78rem;">Pedido</th>
                            <th style="font-size:0.78rem;">Cliente</th>
                            <th style="font-size:0.78rem;">Etapa</th>
                            <th class="text-center" style="font-size:0.78rem;">Meta</th>
                            <th class="text-center" style="font-size:0.78rem;">Tempo Real</th>
                            <th class="text-center" style="font-size:0.78rem;">Atraso</th>
                            <th class="text-end pe-3" style="font-size:0.78rem;">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($delayedOrders as $dOrder): ?>
                        <tr>
                            <td class="ps-3 fw-bold" style="font-size:0.82rem;">#<?= str_pad($dOrder['id'], 4, '0', STR_PAD_LEFT) ?></td>
                            <td style="font-size:0.82rem;"><?= e($dOrder['customer_name'] ?? '—') ?></td>
                            <td>
                                <?php $dStage = $dOrder['pipeline_stage'] ?? 'contato'; ?>
                                <span class="badge" style="background:<?= $stages[$dStage]['color'] ?? '#999' ?>;font-size:0.7rem;">
                                    <i class="<?= $stages[$dStage]['icon'] ?? 'fas fa-circle' ?> me-1"></i>
                                    <?= $stages[$dStage]['label'] ?? $dStage ?>
                                </span>
                            </td>
                            <td class="text-center" style="font-size:0.82rem;"><?= $dOrder['max_hours'] ?>h</td>
                            <td class="text-center text-danger fw-bold" style="font-size:0.82rem;">
                                <?php $h = (int)$dOrder['hours_in_stage']; ?>
                                <?= ($h >= 24) ? floor($h/24).'d '.($h%24).'h' : $h.'h' ?>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-danger rounded-pill" style="font-size:0.7rem;">+<?= $dOrder['delay_hours'] ?>h</span>
                            </td>
                            <td class="text-end pe-3">
                                <a href="?page=pipeline&action=detail&id=<?= $dOrder['id'] ?>" class="btn btn-sm btn-outline-primary" style="font-size:0.72rem;">
                                    <i class="fas fa-eye me-1"></i> Ver
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if(isset($_GET['status'])): ?>
    if (window.history.replaceState) { const url = new URL(window.location); url.searchParams.delete('status'); window.history.replaceState({}, '', url); }
    <?php endif; ?>
    <?php if(isset($_GET['status']) && $_GET['status'] == 'moved'): ?>
    Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2000, timerProgressBar: true })
        .fire({ icon: 'success', title: 'Pedido movido com sucesso!' });
    <?php endif; ?>

    <?php if(isset($_GET['status']) && $_GET['status'] == 'success'): ?>
    Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2000, timerProgressBar: true })
        .fire({ icon: 'success', title: 'Operação realizada!' });
    <?php endif; ?>

    // ── Alerta automático de atrasados ──
    <?php if(count($delayedOrders) > 0): ?>
    Swal.fire({
        title: '<strong class="fs-3">Atenção!</strong>',
        toast: true,
        position: 'bottom-end',
        html: '<small><b><?= count($delayedOrders) ?></b> pedido(s) estão <strong class="text-light">atrasados</strong>!</small>',
        showCancelButton: false,
        confirmButtonText: '<span class="text-red"><i class="fas fa-eye me-1"></i> Ver Detalhes</span>',
        confirmButtonColor: '#ffffff',
        background: '#ef4444',
        color:'#ffffff',
        timer: 5000,
        timerProgressBar: true,
        customClass:{ popup: 'shadow' }
    }).then(function(result) {
        if (result.isConfirmed) {
            var modal = new bootstrap.Modal(document.getElementById('delayedModal'));
            modal.show();
        }
    });
    <?php endif; ?>

    // ══════════════════════════════════════════
    // ══ Search & Filter (client-side)       ══
    // ══════════════════════════════════════════
    var searchInput = document.getElementById('pipelineSearch');
    var priorityFilter = document.getElementById('pipelinePriorityFilter');

    function applyFilters() {
        var query = (searchInput ? searchInput.value : '').toLowerCase().trim();
        var prio = priorityFilter ? priorityFilter.value : '';

        document.querySelectorAll('.pipeline-card').forEach(function(card) {
            var orderId = (card.dataset.orderId || '').toLowerCase();
            var customer = (card.dataset.customer || '').toLowerCase();
            var cardPrio = card.dataset.priority || '';

            var matchSearch = !query || orderId.indexOf(query) !== -1 || customer.indexOf(query) !== -1;
            var matchPrio = !prio || cardPrio === prio;

            card.style.display = (matchSearch && matchPrio) ? '' : 'none';
        });

        // Update column counts after filter
        document.querySelectorAll('.pipeline-column').forEach(function(col) {
            var badge = col.querySelector('.pipeline-column-header .badge.bg-white');
            var cards = col.querySelectorAll('.pipeline-card:not([style*="display: none"])');
            if (badge) badge.textContent = cards.length;

            // Show/hide empty state
            var emptyState = col.querySelector('.pipeline-empty-state');
            var allCards = col.querySelectorAll('.pipeline-card');
            var visibleCards = col.querySelectorAll('.pipeline-card:not([style*="display: none"])');
            if (visibleCards.length === 0 && allCards.length > 0) {
                if (!emptyState) {
                    emptyState = document.createElement('div');
                    emptyState.className = 'pipeline-empty-state';
                    emptyState.innerHTML = '<i class="fas fa-filter"></i>Nenhum resultado';
                    col.querySelector('.pipeline-dropzone').appendChild(emptyState);
                } else {
                    emptyState.style.display = '';
                }
            } else if (emptyState && visibleCards.length > 0) {
                emptyState.style.display = 'none';
            }
        });
    }

    if (searchInput) {
        var searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(applyFilters, 200);
        });
    }
    if (priorityFilter) {
        priorityFilter.addEventListener('change', applyFilters);
    }

    // ── Pipeline Scroll Navigation ──
    (function initPipelineScroll() {
        const wrapper = document.getElementById('pipelineBoardWrapper');
        const board = document.getElementById('pipelineBoard');
        const navLeft = document.getElementById('pipelineNavLeft');
        const navRight = document.getElementById('pipelineNavRight');
        if (!wrapper || !board) return;

        function checkScroll() {
            const hasScroll = wrapper.scrollWidth > wrapper.clientWidth + 2;
            wrapper.classList.toggle('has-scroll', hasScroll);
            if (navLeft) navLeft.style.opacity = wrapper.scrollLeft > 10 ? '1' : '0.3';
            if (navRight) navRight.style.opacity = (wrapper.scrollLeft + wrapper.clientWidth < wrapper.scrollWidth - 10) ? '1' : '0.3';
        }

        if (navLeft) navLeft.addEventListener('click', () => { wrapper.scrollBy({ left: -250, behavior: 'smooth' }); });
        if (navRight) navRight.addEventListener('click', () => { wrapper.scrollBy({ left: 250, behavior: 'smooth' }); });

        wrapper.addEventListener('scroll', checkScroll);
        window.addEventListener('resize', checkScroll);
        checkScroll();

        // Minimap click-to-scroll
        document.querySelectorAll('.pipeline-minimap-item').forEach(item => {
            item.addEventListener('click', function() {
                const targetStage = this.dataset.target;
                const col = board.querySelector(`.pipeline-column[data-stage-key="${targetStage}"]`);
                if (col) {
                    col.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
                }
            });
        });
    })();

    // ── Drag-and-Drop com SortableJS ──
    // Zonas de estoque: pré-produção vs produção+
    const preProductionStages = ['contato', 'orcamento', 'venda'];
    const productionStages = ['producao', 'preparacao', 'envio', 'financeiro', 'concluido'];

    function needsWarehouseSelection(fromStage, toStage) {
        return preProductionStages.includes(fromStage) && productionStages.includes(toStage);
    }

    function needsStockReturn(fromStage, toStage) {
        return productionStages.includes(fromStage) && preProductionStages.includes(toStage);
    }

    // ── CSRF token para fetch (não é coberto pelo $.ajaxSetup do jQuery) ──
    const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfTokenValue = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';

    function performMoveAjax(orderId, newStage, warehouseId, evtItem, evtFrom) {
        const formData = new FormData();
        formData.append('order_id', orderId);
        formData.append('stage', newStage);
        formData.append('csrf_token', csrfTokenValue);
        if (warehouseId) formData.append('warehouse_id', warehouseId);

        return fetch('?page=pipeline&action=moveAjax', {
            method: 'POST',
            body: formData
        })
        .then(r => {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(data => {
            if (data.success) {
                evtItem.classList.add('pipeline-card-moved');
                setTimeout(() => evtItem.classList.remove('pipeline-card-moved'), 1500);
                updateColumnCounts();
                if (data.stock_notes) {
                    Swal.fire({ icon: 'info', title: 'Estoque atualizado', text: data.stock_notes, timer: 3000, showConfirmButton: false });
                }
            } else if (data.needs_warehouse) {
                // Precisa selecionar armazém — mostrar modal
                showWarehouseSelectionModal(orderId, newStage, evtItem, evtFrom);
            } else if (data.blocked_by_paid) {
                // Bloqueado por parcelas pagas — alerta específico
                Swal.fire({
                    icon: 'error',
                    title: '<i class="fas fa-lock me-2"></i>Movimentação bloqueada',
                    html: '<p>' + (data.message || 'Existem parcelas já pagas.') + '</p>'
                        + '<p class="small text-muted mt-2">Estorne todos os pagamentos primeiro no módulo <strong>Financeiro</strong>.</p>',
                    confirmButtonText: '<i class="fas fa-external-link-alt me-1"></i> Ir para Financeiro',
                    showCancelButton: true,
                    cancelButtonText: 'Fechar',
                    confirmButtonColor: '#e74c3c'
                }).then(function(r) {
                    if (r.isConfirmed) {
                        window.open('?page=financial&action=payments', '_blank');
                    }
                });
                revertCard(evtItem, evtFrom);
            } else {
                Swal.fire({ icon: 'error', title: 'Erro', text: data.message || 'Não foi possível mover o pedido.', timer: 3000 });
                revertCard(evtItem, evtFrom);
            }
        })
        .catch((err) => {
            console.error('moveAjax error:', err);
            Swal.fire({ icon: 'error', title: 'Erro', text: 'Erro de conexão ao mover pedido. ' + (err.message || ''), timer: 4000 });
            revertCard(evtItem, evtFrom);
        });
    }

    function revertCard(item, fromZone) {
        fromZone.appendChild(item);
        updateColumnCounts();
        refreshEmptyStates();
    }

    function refreshEmptyStates() {
        document.querySelectorAll('.pipeline-dropzone').forEach(function(dz) {
            var cards = dz.querySelectorAll('.pipeline-card');
            var emptyMsg = dz.querySelector('.pipeline-empty-state');
            if (cards.length === 0) {
                if (!emptyMsg) {
                    emptyMsg = document.createElement('div');
                    emptyMsg.className = 'pipeline-empty-state text-center text-muted py-4 small';
                    emptyMsg.innerHTML = '<i class="fas fa-inbox d-block mb-2" style="font-size: 1.5rem;"></i>Nenhum pedido';
                    dz.appendChild(emptyMsg);
                } else {
                    emptyMsg.style.display = '';
                }
            } else if (emptyMsg) {
                emptyMsg.style.display = 'none';
            }
        });
    }

    function showWarehouseSelectionModal(orderId, newStage, evtItem, evtFrom) {
        // Buscar dados de estoque do pedido
        Swal.fire({
            title: '<i class="fas fa-warehouse me-2"></i>Selecionar Armazém',
            html: '<div class="text-center py-3"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i><br><small class="text-muted mt-2 d-block">Verificando estoque...</small></div>',
            showConfirmButton: false,
            showCancelButton: false,
            allowOutsideClick: false,
            didOpen: () => {
                fetch(`?page=pipeline&action=checkOrderStock&order_id=${orderId}`)
                    .then(r => r.json())
                    .then(data => {
                        if (!data.success) {
                            Swal.fire({ icon: 'error', title: 'Erro', text: data.message || 'Erro ao verificar estoque.' });
                            revertCard(evtItem, evtFrom);
                            return;
                        }

                        let warehouseOptions = '';
                        if (data.warehouses && data.warehouses.length > 0) {
                            data.warehouses.forEach(w => {
                                const isDefault = (w.id == data.default_warehouse_id);
                                const selected = isDefault ? 'selected' : '';
                                const badge = isDefault ? ' ★ Padrão' : '';
                                warehouseOptions += `<option value="${w.id}" ${selected}>${w.name}${badge}</option>`;
                            });
                        }

                        let hasStockItems = false;
                        let itemsHtml = '';
                        if (data.items) {
                            data.items.forEach(item => {
                                if (item.use_stock_control) {
                                    hasStockItems = true;
                                    const icon = item.sufficient ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-exclamation-triangle text-danger"></i>';
                                    const label = item.combination_label ? `${item.product_name} — ${item.combination_label}` : item.product_name;
                                    const cls = item.sufficient ? 'text-success' : 'text-danger fw-bold';
                                    itemsHtml += `<tr><td class="small">${icon} ${label}</td><td class="text-center small">${item.quantity}</td><td class="text-center small ${cls}">${item.stock_available}</td></tr>`;
                                }
                            });
                        }

                        let html = `<p class="mb-2 small text-muted">O pedido está saindo da área comercial para produção. O estoque será deduzido automaticamente.</p>`;
                        if (warehouseOptions) {
                            html += `<div class="mb-3 text-start"><label class="form-label small fw-bold"><i class="fas fa-warehouse me-1"></i>Armazém:</label>
                                <select id="swalWarehouseSelect" class="form-select form-select-sm">${warehouseOptions}</select></div>`;
                        }
                        if (hasStockItems) {
                            html += `<table class="table table-sm table-bordered mb-1" style="font-size:0.8rem;">
                                <thead class="table-light"><tr><th>Produto</th><th class="text-center">Necessário</th><th class="text-center">Disponível</th></tr></thead>
                                <tbody id="swalStockTableBody">${itemsHtml}</tbody></table>`;
                            if (!data.all_from_stock) {
                                html += `<div class="alert alert-warning py-1 small mb-0"><i class="fas fa-exclamation-triangle me-1"></i><small>Alguns itens não possuem estoque suficiente.</small></div>`;
                            }
                        } else {
                            html += `<div class="alert alert-light py-1 small mb-0"><i class="fas fa-info-circle me-1"></i><small>Nenhum item com controle de estoque ativo.</small></div>`;
                        }

                        Swal.fire({
                            title: '<i class="fas fa-warehouse me-2"></i>Selecionar Armazém',
                            html: html,
                            showCancelButton: true,
                            confirmButtonText: '<i class="fas fa-check me-1"></i> Confirmar',
                            cancelButtonText: 'Cancelar',
                            confirmButtonColor: '#27ae60',
                            width: hasStockItems ? '500px' : undefined,
                            preConfirm: () => {
                                const whSelect = document.getElementById('swalWarehouseSelect');
                                return whSelect ? whSelect.value : null;
                            }
                        }).then((result) => {
                            if (result.isConfirmed && result.value) {
                                performMoveAjax(orderId, newStage, result.value, evtItem, evtFrom);
                            } else {
                                // Cancelou — reverter card
                                revertCard(evtItem, evtFrom);
                            }
                        });

                        // Atualizar estoque ao mudar armazém no select
                        setTimeout(() => {
                            const whSelect = document.getElementById('swalWarehouseSelect');
                            if (whSelect) {
                                whSelect.addEventListener('change', function() {
                                    fetch(`?page=pipeline&action=checkOrderStock&order_id=${orderId}&warehouse_id=${this.value}`)
                                        .then(r => r.json())
                                        .then(d => {
                                            if (d.success && d.items) {
                                                const tbody = document.getElementById('swalStockTableBody');
                                                if (tbody) {
                                                    let rows = '';
                                                    d.items.forEach(item => {
                                                        if (item.use_stock_control) {
                                                            const ic = item.sufficient ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-exclamation-triangle text-danger"></i>';
                                                            const lb = item.combination_label ? `${item.product_name} — ${item.combination_label}` : item.product_name;
                                                            const cl = item.sufficient ? 'text-success' : 'text-danger fw-bold';
                                                            rows += `<tr><td class="small">${ic} ${lb}</td><td class="text-center small">${item.quantity}</td><td class="text-center small ${cl}">${item.stock_available}</td></tr>`;
                                                        }
                                                    });
                                                    tbody.innerHTML = rows;
                                                }
                                            }
                                        });
                                });
                            }
                        }, 100);
                    })
                    .catch(() => {
                        Swal.fire({ icon: 'error', title: 'Erro', text: 'Não foi possível verificar o estoque.' });
                        revertCard(evtItem, evtFrom);
                    });
            }
        });
    }

    (function initDragAndDrop() {
        const dropzones = document.querySelectorAll('.pipeline-dropzone');
        
        dropzones.forEach(zone => {
            new Sortable(zone, {
                group: 'pipeline-orders',
                animation: 200,
                ghostClass: 'pipeline-card-ghost',
                chosenClass: 'pipeline-card-chosen',
                dragClass: 'pipeline-card-dragging',
                handle: '.card-body',
                filter: '.pipeline-empty-state, a',
                preventOnFilter: false,
                delay: 120,
                delayOnTouchOnly: true,
                fallbackOnBody: true,
                swapThreshold: 0.65,
                onStart: function(evt) {
                    document.body.classList.add('pipeline-dragging');
                    document.querySelectorAll('.pipeline-empty-state').forEach(el => el.style.display = 'none');
                },
                onEnd: function(evt) {
                    document.body.classList.remove('pipeline-dragging');
                    // Atualiza estados vazios de todas as colunas
                    refreshEmptyStates();

                    const orderId = evt.item.dataset.orderId;
                    const newStage = evt.to.dataset.stage;
                    const oldStage = evt.from.dataset.stage;

                    if (newStage === oldStage) return;

                    updateColumnCounts();

                    // Se precisa de armazém, mostrar modal antes
                    if (needsWarehouseSelection(oldStage, newStage)) {
                        showWarehouseSelectionModal(orderId, newStage, evt.item, evt.from);
                    } else if (needsStockReturn(oldStage, newStage)) {
                        // Produção+ → Pré-produção: confirmar devolução de estoque
                        Swal.fire({
                            title: '<i class="fas fa-undo me-2 text-warning"></i>Devolver ao estoque?',
                            html: '<p>Ao retornar o pedido para a área comercial, os produtos deduzidos do estoque serão <strong>devolvidos automaticamente</strong> ao armazém.</p>',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: '<i class="fas fa-check me-1"></i> Confirmar',
                            cancelButtonText: 'Cancelar',
                            confirmButtonColor: '#e67e22'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                performMoveAjax(orderId, newStage, null, evt.item, evt.from);
                            } else {
                                revertCard(evt.item, evt.from);
                            }
                        });
                    } else {
                        performMoveAjax(orderId, newStage, null, evt.item, evt.from);
                    }
                }
            });
        });
    })();

    function updateColumnCounts() {
        document.querySelectorAll('.pipeline-column').forEach(function(col) {
            var badge = col.querySelector('.pipeline-column-header .badge.bg-white');
            var cards = col.querySelector('.pipeline-dropzone').querySelectorAll('.pipeline-card');
            if (badge) badge.textContent = cards.length;

            // Update delayed count badge
            var delayedBadge = col.querySelector('.pipeline-column-header .badge.bg-danger');
            // We can't recompute delay server-side, so just update total count
        });

        // Recalculate auto-expand after counts change
        autoExpandColumns();
    }

    // ── Auto-expand cards in columns with ≤ 4 orders ──
    var CARDS_THRESHOLD_COLLAPSE = 4;

    function autoExpandColumns() {
        document.querySelectorAll('.pipeline-column').forEach(function(col) {
            var allCards = col.querySelectorAll('.pipeline-card');
            var visibleCards = col.querySelectorAll('.pipeline-card:not([style*="display: none"])');
            var countForThreshold = visibleCards.length > 0 ? visibleCards.length : allCards.length;
            var shouldExpand = (countForThreshold <= CARDS_THRESHOLD_COLLAPSE);

            allCards.forEach(function(card) {
                // Skip manually toggled cards
                if (card.dataset.manualToggle) return;

                if (shouldExpand) {
                    card.classList.add('pipeline-card-expanded');
                } else {
                    card.classList.remove('pipeline-card-expanded');
                }
            });
        });
    }

    // Run on load
    autoExpandColumns();

    // ── Card collapse/expand toggle ──
    document.querySelectorAll('.pipeline-card-toggle').forEach(function(header) {
        header.addEventListener('click', function(e) {
            e.stopPropagation();
            var card = this.closest('.pipeline-card');
            card.classList.toggle('pipeline-card-expanded');
            // Mark as manually toggled so autoExpand won't override
            card.dataset.manualToggle = '1';
        });
    });

    // ── Card hover preview (tooltip with value) ──
    document.querySelectorAll('.pipeline-card').forEach(function(card) {
        card.addEventListener('mouseenter', function() {
            this.style.transition = 'transform 0.15s ease, box-shadow 0.15s ease';
        });
    });
});
</script>
