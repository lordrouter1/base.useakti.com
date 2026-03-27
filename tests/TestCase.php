<?php
namespace Akti\Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * Classe base para todos os testes do sistema Akti.
 *
 * Fornece:
 * - URL base configurável via phpunit.xml (<env name="AKTI_TEST_BASE_URL">)
 * - Login automático com cookie jar em arquivo temporário (cURL)
 * - Sessão compartilhada durante toda a execução da suite (login único)
 * - Métodos utilitários para requisições HTTP
 * - Validação de ausência de erros PHP na resposta
 *
 * @package Akti\Tests
 */
abstract class TestCase extends PHPUnitTestCase
{
    /** @var string URL base do sistema */
    protected string $baseUrl;

    /** @var string|null Caminho do cookie jar compartilhado entre TODOS os testes */
    protected static ?string $cookieJarFile = null;

    /** @var bool Indica se o login já foi feito nesta execução */
    protected static bool $loggedIn = false;

    /** @var int Timeout das requisições em segundos */
    protected int $timeout;

    // ─── Strings que indicam erro PHP na resposta HTML ───────────
    // Usamos padrões específicos para evitar falsos-positivos com conteúdo
    // legítimo do Bootstrap/JS (ex: alert-warning, icon:'warning').
    protected static array $errorPatterns = [
        'Fatal error',
        'Parse error',
        'Uncaught Error',
        'Uncaught Exception',
        'Stack trace:',
        'xdebug-error',
        'Undefined variable',
        'Undefined index',
        'Undefined array key',
        'Call to undefined',
        'Class &quot;',      // Class "X" not found (HTML-encoded)
    ];

    // ─── Padrões regex para erros PHP (mais precisos) ────────────
    // Detectam "Warning:" e "Notice:" apenas quando precedidos por
    // indicadores de erro PHP (tag <b>, "PHP", início de linha).
    protected static array $errorRegexPatterns = [
        '/<b>Warning<\/b>:/i',
        '/<b>Notice<\/b>:/i',
        '/\bPHP Warning:/i',
        '/\bPHP Notice:/i',
        '/\bPHP Fatal error/i',
        '/\bPHP Parse error/i',
        '/\bClass ".+" not found/i',
    ];

    // ══════════════════════════════════════════════════════════════
    // Setup
    // ══════════════════════════════════════════════════════════════

    protected function setUp(): void
    {
        parent::setUp();
        $this->baseUrl = rtrim(getenv('AKTI_TEST_BASE_URL') ?: 'http://localhost/teste.akti.com', '/');
        $this->timeout = (int) (getenv('AKTI_TEST_TIMEOUT') ?: 30);
    }

    // ══════════════════════════════════════════════════════════════
    // Cookie jar (compartilhado entre todas as classes de teste)
    // ══════════════════════════════════════════════════════════════

    /**
     * Retorna o caminho do arquivo de cookie jar.
     * Cria o arquivo temporário na primeira chamada.
     */
    protected static function getCookieJarFile(): string
    {
        if (self::$cookieJarFile === null || !file_exists(self::$cookieJarFile)) {
            self::$cookieJarFile = tempnam(sys_get_temp_dir(), 'akti_test_cookies_');
        }
        return self::$cookieJarFile;
    }

    // ══════════════════════════════════════════════════════════════
    // Autenticação
    // ══════════════════════════════════════════════════════════════

    /**
     * Faz login no sistema e armazena cookies no cookie jar.
     * O login é feito UMA ÚNICA VEZ durante toda a execução da suite.
     * Todas as classes de teste compartilham a mesma sessão.
     */
    protected function ensureAuthenticated(): void
    {
        if (self::$loggedIn) {
            return;
        }

        $email    = getenv('AKTI_TEST_USER_EMAIL') ?: 'admin@akti.com';
        $password = getenv('AKTI_TEST_USER_PASSWORD') ?: 'admin123';

        $loginUrl = $this->baseUrl . '/?page=login';
        $jarFile  = self::getCookieJarFile();

        // ── Passo 1: GET na página de login ──────────────────────
        // Necessário para:
        // - Iniciar a sessão PHP (obtém AKTI_SID via Set-Cookie)
        // - Obter o tenant_key do formulário (validação CSRF-like)
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $loginUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_COOKIEJAR      => $jarFile,
            CURLOPT_COOKIEFILE     => $jarFile,
        ]);

        $loginPageBody = curl_exec($ch);
        curl_close($ch);

        // Extrair tenant_key do formulário
        $tenantKey = '';
        if (preg_match('/name="tenant_key"\s+value="([^"]*)"/', $loginPageBody, $m)) {
            $tenantKey = $m[1];
        }

        // Extrair csrf_token do formulário (proteção CSRF)
        $csrfToken = '';
        if (preg_match('/name="csrf_token"\s+value="([^"]*)"/', $loginPageBody, $m)) {
            $csrfToken = $m[1];
        }

        // ── Passo 2: POST de login com credenciais, tenant_key e csrf_token ──
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $loginUrl,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'email'      => $email,
                'password'   => $password,
                'tenant_key' => $tenantKey,
                'csrf_token' => $csrfToken,
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_FOLLOWLOCATION => false,  // Captura o redirect 302 pós-login
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_COOKIEJAR      => $jarFile,
            CURLOPT_COOKIEFILE     => $jarFile,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Login bem-sucedido retorna 302 (redirect para home)
        $loginOk = ($httpCode === 302 || $httpCode === 303);

        // Verificar se o cookie jar contém um cookie de sessão
        $jarContents = file_exists($jarFile) ? file_get_contents($jarFile) : '';
        $hasCookie = (
            stripos($jarContents, 'AKTI_SID') !== false
            || stripos($jarContents, 'PHPSESSID') !== false
        );

        $this->assertTrue(
            $loginOk && $hasCookie,
            "Login falhou — HTTP {$httpCode} (esperado 302), cookie jar "
            . ($hasCookie ? 'OK' : 'VAZIO')
            . ", tenant_key='{$tenantKey}'"
            . ". Verifique as credenciais em phpunit.xml "
            . "(AKTI_TEST_USER_EMAIL / AKTI_TEST_USER_PASSWORD).\n"
            . "Cookie jar: {$jarFile}"
        );

        self::$loggedIn = true;
    }

    /**
     * Verifica se a resposta é uma página de login (redirect não-autenticado).
     * Útil para detectar sessão expirada e re-autenticar.
     */
    protected function isLoginPage(string $body): bool
    {
        return stripos($body, '<title>Login') !== false
            && stripos($body, 'page=login') !== false;
    }

    // ══════════════════════════════════════════════════════════════
    // Requisições HTTP
    // ══════════════════════════════════════════════════════════════

    /**
     * Faz requisição GET e retorna array com dados da resposta.
     *
     * Usa o cookie jar em arquivo para manter a sessão automaticamente,
     * sem precisar extrair/passar cookies manualmente.
     *
     * @param  string $route  Query string (ex: '?page=products')
     * @param  bool   $auth   Se deve enviar cookie de sessão
     * @return array  ['status' => int, 'body' => string, 'headers' => string, 'url' => string]
     */
    protected function httpGet(string $route, bool $auth = true): array
    {
        if ($auth) {
            $this->ensureAuthenticated();
        }

        $url = $this->baseUrl . '/' . ltrim($route, '/');
        $jarFile = self::getCookieJarFile();

        $ch = curl_init();
        $opts = [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => $this->timeout,
        ];

        if ($auth) {
            $opts[CURLOPT_COOKIEJAR]  = $jarFile;
            $opts[CURLOPT_COOKIEFILE] = $jarFile;
        }

        curl_setopt_array($ch, $opts);
        $raw = curl_exec($ch);

        if ($raw === false) {
            $error = curl_error($ch);
            curl_close($ch);
            $this->fail("cURL error para {$url}: {$error}");
        }

        $httpCode   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $finalUrl   = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);

        $headers = substr($raw, 0, $headerSize);
        $body    = substr($raw, $headerSize);

        // Se recebemos a página de login em uma requisição autenticada,
        // a sessão expirou — tentar re-login uma vez.
        if ($auth && $this->isLoginPage($body)) {
            self::$loggedIn = false;
            // Limpar cookie jar antigo
            if (file_exists($jarFile)) {
                @unlink($jarFile);
            }
            self::$cookieJarFile = null;

            $this->ensureAuthenticated();

            // Repetir a requisição
            $jarFile = self::getCookieJarFile();
            $ch = curl_init();
            $opts[CURLOPT_COOKIEJAR]  = $jarFile;
            $opts[CURLOPT_COOKIEFILE] = $jarFile;
            curl_setopt_array($ch, $opts);
            $raw = curl_exec($ch);

            if ($raw === false) {
                $error = curl_error($ch);
                curl_close($ch);
                $this->fail("cURL error (retry) para {$url}: {$error}");
            }

            $httpCode   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $finalUrl   = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
            curl_close($ch);

            $headers = substr($raw, 0, $headerSize);
            $body    = substr($raw, $headerSize);
        }

        return [
            'status'  => $httpCode,
            'body'    => $body,
            'headers' => $headers,
            'url'     => $finalUrl,
        ];
    }

    // ══════════════════════════════════════════════════════════════
    // Assertions utilitárias
    // ══════════════════════════════════════════════════════════════

    /**
     * Verifica que a resposta NÃO contém strings de erro PHP.
     * Usa padrões exatos (string) e regex (para Warning:/Notice: com contexto PHP).
     */
    protected function assertNoPhpErrors(string $body, string $label = ''): void
    {
        // Padrões de string simples
        foreach (self::$errorPatterns as $pattern) {
            $this->assertStringNotContainsStringIgnoringCase(
                $pattern,
                $body,
                "Erro PHP detectado na página '{$label}': contém '{$pattern}'"
            );
        }

        // Padrões regex (mais precisos para Warning/Notice)
        foreach (self::$errorRegexPatterns as $regex) {
            $this->assertDoesNotMatchRegularExpression(
                $regex,
                $body,
                "Erro PHP detectado na página '{$label}': regex '{$regex}' encontrado"
            );
        }
    }

    /**
     * Verifica que o status HTTP é 200.
     */
    protected function assertStatusOk(int $status, string $label = ''): void
    {
        $this->assertEquals(
            200,
            $status,
            "Página '{$label}' retornou HTTP {$status} (esperado 200)"
        );
    }

    /**
     * Verifica que a resposta contém determinadas strings.
     *
     * @param string[] $needles
     */
    protected function assertBodyContains(array $needles, string $body, string $label = ''): void
    {
        foreach ($needles as $needle) {
            $this->assertStringContainsStringIgnoringCase(
                $needle,
                $body,
                "Página '{$label}' não contém o texto esperado: '{$needle}'"
            );
        }
    }

    /**
     * Verifica que a resposta contém estrutura HTML básica.
     */
    protected function assertValidHtml(string $body, string $label = ''): void
    {
        $this->assertStringContainsStringIgnoringCase(
            '<html',
            $body,
            "Página '{$label}' não contém tag <html>"
        );
    }

    /**
     * Verifica que a resposta NÃO é a página de login (sessão ativa).
     */
    protected function assertNotLoginPage(string $body, string $label = ''): void
    {
        $this->assertFalse(
            $this->isLoginPage($body),
            "Página '{$label}' retornou a tela de login — sessão pode estar expirada"
        );
    }

    /**
     * Carrega todas as rotas do arquivo routes_test.php.
     *
     * @return array
     */
    protected function loadRoutes(): array
    {
        $file = __DIR__ . '/routes_test.php';
        $this->assertFileExists($file, 'Arquivo routes_test.php não encontrado');
        return require $file;
    }
}
