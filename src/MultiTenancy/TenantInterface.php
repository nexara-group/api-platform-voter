<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\MultiTenancy;

interface TenantInterface
{
    public function getId(): int|string;

    public function getIdentifier(): string;
}
