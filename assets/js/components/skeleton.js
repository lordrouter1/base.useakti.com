/**
 * Akti — Skeleton Loading Component
 * Generates placeholder skeleton elements while content loads via AJAX.
 *
 * Usage:
 *   AktiSkeleton.table('#tableBody', 5, 4);   // 5 rows, 4 cols
 *   AktiSkeleton.cards('#container', 6);        // 6 cards
 *   AktiSkeleton.form('#formContainer', 5);     // 5 fields
 *   AktiSkeleton.remove('#tableBody');           // remove skeletons
 */
(function() {
    'use strict';

    function repeat(fn, n) {
        var html = '';
        for (var i = 0; i < n; i++) html += fn(i);
        return html;
    }

    function table(selector, rows, cols) {
        rows = rows || 5;
        cols = cols || 4;
        var el = document.querySelector(selector);
        if (!el) return;

        var html = repeat(function() {
            return '<tr class="ds-skeleton-row-tr">' +
                repeat(function() {
                    var w = Math.floor(Math.random() * 40) + 40;
                    return '<td><div class="ds-skeleton ds-skeleton-text" style="width:' + w + '%;height:14px;"></div></td>';
                }, cols) +
                '</tr>';
        }, rows);

        el.innerHTML = html;
    }

    function cards(selector, count) {
        count = count || 4;
        var el = document.querySelector(selector);
        if (!el) return;

        var html = repeat(function() {
            return '<div class="col">' +
                '<div class="ds-skeleton ds-skeleton-card" style="height:140px;"></div>' +
                '</div>';
        }, count);

        el.innerHTML = html;
    }

    function form(selector, fields) {
        fields = fields || 4;
        var el = document.querySelector(selector);
        if (!el) return;

        var html = repeat(function() {
            return '<div style="margin-bottom:16px;">' +
                '<div class="ds-skeleton ds-skeleton-text short" style="height:12px;margin-bottom:6px;"></div>' +
                '<div class="ds-skeleton" style="height:40px;border-radius:var(--ds-radius-md,8px);"></div>' +
                '</div>';
        }, fields);

        el.innerHTML = html;
    }

    function list(selector, items) {
        items = items || 5;
        var el = document.querySelector(selector);
        if (!el) return;

        var html = repeat(function() {
            return '<div style="display:flex;gap:12px;align-items:center;padding:12px 0;border-bottom:1px solid var(--border-light,#e9ecef);">' +
                '<div class="ds-skeleton ds-skeleton-circle" style="width:40px;height:40px;flex-shrink:0;"></div>' +
                '<div style="flex:1;">' +
                '<div class="ds-skeleton ds-skeleton-text medium" style="height:14px;margin-bottom:6px;"></div>' +
                '<div class="ds-skeleton ds-skeleton-text short" style="height:11px;"></div>' +
                '</div>' +
                '</div>';
        }, items);

        el.innerHTML = html;
    }

    function remove(selector) {
        var el = document.querySelector(selector);
        if (!el) return;
        var skeletons = el.querySelectorAll('.ds-skeleton, .ds-skeleton-row-tr');
        for (var i = 0; i < skeletons.length; i++) {
            skeletons[i].parentNode.removeChild(skeletons[i]);
        }
    }

    window.AktiSkeleton = {
        table: table,
        cards: cards,
        form: form,
        list: list,
        remove: remove,
    };

})();
