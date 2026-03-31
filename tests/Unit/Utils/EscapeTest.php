<?php
namespace Akti\Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;
use Akti\Utils\Escape;

/**
 * Testes unitários do Escape — Onda 1 (prevenção XSS).
 *
 * Cobre todos os métodos de escape de saída:
 *   html, attr, js, url, css, number
 *
 * @package Akti\Tests\Unit\Utils
 */
class EscapeTest extends TestCase
{
    // ══════════════════════════════════════════════════════════════
    // Escape::html()
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function html_escapa_tags_html(): void
    {
        $this->assertSame('&lt;script&gt;alert(1)&lt;/script&gt;', Escape::html('<script>alert(1)</script>'));
    }

    /** @test */
    public function html_escapa_aspas(): void
    {
        $result = Escape::html('"hello" & \'world\'');
        $this->assertStringContainsString('&quot;', $result);
        $this->assertStringContainsString('&#039;', $result);
        $this->assertStringContainsString('&amp;', $result);
    }

    /** @test */
    public function html_retorna_vazio_para_null(): void
    {
        $this->assertSame('', Escape::html(null));
    }

    /** @test */
    public function html_retorna_vazio_para_false(): void
    {
        $this->assertSame('', Escape::html(false));
    }

    /** @test */
    public function html_preserva_texto_seguro(): void
    {
        $this->assertSame('Hello World', Escape::html('Hello World'));
    }

    // ══════════════════════════════════════════════════════════════
    // Escape::attr()
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function attr_escapa_aspas_em_atributos(): void
    {
        $result = Escape::attr('value" onclick="alert(1)');
        $this->assertStringContainsString('&quot;', $result);
        $this->assertStringNotContainsString('"', $result);
    }

    /** @test */
    public function attr_retorna_vazio_para_null(): void
    {
        $this->assertSame('', Escape::attr(null));
    }

    // ══════════════════════════════════════════════════════════════
    // Escape::js()
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function js_retorna_json_seguro_para_string(): void
    {
        $result = Escape::js('Hello <script>');
        $this->assertStringNotContainsString('<', $result);
        $this->assertStringContainsString('\u003C', $result);
    }

    /** @test */
    public function js_escapa_aspas(): void
    {
        $result = Escape::js("it's a \"test\"");
        $this->assertStringNotContainsString("'", $result);
        $this->assertStringNotContainsString('"test"', $result);
    }

    /** @test */
    public function js_converte_array(): void
    {
        $result = Escape::js(['a' => 1, 'b' => '<tag>']);
        $decoded = json_decode($result, true);
        $this->assertSame(1, $decoded['a']);
        $this->assertSame('<tag>', $decoded['b']);
    }

    /** @test */
    public function js_retorna_string_vazia_para_falha(): void
    {
        // INF causa falha no json_encode
        $result = Escape::js(INF);
        $this->assertSame('""', $result);
    }

    // ══════════════════════════════════════════════════════════════
    // Escape::url()
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function url_codifica_caracteres_especiais(): void
    {
        $this->assertSame('hello%20world', Escape::url('hello world'));
        $this->assertSame('a%26b', Escape::url('a&b'));
    }

    /** @test */
    public function url_retorna_vazio_para_null(): void
    {
        $this->assertSame('', Escape::url(null));
    }

    // ══════════════════════════════════════════════════════════════
    // Escape::css()
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function css_preserva_valores_seguros(): void
    {
        $this->assertSame('#ff0000', Escape::css('#ff0000'));
        $this->assertSame('10px', Escape::css('10px'));
    }

    /** @test */
    public function css_remove_caracteres_perigosos(): void
    {
        $result = Escape::css('expression(alert(1))');
        $this->assertStringNotContainsString('(', $result);
        $this->assertStringNotContainsString(')', $result);
    }

    /** @test */
    public function css_retorna_vazio_para_null(): void
    {
        $this->assertSame('', Escape::css(null));
    }

    // ══════════════════════════════════════════════════════════════
    // Escape::number()
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function number_formata_padrao_br(): void
    {
        $this->assertSame('1.234,56', Escape::number(1234.56));
    }

    /** @test */
    public function number_formata_zero(): void
    {
        $this->assertSame('0,00', Escape::number(0));
        $this->assertSame('0,00', Escape::number(null));
        $this->assertSame('0,00', Escape::number(''));
    }

    /** @test */
    public function number_respeita_casas_decimais(): void
    {
        $this->assertSame('100', Escape::number(100, 0));
        $this->assertSame('100,000', Escape::number(100, 3));
    }

    /** @test */
    public function number_aceita_separadores_customizados(): void
    {
        $this->assertSame('1,234.56', Escape::number(1234.56, 2, '.', ','));
    }
}
