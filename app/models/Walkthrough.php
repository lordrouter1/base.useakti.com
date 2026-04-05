<?php
namespace Akti\Models;

use Akti\Core\EventDispatcher;
use Akti\Core\Event;
use PDO;

/**
 * Classe Walkthrough
 *
 * Responsável pelo controle do walkthrough (tour guiado) do usuário.
 * Permite verificar, iniciar, completar, pular, salvar passo e resetar o walkthrough.
 * Emite eventos via EventDispatcher após operações relevantes.
 *
 * Observações:
 * - O registro é salvo na tabela 'user_walkthrough'.
 * - Métodos garantem a existência do registro antes de atualizar.
 * - Recomendado validar o fluxo no Controller.
 *
 * @package Akti\Models
 */
class Walkthrough {
    /**
     * Conexão PDO
     * @var PDO
     */
    private $conn;
    /**
     * Nome da tabela de walkthrough
     * @var string
     */
    private $table = 'user_walkthrough';

    /**
     * Construtor
     * @param PDO $db Conexão PDO
     */
    public function __construct(\PDO $db) {
        $this->conn = $db;
    }

    /**
     * Verifica se o usuário precisa ver o walkthrough.
     * Emite evento 'model.walkthrough.needs'
     * Retorna true se nunca foi completado nem pulado.
     * @param int $userId
     * @return bool
     */
    public function needsWalkthrough(int $userId): bool {
        $query = "SELECT id, completed, skipped FROM {$this->table} WHERE user_id = :user_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Se não existe registro, é o primeiro acesso
        if (!$row) {
            EventDispatcher::dispatch('model.walkthrough.needs', new Event('model.walkthrough.needs', [
                'user_id' => $userId,
            ]));
            return true;
        }

        // Se já completou ou pulou, não precisa
        return (!$row['completed'] && !$row['skipped']);
    }

    /**
     * Retorna o estado atual do walkthrough do usuário.
     * @param int $userId
     * @return array|null
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
     * Emite evento 'model.walkthrough.init'
     * @param int $userId
     * @return bool
     */
    public function start(int $userId): bool {
        $existing = $this->getStatus($userId);

        EventDispatcher::dispatch('model.walkthrough.init', new Event('model.walkthrough.init', [
            'user_id' => $userId,
            'status' => $existing
        ]));


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
     * Emite evento 'model.walkthrough.completed'.
     * @param int $userId
     * @return bool
     */
    public function complete(int $userId): bool {
        $this->ensureRecord($userId);

        $query = "UPDATE {$this->table} SET completed = 1, skipped = 0, completed_at = NOW() WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $result = $stmt->execute();
        if ($result) {
            EventDispatcher::dispatch('model.walkthrough.completed', new Event('model.walkthrough.completed', [
                'user_id' => $userId,
            ]));
        }
        return $result;
    }

    /**
     * Marca o walkthrough como pulado.
     * Emite evento 'model.walkthrough.skipped'.
     * @param int $userId
     * @return bool
     */
    public function skip(int $userId): bool {
        $this->ensureRecord($userId);

        $query = "UPDATE {$this->table} SET skipped = 1, completed_at = NOW() WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $result = $stmt->execute();
        if ($result) {
            EventDispatcher::dispatch('model.walkthrough.skipped', new Event('model.walkthrough.skipped', [
                'user_id' => $userId,
            ]));
        }
        return $result;
    }

    /**
     * Salva o passo atual do walkthrough.
     * @param int $userId
     * @param int $step
     * @return bool
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
     * @param int $userId
     * @return void
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
     * @param int $userId
     * @return bool
     */
    public function reset(int $userId): bool {
        return $this->start($userId);
    }
}
