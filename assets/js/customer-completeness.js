/**
 * Módulo de Clientes — Indicador de Completude do Cadastro
 *
 * Fase 4 — Item 4.3
 *
 * Barra de progresso no formulário que mostra % de preenchimento.
 *
 * Pesos dos campos:
 *   Identificação (30%): person_type, name, document + fantasy, rg_ie
 *   Contato       (25%): email OU cellphone (ao menos 1) + extras
 *   Endereço      (25%): zipcode + city + state (mínimo) + extras
 *   Comercial     (20%): Qualquer campo preenchido conta
 *
 * Este arquivo é um módulo standalone que complementa o CstCompleteness
 * já embutido no customer-wizard.js, adicionando funcionalidades extras
 * como mini-dashboard e detalhamento por campo.
 */
(function () {
    'use strict';

    function el(id) { return document.getElementById(id); }
    function val(id) {
        var f = el(id);
        return f ? f.value.trim() : '';
    }

    var WEIGHTS = {
        identification: { weight: 30, fields: ['person_type', 'name', 'document', 'fantasy_name', 'rg_ie'] },
        contact:        { weight: 25, fields: ['email', 'cellphone', 'phone', 'email_secondary', 'phone_commercial', 'website', 'instagram'] },
        address:        { weight: 25, fields: ['zipcode', 'address_street', 'address_number', 'address_neighborhood', 'address_city', 'address_state'] },
        commercial:     { weight: 20, fields: ['price_table_id', 'payment_term', 'credit_limit', 'discount_default', 'seller_id', 'origin', 'tags', 'observations'] }
    };

    /**
     * Calcula a completude por grupo e total.
     * @returns {{total: number, groups: object}}
     */
    function calculate() {
        var result = { total: 0, groups: {} };

        // Identificação (30%)
        var idScore = 0, idMax = 30;
        if (val('person_type')) idScore += 10;
        if (val('name') && val('name').length >= 3) idScore += 10;
        if (val('document') && val('document').replace(/\D/g, '').length >= 11) idScore += 6;
        if (val('fantasy_name')) idScore += 2;
        if (val('rg_ie')) idScore += 2;
        result.groups.identification = { score: idScore, max: idMax, done: idScore >= 20 };
        result.total += idScore;

        // Contato (25%)
        var ctScore = 0, ctMax = 25;
        var hasEmail = !!val('email');
        var hasCell = !!val('cellphone');
        if (hasEmail || hasCell) ctScore += 12;
        if (hasEmail && hasCell) ctScore += 5;
        if (val('phone')) ctScore += 3;
        if (val('website') || val('instagram')) ctScore += 3;
        if (val('email_secondary')) ctScore += 1;
        if (val('phone_commercial')) ctScore += 1;
        ctScore = Math.min(ctScore, ctMax);
        result.groups.contact = { score: ctScore, max: ctMax, done: ctScore >= 12 };
        result.total += ctScore;

        // Endereço (25%)
        var adScore = 0, adMax = 25;
        if (val('zipcode')) adScore += 7;
        if (val('address_city')) adScore += 6;
        if (val('address_state')) adScore += 5;
        if (val('address_street')) adScore += 4;
        if (val('address_number')) adScore += 2;
        if (val('address_neighborhood')) adScore += 1;
        adScore = Math.min(adScore, adMax);
        result.groups.address = { score: adScore, max: adMax, done: adScore >= 16 };
        result.total += adScore;

        // Comercial (20%)
        var comScore = 0, comMax = 20;
        var comFields = WEIGHTS.commercial.fields;
        comFields.forEach(function (f) {
            if (val(f)) comScore += Math.floor(comMax / comFields.length);
        });
        comScore = Math.min(comScore, comMax);
        result.groups.commercial = { score: comScore, max: comMax, done: comScore >= 5 };
        result.total += comScore;

        return result;
    }

    /**
     * Atualiza a UI do indicador de completude.
     */
    function update() {
        var data = calculate();
        var pct = data.total;

        // Barra principal
        var bar = el('completeness-fill');
        if (bar) {
            bar.style.width = pct + '%';
            bar.className = 'cst-completeness-fill ' + (pct < 40 ? 'low' : pct < 70 ? 'medium' : 'high');
        }

        // Texto
        var text = el('completeness-text');
        if (text) text.textContent = 'Completude: ' + pct + '%';

        // Checklist de grupos
        var checks = el('completeness-checks');
        if (checks) {
            var names = { identification: 'Identificação', contact: 'Contato', address: 'Endereço', commercial: 'Comercial' };
            var html = '';
            for (var key in data.groups) {
                var g = data.groups[key];
                html += '<span class="' + (g.done ? 'done' : 'pending') + '" title="' + g.score + '/' + g.max + ' pontos">' +
                    (g.done ? '✅' : '❌') + ' ' + names[key] +
                    ' <small style="opacity:.6;">(' + Math.round((g.score / g.max) * 100) + '%)</small>' +
                    '</span>';
            }
            checks.innerHTML = html;
        }

        // Mini badge de completude (se existir no header do form)
        var badge = el('completeness-badge');
        if (badge) {
            badge.textContent = pct + '%';
            badge.style.background = pct < 40 ? '#e74c3c' : pct < 70 ? '#f39c12' : '#27ae60';
        }
    }

    /**
     * Retorna os dados de completude (para uso externo).
     */
    function getData() {
        return calculate();
    }

    // Expor globalmente
    window.CstCompleteness = window.CstCompleteness || {};
    window.CstCompleteness.update = update;
    window.CstCompleteness.getData = getData;
    window.CstCompleteness.calculate = calculate;

    // Bind automático
    function bindCompletenessEvents() {
        var form = el('customerForm');
        if (!form) return;

        var inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(function (inp) {
            inp.addEventListener('change', update);
            inp.addEventListener('input', update);
        });

        // Atualizar inicialmente
        setTimeout(update, 300);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindCompletenessEvents);
    } else {
        bindCompletenessEvents();
    }
})();
