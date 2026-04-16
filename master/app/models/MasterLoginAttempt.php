<?php
/**
 * Model: MasterLoginAttempt
 * Gerencia tentativas de login e rate-limiting no painel master.
 */

class MasterLoginAttempt
{
    private $db;

    /** Tentativas antes de exibir reCAPTCHA */
    const CAPTCHA_THRESHOLD = 3;

    /** Tentativas antes de bloquear */
    const BLOCK_THRESHOLD = 5;

    /** Janela de tempo em minutos */
    const WINDOW_MINUTES = 15;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Registra uma tentativa de login.
     */
    public function record(string $ip, string $email, bool $success): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO master_login_attempts (ip_address, email, success) VALUES (:ip, :email, :success)'
        );
        $stmt->execute([
            ':ip' => $ip,
            ':email' => $email,
            ':success' => $success ? 1 : 0,
        ]);
    }

    /**
     * Conta tentativas falhas de um IP nos últimos N minutos.
     */
    public function countRecentFailures(string $ip): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM master_login_attempts 
             WHERE ip_address = :ip AND success = 0 
             AND attempted_at >= DATE_SUB(NOW(), INTERVAL :minutes MINUTE)'
        );
        $stmt->execute([
            ':ip' => $ip,
            ':minutes' => self::WINDOW_MINUTES,
        ]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Verifica se o IP está bloqueado (>= BLOCK_THRESHOLD falhas).
     */
    public function isBlocked(string $ip): bool
    {
        return $this->countRecentFailures($ip) >= self::BLOCK_THRESHOLD;
    }

    /**
     * Verifica se deve exibir reCAPTCHA (>= CAPTCHA_THRESHOLD falhas).
     */
    public function requiresCaptcha(string $ip): bool
    {
        return $this->countRecentFailures($ip) >= self::CAPTCHA_THRESHOLD;
    }

    /**
     * Limpa tentativas falhas de um IP (após login bem-sucedido).
     */
    public function clearFailures(string $ip): void
    {
        $stmt = $this->db->prepare(
            'DELETE FROM master_login_attempts WHERE ip_address = :ip AND success = 0'
        );
        $stmt->execute([':ip' => $ip]);
    }

    /**
     * Minutos restantes de bloqueio.
     */
    public function getBlockMinutesRemaining(string $ip): int
    {
        $stmt = $this->db->prepare(
            'SELECT attempted_at FROM master_login_attempts 
             WHERE ip_address = :ip AND success = 0 
             AND attempted_at >= DATE_SUB(NOW(), INTERVAL :minutes MINUTE)
             ORDER BY attempted_at ASC LIMIT 1'
        );
        $stmt->execute([
            ':ip' => $ip,
            ':minutes' => self::WINDOW_MINUTES,
        ]);
        $oldest = $stmt->fetchColumn();
        if (!$oldest) {
            return 0;
        }
        $unblockTime = strtotime($oldest) + (self::WINDOW_MINUTES * 60);
        $remaining = ceil(($unblockTime - time()) / 60);
        return max(0, (int) $remaining);
    }

    /**
     * Limpa registros antigos (manutenção).
     */
    public function purgeOld(int $days = 7): int
    {
        $stmt = $this->db->prepare(
            'DELETE FROM master_login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL :days DAY)'
        );
        $stmt->execute([':days' => $days]);
        return $stmt->rowCount();
    }
}
