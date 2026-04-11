<?php
/**
 * Checkout — Tab Cartão de Crédito.
 * Variáveis esperadas: $token (array), $gatewaySlug (string), $publicKey (string)
 */
$amount = (float) ($token['amount'] ?? 0);
?>
<div class="co-method-pane" id="card-tab-pane" style="<?= empty($__tabActive) ? 'display:none;' : '' ?>">
    <form id="cardPaymentForm" onsubmit="return AktiCheckout.processCardPayment(event)">
        <div class="mb-3">
            <label for="cardNumber" class="form-label" id="cardNumberLabel">Número do cartão</label>
            <input type="text" class="form-control" id="cardNumber" required
                   placeholder="0000 0000 0000 0000" maxlength="19" autocomplete="cc-number"
                   inputmode="numeric">
            <!-- Stripe CardNumber Element mounts here (replaces manual input) -->
            <div id="stripe-card-number" class="checkout-card-element" style="display:none;"></div>
            <div id="card-errors" class="text-danger small mt-1" role="alert"></div>
        </div>

        <div class="mb-3">
            <label for="cardHolderName" class="form-label">Nome no cartão</label>
            <input type="text" class="form-control" id="cardHolderName" required
                   placeholder="Como está impresso no cartão" autocomplete="cc-name">
        </div>

        <div class="row mb-3">
            <div class="col-6">
                <label for="cardExpiry" class="form-label">Validade</label>
                <input type="text" class="form-control" id="cardExpiry" required
                       placeholder="MM/AA" maxlength="5" autocomplete="cc-exp"
                       inputmode="numeric">
                <!-- Stripe CardExpiry Element mounts here -->
                <div id="stripe-card-expiry" class="checkout-card-element" style="display:none;"></div>
            </div>
            <div class="col-6">
                <label for="cardCvv" class="form-label">CVV</label>
                <input type="text" class="form-control" id="cardCvv" required
                       placeholder="000" maxlength="4" autocomplete="cc-csc"
                       inputmode="numeric">
                <!-- Stripe CardCvc Element mounts here -->
                <div id="stripe-card-cvc" class="checkout-card-element" style="display:none;"></div>
            </div>
        </div>

        <div class="mb-3">
            <label for="cardDocument" class="form-label">CPF/CNPJ do titular</label>
            <input type="text" class="form-control" id="cardDocument" required
                   placeholder="000.000.000-00" maxlength="18"
                   oninput="AktiCheckout.maskCpfCnpj(this)">
        </div>

        <button type="submit" class="co-btn-pay" id="btnPayCard">
            <i class="fas fa-lock"></i> Pagar R$ <?= eNum($amount) ?>
        </button>
    </form>
</div>
