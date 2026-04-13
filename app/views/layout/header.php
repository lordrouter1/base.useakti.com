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
    <meta name="api-base-url" content="<?= akti_env('AKTI_API_URL') ?: 'http://localhost:3000' ?>">

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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha384-iw3OoTErCYJJB9mCa8LNS2hbsQ7M3C0EpIsO/H5+EGAkPGc6rk+V8i04oW/K5xq0" crossorigin="anonymous">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" integrity="sha384-OxWqvePLOm0AAoo759Ls7uD8ysM4N0fSXEE+QUY3pkVXBtkv6jkKNsPMC0KFMxWe" crossorigin="anonymous">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" integrity="sha384-OXVF05DQEe311p6ohU11NwlnX08FzMCsyoXzGOaL+83dKAb3qS17yZJxESl8YrJQ" crossorigin="anonymous" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" integrity="sha384-IrMr0LFnIMa9H6HhC5VVqVuWNEIwspnRLKQc0SUyPj4Cy4s02DiWDZEoJOo5WNK6" crossorigin="anonymous" />
    <!-- Design System (must load BEFORE other CSS) -->
    <link rel="stylesheet" href="<?= asset('assets/css/design-system.css') ?>">
    <!-- Custom CSS (cache busting via asset()) -->
    <link rel="stylesheet" href="<?= asset('assets/css/theme.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">
    <link href="<?= asset('assets/css/walkthrough.css') ?>" rel="stylesheet">
    <!-- Module-specific CSS (loaded per page) -->
    <?php
    $__currentPageCss = $_GET['page'] ?? 'dashboard';
    $__moduleCssMap = [
        'customers'            => 'assets/css/modules/customers.css',
        'products'             => 'assets/css/modules/products.css',
        'pipeline'             => 'assets/css/modules/pipeline.css',
        'financial'            => 'assets/css/modules/financial.css',
        'orders'               => 'assets/css/modules/orders.css',
        'dashboard'            => 'assets/css/modules/dashboard.css',
        'stock'                => 'assets/css/modules/stock.css',
        'reports'              => 'assets/css/modules/reports.css',
        'nfe'                  => 'assets/css/modules/nfe.css',
        'nfe_documents'        => 'assets/css/modules/nfe.css',
        'nfe_credentials'      => 'assets/css/modules/nfe.css',
        'commissions'          => 'assets/css/modules/commissions.css',
        'commissions_formas'   => 'assets/css/modules/commissions.css',
        'commissions_historico'=> 'assets/css/modules/commissions.css',
        'notifications'        => 'assets/css/modules/notifications.css',
        'users'                => 'assets/css/modules/users.css',
        'settings'             => 'assets/css/modules/settings.css',
        'home'                 => 'assets/css/modules/home.css',
        'dashboard_widgets'    => 'assets/css/modules/settings.css',
        'production_board'     => 'assets/css/modules/production-board.css',
        'sectors'              => 'assets/css/modules/pipeline.css',
        'installments'         => 'assets/css/modules/financial.css',
        'categories'           => 'assets/css/modules/products.css',
        'payment_gateways'     => 'assets/css/modules/financial.css',
        'portal_admin'         => 'assets/css/modules/users.css',
        'price_tables'         => 'assets/css/modules/settings.css',
        'profile'              => 'assets/css/modules/users.css',
        'financial_payments'   => 'assets/css/modules/financial.css',
        'financial_transactions' => 'assets/css/modules/financial.css',
        'agenda'               => 'assets/css/modules/orders.css',
        'walkthrough'          => 'assets/css/modules/home.css',
    ];
    if (isset($__moduleCssMap[$__currentPageCss]) && file_exists($__moduleCssMap[$__currentPageCss])):
    ?>
    <link rel="stylesheet" href="<?= asset($__moduleCssMap[$__currentPageCss]) ?>">
    <?php endif; ?>
    <!-- Dark Mode: apply theme BEFORE render to prevent FOUC -->
    <style>
    /* Critical CSS — inline above-the-fold styles to prevent FOUC */
    body{font-family:'Inter',system-ui,sans-serif;background:var(--bg-body,#f1f5f9);color:var(--text-main,#1e293b);margin:0}
    .app-navbar{height:var(--navbar-height,64px);background:var(--primary-color,#1e293b);position:sticky;top:0;z-index:1030;display:flex;align-items:center}
    .app-sidebar{width:260px;background:var(--bg-card,#fff);border-right:1px solid var(--border-color,#e2e8f0);position:fixed;top:var(--navbar-height,64px);bottom:0;overflow-y:auto;z-index:1020}
    .app-content{margin-left:260px;padding:1.5rem;min-height:calc(100vh - var(--navbar-height,64px))}
    @media(max-width:991.98px){.app-sidebar{transform:translateX(-100%)}.app-content{margin-left:0}}
    [data-theme="dark"] body,.dark body{background:#1A1A2E;color:#E8E8E8}
    [data-theme="dark"] .app-sidebar{background:#16213E;border-color:#2C3E50}
    </style>
    <script src="<?= asset('assets/js/components/theme-toggle.js') ?>"></script>
    <script src="<?= asset('assets/js/utils/fetch-timeout.js') ?>"></script>
    <?= \Akti\Core\ModuleBootloader::injectJS() ?>
</head>
<body>
<!-- Skip to main content link for keyboard navigation -->
<a href="#main-content" class="visually-hidden-focusable position-fixed top-0 start-0 p-3 bg-primary text-white z-3">Ir para o conteúdo principal</a>

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

    // ── HeaderDataService: toda lógica SQL extraída para o Service ──
    $isAdmin = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');
    $headerData = $GLOBALS['_headerData'] ?? null;

    if ($headerData === null && isset($_SESSION['user_id'])) {
        $headerService = new \Akti\Services\HeaderDataService(Database::getInstance());
        $headerData = $headerService->getAllHeaderData(
            (int)$_SESSION['user_id'],
            !empty($_SESSION['group_id']) ? (int)$_SESSION['group_id'] : null,
            $isAdmin
        );
    }

    $userPermissions      = $headerData['userPermissions'] ?? [];
    $headerDelayedCount   = $headerData['delayedCount'] ?? 0;
    $headerDelayedOrders  = $headerData['delayedOrders'] ?? [];
    $headerDelayedProducts = $headerData['delayedProducts'] ?? [];
    
    /**
     * Verifica se o usuário pode ver determinada página no menu.
     */
    if (!function_exists('canShowInMenu')) {
    function canShowInMenu($pageKey, $pageInfo, $isAdmin, $userPermissions) {
        if (!\Akti\Core\ModuleBootloader::canAccessPage($pageKey)) return false;
        if (empty($pageInfo['permission'])) return true;
        if ($isAdmin) return true;
        // Use permission_alias if defined (e.g., financial_payments -> financial)
        $checkKey = $pageInfo['permission_alias'] ?? $pageKey;
        return in_array($checkKey, $userPermissions);
    }
    }

    /**
     * Verifica se pelo menos um filho de um submenu é visível para o usuário.
     */
    if (!function_exists('hasVisibleChild')) {
    function hasVisibleChild($children, $isAdmin, $userPermissions) {
        foreach ($children as $childKey => $childInfo) {
            if (!empty($childInfo['menu']) && canShowInMenu($childKey, $childInfo, $isAdmin, $userPermissions)) {
                return true;
            }
        }
        return false;
    }
    }

    /**
     * Verifica se a página atual está dentro de um submenu (para destacar o dropdown).
     */
    if (!function_exists('isChildActive')) {
    function isChildActive($children, $currentPage) {
        return isset($children[$currentPage]);
    }
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
        <!-- Command Palette Trigger -->
        <li class="nav-item d-none d-lg-block">
          <button type="button" class="akti-btn-icon" id="cmdPaletteTrigger"
                  onclick="if(window.AktiCommandPalette)AktiCommandPalette.open();"
                  title="Busca rápida (Ctrl+K)" aria-label="Busca rápida">
            <i class="fas fa-search"></i>
          </button>
        </li>
        <!-- Dark Mode Toggle (cycles: Light → Dark → Auto) -->
        <li class="nav-item">
          <button type="button" class="akti-btn-icon" id="themeToggleBtn"
                  onclick="if(window.AktiTheme)AktiTheme.toggle();"
                  title="Alternar tema (Claro / Escuro / Auto)" aria-label="Alternar modo de tema">
            <i id="themeToggleIcon" class="fas fa-moon"></i>
          </button>
        </li>
        <!-- Notifications Bell (unified: system notifications + delayed orders) -->
        <?php if (isset($_SESSION['user_id'])): ?>
        <li class="nav-item dropdown" id="notifBellContainer">
          <a href="#" class="nav-link nav-icon-btn dropdown-toggle" 
             role="button" data-bs-toggle="dropdown" aria-expanded="false" data-bs-auto-close="outside"
             title="Notificações" id="notifBellToggle">
            <i class="fas fa-bell"></i>
            <span class="notification-badge" id="notifBadge" style="<?= $headerDelayedCount > 0 ? '' : 'display:none;' ?>"
                  data-delayed-count="<?= $headerDelayedCount ?>"><?= $headerDelayedCount > 0 ? $headerDelayedCount : '0' ?></span>
          </a>
          <div class="dropdown-menu dropdown-menu-end p-0 dropdown-themed" id="notifDropdownMenu"
               style="width:420px;max-height:500px;overflow-y:auto;">
            <div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center bg-section-muted">
                <strong class="text-dark" style="font-size:.85rem;"><i class="fas fa-bell text-primary me-1"></i>Notificações</strong>
                <div class="d-flex gap-2">
                    <a href="#" id="notifMarkAllRead" class="text-muted small text-decoration-none" title="Marcar todas como lidas" style="font-size:.72rem;">
                        <i class="fas fa-check-double me-1"></i>Marcar lidas
                    </a>
                </div>
            </div>

            <?php if ($headerDelayedCount > 0): ?>
            <!-- ── Pedidos Atrasados (server-side) ── -->
            <div class="px-3 py-2 border-bottom bg-section-yellow">
                <div class="d-flex justify-content-between align-items-center">
                    <strong class="text-dark" style="font-size:.82rem;"><i class="fas fa-exclamation-triangle text-warning me-1"></i>Pedidos Atrasados</strong>
                    <span class="badge bg-danger rounded-pill" style="font-size:.65rem;"><?= $headerDelayedCount ?></span>
                </div>
            </div>

            <?php if (!empty($headerDelayedOrders)): ?>
            <div class="px-2 py-1">
                <small class="text-muted fw-bold px-2" style="font-size:.65rem;"><i class="fas fa-clock me-1"></i>PEDIDOS NA ETAPA ALÉM DO PRAZO</small>
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
               class="dropdown-item px-3 py-2 border-bottom" style="white-space:normal;">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="d-flex align-items-start gap-2">
                        <div class="flex-shrink-0 mt-1">
                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle" 
                                  style="width:28px;height:28px;background:<?= $dStage['color'] ?>20;">
                                <i class="<?= $dStage['icon'] ?>" style="color:<?= $dStage['color'] ?>;font-size:0.7rem;"></i>
                            </span>
                        </div>
                        <div>
                            <div class="fw-bold" style="font-size:0.82rem;">
                                <?= $pEmoji ?> #<?= str_pad($dOrder['id'], 4, '0', STR_PAD_LEFT) ?>
                                <?php if (!empty($dOrder['customer_name'])): ?>
                                — <?= e(mb_substr($dOrder['customer_name'], 0, 20)) ?>
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
            <div class="px-2 py-1 border-top bg-section-muted">
                <small class="text-muted fw-bold px-2" style="font-size:.65rem;"><i class="fas fa-industry me-1"></i>PRODUTOS EM PRODUÇÃO (SETOR ATUAL)</small>
            </div>
            <?php foreach (array_slice($headerDelayedProducts, 0, 8) as $dProd): ?>
            <a href="?page=pipeline&action=detail&id=<?= $dProd['order_id'] ?>" 
               class="dropdown-item px-3 py-2 border-bottom" style="white-space:normal;">
                <div class="d-flex align-items-center gap-2">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle" 
                          style="width:24px;height:24px;min-width:24px;background:<?= $dProd['sector_color'] ?? '#e67e22' ?>20;">
                        <i class="fas fa-box" style="color:<?= $dProd['sector_color'] ?? '#e67e22' ?>;font-size:0.6rem;"></i>
                    </span>
                    <div style="font-size:0.78rem;">
                        <div class="fw-bold">
                            <?= e(mb_substr($dProd['product_name'], 0, 25)) ?> 
                            <span class="text-muted fw-normal">(×<?= $dProd['quantity'] ?>)</span>
                        </div>
                        <div style="font-size:0.68rem;">
                            <span class="text-muted">Pedido #<?= str_pad($dProd['order_id'], 4, '0', STR_PAD_LEFT) ?></span>
                            <?php if (!empty($dProd['customer_name'])): ?>
                            — <span class="text-muted"><?= e(mb_substr($dProd['customer_name'], 0, 15)) ?></span>
                            <?php endif; ?>
                            <span class="badge px-1 py-0 ms-1" style="background:<?= $dProd['sector_color'] ?? '#e67e22' ?>;color:#fff;font-size:0.6rem;">
                                <i class="fas fa-map-pin me-1"></i><?= e($dProd['sector_name']) ?>
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

            <!-- Link para pipeline -->
            <div class="px-3 py-2 border-top text-center bg-section-muted">
                <a href="?page=pipeline" class="text-decoration-none small fw-bold text-blue">
                    <i class="fas fa-columns me-1"></i>Ver Pipeline Completo
                </a>
            </div>
            <?php endif; ?>

            <!-- ── Notificações do sistema (carregadas via AJAX) ── -->
            <?php if ($headerDelayedCount > 0): ?>
            <div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center bg-section-muted">
                <strong class="text-dark" style="font-size:.82rem;"><i class="fas fa-inbox text-primary me-1"></i>Outras Notificações</strong>
            </div>
            <?php endif; ?>
            <div id="notifDropdownBody">
                <div class="text-center text-muted py-4" style="font-size:.82rem;">
                    <i class="fas fa-spinner fa-spin me-1"></i>Carregando...
                </div>
            </div>
            <div class="px-3 py-2 border-top text-center bg-section-muted">
                <a href="?page=notifications" class="text-decoration-none small fw-bold text-blue">
                    <i class="fas fa-list me-1"></i>Ver todas as notificações
                </a>
            </div>
          </div>
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
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle btn-logout" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fa-solid fa-gear"></i>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <?php if($isAdmin || in_array('users', $userPermissions)): ?>
            <li>
              <a href="?page=users" class="dropdown-item" title="Gestão de Usuários">
                <i class="fas fa-users-cog me-2"></i>Usuários
              </a>
            </li>
            <?php endif; ?>
            <?php if($isAdmin || in_array('settings', $userPermissions)): ?>
            <li>
              <a href="?page=settings" class="dropdown-item" title="Configurações">
                <i class="fas fa-building me-2"></i>Configurações
              </a>
            </li>
            <?php endif; ?>
            <?php if($isAdmin || in_array('portal_admin', $userPermissions)): ?>
            <li>
              <a href="?page=portal_admin" class="dropdown-item" title="Admin do Portal">
                <i class="fas fa-globe me-2"></i>Portal do Cliente
              </a>
            </li>
            <?php endif; ?>
            <li><hr class="dropdown-divider"></li>
            <li>
                <a class="dropdown-item wt-help-trigger" href="javascript:void(0);" onclick="window.aktiWalkthrough.start(0);">
                    <i class="fas fa-question-circle me-2 text-info"></i>Tour Guiado
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="javascript:void(0);" onclick="if(window.AktiShortcuts)AktiShortcuts.showHelp();">
                    <i class="fas fa-keyboard me-2 text-info"></i>Atalhos de Teclado
                </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <a class="dropdown-item text-danger" href="?page=login&action=logout">
                <i class="fas fa-sign-out-alt me-2"></i>Sair do sistema
                </a>
            </li>
          </ul>
        </li>
      </ul>

    </div>
  </div>
</nav>

<!-- Skip link for accessibility -->
<a href="#main-content" class="akti-skip-link">Ir para conteúdo principal</a>

<div class="container-fluid">
    <div class="row">
        <!-- Main Content -->
        <main class="col-md-12 ms-sm-auto px-md-4 py-4 main-bg" id="main-content">
            <?php
            // Render contextual breadcrumb
            if (isset($_SESSION['user_id'])) {
                require 'app/views/components/breadcrumb.php';
            }
            ?>
