<?php
namespace Akti\Tests\Unit\Middleware;

use PHPUnit\Framework\TestCase;
use Akti\Middleware\RateLimitMiddleware;

/**
 * Testes unitários para Akti\Middleware\RateLimitMiddleware.
 *
 * Cobre:
 * - Session-based rate limiting (check())
 * - Intervalo mínimo entre ações
 * - Permitir após expiração do intervalo
 * - Retorno de retry_after correto
 * - Diferentes ações não interferem entre si
 * - Diferentes usuários não interferem entre si
 *
 * @package Akti\Tests\Unit\Middleware
 */
class RateLimitMiddlewareTest extends TestCase
{
    private array $backupSession;

    protected function setUp(): void
    {
        parent::setUp();
        $this->backupSession = $_SESSION ?? [];
        $_SESSION = ['user_id' => 1];
    }

    protected function tearDown(): void
    {
        $_SESSION = $this->backupSession;
        parent::tearDown();
    }

    // ══════════════════════════════════════════════════════════════
    // check() — Rate limiting via sessão
    // ══════════════════════════════════════════════════════════════

    public function testFirstCallIsAllowed(): void
    {
        $result = RateLimitMiddleware::check('test_action', 5);

        $this->assertTrue($result['allowed'], 'Primeira chamada deve ser permitida');
        $this->assertSame(0, $result['retry_after']);
    }

    public function testSecondCallWithinIntervalIsBlocked(): void
    {
        RateLimitMiddleware::check('test_block', 60);
        $result = RateLimitMiddleware::check('test_block', 60);

        $this->assertFalse($result['allowed'], 'Segunda chamada dentro do intervalo deve ser bloqueada');
        $this->assertGreaterThan(0, $result['retry_after']);
    }

    public function testCallAfterIntervalExpiredIsAllowed(): void
    {
        // Simula um rate limit no passado
        $_SESSION['rate_limit_test_expired_1'] = time() - 100;

        $result = RateLimitMiddleware::check('test_expired', 5);
        $this->assertTrue($result['allowed'], 'Chamada após intervalo expirado deve ser permitida');
    }

    public function testRetryAfterIsCorrect(): void
    {
        // Simula rate limit 2 segundos atrás, com intervalo de 10s
        $_SESSION['rate_limit_test_retry_1'] = time() - 2;

        $result = RateLimitMiddleware::check('test_retry', 10);
        $this->assertFalse($result['allowed']);
        $this->assertSame(8, $result['retry_after']);
    }

    public function testDifferentActionsDoNotInterfere(): void
    {
        RateLimitMiddleware::check('action_a', 60);

        $result = RateLimitMiddleware::check('action_b', 60);
        $this->assertTrue($result['allowed'], 'Diferentes ações não devem interferir');
    }

    public function testDifferentUsersDoNotInterfere(): void
    {
        $_SESSION['user_id'] = 1;
        RateLimitMiddleware::check('shared_action', 60);

        $_SESSION['user_id'] = 2;
        $result = RateLimitMiddleware::check('shared_action', 60);
        $this->assertTrue($result['allowed'], 'Diferentes usuários não devem interferir');
    }

    public function testNoUserIdUsesZero(): void
    {
        unset($_SESSION['user_id']);
        $result = RateLimitMiddleware::check('no_user_action', 5);
        $this->assertTrue($result['allowed'], 'Sem user_id, deve usar 0 como fallback');
    }

    public function testDefaultMinIntervalIs5Seconds(): void
    {
        // Verificar que o padrão é 5 segundos
        $ref = new \ReflectionMethod(RateLimitMiddleware::class, 'check');
        $params = $ref->getParameters();

        $this->assertSame('minInterval', $params[1]->getName());
        $this->assertTrue($params[1]->isDefaultValueAvailable());
        $this->assertSame(5, $params[1]->getDefaultValue());
    }

    public function testReturnStructure(): void
    {
        $result = RateLimitMiddleware::check('struct_test', 5);

        $this->assertArrayHasKey('allowed', $result);
        $this->assertArrayHasKey('retry_after', $result);
        $this->assertIsBool($result['allowed']);
        $this->assertIsInt($result['retry_after']);
    }

    public function testZeroIntervalAlwaysAllows(): void
    {
        RateLimitMiddleware::check('zero_interval', 0);
        $result = RateLimitMiddleware::check('zero_interval', 0);

        $this->assertTrue($result['allowed'], 'Intervalo 0 deve sempre permitir');
    }

    // ══════════════════════════════════════════════════════════════
    // checkWithDb() — Estrutura
    // ══════════════════════════════════════════════════════════════

    public function testCheckWithDbHasExpectedSignature(): void
    {
        $ref = new \ReflectionMethod(RateLimitMiddleware::class, 'checkWithDb');
        $params = $ref->getParameters();

        $this->assertSame('db', $params[0]->getName());
        $this->assertSame('action', $params[1]->getName());
        $this->assertSame('minInterval', $params[2]->getName());
        $this->assertSame('maxPerMinute', $params[3]->getName());
    }

    public function testCheckWithDbRequiresUserId(): void
    {
        $_SESSION = []; // No user_id
        $pdo = $this->createMock(\PDO::class);

        $result = RateLimitMiddleware::checkWithDb($pdo, 'test', 5, 10);
        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('inválida', $result['message'] ?? '');
    }

    // ══════════════════════════════════════════════════════════════
    // cleanup() — Estrutura
    // ══════════════════════════════════════════════════════════════

    public function testCleanupMethodExists(): void
    {
        $this->assertTrue(
            method_exists(RateLimitMiddleware::class, 'cleanup'),
            'RateLimitMiddleware deve ter método cleanup()'
        );
    }
}
