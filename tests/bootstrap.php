<?php
/**
 * Bootstrap dos testes — carrega autoloader do Composer e helpers globais.
 *
 * Este arquivo é referenciado pelo phpunit.xml como bootstrap.
 */

require_once __DIR__ . '/../vendor/autoload.php';

// ── Definir AKTI_BASE_PATH se não definido (necessário para os testes) ──
if (!defined('AKTI_BASE_PATH')) {
    define('AKTI_BASE_PATH', realpath(__DIR__ . '/../') . DIRECTORY_SEPARATOR);
}

// ── Carregar PSR-4 autoloader do projeto (para classes Akti\*) ──
spl_autoload_register(function (string $class): void {
    $prefix = 'Akti\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $namespaceMap = [
        'Akti\\Core\\'         => 'app/core/',
        'Akti\\Controllers\\'  => 'app/controllers/',
        'Akti\\Models\\'       => 'app/models/',
        'Akti\\Config\\'       => 'app/config/',
        'Akti\\Services\\'     => 'app/services/',
        'Akti\\Middleware\\'   => 'app/middleware/',
        'Akti\\Repositories\\' => 'app/repositories/',
        'Akti\\Utils\\'        => 'app/utils/',
        'Akti\\Security\\'     => 'app/security/',
        'Akti\\Gateways\\'     => 'app/gateways/',
    ];
    foreach ($namespaceMap as $nsPrefix => $baseDir) {
        if (strncmp($class, $nsPrefix, strlen($nsPrefix)) === 0) {
            $relativeClass = substr($class, strlen($nsPrefix));
            $file = AKTI_BASE_PATH . $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) {
                require_once $file;
            }
            return;
        }
    }
});

// ── Carregar helpers globais (funções sem namespace) ──
// Só carregar se ainda não foram carregados (evitar "function already defined")
if (!function_exists('csrf_field')) {
    $helpers = [
        'app/utils/env_loader.php',
        'app/utils/form_helper.php',
        'app/utils/escape_helper.php',
        'app/utils/asset_helper.php',
        'app/utils/portal_helper.php',
    ];
    foreach ($helpers as $helper) {
        $helperPath = AKTI_BASE_PATH . $helper;
        if (file_exists($helperPath)) {
            require_once $helperPath;
        }
    }
}
