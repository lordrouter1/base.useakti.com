<?php
namespace Akti\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Akti\Core\ModuleBootloader;

/**
 * Testes unitários para Akti\Core\ModuleBootloader.
 *
 * Cobre:
 * - isModuleEnabled() com módulos explícitos e defaults
 * - canAccessPage() com mapeamento page => módulo
 * - canAccessSettingsTab() com mapeamento tab => módulo
 * - sanitizeSettingsTab() com fallback para módulos desabilitados
 * - getModuleLabel() retorna labels amigáveis
 * - getEnabledModules() com diferentes inputs (array, JSON, vazio)
 * - getDisabledModuleJS() retorna JavaScript válido
 * - injectJS() retorna HTML script válido
 *
 * @package Akti\Tests\Unit\Core
 */
class ModuleBootloaderTest extends TestCase
{
    private array $backupSession;

    protected function setUp(): void
    {
        parent::setUp();
        $this->backupSession = $_SESSION ?? [];
    }

    protected function tearDown(): void
    {
        $_SESSION = $this->backupSession;
        parent::tearDown();
    }

    // ══════════════════════════════════════════════════════════════
    // isModuleEnabled()
    // ══════════════════════════════════════════════════════════════

    public function testModuleEnabledByDefault(): void
    {
        $_SESSION = ['tenant' => ['enabled_modules' => []]];
        // financial is true by default
        $this->assertTrue(ModuleBootloader::isModuleEnabled('financial'));
    }

    public function testModuleDisabledByDefault(): void
    {
        $_SESSION = ['tenant' => ['enabled_modules' => []]];
        // boleto is false by default
        $this->assertFalse(ModuleBootloader::isModuleEnabled('boleto'));
    }

    public function testModuleEnabledOverridesDefault(): void
    {
        $_SESSION = ['tenant' => ['enabled_modules' => ['boleto' => true]]];
        $this->assertTrue(ModuleBootloader::isModuleEnabled('boleto'));
    }

    public function testModuleDisabledOverridesDefault(): void
    {
        $_SESSION = ['tenant' => ['enabled_modules' => ['financial' => false]]];
        $this->assertFalse(ModuleBootloader::isModuleEnabled('financial'));
    }

    public function testUnknownModuleIsEnabled(): void
    {
        $_SESSION = ['tenant' => ['enabled_modules' => []]];
        // Unknown modules default to enabled (permissive)
        $this->assertTrue(ModuleBootloader::isModuleEnabled('unknown_module'));
    }

    public function testModuleEnabledWithJsonString(): void
    {
        $_SESSION = ['tenant' => ['enabled_modules' => json_encode(['boleto' => true])]];
        $this->assertTrue(ModuleBootloader::isModuleEnabled('boleto'));
    }

    public function testNoTenantSessionUsesDefaults(): void
    {
        $_SESSION = [];
        $this->assertTrue(ModuleBootloader::isModuleEnabled('financial'));
        $this->assertFalse(ModuleBootloader::isModuleEnabled('boleto'));
    }

    // ══════════════════════════════════════════════════════════════
    // canAccessPage()
    // ══════════════════════════════════════════════════════════════

    public function testCanAccessMappedPageWhenEnabled(): void
    {
        $_SESSION = ['tenant' => ['enabled_modules' => ['financial' => true]]];
        $this->assertTrue(ModuleBootloader::canAccessPage('financial'));
    }

    public function testCannotAccessMappedPageWhenDisabled(): void
    {
        $_SESSION = ['tenant' => ['enabled_modules' => ['financial' => false]]];
        $this->assertFalse(ModuleBootloader::canAccessPage('financial'));
    }

    public function testCanAccessUnmappedPage(): void
    {
        $_SESSION = ['tenant' => ['enabled_modules' => []]];
        // Pages not in PAGE_MODULE_MAP always return true
        $this->assertTrue(ModuleBootloader::canAccessPage('customers'));
        $this->assertTrue(ModuleBootloader::canAccessPage('dashboard'));
        $this->assertTrue(ModuleBootloader::canAccessPage('products'));
    }

    public function testNfePageLinkedToNfeModule(): void
    {
        $_SESSION = ['tenant' => ['enabled_modules' => ['nfe' => false]]];
        $this->assertFalse(ModuleBootloader::canAccessPage('nfe_documents'));
        $this->assertFalse(ModuleBootloader::canAccessPage('nfe_credentials'));
    }

    // ══════════════════════════════════════════════════════════════
    // canAccessSettingsTab()
    // ══════════════════════════════════════════════════════════════

    public function testCanAccessUnmappedSettingsTab(): void
    {
        $_SESSION = ['tenant' => ['enabled_modules' => []]];
        $this->assertTrue(ModuleBootloader::canAccessSettingsTab('company'));
    }

    public function testCannotAccessBoletoTabWhenDisabled(): void
    {
        $_SESSION = ['tenant' => ['enabled_modules' => ['boleto' => false]]];
        $this->assertFalse(ModuleBootloader::canAccessSettingsTab('boleto'));
    }

    public function testCanAccessBoletoTabWhenEnabled(): void
    {
        $_SESSION = ['tenant' => ['enabled_modules' => ['boleto' => true]]];
        $this->assertTrue(ModuleBootloader::canAccessSettingsTab('boleto'));
    }

    // ══════════════════════════════════════════════════════════════
    // sanitizeSettingsTab()
    // ══════════════════════════════════════════════════════════════

    public function testSanitizeSettingsTabReturnsTabWhenEnabled(): void
    {
        $_SESSION = ['tenant' => ['enabled_modules' => ['boleto' => true]]];
        $this->assertSame('boleto', ModuleBootloader::sanitizeSettingsTab('boleto'));
    }

    public function testSanitizeSettingsTabReturnsFallbackWhenDisabled(): void
    {
        $_SESSION = ['tenant' => ['enabled_modules' => ['boleto' => false]]];
        $this->assertSame('company', ModuleBootloader::sanitizeSettingsTab('boleto'));
    }

    public function testSanitizeSettingsTabUsesCustomFallback(): void
    {
        $_SESSION = ['tenant' => ['enabled_modules' => ['boleto' => false]]];
        $this->assertSame('general', ModuleBootloader::sanitizeSettingsTab('boleto', 'general'));
    }

    public function testSanitizeSettingsTabNullUsesDefault(): void
    {
        $_SESSION = ['tenant' => ['enabled_modules' => []]];
        $this->assertSame('company', ModuleBootloader::sanitizeSettingsTab(null));
    }

    // ══════════════════════════════════════════════════════════════
    // getModuleLabel()
    // ══════════════════════════════════════════════════════════════

    public function testGetModuleLabelKnown(): void
    {
        $this->assertSame('Financeiro', ModuleBootloader::getModuleLabel('financial'));
        $this->assertSame('Boleto Bancário', ModuleBootloader::getModuleLabel('boleto'));
    }

    public function testGetModuleLabelUnknown(): void
    {
        $label = ModuleBootloader::getModuleLabel('custom_module');
        $this->assertSame('Custom_module', $label);
    }

    // ══════════════════════════════════════════════════════════════
    // getDisabledModuleJS()
    // ══════════════════════════════════════════════════════════════

    public function testGetDisabledModuleJSContainsPreventDefault(): void
    {
        $js = ModuleBootloader::getDisabledModuleJS('boleto');
        $this->assertStringContainsString('event.preventDefault()', $js);
        $this->assertStringContainsString('event.stopPropagation()', $js);
    }

    public function testGetDisabledModuleJSContainsSwalFire(): void
    {
        $js = ModuleBootloader::getDisabledModuleJS('nfe');
        $this->assertStringContainsString('Swal.fire', $js);
        $this->assertStringContainsString('Nota Fiscal', $js);
    }

    // ══════════════════════════════════════════════════════════════
    // getEnabledModules()
    // ══════════════════════════════════════════════════════════════

    public function testGetEnabledModulesWithArray(): void
    {
        $_SESSION = ['tenant' => ['enabled_modules' => ['boleto' => true, 'nfe' => false]]];
        $result = ModuleBootloader::getEnabledModules();

        $this->assertTrue($result['boleto']);
        $this->assertFalse($result['nfe']);
        $this->assertTrue($result['financial']); // default
    }

    public function testGetEnabledModulesWithJson(): void
    {
        $_SESSION = ['tenant' => ['enabled_modules' => '{"boleto":true}']];
        $result = ModuleBootloader::getEnabledModules();

        $this->assertTrue($result['boleto']);
    }

    public function testGetEnabledModulesEmptyUsesDefaults(): void
    {
        $_SESSION = [];
        $result = ModuleBootloader::getEnabledModules();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('financial', $result);
        $this->assertArrayHasKey('boleto', $result);
    }

    public function testGetEnabledModulesNormalizesKeys(): void
    {
        $_SESSION = ['tenant' => ['enabled_modules' => ['BOLETO' => true, ' NFE ' => false]]];
        $result = ModuleBootloader::getEnabledModules();

        $this->assertTrue($result['boleto']);
        $this->assertFalse($result['nfe']);
    }

    // ══════════════════════════════════════════════════════════════
    // injectJS()
    // ══════════════════════════════════════════════════════════════

    public function testInjectJSReturnsScriptTag(): void
    {
        $_SESSION = ['tenant' => ['enabled_modules' => []]];
        $html = ModuleBootloader::injectJS();

        $this->assertStringContainsString('<script>', $html);
        $this->assertStringContainsString('</script>', $html);
        $this->assertStringContainsString('AktiModules', $html);
        $this->assertStringContainsString('AktiEvents', $html);
    }

    public function testInjectJSContainsModulesLoadedEvent(): void
    {
        $_SESSION = ['tenant' => ['enabled_modules' => []]];
        $html = ModuleBootloader::injectJS();

        $this->assertStringContainsString("'modules:loaded'", $html);
    }
}
