<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Audit;

final class AuditEntry
{
    public function __construct(
        public readonly string $attribute,
        public readonly string $subjectType,
        public readonly string $decision,
        public readonly ?string $user,
        public readonly ?string $ipAddress,
        public readonly ?string $userAgent,
        public readonly \DateTimeImmutable $timestamp,
        public readonly array $context = [],
    ) {
    }

    public static function fromDecision(
        string $attribute,
        mixed $subject,
        string $decision,
        ?string $user = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        array $context = []
    ): self {
        $subjectType = get_debug_type($subject);

        return new self(
            $attribute,
            $subjectType,
            $decision,
            $user,
            $ipAddress,
            $userAgent,
            new \DateTimeImmutable(),
            $context
        );
    }
}
