<?php
namespace Akti\Controllers;

use Akti\Models\Notification;

/**
 * NotificationController
 * 
 * Gerencia notificações do usuário: listagem, marcação como lida, AJAX endpoints.
 */
class NotificationController extends BaseController
{
    /**
     * Lista as notificações do usuário (JSON para AJAX).
     */
    public function index(): void
    {
        $this->requireAuth();

        $model = new Notification($this->db);
        $userId = (int) $_SESSION['user_id'];

        if ($this->isAjax()) {
            $unreadOnly = isset($_GET['unread']) && $_GET['unread'] === '1';
            $limit = min((int) ($_GET['limit'] ?? 20), 50);

            $notifications = $model->getByUser($userId, $limit, $unreadOnly);
            $unreadCount = $model->countUnread($userId);

            $this->json([
                'success'      => true,
                'notifications' => $notifications,
                'unread_count' => $unreadCount,
            ]);
        }

        // Página full (não AJAX) — renderiza view
        $notifications = $model->getByUser($userId, 50);
        $unreadCount = $model->countUnread($userId);

        require 'app/views/notifications/index.php';
    }

    /**
     * Conta notificações não-lidas (JSON endpoint para badge).
     */
    public function count(): void
    {
        $this->requireAuth();

        $model = new Notification($this->db);
        $count = $model->countUnread((int) $_SESSION['user_id']);

        $this->json(['success' => true, 'count' => $count]);
    }

    /**
     * Marca uma notificação como lida.
     */
    public function markRead(): void
    {
        $this->requireAuth();

        $id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
        if ($id <= 0) {
            $this->json(['success' => false, 'error' => 'ID inválido.'], 400);
        }

        $model = new Notification($this->db);
        $model->markAsRead($id, (int) $_SESSION['user_id']);

        $this->json(['success' => true]);
    }

    /**
     * Marca todas as notificações como lidas.
     */
    public function markAllRead(): void
    {
        $this->requireAuth();

        $model = new Notification($this->db);
        $model->markAllAsRead((int) $_SESSION['user_id']);

        $this->json(['success' => true]);
    }

    /**
     * SSE stream — pushes new notifications to the browser in real time.
     * FEAT-001: Server-Sent Events for real-time notifications.
     */
    public function stream(): void
    {
        $userId = $_SESSION['user_id'] ?? 0;
        $tenantId = $_SESSION['tenant']['id'] ?? 0;
        if (!$userId) {
            http_response_code(401);
            exit;
        }

        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        $lastId = isset($_SERVER['HTTP_LAST_EVENT_ID'])
            ? (int) $_SERVER['HTTP_LAST_EVENT_ID']
            : 0;

        $model = new Notification($this->db);
        $maxIterations = 60; // 60 × 2s = ~2 min then reconnect

        for ($i = 0; $i < $maxIterations; $i++) {
            $stmt = $this->db->prepare(
                "SELECT id, title, message, type, link, created_at
                   FROM notifications
                  WHERE user_id = :uid AND tenant_id = :tid
                    AND id > :last AND is_read = 0
                  ORDER BY id ASC LIMIT 10"
            );
            $stmt->execute([':uid' => $userId, ':tid' => $tenantId, ':last' => $lastId]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                $lastId = (int) $row['id'];
                echo "id: {$lastId}\n";
                echo "event: notification\n";
                echo "data: " . json_encode($row) . "\n\n";
            }

            if (ob_get_level()) {
                ob_flush();
            }
            flush();

            if (connection_aborted()) {
                break;
            }
            usleep(2_000_000);
        }

        echo "event: reconnect\ndata: {}\n\n";
    }
}
