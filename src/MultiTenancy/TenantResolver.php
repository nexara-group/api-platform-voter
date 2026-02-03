<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\MultiTenancy;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

final class TenantResolver
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly Security $security,
        private readonly TenantContextInterface $tenantContext,
    ) {
    }

    public function resolve(): ?TenantInterface
    {
        // Try to get tenant from current user
        $user = $this->security->getUser();
        if ($user instanceof TenantAwareInterface) {
            $tenant = $user->getTenant();
            if ($tenant !== null) {
                return $tenant;
            }
        }

        // Try to get tenant from request header
        $request = $this->requestStack->getCurrentRequest();
        if ($request !== null) {
            $tenantId = $request->headers->get('X-Tenant-ID');
            if ($tenantId !== null) {
                // In real implementation, you'd fetch tenant from database
                // For now, return null as we don't have tenant repository
                return null;
            }
        }

        // Try to get tenant from context
        return $this->tenantContext->getCurrentTenant();
    }
}
