<?php
namespace Akti\Core;

use Akti\Core\EventDispatcher;
use Akti\Core\Event;

/**
 * Security — Proteção CSRF centralizada para o sistema Akti.
 *
 * Responsabilidades:
 * - Gerar token CSRF criptograficamente seguro
 * - Armazenar token na sessão
 * - Validar token enviado pelo formulário ou header AJAX
 * - Rotacionar token periodicamente (a cada 30 minutos)
 * - Registrar falhas de validação em /storage/logs/security.log
 *
 * Uso em formulários:
 *   <input type="hidden" name="csrf_token" value="<?= \Akti\Core\Security::generateCsrfToken() ?>">
 *   ou via helper: <?= csrf_field() ?>
 *
 * Uso em AJAX (jQuery):
 *   O token é lido da meta tag <meta name="csrf-token"> e enviado via header X-CSRF-TOKEN.
 *
 * @package Akti\Core
 * @see     app/middleware/CsrfMiddleware.php
 * @see     app/utils/form_helper.php
 * @see     PROJECT_RULES.md — Módulo: Segurança — Proteção CSRF
 */
class Security
{
    /**
     * Tempo máximo de vida do token em segundos (30 minutos).
     * Após esse tempo, um novo token é gerado automaticamente.
     */
    private const TOKEN_LIFETIME = 1800;

    /**
     * Tempo de graça para o token anterior em segundos (5 minutos).
     * Permite que formulários abertos antes da rotação ainda sejam aceitos
     * (ex: usuário com múltiplas abas abertas).
     */
    private const TOKEN_GRACE_PERIOD = 300;

    /**
     * Caminho do arquivo de log de segurança (relativo à raiz do projeto).
     */
    private const LOG_FILE = 'storage/logs/security.log';

    // ══════════════════════════════════════════════════════════════
    // Geração de Token
    // ══════════════════════════════════════════════════════════════

    /**
     * Gera (ou reutiliza) um token CSRF criptograficamente seguro.
     *
     * Se o token atual ainda é válido (dentro do lifetime), retorna o existente.
     * Caso contrário, gera um novo token, salva o anterior como grace token
     * e armazena na sessão.
     *
     * @return string Token CSRF (64 caracteres hexadecimais)
     */
    public static function generateCsrfToken(): string
    {
        // Se já existe um token válido (não expirado), reutilizar
        if (
            isset($_SESSION['csrf_token'], $_SESSION['csrf_token_time'])
            && (time() - $_SESSION['csrf_token_time']) < self::TOKEN_LIFETIME
        ) {
            return $_SESSION['csrf_token'];
        }

        // Salvar token anterior como grace token (para formulários abertos em outras abas)
        if (isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token_previous']      = $_SESSION['csrf_token'];
            $_SESSION['csrf_token_previous_time']  = $_SESSION['csrf_token_time'] ?? time();
        }

        // Gerar novo token criptograficamente seguro
        $token = bin2hex(random_bytes(32));

        $_SESSION['csrf_token']      = $token;
        $_SESSION['csrf_token_time'] = time();

        return $token;
    }

    /**
     * Retorna o token CSRF atual da sessão (sem gerar novo).
     * Útil para injeção em meta tags e headers.
     *
     * @return string|null Token atual ou null se não existir
     */
    public static function getToken(): ?string
    {
        return $_SESSION['csrf_token'] ?? null;
    }

    // ══════════════════════════════════════════════════════════════
    // Validação de Token
    // ══════════════════════════════════════════════════════════════

    /**
     * Valida o token CSRF recebido contra o token da sessão.
     *
     * Aceita tanto o token atual quanto o token anterior (grace period)
     * para evitar falsos positivos quando o token é rotacionado entre
     * o carregamento do formulário e a submissão.
     *
     * Usa hash_equals() para evitar timing attacks.
     *
     * @param  string|null $token Token recebido do formulário ou header
     * @return bool true se válido, false se inválido
     */
    public static function validateCsrfToken(?string $token): bool
    {
        if ($token === null || $token === '') {
            return false;
        }

        // 1. Validar contra o token atual
        $sessionToken = $_SESSION['csrf_token'] ?? null;
        if ($sessionToken !== null && hash_equals($sessionToken, $token)) {
            return true;
        }

        // 2. Validar contra o token anterior (grace period)
        $previousToken = $_SESSION['csrf_token_previous'] ?? null;
        $previousTime  = $_SESSION['csrf_token_previous_time'] ?? 0;

        if (
            $previousToken !== null
            && (time() - $previousTime) < (self::TOKEN_LIFETIME + self::TOKEN_GRACE_PERIOD)
            && hash_equals($previousToken, $token)
        ) {
            return true;
        }

        return false;
    }

    // ══════════════════════════════════════════════════════════════
    // Log de Falhas
    // ══════════════════════════════════════════════════════════════

    /**
     * Registra uma falha de validação CSRF no log de segurança.
     *
     * Informações registradas:
     * - Data e hora
     * - IP do usuário
     * - Rota acessada (page + action)
     * - Método HTTP
     * - Token recebido (parcial — primeiros 8 caracteres)
     * - User ID (se autenticado)
     *
     * @param string|null $receivedToken Token que foi recebido (para log parcial)
     */
    public static function logCsrfFailure(?string $receivedToken = null): void
    {
        $logFile = (defined('AKTI_BASE_PATH') ? AKTI_BASE_PATH : __DIR__ . '/../../') . self::LOG_FILE;
        $logDir  = dirname($logFile);

        // Criar diretório se não existir
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $timestamp    = date('Y-m-d H:i:s');
        $ip           = self::getClientIp();
        $method       = $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN';
        $page         = $_GET['page'] ?? 'home';
        $action       = $_GET['action'] ?? 'index';
        $route        = "?page={$page}&action={$action}";
        $userId       = $_SESSION['user_id'] ?? 'anonymous';
        $tokenPartial = $receivedToken ? substr($receivedToken, 0, 8) . '...' : '(empty)';

        $entry = sprintf(
            "[%s] CSRF validation failed | IP: %s | Route: %s | Method: %s | Token: %s | User: %s\n",
            $timestamp,
            $ip,
            $route,
            $method,
            $tokenPartial,
            $userId
        );

        @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
    }

    // ══════════════════════════════════════════════════════════════
    // Resposta de Erro
    // ══════════════════════════════════════════════════════════════

    /**
     * Trata uma falha de validação CSRF: loga, retorna 403 e encerra.
     *
     * Para requisições AJAX, retorna JSON.
     * Para requisições normais, renderiza a view 403.
     *
     * @param string|null $receivedToken Token recebido (para log)
     */
    public static function handleCsrfFailure(?string $receivedToken = null): void
    {
        // Registrar falha no log
        self::logCsrfFailure($receivedToken);

        EventDispatcher::dispatch('core.security.access_denied', new Event('core.security.access_denied', [
            'page' => $_GET['page'] ?? '',
            'action' => $_GET['action'] ?? '',
            'reason' => 'csrf_failure',
        ]));

        http_response_code(403);

        // Detectar se é AJAX
        $isAjax = self::isAjaxRequest();

        if ($isAjax) {
            if (!headers_sent()) {
                header('Content-Type: application/json');
            }
            // Enviar o token atualizado para que o client possa tentar novamente
            // sem precisar recarregar a página (seguro: Same-Origin Policy impede
            // leitura cross-origin).
            $freshToken = self::generateCsrfToken();
            echo json_encode([
                'success' => false,
                'message' => 'Requisição inválida. Atualize a página e tente novamente.',
                'csrf_error' => true,
                'new_token' => $freshToken,
            ]);
            exit;
        }

        // Requisição normal — renderizar página de erro
        $errorFile = (defined('AKTI_BASE_PATH') ? AKTI_BASE_PATH : __DIR__ . '/../../') . 'app/views/errors/403.php';
        if (file_exists($errorFile)) {
            require $errorFile;
        } else {
            echo '<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body>';
            echo '<h1>403 — Requisição Inválida</h1>';
            echo '<p>Requisição inválida. Atualize a página e tente novamente.</p>';
            echo '</body></html>';
        }
        exit;
    }

    // ══════════════════════════════════════════════════════════════
    // Helpers internos
    // ══════════════════════════════════════════════════════════════

    /**
     * Detecta se a requisição atual é AJAX.
     */
    private static function isAjaxRequest(): bool
    {
        $xhrHeader   = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        $acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
        $contentType  = $_SERVER['CONTENT_TYPE'] ?? '';

        return (strtolower($xhrHeader) === 'xmlhttprequest')
            || (stripos($acceptHeader, 'application/json') !== false)
            || (stripos($contentType, 'application/json') !== false);
    }

    /**
     * Obtém o IP real do cliente (respeitando proxies).
     */
    private static function getClientIp(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                // X-Forwarded-For pode ter múltiplos IPs — pegar o primeiro
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }
}
