<?php
namespace Akti\Tests\Pages;

use Akti\Tests\TestCase;

/**
 * Testes de smoke test do módulo NF-e.
 *
 * Verifica que todas as páginas do módulo NF-e carregam sem erros PHP,
 * retornam HTTP 200 e contêm a estrutura HTML esperada.
 *
 * Corresponde à Fase 2 do ROADMAP_CORRECOES_NFE_V2.md:
 *   - FASE2-01: Validar Emissão (rotas da UI)
 *   - FASE2-02: Validar Cancelamento (rotas da UI)
 *   - FASE2-03: Validar Carta de Correção (rotas da UI)
 *   - FASE2-04: Validar Download XML/DANFE (rotas da UI)
 *   - FASE2-05: Validar Consulta de Status (rotas da UI)
 *
 * Executar: vendor/bin/phpunit tests/Pages/NfeTest.php
 *
 * @package Akti\Tests\Pages
 */
class NfeTest extends TestCase
{
    // ══════════════════════════════════════════════════════════════
    // Listagem e Index (FASE2-01 pré-requisito)
    // ══════════════════════════════════════════════════════════════

    /**
     * @test
     * Listagem de NF-e carrega sem erros PHP.
     */
    public function listagem_nfe_carrega(): void
    {
        $response = $this->httpGet('?page=nfe_documents', true);

        $this->assertStatusOk($response['status'], 'NF-e — Listagem');
        $this->assertNoPhpErrors($response['body'], 'NF-e — Listagem');
        $this->assertValidHtml($response['body'], 'NF-e — Listagem');
        $this->assertBodyContains(['Notas Fiscais'], $response['body'], 'NF-e — Listagem');
    }

    /**
     * @test
     * Listagem com filtro de status carrega sem erros.
     */
    public function listagem_nfe_com_filtro_status(): void
    {
        $response = $this->httpGet('?page=nfe_documents&status=autorizada', true);

        $this->assertStatusOk($response['status'], 'NF-e — Filtro status');
        $this->assertNoPhpErrors($response['body'], 'NF-e — Filtro status');
    }

    /**
     * @test
     * Listagem com filtro de mês/ano carrega sem erros.
     */
    public function listagem_nfe_com_filtro_periodo(): void
    {
        $response = $this->httpGet('?page=nfe_documents&month=' . date('n') . '&year=' . date('Y'), true);

        $this->assertStatusOk($response['status'], 'NF-e — Filtro período');
        $this->assertNoPhpErrors($response['body'], 'NF-e — Filtro período');
    }

    /**
     * @test
     * Listagem com busca textual carrega sem erros.
     */
    public function listagem_nfe_com_busca(): void
    {
        $response = $this->httpGet('?page=nfe_documents&search=teste', true);

        $this->assertStatusOk($response['status'], 'NF-e — Busca');
        $this->assertNoPhpErrors($response['body'], 'NF-e — Busca');
    }

    // ══════════════════════════════════════════════════════════════
    // Dashboard Fiscal (FASE2-01 contexto)
    // ══════════════════════════════════════════════════════════════

    /**
     * @test
     * Dashboard fiscal carrega com KPIs e gráficos.
     */
    public function dashboard_fiscal_carrega(): void
    {
        $response = $this->httpGet('?page=nfe_documents&action=dashboard', true);

        $this->assertStatusOk($response['status'], 'NF-e — Dashboard');
        $this->assertNoPhpErrors($response['body'], 'NF-e — Dashboard');
        $this->assertValidHtml($response['body'], 'NF-e — Dashboard');
    }

    /**
     * @test
     * Dashboard fiscal com filtro de período personalizado.
     */
    public function dashboard_fiscal_com_periodo(): void
    {
        $response = $this->httpGet(
            '?page=nfe_documents&action=dashboard&start_date=' . date('Y-01-01') . '&end_date=' . date('Y-m-d'),
            true
        );

        $this->assertStatusOk($response['status'], 'NF-e — Dashboard período');
        $this->assertNoPhpErrors($response['body'], 'NF-e — Dashboard período');
    }

    // ══════════════════════════════════════════════════════════════
    // Fila de Emissão (FASE2-01 contexto)
    // ══════════════════════════════════════════════════════════════

    /**
     * @test
     * Fila de emissão carrega sem erros.
     */
    public function fila_emissao_carrega(): void
    {
        $response = $this->httpGet('?page=nfe_documents&action=queue', true);

        $this->assertStatusOk($response['status'], 'NF-e — Fila');
        $this->assertNoPhpErrors($response['body'], 'NF-e — Fila');
        $this->assertValidHtml($response['body'], 'NF-e — Fila');
    }

    // ══════════════════════════════════════════════════════════════
    // Documentos Recebidos — DistDFe
    // ══════════════════════════════════════════════════════════════

    /**
     * @test
     * Página de NF-e recebidas (DistDFe) carrega sem erros.
     */
    public function recebidos_carrega(): void
    {
        $response = $this->httpGet('?page=nfe_documents&action=received', true);

        $this->assertStatusOk($response['status'], 'NF-e — Recebidos');
        $this->assertNoPhpErrors($response['body'], 'NF-e — Recebidos');
        $this->assertValidHtml($response['body'], 'NF-e — Recebidos');
    }

    // ══════════════════════════════════════════════════════════════
    // Auditoria
    // ══════════════════════════════════════════════════════════════

    /**
     * @test
     * Página de auditoria NF-e carrega sem erros.
     */
    public function auditoria_carrega(): void
    {
        $response = $this->httpGet('?page=nfe_documents&action=audit', true);

        $this->assertStatusOk($response['status'], 'NF-e — Auditoria');
        $this->assertNoPhpErrors($response['body'], 'NF-e — Auditoria');
        $this->assertValidHtml($response['body'], 'NF-e — Auditoria');
    }

    // ══════════════════════════════════════════════════════════════
    // Webhooks
    // ══════════════════════════════════════════════════════════════

    /**
     * @test
     * Página de webhooks NF-e carrega sem erros.
     */
    public function webhooks_carrega(): void
    {
        $response = $this->httpGet('?page=nfe_documents&action=webhooks', true);

        $this->assertStatusOk($response['status'], 'NF-e — Webhooks');
        $this->assertNoPhpErrors($response['body'], 'NF-e — Webhooks');
        $this->assertValidHtml($response['body'], 'NF-e — Webhooks');
    }

    // ══════════════════════════════════════════════════════════════
    // DANFE Settings
    // ══════════════════════════════════════════════════════════════

    /**
     * @test
     * Página de configurações DANFE carrega sem erros.
     */
    public function danfe_settings_carrega(): void
    {
        $response = $this->httpGet('?page=nfe_documents&action=danfeSettings', true);

        $this->assertStatusOk($response['status'], 'NF-e — DANFE Settings');
        $this->assertNoPhpErrors($response['body'], 'NF-e — DANFE Settings');
        $this->assertValidHtml($response['body'], 'NF-e — DANFE Settings');
    }

    // ══════════════════════════════════════════════════════════════
    // Credenciais SEFAZ
    // ══════════════════════════════════════════════════════════════

    /**
     * @test
     * Página de credenciais SEFAZ carrega sem erros.
     */
    public function credenciais_sefaz_carrega(): void
    {
        $response = $this->httpGet('?page=nfe_credentials', true);

        $this->assertStatusOk($response['status'], 'NF-e — Credenciais');
        $this->assertNoPhpErrors($response['body'], 'NF-e — Credenciais');
        $this->assertValidHtml($response['body'], 'NF-e — Credenciais');
    }

    // ══════════════════════════════════════════════════════════════
    // FASE2-04: Download com ID inválido retorna erro controlado
    // ══════════════════════════════════════════════════════════════

    /**
     * @test
     * Download XML com ID inválido (0) redireciona para listagem (sem erro PHP).
     */
    public function download_id_invalido_redireciona(): void
    {
        $response = $this->httpGet('?page=nfe_documents&action=download&id=0&type=xml', true);

        // Após follow redirect, deve estar na listagem
        $this->assertStatusOk($response['status'], 'NF-e — Download ID 0');
        $this->assertNoPhpErrors($response['body'], 'NF-e — Download ID 0');
    }

    /**
     * @test
     * Download XML com ID inexistente redireciona (sem erro PHP).
     */
    public function download_id_inexistente_redireciona(): void
    {
        $response = $this->httpGet('?page=nfe_documents&action=download&id=999999&type=xml', true);

        $this->assertStatusOk($response['status'], 'NF-e — Download ID inexistente');
        $this->assertNoPhpErrors($response['body'], 'NF-e — Download ID inexistente');
    }

    /**
     * @test
     * Download com tipo inválido redireciona (sem erro PHP).
     */
    public function download_tipo_invalido_redireciona(): void
    {
        $response = $this->httpGet('?page=nfe_documents&action=download&id=1&type=tipo_invalido', true);

        $this->assertStatusOk($response['status'], 'NF-e — Download tipo inválido');
        $this->assertNoPhpErrors($response['body'], 'NF-e — Download tipo inválido');
    }

    // ══════════════════════════════════════════════════════════════
    // FASE2-05: Consulta de Status com ID inválido
    // ══════════════════════════════════════════════════════════════

    /**
     * @test
     * Consulta de status com ID inválido retorna JSON com erro (sem crash PHP).
     */
    public function check_status_id_invalido_retorna_json_erro(): void
    {
        $response = $this->httpGet('?page=nfe_documents&action=checkStatus&id=0', true);

        $this->assertStatusOk($response['status'], 'NF-e — checkStatus ID 0');

        $json = json_decode($response['body'], true);
        $this->assertNotNull($json, 'checkStatus deve retornar JSON válido');
        $this->assertFalse($json['success'] ?? true, 'checkStatus com ID inválido deve retornar success=false');
    }

    /**
     * @test
     * Consulta de status com ID inexistente retorna JSON com erro.
     */
    public function check_status_id_inexistente_retorna_json_erro(): void
    {
        $response = $this->httpGet('?page=nfe_documents&action=checkStatus&id=999999', true);

        $this->assertStatusOk($response['status'], 'NF-e — checkStatus ID inexistente');

        $json = json_decode($response['body'], true);
        $this->assertNotNull($json, 'checkStatus deve retornar JSON válido para ID inexistente');
        $this->assertFalse($json['success'] ?? true, 'checkStatus com ID inexistente deve retornar success=false');
    }

    // ══════════════════════════════════════════════════════════════
    // Detail com ID inválido
    // ══════════════════════════════════════════════════════════════

    /**
     * @test
     * Detalhe de NF-e com ID inválido redireciona para listagem (sem erro PHP).
     */
    public function detail_id_invalido_redireciona(): void
    {
        $response = $this->httpGet('?page=nfe_documents&action=detail&id=0', true);

        $this->assertStatusOk($response['status'], 'NF-e — Detail ID 0');
        $this->assertNoPhpErrors($response['body'], 'NF-e — Detail ID 0');
    }

    /**
     * @test
     * Detalhe de NF-e com ID inexistente redireciona para listagem (sem erro PHP).
     */
    public function detail_id_inexistente_redireciona(): void
    {
        $response = $this->httpGet('?page=nfe_documents&action=detail&id=999999', true);

        $this->assertStatusOk($response['status'], 'NF-e — Detail ID inexistente');
        $this->assertNoPhpErrors($response['body'], 'NF-e — Detail ID inexistente');
    }

    // ══════════════════════════════════════════════════════════════
    // FASE 3 — Smoke Tests
    // ══════════════════════════════════════════════════════════════

    /**
     * @test
     * Fila com filtro batch_id carrega sem erros.
     */
    public function fila_nfe_com_filtro_batch(): void
    {
        $response = $this->httpGet('?page=nfe_documents&action=queue&batch_id=TEST-BATCH', true);

        $this->assertStatusOk($response['status'], 'NF-e — Fila com filtro batch');
        $this->assertNoPhpErrors($response['body'], 'NF-e — Fila com filtro batch');
    }

    /**
     * @test
     * Retry — método existe no controller e rota está registrada.
     */
    public function retry_rota_e_metodo_existem(): void
    {
        // Verificar que o método retry existe via reflexão
        $reflection = new \ReflectionClass(\Akti\Controllers\NfeDocumentController::class);
        $this->assertTrue(
            $reflection->hasMethod('retry'),
            'NfeDocumentController deve ter o método retry()'
        );

        // Verificar que a rota está registrada
        $routes = require __DIR__ . '/../../app/config/routes.php';
        $this->assertArrayHasKey('retry', $routes['nfe_documents']['actions'] ?? [],
            'Action retry deve estar registrada nas rotas de nfe_documents'
        );
    }
}
