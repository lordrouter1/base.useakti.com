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

// ══════════════════════════════════════════════════════════════
// Listeners do Módulo de Comissão Automática
// ══════════════════════════════════════════════════════════════

$commissionLogFile = (defined('AKTI_BASE_PATH') ? AKTI_BASE_PATH : '') . 'storage/logs/commission.log';

/**
 * Helper: inicializa o CommissionAutoService (lazy, singleton por request).
 */
$getCommissionAutoService = function () {
    static $service = null;
    if ($service !== null) return $service;
    try {
        if (class_exists('Database')) {
            $db = (new \Database())->getConnection();
            $service = new \Akti\Services\CommissionAutoService($db);
        }
    } catch (\Throwable $e) {
        // Silencioso — não quebrar o fluxo
    }
    return $service;
};

/**
 * model.order.stage_changed — Quando pedido muda de etapa no pipeline.
 * Lê da configuração (pipeline_stage_comissao) em qual etapa o cálculo
 * de comissão é disparado, e verifica o critério de liberação (criterio_liberacao_comissao).
 */
EventDispatcher::listen('model.order.stage_changed', function (Event $event) use ($commissionLogFile, $getCommissionAutoService) {
    $data = $event->data;
    $toStage = $data['to_stage'] ?? '';
    $orderId = (int) ($data['id'] ?? 0);

    if ($orderId <= 0) return;

    $service = $getCommissionAutoService();
    if (!$service) return;

    // Verificar se a etapa destino é a etapa configurada para gatilho
    $stageGatilho = $service->getStageGatilho();
    if ($toStage !== $stageGatilho) return;

    $result = $service->tryAutoCommission($orderId);

    $logMessage = sprintf(
        '[Comissão Auto - Stage Changed] Pedido: #%d | Etapa: %s | Triggered: %s | %s',
        $orderId,
        $toStage,
        $result['triggered'] ? 'Sim' : 'Não',
        $result['message'] ?? ''
    );
    @file_put_contents($commissionLogFile, date('[Y-m-d H:i:s] ') . $logMessage . PHP_EOL, FILE_APPEND);
});

/**
 * model.installment.confirmed — Quando uma parcela é confirmada.
 * Verifica se o critério de liberação de pagamento foi atendido
 * e se o pedido está na etapa configurada. Se sim, dispara comissão automática.
 */
EventDispatcher::listen('model.installment.confirmed', function (Event $event) use ($commissionLogFile, $getCommissionAutoService) {
    $data = $event->data;
    $orderId = (int) ($data['order_id'] ?? 0);

    if ($orderId <= 0) return;

    $service = $getCommissionAutoService();
    if (!$service) return;

    $result = $service->tryAutoCommission($orderId);

    if ($result['triggered']) {
        $logMessage = sprintf(
            '[Comissão Auto - Payment Confirmed] Pedido: #%d | %s',
            $orderId,
            $result['message'] ?? ''
        );
        @file_put_contents($commissionLogFile, date('[Y-m-d H:i:s] ') . $logMessage . PHP_EOL, FILE_APPEND);
    }
});

/**
 * model.installment.paid — Quando um pagamento é registrado e auto-confirmado.
 * Verifica as mesmas condições para comissão automática.
 * Também cobre o critério 'primeira_parcela'.
 */
EventDispatcher::listen('model.installment.paid', function (Event $event) use ($commissionLogFile, $getCommissionAutoService) {
    $data = $event->data;
    $orderId = (int) ($data['order_id'] ?? 0);

    if ($orderId <= 0) return;

    $service = $getCommissionAutoService();
    if (!$service) return;

    // Tentar comissão automática (o serviço já verifica o critério de liberação)
    $result = $service->tryAutoCommission($orderId);

    if ($result['triggered']) {
        $logMessage = sprintf(
            '[Comissão Auto - Payment Registered] Pedido: #%d | %s',
            $orderId,
            $result['message'] ?? ''
        );
        @file_put_contents($commissionLogFile, date('[Y-m-d H:i:s] ') . $logMessage . PHP_EOL, FILE_APPEND);
    }
});

// ══════════════════════════════════════════════════════════════
// Listeners do Módulo Financeiro
// ══════════════════════════════════════════════════════════════

$financialLogFile = (defined('AKTI_BASE_PATH') ? AKTI_BASE_PATH : '') . 'storage/logs/financial.log';

/**
 * Helper: inicializa o FinancialAuditService (lazy, singleton por request).
 * Retorna null se a classe ou o banco não estiver disponível.
 */
$getAuditService = function () {
    static $service = null;
    if ($service !== null) return $service;
    try {
        if (class_exists('Database')) {
            $db = (new \Database())->getConnection();
            $service = new \Akti\Services\FinancialAuditService($db);
        }
    } catch (\Throwable $e) {
        // Silencioso — não quebrar o fluxo
    }
    return $service;
};

/**
 * model.installment.generated — Parcelas geradas para um pedido.
 * Payload: order_id, total_amount, num_installments, down_payment, installment_value
 */
EventDispatcher::listen('model.installment.generated', function (Event $event) use ($financialLogFile, $getAuditService) {
    $data = $event->getData();
    $logMessage = sprintf(
        '[Parcelas Geradas] Pedido: #%d | Total: R$ %s | Parcelas: %d | Entrada: R$ %s',
        $data['order_id'] ?? 0,
        number_format($data['total_amount'] ?? 0, 2, ',', '.'),
        $data['num_installments'] ?? 0,
        number_format($data['down_payment'] ?? 0, 2, ',', '.')
    );
    @file_put_contents($financialLogFile, date('[Y-m-d H:i:s] ') . $logMessage . PHP_EOL, FILE_APPEND);

    // Audit log
    if ($audit = $getAuditService()) {
        $audit->logOrder((int)($data['order_id'] ?? 0), 'installments_generated', $data);
    }
});

/**
 * model.installment.paid — Pagamento de parcela registrado.
 * Payload: installment_id, order_id, paid_amount, auto_confirmed, user_id
 */
EventDispatcher::listen('model.installment.paid', function (Event $event) use ($financialLogFile, $getAuditService) {
    $data = $event->getData();
    $logMessage = sprintf(
        '[Pagamento Registrado] Parcela: #%d | Pedido: #%d | Valor: R$ %s | Auto-confirmado: %s | Usuário: %s',
        $data['installment_id'] ?? 0,
        $data['order_id'] ?? 0,
        number_format($data['paid_amount'] ?? 0, 2, ',', '.'),
        ($data['auto_confirmed'] ?? false) ? 'Sim' : 'Não',
        $data['user_id'] ?? 'N/A'
    );
    @file_put_contents($financialLogFile, date('[Y-m-d H:i:s] ') . $logMessage . PHP_EOL, FILE_APPEND);

    // Audit log
    if ($audit = $getAuditService()) {
        $audit->logInstallment((int)($data['installment_id'] ?? 0), 'paid', $data, [], (int)($data['user_id'] ?? 0) ?: null);
    }
});

/**
 * model.installment.confirmed — Parcela confirmada manualmente.
 * Payload: installment_id, order_id, confirmed_by
 */
EventDispatcher::listen('model.installment.confirmed', function (Event $event) use ($financialLogFile, $getAuditService) {
    $data = $event->getData();
    $logMessage = sprintf(
        '[Pagamento Confirmado] Parcela: #%d | Pedido: #%d | Confirmado por: %s',
        $data['installment_id'] ?? 0,
        $data['order_id'] ?? 0,
        $data['confirmed_by'] ?? 'N/A'
    );
    @file_put_contents($financialLogFile, date('[Y-m-d H:i:s] ') . $logMessage . PHP_EOL, FILE_APPEND);

    // Audit log
    if ($audit = $getAuditService()) {
        $audit->logInstallment((int)($data['installment_id'] ?? 0), 'confirmed', $data, [], (int)($data['confirmed_by'] ?? 0) ?: null);
    }
});

/**
 * model.installment.cancelled — Parcela estornada/cancelada.
 * Payload: installment_id, order_id, cancelled_by, original_amount
 */
EventDispatcher::listen('model.installment.cancelled', function (Event $event) use ($financialLogFile, $getAuditService) {
    $data = $event->getData();
    $logMessage = sprintf(
        '[Parcela Estornada] Parcela: #%d | Pedido: #%d | Valor original: R$ %s | Por: %s',
        $data['installment_id'] ?? 0,
        $data['order_id'] ?? 0,
        number_format($data['original_amount'] ?? 0, 2, ',', '.'),
        $data['cancelled_by'] ?? 'N/A'
    );
    @file_put_contents($financialLogFile, date('[Y-m-d H:i:s] ') . $logMessage . PHP_EOL, FILE_APPEND);

    // Audit log
    if ($audit = $getAuditService()) {
        $audit->logInstallment((int)($data['installment_id'] ?? 0), 'cancelled', $data, [], (int)($data['cancelled_by'] ?? 0) ?: null);
    }
});

/**
 * model.installment.deleted_all — Todas as parcelas de um pedido removidas.
 * Payload: order_id, count
 */
EventDispatcher::listen('model.installment.deleted_all', function (Event $event) use ($financialLogFile, $getAuditService) {
    $data = $event->getData();
    $logMessage = sprintf(
        '[Parcelas Removidas] Pedido: #%d | Quantidade: %d',
        $data['order_id'] ?? 0,
        $data['count'] ?? 0
    );
    @file_put_contents($financialLogFile, date('[Y-m-d H:i:s] ') . $logMessage . PHP_EOL, FILE_APPEND);

    // Audit log
    if ($audit = $getAuditService()) {
        $audit->logOrder((int)($data['order_id'] ?? 0), 'installments_deleted_all', $data);
    }
});

/**
 * model.installment.merged — Parcelas unificadas em uma.
 * Payload: order_id, merged_ids, new_id, amount
 */
EventDispatcher::listen('model.installment.merged', function (Event $event) use ($financialLogFile, $getAuditService) {
    $data = $event->getData();
    $logMessage = sprintf(
        '[Parcelas Unificadas] Pedido: #%d | IDs mesclados: [%s] → Nova: #%d | Valor: R$ %s',
        $data['order_id'] ?? 0,
        implode(',', $data['merged_ids'] ?? []),
        $data['new_id'] ?? 0,
        number_format($data['amount'] ?? 0, 2, ',', '.')
    );
    @file_put_contents($financialLogFile, date('[Y-m-d H:i:s] ') . $logMessage . PHP_EOL, FILE_APPEND);

    // Audit log
    if ($audit = $getAuditService()) {
        $audit->logInstallment((int)($data['new_id'] ?? 0), 'merged', $data);
    }
});

/**
 * model.installment.split — Parcela dividida em N partes.
 * Payload: order_id, original_id, parts, new_ids, original_amount
 */
EventDispatcher::listen('model.installment.split', function (Event $event) use ($financialLogFile, $getAuditService) {
    $data = $event->getData();
    $logMessage = sprintf(
        '[Parcela Dividida] Pedido: #%d | Original: #%d | Partes: %d | Novos IDs: [%s] | Valor original: R$ %s',
        $data['order_id'] ?? 0,
        $data['original_id'] ?? 0,
        $data['parts'] ?? 0,
        implode(',', $data['new_ids'] ?? []),
        number_format($data['original_amount'] ?? 0, 2, ',', '.')
    );
    @file_put_contents($financialLogFile, date('[Y-m-d H:i:s] ') . $logMessage . PHP_EOL, FILE_APPEND);

    // Audit log
    if ($audit = $getAuditService()) {
        $audit->logInstallment((int)($data['original_id'] ?? 0), 'split', $data);
    }
});

/**
 * model.installment.due_date_updated — Data de vencimento alterada.
 * Payload: id, due_date
 */
EventDispatcher::listen('model.installment.due_date_updated', function (Event $event) use ($financialLogFile, $getAuditService) {
    $data = $event->getData();
    $logMessage = sprintf(
        '[Vencimento Alterado] Parcela: #%d | Nova data: %s',
        $data['id'] ?? 0,
        $data['due_date'] ?? 'N/A'
    );
    @file_put_contents($financialLogFile, date('[Y-m-d H:i:s] ') . $logMessage . PHP_EOL, FILE_APPEND);

    // Audit log
    if ($audit = $getAuditService()) {
        $audit->logInstallment((int)($data['id'] ?? 0), 'due_date_updated', $data);
    }
});

/**
 * model.order.financial_updated — Campos financeiros do pedido atualizados.
 * Payload: id, payment_method, installments, installment_value, down_payment
 */
EventDispatcher::listen('model.order.financial_updated', function (Event $event) use ($financialLogFile, $getAuditService) {
    $data = $event->getData();
    $logMessage = sprintf(
        '[Pedido Financeiro Atualizado] Pedido: #%d | Parcelas: %s | Valor parcela: R$ %s | Entrada: R$ %s',
        $data['id'] ?? 0,
        $data['installments'] ?? 'N/A',
        number_format($data['installment_value'] ?? 0, 2, ',', '.'),
        number_format($data['down_payment'] ?? 0, 2, ',', '.')
    );
    @file_put_contents($financialLogFile, date('[Y-m-d H:i:s] ') . $logMessage . PHP_EOL, FILE_APPEND);

    // Audit log
    if ($audit = $getAuditService()) {
        $audit->logOrder((int)($data['id'] ?? 0), 'financial_updated', $data);
    }
});

/**
 * model.financial_transaction.created — Transação financeira criada.
 * Payload: id, type, category, amount
 */
EventDispatcher::listen('model.financial_transaction.created', function (Event $event) use ($financialLogFile, $getAuditService) {
    $data = $event->getData();
    $logMessage = sprintf(
        '[Transação Criada] ID: %d | Tipo: %s | Categoria: %s | Valor: R$ %s',
        $data['id'] ?? 0,
        $data['type'] ?? 'N/A',
        $data['category'] ?? 'N/A',
        number_format($data['amount'] ?? 0, 2, ',', '.')
    );
    @file_put_contents($financialLogFile, date('[Y-m-d H:i:s] ') . $logMessage . PHP_EOL, FILE_APPEND);

    // Audit log
    if ($audit = $getAuditService()) {
        $audit->logTransaction((int)($data['id'] ?? 0), 'created', $data);
    }
});

/**
 * model.financial_transaction.updated — Transação financeira atualizada.
 * Payload: id, type, category, amount
 */
EventDispatcher::listen('model.financial_transaction.updated', function (Event $event) use ($financialLogFile, $getAuditService) {
    $data = $event->getData();
    $logMessage = sprintf(
        '[Transação Atualizada] ID: %d | Tipo: %s | Categoria: %s | Valor: R$ %s',
        $data['id'] ?? 0,
        $data['type'] ?? 'N/A',
        $data['category'] ?? 'N/A',
        number_format($data['amount'] ?? 0, 2, ',', '.')
    );
    @file_put_contents($financialLogFile, date('[Y-m-d H:i:s] ') . $logMessage . PHP_EOL, FILE_APPEND);

    // Audit log
    if ($audit = $getAuditService()) {
        $audit->logTransaction((int)($data['id'] ?? 0), 'updated', $data);
    }
});

/**
 * model.financial_transaction.deleted — Transação financeira removida.
 * Payload: id, reason, old_data
 */
EventDispatcher::listen('model.financial_transaction.deleted', function (Event $event) use ($financialLogFile, $getAuditService) {
    $data = $event->getData();
    $reason = $data['reason'] ?? '';
    $logMessage = sprintf('[Transação Removida] ID: %d | Motivo: %s', $data['id'] ?? 0, $reason ?: '(não informado)');
    @file_put_contents($financialLogFile, date('[Y-m-d H:i:s] ') . $logMessage . PHP_EOL, FILE_APPEND);

    // Audit log — gravar dados anteriores e motivo
    if ($audit = $getAuditService()) {
        $oldData = $data['old_data'] ?? [];
        $audit->logTransaction(
            (int)($data['id'] ?? 0),
            'deleted',
            $data,
            $oldData,
            null,
            $reason ?: null
        );
    }
});

// ── Recorrências ──────────────────────────────────────────────

/**
 * model.recurring_transaction.created — Recorrência criada.
 * Payload: id, type, amount, description
 */
EventDispatcher::listen('model.recurring_transaction.created', function (Event $event) use ($financialLogFile, $getAuditService) {
    $data = $event->getData();
    $logMessage = sprintf(
        '[Recorrência Criada] ID: %d | Tipo: %s | Valor: R$ %s | Descrição: %s',
        $data['id'] ?? 0,
        $data['type'] ?? 'N/A',
        number_format($data['amount'] ?? 0, 2, ',', '.'),
        $data['description'] ?? ''
    );
    @file_put_contents($financialLogFile, date('[Y-m-d H:i:s] ') . $logMessage . PHP_EOL, FILE_APPEND);

    if ($audit = $getAuditService()) {
        $audit->log('recurring', (int)($data['id'] ?? 0), 'created', $data);
    }
});

/**
 * model.recurring_transaction.updated — Recorrência atualizada.
 * Payload: id, type, amount
 */
EventDispatcher::listen('model.recurring_transaction.updated', function (Event $event) use ($financialLogFile, $getAuditService) {
    $data = $event->getData();
    $logMessage = sprintf(
        '[Recorrência Atualizada] ID: %d | Tipo: %s | Valor: R$ %s',
        $data['id'] ?? 0,
        $data['type'] ?? 'N/A',
        number_format($data['amount'] ?? 0, 2, ',', '.')
    );
    @file_put_contents($financialLogFile, date('[Y-m-d H:i:s] ') . $logMessage . PHP_EOL, FILE_APPEND);

    if ($audit = $getAuditService()) {
        $audit->log('recurring', (int)($data['id'] ?? 0), 'updated', $data);
    }
});

/**
 * model.recurring_transaction.deleted — Recorrência removida.
 * Payload: id
 */
EventDispatcher::listen('model.recurring_transaction.deleted', function (Event $event) use ($financialLogFile, $getAuditService) {
    $data = $event->getData();
    $logMessage = sprintf('[Recorrência Removida] ID: %d', $data['id'] ?? 0);
    @file_put_contents($financialLogFile, date('[Y-m-d H:i:s] ') . $logMessage . PHP_EOL, FILE_APPEND);

    if ($audit = $getAuditService()) {
        $audit->log('recurring', (int)($data['id'] ?? 0), 'deleted', $data);
    }
});

/**
 * model.recurring_transaction.processed — Processamento mensal concluído.
 * Payload: generated, skipped, errors
 */
EventDispatcher::listen('model.recurring_transaction.processed', function (Event $event) use ($financialLogFile) {
    $data = $event->getData();
    $logMessage = sprintf(
        '[Recorrências Processadas] Geradas: %d | Ignoradas: %d | Erros: %d',
        $data['generated'] ?? 0,
        $data['skipped'] ?? 0,
        count($data['errors'] ?? [])
    );
    if (!empty($data['errors'])) {
        $logMessage .= ' | Detalhes: ' . implode('; ', $data['errors']);
    }
    @file_put_contents($financialLogFile, date('[Y-m-d H:i:s] ') . $logMessage . PHP_EOL, FILE_APPEND);
});
