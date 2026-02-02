<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\MultiTenancy;

interface TenantAwareInterface
{
    public function getTenant(): ?TenantInterface;

    public function setTenant(?TenantInterface $tenant): void;
}
