<?php
namespace Akti\Models;

use Akti\Core\EventDispatcher;
use Akti\Core\Event;
use PDO;

/**
 * Model: PortalMessage
 * Gerencia mensagens entre cliente e empresa pelo Portal.
 * Entradas: Conexão PDO ($db), parâmetros das funções.
 * Saídas: Arrays de dados, booleanos, IDs inseridos.
 * Eventos: 'portal.message.sent'
 * Não deve conter HTML, echo, print ou acesso direto a $_POST/$_GET.
 */
class PortalMessage
{
    private $conn;
    private $table = 'customer_portal_messages';

    /**
     * Construtor do model
     * @param PDO $db Conexão PDO
     */
    public function __construct(\PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * Cria uma nova mensagem
     * @param array $data [customer_id, order_id, sender_type, sender_id, message, attachment_path]
     * @return int ID da mensagem criada
     */
    public function create(array $data): int
    {
        $query = "INSERT INTO {$this->table}
                  (customer_id, order_id, sender_type, sender_id, message, attachment_path, created_at)
                  VALUES (:customer_id, :order_id, :sender_type, :sender_id, :message, :attachment_path, NOW())";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':customer_id'    => $data['customer_id'],
            ':order_id'       => $data['order_id'] ?? null,
            ':sender_type'    => $data['sender_type'] ?? 'customer',
            ':sender_id'      => $data['sender_id'] ?? null,
            ':message'        => $data['message'],
            ':attachment_path' => $data['attachment_path'] ?? null,
        ]);

        $newId = (int) $this->conn->lastInsertId();

        EventDispatcher::dispatch('portal.message.sent', new Event('portal.message.sent', [
            'id'          => $newId,
            'customer_id' => $data['customer_id'],
            'order_id'    => $data['order_id'] ?? null,
            'sender_type' => $data['sender_type'] ?? 'customer',
        ]));

        return $newId;
    }

    /**
     * Retorna mensagens de um cliente (opcionalmente filtradas por pedido)
     * @param int $customerId
     * @param int|null $orderId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getByCustomer(int $customerId, ?int $orderId = null, int $limit = 50, int $offset = 0): array
    {
        $where = "WHERE m.customer_id = :cid";
        $params = [':cid' => $customerId];

        if ($orderId !== null) {
            $where .= " AND m.order_id = :oid";
            $params[':oid'] = $orderId;
        }

        $query = "SELECT m.*, u.name AS admin_name
                  FROM {$this->table} m
                  LEFT JOIN users u ON m.sender_type = 'admin' AND u.id = m.sender_id
                  {$where}
                  ORDER BY m.created_at ASC
                  LIMIT :lim OFFSET :off";
        $stmt = $this->conn->prepare($query);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Conta mensagens não lidas de um cliente
     * @param int $customerId
     * @return int
     */
    public function countUnread(int $customerId): int
    {
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) FROM {$this->table}
             WHERE customer_id = :cid AND sender_type = 'admin' AND is_read = 0"
        );
        $stmt->execute([':cid' => $customerId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Marca mensagens de admin como lidas para um cliente
     * @param int $customerId
     * @param int|null $orderId Se informado, marca apenas mensagens daquele pedido
     * @return int Número de mensagens atualizadas
     */
    public function markAsRead(int $customerId, ?int $orderId = null): int
    {
        $where = "customer_id = :cid AND sender_type = 'admin' AND is_read = 0";
        $params = [':cid' => $customerId];

        if ($orderId !== null) {
            $where .= " AND order_id = :oid";
            $params[':oid'] = $orderId;
        }

        $stmt = $this->conn->prepare(
            "UPDATE {$this->table} SET is_read = 1, read_at = NOW() WHERE {$where}"
        );
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Retorna uma mensagem pelo ID (com validação de customer_id)
     * @param int $id
     * @param int $customerId
     * @return array|null
     */
    public function findById(int $id, int $customerId): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM {$this->table} WHERE id = :id AND customer_id = :cid LIMIT 1"
        );
        $stmt->execute([':id' => $id, ':cid' => $customerId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Conta mensagens não lidas de clientes (para painel admin)
     * @return int
     */
    public function countUnreadFromCustomers(): int
    {
        $stmt = $this->conn->query(
            "SELECT COUNT(*) FROM {$this->table} WHERE sender_type = 'customer' AND is_read = 0"
        );
        return (int) $stmt->fetchColumn();
    }
}
