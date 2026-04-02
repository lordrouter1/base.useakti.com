<?php

namespace Akti\Tests\Integration;

use Akti\Tests\TestCase;

/**
 * Testes de integração CRUD — Fluxos completos via HTTP.
 *
 * Verifica que as páginas dos módulos principais carregam
 * corretamente em todas as etapas do CRUD.
 *
 * @package Akti\Tests\Integration
 */
class CrudFlowTest extends TestCase
{
    // ══════════════════════════════════════════════════════════════
    // Produtos: listagem + formulário
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function test_produto_listagem_carrega(): void
    {
        $response = $this->httpGet('?page=products');
        $this->assertStatusOk($response['status'], 'Produto listagem');
        $this->assertNoPhpErrors($response['body'], 'Produto listagem');
    }

    /** @test */
    public function test_produto_formulario_criacao_carrega(): void
    {
        $response = $this->httpGet('?page=products&action=create');
        $this->assertStatusOk($response['status'], 'Produto create form');
        $this->assertNoPhpErrors($response['body'], 'Produto create form');
    }

    // ══════════════════════════════════════════════════════════════
    // Clientes: listagem + formulário
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function test_cliente_listagem_carrega(): void
    {
        $response = $this->httpGet('?page=customers');
        $this->assertStatusOk($response['status'], 'Cliente listagem');
        $this->assertNoPhpErrors($response['body'], 'Cliente listagem');
    }

    /** @test */
    public function test_cliente_formulario_criacao_carrega(): void
    {
        $response = $this->httpGet('?page=customers&action=create');
        $this->assertStatusOk($response['status'], 'Cliente create form');
        $this->assertNoPhpErrors($response['body'], 'Cliente create form');
    }

    // ══════════════════════════════════════════════════════════════
    // Pedidos: listagem + pipeline
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function test_pedido_listagem_carrega(): void
    {
        $response = $this->httpGet('?page=orders');
        $this->assertStatusOk($response['status'], 'Pedido listagem');
        $this->assertNoPhpErrors($response['body'], 'Pedido listagem');
    }

    /** @test */
    public function test_pipeline_carrega(): void
    {
        $response = $this->httpGet('?page=pipeline');
        $this->assertStatusOk($response['status'], 'Pipeline');
        $this->assertNoPhpErrors($response['body'], 'Pipeline');
    }

    // ══════════════════════════════════════════════════════════════
    // Estoque: listagem
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function test_estoque_listagem_carrega(): void
    {
        $response = $this->httpGet('?page=stock');
        $this->assertStatusOk($response['status'], 'Estoque listagem');
        $this->assertNoPhpErrors($response['body'], 'Estoque listagem');
    }

    // ══════════════════════════════════════════════════════════════
    // Financeiro: listagem
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function test_financeiro_listagem_carrega(): void
    {
        $response = $this->httpGet('?page=financial');
        $this->assertStatusOk($response['status'], 'Financeiro listagem');
        $this->assertNoPhpErrors($response['body'], 'Financeiro listagem');
    }
}
