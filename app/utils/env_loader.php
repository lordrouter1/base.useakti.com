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
        //var_dump("{$name}=".getenv($name));
        $_ENV[$name]    = $value;
        $_SERVER[$name] = $value;
    }
}
