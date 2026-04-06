<?php
namespace Akti\Services;

use Akti\Core\Log;

use Akti\Models\CompanySettings;
use PDO;

/**
 * NfeDanfeCustomizer — Personalização do DANFE.
 *
 * Opções de personalização:
 *   - Logo da empresa (imagem)
 *   - Rodapé customizado
 *   - Orientação (retrato/paisagem)
 *   - Tamanho do papel
 *
 * @package Akti\Services
 */
class NfeDanfeCustomizer
{
    private PDO $db;
    private CompanySettings $settings;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->settings = new CompanySettings($db);
    }

    /**
     * Gera DANFE personalizado a partir do XML autorizado.
     *
     * @param string $xmlAutorizado
     * @return string|null PDF binário ou null se falha
     */
    public function generate(string $xmlAutorizado): ?string
    {
        if (!class_exists(\NFePHP\DA\NFe\Danfe::class)) {
            return NfePdfGenerator::renderToString($xmlAutorizado);
        }

        try {
            $danfe = new \NFePHP\DA\NFe\Danfe($xmlAutorizado);
            $danfe->debugMode(false);

            // Logo da empresa
            $logoPath = $this->settings->get('nfe_danfe_logo_path', '');
            if (!empty($logoPath) && file_exists($logoPath)) {
                $danfe->logoParameters($logoPath);
            } else {
                // Fallback: logo padrão do sistema
                $companyLogo = $this->settings->get('company_logo', '');
                if (!empty($companyLogo) && file_exists($companyLogo)) {
                    $danfe->logoParameters($companyLogo);
                }
            }

            // Rodapé customizado
            $customFooter = $this->settings->get('nfe_danfe_custom_footer', '');
            if (!empty($customFooter)) {
                $danfe->creditsIntegr498($customFooter);
            } else {
                $danfe->creditsIntegr498('');
            }

            return $danfe->render();

        } catch (\Exception $e) {
            Log::error('NfeDanfeCustomizer: Erro ao gerar DANFE customizado', ['exception' => $e->getMessage()]);
            // Fallback para geração padrão
            return NfePdfGenerator::renderToString($xmlAutorizado);
        }
    }

    /**
     * Salva configurações de personalização do DANFE.
     *
     * @param array $data
     * @return bool
     */
    public function saveSettings(array $data): bool
    {
        $settingsToSave = [];

        if (isset($data['custom_footer'])) {
            $settingsToSave['nfe_danfe_custom_footer'] = $data['custom_footer'];
        }

        if (!empty($settingsToSave)) {
            $this->settings->saveAll($settingsToSave);
        }

        return true;
    }

    /**
     * Faz upload e salva o logo do DANFE.
     *
     * @param array $file $_FILES['danfe_logo']
     * @return array ['success' => bool, 'path' => string|null, 'message' => string]
     */
    public function uploadLogo(array $file): array
    {
        $fileManager = new FileManager($this->db);

        $oldLogo = $this->settings->get('nfe_danfe_logo_path');
        $result = $fileManager->replace($oldLogo ?: null, $file, 'nfe', [
            'prefix'     => 'danfe_logo',
            'entityType' => 'nfe_danfe',
        ]);

        if ($result['success']) {
            $this->settings->set('nfe_danfe_logo_path', $result['path']);
            return ['success' => true, 'path' => $result['path'], 'message' => 'Logo do DANFE atualizado.'];
        }

        return ['success' => false, 'path' => null, 'message' => $result['error'] ?? 'Erro ao salvar arquivo.'];
    }

    /**
     * Retorna configurações atuais.
     * @return array
     */
    public function getSettings(): array
    {
        return [
            'logo_path'     => $this->settings->get('nfe_danfe_logo_path', ''),
            'custom_footer' => $this->settings->get('nfe_danfe_custom_footer', ''),
        ];
    }
}
