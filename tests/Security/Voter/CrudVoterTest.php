<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Tests\Security\Voter;

use Nexara\ApiPlatformVoter\Voter\CrudVoter;
use Nexara\ApiPlatformVoter\Voter\TargetVoterSubject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class CrudVoterTest extends TestCase
{
    public function testCustomOperationMustBeExplicitlyAllowed(): void
    {
        $voter = new TestFooVoter();
        $token = $this->createMock(TokenInterface::class);

        $foo = new Foo();

        self::assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $foo, ['foo:read']));
        self::assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $foo, ['foo:publish']));
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $voter->vote($token, $foo, ['foo:unknown']));
    }

    public function testTargetVoterSubjectFiltersByVoterClass(): void
    {
        $voter = new TestFooVoter();
        $token = $this->createMock(TokenInterface::class);

        $foo = new Foo();

        self::assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote($token, new TargetVoterSubject($foo, 'Other\\Voter'), ['foo:read'])
        );

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $voter->vote($token, new TargetVoterSubject($foo, TestFooVoter::class), ['foo:read'])
        );
    }
}

final class Foo
{
}

final class TestFooVoter extends CrudVoter
{
    public function __construct()
    {
        $this->setPrefix('foo');
        $this->setResourceClasses(Foo::class);
        $this->customOperations = ['publish'];
    }

    protected function canRead(mixed $object): bool
    {
        return true;
    }

    protected function canCustomOperation(string $operation, mixed $object, mixed $previousObject): bool
    {
        return $operation === 'publish';
    }
}
