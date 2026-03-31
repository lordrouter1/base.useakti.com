<?php
namespace Akti\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Akti\Controllers\HealthController;

/**
 * Testes unitários do HealthController.
 *
 * Verifica:
 * - Estrutura de respostas JSON
 * - Verificação de extensões PHP
 * - Verificação de espaço em disco
 *
 * Executar: vendor/bin/phpunit tests/Unit/HealthControllerTest.php
 *
 * @package Akti\Tests\Unit
 */
class HealthControllerTest extends TestCase
{
    /** @test */
    public function pode_ser_instanciado_sem_db(): void
    {
        $controller = new HealthController(null);
        $this->assertInstanceOf(HealthController::class, $controller);
    }

    /** @test */
    public function pode_ser_instanciado_com_pdo(): void
    {
        $pdo = $this->createMock(\PDO::class);
        $controller = new HealthController($pdo);
        $this->assertInstanceOf(HealthController::class, $controller);
    }

    /** @test */
    public function check_database_retorna_error_sem_conexao(): void
    {
        $controller = new HealthController(null);

        // Use reflection to test private method
        $method = new \ReflectionMethod(HealthController::class, 'checkDatabase');
        $method->setAccessible(true);

        $result = $method->invoke($controller);

        $this->assertSame('error', $result['status']);
        $this->assertArrayHasKey('message', $result);
    }

    /** @test */
    public function check_extensions_verifica_extensoes_obrigatorias(): void
    {
        $controller = new HealthController(null);

        $method = new \ReflectionMethod(HealthController::class, 'checkExtensions');
        $method->setAccessible(true);

        $result = $method->invoke($controller);

        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('missing', $result);
        $this->assertIsArray($result['missing']);
    }

    /** @test */
    public function check_disk_space_retorna_informacoes(): void
    {
        $controller = new HealthController(null);

        $method = new \ReflectionMethod(HealthController::class, 'checkDiskSpace');
        $method->setAccessible(true);

        $result = $method->invoke($controller);

        $this->assertArrayHasKey('status', $result);
        $this->assertContains($result['status'], ['ok', 'warning', 'error']);
    }

    /** @test */
    public function check_filesystem_retorna_diretorios(): void
    {
        $controller = new HealthController(null);

        $method = new \ReflectionMethod(HealthController::class, 'checkFilesystem');
        $method->setAccessible(true);

        $result = $method->invoke($controller);

        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('directories', $result);
        $this->assertIsArray($result['directories']);
    }

    /** @test */
    public function check_database_retorna_ok_com_conexao_valida(): void
    {
        $pdo = $this->createMock(\PDO::class);
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $pdo->method('query')->willReturn($stmt);

        $controller = new HealthController($pdo);

        $method = new \ReflectionMethod(HealthController::class, 'checkDatabase');
        $method->setAccessible(true);

        $result = $method->invoke($controller);

        $this->assertSame('ok', $result['status']);
        $this->assertArrayHasKey('latency_ms', $result);
    }
}
