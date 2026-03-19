<?php
namespace Akti\Controllers;

use Akti\Models\Product;
use Akti\Models\Category;
use Akti\Models\Subcategory;
use Akti\Models\ProductionSector;
use Akti\Models\ProductGrade;
use Akti\Models\Logger;
use Akti\Models\PriceTable;
use Akti\Utils\Input;
use Akti\Utils\Sanitizer;
use Akti\Utils\Validator;
use Database;
use PDO;
use TenantManager;

class ProductController {
    
    private $productModel;
    private $categoryModel;
    private $subcategoryModel;
    private $sectorModel;
    private $gradeModel;
    private $logger;

    public function __construct() {
        $database = new Database();
        $db = $database->getConnection();
        $this->productModel = new Product($db);
        $this->categoryModel = new Category($db);
        $this->subcategoryModel = new Subcategory($db);
        $this->sectorModel = new ProductionSector($db);
        $this->gradeModel = new ProductGrade($db);
        $this->logger = new Logger($db);
    }

    public function index() {
        $perPage     = 15;
        $ctPage = max(1, (Input::get('pg', 'int')?? 1));
        $totalItems  = (int) $this->productModel->countAll();
        $totalPages  = max(1, (int) ceil($totalItems / $perPage));
        $ctPage = min($ctPage, $totalPages);

        $products = $this->productModel->readPaginated($ctPage, $perPage);

        // Verificar limite de produtos do tenant
        $maxProducts = TenantManager::getTenantLimit('max_products');
        $currentProducts = $totalItems;
        $limitReached = ($maxProducts !== null && $currentProducts >= $maxProducts);
        $limitInfo = $limitReached ? ['current' => $currentProducts, 'max' => $maxProducts] : null;

        // Variáveis para o componente de paginação
        $baseUrl = '?page=products';

        require 'app/views/layout/header.php';
        require 'app/views/products/index.php';
        require 'app/views/layout/footer.php';
    }

    public function create() {
        // Fetch categories for the dropdown
        $stmt = $this->categoryModel->readAll();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch price tables
        $database = new Database();
        $db = $database->getConnection();
        $priceTableModel = new PriceTable($db);
        $priceTables = $priceTableModel->readAll();
        $productPrices = []; // Nenhum preço salvo ainda (produto novo)

        // Fetch production sectors
        $allSectors = $this->sectorModel->readAll(true);
        $productSectors = []; // Nenhum setor vinculado ainda (produto novo)

        // Fetch grade types and empty grades for new product
        $gradeTypes = $this->gradeModel->getAllGradeTypes();
        $productGrades = []; // Nenhuma grade vinculada ainda
        $productCombinations = []; // Nenhuma combinação ainda

        require 'app/views/layout/header.php';
        require 'app/views/products/create.php';
        require 'app/views/layout/footer.php';
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $maxProducts = TenantManager::getTenantLimit('max_products');
            if ($maxProducts !== null) {
                $currentProducts = $this->productModel->countAll();
                if ($currentProducts >= $maxProducts) {
                    header('Location: ?page=products&status=limit_products');
                    exit;
                }
            }

            // Handle dynamically added category
            $category_id = Input::post('category_id');
            if ($category_id === 'new' && Input::hasPost('new_category_name')) {
                 $this->categoryModel->name = Input::post('new_category_name');
                 if ($this->categoryModel->create()) {
                     $category_id = $this->categoryModel->id;
                 }
            }

            // Handle dynamically added subcategory
            $subcategory_id = Input::post('subcategory_id');
             if ($subcategory_id === 'new' && Input::hasPost('new_subcategory_name') && $category_id) {
                 $this->subcategoryModel->name = Input::post('new_subcategory_name');
                 $this->subcategoryModel->category_id = $category_id;
                 if ($this->subcategoryModel->create()) {
                     $subcategory_id = $this->subcategoryModel->id;
                 }
            }

            $data = [
                'name' => Input::post('name'),
                'sku' => Input::post('sku'),
                'description' => Input::post('description'),
                'category_id' => $category_id ? $category_id : null,
                'subcategory_id' => $subcategory_id ? $subcategory_id : null,
                'price' => Input::post('price', 'float', 0),
                'use_stock_control' => Input::post('use_stock_control', 'bool') ? 1 : 0
            ];

            // Coletar campos fiscais
            foreach (Product::$fiscalFields as $f) {
                if (Input::hasPost($f)) {
                    $data[$f] = Input::post($f);
                }
            }

            // Criar Produto primeiro para ter o ID
            $productId = $this->productModel->create($data);

            if($productId) {
                $this->logger->log('CREATE_PRODUCT', 'Created product ID: ' . $productId . ' Name: ' . $data['name']);

                // Salvar setores de produção vinculados
                $sectorIds = Input::postArray('sector_ids');
                if (!empty($sectorIds)) {
                    $this->sectorModel->saveProductSectors($productId, Sanitizer::intArray($sectorIds));
                }

                // Salvar grades (variações) do produto
                $grades = Input::postArray('grades');
                if (!empty($grades)) {
                    $this->gradeModel->saveProductGrades($productId, $grades);
                }

                // Salvar dados das combinações (preço/estoque por combinação)
                $combinations = Input::postArray('combinations');
                if (!empty($combinations)) {
                    $this->gradeModel->saveCombinationsData($productId, $combinations);
                }

                // Salvar preços das tabelas de preço
                $tablePrices = Input::postArray('table_prices');
                if (!empty($tablePrices)) {
                                $dbPT = (new Database())->getConnection();
                    $ptModel = new PriceTable($dbPT);
                    $ptModel->saveProductPrices($productId, $tablePrices);
                }

                // Upload das fotos
                $this->handlePhotoUpload($productId, $_FILES['product_photos'] ?? null, Input::post('main_image_index', 'int', 0));
                
                header('Location: ?page=products&status=success');
                exit;
            } else {
                echo "Erro ao cadastrar produto.";
            }
        }
    }
    
    // AJAX for subcategories
    public function getSubcategories() {
        $categoryId = Input::get('category_id', 'int');
        if ($categoryId) {
            $stmt = $this->categoryModel->getSubcategories($categoryId);
            echo json_encode($stmt);
            exit;
        }
    }
    
    // AJAX for create category on the fly
    public function createCategoryAjax() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && Input::hasPost('name')) {
            $name = Input::post('name');
            $this->categoryModel->name = $name;
            if ($this->categoryModel->create()) {
                echo json_encode(['success' => true, 'id' => $this->categoryModel->id, 'name' => $name]);
            } else {
                echo json_encode(['success' => false]);
            }
            exit;
        }
    }

    public function edit() {
        $id = Input::get('id', 'int');
        if (!$id) {
             header('Location: ?page=products');
             exit;
        }

        $product = $this->productModel->readOne($id);

        if (!$product) {
            header('Location: ?page=products');
            exit;
        }

        $stmt = $this->categoryModel->readAll();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $images = $this->productModel->getImages($id);
        
        // Get Subcategories for current category
        $subcategories = [];
        if ($product['category_id']) {
            $subcategories = $this->categoryModel->getSubcategories($product['category_id']);
        }

        // Fetch price tables and existing prices for this product
        $database = new Database();
        $db = $database->getConnection();
        $priceTableModel = new PriceTable($db);
        $priceTables = $priceTableModel->readAll();
        $productPrices = $priceTableModel->getPricesForProduct($id);

        // Fetch production sectors
        $allSectors = $this->sectorModel->readAll(true);
        $productSectors = $this->sectorModel->getProductSectors($id);

        // Fetch grade types and product grades
        $gradeTypes = $this->gradeModel->getAllGradeTypes();
        $productGrades = $this->gradeModel->getProductGradesWithValues($id);
        $productCombinations = $this->gradeModel->getProductCombinations($id);

        require 'app/views/layout/header.php';
        require 'app/views/products/edit.php';
        require 'app/views/layout/footer.php';
    }

    public function update() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Handle content update similar to store
             $category_id = Input::post('category_id');
             if ($category_id === 'new' && Input::hasPost('new_category_name')) {
                 $this->categoryModel->name = Input::post('new_category_name');
                 if ($this->categoryModel->create()) {
                     $category_id = $this->categoryModel->id;
                 }
            }

            // Handle dynamically added subcategory
            $subcategory_id = Input::post('subcategory_id');
             if ($subcategory_id === 'new' && Input::hasPost('new_subcategory_name') && $category_id) {
                 $this->subcategoryModel->name = Input::post('new_subcategory_name');
                 $this->subcategoryModel->category_id = $category_id;
                 if ($this->subcategoryModel->create()) {
                     $subcategory_id = $this->subcategoryModel->id;
                 }
            }
            
            $data = [
                 'id' => Input::post('id', 'int'),
                 'name' => Input::post('name'),
                 'sku' => Input::post('sku'),
                 'description' => Input::post('description'),
                 'category_id' => $category_id ?: null,
                 'subcategory_id' => $subcategory_id ?: null,
                 'price' => Input::post('price', 'float', 0),
                 'use_stock_control' => Input::post('use_stock_control', 'bool') ? 1 : 0
            ];

            // Coletar campos fiscais
            foreach (Product::$fiscalFields as $f) {
                if (Input::hasPost($f)) {
                    $data[$f] = Input::post($f);
                }
            }

            if ($this->productModel->update($data)) {
                $this->logger->log('UPDATE_PRODUCT', 'Updated product ID: ' . $data['id']);

                // Atualizar imagem principal entre as existentes
                if (Input::hasPost('main_image_id')) {
                    $this->productModel->setMainImage($data['id'], Input::post('main_image_id', 'int'));
                }

                // Upload de novas fotos
                $this->handlePhotoUpload($data['id'], $_FILES['product_photos'] ?? null, Input::post('main_image_index', 'int'));

                // Salvar setores de produção vinculados (limpa se vazio)
                $sectorIds = Sanitizer::intArray(Input::postArray('sector_ids'));
                $this->sectorModel->saveProductSectors($data['id'], $sectorIds);

                // Salvar grades (variações) do produto
                $grades = Input::postArray('grades');
                if (!empty($grades)) {
                    $this->gradeModel->saveProductGrades($data['id'], $grades);
                } else {
                    $this->gradeModel->saveProductGrades($data['id'], []);
                }

                // Salvar dados das combinações (preço/estoque por combinação)
                $combinations = Input::postArray('combinations');
                if (!empty($combinations)) {
                    $this->gradeModel->saveCombinationsData($data['id'], $combinations);
                }

                // Salvar preços das tabelas de preço
                $tablePrices = Input::postArray('table_prices');
                if (!empty($tablePrices)) {
                                $dbPT = (new Database())->getConnection();
                    $ptModel = new PriceTable($dbPT);
                    $ptModel->saveProductPrices($data['id'], $tablePrices);
                }

                header('Location: ?page=products&status=success');
                exit;
            }
        }
    }

    /**
     * Método privado para upload de fotos do produto.
     * Reutilizado no store() e update().
     */
    private function handlePhotoUpload(int $productId, ?array $files, $mainImageIndex = 0): void
    {
        if (!$files || empty($files['name']) || !is_array($files['name'])) {
            return;
        }

        // Verificar se há pelo menos um arquivo válido enviado
        $hasValidFile = false;
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $hasValidFile = true;
                break;
            }
        }
        if (!$hasValidFile) {
            return;
        }

        $uploadDir = TenantManager::getTenantUploadBase() . 'products/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $maxSize = 5 * 1024 * 1024; // 5 MB
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp', 'image/svg+xml'];

        $uploadedCount = 0;
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }

            // Validar tamanho
            if ($files['size'][$i] > $maxSize) {
                continue;
            }

            // Validar tipo MIME
            $fileType = mime_content_type($files['tmp_name'][$i]);
            if (!in_array($fileType, $allowedTypes)) {
                continue;
            }

            $fileExt = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
            // Normalizar extensão
            if ($fileExt === 'jpeg') {
                $fileExt = 'jpg';
            }
            $newFileName = uniqid('prod_' . $productId . '_') . '.' . $fileExt;
            $targetPath = $uploadDir . $newFileName;

            if (move_uploaded_file($files['tmp_name'][$i], $targetPath)) {
                $isMain = ($mainImageIndex !== null && $i == (int) $mainImageIndex) ? 1 : 0;

                // Se definindo como principal, resetar as outras primeiro
                if ($isMain) {
                    $this->productModel->setMainImage($productId, 0); // Reset all
                }

                $this->productModel->addImage($productId, $targetPath, $isMain);
                $uploadedCount++;
            }
        }

        // Se enviou fotos mas nenhuma foi marcada como principal, e o produto não tem nenhuma principal, definir a primeira
        if ($uploadedCount > 0) {
            $images = $this->productModel->getImages($productId);
            $hasMain = false;
            foreach ($images as $img) {
                if ($img['is_main']) {
                    $hasMain = true;
                    break;
                }
            }
            if (!$hasMain && !empty($images)) {
                $this->productModel->setMainImage($productId, $images[0]['id']);
            }
        }
    }

    public function delete() {
        $id = Input::get('id', 'int');
        if ($id) {
            // remove images first
            $images = $this->productModel->getImages($id);
            foreach($images as $img) {
                if(file_exists($img['image_path'])) {
                    unlink($img['image_path']);
                }
            }
            
            if ($this->productModel->delete($id)) {
                $this->logger->log('DELETE_PRODUCT', 'Deleted product ID: ' . $id);
                header('Location: ?page=products&status=success');
                exit;
            }
        }
    }

    public function deleteImage() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $imageId = Input::post('image_id', 'int');
            if ($imageId) {
                $image = $this->productModel->getImage($imageId);
                if ($image) {
                    if (file_exists($image['image_path'])) {
                        unlink($image['image_path']);
                    }
                    $this->productModel->deleteImage($imageId);
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false]);
                }
            } else {
                echo json_encode(['success' => false]);
            }
            exit;
        }
    }

    /**
     * Download CSV import template
     */
    public function downloadImportTemplate() {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="modelo_importacao_produtos.csv"');
        
        $output = fopen('php://output', 'w');
        
        // UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        
        // Header row
        fputcsv($output, [
            'nome', 'preco', 'preco_custo', 'estoque', 'categoria', 'subcategoria',
            'descricao', 'formato', 'material', 'ncm'
        ], ';');
        
        // Example rows
        fputcsv($output, [
            'Cartão de Visita', '49.90', '15.00', '100', 'Impressos', 'Cartões',
            'Cartão couché 300g, 4x4 cores', '9x5cm', 'Couché 300g', '49019900'
        ], ';');
        fputcsv($output, [
            'Banner Lona', '89.90', '30.00', '50', 'Grandes Formatos', '',
            'Impressão digital em lona 440g', '1x0.5m', 'Lona 440g', ''
        ], ';');
        
        fclose($output);
        exit;
    }

    /**
     * AJAX: Import products from CSV/XLS file
     */
    public function importProducts() {
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
        
        if (!in_array($ext, ['csv', 'xls', 'xlsx'])) {
            echo json_encode(['success' => false, 'message' => 'Formato não suportado. Use CSV, XLS ou XLSX.']);
            exit;
        }
        
        $rows = [];
        $headers = [];
        
        if ($ext === 'csv') {
            $rows = $this->parseCsvFile($file['tmp_name']);
        } elseif (in_array($ext, ['xls', 'xlsx'])) {
            // For XLS/XLSX, try to parse as CSV-like (tab/semicolon). 
            // If PhpSpreadsheet is available, use it. Otherwise, try csv fallback.
            if (class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
                $rows = $this->parseExcelFile($file['tmp_name']);
            } else {
                // Attempt CSV parse as fallback (sometimes .xls is actually CSV)
                $rows = $this->parseCsvFile($file['tmp_name']);
            }
        }
        
        if (empty($rows)) {
            echo json_encode(['success' => false, 'message' => 'Arquivo vazio ou não foi possível ler os dados.']);
            exit;
        }

        $maxProducts = TenantManager::getTenantLimit('max_products');
        $currentProducts = $this->productModel->countAll();
        if ($maxProducts !== null && $currentProducts >= $maxProducts) {
            echo json_encode(['success' => false, 'message' => 'Limite de produtos do cliente atingido.']);
            exit;
        }
        
        $imported = 0;
        $errors = [];
        
        // Column mapping (header names to internal names)
        $colMap = [
            'nome' => 'name', 'name' => 'name', 'produto' => 'name',
            'preco' => 'price', 'preço' => 'price', 'price' => 'price', 'valor' => 'price',
            'preco_custo' => 'cost_price', 'preço_custo' => 'cost_price', 'custo' => 'cost_price', 'cost_price' => 'cost_price',
            'estoque' => 'stock_quantity_legacy', 'stock' => 'stock_quantity_legacy', 'quantidade' => 'stock_quantity_legacy', 'stock_quantity' => 'stock_quantity_legacy',
            'categoria' => 'category', 'category' => 'category',
            'subcategoria' => 'subcategory', 'subcategory' => 'subcategory',
            'descricao' => 'description', 'descrição' => 'description', 'description' => 'description',
            'formato' => 'format', 'format' => 'format', 'dimensoes' => 'format',
            'material' => 'material', 'papel' => 'material',
            'ncm' => 'fiscal_ncm', 'fiscal_ncm' => 'fiscal_ncm',
        ];
        
        // Cache for categories/subcategories to avoid repeated queries
        $categoryCache = [];
        $subcategoryCache = [];
        
        foreach ($rows as $lineNum => $row) {
            $lineDisplay = $lineNum + 2; // +1 for header, +1 for 1-indexed
            
            // Map columns
            $mapped = [];
            foreach ($row as $key => $value) {
                $key = strtolower(trim($key));
                $key = str_replace([' ', '-', 'ç', 'ã', 'á', 'é', 'ó', 'ú'], ['_', '_', 'c', 'a', 'a', 'e', 'o', 'u'], $key);
                if (isset($colMap[$key])) {
                    $mapped[$colMap[$key]] = trim($value);
                }
            }
            
            // Validate required fields
            if (empty($mapped['name'])) {
                $errors[] = ['line' => $lineDisplay, 'message' => 'Nome do produto é obrigatório.'];
                continue;
            }
            
            $price = isset($mapped['price']) ? str_replace(',', '.', $mapped['price']) : '';
            if ($price === '' || !is_numeric($price) || floatval($price) < 0) {
                $errors[] = ['line' => $lineDisplay, 'message' => 'Preço inválido ou não informado para "' . $mapped['name'] . '".'];
                continue;
            }
            
            // Resolve category
            $categoryId = null;
            if (!empty($mapped['category'])) {
                $catName = $mapped['category'];
                if (isset($categoryCache[$catName])) {
                    $categoryId = $categoryCache[$catName];
                } else {
                    // Try to find existing category
                    $stmt = $this->categoryModel->readAll();
                    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($categories as $cat) {
                        if (mb_strtolower($cat['name']) === mb_strtolower($catName)) {
                            $categoryId = $cat['id'];
                            break;
                        }
                    }
                    // Create if not found
                    if (!$categoryId) {
                        $this->categoryModel->name = $catName;
                        if ($this->categoryModel->create()) {
                            $categoryId = $this->categoryModel->id;
                        }
                    }
                    $categoryCache[$catName] = $categoryId;
                }
            }
            
            // Resolve subcategory
            $subcategoryId = null;
            if (!empty($mapped['subcategory']) && $categoryId) {
                $subName = $mapped['subcategory'];
                $subKey = $categoryId . '_' . $subName;
                if (isset($subcategoryCache[$subKey])) {
                    $subcategoryId = $subcategoryCache[$subKey];
                } else {
                    $subs = $this->categoryModel->getSubcategories($categoryId);
                    if (is_array($subs)) {
                        foreach ($subs as $sub) {
                            if (mb_strtolower($sub['name']) === mb_strtolower($subName)) {
                                $subcategoryId = $sub['id'];
                                break;
                            }
                        }
                    }
                    // Create if not found
                    if (!$subcategoryId) {
                        $this->subcategoryModel->name = $subName;
                        $this->subcategoryModel->category_id = $categoryId;
                        if ($this->subcategoryModel->create()) {
                            $subcategoryId = $this->subcategoryModel->id;
                        }
                    }
                    $subcategoryCache[$subKey] = $subcategoryId;
                }
            }
            
            if ($maxProducts !== null && ($currentProducts + $imported) >= $maxProducts) {
                $errors[] = ['line' => $lineDisplay, 'message' => 'Limite de produtos atingido para este cliente.'];
                continue;
            }

            // Build product data
            $data = [
                'name' => $mapped['name'],
                'description' => $mapped['description'] ?? '',
                'category_id' => $categoryId,
                'subcategory_id' => $subcategoryId,
                'price' => floatval($price),
            ];
            
            // Add fiscal NCM if provided
            if (!empty($mapped['fiscal_ncm'])) {
                $data['fiscal_ncm'] = $mapped['fiscal_ncm'];
            }
            
            try {
                $productId = $this->productModel->create($data);
                if ($productId) {
                    $imported++;
                    $this->logger->log('IMPORT_PRODUCT', 'Imported product ID: ' . $productId . ' Name: ' . $data['name']);
                } else {
                    $errors[] = ['line' => $lineDisplay, 'message' => 'Erro ao salvar "' . $mapped['name'] . '" no banco de dados.'];
                }
            } catch (Exception $e) {
                $errors[] = ['line' => $lineDisplay, 'message' => 'Erro: ' . $e->getMessage()];
            }
        }
        
        echo json_encode([
            'success' => true,
            'imported' => $imported,
            'errors' => $errors
        ]);
        exit;
    }
    
    /**
     * Parse CSV file and return array of associative rows
     */
    private function parseCsvFile($filePath) {
        $rows = [];
        $handle = fopen($filePath, 'r');
        if (!$handle) return $rows;
        
        // Detect BOM and skip it
        $bom = fread($handle, 3);
        if ($bom !== chr(0xEF) . chr(0xBB) . chr(0xBF)) {
            rewind($handle);
        }
        
        // Read first line to detect separator
        $firstLine = fgets($handle);
        rewind($handle);
        // Skip BOM again
        $bom = fread($handle, 3);
        if ($bom !== chr(0xEF) . chr(0xBB) . chr(0xBF)) {
            rewind($handle);
        }
        
        $separator = (substr_count($firstLine, ';') >= substr_count($firstLine, ',')) ? ';' : ',';
        
        // Read header
        $headers = fgetcsv($handle, 0, $separator);
        if (!$headers) { fclose($handle); return $rows; }
        
        // Clean headers
        $headers = array_map(function($h) {
            return strtolower(trim(str_replace(['"', "'"], '', $h)));
        }, $headers);
        
        // Read data rows
        while (($line = fgetcsv($handle, 0, $separator)) !== false) {
            // Skip completely empty lines
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
    
    /**
     * Parse Excel file using PhpSpreadsheet (if available)
     */
    private function parseExcelFile($filePath) {
        if (!class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
            return [];
        }
        
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
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * AJAX: Create a new grade type on the fly
     */
    public function createGradeTypeAjax() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && Input::hasPost('name')) {
            $name = Input::post('name');
            $description = Input::post('description');
            $icon = Input::post('icon', 'string', 'fas fa-th');
            $id = $this->gradeModel->createGradeType($name, $description ?: null, $icon);
            if ($id) {
                echo json_encode(['success' => true, 'id' => $id, 'name' => $name, 'icon' => $icon]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Tipo de grade já existe ou erro ao criar.']);
            }
            exit;
        }
        echo json_encode(['success' => false]);
        exit;
    }

    /**
     * AJAX: Get grade types list
     */
    public function getGradeTypes() {
        $types = $this->gradeModel->getAllGradeTypes();
        echo json_encode($types);
        exit;
    }

    /**
     * AJAX: Generate and return combinations for a product based on provided grades data
     */
    public function generateCombinationsAjax() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $gradesData = Input::postArray('grades');
            // Build combinations client-side from the grades data provided
            $gradeArrays = [];
            foreach ($gradesData as $grade) {
                if (empty($grade['values']) || empty($grade['grade_type_id'])) continue;
                $typeName = $grade['type_name'] ?? 'Grade';
                $arr = [];
                foreach ($grade['values'] as $idx => $val) {
                    $val = trim($val);
                    if ($val !== '') {
                        $arr[] = [
                            'grade_name' => $typeName,
                            'value_label' => $val,
                            'temp_idx' => $idx
                        ];
                    }
                }
                if (!empty($arr)) {
                    $gradeArrays[] = $arr;
                }
            }

            // Generate cartesian product
            $result = [[]];
            foreach ($gradeArrays as $array) {
                $new = [];
                foreach ($result as $combo) {
                    foreach ($array as $item) {
                        $new[] = array_merge($combo, [$item]);
                    }
                }
                $result = $new;
            }

            $combinations = [];
            foreach ($result as $combo) {
                $labels = [];
                foreach ($combo as $item) {
                    $labels[] = $item['grade_name'] . ': ' . $item['value_label'];
                }
                $combinations[] = [
                    'label' => implode(' / ', $labels)
                ];
            }

            echo json_encode(['success' => true, 'combinations' => $combinations]);
            exit;
        }
        echo json_encode(['success' => false]);
        exit;
    }
}
