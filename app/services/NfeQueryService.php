<?php

namespace Akti\Services;

use Akti\Models\NfeDocument;
use Akti\Models\NfeLog;

/**
 * NfeQueryService — Consultas SEFAZ (status do serviço, consulta por chave).
 *
 * @package Akti\Services
 */
class NfeQueryService
{
    private NfeSefazClient $sefazClient;
    private NfeDocument $docModel;
    private NfeLog $logModel;

    /**
     * Construtor da classe NfeQueryService.
     *
     * @param NfeSefazClient $sefazClient Sefaz client
     * @param NfeDocument $docModel Doc model
     * @param NfeLog $logModel Log model
     */
    public function __construct(NfeSefazClient $sefazClient, NfeDocument $docModel, NfeLog $logModel)
    {
        $this->sefazClient = $sefazClient;
        $this->docModel = $docModel;
        $this->logModel = $logModel;
    }

    /**
     * Testa conexão com a SEFAZ (statusServico).
     * @return array ['success' => bool, 'message' => string, 'details' => array]
     */
    public function testConnection(): array
    {
        if (!$this->sefazClient->initTools()) {
            return [
                'success' => false,
                'message' => 'Não foi possível inicializar o sped-nfe. Verifique credenciais e certificado.',
                'details' => [],
            ];
        }

        $tools = $this->sefazClient->getTools();

        try {
            $response = $tools->sefazStatus();
            $st = new \NFePHP\NFe\Common\Standardize($response);
            $std = $st->toStd();

            $cStat = $std->cStat ?? '';
            $xMotivo = $std->xMotivo ?? '';
            $tMed = $std->tMed ?? '';

            $this->logModel->create([
                'action'       => 'status',
                'status'       => $cStat == '107' ? 'success' : 'warning',
                'code_sefaz'   => $cStat,
                'message'      => $xMotivo,
                'xml_response' => $response,
            ]);

            return [
                'success' => ($cStat == '107'),
                'message' => "SEFAZ: {$xMotivo} (cStat: {$cStat})",
                'details' => [
                    'cStat'  => $cStat,
                    'xMotivo' => $xMotivo,
                    'tMed'   => $tMed,
                ],
            ];
        } catch (\Exception $e) {
            $this->logModel->create([
                'action'  => 'status',
                'status'  => 'error',
                'message' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'message' => 'Erro ao consultar SEFAZ: ' . $e->getMessage(),
                'details' => [],
            ];
        }
    }

    /**
     * Consulta NF-e pela chave na SEFAZ.
     * @param int $nfeId
     * @return array ['success' => bool, 'message' => string, 'details' => array]
     */
    public function checkStatus(int $nfeId): array
    {
        $doc = $this->docModel->readOne($nfeId);
        if (!$doc || empty($doc['chave'])) {
            return ['success' => false, 'message' => 'NF-e sem chave de acesso.', 'details' => []];
        }

        if (!$this->sefazClient->initTools()) {
            return ['success' => false, 'message' => 'Erro ao inicializar comunicação.', 'details' => []];
        }

        $tools = $this->sefazClient->getTools();

        try {
            $response = $tools->sefazConsultaChave($doc['chave']);
            $st = new \NFePHP\NFe\Common\Standardize($response);
            $std = $st->toStd();

            $cStat = $std->cStat ?? '';
            $xMotivo = $std->xMotivo ?? '';

            $this->logModel->create([
                'nfe_document_id' => $nfeId,
                'order_id'        => $doc['order_id'],
                'action'          => 'consulta',
                'status'          => 'info',
                'code_sefaz'      => $cStat,
                'message'         => $xMotivo,
                'xml_response'    => $response,
            ]);

            return [
                'success' => ($cStat == '100'),
                'message' => "SEFAZ: {$xMotivo} (cStat: {$cStat})",
                'details' => [
                    'cStat'   => $cStat,
                    'xMotivo' => $xMotivo,
                ],
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'details' => []];
        }
    }
}
