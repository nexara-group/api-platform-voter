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

        $name = $operation->getName();
        if (! is_string($name) || $name === '') {
            return null;
        }

        return $prefix . ':' . $name;
    }
}
