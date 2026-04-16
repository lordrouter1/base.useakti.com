<?php
namespace Akti\Services;

use Akti\Models\NfeReportModel;
use Akti\Services\NfeExportService;
use Akti\Services\NfeSpedFiscalService;
use Akti\Services\NfeSintegraService;
use Akti\Utils\Input;
use PDO;

/**
 * Service: NfeFiscalReportService
 * Lógica de relatórios fiscais: CC-e, exportação Excel, SPED, SINTEGRA, Livros.
 */
class NfeFiscalReportService
{
    private PDO $db;

    /**
     * Construtor da classe NfeFiscalReportService.
     *
     * @param PDO $db Conexão PDO com o banco de dados
     */
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Carrega dados do relatório de Cartas de Correção (CC-e).
     */
    public function getCorrectionReportData(string $startDate, string $endDate): array
    {
        $reportModel = new NfeReportModel($this->db);

        $corrections = $reportModel->getCorrectionHistory($startDate, $endDate);
        $correctionsByMonth = $reportModel->getCorrectionsByMonth(12);

        return [
            'corrections'        => $corrections,
            'correctionsByMonth' => $correctionsByMonth,
            'totalCorrections'   => count($corrections),
            'totalNfes'          => count(array_unique(array_column($corrections, 'nfe_document_id'))),
        ];
    }

    /**
     * Retorna dados e título para exportação de relatório.
     *
     * @return array ['data' => array, 'title' => string] ou ['error' => string]
     */
    public function getExportData(string $type, string $startDate, string $endDate): array
    {
        $reportModel = new NfeReportModel($this->db);

        switch ($type) {
            case 'nfes':
                $data  = $reportModel->getNfesByPeriod($startDate, $endDate);
                $title = "NFe_Emitidas_{$startDate}_a_{$endDate}";
                break;

            case 'taxes':
                $taxSummary = $reportModel->getTaxSummary($startDate, $endDate);
                $data  = $taxSummary['items'] ?? [];
                $title = "Resumo_Impostos_{$startDate}_a_{$endDate}";
                break;

            case 'cfop':
                $data  = $reportModel->getCfopSummary($startDate, $endDate);
                $title = "CFOPs_{$startDate}_a_{$endDate}";
                break;

            case 'cancelled':
                $data  = $reportModel->getCancelledNfes($startDate, $endDate);
                $title = "NFe_Canceladas_{$startDate}_a_{$endDate}";
                break;

            case 'corrections':
                $data  = $reportModel->getCorrectionHistory($startDate, $endDate);
                $title = "Cartas_Correcao_{$startDate}_a_{$endDate}";
                break;

            default:
                return ['error' => 'Tipo de relatório inválido.'];
        }

        if (empty($data)) {
            return ['error' => 'Nenhum dado encontrado para exportar no período selecionado.'];
        }

        return ['data' => $data, 'title' => $title];
    }

    /**
     * Exporta dados para Excel via NfeExportService.
     *
     * @throws \Throwable
     */
    public function exportToExcel(array $data, string $title): void
    {
        $exportService = new NfeExportService();
        $exportService->exportToExcel($data, $title);
    }

    /**
     * Gera conteúdo SPED Fiscal.
     *
     * @return string|null Conteúdo do arquivo ou null se vazio
     * @throws \Throwable
     */
    public function generateSped(string $startDate, string $endDate): ?string
    {
        $spedService = new NfeSpedFiscalService($this->db);
        $content = $spedService->generate($startDate, $endDate);
        return empty($content) ? null : $content;
    }

    /**
     * Gera conteúdo SINTEGRA.
     *
     * @return string|null Conteúdo do arquivo ou null se vazio
     * @throws \Throwable
     */
    public function generateSintegra(string $startDate, string $endDate): ?string
    {
        $sintegraService = new NfeSintegraService($this->db);
        $content = $sintegraService->generate($startDate, $endDate);
        return empty($content) ? null : $content;
    }

    /**
     * Carrega dados do Livro de Registro de Saídas.
     */
    public function getLivroSaidasData(string $startDate, string $endDate): array
    {
        $reportModel = new NfeReportModel($this->db);
        $livro = $reportModel->getLivroSaidas($startDate, $endDate);

        return [
            'items'            => $livro['items'] ?? [],
            'totalsByCfop'     => $livro['totals_by_cfop'] ?? [],
            'totalGeral'       => $livro['total_geral'] ?? [],
            'cfopDescriptions' => NfeReportModel::getCfopDescriptions(),
        ];
    }

    /**
     * Carrega dados do Livro de Registro de Entradas.
     */
    public function getLivroEntradasData(string $startDate, string $endDate): array
    {
        $reportModel = new NfeReportModel($this->db);
        $livro = $reportModel->getLivroEntradas($startDate, $endDate);

        return [
            'items'      => $livro['items'] ?? [],
            'totalGeral' => $livro['total_geral'] ?? [],
        ];
    }
}
