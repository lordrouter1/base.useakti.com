<?php
namespace Akti\Models;

use PDO;
use DateTime;
use DateTimeZone;
use RuntimeException;

/**
 * LoginAttempt — Proteção contra força bruta
 *
 * Registra tentativas de login e aplica rate-limiting por IP+email.
 * - >= 5 falhas em 10 min → bloqueio de 30 min
 * - >= 3 falhas em 10 min → exigir reCAPTCHA
 * - Limpeza automática de registros > 1h
 */
class LoginAttempt
{
    private $conn;

    // Configurações de rate-limit
    const MAX_ATTEMPTS       = 5;     // Bloqueio após N falhas
    const CAPTCHA_THRESHOLD  = 3;     // Exigir captcha após N falhas
    const WINDOW_MINUTES     = 10;    // Janela de contagem (minutos)
    const LOCKOUT_MINUTES    = 30;    // Duração do bloqueio (minutos)
    const CLEANUP_MINUTES    = 60;    // Limpar registros mais velhos que N minutos

    // Chave do reCAPTCHA — configurável via ENV
    // Gere as suas em https://www.google.com/recaptcha/admin
    const RECAPTCHA_SITE_KEY   = ''; // Preenchido via getenv ou hard-coded
    const RECAPTCHA_SECRET_KEY = '';

    /**
     * Construtor da classe LoginAttempt.
     *
     * @param \PDO $db Conexão PDO com o banco de dados
     */
    public function __construct(\PDO $db)
    {
        $this->conn = $db;
    }

    // ─────────────────────────────────────────
    // Chaves reCAPTCHA (prioriza variáveis de ambiente)
    // ─────────────────────────────────────────

    /**
     * Obtém dados específicos.
     * @return string
     */
    public static function getSiteKey(): string
    {
        return getenv('AKTI_RECAPTCHA_SITE_KEY') ?: static::RECAPTCHA_SITE_KEY;
    }

    /**
     * Obtém dados específicos.
     * @return string
     */
    public static function getSecretKey(): string
    {
        return getenv('AKTI_RECAPTCHA_SECRET_KEY') ?: static::RECAPTCHA_SECRET_KEY;
    }

    // ─────────────────────────────────────────
    // Registrar tentativa
    // ─────────────────────────────────────────

    /**
     * Registra uma tentativa de login (falha ou sucesso).
     */
    public function record(string $ip, string $email, bool $success): bool
    {
        $q = "INSERT INTO login_attempts (ip_address, email, attempted_at, success)
              VALUES (:ip, :email, NOW(), :success)";
        $s = $this->conn->prepare($q);
        return $s->execute([
            ':ip'      => $ip,
            ':email'   => strtolower(trim($email)),
            ':success' => $success ? 1 : 0,
        ]);
    }

    // ─────────────────────────────────────────
    // Contagem de falhas recentes
    // ─────────────────────────────────────────

    /**
     * Conta tentativas falhas de um IP+email na janela de tempo.
     */
    public function countRecentFailures(string $ip, string $email): int
    {
        $q = "SELECT COUNT(*) FROM login_attempts
              WHERE ip_address = :ip
              AND email = :email
              AND success = 0
              AND attempted_at >= DATE_SUB(NOW(), INTERVAL :window MINUTE)";
        $s = $this->conn->prepare($q);
        $s->execute([
            ':ip'     => $ip,
            ':email'  => strtolower(trim($email)),
            ':window' => self::WINDOW_MINUTES,
        ]);
        return (int) $s->fetchColumn();
    }

    // ─────────────────────────────────────────
    // Verificação de bloqueio
    // ─────────────────────────────────────────

    /**
     * Verifica se o IP+email está bloqueado.
     * Retorna array com 'blocked' (bool) e 'remaining_minutes' (int).
     */
    public function checkLockout(string $ip, string $email): array
    {
        $dateZone = new \DateTimeZone('America/Sao_Paulo');

        $email = strtolower(trim($email));

        // Buscar a tentativa falha que causou o bloqueio (a 5ª falha na janela)
        $q = "SELECT attempted_at FROM login_attempts
              WHERE ip_address = :ip
              AND email = :email
              AND success = 0
              AND attempted_at >= DATE_SUB(NOW(), INTERVAL :window MINUTE)
              ORDER BY attempted_at DESC
              LIMIT 1 OFFSET :offset";
        $s = $this->conn->prepare($q);
        $s->bindValue(':ip', $ip);
        $s->bindValue(':email', $email);
        $s->bindValue(':window', self::WINDOW_MINUTES, PDO::PARAM_INT);
        $s->bindValue(':offset', self::MAX_ATTEMPTS - 1, PDO::PARAM_INT);
        $s->execute();
        $row = $s->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return ['blocked' => false, 'remaining_minutes' => 0];
        }

        // Calcular quando o bloqueio expira
        $lockoutStart = new DateTime($row['attempted_at'],$dateZone);
        $lockoutEnd   = clone $lockoutStart;
        $lockoutEnd->modify('+' . self::LOCKOUT_MINUTES . ' minutes');
        $now = new DateTime('now',$dateZone);

        if ($now < $lockoutEnd) {
            $diff = $now->diff($lockoutEnd);
            $remaining = ($diff->h * 60) + $diff->i + ($diff->s > 0 ? 1 : 0);
            return ['blocked' => true, 'remaining_minutes' => max(1, $remaining)];
        }

        return ['blocked' => false, 'remaining_minutes' => 0];
    }

    // ─────────────────────────────────────────
    // Verificação de captcha necessário
    // ─────────────────────────────────────────

    /**
     * Verifica se o captcha deve ser exibido (>= 3 falhas recentes).
     */
    public function requiresCaptcha(string $ip, string $email): bool
    {
        // Só exigir se as chaves estiverem configuradas
        if (empty(self::getSiteKey()) || empty(self::getSecretKey())) {
            return false;
        }
        return $this->countRecentFailures($ip, $email) >= self::CAPTCHA_THRESHOLD;
    }

    /**
     * Valida a resposta do reCAPTCHA v2 com a API do Google.
     */
    public function validateCaptcha(string $captchaResponse, string $ip): bool
    {
        $secretKey = self::getSecretKey();
        if (empty($secretKey)) {
            return true; // Se não configurado, não bloqueia
        }

        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $data = [
            'secret'   => $secretKey,
            'response' => $captchaResponse,
            'remoteip' => $ip,
        ];

        $options = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data),
                'timeout' => 5,
            ],
        ];
        $context = stream_context_create($options);
        $result  = @file_get_contents($url, false, $context);

        if ($result === false) {
            return true; // Em caso de falha de rede, não bloqueia o usuário
        }

        $json = json_decode($result, true);
        return !empty($json['success']);
    }

    // ─────────────────────────────────────────
    // Limpeza de registros antigos
    // ─────────────────────────────────────────

    /**
     * Remove tentativas com mais de CLEANUP_MINUTES (padrão: 60 min).
     * Chamar a cada login bem-sucedido ou periodicamente.
     */
    public function purgeOld(): int
    {
        $q = "DELETE FROM login_attempts
              WHERE attempted_at < DATE_SUB(NOW(), INTERVAL :cleanup MINUTE)";
        $s = $this->conn->prepare($q);
        $s->execute([':cleanup' => self::CLEANUP_MINUTES]);
        return $s->rowCount();
    }

    /**
     * Limpa falhas de um IP+email específico (após login bem-sucedido).
     */
    public function clearFailures(string $ip, string $email): bool
    {
        $q = "DELETE FROM login_attempts
              WHERE ip_address = :ip AND email = :email AND success = 0";
        $s = $this->conn->prepare($q);
        return $s->execute([
            ':ip'    => $ip,
            ':email' => strtolower(trim($email)),
        ]);
    }

    // ─────────────────────────────────────────
    // Utilitário de IP
    // ─────────────────────────────────────────

    /**
     * Retorna o IP real do cliente, considerando proxies.
     */
    public static function getClientIp(): string
    {
        $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                // X-Forwarded-For pode ter múltiplos IPs, pegar o primeiro
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
