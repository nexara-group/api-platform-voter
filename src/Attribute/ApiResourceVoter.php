<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class ApiResourceVoter
{
    public function __construct(
        public readonly ?string $prefix = null,
        public readonly ?string $voter = null,
    ) {
    }
}
