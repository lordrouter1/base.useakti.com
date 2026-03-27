<?php
namespace Akti\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Testes unitários da Fase 3 — Views + Interface (UX/UI).
 *
 * Cobre:
 * - 3.1: CSS personalizado com componentes .cst-*
 * - 3.2: create.php com wizard 4 steps
 * - 3.3: Toggle PF/PJ com campos condicionais
 * - 3.4: JavaScript de máscaras (IMask.js)
 * - 3.5: Auto-preenchimento por CEP (ViaCEP)
 * - 3.6: Consulta CNPJ (BrasilAPI)
 * - 3.7: Validação client-side em tempo real
 * - 3.8: edit.php redesenhado com wizard
 * - 3.9: view.php (ficha do cliente) com tabs e stat cards
 * - 3.10: Drawer de filtros avançados
 * - 3.11: Toggle tabela/cards na listagem
 * - 3.12: Ações em lote (checkbox + toolbar)
 * - 3.13: Novas colunas na tabela (tipo, cidade, status)
 * - 3.14: Responsividade (breakpoints CSS)
 * - Acessibilidade (aria-labels, roles, navegação teclado)
 *
 * Executar: vendor/bin/phpunit tests/Unit/CustomerFase3Test.php
 */
class CustomerFase3Test extends TestCase
{
    // ══════════════════════════════════════════════════════════════
    // 3.1 — CSS Personalizado
    // ══════════════════════════════════════════════════════════════

    public function testCssFileExists(): void
    {
        $path = __DIR__ . '/../../assets/css/customers.css';
        $this->assertFileExists($path, 'O arquivo customers.css deve existir');
    }

    public function testCssContainsCstVariables(): void
    {
        $css = file_get_contents(__DIR__ . '/../../assets/css/customers.css');
        $this->assertStringContainsString('--cst-primary:', $css, 'Deve definir variável --cst-primary');
        $this->assertStringContainsString('--cst-success:', $css, 'Deve definir variável --cst-success');
        $this->assertStringContainsString('--cst-danger:', $css, 'Deve definir variável --cst-danger');
        $this->assertStringContainsString('--cst-warning:', $css, 'Deve definir variável --cst-warning');
        $this->assertStringContainsString('--cst-bg:', $css, 'Deve definir variável --cst-bg');
        $this->assertStringContainsString('--cst-radius:', $css, 'Deve definir variável --cst-radius');
        $this->assertStringContainsString('--cst-shadow:', $css, 'Deve definir variável --cst-shadow');
    }

    public function testCssContainsStepperComponent(): void
    {
        $css = file_get_contents(__DIR__ . '/../../assets/css/customers.css');
        $this->assertStringContainsString('.cst-stepper', $css, 'Deve conter stepper horizontal');
        $this->assertStringContainsString('.cst-step', $css, 'Deve conter step item');
        $this->assertStringContainsString('.cst-step.active', $css, 'Deve conter step ativo');
        $this->assertStringContainsString('.cst-step.completed', $css, 'Deve conter step completado');
        $this->assertStringContainsString('.cst-step-number', $css, 'Deve conter número do step');
    }

    public function testCssContainsTogglePfPj(): void
    {
        $css = file_get_contents(__DIR__ . '/../../assets/css/customers.css');
        $this->assertStringContainsString('.cst-person-toggle', $css, 'Deve conter toggle PF/PJ');
        $this->assertStringContainsString('.cst-toggle-option', $css, 'Deve conter opções do toggle');
        $this->assertStringContainsString('.cst-toggle-option.active', $css, 'Deve conter estado ativo do toggle');
    }

    public function testCssContainsPhotoUpload(): void
    {
        $css = file_get_contents(__DIR__ . '/../../assets/css/customers.css');
        $this->assertStringContainsString('.cst-photo-upload', $css, 'Deve conter upload de foto');
        $this->assertStringContainsString('.cst-photo-overlay', $css, 'Deve conter overlay da foto');
        $this->assertStringContainsString('.cst-photo-placeholder', $css, 'Deve conter placeholder da foto');
    }

    public function testCssContainsValidationFeedback(): void
    {
        $css = file_get_contents(__DIR__ . '/../../assets/css/customers.css');
        $this->assertStringContainsString('.cst-field-valid', $css, 'Deve conter classe de campo válido');
        $this->assertStringContainsString('.cst-field-invalid', $css, 'Deve conter classe de campo inválido');
        $this->assertStringContainsString('.cst-field-msg', $css, 'Deve conter mensagem de feedback');
    }

    public function testCssContainsApiFilledStyle(): void
    {
        $css = file_get_contents(__DIR__ . '/../../assets/css/customers.css');
        $this->assertStringContainsString('.cst-api-filled', $css, 'Deve conter estilo para campo preenchido por API');
    }

    public function testCssContainsCompletenessBar(): void
    {
        $css = file_get_contents(__DIR__ . '/../../assets/css/customers.css');
        $this->assertStringContainsString('.cst-completeness', $css, 'Deve conter componente de completude');
        $this->assertStringContainsString('.cst-completeness-bar', $css, 'Deve conter barra de completude');
        $this->assertStringContainsString('.cst-completeness-fill', $css, 'Deve conter preenchimento da barra');
    }

    public function testCssContainsCustomerCard(): void
    {
        $css = file_get_contents(__DIR__ . '/../../assets/css/customers.css');
        $this->assertStringContainsString('.cst-customer-card', $css, 'Deve conter card de cliente');
        $this->assertStringContainsString('.cst-card-avatar', $css, 'Deve conter avatar do card');
        $this->assertStringContainsString('.cst-card-name', $css, 'Deve conter nome do card');
        $this->assertStringContainsString('.cst-card-actions', $css, 'Deve conter ações do card');
    }

    public function testCssContainsFilterDrawer(): void
    {
        $css = file_get_contents(__DIR__ . '/../../assets/css/customers.css');
        $this->assertStringContainsString('.cst-filter-drawer', $css, 'Deve conter drawer de filtros');
        $this->assertStringContainsString('.cst-filter-badges', $css, 'Deve conter badges de filtros');
        $this->assertStringContainsString('.cst-filter-badge', $css, 'Deve conter badge individual');
    }

    public function testCssContainsTagsComponent(): void
    {
        $css = file_get_contents(__DIR__ . '/../../assets/css/customers.css');
        $this->assertStringContainsString('.cst-tag', $css, 'Deve conter tag/chip');
        $this->assertStringContainsString('.cst-tag-input', $css, 'Deve conter input de tag');
        $this->assertStringContainsString('.cst-tag-input-wrapper', $css, 'Deve conter wrapper do input de tag');
        $this->assertStringContainsString('.cst-tag-remove', $css, 'Deve conter botão de remover tag');
    }

    public function testCssContainsProfileSection(): void
    {
        $css = file_get_contents(__DIR__ . '/../../assets/css/customers.css');
        $this->assertStringContainsString('.cst-profile-hero', $css, 'Deve conter hero do perfil');
        $this->assertStringContainsString('.cst-profile-avatar', $css, 'Deve conter avatar do perfil');
        $this->assertStringContainsString('.cst-profile-stat-card', $css, 'Deve conter stat card do perfil');
    }

    public function testCssContainsStatusBadges(): void
    {
        $css = file_get_contents(__DIR__ . '/../../assets/css/customers.css');
        $this->assertStringContainsString('.cst-status-active', $css, 'Deve conter badge status ativo');
        $this->assertStringContainsString('.cst-status-inactive', $css, 'Deve conter badge status inativo');
        $this->assertStringContainsString('.cst-status-blocked', $css, 'Deve conter badge status bloqueado');
    }

    public function testCssContainsBulkToolbar(): void
    {
        $css = file_get_contents(__DIR__ . '/../../assets/css/customers.css');
        $this->assertStringContainsString('.cst-bulk-toolbar', $css, 'Deve conter toolbar de ações em lote');
    }

    public function testCssContainsViewToggle(): void
    {
        $css = file_get_contents(__DIR__ . '/../../assets/css/customers.css');
        $this->assertStringContainsString('.cst-view-toggle', $css, 'Deve conter toggle de visualização');
    }

    public function testCssContainsWizardFooter(): void
    {
        $css = file_get_contents(__DIR__ . '/../../assets/css/customers.css');
        $this->assertStringContainsString('.cst-wizard-footer', $css, 'Deve conter footer do wizard');
    }

    public function testCssContainsFormSectionStyles(): void
    {
        $css = file_get_contents(__DIR__ . '/../../assets/css/customers.css');
        $this->assertStringContainsString('.cst-form-section', $css, 'Deve conter estilo de seção do form');
        $this->assertStringContainsString('.cst-form-section-title', $css, 'Deve conter título da seção');
    }

    public function testCssContainsAnimations(): void
    {
        $css = file_get_contents(__DIR__ . '/../../assets/css/customers.css');
        $this->assertStringContainsString('@keyframes cstStepFadeIn', $css, 'Deve conter animação de fade-in do step');
    }

    // ══════════════════════════════════════════
    // CSS — Responsividade
    // ══════════════════════════════════════════

    public function testCssContainsTabletBreakpoint(): void
    {
        $css = file_get_contents(__DIR__ . '/../../assets/css/customers.css');
        $this->assertStringContainsString('991.98px', $css, 'Deve conter breakpoint tablet (~992px)');
    }

    public function testCssContainsMobileBreakpoint(): void
    {
        $css = file_get_contents(__DIR__ . '/../../assets/css/customers.css');
        $this->assertStringContainsString('767.98px', $css, 'Deve conter breakpoint mobile (~768px)');
    }

    public function testCssContainsSmallMobileBreakpoint(): void
    {
        $css = file_get_contents(__DIR__ . '/../../assets/css/customers.css');
        $this->assertStringContainsString('575.98px', $css, 'Deve conter breakpoint mobile pequeno (~576px)');
    }

    // ══════════════════════════════════════════════════════════════
    // 3.2 / 3.3 — create.php: Wizard 4 steps + Toggle PF/PJ
    // ══════════════════════════════════════════════════════════════

    public function testCreateViewExists(): void
    {
        $this->assertFileExists(__DIR__ . '/../../app/views/customers/create.php');
    }

    public function testCreateViewHas4WizardSteps(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/create.php');
        $this->assertStringContainsString('id="cst-step-1"', $html, 'Deve conter Step 1');
        $this->assertStringContainsString('id="cst-step-2"', $html, 'Deve conter Step 2');
        $this->assertStringContainsString('id="cst-step-3"', $html, 'Deve conter Step 3');
        $this->assertStringContainsString('id="cst-step-4"', $html, 'Deve conter Step 4');
    }

    public function testCreateViewHasStepperNavigation(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/create.php');
        $this->assertStringContainsString('cst-stepper', $html, 'Deve conter stepper visual');
        $this->assertStringContainsString('data-step="1"', $html);
        $this->assertStringContainsString('data-step="2"', $html);
        $this->assertStringContainsString('data-step="3"', $html);
        $this->assertStringContainsString('data-step="4"', $html);
    }

    public function testCreateViewHasTogglePfPj(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/create.php');
        $this->assertStringContainsString('cst-person-toggle', $html, 'Deve conter toggle PF/PJ');
        $this->assertStringContainsString('data-type="PF"', $html, 'Deve conter opção PF');
        $this->assertStringContainsString('data-type="PJ"', $html, 'Deve conter opção PJ');
    }

    public function testCreateViewHasConditionalFields(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/create.php');
        $this->assertStringContainsString('id="group-gender"', $html, 'Deve conter grupo gênero (PF)');
        $this->assertStringContainsString('id="group-im"', $html, 'Deve conter grupo IM (PJ)');
        $this->assertStringContainsString('id="group-contact-pj"', $html, 'Deve conter grupo contato PJ');
    }

    public function testCreateViewHasPhotoUpload(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/create.php');
        $this->assertStringContainsString('cst-photo-upload', $html, 'Deve conter upload de foto');
        $this->assertStringContainsString('id="photo"', $html, 'Deve conter input de foto');
        $this->assertStringContainsString('id="preview-photo"', $html, 'Deve conter preview da foto');
    }

    public function testCreateViewHasCnpjSearchButton(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/create.php');
        $this->assertStringContainsString('id="btnSearchCnpj"', $html, 'Deve conter botão consultar CNPJ');
    }

    public function testCreateViewHasWizardNavButtons(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/create.php');
        $this->assertStringContainsString('id="btnWizardPrev"', $html, 'Deve conter botão Anterior');
        $this->assertStringContainsString('id="btnWizardNext"', $html, 'Deve conter botão Próximo');
        $this->assertStringContainsString('id="btnWizardSubmit"', $html, 'Deve conter botão Salvar');
    }

    public function testCreateViewHasCompletenessIndicator(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/create.php');
        $this->assertStringContainsString('cst-completeness', $html, 'Deve conter indicador de completude');
        $this->assertStringContainsString('id="completeness-fill"', $html, 'Deve conter barra de completude');
        $this->assertStringContainsString('id="completeness-text"', $html, 'Deve conter texto de completude');
        $this->assertStringContainsString('id="completeness-checks"', $html, 'Deve conter checklist de completude');
    }

    public function testCreateViewHasTagsInput(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/create.php');
        $this->assertStringContainsString('cst-tag-input-wrapper', $html, 'Deve conter wrapper de tags');
        $this->assertStringContainsString('id="tags-wrapper"', $html, 'Deve conter wrapper com id tags-wrapper');
        $this->assertStringContainsString('id="tags"', $html, 'Deve conter hidden de tags');
    }

    public function testCreateViewHasFormId(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/create.php');
        $this->assertStringContainsString('id="customerForm"', $html, 'Deve conter form com id customerForm');
    }

    public function testCreateViewHasCsrfField(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/create.php');
        $this->assertStringContainsString('csrf_field()', $html, 'Deve incluir campo CSRF');
    }

    public function testCreateViewIncludesCssFile(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/create.php');
        $this->assertStringContainsString('assets/css/customers.css', $html, 'Deve incluir CSS do módulo');
    }

    public function testCreateViewIncludesImaskCdn(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/create.php');
        $this->assertStringContainsString('imask', $html, 'Deve incluir IMask.js via CDN');
    }

    public function testCreateViewIncludesJsModules(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/create.php');
        $this->assertStringContainsString('customer-masks.js', $html, 'Deve incluir JS de máscaras');
        $this->assertStringContainsString('customer-validation.js', $html, 'Deve incluir JS de validação');
        $this->assertStringContainsString('customer-wizard.js', $html, 'Deve incluir JS do wizard');
    }

    public function testCreateViewHasAllStep1Fields(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/create.php');
        $fields = ['person_type', 'name', 'document', 'fantasy_name', 'rg_ie', 'im', 'birth_date', 'gender', 'status'];
        foreach ($fields as $f) {
            $this->assertStringContainsString('name="' . $f . '"', $html, "Step 1 deve conter campo: $f");
        }
    }

    public function testCreateViewHasAllStep2Fields(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/create.php');
        $fields = ['email', 'email_secondary', 'phone', 'cellphone', 'phone_commercial', 'website', 'instagram', 'contact_name', 'contact_role'];
        foreach ($fields as $f) {
            $this->assertStringContainsString('name="' . $f . '"', $html, "Step 2 deve conter campo: $f");
        }
    }

    public function testCreateViewHasAllStep3Fields(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/create.php');
        $fields = ['zipcode', 'address_street', 'address_number', 'address_complement', 'address_neighborhood', 'address_city', 'address_state', 'address_country', 'address_ibge'];
        foreach ($fields as $f) {
            $this->assertStringContainsString('name="' . $f . '"', $html, "Step 3 deve conter campo: $f");
        }
    }

    public function testCreateViewHasAllStep4Fields(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/create.php');
        $fields = ['price_table_id', 'payment_term', 'credit_limit', 'discount_default', 'seller_id', 'origin', 'tags', 'observations'];
        foreach ($fields as $f) {
            $this->assertStringContainsString('name="' . $f . '"', $html, "Step 4 deve conter campo: $f");
        }
    }

    // ══════════════════════════════════════════════════════════════
    // 3.4 — JavaScript de Máscaras (IMask.js)
    // ══════════════════════════════════════════════════════════════

    public function testMasksJsFileExists(): void
    {
        $this->assertFileExists(__DIR__ . '/../../assets/js/customer-masks.js');
    }

    public function testMasksJsContainsCpfCnpjMask(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-masks.js');
        $this->assertStringContainsString('000.000.000-00', $js, 'Deve conter máscara CPF');
        $this->assertStringContainsString('00.000.000/0000-00', $js, 'Deve conter máscara CNPJ');
    }

    public function testMasksJsContainsPhoneMasks(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-masks.js');
        $this->assertStringContainsString('(00) 0000-0000', $js, 'Deve conter máscara telefone fixo');
        $this->assertStringContainsString('(00) 00000-0000', $js, 'Deve conter máscara celular');
    }

    public function testMasksJsContainsCepMask(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-masks.js');
        $this->assertStringContainsString('00000-000', $js, 'Deve conter máscara CEP');
    }

    public function testMasksJsContainsBirthDateMask(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-masks.js');
        $this->assertStringContainsString('00/00/0000', $js, 'Deve conter máscara data');
    }

    public function testMasksJsContainsCurrencyMask(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-masks.js');
        $this->assertStringContainsString('R$', $js, 'Deve conter máscara moeda');
        $this->assertStringContainsString('thousandsSeparator', $js, 'Deve ter separador de milhar');
    }

    public function testMasksJsHasDynamicDocumentMask(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-masks.js');
        $this->assertStringContainsString('setDocumentMask', $js, 'Deve ter função de troca dinâmica CPF/CNPJ');
        $this->assertStringContainsString('CstMasks', $js, 'Deve exportar via window.CstMasks');
    }

    // ══════════════════════════════════════════════════════════════
    // 3.5 / 3.6 / 3.7 — Validação client-side
    // ══════════════════════════════════════════════════════════════

    public function testValidationJsFileExists(): void
    {
        $this->assertFileExists(__DIR__ . '/../../assets/js/customer-validation.js');
    }

    public function testValidationJsContainsCpfValidator(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-validation.js');
        $this->assertStringContainsString('isValidCPF', $js, 'Deve conter validação de CPF');
    }

    public function testValidationJsContainsCnpjValidator(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-validation.js');
        $this->assertStringContainsString('isValidCNPJ', $js, 'Deve conter validação de CNPJ');
    }

    public function testValidationJsContainsEmailValidator(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-validation.js');
        $this->assertStringContainsString('isValidEmail', $js, 'Deve conter validação de e-mail');
    }

    public function testValidationJsContainsCepValidation(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-validation.js');
        $this->assertStringContainsString('isValidCEP', $js, 'Deve conter validação de CEP');
    }

    public function testValidationJsContainsCepAutoFill(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-validation.js');
        $this->assertStringContainsString('searchCep', $js, 'Deve conter auto-preenchimento por CEP');
        $this->assertStringContainsString('action=searchCep', $js, 'Deve chamar endpoint searchCep');
    }

    public function testValidationJsContainsCnpjSearch(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-validation.js');
        $this->assertStringContainsString('searchCnpj', $js, 'Deve conter consulta CNPJ');
        $this->assertStringContainsString('action=searchCnpj', $js, 'Deve chamar endpoint searchCnpj');
    }

    public function testValidationJsContainsDuplicateCheck(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-validation.js');
        $this->assertStringContainsString('checkDuplicate', $js, 'Deve conter verificação de duplicidade');
        $this->assertStringContainsString('action=checkDuplicate', $js, 'Deve chamar endpoint checkDuplicate');
    }

    public function testValidationJsContainsFeedbackFunctions(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-validation.js');
        $this->assertStringContainsString('setValid', $js, 'Deve conter função de feedback positivo');
        $this->assertStringContainsString('setInvalid', $js, 'Deve conter função de feedback negativo');
        $this->assertStringContainsString('clearValidation', $js, 'Deve conter função de limpar validação');
    }

    public function testValidationJsContainsStepValidation(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-validation.js');
        $this->assertStringContainsString('validateStep', $js, 'Deve conter validação de step');
        $this->assertStringContainsString('CstValidation', $js, 'Deve exportar via window.CstValidation');
    }

    public function testValidationJsContainsFormSubmitValidation(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-validation.js');
        $this->assertStringContainsString('validateAll', $js, 'Deve conter validação completa no submit');
    }

    public function testValidationJsContainsBlurBindings(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-validation.js');
        $this->assertStringContainsString('blur', $js, 'Deve conter binding no evento blur');
        $this->assertStringContainsString('bindValidation', $js, 'Deve conter função de bind');
    }

    public function testValidationJsHasApiFilledClass(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-validation.js');
        $this->assertStringContainsString('cst-api-filled', $js, 'Deve aplicar classe cst-api-filled nos campos preenchidos por API');
    }

    // ══════════════════════════════════════════════════════════════
    // 3.7 — Wizard JS (customer-wizard.js)
    // ══════════════════════════════════════════════════════════════

    public function testWizardJsFileExists(): void
    {
        $this->assertFileExists(__DIR__ . '/../../assets/js/customer-wizard.js');
    }

    public function testWizardJsContainsStepNavigation(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-wizard.js');
        $this->assertStringContainsString('goToStep', $js, 'Deve conter função de navegação entre steps');
        $this->assertStringContainsString('currentStep', $js, 'Deve rastrear step atual');
        $this->assertStringContainsString('totalSteps', $js, 'Deve definir total de steps');
    }

    public function testWizardJsContainsTogglePfPjLogic(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-wizard.js');
        $this->assertStringContainsString('setPersonType', $js, 'Deve conter função de troca PF/PJ');
        $this->assertStringContainsString('group-gender', $js, 'Deve controlar visibilidade do gênero');
        $this->assertStringContainsString('group-im', $js, 'Deve controlar visibilidade do IM');
        $this->assertStringContainsString('group-contact-pj', $js, 'Deve controlar visibilidade do contato PJ');
    }

    public function testWizardJsContainsLabelMapping(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-wizard.js');
        $this->assertStringContainsString('Razão Social', $js, 'Deve mapear label para PJ: Razão Social');
        $this->assertStringContainsString('Nome Completo', $js, 'Deve mapear label para PF: Nome Completo');
        $this->assertStringContainsString('Nome Fantasia', $js, 'Deve mapear label para PJ: Nome Fantasia');
    }

    public function testWizardJsContainsCompletenessLogic(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-wizard.js');
        $this->assertStringContainsString('CstCompleteness', $js, 'Deve exportar via window.CstCompleteness');
        $this->assertStringContainsString('completeness-fill', $js, 'Deve atualizar barra de completude');
        $this->assertStringContainsString('completeness-text', $js, 'Deve atualizar texto de completude');
    }

    public function testWizardJsContainsCompletenessGroups(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-wizard.js');
        $this->assertStringContainsString('identification', $js, 'Deve ter grupo: identificação');
        $this->assertStringContainsString('contact', $js, 'Deve ter grupo: contato');
        $this->assertStringContainsString('address', $js, 'Deve ter grupo: endereço');
        $this->assertStringContainsString('commercial', $js, 'Deve ter grupo: comercial');
    }

    public function testWizardJsContainsAutoSave(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-wizard.js');
        $this->assertStringContainsString('saveDraft', $js, 'Deve ter auto-save');
        $this->assertStringContainsString('loadDraft', $js, 'Deve ter carregamento de rascunho');
        $this->assertStringContainsString('clearDraft', $js, 'Deve ter limpeza de rascunho');
        $this->assertStringContainsString('localStorage', $js, 'Deve usar localStorage');
    }

    public function testWizardJsContainsKeyboardShortcuts(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-wizard.js');
        $this->assertStringContainsString('Ctrl', $js, 'Deve ter atalhos com Ctrl');
        $this->assertStringContainsString('ArrowRight', $js, 'Deve ter Ctrl+→');
        $this->assertStringContainsString('ArrowLeft', $js, 'Deve ter Ctrl+←');
        $this->assertStringContainsString('Escape', $js, 'Deve ter Esc');
    }

    public function testWizardJsContainsPhotoUpload(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-wizard.js');
        $this->assertStringContainsString('bindPhotoUpload', $js, 'Deve ter upload de foto');
        $this->assertStringContainsString('dragover', $js, 'Deve suportar drag & drop');
        $this->assertStringContainsString('FileReader', $js, 'Deve usar FileReader para preview');
    }

    // ══════════════════════════════════════════════════════════════
    // 3.8 — edit.php: Wizard com dados pré-preenchidos
    // ══════════════════════════════════════════════════════════════

    public function testEditViewExists(): void
    {
        $this->assertFileExists(__DIR__ . '/../../app/views/customers/edit.php');
    }

    public function testEditViewHas4WizardSteps(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/edit.php');
        $this->assertStringContainsString('id="cst-step-1"', $html, 'Edit: deve conter Step 1');
        $this->assertStringContainsString('id="cst-step-2"', $html, 'Edit: deve conter Step 2');
        $this->assertStringContainsString('id="cst-step-3"', $html, 'Edit: deve conter Step 3');
        $this->assertStringContainsString('id="cst-step-4"', $html, 'Edit: deve conter Step 4');
    }

    public function testEditViewHasBannerResumido(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/edit.php');
        $this->assertStringContainsString('fa-user-edit', $html, 'Edit: deve ter ícone de edição');
        $this->assertStringContainsString('action=view', $html, 'Edit: deve ter link para a ficha');
    }

    public function testEditViewHasPreFilledFields(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/edit.php');
        $this->assertStringContainsString('$c[\'name\']', $html, 'Edit: deve pré-preencher nome');
        $this->assertStringContainsString('$c[\'document\']', $html, 'Edit: deve pré-preencher documento');
        $this->assertStringContainsString('$c[\'email\']', $html, 'Edit: deve pré-preencher e-mail');
    }

    public function testEditViewHasHiddenId(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/edit.php');
        $this->assertStringContainsString('name="id"', $html, 'Edit: deve ter input hidden de ID');
    }

    public function testEditViewIncludesJsModules(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/edit.php');
        $this->assertStringContainsString('customer-masks.js', $html, 'Edit: deve incluir JS de máscaras');
        $this->assertStringContainsString('customer-validation.js', $html, 'Edit: deve incluir JS de validação');
        $this->assertStringContainsString('customer-wizard.js', $html, 'Edit: deve incluir JS do wizard');
    }

    public function testEditViewHasTagsPrePopulated(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/edit.php');
        $this->assertStringContainsString('$c[\'tags\']', $html, 'Edit: deve pré-preencher tags');
    }

    public function testEditViewHasCompletenessIndicator(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/edit.php');
        $this->assertStringContainsString('cst-completeness', $html, 'Edit: deve conter indicador de completude');
    }

    // ══════════════════════════════════════════════════════════════
    // 3.9 — view.php: Ficha do cliente com tabs
    // ══════════════════════════════════════════════════════════════

    public function testViewPageExists(): void
    {
        $this->assertFileExists(__DIR__ . '/../../app/views/customers/view.php');
    }

    public function testViewPageHasHeroSection(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/view.php');
        $this->assertStringContainsString('cst-profile-hero', $html, 'Ficha: deve conter hero/banner');
        $this->assertStringContainsString('cst-profile-avatar', $html, 'Ficha: deve conter avatar');
    }

    public function testViewPageHasStatCards(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/view.php');
        $this->assertStringContainsString('cst-profile-stat-card', $html, 'Ficha: deve conter stat cards');
        $this->assertStringContainsString('total_orders', $html, 'Ficha: stat de total pedidos');
        $this->assertStringContainsString('total_value', $html, 'Ficha: stat de valor total');
        $this->assertStringContainsString('last_order_date', $html, 'Ficha: stat de último pedido');
        $this->assertStringContainsString('avg_ticket', $html, 'Ficha: stat de ticket médio');
    }

    public function testViewPageHasTabs(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/view.php');
        $this->assertStringContainsString('id="viewTabs"', $html, 'Ficha: deve conter tabs');
        $this->assertStringContainsString('id="pane-dados"', $html, 'Ficha: tab Dados');
        $this->assertStringContainsString('id="pane-contato"', $html, 'Ficha: tab Contato');
        $this->assertStringContainsString('id="pane-endereco"', $html, 'Ficha: tab Endereço');
        $this->assertStringContainsString('id="pane-comercial"', $html, 'Ficha: tab Comercial');
        $this->assertStringContainsString('id="pane-historico"', $html, 'Ficha: tab Histórico');
    }

    public function testViewPageHasAriaAttributes(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/view.php');
        $this->assertStringContainsString('role="tablist"', $html, 'Ficha: deve ter role=tablist');
        $this->assertStringContainsString('role="tab"', $html, 'Ficha: deve ter role=tab');
        $this->assertStringContainsString('role="tabpanel"', $html, 'Ficha: deve ter role=tabpanel');
        $this->assertStringContainsString('aria-label', $html, 'Ficha: deve ter aria-labels');
        $this->assertStringContainsString('aria-controls', $html, 'Ficha: deve ter aria-controls');
    }

    public function testViewPageHasBreadcrumb(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/view.php');
        $this->assertStringContainsString('breadcrumb', $html, 'Ficha: deve conter breadcrumb');
        $this->assertStringContainsString('aria-current="page"', $html, 'Ficha: breadcrumb com aria-current');
    }

    public function testViewPageHasEditButton(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/view.php');
        $this->assertStringContainsString('action=edit', $html, 'Ficha: deve ter botão editar');
    }

    public function testViewPageHasDeleteAction(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/view.php');
        $this->assertStringContainsString('deleteCustomer', $html, 'Ficha: deve ter ação de excluir');
    }

    public function testViewPageShowsContactsSection(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/view.php');
        $this->assertStringContainsString('$contacts', $html, 'Ficha: deve exibir contatos adicionais');
    }

    public function testViewPageShowsOrderHistory(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/view.php');
        $this->assertStringContainsString('$recentOrders', $html, 'Ficha: deve exibir histórico de pedidos');
    }

    public function testViewPageShowsAuditInfo(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/view.php');
        $this->assertStringContainsString('created_at', $html, 'Ficha: deve exibir data de criação');
        $this->assertStringContainsString('updated_at', $html, 'Ficha: deve exibir data de atualização');
    }

    // ══════════════════════════════════════════════════════════════
    // 3.10 — Drawer de filtros na listagem
    // ══════════════════════════════════════════════════════════════

    public function testIndexViewExists(): void
    {
        $this->assertFileExists(__DIR__ . '/../../app/views/customers/index.php');
    }

    public function testIndexViewHasFilterDrawer(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/index.php');
        $this->assertStringContainsString('id="filterDrawer"', $html, 'Listagem: deve conter drawer de filtros');
        $this->assertStringContainsString('offcanvas-end', $html, 'Listagem: drawer como offcanvas Bootstrap 5');
    }

    public function testIndexViewHasStatusFilter(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/index.php');
        $this->assertStringContainsString('filter-status', $html, 'Listagem: filtro de status');
        $this->assertStringContainsString('value="active"', $html, 'Listagem: opção ativo');
        $this->assertStringContainsString('value="inactive"', $html, 'Listagem: opção inativo');
        $this->assertStringContainsString('value="blocked"', $html, 'Listagem: opção bloqueado');
    }

    public function testIndexViewHasPersonTypeFilter(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/index.php');
        $this->assertStringContainsString('filter-person-type', $html, 'Listagem: filtro de tipo');
        $this->assertStringContainsString('value="PF"', $html, 'Listagem: opção PF');
        $this->assertStringContainsString('value="PJ"', $html, 'Listagem: opção PJ');
    }

    public function testIndexViewHasStateFilter(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/index.php');
        $this->assertStringContainsString('id="fState"', $html, 'Listagem: filtro de UF');
    }

    public function testIndexViewHasCityFilter(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/index.php');
        $this->assertStringContainsString('id="fCity"', $html, 'Listagem: filtro de cidade');
    }

    public function testIndexViewHasTagsFilter(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/index.php');
        $this->assertStringContainsString('id="fTags"', $html, 'Listagem: filtro de tags');
    }

    public function testIndexViewHasDateRangeFilter(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/index.php');
        $this->assertStringContainsString('id="fDateFrom"', $html, 'Listagem: filtro data de');
        $this->assertStringContainsString('id="fDateTo"', $html, 'Listagem: filtro data até');
    }

    public function testIndexViewHasFilterBadges(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/index.php');
        $this->assertStringContainsString('id="filterBadges"', $html, 'Listagem: badges de filtros ativos');
    }

    public function testIndexViewHasApplyResetButtons(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/index.php');
        $this->assertStringContainsString('btnApplyFilters', $html, 'Listagem: botão Aplicar filtros');
        $this->assertStringContainsString('btnResetFilters', $html, 'Listagem: botão Limpar filtros');
    }

    // ══════════════════════════════════════════════════════════════
    // 3.11 — Toggle tabela/cards
    // ══════════════════════════════════════════════════════════════

    public function testIndexViewHasViewToggle(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/index.php');
        $this->assertStringContainsString('cst-view-toggle', $html, 'Listagem: toggle de visualização');
        $this->assertStringContainsString('id="btnViewTable"', $html, 'Listagem: botão visualizar tabela');
        $this->assertStringContainsString('id="btnViewCards"', $html, 'Listagem: botão visualizar cards');
    }

    public function testIndexViewHasTableContainer(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/index.php');
        $this->assertStringContainsString('id="customersTableWrap"', $html, 'Listagem: container da tabela');
        $this->assertStringContainsString('id="customersTableBody"', $html, 'Listagem: tbody da tabela');
    }

    public function testIndexViewHasCardsContainer(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/index.php');
        $this->assertStringContainsString('id="customersCardsGrid"', $html, 'Listagem: grid de cards');
    }

    // ══════════════════════════════════════════════════════════════
    // 3.12 — Ações em lote
    // ══════════════════════════════════════════════════════════════

    public function testIndexViewHasCheckboxAll(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/index.php');
        $this->assertStringContainsString('id="checkAll"', $html, 'Listagem: checkbox selecionar todos');
    }

    public function testIndexViewHasBulkToolbar(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/index.php');
        $this->assertStringContainsString('id="bulkToolbar"', $html, 'Listagem: toolbar de ações em lote');
        $this->assertStringContainsString('id="bulkCount"', $html, 'Listagem: contagem de selecionados');
    }

    public function testIndexViewHasBulkExport(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/index.php');
        $this->assertStringContainsString('id="btnBulkExport"', $html, 'Listagem: exportar em lote');
    }

    public function testIndexViewHasBulkStatusActions(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/index.php');
        $this->assertStringContainsString('bulk-status-action', $html, 'Listagem: ações de status em lote');
        $this->assertStringContainsString('data-status="active"', $html, 'Listagem: ação ativar em lote');
        $this->assertStringContainsString('data-status="inactive"', $html, 'Listagem: ação inativar em lote');
        $this->assertStringContainsString('data-status="blocked"', $html, 'Listagem: ação bloquear em lote');
    }

    public function testIndexViewHasBulkDelete(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/index.php');
        $this->assertStringContainsString('id="btnBulkDelete"', $html, 'Listagem: excluir em lote');
    }

    // ══════════════════════════════════════════════════════════════
    // 3.13 — Novas colunas na tabela
    // ══════════════════════════════════════════════════════════════

    public function testIndexTableHasTypeColumn(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/index.php');
        $this->assertStringContainsString('>Tipo</th>', $html, 'Tabela: deve ter coluna Tipo');
    }

    public function testIndexTableHasCityColumn(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/index.php');
        $this->assertStringContainsString('Cidade/UF', $html, 'Tabela: deve ter coluna Cidade/UF');
    }

    public function testIndexTableHasStatusColumn(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/index.php');
        $this->assertStringContainsString('>Status</th>', $html, 'Tabela: deve ter coluna Status');
    }

    public function testIndexTableHasActionsColumn(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/index.php');
        $this->assertStringContainsString('>Ações</th>', $html, 'Tabela: deve ter coluna Ações');
    }

    // ══════════════════════════════════════════════════════════════
    // 3.14 — Responsividade e integração
    // ══════════════════════════════════════════════════════════════

    public function testIndexViewIncludesCssFile(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/index.php');
        $this->assertStringContainsString('assets/css/customers.css', $html, 'Listagem: deve incluir CSS do módulo');
    }

    public function testIndexViewHasPagination(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/index.php');
        $this->assertStringContainsString('id="cstPagination"', $html, 'Listagem: deve ter paginação');
        $this->assertStringContainsString('id="cstPaginationInfo"', $html, 'Listagem: deve ter info de paginação');
    }

    public function testIndexViewHasSearchInput(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/index.php');
        $this->assertStringContainsString('id="cst_search"', $html, 'Listagem: deve ter campo de busca');
    }

    public function testIndexViewHasExportButton(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/index.php');
        $this->assertStringContainsString('id="btnExportCsv"', $html, 'Listagem: deve ter botão exportar');
    }

    public function testIndexViewHasSidebarNavigation(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/index.php');
        $this->assertStringContainsString('cst-sidebar', $html, 'Listagem: deve ter sidebar');
        $this->assertStringContainsString('data-section="overview"', $html, 'Listagem: deve ter seção overview');
        $this->assertStringContainsString('data-section="create"', $html, 'Listagem: deve ter seção create');
        $this->assertStringContainsString('data-section="import"', $html, 'Listagem: deve ter seção import');
    }

    public function testIndexViewHasImportSection(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/index.php');
        $this->assertStringContainsString('id="cst-import"', $html, 'Listagem: deve ter seção de importação');
        $this->assertStringContainsString('import-dropzone', $html, 'Listagem: deve ter dropzone de importação');
        $this->assertStringContainsString('id="importFileInput"', $html, 'Listagem: deve ter input de arquivo');
    }

    public function testIndexViewHasKeyboardShortcuts(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/index.php');
        // O JS inline contém atalhos de teclado
        $this->assertStringContainsString("key === '/'", $html, 'Listagem: atalho / para focar busca');
    }

    public function testIndexViewHasAjaxLoadFunction(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/index.php');
        $this->assertStringContainsString('loadCustomers', $html, 'Listagem: função AJAX de carregar clientes');
        $this->assertStringContainsString('action: \'getCustomersList\'', $html, 'Listagem: chama action getCustomersList');
    }

    public function testIndexViewHasCardRenderFunction(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/index.php');
        $this->assertStringContainsString('renderCards', $html, 'Listagem: função de renderizar cards');
        $this->assertStringContainsString('cst-customer-card', $html, 'Listagem: usa classe de card do cliente');
    }

    public function testIndexViewHasDocFormatHelper(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/index.php');
        $this->assertStringContainsString('formatDoc', $html, 'Listagem: função helper de formato de documento');
    }

    // ══════════════════════════════════════════════════════════════
    // Acessibilidade
    // ══════════════════════════════════════════════════════════════

    public function testViewPageHasAriaCurrentOnBreadcrumb(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/view.php');
        $this->assertStringContainsString('aria-current="page"', $html, 'Ficha: breadcrumb deve ter aria-current=page');
    }

    public function testViewPageHasAriaLabelsOnButtons(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/view.php');
        $this->assertStringContainsString('aria-label="Voltar', $html, 'Ficha: botão voltar com aria-label');
        $this->assertStringContainsString('aria-label="Editar', $html, 'Ficha: botão editar com aria-label');
        $this->assertStringContainsString('aria-label="Mais', $html, 'Ficha: menu mais ações com aria-label');
    }

    public function testViewPageHasAriaOnTabs(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/view.php');
        $this->assertStringContainsString('aria-selected="true"', $html, 'Ficha: tab selecionada');
        $this->assertStringContainsString('aria-selected="false"', $html, 'Ficha: tabs não selecionadas');
    }

    public function testIndexDrawerHasAriaLabel(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/index.php');
        $this->assertStringContainsString('aria-labelledby="filterDrawerLabel"', $html, 'Listagem: drawer com aria-labelledby');
    }

    public function testCreateFormHasLabelsForAllInputs(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/create.php');
        // Verificar que campos importantes tem label com for
        $this->assertStringContainsString('for="name"', $html, 'Create: label para nome');
        $this->assertStringContainsString('for="document"', $html, 'Create: label para documento');
        $this->assertStringContainsString('for="email"', $html, 'Create: label para email');
        $this->assertStringContainsString('for="zipcode"', $html, 'Create: label para CEP');
    }
}
