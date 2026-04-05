<?php
namespace Akti\Models;

use Akti\Core\EventDispatcher;
use Akti\Core\Event;
use PDO;

class Subcategory {
    private $conn;
    private $table_name = "subcategories";

    public $id;
    public $category_id;
    public $name;

    public function __construct(\PDO $db) {
        $this->conn = $db;
    }

    public function readByCategoryId($categoryId) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE category_id = :category_id ORDER BY name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":category_id", $categoryId);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
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
            EventDispatcher::dispatch('model.subcategory.created', new Event('model.subcategory.created', [
                'id' => $this->id,
                'name' => $this->name,
                'category_id' => $this->category_id,
            ]));
            return true;
        }
        return false;
    }

    public function readAll() {
        $stmt = $this->conn->prepare("SELECT s.*, c.name as category_name 
            FROM subcategories s 
            JOIN categories c ON s.category_id = c.id 
            ORDER BY c.name ASC, s.name ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna a quantidade total de subcategorias.
     * @return int
     */
    public function countAll(): int
    {
        $query = "SELECT COUNT(*) FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /**
     * Retorna subcategorias paginadas com nome da categoria.
     *
     * @param int $page   Página atual (1-based)
     * @param int $perPage Registros por página
     * @return array
     */
    public function readPaginated(int $page = 1, int $perPage = 15): array
    {
        $offset = ($page - 1) * $perPage;
        $query = "SELECT s.*, c.name as category_name
                  FROM {$this->table_name} s
                  JOIN categories c ON s.category_id = c.id
                  ORDER BY c.name ASC, s.name ASC
                  LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function readOne($id) {
        $stmt = $this->conn->prepare("SELECT s.*, c.name as category_name FROM subcategories s JOIN categories c ON s.category_id = c.id WHERE s.id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update($id, $name, $categoryId) {
        $stmt = $this->conn->prepare("UPDATE subcategories SET name = :name, category_id = :cat WHERE id = :id");
        $result = $stmt->execute([
            ':name' => htmlspecialchars(strip_tags($name)),
            ':cat'  => $categoryId,
            ':id'   => $id,
        ]);
        if ($result) {
            EventDispatcher::dispatch('model.subcategory.updated', new Event('model.subcategory.updated', [
                'id' => $id,
                'name' => $name,
                'category_id' => $categoryId,
            ]));
        }
        return $result;
    }

    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM subcategories WHERE id = :id");
        $result = $stmt->execute([':id' => $id]);
        if ($result) {
            EventDispatcher::dispatch('model.subcategory.deleted', new Event('model.subcategory.deleted', ['id' => $id]));
        }
        return $result;
    }

    public function countProducts($subId) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM products WHERE subcategory_id = :id");
        $stmt->execute([':id' => $subId]);
        return $stmt->fetchColumn();
    }
}
