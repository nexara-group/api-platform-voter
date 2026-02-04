<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\ApiPlatform\Security;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;

/**
 * Maps API Platform operations to voter attributes.
 *
 * Mapping rules:
 * - GetCollection -> {prefix}:list
 * - Get -> {prefix}:read
 * - Post -> {prefix}:create
 * - Put/Patch -> {prefix}:update
 * - Delete -> {prefix}:delete
 * - Custom operations -> {prefix}:{operationName}
 *
 * @internal
 */
final class OperationToVoterAttributeMapper implements OperationToVoterAttributeMapperInterface
{
    /**
     * @param array<string> $customOperationPatterns
     */
    public function __construct(
        private readonly bool $enforceCollectionList,
        private readonly array $customOperationPatterns = ['!^_api_'],
        private readonly string $namingConvention = 'preserve',
        private readonly bool $normalizeNames = false,
        private readonly bool $detectByUri = true,
    ) {
    }

    public function map(Operation $operation, string $prefix): ?string
    {
        // Check for custom operation name first (before default CRUD mapping)
        $operationKey = $operation->getName();
        if (is_string($operationKey) && $operationKey !== '') {
            // Check against custom operation patterns
            if ($this->isCustomOperation($operationKey)) {
                $normalizedName = $this->normalizeOperationName($operationKey);
                return $prefix . ':' . $normalizedName;
            }
        }

        // Check URI template for custom operation detection
        if ($this->detectByUri && $this->isCustomOperationByUri($operation)) {
            $operationKey = $this->extractOperationNameFromUri($operation);
            if ($operationKey !== null) {
                $normalizedName = $this->normalizeOperationName($operationKey);
                return $prefix . ':' . $normalizedName;
            }
        }

        // Default CRUD mapping
        if ($operation instanceof GetCollection) {
            return $this->enforceCollectionList ? $prefix . ':list' : null;
        }

        if ($operation instanceof Get) {
            return $prefix . ':read';
        }

        if ($operation instanceof Post) {
            return $prefix . ':create';
        }

        if ($operation instanceof Put || $operation instanceof Patch) {
            return $prefix . ':update';
        }

        if ($operation instanceof Delete) {
            return $prefix . ':delete';
        }

        // Fallback for operations without explicit name
        if (! is_string($operationKey) || $operationKey === '') {
            $operationKey = $this->fallbackOperationKey($operation);
        }

        if (! is_string($operationKey) || $operationKey === '') {
            return null;
        }

        return $prefix . ':' . $operationKey;
    }

    private function fallbackOperationKey(Operation $operation): ?string
    {
        if (method_exists($operation, 'getRouteName')) {
            $routeName = $operation->getRouteName();
            if (is_string($routeName) && $routeName !== '') {
                return $routeName;
            }
        }

        if (method_exists($operation, 'getUriTemplate')) {
            $uriTemplate = $operation->getUriTemplate();
            if (is_string($uriTemplate) && $uriTemplate !== '') {
                $path = trim($uriTemplate);
                $path = trim($path, '/');
                if ($path === '') {
                    return null;
                }

                $segments = explode('/', $path);
                $last = end($segments);
                if (is_string($last) && $last !== '' && $last[0] !== '{') {
                    return $last;
                }
            }
        }

        return null;
    }

    /**
     * Checks if operation name matches custom operation patterns.
     */
    private function isCustomOperation(string $operationName): bool
    {
        foreach ($this->customOperationPatterns as $pattern) {
            // Pattern starting with ! means NOT match (exclusion)
            if (str_starts_with($pattern, '!')) {
                $excludePattern = substr($pattern, 1);
                if (! preg_match('#' . $excludePattern . '#', $operationName)) {
                    return true;
                }
            } else {
                // Normal pattern (inclusion)
                if (preg_match('#' . $pattern . '#', $operationName)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Detects custom operations by URI template pattern.
     * Custom operations typically have pattern: /resource/{id}/action
     */
    private function isCustomOperationByUri(Operation $operation): bool
    {
        if (! method_exists($operation, 'getUriTemplate')) {
            return false;
        }

        $uriTemplate = $operation->getUriTemplate();
        if (! is_string($uriTemplate) || $uriTemplate === '') {
            return false;
        }

        // Pattern: /articles/{id}/publish -> custom operation
        // Pattern: /articles/{id} -> standard CRUD
        return preg_match('#/\{[^}]+\}/[^/]+$#', $uriTemplate) === 1;
    }

    /**
     * Extracts operation name from URI template.
     * Example: /articles/{id}/publish-article -> publish_article
     */
    private function extractOperationNameFromUri(Operation $operation): ?string
    {
        if (! method_exists($operation, 'getUriTemplate')) {
            return null;
        }

        $uriTemplate = $operation->getUriTemplate();
        if (! is_string($uriTemplate) || $uriTemplate === '') {
            return null;
        }

        // Extract last segment after {id}
        if (preg_match('#/\{[^}]+\}/([^/]+)$#', $uriTemplate, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Normalizes operation name according to naming convention.
     */
    private function normalizeOperationName(string $name): string
    {
        // First, normalize to lowercase if configured
        if ($this->normalizeNames) {
            $name = strtolower($name);
        }

        // Apply naming convention transformation
        return match ($this->namingConvention) {
            'snake_case' => $this->toSnakeCase($name),
            'camelCase' => $this->toCamelCase($name),
            'kebab-case' => $this->toKebabCase($name),
            'preserve' => $name,
            default => $name,
        };
    }

    private function toSnakeCase(string $str): string
    {
        // Convert kebab-case or camelCase to snake_case
        $str = str_replace('-', '_', $str);
        $str = preg_replace('/([a-z])([A-Z])/', '$1_$2', $str);
        return strtolower((string) $str);
    }

    private function toCamelCase(string $str): string
    {
        // Convert snake_case or kebab-case to camelCase
        $str = str_replace(['-', '_'], ' ', $str);
        $str = ucwords($str);
        $str = str_replace(' ', '', $str);
        return lcfirst($str);
    }

    private function toKebabCase(string $str): string
    {
        // Convert snake_case or camelCase to kebab-case
        $str = str_replace('_', '-', $str);
        $str = preg_replace('/([a-z])([A-Z])/', '$1-$2', $str);
        return strtolower((string) $str);
    }
}
