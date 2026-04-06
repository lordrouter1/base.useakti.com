<?php
namespace Akti\Models;

use Akti\Core\Log;

use Akti\Core\EventDispatcher;
use Akti\Core\Event;
use Akti\Services\FileManager;
use PDO;
use TenantManager;

/**
 * Model para logs/histórico de itens de pedido (por produto)
 * Permite registrar textos, imagens e PDFs vinculados a cada item do pedido.
 */
class OrderItemLog {
    private $conn;
    
    // Tipos de arquivo permitidos
    public static $allowedTypes = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'application/pdf'
    ];
    
    public static $maxFileSize = 10485760; // 10MB

    public function __construct(\PDO $db) {
        $this->conn = $db;
    }

    /**
     * Verifica se a tabela existe (DDL movida para /sql).
     */
    public function createTableIfNotExists() {
        // DDL movida para sql/update_202603301000_extract_ddl_from_models.sql
        // Mantém método para compatibilidade — apenas verifica existência
        try {
            $this->conn->query("SELECT 1 FROM order_item_logs LIMIT 1");
        } catch (\Exception $e) {
            Log::warning('OrderItemLog: Tabela order_item_logs não encontrada. Execute as migrations pendentes.');
        }
    }

    /**
     * Adicionar log a um item do pedido
     */
    public function addLog($orderId, $orderItemId, $userId, $message = null, $filePath = null, $fileName = null, $fileType = null) {
        $sql = "INSERT INTO order_item_logs (order_id, order_item_id, user_id, message, file_path, file_name, file_type)
                VALUES (:oid, :iid, :uid, :msg, :fpath, :fname, :ftype)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':oid'   => $orderId,
            ':iid'   => $orderItemId,
            ':uid'   => $userId,
            ':msg'   => $message,
            ':fpath' => $filePath,
            ':fname' => $fileName,
            ':ftype' => $fileType
        ]);
        $newId = $this->conn->lastInsertId();
        EventDispatcher::dispatch('model.order_item_log.created', new Event('model.order_item_log.created', [
            'id' => $newId,
            'order_id' => $orderId,
            'order_item_id' => $orderItemId,
            'user_id' => $userId,
        ]));
        return $newId;
    }

    /**
     * Buscar logs de um item específico (para modal do painel de produção)
     */
    public function getLogsByItem($orderItemId) {
        $sql = "SELECT l.*, u.name as user_name,
                       p.name as product_name, oi.quantity,
                       o.id as order_id
                FROM order_item_logs l
                LEFT JOIN users u ON l.user_id = u.id
                LEFT JOIN order_items oi ON l.order_item_id = oi.id
                LEFT JOIN products p ON oi.product_id = p.id
                LEFT JOIN orders o ON l.order_id = o.id
                WHERE l.order_item_id = :iid
                ORDER BY l.created_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':iid' => $orderItemId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar todos os logs de todos os itens de um pedido (para detalhe do pedido)
     */
    public function getLogsByOrder($orderId) {
        $sql = "SELECT l.*, u.name as user_name,
                       p.name as product_name, oi.quantity, oi.product_id
                FROM order_item_logs l
                LEFT JOIN users u ON l.user_id = u.id
                LEFT JOIN order_items oi ON l.order_item_id = oi.id
                LEFT JOIN products p ON oi.product_id = p.id
                WHERE l.order_id = :oid
                ORDER BY l.created_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':oid' => $orderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Contar logs por item (para badge no painel de produção)
     */
    public function countLogsByItem($orderItemId) {
        $sql = "SELECT COUNT(*) FROM order_item_logs WHERE order_item_id = :iid";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':iid' => $orderItemId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Contar logs agrupados por item para um pedido (batch)
     */
    public function countLogsByOrderGrouped($orderId) {
        $sql = "SELECT order_item_id, COUNT(*) as total 
                FROM order_item_logs 
                WHERE order_id = :oid 
                GROUP BY order_item_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':oid' => $orderId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $counts = [];
        foreach ($rows as $r) {
            $counts[$r['order_item_id']] = (int)$r['total'];
        }
        return $counts;
    }

    /**
     * Excluir um log (e seu arquivo se existir)
     */
    public function deleteLog($logId, $userId = null) {
        // Buscar log para pegar caminho do arquivo
        $stmt = $this->conn->prepare("SELECT * FROM order_item_logs WHERE id = :id");
        $stmt->execute([':id' => $logId]);
        $log = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$log) return false;

        // Remover arquivo via FileManager
        if (!empty($log['file_path'])) {
            $fileManager = new FileManager($this->conn);
            $fileManager->delete($log['file_path']);
        }

        $del = $this->conn->prepare("DELETE FROM order_item_logs WHERE id = :id");
        $del->execute([':id' => $logId]);
        $deleted = $del->rowCount() > 0;
        if ($deleted) {
            EventDispatcher::dispatch('model.order_item_log.deleted', new Event('model.order_item_log.deleted', [
                'id' => $logId,
                'order_id' => $log['order_id'] ?? null,
                'order_item_id' => $log['order_item_id'] ?? null,
            ]));
        }
        return $deleted;
    }

    /**
     * Upload de arquivo e retorna dados do arquivo
     */
    public function handleFileUpload($file, $orderId, $orderItemId) {
        if (empty($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $fileManager = new FileManager($this->conn);
        $result = $fileManager->upload($file, 'item_logs', [
            'subdirectory' => 'item_logs/' . $orderId . '/' . $orderItemId,
            'entityType'   => 'order_item',
            'entityId'     => $orderItemId,
        ]);

        if (!$result['success']) {
            return ['error' => $result['error'] ?? 'Falha ao mover o arquivo.'];
        }

        return [
            'file_path' => $result['path'],
            'file_name' => $result['original_name'],
            'file_type' => $result['mime_type'],
        ];
    }
}
