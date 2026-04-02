/**
 * Módulo de Clientes — Validação Client-Side em Tempo Real
 * Validações no blur de cada campo + validação geral no submit
 */
(function () {
    'use strict';

    function el(id) { return document.getElementById(id); }

    function escHtml(str) {
        var d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    /* ═══════════════════════════════════════
       Algoritmos de Validação
       ═══════════════════════════════════════ */

    function isValidCPF(cpf) {
        cpf = cpf.replace(/\D/g, '');
        if (cpf.length !== 11) return false;
        if (/^(\d)\1{10}$/.test(cpf)) return false;
        var sum = 0, i;
        for (i = 0; i < 9; i++) sum += parseInt(cpf[i]) * (10 - i);
        var d1 = 11 - (sum % 11);
        if (d1 >= 10) d1 = 0;
        if (parseInt(cpf[9]) !== d1) return false;
        sum = 0;
        for (i = 0; i < 10; i++) sum += parseInt(cpf[i]) * (11 - i);
        var d2 = 11 - (sum % 11);
        if (d2 >= 10) d2 = 0;
        return parseInt(cpf[10]) === d2;
    }

    function isValidCNPJ(cnpj) {
        cnpj = cnpj.replace(/\D/g, '');
        if (cnpj.length !== 14) return false;
        if (/^(\d)\1{13}$/.test(cnpj)) return false;
        var w1 = [5,4,3,2,9,8,7,6,5,4,3,2];
        var w2 = [6,5,4,3,2,9,8,7,6,5,4,3,2];
        var sum = 0, i;
        for (i = 0; i < 12; i++) sum += parseInt(cnpj[i]) * w1[i];
        var d1 = sum % 11 < 2 ? 0 : 11 - (sum % 11);
        if (parseInt(cnpj[12]) !== d1) return false;
        sum = 0;
        for (i = 0; i < 13; i++) sum += parseInt(cnpj[i]) * w2[i];
        var d2 = sum % 11 < 2 ? 0 : 11 - (sum % 11);
        return parseInt(cnpj[13]) === d2;
    }

    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    function isValidCEP(cep) {
        return /^\d{5}-?\d{3}$/.test(cep);
    }

    function isValidPhone(phone) {
        var cleaned = phone.replace(/\D/g, '');
        return cleaned.length >= 10 && cleaned.length <= 11;
    }

    function isValidURL(url) {
        if (!url) return true;
        try { new URL(url); return true; } catch (e) { return false; }
    }

    /* ═══════════════════════════════════════
       Feedback Visual
       ═══════════════════════════════════════ */

    function setValid(field, msg) {
        if (!field) return;
        field.classList.remove('is-invalid', 'cst-field-invalid');
        field.classList.add('is-valid', 'cst-field-valid');
        removeFeedback(field);
        if (msg) {
            var span = document.createElement('span');
            span.className = 'cst-field-msg valid';
            span.innerHTML = '<i class="fas fa-check-circle me-1"></i>' + msg;
            field.parentNode.appendChild(span);
        }
    }

    function setInvalid(field, msg) {
        if (!field) return;
        field.classList.remove('is-valid', 'cst-field-valid');
        field.classList.add('is-invalid', 'cst-field-invalid');
        removeFeedback(field);
        if (msg) {
            var span = document.createElement('span');
            span.className = 'cst-field-msg invalid';
            span.innerHTML = '<i class="fas fa-exclamation-circle me-1"></i>' + msg;
            field.parentNode.appendChild(span);
        }
    }

    function clearValidation(field) {
        if (!field) return;
        field.classList.remove('is-valid', 'is-invalid', 'cst-field-valid', 'cst-field-invalid');
        removeFeedback(field);
    }

    function removeFeedback(field) {
        if (!field || !field.parentNode) return;
        var msgs = field.parentNode.querySelectorAll('.cst-field-msg');
        msgs.forEach(function (m) { m.remove(); });
    }

    /* ═══════════════════════════════════════
       Validação por Campo (on blur)
       ═══════════════════════════════════════ */

    function getPersonType() {
        var pt = el('person_type');
        return pt ? pt.value : 'PF';
    }

    function validateName() {
        var f = el('name');
        if (!f) return true;
        var v = f.value.trim();
        if (v.length < 3) { setInvalid(f, 'Mínimo 3 caracteres'); return false; }
        if (v.length > 191) { setInvalid(f, 'Máximo 191 caracteres'); return false; }
        setValid(f); return true;
    }

    function validateDocument() {
        var f = el('document');
        if (!f) return true;
        var v = f.value.replace(/\D/g, '');
        if (!v) { clearValidation(f); return true; } // não obrigatório para form
        var pt = getPersonType();
        if (pt === 'PJ') {
            if (!isValidCNPJ(v)) { setInvalid(f, 'CNPJ inválido'); return false; }
        } else {
            if (!isValidCPF(v)) { setInvalid(f, 'CPF inválido'); return false; }
        }
        setValid(f, 'Documento válido');
        // Verificar duplicidade
        checkDuplicate(v);
        return true;
    }

    function validateEmail() {
        var f = el('email');
        if (!f) return true;
        var v = f.value.trim();
        if (!v) { clearValidation(f); return true; }
        if (!isValidEmail(v)) { setInvalid(f, 'E-mail inválido'); return false; }
        setValid(f); return true;
    }

    function validateEmailSecondary() {
        var f = el('email_secondary');
        if (!f) return true;
        var v = f.value.trim();
        if (!v) { clearValidation(f); return true; }
        if (!isValidEmail(v)) { setInvalid(f, 'E-mail inválido'); return false; }
        setValid(f); return true;
    }

    function validateCellphone() {
        var f = el('cellphone');
        if (!f) return true;
        var v = f.value.trim();
        if (!v) { clearValidation(f); return true; }
        if (!isValidPhone(v)) { setInvalid(f, 'Celular inválido'); return false; }
        setValid(f); return true;
    }

    function validatePhone() {
        var f = el('phone');
        if (!f) return true;
        var v = f.value.trim();
        if (!v) { clearValidation(f); return true; }
        if (!isValidPhone(v)) { setInvalid(f, 'Telefone inválido'); return false; }
        setValid(f); return true;
    }

    function validateZipcode() {
        var f = el('zipcode');
        if (!f) return true;
        var v = f.value.trim();
        if (!v) { clearValidation(f); return true; }
        if (!isValidCEP(v)) { setInvalid(f, 'CEP inválido'); return false; }
        setValid(f);
        // Autopreenchimento por CEP
        searchCep(v.replace(/\D/g, ''));
        return true;
    }

    function validateWebsite() {
        var f = el('website');
        if (!f) return true;
        var v = f.value.trim();
        if (!v) { clearValidation(f); return true; }
        if (!isValidURL(v)) { setInvalid(f, 'URL inválida'); return false; }
        setValid(f); return true;
    }

    function validateCreditLimit() {
        var f = el('credit_limit');
        if (!f) return true;
        var v = f.value.replace(/[^\d,.-]/g, '').replace(',', '.');
        if (!v) { clearValidation(f); return true; }
        var num = parseFloat(v);
        if (isNaN(num) || num < 0) { setInvalid(f, 'Deve ser um valor positivo'); return false; }
        setValid(f); return true;
    }

    function validateDiscount() {
        var f = el('discount_default');
        if (!f) return true;
        var v = f.value.replace(/[^\d,.-]/g, '').replace(',', '.');
        if (!v) { clearValidation(f); return true; }
        var num = parseFloat(v);
        if (isNaN(num) || num < 0 || num > 100) { setInvalid(f, 'Deve ser entre 0 e 100'); return false; }
        setValid(f); return true;
    }

    /* ═══════════════════════════════════════
       Verificação de Duplicidade AJAX
       ═══════════════════════════════════════ */

    var _dupDebounce = null;

    function checkDuplicate(doc) {
        if (!doc || doc.length < 11) return;
        clearTimeout(_dupDebounce);
        _dupDebounce = setTimeout(function () {
            var excludeId = '';
            var idField = el('customer_id') || document.querySelector('input[name="id"]');
            if (idField) excludeId = idField.value;

            fetch('?page=customers&action=checkDuplicate&document=' + doc + '&exclude_id=' + excludeId)
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    var f = el('document');
                    if (!f) return;
                    if (data.exists) {
                        removeFeedback(f);
                        var span = document.createElement('span');
                        span.className = 'cst-field-msg invalid';
                        span.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>Já existe: <strong>' +
                            escHtml(data.customer.name || '') + '</strong> (' + escHtml(data.customer.code || '') + ') ' +
                            '<a href="?page=customers&action=view&id=' + parseInt(data.customer.id, 10) + '" target="_blank">Ver cadastro</a>';
                        f.parentNode.appendChild(span);
                        f.classList.add('cst-field-invalid');
                        f.classList.remove('cst-field-valid');
                    }
                })
                .catch(function () { /* silencioso */ });
        }, 400);
    }

    /* ═══════════════════════════════════════
       Auto-preenchimento por CEP (ViaCEP)
       ═══════════════════════════════════════ */

    function searchCep(cep) {
        if (!cep || cep.length !== 8) return;

        var zipField = el('zipcode');
        if (zipField) {
            removeFeedback(zipField);
            var spinner = document.createElement('span');
            spinner.className = 'cst-field-msg valid';
            spinner.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Buscando CEP...';
            zipField.parentNode.appendChild(spinner);
        }

        fetch('?page=customers&action=searchCep&cep=' + cep)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) {
                    if (zipField) setInvalid(zipField, 'CEP não encontrado');
                    return;
                }
                var d = data.data;
                var fieldsToFill = {
                    'address_street': d.address_street,
                    'address_neighborhood': d.address_neighborhood,
                    'address_city': d.address_city,
                    'address_state': d.address_state,
                    'address_ibge': d.address_ibge
                };
                for (var fieldId in fieldsToFill) {
                    var f = el(fieldId);
                    if (f && fieldsToFill[fieldId]) {
                        f.value = fieldsToFill[fieldId];
                        f.classList.add('cst-api-filled');
                        setValid(f);
                    }
                }
                if (zipField) setValid(zipField, 'CEP encontrado');
                // Focar no campo Número
                var numField = el('address_number');
                if (numField) numField.focus();
            })
            .catch(function () {
                if (zipField) setInvalid(zipField, 'Erro ao buscar CEP');
            });
    }

    /* ═══════════════════════════════════════
       Consulta CNPJ (BrasilAPI)
       ═══════════════════════════════════════ */

    window.CstValidation = window.CstValidation || {};

    window.CstValidation.searchCnpj = function () {
        var docField = el('document');
        if (!docField) return;
        var cnpj = docField.value.replace(/\D/g, '');
        if (cnpj.length !== 14 || !isValidCNPJ(cnpj)) {
            setInvalid(docField, 'CNPJ inválido para consulta');
            return;
        }

        var btn = el('btnSearchCnpj');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Consultando...';
        }

        fetch('?page=customers&action=searchCnpj&cnpj=' + cnpj)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'warning', title: 'CNPJ não encontrado', text: 'Não foi possível consultar este CNPJ na base da Receita.', timer: 3000, showConfirmButton: false });
                    }
                    return;
                }
                var d = data.data;
                var fieldsToFill = {
                    'name': d.name,
                    'fantasy_name': d.fantasy_name,
                    'email': d.email,
                    'phone': d.phone,
                    'zipcode': d.zipcode,
                    'address_street': d.address_street,
                    'address_number': d.address_number,
                    'address_complement': d.address_complement,
                    'address_neighborhood': d.address_neighborhood,
                    'address_city': d.address_city,
                    'address_state': d.address_state
                };
                for (var fieldId in fieldsToFill) {
                    var f = el(fieldId);
                    if (f && fieldsToFill[fieldId]) {
                        f.value = fieldsToFill[fieldId];
                        f.classList.add('cst-api-filled');
                    }
                }
                if (typeof Swal !== 'undefined') {
                    Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true })
                        .fire({ icon: 'success', title: 'Empresa encontrada! Dados preenchidos automaticamente.' });
                }
                // Atualizar completude
                if (window.CstCompleteness) window.CstCompleteness.update();
            })
            .catch(function () {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'Erro', text: 'Erro ao consultar CNPJ.', timer: 2500, showConfirmButton: false });
                }
            })
            .finally(function () {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-search me-1"></i>Consultar';
                }
            });
    };

    /* ═══════════════════════════════════════
       Validação do Step (antes de avançar)
       ═══════════════════════════════════════ */

    window.CstValidation.validateStep = function (stepNum) {
        if (stepNum === 1) {
            var nameOk = validateName();
            // person_type é obrigatório
            var pt = getPersonType();
            if (!pt || (pt !== 'PF' && pt !== 'PJ')) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'warning', title: 'Selecione o tipo de pessoa', text: 'Escolha Pessoa Física ou Jurídica.', timer: 2500, showConfirmButton: false });
                }
                return false;
            }
            return nameOk;
        }
        // Steps 2, 3, 4: campos opcionais, sempre libera
        return true;
    };

    /* ═══════════════════════════════════════
       Validação Completa (submit)
       ═══════════════════════════════════════ */

    window.CstValidation.validateAll = function () {
        var results = [
            validateName(),
            validateDocument(),
            validateEmail(),
            validateEmailSecondary(),
            validateCellphone(),
            validatePhone(),
            validateZipcode(),
            validateWebsite(),
            validateCreditLimit(),
            validateDiscount()
        ];
        return results.every(function (r) { return r; });
    };

    /* ═══════════════════════════════════════
       Bind de eventos
       ═══════════════════════════════════════ */

    function bindValidation() {
        var bindings = [
            ['name', 'blur', validateName],
            ['document', 'blur', validateDocument],
            ['email', 'blur', validateEmail],
            ['email_secondary', 'blur', validateEmailSecondary],
            ['cellphone', 'blur', validateCellphone],
            ['phone', 'blur', validatePhone],
            ['zipcode', 'blur', validateZipcode],
            ['website', 'blur', validateWebsite],
            ['credit_limit', 'blur', validateCreditLimit],
            ['discount_default', 'blur', validateDiscount]
        ];

        bindings.forEach(function (b) {
            var field = el(b[0]);
            if (field) {
                field.addEventListener(b[1], b[2]);
            }
        });

        // Validação no submit
        var form = el('customerForm');
        if (form) {
            form.addEventListener('submit', function (e) {
                if (!window.CstValidation.validateAll()) {
                    e.preventDefault();
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'error', title: 'Corrija os campos', text: 'Existem campos com erros. Verifique e tente novamente.', confirmButtonColor: '#3498db' });
                    }
                }
            });
        }
    }

    /* ═══════════════════════════════════════ */

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindValidation);
    } else {
        bindValidation();
    }
})();
