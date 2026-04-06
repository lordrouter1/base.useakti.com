</main>
    </div>
</div>

<!-- Footer do sistema -->
<footer class="app-footer mt-auto">
    <div class="container-fluid">
        <div class="d-flex flex-wrap justify-content-between align-items-center py-3">
            <div class="m-auto">
                <img src="assets/logos/akti-logo-light-nBg.svg" alt="Akti" class="me-2">
                <span class="text-muted small">&copy; <?= date('Y') ?> Akti - Gestão em Produção</span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <?php if (isset($_SESSION['user_id'])): ?>
                <a href="?page=walkthrough&action=manual" class="text-muted small text-decoration-none" title="Manual do Sistema">
                    <i class="fas fa-book me-1"></i>Manual
                </a>
                <button type="button" class="wt-footer-help" id="wtFooterHelp" title="Refazer o Tour Guiado do Sistema">
                    <i class="fas fa-question-circle wt-pulse-icon"></i>
                    <span>Tutorial</span>
                </button>
                <?php endif; ?>
                <span class="text-muted small">v1.0</span>
            </div>
        </div>
    </div>
</footer>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha384-1H217gwSVyLSIfaLxHbE7dRb3v4mYCKbpQvzx0cegeju1MVsGrX5xXxAvs/HgeFs" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js" integrity="sha384-6LwNpGeYDjlORU0Q5rfxEC8SQO6/FTh/VecUcvFvNx1gLMdX5dm8y1Y739D3lFSW" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" integrity="sha384-QjoPbdj/93O7LUz0wqTxepA3tIabUD3jzfZX+x5QLvqFtHBzSw4eYFLSVthB+EDT" crossorigin="anonymous"></script>
<!-- Script global do sistema (CSRF, atalhos, máscaras) -->
<script src="<?= asset('assets/js/script.js') ?>"></script>
<!-- FileManager: JS helpers para thumbnail URLs -->
<script>
function thumbUrl(path, w, h) {
    if (!path) return '';
    var ext = path.split('.').pop().toLowerCase();
    if (['svg','pdf','doc','docx','xls','xlsx','csv','txt','zip'].indexOf(ext) !== -1) return path;
    var url = '?page=files&action=thumb&path=' + encodeURIComponent(path) + '&w=' + (w || 150);
    if (h) url += '&h=' + h;
    return url;
}
function fileUrl(path, size) {
    if (!path) return '';
    if (!size) return path;
    var presets = {xs:40,sm:80,md:150,lg:300,xl:600};
    var w = presets[size] || parseInt(size) || 150;
    return thumbUrl(path, w, w);
}
</script>
<!-- Design System Components -->
<script src="<?= asset('assets/js/components/toast.js') ?>"></script>
<script src="<?= asset('assets/js/components/skeleton.js') ?>"></script>
<script src="<?= asset('assets/js/components/shortcuts.js') ?>"></script>
<script src="<?= asset('assets/js/components/command-palette.js') ?>"></script>
<script src="<?= asset('assets/js/components/notification-bell.js') ?>"></script>
<script src="<?= asset('assets/js/components/dashboard-widgets.js') ?>"></script>
<script src="<?= asset('assets/js/components/focus-trap.js') ?>"></script>

<?php
// ── Session Timeout: injetar dados JS para modal de aviso ──
if (isset($_SESSION['user_id'])) {
    // Conexão para ler timeout (reutilizar se possível)
    $__footerDb = $__sessionDb ?? (new Database())->getConnection();
    $__sessionData = SessionGuard::getJsSessionData($__footerDb);
}
?>
<?php if (isset($_SESSION['user_id'])): ?>
<div id="sessionTimeoutCfg" class="d-none"
     data-timeout="<?= $__sessionData['timeout_seconds'] ?>"
     data-warning="<?= $__sessionData['warning_seconds'] ?>"
     data-remaining="<?= $__sessionData['remaining_seconds'] ?>"></div>
<script src="<?= asset('assets/js/components/session-timeout.js') ?>" defer></script>
<?php endif; ?>

<!-- Walkthrough -->
<script src="assets/js/walkthrough.js" defer></script>
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js" integrity="sha384-d3UHjPdzJkZuk5H3qKYMLRyWLAQBJbby2yr2Q58hXXtAGF8RSNO9jpLDlKKPv5v3" crossorigin="anonymous"></script>
<!-- Product Select2 integration -->
<script src="assets/js/product-select2.js"></script>
<!-- Customer Select2 integration -->
<script src="assets/js/customer-select2.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Botão de tutorial no rodapé
        var wtBtn = document.getElementById('wtFooterHelp');
        if (wtBtn) {
            wtBtn.addEventListener('click', function() {
                if (window.aktiWalkthrough) {
                    window.aktiWalkthrough.start(0);
                }
            });
        }
        // Auto-start do tour para novos usuários
        if (window.aktiWalkthrough) {
            window.aktiWalkthrough.autoStart();
        }

        // ── PWA: Registrar Service Worker ──
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('sw.js').catch(function (err) {
                console.warn('SW register failed:', err);
            });
        }
    });
</script>
</body>
</html>
