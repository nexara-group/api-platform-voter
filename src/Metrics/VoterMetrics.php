<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Metrics;

final class VoterMetrics
{
    public function __construct(
        public readonly int $totalChecks,
        public readonly int $grantedCount,
        public readonly int $deniedCount,
        public readonly int $abstainCount,
        public readonly float $averageDuration,
        public readonly float $minDuration,
        public readonly float $maxDuration,
        public readonly array $topAttributes,
        public readonly array $slowestChecks,
        public readonly array $voterStats,
        public readonly array $attributeStats,
    ) {
    }

    public function getSuccessRate(): float
    {
        if ($this->totalChecks === 0) {
            return 0.0;
        }

        return ($this->grantedCount / $this->totalChecks) * 100;
    }

    public function getDenialRate(): float
    {
        if ($this->totalChecks === 0) {
            return 0.0;
        }

        return ($this->deniedCount / $this->totalChecks) * 100;
    }

    public function getAbstainRate(): float
    {
        if ($this->totalChecks === 0) {
            return 0.0;
        }

        return ($this->abstainCount / $this->totalChecks) * 100;
    }

    public function toArray(): array
    {
        return [
            'total_checks' => $this->totalChecks,
            'granted' => $this->grantedCount,
            'denied' => $this->deniedCount,
            'abstain' => $this->abstainCount,
            'success_rate' => $this->getSuccessRate(),
            'denial_rate' => $this->getDenialRate(),
            'abstain_rate' => $this->getAbstainRate(),
            'average_duration_ms' => $this->averageDuration,
            'min_duration_ms' => $this->minDuration,
            'max_duration_ms' => $this->maxDuration,
            'top_attributes' => $this->topAttributes,
            'slowest_checks' => $this->slowestChecks,
            'voter_stats' => $this->voterStats,
            'attribute_stats' => $this->attributeStats,
        ];
    }
}
