<?php
namespace Akti\Core;

/**
 * Log — Structured Logging (PSR-3 inspired)
 *
 * Canais: security, financial, general, api, cron
 * Formato: JSON lines ({timestamp, level, channel, message, context, tenant_id, user_id})
 * Rotação: diária automática (1 arquivo por dia por canal)
 *
 * Usage:
 *   Log::channel('security')->warning('Login attempt failed', ['ip' => $ip]);
 *   Log::info('Order created', ['order_id' => 123]);  // canal 'general'
 *   Log::error('Database timeout', ['query' => $sql]);
 *
 * Substituir todos os error_log() e file_put_contents() de log por esta classe.
 */
class Log
{
    /** @var string Diretório base para logs */
    private static $logDir;

    /** @var string Canal atual */
    private $channel;

    /** Níveis de log (PSR-3) */
    const EMERGENCY = 'emergency';
    const ALERT     = 'alert';
    const CRITICAL  = 'critical';
    const ERROR     = 'error';
    const WARNING   = 'warning';
    const NOTICE    = 'notice';
    const INFO      = 'info';
    const DEBUG     = 'debug';

    /**
     * @param string $channel
     */
    public function __construct(string $channel = 'general')
    {
        $this->channel = $channel;
        if (!self::$logDir) {
            self::$logDir = dirname(__DIR__, 2) . '/storage/logs';
        }
    }

    /**
     * Cria uma instância de Log com um canal específico.
     *
     * @param string $channel  Ex: 'security', 'financial', 'api', 'cron'
     * @return self
     */
    public static function channel(string $channel): self
    {
        return new self($channel);
    }

    // ── Métodos estáticos de conveniência (canal 'general') ──

    /**
     * Emergency.
     *
     * @param string $message Mensagem
     * @param array $context Contexto adicional
     * @return void
     */
    public static function emergency(string $message, array $context = []): void
    {
        (new self())->log(self::EMERGENCY, $message, $context);
    }

    /**
     * Alert.
     *
     * @param string $message Mensagem
     * @param array $context Contexto adicional
     * @return void
     */
    public static function alert(string $message, array $context = []): void
    {
        (new self())->log(self::ALERT, $message, $context);
    }

    /**
     * Critical.
     *
     * @param string $message Mensagem
     * @param array $context Contexto adicional
     * @return void
     */
    public static function critical(string $message, array $context = []): void
    {
        (new self())->log(self::CRITICAL, $message, $context);
    }

    /**
     * Error.
     *
     * @param string $message Mensagem
     * @param array $context Contexto adicional
     * @return void
     */
    public static function error(string $message, array $context = []): void
    {
        (new self())->log(self::ERROR, $message, $context);
    }

    /**
     * Warning.
     *
     * @param string $message Mensagem
     * @param array $context Contexto adicional
     * @return void
     */
    public static function warning(string $message, array $context = []): void
    {
        (new self())->log(self::WARNING, $message, $context);
    }

    /**
     * Notice.
     *
     * @param string $message Mensagem
     * @param array $context Contexto adicional
     * @return void
     */
    public static function notice(string $message, array $context = []): void
    {
        (new self())->log(self::NOTICE, $message, $context);
    }

    /**
     * Info.
     *
     * @param string $message Mensagem
     * @param array $context Contexto adicional
     * @return void
     */
    public static function info(string $message, array $context = []): void
    {
        (new self())->log(self::INFO, $message, $context);
    }

    /**
     * Debug.
     *
     * @param string $message Mensagem
     * @param array $context Contexto adicional
     * @return void
     */
    public static function debug(string $message, array $context = []): void
    {
        (new self())->log(self::DEBUG, $message, $context);
    }

    // ── Métodos de instância (com canal) ──

    /**
     * Grava um log estruturado em JSON.
     *
     * @param string $level
     * @param string $message
     * @param array  $context
     */
    public function log(string $level, string $message, array $context = []): void
    {
        $logDir = self::$logDir;

        // Garantir que o diretório existe
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        // Arquivo: {canal}_{Y-m-d}.log
        $filename = $logDir . '/' . $this->channel . '_' . date('Y-m-d') . '.log';

        // Construir registro JSON
        $record = [
            'timestamp'  => date('Y-m-d\TH:i:s.vP'),
            'level'      => strtoupper($level),
            'channel'    => $this->channel,
            'message'    => $message,
            'tenant_id'  => $_SESSION['tenant']['id'] ?? ($_SESSION['tenant_slug'] ?? null),
            'user_id'    => $_SESSION['user_id'] ?? null,
        ];

        if (!empty($context)) {
            $record['context'] = $context;
        }

        // Adicionar IP e request info para channels sensíveis
        if (in_array($this->channel, ['security', 'api'])) {
            $record['ip'] = $_SERVER['REMOTE_ADDR'] ?? 'cli';
            $record['method'] = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
            $record['uri'] = $_SERVER['REQUEST_URI'] ?? '';
        }

        // Gravar como JSON line
        $line = json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";

        @file_put_contents($filename, $line, FILE_APPEND | LOCK_EX);

        // Também gravar no error_log do PHP para níveis críticos
        if (in_array($level, [self::EMERGENCY, self::ALERT, self::CRITICAL, self::ERROR])) {
            error_log("[{$this->channel}][{$level}] {$message}");
        }
    }

    /**
     * Remove logs mais antigos que X dias.
     * Chamar periodicamente via cron.
     *
     * @param int $daysToKeep  Dias para manter (padrão: 30)
     * @return int Número de arquivos removidos
     */
    public static function cleanup(int $daysToKeep = 30): int
    {
        $logDir = self::$logDir ?: dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($logDir)) return 0;

        $cutoff = time() - ($daysToKeep * 86400);
        $removed = 0;

        foreach (glob($logDir . '/*.log') as $file) {
            if (filemtime($file) < $cutoff) {
                @unlink($file);
                $removed++;
            }
        }

        return $removed;
    }
}
