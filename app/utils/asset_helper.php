<?php
/**
 * Asset Helper — Cache Busting via file modification time
 *
 * Gera URLs de assets com query string de versão baseada no mtime do arquivo.
 * Isso garante que o navegador sempre carregue a versão mais recente após
 * qualquer alteração no arquivo CSS/JS/imagem.
 *
 * Exemplo:
 *   <link href="<?= asset('assets/css/style.css') ?>">
 *   Saída: assets/css/style.css?v=1711800000
 *
 * @see ROADMAP_DETALHADO_2026.md — Fase 4, item 4.4
 * @package Akti\Utils
 */
function asset(string $path): string
{
    $basePath = defined('AKTI_BASE_PATH') ? AKTI_BASE_PATH : (__DIR__ . '/../../');
    $file = rtrim($basePath, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
    $version = file_exists($file) ? filemtime($file) : time();
    return $path . '?v=' . $version;
}
