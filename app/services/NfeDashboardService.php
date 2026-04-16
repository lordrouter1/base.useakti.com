<?php
namespace Akti\Services;

use Akti\Models\NfeReportModel;
use Akti\Models\NfeCredential;
use Akti\Models\NfeQueue;
use Akti\Models\NfeReceivedDocument;
use PDO;

/**
 * Service: NfeDashboardService
 * Agrega todos os dados necessários para o Dashboard Fiscal NF-e.
 */
class NfeDashboardService
{
    private PDO $db;

    /**
     * Construtor da classe NfeDashboardService.
     *
     * @param PDO $db Conexão PDO com o banco de dados
     */
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Carrega todos os dados para o dashboard fiscal.
     *
     * @param string $startDate
     * @param string $endDate
     * @return array Dados completos para a view
     */
    public function loadDashboardData(string $startDate, string $endDate): array
    {
        $reportModel = new NfeReportModel($this->db);

        // KPIs acumulados (período selecionado)
        $kpis = $reportModel->getFiscalKpis($startDate, $endDate);

        // KPIs do mês atual
        $kpisMonth = $reportModel->getFiscalKpis(date('Y-m-01'), date('Y-m-d'));

        // Dados para gráficos
        $nfesByMonth = $reportModel->getNfesByMonth(12);
        $statusDist  = $reportModel->getStatusDistribution();

        // Tabelas top
        $topCfops     = $reportModel->getCfopSummary($startDate, $endDate);
        $topCustomers = $reportModel->getTopCustomers(10);

        // Totais de impostos (12 meses)
        $totalTaxes = $reportModel->getTotalTaxes12Months();

        // Taxa de rejeição
        $totalEmitidas = (int)($kpisMonth['total_emitidas'] ?? 0);
        $rejeitadas    = (int)($kpisMonth['rejeitadas'] ?? 0);
        $taxaRejeicao  = $totalEmitidas > 0 ? round(($rejeitadas / $totalEmitidas) * 100, 1) : 0;

        // Labels e cores por status
        $statusLabels = NfeReportModel::getNfeStatusLabels();
        $statusColors = [
            'autorizada'  => '#28a745',
            'cancelada'   => '#343a40',
            'rejeitada'   => '#dc3545',
            'processando' => '#ffc107',
            'rascunho'    => '#6c757d',
            'inutilizada' => '#17a2b8',
            'corrigida'   => '#6f42c1',
            'denegada'    => '#e83e8c',
        ];

        // Alertas
        $alerts = $this->loadAlerts($reportModel);

        // Contadores da fila e docs recebidos
        $queueCounts = $this->loadQueueCounts();
        $receivedPendingCount = $this->loadReceivedPendingCount();

        return compact(
            'kpis', 'kpisMonth', 'nfesByMonth', 'statusDist',
            'topCfops', 'topCustomers', 'totalTaxes',
            'totalEmitidas', 'rejeitadas', 'taxaRejeicao',
            'statusLabels', 'statusColors',
            'alerts', 'queueCounts', 'receivedPendingCount',
            'startDate', 'endDate'
        );
    }

    /**
     * Carrega alertas fiscais + alerta de certificado.
     */
    private function loadAlerts(NfeReportModel $reportModel): array
    {
        $alerts = [];
        try {
            $alerts = $reportModel->getFiscalAlerts();
        } catch (\Throwable $e) {
        }

        // Alerta de certificado digital
        try {
            $credModel = new NfeCredential($this->db);
            $credentials = $credModel->get();
            if (!empty($credentials['certificate_expiry'])) {
                $expiryDate = new \DateTime($credentials['certificate_expiry']);
                $now = new \DateTime();
                $diff = $now->diff($expiryDate);
                if ($expiryDate < $now) {
                    $alerts[] = ['severity' => 'danger', 'title' => 'Certificado Expirado', 'message' => 'Certificado digital EXPIRADO!'];
                } elseif ($diff->days <= 30) {
                    $alerts[] = ['severity' => 'warning', 'title' => 'Certificado Expirando', 'message' => "Certificado expira em {$diff->days} dias."];
                }
            }
        } catch (\Throwable $e) {
        }

        return $alerts;
    }

    /**
     * Carrega contadores da fila de emissão.
     */
    private function loadQueueCounts(): array
    {
        try {
            $queueModel = new NfeQueue($this->db);
            return $queueModel->countByStatus();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Carrega contagem de documentos recebidos pendentes.
     */
    private function loadReceivedPendingCount(): int
    {
        try {
            $receivedModel = new NfeReceivedDocument($this->db);
            $receivedCounts = $receivedModel->countByManifestationStatus();
            return (int)($receivedCounts['pendente'] ?? 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
