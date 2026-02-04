<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\MultiTenancy;

trait TenantAwareVoterTrait
{
    private ?TenantContextInterface $tenantContext = null;

    public function setTenantContext(TenantContextInterface $tenantContext): void
    {
        $this->tenantContext = $tenantContext;
    }

    protected function getCurrentTenant(): ?TenantInterface
    {
        return $this->tenantContext?->getCurrentTenant();
    }

    protected function belongsToCurrentTenant(mixed $object): bool
    {
        if (! $object instanceof TenantAwareInterface) {
            return true;
        }

        $currentTenant = $this->getCurrentTenant();
        if ($currentTenant === null) {
            return false;
        }

        $objectTenant = $object->getTenant();
        if ($objectTenant === null) {
            return false;
        }

        return $objectTenant->getId() === $currentTenant->getId();
    }

    protected function belongsToTenant(mixed $object, TenantInterface $tenant): bool
    {
        if (! $object instanceof TenantAwareInterface) {
            return true;
        }

        $objectTenant = $object->getTenant();
        if ($objectTenant === null) {
            return false;
        }

        return $objectTenant->getId() === $tenant->getId();
    }
}
