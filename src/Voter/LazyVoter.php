<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Voter;

use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class LazyVoter implements VoterInterface
{
    private ?VoterInterface $realVoter = null;

    public function __construct(
        private readonly string $voterServiceId,
        private readonly ContainerInterface $container,
    ) {
    }

    public function vote(TokenInterface $token, mixed $subject, array $attributes): int
    {
        return $this->getRealVoter()->vote($token, $subject, $attributes);
    }

    private function getRealVoter(): VoterInterface
    {
        if ($this->realVoter === null) {
            $voter = $this->container->get($this->voterServiceId);

            if (! $voter instanceof VoterInterface) {
                throw new \RuntimeException(sprintf(
                    'Service "%s" must implement %s',
                    $this->voterServiceId,
                    VoterInterface::class
                ));
            }

            $this->realVoter = $voter;
        }

        return $this->realVoter;
    }
}
