<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\ApiPlatform\Security;

use ApiPlatform\Metadata\Operation;

interface SubjectResolverInterface
{
    public function resolve(Operation $operation, mixed $data, array $context): mixed;
}
