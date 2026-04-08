<?php

namespace Akti\Controllers;

use Akti\Models\CheckoutToken;
use Akti\Models\PaymentGateway;
use Akti\Models\CompanySettings;
use Akti\Models\Order;
use Akti\Services\CheckoutService;
use Akti\Gateways\GatewayManager;

class CheckoutController extends BaseController
{
    private CheckoutToken $tokenModel;
    private CheckoutService $checkoutService;

    public function __construct(?\PDO $db = null)
    {
        parent::__construct($db);
        $this->tokenModel = new CheckoutToken($this->db);
        $this->checkoutService = new CheckoutService($this->db);
    }

    /**
     * GET: Exibe página de checkout (pública).
     */
    public function show(): void
    {
        $token = $this->validateTokenFormat($_GET['token'] ?? '');
        if (!$token) {
            $this->renderExpired();
            return;
        }

        $tokenRow = $this->tokenModel->findByToken($token);
        if (!$tokenRow) {
            $this->renderExpired();
            return;
        }

        // Verificar status
        if ($tokenRow['status'] === 'used') {
            $this->redirectToConfirmation($token, 'succeeded');
            return;
        }
        if ($tokenRow['status'] !== 'active') {
            $this->renderExpired();
            return;
        }
        if (strtotime($tokenRow['expires_at']) < time()) {
            $this->tokenModel->markExpired((int) $tokenRow['id']);
            $this->renderExpired();
            return;
        }

        // Gravar IP
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $this->tokenModel->updateIp((int) $tokenRow['id'], $ip);

        // Resolver gateway
        $gwModel = new PaymentGateway($this->db);
        $gatewayRow = !empty($tokenRow['gateway_slug'])
            ? $gwModel->readBySlug($tokenRow['gateway_slug'])
            : $gwModel->getDefault();

        $gatewaySlug = $gatewayRow['gateway_slug'] ?? '';
        $publicKey = '';
        $supportedMethods = [];

        if ($gatewayRow) {
            $gateway = GatewayManager::resolveFromRow($gatewayRow);
            $supportedMethods = $gateway->getSupportedMethods();
            $creds = json_decode($gatewayRow['credentials'] ?? '{}', true) ?: [];
            $publicKey = $creds['publishable_key'] ?? $creds['public_key'] ?? '';
        }

        // Filtrar por allowed_methods do token
        if (!empty($tokenRow['allowed_methods'])) {
            $allowed = json_decode($tokenRow['allowed_methods'], true);
            if (is_array($allowed)) {
                $supportedMethods = array_values(array_intersect($supportedMethods, $allowed));
            }
        }

        // Manter apenas métodos com partial de checkout implementado
        $implementedMethods = ['pix', 'credit_card', 'boleto'];
        $supportedMethods = array_values(array_intersect($supportedMethods, $implementedMethods));

        // Dados da empresa
        $companySettings = new CompanySettings($this->db);
        $company = $companySettings->getAll();

        // Itens e custos extras do pedido
        $orderModel = new Order($this->db);
        $orderId = (int) ($tokenRow['order_id'] ?? 0);
        $orderItems = $orderId ? $orderModel->getItems($orderId) : [];
        $extraCosts = $orderId ? $orderModel->getExtraCosts($orderId) : [];

        // Headers de segurança
        $this->setSecurityHeaders($gatewaySlug);

        // Renderizar checkout
        $data = [
            'token'            => $tokenRow,
            'company'          => $company,
            'gatewaySlug'      => $gatewaySlug,
            'publicKey'        => $publicKey,
            'supportedMethods' => $supportedMethods,
            'orderItems'       => $orderItems,
            'extraCosts'       => $extraCosts,
        ];

        extract($data);
        require 'app/views/checkout/pay.php';
    }

    /**
     * POST (AJAX): Processa pagamento.
     */
    public function processPayment(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'error' => 'Método não permitido.'], 405);
        }

        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

        $token = $this->validateTokenFormat($input['token'] ?? '');
        if (!$token) {
            $this->json(['success' => false, 'error' => 'Token inválido.'], 400);
        }

        // Rate limiting simples por token
        $cacheKey = 'checkout_attempts_' . md5($token);
        $attempts = (int) ($_SESSION[$cacheKey] ?? 0);
        $lastAttempt = $_SESSION[$cacheKey . '_time'] ?? 0;

        // Reset após 10 min
        if (time() - $lastAttempt > 600) {
            $attempts = 0;
        }
        if ($attempts >= 5) {
            $this->json(['success' => false, 'error' => 'Muitas tentativas. Aguarde alguns minutos.', 'code' => 'rate_limited'], 429);
        }

        $_SESSION[$cacheKey] = $attempts + 1;
        $_SESSION[$cacheKey . '_time'] = time();

        $paymentData = [
            'method'            => $input['method'] ?? '',
            'card_token'        => $input['card_token'] ?? null,
            'customer_document' => $input['customer_document'] ?? null,
            'customer_name'     => $input['customer_name'] ?? null,
            'customer_email'    => $input['customer_email'] ?? null,
        ];

        $result = $this->checkoutService->processCheckout($token, $paymentData);

        $this->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * GET (AJAX): Verifica status de pagamento (polling).
     */
    public function checkStatus(): void
    {
        $token = $this->validateTokenFormat($_GET['token'] ?? '');
        $externalId = $_GET['external_id'] ?? '';

        if (!$token) {
            $this->json(['success' => false, 'error' => 'Token inválido.'], 400);
        }

        $tokenRow = $this->tokenModel->findByToken($token);
        if (!$tokenRow) {
            $this->json(['success' => false, 'error' => 'Token não encontrado.'], 404);
        }

        // Se já marcado como used, retornar sucesso
        if ($tokenRow['status'] === 'used') {
            $this->json([
                'success' => true,
                'paid'    => true,
                'status'  => 'succeeded',
                'method'  => $tokenRow['used_method'],
            ]);
        }

        // Consultar gateway se tiver external_id
        if (!empty($externalId)) {
            $gwModel = new PaymentGateway($this->db);
            $gatewayRow = !empty($tokenRow['gateway_slug'])
                ? $gwModel->readBySlug($tokenRow['gateway_slug'])
                : $gwModel->getDefault();

            if ($gatewayRow) {
                try {
                    $gateway = GatewayManager::resolveFromRow($gatewayRow);
                    $status = $gateway->getChargeStatus($externalId);

                    if (($status['status'] ?? '') === 'approved' || ($status['status'] ?? '') === 'succeeded') {
                        // Marcar como usado
                        $this->tokenModel->markUsed(
                            (int) $tokenRow['id'],
                            $tokenRow['used_method'] ?? 'unknown',
                            $externalId
                        );
                        $this->json([
                            'success' => true,
                            'paid'    => true,
                            'status'  => 'succeeded',
                        ]);
                    }

                    $this->json([
                        'success' => true,
                        'paid'    => false,
                        'status'  => $status['status'] ?? 'pending',
                    ]);
                } catch (\Throwable $e) {
                    // Falha ao consultar — retornar pendente
                }
            }
        }

        $this->json([
            'success' => true,
            'paid'    => false,
            'status'  => 'pending',
        ]);
    }

    /**
     * GET: Página de confirmação de pagamento (3 estados).
     */
    public function confirmation(): void
    {
        $token = $this->validateTokenFormat($_GET['token'] ?? '');
        if (!$token) {
            $this->renderExpired();
            return;
        }

        $tokenRow = $this->tokenModel->findByToken($token);
        if (!$tokenRow) {
            $this->renderExpired();
            return;
        }

        // Dados da empresa
        $companySettings = new CompanySettings($this->db);
        $company = $companySettings->getAll();

        // Determinar estado
        $status = $_GET['status'] ?? '';
        $externalId = $_GET['external_id'] ?? '';
        $errorMessage = $_GET['error_message'] ?? '';

        if ($tokenRow['status'] === 'used') {
            $confirmationState = 'succeeded';
        } elseif ($status === 'error') {
            $confirmationState = 'error';
        } elseif ($tokenRow['status'] === 'active' && !empty($externalId)) {
            // Verificar se já pagou no gateway
            $gwModel = new PaymentGateway($this->db);
            $gatewayRow = !empty($tokenRow['gateway_slug'])
                ? $gwModel->readBySlug($tokenRow['gateway_slug'])
                : $gwModel->getDefault();

            if ($gatewayRow) {
                try {
                    $gateway = GatewayManager::resolveFromRow($gatewayRow);
                    $chargeStatus = $gateway->getChargeStatus($externalId);
                    if (($chargeStatus['status'] ?? '') === 'approved' || ($chargeStatus['status'] ?? '') === 'succeeded') {
                        $this->tokenModel->markUsed((int) $tokenRow['id'], '', $externalId);
                        $confirmationState = 'succeeded';
                        $tokenRow['used_at'] = date('Y-m-d H:i:s');
                        $tokenRow['external_id'] = $externalId;
                    } else {
                        $confirmationState = 'pending';
                    }
                } catch (\Throwable $e) {
                    $confirmationState = 'pending';
                }
            } else {
                $confirmationState = 'pending';
            }
        } elseif ($tokenRow['status'] === 'expired' || $tokenRow['status'] === 'cancelled') {
            $this->renderExpired();
            return;
        } elseif ($status === 'succeeded') {
            $confirmationState = 'succeeded';
        } elseif ($status === 'pending') {
            $confirmationState = 'pending';
        } else {
            // Token ativo sem external_id — voltar ao checkout
            $this->redirect('/?page=checkout&token=' . urlencode($token));
        }

        $this->setSecurityHeaders('');

        $data = [
            'token'              => $tokenRow,
            'company'            => $company,
            'confirmationState'  => $confirmationState,
            'externalId'         => $externalId,
            'errorMessage'       => $errorMessage,
        ];

        extract($data);
        require 'app/views/checkout/confirmation.php';
    }

    /**
     * Valida formato do token (64 chars hex).
     */
    private function validateTokenFormat(string $token): ?string
    {
        $token = trim($token);
        if (preg_match('/^[a-f0-9]{64}$/i', $token)) {
            return $token;
        }
        return null;
    }

    /**
     * Renderiza página de token expirado/inválido.
     */
    private function renderExpired(): void
    {
        $companySettings = new CompanySettings($this->db);
        $company = $companySettings->getAll();
        require 'app/views/checkout/expired.php';
    }

    /**
     * Redireciona para página de confirmação.
     */
    private function redirectToConfirmation(string $token, string $status, string $externalId = ''): void
    {
        $url = '/?page=checkout&action=confirmation&token=' . urlencode($token) . '&status=' . urlencode($status);
        if ($externalId) {
            $url .= '&external_id=' . urlencode($externalId);
        }
        $this->redirect($url);
    }

    /**
     * Define headers de segurança para a página de checkout.
     */
    private function setSecurityHeaders(string $gatewaySlug): void
    {
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');

        $scriptSrc = "'self' 'unsafe-inline'";
        $frameSrc = "'none'";
        $connectSrc = "'self' https:";

        switch ($gatewaySlug) {
            case 'stripe':
                $scriptSrc .= " https://js.stripe.com";
                $frameSrc = "https://js.stripe.com https://hooks.stripe.com";
                break;
            case 'mercadopago':
                $scriptSrc .= " https://sdk.mercadopago.com https://http2.mlstatic.com";
                $frameSrc = "https://www.mercadopago.com.br https://sdk.mercadopago.com";
                $connectSrc = "'self' https://api.mercadopago.com https://events.mercadopago.com https://sdk.mercadopago.com https:";
                break;
            case 'pagseguro':
                $scriptSrc .= " https://assets.pagseguro.com.br";
                $frameSrc = "https://pagseguro.uol.com.br";
                break;
        }

        // CDNs usadas no checkout: Bootstrap, SweetAlert2, FontAwesome
        $scriptSrc .= " https://cdn.jsdelivr.net";

        header("Content-Security-Policy: default-src 'self'; script-src {$scriptSrc}; frame-src {$frameSrc}; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; font-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.gstatic.com; img-src 'self' data: https:; connect-src {$connectSrc};");
    }
}
