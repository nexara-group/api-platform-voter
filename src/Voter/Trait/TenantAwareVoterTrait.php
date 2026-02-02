<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Security\Voter\Trait;

use Nexara\ApiPlatformVoter\MultiTenancy\TenantAwareInterface;
use Nexara\ApiPlatformVoter\MultiTenancy\TenantContextInterface;
use Nexara\ApiPlatformVoter\MultiTenancy\TenantInterface;

trait TenantAwareVoterTrait
{
    private ?TenantContextInterface $tenantContext = null;

    public function setTenantContext(TenantContextInterface $context): void
    {
        $this->tenantContext = $context;
    }

    protected function getCurrentTenant(): ?TenantInterface
    {
        return $this->tenantContext?->getCurrentTenant();
    }

    protected function belongsToTenant(TenantAwareInterface $object): bool
    {
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

    protected function belongsToCurrentTenant(mixed $object): bool
    {
        if (! $object instanceof TenantAwareInterface) {
            return true;
        }

        return $this->belongsToTenant($object);
    }

    protected function isMultiTenantEnabled(): bool
    {
        return $this->tenantContext !== null && $this->tenantContext->hasTenant();
    }
}
