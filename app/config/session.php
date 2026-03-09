<?php
/**
 * Configuração segura de sessão — Akti
 *
 * Este arquivo DEVE ser incluído ANTES de session_start().
 * Configura cookies de sessão com flags de segurança e define
 * o tempo máximo de vida da sessão no garbage collector.
 *
 * O timeout efetivo por inatividade é controlado pela chave
 * "session_timeout_minutes" em company_settings (padrão: 60 min).
 * O gc_maxlifetime aqui é apenas um fallback do PHP.
 */

// ── Configurações de cookie de sessão ────────────────────────────────
ini_set('session.cookie_httponly', '1');     // JS não acessa o cookie de sessão
ini_set('session.cookie_samesite', 'Strict'); // Previne CSRF via cross-site
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? '1' : '0');
// ↑ Secure=true apenas quando HTTPS está ativo (evita quebrar dev local em HTTP)

ini_set('session.use_strict_mode', '1');    // Rejeita IDs de sessão não gerados pelo servidor
ini_set('session.use_only_cookies', '1');   // Não aceita session ID via query string
ini_set('session.gc_maxlifetime', '3600');  // GC coleta sessões após 1h sem atividade

// Nome customizado do cookie (evita fingerprinting pelo default "PHPSESSID")
session_name('AKTI_SID');

/**
 * Classe auxiliar para controle de sessão (timeout por inatividade).
 *
 * Uso no index.php (após session_start e após resolução de tenant):
 *   SessionGuard::checkInactivity($db);     // verifica timeout
 *   SessionGuard::touch();                   // atualiza last_activity
 */
class SessionGuard
{
    /** Timeout padrão em minutos (usado quando company_settings não tem valor) */
    const DEFAULT_TIMEOUT_MINUTES = 60;

    /** Margem de aviso antes de expirar (em minutos) */
    const WARNING_MINUTES = 5;

    /**
     * Verifica se a sessão expirou por inatividade.
     * Se expirou, destrói a sessão e redireciona para login.
     *
     * @param PDO|null $db  Conexão com banco do tenant (para ler company_settings).
     *                      Se null, usa o timeout padrão.
     */
    public static function checkInactivity(?PDO $db = null): void
    {
        // Só verifica se o usuário está logado
        if (!isset($_SESSION['user_id'])) {
            return;
        }

        // Se não há timestamp de última atividade, define agora (primeira vez)
        if (!isset($_SESSION['last_activity'])) {
            $_SESSION['last_activity'] = time();
            return;
        }

        $timeoutMinutes = self::getTimeoutMinutes($db);
        $timeoutSeconds = $timeoutMinutes * 60;
        $elapsed = time() - $_SESSION['last_activity'];

        if ($elapsed > $timeoutSeconds) {
            // Sessão expirada
            $userId = $_SESSION['user_id'] ?? null;

            // Log de expiração (silencioso, sem depender de model)
            try {
                if ($db) {
                    $stmt = $db->prepare("INSERT INTO system_logs (user_id, action, details, ip_address, created_at) VALUES (:uid, 'SESSION_EXPIRED', 'Sessão expirada por inatividade', :ip, NOW())");
                    $stmt->execute([':uid' => $userId, ':ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
                }
            } catch (\Throwable $e) {
                // Silencioso
            }

            session_unset();
            session_destroy();

            // Detectar se é AJAX
            $isAjax = (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest')
                || (stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);

            if ($isAjax) {
                if (!headers_sent()) header('Content-Type: application/json');
                http_response_code(401);
                echo json_encode(['success' => false, 'session_expired' => true, 'message' => 'Sessão expirada por inatividade.']);
                exit;
            }

            header('Location: ?page=login&session_expired=1');
            exit;
        }
    }

    /**
     * Atualiza o timestamp de última atividade.
     * Chamar em cada request autenticada (após checkInactivity).
     */
    public static function touch(): void
    {
        if (isset($_SESSION['user_id'])) {
            $_SESSION['last_activity'] = time();
        }
    }

    /**
     * Obtém o timeout configurado pelo admin em company_settings.
     * Retorna em minutos. Mínimo: 5 min.
     *
     * @param PDO|null $db  Conexão com o banco do tenant
     * @return int
     */
    public static function getTimeoutMinutes(?PDO $db = null): int
    {
        // Cache em sessão para não consultar o banco em toda request
        if (isset($_SESSION['_session_timeout_minutes']) && isset($_SESSION['_session_timeout_cached_at'])) {
            // Recache a cada 5 minutos
            if ((time() - $_SESSION['_session_timeout_cached_at']) < 300) {
                return (int) $_SESSION['_session_timeout_minutes'];
            }
        }

        $minutes = self::DEFAULT_TIMEOUT_MINUTES;

        if ($db) {
            try {
                $stmt = $db->prepare("SELECT setting_value FROM company_settings WHERE setting_key = 'session_timeout_minutes' LIMIT 1");
                $stmt->execute();
                $val = $stmt->fetchColumn();
                if ($val !== false && is_numeric($val) && (int) $val >= 5) {
                    $minutes = (int) $val;
                }
            } catch (\Throwable $e) {
                // Silencioso — usa default
            }
        }

        // Cachear na sessão
        $_SESSION['_session_timeout_minutes'] = $minutes;
        $_SESSION['_session_timeout_cached_at'] = time();

        return $minutes;
    }

    /**
     * Retorna dados necessários para o JavaScript do modal de aviso de expiração.
     * Usado pelo footer.php para injetar variáveis JS.
     *
     * @param PDO|null $db
     * @return array{timeout_seconds: int, warning_seconds: int, remaining_seconds: int}
     */
    public static function getJsSessionData(?PDO $db = null): array
    {
        $timeoutMinutes = self::getTimeoutMinutes($db);
        $timeoutSeconds = $timeoutMinutes * 60;
        $warningSeconds = self::WARNING_MINUTES * 60;
        $lastActivity = $_SESSION['last_activity'] ?? time();
        $elapsed = time() - $lastActivity;
        $remaining = max(0, $timeoutSeconds - $elapsed);

        return [
            'timeout_seconds'  => $timeoutSeconds,
            'warning_seconds'  => $warningSeconds,
            'remaining_seconds' => $remaining,
        ];
    }
}
