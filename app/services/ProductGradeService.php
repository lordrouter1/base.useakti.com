<?php
namespace Akti\Services;

use Akti\Models\ProductGrade;
use Akti\Utils\Input;
use PDO;

/**
 * Service responsável pela lógica de grades e combinações de produtos.
 * Extraído do ProductController (Fase 2 — Refatoração de Controllers Monolíticos).
 */
class ProductGradeService
{
    private $gradeModel;

    /**
     * Construtor da classe ProductGradeService.
     *
     * @param ProductGrade $gradeModel Grade model
     */
    public function __construct(ProductGrade $gradeModel)
    {
        $this->gradeModel = $gradeModel;
    }

    /**
     * Retorna todos os tipos de grade.
     */
    public function getAllGradeTypes(): array
    {
        return $this->gradeModel->getAllGradeTypes();
    }

    /**
     * Retorna grades com valores de um produto.
     */
    public function getProductGradesWithValues(int $productId): array
    {
        return $this->gradeModel->getProductGradesWithValues($productId);
    }

    /**
     * Retorna combinações de um produto.
     */
    public function getProductCombinations(int $productId): array
    {
        return $this->gradeModel->getProductCombinations($productId);
    }

    /**
     * Cria um novo tipo de grade via AJAX.
     *
     * @param string $name
     * @param string|null $description
     * @param string $icon
     * @return array Resultado com success, id, name, icon
     */
    public function createGradeType(string $name, ?string $description, string $icon = 'fas fa-th'): array
    {
        $id = $this->gradeModel->createGradeType($name, $description, $icon);
        if ($id) {
            return ['success' => true, 'id' => $id, 'name' => $name, 'icon' => $icon];
        }
        return ['success' => false, 'message' => 'Tipo de grade já existe ou erro ao criar.'];
    }

    /**
     * Salva grades de um produto.
     */
    public function saveProductGrades(int $productId, array $grades): void
    {
        $this->gradeModel->saveProductGrades($productId, $grades);
    }

    /**
     * Salva dados de combinações de um produto.
     */
    public function saveCombinationsData(int $productId, array $combinations): void
    {
        $this->gradeModel->saveCombinationsData($productId, $combinations);
    }

    /**
     * Gera combinações (produto cartesiano) com base nos dados de grades.
     *
     * @param array $gradesData Array com dados de grades e valores
     * @return array Lista de combinações geradas
     */
    public function generateCombinations(array $gradesData): array
    {
        $gradeArrays = [];
        foreach ($gradesData as $grade) {
            if (empty($grade['values']) || empty($grade['grade_type_id'])) continue;
            $typeName = $grade['type_name'] ?? 'Grade';
            $arr = [];
            foreach ($grade['values'] as $idx => $val) {
                $val = trim($val);
                if ($val !== '') {
                    $arr[] = [
                        'grade_name'  => $typeName,
                        'value_label' => $val,
                        'temp_idx'    => $idx,
                    ];
                }
            }
            if (!empty($arr)) {
                $gradeArrays[] = $arr;
            }
        }

        // Produto cartesiano
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
                'label' => implode(' / ', $labels),
            ];
        }

        return $combinations;
    }
}
