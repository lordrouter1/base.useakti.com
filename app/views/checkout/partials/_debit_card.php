<?php
/**
 * Checkout — Tab Cartão de Débito.
 * Mesmo fluxo do crédito, mas sem parcelas (sempre à vista).
 * Variáveis esperadas: $token (array), $gatewaySlug (string), $publicKey (string)
 */
$amount = (float) ($token['amount'] ?? 0);
?>
<div class="co-method-pane" id="debit-tab-pane" style="<?= empty($__tabActive) ? 'display:none;' : '' ?>">
    <form id="debitPaymentForm" onsubmit="return AktiCheckout.processDebitCardPayment(event)">
        <div class="mb-3">
            <label for="debitCardNumber" class="form-label">Número do cartão</label>
            <input type="text" class="form-control" id="debitCardNumber" required
                   placeholder="0000 0000 0000 0000" maxlength="19" autocomplete="cc-number"
                   inputmode="numeric">
            <!-- Stripe CardNumber Element mounts here (replaces manual input) -->
            <div id="stripe-debit-card-number" class="checkout-card-element" style="display:none;"></div>
            <div id="debit-card-errors" class="text-danger small mt-1" role="alert"></div>
        </div>

        <div class="mb-3">
            <label for="debitHolderName" class="form-label">Nome no cartão</label>
            <input type="text" class="form-control" id="debitHolderName" required
                   placeholder="Como está impresso no cartão" autocomplete="cc-name">
        </div>

        <div class="row mb-3">
            <div class="col-6">
                <label for="debitExpiry" class="form-label">Validade</label>
                <input type="text" class="form-control" id="debitExpiry" required
                       placeholder="MM/AA" maxlength="5" autocomplete="cc-exp"
                       inputmode="numeric">
                <!-- Stripe CardExpiry Element mounts here -->
                <div id="stripe-debit-card-expiry" class="checkout-card-element" style="display:none;"></div>
            </div>
            <div class="col-6">
                <label for="debitCvv" class="form-label">CVV</label>
                <input type="text" class="form-control" id="debitCvv" required
                       placeholder="000" maxlength="4" autocomplete="cc-csc"
                       inputmode="numeric">
                <!-- Stripe CardCvc Element mounts here -->
                <div id="stripe-debit-card-cvc" class="checkout-card-element" style="display:none;"></div>
            </div>
        </div>

        <div class="mb-3">
            <label for="debitDocument" class="form-label">CPF/CNPJ do titular</label>
            <input type="text" class="form-control" id="debitDocument" required
                   placeholder="000.000.000-00" maxlength="18"
                   oninput="AktiCheckout.maskCpfCnpj(this)">
        </div>

        <button type="submit" class="co-btn-pay" id="btnPayDebit">
            <i class="fas fa-lock"></i> Pagar R$ <?= eNum($amount) ?> no débito
        </button>
    </form>
</div>
