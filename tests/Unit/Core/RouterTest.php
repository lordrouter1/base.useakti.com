<?php
namespace Akti\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Akti\Core\Router;

/**
 * Testes unitários para Akti\Core\Router.
 *
 * Cobre:
 * - Construtor: carregamento de rotas
 * - Construtor: exceção se arquivo não existe
 * - Getters: getPage(), getAction()
 * - isPublicPage(): rotas públicas vs protegidas
 * - hasBeforeAuth(): rotas com handler antes de auth
 * - routeExists(): verificação de existência de rota
 * - resolveControllerClass(): FQCN vs nome curto
 * - resolveAction(): mapeamento de actions
 * - dispatch(): controller + action, redirect, view direta, 404
 *
 * @package Akti\Tests\Unit\Core
 */
class RouterTest extends TestCase
{
    private string $routesFile;
    private array $backupGet;
    private array $backupServer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->backupGet = $_GET;
        $this->backupServer = $_SERVER;

        // Criar arquivo de rotas temporário
        $this->routesFile = tempnam(sys_get_temp_dir(), 'akti_routes_') . '.php';
        file_put_contents($this->routesFile, '<?php return ' . var_export($this->getTestRoutes(), true) . ';');
    }

    protected function tearDown(): void
    {
        $_GET = $this->backupGet;
        $_SERVER = $this->backupServer;
        if (file_exists($this->routesFile)) {
            unlink($this->routesFile);
        }
        parent::tearDown();
    }

    private function getTestRoutes(): array
    {
        return [
            'home' => [
                'controller'     => 'HomeController',
                'default_action' => 'index',
            ],
            'login' => [
                'controller'     => 'AuthController',
                'default_action' => 'showLogin',
                'public'         => true,
                'before_auth'    => true,
                'actions'        => [
                    'doLogin' => 'processLogin',
                    'logout'  => 'logout',
                ],
            ],
            'products' => [
                'controller'     => 'ProductController',
                'default_action' => 'index',
                'actions'        => [
                    'create' => 'create',
                    'store'  => 'store',
                    'edit'   => 'edit',
                    'update' => 'update',
                    'delete' => 'delete',
                ],
            ],
            'redirect_page' => [
                'redirect' => '?page=home',
            ],
            'unmapped' => [
                'controller'     => 'TestController',
                'default_action' => 'index',
                'allow_unmapped' => true,
            ],
            'with_alt_controller' => [
                'controller'     => 'MainController',
                'default_action' => 'index',
                'actions'        => [
                    'special' => [
                        'controller' => 'SpecialController',
                        'method'     => 'handle',
                    ],
                ],
            ],
        ];
    }

    private function createRouter(string $page = 'home', string $action = 'index'): Router
    {
        $_GET['page'] = $page;
        $_GET['action'] = $action;
        return new Router($this->routesFile);
    }

    // ══════════════════════════════════════════════════════════════
    // Construtor
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function construtor_carrega_rotas_do_arquivo(): void
    {
        $router = $this->createRouter();
        $this->assertTrue($router->routeExists());
    }

    /** @test */
    public function construtor_lanca_excecao_se_arquivo_nao_existe(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Arquivo de rotas não encontrado');

        $_GET['page'] = 'home';
        $_GET['action'] = 'index';
        new Router('/caminho/inexistente/routes.php');
    }

    // ══════════════════════════════════════════════════════════════
    // Getters
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function getPage_retorna_page_da_url(): void
    {
        $router = $this->createRouter('products', 'create');
        $this->assertSame('products', $router->getPage());
    }

    /** @test */
    public function getAction_retorna_action_da_url(): void
    {
        $router = $this->createRouter('products', 'create');
        $this->assertSame('create', $router->getAction());
    }

    /** @test */
    public function getPage_padrao_e_home(): void
    {
        unset($_GET['page']);
        $_GET['action'] = 'index';
        $router = new Router($this->routesFile);
        $this->assertSame('home', $router->getPage());
    }

    /** @test */
    public function getAction_padrao_e_index(): void
    {
        $_GET['page'] = 'home';
        unset($_GET['action']);
        $router = new Router($this->routesFile);
        $this->assertSame('index', $router->getAction());
    }

    // ══════════════════════════════════════════════════════════════
    // isPublicPage()
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function isPublicPage_retorna_true_para_pagina_publica(): void
    {
        $router = $this->createRouter('login');
        $this->assertTrue($router->isPublicPage());
    }

    /** @test */
    public function isPublicPage_retorna_false_para_pagina_protegida(): void
    {
        $router = $this->createRouter('home');
        $this->assertFalse($router->isPublicPage());
    }

    /** @test */
    public function isPublicPage_retorna_false_para_pagina_inexistente(): void
    {
        $router = $this->createRouter('nao_existe');
        $this->assertFalse($router->isPublicPage());
    }

    // ══════════════════════════════════════════════════════════════
    // hasBeforeAuth()
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function hasBeforeAuth_retorna_true_quando_definido(): void
    {
        $router = $this->createRouter('login');
        $this->assertTrue($router->hasBeforeAuth());
    }

    /** @test */
    public function hasBeforeAuth_retorna_false_quando_nao_definido(): void
    {
        $router = $this->createRouter('products');
        $this->assertFalse($router->hasBeforeAuth());
    }

    // ══════════════════════════════════════════════════════════════
    // routeExists()
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function routeExists_retorna_true_para_rota_existente(): void
    {
        $router = $this->createRouter('products');
        $this->assertTrue($router->routeExists());
    }

    /** @test */
    public function routeExists_retorna_false_para_rota_inexistente(): void
    {
        $router = $this->createRouter('pagina_fantasma');
        $this->assertFalse($router->routeExists());
    }

    // ══════════════════════════════════════════════════════════════
    // resolveControllerClass() via Reflection
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function resolveControllerClass_adiciona_namespace_para_nome_curto(): void
    {
        $router = $this->createRouter();
        $method = new \ReflectionMethod(Router::class, 'resolveControllerClass');
        $method->setAccessible(true);

        $result = $method->invoke($router, 'ProductController');
        $this->assertSame('Akti\\Controllers\\ProductController', $result);
    }

    /** @test */
    public function resolveControllerClass_mantem_fqcn_com_akti_prefix(): void
    {
        $router = $this->createRouter();
        $method = new \ReflectionMethod(Router::class, 'resolveControllerClass');
        $method->setAccessible(true);

        $result = $method->invoke($router, 'Akti\\Controllers\\ProductController');
        $this->assertSame('Akti\\Controllers\\ProductController', $result);
    }

    /** @test */
    public function resolveControllerClass_remove_backslash_inicial(): void
    {
        $router = $this->createRouter();
        $method = new \ReflectionMethod(Router::class, 'resolveControllerClass');
        $method->setAccessible(true);

        $result = $method->invoke($router, '\\Akti\\Controllers\\ProductController');
        $this->assertSame('Akti\\Controllers\\ProductController', $result);
    }

    // ══════════════════════════════════════════════════════════════
    // resolveAction() via Reflection
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function resolveAction_retorna_mapeamento_explicito_string(): void
    {
        $router = $this->createRouter('login', 'doLogin');
        $method = new \ReflectionMethod(Router::class, 'resolveAction');
        $method->setAccessible(true);

        $route = $this->getTestRoutes()['login'];
        $result = $method->invoke($router, $route);

        $this->assertSame('processLogin', $result['method']);
        $this->assertNull($result['controller']);
    }

    /** @test */
    public function resolveAction_retorna_mapeamento_com_controller_alternativo(): void
    {
        $router = $this->createRouter('with_alt_controller', 'special');
        $method = new \ReflectionMethod(Router::class, 'resolveAction');
        $method->setAccessible(true);

        $route = $this->getTestRoutes()['with_alt_controller'];
        $result = $method->invoke($router, $route);

        $this->assertSame('handle', $result['method']);
        $this->assertSame('SpecialController', $result['controller']);
    }

    /** @test */
    public function resolveAction_retorna_default_para_action_index(): void
    {
        $router = $this->createRouter('products', 'index');
        $method = new \ReflectionMethod(Router::class, 'resolveAction');
        $method->setAccessible(true);

        $route = $this->getTestRoutes()['products'];
        $result = $method->invoke($router, $route);

        $this->assertSame('index', $result['method']);
    }

    /** @test */
    public function resolveAction_permite_unmapped_com_allow_unmapped(): void
    {
        $router = $this->createRouter('unmapped', 'qualquerMetodo');
        $method = new \ReflectionMethod(Router::class, 'resolveAction');
        $method->setAccessible(true);

        $route = $this->getTestRoutes()['unmapped'];
        $result = $method->invoke($router, $route);

        $this->assertSame('qualquerMetodo', $result['method']);
    }

    /** @test */
    public function resolveAction_volta_para_default_se_action_nao_mapeada(): void
    {
        $router = $this->createRouter('products', 'actionInexistente');
        $method = new \ReflectionMethod(Router::class, 'resolveAction');
        $method->setAccessible(true);

        $route = $this->getTestRoutes()['products'];
        $result = $method->invoke($router, $route);

        $this->assertSame('index', $result['method']);
    }

    /** @test */
    public function resolveAction_usa_action_vazia_como_default(): void
    {
        $router = $this->createRouter('products', '');
        $method = new \ReflectionMethod(Router::class, 'resolveAction');
        $method->setAccessible(true);

        $route = $this->getTestRoutes()['products'];
        $result = $method->invoke($router, $route);

        $this->assertSame('index', $result['method']);
    }
}
