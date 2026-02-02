<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Cache;

use ReflectionClass;
use ReflectionMethod;

final class ReflectionCache
{
    private array $classCache = [];

    private array $methodCache = [];

    private array $attributeCache = [];

    public function getClass(string $className): ReflectionClass
    {
        if (! isset($this->classCache[$className])) {
            $this->classCache[$className] = new ReflectionClass($className);
        }

        return $this->classCache[$className];
    }

    public function getMethods(string $className, ?int $filter = null): array
    {
        $cacheKey = $className . '|' . ($filter ?? 'all');

        if (! isset($this->methodCache[$cacheKey])) {
            $reflection = $this->getClass($className);
            $this->methodCache[$cacheKey] = $filter !== null
                ? $reflection->getMethods($filter)
                : $reflection->getMethods();
        }

        return $this->methodCache[$cacheKey];
    }

    public function hasMethod(string $className, string $methodName): bool
    {
        $reflection = $this->getClass($className);

        return $reflection->hasMethod($methodName);
    }

    public function getMethod(string $className, string $methodName): ReflectionMethod
    {
        $cacheKey = $className . '::' . $methodName;

        if (! isset($this->methodCache[$cacheKey])) {
            $reflection = $this->getClass($className);
            $this->methodCache[$cacheKey] = $reflection->getMethod($methodName);
        }

        return $this->methodCache[$cacheKey];
    }

    public function getAttributes(string $className, string $attributeClass): array
    {
        $cacheKey = $className . '@' . $attributeClass;

        if (! isset($this->attributeCache[$cacheKey])) {
            $reflection = $this->getClass($className);
            $this->attributeCache[$cacheKey] = $reflection->getAttributes($attributeClass);
        }

        return $this->attributeCache[$cacheKey];
    }

    public function clear(): void
    {
        $this->classCache = [];
        $this->methodCache = [];
        $this->attributeCache = [];
    }

    public function getStats(): array
    {
        return [
            'classes_cached' => count($this->classCache),
            'methods_cached' => count($this->methodCache),
            'attributes_cached' => count($this->attributeCache),
            'memory_usage' => memory_get_usage(true),
        ];
    }
}
