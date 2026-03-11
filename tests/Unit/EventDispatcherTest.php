<?php
namespace Akti\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Akti\Core\EventDispatcher;
use Akti\Core\Event;

/**
 * Testes unitários do sistema de eventos (Event Dispatcher).
 *
 * Verifica:
 * - Registro e disparo correto de listeners (FIFO)
 * - Dados do Event (name, data, timestamp)
 * - Isolamento de falhas — exceção em listener não interrompe outros
 * - Método forget() remove listeners corretamente
 * - Convenção de nomes (camada.entidade.acao)
 * - Presença de use EventDispatcher e dispatch() nos Models/Controllers/Core/Middleware
 *
 * @package Akti\Tests\Unit
 */
class EventDispatcherTest extends TestCase
{
    /**
     * Limpa todos os listeners antes de cada teste
     * para garantir isolamento entre testes.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Limpar todos os listeners registrados para isolamento
        $registered = EventDispatcher::getRegistered();
        foreach (array_keys($registered) as $event) {
            EventDispatcher::forget($event);
        }
    }

    // ══════════════════════════════════════════════════════════════
    // Testes da classe EventDispatcher
    // ══════════════════════════════════════════════════════════════

    /**
     * @test
     * Verifica que um listener registrado recebe o evento disparado.
     */
    public function listener_recebe_evento_disparado(): void
    {
        $received = null;

        EventDispatcher::listen('test.event', function (Event $event) use (&$received) {
            $received = $event;
        });

        $payload = new Event('test.event', ['key' => 'value']);
        EventDispatcher::dispatch('test.event', $payload);

        $this->assertNotNull($received, 'Listener deveria ter recebido o evento');
        $this->assertSame('test.event', $received->name);
        $this->assertSame('value', $received->data['key']);
    }

    /**
     * @test
     * Verifica que múltiplos listeners são executados em ordem FIFO.
     */
    public function listeners_executados_em_ordem_fifo(): void
    {
        $order = [];

        EventDispatcher::listen('test.fifo', function () use (&$order) {
            $order[] = 'first';
        });
        EventDispatcher::listen('test.fifo', function () use (&$order) {
            $order[] = 'second';
        });
        EventDispatcher::listen('test.fifo', function () use (&$order) {
            $order[] = 'third';
        });

        EventDispatcher::dispatch('test.fifo', new Event('test.fifo'));

        $this->assertSame(['first', 'second', 'third'], $order);
    }

    /**
     * @test
     * Verifica que dispatch sem listeners não gera erro.
     */
    public function dispatch_sem_listeners_nao_gera_erro(): void
    {
        // Não deve lançar exceção
        EventDispatcher::dispatch('test.no_listeners', new Event('test.no_listeners'));
        $this->assertTrue(true); // Se chegou aqui, passou
    }

    /**
     * @test
     * Verifica que exceção em listener não interrompe os demais.
     */
    public function excecao_em_listener_nao_interrompe_outros(): void
    {
        $secondCalled = false;

        EventDispatcher::listen('test.exception', function () {
            throw new \RuntimeException('Erro intencional no listener');
        });

        EventDispatcher::listen('test.exception', function () use (&$secondCalled) {
            $secondCalled = true;
        });

        EventDispatcher::dispatch('test.exception', new Event('test.exception'));

        $this->assertTrue($secondCalled, 'Segundo listener deveria ser executado mesmo com exceção no primeiro');
    }

    /**
     * @test
     * Verifica que forget() remove todos os listeners de um evento.
     */
    public function forget_remove_listeners(): void
    {
        $called = false;

        EventDispatcher::listen('test.forget', function () use (&$called) {
            $called = true;
        });

        EventDispatcher::forget('test.forget');
        EventDispatcher::dispatch('test.forget', new Event('test.forget'));

        $this->assertFalse($called, 'Listener não deveria ser chamado após forget()');
    }

    /**
     * @test
     * Verifica que getRegistered() retorna os eventos registrados.
     */
    public function get_registered_retorna_eventos(): void
    {
        EventDispatcher::listen('test.a', function () {});
        EventDispatcher::listen('test.b', function () {});
        EventDispatcher::listen('test.a', function () {});

        $registered = EventDispatcher::getRegistered();

        $this->assertArrayHasKey('test.a', $registered);
        $this->assertArrayHasKey('test.b', $registered);
        $this->assertCount(2, $registered['test.a']);
        $this->assertCount(1, $registered['test.b']);
    }

    /**
     * @test
     * Verifica que listeners de um evento não afetam outro.
     */
    public function eventos_sao_isolados_entre_si(): void
    {
        $calledA = false;
        $calledB = false;

        EventDispatcher::listen('test.isolated_a', function () use (&$calledA) {
            $calledA = true;
        });
        EventDispatcher::listen('test.isolated_b', function () use (&$calledB) {
            $calledB = true;
        });

        EventDispatcher::dispatch('test.isolated_a', new Event('test.isolated_a'));

        $this->assertTrue($calledA);
        $this->assertFalse($calledB, 'Listener de outro evento não deveria ser chamado');
    }

    // ══════════════════════════════════════════════════════════════
    // Testes da classe Event (Value Object)
    // ══════════════════════════════════════════════════════════════

    /**
     * @test
     * Verifica que o Event recebe name e data corretamente.
     */
    public function event_armazena_name_e_data(): void
    {
        $event = new Event('model.order.created', ['id' => 42, 'name' => 'Test']);

        $this->assertSame('model.order.created', $event->name);
        $this->assertSame(42, $event->data['id']);
        $this->assertSame('Test', $event->data['name']);
    }

    /**
     * @test
     * Verifica que o Event preenche timestamp automaticamente.
     */
    public function event_preenche_timestamp_automaticamente(): void
    {
        $before = time();
        $event = new Event('test.timestamp');
        $after = time();

        $this->assertGreaterThanOrEqual($before, $event->timestamp);
        $this->assertLessThanOrEqual($after, $event->timestamp);
    }

    /**
     * @test
     * Verifica que o Event funciona com data vazio.
     */
    public function event_aceita_data_vazio(): void
    {
        $event = new Event('test.empty');

        $this->assertSame([], $event->data);
        $this->assertSame('test.empty', $event->name);
    }

    // ══════════════════════════════════════════════════════════════
    // Testes de Convenção — Verificam que o código-fonte segue as regras
    // ══════════════════════════════════════════════════════════════

    /**
     * Lista completa de Models que DEVEM ter eventos.
     * Cada entry: [arquivo, [lista de eventos esperados]]
     *
     * @return array
     */
    public function modelsComEventosProvider(): array
    {
        $basePath = realpath(__DIR__ . '/../../app/models') . DIRECTORY_SEPARATOR;

        return [
            'Order' => [
                $basePath . 'Order.php',
                ['model.order.created', 'model.order.updated', 'model.order.deleted'],
            ],
            'Product' => [
                $basePath . 'Product.php',
                ['model.product.created', 'model.product.updated', 'model.product.deleted'],
            ],
            'Customer' => [
                $basePath . 'Customer.php',
                ['model.customer.created', 'model.customer.updated', 'model.customer.deleted'],
            ],
            'User' => [
                $basePath . 'User.php',
                ['model.user.created', 'model.user.updated', 'model.user.deleted'],
            ],
            'Category' => [
                $basePath . 'Category.php',
                ['model.category.created', 'model.category.updated', 'model.category.deleted'],
            ],
            'Subcategory' => [
                $basePath . 'Subcategory.php',
                ['model.subcategory.created', 'model.subcategory.updated', 'model.subcategory.deleted'],
            ],
            'UserGroup' => [
                $basePath . 'UserGroup.php',
                ['model.user_group.created', 'model.user_group.updated', 'model.user_group.deleted'],
            ],
            'Stock' => [
                $basePath . 'Stock.php',
                ['model.warehouse.created', 'model.warehouse.updated', 'model.warehouse.deleted'],
            ],
            'ProductionSector' => [
                $basePath . 'ProductionSector.php',
                ['model.production_sector.created', 'model.production_sector.updated', 'model.production_sector.deleted'],
            ],
            'PriceTable' => [
                $basePath . 'PriceTable.php',
                ['model.price_table.created', 'model.price_table.updated', 'model.price_table.deleted'],
            ],
            'Financial' => [
                $basePath . 'Financial.php',
                ['model.financial_transaction.created', 'model.financial_transaction.deleted'],
            ],
            'Pipeline' => [
                $basePath . 'Pipeline.php',
                ['model.order.stage_changed'],
            ],
            'CompanySettings' => [
                $basePath . 'CompanySettings.php',
                ['model.company_settings.updated'],
            ],
            'CatalogLink' => [
                $basePath . 'CatalogLink.php',
                ['model.catalog_link.created'],
            ],
            'PreparationStep' => [
                $basePath . 'PreparationStep.php',
                ['model.preparation_step.created', 'model.preparation_step.updated', 'model.preparation_step.deleted'],
            ],
            'ProductGrade' => [
                $basePath . 'ProductGrade.php',
                ['model.grade_type.created'],
            ],
            'CategoryGrade' => [
                $basePath . 'CategoryGrade.php',
                ['model.category_grade.saved', 'model.subcategory_grade.saved'],
            ],
            'OrderItemLog' => [
                $basePath . 'OrderItemLog.php',
                ['model.order_item_log.created', 'model.order_item_log.deleted'],
            ],
            'OrderPreparation' => [
                $basePath . 'OrderPreparation.php',
                ['model.preparation_checklist.toggled'],
            ],
            'Walkthrough' => [
                $basePath . 'Walkthrough.php',
                ['model.walkthrough.completed', 'model.walkthrough.skipped'],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider modelsComEventosProvider
     *
     * Verifica que cada Model tem o import do EventDispatcher.
     */
    public function model_possui_import_event_dispatcher(string $filePath, array $expectedEvents): void
    {
        $this->assertFileExists($filePath, "Arquivo model não encontrado: {$filePath}");

        $content = file_get_contents($filePath);

        $this->assertStringContainsString(
            'use Akti\Core\EventDispatcher;',
            $content,
            basename($filePath) . " deve ter 'use Akti\\Core\\EventDispatcher;'"
        );

        $this->assertStringContainsString(
            'use Akti\Core\Event;',
            $content,
            basename($filePath) . " deve ter 'use Akti\\Core\\Event;'"
        );
    }

    /**
     * @test
     * @dataProvider modelsComEventosProvider
     *
     * Verifica que cada Model dispara os eventos esperados via dispatch().
     */
    public function model_dispara_eventos_esperados(string $filePath, array $expectedEvents): void
    {
        $this->assertFileExists($filePath, "Arquivo model não encontrado: {$filePath}");

        $content = file_get_contents($filePath);

        foreach ($expectedEvents as $eventName) {
            $this->assertStringContainsString(
                "'{$eventName}'",
                $content,
                basename($filePath) . " deve disparar evento '{$eventName}'"
            );

            // Verificar que há ao menos uma chamada dispatch com esse evento
            $this->assertStringContainsString(
                "EventDispatcher::dispatch('{$eventName}'",
                $content,
                basename($filePath) . " deve ter EventDispatcher::dispatch('{$eventName}', ...)"
            );
        }
    }

    /**
     * Controllers/Core/Middleware que devem ter eventos.
     *
     * @return array
     */
    public function outroArquivosComEventosProvider(): array
    {
        $base = realpath(__DIR__ . '/../../app') . DIRECTORY_SEPARATOR;

        return [
            'UserController' => [
                $base . 'controllers' . DIRECTORY_SEPARATOR . 'UserController.php',
                ['controller.user.login', 'controller.user.login_failed', 'controller.user.logout'],
            ],
            'CsrfMiddleware' => [
                $base . 'middleware' . DIRECTORY_SEPARATOR . 'CsrfMiddleware.php',
                ['middleware.csrf.failed'],
            ],
            'Security' => [
                $base . 'core' . DIRECTORY_SEPARATOR . 'Security.php',
                ['core.security.access_denied'],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider outroArquivosComEventosProvider
     *
     * Verifica que Controllers, Core e Middleware possuem o import do EventDispatcher.
     */
    public function arquivo_possui_import_event_dispatcher(string $filePath, array $expectedEvents): void
    {
        $this->assertFileExists($filePath, "Arquivo não encontrado: {$filePath}");

        $content = file_get_contents($filePath);

        $this->assertStringContainsString(
            'use Akti\Core\EventDispatcher;',
            $content,
            basename($filePath) . " deve ter 'use Akti\\Core\\EventDispatcher;'"
        );

        $this->assertStringContainsString(
            'use Akti\Core\Event;',
            $content,
            basename($filePath) . " deve ter 'use Akti\\Core\\Event;'"
        );
    }

    /**
     * @test
     * @dataProvider outroArquivosComEventosProvider
     *
     * Verifica que Controllers, Core e Middleware disparam os eventos esperados.
     */
    public function arquivo_dispara_eventos_esperados(string $filePath, array $expectedEvents): void
    {
        $this->assertFileExists($filePath, "Arquivo não encontrado: {$filePath}");

        $content = file_get_contents($filePath);

        foreach ($expectedEvents as $eventName) {
            $this->assertStringContainsString(
                "'{$eventName}'",
                $content,
                basename($filePath) . " deve disparar evento '{$eventName}'"
            );
        }
    }

    // ══════════════════════════════════════════════════════════════
    // Testes de Models que NÃO devem ter eventos (Infraestrutura)
    // ══════════════════════════════════════════════════════════════

    /**
     * Models de infraestrutura que NÃO devem disparar eventos.
     *
     * @return array
     */
    public function modelsSemEventosProvider(): array
    {
        $basePath = realpath(__DIR__ . '/../../app/models') . DIRECTORY_SEPARATOR;

        return [
            'Logger' => [$basePath . 'Logger.php'],
            'IpGuard' => [$basePath . 'IpGuard.php'],
            'LoginAttempt' => [$basePath . 'LoginAttempt.php'],
        ];
    }

    /**
     * @test
     * @dataProvider modelsSemEventosProvider
     *
     * Verifica que models de infraestrutura NÃO contêm EventDispatcher::dispatch.
     */
    public function model_infraestrutura_nao_dispara_eventos(string $filePath): void
    {
        $this->assertFileExists($filePath, "Arquivo model não encontrado: {$filePath}");

        $content = file_get_contents($filePath);

        $this->assertStringNotContainsString(
            'EventDispatcher::dispatch(',
            $content,
            basename($filePath) . " NÃO deve disparar eventos (model de infraestrutura)"
        );
    }

    // ══════════════════════════════════════════════════════════════
    // Testes de Convenção de Nomes de Eventos
    // ══════════════════════════════════════════════════════════════

    /**
     * @test
     * Verifica que todos os dispatch() no código seguem a convenção camada.entidade.acao.
     */
    public function todos_eventos_seguem_convencao_de_nomes(): void
    {
        // Padrão: camada.entidade.acao (3 partes separadas por ponto)
        $pattern = '/EventDispatcher::dispatch\(\s*[\'"]([^\'"]+)[\'"]/';
        $validPrefixes = ['model.', 'controller.', 'core.', 'middleware.'];

        $directories = [
            realpath(__DIR__ . '/../../app/models'),
            realpath(__DIR__ . '/../../app/controllers'),
            realpath(__DIR__ . '/../../app/core'),
            realpath(__DIR__ . '/../../app/middleware'),
        ];

        $errors = [];

        foreach ($directories as $dir) {
            if (!is_dir($dir)) continue;

            $files = glob($dir . DIRECTORY_SEPARATOR . '*.php');
            foreach ($files as $file) {
                $content = file_get_contents($file);
                if (preg_match_all($pattern, $content, $matches)) {
                    foreach ($matches[1] as $eventName) {
                        // Verificar formato: pelo menos 3 partes separadas por ponto
                        $parts = explode('.', $eventName);
                        if (count($parts) < 3) {
                            $errors[] = basename($file) . ": evento '{$eventName}' não tem 3 partes (camada.entidade.acao)";
                            continue;
                        }

                        // Verificar prefixo válido
                        $hasValidPrefix = false;
                        foreach ($validPrefixes as $prefix) {
                            if (strpos($eventName, $prefix) === 0) {
                                $hasValidPrefix = true;
                                break;
                            }
                        }
                        if (!$hasValidPrefix) {
                            $errors[] = basename($file) . ": evento '{$eventName}' não começa com prefixo válido (" . implode(', ', $validPrefixes) . ")";
                        }
                    }
                }
            }
        }

        $this->assertEmpty($errors, "Eventos com nomenclatura incorreta:\n" . implode("\n", $errors));
    }

    /**
     * @test
     * Verifica que cada dispatch() usa new Event() com o mesmo nome do evento.
     */
    public function dispatch_e_event_usam_mesmo_nome(): void
    {
        // Padrão: dispatch('nome', new Event('nome', ...))
        $pattern = "/EventDispatcher::dispatch\(\s*'([^']+)'\s*,\s*new\s+Event\(\s*'([^']+)'/";

        $directories = [
            realpath(__DIR__ . '/../../app/models'),
            realpath(__DIR__ . '/../../app/controllers'),
            realpath(__DIR__ . '/../../app/core'),
            realpath(__DIR__ . '/../../app/middleware'),
        ];

        $mismatches = [];

        foreach ($directories as $dir) {
            if (!is_dir($dir)) continue;

            $files = glob($dir . DIRECTORY_SEPARATOR . '*.php');
            foreach ($files as $file) {
                $content = file_get_contents($file);
                if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $match) {
                        $dispatchName = $match[1];
                        $eventName = $match[2];
                        if ($dispatchName !== $eventName) {
                            $mismatches[] = basename($file) . ": dispatch('{$dispatchName}') ≠ Event('{$eventName}')";
                        }
                    }
                }
            }
        }

        $this->assertEmpty($mismatches, "Nomes de eventos divergem entre dispatch() e Event():\n" . implode("\n", $mismatches));
    }

    // ══════════════════════════════════════════════════════════════
    // Testes de Integridade dos Arquivos do Sistema de Eventos
    // ══════════════════════════════════════════════════════════════

    /**
     * @test
     * Verifica que os arquivos core do sistema de eventos existem.
     */
    public function arquivos_core_existem(): void
    {
        $basePath = realpath(__DIR__ . '/../../');

        $this->assertFileExists($basePath . '/app/core/EventDispatcher.php', 'EventDispatcher.php deve existir');
        $this->assertFileExists($basePath . '/app/core/Event.php', 'Event.php deve existir');
        $this->assertFileExists($basePath . '/app/bootstrap/events.php', 'events.php deve existir');
    }

    /**
     * @test
     * Verifica que o autoload.php carrega o bootstrap de eventos.
     */
    public function autoload_carrega_bootstrap_eventos(): void
    {
        $autoloadPath = realpath(__DIR__ . '/../../app/bootstrap/autoload.php');
        $this->assertFileExists($autoloadPath);

        $content = file_get_contents($autoloadPath);

        $this->assertStringContainsString(
            'events.php',
            $content,
            "autoload.php deve carregar o bootstrap de eventos (events.php)"
        );
    }

    /**
     * @test
     * Verifica que o EventDispatcher tem os 4 métodos públicos obrigatórios.
     */
    public function event_dispatcher_possui_metodos_obrigatorios(): void
    {
        $this->assertTrue(method_exists(EventDispatcher::class, 'listen'), 'EventDispatcher deve ter método listen()');
        $this->assertTrue(method_exists(EventDispatcher::class, 'dispatch'), 'EventDispatcher deve ter método dispatch()');
        $this->assertTrue(method_exists(EventDispatcher::class, 'forget'), 'EventDispatcher deve ter método forget()');
        $this->assertTrue(method_exists(EventDispatcher::class, 'getRegistered'), 'EventDispatcher deve ter método getRegistered()');
    }

    /**
     * @test
     * Verifica que o Event tem as 5 propriedades obrigatórias.
     */
    public function event_possui_propriedades_obrigatorias(): void
    {
        $event = new Event('test.props', ['foo' => 'bar']);

        $this->assertTrue(property_exists($event, 'name'), 'Event deve ter propriedade $name');
        $this->assertTrue(property_exists($event, 'data'), 'Event deve ter propriedade $data');
        $this->assertTrue(property_exists($event, 'timestamp'), 'Event deve ter propriedade $timestamp');
        $this->assertTrue(property_exists($event, 'userId'), 'Event deve ter propriedade $userId');
        $this->assertTrue(property_exists($event, 'tenantDb'), 'Event deve ter propriedade $tenantDb');
    }
}
