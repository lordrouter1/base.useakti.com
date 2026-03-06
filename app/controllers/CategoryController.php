<?php
require_once 'app/models/Category.php';
require_once 'app/models/Subcategory.php';
require_once 'app/models/ProductionSector.php';
require_once 'app/models/ProductGrade.php';
require_once 'app/models/CategoryGrade.php';

class CategoryController {
    
    private $categoryModel;
    private $subcategoryModel;
    private $sectorModel;
    private $gradeModel;
    private $categoryGradeModel;
    private $logger;

    public function __construct() {
        $db = (new Database())->getConnection();
        $this->categoryModel = new Category($db);
        $this->subcategoryModel = new Subcategory($db);
        $this->sectorModel = new ProductionSector($db);
        $this->gradeModel = new ProductGrade($db);
        $this->categoryGradeModel = new CategoryGrade($db);
        require_once 'app/models/Logger.php';
        $this->logger = new Logger($db);
    }

    public function index() {
        $categories = $this->categoryModel->readAllWithCount();
        $subcategories = $this->subcategoryModel->readAll();
        $allSectors = $this->sectorModel->readAll(true);
        
        // Precarregar setores de cada categoria para exibição na lista
        $categorySectorsMap = [];
        foreach ($categories as $cat) {
            $categorySectorsMap[$cat['id']] = $this->sectorModel->getCategorySectors($cat['id']);
        }
        
        // Precarregar setores de cada subcategoria para exibição na lista
        $subcategorySectorsMap = [];
        foreach ($subcategories as $sub) {
            $subcategorySectorsMap[$sub['id']] = $this->sectorModel->getSubcategorySectors($sub['id']);
        }

        // Grade types (para os formulários de grades)
        $gradeTypes = $this->gradeModel->getAllGradeTypes();

        // Precarregar info de grades por categoria/subcategoria para exibição na lista
        $categoryGradesMap = [];
        foreach ($categories as $cat) {
            $categoryGradesMap[$cat['id']] = $this->categoryGradeModel->categoryHasGrades($cat['id']);
        }
        $subcategoryGradesMap = [];
        foreach ($subcategories as $sub) {
            $subcategoryGradesMap[$sub['id']] = $this->categoryGradeModel->subcategoryHasGrades($sub['id']);
        }

        $editCategory = null;
        $editSubcategory = null;
        $editCategorySectors = [];
        $editSubcategorySectors = [];
        $editCategoryGrades = [];
        $editCategoryCombinations = [];
        $editSubcategoryGrades = [];
        $editSubcategoryCombinations = [];

        if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
            $editCategory = $this->categoryModel->getCategory($_GET['id']);
            $editCategorySectors = $this->sectorModel->getCategorySectors($_GET['id']);
            $editCategoryGrades = $this->categoryGradeModel->getCategoryGradesWithValues($_GET['id']);
            $editCategoryCombinations = $this->categoryGradeModel->getCategoryCombinations($_GET['id']);
        }
        if (isset($_GET['action']) && $_GET['action'] === 'editSub' && isset($_GET['id'])) {
            $editSubcategory = $this->subcategoryModel->readOne($_GET['id']);
            $editSubcategorySectors = $this->sectorModel->getSubcategorySectors($_GET['id']);
            $editSubcategoryGrades = $this->categoryGradeModel->getSubcategoryGradesWithValues($_GET['id']);
            $editSubcategoryCombinations = $this->categoryGradeModel->getSubcategoryCombinations($_GET['id']);
        }

        require 'app/views/layout/header.php';
        require 'app/views/categories/index.php';
        require 'app/views/layout/footer.php';
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['name'])) {
            $this->categoryModel->name = $_POST['name'];
            if ($this->categoryModel->create()) {
                $this->logger->log('CREATE_CATEGORY', 'Created category: ' . $_POST['name']);
                // Salvar setores vinculados
                if (isset($_POST['sector_ids']) && is_array($_POST['sector_ids'])) {
                    $this->sectorModel->saveCategorySectors($this->categoryModel->id, $_POST['sector_ids']);
                }
                // Salvar grades da categoria
                if (!empty($_POST['category_grades']) && is_array($_POST['category_grades'])) {
                    $this->categoryGradeModel->saveCategoryGrades($this->categoryModel->id, $_POST['category_grades']);
                }
            }
        }
        header('Location: ?page=categories&status=success');
        exit;
    }

    public function update() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id'])) {
            $this->categoryModel->update($_POST['id'], $_POST['name']);
            $this->logger->log('UPDATE_CATEGORY', 'Updated category ID: ' . $_POST['id']);
            // Salvar setores vinculados
            $sectorIds = isset($_POST['sector_ids']) && is_array($_POST['sector_ids']) ? $_POST['sector_ids'] : [];
            $this->sectorModel->saveCategorySectors($_POST['id'], $sectorIds);
            // Salvar grades da categoria
            $gradesData = isset($_POST['category_grades']) && is_array($_POST['category_grades']) ? $_POST['category_grades'] : [];
            $this->categoryGradeModel->saveCategoryGrades($_POST['id'], $gradesData);
            // Salvar estado de combinações (ativo/inativo)
            if (!empty($_POST['category_combinations']) && is_array($_POST['category_combinations'])) {
                $this->saveCategoryCombinationsState($_POST['id'], $_POST['category_combinations']);
            }
        }
        header('Location: ?page=categories&status=success');
        exit;
    }

    public function delete() {
        if (isset($_GET['id'])) {
            $this->categoryModel->delete($_GET['id']);
            $this->logger->log('DELETE_CATEGORY', 'Deleted category ID: ' . $_GET['id']);
        }
        header('Location: ?page=categories&status=success');
        exit;
    }

    public function storeSub() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['name']) && !empty($_POST['category_id'])) {
            $this->subcategoryModel->name = $_POST['name'];
            $this->subcategoryModel->category_id = $_POST['category_id'];
            if ($this->subcategoryModel->create()) {
                $this->logger->log('CREATE_SUBCATEGORY', 'Created subcategory: ' . $_POST['name']);
                // Salvar setores vinculados
                if (isset($_POST['sector_ids']) && is_array($_POST['sector_ids'])) {
                    $this->sectorModel->saveSubcategorySectors($this->subcategoryModel->id, $_POST['sector_ids']);
                }
                // Salvar grades da subcategoria
                if (!empty($_POST['subcategory_grades']) && is_array($_POST['subcategory_grades'])) {
                    $this->categoryGradeModel->saveSubcategoryGrades($this->subcategoryModel->id, $_POST['subcategory_grades']);
                }
            }
        }
        header('Location: ?page=categories&tab=subcategories&status=success');
        exit;
    }

    public function updateSub() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id'])) {
            $this->subcategoryModel->update($_POST['id'], $_POST['name'], $_POST['category_id']);
            $this->logger->log('UPDATE_SUBCATEGORY', 'Updated subcategory ID: ' . $_POST['id']);
            // Salvar setores vinculados
            $sectorIds = isset($_POST['sector_ids']) && is_array($_POST['sector_ids']) ? $_POST['sector_ids'] : [];
            $this->sectorModel->saveSubcategorySectors($_POST['id'], $sectorIds);
            // Salvar grades da subcategoria
            $gradesData = isset($_POST['subcategory_grades']) && is_array($_POST['subcategory_grades']) ? $_POST['subcategory_grades'] : [];
            $this->categoryGradeModel->saveSubcategoryGrades($_POST['id'], $gradesData);
            // Salvar estado de combinações (ativo/inativo)
            if (!empty($_POST['subcategory_combinations']) && is_array($_POST['subcategory_combinations'])) {
                $this->saveSubcategoryCombinationsState($_POST['id'], $_POST['subcategory_combinations']);
            }
        }
        header('Location: ?page=categories&tab=subcategories&status=success');
        exit;
    }

    public function deleteSub() {
        if (isset($_GET['id'])) {
            $this->subcategoryModel->delete($_GET['id']);
            $this->logger->log('DELETE_SUBCATEGORY', 'Deleted subcategory ID: ' . $_GET['id']);
        }
        header('Location: ?page=categories&tab=subcategories&status=success');
        exit;
    }

    // ─────────────────────────────────────────────────────
    // Helper: save combination is_active state from form
    // ─────────────────────────────────────────────────────

    private function saveCategoryCombinationsState($categoryId, $combosData) {
        $db = (new Database())->getConnection();
        foreach ($combosData as $key => $data) {
            $isActive = isset($data['is_active']) ? (int)$data['is_active'] : 1;
            $stmt = $db->prepare("
                UPDATE category_grade_combinations 
                SET is_active = :active 
                WHERE category_id = :cid AND combination_key = :ckey
            ");
            $stmt->execute([':active' => $isActive, ':cid' => $categoryId, ':ckey' => $key]);
        }
    }

    private function saveSubcategoryCombinationsState($subcategoryId, $combosData) {
        $db = (new Database())->getConnection();
        foreach ($combosData as $key => $data) {
            $isActive = isset($data['is_active']) ? (int)$data['is_active'] : 1;
            $stmt = $db->prepare("
                UPDATE subcategory_grade_combinations 
                SET is_active = :active 
                WHERE subcategory_id = :sid AND combination_key = :ckey
            ");
            $stmt->execute([':active' => $isActive, ':sid' => $subcategoryId, ':ckey' => $key]);
        }
    }

    // ─────────────────────────────────────────────────────
    // AJAX: Get inherited grades for a product (by subcategory/category)
    // ─────────────────────────────────────────────────────

    public function getInheritedGradesAjax() {
        $subcategoryId = $_GET['subcategory_id'] ?? null;
        $categoryId = $_GET['category_id'] ?? null;

        $result = $this->categoryGradeModel->getInheritedGrades($subcategoryId, $categoryId);
        echo json_encode([
            'success' => true,
            'grades' => $result['grades'],
            'source' => $result['source'],
            'source_id' => $result['source_id'],
            'inactive_keys' => $result['inactive_keys']
        ]);
        exit;
    }

    // AJAX: Toggle combination for category
    public function toggleCategoryCombinationAjax() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? null;
            $isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
            if ($id) {
                $this->categoryGradeModel->toggleCategoryCombination($id, $isActive);
                echo json_encode(['success' => true]);
                exit;
            }
        }
        echo json_encode(['success' => false]);
        exit;
    }

    // AJAX: Toggle combination for subcategory
    public function toggleSubcategoryCombinationAjax() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? null;
            $isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
            if ($id) {
                $this->categoryGradeModel->toggleSubcategoryCombination($id, $isActive);
                echo json_encode(['success' => true]);
                exit;
            }
        }
        echo json_encode(['success' => false]);
        exit;
    }

    // ─────────────────────────────────────────────────────
    // AJAX: Get products for export modal
    // ─────────────────────────────────────────────────────

    public function getProductsForExport() {
        header('Content-Type: application/json');
        $type = $_GET['type'] ?? ''; // 'category' or 'subcategory'
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        if (!$id || !in_array($type, ['category', 'subcategory'])) {
            echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos.']);
            exit;
        }

        try {
            require_once 'app/models/Product.php';
            $db = (new Database())->getConnection();
            $productModel = new Product($db);

            if ($type === 'category') {
                $products = $productModel->getByCategory($id);
            } else {
                $products = $productModel->getBySubcategory($id);
            }

            // Get source grades/sectors info
            $hasGrades = false;
            $hasSectors = false;
            if ($type === 'category') {
                $hasGrades = $this->categoryGradeModel->categoryHasGrades($id);
                $sectors = $this->sectorModel->getCategorySectors($id);
                $hasSectors = !empty($sectors);
            } else {
                $hasGrades = $this->categoryGradeModel->subcategoryHasGrades($id);
                $sectors = $this->sectorModel->getSubcategorySectors($id);
                $hasSectors = !empty($sectors);
            }

            echo json_encode([
                'success' => true,
                'products' => $products,
                'has_grades' => $hasGrades,
                'has_sectors' => $hasSectors
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao buscar produtos: ' . $e->getMessage(),
                'products' => []
            ]);
        }
        exit;
    }

    // ─────────────────────────────────────────────────────
    // AJAX: Execute batch export of grades/sectors to products
    // ─────────────────────────────────────────────────────

    public function exportToProducts() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método inválido.']);
            exit;
        }

        $type = $_POST['type'] ?? ''; // 'category' or 'subcategory'
        $sourceId = isset($_POST['source_id']) ? (int)$_POST['source_id'] : 0;
        $productIds = $_POST['product_ids'] ?? [];
        $exportGrades = !empty($_POST['export_grades']);
        $exportSectors = !empty($_POST['export_sectors']);

        if (!$sourceId || !in_array($type, ['category', 'subcategory']) || empty($productIds)) {
            echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos.']);
            exit;
        }

        if (!$exportGrades && !$exportSectors) {
            echo json_encode(['success' => false, 'message' => 'Selecione ao menos grades ou setores para exportar.']);
            exit;
        }

        $db = (new Database())->getConnection();
        $gradeModel = new ProductGrade($db);
        $sectorModel = new ProductionSector($db);
        require_once 'app/models/Logger.php';
        $logger = new Logger($db);

        $results = ['grades_applied' => 0, 'sectors_applied' => 0, 'errors' => []];

        // Get source grades if exporting grades
        $sourceGrades = [];
        if ($exportGrades) {
            if ($type === 'category') {
                $inheritedRaw = $this->categoryGradeModel->getCategoryGradesWithValues($sourceId);
            } else {
                $inheritedRaw = $this->categoryGradeModel->getSubcategoryGradesWithValues($sourceId);
            }
            $sourceGrades = $this->categoryGradeModel->convertInheritedToProductFormat($inheritedRaw);
        }

        // Get source sectors if exporting sectors
        $sourceSectorIds = [];
        if ($exportSectors) {
            if ($type === 'category') {
                $sectors = $sectorModel->getCategorySectors($sourceId);
            } else {
                $sectors = $sectorModel->getSubcategorySectors($sourceId);
            }
            $sourceSectorIds = array_column($sectors, 'sector_id');
        }

        foreach ($productIds as $productId) {
            $productId = (int)$productId;
            try {
                if ($exportGrades && !empty($sourceGrades)) {
                    $gradeModel->saveProductGrades($productId, $sourceGrades);
                    $results['grades_applied']++;
                }
                if ($exportSectors && !empty($sourceSectorIds)) {
                    $sectorModel->saveProductSectors($productId, $sourceSectorIds);
                    $results['sectors_applied']++;
                }
                $logger->log('EXPORT_TO_PRODUCT', "Exported {$type} #{$sourceId} grades/sectors to product #{$productId}");
            } catch (Exception $e) {
                $results['errors'][] = "Produto #{$productId}: " . $e->getMessage();
            }
        }

        echo json_encode([
            'success' => true,
            'results' => $results,
            'message' => sprintf(
                'Exportação concluída! Grades: %d produto(s). Setores: %d produto(s).%s',
                $results['grades_applied'],
                $results['sectors_applied'],
                !empty($results['errors']) ? ' Erros: ' . count($results['errors']) : ''
            )
        ]);
        exit;
    }

    // ─────────────────────────────────────────────────────
    // AJAX: Get inherited sectors for a product (by subcategory/category)
    // ─────────────────────────────────────────────────────

    public function getInheritedSectorsAjax() {
        header('Content-Type: application/json');
        $subcategoryId = $_GET['subcategory_id'] ?? null;
        $categoryId = $_GET['category_id'] ?? null;

        $sectors = [];
        $source = null;

        // Try subcategory first
        if ($subcategoryId) {
            $sectors = $this->sectorModel->getSubcategorySectors($subcategoryId);
            if (!empty($sectors)) {
                $source = 'subcategory';
            }
        }

        // Fall back to category
        if (empty($sectors) && $categoryId) {
            $sectors = $this->sectorModel->getCategorySectors($categoryId);
            if (!empty($sectors)) {
                $source = 'category';
            }
        }

        echo json_encode([
            'success' => true,
            'sectors' => $sectors,
            'source' => $source
        ]);
        exit;
    }
}
