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
 *
 * Ações:
 *   1. Log em arquivo dedicado
 *   2. Geração automática de DANFE (PDF) via NfePdfGenerator
 *   3. Salvar DANFE em disco via NfeStorageService
 *   4. Atualizar danfe_path no documento
 */
EventDispatcher::listen('model.nfe_document.authorized', function (Event $event) {
    $data = $event->getData();
    $nfeId = $data['nfe_id'] ?? 0;
    $orderId = $data['order_id'] ?? 'N/A';
    $chave = $data['chave'] ?? '';

    $logMessage = sprintf(
        '[NF-e Emitida] NF-e ID: %d | Pedido: %s | Chave: %s',
        $nfeId,
        $orderId,
        $chave
    );
    error_log($logMessage);

    // Log em arquivo dedicado
    $logFile = (defined('AKTI_BASE_PATH') ? AKTI_BASE_PATH : '') . 'storage/logs/nfe.log';
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    @file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $logMessage . PHP_EOL, FILE_APPEND);

    // ── Geração automática de DANFE (PDF) ──
    if ($nfeId > 0 && !empty($chave)) {
        try {
            // Carregar XML autorizado do banco
            $db = null;
            if (class_exists('Database')) {
                $db = (new \Database())->getConnection();
            }
            if (!$db) return;

            $docModel = new \Akti\Models\NfeDocument($db);
            $doc = $docModel->readOne($nfeId);
            $xmlAutorizado = $doc['xml_autorizado'] ?? '';

            if (empty($xmlAutorizado)) {
                @file_put_contents($logFile, date('[Y-m-d H:i:s] ')
                    . "[DANFE] NF-e #{$nfeId} — XML autorizado vazio, DANFE não gerado." . PHP_EOL, FILE_APPEND);
                return;
            }

            // Gerar PDF via NfePdfGenerator
            $pdf = \Akti\Services\NfePdfGenerator::renderToString($xmlAutorizado);
            if ($pdf === null) {
                @file_put_contents($logFile, date('[Y-m-d H:i:s] ')
                    . "[DANFE] NF-e #{$nfeId} — Biblioteca sped-da não disponível, DANFE não gerado." . PHP_EOL, FILE_APPEND);
                return;
            }

            // Salvar DANFE em disco
            $storage = new \Akti\Services\NfeStorageService();
            $danfePath = $storage->saveDanfe($chave, $pdf);

            if ($danfePath) {
                // Atualizar danfe_path no documento
                $docModel->update($nfeId, ['danfe_path' => $danfePath]);

                @file_put_contents($logFile, date('[Y-m-d H:i:s] ')
                    . "[DANFE] NF-e #{$nfeId} — DANFE gerado e salvo: {$danfePath}" . PHP_EOL, FILE_APPEND);
            } else {
                @file_put_contents($logFile, date('[Y-m-d H:i:s] ')
                    . "[DANFE] NF-e #{$nfeId} — Erro ao salvar DANFE em disco." . PHP_EOL, FILE_APPEND);
            }
        } catch (\Throwable $e) {
            // Não falhar o fluxo principal se a geração de DANFE falhar
            @file_put_contents($logFile, date('[Y-m-d H:i:s] ')
                . "[DANFE] NF-e #{$nfeId} — Exceção: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
        }
    }
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

// ──────────────────────────────────────────────────────────────
// FASE 4 — Eventos NF-e Expandidos (Rejeição, CC-e, Inutilização, Contingência)
// ──────────────────────────────────────────────────────────────

$nfeLogFile = (defined('AKTI_BASE_PATH') ? AKTI_BASE_PATH : '') . 'storage/logs/nfe.log';

/**
 * Helper: obtém conexão PDO de forma lazy (singleton por request).
 */
$getNfeDb = function () {
    static $db = null;
    if ($db !== null) return $db;
    try {
        if (class_exists('Database')) {
            $db = (new \Database())->getConnection();
        }
    } catch (\Throwable $e) {
        // Silencioso
    }
    return $db;
};

/**
 * Helper: envia notificação interna para um usuário.
 * Grava na tabela `notifications` (se existir).
 */
$sendInternalNotification = function (string $title, string $message, string $type = 'info', ?int $userId = null) use ($getNfeDb) {
    try {
        $db = $getNfeDb();
        if (!$db) return;

        // Verificar se tabela notifications existe
        $check = $db->query("SHOW TABLES LIKE 'notifications'");
        if ($check->rowCount() === 0) return;

        $targetUserId = $userId ?: ($_SESSION['user_id'] ?? null);
        if (!$targetUserId) return;

        $q = "INSERT INTO notifications (user_id, title, message, type, is_read, created_at)
              VALUES (:uid, :title, :msg, :type, 0, NOW())";
        $s = $db->prepare($q);
        $s->execute([
            ':uid'   => $targetUserId,
            ':title' => $title,
            ':msg'   => $message,
            ':type'  => $type,
        ]);
    } catch (\Throwable $e) {
        // Não falhar — notificação é best-effort
    }
};

/**
 * Helper: notifica todos os administradores.
 */
$notifyAdmins = function (string $title, string $message, string $type = 'warning') use ($getNfeDb, $sendInternalNotification) {
    try {
        $db = $getNfeDb();
        if (!$db) return;

        $q = "SELECT id FROM users WHERE role = 'admin' AND status = 'active'";
        $s = $db->prepare($q);
        $s->execute();
        $admins = $s->fetchAll(\PDO::FETCH_COLUMN);

        foreach ($admins as $adminId) {
            $sendInternalNotification($title, $message, $type, (int) $adminId);
        }
    } catch (\Throwable $e) {
        // Silencioso
    }
};

/**
 * model.nfe_document.rejected — Disparado quando uma NF-e é rejeitada pela SEFAZ.
 * Payload: nfe_id, order_id, code_sefaz, motivo
 *
 * Ações:
 *   1. Log em arquivo dedicado
 *   2. Notificação interna para o usuário com código e motivo
 *   3. Sugestão automática de correção baseada no código SEFAZ
 *   4. Alerta para administradores
 */
EventDispatcher::listen('model.nfe_document.rejected', function (Event $event) use ($nfeLogFile, $sendInternalNotification, $notifyAdmins) {
    $data = $event->getData();
    $nfeId    = $data['nfe_id'] ?? 0;
    $orderId  = $data['order_id'] ?? 'N/A';
    $codeSefaz = $data['code_sefaz'] ?? '';
    $motivo   = $data['motivo'] ?? '';

    $logMessage = sprintf(
        '[NF-e Rejeitada] NF-e ID: %d | Pedido: %s | cStat: %s | Motivo: %s',
        $nfeId, $orderId, $codeSefaz, $motivo
    );
    @file_put_contents($nfeLogFile, date('[Y-m-d H:i:s] ') . $logMessage . PHP_EOL, FILE_APPEND);

    // Tabela de sugestões automáticas por código SEFAZ
    $suggestions = [
        '204' => 'NF-e duplicada. Verifique se já existe NF-e com mesmo número/série.',
        '225' => 'Falha no Schema XML. Verifique os dados da NF-e (campos obrigatórios).',
        '233' => 'IE do destinatário inválida. Verifique o cadastro do cliente.',
        '301' => 'Uso Denegado — Irregularidade fiscal do emitente.',
        '302' => 'Uso Denegado — Irregularidade fiscal do destinatário.',
        '539' => 'Duplicidade de NF-e com diferença na chave. Revise número e série.',
        '593' => 'CNPJ do emitente não cadastrado na SEFAZ.',
        '598' => 'NF-e emitida com valor zero.',
        '694' => 'NCM não existe na tabela de NCM.',
        '777' => 'CFOP incompatível. Verifique a operação fiscal.',
        '778' => 'CFOP de entrada utilizado em NF-e de saída.',
    ];

    $suggestion = $suggestions[$codeSefaz] ?? '';
    $notifMsg = "NF-e #{$nfeId} rejeitada pela SEFAZ.\nCódigo: {$codeSefaz}\nMotivo: {$motivo}";
    if ($suggestion) {
        $notifMsg .= "\n\n💡 Sugestão: {$suggestion}";
    }

    // Notificar o usuário que emitiu
    $sendInternalNotification(
        '❌ NF-e Rejeitada #' . $nfeId,
        $notifMsg,
        'danger'
    );

    // Alertar administradores
    $notifyAdmins(
        '⚠️ NF-e Rejeitada #' . $nfeId,
        "Pedido #{$orderId} — cStat: {$codeSefaz} — {$motivo}",
        'warning'
    );
});

/**
 * model.nfe_document.corrected — Disparado quando uma Carta de Correção (CC-e) é enviada.
 * Payload: nfe_id, order_id, chave, seq, texto, protocolo
 *
 * Ações:
 *   1. Log em arquivo dedicado
 *   2. Notificação interna (informativa)
 */
EventDispatcher::listen('model.nfe_document.corrected', function (Event $event) use ($nfeLogFile, $sendInternalNotification) {
    $data = $event->getData();
    $nfeId    = $data['nfe_id'] ?? 0;
    $orderId  = $data['order_id'] ?? 'N/A';
    $seq      = $data['seq'] ?? 0;
    $texto    = $data['texto'] ?? '';
    $protocolo = $data['protocolo'] ?? '';

    $logMessage = sprintf(
        '[NF-e CC-e] NF-e ID: %d | Pedido: %s | Seq: %d | Protocolo: %s | Texto: %s',
        $nfeId, $orderId, $seq, $protocolo, mb_substr($texto, 0, 100)
    );
    @file_put_contents($nfeLogFile, date('[Y-m-d H:i:s] ') . $logMessage . PHP_EOL, FILE_APPEND);

    $sendInternalNotification(
        '📝 Carta de Correção #' . $nfeId . ' (seq ' . $seq . ')',
        "CC-e enviada para NF-e #{$nfeId} (Pedido #{$orderId}).\nTexto: " . mb_substr($texto, 0, 200),
        'info'
    );
});

/**
 * model.nfe_document.inutilized — Disparado quando numeração é inutilizada na SEFAZ.
 * Payload: nfe_id, serie, num_inicial, num_final, justificativa, protocolo
 *
 * Ações:
 *   1. Log em arquivo dedicado
 *   2. Notificação interna
 */
EventDispatcher::listen('model.nfe_document.inutilized', function (Event $event) use ($nfeLogFile, $sendInternalNotification) {
    $data = $event->getData();
    $nfeId       = $data['nfe_id'] ?? 0;
    $serie       = $data['serie'] ?? '';
    $numInicial  = $data['num_inicial'] ?? '';
    $numFinal    = $data['num_final'] ?? '';
    $justificativa = $data['justificativa'] ?? '';
    $protocolo   = $data['protocolo'] ?? '';

    $logMessage = sprintf(
        '[NF-e Inutilizada] ID: %d | Série: %s | Num: %s-%s | Protocolo: %s | Justificativa: %s',
        $nfeId, $serie, $numInicial, $numFinal, $protocolo, mb_substr($justificativa, 0, 100)
    );
    @file_put_contents($nfeLogFile, date('[Y-m-d H:i:s] ') . $logMessage . PHP_EOL, FILE_APPEND);

    $sendInternalNotification(
        '🔢 Numeração Inutilizada',
        "Série {$serie}, números {$numInicial} a {$numFinal} inutilizados.\nProtocolo: {$protocolo}",
        'info'
    );
});

/**
 * model.nfe_document.contingency_activated — Disparado quando modo contingência é ativado.
 * Payload: motivo, tipo_contingencia (EPEC, SVC, FS-DA, etc.)
 *
 * Ações:
 *   1. Log em arquivo dedicado
 *   2. Alerta urgente para administradores
 */
EventDispatcher::listen('model.nfe_document.contingency_activated', function (Event $event) use ($nfeLogFile, $notifyAdmins) {
    $data = $event->getData();
    $motivo = $data['motivo'] ?? 'Não informado';
    $tipo   = $data['tipo_contingencia'] ?? 'N/A';

    $logMessage = sprintf(
        '[NF-e Contingência ATIVADA] Tipo: %s | Motivo: %s',
        $tipo, $motivo
    );
    @file_put_contents($nfeLogFile, date('[Y-m-d H:i:s] ') . $logMessage . PHP_EOL, FILE_APPEND);

    $notifyAdmins(
        '🚨 Contingência Fiscal Ativada',
        "Modo contingência ativado ({$tipo}).\nMotivo: {$motivo}\n\nNotas emitidas neste modo precisam ser transmitidas quando o serviço normalizar.",
        'danger'
    );
});

/**
 * model.nfe_document.contingency_deactivated — Disparado quando contingência é desativada.
 * Payload: notas_pendentes (int)
 *
 * Ações:
 *   1. Log em arquivo dedicado
 *   2. Notificação para administradores
 */
EventDispatcher::listen('model.nfe_document.contingency_deactivated', function (Event $event) use ($nfeLogFile, $notifyAdmins) {
    $data = $event->getData();
    $pendentes = $data['notas_pendentes'] ?? 0;

    $logMessage = sprintf(
        '[NF-e Contingência DESATIVADA] Notas pendentes de transmissão: %d',
        $pendentes
    );
    @file_put_contents($nfeLogFile, date('[Y-m-d H:i:s] ') . $logMessage . PHP_EOL, FILE_APPEND);

    $msg = 'Modo contingência desativado. Operação normalizada.';
    if ($pendentes > 0) {
        $msg .= "\n⚠️ Existem {$pendentes} nota(s) emitida(s) em contingência aguardando transmissão à SEFAZ.";
    }

    $notifyAdmins(
        '✅ Contingência Fiscal Desativada',
        $msg,
        $pendentes > 0 ? 'warning' : 'success'
    );
});

/**
 * model.nfe_document.authorized (expandido) — Envio de XML/DANFE por e-mail.
 * Segundo listener: envia e-mail com XML e DANFE para o destinatário.
 * Payload: nfe_id, order_id, chave
 */
EventDispatcher::listen('model.nfe_document.authorized', function (Event $event) use ($nfeLogFile, $getNfeDb, $sendInternalNotification) {
    $data = $event->getData();
    $nfeId   = $data['nfe_id'] ?? 0;
    $orderId = $data['order_id'] ?? null;

    if (!$nfeId || !$orderId) return;

    try {
        $db = $getNfeDb();
        if (!$db) return;

        // Buscar e-mail do destinatário via pedido → cliente
        $q = "SELECT c.email, c.name
              FROM orders o
              INNER JOIN customers c ON o.customer_id = c.id
              WHERE o.id = :oid";
        $s = $db->prepare($q);
        $s->execute([':oid' => $orderId]);
        $customer = $s->fetch(\PDO::FETCH_ASSOC);

        if (!$customer || empty($customer['email'])) {
            @file_put_contents($nfeLogFile, date('[Y-m-d H:i:s] ')
                . "[E-mail NF-e] NF-e #{$nfeId} — Destinatário sem e-mail, envio não realizado." . PHP_EOL, FILE_APPEND);
            return;
        }

        // Buscar caminhos do XML e DANFE
        $qDoc = "SELECT xml_path, danfe_path, numero, serie, chave FROM nfe_documents WHERE id = :nid";
        $sDoc = $db->prepare($qDoc);
        $sDoc->execute([':nid' => $nfeId]);
        $doc = $sDoc->fetch(\PDO::FETCH_ASSOC);

        if (!$doc) return;

        // Registrar no log que o envio seria feito
        // (A implementação real de envio de e-mail depende do mailer configurado)
        $logMessage = sprintf(
            '[E-mail NF-e] NF-e #%d (Nº %s série %s) — E-mail agendado para: %s (%s) | XML: %s | DANFE: %s',
            $nfeId,
            $doc['numero'] ?? '?',
            $doc['serie'] ?? '?',
            $customer['email'],
            $customer['name'] ?? '',
            $doc['xml_path'] ? 'Sim' : 'Não',
            $doc['danfe_path'] ? 'Sim' : 'Não'
        );
        @file_put_contents($nfeLogFile, date('[Y-m-d H:i:s] ') . $logMessage . PHP_EOL, FILE_APPEND);

        // TODO: Integrar com serviço de e-mail (PHPMailer, SMTP etc.)
        // Exemplo futuro:
        // $mailer->send([
        //     'to'          => $customer['email'],
        //     'subject'     => "NF-e #{$doc['numero']} — {$companyName}",
        //     'body'        => "Segue em anexo a NF-e referente ao pedido #{$orderId}.",
        //     'attachments' => array_filter([$doc['xml_path'], $doc['danfe_path']]),
        // ]);

        // Notificação interna
        $sendInternalNotification(
            '📧 E-mail NF-e #' . $nfeId,
            "XML e DANFE da NF-e #{$doc['numero']} agendados para envio ao cliente {$customer['name']} ({$customer['email']}).",
            'info'
        );

    } catch (\Throwable $e) {
        @file_put_contents($nfeLogFile, date('[Y-m-d H:i:s] ')
            . "[E-mail NF-e] NF-e #{$nfeId} — Exceção: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
    }
});

/**
 * model.nfe_document.cancelled (expandido) — Notificação e integração financeira.
 * Segundo listener: notifica admins e tenta estorno financeiro.
 * Payload: nfe_id, order_id
 */
EventDispatcher::listen('model.nfe_document.cancelled', function (Event $event) use ($nfeLogFile, $getNfeDb, $sendInternalNotification, $notifyAdmins) {
    $data = $event->getData();
    $nfeId   = $data['nfe_id'] ?? 0;
    $orderId = $data['order_id'] ?? null;

    // Notificar administradores
    $notifyAdmins(
        '🚫 NF-e Cancelada #' . $nfeId,
        "A NF-e #{$nfeId} (Pedido #{$orderId}) foi cancelada.",
        'warning'
    );

    // Salvar XML de cancelamento em disco
    if ($nfeId > 0) {
        try {
            $db = $getNfeDb();
            if (!$db) return;

            $qDoc = "SELECT xml_cancelamento, chave FROM nfe_documents WHERE id = :nid";
            $sDoc = $db->prepare($qDoc);
            $sDoc->execute([':nid' => $nfeId]);
            $doc = $sDoc->fetch(\PDO::FETCH_ASSOC);

            if ($doc && !empty($doc['xml_cancelamento']) && !empty($doc['chave'])) {
                $storage = new \Akti\Services\NfeStorageService();
                $cancelPath = $storage->saveXml($doc['chave'], $doc['xml_cancelamento'], 'cancelamento');

                if ($cancelPath) {
                    $db->prepare("UPDATE nfe_documents SET cancel_xml_path = :path WHERE id = :nid")
                       ->execute([':path' => $cancelPath, ':nid' => $nfeId]);

                    @file_put_contents($nfeLogFile, date('[Y-m-d H:i:s] ')
                        . "[Cancelamento] NF-e #{$nfeId} — XML de cancelamento salvo: {$cancelPath}" . PHP_EOL, FILE_APPEND);
                }
            }
        } catch (\Throwable $e) {
            @file_put_contents($nfeLogFile, date('[Y-m-d H:i:s] ')
                . "[Cancelamento] NF-e #{$nfeId} — Exceção ao salvar XML: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
        }
    }

    // Notificar o usuário que cancelou
    $sendInternalNotification(
        '🚫 NF-e Cancelada #' . $nfeId,
        "A NF-e #{$nfeId} do Pedido #{$orderId} foi cancelada com sucesso na SEFAZ.",
        'warning'
    );
});

/**
 * model.nfe_credential.updated — Log de auditoria quando credenciais são alteradas.
 * Payload: id, fields
 */
EventDispatcher::listen('model.nfe_credential.updated', function (Event $event) use ($nfeLogFile) {
    $data = $event->getData();
    $logMessage = sprintf(
        '[NF-e Credencial Atualizada] ID: %s | Campos: %s | Usuário: %s',
        $data['id'] ?? 'N/A',
        implode(', ', $data['fields'] ?? []),
        $_SESSION['user_name'] ?? 'Sistema'
    );
    @file_put_contents($nfeLogFile, date('[Y-m-d H:i:s] ') . $logMessage . PHP_EOL, FILE_APPEND);
});

/**
 * model.nfe_credential.cert_expiring — Certificado digital próximo da expiração.
 * Payload: days_remaining, expiry_date, credential_id
 *
 * Ações:
 *   - ≤30 dias: alerta informativo
 *   - ≤7 dias: alerta urgente
 *   - Expirado: alerta crítico
 */
EventDispatcher::listen('model.nfe_credential.cert_expiring', function (Event $event) use ($nfeLogFile, $notifyAdmins) {
    $data = $event->getData();
    $daysRemaining = (int) ($data['days_remaining'] ?? 999);
    $expiryDate    = $data['expiry_date'] ?? '';
    $credId        = $data['credential_id'] ?? 0;

    if ($daysRemaining <= 0) {
        $type    = 'danger';
        $title   = '🔴 Certificado Digital EXPIRADO';
        $message = "O certificado digital (ID: {$credId}) EXPIROU em {$expiryDate}. A emissão de NF-e está BLOQUEADA até a renovação.";
    } elseif ($daysRemaining <= 7) {
        $type    = 'danger';
        $title   = '🟠 Certificado expira em ' . $daysRemaining . ' dia(s)';
        $message = "O certificado digital (ID: {$credId}) expira em {$expiryDate}. AÇÃO URGENTE necessária para evitar bloqueio na emissão.";
    } elseif ($daysRemaining <= 15) {
        $type    = 'warning';
        $title   = '🟡 Certificado expira em ' . $daysRemaining . ' dias';
        $message = "O certificado digital (ID: {$credId}) expira em {$expiryDate}. Providencie a renovação.";
    } else {
        $type    = 'info';
        $title   = 'ℹ️ Certificado expira em ' . $daysRemaining . ' dias';
        $message = "O certificado digital (ID: {$credId}) expira em {$expiryDate}. Planeje a renovação.";
    }

    $logMessage = "[Certificado] {$title} — Dias restantes: {$daysRemaining} — Expira: {$expiryDate}";
    @file_put_contents($nfeLogFile, date('[Y-m-d H:i:s] ') . $logMessage . PHP_EOL, FILE_APPEND);

    $notifyAdmins($title, $message, $type);
});

/**
 * model.nfe_document.created — Log quando um novo documento fiscal é criado.
 * Payload: id, order_id, modelo, serie
 */
EventDispatcher::listen('model.nfe_document.created', function (Event $event) use ($nfeLogFile) {
    $data = $event->getData();
    $logMessage = sprintf(
        '[NF-e Criada] ID: %d | Pedido: %s | Modelo: %s | Série: %s | Usuário: %s',
        $data['id'] ?? 0,
        $data['order_id'] ?? 'N/A',
        $data['modelo'] ?? '?',
        $data['serie'] ?? '?',
        $_SESSION['user_name'] ?? 'Sistema'
    );
    @file_put_contents($nfeLogFile, date('[Y-m-d H:i:s] ') . $logMessage . PHP_EOL, FILE_APPEND);
});

// ── Listeners de módulos (adicionar conforme módulos forem instalados) ──
// require_once AKTI_BASE_PATH . 'app/modules/notificacoes/listeners.php';
// require_once AKTI_BASE_PATH . 'app/modules/integracao_erp/listeners.php';
// require_once AKTI_BASE_PATH . 'app/modules/webhooks/listeners.php';

// ══════════════════════════════════════════════════════════════
// FASE 4 — Integração NF-e × Financeiro
// Ao autorizar NF-e: marcar parcelas como "faturadas"
// Ao cancelar NF-e: estornar parcelas (remover flag faturada)
// ══════════════════════════════════════════════════════════════

/**
 * model.nfe_document.authorized (integração financeira) — Marcar parcelas como faturadas.
 * Payload: nfe_id, order_id, chave
 */
EventDispatcher::listen('model.nfe_document.authorized', function (Event $event) use ($nfeLogFile, $getNfeDb) {
    $data = $event->getData();
    $nfeId   = $data['nfe_id'] ?? 0;
    $orderId = $data['order_id'] ?? null;

    if (!$nfeId || !$orderId) return;

    try {
        $db = $getNfeDb();
        if (!$db) return;

        // Verificar configuração
        $q = $db->prepare("SELECT setting_value FROM company_settings WHERE setting_key = 'nfe_financial_auto_faturar' LIMIT 1");
        $q->execute();
        $enabled = $q->fetchColumn();
        if ($enabled === false || $enabled === '0') return;

        // Verificar se tabela order_installments tem a coluna nfe_faturada
        $checkCol = $db->query("SHOW COLUMNS FROM order_installments LIKE 'nfe_faturada'");
        if ($checkCol->rowCount() === 0) return;

        // Marcar todas as parcelas do pedido como faturadas
        $upd = $db->prepare("UPDATE order_installments SET nfe_faturada = 1, nfe_document_id = :nfe_id WHERE order_id = :oid AND nfe_faturada = 0");
        $upd->execute([':nfe_id' => $nfeId, ':oid' => $orderId]);
        $affected = $upd->rowCount();

        if ($affected > 0) {
            @file_put_contents($nfeLogFile, date('[Y-m-d H:i:s] ')
                . "[Financeiro] NF-e #{$nfeId} — {$affected} parcela(s) do pedido #{$orderId} marcada(s) como faturada(s)." . PHP_EOL, FILE_APPEND);
        }
    } catch (\Throwable $e) {
        @file_put_contents($nfeLogFile, date('[Y-m-d H:i:s] ')
            . "[Financeiro] NF-e #{$nfeId} — Exceção ao faturar parcelas: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
    }
});

/**
 * model.nfe_document.cancelled (integração financeira) — Estornar flag de faturamento nas parcelas.
 * Payload: nfe_id, order_id
 */
EventDispatcher::listen('model.nfe_document.cancelled', function (Event $event) use ($nfeLogFile, $getNfeDb) {
    $data = $event->getData();
    $nfeId   = $data['nfe_id'] ?? 0;
    $orderId = $data['order_id'] ?? null;

    if (!$nfeId || !$orderId) return;

    try {
        $db = $getNfeDb();
        if (!$db) return;

        // Verificar se coluna existe
        $checkCol = $db->query("SHOW COLUMNS FROM order_installments LIKE 'nfe_faturada'");
        if ($checkCol->rowCount() === 0) return;

        // Remover flag de faturamento das parcelas vinculadas a esta NF-e
        $upd = $db->prepare("UPDATE order_installments SET nfe_faturada = 0, nfe_document_id = NULL WHERE order_id = :oid AND nfe_document_id = :nfe_id");
        $upd->execute([':oid' => $orderId, ':nfe_id' => $nfeId]);
        $affected = $upd->rowCount();

        if ($affected > 0) {
            @file_put_contents($nfeLogFile, date('[Y-m-d H:i:s] ')
                . "[Financeiro Estorno] NF-e #{$nfeId} cancelada — {$affected} parcela(s) do pedido #{$orderId} desmarcada(s) como faturada(s)." . PHP_EOL, FILE_APPEND);
        }
    } catch (\Throwable $e) {
        @file_put_contents($nfeLogFile, date('[Y-m-d H:i:s] ')
            . "[Financeiro Estorno] NF-e #{$nfeId} — Exceção: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
    }
});

// ══════════════════════════════════════════════════════════════
// FASE 4 — Integração NF-e × Estoque
// Ao autorizar NF-e: dar baixa no estoque dos itens do pedido
// Ao cancelar NF-e: estornar (devolver) estoque
// ══════════════════════════════════════════════════════════════

/**
 * model.nfe_document.authorized (integração estoque) — Dar baixa no estoque.
 * Payload: nfe_id, order_id, chave
 */
EventDispatcher::listen('model.nfe_document.authorized', function (Event $event) use ($nfeLogFile, $getNfeDb) {
    $data = $event->getData();
    $nfeId   = $data['nfe_id'] ?? 0;
    $orderId = $data['order_id'] ?? null;

    if (!$nfeId || !$orderId) return;

    try {
        $db = $getNfeDb();
        if (!$db) return;

        // Verificar configuração
        $q = $db->prepare("SELECT setting_value FROM company_settings WHERE setting_key = 'nfe_stock_auto_debit' LIMIT 1");
        $q->execute();
        $enabled = $q->fetchColumn();
        if ($enabled === false || $enabled === '0') return;

        // Verificar se módulo de estoque está disponível
/**
 * Class Unknown.
 */
        if (!class_exists(\Akti\Models\Stock::class)) return;

        $stockModel = new \Akti\Models\Stock($db);

        // Buscar armazém padrão
        $defaultWarehouse = $stockModel->getDefaultWarehouse();
        if (!$defaultWarehouse) return;

        $warehouseId = (int) $defaultWarehouse['id'];

        // Buscar itens do pedido
        $qItems = $db->prepare("SELECT oi.product_id, oi.quantity, oi.combination_id, p.name as product_name
                                FROM order_items oi
                                LEFT JOIN products p ON oi.product_id = p.id
                                WHERE oi.order_id = :oid AND oi.product_id IS NOT NULL");
        $qItems->execute([':oid' => $orderId]);
        $items = $qItems->fetchAll(\PDO::FETCH_ASSOC);

        $totalMoved = 0;
        foreach ($items as $item) {
            if (empty($item['product_id']) || (float)$item['quantity'] <= 0) continue;

            try {
                $stockModel->addMovement([
                    'warehouse_id'    => $warehouseId,
                    'product_id'      => (int) $item['product_id'],
                    'combination_id'  => $item['combination_id'] ?? null,
                    'type'            => 'saida',
                    'quantity'        => (float) $item['quantity'],
                    'reason'          => "Baixa automática — NF-e #{$nfeId} (Pedido #{$orderId})",
                    'reference_type'  => 'nfe',
                    'reference_id'    => $nfeId,
                ]);
                $totalMoved++;
            } catch (\Throwable $e) {
                @file_put_contents($nfeLogFile, date('[Y-m-d H:i:s] ')
                    . "[Estoque] NF-e #{$nfeId} — Erro ao dar baixa no produto #{$item['product_id']}: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
            }
        }

        if ($totalMoved > 0) {
            @file_put_contents($nfeLogFile, date('[Y-m-d H:i:s] ')
                . "[Estoque] NF-e #{$nfeId} — Baixa automática: {$totalMoved} item(ns) do pedido #{$orderId} (armazém #{$warehouseId})." . PHP_EOL, FILE_APPEND);
        }
    } catch (\Throwable $e) {
        @file_put_contents($nfeLogFile, date('[Y-m-d H:i:s] ')
            . "[Estoque] NF-e #{$nfeId} — Exceção ao dar baixa: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
    }
});

/**
 * model.nfe_document.cancelled (integração estoque) — Estornar estoque.
 * Payload: nfe_id, order_id
 */
EventDispatcher::listen('model.nfe_document.cancelled', function (Event $event) use ($nfeLogFile, $getNfeDb) {
    $data = $event->getData();
    $nfeId   = $data['nfe_id'] ?? 0;
    $orderId = $data['order_id'] ?? null;

    if (!$nfeId || !$orderId) return;

    try {
        $db = $getNfeDb();
        if (!$db) return;

        // Verificar configuração
        $q = $db->prepare("SELECT setting_value FROM company_settings WHERE setting_key = 'nfe_stock_auto_debit' LIMIT 1");
        $q->execute();
        $enabled = $q->fetchColumn();
        if ($enabled === false || $enabled === '0') return;

/**
 * Class Unknown.
 */
        if (!class_exists(\Akti\Models\Stock::class)) return;

        $stockModel = new \Akti\Models\Stock($db);
        $defaultWarehouse = $stockModel->getDefaultWarehouse();
        if (!$defaultWarehouse) return;

        $warehouseId = (int) $defaultWarehouse['id'];

        // Buscar movimentações de saída desta NF-e para estornar
        $qMov = $db->prepare("SELECT sm.product_id, sm.combination_id, sm.quantity, p.name as product_name
                              FROM stock_movements sm
                              LEFT JOIN products p ON sm.product_id = p.id
                              WHERE sm.reference_type = 'nfe' AND sm.reference_id = :nfe_id AND sm.type = 'saida'");
        $qMov->execute([':nfe_id' => $nfeId]);
        $movements = $qMov->fetchAll(\PDO::FETCH_ASSOC);

        $totalReversed = 0;
        foreach ($movements as $mov) {
            if ((float)$mov['quantity'] <= 0) continue;

            try {
                $stockModel->addMovement([
                    'warehouse_id'    => $warehouseId,
                    'product_id'      => (int) $mov['product_id'],
                    'combination_id'  => $mov['combination_id'] ?? null,
                    'type'            => 'entrada',
                    'quantity'        => (float) $mov['quantity'],
                    'reason'          => "Estorno — NF-e #{$nfeId} cancelada (Pedido #{$orderId})",
                    'reference_type'  => 'nfe_cancel',
                    'reference_id'    => $nfeId,
                ]);
                $totalReversed++;
            } catch (\Throwable $e) {
                @file_put_contents($nfeLogFile, date('[Y-m-d H:i:s] ')
                    . "[Estoque Estorno] NF-e #{$nfeId} — Erro ao estornar produto #{$mov['product_id']}: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
            }
        }

        if ($totalReversed > 0) {
            @file_put_contents($nfeLogFile, date('[Y-m-d H:i:s] ')
                . "[Estoque Estorno] NF-e #{$nfeId} cancelada — {$totalReversed} item(ns) estornados do pedido #{$orderId}." . PHP_EOL, FILE_APPEND);
        }
    } catch (\Throwable $e) {
        @file_put_contents($nfeLogFile, date('[Y-m-d H:i:s] ')
            . "[Estoque Estorno] NF-e #{$nfeId} — Exceção: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
    }
});

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

// ══════════════════════════════════════════════════════════════
// Listeners de Domínio — Pedidos, Clientes, Segurança
// (ARQ-008 — Ativação de eventos que eram disparados sem listeners)
// ══════════════════════════════════════════════════════════════

$auditLogFile = (defined('AKTI_BASE_PATH') ? AKTI_BASE_PATH : '') . 'storage/logs/audit.log';
$securityLogFile = (defined('AKTI_BASE_PATH') ? AKTI_BASE_PATH : '') . 'storage/logs/security.log';

/**
 * Helper genérico de log em arquivo.
 */
$writeLog = function (string $file, string $message): void {
    $dir = dirname($file);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    @file_put_contents($file, date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
};

/**
 * model.order.created — Pedido criado.
 * Payload: id, customer_id, total (conforme Order.php dispatch)
 *
 * Ações:
 *   1. Log de auditoria
 *   2. Notificação para admins (novo pedido no pipeline)
 */
EventDispatcher::listen('model.order.created', function (Event $event) use ($auditLogFile, $writeLog, $notifyAdmins) {
    $data = $event->getData();
    $orderId    = $data['id'] ?? 0;
    $customerId = $data['customer_id'] ?? 'N/A';
    $total      = $data['total'] ?? 0;

    $writeLog($auditLogFile, sprintf(
        '[Pedido Criado] ID: %d | Cliente: %s | Total: R$ %s | Usuário: %s',
        $orderId,
        $customerId,
        number_format((float) $total, 2, ',', '.'),
        $_SESSION['user_name'] ?? 'Sistema'
    ));

    try {
        $notifyAdmins(
            'Novo Pedido #' . $orderId,
            sprintf('Pedido #%d criado (R$ %s). Verifique o pipeline.', $orderId, number_format((float) $total, 2, ',', '.')),
            'info'
        );
    } catch (\Throwable $e) {
        // best-effort
    }
});

/**
 * model.customer.created — Cliente criado.
 * Payload: id, name, email (conforme Customer.php dispatch)
 *
 * Ações:
 *   1. Log de auditoria
 */
EventDispatcher::listen('model.customer.created', function (Event $event) use ($auditLogFile, $writeLog) {
    $data = $event->getData();

    $writeLog($auditLogFile, sprintf(
        '[Cliente Criado] ID: %d | Nome: %s | Email: %s | Usuário: %s',
        $data['id'] ?? 0,
        $data['name'] ?? '',
        $data['email'] ?? '',
        $_SESSION['user_name'] ?? 'Sistema'
    ));
});

/**
 * auth.login.failed — Tentativa de login falha.
 * Payload: email, ip, user_agent
 *
 * Ações:
 *   1. Log de segurança
 */
EventDispatcher::listen('auth.login.failed', function (Event $event) use ($securityLogFile, $writeLog) {
    $data = $event->getData();

    $writeLog($securityLogFile, sprintf(
        '[Login Falhou] Email: %s | IP: %s | User-Agent: %s',
        $data['email'] ?? '(vazio)',
        $data['ip'] ?? ($_SERVER['REMOTE_ADDR'] ?? '?'),
        $data['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? '?')
    ));
});

/**
 * middleware.csrf.failed — Token CSRF inválido detectado.
 * Payload: ip, page, action, method
 *
 * Ações:
 *   1. Log de segurança
 *   2. Alerta para administradores (possível CSRF attack)
 */
EventDispatcher::listen('middleware.csrf.failed', function (Event $event) use ($securityLogFile, $writeLog, $notifyAdmins) {
    $data = $event->getData();

    $msg = sprintf(
        '[CSRF Falhou] IP: %s | Page: %s | Action: %s | Method: %s | User: %s',
        $data['ip'] ?? ($_SERVER['REMOTE_ADDR'] ?? '?'),
        $data['page'] ?? '?',
        $data['action'] ?? '?',
        $data['method'] ?? ($_SERVER['REQUEST_METHOD'] ?? '?'),
        $_SESSION['user_name'] ?? 'Não autenticado'
    );

    $writeLog($securityLogFile, $msg);

    try {
        $notifyAdmins(
            'Alerta CSRF',
            "Token CSRF inválido detectado.\n" . $msg,
            'danger'
        );
    } catch (\Throwable $e) {
        // best-effort
    }
});

// ══════════════════════════════════════════════════════════════
// FEAT-004 — Audit Log Universal Listener
// Persiste alterações em audit_logs via AuditLogService
// ══════════════════════════════════════════════════════════════

$auditEvents = [
    'model.order.created'     => ['entity' => 'order',    'action' => 'created'],
    'model.order.updated'     => ['entity' => 'order',    'action' => 'updated'],
    'model.customer.created'  => ['entity' => 'customer', 'action' => 'created'],
    'model.customer.updated'  => ['entity' => 'customer', 'action' => 'updated'],
    'model.supplier.created'  => ['entity' => 'supplier', 'action' => 'created'],
    'model.supplier.updated'  => ['entity' => 'supplier', 'action' => 'updated'],
    'model.supplier.deleted'  => ['entity' => 'supplier', 'action' => 'deleted'],
    'model.quote.created'     => ['entity' => 'quote',    'action' => 'created'],
    'model.quote.updated'     => ['entity' => 'quote',    'action' => 'updated'],
    'model.quote.approved'    => ['entity' => 'quote',    'action' => 'approved'],
    'model.product.created'   => ['entity' => 'product',  'action' => 'created'],
    'model.product.updated'   => ['entity' => 'product',  'action' => 'updated'],
];

foreach ($auditEvents as $eventName => $meta) {
    EventDispatcher::listen($eventName, function (Event $event) use ($meta) {
        try {
            $db = (new \Database())->getConnection();
            $service = new \Akti\Services\AuditLogService($db);
            $data = $event->getData();
            $service->log(
                $meta['action'],
                $meta['entity'],
                $data['id'] ?? 0,
                $data['old_values'] ?? [],
                $data['new_values'] ?? ($data ?? [])
            );
        } catch (\Throwable $e) {
            // best-effort — never break the main flow
        }
    });
}

// ══════════════════════════════════════════════════════════════
// Listeners do Módulo de Insumos
// ══════════════════════════════════════════════════════════════

$supplyLogFile = (defined('AKTI_BASE_PATH') ? AKTI_BASE_PATH : '') . 'storage/logs/supply.log';

/**
 * model.supply.price_changed — CMP do insumo foi alterado após entrada de estoque.
 * Payload: supply_id, old_cmp, new_cmp
 *
 * Ações:
 *   1. Log da alteração de custo
 *   2. Buscar produtos afetados (BOM) e armazenar impacto em sessão
 */
EventDispatcher::listen('model.supply.price_changed', function (Event $event) use ($supplyLogFile) {
    $data = $event->getData();
    $supplyId = (int) ($data['supply_id'] ?? 0);
    $oldCmp   = $data['old_cmp'] ?? 0;
    $newCmp   = $data['new_cmp'] ?? 0;

    $logMessage = sprintf(
        '[Insumo CMP Alterado] ID: %d | CMP Anterior: %.4f | CMP Novo: %.4f | Variação: %.2f%%',
        $supplyId,
        $oldCmp,
        $newCmp,
        $oldCmp > 0 ? (($newCmp - $oldCmp) / $oldCmp) * 100 : 0
    );

    $dir = dirname($supplyLogFile);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    @file_put_contents($supplyLogFile, date('[Y-m-d H:i:s] ') . $logMessage . PHP_EOL, FILE_APPEND);

    // Buscar produtos afetados e armazenar impacto em sessão para exibição
    try {
        $db = (new \Database())->getConnection();
        $supply = new \Akti\Models\Supply($db);
        $affectedProducts = $supply->getAffectedProducts($supplyId);

        if (!empty($affectedProducts)) {
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['supply_price_impact'] = [
                    'supply_id'  => $supplyId,
                    'old_cmp'    => $oldCmp,
                    'new_cmp'    => $newCmp,
                    'products'   => $supply->getWhereUsedImpact($supplyId, $newCmp),
                ];
            }
        }
    } catch (\Throwable $e) {
        @file_put_contents($supplyLogFile, date('[Y-m-d H:i:s] ') . '[Erro Impact] ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
    }
});

/**
 * model.supply_stock.reorder_alert — Estoque atingiu ponto de pedido.
 * Payload: supply_id, supply_name, total_stock, reorder_point
 */
EventDispatcher::listen('model.supply_stock.reorder_alert', function (Event $event) use ($supplyLogFile) {
    $data = $event->getData();
    $logMessage = sprintf(
        '[Alerta Reposição] Insumo: %s (ID %d) | Estoque: %.2f | Ponto de Pedido: %.2f',
        $data['supply_name'] ?? '?',
        $data['supply_id'] ?? 0,
        $data['total_stock'] ?? 0,
        $data['reorder_point'] ?? 0
    );
    $dir = dirname($supplyLogFile);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    @file_put_contents($supplyLogFile, date('[Y-m-d H:i:s] ') . $logMessage . PHP_EOL, FILE_APPEND);
});

// ══════════════════════════════════════════════════════════════
// FEAT-010 — Workflow Engine Global Listener
// Dispatches events to the WorkflowEngine for rule evaluation
// ══════════════════════════════════════════════════════════════

$workflowEvents = [
    'model.order.created',
    'model.order.updated',
    'model.order.stage_changed',
    'model.customer.created',
    'model.customer.updated',
    'model.installment.paid',
    'model.installment.overdue',
    'model.supplier.created',
    'model.quote.created',
    'model.quote.approved',
    'model.nfe_document.authorized',
    'auth.login.failed',
    'model.supply.created',
    'model.supply.updated',
    'model.supply.deleted',
    'model.supply.cost_updated',
    'model.supply.price_changed',
    'model.supply.supplier_linked',
    'model.supply.product_linked',
    'model.supply_stock.reorder_alert',
];

foreach ($workflowEvents as $wfEvent) {
    EventDispatcher::listen($wfEvent, function (Event $event) use ($wfEvent) {
        try {
            $db = (new \Database())->getConnection();
            $engine = new \Akti\Services\WorkflowEngine($db);
            $engine->process($wfEvent, $event->getData());
        } catch (\Throwable $e) {
            // best-effort — workflow failures must not break main flow
            $logFile = (defined('AKTI_BASE_PATH') ? AKTI_BASE_PATH : '') . 'storage/logs/workflow.log';
            @file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "[WorkflowEngine Error] {$wfEvent}: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
        }
    });
}

// ══════════════════════════════════════════════════════════════
// ARCH-010 — Expanded Event Listeners
// Adds listeners for previously orphaned events and new
// business-critical events (pipeline, orders, products, portal)
// ══════════════════════════════════════════════════════════════

$businessLogFile = (defined('AKTI_BASE_PATH') ? AKTI_BASE_PATH : '') . 'storage/logs/business.log';

/**
 * model.pipeline.completed — Pedido concluiu o pipeline de produção.
 * Payload: id, from_stage, user_id
 */
EventDispatcher::listen('model.pipeline.completed', function (Event $event) use ($businessLogFile, $writeLog, $notifyAdmins) {
    $data = $event->getData();

    $writeLog($businessLogFile, sprintf(
        '[Pipeline Concluído] Pedido #%d | Etapa anterior: %s | Usuário: %d',
        $data['id'] ?? 0,
        $data['from_stage'] ?? '?',
        $data['user_id'] ?? 0
    ));

    try {
        $notifyAdmins(
            'Pipeline Concluído',
            sprintf('Pedido #%d concluiu o pipeline de produção.', $data['id'] ?? 0),
            'success'
        );
    } catch (\Throwable $e) {
        // best-effort
    }
});

/**
 * model.order.deleted — Pedido foi excluído.
 * Payload: id
 */
EventDispatcher::listen('model.order.deleted', function (Event $event) use ($businessLogFile, $writeLog) {
    $data = $event->getData();

    $writeLog($businessLogFile, sprintf(
        '[Pedido Excluído] ID: %d | Usuário: %s',
        $data['id'] ?? 0,
        $_SESSION['user_name'] ?? 'Sistema'
    ));
});

/**
 * model.product.cost_updated — Custo do produto foi recalculado.
 * Payload: product_id, old_cost, new_cost
 */
EventDispatcher::listen('model.product.cost_updated', function (Event $event) use ($businessLogFile, $writeLog) {
    $data = $event->getData();

    $writeLog($businessLogFile, sprintf(
        '[Custo Atualizado] Produto #%d | Anterior: %.2f | Novo: %.2f',
        $data['product_id'] ?? 0,
        $data['old_cost'] ?? 0,
        $data['new_cost'] ?? 0
    ));
});

/**
 * controller.user.logout — Usuário fez logout.
 * Payload: user_id, user_name
 */
EventDispatcher::listen('controller.user.logout', function (Event $event) use ($securityLogFile, $writeLog) {
    $data = $event->getData();

    $writeLog($securityLogFile, sprintf(
        '[Logout] Usuário: %s (ID: %d) | IP: %s',
        $data['user_name'] ?? '?',
        $data['user_id'] ?? 0,
        $_SERVER['REMOTE_ADDR'] ?? '?'
    ));
});

/**
 * portal.order.rejected — Cliente rejeitou pedido no portal.
 * Payload: order_id, customer_id, reason
 */
EventDispatcher::listen('portal.order.rejected', function (Event $event) use ($businessLogFile, $writeLog, $notifyAdmins) {
    $data = $event->getData();

    $writeLog($businessLogFile, sprintf(
        '[Pedido Rejeitado pelo Cliente] Pedido #%d | Cliente ID: %d | Motivo: %s',
        $data['order_id'] ?? 0,
        $data['customer_id'] ?? 0,
        $data['reason'] ?? '(não informado)'
    ));

    try {
        $notifyAdmins(
            'Pedido Rejeitado',
            sprintf('O cliente rejeitou o pedido #%d. Motivo: %s', $data['order_id'] ?? 0, $data['reason'] ?? '—'),
            'warning'
        );
    } catch (\Throwable $e) {
        // best-effort
    }
});

/**
 * portal.message.sent — Mensagem enviada no portal.
 * Payload: order_id, sender, message
 */
EventDispatcher::listen('portal.message.sent', function (Event $event) use ($businessLogFile, $writeLog) {
    $data = $event->getData();

    $writeLog($businessLogFile, sprintf(
        '[Portal Mensagem] Pedido #%d | De: %s',
        $data['order_id'] ?? 0,
        $data['sender'] ?? '?'
    ));
});

// Audit log expansion: delete events
$auditDeleteEvents = [
    'model.order.deleted'    => ['entity' => 'order',    'action' => 'deleted'],
    'model.customer.deleted' => ['entity' => 'customer', 'action' => 'deleted'],
    'model.product.deleted'  => ['entity' => 'product',  'action' => 'deleted'],
    'model.category.deleted' => ['entity' => 'category', 'action' => 'deleted'],
];

foreach ($auditDeleteEvents as $eventName => $meta) {
    EventDispatcher::listen($eventName, function (Event $event) use ($meta) {
        try {
            $db = (new \Database())->getConnection();
            $service = new \Akti\Services\AuditLogService($db);
            $data = $event->getData();
            $service->log(
                $meta['action'],
                $meta['entity'],
                $data['id'] ?? 0,
                $data ?? [],
                []
            );
        } catch (\Throwable $e) {
            // best-effort
        }
    });
}
