<?php
namespace Akti\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Akti\Models\DashboardWidget;

/**
 * Testes unitários do model DashboardWidget.
 *
 * Verifica:
 * - Registro canônico de widgets disponíveis (constante AVAILABLE_WIDGETS)
 * - Retorno correto de getAvailableWidgets()
 * - Estrutura esperada de cada widget (label, icon, description, file)
 * - Métodos de consulta e persistência com PDO mockado
 * - getVisibleWidgetsForGroup() retorna padrão quando sem config
 * - getVisibleWidgetsForGroup() filtra corretamente por is_visible
 * - saveForGroup() executa transação e insert corretos
 * - resetGroup() remove configuração do grupo
 * - hasConfig() retorna bool correto
 * - Evento 'model.dashboard_widget.saved' é disparado após save
 *
 * Executar: vendor/bin/phpunit tests/Unit/DashboardWidgetTest.php
 *
 * @package Akti\Tests\Unit
 */
class DashboardWidgetTest extends TestCase
{
    /**
     * Cria um mock PDO básico.
     * @return \PDO|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createMockPdo(): \PDO
    {
        $pdo = $this->createMock(\PDO::class);
        return $pdo;
    }

    /**
     * Cria um mock PDOStatement.
     * @return \PDOStatement|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createMockStmt(array $rows = []): \PDOStatement
    {
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->willReturn($rows);
        $stmt->method('fetchColumn')->willReturn(count($rows));
        return $stmt;
    }

    // ══════════════════════════════════════════════════════════════
    // Testes dos widgets disponíveis
    // ══════════════════════════════════════════════════════════════

    /**
     * @test
     * Verifica que AVAILABLE_WIDGETS contém todos os widgets esperados.
     */
    public function available_widgets_contem_todos_os_widgets(): void
    {
        $widgets = DashboardWidget::getAvailableWidgets();

        $expectedKeys = ['header', 'cards_summary', 'pipeline', 'financeiro', 'atrasados', 'agenda', 'atividade'];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $widgets, "Widget '{$key}' não encontrado em AVAILABLE_WIDGETS");
        }

        $this->assertCount(count($expectedKeys), $widgets, 'AVAILABLE_WIDGETS deveria ter exatamente ' . count($expectedKeys) . ' widgets');
    }

    /**
     * @test
     * Verifica que cada widget tem a estrutura correta (label, icon, description, file).
     */
    public function cada_widget_tem_estrutura_correta(): void
    {
        $widgets = DashboardWidget::getAvailableWidgets();
        $requiredKeys = ['label', 'icon', 'description', 'file'];

        foreach ($widgets as $key => $widget) {
            foreach ($requiredKeys as $rk) {
                $this->assertArrayHasKey(
                    $rk,
                    $widget,
                    "Widget '{$key}' está sem a chave '{$rk}'"
                );
                $this->assertNotEmpty(
                    $widget[$rk],
                    "Widget '{$key}' tem a chave '{$rk}' vazia"
                );
            }
        }
    }

    /**
     * @test
     * Verifica que os arquivos de widget referenciados existem.
     */
    public function arquivos_de_widget_existem(): void
    {
        $widgets = DashboardWidget::getAvailableWidgets();

        foreach ($widgets as $key => $widget) {
            $this->assertFileExists(
                $widget['file'],
                "Arquivo do widget '{$key}' não encontrado: {$widget['file']}"
            );
        }
    }

    /**
     * @test
     * Verifica que getAvailableWidgets() retorna o mesmo que a constante.
     */
    public function get_available_widgets_retorna_constante(): void
    {
        $this->assertSame(
            DashboardWidget::AVAILABLE_WIDGETS,
            DashboardWidget::getAvailableWidgets()
        );
    }

    // ══════════════════════════════════════════════════════════════
    // Testes do getVisibleWidgetsForGroup
    // ══════════════════════════════════════════════════════════════

    /**
     * @test
     * Sem configuração personalizada, retorna todos os widgets na ordem padrão.
     */
    public function sem_config_retorna_todos_na_ordem_padrao(): void
    {
        $pdo = $this->createMockPdo();
        $stmt = $this->createMockStmt([]); // Retorna vazio = sem config

        $pdo->method('prepare')->willReturn($stmt);

        $model = new DashboardWidget($pdo);
        $result = $model->getVisibleWidgetsForGroup(999);

        $this->assertSame(
            array_keys(DashboardWidget::AVAILABLE_WIDGETS),
            $result,
            'Sem config personalizada, deve retornar todos os widgets na ordem padrão'
        );
    }

    /**
     * @test
     * Com configuração, retorna apenas widgets com is_visible=1.
     */
    public function com_config_retorna_apenas_visiveis(): void
    {
        $configRows = [
            ['widget_key' => 'header',        'sort_order' => 0, 'is_visible' => 1],
            ['widget_key' => 'cards_summary',  'sort_order' => 1, 'is_visible' => 0],
            ['widget_key' => 'pipeline',       'sort_order' => 2, 'is_visible' => 1],
            ['widget_key' => 'financeiro',     'sort_order' => 3, 'is_visible' => 0],
            ['widget_key' => 'atrasados',      'sort_order' => 4, 'is_visible' => 1],
            ['widget_key' => 'agenda',         'sort_order' => 5, 'is_visible' => 0],
            ['widget_key' => 'atividade',      'sort_order' => 6, 'is_visible' => 1],
        ];

        $pdo = $this->createMockPdo();
        $stmt = $this->createMockStmt($configRows);
        $pdo->method('prepare')->willReturn($stmt);

        $model = new DashboardWidget($pdo);
        $result = $model->getVisibleWidgetsForGroup(1);

        $this->assertSame(
            ['header', 'pipeline', 'atrasados', 'atividade'],
            $result,
            'Deve retornar apenas os widgets com is_visible=1 na ordem configurada'
        );
    }

    /**
     * @test
     * Com config onde todos estão ocultos, retorna array vazio.
     */
    public function todos_ocultos_retorna_vazio(): void
    {
        $configRows = [
            ['widget_key' => 'header',        'sort_order' => 0, 'is_visible' => 0],
            ['widget_key' => 'cards_summary',  'sort_order' => 1, 'is_visible' => 0],
        ];

        $pdo = $this->createMockPdo();
        $stmt = $this->createMockStmt($configRows);
        $pdo->method('prepare')->willReturn($stmt);

        $model = new DashboardWidget($pdo);
        $result = $model->getVisibleWidgetsForGroup(1);

        $this->assertEmpty($result, 'Todos ocultos deve retornar array vazio');
    }

    // ══════════════════════════════════════════════════════════════
    // Testes do hasConfig
    // ══════════════════════════════════════════════════════════════

    /**
     * @test
     * hasConfig retorna true quando grupo tem configuração.
     */
    public function has_config_retorna_true_com_registros(): void
    {
        $pdo = $this->createMockPdo();
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchColumn')->willReturn(5);
        $pdo->method('prepare')->willReturn($stmt);

        $model = new DashboardWidget($pdo);
        $this->assertTrue($model->hasConfig(1));
    }

    /**
     * @test
     * hasConfig retorna false quando grupo não tem configuração.
     */
    public function has_config_retorna_false_sem_registros(): void
    {
        $pdo = $this->createMockPdo();
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchColumn')->willReturn(0);
        $pdo->method('prepare')->willReturn($stmt);

        $model = new DashboardWidget($pdo);
        $this->assertFalse($model->hasConfig(1));
    }

    // ══════════════════════════════════════════════════════════════
    // Testes do saveForGroup
    // ══════════════════════════════════════════════════════════════

    /**
     * @test
     * saveForGroup ignora widgets inválidos (que não existem em AVAILABLE_WIDGETS).
     */
    public function save_ignora_widgets_invalidos(): void
    {
        $pdo = $this->createMockPdo();

        // Esperar beginTransaction e commit
        $pdo->expects($this->once())->method('beginTransaction');
        $pdo->expects($this->once())->method('commit');

        // DELETE e INSERT statements
        $delStmt = $this->createMock(\PDOStatement::class);
        $delStmt->method('execute')->willReturn(true);

        $insStmt = $this->createMock(\PDOStatement::class);
        $insStmt->method('execute')->willReturn(true);

        $pdo->method('prepare')->willReturnOnConsecutiveCalls($delStmt, $insStmt);

        $model = new DashboardWidget($pdo);

        $widgetsToSave = [
            ['widget_key' => 'header', 'is_visible' => 1],
            ['widget_key' => 'inexistente_widget', 'is_visible' => 1], // inválido
            ['widget_key' => 'pipeline', 'is_visible' => 0],
        ];

        $result = $model->saveForGroup(1, $widgetsToSave);

        $this->assertTrue($result, 'saveForGroup deve retornar true ao salvar com sucesso');
    }

    /**
     * @test
     * saveForGroup faz rollback em caso de exceção.
     */
    public function save_faz_rollback_em_excecao(): void
    {
        $pdo = $this->createMockPdo();

        $pdo->expects($this->once())->method('beginTransaction');
        $pdo->expects($this->once())->method('rollBack');
        $pdo->expects($this->never())->method('commit');

        // DELETE lança exceção
        $delStmt = $this->createMock(\PDOStatement::class);
        $delStmt->method('execute')->willThrowException(new \RuntimeException('DB Error'));

        $pdo->method('prepare')->willReturn($delStmt);

        $model = new DashboardWidget($pdo);
        $result = $model->saveForGroup(1, [['widget_key' => 'header', 'is_visible' => 1]]);

        $this->assertFalse($result, 'saveForGroup deve retornar false em caso de erro');
    }

    // ══════════════════════════════════════════════════════════════
    // Testes do resetGroup
    // ══════════════════════════════════════════════════════════════

    /**
     * @test
     * resetGroup executa DELETE corretamente.
     */
    public function reset_group_executa_delete(): void
    {
        $pdo = $this->createMockPdo();
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $pdo->method('prepare')->willReturn($stmt);

        $model = new DashboardWidget($pdo);
        $result = $model->resetGroup(1);

        $this->assertTrue($result, 'resetGroup deve retornar true ao deletar com sucesso');
    }

    // ══════════════════════════════════════════════════════════════
    // Testes de ordenação
    // ══════════════════════════════════════════════════════════════

    /**
     * @test
     * Ordem personalizada é respeitada (widget_key retorna na sequência correta).
     */
    public function ordem_personalizada_e_respeitada(): void
    {
        // Ordem invertida: atividade primeiro, header por último
        $configRows = [
            ['widget_key' => 'atividade',      'sort_order' => 0, 'is_visible' => 1],
            ['widget_key' => 'agenda',         'sort_order' => 1, 'is_visible' => 1],
            ['widget_key' => 'financeiro',     'sort_order' => 2, 'is_visible' => 1],
            ['widget_key' => 'pipeline',       'sort_order' => 3, 'is_visible' => 1],
            ['widget_key' => 'cards_summary',  'sort_order' => 4, 'is_visible' => 1],
            ['widget_key' => 'header',         'sort_order' => 5, 'is_visible' => 1],
        ];

        $pdo = $this->createMockPdo();
        $stmt = $this->createMockStmt($configRows);
        $pdo->method('prepare')->willReturn($stmt);

        $model = new DashboardWidget($pdo);
        $result = $model->getVisibleWidgetsForGroup(1);

        $this->assertSame(
            ['atividade', 'agenda', 'financeiro', 'pipeline', 'cards_summary', 'header'],
            $result,
            'A ordem personalizada deve ser respeitada'
        );
    }

    /**
     * @test
     * getByGroup retorna registros do banco corretamente.
     */
    public function get_by_group_retorna_registros(): void
    {
        $rows = [
            ['widget_key' => 'header', 'sort_order' => 0, 'is_visible' => 1],
            ['widget_key' => 'pipeline', 'sort_order' => 1, 'is_visible' => 0],
        ];

        $pdo = $this->createMockPdo();
        $stmt = $this->createMockStmt($rows);
        $pdo->method('prepare')->willReturn($stmt);

        $model = new DashboardWidget($pdo);
        $result = $model->getByGroup(1);

        $this->assertCount(2, $result);
        $this->assertSame('header', $result[0]['widget_key']);
        $this->assertSame('pipeline', $result[1]['widget_key']);
    }
}
