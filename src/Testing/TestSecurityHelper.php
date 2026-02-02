<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Testing;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchy;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Helper for testing with proper role hierarchy support.
 *
 * Solves the issue where Security::isGranted() doesn't properly evaluate
 * role hierarchy in test environment.
 */
final class TestSecurityHelper
{
    private readonly RoleHierarchy $roleHierarchy;

    /**
     * @param array<string, array<string>> $hierarchy
     */
    public function __construct(array $hierarchy = [])
    {
        $this->roleHierarchy = new RoleHierarchy($hierarchy);
    }

    /**
     * Create a user with roles expanded by hierarchy.
     */
    public function createUser(string $identifier, array $roles, ?string $password = null): UserInterface
    {
        $expandedRoles = $this->roleHierarchy->getReachableRoleNames($roles);

        return new InMemoryUser($identifier, $password, array_values($expandedRoles));
    }

    /**
     * Create a token with proper role hierarchy.
     */
    public function createToken(UserInterface $user, string $firewallName = 'main'): TokenInterface
    {
        return new UsernamePasswordToken($user, $firewallName, $user->getRoles());
    }

    /**
     * Check if user has role (considering hierarchy).
     */
    public function hasRole(UserInterface $user, string $role): bool
    {
        $reachableRoles = $this->roleHierarchy->getReachableRoleNames($user->getRoles());

        return in_array($role, $reachableRoles, true);
    }

    /**
     * Test voter decision with proper role hierarchy.
     */
    public function testVoterDecision(
        VoterInterface $voter,
        UserInterface $user,
        string $attribute,
        mixed $subject
    ): int {
        $token = $this->createToken($user);

        return $voter->vote($token, $subject, [$attribute]);
    }

    /**
     * Get default role hierarchy (standard Symfony roles).
     */
    public static function getDefaultHierarchy(): array
    {
        return [
            'ROLE_SUPER_ADMIN' => ['ROLE_ADMIN', 'ROLE_USER'],
            'ROLE_ADMIN' => ['ROLE_MODERATOR', 'ROLE_USER'],
            'ROLE_MODERATOR' => ['ROLE_USER'],
        ];
    }

    /**
     * Create helper with standard role hierarchy.
     */
    public static function withDefaultHierarchy(): self
    {
        return new self(self::getDefaultHierarchy());
    }
}
