<?php

namespace Akti\Services;

use Akti\Core\Log;
use Akti\Models\NfeCredential;
use Akti\Models\NfeLog;
use PDO;

/**
 * NfeSefazClient — Gerencia inicialização e acesso ao sped-nfe Tools.
 *
 * Responsabilidades:
 *   - Configurar ambiente SEFAZ (certificado, UF, ambiente)
 *   - Verificar disponibilidade da biblioteca
 *   - Expor instância de Tools para sub-services
 *
 * @package Akti\Services
 */
class NfeSefazClient
{
    private PDO $db;
    private NfeCredential $credModel;
    private NfeLog $logModel;
    private array $credentials;

    /** @var \NFePHP\NFe\Tools|null */
    private $tools = null;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->credModel = new NfeCredential($db);
        $this->logModel  = new NfeLog($db);
        $this->credentials = $this->credModel->get() ?: [];
    }

    /**
     * Verifica se a biblioteca sped-nfe está disponível.
     */
    public function isLibraryAvailable(): bool
    {
        return class_exists(\NFePHP\NFe\Tools::class);
    }

    /**
     * Inicializa o Tools do sped-nfe com as credenciais do tenant.
     */
    public function initTools(): bool
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
            $this->tools->model('55');
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

    /**
     * Retorna a instância de Tools (null se não inicializado).
     * @return \NFePHP\NFe\Tools|null
     */
    public function getTools()
    {
        return $this->tools;
    }

    /**
     * Retorna as credenciais carregadas.
     */
    public function getCredentials(): array
    {
        return $this->credentials;
    }

    /**
     * Retorna o model de credenciais.
     */
    public function getCredModel(): NfeCredential
    {
        return $this->credModel;
    }

    /**
     * Retorna o model de log.
     */
    public function getLogModel(): NfeLog
    {
        return $this->logModel;
    }
}
