<?php

namespace Akti\Core;

class Cache
{
    private static string $dir = '';

    private static function dir(): string
    {
        if (!self::$dir) {
            self::$dir = dirname(__DIR__, 2) . '/storage/cache';
            if (!is_dir(self::$dir)) {
                mkdir(self::$dir, 0775, true);
            }
        }
        return self::$dir;
    }

    /**
     * Retrieve a cached value, or compute and store it if missing/expired.
     *
     * @param string   $key    Cache key
     * @param int      $ttl    Time-to-live in seconds
     * @param callable $callback  Function that returns the value to cache
     * @return mixed
     */
    public static function remember(string $key, int $ttl, callable $callback): mixed
    {
        $value = self::get($key);
        if ($value !== null) {
            return $value;
        }
        $value = $callback();
        self::set($key, $value, $ttl);
        return $value;
    }

    public static function get(string $key): mixed
    {
        $file = self::filePath($key);
        if (!file_exists($file)) {
            return null;
        }
        $data = @unserialize(file_get_contents($file));
        if (!is_array($data) || !isset($data['expires'], $data['value'])) {
            @unlink($file);
            return null;
        }
        if ($data['expires'] < time()) {
            @unlink($file);
            return null;
        }
        return $data['value'];
    }

    public static function set(string $key, mixed $value, int $ttl = 60): void
    {
        $file = self::filePath($key);
        $data = serialize(['expires' => time() + $ttl, 'value' => $value]);
        file_put_contents($file, $data, LOCK_EX);
    }

    public static function forget(string $key): void
    {
        $file = self::filePath($key);
        if (file_exists($file)) {
            @unlink($file);
        }
    }

    /**
     * Invalidate all cache entries matching a prefix.
     */
    public static function forgetByPrefix(string $prefix): void
    {
        $pattern = self::dir() . '/' . md5($prefix) . '*';
        // Since we hash keys, use a tag-based approach
        $dir = self::dir();
        $tagFile = $dir . '/tag_' . md5($prefix) . '.json';
        if (file_exists($tagFile)) {
            $keys = json_decode(file_get_contents($tagFile), true) ?: [];
            foreach ($keys as $k) {
                @unlink(self::filePath($k));
            }
            @unlink($tagFile);
        }
    }

    public static function flush(): void
    {
        $files = glob(self::dir() . '/*.cache');
        foreach ($files ?: [] as $file) {
            @unlink($file);
        }
        $tags = glob(self::dir() . '/tag_*.json');
        foreach ($tags ?: [] as $file) {
            @unlink($file);
        }
    }

    private static function filePath(string $key): string
    {
        return self::dir() . '/' . md5($key) . '.cache';
    }
}
