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

final class SubjectResolver implements SubjectResolverInterface
{
    public function resolve(Operation $operation, mixed $data, array $context): mixed
    {
        if ($operation instanceof GetCollection) {
            return $context['resource_class'] ?? $operation->getClass() ?? $data;
        }

        if ($operation instanceof Put || $operation instanceof Patch) {
            return [$data, $context['previous_object'] ?? $context['previous_data'] ?? null];
        }

        if ($operation instanceof Delete || $operation instanceof Get || $operation instanceof Post) {
            return $data;
        }

        return $data;
    }
}
