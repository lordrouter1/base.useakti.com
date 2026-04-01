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
}
