<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\ApiPlatform\Security;

interface ResourceAccessMetadataResolverInterface
{
    public function resolve(string $resourceClass): ResourceAccessMetadata;
}
