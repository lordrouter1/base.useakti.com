/**
 * Módulo de Clientes — Máscaras de Input (IMask.js)
 * Configuração dinâmica de máscaras conforme PF/PJ
 */
(function () {
    'use strict';

    /* ── Helpers ── */
    function el(id) { return document.getElementById(id); }

    /* Aguardar IMask estar disponível */
    var _maskRetries = 0;
    function initMasks() {
        if (typeof IMask === 'undefined') {
            if (++_maskRetries > 25) {
                console.error('[customer-masks] IMask.js não carregou após 5s. Máscaras desativadas.');
                return;
            }
            console.warn('[customer-masks] IMask.js não carregado, tentando novamente em 200ms...');
            return setTimeout(initMasks, 200);
        }

        /* ── Campos ── */
        var docField         = el('document');
        var phoneField       = el('phone');
        var cellphoneField   = el('cellphone');
        var phoneCommField   = el('phone_commercial');
        var zipField         = el('zipcode');
        var birthField       = el('birth_date');
        var creditField      = el('credit_limit');
        var discountField    = el('discount_default');

        /* ── Máscara de Documento (CPF/CNPJ dinâmica) ── */
        var docMask = null;

        window.CstMasks = window.CstMasks || {};

        window.CstMasks.setDocumentMask = function (personType) {
            if (!docField) return;
            // Preservar valor atual antes de destruir a máscara
            var currentValue = docField.value;
            if (docMask) docMask.destroy();

            if (personType === 'PJ') {
                docMask = IMask(docField, {
                    mask: '00.000.000/0000-00'
                });
            } else {
                docMask = IMask(docField, {
                    mask: '000.000.000-00'
                });
            }

            // Restaurar valor: aceitar tanto formatado quanto apenas dígitos
            if (currentValue) {
                var digits = currentValue.replace(/\D/g, '');
                if (digits) {
                    docMask.unmaskedValue = digits;
                }
            }
        };

        /* Inicializar com tipo atual */
        var initialType = 'PF';
        var toggleActive = document.querySelector('.cst-toggle-option.active');
        if (toggleActive && toggleActive.dataset.type) {
            initialType = toggleActive.dataset.type;
        }
        var personTypeInput = el('person_type');
        if (personTypeInput && personTypeInput.value) {
            initialType = personTypeInput.value;
        }
        window.CstMasks.setDocumentMask(initialType);

        /* ── Telefone fixo ── */
        if (phoneField) {
            IMask(phoneField, {
                mask: [
                    { mask: '(00) 0000-0000' },
                    { mask: '(00) 00000-0000' }
                ],
                dispatch: function (appended, dynamicMasked) {
                    var number = (dynamicMasked.value + appended).replace(/\D/g, '');
                    return dynamicMasked.compiledMasks[number.length > 10 ? 1 : 0];
                }
            });
        }

        /* ── Celular ── */
        if (cellphoneField) {
            IMask(cellphoneField, {
                mask: '(00) 00000-0000'
            });
        }

        /* ── Telefone Comercial ── */
        if (phoneCommField) {
            IMask(phoneCommField, {
                mask: [
                    { mask: '(00) 0000-0000' },
                    { mask: '(00) 00000-0000' }
                ],
                dispatch: function (appended, dynamicMasked) {
                    var number = (dynamicMasked.value + appended).replace(/\D/g, '');
                    return dynamicMasked.compiledMasks[number.length > 10 ? 1 : 0];
                }
            });
        }

        /* ── CEP ── */
        if (zipField) {
            IMask(zipField, {
                mask: '00000-000'
            });
        }

        /* ── Data de Nascimento / Fundação ── */
        if (birthField) {
            IMask(birthField, {
                mask: '00/00/0000'
            });
        }

        /* ── Limite de Crédito (moeda) ── */
        if (creditField) {
            IMask(creditField, {
                mask: 'R$ num',
                blocks: {
                    num: {
                        mask: Number,
                        thousandsSeparator: '.',
                        radix: ',',
                        mapToRadix: ['.'],
                        scale: 2,
                        signed: false,
                        normalizeZeros: true,
                        padFractionalZeros: true,
                        min: 0,
                        max: 99999999.99
                    }
                }
            });
        }

        /* ── Desconto padrão (%) ── */
        if (discountField) {
            IMask(discountField, {
                mask: Number,
                scale: 2,
                radix: ',',
                mapToRadix: ['.'],
                signed: false,
                min: 0,
                max: 100,
                normalizeZeros: true,
                padFractionalZeros: true
            });
        }
    }

    /* Iniciar quando DOM estiver pronto */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMasks);
    } else {
        initMasks();
    }
})();
