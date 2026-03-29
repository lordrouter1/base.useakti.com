<?php
namespace Akti\Middleware;

use PDO;

/**
 * PortalAuthMiddleware — Verificação de autenticação do Portal do Cliente.
 *
 * Verifica se o cliente está logado no portal via $_SESSION['portal_customer_id'].
 * Separado completamente da autenticação admin ($_SESSION['user_id']).
 * Integra com tabela customer_portal_sessions para multi-device tracking.
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

        // Validar sessão na tabela (se configurado)
        self::validateDbSession();
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

        // Registrar sessão na tabela customer_portal_sessions
        self::createDbSession($accessId, $customerId);
    }

    /**
     * Encerra a sessão do portal (sem destruir a sessão admin se existir).
     *
     * @return void
     */
    public static function logout(): void
    {
        // Remover sessão da tabela
        self::destroyDbSession();

        unset(
            $_SESSION['portal_customer_id'],
            $_SESSION['portal_access_id'],
            $_SESSION['portal_customer_name'],
            $_SESSION['portal_email'],
            $_SESSION['portal_lang'],
            $_SESSION['portal_last_activity'],
            $_SESSION['portal_cart'],
            $_SESSION['portal_2fa_verified'],
            $_SESSION['portal_2fa_pending']
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

    // ══════════════════════════════════════════════
    // SESSÕES PERSISTENTES (customer_portal_sessions)
    // ══════════════════════════════════════════════

    /**
     * Registra a sessão corrente na tabela customer_portal_sessions.
     */
    private static function createDbSession(int $accessId, int $customerId): void
    {
        try {
            $db = self::getConnection();
            if (!$db) {
                return;
            }

            $sessionId = session_id();
            $ip        = self::getClientIp();
            $ua        = mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);

            // Remover sessão anterior com mesmo session_id (se existir)
            $del = $db->prepare("DELETE FROM customer_portal_sessions WHERE session_id = :sid OR session_token = :token");
            $del->execute([':sid' => $sessionId, ':token' => $sessionId]);

            // Inserir nova sessão
            $stmt = $db->prepare(
                "INSERT INTO customer_portal_sessions
                 (access_id, customer_id, session_token, session_id, ip_address, user_agent, last_activity, created_at, expires_at)
                 VALUES (:aid, :cid, :token, :sid, :ip, :ua, NOW(), NOW(), DATE_ADD(NOW(), INTERVAL 2 HOUR))"
            );
            $stmt->execute([
                ':aid'   => $accessId,
                ':cid'   => $customerId,
                ':token' => $sessionId,
                ':sid'   => $sessionId,
                ':ip'    => $ip,
                ':ua'    => $ua,
            ]);
        } catch (\Throwable $e) {
            // Silenciar — sessão funciona sem a tabela
        }
    }

    /**
     * Remove a sessão corrente da tabela.
     */
    private static function destroyDbSession(): void
    {
        try {
            $db = self::getConnection();
            if (!$db) {
                return;
            }

            $sessionId = session_id();
            $stmt = $db->prepare("DELETE FROM customer_portal_sessions WHERE session_id = :sid");
            $stmt->execute([':sid' => $sessionId]);
        } catch (\Throwable $e) {
            // Silenciar
        }
    }

    /**
     * Valida se a sessão corrente ainda existe na tabela (não foi forçado logout).
     * Se a sessão foi removida pelo admin, desloga o cliente.
     */
    private static function validateDbSession(): void
    {
        try {
            $db = self::getConnection();
            if (!$db) {
                return;
            }

            $sessionId = session_id();
            $stmt = $db->prepare(
                "SELECT id FROM customer_portal_sessions WHERE session_id = :sid LIMIT 1"
            );
            $stmt->execute([':sid' => $sessionId]);

            if (!$stmt->fetch()) {
                // Sessão removida pelo admin → forçar logout
                self::logout();
                header('Location: ?page=portal&action=login&forced=1');
                exit;
            }

            // Atualizar last_activity na tabela
            $upd = $db->prepare(
                "UPDATE customer_portal_sessions SET last_activity = NOW() WHERE session_id = :sid"
            );
            $upd->execute([':sid' => $sessionId]);
        } catch (\Throwable $e) {
            // Silenciar — não bloquear o usuário se a tabela não existir
        }
    }

    /**
     * Verifica se o 2FA está pendente de verificação.
     *
     * @return bool
     */
    public static function is2faPending(): bool
    {
        return !empty($_SESSION['portal_2fa_pending']) && empty($_SESSION['portal_2fa_verified']);
    }

    /**
     * Marca 2FA como pendente.
     */
    public static function set2faPending(bool $pending = true): void
    {
        $_SESSION['portal_2fa_pending'] = $pending;
        if (!$pending) {
            $_SESSION['portal_2fa_verified'] = true;
        }
    }

    /**
     * Marca 2FA como verificado.
     */
    public static function set2faVerified(): void
    {
        $_SESSION['portal_2fa_verified'] = true;
        unset($_SESSION['portal_2fa_pending']);
    }

    /**
     * Obtém conexão PDO para operações de sessão.
     */
    private static function getConnection(): ?PDO
    {
        try {
            return (new \Database())->getConnection();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
