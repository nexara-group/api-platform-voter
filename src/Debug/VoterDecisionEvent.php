<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Debug;

use Symfony\Contracts\EventDispatcher\Event;

final class VoterDecisionEvent extends Event
{
    public const PRE_VOTE = 'nexara.voter.pre_vote';

    public const POST_VOTE = 'nexara.voter.post_vote';

    public const ACCESS_GRANTED = 'nexara.voter.access_granted';

    public const ACCESS_DENIED = 'nexara.voter.access_denied';

    public function __construct(
        public readonly string $attribute,
        public readonly mixed $subject,
        public readonly int $decision,
        public readonly string $voterClass,
        public readonly ?string $reason = null,
        public readonly array $context = [],
    ) {
    }

    public function isGranted(): bool
    {
        return $this->decision === 1;
    }

    public function isDenied(): bool
    {
        return $this->decision === -1;
    }

    public function isAbstain(): bool
    {
        return $this->decision === 0;
    }
}
