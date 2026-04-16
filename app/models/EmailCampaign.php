<?php

namespace Akti\Models;

use PDO;

/**
 * Model de campanhas de email marketing.
 */
class EmailCampaign
{
    private PDO $conn;

    /**
     * Construtor da classe EmailCampaign.
     *
     * @param PDO $db Conexão PDO com o banco de dados
     */
    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * Read paginated.
     *
     * @param int $page Número da página
     * @param int $perPage Registros por página
     * @param string $search Termo de busca
     * @return array
     */
    public function readPaginated(int $page = 1, int $perPage = 15, string $search = ''): array
    {
        $where = ' WHERE 1=1';
        $params = [];
        if ($search) {
            $where .= ' AND (name LIKE :search OR subject LIKE :s2)';
            $params[':search'] = '%' . $search . '%';
            $params[':s2'] = '%' . $search . '%';
        }

        $countStmt = $this->conn->prepare("SELECT COUNT(*) FROM email_campaigns" . $where);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $sql = "SELECT * FROM email_campaigns {$where} ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data'         => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total'        => $total,
            'pages'        => (int) ceil($total / $perPage),
            'current_page' => $page,
        ];
    }

 /**
  * Read one.
  *
  * @param int $id ID do registro
  * @return array|null
  */
    public function readOne(int $id): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM email_campaigns WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

 /**
  * Create.
  *
  * @param array $data Dados para processamento
  * @return int
  */
    public function create(array $data): int
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO email_campaigns (tenant_id, template_id, name, subject, body_html, status, scheduled_at, segment_filters, created_by)
             VALUES (:tenant_id, :template_id, :name, :subject, :body_html, :status, :scheduled_at, :segment_filters, :created_by)"
        );
        $stmt->execute([
            ':tenant_id'       => $data['tenant_id'],
            ':template_id'     => $data['template_id'] ?? null,
            ':name'            => $data['name'],
            ':subject'         => $data['subject'],
            ':body_html'       => $data['body_html'],
            ':status'          => $data['status'] ?? 'draft',
            ':scheduled_at'    => $data['scheduled_at'] ?? null,
            ':segment_filters' => json_encode($data['segment_filters'] ?? []),
            ':created_by'      => $data['created_by'] ?? null,
        ]);
        return (int) $this->conn->lastInsertId();
    }

 /**
  * Update.
  *
  * @param int $id ID do registro
  * @param array $data Dados para processamento
  * @return bool
  */
    public function update(int $id, array $data): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE email_campaigns SET
                name = :name, subject = :subject, body_html = :body_html,
                status = :status, scheduled_at = :scheduled_at, segment_filters = :segment_filters
             WHERE id = :id"
        );
        return $stmt->execute([
            ':id'              => $id,
            ':name'            => $data['name'],
            ':subject'         => $data['subject'],
            ':body_html'       => $data['body_html'],
            ':status'          => $data['status'] ?? 'draft',
            ':scheduled_at'    => $data['scheduled_at'] ?? null,
            ':segment_filters' => json_encode($data['segment_filters'] ?? []),
        ]);
    }

 /**
  * Delete.
  *
  * @param int $id ID do registro
  * @return bool
  */
    public function delete(int $id): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM email_campaigns WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    // ──── Templates ────

 /**
  * Get templates.
  * @return array
  */
    public function getTemplates(): array
    {
        $stmt = $this->conn->prepare("SELECT * FROM email_templates ORDER BY name ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

 /**
  * Get template.
  *
  * @param int $id ID do registro
  * @return array|null
  */
    public function getTemplate(int $id): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM email_templates WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

 /**
  * Create template.
  *
  * @param array $data Dados para processamento
  * @return int
  */
    public function createTemplate(array $data): int
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO email_templates (tenant_id, name, subject, body_html, body_text, variables, created_by)
             VALUES (:tenant_id, :name, :subject, :body_html, :body_text, :variables, :created_by)"
        );
        $stmt->execute([
            ':tenant_id'  => $data['tenant_id'],
            ':name'       => $data['name'],
            ':subject'    => $data['subject'],
            ':body_html'  => $data['body_html'],
            ':body_text'  => $data['body_text'] ?? null,
            ':variables'  => json_encode($data['variables'] ?? []),
            ':created_by' => $data['created_by'] ?? null,
        ]);
        return (int) $this->conn->lastInsertId();
    }

 /**
  * Delete template.
  *
  * @param int $id ID do registro
  * @return bool
  */
    public function deleteTemplate(int $id): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM email_templates WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

 /**
  * Update template.
  *
  * @param int $id ID do registro
  * @param array $data Dados para processamento
  * @return bool
  */
    public function updateTemplate(int $id, array $data): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE email_templates SET
                name = :name, subject = :subject, body_html = :body_html,
                body_text = :body_text, variables = :variables
             WHERE id = :id"
        );
        return $stmt->execute([
            ':id'        => $id,
            ':name'      => $data['name'],
            ':subject'   => $data['subject'],
            ':body_html' => $data['body_html'],
            ':body_text' => $data['body_text'] ?? null,
            ':variables' => json_encode($data['variables'] ?? []),
        ]);
    }

    // ──── Logs ────

 /**
  * Get logs.
  *
  * @param int $campaignId Campaign id
  * @return array
  */
    public function getLogs(int $campaignId): array
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM email_logs WHERE campaign_id = :campaign_id ORDER BY created_at DESC"
        );
        $stmt->execute([':campaign_id' => $campaignId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

 /**
  * Get stats.
  *
  * @param int $campaignId Campaign id
  * @return array
  */
    public function getStats(int $campaignId): array
    {
        $stmt = $this->conn->prepare(
            "SELECT status, COUNT(*) as count FROM email_logs WHERE campaign_id = :campaign_id GROUP BY status"
        );
        $stmt->execute([':campaign_id' => $campaignId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stats = ['sent' => 0, 'opened' => 0, 'clicked' => 0, 'bounced' => 0, 'failed' => 0];
        foreach ($rows as $row) {
            $status = $row['status'];
            $count = (int) $row['count'];
            if (isset($stats[$status])) {
                $stats[$status] = $count;
            }
        }
        return $stats;
    }
}
