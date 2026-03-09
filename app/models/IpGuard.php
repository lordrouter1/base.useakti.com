<?php
namespace Akti\Models;

use PDO;
use PDOException;

/**
 * IpGuard — Detecção de flood 404 e blacklist automática de IPs.
 *
 * Opera no banco MASTER (akti_master), pois a blacklist é global (cross-tenant).
 * Utilizado pelo index.php no handler de 404 para registrar hits e bloquear
 * IPs que excedam o threshold configurado dentro de uma janela de tempo.
 *
 * Tabelas: ip_404_hits, ip_blacklist  (migration: update_20260309_ip_blacklist.sql)
 */
class IpGuard
{
    // ─── Configuração ────────────────────────────────────────────────
    /** Número máximo de 404s permitidos na janela de tempo antes do bloqueio */
    const THRESHOLD = 30;

    /** Janela de tempo em segundos (padrão: 60 = 1 minuto) */
    const WINDOW_SECONDS = 60;

    /** Duração padrão do bloqueio em horas (null = permanente) */
    const BLOCK_HOURS = 24;

    /** Tamanho máximo do path armazenado (truncado para economizar espaço) */
    const MAX_PATH_LENGTH = 2048;

    /** Tamanho máximo do user-agent armazenado */
    const MAX_UA_LENGTH = 512;

    // ─── Conexão ─────────────────────────────────────────────────────
    /** @var PDO|null */
    private static $conn = null;

    /**
     * Obtém (ou cria) uma conexão PDO com o banco master (akti_master).
     * A conexão é reutilizada durante toda a request (singleton).
     */
    private static function getConnection(): ?PDO
    {
        if (self::$conn !== null) {
            return self::$conn;
        }

        try {
            // Usa a mesma lógica de getMasterConfig do TenantManager
            $host    = getenv('AKTI_MASTER_DB_HOST') ?: getenv('AKTI_DB_HOST') ?: 'localhost';
            $port    = (int) (getenv('AKTI_MASTER_DB_PORT') ?: getenv('AKTI_DB_PORT') ?: 3306);
            $dbName  = getenv('AKTI_MASTER_DB_NAME') ?: 'akti_master';
            $user    = getenv('AKTI_MASTER_DB_USER') ?: getenv('AKTI_DB_USER') ?: 'akti_sis_usr';
            $pass    = getenv('AKTI_MASTER_DB_PASS') ?: getenv('AKTI_DB_PASS') ?: 'kP9!vR2@mX6#zL5$';
            $charset = getenv('AKTI_MASTER_DB_CHARSET') ?: 'utf8mb4';

            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $port, $dbName, $charset);

            self::$conn = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT            => 2, // timeout curto para não atrasar a resposta 404
            ]);

            return self::$conn;
        } catch (PDOException $e) {
            // Falha silenciosa — não pode impedir a exibição da página 404
            error_log('[IpGuard] Falha ao conectar ao banco master: ' . $e->getMessage());
            return null;
        }
    }

    // ─── Sanitização ─────────────────────────────────────────────────

    /**
     * Obtém o IP real do visitante, considerando proxies confiáveis.
     * Retorna IPv4 ou IPv6 (máx. 45 chars conforme coluna VARCHAR(45)).
     */
    public static function getClientIp(): string
    {
        // Ordem de prioridade para proxy reverso (Nginx + CloudFlare)
        $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                // X-Forwarded-For pode conter vários IPs separados por vírgula
                $ip = trim(explode(',', $_SERVER[$header])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Sanitiza e trunca o path da requisição.
     */
    private static function sanitizePath(string $path): string
    {
        $path = filter_var($path, FILTER_SANITIZE_URL);
        return mb_substr($path, 0, self::MAX_PATH_LENGTH);
    }

    /**
     * Sanitiza e trunca o user-agent.
     */
    private static function sanitizeUserAgent(?string $ua): ?string
    {
        if ($ua === null || $ua === '') {
            return null;
        }
        // Remove caracteres de controle
        $ua = preg_replace('/[\x00-\x1F\x7F]/', '', $ua);
        return mb_substr($ua, 0, self::MAX_UA_LENGTH);
    }

    // ─── API pública ─────────────────────────────────────────────────

    /**
     * Verifica se um IP está na blacklist ativa (não expirada).
     *
     * @param  string|null $ip  IP a verificar (null = IP do visitante atual)
     * @return bool
     */
    public static function isBlacklisted(?string $ip = null): bool
    {
        $ip   = $ip ?? self::getClientIp();
        $conn = self::getConnection();
        if ($conn === null) {
            return false; // sem conexão → não bloqueia (fail-open)
        }

        try {
            $sql = "SELECT 1 FROM ip_blacklist
                    WHERE ip_address = :ip
                      AND is_active = 1
                      AND (expires_at IS NULL OR expires_at > NOW())
                    LIMIT 1";

            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':ip', $ip);
            $stmt->execute();

            return (bool) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log('[IpGuard] isBlacklisted error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Registra um hit 404 para o IP atual.
     * Se o threshold for ultrapassado dentro da janela de tempo,
     * o IP é automaticamente adicionado à blacklist.
     *
     * Deve ser chamado ANTES de renderizar a view 404 (no index.php).
     * Toda a operação é silenciosa — nunca lança exceção para o chamador.
     */
    public static function register404Hit(): void
    {
        try {
            $ip   = self::getClientIp();
            $conn = self::getConnection();
            if ($conn === null) {
                return;
            }

            // Se já está bloqueado, não precisa registrar novamente
            if (self::isBlacklisted($ip)) {
                return;
            }

            $path = self::sanitizePath($_SERVER['REQUEST_URI'] ?? '');
            $ua   = self::sanitizeUserAgent($_SERVER['HTTP_USER_AGENT'] ?? null);

            // 1. Inserir o hit
            $sqlInsert = "INSERT INTO ip_404_hits (ip_address, path, user_agent) VALUES (:ip, :path, :ua)";
            $stmt = $conn->prepare($sqlInsert);
            $stmt->bindValue(':ip', $ip);
            $stmt->bindValue(':path', $path);
            $stmt->bindValue(':ua', $ua);
            $stmt->execute();

            // 2. Contar hits na janela de tempo
            $sqlCount = "SELECT COUNT(*) FROM ip_404_hits
                         WHERE ip_address = :ip
                           AND created_at >= DATE_SUB(NOW(), INTERVAL :window SECOND)";
            $stmt = $conn->prepare($sqlCount);
            $stmt->bindValue(':ip', $ip);
            $stmt->bindValue(':window', self::WINDOW_SECONDS, PDO::PARAM_INT);
            $stmt->execute();
            $hitCount = (int) $stmt->fetchColumn();

            // 3. Se excedeu threshold → blacklist
            if ($hitCount >= self::THRESHOLD) {
                self::blacklistIp($ip, $hitCount, '404_flood');
            }
        } catch (PDOException $e) {
            error_log('[IpGuard] register404Hit error: ' . $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[IpGuard] register404Hit unexpected error: ' . $e->getMessage());
        }
    }

    /**
     * Adiciona um IP à blacklist.
     *
     * @param string      $ip     Endereço IP
     * @param int         $hits   Número de hits que motivaram o bloqueio
     * @param string      $reason Motivo (ex: '404_flood')
     * @param int|null    $hours  Duração em horas (null = usa BLOCK_HOURS; 0 = permanente)
     */
    public static function blacklistIp(string $ip, int $hits = 0, string $reason = '404_flood', ?int $hours = null): void
    {
        $conn = self::getConnection();
        if ($conn === null) {
            return;
        }

        $hours = $hours ?? self::BLOCK_HOURS;

        try {
            $expiresAt = null;
            if ($hours > 0) {
                $expiresAt = date('Y-m-d H:i:s', strtotime("+{$hours} hours"));
            }

            // UPSERT: se o IP já estiver na blacklist, atualiza hits/expiração
            $sql = "INSERT INTO ip_blacklist (ip_address, hits, reason, is_active, blocked_at, expires_at)
                    VALUES (:ip, :hits, :reason, 1, NOW(), :expires)
                    ON DUPLICATE KEY UPDATE
                        hits       = VALUES(hits),
                        reason     = VALUES(reason),
                        is_active  = 1,
                        blocked_at = NOW(),
                        expires_at = VALUES(expires_at)";

            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':ip', $ip);
            $stmt->bindValue(':hits', $hits, PDO::PARAM_INT);
            $stmt->bindValue(':reason', $reason);
            $stmt->bindValue(':expires', $expiresAt);
            $stmt->execute();

            error_log(sprintf(
                '[IpGuard] IP bloqueado: %s | hits=%d | reason=%s | expires=%s',
                $ip, $hits, $reason, $expiresAt ?? 'PERMANENTE'
            ));
        } catch (PDOException $e) {
            error_log('[IpGuard] blacklistIp error: ' . $e->getMessage());
        }
    }

    /**
     * Limpeza de registros antigos de ip_404_hits (mais de 7 dias).
     * Pode ser chamado periodicamente via cron ou manualmente.
     *
     * @param int $days Número de dias para manter os registros
     * @return int      Número de registros removidos
     */
    public static function purgeOldHits(int $days = 7): int
    {
        $conn = self::getConnection();
        if ($conn === null) {
            return 0;
        }

        try {
            $sql = "DELETE FROM ip_404_hits WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':days', $days, PDO::PARAM_INT);
            $stmt->execute();

            $deleted = $stmt->rowCount();
            if ($deleted > 0) {
                error_log("[IpGuard] Purged {$deleted} old 404 hit records (older than {$days} days).");
            }
            return $deleted;
        } catch (PDOException $e) {
            error_log('[IpGuard] purgeOldHits error: ' . $e->getMessage());
            return 0;
        }
    }
}
