<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\DataCollector;

use Nexara\ApiPlatformVoter\Debug\VoterDebugger;
use Symfony\Bundle\FrameworkBundle\DataCollector\AbstractDataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class VoterDataCollector extends AbstractDataCollector
{
    public function __construct(
        private readonly VoterDebugger $voterDebugger,
    ) {
    }

    public function collect(Request $request, Response $response, ?Throwable $exception = null): void
    {
        $this->data = [
            'decisions' => $this->voterDebugger->getDecisions(),
            'count' => count($this->voterDebugger->getDecisions()),
        ];
    }

    public function getDecisions(): array
    {
        return $this->data['decisions'] ?? [];
    }

    public function getCount(): int
    {
        return $this->data['count'] ?? 0;
    }

    public function getName(): string
    {
        return 'nexara.voter';
    }

    public static function getTemplate(): ?string
    {
        return '@NexaraApiPlatformVoter/Collector/voter.html.twig';
    }
}
