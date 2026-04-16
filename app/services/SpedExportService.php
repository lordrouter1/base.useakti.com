<?php

namespace Akti\Services;

/**
 * SpedExportService — Exportação para SPED Fiscal e Contábil.
 * FEAT-018: Integração com Contabilidade
 *
 * Gera arquivos TXT no layout SPED (Fiscal/Contribuições) para
 * importação em sistemas contábeis (Domínio, Fortes, etc.).
 */
class SpedExportService
{
    private \PDO $db;
    private int $tenantId;

    /**
     * Construtor da classe SpedExportService.
     *
     * @param \PDO $db Conexão PDO com o banco de dados
     * @param int $tenantId ID do tenant
     */
    public function __construct(\PDO $db, int $tenantId)
    {
        $this->db = $db;
        $this->tenantId = $tenantId;
    }

    /**
     * Exporta dados financeiros no formato CSV contábil padrão.
     *
     * @param string $startDate Data inicial (Y-m-d)
     * @param string $endDate Data final (Y-m-d)
     * @return array ['filename' => string, 'content' => string, 'records' => int]
     */
    public function exportFinancialCsv(string $startDate, string $endDate): array
    {
        $stmt = $this->db->prepare(
            "SELECT ft.id, ft.type, ft.description, ft.amount, ft.date,
                    ft.category, ft.payment_method, ft.document_number,
                    ft.cost_center, ft.notes
             FROM financial_transactions ft
             WHERE ft.tenant_id = :tenant_id
               AND ft.date BETWEEN :start_date AND :end_date
               AND ft.deleted_at IS NULL
             ORDER BY ft.date ASC, ft.id ASC"
        );
        $stmt->execute([
            ':tenant_id'  => $this->tenantId,
            ':start_date' => $startDate,
            ':end_date'   => $endDate,
        ]);
        $transactions = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $lines = [];
        $lines[] = implode(';', [
            'DATA', 'TIPO', 'DESCRICAO', 'VALOR', 'CATEGORIA',
            'FORMA_PGTO', 'DOCUMENTO', 'CENTRO_CUSTO', 'OBSERVACOES'
        ]);

        foreach ($transactions as $t) {
            $lines[] = implode(';', [
                date('d/m/Y', strtotime($t['date'])),
                $t['type'] === 'income' ? 'R' : 'D',
                $this->sanitizeCsvField($t['description']),
                number_format((float)$t['amount'], 2, ',', ''),
                $this->sanitizeCsvField($t['category'] ?? ''),
                $this->sanitizeCsvField($t['payment_method'] ?? ''),
                $this->sanitizeCsvField($t['document_number'] ?? ''),
                $this->sanitizeCsvField($t['cost_center'] ?? ''),
                $this->sanitizeCsvField($t['notes'] ?? ''),
            ]);
        }

        $filename = 'export_financeiro_' . str_replace('-', '', $startDate) . '_' . str_replace('-', '', $endDate) . '.csv';
        return [
            'filename' => $filename,
            'content'  => implode("\r\n", $lines),
            'records'  => count($transactions),
        ];
    }

    /**
     * Exporta lançamentos contábeis no formato SPED simplificado (TXT).
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function exportSpedTxt(string $startDate, string $endDate): array
    {
        $stmt = $this->db->prepare(
            "SELECT ft.id, ft.type, ft.description, ft.amount, ft.date,
                    ft.category, ft.document_number
             FROM financial_transactions ft
             WHERE ft.tenant_id = :tenant_id
               AND ft.date BETWEEN :start_date AND :end_date
               AND ft.deleted_at IS NULL
             ORDER BY ft.date ASC"
        );
        $stmt->execute([
            ':tenant_id'  => $this->tenantId,
            ':start_date' => $startDate,
            ':end_date'   => $endDate,
        ]);
        $transactions = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $lines = [];
        // Registro 0000 - Abertura
        $lines[] = '|0000|' . str_replace('-', '', $startDate) . '|' . str_replace('-', '', $endDate) . '|AKTI_ERP|';

        $seq = 1;
        foreach ($transactions as $t) {
            $debitAccount = $t['type'] === 'income' ? '1.1.01' : '2.1.01';
            $creditAccount = $t['type'] === 'income' ? '3.1.01' : '1.1.01';

            $lines[] = implode('|', [
                '',
                'I200',
                str_pad((string)$seq, 6, '0', STR_PAD_LEFT),
                date('dmY', strtotime($t['date'])),
                number_format((float)$t['amount'], 2, ',', ''),
                $debitAccount,
                $creditAccount,
                substr($this->sanitizeCsvField($t['description']), 0, 200),
                $t['document_number'] ?? '',
                '',
            ]);
            $seq++;
        }

        // Registro 9999 - Encerramento
        $lines[] = '|9999|' . count($lines) . '|';

        $filename = 'sped_' . str_replace('-', '', $startDate) . '_' . str_replace('-', '', $endDate) . '.txt';
        return [
            'filename' => $filename,
            'content'  => implode("\r\n", $lines),
            'records'  => count($transactions),
        ];
    }

    /**
     * Exporta plano de contas simplificado.
     */
    public function exportChartOfAccounts(): array
    {
        $accounts = [
            ['code' => '1', 'name' => 'ATIVO', 'type' => 'S'],
            ['code' => '1.1', 'name' => 'ATIVO CIRCULANTE', 'type' => 'S'],
            ['code' => '1.1.01', 'name' => 'CAIXA E EQUIVALENTES', 'type' => 'A'],
            ['code' => '1.1.02', 'name' => 'CONTAS A RECEBER', 'type' => 'A'],
            ['code' => '1.1.03', 'name' => 'ESTOQUES', 'type' => 'A'],
            ['code' => '2', 'name' => 'PASSIVO', 'type' => 'S'],
            ['code' => '2.1', 'name' => 'PASSIVO CIRCULANTE', 'type' => 'S'],
            ['code' => '2.1.01', 'name' => 'FORNECEDORES', 'type' => 'A'],
            ['code' => '2.1.02', 'name' => 'OBRIGACOES TRABALHISTAS', 'type' => 'A'],
            ['code' => '2.1.03', 'name' => 'IMPOSTOS A PAGAR', 'type' => 'A'],
            ['code' => '3', 'name' => 'RECEITAS', 'type' => 'S'],
            ['code' => '3.1', 'name' => 'RECEITA OPERACIONAL', 'type' => 'S'],
            ['code' => '3.1.01', 'name' => 'RECEITA DE VENDAS', 'type' => 'A'],
            ['code' => '3.1.02', 'name' => 'RECEITA DE SERVICOS', 'type' => 'A'],
            ['code' => '4', 'name' => 'DESPESAS', 'type' => 'S'],
            ['code' => '4.1', 'name' => 'DESPESAS OPERACIONAIS', 'type' => 'S'],
            ['code' => '4.1.01', 'name' => 'CUSTO DOS PRODUTOS VENDIDOS', 'type' => 'A'],
            ['code' => '4.1.02', 'name' => 'DESPESAS ADMINISTRATIVAS', 'type' => 'A'],
            ['code' => '4.1.03', 'name' => 'DESPESAS COMERCIAIS', 'type' => 'A'],
        ];

        $lines = ['CODIGO;NOME;TIPO'];
        foreach ($accounts as $acc) {
            $lines[] = $acc['code'] . ';' . $acc['name'] . ';' . $acc['type'];
        }

        return [
            'filename' => 'plano_contas.csv',
            'content'  => implode("\r\n", $lines),
            'records'  => count($accounts),
        ];
    }

    /**
     * Sanitiza campo para CSV (remove ; e quebras de linha).
     */
    private function sanitizeCsvField(string $value): string
    {
        return str_replace([';', "\r", "\n", '"'], ['', '', '', ''], $value);
    }
}
