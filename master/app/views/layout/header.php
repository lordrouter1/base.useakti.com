<?php
/**
 * Layout: Header (Sidebar + Topbar)
 */
$currentPage = $_GET['page'] ?? 'dashboard';
$adminName = $_SESSION['admin_name'] ?? 'Admin';
$adminInitials = strtoupper(substr($adminName, 0, 2));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Akti Master Admin' ?></title>
    <link rel="icon" href="logos/akti-icon-dark.ico" type="image/x-icon">
    <?php if (function_exists('master_csrf_meta')): ?>
        <?= master_csrf_meta() ?>
    <?php endif; ?>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="app-wrapper">
        <!-- Sidebar Overlay (mobile) -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <img src="logos/akti-logo-dark-nBg.svg" alt="Akti" style="width:100% !important;height:auto !important;">
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section">Principal</div>
                
                <div class="nav-item">
                    <a href="?page=dashboard" class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                        <i class="fas fa-chart-pie"></i>
                        Dashboard
                    </a>
                </div>

                <div class="nav-section">Gestão</div>

                <div class="nav-item">
                    <a href="?page=plans" class="nav-link <?= $currentPage === 'plans' ? 'active' : '' ?>">
                        <i class="fas fa-layer-group"></i>
                        Planos
                    </a>
                </div>

                <div class="nav-item">
                    <a href="?page=clients" class="nav-link <?= $currentPage === 'clients' ? 'active' : '' ?>">
                        <i class="fas fa-building"></i>
                        Clientes
                    </a>
                </div>

                <div class="nav-item">
                    <a href="?page=tickets" class="nav-link <?= $currentPage === 'tickets' ? 'active' : '' ?>">
                        <i class="fas fa-headset"></i>
                        Tickets
                    </a>
                </div>

                <div class="nav-section">Ferramentas</div>

                <div class="nav-item">
                    <a href="?page=migrations" class="nav-link <?= $currentPage === 'migrations' && ($_GET['action'] ?? '') !== 'users' ? 'active' : '' ?>">
                        <i class="fas fa-database"></i>
                        Migrações
                    </a>
                </div>

                <div class="nav-item">
                    <a href="?page=migrations&action=users" class="nav-link <?= $currentPage === 'migrations' && ($_GET['action'] ?? '') === 'users' ? 'active' : '' ?>">
                        <i class="fas fa-users-cog"></i>
                        Usuários Tenant
                    </a>
                </div>

                <div class="nav-item">
                    <a href="?page=git" class="nav-link <?= $currentPage === 'git' ? 'active' : '' ?>">
                        <i class="fab fa-git-alt"></i>
                        Versionamento
                    </a>
                </div>

                <div class="nav-item">
                    <a href="?page=backup" class="nav-link <?= $currentPage === 'backup' ? 'active' : '' ?>">
                        <i class="fas fa-database"></i>
                        Backups
                    </a>
                </div>

                <div class="nav-item">
                    <a href="?page=logs" class="nav-link <?= $currentPage === 'logs' ? 'active' : '' ?>">
                        <i class="fas fa-file-lines"></i>
                        Logs Nginx
                    </a>
                </div>

                <div class="nav-section">Sistema</div>

                <div class="nav-item">
                    <a href="?page=login&action=logout" class="nav-link">
                        <i class="fas fa-right-from-bracket"></i>
                        Sair
                    </a>
                </div>
            </nav>

            <div class="sidebar-footer">
                <div class="admin-info">
                    <div class="admin-avatar"><?= $adminInitials ?></div>
                    <div>
                        <strong><?= htmlspecialchars($adminName) ?></strong><br>
                        <small style="opacity:0.6">Administrador</small>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Topbar -->
            <div class="topbar">
                <div class="d-flex align-items-center gap-3">
                    <button class="sidebar-toggle" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div>
                        <h1><?= $pageTitle ?? 'Dashboard' ?></h1>
                        <?php if (isset($pageSubtitle)): ?>
                            <small class="text-muted"><?= $pageSubtitle ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="topbar-actions">
                    <?php if (isset($topbarActions)): ?>
                        <?= $topbarActions ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Content Area -->
            <div class="content-area">
