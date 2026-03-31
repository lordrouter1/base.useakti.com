<?php
namespace Akti\Controllers;

use Akti\Models\DashboardWidget;

/**
 * DashboardWidgetController
 *
 * Endpoint AJAX para carregar widgets individuais do dashboard via lazy loading.
 * Cada widget é um partial PHP renderizado e retornado como HTML.
 *
 * Endpoints:
 *   ?page=dashboard_widgets&action=load&widget=cards_summary  → HTML de um widget
 *   ?page=dashboard_widgets&action=config                     → Config do grupo do usuário (JSON)
 */
class DashboardWidgetController
{
    /** @var \PDO */
    private $db;

    public function __construct()
    {
        $database = new \Database();
        $this->db = $database->getConnection();
    }

    /**
     * Carrega um widget individual via AJAX.
     * Retorna HTML pronto para inserir no DOM.
     */
    public function load(): void
    {
        $this->requireAuth();

        $widgetKey = trim($_GET['widget'] ?? '');
        $available = DashboardWidget::getAvailableWidgets();

        if (!isset($available[$widgetKey])) {
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Widget não encontrado.']);
            exit;
        }

        $widgetInfo = $available[$widgetKey];
        $file = $widgetInfo['file'];

        if (!file_exists($file)) {
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Arquivo do widget não encontrado.']);
            exit;
        }

        // Preparar variáveis que os widgets precisam
        $db = $this->db;
        $userId = (int) $_SESSION['user_id'];
        $isAdmin = ($_SESSION['user_role'] ?? '') === 'admin';

        // Capturar output do partial
        ob_start();
        try {
            require $file;
        } catch (\Throwable $e) {
            ob_end_clean();
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Erro ao renderizar widget.']);
            exit;
        }
        $html = ob_get_clean();

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'widget'  => $widgetKey,
            'html'    => $html,
        ]);
        exit;
    }

    /**
     * Retorna a configuração de widgets do grupo do usuário (JSON).
     * Usado pelo frontend para saber quais widgets carregar e em que ordem.
     */
    public function config(): void
    {
        $this->requireAuth();

        $groupId = (int) ($_SESSION['group_id'] ?? 0);
        $model = new DashboardWidget($this->db);
        $available = DashboardWidget::getAvailableWidgets();

        $visibleKeys = $model->getVisibleWidgetsForGroup($groupId);

        $widgets = [];
        foreach ($visibleKeys as $key) {
            if (isset($available[$key])) {
                $widgets[] = [
                    'key'   => $key,
                    'label' => $available[$key]['label'],
                    'icon'  => $available[$key]['icon'],
                ];
            }
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'widgets' => $widgets,
        ]);
        exit;
    }

    // ── Helpers ──

    private function requireAuth(): void
    {
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Não autenticado.']);
            exit;
        }
    }
}
