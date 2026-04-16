<?php
namespace Akti\Models;

use PDO;

/**
 * FinancialReport — relatórios e dados de dashboard.
 *
 * Extrai a responsabilidade de relatórios do model Financial monolítico.
 */
class FinancialReport
{
    private $conn;

    /**
     * Construtor da classe FinancialReport.
     *
     * @param \PDO $db Conexão PDO com o banco de dados
     */
    public function __construct(\PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * Proxy para Financial::getSummary().
     */
    public function getSummary($month = null, $year = null): array
    {
        return (new Financial($this->conn))->getSummary($month, $year);
    }

    /**
     * Proxy para Financial::getChartData().
     */
    public function getChartData(int $months = 6): array
    {
        return (new Financial($this->conn))->getChartData($months);
    }

    /**
     * Proxy para Financial::getOrderFinancialTotals().
     */
    public function getOrderFinancialTotals(int $orderId): array
    {
        return (new Financial($this->conn))->getOrderFinancialTotals($orderId);
    }

    /**
     * Proxy para Financial::getPendingConfirmations().
     */
    public function getPendingConfirmations(): array
    {
        return (new Financial($this->conn))->getPendingConfirmations();
    }

    /**
     * Proxy para Financial::getUpcomingInstallments().
     */
    public function getUpcomingInstallments(int $days = 7): array
    {
        return (new Financial($this->conn))->getUpcomingInstallments($days);
    }

    /**
     * Proxy para Financial::getOverdueInstallments().
     */
    public function getOverdueInstallments(): array
    {
        return (new Financial($this->conn))->getOverdueInstallments();
    }
}
