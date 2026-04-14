<?php
namespace Akti\Models;

/**
 * Notification Model
 * 
 * Gerencia notificações em tempo real para os usuários.
 * Tipos: order_delayed, payment_received, stock_low, new_order, system, custom.
 */
class Notification
{
    /** @var \PDO */
    private $db;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Cria uma nova notificação.
     *
     * @param int    $userId  Destinatário
     * @param string $type    Tipo da notificação
     * @param string $title   Título
     * @param string $message Mensagem (opcional)
     * @param array  $data    Metadata JSON (opcional)
     * @return int|false ID inserido ou false
     */
    public function create(int $userId, string $type, string $title, string $message = '', array $data = []): int|false
    {
        $sql = "INSERT INTO notifications (user_id, type, title, message, data, created_at)
                VALUES (:user_id, :type, :title, :message, :data, NOW())";

        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            ':user_id' => $userId,
            ':type'    => $type,
            ':title'   => $title,
            ':message' => $message ?: null,
            ':data'    => !empty($data) ? json_encode($data) : null,
        ]);

        return $result ? (int) $this->db->lastInsertId() : false;
    }

    /**
     * Lista notificações de um usuário (mais recentes primeiro).
     *
     * @param int  $userId
     * @param int  $limit
     * @param bool $unreadOnly Se true, retorna apenas não-lidas
     * @return array
     */
    public function getByUser(int $userId, int $limit = 20, bool $unreadOnly = false): array
    {
        $sql = "SELECT * FROM notifications WHERE user_id = :user_id";
        if ($unreadOnly) {
            $sql .= " AND read_at IS NULL";
        }
        $sql .= " ORDER BY created_at DESC LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            if ($row['data']) {
                $row['data'] = json_decode($row['data'], true);
            }
        }
        return $rows;
    }

    /**
     * Conta notificações não-lidas de um usuário.
     *
     * @param int $userId
     * @return int
     */
    public function countUnread(int $userId): int
    {
        $sql = "SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND read_at IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Marca uma notificação como lida.
     *
     * @param int $id
     * @param int $userId (segurança: só o dono pode marcar)
     * @return bool
     */
    public function markAsRead(int $id, int $userId): bool
    {
        $sql = "UPDATE notifications SET read_at = NOW() WHERE id = :id AND user_id = :user_id AND read_at IS NULL";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id, ':user_id' => $userId]);
    }

    /**
     * Marca todas as notificações de um usuário como lidas.
     *
     * @param int $userId
     * @return bool
     */
    public function markAllAsRead(int $userId): bool
    {
        $sql = "UPDATE notifications SET read_at = NOW() WHERE user_id = :user_id AND read_at IS NULL";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':user_id' => $userId]);
    }

    /**
     * Exclui notificações antigas (mais de X dias).
     *
     * @param int $daysOld
     * @return int Número de registros removidos
     */
    public function deleteOld(int $daysOld = 90): int
    {
        $sql = "DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':days' => $daysOld]);
        return $stmt->rowCount();
    }

    /**
     * Retorna uma notificação pelo ID.
     *
     * @param int $id
     * @return array|null
     */
    public function readOne(int $id): ?array
    {
        $sql = "SELECT * FROM notifications WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($row && $row['data']) {
            $row['data'] = json_decode($row['data'], true);
        }
        return $row ?: null;
    }

    /**
     * Envia notificação para múltiplos usuários (broadcast).
     *
     * @param array  $userIds
     * @param string $type
     * @param string $title
     * @param string $message
     * @param array  $data
     * @return int Número de notificações criadas
     */
    public function broadcast(array $userIds, string $type, string $title, string $message = '', array $data = []): int
    {
        $count = 0;
        foreach ($userIds as $uid) {
            if ($this->create((int) $uid, $type, $title, $message, $data)) {
                $count++;
            }
        }
        return $count;
    }
}
