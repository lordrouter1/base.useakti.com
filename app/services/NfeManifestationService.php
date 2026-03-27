<?php
namespace Akti\Services;

use Akti\Models\NfeCredential;
use Akti\Models\NfeReceivedDocument;
use Akti\Models\NfeLog;
use PDO;

/**
 * NfeManifestationService — Manifestação do Destinatário.
 *
 * Tipos de manifestação:
 *   - 210200: Confirmação da Operação
 *   - 210210: Ciência da Operação
 *   - 210220: Desconhecimento da Operação
 *   - 210240: Operação não Realizada
 *
 * @package Akti\Services
 */
class NfeManifestationService
{
    private PDO $db;
    private NfeCredential $credModel;
    private NfeReceivedDocument $receivedModel;
    private NfeLog $logModel;

    /** @var \NFePHP\NFe\Tools|null */
    private $tools = null;

    private array $credentials;

    /**
     * Mapa de tipos de manifestação.
     */
    public const TYPES = [
        'ciencia'        => ['code' => 210210, 'label' => 'Ciência da Operação',          'status' => 'ciencia'],
        'confirmada'     => ['code' => 210200, 'label' => 'Confirmação da Operação',       'status' => 'confirmada'],
        'desconhecida'   => ['code' => 210220, 'label' => 'Desconhecimento da Operação',   'status' => 'desconhecida'],
        'nao_realizada'  => ['code' => 210240, 'label' => 'Operação não Realizada',        'status' => 'nao_realizada'],
    ];

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->credModel = new NfeCredential($db);
        $this->receivedModel = new NfeReceivedDocument($db);
        $this->logModel = new NfeLog($db);
        $this->credentials = $this->credModel->get() ?: [];
    }

    /**
     * Inicializa o Tools.
     * @return bool
     */
    private function initTools(): bool
    {
        if ($this->tools !== null) return true;

        if (!class_exists(\NFePHP\NFe\Tools::class)) return false;

        $cred = $this->credentials;
        if (empty($cred['cnpj']) || empty($cred['certificate_path'])) return false;

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
            return false;
        }
    }

    /**
     * Envia manifestação do destinatário.
     *
     * @param int    $docId  ID na tabela nfe_received_documents
     * @param string $type   ciencia|confirmada|desconhecida|nao_realizada
     * @param string $justificativa Obrigatório para nao_realizada (mín 15 chars)
     * @return array ['success' => bool, 'message' => string]
     */
    public function manifest(int $docId, string $type, string $justificativa = ''): array
    {
        if (!isset(self::TYPES[$type])) {
            return ['success' => false, 'message' => 'Tipo de manifestação inválido.'];
        }

        $doc = $this->receivedModel->readOne($docId);
        if (!$doc) {
            return ['success' => false, 'message' => 'Documento não encontrado.'];
        }

        $chave = $doc['chave'] ?? '';
        if (strlen($chave) !== 44) {
            return ['success' => false, 'message' => 'Chave de acesso inválida.'];
        }

        if ($type === 'nao_realizada' && strlen($justificativa) < 15) {
            return ['success' => false, 'message' => 'Justificativa obrigatória (mín 15 caracteres) para Operação não Realizada.'];
        }

        if (!$this->initTools()) {
            return ['success' => false, 'message' => 'Erro ao inicializar comunicação com SEFAZ.'];
        }

        $typeInfo = self::TYPES[$type];

        try {
            $response = $this->tools->sefazManifesta(
                $chave,
                $typeInfo['code'],
                $justificativa ?: ''
            );

            $st = new \NFePHP\NFe\Common\Standardize($response);
            $std = $st->toStd();

            $cStat = $std->retEvento->infEvento->cStat ?? '';
            $xMotivo = $std->retEvento->infEvento->xMotivo ?? '';
            $nProt = $std->retEvento->infEvento->nProt ?? '';

            $this->logModel->create([
                'action'       => 'manifestacao',
                'status'       => in_array($cStat, ['135', '573']) ? 'success' : 'error',
                'code_sefaz'   => $cStat,
                'message'      => "Manifestação '{$typeInfo['label']}' — Chave: {$chave} — {$xMotivo}",
                'xml_response' => $response,
            ]);

            if (in_array($cStat, ['135', '573'])) {
                $this->receivedModel->updateManifestation($docId, $typeInfo['status'], $nProt);

                return [
                    'success' => true,
                    'message' => "Manifestação '{$typeInfo['label']}' registrada com sucesso. Protocolo: {$nProt}",
                ];
            }

            return ['success' => false, 'message' => "SEFAZ: {$xMotivo} (cStat: {$cStat})"];

        } catch (\Exception $e) {
            $this->logModel->create([
                'action'  => 'manifestacao',
                'status'  => 'error',
                'message' => "Manifestação '{$typeInfo['label']}' — Erro: " . $e->getMessage(),
            ]);
            return ['success' => false, 'message' => 'Erro na manifestação: ' . $e->getMessage()];
        }
    }
}
