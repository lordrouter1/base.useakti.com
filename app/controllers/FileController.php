<?php

namespace Akti\Controllers;

use Akti\Services\FileManager;
use Akti\Services\ThumbnailService;

/**
 * FileController — Endpoints HTTP para gestão de arquivos.
 *
 * Rotas:
 * - ?page=files&action=serve&path=...        → Servir arquivo com cache
 * - ?page=files&action=thumb&path=...&w=...  → Servir thumbnail
 * - ?page=files&action=download&path=...     → Download de arquivo
 * - ?page=files&action=upload                → Upload genérico via AJAX
 */
class FileController extends BaseController
{
    private FileManager $fileManager;

    /**
     * Construtor da classe FileController.
     *
     * @param \PDO|null $db Conexão PDO com o banco de dados
     */
    public function __construct(?\PDO $db = null)
    {
        parent::__construct($db);
        $this->fileManager = new FileManager($this->db);
    }

    /**
     * Servir arquivo com cache headers.
     * GET ?page=files&action=serve&path=<relative_path>
     */
    public function serve(): void
    {
        $path = $_GET['path'] ?? '';

        if (empty($path) || !$this->isPathSafe($path)) {
            http_response_code(400);
            exit;
        }

        if (!$this->fileManager->exists($path)) {
            http_response_code(404);
            exit;
        }

        $this->fileManager->serve($path);
    }

    /**
     * Gerar e servir thumbnail on-the-fly.
     * GET ?page=files&action=thumb&path=<path>&w=<width>&h=<height>
     */
    public function thumb(): void
    {
        $path   = $_GET['path'] ?? '';
        $width  = (int) ($_GET['w'] ?? 150);
        $height = !empty($_GET['h']) ? (int) $_GET['h'] : null;

        if (empty($path) || !$this->isPathSafe($path)) {
            http_response_code(400);
            exit;
        }

        if (!$this->fileManager->exists($path)) {
            http_response_code(404);
            exit;
        }

        $thumbService = new ThumbnailService();

        if (!$thumbService->isGdAvailable()) {
            // Fallback: servir original
            $this->fileManager->serve($path);
            return;
        }

        // Limites de segurança
        $width  = min(max($width, 20), 1200);
        $height = $height !== null ? min(max($height, 20), 1200) : null;

        $thumbPath = $thumbService->getOrCreate($path, $width, $height);

        if ($thumbPath && $this->fileManager->exists($thumbPath)) {
            $this->fileManager->serve($thumbPath);
        } else {
            $this->fileManager->serve($path);
        }
    }

    /**
     * Download de arquivo.
     * GET ?page=files&action=download&path=<path>&name=<filename>
     */
    public function download(): void
    {
        $this->requireAuth();

        $path     = $_GET['path'] ?? '';
        $filename = $_GET['name'] ?? null;

        if (empty($path) || !$this->isPathSafe($path)) {
            http_response_code(400);
            exit;
        }

        $this->fileManager->download($path, $filename);
    }

    /**
     * Upload genérico via AJAX.
     * POST ?page=files&action=upload
     *
     * Espera: file (arquivo), module (string), entity_type (string), entity_id (int)
     */
    public function upload(): void
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'error' => 'Método não permitido.'], 405);
        }

        // Verificar CSRF
        $csrfHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $csrfSession = $_SESSION['csrf_token'] ?? '';
        if (empty($csrfHeader) || $csrfHeader !== $csrfSession) {
            $this->json(['success' => false, 'error' => 'Token CSRF inválido.'], 403);
        }

        $module     = $_POST['module'] ?? '';
        $entityType = $_POST['entity_type'] ?? null;
        $entityId   = !empty($_POST['entity_id']) ? (int) $_POST['entity_id'] : null;

        if (empty($module)) {
            $this->json(['success' => false, 'error' => 'Módulo não informado.']);
        }

        $file = $_FILES['file'] ?? null;
        if (!$file) {
            $this->json(['success' => false, 'error' => 'Nenhum arquivo enviado.']);
        }

        $result = $this->fileManager->upload($file, $module, [
            'entityType' => $entityType,
            'entityId'   => $entityId,
        ]);

        $this->json($result);
    }

    /**
     * Validar se o path é seguro (sem path traversal).
     */
    private function isPathSafe(string $path): bool
    {
        // Bloquear path traversal
        if (str_contains($path, '..') || str_contains($path, "\0")) {
            return false;
        }

        // Deve estar dentro de assets/uploads/ ou storage/
        $allowed = ['assets/uploads/', 'storage/'];
        foreach ($allowed as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
