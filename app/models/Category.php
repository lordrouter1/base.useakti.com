<?php
namespace Akti\Models;
use PDO;

class Category {
    private $conn;
    private $table_name = "categories";

    public $id;
    public $name;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function readAll() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET name=:name";
        $stmt = $this->conn->prepare($query);
        
        $this->name = htmlspecialchars(strip_tags($this->name));
        $stmt->bindParam(":name", $this->name);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function getSubcategories($categoryId) {
        $query = "SELECT * FROM subcategories WHERE category_id = :category_id ORDER BY name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':category_id', $categoryId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCategory($categoryId) {
        $query = "SELECT * FROM categories WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $categoryId);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update($id, $name) {
        $stmt = $this->conn->prepare("UPDATE categories SET name = :name WHERE id = :id");
        return $stmt->execute([':name' => htmlspecialchars(strip_tags($name)), ':id' => $id]);
    }

    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM categories WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function countProducts($categoryId) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM products WHERE category_id = :id");
        $stmt->execute([':id' => $categoryId]);
        return $stmt->fetchColumn();
    }

    public function readAllWithCount() {
        $stmt = $this->conn->query("SELECT c.*, 
            (SELECT COUNT(*) FROM products WHERE category_id = c.id) as product_count,
            (SELECT COUNT(*) FROM subcategories WHERE category_id = c.id) as sub_count
            FROM categories c ORDER BY c.name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
