<?php
namespace Akti\Models;

use Akti\Core\EventDispatcher;
use Akti\Core\Event;
use PDO;

/**
 * Model: CategoryGrade
 * Responsável por gerenciar grades (variações) de categorias e subcategorias.
 * Entradas: Conexão PDO ($db), parâmetros das funções.
 * Saídas: Arrays de dados, booleanos, IDs inseridos.
 * Eventos: 'model.category_grade.saved' (ao salvar grades de categoria)
 * Não deve conter HTML, echo, print ou acesso direto a $_POST/$_GET.
 */
class CategoryGrade {
    private $conn;

    /**
     * Construtor da classe CategoryGrade.
     *
     * @param \PDO $db Conexão PDO com o banco de dados
     */
    public function __construct(\PDO $db) {
        $this->conn = $db;
    }

    // ═════════════════════════════════════════════════════
    //  CATEGORY GRADES
    // ═════════════════════════════════════════════════════

    /**
     * Retorna todas as grades de uma categoria (com info de tipo)
     * @param int $categoryId ID da categoria
     * @return array Array de grades (fetchAll)
     */
    public function getCategoryGrades($categoryId) {
        $stmt = $this->conn->prepare("
            SELECT cg.*, pgt.name as type_name, pgt.icon as type_icon, pgt.description as type_description
            FROM category_grades cg
            JOIN product_grade_types pgt ON pgt.id = cg.grade_type_id
            WHERE cg.category_id = :cid AND cg.is_active = 1
            ORDER BY cg.sort_order ASC
        ");
        $stmt->bindParam(':cid', $categoryId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna todas as grades de uma categoria com seus valores
     * @param int $categoryId ID da categoria
     * @return array Array de grades, cada uma com 'values'
     */
    public function getCategoryGradesWithValues($categoryId) {
        $grades = $this->getCategoryGrades($categoryId);
        foreach ($grades as &$grade) {
            $grade['values'] = $this->getCategoryGradeValues($grade['id']);
        }
        return $grades;
    }

    /**
     * Retorna todos os valores de uma grade de categoria
     * @param int $categoryGradeId ID da grade de categoria
     * @return array Array de valores (fetchAll)
     */
    public function getCategoryGradeValues($categoryGradeId) {
        $stmt = $this->conn->prepare("
            SELECT * FROM category_grade_values
            WHERE category_grade_id = :cgid AND is_active = 1
            ORDER BY sort_order ASC, id ASC
        ");
        $stmt->bindParam(':cgid', $categoryGradeId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Adiciona uma grade a uma categoria
     * @param int $categoryId ID da categoria
     * @param int $gradeTypeId ID do tipo de grade
     * @param int $sortOrder Ordem de exibição (default: 0)
     * @return int|false ID da grade inserida ou false em caso de erro
     */
    public function addGradeToCategory($categoryId, $gradeTypeId, $sortOrder = 0) {
        $stmt = $this->conn->prepare("
            INSERT INTO category_grades (category_id, grade_type_id, sort_order)
            VALUES (:cid, :gtid, :sort)
            ON DUPLICATE KEY UPDATE is_active = 1, sort_order = VALUES(sort_order)
        ");
        $stmt->bindParam(':cid', $categoryId);
        $stmt->bindParam(':gtid', $gradeTypeId);
        $stmt->bindParam(':sort', $sortOrder);
        if ($stmt->execute()) {
            return $this->conn->lastInsertId() ?: $this->getCategoryGradeId($categoryId, $gradeTypeId);
        }
        return false;
    }

    /**
     * Obtém dados específicos.
     *
     * @param mixed $categoryId Category id
     * @param mixed $gradeTypeId Grade type id
     */
    private function getCategoryGradeId($categoryId, $gradeTypeId) {
        $stmt = $this->conn->prepare("SELECT id FROM category_grades WHERE category_id = :cid AND grade_type_id = :gtid");
        $stmt->bindParam(':cid', $categoryId);
        $stmt->bindParam(':gtid', $gradeTypeId);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['id'] : false;
    }

    /**
     * Adiciona um valor a uma grade de categoria
     * @param int $categoryGradeId ID da grade de categoria
     * @param string $value Valor a ser inserido
     * @param int $sortOrder Ordem de exibição (default: 0)
     * @return int|false ID do valor inserido ou false em caso de erro
     */
    public function addCategoryGradeValue($categoryGradeId, $value, $sortOrder = 0) {
        $stmt = $this->conn->prepare("
            INSERT INTO category_grade_values (category_grade_id, value, sort_order)
            VALUES (:cgid, :val, :sort)
        ");
        $stmt->bindParam(':cgid', $categoryGradeId);
        $stmt->bindParam(':val', $value);
        $stmt->bindParam(':sort', $sortOrder);
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    /**
     * Salva todas as grades e valores de uma categoria a partir de dados de formulário
     * @param int $categoryId ID da categoria
     * @param array $gradesData Dados das grades e valores
     * @return void
     * Evento disparado: 'model.category_grade.saved' com ['category_id', 'grades_count']
     */
    public function saveCategoryGrades($categoryId, $gradesData) {
        // Remove existing grades not in new data
        $existingGrades = $this->getCategoryGrades($categoryId);
        $newTypeIds = array_column($gradesData, 'grade_type_id');

        foreach ($existingGrades as $eg) {
            if (!in_array($eg['grade_type_id'], $newTypeIds)) {
                $this->conn->prepare("DELETE FROM category_grades WHERE id = :id")->execute([':id' => $eg['id']]);
            }
        }

        foreach ($gradesData as $idx => $gradeData) {
            $gradeTypeId = $gradeData['grade_type_id'];
            $values = $gradeData['values'] ?? [];

            // Handle "new" grade type
            if ($gradeTypeId === 'new' && !empty($gradeData['new_type_name'])) {
                $stmt = $this->conn->prepare("INSERT INTO product_grade_types (name) VALUES (:name)");
                $stmt->execute([':name' => $gradeData['new_type_name']]);
                $gradeTypeId = $this->conn->lastInsertId();
                if (!$gradeTypeId) continue;
            }

            $categoryGradeId = $this->addGradeToCategory($categoryId, $gradeTypeId, $idx);
            if (!$categoryGradeId) continue;

            // Delete existing values and re-insert
            $this->conn->prepare("DELETE FROM category_grade_values WHERE category_grade_id = :cgid")
                       ->execute([':cgid' => $categoryGradeId]);

            foreach ($values as $vIdx => $value) {
                $value = trim($value);
                if ($value !== '') {
                    $this->addCategoryGradeValue($categoryGradeId, $value, $vIdx);
                }
            }
        }

        // Generate combinations
        $this->generateCategoryCombinations($categoryId);

        EventDispatcher::dispatch('model.category_grade.saved', new Event('model.category_grade.saved', [
            'category_id' => $categoryId,
            'grades_count' => count($gradesData),
        ]));
    }
    /**
     * Gera conteúdo ou dados.
     *
     * @param mixed $categoryId Category id
     */
    public function generateCategoryCombinations($categoryId) {
        $grades = $this->getCategoryGradesWithValues($categoryId);

        if (empty($grades)) {
            // Clear existing combinations
            $this->conn->prepare("DELETE FROM category_grade_combinations WHERE category_id = :cid")
                       ->execute([':cid' => $categoryId]);
            return [];
        }

        $gradeArrays = [];
        foreach ($grades as $grade) {
            if (empty($grade['values'])) continue;
            $arr = [];
            foreach ($grade['values'] as $val) {
                $arr[] = [
                    'grade_id' => $grade['id'],
                    'grade_name' => $grade['type_name'],
                    'value_id' => $val['id'],
                    'value_label' => $val['value']
                ];
            }
            $gradeArrays[] = $arr;
        }

        if (empty($gradeArrays)) return [];

        $combos = $this->cartesianProduct($gradeArrays);

        // Get existing combinations to preserve is_active state
        $existing = [];
        $stmt = $this->conn->prepare("SELECT * FROM category_grade_combinations WHERE category_id = :cid");
        $stmt->execute([':cid' => $categoryId]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $existing[$row['combination_key']] = $row;
        }

        $results = [];
        $validKeys = [];
        foreach ($combos as $combo) {
            $keyParts = [];
            $labelParts = [];
            foreach ($combo as $item) {
                $keyParts[] = $item['grade_id'] . ':' . $item['value_id'];
                $labelParts[] = $item['grade_name'] . ': ' . $item['value_label'];
            }
            $key = implode('|', $keyParts);
            $label = implode(' / ', $labelParts);
            $validKeys[] = $key;

            $isActive = isset($existing[$key]) ? $existing[$key]['is_active'] : 1;

            $stmt = $this->conn->prepare("
                INSERT INTO category_grade_combinations (category_id, combination_key, combination_label, is_active)
                VALUES (:cid, :ckey, :clabel, :active)
                ON DUPLICATE KEY UPDATE combination_label = VALUES(combination_label)
            ");
            $stmt->execute([
                ':cid' => $categoryId,
                ':ckey' => $key,
                ':clabel' => $label,
                ':active' => $isActive
            ]);

            $results[] = ['key' => $key, 'label' => $label, 'is_active' => $isActive];
        }

        // Remove obsolete combinations
        if (!empty($validKeys)) {
            $placeholders = implode(',', array_fill(0, count($validKeys), '?'));
            $sql = "DELETE FROM category_grade_combinations WHERE category_id = ? AND combination_key NOT IN ($placeholders)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(array_merge([$categoryId], $validKeys));
        }

        return $results;
    }

    /**
     * Retorna todas as combinações de grades de uma categoria
     * @param int $categoryId ID da categoria
     * @return array Array de combinações (fetchAll)
     */
    public function getCategoryCombinations($categoryId) {
        $stmt = $this->conn->prepare("
            SELECT * FROM category_grade_combinations
            WHERE category_id = :cid
            ORDER BY combination_label ASC
        ");
        $stmt->bindParam(':cid', $categoryId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ativa ou desativa uma combinação de grades de categoria
     * @param int $combinationId ID da combinação
     * @param bool $isActive Ativo ou não
     * @return bool Retorna true se atualizado com sucesso
     */
    public function toggleCategoryCombination($combinationId, $isActive) {
        $stmt = $this->conn->prepare("UPDATE category_grade_combinations SET is_active = :active WHERE id = :id");
        return $stmt->execute([':active' => $isActive ? 1 : 0, ':id' => $combinationId]);
    }

    /**
     * Verifica se uma categoria possui grades
     * @param int $categoryId ID da categoria
     * @return bool Retorna true se possui grades
     */
    public function categoryHasGrades($categoryId) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM category_grades WHERE category_id = :cid AND is_active = 1");
        $stmt->execute([':cid' => $categoryId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    // ═════════════════════════════════════════════════════
    //  SUBCATEGORY GRADES
    // ═════════════════════════════════════════════════════

    /**
     * Retorna todas as grades de uma subcategoria (com info de tipo)
     * @param int $subcategoryId ID da subcategoria
     * @return array Array de grades (fetchAll)
     */
    public function getSubcategoryGrades($subcategoryId) {
        $stmt = $this->conn->prepare("
            SELECT sg.*, pgt.name as type_name, pgt.icon as type_icon, pgt.description as type_description
            FROM subcategory_grades sg
            JOIN product_grade_types pgt ON pgt.id = sg.grade_type_id
            WHERE sg.subcategory_id = :sid AND sg.is_active = 1
            ORDER BY sg.sort_order ASC
        ");
        $stmt->bindParam(':sid', $subcategoryId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna todas as grades de uma subcategoria com seus valores
     * @param int $subcategoryId ID da subcategoria
     * @return array Array de grades, cada uma com 'values'
     */
    public function getSubcategoryGradesWithValues($subcategoryId) {
        $grades = $this->getSubcategoryGrades($subcategoryId);
        foreach ($grades as &$grade) {
            $grade['values'] = $this->getSubcategoryGradeValues($grade['id']);
        }
        return $grades;
    }

    /**
     * Retorna todos os valores de uma grade de subcategoria
     * @param int $subcategoryGradeId ID da grade de subcategoria
     * @return array Array de valores (fetchAll)
     */
    public function getSubcategoryGradeValues($subcategoryGradeId) {
        $stmt = $this->conn->prepare("
            SELECT * FROM subcategory_grade_values
            WHERE subcategory_grade_id = :sgid AND is_active = 1
            ORDER BY sort_order ASC, id ASC
        ");
        $stmt->bindParam(':sgid', $subcategoryGradeId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Adiciona uma grade a uma subcategoria
     * @param int $subcategoryId ID da subcategoria
     * @param int $gradeTypeId ID do tipo de grade
     * @param int $sortOrder Ordem de exibição (default: 0)
     * @return int|false ID da grade inserida ou false em caso de erro
     */
    public function addGradeToSubcategory($subcategoryId, $gradeTypeId, $sortOrder = 0) {
        $stmt = $this->conn->prepare("
            INSERT INTO subcategory_grades (subcategory_id, grade_type_id, sort_order)
            VALUES (:sid, :gtid, :sort)
            ON DUPLICATE KEY UPDATE is_active = 1, sort_order = VALUES(sort_order)
        ");
        $stmt->bindParam(':sid', $subcategoryId);
        $stmt->bindParam(':gtid', $gradeTypeId);
        $stmt->bindParam(':sort', $sortOrder);
        if ($stmt->execute()) {
            return $this->conn->lastInsertId() ?: $this->getSubcategoryGradeId($subcategoryId, $gradeTypeId);
        }
        return false;
    }

    /**
     * Retorna o ID da grade de subcategoria
     * @param int $subcategoryId ID da subcategoria
     * @param int $gradeTypeId ID do tipo de grade
     * @return int|false ID encontrado ou false se não existir
     */
    private function getSubcategoryGradeId($subcategoryId, $gradeTypeId) {
        $stmt = $this->conn->prepare("SELECT id FROM subcategory_grades WHERE subcategory_id = :sid AND grade_type_id = :gtid");
        $stmt->bindParam(':sid', $subcategoryId);
        $stmt->bindParam(':gtid', $gradeTypeId);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['id'] : false;
    }

    /**
     * Adiciona um valor a uma grade de subcategoria
     * @param int $subcategoryGradeId ID da grade de subcategoria
     * @param string $value Valor a ser inserido
     * @param int $sortOrder Ordem de exibição (default: 0)
     * @return int|false ID do valor inserido ou false em caso de erro
     */
    public function addSubcategoryGradeValue($subcategoryGradeId, $value, $sortOrder = 0) {
        $stmt = $this->conn->prepare("
            INSERT INTO subcategory_grade_values (subcategory_grade_id, value, sort_order)
            VALUES (:sgid, :val, :sort)
        ");
        $stmt->bindParam(':sgid', $subcategoryGradeId);
        $stmt->bindParam(':val', $value);
        $stmt->bindParam(':sort', $sortOrder);
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    /**
     * Salva todas as grades e valores de uma subcategoria a partir de dados de formulário
     * @param int $subcategoryId ID da subcategoria
     * @param array $gradesData Dados das grades e valores
     * @return void
     * Evento disparado: 'model.subcategory_grade.saved' com ['subcategory_id', 'grades_count']
     */
    public function saveSubcategoryGrades($subcategoryId, $gradesData) {
        $existingGrades = $this->getSubcategoryGrades($subcategoryId);
        $newTypeIds = array_column($gradesData, 'grade_type_id');

        foreach ($existingGrades as $eg) {
            if (!in_array($eg['grade_type_id'], $newTypeIds)) {
                $this->conn->prepare("DELETE FROM subcategory_grades WHERE id = :id")->execute([':id' => $eg['id']]);
            }
        }

        foreach ($gradesData as $idx => $gradeData) {
            $gradeTypeId = $gradeData['grade_type_id'];
            $values = $gradeData['values'] ?? [];

            if ($gradeTypeId === 'new' && !empty($gradeData['new_type_name'])) {
                $stmt = $this->conn->prepare("INSERT INTO product_grade_types (name) VALUES (:name)");
                $stmt->execute([':name' => $gradeData['new_type_name']]);
                $gradeTypeId = $this->conn->lastInsertId();
                if (!$gradeTypeId) continue;
            }

            $subcategoryGradeId = $this->addGradeToSubcategory($subcategoryId, $gradeTypeId, $idx);
            if (!$subcategoryGradeId) continue;

            $this->conn->prepare("DELETE FROM subcategory_grade_values WHERE subcategory_grade_id = :sgid")
                       ->execute([':sgid' => $subcategoryGradeId]);

            foreach ($values as $vIdx => $value) {
                $value = trim($value);
                if ($value !== '') {
                    $this->addSubcategoryGradeValue($subcategoryGradeId, $value, $vIdx);
                }
            }
        }

        $this->generateSubcategoryCombinations($subcategoryId);

        EventDispatcher::dispatch('model.subcategory_grade.saved', new Event('model.subcategory_grade.saved', [
            'subcategory_id' => $subcategoryId,
            'grades_count' => count($gradesData),
        ]));
    }
    /**
     * Gera todas as combinações de grades de uma subcategoria e salva no banco
     * @param int $subcategoryId ID da subcategoria
     * @return array Array de combinações geradas
     */
    public function generateSubcategoryCombinations($subcategoryId) {
        $grades = $this->getSubcategoryGradesWithValues($subcategoryId);

        if (empty($grades)) {
            $this->conn->prepare("DELETE FROM subcategory_grade_combinations WHERE subcategory_id = :sid")
                       ->execute([':sid' => $subcategoryId]);
            return [];
        }

        $gradeArrays = [];
        foreach ($grades as $grade) {
            if (empty($grade['values'])) continue;
            $arr = [];
            foreach ($grade['values'] as $val) {
                $arr[] = [
                    'grade_id' => $grade['id'],
                    'grade_name' => $grade['type_name'],
                    'value_id' => $val['id'],
                    'value_label' => $val['value']
                ];
            }
            $gradeArrays[] = $arr;
        }

        if (empty($gradeArrays)) return [];

        $combos = $this->cartesianProduct($gradeArrays);

        $existing = [];
        $stmt = $this->conn->prepare("SELECT * FROM subcategory_grade_combinations WHERE subcategory_id = :sid");
        $stmt->execute([':sid' => $subcategoryId]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $existing[$row['combination_key']] = $row;
        }

        $results = [];
        $validKeys = [];
        foreach ($combos as $combo) {
            $keyParts = [];
            $labelParts = [];
            foreach ($combo as $item) {
                $keyParts[] = $item['grade_id'] . ':' . $item['value_id'];
                $labelParts[] = $item['grade_name'] . ': ' . $item['value_label'];
            }
            $key = implode('|', $keyParts);
            $label = implode(' / ', $labelParts);
            $validKeys[] = $key;

            $isActive = isset($existing[$key]) ? $existing[$key]['is_active'] : 1;

            $stmt = $this->conn->prepare("
                INSERT INTO subcategory_grade_combinations (subcategory_id, combination_key, combination_label, is_active)
                VALUES (:sid, :ckey, :clabel, :active)
                ON DUPLICATE KEY UPDATE combination_label = VALUES(combination_label)
            ");
            $stmt->execute([
                ':sid' => $subcategoryId,
                ':ckey' => $key,
                ':clabel' => $label,
                ':active' => $isActive
            ]);

            $results[] = ['key' => $key, 'label' => $label, 'is_active' => $isActive];
        }

        if (!empty($validKeys)) {
            $placeholders = implode(',', array_fill(0, count($validKeys), '?'));
            $sql = "DELETE FROM subcategory_grade_combinations WHERE subcategory_id = ? AND combination_key NOT IN ($placeholders)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(array_merge([$subcategoryId], $validKeys));
        }

        return $results;
    }

    /**
     * Retorna todas as combinações de grades de uma subcategoria
     * @param int $subcategoryId ID da subcategoria
     * @return array Array de combinações (fetchAll)
     */
    public function getSubcategoryCombinations($subcategoryId) {
        $stmt = $this->conn->prepare("
            SELECT * FROM subcategory_grade_combinations
            WHERE subcategory_id = :sid
            ORDER BY combination_label ASC
        ");
        $stmt->bindParam(':sid', $subcategoryId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ativa ou desativa uma combinação de grades de subcategoria
     * @param int $combinationId ID da combinação
     * @param bool $isActive Ativo ou não
     * @return bool Retorna true se atualizado com sucesso
     */
    public function toggleSubcategoryCombination($combinationId, $isActive) {
        $stmt = $this->conn->prepare("UPDATE subcategory_grade_combinations SET is_active = :active WHERE id = :id");
        return $stmt->execute([':active' => $isActive ? 1 : 0, ':id' => $combinationId]);
    }

    /**
     * Verifica se uma subcategoria possui grades
     * @param int $subcategoryId ID da subcategoria
     * @return bool Retorna true se possui grades
     */
    public function subcategoryHasGrades($subcategoryId) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM subcategory_grades WHERE subcategory_id = :sid AND is_active = 1");
        $stmt->execute([':sid' => $subcategoryId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    // ═════════════════════════════════════════════════════
    //  INHERITANCE LOGIC
    // ═════════════════════════════════════════════════════

    /**
     * Retorna grades herdadas para um produto com base em subcategoria ou categoria.
     * Prioridade: subcategoria > categoria.
     * Formato igual ao ProductGrade->getProductGradesWithValues() para renderização.
     * @param int|null $subcategoryId ID da subcategoria
     * @param int|null $categoryId ID da categoria
     * @return array ['grades' => [...], 'source' => 'subcategory'|'category'|null, 'inactive_keys' => [...]]
     */
    public function getInheritedGrades($subcategoryId = null, $categoryId = null) {
        // Try subcategory first
        if ($subcategoryId) {
            $grades = $this->getSubcategoryGradesWithValues($subcategoryId);
            if (!empty($grades)) {
                $combinations = $this->getSubcategoryCombinations($subcategoryId);
                $inactiveKeys = [];
                foreach ($combinations as $combo) {
                    if (!$combo['is_active']) {
                        $inactiveKeys[] = $combo['combination_key'];
                    }
                }
                return [
                    'grades' => $grades,
                    'source' => 'subcategory',
                    'source_id' => $subcategoryId,
                    'inactive_keys' => $inactiveKeys
                ];
            }
        }

        // Fall back to category
        if ($categoryId) {
            $grades = $this->getCategoryGradesWithValues($categoryId);
            if (!empty($grades)) {
                $combinations = $this->getCategoryCombinations($categoryId);
                $inactiveKeys = [];
                foreach ($combinations as $combo) {
                    if (!$combo['is_active']) {
                        $inactiveKeys[] = $combo['combination_key'];
                    }
                }
                return [
                    'grades' => $grades,
                    'source' => 'category',
                    'source_id' => $categoryId,
                    'inactive_keys' => $inactiveKeys
                ];
            }
        }

        return ['grades' => [], 'source' => null, 'source_id' => null, 'inactive_keys' => []];
    }

    /**
     * Converte grades herdadas para o formato esperado por saveProductGrades().
     * Permite que um produto "adote" grades da categoria/subcategoria.
     * @param array $inheritedGrades Saída de getInheritedGrades()['grades']
     * @return array [['grade_type_id' => X, 'values' => ['P','M','G']], ...]
     */
    public function convertInheritedToProductFormat($inheritedGrades) {
        $result = [];
        foreach ($inheritedGrades as $grade) {
            $values = [];
            foreach ($grade['values'] as $val) {
                $values[] = $val['value'];
            }
            $result[] = [
                'grade_type_id' => $grade['grade_type_id'],
                'values' => $values
            ];
        }
        return $result;
    }

    // ═════════════════════════════════════════════════════
    //  UTILITY
    // ═════════════════════════════════════════════════════

    /**
     * Produto cartesiano de múltiplos arrays
     * @param array $arrays Arrays de entrada
     * @return array Produto cartesiano
     */
    private function cartesianProduct($arrays) {
        $result = [[]];
        foreach ($arrays as $array) {
            $new = [];
            foreach ($result as $combo) {
                foreach ($array as $item) {
                    $new[] = array_merge($combo, [$item]);
                }
            }
            $result = $new;
        }
        return $result;
    }
}
