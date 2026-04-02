/**
 * Focus Trap — utilitário para aprisionar foco do teclado dentro de modais.
 *
 * Uso:
 *   var trap = window.aktiTrapFocus(modalElement);
 *   // ... quando fechar o modal:
 *   trap.release();
 */
(function () {
    'use strict';

    var FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

    function trapFocus(container) {
        var previouslyFocused = document.activeElement;

        function handleKeydown(e) {
            if (e.key !== 'Tab') return;

            var focusable = Array.prototype.slice.call(container.querySelectorAll(FOCUSABLE));
            focusable = focusable.filter(function (el) {
                return el.offsetParent !== null; // visible only
            });
            if (focusable.length === 0) return;

            var first = focusable[0];
            var last = focusable[focusable.length - 1];

            if (e.shiftKey) {
                if (document.activeElement === first) {
                    e.preventDefault();
                    last.focus();
                }
            } else {
                if (document.activeElement === last) {
                    e.preventDefault();
                    first.focus();
                }
            }
        }

        container.addEventListener('keydown', handleKeydown);

        // Focus first focusable element
        var firstFocusable = container.querySelector(FOCUSABLE);
        if (firstFocusable) firstFocusable.focus();

        return {
            release: function () {
                container.removeEventListener('keydown', handleKeydown);
                if (previouslyFocused && previouslyFocused.focus) {
                    previouslyFocused.focus();
                }
            }
        };
    }

    window.aktiTrapFocus = trapFocus;
})();
