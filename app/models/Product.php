<?php
namespace Akti\Models;

use Akti\Core\EventDispatcher;
use Akti\Core\Event;
use PDO;

class Product {
    private $conn;
    private $table_name = "products";

    public $id;
    public $name;
    public $description;
    public $category_id;
    public $subcategory_id;
    public $price;
    public $stock_quantity;
    public $photo_url;

    // Campos fiscais (NF-e)
    public static $fiscalFields = [
        'fiscal_ncm', 'fiscal_cest', 'fiscal_cfop', 'fiscal_cst_icms', 'fiscal_csosn',
        'fiscal_cst_pis', 'fiscal_cst_cofins', 'fiscal_cst_ipi', 'fiscal_origem',
        'fiscal_unidade', 'fiscal_ean', 'fiscal_aliq_icms', 'fiscal_aliq_ipi',
        'fiscal_aliq_pis', 'fiscal_aliq_cofins', 'fiscal_beneficio', 'fiscal_info_adicional'
    ];

    public function __construct($db) {
        $this->conn = $db;
    }

    function readAll() {
        $query = "SELECT p.*, 
                         c.name AS category_name,
                         sc.name AS subcategory_name,
                         (SELECT image_path FROM product_images pi WHERE pi.product_id = p.id AND pi.is_main = 1 LIMIT 1) as main_image_path
                  FROM " . $this->table_name . " p
                  LEFT JOIN categories c ON p.category_id = c.id
                  LEFT JOIN subcategories sc ON p.subcategory_id = sc.id
                  ORDER BY p.name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Retorna produtos com paginação
     * @param int $page Página atual (1-based)
     * @param int $perPage Itens por página
     * @return array Lista de produtos
     */
    public function readPaginated(int $page = 1, int $perPage = 15): array
    {
        $offset = ($page - 1) * $perPage;
        $query = "SELECT p.*, 
                         c.name AS category_name,
                         sc.name AS subcategory_name,
                         (SELECT image_path FROM product_images pi WHERE pi.product_id = p.id AND pi.is_main = 1 LIMIT 1) as main_image_path
                  FROM {$this->table_name} p
                  LEFT JOIN categories c ON p.category_id = c.id
                  LEFT JOIN subcategories sc ON p.subcategory_id = sc.id
                  ORDER BY p.name ASC
                  LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna produtos paginados com filtro opcional de categoria e busca.
     * Usado no catálogo público para lazy loading.
     *
     * @param int $page Página atual (1-based)
     * @param int $perPage Itens por página
     * @param int|null $categoryId Filtrar por categoria (null = todas)
     * @param string|null $search Busca por nome (null = sem filtro)
     * @return array ['data' => [...], 'total' => int]
     */
    public function readPaginatedFiltered(int $page = 1, int $perPage = 20, ?int $categoryId = null, ?string $search = null): array
    {
        $offset = ($page - 1) * $perPage;
        $where = [];
        $params = [];

        if ($categoryId) {
            $where[] = "p.category_id = :cat_id";
            $params[':cat_id'] = $categoryId;
        }
        if ($search) {
            $where[] = "(p.name LIKE :search OR p.description LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Total
        $countQuery = "SELECT COUNT(*) FROM {$this->table_name} p {$whereClause}";
        $countStmt = $this->conn->prepare($countQuery);
        foreach ($params as $k => $v) {
            $countStmt->bindValue($k, $v);
        }
        $countStmt->execute();
        $total = (int)$countStmt->fetchColumn();

        // Dados
        $query = "SELECT p.*, 
                         c.name AS category_name,
                         sc.name AS subcategory_name,
                         (SELECT image_path FROM product_images pi WHERE pi.product_id = p.id AND pi.is_main = 1 LIMIT 1) as main_image_path
                  FROM {$this->table_name} p
                  LEFT JOIN categories c ON p.category_id = c.id
                  LEFT JOIN subcategories sc ON p.subcategory_id = sc.id
                  {$whereClause}
                  ORDER BY p.name ASC
                  LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($query);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data'  => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total' => $total,
        ];
    }

    function getImages($productId) {
        $query = "SELECT * FROM product_images WHERE product_id = :product_id ORDER BY is_main DESC, id ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':product_id', $productId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function countAll() {
        $query = "SELECT COUNT(*) FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    function create($data) {
        // Build fiscal columns dynamically
        $fiscalCols = '';
        $fiscalPlaceholders = '';
        foreach (self::$fiscalFields as $f) {
            if (isset($data[$f])) {
                $fiscalCols .= ", $f";
                $fiscalPlaceholders .= ", :$f";
            }
        }

        $query = "INSERT INTO " . $this->table_name . " 
                  (name, sku, description, category_id, subcategory_id, price, stock_quantity, use_stock_control, created_at{$fiscalCols}) 
                  VALUES (:name, :sku, :description, :category_id, :subcategory_id, :price, 0, :use_stock_control, NOW(){$fiscalPlaceholders})";
        
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':name', $data['name']);
        $stmt->bindValue(':sku', !empty($data['sku']) ? $data['sku'] : null);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':category_id', $data['category_id']);
        $stmt->bindParam(':subcategory_id', $data['subcategory_id']);
        $stmt->bindParam(':price', $data['price']);
        $useStockControl = isset($data['use_stock_control']) ? (int)$data['use_stock_control'] : 0;
        $stmt->bindParam(':use_stock_control', $useStockControl, PDO::PARAM_INT);

        foreach (self::$fiscalFields as $f) {
            if (isset($data[$f])) {
                $val = $data[$f] !== '' ? $data[$f] : null;
                $stmt->bindValue(":$f", $val);
            }
        }

        if($stmt->execute()) {
            $newId = $this->conn->lastInsertId();
            EventDispatcher::dispatch('model.product.created', new Event('model.product.created', [
                'id' => $newId,
                'name' => $data['name'],
                'category_id' => $data['category_id'],
                'price' => $data['price'],
            ]));
            return $newId;
        }
        return false;
    }

    function addImage($productId, $imagePath, $isMain = 0) {
        $query = "INSERT INTO product_images (product_id, image_path, is_main, created_at) 
                  VALUES (:product_id, :image_path, :is_main, NOW())";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':product_id', $productId);
        $stmt->bindParam(':image_path', $imagePath);
        $stmt->bindParam(':is_main', $isMain);
        return $stmt->execute();
    }

    function readOne($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    function update($data) {
        // Build fiscal SET clause dynamically
        $fiscalSet = '';
        foreach (self::$fiscalFields as $f) {
            if (array_key_exists($f, $data)) {
                $fiscalSet .= ", $f = :$f";
            }
        }

        $query = "UPDATE " . $this->table_name . " 
                  SET name = :name, 
                      sku = :sku,
                      description = :description, 
                      category_id = :category_id, 
                      subcategory_id = :subcategory_id, 
                      price = :price,
                      use_stock_control = :use_stock_control
                      {$fiscalSet}
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':name', $data['name']);
        $stmt->bindValue(':sku', !empty($data['sku']) ? $data['sku'] : null);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':category_id', $data['category_id']);
        $stmt->bindParam(':subcategory_id', $data['subcategory_id']);
        $stmt->bindParam(':price', $data['price']);
        $stmt->bindParam(':id', $data['id']);
        $useStockControl = isset($data['use_stock_control']) ? (int)$data['use_stock_control'] : 0;
        $stmt->bindParam(':use_stock_control', $useStockControl, PDO::PARAM_INT);

        foreach (self::$fiscalFields as $f) {
            if (array_key_exists($f, $data)) {
                $val = $data[$f] !== '' ? $data[$f] : null;
                $stmt->bindValue(":$f", $val);
            }
        }

        $result = $stmt->execute();
        if ($result) {
            EventDispatcher::dispatch('model.product.updated', new Event('model.product.updated', [
                'id' => $data['id'],
                'name' => $data['name'],
                'category_id' => $data['category_id'],
                'price' => $data['price'],
            ]));
        }
        return $result;
    }

    function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $result = $stmt->execute();
        if ($result) {
            EventDispatcher::dispatch('model.product.deleted', new Event('model.product.deleted', ['id' => $id]));
        }
        return $result;
    }
    
    function deleteImage($imageId) {
        $query = "DELETE FROM product_images WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $imageId);
        return $stmt->execute();
    }

    function getImage($imageId) {
        $query = "SELECT * FROM product_images WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $imageId);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    function setMainImage($productId, $imageId) {
        // Reset all to 0
        $query = "UPDATE product_images SET is_main = 0 WHERE product_id = :product_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':product_id', $productId);
        $stmt->execute();
        
        // Set new main
        $query2 = "UPDATE product_images SET is_main = 1 WHERE id = :id AND product_id = :product_id";
        $stmt2 = $this->conn->prepare($query2);
        $stmt2->bindParam(':id', $imageId);
        $stmt2->bindParam(':product_id', $productId);
        return $stmt2->execute();
    }

    /**
     * Busca as combinações de grade ativas de um produto
     */
    function getActiveCombinations($productId) {
        $query = "SELECT id, combination_label, sku, price_override, stock_quantity
                  FROM product_grade_combinations
                  WHERE product_id = :product_id AND is_active = 1
                  ORDER BY combination_label ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Verifica se o produto tem combinações de grade ativas
     */
    function hasCombinations($productId) {
        $query = "SELECT COUNT(*) FROM product_grade_combinations
                  WHERE product_id = :product_id AND is_active = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Get products by category ID (with main image)
     */
    function getByCategory($categoryId) {
        $categoryId = (int)$categoryId;
        $query = "SELECT p.id, p.name, p.sku, p.subcategory_id,
                         sc.name AS subcategory_name,
                         (SELECT image_path FROM product_images pi WHERE pi.product_id = p.id AND pi.is_main = 1 LIMIT 1) as main_image_path,
                         (SELECT COUNT(*) FROM product_grades pg WHERE pg.product_id = p.id AND pg.is_active = 1) as grade_count,
                         (SELECT COUNT(*) FROM product_sectors ps WHERE ps.product_id = p.id) as sector_count
                  FROM {$this->table_name} p
                  LEFT JOIN subcategories sc ON p.subcategory_id = sc.id
                  WHERE p.category_id = :category_id
                  ORDER BY p.name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get products by subcategory ID (with main image)
     */
    function getBySubcategory($subcategoryId) {
        $subcategoryId = (int)$subcategoryId;
        $query = "SELECT p.id, p.name, p.sku, p.category_id,
                         c.name AS category_name,
                         (SELECT image_path FROM product_images pi WHERE pi.product_id = p.id AND pi.is_main = 1 LIMIT 1) as main_image_path,
                         (SELECT COUNT(*) FROM product_grades pg WHERE pg.product_id = p.id AND pg.is_active = 1) as grade_count,
                         (SELECT COUNT(*) FROM product_sectors ps WHERE ps.product_id = p.id) as sector_count
                  FROM {$this->table_name} p
                  LEFT JOIN categories c ON p.category_id = c.id
                  WHERE p.subcategory_id = :subcategory_id
                  ORDER BY p.name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':subcategory_id', $subcategoryId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
