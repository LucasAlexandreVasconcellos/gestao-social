<?php

declare(strict_types=1);

namespace App\Core;

use ReflectionClass;

final class Container
{
    private static array $bindings = [];
    private static array $instances = [];

    public static function bind(string $abstract, string|callable $concrete): void
    {
        self::$bindings[$abstract] = $concrete;
    }

    public static function singleton(string $abstract, string|callable $concrete): void
    {
        self::bind($abstract, function () use ($concrete, $abstract) {
            if (!isset(self::$instances[$abstract])) {
                self::$instances[$abstract] = is_callable($concrete)
                    ? $concrete()
                    : self::resolve($concrete);
            }
            return self::$instances[$abstract];
        });
    }

    public static function resolve(string $abstract): mixed
    {
        if (isset(self::$bindings[$abstract])) {
            $concrete = self::$bindings[$abstract];
            if (is_callable($concrete)) {
                return $concrete();
            }
            return self::resolve($concrete);
        }

        if (!class_exists($abstract)) {
            throw new \RuntimeException("Classe ou interface {$abstract} não encontrada.");
        }

        $reflector = new ReflectionClass($abstract);
        if (!$reflector->isInstantiable()) {
            throw new \RuntimeException("Classe {$abstract} não pode ser instanciada.");
        }

        $constructor = $reflector->getConstructor();
        if ($constructor === null) {
            return new $abstract();
        }

        $parameters = $constructor->getParameters();
        $dependencies = [];

        foreach ($parameters as $param) {
            $type = $param->getType();
            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $dependencyClassName = $type->getName();
                if ($dependencyClassName === \PDO::class) {
                    if ($param->allowsNull() || $param->isDefaultValueAvailable()) {
                        $dependencies[] = null;
                    } else {
                        $dependencies[] = Database::getConnection();
                    }
                } elseif (isset(self::$bindings[$dependencyClassName])) {
                    $dependencies[] = self::resolve($dependencyClassName);
                } elseif ($param->isDefaultValueAvailable()) {
                    $dependencies[] = $param->getDefaultValue();
                } else {
                    $dependencies[] = self::resolve($dependencyClassName);
                }
            } elseif ($param->isDefaultValueAvailable()) {
                $dependencies[] = $param->getDefaultValue();
            } else {
                throw new \RuntimeException("Não foi possível resolver parâmetro '{$param->getName()}' em {$abstract}.");
            }
        }

        return $reflector->newInstanceArgs($dependencies);
    }
}
