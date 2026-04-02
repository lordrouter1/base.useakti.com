<?php

namespace Akti\Tests\Security;

use Akti\Tests\TestCase;

/**
 * Testes de segurança ofensivos — XSS, SQLi, Auth Bypass.
 *
 * Verifica que o sistema rejeita payloads maliciosos
 * e não expõe dados sensíveis em respostas.
 *
 * @package Akti\Tests\Security
 */
class OffensiveSecurityTest extends TestCase
{
    // ══════════════════════════════════════════════════════════════
    // XSS — Cross-Site Scripting (via parâmetros GET)
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function test_xss_em_busca_produtos(): void
    {
        $payload = '<script>alert(1)</script>';
        $response = $this->httpGet('?page=products&search=' . urlencode($payload));
        $this->assertStringNotContainsString($payload, $response['body'],
            'XSS payload em param search não deve refletir sem escape');
    }

    /** @test */
    public function test_xss_em_busca_clientes(): void
    {
        $payload = '"><img src=x onerror=alert(1)>';
        $response = $this->httpGet('?page=customers&search=' . urlencode($payload));
        $this->assertStringNotContainsString($payload, $response['body'],
            'XSS payload em busca de clientes não deve refletir sem escape');
    }

    /** @test */
    public function test_xss_em_parametro_page(): void
    {
        $payload = '<script>document.cookie</script>';
        $response = $this->httpGet('?page=' . urlencode($payload));
        $this->assertStringNotContainsString($payload, $response['body'],
            'XSS payload em param page não deve refletir sem escape');
    }

    // ══════════════════════════════════════════════════════════════
    // SQL Injection (via parâmetros GET)
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function test_sqli_em_busca_produtos(): void
    {
        $payload = "' OR '1'='1' --";
        $response = $this->httpGet('?page=products&search=' . urlencode($payload));
        $this->assertNoPhpErrors($response['body'], 'SQLi em busca produtos');
    }

    /** @test */
    public function test_sqli_em_busca_clientes(): void
    {
        $payload = "1; DROP TABLE customers; --";
        $response = $this->httpGet('?page=customers&search=' . urlencode($payload));
        $this->assertNoPhpErrors($response['body'], 'SQLi em busca clientes');
    }

    /** @test */
    public function test_sqli_em_id_numerico(): void
    {
        $payload = "1 OR 1=1";
        $response = $this->httpGet('?page=products&action=edit&id=' . urlencode($payload));
        $this->assertNoPhpErrors($response['body'], 'SQLi em ID numérico');
    }

    // ══════════════════════════════════════════════════════════════
    // Auth Bypass — Acesso sem login
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function test_acesso_sem_login_redireciona(): void
    {
        // Usar cookie jar separado (sem login)
        $ch = curl_init();
        $tempCookie = tempnam(sys_get_temp_dir(), 'akti_noauth_');
        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->baseUrl . '/?page=products',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_COOKIEJAR      => $tempCookie,
            CURLOPT_COOKIEFILE     => $tempCookie,
            CURLOPT_TIMEOUT        => $this->timeout,
        ]);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        @unlink($tempCookie);

        // Deve redirecionar (302/303) ou mostrar login (200 com form)
        $this->assertTrue(
            in_array($status, [200, 302, 303], true),
            "Acesso sem login deve redirecionar ou mostrar login (status: {$status})"
        );

        if ($status === 200 && $body !== false) {
            $hasLoginIndicator = (
                stripos($body, 'login') !== false
                || stripos($body, 'password') !== false
                || stripos($body, 'senha') !== false
                || stripos($body, 'csrf_token') !== false
            );
            $this->assertTrue(
                $hasLoginIndicator,
                'Página sem login deve conter formulário de autenticação'
            );
        }
    }

    // ══════════════════════════════════════════════════════════════
    // Path Traversal
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function test_path_traversal_em_page(): void
    {
        $payload = '../../../etc/passwd';
        $response = $this->httpGet('?page=' . urlencode($payload));
        $this->assertNoPhpErrors($response['body'], 'Path traversal em page');
        $this->assertStringNotContainsString('root:', $response['body'],
            'Path traversal não deve expor conteúdo do sistema');
    }
}
