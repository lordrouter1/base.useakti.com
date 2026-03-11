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

// ── Listeners de módulos (adicionar conforme módulos forem instalados) ──
// require_once AKTI_BASE_PATH . 'app/modules/notificacoes/listeners.php';
// require_once AKTI_BASE_PATH . 'app/modules/integracao_erp/listeners.php';
// require_once AKTI_BASE_PATH . 'app/modules/webhooks/listeners.php';
