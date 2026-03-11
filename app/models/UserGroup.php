<?php
namespace Akti\Models;

use Akti\Core\EventDispatcher;
use Akti\Core\Event;
use PDO;

class UserGroup {
    private $conn;
    private $table_name = "user_groups";
    
    public $id;
    public $name;
    public $description;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function readAll() {
        $query = "SELECT DISTINCT * FROM " . $this->table_name . " ORDER BY id ASC"; 
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
    
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET name = :name, description = :description";
        $stmt = $this->conn->prepare($query);
        
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':description', $this->description);
        
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            EventDispatcher::dispatch('model.user_group.created', new Event('model.user_group.created', [
                'id' => $this->id,
                'name' => $this->name,
            ]));
            return true;
        }
        return false;
    }

    public function readOne($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->description = $row['description'];
            return $row;
        }
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET name = :name, description = :description 
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':id', $this->id);
        
        $result = $stmt->execute();
        if ($result) {
            EventDispatcher::dispatch('model.user_group.updated', new Event('model.user_group.updated', [
                'id' => $this->id,
                'name' => $this->name,
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
            EventDispatcher::dispatch('model.user_group.deleted', new Event('model.user_group.deleted', ['id' => $id]));
        }
        return $result;
    }
    
    public function addPermission($groupId, $pageName) {
        $query = "INSERT INTO group_permissions (group_id, page_name) VALUES (:group_id, :page_name)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':group_id', $groupId);
        $stmt->bindParam(':page_name', $pageName);
        return $stmt->execute();
    }

    public function getPermissions($groupId) {
        $query = "SELECT page_name FROM group_permissions WHERE group_id = :group_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':group_id', $groupId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN); // Returns simple array of strings
    }

    public function deletePermissions($groupId) {
        $query = "DELETE FROM group_permissions WHERE group_id = :group_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':group_id', $groupId);
        return $stmt->execute();
    }

    /**
     * Retorna os IDs de setores permitidos para um grupo.
     * Busca permissões com prefixo 'sector_' e extrai o ID numérico.
     */
    public function getAllowedSectors($groupId) {
        $perms = $this->getPermissions($groupId);
        $sectorIds = [];
        foreach ($perms as $p) {
            if (str_starts_with($p, 'sector_')) {
                $sectorIds[] = (int) str_replace('sector_', '', $p);
            }
        }
        return $sectorIds;
    }

    /**
     * Retorna as chaves de etapas do pipeline permitidas para um grupo.
     * Busca permissões com prefixo 'stage_' e extrai a chave.
     */
    public function getAllowedStages($groupId) {
        $perms = $this->getPermissions($groupId);
        $stages = [];
        foreach ($perms as $p) {
            if (str_starts_with($p, 'stage_')) {
                $stages[] = str_replace('stage_', '', $p);
            }
        }
        return $stages;
    }

    /**
     * Verifica se um grupo tem permissão para um setor específico.
     */
    public function hasSectorPermission($groupId, $sectorId) {
        $allowed = $this->getAllowedSectors($groupId);
        return empty($allowed) || in_array((int)$sectorId, $allowed);
    }

    /**
     * Verifica se um grupo tem permissão para uma etapa específica do pipeline.
     */
    public function hasStagePermission($groupId, $stageKey) {
        $allowed = $this->getAllowedStages($groupId);
        return empty($allowed) || in_array($stageKey, $allowed);
    }
}
