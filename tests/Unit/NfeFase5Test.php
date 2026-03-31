<?php
namespace Akti\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Testes unitários da Fase 5 — Funcionalidades Novas NF-e.
 *
 * Cobre:
 * - FASE5-01: NFC-e (Modelo 65) — NfceXmlBuilder, NfceDanfeGenerator
 * - FASE5-02: Contingência Automática — NfeContingencyService
 * - FASE5-03: Download XML em Lote (ZIP)
 * - FASE5-04: Exportação SPED Fiscal — NfeSpedFiscalService
 * - FASE5-05: Exportação SINTEGRA — NfeSintegraService
 * - FASE5-06: Livro de Registro de Saídas — NfeReportModel::getLivroSaidas()
 * - FASE5-07: Livro de Registro de Entradas — NfeReportModel::getLivroEntradas()
 * - FASE5-08: Backup de XMLs — NfeBackupService
 *
 * Executar: vendor/bin/phpunit tests/Unit/NfeFase5Test.php
 *
 * @package Akti\Tests\Unit
 */
class NfeFase5Test extends TestCase
{
    // ══════════════════════════════════════════════════════════════
    // FASE5-01: NFC-e (Modelo 65)
    // ══════════════════════════════════════════════════════════════

    public function testNfceXmlBuilderClassExists(): void
    {
        $this->assertTrue(
            class_exists(\Akti\Services\NfceXmlBuilder::class),
            'NfceXmlBuilder deve existir'
        );
    }

    public function testNfceXmlBuilderHasBuildMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Services\NfceXmlBuilder::class, 'build'),
            'NfceXmlBuilder deve ter método build()'
        );
    }

    public function testNfceDanfeGeneratorClassExists(): void
    {
        $this->assertTrue(
            class_exists(\Akti\Services\NfceDanfeGenerator::class),
            'NfceDanfeGenerator deve existir'
        );
    }

    public function testNfceDanfeGeneratorHasGenerateMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Services\NfceDanfeGenerator::class, 'generate'),
            'NfceDanfeGenerator deve ter método generate()'
        );
    }

    public function testControllerHasEmitNfceMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Controllers\NfeDocumentController::class, 'emitNfce'),
            'NfeDocumentController deve ter método emitNfce()'
        );
    }

    public function testControllerHasDownloadDanfeNfceMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Controllers\NfeDocumentController::class, 'downloadDanfeNfce'),
            'NfeDocumentController deve ter método downloadDanfeNfce()'
        );
    }

    public function testEmitNfceRouteRegistered(): void
    {
        $routes = require __DIR__ . '/../../app/config/routes.php';
        $this->assertArrayHasKey('emitNfce', $routes['nfe_documents']['actions'],
            'Rota emitNfce deve estar registrada');
    }

    public function testDownloadDanfeNfceRouteRegistered(): void
    {
        $routes = require __DIR__ . '/../../app/config/routes.php';
        $this->assertArrayHasKey('downloadDanfeNfce', $routes['nfe_documents']['actions'],
            'Rota downloadDanfeNfce deve estar registrada');
    }

    public function testNfceXmlBuilderHasCorrectNamespace(): void
    {
        $content = file_get_contents(__DIR__ . '/../../app/services/NfceXmlBuilder.php');
        $this->assertStringContainsString(
            'namespace Akti\\Services;',
            $content,
            'NfceXmlBuilder deve ter namespace Akti\\Services'
        );
    }

    public function testControllerImportsNfceServices(): void
    {
        $content = file_get_contents(__DIR__ . '/../../app/controllers/NfeDocumentController.php');
        $this->assertStringContainsString(
            'use Akti\\Services\\NfceXmlBuilder;',
            $content,
            'Controller deve importar NfceXmlBuilder'
        );
        $this->assertStringContainsString(
            'use Akti\\Services\\NfceDanfeGenerator;',
            $content,
            'Controller deve importar NfceDanfeGenerator'
        );
    }

    // ══════════════════════════════════════════════════════════════
    // FASE5-02: Contingência Automática
    // ══════════════════════════════════════════════════════════════

    public function testNfeContingencyServiceClassExists(): void
    {
        $this->assertTrue(
            class_exists(\Akti\Services\NfeContingencyService::class),
            'NfeContingencyService deve existir'
        );
    }

    public function testNfeContingencyServiceHasGetStatusMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Services\NfeContingencyService::class, 'getStatus'),
            'NfeContingencyService deve ter método getStatus()'
        );
    }

    public function testNfeContingencyServiceHasActivateMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Services\NfeContingencyService::class, 'activate'),
            'NfeContingencyService deve ter método activate()'
        );
    }

    public function testNfeContingencyServiceHasDeactivateMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Services\NfeContingencyService::class, 'deactivate'),
            'NfeContingencyService deve ter método deactivate()'
        );
    }

    public function testNfeContingencyServiceHasSyncPendingMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Services\NfeContingencyService::class, 'syncPending'),
            'NfeContingencyService deve ter método syncPending()'
        );
    }

    public function testNfeContingencyServiceHasGetHistoryMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Services\NfeContingencyService::class, 'getHistory'),
            'NfeContingencyService deve ter método getHistory()'
        );
    }

    public function testContingencyRoutesRegistered(): void
    {
        $routes = require __DIR__ . '/../../app/config/routes.php';
        $actions = $routes['nfe_documents']['actions'];

        $required = ['contingencyStatus', 'contingencyActivate', 'contingencyDeactivate', 'contingencySync', 'contingencyHistory'];
        foreach ($required as $action) {
            $this->assertArrayHasKey($action, $actions, "Rota '{$action}' deve estar registrada");
        }
    }

    public function testControllerHasContingencyMethods(): void
    {
        $methods = ['contingencyStatus', 'contingencyActivate', 'contingencyDeactivate', 'contingencySync', 'contingencyHistory'];
        foreach ($methods as $method) {
            $this->assertTrue(
                method_exists(\Akti\Controllers\NfeDocumentController::class, $method),
                "NfeDocumentController deve ter método {$method}()"
            );
        }
    }

    public function testControllerImportsContingencyService(): void
    {
        $content = file_get_contents(__DIR__ . '/../../app/controllers/NfeDocumentController.php');
        $this->assertStringContainsString(
            'use Akti\\Services\\NfeContingencyService;',
            $content,
            'Controller deve importar NfeContingencyService'
        );
    }

    // ══════════════════════════════════════════════════════════════
    // FASE5-03: Download XML em Lote (ZIP)
    // ══════════════════════════════════════════════════════════════

    public function testControllerHasDownloadBatchMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Controllers\NfeDocumentController::class, 'downloadBatch'),
            'NfeDocumentController deve ter método downloadBatch()'
        );
    }

    public function testDownloadBatchRouteRegistered(): void
    {
        $routes = require __DIR__ . '/../../app/config/routes.php';
        $this->assertArrayHasKey('downloadBatch', $routes['nfe_documents']['actions'],
            'Rota downloadBatch deve estar registrada');
    }

    public function testDownloadBatchUsesZipArchive(): void
    {
        // Após refatoração, ZipArchive está no NfeBatchDownloadService
        $content = file_get_contents(__DIR__ . '/../../app/services/NfeBatchDownloadService.php');
        $this->assertStringContainsString('ZipArchive', $content,
            'NfeBatchDownloadService deve usar ZipArchive para gerar ZIP');
    }

    public function testDownloadBatchUsesAuditService(): void
    {
        $content = file_get_contents(__DIR__ . '/../../app/controllers/NfeDocumentController.php');
        $this->assertStringContainsString('download_batch', $content,
            'downloadBatch() deve registrar auditoria');
    }

    // ══════════════════════════════════════════════════════════════
    // FASE5-04: Exportação SPED Fiscal
    // ══════════════════════════════════════════════════════════════

    public function testNfeSpedFiscalServiceClassExists(): void
    {
        $this->assertTrue(
            class_exists(\Akti\Services\NfeSpedFiscalService::class),
            'NfeSpedFiscalService deve existir'
        );
    }

    public function testNfeSpedFiscalServiceHasGenerateMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Services\NfeSpedFiscalService::class, 'generate'),
            'NfeSpedFiscalService deve ter método generate()'
        );
    }

    public function testSpedFiscalServiceHasCorrectNamespace(): void
    {
        $content = file_get_contents(__DIR__ . '/../../app/services/NfeSpedFiscalService.php');
        $this->assertStringContainsString(
            'namespace Akti\\Services;',
            $content,
            'NfeSpedFiscalService deve ter namespace Akti\\Services'
        );
    }

    public function testSpedFiscalServiceUsesReportModel(): void
    {
        $content = file_get_contents(__DIR__ . '/../../app/services/NfeSpedFiscalService.php');
        $this->assertStringContainsString('NfeReportModel', $content,
            'NfeSpedFiscalService deve usar NfeReportModel');
        $this->assertStringContainsString('NfeCredential', $content,
            'NfeSpedFiscalService deve usar NfeCredential');
    }

    public function testSpedFiscalServiceGeneratesBlocks(): void
    {
        $content = file_get_contents(__DIR__ . '/../../app/services/NfeSpedFiscalService.php');
        $this->assertStringContainsString('Bloco 0', $content, 'SPED deve gerar Bloco 0 (Abertura)');
        $this->assertStringContainsString('Bloco C', $content, 'SPED deve gerar Bloco C (Documentos Fiscais)');
        $this->assertStringContainsString('Bloco 9', $content, 'SPED deve gerar Bloco 9 (Encerramento)');
    }

    public function testExportSpedRouteRegistered(): void
    {
        $routes = require __DIR__ . '/../../app/config/routes.php';
        $this->assertArrayHasKey('exportSped', $routes['nfe_documents']['actions'],
            'Rota exportSped deve estar registrada');
    }

    public function testControllerHasExportSpedMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Controllers\NfeDocumentController::class, 'exportSped'),
            'NfeDocumentController deve ter método exportSped()'
        );
    }

    public function testControllerImportsSpedService(): void
    {
        $content = file_get_contents(__DIR__ . '/../../app/controllers/NfeDocumentController.php');
        $this->assertStringContainsString(
            'use Akti\\Services\\NfeSpedFiscalService;',
            $content,
            'Controller deve importar NfeSpedFiscalService'
        );
    }

    // ══════════════════════════════════════════════════════════════
    // FASE5-05: Exportação SINTEGRA
    // ══════════════════════════════════════════════════════════════

    public function testNfeSintegraServiceClassExists(): void
    {
        $this->assertTrue(
            class_exists(\Akti\Services\NfeSintegraService::class),
            'NfeSintegraService deve existir'
        );
    }

    public function testNfeSintegraServiceHasGenerateMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Services\NfeSintegraService::class, 'generate'),
            'NfeSintegraService deve ter método generate()'
        );
    }

    public function testSintegraServiceHasCorrectNamespace(): void
    {
        $content = file_get_contents(__DIR__ . '/../../app/services/NfeSintegraService.php');
        $this->assertStringContainsString(
            'namespace Akti\\Services;',
            $content,
            'NfeSintegraService deve ter namespace Akti\\Services'
        );
    }

    public function testSintegraServiceGeneratesRecordTypes(): void
    {
        $content = file_get_contents(__DIR__ . '/../../app/services/NfeSintegraService.php');
        // Verifica se gera os tipos de registro obrigatórios
        $this->assertStringContainsString('10', $content, 'SINTEGRA deve gerar registro tipo 10');
        $this->assertStringContainsString('11', $content, 'SINTEGRA deve gerar registro tipo 11');
        $this->assertStringContainsString('50', $content, 'SINTEGRA deve gerar registro tipo 50');
        $this->assertStringContainsString('90', $content, 'SINTEGRA deve gerar registro tipo 90');
        $this->assertStringContainsString('99', $content, 'SINTEGRA deve gerar registro tipo 99');
    }

    public function testExportSintegraRouteRegistered(): void
    {
        $routes = require __DIR__ . '/../../app/config/routes.php';
        $this->assertArrayHasKey('exportSintegra', $routes['nfe_documents']['actions'],
            'Rota exportSintegra deve estar registrada');
    }

    public function testControllerHasExportSintegraMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Controllers\NfeDocumentController::class, 'exportSintegra'),
            'NfeDocumentController deve ter método exportSintegra()'
        );
    }

    public function testControllerImportsSintegraService(): void
    {
        $content = file_get_contents(__DIR__ . '/../../app/controllers/NfeDocumentController.php');
        $this->assertStringContainsString(
            'use Akti\\Services\\NfeSintegraService;',
            $content,
            'Controller deve importar NfeSintegraService'
        );
    }

    // ══════════════════════════════════════════════════════════════
    // FASE5-06: Livro de Registro de Saídas
    // ══════════════════════════════════════════════════════════════

    public function testNfeReportModelHasGetLivroSaidasMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Models\NfeReportModel::class, 'getLivroSaidas'),
            'NfeReportModel deve ter método getLivroSaidas()'
        );
    }

    public function testGetLivroSaidasAcceptsPeriodParams(): void
    {
        $reflection = new \ReflectionMethod(\Akti\Models\NfeReportModel::class, 'getLivroSaidas');
        $params = $reflection->getParameters();

        $this->assertGreaterThanOrEqual(2, count($params), 'getLivroSaidas() deve aceitar pelo menos 2 parâmetros');
        $this->assertEquals('start', $params[0]->getName());
        $this->assertEquals('end', $params[1]->getName());
    }

    public function testLivroSaidasViewExists(): void
    {
        $this->assertFileExists(
            __DIR__ . '/../../app/views/nfe/livro_saidas.php',
            'View livro_saidas.php deve existir'
        );
    }

    public function testLivroSaidasViewContainsExpectedContent(): void
    {
        $content = file_get_contents(__DIR__ . '/../../app/views/nfe/livro_saidas.php');
        $this->assertStringContainsString('Livro de Registro de Saídas', $content);
        $this->assertStringContainsString('items', $content);
        $this->assertStringContainsString('totalsByCfop', $content);
        $this->assertStringContainsString('CFOP', $content);
        $this->assertStringContainsString('ICMS', $content);
        $this->assertStringContainsString('exportSped', $content, 'View deve ter link para exportar SPED');
        $this->assertStringContainsString('exportSintegra', $content, 'View deve ter link para exportar SINTEGRA');
    }

    public function testLivroSaidasRouteRegistered(): void
    {
        $routes = require __DIR__ . '/../../app/config/routes.php';
        $this->assertArrayHasKey('livroSaidas', $routes['nfe_documents']['actions'],
            'Rota livroSaidas deve estar registrada');
    }

    public function testControllerHasLivroSaidasMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Controllers\NfeDocumentController::class, 'livroSaidas'),
            'NfeDocumentController deve ter método livroSaidas()'
        );
    }

    // ══════════════════════════════════════════════════════════════
    // FASE5-07: Livro de Registro de Entradas
    // ══════════════════════════════════════════════════════════════

    public function testNfeReportModelHasGetLivroEntradasMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Models\NfeReportModel::class, 'getLivroEntradas'),
            'NfeReportModel deve ter método getLivroEntradas()'
        );
    }

    public function testGetLivroEntradasAcceptsPeriodParams(): void
    {
        $reflection = new \ReflectionMethod(\Akti\Models\NfeReportModel::class, 'getLivroEntradas');
        $params = $reflection->getParameters();

        $this->assertGreaterThanOrEqual(2, count($params), 'getLivroEntradas() deve aceitar pelo menos 2 parâmetros');
        $this->assertEquals('start', $params[0]->getName());
        $this->assertEquals('end', $params[1]->getName());
    }

    public function testLivroEntradasViewExists(): void
    {
        $this->assertFileExists(
            __DIR__ . '/../../app/views/nfe/livro_entradas.php',
            'View livro_entradas.php deve existir'
        );
    }

    public function testLivroEntradasViewContainsExpectedContent(): void
    {
        $content = file_get_contents(__DIR__ . '/../../app/views/nfe/livro_entradas.php');
        $this->assertStringContainsString('Livro de Registro de Entradas', $content);
        $this->assertStringContainsString('items', $content);
        $this->assertStringContainsString('totalGeral', $content);
        $this->assertStringContainsString('ICMS', $content);
        $this->assertStringContainsString('Emitente', $content);
    }

    public function testLivroEntradasRouteRegistered(): void
    {
        $routes = require __DIR__ . '/../../app/config/routes.php';
        $this->assertArrayHasKey('livroEntradas', $routes['nfe_documents']['actions'],
            'Rota livroEntradas deve estar registrada');
    }

    public function testControllerHasLivroEntradasMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Controllers\NfeDocumentController::class, 'livroEntradas'),
            'NfeDocumentController deve ter método livroEntradas()'
        );
    }

    // ══════════════════════════════════════════════════════════════
    // FASE5-08: Backup de XMLs
    // ══════════════════════════════════════════════════════════════

    public function testNfeBackupServiceClassExists(): void
    {
        $this->assertTrue(
            class_exists(\Akti\Services\NfeBackupService::class),
            'NfeBackupService deve existir'
        );
    }

    public function testNfeBackupServiceHasExecuteMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Services\NfeBackupService::class, 'execute'),
            'NfeBackupService deve ter método execute()'
        );
    }

    public function testNfeBackupServiceHasGetHistoryMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Services\NfeBackupService::class, 'getHistory'),
            'NfeBackupService deve ter método getHistory()'
        );
    }

    public function testBackupServiceHasCorrectNamespace(): void
    {
        $content = file_get_contents(__DIR__ . '/../../app/services/NfeBackupService.php');
        $this->assertStringContainsString(
            'namespace Akti\\Services;',
            $content,
            'NfeBackupService deve ter namespace Akti\\Services'
        );
    }

    public function testBackupServiceSupportsMultipleTypes(): void
    {
        $content = file_get_contents(__DIR__ . '/../../app/services/NfeBackupService.php');
        $this->assertStringContainsString('local', $content, 'Backup deve suportar tipo local');
        $this->assertStringContainsString('s3', $content, 'Backup deve suportar tipo S3');
        $this->assertStringContainsString('ftp', $content, 'Backup deve suportar tipo FTP');
    }

    public function testBackupSettingsViewExists(): void
    {
        $this->assertFileExists(
            __DIR__ . '/../../app/views/nfe/backup_settings.php',
            'View backup_settings.php deve existir'
        );
    }

    public function testBackupSettingsViewContainsExpectedContent(): void
    {
        $content = file_get_contents(__DIR__ . '/../../app/views/nfe/backup_settings.php');
        $this->assertStringContainsString('Backup', $content);
        $this->assertStringContainsString('backupHistory', $content);
        $this->assertStringContainsString('backupConfig', $content);
        $this->assertStringContainsString('saveBackupSettings', $content, 'View deve submeter para saveBackupSettings');
        $this->assertStringContainsString('backup_auto_enabled', $content, 'View deve ter toggle de backup automático');
        $this->assertStringContainsString('backup_s3_bucket', $content, 'View deve ter campo S3 bucket');
        $this->assertStringContainsString('backup_ftp_host', $content, 'View deve ter campo FTP host');
    }

    public function testBackupRoutesRegistered(): void
    {
        $routes = require __DIR__ . '/../../app/config/routes.php';
        $actions = $routes['nfe_documents']['actions'];

        $required = ['backupXml', 'backupHistory', 'backupSettings', 'saveBackupSettings'];
        foreach ($required as $action) {
            $this->assertArrayHasKey($action, $actions, "Rota '{$action}' deve estar registrada");
        }
    }

    public function testControllerHasBackupMethods(): void
    {
        $methods = ['backupXml', 'backupHistory', 'backupSettings', 'saveBackupSettings'];
        foreach ($methods as $method) {
            $this->assertTrue(
                method_exists(\Akti\Controllers\NfeDocumentController::class, $method),
                "NfeDocumentController deve ter método {$method}()"
            );
        }
    }

    public function testControllerImportsBackupService(): void
    {
        $content = file_get_contents(__DIR__ . '/../../app/controllers/NfeDocumentController.php');
        $this->assertStringContainsString(
            'use Akti\\Services\\NfeBackupService;',
            $content,
            'Controller deve importar NfeBackupService'
        );
    }

    // SQL migration tests removed per project convention:
    // PHPUnit tests must NOT test for .sql file existence.

    // ══════════════════════════════════════════════════════════════
    // Integração — Todas as rotas Fase 5 registradas
    // ══════════════════════════════════════════════════════════════

    public function testAllFase5RoutesRegistered(): void
    {
        $routes = require __DIR__ . '/../../app/config/routes.php';
        $nfeActions = $routes['nfe_documents']['actions'];

        $requiredActions = [
            'emitNfce', 'downloadDanfeNfce',
            'contingencyStatus', 'contingencyActivate', 'contingencyDeactivate', 'contingencySync', 'contingencyHistory',
            'downloadBatch',
            'exportSped', 'exportSintegra',
            'livroSaidas', 'livroEntradas',
            'backupXml', 'backupHistory', 'backupSettings', 'saveBackupSettings',
        ];

        foreach ($requiredActions as $action) {
            $this->assertArrayHasKey($action, $nfeActions,
                "Rota '{$action}' deve estar registrada em nfe_documents");
        }
    }

    // ══════════════════════════════════════════════════════════════
    // Sidebar — Seções Fase 5 presentes no index.php
    // ══════════════════════════════════════════════════════════════

    public function testIndexViewHasFase5Sections(): void
    {
        $content = file_get_contents(__DIR__ . '/../../app/views/nfe/index.php');

        $this->assertStringContainsString('sec-contingencia', $content, 'Index deve ter seção de contingência');
        $this->assertStringContainsString('sec-livros', $content, 'Index deve ter seção de livros');
        $this->assertStringContainsString('sec-exportacoes', $content, 'Index deve ter seção de exportações');
        $this->assertStringContainsString('sec-backup', $content, 'Index deve ter seção de backup');
    }

    public function testIndexViewHasFase5SidebarItems(): void
    {
        $content = file_get_contents(__DIR__ . '/../../app/views/nfe/index.php');

        $this->assertStringContainsString('data-sec="contingencia"', $content, 'Sidebar deve ter item contingência');
        $this->assertStringContainsString('data-sec="livros"', $content, 'Sidebar deve ter item livros');
        $this->assertStringContainsString('data-sec="exportacoes"', $content, 'Sidebar deve ter item exportações');
        $this->assertStringContainsString('data-sec="backup"', $content, 'Sidebar deve ter item backup');
    }

    public function testIndexViewHasSpedSintegraExportButtons(): void
    {
        $content = file_get_contents(__DIR__ . '/../../app/views/nfe/index.php');
        $this->assertStringContainsString('btnExportSped', $content, 'Index deve ter botão SPED');
        $this->assertStringContainsString('btnExportSintegra', $content, 'Index deve ter botão SINTEGRA');
        $this->assertStringContainsString('btnDownloadBatch', $content, 'Index deve ter botão download em lote');
    }

    public function testIndexViewHasContingencyJsHandlers(): void
    {
        $content = file_get_contents(__DIR__ . '/../../app/views/nfe/index.php');
        $this->assertStringContainsString('contingencyActivate', $content, 'JS deve ter handler para ativar contingência');
        $this->assertStringContainsString('contingencyDeactivate', $content, 'JS deve ter handler para desativar contingência');
        $this->assertStringContainsString('contingencySync', $content, 'JS deve ter handler para sincronizar contingência');
        $this->assertStringContainsString('loadContingencyStatus', $content, 'JS deve ter função loadContingencyStatus');
        $this->assertStringContainsString('loadContingencyHistory', $content, 'JS deve ter função loadContingencyHistory');
    }

    // ══════════════════════════════════════════════════════════════
    // Consistência — Imports e Namespaces
    // ══════════════════════════════════════════════════════════════

    public function testNfeContingencyServiceHasCorrectNamespace(): void
    {
        $content = file_get_contents(__DIR__ . '/../../app/services/NfeContingencyService.php');
        $this->assertStringContainsString(
            'namespace Akti\\Services;',
            $content,
            'NfeContingencyService deve ter namespace Akti\\Services'
        );
    }

    public function testControllerImportsAllFase5Services(): void
    {
        $content = file_get_contents(__DIR__ . '/../../app/controllers/NfeDocumentController.php');

        $imports = [
            'NfceXmlBuilder',
            'NfceDanfeGenerator',
            'NfeContingencyService',
            'NfeSpedFiscalService',
            'NfeSintegraService',
            'NfeBackupService',
        ];

        foreach ($imports as $import) {
            $this->assertStringContainsString(
                "use Akti\\Services\\{$import};",
                $content,
                "Controller deve importar {$import}"
            );
        }
    }

    public function testNfeReportModelHasGetCfopDescriptionsMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Models\NfeReportModel::class, 'getCfopDescriptions'),
            'NfeReportModel deve ter método getCfopDescriptions()'
        );
    }
}
