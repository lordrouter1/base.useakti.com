<?php
namespace Akti\Models;
use PDO;

class Subcategory {
    private $conn;
    private $table_name = "subcategories";

    public $id;
    public $category_id;
    public $name;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function readByCategoryId($categoryId) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE category_id = :category_id ORDER BY name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":category_id", $categoryId);
        $stmt->execute();
        return $stmt;
    }
    
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET name=:name, category_id=:category_id";
        $stmt = $this->conn->prepare($query);
        
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->category_id = htmlspecialchars(strip_tags($this->category_id));
        
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":category_id", $this->category_id);

        if($stmt->execute()) {
             $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function readAll() {
        $stmt = $this->conn->query("SELECT s.*, c.name as category_name 
            FROM subcategories s 
            JOIN categories c ON s.category_id = c.id 
            ORDER BY c.name ASC, s.name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function readOne($id) {
        $stmt = $this->conn->prepare("SELECT s.*, c.name as category_name FROM subcategories s JOIN categories c ON s.category_id = c.id WHERE s.id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update($id, $name, $categoryId) {
        $stmt = $this->conn->prepare("UPDATE subcategories SET name = :name, category_id = :cat WHERE id = :id");
        return $stmt->execute([
            ':name' => htmlspecialchars(strip_tags($name)),
            ':cat'  => $categoryId,
            ':id'   => $id,
        ]);
    }

    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM subcategories WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function countProducts($subId) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM products WHERE subcategory_id = :id");
        $stmt->execute([':id' => $subId]);
        return $stmt->fetchColumn();
    }
}
