<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\ApiPlatform\Security\Strategy;

use ApiPlatform\Metadata\Operation;
use Nexara\ApiPlatformVoter\ApiPlatform\Security\SubjectResolverStrategyInterface;

final class DefaultSubjectResolverStrategy implements SubjectResolverStrategyInterface
{
    public function supports(Operation $operation): bool
    {
        return true;
    }

    public function resolve(Operation $operation, mixed $data, array $context): mixed
    {
        return $data;
    }

    public function getPriority(): int
    {
        return -100;
    }
}
