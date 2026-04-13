<?php
namespace Akti\Middleware;

use Akti\Core\Log;

/**
 * SentryMiddleware — Error Tracking Integration
 *
 * Captura exceções e erros não tratados e envia para o Sentry (quando configurado).
 * Se o Sentry não estiver configurado (SENTRY_DSN não definida), opera em modo silencioso
 * e apenas registra no Log estruturado local.
 *
 * Uso:
 *   No index.php, chamar SentryMiddleware::init() ANTES de qualquer lógica.
 *
 * Configuração:
 *   - Variável de ambiente: SENTRY_DSN
 *   - Opcional: SENTRY_ENVIRONMENT (default: 'production')
 *   - Opcional: SENTRY_TRACES_SAMPLE_RATE (default: 0.2)
 *
 * Instalação do Sentry:
 *   composer require sentry/sentry
 */
class SentryMiddleware
{
    /** @var bool */
    private static $initialized = false;

    /** @var bool */
    private static $sentryAvailable = false;

    /**
     * Inicializa o middleware de captura de erros.
     * Se o Sentry estiver instalado e configurado, usa-o.
     * Caso contrário, registra erros no Log estruturado local.
     */
    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }
        self::$initialized = true;

        $dsn = getenv('SENTRY_DSN') ?: '';

        // Tentar inicializar Sentry se o pacote estiver instalado
        if ($dsn && class_exists('\Sentry\SentrySdk')) {
            try {
                \Sentry\init([
                    'dsn'                  => $dsn,
                    'environment'          => getenv('SENTRY_ENVIRONMENT') ?: 'production',
                    'traces_sample_rate'   => (float)(getenv('SENTRY_TRACES_SAMPLE_RATE') ?: 0.2),
                    'send_default_pii'     => false,
                    'max_breadcrumbs'      => 50,
                    'attach_stacktrace'    => true,
                ]);
                self::$sentryAvailable = true;
            } catch (\Throwable $e) {
                Log::channel('general')->warning('Sentry init failed: ' . $e->getMessage());
            }
        }

        // Registrar handlers globais
        set_exception_handler([self::class, 'handleException']);
        set_error_handler([self::class, 'handleError']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    /**
     * Handler global de exceções não capturadas.
     */
    public static function handleException(\Throwable $exception): void
    {
        // Log estruturado local (sempre)
        Log::channel('general')->critical('Uncaught exception: ' . $exception->getMessage(), [
            'class'   => get_class($exception),
            'file'    => $exception->getFile(),
            'line'    => $exception->getLine(),
            'trace'   => $exception->getTraceAsString(),
        ]);

        // Enviar para Sentry (se disponível)
        if (self::$sentryAvailable) {
            try {
                \Sentry\captureException($exception);
            } catch (\Throwable $e) {
                // Fallback silencioso
            }
        }

        // Exibir página de erro amigável (se não for AJAX)
        if (!self::isAjax()) {
            http_response_code(500);
            if (file_exists('app/views/errors/500.php')) {
                $errorException = $exception;
                require 'app/views/errors/500.php';
            } else {
                echo '<h1>Erro interno do servidor</h1>';
                echo '<p>Ocorreu um erro inesperado. Por favor, tente novamente.</p>';
            }
        } else {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error'   => 'Erro interno do servidor.',
            ]);
        }
    }

    /**
     * Handler global de erros PHP.
     *
     * @return bool
     */
    public static function handleError(int $severity, string $message, string $file, int $line): bool
    {
        // Ignorar erros suprimidos com @
        if (!(error_reporting() & $severity)) {
            return false;
        }

        $level = self::severityToLogLevel($severity);

        Log::channel('general')->$level("PHP Error: {$message}", [
            'severity' => $severity,
            'file'     => $file,
            'line'     => $line,
        ]);

        if (self::$sentryAvailable) {
            try {
                \Sentry\captureException(
                    new \ErrorException($message, 0, $severity, $file, $line)
                );
            } catch (\Throwable $e) {
                // Fallback silencioso
            }
        }

        // Erros fatais devem parar a execução
        if (in_array($severity, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
            return false;
        }

        return true;
    }

    /**
     * Handler de shutdown — captura erros fatais.
     */
    public static function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            Log::channel('general')->critical('Fatal error on shutdown', [
                'message' => $error['message'],
                'file'    => $error['file'],
                'line'    => $error['line'],
            ]);

            if (self::$sentryAvailable) {
                try {
                    \Sentry\captureException(
                        new \ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line'])
                    );
                } catch (\Throwable $e) {
                    // Fallback silencioso
                }
            }
        }
    }

    /**
     * Enriquece o contexto do Sentry com dados do usuário/tenant.
     */
    public static function setUserContext(int $userId, ?string $email = null, ?int $tenantId = null): void
    {
        if (!self::$sentryAvailable) {
            return;
        }

        try {
            \Sentry\configureScope(function (\Sentry\State\Scope $scope) use ($userId, $email, $tenantId) {
                $scope->setUser([
                    'id'    => $userId,
                    'email' => $email,
                ]);
                if ($tenantId) {
                    $scope->setTag('tenant_id', (string) $tenantId);
                }
            });
        } catch (\Throwable $e) {
            // Silencioso
        }
    }

    /**
     * Adiciona breadcrumb para rastreamento.
     */
    public static function addBreadcrumb(string $category, string $message, array $data = []): void
    {
        if (!self::$sentryAvailable) {
            return;
        }

        try {
            \Sentry\addBreadcrumb(new \Sentry\Breadcrumb(
                \Sentry\Breadcrumb::LEVEL_INFO,
                \Sentry\Breadcrumb::TYPE_DEFAULT,
                $category,
                $message,
                $data
            ));
        } catch (\Throwable $e) {
            // Silencioso
        }
    }

    /**
     * Converte severidade PHP em nível de log PSR-3.
     */
    private static function severityToLogLevel(int $severity): string
    {
        switch ($severity) {
            case E_ERROR:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_USER_ERROR:
                return 'error';
            case E_WARNING:
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:
            case E_USER_WARNING:
                return 'warning';
            case E_NOTICE:
            case E_USER_NOTICE:
                return 'notice';
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
                return 'info';
            default:
                return 'warning';
        }
    }

    /**
     * Detecta se a requisição é AJAX.
     */
    private static function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
