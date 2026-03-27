<?php
namespace Akti\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * NfeExportService — Exportação de relatórios NF-e para Excel (.xlsx).
 *
 * Utiliza a biblioteca PhpSpreadsheet (phpoffice/phpspreadsheet) para
 * gerar arquivos Excel com formatação profissional.
 *
 * Uso:
 *   $service = new NfeExportService();
 *   $service->exportToExcel($data, 'Relatorio_NFe');
 *
 * FASE4-03 — Exportação de Relatórios em PDF/Excel
 *
 * @package Akti\Services
 */
class NfeExportService
{
    /** @var array Mapeamento de nomes de colunas para labels legíveis (pt-BR) */
    private static array $columnLabels = [
        // NF-e gerais
        'id'                => 'ID',
        'numero'            => 'Número',
        'serie'             => 'Série',
        'modelo'            => 'Modelo',
        'status'            => 'Status',
        'natureza_op'       => 'Natureza Op.',
        'valor_total'       => 'Valor Total (R$)',
        'valor_produtos'    => 'Valor Produtos (R$)',
        'valor_desconto'    => 'Desconto (R$)',
        'valor_frete'       => 'Frete (R$)',
        'dest_cnpj_cpf'     => 'CPF/CNPJ Dest.',
        'dest_nome'         => 'Destinatário',
        'dest_uf'           => 'UF Dest.',
        'chave'             => 'Chave de Acesso',
        'protocolo'         => 'Protocolo',
        'tp_emis'           => 'Tipo Emissão',
        'created_at'        => 'Data Criação',
        'emitted_at'        => 'Data Emissão',
        'created_at_fmt'    => 'Data Criação',
        'emitted_at_fmt'    => 'Data Emissão',
        'order_id'          => 'Pedido #',

        // Impostos
        'ncm'               => 'NCM',
        'cfop'              => 'CFOP',
        'qtd_itens'         => 'Qtd. Itens',
        'qtd_nfes'          => 'Qtd. NF-e',
        'icms'              => 'ICMS (R$)',
        'pis'               => 'PIS (R$)',
        'cofins'            => 'COFINS (R$)',
        'ipi'               => 'IPI (R$)',
        'total_icms'        => 'Total ICMS (R$)',
        'total_pis'         => 'Total PIS (R$)',
        'total_cofins'      => 'Total COFINS (R$)',
        'total_ipi'         => 'Total IPI (R$)',
        'total_produtos'    => 'Total Produtos (R$)',
        'total_notas'       => 'Total Notas (R$)',
        'total_tributos'    => 'Total Tributos (R$)',
        'icms_total'        => 'ICMS Total (R$)',
        'icms_base_total'   => 'BC ICMS Total (R$)',

        // CC-e
        'nfe_document_id'   => 'ID NF-e',
        'seq_evento'        => 'Seq. Evento',
        'texto_correcao'    => 'Texto Correção',
        'c_stat'            => 'cStat',
        'x_motivo'          => 'Motivo',
        'user_name'         => 'Usuário',

        // Cancelamento
        'cancel_motivo'     => 'Motivo Cancel.',
        'cancel_protocolo'  => 'Protocolo Cancel.',
        'cancel_date'       => 'Data Cancel.',
        'cancel_date_fmt'   => 'Data Cancel.',

        // Outros
        'customer_name'     => 'Cliente',
        'customer_id'       => 'ID Cliente',
        'total_nfes'        => 'Total NF-e',
        'primeira_emissao'  => 'Primeira Emissão',
        'ultima_emissao'    => 'Última Emissão',
        'month_label'       => 'Mês',
        'total'             => 'Total',
    ];

    /**
     * Exporta dados de relatório NF-e para Excel e envia para download.
     *
     * @param array  $data  Array de registros (cada registro é um array associativo)
     * @param string $title Título do relatório / nome do arquivo
     * @return void (saída direta via headers HTTP)
     * @throws \RuntimeException Se não houver dados
     */
    public function exportToExcel(array $data, string $title = 'Relatorio_NFe'): void
    {
        if (empty($data)) {
            throw new \RuntimeException('Nenhum dado para exportar.');
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(mb_substr(str_replace(['/', '\\', '?', '*', '[', ']'], '_', $title), 0, 31));

        // ── Cabeçalhos ──
        $headers = array_keys($data[0]);
        $colIdx = 1;
        foreach ($headers as $header) {
            $label = self::$columnLabels[$header] ?? ucfirst(str_replace('_', ' ', $header));
            $sheet->setCellValueByColumnAndRow($colIdx, 1, $label);
            $colIdx++;
        }

        // Estilo dos cabeçalhos
        $lastCol = $sheet->getHighestColumn();
        $headerRange = "A1:{$lastCol}1";
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2563EB'], // Azul Akti
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'bottom' => ['borderStyle' => Border::BORDER_THIN],
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(25);

        // ── Dados ──
        foreach ($data as $rowIdx => $record) {
            $colIdx = 1;
            foreach ($record as $value) {
                $sheet->setCellValueByColumnAndRow($colIdx, $rowIdx + 2, $value);
                $colIdx++;
            }
        }

        // Estilo das células de dados — bordas leves
        $lastRow = $sheet->getHighestRow();
        if ($lastRow > 1) {
            $dataRange = "A2:{$lastCol}{$lastRow}";
            $sheet->getStyle($dataRange)->applyFromArray([
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_HAIR],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            // Zebra stripes (linhas alternadas)
            for ($r = 2; $r <= $lastRow; $r += 2) {
                $sheet->getStyle("A{$r}:{$lastCol}{$r}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F0F4FF'],
                    ],
                ]);
            }
        }

        // Auto-size das colunas
        foreach (range('A', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Congelar painel do cabeçalho
        $sheet->freezePane('A2');

        // ── Metadados ──
        $spreadsheet->getProperties()
            ->setCreator('Akti - Gestão em Produção')
            ->setLastModifiedBy('Akti NF-e')
            ->setTitle($title)
            ->setSubject('Relatório Fiscal NF-e')
            ->setDescription('Relatório gerado automaticamente pelo sistema Akti.')
            ->setCategory('Relatório Fiscal');

        // ── Download ──
        $filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $title) . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Pragma: public');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Exporta dados para CSV (alternativa leve sem PhpSpreadsheet).
     *
     * @param array  $data     Array de registros
     * @param string $filename Nome do arquivo
     * @return void
     */
    public function exportToCsv(array $data, string $filename = 'relatorio.csv'): void
    {
        if (empty($data)) {
            throw new \RuntimeException('Nenhum dado para exportar.');
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $output = fopen('php://output', 'w');

        // BOM para UTF-8 no Excel
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Cabeçalhos
        $headers = array_keys($data[0]);
        $labels = array_map(function ($h) {
            return self::$columnLabels[$h] ?? ucfirst(str_replace('_', ' ', $h));
        }, $headers);
        fputcsv($output, $labels, ';');

        // Dados
        foreach ($data as $record) {
            fputcsv($output, array_values($record), ';');
        }

        fclose($output);
        exit;
    }
}
