<?php
namespace Akti\Models;

use Akti\Core\EventDispatcher;
use Akti\Core\Event;
use PDO;

/**
 * Model: Customer
 * Gerencia clientes (CRUD).
 * Entradas: Conexão PDO ($db), parâmetros das funções.
 * Saídas: Arrays de dados, booleanos, IDs inseridos.
 * Eventos: 'model.customer.created', 'model.customer.updated', 'model.customer.deleted' (ao criar, atualizar ou excluir)
 * Não deve conter HTML, echo, print ou acesso direto a $_POST/$_GET.
 */
class Customer {
    private $conn;
    private $table_name = "customers";

    public $id;
    public $name;
    public $email;
    public $phone;

    /**
     * Construtor do model
     * @param PDO $db Conexão PDO
     */
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Retorna todos os clientes
     * @return PDOStatement Lista de clientes (fetchAll)
     */
    public function readAll() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Retorna clientes com paginação
     * @param int $page  Página atual (1-based)
     * @param int $perPage Itens por página
     * @return array Lista de clientes
     */
    public function readPaginated(int $page = 1, int $perPage = 15): array
    {
        $offset = ($page - 1) * $perPage;
        $query = "SELECT * FROM {$this->table_name} ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna um cliente pelo ID
     * @param int $id ID do cliente
     * @return array|null Dados do cliente ou null
     */
    public function readOne($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Cria um novo cliente
     * @param array $data Dados do cliente
     * @return int ID do cliente criado
     * Evento disparado: 'model.customer.created' com ['id', 'name', 'email']
     */
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

    /**
     * Atualiza um cliente
     * @param array $data Dados do cliente
     * @return bool Sucesso ou falha na operação
     * Evento disparado: 'model.customer.updated' com ['id', 'name', 'email']
     */
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

    /**
     * Exclui um cliente
     * @param int $id ID do cliente
     * @return bool Sucesso ou falha na operação
     * Evento disparado: 'model.customer.deleted' com ['id']
     */
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
     * Retorna o total de clientes
     * @return int Total de clientes
     */
    public function countAll() {
        $query = "SELECT COUNT(*) FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    /**
     * Retorna clientes com paginação e filtros
     * @param int $page Página atual (1-based)
     * @param int $perPage Itens por página
     * @param string|null $search Busca por nome, email, telefone ou documento
     * @return array ['data' => [...], 'total' => int]
     */
    public function readPaginatedFiltered(int $page = 1, int $perPage = 15, ?string $search = null): array
    {
        $where = "1=1";
        $params = [];

        if ($search) {
            $where .= " AND (name LIKE :s1 OR email LIKE :s2 OR phone LIKE :s3 OR document LIKE :s4)";
            $params[':s1'] = "%{$search}%";
            $params[':s2'] = "%{$search}%";
            $params[':s3'] = "%{$search}%";
            $params[':s4'] = "%{$search}%";
        }

        // Total
        $stmtCount = $this->conn->prepare("SELECT COUNT(*) FROM {$this->table_name} WHERE {$where}");
        foreach ($params as $k => $v) $stmtCount->bindValue($k, $v);
        $stmtCount->execute();
        $total = (int) $stmtCount->fetchColumn();

        // Data
        $offset = ($page - 1) * $perPage;
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table_name} WHERE {$where} ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return ['data' => $data, 'total' => $total];
    }

    /**
     * Busca clientes para Select2 (AJAX autocomplete).
     * Pesquisa por nome, e-mail, telefone ou documento.
     *
     * @param string $term Termo de busca
     * @param int    $limit Máximo de resultados
     * @return array Lista de clientes [id, name, email, phone, document]
     */
    public function searchForSelect2(string $term, int $limit = 10): array
    {
        $term = trim($term);
        if ($term === '') {
            // Sem filtro: retorna os últimos cadastrados
            $stmt = $this->conn->prepare(
                "SELECT id, name, email, phone, document
                 FROM {$this->table_name}
                 ORDER BY name ASC
                 LIMIT :lim"
            );
            $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }

        $like = "%{$term}%";
        $stmt = $this->conn->prepare(
            "SELECT id, name, email, phone, document
             FROM {$this->table_name}
             WHERE name LIKE :s1 OR email LIKE :s2 OR phone LIKE :s3 OR document LIKE :s4
             ORDER BY
                CASE WHEN name LIKE :exact THEN 0 ELSE 1 END,
                name ASC
             LIMIT :lim"
        );
        $stmt->bindValue(':s1', $like);
        $stmt->bindValue(':s2', $like);
        $stmt->bindValue(':s3', $like);
        $stmt->bindValue(':s4', $like);
        $stmt->bindValue(':exact', $term . '%');
        $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Importa um cliente a partir de dados mapeados
     * @param array $data Dados mapeados
     * @return int|false ID do cliente criado ou false
     */
    public function importFromMapped(array $data)
    {
        $address = json_encode([
            'zipcode'        => $data['zipcode'] ?? '',
            'address_type'   => $data['address_type'] ?? '',
            'address_name'   => $data['address_name'] ?? '',
            'address_number' => $data['address_number'] ?? '',
            'neighborhood'   => $data['neighborhood'] ?? '',
            'complement'     => $data['complement'] ?? '',
        ]);

        $query = "INSERT INTO {$this->table_name} (name, email, phone, document, address, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $this->conn->prepare($query);
        $result = $stmt->execute([
            $data['name'],
            $data['email'] ?? null,
            $data['phone'] ?? null,
            $data['document'] ?? null,
            $address,
        ]);

        if ($result) {
            $newId = $this->conn->lastInsertId();
            EventDispatcher::dispatch('model.customer.created', new Event('model.customer.created', [
                'id'    => $newId,
                'name'  => $data['name'],
                'email' => $data['email'] ?? '',
            ]));
            return $newId;
        }
        return false;
    }
}
