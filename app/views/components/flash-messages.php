<?php
/**
 * Flash Messages Component
 *
 * Converts session flash messages into AktiToast notifications.
 * Include this at the top of views that use flash messages.
 *
 * Supports:
 *   $_SESSION['flash_success']  → toast success
 *   $_SESSION['flash_error']    → toast error
 *   $_SESSION['flash_warning']  → toast warning
 *   $_SESSION['flash_info']     → toast info
 *
 * Also reads URL params: ?status=success&message=...
 *
 * Usage:
 *   require 'app/views/components/flash-messages.php';
 */

$__flashTypes = ['flash_success' => 'success', 'flash_error' => 'error', 'flash_warning' => 'warning', 'flash_info' => 'info'];
$__flashMessages = [];

foreach ($__flashTypes as $sessionKey => $toastType) {
    if (!empty($_SESSION[$sessionKey])) {
        $__flashMessages[] = [
            'type'    => $toastType,
            'message' => $_SESSION[$sessionKey],
        ];
        unset($_SESSION[$sessionKey]);
    }
}

// URL status param fallback
if (isset($_GET['status']) && isset($_GET['message'])) {
    $__urlType = in_array($_GET['status'], ['success', 'error', 'warning', 'info']) ? $_GET['status'] : 'info';
    $__flashMessages[] = [
        'type'    => $__urlType,
        'message' => $_GET['message'],
    ];
}

if (!empty($__flashMessages)):
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php foreach ($__flashMessages as $fm): ?>
    if (window.AktiToast) {
        AktiToast.<?= $fm['type'] ?>(<?= json_encode($fm['message'], JSON_UNESCAPED_UNICODE) ?>);
    } else if (typeof Swal !== 'undefined') {
        Swal.mixin({toast:true,position:'top-end',showConfirmButton:false,timer:3000,timerProgressBar:true})
            .fire({icon:'<?= $fm['type'] === 'error' ? 'error' : ($fm['type'] === 'warning' ? 'warning' : 'success') ?>',title:<?= json_encode($fm['message'], JSON_UNESCAPED_UNICODE) ?>});
    }
    <?php endforeach; ?>
});
</script>
<?php endif; ?>
