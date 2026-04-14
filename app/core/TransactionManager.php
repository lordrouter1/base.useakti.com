<?php

namespace Akti\Core;

use PDO;

class TransactionManager
{
    private PDO $db;
    private int $level = 0;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function begin(): void
    {
        if ($this->level === 0) {
            $this->db->beginTransaction();
        } else {
            $this->db->exec("SAVEPOINT sp{$this->level}");
        }
        $this->level++;
    }

    public function commit(): void
    {
        if ($this->level <= 0) {
            throw new \LogicException('Cannot commit: no active transaction.');
        }
        $this->level--;
        if ($this->level === 0) {
            $this->db->commit();
        } else {
            $this->db->exec("RELEASE SAVEPOINT sp{$this->level}");
        }
    }

    public function rollBack(): void
    {
        if ($this->level <= 0) {
            throw new \LogicException('Cannot rollback: no active transaction.');
        }
        $this->level--;
        if ($this->level === 0) {
            $this->db->rollBack();
        } else {
            $this->db->exec("ROLLBACK TO SAVEPOINT sp{$this->level}");
        }
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function inTransaction(): bool
    {
        return $this->level > 0;
    }

    /**
     * Execute a callable within a transaction, with automatic commit/rollback.
     *
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public function transaction(callable $callback): mixed
    {
        $this->begin();
        try {
            $result = $callback();
            $this->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->rollBack();
            error_log("[ROLLBACK] TransactionManager::transaction level={$this->level} - " . $e->getMessage());
            throw $e;
        }
    }
}
