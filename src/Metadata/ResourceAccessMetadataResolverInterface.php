<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Metadata;

interface ResourceAccessMetadataResolverInterface
{
    public function resolve(string $resourceClass): ResourceAccessMetadata;
}
