<?php
namespace Akti\Models;

use Akti\Core\Log;

use Akti\Core\EventDispatcher;
use Akti\Core\Event;
use PDO;

/**
 * Model para checklist de preparação de pedidos.
 * Controla as etapas de preparo, conferência e verificação antes do envio.
 */
class OrderPreparation {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Verifica se a tabela existe (DDL movida para /sql).
     */
    public function createTableIfNotExists() {
        // DDL movida para sql/update_202603301000_extract_ddl_from_models.sql
        try {
            $this->conn->query("SELECT 1 FROM order_preparation_checklist LIMIT 1");
        } catch (\Exception $e) {
            Log::warning('OrderPreparation: Tabela order_preparation_checklist não encontrada. Execute as migrations pendentes.');
        }
    }

    /**
     * Obter checklist de um pedido como array associativo
     * Retorna: ['check_key' => 1/0, 'check_key_by' => 'Nome', 'check_key_at' => 'datetime', ...]
     */
    public function getChecklist($orderId) {
        $this->createTableIfNotExists();
        
        $sql = "SELECT opc.check_key, opc.checked, opc.checked_at, u.name as checked_by_name
                FROM order_preparation_checklist opc
                LEFT JOIN users u ON opc.checked_by = u.id
                WHERE opc.order_id = :oid";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':oid' => $orderId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($rows as $row) {
            $result[$row['check_key']] = (int)$row['checked'];
            $result[$row['check_key'] . '_by'] = $row['checked_by_name'];
            $result[$row['check_key'] . '_at'] = $row['checked_at'];
        }
        return $result;
    }

    /**
     * Alternar o status de uma etapa do checklist (toggle)
     */
    public function toggle($orderId, $key, $userId) {
        $this->createTableIfNotExists();

        // Verificar se já existe
        $stmt = $this->conn->prepare("SELECT id, checked FROM order_preparation_checklist WHERE order_id = :oid AND check_key = :key");
        $stmt->execute([':oid' => $orderId, ':key' => $key]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $newChecked = $existing['checked'] ? 0 : 1;
            $updSql = "UPDATE order_preparation_checklist 
                       SET checked = :checked, 
                           checked_by = :uid, 
                           checked_at = " . ($newChecked ? "NOW()" : "NULL") . "
                       WHERE order_id = :oid AND check_key = :key";
            $updStmt = $this->conn->prepare($updSql);
            $updStmt->execute([
                ':checked' => $newChecked,
                ':uid' => $newChecked ? $userId : null,
                ':oid' => $orderId,
                ':key' => $key
            ]);
            EventDispatcher::dispatch('model.preparation_checklist.toggled', new Event('model.preparation_checklist.toggled', [
                'order_id' => $orderId,
                'check_key' => $key,
                'checked' => $newChecked,
                'user_id' => $userId,
            ]));
            return $newChecked;
        } else {
            // Inserir como marcado
            $insSql = "INSERT INTO order_preparation_checklist (order_id, check_key, checked, checked_by, checked_at)
                       VALUES (:oid, :key, 1, :uid, NOW())";
            $insStmt = $this->conn->prepare($insSql);
            $insStmt->execute([
                ':oid' => $orderId,
                ':key' => $key,
                ':uid' => $userId
            ]);
            EventDispatcher::dispatch('model.preparation_checklist.toggled', new Event('model.preparation_checklist.toggled', [
                'order_id' => $orderId,
                'check_key' => $key,
                'checked' => 1,
                'user_id' => $userId,
            ]));
            return 1;
        }
    }
}
