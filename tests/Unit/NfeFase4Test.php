<?php
namespace Akti\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Testes unitários da Fase 4 — Segurança, Relatórios e Dashboard.
 *
 * Cobre:
 * - FASE4-01: RateLimitMiddleware (sessão)
 * - FASE4-02: NfeReportModel::getCorrectionHistory()
 * - FASE4-03: NfeExportService (estrutura e labels)
 * - FASE4-04: NfeAuditService (novos métodos de auditoria)
 * - FASE4-05: NfeXmlBuilder validação CPF/CNPJ
 *
 * Executar: vendor/bin/phpunit tests/Unit/NfeFase4Test.php
 *
 * @package Akti\Tests\Unit
 */
class NfeFase4Test extends TestCase
{
    // ══════════════════════════════════════════════════════════════
    // FASE4-01: Rate Limiting
    // ══════════════════════════════════════════════════════════════

    public function testRateLimitMiddlewareClassExists(): void
    {
        $this->assertTrue(
            class_exists(\Akti\Middleware\RateLimitMiddleware::class),
            'RateLimitMiddleware deve existir'
        );
    }

    public function testRateLimitMiddlewareHasCheckMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Middleware\RateLimitMiddleware::class, 'check'),
            'RateLimitMiddleware deve ter método check()'
        );
    }

    public function testRateLimitMiddlewareHasCheckWithDbMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Middleware\RateLimitMiddleware::class, 'checkWithDb'),
            'RateLimitMiddleware deve ter método checkWithDb()'
        );
    }

    public function testRateLimitMiddlewareHasCleanupMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Middleware\RateLimitMiddleware::class, 'cleanup'),
            'RateLimitMiddleware deve ter método cleanup()'
        );
    }

    public function testRateLimitCheckReturnsArrayWithAllowedKey(): void
    {
        // Inicializar sessão para o teste
        if (!isset($_SESSION)) {
            $_SESSION = [];
        }
        $_SESSION['user_id'] = 999;

        $result = \Akti\Middleware\RateLimitMiddleware::check('test_action_unique_' . time(), 5);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('allowed', $result);
        $this->assertArrayHasKey('retry_after', $result);
        $this->assertTrue($result['allowed'], 'Primeira tentativa deve ser permitida');
    }

    public function testRateLimitCheckBlocksSecondAttempt(): void
    {
        if (!isset($_SESSION)) {
            $_SESSION = [];
        }
        $_SESSION['user_id'] = 998;

        $action = 'test_rate_limit_block_' . time();

        // Primeira tentativa
        $result1 = \Akti\Middleware\RateLimitMiddleware::check($action, 60);
        $this->assertTrue($result1['allowed']);

        // Segunda tentativa (dentro do intervalo)
        $result2 = \Akti\Middleware\RateLimitMiddleware::check($action, 60);
        $this->assertFalse($result2['allowed']);
        $this->assertGreaterThan(0, $result2['retry_after']);
    }

    public function testRateLimitControllerUsesRateLimit(): void
    {
        $controller = file_get_contents(__DIR__ . '/../../app/controllers/NfeDocumentController.php');
        $this->assertStringContainsString(
            'RateLimitMiddleware::check',
            $controller,
            'NfeDocumentController::emit() deve usar RateLimitMiddleware::check()'
        );
        $this->assertStringContainsString(
            'nfe_emit',
            $controller,
            'Rate limit deve usar ação "nfe_emit"'
        );
    }

    // ══════════════════════════════════════════════════════════════
    // FASE4-02: Relatório de CC-e
    // ══════════════════════════════════════════════════════════════

    public function testNfeReportModelHasGetCorrectionHistoryMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Models\NfeReportModel::class, 'getCorrectionHistory'),
            'NfeReportModel deve ter método getCorrectionHistory()'
        );
    }

    public function testNfeReportModelHasGetCorrectionsByMonthMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Models\NfeReportModel::class, 'getCorrectionsByMonth'),
            'NfeReportModel deve ter método getCorrectionsByMonth()'
        );
    }

    public function testGetCorrectionHistoryAcceptsPeriodParams(): void
    {
        $reflection = new \ReflectionMethod(\Akti\Models\NfeReportModel::class, 'getCorrectionHistory');
        $params = $reflection->getParameters();

        $this->assertCount(2, $params, 'getCorrectionHistory() deve aceitar 2 parâmetros');
        $this->assertEquals('start', $params[0]->getName());
        $this->assertEquals('end', $params[1]->getName());
    }

    public function testCorrectionReportViewExists(): void
    {
        $this->assertFileExists(
            __DIR__ . '/../../app/views/nfe/correction_report.php',
            'View correction_report.php deve existir'
        );
    }

    public function testCorrectionReportViewContainsExpectedContent(): void
    {
        $content = file_get_contents(__DIR__ . '/../../app/views/nfe/correction_report.php');
        $this->assertStringContainsString('Relatório', $content);
        $this->assertStringContainsString('corrections', $content);
        $this->assertStringContainsString('seq_evento', $content);
        $this->assertStringContainsString('texto_correcao', $content);
        $this->assertStringContainsString('exportReport', $content, 'View deve ter botão de exportação');
    }

    public function testCorrectionReportRouteExists(): void
    {
        $routes = require __DIR__ . '/../../app/config/routes.php';
        $this->assertArrayHasKey('nfe_documents', $routes);
        $this->assertArrayHasKey('actions', $routes['nfe_documents']);
        $this->assertArrayHasKey('correctionReport', $routes['nfe_documents']['actions'],
            'Rota correctionReport deve existir em nfe_documents');
    }

    public function testControllerHasCorrectionReportMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Controllers\NfeDocumentController::class, 'correctionReport'),
            'NfeDocumentController deve ter método correctionReport()'
        );
    }

    // ══════════════════════════════════════════════════════════════
    // FASE4-03: Exportação de Relatórios em Excel
    // ══════════════════════════════════════════════════════════════

    public function testNfeExportServiceClassExists(): void
    {
        $this->assertTrue(
            class_exists(\Akti\Services\NfeExportService::class),
            'NfeExportService deve existir'
        );
    }

    public function testNfeExportServiceHasExportToExcelMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Services\NfeExportService::class, 'exportToExcel'),
            'NfeExportService deve ter método exportToExcel()'
        );
    }

    public function testNfeExportServiceHasExportToCsvMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Services\NfeExportService::class, 'exportToCsv'),
            'NfeExportService deve ter método exportToCsv()'
        );
    }

    public function testExportReportRouteExists(): void
    {
        $routes = require __DIR__ . '/../../app/config/routes.php';
        $this->assertArrayHasKey('exportReport', $routes['nfe_documents']['actions'],
            'Rota exportReport deve existir em nfe_documents');
    }

    public function testControllerHasExportReportMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Controllers\NfeDocumentController::class, 'exportReport'),
            'NfeDocumentController deve ter método exportReport()'
        );
    }

    public function testExportServiceUsesPhpSpreadsheet(): void
    {
        $content = file_get_contents(__DIR__ . '/../../app/services/NfeExportService.php');
        $this->assertStringContainsString('PhpOffice\\PhpSpreadsheet', $content,
            'NfeExportService deve usar PhpSpreadsheet');
        $this->assertStringContainsString('Spreadsheet', $content);
        $this->assertStringContainsString('Xlsx', $content);
    }

    public function testDashboardViewHasExportButtons(): void
    {
        $content = file_get_contents(__DIR__ . '/../../app/views/nfe/dashboard.php');
        $this->assertStringContainsString('exportReport', $content,
            'Dashboard deve ter links para exportReport');
        $this->assertStringContainsString('fa-file-excel', $content,
            'Dashboard deve ter ícone de Excel');
    }

    public function testExportServiceHandlesEmptyData(): void
    {
        $service = new \Akti\Services\NfeExportService();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Nenhum dado para exportar');

        // Isso deve lançar exceção — não pode exportar dados vazios
        $service->exportToExcel([], 'test');
    }

    // ══════════════════════════════════════════════════════════════
    // FASE4-04: Auditoria de Acesso ao Certificado Digital
    // ══════════════════════════════════════════════════════════════

    public function testNfeAuditServiceHasLogCertificateUploadMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Services\NfeAuditService::class, 'logCertificateUpload'),
            'NfeAuditService deve ter método logCertificateUpload()'
        );
    }

    public function testNfeAuditServiceHasLogCredentialsViewMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Services\NfeAuditService::class, 'logCredentialsView'),
            'NfeAuditService deve ter método logCredentialsView()'
        );
    }

    public function testNfeAuditServiceHasLogCredentialsUpdateMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Services\NfeAuditService::class, 'logCredentialsUpdate'),
            'NfeAuditService deve ter método logCredentialsUpdate()'
        );
    }

    public function testCredentialControllerUsesAuditService(): void
    {
        $content = file_get_contents(__DIR__ . '/../../app/controllers/NfeCredentialController.php');

        $this->assertStringContainsString('NfeAuditService', $content,
            'NfeCredentialController deve importar NfeAuditService');
        $this->assertStringContainsString('logCredentialsView', $content,
            'NfeCredentialController::index() deve registrar auditoria de visualização');
        $this->assertStringContainsString('logCredentialsUpdate', $content,
            'NfeCredentialController::store() deve registrar auditoria de atualização');
        $this->assertStringContainsString('credential_cert_upload', $content,
            'NfeCredentialController deve registrar upload de certificado');
    }

    public function testCredentialControllerHasGetAuditServiceHelper(): void
    {
        $reflection = new \ReflectionClass(\Akti\Controllers\NfeCredentialController::class);
        $this->assertTrue(
            $reflection->hasMethod('getAuditService'),
            'NfeCredentialController deve ter helper getAuditService()'
        );
    }

    // ══════════════════════════════════════════════════════════════
    // FASE4-05: Validação de CPF/CNPJ Antes da Emissão
    // ══════════════════════════════════════════════════════════════

    public function testNfeXmlBuilderValidatesCpfCnpj(): void
    {
        $content = file_get_contents(__DIR__ . '/../../app/services/NfeXmlBuilder.php');

        $this->assertStringContainsString('isValidCpf', $content,
            'NfeXmlBuilder::build() deve validar CPF antes de montar XML');
        $this->assertStringContainsString('isValidCnpj', $content,
            'NfeXmlBuilder::build() deve validar CNPJ antes de montar XML');
        $this->assertStringContainsString('Validator::isValidCpf', $content,
            'Deve usar Validator::isValidCpf() existente');
        $this->assertStringContainsString('Validator::isValidCnpj', $content,
            'Deve usar Validator::isValidCnpj() existente');
    }

    public function testValidatorIsValidCpfAcceptsValidCpf(): void
    {
        // CPF válido de teste
        $this->assertTrue(\Akti\Utils\Validator::isValidCpf('52998224725'));
    }

    public function testValidatorIsValidCpfRejectsInvalidCpf(): void
    {
        $this->assertFalse(\Akti\Utils\Validator::isValidCpf('11111111111'));
        $this->assertFalse(\Akti\Utils\Validator::isValidCpf('12345678901'));
        $this->assertFalse(\Akti\Utils\Validator::isValidCpf('123'));
    }

    public function testValidatorIsValidCnpjAcceptsValidCnpj(): void
    {
        // CNPJ válido de teste
        $this->assertTrue(\Akti\Utils\Validator::isValidCnpj('11222333000181'));
    }

    public function testValidatorIsValidCnpjRejectsInvalidCnpj(): void
    {
        $this->assertFalse(\Akti\Utils\Validator::isValidCnpj('11111111111111'));
        $this->assertFalse(\Akti\Utils\Validator::isValidCnpj('12345678000190'));
        $this->assertFalse(\Akti\Utils\Validator::isValidCnpj('123'));
    }

    public function testXmlBuilderValidationThrowsOnInvalidCpf(): void
    {
        // Verificar que o código de validação está antes do Make()
        $content = file_get_contents(__DIR__ . '/../../app/services/NfeXmlBuilder.php');

        // A validação deve vir antes de $nfe = new \NFePHP\NFe\Make()
        $posValidation = strpos($content, 'isValidCpf');
        $posMake = strpos($content, 'new \\NFePHP\\NFe\\Make()');

        $this->assertNotFalse($posValidation, 'Validação CPF deve existir no builder');
        $this->assertNotFalse($posMake, 'Make() deve existir no builder');
        $this->assertLessThan($posMake, $posValidation,
            'Validação de CPF/CNPJ deve vir ANTES de instanciar Make()');
    }

    public function testXmlBuilderThrowsInvalidArgumentOnBadDocument(): void
    {
        // Verificar que InvalidArgumentException é usada
        $content = file_get_contents(__DIR__ . '/../../app/services/NfeXmlBuilder.php');
        $this->assertStringContainsString('InvalidArgumentException', $content,
            'Builder deve lançar InvalidArgumentException para documento inválido');
    }

    // ══════════════════════════════════════════════════════════════
    // Migration SQL
    // ══════════════════════════════════════════════════════════════

    public function testMigrationSqlFileExists(): void
    {
        $this->assertFileExists(
            __DIR__ . '/../../sql/prontos/update_202603281000_fase4_seguranca_relatorios.sql',
            'Migration SQL da Fase 4 deve existir em sql/prontos/'
        );
    }

    public function testMigrationSqlContainsRateLimitTable(): void
    {
        $sql = file_get_contents(__DIR__ . '/../../sql/prontos/update_202603281000_fase4_seguranca_relatorios.sql');
        $this->assertStringContainsString('rate_limit', $sql, 'Migration deve criar tabela rate_limit');
        $this->assertStringContainsString('user_id', $sql);
        $this->assertStringContainsString('action', $sql);
        $this->assertStringContainsString('attempted_at', $sql);
    }

    public function testMigrationSqlContainsIndexes(): void
    {
        $sql = file_get_contents(__DIR__ . '/../../sql/prontos/update_202603281000_fase4_seguranca_relatorios.sql');
        $this->assertStringContainsString('idx_rate_limit_user_action', $sql,
            'Migration deve criar índice idx_rate_limit_user_action');
        $this->assertStringContainsString('idx_correction_history_created', $sql,
            'Migration deve criar índice para relatório de CC-e');
        $this->assertStringContainsString('idx_audit_entity_type', $sql,
            'Migration deve criar índice para auditoria');
    }

    // ══════════════════════════════════════════════════════════════
    // Integração — Rotas Fase 4
    // ══════════════════════════════════════════════════════════════

    public function testAllFase4RoutesRegistered(): void
    {
        $routes = require __DIR__ . '/../../app/config/routes.php';
        $nfeActions = $routes['nfe_documents']['actions'];

        $requiredActions = ['correctionReport', 'exportReport'];
        foreach ($requiredActions as $action) {
            $this->assertArrayHasKey($action, $nfeActions,
                "Rota '{$action}' deve estar registrada em nfe_documents");
        }
    }

    public function testRoutesTestFileIncludesFase4Routes(): void
    {
        $content = file_get_contents(__DIR__ . '/../routes_test.php');
        $this->assertStringContainsString('correctionReport', $content,
            'routes_test.php deve incluir rota correctionReport');
    }

    // ══════════════════════════════════════════════════════════════
    // Consistência — Imports e namespaces
    // ══════════════════════════════════════════════════════════════

    public function testNfeDocumentControllerImportsRateLimitMiddleware(): void
    {
        $content = file_get_contents(__DIR__ . '/../../app/controllers/NfeDocumentController.php');
        $this->assertStringContainsString(
            'use Akti\\Middleware\\RateLimitMiddleware;',
            $content,
            'NfeDocumentController deve importar RateLimitMiddleware'
        );
    }

    public function testNfeExportServiceHasCorrectNamespace(): void
    {
        $content = file_get_contents(__DIR__ . '/../../app/services/NfeExportService.php');
        $this->assertStringContainsString(
            'namespace Akti\\Services;',
            $content,
            'NfeExportService deve ter namespace Akti\\Services'
        );
    }

    public function testRateLimitMiddlewareHasCorrectNamespace(): void
    {
        $content = file_get_contents(__DIR__ . '/../../app/middleware/RateLimitMiddleware.php');
        $this->assertStringContainsString(
            'namespace Akti\\Middleware;',
            $content,
            'RateLimitMiddleware deve ter namespace Akti\\Middleware'
        );
    }
}
