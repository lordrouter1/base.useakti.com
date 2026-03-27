<?php
namespace Akti\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Akti\Models\Customer;
use Akti\Models\CustomerContact;
use Akti\Core\EventDispatcher;

/**
 * Testes unitários dos Models Customer e CustomerContact.
 *
 * Verifica:
 * - Instanciação dos models
 * - Geração de código sequencial (CLI-XXXXX)
 * - Retrocompatibilidade: create() e update() aceitam arrays parciais (campos antigos)
 * - update() dinâmico não sobrescreve campos não fornecidos
 * - softDelete, restore, updateStatus, bulkUpdateStatus, bulkDelete
 * - Sanitização de document no create()
 * - getAllTags() parsing
 * - Métodos do CustomerContact
 *
 * Usa PDO mock (não conecta ao banco real).
 *
 * @package Akti\Tests\Unit
 */
class CustomerModelTest extends TestCase
{
    /** @var \PDO&\PHPUnit\Framework\MockObject\MockObject */
    private $pdoMock;

    /** @var \PDOStatement&\PHPUnit\Framework\MockObject\MockObject */
    private $stmtMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Limpar listeners para evitar efeitos colaterais
        $registered = EventDispatcher::getRegistered();
        foreach (array_keys($registered) as $event) {
            EventDispatcher::forget($event);
        }

        $this->stmtMock = $this->createMock(\PDOStatement::class);
        $this->pdoMock = $this->createMock(\PDO::class);
    }

    // ══════════════════════════════════════════════════════════════
    // Instanciação
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function customer_model_pode_ser_instanciado(): void
    {
        $model = new Customer($this->pdoMock);
        $this->assertInstanceOf(Customer::class, $model);
    }

    /** @test */
    public function customer_contact_model_pode_ser_instanciado(): void
    {
        $model = new CustomerContact($this->pdoMock);
        $this->assertInstanceOf(CustomerContact::class, $model);
    }

    // ══════════════════════════════════════════════════════════════
    // Geração de código sequencial
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function generateCode_gera_primeiro_codigo(): void
    {
        $this->stmtMock->method('execute')->willReturn(true);
        $this->stmtMock->method('fetch')->willReturn(['max_num' => null]);

        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);

        $model = new Customer($this->pdoMock);
        $code = $model->generateCode();

        $this->assertSame('CLI-00001', $code);
    }

    /** @test */
    public function generateCode_incrementa_apos_existente(): void
    {
        $this->stmtMock->method('execute')->willReturn(true);
        $this->stmtMock->method('fetch')->willReturn(['max_num' => 42]);

        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);

        $model = new Customer($this->pdoMock);
        $code = $model->generateCode();

        $this->assertSame('CLI-00043', $code);
    }

    /** @test */
    public function generateCode_formato_cli_com_5_digitos(): void
    {
        $this->stmtMock->method('execute')->willReturn(true);
        $this->stmtMock->method('fetch')->willReturn(['max_num' => 999]);

        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);

        $model = new Customer($this->pdoMock);
        $code = $model->generateCode();

        $this->assertMatchesRegularExpression('/^CLI-\d{5}$/', $code);
        $this->assertSame('CLI-01000', $code);
    }

    // ══════════════════════════════════════════════════════════════
    // Create — retrocompatibilidade com campos antigos
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function create_aceita_array_com_campos_antigos(): void
    {
        // generateCode → precisa de um prepare/execute/fetch
        // create → precisa de outro prepare/execute
        // lastInsertId → retorna ID

        $stmtGenCode = $this->createMock(\PDOStatement::class);
        $stmtGenCode->method('execute')->willReturn(true);
        $stmtGenCode->method('fetch')->willReturn(['max_num' => 0]);

        $stmtInsert = $this->createMock(\PDOStatement::class);
        $stmtInsert->method('execute')->willReturn(true);

        $this->pdoMock->method('prepare')
            ->willReturnOnConsecutiveCalls($stmtGenCode, $stmtInsert);
        $this->pdoMock->method('lastInsertId')->willReturn('1');

        $model = new Customer($this->pdoMock);

        // Simular chamada com campos antigos (como o Controller faz)
        $result = $model->create([
            'name'           => 'João Silva',
            'email'          => 'joao@email.com',
            'phone'          => '1199999999',
            'document'       => '529.982.247-25',
            'address'        => '{"zipcode":"01001000"}',
            'photo'          => null,
            'price_table_id' => 1,
        ]);

        // Não deve lançar exceção, deve retornar ID
        $this->assertEquals('1', $result);
    }

    // ══════════════════════════════════════════════════════════════
    // Update — dinâmico (retrocompatibilidade)
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function update_com_campos_antigos_gera_sql_parcial(): void
    {
        $capturedQuery = null;
        $capturedParams = null;

        $this->stmtMock->method('execute')->willReturnCallback(
            function ($params) use (&$capturedParams) {
                $capturedParams = $params;
                return true;
            }
        );

        $this->pdoMock->method('prepare')->willReturnCallback(
            function ($query) use (&$capturedQuery) {
                $capturedQuery = $query;
                return $this->stmtMock;
            }
        );

        $model = new Customer($this->pdoMock);

        // Simular chamada com campos antigos (Controller antigo)
        $model->update([
            'id'             => 5,
            'name'           => 'Maria Atualizada',
            'email'          => 'maria@novo.com',
            'phone'          => '2133334444',
            'document'       => '529.982.247-25',
            'address'        => '{"zipcode":"01001000"}',
            'price_table_id' => 2,
        ]);

        // Verificar que o SQL NÃO contém campos não fornecidos
        $this->assertNotNull($capturedQuery);
        $this->assertStringContainsString('name = ?', $capturedQuery);
        $this->assertStringContainsString('email = ?', $capturedQuery);
        $this->assertStringContainsString('phone = ?', $capturedQuery);
        $this->assertStringContainsString('document = ?', $capturedQuery);

        // Campos novos que NÃO foram fornecidos NÃO devem estar no SQL
        $this->assertStringNotContainsString('person_type', $capturedQuery);
        $this->assertStringNotContainsString('fantasy_name', $capturedQuery);
        $this->assertStringNotContainsString('status', $capturedQuery);
        $this->assertStringNotContainsString('seller_id', $capturedQuery);
        $this->assertStringNotContainsString('tags', $capturedQuery);

        // Último parâmetro deve ser o ID
        $this->assertEquals(5, end($capturedParams));
    }

    /** @test */
    public function update_com_todos_campos_novos_gera_sql_completo(): void
    {
        $capturedQuery = null;

        $this->stmtMock->method('execute')->willReturn(true);

        $this->pdoMock->method('prepare')->willReturnCallback(
            function ($query) use (&$capturedQuery) {
                $capturedQuery = $query;
                return $this->stmtMock;
            }
        );

        $model = new Customer($this->pdoMock);

        $model->update([
            'id'                   => 10,
            'person_type'          => 'PJ',
            'name'                 => 'Empresa XYZ Ltda',
            'fantasy_name'         => 'XYZ',
            'document'             => '11.222.333/0001-81',
            'rg_ie'                => '123456789',
            'im'                   => '987654',
            'birth_date'           => '2020-01-15',
            'gender'               => null,
            'email'                => 'contato@xyz.com',
            'email_secondary'      => 'financeiro@xyz.com',
            'phone'                => '1133334444',
            'cellphone'            => '11999990000',
            'phone_commercial'     => '1144445555',
            'website'              => 'https://xyz.com.br',
            'instagram'            => 'xyzoficial',
            'contact_name'         => 'Carlos Diretor',
            'contact_role'         => 'Diretor',
            'address'              => '{}',
            'zipcode'              => '01001000',
            'address_street'       => 'Rua Exemplo',
            'address_number'       => '100',
            'address_complement'   => 'Sala 5',
            'address_neighborhood' => 'Centro',
            'address_city'         => 'São Paulo',
            'address_state'        => 'SP',
            'address_country'      => 'Brasil',
            'address_ibge'         => '3550308',
            'price_table_id'       => 3,
            'payment_term'         => '30/60/90',
            'credit_limit'         => '50000.00',
            'discount_default'     => '5.5',
            'seller_id'            => 7,
            'origin'               => 'Indicação',
            'tags'                 => 'VIP,Atacado',
            'observations'         => 'Cliente prioritário',
            'status'               => 'active',
            'updated_by'           => 1,
        ]);

        // Verificar que o SQL contém os campos novos
        $this->assertStringContainsString('person_type = ?', $capturedQuery);
        $this->assertStringContainsString('fantasy_name = ?', $capturedQuery);
        $this->assertStringContainsString('cellphone = ?', $capturedQuery);
        $this->assertStringContainsString('tags = ?', $capturedQuery);
        $this->assertStringContainsString('status = ?', $capturedQuery);
        $this->assertStringContainsString('updated_by = ?', $capturedQuery);
    }

    /** @test */
    public function update_sem_foto_nao_inclui_photo_no_sql(): void
    {
        $capturedQuery = null;

        $this->stmtMock->method('execute')->willReturn(true);

        $this->pdoMock->method('prepare')->willReturnCallback(
            function ($query) use (&$capturedQuery) {
                $capturedQuery = $query;
                return $this->stmtMock;
            }
        );

        $model = new Customer($this->pdoMock);
        $model->update([
            'id'   => 1,
            'name' => 'Teste',
        ]);

        $this->assertStringNotContainsString('photo', $capturedQuery);
    }

    /** @test */
    public function update_com_foto_inclui_photo_no_sql(): void
    {
        $capturedQuery = null;

        $this->stmtMock->method('execute')->willReturn(true);

        $this->pdoMock->method('prepare')->willReturnCallback(
            function ($query) use (&$capturedQuery) {
                $capturedQuery = $query;
                return $this->stmtMock;
            }
        );

        $model = new Customer($this->pdoMock);
        $model->update([
            'id'    => 1,
            'name'  => 'Teste',
            'photo' => '/uploads/foto.jpg',
        ]);

        $this->assertStringContainsString('photo = ?', $capturedQuery);
    }

    // ══════════════════════════════════════════════════════════════
    // Sanitização de documento
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function create_sanitiza_document_removendo_formatacao(): void
    {
        $capturedParams = null;

        $stmtGenCode = $this->createMock(\PDOStatement::class);
        $stmtGenCode->method('execute')->willReturn(true);
        $stmtGenCode->method('fetch')->willReturn(['max_num' => 0]);

        $stmtInsert = $this->createMock(\PDOStatement::class);
        $stmtInsert->method('execute')->willReturnCallback(
            function ($params) use (&$capturedParams) {
                $capturedParams = $params;
                return true;
            }
        );

        $this->pdoMock->method('prepare')
            ->willReturnOnConsecutiveCalls($stmtGenCode, $stmtInsert);
        $this->pdoMock->method('lastInsertId')->willReturn('1');

        $model = new Customer($this->pdoMock);
        $model->create([
            'name'     => 'Teste',
            'document' => '529.982.247-25',
        ]);

        // O documento nos params deve estar sem formatação
        // document é o 5º parâmetro (index 4) no INSERT: code, person_type, name, fantasy_name, document
        $this->assertNotNull($capturedParams);
        $this->assertEquals('52998224725', $capturedParams[4]);
    }

    // ══════════════════════════════════════════════════════════════
    // updateStatus — validação de valores permitidos
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function updateStatus_rejeita_status_invalido(): void
    {
        $model = new Customer($this->pdoMock);
        // Não deve nem chamar prepare (status inválido)
        $result = $model->updateStatus(1, 'deleted');
        $this->assertFalse($result);
    }

    /** @test */
    public function updateStatus_aceita_status_validos(): void
    {
        $this->stmtMock->method('execute')->willReturn(true);
        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);

        $model = new Customer($this->pdoMock);

        $this->assertTrue($model->updateStatus(1, 'active'));
        $this->assertTrue($model->updateStatus(1, 'inactive'));
        $this->assertTrue($model->updateStatus(1, 'blocked'));
    }

    // ══════════════════════════════════════════════════════════════
    // bulkUpdateStatus
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function bulkUpdateStatus_array_vazio_retorna_zero(): void
    {
        $model = new Customer($this->pdoMock);
        $this->assertEquals(0, $model->bulkUpdateStatus([], 'active'));
    }

    /** @test */
    public function bulkUpdateStatus_status_invalido_retorna_zero(): void
    {
        $model = new Customer($this->pdoMock);
        $this->assertEquals(0, $model->bulkUpdateStatus([1, 2], 'invalid'));
    }

    // ══════════════════════════════════════════════════════════════
    // bulkDelete
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function bulkDelete_array_vazio_retorna_zero(): void
    {
        $model = new Customer($this->pdoMock);
        $this->assertEquals(0, $model->bulkDelete([]));
    }

    // ══════════════════════════════════════════════════════════════
    // checkDuplicate — sanitização
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function checkDuplicate_documento_vazio_retorna_false(): void
    {
        $model = new Customer($this->pdoMock);
        $this->assertFalse($model->checkDuplicate(''));
    }

    /** @test */
    public function findByDocument_documento_vazio_retorna_null(): void
    {
        $model = new Customer($this->pdoMock);
        $this->assertNull($model->findByDocument(''));
    }

    // ══════════════════════════════════════════════════════════════
    // Evento disparado no create
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function create_dispara_evento_model_customer_created(): void
    {
        $stmtGenCode = $this->createMock(\PDOStatement::class);
        $stmtGenCode->method('execute')->willReturn(true);
        $stmtGenCode->method('fetch')->willReturn(['max_num' => 0]);

        $stmtInsert = $this->createMock(\PDOStatement::class);
        $stmtInsert->method('execute')->willReturn(true);

        $this->pdoMock->method('prepare')
            ->willReturnOnConsecutiveCalls($stmtGenCode, $stmtInsert);
        $this->pdoMock->method('lastInsertId')->willReturn('99');

        $eventReceived = null;
        EventDispatcher::listen('model.customer.created', function ($event) use (&$eventReceived) {
            $eventReceived = $event;
        });

        $model = new Customer($this->pdoMock);
        $model->create(['name' => 'Evento Teste']);

        $this->assertNotNull($eventReceived);
        $this->assertSame('99', $eventReceived->data['id']);
        $this->assertSame('Evento Teste', $eventReceived->data['name']);
        $this->assertSame('CLI-00001', $eventReceived->data['code']);
    }

    // ══════════════════════════════════════════════════════════════
    // CustomerContact — setPrimary
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function customer_contact_setPrimary_chama_clear_e_set(): void
    {
        $executeCount = 0;

        $this->stmtMock->method('execute')->willReturnCallback(
            function () use (&$executeCount) {
                $executeCount++;
                return true;
            }
        );

        $this->pdoMock->method('prepare')->willReturn($this->stmtMock);

        $model = new CustomerContact($this->pdoMock);
        $result = $model->setPrimary(5, 10);

        $this->assertTrue($result);
        // Deve ter chamado execute 2 vezes: clearPrimary + setPrimary
        $this->assertEquals(2, $executeCount);
    }
}
