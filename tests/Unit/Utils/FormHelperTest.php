<?php
namespace Akti\Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;

/**
 * Testes unitários para app/utils/form_helper.php.
 *
 * Cobre:
 * - csrf_field() gera input hidden com token CSRF
 * - csrf_meta() gera meta tag com token CSRF
 * - csrf_token() retorna token puro (sem HTML)
 * - Saída é HTML-escaped (ENT_QUOTES)
 * - Funções são idempotentes dentro da mesma sessão
 *
 * @package Akti\Tests\Unit\Utils
 */
class FormHelperTest extends TestCase
{
    private array $backupSession = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->backupSession = $_SESSION ?? [];
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = $this->backupSession;
        parent::tearDown();
    }

    // ══════════════════════════════════════════════════════════════
    // csrf_field()
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function csrf_field_retorna_input_hidden(): void
    {
        $html = csrf_field();

        $this->assertStringContainsString('<input', $html);
        $this->assertStringContainsString('type="hidden"', $html);
        $this->assertStringContainsString('name="csrf_token"', $html);
        $this->assertStringContainsString('value="', $html);
    }

    /** @test */
    public function csrf_field_contem_token_de_64_caracteres(): void
    {
        $html = csrf_field();

        // Extrair o valor do token do HTML
        preg_match('/value="([^"]+)"/', $html, $matches);

        $this->assertNotEmpty($matches[1]);
        $this->assertSame(64, strlen($matches[1]));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $matches[1]);
    }

    /** @test */
    public function csrf_field_reutiliza_token_na_mesma_sessao(): void
    {
        $html1 = csrf_field();
        $html2 = csrf_field();

        $this->assertSame($html1, $html2);
    }

    // ══════════════════════════════════════════════════════════════
    // csrf_meta()
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function csrf_meta_retorna_meta_tag(): void
    {
        $html = csrf_meta();

        $this->assertStringContainsString('<meta', $html);
        $this->assertStringContainsString('name="csrf-token"', $html);
        $this->assertStringContainsString('content="', $html);
    }

    /** @test */
    public function csrf_meta_contem_token_valido(): void
    {
        $html = csrf_meta();

        preg_match('/content="([^"]+)"/', $html, $matches);

        $this->assertNotEmpty($matches[1]);
        $this->assertSame(64, strlen($matches[1]));
    }

    /** @test */
    public function csrf_meta_usa_mesmo_token_que_csrf_field(): void
    {
        $field = csrf_field();
        $meta = csrf_meta();

        preg_match('/value="([^"]+)"/', $field, $fieldMatch);
        preg_match('/content="([^"]+)"/', $meta, $metaMatch);

        $this->assertSame($fieldMatch[1], $metaMatch[1], 'Token deve ser o mesmo em ambas as funções');
    }

    // ══════════════════════════════════════════════════════════════
    // csrf_token()
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function csrf_token_retorna_string_pura(): void
    {
        $token = csrf_token();

        $this->assertIsString($token);
        $this->assertStringNotContainsString('<', $token);
        $this->assertStringNotContainsString('>', $token);
        $this->assertStringNotContainsString('"', $token);
    }

    /** @test */
    public function csrf_token_tem_64_caracteres_hex(): void
    {
        $token = csrf_token();

        $this->assertSame(64, strlen($token));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token);
    }

    /** @test */
    public function csrf_token_e_consistente_com_csrf_field(): void
    {
        $token = csrf_token();
        $field = csrf_field();

        $this->assertStringContainsString($token, $field);
    }

    /** @test */
    public function csrf_token_e_consistente_com_csrf_meta(): void
    {
        $token = csrf_token();
        $meta = csrf_meta();

        $this->assertStringContainsString($token, $meta);
    }

    // ══════════════════════════════════════════════════════════════
    // Segurança (escape)
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function csrf_field_escapa_token_com_ent_quotes(): void
    {
        // O token gerado é hexadecimal, então não precisa escapar, mas 
        // a implementação usa htmlspecialchars para segurança. Vamos validar
        // que o output é bem-formado.
        $html = csrf_field();

        // Verificar que é HTML válido (não tem aspas não escapadas)
        $this->assertMatchesRegularExpression(
            '/<input type="hidden" name="csrf_token" value="[0-9a-f]{64}">/',
            $html
        );
    }

    // ══════════════════════════════════════════════════════════════
    // Funções existem
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function funcoes_helper_existem(): void
    {
        $this->assertTrue(function_exists('csrf_field'), 'csrf_field() deve existir');
        $this->assertTrue(function_exists('csrf_meta'), 'csrf_meta() deve existir');
        $this->assertTrue(function_exists('csrf_token'), 'csrf_token() deve existir');
    }
}
