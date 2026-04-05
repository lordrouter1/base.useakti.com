<?php
namespace Akti\Controllers;

use Database;

/**
 * BaseController — classe abstrata base para todos os controllers.
 *
 * Centraliza:
 * - Conexão PDO via Database::getInstance()
 * - Helpers de resposta JSON, redirect e render
 * - Verificação de autenticação e tenant
 */
abstract class BaseController
{
    /** @var \PDO */
    protected \PDO $db;

    public function __construct(?\PDO $db = null)
    {
        $this->db = $db ?? \Database::getInstance();
    }

    /**
     * Retorna resposta JSON e encerra a execução.
     */
    protected function json(array $data, int $status = 200): void
    {
        while (ob_get_level()) {
            ob_end_clean();
        }
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Redireciona para uma URL e encerra a execução.
     */
    protected function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    /**
     * Renderiza views com header e footer.
     *
     * @param string $view Caminho relativo dentro de app/views/ (sem extensão)
     * @param array  $data Variáveis extraídas para a view
     */
    protected function render(string $view, array $data = []): void
    {
        extract($data);
        require 'app/views/layout/header.php';
        require 'app/views/' . $view . '.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * Verifica se o usuário está autenticado.
     * Em requisições AJAX retorna JSON 401; em páginas redireciona para login.
     */
    protected function requireAuth(): void
    {
        if (empty($_SESSION['user_id'])) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'error' => 'Não autenticado.'], 401);
            }
            $this->redirect('?page=login');
        }
    }

    /**
     * Verifica se o usuário logado é admin.
     */
    protected function requireAdmin(): void
    {
        $this->requireAuth();
        if (empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'error' => 'Acesso negado.'], 403);
            }
            $this->redirect('?page=dashboard');
        }
    }

    /**
     * Retorna o tenant_id da sessão.
     */
    protected function getTenantId(): int
    {
        return (int) ($_SESSION['tenant']['id'] ?? 0);
    }

    /**
     * Detecta se a requisição é AJAX/fetch.
     */
    protected function isAjax(): bool
    {
        $xhr = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return strtolower($xhr) === 'xmlhttprequest'
            || stripos($accept, 'application/json') !== false;
    }
}
