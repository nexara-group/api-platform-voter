<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Audit;

interface AuditLoggerInterface
{
    public function log(AuditEntry $entry): void;
}
