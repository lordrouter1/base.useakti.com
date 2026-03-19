<?php
namespace Akti\Controllers;

use Akti\Models\NfeCredential;
use Akti\Services\NfeService;
use Akti\Core\ModuleBootloader;
use Akti\Utils\Input;
use Akti\Utils\Validator;
use Database;
use TenantManager;

/**
 * Controller: NfeCredentialController
 * Gerencia credenciais SEFAZ do tenant (certificado digital, dados do emitente).
 *
 * @package Akti\Controllers
 */
class NfeCredentialController
{
    private $db;
    private NfeCredential $credModel;

    public function __construct()
    {
        if (!ModuleBootloader::isModuleEnabled('nfe')) {
            http_response_code(403);
            require 'app/views/layout/header.php';
            echo "<div class='container mt-5'><div class='alert alert-warning'><i class='fas fa-toggle-off me-2'></i>Módulo NF-e desativado para este tenant.</div></div>";
            require 'app/views/layout/footer.php';
            exit;
        }

        $database = new Database();
        $this->db = $database->getConnection();
        $this->credModel = new NfeCredential($this->db);
    }

    // ══════════════════════════════════════════════════════════════
    // Formulário de credenciais
    // ══════════════════════════════════════════════════════════════

    /**
     * Exibe formulário de credenciais SEFAZ.
     */
    public function index()
    {
        $credentials = $this->credModel->get() ?: [];
        $validation = $this->credModel->validateForEmission();

        // Verificar expiração do certificado
        $certExpiry = $credentials['certificate_expiry'] ?? null;
        $certExpired = false;
        $certExpiringSoon = false;
        if ($certExpiry) {
            $expiryDate = new \DateTime($certExpiry);
            $now = new \DateTime();
            $certExpired = $expiryDate < $now;
            $diff = $now->diff($expiryDate);
            $certExpiringSoon = !$certExpired && $diff->days <= 30;
        }

        require 'app/views/layout/header.php';
        require 'app/views/nfe/credentials.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * Salva/atualiza credenciais SEFAZ.
     */
    public function store()
    {
        $data = [
            'cnpj'           => Input::post('cnpj'),
            'ie'             => Input::post('ie'),
            'razao_social'   => Input::post('razao_social'),
            'nome_fantasia'  => Input::post('nome_fantasia'),
            'crt'            => Input::post('crt', 'int', 1),
            'uf'             => Input::post('uf'),
            'cod_municipio'  => Input::post('cod_municipio'),
            'municipio'      => Input::post('municipio'),
            'logradouro'     => Input::post('logradouro'),
            'numero'         => Input::post('numero'),
            'bairro'         => Input::post('bairro'),
            'cep'            => Input::post('cep'),
            'complemento'    => Input::post('complemento'),
            'telefone'       => Input::post('telefone'),
            'serie_nfe'      => Input::post('serie_nfe', 'int', 1),
            'proximo_numero' => Input::post('proximo_numero', 'int', 1),
            'csc_id'         => Input::post('csc_id'),
            'csc_token'      => Input::post('csc_token'),
        ];

        // Ambiente — requer confirmação especial para produção
        $environment = Input::post('environment');
        if (in_array($environment, ['homologacao', 'producao'])) {
            $data['environment'] = $environment;
        }

        // Upload do certificado .pfx
        if (isset($_FILES['certificate']) && $_FILES['certificate']['error'] === UPLOAD_ERR_OK) {
            $this->handleCertificateUpload($data);
        }

        // Senha do certificado (criptografar)
        $certPassword = Input::post('certificate_password');
        if (!empty($certPassword)) {
            $data['certificate_password'] = NfeCredential::encryptPassword($certPassword);
        }

        $result = $this->credModel->update($data);

        if ($result) {
            $_SESSION['flash_success'] = 'Credenciais SEFAZ atualizadas com sucesso!';
        } else {
            $_SESSION['flash_error'] = 'Erro ao salvar credenciais.';
        }

        header('Location: ?page=nfe_credentials');
        exit;
    }

    /**
     * Processa upload do certificado .pfx.
     */
    private function handleCertificateUpload(array &$data): void
    {
        $file = $_FILES['certificate'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, ['pfx', 'p12'])) {
            $_SESSION['flash_error'] = 'O certificado deve ser um arquivo .pfx ou .p12';
            return;
        }

        // Diretório seguro fora do webroot
        $tenantDb = $_SESSION['tenant']['db_name'] ?? 'default';
        $certDir = __DIR__ . '/../../storage/certificates/' . $tenantDb;
        if (!is_dir($certDir)) {
            mkdir($certDir, 0700, true);
        }

        $certFile = $certDir . '/certificate.' . $ext;

        if (move_uploaded_file($file['tmp_name'], $certFile)) {
            $data['certificate_path'] = $certFile;

            // Tentar ler expiração do certificado
            $password = Input::post('certificate_password', 'string', '');
            if (!empty($password)) {
                $pfxContent = file_get_contents($certFile);
                $certs = [];
                if (openssl_pkcs12_read($pfxContent, $certs, $password)) {
                    $certInfo = openssl_x509_parse($certs['cert']);
                    if (isset($certInfo['validTo_time_t'])) {
                        $data['certificate_expiry'] = date('Y-m-d', $certInfo['validTo_time_t']);
                    }
                }
            }
        } else {
            $_SESSION['flash_error'] = 'Erro ao fazer upload do certificado.';
        }
    }

    /**
     * Atualiza credenciais (alias para store).
     */
    public function update()
    {
        $this->store();
    }

    // ══════════════════════════════════════════════════════════════
    // Teste de Conexão SEFAZ (AJAX)
    // ══════════════════════════════════════════════════════════════

    /**
     * Testa a conexão com a SEFAZ.
     * Retorna JSON.
     */
    public function testConnection()
    {
        header('Content-Type: application/json');

        $nfeService = new NfeService($this->db);
        $result = $nfeService->testConnection();

        echo json_encode($result);
        exit;
    }
}
