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
    let gatewayReady = false;
    let gatewayInitPromise = null;
    let elementsReadyPromise = null; // resolves when Stripe Elements are mounted + ready

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
        // All gateway SDKs (Stripe, MercadoPago, PagSeguro) are only needed for
        // credit card tokenization. PIX and Boleto are processed entirely server-side.
        // Defer SDK loading until the credit_card tab is selected to avoid:
        //  - Stripe.js continuous telemetry ("b" requests / advancedFraudSignals)
        //  - MercadoPago SDK device fingerprinting and background requests
        //  - Unnecessary script loading when user only wants PIX/Boleto
    }

    /**
     * Lazy-load and initialize the gateway SDK.
     * Returns a Promise that resolves when the SDK is ready.
     */
    function ensureGatewayReady() {
        if (gatewayReady) return Promise.resolve();
        if (gatewayInitPromise) return gatewayInitPromise;

        gatewayInitPromise = loadGatewaySDK(config.gatewaySlug).then(function () {
            return initGateway();
        }).then(function () {
            gatewayReady = true;
        }).catch(function (err) {
            gatewayInitPromise = null;
            throw err;
        });

        return gatewayInitPromise;
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
                } else if (qrImg && data.qr_code_image_url) {
                    qrImg.src = data.qr_code_image_url;
                } else if (qrImg && data.qr_code_url) {
                    qrImg.src = data.qr_code_url;
                } else if (!data.qr_code && !data.qr_code_base64 && data.payment_url) {
                    // Gateway retornou apenas URL de pagamento (sem QR inline)
                    window.location.href = data.payment_url;
                    return;
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

        // Ensure gateway SDK is loaded AND elements are ready before tokenizing
        ensureGatewayReady().then(function () {
            return elementsReadyPromise || Promise.resolve();
        }).then(function () {
            return tokenizeCard();
        })
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
                } else if (data.client_secret && config.gatewaySlug === 'stripe') {
                    // 3D Secure ou confirmação pendente via Stripe
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
                } else if (!data.boleto_barcode && !data.barcode && data.payment_url) {
                    // Gateway retornou apenas URL de pagamento (sem boleto inline)
                    window.location.href = data.payment_url;
                    return;
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

            // Prevent duplicate script tag (avoids "Stripe.js was loaded more than one time")
            var existing = document.querySelector('script[src="' + url + '"]');
            if (existing) {
                existing.addEventListener('load', resolve);
                return;
            }

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
            gatewayInstance = window.Stripe(pk, { advancedFraudSignals: false });
            var elements = gatewayInstance.elements();

            var style = {
                base: {
                    fontSize: '16px',
                    color: '#32325d',
                    fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
                    '::placeholder': { color: '#aab7c4' }
                },
                invalid: { color: '#dc3545' }
            };

            // Create separate elements for each field
            stripeElements = {
                cardNumber: elements.create('cardNumber', { style: style, showIcon: true }),
                cardExpiry: elements.create('cardExpiry', { style: style }),
                cardCvc: elements.create('cardCvc', { style: style })
            };

            // Mount CardNumber — hide manual input, show Stripe element
            var stripeNumEl = document.getElementById('stripe-card-number');
            var manualNum = document.getElementById('cardNumber');
            if (stripeNumEl) {
                stripeNumEl.style.display = 'block';
                stripeElements.cardNumber.mount('#stripe-card-number');
            }
            if (manualNum) {
                manualNum.style.display = 'none';
                manualNum.removeAttribute('required');
            }

            // Mount CardExpiry — hide manual input, show Stripe element
            var stripeExpEl = document.getElementById('stripe-card-expiry');
            var manualExp = document.getElementById('cardExpiry');
            if (stripeExpEl) {
                stripeExpEl.style.display = 'block';
                stripeElements.cardExpiry.mount('#stripe-card-expiry');
            }
            if (manualExp) {
                manualExp.style.display = 'none';
                manualExp.removeAttribute('required');
            }

            // Mount CardCvc — hide manual input, show Stripe element
            var stripeCvcEl = document.getElementById('stripe-card-cvc');
            var manualCvv = document.getElementById('cardCvv');
            if (stripeCvcEl) {
                stripeCvcEl.style.display = 'block';
                stripeElements.cardCvc.mount('#stripe-card-cvc');
            }
            if (manualCvv) {
                manualCvv.style.display = 'none';
                manualCvv.removeAttribute('required');
            }

            // Stripe validation errors
            function handleStripeError(event) {
                var errDiv = document.getElementById('card-errors');
                if (errDiv) {
                    if (event.error) {
                        errDiv.textContent = event.error.message;
                        errDiv.style.display = 'block';
                    } else {
                        errDiv.textContent = '';
                        errDiv.style.display = 'none';
                    }
                }
            }
            stripeElements.cardNumber.on('change', handleStripeError);
            stripeElements.cardExpiry.on('change', handleStripeError);
            stripeElements.cardCvc.on('change', handleStripeError);

            // Wait for all three Elements to emit "ready" before resolving.
            // Stripe's createPaymentMethod fails if called before ready.
            elementsReadyPromise = new Promise(function (resolve) {
                var readyCount = 0;
                function onReady() {
                    readyCount++;
                    if (readyCount >= 3) resolve();
                }
                stripeElements.cardNumber.on('ready', onReady);
                stripeElements.cardExpiry.on('ready', onReady);
                stripeElements.cardCvc.on('ready', onReady);
            });
            return elementsReadyPromise;

        } else if (slug === 'mercadopago' && window.MercadoPago && pk) {
            gatewayInstance = new window.MercadoPago(pk);
        }
        // PagSeguro doesn't need initialization
        return Promise.resolve();
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
                card: stripeElements.cardNumber,
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
                 * Fallback 1: chamar a API REST do MercadoPago diretamente via fetch().
                 * Funciona em HTTPS mas pode falhar em HTTP local (CORS).
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

                /**
                 * Fallback 2: proxy server-side (cURL, sem CORS).
                 * Usado quando o ambiente HTTP bloqueia chamadas diretas à API do MP.
                 */
                function createTokenViaProxy() {
                    if (!config.tokenizeUrl) {
                        return Promise.reject(new Error('Proxy de tokenização não disponível.'));
                    }
                    var body = {
                        token: config.token,
                        card_number: card.number,
                        cardholder_name: holderName,
                        identification_type: docType,
                        identification_number: docValue,
                        exp_month: parseInt(card.expMonth, 10),
                        exp_year: parseInt(expYear, 10),
                        security_code: card.cvv
                    };

                    return fetch(config.tokenizeUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        body: JSON.stringify(body)
                    }).then(function (resp) {
                        return resp.json().then(function (data) {
                            if (data.success && data.card_token) {
                                return data.card_token;
                            }
                            throw new Error(data.error || 'Falha na tokenização.');
                        });
                    });
                }

                // Estratégia 3 níveis: SDK → API REST direta → proxy server-side
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
                        console.warn('[AktiCheckout] SDK falhou, tentando API REST direta:', sdkErr);
                        createTokenViaApi().then(resolve).catch(function (apiErr) {
                            console.warn('[AktiCheckout] API REST falhou (CORS?), usando proxy server-side:', apiErr);
                            createTokenViaProxy().then(resolve).catch(function (proxyErr) {
                                console.error('[AktiCheckout] Todas as tentativas de tokenização falharam:', proxyErr);
                                reject(proxyErr);
                            });
                        });
                    });
                } else {
                    // SDK não carregou — tentar API REST, depois proxy
                    console.warn('[AktiCheckout] SDK MercadoPago não disponível');
                    createTokenViaApi().then(resolve).catch(function (apiErr) {
                        console.warn('[AktiCheckout] API REST falhou, usando proxy server-side:', apiErr);
                        createTokenViaProxy().then(resolve).catch(function (proxyErr) {
                            console.error('[AktiCheckout] Todas as tentativas de tokenização falharam:', proxyErr);
                            reject(proxyErr);
                        });
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
    /**
     * Unmount Stripe Elements to stop telemetry when card tab is hidden.
     */
    function unmountStripeElements() {
        if (!stripeElements) return;
        if (stripeElements.cardNumber) { try { stripeElements.cardNumber.unmount(); } catch (e) {} }
        if (stripeElements.cardExpiry) { try { stripeElements.cardExpiry.unmount(); } catch (e) {} }
        if (stripeElements.cardCvc)    { try { stripeElements.cardCvc.unmount(); } catch (e) {} }
    }

    /**
     * Remount Stripe Elements when card tab becomes visible again.
     * Returns a Promise that resolves when all re-mounted elements are ready.
     */
    function remountStripeElements() {
        if (!stripeElements) return Promise.resolve();

        var mounted = [];
        if (stripeElements.cardNumber && document.getElementById('stripe-card-number')) {
            try { stripeElements.cardNumber.mount('#stripe-card-number'); mounted.push(stripeElements.cardNumber); } catch (e) {}
        }
        if (stripeElements.cardExpiry && document.getElementById('stripe-card-expiry')) {
            try { stripeElements.cardExpiry.mount('#stripe-card-expiry'); mounted.push(stripeElements.cardExpiry); } catch (e) {}
        }
        if (stripeElements.cardCvc && document.getElementById('stripe-card-cvc')) {
            try { stripeElements.cardCvc.mount('#stripe-card-cvc'); mounted.push(stripeElements.cardCvc); } catch (e) {}
        }

        if (mounted.length === 0) return Promise.resolve();

        // Wait for all remounted elements to be ready
        elementsReadyPromise = new Promise(function (resolve) {
            var readyCount = 0;
            function onReady() {
                readyCount++;
                if (readyCount >= mounted.length) resolve();
            }
            for (var i = 0; i < mounted.length; i++) {
                mounted[i].on('ready', onReady);
            }
        });
        return elementsReadyPromise;
    }

    return {
        init: init,
        ensureGatewayReady: ensureGatewayReady,
        processPixPayment: processPixPayment,
        processCardPayment: processCardPayment,
        processBoletoPayment: processBoletoPayment,
        copyToClipboard: copyToClipboard,
        maskCpfCnpj: maskCpfCnpj,
        checkPaymentStatus: checkPaymentStatus,
        unmountStripeElements: unmountStripeElements,
        remountStripeElements: remountStripeElements,
        destroy: function () {
            stopPolling();
            stopCountdown();
            unmountStripeElements();
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
