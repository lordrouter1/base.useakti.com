<?php
/**
 * Portal do Cliente — Footer Principal (com bottom navigation)
 *
 * Variáveis esperadas: $currentAction (string)
 */
$currentAction = $currentAction ?? ($_GET['action'] ?? 'dashboard');
?>
    </main>

    <!-- ═══ Bottom Navigation (Mobile) ═══ -->
    <nav class="portal-bottom-nav">
        <a href="?page=portal&action=dashboard"
           class="portal-nav-item <?= in_array($currentAction, ['dashboard','index']) ? 'active' : '' ?>">
            <i class="fas fa-home"></i>
            <span><?= __p('nav_home') ?></span>
        </a>
        <a href="?page=portal&action=orders"
           class="portal-nav-item <?= in_array($currentAction, ['orders','orderDetail','approveOrder']) ? 'active' : '' ?>">
            <i class="fas fa-box"></i>
            <span><?= __p('nav_orders') ?></span>
        </a>
        <a href="?page=portal&action=newOrder"
           class="portal-nav-item portal-nav-center <?= $currentAction === 'newOrder' ? 'active' : '' ?>">
            <span class="portal-nav-center-icon">
                <i class="fas fa-plus"></i>
            </span>
        </a>
        <a href="?page=portal&action=installments"
           class="portal-nav-item <?= in_array($currentAction, ['installments','installmentDetail']) ? 'active' : '' ?>">
            <i class="fas fa-wallet"></i>
            <span><?= __p('nav_financial') ?></span>
        </a>
        <a href="?page=portal&action=profile"
           class="portal-nav-item <?= $currentAction === 'profile' ? 'active' : '' ?>">
            <i class="fas fa-user"></i>
            <span><?= __p('nav_profile') ?></span>
        </a>
    </nav>

    <!-- ═══ PWA Install Banner ═══ -->
    <div class="portal-pwa-banner" id="portalPwaBanner" style="display:none;">
        <div class="portal-pwa-banner-inner">
            <div class="portal-pwa-banner-text">
                <strong><?= __p('pwa_install_title') ?></strong>
                <small><?= __p('pwa_install_text') ?></small>
            </div>
            <div class="portal-pwa-banner-actions">
                <button class="btn btn-sm btn-primary" id="portalPwaInstallBtn"><?= __p('pwa_install_btn') ?></button>
                <button class="btn btn-sm btn-link text-muted" id="portalPwaDismissBtn"><?= __p('pwa_install_dismiss') ?></button>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Portal JS -->
    <script src="assets/js/portal.js"></script>

    <!-- Service Worker Registration -->
    <script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('portal-sw.js')
            .then(function(reg) {
                console.log('Portal SW registered:', reg.scope);
            })
            .catch(function(err) {
                console.log('Portal SW registration failed:', err);
            });
    }
    </script>
</body>
</html>
