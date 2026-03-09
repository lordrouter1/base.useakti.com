<?php
namespace Akti\Tests\Pages;

use Akti\Tests\TestCase;

/**
 * Testes do módulo Produtos.
 *
 * Executar: vendor/bin/phpunit tests/Pages/ProductTest.php
 */
class ProductTest extends TestCase
{
    /**
     * @test
     */
    public function listagem_produtos_carrega(): void
    {
        $response = $this->httpGet('?page=products', true);

        $this->assertStatusOk($response['status'], 'Produtos - Listagem');
        $this->assertNoPhpErrors($response['body'], 'Produtos - Listagem');
        $this->assertValidHtml($response['body'], 'Produtos - Listagem');
        $this->assertBodyContains(['<html'], $response['body'], 'Produtos - Listagem');
    }

    /**
     * @test
     */
    public function listagem_produtos_contem_tabela(): void
    {
        $response = $this->httpGet('?page=products', true);
        $body = $response['body'];

        // A listagem deve ter uma tabela ou grid de produtos
        $hasTable = stripos($body, '<table') !== false;
        $hasGrid  = stripos($body, 'card') !== false;

        $this->assertTrue(
            $hasTable || $hasGrid,
            'Listagem de Produtos não contém tabela ou grid'
        );
    }

    /**
     * @test
     */
    public function formulario_criacao_produto_carrega(): void
    {
        $response = $this->httpGet('?page=products&action=create', true);

        $this->assertStatusOk($response['status'], 'Produtos - Criar');
        $this->assertNoPhpErrors($response['body'], 'Produtos - Criar');
        $this->assertStringContainsStringIgnoringCase(
            '<form',
            $response['body'],
            'Formulário de criação de produto não contém tag <form>'
        );
    }

    /**
     * @test
     */
    public function categorias_carregam(): void
    {
        $response = $this->httpGet('?page=categories', true);

        $this->assertStatusOk($response['status'], 'Categorias');
        $this->assertNoPhpErrors($response['body'], 'Categorias');
        $this->assertBodyContains(['<html'], $response['body'], 'Categorias');
    }

    /**
     * @test
     */
    public function setores_carregam(): void
    {
        $response = $this->httpGet('?page=sectors', true);

        $this->assertStatusOk($response['status'], 'Setores');
        $this->assertNoPhpErrors($response['body'], 'Setores');
        $this->assertBodyContains(['<html'], $response['body'], 'Setores');
    }
}
