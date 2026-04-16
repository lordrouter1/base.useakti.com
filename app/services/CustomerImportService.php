<?php
namespace Akti\Services;

use Akti\Models\Customer;
use Akti\Models\ImportBatch;
use Akti\Models\Logger;
use Akti\Utils\Input;
use Akti\Utils\Validator;
use PDO;

/**
 * CustomerImportService — Lógica de importação de clientes extraída do CustomerController.
 *
 * Responsável por: parse de CSV/Excel, mapeamento de colunas, normalização de dados,
 * validação, persistência e controle de progresso de importações.
 *
 * @see ROADMAP Fase 2 — Item 3.6 (Refatorar Controllers Monolíticos)
 */
class CustomerImportService
{
    /** @var Customer */
    private $customerModel;

    /** @var ImportBatch */
    private $importBatchModel;

    /** @var Logger */
    private $logger;

    /** @var PDO */
    private $db;

    /**
     * Construtor da classe CustomerImportService.
     *
     * @param PDO $db Conexão PDO com o banco de dados
     * @param Customer $customerModel Customer model
     * @param ImportBatch $importBatchModel Import batch model
     * @param Logger $logger Logger
     */
    public function __construct(PDO $db, Customer $customerModel, ImportBatch $importBatchModel, Logger $logger)
    {
        $this->db = $db;
        $this->customerModel = $customerModel;
        $this->importBatchModel = $importBatchModel;
        $this->logger = $logger;
    }

    /**
     * Faz parse do arquivo enviado e retorna colunas + preview + auto-mapeamento.
     *
     * @param array $file $_FILES['import_file']
     * @return array{success: bool, columns?: array, preview?: array, total_rows?: int, auto_mapping?: array, message?: string}
     */
    public function parseFile(array $file): array
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Erro no upload do arquivo.'];
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['csv', 'xls', 'xlsx', 'txt'])) {
            return ['success' => false, 'message' => 'Formato não suportado. Use CSV, XLS ou XLSX.'];
        }

        // Validar MIME type via magic bytes
        $allowedMimes = [
            'text/plain', 'text/csv', 'application/csv',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/octet-stream',
        ];
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $detectedMime = $finfo->file($file['tmp_name']);
        if (!in_array($detectedMime, $allowedMimes, true)) {
            return ['success' => false, 'message' => 'Conteúdo do arquivo inválido para o formato informado.'];
        }

        $rows = $this->readFileRows($file['tmp_name'], $ext);

        if (empty($rows)) {
            return ['success' => false, 'message' => 'Arquivo vazio ou não foi possível ler os dados.'];
        }

        // Salvar temporariamente
        $tmpDir = sys_get_temp_dir() . '/akti_imports/';
        if (!is_dir($tmpDir)) mkdir($tmpDir, 0755, true);
        $tmpName = 'cust_import_' . session_id() . '_' . time() . '.' . $ext;
        $tmpPath = $tmpDir . $tmpName;
        move_uploaded_file($file['tmp_name'], $tmpPath);
        $_SESSION['cust_import_tmp_file'] = $tmpPath;
        $_SESSION['cust_import_tmp_ext'] = $ext;

        $columns = !empty($rows) ? array_keys($rows[0]) : [];
        $autoMapping = $this->buildAutoMapping($columns);

        return [
            'success'      => true,
            'columns'      => $columns,
            'preview'      => array_slice($rows, 0, 10),
            'total_rows'   => count($rows),
            'auto_mapping' => $autoMapping,
        ];
    }

    /**
     * Executa a importação com o mapeamento fornecido.
     *
     * @param array  $mapping     Mapeamento [coluna_arquivo => campo_sistema]
     * @param string $importMode  create | update | create_or_update
     * @param int    $userId
     * @param int    $tenantId
     * @return array Resultado da importação
     */
    public function executeImport(array $mapping, string $importMode, int $userId, int $tenantId): array
    {
        $mappedFields = array_values($mapping);

        if (!in_array('name', $mappedFields)) {
            return ['success' => false, 'message' => 'O campo "Nome / Razão Social" é obrigatório no mapeamento.'];
        }

        if (in_array($importMode, ['update', 'create_or_update']) && !in_array('document', $mappedFields)) {
            return ['success' => false, 'message' => 'Para modo de atualização, o campo "CPF/CNPJ" deve ser mapeado.'];
        }

        // Verificação de limite do plano
        $maxCustomers = \TenantManager::getTenantLimit('max_customers');
        $currentCustomers = (int) $this->customerModel->countAll();
        $availableSlots = ($maxCustomers !== null) ? max(0, $maxCustomers - $currentCustomers) : PHP_INT_MAX;

        if ($importMode === 'create' && $maxCustomers !== null && $availableSlots <= 0) {
            return ['success' => false, 'message' => 'Limite de clientes do plano atingido.'];
        }

        // Recuperar arquivo temporário
        $tmpPath = $_SESSION['cust_import_tmp_file'] ?? null;
        $ext = $_SESSION['cust_import_tmp_ext'] ?? 'csv';
        if (!$tmpPath || !file_exists($tmpPath)) {
            return ['success' => false, 'message' => 'Arquivo temporário não encontrado. Faça o upload novamente.'];
        }

        $rows = $this->readFileRows($tmpPath, $ext);
        if (empty($rows)) {
            return ['success' => false, 'message' => 'Arquivo vazio ou não foi possível reler os dados.'];
        }

        $totalRows = count($rows);

        // Criar lote
        $batchId = $this->importBatchModel->create([
            'tenant_id'    => $tenantId,
            'entity_type'  => 'customers',
            'file_name'    => basename($tmpPath),
            'total_rows'   => $totalRows,
            'import_mode'  => $importMode,
            'mapping_json' => json_encode($mapping),
            'created_by'   => $userId,
        ]);

        // Progresso
        $_SESSION['import_progress'] = [
            'batch_id'  => $batchId,
            'total'     => $totalRows,
            'processed' => 0,
            'imported'  => 0,
            'updated'   => 0,
            'skipped'   => 0,
            'errors'    => 0,
            'status'    => 'processing',
        ];

        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $warnings = [];
        $progressInterval = max(1, (int) floor($totalRows / 50));

        foreach ($rows as $lineNum => $row) {
            $lineDisplay = $lineNum + 2;

            // Limite de plano
            if ($importMode !== 'update' && $maxCustomers !== null && ($imported + $currentCustomers) >= $maxCustomers) {
                $remaining = $totalRows - $lineNum;
                $errors[] = ['line' => $lineDisplay, 'message' => "Limite do plano atingido. {$remaining} registro(s) restante(s) não importado(s)."];
                $skipped += $remaining;
                break;
            }

            // Aplicar mapeamento
            $mapped = [];
            foreach ($mapping as $fileCol => $sysField) {
                if (!empty($sysField) && $sysField !== '_skip' && isset($row[$fileCol])) {
                    $mapped[$sysField] = trim($row[$fileCol]);
                }
            }

            if (empty($mapped['name'])) {
                $errors[] = ['line' => $lineDisplay, 'message' => 'Nome do cliente é obrigatório.'];
                $skipped++;
                continue;
            }

            // Normalizar dados
            $this->normalizeRow($mapped, $lineDisplay, $warnings, $importMode);
            $mapped['created_by'] = $userId;

            // Persistir
            try {
                $result = $this->persistRow($mapped, $row, $importMode, $batchId, $lineDisplay, $errors, $warnings);
                if ($result === 'imported') $imported++;
                elseif ($result === 'updated') $updated++;
                else $skipped++;
            } catch (\Exception $e) {
                $errors[] = ['line' => $lineDisplay, 'message' => 'Erro: ' . $e->getMessage()];
            }

            // Atualizar progresso
            if (($lineNum + 1) % $progressInterval === 0 || ($lineNum + 1) === $totalRows) {
                $_SESSION['import_progress'] = [
                    'batch_id'  => $batchId,
                    'total'     => $totalRows,
                    'processed' => $lineNum + 1,
                    'imported'  => $imported,
                    'updated'   => $updated,
                    'skipped'   => $skipped,
                    'errors'    => count($errors),
                    'status'    => 'processing',
                ];
                $this->importBatchModel->updateProgress($batchId, $lineNum + 1, $imported, $skipped, count($errors), count($warnings));
            }
        }

        // Finalizar
        $batchStatus = count($errors) > 0 ? 'completed_with_errors' : 'completed';
        $this->importBatchModel->finalize($batchId, $batchStatus, $imported, $updated, $skipped, json_encode($errors), json_encode($warnings));

        // Limpar temporários
        if (file_exists($tmpPath)) unlink($tmpPath);
        unset($_SESSION['cust_import_tmp_file'], $_SESSION['cust_import_tmp_ext']);

        $_SESSION['import_progress']['status'] = 'completed';
        $_SESSION['import_progress']['processed'] = $totalRows;
        $_SESSION['import_progress']['imported'] = $imported;
        $_SESSION['import_progress']['updated'] = $updated;

        $modeLabel = ['create' => 'criação', 'update' => 'atualização', 'create_or_update' => 'criação/atualização'][$importMode] ?? 'criação';
        $userName = $_SESSION['user_name'] ?? 'Sistema';
        $this->logger->log('CUSTOMER_IMPORT', "Importação ({$modeLabel}) de {$imported} criado(s), {$updated} atualizado(s), {$skipped} ignorado(s) por {$userName}");

        return [
            'success'  => true,
            'imported' => $imported,
            'updated'  => $updated,
            'skipped'  => $skipped,
            'errors'   => $errors,
            'warnings' => $warnings,
            'batch_id' => $batchId,
            'mode'     => $importMode,
        ];
    }

    // ═══════════════════════════════════════════════
    //  Métodos Privados — Normalização e Parse
    // ═══════════════════════════════════════════════

    /**
     * Lê linhas do arquivo (CSV/Excel).
     */
    public function readFileRows(string $filePath, string $ext): array
    {
        if (in_array($ext, ['csv', 'txt'])) {
            return $this->parseCsvFile($filePath);
        }
        if (in_array($ext, ['xls', 'xlsx']) && class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
            return $this->parseExcelFile($filePath);
        }
        return $this->parseCsvFile($filePath);
    }

    /**
     * Normaliza uma linha mapeada.
     */
    private function normalizeRow(array &$mapped, int $lineDisplay, array &$warnings, string $importMode): void
    {
        // Documento e tipo pessoa
        if (!empty($mapped['document'])) {
            $docDigits = preg_replace('/\D/', '', $mapped['document']);

            // Recuperar zeros à esquerda
            $docLen = strlen($docDigits);
            if ($docLen > 0 && $docLen < 11) {
                $paddedCpf = str_pad($docDigits, 11, '0', STR_PAD_LEFT);
                if (Validator::isValidCpf($paddedCpf)) {
                    $docDigits = $paddedCpf;
                    $mapped['document'] = $paddedCpf;
                } else {
                    $paddedCnpj = str_pad($docDigits, 14, '0', STR_PAD_LEFT);
                    if (Validator::isValidCnpj($paddedCnpj)) {
                        $docDigits = $paddedCnpj;
                        $mapped['document'] = $paddedCnpj;
                    }
                }
            } elseif ($docLen > 11 && $docLen < 14) {
                $padded = str_pad($docDigits, 14, '0', STR_PAD_LEFT);
                if (Validator::isValidCnpj($padded)) {
                    $docDigits = $padded;
                    $mapped['document'] = $padded;
                }
            }

            // Auto-detectar tipo pessoa
            if (empty($mapped['person_type'])) {
                $mapped['person_type'] = (strlen($docDigits) >= 14) ? 'PJ' : 'PF';
            }

            // Validação de dígitos
            $personType = strtoupper(trim($mapped['person_type'] ?? 'PF'));
            if ($personType === 'PJ' && strlen($docDigits) === 14 && !Validator::isValidCnpj($docDigits)) {
                $warnings[] = ['line' => $lineDisplay, 'message' => 'CNPJ "' . $mapped['document'] . '" possui dígitos verificadores inválidos.'];
            } elseif ($personType === 'PF' && strlen($docDigits) === 11 && !Validator::isValidCpf($docDigits)) {
                $warnings[] = ['line' => $lineDisplay, 'message' => 'CPF "' . $mapped['document'] . '" possui dígitos verificadores inválidos.'];
            }

            // Detectar duplicados
            if (strlen($docDigits) > 0) {
                $existing = $this->customerModel->findByDocument($docDigits);
                if ($existing && $importMode === 'create') {
                    $warnings[] = ['line' => $lineDisplay, 'message' => 'Documento já cadastrado — Cliente: "' . $existing['name'] . '".'];
                }
            }
        }

        // Normalizar person_type
        if (!empty($mapped['person_type'])) {
            $pt = strtoupper(trim($mapped['person_type']));
            $ptMap = [
                'PF' => 'PF', 'FISICA' => 'PF', 'FÍSICA' => 'PF', 'CPF' => 'PF', 'F' => 'PF',
                'PJ' => 'PJ', 'JURIDICA' => 'PJ', 'JURÍDICA' => 'PJ', 'CNPJ' => 'PJ', 'J' => 'PJ',
            ];
            $mapped['person_type'] = $ptMap[$pt] ?? 'PF';
        }

        // Data de nascimento
        if (!empty($mapped['birth_date'])) {
            $mapped['birth_date'] = $this->normalizeDateForImport($mapped['birth_date']);
        }

        // Gênero
        if (!empty($mapped['gender'])) {
            $g = strtoupper(trim($mapped['gender']));
            $gMap = ['M' => 'M', 'MASCULINO' => 'M', 'F' => 'F', 'FEMININO' => 'F', 'O' => 'O', 'OUTRO' => 'O'];
            $mapped['gender'] = $gMap[$g] ?? null;
        }

        // UF
        if (!empty($mapped['address_state'])) {
            $mapped['address_state'] = $this->normalizeUfForImport($mapped['address_state']);
        }

        // Email
        if (!empty($mapped['email'])) {
            $mapped['email'] = strtolower(trim($mapped['email']));
            if (!filter_var($mapped['email'], FILTER_VALIDATE_EMAIL)) {
                $warnings[] = ['line' => $lineDisplay, 'message' => 'E-mail "' . $mapped['email'] . '" possui formato inválido.'];
            }
        }

        // Status
        if (!empty($mapped['status'])) {
            $st = strtolower(trim($mapped['status']));
            $stMap = ['ativo' => 'active', 'active' => 'active', 'inativo' => 'inactive', 'inactive' => 'inactive', 'bloqueado' => 'blocked'];
            $mapped['status'] = $stMap[$st] ?? 'active';
        }

        // Telefones
        foreach (['phone', 'cellphone', 'phone_commercial'] as $f) {
            if (!empty($mapped[$f])) {
                $mapped[$f] = preg_replace('/[^\d+() -]/', '', $mapped[$f]);
            }
        }

        // CEP
        if (!empty($mapped['zipcode'])) {
            $mapped['zipcode'] = preg_replace('/\D/', '', $mapped['zipcode']);
            if (strlen($mapped['zipcode']) === 8) {
                $mapped['zipcode'] = substr($mapped['zipcode'], 0, 5) . '-' . substr($mapped['zipcode'], 5);
            }
        }

        // Valores monetários
        if (!empty($mapped['credit_limit'])) {
            $mapped['credit_limit'] = str_replace(['R$', ' ', '.'], ['', '', ''], $mapped['credit_limit']);
            $mapped['credit_limit'] = str_replace(',', '.', $mapped['credit_limit']);
        }
        if (!empty($mapped['discount_default'])) {
            $mapped['discount_default'] = str_replace(['%', ' '], '', $mapped['discount_default']);
            $mapped['discount_default'] = str_replace(',', '.', $mapped['discount_default']);
        }
    }

    /**
     * Persiste uma linha (create/update/merge).
     */
    private function persistRow(array $mapped, array $originalRow, string $mode, int $batchId, int $line, array &$errors, array &$warnings): string
    {
        $existingCustomer = null;
        if (!empty($mapped['document'])) {
            $docDigits = preg_replace('/\D/', '', $mapped['document']);
            if (strlen($docDigits) > 0) {
                $existingCustomer = $this->customerModel->findByDocument($docDigits);
            }
        }

        if ($mode === 'update') {
            if (!$existingCustomer) {
                $warnings[] = ['line' => $line, 'message' => 'Cliente não encontrado para atualização. Ignorado.'];
                return 'skipped';
            }
            $mapped['id'] = $existingCustomer['id'];
            $result = $this->customerModel->updateFromImport($mapped);
            if ($result) {
                $this->importBatchModel->addItem($batchId, $existingCustomer['id'], 'updated', json_encode($originalRow), $line);
                $this->logger->log('IMPORT_CUSTOMER_UPDATE', "Atualizado ID: {$existingCustomer['id']}");
                return 'updated';
            }
            $errors[] = ['line' => $line, 'message' => 'Erro ao atualizar "' . $mapped['name'] . '".'];
            return 'skipped';
        }

        if ($mode === 'create_or_update' && $existingCustomer) {
            $mapped['id'] = $existingCustomer['id'];
            $result = $this->customerModel->updateFromImport($mapped);
            if ($result) {
                $this->importBatchModel->addItem($batchId, $existingCustomer['id'], 'updated', json_encode($originalRow), $line);
                return 'updated';
            }
            $errors[] = ['line' => $line, 'message' => 'Erro ao atualizar "' . $mapped['name'] . '".'];
            return 'skipped';
        }

        // create ou create_or_update (novo)
        $mapped['import_batch_id'] = $batchId;
        $customerId = $this->customerModel->importFromMapped($mapped);
        if ($customerId) {
            $this->importBatchModel->addItem($batchId, $customerId, 'created', json_encode($originalRow), $line);
            return 'imported';
        }
        $errors[] = ['line' => $line, 'message' => 'Erro ao salvar "' . $mapped['name'] . '".'];
        return 'skipped';
    }

    /**
     * Constrói o mapeamento automático baseado nos nomes das colunas.
     */
    public function buildAutoMapping(array $columns): array
    {
        $colMap = [
            'nome' => 'name', 'name' => 'name', 'razao_social' => 'name', 'cliente' => 'name',
            'tipo' => 'person_type', 'tipo_pessoa' => 'person_type', 'person_type' => 'person_type',
            'fantasia' => 'fantasy_name', 'nome_fantasia' => 'fantasy_name', 'fantasy_name' => 'fantasy_name',
            'cpf' => 'document', 'cnpj' => 'document', 'cpf/cnpj' => 'document', 'cpf_cnpj' => 'document', 'documento' => 'document', 'document' => 'document',
            'rg' => 'rg_ie', 'ie' => 'rg_ie', 'rg_ie' => 'rg_ie',
            'im' => 'im',
            'email' => 'email', 'e-mail' => 'email',
            'email_secundario' => 'email_secondary',
            'telefone' => 'phone', 'phone' => 'phone', 'fone' => 'phone',
            'celular' => 'cellphone', 'whatsapp' => 'cellphone', 'cellphone' => 'cellphone',
            'telefone_comercial' => 'phone_commercial',
            'website' => 'website', 'site' => 'website',
            'instagram' => 'instagram',
            'nome_contato' => 'contact_name', 'contato' => 'contact_name',
            'cargo' => 'contact_role',
            'cep' => 'zipcode', 'zipcode' => 'zipcode',
            'logradouro' => 'address_street', 'endereco' => 'address_street', 'rua' => 'address_street',
            'numero' => 'address_number',
            'bairro' => 'address_neighborhood',
            'complemento' => 'address_complement',
            'cidade' => 'address_city', 'city' => 'address_city',
            'estado' => 'address_state', 'uf' => 'address_state',
            'nascimento' => 'birth_date', 'data_nascimento' => 'birth_date', 'birth_date' => 'birth_date',
            'genero' => 'gender', 'sexo' => 'gender',
            'origem' => 'origin', 'origin' => 'origin',
            'tags' => 'tags', 'etiquetas' => 'tags',
            'obs' => 'observations', 'observacoes' => 'observations', 'observations' => 'observations',
            'status' => 'status',
            'prazo_pagamento' => 'payment_term', 'payment_term' => 'payment_term',
            'limite_credito' => 'credit_limit', 'credit_limit' => 'credit_limit',
            'desconto' => 'discount_default', 'desconto_padrao' => 'discount_default',
            'codigo' => '_skip', 'cadastrado_em' => '_skip',
        ];

        $autoMapping = [];
        foreach ($columns as $col) {
            $normalized = mb_strtolower(trim($col));
            $normalized = str_replace([' ', '-', 'ç', 'ã', 'á', 'é', 'ó', 'ú', 'ê', 'í'], ['_', '_', 'c', 'a', 'a', 'e', 'o', 'u', 'e', 'i'], $normalized);
            if (isset($colMap[$normalized])) {
                $autoMapping[$col] = $colMap[$normalized];
            }
            if (!isset($autoMapping[$col]) && isset($colMap[mb_strtolower(trim($col))])) {
                $autoMapping[$col] = $colMap[mb_strtolower(trim($col))];
            }
        }
        return $autoMapping;
    }

    // ═══════════════════════════════════════════════
    //  Helpers de Parse CSV / Excel
    // ═══════════════════════════════════════════════

 /**
  * Parse csv file.
  *
  * @param string $filePath File path
  * @return array
  */
    public function parseCsvFile(string $filePath): array
    {
        $rows = [];
        $handle = fopen($filePath, 'r');
        if (!$handle) return $rows;

        $bom = fread($handle, 3);
        if ($bom !== chr(0xEF) . chr(0xBB) . chr(0xBF)) {
            rewind($handle);
        }

        $firstLine = fgets($handle);
        rewind($handle);
        $bom = fread($handle, 3);
        if ($bom !== chr(0xEF) . chr(0xBB) . chr(0xBF)) {
            rewind($handle);
        }

        $separator = (substr_count($firstLine, ';') >= substr_count($firstLine, ',')) ? ';' : ',';
        $header = fgetcsv($handle, 0, $separator);
        if (!$header) { fclose($handle); return $rows; }

        $header = array_map(function ($h) { return trim(mb_strtolower($h)); }, $header);

        while (($line = fgetcsv($handle, 0, $separator)) !== false) {
            $lc = count($line);
            $hc = count($header);
            if ($lc < $hc) $line = array_pad($line, $hc, '');
            if ($lc > $hc) $line = array_slice($line, 0, $hc);
            $rows[] = array_combine($header, $line);
        }

        fclose($handle);
        return $rows;
    }

 /**
  * Parse excel file.
  *
  * @param string $filePath File path
  * @return array
  */
    public function parseExcelFile(string $filePath): array
    {
        $rows = [];
        if (!class_exists('\\PhpOffice\\PhpSpreadsheet\\IOFactory')) return $rows;

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $data = $spreadsheet->getActiveSheet()->toArray();
            if (empty($data)) return $rows;

            $header = array_map(function ($h) { return trim(mb_strtolower($h ?? '')); }, array_shift($data));

            foreach ($data as $line) {
                $lc = count($line);
                $hc = count($header);
                if ($lc < $hc) $line = array_pad($line, $hc, '');
                if ($lc > $hc) $line = array_slice($line, 0, $hc);
                $rows[] = array_combine($header, $line);
            }
        } catch (\Exception $e) {
            // Falha ao ler — retorna vazio
        }
        return $rows;
    }

 /**
  * Normalize date for import.
  *
  * @param string $dateStr Date str
  * @return string|null
  */
    public function normalizeDateForImport(string $dateStr): ?string
    {
        $dateStr = trim($dateStr);
        if ($dateStr === '' || $dateStr === '0') return null;

        $dt = \DateTime::createFromFormat('Y-m-d', $dateStr);
        if ($dt && $dt->format('Y-m-d') === $dateStr) return $dateStr;

        $formats = ['d/m/Y', 'd-m-Y', 'd.m.Y', 'm/d/Y', 'Y/m/d', 'd/m/y', 'd-m-y'];
        foreach ($formats as $fmt) {
            $dt = \DateTime::createFromFormat($fmt, $dateStr);
            if ($dt) {
                $year = (int) $dt->format('Y');
                if ($year > 1900 && $year <= (int) date('Y')) return $dt->format('Y-m-d');
            }
        }

        try {
            $ts = strtotime($dateStr);
            if ($ts !== false && $ts > strtotime('1900-01-01') && $ts <= time()) return date('Y-m-d', $ts);
        } catch (\Exception $e) {}

        return null;
    }

 /**
  * Normalize uf for import.
  *
  * @param string $state State
  * @return string
  */
    public function normalizeUfForImport(string $state): string
    {
        $state = trim($state);
        if ($state === '') return '';

        $upper = strtoupper($state);
        $validUfs = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];
        if (in_array($upper, $validUfs)) return $upper;

        $map = [
            'acre' => 'AC', 'alagoas' => 'AL', 'amapa' => 'AP', 'amazonas' => 'AM',
            'bahia' => 'BA', 'ceara' => 'CE', 'distrito federal' => 'DF',
            'espirito santo' => 'ES', 'goias' => 'GO', 'maranhao' => 'MA',
            'mato grosso' => 'MT', 'mato grosso do sul' => 'MS', 'minas gerais' => 'MG',
            'para' => 'PA', 'paraiba' => 'PB', 'parana' => 'PR', 'pernambuco' => 'PE',
            'piaui' => 'PI', 'rio de janeiro' => 'RJ', 'rio grande do norte' => 'RN',
            'rio grande do sul' => 'RS', 'rondonia' => 'RO', 'roraima' => 'RR',
            'santa catarina' => 'SC', 'sao paulo' => 'SP', 'sergipe' => 'SE', 'tocantins' => 'TO',
        ];
        return $map[mb_strtolower($state, 'UTF-8')] ?? $upper;
    }

    /**
     * Gera o CSV de template de importação.
     */
    public function generateTemplate(): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="modelo_importacao_clientes.csv"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($output, [
            'nome', 'tipo_pessoa', 'nome_fantasia', 'cpf_cnpj', 'rg_ie', 'im',
            'data_nascimento', 'genero', 'email', 'email_secundario',
            'telefone', 'celular', 'telefone_comercial', 'website', 'instagram',
            'nome_contato', 'cargo_contato',
            'cep', 'logradouro', 'numero', 'bairro', 'complemento',
            'cidade', 'uf', 'origem', 'tags', 'observacoes',
            'status', 'prazo_pagamento', 'limite_credito', 'desconto_padrao'
        ], ';');

        fputcsv($output, [
            'Maria Silva', 'PF', '', '529.982.247-25', '12.345.678-9', '',
            '15/03/1990', 'F', 'maria@email.com', '',
            '(11) 3333-4444', '(11) 99999-0000', '', '', '',
            '', '',
            '01001-000', 'Praça da Sé', '100', 'Sé', 'Sala 5',
            'São Paulo', 'SP', 'Site', 'VIP', 'Cliente desde 2020',
            'active', '30 dias', '5000.00', '5'
        ], ';');

        fclose($output);
        exit;
    }
}
