<?php
namespace Akti\Tests\Unit\Middleware;

use PHPUnit\Framework\TestCase;
use Akti\Middleware\PortalAuthMiddleware;

/**
 * Testes unitários para Akti\Middleware\PortalAuthMiddleware.
 *
 * Cobre:
 * - isAuthenticated(): verifica sessão portal_customer_id
 * - getCustomerId(): retorna ID do cliente
 * - getAccessId(): retorna access_id
 * - getLang(): retorna idioma com fallback pt-br
 * - login(): configura sessão completa do portal
 * - logout(): limpa sessão do portal sem afetar admin
 * - touch(): atualiza last_activity
 * - is2faPending(): verifica se 2FA está pendente
 * - set2faPending(): marca 2FA como pendente
 * - set2faVerified(): marca 2FA como verificado
 * - getClientIp(): resolve IP com proxy headers
 *
 * @package Akti\Tests\Unit\Middleware
 */
class PortalAuthMiddlewareTest extends TestCase
{
    private array $backupSession = [];
    private array $backupServer = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->backupSession = $_SESSION ?? [];
        $this->backupServer = $_SERVER;
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = $this->backupSession;
        $_SERVER = $this->backupServer;
        parent::tearDown();
    }

    // ══════════════════════════════════════════════════════════════
    // isAuthenticated()
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function isAuthenticated_retorna_false_sem_sessao(): void
    {
        $this->assertFalse(PortalAuthMiddleware::isAuthenticated());
    }

    /** @test */
    public function isAuthenticated_retorna_false_com_id_zero(): void
    {
        $_SESSION['portal_customer_id'] = 0;
        $this->assertFalse(PortalAuthMiddleware::isAuthenticated());
    }

    /** @test */
    public function isAuthenticated_retorna_false_com_id_negativo(): void
    {
        $_SESSION['portal_customer_id'] = -1;
        $this->assertFalse(PortalAuthMiddleware::isAuthenticated());
    }

    /** @test */
    public function isAuthenticated_retorna_true_com_id_valido(): void
    {
        $_SESSION['portal_customer_id'] = 42;
        $this->assertTrue(PortalAuthMiddleware::isAuthenticated());
    }

    // ══════════════════════════════════════════════════════════════
    // getCustomerId()
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function getCustomerId_retorna_null_sem_sessao(): void
    {
        $this->assertNull(PortalAuthMiddleware::getCustomerId());
    }

    /** @test */
    public function getCustomerId_retorna_int(): void
    {
        $_SESSION['portal_customer_id'] = '42';
        $result = PortalAuthMiddleware::getCustomerId();

        $this->assertSame(42, $result);
        $this->assertIsInt($result);
    }

    // ══════════════════════════════════════════════════════════════
    // getAccessId()
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function getAccessId_retorna_null_sem_sessao(): void
    {
        $this->assertNull(PortalAuthMiddleware::getAccessId());
    }

    /** @test */
    public function getAccessId_retorna_int(): void
    {
        $_SESSION['portal_access_id'] = '7';
        $result = PortalAuthMiddleware::getAccessId();

        $this->assertSame(7, $result);
        $this->assertIsInt($result);
    }

    // ══════════════════════════════════════════════════════════════
    // getLang()
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function getLang_retorna_ptbr_por_padrao(): void
    {
        $this->assertSame('pt-br', PortalAuthMiddleware::getLang());
    }

    /** @test */
    public function getLang_retorna_idioma_da_sessao(): void
    {
        $_SESSION['portal_lang'] = 'en';
        $this->assertSame('en', PortalAuthMiddleware::getLang());
    }

    // ══════════════════════════════════════════════════════════════
    // login()
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function login_configura_sessao_completa(): void
    {
        PortalAuthMiddleware::login(10, 5, 'Cliente Teste', 'cliente@teste.com', 'es');

        $this->assertSame(10, $_SESSION['portal_customer_id']);
        $this->assertSame(5, $_SESSION['portal_access_id']);
        $this->assertSame('Cliente Teste', $_SESSION['portal_customer_name']);
        $this->assertSame('cliente@teste.com', $_SESSION['portal_email']);
        $this->assertSame('es', $_SESSION['portal_lang']);
        $this->assertArrayHasKey('portal_last_activity', $_SESSION);
        $this->assertIsInt($_SESSION['portal_last_activity']);
    }

    /** @test */
    public function login_usa_ptbr_como_idioma_padrao(): void
    {
        PortalAuthMiddleware::login(10, 5, 'Cliente', 'c@t.com');
        $this->assertSame('pt-br', $_SESSION['portal_lang']);
    }

    // ══════════════════════════════════════════════════════════════
    // logout()
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function logout_limpa_sessao_do_portal(): void
    {
        $_SESSION['portal_customer_id'] = 10;
        $_SESSION['portal_access_id'] = 5;
        $_SESSION['portal_customer_name'] = 'Teste';
        $_SESSION['portal_email'] = 'teste@test.com';
        $_SESSION['portal_lang'] = 'pt-br';
        $_SESSION['portal_last_activity'] = time();
        $_SESSION['portal_cart'] = ['item1'];
        $_SESSION['portal_2fa_verified'] = true;
        $_SESSION['portal_2fa_pending'] = false;

        PortalAuthMiddleware::logout();

        $this->assertArrayNotHasKey('portal_customer_id', $_SESSION);
        $this->assertArrayNotHasKey('portal_access_id', $_SESSION);
        $this->assertArrayNotHasKey('portal_customer_name', $_SESSION);
        $this->assertArrayNotHasKey('portal_email', $_SESSION);
        $this->assertArrayNotHasKey('portal_lang', $_SESSION);
        $this->assertArrayNotHasKey('portal_last_activity', $_SESSION);
        $this->assertArrayNotHasKey('portal_cart', $_SESSION);
        $this->assertArrayNotHasKey('portal_2fa_verified', $_SESSION);
        $this->assertArrayNotHasKey('portal_2fa_pending', $_SESSION);
    }

    /** @test */
    public function logout_nao_afeta_sessao_admin(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_name'] = 'Admin';
        $_SESSION['portal_customer_id'] = 10;

        PortalAuthMiddleware::logout();

        $this->assertSame(1, $_SESSION['user_id'], 'Sessão admin não deve ser afetada');
        $this->assertSame('Admin', $_SESSION['user_name']);
        $this->assertArrayNotHasKey('portal_customer_id', $_SESSION);
    }

    // ══════════════════════════════════════════════════════════════
    // touch()
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function touch_atualiza_last_activity(): void
    {
        $_SESSION['portal_last_activity'] = time() - 3600;
        $before = $_SESSION['portal_last_activity'];

        PortalAuthMiddleware::touch();

        $this->assertGreaterThan($before, $_SESSION['portal_last_activity']);
        $this->assertEqualsWithDelta(time(), $_SESSION['portal_last_activity'], 2);
    }

    // ══════════════════════════════════════════════════════════════
    // 2FA
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function is2faPending_retorna_false_sem_sessao(): void
    {
        $this->assertFalse(PortalAuthMiddleware::is2faPending());
    }

    /** @test */
    public function is2faPending_retorna_true_quando_pendente(): void
    {
        $_SESSION['portal_2fa_pending'] = true;
        unset($_SESSION['portal_2fa_verified']);

        $this->assertTrue(PortalAuthMiddleware::is2faPending());
    }

    /** @test */
    public function is2faPending_retorna_false_quando_verificado(): void
    {
        $_SESSION['portal_2fa_pending'] = true;
        $_SESSION['portal_2fa_verified'] = true;

        $this->assertFalse(PortalAuthMiddleware::is2faPending());
    }

    /** @test */
    public function set2faPending_marca_como_pendente(): void
    {
        PortalAuthMiddleware::set2faPending(true);

        $this->assertTrue($_SESSION['portal_2fa_pending']);
    }

    /** @test */
    public function set2faPending_false_marca_como_verificado(): void
    {
        PortalAuthMiddleware::set2faPending(false);

        $this->assertFalse($_SESSION['portal_2fa_pending']);
        $this->assertTrue($_SESSION['portal_2fa_verified']);
    }

    /** @test */
    public function set2faVerified_marca_como_verificado(): void
    {
        $_SESSION['portal_2fa_pending'] = true;

        PortalAuthMiddleware::set2faVerified();

        $this->assertTrue($_SESSION['portal_2fa_verified']);
        $this->assertArrayNotHasKey('portal_2fa_pending', $_SESSION);
    }

    // ══════════════════════════════════════════════════════════════
    // getClientIp()
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function getClientIp_retorna_remote_addr(): void
    {
        unset(
            $_SERVER['HTTP_CF_CONNECTING_IP'],
            $_SERVER['HTTP_X_FORWARDED_FOR'],
            $_SERVER['HTTP_X_REAL_IP']
        );
        $_SERVER['REMOTE_ADDR'] = '10.0.0.1';

        $this->assertSame('10.0.0.1', PortalAuthMiddleware::getClientIp());
    }

    /** @test */
    public function getClientIp_prioriza_cloudflare(): void
    {
        $_SERVER['HTTP_CF_CONNECTING_IP'] = '1.1.1.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '2.2.2.2';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        $this->assertSame('1.1.1.1', PortalAuthMiddleware::getClientIp());
    }

    /** @test */
    public function getClientIp_retorna_fallback(): void
    {
        unset(
            $_SERVER['HTTP_CF_CONNECTING_IP'],
            $_SERVER['HTTP_X_FORWARDED_FOR'],
            $_SERVER['HTTP_X_REAL_IP'],
            $_SERVER['REMOTE_ADDR']
        );

        $this->assertSame('0.0.0.0', PortalAuthMiddleware::getClientIp());
    }

    // ══════════════════════════════════════════════════════════════
    // Assinatura de métodos
    // ══════════════════════════════════════════════════════════════

    /** @test */
    public function checkInactivity_tem_timeout_padrao_60_minutos(): void
    {
        $ref = new \ReflectionMethod(PortalAuthMiddleware::class, 'checkInactivity');
        $params = $ref->getParameters();

        $this->assertSame('timeoutMinutes', $params[0]->getName());
        $this->assertTrue($params[0]->isDefaultValueAvailable());
        $this->assertSame(60, $params[0]->getDefaultValue());
    }

    /** @test */
    public function login_tem_parametro_lang_padrao_ptbr(): void
    {
        $ref = new \ReflectionMethod(PortalAuthMiddleware::class, 'login');
        $params = $ref->getParameters();

        $langParam = $params[4]; // 5º parâmetro
        $this->assertSame('lang', $langParam->getName());
        $this->assertTrue($langParam->isDefaultValueAvailable());
        $this->assertSame('pt-br', $langParam->getDefaultValue());
    }
}
