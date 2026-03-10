<?php
namespace Akti\Controllers;

use Akti\Models\CompanySettings;
use Akti\Models\PriceTable;
use Akti\Models\Product;
use Akti\Models\PreparationStep;
use Akti\Models\Logger;
use Akti\Core\ModuleBootloader;
use Database;
use PDO;
use TenantManager;

class SettingsController {

    private $db;
    private $companySettings;
    private $priceTable;
    private $preparationStep;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->companySettings = new CompanySettings($this->db);
        $this->priceTable = new PriceTable($this->db);
        $this->preparationStep = new PreparationStep($this->db);
    }

    // ──────── CONFIGURAÇÕES DA EMPRESA ────────

    /**
     * Página de configurações da empresa
     */
    public function index() {
        $safeTab = ModuleBootloader::sanitizeSettingsTab($_GET['tab'] ?? 'company');
        if (isset($_GET['tab']) && $safeTab !== $_GET['tab']) {
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

        $keys = [
            'company_name', 'company_document', 'company_phone', 'company_email',
            'company_website', 'company_zipcode', 'company_address_type',
            'company_address_name', 'company_address_number', 'company_neighborhood',
            'company_complement', 'company_city', 'company_state',
            'quote_validity_days', 'quote_footer_note'
        ];

        foreach ($keys as $key) {
            if (isset($_POST[$key])) {
                $this->companySettings->set($key, $_POST[$key]);
            }
        }

        // Upload de logo
        if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = TenantManager::getTenantUploadBase();
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            
            $ext = pathinfo($_FILES['company_logo']['name'], PATHINFO_EXTENSION);
            $filename = 'company_logo_' . time() . '.' . $ext;
            $filepath = $uploadDir . $filename;
            
            if (move_uploaded_file($_FILES['company_logo']['tmp_name'], $filepath)) {
                // Remover logo antiga
                $oldLogo = $this->companySettings->get('company_logo');
                if ($oldLogo && file_exists($oldLogo)) {
                    unlink($oldLogo);
                }
                $this->companySettings->set('company_logo', $filepath);
            }
        }

        // Remover logo se checkbox marcado
        if (isset($_POST['remove_logo']) && $_POST['remove_logo'] == '1') {
            $oldLogo = $this->companySettings->get('company_logo');
            if ($oldLogo && file_exists($oldLogo)) {
                unlink($oldLogo);
            }
            $this->companySettings->set('company_logo', '');
        }

        $logger = new Logger($this->db);
        $logger->log('SETTINGS_UPDATE', 'Configurações da empresa atualizadas');

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

        if (!ModuleBootloader::isModuleEnabled('boleto')) {
            $_SESSION['flash_error'] = 'Módulo de boleto desativado para este tenant.';
            header('Location: ?page=settings');
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
        ];

        foreach ($keys as $key) {
            if (isset($_POST[$key])) {
                $this->companySettings->set($key, $_POST[$key]);
            }
        }

        $logger = new Logger($this->db);
        $logger->log('SETTINGS_UPDATE', 'Configurações bancárias/boleto atualizadas');

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
            $name = $_POST['name'] ?? '';
            $description = $_POST['description'] ?? '';
            $refPage = $_POST['ref_page'] ?? 'settings';

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
            $id = $_POST['id'] ?? null;
            $name = $_POST['name'] ?? '';
            $description = $_POST['description'] ?? '';
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
        $id = $_GET['id'] ?? null;
        $refPage = $_GET['ref'] ?? 'settings';
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
        $id = $_GET['id'] ?? null;
        $refPage = $_GET['ref'] ?? 'settings';
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
        $products = $productModel->readAll()->fetchAll(PDO::FETCH_ASSOC);

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
            $tableId = $_POST['price_table_id'] ?? null;
            $productId = $_POST['product_id'] ?? null;
            $price = $_POST['price'] ?? 0;
            $refPage = $_POST['ref_page'] ?? 'settings';

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
        $itemId = $_GET['item_id'] ?? null;
        $tableId = $_GET['table_id'] ?? null;
        $refPage = $_GET['ref'] ?? 'settings';

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
        $customerId = $_GET['customer_id'] ?? null;
        $prices = $this->priceTable->getAllPricesForCustomer($customerId);
        header('Content-Type: application/json');
        echo json_encode($prices);
        exit;
    }

    // ──────── ETAPAS DE PREPARO GLOBAIS ────────

    /**
     * Adicionar nova etapa de preparo (POST)
     */
    public function addPreparationStep() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $label = trim($_POST['label'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $icon = trim($_POST['icon'] ?? 'fas fa-check');
            $sortOrder = (int)($_POST['sort_order'] ?? 0);

            if ($label) {
                // Gerar key a partir do label
                $key = $this->generateStepKey($label);
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
            $id = $_POST['id'] ?? null;
            $label = trim($_POST['label'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $icon = trim($_POST['icon'] ?? 'fas fa-check');
            $sortOrder = (int)($_POST['sort_order'] ?? 0);
            $isActive = isset($_POST['is_active']) ? 1 : 0;

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
        $id = $_GET['id'] ?? null;
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
        $id = $_POST['id'] ?? null;
        if ($id) {
            $this->preparationStep->toggleActive($id);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'ID não informado']);
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

        if (!ModuleBootloader::isModuleEnabled('fiscal')) {
            $_SESSION['flash_error'] = 'Módulo fiscal desativado para este tenant.';
            header('Location: ?page=settings');
            exit;
        }

        $keys = [
            // Identificação
            'fiscal_razao_social', 'fiscal_nome_fantasia', 'fiscal_cnpj',
            'fiscal_ie', 'fiscal_im', 'fiscal_cnae', 'fiscal_crt',
            // Endereço fiscal
            'fiscal_endereco_logradouro', 'fiscal_endereco_numero', 'fiscal_endereco_complemento',
            'fiscal_endereco_bairro', 'fiscal_endereco_cidade', 'fiscal_endereco_uf',
            'fiscal_endereco_cep', 'fiscal_endereco_cod_municipio',
            'fiscal_endereco_cod_pais', 'fiscal_endereco_pais', 'fiscal_endereco_fone',
            // Certificado digital
            'fiscal_certificado_tipo', 'fiscal_certificado_senha', 'fiscal_certificado_validade',
            // NF-e
            'fiscal_ambiente', 'fiscal_serie_nfe', 'fiscal_proximo_numero_nfe',
            'fiscal_modelo_nfe', 'fiscal_tipo_emissao', 'fiscal_finalidade',
            // Alíquotas padrão
            'fiscal_aliq_icms_padrao', 'fiscal_aliq_pis_padrao',
            'fiscal_aliq_cofins_padrao', 'fiscal_aliq_iss_padrao',
            // Natureza e informações complementares
            'fiscal_nat_operacao', 'fiscal_info_complementar',
        ];

        foreach ($keys as $key) {
            if (isset($_POST[$key])) {
                $this->companySettings->set($key, $_POST[$key]);
            }
        }

        $logger = new Logger($this->db);
        $logger->log('SETTINGS_UPDATE', 'Configurações fiscais/NF-e atualizadas');

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

        $timeout = (int) ($_POST['session_timeout_minutes'] ?? 60);
        // Validação: mínimo 5, máximo 1440 (24h)
        if ($timeout < 5) $timeout = 5;
        if ($timeout > 1440) $timeout = 1440;

        $this->companySettings->set('session_timeout_minutes', $timeout);

        // Limpar cache de timeout na sessão para refletir imediatamente
        unset($_SESSION['_session_timeout_minutes'], $_SESSION['_session_timeout_cached_at']);

        $logger = new Logger($this->db);
        $logger->log('SETTINGS_UPDATE', "Configurações de segurança atualizadas (timeout={$timeout}min)");

        header('Location: ?page=settings&tab=security&status=saved');
        exit;
    }

    /**
     * Gerar uma chave a partir do label (slug-like)
     */
    private function generateStepKey($label) {
        $key = mb_strtolower($label, 'UTF-8');
        // Remover acentos
        $key = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $key);
        $key = preg_replace('/[^a-z0-9]+/', '_', $key);
        $key = trim($key, '_');
        // Garantir que não exista
        $base = $key;
        $i = 1;
        while (true) {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM preparation_steps WHERE step_key = :key");
            $stmt->execute([':key' => $key]);
            if ($stmt->fetchColumn() == 0) break;
            $key = $base . '_' . $i;
            $i++;
        }
        return $key;
    }
}
