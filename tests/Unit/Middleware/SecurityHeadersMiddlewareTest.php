<?php
namespace Akti\Tests\Unit\Middleware;

use PHPUnit\Framework\TestCase;
use Akti\Middleware\SecurityHeadersMiddleware;

/**
 * Testes unitários para Akti\Middleware\SecurityHeadersMiddleware.
 *
 * Cobre:
 * - Verificação de HTTPS via $_SERVER (direta e por proxy)
 * - Existência dos métodos públicos
 * - Estrutura da classe
 *
 * Nota: Testar headers reais requer output buffering/runInSeparateProcess,
 * então focamos em testar a lógica interna e a estrutura.
 *
 * @package Akti\Tests\Unit\Middleware
 */
class SecurityHeadersMiddlewareTest extends TestCase
{
    private array $backupServer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->backupServer = $_SERVER;
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->backupServer;
        parent::tearDown();
    }

    // ══════════════════════════════════════════════════════════════
    // Estrutura da classe
    // ══════════════════════════════════════════════════════════════

    public function testHandleMethodExists(): void
    {
        $this->assertTrue(
            method_exists(SecurityHeadersMiddleware::class, 'handle'),
            'SecurityHeadersMiddleware deve ter método estático handle()'
        );
    }

    public function testHandleIsStatic(): void
    {
        $ref = new \ReflectionMethod(SecurityHeadersMiddleware::class, 'handle');
        $this->assertTrue($ref->isStatic(), 'handle() deve ser estático');
    }

    public function testHandleIsPublic(): void
    {
        $ref = new \ReflectionMethod(SecurityHeadersMiddleware::class, 'handle');
        $this->assertTrue($ref->isPublic(), 'handle() deve ser público');
    }

    // ══════════════════════════════════════════════════════════════
    // isHttps() — lógica interna via reflection
    // ══════════════════════════════════════════════════════════════

    public function testIsHttpsViaServerVar(): void
    {
        $_SERVER['HTTPS'] = 'on';
        unset($_SERVER['HTTP_X_FORWARDED_PROTO']);
        $_SERVER['SERVER_PORT'] = '443';

        $result = $this->callIsHttps();
        $this->assertTrue($result, 'HTTPS=on deve retornar true');
    }

    public function testIsHttpsOffReturnsFalse(): void
    {
        $_SERVER['HTTPS'] = 'off';
        unset($_SERVER['HTTP_X_FORWARDED_PROTO']);
        $_SERVER['SERVER_PORT'] = '80';

        $result = $this->callIsHttps();
        $this->assertFalse($result, 'HTTPS=off sem proxy deve retornar false');
    }

    public function testIsHttpsViaProxy(): void
    {
        $_SERVER['HTTPS'] = 'off';
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $_SERVER['SERVER_PORT'] = '80';

        $result = $this->callIsHttps();
        $this->assertTrue($result, 'X-Forwarded-Proto=https deve retornar true');
    }

    public function testIsHttpsViaPort443(): void
    {
        unset($_SERVER['HTTPS']);
        unset($_SERVER['HTTP_X_FORWARDED_PROTO']);
        $_SERVER['SERVER_PORT'] = '443';

        $result = $this->callIsHttps();
        $this->assertTrue($result, 'SERVER_PORT=443 deve retornar true');
    }

    public function testIsHttpsPort80ReturnsFalse(): void
    {
        unset($_SERVER['HTTPS']);
        unset($_SERVER['HTTP_X_FORWARDED_PROTO']);
        $_SERVER['SERVER_PORT'] = '80';

        $result = $this->callIsHttps();
        $this->assertFalse($result, 'SERVER_PORT=80 deve retornar false');
    }

    public function testIsHttpsNoVarsReturnsFalse(): void
    {
        unset($_SERVER['HTTPS']);
        unset($_SERVER['HTTP_X_FORWARDED_PROTO']);
        unset($_SERVER['SERVER_PORT']);

        $result = $this->callIsHttps();
        $this->assertFalse($result, 'Sem variáveis de HTTPS deve retornar false');
    }

    // ══════════════════════════════════════════════════════════════
    // Helper
    // ══════════════════════════════════════════════════════════════

    /**
     * Chama o método privado isHttps() via reflection.
     */
    private function callIsHttps(): bool
    {
        $ref = new \ReflectionMethod(SecurityHeadersMiddleware::class, 'isHttps');
        $ref->setAccessible(true);
        return $ref->invoke(null);
    }
}
