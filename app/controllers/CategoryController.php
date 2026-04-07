<?php
namespace Akti\Controllers;

use Akti\Models\Category;
use Akti\Models\Subcategory;
use Akti\Models\ProductionSector;
use Akti\Models\ProductGrade;
use Akti\Models\CategoryGrade;
use Akti\Models\Logger;
use Akti\Models\Product;
use Akti\Core\Log;
use Akti\Services\CategoryService;
use Akti\Utils\Input;

class CategoryController {
    
    private Category $categoryModel;
    private Subcategory $subcategoryModel;
    private ProductionSector $sectorModel;
    private ProductGrade $gradeModel;
    private CategoryGrade $categoryGradeModel;
    private Logger $logger;
    private CategoryService $categoryService;
    private \PDO $db;

    public function __construct(
        \PDO $db,
        Category $categoryModel,
        Subcategory $subcategoryModel,
        ProductionSector $sectorModel,
        ProductGrade $gradeModel,
        CategoryGrade $categoryGradeModel,
        Logger $logger,
        CategoryService $categoryService
    ) {
        $this->db = $db;
        $this->categoryModel = $categoryModel;
        $this->subcategoryModel = $subcategoryModel;
        $this->sectorModel = $sectorModel;
        $this->gradeModel = $gradeModel;
        $this->categoryGradeModel = $categoryGradeModel;
        $this->logger = $logger;
        $this->categoryService = $categoryService;
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

        if (Input::get('action') === 'edit' && Input::hasGet('id')) {
            $editId = Input::get('id', 'int');
            $editCategory = $this->categoryModel->getCategory($editId);
            $editCategorySectors = $this->sectorModel->getCategorySectors($editId);
            $editCategoryGrades = $this->categoryGradeModel->getCategoryGradesWithValues($editId);
            $editCategoryCombinations = $this->categoryGradeModel->getCategoryCombinations($editId);
        }
        if (Input::get('action') === 'editSub' && Input::hasGet('id')) {
            $editSubId = Input::get('id', 'int');
            $editSubcategory = $this->subcategoryModel->readOne($editSubId);
            $editSubcategorySectors = $this->sectorModel->getSubcategorySectors($editSubId);
            $editSubcategoryGrades = $this->categoryGradeModel->getSubcategoryGradesWithValues($editSubId);
            $editSubcategoryCombinations = $this->categoryGradeModel->getSubcategoryCombinations($editSubId);
        }

        require 'app/views/layout/header.php';
        require 'app/views/categories/index.php';
        require 'app/views/layout/footer.php';
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && Input::hasPost('name')) {
            $this->categoryModel->name = Input::post('name');
            $this->categoryModel->show_in_store = Input::hasPost('show_in_store') ? 1 : 0;
            $this->categoryModel->free_shipping = Input::hasPost('free_shipping') ? 1 : 0;
            if ($this->categoryModel->create()) {
                $this->logger->log('CREATE_CATEGORY', 'Created category: ' . Input::post('name'));
                // Salvar setores vinculados
                $sectorIds = Input::postArray('sector_ids');
                if (!empty($sectorIds)) {
                    $this->sectorModel->saveCategorySectors($this->categoryModel->id, $sectorIds);
                }
                // Salvar grades da categoria
                $categoryGrades = Input::postArray('category_grades');
                if (!empty($categoryGrades)) {
                    $this->categoryGradeModel->saveCategoryGrades($this->categoryModel->id, $categoryGrades);
                }
            }
        }
        header('Location: ?page=categories&status=success');
        exit;
    }

    public function update() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && Input::hasPost('id')) {
            $id = Input::post('id', 'int');
            $showInStore = Input::hasPost('show_in_store') ? 1 : 0;
            $freeShipping = Input::hasPost('free_shipping') ? 1 : 0;
            $this->categoryModel->update($id, Input::post('name'), $showInStore, $freeShipping);
            $this->logger->log('UPDATE_CATEGORY', 'Updated category ID: ' . $id);
            // Salvar setores vinculados
            $sectorIds = Input::postArray('sector_ids');
            $this->sectorModel->saveCategorySectors($id, $sectorIds);
            // Salvar grades da categoria
            $gradesData = Input::postArray('category_grades');
            $this->categoryGradeModel->saveCategoryGrades($id, $gradesData);
            // Salvar estado de combinações (ativo/inativo)
            $categoryCombinations = Input::postArray('category_combinations');
            if (!empty($categoryCombinations)) {
                $this->categoryService->saveCategoryCombinationsState($id, $categoryCombinations);
            }
        }
        header('Location: ?page=categories&status=success');
        exit;
    }

    public function delete() {
        $id = Input::get('id', 'int');
        if ($id) {
            $this->categoryModel->delete($id);
            $this->logger->log('DELETE_CATEGORY', 'Deleted category ID: ' . $id);
        }
        header('Location: ?page=categories&status=success');
        exit;
    }

    public function storeSub() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && Input::hasPost('name') && Input::hasPost('category_id')) {
            $this->subcategoryModel->name = Input::post('name');
            $this->subcategoryModel->category_id = Input::post('category_id', 'int');
            $this->subcategoryModel->show_in_store = Input::hasPost('show_in_store') ? 1 : 0;
            $this->subcategoryModel->free_shipping = Input::hasPost('free_shipping') ? 1 : 0;
            if ($this->subcategoryModel->create()) {
                $this->logger->log('CREATE_SUBCATEGORY', 'Created subcategory: ' . Input::post('name'));
                // Salvar setores vinculados
                $sectorIds = Input::postArray('sector_ids');
                if (!empty($sectorIds)) {
                    $this->sectorModel->saveSubcategorySectors($this->subcategoryModel->id, $sectorIds);
                }
                // Salvar grades da subcategoria
                $subcategoryGrades = Input::postArray('subcategory_grades');
                if (!empty($subcategoryGrades)) {
                    $this->categoryGradeModel->saveSubcategoryGrades($this->subcategoryModel->id, $subcategoryGrades);
                }
            }
        }
        header('Location: ?page=categories&tab=subcategories&status=success');
        exit;
    }

    public function updateSub() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && Input::hasPost('id')) {
            $id = Input::post('id', 'int');
            $showInStore = Input::hasPost('show_in_store') ? 1 : 0;
            $freeShipping = Input::hasPost('free_shipping') ? 1 : 0;
            $this->subcategoryModel->update($id, Input::post('name'), Input::post('category_id', 'int'), $showInStore, $freeShipping);
            $this->logger->log('UPDATE_SUBCATEGORY', 'Updated subcategory ID: ' . $id);
            // Salvar setores vinculados
            $sectorIds = Input::postArray('sector_ids');
            $this->sectorModel->saveSubcategorySectors($id, $sectorIds);
            // Salvar grades da subcategoria
            $gradesData = Input::postArray('subcategory_grades');
            $this->categoryGradeModel->saveSubcategoryGrades($id, $gradesData);
            // Salvar estado de combinações (ativo/inativo)
            $subcategoryCombinations = Input::postArray('subcategory_combinations');
            if (!empty($subcategoryCombinations)) {
                $this->categoryService->saveSubcategoryCombinationsState($id, $subcategoryCombinations);
            }
        }
        header('Location: ?page=categories&tab=subcategories&status=success');
        exit;
    }

    public function deleteSub() {
        $id = Input::get('id', 'int');
        if ($id) {
            $this->subcategoryModel->delete($id);
            $this->logger->log('DELETE_SUBCATEGORY', 'Deleted subcategory ID: ' . $id);
        }
        header('Location: ?page=categories&tab=subcategories&status=success');
        exit;
    }

    // ─────────────────────────────────────────────────────
    // AJAX: Get inherited grades for a product (by subcategory/category)
    // ─────────────────────────────────────────────────────

    public function getInheritedGradesAjax() {
        $subcategoryId = Input::get('subcategory_id', 'int');
        $categoryId = Input::get('category_id', 'int');

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
            $id = Input::post('id', 'int');
            $isActive = Input::post('is_active', 'int', 1);
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
            $id = Input::post('id', 'int');
            $isActive = Input::post('is_active', 'int', 1);
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
        $type = Input::get('type', 'enum', '', ['category', 'subcategory']);
        $id = Input::get('id', 'int', 0);

        if (!$id || !in_array($type, ['category', 'subcategory'])) {
            echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos.']);
            exit;
        }

        try {
            $productModel = new Product($this->db);

            if ($type === 'category') {
                $products = $productModel->getByCategory($id);
            } else {
                $products = $productModel->getBySubcategory($id);
            }

            $exportInfo = $this->categoryService->getSourceExportInfo($type, $id);

            echo json_encode([
                'success' => true,
                'products' => $products,
                'has_grades' => $exportInfo['has_grades'],
                'has_sectors' => $exportInfo['has_sectors']
            ]);
        } catch (\Exception $e) {
            Log::error('CategoryController: getProducts', ['exception' => $e->getMessage()]);
            echo json_encode([
                'success' => false,
                'message' => 'Erro interno ao buscar produtos. Tente novamente.',
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

        $type = Input::post('type', 'enum', '', ['category', 'subcategory']);
        $sourceId = Input::post('source_id', 'int', 0);
        $productIds = Input::post('product_ids', 'intArray');
        $exportGrades = Input::post('export_grades', 'bool');
        $exportSectors = Input::post('export_sectors', 'bool');

        if (!$sourceId || !in_array($type, ['category', 'subcategory']) || empty($productIds)) {
            echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos.']);
            exit;
        }

        if (!$exportGrades && !$exportSectors) {
            echo json_encode(['success' => false, 'message' => 'Selecione ao menos grades ou setores para exportar.']);
            exit;
        }

        $results = $this->categoryService->exportToProducts($type, $sourceId, $productIds, $exportGrades, $exportSectors);

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
        $subcategoryId = Input::get('subcategory_id', 'int');
        $categoryId = Input::get('category_id', 'int');

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
