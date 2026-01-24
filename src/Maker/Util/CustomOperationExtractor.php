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
                    $name = $operation->getName();
                    // Standard POST create has no name or name like "_api_/articles_post"
                    if ($name === null || $name === '' || str_starts_with($name, '_api_')) {
                        continue;
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
        $name = $operation->getName();
        if (is_string($name) && $name !== '') {
            return $name;
        }

        if (method_exists($operation, 'getRouteName')) {
            $routeName = $operation->getRouteName();
            if (is_string($routeName) && $routeName !== '') {
                return $routeName;
            }
        }

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
