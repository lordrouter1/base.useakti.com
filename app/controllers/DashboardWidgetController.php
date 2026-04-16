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
class DashboardWidgetController extends BaseController
{
    /** @var \PDO */
    public function __construct(\PDO $db)
    {
        $this->db = $db;
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
            $this->json(['success' => false, 'error' => 'Widget não encontrado.']);
        }

        $widgetInfo = $available[$widgetKey];
        $file = $widgetInfo['file'];

        if (!file_exists($file)) {
            http_response_code(404);
            $this->json(['success' => false, 'error' => 'Arquivo do widget não encontrado.']);
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
            $this->json(['success' => false, 'error' => 'Erro ao renderizar widget.']);
        }
        $html = ob_get_clean();
        $this->json([
            'success' => true,
            'widget'  => $widgetKey,
            'html'    => $html,
        ]);
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
        $this->json([
            'success' => true,
            'widgets' => $widgets,
        ]);
    }

    // ── Helpers ──

    /**
     * Require auth.
     * @return void
     */
    private function requireAuth(): void
    {
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            $this->json(['success' => false, 'error' => 'Não autenticado.']);
        }
    }
}
