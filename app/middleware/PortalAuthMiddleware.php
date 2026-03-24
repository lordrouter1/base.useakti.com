<?php
namespace Akti\Middleware;

/**
 * PortalAuthMiddleware — Verificação de autenticação do Portal do Cliente.
 *
 * Verifica se o cliente está logado no portal via $_SESSION['portal_customer_id'].
 * Separado completamente da autenticação admin ($_SESSION['user_id']).
 *
 * Uso no PortalController:
 *   PortalAuthMiddleware::check();  // redireciona se não logado
 *   $customerId = PortalAuthMiddleware::getCustomerId();
 *
 * @package Akti\Middleware
 */
class PortalAuthMiddleware
{
    /**
     * Verifica se o cliente está autenticado no portal.
     * Se não estiver, redireciona para a tela de login do portal.
     *
     * @return void
     */
    public static function check(): void
    {
        if (!self::isAuthenticated()) {
            header('Location: ?page=portal&action=login');
            exit;
        }
    }

    /**
     * Verifica se o cliente está autenticado (sem redirecionar).
     *
     * @return bool
     */
    public static function isAuthenticated(): bool
    {
        return isset($_SESSION['portal_customer_id']) && (int) $_SESSION['portal_customer_id'] > 0;
    }

    /**
     * Retorna o customer_id da sessão do portal.
     *
     * @return int|null
     */
    public static function getCustomerId(): ?int
    {
        return isset($_SESSION['portal_customer_id']) ? (int) $_SESSION['portal_customer_id'] : null;
    }

    /**
     * Retorna o access_id da sessão do portal.
     *
     * @return int|null
     */
    public static function getAccessId(): ?int
    {
        return isset($_SESSION['portal_access_id']) ? (int) $_SESSION['portal_access_id'] : null;
    }

    /**
     * Retorna o idioma do cliente na sessão.
     *
     * @return string
     */
    public static function getLang(): string
    {
        return $_SESSION['portal_lang'] ?? 'pt-br';
    }

    /**
     * Inicia a sessão do portal para o cliente.
     *
     * @param int    $customerId
     * @param int    $accessId
     * @param string $customerName
     * @param string $email
     * @param string $lang
     * @return void
     */
    public static function login(int $customerId, int $accessId, string $customerName, string $email, string $lang = 'pt-br'): void
    {
        $_SESSION['portal_customer_id']   = $customerId;
        $_SESSION['portal_access_id']     = $accessId;
        $_SESSION['portal_customer_name'] = $customerName;
        $_SESSION['portal_email']         = $email;
        $_SESSION['portal_lang']          = $lang;
        $_SESSION['portal_last_activity'] = time();
    }

    /**
     * Encerra a sessão do portal (sem destruir a sessão admin se existir).
     *
     * @return void
     */
    public static function logout(): void
    {
        unset(
            $_SESSION['portal_customer_id'],
            $_SESSION['portal_access_id'],
            $_SESSION['portal_customer_name'],
            $_SESSION['portal_email'],
            $_SESSION['portal_lang'],
            $_SESSION['portal_last_activity'],
            $_SESSION['portal_cart']
        );
    }

    /**
     * Atualiza o timestamp de última atividade.
     *
     * @return void
     */
    public static function touch(): void
    {
        $_SESSION['portal_last_activity'] = time();
    }

    /**
     * Verifica inatividade do portal (timeout configurável, padrão 60min).
     *
     * @param int $timeoutMinutes Timeout em minutos
     * @return void
     */
    public static function checkInactivity(int $timeoutMinutes = 60): void
    {
        if (!self::isAuthenticated()) {
            return;
        }

        $lastActivity = $_SESSION['portal_last_activity'] ?? 0;
        if (time() - $lastActivity > ($timeoutMinutes * 60)) {
            self::logout();
            header('Location: ?page=portal&action=login&expired=1');
            exit;
        }

        self::touch();
    }

    /**
     * Retorna o IP real do cliente.
     *
     * @return string
     */
    public static function getClientIp(): string
    {
        return $_SERVER['HTTP_CF_CONNECTING_IP']
            ?? $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['HTTP_X_REAL_IP']
            ?? $_SERVER['REMOTE_ADDR']
            ?? '0.0.0.0';
    }
}
