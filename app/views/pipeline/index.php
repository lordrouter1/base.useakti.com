<div id="pipelineApp" class="container-fluid py-3" data-status="<?= e($_GET['status'] ?? '') ?>" data-delayed-count="<?= isset($delayedOrders) ? count($delayedOrders) : 0 ?>">
    <!-- â•â•â• Page Header â€” Clean, Linear-style â•â•â• -->
    <div class="pipeline-page-header">
        <div>
            <h1><i class="fas fa-stream me-2"></i>Linha de ProduÃ§Ã£o</h1>
            <small class="text-muted" style="font-size:0.72rem;"><i class="fas fa-calendar-alt me-1"></i><?= date('d/m/Y H:i') ?></small>
        </div>
        <div class="pipeline-header-actions">
            <div class="input-group input-group-sm" style="max-width:220px;">
                <span class="input-group-text bg-transparent border-end-0"><i class="fas fa-search text-muted" style="font-size:0.75rem;"></i></span>
                <input type="text" class="form-control border-start-0 ps-0" id="pipelineSearch" placeholder="Buscar pedido ou cliente..." style="font-size:0.78rem;">
            </div>
            <select class="form-select form-select-sm" id="pipelinePriorityFilter" style="max-width:140px;font-size:0.78rem;">
                <option value="">Prioridade</option>
                <option value="urgente">ðŸ”´ Urgente</option>
                <option value="alta">ðŸŸ¡ Alta</option>
                <option value="normal">ðŸ”µ Normal</option>
                <option value="baixa">âšª Baixa</option>
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
    <!-- â•â•â• Delayed Orders Alert Banner â•â•â• -->
    <div class="pipeline-delayed-banner">
        <i class="fas fa-exclamation-triangle"></i>
        <div>
            <strong><?= count($delayedOrders) ?> pedido<?= count($delayedOrders) > 1 ? 's' : '' ?></strong> 
            ultrapassaram a meta de tempo. 
            <a href="#" data-bs-toggle="modal" data-bs-target="#delayedModal" class="text-danger fw-bold text-decoration-underline">Ver detalhes â†’</a>
        </div>
    </div>
    <?php endif; ?>

    <!-- â•â•â• KPI Metric Widgets â•â•â• -->
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
                <div class="pipeline-metric-sub"><?= $stats['total_delayed'] > 0 ? 'aÃ§Ã£o necessÃ¡ria' : 'tudo em dia âœ“' ?></div>
            </div>
        </div>
        <div class="pipeline-metric-card metric-completed">
            <div class="pipeline-metric-icon icon-completed">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="pipeline-metric-data">
                <div class="pipeline-metric-label">ConcluÃ­dos</div>
                <div class="pipeline-metric-value"><?= $stats['completed_month'] ?></div>
                <div class="pipeline-metric-sub">neste mÃªs</div>
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

    <!-- â•â•â• Pipeline Kanban Board â•â•â• -->
    <?php
    // Filtra etapas por permissÃ£o do grupo do usuÃ¡rio
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
                <!-- CabeÃ§alho da Coluna -->
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
                                        // â•â•â• Badge NF-e â€” IntegraÃ§Ã£o Pipeline Ã— Fiscal â•â•â•
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
                            <th class="text-end pe-3" style="font-size:0.78rem;">AÃ§Ã£o</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($delayedOrders as $dOrder): ?>
                        <tr>
                            <td class="ps-3 fw-bold" style="font-size:0.82rem;">#<?= str_pad($dOrder['id'], 4, '0', STR_PAD_LEFT) ?></td>
                            <td style="font-size:0.82rem;"><?= e($dOrder['customer_name'] ?? 'â€”') ?></td>
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
<script src="<?= asset('assets/js/modules/pipeline.js') ?>"></script>
