<?php
namespace Akti\Tests\Security;

use PHPUnit\Framework\TestCase;
use Akti\Core\Security;
use Akti\Middleware\CsrfMiddleware;

/**
 * Testes de segurança para proteção CSRF do sistema Akti.
 *
 * Cobre cenários de ataque e edge cases:
 * - Token vazio/nulo não é aceito
 * - Token com caracteres inválidos não é aceito
 * - Token truncado não é aceito
 * - Token com espaços extras não é aceito
 * - Timing-safe comparison (hash_equals)
 * - Grace period funciona corretamente
 * - Rotação automática de token
 * - CSRF em métodos HTTP protegidos vs seguros
 *
 * @package Akti\Tests\Security
 */
class CsrfProtectionTest extends TestCase
{
    private array $backupSession = [];
    private array $backupServer = [];
    private array $backupGet = [];
    private array $backupPost = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->backupSession = $_SESSION ?? [];
        $this->backupServer = $_SERVER;
        $this->backupGet = $_GET;
        $this->backupPost = $_POST;

        $_SESSION = [];
        $_GET = ['page' => 'test', 'action' => 'index'];
        $_POST = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = $this->backupSession;
        $_SERVER = $this->backupServer;
        $_GET = $this->backupGet;
        $_POST = $this->backupPost;
        parent::tearDown();
    }

    // ══════════════════════════════════════════════════════════════
    // Cenários de ataque — Token manipulation
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function rejeita_token_vazio(): void
    {
        Security::generateCsrfToken();
        $this->assertFalse(Security::validateCsrfToken(''));
    }

    /** @test */
    public function rejeita_token_null(): void
    {
        Security::generateCsrfToken();
        $this->assertFalse(Security::validateCsrfToken(null));
    }

    /** @test */
    public function rejeita_token_com_espacos(): void
    {
        $token = Security::generateCsrfToken();
        $this->assertFalse(Security::validateCsrfToken(' ' . $token));
        $this->assertFalse(Security::validateCsrfToken($token . ' '));
        $this->assertFalse(Security::validateCsrfToken(' ' . $token . ' '));
    }

    /** @test */
    public function rejeita_token_truncado(): void
    {
        $token = Security::generateCsrfToken();
        $truncated = substr($token, 0, 32); // Apenas metade
        $this->assertFalse(Security::validateCsrfToken($truncated));
    }

    /** @test */
    public function rejeita_token_com_caractere_modificado(): void
    {
        $token = Security::generateCsrfToken();
        // Mudar um caractere
        $modified = $token[0] === 'a' ? 'b' . substr($token, 1) : 'a' . substr($token, 1);
        $this->assertFalse(Security::validateCsrfToken($modified));
    }

    /** @test */
    public function rejeita_token_completamente_aleatorio(): void
    {
        Security::generateCsrfToken();
        $fake = bin2hex(random_bytes(32));
        $this->assertFalse(Security::validateCsrfToken($fake));
    }

    /** @test */
    public function rejeita_token_em_maiusculo(): void
    {
        $token = Security::generateCsrfToken();
        // hash_equals é case-sensitive
        $this->assertFalse(Security::validateCsrfToken(strtoupper($token)));
    }

    // ══════════════════════════════════════════════════════════════
    // Cenários válidos
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function aceita_token_valido_exato(): void
    {
        $token = Security::generateCsrfToken();
        $this->assertTrue(Security::validateCsrfToken($token));
    }

    /** @test */
    public function aceita_token_valido_multiplas_vezes(): void
    {
        $token = Security::generateCsrfToken();

        // Validar o mesmo token múltiplas vezes (não consome o token)
        for ($i = 0; $i < 5; $i++) {
            $this->assertTrue(Security::validateCsrfToken($token), "Falhou na validação #{$i}");
        }
    }

    // ══════════════════════════════════════════════════════════════
    // Rotação de token
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function apos_rotacao_token_antigo_aceito_no_grace_period(): void
    {
        $oldToken = Security::generateCsrfToken();

        // Forçar rotação
        $_SESSION['csrf_token_time'] = time() - 1801;
        $newToken = Security::generateCsrfToken();

        $this->assertNotSame($oldToken, $newToken);
        $this->assertTrue(Security::validateCsrfToken($newToken), 'Novo token deve ser aceito');
        $this->assertTrue(Security::validateCsrfToken($oldToken), 'Token antigo deve ser aceito no grace period');
    }

    /** @test */
    public function apos_rotacao_token_antigo_rejeitado_fora_grace_period(): void
    {
        $oldToken = Security::generateCsrfToken();

        // Forçar rotação
        $_SESSION['csrf_token_time'] = time() - 1801;
        $newToken = Security::generateCsrfToken();

        // Forçar expiração do grace period (30min + 5min = 2100s)
        $_SESSION['csrf_token_previous_time'] = time() - 2200;

        $this->assertTrue(Security::validateCsrfToken($newToken), 'Novo token deve ser aceito');
        $this->assertFalse(Security::validateCsrfToken($oldToken), 'Token antigo deve ser rejeitado');
    }

    // ══════════════════════════════════════════════════════════════
    // Token entropy
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function tokens_tem_entropia_suficiente(): void
    {
        $tokens = [];
        for ($i = 0; $i < 100; $i++) {
            $_SESSION = [];
            $tokens[] = Security::generateCsrfToken();
        }

        // Todos devem ser únicos
        $this->assertCount(100, array_unique($tokens), 'Todos os tokens devem ser únicos');

        // Nenhum deve ser previsível (verificar que não há sequência)
        foreach ($tokens as $token) {
            $this->assertSame(64, strlen($token), 'Cada token deve ter 64 caracteres');
            $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token);
        }
    }

    // ══════════════════════════════════════════════════════════════
    // CSRF Middleware — métodos HTTP
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function get_nao_requer_csrf(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        CsrfMiddleware::handle();
        $this->assertTrue(true, 'GET passou sem CSRF');
    }

    /** @test */
    public function head_nao_requer_csrf(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'HEAD';
        CsrfMiddleware::handle();
        $this->assertTrue(true, 'HEAD passou sem CSRF');
    }

    /** @test */
    public function options_nao_requer_csrf(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';
        CsrfMiddleware::handle();
        $this->assertTrue(true, 'OPTIONS passou sem CSRF');
    }

    /** @test */
    public function rotas_isentas_nao_requerem_csrf(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET['page'] = 'catalog';
        $_GET['action'] = 'addToCart';

        CsrfMiddleware::handle();
        $this->assertTrue(true, 'Rota isenta passou sem CSRF');
    }

    // ══════════════════════════════════════════════════════════════
    // Sessão vazia
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function sessao_vazia_nao_valida_nenhum_token(): void
    {
        $_SESSION = [];

        $this->assertFalse(Security::validateCsrfToken('qualquer'));
        $this->assertFalse(Security::validateCsrfToken(''));
        $this->assertFalse(Security::validateCsrfToken(null));
        $this->assertFalse(Security::validateCsrfToken(str_repeat('a', 64)));
    }
}
