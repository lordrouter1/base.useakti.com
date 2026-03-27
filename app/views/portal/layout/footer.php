<?php
/**
 * Portal do Cliente — Footer Principal (com bottom navigation)
 *
 * Variáveis esperadas: $currentAction (string), $unreadMessages (int)
 */
$currentAction = $currentAction ?? ($_GET['action'] ?? 'dashboard');
$unreadMessages = $unreadMessages ?? 0;
?>
    </main>

    <!-- ═══ Bottom Navigation (Mobile) ═══ -->
    <nav class="portal-bottom-nav">
        <a href="?page=portal&action=dashboard"
           class="portal-nav-item <?= in_array($currentAction, ['dashboard','index']) ? 'active' : '' ?>">
            <i class="fas fa-house"></i>
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
        <!--<a href="?page=portal&action=messages"
           class="portal-nav-item portal-nav-item-badge <?= in_array($currentAction, ['messages']) ? 'active' : '' ?>">
            <i class="fas fa-comments"></i>
            <?php if ($unreadMessages > 0): ?>
                <span class="portal-bottom-badge"><?= (int) $unreadMessages ?></span>
            <?php endif; ?>
            <span><?= __p('messages_title') ?></span>
        </a>-->
        <a href="#" class="portal-nav-item" id="portalMoreBtn"
           onclick="event.preventDefault(); document.getElementById('portalMoreMenu').classList.toggle('show');">
            <i class="fas fa-ellipsis-h"></i>
            <span><?= __p('nav_more') ?></span>
        </a>
    </nav>

    <!-- ═══ More Menu (Mobile) ═══ -->
    <div class="portal-more-menu" id="portalMoreMenu">
        <div class="portal-more-menu-overlay" onclick="document.getElementById('portalMoreMenu').classList.remove('show');"></div>
        <div class="portal-more-menu-content">
            <a href="?page=portal&action=installments" class="portal-more-item">
                <i class="fas fa-wallet"></i>
                <span><?= __p('nav_financial') ?></span>
            </a>
            <a href="?page=portal&action=tracking" class="portal-more-item">
                <i class="fas fa-truck"></i>
                <span><?= __p('tracking_title') ?></span>
            </a>
            <a href="?page=portal&action=documents" class="portal-more-item">
                <i class="fas fa-file-alt"></i>
                <span><?= __p('documents_title') ?></span>
            </a>
            <a href="?page=portal&action=profile" class="portal-more-item">
                <i class="far fa-user"></i>
                <span><?= __p('nav_profile') ?></span>
            </a>
            <a href="?page=portal&action=logout" class="portal-more-item portal-more-item-danger">
                <i class="fas fa-sign-out-alt"></i>
                <span><?= __p('profile_logout') ?></span>
            </a>
        </div>
    </div>

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
        navigator.serviceWorker.register('portal-sw.js', { scope: './' })
            .then(function(reg) {
                console.log('Portal SW registered:', reg.scope);

                // Push notification permission (Fase 7)
                if ('Notification' in window && Notification.permission === 'default') {
                    // Pedir permissão após interação do usuário
                    document.addEventListener('click', function askPush() {
                        Notification.requestPermission().then(function(permission) {
                            if (permission === 'granted') {
                                console.log('Push notifications enabled');
                            }
                        });
                        document.removeEventListener('click', askPush);
                    }, { once: true });
                }
            })
            .catch(function(err) {
                console.log('Portal SW registration failed:', err);
            });
    }
    </script>
</body>
</html>
