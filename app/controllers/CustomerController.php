<?php
namespace Akti\Controllers;

use Akti\Models\Customer;
use Akti\Models\PriceTable;
use Akti\Models\Logger;
use Akti\Utils\Input;
use Akti\Utils\Validator;
use Database;
use PDO;
use TenantManager;

class CustomerController {
    
    private $customerModel;
    private $logger;
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->customerModel = new Customer($this->db);
        $this->logger = new Logger($this->db);
    }

    public function index() {
        $totalItems = (int) $this->customerModel->countAll();

        // Verificar limite de clientes do tenant
        $maxCustomers = TenantManager::getTenantLimit('max_customers');
        $currentCustomers = $totalItems;
        $limitReached = ($maxCustomers !== null && $currentCustomers >= $maxCustomers);
        $limitInfo = $limitReached ? ['current' => $currentCustomers, 'max' => $maxCustomers] : null;

        // Campos disponíveis para mapeamento de importação
        $importFields = [
            'name'           => ['label' => 'Nome / Razão Social', 'required' => true],
            'email'          => ['label' => 'E-mail', 'required' => false],
            'phone'          => ['label' => 'Telefone', 'required' => false],
            'document'       => ['label' => 'CPF / CNPJ', 'required' => false],
            'zipcode'        => ['label' => 'CEP', 'required' => false],
            'address_type'   => ['label' => 'Tipo Logradouro', 'required' => false],
            'address_name'   => ['label' => 'Nome do Logradouro', 'required' => false],
            'address_number' => ['label' => 'Número', 'required' => false],
            'neighborhood'   => ['label' => 'Bairro', 'required' => false],
            'complement'     => ['label' => 'Complemento', 'required' => false],
        ];

        require 'app/views/layout/header.php';
        require 'app/views/customers/index.php';
        require 'app/views/layout/footer.php';
    }

    public function create() {
        $priceTableModel = new PriceTable($this->db);
        $priceTables = $priceTableModel->readAll();
        
        require 'app/views/layout/header.php';
        require 'app/views/customers/create.php';
        require 'app/views/layout/footer.php';
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $photoPath = $this->handlePhotoUpload();

            $address = json_encode([
                'zipcode' => Input::post('zipcode'),
                'address_type' => Input::post('address_type'),
                'address_name' => Input::post('address_name'),
                'address_number' => Input::post('address_number'),
                'neighborhood' => Input::post('neighborhood'),
                'complement' => Input::post('complement')
            ]);
            
            $name = Input::post('name');
            $email = Input::post('email', 'email');
            $phone = Input::post('phone', 'phone');
            $document = Input::post('document', 'document');
            $priceTableId = Input::post('price_table_id', 'int');

            $v = new Validator();
            $v->required('name', $name, 'Nome')
              ->maxLength('name', $name, 191, 'Nome');

            if ($v->fails()) {
                $_SESSION['errors'] = $v->errors();
                $_SESSION['old'] = $_POST;
                header('Location: ?page=customers&action=create');
                exit;
            }

            $this->customerModel->create([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'document' => $document,
                'address' => $address,
                'photo' => $photoPath,
                'price_table_id' => $priceTableId
            ]);
            
            header('Location: ?page=customers&status=success');
            exit;
        }
    }

    public function edit() {
        $id = Input::get('id', 'int');
        if (!$id) {
            header('Location: ?page=customers');
            exit;
        }
        
        $customer = $this->customerModel->readOne($id);
        if (!$customer) {
            header('Location: ?page=customers');
            exit;
        }

        // Decode address JSON for the form
        $customer['address_data'] = json_decode($customer['address'] ?? '{}', true) ?: [];
        
        $priceTableModel = new PriceTable($this->db);
        $priceTables = $priceTableModel->readAll();

        require 'app/views/layout/header.php';
        require 'app/views/customers/edit.php';
        require 'app/views/layout/footer.php';
    }

    public function update() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $photoPath = $this->handlePhotoUpload();

            $address = json_encode([
                'zipcode' => Input::post('zipcode'),
                'address_type' => Input::post('address_type'),
                'address_name' => Input::post('address_name'),
                'address_number' => Input::post('address_number'),
                'neighborhood' => Input::post('neighborhood'),
                'complement' => Input::post('complement')
            ]);
            
            $id = Input::post('id', 'int');
            $name = Input::post('name');
            $email = Input::post('email', 'email');
            $phone = Input::post('phone', 'phone');
            $document = Input::post('document', 'document');
            $priceTableId = Input::post('price_table_id', 'int');

            $v = new Validator();
            $v->required('id', $id, 'ID')
              ->required('name', $name, 'Nome')
              ->maxLength('name', $name, 191, 'Nome');

            if ($v->fails()) {
                $_SESSION['errors'] = $v->errors();
                header('Location: ?page=customers&action=edit&id=' . $id);
                exit;
            }

            $this->customerModel->update([
                'id' => $id,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'document' => $document,
                'address' => $address,
                'photo' => $photoPath,
                'price_table_id' => $priceTableId
            ]);
            
            header('Location: ?page=customers&status=success');
            exit;
        }
    }

    public function delete() {
        $id = Input::get('id', 'int');
        if ($id) {
            $this->customerModel->delete($id);
            header('Location: ?page=customers&status=success');
            exit;
        }
    }

    private function handlePhotoUpload() {
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $maxSize = 5 * 1024 * 1024;
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
            $fileType = mime_content_type($_FILES['photo']['tmp_name']);
            
            if ($_FILES['photo']['size'] > $maxSize || !in_array($fileType, $allowedTypes)) {
                return null;
            }

            $uploadDir = TenantManager::getTenantUploadBase() . 'customers/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileExtension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $fileName = uniqid() . '.' . $fileExtension;
            $targetFile = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetFile)) {
                return $targetFile;
            }
        }
        return null;
    }

    // ═══════════════════════════════════════════════
    //  AJAX: Lista de clientes com filtro e paginação
    // ═══════════════════════════════════════════════

    public function getCustomersList() {
        header('Content-Type: application/json');

        $search  = Input::get('search');
        $page    = max(1, Input::get('pg', 'int', 1));
        $perPage = Input::get('per_page', 'int', 20);

        $result     = $this->customerModel->readPaginatedFiltered($page, $perPage, $search ?: null);
        $total      = $result['total'];
        $totalPages = max(1, (int) ceil($total / $perPage));
        $items      = $result['data'];

        echo json_encode([
            'success'     => true,
            'items'       => $items,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => $totalPages,
        ]);
        exit;
    }

    // ═══════════════════════════════════════════════
    //  IMPORTAÇÃO: Parse do arquivo (Step 1 → Step 2)
    // ═══════════════════════════════════════════════

    public function parseImportFile() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['import_file'])) {
            echo json_encode(['success' => false, 'message' => 'Nenhum arquivo enviado.']);
            exit;
        }

        $file = $_FILES['import_file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Erro no upload do arquivo.']);
            exit;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['csv', 'xls', 'xlsx', 'txt'])) {
            echo json_encode(['success' => false, 'message' => 'Formato não suportado. Use CSV, XLS ou XLSX.']);
            exit;
        }

        $rows = [];
        if (in_array($ext, ['csv', 'txt'])) {
            $rows = $this->parseCsvFile($file['tmp_name']);
        } elseif (in_array($ext, ['xls', 'xlsx'])) {
            if (class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
                $rows = $this->parseExcelFile($file['tmp_name']);
            } else {
                $rows = $this->parseCsvFile($file['tmp_name']);
            }
        }

        if (empty($rows)) {
            echo json_encode(['success' => false, 'message' => 'Arquivo vazio ou não foi possível ler os dados.']);
            exit;
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
        $preview = array_slice($rows, 0, 10);
        $totalRows = count($rows);

        // Auto-mapeamento por nome de coluna
        $colMap = [
            'nome' => 'name', 'name' => 'name', 'razao_social' => 'name', 'razao social' => 'name', 'cliente' => 'name',
            'email' => 'email', 'e-mail' => 'email', 'e_mail' => 'email',
            'telefone' => 'phone', 'phone' => 'phone', 'fone' => 'phone', 'celular' => 'phone', 'whatsapp' => 'phone', 'tel' => 'phone',
            'cpf' => 'document', 'cnpj' => 'document', 'cpf/cnpj' => 'document', 'cpf_cnpj' => 'document', 'documento' => 'document', 'document' => 'document',
            'cep' => 'zipcode', 'zip' => 'zipcode', 'zipcode' => 'zipcode', 'zip_code' => 'zipcode',
            'tipo_logradouro' => 'address_type', 'tipo logradouro' => 'address_type',
            'logradouro' => 'address_name', 'endereco' => 'address_name', 'endereço' => 'address_name', 'rua' => 'address_name', 'address' => 'address_name',
            'numero' => 'address_number', 'número' => 'address_number', 'num' => 'address_number', 'nro' => 'address_number',
            'bairro' => 'neighborhood', 'neighborhood' => 'neighborhood',
            'complemento' => 'complement', 'complement' => 'complement', 'comp' => 'complement',
        ];

        $autoMapping = [];
        foreach ($columns as $col) {
            $normalized = mb_strtolower(trim($col));
            $normalized = str_replace([' ', '-', 'ç', 'ã', 'á', 'é', 'ó', 'ú', 'ê', 'í'], ['_', '_', 'c', 'a', 'a', 'e', 'o', 'u', 'e', 'i'], $normalized);
            if (isset($colMap[$normalized])) {
                $autoMapping[$col] = $colMap[$normalized];
            }
            // Fallback: nome original direto
            if (!isset($autoMapping[$col]) && isset($colMap[mb_strtolower(trim($col))])) {
                $autoMapping[$col] = $colMap[mb_strtolower(trim($col))];
            }
        }

        echo json_encode([
            'success'      => true,
            'columns'      => $columns,
            'preview'      => $preview,
            'total_rows'   => $totalRows,
            'auto_mapping' => $autoMapping,
        ]);
        exit;
    }

    // ═══════════════════════════════════════════════
    //  IMPORTAÇÃO: Executar import com mapeamento
    // ═══════════════════════════════════════════════

    public function importCustomersMapped() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método inválido.']);
            exit;
        }

        $mapping = json_decode(Input::post('mapping'), true);
        if (empty($mapping)) {
            echo json_encode(['success' => false, 'message' => 'Nenhum mapeamento de colunas definido.']);
            exit;
        }

        $mappedFields = array_values($mapping);
        if (!in_array('name', $mappedFields)) {
            echo json_encode(['success' => false, 'message' => 'O campo "Nome / Razão Social" é obrigatório no mapeamento.']);
            exit;
        }

        // Recuperar arquivo temporário
        $tmpPath = $_SESSION['cust_import_tmp_file'] ?? null;
        $ext = $_SESSION['cust_import_tmp_ext'] ?? 'csv';
        if (!$tmpPath || !file_exists($tmpPath)) {
            echo json_encode(['success' => false, 'message' => 'Arquivo temporário não encontrado. Faça o upload novamente.']);
            exit;
        }

        // Ler arquivo
        $rows = [];
        if (in_array($ext, ['csv', 'txt'])) {
            $rows = $this->parseCsvFile($tmpPath);
        } else {
            if (class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
                $rows = $this->parseExcelFile($tmpPath);
            } else {
                $rows = $this->parseCsvFile($tmpPath);
            }
        }

        if (empty($rows)) {
            echo json_encode(['success' => false, 'message' => 'Arquivo vazio ou não foi possível reler os dados.']);
            exit;
        }

        $imported = 0;
        $errors = [];

        foreach ($rows as $lineNum => $row) {
            $lineDisplay = $lineNum + 2;

            // Aplicar mapeamento
            $mapped = [];
            foreach ($mapping as $fileCol => $sysField) {
                if (!empty($sysField) && $sysField !== '_skip' && isset($row[$fileCol])) {
                    $mapped[$sysField] = trim($row[$fileCol]);
                }
            }

            // Validar obrigatórios
            if (empty($mapped['name'])) {
                $errors[] = ['line' => $lineDisplay, 'message' => 'Nome do cliente é obrigatório.'];
                continue;
            }

            try {
                $customerId = $this->customerModel->importFromMapped($mapped);
                if ($customerId) {
                    $imported++;
                    $this->logger->log('IMPORT_CUSTOMER', "Cliente importado ID: {$customerId} Nome: {$mapped['name']}");
                } else {
                    $errors[] = ['line' => $lineDisplay, 'message' => 'Erro ao salvar "' . $mapped['name'] . '" no banco de dados.'];
                }
            } catch (\Exception $e) {
                $errors[] = ['line' => $lineDisplay, 'message' => 'Erro: ' . $e->getMessage()];
            }
        }

        // Limpar arquivo temporário
        if (file_exists($tmpPath)) {
            unlink($tmpPath);
        }
        unset($_SESSION['cust_import_tmp_file'], $_SESSION['cust_import_tmp_ext']);

        echo json_encode([
            'success'  => true,
            'imported' => $imported,
            'errors'   => $errors,
        ]);
        exit;
    }

    // ═══════════════════════════════════════════════
    //  Download modelo de importação CSV
    // ═══════════════════════════════════════════════

    public function downloadImportTemplate() {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="modelo_importacao_clientes.csv"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8

        fputcsv($output, ['nome', 'email', 'telefone', 'cpf_cnpj', 'cep', 'logradouro', 'numero', 'bairro', 'complemento'], ';');
        fputcsv($output, ['Maria Silva', 'maria@email.com', '(11) 99999-0000', '123.456.789-00', '01001-000', 'Rua Exemplo', '100', 'Centro', 'Sala 5'], ';');
        fputcsv($output, ['Empresa ABC Ltda', 'contato@abc.com.br', '(21) 3333-4444', '12.345.678/0001-99', '20040-020', 'Av. Brasil', '500', 'Comercial', ''], ';');

        fclose($output);
        exit;
    }

    // ═══════════════════════════════════════════════
    //  Helpers de parse CSV / Excel
    // ═══════════════════════════════════════════════

    private function parseCsvFile($filePath) {
        $rows = [];
        $handle = fopen($filePath, 'r');
        if (!$handle) return $rows;

        // Detect BOM
        $bom = fread($handle, 3);
        if ($bom !== chr(0xEF) . chr(0xBB) . chr(0xBF)) {
            rewind($handle);
        }

        // Detect separator
        $firstLine = fgets($handle);
        rewind($handle);
        $bom = fread($handle, 3);
        if ($bom !== chr(0xEF) . chr(0xBB) . chr(0xBF)) {
            rewind($handle);
        }

        $separator = (substr_count($firstLine, ';') >= substr_count($firstLine, ',')) ? ';' : ',';

        // Header
        $headers = fgetcsv($handle, 0, $separator);
        if (!$headers) { fclose($handle); return $rows; }
        $headers = array_map(function($h) {
            return strtolower(trim(str_replace(['"', "'"], '', $h)));
        }, $headers);

        // Data rows
        while (($line = fgetcsv($handle, 0, $separator)) !== false) {
            if (count(array_filter($line, function($v) { return trim($v) !== ''; })) === 0) continue;
            $row = [];
            foreach ($headers as $i => $header) {
                $row[$header] = isset($line[$i]) ? $line[$i] : '';
            }
            $rows[] = $row;
        }

        fclose($handle);
        return $rows;
    }

    private function parseExcelFile($filePath) {
        if (!class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) return [];

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = [];
            $headers = [];

            foreach ($worksheet->getRowIterator() as $rowIndex => $row) {
                $cells = [];
                foreach ($row->getCellIterator() as $cell) {
                    $cells[] = $cell->getValue();
                }

                if ($rowIndex === 1) {
                    $headers = array_map(function($h) {
                        return strtolower(trim((string)$h));
                    }, $cells);
                    continue;
                }

                if (count(array_filter($cells, function($v) { return trim((string)$v) !== ''; })) === 0) continue;

                $row = [];
                foreach ($headers as $i => $header) {
                    $row[$header] = isset($cells[$i]) ? (string)$cells[$i] : '';
                }
                $rows[] = $row;
            }

            return $rows;
        } catch (\Exception $e) {
            return [];
        }
    }
}
