<?php
/**
 * Checkout — Tab Boleto.
 * Variáveis esperadas: $token (array)
 */
$customerDocument = $token['customer_document'] ?? '';
?>
<div class="co-method-pane" id="boleto-tab-pane" style="<?= empty($__tabActive) ? 'display:none;' : '' ?>">
    <!-- Estado inicial: gerar boleto -->
    <div id="boletoGenerate">
        <div class="mb-3">
            <label for="boletoDocument" class="form-label">CPF/CNPJ</label>
            <input type="text" class="form-control" id="boletoDocument"
                   value="<?= eAttr($customerDocument) ?>"
                   placeholder="000.000.000-00" maxlength="18" required
                   oninput="AktiCheckout.maskCpfCnpj(this)">
        </div>
        <button type="button" class="co-btn-pay" id="btnGenerateBoleto" onclick="AktiCheckout.processBoletoPayment()">
            <i class="fas fa-barcode"></i> Gerar Boleto
        </button>
    </div>

    <!-- Estado: boleto gerado (inicialmente oculto) -->
    <div id="boletoResult" style="display:none;">
        <div class="checkout-alert success mb-3">
            <i class="fas fa-check-circle"></i>
            <span>Boleto gerado com sucesso!</span>
        </div>

        <p class="fw-semibold mb-2" style="font-size:0.82rem;">Código de barras</p>
        <div class="checkout-copy-field mb-3">
            <input type="text" id="boletoBarcode" class="form-control" readonly>
            <button type="button" class="btn btn-outline-secondary" onclick="AktiCheckout.copyToClipboard('boletoBarcode')" title="Copiar">
                <i class="fas fa-copy"></i>
            </button>
        </div>

        <div id="boletoPdfLink" class="mb-3" style="display:none;">
            <a id="boletoPdfUrl" href="#" target="_blank" class="checkout-boleto-pdf-link">
                <i class="fas fa-file-pdf"></i> Abrir PDF do Boleto
            </a>
        </div>

        <div id="boletoDueDate" class="mb-2" style="display:none;font-size:0.8rem;color:var(--co-text-secondary);">
            <i class="fas fa-calendar me-1"></i> Vencimento: <span id="boletoDueDateValue"></span>
        </div>

        <div class="checkout-alert warning mt-3">
            <i class="fas fa-info-circle"></i>
            <span>O pagamento pode levar até 3 dias úteis para compensação.</span>
        </div>
    </div>
</div>
