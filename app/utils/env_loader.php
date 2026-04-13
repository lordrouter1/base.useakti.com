<?php
/**
 * Lightweight .env file loader for Akti.
 *
 * Reads a .env file and sets variables via putenv() + $_ENV + $_SERVER
 * so that getenv() works everywhere (including TenantManager).
 *
 * Supports:
 *   - Comments (#)
 *   - Empty lines
 *   - Quoted values (single and double)
 *   - Inline comments after unquoted values
 *   - export VAR=value syntax
 *
 * Does NOT override variables that are already set in the environment
 * (e.g. real server-level env vars take precedence).
 *
 * Compatible with PHP 7.4+
 *
 * @param string $path Absolute path to the .env file
 * @return void
 */
/**
 * Static registry for env vars loaded from .env file.
 * Uses a class with a static property — immune to global scope issues
 * in Apache worker/thread configurations.
 */
class AktiEnvRegistry
{
    /** @var array<string, string> */
    private static array $vars = [];
    private static bool $loaded = false;

    public static function set(string $name, string $value): void
    {
        self::$vars[$name] = $value;
        self::$loaded = true;
    }

    /**
     * @return string|false
     */
    public static function get(string $name)
    {
        return self::$vars[$name] ?? false;
    }

    public static function isLoaded(): bool
    {
        return self::$loaded;
    }
}

function akti_load_env(string $path): void
{
    if (!is_file($path) || !is_readable($path)) {
        return; // Silently skip — in production, env vars come from the server
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);

        // Skip comments and empty lines
        if ($line === '' || $line[0] === '#') {
            continue;
        }

        // Strip optional "export " prefix
        if (strpos($line, 'export ') === 0) {
            $line = substr($line, 7);
        }

        // Must contain "="
        $eqPos = strpos($line, '=');
        if ($eqPos === false) {
            continue;
        }

        $name  = trim(substr($line, 0, $eqPos));
        $value = trim(substr($line, $eqPos + 1));

        if ($name === '') {
            continue;
        }

        // Parse the value — handle quoted strings
        if (strlen($value) >= 2) {
            $first = $value[0];
            $last  = $value[strlen($value) - 1];

            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                // Remove quotes
                $value = substr($value, 1, -1);

                // For double-quoted values, process escape sequences
                if ($first === '"') {
                    $value = str_replace(
                        ['\\n', '\\r', '\\t', '\\"', '\\\\'],
                        ["\n",  "\r",  "\t",  '"',   '\\'],
                        $value
                    );
                }
            } else {
                // Unquoted — strip inline comments
                $hashPos = strpos($value, ' #');
                if ($hashPos !== false) {
                    $value = rtrim(substr($value, 0, $hashPos));
                }
            }
        }

        // Do NOT override existing environment variables
        if (getenv($name) !== false && getenv($name) !== '') {
            continue;
        }

        
        putenv("{$name}={$value}");
        $_ENV[$name]    = $value;
        $_SERVER[$name] = $value;
        AktiEnvRegistry::set($name, $value);
    }
}

/**
 * Retrieve an environment variable loaded by akti_load_env().
 * Falls back through: getenv() → $_ENV → $_SERVER → AktiEnvRegistry.
 * If all fail and the registry was never loaded, attempts to re-load .env.
 *
 * @param string $name Variable name
 * @return string|false The value, or false if not found
 */
function akti_env(string $name)
{
    $val = getenv($name);
    if ($val !== false && $val !== '') {
        return $val;
    }
    if (isset($_ENV[$name]) && $_ENV[$name] !== '') {
        return $_ENV[$name];
    }
    if (isset($_SERVER[$name]) && $_SERVER[$name] !== '') {
        return $_SERVER[$name];
    }

    $reg = AktiEnvRegistry::get($name);
    if ($reg !== false) {
        return $reg;
    }

    // Last resort: re-load .env if registry is empty (guards against boot order issues)
    if (!AktiEnvRegistry::isLoaded() && defined('AKTI_BASE_PATH')) {
        akti_load_env(AKTI_BASE_PATH . '.env');
        $reg = AktiEnvRegistry::get($name);
        if ($reg !== false) {
            return $reg;
        }
    }

    return false;
}
