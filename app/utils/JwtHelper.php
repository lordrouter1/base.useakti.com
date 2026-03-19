<?php
namespace Akti\Utils;

/**
 * JWT Helper — Gera tokens JWT (HMAC-SHA256) compatíveis com jsonwebtoken do Node.js.
 *
 * Não depende de bibliotecas externas. Implementa manualmente o algoritmo HS256.
 * O segredo DEVE ser o mesmo configurado no Node.js (JWT_SECRET).
 */
class JwtHelper
{
    /**
     * Gera um JWT com payload personalizado.
     *
     * @param array  $payload  Dados a incluir no token (ex: user_id, tenant_id).
     * @param string $secret   Chave secreta compartilhada com o Node.js.
     * @param int    $ttl      Tempo de vida em segundos (padrão: 1 hora).
     * @return string Token JWT codificado.
     */
    public static function encode(array $payload, string $secret, int $ttl = 3600): string
    {
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT',
        ];

        $now = time();
        $payload['iat'] = $now;
        $payload['exp'] = $now + $ttl;

        $headerEncoded  = self::base64UrlEncode(json_encode($header));
        $payloadEncoded = self::base64UrlEncode(json_encode($payload));

        $signature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", $secret, true);
        $signatureEncoded = self::base64UrlEncode($signature);

        return "$headerEncoded.$payloadEncoded.$signatureEncoded";
    }

    /**
     * Base64 URL-safe encode (sem padding, + → -, / → _).
     */
    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
