<?php
/**
 * Checkout — Tab PIX.
 * Variáveis esperadas: $token (array)
 */
?>
<div class="co-method-pane" id="pix-tab-pane" style="<?= empty($__tabActive) ? 'display:none;' : '' ?>">
    <!-- Estado inicial: botão gerar -->
    <div id="pixGenerate" class="co-pix-intro">
        <p>Pague instantaneamente via PIX. O QR Code será gerado automaticamente.</p>
        <button type="button" class="co-btn-pay" id="btnGeneratePix" onclick="AktiCheckout.processPixPayment()">
            <i class="fas fa-qrcode"></i> Gerar QR Code PIX
        </button>
    </div>

    <!-- Estado: QR gerado (inicialmente oculto) -->
    <div id="pixResult" class="text-center py-3" style="display:none;">
        <div class="checkout-pix-qr mb-3">
            <img id="pixQrImage" src="" alt="QR Code PIX" class="img-fluid">
        </div>

        <p class="fw-semibold mb-2" style="font-size:0.82rem;">PIX Copia e Cola</p>
        <div class="checkout-copy-field mb-3">
            <input type="text" id="pixCopyPaste" class="form-control" readonly>
            <button type="button" class="btn btn-outline-secondary" onclick="AktiCheckout.copyToClipboard('pixCopyPaste')" title="Copiar">
                <i class="fas fa-copy"></i>
            </button>
        </div>

        <div class="checkout-countdown mb-3" id="pixCountdown">
            <i class="fas fa-clock"></i> Expira em <span id="pixTimer">--:--</span>
        </div>

        <div class="checkout-polling-status" id="pixPollingStatus">
            <div class="spinner-border spinner-border-sm" role="status">
                <span class="visually-hidden">Verificando...</span>
            </div>
            Aguardando confirmação...
        </div>
    </div>
</div>
