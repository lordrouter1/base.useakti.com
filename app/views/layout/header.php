<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akti — Gestão em Produção</title>

    <!-- SEO -->
    <meta name="description" content="Akti — Plataforma online para gestão de produção. Gerencie pedidos, produção, estoque, financeiro e clientes.">
    <meta name="keywords" content="gestão, pedidos, produção, estoque, ERP, produtos personalizados, akti">
    <meta name="author" content="Akti">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#2c3e50">
    <?= csrf_meta() ?>

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="Akti — Gestão em Produção">
    <meta property="og:description" content="Plataforma online para gestão de produção. Pedidos, produção, estoque e clientes em um só lugar.">
    <meta property="og:image" content="assets/logos/akti-logo-dark.svg">
    <meta property="og:locale" content="pt_BR">
    <meta property="og:site_name" content="Akti">

    <!-- X (Twitter) Card -->
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="Akti — Gestão em Produção">
    <meta name="twitter:description" content="Plataforma online para gestão de produção.">
    <meta name="twitter:image" content="assets/logos/akti-logo-dark.svg">

    <!-- Web App Manifest -->
    <link rel="manifest" href="manifest.json">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Akti">
    <link rel="apple-touch-icon" href="assets/logos/akti-icon-dark.svg">

    <link rel="icon" type="image/x-icon" href="assets/logos/akti-icon-dark.ico">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/theme.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="assets/css/walkthrough.css" rel="stylesheet">
    <style>
        /* ── Bell dropdown override (fundo branco) ── */
        #bellDropdownMenu {
            background: #fff !important;
        }
        #bellDropdownMenu .dropdown-item {
            color: #333 !important;
            padding: 0.4rem 0.75rem;
            font-size: 0.85rem;
        }
        #bellDropdownMenu .dropdown-item:hover,
        #bellDropdownMenu .dropdown-item:focus {
            background: #f1f5f9 !important;
            color: #333 !important;
            padding-left: 0.75rem;
        }
        #bellDropdownToggle::after {
            display: none;
        }
    </style>
</head>
<body>

<?php
    $currentPage = isset($_GET['page']) ? $_GET['page'] : 'home';
    $menuPages = require 'app/config/menu.php';

    // Achata o menu para ter uma lista simples (para permissões)
    $flatMenuPages = [];
    foreach ($menuPages as $key => $info) {
        if (isset($info['children'])) {
            foreach ($info['children'] as $childKey => $childInfo) {
                $flatMenuPages[$childKey] = $childInfo;
            }
        } else {
            $flatMenuPages[$key] = $info;
        }
    }

    // Carrega as permissões do usuário logado para filtrar o menu
    $userPermissions = [];
    $isAdmin = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');
    
    if (!$isAdmin && isset($_SESSION['user_id'])) {
        $dbMenu = (new Database())->getConnection();
        if (!empty($_SESSION['group_id'])) {
            $stmtMenu = $dbMenu->prepare("SELECT page_name FROM group_permissions WHERE group_id = :gid");
            $stmtMenu->bindParam(':gid', $_SESSION['group_id']);
            $stmtMenu->execute();
            $userPermissions = $stmtMenu->fetchAll(PDO::FETCH_COLUMN);
        }
    }

    // Contar pedidos atrasados para badge no menu
    $headerDelayedCount = 0;
    $headerDelayedOrders = [];
    if (isset($_SESSION['user_id'])) {
        try {
            $dbAlert = isset($dbMenu) ? $dbMenu : (new Database())->getConnection();
            $stmtGoalsH = $dbAlert->query("SELECT stage, max_hours FROM pipeline_stage_goals");
            $goalsH = [];
            while ($gRow = $stmtGoalsH->fetch(PDO::FETCH_ASSOC)) {
                $goalsH[$gRow['stage']] = (int)$gRow['max_hours'];
            }
            // Buscar pedidos ativos com info do cliente e produtos
            $stmtActiveH = $dbAlert->query("
                SELECT o.id, o.pipeline_stage, o.pipeline_entered_at, o.priority, o.deadline,
                       c.name as customer_name
                FROM orders o
                LEFT JOIN customers c ON o.customer_id = c.id
                WHERE o.pipeline_stage NOT IN ('concluido','cancelado') AND o.status != 'cancelado'
                ORDER BY o.pipeline_entered_at ASC
            ");
            while ($oRow = $stmtActiveH->fetch(PDO::FETCH_ASSOC)) {
                $hrsH = round((time() - strtotime($oRow['pipeline_entered_at'])) / 3600);
                $goalH = $goalsH[$oRow['pipeline_stage']] ?? 24;
                if ($goalH > 0 && $hrsH > $goalH) {
                    $oRow['hours_in_stage'] = $hrsH;
                    $oRow['max_hours'] = $goalH;
                    $oRow['delay_hours'] = $hrsH - $goalH;
                    $headerDelayedOrders[] = $oRow;
                    $headerDelayedCount++;
                }
            }
            // Buscar produtos atrasados nos setores de produção (pedidos em producao/preparacao)
            $headerDelayedProducts = [];
            try {
                $stmtDelayedProd = $dbAlert->query("
                    SELECT ops.order_id, ops.order_item_id, ops.sector_id, ops.status, ops.started_at,
                           s.name as sector_name, s.color as sector_color,
                           p.name as product_name,
                           o.pipeline_stage,
                           oi.quantity,
                           c.name as customer_name
                    FROM order_production_sectors ops
                    JOIN production_sectors s ON ops.sector_id = s.id
                    JOIN order_items oi ON ops.order_item_id = oi.id
                    JOIN products p ON oi.product_id = p.id
                    JOIN orders o ON ops.order_id = o.id
                    LEFT JOIN customers c ON o.customer_id = c.id
                    WHERE ops.status = 'pendente'
                      AND o.pipeline_stage IN ('producao','preparacao')
                      AND o.status != 'cancelado'
                    ORDER BY ops.order_id ASC, ops.sort_order ASC
                ");
                $allPendingSectors = $stmtDelayedProd->fetchAll(PDO::FETCH_ASSOC);
                // Agrupar: primeiro setor pendente por item (setor atual)
                $currentSectorByItem = [];
                foreach ($allPendingSectors as $row) {
                    $itemKey = $row['order_id'] . '_' . $row['order_item_id'];
                    if (!isset($currentSectorByItem[$itemKey])) {
                        $currentSectorByItem[$itemKey] = $row;
                    }
                }
                $headerDelayedProducts = array_values($currentSectorByItem);
            } catch (Exception $e) { $headerDelayedProducts = []; }
        } catch (Exception $e) { $headerDelayedCount = 0; $headerDelayedOrders = []; $headerDelayedProducts = []; }
    }
    
    /**
     * Verifica se o usuário pode ver determinada página no menu.
     */
    function canShowInMenu($pageKey, $pageInfo, $isAdmin, $userPermissions) {
        if (!\Akti\Core\ModuleBootloader::canAccessPage($pageKey)) return false;
        if (empty($pageInfo['permission'])) return true;
        if ($isAdmin) return true;
        // Use permission_alias if defined (e.g., financial_payments -> financial)
        $checkKey = $pageInfo['permission_alias'] ?? $pageKey;
        return in_array($checkKey, $userPermissions);
    }

    /**
     * Verifica se pelo menos um filho de um submenu é visível para o usuário.
     */
    function hasVisibleChild($children, $isAdmin, $userPermissions) {
        foreach ($children as $childKey => $childInfo) {
            if (!empty($childInfo['menu']) && canShowInMenu($childKey, $childInfo, $isAdmin, $userPermissions)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Verifica se a página atual está dentro de um submenu (para destacar o dropdown).
     */
    function isChildActive($children, $currentPage) {
        return isset($children[$currentPage]);
    }
?>

<nav class="navbar navbar-expand-lg navbar-akti sticky-top">
  <div class="container-fluid">
    <a class="navbar-brand" href="?">
        <img src="assets/logos/akti-logo-dark-nBg.svg" alt="Akti" style="height: 70px !important;">
    </a>
    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">

      <!-- ── Menu Principal (com suporte a submenus) ── -->
      <ul class="navbar-nav">
        <?php foreach ($menuPages as $pageKey => $pageInfo): ?>
          <?php if (empty($pageInfo['menu'])) continue; ?>

          <?php if (isset($pageInfo['children'])): ?>
            <?php // ── DROPDOWN (submenu) ── ?>
            <?php if (!hasVisibleChild($pageInfo['children'], $isAdmin, $userPermissions)) continue; ?>
            <li class="nav-item me-1 dropdown" data-wt-group="<?= $pageKey ?>">
              <a class="nav-link dropdown-toggle <?= isChildActive($pageInfo['children'], $currentPage) ? 'active' : '' ?>"
                 href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"
                 data-wt-toggle="<?= $pageKey ?>">
                <i class="<?= $pageInfo['icon'] ?> me-1"></i><?= $pageInfo['label'] ?>
              </a>
              <ul class="dropdown-menu" data-wt-menu="<?= $pageKey ?>">
                <?php foreach ($pageInfo['children'] as $childKey => $childInfo): ?>
                  <?php if (empty($childInfo['menu'])) continue; ?>
                  <?php if (!canShowInMenu($childKey, $childInfo, $isAdmin, $userPermissions)) continue; ?>
                  <li>
                    <a class="dropdown-item <?= ($currentPage == $childKey) ? 'active' : '' ?>"
                       href="?page=<?= $childKey ?>">
                      <i class="<?= $childInfo['icon'] ?> me-2"></i><?= $childInfo['label'] ?>
                    </a>
                  </li>
                <?php endforeach; ?>
              </ul>
            </li>
          <?php else: ?>
            <?php // ── LINK DIRETO (sem submenu) ── ?>
            <?php if (!canShowInMenu($pageKey, $pageInfo, $isAdmin, $userPermissions)) continue; ?>
            <li class="nav-item me-1">
              <a class="nav-link <?= ($currentPage == $pageKey) ? 'active' : '' ?>"
                 href="<?= $pageKey === 'home' ? '?' : '?page=' . $pageKey ?>">
                <i class="<?= $pageInfo['icon'] ?> me-1"></i><?= $pageInfo['label'] ?>
              </a>
            </li>
          <?php endif; ?>

        <?php endforeach; ?>
      </ul>

      <!-- ── Menu Direito (Perfil / Config / Sair) ── -->
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center gap-1">
        <?php if ($headerDelayedCount > 0): ?>
        <li class="nav-item dropdown">
          <a href="#" class="nav-link nav-icon-btn dropdown-toggle" 
             role="button" data-bs-toggle="dropdown" aria-expanded="false" data-bs-auto-close="outside"
             title="<?= $headerDelayedCount ?> pedido(s) atrasado(s)" id="bellDropdownToggle">
            <i class="fas fa-bell"></i>
            <span class="notification-badge">
                <?= $headerDelayedCount ?>
            </span>
          </a>
          <div class="dropdown-menu dropdown-menu-end p-0" id="bellDropdownMenu" 
               style="width:420px;max-height:500px;overflow-y:auto;background:#fff !important;border:1px solid #dee2e6 !important;box-shadow:0 8px 32px rgba(0,0,0,0.18) !important;">
            <!-- Header do dropdown -->
            <div class="px-3 py-2 border-bottom" style="background:#fff3cd;">
                <div class="d-flex justify-content-between align-items-center">
                    <strong class="text-dark"><i class="fas fa-exclamation-triangle text-warning me-1"></i> Pedidos Atrasados</strong>
                    <span class="badge bg-danger rounded-pill"><?= $headerDelayedCount ?></span>
                </div>
            </div>

            <?php if (!empty($headerDelayedOrders)): ?>
            <!-- Lista de pedidos atrasados -->
            <div class="px-2 py-1">
                <small class="text-muted fw-bold px-2"><i class="fas fa-clock me-1"></i>PEDIDOS NA ETAPA ALÉM DO PRAZO</small>
            </div>
            <?php 
            $stageLabelsH = [
                'contato' => ['label' => 'Contato', 'color' => '#9b59b6', 'icon' => 'fas fa-phone'],
                'orcamento' => ['label' => 'Orçamento', 'color' => '#3498db', 'icon' => 'fas fa-file-invoice-dollar'],
                'venda' => ['label' => 'Venda', 'color' => '#2ecc71', 'icon' => 'fas fa-handshake'],
                'producao' => ['label' => 'Produção', 'color' => '#e67e22', 'icon' => 'fas fa-industry'],
                'preparacao' => ['label' => 'Preparação', 'color' => '#1abc9c', 'icon' => 'fas fa-boxes-packing'],
                'envio' => ['label' => 'Envio/Entrega', 'color' => '#e74c3c', 'icon' => 'fas fa-truck'],
                'financeiro' => ['label' => 'Financeiro', 'color' => '#f39c12', 'icon' => 'fas fa-coins'],
            ];
            foreach (array_slice($headerDelayedOrders, 0, 10) as $dOrder): 
                $dStage = $stageLabelsH[$dOrder['pipeline_stage']] ?? ['label' => $dOrder['pipeline_stage'], 'color' => '#999', 'icon' => 'fas fa-circle'];
                $priorityEmoji = ['urgente' => '🔴', 'alta' => '🟡', 'normal' => '🔵', 'baixa' => '🟢'];
                $pEmoji = $priorityEmoji[$dOrder['priority'] ?? 'normal'] ?? '🔵';
            ?>
            <a href="?page=pipeline&action=detail&id=<?= $dOrder['id'] ?>" 
               class="dropdown-item px-3 py-2 border-bottom" style="white-space:normal;color:#333 !important;">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="d-flex align-items-start gap-2">
                        <div class="flex-shrink-0 mt-1">
                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle" 
                                  style="width:28px;height:28px;background:<?= $dStage['color'] ?>20;">
                                <i class="<?= $dStage['icon'] ?>" style="color:<?= $dStage['color'] ?>;font-size:0.7rem;"></i>
                            </span>
                        </div>
                        <div>
                            <div class="fw-bold" style="font-size:0.82rem;color:#333;">
                                <?= $pEmoji ?> #<?= str_pad($dOrder['id'], 4, '0', STR_PAD_LEFT) ?>
                                <?php if (!empty($dOrder['customer_name'])): ?>
                                — <?= htmlspecialchars(mb_substr($dOrder['customer_name'], 0, 20)) ?>
                                <?php endif; ?>
                            </div>
                            <div style="font-size:0.72rem;">
                                <span class="badge px-1 py-0" style="background:<?= $dStage['color'] ?>;color:#fff;font-size:0.65rem;">
                                    <?= $dStage['label'] ?>
                                </span>
                                <span class="text-danger fw-bold ms-1">
                                    <i class="fas fa-clock me-1"></i><?= $dOrder['delay_hours'] ?>h atrasado
                                </span>
                                <span class="text-muted">(<?= $dOrder['hours_in_stage'] ?>h / máx <?= $dOrder['max_hours'] ?>h)</span>
                            </div>
                            <?php if (!empty($dOrder['deadline'])): ?>
                            <div style="font-size:0.65rem;" class="text-muted">
                                <i class="fas fa-calendar me-1"></i>Prazo: <?= date('d/m/Y', strtotime($dOrder['deadline'])) ?>
                                <?php if (strtotime($dOrder['deadline']) < time()): ?>
                                <span class="text-danger fw-bold">— VENCIDO</span>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <i class="fas fa-chevron-right text-muted mt-2" style="font-size:0.6rem;"></i>
                </div>
            </a>
            <?php endforeach; ?>
            
            <?php if (count($headerDelayedOrders) > 10): ?>
            <div class="text-center py-2 small text-muted">
                <i class="fas fa-ellipsis-h me-1"></i>e mais <?= count($headerDelayedOrders) - 10 ?> pedido(s) atrasado(s)
            </div>
            <?php endif; ?>
            <?php endif; ?>

            <?php if (!empty($headerDelayedProducts)): ?>
            <!-- Produtos em produção (onde estão parados) -->
            <div class="px-2 py-1 border-top" style="background:#f8f9fa;">
                <small class="text-muted fw-bold px-2"><i class="fas fa-industry me-1"></i>PRODUTOS EM PRODUÇÃO (SETOR ATUAL)</small>
            </div>
            <?php foreach (array_slice($headerDelayedProducts, 0, 8) as $dProd): ?>
            <a href="?page=pipeline&action=detail&id=<?= $dProd['order_id'] ?>" 
               class="dropdown-item px-3 py-2 border-bottom" style="white-space:normal;color:#333 !important;">
                <div class="d-flex align-items-center gap-2">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle" 
                          style="width:24px;height:24px;min-width:24px;background:<?= $dProd['sector_color'] ?? '#e67e22' ?>20;">
                        <i class="fas fa-box" style="color:<?= $dProd['sector_color'] ?? '#e67e22' ?>;font-size:0.6rem;"></i>
                    </span>
                    <div style="font-size:0.78rem;">
                        <div class="fw-bold" style="color:#333;">
                            <?= htmlspecialchars(mb_substr($dProd['product_name'], 0, 25)) ?> 
                            <span class="text-muted fw-normal">(×<?= $dProd['quantity'] ?>)</span>
                        </div>
                        <div style="font-size:0.68rem;">
                            <span class="text-muted">Pedido #<?= str_pad($dProd['order_id'], 4, '0', STR_PAD_LEFT) ?></span>
                            <?php if (!empty($dProd['customer_name'])): ?>
                            — <span class="text-muted"><?= htmlspecialchars(mb_substr($dProd['customer_name'], 0, 15)) ?></span>
                            <?php endif; ?>
                            <span class="badge px-1 py-0 ms-1" style="background:<?= $dProd['sector_color'] ?? '#e67e22' ?>;color:#fff;font-size:0.6rem;">
                                <i class="fas fa-map-pin me-1"></i><?= htmlspecialchars($dProd['sector_name']) ?>
                            </span>
                        </div>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
            <?php if (count($headerDelayedProducts) > 8): ?>
            <div class="text-center py-1 small text-muted border-top">
                <i class="fas fa-ellipsis-h me-1"></i>e mais <?= count($headerDelayedProducts) - 8 ?> produto(s) em produção
            </div>
            <?php endif; ?>
            <?php endif; ?>

            <!-- Footer -->
            <div class="px-3 py-2 border-top text-center" style="background:#f8f9fa;">
                <a href="?page=pipeline" class="text-decoration-none small fw-bold" style="color:#3498db;">
                    <i class="fas fa-columns me-1"></i>Ver Pipeline Completo
                </a>
            </div>
          </div>
        </li>
        <?php elseif (isset($_SESSION['user_id'])): ?>
        <li class="nav-item">
          <a href="#" class="nav-link nav-icon-btn" title="Sem avisos" style="opacity:0.4;">
            <i class="fas fa-bell"></i>
          </a>
        </li>
        <?php endif; ?>
        <li class="nav-item d-none d-lg-block"><span class="nav-divider"></span></li>
        <li class="nav-item">
          <a href="?page=profile"
             class="nav-link <?= ($currentPage == 'profile') ? 'active' : '' ?>"
             title="Meu Perfil">
            <i class="fas fa-user-circle"></i>
            <?= $_SESSION['user_name'] ?? 'Visitante' ?>
            <span class="user-badge <?= (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') ? 'admin' : '' ?>">
              <?= isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin' ? 'Admin' : 'Usuário' ?>
            </span>
          </a>
        </li>
        <?php if($isAdmin || in_array('settings', $userPermissions)): ?>
        <li class="nav-item">
          <a href="?page=settings"
             class="nav-link nav-icon-btn <?= ($currentPage == 'settings') ? 'active' : '' ?>"
             title="Configurações">
            <i class="fas fa-building"></i>
          </a>
        </li>
        <?php endif; ?>
        <?php if($isAdmin || in_array('users', $userPermissions)): ?>
        <li class="nav-item">
          <a href="?page=users"
             class="nav-link nav-icon-btn <?= ($currentPage == 'users') ? 'active' : '' ?>"
             title="Gestão de Usuários">
            <i class="fas fa-users-cog"></i>
          </a>
        </li>
        <?php endif; ?>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle btn-logout" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-sign-out-alt"></i><span class="d-none d-lg-inline">Sair</span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li>
              <a class="dropdown-item" href="?page=login&action=logout">
                <i class="fas fa-sign-out-alt me-2"></i>Sair do sistema
              </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <a class="dropdown-item wt-help-trigger" href="javascript:void(0);" onclick="window.aktiWalkthrough.start(0);">
                    <i class="fas fa-question-circle me-2 text-info"></i>Tour Guiado
                </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <a class="dropdown-item text-danger" href="?page=logout">
                    <i class="fas fa-sign-out-alt me-2"></i>Sair
                </a>
            </li>
          </ul>
        </li>
      </ul>

    </div>
  </div>
</nav>

<div class="container-fluid">
    <div class="row">
        <!-- Main Content -->
        <main class="col-md-12 ms-sm-auto px-md-4 py-4 main-bg">
