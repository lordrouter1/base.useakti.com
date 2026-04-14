<?php
namespace Akti\Controllers;

use Akti\Models\CompanySettings;
use Akti\Models\PriceTable;
use Akti\Models\Product;
use Akti\Models\PreparationStep;
use Akti\Models\DashboardWidget;
use Akti\Models\UserGroup;
use Akti\Core\ModuleBootloader;
use Akti\Utils\Input;
use Akti\Services\SettingsService;
use TenantManager;

class SettingsController extends BaseController {
    private CompanySettings $companySettings;
    private PriceTable $priceTable;
    private PreparationStep $preparationStep;
    private SettingsService $settingsService;

    public function __construct(
        \PDO $db,
        CompanySettings $companySettings,
        PriceTable $priceTable,
        PreparationStep $preparationStep,
        SettingsService $settingsService
    ) {
        $this->db = $db;
        $this->companySettings = $companySettings;
        $this->priceTable = $priceTable;
        $this->preparationStep = $preparationStep;
        $this->settingsService = $settingsService;
    }

    // ──────── CONFIGURAÇÕES DA EMPRESA ────────

    /**
     * Página de configurações da empresa
     */
    public function index() {
        $rawTab = Input::get('tab', 'string', 'company');
        $safeTab = ModuleBootloader::sanitizeSettingsTab($rawTab);
        if ($rawTab !== $safeTab) {
            header('Location: ?page=settings&tab=' . $safeTab);
            exit;
        }

        $settings = $this->companySettings->getAll();
        $priceTables = $this->priceTable->readAll();
        $preparationSteps = $this->preparationStep->getAll();

        // Verificar limite de tabelas de preço do tenant (para aba de tabelas)
        $maxPriceTables = TenantManager::getTenantLimit('max_price_tables');
        $currentPriceTables = $this->priceTable->countAll();
        $priceTableLimitReached = ($maxPriceTables !== null && $currentPriceTables >= $maxPriceTables);
        $priceTableLimitInfo = $priceTableLimitReached ? ['current' => $currentPriceTables, 'max' => $maxPriceTables] : null;

        // ── Dados para aba Dashboard Widgets ──
        $dashGroups = [];
        $dashSelectedGroupId = null;
        $dashGroupConfig = [];
        $dashAvailableWidgets = DashboardWidget::getAvailableWidgets();
        $dashHasCustomConfig = false;

        if ($safeTab === 'dashboard') {
            $userGroupModel = new UserGroup($this->db);
            $dashGroups = $userGroupModel->readAll();

            $dashSelectedGroupId = Input::get('group_id', 'int');

            if ($dashSelectedGroupId) {
                $dashWidgetModel = new DashboardWidget($this->db);
                $dashGroupConfig = $dashWidgetModel->getByGroup($dashSelectedGroupId);
                $dashHasCustomConfig = $dashWidgetModel->hasConfig($dashSelectedGroupId);
            }
        }

        require 'app/views/layout/header.php';
        require 'app/views/settings/index.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * Salvar configurações da empresa (POST)
     */
    public function saveCompany() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=settings');
            exit;
        }

        // Coletar dados do formulário
        $data = [];
        $keys = [
            'company_name', 'company_document', 'company_phone', 'company_email',
            'company_website', 'company_zipcode', 'company_address_type',
            'company_address_name', 'company_address_number', 'company_neighborhood',
            'company_complement', 'company_city', 'company_state',
            'quote_validity_days', 'quote_footer_note'
        ];
        foreach ($keys as $key) {
            if (Input::hasPost($key)) {
                $data[$key] = Input::post($key);
            }
        }

        $this->settingsService->saveCompanySettings($data);

        // Upload de logo
        if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
            $this->settingsService->handleLogoUpload($_FILES['company_logo']);
        }

        // Remover logo se checkbox marcado
        if (Input::post('remove_logo') === '1') {
            $this->settingsService->removeLogo();
        }

        header('Location: ?page=settings&status=saved');
        exit;
    }

    // ──────── CONFIGURAÇÕES BANCÁRIAS / BOLETO ────────

    /**
     * Salvar configurações bancárias para boletos (POST)
     */
    public function saveBankSettings() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=settings&tab=boleto');
            exit;
        }

        $keys = [
            'boleto_banco', 'boleto_agencia', 'boleto_agencia_dv',
            'boleto_conta', 'boleto_conta_dv', 'boleto_carteira',
            'boleto_especie', 'boleto_cedente', 'boleto_cedente_documento',
            'boleto_convenio', 'boleto_nosso_numero', 'boleto_nosso_numero_digitos',
            'boleto_instrucoes', 'boleto_multa', 'boleto_juros',
            'boleto_aceite', 'boleto_especie_doc', 'boleto_demonstrativo',
            'boleto_local_pagamento', 'boleto_cedente_endereco',
            'mercadopago_access_token', 'mercadopago_public_key',
        ];

        $data = [];
        foreach ($keys as $key) {
            if (Input::hasPost($key)) {
                $data[$key] = Input::post($key);
            }
        }

        $result = $this->settingsService->saveBankSettings($data);

        if (!$result['success']) {
            $_SESSION['flash_error'] = $result['message'];
            header('Location: ?page=settings');
            exit;
        }

        header('Location: ?page=settings&tab=boleto&status=saved');
        exit;
    }

    // ──────── TABELAS DE PREÇO ────────

    /**
     * Página dedicada de Tabelas de Preço (menu principal)
     */
    public function priceTablesIndex() {
        $priceTables = $this->priceTable->readAll();

        // Verificar limite de tabelas de preço do tenant
        $maxPriceTables = TenantManager::getTenantLimit('max_price_tables');
        $currentPriceTables = $this->priceTable->countAll();
        $limitReached = ($maxPriceTables !== null && $currentPriceTables >= $maxPriceTables);
        $limitInfo = $limitReached ? ['current' => $currentPriceTables, 'max' => $maxPriceTables] : null;

        require 'app/views/layout/header.php';
        require 'app/views/settings/price_tables_index.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * Criar tabela de preço (POST)
     */
    public function createPriceTable() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = Input::post('name');
            $description = Input::post('description');
            $refPage = Input::post('ref_page', 'string', 'settings');

            // Verificar limite de tabelas de preço do tenant
            $maxPriceTables = TenantManager::getTenantLimit('max_price_tables');
            if ($maxPriceTables !== null) {
                $currentPriceTables = $this->priceTable->countAll();
                if ($currentPriceTables >= $maxPriceTables) {
                    if ($refPage === 'price_tables') {
                        header('Location: ?page=price_tables&status=limit_price_tables');
                    } else {
                        header('Location: ?page=settings&tab=prices&status=limit_price_tables');
                    }
                    exit;
                }
            }

            if ($name) {
                $this->priceTable->create($name, $description);
            }
            if ($refPage === 'price_tables') {
                header('Location: ?page=price_tables&status=table_created');
            } else {
                header('Location: ?page=settings&tab=prices&status=table_created');
            }
            exit;
        }
    }

    /**
     * Atualizar tabela de preço (POST)
     */
    public function updatePriceTable() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = Input::post('id', 'int');
            $name = Input::post('name');
            $description = Input::post('description');
            if ($id && $name) {
                $this->priceTable->update($id, $name, $description);
            }
            header('Location: ?page=settings&tab=prices&status=table_updated');
            exit;
        }
    }

    /**
     * Excluir tabela de preço
     */
    public function deletePriceTable() {
        $id = Input::get('id', 'int');
        $refPage = Input::get('ref', 'string', 'settings');
        if ($id) {
            $result = $this->priceTable->delete($id);
            $status = $result ? 'table_deleted' : 'table_default_error';
        }
        if ($refPage === 'price_tables') {
            header('Location: ?page=price_tables&status=' . ($status ?? 'error'));
        } else {
            header('Location: ?page=settings&tab=prices&status=' . ($status ?? 'error'));
        }
        exit;
    }

    /**
     * Editar itens de uma tabela de preço
     */
    public function editPriceTable() {
        $id = Input::get('id', 'int');
        $refPage = Input::get('ref', 'string', 'settings');
        if (!$id) {
            header('Location: ?page=' . ($refPage === 'price_tables' ? 'price_tables' : 'settings&tab=prices'));
            exit;
        }

        $table = $this->priceTable->readOne($id);
        if (!$table) {
            header('Location: ?page=' . ($refPage === 'price_tables' ? 'price_tables' : 'settings&tab=prices'));
            exit;
        }

        $items = $this->priceTable->getItems($id);
        
        $productModel = new Product($this->db);
        $products = $productModel->readAll();

        // Criar mapa de produtos já na tabela
        $existingProducts = [];
        foreach ($items as $item) {
            $existingProducts[$item['product_id']] = true;
        }

        require 'app/views/layout/header.php';
        require 'app/views/settings/price_table_edit.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * Adicionar/atualizar item na tabela de preço (POST)
     */
    public function savePriceItem() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $tableId = Input::post('price_table_id', 'int');
            $productId = Input::post('product_id', 'int');
            $price = Input::post('price', 'float', 0);
            $refPage = Input::post('ref_page', 'string', 'settings');

            if ($tableId && $productId) {
                $this->priceTable->setItemPrice($tableId, $productId, $price);
            }
            $basePage = ($refPage === 'price_tables') ? 'price_tables' : 'settings';
            header('Location: ?page=' . $basePage . '&action=editPriceTable&id=' . $tableId . '&ref=' . $refPage . '&status=item_saved');
            exit;
        }
    }

    /**
     * Remover item da tabela de preço
     */
    public function deletePriceItem() {
        $itemId = Input::get('item_id', 'int');
        $tableId = Input::get('table_id', 'int');
        $refPage = Input::get('ref', 'string', 'settings');

        if ($itemId) {
            $this->priceTable->removeItem($itemId);
        }
        $basePage = ($refPage === 'price_tables') ? 'price_tables' : 'settings';
        header('Location: ?page=' . $basePage . '&action=editPriceTable&id=' . $tableId . '&ref=' . $refPage . '&status=item_deleted');
        exit;
    }

    /**
     * API: Retorna preços para um cliente (AJAX/JSON)
     */
    public function getPricesForCustomer() {
        $customerId = Input::get('customer_id', 'int');
        $prices = $this->priceTable->getAllPricesForCustomer($customerId);
        header('Content-Type: application/json');
        $this->json($prices);}

    // ──────── ETAPAS DE PREPARO GLOBAIS ────────

    /**
     * Adicionar nova etapa de preparo (POST)
     */
    public function addPreparationStep() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $label = Input::post('label');
            $description = Input::post('description');
            $icon = Input::post('icon', 'string', 'fas fa-check');
            $sortOrder = Input::post('sort_order', 'int', 0);

            if ($label) {
                $key = $this->settingsService->generateStepKey($label);
                $this->preparationStep->add($key, $label, $description, $icon, $sortOrder);
            }

            header('Location: ?page=settings&tab=preparation&status=step_added');
            exit;
        }
    }

    /**
     * Atualizar etapa de preparo (POST)
     */
    public function updatePreparationStep() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = Input::post('id', 'int');
            $label = Input::post('label');
            $description = Input::post('description');
            $icon = Input::post('icon', 'string', 'fas fa-check');
            $sortOrder = Input::post('sort_order', 'int', 0);
            $isActive = Input::post('is_active', 'bool') ? 1 : 0;

            if ($id && $label) {
                $this->preparationStep->update($id, $label, $description, $icon, $sortOrder, $isActive);
            }

            header('Location: ?page=settings&tab=preparation&status=step_updated');
            exit;
        }
    }

    /**
     * Excluir etapa de preparo
     */
    public function deletePreparationStep() {
        $id = Input::get('id', 'int');
        if ($id) {
            $this->preparationStep->delete($id);
        }
        header('Location: ?page=settings&tab=preparation&status=step_deleted');
        exit;
    }

    /**
     * Ativar/desativar etapa de preparo (AJAX)
     */
    public function togglePreparationStep() {
        header('Content-Type: application/json');
        $id = Input::post('id', 'int');
        if ($id) {
            $this->preparationStep->toggleActive($id);
            $this->json(['success' => true]);
        } else {
            $this->json(['success' => false, 'message' => 'ID não informado']);
        }
        exit;
    }

    // ──────── CONFIGURAÇÕES FISCAIS / NF-e ────────

    /**
     * Salvar configurações fiscais da empresa (POST)
     */
    public function saveFiscalSettings() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=settings&tab=fiscal');
            exit;
        }

        $keys = [
            'fiscal_razao_social', 'fiscal_nome_fantasia', 'fiscal_cnpj',
            'fiscal_ie', 'fiscal_im', 'fiscal_cnae', 'fiscal_crt',
            'fiscal_endereco_logradouro', 'fiscal_endereco_numero', 'fiscal_endereco_complemento',
            'fiscal_endereco_bairro', 'fiscal_endereco_cidade', 'fiscal_endereco_uf',
            'fiscal_endereco_cep', 'fiscal_endereco_cod_municipio',
            'fiscal_endereco_cod_pais', 'fiscal_endereco_pais', 'fiscal_endereco_fone',
            'fiscal_certificado_tipo', 'fiscal_certificado_senha', 'fiscal_certificado_validade',
            'fiscal_ambiente', 'fiscal_serie_nfe', 'fiscal_proximo_numero_nfe',
            'fiscal_modelo_nfe', 'fiscal_tipo_emissao', 'fiscal_finalidade',
            'fiscal_aliq_icms_padrao', 'fiscal_aliq_pis_padrao',
            'fiscal_aliq_cofins_padrao', 'fiscal_aliq_iss_padrao',
            'fiscal_nat_operacao', 'fiscal_info_complementar',
        ];

        $data = [];
        foreach ($keys as $key) {
            if (Input::hasPost($key)) {
                $data[$key] = Input::post($key);
            }
        }

        $result = $this->settingsService->saveFiscalSettings($data);

        if (!$result['success']) {
            $_SESSION['flash_error'] = $result['message'];
            header('Location: ?page=settings');
            exit;
        }

        header('Location: ?page=settings&tab=fiscal&status=saved');
        exit;
    }

    // ──────── CONFIGURAÇÕES DE SEGURANÇA ────────

    /**
     * Salvar configurações de segurança (POST)
     */
    public function saveSecuritySettings() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=settings&tab=security');
            exit;
        }

        $timeout = Input::post('session_timeout_minutes', 'int', 60);
        $validatedTimeout = $this->settingsService->saveSecuritySettings($timeout);

        // Limpar cache de timeout na sessão para refletir imediatamente
        unset($_SESSION['_session_timeout_minutes'], $_SESSION['_session_timeout_cached_at']);

        header('Location: ?page=settings&tab=security&status=saved');
        exit;
    }

    // ──────── DASHBOARD WIDGETS ────────

    /**
     * Salvar configuração de widgets do dashboard para um grupo (AJAX/JSON)
     */
    public function saveDashboardWidgets() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método não permitido.']);}

        $groupId = Input::post('group_id', 'int');
        $widgetsJson = Input::post('widgets');

        $result = $this->settingsService->saveDashboardWidgets($groupId, $widgetsJson ?? '');
        $this->json($result);}

    /**
     * Resetar configuração de widgets de um grupo para o padrão global (AJAX/JSON)
     */
    public function resetDashboardWidgets() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método não permitido.']);}

        $groupId = Input::post('group_id', 'int');
        $result = $this->settingsService->resetDashboardWidgets($groupId);
        $this->json($result);}

}
