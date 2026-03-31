/**
 * Akti — Dark Mode Toggle
 * Manages theme switching between light/dark/auto modes.
 *
 * Modes:
 *   - 'light': Always light theme
 *   - 'dark':  Always dark theme
 *   - 'auto':  Follow OS preference (prefers-color-scheme)
 *
 * Priority:
 *   1. localStorage preference (user chose manually)
 *   2. If 'auto' or no preference → follow OS preference
 *   3. Default: light
 *
 * Usage:
 *   Button in header calls AktiTheme.toggle() — cycles: light → dark → auto
 *   AktiTheme.set('auto') — set to auto mode
 *   AktiTheme.getMode() — returns 'light', 'dark', or 'auto'
 */
(function() {
    'use strict';

    var STORAGE_KEY = 'akti-theme';
    var ATTR = 'data-theme';

    function getStoredTheme() {
        try { return localStorage.getItem(STORAGE_KEY); } catch(e) { return null; }
    }

    function getSystemTheme() {
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            return 'dark';
        }
        return 'light';
    }

    function getEffectiveTheme() {
        var stored = getStoredTheme();
        if (stored === 'auto' || !stored) {
            return getSystemTheme();
        }
        return stored;
    }

    function getMode() {
        return getStoredTheme() || 'auto';
    }

    function updateUI(resolvedTheme, mode) {
        // Update toggle button icon
        var icon = document.getElementById('themeToggleIcon');
        if (icon) {
            if (mode === 'auto') {
                icon.className = 'fas fa-circle-half-stroke';
            } else {
                icon.className = resolvedTheme === 'dark' ? 'fas fa-moon' : 'fas fa-sun';
            }
        }

        // Update label
        var label = document.getElementById('themeToggleLabel');
        if (label) {
            if (mode === 'auto') {
                label.textContent = 'Auto (Sistema)';
            } else {
                label.textContent = resolvedTheme === 'dark' ? 'Modo Claro' : 'Modo Escuro';
            }
        }

        // Update tooltip on button
        var btn = document.getElementById('themeToggleBtn');
        if (btn) {
            var titles = { light: 'Tema: Claro', dark: 'Tema: Escuro', auto: 'Tema: Automático (Sistema)' };
            btn.setAttribute('title', titles[mode] || 'Alternar tema');
        }
    }

    function applyTheme(mode, animate) {
        var root = document.documentElement;
        var resolvedTheme = (mode === 'auto') ? getSystemTheme() : mode;

        if (animate) {
            root.setAttribute('data-theme-transitioning', '');
            setTimeout(function() {
                root.removeAttribute('data-theme-transitioning');
            }, 250);
        }

        root.setAttribute(ATTR, resolvedTheme);
        root.setAttribute('data-theme-mode', mode);

        updateUI(resolvedTheme, mode);

        // Store preference
        try { localStorage.setItem(STORAGE_KEY, mode); } catch(e) {}

        // Notify: dispatch custom event for other components
        window.dispatchEvent(new CustomEvent('akti-theme-changed', { 
            detail: { theme: resolvedTheme, mode: mode } 
        }));
    }

    function toggle() {
        // Cycle: light → dark → auto → light
        var currentMode = getMode();
        var next;
        if (currentMode === 'light') {
            next = 'dark';
        } else if (currentMode === 'dark') {
            next = 'auto';
        } else {
            next = 'light';
        }
        applyTheme(next, true);
    }

    // Apply theme immediately (no flash of wrong theme)
    applyTheme(getMode(), false);

    // Re-run updateUI once DOM is fully parsed, because the script may load
    // in <head> before #themeToggleIcon exists. The attribute on <html> is
    // already correct (set above), we just need to sync the button icon/label.
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            var mode = getMode();
            var resolved = (mode === 'auto') ? getSystemTheme() : mode;
            updateUI(resolved, mode);
        });
    }

    // Listen for OS preference changes — update if in auto mode
    if (window.matchMedia) {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
            if (getMode() === 'auto') {
                applyTheme('auto', true);
            }
        });
    }

    window.AktiTheme = {
        toggle: toggle,
        get: function() { return document.documentElement.getAttribute(ATTR) || getEffectiveTheme(); },
        getMode: getMode,
        set: function(t) { applyTheme(t, true); }
    };

})();
