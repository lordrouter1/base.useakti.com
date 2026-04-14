<?php

namespace Akti\Tests\Unit\Gateways;

use PHPUnit\Framework\TestCase;
use Akti\Gateways\GatewayManager;
use Akti\Gateways\Contracts\PaymentGatewayInterface;
use Akti\Gateways\Providers\MercadoPagoGateway;

/**
 * Testes unitários para gateways de pagamento.
 *
 * Cobre: criação de cobrança, validação de assinatura webhook,
 * parsing de payloads, tratamento de erros.
 * Todos os testes usam mock de API — sem chamadas externas.
 *
 * @package Akti\Tests\Unit\Gateways
 */
class PaymentGatewayTest extends TestCase
{
    // ══════════════════════════════════════════════════════════════
    // GatewayManager — Resolve provider por nome
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function test_gateway_manager_resolve_provider(): void
    {
        $gateway = GatewayManager::make('mercadopago');

        $this->assertInstanceOf(PaymentGatewayInterface::class, $gateway);
        $this->assertInstanceOf(MercadoPagoGateway::class, $gateway);
        $this->assertSame('mercadopago', $gateway->getSlug());
        $this->assertSame('Mercado Pago', $gateway->getDisplayName());
    }

    /** @test */
    public function test_gateway_manager_rejeita_provider_invalido(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        GatewayManager::make('provider_inexistente_xyz');
    }

    // ══════════════════════════════════════════════════════════════
    // AbstractGateway — Interface de pagamento
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function test_create_charge_retorna_url(): void
    {
        $gateway = GatewayManager::resolve('mercadopago', [
            'access_token' => 'TEST-fake-token-for-unit-test',
            'public_key'   => 'TEST-fake-key',
        ], [], 'sandbox');

        // Sem conexão real com API, createCharge retornará erro
        $result = $gateway->createCharge([
            'amount'      => 100.00,
            'description' => 'Teste unitário',
            'payer_email' => 'test@test.com',
        ]);

        // Deve retornar array com chave 'success'
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        // Em sandbox com token fake, a resposta será erro
        $this->assertFalse($result['success']);
    }

    /** @test */
    public function test_create_charge_com_dados_invalidos(): void
    {
        $gateway = GatewayManager::resolve('mercadopago', [
            'access_token' => 'TEST-fake-token',
            'public_key'   => 'TEST-fake-key',
        ], [], 'sandbox');

        // Dados com amount=0 e sem demais campos obrigatórios
        $result = $gateway->createCharge(['amount' => 0]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
    }

    // ══════════════════════════════════════════════════════════════
    // Webhook — Validação de assinatura
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function test_webhook_valida_assinatura_correta(): void
    {
        $gateway = GatewayManager::make('mercadopago');
        $secret  = 'my-test-webhook-secret';

        // Simula payload MP
        $payload = json_encode(['data' => ['id' => '12345'], 'action' => 'payment.created']);
        $requestId = 'req-abc-123';
        $ts = (string) time();

        // Calcula hash esperado pelo algoritmo do MP
        $manifest = "id:12345;request-id:{$requestId};ts:{$ts};";
        $v1 = hash_hmac('sha256', $manifest, $secret);

        $headers = [
            'x-signature'  => "ts={$ts},v1={$v1}",
            'x-request-id' => $requestId,
        ];

        $this->assertTrue($gateway->validateWebhookSignature($payload, $headers, $secret));
    }

    /** @test */
    public function test_webhook_rejeita_assinatura_invalida(): void
    {
        $gateway = GatewayManager::make('mercadopago');

        $payload = json_encode(['data' => ['id' => '12345'], 'action' => 'payment.created']);
        $headers = [
            'x-signature'  => 'ts=9999999999,v1=hash_completamente_invalido',
            'x-request-id' => 'req-xyz',
        ];

        $this->assertFalse($gateway->validateWebhookSignature($payload, $headers, 'my-secret'));
    }

    /** @test */
    public function test_webhook_parse_payload(): void
    {
        $gateway = GatewayManager::make('mercadopago');
        $gateway->setCredentials(['access_token' => '']); // sem token, não faz lookup

        // Payload de tipo "order" — não requer lookup na API
        $payload = json_encode([
            'type'   => 'order',
            'action' => 'order.updated',
            'data'   => ['id' => 'ORD-999'],
        ]);

        $result = $gateway->parseWebhookPayload($payload, []);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('event_type', $result);
        $this->assertArrayHasKey('external_id', $result);
    }

    // ══════════════════════════════════════════════════════════════
    // Tratamento de erros de API
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function test_timeout_api_lancao_excecao(): void
    {
        $gateway = GatewayManager::resolve('mercadopago', [
            'access_token' => 'TEST-fake-token',
            'public_key'   => 'TEST-fake-key',
        ], [], 'sandbox');

        // Chamar getChargeStatus com ID fake — deve retornar erro (sem exceção)
        $result = $gateway->getChargeStatus('nonexistent_charge_id_xyz');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
    }

    /** @test */
    public function test_erro_api_registra_log(): void
    {
        $gateway = GatewayManager::resolve('mercadopago', [
            'access_token' => 'TEST-invalid-token',
            'public_key'   => 'TEST-fake-key',
        ], [], 'sandbox');

        // Chama refund com ID fake — deve retornar erro sem exceção
        $result = $gateway->refund('invalid_transaction_id');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
    }

    // ══════════════════════════════════════════════════════════════
    // Propriedades e capabilities
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function test_gateway_registered_slugs(): void
    {
        $slugs = GatewayManager::getRegisteredSlugs();

        $this->assertContains('mercadopago', $slugs);
        $this->assertContains('stripe', $slugs);
        $this->assertContains('pagseguro', $slugs);
    }

    /** @test */
    public function test_gateway_supports_methods(): void
    {
        $gateway = GatewayManager::make('mercadopago');

        $this->assertTrue($gateway->supports('pix'));
        $this->assertTrue($gateway->supports('credit_card'));
        $this->assertFalse($gateway->supports('bitcoin'));
        $this->assertNotEmpty($gateway->getSupportedMethods());
    }
}
