<?php
namespace Akti\Models;

use Akti\Core\EventDispatcher;
use Akti\Core\Event;
use PDO;

/**
 * ProductGrade Model
 * Manages product grades (variations) — types, values, and combinations.
 */
class ProductGrade {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // ─────────────────────────────────────────────────
    // GRADE TYPES (templates reutilizáveis)
    // ─────────────────────────────────────────────────

    /**
     * List all grade types
     */
    public function getAllGradeTypes() {
        $stmt = $this->conn->prepare("SELECT * FROM product_grade_types ORDER BY name ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create a new grade type
     */
    public function createGradeType($name, $description = null, $icon = 'fas fa-th') {
        $stmt = $this->conn->prepare("INSERT INTO product_grade_types (name, description, icon) VALUES (:name, :desc, :icon)");
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':desc', $description);
        $stmt->bindParam(':icon', $icon);
        if ($stmt->execute()) {
            $newId = $this->conn->lastInsertId();
            EventDispatcher::dispatch('model.grade_type.created', new Event('model.grade_type.created', [
                'id' => $newId,
                'name' => $name,
            ]));
            return $newId;
        }
        return false;
    }

    // ─────────────────────────────────────────────────
    // PRODUCT GRADES (grades vinculadas a um produto)
    // ─────────────────────────────────────────────────

    /**
     * Get all grades for a product (with type info)
     */
    public function getProductGrades($productId) {
        $stmt = $this->conn->prepare("
            SELECT pg.*, pgt.name as type_name, pgt.icon as type_icon, pgt.description as type_description
            FROM product_grades pg
            JOIN product_grade_types pgt ON pgt.id = pg.grade_type_id
            WHERE pg.product_id = :pid AND pg.is_active = 1
            ORDER BY pg.sort_order ASC
        ");
        $stmt->bindParam(':pid', $productId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all grades for a product WITH their values
     */
    public function getProductGradesWithValues($productId) {
        $grades = $this->getProductGrades($productId);
        foreach ($grades as &$grade) {
            $grade['values'] = $this->getGradeValues($grade['id']);
        }
        return $grades;
    }

    /**
     * Add a grade to a product
     */
    public function addGradeToProduct($productId, $gradeTypeId, $sortOrder = 0) {
        $stmt = $this->conn->prepare("
            INSERT INTO product_grades (product_id, grade_type_id, sort_order)
            VALUES (:pid, :gtid, :sort)
            ON DUPLICATE KEY UPDATE is_active = 1, sort_order = VALUES(sort_order)
        ");
        $stmt->bindParam(':pid', $productId);
        $stmt->bindParam(':gtid', $gradeTypeId);
        $stmt->bindParam(':sort', $sortOrder);
        if ($stmt->execute()) {
            return $this->conn->lastInsertId() ?: $this->getProductGradeId($productId, $gradeTypeId);
        }
        return false;
    }

    /**
     * Get product_grade id
     */
    private function getProductGradeId($productId, $gradeTypeId) {
        $stmt = $this->conn->prepare("SELECT id FROM product_grades WHERE product_id = :pid AND grade_type_id = :gtid");
        $stmt->bindParam(':pid', $productId);
        $stmt->bindParam(':gtid', $gradeTypeId);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['id'] : false;
    }

    /**
     * Remove a grade from a product (soft delete)
     */
    public function removeGradeFromProduct($productGradeId) {
        $stmt = $this->conn->prepare("UPDATE product_grades SET is_active = 0 WHERE id = :id");
        $stmt->bindParam(':id', $productGradeId);
        return $stmt->execute();
    }

    /**
     * Hard delete a grade and its values
     */
    public function deleteGradeFromProduct($productGradeId) {
        $stmt = $this->conn->prepare("DELETE FROM product_grades WHERE id = :id");
        $stmt->bindParam(':id', $productGradeId);
        return $stmt->execute();
    }

    // ─────────────────────────────────────────────────
    // GRADE VALUES (valores de cada grade)
    // ─────────────────────────────────────────────────

    /**
     * Get all values for a product grade
     */
    public function getGradeValues($productGradeId) {
        $stmt = $this->conn->prepare("
            SELECT * FROM product_grade_values
            WHERE product_grade_id = :pgid AND is_active = 1
            ORDER BY sort_order ASC, id ASC
        ");
        $stmt->bindParam(':pgid', $productGradeId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Add a value to a product grade
     */
    public function addGradeValue($productGradeId, $value, $sortOrder = 0) {
        $stmt = $this->conn->prepare("
            INSERT INTO product_grade_values (product_grade_id, value, sort_order)
            VALUES (:pgid, :val, :sort)
        ");
        $stmt->bindParam(':pgid', $productGradeId);
        $stmt->bindParam(':val', $value);
        $stmt->bindParam(':sort', $sortOrder);
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    /**
     * Remove a grade value (soft delete)
     */
    public function removeGradeValue($valueId) {
        $stmt = $this->conn->prepare("UPDATE product_grade_values SET is_active = 0 WHERE id = :id");
        $stmt->bindParam(':id', $valueId);
        return $stmt->execute();
    }

    /**
     * Hard delete a grade value
     */
    public function deleteGradeValue($valueId) {
        $stmt = $this->conn->prepare("DELETE FROM product_grade_values WHERE id = :id");
        $stmt->bindParam(':id', $valueId);
        return $stmt->execute();
    }

    // ─────────────────────────────────────────────────
    // COMBINATIONS (combinações de grades)
    // ─────────────────────────────────────────────────

    /**
     * Get all combinations for a product
     */
    public function getProductCombinations($productId) {
        $stmt = $this->conn->prepare("
            SELECT * FROM product_grade_combinations
            WHERE product_id = :pid
            ORDER BY combination_label ASC
        ");
        $stmt->bindParam(':pid', $productId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get only active combinations for a product
     */
    public function getActiveProductCombinations($productId) {
        $stmt = $this->conn->prepare("
            SELECT * FROM product_grade_combinations
            WHERE product_id = :pid AND is_active = 1
            ORDER BY combination_label ASC
        ");
        $stmt->bindParam(':pid', $productId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Toggle combination active/inactive for a product
     */
    public function toggleProductCombination($combinationId, $isActive) {
        $stmt = $this->conn->prepare("UPDATE product_grade_combinations SET is_active = :active WHERE id = :id");
        return $stmt->execute([':active' => $isActive ? 1 : 0, ':id' => $combinationId]);
    }

    /**
     * Save a combination
     */
    public function saveCombination($productId, $combinationKey, $combinationLabel, $sku = null, $priceOverride = null, $stockQuantity = 0) {
        $stmt = $this->conn->prepare("
            INSERT INTO product_grade_combinations 
                (product_id, combination_key, combination_label, sku, price_override)
            VALUES 
                (:pid, :ckey, :clabel, :sku, :price)
            ON DUPLICATE KEY UPDATE
                combination_label = VALUES(combination_label),
                sku = VALUES(sku),
                price_override = VALUES(price_override),
                is_active = 1
        ");
        $stmt->bindParam(':pid', $productId);
        $stmt->bindParam(':ckey', $combinationKey);
        $stmt->bindParam(':clabel', $combinationLabel);
        $stmt->bindParam(':sku', $sku);
        $stmt->bindParam(':price', $priceOverride);
        return $stmt->execute();
    }

    /**
     * Generate all combinations from current grades/values and save them.
     * Preserves existing stock/price data when a combination already exists.
     */
    public function generateCombinations($productId) {
        $grades = $this->getProductGradesWithValues($productId);
        
        if (empty($grades)) {
            return [];
        }

        // Build arrays of [grade_id => [value_id => value_label, ...], ...]
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

        if (empty($gradeArrays)) {
            return [];
        }

        // Generate cartesian product
        $combos = $this->cartesianProduct($gradeArrays);

        // Get existing combinations to preserve data
        $existing = [];
        $existingRows = $this->getProductCombinations($productId);
        foreach ($existingRows as $row) {
            $existing[$row['combination_key']] = $row;
        }

        $results = [];
        foreach ($combos as $combo) {
            $keyParts = [];
            $labelParts = [];
            foreach ($combo as $item) {
                $keyParts[] = $item['grade_id'] . ':' . $item['value_id'];
                $labelParts[] = $item['grade_name'] . ': ' . $item['value_label'];
            }
            $key = implode('|', $keyParts);
            $label = implode(' / ', $labelParts);

            $existingData = $existing[$key] ?? null;
            $sku = $existingData ? $existingData['sku'] : null;
            $price = $existingData ? $existingData['price_override'] : null;

            $this->saveCombination($productId, $key, $label, $sku, $price);
            $results[] = ['key' => $key, 'label' => $label, 'sku' => $sku, 'price' => $price];
        }

        // Deactivate combinations that are no longer valid
        $validKeys = array_column($results, 'key');
        if (!empty($validKeys)) {
            $placeholders = implode(',', array_fill(0, count($validKeys), '?'));
            $sql = "UPDATE product_grade_combinations SET is_active = 0 WHERE product_id = ? AND combination_key NOT IN ($placeholders)";
            $stmt = $this->conn->prepare($sql);
            $params = array_merge([$productId], $validKeys);
            $stmt->execute($params);
        }

        return $results;
    }

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

    /**
     * Save all grades and values for a product from form data.
     * Expected format: 
     *   $gradesData = [
     *       ['grade_type_id' => 1, 'values' => ['P', 'M', 'G']],
     *       ['grade_type_id' => 2, 'values' => ['Branca', 'Preta']],
     *   ]
     */
    public function saveProductGrades($productId, $gradesData) {
        // Remove existing grades that are not in the new data
        $existingGrades = $this->getProductGrades($productId);
        $newTypeIds = array_column($gradesData, 'grade_type_id');

        foreach ($existingGrades as $eg) {
            if (!in_array($eg['grade_type_id'], $newTypeIds)) {
                $this->deleteGradeFromProduct($eg['id']);
            }
        }

        foreach ($gradesData as $idx => $gradeData) {
            $gradeTypeId = $gradeData['grade_type_id'];
            $values = $gradeData['values'] ?? [];

            // Handle "new" grade type
            if ($gradeTypeId === 'new' && !empty($gradeData['new_type_name'])) {
                $gradeTypeId = $this->createGradeType($gradeData['new_type_name']);
                if (!$gradeTypeId) continue;
            }

            // Add/update grade
            $productGradeId = $this->addGradeToProduct($productId, $gradeTypeId, $idx);
            if (!$productGradeId) continue;

            // Delete existing values for this grade and re-insert
            $this->conn->prepare("DELETE FROM product_grade_values WHERE product_grade_id = :pgid")
                       ->execute([':pgid' => $productGradeId]);

            // Add values
            foreach ($values as $vIdx => $value) {
                $value = trim($value);
                if ($value !== '') {
                    $this->addGradeValue($productGradeId, $value, $vIdx);
                }
            }
        }

        // Generate combinations
        $this->generateCombinations($productId);
    }

    /**
     * Save combinations data (prices, stock, SKU, active state) from form
     * Expected: $combosData = ['combination_key' => ['price' => X, 'stock' => Y, 'sku' => Z, 'is_active' => 1|0], ...]
     */
    public function saveCombinationsData($productId, $combosData) {
        foreach ($combosData as $key => $data) {
            $price = isset($data['price']) && $data['price'] !== '' ? $data['price'] : null;
            $sku = $data['sku'] ?? null;
            $isActive = isset($data['is_active']) ? (int)$data['is_active'] : 1;

            $stmt = $this->conn->prepare("
                UPDATE product_grade_combinations 
                SET price_override = :price, sku = :sku, is_active = :active
                WHERE product_id = :pid AND combination_key = :ckey
            ");
            $stmt->execute([
                ':price' => $price,
                ':sku' => $sku,
                ':active' => $isActive,
                ':pid' => $productId,
                ':ckey' => $key
            ]);
        }
    }

    /**
     * Check if a product has any grades configured
     */
    public function productHasGrades($productId) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM product_grades WHERE product_id = :pid AND is_active = 1");
        $stmt->bindParam(':pid', $productId);
        $stmt->execute();
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Get a specific combination by ID
     */
    public function getCombination($combinationId) {
        $stmt = $this->conn->prepare("SELECT * FROM product_grade_combinations WHERE id = :id");
        $stmt->bindParam(':id', $combinationId);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
