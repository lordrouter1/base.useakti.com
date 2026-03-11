<?php
namespace Akti\Models;

use Akti\Core\EventDispatcher;
use Akti\Core\Event;
use PDO;

class Customer {
    private $conn;
    private $table_name = "customers";

    public $id;
    public $name;
    public $email;
    public $phone;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function readAll() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readOne($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $query = "INSERT INTO customers (name, email, phone, document, address, photo, price_table_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            $data['name'],
            $data['email'],
            $data['phone'],
            $data['document'],
            $data['address'],
            $data['photo'],
            !empty($data['price_table_id']) ? $data['price_table_id'] : null
        ]);
        $newId = $this->conn->lastInsertId();
        EventDispatcher::dispatch('model.customer.created', new Event('model.customer.created', [
            'id' => $newId,
            'name' => $data['name'],
            'email' => $data['email'],
        ]));
        return $newId;
    }

    public function update($data) {
        $query = "UPDATE " . $this->table_name . " 
                  SET name = ?, email = ?, phone = ?, document = ?, address = ?, price_table_id = ?";
        $params = [$data['name'], $data['email'], $data['phone'], $data['document'], $data['address'], !empty($data['price_table_id']) ? $data['price_table_id'] : null];
        
        if (isset($data['photo']) && $data['photo']) {
            $query .= ", photo = ?";
            $params[] = $data['photo'];
        }
        
        $query .= " WHERE id = ?";
        $params[] = $data['id'];
        
        $stmt = $this->conn->prepare($query);
        $result = $stmt->execute($params);
        if ($result) {
            EventDispatcher::dispatch('model.customer.updated', new Event('model.customer.updated', [
                'id' => $data['id'],
                'name' => $data['name'],
                'email' => $data['email'],
            ]));
        }
        return $result;
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $result = $stmt->execute();
        if ($result) {
            EventDispatcher::dispatch('model.customer.deleted', new Event('model.customer.deleted', ['id' => $id]));
        }
        return $result;
    }

    /**
     * Total de clientes
     */
    public function countAll() {
        $query = "SELECT COUNT(*) FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchColumn();
    }
}
