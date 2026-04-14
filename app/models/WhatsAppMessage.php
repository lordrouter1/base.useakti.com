<?php

namespace Akti\Models;

use PDO;

class WhatsAppMessage
{
    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function getConfig(int $tenantId): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM whatsapp_configs WHERE tenant_id = :tid LIMIT 1");
        $stmt->execute([':tid' => $tenantId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function saveConfig(int $tenantId, array $data): bool
    {
        $existing = $this->getConfig($tenantId);
        if ($existing) {
            $stmt = $this->conn->prepare("
                UPDATE whatsapp_configs SET provider = :provider, api_url = :api_url, api_key = :api_key, instance_name = :instance_name, phone_number_id = :phone_number_id, is_active = :is_active
                WHERE tenant_id = :tid
            ");
        } else {
            $stmt = $this->conn->prepare("
                INSERT INTO whatsapp_configs (tenant_id, provider, api_url, api_key, instance_name, phone_number_id, is_active)
                VALUES (:tid, :provider, :api_url, :api_key, :instance_name, :phone_number_id, :is_active)
            ");
        }
        return $stmt->execute([
            ':tid'             => $tenantId,
            ':provider'        => $data['provider'] ?? 'evolution_api',
            ':api_url'         => $data['api_url'] ?? null,
            ':api_key'         => $data['api_key'] ?? null,
            ':instance_name'   => $data['instance_name'] ?? null,
            ':phone_number_id' => $data['phone_number_id'] ?? null,
            ':is_active'       => $data['is_active'] ?? 0,
        ]);
    }

    public function getTemplates(int $tenantId): array
    {
        $stmt = $this->conn->prepare("SELECT * FROM whatsapp_templates WHERE tenant_id = :tid ORDER BY name");
        $stmt->execute([':tid' => $tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function saveTemplate(array $data): int
    {
        if (!empty($data['id'])) {
            $stmt = $this->conn->prepare("
                UPDATE whatsapp_templates SET name = :name, event_type = :event_type, message_template = :message_template, is_active = :is_active
                WHERE id = :id AND tenant_id = :tid
            ");
            $stmt->execute([
                ':name'             => $data['name'],
                ':event_type'       => $data['event_type'],
                ':message_template' => $data['message_template'],
                ':is_active'        => $data['is_active'] ?? 1,
                ':id'               => $data['id'],
                ':tid'              => $data['tenant_id'],
            ]);
            return (int) $data['id'];
        }
        $stmt = $this->conn->prepare("
            INSERT INTO whatsapp_templates (tenant_id, name, event_type, message_template, is_active)
            VALUES (:tid, :name, :event_type, :message_template, :is_active)
        ");
        $stmt->execute([
            ':tid'              => $data['tenant_id'],
            ':name'             => $data['name'],
            ':event_type'       => $data['event_type'],
            ':message_template' => $data['message_template'],
            ':is_active'        => $data['is_active'] ?? 1,
        ]);
        return (int) $this->conn->lastInsertId();
    }

    public function logMessage(array $data): int
    {
        $stmt = $this->conn->prepare("
            INSERT INTO whatsapp_messages (tenant_id, template_id, phone, customer_id, message, status)
            VALUES (:tid, :template_id, :phone, :customer_id, :message, :status)
        ");
        $stmt->execute([
            ':tid'         => $data['tenant_id'],
            ':template_id' => $data['template_id'] ?? null,
            ':phone'       => $data['phone'],
            ':customer_id' => $data['customer_id'] ?? null,
            ':message'     => $data['message'],
            ':status'      => 'pending',
        ]);
        return (int) $this->conn->lastInsertId();
    }

    public function updateMessageStatus(int $id, string $status, ?string $externalId = null, ?string $error = null): bool
    {
        $extra = '';
        $params = [':status' => $status, ':id' => $id];
        if ($externalId) {
            $extra .= ', external_id = :ext_id';
            $params[':ext_id'] = $externalId;
        }
        if ($status === 'sent') {
            $extra .= ', sent_at = NOW()';
        } elseif ($status === 'delivered') {
            $extra .= ', delivered_at = NOW()';
        } elseif ($status === 'read') {
            $extra .= ', read_at = NOW()';
        } elseif ($status === 'failed' && $error) {
            $extra .= ', error_message = :err';
            $params[':err'] = $error;
        }
        $stmt = $this->conn->prepare("UPDATE whatsapp_messages SET status = :status{$extra} WHERE id = :id");
        return $stmt->execute($params);
    }

    public function getMessages(int $tenantId, int $page = 1, int $perPage = 50): array
    {
        $offset = ($page - 1) * $perPage;
        $stmt = $this->conn->prepare("
            SELECT wm.*, wt.name AS template_name, c.name AS customer_name
            FROM whatsapp_messages wm
            LEFT JOIN whatsapp_templates wt ON wm.template_id = wt.id
            LEFT JOIN customers c ON wm.customer_id = c.id
            WHERE wm.tenant_id = :tid
            ORDER BY wm.created_at DESC
            LIMIT :lim OFFSET :off
        ");
        $stmt->bindValue(':tid', $tenantId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDashboardStats(int $tenantId): array
    {
        $stmt = $this->conn->prepare("
            SELECT
                COUNT(*) AS total,
                SUM(status = 'sent') AS sent_count,
                SUM(status = 'delivered') AS delivered_count,
                SUM(status = 'read') AS read_count,
                SUM(status = 'failed') AS failed_count
            FROM whatsapp_messages WHERE tenant_id = :tid AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt->execute([':tid' => $tenantId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }
}
