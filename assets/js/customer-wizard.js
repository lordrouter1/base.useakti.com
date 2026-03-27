/**
 * Módulo de Clientes — Wizard Multi-Step + Toggle PF/PJ + Completude + Auto-save
 */
(function () {
    'use strict';

    function el(id) { return document.getElementById(id); }

    /* ═══════════════════════════════════════
       WIZARD — Navegação entre Steps
       ═══════════════════════════════════════ */

    var currentStep = 1;
    var totalSteps = 4;

    function goToStep(step) {
        if (step < 1 || step > totalSteps) return;

        // Desativar steps
        document.querySelectorAll('.cst-step-content').forEach(function (s) {
            s.classList.remove('active');
        });
        document.querySelectorAll('.cst-step').forEach(function (s) {
            s.classList.remove('active');
            var stepNum = parseInt(s.dataset.step);
            if (stepNum < step) {
                s.classList.add('completed');
            } else {
                s.classList.remove('completed');
            }
        });

        // Ativar novo step
        var stepContent = el('cst-step-' + step);
        if (stepContent) stepContent.classList.add('active');

        var stepIndicator = document.querySelector('.cst-step[data-step="' + step + '"]');
        if (stepIndicator) stepIndicator.classList.add('active');

        currentStep = step;

        // Atualizar botões de navegação
        var btnPrev = el('btnWizardPrev');
        var btnNext = el('btnWizardNext');
        var btnSubmit = el('btnWizardSubmit');

        if (btnPrev) btnPrev.style.display = (step === 1) ? 'none' : '';
        if (btnNext) btnNext.style.display = (step === totalSteps) ? 'none' : '';
        if (btnSubmit) btnSubmit.style.display = (step === totalSteps) ? '' : 'none';

        // Atualizar completude
        if (window.CstCompleteness) window.CstCompleteness.update();

        // Auto scroll para o topo do formulário
        var formCard = document.querySelector('.cst-wizard-card');
        if (formCard) formCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    /* ═══════════════════════════════════════
       TOGGLE PF/PJ — Campos condicionais
       ═══════════════════════════════════════ */

    function setPersonType(type) {
        var input = el('person_type');
        if (input) input.value = type;

        // Toggle visual
        document.querySelectorAll('.cst-toggle-option').forEach(function (opt) {
            opt.classList.toggle('active', opt.dataset.type === type);
        });

        // Labels dinâmicos
        var labels = {
            'name': type === 'PJ' ? 'Razão Social' : 'Nome Completo',
            'fantasy_name': type === 'PJ' ? 'Nome Fantasia' : 'Apelido',
            'document': type === 'PJ' ? 'CNPJ' : 'CPF',
            'rg_ie': type === 'PJ' ? 'Inscrição Estadual' : 'RG',
            'birth_date': type === 'PJ' ? 'Data de Fundação' : 'Data de Nascimento'
        };

        for (var fieldId in labels) {
            var label = document.querySelector('label[for="' + fieldId + '"]');
            if (label) {
                var requiredSpan = label.querySelector('.text-danger');
                label.textContent = labels[fieldId] + ' ';
                if (requiredSpan) label.appendChild(requiredSpan);
            }
        }

        // Campos condicionais — PF: gênero visível, IM oculto, contato PJ oculto
        var genderGroup    = el('group-gender');
        var imGroup        = el('group-im');
        var contactPjGroup = el('group-contact-pj');
        var cnpjSearchBtn  = el('btnSearchCnpj');

        if (genderGroup)    genderGroup.style.display    = (type === 'PF') ? '' : 'none';
        if (imGroup)        imGroup.style.display        = (type === 'PJ') ? '' : 'none';
        if (contactPjGroup) contactPjGroup.style.display = (type === 'PJ') ? '' : 'none';
        if (cnpjSearchBtn)  cnpjSearchBtn.style.display  = (type === 'PJ') ? '' : 'none';

        // Trocar máscara do documento
        var docField = el('document');
        var prevDocValue = docField ? docField.value : '';

        if (window.CstMasks && window.CstMasks.setDocumentMask) {
            window.CstMasks.setDocumentMask(type);
        }

        // Só limpar campo de documento se o tipo realmente mudou e o usuário fez a troca interativa
        // (não limpar na inicialização quando já existe valor vindo do servidor)
        if (docField) {
            if (docField.dataset.initialized && docField.dataset.lastType && docField.dataset.lastType !== type) {
                // Tipo mudou interativamente: limpar campo
                docField.value = '';
                docField.classList.remove('is-valid', 'is-invalid', 'cst-field-valid', 'cst-field-invalid');
                var msgs = docField.parentNode.querySelectorAll('.cst-field-msg');
                msgs.forEach(function (m) { m.remove(); });
            } else if (prevDocValue) {
                // Inicialização: restaurar valor original que a máscara pode ter limpado
                docField.value = prevDocValue;
            }
            docField.dataset.initialized = '1';
            docField.dataset.lastType = type;
        }
    }

    /* ═══════════════════════════════════════
       COMPLETUDE — Indicador de progresso
       ═══════════════════════════════════════ */

    window.CstCompleteness = window.CstCompleteness || {};

    window.CstCompleteness.update = function () {
        var bar = el('completeness-fill');
        var text = el('completeness-text');
        var checks = el('completeness-checks');
        if (!bar) return;

        var score = 0;
        var total = 0;
        var groups = { identification: false, contact: false, address: false, commercial: false };

        // Identificação (30%)
        total += 30;
        var idScore = 0;
        if (val('person_type')) idScore += 10;
        if (val('name') && val('name').length >= 3) idScore += 10;
        if (val('document') && val('document').replace(/\D/g, '').length >= 11) idScore += 10;
        score += idScore;
        groups.identification = idScore >= 20;

        // Contato (25%)
        total += 25;
        var ctScore = 0;
        if (val('email') || val('cellphone')) ctScore += 15;
        if (val('email') && val('cellphone')) ctScore += 5;
        if (val('phone') || val('website') || val('instagram')) ctScore += 5;
        score += ctScore;
        groups.contact = ctScore >= 15;

        // Endereço (25%)
        total += 25;
        var addrScore = 0;
        if (val('zipcode')) addrScore += 8;
        if (val('address_city')) addrScore += 8;
        if (val('address_state')) addrScore += 5;
        if (val('address_street')) addrScore += 4;
        score += addrScore;
        groups.address = addrScore >= 16;

        // Comercial (20%)
        total += 20;
        var comScore = 0;
        if (val('price_table_id')) comScore += 5;
        if (val('payment_term')) comScore += 5;
        if (val('seller_id')) comScore += 5;
        if (val('tags')) comScore += 5;
        score += comScore;
        groups.commercial = comScore >= 5;

        var pct = Math.round((score / total) * 100);

        bar.style.width = pct + '%';
        bar.className = 'cst-completeness-fill ' + (pct < 40 ? 'low' : pct < 70 ? 'medium' : 'high');

        if (text) text.textContent = 'Completude: ' + pct + '%';

        if (checks) {
            checks.innerHTML =
                '<span class="' + (groups.identification ? 'done' : 'pending') + '">' +
                    (groups.identification ? '✅' : '❌') + ' Identificação</span>' +
                '<span class="' + (groups.contact ? 'done' : 'pending') + '">' +
                    (groups.contact ? '✅' : '❌') + ' Contato</span>' +
                '<span class="' + (groups.address ? 'done' : 'pending') + '">' +
                    (groups.address ? '✅' : '❌') + ' Endereço</span>' +
                '<span class="' + (groups.commercial ? 'done' : 'pending') + '">' +
                    (groups.commercial ? '✅' : '❌') + ' Comercial</span>';
        }
    };

    function val(id) {
        var f = el(id);
        return f ? f.value.trim() : '';
    }

    /* ═══════════════════════════════════════
       AUTO-SAVE — localStorage
       ═══════════════════════════════════════ */

    var draftKey = 'akti_customer_draft';

    function saveDraft() {
        var form = el('customerForm');
        if (!form) return;
        var data = {};
        var inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(function (inp) {
            if (inp.name && inp.type !== 'file' && inp.name !== 'csrf_token') {
                data[inp.name] = inp.value;
            }
        });
        var idField = document.querySelector('input[name="id"]');
        var suffix = idField ? '_edit_' + idField.value : '_create';
        try {
            localStorage.setItem(draftKey + suffix, JSON.stringify({ ts: Date.now(), data: data }));
        } catch (e) { /* storage full */ }
    }

    function loadDraft() {
        // No modo de edição, não sugerir restauração de rascunho
        var editField = el('edit_customer_id');
        if (editField && editField.value) {
            clearDraft();
            return;
        }

        var idField = document.querySelector('input[name="id"]');
        var suffix = idField ? '_edit_' + idField.value : '_create';
        try {
            var raw = localStorage.getItem(draftKey + suffix);
            if (!raw) return;
            var parsed = JSON.parse(raw);
            // Se rascunho tem mais de 24h, descartar
            if (Date.now() - parsed.ts > 86400000) {
                localStorage.removeItem(draftKey + suffix);
                return;
            }
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'question',
                    title: 'Rascunho encontrado',
                    html: 'Deseja restaurar os dados salvos automaticamente?',
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-undo me-1"></i>Restaurar',
                    cancelButtonText: 'Ignorar',
                    confirmButtonColor: '#3498db'
                }).then(function (result) {
                    if (result.isConfirmed) {
                        for (var key in parsed.data) {
                            var f = document.querySelector('[name="' + key + '"]');
                            if (f && f.type !== 'file') f.value = parsed.data[key];
                        }
                        // Atualizar toggle PF/PJ
                        var pt = parsed.data.person_type;
                        if (pt) setPersonType(pt);
                        if (window.CstCompleteness) window.CstCompleteness.update();
                        Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 })
                            .fire({ icon: 'success', title: 'Rascunho restaurado' });
                    } else {
                        clearDraft();
                    }
                });
            }
        } catch (e) { /* malformed JSON */ }
    }

    function clearDraft() {
        var idField = document.querySelector('input[name="id"]');
        var suffix = idField ? '_edit_' + idField.value : '_create';
        localStorage.removeItem(draftKey + suffix);
    }

    /* ═══════════════════════════════════════
       ATALHOS DE TECLADO
       ═══════════════════════════════════════ */

    function bindShortcuts() {
        document.addEventListener('keydown', function (e) {
            // Ctrl+S → Salvar
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                var form = el('customerForm');
                if (form) form.requestSubmit ? form.requestSubmit() : form.submit();
            }
            // Ctrl+→ → Próximo step
            if (e.ctrlKey && e.key === 'ArrowRight') {
                e.preventDefault();
                goToStep(currentStep + 1);
            }
            // Ctrl+← → Step anterior
            if (e.ctrlKey && e.key === 'ArrowLeft') {
                e.preventDefault();
                goToStep(currentStep - 1);
            }
            // Esc → Voltar
            if (e.key === 'Escape') {
                var modal = document.querySelector('.modal.show');
                if (!modal) {
                    window.location.href = '?page=customers';
                }
            }
        });
    }

    /* ═══════════════════════════════════════
       UPLOAD DE FOTO — Preview
       ═══════════════════════════════════════ */

    function bindPhotoUpload() {
        var photoInput = el('photo');
        var photoUpload = document.querySelector('.cst-photo-upload');
        var previewImg = el('preview-photo');

        if (!photoInput || !photoUpload) return;

        photoUpload.addEventListener('click', function () {
            photoInput.click();
        });

        // Drag & drop
        photoUpload.addEventListener('dragover', function (e) {
            e.preventDefault();
            photoUpload.style.borderColor = 'var(--cst-primary)';
        });
        photoUpload.addEventListener('dragleave', function () {
            photoUpload.style.borderColor = '';
        });
        photoUpload.addEventListener('drop', function (e) {
            e.preventDefault();
            photoUpload.style.borderColor = '';
            if (e.dataTransfer.files.length) {
                photoInput.files = e.dataTransfer.files;
                showPhotoPreview(e.dataTransfer.files[0]);
            }
        });

        photoInput.addEventListener('change', function () {
            if (this.files[0]) showPhotoPreview(this.files[0]);
        });

        function showPhotoPreview(file) {
            var reader = new FileReader();
            reader.onload = function (e) {
                if (previewImg) {
                    previewImg.src = e.target.result;
                    previewImg.style.display = 'block';
                }
                var placeholder = photoUpload.querySelector('.cst-photo-placeholder');
                if (placeholder) placeholder.style.display = 'none';
                photoUpload.classList.add('has-photo');
            };
            reader.readAsDataURL(file);
        }
    }

    /* ═══════════════════════════════════════
       INIT
       ═══════════════════════════════════════ */

    function init() {
        // Wizard navigation
        var btnNext = el('btnWizardNext');
        var btnPrev = el('btnWizardPrev');

        if (btnNext) btnNext.addEventListener('click', function () { goToStep(currentStep + 1); });
        if (btnPrev) btnPrev.addEventListener('click', function () { goToStep(currentStep - 1); });

        // Step indicators clickable — permite pular para qualquer step
        document.querySelectorAll('.cst-step').forEach(function (step) {
            step.style.cursor = 'pointer';
            step.addEventListener('click', function () {
                var num = parseInt(this.dataset.step);
                goToStep(num);
            });
        });

        // PF/PJ toggle
        document.querySelectorAll('.cst-toggle-option').forEach(function (opt) {
            opt.addEventListener('click', function () {
                setPersonType(this.dataset.type);
            });
        });

        // CNPJ search button
        var btnCnpj = el('btnSearchCnpj');
        if (btnCnpj) {
            btnCnpj.addEventListener('click', function () {
                if (window.CstValidation) window.CstValidation.searchCnpj();
            });
        }

        // Photo upload
        bindPhotoUpload();

        // Atalhos
        bindShortcuts();

        // Iniciar PF/PJ com valor atual
        var ptInput = el('person_type');
        if (ptInput && ptInput.value) {
            setPersonType(ptInput.value);
        } else {
            setPersonType('PF');
        }

        // Auto-save: salvar a cada 30s
        setInterval(saveDraft, 30000);

        // Form submit: limpar rascunho
        var form = el('customerForm');
        if (form) {
            form.addEventListener('submit', function () { clearDraft(); });
        }

        // Carregar rascunho
        loadDraft();

        // Monitorar campos para completude
        var formInputs = document.querySelectorAll('#customerForm input, #customerForm select, #customerForm textarea');
        formInputs.forEach(function (inp) {
            inp.addEventListener('change', function () {
                if (window.CstCompleteness) window.CstCompleteness.update();
            });
            inp.addEventListener('input', function () {
                if (window.CstCompleteness) window.CstCompleteness.update();
            });
        });

        // Atualizar completude inicial
        setTimeout(function () {
            if (window.CstCompleteness) window.CstCompleteness.update();
        }, 500);

        // Primeiro step ativo
        goToStep(1);
    }

    // Expor goToStep globalmente para uso do stepper
    window.CstWizard = window.CstWizard || {};
    window.CstWizard.goToStep = goToStep;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
