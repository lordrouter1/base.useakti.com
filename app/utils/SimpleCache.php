<?php
namespace Akti\Utils;

/**
 * SimpleCache — Cache em sessão para dados frequentes
 *
 * Armazena dados em $_SESSION['_cache'] com controle de TTL.
 * Elimina consultas repetidas ao banco para dados que mudam raramente
 * (ex: company settings, permissões, menus, contagens de badges).
 *
 * Compatível com PHP 7.4+
 *
 * @package Akti\Utils
 */
class SimpleCache
{
    /**
     * Chave raiz do cache na sessão.
     */
    private const SESSION_KEY = '_cache';

    /**
     * Busca um valor no cache; se não existir ou expirado, executa o loader
     * e armazena o resultado com TTL.
     *
     * @param string   $key        Chave única do cache
     * @param int      $ttlSeconds Tempo de vida em segundos
     * @param callable $loader     Função que carrega os dados (chamada apenas se cache miss)
     * @return mixed   Dados do cache ou retorno do loader
     */
    public static function remember(string $key, int $ttlSeconds, callable $loader)
    {
        // Garantir que a sessão está ativa
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return $loader();
        }

        $cache = $_SESSION[self::SESSION_KEY] ?? [];

        // Verificar se o cache existe e não expirou
        if (isset($cache[$key]) && isset($cache[$key]['expires_at'])) {
            if ($cache[$key]['expires_at'] > time()) {
                return $cache[$key]['data'];
            }
        }

        // Cache miss ou expirado — executar loader
        $data = $loader();

        // Armazenar no cache
        $_SESSION[self::SESSION_KEY][$key] = [
            'data'       => $data,
            'expires_at' => time() + $ttlSeconds,
            'created_at' => time(),
        ];

        return $data;
    }

    /**
     * Retorna dados do cache sem executar loader.
     * Retorna null se não existir ou expirado.
     *
     * @param string $key Chave do cache
     * @return mixed|null Dados ou null
     */
    public static function get(string $key)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return null;
        }

        $cache = $_SESSION[self::SESSION_KEY] ?? [];

        if (isset($cache[$key]) && isset($cache[$key]['expires_at'])) {
            if ($cache[$key]['expires_at'] > time()) {
                return $cache[$key]['data'];
            }
            // Expirado — limpar
            unset($_SESSION[self::SESSION_KEY][$key]);
        }

        return null;
    }

    /**
     * Armazena um valor diretamente no cache.
     *
     * @param string $key        Chave do cache
     * @param mixed  $data       Dados a armazenar
     * @param int    $ttlSeconds TTL em segundos
     * @return void
     */
    public static function set(string $key, $data, int $ttlSeconds = 300): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION[self::SESSION_KEY][$key] = [
            'data'       => $data,
            'expires_at' => time() + $ttlSeconds,
            'created_at' => time(),
        ];
    }

    /**
     * Invalida uma chave específica do cache.
     *
     * @param string $key Chave a invalidar
     * @return void
     */
    public static function forget(string $key): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        unset($_SESSION[self::SESSION_KEY][$key]);
    }

    /**
     * Invalida todas as chaves que começam com um prefixo.
     * Útil para invalidar grupo de caches relacionados.
     *
     * Exemplo: SimpleCache::forgetByPrefix('company_') limpa company_settings, company_logo, etc.
     *
     * @param string $prefix Prefixo das chaves a invalidar
     * @return int Número de chaves removidas
     */
    public static function forgetByPrefix(string $prefix): int
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return 0;
        }

        $cache = $_SESSION[self::SESSION_KEY] ?? [];
        $removed = 0;

        foreach (array_keys($cache) as $key) {
            if (strpos($key, $prefix) === 0) {
                unset($_SESSION[self::SESSION_KEY][$key]);
                $removed++;
            }
        }

        return $removed;
    }

    /**
     * Limpa todo o cache da sessão.
     *
     * @return void
     */
    public static function flush(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        unset($_SESSION[self::SESSION_KEY]);
    }

    /**
     * Verifica se uma chave existe e não está expirada.
     *
     * @param string $key Chave a verificar
     * @return bool
     */
    public static function has(string $key): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }

        $cache = $_SESSION[self::SESSION_KEY] ?? [];

        return isset($cache[$key])
            && isset($cache[$key]['expires_at'])
            && $cache[$key]['expires_at'] > time();
    }

    /**
     * Retorna estatísticas do cache (para debug).
     *
     * @return array{total_keys: int, total_size_bytes: int, keys: array}
     */
    public static function stats(): array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return ['total_keys' => 0, 'total_size_bytes' => 0, 'keys' => []];
        }

        $cache = $_SESSION[self::SESSION_KEY] ?? [];
        $keys = [];

        foreach ($cache as $key => $entry) {
            $expired = isset($entry['expires_at']) && $entry['expires_at'] <= time();
            $keys[] = [
                'key'        => $key,
                'expired'    => $expired,
                'ttl_remain' => $expired ? 0 : ($entry['expires_at'] - time()),
                'created_at' => $entry['created_at'] ?? null,
            ];
        }

        return [
            'total_keys'       => count($cache),
            'total_size_bytes' => strlen(serialize($cache)),
            'keys'             => $keys,
        ];
    }
}
