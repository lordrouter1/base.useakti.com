/**
 * Módulo de Clientes — Atalhos de Teclado
 *
 * Fase 4 — Item 4.5
 *
 * Atalhos:
 *   Ctrl+S     → Salvar formulário (submit)               [Create/Edit]
 *   Ctrl+→     → Próximo step                              [Wizard]
 *   Ctrl+←     → Step anterior                             [Wizard]
 *   Esc        → Fechar modal / Voltar à listagem          [Qualquer]
 *   Ctrl+N     → Novo cliente (ir para create)             [Listagem]
 *   Ctrl+E     → Exportar clientes                         [Listagem]
 *   /          → Focar na busca                            [Listagem]
 */
(function () {
    'use strict';

    function el(id) { return document.getElementById(id); }

    /**
     * Detecta o contexto atual da página.
     * @returns {'create'|'edit'|'view'|'list'}
     */
    function getContext() {
        var url = window.location.search || '';
        if (url.indexOf('action=create') !== -1) return 'create';
        if (url.indexOf('action=edit') !== -1) return 'edit';
        if (url.indexOf('action=view') !== -1) return 'view';
        if (url.indexOf('page=customers') !== -1) return 'list';
        return 'unknown';
    }

    /**
     * Verifica se o foco está em um input/textarea (para não interferir).
     */
    function isInputFocused() {
        var active = document.activeElement;
        if (!active) return false;
        var tag = active.tagName.toLowerCase();
        return tag === 'input' || tag === 'textarea' || tag === 'select' || active.isContentEditable;
    }

    /**
     * Mostra tooltip de atalhos.
     */
    function showShortcutsHelp() {
        if (typeof Swal === 'undefined') return;

        var context = getContext();
        var shortcuts = [];

        if (context === 'create' || context === 'edit') {
            shortcuts = [
                { key: 'Ctrl + S', desc: 'Salvar formulário' },
                { key: 'Ctrl + →', desc: 'Próximo step' },
                { key: 'Ctrl + ←', desc: 'Step anterior' },
                { key: 'Esc', desc: 'Voltar à listagem' },
            ];
        } else if (context === 'list') {
            shortcuts = [
                { key: 'Ctrl + N', desc: 'Novo cliente' },
                { key: 'Ctrl + E', desc: 'Exportar clientes' },
                { key: '/', desc: 'Focar na busca' },
                { key: 'Esc', desc: 'Limpar busca' },
            ];
        } else if (context === 'view') {
            shortcuts = [
                { key: 'Esc', desc: 'Voltar à listagem' },
            ];
        }

        if (shortcuts.length === 0) return;

        var html = '<table class="table table-sm mb-0" style="font-size:.85rem;"><tbody>';
        shortcuts.forEach(function (s) {
            html += '<tr><td><kbd style="background:#1a1a2e;color:#fff;padding:3px 8px;border-radius:4px;font-size:.78rem;">' +
                s.key + '</kbd></td><td class="text-start">' + s.desc + '</td></tr>';
        });
        html += '</tbody></table>';

        Swal.fire({
            title: '⌨️ Atalhos de Teclado',
            html: html,
            showConfirmButton: true,
            confirmButtonText: 'Entendi',
            confirmButtonColor: '#3498db',
            width: 400
        });
    }

    /**
     * Handler principal de atalhos.
     */
    function handleKeydown(e) {
        var context = getContext();

        // ─── Ctrl+S → Salvar ───
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            var form = el('customerForm');
            if (form && (context === 'create' || context === 'edit')) {
                // Validar antes de submeter
                if (window.CstValidation && !window.CstValidation.validateAll()) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Corrija os campos',
                            text: 'Existem campos com erros. Verifique e tente novamente.',
                            confirmButtonColor: '#3498db'
                        });
                    }
                    return;
                }
                if (form.requestSubmit) {
                    form.requestSubmit();
                } else {
                    form.submit();
                }
            }
        }

        // ─── Ctrl+→ → Próximo step ───
        if (e.ctrlKey && e.key === 'ArrowRight') {
            e.preventDefault();
            var btnNext = el('btnWizardNext');
            if (btnNext && btnNext.style.display !== 'none') {
                btnNext.click();
            }
        }

        // ─── Ctrl+← → Step anterior ───
        if (e.ctrlKey && e.key === 'ArrowLeft') {
            e.preventDefault();
            var btnPrev = el('btnWizardPrev');
            if (btnPrev && btnPrev.style.display !== 'none') {
                btnPrev.click();
            }
        }

        // ─── Esc → Fechar modal ou voltar ───
        if (e.key === 'Escape') {
            // Se tem modal aberto, deixar Bootstrap fechar
            var modal = document.querySelector('.modal.show');
            if (modal) return;

            // Se tem Swal aberto, deixar Swal fechar
            if (document.querySelector('.swal2-container')) return;

            // Se tem dropdown aberto, fechar
            var dropdown = document.querySelector('.cst-tag-dropdown');
            if (dropdown) {
                dropdown.remove();
                return;
            }

            // Se está num formulário ou view, voltar à listagem
            if (context === 'create' || context === 'edit' || context === 'view') {
                window.location.href = '?page=customers';
            }

            // Se está na listagem, limpar busca
            if (context === 'list') {
                var searchInput = el('searchCustomer') || document.querySelector('input[name="search"]');
                if (searchInput && searchInput.value) {
                    searchInput.value = '';
                    searchInput.dispatchEvent(new Event('input', { bubbles: true }));
                }
            }
        }

        // ─── Ctrl+N → Novo cliente ───
        if (e.ctrlKey && e.key === 'n') {
            if (context === 'list' && !isInputFocused()) {
                e.preventDefault();
                window.location.href = '?page=customers&action=create';
            }
        }

        // ─── Ctrl+E → Exportar ───
        if (e.ctrlKey && e.key === 'e') {
            if (context === 'list' && !isInputFocused()) {
                e.preventDefault();
                var exportBtn = el('btnExport') || document.querySelector('[data-action="export"]');
                if (exportBtn) {
                    exportBtn.click();
                } else {
                    window.location.href = '?page=customers&action=export&format=csv';
                }
            }
        }

        // ─── / → Focar na busca ───
        if (e.key === '/' && !e.ctrlKey && !e.altKey && !e.metaKey) {
            if (context === 'list' && !isInputFocused()) {
                e.preventDefault();
                var search = el('searchCustomer') || document.querySelector('input[name="search"]');
                if (search) search.focus();
            }
        }

        // ─── ? → Mostrar ajuda de atalhos ───
        if (e.key === '?' && !e.ctrlKey && !isInputFocused()) {
            e.preventDefault();
            showShortcutsHelp();
        }
    }

    /**
     * Inicializa os atalhos.
     */
    function init() {
        document.addEventListener('keydown', handleKeydown);

        // Indicador visual de atalhos (tooltip no footer do form)
        var form = el('customerForm');
        if (form) {
            var hint = document.createElement('div');
            hint.className = 'text-muted text-center mt-2';
            hint.style.fontSize = '.72rem';
            hint.innerHTML = '<kbd style="font-size:.65rem;background:#eee;padding:1px 5px;border-radius:3px;">?</kbd> ' +
                'para ver atalhos de teclado';
            var footer = form.querySelector('.cst-wizard-footer') || form.parentNode;
            if (footer) footer.appendChild(hint);
        }
    }

    // Expor globalmente
    window.CstShortcuts = window.CstShortcuts || {};
    window.CstShortcuts.showHelp = showShortcutsHelp;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
