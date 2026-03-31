<?php
namespace Akti\Tests\Unit\Middleware;

use PHPUnit\Framework\TestCase;
use Akti\Middleware\CsrfMiddleware;
use Akti\Core\Security;

/**
 * Testes unitários para Akti\Middleware\CsrfMiddleware.
 *
 * Cobre:
 * - Métodos GET/HEAD/OPTIONS não requerem CSRF
 * - Rotas isentas passam sem CSRF
 * - addExemptRoute() adiciona rota à lista
 * - getExemptRoutes() retorna lista de isenções
 * - extractToken() busca no POST e no header
 * - isExempt() verifica match exato e wildcard
 * - Métodos PROTECTED_METHODS requerem validação
 *
 * @package Akti\Tests\Unit\Middleware
 */
class CsrfMiddlewareTest extends TestCase
{
    private array $backupSession = [];
    private array $backupServer = [];
    private array $backupPost = [];
    private array $backupGet = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->backupSession = $_SESSION ?? [];
        $this->backupServer = $_SERVER;
        $this->backupPost = $_POST;
        $this->backupGet = $_GET;

        $_SESSION = [];
        $_POST = [];
        $_GET = ['page' => 'test', 'action' => 'index'];
    }

    protected function tearDown(): void
    {
        $_SESSION = $this->backupSession;
        $_SERVER = $this->backupServer;
        $_POST = $this->backupPost;
        $_GET = $this->backupGet;
        parent::tearDown();
    }

    // ══════════════════════════════════════════════════════════════
    // getExemptRoutes() e addExemptRoute()
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function getExemptRoutes_retorna_array(): void
    {
        $routes = CsrfMiddleware::getExemptRoutes();
        $this->assertIsArray($routes);
    }

    /** @test */
    public function getExemptRoutes_contem_rotas_de_catalogo(): void
    {
        $routes = CsrfMiddleware::getExemptRoutes();
        $this->assertContains('catalog:addToCart', $routes);
        $this->assertContains('catalog:removeFromCart', $routes);
        $this->assertContains('catalog:confirmQuote', $routes);
    }

    /** @test */
    public function addExemptRoute_adiciona_nova_rota(): void
    {
        $before = count(CsrfMiddleware::getExemptRoutes());
        CsrfMiddleware::addExemptRoute('test:uniqueRouteForTest_' . uniqid());
        $after = count(CsrfMiddleware::getExemptRoutes());

        $this->assertSame($before + 1, $after);
    }

    /** @test */
    public function addExemptRoute_nao_duplica_rota(): void
    {
        $uniqueRoute = 'test:noDuplicate_' . uniqid();
        CsrfMiddleware::addExemptRoute($uniqueRoute);
        $before = count(CsrfMiddleware::getExemptRoutes());

        CsrfMiddleware::addExemptRoute($uniqueRoute);
        $after = count(CsrfMiddleware::getExemptRoutes());

        $this->assertSame($before, $after, 'Rotas duplicadas não devem ser adicionadas');
    }

    // ══════════════════════════════════════════════════════════════
    // isExempt() via Reflection
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function isExempt_retorna_true_para_rota_isenta_exata(): void
    {
        $_GET['page'] = 'catalog';
        $_GET['action'] = 'addToCart';

        $method = new \ReflectionMethod(CsrfMiddleware::class, 'isExempt');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke(null));
    }

    /** @test */
    public function isExempt_retorna_false_para_rota_nao_isenta(): void
    {
        $_GET['page'] = 'products';
        $_GET['action'] = 'store';

        $method = new \ReflectionMethod(CsrfMiddleware::class, 'isExempt');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke(null));
    }

    /** @test */
    public function isExempt_aceita_wildcard(): void
    {
        // Adicionar rota wildcard para teste
        CsrfMiddleware::addExemptRoute('wildcard_test_' . uniqid() . ':*');

        // Extrair a rota adicionada para testar
        $routes = CsrfMiddleware::getExemptRoutes();
        $wildcardRoute = array_pop($routes);
        $page = explode(':', $wildcardRoute)[0];

        $_GET['page'] = $page;
        $_GET['action'] = 'qualquerAction';

        $method = new \ReflectionMethod(CsrfMiddleware::class, 'isExempt');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke(null));
    }

    // ══════════════════════════════════════════════════════════════
    // extractToken() via Reflection
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function extractToken_retorna_token_do_post(): void
    {
        $_POST['csrf_token'] = 'token_do_post_123';

        $method = new \ReflectionMethod(CsrfMiddleware::class, 'extractToken');
        $method->setAccessible(true);

        $this->assertSame('token_do_post_123', $method->invoke(null));
    }

    /** @test */
    public function extractToken_retorna_token_do_header(): void
    {
        $_POST = [];
        $_SERVER['HTTP_X_CSRF_TOKEN'] = 'token_do_header_456';

        $method = new \ReflectionMethod(CsrfMiddleware::class, 'extractToken');
        $method->setAccessible(true);

        $this->assertSame('token_do_header_456', $method->invoke(null));
    }

    /** @test */
    public function extractToken_prioriza_post_sobre_header(): void
    {
        $_POST['csrf_token'] = 'token_post';
        $_SERVER['HTTP_X_CSRF_TOKEN'] = 'token_header';

        $method = new \ReflectionMethod(CsrfMiddleware::class, 'extractToken');
        $method->setAccessible(true);

        $this->assertSame('token_post', $method->invoke(null));
    }

    /** @test */
    public function extractToken_retorna_null_sem_token(): void
    {
        $_POST = [];
        unset($_SERVER['HTTP_X_CSRF_TOKEN']);

        $method = new \ReflectionMethod(CsrfMiddleware::class, 'extractToken');
        $method->setAccessible(true);

        $this->assertNull($method->invoke(null));
    }

    // ══════════════════════════════════════════════════════════════
    // handle() — métodos seguros
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function handle_permite_get_sem_token(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        // Não deve lançar exceção nem chamar exit
        CsrfMiddleware::handle();
        $this->assertTrue(true, 'GET não requer CSRF');
    }

    /** @test */
    public function handle_permite_head_sem_token(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'HEAD';

        CsrfMiddleware::handle();
        $this->assertTrue(true, 'HEAD não requer CSRF');
    }

    /** @test */
    public function handle_permite_options_sem_token(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';

        CsrfMiddleware::handle();
        $this->assertTrue(true, 'OPTIONS não requer CSRF');
    }

    /** @test */
    public function handle_permite_post_para_rota_isenta(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET['page'] = 'catalog';
        $_GET['action'] = 'addToCart';

        // Deve passar sem validar CSRF
        CsrfMiddleware::handle();
        $this->assertTrue(true, 'Rota isenta não requer CSRF');
    }

    // ══════════════════════════════════════════════════════════════
    // Constantes via Reflection
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function protected_methods_inclui_post_put_patch_delete(): void
    {
        $ref = new \ReflectionClass(CsrfMiddleware::class);
        $const = $ref->getConstant('PROTECTED_METHODS');

        $this->assertContains('POST', $const);
        $this->assertContains('PUT', $const);
        $this->assertContains('PATCH', $const);
        $this->assertContains('DELETE', $const);
        $this->assertNotContains('GET', $const);
    }
}
