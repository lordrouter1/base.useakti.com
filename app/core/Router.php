<?php
namespace Akti\Core;

use Akti\Models\IpGuard;
use Psr\Container\ContainerInterface;

/**
 * Router baseado em mapa de rotas — Akti
 *
 * Substitui o switch/case do index.php por um sistema declarativo.
 * Cada rota é definida em app/config/routes.php como um array associativo.
 *
 * Funcionalidades:
 * - Resolução de page + action para controller::method
 * - Actions com mapeamento de nome (action → método diferente)
 * - Páginas públicas (sem autenticação)
 * - Atalhos (alias) para outros controllers
 * - Redirects declarativos
 * - Renderização direta de views
 * - Tratamento de 404 com IP Guard
 *
 * @package Akti\Core
 * @see     app/config/routes.php
 * @see     PROJECT_RULES.md — Módulo: Router
 */
class Router
{
    /** @var array Mapa de rotas carregado de routes.php */
    private array $routes = [];

    /** @var string Page atual (?page=xxx) */
    private string $page;

    /** @var string Action atual (?action=xxx) */
    private string $action;

    /** @var ContainerInterface|null PSR-11 Container */
    private ?ContainerInterface $container;

    // ══════════════════════════════════════════════════════════════
    // Construtor
    // ══════════════════════════════════════════════════════════════

    /**
     * @param string $routesFile  Caminho absoluto para o arquivo routes.php
     * @param ContainerInterface|null $container  Container PSR-11 para resolução de dependências
     */
    public function __construct(string $routesFile, ?ContainerInterface $container = null)
    {
        if (!file_exists($routesFile)) {
            throw new \RuntimeException("Arquivo de rotas não encontrado: {$routesFile}");
        }

        $this->routes = require $routesFile;
        $this->page   = $_GET['page'] ?? 'home';
        $this->action = $_GET['action'] ?? 'index';
        $this->container = $container;
    }

    // ══════════════════════════════════════════════════════════════
    // Getters
    // ══════════════════════════════════════════════════════════════

    public function getPage(): string
    {
        return $this->page;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    // ══════════════════════════════════════════════════════════════
    // Verificações
    // ══════════════════════════════════════════════════════════════

    /**
     * Verifica se a página atual é pública (não requer autenticação).
     */
    public function isPublicPage(): bool
    {
        $route = $this->routes[$this->page] ?? null;
        return $route && !empty($route['public']);
    }

    /**
     * Verifica se a página atual tem um handler "before_auth"
     * que deve ser executado ANTES da verificação de autenticação.
     */
    public function hasBeforeAuth(): bool
    {
        $route = $this->routes[$this->page] ?? null;
        return $route && !empty($route['before_auth']);
    }

    /**
     * Verifica se a rota existe no mapa.
     */
    public function routeExists(): bool
    {
        return isset($this->routes[$this->page]);
    }

    // ══════════════════════════════════════════════════════════════
    // Despacho (dispatch)
    // ══════════════════════════════════════════════════════════════

    /**
     * Resolve e executa a rota atual.
     *
     * Fluxo:
     * 1. Busca a config da page no mapa de rotas
     * 2. Se a rota tem 'redirect' → redireciona
     * 3. Se a rota tem 'view' → renderiza views diretamente
     * 4. Instancia o controller (FQCN via namespace)
     * 5. Resolve a action:
     *    a. Busca no mapa 'actions' para ver se existe mapeamento específico
     *    b. Se a action tem 'controller' diferente → usa outro controller
     *    c. Se a action tem 'method' → chama esse método
     *    d. Caso contrário, chama o método com o mesmo nome da action
     *    e. Se a action não está no mapa e existe na whitelist → chama direto
     *    f. Se nenhum match → chama o default_action da rota
     */
    public function dispatch(): void
    {
        $route = $this->routes[$this->page] ?? null;

        if ($route === null) {
            $this->handle404();
            return;
        }

        // ── Redirect declarativo ──
        if (!empty($route['redirect'])) {
            header('Location: ' . $route['redirect']);
            exit;
        }

        // ── View direta (sem controller) ──
        if (!empty($route['view'])) {
            $views = (array) $route['view'];
            foreach ($views as $view) {
                require $view;
            }
            return;
        }

        // ── Controller + Action ──
        $controllerClass = $this->resolveControllerClass($route['controller']);
        $actionConfig    = $this->resolveAction($route);

        // Se a action resolvida tem um controller diferente, instanciar esse
        if (isset($actionConfig['controller'])) {
            $controllerClass = $this->resolveControllerClass($actionConfig['controller']);
        }

        $controller = $this->createController($controllerClass);
        $method     = $actionConfig['method'];

        if (!method_exists($controller, $method)) {
            // Método não existe no controller — fallback para default_action
            $defaultMethod = $route['default_action'] ?? 'index';
            if (method_exists($controller, $defaultMethod)) {
                $controller->$defaultMethod();
            } else {
                $this->handle404();
            }
            return;
        }

        $controller->$method();
    }

    // ══════════════════════════════════════════════════════════════
    // Resolução interna
    // ══════════════════════════════════════════════════════════════

    /**
     * Converte o nome curto do controller para FQCN.
     * Ex: 'ProductController' → 'Akti\Controllers\ProductController'
     *
     * Se já for FQCN (começa com \\ ou Akti\\), retorna como está.
     */
    private function resolveControllerClass(string $name): string
    {
        if (str_starts_with($name, 'Akti\\') || str_starts_with($name, '\\')) {
            return ltrim($name, '\\');
        }
        return 'Akti\\Controllers\\' . $name;
    }

    /**
     * Resolve qual método chamar baseado na action da URL.
     *
     * Ordem de prioridade:
     * 1. Se existe mapeamento em route['actions'][$action] → usa o mapeamento
     * 2. Se a rota tem 'rest' => true e a action é um verbo REST padrão → auto-map
     * 3. Se o método existe na whitelist (route['actions'] keys) → usa direto
     * 4. Senão → usa route['default_action'] ou 'index'
     *
     * @return array ['method' => string, 'controller' => ?string]
     */
    private function resolveAction(array $route): array
    {
        $actions = $route['actions'] ?? [];
        $action  = $this->action;

        // 1. Busca mapeamento explícito (actions + extra_actions)
        $allActions = $actions;
        if (!empty($route['extra_actions'])) {
            foreach ((array) $route['extra_actions'] as $extra) {
                $allActions[$extra] = $extra;
            }
        }

        if (isset($allActions[$action])) {
            $config = $allActions[$action];

            // String simples: nome do método
            if (is_string($config)) {
                return ['method' => $config, 'controller' => null];
            }

            // Array com config completa
            return [
                'method'     => $config['method'] ?? $action,
                'controller' => $config['controller'] ?? null,
            ];
        }

        // 2. REST convenção: se 'rest' => true, auto-mapeia verbos CRUD padrão
        if (!empty($route['rest'])) {
            $restActions = ['index', 'create', 'store', 'edit', 'update', 'delete'];
            if (in_array($action, $restActions, true)) {
                return ['method' => $action, 'controller' => null];
            }
        }

        // 3. Se não há mapa de actions definido, ou a action não está mapeada,
        //    verificar se o action é "index" e retornar default
        if ($action === 'index' || empty($action)) {
            $default = $route['default_action'] ?? 'index';
            return ['method' => $default, 'controller' => null];
        }

        // 4. Se a rota tem 'allow_unmapped' => true, permite chamar qualquer método
        //    (equivalente ao comportamento antigo para rotas simples)
        if (!empty($route['allow_unmapped'])) {
            return ['method' => $action, 'controller' => null];
        }

        // 5. Se a action não está mapeada e não é permitida → default
        $default = $route['default_action'] ?? 'index';
        return ['method' => $default, 'controller' => null];
    }

    // ══════════════════════════════════════════════════════════════
    // DI Container PSR-11
    // ══════════════════════════════════════════════════════════════

    /**
     * Instancia um controller via Container PSR-11 (auto-wiring).
     *
     * Se o container estiver disponível, delega a resolução para ele.
     * Caso contrário, mantém o fallback com Reflection manual (ARQ-012).
     */
    private function createController(string $class): object
    {
        // PSR-11: usar container se disponível
        if ($this->container !== null) {
            return $this->container->get($class);
        }

        // Fallback legado (ARQ-012): Reflection manual para PDO
        $ref = new \ReflectionClass($class);
        $ctor = $ref->getConstructor();

        if ($ctor === null || $ctor->getNumberOfParameters() === 0) {
            return new $class();
        }

        $params = $ctor->getParameters();
        $args = [];

        foreach ($params as $param) {
            $type = $param->getType();
            $typeName = $type instanceof \ReflectionNamedType ? $type->getName() : null;

            if ($typeName === 'PDO' || $typeName === \PDO::class) {
                $args[] = \Database::getInstance();
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } else {
                return new $class();
            }
        }

        return $ref->newInstanceArgs($args);
    }

    // ══════════════════════════════════════════════════════════════
    // 404
    // ══════════════════════════════════════════════════════════════

    /**
     * Trata páginas não encontradas: IP Guard + view 404.
     */
    private function handle404(): void
    {
        http_response_code(404);

        // IP Guard: registra hit 404 e verifica blacklist
        if (IpGuard::isBlacklisted()) {
            header('Retry-After: 3600');
            echo '<!DOCTYPE html><html><head><title>403</title></head><body><h1>403 Forbidden</h1></body></html>';
            exit;
        }
        IpGuard::register404Hit();

        require 'app/views/errors/404.php';
    }
}
