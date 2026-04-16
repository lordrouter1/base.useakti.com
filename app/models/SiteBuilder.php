<?php
namespace Akti\Models;

/**
 * Model para o Site Builder.
 *
 * Gerencia configurações de tema e conteúdo das páginas fixas
 * da loja online do tenant. Usa tabela sb_theme_settings (key-value).
 */
class SiteBuilder
{
    private $db;

    /**
     * Construtor da classe SiteBuilder.
     *
     * @param \PDO $db Conexão PDO com o banco de dados
     */
    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Obtém todas as configurações do tenant (tema + conteúdo de páginas).
     *
     * @return array<string, string> Mapa key => value
     */
    public function getSettings(int $tenantId): array
    {
        $stmt = $this->db->prepare(
            'SELECT setting_key, setting_value FROM sb_theme_settings WHERE tenant_id = :tid'
        );
        $stmt->execute([':tid' => $tenantId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }

    /**
     * Obtém configurações filtradas por grupo.
     *
     * @return array<string, string> Mapa key => value
     */
    public function getSettingsByGroup(int $tenantId, string $group): array
    {
        $stmt = $this->db->prepare(
            'SELECT setting_key, setting_value
             FROM sb_theme_settings
             WHERE tenant_id = :tid AND setting_group = :grp'
        );
        $stmt->execute([':tid' => $tenantId, ':grp' => $group]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }

    /**
     * Obtém o valor de uma configuração específica.
     */
    public function getSetting(int $tenantId, string $key): ?string
    {
        $stmt = $this->db->prepare(
            'SELECT setting_value FROM sb_theme_settings WHERE tenant_id = :tid AND setting_key = :key'
        );
        $stmt->execute([':tid' => $tenantId, ':key' => $key]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? $row['setting_value'] : null;
    }

    /**
     * Salva uma configuração (insert ou update via UPSERT).
     */
    public function saveSetting(int $tenantId, string $key, string $value, string $group = 'general'): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO sb_theme_settings (tenant_id, setting_key, setting_value, setting_group)
             VALUES (:tid, :key, :val, :grp)
             ON DUPLICATE KEY UPDATE setting_value = :val2, setting_group = :grp2'
        );
        return $stmt->execute([
            ':tid'  => $tenantId,
            ':key'  => $key,
            ':val'  => $value,
            ':grp'  => $group,
            ':val2' => $value,
            ':grp2' => $group,
        ]);
    }

    /**
     * Salva múltiplas configurações de um grupo em transação.
     */
    public function saveSettingsBatch(int $tenantId, array $settings, string $group = 'general'): bool
    {
        try {
            $this->db->beginTransaction();
            foreach ($settings as $key => $value) {
                if (!$this->saveSetting($tenantId, (string) $key, (string) $value, $group)) {
                    $this->db->rollBack();
                    return false;
                }
            }
            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('[ROLLBACK] SiteBuilder::saveSettingsBatch - ' . $e->getMessage());
            return false;
        }
    }

    // Alias para compatibilidade
    /**
     * Obtém dados específicos.
     *
     * @param int $tenantId ID do tenant
     * @return array
     */
    public function getThemeSettings(int $tenantId): array
    {
        return $this->getSettings($tenantId);
    }

    /**
     * Salva dados.
     *
     * @param int $tenantId ID do tenant
     * @param array $settings Configurações
     * @param string $group Group
     * @return bool
     */
    public function saveThemeSettings(int $tenantId, array $settings, string $group = 'general'): bool
    {
        return $this->saveSettingsBatch($tenantId, $settings, $group);
    }
}
