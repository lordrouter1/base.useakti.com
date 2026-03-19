<?php
namespace Akti\Tests\Pages;

use Akti\Tests\TestCase;

/**
 * Testes da página Home / Dashboard.
 *
 * Verifica:
 * - Carregamento correto da home
 * - Presença do menu lateral/navegação
 * - Informações do usuário logado
 * - Renderização dos widgets do dashboard
 * - Carregamento do perfil
 *
 * Executar: vendor/bin/phpunit tests/Pages/DashboardTest.php
 */
class DashboardTest extends TestCase
{
    /**
     * @test
     */
    public function home_carrega_corretamente(): void
    {
        $response = $this->httpGet('', true);

        $this->assertStatusOk($response['status'], 'Home');
        $this->assertNoPhpErrors($response['body'], 'Home');
        $this->assertValidHtml($response['body'], 'Home');
        $this->assertNotLoginPage($response['body'], 'Home');
        $this->assertBodyContains(['<html'], $response['body'], 'Home');
    }

    /**
     * @test
     */
    public function home_contem_menu_lateral(): void
    {
        $response = $this->httpGet('', true);
        $this->assertNotLoginPage($response['body'], 'Home (menu)');

        // O layout deve conter navegação (nav, sidebar, ou menu)
        $body = $response['body'];
        $hasNav     = stripos($body, '<nav') !== false;
        $hasSidebar = stripos($body, 'sidebar') !== false;
        $hasMenu    = stripos($body, 'menu') !== false;

        $this->assertTrue(
            $hasNav || $hasSidebar || $hasMenu,
            'Home não contém navegação (nav, sidebar ou menu)'
        );
    }

    /**
     * @test
     */
    public function home_contem_informacoes_do_usuario(): void
    {
        $response = $this->httpGet('', true);
        $this->assertNotLoginPage($response['body'], 'Home (user info)');

        $body = $response['body'];

        // A home deve exibir referência ao usuário logado (nome, perfil ou logout)
        $hasProfileLink = stripos($body, 'page=profile') !== false;
        $hasLogout      = stripos($body, 'logout') !== false;
        $hasUserName    = stripos($body, 'admin') !== false;

        $this->assertTrue(
            $hasProfileLink || $hasLogout || $hasUserName,
            'Home não contém link de perfil, botão de logout ou nome do usuário'
        );
    }

    /**
     * @test
     * Home renderiza os widgets do dashboard (pelo menos o header de saudação).
     */
    public function home_renderiza_widgets_do_dashboard(): void
    {
        $response = $this->httpGet('', true);
        $this->assertNotLoginPage($response['body'], 'Home (widgets)');

        $body = $response['body'];

        // Widget de saudação contém id="home-header"
        $hasHeader = stripos($body, 'home-header') !== false;
        // Widget de cards tem id="home-cards-summary"
        $hasCards = stripos($body, 'home-cards-summary') !== false;

        $this->assertTrue(
            $hasHeader || $hasCards,
            'Home deve renderizar ao menos o widget de saudação ou cards de resumo'
        );
    }

    /**
     * @test
     * Home renderiza widget de pipeline se visível para o grupo.
     */
    public function home_contem_widget_pipeline(): void
    {
        $response = $this->httpGet('', true);
        $this->assertNotLoginPage($response['body'], 'Home (pipeline)');

        // O widget de pipeline deve ter id="home-pipeline" ou texto "Pipeline"
        $body = $response['body'];
        $hasPipeline = stripos($body, 'home-pipeline') !== false
                    || stripos($body, 'Pipeline') !== false;

        $this->assertTrue(
            $hasPipeline,
            'Home deve conter widget de Pipeline (ou o Pipeline está oculto neste grupo)'
        );
    }

    /**
     * @test
     * Home não contém erros PHP mesmo com widgets dinâmicos.
     */
    public function home_sem_erros_php_com_widgets(): void
    {
        $response = $this->httpGet('', true);

        $this->assertNoPhpErrors($response['body'], 'Home (widgets dinâmicos)');
    }

    /**
     * @test
     * Home contém container principal.
     */
    public function home_contem_container_principal(): void
    {
        $response = $this->httpGet('', true);
        $this->assertNotLoginPage($response['body'], 'Home (container)');

        $this->assertBodyContains(
            ['container-fluid'],
            $response['body'],
            'Home (container)'
        );
    }

    /**
     * @test
     */
    public function perfil_carrega_corretamente(): void
    {
        $response = $this->httpGet('?page=profile', true);

        $this->assertStatusOk($response['status'], 'Perfil');
        $this->assertNoPhpErrors($response['body'], 'Perfil');
        $this->assertValidHtml($response['body'], 'Perfil');
        $this->assertNotLoginPage($response['body'], 'Perfil');
    }
}
