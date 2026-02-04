<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Debug;

use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class VoterDebugger
{
    private array $decisions = [];

    public function __construct(
        private readonly ?LoggerInterface $logger = null,
        private bool $enabled = false
    ) {
    }

    public function enable(): void
    {
        $this->enabled = true;
    }

    public function disable(): void
    {
        $this->enabled = false;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function recordDecision(
        string $voterClass,
        string $attribute,
        mixed $subject,
        int $decision,
        ?string $reason = null,
        array $context = []
    ): void {
        if (! $this->enabled) {
            return;
        }

        $decisionName = match ($decision) {
            VoterInterface::ACCESS_GRANTED => 'GRANTED',
            VoterInterface::ACCESS_DENIED => 'DENIED',
            VoterInterface::ACCESS_ABSTAIN => 'ABSTAIN',
            default => 'UNKNOWN',
        };

        $record = [
            'voter' => $voterClass,
            'attribute' => $attribute,
            'subject' => $this->formatSubject($subject),
            'decision' => $decisionName,
            'reason' => $reason,
            'context' => $context,
            'timestamp' => new \DateTimeImmutable(),
        ];

        $this->decisions[] = $record;

        if ($this->logger) {
            $this->logger->info('Voter decision recorded', $record);
        }
    }

    public function getDecisions(): array
    {
        return $this->decisions;
    }

    public function clearDecisions(): void
    {
        $this->decisions = [];
    }

    public function getDecisionsForAttribute(string $attribute): array
    {
        return array_filter(
            $this->decisions,
            fn (array $decision) => $decision['attribute'] === $attribute
        );
    }

    public function formatDebugOutput(string $attribute): string
    {
        $decisions = $this->getDecisionsForAttribute($attribute);

        if ($decisions === []) {
            return "No voter decisions recorded for attribute '{$attribute}'";
        }

        $output = "🔒 Authorization Decisions for '{$attribute}'\n\n";

        foreach ($decisions as $decision) {
            $icon = match ($decision['decision']) {
                'GRANTED' => '✅',
                'DENIED' => '❌',
                'ABSTAIN' => '⊘',
                default => '❓',
            };

            $output .= sprintf(
                "%s %s: %s\n",
                $icon,
                $this->getShortClassName($decision['voter']),
                $decision['decision']
            );

            if ($decision['reason'] !== null) {
                $output .= "   Reason: {$decision['reason']}\n";
            }

            if ($decision['context'] !== []) {
                $output .= '   Context: ' . json_encode($decision['context'], JSON_PRETTY_PRINT) . "\n";
            }

            $output .= "\n";
        }

        return $output;
    }

    public function formatJsonOutput(): string
    {
        return json_encode($this->decisions, JSON_PRETTY_PRINT);
    }

    private function formatSubject(mixed $subject): string
    {
        if (is_object($subject)) {
            $class = $subject::class;
            if (method_exists($subject, 'getId')) {
                return sprintf('%s#%s', $class, $subject->getId());
            }

            return $class;
        }

        if (is_array($subject)) {
            return 'array[' . count($subject) . ']';
        }

        return (string) $subject;
    }

    private function getShortClassName(string $fqcn): string
    {
        $parts = explode('\\', $fqcn);

        return end($parts);
    }
}
