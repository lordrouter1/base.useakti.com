<?php
namespace Akti\Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;
use Akti\Utils\Input;

/**
 * Testes unitários para Akti\Utils\Input.
 *
 * Cobre:
 * - Input::post() com vários tipos de sanitização
 * - Input::get() com vários tipos de sanitização
 * - Input::hasPost() e Input::hasGet()
 * - Input::allPost() e Input::allGet() para acesso em lote
 * - Input::postRaw() e Input::getRaw() (sem sanitização)
 * - Input::postArray() e Input::getArray()
 * - Valores default quando campo não existe
 * - Sanitização por tipo: string, int, float, email, bool, date, enum, etc.
 *
 * @package Akti\Tests\Unit\Utils
 */
class InputTest extends TestCase
{
    /**
     * Backup das superglobais para restaurar após cada teste.
     */
    private array $backupPost;
    private array $backupGet;
    private array $backupRequest;

    protected function setUp(): void
    {
        parent::setUp();
        $this->backupPost    = $_POST;
        $this->backupGet     = $_GET;
        $this->backupRequest = $_REQUEST;
    }

    protected function tearDown(): void
    {
        $_POST    = $this->backupPost;
        $_GET     = $this->backupGet;
        $_REQUEST = $this->backupRequest;
        parent::tearDown();
    }

    // ══════════════════════════════════════════════════════════════
    // Input::post()
    // ══════════════════════════════════════════════════════════════

    public function testPostStringDefault(): void
    {
        $_POST = ['name' => '  João Silva  '];
        $result = Input::post('name');
        $this->assertIsString($result);
        $this->assertSame('João Silva', $result);
    }

    public function testPostStringMissingReturnsDefault(): void
    {
        $_POST = [];
        $result = Input::post('name', 'string', 'padrão');
        $this->assertSame('padrão', $result);
    }

    public function testPostStringMissingReturnsNull(): void
    {
        $_POST = [];
        $result = Input::post('name');
        $this->assertNull($result);
    }

    public function testPostInt(): void
    {
        $_POST = ['quantity' => '42'];
        $result = Input::post('quantity', 'int');
        $this->assertSame(42, $result);
    }

    public function testPostIntInvalidReturnsDefault(): void
    {
        $_POST = ['quantity' => 'abc'];
        $result = Input::post('quantity', 'int', 0);
        $this->assertSame(0, $result);
    }

    public function testPostFloat(): void
    {
        $_POST = ['price' => '1.234,56'];
        $result = Input::post('price', 'float');
        $this->assertIsFloat($result);
        $this->assertEqualsWithDelta(1234.56, $result, 0.01);
    }

    public function testPostEmail(): void
    {
        $_POST = ['email' => '  Test@Example.COM  '];
        $result = Input::post('email', 'email');
        $this->assertSame('test@example.com', $result);
    }

    public function testPostEmailInvalid(): void
    {
        $_POST = ['email' => 'not-an-email'];
        $result = Input::post('email', 'email', 'fallback@x.com');
        // Should return the default or the sanitized value depending on implementation
        $this->assertIsString($result);
    }

    public function testPostBoolTrue(): void
    {
        $_POST = ['active' => '1'];
        $this->assertTrue(Input::post('active', 'bool'));
    }

    public function testPostBoolFalse(): void
    {
        $_POST = ['active' => '0'];
        $this->assertFalse(Input::post('active', 'bool'));
    }

    public function testPostBoolMissing(): void
    {
        $_POST = [];
        // bool type returns false when missing (per implementation)
        $result = Input::post('active', 'bool');
        $this->assertFalse($result);
    }

    public function testPostEnum(): void
    {
        $_POST = ['role' => 'admin'];
        $result = Input::post('role', 'enum', 'user', ['admin', 'user', 'viewer']);
        $this->assertSame('admin', $result);
    }

    public function testPostEnumInvalidReturnsDefault(): void
    {
        $_POST = ['role' => 'hacker'];
        $result = Input::post('role', 'enum', 'user', ['admin', 'user', 'viewer']);
        $this->assertSame('user', $result);
    }

    public function testPostDate(): void
    {
        $_POST = ['date' => '2026-03-30'];
        $result = Input::post('date', 'date');
        $this->assertSame('2026-03-30', $result);
    }

    public function testPostDocument(): void
    {
        $_POST = ['cpf' => '529.982.247-25'];
        $result = Input::post('cpf', 'document');
        $this->assertSame('52998224725', $result);
    }

    public function testPostPhone(): void
    {
        $_POST = ['phone' => '(11) 99999-0000'];
        $result = Input::post('phone', 'phone');
        // Sanitizer::phone keeps digits, +, (), -, spaces
        $this->assertSame('(11) 99999-0000', $result);
    }

    public function testPostCep(): void
    {
        $_POST = ['cep' => '01001-000'];
        $result = Input::post('cep', 'cep');
        $this->assertSame('01001000', $result);
    }

    // ══════════════════════════════════════════════════════════════
    // Input::get()
    // ══════════════════════════════════════════════════════════════

    public function testGetString(): void
    {
        $_GET = ['search' => '<script>alert("xss")</script>'];
        $result = Input::get('search');
        $this->assertStringNotContainsString('<script>', $result);
    }

    public function testGetInt(): void
    {
        $_GET = ['id' => '123'];
        $this->assertSame(123, Input::get('id', 'int'));
    }

    public function testGetMissingReturnsDefault(): void
    {
        $_GET = [];
        $this->assertSame('home', Input::get('page', 'string', 'home'));
    }

    // ══════════════════════════════════════════════════════════════
    // Input::hasPost() / Input::hasGet()
    // ══════════════════════════════════════════════════════════════

    public function testHasPostTrue(): void
    {
        $_POST = ['name' => 'João'];
        $this->assertTrue(Input::hasPost('name'));
    }

    public function testHasPostFalseEmpty(): void
    {
        $_POST = ['name' => ''];
        $this->assertFalse(Input::hasPost('name'));
    }

    public function testHasPostFalseMissing(): void
    {
        $_POST = [];
        $this->assertFalse(Input::hasPost('name'));
    }

    public function testHasGetTrue(): void
    {
        $_GET = ['page' => 'customers'];
        $this->assertTrue(Input::hasGet('page'));
    }

    public function testHasGetFalse(): void
    {
        $_GET = [];
        $this->assertFalse(Input::hasGet('page'));
    }

    // ══════════════════════════════════════════════════════════════
    // Input::allPost() / Input::allGet()
    // ══════════════════════════════════════════════════════════════

    public function testAllPostWithTypeMap(): void
    {
        $_POST = ['name' => ' João ', 'price' => '100,50', 'qty' => '3'];
        $result = Input::allPost([
            'name'  => 'string',
            'price' => 'float',
            'qty'   => 'int',
        ]);

        $this->assertSame('João', $result['name']);
        $this->assertEqualsWithDelta(100.50, $result['price'], 0.01);
        $this->assertSame(3, $result['qty']);
    }

    public function testAllPostWithSimpleList(): void
    {
        $_POST = ['name' => 'Ana', 'email' => 'ana@test.com'];
        $result = Input::allPost(['name', 'email']);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('email', $result);
        $this->assertSame('Ana', $result['name']);
    }

    public function testAllPostMissingFieldReturnsNull(): void
    {
        $_POST = ['name' => 'Ana'];
        $result = Input::allPost(['name', 'email']);
        $this->assertNull($result['email']);
    }

    public function testAllGetWithTypes(): void
    {
        $_GET = ['page' => 'orders', 'id' => '42'];
        $result = Input::allGet(['page' => 'string', 'id' => 'int']);
        $this->assertSame('orders', $result['page']);
        $this->assertSame(42, $result['id']);
    }

    // ══════════════════════════════════════════════════════════════
    // Input::postRaw() / Input::getRaw()
    // ══════════════════════════════════════════════════════════════

    public function testPostRawReturnsUnsanitized(): void
    {
        $_POST = ['password' => 'p@$$w0rd<>&'];
        $result = Input::postRaw('password');
        $this->assertSame('p@$$w0rd<>&', $result);
    }

    public function testPostRawMissingReturnsDefault(): void
    {
        $_POST = [];
        $this->assertSame('default', Input::postRaw('missing', 'default'));
    }

    public function testGetRawReturnsUnsanitized(): void
    {
        $_GET = ['token' => 'abc123+/='];
        $this->assertSame('abc123+/=', Input::getRaw('token'));
    }

    // ══════════════════════════════════════════════════════════════
    // Input::postArray() / Input::getArray()
    // ══════════════════════════════════════════════════════════════

    public function testPostArrayReturnsArray(): void
    {
        $_POST = ['ids' => [1, 2, 3]];
        $result = Input::postArray('ids');
        $this->assertSame([1, 2, 3], $result);
    }

    public function testPostArrayMissingReturnsEmptyArray(): void
    {
        $_POST = [];
        $result = Input::postArray('ids');
        $this->assertSame([], $result);
    }

    public function testPostArrayScalarReturnsEmptyArray(): void
    {
        $_POST = ['ids' => '123'];
        $result = Input::postArray('ids');
        $this->assertSame([], $result);
    }

    public function testGetArrayReturnsArray(): void
    {
        $_GET = ['tags' => ['a', 'b']];
        $result = Input::getArray('tags');
        $this->assertSame(['a', 'b'], $result);
    }

    public function testGetArrayMissingReturnsEmptyArray(): void
    {
        $_GET = [];
        $result = Input::getArray('tags');
        $this->assertSame([], $result);
    }

    // ══════════════════════════════════════════════════════════════
    // Input::request()
    // ══════════════════════════════════════════════════════════════

    public function testRequestReadsFromRequestSuperglobal(): void
    {
        $_REQUEST = ['key' => 'value'];
        $this->assertSame('value', Input::request('key'));
    }

    public function testRequestMissingReturnsDefault(): void
    {
        $_REQUEST = [];
        $this->assertSame('default', Input::request('missing', 'string', 'default'));
    }

    // ══════════════════════════════════════════════════════════════
    // Sanitização com XSS — garante remoção de tags
    // ══════════════════════════════════════════════════════════════

    public function testPostStripsXssFromString(): void
    {
        $_POST = ['comment' => '<img onerror="alert(1)" src=x>Texto'];
        $result = Input::post('comment');
        $this->assertStringNotContainsString('<img', $result);
        $this->assertStringContainsString('Texto', $result);
    }

    public function testPostUrlType(): void
    {
        $_POST = ['website' => 'https://example.com/path?q=1'];
        $result = Input::post('website', 'url');
        $this->assertSame('https://example.com/path?q=1', $result);
    }

    public function testPostIntArrayType(): void
    {
        $_POST = ['ids' => ['1', '2', 'abc', '3']];
        $result = Input::post('ids', 'intArray');
        $this->assertIsArray($result);
        // Should contain only valid integers
        foreach ($result as $v) {
            $this->assertIsInt($v);
        }
    }
}
