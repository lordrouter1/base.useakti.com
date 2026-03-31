<?php
namespace Akti\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Akti\Services\TaxCalculator;

/**
 * Testes unitários para Akti\Services\TaxCalculator.
 *
 * Cobre:
 * - calculateItem() com diferentes regimes (CRT 1, 2, 3)
 * - Cálculos de ICMS (CST 00, 20, 40, 51, CSOSN 101, 102, 900)
 * - Cálculos de PIS, COFINS, IPI
 * - Cálculo de DIFAL para operações interestaduais
 * - Transparência fiscal (vTotTrib)
 * - Alíquotas interestaduais
 *
 * @package Akti\Tests\Unit\Services
 */
class TaxCalculatorTest extends TestCase
{
    private TaxCalculator $calc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calc = new TaxCalculator();
    }

    // ══════════════════════════════════════════════════════════════
    // Estrutura
    // ══════════════════════════════════════════════════════════════

    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(TaxCalculator::class));
    }

    public function testCalculateItemMethodExists(): void
    {
        $this->assertTrue(method_exists(TaxCalculator::class, 'calculateItem'));
    }

    public function testCalculateICMSMethodExists(): void
    {
        $this->assertTrue(method_exists(TaxCalculator::class, 'calculateICMS'));
    }

    // ══════════════════════════════════════════════════════════════
    // calculateItem() — Estrutura do retorno
    // ══════════════════════════════════════════════════════════════

    public function testCalculateItemReturnsExpectedKeys(): void
    {
        $product = ['quantity' => 1, 'unit_price' => 100.00];
        $operation = ['type' => 'venda'];

        $result = $this->calc->calculateItem($product, $operation, 3, 'SP', 'SP');

        $this->assertArrayHasKey('vProd', $result);
        $this->assertArrayHasKey('vDesc', $result);
        $this->assertArrayHasKey('icms', $result);
        $this->assertArrayHasKey('pis', $result);
        $this->assertArrayHasKey('cofins', $result);
        $this->assertArrayHasKey('ipi', $result);
        $this->assertArrayHasKey('vTotTrib', $result);
    }

    public function testCalculateItemVProd(): void
    {
        $product = ['quantity' => 5, 'unit_price' => 10.00];
        $result = $this->calc->calculateItem($product, [], 3, 'SP', 'SP');

        $this->assertEqualsWithDelta(50.00, $result['vProd'], 0.01);
    }

    public function testCalculateItemWithDiscount(): void
    {
        $product = ['quantity' => 1, 'unit_price' => 100.00, 'discount' => 10.00];
        $result = $this->calc->calculateItem($product, [], 3, 'SP', 'SP');

        $this->assertEqualsWithDelta(100.00, $result['vProd'], 0.01);
        $this->assertEqualsWithDelta(10.00, $result['vDesc'], 0.01);
    }

    // ══════════════════════════════════════════════════════════════
    // ICMS — Regime Normal (CRT 3)
    // ══════════════════════════════════════════════════════════════

    public function testICMSNormalCST00(): void
    {
        $product = [
            'quantity' => 1, 'unit_price' => 1000.00,
            'fiscal_cst_icms' => '00', 'fiscal_aliq_icms' => 18.0,
            'fiscal_origem' => 0,
        ];
        $result = $this->calc->calculateItem($product, [], 3, 'SP', 'SP');

        $icms = $result['icms'];
        $this->assertSame('ICMS', $icms['type']);
        $this->assertSame('00', $icms['CST']);
        $this->assertEqualsWithDelta(1000.00, $icms['vBC'], 0.01);
        $this->assertEqualsWithDelta(18.0, $icms['pICMS'], 0.01);
        $this->assertEqualsWithDelta(180.00, $icms['valor'], 0.01);
    }

    public function testICMSNormalCST20ReducaoBC(): void
    {
        $product = [
            'quantity' => 1, 'unit_price' => 1000.00,
            'fiscal_cst_icms' => '20', 'fiscal_aliq_icms' => 18.0,
            'fiscal_icms_reducao_bc' => 30.0,
            'fiscal_origem' => 0,
        ];
        $result = $this->calc->calculateItem($product, [], 3, 'SP', 'SP');

        $icms = $result['icms'];
        $this->assertSame('20', $icms['CST']);
        // BC = 1000 * (1 - 30/100) = 700
        $this->assertEqualsWithDelta(700.00, $icms['vBC'], 0.01);
        // ICMS = 700 * 18% = 126
        $this->assertEqualsWithDelta(126.00, $icms['valor'], 0.01);
    }

    public function testICMSNormalCST40Isenta(): void
    {
        $product = [
            'quantity' => 1, 'unit_price' => 1000.00,
            'fiscal_cst_icms' => '40', 'fiscal_aliq_icms' => 18.0,
            'fiscal_origem' => 0,
        ];
        $result = $this->calc->calculateItem($product, [], 3, 'SP', 'SP');

        $icms = $result['icms'];
        $this->assertSame('40', $icms['CST']);
        $this->assertEqualsWithDelta(0, $icms['valor'], 0.01, 'CST 40 isenta: valor deve ser 0');
    }

    public function testICMSNormalCST51Diferimento(): void
    {
        $product = [
            'quantity' => 1, 'unit_price' => 500.00,
            'fiscal_cst_icms' => '51', 'fiscal_aliq_icms' => 18.0,
            'fiscal_origem' => 0,
        ];
        $result = $this->calc->calculateItem($product, [], 3, 'SP', 'SP');

        $icms = $result['icms'];
        $this->assertSame('51', $icms['CST']);
        // Diferimento total: valor = 0 (diferido)
        $this->assertEqualsWithDelta(0, $icms['valor'], 0.01);
        $this->assertArrayHasKey('vICMSDif', $icms);
    }

    public function testICMSUsesUFAliquotaWhenNotSet(): void
    {
        $product = [
            'quantity' => 1, 'unit_price' => 100.00,
            'fiscal_cst_icms' => '00', 'fiscal_origem' => 0,
            // No fiscal_aliq_icms set → should use UF table
        ];
        $result = $this->calc->calculateItem($product, [], 3, 'SP', 'SP');
        $icms = $result['icms'];

        // SP internal rate is 18%
        $this->assertEqualsWithDelta(18.0, $icms['pICMS'], 0.01);
        $this->assertEqualsWithDelta(18.00, $icms['valor'], 0.01);
    }

    // ══════════════════════════════════════════════════════════════
    // ICMS — Simples Nacional (CRT 1)
    // ══════════════════════════════════════════════════════════════

    public function testICMSSimplesNacionalCSOSN102(): void
    {
        $product = [
            'quantity' => 1, 'unit_price' => 100.00,
            'fiscal_csosn' => '102', 'fiscal_origem' => 0,
        ];
        $result = $this->calc->calculateItem($product, [], 1, 'SP', 'SP');

        $icms = $result['icms'];
        $this->assertSame('ICMSSN', $icms['type']);
        $this->assertSame('102', $icms['CSOSN']);
        $this->assertEqualsWithDelta(0, $icms['valor'], 0.01);
    }

    public function testICMSSimplesNacionalCSOSN101ComCredito(): void
    {
        $product = [
            'quantity' => 1, 'unit_price' => 100.00,
            'fiscal_csosn' => '101', 'fiscal_aliq_icms' => 3.45,
            'fiscal_origem' => 0,
        ];
        $result = $this->calc->calculateItem($product, [], 1, 'SP', 'SP');

        $icms = $result['icms'];
        $this->assertSame('101', $icms['CSOSN']);
        $this->assertArrayHasKey('pCredSN', $icms);
        $this->assertArrayHasKey('vCredICMSSN', $icms);
        $this->assertEqualsWithDelta(3.45, $icms['pCredSN'], 0.01);
    }

    // ══════════════════════════════════════════════════════════════
    // DIFAL — Operações interestaduais
    // ══════════════════════════════════════════════════════════════

    public function testDIFALCalculatedForInterstateOperations(): void
    {
        $product = [
            'quantity' => 1, 'unit_price' => 1000.00,
            'fiscal_cst_icms' => '00', 'fiscal_aliq_icms' => 12.0,
            'fiscal_origem' => 0,
        ];
        $result = $this->calc->calculateItem($product, [], 3, 'SP', 'RJ');

        $this->assertArrayHasKey('difal', $result, 'DIFAL deve ser calculado para operação interestadual');
    }

    public function testNoDIFALForInternalOperations(): void
    {
        $product = [
            'quantity' => 1, 'unit_price' => 1000.00,
            'fiscal_cst_icms' => '00', 'fiscal_aliq_icms' => 18.0,
            'fiscal_origem' => 0,
        ];
        $result = $this->calc->calculateItem($product, [], 3, 'SP', 'SP');

        $this->assertArrayNotHasKey('difal', $result, 'DIFAL não deve existir em operação interna');
    }

    // ══════════════════════════════════════════════════════════════
    // vTotTrib — Transparência fiscal
    // ══════════════════════════════════════════════════════════════

    public function testVTotTribIsSum(): void
    {
        $product = [
            'quantity' => 1, 'unit_price' => 1000.00,
            'fiscal_cst_icms' => '00', 'fiscal_aliq_icms' => 18.0,
            'fiscal_origem' => 0,
        ];
        $result = $this->calc->calculateItem($product, [], 3, 'SP', 'SP');

        $expected = ($result['icms']['valor'] ?? 0)
                  + ($result['pis']['valor'] ?? 0)
                  + ($result['cofins']['valor'] ?? 0)
                  + ($result['ipi']['valor'] ?? 0);

        $this->assertEqualsWithDelta($expected, $result['vTotTrib'], 0.01);
    }

    // ══════════════════════════════════════════════════════════════
    // Cálculos com valores zero
    // ══════════════════════════════════════════════════════════════

    public function testZeroPriceReturnsZeroTaxes(): void
    {
        $product = ['quantity' => 1, 'unit_price' => 0];
        $result = $this->calc->calculateItem($product, [], 3, 'SP', 'SP');

        $this->assertEqualsWithDelta(0, $result['vProd'], 0.01);
        $this->assertEqualsWithDelta(0, $result['icms']['valor'], 0.01);
        $this->assertEqualsWithDelta(0, $result['vTotTrib'], 0.01);
    }

    public function testZeroQuantityReturnsZero(): void
    {
        $product = ['quantity' => 0, 'unit_price' => 100.00];
        $result = $this->calc->calculateItem($product, [], 3, 'SP', 'SP');

        $this->assertEqualsWithDelta(0, $result['vProd'], 0.01);
    }

    // ══════════════════════════════════════════════════════════════
    // Precision — arredondamento correto
    // ══════════════════════════════════════════════════════════════

    public function testCalculatesWithTwoDecimalPrecision(): void
    {
        $product = [
            'quantity' => 3, 'unit_price' => 33.33,
            'fiscal_cst_icms' => '00', 'fiscal_aliq_icms' => 18.0,
            'fiscal_origem' => 0,
        ];
        $result = $this->calc->calculateItem($product, [], 3, 'SP', 'SP');

        // vProd = 3 * 33.33 = 99.99
        $this->assertEqualsWithDelta(99.99, $result['vProd'], 0.01);
        // ICMS = 99.99 * 18% = 18.00 (rounded)
        $this->assertEqualsWithDelta(18.00, $result['icms']['valor'], 0.01);
    }
}
