<?php
    // ── Buscar dados para a home unificada ──
    $dbHome = (new Database())->getConnection();
    $isAdmin = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');

    // ── Resolver widgets visíveis para o grupo do usuário ──
    $userGroupId = (int)($_SESSION['user']['group_id'] ?? 0);
    $dashWidgetModel = new \Akti\Models\DashboardWidget($dbHome);
    $visibleWidgets = $dashWidgetModel->getVisibleWidgetsForGroup($userGroupId);
    $availableWidgets = \Akti\Models\DashboardWidget::getAvailableWidgets();

    // ── Buscar dados somente para os widgets que serão exibidos ──
    // Contadores gerais (usados por cards_summary)
    $totalPedidosAtivos = 0; $pedidosHoje = 0; $concluidosMes = 0;
    if (in_array('cards_summary', $visibleWidgets)) {
        $totalPedidosAtivos = (int) $dbHome->query("SELECT COUNT(*) FROM orders WHERE pipeline_stage NOT IN ('concluido','cancelado') AND status != 'cancelado'")->fetchColumn();
        $pedidosHoje = (int) $dbHome->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()")->fetchColumn();
        $m = date('m'); $y = date('Y');
        try {
            $stmtConclMes = $dbHome->prepare("SELECT COUNT(*) FROM orders WHERE pipeline_stage='concluido' AND MONTH(updated_at)=:m AND YEAR(updated_at)=:y");
            $stmtConclMes->execute([':m' => $m, ':y' => $y]);
            $concluidosMes = (int) $stmtConclMes->fetchColumn();
        } catch (Exception $e) {}
    }

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

    $pipelineCounts = [];
    if (in_array('pipeline', $visibleWidgets)) {
        $stmtPipeline = $dbHome->query("SELECT pipeline_stage, COUNT(*) as cnt FROM orders WHERE pipeline_stage NOT IN ('cancelado') AND status != 'cancelado' GROUP BY pipeline_stage");
        while ($row = $stmtPipeline->fetch(PDO::FETCH_ASSOC)) {
            $pipelineCounts[$row['pipeline_stage']] = (int) $row['cnt'];
        }
    }

    // Atrasados (baseado em metas de tempo)
    $atrasados = 0;
    $delayedOrders = [];
    $needDelayed = in_array('atrasados', $visibleWidgets) || in_array('cards_summary', $visibleWidgets);
    if ($needDelayed) {
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
    }

    // Financeiro resumo
    if (!isset($m)) { $m = date('m'); $y = date('Y'); }
    $recebidoMes = 0; $aReceberTotal = 0; $atrasadosFin = 0; $pendentesConfirmacao = 0;
    if (in_array('financeiro', $visibleWidgets)) {
        try {
            $stmtRec = $dbHome->prepare("SELECT COALESCE(SUM(paid_amount),0) FROM order_installments WHERE status='pago' AND MONTH(paid_date)=:m AND YEAR(paid_date)=:y");
            $stmtRec->execute([':m' => $m, ':y' => $y]);
            $recebidoMes = (float) $stmtRec->fetchColumn();

            $aReceberTotal = (float) $dbHome->query("SELECT COALESCE(SUM(amount),0) FROM order_installments WHERE status IN ('pendente','atrasado')")->fetchColumn();
            $atrasadosFin = (float) $dbHome->query("SELECT COALESCE(SUM(amount),0) FROM order_installments WHERE status IN ('pendente','atrasado') AND due_date < CURDATE()")->fetchColumn();
            $pendentesConfirmacao = (int) $dbHome->query("SELECT COUNT(*) FROM order_installments WHERE is_confirmed=0 AND status='pago'")->fetchColumn();
        } catch (Exception $e) {}
    }

    // Próximos contatos agendados
    $proximosContatos = [];
    if (in_array('agenda', $visibleWidgets)) {
        try {
            $stmtAgenda = $dbHome->query("SELECT o.id, o.scheduled_date, c.name as customer_name 
                FROM orders o LEFT JOIN customers c ON o.customer_id = c.id 
                WHERE o.pipeline_stage = 'contato' AND o.scheduled_date >= CURDATE() AND o.status != 'cancelado' 
                ORDER BY o.scheduled_date ASC LIMIT 5");
            $proximosContatos = $stmtAgenda->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}
    }

    // Últimos pedidos movidos
    $recentesMov = [];
    if (in_array('atividade', $visibleWidgets)) {
        try {
            $stmtRecentes = $dbHome->query("SELECT h.order_id, h.to_stage, h.created_at, c.name as customer_name 
                FROM pipeline_history h 
                LEFT JOIN orders o ON h.order_id = o.id 
                LEFT JOIN customers c ON o.customer_id = c.id 
                ORDER BY h.created_at DESC LIMIT 6");
            $recentesMov = $stmtRecentes->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}
    }
?>

<div class="container-fluid py-3">

    <?php
    // ── Widgets com wrapper de row agrupando pares (financeiro+atrasados, agenda+atividade) ──
    // Pares são agrupados em <div class="row"> somente quando estão consecutivos na ordem.
    // Se o admin reordena um widget para que o par não fique junto, cada um é renderizado standalone.
    $rowPairs = [
        'row_fin' => ['financeiro', 'atrasados'],
        'row_act' => ['agenda', 'atividade'],
    ];

    // Construir mapa de quais widgets pertencem a rows
    $widgetRowMap = [];
    foreach ($rowPairs as $rowKey => $pair) {
        foreach ($pair as $wk) {
            $widgetRowMap[$wk] = $rowKey;
        }
    }

    // Pré-calcular quais pares estão consecutivos entre os visíveis
    $activePairs = [];
    foreach ($rowPairs as $rowKey => $pair) {
        $visiblePair = array_values(array_filter($pair, function($wk) use ($visibleWidgets) {
            return in_array($wk, $visibleWidgets);
        }));
        // Precisa de 2+ widgets visíveis do par e que sejam consecutivos na ordem
        if (count($visiblePair) >= 2) {
            $indices = array_map(function($wk) use ($visibleWidgets) {
                return array_search($wk, $visibleWidgets);
            }, $visiblePair);
            sort($indices);
            // Verificar se são consecutivos (diferença máxima de 1 entre eles)
            $consecutive = true;
            for ($c = 1; $c < count($indices); $c++) {
                if ($indices[$c] - $indices[$c-1] !== 1) {
                    $consecutive = false;
                    break;
                }
            }
            if ($consecutive) {
                $activePairs[$rowKey] = $visiblePair;
            }
        }
    }

    // Rastrear quais rows já foram abertas/fechadas
    $openRows = [];
    $renderedInRow = [];

    foreach ($visibleWidgets as $idx => $wKey):
        if (!isset($availableWidgets[$wKey])) continue;
        $widgetDef = $availableWidgets[$wKey];
        $file = $widgetDef['file'] ?? null;
        if (!$file || !file_exists($file)) continue;

        $inRow = $widgetRowMap[$wKey] ?? null;
        // Só agrupar se o par está ativo (ambos visíveis e consecutivos)
        $shouldGroup = $inRow && isset($activePairs[$inRow]) && in_array($wKey, $activePairs[$inRow]);

        if ($shouldGroup && !isset($openRows[$inRow])) {
            // Abrir a row para o par
            echo '<div class="row g-3 mb-4">';
            $openRows[$inRow] = true;
        } elseif ($inRow && !$shouldGroup) {
            // Widget pertence a um par, mas está sozinho (par não consecutivo ou parceiro oculto)
            // Envolver em row própria para que col-* funcione corretamente
            echo '<div class="row g-3 mb-4">';
        }

        require $file;

        if ($shouldGroup) {
            $renderedInRow[$inRow][] = $wKey;
            // Fechar a row quando todos os widgets do par ativo foram renderizados
            if (count($renderedInRow[$inRow]) >= count($activePairs[$inRow])) {
                echo '</div>';
            }
        } elseif ($inRow && !$shouldGroup) {
            // Fechar row do widget standalone
            echo '</div>';
        }
    endforeach;
    ?>

</div>

<?php if (!empty($delayedOrders) && in_array('atrasados', $visibleWidgets)): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        title: '<strong class="fs-3">Atenção!</strong>',
        toast: true,
        position: 'center-end',
        html: '<small><b><?= count($delayedOrders) ?></b> pedido(s) estão <strong class="text-light">atrasados</strong>!</small>',
        showCancelButton: false,
        confirmButtonText: '<span class="text-red"><i class="fas fa-stream me-1"></i> Ir para Pipeline</span>',
        confirmButtonColor: '#ffffff',
        background: '#ef4444',
        color:'#ffffff',
        timer: 10000,
        timerProgressBar: true,
        customClass:{
            popup: 'shadow',
        }
    }).then((result) => {
        if (result.isConfirmed) window.location.href = '?page=pipeline';
    });
});
</script>
<?php endif; ?>

<!-- Styles moved to assets/css/modules/home.css -->
