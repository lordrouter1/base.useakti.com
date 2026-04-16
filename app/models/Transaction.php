<?php
namespace Akti\Models;

use PDO;

/**
 * Transaction — CRUD e consultas de transações financeiras.
 *
 * Extrai a responsabilidade de transações do model Financial monolítico.
 * Cada método é autocontido com prepared statements.
 */
class Transaction
{
    private $conn;

    /**
     * Construtor da classe Transaction.
     *
     * @param \PDO $db Conexão PDO com o banco de dados
     */
    public function __construct(\PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * Proxy para Financial::addTransaction().
     * Delega ao Financial original para manter backward compatibility.
     */
    public function create(array $data): mixed
    {
        return (new Financial($this->conn))->addTransaction($data);
    }

    /**
     * Proxy para Financial::getTransactionById().
     */
    public function readOne(int $id): mixed
    {
        return (new Financial($this->conn))->getTransactionById($id);
    }

    /**
     * Proxy para Financial::updateTransaction().
     */
    public function update(int $id, array $data): bool
    {
        return (new Financial($this->conn))->updateTransaction($id, $data);
    }

    /**
     * Proxy para Financial::deleteTransaction().
     */
    public function delete(int $id, ?string $reason = null): bool
    {
        return (new Financial($this->conn))->deleteTransaction($id, $reason);
    }

    /**
     * Proxy para Financial::restoreTransaction().
     */
    public function restore(int $id): bool
    {
        return (new Financial($this->conn))->restoreTransaction($id);
    }

    /**
     * Proxy para Financial::getTransactions().
     */
    public function getAll(array $filters = []): array
    {
        return (new Financial($this->conn))->getTransactions($filters);
    }

    /**
     * Proxy para Financial::getTransactionsPaginated().
     */
    public function getPaginated(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        return (new Financial($this->conn))->getTransactionsPaginated($filters, $page, $perPage);
    }
}
