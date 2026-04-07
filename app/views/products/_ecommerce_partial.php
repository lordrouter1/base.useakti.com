<?php
/**
 * Partial: Campos de E-commerce / Marketplace
 * Usado em create.php e edit.php de products
 * 
 * Variáveis esperadas:
 *   $product (array|null) — dados do produto em edição (null no create)
 */
$p = $product ?? [];
?>

<!-- ── Descrição Detalhada (Rich Text Editor) ── -->
<div class="card bg-light border-0 mb-3">
    <div class="card-body p-3">
        <h6 class="fw-bold mb-2"><i class="fas fa-align-left me-2 text-primary"></i>Descrição Detalhada para E-commerce</h6>
        <p class="text-muted small mb-2">Descrição rica com formatação, usada em marketplaces e na loja online. Suporta negrito, itálico, listas, links e mais.</p>
        <div id="ecommerceEditorToolbar">
            <button type="button" class="btn btn-sm btn-outline-secondary me-1 mb-1" data-cmd="bold" title="Negrito"><i class="fas fa-bold"></i></button>
            <button type="button" class="btn btn-sm btn-outline-secondary me-1 mb-1" data-cmd="italic" title="Itálico"><i class="fas fa-italic"></i></button>
            <button type="button" class="btn btn-sm btn-outline-secondary me-1 mb-1" data-cmd="underline" title="Sublinhado"><i class="fas fa-underline"></i></button>
            <button type="button" class="btn btn-sm btn-outline-secondary me-1 mb-1" data-cmd="strikeThrough" title="Tachado"><i class="fas fa-strikethrough"></i></button>
            <span class="border-start mx-1"></span>
            <button type="button" class="btn btn-sm btn-outline-secondary me-1 mb-1" data-cmd="insertUnorderedList" title="Lista"><i class="fas fa-list-ul"></i></button>
            <button type="button" class="btn btn-sm btn-outline-secondary me-1 mb-1" data-cmd="insertOrderedList" title="Lista Numerada"><i class="fas fa-list-ol"></i></button>
            <span class="border-start mx-1"></span>
            <button type="button" class="btn btn-sm btn-outline-secondary me-1 mb-1" data-cmd="justifyLeft" title="Alinhar Esquerda"><i class="fas fa-align-left"></i></button>
            <button type="button" class="btn btn-sm btn-outline-secondary me-1 mb-1" data-cmd="justifyCenter" title="Centralizar"><i class="fas fa-align-center"></i></button>
            <button type="button" class="btn btn-sm btn-outline-secondary me-1 mb-1" data-cmd="justifyRight" title="Alinhar Direita"><i class="fas fa-align-right"></i></button>
            <span class="border-start mx-1"></span>
            <select class="form-select form-select-sm d-inline-block mb-1" id="ecommerceEditorHeading" style="width: auto; font-size: 0.8rem;" title="Título">
                <option value="">Normal</option>
                <option value="h2">Título</option>
                <option value="h3">Subtítulo</option>
                <option value="h4">Subtítulo Menor</option>
            </select>
            <button type="button" class="btn btn-sm btn-outline-secondary me-1 mb-1" id="btnEditorLink" title="Inserir Link"><i class="fas fa-link"></i></button>
            <button type="button" class="btn btn-sm btn-outline-secondary me-1 mb-1" data-cmd="removeFormat" title="Limpar Formatação"><i class="fas fa-eraser"></i></button>
        </div>
        <div id="ecommerceEditor" contenteditable="true" 
             class="form-control mt-1" 
             style="min-height: 200px; max-height: 500px; overflow-y: auto; font-size: 0.9rem; line-height: 1.6;"
             ><?= $p['ecommerce_description'] ?? '' ?></div>
        <input type="hidden" name="ecommerce_description" id="ecommerce_description" value="<?= eAttr($p['ecommerce_description'] ?? '') ?>">
    </div>
</div>

<!-- ── Identificação e Marca ── -->
<div class="card bg-light border-0 mb-3">
    <div class="card-body p-3">
        <h6 class="fw-bold mb-3"><i class="fas fa-tag me-2 text-success"></i>Identificação do Produto</h6>
        <div class="row g-3">
            <div class="col-md-4">
                <label for="ecommerce_brand" class="form-label small fw-bold">Marca</label>
                <input type="text" class="form-control" id="ecommerce_brand" name="ecommerce_brand" 
                       placeholder="Ex: Samsung, Nike, Apple..." value="<?= eAttr($p['ecommerce_brand'] ?? '') ?>">
                <div class="form-text" style="font-size:0.7rem;">Obrigatório na maioria dos marketplaces.</div>
            </div>
            <div class="col-md-4">
                <label for="ecommerce_gtin" class="form-label small fw-bold">EAN / GTIN (Código de Barras)</label>
                <input type="text" class="form-control" id="ecommerce_gtin" name="ecommerce_gtin" 
                       placeholder="7891234567890" maxlength="14" value="<?= eAttr($p['ecommerce_gtin'] ?? '') ?>">
                <div class="form-text" style="font-size:0.7rem;">Código de barras universal do produto (8, 12, 13 ou 14 dígitos).</div>
            </div>
            <div class="col-md-4">
                <label for="ecommerce_condition" class="form-label small fw-bold">Condição</label>
                <select class="form-select" id="ecommerce_condition" name="ecommerce_condition">
                    <option value="new" <?= ($p['ecommerce_condition'] ?? 'new') === 'new' ? 'selected' : '' ?>>Novo</option>
                    <option value="used" <?= ($p['ecommerce_condition'] ?? '') === 'used' ? 'selected' : '' ?>>Usado</option>
                    <option value="refurbished" <?= ($p['ecommerce_condition'] ?? '') === 'refurbished' ? 'selected' : '' ?>>Recondicionado</option>
                </select>
            </div>
        </div>
    </div>
</div>

<!-- ── Dimensões e Peso (Frete) ── -->
<div class="card bg-light border-0 mb-3">
    <div class="card-body p-3">
        <h6 class="fw-bold mb-2"><i class="fas fa-box me-2 text-warning"></i>Dimensões e Peso <small class="text-muted fw-normal">(para cálculo de frete)</small></h6>
        <p class="text-muted small mb-3">Informações do produto <strong>embalado</strong>, necessárias para cálculo de frete nos marketplaces.</p>

        <!-- Frete Grátis -->
        <div class="mb-3">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="free_shipping" name="free_shipping" value="1" <?= !empty($p['free_shipping']) ? 'checked' : '' ?>>
                <label class="form-check-label small fw-bold" for="free_shipping">
                    <i class="fas fa-truck me-1 text-success"></i>Frete Grátis
                </label>
            </div>
            <small class="text-muted" style="font-size:0.7rem;">Marque para exibir selo de frete grátis na loja. Também pode ser definido na categoria ou subcategoria.</small>
        </div>

        <div class="row g-3">
            <div class="col-md-3">
                <label for="ecommerce_weight" class="form-label small fw-bold">Peso (kg)</label>
                <div class="input-group input-group-sm">
                    <input type="number" step="0.001" min="0" class="form-control" id="ecommerce_weight" name="ecommerce_weight" 
                           placeholder="0.000" value="<?= eAttr($p['ecommerce_weight'] ?? '') ?>">
                    <span class="input-group-text">kg</span>
                </div>
            </div>
            <div class="col-md-3">
                <label for="ecommerce_height" class="form-label small fw-bold">Altura (cm)</label>
                <div class="input-group input-group-sm">
                    <input type="number" step="0.01" min="0" class="form-control" id="ecommerce_height" name="ecommerce_height" 
                           placeholder="0.00" value="<?= eAttr($p['ecommerce_height'] ?? '') ?>">
                    <span class="input-group-text">cm</span>
                </div>
            </div>
            <div class="col-md-3">
                <label for="ecommerce_width" class="form-label small fw-bold">Largura (cm)</label>
                <div class="input-group input-group-sm">
                    <input type="number" step="0.01" min="0" class="form-control" id="ecommerce_width" name="ecommerce_width" 
                           placeholder="0.00" value="<?= eAttr($p['ecommerce_width'] ?? '') ?>">
                    <span class="input-group-text">cm</span>
                </div>
            </div>
            <div class="col-md-3">
                <label for="ecommerce_length" class="form-label small fw-bold">Comprimento (cm)</label>
                <div class="input-group input-group-sm">
                    <input type="number" step="0.01" min="0" class="form-control" id="ecommerce_length" name="ecommerce_length" 
                           placeholder="0.00" value="<?= eAttr($p['ecommerce_length'] ?? '') ?>">
                    <span class="input-group-text">cm</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── Informações Extras ── -->
<div class="card bg-light border-0 mb-3">
    <div class="card-body p-3">
        <h6 class="fw-bold mb-3"><i class="fas fa-info-circle me-2 text-info"></i>Informações Complementares</h6>
        <div class="row g-3">
            <div class="col-md-6">
                <label for="ecommerce_warranty" class="form-label small fw-bold">Garantia</label>
                <input type="text" class="form-control" id="ecommerce_warranty" name="ecommerce_warranty" 
                       placeholder="Ex: 12 meses pelo fabricante" value="<?= eAttr($p['ecommerce_warranty'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label for="ecommerce_video_url" class="form-label small fw-bold">URL do Vídeo</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fab fa-youtube text-danger"></i></span>
                    <input type="url" class="form-control" id="ecommerce_video_url" name="ecommerce_video_url" 
                           placeholder="https://youtube.com/watch?v=..." value="<?= eAttr($p['ecommerce_video_url'] ?? '') ?>">
                </div>
                <div class="form-text" style="font-size:0.7rem;">Vídeo demonstrativo do produto (YouTube, Vimeo, etc.).</div>
            </div>
            <div class="col-12">
                <label for="ecommerce_keywords" class="form-label small fw-bold">Palavras-chave / Tags</label>
                <textarea class="form-control" id="ecommerce_keywords" name="ecommerce_keywords" rows="2" 
                          placeholder="Ex: camiseta, algodão, básica, masculina, casual (separadas por vírgula)"><?= e($p['ecommerce_keywords'] ?? '') ?></textarea>
                <div class="form-text" style="font-size:0.7rem;">Palavras-chave para SEO e busca em marketplaces. Separe por vírgula.</div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const editor = document.getElementById('ecommerceEditor');
    const hiddenInput = document.getElementById('ecommerce_description');
    if (!editor || !hiddenInput) return;

    // Sync editor → hidden input on form submit
    const form = editor.closest('form');
    if (form) {
        form.addEventListener('submit', function() {
            hiddenInput.value = editor.innerHTML;
        });
    }

    // Toolbar buttons (execCommand)
    document.querySelectorAll('#ecommerceEditorToolbar button[data-cmd]').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const cmd = this.getAttribute('data-cmd');
            document.execCommand(cmd, false, null);
            editor.focus();
        });
    });

    // Heading selector
    const headingSelect = document.getElementById('ecommerceEditorHeading');
    if (headingSelect) {
        headingSelect.addEventListener('change', function() {
            if (this.value) {
                document.execCommand('formatBlock', false, this.value);
            } else {
                document.execCommand('formatBlock', false, 'p');
            }
            editor.focus();
            this.value = '';
        });
    }

    // Link button
    const btnLink = document.getElementById('btnEditorLink');
    if (btnLink) {
        btnLink.addEventListener('click', function(e) {
            e.preventDefault();
            const url = prompt('Digite a URL do link:', 'https://');
            if (url) {
                document.execCommand('createLink', false, url);
            }
            editor.focus();
        });
    }

    // Paste as plain text (optional: keeps formatting clean)
    editor.addEventListener('paste', function(e) {
        // Allow HTML paste but sanitize dangerous tags
        const clipboardData = e.clipboardData || window.clipboardData;
        const html = clipboardData.getData('text/html');
        if (html) {
            e.preventDefault();
            // Strip script/style tags
            const clean = html.replace(/<script[\s\S]*?<\/script>/gi, '')
                              .replace(/<style[\s\S]*?<\/style>/gi, '')
                              .replace(/on\w+="[^"]*"/gi, '');
            document.execCommand('insertHTML', false, clean);
        }
    });
})();
</script>
