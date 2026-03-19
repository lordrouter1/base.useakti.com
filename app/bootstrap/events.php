<?php
/**
 * Bootstrap de Eventos — Registro central de listeners.
 *
 * Este arquivo é carregado automaticamente pelo autoload.php após o registro
 * do autoloader PSR-4. Aqui devem ser registrados os listeners globais e,
 * futuramente, incluídos os listeners de cada módulo instalado.
 *
 * Padrão para módulos futuros:
 *   require_once AKTI_BASE_PATH . 'app/modules/nome_modulo/listeners.php';
 *
 * Exemplo de registro de listener:
 *   use Akti\Core\EventDispatcher;
 *   use Akti\Core\Event;
 *
 *   EventDispatcher::listen('model.order.created', function (Event $event) {
 *       // Lógica do listener
 *   });
 *
 * @see Akti\Core\EventDispatcher
 * @see Akti\Core\Event
 * @see PROJECT_RULES.md — Sistema de Eventos
 */

use Akti\Core\EventDispatcher;
use Akti\Core\Event;

// ══════════════════════════════════════════════════════════════
// Listeners do Módulo NF-e
// ══════════════════════════════════════════════════════════════

/**
 * model.nfe_document.authorized — Disparado quando uma NF-e é autorizada pela SEFAZ.
 * Payload: nfe_id, order_id, chave
 */
EventDispatcher::listen('model.nfe_document.authorized', function (Event $event) {
    $data = $event->getData();
    $logMessage = sprintf(
        '[NF-e Emitida] NF-e ID: %d | Pedido: %s | Chave: %s',
        $data['nfe_id'] ?? 0,
        $data['order_id'] ?? 'N/A',
        $data['chave'] ?? ''
    );
    error_log($logMessage);

    // Log em arquivo dedicado
    $logFile = (defined('AKTI_BASE_PATH') ? AKTI_BASE_PATH : '') . 'storage/logs/nfe.log';
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    @file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $logMessage . PHP_EOL, FILE_APPEND);
});

/**
 * model.nfe_document.cancelled — Disparado quando uma NF-e é cancelada na SEFAZ.
 * Payload: nfe_id, order_id
 */
EventDispatcher::listen('model.nfe_document.cancelled', function (Event $event) {
    $data = $event->getData();
    $logMessage = sprintf(
        '[NF-e Cancelada] NF-e ID: %d | Pedido: %s',
        $data['nfe_id'] ?? 0,
        $data['order_id'] ?? 'N/A'
    );
    error_log($logMessage);

    $logFile = (defined('AKTI_BASE_PATH') ? AKTI_BASE_PATH : '') . 'storage/logs/nfe.log';
    @file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $logMessage . PHP_EOL, FILE_APPEND);
});

/**
 * model.nfe_document.error — Disparado quando ocorre erro na emissão/cancelamento/correção.
 * Payload: nfe_id, order_id, code, message
 */
EventDispatcher::listen('model.nfe_document.error', function (Event $event) {
    $data = $event->getData();
    $logMessage = sprintf(
        '[NF-e Erro] NF-e ID: %s | Pedido: %s | Código: %s | Mensagem: %s',
        $data['nfe_id'] ?? 'N/A',
        $data['order_id'] ?? 'N/A',
        $data['code'] ?? '',
        $data['message'] ?? ''
    );
    error_log($logMessage);

    $logFile = (defined('AKTI_BASE_PATH') ? AKTI_BASE_PATH : '') . 'storage/logs/nfe.log';
    @file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $logMessage . PHP_EOL, FILE_APPEND);
});

// ── Listeners de módulos (adicionar conforme módulos forem instalados) ──
// require_once AKTI_BASE_PATH . 'app/modules/notificacoes/listeners.php';
// require_once AKTI_BASE_PATH . 'app/modules/integracao_erp/listeners.php';
// require_once AKTI_BASE_PATH . 'app/modules/webhooks/listeners.php';
