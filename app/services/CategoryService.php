<?php
namespace Akti\Services;

use Akti\Models\Category;
use Akti\Models\Subcategory;
use Akti\Models\ProductionSector;
use Akti\Models\ProductGrade;
use Akti\Models\CategoryGrade;
use Akti\Models\Product;
use Akti\Models\Logger;
use PDO;

/**
 * CategoryService — Lógica de negócio para categorias e subcategorias.
 *
 * Extraído do CategoryController na Fase 2 para manter o controller slim.
 * Concentra: toggle de combinações (SQL direto), exportação em lote de
 * grades/setores para produtos, e gerenciamento de estados de combinações.
 *
 * @package Akti\Services
 */
class CategoryService
{
    private PDO $db;
    private CategoryGrade $categoryGradeModel;
    private ProductionSector $sectorModel;
    private Logger $logger;

    public function __construct(
        PDO $db,
        CategoryGrade $categoryGradeModel,
        ProductionSector $sectorModel
    ) {
        $this->db = $db;
        $this->categoryGradeModel = $categoryGradeModel;
        $this->sectorModel = $sectorModel;
        $this->logger = new Logger($db);
    }

    // ═══════════════════════════════════════════
    // COMBINAÇÕES (toggle ativo/inativo)
    // ═══════════════════════════════════════════

    /**
     * Salva o estado (ativo/inativo) das combinações de grades de uma categoria.
     *
     * @param int   $categoryId  ID da categoria
     * @param array $combosData  Array associativo [combination_key => ['is_active' => 0|1]]
     */
    public function saveCategoryCombinationsState(int $categoryId, array $combosData): void
    {
        foreach ($combosData as $key => $data) {
            $isActive = isset($data['is_active']) ? (int) $data['is_active'] : 1;
            $stmt = $this->db->prepare("
                UPDATE category_grade_combinations 
                SET is_active = :active 
                WHERE category_id = :cid AND combination_key = :ckey
            ");
            $stmt->execute([':active' => $isActive, ':cid' => $categoryId, ':ckey' => $key]);
        }
    }

    /**
     * Salva o estado (ativo/inativo) das combinações de grades de uma subcategoria.
     *
     * @param int   $subcategoryId  ID da subcategoria
     * @param array $combosData     Array associativo [combination_key => ['is_active' => 0|1]]
     */
    public function saveSubcategoryCombinationsState(int $subcategoryId, array $combosData): void
    {
        foreach ($combosData as $key => $data) {
            $isActive = isset($data['is_active']) ? (int) $data['is_active'] : 1;
            $stmt = $this->db->prepare("
                UPDATE subcategory_grade_combinations 
                SET is_active = :active 
                WHERE subcategory_id = :sid AND combination_key = :ckey
            ");
            $stmt->execute([':active' => $isActive, ':sid' => $subcategoryId, ':ckey' => $key]);
        }
    }

    // ═══════════════════════════════════════════
    // EXPORTAÇÃO DE GRADES/SETORES PARA PRODUTOS
    // ═══════════════════════════════════════════

    /**
     * Exporta grades e/ou setores de uma categoria/subcategoria para um conjunto de produtos.
     *
     * @param string $type        'category' ou 'subcategory'
     * @param int    $sourceId    ID da categoria ou subcategoria fonte
     * @param array  $productIds  Lista de IDs de produtos destino
     * @param bool   $exportGrades   Se deve exportar grades
     * @param bool   $exportSectors  Se deve exportar setores
     * @return array Resultado com contagens e eventuais erros
     */
    public function exportToProducts(
        string $type,
        int $sourceId,
        array $productIds,
        bool $exportGrades,
        bool $exportSectors
    ): array {
        $results = ['grades_applied' => 0, 'sectors_applied' => 0, 'errors' => []];

        // Obter grades da fonte
        $sourceGrades = [];
        if ($exportGrades) {
            if ($type === 'category') {
                $inheritedRaw = $this->categoryGradeModel->getCategoryGradesWithValues($sourceId);
            } else {
                $inheritedRaw = $this->categoryGradeModel->getSubcategoryGradesWithValues($sourceId);
            }
            $sourceGrades = $this->categoryGradeModel->convertInheritedToProductFormat($inheritedRaw);
        }

        // Obter setores da fonte
        $sourceSectorIds = [];
        if ($exportSectors) {
            if ($type === 'category') {
                $sectors = $this->sectorModel->getCategorySectors($sourceId);
            } else {
                $sectors = $this->sectorModel->getSubcategorySectors($sourceId);
            }
            $sourceSectorIds = array_column($sectors, 'sector_id');
        }

        $gradeModel = new ProductGrade($this->db);

        foreach ($productIds as $productId) {
            $productId = (int) $productId;
            try {
                if ($exportGrades && !empty($sourceGrades)) {
                    $gradeModel->saveProductGrades($productId, $sourceGrades);
                    $results['grades_applied']++;
                }
                if ($exportSectors && !empty($sourceSectorIds)) {
                    $this->sectorModel->saveProductSectors($productId, $sourceSectorIds);
                    $results['sectors_applied']++;
                }
                $this->logger->log('EXPORT_TO_PRODUCT', "Exported {$type} #{$sourceId} grades/sectors to product #{$productId}");
            } catch (\Exception $e) {
                $results['errors'][] = "Produto #{$productId}: " . $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Obtém informações de grades e setores de uma fonte (categoria/subcategoria) para exibição no modal de exportação.
     *
     * @param string $type  'category' ou 'subcategory'
     * @param int    $id    ID da categoria ou subcategoria
     * @return array ['has_grades' => bool, 'has_sectors' => bool]
     */
    public function getSourceExportInfo(string $type, int $id): array
    {
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

        return ['has_grades' => $hasGrades, 'has_sectors' => $hasSectors];
    }
}
