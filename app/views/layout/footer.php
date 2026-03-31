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
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Script global do sistema (CSRF, atalhos, máscaras) -->
<script src="<?= asset('assets/js/script.js') ?>"></script>
<!-- Design System Components -->
<script src="<?= asset('assets/js/components/toast.js') ?>"></script>
<script src="<?= asset('assets/js/components/skeleton.js') ?>"></script>
<script src="<?= asset('assets/js/components/shortcuts.js') ?>"></script>
<script src="<?= asset('assets/js/components/command-palette.js') ?>"></script>
<script src="<?= asset('assets/js/components/notification-bell.js') ?>"></script>
<script src="<?= asset('assets/js/components/dashboard-widgets.js') ?>"></script>

<?php
// ── Session Timeout: injetar dados JS para modal de aviso ──
if (isset($_SESSION['user_id'])) {
    // Conexão para ler timeout (reutilizar se possível)
    $__footerDb = $__sessionDb ?? (new Database())->getConnection();
    $__sessionData = SessionGuard::getJsSessionData($__footerDb);
}
?>
<?php if (isset($_SESSION['user_id'])): ?>
<script>
(function() {
    'use strict';

    var SESSION_TIMEOUT = <?= $__sessionData['timeout_seconds'] ?>;
    var SESSION_WARNING = <?= $__sessionData['warning_seconds'] ?>;
    var remaining = <?= $__sessionData['remaining_seconds'] ?>;
    var warningShown = false;
    var countdownInterval = null;
    var mainInterval = null;

    function formatTime(secs) {
        var m = Math.floor(secs / 60);
        var s = secs % 60;
        return (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
    }

    function renewSession() {
        fetch('?page=session&action=keepalive', {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                remaining = data.remaining_seconds || SESSION_TIMEOUT;
                warningShown = false;
                if (countdownInterval) { clearInterval(countdownInterval); countdownInterval = null; }
                Swal.close();
            } else if (data.session_expired) {
                window.location.href = '?page=login&session_expired=1';
            }
        })
        .catch(function() {});
    }

    function showWarningModal() {
        if (warningShown) return;
        warningShown = true;

        Swal.fire({
            title: '<i class="fas fa-hourglass-half text-warning me-2"></i>Sessão Expirando',
            html: '<div style="font-size:0.95rem;">' +
                  '<p class="mb-2">Sua sessão será encerrada por inatividade em:</p>' +
                  '<div id="swal-session-countdown" class="text-red" style="font-size:2.2rem;font-weight:700;font-family:monospace;">' +
                  formatTime(remaining) + '</div>' +
                  '<p class="text-muted small mt-2 mb-0">Clique em <strong>Continuar</strong> para manter sua sessão ativa.</p>' +
                  '</div>',
            icon: null,
            showConfirmButton: true,
            confirmButtonText: '<i class="fas fa-sync-alt me-1"></i> Continuar Trabalhando',
            confirmButtonColor: '#3b82f6',
            showCancelButton: false,
            allowOutsideClick: false,
            allowEscapeKey: false,
            customClass: { popup: 'shadow-lg' }
        }).then(function(result) {
            if (result.isConfirmed) {
                renewSession();
            }
        });

        // Contagem regressiva dentro do modal
        countdownInterval = setInterval(function() {
            remaining--;
            var el = document.getElementById('swal-session-countdown');
            if (el) {
                el.textContent = formatTime(Math.max(0, remaining));
                if (remaining <= 30) el.style.color = '#dc2626';
            }
            if (remaining <= 0) {
                clearInterval(countdownInterval);
                Swal.close();
                window.location.href = '?page=login&session_expired=1';
            }
        }, 1000);
    }

    // Timer principal: decrementa a cada segundo e checa se deve mostrar aviso
    mainInterval = setInterval(function() {
        remaining--;
        if (remaining <= SESSION_WARNING && remaining > 0 && !warningShown) {
            showWarningModal();
        }
        if (remaining <= 0 && !warningShown) {
            window.location.href = '?page=login&session_expired=1';
        }
    }, 1000);

})();
</script>
<?php endif; ?>

<!-- Walkthrough -->
<script src="assets/js/walkthrough.js"></script>
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
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
    });
</script>
</body>
</html>
