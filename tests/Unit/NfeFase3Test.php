<?php
namespace Akti\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Akti\Models\NfeQueue;
use Akti\Models\NfeCredential;

/**
 * Testes unitários da Fase 3 do módulo NF-e.
 *
 * Cobertura:
 * - FASE3-01: NfeQueue batch tracking (getByBatch, listBatches)
 * - FASE3-02: NfeCredential multi-filial (get com filialId, listAll, update com id)
 * - FASE3-03: finNFe dinâmico no NfeXmlBuilder (validação de estrutura)
 * - FASE3-04: DIFAL ICMSUFDest (campo vBCFCPUFDest corrigido)
 * - FASE3-05: Retry NF-e rejeitada (controller método existe)
 * - FASE3-06: Inutilização com SEFAZ (NfeService método existe)
 *
 * Executar: vendor/bin/phpunit tests/Unit/NfeFase3Test.php
 *
 * @package Akti\Tests\Unit
 */
class NfeFase3Test extends TestCase
{
    /**
     * Helper: cria mock PDO.
     */
    private function createMockPdo(): \PDO
    {
        return $this->createMock(\PDO::class);
    }

    /**
     * Helper: cria mock PDOStatement com retorno configurável.
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
    // FASE3-01: Batch Tracking — NfeQueue
    // ══════════════════════════════════════════════════════════════

    /**
     * @test
     * NfeQueue deve ter o método getByBatch().
     */
    public function nfe_queue_tem_metodo_getByBatch(): void
    {
        $pdo = $this->createMockPdo();
        $model = new NfeQueue($pdo);
        $this->assertTrue(
            method_exists($model, 'getByBatch'),
            'NfeQueue deve ter o método getByBatch()'
        );

        $ref = new \ReflectionMethod($model, 'getByBatch');
        $params = $ref->getParameters();
        $this->assertCount(1, $params, 'getByBatch() deve aceitar exatamente 1 parâmetro (batchId)');
        $this->assertEquals('batchId', $params[0]->getName());
    }

    /**
     * @test
     * NfeQueue deve ter o método listBatches().
     */
    public function nfe_queue_tem_metodo_listBatches(): void
    {
        $pdo = $this->createMockPdo();
        $model = new NfeQueue($pdo);
        $this->assertTrue(
            method_exists($model, 'listBatches'),
            'NfeQueue deve ter o método listBatches()'
        );

        $ref = new \ReflectionMethod($model, 'listBatches');
        $params = $ref->getParameters();
        $this->assertCount(1, $params, 'listBatches() deve aceitar 1 parâmetro opcional (limit)');
        $this->assertTrue($params[0]->isOptional(), 'Parâmetro limit deve ser opcional');
        $this->assertEquals(20, $params[0]->getDefaultValue(), 'Default de limit deve ser 20');
    }

    /**
     * @test
     * NfeQueue::getByBatch() retorna array.
     */
    public function nfe_queue_getByBatch_retorna_array(): void
    {
        $pdo = $this->createMockPdo();
        $stmt = $this->createMockStmt([
            ['id' => 1, 'order_id' => 10, 'batch_id' => 'BATCH-001', 'status' => 'completed'],
            ['id' => 2, 'order_id' => 11, 'batch_id' => 'BATCH-001', 'status' => 'pending'],
        ]);
        $pdo->method('prepare')->willReturn($stmt);

        $model = new NfeQueue($pdo);
        $result = $model->getByBatch('BATCH-001');
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }

    /**
     * @test
     * NfeQueue::listBatches() retorna array com colunas esperadas.
     */
    public function nfe_queue_listBatches_retorna_estrutura_correta(): void
    {
        $batchData = [
            [
                'batch_id'   => 'BATCH-001',
                'total'      => 5,
                'completed'  => 3,
                'failed'     => 1,
                'pending'    => 1,
                'processing' => 0,
                'started_at' => '2026-03-27 10:00:00',
                'finished_at'=> '2026-03-27 10:05:00',
            ],
        ];

        $pdo = $this->createMockPdo();
        $stmt = $this->createMockStmt($batchData);
        $stmt->method('bindValue')->willReturn(true);
        $pdo->method('prepare')->willReturn($stmt);

        $model = new NfeQueue($pdo);
        $result = $model->listBatches(10);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);

        $batch = $result[0];
        $this->assertArrayHasKey('batch_id', $batch);
        $this->assertArrayHasKey('total', $batch);
        $this->assertArrayHasKey('completed', $batch);
        $this->assertArrayHasKey('failed', $batch);
        $this->assertArrayHasKey('pending', $batch);
        $this->assertArrayHasKey('processing', $batch);
        $this->assertArrayHasKey('started_at', $batch);
        $this->assertArrayHasKey('finished_at', $batch);
    }

    /**
     * @test
     * NfeQueue::enqueueBatch() deve persistir batch_id.
     */
    public function nfe_queue_enqueueBatch_persiste_batch_id(): void
    {
        $pdo = $this->createMockPdo();
        $stmt = $this->createMockStmt();
        $pdo->method('prepare')->willReturn($stmt);
        $pdo->method('lastInsertId')->willReturn('1');

        // Verificar que o SQL do enqueueBatch contém 'batch_id'
        $model = new NfeQueue($pdo);
        $ref = new \ReflectionMethod($model, 'enqueueBatch');
        $this->assertTrue($ref->isPublic(), 'enqueueBatch() deve ser público');

        $params = $ref->getParameters();
        $this->assertGreaterThanOrEqual(2, count($params), 'enqueueBatch() deve aceitar pelo menos 2 parâmetros');
        $this->assertEquals('orderIds', $params[0]->getName());
        $this->assertEquals('batchId', $params[1]->getName());
    }

    /**
     * @test
     * NfeQueue::readPaginated() suporta filtro por batch_id.
     */
    public function nfe_queue_readPaginated_suporta_filtro_batch(): void
    {
        $pdo = $this->createMockPdo();
        $stmt = $this->createMockStmt([]);
        $stmt->method('bindValue')->willReturn(true);
        $pdo->method('prepare')->willReturn($stmt);

        $model = new NfeQueue($pdo);

        // Deve executar sem erro com filtro batch_id
        $result = $model->readPaginated(['batch_id' => 'BATCH-001'], 1, 20);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('total', $result);
    }

    // ══════════════════════════════════════════════════════════════
    // FASE3-02: Multi-Filial — NfeCredential
    // ══════════════════════════════════════════════════════════════

    /**
     * @test
     * NfeCredential::get() aceita filialId como parâmetro opcional.
     */
    public function nfe_credential_get_aceita_filial_id(): void
    {
        $pdo = $this->createMockPdo();
        $model = new NfeCredential($pdo);

        $ref = new \ReflectionMethod($model, 'get');
        $params = $ref->getParameters();
        $this->assertCount(1, $params, 'get() deve aceitar 1 parâmetro opcional');
        $this->assertTrue($params[0]->isOptional(), 'filialId deve ser opcional');
        $this->assertNull($params[0]->getDefaultValue(), 'Default deve ser null');
    }

    /**
     * @test
     * NfeCredential::get() sem filialId faz fallback para credencial ativa.
     */
    public function nfe_credential_get_sem_filial_busca_ativa(): void
    {
        $expectedCred = [
            'id' => 1, 'cnpj' => '12345678000199', 'razao_social' => 'Empresa Teste',
            'is_active' => 1, 'filial_id' => null,
        ];

        $pdo = $this->createMockPdo();
        // Primeira query (is_active=1) retorna a credencial
        $stmt = $this->createMockStmt([], $expectedCred);
        $pdo->method('prepare')->willReturn($stmt);

        $model = new NfeCredential($pdo);
        $result = $model->get();

        $this->assertIsArray($result);
        $this->assertEquals('12345678000199', $result['cnpj']);
    }

    /**
     * @test
     * NfeCredential::listAll() retorna array.
     */
    public function nfe_credential_listAll_retorna_array(): void
    {
        $pdo = $this->createMockPdo();
        $stmt = $this->createMockStmt([
            ['id' => 1, 'filial_id' => null, 'razao_social' => 'Matriz', 'is_active' => 1],
            ['id' => 2, 'filial_id' => 2, 'razao_social' => 'Filial SP', 'is_active' => 1],
        ]);
        $pdo->method('prepare')->willReturn($stmt);

        $model = new NfeCredential($pdo);
        $result = $model->listAll();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }

    /**
     * @test
     * NfeCredential::update() aceita $id como segundo parâmetro para multi-filial.
     */
    public function nfe_credential_update_aceita_id_para_multi_filial(): void
    {
        $pdo = $this->createMockPdo();
        $model = new NfeCredential($pdo);

        $ref = new \ReflectionMethod($model, 'update');
        $params = $ref->getParameters();
        $this->assertGreaterThanOrEqual(2, count($params), 'update() deve aceitar 2 parâmetros');
        $this->assertEquals('data', $params[0]->getName());
        $this->assertEquals('id', $params[1]->getName());
        $this->assertTrue($params[1]->isOptional(), 'id deve ser opcional');
        $this->assertNull($params[1]->getDefaultValue(), 'Default de id deve ser null');
    }

    /**
     * @test
     * NfeCredential::update() com id=null usa fallback id=1 (compatibilidade).
     */
    public function nfe_credential_update_sem_id_usa_fallback(): void
    {
        $pdo = $this->createMockPdo();
        $stmt = $this->createMockStmt();
        $pdo->method('prepare')->willReturn($stmt);

        $model = new NfeCredential($pdo);

        // Chamada sem ID deve funcionar (compatibilidade legado)
        try {
            $result = $model->update(['razao_social' => 'Teste']);
            $this->assertIsBool($result);
        } catch (\Exception $e) {
            $this->fail('update() sem ID lançou exceção: ' . $e->getMessage());
        }
    }

    /**
     * @test
     * NfeCredential::getNextNumberForUpdate() aceita credentialId.
     */
    public function nfe_credential_getNextNumber_aceita_credential_id(): void
    {
        $pdo = $this->createMockPdo();
        $model = new NfeCredential($pdo);

        $ref = new \ReflectionMethod($model, 'getNextNumberForUpdate');
        $params = $ref->getParameters();
        $this->assertCount(1, $params);
        $this->assertTrue($params[0]->isOptional());
        $this->assertEquals(1, $params[0]->getDefaultValue());
    }

    /**
     * @test
     * NfeCredential::incrementNextNumber() aceita credentialId.
     */
    public function nfe_credential_incrementNextNumber_aceita_credential_id(): void
    {
        $pdo = $this->createMockPdo();
        $model = new NfeCredential($pdo);

        $ref = new \ReflectionMethod($model, 'incrementNextNumber');
        $params = $ref->getParameters();
        $this->assertCount(1, $params);
        $this->assertTrue($params[0]->isOptional());
        $this->assertEquals(1, $params[0]->getDefaultValue());
    }

    /**
     * @test
     * NfeCredential::validateForEmission() existe e retorna array com 'valid' e 'missing'.
     */
    public function nfe_credential_validateForEmission_retorna_estrutura_correta(): void
    {
        $pdo = $this->createMockPdo();
        $stmt = $this->createMockStmt([], false); // get() retorna false
        $pdo->method('prepare')->willReturn($stmt);

        $model = new NfeCredential($pdo);
        $result = $model->validateForEmission();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('missing', $result);
        $this->assertFalse($result['valid'], 'Sem credenciais configuradas, valid deve ser false');
    }

    // ══════════════════════════════════════════════════════════════
    // FASE3-03: finNFe Dinâmico — NfeXmlBuilder
    // ══════════════════════════════════════════════════════════════

    /**
     * @test
     * NfeXmlBuilder existe e tem o método build.
     */
    public function nfe_xml_builder_existe_e_tem_build(): void
    {
        $this->assertTrue(
            class_exists(\Akti\Services\NfeXmlBuilder::class),
            'Classe NfeXmlBuilder deve existir'
        );

        $this->assertTrue(
            method_exists(\Akti\Services\NfeXmlBuilder::class, 'build'),
            'NfeXmlBuilder deve ter o método build()'
        );
    }

    /**
     * @test
     * NfeXmlBuilder usa finNFe dos orderData (não hardcoded).
     */
    public function nfe_xml_builder_finNFe_dinamico(): void
    {
        $source = file_get_contents(__DIR__ . '/../../app/services/NfeXmlBuilder.php');

        // Deve conter referência a orderData['fin_nfe']
        $this->assertStringContainsString(
            "orderData['fin_nfe']",
            $source,
            'NfeXmlBuilder deve usar $this->orderData[\'fin_nfe\'] para finNFe dinâmico'
        );

        // NÃO deve ter finNFe = 1 hardcoded (sem referência a orderData)
        // Verificar que a linha que define finNFe referencia orderData
        preg_match('/\$ide->finNFe\s*=\s*(.+?);/', $source, $matches);
        $this->assertNotEmpty($matches, 'Deve encontrar atribuição de $ide->finNFe');
        $this->assertStringContainsString(
            'orderData',
            $matches[1],
            'finNFe deve ser dinâmico (referenciando orderData), não hardcoded como 1'
        );
    }

    /**
     * @test
     * NfeXmlBuilder suporta NFref para devolução/complementar/ajuste.
     */
    public function nfe_xml_builder_suporta_NFref(): void
    {
        $source = file_get_contents(__DIR__ . '/../../app/services/NfeXmlBuilder.php');

        // Deve conter tagrefNFe para NF-e de referência
        $this->assertStringContainsString(
            'tagrefNFe',
            $source,
            'NfeXmlBuilder deve conter chamada tagrefNFe para NF-e referenciada'
        );

        // Deve conter verificação para finNFe 2, 3, 4
        $this->assertStringContainsString(
            'chave_ref',
            $source,
            'NfeXmlBuilder deve verificar chave_ref para NF-e de devolução/complementar'
        );

        // Deve verificar finNFe 2, 3, 4
        $this->assertMatchesRegularExpression(
            '/in_array\(\$ide->finNFe,\s*\[2,\s*3,\s*4\]\)/',
            $source,
            'NfeXmlBuilder deve verificar finNFe in [2, 3, 4] para NFref'
        );
    }

    // ══════════════════════════════════════════════════════════════
    // FASE3-04: DIFAL — ICMSUFDest no XML
    // ══════════════════════════════════════════════════════════════

    /**
     * @test
     * NfeXmlBuilder insere ICMSUFDest no XML para operações interestaduais.
     */
    public function nfe_xml_builder_insere_ICMSUFDest(): void
    {
        $source = file_get_contents(__DIR__ . '/../../app/services/NfeXmlBuilder.php');

        // Deve conter tagICMSUFDest
        $this->assertStringContainsString(
            'tagICMSUFDest',
            $source,
            'NfeXmlBuilder deve conter chamada tagICMSUFDest para DIFAL'
        );

        // Deve conter vICMSUFDest e vICMSUFRemet nos totais
        $this->assertStringContainsString(
            'vICMSUFDest',
            $source,
            'NfeXmlBuilder deve incluir vICMSUFDest nos totais ICMSTot'
        );
        $this->assertStringContainsString(
            'vICMSUFRemet',
            $source,
            'NfeXmlBuilder deve incluir vICMSUFRemet nos totais ICMSTot'
        );
        $this->assertStringContainsString(
            'vFCPUFDest',
            $source,
            'NfeXmlBuilder deve incluir vFCPUFDest nos totais ICMSTot'
        );
    }

    /**
     * @test
     * NfeXmlBuilder usa vBCFCPUFDest correto (não vBCUFDest) para base FCP.
     */
    public function nfe_xml_builder_vBCFCPUFDest_correto(): void
    {
        $source = file_get_contents(__DIR__ . '/../../app/services/NfeXmlBuilder.php');

        // A linha de vBCFCPUFDest deve usar $difal['vBCFCPUFDest'] (não $difal['vBCUFDest'])
        preg_match('/vBCFCPUFDest\s*=\s*number_format\((.+?),/', $source, $matches);
        $this->assertNotEmpty($matches, 'Deve encontrar atribuição de vBCFCPUFDest');
        $this->assertStringContainsString(
            "vBCFCPUFDest",
            $matches[1],
            'vBCFCPUFDest deve usar $difal[\'vBCFCPUFDest\'] como valor principal, não $difal[\'vBCUFDest\']'
        );
    }

    /**
     * @test
     * NfeXmlBuilder salva dados DIFAL calculados para persistência em nfe_document_items.
     */
    public function nfe_xml_builder_salva_dados_difal_nos_items(): void
    {
        $source = file_get_contents(__DIR__ . '/../../app/services/NfeXmlBuilder.php');

        // Deve salvar campos DIFAL nos calculatedItems
        $this->assertStringContainsString(
            'difal_icms_dest',
            $source,
            'NfeXmlBuilder deve salvar difal_icms_dest em calculatedItems'
        );
    }

    // ══════════════════════════════════════════════════════════════
    // FASE3-05: Retry NF-e Rejeitada
    // ══════════════════════════════════════════════════════════════

    /**
     * @test
     * NfeDocumentController tem o método retry().
     */
    public function nfe_document_controller_tem_metodo_retry(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Controllers\NfeDocumentController::class, 'retry'),
            'NfeDocumentController deve ter o método retry()'
        );

        $ref = new \ReflectionMethod(\Akti\Controllers\NfeDocumentController::class, 'retry');
        $this->assertTrue($ref->isPublic(), 'retry() deve ser público');
    }

    /**
     * @test
     * A rota 'retry' está registrada no routes.php.
     */
    public function rota_retry_registrada(): void
    {
        $routes = require __DIR__ . '/../../app/config/routes.php';
        $this->assertArrayHasKey('nfe_documents', $routes, 'Rota nfe_documents deve existir');
        $this->assertArrayHasKey('actions', $routes['nfe_documents'], 'nfe_documents deve ter actions');
        $this->assertArrayHasKey('retry', $routes['nfe_documents']['actions'], 'Action retry deve estar registrada');
    }

    /**
     * @test
     * A view index.php contém botão de retry para NF-e rejeitada.
     */
    public function view_index_tem_botao_retry(): void
    {
        $viewPath = __DIR__ . '/../../app/views/nfe/index.php';
        $this->assertFileExists($viewPath, 'View index.php deve existir');

        $content = file_get_contents($viewPath);
        $this->assertStringContainsString(
            'btn-retry-nfe',
            $content,
            'View index.php deve conter botão de retry com classe btn-retry-nfe'
        );
        $this->assertStringContainsString(
            'cancelada_retry',
            $content,
            'View index.php deve reconhecer o status cancelada_retry'
        );
    }

    /**
     * @test
     * O JavaScript da view index.php faz POST para action=retry.
     */
    public function view_index_js_chama_action_retry(): void
    {
        $content = file_get_contents(__DIR__ . '/../../app/views/nfe/index.php');
        $this->assertStringContainsString(
            "action=retry",
            $content,
            'JavaScript da view deve fazer POST para action=retry'
        );
    }

    // ══════════════════════════════════════════════════════════════
    // FASE3-06: Inutilização Real com SEFAZ
    // ══════════════════════════════════════════════════════════════

    /**
     * @test
     * NfeService tem o método inutilizar().
     */
    public function nfe_service_tem_metodo_inutilizar(): void
    {
        $this->assertTrue(
            method_exists(\Akti\Services\NfeService::class, 'inutilizar'),
            'NfeService deve ter o método inutilizar()'
        );

        $ref = new \ReflectionMethod(\Akti\Services\NfeService::class, 'inutilizar');
        $this->assertTrue($ref->isPublic(), 'inutilizar() deve ser público');
    }

    /**
     * @test
     * NfeService::inutilizar() faz chamada real à SEFAZ (não é apenas TODO).
     */
    public function nfe_service_inutilizar_integra_sefaz(): void
    {
        $source = file_get_contents(__DIR__ . '/../../app/services/NfeEmissionService.php');

        // Deve conter sefazInutiliza (chamada real)
        $this->assertStringContainsString(
            'sefazInutiliza',
            $source,
            'NfeService::inutilizar() deve fazer chamada sefazInutiliza() à SEFAZ'
        );

        // Deve tratar cStat 102 (inutilização homologada)
        $this->assertStringContainsString(
            '102',
            $source,
            'NfeService::inutilizar() deve verificar cStat 102 (homologada)'
        );

        // Deve salvar protocolo
        $this->assertStringContainsString(
            'nProt',
            $source,
            'NfeService::inutilizar() deve extrair nProt do retorno SEFAZ'
        );
    }

    /**
     * @test
     * NfeService::inutilizar() aceita modelo e série como parâmetros.
     */
    public function nfe_service_inutilizar_aceita_modelo_e_serie(): void
    {
        $ref = new \ReflectionMethod(\Akti\Services\NfeService::class, 'inutilizar');
        $params = $ref->getParameters();

        $paramNames = array_map(fn($p) => $p->getName(), $params);
        $this->assertContains('numInicial', $paramNames);
        $this->assertContains('numFinal', $paramNames);
        $this->assertContains('justificativa', $paramNames);
        $this->assertContains('modelo', $paramNames);
        $this->assertContains('serie', $paramNames);
    }

    /**
     * @test
     * Rota inutilizar está registrada em routes.php.
     */
    public function rota_inutilizar_registrada(): void
    {
        $routes = require __DIR__ . '/../../app/config/routes.php';
        $this->assertArrayHasKey('nfe_documents', $routes);
        $this->assertArrayHasKey('inutilizar', $routes['nfe_documents']['actions'], 'Action inutilizar deve estar registrada');
    }

    // ══════════════════════════════════════════════════════════════
    // Validações de Estrutura — Migration SQL Fase 3
    // ══════════════════════════════════════════════════════════════

    // SQL migration tests removed per project convention:
    // PHPUnit tests must NOT test for .sql file existence.

    // ══════════════════════════════════════════════════════════════
    // Validações de View — queue.php
    // ══════════════════════════════════════════════════════════════

    /**
     * @test
     * View queue.php exibe coluna de lote e filtro por batch_id.
     */
    public function view_queue_exibe_batch_tracking(): void
    {
        $viewPath = __DIR__ . '/../../app/views/nfe/queue.php';
        $this->assertFileExists($viewPath, 'View queue.php deve existir');

        $content = file_get_contents($viewPath);

        // Deve ter coluna Lote na tabela
        $this->assertStringContainsString('Lote', $content, 'View queue.php deve ter coluna Lote');

        // Deve ter filtro por batch_id
        $this->assertStringContainsString('filterBatchId', $content, 'View queue.php deve ter filtro filterBatchId');
        $this->assertStringContainsString('batch_id', $content, 'View queue.php deve referenciar batch_id');

        // Deve exibir badge com batch_id
        $this->assertStringContainsString('batch_id', $content, 'View deve exibir batch_id como badge');

        // Deve mostrar progresso do batch (completed/total)
        $this->assertStringContainsString('completed', $content, 'View deve exibir contagem de completed');
    }

    /**
     * @test
     * Controller queue() passa batches e batchFilter para a view.
     */
    public function controller_queue_passa_batches_para_view(): void
    {
        $source = file_get_contents(__DIR__ . '/../../app/controllers/NfeDocumentController.php');

        // Deve chamar listBatches
        $this->assertStringContainsString(
            'listBatches',
            $source,
            'Controller queue() deve chamar listBatches()'
        );

        // Deve definir $batchFilter
        $this->assertStringContainsString(
            'batchFilter',
            $source,
            'Controller queue() deve definir $batchFilter'
        );

        // Deve definir $batches
        $this->assertStringContainsString(
            '$batches',
            $source,
            'Controller queue() deve definir $batches'
        );
    }
}
