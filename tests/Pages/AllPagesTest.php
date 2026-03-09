<?php
namespace Akti\Tests\Pages;

use Akti\Tests\TestCase;

/**
 * Teste automatizado de TODAS as rotas do sistema.
 *
 * Percorre cada rota registrada em routes_test.php e verifica:
 * 1. Status HTTP 200
 * 2. Ausência de erros PHP (Fatal, Warning, Parse error, etc.)
 * 3. Presença de strings esperadas (se definidas)
 * 4. Estrutura HTML válida
 *
 * Executar: vendor/bin/phpunit tests/Pages/AllPagesTest.php
 */
class AllPagesTest extends TestCase
{
    /** @var array Rotas carregadas de routes_test.php */
    private array $routes = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->routes = $this->loadRoutes();
    }

    // ══════════════════════════════════════════════════════════════
    // Teste principal: todas as rotas
    // ══════════════════════════════════════════════════════════════

    /**
     * @test
     * @dataProvider routeProvider
     */
    public function pagina_carrega_sem_erros(string $route, string $label, bool $auth, array $contains): void
    {
        $response = $this->httpGet($route, $auth);

        // 1. Status HTTP 200
        $this->assertStatusOk($response['status'], $label);

        // 2. Sem erros PHP
        $this->assertNoPhpErrors($response['body'], $label);

        // 3. HTML válido
        $this->assertValidHtml($response['body'], $label);

        // 4. Strings esperadas
        if (!empty($contains)) {
            $this->assertBodyContains($contains, $response['body'], $label);
        }
    }

    /**
     * DataProvider — gera um caso de teste para cada rota registrada.
     */
    public function routeProvider(): array
    {
        $routes = require __DIR__ . '/../routes_test.php';
        $cases = [];

        foreach ($routes as $r) {
            $key = $r['label'] ?? $r['route'];
            $cases[$key] = [
                $r['route'],
                $r['label'] ?? $r['route'],
                $r['auth'] ?? true,
                $r['contains'] ?? [],
            ];
        }

        return $cases;
    }

    // ══════════════════════════════════════════════════════════════
    // Testes complementares
    // ══════════════════════════════════════════════════════════════

    /**
     * @test
     * Verifica que a página de login NÃO exige autenticação.
     */
    public function pagina_login_acessivel_sem_autenticacao(): void
    {
        $response = $this->httpGet('?page=login', false);
        $this->assertStatusOk($response['status'], 'Login (sem auth)');
        $this->assertNoPhpErrors($response['body'], 'Login (sem auth)');
    }

    /**
     * @test
     * Verifica que uma rota inexistente retorna 404 ou redireciona (sem erros PHP).
     *
     * Nota: Dependendo da configuração do roteador, rotas desconhecidas podem
     * retornar 404 ou redirecionar para a home (200). Ambos são aceitáveis,
     * desde que NÃO haja erros PHP.
     */
    public function pagina_inexistente_sem_erros_php(): void
    {
        $response = $this->httpGet('?page=rota_que_nao_existe_xyz_' . time(), true);
        $this->assertContains(
            $response['status'],
            [200, 302, 404],
            "Rota inexistente retornou HTTP {$response['status']} — esperado 200, 302 ou 404"
        );
        $this->assertNoPhpErrors($response['body'], 'Rota inexistente');
    }

    /**
     * @test
     * Verifica que acessar uma página autenticada sem sessão redireciona para login.
     */
    public function pagina_protegida_redireciona_sem_sessao(): void
    {
        // Faz request sem cookie — deve redirecionar para login
        $response = $this->httpGet('?page=products', false);

        // Após seguir redirects (FOLLOWLOCATION=true), a URL final deve conter "page=login"
        $this->assertStringContainsString(
            'page=login',
            $response['url'],
            'Página protegida não redirecionou para login quando acessada sem sessão'
        );
    }
}
