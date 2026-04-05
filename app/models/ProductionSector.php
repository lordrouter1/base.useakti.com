<?php
namespace Akti\Models;

use Akti\Core\EventDispatcher;
use Akti\Core\Event;
use PDO;

class ProductionSector {
    private $conn;
    private $table = 'production_sectors';

    public function __construct(\PDO $db) {
        $this->conn = $db;
    }

    /**
     * Conta o total de setores de produção cadastrados
     */
    public function countAll() {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM {$this->table}");
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function readAll($onlyActive = false) {
        $sql = "SELECT * FROM {$this->table}";
        if ($onlyActive) {
            $sql .= " WHERE is_active = :active";
        }
        $sql .= " ORDER BY sort_order ASC, name ASC";
        $stmt = $this->conn->prepare($sql);
        if ($onlyActive) {
            $stmt->bindValue(':active', 1, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna setores paginados com busca opcional.
     *
     * @param int    $page    Página atual (1-based)
     * @param int    $perPage Registros por página
     * @param string $search  Termo de busca (nome)
     * @return array{data: array, total: int, pages: int, current_page: int}
     */
    public function readPaginated(int $page = 1, int $perPage = 15, string $search = ''): array
    {
        $offset = ($page - 1) * $perPage;
        $where = [];
        $params = [];

        if ($search !== '') {
            $where[] = "name LIKE :search";
            $params[':search'] = "%{$search}%";
        }

        $whereStr = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $countSql = "SELECT COUNT(*) FROM {$this->table} {$whereStr}";
        $countStmt = $this->conn->prepare($countSql);
        foreach ($params as $k => $v) {
            $countStmt->bindValue($k, $v);
        }
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        $sql = "SELECT * FROM {$this->table} {$whereStr} ORDER BY sort_order ASC, name ASC LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data'         => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total'        => $total,
            'pages'        => (int) ceil($total / $perPage),
            'current_page' => $page,
        ];
    }

    public function readOne($id) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $stmt = $this->conn->prepare("INSERT INTO {$this->table} (name, description, icon, color, sort_order) VALUES (:name, :desc, :icon, :color, :sort)");
        $stmt->execute([
            ':name'  => $data['name'],
            ':desc'  => $data['description'] ?? '',
            ':icon'  => $data['icon'] ?? 'fas fa-cogs',
            ':color' => $data['color'] ?? '#6c757d',
            ':sort'  => $data['sort_order'] ?? 0,
        ]);
        $newId = $this->conn->lastInsertId();
        EventDispatcher::dispatch('model.production_sector.created', new Event('model.production_sector.created', [
            'id' => $newId,
            'name' => $data['name'],
        ]));
        return $newId;
    }

    public function update($data) {
        $stmt = $this->conn->prepare("UPDATE {$this->table} SET name = :name, description = :desc, icon = :icon, color = :color, sort_order = :sort, is_active = :active WHERE id = :id");
        $result = $stmt->execute([
            ':name'   => $data['name'],
            ':desc'   => $data['description'] ?? '',
            ':icon'   => $data['icon'] ?? 'fas fa-cogs',
            ':color'  => $data['color'] ?? '#6c757d',
            ':sort'   => $data['sort_order'] ?? 0,
            ':active' => $data['is_active'] ?? 1,
            ':id'     => $data['id'],
        ]);
        if ($result) {
            EventDispatcher::dispatch('model.production_sector.updated', new Event('model.production_sector.updated', [
                'id' => $data['id'],
                'name' => $data['name'],
            ]));
        }
        return $result;
    }

    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE id = :id");
        $result = $stmt->execute([':id' => $id]);
        if ($result) {
            EventDispatcher::dispatch('model.production_sector.deleted', new Event('model.production_sector.deleted', ['id' => $id]));
        }
        return $result;
    }

    /** Retorna os setores vinculados a um produto */
    public function getProductSectors($productId) {
        $stmt = $this->conn->prepare("SELECT ps.*, s.name as sector_name, s.icon, s.color 
            FROM product_sectors ps 
            JOIN production_sectors s ON ps.sector_id = s.id 
            WHERE ps.product_id = :pid 
            ORDER BY ps.sort_order ASC");
        $stmt->execute([':pid' => $productId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Salva os setores de um produto (replace) */
    public function saveProductSectors($productId, $sectorIds) {
        $this->conn->prepare("DELETE FROM product_sectors WHERE product_id = :pid")->execute([':pid' => $productId]);
        if (!empty($sectorIds)) {
            $stmt = $this->conn->prepare("INSERT INTO product_sectors (product_id, sector_id, sort_order) VALUES (:pid, :sid, :sort)");
            foreach ($sectorIds as $i => $sid) {
                $stmt->execute([':pid' => $productId, ':sid' => $sid, ':sort' => $i]);
            }
        }
    }

    /** Retorna os setores vinculados a uma categoria */
    public function getCategorySectors($categoryId) {
        $stmt = $this->conn->prepare("SELECT cs.*, s.name as sector_name, s.icon, s.color 
            FROM category_sectors cs 
            JOIN production_sectors s ON cs.sector_id = s.id 
            WHERE cs.category_id = :cid 
            ORDER BY cs.sort_order ASC");
        $stmt->execute([':cid' => $categoryId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Salva os setores de uma categoria (replace) */
    public function saveCategorySectors($categoryId, $sectorIds) {
        $this->conn->prepare("DELETE FROM category_sectors WHERE category_id = :cid")->execute([':cid' => $categoryId]);
        if (!empty($sectorIds)) {
            $stmt = $this->conn->prepare("INSERT INTO category_sectors (category_id, sector_id, sort_order) VALUES (:cid, :sid, :sort)");
            foreach ($sectorIds as $i => $sid) {
                $stmt->execute([':cid' => $categoryId, ':sid' => $sid, ':sort' => $i]);
            }
        }
    }

    /** Retorna os setores vinculados a uma subcategoria */
    public function getSubcategorySectors($subcategoryId) {
        $stmt = $this->conn->prepare("SELECT ss.*, s.name as sector_name, s.icon, s.color 
            FROM subcategory_sectors ss 
            JOIN production_sectors s ON ss.sector_id = s.id 
            WHERE ss.subcategory_id = :sid 
            ORDER BY ss.sort_order ASC");
        $stmt->execute([':sid' => $subcategoryId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Salva os setores de uma subcategoria (replace) */
    public function saveSubcategorySectors($subcategoryId, $sectorIds) {
        $this->conn->prepare("DELETE FROM subcategory_sectors WHERE subcategory_id = :sid")->execute([':sid' => $subcategoryId]);
        if (!empty($sectorIds)) {
            $stmt = $this->conn->prepare("INSERT INTO subcategory_sectors (subcategory_id, sector_id, sort_order) VALUES (:sid, :secid, :sort)");
            foreach ($sectorIds as $i => $secid) {
                $stmt->execute([':sid' => $subcategoryId, ':secid' => $secid, ':sort' => $i]);
            }
        }
    }

    /**
     * Retorna os setores efetivos de um produto, com fallback:
     * produto > subcategoria > categoria
     */
    public function getEffectiveSectors($productId, $subcategoryId = null, $categoryId = null) {
        // 1. Tenta setores do produto
        $sectors = $this->getProductSectors($productId);
        if (!empty($sectors)) {
            return ['source' => 'product', 'sectors' => $sectors];
        }

        // 2. Tenta setores da subcategoria
        if ($subcategoryId) {
            $sectors = $this->getSubcategorySectors($subcategoryId);
            if (!empty($sectors)) {
                return ['source' => 'subcategory', 'sectors' => $sectors];
            }
        }

        // 3. Tenta setores da categoria
        if ($categoryId) {
            $sectors = $this->getCategorySectors($categoryId);
            if (!empty($sectors)) {
                return ['source' => 'category', 'sectors' => $sectors];
            }
        }

        return ['source' => null, 'sectors' => []];
    }
}
