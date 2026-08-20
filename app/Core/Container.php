<?php

declare(strict_types=1);

namespace App\Core;

use Closure;
use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;

/**
 * Minimal auto-wiring DI container: resolves constructor dependencies via
 * reflection when no explicit binding exists. Enough for this app's needs
 * without pulling in a Composer dependency.
 */
final class Container
{
    private static ?Container $instance = null;

    /** @var array<string,Closure> */
    private array $bindings = [];

    /** @var array<string,object> */
    private array $instances = [];

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    public function bind(string $abstract, Closure $concrete): void
    {
        $this->bindings[$abstract] = $concrete;
    }

    public function singleton(string $abstract, object $concrete): void
    {
        $this->instances[$abstract] = $concrete instanceof Closure ? $concrete($this) : $concrete;
    }

    public function instance(string $abstract, object $object): void
    {
        $this->instances[$abstract] = $object;
    }

    /**
     * @template T of object
     * @param class-string<T> $abstract
     * @return T
     */
    public function make(string $abstract): object
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        if (isset($this->bindings[$abstract])) {
            $object = ($this->bindings[$abstract])($this);
            $this->instances[$abstract] = $object;

            return $object;
        }

        return $this->build($abstract);
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return T
     */
    private function build(string $class): object
    {
        if (!class_exists($class)) {
            throw new RuntimeException("Class {$class} does not exist and cannot be resolved.");
        }

        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return new $class();
        }

        $dependencies = [];
        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $dependencies[] = $this->make($type->getName());
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
                continue;
            }

            throw new RuntimeException(
                "Cannot resolve parameter \${$parameter->getName()} for {$class}."
            );
        }

        return $reflection->newInstanceArgs($dependencies);
    }
}
