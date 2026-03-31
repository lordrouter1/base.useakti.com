/**
 * Akti — Keyboard Shortcuts
 * Global keyboard shortcuts for navigation and actions.
 *
 * Shortcuts:
 *   Ctrl+K or /   → Focus global search / command palette
 *   N             → New record (on listing pages, if not in input)
 *   Esc           → Close modal/drawer
 *   Ctrl+S        → Save form
 *   ?             → Show shortcuts help modal
 */
(function() {
    'use strict';

    var SHORTCUTS = [
        { keys: 'Ctrl+K',  desc: 'Busca rápida / Command Palette', section: 'Navegação' },
        { keys: '/',       desc: 'Focar busca rápida',             section: 'Navegação' },
        { keys: 'N',       desc: 'Novo registro',                  section: 'Ações' },
        { keys: 'Esc',     desc: 'Fechar modal/drawer',            section: 'Interface' },
        { keys: 'Ctrl+S',  desc: 'Salvar formulário',              section: 'Ações' },
        { keys: '?',       desc: 'Exibir atalhos de teclado',      section: 'Ajuda' },
    ];

    function isInputFocused() {
        var el = document.activeElement;
        if (!el) return false;
        var tag = el.tagName.toLowerCase();
        return tag === 'input' || tag === 'textarea' || tag === 'select' || el.isContentEditable;
    }

    function showHelpModal() {
        // Check if modal already exists
        var existing = document.getElementById('akti-shortcuts-modal');
        if (existing) {
            existing.querySelector('.akti-modal-backdrop').classList.add('active');
            return;
        }

        var sections = {};
        SHORTCUTS.forEach(function(s) {
            if (!sections[s.section]) sections[s.section] = [];
            sections[s.section].push(s);
        });

        var rows = '';
        Object.keys(sections).forEach(function(sec) {
            rows += '<tr><td colspan="2" style="padding:12px 0 6px;font-weight:600;color:var(--text-secondary);font-size:0.75rem;text-transform:uppercase;letter-spacing:0.04em;border:none;">' + sec + '</td></tr>';
            sections[sec].forEach(function(s) {
                rows += '<tr>' +
                    '<td style="padding:6px 0;width:120px;"><kbd style="background:var(--bg-tertiary);border:1px solid var(--border);padding:3px 8px;border-radius:4px;font-family:var(--font-mono);font-size:0.8rem;">' + s.keys + '</kbd></td>' +
                    '<td style="padding:6px 0;color:var(--text-primary);font-size:0.875rem;">' + s.desc + '</td>' +
                    '</tr>';
            });
        });

        var html = '<div class="akti-modal-backdrop" id="akti-shortcuts-modal" onclick="if(event.target===this){this.classList.remove(\'active\')}">' +
            '<div class="akti-modal" style="max-width:440px;">' +
            '<div class="akti-modal-header">' +
            '<h3><i class="fas fa-keyboard me-2"></i>Atalhos de Teclado</h3>' +
            '<button class="akti-modal-close" onclick="this.closest(\'.akti-modal-backdrop\').classList.remove(\'active\')" aria-label="Fechar"><i class="fas fa-times"></i></button>' +
            '</div>' +
            '<div class="akti-modal-body">' +
            '<table style="width:100%;border-collapse:collapse;">' + rows + '</table>' +
            '</div>' +
            '</div></div>';

        document.body.insertAdjacentHTML('beforeend', html);
        setTimeout(function() {
            document.getElementById('akti-shortcuts-modal').classList.add('active');
        }, 10);
    }

    document.addEventListener('keydown', function(e) {
        // Ctrl+K → Command Palette / Global Search
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            if (window.AktiCommandPalette) {
                AktiCommandPalette.open();
            } else {
                var searchInput = document.querySelector('.akti-global-search input, #globalSearch');
                if (searchInput) searchInput.focus();
            }
            return;
        }

        // Ctrl+S → Save form
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            var form = document.querySelector('form.akti-autosave, form[data-autosave], main form');
            if (form) {
                e.preventDefault();
                form.requestSubmit ? form.requestSubmit() : form.submit();
            }
            return;
        }

        // Esc → Close modal/drawer
        if (e.key === 'Escape') {
            // Close shortcuts modal
            var shortcutsModal = document.getElementById('akti-shortcuts-modal');
            if (shortcutsModal && shortcutsModal.classList.contains('active')) {
                shortcutsModal.classList.remove('active');
                return;
            }
            // Close command palette
            if (window.AktiCommandPalette && AktiCommandPalette.isOpen && AktiCommandPalette.isOpen()) {
                AktiCommandPalette.close();
                return;
            }
            // Close any active akti-modal or akti-drawer
            var activeModal = document.querySelector('.akti-modal-backdrop.active');
            if (activeModal) {
                activeModal.classList.remove('active');
                return;
            }
            var activeDrawer = document.querySelector('.akti-drawer.active');
            if (activeDrawer) {
                activeDrawer.classList.remove('active');
                return;
            }
            return;
        }

        // Skip remaining shortcuts if user is typing in an input
        if (isInputFocused()) return;

        // / → Focus search
        if (e.key === '/') {
            e.preventDefault();
            if (window.AktiCommandPalette) {
                AktiCommandPalette.open();
            }
            return;
        }

        // ? → Show shortcuts help
        if (e.key === '?') {
            e.preventDefault();
            showHelpModal();
            return;
        }

        // N → New record (look for primary action button)
        if (e.key === 'n' || e.key === 'N') {
            var newBtn = document.querySelector('[data-shortcut="new"], .akti-btn-primary[href*="action=create"], a[href*="action=create"].btn-primary');
            if (newBtn) {
                e.preventDefault();
                newBtn.click();
            }
            return;
        }
    });

    window.AktiShortcuts = {
        showHelp: showHelpModal,
        list: function() { return SHORTCUTS.slice(); },
    };

})();
