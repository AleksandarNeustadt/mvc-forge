<?php

namespace App\Core\container;

use BadMethodCallException;
use Closure;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Error;
use ErrorException;
use Exception;
use FilesystemIterator;
use InvalidArgumentException;
use PDO;
use PDOException;
use PDOStatement;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use RuntimeException;
use Throwable;
use stdClass;

class Container
{
    private static ?self $instance = null;

    private array $bindings = [];
    private array $singletons = [];
    private array $instances = [];

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function bind(string $key, callable|string $resolver): void
    {
        $this->bindings[$key] = $resolver;
        unset($this->singletons[$key], $this->instances[$key]);
    }

    public function singleton(string $key, callable|string $resolver): void
    {
        $this->singletons[$key] = $resolver;
        unset($this->bindings[$key], $this->instances[$key]);
    }

    public function instance(string $key, mixed $instance): void
    {
        $this->instances[$key] = $instance;
        unset($this->bindings[$key], $this->singletons[$key]);
    }

    public function has(string $key): bool
    {
        return isset($this->instances[$key])
            || isset($this->singletons[$key])
            || isset($this->bindings[$key])
            || class_exists($key);
    }

    public function make(string $key): mixed
    {
        if (array_key_exists($key, $this->instances)) {
            return $this->instances[$key];
        }

        if (array_key_exists($key, $this->singletons)) {
            $this->instances[$key] = $this->resolve($this->singletons[$key]);

            return $this->instances[$key];
        }

        if (array_key_exists($key, $this->bindings)) {
            return $this->resolve($this->bindings[$key]);
        }

        if (class_exists($key)) {
            return $this->build($key);
        }

        throw new RuntimeException("Container binding [{$key}] is not registered.");
    }

    private function resolve(callable|string $resolver): mixed
    {
        if (is_string($resolver)) {
            return $this->build($resolver);
        }

        return $resolver($this);
    }

    private function build(string $className): object
    {
        $reflection = new ReflectionClass($className);
        if (!$reflection->isInstantiable()) {
            throw new RuntimeException("Class [{$className}] is not instantiable.");
        }

        $constructor = $reflection->getConstructor();
        if ($constructor === null) {
            return $reflection->newInstance();
        }

        $arguments = [];
        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $arguments[] = $this->make($type->getName());
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
                continue;
            }

            throw new RuntimeException(
                "Cannot resolve parameter [{$parameter->getName()}] while building [{$className}]."
            );
        }

        return $reflection->newInstanceArgs($arguments);
    }
}


if (!\class_exists('Container', false) && !\interface_exists('Container', false) && !\trait_exists('Container', false)) {
    \class_alias(__NAMESPACE__ . '\\Container', 'Container');
}
