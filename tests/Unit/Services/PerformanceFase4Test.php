<?php
namespace Akti\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;

/**
 * Testes de Fase 4 — Performance e Otimização.
 * Verifica existência de métodos de paginação, cache e asset versioning.
 *
 * @package Akti\Tests\Unit\Services
 */
class PerformanceFase4Test extends TestCase
{
    /**
     * Helper: cria mock PDO.
     */
    private function createMockPdo(): \PDO
    {
        return $this->createMock(\PDO::class);
    }

    // ═══════════════════════════════════════════════
    //  4.1 — SimpleCache
    // ═══════════════════════════════════════════════

    /**
     * @test
     * SimpleCache deve existir no namespace correto.
     */
    public function simple_cache_exists_in_correct_namespace(): void
    {
        $this->assertTrue(
            class_exists(\Akti\Utils\SimpleCache::class),
            'SimpleCache deve existir em Akti\Utils'
        );
    }

    /**
     * @test
     * SimpleCache deve ter os métodos: remember, get, set, forget, forgetByPrefix, flush, has, stats.
     */
    public function simple_cache_has_all_methods(): void
    {
        $methods = ['remember', 'get', 'set', 'forget', 'forgetByPrefix', 'flush', 'has', 'stats'];
        foreach ($methods as $method) {
            $this->assertTrue(
                method_exists(\Akti\Utils\SimpleCache::class, $method),
                "SimpleCache deve ter o método {$method}()"
            );
        }
    }

    // ═══════════════════════════════════════════════
    //  4.2 — searchPaginated nos Models
    // ═══════════════════════════════════════════════

    /**
     * @test
     * Customer model deve ter searchPaginated().
     */
    public function customer_model_has_search_paginated(): void
    {
        $pdo = $this->createMockPdo();
        $model = new \Akti\Models\Customer($pdo);

        $this->assertTrue(
            method_exists($model, 'searchPaginated'),
            'Customer deve ter o método searchPaginated()'
        );

        $ref = new \ReflectionMethod($model, 'searchPaginated');
        $params = $ref->getParameters();
        $this->assertGreaterThanOrEqual(3, count($params), 'searchPaginated deve aceitar pelo menos 3 parâmetros');
        $this->assertEquals('query', $params[0]->getName());
        $this->assertEquals('page', $params[1]->getName());
        $this->assertEquals('perPage', $params[2]->getName());
    }

    /**
     * @test
     * Product model deve ter searchPaginated().
     */
    public function product_model_has_search_paginated(): void
    {
        $pdo = $this->createMockPdo();
        $model = new \Akti\Models\Product($pdo);

        $this->assertTrue(
            method_exists($model, 'searchPaginated'),
            'Product deve ter o método searchPaginated()'
        );

        $ref = new \ReflectionMethod($model, 'searchPaginated');
        $params = $ref->getParameters();
        $this->assertGreaterThanOrEqual(3, count($params));
    }

    // ═══════════════════════════════════════════════
    //  4.2 — searchAjax nos Controllers
    // ═══════════════════════════════════════════════

    /**
     * @test
     * CustomerController deve ter searchAjax().
     */
    public function customer_controller_has_search_ajax(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Controllers\CustomerController::class, 'searchAjax'),
            'CustomerController deve ter o método searchAjax()'
        );
    }

    /**
     * @test
     * ProductController deve ter searchAjax().
     */
    public function product_controller_has_search_ajax(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Controllers\ProductController::class, 'searchAjax'),
            'ProductController deve ter o método searchAjax()'
        );
    }

    // ═══════════════════════════════════════════════
    //  4.4 — Asset Versioning
    // ═══════════════════════════════════════════════

    /**
     * @test
     * A função global asset() deve existir.
     */
    public function asset_function_is_defined(): void
    {
        $this->assertTrue(
            function_exists('asset'),
            'A função global asset() deve estar definida pelo asset_helper.php'
        );
    }

    /**
     * @test
     * asset() deve retornar path com ?v= timestamp.
     */
    public function asset_returns_versioned_path(): void
    {
        $result = asset('assets/css/style.css');
        $this->assertMatchesRegularExpression(
            '/^assets\/css\/style\.css\?v=\d+$/',
            $result,
            'asset() deve retornar path?v=<timestamp>'
        );
    }

    // ═══════════════════════════════════════════════
    //  4.5 — Paginação Server-Side Universal
    // ═══════════════════════════════════════════════

    /**
     * @test
     * Stock model deve ter getMovementsPaginated().
     */
    public function stock_model_has_movements_paginated(): void
    {
        $pdo = $this->createMockPdo();
        $model = new \Akti\Models\Stock($pdo);

        $this->assertTrue(
            method_exists($model, 'getMovementsPaginated'),
            'Stock deve ter o método getMovementsPaginated()'
        );

        $ref = new \ReflectionMethod($model, 'getMovementsPaginated');
        $params = $ref->getParameters();
        $this->assertGreaterThanOrEqual(3, count($params));
    }

    /**
     * @test
     * Logger model deve ter getPaginated().
     */
    public function logger_model_has_get_paginated(): void
    {
        $pdo = $this->createMockPdo();
        $model = new \Akti\Models\Logger($pdo);

        $this->assertTrue(
            method_exists($model, 'getPaginated'),
            'Logger deve ter o método getPaginated()'
        );

        $ref = new \ReflectionMethod($model, 'getPaginated');
        $params = $ref->getParameters();
        $this->assertGreaterThanOrEqual(3, count($params));
    }

    /**
     * @test
     * Logger model deve ter getDistinctActions().
     */
    public function logger_model_has_distinct_actions(): void
    {
        $pdo = $this->createMockPdo();
        $model = new \Akti\Models\Logger($pdo);

        $this->assertTrue(
            method_exists($model, 'getDistinctActions'),
            'Logger deve ter o método getDistinctActions()'
        );
    }

    /**
     * @test
     * Installment model já deve ter getPaginated().
     */
    public function installment_model_has_get_paginated(): void
    {
        $pdo = $this->createMockPdo();
        $model = new \Akti\Models\Installment($pdo);

        $this->assertTrue(
            method_exists($model, 'getPaginated'),
            'Installment deve ter o método getPaginated()'
        );
    }

    /**
     * @test
     * Commission model já deve ter getComissoesRegistradas() com paginação.
     */
    public function commission_model_has_paginated_comissoes(): void
    {
        $pdo = $this->createMockPdo();
        $model = new \Akti\Models\Commission($pdo);

        $this->assertTrue(
            method_exists($model, 'getComissoesRegistradas'),
            'Commission deve ter o método getComissoesRegistradas()'
        );

        $ref = new \ReflectionMethod($model, 'getComissoesRegistradas');
        $params = $ref->getParameters();
        // Deve aceitar filtros, page, perPage
        $this->assertGreaterThanOrEqual(3, count($params));
    }

    // ═══════════════════════════════════════════════
    //  4.1 — HeaderDataService usa SimpleCache
    // ═══════════════════════════════════════════════

    /**
     * @test
     * HeaderDataService deve importar SimpleCache.
     */
    public function header_data_service_uses_simple_cache(): void
    {
        $source = file_get_contents(AKTI_BASE_PATH . 'app/services/HeaderDataService.php');
        $this->assertStringContainsString(
            'use Akti\Utils\SimpleCache',
            $source,
            'HeaderDataService deve importar SimpleCache'
        );
    }

    /**
     * @test
     * HeaderDataService::invalidateCache() deve usar SimpleCache::forget.
     */
    public function header_data_service_invalidate_uses_simple_cache(): void
    {
        $source = file_get_contents(AKTI_BASE_PATH . 'app/services/HeaderDataService.php');
        $this->assertStringContainsString(
            'SimpleCache::forget',
            $source,
            'invalidateCache() deve usar SimpleCache::forget()'
        );
    }

    // ═══════════════════════════════════════════════
    //  4.4 — header.php usa asset()
    // ═══════════════════════════════════════════════

    /**
     * @test
     * O header.php deve usar asset() para CSS local.
     */
    public function header_view_uses_asset_helper(): void
    {
        $source = file_get_contents(AKTI_BASE_PATH . 'app/views/layout/header.php');
        $this->assertStringContainsString(
            "asset('assets/css/style.css')",
            $source,
            'header.php deve usar asset() para style.css'
        );
        $this->assertStringContainsString(
            "asset('assets/css/theme.css')",
            $source,
            'header.php deve usar asset() para theme.css'
        );
    }

    /**
     * @test
     * O footer.php deve usar asset() para JS local.
     */
    public function footer_view_uses_asset_helper(): void
    {
        $source = file_get_contents(AKTI_BASE_PATH . 'app/views/layout/footer.php');
        $this->assertStringContainsString(
            "asset('assets/js/script.js')",
            $source,
            'footer.php deve usar asset() para script.js'
        );
    }

    // ═══════════════════════════════════════════════
    //  4.4 — asset_helper.php está no autoload
    // ═══════════════════════════════════════════════

    /**
     * @test
     * O autoload.php deve carregar o asset_helper.php.
     */
    public function autoload_loads_asset_helper(): void
    {
        $source = file_get_contents(AKTI_BASE_PATH . 'app/bootstrap/autoload.php');
        $this->assertStringContainsString(
            'asset_helper.php',
            $source,
            'autoload.php deve carregar asset_helper.php'
        );
    }

    // ═══════════════════════════════════════════════
    //  Rotas: searchAjax está registrada
    // ═══════════════════════════════════════════════

    /**
     * @test
     * routes.php deve ter searchAjax nas rotas de products e customers.
     */
    public function routes_config_has_search_ajax(): void
    {
        $source = file_get_contents(AKTI_BASE_PATH . 'app/config/routes.php');
        $this->assertStringContainsString(
            "'searchAjax'",
            $source,
            'routes.php deve conter a rota searchAjax'
        );
    }
}
