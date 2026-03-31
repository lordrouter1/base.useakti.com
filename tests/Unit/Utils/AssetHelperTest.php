<?php
namespace Akti\Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;

/**
 * Testes unitários para asset_helper — Cache Busting.
 * Fase 4 — Performance e Otimização (item 4.4).
 *
 * @package Akti\Tests\Unit\Utils
 */
class AssetHelperTest extends TestCase
{
    /**
     * @test
     * A função asset() deve estar definida.
     */
    public function asset_function_exists(): void
    {
        $this->assertTrue(
            function_exists('asset'),
            'A função global asset() deve estar definida'
        );
    }

    /**
     * @test
     * asset() deve retornar o path com query string de versão.
     */
    public function asset_returns_path_with_version_query_string(): void
    {
        $result = asset('assets/css/style.css');
        $this->assertStringContainsString('assets/css/style.css?v=', $result);
    }

    /**
     * @test
     * asset() deve usar o mtime do arquivo quando o arquivo existe.
     */
    public function asset_uses_file_mtime_when_file_exists(): void
    {
        // O arquivo style.css deve existir no projeto
        $result = asset('assets/css/style.css');
        
        // Extrair o valor de v=
        $parts = explode('?v=', $result);
        $this->assertCount(2, $parts, 'Deve ter path?v=timestamp');
        
        $version = (int)$parts[1];
        $this->assertGreaterThan(0, $version, 'Versão deve ser um timestamp válido');
    }

    /**
     * @test
     * asset() deve retornar um timestamp (time()) para arquivos inexistentes.
     */
    public function asset_uses_current_time_for_nonexistent_files(): void
    {
        $before = time();
        $result = asset('assets/css/arquivo_que_nao_existe_xyz.css');
        $after = time();

        $parts = explode('?v=', $result);
        $version = (int)$parts[1];

        $this->assertGreaterThanOrEqual($before, $version);
        $this->assertLessThanOrEqual($after, $version);
    }

    /**
     * @test
     * asset() deve manter o path relativo intacto.
     */
    public function asset_preserves_relative_path(): void
    {
        $result = asset('assets/js/script.js');
        $this->assertStringStartsWith('assets/js/script.js', $result);
    }

    /**
     * @test
     * asset() deve funcionar com paths de imagem.
     */
    public function asset_works_with_image_paths(): void
    {
        $result = asset('assets/img/logo.png');
        $this->assertStringContainsString('assets/img/logo.png?v=', $result);
    }

    /**
     * @test
     * asset() deve produzir versões diferentes para arquivos diferentes existentes.
     */
    public function asset_produces_consistent_versions_for_same_file(): void
    {
        $result1 = asset('assets/css/style.css');
        $result2 = asset('assets/css/style.css');
        $this->assertEquals($result1, $result2, 'Chamadas consecutivas devem retornar o mesmo resultado');
    }
}
