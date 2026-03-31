<?php
namespace Akti\Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;
use Akti\Utils\JwtHelper;

/**
 * Testes unitários para Akti\Utils\JwtHelper.
 *
 * Cobre:
 * - Estrutura do JWT (3 partes separadas por ponto)
 * - Header contém alg=HS256 e typ=JWT
 * - Payload contém dados passados + iat + exp
 * - TTL padrão é 3600 segundos
 * - TTL customizado é respeitado
 * - Assinatura é válida com a chave secreta correta
 * - Assinatura é inválida com chave errada
 * - base64UrlEncode() é URL-safe
 * - Tokens são determinísticos (mesmos dados + mesmo tempo = mesmo resultado)
 *
 * @package Akti\Tests\Unit\Utils
 */
class JwtHelperTest extends TestCase
{
    private string $secret = 'test_secret_key_for_unit_tests_2026';

    // ══════════════════════════════════════════════════════════════
    // Estrutura do JWT
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function encode_retorna_string_com_tres_partes(): void
    {
        $token = JwtHelper::encode(['user_id' => 1], $this->secret);
        $parts = explode('.', $token);

        $this->assertCount(3, $parts, 'JWT deve ter 3 partes separadas por ponto');
    }

    /** @test */
    public function encode_header_contem_alg_e_typ(): void
    {
        $token = JwtHelper::encode(['user_id' => 1], $this->secret);
        $parts = explode('.', $token);

        $header = json_decode($this->base64UrlDecode($parts[0]), true);

        $this->assertSame('HS256', $header['alg']);
        $this->assertSame('JWT', $header['typ']);
    }

    /** @test */
    public function encode_payload_contem_dados_passados(): void
    {
        $payload = ['user_id' => 42, 'tenant_id' => 7, 'role' => 'admin'];
        $token = JwtHelper::encode($payload, $this->secret);
        $parts = explode('.', $token);

        $decoded = json_decode($this->base64UrlDecode($parts[1]), true);

        $this->assertSame(42, $decoded['user_id']);
        $this->assertSame(7, $decoded['tenant_id']);
        $this->assertSame('admin', $decoded['role']);
    }

    /** @test */
    public function encode_payload_contem_iat_e_exp(): void
    {
        $before = time();
        $token = JwtHelper::encode(['test' => true], $this->secret);
        $after = time();
        $parts = explode('.', $token);

        $decoded = json_decode($this->base64UrlDecode($parts[1]), true);

        $this->assertArrayHasKey('iat', $decoded);
        $this->assertArrayHasKey('exp', $decoded);
        $this->assertGreaterThanOrEqual($before, $decoded['iat']);
        $this->assertLessThanOrEqual($after, $decoded['iat']);
    }

    // ══════════════════════════════════════════════════════════════
    // TTL
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function encode_ttl_padrao_e_3600(): void
    {
        $token = JwtHelper::encode(['x' => 1], $this->secret);
        $parts = explode('.', $token);
        $decoded = json_decode($this->base64UrlDecode($parts[1]), true);

        $this->assertSame($decoded['iat'] + 3600, $decoded['exp']);
    }

    /** @test */
    public function encode_ttl_customizado(): void
    {
        $token = JwtHelper::encode(['x' => 1], $this->secret, 7200);
        $parts = explode('.', $token);
        $decoded = json_decode($this->base64UrlDecode($parts[1]), true);

        $this->assertSame($decoded['iat'] + 7200, $decoded['exp']);
    }

    /** @test */
    public function encode_ttl_curto(): void
    {
        $token = JwtHelper::encode(['x' => 1], $this->secret, 60);
        $parts = explode('.', $token);
        $decoded = json_decode($this->base64UrlDecode($parts[1]), true);

        $this->assertSame($decoded['iat'] + 60, $decoded['exp']);
    }

    // ══════════════════════════════════════════════════════════════
    // Assinatura
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function encode_assinatura_valida_com_mesma_chave(): void
    {
        $token = JwtHelper::encode(['user_id' => 1], $this->secret);
        $parts = explode('.', $token);

        $expectedSignature = hash_hmac('sha256', "{$parts[0]}.{$parts[1]}", $this->secret, true);
        $expectedEncoded = $this->base64UrlEncode($expectedSignature);

        $this->assertSame($expectedEncoded, $parts[2]);
    }

    /** @test */
    public function encode_assinatura_difere_com_chave_errada(): void
    {
        $token = JwtHelper::encode(['user_id' => 1], $this->secret);
        $parts = explode('.', $token);

        $wrongSignature = hash_hmac('sha256', "{$parts[0]}.{$parts[1]}", 'chave_errada', true);
        $wrongEncoded = $this->base64UrlEncode($wrongSignature);

        $this->assertNotSame($wrongEncoded, $parts[2]);
    }

    /** @test */
    public function encode_payloads_diferentes_geram_assinaturas_diferentes(): void
    {
        $token1 = JwtHelper::encode(['user_id' => 1], $this->secret);
        $token2 = JwtHelper::encode(['user_id' => 2], $this->secret);

        $parts1 = explode('.', $token1);
        $parts2 = explode('.', $token2);

        $this->assertNotSame($parts1[2], $parts2[2]);
    }

    // ══════════════════════════════════════════════════════════════
    // base64UrlEncode() via Reflection
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function base64UrlEncode_e_url_safe(): void
    {
        $method = new \ReflectionMethod(JwtHelper::class, 'base64UrlEncode');
        $method->setAccessible(true);

        $result = $method->invoke(null, 'test string with +/= chars');

        $this->assertStringNotContainsString('+', $result);
        $this->assertStringNotContainsString('/', $result);
        $this->assertStringNotContainsString('=', $result);
    }

    /** @test */
    public function base64UrlEncode_pode_ser_decodificado(): void
    {
        $method = new \ReflectionMethod(JwtHelper::class, 'base64UrlEncode');
        $method->setAccessible(true);

        $original = 'Dados de teste: àéîõü @#$%';
        $encoded = $method->invoke(null, $original);
        $decoded = $this->base64UrlDecode($encoded);

        $this->assertSame($original, $decoded);
    }

    // ══════════════════════════════════════════════════════════════
    // Payload vazio
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function encode_funciona_com_payload_vazio(): void
    {
        $token = JwtHelper::encode([], $this->secret);
        $parts = explode('.', $token);

        $decoded = json_decode($this->base64UrlDecode($parts[1]), true);

        $this->assertArrayHasKey('iat', $decoded);
        $this->assertArrayHasKey('exp', $decoded);
        $this->assertCount(2, $decoded, 'Payload vazio deve ter apenas iat e exp');
    }

    /** @test */
    public function encode_funciona_com_payload_complexo(): void
    {
        $payload = [
            'user_id'   => 1,
            'tenant_id' => 3,
            'roles'     => ['admin', 'editor'],
            'meta'      => ['key' => 'value'],
        ];

        $token = JwtHelper::encode($payload, $this->secret);
        $parts = explode('.', $token);
        $decoded = json_decode($this->base64UrlDecode($parts[1]), true);

        $this->assertSame(['admin', 'editor'], $decoded['roles']);
        $this->assertSame(['key' => 'value'], $decoded['meta']);
    }

    // ══════════════════════════════════════════════════════════════
    // Helpers internos do teste
    // ══════════════════════════════════════════════════════════════

    private function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
