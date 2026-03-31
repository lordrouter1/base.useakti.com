<?php
namespace Akti\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Akti\Core\Security;

/**
 * Testes unitários para Akti\Core\Security — Proteção CSRF.
 *
 * Cobre:
 * - Geração de token CSRF criptograficamente seguro
 * - Reutilização de token válido (dentro do lifetime)
 * - Rotação de token (após expiração)
 * - Grace period para token anterior
 * - Validação com hash_equals (timing-safe)
 * - Token nulo ou vazio retorna false
 * - getToken() retorna token da sessão
 * - getClientIp() resolve proxy headers
 * - isAjaxRequest() detecta requisições AJAX
 * - logCsrfFailure() registra falha no log
 *
 * @package Akti\Tests\Unit\Core
 */
class SecurityTest extends TestCase
{
    private array $backupSession = [];
    private array $backupServer = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->backupSession = $_SESSION ?? [];
        $this->backupServer = $_SERVER;
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = $this->backupSession;
        $_SERVER = $this->backupServer;
        parent::tearDown();
    }

    // ══════════════════════════════════════════════════════════════
    // generateCsrfToken()
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function generateCsrfToken_retorna_string_64_caracteres_hex(): void
    {
        $token = Security::generateCsrfToken();

        $this->assertIsString($token);
        $this->assertSame(64, strlen($token), 'Token deve ter 64 caracteres (32 bytes hex)');
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token, 'Token deve ser hexadecimal');
    }

    /** @test */
    public function generateCsrfToken_armazena_na_sessao(): void
    {
        $token = Security::generateCsrfToken();

        $this->assertSame($token, $_SESSION['csrf_token']);
        $this->assertArrayHasKey('csrf_token_time', $_SESSION);
        $this->assertIsInt($_SESSION['csrf_token_time']);
    }

    /** @test */
    public function generateCsrfToken_reutiliza_token_valido(): void
    {
        $token1 = Security::generateCsrfToken();
        $token2 = Security::generateCsrfToken();

        $this->assertSame($token1, $token2, 'Token não expirado deve ser reutilizado');
    }

    /** @test */
    public function generateCsrfToken_gera_novo_apos_expiracao(): void
    {
        $token1 = Security::generateCsrfToken();

        // Simular expiração do token (mais de 30 minutos)
        $_SESSION['csrf_token_time'] = time() - 1801;

        $token2 = Security::generateCsrfToken();

        $this->assertNotSame($token1, $token2, 'Token expirado deve gerar novo');
    }

    /** @test */
    public function generateCsrfToken_salva_anterior_como_grace(): void
    {
        $token1 = Security::generateCsrfToken();

        // Simular expiração
        $_SESSION['csrf_token_time'] = time() - 1801;

        Security::generateCsrfToken();

        $this->assertSame($token1, $_SESSION['csrf_token_previous'], 'Token anterior deve ser salvo como grace');
        $this->assertArrayHasKey('csrf_token_previous_time', $_SESSION);
    }

    /** @test */
    public function tokens_gerados_sao_unicos(): void
    {
        $tokens = [];
        for ($i = 0; $i < 10; $i++) {
            $_SESSION = []; // Forçar geração de novo token a cada iteração
            $tokens[] = Security::generateCsrfToken();
        }

        $unique = array_unique($tokens);
        $this->assertCount(10, $unique, 'Todos os tokens devem ser únicos');
    }

    // ══════════════════════════════════════════════════════════════
    // getToken()
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function getToken_retorna_null_sem_sessao(): void
    {
        $this->assertNull(Security::getToken());
    }

    /** @test */
    public function getToken_retorna_token_da_sessao(): void
    {
        $token = Security::generateCsrfToken();
        $this->assertSame($token, Security::getToken());
    }

    // ══════════════════════════════════════════════════════════════
    // validateCsrfToken()
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function validateCsrfToken_retorna_false_para_null(): void
    {
        Security::generateCsrfToken();
        $this->assertFalse(Security::validateCsrfToken(null));
    }

    /** @test */
    public function validateCsrfToken_retorna_false_para_string_vazia(): void
    {
        Security::generateCsrfToken();
        $this->assertFalse(Security::validateCsrfToken(''));
    }

    /** @test */
    public function validateCsrfToken_retorna_false_para_token_invalido(): void
    {
        Security::generateCsrfToken();
        $this->assertFalse(Security::validateCsrfToken('invalidtoken123'));
    }

    /** @test */
    public function validateCsrfToken_retorna_true_para_token_valido(): void
    {
        $token = Security::generateCsrfToken();
        $this->assertTrue(Security::validateCsrfToken($token));
    }

    /** @test */
    public function validateCsrfToken_aceita_token_anterior_dentro_grace_period(): void
    {
        $token1 = Security::generateCsrfToken();

        // Simular expiração e geração de novo token
        $_SESSION['csrf_token_time'] = time() - 1801;
        Security::generateCsrfToken();

        // Token anterior deve ser aceito dentro do grace period
        $this->assertTrue(
            Security::validateCsrfToken($token1),
            'Token anterior deve ser aceito dentro do grace period'
        );
    }

    /** @test */
    public function validateCsrfToken_rejeita_token_anterior_apos_grace_period(): void
    {
        $token1 = Security::generateCsrfToken();

        // Simular que o token anterior expirou além do grace period (30min + 5min = 2100s)
        $_SESSION['csrf_token_time'] = time() - 1801;
        Security::generateCsrfToken();

        // Forçar o previous_time para muito atrás
        $_SESSION['csrf_token_previous_time'] = time() - 2200;

        $this->assertFalse(
            Security::validateCsrfToken($token1),
            'Token anterior deve ser rejeitado após grace period'
        );
    }

    /** @test */
    public function validateCsrfToken_sem_token_na_sessao_retorna_false(): void
    {
        $_SESSION = [];
        $this->assertFalse(Security::validateCsrfToken('qualquer_token'));
    }

    // ══════════════════════════════════════════════════════════════
    // logCsrfFailure()
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function logCsrfFailure_nao_lanca_excecao(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET['page'] = 'test';
        $_GET['action'] = 'store';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        // Não deve lançar exceção
        Security::logCsrfFailure('abc123token');
        $this->assertTrue(true);
    }

    /** @test */
    public function logCsrfFailure_com_token_null(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        Security::logCsrfFailure(null);
        $this->assertTrue(true);
    }

    // ══════════════════════════════════════════════════════════════
    // getClientIp() via Reflection
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function getClientIp_retorna_remote_addr(): void
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';
        unset(
            $_SERVER['HTTP_CF_CONNECTING_IP'],
            $_SERVER['HTTP_X_FORWARDED_FOR'],
            $_SERVER['HTTP_X_REAL_IP']
        );

        $method = new \ReflectionMethod(Security::class, 'getClientIp');
        $method->setAccessible(true);

        $this->assertSame('192.168.1.1', $method->invoke(null));
    }

    /** @test */
    public function getClientIp_prioriza_cloudflare_header(): void
    {
        $_SERVER['HTTP_CF_CONNECTING_IP'] = '1.2.3.4';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '5.6.7.8';
        $_SERVER['HTTP_X_REAL_IP'] = '9.10.11.12';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        $method = new \ReflectionMethod(Security::class, 'getClientIp');
        $method->setAccessible(true);

        $this->assertSame('1.2.3.4', $method->invoke(null));
    }

    /** @test */
    public function getClientIp_extrai_primeiro_ip_de_forwarded_for(): void
    {
        unset($_SERVER['HTTP_CF_CONNECTING_IP']);
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '10.0.0.1, 172.16.0.1, 192.168.0.1';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        $method = new \ReflectionMethod(Security::class, 'getClientIp');
        $method->setAccessible(true);

        $this->assertSame('10.0.0.1', $method->invoke(null));
    }

    /** @test */
    public function getClientIp_retorna_fallback_sem_headers(): void
    {
        unset(
            $_SERVER['HTTP_CF_CONNECTING_IP'],
            $_SERVER['HTTP_X_FORWARDED_FOR'],
            $_SERVER['HTTP_X_REAL_IP'],
            $_SERVER['REMOTE_ADDR']
        );

        $method = new \ReflectionMethod(Security::class, 'getClientIp');
        $method->setAccessible(true);

        $this->assertSame('0.0.0.0', $method->invoke(null));
    }

    // ══════════════════════════════════════════════════════════════
    // isAjaxRequest() via Reflection
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function isAjaxRequest_detecta_xmlhttprequest(): void
    {
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        unset($_SERVER['HTTP_ACCEPT'], $_SERVER['CONTENT_TYPE']);

        $method = new \ReflectionMethod(Security::class, 'isAjaxRequest');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke(null));
    }

    /** @test */
    public function isAjaxRequest_detecta_accept_json(): void
    {
        unset($_SERVER['HTTP_X_REQUESTED_WITH']);
        $_SERVER['HTTP_ACCEPT'] = 'application/json';
        unset($_SERVER['CONTENT_TYPE']);

        $method = new \ReflectionMethod(Security::class, 'isAjaxRequest');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke(null));
    }

    /** @test */
    public function isAjaxRequest_detecta_content_type_json(): void
    {
        unset($_SERVER['HTTP_X_REQUESTED_WITH'], $_SERVER['HTTP_ACCEPT']);
        $_SERVER['CONTENT_TYPE'] = 'application/json';

        $method = new \ReflectionMethod(Security::class, 'isAjaxRequest');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke(null));
    }

    /** @test */
    public function isAjaxRequest_retorna_false_sem_indicadores(): void
    {
        unset(
            $_SERVER['HTTP_X_REQUESTED_WITH'],
            $_SERVER['HTTP_ACCEPT'],
            $_SERVER['CONTENT_TYPE']
        );

        $method = new \ReflectionMethod(Security::class, 'isAjaxRequest');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke(null));
    }

    /** @test */
    public function isAjaxRequest_retorna_false_para_html_accept(): void
    {
        unset($_SERVER['HTTP_X_REQUESTED_WITH'], $_SERVER['CONTENT_TYPE']);
        $_SERVER['HTTP_ACCEPT'] = 'text/html';

        $method = new \ReflectionMethod(Security::class, 'isAjaxRequest');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke(null));
    }

    // ══════════════════════════════════════════════════════════════
    // Constantes (via Reflection)
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function token_lifetime_e_1800_segundos(): void
    {
        $ref = new \ReflectionClass(Security::class);
        $this->assertSame(1800, $ref->getConstant('TOKEN_LIFETIME'));
    }

    /** @test */
    public function token_grace_period_e_300_segundos(): void
    {
        $ref = new \ReflectionClass(Security::class);
        $this->assertSame(300, $ref->getConstant('TOKEN_GRACE_PERIOD'));
    }
}
