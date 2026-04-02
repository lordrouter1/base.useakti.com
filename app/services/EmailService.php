<?php

namespace Akti\Services;

use PDO;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class EmailService
{
    private PDO $db;
    private array $config;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->config = require __DIR__ . '/../config/mail.php';
    }

    /**
     * Send a single email
     */
    public function send(string $toEmail, string $toName, string $subject, string $bodyHtml): array
    {
        $mail = $this->createMailer();

        try {
            $mail->addAddress($toEmail, $toName);
            $mail->Subject = $subject;
            $mail->Body = $bodyHtml;
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $bodyHtml));
            $mail->send();

            return ['success' => true];
        } catch (PHPMailerException $e) {
            return ['success' => false, 'error' => $mail->ErrorInfo];
        }
    }

    /**
     * Send a campaign to all recipients
     */
    public function sendCampaign(int $campaignId): array
    {
        $campaign = $this->getCampaign($campaignId);
        if (!$campaign) {
            return ['success' => false, 'error' => 'Campanha não encontrada.'];
        }

        if ($campaign['status'] === 'sent') {
            return ['success' => false, 'error' => 'Campanha já foi enviada.'];
        }

        $recipients = $this->getRecipients($campaign);
        if (empty($recipients)) {
            return ['success' => false, 'error' => 'Nenhum destinatário encontrado.'];
        }

        // Update campaign status to sending
        $this->updateCampaignStatus($campaignId, 'sending', count($recipients));

        $sentCount = 0;
        $failCount = 0;
        $tenantId = $campaign['tenant_id'];

        foreach ($recipients as $recipient) {
            $personalizedBody = $this->replaceVariables($campaign['body_html'], $recipient, $tenantId);
            $personalizedSubject = $this->replaceVariables($campaign['subject'], $recipient, $tenantId);

            $result = $this->send(
                $recipient['email'],
                $recipient['name'],
                $personalizedSubject,
                $personalizedBody
            );

            $logStatus = $result['success'] ? 'sent' : 'failed';
            $this->createLog($campaignId, $tenantId, $recipient, $logStatus, $result['error'] ?? null);

            if ($result['success']) {
                $sentCount++;
            } else {
                $failCount++;
            }
        }

        // Update campaign totals
        $finalStatus = ($sentCount > 0) ? 'sent' : 'failed';
        $this->finalizeCampaign($campaignId, $finalStatus, $sentCount);

        return [
            'success' => true,
            'total'   => count($recipients),
            'sent'    => $sentCount,
            'failed'  => $failCount,
        ];
    }

    /**
     * Send a test email for preview
     */
    public function sendTest(int $campaignId, string $testEmail): array
    {
        $campaign = $this->getCampaign($campaignId);
        if (!$campaign) {
            return ['success' => false, 'error' => 'Campanha não encontrada.'];
        }

        $sampleRecipient = [
            'name'      => 'Teste',
            'email'     => $testEmail,
            'phone'     => '(00) 00000-0000',
            'document'  => '000.000.000-00',
            'city'      => 'Cidade Teste',
            'state'     => 'UF',
        ];

        $personalizedBody = $this->replaceVariables($campaign['body_html'], $sampleRecipient, $campaign['tenant_id']);
        $personalizedSubject = '[TESTE] ' . $this->replaceVariables($campaign['subject'], $sampleRecipient, $campaign['tenant_id']);

        return $this->send($testEmail, 'Teste', $personalizedSubject, $personalizedBody);
    }

    /**
     * Replace template variables with actual customer data
     */
    private function replaceVariables(string $content, array $recipient, int $tenantId): string
    {
        $companyName = $this->getTenantCompanyName($tenantId);

        $vars = [
            '{{nome}}'      => $recipient['name'] ?? '',
            '{{email}}'     => $recipient['email'] ?? '',
            '{{telefone}}'  => $recipient['phone'] ?? '',
            '{{documento}}' => $recipient['document'] ?? '',
            '{{cidade}}'    => $recipient['city'] ?? '',
            '{{estado}}'    => $recipient['state'] ?? '',
            '{{empresa}}'   => $companyName,
        ];

        return str_replace(array_keys($vars), array_values($vars), $content);
    }

    private function getTenantCompanyName(int $tenantId): string
    {
        return $_SESSION['tenant']['company_name'] ?? 'Empresa';
    }

    private function getCampaign(int $campaignId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM email_campaigns WHERE id = :id");
        $stmt->execute([':id' => $campaignId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function getRecipients(array $campaign): array
    {
        $filters = is_string($campaign['segment_filters'])
            ? json_decode($campaign['segment_filters'], true)
            : ($campaign['segment_filters'] ?? []);

        if (!empty($filters['customer_ids'])) {
            $ids = array_map(function ($c) {
                return is_array($c) ? (int) $c['id'] : (int) $c;
            }, $filters['customer_ids']);

            if (empty($ids)) {
                return [];
            }

            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $this->db->prepare(
                "SELECT id, name, email, phone, document, city, state
                 FROM customers
                 WHERE id IN ({$placeholders})
                   AND deleted_at IS NULL AND email IS NOT NULL AND email != ''"
            );
            $stmt->execute($ids);
        } else {
            $stmt = $this->db->prepare(
                "SELECT id, name, email, phone, document, city, state
                 FROM customers
                 WHERE deleted_at IS NULL AND status = 'active'
                   AND email IS NOT NULL AND email != ''
                 ORDER BY name ASC"
            );
            $stmt->execute();
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function updateCampaignStatus(int $campaignId, string $status, int $totalRecipients = 0): void
    {
        $stmt = $this->db->prepare(
            "UPDATE email_campaigns SET status = :status, total_recipients = :total WHERE id = :id"
        );
        $stmt->execute([
            ':status' => $status,
            ':total'  => $totalRecipients,
            ':id'     => $campaignId,
        ]);
    }

    private function finalizeCampaign(int $campaignId, string $status, int $sentCount): void
    {
        $stmt = $this->db->prepare(
            "UPDATE email_campaigns SET status = :status, total_sent = :sent, sent_at = NOW() WHERE id = :id"
        );
        $stmt->execute([
            ':status' => $status,
            ':sent'   => $sentCount,
            ':id'     => $campaignId,
        ]);
    }

    private function createLog(int $campaignId, int $tenantId, array $recipient, string $status, ?string $error): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO email_logs (tenant_id, campaign_id, recipient_email, recipient_name, customer_id, status, error_message, created_at)
             VALUES (:tenant_id, :campaign_id, :email, :name, :customer_id, :status, :error, NOW())"
        );
        $stmt->execute([
            ':tenant_id'   => $tenantId,
            ':campaign_id' => $campaignId,
            ':email'       => $recipient['email'],
            ':name'        => $recipient['name'] ?? '',
            ':customer_id' => $recipient['id'] ?? null,
            ':status'      => $status,
            ':error'       => $error,
        ]);
    }

    private function createMailer(): PHPMailer
    {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->CharSet = 'UTF-8';
        $mail->Host = $this->config['host'];
        $mail->Port = $this->config['port'];

        if (!empty($this->config['username'])) {
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['username'];
            $mail->Password = $this->config['password'];
        }

        $encryption = $this->config['encryption'] ?? 'tls';
        if ($encryption === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif ($encryption === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        }

        $mail->setFrom($this->config['from_email'], $this->config['from_name']);
        $mail->isHTML(true);

        return $mail;
    }
}
