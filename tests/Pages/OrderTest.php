<?php
namespace Akti\Tests\Pages;

use Akti\Tests\TestCase;

/**
 * Testes dos módulos Pedidos e Clientes.
 *
 * Executar: vendor/bin/phpunit tests/Pages/OrderTest.php
 */
class OrderTest extends TestCase
{
    /**
     * @test
     */
    public function listagem_pedidos_carrega(): void
    {
        $response = $this->httpGet('?page=orders', true);

        $this->assertStatusOk($response['status'], 'Pedidos - Listagem');
        $this->assertNoPhpErrors($response['body'], 'Pedidos - Listagem');
        $this->assertValidHtml($response['body'], 'Pedidos - Listagem');
        $this->assertBodyContains(['<html'], $response['body'], 'Pedidos - Listagem');
    }

    /**
     * @test
     */
    public function formulario_criacao_pedido_carrega(): void
    {
        $response = $this->httpGet('?page=orders&action=create', true);

        $this->assertStatusOk($response['status'], 'Pedidos - Criar');
        $this->assertNoPhpErrors($response['body'], 'Pedidos - Criar');
        $this->assertStringContainsStringIgnoringCase(
            '<form',
            $response['body'],
            'Formulário de criação de pedido não contém tag <form>'
        );
    }

    /**
     * @test
     */
    public function agenda_contatos_carrega(): void
    {
        $response = $this->httpGet('?page=orders&action=agenda', true);

        $this->assertStatusOk($response['status'], 'Agenda de Contatos');
        $this->assertNoPhpErrors($response['body'], 'Agenda de Contatos');
    }

    /**
     * @test
     */
    public function relatorio_pedidos_carrega(): void
    {
        $response = $this->httpGet('?page=orders&action=report', true);

        $this->assertStatusOk($response['status'], 'Relatório de Pedidos');
        $this->assertNoPhpErrors($response['body'], 'Relatório de Pedidos');
    }

    /**
     * @test
     */
    public function listagem_clientes_carrega(): void
    {
        $response = $this->httpGet('?page=customers', true);

        $this->assertStatusOk($response['status'], 'Clientes - Listagem');
        $this->assertNoPhpErrors($response['body'], 'Clientes - Listagem');
        $this->assertBodyContains(['<html'], $response['body'], 'Clientes - Listagem');
    }

    /**
     * @test
     */
    public function formulario_criacao_cliente_carrega(): void
    {
        $response = $this->httpGet('?page=customers&action=create', true);

        $this->assertStatusOk($response['status'], 'Clientes - Criar');
        $this->assertNoPhpErrors($response['body'], 'Clientes - Criar');
        $this->assertStringContainsStringIgnoringCase(
            '<form',
            $response['body'],
            'Formulário de criação de cliente não contém tag <form>'
        );
    }
}
