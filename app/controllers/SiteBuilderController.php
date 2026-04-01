<?php
namespace Akti\Controllers;

use Akti\Models\SiteBuilder;
use Akti\Utils\Input;
use Database;

/**
 * Controller do Site Builder.
 *
 * Gerencia a interface de construção visual da loja online,
 * incluindo páginas, seções, componentes e configurações de tema.
 */
class SiteBuilderController
{
    private $db;
    private SiteBuilder $siteBuilder;
    private int $tenantId;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->siteBuilder = new SiteBuilder($this->db);
        $this->tenantId = (int) ($_SESSION['tenant_id'] ?? 0);
    }

    /**
     * Página principal do Site Builder (editor visual).
     */
    public function index(): void
    {
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
        $schemaPath = __DIR__ . '/../../loja/config/settings_schema.json';
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

        $title = Input::post('title');
        $slug  = Input::post('slug');
        $type  = Input::post('type', 'string', 'custom');

        if (empty($title) || empty($slug)) {
            $this->json(['success' => false, 'message' => 'Título e slug são obrigatórios']);
            return;
        }

        $id = $this->siteBuilder->createPage([
            'tenant_id'        => $this->tenantId,
            'title'            => $title,
            'slug'             => $slug,
            'type'             => $type,
            'meta_title'       => Input::post('meta_title'),
            'meta_description' => Input::post('meta_description'),
        ]);

        $this->json(['success' => true, 'id' => $id]);
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

        $id = Input::post('id', 'int', 0);
        if ($id <= 0) {
            $this->json(['success' => false, 'message' => 'ID inválido']);
            return;
        }

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

        $id = Input::post('id', 'int', 0);
        if ($id <= 0) {
            $this->json(['success' => false, 'message' => 'ID inválido']);
            return;
        }

        $this->siteBuilder->deletePage($id, $this->tenantId);
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
        $sectionsJson = $_POST['sections'] ?? '[]';
        $sections = is_string($sectionsJson) ? json_decode($sectionsJson, true) : $sectionsJson;

        if (!is_array($sections)) {
            $this->json(['success' => false, 'message' => 'Dados de seções inválidos']);
            return;
        }

        foreach ($sections as $index => $section) {
            $sectionData = [
                'tenant_id'  => $this->tenantId,
                'page_id'    => $pageId,
                'type'       => $section['type'] ?? 'custom-html',
                'settings'   => $section['settings'] ?? [],
                'sort_order' => $index,
                'is_visible' => $section['is_visible'] ?? 1,
            ];

            if (!empty($section['id'])) {
                $this->siteBuilder->updateSection((int) $section['id'], $sectionData);
            } else {
                $this->siteBuilder->createSection($sectionData);
            }
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

        $sectionId = Input::post('section_id', 'int', 0);
        $type      = Input::post('type', 'string', 'text');
        $contentJson = $_POST['content'] ?? '{}';
        $content   = is_string($contentJson) ? json_decode($contentJson, true) : $contentJson;
        $gridCol   = Input::post('grid_col', 'int', 12);

        $id = $this->siteBuilder->createComponent([
            'tenant_id'  => $this->tenantId,
            'section_id' => $sectionId,
            'type'       => $type,
            'content'    => $content ?: [],
            'grid_col'   => $gridCol,
        ]);

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

        $id = Input::post('id', 'int', 0);
        if ($id <= 0) {
            $this->json(['success' => false, 'message' => 'ID inválido']);
            return;
        }

        $contentJson = $_POST['content'] ?? '{}';
        $content = is_string($contentJson) ? json_decode($contentJson, true) : $contentJson;

        $this->siteBuilder->updateComponent($id, [
            'tenant_id'  => $this->tenantId,
            'type'       => Input::post('type', 'string', 'text'),
            'content'    => $content ?: [],
            'grid_col'   => Input::post('grid_col', 'int', 12),
            'grid_row'   => Input::post('grid_row', 'int', 0),
            'sort_order' => Input::post('sort_order', 'int', 0),
        ]);

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

        $id = Input::post('id', 'int', 0);
        if ($id <= 0) {
            $this->json(['success' => false, 'message' => 'ID inválido']);
            return;
        }

        $this->siteBuilder->deleteComponent($id, $this->tenantId);
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

        $settingsJson = $_POST['settings'] ?? '{}';
        $settings = is_string($settingsJson) ? json_decode($settingsJson, true) : $settingsJson;

        if (!is_array($settings)) {
            $this->json(['success' => false, 'message' => 'Dados inválidos']);
            return;
        }

        $group = Input::post('group', 'string', 'general');
        $this->siteBuilder->saveThemeSettings($this->tenantId, $settings, $group);
        $this->json(['success' => true]);
    }

    /**
     * Preview da loja (renderiza no iframe).
     */
    public function preview(): void
    {
        $pageId = Input::get('page_id', 'int', 0);
        $page = null;

        if ($pageId > 0) {
            $page = $this->siteBuilder->getFullPage($pageId, $this->tenantId);
        }

        $themeSettings = $this->siteBuilder->getThemeSettings($this->tenantId);

        require 'app/views/site_builder/preview.php';
    }

    /**
     * Retorna JSON e encerra a execução.
     */
    private function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }
}
