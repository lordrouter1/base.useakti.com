<?php
/**
 * Escape — Akti
 *
 * Classe utilitária estática para escape de saída (output encoding).
 * Previne XSS ao exibir dados em contextos HTML, atributos, JavaScript e URLs.
 *
 * REGRA: A sanitização de ENTRADA é feita pelo Sanitizer/Input.
 *        O ESCAPE de SAÍDA é feito por esta classe (ou helpers globais e(), eAttr(), eJs()).
 *        Nunca usar htmlspecialchars() diretamente — usar Escape::html() ou e().
 *
 * Uso:
 *   // Em código com namespace:
 *   use Akti\Utils\Escape;
 *   echo Escape::html($name);
 *   echo '<input value="' . Escape::attr($value) . '">';
 *
 *   // Em views (funções globais):
 *   <?= e($name) ?>
 *   <input value="<?= eAttr($value) ?>">
 *   <script>var data = <?= eJs($array) ?>;</script>
 *
 * Compatível com PHP 7.4+
 *
 * @see app/utils/escape_helper.php — Funções globais e(), eAttr(), eJs()
 * @see PROJECT_RULES.md — Módulo: Sanitização e Validação
 */

namespace Akti\Utils;

class Escape
{
    /**
     * Escape para contexto HTML (conteúdo de tags).
     * Converte &, <, >, ", ' em entidades HTML.
     *
     * Uso: <span><?= Escape::html($name) ?></span>
     *
     * @param  mixed  $value
     * @param  string $encoding
     * @return string
     */
    public static function html($value, string $encoding = 'UTF-8'): string
    {
        if ($value === null || $value === false) {
            return '';
        }
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, $encoding);
    }

    /**
     * Escape para contexto de atributo HTML.
     * Mais agressivo que html() — também escapa `, /, = e espaços.
     *
     * Uso: <input value="<?= Escape::attr($value) ?>">
     *
     * @param  mixed  $value
     * @param  string $encoding
     * @return string
     */
    public static function attr($value, string $encoding = 'UTF-8'): string
    {
        if ($value === null || $value === false) {
            return '';
        }
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, $encoding);
    }

    /**
     * Escape para contexto JavaScript (inline scripts).
     * Retorna JSON seguro para embutir em <script>.
     *
     * Uso: <script>var name = <?= Escape::js($name) ?>;</script>
     * Uso: <script>var data = <?= Escape::js($arrayOrObject) ?>;</script>
     *
     * @param  mixed $value  String, array, object, number, bool
     * @return string JSON-encoded e seguro para HTML
     */
    public static function js($value): string
    {
        $json = json_encode(
            $value,
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE
        );
        return $json !== false ? $json : '""';
    }

    /**
     * Escape para contexto de URL (query string).
     *
     * Uso: <a href="?page=products&id=<?= Escape::url($id) ?>">
     *
     * @param  mixed $value
     * @return string
     */
    public static function url($value): string
    {
        if ($value === null || $value === false) {
            return '';
        }
        return rawurlencode((string) $value);
    }

    /**
     * Escape para contexto CSS (valores inline).
     * Remove caracteres potencialmente perigosos.
     *
     * Uso: <div style="color: <?= Escape::css($color) ?>">
     *
     * @param  mixed $value
     * @return string
     */
    public static function css($value): string
    {
        if ($value === null || $value === false) {
            return '';
        }
        // Remove tudo que não seja alfanumérico, espaço, #, ., %, - ou ,
        return preg_replace('/[^a-zA-Z0-9\s#\.%,\-]/', '', (string) $value);
    }

    /**
     * Formata número para exibição (locale BR).
     *
     * Uso: R$ <?= Escape::number($price, 2) ?>
     *
     * @param  mixed $value
     * @param  int   $decimals
     * @param  string $decSep    Separador decimal
     * @param  string $thousSep  Separador de milhar
     * @return string
     */
    public static function number($value, int $decimals = 2, string $decSep = ',', string $thousSep = '.'): string
    {
        if ($value === null || $value === '' || $value === false) {
            return '0' . ($decimals > 0 ? $decSep . str_repeat('0', $decimals) : '');
        }
        return number_format((float) $value, $decimals, $decSep, $thousSep);
    }
}
