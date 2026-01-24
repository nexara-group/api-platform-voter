<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\ApiPlatform\Security;

use ApiPlatform\Metadata\Operation;

interface OperationToVoterAttributeMapperInterface
{
    public function map(Operation $operation, string $prefix): ?string;
}
