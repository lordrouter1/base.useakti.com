/**
 * customer-select2.js — Integração Select2 + endpoint PHP para busca de clientes.
 *
 * Funciona em:
 *   - Cadastro de Pedidos (create.php)  → select #customer_id  ou .customer-select
 *   - Edição de Pedidos  (edit.php)     → select [name="customer_id"]
 *   - Pipeline Detail    (detail.php)   → qualquer .customer-select
 *
 * A busca é feita via endpoint PHP: ?page=customers&action=searchSelect2
 */
(function () {
  'use strict';

  // ── Configuração ─────────────────────────────────────────────
  var SEARCH_ENDPOINT = '?page=customers&action=searchSelect2';

  // ── Helpers ───────────────────────────────────────────────────

  function escapeHtml(str) {
    if (!str) return '';
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
  }

  function formatPhone(phone) {
    if (!phone) return '';
    return phone;
  }

  /**
   * Template de exibição do resultado no dropdown do Select2.
   */
  function templateResult(item) {
    if (item.loading) return item.text;
    if (!item.id) return item.text;

    var doc = item.document ? '<span class="badge bg-secondary ms-1" style="font-size:0.65rem;">' + escapeHtml(item.document) + '</span>' : '';
    var phone = item.phone ? '<i class="fas fa-phone me-1 text-muted" style="font-size:0.7rem;"></i>' + escapeHtml(formatPhone(item.phone)) : '';
    var email = item.email ? '<i class="fas fa-envelope me-1 text-muted" style="font-size:0.7rem;"></i>' + escapeHtml(item.email) : '';
    var details = [];
    if (phone) details.push(phone);
    if (email) details.push(email);
    var detailsHtml = details.length > 0
      ? '<div class="small text-muted text-truncate" style="max-width:400px;">' + details.join(' &middot; ') + '</div>'
      : '';

    return $('<div class="py-1">' +
      '<div class="fw-semibold">' + escapeHtml(item.text) + doc + '</div>' +
      detailsHtml +
      '</div>');
  }

  /**
   * Template de exibição do item selecionado.
   */
  function templateSelection(item) {
    if (!item.id) return item.text;
    var doc = item.document ? ' (' + item.document + ')' : '';
    return item.text + doc;
  }

  // ── Inicialização Select2 ────────────────────────────────────

  function initCustomerSelect(el) {
    var $el = $(el);
    if ($el.data('cs2-init')) return; // já inicializado

    // Preservar a opção selecionada (para edit forms)
    var currentVal = $el.val();
    var currentText = $el.find('option:selected').text();

    $el.select2({
      theme: 'bootstrap-5',
      placeholder: $el.attr('data-placeholder') || 'Digite para buscar um cliente...',
      allowClear: true,
      width: '100%',
      minimumInputLength: 0,
      language: {
        inputTooShort: function () { return 'Digite para buscar...'; },
        noResults: function () { return 'Nenhum cliente encontrado.'; },
        searching: function () { return 'Buscando...'; },
        errorLoading: function () { return 'Erro ao buscar clientes.'; },
      },
      ajax: {
        url: SEARCH_ENDPOINT,
        delay: 300,
        dataType: 'json',
        data: function (params) {
          return { q: params.term || '', limit: 15 };
        },
        processResults: function (resp) {
          var rows = (resp && resp.data) ? resp.data : [];
          return {
            results: rows.map(function (r) {
              return {
                id: r.id,
                text: r.name || '',
                email: r.email || '',
                phone: r.phone || '',
                document: r.document || ''
              };
            })
          };
        },
        cache: true
      },
      templateResult: templateResult,
      templateSelection: templateSelection,
      escapeMarkup: function (m) { return m; }
    });

    // Restaurar seleção atual (para edição)
    if (currentVal && currentText) {
      // Criar a opção selecionada para que Select2 a reconheça
      var $opt = $el.find('option[value="' + currentVal + '"]');
      if (!$opt.length) {
        var newOpt = new Option(currentText, currentVal, true, true);
        $el.append(newOpt);
      }
      $el.val(currentVal).trigger('change.select2');
    }

    $el.data('cs2-init', true);
  }

  // ── Inicialização principal ──────────────────────────────────

  $(function () {
    if (typeof $.fn.select2 !== 'function') return;

    // Inicializar por ID (página de criar/editar pedido)
    var $custId = $('#customer_id');
    if ($custId.length && !$custId.data('cs2-init')) {
      initCustomerSelect($custId[0]);
    }

    // Inicializar por classe (uso genérico)
    $('.customer-select').each(function () {
      initCustomerSelect(this);
    });

    // Observer: inicializar selects adicionados dinamicamente
    var body = document.body;
    if (body) {
      var observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (m) {
          if (!m.addedNodes) return;
          m.addedNodes.forEach(function (node) {
            if (node.nodeType !== 1) return;
            $(node).find('.customer-select').addBack('.customer-select').each(function () {
              initCustomerSelect(this);
            });
            // Também checar por #customer_id se inserido dinamicamente
            if (node.id === 'customer_id' || $(node).find('#customer_id').length) {
              var $c = $(node).is('#customer_id') ? $(node) : $(node).find('#customer_id');
              $c.each(function () { initCustomerSelect(this); });
            }
          });
        });
      });
      observer.observe(body, { childList: true, subtree: true });
    }
  });

})();
