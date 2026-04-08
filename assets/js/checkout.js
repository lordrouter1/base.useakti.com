/**
 * Checkout Transparente Akti
 *
 * Módulo JS responsável pela interação do checkout público.
 * Carrega dinamicamente o SDK do gateway e processa pagamentos.
 */
const AktiCheckout = (function () {
    'use strict';

    let config = {};
    let gatewayInstance = null;
    let stripeElements = null;
    let pollingInterval = null;
    let countdownInterval = null;
    let processing = false;

    // SDK URLs por gateway
    const SDK_URLS = {
        stripe: 'https://js.stripe.com/v3/',
        mercadopago: 'https://sdk.mercadopago.com/js/v2',
        pagseguro: 'https://assets.pagseguro.com.br/checkout-sdk-js/rc/dist/browser/pagseguro.min.js'
    };

    /* =========================================
       Public API
       ========================================= */

    function init(cfg) {
        config = cfg;
        loadGatewaySDK(config.gatewaySlug).then(function () {
            initGateway();
        }).catch(function (err) {
            console.warn('SDK do gateway não carregado:', err);
        });
    }

    function processPixPayment() {
        if (processing) return;
        processing = true;

        const btn = document.getElementById('btnGeneratePix');
        if (btn) btn.disabled = true;

        showLoading('Gerando código PIX...');

        postPayment({ method: 'pix' })
            .then(function (data) {
                Swal.close();
                processing = false;

                if (!data.success) {
                    showError(data.error || 'Erro ao gerar PIX.');
                    if (btn) btn.disabled = false;
                    return;
                }

                // Mostrar resultado
                const generateDiv = document.getElementById('pixGenerate');
                const resultDiv = document.getElementById('pixResult');
                if (generateDiv) generateDiv.style.display = 'none';
                if (resultDiv) resultDiv.style.display = 'block';

                // QR Code
                const qrImg = document.getElementById('pixQrImage');
                if (qrImg && data.qr_code_base64) {
                    qrImg.src = 'data:image/png;base64,' + data.qr_code_base64;
                } else if (qrImg && data.qr_code_url) {
                    qrImg.src = data.qr_code_url;
                }

                // Copia e cola
                const copyField = document.getElementById('pixCopyPaste');
                if (copyField && data.qr_code) {
                    copyField.value = data.qr_code;
                }

                // Countdown
                if (data.expires_in_seconds) {
                    startCountdown(data.expires_in_seconds);
                }

                // Iniciar polling
                startPolling(data.external_id || '');
            })
            .catch(function (err) {
                Swal.close();
                processing = false;
                if (btn) btn.disabled = false;
                showError('Erro de conexão. Tente novamente.');
            });
    }

    function processCardPayment(event) {
        if (event) event.preventDefault();
        if (processing) return false;
        processing = true;

        const btn = document.getElementById('btnPayCard');
        if (btn) btn.disabled = true;

        const holderName = document.getElementById('cardHolderName');
        const documentField = document.getElementById('cardDocument');

        // Tokenizar conforme gateway
        tokenizeCard()
            .then(function (cardToken) {
                showLoading('Processando pagamento...');

                return postPayment({
                    method: 'credit_card',
                    card_token: cardToken,
                    customer_name: holderName ? holderName.value : '',
                    customer_document: documentField ? documentField.value.replace(/\D/g, '') : ''
                });
            })
            .then(function (data) {
                Swal.close();
                processing = false;

                if (!data.success) {
                    showError(data.error || 'Pagamento recusado.');
                    if (btn) btn.disabled = false;
                    return;
                }

                if (data.status === 'succeeded' || data.status === 'approved') {
                    showSuccess('Pagamento aprovado!');
                    setTimeout(function () {
                        window.location.href = config.confirmationUrl + '&status=succeeded&external_id=' + encodeURIComponent(data.external_id || '');
                    }, 2000);
                } else if (data.status === 'requires_action' && data.client_secret && config.gatewaySlug === 'stripe') {
                    // 3D Secure
                    handle3DSecure(data.client_secret);
                } else {
                    // Pendente — redirect para confirmação
                    window.location.href = config.confirmationUrl + '&status=pending&external_id=' + encodeURIComponent(data.external_id || '');
                }
            })
            .catch(function (err) {
                Swal.close();
                processing = false;
                if (btn) btn.disabled = false;
                var msg = 'Erro ao processar cartão.';
                if (err && err.message) msg = err.message;
                else if (typeof err === 'string') msg = err;
                showError(msg);
            });

        return false;
    }

    function processBoletoPayment() {
        if (processing) return;
        processing = true;

        const btn = document.getElementById('btnGenerateBoleto');
        if (btn) btn.disabled = true;

        const documentField = document.getElementById('boletoDocument');
        const document_value = documentField ? documentField.value.replace(/\D/g, '') : '';

        if (!document_value || (document_value.length !== 11 && document_value.length !== 14)) {
            showError('Informe um CPF ou CNPJ válido.');
            processing = false;
            if (btn) btn.disabled = false;
            return;
        }

        showLoading('Gerando boleto...');

        postPayment({ method: 'boleto', customer_document: document_value })
            .then(function (data) {
                Swal.close();
                processing = false;

                if (!data.success) {
                    showError(data.error || 'Erro ao gerar boleto.');
                    if (btn) btn.disabled = false;
                    return;
                }

                const generateDiv = document.getElementById('boletoGenerate');
                const resultDiv = document.getElementById('boletoResult');
                if (generateDiv) generateDiv.style.display = 'none';
                if (resultDiv) resultDiv.style.display = 'block';

                // Barcode
                const barcodeField = document.getElementById('boletoBarcode');
                if (barcodeField && (data.boleto_barcode || data.barcode)) {
                    barcodeField.value = data.boleto_barcode || data.barcode;
                }

                // PDF link
                if (data.boleto_url) {
                    const pdfLink = document.getElementById('boletoPdfLink');
                    const pdfUrl = document.getElementById('boletoPdfUrl');
                    if (pdfLink) pdfLink.style.display = 'block';
                    if (pdfUrl) pdfUrl.href = data.boleto_url;
                }

                // Due date
                if (data.due_date) {
                    const dueDateDiv = document.getElementById('boletoDueDate');
                    const dueDateVal = document.getElementById('boletoDueDateValue');
                    if (dueDateDiv) dueDateDiv.style.display = 'block';
                    if (dueDateVal) dueDateVal.textContent = data.due_date;
                }
            })
            .catch(function (err) {
                Swal.close();
                processing = false;
                if (btn) btn.disabled = false;
                showError('Erro de conexão. Tente novamente.');
            });
    }

    function copyToClipboard(inputId) {
        const input = document.getElementById(inputId);
        if (!input) return;

        const text = input.value;

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function () {
                showToast('Copiado!');
            }).catch(function () {
                fallbackCopy(input);
            });
        } else {
            fallbackCopy(input);
        }
    }

    function maskCpfCnpj(el) {
        let value = el.value.replace(/\D/g, '');

        if (value.length <= 11) {
            // CPF: 000.000.000-00
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        } else {
            // CNPJ: 00.000.000/0000-00
            value = value.substring(0, 14);
            value = value.replace(/^(\d{2})(\d)/, '$1.$2');
            value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
            value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
            value = value.replace(/(\d{4})(\d)/, '$1-$2');
        }

        el.value = value;
    }

    /* =========================================
       Gateway SDK Loading
       ========================================= */

    function loadGatewaySDK(slug) {
        return new Promise(function (resolve, reject) {
            const url = SDK_URLS[slug];
            if (!url) {
                resolve(); // Gateway sem SDK JS
                return;
            }

            // Já carregado?
            if (slug === 'stripe' && window.Stripe) { resolve(); return; }
            if (slug === 'mercadopago' && window.MercadoPago) { resolve(); return; }
            if (slug === 'pagseguro' && window.PagSeguro) { resolve(); return; }

            const script = document.createElement('script');
            script.src = url;
            script.async = true;
            script.onload = resolve;
            script.onerror = function () { reject(new Error('Falha ao carregar SDK: ' + slug)); };
            document.head.appendChild(script);
        });
    }

    function initGateway() {
        const slug = config.gatewaySlug;
        const pk = config.publicKey;

        if (slug === 'stripe' && window.Stripe && pk) {
            gatewayInstance = window.Stripe(pk);
            const elements = gatewayInstance.elements();
            stripeElements = {
                card: elements.create('card', { hidePostalCode: true }),
            };
            const cardEl = document.getElementById('card-element');
            if (cardEl) {
                stripeElements.card.mount('#card-element');
                // Hide separate expiry/cvv for Stripe (Stripe card element includes them)
                hideElement('card-expiry-element');
                hideElement('card-cvv-element');
            }
        } else if (slug === 'mercadopago' && window.MercadoPago && pk) {
            gatewayInstance = new window.MercadoPago(pk);
        }
        // PagSeguro doesn't need initialization
    }

    /* =========================================
       Card Tokenization
       ========================================= */

    // Helper: read card input fields
    function getCardFields() {
        const numEl = document.getElementById('cardNumber');
        const expEl = document.getElementById('cardExpiry');
        const cvvEl = document.getElementById('cardCvv');
        const number = numEl ? numEl.value.replace(/\s/g, '') : '';
        const expiry = expEl ? expEl.value : '';
        const cvv = cvvEl ? cvvEl.value : '';
        const expParts = expiry.split('/');
        return {
            number: number,
            expMonth: expParts[0] || '',
            expYear: expParts[1] || '',
            cvv: cvv
        };
    }

    function tokenizeCard() {
        const slug = config.gatewaySlug;
        const card = getCardFields();

        if (slug === 'stripe' && gatewayInstance && stripeElements) {
            return gatewayInstance.createPaymentMethod({
                type: 'card',
                card: stripeElements.card,
                billing_details: {
                    name: (document.getElementById('cardHolderName') || {}).value || ''
                }
            }).then(function (result) {
                if (result.error) {
                    throw new Error(result.error.message);
                }
                return result.paymentMethod.id;
            });
        }

        if (slug === 'mercadopago') {
            return new Promise(function (resolve, reject) {
                var docValue = ((document.getElementById('cardDocument') || {}).value || '').replace(/\D/g, '');
                var holderName = (document.getElementById('cardHolderName') || {}).value || '';

                // Validação básica antes de chamar o SDK
                if (!card.number || card.number.length < 13) {
                    reject(new Error('Número do cartão inválido.'));
                    return;
                }
                if (!card.expMonth || !card.expYear) {
                    reject(new Error('Data de validade inválida.'));
                    return;
                }
                if (!card.cvv || card.cvv.length < 3) {
                    reject(new Error('CVV inválido.'));
                    return;
                }
                if (!holderName) {
                    reject(new Error('Informe o nome no cartão.'));
                    return;
                }
                if (!docValue || (docValue.length !== 11 && docValue.length !== 14)) {
                    reject(new Error('Informe um CPF ou CNPJ válido.'));
                    return;
                }

                var docType = docValue.length > 11 ? 'CNPJ' : 'CPF';
                var expYear = card.expYear.length === 2 ? '20' + card.expYear : card.expYear;

                /**
                 * Fallback: chamar a API REST do MercadoPago diretamente via fetch().
                 * POST https://api.mercadopago.com/v1/card_tokens?public_key=PK
                 * É um endpoint público (usa public_key, não access_token),
                 * seguro para chamar do frontend — equivalente ao que o SDK faz internamente.
                 */
                function createTokenViaApi() {
                    var apiUrl = 'https://api.mercadopago.com/v1/card_tokens?public_key=' + encodeURIComponent(config.publicKey);
                    var body = {
                        card_number: card.number,
                        cardholder: {
                            name: holderName,
                            identification: {
                                type: docType,
                                number: docValue
                            }
                        },
                        expiration_month: parseInt(card.expMonth, 10),
                        expiration_year: parseInt(expYear, 10),
                        security_code: card.cvv
                    };

                    return fetch(apiUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(body)
                    }).then(function (resp) {
                        return resp.json().then(function (data) {
                            if (resp.ok && data.id) {
                                return data.id;
                            }
                            var errMsg = data.message || data.cause && data.cause[0] && data.cause[0].description || 'Dados do cartão inválidos.';
                            throw new Error(errMsg);
                        });
                    });
                }

                // Estratégia: tentar SDK primeiro, se falhar usar API REST direta
                if (gatewayInstance) {
                    var tokenDataFull = {
                        cardNumber: card.number,
                        cardholderName: holderName,
                        cardExpirationMonth: card.expMonth,
                        cardExpirationYear: expYear,
                        securityCode: card.cvv,
                        identificationType: docType,
                        identificationNumber: docValue
                    };

                    gatewayInstance.createCardToken(tokenDataFull).then(function (result) {
                        if (result && result.id) {
                            resolve(result.id);
                        } else {
                            throw new Error('Token vazio');
                        }
                    }).catch(function (sdkErr) {
                        console.warn('[AktiCheckout] SDK createCardToken falhou, usando API REST direta:', sdkErr);
                        createTokenViaApi().then(resolve).catch(function (apiErr) {
                            console.error('[AktiCheckout] API REST createCardToken também falhou:', apiErr);
                            reject(apiErr);
                        });
                    });
                } else {
                    // SDK não carregou — usar API REST direta
                    console.warn('[AktiCheckout] SDK MercadoPago não disponível, usando API REST direta');
                    createTokenViaApi().then(resolve).catch(function (apiErr) {
                        console.error('[AktiCheckout] API REST createCardToken falhou:', apiErr);
                        reject(apiErr);
                    });
                }
            });
        }

        if (slug === 'pagseguro' && window.PagSeguro) {
            return new Promise(function (resolve, reject) {
                try {
                    const enc = window.PagSeguro.encryptCard({
                        publicKey: config.publicKey,
                        holder: (document.getElementById('cardHolderName') || {}).value || '',
                        number: card.number,
                        expMonth: card.expMonth,
                        expYear: card.expYear.length === 2 ? '20' + card.expYear : card.expYear,
                        securityCode: card.cvv
                    });
                    if (enc.hasErrors) {
                        reject(new Error('Dados do cartão inválidos.'));
                    } else {
                        resolve(enc.encryptedCard);
                    }
                } catch (e) {
                    reject(e);
                }
            });
        }

        return Promise.reject(new Error('Gateway não suportado para cartão.'));
    }

    function handle3DSecure(clientSecret) {
        if (config.gatewaySlug !== 'stripe' || !gatewayInstance) return;

        showLoading('Verificação de segurança...');

        gatewayInstance.confirmCardPayment(clientSecret)
            .then(function (result) {
                Swal.close();
                if (result.error) {
                    showError(result.error.message);
                } else if (result.paymentIntent && result.paymentIntent.status === 'succeeded') {
                    showSuccess('Pagamento aprovado!');
                    setTimeout(function () {
                        window.location.href = config.confirmationUrl + '&status=succeeded';
                    }, 2000);
                }
            })
            .catch(function () {
                Swal.close();
                showError('Falha na verificação de segurança.');
            });
    }

    /* =========================================
       API Communication
       ========================================= */

    function postPayment(data) {
        data.token = config.token;

        return fetch(config.processUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        }).then(function (resp) {
            return resp.json();
        });
    }

    /* =========================================
       Polling
       ========================================= */

    function startPolling(externalId) {
        let attempts = 0;
        const maxAttempts = 360; // 30 min at 5s interval

        pollingInterval = setInterval(function () {
            attempts++;
            if (attempts >= maxAttempts) {
                stopPolling();
                Swal.fire({
                    icon: 'info',
                    title: 'Verificação pausada',
                    html: 'Se você já realizou o pagamento, ele será confirmado em breve.<br>Você pode fechar esta página.',
                    confirmButtonText: 'OK'
                });
                return;
            }

            checkPaymentStatus(externalId);
        }, 5000);
    }

    function checkPaymentStatus(externalId) {
        const url = config.statusUrl + '&token=' + encodeURIComponent(config.token) +
            (externalId ? '&external_id=' + encodeURIComponent(externalId) : '');

        fetch(url, {
            headers: { 'Accept': 'application/json' }
        })
            .then(function (resp) { return resp.json(); })
            .then(function (data) {
                if (data.paid === true || data.status === 'succeeded') {
                    stopPolling();
                    stopCountdown();

                    Swal.fire({
                        icon: 'success',
                        title: 'Pagamento confirmado!',
                        text: 'Redirecionando...',
                        timer: 2500,
                        showConfirmButton: false,
                        allowOutsideClick: false
                    });

                    setTimeout(function () {
                        window.location.href = config.confirmationUrl + '&status=succeeded&external_id=' + encodeURIComponent(externalId || '');
                    }, 2500);
                }
            })
            .catch(function () {
                // Silently fail — next poll will retry
            });
    }

    function stopPolling() {
        if (pollingInterval) {
            clearInterval(pollingInterval);
            pollingInterval = null;
        }
    }

    /* =========================================
       Countdown
       ========================================= */

    function startCountdown(seconds) {
        let remaining = seconds;
        updateCountdownDisplay(remaining);

        countdownInterval = setInterval(function () {
            remaining--;
            updateCountdownDisplay(remaining);

            if (remaining <= 0) {
                stopCountdown();
                stopPolling();

                const timerEl = document.getElementById('pixTimer');
                if (timerEl) timerEl.textContent = 'Expirado';

                const pollingStatus = document.getElementById('pixPollingStatus');
                if (pollingStatus) pollingStatus.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-circle me-1"></i> PIX expirado. Gere um novo código.</span>';
            }
        }, 1000);
    }

    function updateCountdownDisplay(seconds) {
        const timerEl = document.getElementById('pixTimer');
        if (!timerEl) return;

        const min = Math.floor(seconds / 60);
        const sec = seconds % 60;
        timerEl.textContent = String(min).padStart(2, '0') + ':' + String(sec).padStart(2, '0');
    }

    function stopCountdown() {
        if (countdownInterval) {
            clearInterval(countdownInterval);
            countdownInterval = null;
        }
    }

    /* =========================================
       UI Helpers
       ========================================= */

    function showLoading(msg) {
        Swal.fire({
            title: msg || 'Processando...',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: function () { Swal.showLoading(); }
        });
    }

    function showError(msg) {
        Swal.fire({
            icon: 'error',
            title: 'Erro',
            text: msg,
            confirmButtonColor: 'var(--checkout-primary)'
        });
    }

    function showSuccess(msg) {
        Swal.fire({
            icon: 'success',
            title: msg,
            showConfirmButton: false,
            timer: 2500
        });
    }

    function showToast(msg) {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true
        });
        Toast.fire({ icon: 'success', title: msg });
    }

    function fallbackCopy(input) {
        input.select();
        input.setSelectionRange(0, 99999);
        document.execCommand('copy');
        showToast('Copiado!');
    }

    function hideElement(id) {
        const el = document.getElementById(id);
        if (el) {
            const wrapper = el.closest('.col-6') || el.parentElement;
            if (wrapper) wrapper.style.display = 'none';
        }
    }

    /* =========================================
       Return Public API
       ========================================= */
    return {
        init: init,
        processPixPayment: processPixPayment,
        processCardPayment: processCardPayment,
        processBoletoPayment: processBoletoPayment,
        copyToClipboard: copyToClipboard,
        maskCpfCnpj: maskCpfCnpj,
        checkPaymentStatus: checkPaymentStatus,
        destroy: function () {
            stopPolling();
            stopCountdown();
        }
    };
})();

/**
 * Confirmation Polling — Usado na página de confirmação (estado pendente).
 */
const ConfirmationPolling = (function () {
    'use strict';

    let interval = null;
    let attempts = 0;
    const maxAttempts = 360; // 30 min / 5s

    function init() {
        if (typeof CONFIRMATION_CONFIG === 'undefined') return;

        startPolling();

        const btnCheckNow = document.getElementById('btnCheckNow');
        if (btnCheckNow) {
            btnCheckNow.addEventListener('click', function () {
                poll();
            });
        }
    }

    function startPolling() {
        interval = setInterval(poll, 5000);
    }

    function poll() {
        attempts++;

        // Update progress bar
        const progressBar = document.querySelector('#pollingProgress .progress-bar');
        if (progressBar) {
            const pct = Math.min((attempts / maxAttempts) * 100, 100);
            progressBar.style.width = pct + '%';
        }

        if (attempts >= maxAttempts) {
            stopPolling();
            Swal.fire({
                icon: 'info',
                title: 'Verificação pausada',
                html: 'Se você já realizou o pagamento, ele será confirmado em breve.<br>Você pode fechar esta página.',
                confirmButtonText: 'OK'
            });
            return;
        }

        const cfg = CONFIRMATION_CONFIG;
        const url = cfg.statusUrl + '&token=' + encodeURIComponent(cfg.token) +
            (cfg.externalId ? '&external_id=' + encodeURIComponent(cfg.externalId) : '');

        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function (resp) { return resp.json(); })
            .then(function (data) {
                if (data.paid === true || data.status === 'succeeded') {
                    stopPolling();
                    transitionToSuccess(data);
                } else if (data.status === 'failed') {
                    stopPolling();
                    window.location.href = cfg.confirmationUrl + '&token=' + encodeURIComponent(cfg.token) + '&status=error';
                }
            })
            .catch(function () {
                // Silently continue
            });
    }

    function stopPolling() {
        if (interval) {
            clearInterval(interval);
            interval = null;
        }
    }

    function transitionToSuccess(data) {
        const container = document.getElementById('confirmationContainer');
        if (!container) return;

        container.classList.add('fade-in');
        container.innerHTML = [
            '<div class="checkout-confirmation-card text-center py-4">',
            '  <div class="confirmation-checkmark mb-3">',
            '    <svg class="checkmark-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52" width="72" height="72">',
            '      <circle class="checkmark-circle" cx="26" cy="26" r="25" fill="none" stroke="var(--checkout-success)" stroke-width="2"/>',
            '      <path class="checkmark-check" fill="none" stroke="var(--checkout-success)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>',
            '    </svg>',
            '  </div>',
            '  <h4 class="fw-bold text-success mb-4">Pagamento confirmado!</h4>',
            '  <p class="text-muted">Comprovante enviado para seu email.</p>',
            '</div>'
        ].join('\n');
    }

    return {
        init: init
    };
})();

// Auto-init
document.addEventListener('DOMContentLoaded', function () {
    if (typeof CHECKOUT_CONFIG !== 'undefined') {
        AktiCheckout.init(CHECKOUT_CONFIG);
    }
});
