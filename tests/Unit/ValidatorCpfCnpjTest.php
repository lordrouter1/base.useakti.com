<?php
namespace Akti\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Akti\Utils\Validator;

/**
 * Testes unitários do Validator — CPF, CNPJ e novos métodos.
 *
 * Verifica:
 * - Validação de CPF com dígitos verificadores
 * - Validação de CNPJ com dígitos verificadores
 * - Wrapper document() com person_type
 * - Método cpfOrCnpj() (detecção automática)
 * - Métodos dateNotFuture(), decimal(), between()
 * - Rejeição de sequências iguais
 * - Campos vazios/nulos não geram erro (validação skippada)
 *
 * @package Akti\Tests\Unit
 */
class ValidatorCpfCnpjTest extends TestCase
{
    // ══════════════════════════════════════════════════════════════
    // CPF — Helpers estáticos
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function cpf_valido_retorna_true(): void
    {
        $this->assertTrue(Validator::isValidCpf('52998224725'));
    }

    /** @test */
    public function cpf_valido_com_formatacao(): void
    {
        // isValidCpf limpa formatação internamente
        $this->assertTrue(Validator::isValidCpf('529.982.247-25'));
    }

    /** @test */
    public function cpf_invalido_digitos_errados(): void
    {
        $this->assertFalse(Validator::isValidCpf('12345678900'));
    }

    /** @test */
    public function cpf_todos_digitos_iguais_retorna_false(): void
    {
        $this->assertFalse(Validator::isValidCpf('11111111111'));
        $this->assertFalse(Validator::isValidCpf('00000000000'));
        $this->assertFalse(Validator::isValidCpf('99999999999'));
    }

    /** @test */
    public function cpf_com_menos_de_11_digitos_retorna_false(): void
    {
        $this->assertFalse(Validator::isValidCpf('123456'));
        $this->assertFalse(Validator::isValidCpf('1234567890'));
    }

    /** @test */
    public function cpf_com_mais_de_11_digitos_retorna_false(): void
    {
        $this->assertFalse(Validator::isValidCpf('123456789012'));
    }

    /** @test */
    public function cpf_vazio_retorna_false(): void
    {
        $this->assertFalse(Validator::isValidCpf(''));
    }

    // ══════════════════════════════════════════════════════════════
    // CNPJ — Helpers estáticos
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function cnpj_valido_retorna_true(): void
    {
        $this->assertTrue(Validator::isValidCnpj('11222333000181'));
    }

    /** @test */
    public function cnpj_valido_com_formatacao(): void
    {
        $this->assertTrue(Validator::isValidCnpj('11.222.333/0001-81'));
    }

    /** @test */
    public function cnpj_invalido_digitos_errados(): void
    {
        $this->assertFalse(Validator::isValidCnpj('12345678000199'));
    }

    /** @test */
    public function cnpj_todos_digitos_iguais_retorna_false(): void
    {
        $this->assertFalse(Validator::isValidCnpj('11111111111111'));
        $this->assertFalse(Validator::isValidCnpj('00000000000000'));
    }

    /** @test */
    public function cnpj_com_menos_de_14_digitos_retorna_false(): void
    {
        $this->assertFalse(Validator::isValidCnpj('1234567890'));
    }

    /** @test */
    public function cnpj_vazio_retorna_false(): void
    {
        $this->assertFalse(Validator::isValidCnpj(''));
    }

    // ══════════════════════════════════════════════════════════════
    // Validator encadeável — cpf(), cnpj(), document()
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function validator_cpf_valido_nao_gera_erro(): void
    {
        $v = new Validator();
        $v->cpf('document', '529.982.247-25', 'CPF');
        $this->assertTrue($v->passes());
    }

    /** @test */
    public function validator_cpf_invalido_gera_erro(): void
    {
        $v = new Validator();
        $v->cpf('document', '123.456.789-00', 'CPF');
        $this->assertTrue($v->fails());
        $this->assertNotNull($v->error('document'));
    }

    /** @test */
    public function validator_cnpj_valido_nao_gera_erro(): void
    {
        $v = new Validator();
        $v->cnpj('document', '11.222.333/0001-81', 'CNPJ');
        $this->assertTrue($v->passes());
    }

    /** @test */
    public function validator_cnpj_invalido_gera_erro(): void
    {
        $v = new Validator();
        $v->cnpj('document', '12.345.678/0001-99', 'CNPJ');
        $this->assertTrue($v->fails());
    }

    /** @test */
    public function validator_cpf_campo_vazio_nao_gera_erro(): void
    {
        $v = new Validator();
        $v->cpf('document', '', 'CPF');
        $this->assertTrue($v->passes());
    }

    /** @test */
    public function validator_cpf_campo_null_nao_gera_erro(): void
    {
        $v = new Validator();
        $v->cpf('document', null, 'CPF');
        $this->assertTrue($v->passes());
    }

    // ══════════════════════════════════════════════════════════════
    // Wrapper document() — roteamento PF/PJ
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function document_pf_valida_como_cpf(): void
    {
        $v = new Validator();
        $v->document('document', '52998224725', 'PF', 'Documento');
        $this->assertTrue($v->passes());
    }

    /** @test */
    public function document_pj_valida_como_cnpj(): void
    {
        $v = new Validator();
        $v->document('document', '11222333000181', 'PJ', 'Documento');
        $this->assertTrue($v->passes());
    }

    /** @test */
    public function document_pf_com_cnpj_gera_erro(): void
    {
        $v = new Validator();
        $v->document('document', '11222333000181', 'PF', 'Documento');
        $this->assertTrue($v->fails());
    }

    /** @test */
    public function document_pj_com_cpf_gera_erro(): void
    {
        $v = new Validator();
        $v->document('document', '52998224725', 'PJ', 'Documento');
        $this->assertTrue($v->fails());
    }

    /** @test */
    public function document_vazio_nao_gera_erro(): void
    {
        $v = new Validator();
        $v->document('document', '', 'PF', 'Documento');
        $this->assertTrue($v->passes());
    }

    /** @test */
    public function document_null_nao_gera_erro(): void
    {
        $v = new Validator();
        $v->document('document', null, 'PJ', 'Documento');
        $this->assertTrue($v->passes());
    }

    // ══════════════════════════════════════════════════════════════
    // cpfOrCnpj() — detecção automática
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function cpfOrCnpj_detecta_cpf_valido(): void
    {
        $v = new Validator();
        $v->cpfOrCnpj('document', '52998224725', 'Documento');
        $this->assertTrue($v->passes());
    }

    /** @test */
    public function cpfOrCnpj_detecta_cnpj_valido(): void
    {
        $v = new Validator();
        $v->cpfOrCnpj('document', '11222333000181', 'Documento');
        $this->assertTrue($v->passes());
    }

    /** @test */
    public function cpfOrCnpj_tamanho_invalido_gera_erro(): void
    {
        $v = new Validator();
        $v->cpfOrCnpj('document', '123456', 'Documento');
        $this->assertTrue($v->fails());
    }

    // ══════════════════════════════════════════════════════════════
    // dateNotFuture()
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function dateNotFuture_data_passada_nao_gera_erro(): void
    {
        $v = new Validator();
        $v->dateNotFuture('birth_date', '2000-01-15', 'Data de Nascimento');
        $this->assertTrue($v->passes());
    }

    /** @test */
    public function dateNotFuture_data_hoje_nao_gera_erro(): void
    {
        $v = new Validator();
        $v->dateNotFuture('birth_date', date('Y-m-d'), 'Data de Nascimento');
        $this->assertTrue($v->passes());
    }

    /** @test */
    public function dateNotFuture_data_futura_gera_erro(): void
    {
        $v = new Validator();
        $futureDate = date('Y-m-d', strtotime('+1 year'));
        $v->dateNotFuture('birth_date', $futureDate, 'Data de Nascimento');
        $this->assertTrue($v->fails());
        $this->assertNotNull($v->error('birth_date'));
    }

    /** @test */
    public function dateNotFuture_campo_vazio_nao_gera_erro(): void
    {
        $v = new Validator();
        $v->dateNotFuture('birth_date', '', 'Data de Nascimento');
        $this->assertTrue($v->passes());
    }

    // ══════════════════════════════════════════════════════════════
    // decimal()
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function decimal_positivo_nao_gera_erro(): void
    {
        $v = new Validator();
        $v->decimal('credit_limit', '1500.50', 'Limite de Crédito');
        $this->assertTrue($v->passes());
    }

    /** @test */
    public function decimal_zero_nao_gera_erro(): void
    {
        $v = new Validator();
        $v->decimal('credit_limit', '0', 'Limite de Crédito');
        $this->assertTrue($v->passes());
    }

    /** @test */
    public function decimal_negativo_gera_erro(): void
    {
        $v = new Validator();
        $v->decimal('credit_limit', '-100', 'Limite de Crédito');
        $this->assertTrue($v->fails());
    }

    /** @test */
    public function decimal_nao_numerico_gera_erro(): void
    {
        $v = new Validator();
        $v->decimal('credit_limit', 'abc', 'Limite de Crédito');
        $this->assertTrue($v->fails());
    }

    /** @test */
    public function decimal_vazio_nao_gera_erro(): void
    {
        $v = new Validator();
        $v->decimal('credit_limit', '', 'Limite de Crédito');
        $this->assertTrue($v->passes());
    }

    // ══════════════════════════════════════════════════════════════
    // between()
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function between_dentro_range_nao_gera_erro(): void
    {
        $v = new Validator();
        $v->between('discount', '15.5', 0, 100, 'Desconto');
        $this->assertTrue($v->passes());
    }

    /** @test */
    public function between_no_limite_inferior_nao_gera_erro(): void
    {
        $v = new Validator();
        $v->between('discount', '0', 0, 100, 'Desconto');
        $this->assertTrue($v->passes());
    }

    /** @test */
    public function between_no_limite_superior_nao_gera_erro(): void
    {
        $v = new Validator();
        $v->between('discount', '100', 0, 100, 'Desconto');
        $this->assertTrue($v->passes());
    }

    /** @test */
    public function between_acima_range_gera_erro(): void
    {
        $v = new Validator();
        $v->between('discount', '150', 0, 100, 'Desconto');
        $this->assertTrue($v->fails());
    }

    /** @test */
    public function between_abaixo_range_gera_erro(): void
    {
        $v = new Validator();
        $v->between('discount', '-5', 0, 100, 'Desconto');
        $this->assertTrue($v->fails());
    }

    /** @test */
    public function between_vazio_nao_gera_erro(): void
    {
        $v = new Validator();
        $v->between('discount', '', 0, 100, 'Desconto');
        $this->assertTrue($v->passes());
    }

    // ══════════════════════════════════════════════════════════════
    // Validação encadeada completa (simulação do Controller)
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function validacao_completa_cliente_pf_valido(): void
    {
        $v = new Validator();
        $v->required('person_type', 'PF', 'Tipo de Pessoa')
          ->inList('person_type', 'PF', ['PF', 'PJ'], 'Tipo de Pessoa')
          ->required('name', 'Maria da Silva', 'Nome')
          ->maxLength('name', 'Maria da Silva', 191, 'Nome')
          ->document('document', '52998224725', 'PF', 'CPF')
          ->email('email', 'maria@email.com', 'E-mail')
          ->dateNotFuture('birth_date', '1990-05-15', 'Data de Nascimento')
          ->decimal('credit_limit', '5000.00', 'Limite de Crédito')
          ->between('discount', '10', 0, 100, 'Desconto')
          ->url('website', 'https://www.maria.com.br', 'Website');

        $this->assertTrue($v->passes(), 'Validação completa PF válido deveria passar: ' . json_encode($v->errors()));
    }

    /** @test */
    public function validacao_completa_cliente_pj_valido(): void
    {
        $v = new Validator();
        $v->required('person_type', 'PJ', 'Tipo de Pessoa')
          ->inList('person_type', 'PJ', ['PF', 'PJ'], 'Tipo de Pessoa')
          ->required('name', 'Empresa ABC Ltda', 'Razão Social')
          ->maxLength('name', 'Empresa ABC Ltda', 191, 'Razão Social')
          ->document('document', '11222333000181', 'PJ', 'CNPJ')
          ->email('email', 'contato@abc.com.br', 'E-mail')
          ->decimal('credit_limit', '50000.00', 'Limite de Crédito')
          ->between('discount', '5', 0, 100, 'Desconto');

        $this->assertTrue($v->passes(), 'Validação completa PJ válido deveria passar: ' . json_encode($v->errors()));
    }

    /** @test */
    public function validacao_completa_cliente_sem_nome_gera_erro(): void
    {
        $v = new Validator();
        $v->required('name', '', 'Nome')
          ->document('document', '52998224725', 'PF', 'CPF');

        $this->assertTrue($v->fails());
        $this->assertNotNull($v->error('name'));
        $this->assertNull($v->error('document')); // CPF é válido
    }

    /** @test */
    public function validacao_campos_opcionais_vazios_passam(): void
    {
        $v = new Validator();
        $v->required('name', 'João', 'Nome')
          ->document('document', '', 'PF', 'CPF')
          ->email('email', '', 'E-mail')
          ->dateNotFuture('birth_date', '', 'Data de Nascimento')
          ->decimal('credit_limit', '', 'Limite de Crédito')
          ->between('discount', '', 0, 100, 'Desconto')
          ->url('website', '', 'Website');

        $this->assertTrue($v->passes(), 'Campos opcionais vazios não devem gerar erros');
    }

    /** @test */
    public function primeiro_erro_por_campo_prevalece(): void
    {
        $v = new Validator();
        $v->required('name', '', 'Nome')
          ->minLength('name', '', 3, 'Nome');

        $this->assertTrue($v->fails());
        $errors = $v->errors();
        // Deve ter apenas 1 erro para 'name' (o primeiro — required)
        $this->assertCount(1, $errors);
        $this->assertStringContainsString('obrigatório', $errors['name']);
    }
}
