/**
 * Akti Sidebar Navigation
 * Toggle, collapse/expand, mobile overlay, localStorage persistence
 */
(function () {
    'use strict';

    const STORAGE_KEY = 'akti-sidebar-collapsed';
    const STORAGE_OPEN_GROUPS = 'akti-sidebar-open-groups';
    const MOBILE_BREAKPOINT = 992;

    const sidebar = document.getElementById('aktiSidebar');
    const overlay = document.getElementById('aktiSidebarOverlay');
    const body = document.body;

    if (!sidebar) return;

    // ── Restore collapsed state from localStorage ──
    function restoreState() {
        const collapsed = localStorage.getItem(STORAGE_KEY);
        if (collapsed === 'true' && window.innerWidth >= MOBILE_BREAKPOINT) {
            body.classList.add('sidebar-collapsed');
        }
    }

    // ── Toggle sidebar collapse (desktop) ──
    function toggleCollapse() {
        if (window.innerWidth < MOBILE_BREAKPOINT) {
            toggleMobile();
            return;
        }

        body.classList.toggle('sidebar-collapsed');
        const isCollapsed = body.classList.contains('sidebar-collapsed');
        localStorage.setItem(STORAGE_KEY, isCollapsed);

        // Destroy tooltips when expanding
        if (!isCollapsed) {
            destroyTooltips();
        } else {
            initTooltips();
        }
    }

    // ── Toggle mobile sidebar ──
    function toggleMobile() {
        sidebar.classList.toggle('mobile-open');
        if (overlay) {
            overlay.classList.toggle('active', sidebar.classList.contains('mobile-open'));
        }
        // Prevent body scroll when sidebar open on mobile
        body.style.overflow = sidebar.classList.contains('mobile-open') ? 'hidden' : '';
    }

    function closeMobile() {
        sidebar.classList.remove('mobile-open');
        if (overlay) overlay.classList.remove('active');
        body.style.overflow = '';
    }

    // ── Submenu toggle ──
    function toggleSubmenu(btn) {
        const submenu = btn.nextElementSibling;
        if (!submenu) return;

        const isOpen = submenu.classList.contains('show');

        // Toggle
        submenu.classList.toggle('show');
        btn.setAttribute('aria-expanded', !isOpen);

        // Save open groups
        saveOpenGroups();
    }

    function saveOpenGroups() {
        const openGroups = [];
        sidebar.querySelectorAll('.akti-sidebar-submenu.show').forEach(function (sub) {
            const parent = sub.previousElementSibling;
            if (parent && parent.dataset.group) {
                openGroups.push(parent.dataset.group);
            }
        });
        localStorage.setItem(STORAGE_OPEN_GROUPS, JSON.stringify(openGroups));
    }

    function restoreOpenGroups() {
        let openGroups = [];
        try {
            openGroups = JSON.parse(localStorage.getItem(STORAGE_OPEN_GROUPS) || '[]');
        } catch (e) {
            openGroups = [];
        }

        // Also auto-open group containing the active page
        sidebar.querySelectorAll('.akti-sidebar-submenu').forEach(function (sub) {
            const parent = sub.previousElementSibling;
            const groupKey = parent ? parent.dataset.group : null;
            const hasActive = sub.querySelector('.akti-sidebar-link.active');

            if (hasActive || (groupKey && openGroups.indexOf(groupKey) !== -1)) {
                sub.classList.add('show');
                if (parent) parent.setAttribute('aria-expanded', 'true');
            }
        });
    }

    // ── Tooltips for collapsed mode ──
    function initTooltips() {
        if (typeof bootstrap === 'undefined') return;
        sidebar.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
            new bootstrap.Tooltip(el, {
                placement: 'right',
                trigger: 'hover',
                container: 'body'
            });
        });
    }

    function destroyTooltips() {
        if (typeof bootstrap === 'undefined') return;
        sidebar.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
            var tip = bootstrap.Tooltip.getInstance(el);
            if (tip) tip.dispose();
        });
    }

    // ── Scroll active item into view ──
    function scrollToActive() {
        var active = sidebar.querySelector('.akti-sidebar-link.active');
        if (active) {
            var menuArea = sidebar.querySelector('.akti-sidebar-menu');
            if (menuArea) {
                setTimeout(function () {
                    active.scrollIntoView({ block: 'center', behavior: 'smooth' });
                }, 350);
            }
        }
    }

    // ── Popover submenu (collapsed hover) ──
    function initPopoverSubmenus() {
        var items = sidebar.querySelectorAll('.akti-sidebar-item[data-has-children="true"]');
        items.forEach(function (item) {
            item.addEventListener('mouseenter', function () {
                if (!body.classList.contains('sidebar-collapsed')) return;
                if (window.innerWidth < MOBILE_BREAKPOINT) return;
                var popover = item.querySelector('.akti-sidebar-popover');
                if (popover) {
                    // Position popover
                    var rect = item.getBoundingClientRect();
                    popover.style.top = rect.top + 'px';
                    popover.style.display = 'block';

                    // Ensure it doesn't go off-screen
                    var popRect = popover.getBoundingClientRect();
                    if (popRect.bottom > window.innerHeight) {
                        popover.style.top = (window.innerHeight - popRect.height - 8) + 'px';
                    }
                }
            });

            item.addEventListener('mouseleave', function () {
                var popover = item.querySelector('.akti-sidebar-popover');
                if (popover) popover.style.display = 'none';
            });
        });
    }

    // ── Event Listeners ──
    // Toggle buttons
    document.querySelectorAll('[data-sidebar-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            toggleCollapse();
        });
    });

    // Mobile toggle
    document.querySelectorAll('[data-sidebar-mobile-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            toggleMobile();
        });
    });

    // Overlay click to close
    if (overlay) {
        overlay.addEventListener('click', closeMobile);
    }

    // Submenu toggles
    sidebar.querySelectorAll('[data-submenu-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            toggleSubmenu(btn);
        });
    });

    // Close mobile sidebar on nav link click
    sidebar.querySelectorAll('a.akti-sidebar-link').forEach(function (link) {
        link.addEventListener('click', function () {
            if (window.innerWidth < MOBILE_BREAKPOINT) {
                closeMobile();
            }
        });
    });

    // Close mobile on Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && sidebar.classList.contains('mobile-open')) {
            closeMobile();
        }
    });

    // Handle window resize
    var resizeTimer;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
            if (window.innerWidth >= MOBILE_BREAKPOINT) {
                closeMobile();
            }
        }, 150);
    });

    // ── Initialize ──
    restoreState();
    restoreOpenGroups();
    scrollToActive();
    initPopoverSubmenus();

    if (body.classList.contains('sidebar-collapsed') && window.innerWidth >= MOBILE_BREAKPOINT) {
        initTooltips();
    }

    // Expose API
    window.AktiSidebar = {
        toggle: toggleCollapse,
        collapse: function () {
            body.classList.add('sidebar-collapsed');
            localStorage.setItem(STORAGE_KEY, 'true');
            initTooltips();
        },
        expand: function () {
            body.classList.remove('sidebar-collapsed');
            localStorage.setItem(STORAGE_KEY, 'false');
            destroyTooltips();
        },
        isCollapsed: function () {
            return body.classList.contains('sidebar-collapsed');
        },
        toggleMobile: toggleMobile,
        closeMobile: closeMobile
    };
})();
