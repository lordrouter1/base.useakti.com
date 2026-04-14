<?php

namespace Akti\Services;

use Akti\Core\Log;
use Akti\Core\EventDispatcher;
use Akti\Core\Event;
use Akti\Models\NfeDocument;
use Akti\Models\NfeLog;
use PDO;

/**
 * NfeEmissionService — Emissão e inutilização de NF-e.
 *
 * Responsabilidades:
 *   - Emitir NF-e (assinar XML, enviar lote, consultar recibo)
 *   - Inutilizar faixa de numeração
 *   - Persistir itens e totais fiscais
 *
 * @package Akti\Services
 */
class NfeEmissionService
{
    private PDO $db;
    private NfeSefazClient $sefazClient;
    private NfeDocument $docModel;
    private NfeLog $logModel;

    public function __construct(PDO $db, NfeSefazClient $sefazClient, NfeDocument $docModel, NfeLog $logModel)
    {
        $this->db = $db;
        $this->sefazClient = $sefazClient;
        $this->docModel = $docModel;
        $this->logModel = $logModel;
    }

    /**
     * Emite uma NF-e para o pedido.
     * Fluxo: gerar XML → assinar → enviar → consultar recibo → marcar autorizada
     *
     * @param int   $orderId    ID do pedido
     * @param array $orderData  Dados completos do pedido (com itens, cliente, etc.)
     * @return array ['success' => bool, 'message' => string, 'nfe_id' => int|null, 'chave' => string|null]
     */
    public function emit(int $orderId, array $orderData): array
    {
        $credModel = $this->sefazClient->getCredModel();
        $credentials = $this->sefazClient->getCredentials();

        // Validar credenciais
        $validation = $credModel->validateForEmission();
        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => 'Credenciais incompletas: ' . implode(', ', $validation['missing']),
                'nfe_id'  => null,
                'chave'   => null,
            ];
        }

        if (!$this->sefazClient->initTools()) {
            return [
                'success' => false,
                'message' => 'Erro ao inicializar comunicação com SEFAZ.',
                'nfe_id'  => null,
                'chave'   => null,
            ];
        }

        $tools = $this->sefazClient->getTools();

        // Obter próximo número com lock
        $this->db->beginTransaction();
        try {
            $numero = $credModel->getNextNumberForUpdate();
            $serie  = (int) ($credentials['serie_nfe'] ?? 1);

            // Criar registro no banco
            $nfeId = $this->docModel->create([
                'order_id'       => $orderId,
                'numero'         => $numero,
                'serie'          => $serie,
                'status'         => 'processando',
                'valor_total'    => $orderData['total_amount'] ?? 0,
                'valor_produtos' => $orderData['valor_produtos'] ?? ($orderData['total_amount'] ?? 0),
                'valor_desconto' => $orderData['discount'] ?? 0,
                'valor_frete'    => $orderData['shipping_cost'] ?? 0,
                'dest_cnpj_cpf'  => $orderData['customer_cpf_cnpj'] ?? '',
                'dest_nome'      => $orderData['customer_name'] ?? '',
                'dest_ie'        => $orderData['customer_ie'] ?? '',
                'dest_uf'        => $orderData['customer_uf'] ?? ($credentials['uf'] ?? 'RS'),
            ]);

            // Incrementar numeração
            $credModel->incrementNextNumber();
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => 'Erro ao reservar número NF-e: ' . $e->getMessage(),
                'nfe_id'  => null,
                'chave'   => null,
            ];
        }

        // Montar XML
        try {
            $xmlBuilder = new NfeXmlBuilder($credentials, $orderData, $numero, $serie);
            $xml = $xmlBuilder->build();

            // Salvar XML de envio
            $this->docModel->update($nfeId, ['xml_envio' => $xml]);

            // Validar XML contra XSD antes do envio
            $validation = NfeXmlValidator::validate($xml);
            if (!$validation['valid']) {
                $errorMsg = 'XML inválido: ' . implode('; ', array_slice($validation['errors'], 0, 5));
                $this->docModel->update($nfeId, [
                    'status'       => 'rascunho',
                    'motivo_sefaz' => $errorMsg,
                ]);

                $this->logModel->create([
                    'nfe_document_id' => $nfeId,
                    'order_id'        => $orderId,
                    'action'          => 'validacao_xml',
                    'status'          => 'error',
                    'message'         => $errorMsg,
                ]);

                return [
                    'success' => false,
                    'message' => $errorMsg,
                    'nfe_id'  => $nfeId,
                    'chave'   => null,
                ];
            }

            // Salvar itens calculados na tabela nfe_document_items
            $this->saveDocumentItems($nfeId, $xmlBuilder->getCalculatedItems());

            // Salvar totais fiscais no documento
            $this->saveFiscalTotals($nfeId, $xmlBuilder->getCalculatedTotals());

            // Assinar
            $xmlSigned = $tools->signNFe($xml);

            // Enviar
            $idLote = str_pad($nfeId, 15, '0', STR_PAD_LEFT);
            $resp = $tools->sefazEnviaLote([$xmlSigned], $idLote);
            $st = new \NFePHP\NFe\Common\Standardize($resp);
            $std = $st->toStd();

            $this->logModel->create([
                'nfe_document_id' => $nfeId,
                'order_id'        => $orderId,
                'action'          => 'emissao',
                'status'          => 'info',
                'code_sefaz'      => $std->cStat ?? '',
                'message'         => 'Lote enviado: ' . ($std->xMotivo ?? ''),
                'xml_request'     => $xmlSigned,
                'xml_response'    => $resp,
            ]);

            // Verificar se lote foi aceito
            if (($std->cStat ?? '') != '103') {
                $this->docModel->update($nfeId, [
                    'status'       => 'rejeitada',
                    'status_sefaz' => $std->cStat ?? '',
                    'motivo_sefaz' => $std->xMotivo ?? 'Lote rejeitado',
                ]);

                EventDispatcher::dispatch('model.nfe_document.error', new Event('model.nfe_document.error', [
                    'nfe_id'   => $nfeId,
                    'order_id' => $orderId,
                    'code'     => $std->cStat ?? '',
                    'message'  => $std->xMotivo ?? '',
                ]));

                return [
                    'success' => false,
                    'message' => 'Lote rejeitado pela SEFAZ: ' . ($std->xMotivo ?? ''),
                    'nfe_id'  => $nfeId,
                    'chave'   => null,
                ];
            }

            // Salvar recibo
            $recibo = $std->infRec->nRec ?? '';
            $this->docModel->update($nfeId, ['recibo' => $recibo]);

            // Consultar recibo com retry e backoff exponencial
            $retryIntervals = [3, 5, 10, 15, 30];
            $maxRetries = count($retryIntervals);
            $protNFe = null;
            $stdRec = null;
            $respRecibo = null;

            for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
                sleep($retryIntervals[$attempt]);

                $respRecibo = $tools->sefazConsultaRecibo($recibo);
                $stRec = new \NFePHP\NFe\Common\Standardize($respRecibo);
                $stdRec = $stRec->toStd();

                $cStatRecibo = $stdRec->cStat ?? '';

                $this->logModel->create([
                    'nfe_document_id' => $nfeId,
                    'order_id'        => $orderId,
                    'action'          => 'consulta_recibo',
                    'status'          => 'info',
                    'code_sefaz'      => $cStatRecibo,
                    'message'         => sprintf(
                        'Tentativa %d/%d — cStat: %s — %s',
                        $attempt + 1,
                        $maxRetries,
                        $cStatRecibo,
                        $stdRec->xMotivo ?? ''
                    ),
                    'xml_response'    => $respRecibo,
                ]);

                if ($cStatRecibo == '104') {
                    $protNFe = $stdRec->protNFe ?? null;
                    break;
                }

                if ($cStatRecibo == '105') {
                    continue;
                }

                $this->docModel->update($nfeId, [
                    'status'       => 'rejeitada',
                    'status_sefaz' => $cStatRecibo,
                    'motivo_sefaz' => $stdRec->xMotivo ?? 'Erro na consulta de recibo',
                ]);

                EventDispatcher::dispatch('model.nfe_document.error', new Event('model.nfe_document.error', [
                    'nfe_id'   => $nfeId,
                    'order_id' => $orderId,
                    'code'     => $cStatRecibo,
                    'message'  => $stdRec->xMotivo ?? '',
                ]));

                return [
                    'success' => false,
                    'message' => 'Erro na consulta de recibo: ' . ($stdRec->xMotivo ?? "cStat {$cStatRecibo}"),
                    'nfe_id'  => $nfeId,
                    'chave'   => null,
                ];
            }

            // Se esgotou as tentativas sem resposta definitiva
            if ($protNFe === null) {
                $this->docModel->update($nfeId, [
                    'status'       => 'processando',
                    'motivo_sefaz' => 'Timeout: SEFAZ não processou o lote após ' . $maxRetries . ' tentativas. Consulte manualmente.',
                ]);

                return [
                    'success' => false,
                    'message' => 'A SEFAZ não processou o lote a tempo. Recibo: ' . $recibo . '. Tente consultar o status manualmente.',
                    'nfe_id'  => $nfeId,
                    'chave'   => null,
                ];
            }

            // Verificar resultado do processamento
            if ($protNFe && isset($protNFe->infProt)) {
                $infProt = $protNFe->infProt;
                $cStatProt = $infProt->cStat ?? '';

                if ($cStatProt == '100') {
                    $chave = $infProt->chNFe ?? '';
                    $protocolo = $infProt->nProt ?? '';

                    $xmlAutorizado = \NFePHP\NFe\Complements::toAuthorize($xmlSigned, $respRecibo);

                    $storage = new NfeStorageService();
                    $xmlPath = $storage->saveXml($chave, $xmlAutorizado, 'nfe');

                    $this->docModel->markAuthorized($nfeId, $chave, $protocolo, $xmlAutorizado, $xmlPath);

                    $this->logModel->create([
                        'nfe_document_id' => $nfeId,
                        'order_id'        => $orderId,
                        'action'          => 'emissao',
                        'status'          => 'success',
                        'code_sefaz'      => '100',
                        'message'         => "NF-e autorizada. Chave: {$chave} | Protocolo: {$protocolo}",
                    ]);

                    return [
                        'success' => true,
                        'message' => 'NF-e autorizada com sucesso!',
                        'nfe_id'  => $nfeId,
                        'chave'   => $chave,
                    ];
                } else {
                    $this->docModel->update($nfeId, [
                        'status'       => 'rejeitada',
                        'status_sefaz' => $cStatProt,
                        'motivo_sefaz' => $infProt->xMotivo ?? 'Rejeitada',
                    ]);

                    EventDispatcher::dispatch('model.nfe_document.error', new Event('model.nfe_document.error', [
                        'nfe_id'   => $nfeId,
                        'order_id' => $orderId,
                        'code'     => $cStatProt,
                        'message'  => $infProt->xMotivo ?? '',
                    ]));

                    EventDispatcher::dispatch('model.nfe_document.rejected', new Event('model.nfe_document.rejected', [
                        'nfe_id'      => $nfeId,
                        'order_id'    => $orderId,
                        'code_sefaz'  => $cStatProt,
                        'motivo'      => $infProt->xMotivo ?? 'Rejeitada',
                    ]));

                    return [
                        'success' => false,
                        'message' => 'NF-e rejeitada: ' . ($infProt->xMotivo ?? ''),
                        'nfe_id'  => $nfeId,
                        'chave'   => null,
                    ];
                }
            }

            return [
                'success' => false,
                'message' => 'Resposta inesperada da SEFAZ. Consulte o recibo manualmente.',
                'nfe_id'  => $nfeId,
                'chave'   => null,
            ];

        } catch (\Exception $e) {
            $this->docModel->update($nfeId, [
                'status'       => 'rejeitada',
                'motivo_sefaz' => $e->getMessage(),
            ]);

            $this->logModel->create([
                'nfe_document_id' => $nfeId,
                'order_id'        => $orderId,
                'action'          => 'emissao',
                'status'          => 'error',
                'message'         => $e->getMessage(),
            ]);

            EventDispatcher::dispatch('model.nfe_document.error', new Event('model.nfe_document.error', [
                'nfe_id'   => $nfeId,
                'order_id' => $orderId,
                'message'  => $e->getMessage(),
            ]));

            return [
                'success' => false,
                'message' => 'Erro na emissão: ' . $e->getMessage(),
                'nfe_id'  => $nfeId,
                'chave'   => null,
            ];
        }
    }

    /**
     * Inutiliza faixa de numeração na SEFAZ.
     *
     * @param int    $numInicial   Número inicial da faixa
     * @param int    $numFinal     Número final da faixa
     * @param string $justificativa Justificativa (mín. 15 caracteres)
     * @param int    $modelo       Modelo do documento (55=NF-e, 65=NFC-e)
     * @param int    $serie        Série do documento
     * @return array ['success' => bool, 'message' => string]
     */
    public function inutilizar(int $numInicial, int $numFinal, string $justificativa, int $modelo = 55, int $serie = 1): array
    {
        $credentials = $this->sefazClient->getCredentials();

        if (!$this->sefazClient->initTools()) {
            return ['success' => false, 'message' => 'Biblioteca sefaz-nfe não disponível.'];
        }

        if (empty($credentials['cnpj'])) {
            return ['success' => false, 'message' => 'Credenciais incompletas. Configure o CNPJ do emitente.'];
        }

        $tools = $this->sefazClient->getTools();

        try {
            $config = [
                'tpAmb'  => $credentials['environment'] === 'producao' ? 1 : 2,
                'CNPJ'   => preg_replace('/\D/', '', $credentials['cnpj']),
                'mod'    => $modelo,
                'serie'  => $serie,
                'nNFIni' => $numInicial,
                'nNFFin' => $numFinal,
                'xJust'  => $justificativa,
                'ano'    => date('y'),
            ];

            $sefazProtocol = null;
            if ($tools !== null) {
                try {
                    $response = $tools->sefazInutiliza(
                        $config['serie'],
                        $config['nNFIni'],
                        $config['nNFFin'],
                        $config['xJust'],
                        $config['tpAmb']
                    );

                    $st = new \NFePHP\NFe\Common\Standardize($response);
                    $std = $st->toStd();

                    if (isset($std->infInut) && $std->infInut->cStat == 102) {
                        $sefazProtocol = $std->infInut->nProt ?? null;
                    } elseif (isset($std->cStat) && $std->cStat == 102) {
                        $sefazProtocol = $std->nProt ?? null;
                    } else {
                        $cStat = $std->infInut->cStat ?? $std->cStat ?? '???';
                        $xMotivo = $std->infInut->xMotivo ?? $std->xMotivo ?? 'Motivo desconhecido';
                        return [
                            'success' => false,
                            'message' => "SEFAZ rejeitou inutilização: [{$cStat}] {$xMotivo}",
                        ];
                    }
                } catch (\Throwable $sefazEx) {
                    Log::error('NfeEmissionService: SEFAZ inutilizar error', ['exception' => $sefazEx->getMessage()]);
                    $sefazProtocol = null;
                }
            }

            for ($n = $numInicial; $n <= $numFinal; $n++) {
                $existing = $this->docModel->findByNumero($n, $serie, $modelo);
                if (!$existing) {
                    $this->docModel->create([
                        'numero'       => $n,
                        'serie'        => $serie,
                        'modelo'       => $modelo,
                        'status'       => 'inutilizada',
                        'valor_total'  => 0,
                        'dest_nome'    => 'INUTILIZAÇÃO',
                        'natureza_op'  => $justificativa,
                        'protocolo'    => $sefazProtocol,
                    ]);
                }
            }

            $msgSuffix = $sefazProtocol
                ? " Protocolo SEFAZ: {$sefazProtocol}."
                : ' (registrada localmente — comunicação SEFAZ indisponível).';

            return [
                'success'   => true,
                'message'   => "Numeração {$numInicial} a {$numFinal} inutilizada com sucesso (série {$serie}, modelo {$modelo}).{$msgSuffix}",
                'protocolo' => $sefazProtocol,
            ];

        } catch (\Throwable $e) {
            Log::error('NfeEmissionService: Inutilizar error', ['exception' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erro ao inutilizar: ' . $e->getMessage()];
        }
    }

    /**
     * Salva os itens calculados na tabela nfe_document_items.
     */
    private function saveDocumentItems(int $nfeId, array $items): void
    {
        if (empty($items)) {
            return;
        }

        $q = "INSERT INTO nfe_document_items
              (nfe_document_id, n_item, c_prod, x_prod, ncm, cest, cfop, u_com, q_com, v_un_com,
               v_prod, v_desc, origem, icms_cst, icms_csosn, icms_vbc, icms_aliquota, icms_valor,
               icms_reducao_bc, pis_cst, pis_vbc, pis_aliquota, pis_valor,
               cofins_cst, cofins_vbc, cofins_aliquota, cofins_valor,
               ipi_cst, ipi_vbc, ipi_aliquota, ipi_valor, v_tot_trib)
              VALUES
              (:doc_id, :n_item, :c_prod, :x_prod, :ncm, :cest, :cfop, :u_com, :q_com, :v_un_com,
               :v_prod, :v_desc, :origem, :icms_cst, :icms_csosn, :icms_vbc, :icms_aliquota, :icms_valor,
               :icms_reducao_bc, :pis_cst, :pis_vbc, :pis_aliquota, :pis_valor,
               :cofins_cst, :cofins_vbc, :cofins_aliquota, :cofins_valor,
               :ipi_cst, :ipi_vbc, :ipi_aliquota, :ipi_valor, :v_tot_trib)";

        $stmt = $this->db->prepare($q);

        foreach ($items as $item) {
            try {
                $stmt->execute([
                    ':doc_id'          => $nfeId,
                    ':n_item'          => $item['nItem'] ?? 0,
                    ':c_prod'          => $item['cProd'] ?? '',
                    ':x_prod'          => $item['xProd'] ?? 'Produto',
                    ':ncm'             => $item['ncm'] ?? '',
                    ':cest'            => $item['cest'] ?? null,
                    ':cfop'            => $item['cfop'] ?? '',
                    ':u_com'           => $item['uCom'] ?? 'UN',
                    ':q_com'           => $item['qCom'] ?? 1,
                    ':v_un_com'        => $item['vUnCom'] ?? 0,
                    ':v_prod'          => $item['vProd'] ?? 0,
                    ':v_desc'          => $item['vDesc'] ?? 0,
                    ':origem'          => $item['origem'] ?? 0,
                    ':icms_cst'        => $item['icms_cst'] ?? null,
                    ':icms_csosn'      => $item['icms_csosn'] ?? null,
                    ':icms_vbc'        => $item['icms_vbc'] ?? 0,
                    ':icms_aliquota'   => $item['icms_aliquota'] ?? 0,
                    ':icms_valor'      => $item['icms_valor'] ?? 0,
                    ':icms_reducao_bc' => $item['icms_reducao_bc'] ?? 0,
                    ':pis_cst'         => $item['pis_cst'] ?? null,
                    ':pis_vbc'         => $item['pis_vbc'] ?? 0,
                    ':pis_aliquota'    => $item['pis_aliquota'] ?? 0,
                    ':pis_valor'       => $item['pis_valor'] ?? 0,
                    ':cofins_cst'      => $item['cofins_cst'] ?? null,
                    ':cofins_vbc'      => $item['cofins_vbc'] ?? 0,
                    ':cofins_aliquota' => $item['cofins_aliquota'] ?? 0,
                    ':cofins_valor'    => $item['cofins_valor'] ?? 0,
                    ':ipi_cst'         => $item['ipi_cst'] ?? null,
                    ':ipi_vbc'         => $item['ipi_vbc'] ?? 0,
                    ':ipi_aliquota'    => $item['ipi_aliquota'] ?? 0,
                    ':ipi_valor'       => $item['ipi_valor'] ?? 0,
                    ':v_tot_trib'      => $item['vTotTrib'] ?? 0,
                ]);
            } catch (\Exception $e) {
                Log::error('NfeEmissionService: Erro ao salvar item NF-e', ['exception' => $e->getMessage()]);
            }
        }
    }

    /**
     * Salva os totais fiscais calculados no documento NF-e.
     */
    private function saveFiscalTotals(int $nfeId, array $totals): void
    {
        $updateData = [];

        if (isset($totals['vICMS']))    $updateData['valor_icms']            = $totals['vICMS'];
        if (isset($totals['vPIS']))     $updateData['valor_pis']             = $totals['vPIS'];
        if (isset($totals['vCOFINS']))  $updateData['valor_cofins']          = $totals['vCOFINS'];
        if (isset($totals['vIPI']))     $updateData['valor_ipi']             = $totals['vIPI'];
        if (isset($totals['vTotTrib'])) $updateData['valor_tributos_aprox']  = $totals['vTotTrib'];

        if (!empty($updateData)) {
            try {
                $this->docModel->update($nfeId, $updateData);
            } catch (\Exception $e) {
                Log::error('NfeEmissionService: Erro ao salvar totais fiscais', ['exception' => $e->getMessage()]);
            }
        }
    }
}
