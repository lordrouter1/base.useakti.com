<?php
/**
 * Layout: Header (Sidebar + Topbar) — Minimalist
 */
$currentPage = str_replace('master_', '', $_GET['page'] ?? 'dashboard');
$currentAction = $_GET['action'] ?? '';
$adminName = $_SESSION['user_name'] ?? 'Admin';
$adminInitials = strtoupper(substr($adminName, 0, 2));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Akti Master' ?></title>
    <link rel="icon" href="assets/logos/akti-icon-dark.ico" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="assets/css/master.css" rel="stylesheet">
</head>
<body>
    <div class="app-wrapper">
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <img src="assets/logos/akti-logo-dark-nBg.svg" alt="Akti">
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section">Principal</div>
                <div class="nav-item">
                    <a href="?page=master_dashboard" class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                        <i class="fas fa-chart-pie"></i> Dashboard
                    </a>
                </div>

                <div class="nav-section">Gestão</div>
                <div class="nav-item">
                    <a href="?page=master_plans" class="nav-link <?= $currentPage === 'plans' ? 'active' : '' ?>">
                        <i class="fas fa-layer-group"></i> Planos
                    </a>
                </div>
                <div class="nav-item">
                    <a href="?page=master_clients" class="nav-link <?= $currentPage === 'clients' ? 'active' : '' ?>">
                        <i class="fas fa-building"></i> Clientes
                    </a>
                </div>
                <div class="nav-item">
                    <a href="?page=master_admins" class="nav-link <?= $currentPage === 'admins' ? 'active' : '' ?>">
                        <i class="fas fa-user-shield"></i> Administradores
                    </a>
                </div>

                <div class="nav-section">Ferramentas</div>
                <div class="nav-item">
                    <a href="?page=master_migrations" class="nav-link <?= $currentPage === 'migrations' && !in_array($currentAction, ['users', 'history']) ? 'active' : '' ?>">
                        <i class="fas fa-database"></i> Migrações
                    </a>
                </div>
                <div class="nav-item">
                    <a href="?page=master_migrations&action=history" class="nav-link <?= $currentPage === 'migrations' && $currentAction === 'history' ? 'active' : '' ?>">
                        <i class="fas fa-clock-rotate-left"></i> Histórico SQL
                    </a>
                </div>
                <div class="nav-item">
                    <a href="?page=master_migrations&action=users" class="nav-link <?= $currentPage === 'migrations' && $currentAction === 'users' ? 'active' : '' ?>">
                        <i class="fas fa-users-cog"></i> Usuários Tenant
                    </a>
                </div>
                <div class="nav-item">
                    <a href="?page=master_git" class="nav-link <?= $currentPage === 'git' ? 'active' : '' ?>">
                        <i class="fab fa-git-alt"></i> Versionamento
                    </a>
                </div>
                <div class="nav-item">
                    <a href="?page=master_backup" class="nav-link <?= $currentPage === 'backup' ? 'active' : '' ?>">
                        <i class="fas fa-hard-drive"></i> Backups
                    </a>
                </div>
                <div class="nav-item">
                    <a href="?page=master_logs" class="nav-link <?= $currentPage === 'logs' ? 'active' : '' ?>">
                        <i class="fas fa-file-lines"></i> Logs
                    </a>
                </div>
                <div class="nav-item">
                    <a href="?page=master_health" class="nav-link <?= $currentPage === 'health' ? 'active' : '' ?>">
                        <i class="fas fa-heart-pulse"></i> Health Check
                    </a>
                </div>
                <div class="nav-item">
                    <a href="?page=master_deploy" class="nav-link <?= $currentPage === 'deploy' ? 'active' : '' ?>">
                        <i class="fas fa-rocket"></i> Deploy
                    </a>
                </div>

                <div class="nav-section">Sistema</div>
                <div class="nav-item">
                    <a href="?page=login&action=logout" class="nav-link">
                        <i class="fas fa-right-from-bracket"></i> Sair
                    </a>
                </div>
            </nav>

            <div class="sidebar-footer">
                <div class="admin-info">
                    <div class="admin-avatar"><?= $adminInitials ?></div>
                    <div>
                        <strong style="font-size:13px;"><?= htmlspecialchars($adminName) ?></strong><br>
                        <small style="font-size:11px;color:var(--m-text-tertiary);">Admin</small>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="topbar">
                <div class="d-flex align-items-center gap-3">
                    <button class="sidebar-toggle" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1><?= $pageTitle ?? 'Dashboard' ?></h1>
                </div>
                <div class="topbar-actions">
                    <?php if (isset($topbarActions)): ?>
                        <?= $topbarActions ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="content-area">
