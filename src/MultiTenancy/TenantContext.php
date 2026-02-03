<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\MultiTenancy;

final class TenantContext implements TenantContextInterface
{
    private ?TenantInterface $currentTenant = null;

    public function getCurrentTenant(): ?TenantInterface
    {
        return $this->currentTenant;
    }

    public function setCurrentTenant(?TenantInterface $tenant): void
    {
        $this->currentTenant = $tenant;
    }

    public function hasTenant(): bool
    {
        return $this->currentTenant !== null;
    }
}
