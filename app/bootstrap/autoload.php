<?php
/**
 * Autoloader PSR-4 — Akti
 *
 * Carrega classes automaticamente baseado no namespace.
 * Elimina a necessidade de require/include manuais para classes.
 *
 * Mapeamento:
 *   Akti\Core\            → app/core/
 *   Akti\Controllers\     → app/controllers/
 *   Akti\Models\           → app/models/
 *   Akti\Config\         → app/config/
 *   Akti\Services\       → app/services/
 *   Akti\Middleware\     → app/middleware/
 *   Akti\Repositories\  → app/repositories/
 *   Akti\Utils\          → app/utils/
 *   Akti\Security\       → app/security/
 *   Akti\Gateways\       → app/gateways/
 *
 * Compatível com PHP 7.4+
 *
 * @see PROJECT_RULES.md — Módulo: Autoload PSR-4
 */

// Raiz do projeto (um nível acima de /app/bootstrap/)
define('AKTI_BASE_PATH', realpath(__DIR__ . '/../../') . DIRECTORY_SEPARATOR);

// ── Composer autoload (carrega dependências como TCPDF, PhpSpreadsheet etc.) ──
$composerAutoload = AKTI_BASE_PATH . 'vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}

spl_autoload_register(function (string $class): void {

    // Namespace base do projeto
    $prefix = 'Akti\\';

    // Se a classe não pertence ao namespace Akti\, ignorar
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    // Mapeamento de namespace → diretório relativo à raiz do projeto
    $namespaceMap = [
        'Akti\\Core\\'          => 'app/core/',
        'Akti\\Controllers\\'   => 'app/controllers/',
        'Akti\\Models\\'        => 'app/models/',
        'Akti\\Config\\'        => 'app/config/',
        'Akti\\Services\\'      => 'app/services/',
        'Akti\\Middleware\\'    => 'app/middleware/',
        'Akti\\Repositories\\' => 'app/repositories/',
        'Akti\\Utils\\'         => 'app/utils/',
        'Akti\\Security\\'      => 'app/security/',
        'Akti\\Gateways\\'      => 'app/gateways/',
    ];

    foreach ($namespaceMap as $nsPrefix => $baseDir) {
        // Verificar se a classe pertence a este namespace
        if (strncmp($class, $nsPrefix, strlen($nsPrefix)) === 0) {
            // Extrair o nome relativo da classe (sem o prefixo do namespace)
            $relativeClass = substr($class, strlen($nsPrefix));

            // Converter separadores de namespace em separadores de diretório
            $file = AKTI_BASE_PATH . $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

            if (file_exists($file)) {
                require_once $file;
                return;
            }

            // Arquivo não encontrado para este namespace — ignorar silenciosamente
            // para permitir que outros autoloaders tentem (ex: Composer no futuro)
            return;
        }
    }

    // Classe com prefixo Akti\ mas sem sub-namespace mapeado — ignorar silenciosamente
    // para permitir que outros autoloaders tentem (ex: Composer no futuro)
});

// ── Carregar .env — ANTES de qualquer config que dependa de getenv() ──
require_once AKTI_BASE_PATH . 'app/utils/env_loader.php';
akti_load_env(AKTI_BASE_PATH . '.env');

// ── Carregar configurações que definem classes globais (sem namespace) ──
// session.php define SessionGuard (procedural + classe, deve rodar ANTES de session_start)
require_once AKTI_BASE_PATH . 'app/config/session.php';

// tenant.php define TenantManager (classe global, necessária para resolver conexão)
require_once AKTI_BASE_PATH . 'app/config/tenant.php';

// database.php define Database (classe global, usada por todos os models/controllers)
require_once AKTI_BASE_PATH . 'app/config/database.php';

// ── Carregar helpers globais (funções utilitárias sem namespace) ──
// form_helper.php define csrf_field(), csrf_meta(), csrf_token()
require_once AKTI_BASE_PATH . 'app/utils/form_helper.php';

// escape_helper.php define e(), eAttr(), eJs(), eNum(), eUrl() para escape de saída em views
require_once AKTI_BASE_PATH . 'app/utils/escape_helper.php';

// ── Carregar bootstrap de eventos (registro de listeners) ──
// events.php registra listeners globais e futuramente inclui listeners de módulos
require_once AKTI_BASE_PATH . 'app/bootstrap/events.php';

// asset_helper.php define asset() para cache busting de CSS/JS/imagens
require_once AKTI_BASE_PATH . 'app/utils/asset_helper.php';

// ── Carregar helpers do Portal do Cliente ──
// portal_helper.php define __p(), portal_money(), portal_date() etc.
require_once AKTI_BASE_PATH . 'app/utils/portal_helper.php';
