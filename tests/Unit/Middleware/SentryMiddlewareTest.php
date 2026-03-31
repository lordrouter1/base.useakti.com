<?php
namespace Akti\Tests\Unit\Middleware;

use PHPUnit\Framework\TestCase;
use Akti\Middleware\SentryMiddleware;

/**
 * Testes unitários do SentryMiddleware.
 *
 * Verifica:
 * - Inicialização sem Sentry (modo fallback)
 * - Conversão de severidade PHP para níveis de log
 * - Detecção AJAX
 *
 * Executar: vendor/bin/phpunit tests/Unit/Middleware/SentryMiddlewareTest.php
 *
 * @package Akti\Tests\Unit\Middleware
 */
class SentryMiddlewareTest extends TestCase
{
    /** @test */
    public function severity_to_log_level_converte_corretamente(): void
    {
        $method = new \ReflectionMethod(SentryMiddleware::class, 'severityToLogLevel');
        $method->setAccessible(true);

        $this->assertSame('error', $method->invoke(null, E_ERROR));
        $this->assertSame('error', $method->invoke(null, E_USER_ERROR));
        $this->assertSame('warning', $method->invoke(null, E_WARNING));
        $this->assertSame('warning', $method->invoke(null, E_USER_WARNING));
        $this->assertSame('notice', $method->invoke(null, E_NOTICE));
        $this->assertSame('notice', $method->invoke(null, E_USER_NOTICE));
        $this->assertSame('info', $method->invoke(null, E_DEPRECATED));
    }

    /** @test */
    public function is_ajax_retorna_false_sem_header(): void
    {
        $method = new \ReflectionMethod(SentryMiddleware::class, 'isAjax');
        $method->setAccessible(true);

        // Clear the header
        unset($_SERVER['HTTP_X_REQUESTED_WITH']);

        $this->assertFalse($method->invoke(null));
    }

    /** @test */
    public function is_ajax_retorna_true_com_header(): void
    {
        $method = new \ReflectionMethod(SentryMiddleware::class, 'isAjax');
        $method->setAccessible(true);

        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

        $result = $method->invoke(null);

        // Cleanup
        unset($_SERVER['HTTP_X_REQUESTED_WITH']);

        $this->assertTrue($result);
    }

    /** @test */
    public function classe_existe_e_tem_metodos_necessarios(): void
    {
        $this->assertTrue(method_exists(SentryMiddleware::class, 'init'));
        $this->assertTrue(method_exists(SentryMiddleware::class, 'handleException'));
        $this->assertTrue(method_exists(SentryMiddleware::class, 'handleError'));
        $this->assertTrue(method_exists(SentryMiddleware::class, 'handleShutdown'));
        $this->assertTrue(method_exists(SentryMiddleware::class, 'setUserContext'));
        $this->assertTrue(method_exists(SentryMiddleware::class, 'addBreadcrumb'));
    }
}
