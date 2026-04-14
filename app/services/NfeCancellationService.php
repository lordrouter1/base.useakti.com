<?php

namespace Akti\Services;

use Akti\Models\NfeDocument;
use Akti\Models\NfeLog;

/**
 * NfeCancellationService — Cancelamento de NF-e.
 *
 * @package Akti\Services
 */
class NfeCancellationService
{
    private NfeSefazClient $sefazClient;
    private NfeDocument $docModel;
    private NfeLog $logModel;

    public function __construct(NfeSefazClient $sefazClient, NfeDocument $docModel, NfeLog $logModel)
    {
        $this->sefazClient = $sefazClient;
        $this->docModel = $docModel;
        $this->logModel = $logModel;
    }

    /**
     * Cancela uma NF-e autorizada.
     * @param int    $nfeId   ID do registro nfe_documents
     * @param string $motivo  Justificativa (mín 15 caracteres)
     * @return array ['success' => bool, 'message' => string]
     */
    public function cancel(int $nfeId, string $motivo): array
    {
        $doc = $this->docModel->readOne($nfeId);
        if (!$doc) {
            return ['success' => false, 'message' => 'NF-e não encontrada.'];
        }
        if ($doc['status'] !== 'autorizada') {
            return ['success' => false, 'message' => 'Apenas NF-e autorizadas podem ser canceladas.'];
        }
        if (strlen($motivo) < 15) {
            return ['success' => false, 'message' => 'Justificativa deve ter no mínimo 15 caracteres.'];
        }

        // Verificar prazo de cancelamento (24 horas após autorização)
        $emittedAt = $doc['emitted_at'] ?? null;
        if ($emittedAt) {
            $emittedDate = new \DateTime($emittedAt);
            $now = new \DateTime();
            $diffHours = ($now->getTimestamp() - $emittedDate->getTimestamp()) / 3600;
            $prazoMaximo = 24;

            if ($diffHours > $prazoMaximo) {
                $horasDecorridas = number_format($diffHours, 1, ',', '.');
                return [
                    'success' => false,
                    'message' => "Prazo de cancelamento excedido. A NF-e foi autorizada há {$horasDecorridas} horas. "
                               . "O prazo máximo para cancelamento é de {$prazoMaximo} horas após a autorização. "
                               . "Utilize Carta de Correção ou entre em contato com a SEFAZ.",
                ];
            }
        }

        if (!$this->sefazClient->initTools()) {
            return ['success' => false, 'message' => 'Erro ao inicializar comunicação com SEFAZ.'];
        }

        $tools = $this->sefazClient->getTools();
        $credentials = $this->sefazClient->getCredentials();

        try {
            $chave = $doc['chave'];
            $protocolo = $doc['protocolo'];
            $cnpj = preg_replace('/\D/', '', $credentials['cnpj'] ?? '');

            $response = $tools->sefazCancela($chave, $motivo, $protocolo);
            $st = new \NFePHP\NFe\Common\Standardize($response);
            $std = $st->toStd();

            $cStat = $std->retEvento->infEvento->cStat ?? '';
            $xMotivo = $std->retEvento->infEvento->xMotivo ?? '';
            $nProt = $std->retEvento->infEvento->nProt ?? '';

            $this->logModel->create([
                'nfe_document_id' => $nfeId,
                'order_id'        => $doc['order_id'],
                'action'          => 'cancelamento',
                'status'          => in_array($cStat, ['135', '155']) ? 'success' : 'error',
                'code_sefaz'      => $cStat,
                'message'         => $xMotivo,
                'xml_response'    => $response,
            ]);

            if (in_array($cStat, ['135', '155'])) {
                $this->docModel->markCancelled($nfeId, $nProt, $motivo, $response);
                return ['success' => true, 'message' => "NF-e cancelada com sucesso. Protocolo: {$nProt}"];
            }

            return ['success' => false, 'message' => "SEFAZ: {$xMotivo} (cStat: {$cStat})"];

        } catch (\Exception $e) {
            $this->logModel->create([
                'nfe_document_id' => $nfeId,
                'order_id'        => $doc['order_id'],
                'action'          => 'cancelamento',
                'status'          => 'error',
                'message'         => $e->getMessage(),
            ]);
            return ['success' => false, 'message' => 'Erro no cancelamento: ' . $e->getMessage()];
        }
    }
}
