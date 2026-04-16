<?php

namespace Akti\Core;

use Psr\Container\ContainerInterface;

/**
 * Container de injeção de dependências compatível com PSR-11.
 */
class Container implements ContainerInterface
{
    /** @var array<string, callable> Factories registradas */
    private array $bindings = [];

    /** @var array<string, mixed> Cache de instâncias (shared/singleton) */
    private array $instances = [];

    /** @var array<string, bool> IDs marcados como shared */
    private array $shared = [];

    /** @var array<string, \ReflectionClass> Cache de ReflectionClass */
    private array $reflectionCache = [];

    /**
     * Registra um binding no container.
     */
    public function bind(string $id, callable $factory, bool $shared = false): void
    {
        $this->bindings[$id] = $factory;
        if ($shared) {
            $this->shared[$id] = true;
        }
    }

    /**
     * Registra um binding como singleton.
     */
    public function singleton(string $id, callable $factory): void
    {
        $this->bind($id, $factory, true);
    }

    /**
     * Registra uma instância já pronta.
     */
    public function instance(string $id, mixed $value): void
    {
        $this->instances[$id] = $value;
    }

    /**
     * @inheritDoc
     */
    public function get(string $id): mixed
    {
        // 1. Instância cacheada?
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        // 2. Binding registrado?
        if (isset($this->bindings[$id])) {
            $resolved = ($this->bindings[$id])($this);
            if (!empty($this->shared[$id])) {
                $this->instances[$id] = $resolved;
            }
            return $resolved;
        }

        // 3. Auto-wiring via Reflection
        if (class_exists($id)) {
            $resolved = $this->autoWire($id);
            // Classes auto-wired são shared por padrão
            $this->instances[$id] = $resolved;
            return $resolved;
        }

        throw new NotFoundException("Entry not found: {$id}");
    }

    /**
     * @inheritDoc
     */
    public function has(string $id): bool
    {
        return isset($this->instances[$id])
            || isset($this->bindings[$id])
            || class_exists($id);
    }

    /**
     * Auto-wiring recursivo via Reflection.
     */
    private function autoWire(string $class): object
    {
        $ref = $this->getReflection($class);

        if (!$ref->isInstantiable()) {
            throw new ContainerException("Not instantiable: {$class}");
        }

        $ctor = $ref->getConstructor();
        if ($ctor === null || $ctor->getNumberOfParameters() === 0) {
            return new $class();
        }

        $args = [];
        foreach ($ctor->getParameters() as $param) {
            $type = $param->getType();

            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $args[] = $this->get($type->getName());
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } elseif ($type instanceof \ReflectionNamedType && $type->allowsNull()) {
                $args[] = null;
            } else {
                throw new ContainerException(
                    "Cannot resolve param \${$param->getName()} in {$class}"
                );
            }
        }

        return $ref->newInstanceArgs($args);
    }

    /**
     * Obtém ReflectionClass com cache.
     */
    private function getReflection(string $class): \ReflectionClass
    {
        if (!isset($this->reflectionCache[$class])) {
            try {
                $this->reflectionCache[$class] = new \ReflectionClass($class);
            } catch (\ReflectionException $e) {
                throw new ContainerException("Cannot reflect: {$class}", 0, $e);
            }
        }

        return $this->reflectionCache[$class];
    }
}
