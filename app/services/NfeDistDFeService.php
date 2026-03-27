<?php
namespace Akti\Services;

use Akti\Models\NfeCredential;
use Akti\Models\NfeReceivedDocument;
use Akti\Models\NfeLog;
use PDO;

/**
 * NfeDistDFeService — Consulta DistDFe (Distribuição de Documentos Fiscais Eletrônicos).
 *
 * Funcionalidades:
 *   - Consulta por NSU (incremental)
 *   - Consulta por chave de acesso
 *   - Parse de resumos (resNFe) e XMLs completos (procNFe)
 *   - Armazenamento na tabela nfe_received_documents
 *
 * Dependência: nfephp-org/sped-nfe (biblioteca sped-nfe)
 *
 * @package Akti\Services
 */
class NfeDistDFeService
{
    private PDO $db;
    private NfeCredential $credModel;
    private NfeReceivedDocument $receivedModel;
    private NfeLog $logModel;

    /** @var \NFePHP\NFe\Tools|null */
    private $tools = null;

    private array $credentials;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->credModel = new NfeCredential($db);
        $this->receivedModel = new NfeReceivedDocument($db);
        $this->logModel = new NfeLog($db);
        $this->credentials = $this->credModel->get() ?: [];
    }

    /**
     * Verifica se o serviço está disponível.
     * @return bool
     */
    public function isAvailable(): bool
    {
        return class_exists(\NFePHP\NFe\Tools::class)
            && !empty($this->credentials['cnpj'])
            && !empty($this->credentials['certificate_path']);
    }

    /**
     * Inicializa o Tools.
     * @return bool
     */
    private function initTools(): bool
    {
        if ($this->tools !== null) return true;

        if (!$this->isAvailable()) return false;

        $cred = $this->credentials;
        $certPath = $cred['certificate_path'];
        if (!file_exists($certPath)) return false;

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
            'CSC'          => '',
            'CSCid'        => '',
        ];

        try {
            $configJson = json_encode($config);
            $certificate = \NFePHP\Common\Certificate::readPfx($pfxContent, $certPassword);
            $this->tools = new \NFePHP\NFe\Tools($configJson, $certificate);
            $this->tools->model('55');
            return true;
        } catch (\Exception $e) {
            $this->logModel->create([
                'action'  => 'distdfe_init',
                'status'  => 'error',
                'message' => 'Erro ao inicializar para DistDFe: ' . $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Consulta DistDFe por NSU (incremental).
     * Busca todos os documentos a partir do último NSU processado.
     *
     * @param string|null $ultimoNsu  Se null, usa o último NSU salvo
     * @param int         $maxLoops   Máximo de loops de consulta
     * @return array ['success' => bool, 'total' => int, 'ultimo_nsu' => string, 'message' => string]
     */
    public function queryByNSU(?string $ultimoNsu = null, int $maxLoops = 10): array
    {
        if (!$this->initTools()) {
            return ['success' => false, 'total' => 0, 'ultimo_nsu' => '0', 'message' => 'Serviço indisponível.'];
        }

        if ($ultimoNsu === null) {
            $ultimoNsu = $this->credentials['ultimo_nsu'] ?? '0';
        }

        $totalDocs = 0;
        $lastNsu = $ultimoNsu;

        for ($loop = 0; $loop < $maxLoops; $loop++) {
            try {
                $response = $this->tools->sefazDistDFe($lastNsu);
                $st = new \NFePHP\NFe\Common\Standardize($response);
                $std = $st->toStd();

                $cStat = $std->cStat ?? '';

                $this->logModel->create([
                    'action'       => 'distdfe',
                    'status'       => in_array($cStat, ['137', '138']) ? 'success' : 'info',
                    'code_sefaz'   => $cStat,
                    'message'      => $std->xMotivo ?? '',
                    'xml_response' => $response,
                ]);

                // 137 = Nenhum documento localizado
                if ($cStat == '137') {
                    break;
                }

                // 138 = Documentos localizados
                if ($cStat == '138') {
                    $maxNSU = $std->maxNSU ?? '0';
                    $ultNSU = $std->ultNSU ?? $lastNsu;
                    $lastNsu = $ultNSU;

                    // Processar documentos retornados
                    $loteDistDFeInt = $std->loteDistDFeInt ?? null;
                    if ($loteDistDFeInt && isset($loteDistDFeInt->docZip)) {
                        $docs = is_array($loteDistDFeInt->docZip) ? $loteDistDFeInt->docZip : [$loteDistDFeInt->docZip];

                        foreach ($docs as $docZip) {
                            $this->processDistDFeDoc($docZip);
                            $totalDocs++;
                        }
                    }

                    // Se ultNSU >= maxNSU, não há mais documentos
                    if ($ultNSU >= $maxNSU) {
                        break;
                    }

                    continue; // buscar mais
                }

                // Qualquer outro cStat
                break;

            } catch (\Exception $e) {
                $this->logModel->create([
                    'action'  => 'distdfe',
                    'status'  => 'error',
                    'message' => $e->getMessage(),
                ]);
                return [
                    'success'    => false,
                    'total'      => $totalDocs,
                    'ultimo_nsu' => $lastNsu,
                    'message'    => 'Erro na consulta DistDFe: ' . $e->getMessage(),
                ];
            }
        }

        // Salvar último NSU consultado
        $this->credModel->update(['ultimo_nsu' => $lastNsu]);

        return [
            'success'    => true,
            'total'      => $totalDocs,
            'ultimo_nsu' => $lastNsu,
            'message'    => $totalDocs > 0
                ? "{$totalDocs} documento(s) recebido(s)."
                : 'Nenhum novo documento encontrado.',
        ];
    }

    /**
     * Consulta DistDFe por chave de acesso.
     *
     * @param string $chave 44 dígitos
     * @return array
     */
    public function queryByChave(string $chave): array
    {
        if (!$this->initTools()) {
            return ['success' => false, 'message' => 'Serviço indisponível.'];
        }

        try {
            $response = $this->tools->sefazDistDFe(0, $chave);
            $st = new \NFePHP\NFe\Common\Standardize($response);
            $std = $st->toStd();

            $cStat = $std->cStat ?? '';

            $this->logModel->create([
                'action'       => 'distdfe_chave',
                'status'       => $cStat == '138' ? 'success' : 'info',
                'code_sefaz'   => $cStat,
                'message'      => "Consulta por chave: {$chave} — " . ($std->xMotivo ?? ''),
            ]);

            if ($cStat == '138') {
                $loteDistDFeInt = $std->loteDistDFeInt ?? null;
                if ($loteDistDFeInt && isset($loteDistDFeInt->docZip)) {
                    $docs = is_array($loteDistDFeInt->docZip) ? $loteDistDFeInt->docZip : [$loteDistDFeInt->docZip];
                    foreach ($docs as $docZip) {
                        $this->processDistDFeDoc($docZip);
                    }
                }
                return ['success' => true, 'message' => 'Documento localizado e importado.'];
            }

            return ['success' => false, 'message' => ($std->xMotivo ?? "cStat: {$cStat}")];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Erro: ' . $e->getMessage()];
        }
    }

    /**
     * Processa um documento do DistDFe (docZip).
     * @param object $docZip
     */
    private function processDistDFeDoc(object $docZip): void
    {
        $nsu = $docZip->NSU ?? '';
        $schema = $docZip->schema ?? '';
        $xmlCompressed = $docZip->_ ?? '';

        // Descompactar XML (base64 + gzip)
        $xmlContent = '';
        if (!empty($xmlCompressed)) {
            $decoded = base64_decode($xmlCompressed);
            $xmlContent = @gzinflate($decoded) ?: $decoded;
        }

        // Determinar tipo de schema
        $schemaType = 'unknown';
        if (strpos($schema, 'resNFe') !== false) {
            $schemaType = 'resNFe';
        } elseif (strpos($schema, 'resEvento') !== false) {
            $schemaType = 'resEvento';
        } elseif (strpos($schema, 'procNFe') !== false) {
            $schemaType = 'procNFe';
        }

        // Parse dos dados do documento
        $data = [
            'nsu'           => str_pad($nsu, 15, '0', STR_PAD_LEFT),
            'schema_type'   => $schemaType,
            'credential_id' => $this->credentials['id'] ?? null,
        ];

        if (!empty($xmlContent)) {
            try {
                $xml = @simplexml_load_string($xmlContent);
                if ($xml) {
                    if ($schemaType === 'resNFe') {
                        $data['chave']          = (string) ($xml->chNFe ?? '');
                        $data['cnpj_emitente']  = (string) ($xml->CNPJ ?? '');
                        $data['nome_emitente']  = (string) ($xml->xNome ?? '');
                        $data['ie_emitente']    = (string) ($xml->IE ?? '');
                        $data['data_emissao']   = (string) ($xml->dhEmi ?? '');
                        $data['tipo_nfe']       = (int) ($xml->tpNF ?? 0);
                        $data['valor_total']    = (float) ($xml->vNF ?? 0);
                        $data['situacao']       = (string) ($xml->cSitNFe ?? '');
                        $data['summary_xml']    = $xmlContent;
                    } elseif ($schemaType === 'procNFe') {
                        $nfe = $xml->NFe->infNFe ?? null;
                        if ($nfe) {
                            $data['chave']          = str_replace('NFe', '', (string) ($nfe['Id'] ?? ''));
                            $data['cnpj_emitente']  = (string) ($nfe->emit->CNPJ ?? '');
                            $data['nome_emitente']  = (string) ($nfe->emit->xNome ?? '');
                            $data['ie_emitente']    = (string) ($nfe->emit->IE ?? '');
                            $data['data_emissao']   = (string) ($nfe->ide->dhEmi ?? '');
                            $data['tipo_nfe']       = (int) ($nfe->ide->tpNF ?? 0);
                            $data['valor_total']    = (float) ($nfe->total->ICMSTot->vNF ?? 0);
                            $data['situacao']       = 'autorizada';
                        }
                        $data['xml_content'] = $xmlContent;
                    }
                }
            } catch (\Throwable $e) {
                // Parse failed, save raw
                $data['summary_xml'] = $xmlContent;
            }
        }

        $this->receivedModel->upsert($data);
    }

    /**
     * Retorna o model de documentos recebidos.
     * @return NfeReceivedDocument
     */
    public function getReceivedModel(): NfeReceivedDocument
    {
        return $this->receivedModel;
    }
}
