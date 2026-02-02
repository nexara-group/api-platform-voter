<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\ApiPlatform\Security;

use ApiPlatform\Metadata\Operation;

interface SubjectResolverStrategyInterface
{
    public function supports(Operation $operation): bool;

    public function resolve(Operation $operation, mixed $data, array $context): mixed;

    public function getPriority(): int;
}
