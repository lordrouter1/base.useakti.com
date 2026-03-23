<?php
namespace Akti\Tests\Unit;

use Akti\Tests\TestCase;

/**
 * Testes unitários das rotas AJAX do módulo financeiro (Fase 3/4).
 *
 * Verifica que os endpoints JSON retornam respostas válidas:
 * - getDre           → DRE simplificado
 * - getCashflow      → Fluxo de caixa projetado
 * - recurringList    → Lista de recorrências
 * - exportDreCsv     → Export CSV do DRE
 * - exportCashflowCsv → Export CSV do fluxo de caixa
 *
 * @package Akti\Tests\Unit
 */
class FinancialAjaxTest extends TestCase
{
    // ══════════════════════════════════════════════════════════════
    // DRE
    // ══════════════════════════════════════════════════════════════

    /**
     * @test
     * Verifica que o endpoint DRE retorna JSON válido com estrutura esperada.
     */
    public function dre_retorna_json_valido(): void
    {
        $response = $this->httpGet('?page=financial&action=getDre&from=2026-01&to=2026-03', true);
        $this->assertStatusOk($response['status'], 'DRE AJAX');
        $this->assertNoPhpErrors($response['body'], 'DRE AJAX');

        $json = json_decode($response['body'], true);
        $this->assertIsArray($json, 'DRE response deve ser JSON válido');
        $this->assertTrue($json['success'] ?? false, 'DRE response deve ter success=true');
        $this->assertArrayHasKey('data', $json, 'DRE response deve conter key "data"');

        $data = $json['data'];
        $this->assertArrayHasKey('receitas', $data);
        $this->assertArrayHasKey('despesas', $data);
        $this->assertArrayHasKey('total_receitas', $data);
        $this->assertArrayHasKey('total_despesas', $data);
        $this->assertArrayHasKey('resultado', $data);
        $this->assertArrayHasKey('periodo', $data);
    }

    // ══════════════════════════════════════════════════════════════
    // Fluxo de Caixa
    // ══════════════════════════════════════════════════════════════

    /**
     * @test
     * Verifica que o endpoint Cashflow retorna JSON válido com projeção mensal.
     */
    public function cashflow_retorna_json_valido(): void
    {
        $response = $this->httpGet('?page=financial&action=getCashflow&months=6&recurring=1', true);
        $this->assertStatusOk($response['status'], 'Cashflow AJAX');
        $this->assertNoPhpErrors($response['body'], 'Cashflow AJAX');

        $json = json_decode($response['body'], true);
        $this->assertIsArray($json, 'Cashflow response deve ser JSON válido');
        $this->assertTrue($json['success'] ?? false, 'Cashflow response deve ter success=true');
        $this->assertArrayHasKey('data', $json, 'Cashflow response deve conter key "data"');

        $data = $json['data'];
        $this->assertIsArray($data);
        $this->assertCount(6, $data, 'Cashflow com months=6 deve retornar 6 meses');

        // Verificar estrutura de cada mês
        if (!empty($data[0])) {
            $month = $data[0];
            $this->assertArrayHasKey('month', $month);
            $this->assertArrayHasKey('label', $month);
            $this->assertArrayHasKey('total_entradas', $month);
            $this->assertArrayHasKey('total_saidas', $month);
            $this->assertArrayHasKey('saldo_mes', $month);
            $this->assertArrayHasKey('saldo_acumulado', $month);
        }
    }

    /**
     * @test
     * Verifica que o horizonte de projeção é respeitado (3 meses).
     */
    public function cashflow_horizonte_3_meses(): void
    {
        $response = $this->httpGet('?page=financial&action=getCashflow&months=3&recurring=0', true);
        $json = json_decode($response['body'], true);
        $this->assertCount(3, $json['data'] ?? [], 'Cashflow com months=3 deve retornar 3 meses');
    }

    // ══════════════════════════════════════════════════════════════
    // Recorrências
    // ══════════════════════════════════════════════════════════════

    /**
     * @test
     * Verifica que o endpoint de listagem de recorrências retorna JSON válido.
     */
    public function recurring_list_retorna_json_valido(): void
    {
        $response = $this->httpGet('?page=financial&action=recurringList', true);
        $this->assertStatusOk($response['status'], 'Recurring List AJAX');
        $this->assertNoPhpErrors($response['body'], 'Recurring List AJAX');

        $json = json_decode($response['body'], true);
        $this->assertIsArray($json, 'Recurring response deve ser JSON válido');
        $this->assertArrayHasKey('data', $json, 'Recurring response deve conter key "data"');
        $this->assertArrayHasKey('summary', $json, 'Recurring response deve conter key "summary"');

        $summary = $json['summary'];
        $this->assertArrayHasKey('entradas', $summary);
        $this->assertArrayHasKey('saidas', $summary);
    }

    // ══════════════════════════════════════════════════════════════
    // Exportações CSV
    // ══════════════════════════════════════════════════════════════

    /**
     * @test
     * Verifica que o endpoint de exportação de DRE retorna CSV.
     */
    public function export_dre_csv_retorna_conteudo(): void
    {
        $response = $this->httpGet('?page=financial&action=exportDreCsv&from=2026-01&to=2026-03', true);
        $this->assertStatusOk($response['status'], 'Export DRE CSV');
        $this->assertNoPhpErrors($response['body'], 'Export DRE CSV');
        $this->assertNotEmpty($response['body'], 'Export DRE CSV deve retornar conteúdo');
        // CSV deve conter "DRE" ou "RECEITAS"
        $this->assertStringContainsStringIgnoringCase('DRE', $response['body'], 'CSV deve conter cabeçalho DRE');
    }

    /**
     * @test
     * Verifica que o endpoint de exportação de fluxo de caixa retorna CSV.
     */
    public function export_cashflow_csv_retorna_conteudo(): void
    {
        $response = $this->httpGet('?page=financial&action=exportCashflowCsv&months=3&recurring=1', true);
        $this->assertStatusOk($response['status'], 'Export Cashflow CSV');
        $this->assertNoPhpErrors($response['body'], 'Export Cashflow CSV');
        $this->assertNotEmpty($response['body'], 'Export Cashflow CSV deve retornar conteúdo');
    }

    /**
     * @test
     * Verifica que o endpoint de exportação de transações retorna CSV.
     */
    public function export_transactions_csv_retorna_conteudo(): void
    {
        $response = $this->httpGet('?page=financial&action=exportTransactionsCsv', true);
        $this->assertStatusOk($response['status'], 'Export Transactions CSV');
        $this->assertNoPhpErrors($response['body'], 'Export Transactions CSV');
        $this->assertNotEmpty($response['body'], 'Export Transactions CSV deve retornar conteúdo');
    }

    // ══════════════════════════════════════════════════════════════
    // Rotas no routes.php
    // ══════════════════════════════════════════════════════════════

    /**
     * @test
     * Verifica que todas as novas rotas estão registradas no mapa de rotas.
     */
    public function novas_rotas_registradas(): void
    {
        $routes = require __DIR__ . '/../../app/config/routes.php';
        $financial = $routes['financial']['actions'] ?? [];

        // Fase 3/4 routes
        $expected = [
            'getDre', 'getCashflow',
            'exportTransactionsCsv', 'exportDreCsv', 'exportCashflowCsv',
            'recurringList', 'recurringStore', 'recurringUpdate',
            'recurringDelete', 'recurringToggle', 'recurringProcess', 'recurringGet',
        ];

        foreach ($expected as $action) {
            $this->assertArrayHasKey($action, $financial, "Rota financial.{$action} deve existir no mapa de rotas");
        }
    }
}
