<?php
namespace Akti\Models;

use Akti\Core\EventDispatcher;
use Akti\Core\Event;
use PDO;

/**
 * Model: Category
 * Responsável por acesso e regras de negócio das categorias de produtos.
 * Entradas: Conexão PDO ($db), propriedades públicas $id e $name, parâmetros das funções.
 * Saídas: Arrays de dados, PDOStatement, booleanos, contagem de produtos.
 * Não deve conter HTML, echo, print ou acesso direto a $_POST/$_GET.
 */
class Category {
    private $conn;
    private $table_name = "categories";

    public $id;
    public $name;

    /**
     * Construtor do model Category
     * @param PDO $db Conexão PDO com o banco de dados
     */
    public function __construct(\PDO $db) {
        $this->conn = $db;
    }

    /**
     * Retorna todas as categorias ordenadas por nome
     * @return array Array de categorias
     */
    public function readAll() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Retorna a quantidade total de categorias.
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
     * Retorna categorias paginadas.
     *
     * @param int $page   Página atual (1-based)
     * @param int $perPage Registros por página
     * @return array
     */
    public function readPaginated(int $page = 1, int $perPage = 15): array
    {
        $offset = ($page - 1) * $perPage;
        $query = "SELECT * FROM {$this->table_name}
                  ORDER BY name ASC
                  LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Cria uma nova categoria
     * Usa $this->name como valor
     * @return bool Retorna true se criada com sucesso, false caso contrário
     * Evento disparado: 'model.category.created' com ['id', 'name']
     */
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET name=:name, free_shipping=:free_shipping";
        $stmt = $this->conn->prepare($query);
        
        $this->name = htmlspecialchars(strip_tags($this->name));
        $stmt->bindParam(":name", $this->name);
        $freeShipping = isset($this->free_shipping) ? (int) $this->free_shipping : 0;
        $stmt->bindValue(":free_shipping", $freeShipping, \PDO::PARAM_INT);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            EventDispatcher::dispatch('model.category.created', new Event('model.category.created', [
                'id' => $this->id,
                'name' => $this->name,
            ]));
            return true;
        }
        return false;
    }

    /**
     * Retorna todas as subcategorias de uma categoria
     * @param int $categoryId ID da categoria
     * @return array Array de subcategorias (fetchAll)
     */
    public function getSubcategories($categoryId) {
        $query = "SELECT * FROM subcategories WHERE category_id = :category_id ORDER BY name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':category_id', $categoryId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna dados de uma categoria específica
     * @param int $categoryId ID da categoria
     * @return array|null Array com dados ou null se não encontrada
     */
    public function getCategory($categoryId) {
        $query = "SELECT * FROM categories WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $categoryId);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Atualiza o nome de uma categoria
     * @param int $id ID da categoria
     * @param string $name Novo nome
     * @return bool Retorna true se atualizada com sucesso
     * Evento disparado: 'model.category.updated' com ['id', 'name']
     */
    public function update($id, $name, $showInStore = null, $freeShipping = null) {
        $fields = ['name = :name'];
        $params = [':name' => htmlspecialchars(strip_tags($name)), ':id' => $id];
        if ($showInStore !== null) {
            $fields[] = 'show_in_store = :show_in_store';
            $params[':show_in_store'] = (int) $showInStore;
        }
        if ($freeShipping !== null) {
            $fields[] = 'free_shipping = :free_shipping';
            $params[':free_shipping'] = (int) $freeShipping;
        }
        $stmt = $this->conn->prepare("UPDATE categories SET " . implode(', ', $fields) . " WHERE id = :id");
        $result = $stmt->execute($params);
        if ($result) {
            EventDispatcher::dispatch('model.category.updated', new Event('model.category.updated', [
                'id' => $id,
                'name' => $name,
            ]));
        }
        return $result;
    }

    /**
     * Exclui uma categoria
     * @param int $id ID da categoria
     * @return bool Retorna true se excluída com sucesso
     * Evento disparado: 'model.category.deleted' com ['id']
     */
    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM categories WHERE id = :id");
        $result = $stmt->execute([':id' => $id]);
        if ($result) {
            EventDispatcher::dispatch('model.category.deleted', new Event('model.category.deleted', ['id' => $id]));
        }
        return $result;
    }

    /**
     * Conta quantos produtos estão vinculados a uma categoria
     * @param int $categoryId ID da categoria
     * @return int Quantidade de produtos
     */
    public function countProducts($categoryId) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM products WHERE category_id = :id");
        $stmt->execute([':id' => $categoryId]);
        return $stmt->fetchColumn();
    }

    /**
     * Retorna todas as categorias com contagem de produtos e subcategorias
     * @return array Array de categorias com product_count e sub_count
     */
    public function readAllWithCount() {
        $stmt = $this->conn->query("SELECT c.*, 
            (SELECT COUNT(*) FROM products WHERE category_id = c.id) as product_count,
            (SELECT COUNT(*) FROM subcategories WHERE category_id = c.id) as sub_count
            FROM categories c ORDER BY c.name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna apenas categorias visíveis na loja (show_in_store = 1)
     * @return array
     */
    public function readAllVisible(): array
    {
        $stmt = $this->conn->query("SELECT * FROM categories WHERE show_in_store = 1 ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna subcategorias visíveis na loja para uma categoria
     * @param int $categoryId
     * @return array
     */
    public function getVisibleSubcategories(int $categoryId): array
    {
        $stmt = $this->conn->prepare("SELECT * FROM subcategories WHERE category_id = :category_id AND show_in_store = 1 ORDER BY name ASC");
        $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
