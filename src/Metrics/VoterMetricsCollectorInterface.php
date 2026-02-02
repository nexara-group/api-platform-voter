<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Metrics;

interface VoterMetricsCollectorInterface
{
    public function recordAuthorizationCheck(string $attribute, bool $granted, float $duration): void;

    public function recordVoterDecision(string $voterClass, string $attribute, int $decision, float $duration): void;

    public function getMetrics(): VoterMetrics;

    public function reset(): void;
}
