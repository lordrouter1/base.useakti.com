/**
 * Módulo de Clientes — Auto-Save em localStorage
 *
 * Fase 4 — Item 4.4
 *
 * Salva rascunho do formulário automaticamente para evitar perda de dados.
 *
 * Fluxo:
 * 1. A cada 30 segundos, coletar todos os valores do form
 * 2. Salvar em localStorage com key: "akti_customer_draft_{action}_{id}"
 * 3. Ao carregar o formulário:
 *    a. Verificar se existe draft
 *    b. Se sim, mostrar toast: "Rascunho encontrado. Deseja restaurar?"
 *    c. Se restaurar, preencher campos
 *    d. Se ignorar, limpar draft
 * 4. Ao submit com sucesso, limpar draft
 * 5. Ao clicar "Cancelar", perguntar se quer limpar draft
 */
(function () {
    'use strict';

    var DRAFT_PREFIX = 'akti_customer_draft';
    var SAVE_INTERVAL = 30000; // 30 segundos
    var DRAFT_MAX_AGE = 86400000; // 24 horas

    function el(id) { return document.getElementById(id); }

    /**
     * Obtém o sufixo da chave de draft baseado no contexto.
     */
    function getDraftSuffix() {
        var idField = document.querySelector('input[name="id"]');
        return idField && idField.value ? '_edit_' + idField.value : '_create';
    }

    /**
     * Obtém a chave completa do localStorage.
     */
    function getDraftKey() {
        return DRAFT_PREFIX + getDraftSuffix();
    }

    /**
     * Salva o estado atual do formulário no localStorage.
     */
    function saveDraft() {
        var form = el('customerForm');
        if (!form) return;

        var data = {};
        var inputs = form.querySelectorAll('input, select, textarea');
        var hasContent = false;

        inputs.forEach(function (inp) {
            if (inp.name && inp.type !== 'file' && inp.name !== 'csrf_token' && inp.name !== 'id') {
                data[inp.name] = inp.value;
                if (inp.value && inp.value.trim()) hasContent = true;
            }
        });

        // Não salvar draft vazio
        if (!hasContent) return;

        try {
            localStorage.setItem(getDraftKey(), JSON.stringify({
                ts: Date.now(),
                data: data,
                url: window.location.href
            }));
        } catch (e) {
            // localStorage cheio — silencioso
        }
    }

    /**
     * Carrega e restaura o rascunho do localStorage.
     * Não exibe sugestão de rascunho no modo de edição (edit_customer_id presente).
     */
    function loadDraft() {
        // No modo de edição, não sugerir restauração de rascunho
        // Os dados já vêm preenchidos pelo servidor
        var editField = document.getElementById('edit_customer_id');
        if (editField && editField.value) {
            // Limpar qualquer rascunho de edição que possa existir
            clearDraft();
            return;
        }

        var key = getDraftKey();
        try {
            var raw = localStorage.getItem(key);
            if (!raw) return;

            var parsed = JSON.parse(raw);

            // Se rascunho tem mais de 24h, descartar
            if (Date.now() - parsed.ts > DRAFT_MAX_AGE) {
                localStorage.removeItem(key);
                return;
            }

            // Calcular tempo desde o rascunho
            var minutesAgo = Math.round((Date.now() - parsed.ts) / 60000);
            var timeLabel = minutesAgo < 60
                ? minutesAgo + ' minuto(s) atrás'
                : Math.round(minutesAgo / 60) + ' hora(s) atrás';

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'question',
                    title: 'Rascunho encontrado',
                    html: '<p>Existe um rascunho salvo <strong>' + timeLabel + '</strong>.</p>' +
                          '<p class="text-muted" style="font-size:.85rem;">Deseja restaurar os dados preenchidos anteriormente?</p>',
                    showCancelButton: true,
                    showDenyButton: true,
                    confirmButtonText: '<i class="fas fa-undo me-1"></i>Restaurar',
                    denyButtonText: '<i class="fas fa-trash me-1"></i>Descartar',
                    cancelButtonText: 'Fechar',
                    confirmButtonColor: '#3498db',
                    denyButtonColor: '#e74c3c'
                }).then(function (result) {
                    if (result.isConfirmed) {
                        restoreDraft(parsed.data);
                        showToast('success', 'Rascunho restaurado com sucesso!');
                    } else if (result.isDenied) {
                        clearDraft();
                        showToast('info', 'Rascunho descartado.');
                    }
                });
            } else {
                // Fallback sem SweetAlert
                if (confirm('Rascunho encontrado (' + timeLabel + '). Deseja restaurar?')) {
                    restoreDraft(parsed.data);
                }
            }
        } catch (e) {
            // JSON malformado — limpar
            localStorage.removeItem(key);
        }
    }

    /**
     * Restaura os dados do rascunho nos campos do formulário.
     */
    function restoreDraft(data) {
        if (!data) return;

        for (var key in data) {
            var f = document.querySelector('[name="' + key + '"]');
            if (f && f.type !== 'file') {
                f.value = data[key];
            }
        }

        // Atualizar toggle PF/PJ se existir
        var pt = data.person_type;
        if (pt) {
            // Disparar a troca visual (depende do wizard)
            var toggles = document.querySelectorAll('.cst-toggle-option');
            toggles.forEach(function (opt) {
                if (opt.dataset.type === pt) {
                    opt.click();
                }
            });
        }

        // Atualizar completude
        if (window.CstCompleteness) {
            setTimeout(function () { window.CstCompleteness.update(); }, 200);
        }
    }

    /**
     * Limpa o rascunho do localStorage.
     */
    function clearDraft() {
        localStorage.removeItem(getDraftKey());
    }

    /**
     * Mostra um toast (SweetAlert mixin).
     */
    function showToast(icon, title) {
        if (typeof Swal === 'undefined') return;
        Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true
        }).fire({ icon: icon, title: title });
    }

    /**
     * Inicializa o auto-save.
     */
    function init() {
        var form = el('customerForm');
        if (!form) return;

        // Salvar a cada 30 segundos
        setInterval(saveDraft, SAVE_INTERVAL);

        // Limpar rascunho ao submit
        form.addEventListener('submit', function () {
            clearDraft();
        });

        // Botão cancelar: perguntar se quer limpar draft
        var cancelBtn = document.querySelector('[data-action="cancel"], .btn-cancel, a[href="?page=customers"]');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function (e) {
                var key = getDraftKey();
                var hasDraft = !!localStorage.getItem(key);

                if (hasDraft && typeof Swal !== 'undefined') {
                    e.preventDefault();
                    var href = this.href || '?page=customers';
                    Swal.fire({
                        icon: 'question',
                        title: 'Descartar rascunho?',
                        text: 'Existe um rascunho salvo. Deseja descartá-lo ao sair?',
                        showCancelButton: true,
                        confirmButtonText: 'Sim, descartar',
                        cancelButtonText: 'Continuar editando',
                        confirmButtonColor: '#e74c3c'
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            clearDraft();
                            window.location.href = href;
                        }
                    });
                }
            });
        }

        // Carregar rascunho existente
        loadDraft();
    }

    // Expor para uso externo
    window.CstAutosave = window.CstAutosave || {};
    window.CstAutosave.save = saveDraft;
    window.CstAutosave.load = loadDraft;
    window.CstAutosave.clear = clearDraft;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
