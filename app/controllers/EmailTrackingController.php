<?php

namespace Akti\Controllers;

use Akti\Utils\Input;

class EmailTrackingController extends BaseController {
    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Tracking pixel — records email open
     * URL: ?page=email_track&action=open&lid=<log_id>&h=<hash>
     */
    public function open()
    {
        $logId = Input::get('lid', 'int', 0);
        $hash = Input::get('h', 'string', '');

        if ($logId > 0 && $this->verifyHash($logId, $hash)) {
            $stmt = $this->db->prepare(
                "UPDATE email_logs SET status = 'opened', opened_at = COALESCE(opened_at, NOW()) WHERE id = :id"
            );
            $stmt->execute([':id' => $logId]);

            $this->updateCampaignOpenCount($logId);
        }

        // Return a 1x1 transparent GIF
        header('Content-Type: image/gif');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        // 1x1 transparent GIF binary
        echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        exit;
    }

    /**
     * Click tracking — records link click and redirects
     * URL: ?page=email_track&action=click&lid=<log_id>&h=<hash>&url=<encoded_url>
     */
    public function click()
    {
        $logId = Input::get('lid', 'int', 0);
        $hash = Input::get('h', 'string', '');
        $url = Input::get('url', 'string', '');

        if ($logId > 0 && $this->verifyHash($logId, $hash)) {
            $stmt = $this->db->prepare(
                "UPDATE email_logs SET clicked_at = COALESCE(clicked_at, NOW()) WHERE id = :id"
            );
            $stmt->execute([':id' => $logId]);

            $this->updateCampaignClickCount($logId);
        }

        // Validate URL before redirecting
        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $url)) {
            http_response_code(400);
            echo 'URL inválida.';
            exit;
        }

        header('Location: ' . $url, true, 302);
        exit;
    }

    /**
     * Generate a tracking hash for a log entry (HMAC)
     */
    public static function generateHash(int $logId): string
    {
        $secret = getenv('APP_KEY') ?: 'akti-email-tracking-default-key';
        return substr(hash_hmac('sha256', (string) $logId, $secret), 0, 16);
    }

    /**
     * Verify the tracking hash
     */
    private function verifyHash(int $logId, string $hash): bool
    {
        return hash_equals(self::generateHash($logId), $hash);
    }

    /**
     * Update campaign total_opened from email_logs
     */
    private function updateCampaignOpenCount(int $logId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE email_campaigns c
             JOIN (SELECT campaign_id, COUNT(*) AS cnt FROM email_logs WHERE campaign_id = (SELECT campaign_id FROM email_logs WHERE id = :lid) AND opened_at IS NOT NULL GROUP BY campaign_id) s
             ON c.id = s.campaign_id
             SET c.total_opened = s.cnt"
        );
        $stmt->execute([':lid' => $logId]);
    }

    /**
     * Update campaign total_clicked from email_logs
     */
    private function updateCampaignClickCount(int $logId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE email_campaigns c
             JOIN (SELECT campaign_id, COUNT(*) AS cnt FROM email_logs WHERE campaign_id = (SELECT campaign_id FROM email_logs WHERE id = :lid) AND clicked_at IS NOT NULL GROUP BY campaign_id) s
             ON c.id = s.campaign_id
             SET c.total_clicked = s.cnt"
        );
        $stmt->execute([':lid' => $logId]);
    }
}
