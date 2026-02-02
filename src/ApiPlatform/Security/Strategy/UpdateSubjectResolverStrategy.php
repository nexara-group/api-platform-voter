<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\ApiPlatform\Security\Strategy;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Put;
use Nexara\ApiPlatformVoter\ApiPlatform\Security\SubjectResolverStrategyInterface;

final class UpdateSubjectResolverStrategy implements SubjectResolverStrategyInterface
{
    public function supports(Operation $operation): bool
    {
        return $operation instanceof Put || $operation instanceof Patch;
    }

    public function resolve(Operation $operation, mixed $data, array $context): mixed
    {
        return [$data, $context['previous_object'] ?? $context['previous_data'] ?? null];
    }

    public function getPriority(): int
    {
        return 90;
    }
}
