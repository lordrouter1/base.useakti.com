<?php
namespace Akti\Models;
use PDO;

/**
 * CategoryGrade Model
 * Manages grades (variations) for categories and subcategories.
 * Supports inheritance: subcategory grades > category grades > product.
 */
class CategoryGrade {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // ═════════════════════════════════════════════════════
    //  CATEGORY GRADES
    // ═════════════════════════════════════════════════════

    /**
     * Get all grades for a category (with type info)
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
     * Get all grades for a category WITH their values
     */
    public function getCategoryGradesWithValues($categoryId) {
        $grades = $this->getCategoryGrades($categoryId);
        foreach ($grades as &$grade) {
            $grade['values'] = $this->getCategoryGradeValues($grade['id']);
        }
        return $grades;
    }

    /**
     * Get values for a category grade
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
     * Add a grade to a category
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

    private function getCategoryGradeId($categoryId, $gradeTypeId) {
        $stmt = $this->conn->prepare("SELECT id FROM category_grades WHERE category_id = :cid AND grade_type_id = :gtid");
        $stmt->bindParam(':cid', $categoryId);
        $stmt->bindParam(':gtid', $gradeTypeId);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['id'] : false;
    }

    /**
     * Add a value to a category grade
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
     * Save all grades and values for a category from form data.
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
    }

    /**
     * Generate combinations for a category
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
     * Get all combinations for a category
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
     * Toggle combination active/inactive for category
     */
    public function toggleCategoryCombination($combinationId, $isActive) {
        $stmt = $this->conn->prepare("UPDATE category_grade_combinations SET is_active = :active WHERE id = :id");
        return $stmt->execute([':active' => $isActive ? 1 : 0, ':id' => $combinationId]);
    }

    /**
     * Check if a category has grades
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
     * Get all grades for a subcategory (with type info)
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
     * Get all grades for a subcategory WITH their values
     */
    public function getSubcategoryGradesWithValues($subcategoryId) {
        $grades = $this->getSubcategoryGrades($subcategoryId);
        foreach ($grades as &$grade) {
            $grade['values'] = $this->getSubcategoryGradeValues($grade['id']);
        }
        return $grades;
    }

    /**
     * Get values for a subcategory grade
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
     * Add a grade to a subcategory
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

    private function getSubcategoryGradeId($subcategoryId, $gradeTypeId) {
        $stmt = $this->conn->prepare("SELECT id FROM subcategory_grades WHERE subcategory_id = :sid AND grade_type_id = :gtid");
        $stmt->bindParam(':sid', $subcategoryId);
        $stmt->bindParam(':gtid', $gradeTypeId);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['id'] : false;
    }

    /**
     * Add a value to a subcategory grade
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
     * Save all grades and values for a subcategory from form data.
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
    }

    /**
     * Generate combinations for a subcategory
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
     * Get all combinations for a subcategory
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
     * Toggle combination active/inactive for subcategory
     */
    public function toggleSubcategoryCombination($combinationId, $isActive) {
        $stmt = $this->conn->prepare("UPDATE subcategory_grade_combinations SET is_active = :active WHERE id = :id");
        return $stmt->execute([':active' => $isActive ? 1 : 0, ':id' => $combinationId]);
    }

    /**
     * Check if a subcategory has grades
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
     * Get inherited grades for a product based on its subcategory/category.
     * Priority: subcategory > category.
     * Returns an array in the same format as ProductGrade->getProductGradesWithValues()
     * so the UI can render them identically.
     * 
     * @param int|null $subcategoryId
     * @param int|null $categoryId
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
     * Convert inherited grades to the format expected by saveProductGrades().
     * This allows a product to "adopt" grades from its category/subcategory.
     * 
     * @param array $inheritedGrades (output of getInheritedGrades()['grades'])
     * @return array Format: [['grade_type_id' => X, 'values' => ['P','M','G']], ...]
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
     * Cartesian product of multiple arrays
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
