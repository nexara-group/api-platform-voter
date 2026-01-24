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

final class OperationToVoterAttributeMapper implements OperationToVoterAttributeMapperInterface
{
    public function __construct(
        private readonly bool $enforceCollectionList,
    ) {
    }

    public function map(Operation $operation, string $prefix): ?string
    {
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

        $operationKey = $operation->getName();
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
}
