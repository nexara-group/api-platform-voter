<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Security\Context;

use Symfony\Component\HttpFoundation\RequestStack;

final class RequestContextFactory
{
    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function create(): ?RequestContext
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request === null) {
            return null;
        }

        return new RequestContext(
            ipAddress: $request->getClientIp(),
            userAgent: $request->headers->get('User-Agent'),
            requestTime: new \DateTimeImmutable(),
            headers: $request->headers->all(),
            method: $request->getMethod(),
            uri: $request->getRequestUri(),
            custom: [],
        );
    }
}
