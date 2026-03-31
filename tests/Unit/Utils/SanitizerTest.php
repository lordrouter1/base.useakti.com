<?php
namespace Akti\Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;
use Akti\Utils\Sanitizer;

/**
 * Testes unitários do Sanitizer — Onda 1 (maior ROI).
 *
 * Cobre todos os tipos de sanitização:
 *   string, richText, int, float, bool, email, phone, document, cep,
 *   url, date, datetime, slug, intArray, stringArray, enum, filename, json
 *
 * @package Akti\Tests\Unit\Utils
 */
class SanitizerTest extends TestCase
{
    // ══════════════════════════════════════════════════════════════
    // Sanitizer::string()
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function string_remove_tags_e_faz_trim(): void
    {
        $this->assertSame('Hello', Sanitizer::string('  <b>Hello</b>  '));
    }

    /** @test */
    public function string_retorna_default_para_null(): void
    {
        $this->assertSame('fallback', Sanitizer::string(null, 'fallback'));
    }

    /** @test */
    public function string_retorna_vazio_para_false(): void
    {
        $this->assertSame('', Sanitizer::string(false));
    }

    /** @test */
    public function string_retorna_vazio_por_padrao(): void
    {
        $this->assertSame('', Sanitizer::string(null));
    }

    // ══════════════════════════════════════════════════════════════
    // Sanitizer::richText()
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function richText_preserva_tags_permitidas(): void
    {
        $input = '<b>Bold</b> <script>alert(1)</script> <i>Italic</i>';
        $result = Sanitizer::richText($input);
        $this->assertStringContainsString('<b>Bold</b>', $result);
        $this->assertStringContainsString('<i>Italic</i>', $result);
        $this->assertStringNotContainsString('<script>', $result);
    }

    /** @test */
    public function richText_retorna_vazio_para_null(): void
    {
        $this->assertSame('', Sanitizer::richText(null));
    }

    // ══════════════════════════════════════════════════════════════
    // Sanitizer::int()
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function int_converte_corretamente(): void
    {
        $this->assertSame(42, Sanitizer::int('42'));
        $this->assertSame(0, Sanitizer::int('0'));
        $this->assertSame(-5, Sanitizer::int('-5'));
    }

    /** @test */
    public function int_retorna_default_para_invalido(): void
    {
        $this->assertSame(10, Sanitizer::int('abc', 10));
        $this->assertNull(Sanitizer::int(''));
        $this->assertNull(Sanitizer::int(null));
    }

    /** @test */
    public function int_rejeita_float_string(): void
    {
        $this->assertSame(99, Sanitizer::int('3.14', 99));
    }

    // ══════════════════════════════════════════════════════════════
    // Sanitizer::float()
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function float_converte_formato_us(): void
    {
        $this->assertSame(1234.56, Sanitizer::float('1234.56'));
    }

    /** @test */
    public function float_converte_formato_ptbr(): void
    {
        $this->assertSame(1234.56, Sanitizer::float('1.234,56'));
    }

    /** @test */
    public function float_converte_formato_ptbr_simples(): void
    {
        $this->assertSame(1234.56, Sanitizer::float('1234,56'));
    }

    /** @test */
    public function float_retorna_default_para_invalido(): void
    {
        $this->assertNull(Sanitizer::float(''));
        $this->assertSame(0.0, Sanitizer::float('abc', 0.0));
    }

    /** @test */
    public function float_aceita_negativo_ptbr(): void
    {
        $this->assertSame(-99.50, Sanitizer::float('-99,50'));
    }

    // ══════════════════════════════════════════════════════════════
    // Sanitizer::bool()
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function bool_reconhece_valores_verdadeiros(): void
    {
        $trueValues = ['1', 'true', 'on', 'yes', 'sim', 1, true];
        foreach ($trueValues as $v) {
            $this->assertTrue(Sanitizer::bool($v), "Deveria ser true para: " . var_export($v, true));
        }
    }

    /** @test */
    public function bool_reconhece_valores_falsos(): void
    {
        $falseValues = ['0', 'false', 'off', 'no', 'nao', '', null, false];
        foreach ($falseValues as $v) {
            $this->assertFalse(Sanitizer::bool($v), "Deveria ser false para: " . var_export($v, true));
        }
    }

    // ══════════════════════════════════════════════════════════════
    // Sanitizer::email()
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function email_normaliza_para_lowercase(): void
    {
        $this->assertSame('user@example.com', Sanitizer::email('  User@Example.COM  '));
    }

    /** @test */
    public function email_retorna_default_para_vazio(): void
    {
        $this->assertSame('default@test.com', Sanitizer::email('', 'default@test.com'));
    }

    // ══════════════════════════════════════════════════════════════
    // Sanitizer::phone()
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function phone_mantem_digitos_e_caracteres_permitidos(): void
    {
        $this->assertSame('+55 (11) 99999-8888', Sanitizer::phone('+55 (11) 99999-8888'));
    }

    /** @test */
    public function phone_remove_caracteres_invalidos(): void
    {
        $result = Sanitizer::phone('abc+55(11)99999-8888xyz');
        $this->assertStringNotContainsString('abc', $result);
        $this->assertStringNotContainsString('xyz', $result);
    }

    // ══════════════════════════════════════════════════════════════
    // Sanitizer::document()
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function document_extrai_apenas_digitos(): void
    {
        $this->assertSame('12345678901', Sanitizer::document('123.456.789-01'));
        $this->assertSame('12345678000190', Sanitizer::document('12.345.678/0001-90'));
    }

    // ══════════════════════════════════════════════════════════════
    // Sanitizer::cep()
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function cep_extrai_apenas_digitos(): void
    {
        $this->assertSame('01001000', Sanitizer::cep('01001-000'));
    }

    // ══════════════════════════════════════════════════════════════
    // Sanitizer::url()
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function url_sanitiza_corretamente(): void
    {
        $this->assertSame('https://example.com', Sanitizer::url('  https://example.com  '));
    }

    /** @test */
    public function url_retorna_vazio_para_null(): void
    {
        $this->assertSame('', Sanitizer::url(null));
    }

    // ══════════════════════════════════════════════════════════════
    // Sanitizer::date()
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function date_aceita_formato_valido(): void
    {
        $this->assertSame('2025-12-31', Sanitizer::date('2025-12-31'));
    }

    /** @test */
    public function date_rejeita_formato_invalido(): void
    {
        $this->assertNull(Sanitizer::date('31/12/2025'));
        $this->assertNull(Sanitizer::date('not-a-date'));
    }

    /** @test */
    public function date_rejeita_data_impossivel(): void
    {
        $this->assertNull(Sanitizer::date('2025-02-30'));
    }

    /** @test */
    public function date_retorna_default_para_vazio(): void
    {
        $this->assertSame('2025-01-01', Sanitizer::date('', '2025-01-01'));
    }

    // ══════════════════════════════════════════════════════════════
    // Sanitizer::datetime()
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function datetime_aceita_formato_valido(): void
    {
        $this->assertSame('2025-12-31 23:59:59', Sanitizer::datetime('2025-12-31 23:59:59'));
    }

    /** @test */
    public function datetime_rejeita_formato_invalido(): void
    {
        $this->assertNull(Sanitizer::datetime('2025-12-31'));
        $this->assertNull(Sanitizer::datetime('abc'));
    }

    // ══════════════════════════════════════════════════════════════
    // Sanitizer::slug()
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function slug_gera_slug_correto(): void
    {
        $this->assertSame('meu-produto-especial', Sanitizer::slug('Meu Produto Especial!'));
    }

    /** @test */
    public function slug_remove_hifens_consecutivos(): void
    {
        $this->assertSame('a-b', Sanitizer::slug('a---b'));
    }

    /** @test */
    public function slug_retorna_vazio_para_null(): void
    {
        $this->assertSame('', Sanitizer::slug(null));
    }

    // ══════════════════════════════════════════════════════════════
    // Sanitizer::intArray()
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function intArray_filtra_e_converte(): void
    {
        $this->assertSame([1, 2, 3], Sanitizer::intArray(['1', '2', 'abc', '3']));
    }

    /** @test */
    public function intArray_retorna_vazio_para_nao_array(): void
    {
        $this->assertSame([], Sanitizer::intArray('not-array'));
    }

    // ══════════════════════════════════════════════════════════════
    // Sanitizer::stringArray()
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function stringArray_sanitiza_cada_item(): void
    {
        $result = Sanitizer::stringArray(['<b>A</b>', '  B  ', 'C']);
        $this->assertSame(['A', 'B', 'C'], $result);
    }

    // ══════════════════════════════════════════════════════════════
    // Sanitizer::enum()
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function enum_aceita_valor_na_lista(): void
    {
        $this->assertSame('admin', Sanitizer::enum('admin', ['admin', 'user']));
    }

    /** @test */
    public function enum_retorna_default_para_invalido(): void
    {
        $this->assertSame('user', Sanitizer::enum('hacker', ['admin', 'user'], 'user'));
    }

    // ══════════════════════════════════════════════════════════════
    // Sanitizer::filename()
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function filename_remove_path_traversal(): void
    {
        $this->assertSame('evil.txt', Sanitizer::filename('../../../evil.txt'));
    }

    /** @test */
    public function filename_remove_caracteres_perigosos(): void
    {
        $result = Sanitizer::filename('file name@#$.txt');
        $this->assertStringNotContainsString('@', $result);
        $this->assertStringNotContainsString('#', $result);
        $this->assertStringNotContainsString('$', $result);
    }

    // ══════════════════════════════════════════════════════════════
    // Sanitizer::json()
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function json_aceita_json_valido(): void
    {
        $result = Sanitizer::json('{"key":"value"}');
        $this->assertNotNull($result);
        $decoded = json_decode($result, true);
        $this->assertSame('value', $decoded['key']);
    }

    /** @test */
    public function json_retorna_default_para_invalido(): void
    {
        $this->assertNull(Sanitizer::json('not-json'));
        $this->assertSame('{}', Sanitizer::json('not-json', '{}'));
    }
}
