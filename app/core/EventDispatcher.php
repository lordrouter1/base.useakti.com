<?php
namespace Akti\Core;

/**
 * EventDispatcher — Sistema de eventos nativo do Akti.
 *
 * Classe estática que permite registrar listeners para eventos nomeados
 * e disparar eventos de qualquer camada (Models, Controllers, Core, Middleware).
 *
 * Listeners são executados em ordem FIFO (First In, First Out).
 * Se um listener lançar exceção, o erro é logado e os demais listeners
 * continuam executando normalmente (fire and forget).
 *
 * Convenção de nomes: camada.entidade.acao
 *   Exemplos: model.order.created, controller.user.login, middleware.csrf.failed
 *
 * Compatível com PHP 7.4+
 *
 * @package Akti\Core
 * @see     Event
 * @see     app/bootstrap/events.php
 */
class EventDispatcher
{
    /**
     * Listeners registrados, indexados por nome do evento.
     * Cada evento pode ter múltiplos listeners (callables).
     *
     * @var array<string, callable[]>
     */
    private static $listeners = [];

    /**
     * Caminho do arquivo de log de eventos (relativo à raiz do projeto).
     */
    private const LOG_FILE = 'storage/logs/events.log';

    // ══════════════════════════════════════════════════════════════
    // Registro de Listeners
    // ══════════════════════════════════════════════════════════════

    /**
     * Registra um listener para um evento nomeado.
     *
     * @param string   $event    Nome do evento (ex: 'model.order.created')
     * @param callable $listener Callable que recebe um Event como parâmetro
     */
    public static function listen(string $event, callable $listener): void
    {
        if (!isset(self::$listeners[$event])) {
            self::$listeners[$event] = [];
        }
        self::$listeners[$event][] = $listener;
    }

    // ══════════════════════════════════════════════════════════════
    // Disparo de Eventos
    // ══════════════════════════════════════════════════════════════

    /**
     * Dispara um evento para todos os listeners registrados.
     *
     * Listeners são executados em ordem FIFO. Se um listener lançar
     * exceção, o erro é logado e os demais continuam executando.
     *
     * @param string $event   Nome do evento
     * @param Event  $payload Objeto Event com os dados
     */
    public static function dispatch(string $event, Event $payload): void
    {
        if (empty(self::$listeners[$event])) {
            return;
        }

        foreach (self::$listeners[$event] as $listener) {
            try {
                call_user_func($listener, $payload);
            } catch (\Throwable $e) {
                self::logError($event, $e);
            }
        }
    }

    // ══════════════════════════════════════════════════════════════
    // Gerenciamento
    // ══════════════════════════════════════════════════════════════

    /**
     * Remove todos os listeners de um evento específico.
     *
     * @param string $event Nome do evento
     */
    public static function forget(string $event): void
    {
        unset(self::$listeners[$event]);
    }

    /**
     * Retorna todos os eventos registrados com seus listeners.
     *
     * @return array<string, callable[]>
     */
    public static function getRegistered(): array
    {
        return self::$listeners;
    }

    // ══════════════════════════════════════════════════════════════
    // Log de Erros
    // ══════════════════════════════════════════════════════════════

    /**
     * Registra erro de listener no log de eventos.
     *
     * @param string     $event Nome do evento
     * @param \Throwable $e     Exceção capturada
     */
    private static function logError(string $event, \Throwable $e): void
    {
        $logFile = (defined('AKTI_BASE_PATH') ? AKTI_BASE_PATH : __DIR__ . '/../../') . self::LOG_FILE;
        $logDir  = dirname($logFile);

        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        $message   = $e->getMessage();
        $file      = $e->getFile();
        $line      = $e->getLine();
        $userId    = $_SESSION['user_id'] ?? 'anonymous';

        $entry = sprintf(
            "[%s] Event listener error | Event: %s | Error: %s | File: %s:%d | User: %s\n",
            $timestamp,
            $event,
            $message,
            $file,
            $line,
            $userId
        );

        @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
    }
}
