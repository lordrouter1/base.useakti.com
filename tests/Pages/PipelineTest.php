<?php
namespace Akti\Tests\Pages;

use Akti\Tests\TestCase;

/**
 * Testes do módulo Pipeline (Linha de Produção).
 *
 * Executar: vendor/bin/phpunit tests/Pages/PipelineTest.php
 */
class PipelineTest extends TestCase
{
    /**
     * @test
     */
    public function kanban_carrega(): void
    {
        $response = $this->httpGet('?page=pipeline', true);

        $this->assertStatusOk($response['status'], 'Pipeline - Kanban');
        $this->assertNoPhpErrors($response['body'], 'Pipeline - Kanban');
        $this->assertValidHtml($response['body'], 'Pipeline - Kanban');
    }

    /**
     * @test
     */
    public function pipeline_settings_carrega(): void
    {
        $response = $this->httpGet('?page=pipeline&action=settings', true);

        $this->assertStatusOk($response['status'], 'Pipeline - Settings');
        $this->assertNoPhpErrors($response['body'], 'Pipeline - Settings');
        $this->assertBodyContains(['Meta'], $response['body'], 'Pipeline - Settings');
    }

    /**
     * @test
     */
    public function painel_producao_carrega(): void
    {
        $response = $this->httpGet('?page=production_board', true);

        $this->assertStatusOk($response['status'], 'Painel de Produção');
        $this->assertNoPhpErrors($response['body'], 'Painel de Produção');
    }
}
