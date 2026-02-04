<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Testing;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\RoleHierarchyVoter;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchy;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Test trait for voter testing with proper role hierarchy support.
 *
 * Solves the issue where Security::isGranted() doesn't work correctly with role hierarchy in tests.
 *
 * @example
 * ```php
 * use Nexara\ApiPlatformVoter\Testing\VoterTestTrait;
 *
 * class ArticleVoterTest extends TestCase
 * {
 *     use VoterTestTrait;
 *
 *     public function testModeratorCanPublish(): void
 *     {
 *         $user = $this->createUser(['ROLE_MODERATOR']);
 *         $security = $this->createSecurityWithRoleHierarchy([
 *             'ROLE_ADMIN' => ['ROLE_MODERATOR', 'ROLE_USER'],
 *             'ROLE_MODERATOR' => ['ROLE_USER'],
 *         ], $user);
 *
 *         $voter = new ArticleVoter($security);
 *         // Now $security->isGranted('ROLE_USER') returns true for MODERATOR
 *     }
 * }
 * ```
 */
trait VoterTestTrait
{
    /**
     * Creates a Security instance with proper role hierarchy support for testing.
     *
     * @param array<string, array<string>> $roleHierarchy Role hierarchy map
     * @param UserInterface|null $user Current user (null for anonymous)
     * @return object Security-like object with isGranted() and getUser() methods
     */
    protected function createSecurityWithRoleHierarchy(
        array $roleHierarchy,
        ?UserInterface $user = null
    ): object {
        $hierarchy = new RoleHierarchy($roleHierarchy);
        $roleHierarchyVoter = new RoleHierarchyVoter($hierarchy);

        $accessDecisionManager = new class($roleHierarchyVoter) implements AccessDecisionManagerInterface {
            public function __construct(
                private readonly VoterInterface $voter
            ) {
            }

            public function decide(TokenInterface $token, array $attributes, mixed $object = null): bool
            {
                foreach ($attributes as $attribute) {
                    $result = $this->voter->vote($token, $object, [$attribute]);
                    if ($result === VoterInterface::ACCESS_GRANTED) {
                        return true;
                    }
                }
                return false;
            }
        };

        $token = $this->createToken($user);

        return new class($accessDecisionManager, $token) {
            public function __construct(
                private readonly AccessDecisionManagerInterface $decisionManager,
                private readonly TokenInterface $token
            ) {
            }

            public function isGranted(mixed $attribute, mixed $subject = null): bool
            {
                return $this->decisionManager->decide(
                    $this->token,
                    is_array($attribute) ? $attribute : [$attribute],
                    $subject
                );
            }

            public function getUser(): ?UserInterface
            {
                $user = $this->token->getUser();
                return $user instanceof UserInterface ? $user : null;
            }
        };
    }

    /**
     * Expands user roles according to role hierarchy.
     *
     * @param array<string> $roles User roles
     * @param array<string, array<string>> $roleHierarchy Role hierarchy
     * @return array<string> Expanded roles
     */
    protected function expandRoles(array $roles, array $roleHierarchy): array
    {
        $expandedRoles = $roles;

        foreach ($roles as $role) {
            if (isset($roleHierarchy[$role])) {
                $expandedRoles = array_merge(
                    $expandedRoles,
                    $this->expandRoles($roleHierarchy[$role], $roleHierarchy)
                );
            }
        }

        return array_unique($expandedRoles);
    }

    /**
     * Creates a mock user with given roles.
     *
     * @param array<string> $roles User roles
     */
    protected function createUser(
        array $roles = ['ROLE_USER'],
        string $identifier = 'test@example.com'
    ): UserInterface {
        $user = $this->createMock(UserInterface::class);
        $user->method('getRoles')->willReturn($roles);
        $user->method('getUserIdentifier')->willReturn($identifier);

        return $user;
    }

    /**
     * Creates a mock method for PHPUnit.
     */
    abstract protected function createMock(string $className): object;

    /**
     * Creates an authentication token.
     */
    private function createToken(?UserInterface $user): TokenInterface
    {
        if ($user === null) {
            $token = $this->createMock(TokenInterface::class);
            $token->method('getUser')->willReturn(null);
            $token->method('getRoleNames')->willReturn([]);
            return $token;
        }

        return new UsernamePasswordToken(
            $user,
            'main',
            $user->getRoles()
        );
    }
}
