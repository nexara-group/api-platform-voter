<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Debug;

/**
 * Visualizes the voter decision chain for debugging purposes.
 *
 * Provides a clear, visual representation of how voters make authorization decisions.
 */
final class VoterChainVisualizer
{
    public function __construct(
        private readonly VoterDebugger $debugger
    ) {
    }

    /**
     * Generates a visual representation of the voter decision chain.
     */
    public function visualize(string $attribute, ?string $subjectType = null): string
    {
        $decisions = $this->debugger->getDecisionsForAttribute($attribute);

        if ($decisions === []) {
            return sprintf(
                "🔒 No voter decisions recorded for attribute '%s'\n\n" .
                "💡 Tip: Enable debug mode to see voter decisions:\n" .
                "   nexara_api_platform_voter:\n" .
                '       debug: true',
                $attribute
            );
        }

        $output = sprintf("🔒 Authorization Chain for \"%s\"\n", $attribute);

        if ($subjectType !== null) {
            $output .= sprintf("   Subject: %s\n", $subjectType);
        }

        $output .= "\n";

        $step = 1;
        foreach ($decisions as $decision) {
            $output .= $this->formatDecision($decision, $step);
            $step++;
        }

        $output .= "\n" . $this->getFinalDecision($decisions);

        return $output;
    }

    /**
     * Generates a compact summary of voter decisions.
     */
    public function summarize(string $attribute): string
    {
        $decisions = $this->debugger->getDecisionsForAttribute($attribute);

        if ($decisions === []) {
            return sprintf("No decisions for '%s'", $attribute);
        }

        $granted = 0;
        $denied = 0;
        $abstain = 0;

        foreach ($decisions as $decision) {
            match ($decision['decision']) {
                'GRANTED' => $granted++,
                'DENIED' => $denied++,
                'ABSTAIN' => $abstain++,
                default => null,
            };
        }

        $total = count($decisions);

        return sprintf(
            '📊 %d voter(s): ✅ %d granted, ❌ %d denied, ⊘ %d abstained',
            $total,
            $granted,
            $denied,
            $abstain
        );
    }

    /**
     * Generates a tree-like visualization.
     */
    public function visualizeAsTree(string $attribute): string
    {
        $decisions = $this->debugger->getDecisionsForAttribute($attribute);

        if ($decisions === []) {
            return sprintf("No decisions for '%s'", $attribute);
        }

        $output = sprintf("🌳 Voter Decision Tree: %s\n", $attribute);
        $output .= "│\n";

        $count = count($decisions);

        foreach ($decisions as $index => $decision) {
            $isLast = ($index === $count - 1);
            $connector = $isLast ? '└──' : '├──';
            $continuation = $isLast ? '    ' : '│   ';

            $icon = $this->getDecisionIcon($decision['decision']);
            $voterName = $this->getShortClassName($decision['voter']);

            $output .= sprintf("%s %s %s\n", $connector, $icon, $voterName);

            if ($decision['decision'] !== 'ABSTAIN') {
                $output .= sprintf("%s    Decision: %s\n", $continuation, $decision['decision']);

                if ($decision['reason'] !== null) {
                    $output .= sprintf("%s    Reason: %s\n", $continuation, $decision['reason']);
                }
            }

            if (! $isLast) {
                $output .= "│\n";
            }
        }

        return $output;
    }

    /**
     * Formats a single voter decision.
     */
    private function formatDecision(array $decision, int $step): string
    {
        $icon = $this->getDecisionIcon($decision['decision']);
        $voterName = $this->getShortClassName($decision['voter']);

        $output = sprintf("%d. %s %s\n", $step, $icon, $voterName);
        $output .= sprintf("   │  Decision: %s\n", $decision['decision']);

        if ($decision['reason'] !== null) {
            $output .= sprintf("   │  Reason: %s\n", $decision['reason']);
        }

        if (! empty($decision['context'])) {
            $output .= "   │  Context:\n";
            foreach ($decision['context'] as $key => $value) {
                $output .= sprintf("   │    - %s: %s\n", $key, $this->formatValue($value));
            }
        }

        $output .= "   │\n";

        return $output;
    }

    /**
     * Determines the final decision from all voter decisions.
     */
    private function getFinalDecision(array $decisions): string
    {
        // Affirmative strategy: At least one GRANTED = GRANTED
        // If no GRANTED but at least one DENIED = DENIED
        // Otherwise ABSTAIN

        $hasGranted = false;
        $hasDenied = false;

        foreach ($decisions as $decision) {
            if ($decision['decision'] === 'GRANTED') {
                $hasGranted = true;
                break;
            }
            if ($decision['decision'] === 'DENIED') {
                $hasDenied = true;
            }
        }

        if ($hasGranted) {
            return '🎉 Final Decision: ✅ ACCESS GRANTED';
        }

        if ($hasDenied) {
            return '🚫 Final Decision: ❌ ACCESS DENIED';
        }

        return '❓ Final Decision: ⊘ NO DECISION (all voters abstained)';
    }

    private function getDecisionIcon(string $decision): string
    {
        return match ($decision) {
            'GRANTED' => '✅',
            'DENIED' => '❌',
            'ABSTAIN' => '⊘',
            default => '❓',
        };
    }

    private function getShortClassName(string $fqcn): string
    {
        $parts = explode('\\', $fqcn);
        return end($parts);
    }

    private function formatValue(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_SLASHES);
        }

        if (is_object($value)) {
            return $value::class;
        }

        return (string) $value;
    }
}
