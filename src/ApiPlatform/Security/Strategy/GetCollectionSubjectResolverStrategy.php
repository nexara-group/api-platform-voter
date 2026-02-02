<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\ApiPlatform\Security\Strategy;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use Nexara\ApiPlatformVoter\ApiPlatform\Security\SubjectResolverStrategyInterface;

final class GetCollectionSubjectResolverStrategy implements SubjectResolverStrategyInterface
{
    public function supports(Operation $operation): bool
    {
        return $operation instanceof GetCollection;
    }

    public function resolve(Operation $operation, mixed $data, array $context): mixed
    {
        return $context['resource_class'] ?? $operation->getClass() ?? $data;
    }

    public function getPriority(): int
    {
        return 100;
    }
}
