<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\ApiPlatform\Security;

final class ResourceAccessMetadata
{
    public function __construct(
        public readonly bool $protected,
        public readonly ?string $prefix,
        public readonly ?string $voter,
    ) {
    }
}
