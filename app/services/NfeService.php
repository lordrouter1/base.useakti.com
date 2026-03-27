<?php
namespace Akti\Services;

use Akti\Models\NfeCredential;
use Akti\Models\NfeDocument;
use Akti\Models\NfeLog;
use Akti\Core\EventDispatcher;
use Akti\Core\Event;
use Akti\Services\NfeXmlValidator;
use Akti\Services\NfeStorageService;
use PDO;

/**
 * NfeService — Comunicação com SEFAZ via biblioteca sped-nfe.
 *
 * Responsabilidades:
 *   - Configurar ambiente (certificado, UF, ambiente)
 *   - Emitir NF-e (assinar XML, enviar, consultar recibo)
 *   - Cancelar NF-e
 *   - Carta de Correção
 *   - Consultar status do serviço SEFAZ
 *   - Consultar NF-e pela chave
 *
 * Dependência: nfephp-org/sped-nfe (instalada via Composer)
 *
 * @package Akti\Services
 */
class NfeService
{
    private $db;
    private NfeCredential $credModel;
    private NfeDocument $docModel;
    private NfeLog $logModel;
    private array $credentials;

    /** @var \NFePHP\NFe\Tools|null */
    private $tools = null;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->credModel = new NfeCredential($db);
        $this->docModel  = new NfeDocument($db);
        $this->logModel  = new NfeLog($db);
        $this->credentials = $this->credModel->get() ?: [];
    }

    // ══════════════════════════════════════════════════════════════
    // Inicialização do sped-nfe
    // ══════════════════════════════════════════════════════════════

    /**
     * Verifica se a biblioteca sped-nfe está disponível.
     * @return bool
     */
    public function isLibraryAvailable(): bool
    {
        return class_exists(\NFePHP\NFe\Tools::class);
    }

    /**
     * Inicializa o Tools do sped-nfe com as credenciais do tenant.
     * @return bool true se inicializado com sucesso
     */
    private function initTools(): bool
    {
        if ($this->tools !== null) {
            return true;
        }

        if (!$this->isLibraryAvailable()) {
            return false;
        }

        $cred = $this->credentials;
        if (empty($cred['cnpj']) || empty($cred['certificate_path'])) {
            return false;
        }

        $certPath = $cred['certificate_path'];
        if (!file_exists($certPath)) {
            return false;
        }

        $certPassword = NfeCredential::decryptPassword($cred['certificate_password'] ?? '');
        $pfxContent = file_get_contents($certPath);

        $ambiente = ($cred['environment'] ?? 'homologacao') === 'producao' ? 1 : 2;

        $config = [
            'atualizacao'  => date('Y-m-d H:i:s'),
            'tpAmb'        => $ambiente,
            'razaosocial'  => $cred['razao_social'] ?? '',
            'siglaUF'      => $cred['uf'] ?? 'RS',
            'cnpj'         => preg_replace('/\D/', '', $cred['cnpj'] ?? ''),
            'schemes'      => 'PL_009_V4',
            'versao'       => '4.00',
            'tokenIBPT'    => '',
            'CSC'          => $cred['csc_token'] ?? '',
            'CSCid'        => $cred['csc_id'] ?? '',
        ];

        try {
            $configJson = json_encode($config);
            $certificate = \NFePHP\Common\Certificate::readPfx($pfxContent, $certPassword);
            $this->tools = new \NFePHP\NFe\Tools($configJson, $certificate);
            $this->tools->model('55'); // NF-e modelo 55
            return true;
        } catch (\Exception $e) {
            $this->logModel->create([
                'action'  => 'init',
                'status'  => 'error',
                'message' => 'Erro ao inicializar sped-nfe: ' . $e->getMessage(),
            ]);
            return false;
        }
    }

    // ══════════════════════════════════════════════════════════════
    // Testar Conexão com SEFAZ
    // ══════════════════════════════════════════════════════════════

    /**
     * Testa conexão com a SEFAZ (statusServico).
     * @return array ['success' => bool, 'message' => string, 'details' => array]
     */
    public function testConnection(): array
    {
        if (!$this->initTools()) {
            return [
                'success' => false,
                'message' => 'Não foi possível inicializar o sped-nfe. Verifique credenciais e certificado.',
                'details' => [],
            ];
        }

        try {
            $response = $this->tools->sefazStatus();
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

    // ══════════════════════════════════════════════════════════════
    // Emissão de NF-e
    // ══════════════════════════════════════════════════════════════

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
        // Validar credenciais
        $validation = $this->credModel->validateForEmission();
        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => 'Credenciais incompletas: ' . implode(', ', $validation['missing']),
                'nfe_id'  => null,
                'chave'   => null,
            ];
        }

        if (!$this->initTools()) {
            return [
                'success' => false,
                'message' => 'Erro ao inicializar comunicação com SEFAZ.',
                'nfe_id'  => null,
                'chave'   => null,
            ];
        }

        // Obter próximo número com lock
        $this->db->beginTransaction();
        try {
            $numero = $this->credModel->getNextNumberForUpdate();
            $serie  = (int) ($this->credentials['serie_nfe'] ?? 1);

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
                'dest_uf'        => $orderData['customer_uf'] ?? ($this->credentials['uf'] ?? 'RS'),
            ]);

            // Incrementar numeração
            $this->credModel->incrementNextNumber();
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
            $xmlBuilder = new NfeXmlBuilder($this->credentials, $orderData, $numero, $serie);
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
            $xmlSigned = $this->tools->signNFe($xml);

            // Enviar
            $idLote = str_pad($nfeId, 15, '0', STR_PAD_LEFT);
            $resp = $this->tools->sefazEnviaLote([$xmlSigned], $idLote);
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
            // Intervalos: [3, 5, 10, 15, 30] segundos (5 tentativas máximas)
            $retryIntervals = [3, 5, 10, 15, 30];
            $maxRetries = count($retryIntervals);
            $protNFe = null;
            $stdRec = null;
            $respRecibo = null;

            for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
                sleep($retryIntervals[$attempt]);

                $respRecibo = $this->tools->sefazConsultaRecibo($recibo);
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

                // cStat 104 = Lote processado — sair do loop e processar
                if ($cStatRecibo == '104') {
                    $protNFe = $stdRec->protNFe ?? null;
                    break;
                }

                // cStat 105 = Lote em processamento — retry
                if ($cStatRecibo == '105') {
                    continue;
                }

                // Qualquer outro cStat — erro, sair do loop
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
                    // Autorizada!
                    $chave = $infProt->chNFe ?? '';
                    $protocolo = $infProt->nProt ?? '';

                    // Montar procNFe (XML autorizado completo)
                    $xmlAutorizado = \NFePHP\NFe\Complements::toAuthorize($xmlSigned, $respRecibo);

                    // Salvar XMLs em disco (legislação exige guarda por 5 anos)
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
                    // Rejeitada
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

    // ══════════════════════════════════════════════════════════════
    // Cancelamento
    // ══════════════════════════════════════════════════════════════

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
            $prazoMaximo = 24; // horas — configurável por UF futuramente

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

        if (!$this->initTools()) {
            return ['success' => false, 'message' => 'Erro ao inicializar comunicação com SEFAZ.'];
        }

        try {
            $chave = $doc['chave'];
            $protocolo = $doc['protocolo'];
            $cnpj = preg_replace('/\D/', '', $this->credentials['cnpj'] ?? '');

            $response = $this->tools->sefazCancela($chave, $motivo, $protocolo);
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

    // ══════════════════════════════════════════════════════════════
    // Carta de Correção
    // ══════════════════════════════════════════════════════════════

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

        if (!$this->initTools()) {
            return ['success' => false, 'message' => 'Erro ao inicializar comunicação com SEFAZ.'];
        }

        try {
            $chave = $doc['chave'];
            $seqEvento = ($doc['correcao_seq'] ?? 0) + 1;

            $response = $this->tools->sefazCCe($chave, $texto, $seqEvento);
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

                // Salvar no histórico de CC-e (tabela nfe_correction_history)
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
                    // Não falhar a operação se o histórico falhar — apenas logar
                    error_log('[NfeService] Erro ao salvar histórico CC-e: ' . $e->getMessage());
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

    // ══════════════════════════════════════════════════════════════
    // Consulta
    // ══════════════════════════════════════════════════════════════

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

        if (!$this->initTools()) {
            return ['success' => false, 'message' => 'Erro ao inicializar comunicação.', 'details' => []];
        }

        try {
            $response = $this->tools->sefazConsultaChave($doc['chave']);
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

    /**
     * Retorna as credenciais carregadas.
     * @return array
     */
    public function getCredentials(): array
    {
        return $this->credentials;
    }

    // ══════════════════════════════════════════════════════════════
    // Persistência de itens e totais fiscais
    // ══════════════════════════════════════════════════════════════

    /**
     * Salva os itens calculados na tabela nfe_document_items.
     *
     * @param int   $nfeId  ID do documento NF-e
     * @param array $items  Array de itens com dados fiscais calculados
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
                error_log('[NfeService] Erro ao salvar item NF-e: ' . $e->getMessage());
                // Não falhar a emissão se um item não salvar — apenas logar
            }
        }
    }

    /**
     * Salva os totais fiscais calculados no documento NF-e.
     *
     * @param int   $nfeId  ID do documento
     * @param array $totals Totais do TaxCalculator
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
                error_log('[NfeService] Erro ao salvar totais fiscais: ' . $e->getMessage());
            }
        }
    }

    // ══════════════════════════════════════════════════════════════
    // Inutilização de Numeração
    // ══════════════════════════════════════════════════════════════

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
        if (!$this->initTools()) {
            return ['success' => false, 'message' => 'Biblioteca sefaz-nfe não disponível.'];
        }

        if (empty($this->credentials['cnpj'])) {
            return ['success' => false, 'message' => 'Credenciais incompletas. Configure o CNPJ do emitente.'];
        }

        try {
            $config = [
                'tpAmb'  => $this->credentials['environment'] === 'producao' ? 1 : 2,
                'CNPJ'   => preg_replace('/\D/', '', $this->credentials['cnpj']),
                'mod'    => $modelo,
                'serie'  => $serie,
                'nNFIni' => $numInicial,
                'nNFFin' => $numFinal,
                'xJust'  => $justificativa,
                'ano'    => date('y'),
            ];

            // Integração real com SEFAZ via sped-nfe
            $sefazProtocol = null;
            if ($this->tools !== null) {
                try {
                    $response = $this->tools->sefazInutiliza(
                        $config['serie'],
                        $config['nNFIni'],
                        $config['nNFFin'],
                        $config['xJust'],
                        $config['tpAmb']
                    );

                    $st = new \NFePHP\NFe\Common\Standardize($response);
                    $std = $st->toStd();

                    // cStat 102 = Inutilização homologada
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
                    error_log('[NfeService] SEFAZ inutilizar error: ' . $sefazEx->getMessage());
                    // Se falhou a comunicação SEFAZ, registrar localmente com aviso
                    $sefazProtocol = null;
                }
            }

            // Registrar os documentos inutilizados no banco
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
            error_log('[NfeService] Inutilizar error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao inutilizar: ' . $e->getMessage()];
        }
    }
}
