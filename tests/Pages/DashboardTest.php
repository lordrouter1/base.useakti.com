<?php
namespace Akti\Tests\Pages;

use Akti\Tests\TestCase;

/**
 * Testes da página Home / Dashboard.
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
