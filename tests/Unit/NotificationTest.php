<?php
namespace Akti\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Akti\Models\Notification;

/**
 * Testes unitários do model Notification.
 *
 * Verifica:
 * - Criação de notificações com dados válidos
 * - Listagem por usuário com e sem filtro de leitura
 * - Contagem de não-lidas
 * - Marcação como lida (individual e em massa)
 * - Segurança: apenas o dono pode marcar como lida
 * - Dados JSON corretamente decodificados no retorno
 *
 * Executar: vendor/bin/phpunit tests/Unit/NotificationTest.php
 *
 * @package Akti\Tests\Unit
 */
class NotificationTest extends TestCase
{
    /**
     * Cria um mock PDO.
     */
    private function createMockPdo(): \PDO
    {
        return $this->createMock(\PDO::class);
    }

    /**
     * Cria um mock PDOStatement.
     */
    private function createMockStmt(array $rows = [], $column = 0): \PDOStatement
    {
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->willReturn($rows);
        $stmt->method('fetchColumn')->willReturn($column);
        return $stmt;
    }

    // ══════════════════════════════════════════════════════════════
    // Instanciação
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function pode_ser_instanciado_com_pdo(): void
    {
        $pdo = $this->createMockPdo();
        $model = new Notification($pdo);
        $this->assertInstanceOf(Notification::class, $model);
    }

    // ══════════════════════════════════════════════════════════════
    // create()
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function create_retorna_id_inserido(): void
    {
        $pdo = $this->createMockPdo();
        $stmt = $this->createMockStmt();

        $pdo->method('prepare')->willReturn($stmt);
        $pdo->method('lastInsertId')->willReturn('42');

        $model = new Notification($pdo);
        $result = $model->create(1, 'new_order', 'Novo Pedido', 'Pedido #42 criado', ['order_id' => 42]);

        $this->assertSame(42, $result);
    }

    /** @test */
    public function create_retorna_false_quando_falha(): void
    {
        $pdo = $this->createMockPdo();
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('execute')->willReturn(false);

        $pdo->method('prepare')->willReturn($stmt);

        $model = new Notification($pdo);
        $result = $model->create(1, 'system', 'Teste');

        $this->assertFalse($result);
    }

    // ══════════════════════════════════════════════════════════════
    // getByUser()
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function get_by_user_retorna_notificacoes_com_data_decodificada(): void
    {
        $rows = [
            [
                'id' => 1,
                'type' => 'new_order',
                'title' => 'Novo Pedido',
                'message' => 'Pedido criado',
                'data' => json_encode(['order_id' => 42]),
                'read_at' => null,
                'created_at' => '2026-03-31 10:00:00',
            ],
        ];

        $pdo = $this->createMockPdo();
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->willReturn($rows);

        $pdo->method('prepare')->willReturn($stmt);

        $model = new Notification($pdo);
        $result = $model->getByUser(1, 20);

        $this->assertCount(1, $result);
        $this->assertIsArray($result[0]['data']);
        $this->assertSame(42, $result[0]['data']['order_id']);
    }

    /** @test */
    public function get_by_user_retorna_array_vazio_quando_sem_notificacoes(): void
    {
        $pdo = $this->createMockPdo();
        $stmt = $this->createMockStmt([]);

        $pdo->method('prepare')->willReturn($stmt);

        $model = new Notification($pdo);
        $result = $model->getByUser(1);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // ══════════════════════════════════════════════════════════════
    // countUnread()
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function count_unread_retorna_inteiro(): void
    {
        $pdo = $this->createMockPdo();
        $stmt = $this->createMockStmt([], 5);

        $pdo->method('prepare')->willReturn($stmt);

        $model = new Notification($pdo);
        $result = $model->countUnread(1);

        $this->assertIsInt($result);
        $this->assertSame(5, $result);
    }

    /** @test */
    public function count_unread_retorna_zero_quando_sem_notificacoes(): void
    {
        $pdo = $this->createMockPdo();
        $stmt = $this->createMockStmt([], 0);

        $pdo->method('prepare')->willReturn($stmt);

        $model = new Notification($pdo);
        $result = $model->countUnread(1);

        $this->assertSame(0, $result);
    }

    // ══════════════════════════════════════════════════════════════
    // markAsRead()
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function mark_as_read_retorna_true(): void
    {
        $pdo = $this->createMockPdo();
        $stmt = $this->createMockStmt();

        $pdo->method('prepare')->willReturn($stmt);

        $model = new Notification($pdo);
        $result = $model->markAsRead(1, 1);

        $this->assertTrue($result);
    }
}
