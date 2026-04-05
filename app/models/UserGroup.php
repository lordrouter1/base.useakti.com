<?php
namespace Akti\Models;

use Akti\Core\EventDispatcher;
use Akti\Core\Event;
use PDO;

/**
 * Classe UserGroup
 *
 * Responsável pela gestão de grupos de usuários e permissões.
 * Permite CRUD de grupos, vinculação de permissões, controle de setores e etapas do pipeline.
 * Emite eventos via EventDispatcher após operações relevantes.
 *
 * Observações:
 * - Permissões são armazenadas na tabela group_permissions.
 * - Métodos de verificação retornam true se o grupo não possui restrição explícita.
 * - Recomendado validar dados no Controller antes de chamar métodos de criação/atualização.
 *
 * @package Akti\Models
 */
class UserGroup {
    /**
     * Conexão PDO
     * @var PDO
     */
    private $conn;
    /**
     * Nome da tabela de grupos
     * @var string
     */
    private $table_name = "user_groups";
    /**
     * ID do grupo
     * @var int|null
     */
    public $id;
    /**
     * Nome do grupo
     * @var string|null
     */
    public $name;
    /**
     * Descrição do grupo
     * @var string|null
     */
    public $description;

    /**
     * Construtor
     * @param PDO $db Conexão PDO
     */
    public function __construct(\PDO $db) {
        $this->conn = $db;
    }

    /**
     * Retorna todos os grupos cadastrados.
     * @return array
     */
    public function readAll() {
        $query = "SELECT DISTINCT * FROM " . $this->table_name . " ORDER BY id ASC"; 
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Retorna a quantidade total de grupos.
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
     * Retorna grupos paginados.
     *
     * @param int $page   Página atual (1-based)
     * @param int $perPage Registros por página
     * @return array
     */
    public function readPaginated(int $page = 1, int $perPage = 15): array
    {
        $offset = ($page - 1) * $perPage;
        $query = "SELECT * FROM {$this->table_name}
                  ORDER BY id ASC
                  LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    /**
     * Cria um novo grupo de usuários.
     * Emite evento 'model.user_group.created'.
     * @return bool
     */
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

    /**
     * Retorna um grupo pelo ID.
     * @param int $id
     * @return array|false
     */
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

    /**
     * Atualiza os dados de um grupo.
     * Emite evento 'model.user_group.updated'.
     * @return bool
     */
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

    /**
     * Exclui um grupo pelo ID.
     * Emite evento 'model.user_group.deleted'.
     * @param int $id
     * @return bool
     */
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
    
    /**
     * Adiciona permissão a um grupo.
     * @param int $groupId
     * @param string $pageName
     * @return bool
     */
    public function addPermission($groupId, $pageName) {
        $query = "INSERT INTO group_permissions (group_id, page_name) VALUES (:group_id, :page_name)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':group_id', $groupId);
        $stmt->bindParam(':page_name', $pageName);
        return $stmt->execute();
    }

    /**
     * Retorna as permissões de páginas de um grupo.
     * @param int $groupId
     * @return array
     */
    public function getPermissions($groupId) {
        $query = "SELECT page_name FROM group_permissions WHERE group_id = :group_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':group_id', $groupId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN); // Returns simple array of strings
    }

    /**
     * Remove todas as permissões de um grupo.
     * @param int $groupId
     * @return bool
     */
    public function deletePermissions($groupId) {
        $query = "DELETE FROM group_permissions WHERE group_id = :group_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':group_id', $groupId);
        return $stmt->execute();
    }

    /**
     * Retorna os IDs de setores permitidos para um grupo.
     * Busca permissões com prefixo 'sector_'.
     * @param int $groupId
     * @return array
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
     * Busca permissões com prefixo 'stage_'.
     * @param int $groupId
     * @return array
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
     * @param int $groupId
     * @param int $sectorId
     * @return bool
     */
    public function hasSectorPermission($groupId, $sectorId) {
        $allowed = $this->getAllowedSectors($groupId);
        return empty($allowed) || in_array((int)$sectorId, $allowed);
    }

    /**
     * Verifica se um grupo tem permissão para uma etapa específica do pipeline.
     * @param int $groupId
     * @param string $stageKey
     * @return bool
     */
    public function hasStagePermission($groupId, $stageKey) {
        $allowed = $this->getAllowedStages($groupId);
        return empty($allowed) || in_array($stageKey, $allowed);
    }
}
