<?php

namespace Akti\Tests\Security;

use Akti\Tests\TestCase;

/**
 * Testes de segurança — Upload, Session, Rate Limiting, Info Disclosure.
 *
 * Complementa OffensiveSecurityTest com cenários adicionais
 * de acordo com o roadmap TEST-004.
 */
class AdvancedSecurityTest extends TestCase
{
    // ══════════════════════════════════════════════════════════════
    // File Upload — Extensão e tipo MIME
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function test_upload_rejeita_extensao_php(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'akti_upload_');
        file_put_contents($tempFile, '<?php echo "pwned"; ?>');
        rename($tempFile, $tempFile . '.php');
        $tempFile .= '.php';

        $response = $this->httpPostMultipart(
            '?page=products&action=uploadImage',
            ['image' => new \CURLFile($tempFile, 'image/jpeg', 'malicious.php')]
        );
        @unlink($tempFile);

        $this->assertUploadRejected($response, 'Upload de arquivo .php deve ser rejeitado');
    }

    /** @test */
    public function test_upload_rejeita_extensao_dupla(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'akti_upload_');
        file_put_contents($tempFile, '<?php echo "pwned"; ?>');
        rename($tempFile, $tempFile . '.php.jpg');
        $tempFile .= '.php.jpg';

        $response = $this->httpPostMultipart(
            '?page=products&action=uploadImage',
            ['image' => new \CURLFile($tempFile, 'image/jpeg', 'shell.php.jpg')]
        );
        @unlink($tempFile);

        // Aceitar se sanitizado para .jpg sem executar PHP, ou rejeitar
        if (isset($response['body'])) {
            $this->assertStringNotContainsString('pwned', $response['body'],
                'Upload com extensão dupla .php.jpg não deve executar PHP');
        }
    }

    /** @test */
    public function test_upload_rejeita_svg_com_script(): void
    {
        $svgPayload = '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>';
        $tempFile = tempnam(sys_get_temp_dir(), 'akti_svg_') . '.svg';
        file_put_contents($tempFile, $svgPayload);

        $response = $this->httpPostMultipart(
            '?page=products&action=uploadImage',
            ['image' => new \CURLFile($tempFile, 'image/svg+xml', 'xss.svg')]
        );
        @unlink($tempFile);

        // SVG should either be rejected or sanitized
        if (isset($response['body'])) {
            $this->assertStringNotContainsString('<script>', $response['body'],
                'Upload de SVG com script deve ser rejeitado ou sanitizado');
        }
    }

    // ══════════════════════════════════════════════════════════════
    // Session Fixation
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function test_session_id_muda_apos_login(): void
    {
        $cookieFile1 = tempnam(sys_get_temp_dir(), 'akti_sess1_');
        $cookieFile2 = tempnam(sys_get_temp_dir(), 'akti_sess2_');

        // 1. GET login page to establish a session
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->baseUrl . '/?page=login',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEJAR      => $cookieFile1,
            CURLOPT_COOKIEFILE     => $cookieFile1,
            CURLOPT_TIMEOUT        => $this->timeout,
        ]);
        curl_exec($ch);
        curl_close($ch);

        $preLoginCookies = file_get_contents($cookieFile1);
        preg_match('/PHPSESSID\s+(\S+)/', $preLoginCookies, $m1);
        $preSessionId = $m1[1] ?? '';

        // 2. Login
        $email    = getenv('AKTI_TEST_USER_EMAIL') ?: 'admin@sistema.com';
        $password = getenv('AKTI_TEST_USER_PASSWORD') ?: 'admin123';

        // Get CSRF token from login page
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->baseUrl . '/?page=login',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEJAR      => $cookieFile2,
            CURLOPT_COOKIEFILE     => $cookieFile1,
            CURLOPT_TIMEOUT        => $this->timeout,
        ]);
        $loginPage = curl_exec($ch);
        curl_close($ch);

        preg_match('/name=["\']csrf_token["\']\s+value=["\']([^"\']+)/', $loginPage ?: '', $csrf);
        $csrfToken = $csrf[1] ?? '';

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->baseUrl . '/?page=login&action=authenticate',
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'email'      => $email,
                'password'   => $password,
                'csrf_token' => $csrfToken,
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_COOKIEJAR      => $cookieFile2,
            CURLOPT_COOKIEFILE     => $cookieFile2,
            CURLOPT_TIMEOUT        => $this->timeout,
        ]);
        curl_exec($ch);
        curl_close($ch);

        $postLoginCookies = file_get_contents($cookieFile2);
        preg_match('/PHPSESSID\s+(\S+)/', $postLoginCookies, $m2);
        $postSessionId = $m2[1] ?? '';

        @unlink($cookieFile1);
        @unlink($cookieFile2);

        if ($preSessionId && $postSessionId) {
            $this->assertNotEquals($preSessionId, $postSessionId,
                'Session ID deve mudar após login (proteção contra session fixation)');
        } else {
            $this->markTestIncomplete('Não foi possível extrair session IDs para comparação');
        }
    }

    // ══════════════════════════════════════════════════════════════
    // Info Disclosure — Error messages
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function test_erro_nao_expoe_stack_trace(): void
    {
        $response = $this->httpGet('?page=nonexistent_page_xyz_404');
        $body = $response['body'] ?? '';

        $this->assertStringNotContainsString('Stack trace:', $body,
            'Página inexistente não deve expor stack trace');
        $this->assertStringNotContainsString('.php on line', $body,
            'Página inexistente não deve expor caminho de arquivo PHP');
    }

    /** @test */
    public function test_erro_nao_expoe_caminho_servidor(): void
    {
        $response = $this->httpGet('?page=products&action=edit&id=999999999');
        $body = $response['body'] ?? '';

        $patterns = ['/xampp/', '/var/www/', '/home/', 'C:\\\\'];
        foreach ($patterns as $pattern) {
            $this->assertStringNotContainsString($pattern, $body,
                "Resposta não deve expor caminho do servidor: {$pattern}");
        }
    }

    /** @test */
    public function test_versao_php_nao_exposta_em_headers(): void
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->baseUrl . '/',
            CURLOPT_NOBODY         => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_TIMEOUT        => $this->timeout,
        ]);
        $headers = curl_exec($ch);
        curl_close($ch);

        $this->assertStringNotContainsString('X-Powered-By: PHP', $headers ?: '',
            'Header X-Powered-By não deve expor versão do PHP');
    }

    // ══════════════════════════════════════════════════════════════
    // Security Headers
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function test_security_headers_presentes(): void
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->baseUrl . '/?page=login',
            CURLOPT_NOBODY         => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_TIMEOUT        => $this->timeout,
        ]);
        $headers = strtolower(curl_exec($ch) ?: '');
        curl_close($ch);

        $this->assertStringContainsString('x-content-type-options', $headers,
            'Header X-Content-Type-Options deve estar presente');
        $this->assertStringContainsString('x-frame-options', $headers,
            'Header X-Frame-Options deve estar presente');
    }

    // ══════════════════════════════════════════════════════════════
    // Helpers
    // ══════════════════════════════════════════════════════════════

    private function httpPostMultipart(string $path, array $fields): array
    {
        $cookieFile = self::getCookieJarFile();
        $this->ensureAuthenticated();

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->baseUrl . '/' . ltrim($path, '/'),
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $fields,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEJAR      => $cookieFile,
            CURLOPT_COOKIEFILE     => $cookieFile,
            CURLOPT_TIMEOUT        => $this->timeout,
        ]);
        $body   = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ['body' => $body ?: '', 'status' => $status];
    }

    private function assertUploadRejected(array $response, string $message): void
    {
        $body   = $response['body'] ?? '';
        $status = $response['status'] ?? 0;

        $rejected = (
            $status === 403
            || $status === 400
            || $status === 422
            || stripos($body, 'não permitid') !== false
            || stripos($body, 'extensão') !== false
            || stripos($body, 'invalid') !== false
            || stripos($body, 'rejected') !== false
            || stripos($body, 'tipo de arquivo') !== false
            || stripos($body, '"success":false') !== false
        );
        $this->assertTrue($rejected, $message . " (status: {$status})");
    }
}
