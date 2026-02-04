<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Voter;

use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Lazy-loading proxy for voters.
 *
 * Delays voter instantiation until it's actually needed for a vote.
 * This improves performance when many voters are registered but only
 * a few are used per request.
 */
final class LazyVoterProxy implements VoterInterface
{
    private ?VoterInterface $voter = null;

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly string $serviceId
    ) {
    }

    public function vote(TokenInterface $token, mixed $subject, array $attributes): int
    {
        return $this->getVoter()->vote($token, $subject, $attributes);
    }

    private function getVoter(): VoterInterface
    {
        if ($this->voter === null) {
            $service = $this->container->get($this->serviceId);

            if (! $service instanceof VoterInterface) {
                throw new \LogicException(sprintf(
                    'Service "%s" must implement %s',
                    $this->serviceId,
                    VoterInterface::class
                ));
            }

            $this->voter = $service;
        }

        return $this->voter;
    }
}
