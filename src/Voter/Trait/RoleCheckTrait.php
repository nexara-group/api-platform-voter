<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Security\Voter\Trait;

use Symfony\Bundle\SecurityBundle\Security;

trait RoleCheckTrait
{
    abstract protected function getSecurity(): Security;

    protected function hasRole(string $role): bool
    {
        return $this->getSecurity()->isGranted($role);
    }

    protected function hasAnyRole(string ...$roles): bool
    {
        foreach ($roles as $role) {
            if ($this->getSecurity()->isGranted($role)) {
                return true;
            }
        }

        return false;
    }

    protected function hasAllRoles(string ...$roles): bool
    {
        foreach ($roles as $role) {
            if (! $this->getSecurity()->isGranted($role)) {
                return false;
            }
        }

        return true;
    }

    protected function isAdmin(): bool
    {
        return $this->hasRole('ROLE_ADMIN');
    }

    protected function isSuperAdmin(): bool
    {
        return $this->hasRole('ROLE_SUPER_ADMIN');
    }

    protected function isAuthenticated(): bool
    {
        return $this->getSecurity()->getUser() !== null;
    }
}
