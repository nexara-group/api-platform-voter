<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Maker\Util;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;

final class CustomOperationExtractor
{
    /**
     * @return list<string>
     */
    public function extract(ResourceMetadataCollectionFactoryInterface $factory, string $resourceClass): array
    {
        $collection = $factory->create($resourceClass);

        $custom = [];

        foreach ($collection as $resourceMetadata) {
            if (! method_exists($resourceMetadata, 'getOperations')) {
                continue;
            }

            $operations = $resourceMetadata->getOperations();
            if (! is_iterable($operations)) {
                continue;
            }

            foreach ($operations as $operation) {
                if (! $operation instanceof Operation) {
                    continue;
                }

                // Skip standard CRUD operations
                if ($operation instanceof GetCollection
                    || $operation instanceof Get
                    || $operation instanceof Put
                    || $operation instanceof Patch
                    || $operation instanceof Delete
                ) {
                    continue;
                }

                // For Post operations, check if it's a standard create or custom operation
                if ($operation instanceof Post) {
                    // Check if it's a custom operation by looking at uriTemplate
                    // Standard POST create has uriTemplate like "/articles" (collection endpoint)
                    // Custom POST has uriTemplate like "/articles/{id}/publish" (item endpoint with action)
                    $uriTemplate = method_exists($operation, 'getUriTemplate') ? $operation->getUriTemplate() : null;
                    
                    if ($uriTemplate !== null && is_string($uriTemplate)) {
                        // If uriTemplate contains {id} or similar placeholder followed by an action, it's custom
                        // Example: /articles/{id}/publish, /articles/{id}/archive
                        $hasItemPlaceholder = preg_match('/\{[^}]+\}/', $uriTemplate);
                        $pathSegments = explode('/', trim($uriTemplate, '/'));
                        $hasActionAfterPlaceholder = count($pathSegments) > 2 && $hasItemPlaceholder;
                        
                        if (!$hasActionAfterPlaceholder) {
                            // It's a standard collection POST (create)
                            continue;
                        }
                    } else {
                        // No uriTemplate or not a string - check name as fallback
                        $name = $operation->getName();
                        if ($name === null || $name === '' || str_starts_with($name, '_api_')) {
                            continue;
                        }
                    }
                }

                $key = $this->operationKey($operation);
                if ($key === null || $key === '') {
                    continue;
                }

                $custom[$key] = true;
            }
        }

        $list = array_keys($custom);
        sort($list);

        return $list;
    }

    private function operationKey(Operation $operation): ?string
    {
        // For custom POST operations, prioritize extracting action from uriTemplate
        // This handles cases where name is auto-generated like "_api_/articles/{id}/publish_post"
        if ($operation instanceof Post && method_exists($operation, 'getUriTemplate')) {
            $uriTemplate = $operation->getUriTemplate();
            if (is_string($uriTemplate) && $uriTemplate !== '') {
                $path = trim($uriTemplate, '/');
                $segments = explode('/', $path);
                
                // Check if it's a custom operation pattern: /resource/{id}/action
                if (count($segments) > 2) {
                    $last = end($segments);
                    if (is_string($last) && $last !== '' && $last[0] !== '{') {
                        return $last; // Return the action (e.g., "publish", "archive")
                    }
                }
            }
        }

        // For other operations, use name if available and not auto-generated
        $name = $operation->getName();
        if (is_string($name) && $name !== '' && !str_starts_with($name, '_api_')) {
            return $name;
        }

        if (method_exists($operation, 'getRouteName')) {
            $routeName = $operation->getRouteName();
            if (is_string($routeName) && $routeName !== '') {
                return $routeName;
            }
        }

        // Fallback to extracting from uriTemplate for non-POST operations
        if (method_exists($operation, 'getUriTemplate')) {
            $uriTemplate = $operation->getUriTemplate();
            if (is_string($uriTemplate) && $uriTemplate !== '') {
                $path = trim($uriTemplate, '/');
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
}
