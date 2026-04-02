<?php

namespace Akti\Tests\Unit\Gateways;

use PHPUnit\Framework\TestCase;

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
        $this->markTestIncomplete(
            'Requer mock de GatewayManager com providers registrados. '
            . 'Verificar que resolve() retorna instância correta de AbstractGateway.'
        );
    }

    /** @test */
    public function test_gateway_manager_rejeita_provider_invalido(): void
    {
        $this->markTestIncomplete(
            'Requer GatewayManager — deve lançar exceção para provider não registrado.'
        );
    }

    // ══════════════════════════════════════════════════════════════
    // AbstractGateway — Interface de pagamento
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function test_create_charge_retorna_url(): void
    {
        $this->markTestIncomplete(
            'Requer mock de provider específico (Mercado Pago, PagSeguro, etc.) '
            . 'com resposta simulada contendo URL de pagamento.'
        );
    }

    /** @test */
    public function test_create_charge_com_dados_invalidos(): void
    {
        $this->markTestIncomplete(
            'Requer mock — deve lançar exceção quando faltar dados obrigatórios '
            . '(valor, descrição, dados do cliente).'
        );
    }

    // ══════════════════════════════════════════════════════════════
    // Webhook — Validação de assinatura
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function test_webhook_valida_assinatura_correta(): void
    {
        $this->markTestIncomplete(
            'Requer mock de webhook payload com assinatura HMAC válida. '
            . 'Verificar que validateSignature() retorna true.'
        );
    }

    /** @test */
    public function test_webhook_rejeita_assinatura_invalida(): void
    {
        $this->markTestIncomplete(
            'Requer mock de webhook payload com assinatura incorreta. '
            . 'Verificar que validateSignature() retorna false.'
        );
    }

    /** @test */
    public function test_webhook_parse_payload(): void
    {
        $this->markTestIncomplete(
            'Requer payload JSON simulado de webhook. '
            . 'Verificar que parsePayload() extrai transaction_id, status, amount.'
        );
    }

    // ══════════════════════════════════════════════════════════════
    // Tratamento de erros de API
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function test_timeout_api_lancao_excecao(): void
    {
        $this->markTestIncomplete(
            'Requer mock de HTTP client com timeout. '
            . 'Verificar que exceção GatewayTimeoutException é lançada.'
        );
    }

    /** @test */
    public function test_erro_api_registra_log(): void
    {
        $this->markTestIncomplete(
            'Requer mock de HTTP client com erro 500. '
            . 'Verificar que Log::error() é chamado com detalhes da resposta.'
        );
    }
}
