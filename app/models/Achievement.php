<?php

namespace Akti\Models;

use PDO;

/**
 * Model de conquistas/gamificação do sistema.
 */
class Achievement
{
    private PDO $conn;

    /**
     * Construtor da classe Achievement.
     *
     * @param PDO $db Conexão PDO com o banco de dados
     */
    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * Cria um novo registro no banco de dados.
     *
     * @param array $data Dados para processamento
     * @return int
     */
    public function create(array $data): int
    {
        $stmt = $this->conn->prepare("
            INSERT INTO achievements (tenant_id, name, description, icon, badge_color, metric_type, threshold_value, points)
            VALUES (:tenant_id, :name, :description, :icon, :badge_color, :metric_type, :threshold_value, :points)
        ");
        $stmt->execute([
            ':tenant_id'       => $data['tenant_id'],
            ':name'            => $data['name'],
            ':description'     => $data['description'] ?? null,
            ':icon'            => $data['icon'] ?? 'fas fa-trophy',
            ':badge_color'     => $data['badge_color'] ?? '#ffc107',
            ':metric_type'     => $data['metric_type'],
            ':threshold_value' => $data['threshold_value'],
            ':points'          => $data['points'] ?? 10,
        ]);
        return (int) $this->conn->lastInsertId();
    }

    /**
     * Retorna todos os registros.
     *
     * @param int $tenantId ID do tenant
     * @return array
     */
    public function readAll(int $tenantId): array
    {
        $stmt = $this->conn->prepare("SELECT * FROM achievements WHERE tenant_id = :tid AND is_active = 1 ORDER BY name");
        $stmt->execute([':tid' => $tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna um registro pelo ID.
     *
     * @param int $id ID do registro
     * @param int $tenantId ID do tenant
     * @return array|null
     */
    public function readOne(int $id, int $tenantId): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM achievements WHERE id = :id AND tenant_id = :tid");
        $stmt->execute([':id' => $id, ':tid' => $tenantId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Atualiza um registro existente.
     *
     * @param int $id ID do registro
     * @param int $tenantId ID do tenant
     * @param array $data Dados para processamento
     * @return bool
     */
    public function update(int $id, int $tenantId, array $data): bool
    {
        $stmt = $this->conn->prepare("
            UPDATE achievements SET name = :name, description = :description, icon = :icon, badge_color = :badge_color, metric_type = :metric_type, threshold_value = :threshold_value, points = :points, is_active = :is_active
            WHERE id = :id AND tenant_id = :tid
        ");
        return $stmt->execute([
            ':name'            => $data['name'],
            ':description'     => $data['description'] ?? null,
            ':icon'            => $data['icon'] ?? 'fas fa-trophy',
            ':badge_color'     => $data['badge_color'] ?? '#ffc107',
            ':metric_type'     => $data['metric_type'],
            ':threshold_value' => $data['threshold_value'],
            ':points'          => $data['points'] ?? 10,
            ':is_active'       => $data['is_active'] ?? 1,
            ':id'              => $id,
            ':tid'             => $tenantId,
        ]);
    }

    /**
     * Remove um registro pelo ID.
     *
     * @param int $id ID do registro
     * @param int $tenantId ID do tenant
     * @return bool
     */
    public function delete(int $id, int $tenantId): bool
    {
        $stmt = $this->conn->prepare("UPDATE achievements SET is_active = 0 WHERE id = :id AND tenant_id = :tid");
        return $stmt->execute([':id' => $id, ':tid' => $tenantId]);
    }

    /**
     * Award achievement.
     *
     * @param int $userId ID do usuário
     * @param int $achievementId Achievement id
     * @param int $tenantId ID do tenant
     * @return bool
     */
    public function awardAchievement(int $userId, int $achievementId, int $tenantId): bool
    {
        $stmt = $this->conn->prepare("
            INSERT IGNORE INTO user_achievements (tenant_id, user_id, achievement_id) VALUES (:tid, :uid, :aid)
        ");
        $result = $stmt->execute([':tid' => $tenantId, ':uid' => $userId, ':aid' => $achievementId]);

        if ($stmt->rowCount() > 0) {
            $ach = $this->readOne($achievementId, $tenantId);
            if ($ach) {
                $this->addPoints($userId, $tenantId, $ach['points']);
            }
        }
        return $result;
    }

    /**
     * Add points.
     *
     * @param int $userId ID do usuário
     * @param int $tenantId ID do tenant
     * @param int $points Points
     * @return void
     */
    public function addPoints(int $userId, int $tenantId, int $points): void
    {
        $stmt = $this->conn->prepare("
            INSERT INTO user_scores (tenant_id, user_id, total_points, period_type)
            VALUES (:tid, :uid, :pts, 'all_time')
            ON DUPLICATE KEY UPDATE total_points = total_points + :pts2, level = FLOOR((total_points + :pts3) / 100) + 1
        ");
        $stmt->execute([':tid' => $tenantId, ':uid' => $userId, ':pts' => $points, ':pts2' => $points, ':pts3' => $points]);
    }

    /**
     * Obtém dados específicos.
     *
     * @param int $tenantId ID do tenant
     * @param int $limit Limite de registros
     * @return array
     */
    public function getLeaderboard(int $tenantId, int $limit = 10): array
    {
        $stmt = $this->conn->prepare("
            SELECT us.*, u.name AS user_name
            FROM user_scores us
            JOIN users u ON us.user_id = u.id
            WHERE us.tenant_id = :tid AND us.period_type = 'all_time'
            ORDER BY us.total_points DESC
            LIMIT :lim
        ");
        $stmt->bindValue(':tid', $tenantId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtém dados específicos.
     *
     * @param int $userId ID do usuário
     * @param int $tenantId ID do tenant
     * @return array
     */
    public function getUserAchievements(int $userId, int $tenantId): array
    {
        $stmt = $this->conn->prepare("
            SELECT a.*, ua.earned_at
            FROM user_achievements ua
            JOIN achievements a ON ua.achievement_id = a.id
            WHERE ua.user_id = :uid AND ua.tenant_id = :tid
            ORDER BY ua.earned_at DESC
        ");
        $stmt->execute([':uid' => $userId, ':tid' => $tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtém dados específicos.
     *
     * @param int $userId ID do usuário
     * @param int $tenantId ID do tenant
     * @return array
     */
    public function getUserScore(int $userId, int $tenantId): array
    {
        $stmt = $this->conn->prepare("
            SELECT * FROM user_scores WHERE user_id = :uid AND tenant_id = :tid AND period_type = 'all_time' LIMIT 1
        ");
        $stmt->execute([':uid' => $userId, ':tid' => $tenantId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total_points' => 0, 'level' => 1];
    }
}
