<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Security\AttributeGenerator;

use ApiPlatform\Metadata\Operation;

interface DynamicAttributeGeneratorInterface
{
    public function generate(Operation $operation, mixed $data, array $context): ?string;

    public function supports(string $resourceClass): bool;
}
