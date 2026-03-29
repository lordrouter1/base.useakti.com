<?php
namespace Akti\Models;

use Akti\Core\EventDispatcher;
use Akti\Core\Event;
use PDO;

/**
 * Model: Customer
 * Gerencia clientes (CRUD) — versão expandida com ~40 campos.
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

    // ──────────────────────────────────────────────
    // CRUD — Leitura
    // ──────────────────────────────────────────────

    /**
     * Retorna todos os clientes (exclui soft-deleted)
     * @return PDOStatement Lista de clientes (fetchAll)
     */
    public function readAll() {
        $query = "SELECT * FROM {$this->table_name} WHERE deleted_at IS NULL ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Retorna clientes com paginação (exclui soft-deleted)
     * @param int $page  Página atual (1-based)
     * @param int $perPage Itens por página
     * @return array Lista de clientes
     */
    public function readPaginated(int $page = 1, int $perPage = 15): array
    {
        $offset = ($page - 1) * $perPage;
        $query = "SELECT * FROM {$this->table_name} WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
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
        $query = "SELECT * FROM {$this->table_name} WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ──────────────────────────────────────────────
    // CRUD — Criação
    // ──────────────────────────────────────────────

    /**
     * Cria um novo cliente com todos os campos expandidos.
     * Gera código sequencial automaticamente.
     *
     * @param array $data Dados do cliente
     * @return int ID do cliente criado
     * Evento disparado: 'model.customer.created' com ['id', 'name', 'email', 'code']
     */
    public function create($data) {
        // Gerar código sequencial se não fornecido
        $code = !empty($data['code']) ? $data['code'] : $this->generateCode();

        // Sanitizar document (apenas números)
        $document = isset($data['document']) ? preg_replace('/\D/', '', $data['document']) : null;

        $query = "INSERT INTO {$this->table_name} (
            code, person_type, name, fantasy_name, document, rg_ie, im, birth_date, gender,
            email, email_secondary, phone, cellphone, phone_commercial,
            website, instagram, contact_name, contact_role,
            address, zipcode, address_street, address_number, address_complement,
            address_neighborhood, address_city, address_state, address_country, address_ibge,
            price_table_id, payment_term, credit_limit, discount_default, seller_id, origin, tags,
            photo, observations, status, created_by, import_batch_id, created_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, NOW()
        )";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            $code,
            $data['person_type'] ?? 'PF',
            $data['name'],
            $data['fantasy_name'] ?? null,
            $document ?: null,
            $data['rg_ie'] ?? null,
            $data['im'] ?? null,
            !empty($data['birth_date']) ? $data['birth_date'] : null,
            $data['gender'] ?? null,
            $data['email'] ?? null,
            $data['email_secondary'] ?? null,
            $data['phone'] ?? null,
            $data['cellphone'] ?? null,
            $data['phone_commercial'] ?? null,
            $data['website'] ?? null,
            $data['instagram'] ?? null,
            $data['contact_name'] ?? null,
            $data['contact_role'] ?? null,
            $data['address'] ?? null,
            $data['zipcode'] ?? null,
            $data['address_street'] ?? null,
            $data['address_number'] ?? null,
            $data['address_complement'] ?? null,
            $data['address_neighborhood'] ?? null,
            $data['address_city'] ?? null,
            $data['address_state'] ?? null,
            $data['address_country'] ?? 'Brasil',
            $data['address_ibge'] ?? null,
            !empty($data['price_table_id']) ? $data['price_table_id'] : null,
            $data['payment_term'] ?? null,
            isset($data['credit_limit']) && $data['credit_limit'] !== '' ? $data['credit_limit'] : null,
            isset($data['discount_default']) && $data['discount_default'] !== '' ? $data['discount_default'] : null,
            !empty($data['seller_id']) ? $data['seller_id'] : null,
            $data['origin'] ?? null,
            $data['tags'] ?? null,
            $data['photo'] ?? null,
            $data['observations'] ?? null,
            $data['status'] ?? 'active',
            $data['created_by'] ?? null,
            $data['import_batch_id'] ?? null,
        ]);

        $newId = $this->conn->lastInsertId();

        EventDispatcher::dispatch('model.customer.created', new Event('model.customer.created', [
            'id'    => $newId,
            'name'  => $data['name'],
            'email' => $data['email'] ?? '',
            'code'  => $code,
        ]));

        return $newId;
    }

    // ──────────────────────────────────────────────
    // CRUD — Atualização
    // ──────────────────────────────────────────────

    /**
     * Atualiza um cliente com todos os campos expandidos.
     * Retrocompatível: atualiza apenas os campos presentes no array $data.
     * Campos não fornecidos mantêm seu valor atual no banco.
     *
     * @param array $data Dados do cliente (deve conter 'id')
     * @return bool Sucesso ou falha na operação
     * Evento disparado: 'model.customer.updated' com ['id', 'name', 'email']
     */
    public function update($data) {
        // Mapa de todos os campos atualizáveis e seus tratamentos
        $fieldMap = [
            'person_type'          => 'string',
            'name'                 => 'string',
            'fantasy_name'         => 'string',
            'document'             => 'document',
            'rg_ie'                => 'string',
            'im'                   => 'string',
            'birth_date'           => 'date',
            'gender'               => 'string',
            'email'                => 'string',
            'email_secondary'      => 'string',
            'phone'                => 'string',
            'cellphone'            => 'string',
            'phone_commercial'     => 'string',
            'website'              => 'string',
            'instagram'            => 'string',
            'contact_name'         => 'string',
            'contact_role'         => 'string',
            'address'              => 'string',
            'zipcode'              => 'string',
            'address_street'       => 'string',
            'address_number'       => 'string',
            'address_complement'   => 'string',
            'address_neighborhood' => 'string',
            'address_city'         => 'string',
            'address_state'        => 'string',
            'address_country'      => 'string',
            'address_ibge'         => 'string',
            'price_table_id'       => 'int_nullable',
            'payment_term'         => 'string',
            'credit_limit'         => 'decimal',
            'discount_default'     => 'decimal',
            'seller_id'            => 'int_nullable',
            'origin'               => 'string',
            'tags'                 => 'string',
            'observations'         => 'string',
            'status'               => 'string',
            'updated_by'           => 'int_nullable',
        ];

        $sets = [];
        $params = [];

        foreach ($fieldMap as $field => $type) {
            // Só incluir campos explicitamente presentes no $data (retrocompatibilidade)
            if (!array_key_exists($field, $data)) {
                continue;
            }

            $value = $data[$field];

            switch ($type) {
                case 'document':
                    $value = $value !== null ? preg_replace('/\D/', '', $value) : null;
                    $value = $value ?: null;
                    break;
                case 'date':
                    $value = !empty($value) ? $value : null;
                    break;
                case 'int_nullable':
                    $value = !empty($value) ? (int) $value : null;
                    break;
                case 'decimal':
                    $value = (isset($value) && $value !== '') ? $value : null;
                    break;
                default: // string
                    // mantém como está (pode ser null)
                    break;
            }

            $sets[] = "{$field} = ?";
            $params[] = $value;
        }

        // Foto: só atualiza se nova foto foi enviada
        if (isset($data['photo']) && $data['photo']) {
            $sets[] = "photo = ?";
            $params[] = $data['photo'];
        }

        if (empty($sets)) {
            return false;
        }

        $query = "UPDATE {$this->table_name} SET " . implode(', ', $sets) . " WHERE id = ?";
        $params[] = $data['id'];

        $stmt = $this->conn->prepare($query);
        $result = $stmt->execute($params);

        if ($result) {
            EventDispatcher::dispatch('model.customer.updated', new Event('model.customer.updated', [
                'id'    => $data['id'],
                'name'  => $data['name'] ?? '',
                'email' => $data['email'] ?? '',
            ]));
        }

        return $result;
    }

    // ──────────────────────────────────────────────
    // CRUD — Exclusão
    // ──────────────────────────────────────────────

    /**
     * Exclui permanentemente um cliente (hard delete).
     * Prefira softDelete() em produção.
     *
     * @param int $id ID do cliente
     * @return bool Sucesso ou falha na operação
     * Evento disparado: 'model.customer.deleted' com ['id']
     */
    public function delete($id) {
        $query = "DELETE FROM {$this->table_name} WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $result = $stmt->execute();
        if ($result) {
            EventDispatcher::dispatch('model.customer.deleted', new Event('model.customer.deleted', ['id' => $id]));
        }
        return $result;
    }

    /**
     * Soft delete: marca deleted_at com timestamp atual.
     *
     * @param int $id ID do cliente
     * @return bool Sucesso ou falha
     */
    public function softDelete(int $id): bool
    {
        $query = "UPDATE {$this->table_name} SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $result = $stmt->execute();
        if ($result && $stmt->rowCount() > 0) {
            EventDispatcher::dispatch('model.customer.deleted', new Event('model.customer.deleted', ['id' => $id]));
        }
        return $result;
    }

    /**
     * Restaura um cliente soft-deleted.
     *
     * @param int $id ID do cliente
     * @return bool Sucesso ou falha
     */
    public function restore(int $id): bool
    {
        $query = "UPDATE {$this->table_name} SET deleted_at = NULL WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // ──────────────────────────────────────────────
    // Contagem
    // ──────────────────────────────────────────────

    /**
     * Retorna o total de clientes (exclui soft-deleted)
     * @return int Total de clientes
     */
    public function countAll() {
        $query = "SELECT COUNT(*) FROM {$this->table_name} WHERE deleted_at IS NULL";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    // ──────────────────────────────────────────────
    // Listagem com filtros avançados
    // ──────────────────────────────────────────────

    /**
     * Retorna clientes com paginação e filtros avançados.
     * Exclui soft-deleted por padrão.
     *
     * @param int $page Página atual (1-based)
     * @param int $perPage Itens por página
     * @param string|null $search Busca textual (nome, email, telefone, documento, código)
     * @param array $filters Filtros avançados: status, person_type, state, city, seller_id, from, to, tags
     * @return array ['data' => [...], 'total' => int]
     */
    public function readPaginatedFiltered(int $page = 1, int $perPage = 15, ?string $search = null, array $filters = []): array
    {
        $where = "deleted_at IS NULL";
        $params = [];

        // Busca textual
        if ($search) {
            $where .= " AND (name LIKE :s1 OR email LIKE :s2 OR phone LIKE :s3 OR document LIKE :s4 OR cellphone LIKE :s5 OR code LIKE :s6 OR fantasy_name LIKE :s7)";
            $params[':s1'] = "%{$search}%";
            $params[':s2'] = "%{$search}%";
            $params[':s3'] = "%{$search}%";
            $params[':s4'] = "%{$search}%";
            $params[':s5'] = "%{$search}%";
            $params[':s6'] = "%{$search}%";
            $params[':s7'] = "%{$search}%";
        }

        // Filtro: status
        if (!empty($filters['status'])) {
            $where .= " AND status = :f_status";
            $params[':f_status'] = $filters['status'];
        }

        // Filtro: tipo de pessoa
        if (!empty($filters['person_type'])) {
            $where .= " AND person_type = :f_person_type";
            $params[':f_person_type'] = $filters['person_type'];
        }

        // Filtro: UF
        if (!empty($filters['state'])) {
            $where .= " AND address_state = :f_state";
            $params[':f_state'] = $filters['state'];
        }

        // Filtro: cidade
        if (!empty($filters['city'])) {
            $where .= " AND address_city LIKE :f_city";
            $params[':f_city'] = "%{$filters['city']}%";
        }

        // Filtro: vendedor
        if (!empty($filters['seller_id'])) {
            $where .= " AND seller_id = :f_seller";
            $params[':f_seller'] = (int) $filters['seller_id'];
        }

        // Filtro: data de criação (de)
        if (!empty($filters['from'])) {
            $where .= " AND created_at >= :f_from";
            $params[':f_from'] = $filters['from'] . ' 00:00:00';
        }

        // Filtro: data de criação (até)
        if (!empty($filters['to'])) {
            $where .= " AND created_at <= :f_to";
            $params[':f_to'] = $filters['to'] . ' 23:59:59';
        }

        // Filtro: tags (LIKE)
        if (!empty($filters['tags'])) {
            $where .= " AND tags LIKE :f_tags";
            $params[':f_tags'] = "%{$filters['tags']}%";
        }

        // Filtro: IDs específicos (para exportação de selecionados)
        if (!empty($filters['ids']) && is_array($filters['ids'])) {
            $idPlaceholders = [];
            foreach ($filters['ids'] as $idx => $id) {
                $ph = ':f_id_' . $idx;
                $idPlaceholders[] = $ph;
                $params[$ph] = (int) $id;
            }
            $where .= " AND id IN (" . implode(',', $idPlaceholders) . ")";
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

    // ──────────────────────────────────────────────
    // Busca para Select2 (AJAX autocomplete)
    // ──────────────────────────────────────────────

    /**
     * Busca clientes para Select2 (AJAX autocomplete).
     * Pesquisa por nome, e-mail, telefone, documento ou código.
     * Exclui soft-deleted.
     *
     * @param string $term Termo de busca
     * @param int    $limit Máximo de resultados
     * @return array Lista de clientes [id, name, email, phone, document, code, person_type]
     */
    public function searchForSelect2(string $term, int $limit = 10): array
    {
        $term = trim($term);
        if ($term === '') {
            $stmt = $this->conn->prepare(
                "SELECT id, name, email, phone, document, code, person_type
                 FROM {$this->table_name}
                 WHERE deleted_at IS NULL
                 ORDER BY name ASC
                 LIMIT :lim"
            );
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $like = "%{$term}%";
        $stmt = $this->conn->prepare(
            "SELECT id, name, email, phone, document, code, person_type
             FROM {$this->table_name}
             WHERE deleted_at IS NULL
               AND (name LIKE :s1 OR email LIKE :s2 OR phone LIKE :s3 OR document LIKE :s4 OR code LIKE :s5)
             ORDER BY
                CASE WHEN name LIKE :exact THEN 0 ELSE 1 END,
                name ASC
             LIMIT :lim"
        );
        $stmt->bindValue(':s1', $like);
        $stmt->bindValue(':s2', $like);
        $stmt->bindValue(':s3', $like);
        $stmt->bindValue(':s4', $like);
        $stmt->bindValue(':s5', $like);
        $stmt->bindValue(':exact', $term . '%');
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ──────────────────────────────────────────────
    // Busca e verificação de documento
    // ──────────────────────────────────────────────

    /**
     * Busca um cliente pelo CPF/CNPJ (apenas números).
     *
     * @param string $document CPF ou CNPJ sem formatação
     * @return array|null Dados do cliente ou null
     */
    public function findByDocument(string $document): ?array
    {
        $document = preg_replace('/\D/', '', $document);
        if ($document === '') return null;

        $query = "SELECT * FROM {$this->table_name} WHERE document = :doc AND deleted_at IS NULL LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':doc', $document);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Verifica se um documento já está cadastrado (para evitar duplicidade).
     * Ignora o próprio registro ao editar.
     *
     * @param string $document CPF ou CNPJ
     * @param int|null $excludeId ID a excluir da verificação (edição)
     * @return array|false Dados do duplicado, ou false se não existir
     */
    public function checkDuplicate(string $document, ?int $excludeId = null)
    {
        $document = preg_replace('/\D/', '', $document);
        if ($document === '') return false;

        $query = "SELECT id, code, name, document FROM {$this->table_name} WHERE document = :doc AND deleted_at IS NULL";
        $params = [':doc' => $document];

        if ($excludeId) {
            $query .= " AND id != :eid";
            $params[':eid'] = $excludeId;
        }

        $query .= " LIMIT 1";
        $stmt = $this->conn->prepare($query);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: false;
    }

    // ──────────────────────────────────────────────
    // Código sequencial
    // ──────────────────────────────────────────────

    /**
     * Gera o próximo código sequencial no formato CLI-XXXXX.
     *
     * @return string Código gerado (ex: CLI-00001)
     */
    public function generateCode(): string
    {
        $query = "SELECT MAX(CAST(SUBSTRING(code, 5) AS UNSIGNED)) AS max_num
                  FROM {$this->table_name}
                  WHERE code IS NOT NULL AND code LIKE 'CLI-%'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $next = ((int) ($row['max_num'] ?? 0)) + 1;
        return 'CLI-' . str_pad($next, 5, '0', STR_PAD_LEFT);
    }

    // ──────────────────────────────────────────────
    // Status
    // ──────────────────────────────────────────────

    /**
     * Atualiza o status de um cliente.
     *
     * @param int $id ID do cliente
     * @param string $status Novo status: active, inactive, blocked
     * @return bool Sucesso ou falha
     */
    public function updateStatus(int $id, string $status): bool
    {
        $allowed = ['active', 'inactive', 'blocked'];
        if (!in_array($status, $allowed, true)) return false;

        $query = "UPDATE {$this->table_name} SET status = :status WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':status', $status);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // ──────────────────────────────────────────────
    // Operações em lote (bulk)
    // ──────────────────────────────────────────────

    /**
     * Atualiza o status de vários clientes de uma vez.
     *
     * @param array $ids Lista de IDs
     * @param string $status Novo status
     * @return int Quantidade de registros afetados
     */
    public function bulkUpdateStatus(array $ids, string $status): int
    {
        $allowed = ['active', 'inactive', 'blocked'];
        if (!in_array($status, $allowed, true) || empty($ids)) return 0;

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $query = "UPDATE {$this->table_name} SET status = ? WHERE id IN ({$placeholders})";
        $stmt = $this->conn->prepare($query);
        $stmt->execute(array_merge([$status], array_map('intval', $ids)));
        return $stmt->rowCount();
    }

    /**
     * Soft delete em lote.
     *
     * @param array $ids Lista de IDs
     * @return int Quantidade de registros afetados
     */
    public function bulkDelete(array $ids): int
    {
        if (empty($ids)) return 0;

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $query = "UPDATE {$this->table_name} SET deleted_at = NOW() WHERE id IN ({$placeholders}) AND deleted_at IS NULL";
        $stmt = $this->conn->prepare($query);
        $stmt->execute(array_map('intval', $ids));
        return $stmt->rowCount();
    }

    // ──────────────────────────────────────────────
    // Estatísticas e dados auxiliares
    // ──────────────────────────────────────────────

    /**
     * Retorna estatísticas de um cliente (total pedidos, valor total, último pedido).
     *
     * @param int $id ID do cliente
     * @return array ['total_orders' => int, 'total_value' => float, 'last_order_date' => string|null, 'avg_ticket' => float]
     */
    public function getCustomerStats(int $id): array
    {
        $query = "SELECT
                    COUNT(o.id) AS total_orders,
                    COALESCE(SUM(o.total_amount), 0) AS total_value,
                    MAX(o.created_at) AS last_order_date
                  FROM orders o
                  WHERE o.customer_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $totalOrders = (int) ($row['total_orders'] ?? 0);
        $totalValue  = (float) ($row['total_value'] ?? 0);

        return [
            'total_orders'    => $totalOrders,
            'total_value'     => $totalValue,
            'last_order_date' => $row['last_order_date'] ?? null,
            'avg_ticket'      => $totalOrders > 0 ? round($totalValue / $totalOrders, 2) : 0,
        ];
    }

    /**
     * Retorna lista de cidades distintas (para filtros na listagem).
     *
     * @return array Lista de cidades ['São Paulo', 'Rio de Janeiro', ...]
     */
    public function getDistinctCities(): array
    {
        $query = "SELECT DISTINCT address_city FROM {$this->table_name}
                  WHERE address_city IS NOT NULL AND address_city != '' AND deleted_at IS NULL
                  ORDER BY address_city ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Retorna lista de UFs distintas (para filtros na listagem).
     *
     * @return array Lista de UFs ['SP', 'RJ', 'MG', ...]
     */
    public function getDistinctStates(): array
    {
        $query = "SELECT DISTINCT address_state FROM {$this->table_name}
                  WHERE address_state IS NOT NULL AND address_state != '' AND deleted_at IS NULL
                  ORDER BY address_state ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Retorna lista de tags distintas já usadas (para autocomplete).
     * Tags são armazenadas separadas por vírgula no campo 'tags'.
     *
     * @return array Lista de tags únicas ordenadas
     */
    public function getAllTags(): array
    {
        $query = "SELECT tags FROM {$this->table_name}
                  WHERE tags IS NOT NULL AND tags != '' AND deleted_at IS NULL";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $allTags = [];
        foreach ($rows as $tagStr) {
            $parts = array_map('trim', explode(',', $tagStr));
            foreach ($parts as $tag) {
                if ($tag !== '') {
                    $allTags[$tag] = true;
                }
            }
        }

        $tags = array_keys($allTags);
        sort($tags);
        return $tags;
    }

    // ──────────────────────────────────────────────
    // Exportação
    // ──────────────────────────────────────────────

    /**
     * Retorna todos os clientes com filtros aplicados (para exportação CSV/Excel).
     * Mesma lógica do readPaginatedFiltered, mas sem paginação.
     *
     * @param array $filters Filtros: search, status, person_type, state, city, seller_id, from, to, tags
     * @return array Lista completa de clientes
     */
    public function exportAll(array $filters = []): array
    {
        $result = $this->readPaginatedFiltered(1, PHP_INT_MAX, $filters['search'] ?? null, $filters);
        return $result['data'];
    }

    // ──────────────────────────────────────────────
    // Importação
    // ──────────────────────────────────────────────

    /**
     * Importa um cliente a partir de dados mapeados.
     * Suporta todos os novos campos + mantém retrocompatibilidade com JSON de endereço.
     *
     * @param array $data Dados mapeados
     * @return int|false ID do cliente criado ou false
     */
    public function importFromMapped(array $data)
    {
        // Manter campo address JSON para retrocompatibilidade
        $address = json_encode([
            'zipcode'        => $data['zipcode'] ?? '',
            'address_type'   => $data['address_type'] ?? '',
            'address_name'   => $data['address_name'] ?? '',
            'address_number' => $data['address_number'] ?? '',
            'neighborhood'   => $data['neighborhood'] ?? $data['address_neighborhood'] ?? '',
            'complement'     => $data['complement'] ?? $data['address_complement'] ?? '',
        ]);

        // Montar street a partir de type + name se não vier direto
        $street = $data['address_street'] ?? null;
        if (!$street && (!empty($data['address_type']) || !empty($data['address_name']))) {
            $street = trim(($data['address_type'] ?? '') . ' ' . ($data['address_name'] ?? ''));
        }

        $insertData = [
            'person_type'          => $data['person_type'] ?? 'PF',
            'name'                 => $data['name'],
            'fantasy_name'         => $data['fantasy_name'] ?? null,
            'document'             => $data['document'] ?? null,
            'rg_ie'                => $data['rg_ie'] ?? null,
            'im'                   => $data['im'] ?? null,
            'birth_date'           => !empty($data['birth_date']) ? $data['birth_date'] : null,
            'gender'               => $data['gender'] ?? null,
            'email'                => $data['email'] ?? null,
            'email_secondary'      => $data['email_secondary'] ?? null,
            'phone'                => $data['phone'] ?? null,
            'cellphone'            => $data['cellphone'] ?? null,
            'phone_commercial'     => $data['phone_commercial'] ?? null,
            'website'              => $data['website'] ?? null,
            'instagram'            => $data['instagram'] ?? null,
            'contact_name'         => $data['contact_name'] ?? null,
            'contact_role'         => $data['contact_role'] ?? null,
            'address'              => $address,
            'zipcode'              => $data['zipcode'] ?? null,
            'address_street'       => $street,
            'address_number'       => $data['address_number'] ?? null,
            'address_complement'   => $data['complement'] ?? $data['address_complement'] ?? null,
            'address_neighborhood' => $data['neighborhood'] ?? $data['address_neighborhood'] ?? null,
            'address_city'         => $data['address_city'] ?? null,
            'address_state'        => $data['address_state'] ?? null,
            'address_country'      => $data['address_country'] ?? 'Brasil',
            'address_ibge'         => $data['address_ibge'] ?? null,
            'payment_term'         => $data['payment_term'] ?? null,
            'credit_limit'         => isset($data['credit_limit']) && $data['credit_limit'] !== '' ? $data['credit_limit'] : null,
            'discount_default'     => isset($data['discount_default']) && $data['discount_default'] !== '' ? $data['discount_default'] : null,
            'origin'               => $data['origin'] ?? 'Importação',
            'tags'                 => $data['tags'] ?? null,
            'observations'         => $data['observations'] ?? null,
            'status'               => $data['status'] ?? 'active',
            'created_by'           => $data['created_by'] ?? null,
            'import_batch_id'      => $data['import_batch_id'] ?? null,
        ];

        $newId = $this->create($insertData);

        if ($newId) {
            return $newId;
        }
        return false;
    }

    /**
     * Atualiza um cliente existente a partir de dados importados.
     * Usado pelos modos 'update' e 'create_or_update' da importação.
     * Atualiza apenas campos que foram fornecidos (não vazios).
     *
     * @param array $data Dados mapeados (deve conter 'id')
     * @return bool Sucesso ou falha
     */
    public function updateFromImport(array $data): bool
    {
        if (empty($data['id'])) {
            return false;
        }

        // Campos atualizáveis via importação
        $allowedFields = [
            'person_type', 'name', 'fantasy_name', 'document', 'rg_ie', 'im',
            'birth_date', 'gender', 'email', 'email_secondary',
            'phone', 'cellphone', 'phone_commercial', 'website', 'instagram',
            'contact_name', 'contact_role',
            'zipcode', 'address_street', 'address_number', 'address_complement',
            'address_neighborhood', 'address_city', 'address_state', 'address_country',
            'payment_term', 'credit_limit', 'discount_default',
            'origin', 'tags', 'observations', 'status',
        ];

        $sets = [];
        $params = [];

        foreach ($allowedFields as $field) {
            if (isset($data[$field]) && $data[$field] !== '') {
                // Sanitizar document
                if ($field === 'document') {
                    $data[$field] = preg_replace('/\D/', '', $data[$field]);
                }
                $sets[] = "{$field} = ?";
                $params[] = $data[$field];
            }
        }

        // Tratar campos compostos de endereço
        if (empty($data['address_street']) && (!empty($data['address_type']) || !empty($data['address_name']))) {
            $street = trim(($data['address_type'] ?? '') . ' ' . ($data['address_name'] ?? ''));
            if (!empty($street)) {
                $sets[] = "address_street = ?";
                $params[] = $street;
            }
        }

        if (empty($sets)) {
            return false;
        }

        $sets[] = "updated_at = NOW()";
        $params[] = (int) $data['id'];

        $query = "UPDATE {$this->table_name} SET " . implode(', ', $sets) . " WHERE id = ? AND deleted_at IS NULL";
        $stmt = $this->conn->prepare($query);
        $result = $stmt->execute($params);

        if ($result && $stmt->rowCount() > 0) {
            EventDispatcher::dispatch('model.customer.updated', new Event('model.customer.updated', [
                'id'    => $data['id'],
                'name'  => $data['name'] ?? '',
                'email' => $data['email'] ?? '',
            ]));
        }

        return $result;
    }
}
