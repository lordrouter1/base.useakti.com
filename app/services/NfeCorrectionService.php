<?php

namespace Akti\Services;

use Akti\Core\Log;
use Akti\Core\EventDispatcher;
use Akti\Core\Event;
use Akti\Models\NfeDocument;
use Akti\Models\NfeLog;
use PDO;

/**
 * NfeCorrectionService — Carta de Correção (CC-e) de NF-e.
 *
 * @package Akti\Services
 */
class NfeCorrectionService
{
    private PDO $db;
    private NfeSefazClient $sefazClient;
    private NfeDocument $docModel;
    private NfeLog $logModel;

    /**
     * Construtor da classe NfeCorrectionService.
     *
     * @param PDO $db Conexão PDO com o banco de dados
     * @param NfeSefazClient $sefazClient Sefaz client
     * @param NfeDocument $docModel Doc model
     * @param NfeLog $logModel Log model
     */
    public function __construct(PDO $db, NfeSefazClient $sefazClient, NfeDocument $docModel, NfeLog $logModel)
    {
        $this->db = $db;
        $this->sefazClient = $sefazClient;
        $this->docModel = $docModel;
        $this->logModel = $logModel;
    }

    /**
     * Envia Carta de Correção (CC-e).
     * @param int    $nfeId  ID do registro nfe_documents
     * @param string $texto  Texto da correção (mín 15 chars)
     * @return array ['success' => bool, 'message' => string]
     */
    public function correction(int $nfeId, string $texto): array
    {
        $doc = $this->docModel->readOne($nfeId);
        if (!$doc) {
            return ['success' => false, 'message' => 'NF-e não encontrada.'];
        }
        if (!in_array($doc['status'], ['autorizada', 'corrigida'])) {
            return ['success' => false, 'message' => 'Carta de correção só pode ser enviada para NF-e autorizada.'];
        }
        if (strlen($texto) < 15) {
            return ['success' => false, 'message' => 'Texto da correção deve ter no mínimo 15 caracteres.'];
        }

        // Verificar limite de 20 CC-e por NF-e (regra SEFAZ)
        $seqAtual = (int) ($doc['correcao_seq'] ?? 0);
        if ($seqAtual >= 20) {
            return [
                'success' => false,
                'message' => 'Limite de 20 Cartas de Correção atingido para esta NF-e. '
                           . 'Não é possível enviar novas correções.',
            ];
        }

        if (!$this->sefazClient->initTools()) {
            return ['success' => false, 'message' => 'Erro ao inicializar comunicação com SEFAZ.'];
        }

        $tools = $this->sefazClient->getTools();

        try {
            $chave = $doc['chave'];
            $seqEvento = ($doc['correcao_seq'] ?? 0) + 1;

            $response = $tools->sefazCCe($chave, $texto, $seqEvento);
            $st = new \NFePHP\NFe\Common\Standardize($response);
            $std = $st->toStd();

            $cStat = $std->retEvento->infEvento->cStat ?? '';
            $xMotivo = $std->retEvento->infEvento->xMotivo ?? '';

            $this->logModel->create([
                'nfe_document_id' => $nfeId,
                'order_id'        => $doc['order_id'],
                'action'          => 'correcao',
                'status'          => in_array($cStat, ['135', '155']) ? 'success' : 'error',
                'code_sefaz'      => $cStat,
                'message'         => $xMotivo,
                'xml_response'    => $response,
            ]);

            if (in_array($cStat, ['135', '155'])) {
                $this->docModel->update($nfeId, [
                    'status'         => 'corrigida',
                    'correcao_texto' => $texto,
                    'correcao_seq'   => $seqEvento,
                    'correcao_date'  => date('Y-m-d H:i:s'),
                    'xml_correcao'   => $response,
                ]);

                // Salvar no histórico de CC-e
                $nProt = $std->retEvento->infEvento->nProt ?? '';
                try {
                    $qHist = "INSERT INTO nfe_correction_history
                              (nfe_document_id, seq_evento, texto_correcao, protocolo, code_sefaz, motivo_sefaz, xml_correcao, user_id)
                              VALUES (:doc_id, :seq, :texto, :prot, :cstat, :motivo, :xml, :uid)";
                    $sHist = $this->db->prepare($qHist);
                    $sHist->execute([
                        ':doc_id' => $nfeId,
                        ':seq'    => $seqEvento,
                        ':texto'  => $texto,
                        ':prot'   => $nProt,
                        ':cstat'  => $cStat,
                        ':motivo' => $xMotivo,
                        ':xml'    => $response,
                        ':uid'    => $_SESSION['user_id'] ?? null,
                    ]);
                } catch (\Exception $e) {
                    Log::error('NfeCorrectionService: Erro ao salvar histórico CC-e', ['exception' => $e->getMessage()]);
                }

                EventDispatcher::dispatch('model.nfe_document.corrected', new Event('model.nfe_document.corrected', [
                    'nfe_id'    => $nfeId,
                    'order_id'  => $doc['order_id'] ?? null,
                    'chave'     => $doc['chave'] ?? '',
                    'seq'       => $seqEvento,
                    'texto'     => $texto,
                    'protocolo' => $nProt,
                ]));

                return ['success' => true, 'message' => "Carta de Correção enviada com sucesso (seq: {$seqEvento})."];
            }

            return ['success' => false, 'message' => "SEFAZ: {$xMotivo} (cStat: {$cStat})"];

        } catch (\Exception $e) {
            $this->logModel->create([
                'nfe_document_id' => $nfeId,
                'order_id'        => $doc['order_id'],
                'action'          => 'correcao',
                'status'          => 'error',
                'message'         => $e->getMessage(),
            ]);
            return ['success' => false, 'message' => 'Erro na carta de correção: ' . $e->getMessage()];
        }
    }
}
