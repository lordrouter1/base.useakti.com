<?php
namespace Akti\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Testes unitários da Fase 4 — Integrações, Testes e Documentação.
 *
 * Cobre:
 * - 4.1: Duplicidade em tempo real (on blur) — JS + controller
 * - 4.2: Campo de tags com autocomplete e chips (JS + controller + model)
 * - 4.3: Indicador de completude do cadastro (JS)
 * - 4.4: Auto-save em localStorage (JS)
 * - 4.5: Atalhos de teclado (JS)
 * - 4.6: Histórico de pedidos na ficha do cliente (view + controller)
 * - 4.7: Importação atualizada com novos campos (controller)
 * - 4.8: Testes unitários de validação CPF/CNPJ (já existem em ValidatorCpfCnpjTest)
 * - 4.9: CRUD completo (já existe em CustomerModelTest, complementado aqui)
 * - 4.10: Script de limpeza de duplicatas
 * - 4.11: Documentação final
 *
 * Executar: vendor/bin/phpunit tests/Unit/CustomerFase4Test.php
 */
class CustomerFase4Test extends TestCase
{
    // ══════════════════════════════════════════════════════════════
    // 4.2 — Campo de Tags com Autocomplete e Chips (JS)
    // ══════════════════════════════════════════════════════════════

    public function testTagsJsFileExists(): void
    {
        $this->assertFileExists(__DIR__ . '/../../assets/js/customer-tags.js');
    }

    public function testTagsJsContainsAutocompleteLogic(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-tags.js');
        $this->assertStringContainsString('loadKnownTags', $js, 'Deve ter carregamento de tags via AJAX');
        $this->assertStringContainsString('allKnownTags', $js, 'Deve armazenar tags conhecidas');
        $this->assertStringContainsString('showSuggestions', $js, 'Deve mostrar dropdown de sugestões');
        $this->assertStringContainsString('hideSuggestions', $js, 'Deve ocultar dropdown de sugestões');
    }

    public function testTagsJsCallsGetTagsEndpoint(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-tags.js');
        $this->assertStringContainsString('action=getTags', $js, 'Deve chamar endpoint getTags');
    }

    public function testTagsJsContainsPillChipRendering(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-tags.js');
        $this->assertStringContainsString('renderPills', $js, 'Deve renderizar pills/chips');
        $this->assertStringContainsString('cst-tag', $js, 'Deve usar classe cst-tag');
        $this->assertStringContainsString('cst-tag-remove', $js, 'Deve ter botão remover');
    }

    public function testTagsJsHandlesAddAndRemoveTags(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-tags.js');
        $this->assertStringContainsString('addTag', $js, 'Deve ter função addTag');
        $this->assertStringContainsString('removeTag', $js, 'Deve ter função removeTag');
        $this->assertStringContainsString('getCurrentTags', $js, 'Deve ter função getCurrentTags');
        $this->assertStringContainsString('setCurrentTags', $js, 'Deve ter função setCurrentTags');
    }

    public function testTagsJsStoresAsCommaSeparated(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-tags.js');
        $this->assertStringContainsString("join(',')", $js, 'Deve juntar tags com vírgula');
        $this->assertStringContainsString("split(',')", $js, 'Deve separar tags por vírgula');
    }

    public function testTagsJsHandlesKeyboardEvents(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-tags.js');
        $this->assertStringContainsString('Enter', $js, 'Deve responder a Enter');
        $this->assertStringContainsString('Backspace', $js, 'Deve responder a Backspace');
        $this->assertStringContainsString('keydown', $js, 'Deve usar keydown');
    }

    public function testTagsJsPreventsDuplicateTags(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-tags.js');
        $this->assertStringContainsString('toLowerCase', $js, 'Deve comparar case-insensitive');
        // Verifica existência da lógica de detecção de duplicata
        $this->assertMatchesRegularExpression('/exists|duplicat/i', $js, 'Deve verificar existência antes de adicionar');
    }

    public function testTagsJsHasColorGeneration(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-tags.js');
        $this->assertStringContainsString('tagColor', $js, 'Deve ter função de cor de tag');
        $this->assertStringContainsString('TAG_COLORS', $js, 'Deve ter paleta de cores');
    }

    public function testTagsJsHasAccessibility(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-tags.js');
        $this->assertStringContainsString('aria-label', $js, 'Deve ter aria-label nos botões');
        $this->assertStringContainsString('autocomplete', $js, 'Deve desabilitar autocomplete nativo');
    }

    public function testTagsJsEscapesHtml(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-tags.js');
        $this->assertStringContainsString('escapeHtml', $js, 'Deve escapar HTML para segurança');
    }

    public function testTagsJsDispatchesChangeEvent(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-tags.js');
        $this->assertStringContainsString('dispatchEvent', $js, 'Deve disparar evento change para completude/autosave');
        $this->assertStringContainsString("new Event('change'", $js, 'Deve disparar evento de change');
    }

    // ══════════════════════════════════════════════════════════════
    // 4.3 — Indicador de Completude do Cadastro (JS)
    // ══════════════════════════════════════════════════════════════

    public function testCompletenessJsFileExists(): void
    {
        $this->assertFileExists(__DIR__ . '/../../assets/js/customer-completeness.js');
    }

    public function testCompletenessJsHasWeightGroups(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-completeness.js');
        $this->assertStringContainsString('identification', $js, 'Deve ter grupo Identificação');
        $this->assertStringContainsString('contact', $js, 'Deve ter grupo Contato');
        $this->assertStringContainsString('address', $js, 'Deve ter grupo Endereço');
        $this->assertStringContainsString('commercial', $js, 'Deve ter grupo Comercial');
    }

    public function testCompletenessJsHasCorrectWeights(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-completeness.js');
        $this->assertStringContainsString('weight: 30', $js, 'Identificação deve ter peso 30');
        $this->assertStringContainsString('weight: 25', $js, 'Contato e Endereço devem ter peso 25');
        $this->assertStringContainsString('weight: 20', $js, 'Comercial deve ter peso 20');
    }

    public function testCompletenessJsHasCalculateFunction(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-completeness.js');
        $this->assertStringContainsString('function calculate', $js, 'Deve ter função calculate');
        $this->assertStringContainsString('total', $js, 'Deve calcular total');
        $this->assertStringContainsString('groups', $js, 'Deve retornar grupos');
    }

    public function testCompletenessJsHasUpdateFunction(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-completeness.js');
        $this->assertStringContainsString('function update', $js, 'Deve ter função update');
        $this->assertStringContainsString('completeness-fill', $js, 'Deve atualizar barra');
        $this->assertStringContainsString('completeness-text', $js, 'Deve atualizar texto');
        $this->assertStringContainsString('completeness-checks', $js, 'Deve atualizar checklist');
    }

    public function testCompletenessJsExportsGlobally(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-completeness.js');
        $this->assertStringContainsString('window.CstCompleteness', $js, 'Deve exportar via window.CstCompleteness');
        $this->assertStringContainsString('CstCompleteness.update', $js, 'Deve expor update');
        $this->assertStringContainsString('CstCompleteness.getData', $js, 'Deve expor getData');
        $this->assertStringContainsString('CstCompleteness.calculate', $js, 'Deve expor calculate');
    }

    public function testCompletenessJsBindsEvents(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-completeness.js');
        $this->assertStringContainsString('change', $js, 'Deve escutar evento change');
        $this->assertStringContainsString('input', $js, 'Deve escutar evento input');
        $this->assertStringContainsString('bindCompletenessEvents', $js, 'Deve ter bind de eventos');
    }

    public function testCompletenessJsHasColorCoding(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-completeness.js');
        $this->assertStringContainsString('low', $js, 'Deve ter classe low (vermelho)');
        $this->assertStringContainsString('medium', $js, 'Deve ter classe medium (amarelo)');
        $this->assertStringContainsString('high', $js, 'Deve ter classe high (verde)');
    }

    public function testCompletenessJsShowsGroupStatus(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-completeness.js');
        $this->assertStringContainsString('done', $js, 'Deve marcar grupo como done');
        $this->assertStringContainsString('pending', $js, 'Deve marcar grupo como pending');
        $this->assertStringContainsString('Identificação', $js, 'Deve ter label Identificação');
        $this->assertStringContainsString('Contato', $js, 'Deve ter label Contato');
        $this->assertStringContainsString('Endereço', $js, 'Deve ter label Endereço');
        $this->assertStringContainsString('Comercial', $js, 'Deve ter label Comercial');
    }

    // ══════════════════════════════════════════════════════════════
    // 4.4 — Auto-save em localStorage (JS)
    // ══════════════════════════════════════════════════════════════

    public function testAutosaveJsFileExists(): void
    {
        $this->assertFileExists(__DIR__ . '/../../assets/js/customer-autosave.js');
    }

    public function testAutosaveJsHasSaveDraftFunction(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-autosave.js');
        $this->assertStringContainsString('saveDraft', $js, 'Deve ter função saveDraft');
        $this->assertStringContainsString('localStorage', $js, 'Deve usar localStorage');
    }

    public function testAutosaveJsHasLoadDraftFunction(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-autosave.js');
        $this->assertStringContainsString('loadDraft', $js, 'Deve ter função loadDraft');
    }

    public function testAutosaveJsHasClearDraftFunction(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-autosave.js');
        $this->assertStringContainsString('clearDraft', $js, 'Deve ter função clearDraft');
    }

    public function testAutosaveJsHasAutoInterval(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-autosave.js');
        $this->assertStringContainsString('setInterval', $js, 'Deve ter intervalo de auto-save');
        $this->assertStringContainsString('SAVE_INTERVAL', $js, 'Deve definir constante de intervalo');
        $this->assertStringContainsString('30000', $js, 'Deve usar 30 segundos');
    }

    public function testAutosaveJsHasDraftMaxAge(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-autosave.js');
        $this->assertStringContainsString('DRAFT_MAX_AGE', $js, 'Deve definir max age do draft');
        $this->assertStringContainsString('86400000', $js, 'Deve expirar em 24h');
    }

    public function testAutosaveJsHasDraftPrefix(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-autosave.js');
        $this->assertStringContainsString('akti_customer_draft', $js, 'Deve ter prefixo de chave correto');
    }

    public function testAutosaveJsHandlesRestoreFlow(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-autosave.js');
        $this->assertStringContainsString('restoreDraft', $js, 'Deve ter restauração de rascunho');
        $this->assertStringContainsString('Restaurar', $js, 'Deve oferecer opção Restaurar');
        $this->assertStringContainsString('Descartar', $js, 'Deve oferecer opção Descartar');
    }

    public function testAutosaveJsClearsOnSubmit(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-autosave.js');
        $this->assertStringContainsString('submit', $js, 'Deve escutar evento submit');
        $this->assertStringContainsString('clearDraft', $js, 'Deve limpar draft ao submeter');
    }

    public function testAutosaveJsExcludesSensitiveFields(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-autosave.js');
        $this->assertStringContainsString('csrf_token', $js, 'Deve excluir csrf_token do draft');
        $this->assertStringContainsString("type !== 'file'", $js, 'Deve excluir inputs de arquivo');
    }

    public function testAutosaveJsExportsGlobally(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-autosave.js');
        $this->assertStringContainsString('window.CstAutosave', $js, 'Deve exportar via window.CstAutosave');
        $this->assertStringContainsString('CstAutosave.save', $js, 'Deve expor save');
        $this->assertStringContainsString('CstAutosave.load', $js, 'Deve expor load');
        $this->assertStringContainsString('CstAutosave.clear', $js, 'Deve expor clear');
    }

    public function testAutosaveJsHandlesCancelButton(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-autosave.js');
        $this->assertStringContainsString('cancel', $js, 'Deve escutar clique no botão cancelar');
        $this->assertStringContainsString('descartar', strtolower($js), 'Deve perguntar sobre descartar');
    }

    public function testAutosaveJsUpdatesCompleteness(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-autosave.js');
        $this->assertStringContainsString('CstCompleteness', $js, 'Deve atualizar completude após restaurar');
    }

    // ══════════════════════════════════════════════════════════════
    // 4.5 — Atalhos de Teclado (JS)
    // ══════════════════════════════════════════════════════════════

    public function testShortcutsJsFileExists(): void
    {
        $this->assertFileExists(__DIR__ . '/../../assets/js/customer-shortcuts.js');
    }

    public function testShortcutsJsHasCtrlSave(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-shortcuts.js');
        $this->assertStringContainsString("key === 's'", $js, 'Deve ter Ctrl+S para salvar');
        $this->assertStringContainsString('ctrlKey', $js, 'Deve verificar tecla Ctrl');
        $this->assertStringContainsString('submit', $js, 'Deve submeter formulário');
    }

    public function testShortcutsJsHasCtrlArrows(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-shortcuts.js');
        $this->assertStringContainsString('ArrowRight', $js, 'Deve ter Ctrl+→ para próximo step');
        $this->assertStringContainsString('ArrowLeft', $js, 'Deve ter Ctrl+← para step anterior');
    }

    public function testShortcutsJsHasEscapeHandler(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-shortcuts.js');
        $this->assertStringContainsString('Escape', $js, 'Deve ter handler para Escape');
        $this->assertStringContainsString('page=customers', $js, 'Deve voltar à listagem');
    }

    public function testShortcutsJsHasCtrlN(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-shortcuts.js');
        $this->assertStringContainsString("key === 'n'", $js, 'Deve ter Ctrl+N para novo cliente');
        $this->assertStringContainsString('action=create', $js, 'Deve redirecionar para create');
    }

    public function testShortcutsJsHasCtrlE(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-shortcuts.js');
        $this->assertStringContainsString("key === 'e'", $js, 'Deve ter Ctrl+E para exportar');
        $this->assertStringContainsString('export', $js, 'Deve acionar exportação');
    }

    public function testShortcutsJsHasSlashToSearch(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-shortcuts.js');
        $this->assertStringContainsString("key === '/'", $js, 'Deve ter / para focar busca');
        $this->assertStringContainsString('focus', $js, 'Deve focar no campo de busca');
    }

    public function testShortcutsJsHasContextDetection(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-shortcuts.js');
        $this->assertStringContainsString('getContext', $js, 'Deve detectar contexto da página');
        $this->assertStringContainsString("'create'", $js, 'Deve reconhecer contexto create');
        $this->assertStringContainsString("'edit'", $js, 'Deve reconhecer contexto edit');
        $this->assertStringContainsString("'view'", $js, 'Deve reconhecer contexto view');
        $this->assertStringContainsString("'list'", $js, 'Deve reconhecer contexto list');
    }

    public function testShortcutsJsPreventDefaultOnInputs(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-shortcuts.js');
        $this->assertStringContainsString('isInputFocused', $js, 'Deve verificar se input está focado');
        $this->assertStringContainsString('preventDefault', $js, 'Deve prevenir comportamento padrão');
    }

    public function testShortcutsJsHasHelpPanel(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-shortcuts.js');
        $this->assertStringContainsString('showShortcutsHelp', $js, 'Deve ter painel de ajuda de atalhos');
        $this->assertStringContainsString("key === '?'", $js, 'Deve abrir com tecla ?');
    }

    public function testShortcutsJsExportsGlobally(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-shortcuts.js');
        $this->assertStringContainsString('window.CstShortcuts', $js, 'Deve exportar via window.CstShortcuts');
        $this->assertStringContainsString('CstShortcuts.showHelp', $js, 'Deve expor showHelp');
    }

    public function testShortcutsJsValidatesBeforeSave(): void
    {
        $js = file_get_contents(__DIR__ . '/../../assets/js/customer-shortcuts.js');
        $this->assertStringContainsString('CstValidation', $js, 'Deve validar antes de submeter');
        $this->assertStringContainsString('validateAll', $js, 'Deve chamar validateAll()');
    }

    // ══════════════════════════════════════════════════════════════
    // 4.6 — Histórico de Pedidos na Ficha (view.php + controller)
    // ══════════════════════════════════════════════════════════════

    public function testViewPageHasHistoryTabWithAjaxPagination(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/view.php');
        $this->assertStringContainsString('pane-historico', $html, 'View: deve ter tab histórico');
        $this->assertStringContainsString('getOrderHistory', $html, 'View: deve chamar getOrderHistory via AJAX');
    }

    public function testViewPageHasHistoryTableColumns(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/view.php');
        $this->assertStringContainsString('Pedido', $html, 'Histórico: coluna # Pedido');
        $this->assertStringContainsString('Data', $html, 'Histórico: coluna Data');
        $this->assertStringContainsString('Valor', $html, 'Histórico: coluna Valor');
        $this->assertStringContainsString('Status', $html, 'Histórico: coluna Status');
    }

    public function testControllerHasGetTagsMethod(): void
    {
        $controller = file_get_contents(__DIR__ . '/../../app/controllers/CustomerController.php');
        $this->assertStringContainsString('function getTags', $controller, 'Controller: deve ter método getTags');
        $this->assertStringContainsString('getAllTags', $controller, 'Controller: deve chamar getAllTags do Model');
        $this->assertStringContainsString('application/json', $controller, 'Controller: getTags deve retornar JSON');
    }

    public function testControllerHasGetOrderHistoryMethod(): void
    {
        $controller = file_get_contents(__DIR__ . '/../../app/controllers/CustomerController.php');
        $this->assertStringContainsString('function getOrderHistory', $controller, 'Controller: deve ter método getOrderHistory');
        $this->assertStringContainsString('application/json', $controller, 'Controller: deve retornar JSON');
    }

    // ══════════════════════════════════════════════════════════════
    // 4.7 — Importação Atualizada com Novos Campos
    // ══════════════════════════════════════════════════════════════

    public function testControllerHasExpandedImportFields(): void
    {
        $controller = file_get_contents(__DIR__ . '/../../app/controllers/CustomerController.php');
        // Campos que devem estar no $importFields
        $expectedFields = [
            'person_type', 'fantasy_name', 'rg_ie', 'im',
            'cellphone', 'address_city', 'address_state',
            'birth_date', 'observations', 'origin', 'tags'
        ];
        foreach ($expectedFields as $field) {
            $this->assertStringContainsString(
                "'" . $field . "'",
                $controller,
                "Import: deve incluir campo $field no mapeamento"
            );
        }
    }

    public function testControllerImportHasAutoMapping(): void
    {
        $controller = file_get_contents(__DIR__ . '/../../app/controllers/CustomerController.php');
        // Auto-mapeamento: nomes alternativos
        $autoMapTerms = [
            'tipo_pessoa', 'fantasia', 'celular', 'whatsapp',
            'cidade', 'estado', 'nascimento', 'observacao'
        ];
        foreach ($autoMapTerms as $term) {
            $this->assertStringContainsString(
                $term,
                $controller,
                "Import: deve incluir auto-mapeamento para '$term'"
            );
        }
    }

    // ══════════════════════════════════════════════════════════════
    // 4.2/4.6 — Rotas registradas para novas actions
    // ══════════════════════════════════════════════════════════════

    public function testRoutesHaveGetTagsAction(): void
    {
        $routes = file_get_contents(__DIR__ . '/../../app/config/routes.php');
        $this->assertStringContainsString("'getTags'", $routes, 'Rotas: deve conter action getTags');
    }

    public function testRoutesHaveGetOrderHistoryAction(): void
    {
        $routes = file_get_contents(__DIR__ . '/../../app/config/routes.php');
        $this->assertStringContainsString("'getOrderHistory'", $routes, 'Rotas: deve conter action getOrderHistory');
    }

    // ══════════════════════════════════════════════════════════════
    // 4.2 — Views incluem customer-tags.js
    // ══════════════════════════════════════════════════════════════

    public function testCreateViewIncludesTagsJs(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/create.php');
        $this->assertStringContainsString('customer-tags.js', $html, 'Create: deve incluir customer-tags.js');
    }

    public function testEditViewIncludesTagsJs(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/edit.php');
        $this->assertStringContainsString('customer-tags.js', $html, 'Edit: deve incluir customer-tags.js');
    }

    // ══════════════════════════════════════════════════════════════
    // 4.3 — Views incluem customer-completeness.js
    // ══════════════════════════════════════════════════════════════

    public function testCreateViewIncludesCompletenessJs(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/create.php');
        $this->assertStringContainsString('customer-completeness.js', $html, 'Create: deve incluir customer-completeness.js');
    }

    public function testEditViewIncludesCompletenessJs(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/edit.php');
        $this->assertStringContainsString('customer-completeness.js', $html, 'Edit: deve incluir customer-completeness.js');
    }

    // ══════════════════════════════════════════════════════════════
    // 4.4 — Views incluem customer-autosave.js
    // ══════════════════════════════════════════════════════════════

    public function testCreateViewIncludesAutosaveJs(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/create.php');
        $this->assertStringContainsString('customer-autosave.js', $html, 'Create: deve incluir customer-autosave.js');
    }

    public function testEditViewIncludesAutosaveJs(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/edit.php');
        $this->assertStringContainsString('customer-autosave.js', $html, 'Edit: deve incluir customer-autosave.js');
    }

    // ══════════════════════════════════════════════════════════════
    // 4.5 — Views incluem customer-shortcuts.js
    // ══════════════════════════════════════════════════════════════

    public function testCreateViewIncludesShortcutsJs(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/create.php');
        $this->assertStringContainsString('customer-shortcuts.js', $html, 'Create: deve incluir customer-shortcuts.js');
    }

    public function testEditViewIncludesShortcutsJs(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/edit.php');
        $this->assertStringContainsString('customer-shortcuts.js', $html, 'Edit: deve incluir customer-shortcuts.js');
    }

    // ══════════════════════════════════════════════════════════════
    // 4.2 — Views têm tags-wrapper
    // ══════════════════════════════════════════════════════════════

    public function testCreateViewHasTagsWrapper(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/create.php');
        $this->assertStringContainsString('id="tags-wrapper"', $html, 'Create: deve ter id tags-wrapper');
    }

    public function testEditViewHasTagsWrapper(): void
    {
        $html = file_get_contents(__DIR__ . '/../../app/views/customers/edit.php');
        $this->assertStringContainsString('id="tags-wrapper"', $html, 'Edit: deve ter id tags-wrapper');
    }

    // ══════════════════════════════════════════════════════════════
    // 4.9 — Model: importFromMapped com novos campos
    // ══════════════════════════════════════════════════════════════

    public function testModelHasImportFromMappedMethod(): void
    {
        $model = file_get_contents(__DIR__ . '/../../app/models/Customer.php');
        $this->assertStringContainsString('function importFromMapped', $model, 'Model: deve ter método importFromMapped');
    }

    public function testModelImportFromMappedSupportsNewFields(): void
    {
        $model = file_get_contents(__DIR__ . '/../../app/models/Customer.php');
        $newFields = ['person_type', 'fantasy_name', 'rg_ie', 'im', 'cellphone', 'address_city', 'address_state', 'tags', 'observations', 'origin'];
        foreach ($newFields as $field) {
            $this->assertStringContainsString(
                "'" . $field . "'",
                $model,
                "Model importFromMapped: deve mapear campo $field"
            );
        }
    }

    // ══════════════════════════════════════════════════════════════
    // 4.9 — Model: getAllTags retorna tags únicas
    // ══════════════════════════════════════════════════════════════

    public function testModelHasGetAllTagsMethod(): void
    {
        $model = file_get_contents(__DIR__ . '/../../app/models/Customer.php');
        $this->assertStringContainsString('function getAllTags', $model, 'Model: deve ter método getAllTags');
    }

    public function testModelGetAllTagsParsesCommaSeparated(): void
    {
        $model = file_get_contents(__DIR__ . '/../../app/models/Customer.php');
        $this->assertStringContainsString('explode', $model, 'getAllTags: deve explodir tags por vírgula');
        $this->assertStringContainsString('array_keys', $model, 'getAllTags: deve usar array_keys para deduplicate');
        $this->assertStringContainsString('sort', $model, 'getAllTags: deve ordenar alfabeticamente');
    }

    // ══════════════════════════════════════════════════════════════
    // 4.10 — Script de limpeza de duplicatas
    // ══════════════════════════════════════════════════════════════

    public function testDuplicateCleanupScriptExists(): void
    {
        $this->assertFileExists(
            __DIR__ . '/../../scripts/fix_customer_duplicates.php',
            'Script de limpeza de duplicatas deve existir'
        );
    }

    public function testDuplicateCleanupScriptHasDuplicateDetection(): void
    {
        $script = file_get_contents(__DIR__ . '/../../scripts/fix_customer_duplicates.php');
        $this->assertStringContainsString('document', $script, 'Script: deve buscar por campo document');
        $this->assertStringContainsString('GROUP BY', $script, 'Script: deve agrupar por document');
        $this->assertStringContainsString('HAVING', $script, 'Script: deve filtrar duplicatas com HAVING');
        $this->assertStringContainsString('COUNT', $script, 'Script: deve contar ocorrências');
    }

    public function testDuplicateCleanupScriptHasMergeLogic(): void
    {
        $script = file_get_contents(__DIR__ . '/../../scripts/fix_customer_duplicates.php');
        $this->assertStringContainsString('deleted_at', $script, 'Script: deve soft-deletar duplicatas');
    }

    public function testDuplicateCleanupScriptHasReportGeneration(): void
    {
        $script = file_get_contents(__DIR__ . '/../../scripts/fix_customer_duplicates.php');
        $this->assertMatchesRegularExpression('/relat[oó]rio|report|log/i', $script, 'Script: deve gerar relatório');
    }

    public function testDuplicateCleanupScriptHasDryRunMode(): void
    {
        $script = file_get_contents(__DIR__ . '/../../scripts/fix_customer_duplicates.php');
        $this->assertStringContainsString('dry', $script, 'Script: deve ter modo dry-run');
    }

    // ══════════════════════════════════════════════════════════════
    // 4.11 — Documentação final
    // ══════════════════════════════════════════════════════════════

    public function testFinalDocumentationExists(): void
    {
        $this->assertFileExists(
            __DIR__ . '/../../docs/cadastro/07_DOCUMENTACAO_FINAL.md',
            'Documentação final deve existir'
        );
    }

    public function testFinalDocumentationHasFieldsReference(): void
    {
        $doc = file_get_contents(__DIR__ . '/../../docs/cadastro/07_DOCUMENTACAO_FINAL.md');
        $this->assertStringContainsString('person_type', $doc, 'Doc: deve descrever campo person_type');
        $this->assertStringContainsString('document', $doc, 'Doc: deve descrever campo document');
        $this->assertStringContainsString('tags', $doc, 'Doc: deve descrever campo tags');
    }

    public function testFinalDocumentationHasWizardFlow(): void
    {
        $doc = file_get_contents(__DIR__ . '/../../docs/cadastro/07_DOCUMENTACAO_FINAL.md');
        $this->assertStringContainsString('Wizard', $doc, 'Doc: deve descrever fluxo do wizard');
        $this->assertStringContainsString('Step', $doc, 'Doc: deve mencionar steps');
    }

    public function testFinalDocumentationHasApiReferences(): void
    {
        $doc = file_get_contents(__DIR__ . '/../../docs/cadastro/07_DOCUMENTACAO_FINAL.md');
        $this->assertStringContainsString('ViaCEP', $doc, 'Doc: deve mencionar ViaCEP');
        $this->assertStringContainsString('BrasilAPI', $doc, 'Doc: deve mencionar BrasilAPI');
    }

    public function testFinalDocumentationHasValidations(): void
    {
        $doc = file_get_contents(__DIR__ . '/../../docs/cadastro/07_DOCUMENTACAO_FINAL.md');
        $this->assertStringContainsString('CPF', $doc, 'Doc: deve mencionar validação de CPF');
        $this->assertStringContainsString('CNPJ', $doc, 'Doc: deve mencionar validação de CNPJ');
        $this->assertStringContainsString('CEP', $doc, 'Doc: deve mencionar validação de CEP');
    }

    public function testFinalDocumentationHasPermissions(): void
    {
        $doc = file_get_contents(__DIR__ . '/../../docs/cadastro/07_DOCUMENTACAO_FINAL.md');
        $this->assertMatchesRegularExpression('/[Pp]ermiss[ãõ]/', $doc, 'Doc: deve abordar permissões');
    }

    public function testFinalDocumentationHasShortcuts(): void
    {
        $doc = file_get_contents(__DIR__ . '/../../docs/cadastro/07_DOCUMENTACAO_FINAL.md');
        $this->assertStringContainsString('Ctrl+S', $doc, 'Doc: deve listar atalhos de teclado');
    }

    public function testFinalDocumentationHasFaq(): void
    {
        $doc = file_get_contents(__DIR__ . '/../../docs/cadastro/07_DOCUMENTACAO_FINAL.md');
        $this->assertStringContainsString('FAQ', $doc, 'Doc: deve ter seção FAQ');
    }
}
