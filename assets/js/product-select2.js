/**
 * product-select2.js — Integração Select2 + endpoint PHP para busca de produtos.
 *
 * Funciona em:
 *   - Cadastro de Pedidos   (create.php)  → selects com classe .product-select
 *   - Pipeline Detail       (detail.php)  → select #pipProductSelect
 *
 * A busca é feita diretamente pelo endpoint PHP (sem necessidade de token JWT ou API Node.js).
 */
(function () {
  'use strict';

  // ── Configuração ─────────────────────────────────────────────
  var SEARCH_ENDPOINT = '?page=products&action=searchSelect2';

  // ── Helpers ───────────────────────────────────────────────────

  function formatPrice(val) {
    if (val === null || val === undefined || val === '') return '';
    return 'R$ ' + parseFloat(val).toFixed(2).replace('.', ',');
  }

  function escapeHtml(str) {
    if (!str) return '';
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
  }

  /**
   * Template de exibição do resultado no dropdown do Select2.
   */
  function templateResult(item) {
    if (item.loading) return item.text;
    if (!item.id) return item.text;

    var price = item.price ? ' — ' + formatPrice(item.price) : '';
    var sku = item.sku ? '<span class="badge bg-secondary ms-1" style="font-size:0.65rem;">' + escapeHtml(item.sku) + '</span>' : '';
    var combos = '';
    if (item.combinations && item.combinations.length > 0) {
      combos = ' <span class="badge bg-info ms-1" style="font-size:0.65rem;">' + item.combinations.length + ' variação(ões)</span>';
    }
    var desc = item.description ? '<div class="small text-muted text-truncate" style="max-width:350px;">' + escapeHtml(item.description) + '</div>' : '';

    return $('<div class="py-1">' +
      '<div class="fw-semibold">' + escapeHtml(item.text) + price + sku + combos + '</div>' +
      desc +
      '</div>');
  }

  /**
   * Template de exibição do item selecionado.
   */
  function templateSelection(item) {
    if (!item.id) return item.text;
    var price = item.price ? ' — ' + formatPrice(item.price) : '';
    return item.text + price;
  }

  // ── Inicialização Select2 ────────────────────────────────────

  function initProductSelect(el) {
    var $el = $(el);
    if ($el.data('s2-init')) return; // já inicializado

    $el.select2({
      theme: 'bootstrap-5',
      placeholder: $el.attr('data-placeholder') || 'Digite para buscar...',
      allowClear: true,
      width: '100%',
      minimumInputLength: 3,
      language: {
        inputTooShort: function () { return 'Digite ao menos 3 caractere para buscar...'; },
        noResults: function () { return 'Nenhum produto encontrado.'; },
        searching: function () { return 'Buscando...'; },
        errorLoading: function () { return 'Erro ao buscar produtos.'; },
      },
      ajax: {
        url: SEARCH_ENDPOINT,
        delay: 300,
        dataType: 'json',
        data: function (params) {
          return { q: params.term || '', limit: 10 };
        },
        processResults: function (resp) {
          var rows = (resp && resp.data) ? resp.data : [];
          return {
            results: rows.map(function (r) {
              return {
                id: r.id,
                text: r.name,
                price: (r.price !== undefined && r.price !== null) ? r.price : null,
                sku: r.sku || null,
                description: r.description || '',
                combinations: r.combinations || []
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

    // ── Evento: produto selecionado ──
    $el.on('select2:select', function (e) {
      var data = e.params.data || {};
      var $row = $el.closest('tr');

      // Preencher preço
      var priceInput = $row.find('.item-price');
      if (priceInput.length && data.price) {
        priceInput.val(parseFloat(data.price).toFixed(2));
      }

      // Preencher variações
      handleCombinations($row, data);

      // Disparar change para cálculos existentes
      $el.trigger('change');
    });

    $el.on('select2:clear', function () {
      var $row = $el.closest('tr');
      clearVariations($row);
      $el.trigger('change');
    });

    $el.data('s2-init', true);
  }

  /**
   * Popula o select de variações na linha do item.
   */
  function handleCombinations($row, data) {
    var varSelect = $row.find('.variation-select');
    var noVarText = $row.find('.no-variation-text');
    var gradeDesc = $row.find('.grade-desc-input');

    if (!varSelect.length) return;

    var combos = data.combinations || [];

    if (combos.length > 0) {
      varSelect.show().empty().append('<option value="">Variação...</option>');
      combos.forEach(function (c) {
        var lbl = c.combination_label || '';
        if (c.price_override) {
          lbl += ' — ' + formatPrice(c.price_override);
        }
        varSelect.append(
          $('<option>')
            .val(c.id)
            .attr('data-price', c.price_override || '')
            .attr('data-label', c.combination_label || '')
            .text(lbl)
        );
      });
      if (noVarText.length) noVarText.hide();
      if (gradeDesc.length) gradeDesc.val('');
    } else {
      clearVariations($row);
    }
  }

  function clearVariations($row) {
    var varSelect = $row.find('.variation-select');
    var noVarText = $row.find('.no-variation-text');
    var gradeDesc = $row.find('.grade-desc-input');
    if (varSelect.length) { varSelect.hide().empty(); }
    if (noVarText.length) { noVarText.show(); }
    if (gradeDesc.length) { gradeDesc.val(''); }
  }

  // ── Pipeline: inicialização específica ───────────────────────

  function initPipelineSelect() {
    var $pip = $('#pipProductSelect');
    if (!$pip.length) return;
    if ($pip.data('s2-init')) return;

    $pip.select2({
      theme: 'bootstrap-5',
      placeholder: 'Selecione um produto...',
      allowClear: true,
      width: '100%',
      minimumInputLength: 3,
      language: {
        inputTooShort: function () { return 'Digite ao menos 1 caractere...'; },
        noResults: function () { return 'Nenhum produto encontrado.'; },
        searching: function () { return 'Buscando...'; },
        errorLoading: function () { return 'Erro ao buscar.'; },
      },
      ajax: {
        url: SEARCH_ENDPOINT,
        delay: 300,
        dataType: 'json',
        data: function (params) {
          return { q: params.term || '', limit: 10 };
        },
        processResults: function (resp) {
          var rows = (resp && resp.data) ? resp.data : [];
          return {
            results: rows.map(function (r) {
              return {
                id: r.id,
                text: r.name,
                price: (r.price !== undefined && r.price !== null) ? r.price : null,
                sku: r.sku || null,
                description: r.description || '',
                combinations: r.combinations || []
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

    // Ao selecionar produto no pipeline → preencher preço e variações
    $pip.on('select2:select', function (e) {
      var data = e.params.data || {};

      // Preço
      if (data.price !== undefined && data.price !== null) {
        $('#pipPriceInput').val(parseFloat(data.price).toFixed(2));
      }

      // Variações
      var combos = data.combinations || [];
      if (combos.length > 0) {
        var $variation = $('#pipVariationSelect');
        $variation.empty().append('<option value="">Selecione a variação...</option>');
        combos.forEach(function (c) {
          var lbl = (c.combination_label || '') +
            (c.price_override ? ' — ' + formatPrice(c.price_override) : '');
          $variation.append(
            $('<option>')
              .val(c.id)
              .attr('data-price', c.price_override || '')
              .attr('data-label', c.combination_label || '')
              .text(lbl)
          );
        });
        $('#variationWrapPipeline').show();
      } else {
        $('#variationWrapPipeline').hide();
        $('#pipVariationSelect').empty();
      }

      // Fallback: se existir a variável global productCombinations (preço customizado por cliente)
      if (typeof productCombinations !== 'undefined' && productCombinations[data.id] && productCombinations[data.id].length > 0) {
        var $variation2 = $('#pipVariationSelect');
        $variation2.empty().append('<option value="">Selecione a variação...</option>');
        productCombinations[data.id].forEach(function (c) {
          var lbl2 = c.combination_label +
            (c.price_override ? ' — ' + formatPrice(c.price_override) : '');
          $variation2.append(
            $('<option>')
              .val(c.id)
              .attr('data-price', c.price_override || '')
              .attr('data-label', c.combination_label)
              .text(lbl2)
          );
        });
        $('#variationWrapPipeline').show();
      }
    });

    $pip.on('select2:clear', function () {
      $('#pipPriceInput').val('');
      $('#variationWrapPipeline').hide();
      $('#pipVariationSelect').empty();
    });

    $pip.data('s2-init', true);
  }

  // ── Inicialização principal ──────────────────────────────────

  $(function () {
    if (typeof $.fn.select2 !== 'function') return;

    // Pipeline (inicializar ANTES do seletor genérico para registrar handlers específicos)
    initPipelineSelect();

    // Inicializar selects de produto existentes na página (exceto o do pipeline, já inicializado acima)
    $('.product-select').not('#pipProductSelect').each(function () { initProductSelect(this); });

    // Observer: inicializar selects em novas linhas adicionadas dinamicamente
    var tbody = document.querySelector('#orderItemsTable tbody');
    if (tbody) {
      var observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (m) {
          if (!m.addedNodes) return;
          m.addedNodes.forEach(function (node) {
            if (node.nodeType !== 1) return;
            // Destruir Select2 clone (se existir) e re-inicializar
            $(node).find('.product-select').each(function () {
              var $s = $(this);
              if ($s.data('select2')) {
                $s.select2('destroy');
              }
              $s.removeData('s2-init');
              initProductSelect(this);
            });
          });
        });
      });
      observer.observe(tbody, { childList: true, subtree: true });
    }
  });

})();

