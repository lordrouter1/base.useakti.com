<?php
namespace Akti\Services;

use Akti\Models\NfeBackupService as NfeBackupServiceModel;
use PDO;

/**
 * Service: NfeBackupManagementService
 * Gerencia backup de XMLs, histórico e configurações de backup.
 */
class NfeBackupManagementService
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Executa backup de XMLs no período/tipo informado.
     *
     * @throws \Throwable
     */
    public function executeBackup(string $startDate, string $endDate, string $tipo): array
    {
        if (!in_array($tipo, ['local', 's3', 'ftp'])) {
            return ['success' => false, 'message' => 'Tipo de backup inválido.'];
        }

        $backupService = new \Akti\Services\NfeBackupService($this->db);
        return $backupService->execute($startDate, $endDate, $tipo);
    }

    /**
     * Retorna histórico de backups.
     */
    public function getHistory(int $limit = 0): array
    {
        $backupService = new \Akti\Services\NfeBackupService($this->db);
        return $limit > 0 ? $backupService->getHistory($limit) : $backupService->getHistory();
    }

    /**
     * Carrega configurações de backup do banco.
     */
    public function loadConfig(): array
    {
        $config = [];
        try {
            $stmt = $this->db->query("SELECT config_key, config_value FROM nfe_fiscal_config WHERE config_key LIKE 'backup_%'");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $config[$row['config_key']] = $row['config_value'];
            }
        } catch (\Throwable $e) {
            // tabela pode não existir
        }
        return $config;
    }

    /**
     * Salva configurações de backup.
     *
     * @throws \Throwable
     */
    public function saveConfig(array $configs): void
    {
        foreach ($configs as $key => $value) {
            $stmt = $this->db->prepare(
                "INSERT INTO nfe_fiscal_config (config_key, config_value) VALUES (:key, :val)
                 ON DUPLICATE KEY UPDATE config_value = :val2, updated_at = NOW()"
            );
            $stmt->execute([':key' => $key, ':val' => (string) $value, ':val2' => (string) $value]);
        }
    }
}
