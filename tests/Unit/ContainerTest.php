<?php

namespace Akti\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Akti\Core\Container;
use Akti\Core\NotFoundException;
use Akti\Core\ContainerException;

class ContainerTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    public function test_get_returns_registered_binding(): void
    {
        $this->container->bind('foo', fn() => 'bar');
        $this->assertSame('bar', $this->container->get('foo'));
    }

    public function test_get_throws_not_found_exception(): void
    {
        $this->expectException(NotFoundException::class);
        $this->container->get('nonexistent_entry_xyz');
    }

    public function test_has_returns_true_for_registered(): void
    {
        $this->container->bind('foo', fn() => 'bar');
        $this->assertTrue($this->container->has('foo'));
    }

    public function test_has_returns_false_for_unknown(): void
    {
        $this->assertFalse($this->container->has('nonexistent_entry_xyz'));
    }

    public function test_singleton_returns_same_instance(): void
    {
        $this->container->singleton('counter', fn() => new \stdClass());

        $first = $this->container->get('counter');
        $second = $this->container->get('counter');

        $this->assertSame($first, $second);
    }

    public function test_bind_without_shared_returns_different_instances(): void
    {
        $this->container->bind('counter', fn() => new \stdClass(), false);

        $first = $this->container->get('counter');
        $second = $this->container->get('counter');

        $this->assertNotSame($first, $second);
    }

    public function test_instance_returns_exact_value(): void
    {
        $obj = new \stdClass();
        $obj->name = 'test';
        $this->container->instance('myobj', $obj);

        $this->assertSame($obj, $this->container->get('myobj'));
    }

    public function test_has_returns_true_for_instance(): void
    {
        $this->container->instance('myobj', new \stdClass());
        $this->assertTrue($this->container->has('myobj'));
    }

    public function test_has_returns_true_for_existing_class(): void
    {
        $this->assertTrue($this->container->has(\stdClass::class));
    }

    public function test_auto_wiring_resolves_class_without_constructor(): void
    {
        $result = $this->container->get(\stdClass::class);
        $this->assertInstanceOf(\stdClass::class, $result);
    }

    public function test_auto_wiring_caches_resolved_instance(): void
    {
        $first = $this->container->get(\stdClass::class);
        $second = $this->container->get(\stdClass::class);

        $this->assertSame($first, $second);
    }

    public function test_auto_wiring_resolves_pdo(): void
    {
        $pdo = new \stdClass(); // Simulates a PDO instance
        $this->container->instance(\PDO::class, $pdo);

        $resolved = $this->container->get(\PDO::class);
        $this->assertSame($pdo, $resolved);
    }

    public function test_auto_wiring_resolves_model_recursively(): void
    {
        // DummyWithDep requires DummyDependency (no constructor) — both auto-wirable
        $parent = $this->container->get(ContainerTestDummyWithDep::class);
        $this->assertInstanceOf(ContainerTestDummyWithDep::class, $parent);
        $this->assertInstanceOf(ContainerTestDummyDependency::class, $parent->dep);
    }

    public function test_auto_wiring_resolves_service_with_model(): void
    {
        // DummyService → DummyWithDep → DummyDependency (3-level recursive)
        $service = $this->container->get(ContainerTestDummyService::class);
        $this->assertInstanceOf(ContainerTestDummyService::class, $service);
        $this->assertInstanceOf(ContainerTestDummyWithDep::class, $service->inner);
        $this->assertInstanceOf(ContainerTestDummyDependency::class, $service->inner->dep);
    }

    public function test_binding_receives_container_as_argument(): void
    {
        $this->container->instance('config_value', 'hello');
        $this->container->bind('greeter', function ($c) {
            return $c->get('config_value') . ' world';
        });

        $this->assertSame('hello world', $this->container->get('greeter'));
    }

    public function test_not_instantiable_class_throws_container_exception(): void
    {
        $this->expectException(ContainerException::class);
        $this->container->get(ContainerTestDummyAbstract::class);
    }

    public function test_unresolvable_param_throws_container_exception(): void
    {
        $this->expectException(ContainerException::class);
        // A class that requires a scalar parameter without default
        $this->container->get(ContainerTestDummyUnresolvable::class);
    }
}

/**
 * Dummy class for testing unresolvable parameters.
 */
class ContainerTestDummyUnresolvable
{
    public function __construct(string $requiredParam)
    {
    }
}

/**
 * Dummy leaf dependency for auto-wiring tests.
 */
class ContainerTestDummyDependency
{
}

/**
 * Dummy class that depends on DummyDependency.
 */
class ContainerTestDummyWithDep
{
    public object $dep;

    public function __construct(ContainerTestDummyDependency $dep)
    {
        $this->dep = $dep;
    }
}

/**
 * Dummy service that depends on DummyWithDep (recursive resolution).
 */
class ContainerTestDummyService
{
    public ContainerTestDummyWithDep $inner;

    public function __construct(ContainerTestDummyWithDep $inner)
    {
        $this->inner = $inner;
    }
}

/**
 * Abstract class for testing non-instantiable detection.
 */
abstract class ContainerTestDummyAbstract
{
}
