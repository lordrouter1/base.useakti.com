<?php
namespace Akti\Core;

/**
 * Bootloader central de módulos por tenant.
 *
 * Fonte de dados:
 * - $_SESSION['tenant']['enabled_modules'] (vindo do TenantManager / akti_master)
 *
 * Regras:
 * - Se não houver configuração explícita, os módulos default permanecem habilitados.
 * - O controle é feito por slug de módulo (ex.: financial, boleto, nfe, fiscal).
 */
class ModuleBootloader
{
    /**
     * Módulos padrão habilitados quando o tenant não possui configuração explícita.
     */
    private const DEFAULT_ENABLED = [
        'financial' => true,
        'boleto'    => true,
        'nfe'       => true,
        'fiscal'    => true,
    ];

    /**
     * Mapeamento page => módulo responsável.
     */
    private const PAGE_MODULE_MAP = [
        'financial'              => 'financial',
        'financial_payments'     => 'financial',
        'financial_transactions' => 'financial',
    ];

    /**
     * Mapeamento de tabs da página de configurações.
     */
    private const SETTINGS_TAB_MODULE_MAP = [
        'boleto' => 'boleto',
        'fiscal' => 'fiscal',
    ];

    public static function isModuleEnabled(string $moduleSlug): bool
    {
        $modules = self::getEnabledModules();
        if (!array_key_exists($moduleSlug, $modules)) {
            return true;
        }

        return (bool) $modules[$moduleSlug];
    }

    public static function canAccessPage(string $page): bool
    {
        if (!isset(self::PAGE_MODULE_MAP[$page])) {
            return true;
        }

        $module = self::PAGE_MODULE_MAP[$page];
        return self::isModuleEnabled($module);
    }

    public static function canAccessSettingsTab(string $tab): bool
    {
        if (!isset(self::SETTINGS_TAB_MODULE_MAP[$tab])) {
            return true;
        }

        $module = self::SETTINGS_TAB_MODULE_MAP[$tab];
        return self::isModuleEnabled($module);
    }

    public static function sanitizeSettingsTab(?string $tab, string $fallback = 'company'): string
    {
        $tab = $tab ?: $fallback;
        if (!self::canAccessSettingsTab($tab)) {
            return $fallback;
        }

        return $tab;
    }

    /**
     * Retorna mapa de módulos habilitados com merge de defaults.
     *
     * Entrada aceita:
     * - array associativo ['financial' => true, ...]
     * - string JSON equivalente
     */
    public static function getEnabledModules(): array
    {
        $raw = $_SESSION['tenant']['enabled_modules'] ?? null;
        $parsed = [];

        if (is_array($raw)) {
            $parsed = $raw;
        } elseif (is_string($raw) && trim($raw) !== '') {
            $json = json_decode($raw, true);
            if (is_array($json)) {
                $parsed = $json;
            }
        }

        $normalized = [];
        foreach ($parsed as $key => $value) {
            if (!is_string($key)) {
                continue;
            }
            $normalized[strtolower(trim($key))] = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($normalized[strtolower(trim($key))] === null) {
                $normalized[strtolower(trim($key))] = (bool) $value;
            }
        }

        return array_merge(self::DEFAULT_ENABLED, $normalized);
    }
}

