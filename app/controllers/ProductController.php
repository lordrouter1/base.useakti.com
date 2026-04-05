<?php
namespace Akti\Controllers;

use Akti\Models\Product;
use Akti\Models\Category;
use Akti\Models\Subcategory;
use Akti\Models\ProductionSector;
use Akti\Models\ProductGrade;
use Akti\Models\Logger;
use Akti\Models\PriceTable;
use Akti\Services\ProductImportService;
use Akti\Services\ProductGradeService;
use Akti\Utils\Input;
use Akti\Utils\Sanitizer;
use Akti\Utils\Validator;
use TenantManager;

class ProductController {

    private Product $productModel;
    private Category $categoryModel;
    private Subcategory $subcategoryModel;
    private ProductionSector $sectorModel;
    private ProductGrade $gradeModel;
    private Logger $logger;
    private ProductImportService $importService;
    private ProductGradeService $gradeService;
    private \PDO $db;

    public function __construct(
        \PDO $db,
        Product $productModel,
        Category $categoryModel,
        Subcategory $subcategoryModel,
        ProductionSector $sectorModel,
        ProductGrade $gradeModel,
        Logger $logger,
        ProductImportService $importService,
        ProductGradeService $gradeService
    ) {
        $this->db = $db;
        $this->productModel = $productModel;
        $this->categoryModel = $categoryModel;
        $this->subcategoryModel = $subcategoryModel;
        $this->sectorModel = $sectorModel;
        $this->gradeModel = $gradeModel;
        $this->logger = $logger;
        $this->importService = $importService;
        $this->gradeService = $gradeService;
        // Categorias para filtro
        $categories = $this->categoryModel->readAll();

        // Campos disponíveis para mapeamento de importação
        $importFields = [
            'name'        => ['label' => 'Nome do Produto', 'required' => true],
            'price'       => ['label' => 'Preço de Venda', 'required' => true],
            'cost_price'  => ['label' => 'Preço de Custo', 'required' => false],
            'sku'         => ['label' => 'SKU / Código', 'required' => false],
            'category'    => ['label' => 'Categoria', 'required' => false],
            'subcategory' => ['label' => 'Subcategoria', 'required' => false],
            'description' => ['label' => 'Descrição', 'required' => false],
            'format'      => ['label' => 'Formato', 'required' => false],
            'material'    => ['label' => 'Material', 'required' => false],
            'ncm'         => ['label' => 'NCM Fiscal', 'required' => false],
        ];

        require 'app/views/layout/header.php';
        require 'app/views/products/index.php';
        require 'app/views/layout/footer.php';
    }

    public function create() {
        // Fetch categories for the dropdown
        $categories = $this->categoryModel->readAll();

        // Fetch price tables
        $priceTableModel = new PriceTable($this->db);
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
                    $ptModel = new PriceTable($this->db);
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

        $categories = $this->categoryModel->readAll();

        $images = $this->productModel->getImages($id);
        
        // Get Subcategories for current category
        $subcategories = [];
        if ($product['category_id']) {
            $subcategories = $this->categoryModel->getSubcategories($product['category_id']);
        }

        // Fetch price tables and existing prices for this product
        $priceTableModel = new PriceTable($this->db);
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
                    $ptModel = new PriceTable($this->db);
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
     * AJAX: Busca produtos para Select2 (substitui a API Node.js).
     * GET ?page=products&action=searchSelect2&q=termo&limit=10
     * Retorna JSON no mesmo formato: { data: [{ id, name, sku, description, price, category_id, combinations: [...] }] }
     */
    public function searchSelect2() {
        header('Content-Type: application/json');

        $q     = isset($_GET['q']) ? trim($_GET['q']) : '';
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

        $results = $this->productModel->searchForSelect2($q, $limit);

        echo json_encode(['data' => $results]);
        exit;
    }

    /**
     * AJAX: Busca paginada de produtos para Select2 com scroll infinito.
     * GET ?page=products&action=searchAjax&q=termo&page=1&per_page=20
     * Retorna JSON: { data: [...], total: int, hasMore: bool }
     */
    public function searchAjax(): void
    {
        header('Content-Type: application/json');

        $q       = Input::get('q') ?? '';
        $page    = Input::get('page', 'int', 1);
        $perPage = Input::get('per_page', 'int', 20);

        $result = $this->productModel->searchPaginated($q, $page, $perPage);

        echo json_encode([
            'success' => true,
            'data'    => $result['data'],
            'total'   => $result['total'],
            'hasMore' => $result['hasMore'],
        ]);
        exit;
    }

    /**
     * AJAX: Lista produtos com filtros e paginação (para a seção de visão geral)
     */
    public function getProductsList() {
        header('Content-Type: application/json');

        $search     = Input::get('search');
        $categoryId = Input::get('category_id', 'int');
        $page       = max(1, Input::get('pg', 'int', 1));
        $perPage    = Input::get('per_page', 'int', 20);

        $result = $this->productModel->readPaginatedFiltered($page, $perPage, $categoryId ?: null, $search ?: null);

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

    /**
     * AJAX: Analisa arquivo de importação (CSV/XLS/XLSX) e retorna preview.
     * Delegado ao ProductImportService.
     */
    public function parseImportFile() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['import_file'])) {
            echo json_encode(['success' => false, 'message' => 'Nenhum arquivo enviado.']);
            exit;
        }

        $result = $this->importService->parseImportFile($_FILES['import_file']);
        echo json_encode($result);
        exit;
    }

    /**
     * AJAX: Importa produtos usando mapeamento de colunas definido pelo usuário.
     * Delegado ao ProductImportService.
     */
    public function importProductsMapped() {
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

        $result = $this->importService->importProductsMapped($mapping);
        echo json_encode($result);
        exit;
    }

    /**
     * Download CSV import template.
     * Delegado ao ProductImportService.
     */
    public function downloadImportTemplate() {
        $this->importService->generateImportTemplate();
        exit;
    }

    /**
     * AJAX: Import products from CSV/XLS file (mapeamento automático por header).
     * Delegado ao ProductImportService.
     */
    public function importProducts() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['import_file'])) {
            echo json_encode(['success' => false, 'message' => 'Nenhum arquivo enviado.']);
            exit;
        }

        $result = $this->importService->importProductsDirect($_FILES['import_file']);
        echo json_encode($result);
        exit;
    }
    
    /**
     * AJAX: Create a new grade type on the fly.
     * Delegado ao ProductGradeService.
     */
    public function createGradeTypeAjax() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && Input::hasPost('name')) {
            $name = Input::post('name');
            $description = Input::post('description');
            $icon = Input::post('icon', 'string', 'fas fa-th');
            $result = $this->gradeService->createGradeType($name, $description ?: null, $icon);
            echo json_encode($result);
            exit;
        }
        echo json_encode(['success' => false]);
        exit;
    }

    /**
     * AJAX: Get grade types list.
     * Delegado ao ProductGradeService.
     */
    public function getGradeTypes() {
        $types = $this->gradeService->getAllGradeTypes();
        echo json_encode($types);
        exit;
    }

    /**
     * AJAX: Generate and return combinations based on provided grades data.
     * Delegado ao ProductGradeService.
     */
    public function generateCombinationsAjax() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $gradesData = Input::postArray('grades');
            $combinations = $this->gradeService->generateCombinations($gradesData);
            echo json_encode(['success' => true, 'combinations' => $combinations]);
            exit;
        }
        echo json_encode(['success' => false]);
        exit;
    }
}
