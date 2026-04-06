<?php

namespace Akti\Services;

use Akti\Config\TenantManager;

/**
 * FileManager — Serviço centralizado de gestão de arquivos.
 *
 * Responsável por upload, download, exclusão, geração de URLs
 * e registro de arquivos na tabela file_assets.
 *
 * Preparado para futura integração com Cloudflare R2 / S3.
 */
class FileManager
{
    private \PDO $db;
    private string $basePath;
    private string $disk = 'local';

    /** Mapa de extensões por MIME type */
    private const MIME_EXTENSIONS = [
        'image/jpeg'      => 'jpg',
        'image/png'       => 'png',
        'image/gif'       => 'gif',
        'image/webp'      => 'webp',
        'image/bmp'       => 'bmp',
        'image/svg+xml'   => 'svg',
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        'text/csv'        => 'csv',
        'text/plain'      => 'txt',
        'application/zip' => 'zip',
    ];

    /** MIME types de imagem suportados */
    private const IMAGE_MIMES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/bmp',
    ];

    /** Perfis de upload pré-configurados por módulo */
    private const MODULE_PROFILES = [
        'products' => [
            'maxSize'      => 5242880, // 5MB
            'allowedMimes' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp', 'image/svg+xml'],
            'subdirectory' => 'products',
        ],
        'customers' => [
            'maxSize'      => 5242880,
            'allowedMimes' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
            'subdirectory' => 'customers',
        ],
        'logos' => [
            'maxSize'      => 5242880,
            'allowedMimes' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'],
            'subdirectory' => '',
        ],
        'avatars' => [
            'maxSize'      => 2097152, // 2MB
            'allowedMimes' => ['image/jpeg', 'image/png', 'image/webp'],
            'subdirectory' => 'avatars',
        ],
        'comprovantes' => [
            'maxSize'      => 10485760, // 10MB
            'allowedMimes' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'],
            'subdirectory' => 'comprovantes',
        ],
        'attachments' => [
            'maxSize'      => 10485760,
            'allowedMimes' => [
                'image/jpeg', 'image/png', 'image/gif', 'image/webp',
                'application/pdf', 'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'text/csv', 'text/plain', 'application/zip',
            ],
            'subdirectory' => 'attachments',
        ],
        'item_logs' => [
            'maxSize'      => 10485760,
            'allowedMimes' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'],
            'subdirectory' => 'item_logs',
        ],
        'nfe' => [
            'maxSize'      => 2097152,
            'allowedMimes' => ['image/jpeg', 'image/png'],
            'subdirectory' => 'nfe/danfe/logos',
        ],
    ];

    public function __construct(?\PDO $db = null)
    {
        $this->db = $db ?? \Database::getInstance();
        $this->basePath = defined('AKTI_BASE_PATH')
            ? rtrim(AKTI_BASE_PATH, '/\\') . '/'
            : rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\') . '/';
    }

    /**
     * Upload de um único arquivo.
     *
     * @param array  $file    Entrada de $_FILES (single file)
     * @param string $module  Nome do módulo (products, customers, etc.)
     * @param array  $options Opções adicionais:
     *   - maxSize (int): tamanho máximo em bytes
     *   - allowedMimes (array): MIME types permitidos
     *   - prefix (string): prefixo do nome do arquivo
     *   - subdirectory (string): subdiretório adicional dentro do módulo
     *   - entityType (string): tipo de entidade vinculada
     *   - entityId (int): ID da entidade vinculada
     *   - track (bool): se deve registrar na tabela file_assets (default: true)
     * @return array ['success' => bool, 'path' => string|null, 'asset_id' => int|null, 'error' => string|null]
     */
    public function upload(array $file, string $module, array $options = []): array
    {
        // Validar upload básico
        if (empty($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $errorMsg = $this->getUploadErrorMessage($file['error'] ?? UPLOAD_ERR_NO_FILE);
            return ['success' => false, 'path' => null, 'asset_id' => null, 'error' => $errorMsg];
        }

        // Merge com perfil do módulo
        $profile = self::MODULE_PROFILES[$module] ?? [];
        $maxSize      = $options['maxSize'] ?? $profile['maxSize'] ?? 10485760;
        $allowedMimes = $options['allowedMimes'] ?? $profile['allowedMimes'] ?? [];
        $prefix       = $options['prefix'] ?? '';
        $subdirectory = $options['subdirectory'] ?? $profile['subdirectory'] ?? $module;
        $entityType   = $options['entityType'] ?? null;
        $entityId     = $options['entityId'] ?? null;
        $track        = $options['track'] ?? true;

        // Validar tamanho
        if ($file['size'] > $maxSize) {
            $maxMB = round($maxSize / 1048576, 1);
            return ['success' => false, 'path' => null, 'asset_id' => null, 'error' => "Arquivo excede o tamanho máximo de {$maxMB}MB."];
        }

        // Validar MIME type via magic bytes
        $mime = $this->detectMimeType($file['tmp_name']);
        if (!empty($allowedMimes) && !in_array($mime, $allowedMimes)) {
            return ['success' => false, 'path' => null, 'asset_id' => null, 'error' => 'Tipo de arquivo não permitido.'];
        }

        // Determinar extensão pela MIME detectada
        $ext = self::MIME_EXTENSIONS[$mime] ?? strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext === 'jpeg') {
            $ext = 'jpg';
        }

        // Construir diretório de destino
        $uploadBase = TenantManager::getTenantUploadBase();
        $targetDir  = $uploadBase . ($subdirectory ? rtrim($subdirectory, '/') . '/' : '');

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        // Gerar nome único e seguro
        $storedName = $this->generateFilename($prefix, $ext);
        $targetPath = $targetDir . $storedName;

        // Mover arquivo
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            return ['success' => false, 'path' => null, 'asset_id' => null, 'error' => 'Falha ao salvar o arquivo.'];
        }

        // Detectar dimensões de imagem
        $isImage = in_array($mime, self::IMAGE_MIMES);
        $width = null;
        $height = null;
        if ($isImage && function_exists('getimagesize')) {
            $dims = @getimagesize($targetPath);
            if ($dims) {
                $width = $dims[0];
                $height = $dims[1];
            }
        }

        // Gerar thumbnail se for imagem
        $hasThumbnail = false;
        $thumbnailPath = null;
        if ($isImage && $mime !== 'image/svg+xml') {
            $thumbService = new ThumbnailService();
            $thumbResult = $thumbService->generate($targetPath, 150);
            if ($thumbResult) {
                $hasThumbnail = true;
                $thumbnailPath = $thumbResult;
            }
        }

        // Registrar no banco
        $assetId = null;
        if ($track) {
            $assetId = $this->trackAsset([
                'module'         => $module,
                'entity_type'    => $entityType,
                'entity_id'      => $entityId,
                'original_name'  => $file['name'],
                'stored_name'    => $storedName,
                'path'           => $targetPath,
                'mime_type'      => $mime,
                'size'           => $file['size'],
                'is_image'       => $isImage ? 1 : 0,
                'image_width'    => $width,
                'image_height'   => $height,
                'has_thumbnail'  => $hasThumbnail ? 1 : 0,
                'thumbnail_path' => $thumbnailPath,
            ]);
        }

        return [
            'success'        => true,
            'path'           => $targetPath,
            'stored_name'    => $storedName,
            'original_name'  => $file['name'],
            'mime_type'      => $mime,
            'size'           => $file['size'],
            'is_image'       => $isImage,
            'has_thumbnail'  => $hasThumbnail,
            'thumbnail_path' => $thumbnailPath,
            'asset_id'       => $assetId,
            'error'          => null,
        ];
    }

    /**
     * Upload de múltiplos arquivos (campo HTML com multiple).
     *
     * @param array  $files   Entrada de $_FILES (multiple format via name[])
     * @param string $module  Nome do módulo
     * @param array  $options Opções (mesmas de upload())
     * @return array ['results' => array[], 'uploaded' => int, 'failed' => int]
     */
    public function uploadMultiple(array $files, string $module, array $options = []): array
    {
        $results = [];
        $uploaded = 0;
        $failed = 0;

        if (empty($files['name']) || !is_array($files['name'])) {
            return ['results' => [], 'uploaded' => 0, 'failed' => 0];
        }

        $count = count($files['name']);
        for ($i = 0; $i < $count; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }

            $singleFile = [
                'name'     => $files['name'][$i],
                'type'     => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i],
            ];

            $result = $this->upload($singleFile, $module, $options);
            $results[] = $result;

            if ($result['success']) {
                $uploaded++;
            } else {
                $failed++;
            }
        }

        return ['results' => $results, 'uploaded' => $uploaded, 'failed' => $failed];
    }

    /**
     * Excluir um arquivo do disco e marcar como deletado no banco.
     */
    public function delete(string $path): bool
    {
        if (empty($path)) {
            return false;
        }

        // Soft-delete no banco
        $this->softDeleteAsset($path);

        // Deletar thumbnails associados
        $thumbService = new ThumbnailService();
        $thumbService->deleteThumbnails($path);

        // Deletar arquivo físico
        $fullPath = $this->resolveFullPath($path);
        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }

        return true;
    }

    /**
     * Gerar URL pública para um arquivo.
     * Se $size for informado, retorna URL do thumbnail.
     *
     * @param string|null $path Caminho relativo do arquivo
     * @param string|null $size Tamanho do thumbnail: 'sm' (80px), 'md' (150px), 'lg' (300px), 'xl' (600px) ou WxH
     * @return string URL do arquivo ou placeholder
     */
    public function getUrl(?string $path, ?string $size = null): string
    {
        if (empty($path)) {
            return '';
        }

        // Se solicitado thumbnail
        if ($size !== null && $this->isImage($path)) {
            $dimensions = $this->parseSizePreset($size);
            $thumbService = new ThumbnailService();
            $thumbPath = $thumbService->getOrCreate($path, $dimensions['width'], $dimensions['height']);
            if ($thumbPath) {
                return $thumbPath;
            }
        }

        return $path;
    }

    /**
     * Gerar URL de thumbnail para uma imagem.
     *
     * @param string|null $path   Caminho da imagem original
     * @param int         $width  Largura desejada
     * @param int|null    $height Altura (null = proporcional)
     * @return string URL do thumbnail ou imagem original
     */
    public function thumbUrl(?string $path, int $width, ?int $height = null): string
    {
        if (empty($path) || !$this->isImage($path)) {
            return $path ?? '';
        }

        $thumbService = new ThumbnailService();
        $thumbPath = $thumbService->getOrCreate($path, $width, $height);

        return $thumbPath ?: $path;
    }

    /**
     * Servir arquivo para download com headers HTTP.
     */
    public function download(string $path, ?string $filename = null): void
    {
        $fullPath = $this->resolveFullPath($path);

        if (!file_exists($fullPath)) {
            http_response_code(404);
            echo 'Arquivo não encontrado.';
            exit;
        }

        $mime = $this->detectMimeType($fullPath);
        $downloadName = $filename ?? basename($path);

        // Limpar buffers
        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . addcslashes($downloadName, '"\\') . '"');
        header('Content-Length: ' . filesize($fullPath));
        header('Cache-Control: private, max-age=0');
        readfile($fullPath);
        exit;
    }

    /**
     * Servir imagem/arquivo inline (para exibição no browser).
     */
    public function serve(string $path): void
    {
        $fullPath = $this->resolveFullPath($path);

        if (!file_exists($fullPath)) {
            http_response_code(404);
            exit;
        }

        $mime = $this->detectMimeType($fullPath);
        $mtime = filemtime($fullPath);

        // Cache headers
        $etag = md5($path . $mtime);
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($fullPath));
        header('Cache-Control: public, max-age=31536000');
        header('ETag: "' . $etag . '"');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');

        // 304 Not Modified
        $ifNoneMatch = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
        if (trim($ifNoneMatch, '"') === $etag) {
            http_response_code(304);
            exit;
        }

        readfile($fullPath);
        exit;
    }

    /**
     * Verificar se um caminho é de imagem.
     */
    public function isImage(?string $path): bool
    {
        if (empty($path)) {
            return false;
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp']);
    }

    /**
     * Verificar se um arquivo existe.
     */
    public function exists(?string $path): bool
    {
        if (empty($path)) {
            return false;
        }

        $fullPath = $this->resolveFullPath($path);
        return file_exists($fullPath);
    }

    /**
     * Detectar MIME type via magic bytes (finfo).
     */
    public function detectMimeType(string $path): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        return $finfo->file($path) ?: 'application/octet-stream';
    }

    /**
     * Obter perfil de upload de um módulo.
     */
    public function getModuleProfile(string $module): array
    {
        return self::MODULE_PROFILES[$module] ?? [];
    }

    /**
     * Substituir um arquivo existente com um novo upload.
     * Deleta o antigo e faz upload do novo.
     */
    public function replace(?string $oldPath, array $file, string $module, array $options = []): array
    {
        if (!empty($oldPath)) {
            $this->delete($oldPath);
        }

        return $this->upload($file, $module, $options);
    }

    // ──────────────── Métodos Internos ────────────────

    /**
     * Gerar nome de arquivo único e seguro.
     */
    private function generateFilename(string $prefix, string $ext): string
    {
        $parts = [];
        if ($prefix) {
            $parts[] = preg_replace('/[^a-zA-Z0-9_-]/', '', $prefix);
        }
        $parts[] = time();
        $parts[] = bin2hex(random_bytes(4));

        return implode('_', $parts) . '.' . $ext;
    }

    /**
     * Resolver caminho completo do arquivo.
     * Se já é absoluto, retorna como está.
     * Se é relativo, prepende o basePath.
     */
    private function resolveFullPath(string $path): string
    {
        // Já é caminho absoluto
        if (preg_match('/^[A-Z]:\\\\/i', $path) || str_starts_with($path, '/')) {
            return $path;
        }

        return $this->basePath . $path;
    }

    /**
     * Registrar arquivo na tabela file_assets.
     */
    private function trackAsset(array $data): ?int
    {
        try {
            $tenantId = (int) ($_SESSION['tenant']['id'] ?? 0);
            $userId = (int) ($_SESSION['user_id'] ?? 0);

            $stmt = $this->db->prepare("
                INSERT INTO `file_assets`
                    (`tenant_id`, `disk`, `module`, `entity_type`, `entity_id`,
                     `original_name`, `stored_name`, `path`, `mime_type`, `size`,
                     `is_image`, `image_width`, `image_height`,
                     `has_thumbnail`, `thumbnail_path`, `created_by`)
                VALUES
                    (:tenant_id, :disk, :module, :entity_type, :entity_id,
                     :original_name, :stored_name, :path, :mime_type, :size,
                     :is_image, :image_width, :image_height,
                     :has_thumbnail, :thumbnail_path, :created_by)
            ");

            $stmt->execute([
                'tenant_id'      => $tenantId,
                'disk'           => $this->disk,
                'module'         => $data['module'],
                'entity_type'    => $data['entity_type'],
                'entity_id'     => $data['entity_id'],
                'original_name'  => $data['original_name'],
                'stored_name'    => $data['stored_name'],
                'path'           => $data['path'],
                'mime_type'      => $data['mime_type'],
                'size'           => $data['size'],
                'is_image'       => $data['is_image'],
                'image_width'    => $data['image_width'],
                'image_height'   => $data['image_height'],
                'has_thumbnail'  => $data['has_thumbnail'],
                'thumbnail_path' => $data['thumbnail_path'],
                'created_by'     => $userId ?: null,
            ]);

            return (int) $this->db->lastInsertId();
        } catch (\PDOException $e) {
            // Falha no tracking não deve impedir o upload
            error_log('[FileManager] Erro ao registrar asset: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Soft-delete de asset no banco.
     */
    private function softDeleteAsset(string $path): void
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE `file_assets` SET `deleted_at` = NOW() WHERE `path` = :path AND `deleted_at` IS NULL
            ");
            $stmt->execute(['path' => $path]);
        } catch (\PDOException $e) {
            error_log('[FileManager] Erro ao soft-delete asset: ' . $e->getMessage());
        }
    }

    /**
     * Parsear preset de tamanho para dimensões.
     */
    private function parseSizePreset(string $size): array
    {
        $presets = [
            'xs' => ['width' => 40,  'height' => 40],
            'sm' => ['width' => 80,  'height' => 80],
            'md' => ['width' => 150, 'height' => 150],
            'lg' => ['width' => 300, 'height' => 300],
            'xl' => ['width' => 600, 'height' => 600],
        ];

        if (isset($presets[$size])) {
            return $presets[$size];
        }

        // Formato WxH
        if (preg_match('/^(\d+)x(\d+)$/', $size, $m)) {
            return ['width' => (int) $m[1], 'height' => (int) $m[2]];
        }

        // Apenas largura
        if (is_numeric($size)) {
            return ['width' => (int) $size, 'height' => null];
        }

        return ['width' => 150, 'height' => null];
    }

    /**
     * Mensagem de erro por código de upload.
     */
    private function getUploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE   => 'O arquivo excede o tamanho máximo permitido pelo servidor.',
            UPLOAD_ERR_FORM_SIZE  => 'O arquivo excede o tamanho máximo do formulário.',
            UPLOAD_ERR_PARTIAL    => 'O arquivo foi enviado parcialmente.',
            UPLOAD_ERR_NO_FILE    => 'Nenhum arquivo foi enviado.',
            UPLOAD_ERR_NO_TMP_DIR => 'Diretório temporário não encontrado.',
            UPLOAD_ERR_CANT_WRITE => 'Falha ao gravar o arquivo no disco.',
            UPLOAD_ERR_EXTENSION  => 'Upload bloqueado por extensão PHP.',
            default               => 'Erro desconhecido no upload.',
        };
    }
}
