<?php
namespace Akti\Controllers;

use Akti\Models\Notification;

/**
 * NotificationController
 * 
 * Gerencia notificações do usuário: listagem, marcação como lida, AJAX endpoints.
 */
class NotificationController
{
    /** @var \PDO */
    private $db;

    public function __construct()
    {
        $database = new \Database();
        $this->db = $database->getConnection();
    }

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

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success'      => true,
                'notifications' => $notifications,
                'unread_count' => $unreadCount,
            ]);
            exit;
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

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'count' => $count]);
        exit;
    }

    /**
     * Marca uma notificação como lida.
     */
    public function markRead(): void
    {
        $this->requireAuth();

        $id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
        if ($id <= 0) {
            $this->jsonError('ID inválido.', 400);
        }

        $model = new Notification($this->db);
        $model->markAsRead($id, (int) $_SESSION['user_id']);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true]);
        exit;
    }

    /**
     * Marca todas as notificações como lidas.
     */
    public function markAllRead(): void
    {
        $this->requireAuth();

        $model = new Notification($this->db);
        $model->markAllAsRead((int) $_SESSION['user_id']);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true]);
        exit;
    }

    // ── Helpers ──

    private function requireAuth(): void
    {
        if (empty($_SESSION['user_id'])) {
            if ($this->isAjax()) {
                $this->jsonError('Não autenticado.', 401);
            }
            header('Location: ?page=login');
            exit;
        }
    }

    private function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    private function jsonError(string $message, int $code = 400): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => $message]);
        exit;
    }
}
