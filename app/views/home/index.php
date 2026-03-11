<?php
    // ── Buscar dados para a home unificada ──
    $dbHome = (new Database())->getConnection();
    $isAdmin = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');

    // Contadores gerais
    $totalPedidosAtivos = (int) $dbHome->query("SELECT COUNT(*) FROM orders WHERE pipeline_stage NOT IN ('concluido','cancelado') AND status != 'cancelado'")->fetchColumn();
    $pedidosHoje = (int) $dbHome->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()")->fetchColumn();

    // Pipeline por etapa
    $stagesMap = [
        'contato'    => ['label'=>'Contato',      'color'=>'#9b59b6','icon'=>'fas fa-phone'],
        'orcamento'  => ['label'=>'Orçamento',     'color'=>'#3498db','icon'=>'fas fa-file-invoice-dollar'],
        'venda'      => ['label'=>'Venda',         'color'=>'#2ecc71','icon'=>'fas fa-handshake'],
        'producao'   => ['label'=>'Produção',      'color'=>'#e67e22','icon'=>'fas fa-industry'],
        'preparacao' => ['label'=>'Preparação',    'color'=>'#1abc9c','icon'=>'fas fa-boxes-packing'],
        'envio'      => ['label'=>'Envio/Entrega', 'color'=>'#e74c3c','icon'=>'fas fa-truck'],
        'financeiro' => ['label'=>'Financeiro',    'color'=>'#f39c12','icon'=>'fas fa-coins'],
        'concluido'  => ['label'=>'Concluído',     'color'=>'#27ae60','icon'=>'fas fa-check-double'],
    ];

    $stmtPipeline = $dbHome->query("SELECT pipeline_stage, COUNT(*) as cnt FROM orders WHERE pipeline_stage NOT IN ('cancelado') AND status != 'cancelado' GROUP BY pipeline_stage");
    $pipelineCounts = [];
    while ($row = $stmtPipeline->fetch(PDO::FETCH_ASSOC)) {
        $pipelineCounts[$row['pipeline_stage']] = (int) $row['cnt'];
    }

    // Atrasados (baseado em metas de tempo)
    $atrasados = 0;
    $delayedOrders = [];
    try {
        $stmtGoals = $dbHome->query("SELECT stage, max_hours FROM pipeline_stage_goals");
        $goals = [];
        while ($g = $stmtGoals->fetch(PDO::FETCH_ASSOC)) {
            $goals[$g['stage']] = (int)$g['max_hours'];
        }
        $stmtActive = $dbHome->query("SELECT o.id, o.pipeline_stage, o.pipeline_entered_at, c.name as customer_name
            FROM orders o LEFT JOIN customers c ON o.customer_id = c.id
            WHERE o.pipeline_stage NOT IN ('concluido','cancelado') AND o.status != 'cancelado'");
        while ($o = $stmtActive->fetch(PDO::FETCH_ASSOC)) {
            $hours = round((time() - strtotime($o['pipeline_entered_at'])) / 3600);
            $goal = $goals[$o['pipeline_stage']] ?? 24;
            if ($goal > 0 && $hours > $goal) {
                $atrasados++;
                $delayedOrders[] = array_merge($o, ['delay_hours' => $hours - $goal]);
            }
        }
    } catch (Exception $e) {}

    // Financeiro resumo
    $m = date('m'); $y = date('Y');
    $recebidoMes = 0; $aReceberTotal = 0; $atrasadosFin = 0; $pendentesConfirmacao = 0;
    try {
        $stmtRec = $dbHome->prepare("SELECT COALESCE(SUM(paid_amount),0) FROM order_installments WHERE status='pago' AND MONTH(paid_date)=:m AND YEAR(paid_date)=:y");
        $stmtRec->execute([':m' => $m, ':y' => $y]);
        $recebidoMes = (float) $stmtRec->fetchColumn();

        $aReceberTotal = (float) $dbHome->query("SELECT COALESCE(SUM(amount),0) FROM order_installments WHERE status IN ('pendente','atrasado')")->fetchColumn();
        $atrasadosFin = (float) $dbHome->query("SELECT COALESCE(SUM(amount),0) FROM order_installments WHERE status IN ('pendente','atrasado') AND due_date < CURDATE()")->fetchColumn();
        $pendentesConfirmacao = (int) $dbHome->query("SELECT COUNT(*) FROM order_installments WHERE is_confirmed=0 AND status='pago'")->fetchColumn();
    } catch (Exception $e) {}

    // Concluídos no mês
    $concluidosMes = 0;
    try {
        $stmtConclMes = $dbHome->prepare("SELECT COUNT(*) FROM orders WHERE pipeline_stage='concluido' AND MONTH(updated_at)=:m AND YEAR(updated_at)=:y");
        $stmtConclMes->execute([':m' => $m, ':y' => $y]);
        $concluidosMes = (int) $stmtConclMes->fetchColumn();
    } catch (Exception $e) {}

    // Próximos contatos agendados
    $proximosContatos = [];
    try {
        $stmtAgenda = $dbHome->query("SELECT o.id, o.scheduled_date, c.name as customer_name 
            FROM orders o LEFT JOIN customers c ON o.customer_id = c.id 
            WHERE o.pipeline_stage = 'contato' AND o.scheduled_date >= CURDATE() AND o.status != 'cancelado' 
            ORDER BY o.scheduled_date ASC LIMIT 5");
        $proximosContatos = $stmtAgenda->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}

    // Últimos pedidos movidos
    $recentesMov = [];
    try {
        $stmtRecentes = $dbHome->query("SELECT h.order_id, h.to_stage, h.created_at, c.name as customer_name 
            FROM pipeline_history h 
            LEFT JOIN orders o ON h.order_id = o.id 
            LEFT JOIN customers c ON o.customer_id = c.id 
            ORDER BY h.created_at DESC LIMIT 6");
        $recentesMov = $stmtRecentes->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
?>

<div class="container-fluid py-3">

    <!-- ══════ Saudação + Atalhos rápidos ══════ -->
    <div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-2 mb-4 border-bottom" id="home-header">
        <div>
            <h1 class="h2 mb-0"><i class="fas fa-hand-sparkles me-2 text-warning"></i>Olá, <?= e($_SESSION['user_name'] ?? 'Usuário') ?>!</h1>
            <small class="text-muted"><?= ucfirst(strftime('%A, %d de %B de %Y')) ?></small>
        </div>
        <div class="btn-toolbar gap-2 mt-2 mt-md-0" id="home-shortcuts">
            <a href="?page=orders&action=create" class="btn btn-sm btn-primary">
                <i class="fas fa-plus me-1"></i> Novo Pedido
            </a>
            <a href="?page=customers&action=create" class="btn btn-sm btn-outline-success">
                <i class="fas fa-user-plus me-1"></i> Novo Cliente
            </a>
            <a href="?page=pipeline" class="btn btn-sm btn-outline-warning text-dark">
                <i class="fas fa-stream me-1"></i> Pipeline
            </a>
            <a href="?page=financial_payments" class="btn btn-sm btn-outline-info">
                <i class="fas fa-file-invoice-dollar me-1"></i> Pagamentos
            </a>
        </div>
    </div>

    <!-- ══════ Cards Resumo Principal ══════ -->
    <div class="row g-3 mb-4" id="home-cards-summary">
        <div class="col-xl-3 col-md-6">
            <a href="?page=pipeline" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
                    <div class="card-body d-flex align-items-center p-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:50px;height:50px;background:rgba(52,152,219,0.15);">
                            <i class="fas fa-tasks fa-lg text-primary"></i>
                        </div>
                        <div>
                            <div class="text-muted small text-uppercase fw-bold">Pedidos Ativos</div>
                            <div class="fw-bold fs-4 text-primary"><?= $totalPedidosAtivos ?></div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-xl-3 col-md-6">
            <a href="?page=orders" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 border-start border-info border-4">
                    <div class="card-body d-flex align-items-center p-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:50px;height:50px;background:rgba(23,162,184,0.15);">
                            <i class="fas fa-calendar-day fa-lg text-info"></i>
                        </div>
                        <div>
                            <div class="text-muted small text-uppercase fw-bold">Criados Hoje</div>
                            <div class="fw-bold fs-4 text-info"><?= $pedidosHoje ?></div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-xl-3 col-md-6">
            <a href="?page=pipeline" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 border-start border-4 <?= $atrasados > 0 ? 'border-danger' : 'border-success' ?>">
                    <div class="card-body d-flex align-items-center p-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:50px;height:50px;background:<?= $atrasados > 0 ? 'rgba(192,57,43,0.15)' : 'rgba(39,174,96,0.15)' ?>;">
                            <i class="fas fa-exclamation-triangle fa-lg <?= $atrasados > 0 ? 'text-danger' : 'text-success' ?>"></i>
                        </div>
                        <div>
                            <div class="text-muted small text-uppercase fw-bold">Atrasados</div>
                            <div class="fw-bold fs-4 <?= $atrasados > 0 ? 'text-danger' : 'text-success' ?>"><?= $atrasados ?></div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-xl-3 col-md-6">
            <a href="?page=orders" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
                    <div class="card-body d-flex align-items-center p-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:50px;height:50px;background:rgba(39,174,96,0.15);">
                            <i class="fas fa-check-double fa-lg text-success"></i>
                        </div>
                        <div>
                            <div class="text-muted small text-uppercase fw-bold">Concluídos no Mês</div>
                            <div class="fw-bold fs-4 text-success"><?= $concluidosMes ?></div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- ══════ Pipeline Mini Overview ══════ -->
    <div class="card border-0 shadow-sm mb-4" id="home-pipeline">
        <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 text-primary fw-bold"><i class="fas fa-stream me-2"></i>Pipeline</h6>
            <a href="?page=pipeline" class="btn btn-sm btn-outline-primary">Ver Kanban <i class="fas fa-arrow-right ms-1"></i></a>
        </div>
        <div class="card-body p-3">
            <div class="row g-2">
                <?php foreach ($stagesMap as $sKey => $sInfo):
                    if ($sKey === 'concluido') continue;
                    $count = $pipelineCounts[$sKey] ?? 0;
                ?>
                <div class="col">
                    <a href="?page=pipeline" class="text-decoration-none">
                        <div class="text-center p-2 rounded pipeline-mini-card" style="background:<?= $sInfo['color'] ?>15; border:1px solid <?= $sInfo['color'] ?>30;">
                            <div class="rounded-circle d-inline-flex align-items-center justify-content-center mx-auto mb-1"
                                 style="width:36px;height:36px;background:<?= $sInfo['color'] ?>;color:#fff;font-size:0.8rem;">
                                <i class="<?= $sInfo['icon'] ?>"></i>
                            </div>
                            <div class="fw-bold fs-5" style="color:<?= $sInfo['color'] ?>;"><?= $count ?></div>
                            <div class="text-muted" style="font-size:0.7rem;"><?= $sInfo['label'] ?></div>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ══════ Resumo Financeiro + Atrasados ══════ -->
    <div class="row g-3 mb-4">
        <div class="col-xl-6" id="home-financeiro">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-success"><i class="fas fa-coins me-2"></i>Financeiro — <?= strftime('%B/%Y') ?></h6>
                    <a href="?page=financial_payments" class="btn btn-sm btn-outline-success">Pagamentos <i class="fas fa-arrow-right ms-1"></i></a>
                </div>
                <div class="card-body p-3">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="bg-light rounded p-3 text-center h-100">
                                <div class="text-muted small text-uppercase fw-bold">Recebido</div>
                                <div class="fw-bold fs-5 text-success">R$ <?= number_format($recebidoMes, 2, ',', '.') ?></div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bg-light rounded p-3 text-center h-100">
                                <div class="text-muted small text-uppercase fw-bold">A Receber</div>
                                <div class="fw-bold fs-5 text-warning">R$ <?= number_format($aReceberTotal, 2, ',', '.') ?></div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bg-light rounded p-3 text-center h-100">
                                <div class="text-muted small text-uppercase fw-bold">Em Atraso</div>
                                <div class="fw-bold fs-5 <?= $atrasadosFin > 0 ? 'text-danger' : 'text-muted' ?>">R$ <?= number_format($atrasadosFin, 2, ',', '.') ?></div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bg-light rounded p-3 text-center h-100">
                                <div class="text-muted small text-uppercase fw-bold">Aguardando Confirm.</div>
                                <div class="fw-bold fs-5 <?= $pendentesConfirmacao > 0 ? 'text-info' : 'text-muted' ?>"><?= $pendentesConfirmacao ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6" id="home-atrasados">
            <div class="card border-0 shadow-sm h-100 <?= count($delayedOrders) > 0 ? 'border-start border-danger border-4' : '' ?>">
                <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold <?= count($delayedOrders) > 0 ? 'text-danger' : 'text-muted' ?>">
                        <i class="fas fa-exclamation-triangle me-2"></i>Pedidos Atrasados
                        <?php if (count($delayedOrders) > 0): ?>
                            <span class="badge bg-danger rounded-pill ms-1"><?= count($delayedOrders) ?></span>
                        <?php endif; ?>
                    </h6>
                    <a href="?page=pipeline" class="btn btn-sm btn-outline-danger">Ver todos <i class="fas fa-arrow-right ms-1"></i></a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($delayedOrders)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-check-circle fa-2x d-block mb-2 text-success opacity-50"></i>
                        <small>Nenhum pedido atrasado!</small>
                    </div>
                    <?php else: ?>
                    <div class="list-group list-group-flush" style="max-height:220px;overflow-y:auto;">
                        <?php foreach (array_slice($delayedOrders, 0, 6) as $dOrder):
                            $dStage = $stagesMap[$dOrder['pipeline_stage']] ?? ['label'=>$dOrder['pipeline_stage'],'color'=>'#999','icon'=>'fas fa-circle'];
                        ?>
                        <a href="?page=pipeline&action=detail&id=<?= $dOrder['id'] ?>" class="list-group-item list-group-item-action py-2 px-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="rounded-circle d-inline-flex align-items-center justify-content-center"
                                          style="width:28px;height:28px;background:<?= $dStage['color'] ?>;color:#fff;font-size:0.65rem;">
                                        <i class="<?= $dStage['icon'] ?>"></i>
                                    </span>
                                    <div>
                                        <span class="fw-bold small">#<?= str_pad($dOrder['id'], 4, '0', STR_PAD_LEFT) ?></span>
                                        <span class="ms-1 small text-muted"><?= e($dOrder['customer_name'] ?? '') ?></span>
                                    </div>
                                </div>
                                <span class="badge bg-danger rounded-pill" style="font-size:0.65rem;">+<?= $dOrder['delay_hours'] ?>h</span>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════ Atividades Recentes + Agenda ══════ -->
    <div class="row g-3 mb-4">
        <div class="col-md-6" id="home-agenda">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold" style="color:#9b59b6;"><i class="fas fa-calendar-check me-2"></i>Próximos Contatos</h6>
                    <a href="?page=agenda" class="btn btn-sm btn-outline-secondary">Ver Agenda</a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($proximosContatos)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-calendar-check d-block mb-2" style="font-size:1.5rem;opacity:0.4;"></i>
                        <small>Nenhum contato agendado</small>
                    </div>
                    <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($proximosContatos as $contato):
                            $isToday = (($contato['scheduled_date'] ?? '') == date('Y-m-d'));
                        ?>
                        <a href="?page=pipeline&action=detail&id=<?= $contato['id'] ?>" class="list-group-item list-group-item-action py-2 px-3 <?= $isToday ? 'list-group-item-warning' : '' ?>">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="fw-bold small">#<?= str_pad($contato['id'], 4, '0', STR_PAD_LEFT) ?></span>
                                    <span class="ms-1 small"><?= e($contato['customer_name'] ?? 'Cliente') ?></span>
                                </div>
                                <?php if ($isToday): ?>
                                <span class="badge bg-warning text-dark" style="font-size:0.65rem;">HOJE</span>
                                <?php else: ?>
                                <span class="text-muted" style="font-size:0.7rem;"><?= date('d/m', strtotime($contato['scheduled_date'])) ?></span>
                                <?php endif; ?>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-6" id="home-atividade">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-history me-2"></i>Atividade Recente</h6>
                    <a href="?page=pipeline" class="btn btn-sm btn-outline-secondary">Ver Pipeline</a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recentesMov)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-history d-block mb-2" style="font-size:1.5rem;opacity:0.4;"></i>
                        <small>Nenhuma movimentação recente</small>
                    </div>
                    <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recentesMov as $mov):
                            $stInfo = $stagesMap[$mov['to_stage']] ?? ['label'=>$mov['to_stage'],'color'=>'#999','icon'=>'fas fa-circle'];
                        ?>
                        <a href="?page=pipeline&action=detail&id=<?= $mov['order_id'] ?>" class="list-group-item list-group-item-action py-2 px-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="rounded-circle d-inline-flex align-items-center justify-content-center"
                                          style="width:24px;height:24px;background:<?= $stInfo['color'] ?>;color:#fff;font-size:0.6rem;">
                                        <i class="<?= $stInfo['icon'] ?>"></i>
                                    </span>
                                    <div>
                                        <span class="fw-bold small">#<?= str_pad($mov['order_id'], 4, '0', STR_PAD_LEFT) ?></span>
                                        <span class="ms-1 small text-muted"><?= e($mov['customer_name'] ?? '') ?></span>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span class="badge rounded-pill" style="background:<?= $stInfo['color'] ?>;font-size:0.6rem;"><?= $stInfo['label'] ?></span>
                                    <div class="text-muted" style="font-size:0.6rem;"><?= date('d/m H:i', strtotime($mov['created_at'])) ?></div>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<?php if (!empty($delayedOrders)): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        icon: 'warning',
        title: 'Atenção!',
        html: '<b><?= count($delayedOrders) ?></b> pedido(s) estão <strong class="text-danger">atrasados</strong> na linha de produção!',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-stream me-1"></i> Ir para Pipeline',
        cancelButtonText: 'Depois',
        confirmButtonColor: '#c0392b'
    }).then((result) => {
        if (result.isConfirmed) window.location.href = '?page=pipeline';
    });
});
</script>
<?php endif; ?>

<style>
.pipeline-mini-card { transition: transform 0.15s ease; }
.pipeline-mini-card:hover { transform: translateY(-2px); }
</style>
