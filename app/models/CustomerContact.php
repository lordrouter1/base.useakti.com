<?php
namespace Akti\Models;

use Akti\Core\EventDispatcher;
use Akti\Core\Event;
use PDO;

/**
 * Model: CustomerContact
 * Gerencia contatos adicionais de clientes (multi-contato PJ).
 * Tabela: customer_contacts
 *
 * Entradas: Conexão PDO ($db), parâmetros das funções.
 * Saídas: Arrays de dados, booleanos, IDs inseridos.
 * Eventos: 'model.customer_contact.created', 'model.customer_contact.updated', 'model.customer_contact.deleted'
 * Não deve conter HTML, echo, print ou acesso direto a $_POST/$_GET.
 */
class CustomerContact
{
    private $conn;
    private $table_name = 'customer_contacts';

    /**
     * Construtor do model
     * @param PDO $db Conexão PDO
     */
    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Cria um novo contato para um cliente.
     *
     * @param array $data Dados do contato: customer_id, name, role, email, phone, is_primary, notes
     * @return int ID do contato criado
     */
    public function create(array $data): int
    {
        // Se marcado como primário, desmarca os outros do mesmo cliente
        if (!empty($data['is_primary'])) {
            $this->clearPrimary((int) $data['customer_id']);
        }

        $query = "INSERT INTO {$this->table_name}
            (customer_id, name, role, email, phone, is_primary, notes, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            $data['customer_id'],
            $data['name'],
            $data['role'] ?? null,
            $data['email'] ?? null,
            $data['phone'] ?? null,
            !empty($data['is_primary']) ? 1 : 0,
            $data['notes'] ?? null,
        ]);

        $newId = (int) $this->conn->lastInsertId();

        EventDispatcher::dispatch('model.customer_contact.created', new Event('model.customer_contact.created', [
            'id'          => $newId,
            'customer_id' => $data['customer_id'],
            'name'        => $data['name'],
        ]));

        return $newId;
    }

    /**
     * Lista todos os contatos de um cliente.
     *
     * @param int $customerId ID do cliente
     * @return array Lista de contatos ordenados: primário primeiro, depois por nome
     */
    public function readByCustomer(int $customerId): array
    {
        $query = "SELECT * FROM {$this->table_name}
                  WHERE customer_id = :cid
                  ORDER BY is_primary DESC, name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':cid', $customerId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca um contato pelo ID.
     *
     * @param int $id ID do contato
     * @return array|null Dados do contato ou null
     */
    public function readOne(int $id): ?array
    {
        $query = "SELECT * FROM {$this->table_name} WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Atualiza um contato existente.
     *
     * @param array $data Dados com 'id', 'customer_id', 'name', 'role', 'email', 'phone', 'is_primary', 'notes'
     * @return bool Sucesso ou falha
     */
    public function update(array $data): bool
    {
        // Se marcado como primário, desmarca os outros do mesmo cliente
        if (!empty($data['is_primary']) && !empty($data['customer_id'])) {
            $this->clearPrimary((int) $data['customer_id']);
        }

        $query = "UPDATE {$this->table_name}
                  SET name = ?, role = ?, email = ?, phone = ?, is_primary = ?, notes = ?
                  WHERE id = ?";

        $stmt = $this->conn->prepare($query);
        $result = $stmt->execute([
            $data['name'],
            $data['role'] ?? null,
            $data['email'] ?? null,
            $data['phone'] ?? null,
            !empty($data['is_primary']) ? 1 : 0,
            $data['notes'] ?? null,
            $data['id'],
        ]);

        if ($result) {
            EventDispatcher::dispatch('model.customer_contact.updated', new Event('model.customer_contact.updated', [
                'id'   => $data['id'],
                'name' => $data['name'],
            ]));
        }

        return $result;
    }

    /**
     * Remove um contato.
     *
     * @param int $id ID do contato
     * @return bool Sucesso ou falha
     */
    public function delete(int $id): bool
    {
        $query = "DELETE FROM {$this->table_name} WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $result = $stmt->execute();

        if ($result) {
            EventDispatcher::dispatch('model.customer_contact.deleted', new Event('model.customer_contact.deleted', [
                'id' => $id,
            ]));
        }

        return $result;
    }

    /**
     * Define um contato como principal (desmarca todos os outros do mesmo cliente).
     *
     * @param int $id ID do contato a ser definido como primário
     * @param int $customerId ID do cliente
     * @return bool Sucesso ou falha
     */
    public function setPrimary(int $id, int $customerId): bool
    {
        $this->clearPrimary($customerId);

        $query = "UPDATE {$this->table_name} SET is_primary = 1 WHERE id = :id AND customer_id = :cid";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':cid', $customerId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Conta o total de contatos de um cliente.
     *
     * @param int $customerId ID do cliente
     * @return int Total de contatos
     */
    public function countByCustomer(int $customerId): int
    {
        $query = "SELECT COUNT(*) FROM {$this->table_name} WHERE customer_id = :cid";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':cid', $customerId, PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /**
     * Retorna o contato principal de um cliente (se houver).
     *
     * @param int $customerId ID do cliente
     * @return array|null Dados do contato principal ou null
     */
    public function getPrimary(int $customerId): ?array
    {
        $query = "SELECT * FROM {$this->table_name}
                  WHERE customer_id = :cid AND is_primary = 1
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':cid', $customerId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // ──────────────────────────────────────────────
    // Helpers privados
    // ──────────────────────────────────────────────

    /**
     * Remove a flag is_primary de todos os contatos de um cliente.
     *
     * @param int $customerId ID do cliente
     * @return void
     */
    private function clearPrimary(int $customerId): void
    {
        $query = "UPDATE {$this->table_name} SET is_primary = 0 WHERE customer_id = :cid";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':cid', $customerId, PDO::PARAM_INT);
        $stmt->execute();
    }
}
