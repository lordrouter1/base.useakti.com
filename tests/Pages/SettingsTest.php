<?php
namespace Akti\Tests\Pages;

use Akti\Tests\TestCase;

/**
 * Testes do módulo Configurações.
 *
 * Executar: vendor/bin/phpunit tests/Pages/SettingsTest.php
 */
class SettingsTest extends TestCase
{
    /**
     * @test
     */
    public function configuracoes_carregam(): void
    {
        $response = $this->httpGet('?page=settings', true);

        $this->assertStatusOk($response['status'], 'Configurações');
        $this->assertNoPhpErrors($response['body'], 'Configurações');
        $this->assertValidHtml($response['body'], 'Configurações');
        $this->assertBodyContains(['<html'], $response['body'], 'Configurações');
    }

    /**
     * @test
     */
    public function configuracoes_contem_formulario(): void
    {
        $response = $this->httpGet('?page=settings', true);

        $this->assertStringContainsStringIgnoringCase(
            '<form',
            $response['body'],
            'Configurações não contém formulário'
        );
    }

    /**
     * @test
     */
    public function tabelas_preco_carregam(): void
    {
        $response = $this->httpGet('?page=price_tables', true);

        $this->assertStatusOk($response['status'], 'Tabelas de Preço');
        $this->assertNoPhpErrors($response['body'], 'Tabelas de Preço');
        $this->assertBodyContains(['<html'], $response['body'], 'Tabelas de Preço');
    }
}
