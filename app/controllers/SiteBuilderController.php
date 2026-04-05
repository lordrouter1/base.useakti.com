<?php
namespace Akti\Controllers;

use Akti\Models\Product;
use Akti\Models\SiteBuilder;
use Akti\Utils\Input;

/**
 * Controller do Site Builder.
 *
 * Gerencia a interface de construção visual da loja online,
 * incluindo páginas, seções, componentes e configurações de tema.
 */
class SiteBuilderController
{
    private \PDO $db;
    private SiteBuilder $siteBuilder;
    private int $tenantId;

    public function __construct(\PDO $db, SiteBuilder $siteBuilder)
    {
        $this->db = $db;
        $this->siteBuilder = $siteBuilder;
        $this->tenantId = (int) ($_SESSION['tenant']['id'] ?? 0);
    }

    /**
     * Página principal do Site Builder (editor visual).
     */
    public function index(): void
    {
        if ($this->tenantId <= 0) {
            http_response_code(403);
            require 'app/views/layout/header.php';
            echo '<div class="container mt-4"><div class="alert alert-danger mb-0"><i class="fas fa-ban me-2"></i>Tenant inválido para o Site Builder.</div></div>';
            require 'app/views/layout/footer.php';
            return;
        }

        $pages = $this->siteBuilder->getPages($this->tenantId);
        $themeSettings = $this->siteBuilder->getThemeSettings($this->tenantId);

        // Carregar a primeira página se existir, ou nenhuma
        $currentPage = null;
        $pageId = Input::get('page_id', 'int', 0);
        if ($pageId > 0) {
            $currentPage = $this->siteBuilder->getFullPage($pageId, $this->tenantId);
        } elseif (!empty($pages)) {
            $currentPage = $this->siteBuilder->getFullPage((int) $pages[0]['id'], $this->tenantId);
        }

        // Carregar schema de configurações do tema
        $basePath = realpath(__DIR__ . '/../../') ?: dirname(__DIR__, 2);
        $schemaPath = $basePath . '/loja/config/settings_schema.json';
        $settingsSchema = file_exists($schemaPath)
            ? json_decode(file_get_contents($schemaPath), true)
            : [];

        require 'app/views/layout/header.php';
        require 'app/views/site_builder/index.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * Listagem de páginas (AJAX JSON).
     */
    public function pages(): void
    {
        if (!$this->requireTenant()) return;

        $pages = $this->siteBuilder->getPages($this->tenantId);
        $this->json(['success' => true, 'pages' => $pages]);
    }

    /**
     * Criar nova página (POST AJAX).
     */
    public function createPage(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método não permitido'], 405);
            return;
        }
        if (!$this->requireTenant()) return;

        $title = Input::post('title');
        $slug  = Input::post('slug');
        $type  = Input::post('type', 'string', 'custom');

        if (empty($title) || empty($slug)) {
            $this->json(['success' => false, 'message' => 'Título e slug são obrigatórios']);
            return;
        }

        try {
            $id = $this->siteBuilder->createPage([
                'tenant_id'        => $this->tenantId,
                'title'            => $title,
                'slug'             => $slug,
                'type'             => $type,
                'meta_title'       => Input::post('meta_title'),
                'meta_description' => Input::post('meta_description'),
            ]);
            $this->json(['success' => true, 'id' => $id]);
        } catch (\PDOException $e) {
            if ((int) $e->getCode() === 23000) {
                $this->json(['success' => false, 'message' => 'Já existe uma página com este slug'], 409);
                return;
            }
            $this->json(['success' => false, 'message' => 'Erro ao criar página no banco de dados'], 500);
        }
    }

    /**
     * Atualizar página existente (POST AJAX).
     */
    public function updatePage(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método não permitido'], 405);
            return;
        }
        if (!$this->requireTenant()) return;

        $id = Input::post('id', 'int', 0);
        if ($id <= 0) {
            $this->json(['success' => false, 'message' => 'ID inválido'], 422);
            return;
        }

        if (!$this->siteBuilder->getPage($id, $this->tenantId)) {
            $this->json(['success' => false, 'message' => 'Página não encontrada'], 404);
            return;
        }

        try {
            $this->siteBuilder->updatePage($id, [
                'tenant_id'        => $this->tenantId,
                'title'            => Input::post('title'),
                'slug'             => Input::post('slug'),
                'type'             => Input::post('type', 'string', 'custom'),
                'meta_title'       => Input::post('meta_title'),
                'meta_description' => Input::post('meta_description'),
                'is_active'        => Input::post('is_active', 'int', 1),
                'sort_order'       => Input::post('sort_order', 'int', 0),
            ]);
        } catch (\PDOException $e) {
            if ((int) $e->getCode() === 23000) {
                $this->json(['success' => false, 'message' => 'Já existe uma página com este slug'], 409);
                return;
            }

            $this->json(['success' => false, 'message' => 'Erro ao atualizar página no banco de dados'], 500);
            return;
        }

        $this->json(['success' => true]);
    }

    /**
     * Excluir página (POST AJAX).
     */
    public function deletePage(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método não permitido'], 405);
            return;
        }
        if (!$this->requireTenant()) return;

        $id = Input::post('id', 'int', 0);
        if ($id <= 0) {
            $this->json(['success' => false, 'message' => 'ID inválido'], 422);
            return;
        }

        if (!$this->siteBuilder->getPage($id, $this->tenantId)) {
            $this->json(['success' => false, 'message' => 'Página não encontrada'], 404);
            return;
        }

        if (!$this->siteBuilder->deletePage($id, $this->tenantId)) {
            $this->json(['success' => false, 'message' => 'Falha ao excluir página'], 500);
            return;
        }

        $this->json(['success' => true]);
    }

    /**
     * Salvar seções da página (POST AJAX) — salva ordem e configurações.
     */
    public function saveSections(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método não permitido'], 405);
            return;
        }

        $pageId = Input::post('page_id', 'int', 0);
        if (!$this->requireTenant()) return;

        if ($pageId <= 0) {
            $this->json(['success' => false, 'message' => 'page_id inválido'], 422);
            return;
        }

        if (!$this->siteBuilder->getPage($pageId, $this->tenantId)) {
            $this->json(['success' => false, 'message' => 'Página não encontrada'], 404);
            return;
        }

        $sectionsJson = $_POST['sections'] ?? '[]';
        $sections = is_string($sectionsJson) ? json_decode($sectionsJson, true) : $sectionsJson;

        if (!is_array($sections)) {
            $this->json(['success' => false, 'message' => 'Dados de seções inválidos'], 422);
            return;
        }

        foreach ($sections as $section) {
            if (!is_array($section)) {
                $this->json(['success' => false, 'message' => 'Estrutura de seção inválida'], 422);
                return;
            }
        }

        if (!$this->siteBuilder->saveSectionsBatch($this->tenantId, $pageId, $sections)) {
            $this->json(['success' => false, 'message' => 'Falha ao salvar seções'], 500);
            return;
        }

        $this->json(['success' => true]);
    }

    /**
     * Excluir seção (POST AJAX).
     */
    public function deleteSection(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método não permitido'], 405);
            return;
        }
        if (!$this->requireTenant()) return;

        $id = Input::post('id', 'int', 0);
        if ($id <= 0) {
            $this->json(['success' => false, 'message' => 'ID inválido'], 422);
            return;
        }

        $section = $this->siteBuilder->getSection($id, $this->tenantId);
        if (!$section) {
            $this->json(['success' => false, 'message' => 'Seção não encontrada'], 404);
            return;
        }

        if (!$this->siteBuilder->deleteSection($id, $this->tenantId)) {
            $this->json(['success' => false, 'message' => 'Falha ao excluir seção'], 500);
            return;
        }

        $this->json(['success' => true]);
    }

    /**
     * Atualiza as configurações de uma seção (POST AJAX).
     */
    public function updateSection(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método não permitido'], 405);
            return;
        }
        if (!$this->requireTenant()) return;

        $id = Input::post('id', 'int', 0);
        if ($id <= 0) {
            $this->json(['success' => false, 'message' => 'ID inválido'], 422);
            return;
        }

        $section = $this->siteBuilder->getSection($id, $this->tenantId);
        if (!$section) {
            $this->json(['success' => false, 'message' => 'Seção não encontrada'], 404);
            return;
        }

        $settingsJson = $_POST['settings'] ?? '{}';
        $settings = is_string($settingsJson) ? json_decode($settingsJson, true) : $settingsJson;
        if (!is_array($settings)) {
            $this->json(['success' => false, 'message' => 'Configurações inválidas'], 422);
            return;
        }

        $updated = $this->siteBuilder->updateSection($id, [
            'tenant_id'  => $this->tenantId,
            'type'       => Input::post('type', 'string', (string) $section['type']),
            'settings'   => $settings,
            'sort_order' => (int) ($section['sort_order'] ?? 0),
            'is_visible' => Input::post('is_visible', 'int', (int) ($section['is_visible'] ?? 1)) ? 1 : 0,
        ]);

        if (!$updated) {
            $this->json(['success' => false, 'message' => 'Falha ao atualizar seção'], 500);
            return;
        }

        $this->json(['success' => true]);
    }

    /**
     * Reordenar seções da página (POST AJAX).
     */
    public function reorderSections(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método não permitido'], 405);
            return;
        }
        if (!$this->requireTenant()) return;

        $pageId = Input::post('page_id', 'int', 0);
        if ($pageId <= 0) {
            $this->json(['success' => false, 'message' => 'page_id inválido'], 422);
            return;
        }

        if (!$this->siteBuilder->getPage($pageId, $this->tenantId)) {
            $this->json(['success' => false, 'message' => 'Página não encontrada'], 404);
            return;
        }

        $orderJson = $_POST['order'] ?? '[]';
        $order = is_string($orderJson) ? json_decode($orderJson, true) : $orderJson;
        if (!is_array($order)) {
            $this->json(['success' => false, 'message' => 'Ordem inválida'], 422);
            return;
        }

        $order = array_values(array_map('intval', $order));
        if (!$this->siteBuilder->isValidSectionOrder($pageId, $this->tenantId, $order)) {
            $this->json(['success' => false, 'message' => 'Ordem de seções inválida para esta página'], 422);
            return;
        }

        if (!$this->siteBuilder->reorderSections($pageId, $this->tenantId, $order)) {
            $this->json(['success' => false, 'message' => 'Falha ao reordenar seções'], 500);
            return;
        }

        $this->json(['success' => true]);
    }

    /**
     * Adicionar componente a uma seção (POST AJAX).
     */
    public function addComponent(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método não permitido'], 405);
            return;
        }
        if (!$this->requireTenant()) return;

        $sectionId = Input::post('section_id', 'int', 0);
        $type      = Input::post('type', 'string', 'text');
        $contentJson = $_POST['content'] ?? '{}';
        $content   = is_string($contentJson) ? json_decode($contentJson, true) : $contentJson;
        $gridCol   = Input::post('grid_col', 'int', 12);

        if ($sectionId <= 0) {
            $this->json(['success' => false, 'message' => 'section_id inválido'], 422);
            return;
        }

        if (!$this->siteBuilder->getSection($sectionId, $this->tenantId)) {
            $this->json(['success' => false, 'message' => 'Seção não encontrada'], 404);
            return;
        }

        $id = $this->siteBuilder->createComponent([
            'tenant_id'  => $this->tenantId,
            'section_id' => $sectionId,
            'type'       => $type,
            'content'    => $content ?: [],
            'grid_col'   => $gridCol,
        ]);

        if ($id <= 0) {
            $this->json(['success' => false, 'message' => 'Falha ao criar componente'], 500);
            return;
        }

        $this->json(['success' => true, 'id' => $id]);
    }

    /**
     * Atualizar componente (POST AJAX).
     */
    public function updateComponent(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método não permitido'], 405);
            return;
        }
        if (!$this->requireTenant()) return;

        $id = Input::post('id', 'int', 0);
        if ($id <= 0) {
            $this->json(['success' => false, 'message' => 'ID inválido'], 422);
            return;
        }

        if (!$this->siteBuilder->getComponent($id, $this->tenantId)) {
            $this->json(['success' => false, 'message' => 'Componente não encontrado'], 404);
            return;
        }

        $contentJson = $_POST['content'] ?? '{}';
        $content = is_string($contentJson) ? json_decode($contentJson, true) : $contentJson;

        if (!$this->siteBuilder->updateComponent($id, [
            'tenant_id'  => $this->tenantId,
            'type'       => Input::post('type', 'string', 'text'),
            'content'    => $content ?: [],
            'grid_col'   => Input::post('grid_col', 'int', 12),
            'grid_row'   => Input::post('grid_row', 'int', 0),
            'sort_order' => Input::post('sort_order', 'int', 0),
        ])) {
            $this->json(['success' => false, 'message' => 'Componente não encontrado ou sem alterações'], 404);
            return;
        }

        $this->json(['success' => true]);
    }

    /**
     * Remover componente (POST AJAX).
     */
    public function removeComponent(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método não permitido'], 405);
            return;
        }
        if (!$this->requireTenant()) return;

        $id = Input::post('id', 'int', 0);
        if ($id <= 0) {
            $this->json(['success' => false, 'message' => 'ID inválido'], 422);
            return;
        }

        if (!$this->siteBuilder->getComponent($id, $this->tenantId)) {
            $this->json(['success' => false, 'message' => 'Componente não encontrado'], 404);
            return;
        }

        if (!$this->siteBuilder->deleteComponent($id, $this->tenantId)) {
            $this->json(['success' => false, 'message' => 'Falha ao remover componente'], 500);
            return;
        }

        $this->json(['success' => true]);
    }

    /**
     * Salvar configurações de tema (POST AJAX).
     */
    public function saveThemeSettings(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método não permitido'], 405);
            return;
        }
        if (!$this->requireTenant()) return;

        $settingsJson = $_POST['settings'] ?? '{}';
        $settings = is_string($settingsJson) ? json_decode($settingsJson, true) : $settingsJson;

        if (!is_array($settings)) {
            $this->json(['success' => false, 'message' => 'Dados inválidos'], 422);
            return;
        }

        $group = Input::post('group', 'string', 'general');
        if (!$this->siteBuilder->saveThemeSettings($this->tenantId, $settings, $group)) {
            $this->json(['success' => false, 'message' => 'Falha ao salvar configurações'], 500);
            return;
        }

        $this->json(['success' => true]);
    }

    /**
     * Preview da loja (renderiza no iframe).
     */
    public function preview(): void
    {
        if ($this->tenantId <= 0) {
            http_response_code(403);
            echo 'Tenant inválido';
            return;
        }

        $pageId = Input::get('page_id', 'int', 0);
        $page = null;
        $previewProducts = [];

        if ($pageId > 0) {
            $page = $this->siteBuilder->getFullPage($pageId, $this->tenantId);
        }

        $themeSettings = $this->siteBuilder->getThemeSettings($this->tenantId);

        try {
            $productModel = new Product($this->db);
            $productResult = $productModel->readPaginatedFiltered(1, 8, null, null);
            $previewProducts = $productResult['data'] ?? [];
        } catch (\Throwable $e) {
            $previewProducts = [];
        }

        require 'app/views/site_builder/preview.php';
    }

    /**
     * Adicionar uma única seção a uma página (POST AJAX).
     */
    public function addSection(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método não permitido'], 405);
            return;
        }
        if (!$this->requireTenant()) return;

        $pageId = Input::post('page_id', 'int', 0);
        if ($pageId <= 0) {
            $this->json(['success' => false, 'message' => 'page_id inválido'], 422);
            return;
        }

        if (!$this->siteBuilder->getPage($pageId, $this->tenantId)) {
            $this->json(['success' => false, 'message' => 'Página não encontrada'], 404);
            return;
        }

        $type = Input::post('type', 'string', 'custom-html');
        $settingsJson = $_POST['settings'] ?? '{}';
        $settings = is_string($settingsJson) ? json_decode($settingsJson, true) : $settingsJson;

        try {
            $sections = $this->siteBuilder->getSections($pageId, $this->tenantId);
            $maxSort = 0;
            foreach ($sections as $s) {
                $maxSort = max($maxSort, (int) $s['sort_order']);
            }

            $id = $this->siteBuilder->createSection([
                'tenant_id'  => $this->tenantId,
                'page_id'    => $pageId,
                'type'       => $type,
                'settings'   => $settings ?: [],
                'sort_order' => $maxSort + 1,
                'is_visible' => 1,
            ]);

            if ($id <= 0) {
                $this->json(['success' => false, 'message' => 'Falha ao criar seção'], 500);
                return;
            }

            $this->json(['success' => true, 'id' => $id]);
        } catch (\Throwable $e) {
            error_log('[SiteBuilder::addSection] ' . $e->getMessage());
            $this->json(['success' => false, 'message' => 'Erro interno ao criar seção'], 500);
        }
    }

    /**
     * Retorna dados completos da página com seções e componentes (GET AJAX).
     */
    public function getPageData(): void
    {
        if (!$this->requireTenant()) return;

        $pageId = Input::get('page_id', 'int', 0);
        if ($pageId <= 0) {
            $this->json(['success' => false, 'message' => 'page_id inválido'], 422);
            return;
        }

        $page = $this->siteBuilder->getFullPage($pageId, $this->tenantId);
        if (!$page) {
            $this->json(['success' => false, 'message' => 'Página não encontrada'], 404);
            return;
        }

        $this->json(['success' => true, 'page' => $page]);
    }

    /**
     * Verifica se o tenant_id é válido; caso contrário, responde com erro.
     */
    private function requireTenant(): bool
    {
        if ($this->tenantId <= 0) {
            $this->json(['success' => false, 'message' => 'Tenant inválido'], 403);
            return false;
        }
        return true;
    }

    /**
     * Retorna JSON e encerra a execução.
     */
    private function json(array $data, int $statusCode = 200): void
    {
        while (ob_get_level()) {
            ob_end_clean();
        }
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
