<?php
/**
 * Internationalization (i18n) Helper — Akti
 * FEAT-009: Multi-language support
 *
 * Provides the __() function for translating strings.
 * Loaded automatically by autoload.php.
 *
 * Usage in views:
 *   <h1><?= __('dashboard') ?></h1>
 *   <button><?= __('save') ?></button>
 *
 * Locale resolution order:
 *   1. $_SESSION['locale'] (user preference)
 *   2. $_SESSION['tenant_locale'] (tenant default)
 *   3. 'pt-br' (system default)
 */
function __($key, array $replace = [], string $group = 'app'): string
{
    static $cache = [];

    $locale = $_SESSION['locale'] ?? $_SESSION['tenant_locale'] ?? 'pt-br';
    $cacheKey = "{$locale}.{$group}";

    if (!isset($cache[$cacheKey])) {
        $basePath = (defined('AKTI_BASE_PATH') ? AKTI_BASE_PATH : '') . 'app/lang/';
        $file = $basePath . $locale . '/' . $group . '.php';

        if (file_exists($file)) {
            $cache[$cacheKey] = require $file;
        } else {
            // Fallback to pt-br
            $fallback = $basePath . 'pt-br/' . $group . '.php';
            $cache[$cacheKey] = file_exists($fallback) ? require $fallback : [];
        }
    }

    $translation = $cache[$cacheKey][$key] ?? $key;

    foreach ($replace as $placeholder => $value) {
        $translation = str_replace(':' . $placeholder, (string) $value, $translation);
    }

    return $translation;
}

/**
 * Get the current locale.
 *
 * @return string
 */
function currentLocale(): string
{
    return $_SESSION['locale'] ?? $_SESSION['tenant_locale'] ?? 'pt-br';
}

/**
 * Set the current locale for the session.
 *
 * @param string $locale
 */
function _setLocale(string $locale): void
{
    $allowed = ['pt-br', 'en', 'es'];
    if (in_array($locale, $allowed)) {
        $_SESSION['locale'] = $locale;
    }
}
