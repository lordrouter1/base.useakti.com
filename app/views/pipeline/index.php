<div class="container-fluid py-3">
    <!-- Header com Estatísticas -->
    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-3 border-bottom">
        <h1 class="h2 mb-0"><i class="fas fa-stream me-2"></i>Linha de Produção</h1>
        <div class="btn-toolbar gap-2">
            <?php if(!empty($delayedOrders)): ?>
            <button class="btn btn-sm btn-danger position-relative" data-bs-toggle="modal" data-bs-target="#delayedModal">
                <i class="fas fa-exclamation-triangle me-1"></i> Atrasados
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-dark">
                    <?= count($delayedOrders) ?>
                </span>
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

    <!-- Cards de Resumo -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:45px;height:45px;background:rgba(52,152,219,0.15);">
                        <i class="fas fa-tasks text-primary"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Pedidos Ativos</div>
                        <div class="fw-bold fs-5"><?= $stats['total_active'] ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:45px;height:45px;background:rgba(192,57,43,0.15);">
                        <i class="fas fa-exclamation-circle text-danger"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Atrasados</div>
                        <div class="fw-bold fs-5 <?= $stats['total_delayed'] > 0 ? 'text-danger' : '' ?>"><?= $stats['total_delayed'] ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:45px;height:45px;background:rgba(39,174,96,0.15);">
                        <i class="fas fa-check-circle text-success"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Concluídos (mês)</div>
                        <div class="fw-bold fs-5"><?= $stats['completed_month'] ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:45px;height:45px;background:rgba(243,156,18,0.15);">
                        <i class="fas fa-dollar-sign text-warning"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Valor em Aberto</div>
                        <div class="fw-bold fs-5">R$ <?= number_format($stats['total_value'], 2, ',', '.') ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pipeline Kanban Board -->
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
        // Se nenhuma etapa configurada, mostra todas (retrocompatibilidade)
        if (empty($allowedStages)) {
            $allowedStages = array_keys($stages);
        }
    } else {
        $allowedStages = array_keys($stages);
    }
    ?>
    <div class="pipeline-board-wrapper position-relative" id="pipelineBoardWrapper">
        <!-- Navigation buttons for horizontal scroll (mobile/tablet) -->
        <button class="pipeline-nav-btn nav-left" id="pipelineNavLeft" title="Rolar para esquerda"><i class="fas fa-chevron-left"></i></button>
        <button class="pipeline-nav-btn nav-right" id="pipelineNavRight" title="Rolar para direita"><i class="fas fa-chevron-right"></i></button>

        <div class="pipeline-board d-flex gap-2 pb-3" id="pipelineBoard" style="min-height: 500px;">
            <?php 
            // Contar quantas colunas visíveis teremos
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
                if ($stageKey === 'concluido' || $stageKey === 'cancelado') continue; // Concluído e Cancelado não aparecem no kanban
                if (!in_array($stageKey, $allowedStages)) continue; // Filtra por permissão
                $stageOrders = $ordersByStage[$stageKey] ?? [];
                $stageGoal = isset($goals[$stageKey]) ? (int)$goals[$stageKey]['max_hours'] : 24;
            ?>
            <div class="pipeline-column" data-stage-key="<?= $stageKey ?>">
                <!-- Cabeçalho da Coluna -->
                <div class="pipeline-column-header rounded-top p-2 px-3 d-flex align-items-center justify-content-between" 
                     style="background: <?= $stageInfo['color'] ?>; color: #fff;">
                    <div class="d-flex align-items-center">
                        <i class="<?= $stageInfo['icon'] ?> me-2"></i>
                        <span class="fw-bold small"><?= $stageInfo['label'] ?></span>
                    </div>
                    <span class="badge bg-white text-dark rounded-pill"><?= count($stageOrders) ?></span>
                </div>
                
                <!-- Meta de tempo -->
                <div class="bg-light text-center py-1 border-start border-end" style="font-size: 0.7rem;">
                    <i class="fas fa-clock text-muted me-1"></i>Meta: <?= $stageGoal ?>h
                </div>

                <!-- Cards dos Pedidos (droppable zone) -->
                <div class="pipeline-column-body border border-top-0 rounded-bottom bg-white p-2 pipeline-dropzone" 
                     style="min-height: 400px; max-height: 70vh; overflow-y: auto;"
                     data-stage="<?= $stageKey ?>">
                    
                    <?php if (empty($stageOrders)): ?>
                        <div class="pipeline-empty-state text-center text-muted py-4 small">
                            <i class="fas fa-inbox d-block mb-2" style="font-size: 1.5rem;"></i>
                            Nenhum pedido
                        </div>
                    <?php endif; ?>
                    <?php foreach ($stageOrders as $order): ?>
                    <?php
                        $hoursInStage = (int)$order['hours_in_stage'];
                        $isDelayed = ($stageGoal > 0 && $hoursInStage > $stageGoal);
                        $delayHours = $isDelayed ? $hoursInStage - $stageGoal : 0;
                        $priorityColors = [
                            'baixa'   => 'secondary',
                            'normal'  => 'primary',
                            'alta'    => 'warning',
                            'urgente' => 'danger',
                        ];
                        $prioColor = $priorityColors[$order['priority'] ?? 'normal'] ?? 'primary';
                    ?>
                    <div class="pipeline-card card border-0 shadow-sm mb-2 <?= $isDelayed ? 'pipeline-card-delayed' : '' ?>" 
                         data-order-id="<?= $order['id'] ?>" data-priority="<?= $order['priority'] ?? 'normal' ?>">
                        <div class="card-body p-2 pb-0">
                            <!-- Topo: Nº do Pedido + Badge de Prioridade -->
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold small text-dark">
                                    #<?= str_pad($order['id'], 4, '0', STR_PAD_LEFT) ?>
                                </span>
                                <span class="badge bg-<?= $prioColor ?> rounded-pill" style="font-size:0.65rem;">
                                    <?= ucfirst($order['priority'] ?? 'normal') ?>
                                </span>
                            </div>
                            
                            <!-- Nome do Cliente -->
                            <div class="small mb-2">
                                <i class="fas fa-user text-muted me-1" style="font-size:0.7rem;"></i>
                                <span class="text-truncate d-inline-block" style="max-width: 180px;"><?= htmlspecialchars($order['customer_name'] ?? 'Cliente removido') ?></span>
                            </div>

                            <!-- Tempo na etapa atual -->
                            <div class="d-flex align-items-center mb-2">
                                <span class="small <?= $isDelayed ? 'text-danger fw-bold' : 'text-muted' ?>">
                                    <i class="fas fa-clock me-1"></i>
                                    <?php if ($hoursInStage < 24): ?>
                                        <?= $hoursInStage ?>h nesta etapa
                                    <?php else: ?>
                                        <?= floor($hoursInStage / 24) ?>d <?= $hoursInStage % 24 ?>h nesta etapa
                                    <?php endif; ?>
                                    <?php if ($isDelayed): ?>
                                        <i class="fas fa-exclamation-triangle ms-1" title="Atrasado em <?= $delayHours ?>h"></i>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>

                        <!-- Footer: botão de visualizar -->
                        <div class="card-footer bg-transparent border-top p-2 text-center">
                            <a href="?page=pipeline&action=detail&id=<?= $order['id'] ?>" 
                               class="btn btn-sm btn-outline-primary w-100 py-1" style="font-size:0.75rem;">
                                <i class="fas fa-eye me-1"></i> Ver Pedido
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Column Minimap / Quick Navigator (visible on smaller screens) -->
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
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="delayedModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>Pedidos Atrasados (<?= count($delayedOrders) ?>)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-3">Pedido</th>
                            <th>Cliente</th>
                            <th>Etapa</th>
                            <th>Meta</th>
                            <th>Tempo Real</th>
                            <th>Atraso</th>
                            <th class="text-end pe-3">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($delayedOrders as $dOrder): ?>
                        <tr>
                            <td class="ps-3 fw-bold">#<?= str_pad($dOrder['id'], 4, '0', STR_PAD_LEFT) ?></td>
                            <td><?= $dOrder['customer_name'] ?? '—' ?></td>
                            <td>
                                <?php $dStage = $dOrder['pipeline_stage'] ?? 'contato'; ?>
                                <span class="badge" style="background:<?= $stages[$dStage]['color'] ?? '#999' ?>;">
                                    <i class="<?= $stages[$dStage]['icon'] ?? 'fas fa-circle' ?> me-1"></i>
                                    <?= $stages[$dStage]['label'] ?? $dStage ?>
                                </span>
                            </td>
                            <td><?= $dOrder['max_hours'] ?>h</td>
                            <td class="text-danger fw-bold">
                                <?php $h = (int)$dOrder['hours_in_stage']; ?>
                                <?= ($h >= 24) ? floor($h/24).'d '.($h%24).'h' : $h.'h' ?>
                            </td>
                            <td>
                                <span class="badge bg-danger rounded-pill">+<?= $dOrder['delay_hours'] ?>h</span>
                            </td>
                            <td class="text-end pe-3">
                                <a href="?page=pipeline&action=detail&id=<?= $dOrder['id'] ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i>
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
    Swal.fire({ icon: 'success', title: 'Pedido movido!', text: 'O pedido foi movido para a próxima etapa.', timer: 2000, showConfirmButton: false });
    <?php endif; ?>

    <?php if(isset($_GET['status']) && $_GET['status'] == 'success'): ?>
    Swal.fire({ icon: 'success', title: 'Sucesso!', timer: 2000, showConfirmButton: false });
    <?php endif; ?>

    // Alerta automático de atrasados ao entrar na página
    <?php if(count($delayedOrders) > 0): ?>
    Swal.fire({
        icon: 'warning',
        title: 'Atenção!',
        html: '<b><?= count($delayedOrders) ?></b> pedido(s) estão atrasados!<br>Clique em "Ver Detalhes" para analisar.',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-eye me-1"></i> Ver Detalhes',
        cancelButtonText: 'Fechar',
        confirmButtonColor: '#c0392b'
    }).then((result) => {
        if (result.isConfirmed) {
            var modal = new bootstrap.Modal(document.getElementById('delayedModal'));
            modal.show();
        }
    });
    <?php endif; ?>

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

    function performMoveAjax(orderId, newStage, warehouseId, evtItem, evtFrom) {
        const formData = new FormData();
        formData.append('order_id', orderId);
        formData.append('stage', newStage);
        if (warehouseId) formData.append('warehouse_id', warehouseId);

        return fetch('?page=pipeline&action=moveAjax', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
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
            } else {
                Swal.fire({ icon: 'error', title: 'Erro', text: data.message || 'Não foi possível mover o pedido.', timer: 3000 });
                revertCard(evtItem, evtFrom);
            }
        })
        .catch(() => {
            Swal.fire({ icon: 'error', title: 'Erro', text: 'Erro de conexão ao mover pedido.', timer: 3000 });
            revertCard(evtItem, evtFrom);
        });
    }

    function revertCard(item, fromZone) {
        fromZone.appendChild(item);
        updateColumnCounts();
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
                    // Mostra "Nenhum pedido" se colunas ficaram vazias
                    document.querySelectorAll('.pipeline-dropzone').forEach(dz => {
                        const cards = dz.querySelectorAll('.pipeline-card');
                        let emptyMsg = dz.querySelector('.pipeline-empty-state');
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
        document.querySelectorAll('.pipeline-column').forEach(col => {
            const badge = col.querySelector('.pipeline-column-header .badge');
            const cards = col.querySelector('.pipeline-dropzone').querySelectorAll('.pipeline-card');
            if (badge) badge.textContent = cards.length;
        });
    }
});
</script>
