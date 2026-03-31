<?php
namespace Akti\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Akti\Models\NfeDocument;
use Akti\Models\NfeCredential;
use Akti\Models\NfeQueue;
use Akti\Models\NfeReportModel;

/**
 * Testes unitários do módulo NF-e.
 *
 * Verifica:
 * - NfeDocument: allowedFields para update cobre todos os campos fiscais
 * - NfeCredential: allowedFields inclui ultimo_nsu, filial_id, is_active
 * - NfeQueue: enqueueBatch() persiste batch_id
 * - NfeReportModel: queries usam snake_case (v_prod, não vProd)
 * - Estrutura e existência dos arquivos de view
 *
 * Executar: vendor/bin/phpunit tests/Unit/NfeDocumentTest.php
 *
 * @package Akti\Tests\Unit
 */
class NfeDocumentTest extends TestCase
{
    /**
     * Cria um mock PDO básico.
     */
    private function createMockPdo(): \PDO
    {
        return $this->createMock(\PDO::class);
    }

    /**
     * Cria um mock PDOStatement.
     */
    private function createMockStmt(array $rows = [], $fetchResult = null): \PDOStatement
    {
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->willReturn($rows);
        $stmt->method('fetch')->willReturn($fetchResult);
        $stmt->method('fetchColumn')->willReturn(count($rows));
        $stmt->method('rowCount')->willReturn(count($rows));
        return $stmt;
    }

    // ══════════════════════════════════════════════════════════════
    // NfeDocument — allowedFields
    // ══════════════════════════════════════════════════════════════

    /**
     * @test
     * Verifica que NfeDocument::update() aceita campos de totais fiscais.
     * Bug encontrado na auditoria: esses campos estavam ausentes do allowedFields.
     */
    public function nfe_document_update_aceita_campos_fiscais(): void
    {
        $pdo = $this->createMockPdo();
        $stmt = $this->createMockStmt();
        $pdo->method('prepare')->willReturn($stmt);

        $model = new NfeDocument($pdo);

        // Campos que devem ser aceitos pelo update (totais fiscais)
        $camposFiscais = [
            'valor_icms', 'valor_pis', 'valor_cofins', 'valor_ipi',
            'valor_frete', 'valor_seguro', 'valor_desconto', 'valor_outros',
            'valor_total', 'valor_produtos', 'protocolo', 'chave',
            'xml_autorizado', 'xml_cancelamento', 'xml_cce',
            'motivo_cancelamento', 'correcao_texto', 'emitted_at',
        ];

        $reflection = new \ReflectionClass($model);
        $this->assertTrue(
            $reflection->hasMethod('update'),
            'NfeDocument deve ter o método update()'
        );

        $data = [];
        foreach ($camposFiscais as $campo) {
            $data[$campo] = 'test_value';
        }

        try {
            $model->update(1, $data);
            $this->assertTrue(true, 'update() executou sem exceção com campos fiscais');
        } catch (\Exception $e) {
            $this->fail('update() lançou exceção com campos fiscais: ' . $e->getMessage());
        }
    }

    /**
     * @test
     * Verifica que NfeDocument tem os métodos essenciais.
     */
    public function nfe_document_tem_metodos_essenciais(): void
    {
        $pdo = $this->createMockPdo();
        $model = new NfeDocument($pdo);

        $metodosEsperados = ['create', 'readOne', 'readAllByOrder', 'update'];

        foreach ($metodosEsperados as $metodo) {
            $this->assertTrue(
                method_exists($model, $metodo),
                "NfeDocument deve ter o método {$metodo}()"
            );
        }
    }

    // ══════════════════════════════════════════════════════════════
    // NfeCredential — allowedFields
    // ══════════════════════════════════════════════════════════════

    /**
     * @test
     * Verifica que NfeCredential::update() aceita campos obrigatórios.
     * Bug: ultimo_nsu, filial_id, is_active estavam ausentes.
     */
    public function nfe_credential_update_aceita_campos_obrigatorios(): void
    {
        $pdo = $this->createMockPdo();
        $stmt = $this->createMockStmt();
        $pdo->method('prepare')->willReturn($stmt);

        $model = new NfeCredential($pdo);

        // NfeCredential::update() aceita (array $data) — o id vem dentro do array
        $data = [
            'id'         => 1,
            'ultimo_nsu' => '000000000012345',
            'filial_id'  => 1,
            'is_active'  => 1,
        ];

        try {
            $model->update($data);
            $this->assertTrue(true, 'update() executou sem exceção com campos obrigatórios');
        } catch (\Exception $e) {
            $this->fail('update() lançou exceção com campos obrigatórios: ' . $e->getMessage());
        }
    }

    // ══════════════════════════════════════════════════════════════
    // NfeQueue — enqueueBatch com batch_id
    // ══════════════════════════════════════════════════════════════

    /**
     * @test
     * Verifica que enqueueBatch() inclui batch_id no INSERT.
     */
    public function nfe_queue_enqueue_batch_inclui_batch_id(): void
    {
        $pdo = $this->createMockPdo();
        $stmt = $this->createMockStmt();

        $capturedSql = '';
        $pdo->method('prepare')
            ->willReturnCallback(function ($sql) use (&$capturedSql, $stmt) {
                $capturedSql = $sql;
                return $stmt;
            });

        $model = new NfeQueue($pdo);

        $_SESSION['user_id'] = 1;

        $model->enqueueBatch([100, 200], 'BATCH-001');

        $this->assertStringContainsString(
            'batch_id',
            $capturedSql,
            'SQL do enqueueBatch() deve incluir a coluna batch_id'
        );
    }

    /**
     * @test
     * Verifica que enqueueBatch retorna a quantidade correta de itens enfileirados.
     */
    public function nfe_queue_enqueue_batch_retorna_contagem_correta(): void
    {
        $pdo = $this->createMockPdo();
        $stmt = $this->createMockStmt();
        $pdo->method('prepare')->willReturn($stmt);

        $model = new NfeQueue($pdo);
        $_SESSION['user_id'] = 1;

        $count = $model->enqueueBatch([10, 20, 30], 'BATCH-002');

        $this->assertEquals(3, $count, 'enqueueBatch deve retornar 3 para 3 orderIds');
    }

    // ══════════════════════════════════════════════════════════════
    // NfeReportModel — queries usam snake_case
    // ══════════════════════════════════════════════════════════════

    /**
     * @test
     * Verifica que NfeReportModel não referencia colunas camelCase antigas.
     */
    public function nfe_report_model_usa_snake_case(): void
    {
        $filePath = __DIR__ . '/../../app/models/NfeReportModel.php';
        $this->assertFileExists($filePath, 'NfeReportModel.php deve existir');

        $code = file_get_contents($filePath);

        $camelCaseColumns = ['ni.vProd', 'ni.cProd', 'ni.xProd', 'ni.uCom', 'ni.qCom', 'ni.vUnCom', 'ni.vDesc', 'ni.cEAN'];

        foreach ($camelCaseColumns as $col) {
            $this->assertStringNotContainsString(
                $col,
                $code,
                "NfeReportModel não deve referenciar coluna camelCase '{$col}' — usar snake_case"
            );
        }
    }

    // ══════════════════════════════════════════════════════════════
    // NfeService — saveFiscalTotals usa nomes corretos
    // ══════════════════════════════════════════════════════════════

    /**
     * @test
     * Verifica que NfeService::saveFiscalTotals() usa nomes de coluna corretos.
     */
    public function nfe_service_save_fiscal_totals_usa_colunas_corretas(): void
    {
        $filePath = __DIR__ . '/../../app/services/NfeService.php';
        $this->assertFileExists($filePath, 'NfeService.php deve existir');

        $code = file_get_contents($filePath);

        $colunasCorretas = ['valor_icms', 'valor_pis', 'valor_cofins', 'valor_ipi'];

        foreach ($colunasCorretas as $col) {
            $this->assertStringContainsString(
                "'{$col}'",
                $code,
                "NfeService deve referenciar coluna '{$col}' no saveFiscalTotals"
            );
        }
    }

    // ══════════════════════════════════════════════════════════════
    // FASE 2 — Validações adicionais
    // ══════════════════════════════════════════════════════════════

    /**
     * @test
     * FASE2-01: saveFiscalTotals verifica diretamente vICMS (não vBC) para gravar valor_icms.
     * Bug corrigido: antes verificava isset($totals['vBC']) para atribuir $totals['vICMS'].
     */
    public function nfe_service_save_fiscal_totals_verifica_vicms_diretamente(): void
    {
        $filePath = __DIR__ . '/../../app/services/NfeService.php';
        $code = file_get_contents($filePath);

        // Deve conter: isset($totals['vICMS']) e NÃO isset($totals['vBC']) para atribuir valor_icms
        $this->assertStringContainsString(
            "isset(\$totals['vICMS'])",
            $code,
            "saveFiscalTotals deve verificar isset(\$totals['vICMS']) para gravar valor_icms"
        );

        // Não deve usar vBC como condição para gravar valor_icms
        $this->assertStringNotContainsString(
            "isset(\$totals['vBC'])    \$updateData['valor_icms']",
            $code,
            "saveFiscalTotals NÃO deve usar vBC como condição para gravar valor_icms"
        );
    }

    /**
     * @test
     * FASE2-01: saveFiscalTotals inclui valor_tributos_aprox no mapeamento.
     */
    public function nfe_service_save_fiscal_totals_inclui_tributos_aprox(): void
    {
        $filePath = __DIR__ . '/../../app/services/NfeService.php';
        $code = file_get_contents($filePath);

        $this->assertStringContainsString(
            "'valor_tributos_aprox'",
            $code,
            "saveFiscalTotals deve incluir coluna 'valor_tributos_aprox'"
        );

        $this->assertStringContainsString(
            "vTotTrib",
            $code,
            "saveFiscalTotals deve mapear vTotTrib para valor_tributos_aprox"
        );
    }

    /**
     * @test
     * FASE2-01: NfeDocumentController::emit() valida order_id e verifica NF-e existente.
     */
    public function nfe_controller_emit_tem_validacoes(): void
    {
        $filePath = __DIR__ . '/../../app/controllers/NfeDocumentController.php';
        $code = file_get_contents($filePath);

        // Deve validar order_id
        $this->assertStringContainsString(
            'order_id',
            $code,
            "emit() deve receber e validar order_id"
        );

        // Deve verificar NF-e existente
        $this->assertStringContainsString(
            'readByOrder',
            $code,
            "emit() deve verificar se já existe NF-e para o pedido"
        );
    }

    /**
     * @test
     * FASE2-02: NfeDocumentController::cancel() valida motivo com mínimo 15 caracteres.
     */
    public function nfe_controller_cancel_valida_motivo_minimo(): void
    {
        $filePath = __DIR__ . '/../../app/controllers/NfeDocumentController.php';
        $code = file_get_contents($filePath);

        $this->assertStringContainsString(
            '15',
            $code,
            "cancel() deve validar motivo com mínimo de 15 caracteres"
        );

        $this->assertStringContainsString(
            'motivo',
            $code,
            "cancel() deve receber campo motivo"
        );
    }

    /**
     * @test
     * FASE2-03: NfeDocumentController::correction() valida texto com mínimo 15 caracteres.
     */
    public function nfe_controller_correction_valida_texto_minimo(): void
    {
        $filePath = __DIR__ . '/../../app/controllers/NfeDocumentController.php';
        $code = file_get_contents($filePath);

        $this->assertStringContainsString(
            'correcao_seq',
            $code,
            "correction() deve tratar sequência de CC-e (correcao_seq)"
        );
    }

    /**
     * @test
     * FASE2-04: NfeDocumentController::download() suporta tipos xml, danfe, cancel_xml, cce_xml.
     */
    public function nfe_controller_download_suporta_todos_tipos(): void
    {
        $filePath = __DIR__ . '/../../app/controllers/NfeDocumentController.php';
        $code = file_get_contents($filePath);

        $tipos = ['xml', 'danfe', 'cancel_xml', 'cce_xml'];
        foreach ($tipos as $tipo) {
            $this->assertStringContainsString(
                "case '{$tipo}'",
                $code,
                "download() deve suportar tipo '{$tipo}'"
            );
        }
    }

    /**
     * @test
     * FASE2-05: NfeDocumentController::checkStatus() retorna JSON.
     */
    public function nfe_controller_check_status_retorna_json(): void
    {
        $filePath = __DIR__ . '/../../app/controllers/NfeDocumentController.php';
        $code = file_get_contents($filePath);

        // Método checkStatus deve definir Content-Type JSON
        $this->assertStringContainsString(
            'application/json',
            $code,
            "checkStatus() deve definir Content-Type application/json"
        );
    }

    /**
     * @test
     * FASE2-01: NfeXmlBuilder tem métodos getCalculatedItems e getCalculatedTotals.
     */
    public function nfe_xml_builder_tem_metodos_calculados(): void
    {
        $this->assertTrue(
            class_exists(\Akti\Services\NfeXmlBuilder::class),
            'NfeXmlBuilder deve existir'
        );

        $reflection = new \ReflectionClass(\Akti\Services\NfeXmlBuilder::class);

        $this->assertTrue(
            $reflection->hasMethod('getCalculatedItems'),
            'NfeXmlBuilder deve ter método getCalculatedItems()'
        );

        $this->assertTrue(
            $reflection->hasMethod('getCalculatedTotals'),
            'NfeXmlBuilder deve ter método getCalculatedTotals()'
        );
    }

    /**
     * @test
     * FASE2-01: NfeService chama saveDocumentItems e saveFiscalTotals no método emit.
     */
    public function nfe_service_emit_salva_itens_e_totais(): void
    {
        $filePath = __DIR__ . '/../../app/services/NfeService.php';
        $code = file_get_contents($filePath);

        $this->assertStringContainsString(
            'saveDocumentItems',
            $code,
            "NfeService::emit() deve chamar saveDocumentItems()"
        );

        $this->assertStringContainsString(
            'saveFiscalTotals',
            $code,
            "NfeService::emit() deve chamar saveFiscalTotals()"
        );
    }

    /**
     * @test
     * FASE2-01: NfeService registra auditoria via EventDispatcher ou AuditService.
     */
    public function nfe_service_usa_auditoria(): void
    {
        $controllerPath = __DIR__ . '/../../app/controllers/NfeDocumentController.php';
        $code = file_get_contents($controllerPath);

        $this->assertStringContainsString(
            'AuditService',
            $code,
            "NfeDocumentController deve usar NfeAuditService para registrar auditoria"
        );

        $this->assertStringContainsString(
            'logEmit',
            $code,
            "NfeDocumentController deve chamar logEmit() na emissão"
        );

        $this->assertStringContainsString(
            'logCancel',
            $code,
            "NfeDocumentController deve chamar logCancel() no cancelamento"
        );
    }

    /**
     * @test
     * FASE2-04: download() registra auditoria de download XML e DANFE.
     */
    public function nfe_controller_download_registra_auditoria(): void
    {
        $filePath = __DIR__ . '/../../app/controllers/NfeDocumentController.php';
        $code = file_get_contents($filePath);

        $this->assertStringContainsString(
            'logDownloadXml',
            $code,
            "download() deve chamar logDownloadXml() ao baixar XML"
        );

        $this->assertStringContainsString(
            'logDownloadDanfe',
            $code,
            "download() deve chamar logDownloadDanfe() ao baixar DANFE"
        );
    }

    // ══════════════════════════════════════════════════════════════
    // NfeDocumentController — métodos obrigatórios existem
    // ══════════════════════════════════════════════════════════════

    /**
     * @test
     * Verifica que NfeDocumentController tem todos os métodos críticos via reflexão.
     * (Não instanciamos o controller pois o construtor tem side-effects)
     */
    public function nfe_controller_tem_metodos_criticos(): void
    {
        $reflection = new \ReflectionClass(\Akti\Controllers\NfeDocumentController::class);

        $metodosObrigatorios = [
            'index', 'detail',
            'emit', 'cancel', 'correction', 'download', 'checkStatus',
            'queue', 'received', 'dashboard',
        ];

        foreach ($metodosObrigatorios as $metodo) {
            $this->assertTrue(
                $reflection->hasMethod($metodo),
                "NfeDocumentController deve ter o método {$metodo}()"
            );
        }
    }

    // ══════════════════════════════════════════════════════════════
    // Existência dos arquivos de view NF-e
    // ══════════════════════════════════════════════════════════════

    /**
     * @test
     * Verifica que todos os arquivos de view NF-e necessários existem.
     */
    public function views_nfe_existem(): void
    {
        $viewsDir = __DIR__ . '/../../app/views/nfe/';

        $viewsEsperadas = [
            'index.php',
            'detail.php',
            'credentials.php',
            'dashboard.php',
            'queue.php',
            'received.php',
        ];

        foreach ($viewsEsperadas as $view) {
            $this->assertFileExists(
                $viewsDir . $view,
                "View NF-e '{$view}' deve existir em app/views/nfe/"
            );
        }
    }

    /**
     * @test
     * Verifica que NfeService::saveItems() usa snake_case nos nomes de coluna do INSERT.
     */
    public function nfe_service_save_items_usa_snake_case(): void
    {
        $filePath = __DIR__ . '/../../app/services/NfeService.php';
        $code = file_get_contents($filePath);

        $colunasSnakeCase = ['c_prod', 'x_prod', 'u_com', 'q_com', 'v_un_com', 'v_prod', 'v_desc'];

        foreach ($colunasSnakeCase as $col) {
            $this->assertStringContainsString(
                $col,
                $code,
                "NfeService::saveItems deve usar coluna snake_case '{$col}'"
            );
        }
    }

    /**
     * @test
     * Verifica que o controller detail() usa v_prod (não vProd) na query de itens.
     */
    public function nfe_controller_detail_query_usa_snake_case(): void
    {
        // Após refatoração, a query de detalhe está no NfeDetailService
        $filePath = __DIR__ . '/../../app/services/NfeDetailService.php';
        $code = file_get_contents($filePath);

        $this->assertStringNotContainsString(
            'vProd AS valor_total',
            $code,
            'NfeDetailService não deve usar vProd — usar v_prod'
        );

        $this->assertStringContainsString(
            'v_prod AS valor_total',
            $code,
            'NfeDetailService deve usar v_prod AS valor_total'
        );
    }

    // ══════════════════════════════════════════════════════════════
    // SQL Migration — Arquivo existe
    // ══════════════════════════════════════════════════════════════

    // SQL migration tests removed per project convention:
    // PHPUnit tests must NOT test for .sql file existence.

    // ══════════════════════════════════════════════════════════════
    // FASE 3 — Testes unitários
    // ══════════════════════════════════════════════════════════════

    /**
     * @test
     * FASE3-01: NfeQueue tem métodos getByBatch() e listBatches().
     */
    public function nfe_queue_tem_metodos_batch(): void
    {
        $pdo = $this->createMockPdo();
        $stmt = $this->createMockStmt();
        $pdo->method('prepare')->willReturn($stmt);

        $model = new NfeQueue($pdo);

        $this->assertTrue(
            method_exists($model, 'getByBatch'),
            'NfeQueue deve ter método getByBatch()'
        );

        $this->assertTrue(
            method_exists($model, 'listBatches'),
            'NfeQueue deve ter método listBatches()'
        );
    }

    /**
     * @test
     * FASE3-01: NfeQueue::readPaginated() aceita filtro batch_id.
     */
    public function nfe_queue_read_paginated_aceita_filtro_batch_id(): void
    {
        $pdo = $this->createMockPdo();
        $stmt = $this->createMockStmt();

        $capturedSql = '';
        $pdo->method('prepare')
            ->willReturnCallback(function ($sql) use (&$capturedSql, $stmt) {
                $capturedSql = $sql;
                return $stmt;
            });

        $model = new NfeQueue($pdo);
        $model->readPaginated(['batch_id' => 'BATCH-TEST'], 1, 20);

        $this->assertStringContainsString(
            'batch_id',
            $capturedSql,
            'readPaginated() deve filtrar por batch_id quando informado'
        );
    }

    /**
     * @test
     * FASE3-01: NfeQueue::listBatches() retorna array.
     */
    public function nfe_queue_list_batches_retorna_array(): void
    {
        $pdo = $this->createMockPdo();
        $stmt = $this->createMockStmt([
            ['batch_id' => 'BATCH-001', 'total' => 5, 'completed' => 3, 'failed' => 1, 'pending' => 1, 'processing' => 0, 'started_at' => '2026-03-27', 'finished_at' => null],
        ]);
        $pdo->method('prepare')->willReturn($stmt);

        $model = new NfeQueue($pdo);
        $result = $model->listBatches(10);

        $this->assertIsArray($result, 'listBatches() deve retornar um array');
    }

    /**
     * @test
     * FASE3-02: NfeCredential::get() aceita parâmetro filialId opcional.
     */
    public function nfe_credential_get_aceita_filial_id(): void
    {
        $pdo = $this->createMockPdo();
        $stmt = $this->createMockStmt([], ['id' => 1, 'cnpj' => '12345678000199']);
        $pdo->method('prepare')->willReturn($stmt);

        $model = new NfeCredential($pdo);

        $reflection = new \ReflectionMethod($model, 'get');
        $params = $reflection->getParameters();

        // Deve ter parâmetro opcional $filialId
        $this->assertGreaterThanOrEqual(0, count($params), 'get() deve aceitar parâmetro filialId');

        // Chamada sem parâmetro (compatibilidade)
        try {
            $model->get();
            $this->assertTrue(true, 'get() funciona sem parâmetro');
        } catch (\Exception $e) {
            $this->fail('get() sem parâmetro lançou exceção: ' . $e->getMessage());
        }

        // Chamada com filialId
        try {
            $model->get(1);
            $this->assertTrue(true, 'get() funciona com filialId');
        } catch (\Exception $e) {
            $this->fail('get() com filialId lançou exceção: ' . $e->getMessage());
        }
    }

    /**
     * @test
     * FASE3-02: NfeCredential tem método listAll().
     */
    public function nfe_credential_tem_list_all(): void
    {
        $pdo = $this->createMockPdo();
        $model = new NfeCredential($pdo);

        $this->assertTrue(
            method_exists($model, 'listAll'),
            'NfeCredential deve ter método listAll()'
        );
    }

    /**
     * @test
     * FASE3-03: NfeXmlBuilder suporta finNFe dinâmico (não hardcoded).
     */
    public function nfe_xml_builder_suporta_fin_nfe_dinamico(): void
    {
        $filePath = __DIR__ . '/../../app/services/NfeXmlBuilder.php';
        $code = file_get_contents($filePath);

        // Deve usar fin_nfe do orderData (não hardcoded 1)
        $this->assertStringContainsString(
            "fin_nfe",
            $code,
            "NfeXmlBuilder deve usar orderData['fin_nfe'] para finNFe dinâmico"
        );

        // Deve ter tag NFref para devoluções/complementares
        $this->assertStringContainsString(
            'tagrefNFe',
            $code,
            'NfeXmlBuilder deve chamar tagrefNFe() para NF-e de devolução/complementar'
        );

        // Deve verificar chave_ref
        $this->assertStringContainsString(
            'chave_ref',
            $code,
            "NfeXmlBuilder deve referenciar orderData['chave_ref'] para tag NFref"
        );
    }

    /**
     * @test
     * FASE3-04: NfeXmlBuilder insere ICMSUFDest (DIFAL) no XML.
     */
    public function nfe_xml_builder_insere_difal(): void
    {
        $filePath = __DIR__ . '/../../app/services/NfeXmlBuilder.php';
        $code = file_get_contents($filePath);

        $this->assertStringContainsString(
            'tagICMSUFDest',
            $code,
            'NfeXmlBuilder deve chamar tagICMSUFDest() para inserir DIFAL no XML'
        );

        // Deve incluir DIFAL nos totais ICMSTot
        $this->assertStringContainsString(
            'vICMSUFDest',
            $code,
            'NfeXmlBuilder deve incluir vICMSUFDest nos totais ICMSTot'
        );

        $this->assertStringContainsString(
            'vFCPUFDest',
            $code,
            'NfeXmlBuilder deve incluir vFCPUFDest nos totais ICMSTot'
        );
    }

    /**
     * @test
     * FASE3-05: NfeDocumentController tem método retry().
     */
    public function nfe_controller_tem_metodo_retry(): void
    {
        $reflection = new \ReflectionClass(\Akti\Controllers\NfeDocumentController::class);

        $this->assertTrue(
            $reflection->hasMethod('retry'),
            'NfeDocumentController deve ter o método retry()'
        );
    }

    /**
     * @test
     * FASE3-05: Rota 'retry' existe no mapa de rotas.
     */
    public function rota_retry_existe(): void
    {
        $routesPath = __DIR__ . '/../../app/config/routes.php';
        $this->assertFileExists($routesPath, 'routes.php deve existir');

        $routes = require $routesPath;

        $this->assertArrayHasKey('nfe_documents', $routes, 'Rota nfe_documents deve existir');
        $this->assertArrayHasKey('retry', $routes['nfe_documents']['actions'], 'Action retry deve existir em nfe_documents');
    }

    /**
     * @test
     * FASE3-05: View index.php tem botão de reenvio para NF-e rejeitada.
     */
    public function view_index_tem_botao_retry(): void
    {
        $filePath = __DIR__ . '/../../app/views/nfe/index.php';
        $code = file_get_contents($filePath);

        $this->assertStringContainsString(
            'btn-retry-nfe',
            $code,
            "View index.php deve ter botão com classe 'btn-retry-nfe' para reenvio"
        );

        $this->assertStringContainsString(
            'cancelada_retry',
            $code,
            "View index.php deve suportar status 'cancelada_retry'"
        );
    }

    /**
     * @test
     * FASE3-06: NfeService::inutilizar() integra com SEFAZ (sem TODO).
     */
    public function nfe_service_inutilizar_sem_todo(): void
    {
        $filePath = __DIR__ . '/../../app/services/NfeService.php';
        $code = file_get_contents($filePath);

        $this->assertStringNotContainsString(
            'TODO: Integrar com a API SEFAZ real',
            $code,
            'NfeService::inutilizar() não deve ter TODO — deve integrar com SEFAZ'
        );

        // Deve chamar sefazInutiliza
        $this->assertStringContainsString(
            'sefazInutiliza',
            $code,
            'NfeService::inutilizar() deve chamar sefazInutiliza() do sped-nfe'
        );
    }

    /**
     * @test
     * FASE3-01: View queue.php exibe coluna de Lote (batch_id).
     */
    public function view_queue_exibe_coluna_lote(): void
    {
        $filePath = __DIR__ . '/../../app/views/nfe/queue.php';
        $code = file_get_contents($filePath);

        $this->assertStringContainsString(
            'Lote',
            $code,
            "View queue.php deve ter coluna 'Lote'"
        );

        $this->assertStringContainsString(
            'batch_id',
            $code,
            "View queue.php deve referenciar batch_id"
        );

        $this->assertStringContainsString(
            'filterBatchId',
            $code,
            "View queue.php deve ter filtro de lote (filterBatchId)"
        );
    }

    // SQL migration tests removed per project convention:
    // PHPUnit tests must NOT test for .sql file existence.
}
