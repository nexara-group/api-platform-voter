<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Security\Permission;

final class HierarchicalPermissionChecker
{
    public function __construct(
        private array $hierarchy = []
    ) {
    }

    public function setHierarchy(array $hierarchy): void
    {
        $this->hierarchy = $hierarchy;
    }

    public function addPermission(string $permission, array $implies = []): void
    {
        $this->hierarchy[$permission] = $implies;
    }

    public function implies(string $permission, string $requiredPermission): bool
    {
        if ($permission === $requiredPermission) {
            return true;
        }

        if (! isset($this->hierarchy[$permission])) {
            return false;
        }

        $impliedPermissions = $this->hierarchy[$permission];

        if (in_array($requiredPermission, $impliedPermissions, true)) {
            return true;
        }

        foreach ($impliedPermissions as $impliedPermission) {
            if ($this->implies($impliedPermission, $requiredPermission)) {
                return true;
            }
        }

        return false;
    }

    public function hasPermission(array $userPermissions, string $requiredPermission): bool
    {
        foreach ($userPermissions as $permission) {
            if ($this->implies($permission, $requiredPermission)) {
                return true;
            }
        }

        return false;
    }

    public function expandPermissions(array $permissions): array
    {
        $expanded = [];

        foreach ($permissions as $permission) {
            $expanded[] = $permission;
            $expanded = array_merge($expanded, $this->getImpliedPermissions($permission));
        }

        return array_unique($expanded);
    }

    public function getImpliedPermissions(string $permission): array
    {
        if (! isset($this->hierarchy[$permission])) {
            return [];
        }

        $implied = $this->hierarchy[$permission];
        $allImplied = $implied;

        foreach ($implied as $impliedPermission) {
            $allImplied = array_merge($allImplied, $this->getImpliedPermissions($impliedPermission));
        }

        return array_unique($allImplied);
    }

    public function getHierarchy(): array
    {
        return $this->hierarchy;
    }
}
