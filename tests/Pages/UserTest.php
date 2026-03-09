<?php
namespace Akti\Tests\Pages;

use Akti\Tests\TestCase;

/**
 * Testes do módulo Usuários (Admin).
 *
 * Executar: vendor/bin/phpunit tests/Pages/UserTest.php
 */
class UserTest extends TestCase
{
    /**
     * @test
     */
    public function listagem_usuarios_carrega(): void
    {
        $response = $this->httpGet('?page=users', true);

        $this->assertStatusOk($response['status'], 'Usuários - Listagem');
        $this->assertNoPhpErrors($response['body'], 'Usuários - Listagem');
        $this->assertValidHtml($response['body'], 'Usuários - Listagem');
        $this->assertBodyContains(['<html'], $response['body'], 'Usuários - Listagem');
    }

    /**
     * @test
     */
    public function formulario_criacao_usuario_carrega(): void
    {
        $response = $this->httpGet('?page=users&action=create', true);

        $this->assertStatusOk($response['status'], 'Usuários - Criar');
        $this->assertNoPhpErrors($response['body'], 'Usuários - Criar');
        $this->assertStringContainsStringIgnoringCase(
            '<form',
            $response['body'],
            'Formulário de criação de usuário não contém tag <form>'
        );
    }

    /**
     * @test
     */
    public function grupos_permissao_carregam(): void
    {
        $response = $this->httpGet('?page=users&action=groups', true);

        $this->assertStatusOk($response['status'], 'Grupos de Permissão');
        $this->assertNoPhpErrors($response['body'], 'Grupos de Permissão');
        $this->assertBodyContains(['<html'], $response['body'], 'Grupos de Permissão');
    }

    /**
     * @test
     */
    public function login_carrega_sem_autenticacao(): void
    {
        $response = $this->httpGet('?page=login', false);

        $this->assertStatusOk($response['status'], 'Login');
        $this->assertNoPhpErrors($response['body'], 'Login');

        $body = $response['body'];
        $this->assertStringContainsStringIgnoringCase('email', $body, 'Login não contém campo email');
        $this->assertStringContainsStringIgnoringCase('password', $body, 'Login não contém campo password');
        $this->assertStringContainsStringIgnoringCase('<form', $body, 'Login não contém formulário');
    }
}
