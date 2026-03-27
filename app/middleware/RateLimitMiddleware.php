<?php
namespace Akti\Middleware;

use PDO;

/**
 * RateLimitMiddleware — Proteção contra burst de ações.
 *
 * Usa sessão como camada rápida (sem query SQL) e opcionalmente
 * persiste em tabela `rate_limit` para controle cross-session.
 *
 * Uso:
 *   RateLimitMiddleware::check('nfe_emit', 5); // 5s entre emissões
 *   RateLimitMiddleware::checkWithDb($db, 'nfe_emit', 5, 10);
 *
 * @package Akti\Middleware
 */
class RateLimitMiddleware
{
    /**
     * Verifica rate limit usando sessão (rápido, sem DB).
     *
     * @param string $action      Identificador da ação (ex: 'nfe_emit')
     * @param int    $minInterval Intervalo mínimo em segundos entre ações
     * @return array ['allowed' => bool, 'retry_after' => int] Segundos até liberação
     */
    public static function check(string $action, int $minInterval = 5): array
    {
        $userId = $_SESSION['user_id'] ?? 0;
        $key = "rate_limit_{$action}_{$userId}";
        $lastAttempt = $_SESSION[$key] ?? 0;
        $elapsed = time() - $lastAttempt;

        if ($elapsed < $minInterval) {
            return [
                'allowed'     => false,
                'retry_after' => $minInterval - $elapsed,
            ];
        }

        $_SESSION[$key] = time();
        return ['allowed' => true, 'retry_after' => 0];
    }

    /**
     * Verifica rate limit usando banco de dados (mais robusto, cross-session).
     *
     * @param PDO    $db           Conexão PDO
     * @param string $action       Identificador da ação
     * @param int    $minInterval  Intervalo mínimo em segundos entre ações
     * @param int    $maxPerMinute Máximo de ações por minuto (0 = sem limite)
     * @return array ['allowed' => bool, 'retry_after' => int, 'message' => string]
     */
    public static function checkWithDb(PDO $db, string $action, int $minInterval = 5, int $maxPerMinute = 10): array
    {
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            return ['allowed' => false, 'retry_after' => 0, 'message' => 'Sessão inválida.'];
        }

        try {
            // Verificar intervalo mínimo
            $stmt = $db->prepare(
                "SELECT attempted_at FROM rate_limit 
                 WHERE user_id = :uid AND action = :action 
                 ORDER BY attempted_at DESC LIMIT 1"
            );
            $stmt->execute([':uid' => $userId, ':action' => $action]);
            $lastRow = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($lastRow) {
                $lastTime = strtotime($lastRow['attempted_at']);
                $elapsed = time() - $lastTime;
                if ($elapsed < $minInterval) {
                    return [
                        'allowed'     => false,
                        'retry_after' => $minInterval - $elapsed,
                        'message'     => "Aguarde " . ($minInterval - $elapsed) . " segundo(s) entre ações.",
                    ];
                }
            }

            // Verificar limite por minuto
            if ($maxPerMinute > 0) {
                $stmt = $db->prepare(
                    "SELECT COUNT(*) FROM rate_limit 
                     WHERE user_id = :uid AND action = :action 
                     AND attempted_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)"
                );
                $stmt->execute([':uid' => $userId, ':action' => $action]);
                $count = (int) $stmt->fetchColumn();

                if ($count >= $maxPerMinute) {
                    return [
                        'allowed'     => false,
                        'retry_after' => 60,
                        'message'     => "Limite de {$maxPerMinute} ação(ões) por minuto atingido.",
                    ];
                }
            }

            // Registrar tentativa
            $stmt = $db->prepare(
                "INSERT INTO rate_limit (user_id, action, attempted_at) VALUES (:uid, :action, NOW())"
            );
            $stmt->execute([':uid' => $userId, ':action' => $action]);

            return ['allowed' => true, 'retry_after' => 0, 'message' => ''];
        } catch (\Throwable $e) {
            // Se a tabela não existir, fallback para sessão
            error_log('[RateLimitMiddleware] DB error: ' . $e->getMessage());
            return self::check($action, $minInterval);
        }
    }

    /**
     * Limpa registros antigos de rate limiting (> 24h).
     * Deve ser chamado via cron ou periodicamente.
     *
     * @param PDO $db Conexão PDO
     * @return int Registros removidos
     */
    public static function cleanup(PDO $db): int
    {
        try {
            $stmt = $db->prepare("DELETE FROM rate_limit WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
            $stmt->execute();
            return $stmt->rowCount();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
