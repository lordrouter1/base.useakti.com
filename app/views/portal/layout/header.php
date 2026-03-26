<?php
/**
 * Portal do Cliente — Header Principal (com topbar + bottom nav)
 * Layout mobile-first para páginas autenticadas.
 *
 * Variáveis esperadas: $company (array de configurações da empresa)
 */
$companyName = $company['company_name'] ?? 'Akti';
$companyLogo = $company['company_logo'] ?? '';
$customerName = $_SESSION['portal_customer_name'] ?? 'Cliente';
$currentAction = $_GET['action'] ?? 'dashboard';
$customerAvatar = $_SESSION['portal_customer_avatar'] ?? '';
$customerInitial = strtoupper(substr($customerName, 0, 1));
// Mensagens não lidas: pode ser passada pelo controller ou calculada sob demanda
if (!isset($unreadMessages)) {
    $unreadMessages = 0;
    if (!empty($_SESSION['portal_customer_id'])) {
        try {
            $unreadMessages = (new \Akti\Models\PortalMessage(
                (new \Database())->getConnection()
            ))->countUnread((int) $_SESSION['portal_customer_id']);
        } catch (\Throwable $e) {
            // silenciar
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= eAttr(\Akti\Services\PortalLang::getLang()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title><?= e($companyName) ?> — <?= __p('portal_title') ?></title>
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#3b82f6">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="<?= eAttr($companyName) ?>">
    <?= csrf_meta() ?>

    <!-- Manifest PWA -->
    <link rel="manifest" href="portal-manifest.json">
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
    <!-- Portal CSS -->
    <link rel="stylesheet" href="assets/css/portal.css?v=<?= filemtime(__DIR__ . '/../../../../assets/css/portal.css') ?>">
</head>
<body class="portal-body">

    <!-- ═══ Top Bar ═══ -->
    <nav class="portal-topbar">
        <div class="portal-topbar-inner">
            <div class="portal-topbar-left">
                <?php if ($companyLogo): ?>
                    <img src="<?= eAttr($companyLogo) ?>" alt="<?= eAttr($companyName) ?>" class="portal-topbar-logo">
                <?php else: ?>
                    <span class="portal-topbar-brand"><?= e($companyName) ?></span>
                <?php endif; ?>
            </div>
            <nav class="portal-desktop-nav">
                <a href="?page=portal&action=dashboard"
                   class="portal-desktop-link <?= in_array($currentAction, ['dashboard','index']) ? 'active' : '' ?>">
                    <i class="fas fa-house me-1"></i> <?= __p('nav_home') ?>
                </a>
                <a href="?page=portal&action=orders"
                   class="portal-desktop-link <?= in_array($currentAction, ['orders','orderDetail','approveOrder']) ? 'active' : '' ?>">
                    <i class="fas fa-box me-1"></i> <?= __p('nav_orders') ?>
                </a>
                <a href="?page=portal&action=newOrder"
                   class="portal-desktop-link <?= $currentAction === 'newOrder' ? 'active' : '' ?>">
                    <i class="fas fa-circle-plus me-1"></i> <?= __p('nav_new_order') ?>
                </a>
                <a href="?page=portal&action=installments"
                   class="portal-desktop-link <?= in_array($currentAction, ['installments','installmentDetail']) ? 'active' : '' ?>">
                    <i class="fas fa-wallet me-1"></i> <?= __p('nav_financial') ?>
                </a>
                <a href="?page=portal&action=tracking"
                   class="portal-desktop-link <?= $currentAction === 'tracking' ? 'active' : '' ?>">
                    <i class="fas fa-truck me-1"></i> <?= __p('tracking_title') ?>
                </a>
                <a href="?page=portal&action=messages"
                   class="portal-desktop-link portal-desktop-link-badge <?= $currentAction === 'messages' ? 'active' : '' ?>">
                    <i class="fas fa-comments me-1"></i> <?= __p('messages_title') ?>
                    <?php if ($unreadMessages > 0): ?>
                        <span class="portal-nav-badge"><?= (int) $unreadMessages ?></span>
                    <?php endif; ?>
                </a>
                <a href="?page=portal&action=documents"
                   class="portal-desktop-link <?= $currentAction === 'documents' ? 'active' : '' ?>">
                    <i class="fas fa-file-alt me-1"></i> <?= __p('documents_title') ?>
                </a>
                <a href="?page=portal&action=profile"
                   class="portal-desktop-link <?= $currentAction === 'profile' ? 'active' : '' ?>">
                    <i class="far fa-user me-1"></i> <?= __p('nav_profile') ?>
                </a>
            </nav>
            <div class="portal-topbar-right">
                <span class="portal-topbar-greeting d-none d-sm-inline">
                    <?php if (!empty($customerAvatar) && file_exists($customerAvatar)): ?>
                        <img src="<?= eAttr($customerAvatar) ?>" alt="" class="portal-topbar-avatar">
                    <?php endif; ?>
                    <?= e($customerName) ?>
                </span>
                <a href="?page=portal&action=logout" class="portal-topbar-btn" title="<?= __p('profile_logout') ?>">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </nav>

    <!-- ═══ Conteúdo Principal ═══ -->
    <main class="portal-content">
