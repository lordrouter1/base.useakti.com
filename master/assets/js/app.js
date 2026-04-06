/**
 * Akti Master Admin - JavaScript
 */

$(document).ready(function() {
    // Sidebar toggle (mobile)
    $('#sidebarToggle').on('click', function() {
        $('#sidebar').toggleClass('show');
        $('#sidebarOverlay').toggleClass('show');
    });

    $('#sidebarOverlay').on('click', function() {
        $('#sidebar').removeClass('show');
        $(this).removeClass('show');
    });

    // Close sidebar on nav click (mobile)
    $('.sidebar-nav .nav-link').on('click', function() {
        if (window.innerWidth < 992) {
            $('#sidebar').removeClass('show');
            $('#sidebarOverlay').removeClass('show');
        }
    });

    // Tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    tooltipTriggerList.map(function(el) {
        return new bootstrap.Tooltip(el, { trigger: 'hover' });
    });
});
