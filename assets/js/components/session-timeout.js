/**
 * Session Timeout — monitora inatividade e exibe aviso SweetAlert2.
 *
 * Lê configuração via data-attributes no elemento #sessionTimeoutCfg:
 *   data-timeout      Timeout total (segundos)
 *   data-warning      Antecedência do aviso (segundos)
 *   data-remaining    Tempo restante inicial (segundos)
 */
(function () {
    'use strict';

    var cfg = document.getElementById('sessionTimeoutCfg');
    if (!cfg) return;

    var SESSION_TIMEOUT  = parseInt(cfg.dataset.timeout, 10)  || 1800;
    var SESSION_WARNING  = parseInt(cfg.dataset.warning, 10)  || 300;
    var remaining        = parseInt(cfg.dataset.remaining, 10) || SESSION_TIMEOUT;
    var warningShown     = false;
    var countdownInterval = null;
    var mainInterval     = null;

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
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success) {
                remaining = data.remaining_seconds || SESSION_TIMEOUT;
                warningShown = false;
                if (countdownInterval) { clearInterval(countdownInterval); countdownInterval = null; }
                Swal.close();
            } else if (data.session_expired) {
                window.location.href = '?page=login&session_expired=1';
            }
        })
        .catch(function () {});
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
        }).then(function (result) {
            if (result.isConfirmed) {
                renewSession();
            }
        });

        countdownInterval = setInterval(function () {
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

    mainInterval = setInterval(function () {
        remaining--;
        if (remaining <= SESSION_WARNING && remaining > 0 && !warningShown) {
            showWarningModal();
        }
        if (remaining <= 0 && !warningShown) {
            window.location.href = '?page=login&session_expired=1';
        }
    }, 1000);
})();
