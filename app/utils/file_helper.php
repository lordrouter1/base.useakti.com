<?php

/**
 * File helpers — Funções globais para gestão de arquivos e URLs.
 *
 * Fornece atalhos para o FileManager, usados em views e controllers.
 * Carregado automaticamente pelo autoloader.
 */

use Akti\Services\FileManager;
use Akti\Services\ThumbnailService;

/**
 * Obter URL de um arquivo com suporte a thumbnail.
 *
 * @param string|null $path Caminho relativo do arquivo (salvo no BD)
 * @param string|null $size Preset de tamanho: 'xs'(40px), 'sm'(80px), 'md'(150px), 'lg'(300px), 'xl'(600px), ou 'WxH'
 * @return string URL do arquivo ou string vazia
 */
function file_url(?string $path, ?string $size = null): string
{
    if (empty($path)) {
        return '';
    }

    if ($size === null) {
        return $path;
    }

    static $fm = null;
    if ($fm === null) {
        $fm = new FileManager();
    }

    return $fm->getUrl($path, $size);
}

/**
 * Obter URL de thumbnail de uma imagem.
 *
 * @param string|null $path   Caminho da imagem original
 * @param int         $width  Largura desejada em pixels
 * @param int|null    $height Altura (null = proporcional)
 * @return string URL do thumbnail ou imagem original
 */
function thumb_url(?string $path, int $width, ?int $height = null): string
{
    if (empty($path)) {
        return '';
    }

    // SVG não pode ser redimensionado via GD
    if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'svg') {
        return $path;
    }

    static $fm = null;
    if ($fm === null) {
        $fm = new FileManager();
    }

    return $fm->thumbUrl($path, $width, $height);
}

/**
 * Verificar se um path de arquivo é uma imagem.
 *
 * @param string|null $path Caminho do arquivo
 * @return bool
 */
function is_file_image(?string $path): bool
{
    if (empty($path)) {
        return false;
    }

    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp']);
}

/**
 * Obter URL de imagem com fallback para placeholder.
 *
 * @param string|null $path        Caminho da imagem
 * @param string      $placeholder URL do placeholder (default: ícone genérico)
 * @param string|null $size        Preset de tamanho do thumbnail
 * @return string URL da imagem ou placeholder
 */
function file_url_or(
    ?string $path,
    string $placeholder = 'assets/img/default-avatar.png',
    ?string $size = null
): string {
    if (empty($path)) {
        return $placeholder;
    }

    return file_url($path, $size);
}
