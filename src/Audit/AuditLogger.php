<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Audit;

use Psr\Log\LoggerInterface;

final class AuditLogger implements AuditLoggerInterface
{
    public function __construct(
        private readonly ?LoggerInterface $logger = null,
        private readonly bool $enabled = false,
        private readonly string $level = 'all',
        private readonly bool $includeContext = true,
    ) {
    }

    public function log(AuditEntry $entry): void
    {
        if (! $this->enabled) {
            return;
        }

        if (! $this->shouldLog($entry)) {
            return;
        }

        if ($this->logger) {
            $context = $this->includeContext ? $entry->context : [];
            
            $this->logger->info('Authorization decision', [
                'attribute' => $entry->attribute,
                'subject_type' => $entry->subjectType,
                'decision' => $entry->decision,
                'user' => $entry->user,
                'ip_address' => $entry->ipAddress,
                'user_agent' => $entry->userAgent,
                'timestamp' => $entry->timestamp->format('Y-m-d H:i:s'),
                'context' => $context,
            ]);
        }
    }

    private function shouldLog(AuditEntry $entry): bool
    {
        return match ($this->level) {
            'denied_only' => $entry->decision === 'DENIED',
            'granted_only' => $entry->decision === 'GRANTED',
            'all' => true,
            default => true,
        };
    }
}
