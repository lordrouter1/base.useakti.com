<?php
namespace Akti\Services;

/**
 * PortalLang — Sistema de tradução (i18n) do Portal do Cliente.
 *
 * Carrega arquivos de idioma de app/lang/{idioma}/portal.php
 * e fornece traduções via método estático get().
 *
 * Uso em views:
 *   <?= \Akti\Services\PortalLang::get('login_title') ?>
 *   <?= __p('login_title') ?>  // via helper global
 *
 * Placeholders:
 *   __p('dashboard_greeting', ['name' => 'João'])  // "Olá, João!"
 *
 * @package Akti\Services
 */
class PortalLang
{
    /** @var array Traduções carregadas */
    private static array $translations = [];

    /** @var string Idioma atual */
    private static string $lang = 'pt-br';

    /** @var bool Se já foi inicializado */
    private static bool $initialized = false;

    /**
     * Inicializa o sistema de tradução com o idioma especificado.
     *
     * @param string $lang Código do idioma (ex: 'pt-br', 'en', 'es')
     * @return void
     */
    public static function init(string $lang = 'pt-br'): void
    {
        self::$lang = $lang;
        self::$translations = self::loadTranslations($lang);
        self::$initialized = true;
    }

    /**
     * Retorna a tradução de uma chave, com suporte a placeholders.
     *
     * @param string $key       Chave de tradução (ex: 'login_title')
     * @param array  $params    Placeholders (ex: ['name' => 'João'])
     * @param string|null $default Valor padrão se a chave não existir
     * @return string
     */
    public static function get(string $key, array $params = [], ?string $default = null): string
    {
        if (!self::$initialized) {
            self::init($_SESSION['portal_lang'] ?? 'pt-br');
        }

        $text = self::$translations[$key] ?? $default ?? $key;

        // Substituir placeholders :name por valores
        foreach ($params as $param => $value) {
            $text = str_replace(':' . $param, (string) $value, $text);
        }

        return $text;
    }

    /**
     * Retorna o idioma atual.
     *
     * @return string
     */
    public static function getLang(): string
    {
        return self::$lang;
    }

    /**
     * Lista idiomas disponíveis.
     *
     * @return array [code => label]
     */
    public static function getAvailableLanguages(): array
    {
        return [
            'pt-br' => 'Português (Brasil)',
            'en'    => 'English',
            'es'    => 'Español',
        ];
    }

    /**
     * Carrega o arquivo de traduções para o idioma especificado.
     * Fallback para pt-br se o arquivo não existir.
     *
     * @param string $lang
     * @return array
     */
    private static function loadTranslations(string $lang): array
    {
        $file = AKTI_BASE_PATH . "app/lang/{$lang}/portal.php";

        if (!file_exists($file)) {
            // Fallback para pt-br
            $file = AKTI_BASE_PATH . 'app/lang/pt-br/portal.php';
        }

        if (!file_exists($file)) {
            return [];
        }

        $translations = require $file;
        return is_array($translations) ? $translations : [];
    }
}
