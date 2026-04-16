<?php

namespace Akti\Utils;

/**
 * Vite asset helper — reads the manifest produced by `npm run build`
 * and returns the hashed file paths.
 *
 * Usage: ViteAssets::css('theme') or ViteAssets::js('app')
 * Returns null when the manifest doesn't exist (dev mode / not yet built).
 */
class ViteAssets
{
    private static ?array $manifest = null;
    private static bool $loaded = false;

    /**
     * Carrega dados.
     * @return void
     */
    private static function load(): void
    {
        if (self::$loaded) {
            return;
        }
        self::$loaded = true;
        $path = __DIR__ . '/../../assets/dist/.vite/manifest.json';
        if (file_exists($path)) {
            $json = file_get_contents($path);
            self::$manifest = json_decode($json, true) ?: null;
        }
    }

    /**
     * Verifica uma condição booleana.
     * @return bool
     */
    public static function isBuilt(): bool
    {
        self::load();
        return self::$manifest !== null;
    }

    /**
     * Css.
     *
     * @param string $name Nome
     * @return string|null
     */
    public static function css(string $name): ?string
    {
        self::load();
        if (!self::$manifest) {
            return null;
        }

        // Search for CSS entry by name
        foreach (self::$manifest as $key => $entry) {
            if (str_contains($key, $name) && !empty($entry['css'])) {
                return 'assets/dist/' . $entry['css'][0];
            }
            if (str_contains($key, $name) && !empty($entry['file']) && str_ends_with($entry['file'], '.css')) {
                return 'assets/dist/' . $entry['file'];
            }
        }
        return null;
    }

    /**
     * Js.
     *
     * @param string $name Nome
     * @return string|null
     */
    public static function js(string $name): ?string
    {
        self::load();
        if (!self::$manifest) {
            return null;
        }

        foreach (self::$manifest as $key => $entry) {
            if (str_contains($key, $name) && !empty($entry['file']) && str_ends_with($entry['file'], '.js')) {
                return 'assets/dist/' . $entry['file'];
            }
        }
        return null;
    }

    /**
     * Tag.
     *
     * @param string $type Tipo do recurso
     * @param string $name Nome
     * @param string $extra Extra
     * @return string
     */
    public static function tag(string $type, string $name, string $extra = ''): string
    {
        if ($type === 'css') {
            $file = self::css($name);
            return $file ? '<link rel="stylesheet" href="' . htmlspecialchars($file) . '">' : '';
        }
        $file = self::js($name);
        $attr = $extra ? ' ' . $extra : '';
        return $file ? '<script src="' . htmlspecialchars($file) . '"' . $attr . '></script>' : '';
    }
}
