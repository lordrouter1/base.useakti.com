<?php
namespace Akti\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Akti\Utils\Validator;

/**
 * Testes unitários da Fase 2 — Controller + Lógica de Negócio.
 *
 * Cobre:
 * - 2.1/2.2: Validação completa server-side de todos os campos
 * - 2.3: Soft delete (método no Model)
 * - 2.4: Novos métodos de validação (uniqueExcept, url, inList, etc.)
 * - 2.5: Logger interface
 * - 2.6: Action view() (controller method exists)
 * - 2.7-2.11: Novas actions (checkDuplicate, searchCep, searchCnpj, export, etc.)
 * - 2.12: Novas rotas registradas
 *
 * Executar: vendor/bin/phpunit tests/Unit/CustomerFase2Test.php
 */
class CustomerFase2Test extends TestCase
{
    // ══════════════════════════════════════════════════════════════
    // Validação completa (Fase 2.4)
    // ══════════════════════════════════════════════════════════════

    public function testValidatorRequiredFailsOnEmpty(): void
    {
        $v = new Validator();
        $v->required('name', '', 'Nome');
        $this->assertTrue($v->fails());
        $this->assertStringContainsString('obrigatório', $v->error('name'));
    }

    public function testValidatorInListValid(): void
    {
        $v = new Validator();
        $v->inList('person_type', 'PJ', ['PF', 'PJ'], 'Tipo');
        $this->assertTrue($v->passes());
    }

    public function testValidatorInListInvalid(): void
    {
        $v = new Validator();
        $v->inList('person_type', 'XX', ['PF', 'PJ'], 'Tipo');
        $this->assertTrue($v->fails());
        $this->assertStringContainsString('inválido', $v->error('person_type'));
    }

    public function testValidatorEmailValid(): void
    {
        $v = new Validator();
        $v->email('email', 'test@example.com', 'E-mail');
        $this->assertTrue($v->passes());
    }

    public function testValidatorEmailInvalid(): void
    {
        $v = new Validator();
        $v->email('email', 'notanemail', 'E-mail');
        $this->assertTrue($v->fails());
    }

    public function testValidatorUrlValid(): void
    {
        $v = new Validator();
        $v->url('website', 'https://example.com', 'Website');
        $this->assertTrue($v->passes());
    }

    public function testValidatorUrlInvalid(): void
    {
        $v = new Validator();
        $v->url('website', 'not a url', 'Website');
        $this->assertTrue($v->fails());
    }

    public function testValidatorUrlSkipsEmpty(): void
    {
        $v = new Validator();
        $v->url('website', '', 'Website');
        $this->assertTrue($v->passes());
    }

    public function testValidatorDateNotFutureToday(): void
    {
        $v = new Validator();
        $v->dateNotFuture('birth_date', date('Y-m-d'), 'Data Nasc.');
        $this->assertTrue($v->passes(), 'Data de hoje não deve ser considerada futura');
    }

    public function testValidatorDateNotFuturePast(): void
    {
        $v = new Validator();
        $v->dateNotFuture('birth_date', '1990-01-01', 'Data Nasc.');
        $this->assertTrue($v->passes());
    }

    public function testValidatorDateNotFutureFuture(): void
    {
        $v = new Validator();
        $v->dateNotFuture('birth_date', '2099-12-31', 'Data Nasc.');
        $this->assertTrue($v->fails());
        $this->assertStringContainsString('futura', $v->error('birth_date'));
    }

    public function testValidatorDecimalValid(): void
    {
        $v = new Validator();
        $v->decimal('credit_limit', '1500.50', 'Limite');
        $this->assertTrue($v->passes());
    }

    public function testValidatorDecimalZeroValid(): void
    {
        $v = new Validator();
        $v->decimal('credit_limit', '0', 'Limite');
        $this->assertTrue($v->passes());
    }

    public function testValidatorDecimalNegativeInvalid(): void
    {
        $v = new Validator();
        $v->decimal('credit_limit', '-100', 'Limite');
        $this->assertTrue($v->fails());
    }

    public function testValidatorBetweenValid(): void
    {
        $v = new Validator();
        $v->between('discount', '15', 0, 100, 'Desconto');
        $this->assertTrue($v->passes());
    }

    public function testValidatorBetweenAboveMax(): void
    {
        $v = new Validator();
        $v->between('discount', '150', 0, 100, 'Desconto');
        $this->assertTrue($v->fails());
    }

    public function testValidatorBetweenBelowMin(): void
    {
        $v = new Validator();
        $v->between('discount', '-5', 0, 100, 'Desconto');
        $this->assertTrue($v->fails());
    }

    public function testValidatorDocumentPfValid(): void
    {
        $v = new Validator();
        $v->document('document', '52998224725', 'PF', 'CPF');
        $this->assertTrue($v->passes());
    }

    public function testValidatorDocumentPjValid(): void
    {
        $v = new Validator();
        $v->document('document', '11222333000181', 'PJ', 'CNPJ');
        $this->assertTrue($v->passes());
    }

    public function testValidatorDocumentPfInvalid(): void
    {
        $v = new Validator();
        $v->document('document', '12345678901', 'PF', 'CPF');
        $this->assertTrue($v->fails());
    }

    public function testValidatorDocumentPjInvalid(): void
    {
        $v = new Validator();
        $v->document('document', '11111111111111', 'PJ', 'CNPJ');
        $this->assertTrue($v->fails());
    }

    public function testValidatorDocumentSkipsEmpty(): void
    {
        $v = new Validator();
        $v->document('document', '', 'PF', 'CPF');
        $this->assertTrue($v->passes());
    }

    public function testValidatorMaxLengthValid(): void
    {
        $v = new Validator();
        $v->maxLength('name', 'Nome Curto', 191, 'Nome');
        $this->assertTrue($v->passes());
    }

    public function testValidatorMaxLengthInvalid(): void
    {
        $v = new Validator();
        $v->maxLength('name', str_repeat('A', 200), 191, 'Nome');
        $this->assertTrue($v->fails());
    }

    public function testValidatorMinLengthValid(): void
    {
        $v = new Validator();
        $v->minLength('name', 'ABC', 3, 'Nome');
        $this->assertTrue($v->passes());
    }

    public function testValidatorMinLengthInvalid(): void
    {
        $v = new Validator();
        $v->minLength('name', 'AB', 3, 'Nome');
        $this->assertTrue($v->fails());
    }

    public function testValidatorAddErrorManually(): void
    {
        $v = new Validator();
        $v->addError('custom', 'Mensagem customizada');
        $this->assertTrue($v->fails());
        $this->assertEquals('Mensagem customizada', $v->error('custom'));
    }

    public function testValidatorReset(): void
    {
        $v = new Validator();
        $v->required('name', '', 'Nome');
        $this->assertTrue($v->fails());
        $v->reset();
        $this->assertTrue($v->passes());
    }

    public function testValidatorChainingMultipleFields(): void
    {
        $v = new Validator();
        $v->required('person_type', 'PF', 'Tipo')
          ->inList('person_type', 'PF', ['PF', 'PJ'], 'Tipo')
          ->required('name', 'Empresa ABC', 'Nome')
          ->maxLength('name', 'Empresa ABC', 191, 'Nome')
          ->email('email', 'test@example.com', 'E-mail')
          ->decimal('credit_limit', '1000.00', 'Limite')
          ->between('discount', '10', 0, 100, 'Desconto');
        $this->assertTrue($v->passes());
        $this->assertEmpty($v->errors());
    }

    public function testValidatorChainingWithErrors(): void
    {
        $v = new Validator();
        $v->required('name', '', 'Nome')
          ->email('email', 'invalido', 'E-mail')
          ->decimal('credit', '-50', 'Crédito');
        $this->assertTrue($v->fails());
        $this->assertCount(3, $v->errors());
    }

    public function testValidatorDateValid(): void
    {
        $v = new Validator();
        $v->date('birth_date', '1990-06-15', 'Data');
        $this->assertTrue($v->passes());
    }

    public function testValidatorDateInvalid(): void
    {
        $v = new Validator();
        $v->date('birth_date', '31/12/1990', 'Data');
        $this->assertTrue($v->fails());
    }

    public function testValidatorUfValidation(): void
    {
        $ufs = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];
        $v = new Validator();
        $v->inList('state', 'SP', $ufs, 'UF');
        $this->assertTrue($v->passes());

        $v2 = new Validator();
        $v2->inList('state', 'XX', $ufs, 'UF');
        $this->assertTrue($v2->fails());
    }

    // ══════════════════════════════════════════════════════════════
    // Controller — Novas actions existem (Fase 2.6-2.11)
    // ══════════════════════════════════════════════════════════════

    public function testControllerHasViewMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Controllers\CustomerController::class, 'view'),
            'CustomerController deve ter método view()'
        );
    }

    public function testControllerHasCheckDuplicateMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Controllers\CustomerController::class, 'checkDuplicate'),
            'CustomerController deve ter método checkDuplicate()'
        );
    }

    public function testControllerHasSearchCepMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Controllers\CustomerController::class, 'searchCep'),
            'CustomerController deve ter método searchCep()'
        );
    }

    public function testControllerHasSearchCnpjMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Controllers\CustomerController::class, 'searchCnpj'),
            'CustomerController deve ter método searchCnpj()'
        );
    }

    public function testControllerHasExportMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Controllers\CustomerController::class, 'export'),
            'CustomerController deve ter método export()'
        );
    }

    public function testControllerHasBulkActionMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Controllers\CustomerController::class, 'bulkAction'),
            'CustomerController deve ter método bulkAction()'
        );
    }

    public function testControllerHasUpdateStatusMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Controllers\CustomerController::class, 'updateStatus'),
            'CustomerController deve ter método updateStatus()'
        );
    }

    public function testControllerHasRestoreMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Controllers\CustomerController::class, 'restore'),
            'CustomerController deve ter método restore()'
        );
    }

    public function testControllerHasGetContactsMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Controllers\CustomerController::class, 'getContacts'),
            'CustomerController deve ter método getContacts()'
        );
    }

    public function testControllerHasSaveContactMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Controllers\CustomerController::class, 'saveContact'),
            'CustomerController deve ter método saveContact()'
        );
    }

    public function testControllerHasDeleteContactMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Controllers\CustomerController::class, 'deleteContact'),
            'CustomerController deve ter método deleteContact()'
        );
    }

    // ══════════════════════════════════════════════════════════════
    // Controller — Métodos antigos preservados (retrocompatibilidade)
    // ══════════════════════════════════════════════════════════════

    public function testControllerHasIndexMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Controllers\CustomerController::class, 'index'),
            'CustomerController deve ter método index()'
        );
    }

    public function testControllerHasStoreMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Controllers\CustomerController::class, 'store'),
            'CustomerController deve ter método store()'
        );
    }

    public function testControllerHasEditMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Controllers\CustomerController::class, 'edit'),
            'CustomerController deve ter método edit()'
        );
    }

    public function testControllerHasUpdateMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Controllers\CustomerController::class, 'update'),
            'CustomerController deve ter método update()'
        );
    }

    public function testControllerHasDeleteMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Controllers\CustomerController::class, 'delete'),
            'CustomerController deve ter método delete()'
        );
    }

    public function testControllerHasGetCustomersListMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Controllers\CustomerController::class, 'getCustomersList'),
            'CustomerController deve ter método getCustomersList()'
        );
    }

    public function testControllerHasSearchSelect2Method(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Controllers\CustomerController::class, 'searchSelect2'),
            'CustomerController deve ter método searchSelect2()'
        );
    }

    public function testControllerHasParseImportFileMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Controllers\CustomerController::class, 'parseImportFile'),
            'CustomerController deve ter método parseImportFile()'
        );
    }

    public function testControllerHasImportCustomersMappedMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Controllers\CustomerController::class, 'importCustomersMapped'),
            'CustomerController deve ter método importCustomersMapped()'
        );
    }

    public function testControllerHasDownloadImportTemplateMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Controllers\CustomerController::class, 'downloadImportTemplate'),
            'CustomerController deve ter método downloadImportTemplate()'
        );
    }

    // ══════════════════════════════════════════════════════════════
    // Controller — Imports e Namespaces corretos
    // ══════════════════════════════════════════════════════════════

    public function testControllerHasCorrectNamespace(): void
    {
        $content = file_get_contents(__DIR__ . '/../../app/controllers/CustomerController.php');
        $this->assertStringContainsString(
            'namespace Akti\\Controllers;',
            $content,
            'Controller deve ter namespace Akti\\Controllers'
        );
    }

    public function testControllerImportsCustomerContact(): void
    {
        $content = file_get_contents(__DIR__ . '/../../app/controllers/CustomerController.php');
        $this->assertStringContainsString(
            'use Akti\\Models\\CustomerContact;',
            $content,
            'Controller deve importar CustomerContact'
        );
    }

    public function testControllerImportsValidator(): void
    {
        $content = file_get_contents(__DIR__ . '/../../app/controllers/CustomerController.php');
        $this->assertStringContainsString(
            'use Akti\\Utils\\Validator;',
            $content,
            'Controller deve importar Validator'
        );
    }

    public function testControllerImportsLogger(): void
    {
        $content = file_get_contents(__DIR__ . '/../../app/controllers/CustomerController.php');
        $this->assertStringContainsString(
            'use Akti\\Models\\Logger;',
            $content,
            'Controller deve importar Logger'
        );
    }

    // ══════════════════════════════════════════════════════════════
    // Controller — Logs de auditoria presentes no código
    // ══════════════════════════════════════════════════════════════

    public function testControllerHasAuditLogCreate(): void
    {
        $content = file_get_contents(__DIR__ . '/../../app/controllers/CustomerController.php');
        $this->assertStringContainsString('CUSTOMER_CREATE', $content, 'store() deve registrar log CUSTOMER_CREATE');
    }

    public function testControllerHasAuditLogUpdate(): void
    {
        $content = file_get_contents(__DIR__ . '/../../app/controllers/CustomerController.php');
        $this->assertStringContainsString('CUSTOMER_UPDATE', $content, 'update() deve registrar log CUSTOMER_UPDATE');
    }

    public function testControllerHasAuditLogDelete(): void
    {
        $content = file_get_contents(__DIR__ . '/../../app/controllers/CustomerController.php');
        $this->assertStringContainsString('CUSTOMER_DELETE', $content, 'delete() deve registrar log CUSTOMER_DELETE');
    }

    public function testControllerHasAuditLogRestore(): void
    {
        $content = file_get_contents(__DIR__ . '/../../app/controllers/CustomerController.php');
        $this->assertStringContainsString('CUSTOMER_RESTORE', $content, 'restore() deve registrar log CUSTOMER_RESTORE');
    }

    public function testControllerHasAuditLogStatus(): void
    {
        $content = file_get_contents(__DIR__ . '/../../app/controllers/CustomerController.php');
        $this->assertStringContainsString('CUSTOMER_STATUS', $content, 'updateStatus() deve registrar log CUSTOMER_STATUS');
    }

    public function testControllerHasAuditLogExport(): void
    {
        // Após refatoração, o log de exportação está no CustomerExportService
        $content = file_get_contents(__DIR__ . '/../../app/services/CustomerExportService.php');
        $this->assertStringContainsString('CUSTOMER_EXPORT', $content, 'CustomerExportService deve registrar log CUSTOMER_EXPORT');
    }

    public function testControllerHasAuditLogImport(): void
    {
        $content = file_get_contents(__DIR__ . '/../../app/controllers/CustomerController.php');
        $this->assertStringContainsString('CUSTOMER_IMPORT', $content, 'importCustomersMapped() deve registrar log CUSTOMER_IMPORT');
    }

    // ══════════════════════════════════════════════════════════════
    // Controller — Sanitizações específicas presentes
    // ══════════════════════════════════════════════════════════════

    public function testControllerSanitizesDocument(): void
    {
        $content = file_get_contents(__DIR__ . '/../../app/controllers/CustomerController.php');
        $this->assertStringContainsString("preg_replace('/\\D/', '', \$document", $content,
            'Controller deve sanitizar documento removendo não-numéricos');
    }

    public function testControllerSanitizesInstagram(): void
    {
        $content = file_get_contents(__DIR__ . '/../../app/controllers/CustomerController.php');
        $this->assertStringContainsString("strpos(\$instagram, '@')", $content,
            'Controller deve remover @ do Instagram');
    }

    public function testControllerSanitizesWebsite(): void
    {
        $content = file_get_contents(__DIR__ . '/../../app/controllers/CustomerController.php');
        $this->assertStringContainsString("'https://' . \$website", $content,
            'Controller deve adicionar https:// ao website se ausente');
    }

    public function testControllerSoftDeleteInDeleteMethod(): void
    {
        $content = file_get_contents(__DIR__ . '/../../app/controllers/CustomerController.php');
        // Verifica se o método delete() chama softDelete em vez de delete direto
        $this->assertStringContainsString('softDelete', $content,
            'delete() deve usar softDelete() ao invés de hard delete');
    }

    // ══════════════════════════════════════════════════════════════
    // Rotas — Todas as novas rotas registradas (Fase 2.12)
    // ══════════════════════════════════════════════════════════════

    public function testAllFase2RoutesRegistered(): void
    {
        $routes = require __DIR__ . '/../../app/config/routes.php';
        $customerActions = $routes['customers']['actions'];

        $requiredActions = [
            // CRUD original
            'store', 'create', 'edit', 'update', 'delete',
            // Novas Fase 2
            'view',
            'checkDuplicate', 'searchCep', 'searchCnpj',
            'export',
            'bulkAction', 'updateStatus', 'restore',
            'getContacts', 'saveContact', 'deleteContact',
            // Existentes
            'getCustomersList', 'searchSelect2',
            'parseImportFile', 'importCustomersMapped', 'downloadImportTemplate',
        ];

        foreach ($requiredActions as $action) {
            $this->assertArrayHasKey($action, $customerActions,
                "Rota '{$action}' deve estar registrada em customers");
        }
    }

    public function testCustomerRoutesCount(): void
    {
        $routes = require __DIR__ . '/../../app/config/routes.php';
        $customerActions = $routes['customers']['actions'];

        // Fase 2 adiciona 11 novas rotas (view, checkDuplicate, searchCep, searchCnpj, export,
        // bulkAction, updateStatus, restore, getContacts, saveContact, deleteContact)
        // + 10 existentes = 21 rotas
        $this->assertGreaterThanOrEqual(21, count($customerActions),
            'Deve ter pelo menos 21 rotas de clientes (CRUD + Fase 2)');
    }

    // ══════════════════════════════════════════════════════════════
    // Model — Métodos usados pelo Controller existem
    // ══════════════════════════════════════════════════════════════

    public function testModelHasSoftDeleteMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Models\Customer::class, 'softDelete'),
            'Customer model deve ter método softDelete()'
        );
    }

    public function testModelHasRestoreMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Models\Customer::class, 'restore'),
            'Customer model deve ter método restore()'
        );
    }

    public function testModelHasUpdateStatusMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Models\Customer::class, 'updateStatus'),
            'Customer model deve ter método updateStatus()'
        );
    }

    public function testModelHasCheckDuplicateMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Models\Customer::class, 'checkDuplicate'),
            'Customer model deve ter método checkDuplicate()'
        );
    }

    public function testModelHasBulkUpdateStatusMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Models\Customer::class, 'bulkUpdateStatus'),
            'Customer model deve ter método bulkUpdateStatus()'
        );
    }

    public function testModelHasBulkDeleteMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Models\Customer::class, 'bulkDelete'),
            'Customer model deve ter método bulkDelete()'
        );
    }

    public function testModelHasExportAllMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Models\Customer::class, 'exportAll'),
            'Customer model deve ter método exportAll()'
        );
    }

    public function testModelHasGetDistinctCitiesMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Models\Customer::class, 'getDistinctCities'),
            'Customer model deve ter método getDistinctCities()'
        );
    }

    public function testModelHasGetDistinctStatesMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Models\Customer::class, 'getDistinctStates'),
            'Customer model deve ter método getDistinctStates()'
        );
    }

    public function testModelHasGetAllTagsMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Models\Customer::class, 'getAllTags'),
            'Customer model deve ter método getAllTags()'
        );
    }

    public function testModelHasGetCustomerStatsMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Models\Customer::class, 'getCustomerStats'),
            'Customer model deve ter método getCustomerStats()'
        );
    }

    // ══════════════════════════════════════════════════════════════
    // ContactModel — Métodos usados pelo Controller existem
    // ══════════════════════════════════════════════════════════════

    public function testContactModelHasCreateMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Models\CustomerContact::class, 'create'),
            'CustomerContact model deve ter método create()'
        );
    }

    public function testContactModelHasReadByCustomerMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Models\CustomerContact::class, 'readByCustomer'),
            'CustomerContact model deve ter método readByCustomer()'
        );
    }

    public function testContactModelHasUpdateMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Models\CustomerContact::class, 'update'),
            'CustomerContact model deve ter método update()'
        );
    }

    public function testContactModelHasDeleteMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Models\CustomerContact::class, 'delete'),
            'CustomerContact model deve ter método delete()'
        );
    }

    // ══════════════════════════════════════════════════════════════
    // Validator — UniqueExcept method exists
    // ══════════════════════════════════════════════════════════════

    public function testValidatorHasUniqueExceptMethod(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Utils\Validator::class, 'uniqueExcept'),
            'Validator deve ter método uniqueExcept()'
        );
    }

    // ══════════════════════════════════════════════════════════════
    // View — Ficha do cliente existe
    // ══════════════════════════════════════════════════════════════

    public function testViewFileExists(): void
    {
        $this->assertFileExists(
            __DIR__ . '/../../app/views/customers/view.php',
            'View view.php deve existir'
        );
    }

    public function testViewFileContainsExpectedContent(): void
    {
        $content = file_get_contents(__DIR__ . '/../../app/views/customers/view.php');
        $this->assertStringContainsString('Identificação', $content, 'View deve ter seção Identificação');
        $this->assertStringContainsString('Contato', $content, 'View deve ter seção Contato');
        $this->assertStringContainsString('Endereço', $content, 'View deve ter seção Endereço');
        $this->assertStringContainsString('Comercial', $content, 'View deve ter seção Comercial');
        $this->assertStringContainsString('AUDITORIA', $content, 'View deve ter seção Auditoria');
        $this->assertStringContainsString('Histórico de Pedidos', $content, 'View deve ter seção Histórico de Pedidos');
        $this->assertStringContainsString('$stats', $content, 'View deve usar variável $stats');
        $this->assertStringContainsString('$contacts', $content, 'View deve usar variável $contacts');
        $this->assertStringContainsString('$recentOrders', $content, 'View deve usar variável $recentOrders');
    }

    // ══════════════════════════════════════════════════════════════
    // Validação completa — Simulação end-to-end
    // ══════════════════════════════════════════════════════════════

    public function testFullValidationPassesWithValidData(): void
    {
        $v = new Validator();
        $v->required('person_type', 'PJ', 'Tipo')
          ->inList('person_type', 'PJ', ['PF', 'PJ'], 'Tipo')
          ->required('name', 'Empresa ABC Ltda', 'Nome')
          ->minLength('name', 'Empresa ABC Ltda', 3, 'Nome')
          ->maxLength('name', 'Empresa ABC Ltda', 191, 'Nome')
          ->maxLength('fantasy_name', 'ABC', 191, 'Nome Fantasia')
          ->document('document', '11222333000181', 'PJ', 'CNPJ')
          ->email('email', 'contato@abc.com.br', 'E-mail')
          ->url('website', 'https://abc.com.br', 'Website')
          ->dateNotFuture('birth_date', '2020-01-15', 'Data Fundação')
          ->decimal('credit_limit', '50000.00', 'Limite')
          ->between('discount_default', '5', 0, 100, 'Desconto')
          ->inList('status', 'active', ['active', 'inactive', 'blocked'], 'Status');

        $this->assertTrue($v->passes(), 'Validação completa com dados válidos deve passar');
    }

    public function testFullValidationFailsWithInvalidData(): void
    {
        $v = new Validator();
        $v->required('person_type', '', 'Tipo')     // empty
          ->required('name', '', 'Nome')              // empty
          ->email('email', 'invalido', 'E-mail')      // bad email
          ->url('website', 'nao é url', 'Website')    // bad url
          ->document('document', '11111111111', 'PF', 'CPF')  // sequence
          ->dateNotFuture('birth_date', '2099-01-01', 'Data') // future
          ->decimal('credit_limit', '-100', 'Limite') // negative
          ->between('discount', '200', 0, 100, 'Desconto');   // out of range

        $this->assertTrue($v->fails());
        $this->assertGreaterThanOrEqual(7, count($v->errors()), 'Deve ter pelo menos 7 erros');
    }
}
