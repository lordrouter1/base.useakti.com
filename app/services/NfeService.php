<?php
namespace Akti\Services;

use Akti\Models\NfeCredential;
use Akti\Models\NfeDocument;
use Akti\Models\NfeLog;
use Akti\Core\EventDispatcher;
use Akti\Core\Event;
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

            // Consultar recibo (aguardar processamento)
            sleep(3);
            $respRecibo = $this->tools->sefazConsultaRecibo($recibo);
            $stRec = new \NFePHP\NFe\Common\Standardize($respRecibo);
            $stdRec = $stRec->toStd();

            $this->logModel->create([
                'nfe_document_id' => $nfeId,
                'order_id'        => $orderId,
                'action'          => 'consulta_recibo',
                'status'          => 'info',
                'code_sefaz'      => $stdRec->cStat ?? '',
                'message'         => $stdRec->xMotivo ?? '',
                'xml_response'    => $respRecibo,
            ]);

            // Verificar resultado do processamento
            $protNFe = $stdRec->protNFe ?? null;
            if ($protNFe && isset($protNFe->infProt)) {
                $infProt = $protNFe->infProt;
                $cStatProt = $infProt->cStat ?? '';

                if ($cStatProt == '100') {
                    // Autorizada!
                    $chave = $infProt->chNFe ?? '';
                    $protocolo = $infProt->nProt ?? '';

                    // Montar procNFe (XML autorizado completo)
                    $xmlAutorizado = \NFePHP\NFe\Complements::toAuthorize($xmlSigned, $respRecibo);

                    $this->docModel->markAuthorized($nfeId, $chave, $protocolo, $xmlAutorizado);

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
}
