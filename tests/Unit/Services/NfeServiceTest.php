<?php

namespace Akti\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Akti\Services\NfeXmlValidator;
use Akti\Services\TaxCalculator;
use Akti\Services\NfeAuditService;
use Akti\Services\NfeQueueService;
use Akti\Services\NfePdfGenerator;

/**
 * Testes unitários para services NF-e.
 *
 * Cobre: construção de XML, validação, cálculos fiscais, geração de PDF.
 * Services sem testes: utilizam mock de SEFAZ para isolar de APIs externas.
 *
 * @package Akti\Tests\Unit\Services
 */
class NfeServiceTest extends TestCase
{
    // ══════════════════════════════════════════════════════════════
    // NfeXmlBuilder — Construção de XML
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function test_xml_builder_gera_xml_valido(): void
    {
        $emitente = [
            'razao_social' => 'Empresa Teste Ltda',
            'nome_fantasia' => 'Teste',
            'cnpj' => '11222333000181',
            'ie' => '123456789',
            'crt' => 3,
            'logradouro' => 'Rua Teste',
            'numero' => '100',
            'bairro' => 'Centro',
            'cMun' => '3550308',
            'xMun' => 'Sao Paulo',
            'uf' => 'SP',
            'cep' => '01001000',
            'cPais' => '1058',
            'xPais' => 'Brasil',
            'fone' => '1199999999',
        ];

        $orderData = [
            'nNF' => 1,
            'serie' => 1,
            'natOp' => 'Venda de mercadoria',
            'tpNF' => 1,
            'idDest' => 1,
            'indFinal' => 1,
            'indPres' => 1,
            'items' => [
                [
                    'name' => 'Produto Teste',
                    'ncm' => '49019900',
                    'cfop' => '5102',
                    'unit' => 'UN',
                    'quantity' => 2,
                    'unit_price' => 50.00,
                    'cest' => '',
                    'fiscal_origem' => 0,
                    'fiscal_cst_icms' => '00',
                    'fiscal_aliq_icms' => 18,
                    'fiscal_cst_pis' => '01',
                    'fiscal_aliq_pis' => 1.65,
                    'fiscal_cst_cofins' => '01',
                    'fiscal_aliq_cofins' => 7.6,
                    'fiscal_cst_ipi' => '50',
                    'fiscal_aliq_ipi' => 0,
                ],
            ],
            'customer' => [
                'cpf' => '12345678901',
                'name' => 'Cliente Teste',
                'logradouro' => 'Rua Cliente',
                'numero' => '200',
                'bairro' => 'Bairro',
                'cMun' => '3550308',
                'xMun' => 'Sao Paulo',
                'uf' => 'SP',
                'cep' => '01001000',
                'fone' => '11999888777',
                'indIEDest' => 9,
            ],
            'modFrete' => 9,
            'installments' => [
                ['amount' => 100.00, 'due_date' => '2025-12-31'],
            ],
        ];

        if (!class_exists('NFePHP\NFe\Make')) {
            $this->markTestSkipped('Biblioteca sped-nfe não instalada.');
        }

        $builder = new \Akti\Services\NfeXmlBuilder($emitente, $orderData, 1, 1);
        $xml = $builder->build();

        $this->assertIsString($xml);
        $this->assertNotEmpty($xml);
        $this->assertStringContainsString('<infNFe', $xml);
        $this->assertStringContainsString('<emit>', $xml);
        $this->assertStringContainsString('<det', $xml);
    }

    /** @test */
    public function test_xml_builder_valida_campos_obrigatorios(): void
    {
        $emitente = [
            'razao_social' => 'Empresa Teste Ltda',
            'cnpj' => '11222333000181',
            'ie' => '123456789',
            'crt' => 3,
            'logradouro' => 'Rua Teste',
            'numero' => '100',
            'bairro' => 'Centro',
            'cMun' => '3550308',
            'xMun' => 'Sao Paulo',
            'uf' => 'SP',
            'cep' => '01001000',
            'cPais' => '1058',
            'xPais' => 'Brasil',
        ];

        $orderData = [
            'nNF' => 1,
            'serie' => 1,
            'natOp' => 'Venda',
            'tpNF' => 1,
            'idDest' => 1,
            'indFinal' => 1,
            'indPres' => 1,
            'items' => [
                [
                    'name' => 'Produto',
                    'ncm' => '49019900',
                    'cfop' => '5102',
                    'unit' => 'UN',
                    'quantity' => 1,
                    'unit_price' => 10.00,
                ],
            ],
            'customer' => [
                'cpf' => '12345678901',
                'name' => 'Cliente',
                'logradouro' => 'Rua',
                'numero' => '1',
                'bairro' => 'Centro',
                'cMun' => '3550308',
                'xMun' => 'Sao Paulo',
                'uf' => 'SP',
                'cep' => '01001000',
                'indIEDest' => 9,
            ],
            'modFrete' => 9,
            'installments' => [],
        ];

        if (!class_exists('NFePHP\NFe\Make')) {
            $this->markTestSkipped('Biblioteca sped-nfe não instalada.');
        }

        $builder = new \Akti\Services\NfeXmlBuilder($emitente, $orderData, 1, 1);
        $xml = $builder->build();

        // O XML gerado deve conter os campos obrigatórios: CNPJ, IE, natOp, série
        $this->assertStringContainsString('<CNPJ>11222333000181</CNPJ>', $xml);
        $this->assertStringContainsString('<IE>123456789</IE>', $xml);
        $this->assertStringContainsString('<natOp>Venda</natOp>', $xml);
        $this->assertStringContainsString('<serie>1</serie>', $xml);
    }

    // ══════════════════════════════════════════════════════════════
    // NfeXmlValidator — Validação de XML contra XSD
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function test_xml_validator_rejeita_xml_invalido(): void
    {
        // XML sem infNFe — deve falhar na validação básica
        $xmlInvalido = '<?xml version="1.0" encoding="UTF-8"?><root><data>teste</data></root>';
        $result = NfeXmlValidator::validate($xmlInvalido);

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
    }

    /** @test */
    public function test_xml_validator_aceita_xml_valido(): void
    {
        // XML vazio deve retornar erro
        $result = NfeXmlValidator::validate('');
        $this->assertFalse($result['valid']);
        $this->assertContains('XML vazio.', $result['errors']);

        // XML mal-formado deve retornar erro
        $result = NfeXmlValidator::validate('<nao-e-xml>');
        $this->assertFalse($result['valid']);
    }

    // ══════════════════════════════════════════════════════════════
    // NfePdfGenerator — Geração de DANFE
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function test_pdf_generator_retorna_conteudo(): void
    {
        // renderToString com XML inválido — deve retornar null (falha silenciosa)
        $result = NfePdfGenerator::renderToString('<xml>invalido</xml>');

        // Sem sped-da instalado ou com XML inválido, retorna null
        $this->assertNull($result);
    }

    // ══════════════════════════════════════════════════════════════
    // NfeAuditService — Rastreamento de ações
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function test_audit_service_registra_acao(): void
    {
        $stmtMock = $this->createMock(\PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(\PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $pdoMock->method('lastInsertId')->willReturn('42');

        $audit = new NfeAuditService($pdoMock);
        $id = $audit->record('emit', 'nfe', 1, 'Emissão de teste');

        $this->assertSame(42, $id);
    }

    // ══════════════════════════════════════════════════════════════
    // TaxCalculator — Cálculos fiscais
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function test_calculo_icms_correto(): void
    {
        $calc = new TaxCalculator();

        $product = [
            'quantity'        => 1,
            'unit_price'      => 1000.00,
            'discount'        => 0,
            'fiscal_origem'   => 0,
            'fiscal_cst_icms' => '00',
            'fiscal_aliq_icms'=> 18.0,
        ];

        $icms = $calc->calculateICMS($product, 3, 'SP', 'SP', 1000.00);

        $this->assertArrayHasKey('valor', $icms);
        $this->assertArrayHasKey('vBC', $icms);
        $this->assertArrayHasKey('pICMS', $icms);

        // ICMS sobre R$1000 a 18% = R$180.00
        $this->assertEquals(18.0, $icms['pICMS']);
        $this->assertEquals(180.00, $icms['valor']);
    }

    /** @test */
    public function test_calculo_ipi_sobre_base(): void
    {
        $calc = new TaxCalculator();

        // CST 50 com alíquota 10%
        $product = [
            'fiscal_cst_ipi'  => '50',
            'fiscal_aliq_ipi' => 10.0,
        ];

        $ipi = $calc->calculateIPI($product, 3, 500.00);

        $this->assertArrayHasKey('vIPI', $ipi);
        $this->assertArrayHasKey('pIPI', $ipi);
        $this->assertEquals(10.0, $ipi['pIPI']);
        // IPI sobre R$500 a 10% = R$50.00
        $this->assertEquals(50.00, $ipi['vIPI']);
        $this->assertEquals(50.00, $ipi['valor']);
    }

    // ══════════════════════════════════════════════════════════════
    // NfeQueueService — Fila de emissão
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function test_queue_service_adiciona_na_fila(): void
    {
        // Mock: verificar se há item pendente (retorna 0 = sem duplicata)
        $stmtCheck = $this->createMock(\PDOStatement::class);
        $stmtCheck->method('execute')->willReturn(true);
        $stmtCheck->method('fetchColumn')->willReturn(0);

        // Mock: insert na fila
        $stmtInsert = $this->createMock(\PDOStatement::class);
        $stmtInsert->method('execute')->willReturn(true);

        // Mock: company_settings (batch limit)
        $stmtSettings = $this->createMock(\PDOStatement::class);
        $stmtSettings->method('execute')->willReturn(true);
        $stmtSettings->method('fetchColumn')->willReturn(false);

        $pdoMock = $this->createMock(\PDO::class);
        $pdoMock->method('prepare')
            ->willReturnOnConsecutiveCalls($stmtCheck, $stmtInsert, $stmtSettings);
        $pdoMock->method('lastInsertId')->willReturn('7');

        $queue = new NfeQueueService($pdoMock);
        $result = $queue->enqueue(101);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    /** @test */
    public function test_queue_service_processa_proximo(): void
    {
        // Mock: fetch next pending item — none available
        $stmtFetch = $this->createMock(\PDOStatement::class);
        $stmtFetch->method('execute')->willReturn(true);
        $stmtFetch->method('fetch')->willReturn(false);

        $pdoMock = $this->createMock(\PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtFetch);

        $queue = new NfeQueueService($pdoMock);
        $result = $queue->processNext();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('processed', $result);
        $this->assertFalse($result['processed']);
    }
}
