<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Testing;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManager;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Strategy\AffirmativeStrategy;
use Symfony\Component\Security\Core\Authorization\Voter\RoleHierarchyVoter;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchy;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Builder for creating Security instances with proper role hierarchy in tests.
 *
 * This builder solves the problem where Security::isGranted() doesn't properly
 * evaluate role hierarchy in Symfony test environment.
 *
 * @example
 * ```php
 * $security = SecurityBuilder::create()
 *     ->withRoleHierarchy([
 *         'ROLE_ADMIN' => ['ROLE_MODERATOR', 'ROLE_USER'],
 *         'ROLE_MODERATOR' => ['ROLE_USER'],
 *     ])
 *     ->withUser($user)
 *     ->build();
 *
 * // Now MODERATOR users will have ROLE_USER
 * $security->isGranted('ROLE_USER'); // true for MODERATOR
 * ```
 */
final class SecurityBuilder
{
    /**
     * @var array<string, array<string>>
     */
    private array $roleHierarchy = [];

    private ?UserInterface $user = null;

    /**
     * @var array<VoterInterface>
     */
    private array $voters = [];

    private string $firewallName = 'main';

    private bool $allowIfAllAbstain = false;

    public static function create(): self
    {
        return new self();
    }

    /**
     * @param array<string, array<string>> $hierarchy
     */
    public function withRoleHierarchy(array $hierarchy): self
    {
        $this->roleHierarchy = $hierarchy;
        return $this;
    }

    public function withUser(?UserInterface $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function withVoter(VoterInterface $voter): self
    {
        $this->voters[] = $voter;
        return $this;
    }

    public function withFirewallName(string $name): self
    {
        $this->firewallName = $name;
        return $this;
    }

    public function allowIfAllAbstain(bool $allow = true): self
    {
        $this->allowIfAllAbstain = $allow;
        return $this;
    }

    /**
     * @return object Security-like object with isGranted() and getUser() methods
     */
    public function build(): object
    {
        $voters = $this->voters;

        // Add role hierarchy voter if hierarchy is defined
        if ($this->roleHierarchy !== []) {
            $hierarchy = new RoleHierarchy($this->roleHierarchy);
            $voters[] = new RoleHierarchyVoter($hierarchy);
        }

        $accessDecisionManager = new AccessDecisionManager(
            $voters,
            new AffirmativeStrategy($this->allowIfAllAbstain)
        );

        $token = $this->createToken();

        return new TestSecurity($accessDecisionManager, $token);
    }

    private function createToken(): TokenInterface
    {
        if ($this->user === null) {
            return new class() implements TokenInterface {
                public function __serialize(): array
                {
                    return [];
                }

                public function __unserialize(array $data): void
                {
                }

                public function __toString(): string
                {
                    return 'anonymous';
                }

                public function getRoleNames(): array
                {
                    return [];
                }

                public function getUser(): ?UserInterface
                {
                    return null;
                }

                public function setUser(UserInterface $user): void
                {
                }

                public function getUserIdentifier(): string
                {
                    return '';
                }

                public function isAuthenticated(): bool
                {
                    return false;
                }

                public function setAuthenticated(bool $authenticated): void
                {
                }

                public function eraseCredentials(): void
                {
                }

                public function getAttributes(): array
                {
                    return [];
                }

                public function setAttributes(array $attributes): void
                {
                }

                public function hasAttribute(string $name): bool
                {
                    return false;
                }

                public function getAttribute(string $name): mixed
                {
                    return null;
                }

                public function setAttribute(string $name, mixed $value): void
                {
                }
            };
        }

        return new UsernamePasswordToken(
            $this->user,
            $this->firewallName,
            $this->user->getRoles()
        );
    }
}

/**
 * Test implementation of Security service.
 *
 * @internal
 */
final class TestSecurity
{
    public function __construct(
        private readonly AccessDecisionManagerInterface $decisionManager,
        private readonly TokenInterface $token
    ) {
    }

    public function isGranted(mixed $attribute, mixed $subject = null): bool
    {
        $attributes = is_array($attribute) ? $attribute : [$attribute];

        return $this->decisionManager->decide(
            $this->token,
            $attributes,
            $subject
        );
    }

    public function getUser(): ?UserInterface
    {
        $user = $this->token->getUser();
        return $user instanceof UserInterface ? $user : null;
    }

    public function getToken(): ?TokenInterface
    {
        return $this->token;
    }
}
