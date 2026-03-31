<?php
namespace Akti\Services;

use Akti\Models\CompanySettings;
use Akti\Models\Logger;
use Akti\Models\DashboardWidget;
use Akti\Models\UserGroup;
use Akti\Models\PreparationStep;
use Akti\Core\ModuleBootloader;
use PDO;
use TenantManager;

/**
 * SettingsService — Lógica de negócio para configurações do sistema.
 *
 * Extraído do SettingsController na Fase 2 para manter o controller slim.
 * Concentra: upload de logo, geração de step keys, salvamento de configurações
 * bancárias/fiscais/segurança, e gerenciamento de widgets do dashboard.
 *
 * @package Akti\Services
 */
class SettingsService
{
    private PDO $db;
    private CompanySettings $companySettings;
    private Logger $logger;

    public function __construct(PDO $db, CompanySettings $companySettings)
    {
        $this->db = $db;
        $this->companySettings = $companySettings;
        $this->logger = new Logger($db);
    }

    // ═══════════════════════════════════════════
    // CONFIGURAÇÕES DA EMPRESA
    // ═══════════════════════════════════════════

    /**
     * Salva configurações da empresa a partir de um array de chave => valor.
     */
    public function saveCompanySettings(array $data): void
    {
        $keys = [
            'company_name', 'company_document', 'company_phone', 'company_email',
            'company_website', 'company_zipcode', 'company_address_type',
            'company_address_name', 'company_address_number', 'company_neighborhood',
            'company_complement', 'company_city', 'company_state',
            'quote_validity_days', 'quote_footer_note'
        ];

        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                $this->companySettings->set($key, $data[$key]);
            }
        }

        $this->logger->log('SETTINGS_UPDATE', 'Configurações da empresa atualizadas');
    }

    /**
     * Processa upload do logo da empresa.
     *
     * @param array $file Dados do $_FILES['company_logo']
     * @return bool Se o upload foi bem-sucedido
     */
    public function handleLogoUpload(array $file): bool
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }

        $uploadDir = TenantManager::getTenantUploadBase();
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'company_logo_' . time() . '.' . $ext;
        $filepath = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            $this->removeOldLogo();
            $this->companySettings->set('company_logo', $filepath);
            return true;
        }

        return false;
    }

    /**
     * Remove o logo da empresa.
     */
    public function removeLogo(): void
    {
        $this->removeOldLogo();
        $this->companySettings->set('company_logo', '');
    }

    /**
     * Remove o arquivo do logo antigo do disco.
     */
    private function removeOldLogo(): void
    {
        $oldLogo = $this->companySettings->get('company_logo');
        if ($oldLogo && file_exists($oldLogo)) {
            unlink($oldLogo);
        }
    }

    // ═══════════════════════════════════════════
    // CONFIGURAÇÕES BANCÁRIAS / BOLETO
    // ═══════════════════════════════════════════

    /**
     * Salva configurações bancárias/boleto.
     *
     * @param array $data Dados do formulário
     * @return array ['success' => bool, 'message' => string]
     */
    public function saveBankSettings(array $data): array
    {
        if (!ModuleBootloader::isModuleEnabled('boleto')) {
            return ['success' => false, 'message' => 'Módulo de boleto desativado para este tenant.'];
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

        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                $this->companySettings->set($key, $data[$key]);
            }
        }

        $this->logger->log('SETTINGS_UPDATE', 'Configurações bancárias/boleto atualizadas');
        return ['success' => true, 'message' => 'Configurações bancárias salvas com sucesso.'];
    }

    // ═══════════════════════════════════════════
    // CONFIGURAÇÕES FISCAIS / NF-e
    // ═══════════════════════════════════════════

    /**
     * Salva configurações fiscais da empresa.
     *
     * @param array $data Dados do formulário
     * @return array ['success' => bool, 'message' => string]
     */
    public function saveFiscalSettings(array $data): array
    {
        if (!ModuleBootloader::isModuleEnabled('fiscal')) {
            return ['success' => false, 'message' => 'Módulo fiscal desativado para este tenant.'];
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

        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                $this->companySettings->set($key, $data[$key]);
            }
        }

        $this->logger->log('SETTINGS_UPDATE', 'Configurações fiscais/NF-e atualizadas');
        return ['success' => true, 'message' => 'Configurações fiscais salvas com sucesso.'];
    }

    // ═══════════════════════════════════════════
    // CONFIGURAÇÕES DE SEGURANÇA
    // ═══════════════════════════════════════════

    /**
     * Salva configurações de segurança (timeout de sessão).
     *
     * @param int $timeoutMinutes Timeout em minutos
     * @return int O timeout validado e salvo
     */
    public function saveSecuritySettings(int $timeoutMinutes): int
    {
        // Validação: mínimo 5, máximo 1440 (24h)
        if ($timeoutMinutes < 5) $timeoutMinutes = 5;
        if ($timeoutMinutes > 1440) $timeoutMinutes = 1440;

        $this->companySettings->set('session_timeout_minutes', $timeoutMinutes);

        $this->logger->log('SETTINGS_UPDATE', "Configurações de segurança atualizadas (timeout={$timeoutMinutes}min)");

        return $timeoutMinutes;
    }

    // ═══════════════════════════════════════════
    // ETAPAS DE PREPARO GLOBAIS
    // ═══════════════════════════════════════════

    /**
     * Gera uma chave única a partir do label (slug-like) para preparation_steps.
     *
     * @param string $label Label da etapa
     * @return string Chave única gerada
     */
    public function generateStepKey(string $label): string
    {
        $key = mb_strtolower($label, 'UTF-8');
        $key = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $key);
        $key = preg_replace('/[^a-z0-9]+/', '_', $key);
        $key = trim($key, '_');

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

    // ═══════════════════════════════════════════
    // DASHBOARD WIDGETS
    // ═══════════════════════════════════════════

    /**
     * Salva a configuração de widgets do dashboard para um grupo.
     *
     * @param int $groupId ID do grupo de usuários
     * @param string $widgetsJson JSON com a configuração de widgets
     * @return array ['success' => bool, 'message' => string]
     */
    public function saveDashboardWidgets(int $groupId, string $widgetsJson): array
    {
        if (!$groupId) {
            return ['success' => false, 'message' => 'Grupo não informado.'];
        }

        $widgets = json_decode($widgetsJson, true);
        if (!is_array($widgets)) {
            return ['success' => false, 'message' => 'Dados de widgets inválidos.'];
        }

        $dashWidgetModel = new DashboardWidget($this->db);
        $result = $dashWidgetModel->saveForGroup($groupId, $widgets);

        if ($result) {
            $this->logger->log('SETTINGS_UPDATE', "Widgets do dashboard atualizados para o grupo #{$groupId}");
            return ['success' => true];
        }

        return ['success' => false, 'message' => 'Erro ao salvar configuração.'];
    }

    /**
     * Reseta a configuração de widgets do dashboard para o padrão global.
     *
     * @param int $groupId ID do grupo de usuários
     * @return array ['success' => bool, 'message' => string]
     */
    public function resetDashboardWidgets(int $groupId): array
    {
        if (!$groupId) {
            return ['success' => false, 'message' => 'Grupo não informado.'];
        }

        $dashWidgetModel = new DashboardWidget($this->db);
        $result = $dashWidgetModel->resetGroup($groupId);

        if ($result) {
            $this->logger->log('SETTINGS_UPDATE', "Widgets do dashboard resetados para o grupo #{$groupId}");
            return ['success' => true];
        }

        return ['success' => false, 'message' => 'Erro ao restaurar padrão.'];
    }
}
