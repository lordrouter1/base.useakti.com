<?php
/**
 * Sanitizer — Akti
 *
 * Classe utilitária estática para sanitização de entrada de dados.
 * Remove caracteres perigosos e normaliza valores antes do processamento.
 *
 * Uso:
 *   $name  = Sanitizer::string($_POST['name']);
 *   $email = Sanitizer::email($_POST['email']);
 *   $price = Sanitizer::float($_POST['price']); // aceita "1.234,56" (PT-BR)
 *   $id    = Sanitizer::int($_POST['id']);
 *
 * @see PROJECT_RULES.md — Módulo: Sanitização e Validação
 */

namespace Akti\Utils;

/**
 * Sanitizador de dados de entrada.
 */
class Sanitizer
{
    // ──────────────────────────────────────────────
    // Tipos primitivos
    // ──────────────────────────────────────────────

    /**
     * Sanitiza string genérica: trim + strip_tags.
     * Não aplica htmlspecialchars aqui — isso é responsabilidade da camada de saída (Escape).
     *
     * @param  mixed       $value
     * @param  string|null $default Valor padrão se vazio/null
     * @return string
     */
    public static function string($value, ?string $default = ''): string
    {
        if ($value === null || $value === false) {
            return $default ?? '';
        }
        return trim(strip_tags((string) $value));
    }

    /**
     * Sanitiza string preservando algumas tags HTML permitidas.
     *
     * @param  mixed  $value
     * @param  string $allowedTags Tags permitidas (ex: '<b><i><br><p><ul><li>')
     * @return string
     */
    public static function richText($value, string $allowedTags = '<b><i><br><p><ul><ol><li><strong><em>'): string
    {
        if ($value === null || $value === false) {
            return '';
        }
        return trim(strip_tags((string) $value, $allowedTags));
    }

    /**
     * Sanitiza e converte para inteiro.
     *
     * @param  mixed    $value
     * @param  int|null $default
     * @return int|null
     */
    public static function int($value, ?int $default = null): ?int
    {
        if ($value === null || $value === '' || $value === false) {
            return $default;
        }
        $filtered = filter_var($value, FILTER_VALIDATE_INT);
        return $filtered !== false ? $filtered : $default;
    }

    /**
     * Sanitiza e converte para float, aceitando formato PT-BR ("1.234,56").
     *
     * @param  mixed       $value
     * @param  float|null  $default
     * @return float|null
     */
    public static function float($value, ?float $default = null): ?float
    {
        if ($value === null || $value === '' || $value === false) {
            return $default;
        }

        $str = trim((string) $value);

        // Detectar formato PT-BR: "1.234,56" ou "1234,56"
        if (preg_match('/^\-?\d{1,3}(\.\d{3})*(,\d+)?$/', $str) || preg_match('/^\-?\d+(,\d+)$/', $str)) {
            $str = str_replace('.', '', $str);   // remove separador de milhar
            $str = str_replace(',', '.', $str);  // troca vírgula decimal por ponto
        }

        $filtered = filter_var($str, FILTER_VALIDATE_FLOAT);
        return $filtered !== false ? (float) $filtered : $default;
    }

    /**
     * Sanitiza valor booleano.
     * Aceita: 1, '1', 'true', 'on', 'yes', 'sim' como true.
     *
     * @param  mixed $value
     * @return bool
     */
    public static function bool($value): bool
    {
        if ($value === null || $value === '' || $value === false) {
            return false;
        }
        $str = strtolower(trim((string) $value));
        return in_array($str, ['1', 'true', 'on', 'yes', 'sim'], true);
    }

    // ──────────────────────────────────────────────
    // Tipos específicos
    // ──────────────────────────────────────────────

    /**
     * Sanitiza e-mail: trim + lowercase + filter.
     *
     * @param  mixed       $value
     * @param  string|null $default
     * @return string
     */
    public static function email($value, ?string $default = ''): string
    {
        if ($value === null || $value === '' || $value === false) {
            return $default ?? '';
        }
        $sanitized = filter_var(trim(strtolower((string) $value)), FILTER_SANITIZE_EMAIL);
        return $sanitized !== false ? $sanitized : ($default ?? '');
    }

    /**
     * Sanitiza telefone: remove tudo exceto dígitos, +, ( e ).
     *
     * @param  mixed  $value
     * @return string
     */
    public static function phone($value): string
    {
        if ($value === null || $value === '' || $value === false) {
            return '';
        }
        return preg_replace('/[^\d\+\(\)\-\s]/', '', trim((string) $value));
    }

    /**
     * Sanitiza CPF/CNPJ: remove tudo exceto dígitos.
     *
     * @param  mixed  $value
     * @return string Apenas dígitos
     */
    public static function document($value): string
    {
        if ($value === null || $value === '' || $value === false) {
            return '';
        }
        return preg_replace('/\D/', '', trim((string) $value));
    }

    /**
     * Sanitiza CEP: remove tudo exceto dígitos.
     *
     * @param  mixed  $value
     * @return string
     */
    public static function cep($value): string
    {
        if ($value === null || $value === '' || $value === false) {
            return '';
        }
        return preg_replace('/\D/', '', trim((string) $value));
    }

    /**
     * Sanitiza URL.
     *
     * @param  mixed  $value
     * @return string
     */
    public static function url($value): string
    {
        if ($value === null || $value === '' || $value === false) {
            return '';
        }
        $sanitized = filter_var(trim((string) $value), FILTER_SANITIZE_URL);
        return $sanitized !== false ? $sanitized : '';
    }

    /**
     * Sanitiza data no formato Y-m-d.
     * Retorna string vazia se inválido.
     *
     * @param  mixed       $value
     * @param  string|null $default
     * @return string|null
     */
    public static function date($value, ?string $default = null): ?string
    {
        if ($value === null || $value === '' || $value === false) {
            return $default;
        }
        $str = trim((string) $value);
        $dt = \DateTime::createFromFormat('Y-m-d', $str);
        if ($dt && $dt->format('Y-m-d') === $str) {
            return $str;
        }
        return $default;
    }

    /**
     * Sanitiza datetime no formato Y-m-d H:i:s.
     *
     * @param  mixed       $value
     * @param  string|null $default
     * @return string|null
     */
    public static function datetime($value, ?string $default = null): ?string
    {
        if ($value === null || $value === '' || $value === false) {
            return $default;
        }
        $str = trim((string) $value);
        $dt = \DateTime::createFromFormat('Y-m-d H:i:s', $str);
        if ($dt && $dt->format('Y-m-d H:i:s') === $str) {
            return $str;
        }
        return $default;
    }

    /**
     * Sanitiza slug: lowercase, sem acentos, apenas a-z 0-9 e hífens.
     *
     * @param  mixed  $value
     * @return string
     */
    public static function slug($value): string
    {
        if ($value === null || $value === '' || $value === false) {
            return '';
        }
        $str = trim((string) $value);
        $str = mb_strtolower($str, 'UTF-8');
        // Transliterar acentos
        $str = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
        // Manter apenas letras, números, hífens
        $str = preg_replace('/[^a-z0-9\-]/', '-', $str);
        $str = preg_replace('/-+/', '-', $str);
        return trim($str, '-');
    }

    // ──────────────────────────────────────────────
    // Arrays
    // ──────────────────────────────────────────────

    /**
     * Sanitiza um array de inteiros.
     *
     * @param  mixed $value
     * @return array<int>
     */
    public static function intArray($value): array
    {
        if (!is_array($value)) {
            return [];
        }
        return array_values(array_filter(array_map(function ($v) {
            return self::int($v);
        }, $value), function ($v) {
            return $v !== null;
        }));
    }

    /**
     * Sanitiza um array de strings.
     *
     * @param  mixed $value
     * @return array<string>
     */
    public static function stringArray($value): array
    {
        if (!is_array($value)) {
            return [];
        }
        return array_values(array_map(function ($v) {
            return self::string($v);
        }, $value));
    }

    // ──────────────────────────────────────────────
    // Helpers para valores em whitelist
    // ──────────────────────────────────────────────

    /**
     * Valida se o valor está em uma lista de opções permitidas.
     * Retorna o valor se válido ou o default.
     *
     * @param  mixed       $value
     * @param  array       $allowed  Lista de valores permitidos
     * @param  mixed       $default  Valor padrão se não estiver na lista
     * @return mixed
     */
    public static function enum($value, array $allowed, $default = null)
    {
        $str = self::string($value);
        return in_array($str, $allowed, true) ? $str : $default;
    }

    /**
     * Sanitiza um nome de arquivo: remove caracteres perigosos, preserva extensão.
     *
     * @param  mixed  $value
     * @return string
     */
    public static function filename($value): string
    {
        if ($value === null || $value === '' || $value === false) {
            return '';
        }
        $str = trim((string) $value);
        // Remove path traversal
        $str = basename($str);
        // Remove caracteres perigosos
        $str = preg_replace('/[^\w\.\-]/', '_', $str);
        return $str;
    }

    /**
     * Sanitiza JSON string: decode + re-encode para garantir formato válido.
     *
     * @param  mixed       $value
     * @param  string|null $default
     * @return string|null JSON string válido ou default
     */
    public static function json($value, ?string $default = null): ?string
    {
        if ($value === null || $value === '' || $value === false) {
            return $default;
        }
        $decoded = json_decode((string) $value, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $default;
        }
        return json_encode($decoded, JSON_UNESCAPED_UNICODE);
    }
}
