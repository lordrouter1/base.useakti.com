<?php

namespace Akti\Controllers;

use Akti\Models\Customer;
use Akti\Models\ImportBatch;
use Akti\Models\ImportMappingProfile;
use Akti\Models\Logger;
use Akti\Services\CustomerImportService;
use Akti\Utils\Input;

/**
 * Class CustomerImportController.
 */
class CustomerImportController extends BaseController
{
    private CustomerImportService $importService;
    private ImportBatch $importBatchModel;
    private ImportMappingProfile $mappingProfileModel;
    private Customer $customerModel;
    private Logger $logger;

    /**
     * Construtor da classe CustomerImportController.
     *
     * @param \PDO $db Conexão PDO com o banco de dados
     */
    public function __construct(\PDO $db)
    {
        parent::__construct($db);
        $this->importService = new CustomerImportService($db);
        $this->importBatchModel = new ImportBatch($db);
        $this->mappingProfileModel = new ImportMappingProfile($db);
        $this->customerModel = new Customer($db);
        $this->logger = new Logger($db);
    }

    /**
     * Interpreta dados.
     */
    public function parseImportFile()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['import_file'])) {
            $this->json(['success' => false, 'message' => 'Nenhum arquivo enviado.']);
        }

        $result = $this->importService->parseFile($_FILES['import_file']);
        $this->json($result);
    }

    /**
     * Importa dados.
     */
    public function importCustomersMapped()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido.']);
        }

        $mapping = json_decode(Input::post('mapping'), true);
        if (empty($mapping)) {
            $this->json(['success' => false, 'message' => 'Nenhum mapeamento de colunas definido.']);
        }

        $importMode = Input::post('import_mode', 'string', 'create');
        if (!in_array($importMode, ['create', 'update', 'create_or_update'])) {
            $importMode = 'create';
        }

        $userId = $_SESSION['user_id'] ?? 0;
        $tenantId = $_SESSION['tenant']['id'] ?? 0;

        $result = $this->importService->executeImport($mapping, $importMode, $userId, $tenantId);
        $this->json($result);
    }

    /**
     * Obtém dados específicos.
     */
    public function getImportProgress()
    {
        $progress = $_SESSION['import_progress'] ?? null;
        if (!$progress) {
            $this->json(['success' => false, 'message' => 'Nenhuma importação em andamento.']);
        }

        $this->json(['success' => true, 'progress' => $progress]);
    }

    /**
     * Undo import.
     */
    public function undoImport()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido.']);
        }

        $batchId = (int) Input::post('batch_id', 'int', 0);
        if ($batchId <= 0) {
            $this->json(['success' => false, 'message' => 'ID do lote inválido.']);
        }

        $batch = $this->importBatchModel->findById($batchId);
        if (!$batch) {
            $this->json(['success' => false, 'message' => 'Lote de importação não encontrado.']);
        }

        if ($batch['status'] === 'undone') {
            $this->json(['success' => false, 'message' => 'Esta importação já foi desfeita.']);
        }

        $createdItems = $this->importBatchModel->getCreatedItems($batchId);
        $deletedCount = 0;

        foreach ($createdItems as $item) {
            try {
                $result = $this->customerModel->softDelete((int) $item['entity_id']);
                if ($result) {
                    $deletedCount++;
                }
            } catch (\Exception $e) {
                // Continuar mesmo se falhar em um registro
            }
        }

        $userId = $_SESSION['user_id'] ?? 0;
        $this->importBatchModel->markUndone($batchId, (int) $userId);

        $userName = $_SESSION['user_name'] ?? 'Sistema';
        $this->logger->log('CUSTOMER_IMPORT_UNDO', "Importação (lote #{$batchId}) desfeita por {$userName}. {$deletedCount} cliente(s) removido(s).");

        $this->json([
            'success' => true,
            'message' => "{$deletedCount} cliente(s) removido(s) com sucesso.",
            'deleted' => $deletedCount,
        ]);
    }

 /**
  * Get import history.
  */
    public function getImportHistory()
    {
        $tenantId = $_SESSION['tenant']['id'] ?? 0;
        $batches = $this->importBatchModel->listByTenant($tenantId);

        $this->json([
            'success'  => true,
            'batches'  => $batches,
        ]);
    }

 /**
  * Get import details.
  */
    public function getImportDetails()
    {
        $batchId  = (int) ($_GET['batch_id'] ?? 0);
        $tenantId = $_SESSION['tenant']['id'] ?? 0;

        if ($batchId <= 0) {
            $this->json(['success' => false, 'message' => 'Lote inválido.']);
        }

        $batch = $this->importBatchModel->findById($batchId);
        if (!$batch || (int) $batch['tenant_id'] !== (int) $tenantId) {
            $this->json(['success' => false, 'message' => 'Lote não encontrado.']);
        }

        $items = $this->importBatchModel->getItemsWithEntity($batchId, $batch['entity_type'] ?? 'customers');

        $created = [];
        $updated = [];
        foreach ($items as $item) {
            $entry = [
                'id'       => $item['entity_id'],
                'name'     => $item['entity_name'] ?? '—',
                'email'    => $item['entity_email'] ?? '',
                'document' => $item['entity_document'] ?? '',
                'line'     => $item['line_number'],
            ];
            if ($item['action'] === 'created') {
                $created[] = $entry;
            } elseif ($item['action'] === 'updated') {
                $updated[] = $entry;
            }
        }

        $errors   = !empty($batch['errors_json'])   ? json_decode($batch['errors_json'], true)   : [];
        $warnings = !empty($batch['warnings_json']) ? json_decode($batch['warnings_json'], true) : [];

        $this->json([
            'success'  => true,
            'batch'    => [
                'id'             => $batch['id'],
                'file_name'      => $batch['file_name'],
                'import_mode'    => $batch['import_mode'],
                'status'         => $batch['status'],
                'total_rows'     => $batch['total_rows'],
                'imported_count' => $batch['imported_count'],
                'updated_count'  => $batch['updated_count'],
                'skipped_count'  => $batch['skipped_count'],
                'error_count'    => $batch['error_count'],
                'warning_count'  => $batch['warning_count'],
                'created_at'     => $batch['created_at'],
            ],
            'created'  => $created,
            'updated'  => $updated,
            'errors'   => $errors,
            'warnings' => $warnings,
        ]);
    }

 /**
  * Get mapping profiles.
  */
    public function getMappingProfiles()
    {
        $tenantId = $_SESSION['tenant']['id'] ?? 0;
        $profiles = $this->mappingProfileModel->listByTenant($tenantId, 'customers');

        $this->json([
            'success'  => true,
            'profiles' => $profiles,
        ]);
    }

 /**
  * Save mapping profile.
  */
    public function saveMappingProfile()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido.']);
        }

        $name = trim(Input::post('profile_name', 'string', ''));
        $mappingJson = Input::post('mapping');
        $isDefault = (int) Input::post('is_default', 'int', 0);
        $profileId = (int) Input::post('profile_id', 'int', 0);

        if (empty($name)) {
            $this->json(['success' => false, 'message' => 'Nome do perfil é obrigatório.']);
        }

        $mapping = json_decode($mappingJson, true);
        if (empty($mapping)) {
            $this->json(['success' => false, 'message' => 'Mapeamento inválido.']);
        }

        try {
            $tenantId = $_SESSION['tenant']['id'] ?? 0;
            $importMode = Input::post('import_mode', 'string', 'create');

            if ($profileId > 0) {
                $result = $this->mappingProfileModel->update($profileId, [
                    'name'        => $name,
                    'mapping_json' => $mappingJson,
                    'import_mode' => $importMode,
                    'is_default'  => $isDefault,
                    'tenant_id'   => $tenantId,
                    'entity_type' => 'customers',
                ]);
                $msg = 'Perfil atualizado com sucesso.';
            } else {
                $profileId = $this->mappingProfileModel->create([
                    'tenant_id'   => $tenantId,
                    'entity_type' => 'customers',
                    'name'        => $name,
                    'mapping_json' => $mappingJson,
                    'import_mode' => $importMode,
                    'is_default'  => $isDefault,
                    'created_by'  => $_SESSION['user_id'] ?? null,
                ]);
                $msg = 'Perfil salvo com sucesso.';
            }

            $this->json(['success' => true, 'message' => $msg, 'profile_id' => $profileId]);
        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            if (strpos($errorMsg, 'Duplicate entry') !== false) {
                $this->json(['success' => false, 'message' => 'Já existe um perfil com este nome.']);
            } else {
                $this->json(['success' => false, 'message' => 'Erro ao salvar perfil: ' . $errorMsg]);
            }
        }
    }

 /**
  * Delete mapping profile.
  */
    public function deleteMappingProfile()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método inválido.']);
        }

        $profileId = (int) Input::post('profile_id', 'int', 0);
        if ($profileId <= 0) {
            $this->json(['success' => false, 'message' => 'ID do perfil inválido.']);
        }

        $result = $this->mappingProfileModel->delete($profileId);
        if ($result) {
            $this->json(['success' => true, 'message' => 'Perfil excluído com sucesso.']);
        } else {
            $this->json(['success' => false, 'message' => 'Erro ao excluir perfil.']);
        }
    }

 /**
  * Download import template.
  */
    public function downloadImportTemplate()
    {
        $this->importService->generateTemplate();
    }
}
