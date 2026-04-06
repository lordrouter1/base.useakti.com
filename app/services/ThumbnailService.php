<?php

namespace Akti\Services;

/**
 * ThumbnailService — Geração e gestão de thumbnails para imagens.
 *
 * Gera thumbnails em tamanhos padronizados, armazenando-os em
 * subdiretório `_thumbs/` relativo ao arquivo original.
 *
 * Requer extensão GD do PHP. Se GD não estiver disponível,
 * retorna o caminho original da imagem.
 */
class ThumbnailService
{
    private const THUMB_DIR = '_thumbs';
    private const QUALITY_JPEG = 80;
    private const QUALITY_PNG = 8;
    private const QUALITY_WEBP = 80;

    /**
     * Gerar thumbnail de uma imagem.
     *
     * @param string   $sourcePath Caminho relativo da imagem original
     * @param int      $width      Largura desejada
     * @param int|null $height     Altura (null = proporcional)
     * @param string   $mode       Modo: 'cover' (preenche/corta), 'contain' (proporcional)
     * @return string|null Caminho do thumbnail gerado, ou null se falhou
     */
    public function generate(string $sourcePath, int $width, ?int $height = null, string $mode = 'cover'): ?string
    {
        if (!$this->isGdAvailable()) {
            return null;
        }

        $fullSource = $this->resolveFullPath($sourcePath);
        if (!file_exists($fullSource)) {
            return null;
        }

        $thumbPath = $this->buildThumbPath($sourcePath, $width, $height);
        $fullThumb = $this->resolveFullPath($thumbPath);

        // Se thumbnail já existe e é mais recente que o original, retornar
        if (file_exists($fullThumb) && filemtime($fullThumb) >= filemtime($fullSource)) {
            return $thumbPath;
        }

        // Criar diretório do thumb
        $thumbDir = dirname($fullThumb);
        if (!is_dir($thumbDir)) {
            mkdir($thumbDir, 0755, true);
        }

        // Carregar imagem original
        $sourceImage = $this->loadImage($fullSource);
        if (!$sourceImage) {
            return null;
        }

        $origWidth  = imagesx($sourceImage);
        $origHeight = imagesy($sourceImage);

        // Calcular dimensões
        $targetHeight = $height ?? (int) round($origHeight * ($width / $origWidth));

        if ($mode === 'cover' && $height !== null) {
            $dims = $this->calculateCoverDimensions($origWidth, $origHeight, $width, $targetHeight);
        } else {
            $dims = $this->calculateContainDimensions($origWidth, $origHeight, $width, $targetHeight);
        }

        // Criar thumbnail
        $thumb = imagecreatetruecolor($dims['dst_width'], $dims['dst_height']);

        // Preservar transparência para PNG e WebP
        $ext = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
        if (in_array($ext, ['png', 'webp'])) {
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
            $transparent = imagecolorallocatealpha($thumb, 0, 0, 0, 127);
            imagefilledrectangle($thumb, 0, 0, $dims['dst_width'] - 1, $dims['dst_height'] - 1, $transparent);
        }

        // Redimensionar com resampling
        imagecopyresampled(
            $thumb, $sourceImage,
            0, 0,
            $dims['src_x'], $dims['src_y'],
            $dims['dst_width'], $dims['dst_height'],
            $dims['src_width'], $dims['src_height']
        );

        // Salvar
        $saved = $this->saveImage($thumb, $fullThumb, $ext);

        imagedestroy($sourceImage);
        imagedestroy($thumb);

        return $saved ? $thumbPath : null;
    }

    /**
     * Obter thumbnail existente ou criar um novo.
     */
    public function getOrCreate(string $sourcePath, int $width, ?int $height = null): ?string
    {
        $thumbPath = $this->buildThumbPath($sourcePath, $width, $height);
        $fullThumb = $this->resolveFullPath($thumbPath);
        $fullSource = $this->resolveFullPath($sourcePath);

        // Cache hit: thumb existe e é mais recente
        if (file_exists($fullThumb) && file_exists($fullSource) && filemtime($fullThumb) >= filemtime($fullSource)) {
            return $thumbPath;
        }

        return $this->generate($sourcePath, $width, $height);
    }

    /**
     * Deletar todos os thumbnails de uma imagem.
     */
    public function deleteThumbnails(string $sourcePath): void
    {
        $dir = dirname($sourcePath);
        $filename = pathinfo($sourcePath, PATHINFO_FILENAME);
        $thumbDir = $this->resolveFullPath($dir . '/' . self::THUMB_DIR);

        if (!is_dir($thumbDir)) {
            return;
        }

        $pattern = $thumbDir . '/' . $filename . '_*';
        foreach (glob($pattern) as $file) {
            unlink($file);
        }
    }

    /**
     * Verificar se GD está disponível.
     */
    public function isGdAvailable(): bool
    {
        return extension_loaded('gd') && function_exists('imagecreatetruecolor');
    }

    // ──────────────── Métodos Internos ────────────────

    /**
     * Construir caminho do thumbnail.
     * Ex: products/foto.jpg → products/_thumbs/foto_150x150.jpg
     */
    private function buildThumbPath(string $sourcePath, int $width, ?int $height): string
    {
        $dir      = dirname($sourcePath);
        $filename = pathinfo($sourcePath, PATHINFO_FILENAME);
        $ext      = pathinfo($sourcePath, PATHINFO_EXTENSION);
        $sizePart = $height ? "{$width}x{$height}" : "{$width}w";

        return $dir . '/' . self::THUMB_DIR . '/' . $filename . '_' . $sizePart . '.' . $ext;
    }

    /**
     * Resolver caminho completo do arquivo.
     */
    private function resolveFullPath(string $path): string
    {
        if (preg_match('/^[A-Z]:\\\\/i', $path) || str_starts_with($path, '/')) {
            return $path;
        }

        $basePath = defined('AKTI_BASE_PATH')
            ? rtrim(AKTI_BASE_PATH, '/\\') . '/'
            : rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\') . '/';

        return $basePath . $path;
    }

    /**
     * Carregar imagem de um arquivo.
     */
    private function loadImage(string $fullPath): ?\GdImage
    {
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($fullPath);

        return match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($fullPath) ?: null,
            'image/png'  => @imagecreatefrompng($fullPath) ?: null,
            'image/gif'  => @imagecreatefromgif($fullPath) ?: null,
            'image/webp' => function_exists('imagecreatefromwebp')
                ? (@imagecreatefromwebp($fullPath) ?: null)
                : null,
            'image/bmp'  => function_exists('imagecreatefrombmp')
                ? (@imagecreatefrombmp($fullPath) ?: null)
                : null,
            default      => null,
        };
    }

    /**
     * Salvar imagem processada.
     */
    private function saveImage(\GdImage $image, string $fullPath, string $ext): bool
    {
        return match ($ext) {
            'jpg', 'jpeg' => imagejpeg($image, $fullPath, self::QUALITY_JPEG),
            'png'         => imagepng($image, $fullPath, self::QUALITY_PNG),
            'gif'         => imagegif($image, $fullPath),
            'webp'        => function_exists('imagewebp')
                ? imagewebp($image, $fullPath, self::QUALITY_WEBP)
                : imagejpeg($image, $fullPath, self::QUALITY_JPEG),
            'bmp'         => function_exists('imagebmp')
                ? imagebmp($image, $fullPath)
                : imagejpeg($image, $fullPath, self::QUALITY_JPEG),
            default       => imagejpeg($image, $fullPath, self::QUALITY_JPEG),
        };
    }

    /**
     * Calcular dimensões para modo "cover" (preenche e corta).
     */
    private function calculateCoverDimensions(int $srcW, int $srcH, int $dstW, int $dstH): array
    {
        $srcRatio = $srcW / $srcH;
        $dstRatio = $dstW / $dstH;

        if ($srcRatio > $dstRatio) {
            // Imagem mais larga: cortar lados
            $cropW = (int) round($srcH * $dstRatio);
            $cropH = $srcH;
            $srcX = (int) round(($srcW - $cropW) / 2);
            $srcY = 0;
        } else {
            // Imagem mais alta: cortar topo/base
            $cropW = $srcW;
            $cropH = (int) round($srcW / $dstRatio);
            $srcX = 0;
            $srcY = (int) round(($srcH - $cropH) / 2);
        }

        return [
            'dst_width'  => $dstW,
            'dst_height' => $dstH,
            'src_x'      => $srcX,
            'src_y'      => $srcY,
            'src_width'  => $cropW,
            'src_height' => $cropH,
        ];
    }

    /**
     * Calcular dimensões para modo "contain" (proporcional, sem corte).
     */
    private function calculateContainDimensions(int $srcW, int $srcH, int $dstW, int $dstH): array
    {
        $ratio = min($dstW / $srcW, $dstH / $srcH);
        $newW = (int) round($srcW * $ratio);
        $newH = (int) round($srcH * $ratio);

        return [
            'dst_width'  => $newW,
            'dst_height' => $newH,
            'src_x'      => 0,
            'src_y'      => 0,
            'src_width'  => $srcW,
            'src_height' => $srcH,
        ];
    }
}
