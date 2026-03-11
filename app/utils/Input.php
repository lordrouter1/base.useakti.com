<?php
/**
 * Input — Akti
 *
 * Wrapper seguro para acesso a variáveis superglobais ($_POST, $_GET, $_REQUEST).
 * Aplica sanitização automática via Sanitizer ao recuperar valores.
 *
 * Uso:
 *   $name  = Input::post('name');                     // Sanitizer::string por padrão
 *   $email = Input::post('email', 'email');            // Sanitizer::email
 *   $price = Input::post('price', 'float');            // Sanitizer::float (aceita PT-BR)
 *   $id    = Input::get('id', 'int');                  // Sanitizer::int
 *   $role  = Input::post('role', 'enum', 'user', ['admin', 'user', 'viewer']);
 *   $ids   = Input::post('ids', 'intArray');           // Sanitizer::intArray
 *   $all   = Input::allPost(['name', 'email', 'phone']); // Múltiplos campos
 *
 * Compatível com PHP 7.4+
 *
 * @see Akti\Utils\Sanitizer
 * @see PROJECT_RULES.md — Módulo: Sanitização e Validação
 */

namespace Akti\Utils;

class Input
{
    // ──────────────────────────────────────────────
    // Acesso por fonte (POST, GET, REQUEST)
    // ──────────────────────────────────────────────

    /**
     * Obtém valor de $_POST com sanitização.
     *
     * @param  string     $key     Nome do campo
     * @param  string     $type    Tipo de sanitização (string, int, float, email, bool, date, etc.)
     * @param  mixed      $default Valor padrão se ausente
     * @param  array|null $options Opções extras (ex: lista de valores para 'enum')
     * @return mixed
     */
    public static function post(string $key, string $type = 'string', $default = null, ?array $options = null)
    {
        $value = $_POST[$key] ?? null;
        return self::sanitize($value, $type, $default, $options);
    }

    /**
     * Obtém valor de $_GET com sanitização.
     */
    public static function get(string $key, string $type = 'string', $default = null, ?array $options = null)
    {
        $value = $_GET[$key] ?? null;
        return self::sanitize($value, $type, $default, $options);
    }

    /**
     * Obtém valor de $_REQUEST com sanitização.
     */
    public static function request(string $key, string $type = 'string', $default = null, ?array $options = null)
    {
        $value = $_REQUEST[$key] ?? null;
        return self::sanitize($value, $type, $default, $options);
    }

    // ──────────────────────────────────────────────
    // Verificação de existência
    // ──────────────────────────────────────────────

    /**
     * Verifica se um campo existe em $_POST (e não está vazio).
     */
    public static function hasPost(string $key): bool
    {
        return isset($_POST[$key]) && $_POST[$key] !== '';
    }

    /**
     * Verifica se um campo existe em $_GET (e não está vazio).
     */
    public static function hasGet(string $key): bool
    {
        return isset($_GET[$key]) && $_GET[$key] !== '';
    }

    // ──────────────────────────────────────────────
    // Acesso em lote
    // ──────────────────────────────────────────────

    /**
     * Obtém múltiplos campos de $_POST com sanitização.
     *
     * @param  array $fields Mapa de campo => tipo ou lista de campos (tipo 'string' padrão)
     *                       Exemplos:
     *                       ['name', 'email']  →  ambos como 'string'
     *                       ['name' => 'string', 'price' => 'float', 'id' => 'int']
     * @return array<string, mixed>
     */
    public static function allPost(array $fields): array
    {
        return self::allFrom($_POST, $fields);
    }

    /**
     * Obtém múltiplos campos de $_GET com sanitização.
     */
    public static function allGet(array $fields): array
    {
        return self::allFrom($_GET, $fields);
    }

    /**
     * Obtém um valor raw de $_POST sem sanitização (usar com cautela).
     * Útil para campos que precisam de tratamento especial (ex: password, rich text).
     *
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    public static function postRaw(string $key, $default = null)
    {
        return $_POST[$key] ?? $default;
    }

    /**
     * Obtém um valor raw de $_GET sem sanitização.
     */
    public static function getRaw(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * Obtém um array de $_POST (ex: grades[], items[]).
     * Retorna array vazio se o campo não existir ou não for array.
     *
     * @param  string $key
     * @return array
     */
    public static function postArray(string $key): array
    {
        return isset($_POST[$key]) && is_array($_POST[$key]) ? $_POST[$key] : [];
    }

    /**
     * Obtém um array de $_GET.
     */
    public static function getArray(string $key): array
    {
        return isset($_GET[$key]) && is_array($_GET[$key]) ? $_GET[$key] : [];
    }

    // ──────────────────────────────────────────────
    // Sanitização interna
    // ──────────────────────────────────────────────

    /**
     * Aplica sanitização pelo tipo especificado.
     *
     * @param  mixed      $value
     * @param  string     $type
     * @param  mixed      $default
     * @param  array|null $options
     * @return mixed
     */
    private static function sanitize($value, string $type, $default, ?array $options)
    {
        // Se o valor é null e não é 'bool', retorna o default
        if ($value === null && $type !== 'bool') {
            return $default;
        }

        switch ($type) {
            case 'string':
                return Sanitizer::string($value, $default);

            case 'richText':
            case 'rich_text':
                return Sanitizer::richText($value);

            case 'int':
            case 'integer':
                return Sanitizer::int($value, $default);

            case 'float':
            case 'decimal':
            case 'number':
                return Sanitizer::float($value, $default);

            case 'bool':
            case 'boolean':
                return Sanitizer::bool($value);

            case 'email':
                return Sanitizer::email($value, $default ?? '');

            case 'phone':
                return Sanitizer::phone($value);

            case 'document':
            case 'cpf':
            case 'cnpj':
                return Sanitizer::document($value);

            case 'cep':
                return Sanitizer::cep($value);

            case 'url':
                return Sanitizer::url($value);

            case 'date':
                return Sanitizer::date($value, $default);

            case 'datetime':
                return Sanitizer::datetime($value, $default);

            case 'slug':
                return Sanitizer::slug($value);

            case 'filename':
                return Sanitizer::filename($value);

            case 'json':
                return Sanitizer::json($value, $default);

            case 'enum':
                $allowed = $options ?? [];
                return Sanitizer::enum($value, $allowed, $default);

            case 'intArray':
            case 'int_array':
                return Sanitizer::intArray($value ?? []);

            case 'stringArray':
            case 'string_array':
                return Sanitizer::stringArray($value ?? []);

            case 'raw':
                return $value ?? $default;

            default:
                return Sanitizer::string($value, $default);
        }
    }

    /**
     * Helper interno para allPost / allGet.
     *
     * @param  array $source  $_POST ou $_GET
     * @param  array $fields  Definição dos campos
     * @return array<string, mixed>
     */
    private static function allFrom(array $source, array $fields): array
    {
        $result = [];

        foreach ($fields as $key => $type) {
            // Se o array usa chaves numéricas, o valor é o nome do campo e tipo é 'string'
            if (is_int($key)) {
                $fieldName = $type;
                $fieldType = 'string';
            } else {
                $fieldName = $key;
                $fieldType = $type;
            }

            $value = $source[$fieldName] ?? null;
            $result[$fieldName] = self::sanitize($value, $fieldType, null, null);
        }

        return $result;
    }
}
