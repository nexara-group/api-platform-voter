<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Security\Voter;

final class TargetVoterSubject
{
    public function __construct(
        public readonly mixed $subject,
        public readonly string $voterClass,
    ) {
    }
}
