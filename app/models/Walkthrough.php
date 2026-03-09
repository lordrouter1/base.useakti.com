<?php
namespace Akti\Models;
use PDO;

class Walkthrough {

    private $conn;
    private $table = 'user_walkthrough';

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Verifica se o usuário precisa ver o walkthrough.
     * Retorna true se o walkthrough nunca foi completado nem pulado.
     */
    public function needsWalkthrough(int $userId): bool {
        $query = "SELECT id, completed, skipped FROM {$this->table} WHERE user_id = :user_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Se não existe registro, é o primeiro acesso
        if (!$row) {
            return true;
        }

        // Se já completou ou pulou, não precisa
        return (!$row['completed'] && !$row['skipped']);
    }

    /**
     * Retorna o estado atual do walkthrough do usuário.
     */
    public function getStatus(int $userId): ?array {
        $query = "SELECT * FROM {$this->table} WHERE user_id = :user_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Inicia o walkthrough para o usuário (cria registro se não existir).
     */
    public function start(int $userId): bool {
        $existing = $this->getStatus($userId);

        if (!$existing) {
            $query = "INSERT INTO {$this->table} (user_id, completed, skipped, current_step) VALUES (:user_id, 0, 0, 0)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            return $stmt->execute();
        }

        // Reset se já existia
        $query = "UPDATE {$this->table} SET completed = 0, skipped = 0, current_step = 0, completed_at = NULL WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Marca o walkthrough como completo.
     */
    public function complete(int $userId): bool {
        $this->ensureRecord($userId);

        $query = "UPDATE {$this->table} SET completed = 1, skipped = 0, completed_at = NOW() WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Marca o walkthrough como pulado.
     */
    public function skip(int $userId): bool {
        $this->ensureRecord($userId);

        $query = "UPDATE {$this->table} SET skipped = 1, completed_at = NOW() WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Salva o passo atual (para retomar depois se fechar).
     */
    public function saveStep(int $userId, int $step): bool {
        $this->ensureRecord($userId);

        $query = "UPDATE {$this->table} SET current_step = :step WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':step', $step, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Garante que existe um registro para o usuário.
     */
    private function ensureRecord(int $userId): void {
        $existing = $this->getStatus($userId);
        if (!$existing) {
            $query = "INSERT INTO {$this->table} (user_id) VALUES (:user_id)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
        }
    }

    /**
     * Reseta o walkthrough para que o usuário veja novamente.
     */
    public function reset(int $userId): bool {
        return $this->start($userId);
    }
}
