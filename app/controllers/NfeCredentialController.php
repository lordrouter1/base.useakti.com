<?php
namespace Akti\Controllers;

use Akti\Models\NfeCredential;
use Akti\Services\NfeService;
use Akti\Services\NfeAuditService;
use Akti\Core\ModuleBootloader;
use Akti\Core\Log;
use Akti\Utils\Input;
use Akti\Utils\Validator;
use Akti\Models\User;
use TenantManager;

/**
 * Controller: NfeCredentialController
 * Gerencia credenciais SEFAZ do tenant (certificado digital, dados do emitente).
 *
 * @package Akti\Controllers
 */
class NfeCredentialController
{
    private \PDO $db;
    private NfeCredential $credModel;

    public function __construct(\PDO $db, NfeCredential $credModel)
    {
        if (!ModuleBootloader::isModuleEnabled('nfe')) {
            http_response_code(403);
            require 'app/views/layout/header.php';
            echo "<div class='container mt-5'><div class='alert alert-warning'><i class='fas fa-toggle-off me-2'></i>Módulo NF-e desativado para este tenant.</div></div>";
            require 'app/views/layout/footer.php';
            exit;
        }

        $this->db = $db;
        $this->credModel = $credModel;

        // Verificar permissão de visualização (nfe_credentials)
        $this->checkPermission('nfe_credentials');
    }

    /**
     * Verifica se o usuário tem permissão para acessar credenciais NF-e.
     * @param string $page Nome da página/módulo
     */
    private function checkPermission(string $page): void
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ?page=login');
            exit;
        }
        $userModel = new User($this->db);
        if (!$userModel->checkPermission($_SESSION['user_id'], $page)) {
            http_response_code(403);
            require 'app/views/layout/header.php';
            echo "<div class='container mt-5'><div class='alert alert-danger'><i class='fas fa-ban me-2'></i>Acesso Negado. Você não tem permissão para acessar as Credenciais SEFAZ.</div></div>";
            require 'app/views/layout/footer.php';
            exit;
        }
    }

    // ══════════════════════════════════════════════════════════════
    // Formulário de credenciais
    // ══════════════════════════════════════════════════════════════

    /**
     * Exibe formulário de credenciais SEFAZ.
     */
    public function index()
    {
        // Auditoria: registrar visualização de credenciais (FASE4-04)
        $this->getAuditService()->logCredentialsView();

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

        // AJAX: retornar apenas o fragmento (sem header/footer)
        $isAjax = !empty($_GET['_ajax']) || (
            isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
        );

        if ($isAjax) {
            require 'app/views/nfe/credentials.php';
            return;
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
        // Verificar permissão de escrita nas credenciais
        if (!isset($_SESSION['user_id'])) {
            header('Location: ?page=login');
            exit;
        }
        $userModel = new User($this->db);
        if (!$userModel->checkPermission($_SESSION['user_id'], 'nfe_credentials')) {
            $_SESSION['flash_error'] = 'Sem permissão para alterar credenciais SEFAZ.';
            header('Location: ?page=nfe_documents&sec=credenciais');
            exit;
        }

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
            // Auditoria: registrar atualização de credenciais (FASE4-04)
            $updatedFields = array_keys(array_filter($data, fn($v) => $v !== null && $v !== ''));
            $this->getAuditService()->logCredentialsUpdate($updatedFields);

            $_SESSION['flash_success'] = 'Credenciais SEFAZ atualizadas com sucesso!';
        } else {
            $_SESSION['flash_error'] = 'Erro ao salvar credenciais.';
        }

        header('Location: ?page=nfe_documents&sec=credenciais');
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

        // Validação MIME por magic bytes (SEC-006)
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $allowedMimes = ['application/x-pkcs12', 'application/octet-stream'];
        if (!in_array($mime, $allowedMimes)) {
            $_SESSION['flash_error'] = 'O arquivo enviado não é um certificado válido.';
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

            // Auditoria: registrar upload de certificado digital (FASE4-04)
            $this->getAuditService()->record(
                'credential_cert_upload',
                'nfe_credential',
                1,
                'Upload de certificado digital (' . $ext . ')'
            );

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

    // ══════════════════════════════════════════════════════════════
    // Importação IBPTax (CSV)
    // ══════════════════════════════════════════════════════════════

    /**
     * Importa tabela IBPTax a partir de arquivo CSV enviado pelo usuário.
     * Aceita POST com arquivo CSV no campo 'ibptax_csv'.
     * Retorna JSON com resultado.
     */
    public function importIbptax()
    {
        header('Content-Type: application/json');

        // Verificar permissão de escrita
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Sessão expirada.']);
            exit;
        }
        $userModel = new User($this->db);
        if (!$userModel->checkPermission($_SESSION['user_id'], 'nfe_credentials')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sem permissão para esta ação.']);
            exit;
        }

        // Validar arquivo
        if (!isset($_FILES['ibptax_csv']) || $_FILES['ibptax_csv']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Nenhum arquivo CSV enviado ou erro no upload.']);
            exit;
        }

        $file = $_FILES['ibptax_csv'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, ['csv', 'txt'])) {
            echo json_encode(['success' => false, 'message' => 'Formato inválido. Envie um arquivo .csv ou .txt da tabela IBPTax.']);
            exit;
        }

        // Limite de tamanho: 20MB
        if ($file['size'] > 20 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'Arquivo muito grande (máx. 20MB).']);
            exit;
        }

        try {
            $ibptaxModel = new \Akti\Models\IbptaxModel($this->db);

            // Opção de truncar antes de importar
            $truncateBefore = Input::post('truncate_before', 'int', 0);
            if ($truncateBefore) {
                $removed = $ibptaxModel->truncate();
            }

            $result = $ibptaxModel->importFromCsv($file['tmp_name']);

            $msg = sprintf(
                'Importação concluída: %d registros importados, %d erros de %d linhas processadas.',
                $result['imported'],
                $result['errors'],
                $result['total']
            );
            if (!empty($removed)) {
                $msg .= sprintf(' (%d registros anteriores removidos.)', $removed);
            }

            echo json_encode([
                'success'  => true,
                'message'  => $msg,
                'imported' => $result['imported'],
                'errors'   => $result['errors'],
                'total'    => $result['total'],
            ]);
        } catch (\Throwable $e) {
            Log::error('NfeCredentialController: importCertificates', ['exception' => $e->getMessage()]);
            echo json_encode([
                'success' => false,
                'message' => 'Erro interno na importação. Tente novamente.',
            ]);
        }
        exit;
    }

    /**
     * Retorna estatísticas da tabela IBPTax (AJAX/JSON).
     */
    public function ibptaxStats()
    {
        header('Content-Type: application/json');

        try {
            $ibptaxModel = new \Akti\Models\IbptaxModel($this->db);
            $stats = $ibptaxModel->getStats();
            echo json_encode(['success' => true, 'stats' => $stats]);
        } catch (\Throwable $e) {
            Log::error('NfeCredentialController: ibptaxStats', ['exception' => $e->getMessage()]);
            echo json_encode(['success' => false, 'message' => 'Erro interno ao consultar estatísticas. Tente novamente.']);
        }
        exit;
    }

    // ══════════════════════════════════════════════════════════════
    // Helpers internos
    // ══════════════════════════════════════════════════════════════

    /**
     * Retorna instância do serviço de auditoria (lazy).
     * @return NfeAuditService
     */
    private function getAuditService(): NfeAuditService
    {
        static $service = null;
        if ($service === null) {
            $service = new NfeAuditService($this->db);
        }
        return $service;
    }
}
