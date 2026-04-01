<?php
namespace Akti\Models;

use PDO;

/**
 * FinancialSchema — verificação de schema/tabelas financeiras.
 *
 * Extrai a responsabilidade de checagem de schema do model Financial monolítico.
 */
class FinancialSchema
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Proxy para Financial::hasSoftDeleteColumn().
     */
    public function hasSoftDeleteColumn(): bool
    {
        return (new Financial($this->conn))->hasSoftDeleteColumn();
    }
}
