<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Metrics;

use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class InMemoryVoterMetricsCollector implements VoterMetricsCollectorInterface
{
    private array $checks = [];

    private array $voterDecisions = [];

    public function recordAuthorizationCheck(string $attribute, bool $granted, float $duration): void
    {
        $this->checks[] = [
            'attribute' => $attribute,
            'granted' => $granted,
            'duration' => $duration,
            'timestamp' => microtime(true),
        ];
    }

    public function recordVoterDecision(string $voterClass, string $attribute, int $decision, float $duration): void
    {
        $this->voterDecisions[] = [
            'voter' => $voterClass,
            'attribute' => $attribute,
            'decision' => $decision,
            'duration' => $duration,
            'timestamp' => microtime(true),
        ];
    }

    public function getMetrics(): VoterMetrics
    {
        $totalChecks = count($this->checks);

        if ($totalChecks === 0) {
            return new VoterMetrics(
                totalChecks: 0,
                grantedCount: 0,
                deniedCount: 0,
                abstainCount: 0,
                averageDuration: 0.0,
                minDuration: 0.0,
                maxDuration: 0.0,
                topAttributes: [],
                slowestChecks: [],
                voterStats: [],
                attributeStats: [],
            );
        }

        $granted = array_filter($this->checks, fn ($check) => $check['granted'] === true);
        $denied = array_filter($this->checks, fn ($check) => $check['granted'] === false);

        $durations = array_column($this->checks, 'duration');
        $avgDuration = array_sum($durations) / count($durations);
        $minDuration = min($durations);
        $maxDuration = max($durations);

        $attributeCounts = [];
        foreach ($this->checks as $check) {
            $attr = $check['attribute'];
            $attributeCounts[$attr] = ($attributeCounts[$attr] ?? 0) + 1;
        }
        arsort($attributeCounts);
        $topAttributes = array_slice($attributeCounts, 0, 10, true);

        usort($this->checks, fn ($a, $b) => $b['duration'] <=> $a['duration']);
        $slowestChecks = array_slice(array_map(
            fn ($check) => [
                'attribute' => $check['attribute'],
                'duration' => $check['duration'],
                'granted' => $check['granted'],
            ],
            $this->checks
        ), 0, 10);

        $voterStats = $this->calculateVoterStats();
        $attributeStats = $this->calculateAttributeStats();

        return new VoterMetrics(
            totalChecks: $totalChecks,
            grantedCount: count($granted),
            deniedCount: count($denied),
            abstainCount: 0,
            averageDuration: $avgDuration,
            minDuration: $minDuration,
            maxDuration: $maxDuration,
            topAttributes: $topAttributes,
            slowestChecks: $slowestChecks,
            voterStats: $voterStats,
            attributeStats: $attributeStats,
        );
    }

    public function reset(): void
    {
        $this->checks = [];
        $this->voterDecisions = [];
    }

    private function calculateVoterStats(): array
    {
        $stats = [];

        foreach ($this->voterDecisions as $decision) {
            $voter = $decision['voter'];

            if (! isset($stats[$voter])) {
                $stats[$voter] = [
                    'total' => 0,
                    'granted' => 0,
                    'denied' => 0,
                    'abstain' => 0,
                    'total_duration' => 0.0,
                ];
            }

            $stats[$voter]['total']++;
            $stats[$voter]['total_duration'] += $decision['duration'];

            match ($decision['decision']) {
                1 => $stats[$voter]['granted']++,
                -1 => $stats[$voter]['denied']++,
                0 => $stats[$voter]['abstain']++,
                default => null,
            };
        }

        foreach ($stats as $voter => $data) {
            $stats[$voter]['average_duration'] = $data['total'] > 0
                ? $data['total_duration'] / $data['total']
                : 0.0;
        }

        return $stats;
    }

    private function calculateAttributeStats(): array
    {
        $stats = [];

        foreach ($this->checks as $check) {
            $attr = $check['attribute'];

            if (! isset($stats[$attr])) {
                $stats[$attr] = [
                    'total' => 0,
                    'granted' => 0,
                    'denied' => 0,
                    'total_duration' => 0.0,
                ];
            }

            $stats[$attr]['total']++;
            $stats[$attr]['total_duration'] += $check['duration'];

            if ($check['granted']) {
                $stats[$attr]['granted']++;
            } else {
                $stats[$attr]['denied']++;
            }
        }

        foreach ($stats as $attr => $data) {
            $stats[$attr]['average_duration'] = $data['total'] > 0
                ? $data['total_duration'] / $data['total']
                : 0.0;
            $stats[$attr]['success_rate'] = $data['total'] > 0
                ? ($data['granted'] / $data['total']) * 100
                : 0.0;
        }

        return $stats;
    }
}
