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

    // ══════════════════════════════════════════════════════════════
    // Dashboard Widgets (aba de configuração)
    // ══════════════════════════════════════════════════════════════

    /**
     * @test
     * Aba Dashboard Widgets carrega corretamente.
     */
    public function dashboard_widgets_tab_carrega(): void
    {
        $response = $this->httpGet('?page=settings&tab=dashboard', true);

        $this->assertStatusOk($response['status'], 'Settings > Dashboard Widgets');
        $this->assertNoPhpErrors($response['body'], 'Settings > Dashboard Widgets');
        $this->assertValidHtml($response['body'], 'Settings > Dashboard Widgets');
    }

    /**
     * @test
     * Aba Dashboard contém seletor de grupo.
     */
    public function dashboard_widgets_tab_contem_seletor_grupo(): void
    {
        $response = $this->httpGet('?page=settings&tab=dashboard', true);

        $this->assertBodyContains(
            ['dashGroupSelector', 'Grupo de Usuários'],
            $response['body'],
            'Settings > Dashboard (seletor)'
        );
    }

    /**
     * @test
     * Aba Dashboard contém link/tab "Dashboard" no menu de tabs.
     */
    public function settings_tabs_contem_dashboard(): void
    {
        $response = $this->httpGet('?page=settings&tab=dashboard', true);

        $this->assertBodyContains(
            ['tab=dashboard', 'Dashboard'],
            $response['body'],
            'Settings > Dashboard (tab link)'
        );
    }

    /**
     * @test
     * Aba Dashboard com grupo selecionado mostra lista de widgets.
     */
    public function dashboard_widgets_com_grupo_mostra_widgets(): void
    {
        // Primeiro, pegar a lista de grupos disponíveis
        $response = $this->httpGet('?page=settings&tab=dashboard', true);
        $this->assertStatusOk($response['status'], 'Settings > Dashboard (pré-grupo)');

        // Extrair primeiro group_id do select
        if (preg_match('/option value="(\d+)"/', $response['body'], $m)) {
            $groupId = $m[1];

            $response2 = $this->httpGet("?page=settings&tab=dashboard&group_id={$groupId}", true);
            $this->assertStatusOk($response2['status'], "Settings > Dashboard (grupo #{$groupId})");
            $this->assertNoPhpErrors($response2['body'], "Settings > Dashboard (grupo #{$groupId})");

            // Deve exibir a lista de widgets sortable
            $this->assertBodyContains(
                ['widgetSortable', 'widget-toggle', 'btnSaveWidgets'],
                $response2['body'],
                "Settings > Dashboard Widgets (grupo #{$groupId})"
            );
        } else {
            $this->markTestSkipped('Nenhum grupo de usuários encontrado para testar');
        }
    }

    // ══════════════════════════════════════════════════════════════
    // Aba Segurança
    // ══════════════════════════════════════════════════════════════

    /**
     * @test
     * Aba Segurança carrega sem erros.
     */
    public function seguranca_tab_carrega(): void
    {
        $response = $this->httpGet('?page=settings&tab=security', true);

        $this->assertStatusOk($response['status'], 'Settings > Segurança');
        $this->assertNoPhpErrors($response['body'], 'Settings > Segurança');
        $this->assertValidHtml($response['body'], 'Settings > Segurança');
    }

    // ══════════════════════════════════════════════════════════════
    // Aba Etapas de Preparo
    // ══════════════════════════════════════════════════════════════

    /**
     * @test
     * Aba Etapas de Preparo carrega sem erros.
     */
    public function preparacao_tab_carrega(): void
    {
        $response = $this->httpGet('?page=settings&tab=preparation', true);

        $this->assertStatusOk($response['status'], 'Settings > Preparação');
        $this->assertNoPhpErrors($response['body'], 'Settings > Preparação');
        $this->assertValidHtml($response['body'], 'Settings > Preparação');
    }
}
