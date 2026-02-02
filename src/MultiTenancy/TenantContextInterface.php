<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\MultiTenancy;

interface TenantContextInterface
{
    public function getCurrentTenant(): ?TenantInterface;

    public function setCurrentTenant(?TenantInterface $tenant): void;

    public function hasTenant(): bool;
}
