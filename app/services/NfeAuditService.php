<?php
namespace Akti\Services;

use Akti\Core\Log;

use Akti\Models\NfeAuditLog;
use PDO;

/**
 * NfeAuditService — Registra trilha de auditoria para o módulo NF-e.
 *
 * Ações registradas:
 *   - view: visualização de NF-e
 *   - emit: emissão de NF-e
 *   - cancel: cancelamento
 *   - correct: carta de correção
 *   - download_xml: download de XML
 *   - download_danfe: download de DANFE
 *   - credentials_update: alteração de credenciais
 *   - credentials_view: visualização de credenciais
 *   - manifestation: manifestação do destinatário
 *   - distdfe_query: consulta DistDFe
 *   - batch_emit: emissão em lote
 *   - webhook_config: configuração de webhook
 *   - inutilizar: inutilização de numeração
 *
 * @package Akti\Services
 */
class NfeAuditService
{
    private PDO $db;
    private NfeAuditLog $model;
    private bool $enabled;

    /**
     * Construtor da classe NfeAuditService.
     *
     * @param PDO $db Conexão PDO com o banco de dados
     */
    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->model = new NfeAuditLog($db);

        // Verificar se auditoria está habilitada
        try {
            $q = $db->prepare("SELECT setting_value FROM company_settings WHERE setting_key = 'nfe_audit_enabled' LIMIT 1");
            $q->execute();
            $val = $q->fetchColumn();
            $this->enabled = ($val !== false && $val !== '0');
        } catch (\Throwable $e) {
            $this->enabled = true; // Default: habilitado
        }
    }

    /**
     * Registra uma ação de auditoria.
     *
     * @param string   $action      Tipo da ação (view, emit, cancel, etc.)
     * @param string   $entityType  Tipo da entidade (nfe_document, nfe_credential, etc.)
     * @param int|null $entityId    ID da entidade
     * @param string   $description Descrição legível
     * @param array    $extraData   Dados adicionais (JSON)
     * @return int|null ID do registro ou null se desabilitado
     */
    public function record(
        string $action,
        string $entityType,
        ?int $entityId = null,
        string $description = '',
        array $extraData = []
    ): ?int {
        if (!$this->enabled) {
            return null;
        }

        try {
            return $this->model->log([
                'action'      => $action,
                'entity_type' => $entityType,
                'entity_id'   => $entityId,
                'description' => $description,
                'extra_data'  => !empty($extraData) ? $extraData : null,
            ]);
        } catch (\Throwable $e) {
            Log::error('NfeAuditService: Erro ao registrar auditoria', ['exception' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Atalhos de registro.
     */
    public function logView(int $nfeId, string $description = ''): void
    {
        $this->record('view', 'nfe_document', $nfeId, $description ?: "Visualizou NF-e #{$nfeId}");
    }

 /**
  * Log emit.
  *
  * @param int $nfeId Nfe id
  * @param int $orderId ID do pedido
  * @param string $chave Chave
  * @return void
  */
    public function logEmit(int $nfeId, int $orderId, string $chave = ''): void
    {
        $this->record('emit', 'nfe_document', $nfeId, "Emitiu NF-e #{$nfeId} (Pedido #{$orderId})", [
            'order_id' => $orderId,
            'chave'    => $chave,
        ]);
    }

 /**
  * Log cancel.
  *
  * @param int $nfeId Nfe id
  * @param string $motivo Motivo
  * @return void
  */
    public function logCancel(int $nfeId, string $motivo = ''): void
    {
        $this->record('cancel', 'nfe_document', $nfeId, "Cancelou NF-e #{$nfeId}", ['motivo' => $motivo]);
    }

 /**
  * Log correction.
  *
  * @param int $nfeId Nfe id
  * @param int $seq Seq
  * @param string $texto Texto
  * @return void
  */
    public function logCorrection(int $nfeId, int $seq, string $texto): void
    {
        $this->record('correct', 'nfe_document', $nfeId, "CC-e seq {$seq} para NF-e #{$nfeId}", [
            'seq'   => $seq,
            'texto' => mb_substr($texto, 0, 200),
        ]);
    }

 /**
  * Log download xml.
  *
  * @param int $nfeId Nfe id
  * @return void
  */
    public function logDownloadXml(int $nfeId): void
    {
        $this->record('download_xml', 'nfe_document', $nfeId, "Download XML da NF-e #{$nfeId}");
    }

 /**
  * Log download danfe.
  *
  * @param int $nfeId Nfe id
  * @return void
  */
    public function logDownloadDanfe(int $nfeId): void
    {
        $this->record('download_danfe', 'nfe_document', $nfeId, "Download DANFE da NF-e #{$nfeId}");
    }

 /**
  * Log credentials update.
  *
  * @param array $fields Campos a processar
  * @return void
  */
    public function logCredentialsUpdate(array $fields = []): void
    {
        $this->record('credentials_update', 'nfe_credential', 1, 'Credenciais SEFAZ atualizadas', [
            'fields' => $fields,
        ]);
    }

 /**
  * Log credentials view.
  * @return void
  */
    public function logCredentialsView(): void
    {
        $this->record('credentials_view', 'nfe_credential', 1, 'Visualizou credenciais SEFAZ');
    }

    /**
     * Registra upload de certificado digital (FASE4-04).
     */
    public function logCertificateUpload(string $ext = 'pfx'): void
    {
        $this->record('credential_cert_upload', 'nfe_credential', 1, "Upload de certificado digital ({$ext})");
    }

 /**
  * Log manifestation.
  *
  * @param int $docId Doc id
  * @param string $tipo Tipo
  * @param string $chave Chave
  * @return void
  */
    public function logManifestation(int $docId, string $tipo, string $chave): void
    {
        $this->record('manifestation', 'nfe_received_document', $docId, "Manifestação '{$tipo}' para NF-e {$chave}");
    }

 /**
  * Log dist d fe.
  *
  * @param int $totalDocs Total docs
  * @return void
  */
    public function logDistDFe(int $totalDocs): void
    {
        $this->record('distdfe_query', 'nfe_received_document', null, "Consulta DistDFe — {$totalDocs} documento(s) recebido(s)");
    }

 /**
  * Log batch emit.
  *
  * @param int $count Count
  * @param string $batchId Batch id
  * @return void
  */
    public function logBatchEmit(int $count, string $batchId): void
    {
        $this->record('batch_emit', 'nfe_queue', null, "Emissão em lote — {$count} pedido(s) (batch: {$batchId})");
    }

 /**
  * Log inutilizar.
  *
  * @param int $numInicial Num inicial
  * @param int $numFinal Num final
  * @param string $justificativa Justificativa
  * @return void
  */
    public function logInutilizar(int $numInicial, int $numFinal, string $justificativa): void
    {
        $this->record('inutilizar', 'nfe_document', null, "Inutilização #{$numInicial} a #{$numFinal}", [
            'num_inicial'   => $numInicial,
            'num_final'     => $numFinal,
            'justificativa' => mb_substr($justificativa, 0, 200),
        ]);
    }

    /**
     * Retorna o model para uso direto (leituras paginadas etc.).
     * @return NfeAuditLog
     */
    public function getModel(): NfeAuditLog
    {
        return $this->model;
    }
}
