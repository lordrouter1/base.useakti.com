<?php
namespace Akti\Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;
use Akti\Utils\Validator;

/**
 * Testes unitários do Validator — Onda 1.
 *
 * Cobre todas as regras de validação:
 *   required, minLength, maxLength, email, integer, numeric, min, max,
 *   inList, date, url, regex, cpf, cnpj, cpfOrCnpj, passwordStrength,
 *   dateNotFuture, decimal, between, document, addError, reset, encadeamento
 *
 * @package Akti\Tests\Unit\Utils
 */
class ValidatorTest extends TestCase
{
    private Validator $v;

    protected function setUp(): void
    {
        parent::setUp();
        $this->v = new Validator();
    }

    // ══════════════════════════════════════════════════════════════
    // Estado básico
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function novo_validator_nao_tem_erros(): void
    {
        $this->assertTrue($this->v->passes());
        $this->assertFalse($this->v->fails());
        $this->assertEmpty($this->v->errors());
    }

    /** @test */
    public function addError_acumula_erros(): void
    {
        $this->v->addError('field', 'Erro manual');
        $this->assertTrue($this->v->fails());
        $this->assertSame('Erro manual', $this->v->error('field'));
    }

    /** @test */
    public function reset_limpa_todos_os_erros(): void
    {
        $this->v->addError('field', 'Erro');
        $this->assertTrue($this->v->fails());

        $this->v->reset();
        $this->assertTrue($this->v->passes());
    }

    /** @test */
    public function error_retorna_null_para_campo_sem_erro(): void
    {
        $this->assertNull($this->v->error('inexistente'));
    }

    // ══════════════════════════════════════════════════════════════
    // required
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function required_falha_para_null(): void
    {
        $this->v->required('name', null, 'Nome');
        $this->assertTrue($this->v->fails());
        $this->assertStringContainsString('Nome', $this->v->error('name'));
    }

    /** @test */
    public function required_falha_para_string_vazia(): void
    {
        $this->v->required('name', '', 'Nome');
        $this->assertTrue($this->v->fails());
    }

    /** @test */
    public function required_falha_para_array_vazio(): void
    {
        $this->v->required('items', [], 'Itens');
        $this->assertTrue($this->v->fails());
    }

    /** @test */
    public function required_passa_com_valor_preenchido(): void
    {
        $this->v->required('name', 'João', 'Nome');
        $this->assertTrue($this->v->passes());
    }

    // ══════════════════════════════════════════════════════════════
    // minLength / maxLength
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function minLength_falha_para_texto_curto(): void
    {
        $this->v->minLength('name', 'AB', 3, 'Nome');
        $this->assertTrue($this->v->fails());
    }

    /** @test */
    public function minLength_passa_para_texto_igual(): void
    {
        $this->v->minLength('name', 'ABC', 3, 'Nome');
        $this->assertTrue($this->v->passes());
    }

    /** @test */
    public function minLength_ignora_valor_vazio(): void
    {
        $this->v->minLength('name', '', 3, 'Nome');
        $this->assertTrue($this->v->passes());
    }

    /** @test */
    public function maxLength_falha_para_texto_longo(): void
    {
        $this->v->maxLength('name', 'ABCD', 3, 'Nome');
        $this->assertTrue($this->v->fails());
    }

    /** @test */
    public function maxLength_passa_para_texto_igual(): void
    {
        $this->v->maxLength('name', 'ABC', 3, 'Nome');
        $this->assertTrue($this->v->passes());
    }

    // ══════════════════════════════════════════════════════════════
    // email
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function email_passa_com_email_valido(): void
    {
        $this->v->email('email', 'user@example.com');
        $this->assertTrue($this->v->passes());
    }

    /** @test */
    public function email_falha_com_email_invalido(): void
    {
        $this->v->email('email', 'not-an-email');
        $this->assertTrue($this->v->fails());
    }

    /** @test */
    public function email_ignora_valor_vazio(): void
    {
        $this->v->email('email', '');
        $this->assertTrue($this->v->passes());
    }

    // ══════════════════════════════════════════════════════════════
    // integer / numeric
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function integer_passa_com_inteiro(): void
    {
        $this->v->integer('age', '25');
        $this->assertTrue($this->v->passes());
    }

    /** @test */
    public function integer_falha_com_texto(): void
    {
        $this->v->integer('age', 'abc');
        $this->assertTrue($this->v->fails());
    }

    /** @test */
    public function numeric_passa_com_float(): void
    {
        $this->v->numeric('price', '99.99');
        $this->assertTrue($this->v->passes());
    }

    /** @test */
    public function numeric_falha_com_texto(): void
    {
        $this->v->numeric('price', 'abc');
        $this->assertTrue($this->v->fails());
    }

    // ══════════════════════════════════════════════════════════════
    // min / max / between
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function min_falha_abaixo_do_minimo(): void
    {
        $this->v->min('qty', '3', 5, 'Quantidade');
        $this->assertTrue($this->v->fails());
    }

    /** @test */
    public function min_passa_no_limite(): void
    {
        $this->v->min('qty', '5', 5, 'Quantidade');
        $this->assertTrue($this->v->passes());
    }

    /** @test */
    public function max_falha_acima_do_maximo(): void
    {
        $this->v->max('qty', '15', 10, 'Quantidade');
        $this->assertTrue($this->v->fails());
    }

    /** @test */
    public function between_falha_fora_do_range(): void
    {
        $this->v->between('age', '150', 0, 120, 'Idade');
        $this->assertTrue($this->v->fails());
    }

    /** @test */
    public function between_passa_dentro_do_range(): void
    {
        $this->v->between('age', '30', 0, 120, 'Idade');
        $this->assertTrue($this->v->passes());
    }

    // ══════════════════════════════════════════════════════════════
    // inList
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function inList_passa_com_valor_permitido(): void
    {
        $this->v->inList('role', 'admin', ['admin', 'user']);
        $this->assertTrue($this->v->passes());
    }

    /** @test */
    public function inList_falha_com_valor_nao_permitido(): void
    {
        $this->v->inList('role', 'hacker', ['admin', 'user']);
        $this->assertTrue($this->v->fails());
    }

    // ══════════════════════════════════════════════════════════════
    // date
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function date_passa_com_formato_valido(): void
    {
        $this->v->date('birth', '2000-06-15');
        $this->assertTrue($this->v->passes());
    }

    /** @test */
    public function date_falha_com_formato_invalido(): void
    {
        $this->v->date('birth', '15/06/2000');
        $this->assertTrue($this->v->fails());
    }

    /** @test */
    public function date_falha_com_data_impossivel(): void
    {
        $this->v->date('birth', '2000-02-30');
        $this->assertTrue($this->v->fails());
    }

    // ══════════════════════════════════════════════════════════════
    // dateNotFuture
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function dateNotFuture_falha_com_data_futura(): void
    {
        $futureDate = (new \DateTime('+1 year'))->format('Y-m-d');
        $this->v->dateNotFuture('date', $futureDate, 'Data');
        $this->assertTrue($this->v->fails());
    }

    /** @test */
    public function dateNotFuture_passa_com_data_passada(): void
    {
        $this->v->dateNotFuture('date', '2020-01-01', 'Data');
        $this->assertTrue($this->v->passes());
    }

    // ══════════════════════════════════════════════════════════════
    // url
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function url_passa_com_url_valida(): void
    {
        $this->v->url('site', 'https://example.com');
        $this->assertTrue($this->v->passes());
    }

    /** @test */
    public function url_falha_com_url_invalida(): void
    {
        $this->v->url('site', 'not-a-url');
        $this->assertTrue($this->v->fails());
    }

    // ══════════════════════════════════════════════════════════════
    // regex
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function regex_passa_com_padrao_valido(): void
    {
        $this->v->regex('code', 'ABC-123', '/^[A-Z]+-\d+$/');
        $this->assertTrue($this->v->passes());
    }

    /** @test */
    public function regex_falha_com_padrao_invalido(): void
    {
        $this->v->regex('code', '123-abc', '/^[A-Z]+-\d+$/');
        $this->assertTrue($this->v->fails());
    }

    // ══════════════════════════════════════════════════════════════
    // CPF
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function cpf_valido_aceito(): void
    {
        // CPF válido: 529.982.247-25
        $this->v->cpf('cpf', '52998224725');
        $this->assertTrue($this->v->passes());
    }

    /** @test */
    public function cpf_invalido_rejeitado(): void
    {
        $this->v->cpf('cpf', '12345678901');
        $this->assertTrue($this->v->fails());
    }

    /** @test */
    public function cpf_sequencia_igual_rejeitada(): void
    {
        $this->v->cpf('cpf', '11111111111');
        $this->assertTrue($this->v->fails());
    }

    /** @test */
    public function cpf_com_mascara_aceito(): void
    {
        $this->v->cpf('cpf', '529.982.247-25');
        $this->assertTrue($this->v->passes());
    }

    // ══════════════════════════════════════════════════════════════
    // CNPJ
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function cnpj_valido_aceito(): void
    {
        // CNPJ válido: 11.222.333/0001-81
        $this->v->cnpj('cnpj', '11222333000181');
        $this->assertTrue($this->v->passes());
    }

    /** @test */
    public function cnpj_invalido_rejeitado(): void
    {
        $this->v->cnpj('cnpj', '12345678000190');
        $this->assertTrue($this->v->fails());
    }

    /** @test */
    public function cnpj_sequencia_igual_rejeitada(): void
    {
        $this->v->cnpj('cnpj', '11111111111111');
        $this->assertTrue($this->v->fails());
    }

    // ══════════════════════════════════════════════════════════════
    // cpfOrCnpj
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function cpfOrCnpj_aceita_cpf_valido(): void
    {
        $this->v->cpfOrCnpj('doc', '52998224725');
        $this->assertTrue($this->v->passes());
    }

    /** @test */
    public function cpfOrCnpj_aceita_cnpj_valido(): void
    {
        $this->v->cpfOrCnpj('doc', '11222333000181');
        $this->assertTrue($this->v->passes());
    }

    /** @test */
    public function cpfOrCnpj_rejeita_tamanho_invalido(): void
    {
        $this->v->cpfOrCnpj('doc', '123456');
        $this->assertTrue($this->v->fails());
    }

    // ══════════════════════════════════════════════════════════════
    // passwordStrength
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function passwordStrength_passa_com_senha_forte(): void
    {
        $this->v->passwordStrength('pass', 'Abcdefg1');
        $this->assertTrue($this->v->passes());
    }

    /** @test */
    public function passwordStrength_falha_sem_maiuscula(): void
    {
        $this->v->passwordStrength('pass', 'abcdefg1');
        $this->assertTrue($this->v->fails());
    }

    /** @test */
    public function passwordStrength_falha_sem_numero(): void
    {
        $this->v->passwordStrength('pass', 'Abcdefgh');
        $this->assertTrue($this->v->fails());
    }

    /** @test */
    public function passwordStrength_falha_curta(): void
    {
        $this->v->passwordStrength('pass', 'Ab1');
        $this->assertTrue($this->v->fails());
    }

    /** @test */
    public function passwordStrength_ignora_vazio(): void
    {
        $this->v->passwordStrength('pass', '');
        $this->assertTrue($this->v->passes());
    }

    // ══════════════════════════════════════════════════════════════
    // decimal
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function decimal_passa_com_positivo(): void
    {
        $this->v->decimal('price', '99.99');
        $this->assertTrue($this->v->passes());
    }

    /** @test */
    public function decimal_falha_com_negativo(): void
    {
        $this->v->decimal('price', '-5');
        $this->assertTrue($this->v->fails());
    }

    // ══════════════════════════════════════════════════════════════
    // Encadeamento
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function encadeamento_acumula_erros_de_campos_diferentes(): void
    {
        $this->v
            ->required('name', '', 'Nome')
            ->required('email', '', 'E-mail')
            ->required('phone', '11999998888', 'Telefone');

        $this->assertTrue($this->v->fails());
        $errors = $this->v->errors();
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayNotHasKey('phone', $errors);
    }

    /** @test */
    public function encadeamento_nao_sobrescreve_erro_existente(): void
    {
        $this->v
            ->required('name', '', 'Nome')
            ->minLength('name', '', 3, 'Nome');

        // Apenas o primeiro erro deve ser registrado
        $this->assertCount(1, $this->v->errors());
        $this->assertStringContainsString('obrigatório', $this->v->error('name'));
    }

    // ══════════════════════════════════════════════════════════════
    // Helpers estáticos (isValidCpf, isValidCnpj)
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function isValidCpf_estatico_funciona(): void
    {
        $this->assertTrue(Validator::isValidCpf('52998224725'));
        $this->assertFalse(Validator::isValidCpf('12345678901'));
        $this->assertFalse(Validator::isValidCpf('00000000000'));
    }

    /** @test */
    public function isValidCnpj_estatico_funciona(): void
    {
        $this->assertTrue(Validator::isValidCnpj('11222333000181'));
        $this->assertFalse(Validator::isValidCnpj('12345678000190'));
        $this->assertFalse(Validator::isValidCnpj('00000000000000'));
    }
}
