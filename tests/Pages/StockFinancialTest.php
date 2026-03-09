<?php
namespace Akti\Tests\Pages;

use Akti\Tests\TestCase;

/**
 * Testes dos módulos Estoque e Financeiro.
 *
 * Executar: vendor/bin/phpunit tests/Pages/StockFinancialTest.php
 */
class StockFinancialTest extends TestCase
{
    // ── Estoque ──────────────────────────────────────────────────

    /**
     * @test
     */
    public function estoque_listagem_carrega(): void
    {
        $response = $this->httpGet('?page=stock', true);

        $this->assertStatusOk($response['status'], 'Estoque - Listagem');
        $this->assertNoPhpErrors($response['body'], 'Estoque - Listagem');
        $this->assertNotLoginPage($response['body'], 'Estoque - Listagem');
        $this->assertValidHtml($response['body'], 'Estoque - Listagem');
    }

    /**
     * @test
     */
    public function estoque_armazens_carrega(): void
    {
        $response = $this->httpGet('?page=stock&action=warehouses', true);

        $this->assertStatusOk($response['status'], 'Estoque - Armazéns');
        $this->assertNoPhpErrors($response['body'], 'Estoque - Armazéns');
        $this->assertNotLoginPage($response['body'], 'Estoque - Armazéns');
    }

    /**
     * @test
     */
    public function estoque_entrada_carrega(): void
    {
        $response = $this->httpGet('?page=stock&action=entry', true);

        $this->assertStatusOk($response['status'], 'Estoque - Entrada');
        $this->assertNoPhpErrors($response['body'], 'Estoque - Entrada');
        $this->assertNotLoginPage($response['body'], 'Estoque - Entrada');
    }

    /**
     * @test
     */
    public function estoque_movimentacoes_carrega(): void
    {
        $response = $this->httpGet('?page=stock&action=movements', true);

        $this->assertStatusOk($response['status'], 'Estoque - Movimentações');
        $this->assertNoPhpErrors($response['body'], 'Estoque - Movimentações');
        $this->assertNotLoginPage($response['body'], 'Estoque - Movimentações');
    }

    // ── Financeiro ───────────────────────────────────────────────

    /**
     * @test
     */
    public function financeiro_pagamentos_carrega(): void
    {
        $response = $this->httpGet('?page=financial', true);

        $this->assertStatusOk($response['status'], 'Financeiro - Pagamentos');
        $this->assertNoPhpErrors($response['body'], 'Financeiro - Pagamentos');
    }

    /**
     * @test
     */
    public function financeiro_transacoes_carrega(): void
    {
        $response = $this->httpGet('?page=financial&action=transactions', true);

        $this->assertStatusOk($response['status'], 'Financeiro - Transações');
        $this->assertNoPhpErrors($response['body'], 'Financeiro - Transações');
    }
}
