<?php
namespace Akti\Services;

use Akti\Core\Log;

use Akti\Models\NfeCredential;
use Akti\Models\NfeDocument;
use PDO;

/**
 * NfeContingencyService — Gerencia ativação/desativação de contingência NF-e.
 *
 * Modos de contingência suportados:
 *   - tpEmis=1: Normal (SEFAZ online)
 *   - tpEmis=6: SVC-AN (Sefaz Virtual de Contingência — Ambiente Nacional)
 *   - tpEmis=7: SVC-RS (Sefaz Virtual de Contingência — Rio Grande do Sul)
 *   - tpEmis=9: Offline NFC-e (contingência offline para modelo 65)
 *
 * Funcionalidades:
 *   - Ativação manual de contingência
 *   - Desativação manual com sincronização
 *   - Detecção automática (auto-detect) quando SEFAZ está offline
 *   - Sincronização de NF-e emitidas em contingência
 *
 * @package Akti\Services
 */
class NfeContingencyService
{
    private PDO $db;
    private NfeCredential $credModel;

    /** @var array Mapeamento de UF → SVC preferencial */
    private const UF_SVC_MAP = [
        // SVC-AN (SEFAZ Virtual Ambiente Nacional — SVCAN): UFs que usam SVC-AN
        'AM' => 6, 'BA' => 6, 'CE' => 6, 'GO' => 6, 'MA' => 6,
        'MS' => 6, 'MT' => 6, 'PA' => 6, 'PE' => 6, 'PI' => 6,
        'PR' => 6,
        // SVC-RS (SEFAZ Virtual Contingência RS — SVCRS): demais UFs
        'AC' => 7, 'AL' => 7, 'AP' => 7, 'DF' => 7, 'ES' => 7,
        'MG' => 7, 'PB' => 7, 'RJ' => 7, 'RN' => 7, 'RO' => 7,
        'RR' => 7, 'RS' => 7, 'SC' => 7, 'SE' => 7, 'SP' => 7,
        'TO' => 7,
    ];

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->credModel = new NfeCredential($db);
    }

    /**
     * Verifica se o sistema está em contingência.
     *
     * @param int|null $credentialId ID da credencial (null = ativa)
     * @return array ['active' => bool, 'type' => int, 'since' => string|null, 'justification' => string|null]
     */
    public function getStatus(?int $credentialId = null): array
    {
        $cred = $this->credModel->get($credentialId);
        if (!$cred) {
            return ['active' => false, 'type' => 1, 'since' => null, 'justification' => null];
        }

        $tpEmis = (int) ($cred['tp_emis'] ?? 1);
        return [
            'active'        => $tpEmis !== 1,
            'type'          => $tpEmis,
            'type_label'    => self::getTpEmisLabel($tpEmis),
            'since'         => $cred['contingencia_ativada_em'] ?? null,
            'justification' => $cred['contingencia_justificativa'] ?? null,
            'auto_enabled'  => (bool) ($cred['contingencia_auto_enabled'] ?? true),
        ];
    }

    /**
     * Ativa contingência manualmente.
     *
     * @param string   $justificativa Justificativa (min 15 caracteres)
     * @param int|null $tpEmis        Tipo de emissão (6=SVC-AN, 7=SVC-RS, 9=Offline NFC-e)
     * @param int|null $credentialId  ID da credencial
     * @return array ['success' => bool, 'message' => string]
     */
    public function activate(string $justificativa, ?int $tpEmis = null, ?int $credentialId = null): array
    {
        if (strlen(trim($justificativa)) < 15) {
            return ['success' => false, 'message' => 'Justificativa deve ter pelo menos 15 caracteres.'];
        }

        $cred = $this->credModel->get($credentialId);
        if (!$cred) {
            return ['success' => false, 'message' => 'Credenciais SEFAZ não encontradas.'];
        }

        $currentTpEmis = (int) ($cred['tp_emis'] ?? 1);
        if ($currentTpEmis !== 1) {
            return ['success' => false, 'message' => 'Sistema já está em contingência (' . self::getTpEmisLabel($currentTpEmis) . ').'];
        }

        // Determinar tipo de contingência
        if ($tpEmis === null) {
            $uf = strtoupper($cred['uf'] ?? 'RS');
            $tpEmis = self::UF_SVC_MAP[$uf] ?? 7;
        }

        if (!in_array($tpEmis, [6, 7, 9])) {
            return ['success' => false, 'message' => 'Tipo de contingência inválido.'];
        }

        // Atualizar credenciais
        $this->credModel->update([
            'tp_emis'                    => $tpEmis,
            'contingencia_justificativa' => $justificativa,
            'contingencia_ativada_em'    => date('Y-m-d H:i:s'),
        ], $cred['id'] ?? null);

        // Registrar log
        $this->logContingency('ativacao', $currentTpEmis, $tpEmis, $justificativa);

        return [
            'success' => true,
            'message' => 'Contingência ativada (' . self::getTpEmisLabel($tpEmis) . ').',
            'type'    => $tpEmis,
        ];
    }

    /**
     * Desativa contingência e inicia sincronização.
     *
     * @param int|null $credentialId ID da credencial
     * @return array ['success' => bool, 'message' => string, 'pending' => int]
     */
    public function deactivate(?int $credentialId = null): array
    {
        $cred = $this->credModel->get($credentialId);
        if (!$cred) {
            return ['success' => false, 'message' => 'Credenciais não encontradas.', 'pending' => 0];
        }

        $currentTpEmis = (int) ($cred['tp_emis'] ?? 1);
        if ($currentTpEmis === 1) {
            return ['success' => false, 'message' => 'Sistema não está em contingência.', 'pending' => 0];
        }

        // Contar NF-e pendentes de sincronização
        $pending = $this->countPendingSync();

        // Atualizar credenciais
        $this->credModel->update([
            'tp_emis'                    => 1,
            'contingencia_justificativa' => null,
            'contingencia_ativada_em'    => null,
        ], $cred['id'] ?? null);

        // Registrar log
        $this->logContingency('desativacao', $currentTpEmis, 1, null, $pending);

        return [
            'success' => true,
            'message' => 'Contingência desativada.' . ($pending > 0 ? " {$pending} NF-e(s) pendente(s) de sincronização." : ''),
            'pending' => $pending,
        ];
    }

    /**
     * Verifica se SEFAZ está online e ativa contingência automaticamente se offline.
     *
     * @return array ['sefaz_online' => bool, 'contingency_activated' => bool, 'message' => string]
     */
    public function autoDetect(): array
    {
        $cred = $this->credModel->get();
        if (!$cred || !((bool) ($cred['contingencia_auto_enabled'] ?? true))) {
            return [
                'sefaz_online'          => true,
                'contingency_activated' => false,
                'message'               => 'Auto-detecção desabilitada.',
            ];
        }

        $currentTpEmis = (int) ($cred['tp_emis'] ?? 1);

        try {
            $nfeService = new NfeService($this->db);
            $statusResult = $nfeService->testConnection();
            $sefazOnline = $statusResult['success'] ?? false;
        } catch (\Throwable $e) {
            $sefazOnline = false;
        }

        // SEFAZ online e em contingência → pode desativar
        if ($sefazOnline && $currentTpEmis !== 1) {
            return [
                'sefaz_online'          => true,
                'contingency_activated' => false,
                'message'               => 'SEFAZ está online. Contingência pode ser desativada manualmente.',
            ];
        }

        // SEFAZ offline e não em contingência → ativar automaticamente
        if (!$sefazOnline && $currentTpEmis === 1) {
            $justificativa = 'Contingência ativada automaticamente - SEFAZ indisponível em ' . date('d/m/Y H:i:s');
            $result = $this->activate($justificativa);

            return [
                'sefaz_online'          => false,
                'contingency_activated' => $result['success'],
                'message'               => $result['message'],
            ];
        }

        return [
            'sefaz_online'          => $sefazOnline,
            'contingency_activated' => false,
            'message'               => $sefazOnline ? 'SEFAZ online, operação normal.' : 'Já em contingência.',
        ];
    }

    /**
     * Sincroniza NF-e emitidas em contingência que ainda não foram sincronizadas.
     *
     * @param int $limit Quantidade máxima a sincronizar por execução
     * @return array ['success' => bool, 'synced' => int, 'failed' => int, 'remaining' => int]
     */
    public function syncPending(int $limit = 10): array
    {
        $docModel = new NfeDocument($this->db);

        // Buscar NF-e emitidas em contingência e não sincronizadas
        $stmt = $this->db->prepare(
            "SELECT id FROM nfe_documents 
             WHERE emitida_contingencia = 1 AND contingencia_sincronizada = 0 
               AND status IN ('autorizada', 'processando')
             ORDER BY created_at ASC LIMIT :lim"
        );
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $pendingDocs = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($pendingDocs)) {
            return ['success' => true, 'synced' => 0, 'failed' => 0, 'remaining' => 0];
        }

        $synced = 0;
        $failed = 0;

        $nfeService = new NfeService($this->db);

        foreach ($pendingDocs as $nfeId) {
            try {
                $result = $nfeService->checkStatus($nfeId);
                if ($result['success'] ?? false) {
                    $docModel->update($nfeId, ['contingencia_sincronizada' => 1]);
                    $synced++;
                } else {
                    $failed++;
                }
            } catch (\Throwable $e) {
                $failed++;
                Log::error('NfeContingencyService: Sync error for NF-e #{$nfeId}', ['detail' => $e->getMessage()]);
            }
        }

        $remaining = $this->countPendingSync();

        // Registrar log
        $this->logContingency('sincronizacao', null, null, null, count($pendingDocs), $synced);

        return [
            'success'   => true,
            'synced'    => $synced,
            'failed'    => $failed,
            'remaining' => $remaining,
        ];
    }

    /**
     * Conta NF-e pendentes de sincronização.
     *
     * @return int
     */
    public function countPendingSync(): int
    {
        try {
            $stmt = $this->db->query(
                "SELECT COUNT(*) FROM nfe_documents WHERE emitida_contingencia = 1 AND contingencia_sincronizada = 0"
            );
            return (int) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Registra evento no log de contingência.
     */
    private function logContingency(
        string $tipo,
        ?int $tpEmisAnterior,
        ?int $tpEmisNovo,
        ?string $justificativa,
        int $nfesPendentes = 0,
        int $nfesSincronizadas = 0
    ): void {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO nfe_contingency_log 
                 (tipo, tp_emis_anterior, tp_emis_novo, justificativa, nfes_pendentes, nfes_sincronizadas, user_id)
                 VALUES (:tipo, :anterior, :novo, :justificativa, :pendentes, :sincronizadas, :user_id)"
            );
            $stmt->execute([
                ':tipo'           => $tipo,
                ':anterior'       => $tpEmisAnterior,
                ':novo'           => $tpEmisNovo,
                ':justificativa'  => $justificativa,
                ':pendentes'      => $nfesPendentes,
                ':sincronizadas'  => $nfesSincronizadas,
                ':user_id'        => $_SESSION['user_id'] ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::error('NfeContingencyService: Log error', ['exception' => $e->getMessage()]);
        }
    }

    /**
     * Retorna label legível para o tipo de emissão.
     */
    public static function getTpEmisLabel(int $tpEmis): string
    {
        $labels = [
            1 => 'Normal',
            2 => 'Contingência FS-IA',
            3 => 'Contingência SCAN',
            4 => 'Contingência DPEC',
            5 => 'Contingência FS-DA',
            6 => 'Contingência SVC-AN',
            7 => 'Contingência SVC-RS',
            9 => 'Contingência Offline NFC-e',
        ];
        return $labels[$tpEmis] ?? "Tipo {$tpEmis}";
    }

    /**
     * Retorna histórico de contingências.
     *
     * @param int $limit Quantidade máxima de registros
     * @return array
     */
    public function getHistory(int $limit = 50): array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT cl.*, COALESCE(u.name, 'Sistema') AS user_name
                 FROM nfe_contingency_log cl
                 LEFT JOIN users u ON cl.user_id = u.id
                 ORDER BY cl.created_at DESC LIMIT :lim"
            );
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return [];
        }
    }
}
