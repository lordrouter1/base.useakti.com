<?php
/**
 * Checkout — Formulário de dados do cliente (exibido antes dos meios de pagamento).
 *
 * Variáveis esperadas:
 *   $missingFields  (array) Campos faltantes: ['name' => 'Nome completo', ...]
 *   $customerData   (array) Dados atuais do cliente
 *   $token          (array) Dados do checkout_token
 */

$fieldLabels = [
    'name'         => 'Nome completo',
    'email'        => 'E-mail',
    'document'     => 'CPF/CNPJ',
    'phone'        => 'Telefone/Celular',
    'zip'          => 'CEP',
    'street'       => 'Rua/Logradouro',
    'number'       => 'Número',
    'neighborhood' => 'Bairro',
    'city'         => 'Cidade',
    'state'        => 'Estado',
];

$fieldPlaceholders = [
    'name'         => 'Nome completo do pagador',
    'email'        => 'email@exemplo.com',
    'document'     => '000.000.000-00',
    'phone'        => '(00) 00000-0000',
    'zip'          => '00000-000',
    'street'       => 'Rua, Avenida...',
    'number'       => 'Nº',
    'neighborhood' => 'Bairro',
    'city'         => 'Cidade',
    'state'        => 'UF',
];

$fieldTypes = [
    'email' => 'email',
];

$fieldMaxlengths = [
    'document' => 18,
    'phone'    => 15,
    'zip'      => 9,
    'state'    => 2,
    'number'   => 10,
];

// Campos de endereço agrupados
$addressFields = ['zip', 'street', 'number', 'neighborhood', 'city', 'state'];
$hasAddressFields = !empty(array_intersect(array_keys($missingFields), $addressFields));
?>

<div class="co-card mb-4" id="customerDataCard">
    <div class="co-card-header">
        <i class="fas fa-user-edit"></i>
        <h2>Complete seus dados</h2>
    </div>
    <div class="co-card-body">
        <p class="text-muted small mb-3">
            <i class="fas fa-info-circle me-1"></i>
            Precisamos de algumas informações para processar seu pagamento.
        </p>

        <form id="customerDataForm" novalidate>
            <?php foreach ($missingFields as $field => $label):
                if (in_array($field, $addressFields, true)) continue; // Endereço renderizado separado
                $type = $fieldTypes[$field] ?? 'text';
                $placeholder = $fieldPlaceholders[$field] ?? '';
                $maxlength = $fieldMaxlengths[$field] ?? 255;
                $currentVal = $customerData[$field] ?? '';
            ?>
            <div class="mb-3">
                <label for="custData_<?= e($field) ?>" class="form-label"><?= e($label) ?> <span class="text-danger">*</span></label>
                <input type="<?= e($type) ?>"
                       class="form-control"
                       id="custData_<?= e($field) ?>"
                       name="<?= e($field) ?>"
                       value="<?= eAttr($currentVal) ?>"
                       placeholder="<?= eAttr($placeholder) ?>"
                       maxlength="<?= (int) $maxlength ?>"
                       required
                       <?php if ($field === 'document'): ?>
                       oninput="maskCustDocument(this)"
                       inputmode="numeric"
                       <?php elseif ($field === 'phone'): ?>
                       oninput="maskCustPhone(this)"
                       inputmode="tel"
                       <?php endif; ?>>
            </div>
            <?php endforeach; ?>

            <?php if ($hasAddressFields): ?>
            <hr class="my-3">
            <p class="text-muted small mb-2"><i class="fas fa-map-marker-alt me-1"></i> Endereço de cobrança</p>

            <?php if (isset($missingFields['zip'])): ?>
            <div class="mb-3">
                <label for="custData_zip" class="form-label">CEP <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="custData_zip" name="zip"
                       value="<?= eAttr($customerData['zip'] ?? '') ?>"
                       placeholder="00000-000" maxlength="9" required
                       oninput="maskCustZip(this)" inputmode="numeric">
            </div>
            <?php endif; ?>

            <div class="row">
                <?php if (isset($missingFields['street'])): ?>
                <div class="col-8 mb-3">
                    <label for="custData_street" class="form-label">Rua <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="custData_street" name="street"
                           value="<?= eAttr($customerData['street'] ?? '') ?>"
                           placeholder="Rua, Avenida..." required>
                </div>
                <?php endif; ?>

                <?php if (isset($missingFields['number'])): ?>
                <div class="col-4 mb-3">
                    <label for="custData_number" class="form-label">Nº <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="custData_number" name="number"
                           value="<?= eAttr($customerData['number'] ?? '') ?>"
                           placeholder="Nº" maxlength="10" required>
                </div>
                <?php endif; ?>
            </div>

            <div class="row">
                <?php if (isset($missingFields['neighborhood'])): ?>
                <div class="col-12 mb-3">
                    <label for="custData_neighborhood" class="form-label">Bairro <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="custData_neighborhood" name="neighborhood"
                           value="<?= eAttr($customerData['neighborhood'] ?? '') ?>"
                           placeholder="Bairro" required>
                </div>
                <?php endif; ?>
            </div>

            <div class="row">
                <?php if (isset($missingFields['city'])): ?>
                <div class="col-8 mb-3">
                    <label for="custData_city" class="form-label">Cidade <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="custData_city" name="city"
                           value="<?= eAttr($customerData['city'] ?? '') ?>"
                           placeholder="Cidade" required>
                </div>
                <?php endif; ?>

                <?php if (isset($missingFields['state'])): ?>
                <div class="col-4 mb-3">
                    <label for="custData_state" class="form-label">UF <span class="text-danger">*</span></label>
                    <input type="text" class="form-control text-uppercase" id="custData_state" name="state"
                           value="<?= eAttr($customerData['state'] ?? '') ?>"
                           placeholder="UF" maxlength="2" required>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <button type="submit" class="co-btn-pay" id="btnSaveCustomerData">
                <i class="fas fa-arrow-right"></i> Continuar para pagamento
            </button>
        </form>
    </div>
</div>

<script>
(function() {
    function maskCustDocument(el) {
        var v = el.value.replace(/\D/g, '');
        if (v.length <= 11) {
            v = v.replace(/(\d{3})(\d)/, '$1.$2');
            v = v.replace(/(\d{3})(\d)/, '$1.$2');
            v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        } else {
            v = v.substring(0, 14);
            v = v.replace(/^(\d{2})(\d)/, '$1.$2');
            v = v.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
            v = v.replace(/\.(\d{3})(\d)/, '.$1/$2');
            v = v.replace(/(\d{4})(\d)/, '$1-$2');
        }
        el.value = v;
    }

    function maskCustPhone(el) {
        var v = el.value.replace(/\D/g, '');
        if (v.length <= 10) {
            v = v.replace(/^(\d{2})(\d)/g, '($1) $2');
            v = v.replace(/(\d{4})(\d)/, '$1-$2');
        } else {
            v = v.substring(0, 11);
            v = v.replace(/^(\d{2})(\d)/g, '($1) $2');
            v = v.replace(/(\d{5})(\d)/, '$1-$2');
        }
        el.value = v;
    }

    function maskCustZip(el) {
        var v = el.value.replace(/\D/g, '').substring(0, 8);
        if (v.length > 5) {
            v = v.replace(/^(\d{5})(\d)/, '$1-$2');
        }
        el.value = v;
    }

    // Expose masks to inline handlers
    window.maskCustDocument = maskCustDocument;
    window.maskCustPhone = maskCustPhone;
    window.maskCustZip = maskCustZip;

    // CEP auto-fill via ViaCEP
    var zipInput = document.getElementById('custData_zip');
    if (zipInput) {
        zipInput.addEventListener('blur', function() {
            var cep = this.value.replace(/\D/g, '');
            if (cep.length !== 8) return;

            fetch('https://viacep.com.br/ws/' + cep + '/json/')
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.erro) return;
                    var street = document.getElementById('custData_street');
                    var neighborhood = document.getElementById('custData_neighborhood');
                    var city = document.getElementById('custData_city');
                    var state = document.getElementById('custData_state');
                    if (street && !street.value && data.logradouro) street.value = data.logradouro;
                    if (neighborhood && !neighborhood.value && data.bairro) neighborhood.value = data.bairro;
                    if (city && !city.value && data.localidade) city.value = data.localidade;
                    if (state && !state.value && data.uf) state.value = data.uf;
                })
                .catch(function() {});
        });
    }

    // Form submission
    var form = document.getElementById('customerDataForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            // HTML5 validation
            if (!form.checkValidity()) {
                form.classList.add('was-validated');
                return;
            }

            var btn = document.getElementById('btnSaveCustomerData');
            if (btn) btn.disabled = true;

            // Collect form data
            var formData = { token: CHECKOUT_CONFIG.token };
            var inputs = form.querySelectorAll('input[name]');
            for (var i = 0; i < inputs.length; i++) {
                formData[inputs[i].name] = inputs[i].value;
            }

            Swal.fire({
                title: 'Salvando dados...',
                allowOutsideClick: false,
                didOpen: function() { Swal.showLoading(); }
            });

            fetch('/?page=checkout&action=updateCustomerData', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(formData)
            })
            .then(function(resp) { return resp.json(); })
            .then(function(data) {
                Swal.close();
                if (data.success) {
                    // Hide customer form, show payment methods
                    var card = document.getElementById('customerDataCard');
                    var paySection = document.getElementById('paymentSection');
                    if (card) card.style.display = 'none';
                    if (paySection) paySection.style.display = 'block';

                    Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2000, timerProgressBar: true })
                        .fire({ icon: 'success', title: 'Dados salvos!' });
                } else {
                    Swal.fire({ icon: 'error', title: 'Erro', text: data.error || 'Erro ao salvar dados.' });
                    if (btn) btn.disabled = false;
                }
            })
            .catch(function() {
                Swal.close();
                Swal.fire({ icon: 'error', title: 'Erro', text: 'Erro de conexão.' });
                if (btn) btn.disabled = false;
            });
        });
    }
})();
</script>
