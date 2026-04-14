<?php
namespace Akti\Controllers;

use Akti\Models\SiteBuilder;
use Akti\Models\Product;
use Akti\Utils\Input;

/**
 * Controller do Site Builder.
 *
 * Gerencia a interface de configuração da loja online.
 * As páginas são fixas (início, produtos, contato, carrinho, perfil).
 * O editor permite ajustar tema e conteúdo via settings.
 */
class SiteBuilderController extends BaseController {
    private SiteBuilder $siteBuilder;
    private int $tenantId;

    public function __construct(\PDO $db, SiteBuilder $siteBuilder)
    {
        $this->db = $db;
        $this->siteBuilder = $siteBuilder;
        $this->tenantId = (int) ($_SESSION['tenant']['id'] ?? 0);
    }

    private function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        $this->json($data);
    }

    private function requireTenant(): bool
    {
        if ($this->tenantId <= 0) {
            $this->json(['success' => false, 'message' => 'Tenant inválido'], 403);
            return false;
        }
        return true;
    }

    /**
     * Página principal do Site Builder (editor de configurações + preview).
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

        $settings = $this->siteBuilder->getSettings($this->tenantId);

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
     * Retorna todas as configurações (AJAX GET).
     */
    public function getSettings(): void
    {
        if (!$this->requireTenant()) return;

        $settings = $this->siteBuilder->getSettings($this->tenantId);
        $this->json(['success' => true, 'settings' => $settings]);
    }

    /**
     * Salva configurações de um grupo (POST AJAX).
     */
    public function saveSettings(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método não permitido'], 405);
            return;
        }
        if (!$this->requireTenant()) return;

        $settingsJson = $_POST['settings'] ?? '{}';
        $settings = is_string($settingsJson) ? json_decode($settingsJson, true) : $settingsJson;

        if (!is_array($settings) || empty($settings)) {
            $this->json(['success' => false, 'message' => 'Dados inválidos'], 422);
            return;
        }

        $group = Input::post('group', 'string', 'general');

        if (!$this->siteBuilder->saveSettingsBatch($this->tenantId, $settings, $group)) {
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

        $settings = $this->siteBuilder->getSettings($this->tenantId);
        $previewPage = Input::get('p', 'string', 'home');
        $previewProducts = [];

        try {
            $productModel = new Product($this->db);
            $count = (int) ($settings['featured_products_count'] ?? 8);
            $productResult = $productModel->readPaginatedFiltered(1, $count, null, null);
            $previewProducts = $productResult['data'] ?? [];
        } catch (\Throwable $e) {
            $previewProducts = [];
        }

        require 'app/views/site_builder/preview.php';
    }

    /**
     * Upload de imagem para o site builder (POST AJAX).
     */
    public function uploadImage(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método não permitido'], 405);
            return;
        }
        if (!$this->requireTenant()) return;

        if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['success' => false, 'message' => 'Nenhum arquivo enviado'], 422);
            return;
        }

        $file = $_FILES['image'];

        // Validar tipo MIME
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml', 'image/x-icon'];
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (!in_array($mime, $allowedMimes, true)) {
            $this->json(['success' => false, 'message' => 'Tipo de arquivo não permitido'], 422);
            return;
        }

        // Validar tamanho (5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            $this->json(['success' => false, 'message' => 'Arquivo muito grande (máx 5MB)'], 422);
            return;
        }

        // Gerar caminho seguro dentro do diretório do tenant
        $uploadBase = \Akti\Config\TenantManager::getTenantUploadBase();
        $uploadDir = $uploadBase . 'site_builder/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Nome seguro: hash + extensão original
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico'];
        if (!in_array($ext, $allowedExts, true)) {
            $ext = 'png';
        }
        $filename = 'sb_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $destPath = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            $this->json(['success' => false, 'message' => 'Falha ao salvar arquivo'], 500);
            return;
        }

        $url = '/' . $destPath;

        $this->json([
            'success'  => true,
            'url'      => $url,
            'filename' => $filename,
        ]);
    }
}
