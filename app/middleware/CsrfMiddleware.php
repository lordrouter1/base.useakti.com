<?php
namespace Akti\Middleware;

use Akti\Core\EventDispatcher;
use Akti\Core\Event;
use Akti\Core\Security;

/**
 * CsrfMiddleware — Intercepta requisições que alteram dados e valida token CSRF.
 *
 * Deve ser executado no index.php ANTES do dispatch do Router para garantir
 * que nenhuma requisição POST/PUT/PATCH/DELETE chegue ao controller sem token válido.
 *
 * Métodos HTTP protegidos: POST, PUT, PATCH, DELETE.
 * Métodos HTTP ignorados: GET, HEAD, OPTIONS.
 *
 * O token é buscado na seguinte ordem:
 * 1. Campo POST 'csrf_token'
 * 2. Header HTTP 'X-CSRF-TOKEN' (para AJAX)
 *
 * Rotas que podem ser isentas (ex: webhooks) devem ser listadas em $exemptRoutes.
 *
 * @package Akti\Middleware
 * @see     Akti\Core\Security
 * @see     PROJECT_RULES.md — Módulo: Segurança — Proteção CSRF
 */
class CsrfMiddleware
{
    /**
     * Métodos HTTP que requerem validação CSRF.
     */
    private const PROTECTED_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * Rotas isentas de verificação CSRF (ex: webhooks, APIs externas).
     * Formato: 'page:action' ou 'page:*' para isentar toda a page.
     *
     * @var array
     */
    private static array $exemptRoutes = [
        // Exemplo: 'webhook:*',
        // Exemplo: 'api:callback',
    ];

    // ══════════════════════════════════════════════════════════════
    // Método principal
    // ══════════════════════════════════════════════════════════════

    /**
     * Verifica se a requisição atual precisa de validação CSRF e, se sim, valida.
     *
     * Fluxo:
     * 1. Se o método HTTP não é protegido (GET, HEAD, OPTIONS) → passa direto
     * 2. Se a rota é isenta → passa direto
     * 3. Busca o token no POST ou no header X-CSRF-TOKEN
     * 4. Se o token é inválido → loga e retorna 403
     * 5. Se o token é válido → permite a requisição
     *
     * @return void Retorna normalmente se a validação passa. Aborta com 403 se falhar.
     */
    public static function handle(): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        // Métodos seguros (leitura) não precisam de CSRF
        if (!in_array($method, self::PROTECTED_METHODS, true)) {
            return;
        }

        // Verificar se a rota é isenta
        if (self::isExempt()) {
            return;
        }

        // Buscar token: primeiro no POST, depois no header
        $token = self::extractToken();

        // Validar
        if (!Security::validateCsrfToken($token)) {
            $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $page   = $_GET['page'] ?? 'home';
            $action = $_GET['action'] ?? 'index';
            EventDispatcher::dispatch('middleware.csrf.failed', new Event('middleware.csrf.failed', [
                'ip' => $ip,
                'route' => "?page={$page}&action={$action}",
                'method' => $method,
            ]));
            Security::handleCsrfFailure($token);
            // handleCsrfFailure chama exit, mas por segurança:
            exit;
        }

        // Token válido — requisição pode prosseguir
    }

    // ══════════════════════════════════════════════════════════════
    // Extração do Token
    // ══════════════════════════════════════════════════════════════

    /**
     * Extrai o token CSRF da requisição.
     *
     * Ordem de busca:
     * 1. Campo POST 'csrf_token'
     * 2. Header HTTP 'X-CSRF-TOKEN'
     *
     * @return string|null Token encontrado ou null
     */
    private static function extractToken(): ?string
    {
        // 1. Campo POST
        if (isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])) {
            return $_POST['csrf_token'];
        }

        // 2. Header HTTP (para AJAX)
        $header = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if ($header !== null && is_string($header)) {
            return $header;
        }

        return null;
    }

    // ══════════════════════════════════════════════════════════════
    // Isenções
    // ══════════════════════════════════════════════════════════════

    /**
     * Verifica se a rota atual está na lista de isenções.
     *
     * @return bool true se a rota é isenta de CSRF
     */
    private static function isExempt(): bool
    {
        $page   = $_GET['page'] ?? 'home';
        $action = $_GET['action'] ?? 'index';

        // Verificar match exato: 'page:action'
        if (in_array("{$page}:{$action}", self::$exemptRoutes, true)) {
            return true;
        }

        // Verificar wildcard: 'page:*'
        if (in_array("{$page}:*", self::$exemptRoutes, true)) {
            return true;
        }

        return false;
    }

    // ══════════════════════════════════════════════════════════════
    // Configuração
    // ══════════════════════════════════════════════════════════════

    /**
     * Adiciona uma rota à lista de isenções em runtime.
     * Útil para testes ou configurações dinâmicas.
     *
     * @param string $route Formato: 'page:action' ou 'page:*'
     */
    public static function addExemptRoute(string $route): void
    {
        if (!in_array($route, self::$exemptRoutes, true)) {
            self::$exemptRoutes[] = $route;
        }
    }

    /**
     * Retorna a lista atual de rotas isentas.
     *
     * @return array
     */
    public static function getExemptRoutes(): array
    {
        return self::$exemptRoutes;
    }
}
