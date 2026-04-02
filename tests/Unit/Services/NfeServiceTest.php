<?php

namespace Akti\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;

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
        $this->markTestIncomplete(
            'Requer mock de NfeXmlBuilder com dados de pedido de teste. '
            . 'Implementar quando refatorar para injeção de dependências.'
        );
    }

    /** @test */
    public function test_xml_builder_valida_campos_obrigatorios(): void
    {
        $this->markTestIncomplete(
            'Requer mock de NfeXmlBuilder com validação de campos. '
            . 'Campos obrigatórios: CNPJ, IE, natureza operação, série.'
        );
    }

    // ══════════════════════════════════════════════════════════════
    // NfeXmlValidator — Validação de XML contra XSD
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function test_xml_validator_rejeita_xml_invalido(): void
    {
        $this->markTestIncomplete(
            'Requer XSD da NF-e e XML de teste inválido.'
        );
    }

    /** @test */
    public function test_xml_validator_aceita_xml_valido(): void
    {
        $this->markTestIncomplete(
            'Requer XML de NF-e válido de teste.'
        );
    }

    // ══════════════════════════════════════════════════════════════
    // NfePdfGenerator — Geração de DANFE
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function test_pdf_generator_retorna_conteudo(): void
    {
        $this->markTestIncomplete(
            'Requer mock de NfePdfGenerator com XML autorizado de teste.'
        );
    }

    // ══════════════════════════════════════════════════════════════
    // NfeAuditService — Rastreamento de ações
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function test_audit_service_registra_acao(): void
    {
        $this->markTestIncomplete(
            'Requer mock de PDO para verificar INSERT em nfe_audit_log.'
        );
    }

    // ══════════════════════════════════════════════════════════════
    // NfeFiscalReportService — Cálculos fiscais
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function test_calculo_icms_correto(): void
    {
        $this->markTestIncomplete(
            'Requer tabela de alíquotas ICMS mockada e cenários de cálculo.'
        );
    }

    /** @test */
    public function test_calculo_ipi_sobre_base(): void
    {
        $this->markTestIncomplete(
            'Requer cenários de IPI com base de cálculo variável.'
        );
    }

    // ══════════════════════════════════════════════════════════════
    // NfeQueueService — Fila de emissão
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function test_queue_service_adiciona_na_fila(): void
    {
        $this->markTestIncomplete(
            'Requer mock de PDO e NfeQueue model.'
        );
    }

    /** @test */
    public function test_queue_service_processa_proximo(): void
    {
        $this->markTestIncomplete(
            'Requer mock de NfeService e NfeQueue model.'
        );
    }
}
