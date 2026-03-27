/**
 * Módulo de Clientes — Campo de Tags com Autocomplete e Chips
 *
 * Fase 4 — Item 4.2
 *
 * Comportamento:
 * 1. Ao focar no input, carrega tags existentes via AJAX (Model::getAllTags())
 * 2. Ao digitar, filtra sugestões (dropdown abaixo do input)
 * 3. Ao pressionar Enter ou clicar numa sugestão:
 *    - Adiciona pill colorido ao lado do input
 *    - Pill tem botão × para remover
 * 4. Tags são armazenadas como string separada por vírgula no hidden input
 * 5. Valor final: "VIP,Atacado,Indústria"
 */
(function () {
    'use strict';

    var TAG_COLORS = [
        '#3498db', '#27ae60', '#e74c3c', '#f39c12', '#9b59b6',
        '#1abc9c', '#e67e22', '#2980b9', '#c0392b', '#16a085'
    ];

    var allKnownTags = [];
    var tagsLoaded = false;

    /**
     * Gera uma cor consistente para uma tag baseada no hash do texto.
     */
    function tagColor(tag) {
        var hash = 0;
        for (var i = 0; i < tag.length; i++) {
            hash = tag.charCodeAt(i) + ((hash << 5) - hash);
        }
        return TAG_COLORS[Math.abs(hash) % TAG_COLORS.length];
    }

    /**
     * Retorna as tags atuais do hidden input como array.
     */
    function getCurrentTags(hiddenInput) {
        var val = (hiddenInput.value || '').trim();
        if (!val) return [];
        return val.split(',').map(function (t) { return t.trim(); }).filter(Boolean);
    }

    /**
     * Atualiza o hidden input com o array de tags.
     */
    function setCurrentTags(hiddenInput, tags) {
        hiddenInput.value = tags.join(',');
        // Disparar evento change para completude/autosave
        var event = new Event('change', { bubbles: true });
        hiddenInput.dispatchEvent(event);
    }

    /**
     * Renderiza os pills/chips visuais das tags.
     */
    function renderPills(container, hiddenInput) {
        // Limpar pills existentes
        var existingPills = container.querySelectorAll('.cst-tag');
        existingPills.forEach(function (p) { p.remove(); });

        var tags = getCurrentTags(hiddenInput);
        var textInput = container.querySelector('.cst-tag-text-input');

        tags.forEach(function (tag) {
            var pill = document.createElement('span');
            pill.className = 'cst-tag';
            var color = tagColor(tag);
            pill.style.cssText = 'background:' + color + '15;color:' + color + ';border:1px solid ' + color + '40;' +
                'display:inline-flex;align-items:center;gap:4px;padding:2px 10px;border-radius:20px;' +
                'font-size:.78rem;font-weight:500;margin:2px;white-space:nowrap;transition:all .2s;';

            pill.innerHTML = '<span>' + escapeHtml(tag) + '</span>' +
                '<button type="button" class="cst-tag-remove" data-tag="' + escapeHtml(tag) + '" ' +
                'style="background:none;border:none;color:inherit;cursor:pointer;padding:0 2px;font-size:.9rem;opacity:.7;" ' +
                'aria-label="Remover tag ' + escapeHtml(tag) + '">×</button>';

            // Inserir antes do input de texto
            if (textInput) {
                container.insertBefore(pill, textInput);
            } else {
                container.appendChild(pill);
            }
        });
    }

    /**
     * Adiciona uma tag se não existir ainda.
     */
    function addTag(hiddenInput, container, tag) {
        tag = tag.trim();
        if (!tag) return;

        var tags = getCurrentTags(hiddenInput);
        // Evitar duplicatas (case-insensitive)
        var exists = tags.some(function (t) { return t.toLowerCase() === tag.toLowerCase(); });
        if (exists) return;

        tags.push(tag);
        setCurrentTags(hiddenInput, tags);
        renderPills(container, hiddenInput);
    }

    /**
     * Remove uma tag.
     */
    function removeTag(hiddenInput, container, tag) {
        var tags = getCurrentTags(hiddenInput);
        tags = tags.filter(function (t) { return t.toLowerCase() !== tag.toLowerCase(); });
        setCurrentTags(hiddenInput, tags);
        renderPills(container, hiddenInput);
    }

    /**
     * Carrega as tags conhecidas via AJAX.
     */
    function loadKnownTags(callback) {
        if (tagsLoaded) {
            if (callback) callback(allKnownTags);
            return;
        }

        fetch('?page=customers&action=getTags')
            .then(function (r) { return r.json(); })
            .then(function (data) {
                allKnownTags = data.tags || [];
                tagsLoaded = true;
                if (callback) callback(allKnownTags);
            })
            .catch(function () {
                allKnownTags = [];
                tagsLoaded = true;
                if (callback) callback(allKnownTags);
            });
    }

    /**
     * Mostra dropdown de sugestões.
     */
    function showSuggestions(container, hiddenInput, query) {
        hideSuggestions(container);

        var currentTags = getCurrentTags(hiddenInput);
        var filtered = allKnownTags.filter(function (tag) {
            if (currentTags.some(function (t) { return t.toLowerCase() === tag.toLowerCase(); })) return false;
            if (!query) return true;
            return tag.toLowerCase().indexOf(query.toLowerCase()) !== -1;
        });

        if (filtered.length === 0 && query) {
            // Oferecer criar nova tag
            filtered = [query];
        } else if (filtered.length === 0) {
            return;
        }

        var dropdown = document.createElement('div');
        dropdown.className = 'cst-tag-dropdown';
        dropdown.style.cssText = 'position:absolute;top:100%;left:0;right:0;z-index:1050;' +
            'background:#fff;border:1px solid var(--cst-border,#e9ecef);border-top:none;' +
            'border-radius:0 0 8px 8px;box-shadow:0 4px 12px rgba(0,0,0,.1);max-height:200px;overflow-y:auto;';

        filtered.slice(0, 10).forEach(function (tag) {
            var item = document.createElement('div');
            item.className = 'cst-tag-suggestion';
            item.style.cssText = 'padding:8px 12px;cursor:pointer;font-size:.82rem;transition:background .15s;';
            var isNew = !allKnownTags.some(function (t) { return t.toLowerCase() === tag.toLowerCase(); });
            item.innerHTML = isNew
                ? '<i class="fas fa-plus me-1" style="font-size:.7rem;"></i>Criar: <strong>' + escapeHtml(tag) + '</strong>'
                : '<i class="fas fa-tag me-1" style="font-size:.7rem;color:' + tagColor(tag) + ';"></i>' + escapeHtml(tag);

            item.addEventListener('mousedown', function (e) {
                e.preventDefault(); // Impedir blur do input
                addTag(hiddenInput, container, tag);
                var textInput = container.querySelector('.cst-tag-text-input');
                if (textInput) {
                    textInput.value = '';
                    textInput.focus();
                }
                hideSuggestions(container);
            });

            item.addEventListener('mouseenter', function () {
                this.style.background = 'var(--cst-bg, #f8f9fb)';
            });
            item.addEventListener('mouseleave', function () {
                this.style.background = '#fff';
            });

            dropdown.appendChild(item);
        });

        container.style.position = 'relative';
        container.appendChild(dropdown);
    }

    /**
     * Oculta dropdown de sugestões.
     */
    function hideSuggestions(container) {
        var existing = container.querySelector('.cst-tag-dropdown');
        if (existing) existing.remove();
    }

    /**
     * Escapa HTML para segurança.
     */
    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    /**
     * Inicializa o campo de tags.
     */
    function initTagField() {
        var hiddenInput = document.getElementById('tags');
        if (!hiddenInput) return;

        // Criar wrapper visual
        var wrapper = document.getElementById('tags-wrapper');
        if (!wrapper) return;

        wrapper.style.cssText = 'display:flex;flex-wrap:wrap;align-items:center;gap:4px;' +
            'padding:6px 10px;border:1px solid var(--cst-border,#e9ecef);border-radius:8px;' +
            'background:#fff;min-height:42px;cursor:text;transition:border-color .2s,box-shadow .2s;';

        // Criar input de texto dentro do wrapper
        var textInput = document.createElement('input');
        textInput.type = 'text';
        textInput.className = 'cst-tag-text-input';
        textInput.placeholder = 'Digite uma tag...';
        textInput.setAttribute('autocomplete', 'off');
        textInput.setAttribute('aria-label', 'Adicionar tag');
        textInput.style.cssText = 'border:none;outline:none;flex:1;min-width:100px;font-size:.82rem;' +
            'padding:4px 0;background:transparent;';

        wrapper.appendChild(textInput);

        // Renderizar tags existentes
        renderPills(wrapper, hiddenInput);

        // Focus visual
        wrapper.addEventListener('click', function () {
            textInput.focus();
        });
        textInput.addEventListener('focus', function () {
            wrapper.style.borderColor = 'var(--cst-primary, #3498db)';
            wrapper.style.boxShadow = '0 0 0 3px var(--cst-focus, rgba(52,152,219,.25))';
            loadKnownTags(function () {
                showSuggestions(wrapper, hiddenInput, textInput.value);
            });
        });
        textInput.addEventListener('blur', function () {
            wrapper.style.borderColor = 'var(--cst-border, #e9ecef)';
            wrapper.style.boxShadow = 'none';
            // Timeout para permitir click no dropdown
            setTimeout(function () { hideSuggestions(wrapper); }, 200);
        });

        // Input para filtrar sugestões
        textInput.addEventListener('input', function () {
            loadKnownTags(function () {
                showSuggestions(wrapper, hiddenInput, textInput.value);
            });
        });

        // Enter para adicionar tag
        textInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                var val = textInput.value.trim();
                if (val) {
                    addTag(hiddenInput, wrapper, val);
                    textInput.value = '';
                    hideSuggestions(wrapper);
                }
            }
            // Backspace com input vazio: remove última tag
            if (e.key === 'Backspace' && !textInput.value) {
                var tags = getCurrentTags(hiddenInput);
                if (tags.length > 0) {
                    removeTag(hiddenInput, wrapper, tags[tags.length - 1]);
                }
            }
            // Vírgula como separador
            if (e.key === ',') {
                e.preventDefault();
                var val2 = textInput.value.trim();
                if (val2) {
                    addTag(hiddenInput, wrapper, val2);
                    textInput.value = '';
                    hideSuggestions(wrapper);
                }
            }
        });

        // Delegate: clique no botão remover de um pill
        wrapper.addEventListener('click', function (e) {
            var removeBtn = e.target.closest('.cst-tag-remove');
            if (removeBtn) {
                var tagName = removeBtn.getAttribute('data-tag');
                removeTag(hiddenInput, wrapper, tagName);
            }
        });
    }

    // ═══════════════════════════════════════
    //  INIT
    // ═══════════════════════════════════════

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTagField);
    } else {
        initTagField();
    }
})();
