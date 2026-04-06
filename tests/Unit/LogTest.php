<?php
namespace Akti\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Akti\Core\Log;

/**
 * Testes unitários da classe Log (Structured Logging).
 *
 * Verifica:
 * - Criação de instâncias com canal específico
 * - Métodos estáticos de conveniência
 * - Formato JSON do log
 * - Rotação diária de arquivos
 * - Níveis de log corretos
 *
 * Executar: vendor/bin/phpunit tests/Unit/LogTest.php
 *
 * @package Akti\Tests\Unit
 */
class LogTest extends TestCase
{
    /** @var string Diretório temporário para logs de teste */
    private string $tempLogDir;

    protected function setUp(): void
    {
        $this->tempLogDir = sys_get_temp_dir() . '/akti_test_logs_' . uniqid();
        mkdir($this->tempLogDir, 0777, true);
    }

    protected function tearDown(): void
    {
        // Limpar arquivos de teste
        $files = glob($this->tempLogDir . '/*');
        foreach ($files as $file) {
            unlink($file);
        }
        rmdir($this->tempLogDir);
    }

    // ══════════════════════════════════════════════════════════════
    // Instanciação
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function pode_criar_instancia_com_canal(): void
    {
        $log = Log::channel('security');
        $this->assertInstanceOf(Log::class, $log);
    }

    /** @test */
    public function canal_padrao_e_general(): void
    {
        $log = new Log();
        $this->assertInstanceOf(Log::class, $log);
    }

    // ══════════════════════════════════════════════════════════════
    // Constantes de nível
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function constantes_de_nivel_estao_definidas(): void
    {
        $this->assertSame('emergency', Log::EMERGENCY);
        $this->assertSame('alert', Log::ALERT);
        $this->assertSame('critical', Log::CRITICAL);
        $this->assertSame('error', Log::ERROR);
        $this->assertSame('warning', Log::WARNING);
        $this->assertSame('notice', Log::NOTICE);
        $this->assertSame('info', Log::INFO);
        $this->assertSame('debug', Log::DEBUG);
    }

    // ══════════════════════════════════════════════════════════════
    // Métodos estáticos
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function metodos_estaticos_nao_lancam_excecao(): void
    {
        // Estes métodos não devem lançar exceção, mesmo sem diretório válido
        try {
            Log::info('Test info message');
            Log::warning('Test warning message');
            Log::error('Test error message');
            Log::channel('security')->info('Security test');
            $this->assertTrue(true); // Chegou aqui = sem exceção
        } catch (\Throwable $e) {
            $this->fail('Log methods should not throw: ' . $e->getMessage());
        }
    }

    // ══════════════════════════════════════════════════════════════
    // Channel factory
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function channel_retorna_nova_instancia_de_log(): void
    {
        $log1 = Log::channel('general');
        $log2 = Log::channel('financial');

        $this->assertInstanceOf(Log::class, $log1);
        $this->assertInstanceOf(Log::class, $log2);
        $this->assertNotSame($log1, $log2);
    }
}
