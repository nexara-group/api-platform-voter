<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Testing;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\User\UserInterface;

abstract class VoterTestCase extends TestCase
{
    protected VoterInterface $voter;

    protected TokenInterface $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->voter = $this->createVoter();
        $this->token = $this->createMock(TokenInterface::class);
    }

    abstract protected function createVoter(): VoterInterface;

    protected function assertVoterGrants(string $attribute, mixed $subject, ?string $message = null): void
    {
        $message ??= "Expected voter to GRANT access for attribute '{$attribute}'";

        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($this->token, $subject, [$attribute]),
            $message
        );
    }

    protected function assertVoterDenies(string $attribute, mixed $subject, ?string $message = null): void
    {
        $message ??= "Expected voter to DENY access for attribute '{$attribute}'";

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($this->token, $subject, [$attribute]),
            $message
        );
    }

    protected function assertVoterAbstains(string $attribute, mixed $subject, ?string $message = null): void
    {
        $message ??= "Expected voter to ABSTAIN from voting for attribute '{$attribute}'";

        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->token, $subject, [$attribute]),
            $message
        );
    }

    /**
     * @param array<string> $roles
     * @param array<string, mixed> $additionalMethods
     */
    protected function mockUser(
        array $roles = [],
        string $identifier = 'test@example.com',
        array $additionalMethods = []
    ): UserInterface {
        $user = $this->createMock(UserInterface::class);
        $user->method('getRoles')->willReturn($roles);
        $user->method('getUserIdentifier')->willReturn($identifier);

        foreach ($additionalMethods as $method => $returnValue) {
            /** @var \PHPUnit\Framework\MockObject\MockObject $user */
            $user->method($method)->willReturn($returnValue);
        }

        $this->token->method('getUser')->willReturn($user);

        return $user;
    }

    protected function mockAnonymousUser(): void
    {
        $this->token->method('getUser')->willReturn(null);
    }

    protected function assertVoterSupports(string $attribute, mixed $subject): void
    {
        $result = $this->voter->vote($this->token, $subject, [$attribute]);

        $this->assertNotSame(
            VoterInterface::ACCESS_ABSTAIN,
            $result,
            "Expected voter to support attribute '{$attribute}', but it abstained"
        );
    }

    protected function assertVoterDoesNotSupport(string $attribute, mixed $subject): void
    {
        $result = $this->voter->vote($this->token, $subject, [$attribute]);

        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $result,
            "Expected voter to NOT support attribute '{$attribute}', but it did"
        );
    }
}
